<?php
/*********************/
/*                   */
/*  Version : 5.1.0  */
/*  Author  : RM     */
/*  Comment : 071223 */
/*                   */
/*********************/

class params
{

		public $html = NULL;
		public $group_html = NULL;
		public $attr_break = NULL;
		public $attr_html = NULL;
		public $array = NULL;
		public $type_setting = NULL;

		public function params( $html, $group_html = "@@@", $attr_break = "###", $attr_html = "|||" )
		{
				if ( !$this->isLegal( $html ) )
				{
						$this->html = $this->formatHtml( $html );
				}
				else
				{
						$this->html = $html;
				}
				$this->group_html = $group_html;
				$this->attr_break = $attr_break;
				$this->attr_html = $attr_html;
				$this->array = array( );
				$this->readGroup( );
		}

		public function readValueHelp( $value, $tag )
		{
				foreach ( $value as $string )
				{
						$array = fom_array( explode( "|||", $string ) );
						$return[$array['0']] = $tag ? $array['1'] : "";
				}
				return $return;
		}

		public function readGroup( )
		{
				$this->html = explode( $this->group_html, $this->html );
				$this->html = fom_array( $this->html, 1, 0 );
		}

		public function readValue( $tag = 1 )
		{
				foreach ( $this->html as $key => $value )
				{
						$value = fom_array( explode( "###", $value ) );
						$this->array[array_shift( $value )] = $this->readValueHelp( $value, $tag );
				}
		}

		public function run( )
		{
				$this->readValue( );
				return $this->array;
		}

		public function getType( )
		{
				$this->readValue( 0 );
				return $this->array;
		}

		public function isLegal( $value )
		{
				$i = 0;
				if ( preg_match( "/@@@/", $value ) )
				{
						++$i;
				}
				if ( preg_match( "/###/", $value ) )
				{
						++$i;
				}
				if ( preg_match( "/\\|\\|\\|/", $value ) )
				{
						++$i;
				}
				if ( $i == 3 )
				{
						return TRUE;
				}
				return FALSE;
		}

		public function formatHtml( $string )
		{
				$string = preg_replace( "/<th[^>]*>/", "@@@", $string );
				$string = preg_replace( "/<tr[^>]*>/", "###", $string );
				$string = preg_replace( "/<td[^>]*>/", "|||", $string );
				$string = strip_tags( $string );
				return $string;
		}

		public function formatArray( $array )
		{
				foreach ( $array as $key1 => $array1 )
				{
						foreach ( $array1 as $key2 => $array2 )
						{
								$array[$key1][$key2] = "";
						}
				}
				return $array;
		}

}

class attribute
{

		public function getAttr( $goods_type, &$attr_input_type, &$attr_values, &$attr_type )
		{
				global $db;
				global $ecs;
				$sql = "SELECT * FROM ".$ecs->table( "attribute" )." a ,".$ecs->table( "goods_type" )." gt WHERE a.cat_id=gt.cat_id AND gt.cat_id='".$goods_type."'";
				$res = $db->query( $sql );
				while ( $row = $db->fetchRow( $res ) )
				{
						$attr_input_type[$row['attr_id']] = $row['attr_input_type'];
						$attr_type[$row['attr_id']] = $row['attr_type'];
						$attr_values[$row['attr_id']] = $row['attr_values'];
				}
		}

		public function attrFormat( $attr, $attr_input_type, $attr_values, $attr_type )
		{
				global $db;
				global $ecs;
				global $_POST;
				foreach ( $attr as $attr_id => $attr_value )
				{
						$is_modify = 0;
						$attr_value = trim($attr_value);
						if (!empty($attr_value))
						{
								if ( $attr_input_type[$attr_id] == 1 && $attr_type[$attr_id] == 0 )
								{
										$attr_values[$attr_id] = explode( "\r\n", $attr_values[$attr_id] );
										if ( !in_array( $attr_value, $attr_values[$attr_id] ) )
										{
												array_push( $attr_values[$attr_id], $attr_value );
												$is_modify = 1;
										}
										$attr_values[$attr_id] = implode( "\r\n", $attr_values[$attr_id] );
								}
								else if ( ( $attr_input_type[$attr_id] == 1  && $attr_type[$attr_id] == 1) || ($attr_input_type[$attr_id] == 1 && $attr_type[$attr_id] == 2) )
								{
										$attr_values[$attr_id] = explode( "\r\n", $attr_values[$attr_id] );
										$attr[$attr_id] = explode( "|||", $attr_value );
										foreach ( $attr[$attr_id] as $key => $attr_value )
										{
												if ( !$attr_value )
												{
												}
												else if ( !in_array( $attr_value, $attr_values[$attr_id] ) )
												{
														array_push( $attr_values[$attr_id], $attr_value );
														$is_modify = 1;
												}
										}
										$attr_values[$attr_id] = implode( "\r\n", $attr_values[$attr_id] );
								}
								if ( $is_modify )
								{
										$sql = "UPDATE ".$ecs->table( "attribute" )." SET attr_values = '".$attr_values[$attr_id]. "' WHERE attr_id = '".$attr_id."'";
										$db->query( $sql );
								}
						}
				}
				reset( $attr );
				foreach ( $attr as $attr_id => $attr_value )
				{
						if ( is_array( $attr_value ) )
						{
								foreach ( $attr_value as $key => $value )
								{
										array_push( $GLOBALS['_POST']['attr_id_list'], $attr_id );
										array_push( $GLOBALS['_POST']['attr_price_list'], get_sub_info( $value, "price" ) );
										array_push( $GLOBALS['_POST']['attr_value_list'], $value );
								}
						}
						else
						{
								array_push( $GLOBALS['_POST']['attr_id_list'], $attr_id );
								array_push( $GLOBALS['_POST']['attr_price_list'], get_sub_info( $attr_value, "price" ) );
								array_push( $GLOBALS['_POST']['attr_value_list'], $attr_value );
						}
				}
		}

		public function isExists( $attr_name, $goods_type )
		{
				global $db;
				global $ecs;
				$sql = "SELECT attr_id FROM ".$ecs->table( "attribute" ). " WHERE attr_name = '".$attr_name."' AND cat_id = '{$goods_type}'";
				$res = $db->query( $sql );
				$row = $db->fetchRow( $res );
				if ( $row )
				{
						return $row['attr_id'];
				}
				return 0;
		}

		public function insert( $attr_name, $goods_type, $attr_group = 0, $attr_index = 1, $is_linked = 0, $attr_type = 0, $attr_input_type = 0, $attr_values = "", $attr_id = 0 )
		{
				global $db;
				global $ecs;
				$attr_id = $this->isExists( $attr_name, $goods_type );
				if ( $attr_id )
				{
						return $attr_id;
				}
				$attr = array(
						"cat_id" => $goods_type,
						"attr_name" => $attr_name,
						"attr_index" => $attr_index,
						"attr_input_type" => $attr_input_type,
						"is_linked" => $is_linked,
						"attr_values" => isset( $attr_values ) ? $attr_values : "",
						"attr_type" => empty( $attr_type ) ? "0" : intval( $attr_type ),
						"attr_group" => isset( $attr_group ) ? intval( $attr_group ) : 0
				);
				$db->autoExecute( $ecs->table( "attribute" ), $attr, "INSERT" );
				return $db->insert_id( );
		}

}

class goods_type
{

		public $group_list = array( );
		public $goods_type = NULL;

		public function getList( )
		{
				global $db;
				global $ecs;
				$goods_type = $db->getAll( "SELECT cat_id,cat_name FROM ".$ecs->table( "goods_type" ) );
				foreach ( $goods_type as $value )
				{
						$type[$value['cat_name']] = $value['cat_id'];
				}
				return $type;
		}

		public function insert( $cat_name, $attr_group = "", $enabled = 1 )
		{
				global $db;
				global $ecs;
				global $_LANG;
				$goods_type['cat_name'] = sub_str( $cat_name, 60 );
				$goods_type['attr_group'] = sub_str( $attr_group, 255 );
				$goods_type['enabled'] = intval( $enabled );
				if ( !$db->autoExecute( $ecs->table( "goods_type" ), $goods_type ) )
				{
						sys_msg( $_LANG['add_goodstype_failed'], 1 );
				}
				return $db->insert_id( );
		}

		public function getGroupList( )
		{
				global $db;
				$attr_group = $db->getOne( "SELECT `attr_group` FROM `ecs_goods_type` WHERE `cat_id`=".$this->goods_type." limit 0,1" );
				$attr_group = explode( "\n", $attr_group );
				foreach ( $attr_group as $key => $value )
				{
						$this->group_list[$value] = $key;
				}
				return $this->group_list;
		}

		public function getId( $cat_name, $attr_group = "" )
		{
				$all_type = $this->getList( );
				if ( isset( $all_type[$cat_name], $all_type[$cat_name] ) )
				{
						$id = $all_type[$cat_name];
						$this->update( $id, $attr_group );
				}
				else
				{
						$id = $this->insert( $cat_name, $attr_group );
				}
				if ( !$id )
				{
						exit( "商品类型错误" );
				}
				$this->goods_type = $id;
				return $id;
		}

		public function update( $id, $attr_group = "" )
		{
				global $db;
				global $ecs;
				if ( $attr_group )
				{
						$old_arrt_group = $db->getOne( "SELECT `attr_group` FROM ".$ecs->table( "goods_type" ). " WHERE `cat_id`=".$id ) ;
						$attr_group = $attr_group."\n".$old_arrt_group;
						$attr_group = explode( "\n", $attr_group );
						$attr_group = han_trip_same( $attr_group );
						$attr_group = implode( "\n", $attr_group );
						$db->query( "UPDATE ".$ecs->table( "goods_type" ). " SET `attr_group` = '".$attr_group."' WHERE `cat_id` ={$id}" );
				}
		}

}

