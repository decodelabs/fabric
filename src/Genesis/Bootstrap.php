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
    /**
     * @var list<string>
     */
    protected const array SourceArguments = [
        '--fabric-source'
    ];

    protected(set) string $hubClass = Hub::class;
    protected(set) string $applicationPath;

    protected string $vendorPath = 'vendor';
    protected string $buildVendorPath;

    /**
     * Init with root path of source Df.php and app path
     */
    public function __construct(
        ?string $hubClass = null,
        ?string $applicationPath = null,
        ?string $vendorPath = null,
        ?string $buildVendorPath = null
    ) {
        $this->hubClass = $hubClass ?? $this->hubClass;
        $this->applicationPath = $applicationPath ?? $this->prepareDefaultAppPath();
        $this->vendorPath = $vendorPath ?? $this->vendorPath;
        $this->buildVendorPath = $buildVendorPath ?? $this->vendorPath;
    }


    /**
     * Get default app path
     */
    protected function prepareDefaultAppPath(): string
    {
        /** @var string $entryPath */
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
        /** @var array<string> */
        $args = $_SERVER['argv'] ?? [];

        foreach (static::SourceArguments as $arg) {
            if (in_array($arg, $args)) {
                $sourceMode = true;
                break;
            }
        }

        if (!$sourceMode) {
            $runPath = $this->applicationPath . '/data/local/run';

            $paths = [
                $runPath . '/active1/run.php' => $runPath . '/active1/' . $this->buildVendorPath,
                $runPath . '/active2/run.php' => $runPath . '/active2/' . $this->buildVendorPath,
            ];
        } else {
            $paths = [];
        }

        $paths[__FILE__] = $this->applicationPath . '/' . $this->vendorPath;

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
            'applicationPath' => $this->applicationPath,

            // Deprecated - kept for compatibility
            'appPath' => $this->applicationPath,
        ]);

        $kernel->run();
        $kernel->shutdown();
    }
}
