<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use DecodeLabs\Fabric\Genesis\Bootstrap;

require_once __DIR__ . '/Genesis/Bootstrap.php';

// Init system
$bootstrap = new Bootstrap();
$bootstrap->run();
