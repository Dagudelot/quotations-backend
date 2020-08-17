<?php

namespace Controllers;

use Providers\ControllersProvider;
use Providers\RequestsHandler;

class Controller {

    public function __invoke()
    {
        $controllersProvider = new ControllersProvider();
        $requestsHandler = new RequestsHandler();

        $controllersProvider();
        $requestsHandler();
    }
}