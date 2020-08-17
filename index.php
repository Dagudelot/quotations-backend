<?php

require './vendor/autoload.php';
require_once('./autoload.php');
require_once __DIR__ . "/Controllers/Controller.php";

session_start();

$controller = new \Controllers\Controller();
$controller();