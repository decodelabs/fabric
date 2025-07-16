<?php

/**
 * @package Fabric
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Fabric\Genesis\Kernel;

use DecodeLabs\Fabric;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Harvest;
use DecodeLabs\Harvest\Dispatcher;
use DecodeLabs\Harvest\Profile;
use DecodeLabs\Harvest\Request;
use DecodeLabs\Harvest\Request\Factory\Environment as RequestFactory;

class Http implements Kernel
{
    public string $mode {
        get => 'Http';
    }

    protected Dispatcher $dispatcher;
    protected Request $request;
    protected Context $context;

    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    public function initialize(): void
    {
        // Dispatcher
        $this->dispatcher = new Dispatcher(
            Fabric::$app->loadHttpProfile() ??
            static::loadDefaultProfile()
        );

        $this->request = (new RequestFactory())->createServerRequest();
    }

    public static function loadDefaultProfile(): Profile
    {
        return Harvest::loadDefaultProfile()
            ->add('?ContentSecurityPolicy')
            ->add('?Zest')
            ->add('Greenleaf');
    }

    public function run(): void
    {
        $response = $this->dispatcher->handle($this->request);
        $transport = Harvest::createTransport();

        $transport->sendResponse(
            $this->request,
            $response
        );
    }

    public function shutdown(): never
    {
        exit;
    }
}
