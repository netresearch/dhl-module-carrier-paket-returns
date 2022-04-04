<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Config\Backend\File;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\File\Csv;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Netresearch\ShippingCore\Model\Config\Backend\ArraySerialized;

class ReceiverIds extends ArraySerialized
{
    private const ALLOWED_EXTENSIONS = ['csv'];

    /**
     * @var RequestDataInterface
     */
    private $requestData;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Csv
     */
    private $csvReader;

    /**
     * @var CountryInformationAcquirerInterface
     */
    private $countryInfo;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Json $serializer,
        RequestDataInterface $requestData,
        UploaderFactory $uploaderFactory,
        Csv $csvReader,
        CountryInformationAcquirerInterface $countryInfo,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->requestData = $requestData;
        $this->uploaderFactory = $uploaderFactory;
        $this->csvReader = $csvReader->setDelimiter(';');
        $this->countryInfo = $countryInfo;

        parent::__construct($context, $registry, $config, $cacheTypeList, $serializer, $resource, $resourceCollection, $data);
    }


    /**
     * Receiving uploaded file data
     *
     * @see \Magento\Config\Model\Config\Backend\File::getFileData
     */
    private function getFileData(): array
    {
        $file = [];
        $value = $this->getValue();
        $tmpName = $this->requestData->getTmpName($this->getPath());
        if ($tmpName) {
            $file['tmp_name'] = $tmpName;
            $file['name'] = $this->requestData->getName($this->getPath());
        } elseif (is_array($value) && !empty($value['tmp_name'])) {
            $file['tmp_name'] = $value['tmp_name'];
            $file['name'] = $value['value'] ?? $value['name'];
        }

        return $file;
    }

    public function save()
    {
        $fileData = $this->getFileData();
        if (empty($fileData)) {
            return $this;
        }

        $fileExt = pathinfo($fileData['name'], PATHINFO_EXTENSION);

        $uploader = $this->uploaderFactory->create(['fileId' => $fileData]);
        $uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);
        $uploader->checkAllowedExtension($fileExt);
        $uploader->validateFile();

        $countriesInfo = $this->countryInfo->getCountriesInfo();
        $rows = [];
        foreach ($this->csvReader->getData($fileData['tmp_name']) as $i => $row) {
            if ($i === 0) {
                // header row
                continue;
            }

            $countryName = $row[0];
            $receiverId = $row[1];

            foreach ($countriesInfo as $countryInfo) {
                if ($countryInfo->getFullNameLocale() !== $countryName) {
                    continue;
                }

                // mimic matrix field data structure with timestamp + milliseconds indexes.
                $ms = str_pad((string) $i, 3, "0", STR_PAD_LEFT);
                $index = sprintf('_%s%s_%s', time(), $ms, $ms);
                $rows[$index] = [
                    'iso' => $countryInfo->getTwoLetterAbbreviation(),
                    'receiver_id' => $receiverId,
                ];
            }
        }

        if (empty($rows)) {
            return $this;
        }

        $this->setPath(ModuleConfig::CONFIG_PATH_RECEIVER_IDS);


        // no worries, array serialization is done in parent::beforeSave()
        $this->setValue($rows);

        return parent::save();
    }
}
