<?php
require_once "minifyRendererInit.php";
header('Content-Type: application/json');
if(isset($_POST['action'])) {
    $templaterAjax = new Templater($GLOBALS['templaterConfig']);
    if(isset($_POST['descriptor']) && $_POST['descriptor']!="") $templaterAjax->loadLanguagePackade('{"en":'.$_POST['descriptor'].'}', "en"); //"file:///./translation/descriptor-pl.json"
    switch($_POST['action']) {
        case "compileCode":
            //$_POST['content']
            $preparedResult = $templaterAjax->render($_POST['content'])->getRes();
            echo json_encode(["code"=>$preparedResult]);
        break;
    }
} else echo json_encode(["errname"=>"No action defined"]);
?>