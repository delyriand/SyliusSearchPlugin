<?php

/*
 * This file is part of Monsieur Biz' Search plugin for Sylius.
 *
 * (c) Monsieur Biz
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSearchPlugin\AutoMapper\Transformer\ProductAttribute;

use AutoMapper\AutoMapperInterface;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;
use MonsieurBiz\SyliusSearchPlugin\AutoMapper\ConfigurationInterface;
use MonsieurBiz\SyliusSearchPlugin\AutoMapper\ProductAttributeValueReader\ReaderInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ValueTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    /**
     * @var ReaderInterface[]
     */
    private array $productAttributeValueReaders;

    public function __construct(
        private ConfigurationInterface $configuration,
        #[AutowireIterator('monsieurbiz.search.automapper.product_attribute_value_reader', defaultIndexMethod: 'getReaderCode')]
        iterable $productAttributeValueReaders
    ) {
        $this->productAttributeValueReaders = $productAttributeValueReaders instanceof \Traversable
            ? iterator_to_array($productAttributeValueReaders)
            : $productAttributeValueReaders;
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        if (!$source instanceof ProductAttributeValueInterface || null === $source->getType()) {
            return null;
        }
        if (!\array_key_exists($source->getType(), $this->productAttributeValueReaders)) {
            return null;
        }

        return $this->productAttributeValueReaders[$source->getType()]->getValue($source);
    }

    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $mapperMetadata->target === $this->configuration->getTargetClass('product_attribute')
            && $target->property === 'value'
        ;
    }
}
