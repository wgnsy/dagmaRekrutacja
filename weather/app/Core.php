<?php
namespace App;

use App\Library\View;

/**
 * Rdzeń mini aplikacji, ładuje klasę odpowiedzialną za renderowanie widoku
 */
class Core{
    public $view;
    public function __construct(){
        $this->view = new View();
    }
    public function view($view){
        return $this->view->view($view);
    }
    
}

?>