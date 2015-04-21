<?php

/**
 * ECSHOP 支付响应页面
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: respond.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

$smarty->assign('categories',      get_categories_tree()); // 分类树

/* 支付方式代码 */
$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';

//订单号
$order_sn = !empty($_REQUEST['sn']) ? trim(intval($_REQUEST['sn'])) : '';
if($order_sn) {
    $order_tmp = order_info(0, $order_sn);    
    if($order_tmp) {
        $order_goods = order_goods($order_tmp['order_id']);
        $order_goods = $order_goods[0];
        
        $host_url = $_SERVER['HTTP_HOST'];
        if(strpos($host_url, 'http://') === false) {
           $host_url = 'http://'.$host_url;  
        }
        $order_info['url'] = $host_url.'/'.build_uri('goods', array('gid'=>$order_goods['goods_id']), $order_goods['goods_name']);
        $order_info['date'] = date('Y/m/d');
        $order_info['order_sn'] = $order_tmp['order_sn'];
        $order_info['goods_amount'] = $order_tmp['goods_amount'];
        $smarty->assign('order_info', $order_info);
    }
   
   
}


//获取首信支付方式
if (empty($pay_code) && !empty($_REQUEST['v_pmode']) && !empty($_REQUEST['v_pstring']))
{
    $pay_code = 'cappay';
}

//获取快钱神州行支付方式
if (empty($pay_code) && ($_REQUEST['ext1'] == 'shenzhou') && ($_REQUEST['ext2'] == 'ecshop'))
{
    $pay_code = 'shenzhou';
}

//获取钱宝支付方式(开始)
if ($_REQUEST['code'] == 'creditcard' && $_REQUEST['success'] == '1')
{
    $msg = $_LANG['pay_success'];
    assign_template();
    $position = assign_ur_here();
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置
    $smarty->assign('helps',      get_shop_help());      // 网店帮助

    $smarty->assign('message',    $msg);
    $smarty->assign('shop_url',   $ecs->url());

    $smarty->display('respond_success.dwt');
    exit();
}elseif($_REQUEST['code'] == 'creditcard' && $_REQUEST['success'] == '0'){
    $msg = $_LANG['pay_fail'];
    assign_template();
    $position = assign_ur_here();
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置
    $smarty->assign('page_title', $position['title']);   // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']); // 当前位置
    $smarty->assign('helps',      get_shop_help());      // 网店帮助

    $smarty->assign('message',    $msg);
    $smarty->assign('shop_url',   $ecs->url());

    $smarty->display('respond_failed.dwt');
    exit();
}
//获取钱宝支付方式(结束)


/* 参数是否为空 */
if (empty($pay_code))
{
    $msg = $_LANG['pay_not_exist'];
}
else
{
    /* 检查code里面有没有问号 */
    if (strpos($pay_code, '?') !== false)
    {
        $arr1 = explode('?', $pay_code);
        $arr2 = explode('=', $arr1[1]);

        $_REQUEST['code']   = $arr1[0];
        $_REQUEST[$arr2[0]] = $arr2[1];
        $_GET['code']       = $arr1[0];
        $_GET[$arr2[0]]     = $arr2[1];
        $pay_code           = $arr1[0];
    }

    /* 判断是否启用 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
    if ($db->getOne($sql) == 0)
    {
        $msg = $_LANG['pay_disabled'];
    }
    else
    {
        $plugin_file = 'includes/modules/payment/' . $pay_code . '.php';

        /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
        if (file_exists($plugin_file))
        {
            /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
            include_once($plugin_file);

            $payment = new $pay_code();
            $msg     = (@$payment->respond()) ? $_LANG['pay_success'] : $_LANG['pay_fail'];
        }
        else
        {
            $msg = $_LANG['pay_not_exist'];
        }
    }
}

assign_template();
$position = assign_ur_here();
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('helps',      get_shop_help());      // 网店帮助

$smarty->assign('message',    $msg);
$smarty->assign('shop_url',   $ecs->url());

$smarty->display('respond.dwt');

?>