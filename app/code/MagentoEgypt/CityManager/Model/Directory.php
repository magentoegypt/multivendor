<?php
declare(strict_types=1);
namespace MagentoEgypt\CityManager\Model;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
class Directory
{
    const COUNTRIES = ['EG','SA','AE','US'];
    public function __construct(private ResourceConnection $resource) {}
    public function table(): string { return $this->resource->getTableName('me_city_location'); }
    public function get(int $id): array { return $this->resource->getConnection()->fetchRow($this->resource->getConnection()->select()->from($this->table())->where('location_id = ?', $id)) ?: []; }
    public function options(string $country, string $level, int $parent = 0, int $region = 0, string $query = '', bool $all = false, int $limit = 0, int $offset = 0): array
    {
        if (!in_array($country,self::COUNTRIES,true) || !in_array($level,['region','city','locality'],true)) { throw new LocalizedException(__('Invalid location query.')); }
        $db=$this->resource->getConnection();
        $s=$db->select()->from($this->table())->where('country_id = ?', $country)->where('level = ?', $level)->order('name_en ASC');
        if (!$all) {
            $s->where('is_active = ?',1)->where('(parent_id = 0 OR parent_id IN (SELECT location_id FROM '.$this->table().' WHERE is_active = 1))');
        }
        if ($limit) $s->limit($limit,max(0,$offset));
        if ($parent) $s->where('parent_id = ?', $parent);
        if ($region) $s->where('region_id = ?', $region);
        if ($query !== '') $s->where('(name_en LIKE ? OR name_ar LIKE ?)', '%'.str_replace(['%','_'],['\\%','\\_'],$query).'%');
        // Entire selected region is returned so dropdowns never silently omit cities.
        return $db->fetchAll($s);
    }
    public function resolve(string $country, int $region, string $city): array
    {
        $parts=explode(' / ',str_replace(' — ',' / ',trim($city)),2); $name=trim($parts[0]);
        $db=$this->resource->getConnection();
        $s=$db->select()->from($this->table())->where('country_id = ?',$country)->where('level = ?','city')->where('is_active = ?',1)
          ->where('(name_en = ? OR name_ar = ?)', $name);
        if ($country !== 'AE') $s->where('region_id = ?',$region);
        $rows=$db->fetchAll($s);
        if(count($rows)!==1) throw new LocalizedException(__('Select a valid city for the selected country and region.'));
        $row=$rows[0];
        if($country==='AE') {
            $localities=$this->options('AE','locality',(int)$row['location_id']);
            if (!$localities && !isset($parts[1])) return $row;
            $matched=array_values(array_filter($localities,fn($l)=>isset($parts[1]) && in_array(trim($parts[1]),[$l['name_en'],$l['name_ar']],true)));
            if(count($matched)!==1) throw new LocalizedException(__('Select a valid locality for the selected city.'));
            $row['locality']=$matched[0];
        }
        $parent=$this->get((int)$row['parent_id']);
        if($country!=='AE' && (!$parent || !$parent['is_active'])) throw new LocalizedException(__('This region is unavailable.'));
        return $row;
    }
    public function resolveIds(string $country, int $region, int $cityId, int $localityId = 0): array
    {
        $row=$this->get($cityId);
        if(!$row || !$row['is_active'] || $row['level']!=='city' || $row['country_id']!==$country || ($country!=='AE' && (int)$row['region_id']!==$region)) throw new LocalizedException(__('Invalid city selection.'));
        if($country==='AE') {
            if (!$localityId && !$this->options('AE','locality',$cityId)) return $row;
            $locality=$this->get($localityId);
            if(!$locality || !$locality['is_active'] || $locality['level']!=='locality' || (int)$locality['parent_id']!==$cityId || $locality['country_id']!==$country) throw new LocalizedException(__('Invalid locality selection.'));
            $row['locality']=$locality;
        } else {
            $parent=$this->get((int)$row['parent_id']);
            if(!$parent || !$parent['is_active']) throw new LocalizedException(__('This region is unavailable.'));
        }
        return $row;
    }
    public function unchangedCustomerAddress($address): bool
    {
        if (!$address->getId()) return false;
        $db=$this->resource->getConnection();
        $old=$db->fetchRow($db->select()->from($this->resource->getTableName('customer_address_entity'),['country_id','city','region_id','cm_city_id','cm_locality_id'])->where('entity_id = ?', (int)$address->getId()));
        if (!$old) return false;
        foreach ($old as $key=>$value) {
            if ((string)$value !== (string)$address->getData($key)) return false;
        }
        return true;
    }
    public function exportCsv(string $country): string
    {
        if (!in_array($country,self::COUNTRIES,true)) throw new LocalizedException(__('Invalid country.'));
        $db=$this->resource->getConnection();
        $rows=$db->fetchAll($db->select()->from($this->table())->where('country_id = ?',$country)->order(new \Zend_Db_Expr("FIELD(level, 'region', 'city', 'locality')"))->order('location_id'));
        $codes=array_column($rows,'code','location_id');
        $stream=fopen('php://temp','w+');
        fputcsv($stream,['code','country_id','level','parent_code','region_id','name_en','name_ar','is_active'],',','"','');
        foreach($rows as $row) {
            $values=[$row['code'],$row['country_id'],$row['level'],$codes[$row['parent_id']]??'',$row['region_id'],$row['name_en'],$row['name_ar']??'',$row['is_active']];
            // Prevent spreadsheet formula execution, while allowing lossless reimport.
            $values=array_map(fn($v)=>preg_match('/^[=+@\-\t\r]/',(string)$v)?"'".$v:$v,$values);
            fputcsv($stream,$values,',','"','');
        }
        rewind($stream);$csv=stream_get_contents($stream);fclose($stream);return $csv;
    }
    public function importCsv(string $csv, int $actor): array
    {
        if(strlen($csv)>5*1024*1024) throw new LocalizedException(__('CSV must be at most 5 MB.'));
        $stream=fopen('php://temp','w+');fwrite($stream,preg_replace('/^\xEF\xBB\xBF/','',$csv));rewind($stream);
        $columns=['code','country_id','level','parent_code','region_id','name_en','name_ar','is_active'];
        if(fgetcsv($stream,0,',','"','')!==$columns) {fclose($stream);throw new LocalizedException(__('Use the columns and order from the exported CSV.'));}
        $db=$this->resource->getConnection();$db->beginTransaction();$line=1;$seen=[];$result=['created'=>0,'updated'=>0,'unchanged'=>0];
        try {
            while(($values=fgetcsv($stream,0,',','"',''))!==false) {
                ++$line;if($values===[null])continue;
                if($line>25001)throw new LocalizedException(__('Maximum 25000 rows.'));
                if(count($values)!==count($columns))throw new LocalizedException(__('Incorrect number of columns.'));
                $values=array_map(fn($v)=>preg_match("/^'[=+@\-\t\r]/",(string)$v)?substr($v,1):$v,$values);
                $data=array_combine($columns,$values);
                if(isset($seen[$data['code']]))throw new LocalizedException(__('Duplicate code in CSV.'));
                $seen[$data['code']]=true;
                if(!in_array($data['is_active'],['0','1'],true)||!ctype_digit($data['region_id']))throw new LocalizedException(__('Region ID must be numeric and Active must be 0 or 1.'));
                $old=$db->fetchRow($db->select()->from($this->table())->where('code = ?',$data['code']));
                $data['location_id']=$old['location_id']??0;$data['parent_id']=0;
                if($data['parent_code']!=='') {
                    $parent=$db->fetchRow($db->select()->from($this->table())->where('code = ?',$data['parent_code']));
                    if(!$parent)throw new LocalizedException(__('Parent code not found. Put parents before children.'));
                    $data['parent_id']=$parent['location_id'];
                }
                unset($data['parent_code']);
                $unchanged=(bool)$old;
                foreach($data as $key=>$value)if((string)($old[$key]??'')!==(string)$value)$unchanged=false;
                if($unchanged){++$result['unchanged'];continue;}
                $this->save($data,$actor);++$result[$old?'updated':'created'];
            }
            $db->commit();fclose($stream);return $result;
        } catch(\Throwable $e) {
            $db->rollBack();fclose($stream);
            $message=$e instanceof LocalizedException?$e->getMessage():(string)__('Unable to import this row; check duplicate codes and values.');
            throw new LocalizedException(__('CSV row %1: %2 No rows were imported.',$line,$message));
        }
    }
    public function save(array $input, int $actor): int
    {
        $id=(int)($input['location_id']??0); $old=$id?$this->get($id):[];
        if($id&&!$old) throw new LocalizedException(__('Location does not exist.'));
        $data=array_intersect_key($input,array_flip(['country_id','code','level','parent_id','region_id','name_en','name_ar','is_active']));
        foreach(['country_id','code','level','name_en'] as $k) { $data[$k]=trim((string)($data[$k]??'')); if($data[$k]==='') throw new LocalizedException(__('Required field: %1',$k)); }
        if(!in_array($data['country_id'],self::COUNTRIES,true)||!in_array($data['level'],['region','city','locality'],true)) throw new LocalizedException(__('Invalid country or level.'));
        if($data['level']!=='region') {
            foreach(['name_en','name_ar'] as $key) $data[$key]=strtr((string)($data[$key]??''),['‘'=>"'",'`'=>"'"]);
            foreach(['name_en','name_ar'] as $key) if(!empty($data[$key]) && !preg_match("/^[\\p{L}\\p{M}\\p{N}\\s\\-'’,.\\/()&]{1,100}$/u",$data[$key])) throw new LocalizedException(__('Location names must use letters, numbers and standard place-name punctuation (maximum 100 characters).'));
        }
        if(!preg_match('/^[A-Za-z0-9_.:-]{1,80}$/',$data['code']) || mb_strlen($data['name_en'])>255 || mb_strlen((string)($data['name_ar']??''))>255) throw new LocalizedException(__('Invalid code or name length.'));
        $data['parent_id']=(int)($data['parent_id']??0); $data['region_id']=(int)($data['region_id']??0); $data['is_active']=empty($data['is_active'])?0:1;
        if($data['level']==='region' && $data['parent_id']) throw new LocalizedException(__('Regions cannot have a parent.'));
        if($data['level']==='locality' && $data['country_id']!=='AE') throw new LocalizedException(__('Localities are currently used for UAE only.'));
        if($data['level']==='locality'||($data['level']==='city' && $data['country_id']!=='AE')) {
            $parent=$this->get($data['parent_id']); $expected=$data['level']==='city'?'region':'city';
            if(!$parent || $parent['country_id']!==$data['country_id'] || $parent['level']!==$expected || !$parent['is_active']) throw new LocalizedException(__('Invalid parent location.'));
            $data['region_id']=(int)$parent['region_id'];
        }
        if($data['level']==='city' && $data['country_id']==='AE' && $data['parent_id']) throw new LocalizedException(__('UAE cities are top-level selections.'));
        if(!$data['region_id']) throw new LocalizedException(__('An internal Magento region mapping is required.'));
        $db=$this->resource->getConnection();
        $same=$db->select()->from($this->table(),'location_id')->where('country_id = ?',$data['country_id'])->where('level = ?',$data['level'])->where('region_id = ?',$data['region_id'])->where('parent_id = ?',$data['parent_id'])->where('name_en = ?',$data['name_en']);
        if($id)$same->where('location_id <> ?',$id);
        if($db->fetchOne($same))throw new LocalizedException(__('A location with this name already exists under this parent.'));
        $regionCountry=$db->fetchOne($db->select()->from($this->resource->getTableName('directory_country_region'),'country_id')->where('region_id = ?',$data['region_id']));
        if($regionCountry!==$data['country_id']) throw new LocalizedException(__('Region mapping belongs to another country.'));
        if($old) foreach(['country_id','code','level','parent_id','region_id'] as $k) if((string)$old[$k] !== (string)$data[$k]) throw new LocalizedException(__('Existing location identity and parent cannot change. Deactivate it and create a new location.'));
        $db->beginTransaction();
        try {
            if($id) $db->update($this->table(),$data,['location_id = ?'=>$id]); else { $db->insert($this->table(),$data); $id=(int)$db->lastInsertId($this->table()); }
            $db->insert($this->resource->getTableName('me_city_audit'),['admin_id'=>$actor?:null,'location_id'=>$id,'before_json'=>json_encode($old),'after_json'=>json_encode($data)]);
            $db->commit(); return $id;
        } catch(\Throwable $e) { $db->rollBack(); throw $e; }
    }
}
