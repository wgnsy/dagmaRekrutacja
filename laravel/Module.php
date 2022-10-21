<?php
namespace prrwebcreate\smoothcms\app\Library\Classes;

use Prrwebcreate\Smoothcms\app\Library\Traits\PanelTrait\Traits\Validate;
use Prrwebcreate\Smoothcms\app\Library\Traits\PanelTrait\ModelSecurity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
use File;
use Illuminate\Http\Request;
use Prrwebcreate\Smoothcms\App\Models\Themes;
use Prrwebcreate\Smoothcms\App\Models\Modules;
use Prrwebcreate\Smoothcms\App\Models\Hooks;
use ZipArchive;
use Illuminate\Support\Facades\Artisan;

class Module {
    use Validate;
    public $moduleRootPath,$moduleName,$file,$config;
    private $destinationPaths = [];

    public function loadNewModule($file){
        
        $upload_path = base_path() . '/modules/';
        if($file){ //check if uploaded file is file
            $filename = $file->getClientOriginalName();//get file name
            
            if($this->checkFileExtension($filename,'zip')){ //check is uploaded file is zip
             
        $filePath = $upload_path; //get path
        $file->move($filePath, $filename);
        $zip = new \ZipArchive();
        $result_code = $zip->open($upload_path.$filename);
        if( $result_code === true ){
            $moduleName = explode('.',$filename)[0];
            $this->moduleName = $moduleName;
            $this->file = new Filesystem();
            $zip->extractTo($upload_path);
            $this->file->delete($upload_path.$filename);
            $this->moduleRootPath = $upload_path.$moduleName;

            $validate = $this->validateModule($this->moduleRootPath);

        if (count($validate['errors']) == 0){
            if(count($validate['warnings']) > 0) $this->warnings = $validate['warnings'];
            $this->loadModuleConfig();
            $this->setModuleDestinationPaths();
            $this->deployModule();
            addlog('<span class="text-success">OK</span>','OK','Załadowano moduł:'.$this->moduleName,__DIR__);
            return ['module' => $moduleName,'provider' => ($this->config['provider']) ? $this->config['provider'] : ''];
        } else $this->errors = $validate['errors'];
        }
        return ['error_msg' => $this->errors,'warning_msg' => $this->warnings];
            }else return ['error_msg' => 'Nieprawidlowe rozszerzenie'];
        }else return ['error_msg' => 'To nie plik'];
    }
    public function setModuleDestinationPaths(){
        $this->destinationPaths = [
            'views' => resource_path().'/views/modules/'.$this->moduleName.'/views', 
            'assets' => public_path().'/modules/'.$this->moduleName,
            'routes' => base_path().'/routes/modules/'.$this->moduleName.'/'.$this->moduleName.'.php',
            'config' => base_path().'/config/'.$this->moduleName.'.php'
        ];
    }

