<?php
class api {
    public $DATA;
    public function __construct() {
        global $_W,$_GPC;
        if($_W['isajax'] && $_W['ispost']){
            $json = file_get_contents('php://input');
            $this->DATA = json_decode($json,true);
        }
    }

    public function tixian () {
        global $_W,$_GPC;
        $uid = $_W['member']['uid'];
        $errno = 0;
        $message = '提现成功';
        $data = array(
            'name' => $this->DATA['name'],
            'money' => $this->DATA['money'],
            'uid' => $uid,
            'uniacid' => $_W['uniacid'],
            'create_time' => date('Y-m-d H:i:s'),
            'order_sn' => date("ymdHis").sprintf("%06d", $_W['member']['uid']).rand(10,99)
        );
  		 if(!$_W['member']) {
            returnJson(1,'未登录');
        }
        if(!$data['name']) {
            //returnJson(1,'请填写姓名');
        }

        if($data['money']<= 0 || !$data['money'])
        {
            returnJson(1,'金额错误');
        }

         if($data['money']<1)
        {
            returnJson(1,'金额应大于1');
        }

        if($data['money']>=200) {
            returnJson(1,'单次最高提现200元');
        }

        $log = array(
            1 => '打卡提现',
            2 => 'hai105_daka',
            5 => 1
        );
        load()->model('mc');
        $res = mc_credit_update($uid, 'credit2', -$data['money'], $log);
        if($res) {

            if($_W['mconfig']['atmminmoney']>=1 && $data['money']<=$_W['mconfig']['atmminmoney']){
                //打款
                $payres = $this->_payAtm($data);
                if($payres) {
                    $data['status'] = 1;
                }
                unlink(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');
            }

            pdo_insert('hai_daka_atm',$data);
            if($data['status']==1) {
                returnJson(0,'提现成功');
            }else{
                returnJson(0,'提现申请已提交');
            }
        }
        returnJson(0,'提现失败请重试');
    }

    public function getatmlog () {
        global $_W,$_GPC;
        $where = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid']
        );
        $res = getPageLIst('hai_daka_atm',$where,'id desc');
        $list = $res['list'];
        if(empty($list)) {
            returnJson(1,'null');
        }
        returnJson(0,'ok',$list);
    }
    
    public function getreferlog () {
        global $_W,$_GPC;
        $where = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'remark like' => "%推荐参与奖励%"
        );
        $res = getPageLIst('mc_credits_record',$where,'id desc');
        $list = $res['list'];
        if(empty($list)) {
            returnJson(1,'null');
        }
        
        returnJson(0,'ok',$list);
    }

    protected function _payAtm ($order) {
        global $_W;
        $pay = uni_setting_load('payment', $_W['uniacid']);
        $payment = $pay['payment'];
        $paycert = $payment['wechat_refund']['cert'];
        $paykey = $payment['wechat_refund']['key'];
        $paycert = authcode($paycert, 'DECODE');
        $paykey = authcode($paykey, 'DECODE');
        $certurl = ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem';
        file_put_contents($certurl, $paykey.$paycert);

        $data = array(
            'trade_no' => $order['order_sn'],
            'send_name' => '早起打卡挑战',
            'openid' => mc_uid2openid($order['uid']),
            'total_amount' => $order['money']*100,//分
            'wishing' => '早起挑战奖励金,加油,再接再厉',
            'ip' => $_W['clientip'],
            'act_name' => '早起挑战活动',
            'remark' => '早起挑战奖励金'
        );
        $data['trade_no'] = 'atm'.$data['trade_no'];
        if($_W['mconfig']['atmtype']==1) {
            return qiyeLuckyMoney($data,$payment,$certurl);
        }elseif($_W['mconfig']['atmtype']==2){
            return qiyePay($data,$payment,$certurl);
        }


        

    }
}
?>