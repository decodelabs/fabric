<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Clip\Controller as ClipController;
use DecodeLabs\Fabric;
use DecodeLabs\Genesis\Context as Genesis;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer;
use DecodeLabs\Veneer\LazyLoad;

#[LazyLoad]
class Context
{
    protected Genesis $genesis;

    public function __construct(
        Genesis $genesis
    ) {
        $this->genesis = $genesis;
    }

    /**
     * Get app
     */
    public function getApp(): App
    {
        return $this->genesis->container->get(App::class);
    }

    /**
     * Get task controller
     */
    public function getTaskController(): ClipController
    {
        return $this->genesis->container->get(ClipController::class);
    }

    /**
     * Ensure CLI is running in source mode
     */
    public function ensureCliSource(): void
    {
        if (!$this->genesis->build->isCompiled()) {
            return;
        }

        Cli::notice('Switching to source mode');
        Cli::newLine();

        $args = $_SERVER['argv'] ?? [];
        $path = realpath(array_shift($args));
        $args[] = '--fabric-source';

        Systemic::runScript([$path, ...$args]);
        $this->genesis->shutdown();
    }
}

// Veneer
Veneer::register(
    Context::class,
    Fabric::class // @phpstan-ignore-line
);
