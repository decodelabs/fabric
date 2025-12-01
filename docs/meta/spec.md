# Fabric — Package Specification

> **Cluster:** `runtime`
> **Language:** `php`
> **Milestone:** `m3`
> **Repo:** `https://github.com/decodelabs/fabric`
> **Role:** Framework entry point

This document describes the purpose, contracts, and design of **Fabric** within the Decode Labs ecosystem.

It is aimed at:

- Developers **using** Fabric to build web applications.
- Contributors **maintaining or extending** Fabric.
- Tools and AI assistants that need to reason about its behaviour.

---

## 1. Overview

### 1.1 Purpose

Fabric provides the structures, prerequisites, and conventions for building a web application using the Decode Labs ecosystem. It serves as a lightweight framework entry point that integrates Genesis bootstrapping, Kingdom service container, Harvest HTTP kernel, Clip CLI kernel, Greenleaf routing, Dovetail configuration, and other Decode Labs packages into a cohesive application framework. Fabric establishes conventions for application structure, namespace mapping, configuration management, and runtime initialization, allowing developers to focus on application logic rather than framework integration.

### 1.2 Non-Goals

Fabric does **not**:

- Provide a full-stack framework with built-in ORM, templating, or authentication
- Implement application-specific business logic or domain models
- Provide database abstraction or query builders
- Implement user authentication or authorization systems
- Provide built-in session management or caching strategies
- Implement form handling or validation beyond what Lucid provides
- Provide built-in asset management or bundling
- Implement application-specific routing or middleware beyond what Harvest/Greenleaf provide
- Provide application scaffolding or code generation tools
- Implement deployment or DevOps tooling

Fabric focuses on providing the foundational structure and integration points for building applications with the Decode Labs ecosystem, not on implementing application-specific features.

---

## 2. Role in the Ecosystem

### 2.1 Cluster & Positioning

- **Cluster:** `runtime` (see Chorus taxonomy)
- Fabric is positioned as a runtime framework that orchestrates multiple Decode Labs packages into a cohesive application structure. It sits above individual packages like Genesis, Kingdom, Harvest, Clip, and Greenleaf, providing integration and conventions. It is used by applications built on the Decode Labs ecosystem to establish a standard structure and initialization pattern.

### 2.2 Typical Usage Contexts

Typical places Fabric appears:

- Web applications built on the Decode Labs ecosystem
- CLI applications that need HTTP capabilities
- Applications requiring both HTTP and CLI runtimes
- Applications that need structured configuration management
- Applications requiring service container and dependency injection
- Applications using Greenleaf for routing
- Applications using Harvest for HTTP request handling
- Applications using Clip for CLI command dispatching
- Applications requiring Genesis bootstrapping and build system
- Applications needing error handling and logging via Glitch

Fabric is intended to be used as the foundation for building complete applications using the Decode Labs ecosystem, providing a standard structure and integration pattern.

---

## 3. Public Surface

> This section focuses on the conceptual API, not every symbol.

### 3.1 Key Types

The primary public types are:

- `DecodeLabs\Fabric\Genesis\Hub`
  Genesis Hub implementation that provides Fabric-specific bootstrapping. Handles build manifest creation, loader initialization, environment configuration, platform initialization, and Kingdom loading. Integrates with Genesis build system and provides Archetype namespace mapping for application code.

- `DecodeLabs\Fabric\Kingdom`
  Kingdom implementation that provides Fabric-specific service container setup. Handles HTTP and CLI runtime detection, Harvest profile configuration with Greenleaf middleware, and runtime initialization. Extends `KingdomInterface` and uses `KingdomTrait`.

- `DecodeLabs\Fabric\Dovetail\Config\Environment`
  Dovetail configuration class for environment settings. Provides methods for accessing mode, name, app namespace, and data paths. Uses Dovetail's config system with environment variable resolution.

- `DecodeLabs\Fabric\Genesis\Build\Manifest`
  Genesis build manifest implementation that provides Fabric-specific build entry file generation. Writes entry files with build constants and Genesis initialization.

### 3.2 Main Entry Points

The main usage pattern is through Genesis bootstrapping:

```php
// In composer.json
{
    "extra": {
        "genesis": {
            "hub": "DecodeLabs\\Fabric\\Genesis\\Hub"
        }
    }
}

// HTTP server rewrites to vendor/genesis.php
// Genesis takes care of the rest
```

