<?php
    $GLOBALS['debugWindowConfig'] = [
        "properties"=>[
            "height"=>"max",
            "width"=>"max",
            "fullscreen"=>true,
            "location"=>1,
            "scrollbars"=>1
        ]
    ];
/*var params = [
    'height='+screen.height,
    'width='+screen.width,
    'fullscreen=yes' // only works in IE, but here for completeness
].join(',');*/

function FilterWindowProperties(array $windowProperties) {
    $filtredArr = []; $allowedProperties = ["fullscreen","height","width","scrollbars","resizable","titlebar","location"];
    foreach($windowProperties as $windowProperty=>$propertyValue) {
        if(in_array($windowProperty, array_keys($allowedProperties))) {
            $filtredArr[$windowProperty] = $propertyValue;
        }
    }
    return $filtredArr;
}

function parsePipeOutput($pipeCollection, array $pipeOutput, array $vars=[], int $pipeIteration=0, $lastPipe=null, $firstPipe=null) {
    if($pipeIteration<0) { return false; }
    $currPipe = rtrim(ltrim($pipeOutput[$pipeIteration]));
    if((substr($currPipe, -1)=='"' || substr($currPipe, -1)=="'") && (substr($currPipe, 0)=='"' || substr($currPipe, 0)=="'")) {
        if($lastPipe==null) {
        $lastPipe = $currPipe;
        }     
    } else if(is_numeric($currPipe)) {
        $lastPipe = intval($currPipe);
    } else if(array_key_exists($currPipe, $pipeCollection)) { //function_exists($lastPipe)
        $lastPipe = $pipeCollection[$currPipe]->invoke($lastPipe);
    } else if(defined($currPipe)) {
        $lastPipe = eval("echo $currPipe;");
    } else if(preg_match("/([A-z0-9]*)\(([A-z0-9,]*?)\)/", $currPipe, $matchedFn)) {
        $funcName = explode("(", $matchedFn[0])[0]; $funcArgs = array_splice(explode("(", $matchedFn[0]), 0, 1);
        $funcArgs[count($funcArgs)] = str_replace(")", "", $funcArgs[count($funcArgs)]);
        if(function_exists($funcName)) {
            $lastPipe = eval("echo $funcName(".implode(",", $funcArgs).");");
        }
    } else if(strpos("$", $currPipe)==0) {
        $lastPipe = parseVariable($currPipe, $vars, false);
    }
    if($pipeIteration==0) $firstPipe = $lastPipe!=null ? $lastPipe : $currPipe;
    if($pipeIteration<count($pipeOutput)-1) return parsePipeOutput($pipeCollection, $pipeOutput, $vars, ++$pipeIteration, $lastPipe, $firstPipe); else { if($lastPipe==null) $lastPipe = $firstPipe; return $lastPipe; }
}

function parseSemiclosedBlock(string $inputString, string $blockName="", array $filterArr=[]) {
    $blockName = $blockName!="" ? $blockName : substr($preparedTag, strpos($preparedTag, "["), strpos($prepareTag, " "));
    $preparedBlock = str_replace(["[$blockName ", "]", '"'], "", preg_replace('/!\s+!/', " ", $inputString));
    $preparedVarBlockArgs = explode(" ", $preparedBlock); $preparedAttrs = [];
    foreach($preparedBlockArgs as $onceArg) {
        if(empty($filterArr) || in_array($onceArg[0], $filterArr)) {
        $onceArg = explode("=", $onceArg);
        $preparedAttrs[$onceArg[0]] = $onceArg[1]; }
    }
    return $preparedVarAttrs;
}

/*function parseBlock(string $inputString, string $blockName="", array $filterArr=[]) {
    $blockName = $blockName!="" ? $blockName : substr($preparedTag, strpos($preparedTag, "["), strpos($prepareTag, " "));

}*/

function parseBlockWithoutAttrs(string $inputString, string $blockName) {
    //$tagName = substr($preparedTag, strpos($preparedTag, "<["), strpos($prepareTag, " "));
    $preparedContent = preg_replace("/\[$tagName(.*)\]/", "", str_replace("[/$tagName]", "", $inputString));
    $preparedStatement = preg_replace("/(\[$tagName|\])/", "", preg_replace("/([\s\S]*?)\[\/$tagName\]/", "", $inputString), 1);
}

function parseBlockWithStatement(string $inputString, string $blockName="") {
    $inputString = preg_replace('/!\s+!/', " ", $inputString);
    //$preparedBlock = str_replace(["[$blockName "], "", $inputString);
    $preparedContent = preg_replace("/\[$blockName(.*)\]/", "", str_replace("[/$blockName]", "", $inputString));
    $preparedStatement = preg_replace("/\]([\s\S]*?)\[\/$blockName\]/", "", preg_replace("/\[$blockName/", "", $inputString, 1));
    //$preparedStatement = str_lreplace("]", "", $preparedStatement);
    return [$preparedStatement, $preparedContent];
}

