<?php 

/**
* 批量添加商品属性 
*/

define('IN_ECS', true);
set_time_limit(0);
$sapi = php_sapi_name();
if($sapi != 'cli') {
    die('只能命令行执行');
}

require(dirname(__FILE__).'/../includes/init.php');


$count = $GLOBALS['db']->getOne("SELECT count(*) FROM tmp_goods WHERE is_delete=0");
$pagesize = 500;
$num = ceil($count/$pagesize);


for($i=1; $i<=$num; $i++) {
    $sql = "SELECT goods_id,goods_type FROM tmp_goods WHERE is_delete=0 ORDER BY goods_id LIMIT ".($i-1)*$pagesize.','.$pagesize;       
    $result = $GLOBALS['db']->getAll($sql);
    if($result) {
        foreach($result as $k=>$val) {
            $res = $GLOBALS['db']->getOne("SELECT count(*) FROM tmp_goods_attr WHERE goods_id='{$val['goods_id']}'");
            if($res >0) {
                if($val['goods_type'] == '2') {
                    $insert_sql = "INSERT INTO tmp_goods_attr (goods_id,attr_id,attr_value,attr_price) values ({$val['goods_id']},54,'custom measurements','19.9')";        
                } else if($val['goods_type'] == '8') { 
                    $insert_sql = "INSERT INTO tmp_goods_attr (goods_id,attr_id,attr_value,attr_price) values ({$val['goods_id']},55,'custom measurements','19.9')";       
                }
                //$GLOBALS['db']->query($insert_sql);
            }
            //file_put_contents('/tmp/libaoan_20141119.log', "{$val['goods_id']}\n", FILE_APPEND); 
        }
    }
    
}

echo "compelte\n";