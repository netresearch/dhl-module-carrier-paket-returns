<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\ViewModel\Sales\Rma;

use Dhl\PaketReturns\Model\Sales\OrderProvider;
use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Dhl\ShippingCore\Api\Data\RecipientStreetInterface;
use Dhl\ShippingCore\Api\SplitAddress\RecipientStreetLoaderInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\Session;
use Magento\Directory\Block\Data as DirectoryBlock;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Shipping\Block\Items;

/**
 * View model class for creating a return shipment.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Form implements ArgumentInterface
{
    /**
     * @var OrderProvider
     */
    private $orderProvider;

    /**
     * @var OrderValidator
     */
    private $orderValidator;

    /**
     * @var RecipientStreetLoaderInterface
     */
    private $recipientStreetLoader;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * The customer session model.
     *
     * @var Session
     */
    private $customerSession;

    /**
     * The URL builder instance.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Form constructor.
     *
     * @param OrderProvider $orderProvider
     * @param OrderValidator $orderValidator
     * @param RecipientStreetLoaderInterface $recipientStreetLoader
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ProductRepository $productRepository
     * @param LayoutInterface $layout
     * @param DirectoryHelper $directoryHelper
     * @param Image $imageHelper
     * @param Session $customerSession
     * @param UrlInterface $url
     */
    public function __construct(
        OrderProvider $orderProvider,
        OrderValidator $orderValidator,
        RecipientStreetLoaderInterface $recipientStreetLoader,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        ProductRepository $productRepository,
        LayoutInterface $layout,
        DirectoryHelper $directoryHelper,
        Image $imageHelper,
        Session $customerSession,
        UrlInterface $url
    ) {
        $this->orderProvider = $orderProvider;
        $this->orderValidator = $orderValidator;
        $this->recipientStreetLoader = $recipientStreetLoader;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->productRepository = $productRepository;
        $this->layout = $layout;
        $this->directoryHelper = $directoryHelper;
        $this->imageHelper = $imageHelper;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $url;
    }

    /**
     * Obtain the order's shipments if RMA creation is enabled in module config.
     *
     * @return ShipmentInterface[]
     */
    public function getShipments()
    {
        $order = $this->orderProvider->getOrder();
        if (!$order instanceof OrderInterface || !$this->orderValidator->canCreateRma($order)) {
            return [];
        }

        $orderIdFilter = $this->filterBuilder
            ->setField(ShipmentInterface::ORDER_ID)
            ->setValue($order->getEntityId())
            ->setConditionType('eq')
            ->create();

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter($orderIdFilter)->create();
        $searchResult = $this->shipmentRepository->getList($searchCriteria);
        return $searchResult->getItems();
    }

    /**
     * Obtain the order's shipping address.
     *
     * @return OrderAddressInterface|null
     */
    public function getAddress()
    {
        $order = $this->orderProvider->getOrder();
        if (!$order instanceof Order) {
            return null;
        }

        return $order->getShippingAddress();
    }

    /**
     * Get split Address for RMA Form.
     *
     * @return RecipientStreetInterface
     */
    public function getRecipientStreet()
    {
        $shippingAddress = $this->getAddress();
        return $this->recipientStreetLoader->load($shippingAddress);
    }

    /**
     * Obtain the country select markup, optionally initialized with given country.
     *
     * @param string $defaultCountry
     * @param string $name
     * @return string
     */
    public function getCountrySelectHtml(string $defaultCountry = '', string $name = 'country_id'): string
    {
        /** @var DirectoryBlock $block */
        $block = $this->layout->createBlock(DirectoryBlock::class);
        return $block->getCountryHtmlSelect($defaultCountry, $name);
    }

    /**
     * Obtain possible regions as JSON data.
     *
     * @return string
     */
    public function getRegionJson(): string
    {
        try {
            return $this->directoryHelper->getRegionJson();
        } catch (NoSuchEntityException $exception) {
            return 'false';
        }
    }

    /**
     * Obtain countries for which postal code is optional as JSON data.
     *
     * @return string
     */
    public function getCountriesWithOptionalZipJson(): string
    {
        return $this->directoryHelper->getCountriesWithOptionalZip(true);
    }

    /**
     * Obtain the preview image URL for the given shipment item.
     *
     * @param ShipmentItemInterface|Item $item
     * @return string
     */
    public function getProductThumbnailUrl(ShipmentItemInterface $item): string
    {
        try {
            $product = $this->productRepository->get($item->getSku());
        } catch (NoSuchEntityException $exception) {
            $product = $item->getOrderItem()->getProduct();
        }

        if (!$product) {
            return $this->imageHelper->getDefaultPlaceholderUrl();
        }

        return $this->imageHelper->init($product, 'cart_page_product_thumbnail')->getUrl();
    }

    /**
     * Obtain item details (name, sku, options) markup for the given shipment item.
     *
     * @param ShipmentItemInterface|Item $item
     * @return string
     */
    public function getItemDetailsHtml(ShipmentItemInterface $item): string
    {
        /** @var Items $block */
        $block = $this->layout->createBlock(
            Items::class,
            '',
            ['data' => ['renderer_list_name' => 'sales.order.shipment.renderers', 'viewModel' => $this]]
        );

        return $block->getItemHtml($item);
    }

    /**
     * Returns the form "submit" url.
     *
     * @return string
     */
    public function getSubmitUrl(): string
    {
        $order = $this->orderProvider->getOrder();

        if ($this->customerSession->isLoggedIn()) {
            $routePath = 'dhlpaketrma/returns/label';
        } else {
            $routePath = 'dhlpaketrma/returns_label/guest';
        }

        return $this->urlBuilder->getUrl($routePath, ['order_id' => $order->getEntityId()]);
    }
}
