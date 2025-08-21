<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Harvest\Middleware\Greenleaf;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Kingdom as KingdomInterface;
use DecodeLabs\Kingdom\Runtime;
use DecodeLabs\Kingdom\Runtime\Clip as CliRuntime;
use DecodeLabs\Kingdom\Runtime\Harvest as HttpRuntime;
use DecodeLabs\Kingdom\RuntimeMode;
use DecodeLabs\KingdomTrait;
use DecodeLabs\Monarch;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus\Session;

class Kingdom implements KingdomInterface
{
    use KingdomTrait;

    public protected(set) string $name = 'Fabric application';

    public function initialize(): void
    {
        // Harvest profile
        if (!$this->container->has(Profile::class)) {
            $this->container->setFactory(
                Profile::class,
                fn () => Profile::loadDefault()
                    ->add('?ContentSecurityPolicy')
                    ->add('?Zest')
                    ->add(Greenleaf::class)
            );
        }

        // Runtime
        if (!$this->container->has(Runtime::class)) {
            $this->container->setType(
                Runtime::class,
                match ($this->detectRuntimeMode()) {
                    RuntimeMode::Http => HttpRuntime::class,
                    RuntimeMode::Cli => CliRuntime::class,
                }
            );
        }
    }

    public static function ensureCliSource(): void
    {
        $kingdom = Monarch::getKingdom();
        $mode = $kingdom->runtime->mode;

        if (
            !Monarch::getBuild()->compiled ||
            $mode !== RuntimeMode::Cli
        ) {
            return;
        }

        $io = Session::getDefault();

        $io->notice('Switching to source mode');
        $io->newLine();

        /** @var array<string> */
        $args = $_SERVER['argv'] ?? [];
        $args[] = '--fabric-source';

        $systemic = $kingdom->getService(Systemic::class);
        $systemic->runScript($args);
        exit(0);
    }
}
