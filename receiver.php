<?php

/**
 * 我的助理模块订阅器
 *
 * @author hai105
 * @url 
 */
defined('IN_IA') or exit('Access Denied');
include 'model/function.php';
class Hai105_dakaModuleReceiver extends WeModuleReceiver {

    /*
     * {"tousername":"gh_62b83e94f8fc","fromusername":"os4Sn0Yw3A_9aad7vik57_k4ypzI","createtime":
     * "1510826187","msgtype":"event","event":"SCAN","eventkey":"100001",
     * "ticket":"gQH-8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyOElQWEZkUDRlRGgxZUZKUTFxMVQAAgSpYA1aAwQAjScA",
     * "from":"os4Sn0Yw3A_9aad7vik57_k4ypzI","to":"gh_62b83e94f8fc","time":"1510826187","type":"qr","scene":"100001"}
     */
    public $member;
    public function receive() {
        $this->member = mc_fansinfo($this->message['from']);
        $type = $this->message['msgtype'];
        //file_put_contents(ATTACHMENT_ROOT . 'rec1.txt', json_encode($this->message) . PHP_EOL, FILE_APPEND);
        //这里定义此模块进行消息订阅时的, 消息到达以后的具体处理过程, 请查看微擎文档来编写你的代码
        if($type=='event') {
            $this->_handleEvent();
        }
    }
    
    protected function _handleEvent () {
        load()->func('communication'); 
        global $_W;
        $event = $this->message['event'];
        if($event=='SCAN') {
            $scene = $this->message['scene'];
            $qrcode = pdo_get('hai_daka_qrcode',array('scene_id'=>$scene,'uniacid'=>$_W['uniacid']));
            $res = setRefer($this->message['from'], $qrcode['openid']);

            $url = murl('entry',array(
                'm' => 'hai105_daka',
                'do' => 'cover'
            ),'',true);
            $text = "快来参与早起打卡挑战吧。\n\n<a href='{$url}'>参加挑战</a>";
            $message = array(
                'touser' => mc_uid2openid($this->message['from']),
                'msgtype' => 'text',
                'text' => array('content' => urlencode($text))
            );
            $account_api = WeAccount::create();
            $account_api->sendCustomNotice($message);
        }
    }
}