function parseVars(string $inputString, array $vars) : string {
    preg_match_all("/(\$[A-z_]+[A-z0-9_]*)/", $inputString, $matchedVars);
    $matchedVars = array_filter($matchedVars, function($param) {return $param!="";});
    foreach($matchedVars as $matchedVar) {
        if(!empty($matchedVar) && isset($vars[$matchedVar[0]])) {
        $inputString = str_replace($matchedVar, $vars[$matchedVar], $inputString);
        }
    }
    return $inputString;
}

function parseVarsAdvanced($inputEntry, array $vars, int $currIndex=-1, $lastRes="", $debug=false) {
    if(is_array($inputEntry)) {
        if($currIndex<count($inputEntry)) {
            if(array_key_exists($inputEntry[$currIndex], $lastRes)) { //array_key_exists($inputEntry[$currIndex], $lastRes)
                if(is_array($lastRes[$inputEntry[$currIndex]])) {
                    if($currIndex+1<count($inputEntry)) {
                    return parseVarsAdvanced($inputEntry, $vars, $currIndex++, $lastRes[$inputEntry[$currIndex]]);
                    } else return $lastRes[$inputEntry[$currIndex]];
                } else if(is_string($lastRes[$inputEntry[$currIndex]])) {
                    return $lastRes[$inputEntry[$currIndex]];
                }
            } else {
                return $debug ? '<span class="error">Cannot parse Variable - varname, due to not access index</span>' : "";
            }
        } else {
            return $lastRes;
        }
    } else if(is_string($inputEntry)) {
        $inputEntry = str_replace(["]", "'", '"', "$"], "", $inputEntry);
        $inputEntry = explode("[", $inputEntry);
        if(isset($vars[$inputEntry[0]])) {
        return parseVarsAdvanced($inputEntry, $vars, 1, $vars[$inputEntry[0]], $debug);
        } else return $debug ? '<span class="error">Cannot parse Variable - varname, due to not access index</span>' : "";
    } else {
        return "Unknown Type";
    }
}

function parseVariable(string $inputString, array $vars, bool $detection=true) {
    $parsedVar = "";
    if($detection) {
    if($prepMatchedVars = preg_match('/(\$[A-z_]+[A-z0-9_\]\[\'\"]*)/', $inputString, $matchedVars)) {
    $matchedVar = array_filter($matchedVars, function($param) {return $param!="";});
    $matchedVar = $matchedVar[0];
    $parsedVar = parseVarsAdvanced($matchedVar, $vars);
    }
    } else {
    $parsedVar = parseVarsAdvanced($inputString, $vars);
    }
    return $parsedVar;
}
//parsedVar or stringChanged
function parseVariables(string $inputString, array $vars, string $outputType="parsedVars", bool $stripStrings=true) {
    $parsedVars = []; $stringChanged = "";
    if($prepMatchedVars = preg_match_all('/(\$[A-z_]+[A-z0-9_\]\[\'\"]*)/', $inputString, $matchedVars)) {
    $matchedVars = array_filter($matchedVars[0], function($param) {return $param!="";});
    if(!empty($matchedVars)) {
        foreach($matchedVars as $matchedVar) {
            $varOutput = parseVarsAdvanced($matchedVar, $vars);
            if($outputType=="stringChanged") {
            $stringChanged = str_replace($matchedVar, (($stripStrings && is_string($varOutput)) ? "'".$varOutput."'" : $varOutput), $inputString);
            } else {
            $parsedVar[] = ($stripStrings && is_string($varOutput)) ? "'".$varOutput."'" : $varOutput;
            }
        }
    }
    }
    return $outputType=="stringChanged" ? $stringChanged : $parsedVars;
}

function parseBlockExpression($expression, $removeClosingTag=true, $stripStatement=true) {
    $tagOpenData = getPosByMultipleDeterminer($expression, "[", "]");
    $blockStatement = substr($expression, 0, $tagOpenData[0]);
    $blockInstructions = substr($expression, $tagOpenData[0]);
    if($removeClosingTag) {
        $tagName = explode(" ", str_replace("[", "", $blockStatement))[0];
        $blockInstructions = str_replace('[/'.$tagName.']', "", $blockInstructions);
    }
    if($stripStatement) {
        $blockStatement = preg_replace('/!\s+!/', " ", $blockStatement);
    }
    return [$blockStatement, $blockInstructions];
}

function parseBlockStatementParts(string $inputStr, bool $parseStrings=true) : array {
    $statementParts = []; $inString = false; $lastStringStart = ""; $lastStringSplitPos = 0; $selectedString = "";
    for($i = 0; $i<strlen($inputStr); $i++){
        if($inputStr[$i]=='"' || $inputStr[$i]=="'") {
            if($inString && $lastStringStart==$inputStr[$i]) {
                $inString = false;
                $lastStringStart = "";
            } else {
                $lastStringStart = $inputStr[$i];
                $inString = true;
            }
            
        } else if($inputStr[$i]==" " && $inString===false) {
            array_push($statementParts, $selectedString);
            $lastStringSplitPos = $i;
            $selectedString = "";
        } else if($i==strlen($inputStr)-1) {
            array_push($statementParts, $selectedString);
        }
        if($inputStr[$i]!=" ") $selectedString .= $inputStr[$i];
    }
    return $statementParts;
}

