<!doctype html>
<html lang="pl-PL">
<head>
<!-- Charset -->
<meta charset="utf-8">
<!-- Title -->
<title>Templater Renderer Test</title>
<!-- meta -->
<meta name="author" content="Grano22">
<meta name="description" content="Templater is a template web engine">
<!-- View point -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Local Styles -->
<style type="text/css">
body {margin:0;background: #424242;color:#222;}

textarea {
    width: 100%;
    resize: vertical;
    /*background: #efefef;*/
    background: #333333;
    color: #f0f0f0;
    min-height: 350px;
}

header#header {
    text-align: center;
}

header#header h1 {
    font-style: oblique;
    word-spacing: 0.2em;
    font-family: verdana;
}

footer#footer {
    width: 100%;
    background: #6e6e6e;
}
footer#footer #footerLefty , footer#footer #footerRighty { padding: 11px; display: inline-block; }
footer#footer #footerRighty { float: right; }

#templaterForm {
    margin: 20px auto;
    width: 95%;
    text-align: center;
} 

#templaterForm input[type=submit] {
    padding: 13px 24px;
    border: 0;
    background: #2b2b2b;
    color: #aaa;
    margin: 14px;
    transition: .2s ease-out;
}

#templaterForm input[type=submit]:hover {
    background: #5e5e5e;
    color: #2b2b2b;
    font-weight: bold;
    transition: .2s ease-in;
    cursor: pointer;
    box-shadow: 0 0 2px 2px #333;
}

#templaterOutput {
    width: 100%;
    min-height: 250px;
    background: #555;
}

#templaterForm iframe {
    width: 100%;
    min-height: 350px;
}

/* Definition list */
dt {
    font-weight: bolder;
}

/* Links */
a {
    color: #83939c; /*#4b5459;*/
}
</style>
<!-- Script -->
<script type="text/javascript">
<!--
function prepareJSON(s) {
    s = s.replace(/\\n/g, "\\n")  
               .replace(/\\'/g, "\\'")
               .replace(/\\"/g, '\\"')
               .replace(/\\&/g, "\\&")
               .replace(/\\r/g, "\\r")
               .replace(/\\t/g, "\\t")
               .replace(/\\b/g, "\\b")
               .replace(/\\f/g, "\\f");
    return s = s.replace(/[\u0000-\u0019]+/g,""); 
}
window.onload = function() {
    document.getElementById('compileTemplaterCode').onclick = function(evt) {
        evt.preventDefault();
        var xhr = new XMLHttpRequest();
        xhr.open("POST", 'MinifyRenderer/receiveAsyncOutput.php', true);
        xhr.onreadystatechange = function(e) {
            if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                console.log(this.response);
                var resp = JSON.parse(prepareJSON(this.response));
                document.getElementById('templaterOutput').innerHTML = resp.code;
                var iframe = document.getElementById('templaterIframeOutput');
                var iframecode = resp.code.replace(/<style(\s([A-z0-9]*\=\"[A-z0-9/\\.]*\"))*?>([\s\S]*?)<\/style>/g, "").replace(/<link(\s([A-z0-9]*\=\"[A-z0-9/\\.]*\"))*?>/g, "");
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(iframecode);
                iframe.contentWindow.document.close();
                //{{$zlaZmienna|"Wpisales zla zmienna"}}
            }
        }
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        //var preparedContent = document.forms['templaterForm'].items['templaterCode'].textContent;
        var preparedContent = document.getElementById('templaterCode').value;
        var preparedDescriptor = document.getElementById('descriptorCode').value;
        xhr.send("action=compileCode&content="+preparedContent+"&descriptor="+preparedDescriptor);
    }

    document.getElementById('showTemplaterCode').onchange = function(evt) {
        if(evt.target.checked) {
            document.getElementById('templaterCode').style.display = "initial";
        } else {
            document.getElementById('templaterCode').style.display = "none";
        }
    }

    document.getElementById('showDescriptorCode').onchange = function(evt) {
        if(evt.target.checked) {
            document.getElementById('descriptorCode').style.display = "initial";
        } else {
            document.getElementById('descriptorCode').style.display = "none";
        }
    }
}
//-->
</script>
</head>
<body>
<div id="container">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-fork-ribbon-css/0.2.3/gh-fork-ribbon.min.css" />
<a class="github-fork-ribbon" href="https://github.com/Grano22/templater" target="_blank" data-ribbon="Fork me on GitHub" title="Fork me on GitHub">Fork me on GitHub</a>
<header id="header">
<h1>Templarter Renderer Core</h1>
<img src="images/templaterLogo.jpg" alt="Logo Templatera" width="128">
<dl>
<dt>Wersja</dt><dd>0.1 Beta</dd>
</dl>
<a href="https://sites.google.com/view/templater-documentation/strona-g%C5%82%C3%B3wna" target="_blank">Dokumentacja</a>
<h3>Składnie (Syntax Highlight)</h3>
<a href="download/templaterNPP.xml" download="templaterNPP">Pobierz składnię dla edytora kodu Notepad++</a><br>
Wkrótce dojdzie do większej ilości edytorów i będą usprawniane.
</header>
<main>
<form method="post" id="templaterForm">
<h2>Kod templatera</h2>
<p>Kod w jezyku blokowym Templater, silnik templatek napisany w PHP</p>
<label>Pokaż kod Templatera <input type="checkbox" id="showTemplaterCode" checked></label>
<textarea id="templaterCode" name="templaterCode" placeholder="Wpisz kod templatera">
[block name="linki"]<ul class="links">[for $link of $langDescriptor['linki']]<li><a href="$link">Link $iter</a></li>[/for]</ul>[/block]
[block name="znam"]<div id="przyklad"><h1>{{$langDescriptor['tekst']}}</h1></div>[/block]
[if $langDescriptor['chcesprawdzic']=='takjest']<h2>Faktycznie tak jest!</h2>[/if]
{* Komentarz 
wieloliniowy 
*}
{# Komentarz jednoliniowy
</textarea>
<h2>Kod Deskryptora</h2>
<P>Deksryptor to tablica asocjacyjna tłumaczonych wartości, ma ona wiele zasięgów. Tutaj jest lokalny i tylk ogranicozny do en na potrzbey prostego kompilatora</p>
<label>Pokaż kod descriptora <input type="checkbox" id="showDescriptorCode"></label>
<textarea id="descriptorCode" style="display:none" name="descriptorCode" placeholder="Wpisz kod descriptora">
{
"tekst":"Skompilowane w Templaterze!",
"linki":[
"www.google.pl",
"google.pl"
],
"chcesprawdzic":"takjest"
}
</textarea>
<input id="compileTemplaterCode" type="submit" value="kompiluj">
<h2>Dane wyjściowe (w pudełku)</h2>
<small>Style będą sanitizowane</small>
<div id="templaterOutput"></div>
<h2>Dane wyjściowe Iframe</h2>
<iframe id="templaterIframeOutput"></iframe>
</form>
</main>
<footer id="footer">
<span id="footerLefty">
Grano22 &copy; 2020
</span>
<span id="footerRighty">
Templater Core Renderer
</span>
</footer>
</div>
</body>
</html>