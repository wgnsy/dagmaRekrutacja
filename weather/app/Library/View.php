<?php
namespace App\Library;

/**
 * Klasa odpowiedzialna za renderowanie HTMLa
 */
class View{
    public $file;
    public $with;
    public function __construct(){
        $this->with = [];
        $this->file = '';
    }
    /**
     * Funkcja definiuje zmienną $file według przesłanego stringa
     * @param string $view - ścieżka do pliku
     */
    public function view(string $view){
        if(strpos($view,'plugin') !== false){
            if (preg_match('#^plugin.([^\s]+)#m', $view, $match)) {
                $result = explode('.',$match[1]);
                $this->file = 'plugins\\'.$result[0].'\resources\views\\'.$result[1].'.php';  
            }
        }else{
            $this->file = 'resources\views\\'.$view.'.php';
        }
        return $this;
    }
    /**
     * Funkcja definiuje zmienną with, która jest tablicą dla zmiennych przesyłanych do widoku
     * @param mixed $key    - nazwa zmiennej
     * @param mixed $value  - wartość zmiennej
     */
    public function with($key,$value){
        array_push($this->with,[$key => $value]);
        return $this;
    }
    /**
     * Renderowanie widoku
     * @param boolean $print - czy widok ma zostać wygenerowany czy zwrócony jako string (FALSE)
     */
    public function render($print = true){
        
        $output = NULL;
        if(file_exists($this->file)){
            // Extract the variables to a local namespace
            if(!empty($this->with)){
                foreach($this->with as $key => $value){
                    $key = $value;
                    extract($key);
                }
            }
            //start buforowania
            ob_start();

            include $this->file;

            //koniec buforowania
            $output = ob_get_clean();
        }
        if ($print) {
            print $output;
        }
        return $output;
    }
}

?>