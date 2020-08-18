<?php

namespace ObjectModels;

class Sector
{
    private $data;

    public function __construct( array $data ){
        $this->data = $data;
    }

    public function get(): array
    {
        $objectModel = array();
        $title = $this->data['title'];
        $sector = $this->data['sector'];

        $objectModel[0][] = "Obra";
        $objectModel[0][] = $title;
        $objectModel[1][] = "Sector";
        $objectModel[1][] = $sector;

        return $objectModel;
    }
}