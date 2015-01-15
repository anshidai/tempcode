<?php 

/**
* 导入商品评论 
*/

define('IN_ECS', true);
set_time_limit(0);
$sapi = php_sapi_name();
if($sapi != 'cli') {
    die('只能命令行执行');
}

require(dirname(__FILE__).'/../includes/init.php');

$cout = $GLOBALS['db']->getOne("SELECT count(*) FROM tmp_comment"); 
$pagesize = 1000;
$num = ceil($cout/$pagesize);
for($i=1; $i<=$num; $i++) {
    $sql = "SELECT comment_id,id_value FROM tmp_comment ORDER BY comment_id LIMIT ".($i-1)*$pagesize.','.$pagesize;    
    $result = $GLOBALS['db']->getAll($sql);
    if($result) {
        foreach($result as $k=>$val) {
            $sql_goods = "SELECT goods_id,cat_id FROM tmp_goods WHERE goods_id='{$val['id_value']}' LIMIT 1";
            $goods = $GLOBALS['db']->getRow($sql_goods);
            if($goods && $goods['goods_id'] == $val['id_value']) {
                $GLOBALS['db']->query("UPDATE tmp_comment SET cat_id='{$goods['cat_id']}' WHERE comment_id='{$val['comment_id']}' LIMIT 1"); 
            }   
        }
    }
}
echo "compelte\n";

exit;
$error = 0;
for($i=1; $i<5000; $i++) {
    if($error>3) break; 
    $limit = rand(5,10);
    $sql = "SELECT itemid,username,content,type FROM site_review_pp WHERE status=0 AND type='bridesmaid' ORDER BY RAND() LIMIT $limit";
    $result = $GLOBALS['db']->getAll($sql);
    if($result) {
        foreach($result as $k=>$val) {
            if($val['type'] == 'Wedding') {
                $category = '1,3,8,9,10,11,12';        
            }else if($val['type'] == 'Special Occasion') {
                $category = '19,18,17,22,21,20,150';    
            } else {
                $category = '13,15,14,16';        
            }
            $sql = "SELECT goods_id,cat_id FROM tmp_goods WHERE cat_id in($category) AND is_comment=0 ORDER BY RAND() LIMIT 1";
            $goods = $GLOBALS['db']->getRow($sql);
            //file_put_contents('/tmp/libaoan_20141119_2.log', "{$sql}\n", FILE_APPEND);
            if($goods) {
                $rand = rand(3,5);
                $time = time();
                $val['username'] = mysql_escape_string($val['username']);
                $val['content'] = mysql_escape_string($val['content']);
                
                $insert_sql = "INSERT INTO tmp_comment (id_value,user_name,content,comment_rank,add_time,ip_address,status) 
                                values ('{$goods['goods_id']}','{$val['username']}','{$val['content']}','$rand', '$time', '50,115,38,249', '1')";
                $GLOBALS['db']->query($insert_sql);
                
                $GLOBALS['db']->query("UPDATE tmp_goods SET is_comment=1 WHERE goods_id='{$goods['goods_id']}' LIMIT 1");
                $GLOBALS['db']->query("UPDATE site_review_pp SET status=1 WHERE itemid='{$val['itemid']}' LIMIT 1"); 
            }
                 
        }
    } else {
        $error++;   
    }
}
//file_put_contents('/tmp/libaoan_20141119_2.log', "compelte\n", FILE_APPEND);
echo "compelte\n";
