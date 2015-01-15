<?php 

/* 分割图片数组 */
function fom_array( $input, $f = 0, $r = 0) 
{
    if (count($input) < $f + $r ) {
        return array();
    }
    
    if ($r != 0) {
        $r = 0 - $r;
        $input = array_slice($input, $f, $r );
    } else {
        $input = array_slice($input, $f );
    }
    $tmp = array();
    foreach ($input as $value) {
        if ($value) {
            $tmp[] = $value;
        }
    }
    return $tmp;
}


function han_trip_same($array)
{
    $tmp = array();
    foreach ($array as $value) {
        if (!han_in_array($value,$tmp)) {
            array_push($tmp,$value);
        }
    }
    return $tmp;
}

function han_in_array($findme,$array )
{
    foreach ($array as $key => $value) {
        if (!($findme == $value)) {
            continue;
        }
        return true;
    }
    return false;
}


function GetRemoteImage($url, $rndtrueName)
{
    if(DOWNLOAD_TYPE == "http") {
        return httpget($url, $rndtrueName);
    }
    if(DOWNLOAD_TYPE == "curl") {
        return curlget($url, $rndtrueName);
    }
}

function httpGet($url, $rndtrueName)
{
    $url = fom_url($url);
    $revalues = array();
    $ok = false;
    $htd = new Httpdown();
    
    $htd->OpenUrl($url);
    $sparr = array("image/pjpeg", "image/jpeg", "image/gif", "image/png", "image/xpng", "image/wbmp");
    if(!in_array($htd->GetHead("content-type"), $sparr)) {
        return "";
    }
    //print_r($goods_img_array);
    //echo "url:".$url."\n";
    //echo "rndtrueName:".$rndtrueName."\n";
    //exit();                    
    make_dir(dirname($rndtrueName));
    $itype = $htd->GetHead("content-type");
    $ok = $htd->SaveToBin($rndtrueName);
    if($ok) {
        $data = getimagesize($rndtrueName);
        $revalues[0] = $rndtrueName;
        $revalues[1] = $data[0];
        $revalues[2] = $data[1];
    }
    $htd->Close();
    if ($ok) {
        return $revalues;
    }
    return "";
}

