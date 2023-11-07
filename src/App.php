<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Genesis\Loader\Stack as StackLoader;

interface App
{
    public function __construct(
        ?string $namespace
    );

    public function getNamespace(): ?string;

    public function initializeLoaders(StackLoader $stack): void;
    public function initializePlatform(): void;
}
