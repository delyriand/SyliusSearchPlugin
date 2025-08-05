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

namespace MonsieurBiz\SyliusSearchPlugin\AutoMapper\Transformer\ProductVariant;


use AutoMapper\Symfony\Attribute\AsAutoMapperExpressionService;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Inventory\Model\StockableInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product_variant.is_in_stock')]
class IsInStockTransformer
{
    public function __construct(
        private AvailabilityCheckerInterface $availabilityChecker,
    ) {
    }

    public function transform(ProductVariantInterface $productVariant): bool
    {
        if (!$productVariant instanceof StockableInterface) {
            return true;
        }

        return $this->availabilityChecker->isStockAvailable($productVariant);
    }
}
