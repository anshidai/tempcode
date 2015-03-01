<?php 


/**
* 导出商品 
*/

define('IN_ECS', true);
set_time_limit(0);
$sapi = php_sapi_name();
if($sapi != 'cli') {
    die('只能命令行执行');
}

require(dirname(__FILE__).'/../includes/init.php');
//require(dirname(__FILE__).'/../includes/lib_common.php');

$categorys = array(
    '1' => 'Wedding-Dresses',
    '3' => 'A-Line-Wedding-Dresses',
    '9' => 'Beach-Wedding-Dresses',
    '8' => 'Lace-Wedding-Dresses',
    '12' => 'Chiffon-Wedding-Dresses',
    '10' => 'Maternity-Wedding-Dresses',
    '11' => 'Trumpet/Mermaid-Wedding-Dresses',
    '4' => 'Special-Occasion-Dresses',
    '19' => 'Cocktail-Dresses',
    '18' => 'Prom-Dresses',
    '17' => 'Evening-Dresses',
    '21' => 'Holiday-Dresses',
    '20' => 'Graduation-Dresses',
    '2' => 'Wedding-Party-Dresses',
    '13' => 'Bridesmaid-Dresses',
    '15' => 'Mother-Wedding-Dresses',
    '14' => 'Flower-Girl-Dresses',
    '5' => 'Homecoming-Dresses',
    '67' => 'Plus-Size-Mother-Of-The-Bride-Dresses',
    '68' => 'Plus-Size-Prom-Dresses',
    '151' => 'Plus-Size-Wedding-Dresses',
    '30' => 'Tea-Length-Wedding-Dresses',
    '74' => 'Wedding-Petticoats',
    '71' => 'Wedding-Gloves',
    '72' => 'Wedding-Veils',
    '73' => 'Wedding-Wraps',
    '70' => 'Wedding-Jewelry',
);


foreach($categorys as $k=>$val) {
    //var_dump($k,$val);exit;
    
    $result = $GLOBALS['db']->getAll("SELECT goods_id,goods_name FROM tmp_goods WHERE cat_id='{$k}' AND is_delete=0 order by goods_id");
    if($result) {
        $csv = "URL,商品名称\n";
        foreach($result as $k2=>$val2) {
            $url = 'http://www.temperqueen.com/'.build_uri('goods', array('gid' => $val2['goods_id']), $val2['goods_name']);
            $csv .= "{$url},{$val2['goods_name']}\n";
        }
        file_put_contents("/tmp/goods/goods_{$k}_{$val}.csv", $csv);     
    }
}

echo "compelte\n";
