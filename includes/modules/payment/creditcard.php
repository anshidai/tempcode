<?php

/**
 * ECSHOP globebill 信用卡支付插件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: pan $
 * $Id: creditcard.php 17217 2013-05-23 13:56:08Z pan $
 */
include("Mobile_Detect.php");
if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/payment/creditcard.php';

if (file_exists($payment_lang))
{
	global $_LANG;

	include_once($payment_lang);
}

/**
 * 模块信息
 */
if (isset($set_modules) && $set_modules == true)
{
	$i = isset($modules) ? count($modules) : 0;

	/* 代码 */
	$modules[$i]['code'] = basename(__FILE__, '.php');

	/* 描述对应的语言项 */
	$modules[$i]['desc'] = 'creditcard_desc';

	/* 是否支持货到付款 */
	$modules[$i]['is_cod'] = '0';

	/* 是否支持在线支付 */
	$modules[$i]['is_online'] = '1';

	/* 作者 */
	$modules[$i]['author']  = 'GLOBEBILL钱宝支付';

	/* 网址 */
	$modules[$i]['website'] = 'http://www.globebill.com.cn';

	/* 版本号 */
	$modules[$i]['version'] = '2.5';

	/* 配置信息 */
	$modules[$i]['config'] = array(
	array('name' => 'merNo', 'type' => 'text', 'value' => ''),
	array('name' => 'gatewayNo', 'type' => 'text', 'value' => ''),
	array('name' => 'signKey', 'type' => 'text', 'value' => ''),
	array('name' => 'creditcardHandler', 'type' => 'text', 'value' => 'https://payment.globebill.com/Interface'),
	array('name' => 'Inside', 'type' => 'select', 'value' => ''),
	array('name' => 'logPath', 'type' => 'text', 'value' => 'globebill'),
	);

	return;

}

class creditcard
{
	const paymentMethod = 'Credit Card';
	/**
	 * 构造函数
	 *
	 * @access  public
	 * @param
	 *
	 * @return void
	 */

	function creditcard()
	{
	}

	function __construct()
	{
		$this->creditcard();
	}

