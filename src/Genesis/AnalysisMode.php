<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis;

enum AnalysisMode: string
{
    case Self = 'self';
    case App = 'app';
}
