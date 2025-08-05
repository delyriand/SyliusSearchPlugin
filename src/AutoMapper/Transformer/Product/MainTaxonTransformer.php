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
use MonsieurBiz\SyliusSearchPlugin\Generated\Model\TaxonDTO;
use Sylius\Component\Core\Model\ProductInterface;

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product.main_taxon')]
class MainTaxonTransformer
{
    public function __construct(
        private ConfigurationInterface $configuration,
        private AutoMapperInterface $autoMapper,
    ) {
    }

    public function transform(ProductInterface $product): ?TaxonDTO
    {
        $mainTaxon = $product->getMainTaxon();
        if (null === $mainTaxon) {
            return null;
        }

        $currentLocale = $product->getTranslation()->getLocale();
        if (null !== $currentLocale) {
            $mainTaxon->setCurrentLocale($currentLocale);
        }

        return $this->autoMapper->map($mainTaxon, $this->configuration->getTargetClass('taxon'));
    }
}
