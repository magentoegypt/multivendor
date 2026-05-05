<?php
namespace MagentoEgypt\VendorExtend\Plugin\Model;

class Notification
{
    public function afterGetData($subject, $data) {
        foreach ($data as &$item) {
            // $item->getMessage();
            $originals = [];
            $replaced = preg_replace_callback(
                '/<strong>(.*?)<\/strong>/i',
                function ($matches) use (&$originals) {
                    if (!empty($matches[1])) {
                        $originals[] = $matches[1]; // Store the original text inside <strong>
                        return '%' . count($originals); // Or use your own logic
                    }
                },
                $item['message']
            );
            // var_dump($replaced, $originals);
            $item['message'] = __($replaced, $originals);
        }
        return $data;
    }
}
