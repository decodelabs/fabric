<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Clip;
use DecodeLabs\Fabric;
use DecodeLabs\Genesis;
use DecodeLabs\Monarch;
use DecodeLabs\Pandora\Container;
use DecodeLabs\Systemic;
use DecodeLabs\Veneer;
use DecodeLabs\Veneer\Plugin;

class Context
{
    #[Plugin]
    public App $app {
        get => $this->container->get(App::class);
    }

    #[Plugin]
    public Container $container {
        get {
            if(isset($this->container)) {
                return $this->container;
            }

            if(!Monarch::$container instanceof Container) {
                $this->container = new Container();
                Monarch::replaceContainer($this->container);
            } else {
                $this->container = Monarch::$container;
            }

            return $this->container;
        }
    }

    public function ensureCliSource(): void
    {
        if (
            !Genesis::$build->compiled ||
            Genesis::$kernel->mode !== 'Cli'
        ) {
            return;
        }

        $io = Clip::getIoSession();

        $io->notice('Switching to source mode');
        $io->newLine();

        /** @var array<string> */
        $args = $_SERVER['argv'] ?? [];
        $args[] = '--fabric-source';

        Systemic::runScript($args);
        exit(0);
    }
}

// Veneer
Veneer\Manager::getGlobalManager()->register(
    Context::class,
    Fabric::class
);
