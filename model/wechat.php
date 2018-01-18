<?php

include 'function.php';

function qiyeLuckyMoney($data, $payment, $certurl) {
    global $_W;
    $jsApiObj["mch_billno"] = $data['trade_no']; //商户订单号（每个订单号必须唯一）组成：mch_id+yyyymmdd+10位一天内不能重复的数字。
    $jsApiObj["nonce_str"] = generateNonceStr(); //随机字符串
    $jsApiObj["mch_id"] = $payment['wechat']['mchid']; //商户号
    $jsApiObj["wxappid"] = $_W['uniaccount']['key']; //微信号
    $jsApiObj["send_name"] = $data['send_name']; //红包发送者
    $jsApiObj["re_openid"] = $data['openid']; //用户openid
    $jsApiObj["total_amount"] = $data['total_amount']; //金额,分
    $jsApiObj["total_num"] = 1; //用户openid
    $jsApiObj["wishing"] = $data['wishing']; //	红包祝福语
    $jsApiObj["client_ip"] = $data['ip']; //调用接口的机器Ip地址
    $jsApiObj["act_name"] = $data['act_name']; //活动名称
    $jsApiObj["remark"] = $data['remark']; //备注
    $jsApiObj["sign"] = _getSign($jsApiObj, $payment['wechat']['apikey']); //签名
    $xml = arrayToXml($jsApiObj);
    $ext = array(CURLOPT_SSLCERT => $certurl);
    load()->func('communication');
    $result = ihttp_request('https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack', $xml, $ext);
    $obj = xmlToArray($result['content']);
    if(IS_DEBUG==2) {
        var_dump($obj);
    }
    if ($obj['return_code'] != "SUCCESS") {
        return false;
    }
    if ($obj['result_code'] == "SUCCESS") {
        return true;
    }
    return false;
}

function qiyePay($data, $payment, $certurl) {
    global $_W;
    $jsApiObj["mch_appid"] = $_W['uniaccount']['key']; //公众账号ID
    $jsApiObj["mchid"] = $payment['wechat']['mchid']; //商户号
    $jsApiObj["partner_trade_no"] = $data['trade_no']; //商户订单号
    $jsApiObj["nonce_str"] = generateNonceStr(); //随机字符串
    $jsApiObj["openid"] = $data['openid']; //用户openid
    $jsApiObj["check_name"] = "NO_CHECK"; //用户openid
    $jsApiObj["amount"] = $data['total_amount']; //企业付款金额，单位为分
    $jsApiObj["desc"] = $data['remark']; //企业付款操作说明信息。必填。
    $jsApiObj["spbill_create_ip"] = $data['ip']; //调用接口的机器Ip地址
    $jsApiObj["sign"] = _getSign($jsApiObj,$payment['wechat']['apikey']); //签名
    $xml = arrayToXml($jsApiObj);
    $ext = array(CURLOPT_SSLCERT => $certurl);
    load()->func('communication');
    $result = ihttp_request('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers', $xml,$ext);
    $obj = xmlToArray($result['content']);
    if(IS_DEBUG==2) {
        var_dump($obj);
    }
    if ($obj['return_code'] != "SUCCESS") {
        return false;
    }
    if ($obj['result_code'] == "SUCCESS") {
        return true;
    }
    return false;
}

function _getSign($Obj, $apikey) {
    foreach ($Obj as $k => $v) {
        $Parameters[$k] = $v;
    }
    //签名步骤一：按字典序排序参数
    ksort($Parameters);
    $String = _formatBizQueryParaMap($Parameters, false);
    //echo '【string1】'.$String.'</br>';
    //签名步骤二：在string后加入KEY

    $String = $String . "&key=" . $apikey;
    //echo "【string2】".$String."</br>";
    //签名步骤三：MD5加密

    $String = md5($String);

    //echo "【string3】 ".$String."</br>";
    //签名步骤四：所有字符转为大写
    $result_ = strtoupper($String);
    //echo "【result】 ".$result_."</br>";
    return $result_;
}

//格式化参数
function _formatBizQueryParaMap($paraMap, $urlencode) {
    $buff = "";
    ksort($paraMap);
    foreach ($paraMap as $k => $v) {
        if ($urlencode) {
            $v = urlencode($v);
        }
        $buff .= $k . "=" . $v . "&";
    }
    $reqPar = "";
    if (strlen($buff) > 0) {
        $reqPar = substr($buff, 0, strlen($buff) - 1);
    }
    return $reqPar;
}
