<?php
/**
 * 早起打卡模块微站定义
 *
 * @author hai105
 * @url 
 */
defined('IN_IA') or exit('Access Denied');
include 'model/wechat.php';
class Hai105_dakaModuleSite extends WeModuleSite {
    public $member;
    public $config;
    public function __construct() {
        global $_W;
        $this->init();
        $_W['mconfig'] = $this->config;
        if (!empty($_W['member'])) {
            $this->member = $_W['member'];
            $where = array(
                'uid' => $_W['member']['uid'],
                'uniacid' => $_W['uniacid'],
            );
            $dakaMember = pdo_get('hai_daka_member',$where);
            if(!$dakaMember) {
                $dakaMember = array();
            }
            $fans = mc_fansinfo($_W['member']['uid']);
            $this->member = array_merge($this->member,$dakaMember,$fans);
        }
    }
    
    protected function init () {
        global $_W;
        $isSet = pdo_get('hai_daka_config',array(
            'uniacid' => $_W['uniacid'],
            'key' => 'atmminmoney'
        ));
        if(!$isSet) {
            $keys = array(
                'pay_type' => 1,//1js支付 2php支付
                'per_money' => 1,//单笔金额
                'tax_bili' => 0.006,
                'daka_start_time' => '05:00',
                'daka_end_time' => '08:00',
                'fenmoney_type' => 1,//1红包 2企业付款
                'share_type' => 1,//1自动生成 2人工设置
                'share_title' => '早起打卡赢现金',
                'share_des' => '早起打卡赢现金',
                'share_img' => '',
                'tpl_type' => 1,
                'sub_url' => '',
                'is_debug' => 1,
                'zhanji_back' => '',
                'zhanji_type' => 1,
                'index_title' => '早起打卡',
                'ad_imgurl' => '',
                'ad_url' => '',
                'zhanji_params' => '',
                'lianxu_status' => 2,
                'lianxu_imgurl' => '',
                'lianxu_detail' => '',
                'zhanji_auto' => 2,//打卡后自动发海报给用户
                'score' => 0,
                'rule' => "",
                'pay_list' => '',//金额列表
                'paybtntext' => '支付一元参与挑战',//支付按钮文字
                'iswarn' => '0',
                'warntmpid' => '',
                'iswarnfail' => '',
                'warnfaildate' => '2017-11-11',
                'menu' => '',
                'ispaying' => 0,
                'refer_money' => 0,
                'autoatm' => 0,
                'atmtype' => 1,//1红包 2企业付款
                'atmminmoney' => 0
            );
            foreach ($keys as $key=>$value) {
                $kw = array(
                    'key' => $key,
                    'uniacid' => $_W['uniacid']
                );
                $keyline = pdo_get('hai_daka_config',$kw);
                if(!$keyline) {
                    $data = array(
                        'key' => $key,
                        'value' => $value,
                        'uniacid' => $_W['uniacid']
                    );
                    pdo_insert('hai_daka_config', $data);
                }
            }
        }
        
        $where = array(
            'uniacid' => $_W['uniacid']
        );
        $configlist = pdo_getall('hai_daka_config',$where);
        foreach ($configlist as $k=>$v) {
            $this->config[$v['key']] = $v['value'];
        }
        define('IS_DEBUG', $this->config['is_debug']);
    }

    public function payResult($params) {
        if ($params['result'] == 'success' && $params['from'] == 'notify') {
            $where = array(
                'order_sn' => $params['tid']
            );
            $order = pdo_get('hai_daka_yiyuan',$where);
            if(!$order){
                echo 'fail';exit;
            }
            if($order['status']!=0) {
                echo 'success';exit;
            }
            if($params['fee']!=1) {
                //echo 'fail';exit;
            }
            $save = array(
                'status' => 1,
                'pay_result' => json_encode($params)
            );
            
            pdo_update('hai_daka_yiyuan', $save, $where);
            payRefer($order['uid']);
            $this->_addCron();
        }
        if ($params['from'] == 'return') {
            if ($params['result'] == 'success') {
                message('支付成功！', $this->createMobileUrl('cover'), 'success');
            } else {
                message('支付失败！', $this->createMobileUrl('cover'), 'error');
            }
        }
    }

    public function doMobileCover() {
        load()->func('communication'); 
        global $_W;
        $seo = array(
            'title' => $this->config['index_title']
        );
        if($_W['container']!='wechat') {
//            message('请在微信端打开');exit;
        }
        
        $share = $this->_setShare();
        checkauth();

        $this->_addMember();
        $this->_checkLianxu();
        
        $zhanji_url = $this->createMobileUrl('zhanji');
        $h = date('H');
        if($h>5 && $h<19) {
            $isDay = true;
        }else{
            $isDay = false;
        }
        
        $this->config['lianxu_detail'] = htmlspecialchars_decode($this->config['lianxu_detail']);
        $this->config['lianxu_imgurl'] = $_W['attachurl'].$this->config['lianxu_imgurl'];
        //$this->config['rule']  = str_replace(PHP_EOL, '<br>', $this->config['rule']);
        $config = $this->config;
        $config['rule'] =  str_replace("\r\n","<br>",$config['rule']); 
        if($config['ad_imgurl']) {
            $config['ad_imgurl'] = $_W['attachurl'].$config['ad_imgurl'];
        }

        if($config['menu']) {
            $config['menu'] = json_decode($config['menu'],true);
        }
        
        include $this->template('index');
    }


    public function doMobileApi() {
        $action = $_GET['action'];
        if (!$action) {
            return false;
        }
        $this->$action();
    }

    public function doMobileApinew() {
        include MODULE_ROOT.'/inc/app/api.php';
        global $_W,$_GPC;
        $api = new api();
        $action = $_GPC['action'];
        if($action){
            $api->$action();
        }
    }