Applications extend `Kingdom` for custom initialization:

```php
namespace MyApp;

use DecodeLabs\Fabric\Kingdom as FabricKingdom;

class Kingdom extends FabricKingdom
{
    public protected(set) string $name = 'My Application';

    public function initialize(): void
    {
        parent::initialize();
        // Custom initialization
    }
}
```

---

## 4. Dependencies

### 4.1 Decode Labs

- `decodelabs/archetype` (required)
  Used for class resolution and namespace mapping. Fabric sets up Archetype mappings for application namespaces and HTTP/CLI action resolution.

- `decodelabs/clip` (required)
  Used for CLI command dispatching. Fabric integrates Clip as the CLI runtime via Kingdom.

- `decodelabs/coercion` (required)
  Used for type coercion in configuration and build processes.

- `decodelabs/commandment` (required)
  Used by Clip for CLI command interface and dispatching.

- `decodelabs/dovetail` (required)
  Used for configuration management. Fabric uses Dovetail to load environment configuration and other config files.

- `decodelabs/exceptional` (required)
  Used for exception handling throughout the framework.

- `decodelabs/genesis` (required)
  Used for application bootstrapping and build system. Fabric provides a Genesis Hub implementation.

- `decodelabs/glitch` (required)
  Used for error handling and reporting. Fabric registers Glitch as the error handler.

- `decodelabs/greenleaf` (required)
  Used for HTTP routing. Fabric integrates Greenleaf as Harvest middleware.

- `decodelabs/harvest` (required)
  Used for HTTP request handling. Fabric uses Harvest as the HTTP runtime via Kingdom.

- `decodelabs/kingdom` (required)
  Used for service container and application management. Fabric provides a Kingdom implementation.

- `decodelabs/lucid` (required)
  Used for validation and data processing.

- `decodelabs/monarch` (required)
  Used for service location and path management.

- `decodelabs/pandora` (required)
  Used for dependency injection container. Fabric uses Pandora's Container for service management.

- `decodelabs/systemic` (required)
  Used for system operations and environment detection.

### 4.2 External

- No external dependencies beyond PHP core extensions.

### 4.3 Optional Integrations

- `decodelabs/veneer` — Detected at runtime if installed, used for setting the global container via `Veneer::setContainer()`.
- `decodelabs/stash` — Used in development for caching (conflict constraint ensures version compatibility).

---

## 5. Behaviour & Contracts

### 5.1 Invariants

- Fabric applications must use Genesis for bootstrapping
- Fabric applications must have a `DecodeLabs\Fabric\Dovetail\Config\Environment` configuration file
- Fabric applications must define an app namespace in the Environment config
- Fabric applications must have a Kingdom implementation (defaults to `DecodeLabs\Fabric\Kingdom`)
- HTTP runtime uses Harvest with Greenleaf middleware by default
- CLI runtime uses Clip by default
- Runtime mode is automatically detected (HTTP vs CLI)
- Archetype mappings are set up automatically for app namespace and HTTP/CLI actions
- Glitch is registered as the error handler during platform initialization
- Build constants are defined during build process (BUILD_TIMESTAMP, BUILD_ID, BUILD_ROOT_PATH, BUILD_ENV_MODE)
- Data paths (local and shared) are configured from Environment config
- Harvest profile includes optional ContentSecurityPolicy and Zest middleware, plus Greenleaf routing

### 5.2 Input & Output Contracts

**Hub Methods:**
- `loadBuild(): Build` — Creates Genesis Build instance with root path and timestamp. Returns Build instance.
- `initializeLoaders(): void` — Sets up Archetype namespace mappings for DecodeLabs, app namespace, and HTTP/CLI action aliases. Configures data paths from Environment config.
- `loadEnvironmentConfig(): EnvConfig` — Loads environment configuration from Dovetail. Returns EnvConfig instance (Development, Testing, or Production).
- `initializePlatform(): void` — Initializes Glitch error handler, sets start time, registers header buffer sender for cookies. No return value.
- `loadKingdom(): Kingdom` — Resolves and instantiates Kingdom implementation via Archetype. Returns Kingdom instance.

**Kingdom Methods:**
- `initialize(): void` — Sets up Harvest profile with middleware and configures runtime (HTTP or CLI). No return value.
- `detectRuntimeMode(): RuntimeMode` — Detects whether application is running in HTTP or CLI mode. Returns RuntimeMode enum value.

