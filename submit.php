<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>正在为您跳转到支付页面，请稍候...</title>
    <style type="text/css">
        body {margin:0;padding:0;}
        p {position:absolute;
            left:50%;top:50%;
            width:330px;height:30px;
            margin:-35px 0 0 -160px;
            padding:20px;font:bold 14px/30px "宋体", Arial;
            background:#f9fafc url(../images/loading.gif) no-repeat 20px 26px;
            text-indent:22px;border:1px solid #c5d0dc;}
        #waiting {font-family:Arial;}
    </style>
<script>
function open_without_referrer(link){
document.body.appendChild(document.createElement('iframe')).src='javascript:"<script>top.location.replace(\''+link+'\')<\/script>"';
}
</script>
</head>
<body>
<?php
@header('Content-Type: text/html; charset=UTF-8');

require_once("./includes/common.php");
require_once("./includes/muzhifu/muzhifu_config.php");
require_once("./includes/muzhifu/lib/muzhifu_submit.php");
$pid =$_GET['pid'];
if(isset($pid)){
	$dataarr = $_GET;
}else{
	$dataarr = $_POST;
}

$prestr=createLinkstring(argSort(paraFilter($dataarr)));
$pid=intval($dataarr['pid']);
if(empty($pid))sysmsg('APPID不存在');
$userrow=$DB->query("SELECT * FROM mzf_merchant WHERE id='{$pid}' limit 1")->fetch();
if(!md5Verify($prestr, $dataarr['sign'], $userrow['key']))sysmsg('签名校验失败，请返回重试！');
if($userrow['active']==0)sysmsg('商户已封禁，无法支付！');
$type=daddslashes($dataarr['type']);
$out_trade_no=daddslashes($dataarr['out_trade_no']);//客户提交订单号
$notify_url=daddslashes($dataarr['notify_url']);
$return_url=daddslashes($dataarr['return_url']);
$name=daddslashes($dataarr['name']);
$money=daddslashes($dataarr['money']);

if(empty($out_trade_no))sysmsg('订单号(out_trade_no)不能为空');
if(empty($notify_url))sysmsg('通知地址(notify_url)不能为空');
if(empty($return_url))sysmsg('回调地址(return_url)不能为空');
if(empty($name))sysmsg('商品名称(name)不能为空');
if(empty($money))sysmsg('金额(money)不能为空');
if($money<=0)sysmsg('金额不合法');
$trade_no=date("YmdHis").rand(11111,99999);//内部生成订单号
$domain=getdomain($notify_url);
	if($type=='alipay'){
	    $type='alipay.trade.precreate';//支付宝支付
        $mz_type = $conf['alipay_type'];
	}elseif($type=='qqpay'){
	    $type='qq.pay.native';//QQ支付
        $mz_type = $conf['qqpay_type'];
    }elseif($type=='wxpay'){
	    $type='wxpay.pay.unifiedorder';//微信支付
        $mz_type = $conf['wxpay_type'];
    }else{
	    $type='alipay.trade.precreate';//默认支付宝支付
        $mz_type = $conf['qqpay_alipay'];
    }
if(!$DB->query("insert into `pay_order` (`trade_no`,`out_trade_no`,`notify_url`,`return_url`,`type`,`pid`,`addtime`,`name`,`money`,`domain`,`ip`,`status`) values ('".$trade_no."','".$out_trade_no."','".$notify_url."','".$return_url."','".$type."','".$pid."','".$date."','".$name."','".$money."','".$domain."','".$clientip."','0')"))exit('创建订单失败，请返回重试！');
$http_to = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	//echo $tyep;
    if($mz_type == '-1'){
         sysmsg('商户已暂停此支付方式!');
}
	$params =   [
	    'appid'        =>  $muzhifu_config['partner'],
	    'type'        =>  $type,
	    'addtime'     =>  date('Y-m-d H:i:s'),
        'mz_type'      => $mz_type //0 默认方式 or 1 本地扫码方式
	];

	$params['mz_content'] = json_encode([
		'out_trade_no'	=>	$trade_no,//内部生成订单号传到api接口
		'subject'		=>	$name,
		'total_amount'	=>	$money,
		"notify_url"	=> $http_to.$_SERVER['HTTP_HOST'].'/pay_notify.php',
		"return_url"	=> $http_to.$_SERVER['HTTP_HOST'].'/pay_return.php'
	]);
	if($params['mz_type'] == '1'){
    $params['mz_domain'] = $http_to.$_SERVER['HTTP_HOST'];
          }
	// 获得签名
	$sign = Muzhifu::sign($params, $muzhifu_config['key']);
	$params['sign'] = $sign;
	// 发送请求获得结果
	$http = Muzhifu::http($params);
    //print_r($http);
	$res = json_decode($http);
	if (!isset($res)) {
	    exit('出现问题：'.$http);
	
	}

	if (isset($res->url)) {
        if ($res->type == '1') {//拇指付官方跳转
            echo $res->url;
        } else {
            header('Location: '.$res->url);
        }
	} else {
	    exit('出现问题：'.$res->msg);
	}


?>
<p>正在为您跳转到支付页面，请稍候...</p>
</body>
</html>