function print_array($array)
{
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

function write_error($source_url)
{
    $fp = fopen("error.log", "a+");
    fwrite($fp, $source_url."\r\n");
    exit("分析错误");
}

function fom_url($url)
{
    $url = str_replace("à", "%c3%a0", $url);
    $url = str_replace(" ", "%20", $url);
    return $url;
}

function hexplode($html, $break = "|||")
{
    $html = explode($break, $html);
    $html = fom_array($html);
    return $html;
}

function get_sub_info($name_array, $tag)
{
    $tag .= "=";
    if(is_array($name_array)) {
        $values = array();
        foreach ($name_array as $key => $name) {
                if(preg_match("/:/", $name)) {
                    $name_sub_info = strstr($name, $tag);
                    $name_array[$key] = trim(preg_replace("/:.*/", "", $name));
                    $value = str_replace($tag, "", $name_sub_info);
                    array_push($values, $value);
                } else {
                    array_push($values, "0");
                }
        }
        return $values;
    }
    if (is_string($name_array)) {
        $name_sub_info = strstr($name_array, $tag);
        $name_array = trim(preg_replace("/:.*/", "", $name_array));
        $value = str_replace($tag, "", $name_sub_info);
        return $value;
    }
    return "0";
}

function add_gallery_image($goods_id, $image_files, $image_descs)
{
    $proc_thumb = isset($GLOBALS['shop_id']) && 0 < $GLOBALS['shop_id'] ? false : true;
    foreach($image_descs as $key => $img_desc) {
        $flag = false;
        $img_original = $GLOBALS['image']->upload_image($image_files[$key]);
        if($img_original !== FALSE) {
                $flag = true;
        }
        if($flag) {
            $img_url = $img_original;
            if($proc_thumb ) {
                $thumb_url = $GLOBALS['image']->make_thumb("../".$img_url, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                $thumb_url = is_string($thumb_url) ? $thumb_url : "";
            }
            if(!$proc_thumb) {
                $thumb_url = $img_original;
            }
            if($proc_thumb && 0 < gd_version()) {
                $pos = strpos(basename($img_original ), ".");
                $newname = dirname($img_original)."/".$GLOBALS['image']->random_filename().substr(basename($img_original), $pos);
                copy("../".$img_original, "../".$newname);
                $img_url = $newname;
                $GLOBALS['image']->add_watermark("../".$img_url, "", $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
            }
            $img_original = reformat_image_name("gallery", $goods_id, $img_original, "source");
            $img_url = reformat_image_name("gallery", $goods_id, $img_url, "goods" );
            $thumb_url = reformat_image_name("gallery_thumb", $goods_id, $thumb_url, "thumb");
            $sql = "INSERT INTO ".$GLOBALS['ecs']->table( "goods_gallery" )." (goods_id, img_url, img_desc, thumb_url, img_original) ". "VALUES ('".$goods_id."', '{$img_url}', '{$img_desc}', '{$thumb_url}', '{$img_original}')" ;
            $GLOBALS['db']->query($sql);
            if($proc_thumb) {
                if(!$GLOBALS['_CFG']['retain_original_img']) {
                    if(!empty( $img_original)) {
                        $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table( "goods_gallery" ). " SET img_original='' WHERE `goods_id`='".$goods_id."'") ;
                        @unlink("../".$img_original);
                    }
                }
            }
        }
    }
}

function goods_parse_url($url)
{
    $parse_url = @parse_url($url);
    return !empty($parse_url['scheme']) && !empty($parse_url['host']);
}


/* 解析采集商品属性 */
function cj_parse_attr($attr ,$fenge = '|||')
{
    if(empty($attr)) return false;
    $attr = trim($attr, $fenge);
    $arr_tmp = explode($fenge, $attr);
    $new_attr = array();
    if($arr_tmp) {
        foreach($arr_tmp as $k=>$v) {
            $attr_name = explode('=>', $v);
            $new_attr[strtolower($attr_name[0])] = $attr_name[1];
        }
    }
    return $new_attr;
}

function filterstr($str = '') 
{
    $str = str_replace(array('/', '&amp;', '&'), ' ', $str);
    return $str;
}

/***
* 针对属性不一样情况进行替换
* @param mixed $post_attr
* @param mixed $replace
*/
function replaceparams($post_attr, $replace)
{
    if(empty($post_attr)) return false;
    $newarr = $post_attr;
    foreach($replace as $k=>$v) {
        foreach($post_attr as $k2=>$v2) {
            if($k == $k2) {
                $newarr[$v] = $v2;
                unset($newarr[$k2]);
            }
        }
    }
    return $newarr; 
}

function compareattr($attr)
{
    if(is_array($attr) && !empty($attr)) {
        $zid = array(
            'silhouette', 
            'hemline_train',
            'wedding_venues',
            'trend_collections',
            'neckline',
            'body_shape',
            'fabric',
            'style',
            'season',
            'sleeve_length',
            'sleeve_style',
            'embellishments',
            'back_details',
            'waist',
            'select_size',
            'price',
            'color',
            'occasion',
            'shown_color',
            'celebrity_style',
            'trend',
        );
        foreach($attr as $k=>$v) {
            $key = str_replace(array(' ','/'),'_',strtolower($k));
            if(in_array($key, $zid)) {
                $newarr[$key] = $v;
            }
        }
        return $newarr;    
    }    
}

function randcolor()
{
    $colorarr = array(
      'White',
      'Black',
      'Red',
      'Purple',
      'Blue',
      'Pink',
      'Yellow',
      'Green',
      'Orange',
      'Brown',
      'Gold',
      'Grey',
      'Silver',
      'Ivory',
    );
    $rand_keys = array_rand($colorarr, 2);
    return $colorarr[$rand_keys[0]].' '.$colorarr[$rand_keys[1]];
}