**Environment Config Methods:**
- `getMode(): string` — Gets environment mode (production, development, testing). Returns string.
- `getName(): ?string` — Gets environment name. Returns string or null.
- `getAppNamespace(): ?string` — Gets application namespace for code organization. Returns namespace string or null.
- `getLocalDataPath(): string` — Gets local data directory path. Returns string (default: 'data/local').
- `getSharedDataPath(): string` — Gets shared data directory path. Returns string (default: 'data/shared').

**Build Manifest Methods:**
- `writeEntryFile(File $file, string $buildId, string $hubClass): void` — Writes Genesis entry file with build constants. No return value.

**Archetype Aliases:**
- `CommandmentAction::class` → `Fabric\Cli` — Maps CLI actions to app's Cli namespace
- `Greenleaf::class . '\\*'` → `Fabric\Http` — Maps HTTP actions to app's Http namespace

---

## 6. Error Handling

- Configuration loading errors throw `Exceptional` exceptions
- Missing Environment config throws appropriate exceptions
- Invalid app namespace configuration is handled gracefully (returns null)
- Build path resolution failures are handled with fallbacks
- Kingdom resolution failures throw exceptions
- Runtime mode detection failures are handled by defaulting to appropriate mode
- Glitch registration errors are handled by the error handler itself
- Missing services in container throw container exceptions
- Invalid Archetype mappings throw Archetype exceptions

---

## 7. Configuration & Extensibility

- Configuration is managed via Dovetail
- Environment config file (`Environment.php`) must be in `/config` directory (customizable via Dovetail Finder)
- Environment variables are loaded from `.env` file in app root
- App namespace is configured in Environment config via `appNamespace` key
- Data paths are configured in Environment config via `localDataPath` and `sharedDataPath` keys
- Applications can extend `Kingdom` to customize initialization
- Applications can extend `Hub` to customize bootstrapping (though this is less common)
- Harvest profile can be customized by overriding Kingdom's `initialize()` method
- Archetype mappings can be extended by applications
- Build manifest can be customized by extending `Manifest`
- Runtime mode detection can be customized by overriding Kingdom's `detectRuntimeMode()` method

---

## 8. Interactions with Other Packages

### 8.1 Genesis

Fabric uses Genesis for:
- Application bootstrapping via Hub implementation
- Build system integration via Build Manifest
- Entry point generation
- Build constant management

### 8.2 Kingdom

Fabric uses Kingdom for:
- Service container and application management
- Runtime mode detection and initialization
- Application lifecycle management

### 8.3 Harvest

Fabric uses Harvest for:
- HTTP request handling
- HTTP runtime implementation
- Middleware pipeline management
- Response generation

### 8.4 Clip

Fabric uses Clip for:
- CLI command dispatching
- CLI runtime implementation
- Command execution and error handling

### 8.5 Greenleaf

Fabric uses Greenleaf for:
- HTTP routing
- Route matching and dispatching
- Action resolution via Archetype

### 8.6 Dovetail

Fabric uses Dovetail for:
- Configuration management
- Environment variable loading
- Config file loading and resolution

### 8.7 Glitch

Fabric uses Glitch for:
- Error handling and reporting
- Exception logging
- Error display and debugging

### 8.8 Archetype

Fabric uses Archetype for:
- Class resolution
- Namespace mapping for application code
- HTTP and CLI action resolution

### 8.9 Pandora

Fabric uses Pandora for:
- Dependency injection container
- Service resolution and management

### 8.10 Monarch

Fabric uses Monarch for:
- Service location
- Path management
- Application metadata access

### 8.11 Other Packages

Fabric may integrate with:
- `decodelabs/veneer` — Global container access
- `decodelabs/stash` — Caching (development dependency)
- `decodelabs/lucid` — Validation and data processing
- `decodelabs/coercion` — Type coercion
- `decodelabs/systemic` — System operations

---

## 9. Usage Examples

### 9.1 Basic Application Setup

```php
// composer.json
{
    "name": "myapp/application",
    "require": {
        "decodelabs/fabric": "^0.10"
    },
    "extra": {
        "genesis": {
            "hub": "DecodeLabs\\Fabric\\Genesis\\Hub"
        }
    }
}

// config/Environment.php (generated by Dovetail)
return [
    'mode' => "{{Env::asString('ENV_MODE', 'production')}}",
    'name' => "{{Env::asString('ENV_NAME', 'production')}}",
    'appNamespace' => 'MyApp',
    'localDataPath' => 'data/local',
    'sharedDataPath' => 'data/shared'
];

// HTTP server rewrites to vendor/genesis.php
```

