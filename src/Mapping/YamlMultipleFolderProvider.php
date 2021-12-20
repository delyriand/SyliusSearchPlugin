<?php

/*
 * This file is part of Monsieur Biz' Search plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSearchPlugin\Mapping;

use ArrayObject;
use Elastica\Exception\InvalidException;
use JoliCode\Elastically\Mapping\MappingProviderInterface;
use MonsieurBiz\SyliusSearchPlugin\Event\MappingProviderEvent;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Parser;

class YamlMultipleFolderProvider implements MappingProviderInterface
{
    private array $configurationDirectories;
    private Parser $parser;
    private FileLocatorInterface $fileLocator;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        array $configurationDirectories,
        FileLocatorInterface $fileLocator,
        EventDispatcherInterface $eventDispatcher,
        ?Parser $parser = null
    ) {
        $this->configurationDirectories = $configurationDirectories;
        $this->fileLocator = $fileLocator;
        $this->eventDispatcher = $eventDispatcher;
        $this->parser = $parser ?? new Parser();
    }

    public function provideMapping(string $indexName, array $context = []): ?array
    {
        $indexName = $context['index_code'] ?? $indexName;
        $fileName = $context['filename'] ?? ($indexName . '_mapping.yaml');
        $mappings = [];
        foreach ($this->configurationDirectories as $configurationDirectory) {
            $configurationDirectory = $this->fileLocator->locate($configurationDirectory);
            $mappingFilePath = !\is_array($configurationDirectory) ? $configurationDirectory . \DIRECTORY_SEPARATOR . $fileName : null;
            if (null === $mappingFilePath || !is_file($mappingFilePath)) {
                continue;
            }
            $mappings[] = $this->parser->parseFile($mappingFilePath);
            $mappings = $this->appendAnalyzers($configurationDirectory, $mappings, $context);
        }

        if (0 === \count($mappings)) {
            throw new InvalidException(sprintf('Mapping file "%s" not found.', $fileName));
        }

        $mapping = array_merge_recursive(...$mappings);
        $mappingProviderEvent = new MappingProviderEvent($context['index_code'] ?? $indexName, new ArrayObject($mapping));
        $this->eventDispatcher->dispatch(
            $mappingProviderEvent,
            MappingProviderEvent::EVENT_NAME
        );

        return (array) $mappingProviderEvent->getMapping();
    }

    private function appendAnalyzers(string $configurationDirectory, array $mappings, array $context): array
    {
        $analyzerFilePath = $configurationDirectory . '/analyzers.yaml';
        if (1 >= \count($mappings) && is_file($analyzerFilePath)) {
            $analyzer = $this->parser->parseFile($analyzerFilePath);
            $mappings[]['settings']['analysis'] = $analyzer;
        }

        return $this->appendLocaleAnalyzers($configurationDirectory, $mappings, $context['locale'] ?? null);
    }

    private function appendLocaleAnalyzers(string $configurationDirectory, array $mappings, ?string $locale): array
    {
        if (1 >= \count($mappings) && null !== $locale) {
            return $mappings;
        }
        foreach ($this->getLocaleCode($locale) as $localeCode) {
            $analyzerFilePath = $configurationDirectory . \DIRECTORY_SEPARATOR . 'analyzers_' . $localeCode . '.yaml';
            if (!is_file($analyzerFilePath)) {
                continue;
            }
            $mappings[]['settings']['analysis'] = $this->parser->parseFile($analyzerFilePath) ?? [];
        }

        return $mappings;
    }

    private function getLocaleCode(string $locale): array
    {
        return array_unique([
            current(explode('_', $locale)),
            $locale,
        ]);
    }
}
