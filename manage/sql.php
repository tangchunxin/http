<?php
exit();

-- phpMyAdmin SQL Dump
-- version 4.6.2
-- https://www.phpmyadmin.net/
--
-- Host: 100.91.230.51:6504
-- Generation Time: 2017-08-31 10:30:50
-- 服务器版本： 5.6.28-cdb20160902-log
-- PHP Version: 5.6.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test_game_mahjong_hb`
--


-- --------------------------------------------------------
--
-- 表的结构 `game_table_log`
--


CREATE TABLE  `game_table_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增长id',
  `rid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间号',
  `uid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房主 uid',
  `game_table_info` varchar(8192) NOT NULL DEFAULT '' COMMENT '每桌总分',
  `time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '记录时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=729 DEFAULT CHARSET=utf8 COMMENT='游戏table_log记录';

--
-- 表的结构 `game_log`
--


CREATE TABLE  `game_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增长id',
  `rid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间号',
  `uid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房主 uid',
  `game_info` varchar(8192) NOT NULL DEFAULT '' COMMENT '游戏信息',
  `type` tinyint(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1 一局记录 2 一房间记录',
  `time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '记录时间',
  `save` varchar(64) NOT NULL DEFAULT '',
  `game_type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `play_time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rid_type` (`rid`,`type`) USING BTREE,
  KEY `time` (`time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='游戏log记录';

-- --------------------------------------------------------

--
-- 表的结构 `game_log_user`
--


CREATE TABLE  `game_log_user` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增长id',
  `game_log_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间号',
  `uid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户 id',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`) USING BTREE,
  KEY `idx_game_log_user_game_log_id` (`game_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='游戏log记录id 和用户uid对应表';

-- --------------------------------------------------------

--
-- 表的结构 `kpi`
--

CREATE TABLE  `kpi` (
  `id_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '日期时间，每天零点，每天一条',
  `all_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总用户数',
  `new_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '新注册用户',
  `active_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活跃用户',
  `game_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每天局数',
  `hour_user` varchar(256) NOT NULL DEFAULT '0' COMMENT '按时段统计用户在线量',
  `currency` int(11) NOT NULL DEFAULT '0' COMMENT '每天消费',
  `play_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_time`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统计数据业绩'
-- --------------------------------------------------------

--
-- 表的结构 `kpi_new`
--


CREATE TABLE `kpi_new` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT  COMMENT '主键自增ID',
  `id_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '日期时间，每天零点，每天一条',
  `all_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总用户数',
  `new_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '新注册用户',
  `active_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活跃用户',
  `game_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '每天局数',
  `hour_user` varchar(256) NOT NULL DEFAULT '0' COMMENT '按时段统计用户在线量',
  `recharge_direct` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '直属用户当日充值总额',
  `recharge_subordinate` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '下属用户当日充值金额',
  `currency_direct` int(11) NOT NULL DEFAULT '0' COMMENT '每天消费',
  `currency_subordinate` int(10) NOT NULL DEFAULT '0',
  `play_time` int(10) unsigned NOT NULL DEFAULT '0',
  `agent_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '推广员id',
  `pay_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '提现状态 0 未提现 1已提现',
  `recharge_direct_shared` float(5,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '直属用户充值分成占比',
  `recharge_subordinate_shared` float(5,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '下属用户充值分成占比'
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统计数据业绩';

-- --------------------------------------------------------

--
-- 表的结构 `room`
--

CREATE TABLE  `room` (
  `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'room id',
  `state` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'state 0 未定义 1 空闲 2 开放  3 正在游戏',
  PRIMARY KEY (`rid`),
  KEY `state` (`state`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='房间信息';

-- --------------------------------------------------------

--
-- 表的结构 `uid`
--

CREATE TABLE  `uid` (
  `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'room id',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='生成自增长uid';

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE  `user` (
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户id',
  `key` char(8) NOT NULL DEFAULT '' COMMENT '登录key',
  `wx_openid` char(64) NOT NULL COMMENT '微信openID',
  `wx_pic` varchar(256) NOT NULL DEFAULT '' COMMENT '用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。',
  `name` char(32) NOT NULL DEFAULT '' COMMENT '用户名字',
  `sex` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户的性别，值为1时是男性，值为2时是女性，值为0时是未知',
  `province` char(32) NOT NULL DEFAULT '' COMMENT '省',
  `city` char(32) NOT NULL DEFAULT '' COMMENT '市',
  `init_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `login_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `real_name_reg` varchar(64) NOT NULL COMMENT '实名制登记',
  `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0正常  1黑名单',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `wx_openid` (`wx_openid`) USING BTREE,
  KEY `init_time` (`init_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

-- --------------------------------------------------------

--
-- 表的结构 `user_game`
--

CREATE TABLE `user_game` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `currency` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '游戏币',
  `room` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '未结束的游戏房间号',
  `is_room_owner` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否房主 0 否 1 是',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `last_game_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次玩游戏的时间',
  `currency2` int(10) unsigned NOT NULL DEFAULT '0',
  `agent_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '推广员id',
  `sum_money` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '总计充值金额',
  `sum_currency` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '总计消耗房卡',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0 正常  1黑名单',
  `bind_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '绑定工会时间',
  `score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `cup` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖杯',
  `reward_state` char(100) NOT NULL DEFAULT '''''' COMMENT '满足的奖励状况',
  `inviter` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人uid',
  `vip_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '101vip日卡  102vip周卡  103vip月卡  104vip季卡  105vip半年卡  106vip年卡',
  `vip_overtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间',
  PRIMARY KEY (`uid`),
  KEY `last_game_time` (`last_game_time`),
  KEY `room` (`room`),
  KEY `update_time` (`agent_id`,`update_time`) USING BTREE
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户游戏信息'

-- --------------------------------------------------------

--
-- 表的结构 `user_log`
--

CREATE TABLE  `user_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `old_currency` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户以前的货币值',
  `currency` int(11) NOT NULL DEFAULT '0' COMMENT '游戏币变化值 有正负',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1 开房间消费 2 代理充值 3分享充值 4微信充值 5积分换蓝钻 21红钻消耗(红钻兑换积分) 22 红钻充值 31游戏积分增减 32 开房积分赠送 33 积分换礼物 34 积分增加(红钻兑换积分) 41 游戏赠送奖杯 42 奖杯换礼物 51 邀请好友绑定公会 52 邀请好友第一次充值 53 邀请好友完成十桌游戏 61 首次关注微信公众号',
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '记录时间',
  `money` float(11,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `aid` char(13) NOT NULL DEFAULT '''''' COMMENT '代理id'
  PRIMARY KEY (`id`),
  KEY `time_type` (`time`,`type`) USING BTREE,
  KEY `uid_type_time` (`type`,`uid`,`time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户游戏币变动记录';


-- --------------------------------------------------------

--
-- 表的结构 `gift_exchange_log`
--

CREATE TABLE `gift_exchange_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '自增主键ID',
  `name` char(50) NOT NULL DEFAULT '' COMMENT '礼物名称',
  `picture` varchar(512) NOT NULL DEFAULT '' COMMENT '礼物图片',
  `uid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '兑换人',
  `time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '兑换时间',
  `receiver_name` char(32) NOT NULL DEFAULT '' COMMENT '收货人姓名',
  `receiver_cellphone` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '收件人手机号',
  `receiver_address` varchar(512) NOT NULL DEFAULT '' COMMENT '收件人地址',
  `remark` varchar(512) NOT NULL DEFAULT '' COMMENT '备注',
  `state` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '发货状态(1,待处理,2,处理中,3已完成)',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更改时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='礼物兑换记录表';


-- --------------------------------------------------------

--
-- 表的结构 `reward_message`
--

DROP TABLE IF EXISTS `reward_message`;
CREATE TABLE `reward_message` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '玩家id(邀请人)',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '奖励类型（1邀请的人第一次登录，2邀请的人第一次充值 3 邀请的人打够十桌  11 我第一次登录，邀请我的人获取奖励 12 ，我第一次充值 13 我打够十桌 21 新用户奖励钻石）',
  `state` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '领取状态(0未领取;1,已领取可删除2,不显示)',
  `invitee` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '被邀请人',
  `invitee_name` char(32) NOT NULL DEFAULT '''''' COMMENT '被邀请人姓名',
  `currency` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '奖励钻石数',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `uid_state` (`uid`,`state`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='消息表';

-- --------------------------------------------------------
--
-- 表的结构 `user_active`
--

CREATE TABLE `user_active` (
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '玩家id',
  `subscribe_reward` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否领取关注公众号奖励（0，未领取 1已领取）',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='玩家活动数据记录表';


--
-- Indexes for table `kpi`
--
ALTER TABLE `kpi`
  ADD PRIMARY KEY (`id_time`);

--
-- Indexes for table `kpi_new`
--
ALTER TABLE `kpi_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- 使用表AUTO_INCREMENT `kpi_new`
--
ALTER TABLE `kpi_new`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键自增ID', AUTO_INCREMENT=1;


ALTER TABLE `user_game` 
  ADD `bind_time` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '绑定工会时间' AFTER `status`;

ALTER TABLE `user_game` ADD `score` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '积分' AFTER `bind_time`, ADD `cup` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '奖杯' AFTER `score`;

ALTER TABLE `user_log` CHANGE `type` `type` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1 开房间消费 2 代理充值 3分享充值 4微信充值 5积分换蓝钻 21红钻消耗(红钻兑换积分) 22 红钻充值 31游戏积分增减 32 开房积分赠送 33 积分换礼物 34 积分增加(红钻兑换积分) 41 游戏赠送奖杯 42 奖杯换礼物 51 邀请好友绑定公会 52 邀请好友第一次充值 53 邀请好友完成十桌游戏 61 首次关注微信公众号';

ALTER TABLE `user_log` DROP INDEX `type_uid_time`, ADD INDEX `type_uid_time` (`type`, `uid`, `time`) USING BTREE;
ALTER TABLE `user_log` ADD INDEX `uid_type_time` (`uid`, `type`, `time`);

ALTER TABLE `game_table_log` CHANGE `uid` `uid` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房主 uid';
ALTER TABLE `game_table_log` ADD `state` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'log状态' AFTER `time`;

ALTER TABLE `kpi_new` ADD `recharge_under_subordinate` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '下下级充值' AFTER `recharge_subordinate_shared`, ADD `recharge_under_subordinate_shared` FLOAT(5,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '下下级比例' AFTER `recharge_under_subordinate`;

ALTER TABLE `user_game` ADD `reward_state` CHAR(100) NOT NULL DEFAULT '\'\'' COMMENT '满足的奖励状况' AFTER `cup`, ADD `inviter` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '邀请人uid' AFTER `reward_state`;


ALTER TABLE `user_game` ADD `vip_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1日卡  2周卡  3月卡 4季卡 5半年卡 6年卡' AFTER `inviter`, ADD `vip_overtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'vip到期时间' AFTER `vip_type`;
