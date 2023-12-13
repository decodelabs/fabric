<?php

require_once 'vendor/autoload.php';

use DecodeLabs\Genesis;
use DecodeLabs\Fabric\Genesis\Hub;

Genesis::initialize(Hub::class, [
    'analysis' => true
]);
