<?php

/*
 * This file is part of Monsieur Biz' Search plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSearchPlugin\Model\Product;

use AutoMapper\Attribute\MapFrom;
use MonsieurBiz\SyliusSearchPlugin\Generated\Model\ChannelDTO;
use MonsieurBiz\SyliusSearchPlugin\Generated\Model\ImageDTO;
use MonsieurBiz\SyliusSearchPlugin\Generated\Model\PricingDTO;
use MonsieurBiz\SyliusSearchPlugin\Generated\Model\ProductAttributeDTO;
use MonsieurBiz\SyliusSearchPlugin\Generated\Model\TaxonDTO;

class ProductDTO
{
    public int $id;
    public ?string $code;
    public bool $enabled;
    public ?string $slug;
    public ?string $name;
    public ?string $description;
    public ?\DateTimeInterface $created_at;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.channels").transform(source)')]
    /** @var ChannelDTO[] */
    public array $channels;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.images").transform(source)', groups: [])]
    /** @var ImageDTO[] */
    public array $images;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.main_taxon").transform(source)', groups: [])]
    public ?TaxonDTO $main_taxon;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.taxons").transform(source)')]
    /** @var TaxonDTO[] */
    public array $product_taxons;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.attributes").transform(source)')]
    /** @var array<string, ProductAttributeDTO> */
    public array $attributes;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.options").transform(source)')]
    public array $options;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.variants").transform(source)')]
    /** @var VariantDTO[] */
    public array $variants;

    #[MapFrom(transformer: 'service("monsieurbiz.search.transformer.product.prices").transform(source)')]
    /** @var PricingDTO[] */
    public array $prices;

    public function getImagesByType(string $type): array
    {
        return array_filter($this->images, function ($image) use ($type) {
            return \is_object($image) && method_exists($image, 'getType') && $image->getType() === $type;
        });
    }
}
