<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis;

use DecodeLabs\Archetype;
use DecodeLabs\Clip as ClipNamespace;
use DecodeLabs\Clip\Controller as ClipController;
use DecodeLabs\Clip\Kernel as ClipKernel;
use DecodeLabs\Clip\Task as ClipTask;
use DecodeLabs\Coercion;
use DecodeLabs\Dovetail;
use DecodeLabs\Dovetail\Config as ConfigInterface;
use DecodeLabs\Dovetail\Finder\Generic as DovetailFinder;
use DecodeLabs\Exceptional;
use DecodeLabs\Fabric;
use DecodeLabs\Fabric\App;
use DecodeLabs\Fabric\Bootstrap;
use DecodeLabs\Fabric\Dovetail as ConfigNamespace;
use DecodeLabs\Fabric\Dovetail\Environment as EnvironmentConfig;
use DecodeLabs\Fluidity\CastTrait;
use DecodeLabs\Genesis\Build;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Environment\Config as EnvConfig;
use DecodeLabs\Genesis\Hub as HubInterface;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Glitch;
use DecodeLabs\Greenleaf;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer;
use Psr\Http\Server\MiddlewareInterface as HttpMiddleware;

class Hub implements HubInterface
{
    use CastTrait;

    public const ARCHETYPES = [
        Kernel::class => 'Genesis\\Kernel',
        HttpMiddleware::class => 'Harvest\\Middleware',
        ClipTask::class => 'Cli'
    ];

    protected string $envId = 'default';
    protected string $appPath;
    protected bool $analysis = false;
    protected App $app;
    protected Context $context;

    public function __construct(
        Context $context,
        array $options
    ) {
        $this->context = $context;

        if ($options['analysis'] ?? false) {
            $this->prepareForAnalysis();
            return;
        }

        $this->appPath =
            Coercion::toStringOrNull($options['appPath']) ??
            Bootstrap::getDefaultAppPath();
    }


    private function prepareForAnalysis(): void
    {
        $this->analysis = true;
        $this->envId = 'analysis';

        if (!$appDir = getcwd()) {
            throw Exceptional::Runtime('Unable to get current working directory');
        }

        $hasAppFile = file_exists($appDir . '/App.php');

        if (!$hasAppFile) {
            $appDir = dirname(dirname(__DIR__)) . '/tests';
        }

        $this->appPath = $appDir;
    }

    /**
     * Get application path
     */
    public function getApplicationPath(): string
    {
        return $this->appPath;
    }

    /**
     * Get local data path
     */
    public function getLocalDataPath(): string
    {
        static $path;

        if (!isset($path)) {
            $path = $this->appPath . '/' . ltrim(
                EnvironmentConfig::load()->getLocalDataPath(),
                '/'
            );
        }

        return $path;
    }

    /**
     * Get shared data path
     */
    public function getSharedDataPath(): string
    {
        static $path;

        if (!isset($path)) {
            $path = $this->appPath . '/' . ltrim(
                EnvironmentConfig::load()->getSharedDataPath(),
                '/'
            );
        }

        return $path;
    }

    /**
     * Get application name
     */
    public function getApplicationName(): string
    {
        static $name;

        if (!isset($name)) {
            $name = EnvironmentConfig::load()->getAppName();
        }

        return $name;
    }

    /**
     * Load build info
     */
    public function loadBuild(): Build
    {
        // Ensure compile constants
        if (!defined('DecodeLabs\\Fabric\\BUILD_TIMESTAMP')) {
            define('DecodeLabs\\Fabric\\BUILD_TIMESTAMP', null);
            define('DecodeLabs\\Fabric\\BUILD_ID', null);
            define('DecodeLabs\\Fabric\\BUILD_ROOT_PATH', null);
            define('DecodeLabs\\Fabric\\BUILD_ENV_MODE', null);
        }

        // Work out root path
        if (
            /** @phpstan-ignore-next-line */
            Fabric\BUILD_ROOT_PATH !== null &&
            is_dir((string)Fabric\BUILD_ROOT_PATH)
        ) {
            $buildPath = Fabric\BUILD_ROOT_PATH;
        } elseif ($this->analysis) {
            $buildPath = dirname(dirname(__DIR__));
        } else {
            $buildPath = $this->appPath . '/vendor/decodelabs/fabric';
        }

        // Create build info
        return new Build(
            $this->context,
            $buildPath,
            Fabric\BUILD_TIMESTAMP
        );
    }

