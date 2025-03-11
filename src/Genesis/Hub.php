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
use DecodeLabs\Dovetail\Finder\Generic as DovetailFinder;
use DecodeLabs\Exceptional;
use DecodeLabs\Fabric;
use DecodeLabs\Fabric\App;
use DecodeLabs\Fabric\Dovetail\Config\Environment as EnvironmentConfig;
use DecodeLabs\Fluidity\CastTrait;
use DecodeLabs\Genesis\Build;
use DecodeLabs\Genesis\Build\Manifest as BuildManifestInterface;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Environment\Config as EnvConfig;
use DecodeLabs\Genesis\Hub as HubInterface;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Glitch;
use DecodeLabs\Greenleaf;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer;

class Hub implements HubInterface
{
    use CastTrait;

    /**
     * @var array<string,string>
     */
    protected const array ArchetypeAliases = [
        ClipTask::class => 'Cli',
        Greenleaf::class . '\\*' => 'Http'
    ];

    protected ?string $envId = null;

    public string $applicationName {
        get => $this->applicationName ??= EnvironmentConfig::load()->getAppName();
    }

    protected(set) string $applicationPath;

    public string $localDataPath {
        get => $this->localDataPath ??= $this->applicationPath . '/' . ltrim(
            EnvironmentConfig::load()->getLocalDataPath(),
            '/'
        );
    }

    public string $sharedDataPath {
        get => $this->sharedDataPath ??= $this->applicationPath . '/' . ltrim(
            EnvironmentConfig::load()->getSharedDataPath(),
            '/'
        );
    }

    public ?BuildManifestInterface $buildManifest {
        get => new BuildManifest(Cli::getSession());
    }

    protected ?AnalysisMode $analysisMode = null;
    protected App $app;
    protected Context $context;

    public function __construct(
        Context $context,
        array $options
    ) {
        $this->context = $context;

        if ($options['analysis'] ?? false) {
            $this->prepareForAnalysis($options);
            return;
        }

        $this->prepareForRun($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function prepareForAnalysis(
        array $options
    ): void {
        if (!$appDir = getcwd()) {
            throw Exceptional::Runtime(
                message: 'Unable to get current working directory'
            );
        }

        $hasAppFile = file_exists($appDir . '/src/App.php');

        if ($hasAppFile) {
            $this->analysisMode = AnalysisMode::App;
        } else {
            $this->analysisMode = AnalysisMode::Self;
            $appDir = dirname(dirname(__DIR__)) . '/tests';
        }

        $this->applicationPath = $appDir;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function prepareForRun(
        array $options
    ): void {
        $this->applicationPath = rtrim(Coercion::asString($options['applicationPath']), '/');
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
            $buildPath = $this->applicationPath;
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
        // Dovetail
        if ($this->context->build->compiled) {
            Dovetail::setEnvPath($this->context->build->path);

            Dovetail::setFinder(new DovetailFinder(
                $this->context->build->path
            ));
        } else {
            Dovetail::setEnvPath($this->applicationPath);
        }


        // Archetype
        Archetype::map(
            'DecodeLabs',
            Fabric::class,
            1
        );


        // App
        $namespace = EnvironmentConfig::load()->getAppNamespace();

        if ($namespace !== null) {
            Archetype::map('DecodeLabs', $namespace, 10);
            Archetype::alias(Fabric::class, $namespace, 11);
            Archetype::alias(App::class, $namespace);
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
            ->setRunMode($this->context->environment->mode->value)
            ->registerPathAliases([
                'app' => $this->applicationPath,
                'vendor' => $this->applicationPath . '/vendor'
            ])
            ->registerAsErrorHandler();


        // Namespaces
        foreach (static::ArchetypeAliases as $interface => $classExt) {
            Archetype::alias(
                $interface,
                Fabric::class . '\\' . $classExt
            );
        }

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
