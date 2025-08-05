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

use AutoMapper\Symfony\Attribute\AsAutoMapperExpressionService;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Inventory\Model\StockableInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product.options')]
class OptionsTransformer
{
    public function __construct(
        private AvailabilityCheckerInterface $availabilityChecker,
    ) {
    }

    public function transform(ProductInterface $product): array
    {
        $options = [];
        $currentLocale = $product->getTranslation()->getLocale();
        foreach ($product->getVariants() as $variant) {
            foreach ($variant->getOptionValues() as $optionValue) {
                if (null === $optionValue->getOption()) {
                    continue;
                }
                if (!isset($options[$optionValue->getOptionCode()])) {
                    $options[$optionValue->getOptionCode()] = [
                        'name' => $optionValue->getOption()->getTranslation($currentLocale)->getName(),
                        'values' => [],
                    ];
                }
                $isEnabled = ($options[$optionValue->getOptionCode()]['values'][$optionValue->getCode()]['enabled'] ?? false)
                    || $variant->isEnabled();
                // A variant option is considered to be in stock if the current option is enabled and is in stock
                $isInStock = ($options[$optionValue->getOptionCode()]['values'][$optionValue->getCode()]['is_in_stock'] ?? false)
                    || ($variant->isEnabled() && $this->isProductVariantInStock($variant));
                $options[$optionValue->getOptionCode()]['values'][$optionValue->getCode()] = [
                    'value' => $optionValue->getTranslation($currentLocale)->getValue(),
                    'enabled' => $isEnabled,
                    'is_in_stock' => $isInStock,
                ];
            }
        }

        foreach ($options as $optionCode => $optionValues) {
            $options[$optionCode]['values'] = array_values($optionValues['values']);
        }

        return $options;
    }

    private function isProductVariantInStock(ProductVariantInterface $productVariant): bool
    {
        if (!$productVariant instanceof StockableInterface) {
            return true;
        }

        return $this->availabilityChecker->isStockAvailable($productVariant);
    }
}
