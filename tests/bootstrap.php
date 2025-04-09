<?php

require_once 'vendor/autoload.php';

use DecodeLabs\Genesis\Bootstrap\Analysis;
use DecodeLabs\Fabric\Genesis\Hub;

new Analysis(
    hubClass: Hub::class
)->initializeOnly();
