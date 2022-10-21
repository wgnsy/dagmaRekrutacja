<?php

if(!function_exists('view')){
    function view($view){
        $core = new App\Core;
        return $core->view($view);
    }
}

?>