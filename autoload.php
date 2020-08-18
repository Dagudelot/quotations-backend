<?php

function ClassLoader($className)
{
    if(file_exists(__DIR__ . "/" . str_replace('\\', '/', $className) . '.php'))
    {
      require_once(__DIR__ . "/" . str_replace('\\', '/', $className) . '.php');
    }
    else {
      echo 'Error trying lo load class: '. __DIR__ . "/" . str_replace('\\', '/', $className) . '.php';
    }
}

spl_autoload_register('ClassLoader');