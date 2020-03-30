<?php
require "minifyRendererInit.php";
$router = new TemplaterRouter(new Templater($GLOBALS['templaterConfig']), []);
$router->init();
?>