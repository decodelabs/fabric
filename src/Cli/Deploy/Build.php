<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Cli\Deploy;

use DecodeLabs\Commandment\Action;
use DecodeLabs\Commandment\Argument;
use DecodeLabs\Commandment\Request;
use DecodeLabs\Fabric;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus\Session;

#[Argument\Flag(
    name: 'force',
    shortcut: 'f',
    description: 'Force compilation'
)]
#[Argument\Flag(
    name: 'dev',
    shortcut: 'd',
    description: 'Build without compilation'
)]
#[Argument\Flag(
    name: 'clear',
    shortcut: 'c',
    description: 'Clear builds'
)]
class Build implements Action
{
    public function __construct(
        protected Session $io
    ) {
    }

    public function execute(
        Request $request,
    ): bool {
        Fabric::ensureCliSource();

        // Setup controller
        $handler = Genesis::$build->handler;

        if ($request->parameters->getAsBool('clear')) {
            // Clear
            $handler->clear();
        } else {
            // Run
            if ($request->parameters->getAsBool('dev')) {
                $handler->setCompile(false);
            } elseif ($request->parameters->getAsBool('force')) {
                $handler->setCompile(true);
            }


            $handler->run();
        }

        clearstatcache();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return true;
    }
}
