<?php
class Hai105_dakaModuleCron extends WeModuleCron {
    public function doCronPay () {
        global $_W;
        load()->func('communication'); 
        //执行任务
        $url = murl('entry',array(
            'do' => 'api',
            'm' => $_W['cron']['module'],
            'action' => 'cron'
        ),'',true);
        ihttp_get($url);
    }
}