class DedeHttpDown
{
	var $m_url = '';
	var $m_urlpath = '';
	var $m_scheme = 'http';
	var $m_host = '';
	var $m_port = '80';
	var $m_user = '';
	var $m_pass = '';
	var $m_path = '/';
	var $m_query = '';
	var $m_fp = '';
	var $m_error = '';
	var $m_httphead = '';
	var $m_html = '';
	var $m_puthead = '';
	var $BaseUrlPath = '';
	var $HomeUrl = '';
	var $reTry = 0;
	var $JumpCount = 0;

	//初始化系统
	function PrivateInit($url)
	{
		if($url=='') {
			return ;
		}
		$urls = '';
		$urls = @parse_url($url);
		$this->m_url = $url;
		if(is_array($urls))
		{
			$this->m_host = $urls["host"];
			if(!empty($urls["scheme"]))
			{
				$this->m_scheme = $urls["scheme"];
			}
			if(!empty($urls["user"]))
			{
				$this->m_user = $urls["user"];
			}
			if(!empty($urls["pass"]))
			{
				$this->m_pass = $urls["pass"];
			}
			if(!empty($urls["port"]))
			{
				$this->m_port = $urls["port"];
			}
			if(!empty($urls["path"]))
			{
				$this->m_path = $urls["path"];
			}
			$this->m_urlpath = $this->m_path;
			if(!empty($urls["query"]))
			{
				$this->m_query = $urls["query"];
				$this->m_urlpath .= "?".$this->m_query;
			}
			$this->HomeUrl = $urls["host"];
			$this->BaseUrlPath = $this->HomeUrl.$urls["path"];
			$this->BaseUrlPath = preg_replace("/\/([^\/]*)\.(.*)$/","/",$this->BaseUrlPath);
			$this->BaseUrlPath = preg_replace("/\/$/","",$this->BaseUrlPath);
		}
	}

	function ResetAny()
	{
		//重设各参数
		$this->m_url = "";
		$this->m_urlpath = "";
		$this->m_scheme = "http";
		$this->m_host = "";
		$this->m_port = "80";
		$this->m_user = "";
		$this->m_pass = "";
		$this->m_path = "/";
		$this->m_query = "";
		$this->m_error = "";
	}

	//打开指定网址
	function OpenUrl($url,$requestType="GET")
	{
		$this->ResetAny();
		$this->JumpCount = 0;
		$this->m_httphead = Array() ;
		$this->m_html = '';
		$this->reTry = 0;
		$this->Close();

		//初始化系统
		$this->PrivateInit($url);
		$this->PrivateStartSession($requestType);
	}

	//转到303重定向网址
	function JumpOpenUrl($url)
	{
		$this->ResetAny();
		$this->JumpCount++;
		$this->m_httphead = Array() ;
		$this->m_html = "";
		$this->Close();

		//初始化系统
		$this->PrivateInit($url);
		$this->PrivateStartSession('GET');
	}

	//获得某操作错误的原因
	function printError()
	{
		echo "错误信息：".$this->m_error;
		echo "<br/>具体返回头：<br/>";
		foreach($this->m_httphead as $k=>$v){ echo "$k => $v <br/>\r\n"; }
	}

	//判别用Get方法发送的头的应答结果是否正确
	function IsGetOK()
	{
		if( ereg("^2",$this->GetHead("http-state")) )
		{
			return true;
		}
		else
		{
			$this->m_error .= $this->GetHead("http-state")." - ".$this->GetHead("http-describe")."<br/>";
			return false;
		}
	}

	//看看返回的网页是否是text类型
	function IsText()
	{
		if( ereg("^2",$this->GetHead("http-state")) && eregi("text|xml",$this->GetHead("content-type")) )
		{
			return true;
		}
		else
		{
			$this->m_error .= "内容为非文本类型或网址重定向<br/>";
			return false;
		}
	}

	//判断返回的网页是否是特定的类型
	function IsContentType($ctype)
	{
		if(ereg("^2",$this->GetHead("http-state"))
		&& $this->GetHead("content-type")==strtolower($ctype))
		{	return true; }
		else
		{
			$this->m_error .= "类型不对 ".$this->GetHead("content-type")."<br/>";
			return false;
		}
	}

	//用Http协议下载文件
	function SaveToBin($savefilename)
	{
		if(!$this->IsGetOK())
		{
			return false;
		}
		if(@feof($this->m_fp))
		{
			$this->m_error = "连接已经关闭！"; return false;
		}
		$fp = fopen($savefilename,"w");
		while(!feof($this->m_fp))
		{
			fwrite($fp,fread($this->m_fp,1024));
		}
		fclose($this->m_fp);
		fclose($fp);
		return true;
	}

	//保存网页内容为Text文件
	function SaveToText($savefilename)
	{
		if($this->IsText())
		{
			$this->SaveBinFile($savefilename);
		}
		else
		{
			return "";
		}
	}

	//用Http协议获得一个网页的内容
	function GetHtml()
	{
		if(!$this->IsText())
		{
			return '';
		}
		if($this->m_html!='')
		{
			return $this->m_html;
		}
		if(!$this->m_fp||@feof($this->m_fp))
		{
			return '';
		}
		while(!feof($this->m_fp))
		{
			$this->m_html .= fgets($this->m_fp,256);
		}
		@fclose($this->m_fp);
		return $this->m_html;
	}

	//开始HTTP会话
	function PrivateStartSession($requestType="GET")
	{
		if(!$this->PrivateOpenHost())
		{
			$this->m_error .= "打开远程主机出错!";
			return false;
		}
		$this->reTry++;
		if($this->GetHead("http-edition")=="HTTP/1.1")
		{
			$httpv = "HTTP/1.1";
		}
		else
		{
			$httpv = "HTTP/1.0";
		}
		$ps = explode('?',$this->m_urlpath);

		$headString = '';

		//发送固定的起始请求头GET、Host信息
		if($requestType=="GET")
		{
			$headString .= "GET ".$this->m_urlpath." $httpv\r\n";
		}
		else
		{
			$headString .= "POST ".$ps[0]." $httpv\r\n";
		}
		$this->m_puthead["Host"] = $this->m_host;

		//发送用户自定义的请求头
		if(!isset($this->m_puthead["Accept"]))
		{
			$this->m_puthead["Accept"] = "*/*";
		}
		if(!isset($this->m_puthead["User-Agent"]))
		{
			$this->m_puthead["User-Agent"] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2)";
		}
		if(!isset($this->m_puthead["Refer"]))
		{
			$this->m_puthead["Refer"] = "http://".$this->m_puthead["Host"];
		}

		foreach($this->m_puthead as $k=>$v)
		{
			$k = trim($k);
			$v = trim($v);
			if($k!=""&&$v!="")
			{
				$headString .= "$k: $v\r\n";
			}
		}
		fputs($this->m_fp, $headString);
		if($requestType=="POST")
		{
			$postdata = "";
			if(count($ps)>1)
			{
				for($i=1;$i<count($ps);$i++)
				{
					$postdata .= $ps[$i];
				}
			}
			else
			{
				$postdata = "OK";
			}
			$plen = strlen($postdata);
			fputs($this->m_fp,"Content-Type: application/x-www-form-urlencoded\r\n");
			fputs($this->m_fp,"Content-Length: $plen\r\n");
		}

		//发送固定的结束请求头
		//HTTP1.1协议必须指定文档结束后关闭链接,否则读取文档时无法使用feof判断结束
		if($httpv=="HTTP/1.1")
		{
			fputs($this->m_fp,"Connection: Close\r\n\r\n");
		}
		else
		{
			fputs($this->m_fp,"\r\n");
		}
		if($requestType=="POST")
		{
			fputs($this->m_fp,$postdata);
		}

		//获取应答头状态信息
		$httpstas = explode(" ",fgets($this->m_fp,256));
		$this->m_httphead["http-edition"] = trim($httpstas[0]);
		$this->m_httphead["http-state"] = trim($httpstas[1]);
		$this->m_httphead["http-describe"] = "";
		for($i=2;$i<count($httpstas);$i++)
		{
			$this->m_httphead["http-describe"] .= " ".trim($httpstas[$i]);
		}

		//获取详细应答头
		while(!feof($this->m_fp))
		{
			$line = trim(fgets($this->m_fp,256));
			if($line == "")
			{
				break;
			}
			$hkey = "";
			$hvalue = "";
			$v = 0;
			for($i=0;$i<strlen($line);$i++)
			{
				if($v==1)
				{
					$hvalue .= $line[$i];
				}
				if($line[$i]==":")
				{
					$v = 1;
				}
				if($v==0)
				{
					$hkey .= $line[$i];
				}
			}
			$hkey = trim($hkey);
			if($hkey!="")
			{
				$this->m_httphead[strtolower($hkey)] = trim($hvalue);
			}
		}

		//如果连接被不正常关闭，重试
		if(feof($this->m_fp))
		{
			if($this->reTry > 10)
			{
				return false;
			}
			$this->PrivateStartSession($requestType);
		}

