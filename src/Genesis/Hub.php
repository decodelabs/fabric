<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis;

use DecodeLabs\Archetype;
use DecodeLabs\Clip\Controller as ClipController;
use DecodeLabs\Clip\Controller\Commandment as CommandmentController;
use DecodeLabs\Clip\Kernel as ClipKernel;
use DecodeLabs\Coercion;
use DecodeLabs\Commandment\Action as CommandmentAction;
use DecodeLabs\Exceptional;
use DecodeLabs\Fabric;
use DecodeLabs\Fabric\App;
use DecodeLabs\Fabric\Dovetail\Config\Environment as EnvironmentConfig;
use DecodeLabs\Fabric\Genesis\Build\Manifest as BuildManifest;
use DecodeLabs\Fluidity\CastTrait;
use DecodeLabs\Genesis\Bootstrap;
use DecodeLabs\Genesis\Bootstrap\Analysis as AnalysisBootstrap;
use DecodeLabs\Genesis\Build;
use DecodeLabs\Genesis\Build\Manifest as BuildManifestInterface;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Environment\Config as EnvConfig;
use DecodeLabs\Genesis\Hub as HubInterface;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Glitch;
use DecodeLabs\Greenleaf;
use DecodeLabs\Harvest;
use DecodeLabs\Monarch;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer;

class Hub implements HubInterface
{
    use CastTrait;

    /**
     * @var array<string,string>
     */
    protected const array ArchetypeAliases = [
        CommandmentAction::class => 'Cli',
        Greenleaf::class . '\\*' => 'Http'
    ];

    protected ?string $envId = null;

    public ?BuildManifestInterface $buildManifest {
        get => new BuildManifest();
    }

    protected ?AnalysisMode $analysisMode = null;
    protected App $app;
    protected Context $context;

    public function __construct(
        Context $context,
        Bootstrap $bootstrap
    ) {
        $this->context = $context;

        if ($bootstrap instanceof AnalysisBootstrap) {
            $this->prepareForAnalysis($bootstrap);
        }
    }

    protected function prepareForAnalysis(
        AnalysisBootstrap $bootstrap
    ): void {
        $appDir = $bootstrap->rootPath;
        $hasAppFile =
            file_exists($appDir . '/src/App.php') &&
            !file_exists($appDir . '/src/Context.php');

        if ($hasAppFile) {
            $this->analysisMode = AnalysisMode::App;
        } else {
            $this->analysisMode = AnalysisMode::Self;
            $appDir = dirname(dirname(__DIR__)) . '/tests';
        }

        Monarch::$paths->root = $appDir;
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
            // @phpstan-ignore-next-line
            Fabric\BUILD_ROOT_PATH !== null &&
            is_dir($path = Coercion::asString(Fabric\BUILD_ROOT_PATH))
        ) {
            $buildPath = $path;
        } elseif ($this->analysisMode === AnalysisMode::Self) {
            $buildPath = (string)getcwd();
        } else {
            $buildPath = Monarch::$paths->root;
        }

        // Create build info
        return new Build(
            $this->context,
            $buildPath,
            Coercion::tryInt(Fabric\BUILD_TIMESTAMP)
        );
    }

    /**
     * Setup loaders
     */
    public function initializeLoaders(
        StackLoader $stack
    ): void {
        // Archetype
        Archetype::map(
            'DecodeLabs',
            Fabric::class,
            1
        );

        // Config
        if($this->analysisMode !== AnalysisMode::Self) {
            $config = EnvironmentConfig::load();
            Monarch::setApplicationName($config->getAppName());
            Monarch::$paths->localData = Monarch::$paths->root . '/' . ltrim($config->getLocalDataPath(), '/');
            Monarch::$paths->sharedData = Monarch::$paths->root . '/' . ltrim($config->getSharedDataPath(), '/');

            // App
            $namespace = $config->getAppNamespace();

            if ($namespace !== null) {
                Archetype::map('DecodeLabs', $namespace, 10);
                Archetype::alias(Fabric::class, $namespace, 11);
                Archetype::alias(App::class, $namespace);
            }
        } else {
            $namespace = null;
        }

        $this->app = Fabric::$container->getWith(App::class, [
            'namespace' => $namespace
        ]);

        $this->app->initializeLoaders($stack);
    }

    /**
     * Load env config
     */
    public function loadEnvironmentConfig(): EnvConfig
    {
        if ($this->analysisMode) {
            return new EnvConfig\Development('analysis');
        }

        /** @phpstan-ignore-next-line */
        $name = ucfirst(Fabric\BUILD_ENV_MODE ?? EnvironmentConfig::load()->getMode());

        /** @var class-string<EnvConfig\Development|EnvConfig\Testing|EnvConfig\Production> */
        $class = EnvConfig::class . '\\' . $name;

        $output = new $class(
            $this->envId ?? EnvironmentConfig::load()->getName()
        );

        $output->umask = 0;
        return $output;
    }

    /**
     * Initialize platform
     */
    public function initializePlatform(): void
    {
        // Setup Glitch
        Glitch::setStartTime($this->context->getStartTime())
            ->setRunMode(Monarch::getEnvironmentMode()->value)
            ->registerPathAliases([
                'app' => Monarch::$paths->root,
                'vendor' => Monarch::$paths->root . '/vendor'
            ])
            ->registerAsErrorHandler()
            ->setHeaderBufferSender(function () {
                // Send cookies when dumping
                foreach(Harvest::$cookies->toStringArray() as $cookie) {
                    header('Set-Cookie: ' . $cookie, false);
                }
            });


        // Namespaces
        foreach (static::ArchetypeAliases as $interface => $classExt) {
            Archetype::alias(
                $interface,
                Fabric::class . '\\' . $classExt
            );
        }

        // App
        $this->app->initializePlatform();


        // Controller
        if (!Fabric::$container->has(ClipController::class)) {
            Fabric::$container->bindShared(
                ClipController::class,
                CommandmentController::class
            );
        }
    }

    /**
     * Load kernel
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
            message: 'Unable to detect run mode (' . \PHP_SAPI . ')'
        );
    }
}