function parseTemplaterTemplate(string $content, Templater $templater, array $localVars=[], array $pipeList=[], bool $parseDebugVariables=false) {
    $startParseTime = microtime(true);
    $allVars = array_merge($templater->globalVars->all, $templater->localVars->all, $localVars); $docVars = [];
    //\{\*.+?\*\}
    $content = preg_replace('/\{\*([\s\S]*?)\*\}/', "", $content);
    /* Linear Parsing */
    $parsedLines = []; $linedContent = "";
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
        //$lineParseTime
        $line = preg_replace('/\{\#(.*)?/', "", $line);
        if($parseDebugVariables) {
            $debugWindowConfig = $GLOBALS['debugWindowConfig'];
            $debugWindowProperty = 'height='.($debugWindowConfig['properties']['height']=="max" ? "screen.height" : $debugWindowConfig['properties']['height']).',width='.($debugWindowConfig['properties']['width']=="max" ? "screen.width" : $debugWindowConfig['properties']['width']).',fullscreen='.$debugWindowConfig['properties']['fullscreen'];
            $content = str_replace('$__debug_vars', $templater->debuger->render("vars", $allVars, $startParseTime), $content);
        }
        $linedContent .= $line;
    }
    $content = $linedContent;
    /* All document Parsing */
    //$resPreparedTags = preg_match_all('/<\[.+?\]>/', $content, $preparedTags)
    if($resPreparedTags = preg_match_all('/<\[([A-z0-9]*)(\s([A-z]*\=\"[A-z0-9]*\"))*?\]>([\s\S]*?)\<\[\/([A-z0-9]*)\]\>/', $content, $preparedTags)) {
        $preparedTags = array_filter($preparedTags,function($child) {return $child!="";});
        foreach($preparedTags[0] as $preparedTag) {
        $originalTag = $preparedTag;
        //$preparedTag = $preparedTag[0];
        $prepadedTag = preg_replace('/!\s+!/', " ", $preparedTag);
        $tagName = substr($preparedTag, strpos($preparedTag, "<["), strpos($preparedTag, " "));
        if(preg_match('/class="([A-z0-9_-]*)"/', $preparedTag, $preparedClasses)) {
            $preparedClasses = str_replace(["class=", '"'], "", $preparedClasses[0]);
            $preparedTag = preg_replace('/class="([A-z]*)"/', 'class="'.(in_array($tagName, ["img"]) ? $templater->preloaderUnderbuildElementsClass : $templater->preloaderElementsClass).$preparedClasses.'"');
        } else {
            $preparedTag = preg_replace('/\]\>/', ' class="'.(in_array($tagName, ["img"]) ? $templater->preloaderElementsClass : $templater->preloaderUnderbuildElementsClass).'">', $prepadedTag, 1);
        }
        $preparedTag = str_replace("<[", "<", str_replace("]>", ">", $preparedTag));
        //var_dump($preparedTag);
        $content = str_replace($originalTag, $preparedTag, $content);
        //Replace Method else $preparedClasses = "";
        //Insert Method
        /*var_dump($preparedTag);
        $parsedAttrs = substr($prepadedTag, strpos(" ", $prepadedTag), strpos("]>", $prepadedTag));
        $content = str_replace($originalTag, '<'.$tagName.' class="'.(in_array($tagName, ["img"]) ? $templater->preloaderUnderbuildElementsClass : $templater->preloaderElementsClass).$preparedClasses).$parsedAttrs.'">'.$prepadedTag."</$tagName>", $content);*/
        }
    }
    //\{\{(\s|)\$([A-z0-9_[\]']*)(\|[A-z0-9_]*|)+(\s|)\}\} enchanced or /\{\{(\s|)\$([A-z0-9_]*)(\|[A-z0-9_]*|)+(\s|)\}\}/
    //\{\{(\s|)\$([A-z0-9_[\]\']*)(\|[A-z0-9_'" ]*|)+(\s|)\}\} or \{\{(\s|)\$([A-z0-9_[\]\']*)(\|[A-z0-9_]*|)+(\s|)\}\}
    if($resPreparedVarInvokes = preg_match_all('/\{\{(\s|)\$([A-z0-9_[\]\']*)(\|[A-z0-9_\'\" ]*|)+(\s|)\}\}/', $content, $preparedVars)) {
        //$preparedVars = array_filter($preparedVars[0], function($child) {return $child!="";});
        foreach($preparedVars[0] as $preparedVar) {
            $preparedVarParsed = str_replace(["{{", "}}"], "", $preparedVar);
            $pipeSteps = explode("|", $preparedVarParsed);
            //$pipeSteps[0] = parseVariable($pipeSteps[0], $allVars);
            $preparedVarParsed = parsePipeOutput($pipeList, $pipeSteps, $allVars);
            //$preparedVarParsed = parseVariable($preparedVar, $allVars);
            $content = str_replace($preparedVar, $preparedVarParsed, $content);
        }
    }
    if($resPreparedInstructionBlocks = preg_match_all('/\[block(\s([A-z]*\=\"[A-z0-9]*\"))?\]([\s\S]*?)\[\/block\]/', $content, $preparedInstructionBlocks)) { //\[block(\s([A-z]*\=\"[A-z0-9]*\"))*?\]([\s\S]*?)\[\/block\]
        $content = preg_replace(['/\[block(\s([A-z]*\=\"[A-z0-9]*\"))?\]/', '/\[\/block\]/'], "", $content);
    }
    if($resPreparedVarBlock = preg_match_all('/\[var(\s([A-z]*\=\"[A-z0-9]*\"))*?\]/', $content, $preparedVarBlock)) { //\[block(\s([A-z]*\=\"[A-z0-9]*\"))*?\]([\s\S]*?)\[\/block\]
        /*$preparedVarBlock = str_replace(["[var ", "]", '"'], "", preg_replace('/!\s+!/', " ", $preparedVarBlock[0]));
        $preparedVarBlockArgs = explode(" ", $preparedVarBlock); $preparedVarAttrs = [];
        foreach($preparedVarBlockArgs as $onceArg) {
            $onceArg = explode("=", $onceArg);
            $preparedVarAttrs[$onceArg[0]] = $onceArg[1];
        }*/
        $parsedAttrs = parseSemiclosedBlock($preparedVarBlock[0], "var", ["name", "scope", "value"]);
        $docVars[$parsedAttrs["name"]] = $docVars["value"];
        $content = preg_replace(['/\[var(\s([A-z]*\=\"[A-z0-9]*\"))*?\]/', '/\[\/var\]/'], "", $content);
    }
    //\[for(\s(\$[A-z0-9]*\s(in|of)\s\$[A-z0-9]*))\]([\s\S]*?)\[\/for\]
    //\[for(\s(\$[A-z0-9_]*\s(in|of)\s\$[A-z0-9\[\]\'\"_]*))\]([\s\S]*?)\[\/for\]
    //preg_match_all('/\[for(\s(\$[A-z0-9_]*\s(in|of)\s\$[A-z0-9\[\]\\'\"_]*))\]([\s\S]*?)\[\/for\]/', $input_lines, $output_array);
    if($resPreparedForBlocks = preg_match_all('/\[for(\s(\$[A-z0-9_]*\s(in|of)\s\$[A-z0-9\[\]\'\"_]*))\]([\s\S]*?)\[\/for\]/', $content, $preparedForBlocks)) {
        for($i=0;$i<count($preparedForBlocks[0]);$i++) { //$preparedForBlocks[0] as $preparedForBlock
            $preparedForBlock = $preparedForBlocks[0][$i];
            $parsedExpression = parseBlockExpression($preparedForBlock);
            //$parsedExpression[1] = str_replace('[/for]', '', $parsedExpression[1]);
           
            $statementParts = parseBlockStatementParts($parsedExpression[0]);
            $contextArr = parseVariable($statementParts[3], $allVars);

            if(is_array($contextArr) && !empty($contextArr)) {
                $preparedInstructionResult = ""; $contextArrKeys = array_keys($contextArr);
                for($iter = 0;$iter<count($contextArr);$iter++) {
                    if($statementParts[2]=="of") {
                        $preparedInstructionResult .= str_replace($statementParts[1], $contextArr[$contextArrKeys[$iter]], $parsedExpression[1]);
                    } else if($statementParts[2]=="in") {
                        $preparedInstructionResult .= str_replace($statementParts[1], $contextArrKeys[$i], $parsedExpression[1]);
                        $preparedInstructionResult = str_replace([$statementParts[3].'["'.$contextArrKeys[$iter].'"]', $statementParts[3]."['".$contextArrKeys[$iter]."']"], $contextArr[$contextArrKeys[$iter]], $preparedInstructionResult);
                    }
                    $preparedInstructionResult = str_replace('$iter', $iter, $preparedInstructionResult);
                }
                $content = str_replace($preparedForBlock, $preparedInstructionResult, $content);
            } else $content = str_replace($preparedForBlock, "Cannot parse", $content);
        }
    }
    //\[if\s([A-z0-9=\'\"\]\[]*)\]([\s\S]*?)\[\/if\]
    if($resPreparedIfBlocks = preg_match_all('/\[if\s([A-z0-9\=\'\"\]\[\$]*)\]([\s\S]*?)\[\/if\]/', $content, $preparedIfBlocks)) {
        foreach($preparedIfBlocks[0] as $preparedIfBlock) {
        $ifParts = parseBlockExpression($preparedIfBlock);
        $ifParams = parseBlockStatementParts($ifParts[0]);
        $ifParams[1] = parseVariables($ifParams[1], $allVars, "stringChanged");
        $statementRes = "";
        eval('try { if('.$ifParams[1].') { $statementRes = \''.$ifParts[1].'\'; } } catch(Exception $err) { $statementRes = \'<span class="error">$err</span>\'; }');
        $content = str_replace($preparedIfBlock, $statementRes, $content); /* ($ifParams[0] ? $ifParams[1] : "") */
        }
    }
    //\[include(\s(\"|\')[A-z0-9._\/]*(\"|\'))*?\]/
    if($resPreparedIncludeBlocks = preg_match_all('/\[include(\s(\"|\')[A-z0-9._\/]*(\"|\'))(\s([A-z]*\=\"[A-z0-9]*\"))*?\]/', $content, $preparedIncludeBlocks)) {
        if(isset($allVars['env']) && $allVars['env']['path']!=='false') {
            $pathSeparated = explode("\\", $allVars['env']['path']);
            unset($pathSeparated[count($pathSeparated)-1]);
            $totalPrepath = implode(DIRECTORY_SEPARATOR, $pathSeparated).DIRECTORY_SEPARATOR;
        } else {
            $totalPrepath = $_SERVER['DOCUMENT_ROOT'];
        }
        foreach($preparedIncludeBlocks[0] as $preparedIncludeBlock) {
            $statementParts = parseBlockStatementParts($preparedIncludeBlock);
            $statementParts[1] = str_replace(['"', "'", "./"], "", $statementParts[1]);
            $relativePath = strpos("/", $statementParts[1])==0 ? $totalPrepath : $totalPrepath;
            $subAllVars = $allVars;
            if(is_file($relativePath.$statementParts[1])) {
                $stream = file_get_contents($relativePath.$statementParts[1]);
                if(count($statementParts)>1) {
                    for($jk=2;$jk<count($statementParts);$jk++) {
                        $statementParam = explode("=", $statementParts[$jk]);
                        $statementParam[1] = str_replace('"', "", $statementParam[1]);
                        switch($statementParam[0]) {
                            case "lang":
                                if(isset($subAllVars['langDescriptor'][$statementParam[1]])) $subAllVars["langDescriptor"] = $subAllVars['langDescriptor'][$statementParam[1]];
                            break;
                        }
                    }
                }
                $content = str_replace($preparedIncludeBlock, parseTemplaterTemplate($stream, $templater, $subAllVars, $pipeList, $parseDebugVariables)[0], $content);
            } else {
                $content = str_replace($preparedIncludeBlock,"", $content);
            }
        }
    }
    return [$content, $docVars];
}