		//判断是否是3xx开头的应答
		if(ereg("^3",$this->m_httphead["http-state"]))
		{
			if($this->JumpCount > 3)
			{
				return;
			}
			if(isset($this->m_httphead["location"]))
			{
				$newurl = $this->m_httphead["location"];
				if(eregi("^http",$newurl))
				{
					$this->JumpOpenUrl($newurl);
				}
				else
				{
					$newurl = $this->FillUrl($newurl);
					$this->JumpOpenUrl($newurl);
				}
			}
			else
			{
				$this->m_error = "无法识别的答复！";
			}
		}
	}

	//获得一个Http头的值
	function GetHead($headname)
	{
		$headname = strtolower($headname);
		return isset($this->m_httphead[$headname]) ? $this->m_httphead[$headname] : '';
	}

	//设置Http头的值
	function SetHead($skey,$svalue)
	{
		$this->m_puthead[$skey] = $svalue;
	}

	//打开连接
	function PrivateOpenHost()
	{
		if($this->m_host=="")
		{
			return false;
		}
		$errno = "";
		$errstr = "";
		$this->m_fp = @fsockopen($this->m_host, $this->m_port, $errno, $errstr,10);
		if(!$this->m_fp)
		{
			$this->m_error = $errstr;
			return false;
		}
		else
		{
			return true;
		}
	}

	//关闭连接
	function Close()
	{
		@fclose($this->m_fp);
	}

	//补全相对网址
	function FillUrl($surl)
	{
		$i = 0;
		$dstr = "";
		$pstr = "";
		$okurl = "";
		$pathStep = 0;
		$surl = trim($surl);
		if($surl=="")
		{
			return "";
		}
		$pos = strpos($surl,"#");
		if($pos>0)
		{
			$surl = substr($surl,0,$pos);
		}
		if($surl[0]=="/")
		{
			$okurl = "http://".$this->HomeUrl.$surl;
		}
		else if($surl[0]==".")
		{
			if(strlen($surl)<=1)
			{
				return "";
			}
			else if($surl[1]=="/")
			{
				$okurl = "http://".$this->BaseUrlPath."/".substr($surl,2,strlen($surl)-2);
			}
			else
			{
				$urls = explode("/",$surl);
				foreach($urls as $u)
				{
					if($u=="..")
					{
						$pathStep++;
					}
					else if($i<count($urls)-1)
					{
						$dstr .= $urls[$i]."/";
					}
					else
					{
						$dstr .= $urls[$i];
					}
					$i++;
				}
				$urls = explode("/",$this->BaseUrlPath);
				if(count($urls) <= $pathStep)
				{
					return "";
				}
				else
				{
					$pstr = "http://";
					for($i=0;$i<count($urls)-$pathStep;$i++)
					{
						$pstr .= $urls[$i]."/";
					}
					$okurl = $pstr.$dstr;
				}
			}
		}
		else
		{
			if(strlen($surl)<7)
			{
				$okurl = "http://".$this->BaseUrlPath."/".$surl;
			}
			else if(strtolower(substr($surl,0,7))=="http://")
			{
				$okurl = $surl;
			}
			else
			{
				$okurl = "http://".$this->BaseUrlPath."/".$surl;
			}
		}
		$okurl = eregi_replace("^(http://)","",$okurl);
		$okurl = eregi_replace("/{1,}","/",$okurl);
		return "http://".$okurl;
	}
}

function list_link( $is_add = TRUE, $extension_code = "" )
{
		$href = "goods.php?act=list";
		if ( !empty( $extension_code ) )
		{
				$href .= "&extension_code=".$extension_code;
		}
		if ( !$is_add )
		{
				$href .= "&".list_link_postfix( );
		}
		if ( $extension_code == "virtual_card" )
		{
				$text = $GLOBALS['_LANG']['50_virtual_card_list'];
		}
		else
		{
				$text = $GLOBALS['_LANG']['01_goods_list'];
		}
		return array(
				"href" => $href,
				"text" => $text
		);
}

function add_link( $extension_code = "" )
{
		$href = "goods.php?act=add";
		if ( !empty( $extension_code ) )
		{
				$href .= "&extension_code=".$extension_code;
		}
		if ( $extension_code == "virtual_card" )
		{
				$text = $GLOBALS['_LANG']['51_virtual_card_add'];
		}
		else
		{
				$text = $GLOBALS['_LANG']['02_goods_add'];
		}
		return array(
				"href" => $href,
				"text" => $text
		);
}

function goods_parse_url( $url )
{
		$parse_url = @parse_url( $url );
		return !empty( $parse_url['scheme'] ) && !empty( $parse_url['host'] );
}

function handle_volume_price( $goods_id, $number_list, $price_list )
{
		$sql = "DELETE FROM ".$GLOBALS['ecs']->table( "volume_price" ). " WHERE price_type = '1' AND goods_id = '".$goods_id."'" ;
		$GLOBALS['db']->query( $sql );
		foreach ( $price_list as $key => $price )
		{
				$volume_number = $number_list[$key];
				if ( !empty( $price ) )
				{
						$sql = "INSERT INTO ".$GLOBALS['ecs']->table( "volume_price" )." (price_type, goods_id, volume_number, volume_price) ". "VALUES ('1', '".$goods_id."', '{$volume_number}', '{$price}')" ;
						$GLOBALS['db']->query( $sql );
				}
		}
}

