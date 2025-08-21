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


    public function getMode(): string
    {
        return Coercion::tryString($this->data['mode']) ?? 'testing';
    }

    public function getName(): ?string
    {
        return Coercion::tryString($this->data['name']);
    }

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

    public function getLocalDataPath(): string
    {
        return Coercion::tryString($this->data['localDataPath']) ?? 'data/local';
    }

    public function getSharedDataPath(): string
    {
        return Coercion::tryString($this->data['sharedDataPath']) ?? 'data/shared';
    }
}
