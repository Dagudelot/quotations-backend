<?php

namespace Config;

trait Environment
{

    public function env()
    {
        return json_decode(file_get_contents("env.json"), true);
    }
}