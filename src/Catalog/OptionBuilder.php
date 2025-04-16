<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Model\Entity\Attribute\Option as AttributeOption;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Create a source-model option for an attribute.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OptionBuilder
{
    private AttributeOptionManagementInterface $optionManagement;
    private AttributeOption $option;
    private AttributeOptionLabelInterface $optionLabel;
    private string $attributeCode;

    public function __construct(
        AttributeOptionManagementInterface $optionManagement,
        AttributeOption $option,
        AttributeOptionLabelInterface $optionLabel,
        string $attributeCode
    ) {
        $this->optionManagement = $optionManagement;
        $this->option = $option;
        $this->optionLabel = $optionLabel;
        $this->attributeCode = $attributeCode;
    }

    public function __clone()
    {
        $this->option = clone $this->option;
    }

    /**
     * Create an option.
     *
     * @throws InputException
     * @throws StateException
     */
    public static function anOptionFor(string $attributeCode): OptionBuilder
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var AttributeOptionManagementInterface $optionManagement */
        $optionManagement = $objectManager->create(AttributeOptionManagementInterface::class);
        $items = $optionManagement->getItems(Product::ENTITY, $attributeCode);

        /** @var AttributeOptionLabelInterface $optionLabel */
        $optionLabel = $objectManager->create(AttributeOptionLabelInterface::class);
        $label = uniqid('Name ', true);
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($label);

        /** @var AttributeOption $option */
        $option = $objectManager->create(AttributeOption::class);
        $option->setLabel($label);
        $option->setStoreLabels([$optionLabel]);
        $option->setSortOrder(count($items) + 1);
        $option->setIsDefault(false);

        return new static(
            $optionManagement,
            $option,
            $optionLabel,
            $attributeCode
        );
    }

    public function withLabel(string $label): OptionBuilder
    {
        $builder = clone $this;
        $builder->optionLabel->setLabel($label);
        $builder->option->setStoreLabels([$builder->optionLabel]);
        $builder->option->setLabel($label);

        return $builder;
    }

    public function withSortOrder(int $sortOrder): OptionBuilder
    {
        $builder = clone $this;
        $builder->option->setSortOrder($sortOrder);

        return $builder;
    }

    public function withIsDefault(bool $isDefault): OptionBuilder
    {
        $builder = clone $this;
        $builder->option->setIsDefault($isDefault);

        return $builder;
    }

    public function withStoreId(int $storeId): OptionBuilder
    {
        $builder = clone $this;
        $builder->optionLabel->setStoreId($storeId);

        return $builder;
    }

    /**
     * Build the option and apply it to the attribute.
     *
     * @throws InputException
     * @throws StateException
     */
    public function build(): AttributeOption
    {
        $builder = clone $this;

        // add the option
        $this->optionManagement->add(
            Product::ENTITY,
            $builder->attributeCode,
            $builder->option
        );

        $optionId = $this->getOptionId();
        $builder->option->setId($optionId);

        return $builder->option;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getOptionId(): int
    {
        $objectManager = Bootstrap::getObjectManager();
        // the add option above does not return the option, so we need to retrieve it
        $attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
        $attribute = $attributeRepository->get($this->attributeCode);
        $attributeValues[$attribute->getAttributeId()] = [];

        // We have to generate a new sourceModel instance each time through to prevent it from
        // referencing its _options cache. No other way to get it to pick up newly-added values.
        $tableFactory = $objectManager->get(TableFactory::class);
        $sourceModel = $tableFactory->create();
        $sourceModel->setAttribute($attribute);
        foreach ($sourceModel->getAllOptions() as $option) {
            $attributeValues[$attribute->getAttributeId()][$option['label']] = $option['value'];
        }
        if (isset($attributeValues[$attribute->getAttributeId()][$this->optionLabel->getLabel()])) {
            return (int)$attributeValues[$attribute->getAttributeId()][$this->optionLabel->getLabel()];
        }

        throw new \RuntimeException('Error building option');
    }
}