$pipes = [];
class TemplaterPipe {
    public $pipeName = "";
    public $invokableCallback = null;

    function __construct(array &$pipeCollection, string $pipeName, $callback) {
        if(is_closure($callback)) {
            $this->invokableCallback = $callback;
        } else if(is_string($callback) && function_exists($callback)) {

        }
        $pipeCollection[$pipeName] = $this;
    }

    function invoke($input) {
        return $this->invokableCallback->call($this, $input);
    }
}

class TemplaterVariablesCollection {
    private $collection = [];
    private $range = "local";

    function __get(string $varName) {
        if($varName=="all") {
        return $copyArr = $this->collection;
        } else {
        return $this->collection[$varName];
        }
    }
    function __set(string $varName, $assign) {
        if($varName=="all") {

        } else {
        if($assign=="null") { if(isset($this->collection[$varName])) unset($this->collection[$varName]); } else $this->collection[$varName] = $assign;
        }
    }
    function __construct(string $range="local") {
        $this->range = $range;
    }
    function __destruct() {

    }


    function get(string $name="all") {
        return $this->collection[$name];
    }
    function unset(string $name="all") {
        if($name="all") {
            $this->collection = [];
        } else unset($this->collection[$name]);
    }
}

class TemplaterDebuger {
    protected $themes = [];
    protected $selectedTheme = "default";
    protected $displayMode = "unknown"; //Window, Page
    protected $structureSample = '';
    protected $structure = '';
    protected $templaterInstance = null;
    //Logs and errors
    protected $logs = [];

