/*
Navicat MySQL Data Transfer

Source Server         : 101.200.91.203
Source Server Version : 50169
Source Host           : 101.200.91.203:3306
Source Database       : aso_db

Target Server Type    : MYSQL
Target Server Version : 50169
File Encoding         : 65001

Date: 2018-05-08 23:56:10
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for aso_advert
-- ----------------------------
DROP TABLE IF EXISTS `aso_advert`;
CREATE TABLE `aso_advert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(255) DEFAULT NULL COMMENT '应用名',
  `cpid` int(50) NOT NULL COMMENT '合作方ID',
  `charge` varchar(100) NOT NULL COMMENT '负责人',
  `price` char(10) DEFAULT NULL COMMENT '单价',
  `appid` int(50) DEFAULT NULL,
  `channel` varchar(10) DEFAULT NULL,
  `salesman` varchar(10) DEFAULT NULL,
  `IdfaRepeat_url` varchar(255) DEFAULT NULL COMMENT '排重url',
  `submit_url` varchar(255) DEFAULT NULL COMMENT '上报接口url',
  `source_url` varchar(255) DEFAULT NULL COMMENT '点击请求链接',
  `create_time` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `source_value` varchar(50) DEFAULT '' COMMENT '是否点击',
  `repeat_value` varchar(50) DEFAULT '' COMMENT '是否上报',
  `submit_value` varchar(50) DEFAULT '' COMMENT '是否排重',
  `is_advert` tinyint(2) unsigned DEFAULT '0' COMMENT '是否回调 1 是 0否',
  `is_disable` enum('0','1') DEFAULT '0' COMMENT '是否禁用接口 0否  1是',
  `key` varchar(50) DEFAULT NULL COMMENT '接口key',
  `api_cat` tinyint(2) unsigned DEFAULT '1' COMMENT '接口类型 0常规接口 1特殊接口',
  `is_repeat` tinyint(2) unsigned DEFAULT '0' COMMENT '是否排重  1 是  0 否',
  `is_source` tinyint(2) unsigned DEFAULT '0' COMMENT '是否点击 1 是 0 否',
  `is_submit` tinyint(2) unsigned DEFAULT '0' COMMENT '是否上报  1 是  0 否',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2191 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_advert_log
-- ----------------------------
DROP TABLE IF EXISTS `aso_advert_log`;
CREATE TABLE `aso_advert_log` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `cpid` int(50) NOT NULL COMMENT '渠道商的cpid',
  `appid` int(50) NOT NULL COMMENT '广告主推广的app标识一般是Appstore ID',
  `idfa` char(36) NOT NULL COMMENT '用户的idfa,格式：字母为⼤写字⺟，包含分隔符号-',
  `ip` varchar(255) DEFAULT NULL COMMENT '当前用户ip地址',
  `timestamp` int(10) DEFAULT NULL COMMENT '请求时间戳，以秒为单位',
  `error` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`) USING BTREE,
  KEY `appid` (`appid`) USING BTREE,
  KEY `cpid` (`ip`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=36923663 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_bundle
-- ----------------------------
DROP TABLE IF EXISTS `aso_bundle`;
CREATE TABLE `aso_bundle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle` varchar(60) DEFAULT NULL,
  `wait` tinyint(10) DEFAULT NULL,
  `status` enum('1','0') DEFAULT '1' COMMENT '开关 1开 0关',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_channel
-- ----------------------------
DROP TABLE IF EXISTS `aso_channel`;
CREATE TABLE `aso_channel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '应用名',
  `cpid` int(50) NOT NULL COMMENT '合作方ID（为了兼容之前代码）',
  `aso_cpid` int(50) DEFAULT NULL,
  `charge` varchar(100) NOT NULL COMMENT '渠道负责人',
  `ad_param` varchar(255) DEFAULT NULL COMMENT '对应渠道的广告',
  `IdfaRepeat_status` tinyint(1) DEFAULT '0',
  `IdfaRepeat_url` varchar(255) DEFAULT NULL COMMENT '排重url',
  `submit_status` tinyint(1) DEFAULT '0',
  `submit_url` varchar(255) DEFAULT NULL COMMENT '上报接口url',
  `source_status` tinyint(1) DEFAULT '0',
  `source_url` varchar(255) DEFAULT NULL COMMENT '点击请求链接',
  `advert_status` tinyint(1) DEFAULT '0',
  `advert_url` varchar(255) DEFAULT NULL COMMENT '回调',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_cpid` (`cpid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_gnh_submit
-- ----------------------------
DROP TABLE IF EXISTS `aso_gnh_submit`;
CREATE TABLE `aso_gnh_submit` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `cpid` int(10) NOT NULL,
  `appid` int(20) NOT NULL,
  `idfa` char(36) NOT NULL,
  `keywords` varchar(100) DEFAULT NULL COMMENT '关键词',
  `timestamp` int(10) NOT NULL,
  `type` int(4) DEFAULT NULL COMMENT '接收值  1  拒收值 0',
  `is_mobile` tinyint(2) unsigned DEFAULT NULL COMMENT '是否是移动端数据  1 是 0否',
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`),
  KEY `appid` (`appid`),
  KEY `cpid` (`cpid`),
  KEY `is_mobile` (`is_mobile`)
) ENGINE=MyISAM AUTO_INCREMENT=19342520 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- ----------------------------
-- Table structure for aso_IdfaRepeat
-- ----------------------------
DROP TABLE IF EXISTS `aso_IdfaRepeat`;
CREATE TABLE `aso_IdfaRepeat` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cpid` int(4) NOT NULL,
  `adid` int(10) DEFAULT '0',
  `appid` int(16) NOT NULL,
  `idfa` char(36) NOT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `json` varchar(255) NOT NULL COMMENT '排重返回值',
  `date` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `appid_idfa_indx` (`appid`,`idfa`) USING BTREE,
  KEY `appid_ip_indx` (`appid`,`ip`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=148839 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_IdfaRepeat_log
-- ----------------------------
DROP TABLE IF EXISTS `aso_IdfaRepeat_log`;
CREATE TABLE `aso_IdfaRepeat_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cpid` int(4) NOT NULL,
  `adid` int(10) DEFAULT '0',
  `appid` int(16) NOT NULL,
  `idfa` char(36) NOT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `json` varchar(255) NOT NULL COMMENT '排重返回值',
  `date` int(10) NOT NULL,
  `is_mobile` tinyint(2) unsigned DEFAULT '1' COMMENT '是否是移动端数据 1是 0 否',
  PRIMARY KEY (`id`),
  KEY `appid_idfa_indx` (`appid`,`idfa`) USING BTREE,
  KEY `appid_ip_indx` (`appid`,`ip`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=115951359 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_sales
-- ----------------------------
DROP TABLE IF EXISTS `aso_sales`;
CREATE TABLE `aso_sales` (
  `sales_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '销售id',
  `sales_name` varchar(50) DEFAULT NULL COMMENT '销售名字',
  `create_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`sales_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_source
-- ----------------------------
DROP TABLE IF EXISTS `aso_source`;
CREATE TABLE `aso_source` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `cpid` int(50) NOT NULL COMMENT '渠道商的cpid',
  `appid` int(50) NOT NULL COMMENT '广告主推广的app标识一般是Appstore ID',
  `idfa` char(36) NOT NULL COMMENT '用户的idfa,格式：字母为⼤写字⺟，包含分隔符号-',
  `ip` varchar(255) DEFAULT NULL COMMENT '当前用户ip地址',
  `timestamp` int(10) DEFAULT NULL COMMENT '请求时间戳，以秒为单位',
  `sign` varchar(255) DEFAULT NULL COMMENT '请求参数的MD5签名',
  `session_id` varchar(255) DEFAULT NULL COMMENT '点击返回的data',
  `type` int(1) DEFAULT NULL COMMENT '是否做任务 1 为是 ',
  `reqtype` int(4) unsigned zerofill DEFAULT NULL COMMENT '0表示点击下载一个应用',
  `device` varchar(255) DEFAULT NULL COMMENT '设备类型',
  `os` varchar(255) DEFAULT NULL COMMENT '操作系统版本号',
  `isbreak` varchar(255) DEFAULT NULL COMMENT '是否越狱 0没有越狱，1越狱了',
  `callback` varchar(755) DEFAULT NULL COMMENT '回调接口，完整的url,URLEncoder过的',
  `backtype` int(4) DEFAULT NULL COMMENT '回调地址结果 1为成功 ',
  `submit` int(4) DEFAULT NULL COMMENT '上报状态',
  `adid` int(4) unsigned DEFAULT NULL COMMENT '由我方提供的广告id',
  `keywords` varchar(100) DEFAULT NULL,
  `is_mobile` tinyint(2) unsigned DEFAULT '1' COMMENT '是否是移动端数据 1 是 0否',
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`) USING BTREE,
  KEY `appid` (`appid`) USING BTREE,
  KEY `cpid` (`cpid`) USING BTREE,
  KEY `adid` (`adid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=45181692 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_source_adv
-- ----------------------------
DROP TABLE IF EXISTS `aso_source_adv`;
CREATE TABLE `aso_source_adv` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `cpid` int(50) NOT NULL COMMENT '渠道商的cpid',
  `appid` int(50) NOT NULL COMMENT '广告主推广的app标识一般是Appstore ID',
  `idfa` char(36) NOT NULL COMMENT '用户的idfa,格式：字母为⼤写字⺟，包含分隔符号-',
  `ip` varchar(255) DEFAULT NULL COMMENT '当前用户ip地址',
  `timestamp` int(10) DEFAULT NULL COMMENT '请求时间戳，以秒为单位',
  `sign` varchar(255) DEFAULT NULL COMMENT '请求参数的MD5签名',
  `type` int(1) DEFAULT NULL COMMENT '是否做任务 1 为是 ',
  `reqtype` int(4) DEFAULT NULL COMMENT '0表示点击下载一个应用',
  `device` varchar(255) DEFAULT NULL COMMENT '设备类型',
  `os` varchar(255) DEFAULT NULL COMMENT '操作系统版本号',
  `isbreak` varchar(255) DEFAULT NULL COMMENT '是否越狱 0没有越狱，1越狱了',
  `callback` varchar(755) DEFAULT NULL COMMENT '回调接口，完整的url,URLEncoder过的',
  `backtype` int(4) DEFAULT NULL COMMENT '回调地址结果 1为成功 ',
  `submit` int(4) DEFAULT NULL COMMENT '上报状态',
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`) USING BTREE,
  KEY `appid` (`appid`) USING BTREE,
  KEY `cpid` (`cpid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_source_cpid
-- ----------------------------
DROP TABLE IF EXISTS `aso_source_cpid`;
CREATE TABLE `aso_source_cpid` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cpid` int(5) NOT NULL COMMENT 'cpid ',
  `name` varchar(255) NOT NULL COMMENT '渠道名称',
  `key` varchar(255) NOT NULL COMMENT '通信密钥',
  `note` varchar(255) DEFAULT NULL COMMENT '备注',
  `ip` varchar(255) DEFAULT NULL COMMENT 'IP白名单',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpid` (`cpid`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=90 DEFAULT CHARSET=utf8 COMMENT='合作渠道';

-- ----------------------------
-- Table structure for aso_source_cpid_copy
-- ----------------------------
DROP TABLE IF EXISTS `aso_source_cpid_copy`;
CREATE TABLE `aso_source_cpid_copy` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cpid` int(5) NOT NULL COMMENT '合作渠道cpid  我们自己定义',
  `name` varchar(255) NOT NULL COMMENT '合作渠道名称',
  `key` varchar(255) NOT NULL COMMENT '与渠道商通信密钥',
  `note` varchar(255) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_source_log
-- ----------------------------
DROP TABLE IF EXISTS `aso_source_log`;
CREATE TABLE `aso_source_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cpid` int(10) NOT NULL,
  `appid` int(16) NOT NULL,
  `idfa` char(36) NOT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `timestamp` int(10) NOT NULL,
  `reqtype` int(4) DEFAULT NULL COMMENT '0表示点击下载一个应用',
  `keywords` varchar(50) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL COMMENT '设备类型',
  `os` varchar(255) DEFAULT NULL COMMENT '操作系统版本号',
  `isbreak` varchar(255) DEFAULT NULL COMMENT '是否越狱 0没有越狱，1越狱了',
  `callback` varchar(755) DEFAULT NULL,
  `json` varchar(255) NOT NULL,
  `sign` varchar(255) DEFAULT NULL,
  `adid` tinyint(4) unsigned DEFAULT NULL,
  `is_mobile` tinyint(2) unsigned DEFAULT '1' COMMENT '是否是移动端数据 1 是 0 否',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2533085 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_source_myc
-- ----------------------------
DROP TABLE IF EXISTS `aso_source_myc`;
CREATE TABLE `aso_source_myc` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `cpid` int(50) NOT NULL COMMENT '渠道商的cpid',
  `appid` int(50) NOT NULL COMMENT '广告主推广的app标识一般是Appstore ID',
  `idfa` char(36) NOT NULL COMMENT '用户的idfa,格式：字母为⼤写字⺟，包含分隔符号-',
  `ip` varchar(255) DEFAULT NULL COMMENT '当前用户ip地址',
  `timestamp` int(10) DEFAULT NULL COMMENT '请求时间戳，以秒为单位',
  `sign` varchar(255) DEFAULT NULL COMMENT '请求参数的MD5签名',
  `session_id` varchar(255) DEFAULT NULL COMMENT '点击返回的data',
  `type` int(1) DEFAULT NULL COMMENT '是否做任务 1 为是 ',
  `reqtype` int(4) unsigned zerofill DEFAULT NULL COMMENT '0表示点击下载一个应用',
  `device` varchar(255) DEFAULT NULL COMMENT '设备类型',
  `os` varchar(255) DEFAULT NULL COMMENT '操作系统版本号',
  `isbreak` varchar(255) DEFAULT NULL COMMENT '是否越狱 0没有越狱，1越狱了',
  `callback` varchar(755) DEFAULT NULL COMMENT '回调接口，完整的url,URLEncoder过的',
  `backtype` int(4) DEFAULT NULL COMMENT '回调地址结果 1为成功 ',
  `submit` int(4) DEFAULT NULL COMMENT '上报状态',
  `adid` int(4) unsigned DEFAULT NULL COMMENT '由我方提供的广告id',
  `keywords` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`) USING BTREE,
  KEY `appid` (`appid`) USING BTREE,
  KEY `cpid` (`cpid`) USING BTREE,
  KEY `adid` (`adid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=38260725 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_submit
-- ----------------------------
DROP TABLE IF EXISTS `aso_submit`;
CREATE TABLE `aso_submit` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `cpid` int(10) NOT NULL,
  `appid` int(20) NOT NULL,
  `idfa` char(36) NOT NULL,
  `keywords` varchar(100) DEFAULT NULL,
  `timestamp` int(10) NOT NULL,
  `type` int(4) DEFAULT NULL COMMENT '上报值为1，回调值为2',
  `is_mobile` tinyint(2) unsigned DEFAULT NULL COMMENT '是否是移动端 1是 0 否',
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`),
  KEY `appid` (`appid`),
  KEY `cpid` (`cpid`)
) ENGINE=MyISAM AUTO_INCREMENT=8232071 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_submit_log
-- ----------------------------
DROP TABLE IF EXISTS `aso_submit_log`;
CREATE TABLE `aso_submit_log` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `cpid` int(10) NOT NULL,
  `appid` int(20) NOT NULL,
  `idfa` char(36) NOT NULL,
  `keywords` varchar(100) DEFAULT NULL,
  `timestamp` int(10) NOT NULL,
  `type` int(4) DEFAULT NULL COMMENT '上报值为1，回调值为2',
  `json` varchar(255) DEFAULT NULL,
  `is_mobile` tinyint(2) unsigned DEFAULT NULL COMMENT '是否是移动端 1 是 0 否',
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`),
  KEY `appid` (`appid`),
  KEY `cpid` (`cpid`)
) ENGINE=MyISAM AUTO_INCREMENT=12706711 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_submit2
-- ----------------------------
DROP TABLE IF EXISTS `aso_submit2`;
CREATE TABLE `aso_submit2` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `cpid` int(10) NOT NULL,
  `appid` int(20) NOT NULL,
  `idfa` char(36) NOT NULL,
  `keywords` varchar(100) DEFAULT NULL COMMENT '关键词',
  `timestamp` int(10) NOT NULL,
  `type` int(4) DEFAULT NULL COMMENT '上报值为1，回调值为2',
  `is_mobile` tinyint(2) unsigned DEFAULT NULL COMMENT '是否是移动端数据  1 是 0否',
  PRIMARY KEY (`id`),
  KEY `idfa` (`idfa`),
  KEY `appid` (`appid`),
  KEY `cpid` (`cpid`),
  KEY `is_mobile` (`is_mobile`)
) ENGINE=MyISAM AUTO_INCREMENT=20328377 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- ----------------------------
-- Table structure for aso_table1
-- ----------------------------
DROP TABLE IF EXISTS `aso_table1`;
CREATE TABLE `aso_table1` (
  `idfa` varchar(50) NOT NULL,
  `appid` int(11) DEFAULT NULL,
  `start_time` varchar(100) DEFAULT NULL,
  `end_time` varchar(100) DEFAULT NULL,
  KEY `idfa` (`appid`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for aso_timeout_log
-- ----------------------------
DROP TABLE IF EXISTS `aso_timeout_log`;
CREATE TABLE `aso_timeout_log` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL COMMENT '超时url',
  `message` varchar(50) DEFAULT NULL COMMENT '错误信息',
  `date` varchar(50) DEFAULT NULL COMMENT '超时时间',
  PRIMARY KEY (`id`),
  KEY `url` (`url`) USING BTREE,
  KEY `message` (`message`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=3058908 DEFAULT CHARSET=utf8 COMMENT='aso_submit测试表 刘超';
