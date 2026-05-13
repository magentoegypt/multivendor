<?php

namespace Vnecoms\VendorsCms\Block\Vendors\Helper\Renderer;

class Editor extends \Magento\Framework\Data\Form\Element\Editor
{
    /**
     * Return HTML button to toggling WYSIWYG.
     *
     * @param bool $visible
     *
     * @return string
     */
    protected function _getEnabledModeButtonHtml($visible = true)
    {
        $html = $this->_getButtonHtml(
            [
                'title' => $this->translate('Active Easy Mode'),
                'class' => 'action-mode ui-button btn-warning',
                'style' => $visible ? '' : 'display:none',
                'id' => 'mode'.$this->getHtmlId(),
               // 'onclick' => ''
            ]
        );

        return $html;
    }

    protected function _getDisableModeButtonHtml($visible = true)
    {
        $html = $this->_getButtonHtml(
            [
                'title' => $this->translate('Disable Easy Mode'),
                'class' => 'action-mode-disable ui-button btn-delete',
                'style' => 'display:none',
                'id' => 'disable-mode'.$this->getHtmlId(),
                // 'onclick' => ''
            ]
        );

        return $html;
    }

    /**
     * Return Editor top Buttons HTML.
     *
     * @return string
     */
    protected function _getButtonsHtml()
    {
        $buttonsHtml = '<div id="buttons'.$this->getHtmlId().'" class="buttons-set">';
        if ($this->isEnabled()) {
            $buttonsHtml .= $this->_getEnabledModeButtonHtml($this->isToggleModeVisible());
            $buttonsHtml .= $this->_getDisableModeButtonHtml($this->isToggleModeVisible());
            $buttonsHtml .= $this->_getToggleButtonHtml($this->isToggleButtonVisible());
            $buttonsHtml .= $this->_getPluginButtonsHtml($this->isHidden());
        } else {
            $buttonsHtml .= $this->_getPluginButtonsHtml(true);
        }
        $buttonsHtml .= '</div>';

        return $buttonsHtml;
    }

    public function isCmsEnabled()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function isToggleModeVisible()
    {
        return !$this->getConfig()->hasData('mode_button') || $this->getConfig('mode_button');
    }

    public function getJSONCmsConfig()
    {
        return \Laminas\Json\Json::encode($this->getConfig());
    }

    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();

        $html .= '
            <script>
                var cmsConfig = '.$this->getJSONCmsConfig().';
                window.cmsConfig = cmsConfig;

                require(["jquery", "vendorscms/stage"], function($, StageClass) {
                    $(document).off("click", ".action-mode");
                    $(document).on("click", ".action-mode", function(event) {
                        var Stage = new StageClass();
                        Stage.init($(event.currentTarget));
                    });
                });
            </script>
        ';

