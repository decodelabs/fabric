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
use DecodeLabs\Fabric\Kingdom as FabricKingdom;
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
        protected Session $io,
        protected Genesis $genesis
    ) {
    }

    public function execute(
        Request $request,
    ): bool {
        FabricKingdom::ensureCliSource();

        // Setup controller
        $handler = $this->genesis->buildHandler;

        if ($request->parameters->asBool('clear')) {
            // Clear
            $handler->clear();
        } else {
            // Run
            if ($request->parameters->asBool('dev')) {
                $handler->setCompile(false);
            } elseif ($request->parameters->asBool('force')) {
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
