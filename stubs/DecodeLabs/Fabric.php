<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs;

use DecodeLabs\Veneer\Proxy as Proxy;
use DecodeLabs\Veneer\ProxyTrait as ProxyTrait;
use DecodeLabs\Fabric\Context as Inst;
use DecodeLabs\Fabric\App as AppPlugin;
use DecodeLabs\Pandora\Container as ContainerPlugin;
use DecodeLabs\Veneer\Plugin\Wrapper as PluginWrapper;

class Fabric implements Proxy
{
    use ProxyTrait;

    public const Veneer = 'DecodeLabs\\Fabric';
    public const VeneerTarget = Inst::class;

    protected static Inst $_veneerInstance;
    /** @var AppPlugin|PluginWrapper<AppPlugin> $app */
    public static AppPlugin|PluginWrapper $app;
    public static ContainerPlugin $container;

    public static function ensureCliSource(): void {}
};
