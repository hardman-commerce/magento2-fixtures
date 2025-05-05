<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @internal Use CategoryFixture::rollback() or CategoryFixturePool::rollback() instead
 */
class CategoryFixtureRollback
{
    private readonly Registry $registry;
    private readonly CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
    ) {
        $this->registry = $registry;
        $this->categoryRepository = $categoryRepository;
    }

    public static function create(): CategoryFixtureRollback
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->get(Registry::class),
            $objectManager->get(CategoryRepositoryInterface::class),
        );
    }

    /**
     * @throws LocalizedException
     */
    public function execute(CategoryFixture ...$categoryFixtures): void
    {
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);

        foreach ($categoryFixtures as $categoryFixture) {
            try {
                $this->categoryRepository->deleteByIdentifier($categoryFixture->getId());
            } catch (NoSuchEntityException) {
                // this is fine, category has already been removed
            }
        }

        $this->registry->unregister('isSecureArea');
    }
}
