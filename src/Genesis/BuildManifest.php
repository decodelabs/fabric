<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Dir;
use DecodeLabs\Atlas\File;
use DecodeLabs\Exceptional;
use DecodeLabs\Fabric;
use DecodeLabs\Genesis;
use DecodeLabs\Genesis\Build\Manifest;
use DecodeLabs\Genesis\Build\Package;
use DecodeLabs\Genesis\Build\Task\Generic as GenericTask;
use DecodeLabs\Terminus\Session;
use Generator;

class BuildManifest implements Manifest
{
    protected string $buildId;
    protected Session $session;

    public function __construct(
        Session $session
    ) {
        $this->buildId = md5('b-' . (string)microtime(true));
        $this->session = $session;
    }

    /**
     * Get Terminus session
     */
    public function getCliSession(): Session
    {
        return $this->session;
    }

    /**
     * Create Guid for build
     */
    public function generateBuildId(): string
    {
        return $this->buildId;
    }

    /**
     * Get build temp dir
     */
    public function getBuildTempDir(): Dir
    {
        return Atlas::dir(Genesis::$hub->localDataPath . '/build/');
    }

    /**
     * Get run dir
     */
    public function getRunDir(): Dir
    {
        return Atlas::dir(Genesis::$hub->localDataPath . '/run/');
    }

    /**
     * Get entry file name
     */
    public function getEntryFileName(): string
    {
        return 'run.php';
    }

    public function getRunName1(): string
    {
        return 'active1';
    }

    public function getRunName2(): string
    {
        return 'active2';
    }


    /**
     * Scan pre compile tasks
     */
    public function scanPreCompileTasks(): Generator
    {
        yield from [];
    }

    /**
     * @return Generator<Package>
     */
    public function scanPackages(): Generator
    {
        // App
        yield new Package(
            'app',
            Atlas::dir(Genesis::$hub->applicationPath)
        );
    }

    /**
     * @return Generator<File|Dir, string>
     */
    public function scanPackage(
        Package $package
    ): Generator {
        switch ($package->name) {
            case 'app':
                yield from $this->scanAppPackage($package);
                break;

            default:
                throw Exceptional::Setup(
                    message: 'Unknown package: ' . $package->name
                );
        }
    }


    /**
     * @return Generator<File|Dir, string>
     */
    protected function scanAppPackage(
        Package $package
    ): Generator {
        $appDir = $package->source;

        yield $appDir->getDir('config') => 'config/';
        yield $appDir->getDir('src') => 'src/';
        yield $appDir->getDir('vendor') => 'vendor/';

        yield $appDir->getFile('.env') => '.env';
        yield $appDir->getFile('composer.json') => 'composer.json';
        yield $appDir->getFile('composer.lock') => 'composer.lock';
    }


    public function writeEntryFile(
        File $file
    ): void {
        $file->putContents(
            '<?php' . "\n" .
            'namespace DecodeLabs\Fabric;' . "\n" .
            'const BUILD_TIMESTAMP = ' . time() . ';' . "\n" .
            'const BUILD_ID = \'' . $this->buildId . '\';' . "\n" .
            'const BUILD_ROOT_PATH = __DIR__;' . "\n" .
            'const BUILD_ENV_MODE = \'' . Genesis::$environment->mode->value . '\';'
        );
    }

    /**
     * Scan post compile tasks
     */
    public function scanPostCompileTasks(): Generator
    {
        yield from [];
    }

    /**
     * Scan post activation tasks
     */
    public function scanPostActivationTasks(): Generator
    {
        yield new GenericTask(
            'Purging caches',
            function () {
                Fabric::getTaskController()->runTask('cache/purge');
            }
        );
    }
}
