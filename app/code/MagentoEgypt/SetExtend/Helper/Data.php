<?php 
namespace MagentoEgypt\SetExtend\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SET_TITLE_TABLE = 'eav_attribute_set_title';
    const REPLACE_WORD = 'attribute_set_title_';
    protected $resource;
    protected $titleData;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        parent::__construct($context);
    }

    public function LoadSetTitles($id) {
        if($this->titleData == null) {
            $select = $this->connection->select()
                ->from($this->resource->getTableName(self::SET_TITLE_TABLE));
            $rows = $this->connection->fetchAll($select) ?? [];
            $titleData = [];
            foreach($rows as $title) {
                $titleData[$title['attribute_set_id']][] = [
                    'store_id' => $title['store_id'],
                    'label' => $title['value']
                ];
            }
            $this->titleData = $titleData;
        }
        return $this->titleData[$id] ?? [];
    }

    public function getSetTitle($id)
    {
        $select = $this->connection->select()
            ->from($this->resource->getTableName(self::SET_TITLE_TABLE),['store_id', 'value'])
            ->where('attribute_set_id=?', $id);
        return $this->connection->fetchPairs($select);
    }

    public function saveSetTitles($id, $data) {
        if($id > 0) {
            $table = $this->resource->getTableName(self::SET_TITLE_TABLE);
            $this->connection->query("DELETE FROM {$table} WHERE attribute_set_id=".$id);
            if(count($data)) {
                $rows = $this->generateData($id, $data);
                $this->connection->insertMultiple($table, $rows);
            }
        }
    }

    protected function generateData($id, $data) {
        $rows = [];
        $data = array_filter($data);
        foreach($data as $key => $val)
        {
            if(trim($val) == '') continue;
            $store = str_replace(self::REPLACE_WORD, "", $key);
            $rows[] = [
                'attribute_set_id' => $id,
                'store_id' => $store,
                'value' => $val
            ];
        }
        return $rows;
    }


}