function add_gallery_image( $goods_id, $image_files, $image_descs )
{
		$proc_thumb = isset( $GLOBALS['shop_id'] ) && 0 < $GLOBALS['shop_id'] ? FALSE : TRUE;
		foreach ( $image_descs as $key => $img_desc )
		{
				$flag = FALSE;
				$img_original = $GLOBALS['image']->upload_image( $image_files[$key] );
				if ( $img_original !== FALSE )
				{
						$flag = TRUE;
				}
				if ( $flag )
				{
						$img_url = $img_original;
						if ( $proc_thumb )
						{
								$thumb_url = $GLOBALS['image']->make_thumb( "../".$img_url, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'] );
								$thumb_url = is_string( $thumb_url ) ? $thumb_url : "";
						}
						if ( !$proc_thumb )
						{
								$thumb_url = $img_original;
						}
						if ( $proc_thumb && 0 < gd_version( ) )
						{
								$pos = strpos( basename( $img_original ), "." );
								$newname = dirname( $img_original )."/".$GLOBALS['image']->random_filename( ).substr( basename( $img_original ), $pos );
								copy( "../".$img_original, "../".$newname );
								$img_url = $newname;
								$GLOBALS['image']->add_watermark( "../".$img_url, "", $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha'] );
						}
						$img_original = reformat_image_name( "gallery", $goods_id, $img_original, "source" );
						$img_url = reformat_image_name( "gallery", $goods_id, $img_url, "goods" );
						$thumb_url = reformat_image_name( "gallery_thumb", $goods_id, $thumb_url, "thumb" );
						$sql = "INSERT INTO ".$GLOBALS['ecs']->table( "goods_gallery" )." (goods_id, img_url, img_desc, thumb_url, img_original) ". "VALUES ('".$goods_id."', '{$img_url}', '{$img_desc}', '{$thumb_url}', '{$img_original}')" ;
						$GLOBALS['db']->query( $sql );
						if ( $proc_thumb )
						{
								if ( !$GLOBALS['_CFG']['retain_original_img'] )
								{
										if ( !empty( $img_original ) )
										{
												$GLOBALS['db']->query( "UPDATE ".$GLOBALS['ecs']->table( "goods_gallery" ). " SET img_original='' WHERE `goods_id`='".$goods_id."'" ) ;
												@unlink( "../".$img_original );
										}
								}
						}
				}
		}
}

function add_brand( $brand_name = "", $brand_logo = "", $brand_desc = "", $is_show = 1, $sort_order = "50" )
{
		global $db;
		global $ecs;
		global $image;
		$is_show = isset( $is_show ) ? intval( $is_show ) : 1;
		$img_name = basename( $image->upload_image( $brand_logo, "brandlogo" ) );
		$site_url = sanitize_url( $site_url );
		$sql = "INSERT INTO ".$ecs->table( "brand" )." (brand_name, site_url, brand_desc, brand_logo, is_show, sort_order) ". " VALUES ('".$brand_name."', '{$site_url}', '{$brand_desc}', '{$img_name}', '{$is_show}', '{$sort_order}')" ;
		$db->query( $sql );
		clear_cache_files( );
		return $db->insert_id( );
}

function add_category( $cat_name = "", $parent_id = 0, $sort_order = 50, $keywords = "", $cat_desc = "", $measure_unit = "", $show_in_nav = 0, $style = "", $is_show = 1, $grade = 0, $filter_attr = 0, $cat_recommend = array( ) )
{
		global $db;
		global $ecs;
		global $image;
		global $_LANG;
		$cat['parent_id'] = !empty( $parent_id ) ? intval( $parent_id ) : 0;
		$cat['sort_order'] = !empty( $sort_order ) ? intval( $sort_order ) : 0;
		$cat['keywords'] = !empty( $keywords ) ? trim( $keywords ) : "";
		$cat['cat_desc'] = !empty( $cat_desc ) ? $cat_desc : "";
		$cat['measure_unit'] = !empty( $measure_unit ) ? trim( $measure_unit ) : "";
		$cat['cat_name'] = !empty( $cat_name ) ? trim( $cat_name ) : "";
		$cat['show_in_nav'] = !empty( $show_in_nav ) ? intval( $show_in_nav ) : 0;
		$cat['style'] = !empty( $style ) ? trim( $style ) : "";
		$cat['is_show'] = !empty( $is_show ) ? intval( $is_show ) : 0;
		$cat['grade'] = !empty( $grade ) ? intval( $grade ) : 0;
		$cat['filter_attr'] = !empty( $filter_attr ) ? implode( ",", array_unique( $filter_attr ) ) : 0;
		$cat['cat_recommend'] = !empty( $cat_recommend ) ? $cat_recommend : array( );
		if ( 10 < $cat['grade'] || $cat['grade'] < 0 )
		{
				$link[] = array(
						"text" => $_LANG['go_back'],
						"href" => "javascript:history.back(-1)"
				);
				sys_msg( $_LANG['grade_error'], 0, $link );
		}
		if ( $db->autoExecute( $ecs->table( "category" ), $cat ) !== FALSE )
		{
				$cat_id = $db->insert_id( );
				if ( $cat['show_in_nav'] == 1 )
				{
						$vieworder = $db->getOne( "SELECT max(vieworder) FROM ".$ecs->table( "nav" )." WHERE type = 'middle'" );
						$vieworder += 2;
						$sql = "INSERT INTO ".$ecs->table( "nav" )." (name,ctype,cid,ifshow,vieworder,opennew,url,type) VALUES('".$cat['cat_name']."', 'c', '".$db->insert_id( ). "','1','".$vieworder."','0', '" .build_uri( "category", array(
								"cid" => $cat_id
						), $cat['cat_name'] )."','middle')";
						$db->query( $sql );
				}
				insert_cat_recommend( $cat['cat_recommend'], $cat_id );
				clear_cache_files( );
				return $cat_id;
		}
}

function insert_cat_recommend( $recommend_type, $cat_id )
{
		if ( !empty( $recommend_type ) )
		{
				$recommend_res = $GLOBALS['db']->getAll( "SELECT recommend_type FROM ".$GLOBALS['ecs']->table( "cat_recommend" )." WHERE cat_id=".$cat_id );
				if ( empty( $recommend_res ) )
				{
						foreach ( $recommend_type as $data )
						{
								$data = intval( $data );
								$GLOBALS['db']->query( "INSERT INTO ".$GLOBALS['ecs']->table( "cat_recommend" ). "(cat_id, recommend_type) VALUES ('".$cat_id."', '{$data}')" ) ;
								continue;
						}

						$old_data = array( );
						foreach ( $recommend_res as $data )
						{
								$old_data[] = $data['recommend_type'];
						}
						$delete_array = array_diff( $old_data, $recommend_type );
						if ( !empty( $delete_array ) )
						{
								$GLOBALS['db']->query( "DELETE FROM ".$GLOBALS['ecs']->table( "cat_recommend" ). " WHERE cat_id=".$cat_id." AND recommend_type " ).db_create_in( $delete_array );
						}
						$insert_array = array_diff( $recommend_type, $old_data );
						if ( !empty( $insert_array ) )
						{
								foreach ( $insert_array as $data )
								{
										$data = intval( $data );
										$GLOBALS['db']->query( "INSERT INTO ".$GLOBALS['ecs']->table( "cat_recommend" ). "(cat_id, recommend_type) VALUES ('".$cat_id."', '{$data}')" );
								}
						}
				}
		}
		else
		{
				$GLOBALS['db']->query( "DELETE FROM ".$GLOBALS['ecs']->table( "cat_recommend" )." WHERE cat_id=".$cat_id );
		}
}

function brand_exist( $brand_name )
{
		global $db;
		global $ecs;
		$query = $db->query( "SELECT * FROM ".$ecs->table( "brand" ). " WHERE brand_name='".$brand_name."'" );
		$num = $db->num_rows( $query );
		if ( 0 < $num )
		{
				return TRUE;
		}
		return FALSE;
}

function get_brand_id( $brand_name )
{
		global $db;
		global $ecs;
		$brand_id = $db->getOne( "SELECT brand_id FROM ".$ecs->table( "brand" )." WHERE brand_name='".$brand_name."'" );
		return $brand_id;
}

function category_exist( $category_name, $parent_id = 0 )
{
		global $db;
		global $ecs;
		$query = $db->query( "SELECT * FROM ".$ecs->table( "category" ). " WHERE cat_name='".$category_name."' and parent_id='{$parent_id}'"  );
		$num = $db->num_rows( $query );
		if ( 0 < $num )
		{
				return TRUE;
		}
		$query = $db->query( "SELECT * FROM ".$ecs->table( "category" ). " WHERE cat_name='".$category_name."' and parent_id='{$parent_id}'"  );
		$num = $db->num_rows( $query );
		if ( 0 < $num )
		{
				return TRUE;
		}
		return FALSE;
}

function get_category_id( $category_name, $parent_id )
{
		global $db;
		global $ecs;
		$category_id = $db->getOne( "SELECT cat_id FROM ".$ecs->table( "category" ). " WHERE cat_name='".$category_name."' and parent_id='{$parent_id}'"  );
		return $category_id;
}

function fom_array( $input, $f = 0, $r = 0 )
{
		if ( count( $input ) < $f + $r )
		{
				return array( );
		}
		if ( $r != 0 )
		{
				$r = 0 - $r;
				$input = array_slice( $input, $f, $r );
		}
		else
		{
				$input = array_slice( $input, $f );
		}
		$tmp = array( );
		foreach ( $input as $value )
		{
				if ( $value )
				{
						$tmp[] = $value;
				}
		}
		return $tmp;
}

function fomweight( $input )
{
		if ( preg_match( "/kg/", $input ) )
		{
				return floatval( $input ) * 1000;
		}
		return floatval( $input );
}

function license( $url, $baseURL = 1 )
{
		$site = geturlbase( $baseURL );
		if ( is_array( $url ) )
		{
				if ( in_array( $site, $url ) )
				{
						$license = 1;
				}
				else
				{
						$license = 0;
				}
		}
		else if ( $site == $url )
		{
				$license = 1;
		}
		else
		{
				$license = 0;
		}
		if ( !$license )
		{
				exit( "未被授权的域名 ".$site."，请联系我们购买授权！" );
		}
}

function getUrlBase( $baseURL )
{
		$url = "http://".$_SERVER['HTTP_HOST'];
		$parse = parse_url( $url );
		$host = $parse['host'];
		if ( $baseURL )
		{
				return $host;
		}
		$h = explode( ".", $host );
		if ( FALSE === strpos( $host, "." ) || preg_match( "/^(\\d+\\.){3}(\\d+)\$/", $host ) )
		{
				$root = $host;
				return $root;
		}
		if ( preg_match( "/(?:ag|am|asia|at|be|biz|bz|ca|cc|co|com|cn|de|es|eu|firm|fm|fr|gen|gs|idv|in|ind|info|it|jobs|jp|me|mobi|ms|mx|net|nl|nom|nu|nz|org|tc|tk|tv|tw|uk|us|vg|ws)(?:\\.\\w{2})\$/", $host, $match ) )
		{
				array_pop( $h );
				array_pop( $h );
				$root = array_pop( $h ).".".$match[0];
				return $root;
		}
		if ( preg_match( "/(?:\\w{2,4})\$/", $host, $match ) )
		{
				array_pop( $h );
				$root = array_pop( $h ).".".$match[0];
		}
		return $root;
}

function han_in_array( $findme, $array )
{
		foreach ( $array as $key => $value )
		{
				if ( !( $findme == $value ) )
				{
						continue;
				}
				return TRUE;
		}
		return FALSE;
}

function han_trip_same( $array )
{
		$tmp = array( );
		foreach ( $array as $value )
		{
				if ( !han_in_array( $value, $tmp ) )
				{
						array_push( $tmp, $value );
				}
		}
		return $tmp;
}

function curlGet( $url, $rndtrueName )
{
		$fp = fopen( $rndtrueName, "w" );
		$curl = curl_init( );
		IS_PROXY ? curl_setopt( $curl, CURLOPT_PROXY, PROXY ) : "";
		$cookie_file = dirname( __FILE__ )."/cookie_".md5( basename( __FILE__ ) ).".txt";
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
		curl_setopt( $curl, CURLOPT_USERAGENT, USER_AGENT );
		@curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $curl, CURLOPT_AUTOREFERER, 1 );
		curl_setopt( $curl, CURLOPT_HTTPGET, 1 );
		curl_setopt( $curl, CURLOPT_COOKIEFILE, $cookie_file );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 120 );
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_FILE, $fp );
		$tmpInfo = curl_exec( $curl );
		if ( curl_errno( $curl ) )
		{
				echo "Errno".curl_error( $curl );
				return "";
		}
		curl_close( $curl );
		$revalues[0] = $rndtrueName;
		return $revalues;
}

function GetRemoteImage( $url, $rndtrueName )
{
		if ( DOWNLOAD_TYPE == "http" )
		{

				return httpget( $url, $rndtrueName );
		}
		if ( DOWNLOAD_TYPE == "curl" )
		{
				return curlget( $url, $rndtrueName );
		}
}

function httpGet( $url, $rndtrueName )
{
		$url = fom_url( $url );
		$revalues = array( );
		$ok = FALSE;
		$htd = new DedeHttpDown( );
		
		$htd->OpenUrl( $url );
		$sparr = array( "image/pjpeg", "image/jpeg", "image/gif", "image/png", "image/xpng", "image/wbmp" );
		if ( !in_array( $htd->GetHead( "content-type" ), $sparr ) )
		{
				return "";
		}
				//print_r($goods_img_array);
				//echo "url:".$url."\n";
				//echo "rndtrueName:".$rndtrueName."\n";
				//exit();					
		make_dir( dirname( $rndtrueName ) );
		$itype = $htd->GetHead( "content-type" );
		$ok = $htd->SaveToBin( $rndtrueName );
		if ( $ok )
		{
				$data = getimagesize( $rndtrueName );
				$revalues[0] = $rndtrueName;
				$revalues[1] = $data[0];
				$revalues[2] = $data[1];
		}
		$htd->Close( );
		if ( $ok )
		{
				return $revalues;
		}
		return "";
}

function print_array( $array )
{
		echo "<pre>";
		print_r( $array );
		echo "</pre>";
}

function write_error( $source_url )
{
		$fp = fopen( "error.log", "a+" );
		fwrite( $fp, $source_url."\r\n" );
		exit( "分析错误" );
}

function fom_url( $url )
{
		$url = str_replace( "à", "%c3%a0", $url );
		$url = str_replace( " ", "%20", $url );
		return $url;
}

