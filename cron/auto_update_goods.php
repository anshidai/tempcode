<?php 
/**
* /home/wwwroot/temperqueen.com/cron/auto_update_goods.php
* 定时审核产品
* 更新规则：
* 时间：0-24时随机更新
* 数量：30-60/天（每天随机数量，在30-60之间）
* 更新规则:没有目录限制，所有未审核的数据都可以更新；
*/

define('IN_ECS', true);
set_time_limit(0);
$sapi = php_sapi_name();
if($sapi != 'cli') {
    die('只能命令行执行');
}

require(dirname(__FILE__).'/../includes/init.php');

$nextlog = dirname(__FILE__) . '/log.txt';
$log = dirname(__FILE__) . '/cron.log';

$date = getfile_log(); //获取本次执行的具体日期

$today = date('Ymd'); 
$h = date('H');

//每天只执行一次，根据小时执行
if($date == 'first' || ($today == $date['date'] && $h == $date['hours']) ) {
    $rand_total = rand(100, 150);  //随机审核商品数量
    $sql = "UPDATE ".$GLOBALS['ecs']->table('goods')." SET is_on_sale=1 WHERE is_delete=0 AND is_on_sale=0 ORDER BY goods_id LIMIT $rand_total";
    $GLOBALS['db']->query($sql);
    putfile_log($sql);       
}

function getfile_log()
{
    global $nextlog;
    
    if(!file_exists($nextlog)) {
        $rand_time = rand(1, 20);
        $res = array(
            'date' => date('Ymd', strtotime('1 day')),
            'hours' => sprintf("%02d", $rand_time),
        );
        file_put_contents($nextlog, $res['date'] . "\t" . $res['hours']);
        return 'first';
             
    }else {
        list($res['date'], $res['hours']) = explode("\t", file_get_contents($nextlog));
        return $res;
    }  
}

function putfile_log($msg = '')
{
    global $nextlog,$log;
    $rand_time = rand(1, 20);
    $res = array(
        'date' => date('Ymd', strtotime('1 day')),
        'hours' => sprintf("%02d", $rand_time),
    );
    file_put_contents($nextlog, $res['date'] . "\t" . $res['hours']);

    //记录日志
    file_put_contents($log, date('Y-m-d H:i:s')."===".$msg."\n", FILE_APPEND);
}






