<?php
/**
* ECSHOP TNT插件
* $Date: 2007-07-2  $
* $Id: ups.php 8272 2007-07-7 10:11:35Z goalbell $
*/
$shipping_lang = ROOT_PATH.'languages/' .$GLOBALS['_CFG']['lang']. '/shipping/tnt.php';
if (file_exists($shipping_lang))
{
    global $_LANG;
    include_once($shipping_lang);
}
/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = (isset($modules)) ? count($modules) : 0;
    /* 配送方式插件的代码必须和文件名保持一致 */
    $modules[$i]['code']    = 'tnt';
    $modules[$i]['version'] = '1.1.0';
    /* 配送方式的描述 */
    $modules[$i]['desc']    = 'tnt_express_desc';
    /* 配送方式是否支持货到付款 */
    $modules[$i]['cod']     = false;
    /* 插件的作者 */
    $modules[$i]['author']  = 'goalbell';
    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ygdns.com';
    /* 配送接口需要的参数 */
    $modules[$i]['configure'] = array(
                                    array('name' => 'base1_fee',     'value'=>20),
                                    array('name' => 'step1_fee',     'value'=>15),
                                );
    return;
}
/**
* 包裹费用计算方式
* ====================================================================================
* 500g及500g以内                             20元
* -------------------------------------------------------------------------------------
* 续重每500克或其零数                       15元
* -------------------------------------------------------------------------------------
*
*/
class tnt
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */
    /**
     * 配置信息
     */
    var $configure;
    /*------------------------------------------------------ */
    //-- PUBLIC METHODs
    /*------------------------------------------------------ */
    /**
     * 构造函数
     *
     * @param: $configure[array]    配送方式的参数的数组
     *
     * @return null
     */
    function tnt($cfg=array())
    {
        foreach ($cfg AS $key=>$val)
        {
            $this->configure[$val['name']] = $val['value'];
        }
    }
    /**
     * 计算订单的配送费用的函数
     *
     * @param   float   $goods_weight   商品重量
     * @param   float   $goods_amount   商品金额
     * @return  decimal
     */
    function calculate($goods_weight, $goods_amount)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money'])
        {
            return 0;
        }
        else
        {
            $fee = $this->configure['base1_fee'];
            if ($goods_weight > 0.5)
            {
                $fee += (ceil(($goods_weight - 0.5) / 0.5)) * $this->configure['step1_fee'];
            }
            return $fee;
        }
    }
    /**
     * 查询发货状态
     *
     * @access  public
     * @param   string  $invoice_sn     发货单号
     * @return  string
     */
    function query($invoice_sn)
    {
        $str = '<form style="margin:0px" methods="post" '.
            'action="http://www.tnt.com/webtracker/tracking.do" name="queryForm_' .$invoice_sn. '" target="_blank">'.
            '<input value="" name="genericSiteIdent" type="hidden" />'.
			'<INPUT VALUE="1" NAME="page" TYPE="hidden" />'.
            '<INPUT VALUE="zh" NAME="respLang" TYPE="hidden" />'.
			'<INPUT VALUE="CN" NAME="respCountry" TYPE="hidden" />'.
			'<INPUT VALUE="1" NAME="sourceID" TYPE="hidden" />'.
			'<INPUT VALUE="ww" NAME="sourceCountry" TYPE="hidden" />'.
			'<INPUT VALUE="" NAME="plazakey" TYPE="hidden" />'.
			'<INPUT VALUE="" NAME="refs" TYPE="hidden" />'.
			'<INPUT VALUE="GEN" NAME="requestType" TYPE="hidden" />'.
			'<INPUT VALUE="CON" NAME="searchType" TYPE="hidden" />'.
			'<INPUT VALUE="1" NAME="navigation" TYPE="hidden" />'.
			'<input type="hidden" name="trackType" value="CON" />'.
            '<input type="hidden" name="cons" value="' .$invoice_sn. '" />'.
            '<a href="javascript:document.forms[\'queryForm_' .$invoice_sn. '\'].submit();">' .$invoice_sn. '</a>'.
            '</form>';
        return $str;
    }
}
?>