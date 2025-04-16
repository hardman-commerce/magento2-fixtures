<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\TestFramework\Helper\Bootstrap;

class CategoryBuilder
{
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryResource $categoryResource;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $productLinkFactory;
    private Category $category;
    /**
     * @var string[]
     */
    private array $skus;

    /**
     * @param string[] $skus
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryResource $categoryResource,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $productLinkFactory,
        Category $category,
        array $skus
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryResource = $categoryResource;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->productLinkFactory = $productLinkFactory;
        $this->category = $category;
        $this->skus = $skus;
    }

    public static function topLevelCategory(): CategoryBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Top Level Category');
        $category->setIsActive(true);
        $category->setPath('1/2');

        return new self(
            $objectManager->create(CategoryRepositoryInterface::class),
            $objectManager->create(CategoryResource::class),
            $objectManager->create(CategoryLinkRepositoryInterface::class),
            $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            $category,
            []
        );
    }

    public static function childCategoryOf(
        CategoryFixture $parent
    ): CategoryBuilder {
        $objectManager = Bootstrap::getObjectManager();
        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(CategoryInterface::class);

        $category->setName('Child Category');
        $category->setIsActive(true);
        $category->setPath((string)$parent->getCategory()->getPath());

        return new self(
            $objectManager->create(CategoryRepositoryInterface::class),
            $objectManager->create(CategoryResource::class),
            $objectManager->create(CategoryLinkRepositoryInterface::class),
            $objectManager->create(CategoryProductLinkInterfaceFactory::class),
            $category,
            []
        );
    }

    /**
     * Assigns products by sku. The keys of the array will be used for the sort position
     *
     * @param string[] $skus
     *
     * @return CategoryBuilder
     */
    public function withProducts(array $skus): CategoryBuilder
    {
        $builder = clone $this;
        $builder->skus = $skus;

        return $builder;
    }

    public function withDescription(string $description): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setCustomAttribute('description', $description);

        return $builder;
    }

    public function withName(string $name): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setName($name);

        return $builder;
    }

    public function withUrlKey(string $urlKey): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData('url_key', $urlKey);

        return $builder;
    }

    public function withIsActive(bool $isActive): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setIsActive($isActive);

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

        if (!$builder->category->getData('url_key')) {
            $builder->category->setData('url_key', sha1(uniqid('', true)));
        }

        // Save with global scope if not specified otherwise
        if (!$builder->category->hasData('store_id')) {
            $builder->category->setStoreId(0);
        }
        $builder->categoryResource->save($builder->category);

        foreach ($builder->skus as $position => $sku) {
            $productLink = $builder->productLinkFactory->create();
            $productLink->setSku($sku);
            $productLink->setPosition($position);
            $productLink->setCategoryId($builder->category->getId());
            $builder->categoryLinkRepository->save($productLink);
        }

        return $builder->category;
    }
}
