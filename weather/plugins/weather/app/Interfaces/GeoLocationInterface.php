<?php
namespace Plugins\Weather\App\Interfaces;

/**
 * GeoLocationInterface
 * Interfejs do funkcji czysto geograficznych (np. pobranie szerokości geograficznych) 
 */
interface GeoLocationInterface{
    public function getCityCoordinates($city);
    public function getCityData($city);
}

?>