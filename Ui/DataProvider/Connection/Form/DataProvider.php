<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Ui\DataProvider\Connection\Form;

use CommerceLeague\ActiveCampaign\Model\Connection;
use Magento\Ui\DataProvider\AbstractDataProvider;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Connection\CollectionFactory;
use CommerceLeague\ActiveCampaign\Model\ResourceModel\Connection\Collection;
/**
 * Class DataProvider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        /** @var Connection[] $items */
        $items = $this->collection->getItems();

        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }

        return $this->loadedData;
    }
}
