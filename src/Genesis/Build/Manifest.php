<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis\Build;

use DecodeLabs\Atlas\File;
use DecodeLabs\Genesis\Build\Manifest as ManifestInterface;
use DecodeLabs\Genesis\Build\ManifestTrait;
use DecodeLabs\Monarch;

class Manifest implements ManifestInterface
{
    use ManifestTrait;

    public function writeEntryFile(
        File $file,
        string $buildId,
        string $hubClass
    ): void {
        $time = time();
        $mode = Monarch::getEnvironment()->mode->value;
        $rootPath = Monarch::getPaths()->root;

        $file->putContents(
            <<<PHP
            <?php

            /**
             * @package Fabric
             * @license http://opensource.org/licenses/MIT
             */

            declare(strict_types=1);

            namespace DecodeLabs\Fabric;

            use DecodeLabs\Genesis;
            use {$hubClass} as Hub;

            const BUILD_TIMESTAMP = {$time};
            const BUILD_ID = '{$buildId}';
            const BUILD_ROOT_PATH = __DIR__;
            const BUILD_ENV_MODE = '{$mode}';

            require_once __DIR__ . '/vendor/autoload.php';

            new Genesis(
                rootPath: '{$rootPath}',
                hubClass: Hub::class
            )->run();
            PHP
        );
    }
}
