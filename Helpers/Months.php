<?php

namespace Helpers;

trait Months
{

    public function getMonth( int $month = null ){
        $months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

        if( !$month ){
            $month = date('m');
        }

        return $months[ $month-1 ];
    }
}