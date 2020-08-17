<?php

namespace ObjectModels;

class Item
{
    private $data;

    public function __construct( array $data ){
        $this->data = $data;
    }

    public function get(): array
    {
        $objectModel = array();
        $items = $this->data['items'];
        $unit_values = $this->data['unit_values'];
        $measurement_units = $this->data['measurement_units'];
        $quantities = $this->data['quantities'];

        for ($i = 0; $i < count($items); $i++){
            if( !empty($items[$i]) ){
                $objectModel[$i][] = $items[$i];
                $objectModel[$i][] = (int) $quantities[$i];
                $objectModel[$i][] = $measurement_units[$i];
                $objectModel[$i][] = (int) $unit_values[$i];
            }
        }

        return $objectModel;
    }
}