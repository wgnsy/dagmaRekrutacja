<?php
namespace Prrwebcreate\Smoothcms\app\Library\Classes;

use Illuminate\Support\Facades\Storage; 
use Illuminate\Filesystem\Filesystem;
use File;
use Illuminate\Http\Request;
use Prrwebcreate\Smoothcms\App\Models\Themes;
use Prrwebcreate\Smoothcms\App\Models\Fields;
use ZipArchive;
use Prrwebcreate\Smoothcms\app\Library\Traits\PanelTrait\ModelSecurity;
use Prrwebcreate\Smoothcms\app\Library\Traits\PanelTrait\TranslationTrait;
//use Prrwebcreate\Smoothcms\app\Library\Traits\PanelTrait\ModuleTrait;
use Prrwebcreate\Smoothcms\app\Library\Traits\PanelTrait\Traits\FileTrait;

class Template extends Module {
    use TranslationTrait,FileTrait;
    public $themeRootPath,$themeName,$file,$errors = [],$config;
    private $destinationPaths = [];
    public function loadNewTemplate($file){
        
        $upload_path = base_path() . '/Themes/';
        if($file){ //check if uploaded file is file
            $filename = $file->getClientOriginalName();//get file name
            
            if($this->checkFileExtension($filename,'zip')){ //check is uploaded file is zip
             
        $filePath = $upload_path; //get path
        $file->move($filePath, $filename);
        $zip = new \ZipArchive();
        $result_code = $zip->open($upload_path.$filename);
        if( $result_code === true ){
            $themeName = explode('.',$filename)[0];
            $this->themeName = $themeName;
            $this->file = new Filesystem();
            $zip->extractTo(base_path().'/Themes/');
            $this->file->delete(base_path().'/Themes/'.$filename);
            $this->themeRootPath = base_path().'/Themes/'.$themeName;
            $this->loadConfig();
            $this->setDestinationPaths();
            $this->deployTemplate();
            
            
            //Storage::disk('public')->move('uploads/'.$filename, $file);
        }
        $is_success = $filePath.$filename;
        return ['error_msg' => $this->errors];
            }else return ['error_msg' => 'Nieprawidlowe rozszerzenie'];
        }else return ['error_msg' => 'To nie plik'];
        /*$file = $req->file('file');
        $filename = $file->getClientOriginalName();
        //$filename = explode('.',$filename)[0].'.zip';
        $filePath = public_path() . '/files/uploads/';
        $file->move($filePath, $filename);
        $zip = new \ZipArchive();
        $result_code = $zip->open('files/uploads/'.$filename);
        if( $result_code === true ){
            $zip->extractTo('files/uploads/test/');
        }
        $is_success = $filePath.$filename;*/
    }
    public function setDestinationPaths(){
        $this->destinationPaths = [
            'views' => resource_path().'/views/themes/'.$this->themeName.'/views', 
            'assets' => public_path().'/themes/'.$this->themeName.'/',
            'routes' => base_path().'/routes/'.$this->themeName.'/'.$this->themeName.'.php'

        ];
    }
    public function checkFileExtension($filename,$extension){
        
        //$filename = $file->getClientOriginalName(); 
       
            $check_ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        
            if($check_ext == $extension) return true; else return false;
           
    }
    public function deployTemplate(){
        
        if (!$this->file->exists($this->themeRootPath.'/views')) $this->errors[] = 'Brak pliku: views<br>';
        if (!$this->file->exists($this->themeRootPath.'/assets')) $this->errors[] = 'Brak pliku: assets<br>';    
        if (!$this->file->exists($this->themeRootPath.'/lang')) $this->errors[] = 'Brak pliku: lang<br>';    

        if (count($this->errors) == 0){
            $this->clearFields();
            $path = $this->themeRootPath.'/views';
            //deploy theme views to application
            if($this->file->copyDirectory($path,$this->destinationPaths['views'])){
                
                //$config = require resource_path().'/themes//'.$themeName.'/views/theme.php';
                //return ['error_msg' => $config['author']];
            }
      
        
            
            //deploy theme assets to application
            $path = $this->themeRootPath.'/assets';
            $assets = scandir($path); //any directory
            foreach ($assets as $key => $value) {
                        if ('.' !== $value && '..' !== $value){
                            $this->file->copyDirectory($path.'/'.$value,$this->destinationPaths['assets'].$value);
                }
            }

            if($this->file->copyDirectory($path,$this->destinationPaths['assets'])){
               // dd($this->destinationPaths['assets']);
            }

            $path = $this->themeRootPath.'/lang';
            $sub_directories = array_map('basename', glob($path . '/*', GLOB_ONLYDIR));
            
            foreach($sub_directories as $directory){
                $destinationPath = resource_path().'/lang/'.$directory;
                if($this->file->copyDirectory($path.'/'.$directory,$destinationPath)){
                   // dd($this->destinationPaths['lang']);
                }
            }

            //deploy routes
            $path = $this->themeRootPath.'/routes/'.$this->themeName.'.php';
            if(!file_exists(base_path().'/routes/'.$this->themeName)){
                File::makeDirectory(base_path().'/routes/'.$this->themeName, 0755, true, true);
            }
            File::copy($path,$this->destinationPaths['routes']);
            $routeFile = array_filter(file(base_path().'/routes/web.php', true),fn($e) => !in_array($e,["\n"]));
            $routeFile[] = "\nrequire __DIR__.'/".$this->themeName."/".$this->themeName.".php';";
            $content = '';
            foreach($routeFile as $line)
            {
                $content .= $line;
            }
            file_put_contents(base_path().'/routes/web.php', $content);
            //preview image
            File::copy($this->themeRootPath.'/preview.jpg',$this->destinationPaths['assets'].'preview.jpg');
            

            //deploy in db
            if(count(Themes::where('name',$this->themeName)->get()) > 0 ){
                $theme = Themes::where('name',$this->themeName)->first();
                $theme->name= $this->themeName;
                $theme->author = $this->config['author'];
                $theme->version = $this->config['version'];
                $theme->active = 1;
                $theme->preview = 'themes/'.$this->themeName.'/preview.jpg';
                // add more fields (all fields that users table contains without id)
                $theme->save();
            }else{
                $theme = new Themes;
                $theme->name= $this->themeName;
                $theme->author = $this->config['author'];
                $theme->version = $this->config['version'];
                $theme->active = 1;
                $theme->preview = 'themes/'.$this->themeName.'/preview.jpg';
                // add more fields (all fields that users table contains without id)
                $theme->save();

                //create hook fields rows
                $structures = require_once($this->themeRootPath.'/structure.php');
                foreach($structures as $name => $slug){
                    $structureData = array('name' => $name, 'slug' => $slug);
                    Fields::create($structureData);
                }
            }
            
        }  
        
        
            
            
        
    }
    public function loadConfig(){
        $this->config = require $this->themeRootPath.'/theme.php';
    }
    public function delete($id){
        $file = new Filesystem();
        $path = resource_path().'/lang/pl';
        $translationsFiles = array_map( //GET ALL LANG FILES FROM LANG/PL FOLDER
            function($path) {
                return basename($path);
            },
            glob($path.'./{scms}_*.{php}', GLOB_BRACE)
        );
        foreach($translationsFiles as $langFile){
            File::delete($path.'/'.$langFile); //REMOVE LANG FILES FOREACH
        }
        
        $details = $this->getTemplateDetails($id);
        //DELETE ALL THEME FILES


        $file->deleteDirectory(base_path().'/routes/'.$details->name);
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

        $file->deleteDirectory(public_path().'/files/uploads/themes/'.$details->name);
        $file->deleteDirectory(resource_path().'/themes/'.$details->name); //delete views
        $file->deleteDirectory(public_path().'/themes/'.$details->name); // delete assets
        $file->deleteDirectory(resource_path().'/lang/themes/'.$details->name); //delete lang
        $file->deleteDirectory(base_path().'/Themes/'.$details->name); //delete lang
        File::delete(base_path().'/Themes/'.$details->name.'.zip');

        //END DELETE ALL THEME FILES
        
        Themes::destroy($id); //delete from DB
        
        //delete hook fields rows
        $this->clearFields();

        
        return true;
    }
    public function getTemplateDetails($id){
        
        $theme = Themes::find($id);
        return $theme;
    }
    public function getActiveTemplate(){
        return Themes::where('active',1)->first();
    }
    public function clearFields(){
        Fields::where('id', '>=', 0)->delete();
    }

}