<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Cli\Cache;

use DecodeLabs\Commandment\Action;
use DecodeLabs\Commandment\Request;
use DecodeLabs\Stash;
use DecodeLabs\Terminus\Session;

class Purge implements Action
{
    public function __construct(
        protected Session $io
    ) {
    }

    public function execute(
        Request $request,
    ): bool {
        if (function_exists('opcache_reset')) {
            $this->io->{'.green'}('Opcache');
            opcache_reset();
        }

        if (class_exists(Stash::class)) {
            $this->io->{'.green'}('Stash');
            Stash::purge();
        }

        return true;
    }
}