        return $html;
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getElementHtml()
    {
        $js = '
            <script type="text/javascript">
            //<![CDATA[
                openEditorPopup = function(url, name, specs, parent) {
                    if ((typeof popups == "undefined") || popups[name] == undefined || popups[name].closed) {
                        if (typeof popups == "undefined") {
                            popups = new Array();
                        }
                        var opener = (parent != undefined ? parent : window);
                        popups[name] = opener.open(url, name, specs);
                    } else {
                        popups[name].focus();
                    }
                    return popups[name];
                }

                closeEditorPopup = function(name) {
                    if ((typeof popups != "undefined") && popups[name] != undefined && !popups[name].closed) {
                        popups[name].close();
                    }
                }
            //]]>
            </script>';

        if ($this->isEnabled()) {
            $jsSetupObject = 'wysiwyg'.$this->getHtmlId();

            $forceLoad = '';
            if (!$this->isHidden()) {
                if ($this->getForceLoad()) {
                    $forceLoad = $jsSetupObject.'.setup("exact");';
                } else {
                    $forceLoad = 'jQuery(window).on("load", '.
                        $jsSetupObject.
                        '.setup.bind('.
                        $jsSetupObject.
                        ', "exact"));';
                }
            }

            $html = $this->_getButtonsHtml().
                $this->getStageContainerHtml().
                '<textarea name="'.
                $this->getName().
                '" title="'.
                $this->getTitle().
                '" '.
                $this->_getUiId().
                ' id="'.
                $this->getHtmlId().
                '"'.
                ' class="textarea'.
                $this->getClass().
                '" '.
                $this->serialize(
                    $this->getHtmlAttributes()
                ).
                ' >'.
                $this->getEscapedValue().
                '</textarea>'.
                $js. $this->getInlineJs($jsSetupObject, $forceLoad);

            $html = $this->_wrapIntoContainer($html);
            $html .= $this->getAfterElementHtml();

            return $html;
        } else {
            // Display only buttons to additional features
            if ($this->getPluginConfigOptions('magentowidget', 'window_url')) {
                $html = $this->_getButtonsHtml().$js.parent::getElementHtml();
                if ($this->getConfig('add_widgets')) {
                    $html .= '<script type="text/javascript">
                    //<![CDATA[
                    require(["jquery", "mage/translate", "mage/adminhtml/wysiwyg/widget"], function(jQuery){
                        (function($) {
                            $.mage.translate.add('.\Laminas\Json\Json::encode($this->getButtonTranslations()).')
                        })(jQuery);
                    });
                    //]]>
                    </script>';
                }
                $html = $this->_wrapIntoContainer($html);

                return $html;
            }

            return parent::getElementHtml();
        }
    }

    public function getStageContainerHtml()
    {
        return '<div class="vendorscms-stage-container" id="vendorscms-stage'.$this->getHtmlId().'" style="display: none;"></div>';
    }

    /**
     * Returns inline js to initialize wysiwyg adapter
     *
     * @param string $jsSetupObject
     * @param string $forceLoad
     * @return string
     */
    protected function getInlineJs($jsSetupObject, $forceLoad)
    {
        $jsString = '
                <script type="text/javascript">
                //<![CDATA[
                window.tinyMCE_GZ = window.tinyMCE_GZ || {};
                window.tinyMCE_GZ.loaded = true;
                require([
                "jquery",
                "mage/translate",
                "mage/adminhtml/events",
                "mage/adminhtml/wysiwyg/tiny_mce/setup",
                "mage/adminhtml/wysiwyg/widget"
                ], function(jQuery){' .
            "\n" .
            '  (function($) {$.mage.translate.add(' .
            $this->determineTranslateButtons() .
            ')})(jQuery);' .
            "\n" .
            $jsSetupObject .
            ' = new wysiwygSetup("' .
            $this->getHtmlId() .
            '", ' .
            $this->getJsonConfig() .
            ');' .
            $forceLoad .
            '
                    editorFormValidationHandler = ' .
            $jsSetupObject .
            '.onFormValidation.bind(' .
            $jsSetupObject .
            ');
                    Event.observe("toggle' .
            $this->getHtmlId() .
            '", "click", ' .
            $jsSetupObject .
            '.toggle.bind(' .
            $jsSetupObject .
            '));
                    varienGlobalEvents.attachEventHandler("formSubmit", editorFormValidationHandler);
                    varienGlobalEvents.clearEventHandlers("open_browser_callback");
                    varienGlobalEvents.attachEventHandler("open_browser_callback", ' .
            $jsSetupObject .
            '.openFileBrowser);
                //]]>
                });
                </script>';
        return $jsString;
    }

    /**
     * @return string
     */
    public function determineTranslateButtons() {
        $productMetadata = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\App\ProductMetadataInterface::class);
        $version         = $productMetadata->getVersion(); //will return the magento version

        $versionCompare = version_compare($version, '2.2.0', '>=');

        if ($versionCompare) {
            return \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Serialize\Serializer\Json::class)->serialize(
                $this->getButtonTranslations()
            );
        } else {
            return \Laminas\Json\Json::encode(
                $this->getButtonTranslations()
            );
        }

    }
}
