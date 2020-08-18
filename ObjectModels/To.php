<?php

namespace ObjectModels;

class To
{
    private $data;

    public function __construct( array $data ){
        $this->data = $data;
    }

    public function get(): array
    {
        $objectModel = array();
        $to = $this->data['to'];

        for ($i = 0; $i <= count($to); $i++){
            if( !empty($to[$i]) ){
                if( $i==0 ){
                    $objectModel[$i][] = "Dirigido a";
                }else{
                    $objectModel[$i][] = '';
                }
                $objectModel[$i][] = $to[$i];
            }
        }

        return $objectModel;
    }
}