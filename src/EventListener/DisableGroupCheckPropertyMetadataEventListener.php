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

namespace MonsieurBiz\SyliusSearchPlugin\EventListener;

use AutoMapper\Event\PropertyMetadataEvent;
use MonsieurBiz\SyliusSearchPlugin\AutoMapper\ConfigurationInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(
    event: PropertyMetadataEvent::class
)]
class DisableGroupCheckPropertyMetadataEventListener
{
    public function __construct(
        private ConfigurationInterface $configuration,
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->ignored !== null) {
            return;
        }

        if (in_array($event->mapperMetadata->target, $this->getDtoClasses(), true)) {
            $event->disableGroupsCheck = true;
        }
    }

    private function getDtoClasses(): array
    {
        return $this->configuration->getTargetClasses();
    }
}
