<?php
$createYiyuan = "CREATE TABLE IF NOT EXISTS `".tablename('hai_daka_yiyuan')."` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `create_date` date DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `status` int(1) DEFAULT '0',
  `pay_result` text,
  `checked` int(1) DEFAULT '0' COMMENT '签到',
  `checked_time` datetime DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00',
  `money_time` datetime DEFAULT NULL,
  `uniacid` int(11) NOT NULL COMMENT '公众号ID',
  `order_sn` varchar(30) DEFAULT NULL COMMENT '订单编号',
  `pay_money` decimal(11,2) DEFAULT '1.00',
   `money_type` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$createMember = "CREATE TABLE IF NOT EXISTS `".tablename('hai_daka_member')."` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(50) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `uniacid` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `lianxu` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$createConfig = "CREATE TABLE IF NOT EXISTS `".tablename('hai_daka_config')."` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) DEFAULT NULL,
  `key` varchar(20) DEFAULT NULL,
  `value` blob,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
pdo_query($createYiyuan);
pdo_query($createMember);
pdo_query($createConfig);
