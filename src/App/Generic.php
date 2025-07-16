<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\App;

use DecodeLabs\Fabric\App;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Harvest\Profile as HarvestProfile;

class Generic implements App
{
    public protected(set) ?string $namespace;

    public function __construct(
        ?string $namespace
    ) {
        $this->namespace = $namespace;
    }

    public function initializeLoaders(StackLoader $stack): void
    {
    }

    public function initializePlatform(): void
    {
    }

    public function loadHttpProfile(): ?HarvestProfile
    {
        return null;
    }
}
