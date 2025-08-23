<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis;

use DecodeLabs\Archetype;
use DecodeLabs\Coercion;
use DecodeLabs\Commandment\Action as CommandmentAction;
use DecodeLabs\Dovetail;
use DecodeLabs\Fabric;
use DecodeLabs\Fabric\Dovetail\Config\Environment as EnvironmentConfig;
use DecodeLabs\Fabric\Genesis\Build\Manifest as BuildManifest;
use DecodeLabs\Genesis;
use DecodeLabs\Genesis\AnalysisMode;
use DecodeLabs\Genesis\Build;
use DecodeLabs\Genesis\Build\Manifest as BuildManifestInterface;
use DecodeLabs\Genesis\Build\Strategy;
use DecodeLabs\Genesis\Build\Strategy\Seamless;
use DecodeLabs\Genesis\Environment\Config as EnvConfig;
use DecodeLabs\Genesis\Hub as HubInterface;
use DecodeLabs\Glitch;
use DecodeLabs\Greenleaf;
use DecodeLabs\Harvest;
use DecodeLabs\Kingdom;
use DecodeLabs\Monarch;
use DecodeLabs\Pandora\Container;
use DecodeLabs\Veneer;
use ReflectionClass;

class Hub implements HubInterface
{
    /**
     * @var array<string,string>
     */
    protected const array ArchetypeAliases = [
        CommandmentAction::class => 'Cli',
        Greenleaf::class . '\\*' => 'Http'
    ];

    protected ?string $envId = null;

    public ?BuildManifestInterface $buildManifest {
        get => new BuildManifest(
            $this->buildStrategy,
            $this->archetype
        );
    }

    protected Strategy $buildStrategy {
        get => new Seamless();
    }

    protected ?string $appNamespace = null;
    protected Container $container;
    protected Archetype $archetype;

    public function __construct(
        protected Genesis $genesis,
        protected ?AnalysisMode $analysisMode = null
    ) {
        $this->container = new Container();

        if (class_exists(Veneer::class)) {
            Veneer::setContainer($this->container);
        }

        $this->archetype = $this->container->get(Archetype::class);
    }

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
        } elseif ($this->analysisMode === AnalysisMode::Library) {
            $buildPath = (string)getcwd();
        } else {
            $buildPath = Monarch::getPaths()->root;
        }

        // Create build info
        return new Build(
            $this->genesis,
            $buildPath,
            Coercion::tryInt(Fabric\BUILD_TIMESTAMP)
        );
    }

    public function initializeLoaders(): void
    {
        // Archetype
        $this->archetype->map(
            root: 'DecodeLabs',
            // @phpstan-ignore-next-line
            namespace: Fabric::class,
            priority: 1
        );

        // Config
        if ($this->analysisMode !== AnalysisMode::Library) {
            $config = $this->container->get(EnvironmentConfig::class);
            $paths = Monarch::getPaths();

            $paths->localData = $paths->root . '/' . ltrim($config->getLocalDataPath(), '/');
            $paths->sharedData = $paths->root . '/' . ltrim($config->getSharedDataPath(), '/');

            // App
            $this->appNamespace = $config->getAppNamespace();

            if ($this->appNamespace !== null) {
                $this->archetype->map(
                    root: 'DecodeLabs',
                    namespace: $this->appNamespace,
                    priority: 10
                );

                $this->archetype->alias(
                    // @phpstan-ignore-next-line
                    interface: Fabric::class,
                    alias: $this->appNamespace,
                    priority: 11
                );
            }
        }

        // Namespaces
        foreach (static::ArchetypeAliases as $interface => $classExt) {
            $this->archetype->alias(
                interface: $interface,
                // @phpstan-ignore-next-line
                alias: Fabric::class . '\\' . $classExt
            );
        }
    }

    public function loadEnvironmentConfig(): EnvConfig
    {
        if ($this->analysisMode) {
            return new EnvConfig\Development('analysis');
        }

        $ref = new ReflectionClass(EnvironmentConfig::class);

        $config = $ref->newLazyProxy(function () {
            $dovetail = $this->container->get(Dovetail::class);
            return $dovetail->load(EnvironmentConfig::class);
        });

        /** @phpstan-ignore-next-line */
        $name = ucfirst(Fabric\BUILD_ENV_MODE ?? $config->getMode());

        /** @var class-string<EnvConfig\Development|EnvConfig\Testing|EnvConfig\Production> */
        $class = EnvConfig::class . '\\' . $name;

        $output = new $class(
            $this->envId ?? $config->getName()
        );

        $output->umask = 0;
        return $output;
    }

    public function initializePlatform(): void
    {
        // Glitch
        $glitch = $this->container->get(Glitch::class);
        $glitch->setStartTime(Monarch::getStartTime());
        $glitch->registerAsErrorHandler();

        $glitch->setHeaderBufferSender(function () {
            $harvest = $this->container->get(Harvest::class);

            // Send cookies when dumping
            foreach ($harvest->cookies->toStringArray() as $cookie) {
                header('Set-Cookie: ' . $cookie, false);
            }
        });
    }

    public function loadKingdom(): Kingdom
    {
        $class = $this->archetype->resolve(Kingdom::class);
        return new $class($this->container);
    }
}
