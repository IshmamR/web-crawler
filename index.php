<?php
error_reporting(0);

//$start = "http://localhost/New%20folder/Crawler/test.php";
//$start = "https://www.bing.com/search?form=MOZLBR&pc=MOZI&q=browsers";
//$start = "https://en.wikipedia.org";
//$start = "https://youtube.com";
//$start = "https://doftv.xyz";
$start = readline("Init url: ");

$already_crawled = array();
$crawled = array();

function follow_links($url) {
    global $already_crawled;
    global $crawled;
    
    // creating headers
    $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: promethewz/0.1\n"));
    $context = stream_context_create($options);
    
    $doc = new DOMDocument();
    $html = file_get_contents($url, false, $context); // outputs a string
    
    
    $doc->loadHTML($html);
    $linklist = $doc->getElementsByTagName("a");
    
    foreach($linklist as $link) {
        $l = $link->getAttribute("href");
        
        $scheme = parse_url($url)["scheme"];
        $host = parse_url($url)["host"];
        $path = parse_url($url)["path"];
        
        // "/test/another/../../anotherpage.php"
        if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") { 
            $l = $scheme . "://" . $host . $l;
        } 
        // "//www.youtube.com/upload"
        else if (substr($l, 0, 2) == "//") { 
            $l = $scheme . ":" . $l;
        } 
        // "./mooreee.php"
        else if (substr($l, 0, 2) == "./") {
            $l = $scheme . "://" . $host . dirname($path) . substr($l, 1);
        }
        // "#anchor"
        else if (substr($l, 0, 1) == "#") {
            $l = $scheme . "://" . $host . $path . $l;
        }
        // "../../anotherpage.php"
        else if (substr($l, 0, 3) == "../") {
            $l = $scheme . "://" . $host . "/" . $l;
        } 
        // "javascript:d"
        else if (substr($l, 0, 11) == "javascript:") {
            continue;
        }
        // "test.php"
        else if (substr($l, 0, 5) != "https" || substr($l, 0, 4) != "http") {
            $l = $scheme . "://" . $host . "/" . $l;
        }
        
        if (!in_array($l, $already_crawled)) {
            //echo "this is the link:" . $l . "\n";
            $already_crawled[] = $l;
            $crawled[] = $l;
            if (substr($l, 0, 1) != "#") {
                echo get_details($l);
            }
            //print_r($already_crawled);
        }        
    }
    // follow already_crawled links (this is the most important for web crawlers)
    array_shift($crawled);
    foreach($crawled as $site) {
        follow_links($site);
    }

    // var_dump($already_crawled);
}

function get_details($url) {
    $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: promethewz/1.1\n"));
    $context = stream_context_create($options);
    
    $doc = new DOMDocument();
    $html = file_get_contents($url, false, $context); // outputs a string
    //echo $html;
    $doc->loadHTML($html);
    
    $title = $doc->getElementsByTagName("title");
    $title = $title->item(0)->nodeValue;
    
    $description = "";
    $keywords = "";
    $metas = $doc->getElementsByTagName("meta");
    
    for($i = 0; $i < $metas->length; $i++) {
        $meta = $metas->item($i);
        if ($meta->getAttribute("name") == strtolower("description")) {
            $description = $meta->getAttribute("content");
        }
        if ($meta->getAttribute("name") == strtolower("keywords")) {
            $keywords = $meta->getAttribute("content");
        }
    }
    if ($title == "" && $description == "" && $keywords == "") {
        return;
    }
    
    //echo $title."\n".$description."\n".$keywords;
    
    return '{ 
    "Title": "'. str_replace("\n", "", $title) .'",
    "Description": "'. substr(str_replace("\n", "", $description), 0, 350) .'",
    "Keywords": "'. str_replace("\n", "", substr($keywords, 0, 250)) .'",
    "URL": "'. $url .'"
}' . "\n";
}

follow_links($start);
