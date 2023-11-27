<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Fabric\Genesis\Hub;
use DecodeLabs\Genesis;
use DecodeLabs\Genesis\Bootstrap as Base;
use Exception;

require_once dirname(__DIR__, 2) . '/genesis/src/Bootstrap.php';

class Bootstrap extends Base
{
    protected string $appPath;

    /**
     * Init with root path of source Df.php and app path
     */
    public function __construct(
        ?string $appPath = null
    ) {
        $this->appPath = $appPath ?? $this->getDefaultAppPath();
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
    public static function getDefaultAppPath(): string
    {
        if (false === ($entryPath = $_SERVER['SCRIPT_FILENAME'])) {
            throw new Exception(
                'Unable to determine entry point'
            );
        }

        return dirname($entryPath, 5);
    }

    /**
     * Get list of possible build locations
     */
    public function getRootSearchPaths(): array
    {
        // Do we need to force loading source?
        $sourceMode = isset($_SERVER['argv']) && (
            in_array('--fabric-source', $_SERVER['argv'])
        );

        if (!$sourceMode) {
            $runPath = $this->appPath . '/data/local/run';

            $paths = [
                $runPath . '/active/Run.php' => $runPath . '/active/apex/vendor',
                $runPath . '/active2/Run.php' => $runPath . '/active2/apex/vendor',
            ];
        } else {
            $paths = [];
        }

        $paths[__FILE__] = $this->appPath . '/vendor';

        return $paths;
    }
}

// Init system
$bootstrap = new Bootstrap();
$bootstrap->run();

// Run app
$kernel = Genesis::initialize(Hub::class, [
    'appPath' => $bootstrap->getAppPath(),
]);

$kernel->run();
$kernel->shutdown();