    function __construct(Templater $templater, string $selectedTheme="default", string $structure, array $themes=[]) {
        $defaultTheme = [
            "body"=>["margin"=>"0"],
            "#templaterDebuger"=>[
                "width"=>"100%"
            ],
            "#templaterDebugerHeader"=>[
                "background-color"=>"#5b67b3",
                "width"=>"100%",
                "display"=>"inline-block",
                "text-align"=>"center",
                "border-bottom"=>"2px solid #406987"
            ],
            "#templateDebugerContainer"=>[
                "display"=>"inline-block",
                "width"=>"100%",
                "background-color"=>"#8293b3"
            ],
            "nav#templaterProcessingSpec"=>[
                "display"=>"block",
                "width"=>"100%",
                "margin"=>"0",
                "padding"=>"0",
                "background-color"=>"#1d2a3d"
            ],
            "nav#templaterProcessingSpec span"=>[
                "display"=>"inline-block",
                "padding"=>"15px",
                "color"=>"#fff"
            ],
            "pre"=>[
                "font-size"=>"20px"
            ],
            ".in"=>["margin"=>"10px"]
        ];
        $this->selectedTheme = $selectedTheme;
        if(!empty($themes)) {
            foreach($themes as $theme) {
                $theme = array_merge($defaultTheme, $theme);
            }
        } else $themes["default"] = array_merge($defaultTheme, []);
        $this->themes = $themes;
    }

}

