<?php
header("Content-type: text/html; charset=utf-8");

$act = $_REQUEST['act']? $_REQUEST['act']: '';

if(empty($act) || !in_array($act, array('cat', 'insert'))) {
    echo 'type is none';exit;
}

define('ROOT_PATH', substr(dirname(__FILE__),0,-6)); //根目录
define('CURR_PATH', ROOT_PATH.'spider/'); //火车头接口目录
define("ADMIN_PATH", "admin"); //admin目录 
define("ECS_ADMIN", true);
define("FREE", false);
define("IN_ECS", true);
define("DEBUG_MODE", 0);

if(isset($_SERVER['PHP_SELF'])) {
    define("PHP_SELF", $_SERVER['PHP_SELF']);
} else {
    define("PHP_SELF", $_SERVER['SCRIPT_NAME']);
}

if(file_exists(ROOT_PATH."data/config.php")){
    include_once(ROOT_PATH."data/config.php");
} else{
    include_once(ROOT_PATH."includes/config.php");
}

require(ROOT_PATH .'includes/cls_ecshop.php');
$ecs = new ECS($db_name, $prefix);
define("DATA_DIR", $ecs->data_dir());
define("IMAGE_DIR", $ecs->image_dir());

require(ROOT_PATH."includes/cls_mysql.php");
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db_host = $db_user = $db_pass = $db_name = null;

/* 获取分类列表 */
if($act == 'cat') {
    $sql = "SELECT cat_id,cat_name FROM ".$ecs->table('category')." ORDER BY cat_id";   
    $res = $db->query($sql);
    $cat = array();
    while($row = $db->fetchRow($res)) {
        $cat[$row['cat_id']] = $row['cat_name'];    
    }
    $select = "<select>\n";
    if($cat) {
        foreach($cat as $k=>$v) {
            $select .= "<option value='{$k}'>{$v}</option>\n";          
        }
        
    }
    $select .= "</select>\n";
    echo $select;exit;
}


/* curl下载设置
* 如果目标站屏蔽了图片下载，可以使用代理服务器下载，请确认设置的代理服务器是否正确
* */
define('DOWNLOAD_TYPE','http');
define('IS_PROXY', false); //是否启用代理
define('PROXY', ''); //代理服务器IP地址
define('USER_AGENT', "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; GTB6; .NET CLR 2.0.50727; CIBA)");

/*------------------------------------------------------ */
//-- 基本配置
/*------------------------------------------------------ */
$parent_id = 0;//商品发到哪个分类下 

$f = 0; //分类前面需去掉的几项
$r = 0; //分类后面需去掉的几项
$remote_image = 0; //是否外链商品大图，0为下载，1为外链
  
@ini_set("memory_limit", "64M" );
@ini_set("session.cache_expire", 180);
@ini_set("session.use_trans_sid", 0);
@ini_set("session.use_cookies", 1);
@ini_set("session.auto_start", 0);
//@ini_set("display_errors", 1);
@error_reporting(E_ALL ^ E_NOTICE);


