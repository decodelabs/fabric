<?php

require_once 'vendor/autoload.php';

use DecodeLabs\Fabric\Genesis\Hub;
use DecodeLabs\Genesis\Bootstrap\Analysis;

new Analysis(
    hubClass: Hub::class
)->initializeOnly();
