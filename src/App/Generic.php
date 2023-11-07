<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\App;

use DecodeLabs\Fabric\App;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;

class Generic implements App
{
    protected ?string $namespace;

    /**
     * Init with app namespace
     */
    public function __construct(
        ?string $namespace
    ) {
        $this->namespace = $namespace;
    }

    /**
     * Get app namespace
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Stub loader initializer
     */
    public function initializeLoaders(StackLoader $stack): void
    {
    }

    /**
     * Stub platform initializer
     */
    public function initializePlatform(): void
    {
    }
}
