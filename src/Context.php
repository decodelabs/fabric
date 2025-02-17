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
use DecodeLabs\Veneer\Plugin;

class Context
{
    #[Plugin]
    public App $app {
        get => $this->genesis->container->get(App::class);
    }

    protected Genesis $genesis;

    public function __construct(
        Genesis $genesis
    ) {
        $this->genesis = $genesis;
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
        if (!$this->genesis->build->compiled) {
            return;
        }

        Cli::notice('Switching to source mode');
        Cli::newLine();

        /** @var array<string> */
        $args = $_SERVER['argv'] ?? [];
        $args[] = '--fabric-source';

        Systemic::runScript($args);
        $this->genesis->shutdown();
        exit;
    }
}

// Veneer
Veneer\Manager::getGlobalManager()->register(
    Context::class,
    Fabric::class
);