    /**
     * Setup loaders
     */
    public function initializeLoaders(
        StackLoader $stack
    ): void {
        // Dovetail
        if ($this->context->build->isCompiled()) {
            Dovetail::setEnvPath($this->context->build->path);

            Dovetail::setFinder(new DovetailFinder(
                $this->context->build->path
            ));
        } else {
            Dovetail::setEnvPath($this->appPath);
        }

        Archetype::extend(
            ConfigInterface::class,
            ConfigNamespace::class // @phpstan-ignore-line
        );



        // App
        $namespace = EnvironmentConfig::load()->getAppNamespace();

        if ($namespace !== null) {
            Archetype::extend(App::class, $namespace);
        }

        $this->app = $this->context->container->getWith(App::class, [
            'namespace' => $namespace
        ]);

        $this->app->initializeLoaders($stack);
    }

    /**
     * Load env config
     */
    public function loadEnvironmentConfig(): EnvConfig
    {
        if ($this->analysis) {
            return new EnvConfig\Development($this->envId);
        }

        /** @phpstan-ignore-next-line */
        $name = ucfirst(Fabric\BUILD_ENV_MODE ?? EnvironmentConfig::load()->getMode());

        /** @var class-string<EnvConfig\Development|EnvConfig\Testing|EnvConfig\Production> */
        $class = EnvConfig::class . '\\' . $name;
        $output = new $class($this->envId);

        $output->setUmask(0);

        return $output;
    }

    /**
     * Initialize platform
     */
    public function initializePlatform(): void
    {
        // Setup Glitch
        Glitch::setStartTime($this->context->getStartTime())
            ->setRunMode($this->context->environment->getMode())
            ->registerPathAliases([
                'app' => $this->appPath,
                'vendor' => $this->appPath . '/vendor'
            ])
            ->registerAsErrorHandler();


        // Namespaces
        $appNamespace = $this->app->getNamespace();

        foreach (static::ARCHETYPES as $interface => $classExt) {
            Archetype::extend(
                $interface,
                Fabric::class . '\\' . $classExt // @phpstan-ignore-line
            );

            if ($appNamespace !== null) {
                Archetype::extend($interface, $appNamespace . '\\' . $classExt);
            }
        }

        Greenleaf::$namespaces->add($appNamespace . '\\Http');


        // Clip
        $this->context->container->bindShared(
            ClipController::class
        );

        Veneer::register(
            ClipController::class,
            ClipNamespace::class // @phpstan-ignore-line
        );


        // App
        $this->app->initializePlatform();
    }

    /**
     * Load custom R7 kernel
     */
    public function loadKernel(): Kernel
    {
        $kernel = $this->detectKernel();

        if ($kernel === 'Cli') {
            $kernel = ['Cli', ClipKernel::class];
        }

        $class = Archetype::resolve(Kernel::class, $kernel);
        return new $class($this->context);
    }

    protected function detectKernel(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return 'Http';
        } elseif (isset($_SERVER['argv'])) {
            return 'Cli';
        }

        switch (\PHP_SAPI) {
            case 'cli':
            case 'phpdbg':
                return 'Cli';

            case 'apache':
            case 'apache2filter':
            case 'apache2handler':
            case 'fpm-fcgi':
            case 'cgi-fcgi':
            case 'phttpd':
            case 'pi3web':
            case 'thttpd':
                return 'Http';
        }

        throw Exceptional::UnexpectedValue(
            'Unable to detect run mode (' . \PHP_SAPI . ')'
        );
    }

    /**
     * Get Build Manifest
     */
    public function getBuildManifest(): ?BuildManifest
    {
        return new BuildManifest(Cli::getSession());
    }
}
