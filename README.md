# Fabric

[![PHP from Packagist](https://img.shields.io/packagist/php-v/decodelabs/fabric?style=flat)](https://packagist.org/packages/decodelabs/fabric)
[![Latest Version](https://img.shields.io/packagist/v/decodelabs/fabric.svg?style=flat)](https://packagist.org/packages/decodelabs/fabric)
[![Total Downloads](https://img.shields.io/packagist/dt/decodelabs/fabric.svg?style=flat)](https://packagist.org/packages/decodelabs/fabric)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/decodelabs/fabric/integrate.yml?branch=develop)](https://github.com/decodelabs/fabric/actions/workflows/integrate.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-44CC11.svg?longCache=true&style=flat)](https://github.com/phpstan/phpstan)
[![License](https://img.shields.io/packagist/l/decodelabs/fabric?style=flat)](https://packagist.org/packages/decodelabs/fabric)

### Lightweight PHP framework implementation using DecodeLabs ecosystem

Fabric provides the structures, prerequisites and conventions for building a web application using the DecodeLabs ecosystem.

---

## Installation

Install via Composer:

```bash
composer require decodelabs/fabric
```

## Usage

A Fabric app looks very similar to a standard package with most code residing in the src folder, under a namespace of your choice and loaded via composer.

It does not require a custom entry point as it will automatically detect and load the app when the framework is initialised via the built in Bootstrap:

```nginx
# Example nginx config
server {
    listen          443 ssl;
    server_name     my-app.localtest.me;
    root            /var/www/my-app/;

    # Rewrite to fabric Bootstrap
    rewrite         .* /vendor/decodelabs/fabric/src/Bootstrap.php last;

    include         snippets/php81.conf;
    include         snippets/ssl.conf;
}
```

### Config

Fabric utilises <code>Dovetail</code> for config loading - via a private .env file in the app root and data files in /config (though this can be customised if necessary).

The most important config file is the <code>Environment.php</code> file which defines some key values for the rest of the app to initialize with.

The _appNamespace_ value will allow you to define the namespace in which the majority of your app code will reside, and which is already defined for loading in your composer file.

### App file

The App file is the main entry point for your app and is where you can override default behaviour in key areas of your app. If one is not defined, a default will be used.

While in early development, the interface for this class will change a lot, however default implementations will be provided in the Generic instance of the interface to ensure backwards compatibility.

The App instance can be recalled using the Fabric Veneer frontage:

```php
use DecodeLabs\Fabric;

$app = Fabric::getApp();
```

### Structure

Fabric provides solid HTTP and CLI kernels that can handle requests in both contexts. <code>Clip</code> is used for CLI tasks, and <code>Harvest</code> for HTTP.

The HTTP kernel uses an extensible set of Middlewares to provide a flexible request handling pipeline. The default implementation is provided by <code>Harvest</code> and is a good starting point for most apps.

Greenleaf is used for routing and provides a simple, flexible and powerful routing system for HTTP Actions.

## Licensing

Fabric is licensed under the MIT License. See [LICENSE](./LICENSE) for the full license text.