    protected function getpayinfo () {
        global $_W,$_GPC;
        $paymoney = $this->config['per_money']?$this->config['per_money']:1;
        
        if($_GPC['money'] && is_numeric($_GPC['money'])){
            $paymoney = round($_GPC['money'],2);
        }
        
        $todayWhere = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $this->member['uid'],
            'create_date' => date('Y-m-d'),
        );
        $today = pdo_get('hai_daka_yiyuan',$todayWhere);
        if(!empty($today) && $today['status']==1) {
            returnJson(1, '已支付');
        }
        if(!$today) {
            $todayWhere['pay_money'] = $paymoney;
            $todayWhere['create_time'] = date('Y-m-d H:i:s');
            $todayWhere['order_sn'] = 'h'.date("ymdHis").sprintf("%06d", $this->member['uid']).rand(10,99);
            pdo_insert('hai_daka_yiyuan', $todayWhere);
            $id = $todayWhere['order_sn'];
        }else{
            $tt = time()-strtotime($today['create_time']);
            $save = array(
                'pay_money' => $paymoney
            );
            if($tt>1800 || $today['pay_money']!=$paymoney) {
                $newsn  = 'h'.date("ymdHis").sprintf("%06d", $this->member['uid']).rand(10,99);
                $save['order_sn'] = $newsn;
                $save['create_time'] = date('Y-m-d H:i:s');
                $id = $newsn;
            }else{
                $id = $today['order_sn'];
            }
            pdo_update('hai_daka_yiyuan',$save,array('id'=>$today['id']));
            
        }
        if($this->config['pay_type']!=1) {
            returnJson(1,'请稍等','',$this->createMobileUrl('pay',array('tid'=>$id)));
        }
        $order = array(
            'orderFee' => $paymoney,
            'orderTitle' => '打卡挑战',
            'orderTid' => $id,
            'module' => $_W['current_module']['name']
        );
        returnJson(0,'success',$order);
    }
    
    public function doMobilePay () {
        global $_GPC;
        $id = $_GPC['tid'];
        $order = pdo_get('hai_daka_yiyuan',array(
            'order_sn' => $id
        ));
        if(empty($order)) {
            message('订单不存在', '', 'error');
        }
        $params = array(
            'tid' => $id,      //充值模块中的订单号，此号码用于业务模块中区分订单，交易的识别码
            'ordersn' => $id,  //收银台中显示的订单号
            'title' => '打卡挑战',          //收银台中显示的标题
            'fee' => $order['pay_money'],      //收银台中显示需要支付的金额,只能大于 0
            'user' => $this->member['uid'],     //付款用户, 付款的用户名(选填项)
        );
        //调用pay方法
        $this->pay($params);exit;
    }

    

    protected function daka () {
        global $_W;
        $h = date('H');
        $nowTime = date('H:i');
        
//        if($h<5 || $h>=8) {
//            returnJson(1,'非打卡时间');
//        }
        
        if($nowTime<$this->config['daka_start_time'] || $nowTime>= $this->config['daka_end_time']) {
            returnJson(1,'非打卡时间');
        }
        
        $where = array(
            'uid' => $this->member['uid'],
            'create_date' => date('Y-m-d', strtotime('-1 day')),
            'status' => 1,
            'uniacid' => $_W['uniacid']
        );
        $yesterday = pdo_get('hai_daka_yiyuan',$where);
        if(!$yesterday) {
            returnJson(1,'昨日未参与');
        }
        if($yesterday['checked']==1) {
            returnJson(1,'已打卡，请勿重复操作');
        }
        $save = array(
            'checked' => 1,
            'checked_time' => date('Y-m-d H:i:s'),
        );
        $memWhere = array(
            'uid' => $this->member['uid'],
            'uniacid' => $_W['uniacid']
        );
        $mem = pdo_get('hai_daka_member',$memWhere);
        $res = pdo_update('hai_daka_member',array(
            'lianxu' => $mem['lianxu']+1
        ),$memWhere);
        $res = pdo_update('hai_daka_yiyuan',$save,$where);
        if($res) {
            if($this->config['zhanji_auto']==1) {
                $url = murl('entry',array(
                    'do' => 'api',
                    'm' => 'hai105_daka',
                    'action' => 'sendhaibao',
                    'uid' => $this->member['uid']
                ),'',true);
                backOpen($url);
            }
            $this->_addScore($this->member['uid'], $this->config['score'], '打卡送积分');
            
            returnJson(0,'打卡成功');
        }else {
            returnJson(1,'打卡失败，请重试');
        }
    }

    public function initdata() {
        global $_W;
        $hour = date('H');
        $nowTime = date('H:i');
        $todayDate = date('Y-m-d');
        $yesDate = date('Y-m-d', strtotime('-1 day'));
        $todayWhere = array(
            'status' => 1,
            'uniacid' => $_W['uniacid'],
            'create_date' => $todayDate
        );
        $yesterdayWhere = array(
            'status' => 1,
            'uniacid' => $_W['uniacid'],
            'create_date' => $yesDate
        );
//        if ($hour < 5) {
//            $todayDate = $yesDate;
//        }
        $allNumber = pdo_fetchcolumn("select count(*) from " . tablename('hai_daka_yiyuan') . " where status=1 AND create_date='{$todayDate}' and uniacid='{$_W['uniacid']}'");
        
 //       $allMoney = pdo_fetchcolumn("select sum(pay_money) from " . tablename('hai_daka_yiyuan') . " where status=1 AND create_date='{$todayDate}' and uniacid='{$_W['uniacid']}'");
        
                
//昨天和今天本人的支付信息
          $day = date('d');
        if(intval($day) == 18)
        {
            $mytodayWhere = array(
                'status' => 1,
                'create_date' => $todayDate,
                'uniacid' => $_W['uniacid'],
                'uid' => $this->member['uid']
            );

            $mytoday = pdo_get('hai_daka_yiyuan', $mytodayWhere);
            $myyesWhere = array(
                'status' => 1,
                'create_date' => $yesDate,
                'uniacid' => $_W['uniacid'],
                'uid' => $this->member['uid']
            );
            $myyesterday = pdo_get('hai_daka_yiyuan', $myyesWhere);
            if(!$myyesterday) {
                $myyesterday = array(
                    'checked' => 0,
                    'status' => 0
                );
            }
        }else{
            //昨天本人的打卡情况
            $myyesWhere = array(
                'checked' => 1,
                'create_date' => $yesDate,
                'uniacid' => $_W['uniacid'],
                'uid' => $this->member['uid']
            );
            $myyesterday = pdo_get('hai_daka_yiyuan', $myyesWhere);
            if(!empty($myyesterday))
            {
                $mytodayWhere = array(
                    'status' => 1,
                    'create_date' => $todayDate,
                    'uniacid' => $_W['uniacid'],
                    'uid' => $this->member['uid']
                );
                //今天是否付钱
                $mytoday = pdo_get('hai_daka_yiyuan', $mytodayWhere);
                if(empty($mytoday))
                {
                    $mytodaywhere = array(
                        'status' => 1,
                        'create_time' => date('Y-m-d H:i;s',time()),
                        'create_date' => $todayDate,
                        'uniacid' => $_W['uniacid'],
                        'uid' => $this->member['uid'],
                        'pay_money' => 0.00
                    );
                    pdo_insert('hai_daka_yiyuan',$mytodaywhere);

                    $mytodayWhere = array(
                        'status' => 1,
                        'create_date' => $todayDate,
                        'uniacid' => $_W['uniacid'],
                        'uid' => $this->member['uid']
                    );
                    $mytoday = pdo_get('hai_daka_yiyuan', $mytodayWhere);
                }

            }else{
                //昨天本人的支付信息
                $myyesWhere = array(
                    'status' => 1,
                    'create_date' => $yesDate,
                    'uniacid' => $_W['uniacid'],
                    'uid' => $this->member['uid']
                );
                $myyesterday = pdo_get('hai_daka_yiyuan', $myyesWhere);
                if(!$myyesterday) {
                    $myyesterday = array(
                        'checked' => 0,
                        'status' => 0
                    );
                }
            }
        }
        
        $successNum = pdo_fetchcolumn("select count(*) from " . tablename('hai_daka_yiyuan') . " where status=1 AND checked=1 AND create_date='{$yesDate}'  and uniacid='{$_W['uniacid']}'");
        $failNum = pdo_fetchcolumn("select count(*) from " . tablename('hai_daka_yiyuan') . " where status=1 AND checked=0 AND create_date='{$yesDate}'  and uniacid='{$_W['uniacid']}'");

        //倒计时秒数
        $now = time();
        if ($nowTime >= $this->config['daka_start_time']) {
            $to = date('Y-m-d H:i:s', strtotime($this->config['daka_start_time'].' +1 day'));
        } else {
            $to = date('Y-m-d H:i:s',strtotime($this->config['daka_start_time']));
        }
        $totime = strtotime($to);
        $dajishi = $totime - $now;
        if ($myyesterday['status'] == 1 && $myyesterday['checked'] == 0 && $nowTime >= $this->config['daka_start_time'] && $nowTime < $this->config['daka_end_time']) {
            $dajishi = 0;
        }
        $headlist = pdo_getall('hai_daka_yiyuan', $todayWhere, array('id', 'uid', 'status', 'checked', 'checked_time', 'create_time'), '', 'id desc', array(1, 20));
        foreach ($headlist as $k => $v) {
            $fans = mc_fansinfo($v['uid']);
            if(!$fans['headimgurl']){
                $headlist[$k]['headimgurl'] = 'http://pay.wao021.cn/Static/sun.png';
            }else{
                $headlist[$k]['headimgurl'] = $fans['headimgurl'];
            }
        }
        if($this->config['pay_list']){
            $this->config['pay_list'] = json_decode($this->config['pay_list'],true);
        }

        //赞助信息
        $zanzhu = pdo_get('hai_daka_zanzhu',array(
            'uniacid' => $_W['uniacid'],
            'date' => date('Y-m-d',strtotime('+1 day'))
        ));
        
        $data = array(
            'allNumber' => (int) $allNumber,
            'allMoney' => (int) $allNumber*10,
            'mytoday' => $mytoday ? $mytoday : array(
                'status' => 0
            ),
            'daojishi' => (int) $dajishi,
            'myyesterday' => $myyesterday,
            'successNumber' => (int) $successNum,
            'failNumber' => (int) $failNum,
            'time' => array(
                'h' => (int) $hour,
                'i' => (int) date('i'),
                's' => (int) date('s'),
            ),
            'now' => $nowTime,
            'headlist' => $headlist,
            'userinfo' => $this->member,
            'config'=>$this->config,
            'zanzhu' => $zanzhu
        );
        returnJson(0, '', $data);
    }
    
    protected function _checkLianxu () {
        global $_W;
        //昨天没参加
        $zwhere = array(
            'uid' => $this->member['uid'],
            'status' => 1,
            'create_date' => date('Y-m-d', strtotime('-1 day'))
        );
        $z = pdo_get('hai_daka_yiyuan',$zwhere);
        $where = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $this->member['uid']
        );
        if(!$z) {
            pdo_update('hai_daka_member',array('lianxu'=>0),$where);
        }
        if(date('H:i')>$this->config['daka_end_time'] && $z['checked']==0) {
            pdo_update('hai_daka_member',array('lianxu'=>0),$where);
        }
    }

    protected function jssdk() {
        $data['jssdk'] = getJsSign($this->data['url']);
        $data['share'] = array(
            'title' => '赌你起不来,不服来挑战!',
            'desc' => '挑战早起打卡，早起有钱拿',
            'link' => $this->data['url'],
            'imgUrl' => 'http://pay.wao021.cn/Static/sun.png'
        );
        returnJson(0, '', $data);
    }

    protected function dakalist() {
        global $_W,$_GPC;
        $size = 10;
        $page = max(1,$_GPC['page']);
        $limit = " ORDER BY checked_time DESC LIMIT ". ($page-1)*$size.", {$size}";
        
        $where = array(
            ':uniacid' => $_W['uniacid'],
            ':create_date' => date('Y-m-d', strtotime('-1 day')),
            ':status' => 1,
            ':checked' => 1
        );
        //$list = pdo_getall('hai_daka_yiyuan', $where);
        $list = pdo_fetchall('select * from '.tablename('hai_daka_yiyuan').' where uniacid=:uniacid and create_date=:create_date and status=:status'
                . ' and checked=:checked '.$limit,$where);
        foreach ($list as $k=>$v) {
            $fans = mc_fansinfo($v['uid']);
            $list[$k]['headimgurl'] = $fans['headimgurl'];
            $list[$k]['nickname'] = $fans['nickname'];
            $mwhere = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $v['uid']
            );
            $list[$k]['lianxu'] = 0;
            $mem = pdo_get('hai_daka_member',$mwhere);
            if($mem) {
                $list[$k]['lianxu'] = $mem['lianxu'];
            }
            
//            $allmoney = pdo_fetc("select sum('money') from ".tablename('hai_daka_yiyuan')." where uniacid=:uniacid and uid=:uid",array(
//                ':uniacid' => $_W['uniacid'],
//                ':uid' => $v['uid']
//            ));
            $mem = pdo_get('hai_daka_yiyuan', array('uid'=>$v['uid'],'uniacid'=>$_W['uniacid']), array('sum(money)'));
            $allmoney = $mem[0];
            $list[$k]['allmoney'] = $allmoney>0?$allmoney:0;
        }
        if(!empty($list)) {
            returnJson(0, 'success',$list);
        }else{
            returnJson(1, 'null');
        }
        
    }
    
    protected function _addMember () {
        global $_W;
        $where = array(
            'uid' => $_W['member']['uid'],
            'uniacid' => $_W['uniacid']
        );
        $mem = pdo_get('hai_daka_member',$where);
        if(!$mem) {
            $data = $where;
            $data['create_time'] = date('Y-m-d H:i:s');
            pdo_insert('hai_daka_member',$data);
        }
    }
    
    protected function _pay ($order,$money,$payment,$certurl,$isQiye=false) {
        global $_W;
        $data = array(
            'trade_no' => $order['order_sn'],
            'send_name' => '早起打卡挑战',
            'openid' => mc_uid2openid($order['uid']),
            'total_amount' => $money*100,//分
            'wishing' => '早起挑战奖励金,加油,再接再厉',
            'ip' => $_W['clientip'],
            'act_name' => '早起挑战活动',
            'remark' => '早起挑战奖励金'
        );
        if($isQiye){
            $data['trade_no'] = 'atm'.$data['trade_no'];
            //处理提现用企业付款
            return qiyePay($data,$payment,$certurl);
        }else{
            $data['trade_no'] = 'out'.$data['trade_no'];
        }
        if($this->config['fenmoney_type']==1) {
            return qiyeLuckyMoney($data,$payment,$certurl);
        }elseif($this->config['fenmoney_type']==2){
            return qiyePay($data,$payment,$certurl);
        }else{
            load()->model('mc');
            $content = '早起挑战奖励金';
            mc_credit_update($order['uid'], 'credit2', $money, array(0, $content,$_W['current_module']['name']));
            if($_W['account']['level'] >= ACCOUNT_SUBSCRIPTION_VERIFY) { 
                //$url = murl('mc','','',true);
//                $url = murl('entry',array(
//                    'm' => 'hai105_daka',
//                    'do' => 'cover',
//                ),'',true);
                $url = murl('mc',array(
                    'a' => 'bond',
                    'do' => 'credits',
                    'credittype' => 'credit2',
                    'type' => 'record',
                    'period' => 1
                ),'',true);
                $text = "恭喜你有新的现金入账,{$content}\n<a href='{$url}#/my'>查看记录</a>";
                $message = array(
                    'touser' => mc_uid2openid($order['uid']),
                    'msgtype' => 'text',
                    'text' => array('content' => urlencode($text))
                );
                $account_api = WeAccount::create();
                $account_api->sendCustomNotice($message);
            }
            return ture;
        }
        
    }
    protected function _paylianxu ($order,$money,$payment,$certurl) {
        global $_W;
        $data = array(
            'trade_no' => $order['order_sn'],
            'send_name' => '连续打卡奖励',
            'openid' => mc_uid2openid($order['uid']),
            'total_amount' => $money*100,//分
            'wishing' => '连续打卡奖励,加油,再接再厉',
            'ip' => $_W['clientip'],
            'act_name' => '连续打卡奖励',
            'remark' => '连续打卡奖励'
        );
        if($this->config['fenmoney_type']==1) {
            return qiyeLuckyMoney($data,$payment,$certurl);
        }else{
            return qiyePay($data,$payment,$certurl);
        }
        
    }
    
    protected function cron () {
        
        set_time_limit(0);
        
        global $_W,$_GPC;
        load()->func('cron');
        $hour = date('H');
        if($hour<8) {
            echo 'only 8 later can do this';
            exit;
        }
        $ispaywhere = array(
            'uniacid' => $_W['uniacid'],
            'key' => 'ispaying'
        );
        $r = pdo_get('hai_daka_config',$ispaywhere);
        //非调试模式下 防止重复执行
        if($r['value']==1 && IS_DEBUG==1){
            exit('ispaying');
        }
        pdo_update('hai_daka_config',array(
            'value' => 1
        ),$ispaywhere);
        pdo_update('hai_daka_config',array('value'=>date('Y-m-d')),array(
            'uniacid' => $_W['uniacid'],
            'key' => 'warnfaildate'
        ));

        $dd = 1;
        if(IS_DEBUG==2) {
            $dd = $_GPC['day'];
            if($dd<=0) {
                $dd = 1;
            }
        }
       
        $time = date('Y-m-d', strtotime('-'.$dd.' day'));
        $successW = array(
            'create_date' => $time,
            'status' => 1,
            'checked' => 1,
            'uniacid' => $_W['uniacid']
        );
        $allNumber = pdo_fetchcolumn("select count(*) from ". tablename('hai_daka_yiyuan'). " where create_date=:create_date and status=:status  and uniacid=:uniacid",array(
            ':create_date' => $time,
            ':status' => 1,
            ':uniacid' => $_W['uniacid']
        ));
        $successNumber = pdo_fetchcolumn("select count(*) from ".tablename('hai_daka_yiyuan')." where create_date=:create_date and status=:status and checked=:checked and uniacid=:uniacid",array(
            ':create_date' => $time,
            ':status' => 1,
            ':checked' => 1,
            ':uniacid' => $_W['uniacid']
        ));
        
        $allpaymoney = pdo_fetchcolumn("select sum(pay_money) from ".tablename('hai_daka_yiyuan')." where "
                . "create_date='{$time}' and uniacid={$_W['uniacid']} and status=1");
        
        $failpaymoney = pdo_fetchcolumn("select sum(pay_money) from ".tablename('hai_daka_yiyuan')." where "
                . "create_date='{$time}' and uniacid={$_W['uniacid']} and status=1 and checked=0");
        $successpaymoney =  $allpaymoney-$failpaymoney;
        
        $allMoney = $allpaymoney-$failpaymoney*$this->config['tax_bili'];

        $zanzhu = pdo_get('hai_daka_zanzhu',array(
            'uniacid' => $_W['uniacid'],
            'date' => date('Y-m-d')
        ));
        if($zanzhu && $zanzhu['money']>0 && is_numeric($zanzhu['money'])){
            $allMoney += $zanzhu['money'];
        }
        $successList = pdo_getall('hai_daka_yiyuan',$successW);
        $pay = uni_setting_load('payment', $_W['uniacid']);
        $payment = $pay['payment'];
        $paycert = $payment['wechat_refund']['cert'];
        $paykey = $payment['wechat_refund']['key'];
        $paycert = authcode($paycert, 'DECODE');
        $paykey = authcode($paykey, 'DECODE');
        $certurl = ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem';
        file_put_contents($certurl, $paykey.$paycert);
        foreach ($successList as $member) {
            if($member['money']>0) {
                continue;
            }
            $per = 10*($allNumber-$successNumber)/$successNumber;
            $per = round($per,2);
//            $per = $per>1?$per:1;
            $per = $per>5?5:$per;
            $res = $this->_pay($member, $per,$payment,$certurl);
            if($res) {
                $save = array(
                    'money' => $per,
                    'money_time' => date('Y-m-d H:i:s')
                );
                pdo_update('hai_daka_yiyuan',$save,array('id'=>$member['id']));
            }
        }
        
        unlink(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');
        pdo_update('hai_daka_config',array(
            'value' => 0
        ),$ispaywhere);
        echo '发放完成';
        $this->cronwarnfail();
    }
    
    protected function _addCron () {
        load()->func('cron');
        global $_W;
        $next = strtotime('8:10 +1 day');
        $cron = array(
            'module' => 'hai105_daka',
            'uniacid' => $_W['uniacid'],
            'type' => 1,
            'name' => 'pay',
            'filename' => 'pay',
            'nextruntime' => $next,
            'lastruntime' => $next,
            'status' => 1,
            'extra' => date('Y-m-d',TIMESTAMP)
        );
        $old = pdo_get('core_cron',$cron);
        if($old) {
            return true;
        }
        cron_add($cron);
    }
    
      public function doWebList () {
       global $_W,$_GPC;
        $_W['page']['title'] = '参与人员';
        $where = array();
        if(isset($_GPC['uid']))
        {
            $uid = $_GPC['uid'];
            $where['uid'] = $uid;
        }
        $size = 10;
        $page = max(1,$_GPC['page']);

        $limit = " ORDER BY id DESC LIMIT ". ($page-1)*$size.", {$size}";

        $where['create_date'] = $_GPC['date']?$_GPC['date']:date('Y-m-d');
        $where['uniacid'] = $_W['uniacid'];
        $where['status'] = 1;

        if(isset($_GPC['uid']))
        {
            $list = pdo_fetchall("select * from ".tablename('hai_daka_yiyuan')." where "
                . "create_date=:create_date AND uid=:uid AND uniacid=:uniacid AND status=:status {$limit}",
                $where);
            foreach ($list as $k=>$v) {
                $list[$k]['fansinfo'] = mc_fansinfo($v['uid']);
            }
        }else{
            $list = pdo_fetchall("select * from ".tablename('hai_daka_yiyuan')." where "
                . "create_date=:create_date AND uniacid=:uniacid AND status=:status {$limit}",
                $where);
            foreach ($list as $k=>$v) {
                $list[$k]['fansinfo'] = mc_fansinfo($v['uid']);
            }
        }

        if(isset($_GPC['uid']))
        {
            $total = pdo_fetchcolumn("select count(*) from ".tablename('hai_daka_yiyuan')." where "
                . "create_date=:create_date AND uid=:uid AND uniacid=:uniacid AND status=:status",
                $where);
        }else{
            $total = pdo_fetchcolumn("select count(*) from ".tablename('hai_daka_yiyuan')." where "
                . "create_date=:create_date AND uniacid=:uniacid AND status=:status",
                $where);
        }

        $pager = pagination($total, $page, $size);

        $cronurl = murl('entry',array(
            'do' => 'api',
            'm' => $_W['current_module']['name'],
            'action' => 'cron'
        ),'',true);

        include $this->template('list');
    }
    public function doMobileMy() {
        global $_W,$_GPC;
        if($_W['container']!='wechat') {
//            message('请在微信端打开');exit;
        }
        $seo['title'] = '个人中心';
        $share = $this->_setShare();
        checkauth();
        include $this->template('my');
    }
  
    protected function myinit () {
        global $_W;
        $my = pdo_get('hai_daka_yiyuan', array('uid'=>$this->member['uid'],'uniacid'=>$_W['uniacid'],'status'=>1), array(
            'sum(money) as allmoney','sum(pay_money) as allpaymoney'
        ));
        $mysuccess = pdo_get('hai_daka_yiyuan', array('uid'=>$this->member['uid'],'uniacid'=>$_W['uniacid'],'status'=>1,'checked'=>1), array(
            'count(*) as alldaka'
        ));
        $res['allin'] = $my['allpaymoney']>0?$my['allpaymoney']:0;
        $res['allmoney'] = $my['allmoney']>0?$my['allmoney']:0;
        $res['alldaka'] = $mysuccess['alldaka']>0?$mysuccess['alldaka']:0;
        $res['score'] = $this->member['credit1'];
          $res['money'] =  floatval($this->member['credit2']);
        returnJson(0, '',$res);
        
    }

    protected function getdatelist() {
        global $_W;
        $end = date('Y-m-d');
        $start = date('Y-m-d',strtotime('-5 month'));
        $sql = "select * from ".tablename('hai_daka_yiyuan')." where uid=:uid and uniacid=:uniacid and create_date>:start and create_date<:end and status=1 order by create_date";
        $list = pdo_fetchall($sql, array(
            ':end' => $end,
            ':start' => $start,
            ':uid' => $this->member['uid'],
            ':uniacid' => $_W['uniacid']
        ));
        
        $res = array(
            'selects' => array(),
            'events' => array()
        );
        if(!empty($list)) {
            foreach ($list as $k=>$v) {
                $v['create_date'] = date('Y-n-j', strtotime($v['create_date']));
                if($v['money']>0) {
                    $res['events'][$v['create_date']] = $v['money'];
                }elseif($v['checked']==1){
                    $res['events'][$v['create_date']] = '待发放';
                }else{
                    $res['events'][$v['create_date']] = '未打卡';
                }
                $res['selects'][] = $v['create_date'];
            }
        }
        returnJson(0, 'success', $res);
    }
    
    public function doWebConfig () {
        global $_W,$_GPC;
        $_W['page']['title'] = '参数设置';
        if(!$this->config['pay_list']){
            $this->config['pay_list'] = json_encode(array());
        }
        $this->config['menu'] = json_decode($this->config['menu'],true);
        $config = $this->config;
        if(!$_GPC['op']||$_GPC['op']=='index'){
            if($_W['isajax']){
                if(!isset($_GPC['daka_start_time'])) {
                    returnJson(1, '请设置打卡开始时间');
                }
                if(!isset($_GPC['daka_end_time'])) {
                    returnJson(1, '请设置打卡结束时间');
                }
                if($_GPC['daka_start_time']>=$_GPC['daka_end_time']) {
                    returnJson(1, '结束时间必须大于开始时间');
                }
                if($_GPC['daka_end_time']>'08:00') {
                    returnJson(1, '结束时间不能大于早晨8点');
                }
                if(!isset($_GPC['pay_type']) || ($_GPC['pay_type']!=1 && $_GPC['pay_type']!=2)) {
                    returnJson(1, '请选择支付方式');
                }
                if(!isset($_GPC['fenmoney_type'])) {
                    returnJson(1, '请选择发放奖励方式');
                }
                if(!isset($_GPC['tax_bili'])) {
                    returnJson(1, '请填写税率');
                }
                if($_GPC['tax_bili']>0.5) {
                    returnJson(1, '税率必须小于0.5');
                }
                if($_GPC['tax_bili']<0) {
                    returnJson(1, '税率必须大于0');
                }
                $payarr = array();
                $paytext = $_GPC['paytext'];
                $paymoney = $_GPC['paymoney'];
                if(is_array($paytext)){
                    foreach ($paytext as $k=>$v) {
                        $payarr[] = array(
                            'value' => $paymoney[$k],
                            'name' => $paytext[$k],
                        );
                    }
                }
                $configlist = array(
                    'daka_start_time' => $_GPC['daka_start_time'],
                    'daka_end_time' => $_GPC['daka_end_time'],
                    'pay_type' => $_GPC['pay_type'],
                    'fenmoney_type' => $_GPC['fenmoney_type'],
                    'tax_bili' => $_GPC['tax_bili'],
                    'share_type' => $_GPC['share_type'],
                    'share_title' => $_GPC['share_title'],
                    'share_des' => $_GPC['share_des'],
                    'share_img' => $_GPC['share_img'],
                    'tpl_type' => $_GPC['tpl_type'],
                    'sub_url' => $_GPC['sub_url'],
                    'is_debug' => $_GPC['is_debug'],
                    'index_title' => $_GPC['index_title'],
                    'ad_imgurl' => $_GPC['ad_imgurl'],
                    'ad_url' => $_GPC['ad_url'],
                    'score' => $_GPC['score'],
                    'per_money' => $_GPC['per_money'],
                    'rule' => $_GPC['rule'],
                    'pay_list' => $payarr?json_encode($payarr,JSON_UNESCAPED_UNICODE):'',
                    'paybtntext' => $_GPC['paybtntext'],
                    'refer_money' => $_GPC['refer_money'],
                    'atmtype' => $_GPC['atmtype'],
                    'atmminmoney' => $_GPC['atmminmoney']
                );
                foreach ($configlist as $k=>$v) {
                    pdo_update('hai_daka_config',array(
                        'value' => $v
                    ),array(
                        'key' => $k,
                        'uniacid' => $_W['uniacid']
                    ));
                }
                returnJson(0, '修改成功');
            }
        }elseif($_GPC['op']=='zanzhu'){
            $where = " 1 and uniacid={$_W['uniacid']}";
            $page = max($_GPC['page'],1);
            $size = 20;
            $limit = " ORDER BY date desc,id DESC LIMIT ". ($page-1)*$size.", {$size}";
            $list = pdo_fetchall("select * from ".tablename('hai_daka_zanzhu')." where "
            . " {$where} {$limit}");
            $total = pdo_fetchcolumn("select count(*) from ".tablename('hai_daka_zanzhu')." where "
            . " {$where}");
            $pager = pagination($total, $page, $size);
            if($_W['isajax']){
                if($_GPC['action']=='add'){
                    $data = array(
                        'uniacid' => $_W['uniacid'],
                        'date' => $_GPC['date'],
                        'money' => $_GPC['money'],
                        'name' => $_GPC['name'],
                        'url' => $_GPC['url']
                    );
                    if(!$data['money']||!$data['date']) {
                        returnJson(1,'金额和日期必填');
                    }
                    if($_GPC['id']){
                        $old = pdo_get('hai_daka_zanzhu',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
                        if(!$old) {
                            returnJson(1,'记录不存在');
                        }
                        pdo_update('hai_daka_zanzhu',$data,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
                    }else{
                        $old = pdo_get('hai_daka_zanzhu',array('uniacid'=>$_W['uniacid'],'date'=>$_GPC['date']));
                        if($old) {
                            returnJson(1,'日期已存在');
                        }
                        pdo_insert('hai_daka_zanzhu',$data);
                    }
                    returnJson(0,'操作成功');
                }elseif($_GPC['action']=='delete') {
                    if(!$_GPC['id']){
                        returnJson(1,'error');
                    }
                    $old = pdo_get('hai_daka_zanzhu',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
                    if(!$old) {
                        returnJson(1,'记录不存在');
                    }
                    pdo_delete('hai_daka_zanzhu',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
                    returnJson(0,'操作成功');
                }
            }
        }elseif($_GPC['op']=='warn'){
            $warnurl = murl('entry',array(
                'do' => 'api',
                'm' => $_W['current_module']['name'],
                'action' => 'cronwarn'
            ),'',true);
            if($_W['isajax']){
                $configlist = array(
                    'iswarn' => $_GPC['iswarn'],
                    'warntmpid' => $_GPC['warntmpid'],
                    'iswarnfail' => $_GPC['iswarnfail']
                );
                foreach ($configlist as $k=>$v) {
                    pdo_update('hai_daka_config',array(
                        'value' => $v
                    ),array(
                        'key' => $k,
                        'uniacid' => $_W['uniacid']
                    ));
                }
                returnJson(0, '修改成功');
            }
        }elseif($_GPC['op']=='menu'){
            if($_GPC['action']=='addmenu') {
                $config['menu'][] = array(
                    'text' => $_GPC['text'],
                    'url' => $_GPC['url'],
                    'imgurl' => $_GPC['imgurl']
                );
                $save = array(
                    'value' => json_encode($config['menu'])
                );
                pdo_update('hai_daka_config',$save,array(
                    'uniacid' => $_W['uniacid'],
                    'key' => 'menu'
                ));
                returnJson(0,'ok');
            }elseif($_GPC['action']=='deletemenu'){
                $id = $_GPC['id'];
                if(!$id){
                    $id = 0;
                }
                unset($config['menu'][$id]);
                $save = array(
                    'value' => json_encode($config['menu'])
                );
                pdo_update('hai_daka_config',$save,array(
                    'uniacid' => $_W['uniacid'],
                    'key' => 'menu'
                ));
                returnJson(0,'ok');
            }
        }
        
        include $this->template('config');
    }

    public function doMobilePaihang () {
        checkauth();
        $seo['title'] = '排行榜';
        $share = $this->_setShare();
        include $this->template('paihang');
    }
    
    public function lianxulist () {
        global $_W,$_GPC;
        $size = 10;
        $page = max(1,$_GPC['page']);
        $limit = "LIMIT ". ($page-1)*$size.", {$size}";
        $where = array(
            'uniacid' => $_W['uniacid']
        );
        $list = pdo_getall('hai_daka_member',$where,'','','lianxu desc',$limit);
        if($list) {
            foreach ($list as $k=>$v) {
                $fans = mc_fansinfo($v['uid']);
                if(!$fans) {
                    $fans['nickname'] = '无';
                    $fans['headimgurl'] = '';
                }
                $list[$k]['fans'] = $fans;
                $where = array(
                    ':uid' => $v['uid'],
                    ':uniacid' => $_W['uniacid']
                );
                $list[$k]['lianxu'] = pdo_fetchcolumn('select lianxu from '.tablename('hai_daka_member')." where uid=:uid and uniacid=:uniacid",$where);
            }
            returnJson(0, count($list),$list);
        }else{
            returnJson(1,'empty');
        }
    }
    
    public function jianglilist () {
        global $_W,$_GPC;
        $size = 10;
        $page = max(1,$_GPC['page']);
        $limit = "LIMIT ". ($page-1)*$size.", {$size}";
        $list = pdo_fetchall("select uid,sum(money) from ". tablename('hai_daka_yiyuan')." where uniacid={$_W['uniacid']} group by uid order by sum(money) desc $limit");
        if($list) {
            foreach ($list as $k=>$v) {
                $fans = mc_fansinfo($v['uid']);
                if(!$fans) {
                    $fans['nickname'] = '无';
                    $fans['headimgurl'] = '';
                }
                $list[$k]['fans'] = $fans;
            }
            returnJson(0,count($list),$list);
        }else{
            returnJson(1,'empty');
        }
    }
    
    /*
     * 懒虫榜
     */
    public function lazylist () {
        global $_W,$_GPC;
        $size = 10;
        $page = max(1,$_GPC['page']);
        $limit = "LIMIT ". ($page-1)*$size.", {$size}";
        $where = array(
            'uniacid' => $_W['uniacid'],
            'status' => 1,
            'date' => date('Y-m-d', strtotime('-1 day')),
            'checked' => 0
        );
        $list = pdo_getall('hai_daka_yiyuan',$where,'','','id desc',$limit);
        if($list) {
            foreach ($list as $k=>$v) {
                $fans = mc_fansinfo($v['uid']);
                if(!$fans) {
                    $fans['nickname'] = '无';
                    $fans['headimgurl'] = '';
                }
                $list[$k]['fans'] = $fans;
            }
            returnJson(0, count($list),$list);
        }else{
            returnJson(1,'empty');
        }
    }
  
    
    public function doMobileZhanji () {
        checkauth();
        global $_W,$_GPC;
        $where = array(
            'uid' => $this->member['uid'],
            'uniacid' => $_W['uniacid'],
            'checked' => 1
        );
        $res = pdo_get('hai_daka_yiyuan',$where,array(
            'sum(money)','count(*)'
        ));

        $file = ATTACHMENT_ROOT."images/{$_W['uniacid']}_{$this->member['uid']}.jpg";//存储的文件名
        if(!file_exists($file) || !$_GPC['update_zhanji']) {
            $back = $_W['attachurl'].$this->config['zhanji_back'];
            $read_backgroud = file_get_contents($back);
            $background = imagecreatefromstring($read_backgroud);//把底图载入画板

            $font = MODULE_ROOT.'/static/fzkt.TTF';//思源黑体
            $color= imagecolorallocate($background,28,199,33);//颜色
            imagettftext($background,35,0,200,90,$color, $font,'早起'. $res[1].'天');
            imagettftext($background,35,0,200,140,$color, $font,'累计奖励'. $res[0].'元');

            $read_qrcode = file_get_contents($this->member['headimgurl']);//读取头像
            $erweima = imagecreatefromstring($read_qrcode);//把二维码载入画板
            if (imageistruecolor($erweima)) {
                imagetruecolortopalette($erweima, false, 65535); 
            }
            $qwidth = imagesx($erweima);//二维码图片宽度
            $qheight = imagesy($erweima);//二维码的图片高度
            imagecopyresampled($background,$erweima,40,40,0,0,132,132,$qwidth,$qheight);//把二维码放到背景图片上
            imagejpeg($background,$file,100);//最后一个参数是图片质量,保存图片到文件
            isetcookie('update_zhanji', true, 3600);
        }
        $background = file_get_contents($file);
        header("Content-Type: image/jpeg");
        echo $background;exit;
    }
    
    protected function _setShare () {
        global $_W;
        if($this->config['share_type']==1) {
            $share = array(
                'title' => $this->config['share_title'],
                'desc' => $this->config['share_des'],
                'link' => $_W['siteroot'].$this->createMobileUrl('cover'),
                'imgUrl' => $_W['attachurl'].$this->config['share_img']
            );
        }elseif($this->config['share_type']==2){
            $my = pdo_get('hai_daka_yiyuan',array('uniacid'=>$_W['uniacid'],'uid'=>$this->member['uid'],'status'=>1),array(
                'count(*) as canyu','sum(money) as allmoney'
            ));

            $mycheck = pdo_get('hai_daka_yiyuan',array('uniacid'=>$_W['uniacid'],'uid'=>$this->member['uid'],'status'=>1,'checked'=>1),array(
                'count(*) as alldaka'
            ));

            $my['allmoney'] = $my['allmoney']>0?$my['allmoney']:0;
            $share = array(
                'title' => "我已打卡{$mycheck['alldaka']}天，累计奖励{$my['allmoney']}元",
                'desc' => '早起打卡养成好习惯',
                'link' => $_W['siteroot'].$this->createMobileUrl('cover'),
                'imgUrl' => $_W['attachurl'].$this->config['share_img']
            );
        }
        return $share;
    }
    
    public function doWebHaibao () {
        global $_W,$_GPC;
        $_W['page']['title'] = '海报设计';
        if($_W['setting']['remote']['type']!=3) {
//            message('请开启七牛云附件才能使用该功能', $redirect, 'error');
        }
        $config = $this->config;
        if($_W['isajax']) {
            
            $configlist = array(
                'zhanji_back' => $_GPC['zhanji_back'],
                'zhanji_params' => $_GPC['zhanji_params'],
                'zhanji_type' => $_GPC['zhanji_type'],
                'zhanji_auto' => $_GPC['zhanji_auto']
            );
            
            if($_GPC['action']=='preview') {
                $this->config['zhanji_back'] = $_GPC['zhanji_back'];
                $this->config['zhanji_params'] = $_GPC['zhanji_params'];
                $url = $this->_getHaiBao(true);
                returnJson(0,'',$url);
            }
      
            foreach ($configlist as $k=>$v) {
                pdo_update('hai_daka_config',array(
                    'value' => $v
                ),array(
                    'key' => $k,
                    'uniacid' => $_W['uniacid']
                ));
            }
            
//            if($_W['setting']['remote']['type']!=3) {
//    //            message('请开启七牛云附件才能使用该功能', $redirect, 'error');
//                returnJson(0, '请开启七牛云附件才能使用该功能');
//            }
        
            returnJson(0, '修改成功');
        }
        $config['zhanji_params'] = htmlspecialchars_decode($config['zhanji_params']);
        $config['zhanji_params'] = json_decode($config['zhanji_params'],true);
        
        $new = array(
            'zhanji_back' => $config['zhanji_back'],
            'zhanji_params' => $config['zhanji_params'],
            'zhanji_auto' => $config['zhanji_auto'],
            'zhanji_type' => $config['zhanji_type']
        );
        $config = $new;
        
        include $this->template('haibao');
    }
    
    protected function _getHaiBao ($pre='') {
        global $_W;
        load()->func('file');
        $qiniu = $_W['setting']['remote']['qiniu'];
        if(!$qiniu['accesskey'] || !$qiniu['secretkey'] || !$qiniu['bucket'] || !$qiniu['url']) {
            return false;
        }
        if($pre){
            $m = pdo_get('hai_daka_member',array('uniacid'=>$_W['uniacid']));
            $this->member['uid'] = $m['uid'];
            $fans = mc_fansinfo($this->member['uid']);
            $this->member = $fans;
        }
        $fans = mc_fansinfo($this->member['uid']);
        //$this->member = $fans;
        
        if($this->config['zhanji_params']) {
            $params = json_decode(htmlspecialchars_decode($this->config['zhanji_params']),true);
            $resUrl = $_W['attachurl'].$this->config['zhanji_back'];
            if($_W['setting']['remote']['type']!=3) {
                $pathname = file_remote_attach_fetch($resUrl);
                $_W['setting']['remote']['type'] = 3;
                file_remote_upload($pathname);
                $resUrl = $qiniu['url'].'/'.$pathname;
            }
            $_W['setting']['remote']['type'] = 3;
            if(!empty($params)){
                $resUrl .= '?watermark/3';
                foreach ($params as $v) {
                    if($v['type']==1) {
                        if(!$v['content']) {
                            continue;
                        }
                        $content = $this->_transContent($v['content']);
                        $resUrl .= '/text/'.base64url($content);
                        
                        if($v['gravity']) {
                            $resUrl .= '/gravity/'.$v['gravity'];
                        }

                        if($v['fontsize']) {
                            $resUrl .= '/fontsize/'.$v['fontsize'];
                        }
                        if($v['font']) {
                            $resUrl .= '/font/'.base64url($v['font']);
                        }
                        if($v['right']) {
                            $resUrl .= '/dx/'.$v['right'];
                        }
                        if($v['bottom']) {
                            $resUrl .= '/dy/'.$v['bottom'];
                        }
                        if($v['color']) {
                            $resUrl .= '/fill/'.base64url($v['color']);
                        }
                        if($v['dissolve']) {
                            $resUrl .= '/dissolve/'.$v['dissolve'];
                        }
                    }elseif($v['type']==2) {
                        if(!$fans['headimgurl']) {
                            $fans['headimgurl'] = MODULE_URL.'static/img/headerimg.png';
    //                        $fans['headimgurl'] = 'http://jianghai.wao021.cn/addons/hai105_daka/static/img/headerimg.png';
                        }
                        $pathname = file_remote_attach_fetch($fans['headimgurl']);
                        $remote = file_remote_upload($pathname);
                        $resUrl .= '/image/'.base64url($qiniu['url'].'/'.$pathname.'?roundPic/radius/!50p');
                        if($v['gravity']) {
                            $resUrl .= '/gravity/'.$v['gravity'];
                        }
                
                        if($v['right']) {
                            $resUrl .= '/dx/'.$v['right'];
                        }
                        if($v['bottom']) {
                            $resUrl .= '/dy/'.$v['bottom'];
                        }
                        if($v['dissolve']) {
                            $resUrl .= '/dissolve/'.$v['dissolve'];
                        }
                        if($v['ws']) {
                            $resUrl .= '/ws/'.$v['ws'];
                        }
                    }elseif($v['type']==3) {
                        $qr = $this->_getMyQrcode();
                        $qrurl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($qr['ticket']);
                        
                        $resUrl .= '/image/'.base64url($qrurl);
                        if($v['gravity']) {
                            $resUrl .= '/gravity/'.$v['gravity'];
                        }
                        if($v['right']) {
                            $resUrl .= '/dx/'.$v['right'];
                        }
                        if($v['bottom']) {
                            $resUrl .= '/dy/'.$v['bottom'];
                        }
                        if($v['dissolve']) {
                            $resUrl .= '/dissolve/'.$v['dissolve'];
                        }
                        if($v['ws']) {
                            $resUrl .= '/ws/'.$v['ws'];
                        }
                    }
                }
            }
            
            return $resUrl;
        }
    }
    
    protected function _transContent ($content) {
        global $_W;
        $fans = mc_fansinfo($this->member['openid']);
        $condition = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $this->member['uid'],
            'status' => 1
        );
        $arr = pdo_get('hai_daka_yiyuan', $condition, array('count(*) as allcanyu','sum(money) as allmoney'));
        
        $daka = pdo_get('hai_daka_yiyuan',array(
            'uid' => $this->member['uid'],
            'create_date' => date('Y-m-d', strtotime('-1 day')),
            'checked' => 1
        ));
        
        $condition['checked'] = 1;
        $carr = pdo_get('hai_daka_yiyuan', $condition, array('count(*) as alldaka'));
        $replace = array(
            '%yyyy%' => date('Y'),
            '%mm%' => date('m'),
            '%dd%' => date('d'),
            '%hh%' => date('H'),
            '%ii%' => date('i'),
            '%ss%' => date('s'),
            '%nickname%' => $fans['nickname']?$fans['nickname']:'昵称',
            '%all%' => $arr['allcanyu']>0?$arr['allcanyu']:0,
            '%money%' => $arr['allmoney']>0?$arr['allmoney']:0,
            '%lianxu%' => $this->member['lianxu']>0?$this->member['lianxu']:0,
            '%check%' => $carr['alldaka']>0?$carr['alldaka']:0,
            '%daka%' => $daka?date('H:i',strtotime($daka['checked_time'])):'--:--'
        );
        foreach ($replace as $k=>$v) {
            $content = str_replace($k, $v, $content);
        }
        return $content;
    }
    
    public function doWebLianxu () {
        global $_W,$_GPC;
        
        if(!$_GPC['date']) {
            $_GPC['date'] = date('Y-m', strtotime('-1 month'));
        }
        $date = date('Y-m', strtotime($_GPC['date']));
        $old = pdo_fetch("select * from ".tablename('hai_daka_lianxu')." where date='{$date}' and "
        . "uniacid={$_W['uniacid']}");
        
        
        if($_W['isajax']) {
            $action = $_GPC['action'];
            $money = $_GPC['money'];
            if($action!='fafang') {
                return false;
            }
            $date = date('Y-m',strtotime('-1 month'));
            $list = pdo_fetchall("select * from ".tablename('hai_daka_lianxu')." where date='{$date}' and "
                . "uniacid={$_W['uniacid']}");
            if(!$list) {
                returnJson(1, '上月没有连续打卡合格用户');
            }
            $per = round($money/count($list),2);
            if($per<1) {
                returnJson(1, '单人金额小于1元,无法发放');
            }
            
            $pay = uni_setting_load('payment', $_W['uniacid']);
            $payment = $pay['payment'];
            $paycert = $payment['wechat_refund']['cert'];
            $paykey = $payment['wechat_refund']['key'];
            $paycert = authcode($paycert, 'DECODE');
            $paykey = authcode($paykey, 'DECODE');
            $certurl = ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem';
            file_put_contents($certurl, $paykey.$paycert);
            foreach ($list as $member) {
                if($member['money']>0) {
                    continue;
                }
                $res = $this->_paylianxu($member, $per,$payment,$certurl);
                if($res) {
                    $save = array(
                        'money' => $per,
                        'money_time' => date('Y-m-d H:i:s')
                    );
                    pdo_update('hai_daka_lianxu',$save,array('id'=>$member['id']));
                }
            }

            unlink(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');

            
            returnJson(0,'发放成功');
        }
        
        
        if(!$old && $date==date('Y-m', strtotime("-1 month"))) {
            $this->_handleLastMonth();
        }
        
        $_W['page']['title'] = '连续打卡';
        $size = 10;
        $page = max(1,$_GPC['page']);
        $limit = " ORDER BY id DESC LIMIT ". ($page-1)*$size.", {$size}";
       
        $list = pdo_fetchall("select * from ".tablename('hai_daka_lianxu')." where "
                . "date='{$date}' AND uniacid={$_W['uniacid']} {$limit}");
        foreach ($list as $k=>$v) {
            $list[$k]['fansinfo'] = mc_fansinfo($v['uid']);
        }
        $total = pdo_fetchcolumn("select count(*) from ".tablename('hai_daka_lianxu')." where "
                . "date='{$date}' AND uniacid={$_W['uniacid']}");
        $pager = pagination($total, $page, $size);
        include $this->template('lianxu');
    }
    
    /*
     * 计算上月满足21天连续打卡的
     */
    protected function _handleLastMonth () {
        global $_W,$_GPC;
        $lianxuday = 21;
        $date = date('Y-m', strtotime('-1 month'));
        $start = date('Y-m-01', strtotime($date));
        $end = date('Y-m-01', strtotime($date." +1 month"));
        $list = pdo_fetchall("select count(*),uid from ".tablename('hai_daka_yiyuan')." where uniacid={$_W['uniacid']}"
        . " and status=1 and checked=1 and create_date>='{$start}' and create_date<'{$end}' group by uid");
        $new = array();
        
        foreach ($list as $v) {
            //参与天数不到21天 肯定不可能连续打卡21天
            if($v['count(*)']<$lianxuday) {
                continue;
            }
            $i = $start;
            $count = 0;
            while($i<$end) {
                $iwhere = array(
                    'uid' => $v['uid'],
                    'checked' => 1,
                    'status' => 1,
                    'create_date' => $i
                );
                $idata = pdo_get('hai_daka_yiyuan',$iwhere);
                if($idata) {
                    $count++;
                }else if($count>=$lianxuday){
                    break;
                }else{
                    $count=0;
                }
                
                $i = date('Y-m-d', strtotime($i." +1 day"));
            }
            if($count>=$lianxuday) {
                $v['days'] = $count;
                $new[] = $v;
            }
        }
        foreach ($new as $nv) {
            $data = array(
                'uid' => $nv['uid'],
                'uniacid' => $_W['uniacid'],
                'date' => $date,
                'days' => $nv['days'],
                'order_sn' => 'lx'.$nv['uid'].date('ymdhis').rand(10,99)
            );
            pdo_insert('hai_daka_lianxu',$data);
        }
    }
    
    public function doWebSetlianxu () {
        global $_W,$_GPC;
        $_W['page']['title'] = '连续打卡设置';
        $config = $this->config;
        if($_W['isajax']) {
            $configlist = array(
                'lianxu_status' => $_GPC['lianxu_status'],//1开启 关闭
                'lianxu_imgurl' => $_GPC['lianxu_imgurl'],//活动主图
                'lianxu_detail' => $_GPC['lianxu_detail'],
            );
      
            foreach ($configlist as $k=>$v) {
                pdo_update('hai_daka_config',array(
                    'value' => $v
                ),array(
                    'key' => $k,
                    'uniacid' => $_W['uniacid']
                ));
            }
            returnJson(0, '修改成功');
        }
        
        include $this->template('setlianxu');
    }
    
    
    protected function getstar () {
        global $_W;
        if(date('H')<8) {
            returnJson(1, 'null');
        }
        $date = date('Y-m-d', strtotime('-1 day'));
        $zaoqi = pdo_fetch("select * from ".tablename('hai_daka_yiyuan')." where create_date='{$date}' "
        . " and uniacid={$_W['uniacid']} and status=1 and checked=1 order by checked_time");
        if($zaoqi) {
            $zaoqi['fans'] = mc_fansinfo($zaoqi['uid']);
            $zaoqi['checked_time'] = date('H:i:s',strtotime($zaoqi['checked_time']));
        }
        
        $lianxu = pdo_fetch("select * from ".tablename('hai_daka_member')." where uniacid={$_W['uniacid']} order by lianxu desc");
        if($lianxu) {
            $lianxu['fans'] = mc_fansinfo($lianxu['uid']);
        }
        
        returnJson(0, '',array(
            'zaoqi' => $zaoqi?$zaoqi:false,
            'lianxu' => $lianxu
        ));
    }
    
    
    protected function sendhaibao () {
        global $_GPC;
        if(!$_GPC['uid']) {
            return FALSE;
        }
        $local = pdo_get('hai_daka_member',array('uid'=>$_GPC['uid']));
        $fans = mc_fansinfo($_GPC['uid']);
        $fans['lianxu'] = $local['lianxu'];
        $this->member = $fans;
        $openid = mc_uid2openid($_GPC['uid']);
        $haibao = $this->_getHaiBao();
        if(!$haibao) {
            return false;
        }
        $resLocalUrl = file_remote_attach_fetch($haibao);

        $account_api = WeAccount::create();
        $result = $account_api->uploadMedia(ATTACHMENT_ROOT . $resLocalUrl, 'image');
        $media_id = $result['media_id'];
        if(!$media_id) {
            return false;
        }
        sendImg($openid, $media_id);
    }
    
    protected function gethaibao () {
        $haibao = $this->_getHaiBao();
        returnJson(1,'',$haibao);
    }
    
    protected function _addScore ($uid,$score,$content='活动送积分') {
        global $_W;
        if(!$uid) {
            return false;
        }
        if($score<=0) {
            return false;
        }
        load()->model('mc');
        mc_credit_update($uid, 'credit1', $score, array(0, $content,$_W['current_module']['name']));
        if($_W['account']['level'] >= ACCOUNT_SUBSCRIPTION_VERIFY) { 
            $url = murl('mc','','',true);
            $text = "恭喜你有新的积分入账,{$content}\n<a href='{$url}'>查看记录</a>";
            $message = array(
                'touser' => mc_uid2openid($uid),
                'msgtype' => 'text',
                'text' => array('content' => urlencode($text))
            );
            $account_api = WeAccount::create();
            $account_api->sendCustomNotice($message);
        }
    }

    protected function setwarn () {
        global $_W,$_GPC;
        $data = file_get_contents('php://input');
        $data = json_decode($data,true);
        $data['warntime'] = date('H:i',strtotime($data['warntime']));
        if(!$_W['member']['uid']){
            returnJson(1,'未登录');
        }
        pdo_update('hai_daka_member',$data,array('uid'=>$_W['member']['uid']));
        returnJson(0,'设置成功');
    }

    protected function getuserinfo () {
        global $_W,$_GPC;
        $data = pdo_get('hai_daka_member',array('uid'=>$_W['member']['uid']));
        returnJson(0,'success',$data);
    }

    /*
    *循环任务执行提醒
    */
    protected function cronwarn () {
        global $_W,$_GPC;
        if($this->config['iswarn']!=1) {
            exit('warn is closed');
        }
        $h = date('H');
        $time = date('H:i');
        if($h<5 || $h>=8) {
            exit('only 5-8'); 
        }
        $where = array(
            'iswarn' => 1,
            'uniacid' => $_W['uniacid'],
            'warndate !=' => date('Y-m-d'),
            'warntime <' => $time 
        );
        
        $memlist = pdo_getall('hai_daka_member',$where);
        pdo_update('hai_daka_member',array('warndate'=>date('Y-m-d')),$where);
        load()->func('communication'); 
        foreach($memlist as $v) {
            $account_api = WeAccount::create();
            $token = $account_api->getAccessToken();
            $fans = mc_fansinfo($v['uid']);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;
            $msg = array(
                'touser' => mc_uid2openid($v['uid']),
                'template_id' => $this->config['warntmpid'],
                'url' => $_W['siteroot'].$this->createMobileUrl('cover'),
                'data' => array(
                    'first' => array(
                        'value' => $fans['nickname'].'该起床啦',
                        'color:' => '#000'
                    ),
                    'keyword1' => array(
                        'value' => '早起打卡',
                        'color:' => '#000'
                    ),
                    'keyword2' => array(
                        'value' => $v['warntime'],
                        'color:' => '#000'
                    ),
                    'remark' => array(
                        'value' => '',
                        'color:' => ''
                    )
                )
            );
            ihttp_post($url, json_encode($msg));
        }
    }

    protected function  cronwarnfail() {
        global $_W,$_GPC;
        if(date('H')<8){
            return false;
        }
        if($this->config['iswarnfail']!=1) {
            return false;
        }
        if($this->config['warnfaildate']==date('Y-m-d')){
            exit('today done');
        }
        
        $where = array(
            'uniacid' => $_W['uniacid'],
            'status' => 1,
            'checked' => 0,
            'create_date' => date('Y-m-d',strtotime('-1 day')) 
        );
        $memlist = pdo_getall('hai_daka_yiyuan',$where);
        load()->func('communication'); 
        foreach($memlist as $v) {
            $account_api = WeAccount::create();
            $token = $account_api->getAccessToken();
            $fans = mc_fansinfo($v['uid']);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;
            $msg = array(
                'touser' => mc_uid2openid($v['uid']),
                'template_id' => $this->config['warntmpid'],
                'url' => $_W['siteroot'].$this->createMobileUrl('cover'),
                'data' => array(
                    'first' => array(
                        'value' => $fans['nickname'].',今天忘记起床了吗?',
                        'color:' => '#000'
                    ),
                    'keyword1' => array(
                        'value' => '早起打卡',
                        'color:' => '#000'
                    ),
                    'keyword2' => array(
                        'value' => '8:00',
                        'color:' => '#000'
                    ),
                    'remark' => array(
                        'value' => '点击继续挑战吧',
                        'color:' => ''
                    )
                )
            );
            ihttp_post($url, json_encode($msg));
        }
    }

    public function doWebAtm() {
        global $_W, $_GPC;
        
        if($_W['isajax']) {
            $action = $_GPC['action'];
            if($action=='atm') {
                $id = $_GPC['id'];
                $atm = pdo_get('hai_daka_atm',array('id'=>$id));
                if(!$atm) {
                    returnJson(1, '提现记录不存在');
                }
                if($atm['money']<1 || $atm['status']==1) {
                    returnJson(1, '金额错误或已经打款');
                }
                $pay = uni_setting_load('payment',$_W['uniacid']);
                $payment = $pay['payment'];
                $paycert = $payment['wechat_refund']['cert'];
                $paykey = $payment['wechat_refund']['key'];
                $paycert = authcode($paycert, 'DECODE');
                $paykey = authcode($paykey, 'DECODE');
                $certurl = ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem';
                file_put_contents($certurl, $paykey . $paycert);
                $res = $this->_pay($atm, $atm['money'], $payment, $certurl,true);
                unlink(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');
                if($res) {
                    $save = array(
                        'check_time' => date('Y-m-d H:i:s'),
                        'status' => 1
                    );
                    pdo_update('hai_daka_atm',$save,array('id'=>$id));
                    returnJson(0, '打款成功');
                }else{
                    returnJson(1, '打款失败');
                }
            }
        }
        
        $_W['page']['title'] = '提现申请';
        $size = 10;
        $page = max(1, $_GPC['page']);
        $where = array(
            'uniacid' => $_W['uniacid'],
        );
        if(isset($_GPC['status'])){
            $where['status'] = $_GPC['status'];
        }
        $list = pdo_getall('hai_daka_atm',$where,'','','id desc',($page - 1) * $size . ", {$size}");
        foreach ($list as $k => $v) {
            $list[$k]['fansinfo'] = mc_fansinfo($v['uid']);
        }
        $arr = pdo_get('hai_daka_atm',$where,array('count(*) as allcount'));
        $total = $arr['allcount'];
        $pager = pagination($total, $page, $size);
        include $this->template('atm');
    }

    protected function _getMyQrcode () {
        global $_W;
        
        //查看有没有没过期的二维码
        $where = array(
            'uniacid' => $_W['uniacid'],
            'expiretime >' => TIMESTAMP,
            'openid' => $this->member['openid']
        );
        $old = pdo_get('hai_daka_qrcode',$where);
        if($old) {
            return $old;
        }
        $barcode = array(
            'expire_seconds' => '',
            'action_name' => '',
            'action_info' => array(
                'scene' => array(),
            )
        );
        $acid = intval($_W['uniacid']);
        $qrcid = pdo_fetchcolumn("SELECT scene_id FROM ".tablename('hai_daka_qrcode')." WHERE uniacid = {$_W['uniacid']} AND type = 1 ORDER BY scene_id DESC LIMIT 1");
        $uniacccount = WeAccount::create($acid);
        $qrcid = !empty($qrcid) ? ($qrcid + 1) : 500001;
        $barcode['action_info']['scene']['scene_id'] = $qrcid;
        $barcode['expire_seconds'] = intval('2592000');
        $barcode['action_name'] = 'QR_SCENE';
        $result = $uniacccount->barCodeCreateDisposable($barcode);
        $insert = array(
            'uniacid' => $_W['uniacid'],
            'ticket' => $result['ticket'],
            'url' => $result['url'],
            'expire_seconds' => '2592000',
            'expiretime' => TIMESTAMP+2592000,
            'createtime' => TIMESTAMP,
            'openid' => $this->member['openid'],
            'scene_id' => $qrcid
        );
//        file_put_contents('y.txt', json_encode($insert));
        pdo_insert('hai_daka_qrcode', $insert);
        //$qrurl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($qr['ticket']);
        $id = pdo_insertid();
        $insert['id'] = $id;
        return $insert;
    }
  
    public function doWebChecked()
    {
    global $_W;
    if(isset($_REQUEST['checked']) && isset($_REQUEST['checked_time']))
    {
        $checked = $_REQUEST['checked'];
        $checked_time = $_REQUEST['checked_time'];

        $where = array(':create_date'=>$checked_time,':checked'=>$checked,':uniacid'=>$_W['uniacid'],':status'=>1);
        $list = pdo_fetchall("select * from ".tablename('hai_daka_yiyuan')." where "
            . "create_date=:create_date  AND uniacid=:uniacid AND checked=:checked and status=:status",
            $where);
        foreach ($list as $k=>$v) {
            $list[$k]['fansinfo'] = mc_fansinfo($v['uid']);
        }
        if($checked == 0)
        {
            $ret_checked = 1;
        }elseif($checked == 1)
        {
            $ret_checked = 0;
        }
        $response = array('retcode'=>200,'data'=>$list,'checked'=>$ret_checked);
    }else{
        $response = array('retcode'=>201);
    }
    echo json_encode($response);

    }
  
   public function doWebPiliang()
    {
        global $_W, $_GPC;
       
        $tixian_id = explode(',',$_REQUEST['tixian_id']);
    
        $action = $_GPC['do'];	

        $_W['page']['title'] = '提现申请';
        $size = 10;
        $page = max(1, $_GPC['page']);
        $where = array(
            'uniacid' => $_W['uniacid'],
        );
        $list = pdo_getall('hai_daka_atm',$where,'','','id desc',($page - 1) * $size . ", {$size}");
        foreach ($list as $k => $v) {
            $list[$k]['fansinfo'] = mc_fansinfo($v['uid']);
        }
        $arr = pdo_get('hai_daka_atm',$where,array('count(*) as allcount'));
        $total = $arr['allcount'];
        $pager = pagination($total, $page, $size);

        for($i=0;$i<count($tixian_id);$i++)
        {
            if($action=='piliang') {
                $id = $tixian_id[$i];
                $atm = pdo_get('hai_daka_atm',array('id'=>$id));
                if(!$atm) {
                    echo json_encode(array('code'=>801,'msg'=>'提现记录不存在'));
                    return;
                }
                if($atm['money']<1 || $atm['status']==1) {
                    echo json_encode(array('code'=>802,'msg'=>$id.'金额错误或已经打款'));
                    return;
                }
                $pay = uni_setting_load('payment',$_W['uniacid']);
                $payment = $pay['payment'];
                $paycert = $payment['wechat_refund']['cert'];
                $paykey = $payment['wechat_refund']['key'];
                $paycert = authcode($paycert, 'DECODE');
                $paykey = authcode($paykey, 'DECODE');
                $certurl = ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem';
                file_put_contents($certurl, $paykey . $paycert);
                $res = $this->_pay($atm, $atm['money'], $payment, $certurl,true);
                unlink(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');
                if($res) {
                    $save = array(
                        'check_time' => date('Y-m-d H:i:s'),
                        'status' => 1
                    );
                    pdo_update('hai_daka_atm',$save,array('id'=>$id));
                }else{
                    echo json_encode(array('code'=>803,'msg'=>'打款失败'));
                    return;
                }
            }
        }
        echo json_encode(array('code'=>800,'msg'=>'打款成功'));
    }
  

}
