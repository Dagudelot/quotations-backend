<?php

namespace Providers;

use Config\Environment;
use Controllers\QuotationsController;
use Controllers\UsersController;
use Services\SheetsService;

class RequestsHandler
{
    use Environment;

    public function __invoke()
    {
        $this->handlePostRequests();
        $this->handleGetRequests();
    }

    private function handlePostRequests(): void
    {
        if( count($_POST) ){
            $requestController = explode('@', $_POST["controller"]);
            $controller = $requestController[0];
            $method = $requestController[1];
            $class = null;

            switch ($controller) {
                case 'QuotationsController':
                    $class = new QuotationsController();
                    break;
            }

            if ($class) $class->{$method}();
        }
    }

    private function handleGetRequests(): void
    {
        // Code sent by google to store accessToken
        if ( isset($_GET['code']) ) {
            $usersController = new UsersController();
            $usersController->setAuthCode();
        }

        // if index route, check auth
        if ( count($_GET) == 0 ) {
            $sheetsService = new SheetsService();
            $client = $sheetsService->getClient();

            if( $client ){
                // Return view
                $return = $this->env()['frontend_url'] . "index.html?auth=true";
                header("location:".$return);
            }
        }
    }
}