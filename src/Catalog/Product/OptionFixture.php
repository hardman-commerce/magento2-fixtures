<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Eav\Model\Entity\Attribute\Option as AttributeOption;
use Magento\Framework\Exception\LocalizedException;

class OptionFixture
{
    private string $attributeCode;
    private AttributeOption $option;

    public function __construct(AttributeOption $option, string $attributeCode)
    {
        $this->attributeCode = $attributeCode;
        $this->option = $option;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function getOption(): AttributeOption
    {
        return $this->option;
    }

    /**
     * @throws LocalizedException
     */
    public function rollback(): void
    {
        OptionFixtureRollback::create()->execute($this);
    }
}
