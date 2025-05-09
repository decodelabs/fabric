<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Harvest\Profile as HarvestProfile;

interface App
{
    public ?string $namespace { get; }

    public function initializeLoaders(
        StackLoader $stack
    ): void;

    public function initializePlatform(): void;

    public function loadHttpProfile(): ?HarvestProfile;
}
