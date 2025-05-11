<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Model\Entity\Attribute\OptionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option as OptionResource;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class OptionBuilderTest extends TestCase
{
    private array $options = [];
    private AttributeOptionManagementInterface $optionManagement;
    private OptionResource $optionResourceModel;
    private OptionFactory $optionFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->optionManagement = $objectManager->get(AttributeOptionManagementInterface::class);
        $this->optionFactory = $objectManager->get(OptionFactory::class);
        $this->optionResourceModel = $objectManager->get(OptionResource::class);
    }

    protected function tearDown(): void
    {
        if (!empty($this->options)) {
            foreach ($this->options as $optionFixture) {
                $optionFixture->rollBack();
            }
        }
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/dropdown_attribute.php
     */
    public function testAddOption(): void
    {
        $userDefinedAttributeCode = 'dropdown_attribute';
        $optionFixture = new OptionFixture(
            OptionBuilder::anOptionFor($userDefinedAttributeCode)->build(),
            $userDefinedAttributeCode,
        );
        $this->options[] = $optionFixture;

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->optionFactory->create();
        $this->optionResourceModel->load($option, $optionFixture->getOption()->getId());

        self::assertEquals($optionFixture->getOption()->getId(), $option->getId());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/dropdown_attribute.php
     */
    public function testAddOptionWithLabel(): void
    {
        $userDefinedAttributeCode = 'dropdown_attribute';
        $label = uniqid('Label ', true);
        $optionFixture = new OptionFixture(
            OptionBuilder::anOptionFor($userDefinedAttributeCode)->withLabel($label)->build(),
            $userDefinedAttributeCode,
        );
        $this->options[] = $optionFixture;

        /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
        $option = $this->optionFactory->create();
        $this->optionResourceModel->load($option, $optionFixture->getOption()->getId());

        $items = $this->optionManagement->getItems(Product::ENTITY, $userDefinedAttributeCode);

        self::assertEquals($optionFixture->getOption()->getId(), $option->getId());
        $foundLabel = false;
        foreach ($items as $item) {
            if ((int)$item->getValue() === $optionFixture->getOption()->getId()) {
                self::assertEquals($label, $item->getLabel());
                $foundLabel = true;
            }
        }
        if (!$foundLabel) {
            self::fail('No label found');
        }
    }
}
