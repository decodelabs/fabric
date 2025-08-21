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
        string $buildId
    ): void {
        $file->putContents(
            '<?php' . "\n" .
            'namespace DecodeLabs\Fabric;' . "\n" .
            'const BUILD_TIMESTAMP = ' . time() . ';' . "\n" .
            'const BUILD_ID = \'' . $buildId . '\';' . "\n" .
            'const BUILD_ROOT_PATH = __DIR__;' . "\n" .
            'const BUILD_ENV_MODE = \'' . Monarch::getEnvironment()->mode->value . '\';'
        );
    }
}
