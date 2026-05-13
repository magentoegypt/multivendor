<?php
namespace Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Theme;

use Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class Save extends Action
{    
    /**
     * File uploader
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;
    
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        Filesystem $fileSystem
    ) {
        parent::__construct($context, $registry);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_fileSystem = $fileSystem;
    }
    
    /**
     * Get groups for save
     *
     * @return array|null
     */
    protected function _getGroupsForSave()
    {
        $groups = $this->getRequest()->getPost('groups');
        $files = $this->getRequest()->getFiles('groups');
    
        if ($files && is_array($files)) {
            /**
             * Carefully merge $_FILES and $_POST information
             * None of '+=' or 'array_merge_recursive' can do this correct
             */
            foreach ($files as $groupName => $group) {
                $data = $this->_processNestedGroups($group);
                if (!empty($data)) {
                    if (!empty($groups[$groupName])) {
                        $groups[$groupName] = array_merge_recursive((array)$groups[$groupName], $data);
                    } else {
                        $groups[$groupName] = $data;
                    }
                }
            }
        }
        return $groups;
    }
    
    /**
     * Process nested groups
     *
     * @param mixed $group
     * @return array
     */
    protected function _processNestedGroups($group)
    {
        $data = [];
    
        if (isset($group['fields']) && is_array($group['fields'])) {
            foreach ($group['fields'] as $fieldName => $field) {
                if (!empty($field['value'])) {
                    $data['fields'][$fieldName] = ['value' => $field['value']];
                }
            }
        }
    
        if (isset($group['groups']) && is_array($group['groups'])) {
            foreach ($group['groups'] as $groupName => $groupData) {
                $nestedGroup = $this->_processNestedGroups($groupData);
                if (!empty($nestedGroup)) {
                    $data['groups'][$groupName] = $nestedGroup;
                }
            }
        }
    
        return $data;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        if ($this->getRequest()->getPostValue()) {
            try {
                /** @var \Vnecoms\VendorsCustomTheme\Model\Theme $model */
                $model = $this->_objectManager->create(
                    'Vnecoms\VendorsCustomTheme\Model\Theme'
                );

                $data = $this->getRequest()->getParam('theme');

                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new LocalizedException(__('Wrong rule specified.'));
                    }
                }
                
				if (!empty($data) && is_array($data)) {
					$model->addData($data);
				}
                
                $imageField = 'preview_image';
                if(isset($_FILES['theme']['name'][$imageField]) && $_FILES['theme']['name'][$imageField] != '') {
                    if(!file_exists($_FILES['theme']['tmp_name'][$imageField])) return;
                
                    /* Starting upload */
                    $uploader = $this->_fileUploaderFactory->create(['fileId' => 'theme['.$imageField.']']);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $path = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('ves_vendorscustomtheme');
                    $result = $uploader->save($path);
                    $uploadedFilePath = 'ves_vendorscustomtheme/'.trim($result['file'],'/');
                    $model->setData($imageField,$uploadedFilePath);
                
                }else{
                    $imageData = $model->getData($imageField);
                    if(is_array($imageData) && isset($imageData['delete']) && $imageData['delete']){
                        $model->setData($imageField,'');
                    }else{
                        if (isset($imageData['value']))
                        $model->setData($imageField, $imageData['value']);
                    }
                }
                
                if($section = $this->getRequest()->getParam('section')){
                    $groups = $this->_getGroupsForSave();
                    $model->setGroups([$section => $groups]);
                }
                $model->save();
                
                $this->messageManager->addSuccess(__('You saved the theme.'));
                
                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect(
                        'vendors/theme/edit',
                        ['id' => $model->getId(),'section' => $section]
                    );
                }
                return $this->_redirect('vendors/theme/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('vendors/*/edit', [
                    'id' => $this->getRequest()->getParam('rule_id'),
                    'section' => $this->getRequest()->getParam('section'),
                ]);
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    $e->getMessage()
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('vendors/*/edit', ['id' => $this->getRequest()->getParam('id'), 'section' => $this->getRequest()->getParam('section'),]);
                return;
            }
        }
    }
}