function hexplode( $html, $break = "|||" )
{
		$html = explode( $break, $html );
		$html = fom_array( $html );
		return $html;
}

function get_sub_info( $name_array, $tag )
{
		$tag .= "=";
		if ( is_array( $name_array ) )
		{
				$values = array( );
				foreach ( $name_array as $key => $name )
				{
						if ( preg_match( "/:/", $name ) )
						{
								$name_sub_info = strstr( $name, $tag );
								$name_array[$key] = trim( preg_replace( "/:.*/", "", $name ) );
								$value = str_replace( $tag, "", $name_sub_info );
								array_push( $values, $value );
						}
						else
						{
								array_push( $values, "0" );
						}
				}
				return $values;
		}
		if ( is_string( $name_array ) )
		{
				$name_sub_info = strstr( $name_array, $tag );
				$name_array = trim( preg_replace( "/:.*/", "", $name_array ) );
				$value = str_replace( $tag, "", $name_sub_info );
				return $value;
		}
		return "0";
}

ini_set( "memory_limit", "520M" );
license( array( "localhost", "127.0.0.1", "www.temperqueen.com", "temperqueen.com.com" ), 1 );
set_time_limit( 0 );
ini_set( "ignore_user_abort", TRUE );
ignore_user_abort( TRUE );
define( "FREE", FALSE );
define( "IN_ECS", TRUE );
if ( !defined( "IN_ECS" ) )
{
		exit( "Hacking attempt" );
}
define( "ECS_ADMIN", TRUE );
error_reporting( E_ALL );
if ( __FILE__ == "" )
{
		exit( "Fatal error code: 0" );
}
@ini_set( "memory_limit", "64M" );
@ini_set( "session.cache_expire", 180 );
@ini_set( "session.use_trans_sid", 0 );
@ini_set( "session.use_cookies", 1 );
@ini_set( "session.auto_start", 0 );
@ini_set( "display_errors", 1 );
if ( DIRECTORY_SEPARATOR == "\\" )
{
		@ini_set( "include_path", ".;".ROOT_PATH );
}
else
{
		@ini_set( "include_path", ".:".ROOT_PATH );
}
if ( file_exists( "../data/config.php" ) )
{
		include( "../data/config.php" );
}
else
{
		include( "../includes/config.php" );
}
if ( !defined( "ADMIN_PATH" ) )
{
		define( "ADMIN_PATH", "admin" );
}
require( "ecshop.config.php" );
if ( !defined( "DEBUG_MODE" ) )
{
		define( "DEBUG_MODE", 0 );
}
if ( "5.1" <= PHP_VERSION && !empty( $timezone ) )
{
		date_default_timezone_set( $timezone );
}
if ( isset( $_SERVER['PHP_SELF'] ) )
{
		define( "PHP_SELF", $_SERVER['PHP_SELF'] );
}
else
{
		define( "PHP_SELF", $_SERVER['SCRIPT_NAME'] );
}
require( ROOT_PATH."includes/inc_constant.php" );
require( ROOT_PATH."includes/cls_ecshop.php" );
require( ROOT_PATH."includes/cls_error.php" );
require( ROOT_PATH."includes/lib_time.php" );
require( ROOT_PATH."includes/lib_base.php" );
require( ROOT_PATH."includes/lib_common.php" );
require( ROOT_PATH.ADMIN_PATH."/includes/lib_main.php" );
require( ROOT_PATH.ADMIN_PATH."/includes/cls_exchange.php" );
require( ROOT_PATH."languages/zh_cn/admin/goods.php" );
if ( !get_magic_quotes_gpc( ) )
{
		if ( !empty( $_GET ) )
		{
				$GLOBALS['_GET'] = addslashes_deep( $_GET );
		}
		if ( !empty( $_POST ) )
		{
				$GLOBALS['_POST'] = addslashes_deep( $_POST );
		}
		$GLOBALS['_REQUEST'] = addslashes_deep( $_REQUEST );
}

if ( strpos( PHP_SELF, ".php/" ) !== FALSE )
{
		ecs_header( "Location:".substr( PHP_SELF, 0, strpos( PHP_SELF, ".php/" ) + 4 )."\n" );
		exit( );
}
$ecs = new ECS( $db_name, $prefix );
define( "DATA_DIR", $ecs->data_dir( ) );
define( "IMAGE_DIR", $ecs->image_dir( ) );
require( ROOT_PATH."includes/cls_mysql.php" );
$db = new cls_mysql( $db_host, $db_user, $db_pass, $db_name );
$db_host = $db_user = $db_pass = $db_name = NULL;
$err = new ecs_error( "message.htm" );
$_CFG = load_config( );
require( ROOT_PATH."languages/".$_CFG['lang']."/admin/common.php" );
require( ROOT_PATH."languages/".$_CFG['lang']."/admin/log_action.php" );
if ( file_exists( ROOT_PATH."languages/".$_CFG['lang']."/admin/".basename( PHP_SELF ) ) )
{
		include( ROOT_PATH."languages/".$_CFG['lang']."/admin/".basename( PHP_SELF ) );
}
if ( !file_exists( "../temp/caches" ) )
{
		@mkdir( "../temp/caches", 511 );
		@chmod( "../temp/caches", 511 );
}
if ( !file_exists( "../temp/compiled/admin" ) )
{
		@mkdir( "../temp/compiled/admin", 511 );
		@chmod( "../temp/compiled/admin", 511 );
}
clearstatcache( );
require( ROOT_PATH."includes/cls_template.php" );
$smarty = new cls_template( );
$smarty->template_dir = ROOT_PATH.ADMIN_PATH."/templates";
$smarty->compile_dir = ROOT_PATH."temp/compiled/admin";
if ( ( DEBUG_MODE & 2 ) == 2 )
{
		$smarty->force_compile = TRUE;
}
$smarty->assign( "lang", $_LANG );
$smarty->assign( "help_open", $_CFG['help_open'] );
if ( isset( $_CFG['enable_order_check'] ) )
{
		$smarty->assign( "enable_order_check", $_CFG['enable_order_check'] );
}
else
{
		$smarty->assign( "enable_order_check", 0 );
}
header( "content-type: text/html; charset=".EC_CHARSET );
header( "Expires: Fri, 14 Mar 1980 20:53:00 GMT" );
header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );
if ( ( DEBUG_MODE & 1 ) == 1 )
{
		error_reporting( E_ALL );
}
else
{
		error_reporting( E_ALL ^ E_NOTICE );
}
if ( ( DEBUG_MODE & 4 ) == 4 )
{
		include( ROOT_PATH."includes/lib.debug.php" );
}
if ( gzip_enabled( ) )
{
		ob_start( "ob_gzhandler" );
}
else
{
		ob_start( );
}
define( "IN_ECS", TRUE );
require_once( ROOT_PATH."/".ADMIN_PATH."/includes/lib_goods.php" );
include_once( ROOT_PATH."/includes/cls_image.php" );
class down_image extends cls_image
{
		public function __construct( $bgcolor = "" )
		{
				parent::__construct( $bgcolor );
		}

		public function upload_image( $remote_goods_img, $dir = "", $img_name = "" )
		{
				if ( empty( $dir ) )
				{
						$dir = date( "Ym" );
						$dir = ROOT_PATH.$this->images_dir."/".$dir."/";
				}
				else
				{
						$dir = ROOT_PATH.$this->data_dir."/".$dir."/";
						if ( $img_name )
						{
								$img_name = $dir.$img_name;
						}
				}
				if ( !file_exists( $dir ) || !make_dir( $dir ) )
				{
						$this->error_msg = sprintf( $GLOBALS['_LANG']['directory_readonly'], $dir );
						$this->error_no = ERR_DIRECTORY_READONLY;
						return FALSE;
				}
				if ( empty( $img_name ) )
				{
						$img_name = $this->unique_name( $dir );
						$img_name = $dir.$img_name.$this->get_filetype( $remote_goods_img );
				}
			
				if ( getremoteimage( $remote_goods_img, $img_name ) )
				{

						return str_replace( ROOT_PATH, "", $img_name );
				}
				return FALSE;
		}

		public function get_filetype( $remote_goods_img )
		{
				$pathinfo_goods_img = pathinfo( $remote_goods_img );
				return ".".$pathinfo_goods_img['extension'];
		}

