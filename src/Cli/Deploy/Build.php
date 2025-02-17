<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Cli\Deploy;

use DecodeLabs\Clip\Task;
use DecodeLabs\Fabric;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class Build implements Task
{
    public function execute(): bool
    {
        Fabric::ensureCliSource();

        // Prepare arguments
        Cli::$command
            ->addArgument('-force|f', 'Force compilation')
            ->addArgument('-dev|d', 'Build without compilation')
            ->addArgument('-clear|c', 'Clear builds');


        // Setup controller
        $handler = Genesis::$build->handler;

        if (Cli::$command['clear']) {
            // Clear
            $handler->clear();
        } else {
            // Run
            if (Cli::$command['dev']) {
                $handler->setCompile(false);
            } elseif (Cli::$command['force']) {
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
