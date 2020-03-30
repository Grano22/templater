<?php
//CSS processing by grano22
function arrayToCSS(array $arrInput, string $currCss="") {
    $cssc = $currCss!="" ? $currCss : "";
    foreach($arrInput as $cssSelector=>$cssRules) {
        $cssc .= "$cssSelector {\n";
        foreach($cssRules as $cssDef=>$cssParam) {
            if(strcontains(["@media"], $cssSelector)) {
                $cssc .= "$cssDef {\n";
                foreach($cssParam as $cssSubdef=>$cssSubparam) {
                    $cssc .= "$cssSubdef:$cssSubparam;\n";
                }
                $cssc .= "}";
            } else {
                $cssc .= "$cssDef:$cssParam;\n";
            }
        }
        $cssc .= "}";
    }
    return $cssc;
}

//([A-z0-9#.>:_ ]*)\{([\s\S]*?)\} ([A-z0-9#.>:_@()\- ]*)\{([\s\S]*?)\}
function CSStoArray(string $cssString) {
    $cssArr = []; $cssString = preg_replace("/!\s+!/", " ", $cssString);
    $preparedCss = preg_match_all("/([A-z0-9#.>:_@()\- ]*)\{([\s\S]*?)\}(\s*\}|)/", $cssString, $cssArray);
    //print_a($cssArray);
    foreach($cssArray[0] as $cssDetection) {
        if(strcontains_arr(["@media"], $cssDetection)) {
            
        } else {
            $cssDetection = explode("{", str_replace("}", "", $cssDetection));
            //print_a($cssDetection, "prer", ["separate"=>"<hr>"]);
            $prepDetection = array_filter(explode(";", $cssDetection[1]), function($res){return trim($res)!="";});
            if(count($prepDetection)>0) {
            $cssDetection[0] = ltrim(rtrim($cssDetection[0]));
            $cssArr[$cssDetection[0]] = [];
            foreach($prepDetection as $prepOnce) {
                $prepOnce = explode(":", $prepOnce);
                $prepOnce[0] = ltrim(rtrim($prepOnce[0]));
                $prepOnce[1] = ltrim(rtrim($prepOnce[1]));
                $cssArr[$cssDetection[0]][$prepOnce[0]] = $prepOnce[1];
            }
            }
        }
    }
    return $cssArr;
}
?>