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

namespace MonsieurBiz\SyliusSearchPlugin\AutoMapper\Transformer\Product;

use AutoMapper\AutoMapperInterface;
use AutoMapper\Symfony\Attribute\AsAutoMapperExpressionService;
use MonsieurBiz\SyliusSearchPlugin\AutoMapper\ConfigurationInterface;
use MonsieurBiz\SyliusSearchPlugin\Entity\Product\SearchableInterface;
use Sylius\Component\Core\Model\ProductInterface;

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product.attributes')]
class AttributesTransformer
{
    public function __construct(
        private ConfigurationInterface $configuration,
        private AutoMapperInterface $autoMapper,
    ) {
    }

    public function transform(ProductInterface $product): array
    {
        $attributes = [];
        $currentLocale = $product->getTranslation()->getLocale();
        if (null === $currentLocale) {
            return $attributes;
        }
        $productAttributeDTOClass = $this->configuration->getTargetClass('product_attribute');
        foreach ($product->getAttributesByLocale($currentLocale, $currentLocale) as $attributeValue) {
            $attribute = $attributeValue->getAttribute();
            $attribute?->setCurrentLocale($currentLocale);
            if (null === $attributeValue->getName() || null === $attributeValue->getValue()) {
                continue;
            }
            $attribute = $attributeValue->getAttribute();
            if (!$attribute instanceof SearchableInterface || (!$attribute->isSearchable() && !$attribute->isFilterable())) {
                continue;
            }
            $attributes[$attributeValue->getCode()] = $this->autoMapper->map($attributeValue, $productAttributeDTOClass);
        }

        return $attributes;
    }
}