class TemplaterCriticalErrorDebuger extends TemplaterDebuger {
    protected $structureSample = '<div id="templaterDebuger"><div id="templaterDebugerHeader"><h1>Templater Debuger</h1></div><div id="templaterDebugerContainer">$debug</div></div>';

    function __construct(Templater $templater, string $selectedTheme="default", string $structure="" ,array $themes=[]) {
        parent::__construct($templater, $selectedTheme, $structure, $themes);
        $this->templaterInstance = $templater;
        $this->structure = '<style type="text/css">'.arrayToCSS($this->themes[$selectedTheme]).'</style>'.$this->structureSample;
    }

        //Render Templater Debuger Page, enter type
        function render(string $type="vars") {
            $parsedStructure = $this->structureSample;
            switch($type) {
                case "vars":
                    $this->templaterInstance->getVarValue();
                    $parsedStructure = str_replace('$debug', "", $parsedStructure);
                break;
                case "globalVars":
    
                break;
                case "localVars":
    
                break;
            }
            return $parsedStructure;
    
        }
}

class TemplaterDebugerInspector extends TemplaterDebuger {
    protected $structureSample = '<div id="templaterDebuger"><div id="templaterDebugerHeader"><h1>Templater Debuger</h1><nav id="templaterProcessingSpec"><span>Execution Time: $debugExecutionTime</span></nav></div><div id="templaterDebugerContainer"><div class="in">$debug</div></div></div>';
    private $windowSettings = [];

    function __construct(Templater $templater, string $selectedTheme="default", string $structure , array $windowSettings=[], array $themes=[]) {
        parent::__construct($templater, $selectedTheme, $structure, $themes);
        $debugWindowDefaultConfig = [
            "properties"=>[
                "height"=>"max",
                "width"=>"max",
                "fullscreen"=>true
            ]
        ];
        $this->windowSettings = array_merge($debugWindowDefaultConfig, FilterWindowProperties($windowSettings));

    }

    //Render Templater Debuger Page, enter type
    function render(string $type="vars", $param, float $renderingTime=0) : string {
        $debugWindowConfig = $GLOBALS['debugWindowConfig'];
        $debugWindowProperty = 'height='.($debugWindowConfig['properties']['height']=="max" ? "screen.height" : $debugWindowConfig['properties']['height']).',width='.($debugWindowConfig['properties']['width']=="max" ? "screen.width" : $debugWindowConfig['properties']['width']).',fullscreen='.$debugWindowConfig['properties']['fullscreen'];
        $parsedStructure = '<script type="text/javascript">var debugWindow = window.open("", "Templater Debuger Inspector", );debugWindow.moveTo(0,0);debugWindow.document.body.innerHTML=`'.$this->structureSample;
        
        $parsedStructure .= '<style type="text/css">'.arrayToCSS($this->themes[$this->selectedTheme]).'</style>';
        $parsedStructure = str_replace('$debugExecutionTime', (microtime(true)-$renderingTime)."", $parsedStructure);
        switch($type) {
            case "vars":
                $variables = $param;
                $parsedStructure = str_replace('$debug', print_a($variables), $parsedStructure);
            break;
            case "globalVars":
                $variables = $this->templaterInstance->getVarValue("all", "global");
                $parsedStructure = str_replace('$debug', print_a($variables), $parsedStructure);
            break;
            case "localVars":
                $variables = $this->templaterInstance->getVarValue("all", "local");
                $parsedStructure = str_replace('$debug', print_a($variables), $parsedStructure);
            break;
            case "docVars":
                $variables = $param;
            break;
        }
        $parsedStructure .= '`;document.getElementsByTagName("script")[document.getElementsByTagName("script").length-1].remove();</script>';
        return $parsedStructure;

    }

}

class TemplaterRenderedDocument {
    private $structure = '';
    private $variables = [];
    private $documentVars = [];

    private $prepFilename = "";
    private $filename = "";
    private $source = "";
    //Spec
    private $genDate = null;

    private $templater = null;

    function __construct(Templater $templater, string $source, array $inputVariables) {
        $this->genDate = date("Y-m-d H:i:s");
        $this->templater = $templater;
        if(strpos($source, "file:///")===0) {
            $source = str_replace("file:///", "", $source);
            $stream = file_get_contents($source);
            if(isset($inputVariables['env'])) {
                $inputVariables['env']['path'] = $source;
            }
        } else {
            $stream = $source;
            if(isset($inputVariables['env'])) {
                $inputVariables['env']['path'] = "false";
            }
        }
        $this->structure = parseTemplaterTemplate($stream, $this->templater, $inputVariables, $this->templater->pipes ,true)[0];
    }

