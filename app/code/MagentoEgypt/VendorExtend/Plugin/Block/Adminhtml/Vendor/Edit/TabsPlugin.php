<?php
namespace MagentoEgypt\VendorExtend\Plugin\Block\Adminhtml\Vendor\Edit;

/**
 * Programmatically register the Custom Theme tab on the admin Vendor Edit
 * Tabs widget. This is a robust fallback alongside the layout XML in
 * view/adminhtml/layout/vendors_index_edit.xml so the tab still appears
 * even if the layout merge is bypassed for any reason.
 */
class TabsPlugin
{
    const TAB_ID = 'custom_theme_section';
    const TAB_BLOCK_NAME = 'vendors_edit_tab_custom_theme';
    const TAB_BLOCK_CLASS = \MagentoEgypt\VendorExtend\Block\Adminhtml\Vendor\Edit\Tab\CustomTheme::class;

    /**
     * Add the Custom Theme tab if it has not already been added via layout XML.
     *
     * @param \Vnecoms\Vendors\Block\Adminhtml\Vendor\Edit\Tabs $subject
     * @return void
     */
    public function beforeToHtml(\Vnecoms\Vendors\Block\Adminhtml\Vendor\Edit\Tabs $subject)
    {
        if ($subject->getChildBlock(self::TAB_BLOCK_NAME)) {
            return;
        }

        $block = $subject->getLayout()->createBlock(
            self::TAB_BLOCK_CLASS,
            self::TAB_BLOCK_NAME
        );
        $subject->setChild(self::TAB_BLOCK_NAME, $block);
        $subject->addTab(self::TAB_ID, self::TAB_BLOCK_NAME);
    }
}
