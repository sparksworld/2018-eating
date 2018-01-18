<?php

$d1 = "DROP TABLE IF EXISTS `". tablename('hai_daka_member')."`;";
$d2 = "DROP TABLE IF EXISTS `". tablename('hai_daka_config')."`;";
$d3 = "DROP TABLE IF EXISTS `". tablename('hai_daka_yiyuan')."`;";
pdo_query($d1);
pdo_query($d2);
pdo_query($d3);