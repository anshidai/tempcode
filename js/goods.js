var servicetime;
var chaday;

jQuery(document).ready(function() {
    //等比缩小商品内容的图片
    jQuery(".content img").LoadImage(true,760,1000,"http://www.temperqueen.com/js/loading.gif");
    
     //倒计时
     today = new Date();
     servicetime = new Date();
     chaday = today - servicetime
    
    //切换
    jQuery.jqtab = function(tabtit,tabcon) {
        jQuery(tabtit+" li").click(function() {
            if(jQuery(this).attr("tab")) {
                jQuery(tabtit+" li").removeClass("select");
                jQuery(this).addClass("select");
                jQuery(tabcon).hide();
                jQuery(tabcon).eq(jQuery(this).index()).show();
            }
            return false;
        });
        
    };
    /*调用方法如下：*/
    jQuery.jqtab("#tabs",".tab_con");
    
    jQuery("#comment_show").click(function(){
        showlayer("#comment-layer");
    });
    
    jQuery("#comment_close").click(function(){
        jQuery("#comment-layer").hide();    
    });
    
    rateStar(); 
    
}); 

function submitComment()
{  
    var quslity_rank = jQuery("#quslity_rank").val();
    var value_rank = jQuery("#value_rank").val();
    var price_rank = jQuery("#price_rank").val();
    var goods_id = jQuery("#goods_id").val();
    var content = jQuery("#review_content").val();

    if(content == '') {
        alert('Please enter content.');
        return false;  
    } else if(content.lenght <10) {
        alert('Please enter content may not be less than 10 characters.');
        return false;  
    }
    jQuery.ajax({
        type: 'POST',
        url: 'comment.php?act=comment',
        data: {
            goods_id: goods_id, 
            cmt_type: jQuery("#cmt_type").val(), 
            username: jQuery("#review_nickname").val(), 
            title: jQuery("#review_title").val(),
            quslity_rank: jQuery("#quslity_rank").val(), 
            value_rank: jQuery("#quslity_rank").val(), 
            price_rank: jQuery("#price_rank").val(), 
            content: content, 
        },
        success: function(data){
            if(data.status == '0') {
                alert('Comment on success');
                location.replace(location.href);    
            }else {
                alert(data.msg);  
            }

        },
        dataType:'json'
    });
}

function showlayer(obj)
{   
    
    var layer = jQuery(obj);
    _windowWidth = jQuery(document).width(),//获取当前窗口宽度
    _windowHeight = jQuery(window).height(),//获取当前窗口高度
    _width = layer.outerWidth();//获取弹出层宽度
    _height = layer.outerHeight(),//获取弹出层高度
    _scrollTop = jQuery(document).scrollTop();//滚动轴高度
    
    _posiLeft = (_windowWidth - _width)/2; 
    _posiTop = (_windowHeight - _height)/2 + _scrollTop;
    layer.css({
        "position": "absolute",
        "left": _posiLeft + "px",
        "top": _posiTop + "px",
        "display": "block",
        "z-index": "100"
    });//设置position
    
    /*
    jQuery("#mask_layer").css({
        "width" :jQuery(document.body).width()+"px",
        "height" :jQuery(document.body).height()+"px",   
    });
    */
     
}

//打分
function rateStar()
{
    var quslity_star = jQuery('#quslity_star span');
    var quslity_status = false;
    quslity_star.each(function(i){
        jQuery(this).mouseover(function(){
            if(!quslity_status) {
                quslity_star.removeClass('active-star');
                for(var j=0; j<=i; j++) {
                    quslity_star.eq(j).addClass('active-star');
                }
            }    
        });
        jQuery(this).click(function(){
            jQuery('#quslity_rank').val(quslity_star.eq(i).attr('value'));
            quslity_status = true; 
        });   
    });
    
    var value_star = jQuery('#value_star span');
    var value_status = false;
    value_star.each(function(i){
        jQuery(this).mouseover(function(){
            if(!value_status) {
                value_star.removeClass('active-star');
                for(var j=0; j<=i; j++) {
                    value_star.eq(j).addClass('active-star');
                }
            }    
        });
        jQuery(this).click(function(){
            jQuery('#value_rank').val(value_star.eq(i).attr('value'));
            value_status = true; 
        });   
    });
    
    var price_star = jQuery('#price_star span');
    var price_status = false;
    price_star.each(function(i){
        jQuery(this).mouseover(function(){
            if(!price_status) {
                price_star.removeClass('active-star');
                for(var j=0; j<=i; j++) {
                    price_star.eq(j).addClass('active-star');
                }
            }    
        });
        jQuery(this).click(function(){
            jQuery('#price_rank').val(price_star.eq(i).attr('value'));
            price_status = true; 
        });   
    });  
}


function show_date_time_0() {
    if (document.getElementById("timeout")) {
        for (var e = 0,
        c = target.length; e < c; e++) {
            today = new Date();
            todayse = today.getTime() - chaday;
            timeold = target[e] - today.getTime();
            if (timeold < 0) {
                document.getElementById("timeout").style.display = "none"
            }
            sectimeold = timeold / 1000;
            secondsold = Math.floor(sectimeold);
            msPerDay = 24 * 60 * 60 * 1000;
            e_daysold = timeold / msPerDay;
            daysold = Math.floor(e_daysold);
            e_hrsold = (e_daysold - daysold) * 24;
            hrsold = Math.floor(e_hrsold);
            e_minsold = (e_hrsold - hrsold) * 60;
            d = Math.floor((e_hrsold - hrsold) * 60);
            var g = e_hrsold - hrsold;
            var d = Math.floor(g * 60);
            var b = e_minsold - d;
            var f = Math.floor(b * 60);
            e_seconds = (e_minsold - d) * 60;
            f = Math.floor(e_seconds);
            e_millisecond = (e_seconds - f) * 1000;
            millisecond = Math.floor(e_millisecond);
            millisecond10 = Math.floor(millisecond);
            var a = "<span>&nbsp;days&nbsp;</span>";
            if (daysold < 10) {
                daysold = daysold
            }
            if (daysold < 100) {
                daysold = daysold
            }
            if (d < 10) {
                d = "0" + d
            }
            if (f < 10) {
                f = "0" + f
            }
            if (millisecond10 < 10) {
                millisecond10 = "00" + millisecond10
            } else {
                if (millisecond10 < 100) {
                    millisecond10 = "0" + millisecond10
                }
            }
            document.getElementById(time_id[e]).innerHTML = daysold + a + hrsold + ":" + d + ":" + f + ":" + millisecond10
        }
        setTimeout("show_date_time_0()", 500)
    }
}

function customAttr()
{
    var customcheck = jQuery("#customcheck");
    if(customcheck.attr("checked")==true) {
        jQuery('#custom_measurements').show();
        jQuery("#attrSize").val("");
        jQuery("#attrSize").attr("disabled","disabled");      
    }else {
        jQuery('#custom_measurements').hide();
        jQuery('#cus_shoulder_width').val('');
        jQuery('#cus_bust_size').val('');
        jQuery('#cus_waist_size').val('');
        jQuery('#cus_hip_size').val('');
        jQuery('#cus_hollow_to_floor').val('');
        jQuery('#cus_hollow_to_knee').val('');
        jQuery("#attrSize").removeAttr("disabled");
    }
}