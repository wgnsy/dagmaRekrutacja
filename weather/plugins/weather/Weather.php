<?php
namespace Plugins\Weather;

/**
 * Weather
 * Fundament pluginu pogodowego, posiada podstawowy widok widgetu
 * 
 * @author  Rafał Ryński wgnsy123@gmail.com
 */
class Weather{
    public $config, $api, $city,$country;

    public function __construct(){
        $this->setDefaults();
    }
    /**
     *  - Ładowanie funkcji pomocniczych, np. config()
     *  - Zdefiniowanie klasy odpowiedzialnej za API
     */
    public function setDefaults(){
        $this->loadHelpers();
        $this->setApiHost();
    }
    public function setApiHost(){
        $url = "Plugins\Weather\App\Api\\".config()['api']['default']."Api";
        $this->api = new $url;
    }
    public function loadHelpers(){
        require 'app\helpers\helpers.php';
    }
    /**
     * Setters
     */
    /**
     * Ustawianie miejscowości
     * @param string $city
     */
    public function setCity($city){
        $this->city = $city;
        return $this;
    }
    /**
     * Ustawianie kraju
     * @param string $country
     */
    public function setCountry($country){
        $this->country = $country;
        return $this;
    }
     /**
     * Getters
     */

     /**
      * Pobiera dane o pogodzie i zwraca żądaną wartość
      * @param mixed $property
      */
    public function get($property){
        $data = $this->getWeather();
        return $data[$property];
    }
     /**
     * Zwraca dane pogodowe z API
     */
    public function getWeather(){
        $data = [
            'city'      => ($this->city) ? $this->city     : 'Warsaw',
            'country'   => ($this->city) ? $this->country  : 'Poland'
        ];
        return $this->api->getData($data);
    }
    /**
     * Funkcja odpowiedzialna za renderowanie widoku widgetu
     */
    public function renderView(){
        $data = $this->getWeather();
        return view('plugin.weather.weather')->with('data',$data)->render(false);

    }
   
}

?>