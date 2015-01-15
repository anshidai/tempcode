<?php

/**
* 关键词内链设置管理
* ============================================================================
* 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
* 网站地址: http://www.ecshop.com；
* ----------------------------------------------------------------------------
* 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
* 使用；不允许对程序代码以任何形式任何目的的再发布。
* ============================================================================
* $Author: yoja365.com $
* $Id: content_key.php 17063 2010-03-25 06:35:46Z yoja365.com $
*/

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_goods.php');


/*------------------------------------------------------ */
//-- 关键词 列表页
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
/* 检查权限 */
admin_priv('content_key');

/* 模板赋值 */
$smarty->assign('full_page',   1);
$smarty->assign('ur_here',     '关键词列表');
$smarty->assign('action_link', array('href' => 'content_key.php?act=add', 'text' => '添加关键词'));

$ckey_list = content_key_list();
$smarty->assign('ckey_list',   $ckey_list['content']);
$smarty->assign('filter',       $ckey_list['filter']);
$smarty->assign('record_count', $ckey_list['record_count']);
$smarty->assign('page_count',   $ckey_list['page_count']);

assign_query_info();
$smarty->display('content_key_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
check_authz_json('article_manage');

$ckey_list = content_key_list();

$smarty->assign('ckey_list',    $ckey_list['content']);
$smarty->assign('filter',          $ckey_list['filter']);
$smarty->assign('record_count',    $ckey_list['record_count']);
$smarty->assign('page_count',      $ckey_list['page_count']);

$sort_flag  = sort_flag($ckey_list['filter']);
$smarty->assign($sort_flag['tag'], $sort_flag['img']);

make_json_result($smarty->fetch('content_key_list.htm'), '',
array('filter' => $ckey_list['filter'], 'page_count' => $ckey_list['page_count']));
}

/*------------------------------------------------------ */
//-- 关键词 增加与修改
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add')
{
/* 检查权限 */
admin_priv('content_key');

/* 模板赋值 */
$smarty->assign('ur_here',      "添加关键词");
$smarty->assign('action_link',  array('href' => 'content_key.php?act=list', 'text' => '关键词列表'));
$smarty->assign('form_act',     'insert');

/* 显示页面 */
assign_query_info();

$smarty->display('content_key_info.htm');

}

/*------------------------------------------------------ */
//-- 关键词添加时的处理
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
/* 权限检查 */
admin_priv('content_key');

/* 初始化变量 */
$conkey['key_id']       = !empty($_POST['key_id'])       ? intval($_POST['key_id'])     : 0;
$conkey['sort_order']   = !empty($_POST['sort_order'])   ? intval($_POST['sort_order']) : 0;
$conkey['key_name']     = !empty($_POST['key_name'])     ? trim($_POST['key_name'])     : '';
$conkey['key_url']     = !empty($_POST['key_url'])     ? sanitize_url($_POST['key_url'])     : '';
$conkey['replace_num']     = !empty($_POST['replace_num'])     ? intval($_POST['replace_num'])  : 0;

if (content_key_exists($conkey['key_name']))
{
/* 不能有重复的关键词 */
$link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
sys_msg($_LANG['catname_exist'], 0, $link);
}

/* 入库的操作 */
if ($db->autoExecute($ecs->table('content_key'), $conkey) !== false)
{

clear_cache_files();    // 清除缓存

/*添加链接*/
$link[0]['text'] = '继续添加';
$link[0]['href'] = 'content_key.php?act=add';

$link[1]['text'] = '分类列表';
$link[1]['href'] = 'content_key.php?act=list';

sys_msg('关键词添加成功', 0, $link);
}
}

/*------------------------------------------------------ */
//-- 编辑关键词信息
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
$key_id = intval($_REQUEST['key_id']);
$conkey_info = get_content_key($key_id);  // 查询分类信息数据
$smarty->assign('ur_here',     "关键词修改");
$smarty->assign('action_link', array('text' => "关键词列表", 'href' => 'content_key.php?act=list'));

$smarty->assign('conkey_info',    $conkey_info);
$smarty->assign('form_act',    'update');

/* 显示页面 */
assign_query_info();
$smarty->display('content_key_info.htm');
}

/*------------------------------------------------------ */
//-- 更新关键词信息
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'update')
{
/* 初始化变量 */
$key_id              = !empty($_POST['key_id'])       ? intval($_POST['key_id'])     : 0;
$old_key_name        = $_POST['old_key_name'];
$conkey['sort_order']   = !empty($_POST['sort_order'])   ? intval($_POST['sort_order']) : 0;
$conkey['key_name']     = !empty($_POST['key_name'])     ? trim($_POST['key_name'])     : '';
$conkey['key_url']     = !empty($_POST['key_url'])     ? sanitize_url($_POST['key_url'])  : '';
$conkey['replace_num']     = !empty($_POST['replace_num'])     ? intval($_POST['replace_num'])  : 0;

/* 判断关键词是否重复 */

if ($conkey['key_name'] != $old_key_name)
{
if (content_key_exists($conkey['key_name']))
{
$link[] = array('text' => "返回", 'href' => 'javascript:history.back(-1)');
sys_msg('该关键词已经存在', 0, $link);
}
}

if ($db->autoExecute($ecs->table('content_key'), $conkey, 'UPDATE', "key_id='$key_id'"))
{
/* 更新关键词信息成功 */
clear_cache_files(); // 清除缓存

/* 提示信息 */
$link[] = array('text' => "返回列表页", 'href' => 'content_key.php?act=list');
sys_msg("关键词修改成功", 0, $link);
}

}

/*------------------------------------------------------ */
//-- 删除关键词
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'remove')
{

/* 初始化关键词ID并取得关键词名称 */
$key_id   = intval($_GET['key_id']);


/* 删除分类 */
$sql = 'DELETE FROM ' .$ecs->table('content_key'). " WHERE key_id = '$key_id' ";
$db->query($sql);

//清除缓存

$url = 'content_key.php?act=list&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
ecs_header("Location: $url\n");
exit;
}



/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
* 获取关键词列表
*
* @access  public
* @return  array
*/
function content_key_list()
{
$result = get_filter();
if ($result === false)
{
/* 分页大小 */
$filter = array();

/* 记录总数以及页数 */
$sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('content_key');
$filter['record_count'] = $GLOBALS['db']->getOne($sql);

$filter = page_and_size($filter);

/* 查询记录 */
$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('content_key')." ORDER BY sort_order ASC";
set_filter($filter, $sql);
}
else
{
$sql    = $result['sql'];
$filter = $result['filter'];
}

$res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

$arr = array();
while ($rows = $GLOBALS['db']->fetchRow($res))
{
$arr[] = $rows;
}

return array('content' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
* 检查关键词是否已经存在
*
* @param   string      $key_name       关键词名称
*
*	@return  boolean
*/
function content_key_exists($key_name)
{
$sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('content_key').
" WHERE  key_name = '$key_name' ";
return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;

}

/**
* 获得某个关键词的所有信息
*
* @param   integer     $key_id     指定的关键词ID
* @return  mix
*/
function get_content_key($key_id)
{
$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('content_key'). " WHERE key_id='$key_id' LIMIT 1";
return $GLOBALS['db']->getRow($sql);
}
?>