### 9.2 Custom Kingdom Implementation

```php
namespace MyApp;

use DecodeLabs\Fabric\Kingdom as FabricKingdom;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Harvest\Middleware\Greenleaf;
use MyApp\Http\Middleware\CustomMiddleware;

class Kingdom extends FabricKingdom
{
    public protected(set) string $name = 'My Application';

    public function initialize(): void
    {
        parent::initialize();

        // Custom Harvest profile
        $this->container->setFactory(
            Profile::class,
            fn () => Profile::loadDefault()
                ->add('?ContentSecurityPolicy')
                ->add('?Zest')
                ->add(Greenleaf::class)
                ->add(CustomMiddleware::class)
        );
    }
}
```

### 9.3 HTTP Action

```php
namespace MyApp\Http;

use DecodeLabs\Greenleaf\Route\Action;
use DecodeLabs\Greenleaf\Route\Parameter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Home implements Action
{
    #[Parameter\Value(name: 'id', required: false)]
    public function handle(
        ServerRequestInterface $request,
        array $parameters
    ): ResponseInterface {
        $id = $parameters['id'] ?? null;
        // Handle request
        return $response;
    }
}
```

### 9.4 CLI Action

```php
namespace MyApp\Cli;

use DecodeLabs\Commandment\Action;
use DecodeLabs\Commandment\Request;
use DecodeLabs\Commandment\Argument\Value;

class Migrate implements Action
{
    #[Value(name: 'version', required: false)]
    public function execute(Request $request): bool
    {
        $version = $request->parameters->tryString('version');
        // Run migration
        return true;
    }
}
```

### 9.5 Environment Configuration

```php
// .env file
ENV_MODE=development
ENV_NAME=local

// config/Environment.php
return [
    'mode' => "{{Env::asString('ENV_MODE', 'production')}}",
    'name' => "{{Env::asString('ENV_NAME', 'production')}}",
    'appNamespace' => 'MyApp',
    'localDataPath' => 'data/local',
    'sharedDataPath' => 'data/shared'
];
```

### 9.6 Custom Hub (Advanced)

```php
namespace MyApp;

use DecodeLabs\Fabric\Genesis\Hub as FabricHub;
use DecodeLabs\Genesis;
use DecodeLabs\Genesis\AnalysisMode;

class Hub extends FabricHub
{
    public function initializeLoaders(): void
    {
        parent::initializeLoaders();
        
        // Additional Archetype mappings
        $this->archetype->map(
            root: 'MyApp',
            namespace: 'MyApp\\Custom',
            priority: 5
        );
    }
}
```

### 9.7 Runtime Mode Detection

```php
namespace MyApp;

use DecodeLabs\Fabric\Kingdom as FabricKingdom;
use DecodeLabs\Kingdom\RuntimeMode;

class Kingdom extends FabricKingdom
{
    protected function detectRuntimeMode(): RuntimeMode
    {
        // Custom detection logic
        if (isset($_SERVER['HTTP_HOST'])) {
            return RuntimeMode::Http;
        }
        
        return RuntimeMode::Cli;
    }
}
```

---

## 10. Implementation Notes (for Contributors)

### 10.1 Internal Architecture

At a high level, Fabric:
- Provides a Genesis Hub implementation that orchestrates bootstrapping
- Sets up Archetype namespace mappings for application code organization
- Configures Dovetail for environment and application configuration
- Initializes Glitch for error handling
- Provides a Kingdom implementation for service container setup
- Configures Harvest for HTTP request handling with Greenleaf routing
- Configures Clip for CLI command dispatching
- Detects runtime mode (HTTP vs CLI) automatically
- Manages build constants and entry point generation
- Integrates multiple Decode Labs packages into a cohesive framework

### 10.2 Bootstrapping Flow

1. Genesis loads Hub
2. Hub loads build information
3. Hub initializes loaders (Archetype mappings)
4. Hub loads environment configuration
5. Hub initializes platform (Glitch)
6. Hub loads Kingdom
7. Kingdom initializes (Harvest profile, runtime detection)
8. Runtime handles requests/commands

### 10.3 Namespace Mapping

