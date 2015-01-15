<?php 

class down_image extends cls_image
{
    public function __construct($bgcolor = "")
    {
        parent::__construct($bgcolor);
    }

    public function upload_image($remote_goods_img, $dir = "", $img_name = "")
    {
            if (empty($dir))
            {
                $dir = date("Ym");
                $dir = ROOT_PATH.$this->images_dir."/".$dir."/";
            }
            else {
                $dir = ROOT_PATH.$this->data_dir."/".$dir."/";
                if ($img_name){
                    $img_name = $dir.$img_name;
                }
            }

            if(!file_exists($dir) || !make_dir($dir)){
                $this->error_msg = sprintf( $GLOBALS['_LANG']['directory_readonly'], $dir );
                $this->error_no = ERR_DIRECTORY_READONLY;
                return false;
            }
            if(empty($img_name)){
                $img_name = $this->unique_name( $dir );
                $img_name = $dir.$img_name.$this->get_filetype( $remote_goods_img );
            }
        
            if(getremoteimage($remote_goods_img, $img_name)){
                return str_replace(ROOT_PATH, "", $img_name);
            }
            return false;
    }

    public function get_filetype($remote_goods_img)
    {
        $pathinfo_goods_img = pathinfo($remote_goods_img);
        return ".".$pathinfo_goods_img['extension'];
    }

    public function make_thumb($img, $thumb_width = 0, $thumb_height = 0, $path = "", $bgcolor = "")
    {
        $gd = $this->gd_version();
        if($gd == 0) {
            $this->error_msg = $GLOBALS['_LANG']['missing_gd'];
            return false;
        }
        if($thumb_width == 0 && $thumb_height == 0) {
            return str_replace(ROOT_PATH, "", str_replace("\\", "/", realpath($img)));
        }
        $org_info = @getimagesize( $img );
        if(!$org_info) {
            $this->error_msg = sprintf( $GLOBALS['_LANG']['missing_orgin_image'], $img);
            $this->error_no = ERR_IMAGE_NOT_EXISTS;
            return false;
        }
        if(!$this->check_img_function( $org_info[2])) {
            $this->error_msg = sprintf($GLOBALS['_LANG']['nonsupport_type'], $this->type_maping[$org_info[2]]);
            $this->error_no = ERR_NO_GD;
            return false;
        }
        $img_org = $this->img_resource($img, $org_info[2]);
        $scale_org = $org_info[0] / $org_info[1];
        if($thumb_width == 0) {
            $thumb_width = $thumb_height * $scale_org;
        }
        if($thumb_height == 0) {
            $thumb_height = $thumb_width / $scale_org;
        }
        if($gd == 2) {
            $img_thumb = imagecreatetruecolor($thumb_width, $thumb_height);
        } else {
            $img_thumb = imagecreate($thumb_width, $thumb_height);
        }
        if(empty($bgcolor)) {
            $bgcolor = $this->bgcolor;
        }
        $bgcolor = trim($bgcolor, "#");
        sscanf($bgcolor, "%2x%2x%2x", $red, $green, $blue);
        $clr = imagecolorallocate($img_thumb, $red, $green, $blue);
        imagefilledrectangle($img_thumb, 0, 0, $thumb_width, $thumb_height, $clr);
        if($org_info[1] / $thumb_height < $org_info[0] / $thumb_width) {
            $lessen_width = $thumb_width;
            $lessen_height = $thumb_width / $scale_org;
        } else {
            $lessen_width = $thumb_height * $scale_org;
            $lessen_height = $thumb_height;
        }
        $dst_x = ($thumb_width - $lessen_width) / 2;
        $dst_y = ($thumb_height - $lessen_height) / 2;
        if($gd == 2) {
            imagecopyresampled( $img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1] );
        } else {
            imagecopyresized( $img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1] );
        }
        if(empty($path)) {
            $dir = ROOT_PATH.$this->images_dir."/".date( "Ym" )."/";
        } else {
            $dir = $path;
        }
        if(!file_exists($dir) || !make_dir($dir)) {
            $this->error_msg = sprintf( $GLOBALS['_LANG']['directory_readonly'], $dir );
            $this->error_no = ERR_DIRECTORY_READONLY;
            return FALSE;
        }
        $filename = $this->unique_name($dir);
        if(function_exists("imagejpeg")) {
                $filename .= ".jpg";
                imagejpeg($img_thumb, $dir.$filename);
        } else if(function_exists("imagegif")) {
            $filename .= ".gif";
            imagegif($img_thumb, $dir.$filename);
        }
        else if (function_exists("imagepng")) {
            $filename .= ".png";
            imagepng($img_thumb, $dir.$filename);
        } else {
            $this->error_msg = $GLOBALS['_LANG']['creating_failure'];
            $this->error_no = ERR_NO_GD;
            return false;
        }
        imagedestroy($img_thumb);
        imagedestroy($img_org);
        if(file_exists($dir.$filename)) {
            return str_replace( ROOT_PATH, "", $dir ).$filename;
        }
        $this->error_msg = $GLOBALS['_LANG']['writting_failure'];
        $this->error_no = ERR_DIRECTORY_READONLY;
        return false;
    }

}