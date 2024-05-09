<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Cli\Cache;

use DecodeLabs\Clip\Task;
use DecodeLabs\Stash;
use DecodeLabs\Terminus as Cli;

class Purge implements Task
{
    public function execute(): bool
    {
        if (function_exists('opcache_reset')) {
            Cli::{'.green'}('Opcache');
            opcache_reset();
        }

        if (class_exists(Stash::class)) {
            Cli::{'.green'}('Stash');
            Stash::purge();
        }

        return true;
    }
}