require(ROOT_PATH . 'includes/inc_constant.php');
require(ROOT_PATH . 'includes/cls_error.php');
require(ROOT_PATH . 'includes/lib_time.php');
require(ROOT_PATH . 'includes/lib_base.php');
require(ROOT_PATH . 'includes/lib_common.php');
require(ROOT_PATH . ADMIN_PATH . '/includes/lib_main.php');
require(ROOT_PATH . ADMIN_PATH . '/includes/cls_exchange.php');
require(ROOT_PATH."languages/zh_cn/admin/goods.php");
$_CFG = load_config(); //加载全局变量
require(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/common.php'); 

/* 加载上传图片类 */
require_once(ROOT_PATH.ADMIN_PATH."/includes/lib_goods.php");
require(CURR_PATH . 'spider_func.php'); 
include_once(CURR_PATH."httpdown.php");
include_once(CURR_PATH."attribute.php");
include_once(ROOT_PATH."includes/cls_image.php");
include_once(CURR_PATH."down_image.php");
$image = new down_image($_CFG['bgcolor']);
$exc = new exchange($ecs->table("goods"), $db, "goods_id", "goods_name");

/*
$_POST = array(
    'goods_name' => '5555555566',
    'cat_id' => '1',
    'shop_price' => '140',
    'market_price' => '120',
    'goods_img' => 'http://img0.bdstatic.com/img/image/shouye/mxab-9632369186.jpg|||http://img0.bdstatic.com/img/image/shouye/mxlmh-9420812562.jpg',
    'auto_thumb' => '1',    
);
*/

/* 采集入库 */ 
if ($act == 'insert') {
    
    /*
    $goods_type = 2;
    $sql = "SELECT attr_id,attr_name,attr_values,attr_index FROM ".$ecs->table( "attribute" ). " WHERE cat_id = '".$goods_type."'";
        $res = $db->query($sql);
        $attr_list = $new_attr = array();
        while($row = $db->fetchRow($res)) {
            $attr_list[strtolower($row['attr_name'])] = $row;
            $attr_index[] = strtolower($row['attr_name']);
        }
        
        $post_attr = cj_parse_attr($_POST['attr']); //解析采集商品属性
        $seotitle = '';
        if($post_attr) {
            foreach($post_attr as $k=>$v) {
                if(in_array($k, $attr_index)) {
                    $one_attr = explode(',', $v);
                    $new_attr[$k]['attr_value'] = $one_attr[0];
                    $new_attr[$k]['attr_id'] = $attr_list[$k]['attr_id'];
                    $new_attr[$k]['attr_name'] = $attr_list[$k]['attr_name'];
                    $new_attr[$k]['attr_value_all'] = $v;
                    $seotitle .= $one_attr[0];
                }
            }
            
            //插入自定义商品属性扩展
            $compare_attr = compareattr($post_attr);
            if($compare_attr && $goods_id) {
                $sqlkey = 'goods_id,';
                $sqlval = $goods_id.',';
                foreach($compare_attr as $k=>$v) {
                    if($v) {
                        $sqlkey .= $k.',';
                        $sqlval .= "'{$v}',"; 
                    }  
                }
                $sqlkey = rtrim($sqlkey,','); 
                $sqlval = rtrim($sqlval,','); 
                $sql = "INSERT INTO ".$ecs->table("goods_attr_extend")." ($sqlkey) VALUES($sqlval)";
                //$db->query($sql);
            }
            
        }
        print_r($sql);exit;
        
        
        //插入商品属性
        if($new_attr) {
            foreach($new_attr as $k=>$v) {
                $sql = "INSERT INTO ".$ecs->table("goods_attr" )." (goods_id, attr_id,attr_value, attr_price)". " VALUES ('{$goods_id}', '{$v['attr_id']}', '{$v['attr_value']}', '0')" ;
                //$db->query($sql);
            }
        }
        
        //根据商品属性拼接商品名称
        if($seotitle) {
            $sql = "UPDATE ".$ecs->table("goods"). " SET goods_name = '".$seotitle."' WHERE goods_id = '{$goods_id}' LIMIT 1" ;
            //$db->query($sql);
        }
    
    
    exit;
    */
    
    
    //商品的扩展属性，比如像虚拟卡
    $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
    
    /* 是否处理缩略图 */
    $proc_thumb = true;

    /* 插入还是更新的标识 */
    $is_insert = $act == 'insert';

    /* 处理商品图片 */
    $php_maxsize = ini_get("upload_max_filesize");
    $htm_maxsize = "2M";
    $goods_img        = '';  // 初始化商品图片
    $goods_thumb      = '';  // 初始化商品缩略图
    $original_img     = '';  // 初始化原始图片
    $old_original_img = '';  // 初始化原始图片旧图

    $_POST['goods_img'] = trim($_POST['goods_img'], '|||'); //去掉前后的|||
    $goods_img_array = explode('|||', $_POST['goods_img']); //图片数组
    $goods_img_array = fom_array($goods_img_array);
    $goods_img_array = han_trip_same($goods_img_array);
    
    $img_descs = isset($_POST['img_desc']) && $_POST['img_desc'] ? explode("|||", $_POST['img_desc'] ) : array_fill("0",@count($goods_img_array), "");
    if (count($goods_img_array) >0) {
        $main_image = array_shift($goods_img_array);
        array_shift($img_descs);
    }
    
    while (!empty($main_image) || !$remote_image) {
        $original_img = $image->upload_image($main_image);
        if($original_img || !(count($goods_img_array) > 0 )){
            break;
        }
        $main_image = array_shift($goods_img_array);
        array_shift($img_descs);
    }
    
    if($original_img) {
        $goods_img = $original_img;
        
        /* 上传商品是否自动生成相册图 */
        if($_CFG['auto_generate_gallery']) {
            $img = $original_img;
            $pos = strpos(basename($img ), ".");
            $newname = dirname($img)."/".$image->random_filename().substr(basename($img), $pos);
            if (!copy("../".$img, "../".$newname)) {
                sys_msg("fail to copy file: ".realpath( "../".$img), 1, array(), false);
            }
            $img = $newname;
            $gallery_img = $img;
            $gallery_thumb = $img;    
        }
        if(!$proc_thumb) {
            //break;
        }else {
            if($image->gd_version() >0) {
                if($_CFG['image_width'] != 0 || $_CFG['image_height'] != 0) {
                    $goods_img = $image->make_thumb( "../".$goods_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height'] );
                    if($goods_img === FALSE) {
                        sys_msg( $image->error_msg( ), 1, array( ), FALSE );
                    }
                }
                if($_CFG['auto_generate_gallery']) {
                    $newname = dirname($img)."/".$image->random_filename().substr(basename($img), $pos);
                    if(!copy("../".$img, "../".$newname)){
                        sys_msg("fail to copy file: ".realpath("../".$img), 1, array(), false);
                    }
                    $gallery_img = $newname;
                }
                if(intval($_CFG['watermark_place'])>0 && !empty($GLOBALS['_CFG']['watermark'])) {
                    if ($image->add_watermark("../".$goods_img, "", $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === FALSE) {
                        sys_msg( $image->error_msg(), 1, array(), false);
                    }
                    if ($_CFG['auto_generate_gallery'] && $image->add_watermark("../".$gallery_img, "", $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === FALSE) {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
                if($_CFG['auto_generate_gallery'] && ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)) {
                    $gallery_thumb = $image->make_thumb("../".$img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                    if($gallery_thumb === FALSE) {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
            }
        }
        
        if(!$proc_thumb && !isset($_POST['auto_thumb']) && empty($original_img)) {
            //break;
        }
        
        if($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)
        {
            $goods_thumb = $image->make_thumb("../".$original_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
            if ( $goods_thumb === FALSE ) {
                sys_msg($image->error_msg(), 1, array(), false);
            }
                
        } else {
            $goods_thumb = $original_img;
        }
          
    }
    
    /* 如果没有输入商品货号则自动生成一个商品货号 */
    if (empty($_POST['goods_sn'])) {
        $max_id = $is_insert ? $db->getOne("SELECT MAX(goods_id) + 1 FROM ".$ecs->table('goods')) : $_REQUEST['goods_id'];
        $goods_sn = generate_goods_sn($max_id);
    } else {
        $goods_sn = $_POST['goods_sn'];
    }

    /* 处理商品数据 */
    $catgory_id  = !empty($_POST['categories']) ? intval($_POST['categories']) : 1;
    $goods_type  = !empty($_POST['goods_type']) ? intval($_POST['goods_type']) : 2;
    $shop_price = !empty($_POST['shop_price']) ? $_POST['shop_price'] : 0;
    $market_price = !empty($_POST['market_price']) ? $_POST['market_price'] : 0;
    $goods_name = !empty($_POST['goods_name']) ? htmlspecialchars(trim($_POST['goods_name'])) : '';
    $goods_desc = !empty($_POST['goods_desc']) ? htmlspecialchars(trim($_POST['goods_desc'])) : '';
    $keywords = !empty($_POST['keywords']) ? htmlspecialchars(trim($_POST['keywords'])) : '';
    $goods_brief = !empty($_POST['goods_brief']) ? htmlspecialchars(trim($_POST['goods_brief'])) : '';
    $is_best = isset($_POST['is_best']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_on_sale = $_POST['is_on_sale'] ? $_POST['is_on_sale'] : 0;
    $is_alone_sale = $_POST['is_alone_sale'] ? $_POST['is_alone_sale'] : 0;
    $is_shipping = $_POST['is_shipping'] ? $_POST['is_shipping'] : 0;
    $source_url = $_POST['source_url'] ? $_POST['source_url'] : '';
    $goods_number = isset($_POST['goods_number']) ? $_POST['goods_number'] : 100;
    $warn_number = isset($_POST['warn_number']) ? $_POST['warn_number'] : 0;
    $suppliers_id = isset($_POST['suppliers_id']) ? intval($_POST['suppliers_id'] ) : "0";
    $goods_img = empty($goods_img) && !empty($main_image) || goods_parse_url($main_image) && $remote_image ? htmlspecialchars(trim($main_image)) : $goods_img;
    $goods_thumb = empty($goods_thumb) && !empty($_POST['goods_thumb']) || goods_parse_url($_POST['goods_thumb']) && $remote_image ? htmlspecialchars(trim($_POST['goods_thumb'])) : $goods_thumb;
    $goods_thumb = empty($goods_thumb) && isset($_POST['auto_thumb']) ? $goods_img : $goods_thumb;
        
    /* 入库 */
    if($is_insert) {

        $data = array(
            'goods_name' => $goods_name, //商品名称
            'goods_sn' => $goods_sn, //商品货号
            'cat_id' => $catgory_id, //商品分类
            'shop_price' => $shop_price, //本店售价
            'market_price' => $market_price, //市场售价
            'goods_img' => $goods_img, //上传商品图片
            'goods_thumb' => $goods_thumb, //上传商品缩略图
            'goods_desc' => $goods_desc, //详情描述
            'is_best' => $is_best, //精品--加入推荐
            'is_new' => $is_new, //新品--加入推荐
            'is_hot' => $is_hot, //热销--加入推荐
            'is_on_sale' => $is_on_sale, //上架
            'is_alone_sale' => $is_alone_sale, //能作为普通商品销售
            'is_shipping' => $is_shipping, //商品不会产生运费花销
            'keywords' => $keywords, //商品关键词
            'goods_brief' => $goods_brief, //商品简单描述
            'goods_type' => $goods_type, //商品类型
            'goods_number' => $goods_number, //商品库存数量
            'source_url' => $source_url, //来源网址
            'add_time' => time(), 
            'last_update' => time(), 
        );
        
        $sqlkey = $sqlval = '';
        foreach($data as $k=>$v) {
            $sqlkey .= $k.',';
            $sqlval .= "'{$v}',";   
        }
        $sqlkey = rtrim($sqlkey,','); 
        $sqlval = rtrim($sqlval,','); 
        $sql = "INSERT INTO ".$ecs->table("goods")." ($sqlkey) VALUES($sqlval)";
        $db->query($sql);
    } 
    

    /* 商品编号 */
    $goods_id = $is_insert ? $db->insert_id() : '';
    $randcolor = randcolor();
     //处理商品属性
    if($goods_id && isset($_POST['attr']) && $_POST['attr']) {
        
        $sql = "SELECT attr_id,attr_name,attr_values,attr_index FROM ".$ecs->table( "attribute" ). " WHERE cat_id = '".$goods_type."'";
        $res = $db->query($sql);
        $attr_list = $new_attr = array();
        while($row = $db->fetchRow($res)) {
            $attr_list[strtolower($row['attr_name'])] = $row;
            $attr_index[] = strtolower($row['attr_name']);
        }
        
        $post_attr = cj_parse_attr($_POST['attr']); //解析采集商品属性
        
        //替换属性 属性名称要改成小写
        $post_attr = replaceparams($post_attr, array('embellishment'=>'embellishments'));
        if($post_attr) {
            foreach($post_attr as $k=>$v) {
                if(in_array($k, $attr_index)) {
                    $one_attr = explode(',', $v);
                    $new_attr[$k]['attr_value'] = $one_attr[0];
                    $new_attr[$k]['attr_id'] = $attr_list[$k]['attr_id'];
                    $new_attr[$k]['attr_name'] = $attr_list[$k]['attr_name'];
                    $new_attr[$k]['attr_value_all'] = $v;
                }
            }
            
            //插入自定义商品属性扩展
            $compare_attr = compareattr($post_attr);
            if($compare_attr) {
                $sqlkey = 'goods_id,';
                $sqlval = $goods_id.',';
                
                $compare_attr['color'] = $randcolor; //把color随机取2个
                foreach($compare_attr as $k=>$v) {
                    if($v) {
                        $sqlkey .= $k.',';
                        $sqlval .= "'{$v}',"; 
                    }  
                }
                $sqlkey = rtrim($sqlkey,','); 
                $sqlval = rtrim($sqlval,','); 
                $sql = "INSERT INTO ".$ecs->table("goods_attr_extend")." ($sqlkey) VALUES($sqlval)";
                $db->query($sql);
            }
            
        }
        
        //插入商品属性和自定义商品属性扩展
        if($new_attr) {
            foreach($new_attr as $k=>$v) {
                $sql = "INSERT INTO ".$ecs->table("goods_attr" )." (goods_id, attr_id,attr_value, attr_price)". " VALUES ('{$goods_id}', '{$v['attr_id']}', '{$v['attr_value']}', '0')" ;
                $db->query($sql);
            }
        }
        
        //根据商品属性拼接商品名称
        $seotitle = $seokeyword = '';
        $seotitleattr = array('Silhouette','Color','Hemline/Train','Embellishments','Neckline','Back Details','Fabric','Sleeve Length','Body Shape','Occasion','Shown Color','Celebrity Style','Trend');
        $new_attr['color']['attr_value'] = $randcolor; //把color随机取2个
        foreach($seotitleattr as $v) {
            $att_v = $new_attr[strtolower($v)]['attr_value'];
            if($att_v && strtolower(trim($att_v)) != 'no') {
                $tmp_attr = explode(',', $new_attr[strtolower($v)]['attr_value_all']);
                if(count($tmp_attr)>1) {
                    $rand_attr = array_rand($tmp_attr,1);
                    $seotitle .= $tmp_attr[$rand_attr].' ';    
                }else {
                    $seotitle .= $new_attr[strtolower($v)]['attr_value'].' ';     
                } 
            }    
        }
        if($seotitle && !empty($_POST['attr'])) {
            $seotitle = filterstr(trim($seotitle));
            $seokeyword = $seotitle;
            $sql = "UPDATE ".$ecs->table("goods"). " SET goods_name = '{$seotitle}',keywords='{$seokeyword}' WHERE goods_id = '{$goods_id}' LIMIT 1" ;
            $db->query($sql);
        }
    }
    
    
    /* 处理会员价格 */   
    if(isset($_POST['user_rank'], $_POST['user_price'])) {
        handle_member_price( $goods_id, $_POST['user_rank'], $_POST['user_price'] );
    }
    
     /* 处理优惠价格 */  
    if(isset($_POST['volume_number'], $_POST['volume_price'])) {
        $temp_num = array_count_values( $_POST['volume_number'] );
        foreach($temp_num as $v) {
            if(!($v >1)) {
                continue;
            }
            break;
        }
        handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
    }
    
    /* 处理扩展分类 */   
    if(isset( $_POST['other_cat'])) {
        handle_other_cat( $goods_id, array_unique( $_POST['other_cat']));
    }
    
    
    if($is_insert) {
        /* 处理关联商品 */
        handle_link_goods($goods_id); 
        
        /* 处理组合商品 */    
        handle_group_goods($goods_id);
        
        /* 处理关联文章 */
        handle_goods_article($goods_id);
    }
    
    /* 重新格式化图片名称 */
    $original_img = reformat_image_name("goods", $goods_id, $original_img, "source");
    $goods_img = reformat_image_name("goods", $goods_id, $goods_img, "goods" );
    $goods_thumb = reformat_image_name("goods_thumb", $goods_id, $goods_thumb, "thumb");
    if($goods_img !== FALSE) {
        $db->query("UPDATE ".$ecs->table("goods"). " SET goods_img = '".$goods_img."' WHERE goods_id='{$goods_id}'");
    }
    if($original_img !== FALSE) {
        $db->query("UPDATE ".$ecs->table("goods"). " SET original_img = '".$original_img."' WHERE goods_id='{$goods_id}'");
    }
    if($goods_thumb !== FALSE) {
        $db->query("UPDATE ".$ecs->table("goods"). " SET goods_thumb = '".$goods_thumb."' WHERE goods_id='{$goods_id}'") ;
    }
    
    /* 如果有图片，把商品图片加入图片相册 */
    if(isset($img)) {
        /* 重新格式化图片名称 */ 
        $img = reformat_image_name( "gallery", $goods_id, $img, "source");
        $gallery_img = reformat_image_name("gallery", $goods_id, $gallery_img, "goods");
        $gallery_thumb = reformat_image_name("gallery_thumb", $goods_id, $gallery_thumb, "thumb");
        $sql = "INSERT INTO ".$ecs->table("goods_gallery")." (goods_id, img_url, img_desc, thumb_url, img_original) ". " VALUES ('".$goods_id."', '{$gallery_img}', '', '{$gallery_thumb}', '{$img}')" ;
        $db->query($sql);
    }
    
    if($goods_img_array) {
        /* 处理相册图片 */  
        add_gallery_image($goods_id, $goods_img_array, $img_descs);
    }
    
    /* 编辑时处理相册图片描述 */
    if(!$is_insert || isset( $_POST['old_img_desc'])) {
        foreach($GLOBALS['_POST']['old_img_desc'] as $img_id => $img_desc) {
            $sql = "UPDATE ".$ecs->table("goods_gallery"). " SET img_desc = '".$img_desc."' WHERE img_id = '{$img_id}' LIMIT 1" ;
            $db->query($sql);
        }
    }
    
    /* 不保留商品原图的时候删除原图 */
    if($proc_thumb && !$_CFG['retain_original_img'] && !empty($original_img)) {
            $db->query("UPDATE ".$ecs->table("goods"). " SET original_img='' WHERE `goods_id`='".$goods_id."'") ;
            $db->query("UPDATE ".$ecs->table("goods_gallery"). " SET img_original='' WHERE `goods_id`='".$goods_id."'") ;
            @unlink("../".$original_img);
            @unlink("../".$img );
    }
    /* 清空缓存 */ 
    clear_cache_files();
    
    echo "添加商品成功";

}


