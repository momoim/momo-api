-- phpMyAdmin SQL Dump
-- version 3.5.3
-- http://www.phpmyadmin.net
--
-- 主机: 10.1.191.146
-- 生成日期: 2013 年 11 月 01 日 13:57
-- 服务器版本: 5.5.25-log
-- PHP 版本: 5.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `momo_contact`
--
CREATE DATABASE IF NOT EXISTS `momo_contact`
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_general_ci;
USE `momo_contact`;

-- --------------------------------------------------------

--
-- 表的结构 `contact_addresses`
--

CREATE TABLE IF NOT EXISTS `contact_addresses` (
  `id`      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '地址ID',
  `uid`     BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`     BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`    VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `country` VARCHAR(255)     DEFAULT NULL
  COMMENT '值',
  `postal`  VARCHAR(20)      DEFAULT NULL,
  `region`  VARCHAR(255)     DEFAULT NULL,
  `city`    VARCHAR(255)     DEFAULT NULL,
  `street`  TEXT,
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='地址'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_addresses_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_addresses_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '地址ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `country`     VARCHAR(255)     DEFAULT NULL
  COMMENT '国家',
  `postal`      VARCHAR(20)      DEFAULT NULL
  COMMENT '邮编',
  `region`      VARCHAR(255)     DEFAULT NULL
  COMMENT '省',
  `city`        VARCHAR(255)     DEFAULT NULL
  COMMENT '市',
  `street`      TEXT COMMENT '街道',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='地址';

-- --------------------------------------------------------

--
-- 表的结构 `contact_addresses_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_addresses_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '地址ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `country`     VARCHAR(255)              DEFAULT NULL
  COMMENT '国家',
  `postal`      VARCHAR(20)               DEFAULT NULL
  COMMENT '邮编',
  `region`      VARCHAR(255)              DEFAULT NULL
  COMMENT '省',
  `city`        VARCHAR(255)              DEFAULT NULL
  COMMENT '市',
  `street`      TEXT COMMENT '街道',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='地址';


CREATE TABLE IF NOT EXISTS `contact_categories` (
  `id`            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `uid`           BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `category_name` VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '分组名',
  `order_by`      SMALLINT(6)      NOT NULL DEFAULT '0'
  COMMENT '分组顺序',
  PRIMARY KEY (`id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_categories_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_categories_snapshot` (
  `snapshot_id`   BIGINT(20)          NOT NULL
  COMMENT '快照ID',
  `id`            BIGINT(20) UNSIGNED NOT NULL DEFAULT '0'
  COMMENT '分组ID',
  `uid`           BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `category_name` VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '分组名',
  `order_by`      SMALLINT(6)      NOT NULL DEFAULT '0'
  COMMENT '分组顺序',
  KEY `IDX` (`uid`, `snapshot_id`, `id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contact_classes`
--

CREATE TABLE IF NOT EXISTS `contact_classes` (
  `category_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '分组ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '联系人ID',
  PRIMARY KEY (`uid`, `category_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contact_classes_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_classes_snapshot` (
  `snapshot_id` BIGINT(20) NOT NULL COMMENT '快照ID',
  `category_id` BIGINT(20) unsigned NOT NULL COMMENT '分组ID',
  `uid` BIGINT(20) unsigned NOT NULL COMMENT '用户ID',
  `cid` BIGINT(20) unsigned NOT NULL COMMENT '联系人ID',
  KEY `IDX` (`uid`,`snapshot_id`,`category_id`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- 表的结构 `contact_customs`
--

CREATE TABLE IF NOT EXISTS `contact_customs` (
  `id`    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid`   BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`   BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`  VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value` TEXT COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='自定义'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_customs_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_customs_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT 'ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value`       TEXT COMMENT '值',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='网址';

-- --------------------------------------------------------

--
-- 表的结构 `contact_customs_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_customs_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT 'ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       TEXT COMMENT '值',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='网址';

-- --------------------------------------------------------

--
-- 表的结构 `contact_emails`
--

CREATE TABLE IF NOT EXISTS `contact_emails` (
  `id`    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '邮箱ID',
  `uid`   BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`   BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`  VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value` VARCHAR(75)      DEFAULT NULL
  COMMENT '值(最多75个字符)',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='邮箱'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_emails_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_emails_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '邮箱ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)      DEFAULT NULL
  COMMENT '值(最多75个字符)',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='邮箱';

-- --------------------------------------------------------

--
-- 表的结构 `contact_emails_snapshot_124`
--

CREATE TABLE IF NOT EXISTS `contact_emails_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '邮箱ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='邮箱';

-- --------------------------------------------------------

--
-- 表的结构 `contact_events`
--

CREATE TABLE IF NOT EXISTS `contact_events` (
  `id`    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日期ID',
  `uid`   BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`   BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`  VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value` VARCHAR(75)      DEFAULT NULL
  COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='日期'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_events_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_events_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '日期ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)      DEFAULT NULL
  COMMENT '值',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='日期';

-- --------------------------------------------------------

--
-- 表的结构 `contact_events_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_events_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '日期ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='日期';

-- --------------------------------------------------------

--
-- 表的结构 `contact_history`
--

CREATE TABLE IF NOT EXISTS `contact_history` (
  `id`             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '联系人修改历史ID',
  `uid`            BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `dateline`       INT(10)                   DEFAULT NULL
  COMMENT '变更时间',
  `appid`          INT(10) UNSIGNED NOT NULL DEFAULT '0'
  COMMENT '应用ID',
  `source`         INT(10)                   DEFAULT NULL
  COMMENT '变更来源ID',
  `operation`      VARCHAR(50)               DEFAULT NULL
  COMMENT '变更说明',
  `added_ids`      TEXT COMMENT '新增联系人ID',
  `updated_ids`    TEXT COMMENT '修改联系人ID',
  `deleted_ids`    TEXT COMMENT '删除联系人ID',
  `count`          INT(10) UNSIGNED          DEFAULT NULL
  COMMENT '快照联系人数',
  `category_count` INT(10) UNSIGNED          DEFAULT NULL
  COMMENT '快照分组数',
  `device_id`      VARCHAR(200)              DEFAULT NULL
  COMMENT '设备ID',
  `phone_model`    VARCHAR(200)              DEFAULT NULL
  COMMENT '手机型号',
  PRIMARY KEY (`id`),
  KEY `IDX` (`uid`, `dateline`),
  KEY `IDX_DEVICE` (`uid`, `appid`, `device_id`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='联系人修改历史表'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_ims`
--

CREATE TABLE IF NOT EXISTS `contact_ims` (
  `id`       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '即时通讯ID',
  `uid`      BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`      BIGINT(20) UNSIGNED NOT NULL DEFAULT '0'
  COMMENT '联系人ID',
  `protocol` VARCHAR(50)      NOT NULL
  COMMENT '协议',
  `type`     VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`    VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='即时通讯'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_ims_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_ims_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '即时通讯ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED NOT NULL DEFAULT '0'
  COMMENT '联系人ID',
  `protocol`    VARCHAR(50)      NOT NULL
  COMMENT '协议',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='即时通讯';

-- --------------------------------------------------------

--
-- 表的结构 `contact_ims_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_ims_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '即时通讯ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED NOT NULL DEFAULT '0'
  COMMENT '联系人ID',
  `protocol`    VARCHAR(50)      NOT NULL
  COMMENT '协议',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='即时通讯';

-- --------------------------------------------------------

--
-- 表的结构 `contact_relations`
--

CREATE TABLE IF NOT EXISTS `contact_relations` (
  `id`    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '人员ID',
  `uid`   BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`   BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`  VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value` VARCHAR(75)      DEFAULT NULL
  COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='关系人'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_relations_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_relations_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '人员ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)      DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)      DEFAULT NULL
  COMMENT '值',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='关系人';

-- --------------------------------------------------------

--
-- 表的结构 `contact_relations_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_relations_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '人员ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='关系人';

-- --------------------------------------------------------

--
-- 表的结构 `contact_tels`
--

CREATE TABLE IF NOT EXISTS `contact_tels` (
  `id`     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '电话ID',
  `uid`    BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`    BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`   VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`  VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  `pref`   SMALLINT(6)      NOT NULL DEFAULT '0'
  COMMENT '是否主手机',
  `city`   VARCHAR(30)      NOT NULL DEFAULT ''
  COMMENT '归属地',
  `search` VARCHAR(15)      NOT NULL DEFAULT ''
  COMMENT '格式化手机号码',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_SEARCH` (`uid`, `search`),
  KEY `IDX_UID_CID` (`uid`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='电话'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contact_tels_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_tels_recycled` (
  `recycled_id` BIGINT(20) UNSIGNED NOT NULL
  COMMENT '回收站ID',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '电话ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  `pref`        SMALLINT(6)      NOT NULL DEFAULT '0'
  COMMENT '是否主手机',
  `city`        VARCHAR(30)      NOT NULL DEFAULT ''
  COMMENT '归属地',
  `search`      VARCHAR(14)      NOT NULL DEFAULT ''
  COMMENT '格式化手机号码',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='电话';

-- --------------------------------------------------------

--
-- 表的结构 `contact_tels_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_tels_snapshot` (
  `snapshot_id` BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `id`          BIGINT(20) UNSIGNED NOT NULL
  COMMENT '电话ID',
  `uid`         BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `cid`         BIGINT(20) UNSIGNED          DEFAULT NULL
  COMMENT '联系人ID',
  `type`        VARCHAR(50)               DEFAULT NULL
  COMMENT '类型',
  `value`       VARCHAR(75)               DEFAULT NULL
  COMMENT '值(最多75个字符)',
  `pref`        SMALLINT(6)      NOT NULL DEFAULT '0'
  COMMENT '是否主手机',
  `city`        VARCHAR(30)      NOT NULL DEFAULT ''
  COMMENT '归属地',
  `search`      VARCHAR(14)      NOT NULL DEFAULT ''
  COMMENT '格式化手机号码',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='电话';

-- --------------------------------------------------------

--
-- 表的结构 `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `cid`            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '编号',
  `uid`            BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `formatted_name` VARCHAR(75)      NOT NULL
  COMMENT '姓名',
  `phonetic`       VARCHAR(255)     NOT NULL
  COMMENT '名字拼音',
  `given_name`     VARCHAR(32)      NOT NULL
  COMMENT '名',
  `middle_name`    VARCHAR(32)      NOT NULL
  COMMENT '中间名',
  `family_name`    VARCHAR(32)      NOT NULL
  COMMENT '姓',
  `prefix`         VARCHAR(32)      NOT NULL
  COMMENT '前缀',
  `suffix`         VARCHAR(32)      NOT NULL
  COMMENT '后缀',
  `organization`   VARCHAR(255)              DEFAULT NULL
  COMMENT '公司',
  `department`     VARCHAR(255)              DEFAULT NULL
  COMMENT '部门',
  `note`           TEXT COMMENT '备注',
  `birthday`       VARCHAR(10)               DEFAULT NULL
  COMMENT '生日',
  `title`          VARCHAR(75)               DEFAULT NULL
  COMMENT '职称(最多75个字符)',
  `nickname`       VARCHAR(75)               DEFAULT NULL
  COMMENT '昵称(最多75个字符)',
  `sort`           VARCHAR(20)      NOT NULL
  COMMENT '姓名首字母',
  `created`        INT(11)                   DEFAULT '0'
  COMMENT '创建时间',
  `modified`       INT(11)                   DEFAULT '0'
  COMMENT '修改时间',
  `source`         VARCHAR(200)              DEFAULT ''
  COMMENT '数据来源',
  `avatar`         VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '头像',
  `category`       VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '分组名',
  PRIMARY KEY (`cid`),
  KEY `IDX` (`uid`, `formatted_name`, `phonetic`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='联系人'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contacts_recycled`
--

CREATE TABLE IF NOT EXISTS `contacts_recycled` (
  `recycled_id`    BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回收站ID',
  `cid`            BIGINT(20) UNSIGNED NOT NULL
  COMMENT '编号',
  `uid`            BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `formatted_name` VARCHAR(75)      NOT NULL
  COMMENT '姓名',
  `phonetic`       VARCHAR(255)     NOT NULL
  COMMENT '名字拼音',
  `given_name`     VARCHAR(32)      NOT NULL
  COMMENT '名',
  `middle_name`    VARCHAR(32)      NOT NULL
  COMMENT '中间名',
  `family_name`    VARCHAR(32)      NOT NULL
  COMMENT '姓',
  `prefix`         VARCHAR(32)      NOT NULL
  COMMENT '前缀',
  `suffix`         VARCHAR(32)      NOT NULL
  COMMENT '后缀',
  `organization`   VARCHAR(255)              DEFAULT NULL
  COMMENT '公司',
  `department`     VARCHAR(255)              DEFAULT NULL
  COMMENT '部门',
  `note`           TEXT COMMENT '备注',
  `birthday`       VARCHAR(10)               DEFAULT NULL
  COMMENT '生日',
  `title`          VARCHAR(75)               DEFAULT NULL
  COMMENT '职称(最多75个字符)',
  `nickname`       VARCHAR(75)               DEFAULT NULL
  COMMENT '昵称(最多75个字符)',
  `sort`           VARCHAR(20)      NOT NULL
  COMMENT '姓名首字母',
  `created`        INT(11)                   DEFAULT '0'
  COMMENT '创建时间',
  `modified`       INT(11)                   DEFAULT '0'
  COMMENT '修改时间',
  `source`         VARCHAR(200)     NOT NULL DEFAULT ''
  COMMENT '数据来源',
  `avatar`         VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '头像',
  `category`       VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '分组名',
  `operation`      VARCHAR(20)      NOT NULL DEFAULT 'delete'
  COMMENT '操作类型',
  PRIMARY KEY (`recycled_id`),
  KEY `IDX_UID_MOD` (`uid`, `modified`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='联系人'
  AUTO_INCREMENT =1;

-- --------------------------------------------------------

--
-- 表的结构 `contacts_snapshot`
--

CREATE TABLE IF NOT EXISTS `contacts_snapshot` (
  `snapshot_id`    BIGINT(20)          NOT NULL DEFAULT '0'
  COMMENT '快照ID（快照时间）',
  `cid`            BIGINT(20) UNSIGNED NOT NULL
  COMMENT '编号',
  `uid`            BIGINT(20) UNSIGNED NOT NULL
  COMMENT '用户ID',
  `formatted_name` VARCHAR(75)      NOT NULL
  COMMENT '姓名',
  `phonetic`       VARCHAR(255)     NOT NULL
  COMMENT '名字拼音',
  `given_name`     VARCHAR(32)      NOT NULL
  COMMENT '名',
  `middle_name`    VARCHAR(32)      NOT NULL
  COMMENT '中间名',
  `family_name`    VARCHAR(32)      NOT NULL
  COMMENT '姓',
  `prefix`         VARCHAR(32)      NOT NULL
  COMMENT '前缀',
  `suffix`         VARCHAR(32)      NOT NULL
  COMMENT '后缀',
  `organization`   VARCHAR(255)              DEFAULT NULL
  COMMENT '公司',
  `department`     VARCHAR(255)              DEFAULT NULL
  COMMENT '部门',
  `note`           TEXT COMMENT '备注',
  `birthday`       VARCHAR(10)               DEFAULT NULL
  COMMENT '生日',
  `title`          VARCHAR(75)               DEFAULT NULL
  COMMENT '职称(最多75个字符)',
  `nickname`       VARCHAR(75)               DEFAULT NULL
  COMMENT '昵称(最多75个字符)',
  `sort`           VARCHAR(20)      NOT NULL
  COMMENT '姓名首字母',
  `created`        INT(11)                   DEFAULT '0'
  COMMENT '创建时间',
  `modified`       INT(11)                   DEFAULT '0'
  COMMENT '修改时间',
  `source`         VARCHAR(200)     NOT NULL DEFAULT ''
  COMMENT '数据来源',
  `avatar`         VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '头像',
  `category`       VARCHAR(255)     NOT NULL DEFAULT ''
  COMMENT '分组名',
  KEY `IDX_UID_SID_CID` (`uid`, `snapshot_id`, `cid`)
)
  ENGINE =InnoDB
  DEFAULT CHARSET =utf8
  COMMENT ='联系人';

-- --------------------------------------------------------

--
-- 表的结构 `contact_urls`
--

CREATE TABLE IF NOT EXISTS `contact_urls` (
  `id` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` BIGINT(20) unsigned NOT NULL COMMENT '用户ID',
  `cid` BIGINT(20) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_CID` (`uid`,`cid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='网址' AUTO_INCREMENT=2572911 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_urls_recycled`
--

CREATE TABLE IF NOT EXISTS `contact_urls_recycled` (
  `recycled_id` BIGINT(20) unsigned NOT NULL COMMENT '回收站ID',
  `id` BIGINT(20) unsigned NOT NULL COMMENT 'ID',
  `uid` BIGINT(20) unsigned NOT NULL COMMENT '用户ID',
  `cid` BIGINT(20) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  KEY `IDX` (`recycled_id`),
  KEY `UID` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网址';

-- --------------------------------------------------------

--
-- 表的结构 `contact_urls_snapshot`
--

CREATE TABLE IF NOT EXISTS `contact_urls_snapshot` (
  `snapshot_id` BIGINT(20) NOT NULL DEFAULT '0' COMMENT '快照ID（快照时间）',
  `id` BIGINT(20) unsigned NOT NULL COMMENT 'ID',
  `uid` BIGINT(20) unsigned NOT NULL COMMENT '用户ID',
  `cid` BIGINT(20) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  KEY `IDX_UID_SID_CID` (`uid`,`snapshot_id`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='网址';

-- --------------------------------------------------------

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
