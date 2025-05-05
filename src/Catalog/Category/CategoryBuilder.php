<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ImageUploader;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir as Directory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

class CategoryBuilder
{
    private CategoryResource $categoryResource;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $productLinkFactory;
    private Category $category;
    /**
     * @var string[]
     */
    private array $skus;
    /**
     * @var mixed[][]
     */
    private array $storeSpecificValues;

    /**
     * @param string[] $skus
     * @param mixed[][] $storeSpecificValues
     */
    public function __construct(
        CategoryResource $categoryResource,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $productLinkFactory,
        Category $category,
        array $skus,
        array $storeSpecificValues,
    ) {
        $this->categoryResource = $categoryResource;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->productLinkFactory = $productLinkFactory;
        $this->category = $category;
        $this->skus = $skus;
        $this->storeSpecificValues = $storeSpecificValues;
    }

    public static function rootCategory(): CategoryBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Root Category');
        $category->setIsActive(true);
        $category->setPath('1');
        $category->setParentId(1);

        return new self(
            categoryResource: $objectManager->create(CategoryResource::class),
            categoryLinkRepository: $objectManager->create(CategoryLinkRepositoryInterface::class),
            productLinkFactory: $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            category: $category,
            skus: [],
            storeSpecificValues: [],
        );
    }

    public static function topLevelCategory(?int $rootCategoryId = null): CategoryBuilder
    {
        $rootCategoryId = $rootCategoryId ?? static::getLowestRootCategoryId();
        $objectManager = Bootstrap::getObjectManager();

        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Top Level Category');
        $category->setIsActive(true);
        $category->setPath('1/' . $rootCategoryId);
        $category->setParentId($rootCategoryId);

        return new self(
            categoryResource: $objectManager->create(CategoryResource::class),
            categoryLinkRepository: $objectManager->create(CategoryLinkRepositoryInterface::class),
            productLinkFactory: $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            category: $category,
            skus: [],
            storeSpecificValues: [],
        );
    }

    public static function childCategoryOf(
        CategoryInterface $parent,
    ): CategoryBuilder {
        $objectManager = Bootstrap::getObjectManager();
        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Child Category');
        $category->setIsActive(true);
        $category->setPath((string)$parent->getPath());

        return new self(
            categoryResource: $objectManager->create(CategoryResource::class),
            categoryLinkRepository: $objectManager->create(CategoryLinkRepositoryInterface::class),
            productLinkFactory: $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            category: $category,
            skus: [],
            storeSpecificValues: [],
        );
    }

    /**
     * Assigns products by sku. The keys of the array will be used for the sort position
     */
    public function withProducts(array $skus): CategoryBuilder
    {
        $builder = clone $this;
        $builder->skus = $skus;

        return $builder;
    }

    public function withDescription(string $description, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId]['description'] = $description;
        } else {
            $builder->category->setCustomAttribute('description', $description);
        }

        return $builder;
    }

    public function withName(string $name, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][CategoryInterface::KEY_NAME] = $name;
        } else {
            $builder->category->setName($name);
        }

        return $builder;
    }

    public function withUrlKey(string $urlKey, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId]['url_key'] = $urlKey;
        } else {
            $builder->category->setData('url_key', $urlKey);
        }

        return $builder;
    }

    public function withIsActive(bool $isActive, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][CategoryInterface::KEY_IS_ACTIVE] = $isActive;
        } else {
            $builder->category->setIsActive($isActive);
        }

        return $builder;
    }

    public function withIsAnchor(bool $isAnchor): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData('is_anchor', $isAnchor);

        return $builder;
    }

    public function withDisplayMode(string $displayMode): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData('display_mode', $displayMode);

        return $builder;
    }

    public function withStoreId(int $storeId): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData('store_id', $storeId);

        return $builder;
    }

    public function withCustomAttributes(array $values, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            if ($storeId) {
                $builder->storeSpecificValues[$storeId][$code] = $value;
            } else {
                $builder->category->setCustomAttribute($code, $value);
            }
        }

        return $builder;
    }

    public function withImage(
        string $fileName = 'image1',
        string $mimeType = 'image/png',
        ?string $imagePath = null,
    ): CategoryBuilder {
        $builder = clone $this;

        $objectManager = Bootstrap::getObjectManager();
        $dbStorage = $objectManager->create(Database::class);
        $filesystem = $objectManager->get(Filesystem::class);
        $tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $directory = $objectManager->get(Directory::class);
        $imageUploader = $objectManager->create(
            ImageUploader::class,
            [
                'baseTmpPath' => 'catalog/tmp/category',
                'basePath' => 'media/catalog/category',
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

        $tmpFilePath = $tmpDirectory->getAbsolutePath($fileName);
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
        $imagePath = $imageUploader->moveFileFromTmp(imageName: $fileName, returnRelativePath: false);

        $builder->category->setData('image', $imagePath);

        return $builder;
    }

    public function __clone()
    {
        $this->category = clone $this->category;
    }

    /**
     * @return Category
     * @throws \Exception
     */
    public function build(): Category
    {
        $builder = clone $this;

        if (!$builder->category->getData(key: 'url_key')) {
            $builder->category->setData(key: 'url_key', value: sha1(uniqid(prefix: '', more_entropy: true)));
        }

        // Save with global scope if not specified otherwise
        if (!$builder->category->hasData(key: 'store_id')) {
            $builder->category->setStoreId(storeId: Store::DEFAULT_STORE_ID);
        }
        /** @var Category $category */
        $category = $builder->category;
        $builder->categoryResource->save(object: $category);

        foreach ($builder->skus as $position => $sku) {
            $productLink = $builder->productLinkFactory->create();
            $productLink->setSku(sku: $sku);
            $productLink->setPosition(position: $position);
            $productLink->setCategoryId(categoryId: $builder->category->getId());
            $builder->categoryLinkRepository->save(productLink: $productLink);
        }
        foreach ($builder->storeSpecificValues as $storeId => $values) {
            $storeCategory = clone $category;
            $storeCategory->setStoreId(storeId: $storeId);
            $storeCategory->addData($values);
            $builder->categoryResource->save(object: $storeCategory);
        }
        if ($builder->storeSpecificValues) {
            $this->clearCategoryRepositoryCache();
        }

        return $builder->category;
    }

    private function clearCategoryRepositoryCache(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
        $categoryRepository->_resetState();
    }

    private static function getLowestRootCategoryId(): int
    {
        $objectManager = Bootstrap::getObjectManager();

        $filter = $objectManager->create(type: Filter::class);
        $filter->setField(field: CategoryInterface::KEY_PARENT_ID);
        $filter->setValue(value: 1);

        $filterGroup = $objectManager->create(FilterGroup::class);
        $filterGroup->setFilters(filters: [$filter]);

        $sortOrder = $objectManager->get(SortOrder::class);
        $sortOrder->setField(field: 'category_id');
        $sortOrder->setDirection(direction: SortOrder::SORT_ASC);

        $searchCriteria = $objectManager->create(type: SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups(filterGroups: [$filterGroup]);
        $searchCriteria->setSortOrders(sortOrders: [$sortOrder]);
        $searchCriteria->setPageSize(pageSize: 1);
        $searchCriteria->setCurrentPage(currentPage: 1);

        $categoryList = $objectManager->create(CategoryListInterface::class);
        $categoryListSearchResult = $categoryList->getList(searchCriteria: $searchCriteria);
        $categories = $categoryListSearchResult->getItems();
        $category = array_shift(array: $categories);

        return (int)$category->getId();
    }
}