		public function make_thumb( $img, $thumb_width = 0, $thumb_height = 0, $path = "", $bgcolor = "" )
		{
				$gd = $this->gd_version( );
				if ( $gd == 0 )
				{
						$this->error_msg = $GLOBALS['_LANG']['missing_gd'];
						return FALSE;
				}
				if ( $thumb_width == 0 && $thumb_height == 0 )
				{
						return str_replace( ROOT_PATH, "", str_replace( "\\", "/", realpath( $img ) ) );
				}
				$org_info = @getimagesize( $img );
				if ( !$org_info )
				{
						$this->error_msg = sprintf( $GLOBALS['_LANG']['missing_orgin_image'], $img );
						$this->error_no = ERR_IMAGE_NOT_EXISTS;
						return FALSE;
				}
				if ( !$this->check_img_function( $org_info[2] ) )
				{
						$this->error_msg = sprintf( $GLOBALS['_LANG']['nonsupport_type'], $this->type_maping[$org_info[2]] );
						$this->error_no = ERR_NO_GD;
						return FALSE;
				}
				$img_org = $this->img_resource( $img, $org_info[2] );
				$scale_org = $org_info[0] / $org_info[1];
				if ( $thumb_width == 0 )
				{
						$thumb_width = $thumb_height * $scale_org;
				}
				if ( $thumb_height == 0 )
				{
						$thumb_height = $thumb_width / $scale_org;
				}
				if ( $gd == 2 )
				{
						$img_thumb = imagecreatetruecolor( $thumb_width, $thumb_height );
				}
				else
				{
						$img_thumb = imagecreate( $thumb_width, $thumb_height );
				}
				if ( empty( $bgcolor ) )
				{
						$bgcolor = $this->bgcolor;
				}
				$bgcolor = trim( $bgcolor, "#" );
				sscanf( $bgcolor, "%2x%2x%2x", $red, $green, $blue );
				$clr = imagecolorallocate( $img_thumb, $red, $green, $blue );
				imagefilledrectangle( $img_thumb, 0, 0, $thumb_width, $thumb_height, $clr );
				if ( $org_info[1] / $thumb_height < $org_info[0] / $thumb_width )
				{
						$lessen_width = $thumb_width;
						$lessen_height = $thumb_width / $scale_org;
				}
				else
				{
						$lessen_width = $thumb_height * $scale_org;
						$lessen_height = $thumb_height;
				}
				$dst_x = ( $thumb_width - $lessen_width ) / 2;
				$dst_y = ( $thumb_height - $lessen_height ) / 2;
				if ( $gd == 2 )
				{
						imagecopyresampled( $img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1] );
				}
				else
				{
						imagecopyresized( $img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info[0], $org_info[1] );
				}
				if ( empty( $path ) )
				{
						$dir = ROOT_PATH.$this->images_dir."/".date( "Ym" )."/";
				}
				else
				{
						$dir = $path;
				}
				if ( !file_exists( $dir ) || !make_dir( $dir ) )
				{
						$this->error_msg = sprintf( $GLOBALS['_LANG']['directory_readonly'], $dir );
						$this->error_no = ERR_DIRECTORY_READONLY;
						return FALSE;
				}
				$filename = $this->unique_name( $dir );
				if ( function_exists( "imagejpeg" ) )
				{
						$filename .= ".jpg";
						imagejpeg( $img_thumb, $dir.$filename );
				}
				else if ( function_exists( "imagegif" ) )
				{
						$filename .= ".gif";
						imagegif( $img_thumb, $dir.$filename );
				}
				else if ( function_exists( "imagepng" ) )
				{
						$filename .= ".png";
						imagepng( $img_thumb, $dir.$filename );
				}
				else
				{
						$this->error_msg = $GLOBALS['_LANG']['creating_failure'];
						$this->error_no = ERR_NO_GD;
						return FALSE;
				}
				imagedestroy( $img_thumb );
				imagedestroy( $img_org );
				if ( file_exists( $dir.$filename ) )
				{
						return str_replace( ROOT_PATH, "", $dir ).$filename;
				}
				$this->error_msg = $GLOBALS['_LANG']['writting_failure'];
				$this->error_no = ERR_DIRECTORY_READONLY;
				return FALSE;
		}

}

$image = new down_image( $_CFG['bgcolor'] );
$exc = new exchange( $ecs->table( "goods" ), $db, "goods_id", "goods_name" );
$download = new DedeHttpDown( );
$i = 0;

if ( !count( $_POST ) )
{
		$common = "goods_name=[标签:商品名称]&price=[标签:本店价]&brand=[标签:品牌]&image=[标签:图片]&fenlei=[标签:分类]&goods_desc=[标签:详细描述]&mktprice=[标签:市场价]&keywords=[标签:关键字]&goods_brief=[标签:简单描述]";
		$sql = "SELECT * FROM ".$ecs->table( "attribute" )." a,".$ecs->table( "goods_type" ).( " g where a.cat_id=g.cat_id and g.cat_id='".$goods_type."'" );
		$res = $db->query( $sql );
		while ( $row = $db->fetchRow( $res ) )
		{
				$label[$i++] = "attr[".$row['attr_id']."]=[标签:".str_replace( "/", "", $row['attr_name'] )."]";
		}
		if ( isset( $label ) && is_array( $label ) && count( $label ) )
		{
				$label = implode( "&", $label );
				$label = "&".$label;
		}
		else
		{
				$label = "";
		}
		$tip = "<br><font color='red'>以下内容为可选内容，如有需要也请添加(非必须),不需要请不要添加</font><br>&weight=[标签:重量]&bn=[标签:货号]";
		if ( empty( $label ) )
		{
				$link['0'] = array(
						"href" => basename( __FILE__ ),
						"text" => ""
				);
				sys_msg( "<font color=red><b>您选择的商品类型无额外属性</b></font> <b>接口作者:</b> superlee, <b>QQ:</b> 4292423", 0, $link, FALSE );
		}
		$link['0'] = array(
				"href" => basename( __FILE__ ),
				"text" => "<font color=red><b>请将上面的自定义商品属性信息添加到发布模块</b></font> <b>接口作者:</b> superlee, <b>QQ:</b> 4292423"
		);
		sys_msg( $label, 0, $link, FALSE );
}
if ( file_exists( "chuli.php" ) )
{
		include( "chuli.php" );
}

$type_name = isset( $type_name ) && $type_name ? $type_name : "";
if ( isset( $_POST['params'] ) && $_POST['params'] && isset( $type_name ) && $type_name )
{
		$objParams = new params( $_POST['params'] );
		$params = $objParams->run( );
		$objGoodsType = new goods_type( );
		$objAttribute = new attribute( );
		$attr_names = array( );
		$attr_group = "";
		$GLOBALS['_POST']['attr'] = array( );
		foreach ( $params as $group_name => $group )
		{
				$attr_group .= $group_name."\n";
				foreach ( $group as $attr_name => $attr_value )
				{
						$attr_names[$attr_name] = $attr_value;
				}
		}
		$goods_type = $objGoodsType->getId( $type_name, $attr_group );
		$group_list = $objGoodsType->getGroupList( );
		foreach ( $params as $group_name => $group )
		{
				foreach ( $group as $attr_name => $attr_value )
				{
						$attr_id = $objAttribute->insert( $attr_name, $goods_type, $group_list[$group_name] );
						$GLOBALS['_POST']['attr'][$attr_id] = $attr_value;
				}
		}
}
if ( $_POST['categories'] )
{
		$categories = explode( $fenlei_break, $_POST['categories'] );
		$categories = fom_array( $categories, $f, $r );
		foreach ( $categories as $key => $category_name )
		{
				if ( category_exist( $category_name, $parent_id ) )
				{
						$catgory_id = get_category_id( $category_name, $parent_id );
				}
				else
				{
						$catgory_id = add_category( $category_name, $parent_id );
				}
				$parent_id = $catgory_id;
		}
}
else
{
		exit( "对不起，您没有上传分类信息" );
}

