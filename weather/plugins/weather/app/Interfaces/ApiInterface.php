<?php
namespace Plugins\Weather\App\Interfaces;

interface ApiInterface extends GeoLocationInterface{
    public function getData(array $data);
    public function parseData($json);
}

?>