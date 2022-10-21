<?php
/**
 * Kod przygotowany w ramach rekrutacji DAGMA
 * przez: Rafał Ryński
 */
require 'Autoloader.php';
$autoloader = new Autoloader();
$autoloader->register();




$weather = new \Plugins\Weather\Weather;
$weatherView = $weather->setCity('Radzionków')->setCountry('Poland')->renderView();

view('homepage')->with('weather',$weatherView)->with('test2','test3')->render(true);
?>