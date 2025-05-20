<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Cli\Deploy;

use DecodeLabs\Clip;
use DecodeLabs\Commandment\Action;
use DecodeLabs\Commandment\Request;
use DecodeLabs\Fabric;
use DecodeLabs\Monarch;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus\Session;

class Update implements Action
{
    public function __construct(
        protected Session $io
    ) {
    }

    public function execute(
        Request $request,
    ): bool {
        Fabric::ensureCliSource();

        $this->updateGit();
        $this->updateComposer();
        $this->build();

        $this->io->newLine();
        $this->io->success('Done');
        return true;
    }

    protected function updateGit(): void
    {
        $this->io->info('Updating git...');

        // Git pull
        Systemic::run(
            ['git', 'pull'],
            Monarch::$paths->root
        );

        $this->io->newLine();
        $this->io->newLine();
    }

    protected function updateComposer(): void
    {
        $this->io->info('Updating composer...');

        $args = [];

        if (!Monarch::isDevelopment()) {
            $args[] = '--no-dev';
        }

        Systemic::run(
            ['composer', 'install', ...$args],
            Monarch::$paths->root
        );

        $this->io->newLine();
        $this->io->newLine();
    }

    protected function build(): void
    {
        $this->io->info('Building...');

        Clip::runAction('deploy/build', [
            '--fabric-source'
        ]);
    }
}