    public function checkFileExtension($filename,$extension){
            $check_ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            if($check_ext == $extension) return true; else return false;   
    }
    public function deployModule(){
        $json = require base_path('modules/'.$this->moduleName.'/module.php');
        //deploy module views
        /*$path = $this->moduleRootPath.'/views';
        $this->file->copyDirectory($path,$this->destinationPaths['views']);*/
        
        //deploy module assets to application
        $path = $this->moduleRootPath.'/assets';
        $this->copyDirectories($path,$this->destinationPaths['assets']);

        //deploy module lang to application
        /*$path = $this->moduleRootPath.'/lang';
        $this->copyDirectories($path,resource_path().'/lang');*/

        //deploy module controller to application
        /*$path = $this->moduleRootPath.'/controllers';
        File::ensureDirectoryExists(app_path().'/Http/Controllers/'.$this->moduleName);
        $this->copyFiles(dirPath:$path,destinationPath:app_path().'/Http/Controllers/'.$this->moduleName);*/
    
        //deploy module providers
        /*$path = $this->moduleRootPath.'/providers';
        File::ensureDirectoryExists(app_path().'/Providers/Modules');
        File::ensureDirectoryExists(app_path().'/Providers/Modules/'.$this->moduleName);
        $this->copyFiles($path,'./*{ServiceProvider}.{php}',app_path().'/Providers/Modules/'.$this->moduleName);*/

        //deploy module model to application
        /*$path = $this->moduleRootPath.'/models';
        File::ensureDirectoryExists(app_path().'/Models/'.$this->moduleName);
        $this->copyFiles(dirPath:$path,destinationPath:app_path().'/Models/'.$this->moduleName);*/

        //deploy module database to application
        /*$path = $this->moduleRootPath.'/database';
        File::ensureDirectoryExists(base_path().'/database/migrations/'.$this->moduleName);
        $this->copyFiles(dirPath:$path,destinationPath:base_path().'/database/migrations/'.$this->moduleName);
        \Artisan::call('migrate --path=database/migrations/'.$this->moduleName);
        */
        //deploy module config 
        /*if(file_exists($this->moduleRootPath.'/config/'.$this->moduleName.'.php')){
        $configPath = $this->moduleRootPath.'/config/'.$this->moduleName.'.php';
        File::copy($configPath,$this->destinationPaths['config']);
        }*/

        //preview image
        File::copy($this->moduleRootPath.'/preview.jpg',$this->destinationPaths['assets'].'/preview.jpg');

            /*//deploy routes
            $path = $this->moduleRootPath.'/routes/'.$this->moduleName.'.php';
            //make modules directory in routes if doesnt exist
            if(!file_exists(base_path().'/routes/modules')) File::makeDirectory(base_path().'/routes/modules', 0755, true, true);
            if(!file_exists(base_path().'/routes/modules/'.$this->moduleName)){
                File::makeDirectory(base_path().'/routes/modules/'.$this->moduleName, 0755, true, true);
            }
            File::copy($path,$this->destinationPaths['routes']);
            $routeFile = array_filter(file(base_path().'/routes/web.php', true),fn($e) => !in_array($e,["\n"]));
            $routeFile[] = "\nrequire __DIR__.'/modules/".$this->moduleName."/".$this->moduleName.".php';";
            $content = '';
            foreach($routeFile as $line)
            {
                $content .= $line;
            }
            file_put_contents(base_path().'/routes/web.php', $content);*/

            //deploy crud if exist
            if( array_key_exists('crud',$json) && $json['crud'] == true){
                if (file_exists($this->moduleRootPath.'/crud')) {
                    //controllers
                    $path = $this->moduleRootPath.'/crud/Controllers';
                    $destinationPath = base_path().'/packages/prrwebcreate/smoothcms/src/app/Http/Controllers/'.$this->moduleName;  
                    File::ensureDirectoryExists(base_path().'/packages/prrwebcreate/smoothcms/src/app/Http/Controllers/'.$this->moduleName);
                    $this->copyFiles($path,'./*{Controller}.{php}',$destinationPath);
        
                    //routes
                    $routesPath = $this->moduleRootPath.'/crud/routes';
                    if(file_exists($routesPath.'/'.$this->moduleName.'.php')){
                        File::ensureDirectoryExists(base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules'); //create modules directory if doesnt exist
                        File::copy($routesPath.'/'.$this->moduleName.'.php',base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules/'.$this->moduleName.'.php');
                        $routeFile = array_filter(file(base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules.php', true),fn($e) => !in_array($e,["\n"]));
                        $routeFile[] = "\nrequire __DIR__.'/modules/".$this->moduleName.".php';";
                        $content = '';
                        foreach($routeFile as $line)
                        {
                            $content .= $line;
                        }
                        file_put_contents(base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules.php', $content);
                    }
                    
                }
            }
        

            //deploy in db
            if(count(Modules::where('name',$this->moduleName)->get()) > 0 ){
                $module = Modules::where('name',$this->moduleName)->first();
                $module->name= $this->moduleName;
                $module->author = $this->config['author'];
                $module->version = $this->config['version'];
                $module->active = 1;
                $module->preview = 'modules/'.$this->moduleName.'/preview.jpg';
                // add more fields (all fields that users table contains without id)
                $module->save();
            }else{
                $module = new Modules;
                $module->name= $this->moduleName;
                $module->author = $this->config['author'];
                $module->version = $this->config['version'];
                $module->active = 1;
                $module->preview = 'modules/'.$this->moduleName.'/preview.jpg';
                // add more fields (all fields that users table contains without id)
                $module->save();
            }
            $this->publishHooks($module);

    }
    private function loadModuleConfig(){
        $this->config = require $this->moduleRootPath.'/module.php';
    }
    public function deleteModule($id){
        $file = new Filesystem();  
        $details = $this->getModuleDetails($id);

        //DELETE ALL MODULE FILES

            $file->deleteDirectory(base_path().'/routes/modules/'.$details->name);
            $routeFile = file(base_path().'/routes/web.php', true);
            $routeFile[] = "\nrequire __DIR__.'/".$details->name."/".$details->name.".php';";
            $content = '';
            foreach($routeFile as $line)
            {
                $isInLine = strpos($line, $details->name);
                if($isInLine){
                    continue;
                }
                $content .= $line;
            }
            file_put_contents(base_path().'/routes/web.php', $content);


        $file->deleteDirectory(base_path().'/modules//'.$details->name);
        $file->deleteDirectory(resource_path().'/views/modules/'.$details->name); //delete views
        $file->deleteDirectory(public_path().'/modules/'.$details->name); // delete assets
        $file->deleteDirectory(resource_path().'/lang/modules/'.$details->name); //delete lang
        $file->deleteDirectory(app_path().'/Http/Controllers/'.$details->name);
        $file->deleteDirectory(base_path().'/database/migrations/'.$details->name);
        $file->deleteDirectory(app_path().'/Models/'.$details->name);
        $file->deleteDirectory(app_path().'/Providers/Modules/'.$details->name);

        File::delete(base_path().'/config/'.$details->name.'.php');
        //END DELETE ALL THEME FILES
        Hooks::where('module_id',$id)->delete();
        Modules::destroy($id); //delete from DB
        
        //delete crud if exist
        if (file_exists(base_path().'/packages/prrwebcreate/smoothcms/src/app/Http/Controllers/'.$details->name)) {
            $file->deleteDirectory(base_path().'/packages/prrwebcreate/smoothcms/src/app/Http/Controllers/'.$details->name);
        }
        //delete crud routes
        $crudRouteFile = file(base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules.php', true);
        $crudRouteFile[] = "\nrequire '/modules/".$details->name."/".$details->name.".php';";
            $content = '';
            foreach($crudRouteFile as $line)
            {
                $isInLine = strpos($line, $details->name);
                if($isInLine){
                    continue;
                }
                $content .= $line;
            }
            file_put_contents(base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules.php', $content);
            File::delete(base_path().'/packages/prrwebcreate/smoothcms/src/routes/smoothcms/modules/'.$details->name.'.php');
        
        addlog('<span class="text-success">OK</span>','OK','Usunięto moduł:'.$details->name,__DIR__);
        return true;
    }
    public function getModuleDetails($id){
        
        $module = Modules::find($id);
        return $module;
    }
    public function getActiveModule(){
        return Modules::where('active',1)->first();
    }
    public function publishHooks($module){
        $hooks = require $this->moduleRootPath.'/resources/views/hooks/index.php';
        foreach($hooks as $hook){
            $hookObj = new Hooks;
            $hookObj->module_id = $module->id;
            $hookObj->name = $hook['name'];
            $hookObj->hook = $hook['file'];
            // add more fields (all fields that users table contains without id)
            $hookObj->save();
        }
    }
    public function renderModulesList(){

    }

}