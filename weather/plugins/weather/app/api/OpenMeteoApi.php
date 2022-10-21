<?php
namespace Plugins\Weather\App\Api;

use Plugins\Weather\App\Interfaces\ApiInterface;

class OpenMeteoApi implements ApiInterface{
    public $data;
    
    /**
     * Pobieranie danych z API
     * @param array $data - tablica zawierająca nazwę miejscowości oraz kraju.
     */
    public function getData(array $data){
        $this->data = $data;
        $coordinates = $this->getCityCoordinates($this->data['city']);
        $api_url = 'https://api.open-meteo.com/v1/forecast?latitude='.$coordinates['lat'].'&longitude='.$coordinates['lon'].'&hourly=temperature_2m&current_weather=true&timezone=auto&daily';
        $json_data = file_get_contents($api_url);
        $response_data = $this->parseData($json_data);
        
        return $response_data;
    }
    /**
     * Funkcja odpowiedzialna za prawidłowe ułożenie danych wyjściowych
     * @param string $json
     */
    public function parseData($json){
        $cityData = $this->getCityData($this->data['city']);
        $data = json_decode($json);
        $combined = array_combine($data->hourly->time,$data->hourly->temperature_2m);
        $todayDate = date("Y-m-d",strtotime($data->current_weather->time));
        $todayTime = date("H:i:s",strtotime($data->current_weather->time));
        
        return [
            'city'   => [
                'name'        => $this->data['city'],
                'description' => $cityData->display_name
            ],
            'today' => [
                'date'          => $todayDate,
                'time'          => $todayTime,
                'temperature'   => round($data->current_weather->temperature),
                'wind'          => round($data->current_weather->windspeed)
            ],
            'hourly' => $combined
        ];
        
    }
    /**
     * Funkcja zwracająca szerokości grograficzne danej miejscowości
     * @param string $city
     */
    public function getCityCoordinates($city){
        $cityData = $this->getCityData($city);
        $coordinates = ['lat'=>0,'lon'=>0];
        $coordinates['lat'] = $cityData->lat;
        $coordinates['lon'] = $cityData->lon;
        return $coordinates;
    }
    /**
     * Wykorzystanie API do pobrania wartości geograficznych danej miejscowości
     * @param string $city
     */
    public function getCityData($city){
        $api_url = config()['geo']['url'].$city;
        $json_data = file_get_contents($api_url);
        return $response_data = json_decode($json_data)[0];
    }
}

?>