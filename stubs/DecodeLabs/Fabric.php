<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs;

use DecodeLabs\Veneer\Proxy as Proxy;
use DecodeLabs\Veneer\ProxyTrait as ProxyTrait;
use DecodeLabs\Fabric\Context as Inst;
use DecodeLabs\Fabric\App as Ref0;

class Fabric implements Proxy
{
    use ProxyTrait;

    const VENEER = 'DecodeLabs\\Fabric';
    const VENEER_TARGET = Inst::class;

    public static Inst $instance;

    public static function getApp(): Ref0 {
        return static::$instance->getApp();
    }
};
