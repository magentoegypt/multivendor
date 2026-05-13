<?php
namespace Vnecoms\RMA\Helper;

//use GeoIp2\Database\Reader;
//use Magento\Framework\Module\Dir;

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


class Geoip
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    private $gi = false;

    private $record ;


    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->_assetRepo = $assetRepo;
    }
    /**
     * set Record
     * return record
     * @var database and ip
     **/
    public function setRecord($ip)
    {
        /*
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $configReader = $object_manager->get('Magento\Framework\Module\Dir\Reader');

        $wsdlBasePath = $configReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Vnecoms_HelpDesk') . '/lib/';
        try {
            $reader = new Reader($wsdlBasePath.'GeoLite2-City.mmdb');
            $record = $reader->city($ip);
            $this->record = $record;
        } catch (\Exception $e) {
        } */
    }

    /**
     * get Record
     **/
    public function getRecord()
    {
        return $this->record ;
    }
    /**
     * return country_code
     **/
    public function getCountryCode()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->country->isoCode;
    }
    /**
     * return country_name
     **/
    public function getCountryName()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->country->name;
    }
    /**
     * return region
     **/
    public function getRegionCode()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->mostSpecificSubdivision->isoCode;
    }
    /**
     * return region name
     **/
    public function getRegionName()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->mostSpecificSubdivision->name;
    }
    /**
     * return city
     **/
    public function getCity()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->city->name;
    }
    /**
     * return city
     **/
    public function getFlags()
    {
        if (!$this->getRecord()) {
            return null;
        }

        $filename = $this->_assetRepo->getUrl(
            'Vnecoms_RM::geoip/' . $this->getCountryCode().'.png'
        );


        return $filename;
    }
    /**
     * return postal_code
     **/
    public function getPostalCode()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->postal->code;
    }
    /**
     * return latitude
     **/
    public function getLatitude()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->location->latitude;
    }
    /**
     * return longitude
     **/
    public function getLongitude()
    {
        if (!$this->getRecord()) {
            return null;
        }
        return $this->getRecord()->location->longitude;
    }
}
