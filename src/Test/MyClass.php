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

namespace MonsieurBiz\SyliusSearchPlugin\Test;

use MonsieurBiz\SyliusSearchPlugin\Annotations\IndexMapping;
use MonsieurBiz\SyliusSearchPlugin\Annotations\IndexProperty;
use MonsieurBiz\SyliusSearchPlugin\Model\Documentable\DocumentableInterface;
use MonsieurBiz\SyliusSearchPlugin\Model\Documentable\DocumentableProductTrait;

/**
 * @IndexMapping(
 *     indexName="my_class",
 *     properties={
 *         @IndexProperty(name="code", type="keyword"),
 *         @IndexProperty(name="mainTaxon", type="nested", properties={
 *             @IndexProperty(name="code", type="keyword"),
 *             @IndexProperty(name="name", type="keyword")
 *         }),
 *         @IndexProperty(name="attribute", type="nested", properties={
 *             @IndexProperty(name="name", type="text", analyzer="search_standard", fields={"keyword"={"type"="keyword"}}),
 *             @IndexProperty(name="score", type="rank_feature")
 *         }),
 *     }
 * )
 */
class MyClass implements DocumentableInterface
{
    use DocumentableProductTrait;
}
