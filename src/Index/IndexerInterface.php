<?php

/*
 * This file is part of Monsieur Biz' Search plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSearchPlugin\Index;

use JoliCode\Elastically\Indexer as ElasticallyIndexer;
use MonsieurBiz\SyliusSearchPlugin\Model\Documentable\DocumentableInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface IndexerInterface
{
    public function indexAll(?OutputInterface $output = null): void;

    public function indexByDocuments(DocumentableInterface $documentable, array $documents, ?string $locale = null, ?ElasticallyIndexer $indexer = null): void;

    public function deleteByDocuments(DocumentableInterface $documentable, array $documents, ?string $locale = null, ?ElasticallyIndexer $indexer = null): void;
}