if ( $_REQUEST['act'] == "insert" )
{

		$code = empty( $_REQUEST['extension_code'] ) ? "" : trim( $_REQUEST['extension_code'] );
		$proc_thumb = isset( $GLOBALS['shop_id'] ) && 0 < $GLOBALS['shop_id'] ? FALSE : TRUE;
		if ( $_POST['goods_sn'] )
		{
				$sql = "SELECT COUNT(*) FROM ".$ecs->table( "goods" ).( " WHERE goods_sn = '".$_POST['goods_sn']."' AND is_delete = 0 AND goods_id <> '{$_POST['goods_id']}'" );
				if ( 0 < $db->getOne( $sql ) )
				{
						sys_msg( $_LANG['goods_sn_exists'], 1, array( ), FALSE );
				}
		}
		$brand_name = trim( $_POST['brand_name'] );
		if ( !empty( $brand_name ) && brand_exist( $brand_name ) )
		{
				$brand_id = get_brand_id( $brand_name );
		}
		else if ( !empty( $brand_name ) )
		{
				$brand_id = add_brand( $brand_name );
		}
		else
		{
				$brand_id = 0;
		}
		$php_maxsize = ini_get( "upload_max_filesize" );
		$htm_maxsize = "2M";
		$is_insert = $_REQUEST['act'] == "insert";
		$goods_img = "";
		$goods_thumb = "";
		$original_img = "";
		$goods_img_array = explode( "|||", $_POST['goods_img'] );
		$goods_img_array = fom_array( $goods_img_array );
		$goods_img_array = han_trip_same( $goods_img_array );

		$img_descs = isset( $_POST['img_desc'] ) && $_POST['img_desc'] ? explode( "|||", $_POST['img_desc'] ) : array_fill( "0", @count( $goods_img_array ), "" );
		if ( 0 < count( $goods_img_array ) )
		{
				$main_image = array_shift( $goods_img_array );
				array_shift( $img_descs );
		}
		while ( !empty( $main_image ) || !$remote_image )
		{
				$original_img = $image->upload_image( $main_image );
				if ( $original_img || !( 0 < count( $goods_img_array ) ) )
				{
						break;
				}
				$main_image = array_shift( $goods_img_array );
				array_shift( $img_descs );
		}

		if ( $original_img )
		{
			//exit($original_img);

						$goods_img = $original_img;
						if ( $_CFG['auto_generate_gallery'] )
						{
								$img = $original_img;
								$pos = strpos( basename( $img ), "." );
								$newname = dirname( $img )."/".$image->random_filename( ).substr( basename( $img ), $pos );
								if ( !copy( "../".$img, "../".$newname ) )
								{
										sys_msg( "fail to copy file: ".realpath( "../".$img ), 1, array( ), FALSE );
								}
								$img = $newname;
								$gallery_img = $img;
								$gallery_thumb = $img;
						}
						if ( !$proc_thumb )
						{
								//break;
						}
						else
						{
								if ( 0 < $image->gd_version( ) )
								{
										if ( $_CFG['image_width'] != 0 || $_CFG['image_height'] != 0 )
										{
												$goods_img = $image->make_thumb( "../".$goods_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height'] );
												if ( $goods_img === FALSE )
												{
														sys_msg( $image->error_msg( ), 1, array( ), FALSE );
												}
										}
										if ( $_CFG['auto_generate_gallery'] )
										{
												$newname = dirname( $img )."/".$image->random_filename( ).substr( basename( $img ), $pos );
												if ( !copy( "../".$img, "../".$newname ) )
												{
														sys_msg( "fail to copy file: ".realpath( "../".$img ), 1, array( ), FALSE );
												}
												$gallery_img = $newname;
										}
										if ( 0 < intval( $_CFG['watermark_place'] ) && !empty( $GLOBALS['_CFG']['watermark'] ) )
										{
												if ( $image->add_watermark( "../".$goods_img, "", $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha'] ) === FALSE )
												{
														sys_msg( $image->error_msg( ), 1, array( ), FALSE );
												}
												if ( $_CFG['auto_generate_gallery'] && $image->add_watermark( "../".$gallery_img, "", $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha'] ) === FALSE )
												{
														sys_msg( $image->error_msg( ), 1, array( ), FALSE );
												}
										}
										if ( $_CFG['auto_generate_gallery'] && ( $_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0 ) )
										{
												$gallery_thumb = $image->make_thumb( "../".$img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'] );
												if ( $gallery_thumb === FALSE )
												{
														sys_msg( $image->error_msg( ), 1, array( ), FALSE );
												}
										}
								}
						}

				if ( !$proc_thumb && !isset( $_POST['auto_thumb'] ) && empty( $original_img ) )
				{
						//break;
				}
				if ( $_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0 )
				{
						$goods_thumb = $image->make_thumb( "../".$original_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'] );
						if ( $goods_thumb === FALSE )
						{
							sys_msg( $image->error_msg( ), 1, array( ), FALSE );
						}
						
				}
				else
				{
						$goods_thumb = $original_img;
				}
		}
		if ( empty( $_POST['goods_sn'] ) )
		{
				$max_id = $is_insert ? $db->getOne( "SELECT MAX(goods_id) + 1 FROM ".$ecs->table( "goods" ) ) : $_REQUEST['goods_id'];
				$goods_sn = generate_goods_sn( $max_id );
		}
		else
		{
				$goods_sn = $_POST['goods_sn'];
		}
		$shop_price = !empty( $_POST['shop_price'] ) ? $shop_price_factor * $_POST['shop_price'] : 0;
		$market_price = !empty( $_POST['market_price'] ) ? $market_price_factor * $_POST['market_price'] : 0;
		$promote_price = !empty( $_POST['promote_price'] ) ? floatval( $_POST['promote_price'] ) : 0;
		$is_promote = empty( $promote_price ) ? 0 : 1;
		$promote_start_date = !$is_promote && !empty( $_POST['promote_start_date'] ) ? local_strtotime( $_POST['promote_start_date'] ) : 0;
		$promote_end_date = !$is_promote && !empty( $_POST['promote_end_date'] ) ? local_strtotime( $_POST['promote_end_date'] ) : 0;
		$goods_weight = !empty( $_POST['goods_weight'] ) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;
		$is_best = isset( $_POST['is_best'] ) ? 1 : 0;
		$is_new = isset( $_POST['is_new'] ) ? 1 : 0;
		$is_hot = isset( $_POST['is_hot'] ) ? 1 : 0;
		$is_on_sale = isset( $_POST['is_on_sale'] ) ? 1 : 0;
		$is_alone_sale = isset( $_POST['is_alone_sale'] ) ? 1 : 0;
		$is_shipping = isset( $_POST['is_shipping'] ) ? 1 : 0;
		$goods_number = isset( $_POST['goods_number'] ) ? $_POST['goods_number'] : 0;
		$warn_number = isset( $_POST['warn_number'] ) ? $_POST['warn_number'] : 0;
		$give_integral = isset( $_POST['give_integral'] ) ? intval( $_POST['give_integral'] ) : "-1";
		$rank_integral = isset( $_POST['rank_integral'] ) ? intval( $_POST['rank_integral'] ) : "-1";
		$suppliers_id = isset( $_POST['suppliers_id'] ) ? intval( $_POST['suppliers_id'] ) : "0";
		$goods_name_style = $_POST['goods_name_color']."+".$_POST['goods_name_style'];
		$goods_img = empty( $goods_img ) && !empty( $main_image ) || goods_parse_url( $main_image ) && $remote_image ? htmlspecialchars( trim( $main_image ) ) : $goods_img;
		$goods_thumb = empty( $goods_thumb ) && !empty( $_POST['goods_thumb'] ) || goods_parse_url( $_POST['goods_thumb'] ) && $remote_image ? htmlspecialchars( trim( $_POST['goods_thumb'] ) ) : $goods_thumb;
		$goods_thumb = empty( $goods_thumb ) && isset( $_POST['auto_thumb'] ) ? $goods_img : $goods_thumb;
		if ( $is_insert )
		{
				if ( $code == "" )
				{
						$sql = "INSERT INTO ".$ecs->table( "goods" )." (goods_name, goods_name_style, goods_sn, cat_id, brand_id, shop_price, market_price, is_promote, promote_price, promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, seller_note, goods_weight, goods_number, warn_number, integral, give_integral, is_best, is_new, is_hot, is_on_sale, is_alone_sale, is_shipping, goods_desc, add_time, last_update, goods_type, rank_integral, suppliers_id)".( "VALUES ('".$_POST['goods_name']."', '{$goods_name_style}', '{$goods_sn}', '{$catgory_id}', " ).( "'".$brand_id."', '{$shop_price}', '{$market_price}', '{$is_promote}','{$promote_price}', " ).( "'".$promote_start_date."', '{$promote_end_date}', '{$goods_img}', '{$goods_thumb}', '{$original_img}', " ).( "'".$_POST['keywords']."', '{$_POST['goods_brief']}', '{$_POST['seller_note']}', '{$goods_weight}', '{$goods_number}'," ).( " '".$warn_number."', '{$_POST['integral']}', '{$give_integral}', '{$is_best}', '{$is_new}', '{$is_hot}', '{$is_on_sale}', '{$is_alone_sale}', {$is_shipping}, " ).( " '".$_POST['goods_desc']."', '" ).gmtime( )."', '".gmtime( ).( "', '".$goods_type."', '{$rank_integral}', '{$suppliers_id}')" );
				}
				else
				{
						$sql = "INSERT INTO ".$ecs->table( "goods" )." (goods_name, goods_name_style, goods_sn, cat_id, brand_id, shop_price, market_price, is_promote, promote_price, promote_start_date, promote_end_date, goods_img, goods_thumb, original_img, keywords, goods_brief, seller_note, goods_weight, goods_number, warn_number, integral, give_integral, is_best, is_new, is_hot, is_real, is_on_sale, is_alone_sale, is_shipping, goods_desc, add_time, last_update, goods_type, extension_code, rank_integral)".( "VALUES ('".$_POST['goods_name']."', '{$goods_name_style}', '{$goods_sn}', '{$catgory_id}', " ).( "'".$brand_id."', '{$shop_price}', '{$market_price}', '{$is_promote}','{$promote_price}', " ).( "'".$promote_start_date."', '{$promote_end_date}', '{$goods_img}', '{$goods_thumb}', '{$original_img}', " ).( "'".$_POST['keywords']."', '{$_POST['goods_brief']}', '{$_POST['seller_note']}', '{$goods_weight}', '{$goods_number}'," ).( " '".$warn_number."', '{$_POST['integral']}', '{$give_integral}', '{$is_best}', '{$is_new}', '{$is_hot}', 0, '{$is_on_sale}', '{$is_alone_sale}', {$is_shipping}, " ).( " '".$_POST['goods_desc']."', '" ).gmtime( )."', '".gmtime( ).( "', '".$goods_type."', '{$code}', '{$rank_integral}')" );
				}
		}
		else
		{
				$sql = "SELECT goods_thumb, goods_img, original_img  FROM ".$ecs->table( "goods" ).( " WHERE goods_id = '".$_REQUEST['goods_id']."'" );
				$row = $db->getRow( $sql );
				if ( $proc_thumb )
				{
						if ( $goods_img && $row['goods_img'] && !goods_parse_url( $row['goods_img'] ) )
						{
								@unlink( ROOT_PATH.$row['goods_img'] );
								@unlink( ROOT_PATH.$row['original_img'] );
						}
						if ( $proc_thumb && $goods_thumb && $row['goods_thumb'] && !goods_parse_url( $row['goods_thumb'] ) )
						{
								@unlink( ROOT_PATH.$row['goods_thumb'] );
						}
				}
				$sql = "UPDATE ".$ecs->table( "goods" )." SET ".( "goods_name = '".$_POST['goods_name']."', " ).( "goods_name_style = '".$goods_name_style."', " ).( "goods_sn = '".$goods_sn."', " ).( "cat_id = '".$catgory_id."', " ).( "brand_id = '".$brand_id."', " ).( "shop_price = '".$shop_price."', " ).( "market_price = '".$market_price."', " ).( "is_promote = '".$is_promote."', " ).( "promote_price = '".$promote_price."', " ).( "promote_start_date = '".$promote_start_date."', " ).( "suppliers_id = '".$suppliers_id."', " ).( "promote_end_date = '".$promote_end_date."', " );
				if ( $goods_img )
				{
						$sql .= "goods_img = '".$goods_img."', original_img = '{$original_img}', ";
				}
				if ( $goods_thumb )
				{
						$sql .= "goods_thumb = '".$goods_thumb."', ";
				}
				if ( $code != "" )
				{
						$sql .= "is_real=0, extension_code='".$code."', ";
				}
				$sql .= "keywords = '".$_POST['keywords']."', ".( "goods_brief = '".$_POST['goods_brief']."', " ).( "seller_note = '".$_POST['seller_note']."', " ).( "goods_weight = '".$goods_weight."'," ).( "goods_number = '".$goods_number."', " ).( "warn_number = '".$warn_number."', " ).( "integral = '".$_POST['integral']."', " ).( "give_integral = '".$give_integral."', " ).( "rank_integral = '".$rank_integral."', " ).( "is_best = '".$is_best."', " ).( "is_new = '".$is_new."', " ).( "is_hot = '".$is_hot."', " ).( "is_on_sale = '".$is_on_sale."', " ).( "is_alone_sale = '".$is_alone_sale."', " ).( "is_shipping = '".$is_shipping."', " ).( "goods_desc = '".$_POST['goods_desc']."', " )."last_update = '".gmtime( )."', ".( "goods_type = '".$goods_type."' " ).( "WHERE goods_id = '".$_REQUEST['goods_id']."' LIMIT 1" );
		}
		$db->query( $sql );
		$goods_id = $is_insert ? $db->insert_id( ) : $_REQUEST['goods_id'];
		if ( function_exists( write_price_log ) )
		{
				write_price_log( $_POST['shop_price'], $price_image, $goods_id );
		}
		if ( FREE )
		{
				$GLOBALS['_POST']['attr'] = array( );
				$goods_img_array = array( );
		}

		if ( isset( $_POST['attr'] ) && $_POST['attr'] )
		{
				$objAttribute = isset( $objAttribute ) ? $objAttribute : new attribute( );
				$attr_input_type = array( );
				$attr_values = array( );
				$attr_type = array( );
				$objAttribute->getAttr( $goods_type, $attr_input_type, $attr_values, $attr_type );
				$GLOBALS['_POST']['attr_id_list'] = array( );
				$GLOBALS['_POST']['attr_value_list'] = array( );
				$GLOBALS['_POST']['attr_price_list'] = array( );
				$objAttribute->attrFormat( $_POST['attr'], $attr_input_type, $attr_values, $attr_type );
				$goods_attr_list = array( );
				$keywords_arr = explode( " ", $_POST['keywords'] );
				$keywords_arr = array_flip( $keywords_arr );
				if ( isset( $keywords_arr[''] ) )
				{
						unset( $keywords_arr[''] );
				}
				$sql = "SELECT attr_id, attr_index FROM ".$ecs->table( "attribute" ). " WHERE cat_id = '".$goods_type."'";
				$attr_res = $db->query( $sql );
				$attr_list = array( );
				while ( $row = $db->fetchRow( $attr_res ) )
				{
						$attr_list[$row['attr_id']] = $row['attr_index'];
				}
				$sql = "SELECT * FROM ".$ecs->table( "goods_attr" ). " WHERE goods_id = '".$goods_id."'" ;
				$res = $db->query( $sql );
				while ( $row = $db->fetchRow( $res ) )
				{
						$goods_attr_list[$row['attr_id']][$row['attr_value']] = array(
								"sign" => "delete",
								"goods_attr_id" => $row['goods_attr_id']
						);
				}
				if ( isset( $_POST['attr_id_list'] ) )
				{
						foreach ( $GLOBALS['_POST']['attr_id_list'] as $key => $attr_id )
						{
								$attr_value = $_POST['attr_value_list'][$key];
								$attr_price = $_POST['attr_price_list'][$key];
								if ( !empty( $attr_value ) )
								{
										if ( isset( $goods_attr_list[$attr_id][$attr_value] ) )
										{
												$goods_attr_list[$attr_id][$attr_value]['sign'] = "update";
												$goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
										}
										else
										{
												$goods_attr_list[$attr_id][$attr_value]['sign'] = "insert";
												$goods_attr_list[$attr_id][$attr_value]['attr_price'] = $attr_price;
										}
										$val_arr = explode( " ", $attr_value );
										foreach ( $val_arr as $k => $v )
										{
												if ( !isset( $keywords_arr[$v] ) )
												{
														if ( $attr_list[$attr_id] == "1" )
														{
																$keywords_arr[$v] = $v;
														}
												}
										}
								}
						}
				}
				$keywords = join( " ", array_flip( $keywords_arr ) );
				$sql = "UPDATE ".$ecs->table( "goods" ). " SET keywords = '".$keywords."' WHERE goods_id = '{$goods_id}' LIMIT 1" ;
				$db->query( $sql );
				foreach ( $goods_attr_list as $attr_id => $attr_value_list )
				{
						foreach ( $attr_value_list as $attr_value => $info )
						{
								if ( $info['sign'] == "insert" )
								{
										$sql = "INSERT INTO ".$ecs->table( "goods_attr" )." (attr_id, goods_id, attr_value, attr_price)". " VALUES ('".$attr_id."', '{$goods_id}', '{$attr_value}', '{$info['attr_price']}')" ;
								}
								else if ( $info['sign'] == "update" )
								{
										$sql = "UPDATE ".$ecs->table( "goods_attr" ). " SET attr_price = '".$info['attr_price']."' WHERE goods_attr_id = '{$info['goods_attr_id']}' LIMIT 1" ;
								}
								else
								{
										$sql = "DELETE FROM ".$ecs->table( "goods_attr" ). " WHERE goods_attr_id = '".$info['goods_attr_id']."' LIMIT 1" ;
								}
								$db->query( $sql );
						}
				}
		}
		if ( isset( $_POST['user_rank'], $_POST['user_price'] ) )
		{
				handle_member_price( $goods_id, $_POST['user_rank'], $_POST['user_price'] );
		}
		if ( isset( $_POST['volume_number'], $_POST['volume_price'] ) )
		{
				$temp_num = array_count_values( $_POST['volume_number'] );
				foreach ( $temp_num as $v )
				{
						if ( !( 1 < $v ) )
						{
								continue;
						}
						break;
				}
				handle_volume_price( $goods_id, $_POST['volume_number'], $_POST['volume_price'] );
		}
		if ( isset( $_POST['other_cat'] ) )
		{
				handle_other_cat( $goods_id, array_unique( $_POST['other_cat'] ) );
		}
		if ( $is_insert )
		{
				handle_link_goods( $goods_id );
				handle_group_goods( $goods_id );
				handle_goods_article( $goods_id );
		}
		$original_img = reformat_image_name( "goods", $goods_id, $original_img, "source" );
		
		$goods_img = reformat_image_name( "goods", $goods_id, $goods_img, "goods" );
		$goods_thumb = reformat_image_name( "goods_thumb", $goods_id, $goods_thumb, "thumb" );
		if ( $goods_img !== FALSE )
		{
				$db->query( "UPDATE ".$ecs->table( "goods" ). " SET goods_img = '".$goods_img."' WHERE goods_id='{$goods_id}'" );
		}
		if ( $original_img !== FALSE )
		{
				$db->query( "UPDATE ".$ecs->table( "goods" ). " SET original_img = '".$original_img."' WHERE goods_id='{$goods_id}'" );
		}
		if ( $goods_thumb !== FALSE )
		{
				$db->query( "UPDATE ".$ecs->table( "goods" ). " SET goods_thumb = '".$goods_thumb."' WHERE goods_id='{$goods_id}'" ) ;
		}
		if ( isset( $img ) )
		{
				$img = reformat_image_name( "gallery", $goods_id, $img, "source" );
				$gallery_img = reformat_image_name( "gallery", $goods_id, $gallery_img, "goods" );
				$gallery_thumb = reformat_image_name( "gallery_thumb", $goods_id, $gallery_thumb, "thumb" );
				$sql = "INSERT INTO ".$ecs->table( "goods_gallery" )." (goods_id, img_url, img_desc, thumb_url, img_original) ". " VALUES ('".$goods_id."', '{$gallery_img}', '', '{$gallery_thumb}', '{$img}')" ;
				$db->query( $sql );
		}
		if ( $goods_img_array )
		{
				add_gallery_image( $goods_id, $goods_img_array, $img_descs );
		}
		if ( !$is_insert || isset( $_POST['old_img_desc'] ) )
		{
				foreach ( $GLOBALS['_POST']['old_img_desc'] as $img_id => $img_desc )
				{
						$sql = "UPDATE ".$ecs->table( "goods_gallery" ). " SET img_desc = '".$img_desc."' WHERE img_id = '{$img_id}' LIMIT 1" ;
						$db->query( $sql );
				}
		}
		if ( $proc_thumb && !$_CFG['retain_original_img'] && !empty( $original_img ) )
		{
				$db->query( "UPDATE ".$ecs->table( "goods" ). " SET original_img='' WHERE `goods_id`='".$goods_id."'" ) ;
				$db->query( "UPDATE ".$ecs->table( "goods_gallery" ). " SET img_original='' WHERE `goods_id`='".$goods_id."'" ) ;
				@unlink( "../".$original_img );
				@unlink( "../".$img );
		}
		clear_cache_files( );
		echo "添加商品成功";
}

$link = array( );
if ( check_goods_specifications_exist( $goods_id ) )
{
		$link[0] = array(
				"href" => "goods.php?act=product_list&goods_id=".$goods_id,
				"text" => $_LANG['product']
		);
}
if ( $code == "virtual_card" )
{
		$link[1] = array(
				"href" => "virtual_card.php?act=replenish&goods_id=".$goods_id,
				"text" => $_LANG['add_replenish']
		);
}
if ( $is_insert )
{
		$link[2] = add_link( $code );
}
$link[3] = list_link( $is_insert, $code );
if ( empty( $is_insert ) )
{
		$key_array = array_keys( $link );
		krsort( $link );
		$link = array_combine( $key_array, $link );
}
sys_msg( $is_insert ? $_LANG['add_goods_ok'] : $_LANG['edit_goods_ok'], 0, $link );
?>
