<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Dovetail\Config;

use DecodeLabs\Coercion;
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
            'mode' => "{{Env::asString('ENV_MODE', 'production')}}",
            'name' => "{{Env::asString('ENV_NAME', 'production')}}",
            'appNamespace' => '{{Vendor\\AppName::class}}',
            'localDataPath' => 'data/local',
            'sharedDataPath' => 'data/shared'
        ];
    }


    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return Coercion::tryString($this->data['mode']) ?? 'testing';
    }

    /**
     * Get environment name
     */
    public function getName(): ?string
    {
        return Coercion::tryString($this->data['name']);
    }

    /**
     * Get application namespace
     */
    public function getAppNamespace(): ?string
    {
        if (
            null === ($output = Coercion::tryString($this->data['appNamespace'])) ||
            $output === '\\'
        ) {
            return null;
        }

        return rtrim($output, '\\');
    }


    /**
     * Get local data path
     */
    public function getLocalDataPath(): string
    {
        return Coercion::tryString($this->data['localDataPath']) ?? 'data/local';
    }

    /**
     * Get shared data path
     */
    public function getSharedDataPath(): string
    {
        return Coercion::tryString($this->data['sharedDataPath']) ?? 'data/shared';
    }
}
