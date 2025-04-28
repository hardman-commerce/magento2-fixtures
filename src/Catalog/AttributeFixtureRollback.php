<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

class AttributeFixtureRollback
{
    private Registry $registry;
    private AttributeRepositoryInterface $attributeRepository;

    public function __construct(
        Registry $registry,
        AttributeRepositoryInterface $attributeRepository,
    ) {
        $this->registry = $registry;
        $this->attributeRepository = $attributeRepository;
    }

    public static function create(): AttributeFixtureRollback //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();

        return new self(
            $objectManager->get(type: Registry::class),
            $objectManager->get(type: AttributeRepositoryInterface::class),
        );
    }

    /**
     * Rollback attributes.
     **/
    public function execute(AttributeFixture ...$attributeFixtures): void
    {
        $this->registry->unregister(key: 'isSecureArea');
        $this->registry->register(key: 'isSecureArea', value: true);

        foreach ($attributeFixtures as $attributeFixture) {
            try {
                $this->attributeRepository->deleteById(
                    attributeId: $attributeFixture->getAttributeId(),
                );
            } catch (\Exception) {
                // this is fine, attribute has already been removed
            }
        }

        $this->registry->unregister(key: 'isSecureArea');
    }
}
