<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis\Kernel;

use Closure;
use DecodeLabs\Fabric;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Request;
use DecodeLabs\Harvest\Request\Factory\Environment as RequestFactory;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Psr\Http\Server\MiddlewareInterface as PsrMiddleware;
use Psr\Http\Server\RequestHandlerInterface as PsrHandler;

class Http implements Kernel
{
    public const MIDDLEWARE = [
        // Error
        'ErrorHandler',

        // Inbound
        'Https',

        // Outbound
        'ContentSecurityPolicy',

        // Generators
        'Greenleaf'
    ];

    protected Dispatcher $dispatcher;
    protected Request $request;
    protected Context $context;

    /**
     * Init with Context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        // Dispatcher
        $this->dispatcher = new Dispatcher(
            $this->context->container
        );

        // Middleware
        $this->dispatcher->add(
            ...$this->loadMiddleware()
        );

        $this->request = (new RequestFactory())->createServerRequest();
    }

    /**
     * Get middleware list
     *
     * @return array<string|class-string<PsrMiddleware>|PsrMiddleware|Closure(PsrRequest, PsrHandler):PsrResponse>
     */
    protected function loadMiddleware(): array
    {
        return Fabric::getApp()->getHttpMiddleware() ?? static::MIDDLEWARE;
    }

    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return 'Http';
    }

    /**
     * Run app
     */
    public function run(): void
    {
        $response = $this->dispatcher->handle($this->request);
        $transport = Harvest::createTransport();

        $transport->sendResponse(
            $this->request,
            $response
        );
    }

    /**
     * Shutdown app
     */
    public function shutdown(): void
    {
    }
}
