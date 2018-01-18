<?php

/**
 * 我的助理模块处理程序
 *
 * @author hai105
 * @url 
 */
defined('IN_IA') or exit('Access Denied');
include 'model/function.php';
class Hai105_dakaModuleProcessor extends WeModuleProcessor {

    public function respond() {
        
        $content = $this->message['content'];
        //file_put_contents(ATTACHMENT_ROOT.'prozz1.txt', json_encode($this->message).PHP_EOL, FILE_APPEND);
        //这里定义此模块进行消息处理时的具体过程, 请查看微擎文档来编写你的代码
        load()->func('communication'); 
        $msgtype = $this->message['msgtype'];
        if($msgtype=='event') {
            $res = $this->_handleEvent();
        }
        if($msgtype=='text') {
            $res = $this->_handleText();
        }
        return $res;
        
    }

    /*
     * {"tousername":"gh_62b83e94f8fc","fromusername":"os4Sn0Yw3A_9aad7vik57_k4ypzI","createtime":"1510990679",
     * "msgtype":"event","event":"subscribe","eventkey":"qrscene_100002",
     * "ticket":"gQE18TwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyZVQzaUZmUDRlRGgxMTVCUjFxMWgAAgRFmA1aAwQAjScA",
     * "from":"os4Sn0Yw3A_9aad7vik57_k4ypzI","to":"gh_62b83e94f8fc","time":"1510990679","type":"text",
     * "scene":"100002","redirection":true,"source":"subscribe","content":"\u52a9\u7406\u5173\u6ce8"}
     */
    protected function _handleEvent () {
        global $_W;
        $event = $this->message['event'];
        $scene = $this->message['scene'];
        if($event=='subscribe' && $scene) {
            //扫码关注
            $scene = $this->message['scene'];
            $qrcode = pdo_get('hai_daka_qrcode',array('scene_id'=>$scene,'uniacid'=>$_W['uniacid']));
            setRefer($this->message['from'], $qrcode['openid']);
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
        return $this->respText('');
    }
    
    
    protected function _handleText () {
        global $_W;
        if($this->message['content']=='助理打卡') {
            //执行任务
            $url = murl('entry',array(
                'do' => 'api',
                'm' => 'hai105_ass',
                'action' => 'senddakaimg'
            ),'',true);
            $data['openid'] = $this->message['from'];
            ihttp_request($url, $data);
        }
        return $this->respText('');
    }
}
