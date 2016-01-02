-- phpMyAdmin SQL Dump
-- version 3.5.3
-- http://www.phpmyadmin.net
--
-- 主机: 10.1.242.206
-- 生成日期: 2014 年 03 月 10 日 19:35
-- 服务器版本: 5.5.21-log
-- PHP 版本: 5.3.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `momo_contact`
--
CREATE DATABASE IF NOT EXISTS `momo_v3` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `momo_v3`;

--
-- 数据库: `momo_v3`
--

-- --------------------------------------------------------

--
-- 表的结构 `action`
--

CREATE TABLE IF NOT EXISTS `action` (
  `aid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `creator_id` int(11) unsigned NOT NULL COMMENT '活动创建者ID',
  `title` varchar(30) NOT NULL COMMENT '活动标题',
  `start_time` int(10) unsigned NOT NULL COMMENT '活动开始时间',
  `end_time` int(10) unsigned NOT NULL COMMENT '活动结束时间',
  `spot` varchar(50) NOT NULL COMMENT '活动地点',
  `content` varchar(300) NOT NULL COMMENT '活动内容',
  `is_allow_invite` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否允许参与活动的成员邀请自己的好友0为不允许1允许',
  `create_time` int(10) NOT NULL COMMENT '活动创建时间',
  `gid` int(11) NOT NULL DEFAULT '0' COMMENT '活动范围（我的好友或群）',
  `is_publish` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1好有活动时，是否发动态，2群活动时，是否给每个群成员发系统消息',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '活动类型（默认值1表示其他类型）',
  `belong_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '活动所属类型（0普通活动, 1公司活动, 2学校活动）',
  `belong_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属公司或者学校id,默认为0',
  `feed_id` char(32) DEFAULT NULL,
  `action_feed_id` char(32) DEFAULT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='活动' AUTO_INCREMENT=537 ;

-- --------------------------------------------------------

--
-- 表的结构 `action_group`
--

CREATE TABLE IF NOT EXISTS `action_group` (
  `aid` int(11) unsigned NOT NULL COMMENT '活动ID',
  `gid` int(11) NOT NULL COMMENT '群ID',
  KEY `group_action` (`aid`,`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='可参与活动群组';

-- --------------------------------------------------------

--
-- 表的结构 `action_invite`
--

CREATE TABLE IF NOT EXISTS `action_invite` (
  `aid` int(11) unsigned NOT NULL COMMENT '活动id',
  `uid` int(10) unsigned NOT NULL COMMENT '邀请者id',
  `invite_uid` int(10) unsigned NOT NULL COMMENT '被邀请者id',
  `invite_time` int(10) unsigned NOT NULL COMMENT '邀请时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '邀请处理状态(0:未处理,1:已处理)'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动邀请';

-- --------------------------------------------------------

--
-- 表的结构 `action_invite_register`
--

CREATE TABLE IF NOT EXISTS `action_invite_register` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(32) NOT NULL COMMENT '邀请码',
  `invite_uid` int(10) NOT NULL COMMENT '邀请人id',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '活动id',
  PRIMARY KEY (`id`),
  KEY `invite_code` (`invite_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='活动邀请注册信息' AUTO_INCREMENT=2148 ;

-- --------------------------------------------------------

--
-- 表的结构 `action_member`
--

CREATE TABLE IF NOT EXISTS `action_member` (
  `aid` int(11) unsigned NOT NULL COMMENT '活动id',
  `uid` int(10) unsigned NOT NULL COMMENT '活动报名者id',
  `apply_type` tinyint(1) unsigned NOT NULL COMMENT '报名选择(1:参加,2:不参加,3:感兴趣)',
  `apply_time` int(10) unsigned NOT NULL COMMENT '报名时间',
  `is_verify` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否通过管理员审批(0:未通过,1:通过)',
  `grade` tinyint(1) NOT NULL DEFAULT '1' COMMENT '活动成员权限（3发起者，2组织者，1普通报名者，-1未审核者）',
  `like_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后赞活动照片时间',
  `today_like` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '今天对活动照片赞的数量',
  `explain` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`aid`,`uid`),
  KEY `IDX_UID_APPLY` (`uid`,`apply_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动报名成员';

-- --------------------------------------------------------

--
-- 表的结构 `action_user`
--

CREATE TABLE IF NOT EXISTS `action_user` (
  `aid` int(11) unsigned NOT NULL COMMENT '活动id',
  `uid` int(11) unsigned NOT NULL COMMENT '用户id',
  PRIMARY KEY (`aid`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `adaptive_feedback`
--

CREATE TABLE IF NOT EXISTS `adaptive_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `session_id` char(36) DEFAULT NULL,
  `brand` varchar(15) DEFAULT NULL,
  `marque` varchar(25) DEFAULT NULL,
  `os` varchar(10) DEFAULT NULL,
  `adaptive` tinyint(1) DEFAULT '0' COMMENT '是否适配对:1是;2否',
  `custom_brand` varchar(15) DEFAULT '0' COMMENT '用户提交的品牌',
  `custom_marque` varchar(25) DEFAULT '0' COMMENT '用户提交的机型',
  `down` tinyint(1) DEFAULT '0' COMMENT '1能下载可以安装;2能下载,不能安装;3不能下载',
  `sound` tinyint(1) DEFAULT '0' COMMENT '1能播放;2不能播放,能下载;3不能播放,不能下载',
  `create_time` int(10) DEFAULT '0',
  `user_agent` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户反馈机型适配' AUTO_INCREMENT=20392 ;

-- --------------------------------------------------------

--
-- 表的结构 `agency`
--

CREATE TABLE IF NOT EXISTS `agency` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL,
  `short_title` varchar(10) NOT NULL,
  `area_code` varchar(6) NOT NULL,
  `telephone` varchar(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `create_at` int(10) NOT NULL,
  `brief` varchar(255) DEFAULT NULL,
  `profile` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_activity_album`
--

CREATE TABLE IF NOT EXISTS `album_activity_album` (
  `album_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) DEFAULT '0',
  `user_id` int(11) unsigned DEFAULT '0',
  `create_dt` int(10) NOT NULL,
  `update_dt` int(10) NOT NULL,
  `pic_num` int(11) NOT NULL DEFAULT '0',
  `privacy_lev` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1.所有用户可见、2: 成员可见 ',
  `album_desc` varchar(255) NOT NULL,
  `appid` int(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=76 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_activity_pic`
--

CREATE TABLE IF NOT EXISTS `album_activity_pic` (
  `pic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) unsigned NOT NULL,
  `album_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `create_time` int(10) NOT NULL,
  `update_time` int(10) NOT NULL,
  `pic_title` varchar(100) NOT NULL,
  `file_md5` varchar(42) NOT NULL,
  `file_type` tinyint(2) NOT NULL DEFAULT '2' COMMENT '1 = GIF，2 = JPG，3 = PNG',
  `file_size` int(11) NOT NULL,
  `pic_width` int(11) NOT NULL DEFAULT '0',
  `pic_height` int(11) NOT NULL DEFAULT '0',
  `degree` int(4) NOT NULL DEFAULT '0' COMMENT '旋转角度0-0度,1右旋90,2右旋180,3右旋270',
  `appid` int(4) NOT NULL DEFAULT '1',
  `is_animate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是gif动画',
  `likes` int(10) DEFAULT '0',
  PRIMARY KEY (`pic_id`),
  KEY `album_id` (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=153 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_avatar_album`
--

CREATE TABLE IF NOT EXISTS `album_avatar_album` (
  `album_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `create_dt` int(10) NOT NULL,
  `update_dt` int(10) NOT NULL,
  PRIMARY KEY (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_avatar_photo`
--

CREATE TABLE IF NOT EXISTS `album_avatar_photo` (
  `pic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `pic_title` varchar(100) NOT NULL,
  `file_md5` varchar(50) NOT NULL,
  `pic_width` varchar(20) NOT NULL,
  `pic_height` varchar(20) NOT NULL,
  `create_dt` int(10) NOT NULL,
  `upload_ip` varchar(20) NOT NULL,
  `appid` int(4) NOT NULL DEFAULT '0' COMMENT '3标识为web端flash相册应用的头像照片',
  PRIMARY KEY (`pic_id`),
  KEY `album_id` (`album_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_categories`
--

CREATE TABLE IF NOT EXISTS `album_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `sort_id` int(4) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_group_album`
--

CREATE TABLE IF NOT EXISTS `album_group_album` (
  `album_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_name` varchar(100) NOT NULL,
  `group_id` int(11) DEFAULT '0',
  `user_id` int(11) unsigned DEFAULT '0',
  `create_dt` int(10) NOT NULL,
  `update_dt` int(10) NOT NULL,
  `pic_num` int(11) NOT NULL DEFAULT '0',
  `cover_pic_id` int(11) unsigned NOT NULL,
  `cover_pic_url` varchar(255) NOT NULL,
  `privacy_lev` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1.所有用户可见、2: 成员可见 ',
  `album_desc` varchar(255) NOT NULL,
  `album_spot` varchar(100) NOT NULL,
  `view_cnt` int(11) NOT NULL DEFAULT '0',
  `album_default` tinyint(1) NOT NULL DEFAULT '0',
  `allow_upload` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否允许别人上传照片 默认为1，0标识为不允许',
  `appid` int(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=390 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_group_pic`
--

CREATE TABLE IF NOT EXISTS `album_group_pic` (
  `pic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL,
  `album_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `create_time` int(10) NOT NULL,
  `update_time` int(10) NOT NULL,
  `upload_ip` char(20) NOT NULL,
  `upload_file_name` varchar(100) NOT NULL,
  `pic_title` varchar(100) NOT NULL,
  `file_md5` varchar(42) NOT NULL,
  `datetime_original` varchar(50) NOT NULL COMMENT '拍摄时间',
  `camera_model` varchar(50) NOT NULL COMMENT '相机型号',
  `file_type` tinyint(2) NOT NULL DEFAULT '2' COMMENT '1 = GIF，2 = JPG，3 = PNG',
  `file_url` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `pic_width` int(11) NOT NULL DEFAULT '0',
  `pic_height` int(11) NOT NULL DEFAULT '0',
  `nav_cnt` int(11) unsigned DEFAULT '0',
  `comment_cnt` int(11) unsigned DEFAULT '0',
  `degree` int(4) NOT NULL DEFAULT '0' COMMENT '旋转角度0-0度,1右旋90,2右旋180,3右旋270',
  `sorts` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-1 审核不通过0 未审核1 已审核通过',
  `admin_id` int(11) NOT NULL,
  `from` tinyint(2) NOT NULL COMMENT '图片来源:1web,2-91see,3-phone,4-91u',
  `appid` int(4) NOT NULL DEFAULT '1',
  `is_animate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是gif动画',
  PRIMARY KEY (`pic_id`),
  KEY `album_id` (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2728 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_group_pic_desc`
--

CREATE TABLE IF NOT EXISTS `album_group_pic_desc` (
  `pic_id` int(11) unsigned NOT NULL,
  `album_id` int(11) unsigned NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `pic_desc` text,
  PRIMARY KEY (`pic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `album_group_pic_temp`
--

CREATE TABLE IF NOT EXISTS `album_group_pic_temp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file_dna` char(52) DEFAULT NULL,
  `pic_title` varchar(100) DEFAULT NULL,
  `pic_desc` text,
  `upload_file_name` varchar(100) NOT NULL,
  `temp_file_path` varchar(64) DEFAULT NULL,
  `tmp_offset` int(11) NOT NULL DEFAULT '0',
  `album_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_dt` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_dna` (`file_dna`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_group_sorts`
--

CREATE TABLE IF NOT EXISTS `album_group_sorts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(11) unsigned NOT NULL,
  `sorts` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `album_id` (`album_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_limituser`
--

CREATE TABLE IF NOT EXISTS `album_limituser` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `limituser` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_pic`
--

CREATE TABLE IF NOT EXISTS `album_pic` (
  `pic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(11) unsigned NOT NULL,
  `album_name` varchar(100) NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `create_time` int(10) NOT NULL,
  `update_time` int(10) NOT NULL,
  `upload_ip` char(20) NOT NULL,
  `upload_file_name` varchar(100) NOT NULL,
  `pic_title` varchar(100) NOT NULL,
  `file_id` int(11) NOT NULL,
  `file_md5` varchar(42) NOT NULL,
  `file_type` tinyint(2) NOT NULL DEFAULT '2' COMMENT '1 = GIF，2 = JPG，3 = PNG',
  `datetime_original` varchar(50) DEFAULT NULL COMMENT '拍摄时间',
  `camera_model` varchar(50) DEFAULT NULL COMMENT '相机型号',
  `file_url` varchar(100) NOT NULL,
  `pic_fs_path` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `pic_width` int(11) NOT NULL DEFAULT '0',
  `pic_height` int(11) NOT NULL DEFAULT '0',
  `nav_cnt` int(11) unsigned DEFAULT '0',
  `degree` int(4) NOT NULL DEFAULT '0' COMMENT '旋转角度0-0度,1右旋90,2右旋180,3右旋270',
  `sorts` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-1 审核不通过0 未审核1 已审核通过',
  `admin_id` int(11) NOT NULL,
  `is_avatar` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为当前使用的头像照',
  `is_animate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是gif动画',
  `from` tinyint(2) NOT NULL COMMENT '图片来源:1web,2-91see,3-phone,4-91u',
  `album_default` tinyint(1) NOT NULL DEFAULT '1' COMMENT '2标识为头像相册中的照片',
  PRIMARY KEY (`pic_id`),
  KEY `album_id` (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9071 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_pic_desc`
--

CREATE TABLE IF NOT EXISTS `album_pic_desc` (
  `pic_id` int(11) unsigned NOT NULL,
  `album_id` int(11) unsigned NOT NULL,
  `pic_desc` text,
  `pic_exif` text NOT NULL,
  KEY `pic_id` (`pic_id`),
  KEY `album_id` (`album_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `album_pic_sorts`
--

CREATE TABLE IF NOT EXISTS `album_pic_sorts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(11) unsigned NOT NULL,
  `sorts` text NOT NULL,
  `update_dt` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `album_id` (`album_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=480 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_pic_temp`
--

CREATE TABLE IF NOT EXISTS `album_pic_temp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file_dna` char(52) DEFAULT NULL,
  `pic_title` varchar(100) DEFAULT NULL,
  `pic_desc` text,
  `tag` varchar(100) NOT NULL,
  `upload_file_name` varchar(100) NOT NULL,
  `temp_file_path` varchar(64) DEFAULT NULL,
  `tmp_offset` int(11) NOT NULL DEFAULT '0',
  `album_id` int(11) DEFAULT NULL,
  `pic_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_dt` int(10) DEFAULT NULL,
  `is_thumb` tinyint(1) NOT NULL DEFAULT '0',
  `is_avatar` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为头像照，1为时，0为否，默认为0',
  `from` tinyint(2) NOT NULL COMMENT '图片来源:1web,2-91see,3-phone,4-91u',
  `appid` int(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `file_dna` (`file_dna`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_pwd_users`
--

CREATE TABLE IF NOT EXISTS `album_pwd_users` (
  `album_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  KEY `album_id` (`album_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `album_sorts`
--

CREATE TABLE IF NOT EXISTS `album_sorts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `sorts` text NOT NULL,
  `appid` int(4) NOT NULL DEFAULT '3',
  `update_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `album_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=157 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_upload_temp`
--

CREATE TABLE IF NOT EXISTS `album_upload_temp` (
  `temp_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `file_title` varchar(100) DEFAULT NULL,
  `file_type` tinyint(2) NOT NULL DEFAULT '2',
  `file_md5` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_offset` int(11) NOT NULL DEFAULT '0',
  `file_path` varchar(100) NOT NULL,
  `create_dt` int(10) NOT NULL,
  PRIMARY KEY (`temp_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3941 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_user_album`
--

CREATE TABLE IF NOT EXISTS `album_user_album` (
  `album_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_name` varchar(100) NOT NULL,
  `user_id` int(11) unsigned DEFAULT '0',
  `user_name` varchar(30) NOT NULL,
  `create_dt` int(10) NOT NULL,
  `update_dt` int(10) NOT NULL,
  `pic_num` int(11) NOT NULL DEFAULT '0',
  `cover_pic_id` int(11) unsigned NOT NULL,
  `cover_pic_url` varchar(255) NOT NULL,
  `privacy_lev` tinyint(4) NOT NULL DEFAULT '1',
  `album_pwd` varchar(50) NOT NULL,
  `album_pwd_prompt` varchar(50) NOT NULL,
  `allow_comment` tinyint(4) NOT NULL DEFAULT '1',
  `allow_repost` tinyint(4) NOT NULL DEFAULT '1',
  `album_desc` varchar(255) NOT NULL,
  `album_spot` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT '1',
  `album_default` tinyint(1) NOT NULL DEFAULT '0',
  `comment_cnt` int(11) NOT NULL,
  `favorite_cnt` int(11) NOT NULL,
  `view_cnt` int(11) NOT NULL,
  `support_cnt` int(11) NOT NULL,
  `view_app` int(11) NOT NULL,
  PRIMARY KEY (`album_id`),
  KEY `user_id` (`user_id`,`album_id`),
  KEY `album_id` (`album_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36016 ;

-- --------------------------------------------------------

--
-- 表的结构 `album_user_dynamic`
--

CREATE TABLE IF NOT EXISTS `album_user_dynamic` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `album_id` int(11) unsigned NOT NULL,
  `update_dt` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=205 ;

-- --------------------------------------------------------

--
-- 表的结构 `analy_log`
--

CREATE TABLE IF NOT EXISTS `analy_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appid` int(10) unsigned NOT NULL,
  `type` char(16) NOT NULL,
  `code` smallint(8) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `client_id` smallint(3) NOT NULL,
  `user_agent` varchar(250) NOT NULL,
  `created` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=106778 ;

-- --------------------------------------------------------

--
-- 表的结构 `apns_device`
--

CREATE TABLE IF NOT EXISTS `apns_device` (
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `device_id` char(64) NOT NULL COMMENT '设备ID',
  `im_last_time` int(10) unsigned NOT NULL COMMENT '此用户发出的最后一条消息时间',
  `im_last_client` tinyint(3) unsigned NOT NULL,
  `app_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  KEY `uid` (`uid`),
  KEY `device_id` (`device_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='苹果消息推送设备表';

-- --------------------------------------------------------

--
-- 表的结构 `apply`
--

CREATE TABLE IF NOT EXISTS `apply` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `number` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- 表的结构 `apps`
--

CREATE TABLE IF NOT EXISTS `apps` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '组件名称',
  `intro` text NOT NULL COMMENT '组件介绍',
  `url` varchar(100) NOT NULL COMMENT '组件链接',
  `issys` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统组件',
  `icon` varchar(50) NOT NULL COMMENT '图标',
  `developer` varchar(50) NOT NULL COMMENT '开发者',
  `developerurl` varchar(100) NOT NULL COMMENT '开发者链接',
  `usernums` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '使用人数',
  `pubdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布日期',
  `update` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新日期',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='应用表' AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- 表的结构 `appsuser`
--

CREATE TABLE IF NOT EXISTS `appsuser` (
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `appid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `pos` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `allowfeed` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否接收此应用的事件',
  `allownotification` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '就否接收此应用的通知',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '组件添加时间',
  PRIMARY KEY (`uid`,`appid`),
  KEY `pos` (`uid`,`pos`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户添加的应用表';

-- --------------------------------------------------------

--
-- 表的结构 `app_91_usage`
--

CREATE TABLE IF NOT EXISTS `app_91_usage` (
  `uid` int(10) unsigned NOT NULL,
  `appid` int(10) unsigned NOT NULL,
  `imei` varchar(200) NOT NULL,
  `phone_model` varchar(200) NOT NULL,
  `phone_os` varchar(200) NOT NULL,
  KEY `uid` (`uid`,`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_download`
--

CREATE TABLE IF NOT EXISTS `app_download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) DEFAULT NULL COMMENT '平台',
  `phone_model` int(11) DEFAULT NULL COMMENT '型号',
  `brand_id` int(11) DEFAULT NULL COMMENT '品牌',
  `dl_times` int(11) DEFAULT NULL COMMENT '下载次数',
  `channel` varchar(45) DEFAULT NULL COMMENT '下载渠道',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46356 ;

-- --------------------------------------------------------

--
-- 表的结构 `app_message`
--

CREATE TABLE IF NOT EXISTS `app_message` (
  `id` bigint(13) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` char(36) NOT NULL COMMENT '消息id',
  `app` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '所属应用',
  `timestamp` bigint(13) unsigned NOT NULL COMMENT '消息生成时间',
  `ownerid` int(10) unsigned NOT NULL COMMENT '所属用户id',
  `opid` varchar(32) NOT NULL COMMENT '会话方id',
  `optype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '会话方的类型(1群组,0单个用户)',
  `box` tinyint(1) unsigned NOT NULL COMMENT '标识是收取还是发送(0收,1送)',
  `content_key` varchar(32) NOT NULL COMMENT '消息内容的key',
  `s_client` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：发送的客户端类型',
  `r_roger` set('1','2','3') NOT NULL DEFAULT '' COMMENT '接收属性：消息送达标识(1送到客户端,2送到苹果设备,3送到手机,''''未送达)',
  `r_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：消息已读时间(0未读)',
  PRIMARY KEY (`id`),
  KEY `msgid` (`msgid`),
  KEY `app_ownerid_opid_optype_box` (`app`,`ownerid`,`opid`,`optype`,`box`),
  KEY `r_time` (`r_time`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1433 ;

-- --------------------------------------------------------

--
-- 表的结构 `app_message_group`
--

CREATE TABLE IF NOT EXISTS `app_message_group` (
  `listmd5` char(32) CHARACTER SET latin1 NOT NULL COMMENT 'MD5(list)',
  `list` text CHARACTER SET latin1 NOT NULL COMMENT '参与会话的所有用户uid不重复顺序排序并以'',''分隔',
  PRIMARY KEY (`listmd5`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_message_notify`
--

CREATE TABLE IF NOT EXISTS `app_message_notify` (
  `ownerid` int(10) unsigned NOT NULL COMMENT '接收者的uid',
  `opid` int(10) NOT NULL COMMENT '发送者的uid或者群的id',
  `app` tinyint(3) unsigned NOT NULL COMMENT '所属应用',
  `r_last_time` bigint(13) unsigned NOT NULL COMMENT '最后接收的消息的时间',
  `r_last_msgid` varchar(36) NOT NULL COMMENT '最后接收的消息id',
  `r_new_count` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT '来自opid的且ownerid未读消息条数',
  PRIMARY KEY (`ownerid`,`opid`,`app`),
  KEY `r_last_time` (`r_last_time`),
  KEY `r_new_count` (`r_new_count`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='会话统计';

-- --------------------------------------------------------

--
-- 表的结构 `app_push`
--

CREATE TABLE IF NOT EXISTS `app_push` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
  `push_time` smallint(4) unsigned zerofill NOT NULL DEFAULT '0000' COMMENT '推送时间',
  `location` varchar(30) NOT NULL DEFAULT '' COMMENT '推送地点',
  `push_interval` char(10) NOT NULL DEFAULT '' COMMENT '推送周期',
  KEY `uid_rid` (`uid`,`robot_id`),
  KEY `rid_time` (`robot_id`,`push_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_robot`
--

CREATE TABLE IF NOT EXISTS `app_robot` (
  `robot_id` int(11) unsigned NOT NULL COMMENT '机器人ID(对应UID)',
  `appid` int(11) unsigned NOT NULL COMMENT '应用ID',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '机器人名称',
  `created` int(11) NOT NULL COMMENT '创建时间',
  `command_type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '指令类型',
  `command` text NOT NULL COMMENT '机器人指令',
  `auto_query_command` text NOT NULL COMMENT '自动查询指令',
  `desc` text NOT NULL COMMENT '机器人说明',
  PRIMARY KEY (`robot_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `app_subscription`
--

CREATE TABLE IF NOT EXISTS `app_subscription` (
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `robot_id` int(11) unsigned NOT NULL COMMENT '机器人ID',
  `dateline` int(10) NOT NULL COMMENT '订阅时间',
  `active` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否激活',
  PRIMARY KEY (`uid`,`robot_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `autobackup_91`
--

CREATE TABLE IF NOT EXISTS `autobackup_91` (
  `uid` int(10) unsigned NOT NULL,
  `imei` varchar(100) NOT NULL,
  `contact` tinyint(1) NOT NULL DEFAULT '0',
  `sms` tinyint(1) NOT NULL DEFAULT '0',
  `call` tinyint(1) NOT NULL DEFAULT '0',
  `photo` tinyint(1) NOT NULL DEFAULT '0',
  `app` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `uid` (`uid`,`imei`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `card_sharelog`
--

CREATE TABLE IF NOT EXISTS `card_sharelog` (
  `uid` bigint(20) unsigned NOT NULL,
  `opid` bigint(20) unsigned NOT NULL,
  `ctime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`uid`,`opid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='名片发送日志';

-- --------------------------------------------------------

--
-- 表的结构 `car_assistant`
--

CREATE TABLE IF NOT EXISTS `car_assistant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_user` varchar(200) NOT NULL,
  `to_user` varchar(200) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `nickname` varchar(500) NOT NULL,
  `content` text NOT NULL,
  `plate_number` varchar(50) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL,
  `msg_type` char(10) NOT NULL,
  `break_total` smallint(5) NOT NULL,
  `last_break_time` int(10) NOT NULL,
  `last_rotate_time` int(10) NOT NULL,
  `create_time` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `plate_number` (`plate_number`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

--
-- 表的结构 `car_break_rules`
--

CREATE TABLE IF NOT EXISTS `car_break_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plate_number` char(12) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `no` varchar(50) NOT NULL,
  `break_time` int(10) NOT NULL,
  `break_address` varchar(100) NOT NULL,
  `break_code` varchar(30) NOT NULL,
  `detail_url` varchar(200) NOT NULL,
  `handled` tinyint(1) NOT NULL DEFAULT '0',
  `delivered` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `plate_number` (`plate_number`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40 ;

-- --------------------------------------------------------

--
-- 表的结构 `car_daily_rorate`
--

CREATE TABLE IF NOT EXISTS `car_daily_rorate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(40) NOT NULL,
  `no` varchar(200) NOT NULL,
  `add_time` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `change_mobile_log`
--

CREATE TABLE IF NOT EXISTS `change_mobile_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `old_mobile_zone_code` char(20) NOT NULL,
  `old_mobile` char(11) NOT NULL,
  `new_mobile_zone_code` char(20) NOT NULL,
  `new_mobile` char(11) NOT NULL,
  `new_uid` int(10) unsigned NOT NULL,
  `created` int(10) NOT NULL,
  `updated` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=600 ;

-- --------------------------------------------------------

--
-- 表的结构 `change_number_log`
--

CREATE TABLE IF NOT EXISTS `change_number_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `old_mobile` char(11) NOT NULL,
  `new_mobile` char(11) NOT NULL,
  `dateline` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- 表的结构 `city`
--

CREATE TABLE IF NOT EXISTS `city` (
  `id` int(11) NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
  `name` varchar(128) NOT NULL COMMENT '地区名字',
  `py` varchar(64) NOT NULL COMMENT '地区拼音',
  `ord` int(11) NOT NULL AUTO_INCREMENT COMMENT '排序',
  PRIMARY KEY (`ord`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3274 ;

-- --------------------------------------------------------

--
-- 表的结构 `city_collegeschool`
--

CREATE TABLE IF NOT EXISTS `city_collegeschool` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL COMMENT '名称',
  `py` varchar(64) NOT NULL COMMENT '拼音',
  `area_id` int(11) NOT NULL COMMENT '所在地id',
  UNIQUE KEY `id` (`id`),
  KEY `area_id` (`area_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `city_highschool`
--

CREATE TABLE IF NOT EXISTS `city_highschool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT '名称',
  `py` varchar(64) NOT NULL COMMENT '拼音',
  `area_id` int(11) NOT NULL COMMENT '所在地id',
  `verify` tinyint(2) NOT NULL DEFAULT '1' COMMENT '学校是否通过审核(1通过审核、0未审核，默认值1)',
  UNIQUE KEY `id` (`id`),
  KEY `area_id` (`area_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10328323 ;

-- --------------------------------------------------------

--
-- 表的结构 `city_juniorschool`
--

CREATE TABLE IF NOT EXISTS `city_juniorschool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT '名称',
  `py` varchar(64) NOT NULL COMMENT '拼音',
  `area_id` int(11) NOT NULL COMMENT '所在地id',
  `verify` tinyint(2) NOT NULL DEFAULT '1' COMMENT '学校是否通过审核(1通过审核、0未审核，默认值1)',
  UNIQUE KEY `id` (`id`),
  KEY `area_id` (`area_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40172616 ;

-- --------------------------------------------------------

--
-- 表的结构 `city_universityschool`
--

CREATE TABLE IF NOT EXISTS `city_universityschool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT '名称',
  `py` varchar(64) NOT NULL COMMENT '拼音',
  `area_id` int(11) NOT NULL COMMENT '所在地id',
  `verify` tinyint(2) NOT NULL DEFAULT '1' COMMENT '学校是否通过审核(1通过审核、0未审核，默认值1)',
  UNIQUE KEY `id` (`id`),
  KEY `area_id` (`area_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=34047 ;

-- --------------------------------------------------------

--
-- 表的结构 `classic_sms`
--

CREATE TABLE IF NOT EXISTS `classic_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=101 ;

-- --------------------------------------------------------

--
-- 表的结构 `cloud_91_userlog`
--

CREATE TABLE IF NOT EXISTS `cloud_91_userlog` (
  `appid` int(10) unsigned NOT NULL,
  `uin` int(10) unsigned NOT NULL,
  `created` int(10) NOT NULL,
  UNIQUE KEY `appid` (`appid`,`uin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `cloud_tickets`
--

CREATE TABLE IF NOT EXISTS `cloud_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket` varchar(100) DEFAULT NULL,
  `token` varchar(100) DEFAULT NULL,
  `expire` int(10) DEFAULT NULL,
  `last_update` int(10) DEFAULT NULL,
  `uid` bigint(20) DEFAULT NULL,
  `app` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='cloud用户的登陆凭据' AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- 表的结构 `collage`
--

CREATE TABLE IF NOT EXISTS `collage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `college` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='大学字典 ' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `comment`
--

CREATE TABLE IF NOT EXISTS `comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `appid` char(21) NOT NULL DEFAULT '0' COMMENT '应用id,例如某条转帖或某篇日记的id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父评论id',
  `privacy` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '隐私,0:普通;1:悄悄话',
  `childs` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '子评论数',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论日期',
  `uid` int(11) unsigned NOT NULL,
  `owner` int(11) unsigned NOT NULL,
  `updatetime` int(11) NOT NULL,
  `im` tinyint(1) NOT NULL DEFAULT '0',
  `content` varchar(1400) NOT NULL COMMENT '评论内容',
  `appdescribe` varchar(30) NOT NULL COMMENT '指明是什么的评论，例如1为日记，2为转帖，3为投票等。',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `id` (`appid`,`appdescribe`,`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='评论' AUTO_INCREMENT=52843 ;

-- --------------------------------------------------------

--
-- 表的结构 `comment_last`
--

CREATE TABLE IF NOT EXISTS `comment_last` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appid` int(11) NOT NULL,
  `appdescribe` varchar(20) NOT NULL,
  `addtime` int(11) NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `owner` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `company`
--

CREATE TABLE IF NOT EXISTS `company` (
  `cid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '公司id，自动递增',
  `name` varchar(255) NOT NULL COMMENT '公司名称',
  `trade` tinyint(1) NOT NULL DEFAULT '0' COMMENT '行业',
  `website` varchar(255) NOT NULL DEFAULT '' COMMENT '官网',
  `email` varchar(255) NOT NULL DEFAULT '' COMMENT '邮箱',
  `scale` int(11) NOT NULL DEFAULT '0' COMMENT '规模(人数范围)',
  `province_id` int(10) NOT NULL DEFAULT '0' COMMENT '省份id',
  `area_id` int(10) NOT NULL DEFAULT '0' COMMENT '地区id',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '公司创建时间',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者的ID',
  `verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '公司是否通过审核',
  `audit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动审核(0:不审核,1:关链company_user,2:邮件审核)',
  `notice` varchar(200) NOT NULL DEFAULT '' COMMENT '公告',
  PRIMARY KEY (`cid`),
  KEY `name` (`verify`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='公司' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- 表的结构 `company_bak`
--

CREATE TABLE IF NOT EXISTS `company_bak` (
  `cid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '公司id，自动递增',
  `name` varchar(256) NOT NULL COMMENT '公司名称',
  `province_id` int(10) NOT NULL COMMENT '省份id',
  `area_id` int(10) unsigned NOT NULL COMMENT '地区id',
  `createtime` int(10) unsigned NOT NULL COMMENT '创建时间',
  `creator_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者的ID',
  `verify` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '公司是否通过审核',
  PRIMARY KEY (`cid`),
  KEY `cityeid` (`area_id`),
  KEY `name` (`name`),
  KEY `areaid_name` (`area_id`,`name`),
  KEY `provinceid_name` (`province_id`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='公司' AUTO_INCREMENT=3181751 ;

-- --------------------------------------------------------

--
-- 表的结构 `company_member`
--

CREATE TABLE IF NOT EXISTS `company_member` (
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公司id',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `grade` tinyint(1) NOT NULL COMMENT '等级,0未验证,1管理员,2员工',
  `join_time` char(6) NOT NULL DEFAULT '' COMMENT '入职时间',
  `leave_time` char(6) NOT NULL DEFAULT '' COMMENT '离职时间',
  `datetime` int(10) unsigned NOT NULL COMMENT '加入时间',
  `activity` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否停用',
  PRIMARY KEY (`cid`,`uid`),
  KEY `uid` (`uid`),
  KEY `cid_grade` (`cid`,`grade`),
  KEY `uid_grade` (`uid`,`grade`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='公司成员';

-- --------------------------------------------------------

--
-- 表的结构 `company_staff`
--

CREATE TABLE IF NOT EXISTS `company_staff` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id(company表)',
  `staff_id` varchar(64) NOT NULL,
  `staff_name` varchar(64) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否在职',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='自动验证表' AUTO_INCREMENT=8938 ;

-- --------------------------------------------------------

--
-- 表的结构 `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `cid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `formatted_name` varchar(75) NOT NULL COMMENT '姓名',
  `phonetic` varchar(255) NOT NULL COMMENT '名字拼音',
  `given_name` varchar(32) NOT NULL COMMENT '名',
  `middle_name` varchar(32) NOT NULL COMMENT '中间名',
  `family_name` varchar(32) NOT NULL COMMENT '姓',
  `prefix` varchar(32) NOT NULL COMMENT '前缀',
  `suffix` varchar(32) NOT NULL COMMENT '后缀',
  `organization` varchar(255) DEFAULT NULL COMMENT '公司',
  `department` varchar(255) DEFAULT NULL COMMENT '部门',
  `note` text COMMENT '备注',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `title` varchar(75) DEFAULT NULL COMMENT '职称(最多75个字符)',
  `nickname` varchar(75) DEFAULT NULL COMMENT '昵称(最多75个字符)',
  `sort` varchar(20) NOT NULL COMMENT '姓名首字母',
  `created` int(11) DEFAULT '0' COMMENT '创建时间',
  `modified` int(11) DEFAULT '0' COMMENT '修改时间',
  `fid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对应用户ID',
  `recycle` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否在回收站',
  `source` varchar(30) DEFAULT '' COMMENT '数据来源',
  `lunar_bday` varchar(25) NOT NULL COMMENT '农历生日',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `health` tinyint(1) NOT NULL DEFAULT '0' COMMENT '健康状态',
  PRIMARY KEY (`cid`),
  KEY `IDX_FN` (`formatted_name`),
  KEY `idx_all` (`uid`,`sort`,`recycle`),
  KEY `IDX_UID_FID` (`uid`,`fid`,`recycle`),
  KEY `IDX_FID_UID` (`fid`,`recycle`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='联系人' AUTO_INCREMENT=27150414 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_addresses`
--

CREATE TABLE IF NOT EXISTS `contact_addresses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '地址ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `country` varchar(255) DEFAULT NULL COMMENT '值',
  `postal` varchar(20) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `street` text,
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='地址' AUTO_INCREMENT=386712 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_avatars`
--

CREATE TABLE IF NOT EXISTS `contact_avatars` (
  `cid` int(11) unsigned NOT NULL COMMENT '编号',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `avatar` text COMMENT '头像',
  `space` smallint(1) DEFAULT '0' COMMENT '是否SNS头像',
  `dateline` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`cid`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='联系人头像';

-- --------------------------------------------------------

--
-- 表的结构 `contact_check_result`
--

CREATE TABLE IF NOT EXISTS `contact_check_result` (
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `result` mediumtext COMMENT '体检结果',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC COMMENT='联系人体检结果';

-- --------------------------------------------------------

--
-- 表的结构 `contact_city`
--

CREATE TABLE IF NOT EXISTS `contact_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=506 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_conflicts`
--

CREATE TABLE IF NOT EXISTS `contact_conflicts` (
  `conflict_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '联系人信息冲突ID',
  `uid` int(10) unsigned DEFAULT '0' COMMENT '用户ID',
  `cid` int(10) unsigned DEFAULT '0' COMMENT '联系人ID',
  `fid` int(10) unsigned DEFAULT '0' COMMENT '冲突用户ID',
  PRIMARY KEY (`conflict_id`),
  KEY `idx` (`uid`,`cid`,`fid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=50838 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_emails`
--

CREATE TABLE IF NOT EXISTS `contact_emails` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '邮箱ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_VALUE` (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='邮箱' AUTO_INCREMENT=1396219 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_events`
--

CREATE TABLE IF NOT EXISTS `contact_events` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日期ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='日期' AUTO_INCREMENT=64518 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_groups`
--

CREATE TABLE IF NOT EXISTS `contact_groups` (
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分组ID',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '联系人ID',
  PRIMARY KEY (`uid`,`gid`,`cid`),
  KEY `cid` (`cid`),
  KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='分组联系人';

-- --------------------------------------------------------

--
-- 表的结构 `contact_history`
--

CREATE TABLE IF NOT EXISTS `contact_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '联系人修改历史ID',
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `source` int(10) DEFAULT NULL COMMENT '数据来源ID',
  `add_num` int(11) DEFAULT NULL COMMENT '新增数量',
  `edit_num` int(11) DEFAULT NULL COMMENT '修改数量',
  `delete_num` int(11) DEFAULT NULL COMMENT '删除数量',
  `dateline` int(10) DEFAULT NULL COMMENT '修改时间戳',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`source`,`dateline`),
  KEY `IDX` (`uid`,`source`,`dateline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='联系人修改历史表' AUTO_INCREMENT=1081673 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_import_history`
--

CREATE TABLE IF NOT EXISTS `contact_import_history` (
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `dateline` int(11) DEFAULT '0' COMMENT '时间戳',
  `cids` mediumtext COMMENT '联系人ID',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC COMMENT='导入历史表';

-- --------------------------------------------------------

--
-- 表的结构 `contact_ims`
--

CREATE TABLE IF NOT EXISTS `contact_ims` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '即时通讯ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '联系人ID',
  `protocol` varchar(50) NOT NULL COMMENT '协议',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='即时通讯' AUTO_INCREMENT=149611 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_mygroups`
--

CREATE TABLE IF NOT EXISTS `contact_mygroups` (
  `gid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `name` varchar(32) DEFAULT NULL COMMENT '分组名',
  `orderby` int(11) NOT NULL DEFAULT '0' COMMENT '分组排序',
  PRIMARY KEY (`gid`),
  KEY `IDX_ORDERBY` (`orderby`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='联系人分组表' AUTO_INCREMENT=31424 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_phone_number`
--

CREATE TABLE IF NOT EXISTS `contact_phone_number` (
  `number` int(11) NOT NULL,
  `contact_city_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  PRIMARY KEY (`number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `contact_phone_number_type`
--

CREATE TABLE IF NOT EXISTS `contact_phone_number_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=149 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_queue`
--

CREATE TABLE IF NOT EXISTS `contact_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '队列ID',
  `operation` varchar(20) DEFAULT NULL COMMENT '操作名',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content` text COMMENT '操作内容',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_relations`
--

CREATE TABLE IF NOT EXISTS `contact_relations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '人员ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='关系人' AUTO_INCREMENT=7043 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_share`
--

CREATE TABLE IF NOT EXISTS `contact_share` (
  `share_id` int(11) unsigned NOT NULL COMMENT '分享ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '联系人ID',
  `fid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '好友ID',
  PRIMARY KEY (`uid`,`cid`,`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='联系人分享表';

-- --------------------------------------------------------

--
-- 表的结构 `contact_tels`
--

CREATE TABLE IF NOT EXISTS `contact_tels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '电话ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  `pref` smallint(6) NOT NULL DEFAULT '0' COMMENT '是否主手机',
  `city` varchar(30) NOT NULL DEFAULT '' COMMENT '归属地',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_VALUE` (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='电话' AUTO_INCREMENT=29924667 ;

-- --------------------------------------------------------

--
-- 表的结构 `contact_urls`
--

CREATE TABLE IF NOT EXISTS `contact_urls` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `cid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`cid`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='网址' AUTO_INCREMENT=585949 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_apns_device`
--

CREATE TABLE IF NOT EXISTS `cs_apns_device` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cs_id` varchar(64) NOT NULL DEFAULT '' COMMENT '来电秀用户id，guid或者uid',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '账号类型,1:uid,0:guid',
  `device_token` varchar(64) NOT NULL DEFAULT '' COMMENT 'apns token',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dt` (`device_token`),
  UNIQUE KEY `cs_id` (`cs_id`,`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀apns token相关表' AUTO_INCREMENT=287831 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_contact_device`
--

CREATE TABLE IF NOT EXISTS `cs_contact_device` (
  `guid` varchar(100) NOT NULL DEFAULT '' COMMENT '设备ID',
  `data` mediumtext COMMENT '联系人数据',
  `dateline` int(10) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `cs_contact_device_history`
--

CREATE TABLE IF NOT EXISTS `cs_contact_device_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '历史ID',
  `file_md5` varchar(32) NOT NULL DEFAULT '' COMMENT '文件md5',
  `dateline` int(10) NOT NULL DEFAULT '0' COMMENT '时间',
  `guid` varchar(100) NOT NULL DEFAULT '' COMMENT '设备ID',
  `client_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户端来源',
  PRIMARY KEY (`id`),
  KEY `device` (`guid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=710369 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_contact_file`
--

CREATE TABLE IF NOT EXISTS `cs_contact_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `md5` varchar(32) NOT NULL DEFAULT '' COMMENT '文件md5',
  `size` int(11) NOT NULL DEFAULT '0' COMMENT '文件大小',
  `ndfs_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ndfs_id',
  `cflag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '文件是否上传完0:未传完，1:已传完',
  `refcount` smallint(6) NOT NULL DEFAULT '0' COMMENT '文件被引用次数',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '文件创建时间',
  `finish_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '完成上传时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `md5` (`md5`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='文件信息' AUTO_INCREMENT=663070 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_contact_history`
--

CREATE TABLE IF NOT EXISTS `cs_contact_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '历史ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '联系人备份地址',
  `reason` varchar(20) NOT NULL DEFAULT '' COMMENT '备份原因',
  `dateline` int(10) NOT NULL DEFAULT '0' COMMENT '时间',
  `appid` int(10) NOT NULL DEFAULT '0' COMMENT '应用ID',
  `device_id` varchar(200) NOT NULL DEFAULT '' COMMENT '设备ID',
  `client_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户端来源',
  `guid` varchar(100) NOT NULL DEFAULT '' COMMENT 'GUID',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=691577 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_device`
--

CREATE TABLE IF NOT EXISTS `cs_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL DEFAULT '',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '识别串类型,0随机，1只有imei，2只有mac，3两者都有',
  `guid` varchar(100) NOT NULL DEFAULT '' COMMENT '设备 guid',
  `mac` varchar(32) NOT NULL DEFAULT '' COMMENT '设备 mac地址',
  `imei` varchar(32) NOT NULL DEFAULT '' COMMENT '设备 imei',
  `imsi` varchar(32) NOT NULL DEFAULT '' COMMENT '设备 imsi',
  `client_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '客户端类型',
  `phone_model` varchar(40) NOT NULL DEFAULT '' COMMENT '手机型号',
  `os` varchar(20) NOT NULL DEFAULT '' COMMENT '手机系统版本号',
  `bind_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '绑定时间',
  `create_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时的uid',
  `last_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次绑定的uid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `iden` (`identifier`),
  KEY `guid` (`guid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀 设备列表' AUTO_INCREMENT=396709 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_event`
--

CREATE TABLE IF NOT EXISTS `cs_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `initiator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发起人uid',
  `recipient` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接受者uid',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '事件类型:1为我设秀,2送礼物给我,3系统通知,4好友加入来电秀,5好友更新来电秀',
  `detail_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事件对应详情的id',
  `event_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事件发生时间',
  `readed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读,0否，1是',
  `read_time` int(11) unsigned DEFAULT NULL COMMENT '已读时间',
  `outdated` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已失效',
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`,`type`,`readed`,`outdated`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀-关于我的' AUTO_INCREMENT=867277 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_event_guid`
--

CREATE TABLE IF NOT EXISTS `cs_event_guid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `initiator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发起人uid',
  `recipient` varchar(64) NOT NULL DEFAULT '' COMMENT '接受者uid',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '事件类型:1为我设秀,2送礼物给我,3系统通知,4好友加入来电秀,5好友更新来电秀',
  `detail_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事件对应详情的id',
  `event_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事件发生时间',
  `readed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读,0否，1是',
  `read_time` int(11) unsigned DEFAULT NULL COMMENT '已读时间',
  `outdated` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已失效',
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`,`type`,`readed`,`outdated`),
  KEY `detail_id` (`detail_id`,`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀-关于我的' AUTO_INCREMENT=509434 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_event_sms`
--

CREATE TABLE IF NOT EXISTS `cs_event_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `event_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事件最新发生时间',
  `last_deal_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后处理时间',
  `sms_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '短信计数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  KEY `event_time` (`event_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='好友事件的短信记录' AUTO_INCREMENT=20096 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_event_sms_guid`
--

CREATE TABLE IF NOT EXISTS `cs_event_sms_guid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guid` varchar(100) NOT NULL DEFAULT '0' COMMENT '用户id',
  `event_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '事件最新发生时间',
  `last_deal_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后处理时间',
  `sms_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '短信计数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`guid`),
  KEY `event_time` (`event_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='好友事件的短信记录' AUTO_INCREMENT=89250 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_feedback`
--

CREATE TABLE IF NOT EXISTS `cs_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(64) NOT NULL DEFAULT '' COMMENT '用户uid信息，若未登录，则为guid',
  `is_reg` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否注册用户:1是，0否',
  `client_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '客户端类型',
  `phone_model` varchar(255) NOT NULL DEFAULT '',
  `phone_os` varchar(255) NOT NULL DEFAULT '',
  `version` varchar(32) NOT NULL DEFAULT '' COMMENT '客户端版本号',
  `mongo_id` varchar(64) NOT NULL DEFAULT '',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交时间',
  `refresh_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  `state` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '反馈状态,0:待反馈,1解决中,2已解决',
  PRIMARY KEY (`id`),
  KEY `mongo_id` (`mongo_id`),
  KEY `user_id` (`user_id`,`create_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀用户反馈' AUTO_INCREMENT=3006 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_feedback_sms`
--

CREATE TABLE IF NOT EXISTS `cs_feedback_sms` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(32) NOT NULL DEFAULT '' COMMENT '手机号',
  `zone_code` smallint(5) unsigned NOT NULL DEFAULT '86' COMMENT '国家码',
  `mongo_id` varchar(64) NOT NULL DEFAULT '' COMMENT '反馈的mongoid',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `mobile` (`mobile`,`zone_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='反馈短信' AUTO_INCREMENT=892 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_gift_history`
--

CREATE TABLE IF NOT EXISTS `cs_gift_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '送礼物者的uid',
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀id',
  `gift_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '礼物id',
  `gift_num` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '礼物数目',
  `event_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '送礼日期',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_show` (`show_id`,`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='赠送礼物信息' AUTO_INCREMENT=262575 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_history`
--

CREATE TABLE IF NOT EXISTS `cs_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ring_name` varchar(255) NOT NULL DEFAULT '' COMMENT '铃声名字',
  `ring_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '铃声mime',
  `ring_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '铃音url',
  `ring_duration` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '铃音时长',
  `ring_refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃声引用id',
  `image_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '照片mime',
  `image_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '照片url',
  `image_refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片引用id',
  `video_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '视频mime',
  `video_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '视频url',
  `video_duration` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '视频时长',
  `video_snap_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '视频截图mime',
  `video_snap_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '视频截图url',
  `video_refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '视频引用id',
  `label` varchar(255) NOT NULL DEFAULT '' COMMENT '来电秀标注',
  `refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀引用id',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀创建时间',
  `creator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者uid',
  `owner` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '拥有者uid',
  `gift_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '礼物数目',
  `nice_coefficient` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '精品指数',
  `nice_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '射精时间',
  `hot_score` int(11) unsigned NOT NULL DEFAULT '30' COMMENT '热门分数',
  `client_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '设置平台',
  `is_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已被删除0否1是',
  `access_ctrl_range` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '可见范围:0:public-全部可见，1:protect-只有通过手机号可见，2：friendly-只有指定的人可见,3:private:只有自己可见，默认0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模板秀id',
  `template_similarity` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '模板秀相似度',
  `forwarded_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '转发秀id',
  PRIMARY KEY (`id`),
  KEY `create_time` (`create_time`),
  KEY `owner` (`owner`),
  KEY `hot_score` (`hot_score`),
  KEY `update_time` (`update_time`),
  KEY `nice_time` (`nice_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀记录' AUTO_INCREMENT=729558 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_mass_log`
--

CREATE TABLE IF NOT EXISTS `cs_mass_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint` varchar(255) DEFAULT NULL COMMENT '指纹',
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对应的来电秀id',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否有效,0失效，1有效',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '海选秀登记时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fp` (`fingerprint`),
  KEY `show` (`show_id`),
  KEY `time_status` (`create_time`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='海选秀记录' AUTO_INCREMENT=207815 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_personalty_image`
--

CREATE TABLE IF NOT EXISTS `cs_personalty_image` (
  `rid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(64) NOT NULL DEFAULT '' COMMENT '定义码',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '图片mime',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '图片下载地址',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  PRIMARY KEY (`rid`),
  UNIQUE KEY `iden_code` (`identifier`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='我的图片库' AUTO_INCREMENT=325826 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_personalty_ring`
--

CREATE TABLE IF NOT EXISTS `cs_personalty_ring` (
  `rid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(64) NOT NULL DEFAULT '' COMMENT '定义码',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '铃声名字',
  `mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '铃声mime',
  `duration` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '铃声时长',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '铃声下载地址',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  PRIMARY KEY (`rid`),
  UNIQUE KEY `iden_code` (`identifier`),
  KEY `uid` (`uid`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='我的铃声库' AUTO_INCREMENT=351485 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_personalty_video`
--

CREATE TABLE IF NOT EXISTS `cs_personalty_video` (
  `rid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(64) NOT NULL DEFAULT '' COMMENT '定义码',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '视频mime',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '视频下载地址',
  `duration` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '视频时长',
  `snap_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '视频截图mime',
  `snap_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '视频截图下载地址',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  PRIMARY KEY (`rid`),
  UNIQUE KEY `iden_code` (`identifier`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='我的视频库' AUTO_INCREMENT=123798 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_recommend`
--

CREATE TABLE IF NOT EXISTS `cs_recommend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀id',
  `is_sys` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否系统秀',
  `share_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分享日期',
  PRIMARY KEY (`id`),
  UNIQUE KEY `showid` (`show_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀分享表' AUTO_INCREMENT=61 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_gift`
--

CREATE TABLE IF NOT EXISTS `cs_resource_gift` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '礼物id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '礼物名字',
  `class` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '礼物类别',
  `nice` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否精品,1是0不是',
  `remark` varchar(1024) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='礼物类型列表' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_image`
--

CREATE TABLE IF NOT EXISTS `cs_resource_image` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '图片名字',
  `mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '图片mime',
  `pix_x` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '像素宽',
  `pix_y` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '像素高',
  `tag` varchar(255) NOT NULL DEFAULT '' COMMENT '图片tag',
  `nice` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否精品，1是0不是',
  `size` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片字节数',
  `author` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传者uid',
  `refcount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被使用次数',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '图片下载地址',
  `remark` varchar(1024) DEFAULT NULL COMMENT '备注',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间戳',
  `refresh_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `approve_stat` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态:0未审核,1重新审核，2已审核',
  `approver` int(11) unsigned DEFAULT NULL COMMENT '批准者uid',
  `approve_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '批准日期',
  `is_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否在回收站中，0否1是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='图片资源' AUTO_INCREMENT=28078 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_image_tag`
--

CREATE TABLE IF NOT EXISTS `cs_resource_image_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `cover` varchar(1024) NOT NULL DEFAULT '' COMMENT '封面url',
  `sequence` smallint(5) unsigned NOT NULL DEFAULT '65535' COMMENT 'tag显示顺序',
  `num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片个数',
  `approved_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已审核图片个数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `sequence` (`sequence`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='图片资源tag' AUTO_INCREMENT=182 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_image_tag_link`
--

CREATE TABLE IF NOT EXISTS `cs_resource_image_tag_link` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `image_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片id',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `tag_img` (`tag_name`,`image_id`),
  KEY `image` (`image_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='图片资源tag关联表' AUTO_INCREMENT=30111 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_ring`
--

CREATE TABLE IF NOT EXISTS `cs_resource_ring` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '铃声名字',
  `mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '铃音mime',
  `tag` varchar(255) NOT NULL DEFAULT '' COMMENT '铃音tag',
  `nice` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否精品，1是0不是',
  `duration` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '铃音时长',
  `size` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃音字节数',
  `author` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传者uid',
  `singer` varchar(32) NOT NULL DEFAULT '' COMMENT '演唱者',
  `refcount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被使用次数',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '铃音下载地址',
  `remark` varchar(1024) DEFAULT NULL COMMENT '备注',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传时间戳',
  `refresh_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `approve_stat` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态:0未审核,1重新审核，2已审核',
  `approver` int(11) unsigned DEFAULT NULL COMMENT '批准者',
  `approve_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '批准时间戳',
  `is_deleted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否在回收站中，0否1是',
  PRIMARY KEY (`id`),
  KEY `singer` (`singer`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='铃音资源' AUTO_INCREMENT=26963 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_ring_tag`
--

CREATE TABLE IF NOT EXISTS `cs_resource_ring_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `cover` varchar(1024) NOT NULL DEFAULT '' COMMENT '封面url',
  `sequence` smallint(5) unsigned NOT NULL DEFAULT '65535' COMMENT 'tag显示顺序',
  `num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃声个数',
  `approved_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已审核铃声个数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `sequence` (`sequence`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='铃声资源tag' AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_ring_tag_link`
--

CREATE TABLE IF NOT EXISTS `cs_resource_ring_tag_link` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `ring_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃声id',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `tag_img` (`tag_name`,`ring_id`),
  KEY `ring` (`ring_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='铃声资源tag关联表' AUTO_INCREMENT=26876 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_ring_topic`
--

CREATE TABLE IF NOT EXISTS `cs_resource_ring_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '专题名',
  `cover` varchar(1024) NOT NULL DEFAULT '' COMMENT '专题封面',
  `desc` varchar(1024) NOT NULL DEFAULT '' COMMENT '专题描述',
  `num` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '资源数',
  `sequence` int(11) unsigned NOT NULL DEFAULT '65535' COMMENT '显示顺序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='铃声专题列表' AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_ring_topic_link`
--

CREATE TABLE IF NOT EXISTS `cs_resource_ring_topic_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '专题id',
  `ring_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃声资源id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic_ring` (`topic_id`,`ring_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='铃声专题内容' AUTO_INCREMENT=54 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_show`
--

CREATE TABLE IF NOT EXISTS `cs_resource_show` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '来电秀名字',
  `tag` varchar(255) NOT NULL DEFAULT '' COMMENT '来电秀tag',
  `nice` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否精品，1是0不是',
  `image_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片id',
  `ring_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃声id',
  `label` varchar(255) NOT NULL DEFAULT '' COMMENT '文字描述',
  `author` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作者uid',
  `refcount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被使用次数',
  `remark` varchar(1024) DEFAULT '' COMMENT '备注',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `refresh_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `approve_stat` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态:0未审核,1重新审核，2已审核',
  `approver` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核者uid',
  `approve_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间戳',
  `xiaomi_show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '小秘的秀的id',
  PRIMARY KEY (`id`),
  KEY `xiaomi_show_id` (`xiaomi_show_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀资源' AUTO_INCREMENT=56 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_show_tag`
--

CREATE TABLE IF NOT EXISTS `cs_resource_show_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `cover` varchar(1024) NOT NULL DEFAULT '' COMMENT '封面url',
  `sequence` smallint(5) unsigned NOT NULL DEFAULT '65535' COMMENT 'tag显示顺序',
  `num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀个数',
  `approved_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已审核来电秀个数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `sequence` (`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='来电秀资源tag' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_resource_show_tag_link`
--

CREATE TABLE IF NOT EXISTS `cs_resource_show_tag_link` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀id',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `tag_img` (`tag_name`,`show_id`),
  KEY `show` (`show_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='来电秀tag关联表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_share`
--

CREATE TABLE IF NOT EXISTS `cs_share` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀id',
  `type` varchar(16) NOT NULL DEFAULT '' COMMENT '分享类型',
  `share_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分享者uid',
  `guid` varchar(64) NOT NULL DEFAULT '' COMMENT '该分享对应的guid',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分享时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `guid` (`guid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀分享相关分配' AUTO_INCREMENT=732486 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_sms`
--

CREATE TABLE IF NOT EXISTS `cs_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发送者uid',
  `receiver_zonecode` smallint(5) unsigned NOT NULL DEFAULT '86' COMMENT '接收方手机国家码',
  `receiver_mobile` varchar(32) NOT NULL DEFAULT '' COMMENT '接收方手机',
  `timestamp` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '发送时间',
  `guid` varchar(64) NOT NULL DEFAULT '' COMMENT 'url中的code',
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_code` (`guid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来电秀短信记录' AUTO_INCREMENT=332009 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_sysmsg`
--

CREATE TABLE IF NOT EXISTS `cs_sysmsg` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `creator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者，一般为管理员',
  `receiver_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0:广播,1组播,2单播',
  `mongo_id` varchar(64) NOT NULL DEFAULT '' COMMENT '实际存储内容id',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='系统消息' AUTO_INCREMENT=11710 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_template`
--

CREATE TABLE IF NOT EXISTS `cs_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint` varchar(64) NOT NULL DEFAULT '' COMMENT '指纹，为video_url|image_url|ring_url的md5',
  `ring_name` varchar(255) NOT NULL DEFAULT '' COMMENT '铃声名字',
  `ring_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '铃声mime',
  `ring_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '铃音url',
  `ring_duration` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '铃音时长',
  `ring_refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '铃声引用id',
  `image_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '照片mime',
  `image_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '照片url',
  `image_refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图片资源id',
  `video_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '视频mime',
  `video_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '视频url',
  `video_duration` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '视频时长',
  `video_snap_mime` varchar(64) NOT NULL DEFAULT 'application/octet-stream' COMMENT '视频截图mime',
  `video_snap_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '视频截图url',
  `video_refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '视频引用id',
  `label` varchar(255) NOT NULL DEFAULT '' COMMENT '来电秀标注',
  `refid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来电秀引用id',
  `creator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者uid',
  `tag` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `approve_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '设为模板秀的时间',
  `approver` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作者',
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来源秀id',
  `refcount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被使用次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fingerprint` (`fingerprint`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='模板秀记录' AUTO_INCREMENT=44 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_template_tag`
--

CREATE TABLE IF NOT EXISTS `cs_template_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `cover` varchar(1024) NOT NULL DEFAULT '' COMMENT 'tag封面',
  `sequence` mediumint(8) unsigned NOT NULL DEFAULT '65535' COMMENT 'tag显示顺序',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT '模板秀总个数',
  PRIMARY KEY (`id`),
  KEY `sequence` (`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模板秀标签' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_template_tag_link`
--

CREATE TABLE IF NOT EXISTS `cs_template_tag_link` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'tag名',
  `show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模板秀id',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `t_s` (`tag_name`,`show_id`),
  KEY `show` (`show_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='模板秀标签关联表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `cs_user`
--

CREATE TABLE IF NOT EXISTS `cs_user` (
  `owner` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '拥有者uid',
  `private_create` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否只允许自己设置来电秀,默认0即为否',
  `cur_show_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '当前来电秀的id',
  `refresh_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '当前秀的时间',
  `reg_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '初次创建时间',
  `reg_client_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '注册平台',
  PRIMARY KEY (`owner`),
  KEY `cur_show_id` (`cur_show_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='来电秀用户';

-- --------------------------------------------------------

--
-- 表的结构 `deals`
--

CREATE TABLE IF NOT EXISTS `deals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deal_id` char(36) NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `longitude` char(50) NOT NULL,
  `latitude` char(50) NOT NULL,
  `price` varchar(200) NOT NULL,
  `created_at` int(10) NOT NULL,
  `modified_at` int(10) NOT NULL,
  `private` tinyint(1) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `remark` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `longitude` (`longitude`,`latitude`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=323 ;

-- --------------------------------------------------------

--
-- 表的结构 `department`
--

CREATE TABLE IF NOT EXISTS `department` (
  `id` int(11) NOT NULL,
  `cid` int(11) unsigned DEFAULT NULL COMMENT '大学id',
  `department` varchar(25) DEFAULT NULL COMMENT '院系名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='院系字典';

-- --------------------------------------------------------

--
-- 表的结构 `diary`
--

CREATE TABLE IF NOT EXISTS `diary` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日记ID',
  `classid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作者ID(aid=uid)原创',
  `privacy` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型(0:任何人可见,1:仅好友可见,2:凭密码访问,3:私密日记,4:指定好友可见,5:仅指定群)',
  `dtype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型(0:转载,1:原创)',
  `subject` varchar(50) NOT NULL COMMENT '标题',
  `summary` varchar(255) NOT NULL COMMENT '摘要',
  `password` varchar(32) DEFAULT NULL COMMENT '密码',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加日期',
  `allowshare` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否允许好友转帖0为允许1不允许',
  `commentnums` smallint(6) NOT NULL DEFAULT '0' COMMENT '评论数',
  `draft` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否草稿',
  `appoint` varchar(55) DEFAULT NULL,
  `appoint_group` varchar(54) NOT NULL,
  `feed_id` char(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classid` (`uid`,`classid`,`privacy`),
  KEY `draft` (`draft`),
  KEY `uid` (`uid`,`classid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='日记' AUTO_INCREMENT=4032 ;

-- --------------------------------------------------------

--
-- 表的结构 `diary_class`
--

CREATE TABLE IF NOT EXISTS `diary_class` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `classname` char(10) NOT NULL COMMENT '分类名称',
  `diarynums` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '日记数',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加日期',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='日记分类' AUTO_INCREMENT=278 ;

-- --------------------------------------------------------

--
-- 表的结构 `diary_fields`
--

CREATE TABLE IF NOT EXISTS `diary_fields` (
  `did` int(11) unsigned NOT NULL,
  `frid` int(11) unsigned NOT NULL COMMENT '转自那篇日记的ID',
  `quoturl` varchar(255) DEFAULT NULL COMMENT '转载网络的URL',
  `content` mediumtext COMMENT '日记内容',
  KEY `fk_diaryfields_diary1` (`did`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日记内容表';

-- --------------------------------------------------------

--
-- 表的结构 `diary_friends`
--

CREATE TABLE IF NOT EXISTS `diary_friends` (
  `did` int(11) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL COMMENT '好友id',
  `uid` int(10) unsigned NOT NULL COMMENT '日记作者ID',
  KEY `did` (`did`),
  KEY `fid` (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日记提到的好友表';

-- --------------------------------------------------------

--
-- 表的结构 `diary_read`
--

CREATE TABLE IF NOT EXISTS `diary_read` (
  `did` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `addtime` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `diduid` (`did`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `duplicate_check`
--

CREATE TABLE IF NOT EXISTS `duplicate_check` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content_md5` varchar(32) NOT NULL,
  `uid` int(11) unsigned NOT NULL,
  `created` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `encrypted_mining`
--

CREATE TABLE IF NOT EXISTS `encrypted_mining` (
  `user_id` int(10) unsigned NOT NULL,
  `encrypt_str` char(32) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`,`encrypt_str`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='加密串挖掘用';

-- --------------------------------------------------------

--
-- 表的结构 `event`
--

CREATE TABLE IF NOT EXISTS `event` (
  `eid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `gid` int(11) unsigned NOT NULL,
  `pid` int(11) unsigned NOT NULL DEFAULT '1',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '活动类型（默认值1表示其他类型）',
  `organizer` int(11) unsigned NOT NULL COMMENT '活动组织者ID',
  `title` varchar(30) NOT NULL COMMENT '活动标题',
  `summary` text NOT NULL COMMENT '活动摘要',
  `content` text NOT NULL COMMENT '活动内容',
  `start_time` int(10) unsigned NOT NULL COMMENT '活动开始时间',
  `end_time` int(10) unsigned NOT NULL COMMENT '活动结束时间',
  `image` text NOT NULL,
  `assemble_location` varchar(500) NOT NULL COMMENT '集合地点',
  `event_location` varchar(500) NOT NULL COMMENT '活动地点',
  `city` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '活动所处的城市',
  `create_time` int(10) NOT NULL COMMENT '活动创建时间',
  `update_time` int(10) NOT NULL,
  `private` tinyint(1) NOT NULL,
  `fee` varchar(200) NOT NULL COMMENT '是否收费',
  `deadline` int(10) NOT NULL,
  `apply_desc` text NOT NULL,
  `apply_doc` text NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '活动状态',
  PRIMARY KEY (`eid`),
  KEY `organizer` (`organizer`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='活动' AUTO_INCREMENT=49 ;

-- --------------------------------------------------------

--
-- 表的结构 `event_apply_doc`
--

CREATE TABLE IF NOT EXISTS `event_apply_doc` (
  `did` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '材料id',
  `eid` int(11) unsigned NOT NULL COMMENT '活动id',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '材料名称',
  PRIMARY KEY (`did`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='活动报名材料' AUTO_INCREMENT=73 ;

-- --------------------------------------------------------

--
-- 表的结构 `event_image`
--

CREATE TABLE IF NOT EXISTS `event_image` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `eid` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `title` varchar(400) NOT NULL,
  `url` varchar(400) NOT NULL,
  `width` smallint(6) NOT NULL,
  `height` smallint(6) NOT NULL,
  `cover` tinyint(1) NOT NULL,
  `banner` tinyint(1) NOT NULL DEFAULT '0',
  `created` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `eid` (`eid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=162 ;

-- --------------------------------------------------------

--
-- 表的结构 `event_user`
--

CREATE TABLE IF NOT EXISTS `event_user` (
  `eid` int(11) unsigned NOT NULL COMMENT '活动id',
  `pid` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL COMMENT '活动报名者id',
  `name` varchar(200) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `apply_type` tinyint(1) unsigned NOT NULL COMMENT '报名选择(1:参加,2:不参加,3:感兴趣)',
  `apply_time` int(10) unsigned NOT NULL COMMENT '报名时间',
  `apply_doc` text NOT NULL,
  `invite_by` int(10) unsigned NOT NULL DEFAULT '0',
  `grade` tinyint(1) NOT NULL,
  KEY `IDX_UID_APPLY` (`eid`,`apply_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='活动报名成员';

-- --------------------------------------------------------

--
-- 表的结构 `event_user_apply_doc`
--

CREATE TABLE IF NOT EXISTS `event_user_apply_doc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `did` int(11) unsigned NOT NULL COMMENT '材料id',
  `eid` int(11) unsigned NOT NULL COMMENT '活动id',
  `uid` int(11) unsigned NOT NULL COMMENT '用户id',
  `pid` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='活动用户报名材料' AUTO_INCREMENT=83 ;

-- --------------------------------------------------------

--
-- 表的结构 `family_names`
--

CREATE TABLE IF NOT EXISTS `family_names` (
  `x` char(10) NOT NULL,
  UNIQUE KEY `x` (`x`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `feed`
--

CREATE TABLE IF NOT EXISTS `feed` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mix_id` int(10) unsigned NOT NULL,
  `feed_id` varchar(60) NOT NULL,
  `typeid` smallint(2) NOT NULL,
  `rt_status_id` varchar(60) NOT NULL,
  `owner_uid` int(10) unsigned NOT NULL,
  `created_at` int(10) NOT NULL,
  `last_updated` int(14) NOT NULL,
  `source` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mix_id` (`mix_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `content` text NOT NULL,
  `addtime` int(11) NOT NULL,
  `client_id` int(4) NOT NULL DEFAULT '0',
  `contact` varchar(64) NOT NULL,
  `kind` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=148 ;

-- --------------------------------------------------------

--
-- 表的结构 `feedkey`
--

CREATE TABLE IF NOT EXISTS `feedkey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feedtype` varchar(30) NOT NULL COMMENT '动态模板名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7226 ;

-- --------------------------------------------------------

--
-- 表的结构 `flea_market`
--

CREATE TABLE IF NOT EXISTS `flea_market` (
  `object_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `price` decimal(9,3) NOT NULL DEFAULT '0.000',
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(20) DEFAULT '',
  `category` tinyint(1) NOT NULL DEFAULT '1' COMMENT '信息种类：1二手物品,2租房,3售房,4团购',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '信息类型：1供,2求',
  `trading_places` varchar(60) NOT NULL DEFAULT '' COMMENT '交易地点',
  `city` varchar(60) DEFAULT '' COMMENT '所在城市',
  `privacy` tinyint(1) NOT NULL DEFAULT '0' COMMENT '查看权限：1好友，2群友，4同公司，8同城 (2^n形式存储)',
  `has_pic` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有图片：0没有，1有',
  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '发布时间',
  `modify_at` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0，1过期，2取消，3完成交易',
  `status_at` int(10) NOT NULL DEFAULT '0' COMMENT '发布者设置状态时间：如取消时间，完成交易时间',
  `longitude` float(9,6) DEFAULT '0.000000' COMMENT '经度',
  `latitude` float(9,6) DEFAULT '0.000000' COMMENT '纬度',
  `client` tinyint(1) DEFAULT '0' COMMENT '来自那个客户端PC 手机',
  `brief` varchar(255) DEFAULT NULL COMMENT '截取description的前50字符',
  `description` text,
  PRIMARY KEY (`object_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='跳蚤市场' AUTO_INCREMENT=191 ;

-- --------------------------------------------------------

--
-- 表的结构 `forget_pass`
--

CREATE TABLE IF NOT EXISTS `forget_pass` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(20) NOT NULL,
  `zone_code` varchar(20) NOT NULL,
  `password` varchar(50) NOT NULL,
  `create_date` int(10) NOT NULL,
  `verify_date` int(10) NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`,`zone_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11018 ;

-- --------------------------------------------------------

--
-- 表的结构 `friends`
--

CREATE TABLE IF NOT EXISTS `friends` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `fid` int(10) unsigned NOT NULL COMMENT '好友ID',
  `dateline` int(10) DEFAULT NULL COMMENT '成为好友时间',
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_FID` (`fid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25881 ;

-- --------------------------------------------------------

--
-- 表的结构 `fs_fileentry`
--

CREATE TABLE IF NOT EXISTS `fs_fileentry` (
  `fid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `oid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '文件源id',
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `uid` bigint(20) unsigned NOT NULL COMMENT '用户id',
  `type` tinyint(3) unsigned NOT NULL COMMENT '1为文件，2为文件夹',
  `path` varchar(255) NOT NULL COMMENT '文件位置',
  `created_at` int(10) unsigned NOT NULL COMMENT '文件创建时间',
  `mtime` int(10) unsigned NOT NULL COMMENT '文件最后修改时间',
  `desc` text NOT NULL COMMENT '描述',
  `file_count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '包含的文件数量，如果是文件固定为1',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '大小，如果是文件夹表示文件夹内部包含文件的总大小',
  `mime` varchar(128) NOT NULL COMMENT '文件类型',
  `md5` char(32) NOT NULL DEFAULT '' COMMENT '文件md5',
  `ext` varchar(10) NOT NULL COMMENT '扩展名',
  `cid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '分类id(0动态分享,1私聊)',
  `ctrl_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '文件解析类型(0文件,1音频)',
  `thumb_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '缩略图图片的id',
  PRIMARY KEY (`fid`),
  UNIQUE KEY `uid_type_path` (`uid`,`type`,`path`),
  KEY `ext` (`ext`),
  KEY `oid` (`oid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='网盘' AUTO_INCREMENT=1537153 ;

-- --------------------------------------------------------

--
-- 表的结构 `fs_fileentry_sharelog`
--

CREATE TABLE IF NOT EXISTS `fs_fileentry_sharelog` (
  `fid` bigint(20) unsigned NOT NULL COMMENT '资源id',
  `objid` varchar(50) NOT NULL COMMENT '动态id',
  `ctime` int(10) unsigned NOT NULL COMMENT '分享时间',
  `mix_id` varchar(30) NOT NULL COMMENT '分享对象',
  `owner_uid` int(10) unsigned NOT NULL COMMENT '动态发布者id',
  PRIMARY KEY (`fid`,`objid`),
  KEY `ctime` (`ctime`),
  KEY `mix_id` (`mix_id`),
  KEY `owner_uid` (`owner_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `fs_photo`
--

CREATE TABLE IF NOT EXISTS `fs_photo` (
  `pid` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `oid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '来源图片',
  `uid` bigint(20) unsigned NOT NULL COMMENT '所属用户id',
  `created_at` int(10) unsigned NOT NULL COMMENT '上传时间',
  `mtime` int(10) unsigned NOT NULL COMMENT '最后修改时间',
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '文件名',
  `title` varchar(128) NOT NULL DEFAULT '' COMMENT '标题',
  `desc` text NOT NULL COMMENT '描述',
  `mime` varchar(128) NOT NULL COMMENT '原始图片mime',
  `size` int(10) unsigned NOT NULL COMMENT '原始图片字节数',
  `md5` char(32) NOT NULL COMMENT '原始图片MD5',
  `direction` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '相对原始图片顺时针0度,90度,180度,270度编号为(0,1,2,3)',
  `width` smallint(5) unsigned NOT NULL COMMENT '长',
  `height` smallint(5) unsigned NOT NULL COMMENT '宽',
  `is_animated` tinyint(3) unsigned NOT NULL COMMENT '是否动画图片',
  `cid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '分类id(0相册照片,1头像照片,2联系人照片,3日记图片,4生活信息图片,5私聊图片)',
  `ctrl_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '图片解析类型(0 photo,1 avatar)',
  PRIMARY KEY (`pid`),
  KEY `uid` (`uid`),
  KEY `cid` (`cid`),
  KEY `created_at` (`created_at`),
  KEY `md5_direction` (`md5`,`direction`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='图库' AUTO_INCREMENT=9802137 ;

-- --------------------------------------------------------

--
-- 表的结构 `fs_photo_avatar`
--

CREATE TABLE IF NOT EXISTS `fs_photo_avatar` (
  `uid` bigint(20) unsigned NOT NULL COMMENT '用户id',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '头像截图id',
  `oid` int(10) unsigned NOT NULL COMMENT '与photo表的mtime一致',
  `mtime` int(10) unsigned NOT NULL COMMENT '与photo表的mtime一致',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `fs_photo_sharelog`
--

CREATE TABLE IF NOT EXISTS `fs_photo_sharelog` (
  `pid` bigint(20) unsigned NOT NULL COMMENT '照片id',
  `objid` varchar(50) NOT NULL COMMENT '动态id',
  `ctime` int(10) unsigned NOT NULL COMMENT '时间',
  `mix_id` varchar(30) NOT NULL COMMENT '分享对象',
  `owner_uid` int(10) unsigned NOT NULL COMMENT '动态发布者id',
  PRIMARY KEY (`pid`,`objid`),
  KEY `ctime` (`ctime`),
  KEY `mix_id` (`mix_id`),
  KEY `owner_uid` (`owner_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='记录动态与照片的关系';

-- --------------------------------------------------------

--
-- 表的结构 `fs_temp`
--

CREATE TABLE IF NOT EXISTS `fs_temp` (
  `upload_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '临时上传id',
  `size` int(10) unsigned NOT NULL COMMENT '文件长度',
  `md5` char(32) NOT NULL COMMENT 'MD5',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '文件类型（1音频，2图片，0文件）',
  `uid` bigint(20) unsigned NOT NULL COMMENT '用户id',
  `cid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '分类',
  `basedir` varchar(255) NOT NULL DEFAULT '' COMMENT '存放路径',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
  PRIMARY KEY (`upload_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=351951 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_addresses`
--

CREATE TABLE IF NOT EXISTS `gcp_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `country` varchar(255) DEFAULT NULL COMMENT '值',
  `postal` varchar(20) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `street` text,
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='地址' AUTO_INCREMENT=1451 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_avatars`
--

CREATE TABLE IF NOT EXISTS `gcp_avatars` (
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '联系人ID',
  `avatar` text COMMENT '头像',
  `space` smallint(1) DEFAULT '0' COMMENT '是否SNS头像',
  `dateline` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`gcid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='联系人列表';

-- --------------------------------------------------------

--
-- 表的结构 `gcp_contacts`
--

CREATE TABLE IF NOT EXISTS `gcp_contacts` (
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '联系人ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `formatted_name` varchar(75) NOT NULL COMMENT '姓名',
  `phonetic` varchar(255) NOT NULL COMMENT '名字拼音',
  `given_name` varchar(32) NOT NULL COMMENT '名',
  `middle_name` varchar(32) NOT NULL COMMENT '中间名',
  `family_name` varchar(32) NOT NULL COMMENT '姓',
  `prefix` varchar(32) DEFAULT NULL COMMENT '前缀',
  `suffix` varchar(32) DEFAULT NULL COMMENT '后缀',
  `organization` varchar(255) DEFAULT NULL COMMENT '公司',
  `department` varchar(255) DEFAULT NULL COMMENT '部门',
  `note` text COMMENT '备注',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `title` varchar(75) DEFAULT NULL COMMENT '职称(最多75个字符)',
  `nickname` varchar(75) DEFAULT NULL COMMENT '昵称(最多75个字符)',
  `sort` varchar(20) NOT NULL COMMENT '姓名首字母',
  `created` int(11) DEFAULT '0' COMMENT '创建时间',
  `modified` int(11) DEFAULT '0' COMMENT '修改时间',
  `fid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '对应用户ID',
  `deleted` smallint(1) DEFAULT '0' COMMENT '是否删除',
  PRIMARY KEY (`gcid`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='联系人' AUTO_INCREMENT=4821 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_emails`
--

CREATE TABLE IF NOT EXISTS `gcp_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_VALUE` (`value`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='邮箱' AUTO_INCREMENT=3267 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_events`
--

CREATE TABLE IF NOT EXISTS `gcp_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='日期' AUTO_INCREMENT=36 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_ims`
--

CREATE TABLE IF NOT EXISTS `gcp_ims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `protocol` varchar(50) NOT NULL COMMENT '协议',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='即时通讯' AUTO_INCREMENT=5480 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_relations`
--

CREATE TABLE IF NOT EXISTS `gcp_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='群组关系人' AUTO_INCREMENT=36 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_tels`
--

CREATE TABLE IF NOT EXISTS `gcp_tels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  `pref` smallint(6) NOT NULL DEFAULT '0' COMMENT '是否主手机号',
  `city` varchar(30) NOT NULL COMMENT '归属地',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_VALUE` (`value`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='电话' AUTO_INCREMENT=8575 ;

-- --------------------------------------------------------

--
-- 表的结构 `gcp_urls`
--

CREATE TABLE IF NOT EXISTS `gcp_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID',
  `gcid` int(11) unsigned DEFAULT NULL COMMENT '联系人ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  PRIMARY KEY (`id`),
  KEY `IDX_CID` (`gcid`),
  KEY `IDX_GID` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='网址' AUTO_INCREMENT=5591 ;

-- --------------------------------------------------------

--
-- 表的结构 `gift`
--

CREATE TABLE IF NOT EXISTS `gift` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gsid` int(11) DEFAULT NULL COMMENT '礼物ID',
  `uid` int(11) unsigned DEFAULT NULL,
  `tid` int(11) unsigned DEFAULT NULL,
  `mark` int(11) DEFAULT '0',
  `privacy` tinyint(4) DEFAULT '0' COMMENT '赠送方式0,实名;1,悄悄地送;2,匿名贈送',
  `gstype` tinyint(4) DEFAULT NULL COMMENT '礼物类型，用于分类统计',
  `scene` varchar(255) DEFAULT NULL COMMENT '送礼场景',
  `msg` varchar(255) DEFAULT NULL COMMENT '赠礼留言',
  `gtime` int(11) DEFAULT NULL COMMENT '赠送时间',
  `ctime` int(11) DEFAULT NULL COMMENT '写数据表时间',
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`,`mark`,`gtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `gift_scene`
--

CREATE TABLE IF NOT EXISTS `gift_scene` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) DEFAULT NULL,
  `scene` varchar(255) DEFAULT NULL COMMENT '场景',
  `think` varchar(255) DEFAULT NULL COMMENT '帮我想想',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='赠送礼物场景' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `gift_store`
--

CREATE TABLE IF NOT EXISTS `gift_store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gold` tinyint(4) DEFAULT '0',
  `gtype` tinyint(4) DEFAULT '0',
  `gname` varchar(100) DEFAULT NULL,
  `sicon` varchar(255) DEFAULT NULL COMMENT '小图超链接',
  `bicon` varchar(255) DEFAULT NULL COMMENT '大图超链接',
  `isflash` tinyint(4) DEFAULT '0' COMMENT '是否是魔法礼物',
  `tips` text COMMENT 'AD礼物备用',
  `rules` tinyint(4) DEFAULT NULL COMMENT 'AD礼物规则ID标识，给程序调用，备用',
  `isdel` tinyint(4) DEFAULT '0' COMMENT '删除标识 1为己删除',
  `ctime` int(11) DEFAULT NULL COMMENT '创建礼物的时间',
  `hot` int(11) DEFAULT NULL COMMENT '礼物人气',
  PRIMARY KEY (`id`),
  KEY `fk_gift_store_gift1` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='礼物表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `group`
--

CREATE TABLE IF NOT EXISTS `group` (
  `gid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '群组id，自动递增',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `gname` varchar(64) NOT NULL COMMENT '群组名字',
  `introduction` varchar(300) NOT NULL COMMENT '简介',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  `modify_time` int(10) unsigned NOT NULL COMMENT '修改时间',
  `creator_id` int(10) unsigned NOT NULL COMMENT '创建者的ID',
  `member_number` int(10) NOT NULL DEFAULT '0' COMMENT '群组人数',
  `notice` varchar(200) NOT NULL COMMENT '群公告',
  `privacy` tinyint(1) NOT NULL DEFAULT '1' COMMENT '群类型(1:公开群,2:私密群)',
  `master_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群主id',
  `belong_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '群归属类型（0普通群, 1公司群, 2学校群）',
  `belong_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '归属公司或者学校id,默认为0',
  `feed_id` char(32) DEFAULT NULL,
  `group_feed_id` char(32) DEFAULT NULL,
  `allow_join` tinyint(1) NOT NULL DEFAULT '0' COMMENT '公开群是否允许自由加入，无需管理员审核(0不允许，1允许)',
  `verify_tip` varchar(100) NOT NULL COMMENT '验证提示',
  PRIMARY KEY (`gid`),
  KEY `createid` (`creator_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='群组' AUTO_INCREMENT=17222 ;

-- --------------------------------------------------------

--
-- 表的结构 `group_apply`
--

CREATE TABLE IF NOT EXISTS `group_apply` (
  `uid` int(10) unsigned NOT NULL COMMENT '用户id',
  `gid` int(10) unsigned NOT NULL COMMENT '群id',
  `reason` varchar(255) NOT NULL COMMENT '申请加入的理由',
  `time` int(10) unsigned NOT NULL COMMENT '申请的时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '群申请处理状态(0:未处理，1:已处理)',
  `manager_uid` int(11) NOT NULL DEFAULT '0' COMMENT '群申请处理管理员id',
  PRIMARY KEY (`uid`,`gid`),
  KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `group_ignore`
--

CREATE TABLE IF NOT EXISTS `group_ignore` (
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `ignored_ids` text CHARACTER SET latin1 COMMENT '忽略群ID',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- 表的结构 `group_invite`
--

CREATE TABLE IF NOT EXISTS `group_invite` (
  `gid` int(10) unsigned NOT NULL COMMENT '邀请加入的群',
  `uid` int(10) unsigned NOT NULL COMMENT '被邀请人',
  `muid` int(10) unsigned NOT NULL COMMENT '邀请人',
  PRIMARY KEY (`gid`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='邀请加入表';

-- --------------------------------------------------------

--
-- 表的结构 `group_invite_register`
--

CREATE TABLE IF NOT EXISTS `group_invite_register` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(32) NOT NULL COMMENT '邀请码',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '邀请链接有效状态(0有效,1无效)',
  `invite_uid` int(10) NOT NULL COMMENT '邀请人id',
  `invite_realname` varchar(30) NOT NULL COMMENT '邀请人姓名',
  `gid` int(10) NOT NULL DEFAULT '0' COMMENT '群组id',
  `invite_time` int(10) NOT NULL COMMENT '邀请码生成时间',
  PRIMARY KEY (`id`),
  KEY `invite_code` (`invite_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=742 ;

-- --------------------------------------------------------

--
-- 表的结构 `group_member`
--

CREATE TABLE IF NOT EXISTS `group_member` (
  `gid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '群组id',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `grade` tinyint(1) NOT NULL COMMENT '等级',
  `join_time` int(10) unsigned NOT NULL COMMENT '加入时间',
  PRIMARY KEY (`gid`,`uid`),
  KEY `uid` (`uid`),
  KEY `gid_grade` (`gid`,`grade`),
  KEY `uid_grade` (`uid`,`grade`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='群组成员';

-- --------------------------------------------------------

--
-- 表的结构 `haixi`
--

CREATE TABLE IF NOT EXISTS `haixi` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(10) DEFAULT NULL COMMENT '类型',
  `company` varchar(50) DEFAULT NULL COMMENT '单位名称',
  `office` varchar(50) DEFAULT NULL COMMENT '职务',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `question` varchar(350) DEFAULT NULL,
  `number` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='海西论坛报名' AUTO_INCREMENT=175 ;

-- --------------------------------------------------------

--
-- 表的结构 `imsi_mobile_link`
--

CREATE TABLE IF NOT EXISTS `imsi_mobile_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `imsi` varchar(32) NOT NULL DEFAULT '' COMMENT 'imsi号码',
  `zone_code` varchar(6) NOT NULL DEFAULT '86' COMMENT '国家码',
  `mobile` varchar(16) NOT NULL DEFAULT '' COMMENT '手机号',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '关联类型，0：接口关联，1:上行短信关联',
  `can_login` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '能否用于登录 0:不能，1：能',
  `link_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `imsi` (`imsi`),
  UNIQUE KEY `mobile` (`zone_code`,`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='imsi和手机号的关联表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_channel`
--

CREATE TABLE IF NOT EXISTS `im_channel` (
  `receiver_uid` bigint(20) unsigned NOT NULL COMMENT '接收者的id',
  `sender_uid` bigint(20) unsigned NOT NULL COMMENT '发送者的id',
  `channel` int(10) unsigned NOT NULL COMMENT '通道号',
  `receiver_mobile` varchar(20) NOT NULL COMMENT '接收者手机号',
  `timestamp` int(10) unsigned NOT NULL COMMENT '通道创建时间',
  `act_count` int(11) NOT NULL DEFAULT '0' COMMENT '回复次数',
  `act_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后回复时间',
  PRIMARY KEY (`receiver_uid`,`sender_uid`,`channel`),
  KEY `receiver_mobile` (`receiver_mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短信通道';

-- --------------------------------------------------------

--
-- 表的结构 `im_channel_nv`
--

CREATE TABLE IF NOT EXISTS `im_channel_nv` (
  `sender_mobile` varchar(11) NOT NULL DEFAULT '',
  `channel` int(10) unsigned NOT NULL DEFAULT '0',
  `receiver_mobile` varchar(11) NOT NULL DEFAULT '',
  UNIQUE KEY `channel_receiver` (`channel`,`receiver_mobile`),
  UNIQUE KEY `sender_receiver` (`sender_mobile`,`receiver_mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='网音短信通道号分配表';

-- --------------------------------------------------------

--
-- 表的结构 `im_channel_nv_cursor`
--

CREATE TABLE IF NOT EXISTS `im_channel_nv_cursor` (
  `cursor` int(11) unsigned NOT NULL DEFAULT '0',
  `receiver_mobile` varchar(11) NOT NULL DEFAULT '',
  UNIQUE KEY `receiver` (`receiver_mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `im_geohistory`
--

CREATE TABLE IF NOT EXISTS `im_geohistory` (
  `address` varchar(50) NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `longitude` varchar(128) NOT NULL,
  `latitude` varchar(128) NOT NULL,
  `is_correct` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ctime` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1发的，2收的',
  PRIMARY KEY (`address`,`uid`),
  KEY `ctime` (`ctime`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `im_group`
--

CREATE TABLE IF NOT EXISTS `im_group` (
  `listmd5` char(32) CHARACTER SET latin1 NOT NULL COMMENT 'MD5(list)',
  `list` text CHARACTER SET latin1 NOT NULL COMMENT '参与会话的所有用户uid不重复顺序排序并以'',''分隔',
  PRIMARY KEY (`listmd5`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `im_grpsms_verify`
--

CREATE TABLE IF NOT EXISTS `im_grpsms_verify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_uid` int(10) unsigned NOT NULL COMMENT '发送者id',
  `group_id` varchar(32) NOT NULL COMMENT '群发id',
  `receiver_count` mediumint(8) unsigned NOT NULL COMMENT '接收者数量',
  `ctime` int(10) unsigned NOT NULL COMMENT '记录时间',
  `msgid` char(36) NOT NULL COMMENT '消息id',
  `content_key` varchar(32) NOT NULL COMMENT '消息内容的key',
  `verify` int(11) NOT NULL DEFAULT '0' COMMENT '0未处理，-1不通过，其他通过时间',
  PRIMARY KEY (`id`),
  KEY `verify` (`verify`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='短信审核' AUTO_INCREMENT=5581 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_message`
--

CREATE TABLE IF NOT EXISTS `im_message` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msgid` char(36) NOT NULL COMMENT '消息id',
  `msgtype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '消息类型(0普通,1系统)',
  `timestamp` bigint(13) NOT NULL COMMENT '消息生成时间',
  `ownerid` bigint(20) unsigned NOT NULL COMMENT '所属用户id',
  `opid` varchar(32) NOT NULL COMMENT '会话方id',
  `optype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '会话方的类型(1群组,0单个用户)',
  `box` tinyint(1) unsigned NOT NULL COMMENT '标识是收取还是发送(0收,1送)',
  `content_key` varchar(32) NOT NULL COMMENT '消息内容的key',
  `r_appid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：接收的客户端类型',
  `r_roger` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：消息送达标识(0未送,1送到客户端,2送到苹果设备,3送到手机)',
  `r_rtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：消息已读时间(0未读)',
  `s_appid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：发送的客户端类型',
  `s_stime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：客户端发送的时间',
  `s_sms` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：手机短信发送标识',
  PRIMARY KEY (`id`),
  KEY `ownerid` (`ownerid`),
  KEY `opid` (`opid`),
  KEY `timestamp` (`timestamp`),
  KEY `msgid` (`msgid`),
  KEY `s_stime` (`s_stime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13435161 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_message_del`
--

CREATE TABLE IF NOT EXISTS `im_message_del` (
  `id` bigint(20) unsigned NOT NULL,
  `msgid` char(36) NOT NULL COMMENT '消息id',
  `msgtype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '消息类型(0普通,1系统)',
  `timestamp` bigint(13) unsigned NOT NULL COMMENT '消息生成时间',
  `ownerid` bigint(20) unsigned NOT NULL COMMENT '所属用户id',
  `opid` varchar(32) NOT NULL COMMENT '会话方id',
  `optype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '会话方的类型(1群组,0单个用户)',
  `box` tinyint(1) unsigned NOT NULL COMMENT '标识是收取还是发送(0收,1送)',
  `content_key` varchar(32) NOT NULL COMMENT '消息内容的key',
  `r_appid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：接收的客户端类型',
  `r_roger` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：消息送达标识(0未送,1送到客户端,2送到苹果设备,3送到手机)',
  `r_rtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接收属性：消息已读时间(0未读)',
  `s_appid` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：发送的客户端类型',
  `s_stime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：客户端发送的时间',
  `s_sms` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '发送属性：手机短信发送标识',
  `missing` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0主动删除数据，1丢失数据',
  KEY `ownerid` (`ownerid`),
  KEY `opid` (`opid`),
  KEY `timestamp` (`timestamp`),
  KEY `msgid` (`msgid`),
  KEY `missing` (`missing`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `im_notify`
--

CREATE TABLE IF NOT EXISTS `im_notify` (
  `ownerid` bigint(20) unsigned NOT NULL COMMENT '接收者的uid',
  `optype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '会话方的类型(1群组,0单个用户)',
  `opid` varchar(32) NOT NULL COMMENT '发送者的uid或者群的id',
  `new_count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '来自opid的且ownerid未读消息条数',
  `timestamp` bigint(13) unsigned NOT NULL COMMENT '最后一条消息的时间',
  `last_msgid` varchar(36) NOT NULL COMMENT '最后一条消息的id',
  `last_smstime` bigint(13) unsigned NOT NULL COMMENT '最后推送短信的时间',
  PRIMARY KEY (`ownerid`,`opid`,`optype`),
  KEY `timestamp` (`timestamp`),
  KEY `opid` (`opid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `im_present_log`
--

CREATE TABLE IF NOT EXISTS `im_present_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `status` tinyint(1) NOT NULL,
  `amount` smallint(3) NOT NULL,
  `created` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15847 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_pushlog`
--

CREATE TABLE IF NOT EXISTS `im_pushlog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from` varchar(18) NOT NULL COMMENT '上行手机号码',
  `channel` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '短信通道',
  `timestamp` bigint(13) unsigned NOT NULL COMMENT '记录时间',
  `content` varchar(255) NOT NULL COMMENT '短信内容',
  `source_type` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=520195 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_register`
--

CREATE TABLE IF NOT EXISTS `im_register` (
  `guid` varchar(50) NOT NULL,
  `mobile` varchar(50) NOT NULL,
  `uid` bigint(20) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `zone` tinyint(3) unsigned NOT NULL DEFAULT '86',
  `install_id` int(10) NOT NULL DEFAULT '0',
  `phone_model` varchar(128) NOT NULL DEFAULT '',
  `os` varchar(128) NOT NULL DEFAULT '',
  `device_id` varchar(128) NOT NULL DEFAULT '',
  `client_id` tinyint(3) unsigned NOT NULL,
  `appid` int(10) unsigned NOT NULL DEFAULT '0',
  `imsi` varchar(64) NOT NULL DEFAULT '',
  `ctime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `im_register_guid`
--

CREATE TABLE IF NOT EXISTS `im_register_guid` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appid` smallint(5) unsigned NOT NULL COMMENT '应用ID',
  `guid` varchar(64) NOT NULL COMMENT '手机标识',
  `mobile` varchar(20) NOT NULL COMMENT '手机号',
  `zone_code` varchar(6) NOT NULL COMMENT '区号',
  `verify` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证过',
  `ctime` int(10) unsigned NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `appid_guid` (`appid`,`guid`),
  KEY `appid_mobile_zone_code` (`appid`,`mobile`,`zone_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=773 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_register_imsi`
--

CREATE TABLE IF NOT EXISTS `im_register_imsi` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `imsi` varchar(64) NOT NULL COMMENT '手机标识',
  `mobile` varchar(20) NOT NULL COMMENT '手机号',
  `zone_code` varchar(6) NOT NULL COMMENT '区号',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '插入类型0：用户自己上传,1上行短信',
  `ctime` int(10) unsigned NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `imsi` (`imsi`),
  KEY `mobile_zone_code` (`mobile`,`zone_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=130594 ;

-- --------------------------------------------------------

--
-- 表的结构 `im_smslog`
--

CREATE TABLE IF NOT EXISTS `im_smslog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1:音信,2：国都',
  `sender_uid` bigint(20) unsigned NOT NULL,
  `receiver_zonecode` smallint(5) unsigned NOT NULL DEFAULT '86' COMMENT '默认中国',
  `receiver_mobile` varchar(32) NOT NULL COMMENT '发送号码',
  `receiver_uid` bigint(20) unsigned NOT NULL COMMENT '发送的用户uid',
  `timestamp` bigint(13) unsigned NOT NULL COMMENT '记录时间',
  `content` text NOT NULL COMMENT '短信内容',
  PRIMARY KEY (`id`),
  KEY `sender_uid` (`sender_uid`),
  KEY `receiver_uid` (`receiver_uid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4688312 ;

-- --------------------------------------------------------

--
-- 表的结构 `invitation`
--

CREATE TABLE IF NOT EXISTS `invitation` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `reg_date` int(10) NOT NULL,
  `invite_uid` int(10) NOT NULL,
  `invite_realname` varchar(30) NOT NULL,
  `realname` varchar(30) NOT NULL COMMENT '被邀请人真实姓名',
  `mobile_check` varchar(32) NOT NULL,
  `uid` int(10) NOT NULL COMMENT '被邀请人uid',
  `is_mobile_fit` tinyint(1) NOT NULL,
  `group_id` int(10) NOT NULL DEFAULT '0' COMMENT '群组id',
  PRIMARY KEY (`id`),
  KEY `invite_code` (`invite_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1446 ;

-- --------------------------------------------------------

--
-- 表的结构 `invitehistory`
--

CREATE TABLE IF NOT EXISTS `invitehistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `gid` int(11) DEFAULT '0',
  `realname` varchar(10) DEFAULT NULL,
  `content` text,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送标志',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `links`
--

CREATE TABLE IF NOT EXISTS `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `url` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=105 ;

-- --------------------------------------------------------

--
-- 表的结构 `links_category`
--

CREATE TABLE IF NOT EXISTS `links_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- 表的结构 `location`
--

CREATE TABLE IF NOT EXISTS `location` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `number` int(10) NOT NULL DEFAULT '0' COMMENT '号码前7位',
  `area` varchar(50) DEFAULT NULL COMMENT '归属地',
  `type` varchar(50) DEFAULT NULL COMMENT '卡类型',
  `area_code` varchar(10) DEFAULT NULL COMMENT '归属地编码',
  `post_code` varchar(50) DEFAULT NULL COMMENT '邮政编码',
  PRIMARY KEY (`id`),
  KEY `number` (`number`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=280001 ;

-- --------------------------------------------------------

--
-- 表的结构 `market_class`
--

CREATE TABLE IF NOT EXISTS `market_class` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depth` tinyint(1) NOT NULL,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'object_id创建的时间',
  `create_at` int(10) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=360 ;

-- --------------------------------------------------------

--
-- 表的结构 `market_favorite`
--

CREATE TABLE IF NOT EXISTS `market_favorite` (
  `object_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `create_at` int(10) DEFAULT NULL,
  UNIQUE KEY `UID_OBJID` (`user_id`,`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `market_hide`
--

CREATE TABLE IF NOT EXISTS `market_hide` (
  `object_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `object_time` int(10) DEFAULT NULL COMMENT 'object_id创建的时间',
  `create_at` int(10) DEFAULT NULL COMMENT '用户隐藏操作的时间',
  UNIQUE KEY `user_id` (`user_id`,`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `market_matchlibrary`
--

CREATE TABLE IF NOT EXISTS `market_matchlibrary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `market_class_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `create_at` int(10) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `market_pic`
--

CREATE TABLE IF NOT EXISTS `market_pic` (
  `user_id` int(10) unsigned NOT NULL,
  `object_id` int(10) unsigned NOT NULL,
  `src` varchar(255) DEFAULT NULL,
  `create_at` int(10) NOT NULL DEFAULT '0' COMMENT '发布时间',
  KEY `object_id` (`object_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='跳蚤市场图片表';

-- --------------------------------------------------------

--
-- 表的结构 `market_terms`
--

CREATE TABLE IF NOT EXISTS `market_terms` (
  `term_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` int(10) NOT NULL DEFAULT '0',
  `hot` int(10) NOT NULL DEFAULT '0' COMMENT '标签搜索热度',
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='二手信息tag' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `market_terms_relationships`
--

CREATE TABLE IF NOT EXISTS `market_terms_relationships` (
  `object_id` int(10) unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` int(10) unsigned NOT NULL DEFAULT '0',
  `term_order` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `members`
--

CREATE TABLE IF NOT EXISTS `members` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `imid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '91通行证ID',
  `mobile` char(30) NOT NULL,
  `username` varchar(70) NOT NULL COMMENT '用户名',
  `password` varchar(64) NOT NULL COMMENT '密码',
  `or_password` varchar(200) NOT NULL,
  `regip` varchar(15) NOT NULL COMMENT '注册时IP',
  `regdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册日期',
  `lastloginip` varchar(15) NOT NULL COMMENT '最后登录IP',
  `lastlogintime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `appid` smallint(6) NOT NULL COMMENT '注册来源app',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `invite_id` int(10) unsigned NOT NULL,
  `invite_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请者uid',
  `invite_limit` smallint(5) NOT NULL DEFAULT '0',
  `source` char(12) NOT NULL,
  `private_group_limit` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT '用户可创建的私密群数量',
  `invite_gid` int(10) NOT NULL DEFAULT '0' COMMENT '群邀请注册进来用户相应的群id',
  `invite_aid` int(11) NOT NULL DEFAULT '0' COMMENT '活动邀请注册进来的相应活动id',
  `verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '实名验证',
  `public_group_limit` smallint(5) unsigned NOT NULL DEFAULT '4' COMMENT '用户可以创建的公开群群数 量',
  `show_company` tinyint(1) NOT NULL DEFAULT '1',
  `mass_sms_limit` int(10) NOT NULL DEFAULT '0',
  `phone_model` varchar(40) NOT NULL COMMENT '手机型号',
  `phone_os` varchar(20) NOT NULL COMMENT '手机系统版>本号',
  `install_id` varchar(40) NOT NULL COMMENT '手机客户>端安装标识id',
  `zone_code` varchar(30) NOT NULL DEFAULT '86',
  `device_id` varchar(64) NOT NULL COMMENT '体验用户设备ID',
  `sms_count` int(10) NOT NULL,
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户类型0普通用户1机构用户',
  `activity` tinyint(1) NOT NULL DEFAULT '1' COMMENT '用户是否激活',
  `last_login_model` tinyint(1) NOT NULL DEFAULT '0' COMMENT '最后登录方式0用户密码1短信临时密码',
  `binded` tinyint(1) NOT NULL,
  `version` tinyint(1) NOT NULL DEFAULT '3',
  PRIMARY KEY (`uid`),
  KEY `status` (`status`,`source`,`device_id`),
  KEY `mobile` (`mobile`,`zone_code`),
  KEY `imid` (`imid`,`appid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户表' AUTO_INCREMENT=100422791 ;

-- --------------------------------------------------------

--
-- 表的结构 `membersinfo`
--

CREATE TABLE IF NOT EXISTS `membersinfo` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `email` varchar(32) NOT NULL COMMENT '邮箱',
  `emailcheck` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '邮箱是否已经验证',
  `realname` varchar(30) NOT NULL COMMENT '真实姓名',
  `familyname` varchar(20) NOT NULL,
  `givenname` varchar(20) NOT NULL,
  `idcard` varchar(18) NOT NULL COMMENT '身份证号码',
  `vip` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT 'VIP星级(0,姓名未审核)',
  `zone_code` char(20) NOT NULL,
  `mobile` char(30) NOT NULL COMMENT '手机号码',
  `telephone` varchar(20) NOT NULL COMMENT '电话号码',
  `sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=保密，1=男，2=女',
  `qq` varchar(20) NOT NULL COMMENT 'QQ号码',
  `msn` varchar(32) NOT NULL COMMENT 'MSN帐号',
  `homepage` varchar(100) NOT NULL COMMENT '个人主页',
  `sign` varchar(140) NOT NULL COMMENT '个人签名',
  `birthyear` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '出生年份',
  `birthmonth` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '出生月',
  `birthday` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '出生日',
  `is_lunar` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否农历生日',
  `is_hide_year` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏年份',
  `blood` varchar(5) NOT NULL COMMENT '血型 ',
  `marry` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '婚否，0=保密、1=单身、2=非单身',
  `animal_sign` char(1) NOT NULL COMMENT '生肖',
  `zodiac` char(3) NOT NULL COMMENT '星座',
  `astro` tinyint(2) NOT NULL,
  `work` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '职业',
  `avatar` smallint(5) unsigned NOT NULL DEFAULT '0',
  `birthcountry` varchar(20) NOT NULL COMMENT '出生国家',
  `birthprovince` varchar(20) NOT NULL COMMENT '出生省份',
  `birthcity` varchar(20) NOT NULL COMMENT '出生城市 ',
  `residecountry` varchar(255) NOT NULL COMMENT '居住国家',
  `resideprovince` varchar(255) NOT NULL COMMENT '所在省份',
  `residecity` varchar(255) NOT NULL COMMENT '所在城市 ',
  `note` varchar(140) NOT NULL COMMENT '备注',
  `authstr` varchar(20) NOT NULL,
  `friend` mediumtext NOT NULL,
  `feedfriend` mediumtext NOT NULL,
  `gtalk` varchar(32) NOT NULL,
  `company` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `title` varchar(50) NOT NULL,
  `college` varchar(50) NOT NULL,
  `duty` varchar(30) NOT NULL,
  `commit` varchar(250) NOT NULL,
  `hsch` varchar(50) NOT NULL,
  `khfn` varchar(50) NOT NULL,
  `nickname` varchar(64) NOT NULL,
  `updatetime` int(10) NOT NULL DEFAULT '0' COMMENT '标识头像最新修改的时间',
  `lunar_bday` varchar(25) NOT NULL COMMENT '农历生日',
  `completed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '完善度',
  PRIMARY KEY (`uid`),
  KEY `email` (`email`),
  KEY `realname` (`realname`),
  KEY `zone_code` (`zone_code`,`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户详细信息';

-- --------------------------------------------------------

--
-- 表的结构 `members_auto_url`
--

CREATE TABLE IF NOT EXISTS `members_auto_url` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url_code` char(40) NOT NULL,
  `password` varchar(40) NOT NULL,
  `tmp_uid` int(10) unsigned NOT NULL,
  `last_login_date` int(10) NOT NULL,
  `last_login_ip` varchar(30) NOT NULL,
  `tmp_user` tinyint(1) NOT NULL,
  `counts` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `members_bind`
--

CREATE TABLE IF NOT EXISTS `members_bind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bind_uid` int(10) unsigned NOT NULL,
  `bind_zonecode` varchar(20) NOT NULL,
  `bind_mobile` varchar(40) NOT NULL,
  `to_bind_uid` int(10) unsigned NOT NULL,
  `to_bind_zonecode` varchar(20) NOT NULL,
  `to_bind_mobile` varchar(40) NOT NULL,
  `bind_date` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=171499 ;

-- --------------------------------------------------------

--
-- 表的结构 `members_login_log`
--

CREATE TABLE IF NOT EXISTS `members_login_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `zone_code` char(20) NOT NULL,
  `mobile` char(30) NOT NULL,
  `created` int(10) NOT NULL,
  `password` varchar(40) NOT NULL,
  `counts` int(11) NOT NULL,
  `client_id` tinyint(2) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `zone_code` (`zone_code`,`mobile`,`created`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=244282 ;

-- --------------------------------------------------------

--
-- 表的结构 `members_units`
--

CREATE TABLE IF NOT EXISTS `members_units` (
  `uid` int(10) unsigned NOT NULL,
  `unitid` int(10) unsigned NOT NULL,
  `uap_uid` int(10) unsigned NOT NULL,
  `username` varchar(40) NOT NULL,
  `unitname` varchar(64) NOT NULL,
  PRIMARY KEY (`uid`,`unitid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `members_url`
--

CREATE TABLE IF NOT EXISTS `members_url` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `appid` int(10) NOT NULL,
  `type` char(10) NOT NULL COMMENT 'url类型：动态feed 个人名片card 私聊im',
  `status_id` varchar(60) NOT NULL,
  `card_url` varchar(60) NOT NULL,
  `card_name` varchar(30) NOT NULL,
  `url_code` varchar(40) NOT NULL COMMENT 'URL唯一识别ID',
  `msgid` char(36) NOT NULL COMMENT '消息id',
  `content_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '消息类型:1音频',
  `sender_uid` int(10) NOT NULL COMMENT '发送者uid',
  `sender_name` varchar(30) NOT NULL COMMENT '发送者姓名',
  `receiver_uid` int(10) NOT NULL COMMENT '接收者uid',
  `receiver_mobile` char(20) NOT NULL COMMENT '接收者手机号',
  `receiver_zone_code` varchar(40) NOT NULL,
  `receiver_name` varchar(30) NOT NULL COMMENT '接收者姓名',
  `send_time` int(10) NOT NULL COMMENT '创建时间',
  `receive_time` int(10) NOT NULL COMMENT 'url打开时间',
  `receive_ip` varchar(30) NOT NULL COMMENT 'url打开ip',
  `verify_time` int(10) NOT NULL,
  `verify_client_id` smallint(3) NOT NULL,
  `verify_phone_model` varchar(50) NOT NULL,
  `verify_phone_os` varchar(50) NOT NULL,
  `last_open_time` int(10) NOT NULL,
  `last_open_ip` varchar(20) NOT NULL,
  `browser` varchar(40) NOT NULL,
  `os` varchar(40) NOT NULL,
  `user_agent` varchar(250) NOT NULL,
  `expire_time` int(10) NOT NULL COMMENT '过期时间',
  `password` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `url_code` (`url_code`),
  KEY `receiver_uid` (`receiver_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3488609 ;

-- --------------------------------------------------------

--
-- 表的结构 `mo_sms_log`
--

CREATE TABLE IF NOT EXISTS `mo_sms_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_uid` int(10) unsigned NOT NULL,
  `receiver_uid` int(10) unsigned NOT NULL,
  `send_time` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `comment_id` varchar(40) NOT NULL,
  `feed_id` varchar(40) NOT NULL,
  `mo_id` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24613 ;

-- --------------------------------------------------------

--
-- 表的结构 `mysite`
--

CREATE TABLE IF NOT EXISTS `mysite` (
  `domain` varchar(20) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  KEY `user_id` (`user_id`),
  KEY `domain` (`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `nd_hr_invitation`
--

CREATE TABLE IF NOT EXISTS `nd_hr_invitation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(32) NOT NULL COMMENT '邀请码',
  `invite_uid` int(10) NOT NULL COMMENT '邀请人id',
  PRIMARY KEY (`id`),
  KEY `invite_code` (`invite_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=205 ;

-- --------------------------------------------------------

--
-- 表的结构 `nd_hr_invitation_user`
--

CREATE TABLE IF NOT EXISTS `nd_hr_invitation_user` (
  `uid` int(10) NOT NULL COMMENT '用户id',
  `invite_uid` int(10) NOT NULL COMMENT '邀请人id',
  `time` int(11) NOT NULL COMMENT '用户接受邀请时间',
  PRIMARY KEY (`uid`,`invite_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `new_year_sms_send`
--

CREATE TABLE IF NOT EXISTS `new_year_sms_send` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) DEFAULT NULL,
  `send_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=55 ;

-- --------------------------------------------------------

--
-- 表的结构 `notification`
--

CREATE TABLE IF NOT EXISTS `notification` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `appid` smallint(4) DEFAULT '0',
  `tplid` smallint(4) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '消息状态',
  `isnew` tinyint(1) DEFAULT '1',
  `authorid` int(11) unsigned DEFAULT '0',
  `author` varchar(15) DEFAULT NULL,
  `title` text,
  `body` text,
  `addtime` int(11) NOT NULL DEFAULT '0',
  `hash` char(32) NOT NULL,
  `objid` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `AK_uid` (`uid`,`isnew`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=47191 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth2_token`
--

CREATE TABLE IF NOT EXISTS `oauth2_token` (
  `uid` int(10) unsigned NOT NULL,
  `usa_id` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `access_token` varchar(60) NOT NULL,
  `expires_in` int(10) NOT NULL,
  `created` int(10) NOT NULL,
  `updated` int(10) NOT NULL,
  `site` char(10) NOT NULL,
  `appid` smallint(5) unsigned NOT NULL DEFAULT '0',
  KEY `uid` (`uid`),
  KEY `site` (`site`),
  KEY `appid` (`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_consumer_registry`
--

CREATE TABLE IF NOT EXISTS `oauth_consumer_registry` (
  `ocr_id` int(11) NOT NULL AUTO_INCREMENT,
  `ocr_usa_id_ref` int(11) DEFAULT NULL,
  `ocr_consumer_key` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ocr_consumer_secret` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ocr_signature_methods` varchar(255) NOT NULL DEFAULT 'HMAC-SHA1,PLAINTEXT',
  `ocr_server_uri` varchar(200) NOT NULL,
  `ocr_server_uri_host` varchar(128) NOT NULL,
  `ocr_server_uri_path` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ocr_request_token_uri` varchar(255) NOT NULL,
  `ocr_authorize_uri` varchar(255) NOT NULL,
  `ocr_access_token_uri` varchar(255) NOT NULL,
  `ocr_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocr_id`),
  UNIQUE KEY `ocr_consumer_key` (`ocr_consumer_key`,`ocr_usa_id_ref`,`ocr_server_uri`),
  KEY `ocr_server_uri` (`ocr_server_uri`),
  KEY `ocr_server_uri_host` (`ocr_server_uri_host`,`ocr_server_uri_path`),
  KEY `ocr_usa_id_ref` (`ocr_usa_id_ref`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36690 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_consumer_token`
--

CREATE TABLE IF NOT EXISTS `oauth_consumer_token` (
  `oct_id` int(11) NOT NULL AUTO_INCREMENT,
  `oct_ocr_id_ref` int(11) NOT NULL,
  `oct_usa_id_ref` int(11) NOT NULL,
  `oct_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `oct_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `oct_token_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `oct_token_type` enum('request','authorized','access') DEFAULT NULL,
  `oct_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `oct_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`oct_id`),
  UNIQUE KEY `oct_ocr_id_ref` (`oct_ocr_id_ref`,`oct_token`),
  UNIQUE KEY `oct_usa_id_ref` (`oct_usa_id_ref`,`oct_ocr_id_ref`,`oct_token_type`,`oct_name`),
  KEY `oct_token_ttl` (`oct_token_ttl`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=80307 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_log`
--

CREATE TABLE IF NOT EXISTS `oauth_log` (
  `olg_id` int(11) NOT NULL AUTO_INCREMENT,
  `olg_osr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_ost_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_ocr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_oct_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_usa_id_ref` int(11) DEFAULT NULL,
  `olg_received` text NOT NULL,
  `olg_sent` text NOT NULL,
  `olg_base_string` text NOT NULL,
  `olg_notes` text NOT NULL,
  `olg_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `olg_remote_ip` bigint(20) NOT NULL,
  PRIMARY KEY (`olg_id`),
  KEY `olg_osr_consumer_key` (`olg_osr_consumer_key`,`olg_id`),
  KEY `olg_ost_token` (`olg_ost_token`,`olg_id`),
  KEY `olg_ocr_consumer_key` (`olg_ocr_consumer_key`,`olg_id`),
  KEY `olg_oct_token` (`olg_oct_token`,`olg_id`),
  KEY `olg_usa_id_ref` (`olg_usa_id_ref`,`olg_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_login_log`
--

CREATE TABLE IF NOT EXISTS `oauth_login_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `appid` int(10) NOT NULL,
  `imei1` varchar(250) NOT NULL,
  `phone_model1` varchar(100) NOT NULL,
  `phone_os1` varchar(100) NOT NULL,
  `imei2` varchar(250) NOT NULL,
  `phone_model2` varchar(100) NOT NULL,
  `phone_os2` varchar(100) NOT NULL,
  `datetime2` int(10) NOT NULL,
  `imei3` varchar(250) NOT NULL,
  `phone_model3` varchar(100) NOT NULL,
  `phone_os3` varchar(100) NOT NULL,
  `datetime3` int(10) NOT NULL,
  `datetime1` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_2` (`uid`,`appid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16665779 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_server_nonce`
--

CREATE TABLE IF NOT EXISTS `oauth_server_nonce` (
  `osn_id` int(11) NOT NULL AUTO_INCREMENT,
  `osn_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osn_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osn_timestamp` bigint(20) NOT NULL,
  `osn_nonce` varchar(80) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`osn_id`),
  UNIQUE KEY `osn_consumer_key` (`osn_consumer_key`,`osn_token`,`osn_timestamp`,`osn_nonce`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1854363 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_server_registry`
--

CREATE TABLE IF NOT EXISTS `oauth_server_registry` (
  `osr_id` int(11) NOT NULL AUTO_INCREMENT,
  `ost_app_id` int(10) NOT NULL,
  `osr_name` varchar(50) NOT NULL,
  `osr_usa_id_ref` int(11) DEFAULT NULL,
  `osr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osr_consumer_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osr_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `osr_status` varchar(16) NOT NULL,
  `osr_requester_name` varchar(64) NOT NULL,
  `osr_requester_email` varchar(64) NOT NULL,
  `osr_callback_uri` varchar(255) NOT NULL,
  `osr_application_uri` varchar(255) NOT NULL,
  `osr_application_title` varchar(80) NOT NULL,
  `osr_application_descr` text NOT NULL,
  `osr_application_notes` text NOT NULL,
  `osr_application_type` varchar(20) NOT NULL,
  `osr_application_commercial` tinyint(1) NOT NULL DEFAULT '0',
  `osr_issue_date` datetime NOT NULL,
  `osr_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`osr_id`),
  UNIQUE KEY `osr_consumer_key` (`osr_consumer_key`),
  KEY `osr_usa_id_ref` (`osr_usa_id_ref`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1007 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_server_token`
--

CREATE TABLE IF NOT EXISTS `oauth_server_token` (
  `ost_id` int(11) NOT NULL AUTO_INCREMENT,
  `ost_osr_id_ref` int(11) NOT NULL,
  `ost_usa_id_ref` int(11) NOT NULL,
  `ost_device_id` varchar(200) NOT NULL,
  `ost_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ost_token_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ost_token_type` enum('request','access') DEFAULT NULL,
  `ost_authorized` tinyint(1) NOT NULL DEFAULT '0',
  `ost_referrer_host` varchar(128) NOT NULL DEFAULT '',
  `ost_token_ttl` datetime NOT NULL DEFAULT '9999-12-31 00:00:00',
  `ost_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ost_timestamp_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ost_verifier` char(10) DEFAULT NULL,
  `ost_callback_url` varchar(512) DEFAULT NULL,
  `ost_client_id` tinyint(3) NOT NULL,
  `ost_phone_model` varchar(200) NOT NULL,
  `ost_phone_os` varchar(200) NOT NULL,
  `ost_phone_name` varchar(200) NOT NULL,
  `ost_imsi` varchar(200) NOT NULL,
  `ost_imsi2` varchar(200) NOT NULL,
  PRIMARY KEY (`ost_id`),
  UNIQUE KEY `ost_token` (`ost_token`),
  KEY `ost_osr_id_ref` (`ost_osr_id_ref`),
  KEY `ost_token_ttl` (`ost_token_ttl`),
  KEY `ost_usa_id_ref` (`ost_usa_id_ref`,`ost_device_id`),
  KEY `ost_imsi` (`ost_imsi`),
  KEY `ost_imsi2` (`ost_imsi2`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=87824343 ;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_token`
--

CREATE TABLE IF NOT EXISTS `oauth_token` (
  `uid` int(11) unsigned NOT NULL,
  `usa_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `oauth_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `oauth_token_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `site` enum('weibo.com','t.qq.com','renren.com','t.sohu.com','kaixin001.com') NOT NULL COMMENT '新浪,搜狐,腾讯,人人,开心网',
  `disable` enum('N','Y') NOT NULL DEFAULT 'N' COMMENT '用戶是否己取消綁定',
  KEY `site` (`site`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `datetime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `pending_birthday`
--

CREATE TABLE IF NOT EXISTS `pending_birthday` (
  `uid` int(10) unsigned NOT NULL,
  `result` varchar(2048) NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `pending_friends`
--

CREATE TABLE IF NOT EXISTS `pending_friends` (
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `pendingFriends` varchar(2048) DEFAULT '' COMMENT '可能认识的人, uid_type 1:tel 2:email 3:tel&email',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC COMMENT='可能认识的人：以,分隔uid串';

-- --------------------------------------------------------

--
-- 表的结构 `pending_groups`
--

CREATE TABLE IF NOT EXISTS `pending_groups` (
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `pendingGroups` varchar(2048) DEFAULT '' COMMENT '可能感兴趣的群组',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC COMMENT='可能感兴趣的群组：以,分隔的gid串';

-- --------------------------------------------------------

--
-- 表的结构 `personal_addresses`
--

CREATE TABLE IF NOT EXISTS `personal_addresses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '地址ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `country` varchar(255) DEFAULT NULL COMMENT '值',
  `postal` varchar(20) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `street` text,
  `show` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否可见',
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='地址' AUTO_INCREMENT=215 ;

-- --------------------------------------------------------

--
-- 表的结构 `personal_emails`
--

CREATE TABLE IF NOT EXISTS `personal_emails` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '邮箱ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  `show` int(11) NOT NULL DEFAULT '1' COMMENT '是否可见',
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_VALUE` (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='邮箱' AUTO_INCREMENT=45059 ;

-- --------------------------------------------------------

--
-- 表的结构 `personal_ims`
--

CREATE TABLE IF NOT EXISTS `personal_ims` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '即时通讯ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `protocol` varchar(50) NOT NULL COMMENT '协议',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  `show` int(11) NOT NULL DEFAULT '1' COMMENT '是否可见',
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='即时通讯' AUTO_INCREMENT=566 ;

-- --------------------------------------------------------

--
-- 表的结构 `personal_tels`
--

CREATE TABLE IF NOT EXISTS `personal_tels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '电话ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值(最多75个字符)',
  `pref` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否主手机',
  `city` varchar(30) NOT NULL DEFAULT '' COMMENT '归属地',
  `show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否可见',
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_VALUE` (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='电话' AUTO_INCREMENT=27887961 ;

-- --------------------------------------------------------

--
-- 表的结构 `personal_urls`
--

CREATE TABLE IF NOT EXISTS `personal_urls` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `value` varchar(75) DEFAULT NULL COMMENT '值',
  `show` int(11) NOT NULL DEFAULT '1' COMMENT '是否可见',
  PRIMARY KEY (`id`),
  KEY `IDX_UID` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='网址' AUTO_INCREMENT=30628 ;

-- --------------------------------------------------------

--
-- 表的结构 `phone_brand`
--

CREATE TABLE IF NOT EXISTS `phone_brand` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) DEFAULT '',
  `rank` int(10) DEFAULT '0',
  `brand` varchar(15) NOT NULL,
  `brand_en` varchar(15) NOT NULL,
  `ishot` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='手机品牌' AUTO_INCREMENT=43 ;

-- --------------------------------------------------------

--
-- 表的结构 `phone_marque`
--

CREATE TABLE IF NOT EXISTS `phone_marque` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `brand` varchar(15) DEFAULT NULL,
  `brand_en` varchar(15) DEFAULT NULL,
  `brand_id` int(10) NOT NULL,
  `os` varchar(10) DEFAULT NULL,
  `marque` varchar(25) DEFAULT NULL,
  `dpi` varchar(9) DEFAULT NULL,
  `dpi_w` int(10) DEFAULT '0',
  `dpi_h` int(10) DEFAULT '0',
  `mtime` int(10) NOT NULL,
  `touch` tinyint(1) DEFAULT '0',
  `rank` int(10) DEFAULT '0',
  `ver` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marque_brand` (`marque`,`brand`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1135 ;

-- --------------------------------------------------------

--
-- 表的结构 `phone_model`
--

CREATE TABLE IF NOT EXISTS `phone_model` (
  `model` varchar(200) NOT NULL COMMENT '机型',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
  PRIMARY KEY (`model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `praise`
--

CREATE TABLE IF NOT EXISTS `praise` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `objid` char(21) NOT NULL DEFAULT '0' COMMENT '应用id,例如某条转帖或某篇日记的id',
  `tplname` varchar(60) NOT NULL COMMENT '指明是什么的赞,如日记 投票 以momoserver中feedtype为准',
  `owner` int(11) unsigned NOT NULL COMMENT '被赞的用户ID',
  `uid` int(11) unsigned NOT NULL COMMENT '说赞的用户ID',
  `name` varchar(30) NOT NULL COMMENT '说赞的用户姓名',
  `addtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `IDX_OID_TPN` (`objid`,`tplname`(10))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39229 ;

-- --------------------------------------------------------

--
-- 表的结构 `record`
--

CREATE TABLE IF NOT EXISTS `record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联用户id',
  `content` varchar(255) NOT NULL,
  `imgurl` varchar(255) DEFAULT NULL COMMENT '外部引用的源图片URL',
  `isexist` tinyint(1) NOT NULL DEFAULT '0' COMMENT '广播是否包含图片0：否1:是',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0',
  `retweet_id` varchar(100) DEFAULT NULL COMMENT '转发ID',
  `linktype` tinyint(1) unsigned NOT NULL COMMENT '广播类型:1空间广播,2手机,3短信',
  `appoint_group` varchar(16) NOT NULL,
  `appid` smallint(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44888 ;

-- --------------------------------------------------------

--
-- 表的结构 `record_collage`
--

CREATE TABLE IF NOT EXISTS `record_collage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rid` int(11) unsigned NOT NULL COMMENT '广播id',
  `uid` int(10) unsigned NOT NULL COMMENT '好友id',
  `collagetime` int(10) unsigned NOT NULL COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  KEY `rid` (`rid`,`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='收藏广播' AUTO_INCREMENT=100 ;

-- --------------------------------------------------------

--
-- 表的结构 `record_friends`
--

CREATE TABLE IF NOT EXISTS `record_friends` (
  `rid` int(11) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL COMMENT '好友id',
  KEY `fid` (`fid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='(记录)广播提到的好友表';

-- --------------------------------------------------------

--
-- 表的结构 `report`
--

CREATE TABLE IF NOT EXISTS `report` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `source` smallint(2) NOT NULL,
  `reason` varchar(50) NOT NULL,
  `report_phone` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `url_code` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=91 ;

-- --------------------------------------------------------

--
-- 表的结构 `schooldepartment`
--

CREATE TABLE IF NOT EXISTS `schooldepartment` (
  `sid` int(11) unsigned NOT NULL DEFAULT '0',
  `sname` varchar(50) NOT NULL DEFAULT '',
  `scontent` text NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `sensitives`
--

CREATE TABLE IF NOT EXISTS `sensitives` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `keyword` (`keyword`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=344 ;

-- --------------------------------------------------------

--
-- 表的结构 `set_info_1st_time`
--

CREATE TABLE IF NOT EXISTS `set_info_1st_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) DEFAULT NULL,
  `field` int(11) DEFAULT NULL,
  `isset` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `UID_FIELD` (`uid`,`field`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=568523 ;

-- --------------------------------------------------------

--
-- 表的结构 `share`
--

CREATE TABLE IF NOT EXISTS `share` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL COMMENT '用户id',
  `subject` varchar(50) NOT NULL COMMENT '标题',
  `message` text NOT NULL COMMENT '内容',
  `url` varchar(255) NOT NULL COMMENT '转帖网址',
  `privacy` tinyint(1) unsigned NOT NULL COMMENT '是否只为自己转帖',
  `addtime` int(10) unsigned NOT NULL COMMENT '转帖的时间',
  `views` int(11) unsigned NOT NULL COMMENT '浏览人数',
  `rpnums` int(11) unsigned NOT NULL COMMENT '转帖次数',
  `votenums` int(11) unsigned NOT NULL COMMENT '投票次数',
  `commentnums` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论次数',
  `hasvote` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`rpnums`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='转帖' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `shareother`
--

CREATE TABLE IF NOT EXISTS `shareother` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(11) unsigned NOT NULL COMMENT '用户id',
  `sid` int(11) unsigned NOT NULL COMMENT '转帖id',
  `friendnums` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '好友数',
  `firendids` varchar(255) DEFAULT NULL COMMENT '看帖的好友id',
  `commentnums` int(11) NOT NULL DEFAULT '0',
  `addtime` int(11) NOT NULL COMMENT '发帖时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='我的转帖' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `sharevote`
--

CREATE TABLE IF NOT EXISTS `sharevote` (
  `sid` int(11) unsigned NOT NULL COMMENT '用户id',
  `vote1` varchar(50) NOT NULL,
  `vote2` varchar(50) NOT NULL,
  `vote3` varchar(50) NOT NULL,
  `vote4` varchar(50) NOT NULL,
  `vote5` varchar(50) NOT NULL,
  `vote6` varchar(50) NOT NULL,
  `vote7` varchar(50) NOT NULL,
  `vote8` varchar(50) NOT NULL,
  `num1` int(10) unsigned NOT NULL DEFAULT '0',
  `num2` int(10) unsigned NOT NULL DEFAULT '0',
  `num3` int(10) unsigned NOT NULL DEFAULT '0',
  `num4` int(10) unsigned NOT NULL DEFAULT '0',
  `num5` int(10) unsigned NOT NULL DEFAULT '0',
  `num6` int(10) unsigned NOT NULL DEFAULT '0',
  `num7` int(10) unsigned NOT NULL DEFAULT '0',
  `num8` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `fk_sharevote_share1` (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='转帖';

-- --------------------------------------------------------

--
-- 表的结构 `sms_invite_sent_log`
--

CREATE TABLE IF NOT EXISTS `sms_invite_sent_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_uid` int(10) NOT NULL COMMENT '发送用户id',
  `mobile` varchar(20) NOT NULL COMMENT '发送手机号码',
  `ip` varchar(20) NOT NULL COMMENT '发送用户对应ip地址',
  `send_time` int(10) NOT NULL COMMENT '短信发送时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `sms_sent_log`
--

CREATE TABLE IF NOT EXISTS `sms_sent_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_uid` int(10) NOT NULL,
  `receiver_uid` int(10) NOT NULL DEFAULT '0',
  `ip` varchar(20) NOT NULL,
  `created` int(10) NOT NULL,
  `mobile` varchar(20) NOT NULL COMMENT '发送手机号码',
  `content` varchar(200) NOT NULL COMMENT '短信内容',
  `is_register` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是注册秘密短信',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '短信密码是否有效',
  `login_failed_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '短信密码登录失败次数',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=815 ;

-- --------------------------------------------------------

--
-- 表的结构 `sync_anchors`
--

CREATE TABLE IF NOT EXISTS `sync_anchors` (
  `db_id` int(10) unsigned NOT NULL,
  `client` varchar(256) NOT NULL DEFAULT '',
  `client_anchor` varchar(256) NOT NULL DEFAULT '',
  `server_anchor` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`db_id`,`client`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `sync_dbs`
--

CREATE TABLE IF NOT EXISTS `sync_dbs` (
  `user_id` int(11) unsigned NOT NULL,
  `db_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `db_type` varchar(32) NOT NULL DEFAULT '',
  `db_name` varchar(32) NOT NULL DEFAULT '',
  `db_anchor` varchar(32) NOT NULL DEFAULT '',
  `db_user_name` varchar(32) NOT NULL DEFAULT '',
  `db_password` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`db_id`),
  KEY `IDX_UID_DBN_DBID` (`user_id`,`db_name`,`db_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1416 ;

-- --------------------------------------------------------

--
-- 表的结构 `sync_error`
--

CREATE TABLE IF NOT EXISTS `sync_error` (
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `db_id` int(10) unsigned NOT NULL COMMENT '数据库ID',
  `client` varchar(256) NOT NULL DEFAULT '' COMMENT '客户端名',
  `cids` text COMMENT '出错联系人ID',
  PRIMARY KEY (`db_id`),
  KEY `IDX_UID_DBID_CLIENT` (`user_id`,`db_id`,`client`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='联系人错误表';

-- --------------------------------------------------------

--
-- 表的结构 `sync_history`
--

CREATE TABLE IF NOT EXISTS `sync_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '同步历史ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `db_id` int(11) DEFAULT NULL COMMENT '数据库ID',
  `sync_type` smallint(6) DEFAULT NULL COMMENT '同步类型',
  `client_new` int(11) DEFAULT NULL COMMENT '客户端新增记录数',
  `client_updated` int(11) DEFAULT NULL COMMENT '客户端修改记录数',
  `client_deleted` int(11) DEFAULT NULL COMMENT '客户端删除记录数',
  `server_new` int(11) DEFAULT NULL COMMENT '服务端新增记录数',
  `server_updated` int(11) DEFAULT NULL COMMENT '服务端修改记录数',
  `server_deleted` int(11) DEFAULT NULL COMMENT '客户端删除记录数',
  `dateline` int(11) DEFAULT NULL COMMENT '同步时间戳',
  `client` varchar(32) DEFAULT NULL COMMENT '同步客户端',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='同步历史' AUTO_INCREMENT=8122 ;

-- --------------------------------------------------------

--
-- 表的结构 `sync_items`
--

CREATE TABLE IF NOT EXISTS `sync_items` (
  `db_id` int(10) unsigned NOT NULL,
  `cid` int(11) unsigned NOT NULL,
  `item_anchor` int(10) NOT NULL,
  `item_type` varchar(32) NOT NULL DEFAULT '',
  `item_content` text NOT NULL,
  `item_deleted` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`db_id`,`cid`),
  KEY `IDX_DBID_CID_ANCHOR` (`db_id`,`cid`,`item_anchor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `sync_map`
--

CREATE TABLE IF NOT EXISTS `sync_map` (
  `user_id` int(11) unsigned NOT NULL,
  `db_id` int(10) unsigned NOT NULL,
  `client` varchar(256) NOT NULL DEFAULT '',
  `client_item_id` varchar(128) NOT NULL,
  `cid` int(11) unsigned NOT NULL,
  PRIMARY KEY (`client`,`cid`),
  KEY `IDX_UID_DBID_CLIENT` (`user_id`,`db_id`,`client`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `sync_sessions`
--

CREATE TABLE IF NOT EXISTS `sync_sessions` (
  `session_id` varchar(127) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `tab`
--

CREATE TABLE IF NOT EXISTS `tab` (
  `uid` int(11) unsigned NOT NULL COMMENT '用户id',
  `type_id` smallint(4) unsigned NOT NULL COMMENT '类型',
  `id` int(11) unsigned NOT NULL COMMENT '群或活动id',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示tab',
  `index` int(11) NOT NULL DEFAULT '0' COMMENT 'tab排序索引',
  `last_modify` int(10) NOT NULL,
  PRIMARY KEY (`uid`,`type_id`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `task`
--

CREATE TABLE IF NOT EXISTS `task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL COMMENT '任务类别id',
  `name` varchar(45) NOT NULL,
  `goal` varchar(45) DEFAULT NULL,
  `gold` mediumint(9) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='任务列表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `taskcomplete`
--

CREATE TABLE IF NOT EXISTS `taskcomplete` (
  `id` int(11) NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `task_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_completetask_task1` (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='已完成任务';

-- --------------------------------------------------------

--
-- 表的结构 `tasktype`
--

CREATE TABLE IF NOT EXISTS `tasktype` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL COMMENT '类别名称',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='任务类别表。id为1表示按次数的任务，当任务达到次数就线束，为2表示一次性的任务，为3表示永不结束的任务。这个表可以使用' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `thumb_queue`
--

CREATE TABLE IF NOT EXISTS `thumb_queue` (
  `file_path` varchar(100) NOT NULL,
  `fs_path` varchar(100) NOT NULL,
  `thumb_type` varchar(50) NOT NULL,
  `images_width` int(11) NOT NULL,
  `images_height` int(11) NOT NULL,
  `create_time` int(10) NOT NULL DEFAULT '0',
  KEY `file_path` (`file_path`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 MAX_ROWS=100000000;

-- --------------------------------------------------------

--
-- 表的结构 `ticket`
--

CREATE TABLE IF NOT EXISTS `ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `token` varchar(60) NOT NULL,
  `ticket` varchar(255) NOT NULL COMMENT '自动登录票据',
  `expire` int(11) NOT NULL COMMENT '过期时间',
  `last_update` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket` (`ticket`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='登录票据' AUTO_INCREMENT=69664 ;

-- --------------------------------------------------------

--
-- 表的结构 `tmpuploadimg`
--

CREATE TABLE IF NOT EXISTS `tmpuploadimg` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pid` int(11) NOT NULL COMMENT '临时图片ID',
  `uid` int(11) NOT NULL COMMENT '用户ID',
  `uptime` int(11) NOT NULL COMMENT '上传时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7219 ;

-- --------------------------------------------------------

--
-- 表的结构 `tmp_event_20111225`
--

CREATE TABLE IF NOT EXISTS `tmp_event_20111225` (
  `uid` bigint(20) unsigned NOT NULL,
  `receiver_uid` bigint(20) unsigned NOT NULL,
  `open_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='圣诞活动';

-- --------------------------------------------------------

--
-- 表的结构 `upgrade`
--

CREATE TABLE IF NOT EXISTS `upgrade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appid` int(10) NOT NULL DEFAULT '0',
  `platform` varchar(20) DEFAULT NULL COMMENT '平台',
  `pre_version` varchar(20) NOT NULL,
  `version` varchar(20) DEFAULT NULL COMMENT '版本',
  `publish_date` int(10) DEFAULT NULL COMMENT '发布日期',
  `file_size` int(11) DEFAULT NULL COMMENT '文件大小',
  `download_url` varchar(255) DEFAULT NULL COMMENT '下载地址',
  `remark` text COMMENT '备注',
  `patch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否补丁',
  `force_update` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否强制升级',
  `view_version` varchar(50) NOT NULL COMMENT '显示版本号',
  `signed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否签名',
  `channel` varchar(200) NOT NULL DEFAULT '1' COMMENT '渠道',
  `mobile_brand` varchar(200) DEFAULT NULL COMMENT '机型',
  `alpha` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否内测',
  `ug_ext` varchar(5) DEFAULT NULL COMMENT '升级包扩展名',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=497 ;

-- --------------------------------------------------------

--
-- 表的结构 `upgrade_beta`
--

CREATE TABLE IF NOT EXISTS `upgrade_beta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appid` int(10) NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL,
  `platform` varchar(20) DEFAULT NULL COMMENT '平台',
  `version` varchar(20) DEFAULT NULL COMMENT '版本',
  `publish_date` int(10) DEFAULT NULL COMMENT '发布日期',
  `file_size` int(11) DEFAULT NULL COMMENT '文件大小',
  `download_url` varchar(255) DEFAULT NULL COMMENT '下载地址',
  `remark` text COMMENT '备注',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=276 ;

-- --------------------------------------------------------

--
-- 表的结构 `upgrade_brand`
--

CREATE TABLE IF NOT EXISTS `upgrade_brand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) DEFAULT NULL,
  `upgrade_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BRD` (`brand_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='升级包-机型表' AUTO_INCREMENT=8521 ;

-- --------------------------------------------------------

--
-- 表的结构 `url_click_statis`
--

CREATE TABLE IF NOT EXISTS `url_click_statis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `url_md5` varchar(35) DEFAULT NULL,
  `click` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- 表的结构 `userinfo`
--

CREATE TABLE IF NOT EXISTS `userinfo` (
  `uid` int(10) unsigned NOT NULL,
  `information` text COMMENT 'json格式 {books:计算机世界，movies:简爱}',
  `allowhomepage` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0为任何人，1为仅好友，2为隐藏',
  `allowcomment` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '0为任何人，1为仅好友，3为关闭留言和评论功能',
  `allowfriend` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0为任何人，1为仅好友的好友',
  `allowsearch` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0允许1不允许',
  `allowkeep` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0允许留下访问足迹，1不允许',
  `visitsnum` int(11) DEFAULT '0' COMMENT '访问量',
  `indexpermit` varchar(255) DEFAULT NULL COMMENT '首页的访问限制',
  `diarypermit` varchar(255) DEFAULT NULL COMMENT '日志的访问限制',
  `photopermit` varchar(255) DEFAULT NULL COMMENT '照片的访问限制',
  `recordpermit` varchar(255) DEFAULT NULL COMMENT '记录的访问限制',
  `permitshow` text COMMENT '用户信息字段好友可见和首页可见权限值json格式 {realnameshow:1,sexshow:1,realnamechk:1}',
  `contactshow` text COMMENT '用户联系方式字段可见权限和首页可见权限值json格式 {字段名:值,字段名:值..}',
  `updateinfo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '修改个人资料',
  `updateface` tinyint(1) NOT NULL DEFAULT '1' COMMENT '更新头像',
  `uploadphoto` tinyint(1) NOT NULL DEFAULT '1' COMMENT '上传新照片',
  `writeblog` tinyint(1) NOT NULL DEFAULT '1' COMMENT '发表新日记',
  `setupgroup` tinyint(1) NOT NULL DEFAULT '1' COMMENT '建立新群',
  `intogroup` tinyint(1) NOT NULL DEFAULT '1' COMMENT '加入某群',
  `addfriend` tinyint(1) NOT NULL DEFAULT '1' COMMENT '加某人为好友',
  `addmodule` tinyint(1) NOT NULL DEFAULT '1' COMMENT '添加新组件',
  `writemessage` tinyint(1) NOT NULL DEFAULT '1' COMMENT '在好友的留言板添加留言',
  `writecomment` tinyint(1) NOT NULL DEFAULT '1' COMMENT '在好友的日记、照片等添加评论',
  `firstavatar` tinyint(1) NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户信息，爱好等';

-- --------------------------------------------------------

--
-- 表的结构 `usermessage`
--

CREATE TABLE IF NOT EXISTS `usermessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(1000) NOT NULL,
  `addtime` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `owner` (`owner`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=882 ;

-- --------------------------------------------------------

--
-- 表的结构 `userschool`
--

CREATE TABLE IF NOT EXISTS `userschool` (
  `us_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `type` int(11) NOT NULL COMMENT '学校类型',
  `name` varchar(60) NOT NULL,
  `school_id` int(11) NOT NULL,
  `department` varchar(30) DEFAULT NULL,
  `class` varchar(45) DEFAULT NULL,
  `year` char(4) DEFAULT NULL,
  `ushow` tinyint(1) DEFAULT '1' COMMENT '谁可见',
  `usindexshow` tinyint(1) DEFAULT '1' COMMENT '是否显示在首页',
  PRIMARY KEY (`us_id`),
  KEY `type_schoolid_year_depert_uid` (`type`,`school_id`,`year`,`department`,`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户院校' AUTO_INCREMENT=327 ;

-- --------------------------------------------------------

--
-- 表的结构 `usersign`
--

CREATE TABLE IF NOT EXISTS `usersign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `createtime` int(10) DEFAULT NULL,
  `feed_id` char(32) DEFAULT NULL,
  `content` varchar(140) DEFAULT NULL,
  `client` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5483 ;

-- --------------------------------------------------------

--
-- 表的结构 `uservisit`
--

CREATE TABLE IF NOT EXISTS `uservisit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned DEFAULT NULL,
  `visitor` int(11) unsigned DEFAULT NULL,
  `visittime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27758 ;

-- --------------------------------------------------------

--
-- 表的结构 `userwork`
--

CREATE TABLE IF NOT EXISTS `userwork` (
  `uw_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `company` varchar(250) DEFAULT NULL COMMENT '公司名称',
  `department` varchar(45) DEFAULT NULL COMMENT '部门',
  `starttime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `ushow` tinyint(1) DEFAULT '1' COMMENT '谁可以看见',
  `uwindexshow` tinyint(1) DEFAULT '1' COMMENT '是否显示在首页，默认1 显示在首页',
  `flag` int(1) DEFAULT '1' COMMENT '1表示现在工作 2表示过去工作',
  `company_id` int(11) NOT NULL COMMENT '公司id',
  PRIMARY KEY (`uw_id`),
  KEY `companyid_uid` (`company_id`,`uid`),
  KEY `uid_flag_starttime` (`uid`,`flag`,`starttime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户工作信息' AUTO_INCREMENT=378 ;

-- --------------------------------------------------------

--
-- 表的结构 `user_dictionary`
--

CREATE TABLE IF NOT EXISTS `user_dictionary` (
  `user_id` int(10) unsigned NOT NULL,
  `encrypt_str` char(32) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`,`encrypt_str`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户字典表';

-- --------------------------------------------------------

--
-- 表的结构 `user_forget_password_log`
--

CREATE TABLE IF NOT EXISTS `user_forget_password_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '忘记密码ID',
  `mobile` char(11) DEFAULT NULL COMMENT '电话号码',
  `check_count` smallint(6) DEFAULT '0' COMMENT '验证次数',
  `success` tinyint(1) DEFAULT '0' COMMENT '是否成功',
  `dateline` int(11) DEFAULT '0' COMMENT '时间戳',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC COMMENT='忘记密码表' AUTO_INCREMENT=395 ;

-- --------------------------------------------------------

--
-- 表的结构 `user_ignore`
--

CREATE TABLE IF NOT EXISTS `user_ignore` (
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `ignored_ids` text COMMENT '忽略用户ID',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `user_last_login`
--

CREATE TABLE IF NOT EXISTS `user_last_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户ID',
  `source` int(11) DEFAULT NULL COMMENT '用户来源平台',
  `last_login_time` int(10) DEFAULT NULL COMMENT '最后登陆时间',
  `token` varchar(255) DEFAULT NULL COMMENT '用户token',
  PRIMARY KEY (`id`),
  KEY `IDX_UID_SRC_TKN` (`uid`,`source`,`token`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6049881 ;

-- --------------------------------------------------------

--
-- 表的结构 `user_link`
--

CREATE TABLE IF NOT EXISTS `user_link` (
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `fid` int(11) unsigned NOT NULL COMMENT '关联用户ID',
  `dateline` int(10) NOT NULL COMMENT '更新时间',
  `mobile` varchar(20) NOT NULL COMMENT '关联手机号',
  `zone_code` smallint(6) NOT NULL DEFAULT '86' COMMENT '国家码',
  PRIMARY KEY (`uid`,`fid`),
  KEY `IDX_UID` (`uid`),
  KEY `IDX_FID` (`fid`),
  KEY `IDX_MOBILE` (`mobile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户连接表';

-- --------------------------------------------------------

--
-- 表的结构 `user_password_reset_log`
--

CREATE TABLE IF NOT EXISTS `user_password_reset_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created` int(10) NOT NULL,
  `source` smallint(2) NOT NULL,
  `ip` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19758 ;

-- --------------------------------------------------------

--
-- 表的结构 `verifycode`
--

CREATE TABLE IF NOT EXISTS `verifycode` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appid` int(10) unsigned NOT NULL DEFAULT '0',
  `zone_code` varchar(30) NOT NULL,
  `mobile` char(11) NOT NULL,
  `verify_code` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL,
  `regip` varchar(15) NOT NULL,
  `regdate` int(10) NOT NULL,
  `verifydate` int(10) NOT NULL,
  `send_count` smallint(3) NOT NULL DEFAULT '1',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) NOT NULL DEFAULT '0' COMMENT '群组id',
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`,`regdate`,`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1255701 ;

-- --------------------------------------------------------

--
-- 表的结构 `vote`
--

CREATE TABLE IF NOT EXISTS `vote` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID,投票发起人',
  `choicelimit` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '可投选项,1:单选,2以上多选,最大20。',
  `votelimit` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '投票权限,0:不限;1:限女性;2:限男性。',
  `privacy` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '隐私权限,0为任何人可参与;1:仅好友可参与。',
  `votenums` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '投票参与人数',
  `endtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '截止日期',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加日期',
  `commentnums` int(11) unsigned NOT NULL DEFAULT '0',
  `allowshare` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `appoint_group` varchar(54) NOT NULL,
  `feed_id` char(32) DEFAULT NULL,
  `subject` varchar(50) NOT NULL COMMENT '标题',
  `summary` varchar(255) NOT NULL COMMENT '投票总结',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `endtime` (`endtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='投票列表' AUTO_INCREMENT=486 ;

-- --------------------------------------------------------

--
-- 表的结构 `vote_fields`
--

CREATE TABLE IF NOT EXISTS `vote_fields` (
  `vid` int(11) unsigned NOT NULL COMMENT '投票ID',
  `message` text NOT NULL COMMENT '投票内容',
  `vote` text NOT NULL,
  `itemdesc` text,
  PRIMARY KEY (`vid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='投票选项表';

-- --------------------------------------------------------

--
-- 表的结构 `vote_list`
--

CREATE TABLE IF NOT EXISTS `vote_list` (
  `vid` int(11) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`,`vid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `vote_records`
--

CREATE TABLE IF NOT EXISTS `vote_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '投票ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `addtime` int(10) unsigned NOT NULL COMMENT '投票时间',
  `votes` varchar(218) NOT NULL COMMENT '1，11，13',
  PRIMARY KEY (`id`),
  KEY `vid` (`vid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='投票用户表' AUTO_INCREMENT=1865 ;

-- --------------------------------------------------------

--
-- 表的结构 `wp_album`
--

CREATE TABLE IF NOT EXISTS `wp_album` (
  `album_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_name` varchar(100) NOT NULL,
  `user_id` int(11) unsigned DEFAULT '0',
  `pic_num` int(10) unsigned NOT NULL DEFAULT '0',
  `create_dt` int(10) NOT NULL,
  `update_dt` int(10) NOT NULL,
  `album_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1日记相册，2广播相册',
  `appid` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=383 ;

-- --------------------------------------------------------

--
-- 表的结构 `wp_pic`
--

CREATE TABLE IF NOT EXISTS `wp_pic` (
  `pic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `create_time` int(10) NOT NULL,
  `update_time` int(10) NOT NULL,
  `upload_ip` char(20) NOT NULL,
  `upload_file_name` varchar(100) NOT NULL,
  `file_md5` varchar(42) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT '0',
  `file_type` tinyint(2) NOT NULL DEFAULT '2' COMMENT '1 = GIF，2 = JPG，3 = PNG',
  `pic_width` int(11) NOT NULL DEFAULT '0',
  `pic_height` int(11) NOT NULL DEFAULT '0',
  `degree` int(4) NOT NULL DEFAULT '0' COMMENT '旋转角度0-0度,1右旋90,2右旋180,3右旋270',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '-1 审核不通过0 未审核1 已审核通过',
  `appid` int(4) NOT NULL DEFAULT '0' COMMENT '1标识为复制头像照',
  `is_animate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是gif动画',
  `privacy_lev` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1公开，2好友可见，3加密，4私密',
  PRIMARY KEY (`pic_id`),
  KEY `album_id` (`album_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33923 ;

-- --------------------------------------------------------

--
-- 表的结构 `wp_temp`
--

CREATE TABLE IF NOT EXISTS `wp_temp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file_md5` varchar(52) DEFAULT NULL,
  `upload_file_name` varchar(100) NOT NULL,
  `temp_file_path` varchar(64) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `update_time` int(10) DEFAULT NULL,
  `create_time` int(10) DEFAULT NULL,
  `upload_ip` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_dna` (`file_md5`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `yourlslog`
--

CREATE TABLE IF NOT EXISTS `yourlslog` (
  `click_id` int(11) NOT NULL AUTO_INCREMENT,
  `click_time` datetime NOT NULL,
  `shorturl` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `referrer` varchar(200) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `ip_address` varchar(41) NOT NULL,
  `country_code` char(2) NOT NULL,
  PRIMARY KEY (`click_id`),
  KEY `shorturl` (`shorturl`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=136509 ;

-- --------------------------------------------------------

--
-- 表的结构 `yourlsoptions`
--

CREATE TABLE IF NOT EXISTS `yourlsoptions` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(64) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  PRIMARY KEY (`option_id`,`option_name`),
  KEY `option_name` (`option_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- 表的结构 `yourlsurl`
--

CREATE TABLE IF NOT EXISTS `yourlsurl` (
  `keyword` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `url` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(41) NOT NULL,
  `clicks` int(10) unsigned NOT NULL,
  PRIMARY KEY (`keyword`),
  KEY `timestamp` (`timestamp`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