	/**
	 * 生成支付代码
	 * @param   array   $order  订单信息
	 * @param   array   $payment    支付方式信息
	 */
	function get_code($order, $payment)
	{
		//内嵌
		$Inside = isset($payment['Inside']);    
		
		//MD5私钥
		$signkey = trim($payment['signKey']);

		//商户号
		$merNo = trim($payment['merNo']);

		//网关号
		$gatewayNo = trim($payment['gatewayNo']);

		//支付方式
		$paymentMethod = self::paymentMethod;

		//备注
		$remark='';

		//返回地址
		$returnUrl           = return_url(basename(__FILE__, '.php'));

		//支付人姓名 ecshop 没有firstName，lastName之分，只有一个name
		$firstName = $order['consignee'];
		$lastName = '(null)';

		//邮件
		$email = isset($order['email']) ? $order['email'] : '287639598@qq.com';

		//电话tel  可能是mobile也可能是tel，具体看模板
		//$phone = isset($order['mobile']) ? $order['mobile'] : '13888888888';
		$phone = isset($order['tel']) ? $order['tel'] : '15201270117';

		//订单id
		$orderNo = $order['order_sn'];

		//支付的币种
		$orderCurrency = 'USD';

		//支付的金额
		$orderAmount = $order['order_amount'];


        //接口信息
		$interfaceInfo='ecshop';
		$interfaceVersion='V2.3';
		
		$detect = new Mobile_Detect(); 
		if($detect->isiOS()){  
			$isMobile='1';
		}  
		elseif($detect->isMobile()){  
			$isMobile='1';
		}  
		elseif($detect->isTablet()){ 
			$isMobile='0';
		} 
		else
		{
			$isMobile='0';
		}

        //如果国家，州，城市的值是ID，则调用getCsc();这方法
		$data = $this->getCsc($order['country'],$order['province'],$order['city']);
		$country = $data['country'];
		$state = $data['province'];
		$city = $data['city'];
		

        //如果国家，州，城市的值不是ID，则直接从$order中获取
        /**
		$country = $order['country'];
		$state = $order['province'];
		$city = $order['city'];
        **/

        //地址
		$address = $order['address'];

		//邮编
		$zip = $order['zipcode'];

        //邮编为空时，生成随机数
		$rand = '';
        for($i=0;$i<6;$i++)
		{
			$temp=rand(0,9);
			$rand.=$temp;
		}
		$zip=empty($zip)?$rand:$zip;
		
		//生成加密签名串
		$signInfo=hash("sha256",$merNo.$gatewayNo.$orderNo.$orderCurrency.$orderAmount.$returnUrl.$signkey);


		//获取日志存放路径
		$log_path = trim($payment['logPath']).'/';
		if (!is_dir($log_path)) mkdir($log_path);//不存在路径则创建
		$_SESSION['log_path'] = $log_path;
		//记录post log
		$filedate = date('Y-m-d');
		$postdate = date('Y-m-d H:i:s');	
		$newfile  = fopen($log_path . $filedate . ".log", "a+" );		
		$post_log = $postdate."[POST]\r\n" .
				"merNo = "        .$merNo . "\r\n".
				"gatewayNo = "    .$gatewayNo . "\r\n".
				"orderNo = "      .$orderNo . "\r\n".
				"orderCurrency = ".$orderCurrency . "\r\n".
				"orderAmount = "  .$orderAmount . "\r\n".
				"returnUrl = "    .$returnUrl . "\r\n".
				"signInfo = "     .$signInfo . "\r\n".
				"firstName = "    .$firstName . "\r\n".
				"lastName = "     .$lastName . "\r\n".
				"email = "        .$email . "\r\n".
				"phone = "        .$phone . "\r\n".
				"remark = "       .$remark . "\r\n".
				"paymentMethod = ".$paymentMethod . "\r\n".
				"country = "      .$country . "\r\n".
				"state = "        .$state . "\r\n".
				"city = "         .$city . "\r\n".
				"address = "      .$address . "\r\n".
				"zip = "          .$zip . "\r\n".		
		        "interfaceInfo = ".$interfaceInfo . "\r\n".
				"interfaceInfo = ".$interfaceVersion . "\r\n".
				"isMobile = ".$isMobile . "\r\n";
		$post_log = $post_log . "*************************************\r\n";		
		$post_log = $post_log.file_get_contents($log_path . $filedate . ".log");		
		$filename = fopen($log_path . $filedate . ".log", "r+" );		
		fwrite($filename,$post_log);
		fclose($filename);
		fclose($newfile);
		
		$def_url  = "<div style='text-align:center'><form  style='text-align:center;' method='post' name='creditcard_checkout' action='".$payment['creditcardHandler']."'  >";
		$def_url .= "<input type='hidden' name='merNo' value='" . $merNo . "' />";
		$def_url .= "<input type='hidden' name='gatewayNo' value='" . $gatewayNo . "' />";
		$def_url .= "<input type='hidden' name='orderNo' value='" . $orderNo . "' />";
		$def_url .= "<input type='hidden' name='orderCurrency' value='" . $orderCurrency . "' />";
		$def_url .= "<input type='hidden' name='orderAmount' value='" . $orderAmount . "' />";
		$def_url .= "<input type='hidden' name='returnUrl' value='" . $returnUrl . "' />";
		$def_url .= "<input type='hidden' name='signInfo' value='" . $signInfo . "' />";
		$def_url .= "<input type='hidden' name='firstName' value='" . $firstName . "' />";
		$def_url .= "<input type='hidden' name='lastName' value='" . $lastName . "' />";
		$def_url .= "<input type='hidden' name='email' value='" . $email . "' />";
		$def_url .= "<input type='hidden' name='phone' value='" . $phone . "' />";
		$def_url .= "<input type='hidden' name='remark' value='" . $remark . "' />";
		$def_url .= "<input type='hidden' name='paymentMethod' value='" . $paymentMethod . "' />";
		$def_url .= "<input type='hidden' name='country' value='" . $country . "' />";
		$def_url .= "<input type='hidden' name='state' value='" . $state . "' />";
		$def_url .= "<input type='hidden' name='city' value='" . $city . "' />";
		$def_url .= "<input type='hidden' name='address' value='" . $address . "' />";
		$def_url .= "<input type='hidden' name='zip' value='" . $zip . "' />";
		$def_url .= "<input type='hidden' name='interfaceInfo' value='" . $interfaceInfo . "' />";
		$def_url .= "<input type='hidden' name='interfaceVersion' value='" . $interfaceVersion . "' />";
		$def_url .= "<input type='hidden' name='isMobile' value='" . $isMobile . "' />";
		
		if($Inside=="1" && $isMobile=='0'){
			/* 开启内嵌 */
			$def_url .= '</form></div></br>';
			$def_url .= '<iframe width="100%" height="550px"  scrolling="no" style="border:none ; margin: 0 auto; overflow:hidden;" id="ifrm_creditcard_checkout" name="ifrm_creditcard_checkout"></iframe>' . "\n";
			$def_url .= '<script type="text/javascript">' . "\n";
			$def_url .= 'if (window.XMLHttpRequest) {' . "\n";
			$def_url .= 'document.creditcard_checkout.target="ifrm_creditcard_checkout";' . "\n";
			$def_url .= '}' . "\n";
			$def_url .= 'document.creditcard_checkout.action="'.$payment['creditcardHandler'].'";' . "\n";
			$def_url .= 'document.creditcard_checkout.submit();' . "\n";
			$def_url .= 'window.status = "'.$payment['creditcardHandler'].'";' . "\n";
			$def_url .= '</script>' . "\n";
			
		}else{
			/* 支付按钮 */
			$def_url .= "<input type='submit' name='submit' value='" . $GLOBALS['_LANG']['pay_button'] . "' />";
			$def_url .= "</form></div></br>";
		}
		
		return $def_url;
	}
	/**
	 * 响应操作
	 */
	function respond()
	{
		$payment        = get_payment(basename(__FILE__, '.php'));
		$merNo=$_REQUEST["merNo"];
		$gatewayNo=$_REQUEST["gatewayNo"];
		$tradeNo=$_REQUEST["tradeNo"];
		$orderNo=trim($_REQUEST["orderNo"]);
		$orderCurrency=$_REQUEST["orderCurrency"];
		$orderAmount=$_REQUEST["orderAmount"];
		$orderStatus=$_REQUEST["orderStatus"];
		$orderInfo=$_REQUEST["orderInfo"];
		$signInfo=$_REQUEST["signInfo"];
		$remark=$_REQUEST["remark"];
		$signkey = $payment['signKey'];
		$signInfocheck=hash("sha256",$merNo.$gatewayNo.$tradeNo.$orderNo.$orderCurrency.$orderAmount.$orderStatus.$orderInfo.$signkey);
	
		if($_REQUEST['isPush'] == '1'){      //检测是否推送 1为推送  空为正常POST
			$logtype = '[PUSH]';
		}else{
			$logtype = '[RETURN]';
		}
		
		$log_path = $_SESSION['log_path'];
		unset($_SESSION['log_path']);
		
		//记录日志
		$filedate   = date('Y-m-d');
		$returndate = date('Y-m-d H:i:s');
		$newfile    = fopen($log_path . $filedate . ".log", "a+" );
		$return_log = $returndate . $logtype . "\r\n".
				"isPush = "       . $_REQUEST['isPush'] . "\r\n".
				"merNo = "        . $_REQUEST['merNo'] . "\r\n".
				"gatewayNo = "    . $_REQUEST['gatewayNo'] . "\r\n".
				"tradeNo = "      . $_REQUEST['tradeNo'] . "\r\n".
				"orderNo = "      . $_REQUEST['orderNo'] . "\r\n".
				"orderCurrency = ". $_REQUEST['orderCurrency'] . "\r\n".
				"orderAmount = "  . $_REQUEST['orderAmount'] . "\r\n".
				"orderStatus = "  . $_REQUEST['orderStatus'] . "\r\n".
				"orderInfo = "    . $_REQUEST['orderInfo'] . "\r\n".
				"signInfo = "     . $_REQUEST['signInfo'] . "\r\n".
				"riskInfo = "     . $_REQUEST['riskInfo'] . "\r\n".
				"remark = "       . $_REQUEST['remark'] . "\r\n";
		
		$return_log = $return_log . "*************************************\r\n";
		$return_log = $return_log.file_get_contents($log_path . $filedate . ".log");
		$filename   = fopen($log_path . $filedate . ".log", "r+" );
		fwrite($filename,$return_log);
		fclose($filename);
		fclose($newfile);
		
		if (strtolower($signInfo) == strtolower($signInfocheck)) {
			
			//是否推送，isPush:1则是推送，为空则是POST返回
			if($_REQUEST['isPush'] == '1'){
			
				if(substr($orderInfo,0,5) == 'I0061'){	 //排除订单号重复(I0061)的交易
			
				}else{
					//正常处理流程
					if ($orderStatus == "1") {
						//支付成功
						$log_id = get_order_id_by_sn($orderNo);
						/* 改变订单状态 */
						order_paid($log_id);
						$returnUrl = return_url(basename(__FILE__, '.php'))."&success=1&sn=$orderNo";
						echo '<script type="text/javascript">parent.location.replace("'.$returnUrl.'");</script>';				
					} else {		
						//支付失败
						$returnUrl = return_url(basename(__FILE__, '.php'))."&success=0&sn=$orderNo";
						echo '<script type="text/javascript">parent.location.replace("'.$returnUrl.'");</script>';
					}
				}
					
			}elseif($_REQUEST['isPush'] == ''){
					
				//正常 POST返回
				if(substr($orderInfo,0,5) == 'I0061'){	 //排除订单号重复(I0061)的交易
					$returnUrl = return_url(basename(__FILE__, '.php'))."&success=0&sn=$orderNo";
					echo '<script type="text/javascript">parent.location.replace("'.$returnUrl.'");</script>';	
				}else{
					//正常处理流程
					if ($orderStatus == "1") {
						//支付成功
						$log_id = get_order_id_by_sn($orderNo);
						/* 改变订单状态 */
						order_paid($log_id);
						$returnUrl = return_url(basename(__FILE__, '.php'))."&success=1&sn=$orderNo";
						echo '<script type="text/javascript">parent.location.replace("'.$returnUrl.'");</script>';			
					} else {
						//支付失败
						$returnUrl = return_url(basename(__FILE__, '.php'))."&success=0&sn=$orderNo";
						echo '<script type="text/javascript">parent.location.replace("'.$returnUrl.'");</script>';			
					}			
				}				
			}
			
		}else{
			//加密验证失败
			$returnUrl = return_url(basename(__FILE__, '.php'))."&success=0&sn=$orderNo";
			echo '<script type="text/javascript">parent.location.replace("'.$returnUrl.'");</script>';
		}

	}

	/**
	 * 将变量值不为空的参数组成字符串
	 * @param   string   $strs  参数字符串
	 * @param   string   $key   参数键名
	 * @param   string   $val   参数键对应值
	 */
	function append_param($strs,$key,$val)
	{
		if($strs != "")
		{
			if($key != '' && $val != '')
			{
				$strs .= '&' . $key . '=' . $val;
			}
		}
		else
		{
			if($val != '')
			{
				$strs = $key . '=' . $val;
			}
		}
		return $strs;
	}
	function getCsc($country,$state,$city)
	{
		$data = array();
		if(!empty($country))
		{
			$data['country']=$country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$country);
		}
		else
		{
			$data['country']='Undefined';
		}
		if(!empty($state))
		{
			$data['province']=$country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$state);
		}

		else
		{
			$data['province']='Undefined';
		}
		if(!empty($city))
		{
			$data['city']=$country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$city);
		}

		else
		{
			$data['city']='Undefined';
		}

		return $data;
	}

}

?>