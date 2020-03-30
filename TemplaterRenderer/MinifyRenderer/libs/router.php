<?php
//Templater Router by Grano22 v.1
class TemplaterRouter {
    private $hostname = "";
    private $fullUrl = "";
    private $destinationURL = "";
    private $executionScriptPath = "";

    private $targetFileURL = "";
    private $fullTargetFileUrl = "";

    private $routerPath = "";

    //Protocol
    private $protocol = "";

    //Routes
    private $routesDestinations = [];

    //View
    private $templaterView = null;

    function __construct(Templater $templaterView, array $routesList=[]) {
        $this->templaterView = $templaterView;
        $this->hostname = $_SERVER['HTTP_HOST'];

        $this->protocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on' ? "https" : "http";

        $this->targetFilePath = $_SERVER["SCRIPT_FILENAME"];

        //Url Data
        $this->destinationURL = str_replace($_SERVER["QUERY_STRING"], "", ltrim($_SERVER["REQUEST_URI"], "/"));
        $this->targetFileURL = str_replace($_SERVER['DOCUMENT_ROOT'], "", str_replace("\\", "/", $_SERVER["SCRIPT_FILENAME"]));
        $this->fullTargetFileUrl = $this->protocol."//".$this->hostname.$this->targetFileURL;
        $this->fullUrl = $this->protocol."//".$this->hostname."/".ltrim($_SERVER["REQUEST_URI"], "/");
        $this->destinationRootURL = str_replace(str_replace(["index.php", "/"],"",$this->targetFileURL),"",$this->destinationURL);
   
        $this->routerPath = __FILE__;

        $this->projectPath = explode('MinifyRenderer\libs\\', __FILE__)[0];
        /*$this->projectSubpath = explode("\\", $this->projectPath);
        $this->projectSubpath = $this->projectSubpath[count($this->projectSubpath)-2];*/

        $this->fullTargetFilePath = $_SERVER['DOCUMENT_ROOT'].$this->targetFileURL;
        $this->destinationPath = $_SERVER['DOCUMENT_ROOT']."/".$this->destinationURL;

        $this->routesDestinations = array_merge([
            "/"=>["path"=>"/view/client/index.tpf"]
        ], $routesList);
        $parsedRoutes = [];
        foreach($this->routesDestinations as $destinName=>$destinData) {
            if(isset($destinData['path'])) {
            $destinData['path'] = str_replace("/", "\\", $destinData['path']);
            if(strpos("\\", $destinData['path'])==0) $destinData['path'] = preg_replace("/\\\/", $this->projectPath, $destinData['path'], 1);
            //if(strpos("/", $destinName)==0) $destinName = preg_replace("/\//", str_replace(["index.php", "/"], "", $this->targetFileURL)."/", $destinName, 1);
            $parsedRoutes[$destinName] = $destinData;
            }
        }
        $this->routesDestinations = $parsedRoutes;
        /*$urlData = pathinfo($this->fullUrl);
        var_dump($urlData);*/
    }

    function init() {
        if(is_file($this->destinationPath)) {
            $fileParts = pathinfo($this->destinationPath);
            $urlForomats = ["htm", "html", "php", "tpr"];
            if(in_array($fileParts['extension'], $urlForomats)) {
                include $this->destinationPat;
            } else {
                echo file_get_contents($this->destinationPat);
            }
        } else if(array_key_exists($this->destinationRootURL, $this->routesDestinations)) {
            if(is_file($this->routesDestinations[$this->destinationRootURL]["path"])) {
                $fullPath = $this->routesDestinations[$this->destinationRootURL]["path"];
                $fileParts = pathinfo($fullPath);
                if($fileParts['extension']=="tpf" || $fileParts['extension']=="tpr") {
                    $this->templaterView->loadLanguagePackade("file:///translation/descriptor-pl.json", "pl");
                    //$this->templaterView->render("file:///$fullPath", [], ["langIndex"=>"pl", "scope"=>"footer"])->view();
                    $this->templaterView->render("file:///$fullPath", [], ["langIndex"=>"pl"])->view();
                }
            }
        } else {

        }
    }
}
?>