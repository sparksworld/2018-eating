<?php
$s1 = "CREATE TABLE IF NOT EXISTS `ims_hai_daka_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT NULL,
  `key` varchar(20) DEFAULT NULL,
  `value` blob,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$s2 = "ALTER TABLE ". tablename('hai_daka_yiyuan')." ADD pay_money DECIMAL(11,2) DEFAULT '1';";
$s3 = "ALTER TABLE ". tablename('hai_daka_yiyuan')." ADD money_type int(11) DEFAULT '1';";
pdo_query($s1);
if(!pdo_fieldexists('hai_daka_yiyuan', 'pay_money')) {
    pdo_query($s2);
}
if(!pdo_fieldexists('hai_daka_yiyuan', 'money_type')) {
    pdo_query($s3);
}
    
