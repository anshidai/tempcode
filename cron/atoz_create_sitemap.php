<?php

/**
* 更新atoz生成的sitemap地图
*/


define('IN_ECS', true);
set_time_limit(0);
$sapi = php_sapi_name();
if($sapi != 'cli') {
    die('只能命令行执行');
}

require(dirname(__FILE__).'/../includes/init.php');

$sitemap_path = '/home/wwwroot/temperqueen.com/sitemap/';
if(!file_exists($sitemap_path)) {
    mkdir($sitemap_path);
    chmod($sitemap_path, 0777);
    
}

$pagesize = 5000;

$keywords_total = $GLOBALS['db']->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('keywords_detail')." WHERE status=1");
$keywords_page_count = ceil($keywords_total/$pagesize);


$goods_total = $GLOBALS['db']->getOne("SELECT count(*) FROM " .$ecs->table('goods'). " WHERE is_delete = 0 AND is_on_sale=1");
$goods_page_count = ceil($goods_total/$pagesize);

//创建sitemap_index
create_index($keywords_page_count, $goods_page_count);


//创建
if($keywords_page_count) {
    for($i=1; $i<=$keywords_page_count; $i++) {
        $sql = "SELECT qid,query_url FROM ".$GLOBALS['ecs']->table('keywords_detail')." WHERE status=1 ORDER BY qid LIMIT ".($i-1)*$pagesize.",".$pagesize;
        $result = $GLOBALS['db']->getAll($sql);
        if($result) {
            create_item_keywords($i, $result);     
        }      
    }
}

if($goods_page_count) {
    for($i=1; $i<=$goods_page_count; $i++) {
        $sql = "SELECT goods_id, goods_name FROM ".$GLOBALS['ecs']->table('goods')." WHERE is_delete = 0 AND is_on_sale=1 ORDER BY goods_id LIMIT ".($i-1)*$pagesize.",".$pagesize;
        $result = $GLOBALS['db']->getAll($sql);
        if($result) {
            create_item_goods($i, $result);     
        }      
    }
}

echo "compelte\n";


function create_index($keywords_page_count, $goods_page_count)
{
    global $sitemap_path;
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    $xml .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    
    if($keywords_page_count) {
        for($i=1; $i<=$keywords_page_count; $i++) {
            $xml .= "<sitemap>\n";
            $xml .= "<loc>http://www.temperqueen.com/sitemap/detail_".$i.".xml</loc>\n";
            $xml .= "<lastmod>".date('Y-m-d')."</lastmod>\n";
            $xml .= "</sitemap>\n";
        }  
    }
    if($goods_page_count) {
        for($i=1; $i<=$goods_page_count; $i++) {
            $xml .= "<sitemap>\n";
            $xml .= "<loc>http://www.temperqueen.com/sitemap/goods_en_".$i.".xml</loc>\n";
            $xml .= "<lastmod>".date('Y-m-d')."</lastmod>\n";
            $xml .= "</sitemap>\n";
        }  
    }
    
    $xml .= "</sitemapindex>\n";
    file_put_contents($sitemap_path.'sitemap_index.xml', $xml);
}

function create_item_keywords($index = 1, $data)
{
    global $sitemap_path;
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    
    foreach($data as $val) {
        $xml .= "<url>\n";
        $xml .= "<loc>http://www.temperqueen.com/product-detail/{$val['query_url']}.html</loc>\n";
        $xml .= "<lastmod>".date('Y-m-d')."</lastmod>\n";
        $xml .= "<changefreq>hourly</changefreq>\n";
        $xml .= "<priority>0.8</priority>\n";
        $xml .= "</url>\n";
    }
    $xml .= "</urlset>\n";            
    file_put_contents($sitemap_path."detail_{$index}.xml", $xml);
}

function create_item_goods($index = 1, $data)
{
    global $sitemap_path;
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    
    foreach($data as $val) {
        $url = 'http://www.temperqueen.com/'.build_uri('goods', array('gid' => $val['goods_id']), $val['goods_name']);
        $xml .= "<url>\n";
        $xml .= "<loc>$url</loc>\n";
        $xml .= "<lastmod>".date('Y-m-d')."</lastmod>\n";
        $xml .= "<changefreq>hourly</changefreq>\n";
        $xml .= "<priority>0.8</priority>\n";
        $xml .= "</url>\n";
    }
    $xml .= "</urlset>\n";            
    file_put_contents($sitemap_path."goods_en_{$index}.xml", $xml);
}