    function refresh() {

    }

    function getRes() {
        return $this->structure;
    }

    function view() {
        echo $this->structure;
    }
}

class Templater {

    private $globalVariables;
    private $localVariables;

    //Pipes
    private $pipesList = [];

    //Languages
    private $langsInstances = [];

    public $debuger = null;
    public $errorDebuger = null;

    public $preloaderStyles = "";
    public $preloaderStructure = "";
    public $preloaderElementsClass = "preloaded";
    public $preloaderUnderbuildElementsClass = "preloaderUnderbuild";
    public $touchStartClass = "";
    //Other Options
    public $allowUnderbuildPreloader = true;
    
    function __construct(array $options=[]) {
        $this->debuger = new TemplaterDebugerInspector($this, "default", "", []);
        $this->errorDebuger = new TemplaterCriticalErrorDebuger($this);

        $this->localVariables = new TemplaterVariablesCollection("global");
        $this->globalVariables = new TemplaterVariablesCollection("local");

        if(isset($options["preloader"])) {
            $this->preloaderStructure = $options["preloader"]["structure"];
            $this->preloaderStyles = $options["preloader"]["style"];
            $this->preloadedElementsClass = isset($options["preloader"]["preloadedElementsClass"]) ? $options["preloader"]["preloadedElementsClass"] : "preloaded";
            $this->preloaderUnderbuildElementsClass = isset($options["preloader"]["preloadedUnderbuildElementsClass"]) ? $options["preloader"]["preloadedUnderbuildElementsClass"] : "preloadedUnderbuild";
        } else $this->$preloaderElementsClass = "preloaded";
        if(isset($options["pipes"]) && is_array($options["pipes"])) {
            $this->pipesList = $options["pipes"];
        }
    }
    function __get(string $name) {
        switch($name) {
            case "globalVars":
                return $this->globalVariables;
            break;
            case "localVars":
                return $this->localVariables;
            break;
            case "pipes":
                return $this->pipesList;
            break;
            default: 
        }
    }
    function __set(string $name, $content) {
        switch($name) {
            case "globalVars":
                return $this->globalVariables;
            break;
            case "localVars":
                return $this->localVariables;
            break;
            default: 
        }
    }

    //Generate Init Javascript Code
    function JavascriptInit(string $additionalCode="") {
        $jsCode = '<script type="text/javascript">
        <!--'.PHP_EOL;
        if($this->allowUnderbuildPreloader) {
        $jsCode .= 'window.addEventListener("DOMContentLoaded", function(wevt) {
            let preloadedWithoutBuildEl = document.getElementsByClassName("'.$this->preloaderUnderbuildElementsClass.'");
            for(let preloadedEl of preloadedWithoutBuildEl) {
                preloadedEl.classList.remove("'.$this->preloaderUnderbuildElementsClass.'");
            }
        });';
        }
        $jsCode .= 'window.addEventListener("load", function(wevt) {
            document.getElementById("preloader").remove();
            let preloadedElements = document.getElementsByClassName("'.$this->preloaderElementsClass.'");
            for(let preloadedEl of preloadedElements) {
                preloadedEl.classList.remove("'.$this->preloaderElementsClass.'");
            }
        });';
        return $additionalCode.$jsCode.PHP_EOL.'//-->
        </script>';
    }

    //Define variable in templater with scope global|local, if is defined change their value
    function defineVar(string $name, $value, string $scope="global") {

    }

    //Get variable in templater
    function getVarValue(string $name, string $scope="global") {
        switch($scope) {
            case "global":
                return $this->globalVariables->get($name);
            break;
            case "local":
                return $this->localVariables->get($name);
            break;
        }
    }

    //Unsert variable in templater
    function unsetVar(string $varName, string $scope="global") {

    }

    function render($temporlink, array $localVars=[], array $langOpt=[]) : TemplaterRenderedDocument {
        /*if(is_string($temporlink)) {
            if(is_url($temporlink)) {
                $expContent = file_get_contents($temporlink);
            } else {
                $expContent = $temporlink;
            }
        } else {

        }*/
        //parseTemplaterTemplate($expContent, $this, array_merge($this->globalVars->all, $this->localVars->all, $localVars), true);
        $langOpt = array_merge(["langInstance"=>0, "langIndex"=>"", "scope"=>"*"], $langOpt);
        if(isset($langOpt["langInstance"]) && isset($this->langsInstances[$langOpt["langInstance"]])) {
            if($langOpt['langIndex']=="") $langOpt['langIndex'] = $this->langsInstances[$langOpt["langInstance"]]->defaultLang;
            $localVars["langDescriptor"] = $this->langsInstances[$langOpt["langInstance"]]->toDescriptor($langOpt['langIndex'], $langOpt['scope']);
        }
        $localVars['env'] = [];
        $localVars['env']['initJavascript'] = $this->JavascriptInit();
        $localVars['env']['preloaderCode'] = '<div id="preloader"><style type="text/css">div#preloader {display:flex;justify-content:center;align-items:center;position:fixed;width:100%;height:100vh;top:0;left:0;} '.$this->preloaderStyles.'</style><div id="preloader-animation">'.$this->preloaderStructure.'</div></div>';
        return new TemplaterRenderedDocument($this, $temporlink, array_merge($this->globalVars->all, $this->localVars->all, $localVars));
    }

    function renderAfter(string $type="vars", $param, float $renderingTime=0) {

    }

    function renderErrorPage($temporlink, array $localVars=[], array $langOpt=[]) {

    }

    //Debug Templater
    function debug() {

    }

    //Load language packade (JSON)
    function loadLanguagePackade($jsonstrorlink, $defaultLang="en") {
        if(is_array($jsonstrorlink)) {
        $streams = [];
        foreach($jsonstrorlink as $entry) {
            if(strpos($entry, "file:///")===0) {
                $entry = str_replace("file:///", "", $entry);
                array_push($streams, json_decode(file_get_contents($entry, true)));
            } else {
                array_push($streams, json_decode($jsonstrorlink, true));
            }
        }
        $parsedLangDescriptor = new TemplaterLanguageDescriptor($streams, ["defaultLang"=>$defaultLang]);
        } else {
        if(strpos($jsonstrorlink, "file:///")===0) {
            $jsonstrorlink = str_replace("file:///", "", $jsonstrorlink);
            $stream = file_get_contents($jsonstrorlink);
        } else {
            $stream = $jsonstrorlink;
        }
        $parsedLangDescriptor = new TemplaterLanguageDescriptor([json_decode($stream, true)], ["defaultLang"=>$defaultLang]);
        }
        array_push($this->langsInstances, $parsedLangDescriptor); 
    }
}
//Templater User Data Controller
class TemplaterUserData {


