<?php

namespace Vnecoms\VendorsRMA\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Template extends AbstractDb
{
    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_rma_request_escalate_template', 'template_id');
    }

    /**
     * Check usage of template code in other templates
     *
     * @param \Magento\Email\Model\Template $template
     * @return bool
     */
    public function checkCodeUsage(\Vnecoms\VendorsRMA\Model\Request\Escalate\Template $template)
    {
        if ($template->getTemplateActual() != 0 || $template->getTemplateActual() === null) {
            $select = $this->getConnection()->select()->from(
                $this->getMainTable(),
                'COUNT(*)'
            )->where(
                'template_code = :template_code AND type_send_mail = :type_send_mail'
            );
            $bind = [
                'template_code' => $template->getTemplateCode(),
                'type_send_mail' => $template->getData("type_send_mail")
            ];

            $templateId = $template->getId();
            if ($templateId) {
                $select->where('template_id != :template_id');
                $bind['template_id'] = $templateId;
            }

            $result = $this->getConnection()->fetchOne($select, $bind);
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set template type, added at and modified at time
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $object->setTemplateType((int)$object->getTemplateType());
        return parent::_beforeSave($object);
    }

    /**
     * Retrieve config scope and scope id of specified email template by email paths
     *
     * @param array $paths
     * @param int|string $templateId
     * @return array
     */
    public function getSystemConfigByPathsAndTemplateId($paths, $templateId)
    {
        $orWhere = [];
        $pathsCounter = 1;
        $bind = [];
        foreach ($paths as $path) {
            $pathAlias = 'path_' . $pathsCounter;
            $orWhere[] = 'path = :' . $pathAlias;
            $bind[$pathAlias] = $path;
            $pathsCounter++;
        }
        $bind['template_id'] = $templateId;
        $select = $this->getConnection()->select()->from(
            $this->getTable('core_config_data'),
            ['scope', 'scope_id', 'path']
        )->where(
            'value LIKE :template_id'
        )->where(
            join(' OR ', $orWhere)
        );

        return $this->getConnection()->fetchAll($select, $bind);
    }
}