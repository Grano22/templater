<?php
$definedPipes = [];
new TemplaterPipe($definedPipes, "formatFooterHeader", function($output){ return strtoupper($output); });

?>