<?php

/**
 * 由atoz程序生产的 关键词关联商品
 * $Author: libaoan 2015-01-17$
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$query_url = isset($_REQUEST['url'])  ? htmlspecialchars(trim($_REQUEST['url'])) : '';
if(empty($query_url)) {
    die('no goods');
}

$cache_id = $query_url . '-' . $_SESSION['user_rank'].'-'.$_CFG['lang'];
$cache_id = sprintf('%X', crc32($cache_id));

if(!$smarty->is_cached('keyword_detail.dwt', $cache_id)) {
    $host = $_SERVER['HTTP_HOST'];
    if(!strpos('http://', $host)) {
        $host = 'http://'.$host;    
    }

    $show_limit = 15; //显示多少条记录

    $list['info'] = $db->getRow("SELECT * FROM ".$ecs->table('keywords_detail')." WHERE query_url='{$query_url}' LIMIT 1");
    $all_goods = $list['info']['related_product_id_list'];
    if($all_goods) {
        //获取随机的关键词
        if($list['info']['rand_qid_list']) {
            $sql = "SELECT qid,query_url,query_str FROM ".$ecs->table('keywords_detail')." 
                WHERE qid in({$list['info']['rand_qid_list']}) LIMIT 20";    
            $rand_list = $db->getAll($sql);
            foreach($rand_list as $k=>$val) {
                $val['url'] = $host."/product-detail/{$val['query_url']}.html";
                $list['rand_list'][$val['qid']] = $val;
            }    
        }
        
        $relategoods = explode(',', trim($all_goods, ','));
        $relategoods = implode(',', array_slice($relategoods, 0, $show_limit));

        $sql = "SELECT goods_id,goods_name,market_price,shop_price,goods_thumb,goods_img,original_img 
                FROM ".$ecs->table('goods')." 
                WHERE goods_id in({$relategoods}) ORDER BY goods_id DESC";    
        $list['list'] = $db->getAll($sql);
        
        //获取商品的属性-值
        $sql = "SELECT a.goods_id,b.attr_id,b.attr_name,a.attr_value 
                FROM ".$ecs->table('goods_attr')." a LEFT JOIN ".$ecs->table('attribute')." b 
                ON a.attr_id=b.attr_id WHERE a.goods_id in({$relategoods})";    
        $attr_tmp = $db->getAll($sql);
        if($attr_tmp) {
            foreach($attr_tmp as $k=>$val) {
                $row_attr['goods_id'] = $val['goods_id'];
                $row_attr['attr_id'] = $val['attr_id'];
                $row_attr['attr_name'] = $val['attr_name'];
                $row_attr['attr_value'] = $val['attr_value'];
                $attr_list[$val['goods_id']][$val['attr_id']] = $row_attr;
            }
        }
        
        //获取商品图片
        $sql = "SELECT img_id,goods_id,img_url,thumb_url,img_original 
                FROM ".$ecs->table('goods_gallery')." WHERE goods_id in({$relategoods})";    
        $img_tmp = $db->getAll($sql);
        if($img_tmp) {
            foreach($img_tmp as $k=>$val) {
                 $row_img['goods_id'] = $val['goods_id'];
                 $row_img['img_url'] = $host.'/'.$val['img_url'];
                 $row_img['thumb_url'] = $host.'/'.$val['thumb_url'];
                 $row_img['img_original'] = $host.'/'.$val['img_original'];
                 $img_list[$val['goods_id']][$val['img_id']] = $row_img;
            }    
        }

        //获取评论数
        
        
        //数据组合
        if($list['list']) {
            foreach($list['list'] as $k=>$val) {
                //goods_thumb,goods_img,original_img
                $list['list'][$k]['goods_thumb'] = $host.'/'.$val['goods_thumb'];
                $list['list'][$k]['goods_img'] = $host.'/'.$val['goods_img'];
                $list['list'][$k]['original_img'] = $host.'/'.$val['original_img'];
                $list['list'][$k]['url'] = $host.'/'.build_uri('goods', array('gid'=>$val['goods_id']), $val['goods_name']);
                $list['list'][$k]['attr_list'] = $attr_list[$val['goods_id']];        
                $list['list'][$k]['img_list'] = array_slice($img_list[$val['goods_id']],0, 5);
                
                $list['comment_list'][$val['goods_id']] = get_goods_comment($val['goods_id']);
                $list['list'][$k]['comment_total'] = $list['comment_list'][$val['goods_id']]? count($list['comment_list'][$val['goods_id']]): 0;
                        
            }
        }
        $list['desc_content'] = get_rand_desc($list['info']['query_str']); //获取随机描述
        
        unset($attr_tmp,$img_tmp);   
    }
}

//echo '<pre>';
//var_dump($list['list']);
$smarty->assign('list', $list);
$smarty->assign('categories', get_categories_tree());  // 分类树
$smarty->display('keyword_detail.dwt');


function get_rand_desc($keyword)
{
    $desc[0] = 'Temperqueen provided with {keyword} the most detailed product information, including commodity prices, parameter, picture, color, size, etc., Temperqueen site offers and {keyword} the most relevant product information, see {keyword} information more accurately, on temperqueen.com. ';
    $desc[1] = 'Temperqueen provides {keyword} relevant information, contains {keyword} prices, {keyword} images, {keyword} size, and so on, also including the detail information of choosing, buy {keyword} on temperqueen.com! ';
    $desc[2] = 'The most comprehensive dress site Temperqueen offers {keyword}, {keyword} pictures, {keyword} size, {keyword} price of product information to consumers, learn more about {keyword} quotation, {keyword} on temperqueen.com offers mall brands and other related information, please attention. ';
    $desc[3] = 'Temperqueen online sales {keyword} and other wedding dresses, Temperqueen website for you to purchase the {keyword} {keyword} your current quotation, {keyword} price, promotion, parameters, evaluation and ranking, pictures and other information of choosing and buying, buy and see the latest and most wedding dresses goods on temperqueen.com. ';
    $desc[4] = 'Temperqueen website provides all kinds of {keyword}, mainly to bud silk, silk, chiffon, satin and other types of {keyword}, Temperqueen different roles of different ages for different occasions {keyword} have made comprehensive explanation, want to buy cheap {keyword}, right on temperqueen.com. ';
    $desc[5] = 'Temperqueen website provides thick texture, good drapability have weight, warmth retention property is strong, suitable for age season and winter {keyword}, there are more suitable for on line feeling A word and the tail of {keyword}, can express grand sense, with the court of bead light feeling type or big trailing style of {keyword} also often use thick satin to make, simple sense is gentle and elegant, can show the aesthetic feeling of romance twilight, all seasons. ';
    $desc[6] = 'Temperqueen site offers {keyword} the material hard texture, good transparency, light weight, thin, shiny, the seven color, feel soft elegant, also has a silky feel, light transmittance is not high, mostly used to catch the bright color made of different region amorous feelings {keyword}, grid is thick, reflective evenly, moderate hardness.'; 
    $desc[7] = 'Temperqueen website according to {keyword} to provide different colors of dress, the color contains: purple, sapphire blue, cinnamon, lotus root, copper gold. {keyword} to provide related sites under the color of dress, let you put on {keyword} the distance it seems cannot even tell the boundaries of skin and dress. ';
    $desc[8] = 'Temperqueen website according to the accurate time, place, and style, the season to select {keyword}, {keyword} to be consistent with the style of the wedding. If {keyword} goes in tide front, if is one of the traditional ceremony, then you have to choose according to {keyword} a breezy style suitable for outdoor wear. ';
    $desc[9] = 'Temperqueen site according to the {keyword} and popular trend confirmed pearl crystal sequins, lace, bowknot, fold, falbala, three-dimensional flowers and feathers, etc., {keyword} also according to choose than column is also in this order, is no different with previous, to determine the {keyword} level of romance. ';
    
    $rand_keys = array_rand($desc, 1);
    return str_replace('{keyword}', $keyword, $desc[$rand_keys]);  
}


function get_goods_comment($goods_id)
{
    $sql = "SELECT comment_id,id_value,user_name,content,comment_rank,add_time,quslity_rank,value_rank,price_rank FROM ".$GLOBALS['ecs']->table('comment')." WHERE id_value='{$goods_id}' AND status=1 ORDER BY add_time DESC";
    $res = $GLOBALS['db']->getAll($sql);
    if($res) {
        foreach($res as $k=>$val) {
            $res[$k]['date'] = date('d/m/Y', $val['add_time']);
            $res[$k]['hours'] = date('H:i', $val['add_time']);
            $res[$k]['quslity_rank_all'] = $val['quslity_rank']*2;
            $res[$k]['value_rank_all'] = $val['value_rank']*2;
            $res[$k]['price_rank_all'] = $val['price_rank']*2;
        }
    }
    return $res;
}

?>