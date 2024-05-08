<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis;

use DecodeLabs\Genesis;
use DecodeLabs\Genesis\Bootstrap as Base;
use Exception;

require_once dirname(__DIR__, 3) . '/genesis/src/Bootstrap.php';

class Bootstrap extends Base
{
    public const SOURCE_ARGUMENTS = [
        '--fabric-source'
    ];

    protected string $hubClass = Hub::class;
    protected string $appPath;

    protected string $vendorPath = 'vendor';
    protected string $buildVendorPath;

    /**
     * Init with root path of source Df.php and app path
     */
    public function __construct(
        ?string $hubClass = null,
        ?string $appPath = null,
        ?string $vendorPath = null,
        ?string $buildVendorPath = null
    ) {
        $this->hubClass = $hubClass ?? $this->hubClass;
        $this->appPath = $appPath ?? $this->getDefaultAppPath();
        $this->vendorPath = $vendorPath ?? $this->vendorPath;
        $this->buildVendorPath = $buildVendorPath ?? $this->vendorPath;
    }

    /**
     * Get hub class
     */
    public function getHubClass(): string
    {
        return $this->hubClass;
    }

    /**
     * Get app path
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * Get default app path
     */
    public function getDefaultAppPath(): string
    {
        $entryPath = $_SERVER['SCRIPT_FILENAME'];

        if (!str_contains($entryPath, '/' . $this->vendorPath . '/')) {
            throw new Exception(
                'Unable to determine entry point'
            );
        }

        return explode('/' . $this->vendorPath . '/', $entryPath, 2)[0] . '/';
    }

    /**
     * Get list of possible build locations
     */
    public function getRootSearchPaths(): array
    {
        // Do we need to force loading source?
        $sourceMode = false;
        $args = $_SERVER['argv'] ?? [];

        foreach (static::SOURCE_ARGUMENTS as $arg) {
            if (in_array($arg, $args)) {
                $sourceMode = true;
                break;
            }
        }

        if (!$sourceMode) {
            $runPath = $this->appPath . '/data/local/run';

            $paths = [
                $runPath . '/active1/run.php' => $runPath . '/active1/' . $this->buildVendorPath,
                $runPath . '/active2/run.php' => $runPath . '/active2/' . $this->buildVendorPath,
            ];
        } else {
            $paths = [];
        }

        $paths[__FILE__] = $this->appPath . '/' . $this->vendorPath;

        return $paths;
    }


    /**
     * Default execution method
     */
    public function execute(
        string $vendorPath
    ): void {
        // Run app
        $kernel = Genesis::initialize($this->hubClass, [
            'appPath' => $this->getAppPath(),
        ]);

        $kernel->run();
        $kernel->shutdown();
    }
}
