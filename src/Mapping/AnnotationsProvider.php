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

use Doctrine\Common\Annotations\Reader;
use JoliCode\Elastically\Mapping\MappingProviderInterface;
use MonsieurBiz\SyliusSearchPlugin\Annotations\IndexMapping;
use MonsieurBiz\SyliusSearchPlugin\Annotations\IndexProperty;
use MonsieurBiz\SyliusSearchPlugin\Model\Documentable\DocumentableInterface;
use Symfony\Component\Yaml\Parser;

class AnnotationsProvider implements MappingProviderInterface
{
    private Reader $annotationReader;
    /**
     * @var iterable<DocumentableInterface>
     */
    private iterable $documentableClasses;
    private string $configurationDirectory;
    private Parser $parser;

    public function __construct(
        Reader $annotationReader,
        iterable $documentableClasses,
        string $configurationDirectory,
        ?Parser $parser = null
    ) {
        $this->annotationReader = $annotationReader;
        $this->documentableClasses = $documentableClasses;
        $this->configurationDirectory = $configurationDirectory;
        $this->parser = $parser ?? new Parser();
    }

    public function provideMapping(string $indexName, array $context = []): ?array
    {
        if (!\array_key_exists('index_identifier', $context)) {
            throw new \InvalidArgumentException('Missing "index_identifier" in the context');
        }
        $mapping = [];
        foreach ($this->documentableClasses as $documentableClass) {
            $annotation = $this->annotationReader->getMethodAnnotation(
                new \ReflectionMethod($documentableClass, 'convertToDocument'),
                IndexMapping::class
            );
            if (null === $annotation || $context['index_identifier'] !== $annotation->getIndexName()) {
                continue;
            }

            $mapping['mappings']['properties'] = $this->getPropertiesMapping($annotation->getProperties());
            break;
        }

        $analyzerFilePath = $this->configurationDirectory . '/analyzers.yaml';
        if ($mapping && is_file($analyzerFilePath)) {
            $analyzer = $this->parser->parseFile($analyzerFilePath);
            $mapping['settings']['analysis'] = array_merge_recursive($mapping['settings']['analysis'] ?? [], $analyzer);
        }

        return $mapping;
    }

    /**
     * @param IndexProperty[] $propertiesAnnotations
     */
    protected function getPropertiesMapping(array $propertiesAnnotations): array
    {
        $propertiesMapping = [];
        foreach ($propertiesAnnotations as $property) {
            $propertyMapping = ['type' => $property->type];
            if (null !== $property->analyzer) {
                $propertyMapping['analyzer'] = $property->analyzer;
            }
            if (null !== $property->fields) {
                $propertyMapping['fields'] = $property->fields;
            }
            if (0 < \count($property->properties)) {
                $propertyMapping['properties'] = $this->getPropertiesMapping($property->properties);
            }

            $propertiesMapping[$property->name] = $propertyMapping;
        }

        return $propertiesMapping;
    }
}
