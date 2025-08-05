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
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product.taxons')]
class TaxonsTransformer
{
    public function __construct(
        private ConfigurationInterface $configuration,
        private AutoMapperInterface $autoMapper,
    ) {
    }

    public function transform(ProductInterface $product): array
    {
        return array_map(function (ProductTaxonInterface $productTaxon) use ($product) {
            $taxon = $productTaxon->getTaxon();
            $currentLocale = $product->getTranslation()->getLocale();
            if (null !== $currentLocale && null !== $taxon) {
                $taxon->setCurrentLocale($currentLocale);
            }

            return $this->autoMapper->map($productTaxon, $this->configuration->getTargetClass('product_taxon'));
        }, $product->getProductTaxons()->toArray());
    }
}
