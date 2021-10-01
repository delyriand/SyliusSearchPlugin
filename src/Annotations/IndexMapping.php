<?php

namespace MonsieurBiz\SyliusSearchPlugin\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class IndexMapping
{
    /**
     * @Required
     */
    public string $indexName;

    /**
     * @var array<\MonsieurBiz\SyliusSearchPlugin\Annotations\IndexProperty>
     */
    public array $properties = [];

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    /**
     * @return array<IndexProperty>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
