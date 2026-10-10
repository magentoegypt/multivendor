<?php
namespace MagentoEgypt\CityManager\Plugin;
class TableRateImport
{
    public function beforeUploadAndImport($subject,$vendorId) {
        $file=$_FILES['file_import']['tmp_name']??'';
        if(!$file || !is_file($file))return [$vendorId];
        $handle=fopen($file,'r');$line=0;
        try {
            while(($row=fgetcsv($handle,0,',','"',''))!==false) {
                ++$line;if($row===[null])continue;
                if(count($row)!==8)throw new \Magento\Framework\Exception\LocalizedException(__('Shipping rate CSV row %1 must contain the existing eight columns. City/locality ID columns are not supported; use country, region and postcode.',$line));
            }
        }finally{fclose($handle);}
        return [$vendorId];
    }
}
