<?php

/**
 * Copyright © Klevu Oy & HardmanCommerce. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TddWizard\Fixtures\Store;

use Magento\Store\Api\Data\WebsiteInterface;

class WebsiteFixture
{
    private WebsiteInterface $website;

    public function __construct(WebsiteInterface $website)
    {
        $this->website = $website;
    }

    public function get(): WebsiteInterface
    {
        return $this->website;
    }

    public function getId(): int
    {
        return (int)$this->website->getId();
    }

    public function getCode(): string
    {
        return $this->website->getCode();
    }

    public function getName(): string
    {
        return $this->website->getName();
    }

    public function getDefaultGroupId(): int
    {
        return (int)$this->website->getDefaultGroupId();
    }

    /**
     * @throws \Exception
     */
    public function rollback(): void
    {
        WebsiteFixtureRollback::create()->execute($this);
    }
}
