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

#[AsAutoMapperExpressionService(alias: 'monsieurbiz.search.transformer.product.channels')]
class ChannelsTransformer
{
    public function __construct(
        private ConfigurationInterface $configuration,
        private AutoMapperInterface $autoMapper,
    ) {
    }

    public function transform(ProductInterface $product): array
    {
        $channels = [];
        $channelDTOClass = $this->configuration->getTargetClass('channel');
        foreach ($product->getChannels() as $channel) {
            $channels[] = $this->autoMapper->map($channel, $channelDTOClass);
        }

        return $channels;
    }

}