Fabric sets up Archetype mappings:
- `DecodeLabs` → `DecodeLabs\Fabric` (priority 1)
- App namespace → Application namespace (priority 10)
- `Fabric` interface → App namespace (priority 11)
- `CommandmentAction` → `Fabric\Cli` (for CLI actions)
- `Greenleaf\*` → `Fabric\Http` (for HTTP actions)

### 10.4 Runtime Detection

Kingdom detects runtime mode by checking for HTTP context (e.g., `$_SERVER['HTTP_HOST']`). If HTTP context is present, it uses `HttpRuntime` (Harvest). Otherwise, it uses `CliRuntime` (Clip).

### 10.5 Build System Integration

Fabric's Build Manifest generates entry files with build constants:
- `BUILD_TIMESTAMP` — Build timestamp
- `BUILD_ID` — Build identifier
- `BUILD_ROOT_PATH` — Application root path
- `BUILD_ENV_MODE` — Environment mode

### 10.6 Configuration Management

Fabric uses Dovetail for configuration:
- Environment config is loaded from `config/Environment.php`
- Environment variables are loaded from `.env` file
- Config values can reference environment variables via Dovetail's template system
- App namespace is resolved from config and used for Archetype mappings

### 10.7 Error Handling Integration

Fabric registers Glitch as the error handler during platform initialization:
- Sets start time for performance tracking
- Registers as PHP error handler
- Sets up header buffer sender for cookie handling during error dumps
- Integrates with Harvest for HTTP error responses

### 10.8 Service Container Setup

Fabric uses Pandora's Container for dependency injection:
- Container is created in Hub constructor
- Services are resolved via Archetype when needed
- Container is set on Veneer if available for global access
- Kingdom uses container for service resolution

### 10.9 Performance Considerations

- Lazy loading of configuration via Dovetail proxies
- Archetype mappings are set up once during initialization
- Runtime mode is detected once per request/command
- Build constants are defined at compile time
- Service container uses lazy resolution

### 10.10 Gotchas & Historical Decisions

- App namespace must be a valid PHP namespace (not just a string)
- Environment config uses Dovetail's template system for environment variable resolution
- Data paths are relative to application root
- Build path resolution has fallbacks for library analysis mode
- Analysis mode (Library) skips application-specific initialization
- HTTP actions are resolved via Greenleaf's Archetype integration
- CLI actions are resolved via Commandment's Archetype integration
- Harvest profile includes optional middleware (ContentSecurityPolicy, Zest) that may not be installed
- Kingdom name defaults to 'Fabric application' but should be overridden by applications
- Runtime detection is based on HTTP context presence, not SAPI type

---

## 11. Testing & Quality

- **Code Quality Score:** 4/5
- **README Quality Score:** 3/5
- **Documentation Score:** 0/5 (this spec)
- **Test Coverage Score:** 0/5

See `composer.json` for supported PHP versions.

---

## 12. Roadmap & Future Ideas

- Enhanced documentation and examples
- Application scaffolding tools
- Enhanced build system integration
- Better development tooling
- Enhanced error handling and debugging
- Performance optimizations
- Enhanced configuration management
- Better integration with other Decode Labs packages
- Enhanced CLI tooling
- Better HTTP middleware support
- Enhanced routing capabilities
- Better service container integration
- Enhanced testing support
- Better deployment tooling

---

## 13. References

- [Genesis Package](https://github.com/decodelabs/genesis) — Application bootstrapping
- [Kingdom Package](https://github.com/decodelabs/kingdom) — Service container
- [Harvest Package](https://github.com/decodelabs/harvest) — HTTP kernel
- [Clip Package](https://github.com/decodelabs/clip) — CLI kernel
- [Greenleaf Package](https://github.com/decodelabs/greenleaf) — HTTP routing
- [Dovetail Package](https://github.com/decodelabs/dovetail) — Configuration management
- [Glitch Package](https://github.com/decodelabs/glitch) — Error handling
- [Archetype Package](https://github.com/decodelabs/archetype) — Class resolution
- [Pandora Package](https://github.com/decodelabs/pandora) — Dependency injection
- [Monarch Package](https://github.com/decodelabs/monarch) — Service location
- [Commandment Package](https://github.com/decodelabs/commandment) — CLI dispatcher
- [Chorus Package Index](../../../chorus/config/packages.json) — Ecosystem metadata

