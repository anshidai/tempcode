<?php 

/**
* 更新每天放出atoz关键词
*/

define('IN_ECS', true);
set_time_limit(0);
$sapi = php_sapi_name();
if($sapi != 'cli') {
    die('只能命令行执行');
}

require(dirname(__FILE__).'/../includes/init.php');


$total = $GLOBALS['db']->getOne("SELECT count(*) FROM atoz_query_info");

$pagesize = 5000;

$num = ceil($total/$pagesize);

for($i=1;$i<$num; $i++) {
    
    $sql = "SELECT * FROM atoz_query_info ORDER BY qid LIMIT ".($i-1)*$pagesize.','.$pagesize;
    $result = $GLOBALS['db']->getAll($sql);
    foreach($result as $k=>$val) {
        $val['query_url'] = str_replace('_', '-', $val['query_url']);
        $insert = "INSERT INTO tmp_keywords_detail (qid,query_url,query_str,related_product_id_list,recomm_product_list)
                values('{$val['qid']}', '{$val['query_url']}', '{$val['query_str']}', '{$val['related_product_id_list']}', '{$val['recomm_product_list']}')";
        $GLOBALS['db']->query($insert);        
    }    
}

echo "compelte\n";

exit;


$maxid = $GLOBALS['db']->getOne("SELECT max(qid) FROM ".$GLOBALS['ecs']->table('keywords_detail')." WHERE status=1");
$rand = rand(200, 300);

$sql = "SELECT qid,status FROM ".$GLOBALS['ecs']->table('keywords_detail')." WHERE status=0 AND qid>{$maxid} ORDER BY qid LIMIT {$rand}";
$result = $GLOBALS['db']->getAll($sql);
if($result) {
    foreach($result as $k=>$val) {
        $qids[] = $val['qid'];   
    }
    
    if(isset($qids) && !empty($qids)) {
        $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('keywords_detail')." SET status=1 WHERE qid in(".implode(',', $qids).")");     

        $best_max = $GLOBALS['db']->getOne("SELECT max(qid) FROM ".$GLOBALS['ecs']->table('keywords_detail')." WHERE status=1");
        for($i=0; $i<count($qids); $i++) {
            $multi_qid = multi_rand(1, $best_max, 20);
            $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('keywords_detail')." SET rand_qid_list='".implode(',', $multi_qid)."' WHERE qid='{$qids[$i]}'");      
        } 
    } 
}

echo "compelte\n";


//取多个不重复的随机数
function multi_rand($begin, $end, $count)
{
    $rand_array = array();
    if($count>($end - $begin + 1)) {
        $count = ($end - $begin + 1);
    }
    for($i = 0;$i < $count; $i++ ) {
        $is_ok = false;
        $num = 0;
        while(!$is_ok){
            $num = rand($begin,$end);
            $is_out = 1;
            foreach($rand_array as $v) {
                if($v == $num ) {
                    $is_ok = false;
                    $is_out = 0;
                    break;
                }
            }
            if($is_out == 1) {
                $is_ok = true;
            }
        }
        $rand_array[] = $num;
    }
    return $rand_array;
}