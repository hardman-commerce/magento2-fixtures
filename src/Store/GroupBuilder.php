<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Trait\IsTransactionExceptionTrait;

class GroupBuilder
{
    use IsTransactionExceptionTrait;

    public const DEFAULT_CODE = 'tdd_store_group_1';

    private GroupInterface & AbstractModel $group;
    private WebsiteRepositoryInterface $websiteRepository;
    private CategoryCollectionFactory $categoryCollectionFactory;

    public function __construct(
        GroupInterface & AbstractModel $group,
        WebsiteRepositoryInterface $websiteRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
    ) {
        $this->group = $group;
        $this->websiteRepository = $websiteRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    public static function addGroup(): GroupBuilder //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->create(type: GroupInterface::class),
            $objectManager->get(type: WebsiteRepositoryInterface::class),
            $objectManager->get(type: CategoryCollectionFactory::class),
        );
    }

    public function withCode(string $code): GroupBuilder
    {
        $builder = clone $this;
        $builder->group->setCode(code: $code);

        return $builder;
    }

    public function withName(string $name): GroupBuilder
    {
        $builder = clone $this;
        $builder->group->setName(name: $name);

        return $builder;
    }

    public function withWebsiteId(int $websiteId): GroupBuilder
    {
        $builder = clone $this;
        $builder->group->setWebsiteId(websiteId: $websiteId);

        return $builder;
    }

    public function withRootCategoryId(int $categoryId): GroupBuilder
    {
        $builder = clone $this;
        $builder->group->setRootCategoryId(rootCategoryId: $categoryId);

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): GroupInterface
    {
        try {
            return $this->saveGroup(
                builder: $this->createGroup(),
            );
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }
    }

    public function buildWithoutSave(): GroupInterface
    {
        $builder = $this->createGroup();

        return $builder->group;
    }

    private function createGroup(): GroupBuilder
    {
        $builder = clone $this;

        if (!$builder->group->getCode()) {
            $builder->group->setCode(code: static::DEFAULT_CODE);
        }
        if (!$builder->group->getName()) {
            $builder->group->setName(
                name: ucwords(
                    string: str_replace(search: ['_', '-'], replace: ' ', subject: $builder->group->getCode()),
                ),
            );
        }
        if (null === $builder->group->getWebsiteId()) {
            $defaultWebsite = $this->websiteRepository->getDefault();
            $builder->group->setWebsiteId(websiteId: (int)($defaultWebsite->getId()));
        }
        if (null === $builder->group->getRootCategoryId()) {
            $rootCategory = $this->getFirstRootCategory();
            $builder->group->setRootCategoryId(rootCategoryId: (int)$rootCategory->getId());
        }

        return $builder;
    }

    /**
     * @throws AlreadyExistsException
     */
    private function saveGroup(GroupBuilder $builder): GroupInterface
    {
        // store group repository has no save methods so revert to resourceModel
        /** @var StoreResourceModel $storeResourceModel */
        $storeResourceModel = $this->group->getResource();
        $storeResourceModel->save(object: $builder->group);

        return $builder->group;
    }

    private function getFirstRootCategory(): CategoryInterface
    {
        $categoriesCollection = $this->categoryCollectionFactory->create();
        $categoriesCollection->addFilter(
            field: CategoryInterface::KEY_LEVEL,
            value: '1',
        );
        /** @var CategoryInterface $rootCategory */
        $rootCategory = $categoriesCollection->getFirstItem();

        return $rootCategory;
    }
}