    function __construct() {

    }
}
//Templater Session Data Controller
class TemplaterSessionData {
    public $name = "session";
    private $currentSession = false;

    function __construct(string $name = "session", $expireTime=0, $path="/", $otherOptions=[]) { //ff
        $currParam = session_get_cookie_params(); //$sessionSettings = array_merge($currParam, $options);
        session_set_cookie_params($expireTime, $path);
        session_name($name);
    }

    function __get($name) {
        switch($name) {
            case "isCurrent":
                return $this->currentSession;
            break;
        }
    }

    function start() {
        session_start();
        $this->currentSession = true;
        return $this;
    }

    function terminate() {

    }
}

//Templater Language Descriptor Controller
class TemplaterLanguageDescriptor {

    private $entries = [];

    //Options
    public $defaultLang = "en";

    function __construct($langEntries, $options=["defaultLang"=>"en"]) {
        $supportedDescriptorPrefixes = [
            "pl"=>["name"=>"polski", "full"=>"pl-PL", "charset"=>"utf-8"],
            "en"=>["name"=>"english", "full"=>"pl-PL", "charset"=>"utf-8"],
            "de"=>["name"=>"deutch", "full"=>"pl-PL", "charset"=>"utf-8"]
        ];
        if(is_string($options)) {
            $this->defaultLang = $options;
        } else if(is_array($options)) {

        } 
        foreach($langEntries[0] as $langEntryName=>$langEntry) {
            if(array_key_exists($langEntryName, $supportedDescriptorPrefixes)) {
                $this->entries[$langEntryName] = $langEntry;
            }
        }
    }

    function addEntries($langEntries) {
        $supportedDescriptorPrefixes = [
            "pl"=>["name"=>"polski", "full"=>"pl-PL", "charset"=>"utf-8"],
            "en"=>[],
            "de"=>[]
        ];        
        foreach($langEntries as $langEntryName=>$langEntry) {
            if(array_key_exists($langEntryName, $supportedDescriptorPrefixes)) {
                $this->entries[$langEntryName] = array_merge($this->entries[$langEntryName], $langEntry);
            }
        }
    }

    function toDescriptor($lang="default", $scope="*") {
        $preparedDescriptor = [];
        if($lang=="default" || $lang=="") {
            if(array_key_exists($this->defaultLang, $this->entries)) {
                if($scope="" || $scope=="*") {
                    $preparedDescriptor = $this->entries[$this->defaultLang];
                } else {
                    if(array_key_exists($scope, $this->entries[$this->defaultLang])) $preparedDescriptor = $this->entries[$this->defaultLang][$scope];
                }
            }
        } else if(array_key_exists($lang, $this->entries)) { 
            if($scope=="" || $scope=="*") {
                $preparedDescriptor = $this->entries[$lang];
            } else {
                if(array_key_exists($scope, $this->entries[$lang])) $preparedDescriptor = $this->entries[$lang][$scope];
            }
        }
        return $preparedDescriptor; 
    }
}

class TemplaterEnv {
    private $defaultLang = "en";
    private $currentLang = "";

    function __construct() {

    }
}

?>