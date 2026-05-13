<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import\Product;

class CategoryProcessor extends \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor
{
    /**
     * Returns ID of category by string path creating nonexistent ones.
     *
     * @param string $categoryPath
     *
     * @return int
     */
    protected function upsertCategory($categoryPath)
    {
				$index = $this->standardizeString($categoryPath);

        if (!isset($this->categories[$index])) {
            $pathParts = explode(self::DELIMITER_CATEGORY, $categoryPath);
            $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
            $path = '';

            foreach ($pathParts as $pathPart) {
                $path .= $this->standardizeString($pathPart);
                if (!isset($this->categories[$path])) {
                    throw new \Magento\Framework\Exception\AlreadyExistsException(__('Category %1 does not exist', $pathPart));
                }
                $parentId = $this->categories[$path];
                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $this->categories[$index];
    }
    
    /**
     * Reset fail categories
     */
    public function resetFailCategories()
    {
        $this->failedCategories = [];
        return $this;
    }
		
		/**
     * Standardize a string.
     * For now it performs only a lowercase action, this method is here to include more complex checks in the future
     * if needed.
     *
     * @param string $string
     * @return string
     */
    private function standardizeString($string)
    {
        return mb_strtolower($string);
    }
}
