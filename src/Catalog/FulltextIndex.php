<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Indexer\Model\IndexerFactory;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Can run the fulltext catalog index once from tests to ensure that tables for all stores are created
 */
class FulltextIndex
{
    private static bool $created = false;

    public function __construct(
        private IndexerFactory $indexerFactory,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public static function ensureTablesAreCreated(): void
    {
        if (!self::$created) {
            (new self(Bootstrap::getObjectManager()->create(IndexerFactory::class)))->reindex();
        }
    }

    /**
     * @throws \Throwable
     */
    public function reindex(): void
    {
        $indexer = $this->indexerFactory->create();
        $indexer->load('catalogsearch_fulltext');
        $indexer->reindexAll();
    }
}
