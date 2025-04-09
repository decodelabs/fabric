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
use DecodeLabs\Monarch;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;

class Update implements Task
{
    public function execute(): bool
    {
        Fabric::ensureCliSource();

        $this->updateGit();
        $this->updateComposer();
        $this->build();

        Cli::newLine();
        Cli::success('Done');
        return true;
    }

    protected function updateGit(): void
    {
        Cli::info('Updating git...');

        // Git pull
        Systemic::run(
            ['git', 'pull'],
            Monarch::$paths->root
        );

        Cli::newLine();
        Cli::newLine();
    }

    protected function updateComposer(): void
    {
        Cli::info('Updating composer...');

        $args = [];

        if (!Monarch::isDevelopment()) {
            $args[] = '--no-dev';
        }

        Systemic::run(
            ['composer', 'install', ...$args],
            Monarch::$paths->root
        );

        Cli::newLine();
        Cli::newLine();
    }

    protected function build(): void
    {
        Cli::info('Building...');

        Fabric::getTaskController()->runTask('deploy/build', [
            '--fabric-source'
        ]);
    }
}
