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
use MonsieurBiz\SyliusSearchPlugin\Context\ChannelSimulationContext;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface as ModelProductVariantInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product.prices')]
class PricesTransformer
{
    public function __construct(
        private ConfigurationInterface $configuration,
        private AutoMapperInterface $autoMapper,
        private ProductVariantResolverInterface $productVariantResolver,
        private ChannelSimulationContext $channelSimulationContext
    ) {
    }

    public function transform(ProductInterface $product): array
    {
        $prices = [];
        foreach ($product->getChannels() as $channel) {
            /** @var ChannelInterface $channel */
            $this->channelSimulationContext->setChannel($channel);
            if (
                null === ($variant = $this->productVariantResolver->getVariant($product))
                || !$variant instanceof ModelProductVariantInterface
                || null === ($channelPricing = $variant->getChannelPricingForChannel($channel))
            ) {
                $this->channelSimulationContext->setChannel(null);

                continue;
            }
            $this->channelSimulationContext->setChannel(null);
            $prices[] = $this->autoMapper->map(
                $channelPricing,
                $this->configuration->getTargetClass('pricing')
            );
        }

        return $prices;
    }
}
