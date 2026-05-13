<?php

namespace Vnecoms\VendorsRMA\Model\Source\Wysiwyg;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Wysiwyg Config for Editor HTML Element
 */
class Config extends \Magento\Cms\Model\Wysiwyg\Config
{

    /**
     * Return Wysiwyg config as \Magento\Framework\DataObject
     *
     * Config options description:
     *
     * enabled:                 Enabled Visual Editor or not
     * hidden:                  Show Visual Editor on page load or not
     * use_container:           Wrap Editor contents into div or not
     * no_display:              Hide Editor container or not (related to use_container)
     * translator:              Helper to translate phrases in lib
     * files_browser_*:         Files Browser (media, images) settings
     * encode_directives:       Encode template directives with JS or not
     *
     * @param array|\Magento\Framework\DataObject $data Object constructor params to override default config values
     * @return \Magento\Framework\DataObject
     */
    public function getConfig($data = [])
    {
        $config = parent::getConfig();
        $config->setData('add_widgets', false);
        $config->setData('add_variables', false);
        $config->setData('width', '98%');
        $config->setData('height', '500px');
        $config->setData('plugins', []);
        $config->setData('settings', [
            'theme_advanced_buttons1' => "bold,italic,|,justifyleft,justifycenter,justifyright,|,fontselect,fontsizeselect,|,forecolor,backcolor,|,link,unlink,image,|,bullist,numlist,|,code",
            'theme_advanced_buttons2' => false,
            'theme_advanced_buttons3' => false,
            'theme_advanced_buttons4' => false,
            'theme_advanced_statusbar_location' => false,
        ]);
        return $config;
    }

}
