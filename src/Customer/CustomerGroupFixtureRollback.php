<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

class CustomerGroupFixtureRollback
{
    private Registry $registry;
    private GroupRepositoryInterface $customerGroupRepository;

    public function __construct(
        Registry $registry,
        GroupRepositoryInterface $customerGroupRepository,
    ) {
        $this->registry = $registry;
        $this->customerGroupRepository = $customerGroupRepository;
    }

    public static function create(): CustomerGroupFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->get(type: Registry::class),
            $objectManager->get(type: GroupRepositoryInterface::class),
        );
    }

    /**
     * @throws LocalizedException
     * @throws StateException
     */
    public function execute(CustomerGroupFixture ...$customerGroupFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($customerGroupFixtures as $customerGroupFixture) {
            try {
                $this->customerGroupRepository->deleteById(id: (int)$customerGroupFixture->getId());
            } catch (NoSuchEntityException) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // customer group has already been removed
            }
        }
        $this->registry->unregister(key: 'isSecureArea');
    }
}
