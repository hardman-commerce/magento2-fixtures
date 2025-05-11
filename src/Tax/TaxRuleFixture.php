<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Tax;

use Magento\Tax\Api\Data\TaxRuleInterface;

class TaxRuleFixture
{
    private TaxRuleInterface $taxRule;

    public function __construct(TaxRuleInterface $taxRule)
    {
        $this->taxRule = $taxRule;
    }

    public function getTaxRule(): TaxRuleInterface
    {
        return $this->taxRule;
    }

    public function getId(): int
    {
        return (int)$this->taxRule->getId();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        TaxRuleFixtureRollback::create()->execute($this);
    }
}
