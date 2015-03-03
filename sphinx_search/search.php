<?php

/**
 * Sphinx 搜索程序
 * $Author: libaoan
*/


define('IN_ECS', true);

$keywords = trim($_REQUEST['k'])? htmlspecialchars(trim($_REQUEST['k'])): '';
$page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;

$sort = $_GET['sort']? htmlspecialchars($_GET['sort']): 'time';
$order = $_GET['order']? htmlspecialchars($_GET['order']): 'desc';

if($sort == 'price') {
    if($order == 'desc') {
        $orderString = 'shop_price DESC';    
    }else {
        $orderString = 'shop_price ASC'; 
    }     
}elseif($sort == 'time') {
    if($order == 'desc') {
        $orderString = 'add_time DESC';    
    }else {
        $orderString = 'add_time ASC'; 
    }     
}

$pagesize = 20;

if(empty($keywords)) {
    //header("Location: index.php\n");
    //exit;
}

require '../includes/init.php';
require 'sphinxapi.php';

//ini_set('display_errors', 1);
error_reporting(0);

$sphinx = new SphinxClient(); 

$sphinx->setServer('127.0.0.1', 9312); #sphinx的主机名和端口

$sphinx->SetArrayResult(true); #设置返回结果集为php数组格式
//$sphinx->SetMatchMode(SPH_MATCH_ALL);
$sphinx->SetLimits(($page-1)*$pagesize, $pagesize, 1000); #匹配结果的偏移量，参数的意义依次为：起始位置，返回结果条数，最大匹配条数
$sphinx->SetMaxQueryTime(10); #最大搜索时间
 
if(isset($orderString) && $orderString) {
    $sphinx->SetSortMode(SPH_SORT_EXTENDED, $orderString); 
}

//索引源是配置文件中的 index 类，如果有多个索引源可使用,号隔开：'email,diary' 或者使用'*'号代表全部索引源
//$sphinx->query('搜索词', '索引源名称');
$res = $sphinx->query($keywords, 'goods_index');
//$res = $sphinx->query('Sleeveless', 'goods_index');

if(isset($_GET['debug'])) {
    echo '<pre>';
    var_dump($res);
    
}

$goods_list = array();
$record_count = $res['total_found']? $res['total_found']: 0;
if($record_count) {
    foreach($res['matches'] as $k=>$val) {
        $row['goods_id'] = $val['id']; 
        $row['weight'] = $val['weight'];
        $row['cat_id'] = $val['attrs']['cat_id'];
        $row['url'] = $val['attrs']['cat_id'];
        $row['goods_name'] = $val['attrs']['goods_name'];
        $row['name'] = $val['attrs']['goods_name'];
        $row['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['name']);   
        $row['goods_thumb'] = $siteurl.'/'.$val['attrs']['goods_thumb'];   
        $row['add_time'] = $val['attrs']['add_time'];
        $row['market_price'] = $val['attrs']['market_price'];
        $row['shop_price'] = $val['attrs']['shop_price'];
        $row['click_count'] = $val['click_count']? $val['click_count']: 0;
        $row['sale_num'] = intval((1-$row['shop_price']/$row['market_price'])*100);
        $row['comment_count']   = $comment_count? $comment_count: 0;
        $goods_list[] = $row;   
    }
    
    //分页
    $pager = get_pager("http://search.temperqueen.com/search.php?k={$keywords}&", array('sort'=>$sort, 'order'=>$order), $record_count, $page, $pagesize);
    $smarty->assign('pager', $pager);
}


$smarty->assign('categories', get_categories_tree()); // 分类树     
$smarty->assign('goods_list', $goods_list);     
$smarty->assign('record_count', $record_count);             
$smarty->assign('kwd', $keywords);        
$smarty->assign('page', $page);        
$smarty->assign('order', $order);        
$smarty->assign('sort', $sort);        
$smarty->display('sphinx_search.dwt');

exit;

