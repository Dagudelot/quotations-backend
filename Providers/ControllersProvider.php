<?php

namespace Providers;

class ControllersProvider
{

    public function __invoke()
    {
        $dir    = './Controllers/';
        $controllersArray = scandir($dir);

        $controllers = array_filter($controllersArray, function($controller){
            return $controller != "." && $controller != ".." && $controller != "Controller.php";
        });

        foreach ($controllers as $controller){
            include "./Controllers/" . $controller;
        }
    }
}