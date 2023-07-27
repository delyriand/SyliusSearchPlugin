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

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Document;
use Jane\Component\AutoMapper\AutoMapperInterface;
use JoliCode\Elastically\Indexer as ElasticallyIndexer;
use MonsieurBiz\SyliusSearchPlugin\Model\Documentable\DocumentableInterface;
use MonsieurBiz\SyliusSearchPlugin\Search\ClientFactory;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Indexer implements IndexerInterface
{
    private ServiceRegistryInterface $documentableRegistry;

    private ChannelRepositoryInterface $channelRepository;

    private array $locales = [];

    private EntityManagerInterface $entityManager;

    private AutoMapperInterface $autoMapper;

    private ClientFactory $clientFactory;

    public function __construct(
        ServiceRegistryInterface $documentableRegistry,
        ChannelRepositoryInterface $channelRepository,
        EntityManagerInterface $entityManager,
        AutoMapperInterface $autoMapper,
        ClientFactory $clientFactory
    ) {
        $this->documentableRegistry = $documentableRegistry;
        $this->channelRepository = $channelRepository;
        $this->entityManager = $entityManager;
        $this->autoMapper = $autoMapper;
        $this->clientFactory = $clientFactory;
    }

    /**
     * Index all documentable object.
     */
    public function indexAll(?OutputInterface $output = null): void
    {
        $output = $output ?? new NullOutput();
        /** @var DocumentableInterface $documentable */
        foreach ($this->documentableRegistry->all() as $documentable) {
            $output->writeln(sprintf('Indexing <info>%s</info>', $documentable->getIndexCode()));
            $this->indexDocumentable($output, $documentable);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function indexByDocuments(DocumentableInterface $documentable, array $documents, ?string $locale = null, ?ElasticallyIndexer $indexer = null): void
    {
        if (null === $indexer) {
            $indexer = $this->clientFactory->getIndexer($documentable, $locale);
        }

        if (null === $locale && $documentable->isTranslatable()) {
            foreach ($this->getLocales() as $localeCode) {
                $this->indexByDocuments($documentable, $documents, $localeCode, $indexer);
            }

            $indexer->flush();

            return;
        }
        $index = $this->clientFactory->getIndex($documentable, $locale);
        foreach ($documents as $document) {
            if (null !== $locale && $document instanceof TranslatableInterface) {
                $document->setCurrentLocale($locale);
            }
            $dto = $this->autoMapper->map($document, $documentable->getTargetClass());
            // @phpstan-ignore-next-line
            $indexer->scheduleIndex($index, new Document((string) $document->getId(), $dto));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function deleteByDocuments(DocumentableInterface $documentable, array $documents, ?string $locale = null, ?ElasticallyIndexer $indexer = null): void
    {
        if (null === $indexer) {
            $indexer = $this->clientFactory->getIndexer($documentable, $locale);
        }

        if (null === $locale && $documentable->isTranslatable()) {
            foreach ($this->getLocales() as $localeCode) {
                $this->deleteByDocuments($documentable, $documents, $localeCode, $indexer);
            }

            $indexer->flush();

            return;
        }

        $index = $this->clientFactory->getIndex($documentable, $locale);
        foreach ($documents as $document) {
            if (null !== $locale && $document instanceof TranslatableInterface) {
                $document->setCurrentLocale($locale);
            }
            $indexer->scheduleDelete($index, (string) $document->getId());
        }
    }

    /**
     * Retrieve all used locales.
     */
    private function getLocales(): array
    {
        if (0 === \count($this->locales)) {
            $enabledChannels = $this->channelRepository->findBy(['enabled' => true]);
            /** @var ChannelInterface $channel */
            foreach ($enabledChannels as $channel) {
                $this->locales = array_merge(
                    $this->locales,
                    $channel->getLocales()->map(function (LocaleInterface $locale): string { return $locale->getCode() ?? ''; })->toArray()
                );
            }
            $this->locales = array_unique(array_filter($this->locales));
        }

        return $this->locales;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function indexDocumentable(OutputInterface $output, DocumentableInterface $documentable, ?string $locale = null): void
    {
        if (null === $locale && $documentable->isTranslatable()) {
            foreach ($this->getLocales() as $localeCode) {
                $output->writeln(
                    sprintf('Indexing <info>%s</info> for locale <info>%s</info>', $documentable->getIndexCode(), $localeCode),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $this->indexDocumentable($output, $documentable, $localeCode);
            }

            return;
        }
        $indexName = $this->clientFactory->getIndexName($documentable, $locale);
        $indexBuilder = $this->clientFactory->getIndexBuilder($documentable, $locale);
        $newIndex = $indexBuilder->createIndex($indexName, [
            'index_code' => $documentable->getIndexCode(),
            'locale' => null !== $locale ? strtolower($locale) : null,
        ]);

        $indexer = $this->clientFactory->getIndexer($documentable, $locale);
        foreach ($documentable->getDatasource()->getItems($documentable->getSourceClass()) as $item) {
            $item = $this->getRealEntity($item);
            if (null !== $locale && $item instanceof TranslatableInterface) {
                $item->setCurrentLocale($locale);
            }
            $dto = $this->autoMapper->map($item, $documentable->getTargetClass());
            // @phpstan-ignore-next-line
            $indexer->scheduleIndex($newIndex, new Document((string) $item->getId(), $dto));
        }
        $indexer->flush();

        $indexBuilder->markAsLive($newIndex, $indexName);
        $output->writeln(sprintf('Index <info>%s</info> is now live', $indexName), OutputInterface::VERBOSITY_VERBOSE);
        $indexBuilder->speedUpRefresh($newIndex);
        $indexBuilder->purgeOldIndices($indexName);
        $output->writeln(sprintf('Old indices for <info>%s</info> are now purged', $indexName), OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Convert proxies classes to the entity one.
     *
     * This avoid to retrieve the incorrect Mapper and have errors like :
     * `index: /<INDEX_NAME>/_doc/<ID> caused failed to parse`
     *
     * @param mixed $entity
     *
     * @return mixed
     */
    private function getRealEntity($entity)
    {
        if (!$entity instanceof Proxy || !method_exists($entity, 'getId')) {
            return $entity;
        }

        // Clear the entity manager to detach the proxy object
        $this->entityManager->clear(\get_class($entity));
        // Retrieve the original class name
        $entityClassName = $this->entityManager->getClassMetadata(\get_class($entity))->rootEntityName;
        // Find the object in repository from the ID
        return $this->entityManager->find($entityClassName, $entity->getId());
    }
}
