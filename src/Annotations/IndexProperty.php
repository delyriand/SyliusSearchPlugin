<?php

namespace MonsieurBiz\SyliusSearchPlugin\Annotations;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class IndexProperty
{
    public string $name;

    public string $type;

    /**
     * @var array<\MonsieurBiz\SyliusSearchPlugin\Annotations\IndexProperty>
     */
    public array $properties = [];

    public ?string $analyzer = null;

    public ?array $fields = null;
}
