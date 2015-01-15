<?php

/**
* 采集 http://www.vestitidacerimonia.eu/  A-Z关键词
*/

header("Content-type:text/html; charset=utf-8");
set_time_limit(0);

require 'simple_html_dom.php';
require 'cj.class.php'; //引入采集扩展文件


$dir = './jenjenhouse.com/data/';
if(!file_exists($dir)) {
    mkdir($dir);
    chmod($dir, 0755); 
}

$params = array(
    'node'=>array(
        'element'=>'.tag-list',
        'index' => '0',
        ),
    'items' => array(
        'keyword' => array(
        'index' => 'all',
        'element' => 'td>a',
        //'attr' => 'src',
        ),
    )
);

$atoz = array(
    'A' => 154,
    'B' => 338,
    'C' => 340,
    'D' => 242,
    'E' => 136,
    'F' => 162,
    
    'G' => 87,
    'H' => 81,
    'I' => 129,
    'J' => 24,
    'K' => 30,
    'L' => 168,
    
    'M' => 114,
    'N' => 35,
    'O' => 88,
    'P' => 219,
    'Q' => 7,
    'R' => 91,
    
    'S' => 323,
    'T' => 111,
    'U' => 37,
    'V' => 37,
    'W' => 567,
    'X' => 1,
    
    'Y' => 13,
    'Z' => 2,
    
    '0-9' => 36,
    /*
    '0' => 17,
    '1' => 17,
    '2' => 37,
    '3' => 4,
    '4' => 3,
    '5' => 6,
    '6' => 3,
    '7' => 3,
    '8' => 5,
    '9' => 2,
    */
);


foreach($atoz as $letter=>$ptotal) {
    
    for($i=1; $i<=$ptotal; $i++) {
        $url = 'http://www.jenjenhouse.com/tag/'.$letter.'?p='.$i;    
        $CjModel = new FetchHtml($url);
        $attr = $CjModel->getNodeAttribute($params);

        if(!empty($attr['keyword'])) {
            foreach($attr['keyword'] as $val) {
                if($val) file_put_contents("{$dir}{$letter}.txt", $val."\n", FILE_APPEND);    
            }
            file_put_contents("./jenjenhouse.com/{$letter}-url.txt", $url."\n", FILE_APPEND);   
        }
        
        //echo $url."\n";
        unset($CjModel, $attr); 
    } 
}

echo "complete\n";  









