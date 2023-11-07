<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Dovetail;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;

class Environment implements Config
{
    use ConfigTrait;

    /**
     * Get default config values
     */
    public static function getDefaultValues(): array
    {
        return [
            'applicationName' => 'Fabric',
            'mode' => 'development',
            'localDataPath' => 'data/local',
            'sharedDataPath' => 'data/shared'
        ];
    }


    /**
     * Get application name
     */
    public function getApplicationName(): string
    {
        return $this->data->applicationName->as('string', [
            'default' => 'Fabric'
        ]);
    }


    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return $this->data->mode->as('string', [
            'default' => 'testing'
        ]);
    }

    /**
     * Get local data path
     */
    public function getLocalDataPath(): string
    {
        return $this->data->localDataPath->as('string', [
            'default' => 'data/local'
        ]);
    }

    /**
     * Get shared data path
     */
    public function getSharedDataPath(): string
    {
        return $this->data->sharedDataPath->as('string', [
            'default' => 'data/shared'
        ]);
    }
}
