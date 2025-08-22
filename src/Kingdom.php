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
}
