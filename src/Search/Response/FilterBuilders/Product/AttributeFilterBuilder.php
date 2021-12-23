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

namespace MonsieurBiz\SyliusSearchPlugin\Search\Response\FilterBuilders\Product;

use MonsieurBiz\SyliusSearchPlugin\Model\Documentable\DocumentableInterface;
use MonsieurBiz\SyliusSearchPlugin\Search\Filter\Filter;
use MonsieurBiz\SyliusSearchPlugin\Search\Request\RequestConfiguration;
use MonsieurBiz\SyliusSearchPlugin\Search\Response\FilterBuilders\FilterBuilderInterface;
use MonsieurBiz\SyliusSearchPlugin\Search\Response\FilterInterface;

class AttributeFilterBuilder implements FilterBuilderInterface
{
    public function build(
        DocumentableInterface $documentable,
        RequestConfiguration $requestConfiguration,
        string $aggregationCode,
        array $aggregationData
    ): ?FilterInterface {
        if ('monsieurbiz_product' !== $documentable->getIndexCode() || !\in_array($aggregationCode, ['attributes', 'options'], true)) {
            return null;
        }

        $attributeAggregations = $aggregationData[$aggregationCode] ?? [];
        $attributeAggregations = $attributeAggregations[$aggregationCode] ?? $attributeAggregations;
        unset($attributeAggregations['doc_count']);
        foreach ($attributeAggregations as $attributeCode => $attributeAggregation) {
            if (isset($attributeAggregation[$attributeCode])) {
                $attributeAggregation = $attributeAggregation[$attributeCode];
            }
            $attributeNameBuckets = $attributeAggregation['names']['buckets'] ?? [];
            foreach ($attributeNameBuckets as $attributeNameBucket) {
                $attributeValueBuckets = $attributeNameBucket['values']['buckets'] ?? [];
                $filter = new Filter(
                    $requestConfiguration,
                    $attributeCode,
                    $attributeNameBucket['key'],
                    $attributeNameBucket['doc_count'],
                    $aggregationCode
                );
                foreach ($attributeValueBuckets as $attributeValueBucket) {
                    if (0 === $attributeValueBucket['doc_count']) {
                        continue;
                    }
                    if (isset($attributeValueBucket['key']) && isset($attributeValueBucket['doc_count'])) {
                        $filter->addValue($attributeValueBucket['key'], $attributeValueBucket['doc_count']);
                    }
                }

                return $filter;
            }
        }

        return null;
    }

    public function getPosition(): int
    {
        return 20;
    }
}
