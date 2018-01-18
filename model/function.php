<?php

function generateNonceStr($length = 16) {
    // 密码字符集，可任意添加你需要的字符
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

function getSignature($arrdata, $method = "sha1") {
    if (!function_exists($method))
        return false;
    ksort($arrdata);
    $paramstring = "";
    foreach ($arrdata as $key => $value) {
        if (strlen($paramstring) == 0)
            $paramstring .= $key . "=" . $value;
        else
            $paramstring .= "&" . $key . "=" . $value;
    }

    $Sign = $method($paramstring);
    return $Sign;
}

function getJsSign($url) {
    global $_W;
    $wechat = $_W['account']['uniaccount'];
    $appid = $wechat['key'];
    $jsapi_ticket = $wechat['jsapi_ticket'];
    $timestamp = time();
    $noncestr = generateNonceStr();
    $ret = strpos($url, '#');
    if ($ret) {
        $url = substr($url, 0, $ret);
    }
    $url = trim($url);
    if (empty($url)) {
        return false;
    }
    $arrdata = array("timestamp" => $timestamp, "noncestr" => $noncestr, "url" => $url, "jsapi_ticket" => $this->jsapi_ticket);
    $sign = getSignature($arrdata);

    if (!$sign) {
        return false;
    }

    $signPackage = array(
        "appid" => $this->appid,
        "noncestr" => $noncestr,
        "timestamp" => $timestamp,
        "url" => $url,
        "signature" => $sign
    );
    return $signPackage;
}

function returnJson($code, $message, $data = array(),$url='') {
    header("Content-type: text/html; charset=utf-8");
    echo json_encode(array(
        'ret' => $code,
        'message' => $message,
        'data' => $data,
        'url' => $url
            ), true);
    exit;
}

function _pr($arr) {
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
    exit;
}

function arrayToXml($arr) {
    $xml = "<xml>";
    foreach ($arr as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
    }
    $xml .= "</xml>";
    return $xml;
}

function xmlToArray($xml) {
    //将XML转为array
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}
//
//function base64EncodeImage ($image_file) {
//  $image_data = file_get_contents($image_file);
//  $base64_image = base64_encode($image_data);
//  return 'data:image/bmp;base64,'.$base64_image;
//}
function download_remote_file_with_curl($file_url, $save_to)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 0); 
    curl_setopt($ch,CURLOPT_URL,$file_url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $file_content = curl_exec($ch);
    curl_close($ch);

    $downloaded_file = fopen($save_to, 'w');
    fwrite($downloaded_file, $file_content);
    fclose($downloaded_file);

}

function ipost ($url,$data) {
    load()->func('communication'); 
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
    return ihttp_request($url, $data, $headers,1);
}

function base64url ($str) {
    return strtr(base64_encode($str), '+/', '-_');
}


function sendText ($openid,$text)  {
    global $_W;
    if($_W['account']['level'] >= ACCOUNT_SUBSCRIPTION_VERIFY) { 
        $message = array(
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => array('content' => urlencode($text))
        );
        $account_api = WeAccount::create();
        $account_api->sendCustomNotice($message);
    }
}

function sendImg ($openid,$media_id) {
    global $_W;
    if($_W['account']['level'] >= ACCOUNT_SUBSCRIPTION_VERIFY) { 
        $message = array(
            'touser' => $openid,
            'msgtype' => 'image',
            'image' => array('media_id' => $media_id)
        );
        $account_api = WeAccount::create();
        $account_api->sendCustomNotice($message);
    }
}

function backOpen($url){
    $ch = curl_init(); 
    $curl_opt = array( 
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER=>1, 
      CURLOPT_TIMEOUT=>1 
    ); 
    curl_setopt_array($ch, $curl_opt); 
    curl_exec($ch); 
    curl_close($ch); 
}

function getPageLIst ($tablename,$where,$orderby) {
    global $_W,$_GPC;
    $size = 20;
    $page = max(1,$_GPC['page']);
    $limit = "LIMIT ". ($page-1)*$size.", {$size}";
    $list = pdo_getall($tablename, $where, '', '', $orderby, $limit);
    $countline = pdo_get($tablename,$where,array('count(*) as allcount'));
    $pager = pagination($countline['allcount'], $page, $size);
    return array(
        'list' => $list,
        'pager' => $pager
    );
}

function setRefer ($openid,$refer_openid) {
    if($openid==$refer_openid) {
        return false;
    }
    global $_W;
    $member = mc_fansinfo($openid);
    $refer_uid = mc_openid2uid($refer_openid);
    $where = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $member['uid']
    );
    $res = pdo_get('hai_daka_member',$where);
    if(!$res) {
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $member['uid'],
            'refer_uid' => $refer_uid,
            'create_time' => date('Y-m-d H:i:s')
        );
        pdo_insert('hai_daka_member',$data);
        return true;
    }
    $where = array(
        'uid' => $member['uid'],
        'status' => 1,
        'uniacid' => $_W['uniacid']
    );
    $order = pdo_get('hai_daka_yiyuan',$where);
    if($order) {
        return false;
    }
    
    if(!$res['refer_uid']){
        pdo_update('hai_daka_member',array(
            'refer_uid' => $refer_uid
        ),array(
            'uniacid' => $_W['uniacid'],
            'uid' => $member['uid']
        ));
        return true;
    }
    return false;
}

function payRefer ($uid) {
    global $_W;
    $member = pdo_get('hai_daka_member',array(
        'uid' => $uid,
        'uniacid' => $_W['uniacid']
    ));
    if(!$member['refer_uid']) {
        return false;
    }
    $where = array(
        'uid'=>$uid,
        'uniacid' => $_W['uniacid'],
        'status' => 1
    );
    $res = pdo_get('hai_daka_yiyuan',$where,array(
        'count(*) as allcount'
    ));
    if($res['allcount']>1) {
        return false;
    }
    $money = $_W['mconfig']['refer_money'];
    $money = round($money,2);
    load()->model('mc');
    $fans = mc_fansinfo($uid);
    $content = '推荐参与奖励-'.$fans['nickname'];
    mc_credit_update($member['refer_uid'], 'credit2', $money, array(0, $content,'hai105_daka'));
    if($_W['account']['level'] >= ACCOUNT_SUBSCRIPTION_VERIFY) { 
        //$url = murl('mc','','',true);
//        $url = murl('entry',array(
//            'm' => 'hai105_daka',
//            'do' => 'cover',
//        ),'',true);
        $url = murl('mc',array(
            'a' => 'bond',
            'do' => 'credits',
            'credittype' => 'credit2',
            'type' => 'record',
            'period' => 1
        ),'',true);
        
        $text = "恭喜你有新的现金入账,{$content}\n<a href='{$url}'>查看记录</a>";
        $message = array(
            'touser' => mc_uid2openid($member['refer_uid']),
            'msgtype' => 'text',
            'text' => array('content' => urlencode($text))
        );
        $account_api = WeAccount::create();
        $account_api->sendCustomNotice($message);
    }
}
