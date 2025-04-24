<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\Data\ProductWebsiteLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductWebsiteLinkRepositoryInterface;
use Magento\Catalog\Model\ImageUploader;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Downloadable\Api\Data\LinkInterface as DownloadableLinkInterface;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as DownloadableLinkInterfaceFactory;
use Magento\Downloadable\Api\DomainManagerInterface;
use Magento\Downloadable\Api\LinkRepositoryInterface as DownloadableLinkRepositoryInterface;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir as Directory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use TddWizard\Fixtures\Exception\IndexFailedException;

//phpcs:disable SlevomatCodingStandard.Classes.ClassStructure.IncorrectGroupOrder

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class ProductBuilder
{
    private ProductRepositoryInterface $productRepository;
    private StockItemRepositoryInterface $stockItemRepository;
    private ProductWebsiteLinkRepositoryInterface $websiteLinkRepository;
    private ProductWebsiteLinkInterfaceFactory $websiteLinkFactory;
    private IndexerFactory $indexerFactory;
    private DownloadableLinkRepositoryInterface $downloadLinkRepository;
    private DownloadableLinkInterfaceFactory $downloadLinkFactory;
    private DomainManagerInterface $domainManager;
    private ProductTierPriceInterfaceFactory $tierPriceFactory;
    private ConfigurableOptionsFactory $configurableOptionsFactory;
    protected ProductInterface $product;
    /**
     * @var int[]
     */
    private array $websiteIds;
    /**
     * @var mixed[][]
     */
    private array $storeSpecificValues;
    /**
     * @var int[]
     */
    private array $categoryIds = [];
    private ?string $downloadableLinkDomain;
    /**
     * @var AttributeInterface[]
     */
    private array $configurableAttributes = [];
    /**
     * @var ProductInterface[]
     */
    private array $variantProducts = [];

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param ProductWebsiteLinkRepositoryInterface $websiteLinkRepository
     * @param ProductWebsiteLinkInterfaceFactory $websiteLinkFactory
     * @param IndexerFactory $indexerFactory
     * @param DownloadableLinkRepositoryInterface $downloadLinkRepository
     * @param DownloadableLinkInterfaceFactory $downloadLinkFactory
     * @param DomainManagerInterface $domainManager
     * @param ProductTierPriceInterfaceFactory $tierPriceFactory
     * @param ConfigurableOptionsFactory $configurableOptionsFactory
     * @param Product $product
     * @param int[] $websiteIds
     * @param mixed[] $storeSpecificValues
     * @param string|null $downloadableLinkDomain
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductWebsiteLinkRepositoryInterface $websiteLinkRepository,
        ProductWebsiteLinkInterfaceFactory $websiteLinkFactory,
        IndexerFactory $indexerFactory,
        DownloadableLinkRepositoryInterface $downloadLinkRepository,
        DownloadableLinkInterfaceFactory $downloadLinkFactory,
        DomainManagerInterface $domainManager,
        ProductTierPriceInterfaceFactory $tierPriceFactory,
        ConfigurableOptionsFactory $configurableOptionsFactory,
        Product $product,
        array $websiteIds,
        array $storeSpecificValues,
        ?string $downloadableLinkDomain = 'https://magento.test/',
    ) {
        $this->productRepository = $productRepository;
        $this->websiteLinkRepository = $websiteLinkRepository;
        $this->websiteLinkFactory = $websiteLinkFactory;
        $this->indexerFactory = $indexerFactory;
        $this->downloadLinkRepository = $downloadLinkRepository;
        $this->downloadLinkFactory = $downloadLinkFactory;
        $this->domainManager = $domainManager;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->configurableOptionsFactory = $configurableOptionsFactory;
        $this->product = $product;
        $this->websiteIds = $websiteIds;
        $this->storeSpecificValues = $storeSpecificValues;
        $this->downloadableLinkDomain = $downloadableLinkDomain;
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->product = clone $this->product;
    }

    /**
     * @return ProductBuilder
     */
    public static function aSimpleProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Product $product */
        $product = $objectManager->create(ProductInterface::class);

        $product->setTypeId(Type::TYPE_SIMPLE);
        $product->setAttributeSetId(4);
        $product->setName('TDD Test Simple Product');
        $product->setPrice(10);
        $product->setVisibility(Visibility::VISIBILITY_BOTH);
        $product->setImage('no_selection');
        $product->setThumbnail('no_selection');
        $product->setSmallImage('no_selection');
        $product->setKlevuImage('no_selection');
        $product->setImage('no_selection');
        $product->setStatus(Status::STATUS_ENABLED);
        $product->addData(
            [
                'tax_class_id' => 1,
                'description' => 'Description',
            ],
        );
        /** @var StockItemInterface $stockItem */
        $stockItem = $objectManager->create(StockItemInterface::class);
        $stockItem->setManageStock(true)
            ->setQty(100)
            ->setIsQtyDecimal(false)
            ->setIsInStock(true);

        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setStockItem($stockItem);

        return new static(
            productRepository: $objectManager->create(ProductRepositoryInterface::class),
            websiteLinkRepository: $objectManager->create(ProductWebsiteLinkRepositoryInterface::class),
            websiteLinkFactory: $objectManager->create(ProductWebsiteLinkInterfaceFactory::class),
            indexerFactory: $objectManager->create(IndexerFactory::class),
            downloadLinkRepository: $objectManager->create(DownloadableLinkRepositoryInterface::class),
            downloadLinkFactory: $objectManager->create(DownloadableLinkInterfaceFactory::class),
            domainManager: $objectManager->create(DomainManagerInterface::class),
            tierPriceFactory: $objectManager->create(ProductTierPriceInterfaceFactory::class),
            configurableOptionsFactory: $objectManager->create(ConfigurableOptionsFactory::class),
            product: $product,
            websiteIds: [1],
            storeSpecificValues: [],
        );
    }

    /**
     * @return ProductBuilder
     */
    public static function aVirtualProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Virtual Product');
        $builder->product->setTypeId(typeId: Type::TYPE_VIRTUAL);

        return $builder;
    }

    /**
     * @return ProductBuilder
     */
    public static function aDownloadableProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Downloadable Product');
        $builder->product->setTypeId(typeId: DownloadableType::TYPE_DOWNLOADABLE);

        return $builder;
    }

    /**
     * @return ProductBuilder
     */
    public static function aConfigurableProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Configurable Product');
        $builder->product->setTypeId(typeId: Configurable::TYPE_CODE);

        return $builder;
    }

    /**
     * @param AttributeInterface $attribute
     *
     * @return ProductBuilder
     */
    public function withConfigurableAttribute(
        AttributeInterface $attribute,
    ): ProductBuilder {
        $builder = clone $this;
        $attributeCode = $attribute->getAttributeCode();
        if (!array_key_exists($attributeCode, $builder->configurableAttributes)) {
            $builder->configurableAttributes[$attributeCode] = $attribute;
        }

        return $builder;
    }

    /**
     * @param ProductInterface $variantProduct
     *
     * @return ProductBuilder
     */
    public function withVariant(ProductInterface $variantProduct): ProductBuilder
    {
        $builder = clone $this;
        $builder->variantProducts[] = $variantProduct;

        return $builder;
    }

    /**
     * @param mixed[] $data
     *
     * @return ProductBuilder
     */
    public function withData(array $data): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->addData($data);

        return $builder;
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function withSku(string $sku): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setSku(sku: $sku);

        return $builder;
    }

    /**
     * @param string $name
     * @param int|null $storeId
     *
     * @return $this
     */
    public function withName(string $name, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::NAME] = $name;
        } else {
            $builder->product->setName(name: $name);
        }

        return $builder;
    }

    /**
     * @param int $status
     * @param int|null $storeId Pass store ID to set value for specific store.
     *                          Attention: Status is configured per website, will affect all stores of the same website
     *
     * @return ProductBuilder
     */
    public function withStatus(int $status, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::STATUS] = $status;
        } else {
            $builder->product->setStatus(status: $status);
        }

        return $builder;
    }

    /**
     * @param int $visibility
     * @param int|null $storeId
     *
     * @return $this
     */
    public function withVisibility(int $visibility, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::VISIBILITY] = $visibility;
        } else {
            $builder->product->setVisibility(visibility: $visibility);
        }

        return $builder;
    }

    /**
     * @param int[] $websiteIds
     *
     * @return ProductBuilder
     */
    public function withWebsiteIds(array $websiteIds): ProductBuilder
    {
        $builder = clone $this;
        $builder->websiteIds = $websiteIds;

        return $builder;
    }

    /**
     * @param int[] $categoryIds
     *
     * @return ProductBuilder
     */
    public function withCategoryIds(array $categoryIds): ProductBuilder
    {
        $builder = clone $this;
        $builder->categoryIds = $categoryIds;

        return $builder;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function withPrice(float $price): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setPrice(price: $price);

        return $builder;
    }

    /**
     * @param mixed[] $tierPrices
     *
     * @return $this
     */
    public function withTierPrices(array $tierPrices): ProductBuilder
    {
        $pricesToSet = [];
        foreach ($tierPrices as $tierPriceData) {
            if (!($tierPriceData['price'] ?? null)) {
                continue;
            }
            /** @var ProductTierPriceInterface $tierPrice */
            $tierPrice = $this->tierPriceFactory->create();
            $tierPrice->setCustomerGroupId(
                customerGroupId: $tierPriceData['customer_group_id'] ?? CustomerGroup::CUST_GROUP_ALL,
            );
            $tierPrice->setValue(value: $tierPriceData['price']);
            $tierPrice->setQty(qty: $tierPriceData['qty'] ?? 1);
            /** @var ProductTierPriceExtensionInterface|null $extensionAttributes */
            $extensionAttributes = $tierPrice->getExtensionAttributes();
            if (($tierPriceData['website_id'] ?? null)) {
                $extensionAttributes = $extensionAttributes
                                       ?? ObjectManager::getInstance()->get(ProductTierPriceExtensionInterface::class);
                $extensionAttributes->setWebsiteId($tierPriceData['website_id']);
            }
            if (($tierPriceData['price_type'] ?? null)) {
                $extensionAttributes = $extensionAttributes
                                       ?? ObjectManager::getInstance()->get(ProductTierPriceExtensionInterface::class);
                $extensionAttributes->setPercentageValue($tierPriceData['price']);
            }
            $tierPrice->setExtensionAttributes(extensionAttributes: $extensionAttributes);
            $pricesToSet[] = $tierPrice;
        }
        $builder = clone $this;
        if ($pricesToSet) {
            $builder->product->setTierPrices(tierPrices: $pricesToSet);
        }

        return $builder;
    }

    /**
     * @param int $taxClassId
     *
     * @return $this
     */
    public function withTaxClassId(int $taxClassId): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setData(key: 'tax_class_id', value: $taxClassId);

        return $builder;
    }

    /**
     * @param bool $inStock
     *
     * @return $this
     */
    public function withIsInStock(bool $inStock): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setIsInStock($inStock);

        return $builder;
    }

    /**
     * @param float $qty
     *
     * @return $this
     */
    public function withStockQty(float $qty): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setQty($qty);

        return $builder;
    }

    /**
     * @param float $backorders
     *
     * @return $this
     */
    public function withBackorders(float $backorders): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setBackorders($backorders);

        return $builder;
    }

    /**
     * @param DownloadableLinkInterface[] $links
     *
     * @return $this
     */
    public function withDownloadLinks(?array $links = []): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->setDownloadableProductLinks($links);

        return $builder;
    }

    /**
     * @param float $weight
     *
     * @return $this
     */
    public function withWeight(float $weight): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setWeight(weight: $weight);

        return $builder;
    }

    /**
     * @param mixed[] $values
     * @param int|null $storeId
     *
     * @return ProductBuilder
     */
    public function withCustomAttributes(array $values, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            if ($storeId) {
                $builder->storeSpecificValues[$storeId][$code] = $value;
            } else {
                $builder->product->setCustomAttribute(attributeCode: $code, attributeValue: $value);
            }
        }

        return $builder;
    }

    /**
     * @param string $fileName
     * @param string $imageType image, small_image, thumbnail
     * @param string $mimeType
     * @param string|null $imagePath
     *
     * @return $this
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function withImage(
        string $fileName = '1',
        string $imageType = 'image',
        string $mimeType = 'image/png',
        ?string $imagePath = null,
    ): ProductBuilder {
        $builder = clone $this;

        $objectManager = Bootstrap::getObjectManager();
        $dbStorage = $objectManager->create(type: Database::class);
        $filesystem = $objectManager->get(type: Filesystem::class);
        $tmpDirectory = $filesystem->getDirectoryWrite(directoryCode: DirectoryList::SYS_TMP);
        $directory = $objectManager->get(type: Directory::class);
        $imageUploader = $objectManager->create(
            type: ImageUploader::class,
            arguments: [
                'baseTmpPath' => 'catalog/tmp/product',
                'basePath' => 'catalog/product',
                'coreFileStorageDatabase' => $dbStorage,
                'allowedExtensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'allowedMimeTypes' => ['image/jpg', 'image/jpeg', 'image/gif', 'image/png'],
            ],
        );
        if (!$imagePath) {
            $imagePath = $directory->getDir(moduleName: 'TddWizard_Fixtures')
                         . DIRECTORY_SEPARATOR
                         . '_files'
                         . DIRECTORY_SEPARATOR
                         . 'images';
        }
        $fixtureImagePath = $imagePath . DIRECTORY_SEPARATOR . $fileName;
        $tmpFilePath = $tmpDirectory->getAbsolutePath(path: $fileName);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
        copy(from: $fixtureImagePath, to: $tmpFilePath);
        // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageError, SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
        $_FILES['image'] = [
            'name' => $fileName,
            'type' => $mimeType,
            'tmp_name' => $tmpFilePath,
            'error' => 0,
            'size' => 12500,
        ];
        $imageUploader->saveFileToTmpDir(fileId: 'image');
        $imagePath = $imageUploader->moveFileFromTmp(imageName: $fileName, returnRelativePath: true);
        $builder->product->addImageToMediaGallery(
            file: $imagePath,
            mediaAttribute: $imageType,
            move: true,
            exclude: false,
        );

        return $builder;
    }

    /**
     * @return ProductInterface
     * @throws \Exception
     */
    public function build(): ProductInterface
    {
        try {
            $product = $this->createProduct();
            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                $product = $this->associateChildren(
                    configurableProduct: $product,
                    configurableAttributes: $this->configurableAttributes,
                    variantProducts: $this->variantProducts,
                );
            }

            $indexer = $this->indexerFactory->create();
            $indexerNames = [
                'cataloginventory_stock',
                'catalog_product_price',
            ];
            foreach ($indexerNames as $indexerName) {
                $indexer->load(indexerId: $indexerName)->reindexRow(id: $product->getId());
            }

            return $product;
        } catch (\Exception $exception) {
            if (self::isTransactionException($exception) || self::isTransactionException($exception->getPrevious())) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction($exception);
            }
            throw $exception;
        }
    }

    /**
     * @return ProductInterface
     */
    public function buildWithoutSave(): ProductInterface
    {
        if (!$this->product->getSku()) {
            $this->product->setSku(sku: sha1(uniqid(more_entropy: true)));
        }
        $this->product->setCustomAttribute(attributeCode: 'url_key', attributeValue: $this->product->getSku());
        $this->product->setData(key: 'category_ids', value: $this->categoryIds);

        return clone $this->product;
    }

    /**
     * @return ProductInterface
     * @throws \Exception
     */
    private function createProduct(): ProductInterface
    {
        $builder = clone $this;
        if (!$builder->product->getSku()) {
            $builder->product->setSku(sku: sha1(uniqid(more_entropy: true)));
        }
        if (!$builder->product->getCustomAttribute(attributeCode: 'url_key')) {
            $builder->product->setCustomAttribute(
                attributeCode: 'url_key',
                attributeValue: strtolower(
                    string: str_replace(search: ' ', replace: '_', subject: $builder->product->getSku()),
                ),
            );
        }
        $builder->product->setData(key: 'category_ids', value: $builder->categoryIds);
        $product = $builder->productRepository->save(product: $builder->product);
        foreach ($builder->websiteIds as $websiteId) {
            $websiteLink = $builder->websiteLinkFactory->create();
            $websiteLink->setWebsiteId(websiteId: $websiteId);
            $websiteLink->setSku(sku: $product->getSku());
            $builder->websiteLinkRepository->save(productWebsiteLink: $websiteLink);
        }
        if (!empty($builder->websiteIds)) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes?->setWebsiteIds($builder->websiteIds);
        }
        foreach ($builder->storeSpecificValues as $storeId => $values) {
            /** @var Product $storeProduct */
            $storeProduct = clone $product;
            $storeProduct->setStoreId(storeId: $storeId);
            $storeProduct->addData($values);
            $storeProduct->save();
        }
        if ($product->getTypeId() === DownloadableType::TYPE_DOWNLOADABLE) {
            $this->setDownloadableLinks(product: $product);
        }

        return $product;
    }

    /**
     * @param ProductInterface $product
     *
     * @return void
     */
    private function setDownloadableLinks(ProductInterface $product): void
    {
        $builder = clone $this;
        $links = $builder->product->getExtensionAttributes()->getDownloadableProductLinks();
        if (!$links) {
            /** @var DownloadableLinkInterface $link */
            $link = $builder->downloadLinkFactory->create();
            $link->setTitle(title: 'Downloadable Item');
            $link->setNumberOfDownloads(numberOfDownloads: 100);
            $link->setIsShareable(isShareable: 1);
            $link->setLinkType(linkType: 'url');
            $link->setLinkUrl(linkUrl: $this->downloadableLinkDomain);
            $link->setPrice(price: 54.99);
            $link->setSortOrder(sortOrder: 1);
            $links = [$link];
        }
        $domains = array_map(
            callback: static function (DownloadableLinkInterface $link) {
                $urlParts = explode(separator: '://', string: $link->getLinkUrl());
                $url = explode(separator: '/', string: $urlParts[1]);

                return $url[0];
            },
            array: $links,
        );
        $builder->domainManager->addDomains(hosts: $domains);

        foreach ($links as $link) {
            $builder->downloadLinkRepository->save(
                sku: $product->getSku(),
                link: $link,
            );
        }
        // Removing these added domains can lead to an empty array for downloadable_domains which causes
        // ERROR: deployment configuration is corrupted. The application state is no longer valid.
        // $builder->domainManager->removeDomains($domains);
    }

    /**
     * @param ProductInterface $configurableProduct
     * @param AttributeInterface[] $configurableAttributes
     * @param ProductInterface[] $variantProducts
     *
     * @return ProductInterface
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    private function associateChildren(
        ProductInterface $configurableProduct,
        array $configurableAttributes,
        array $variantProducts,
    ): ProductInterface {
        if (!$configurableAttributes) {
            return $configurableProduct;
        }

        $attributeValues = [];
        foreach ($configurableAttributes as $attributeCode => $configurableAttribute) {
            $attributeValues[$attributeCode] = [];

            /** @var Product $variantProduct */
            foreach ($variantProducts as $variantProduct) {
                $attributeCode = $configurableAttribute->getAttributeCode();
                $attributeValues[$attributeCode][] = [
                    'label' => 'test',
                    'attribute_id' => $configurableAttribute->getId(),
                    'value_index' => $variantProduct->getData(key: $attributeCode),
                ];
            }
        }

        $configurableAttributesData = [];
        $position = 0;
        foreach ($attributeValues as $attributeCode => $values) {
            $configurableAttribute = $configurableAttributes[$attributeCode];

            $configurableAttributesData[] = [
                'attribute_id' => $configurableAttribute->getId(),
                'code' => $configurableAttribute->getAttributeCode(),
                'label' => $configurableAttribute->getDataUsingMethod(key: 'store_label'),
                'position' => $position++,
                'values' => $values,
            ];
        }

        $extensionConfigurableAttributes = $configurableProduct->getExtensionAttributes();
        if (!$extensionConfigurableAttributes) {
            $objectManager = ObjectManager::getInstance();
            $extensionConfigurableAttributes = $objectManager->create(type: ProductExtensionInterface::class);
        }

        $configurableOptions = $this->configurableOptionsFactory->create(attributesData: $configurableAttributesData);
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks(
            array_map(
                static fn (ProductInterface $variantProduct): int => (int)$variantProduct->getId(),
                $variantProducts,
            ),
        );
        $configurableProduct->setExtensionAttributes(extensionAttributes: $extensionConfigurableAttributes);

        return $this->productRepository->save(product: $configurableProduct);
    }

    /**
     * @param \Throwable|null $exception
     *
     * @return bool
     */
    private static function isTransactionException(?\Throwable $exception): bool
    {
        if ($exception === null) {
            return false;
        }

        return (bool)preg_match(
            '{please retry transaction|DDL statements are not allowed in transactions}i',
            $exception->getMessage(),
        );
    }
}
