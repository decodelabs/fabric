<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric;

use Closure;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

interface App
{
    public function __construct(
        ?string $namespace
    );

    /**
     * Get app namespace
     */
    public function getNamespace(): ?string;

    /**
     * Perform any loader initialization
     */
    public function initializeLoaders(StackLoader $stack): void;

    /**
     * Perform any platform initialization
     */
    public function initializePlatform(): void;


    /**
     * Get middleware list
     *
     * @return array<string|class-string<PsrMiddleware>|PsrMiddleware|Closure(PsrRequest, PsrHandler):PsrResponse>
     */
    public function getHttpMiddleware(): ?array;
}
