-- phpMyAdmin SQL Dump
-- version 3.5.3
-- http://www.phpmyadmin.net
--
-- ホスト: ec2-54-199-38-152.ap-northeast-1.compute.amazonaws.com
-- 生成日時: 2014 年 3 月 04 日 11:17
-- サーバのバージョン: 5.1.69
-- PHP のバージョン: 5.3.27

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- データベース: `mre_cbase_mysql`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `access_log`
--

CREATE TABLE IF NOT EXISTS `access_log` (
  `access_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `script_name` text COLLATE utf8_unicode_ci COMMENT 'ドメイン名以降のURL',
  `cdate` datetime DEFAULT NULL,
  `uid` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '`usr`.`uid` 、管理者ログの場合はNULL',
  `muid` mediumint(9) DEFAULT NULL COMMENT '`musr`.`muid` 、回答者ログの場合はNULL',
  `proxy_flg` text COLLATE utf8_unicode_ci COMMENT '0 : 通常ログイン\n1 : 代理ログイン',
  PRIMARY KEY (`access_log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `auth_set`
--

CREATE TABLE IF NOT EXISTS `auth_set` (
  `auth_set_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `evid` int(11) DEFAULT NULL COMMENT '対象evid(nullですべて)',
  `muid` int(11) NOT NULL COMMENT '権限所持者',
  `page` text COLLATE utf8_unicode_ci COMMENT '対象ページ',
  PRIMARY KEY (`auth_set_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=544 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `auth_set_div`
--

CREATE TABLE IF NOT EXISTS `auth_set_div` (
  `asd_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `muid` int(11) DEFAULT NULL,
  `div1` text COLLATE utf8_unicode_ci,
  `div2` text COLLATE utf8_unicode_ci,
  `div3` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`asd_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='360度_所属別管理権限' AUTO_INCREMENT=68 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `backup_data`
--

CREATE TABLE IF NOT EXISTS `backup_data` (
  `serial_no` char(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `cacheid` int(11) DEFAULT NULL,
  `rid` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bdid` char(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  `evid` int(11) DEFAULT NULL,
  `page` smallint(6) NOT NULL DEFAULT '1',
  `event_data_id` int(11) DEFAULT NULL,
  `restore_id` text COLLATE utf8_unicode_ci,
  `target` text COLLATE utf8_unicode_ci,
  UNIQUE KEY `bdid` (`bdid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `backup_event`
--

CREATE TABLE IF NOT EXISTS `backup_event` (
  `cacheid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `arrayserial` mediumtext COLLATE utf8_unicode_ci,
  `rid` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`cacheid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1878 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `choice`
--

CREATE TABLE IF NOT EXISTS `choice` (
  `evid` int(11) DEFAULT NULL,
  `seid` int(11) DEFAULT NULL,
  `num` smallint(6) DEFAULT NULL,
  `choice` text COLLATE utf8_unicode_ci,
  `divs` text COLLATE utf8_unicode_ci,
  UNIQUE KEY `evid` (`evid`,`seid`,`num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cond`
--

CREATE TABLE IF NOT EXISTS `cond` (
  `cnid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci,
  `strsql` text COLLATE utf8_unicode_ci,
  `pgcache` text COLLATE utf8_unicode_ci,
  `flgt` smallint(6) DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  `muid` int(11) DEFAULT NULL,
  PRIMARY KEY (`cnid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=135 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `div2`
--

CREATE TABLE IF NOT EXISTS `div2` (
  `div1` text NOT NULL COMMENT '所属(大)コード',
  `div1_name` text NOT NULL COMMENT '所属(大)表示名',
  `div1_sort` mediumint(9) NOT NULL COMMENT '所属(大)並び順',
  `div2` text NOT NULL COMMENT '所属(中)コード',
  `div2_name` text NOT NULL COMMENT '所属(中)表示名',
  `div2_sort` mediumint(9) NOT NULL COMMENT '所属(中)並び順',
  `div3` text NOT NULL COMMENT '所属(小)コード',
  `div3_name` text NOT NULL COMMENT '所属(小)表示名',
  `div3_sort` mediumint(9) NOT NULL COMMENT '所属(小)並び順'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='360度_所属マスタ';

-- --------------------------------------------------------

--
-- テーブルの構造 `divs`
--

CREATE TABLE IF NOT EXISTS `divs` (
  `div1` text NOT NULL COMMENT '所属(大)コード',
  `div1_name` text NOT NULL COMMENT '所属(大)表示名',
  `div1_sort` mediumint(9) NOT NULL COMMENT '所属(大)並び順',
  `div2` text NOT NULL COMMENT '所属(中)コード',
  `div2_name` text NOT NULL COMMENT '所属(中)表示名',
  `div2_sort` mediumint(9) NOT NULL COMMENT '所属(中)並び順',
  `div3` text NOT NULL COMMENT '所属(小)コード',
  `div3_name` text NOT NULL COMMENT '所属(小)表示名',
  `div3_sort` mediumint(9) NOT NULL COMMENT '所属(小)並び順',
  `div1_name_1` text,
  `div1_name_2` text,
  `div1_name_3` text,
  `div1_name_4` text,
  `div2_name_1` text,
  `div2_name_2` text,
  `div2_name_3` text,
  `div2_name_4` text,
  `div3_name_1` text,
  `div3_name_2` text,
  `div3_name_3` text,
  `div3_name_4` text,
  KEY `div_div1` (`div1`(200)),
  KEY `div_div2` (`div2`(200)),
  KEY `div_div3` (`div3`(200))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='360度_所属マスタ';

-- --------------------------------------------------------

--
-- テーブルの構造 `event`
--

CREATE TABLE IF NOT EXISTS `event` (
  `evid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `rid` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci,
  `type` mediumint(9) DEFAULT NULL,
  `flgs` mediumint(9) DEFAULT NULL,
  `flgl` mediumint(9) DEFAULT NULL,
  `flgo` mediumint(9) DEFAULT NULL,
  `limitc` mediumint(9) DEFAULT NULL,
  `point` mediumint(9) DEFAULT NULL,
  `mfid` mediumint(9) DEFAULT NULL,
  `htmlh` text COLLATE utf8_unicode_ci,
  `htmlm` text COLLATE utf8_unicode_ci,
  `htmlf` text COLLATE utf8_unicode_ci,
  `url` text COLLATE utf8_unicode_ci,
  `setting` text COLLATE utf8_unicode_ci,
  `sdate` datetime DEFAULT NULL,
  `edate` datetime DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  `muid` mediumint(9) DEFAULT NULL,
  `htmls` text COLLATE utf8_unicode_ci,
  `lastpage` mediumint(9) DEFAULT NULL,
  `randomize` text COLLATE utf8_unicode_ci,
  `mailaddress` text COLLATE utf8_unicode_ci,
  `mailname` text COLLATE utf8_unicode_ci,
  `id` text COLLATE utf8_unicode_ci,
  `pw` text COLLATE utf8_unicode_ci,
  `htmls2` text COLLATE utf8_unicode_ci,
  UNIQUE KEY `evid` (`evid`),
  UNIQUE KEY `rid` (`rid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=204 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `event_data`
--

CREATE TABLE IF NOT EXISTS `event_data` (
  `evid` mediumint(9) DEFAULT NULL,
  `serial_no` char(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `flg` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `answer_state` smallint(6) NOT NULL DEFAULT '0',
  `udate` datetime DEFAULT NULL COMMENT 'このデータの最終更新時刻',
  `event_data_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `target` text COLLATE utf8_unicode_ci,
  `ucount` smallint(6) NOT NULL DEFAULT '0' COMMENT '管理画面からコメントを書き換えた回数',
  UNIQUE KEY `event_data_id` (`event_data_id`),
  UNIQUE KEY `event_data_serial_no_evid_target_key` (`serial_no`,`evid`,`target`(200)),
  KEY `event_data_serial_no` (`serial_no`),
  KEY `event_data_evid` (`evid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=255 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `event_design`
--

CREATE TABLE IF NOT EXISTS `event_design` (
  `evid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `sa_title` text COLLATE utf8_unicode_ci,
  `sa_cheader` text COLLATE utf8_unicode_ci,
  `sa_cbody` text COLLATE utf8_unicode_ci,
  `sa_cother` text COLLATE utf8_unicode_ci,
  `sa_cfooter` text COLLATE utf8_unicode_ci,
  `ma_title` text COLLATE utf8_unicode_ci,
  `ma_cheader` text COLLATE utf8_unicode_ci,
  `ma_cbody` text COLLATE utf8_unicode_ci,
  `ma_cother` text COLLATE utf8_unicode_ci,
  `ma_cfooter` text COLLATE utf8_unicode_ci,
  `fa_title` text COLLATE utf8_unicode_ci,
  `fa_cheader` text COLLATE utf8_unicode_ci,
  `fa_cbody` text COLLATE utf8_unicode_ci,
  `fa_cfooter` text COLLATE utf8_unicode_ci,
  `mx_title` text COLLATE utf8_unicode_ci,
  `mx_cheader` text COLLATE utf8_unicode_ci,
  `mx_cbody` text COLLATE utf8_unicode_ci,
  `mx_cchoice` text COLLATE utf8_unicode_ci,
  `mx_cfooter` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`evid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `fromto`
--

CREATE TABLE IF NOT EXISTS `fromto` (
  `evid` mediumint(9) NOT NULL,
  `type` char(8) COLLATE utf8_unicode_ci NOT NULL,
  `sdate` datetime DEFAULT NULL,
  `edate` datetime DEFAULT NULL,
  UNIQUE KEY `evid_type` (`evid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `inquiry`
--

CREATE TABLE IF NOT EXISTS `inquiry` (
  `inqid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `uid` text COLLATE utf8_unicode_ci,
  `category` smallint(6) DEFAULT NULL,
  `method` smallint(6) DEFAULT NULL,
  `title` text COLLATE utf8_unicode_ci,
  `status` smallint(6) DEFAULT NULL,
  `muid` mediumint(9) DEFAULT NULL,
  `firstrsvdate` datetime DEFAULT NULL,
  `rsvdate` datetime DEFAULT NULL,
  `senddate` datetime DEFAULT NULL,
  PRIMARY KEY (`inqid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `mail_format`
--

CREATE TABLE IF NOT EXISTS `mail_format` (
  `mfid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci,
  `title` text COLLATE utf8_unicode_ci,
  `body` text COLLATE utf8_unicode_ci,
  `header` text COLLATE utf8_unicode_ci,
  `footer` text COLLATE utf8_unicode_ci,
  `file` text COLLATE utf8_unicode_ci,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  `muid` mediumint(9) DEFAULT NULL,
  `title_1` text COLLATE utf8_unicode_ci,
  `body_1` text COLLATE utf8_unicode_ci,
  `title_2` text COLLATE utf8_unicode_ci,
  `body_2` text COLLATE utf8_unicode_ci,
  `title_3` text COLLATE utf8_unicode_ci,
  `body_3` text COLLATE utf8_unicode_ci,
  `title_4` text COLLATE utf8_unicode_ci,
  `body_4` text COLLATE utf8_unicode_ci,
  `mfodr` mediumint(9) DEFAULT NULL,
  `file_1` text COLLATE utf8_unicode_ci,
  `file_2` text COLLATE utf8_unicode_ci,
  `file_3` text COLLATE utf8_unicode_ci,
  `file_4` text COLLATE utf8_unicode_ci,
  UNIQUE KEY `mfid` (`mfid`),
  KEY `mail_format_mfodr_key` (`mfodr`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `mail_log`
--

CREATE TABLE IF NOT EXISTS `mail_log` (
  `mrid` mediumint(9) DEFAULT NULL,
  `serial_no` char(8) COLLATE utf8_unicode_ci NOT NULL,
  `result` mediumint(9) DEFAULT NULL COMMENT '0=>失敗,1=>成功'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `mail_received`
--

CREATE TABLE IF NOT EXISTS `mail_received` (
  `mail_received_id` mediumint(9) NOT NULL AUTO_INCREMENT COMMENT '360度_受信メール',
  `mail_to` text CHARACTER SET utf8,
  `mail_from` text CHARACTER SET utf8,
  `title` text CHARACTER SET utf8,
  `body` text CHARACTER SET utf8,
  `cdate` datetime DEFAULT NULL,
  `rdate` datetime DEFAULT NULL COMMENT '送信日時',
  `response_flag` mediumint(9) DEFAULT NULL COMMENT '0->未返信 1->返信済',
  `response_status` mediumint(9) DEFAULT NULL COMMENT '10->未対応 20->対応済',
  PRIMARY KEY (`mail_received_id`),
  UNIQUE KEY `mail_received_id` (`mail_received_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `mail_rsv`
--

CREATE TABLE IF NOT EXISTS `mail_rsv` (
  `mrid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci,
  `mfid` mediumint(9) DEFAULT NULL,
  `cnid` mediumint(9) DEFAULT NULL,
  `flgs` mediumint(9) DEFAULT NULL,
  `flgl` mediumint(9) DEFAULT NULL,
  `mdate` datetime DEFAULT NULL,
  `count` mediumint(9) DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  `muid` mediumint(9) DEFAULT NULL,
  `evid` mediumint(9) DEFAULT NULL COMMENT 'evid',
  PRIMARY KEY (`mrid`),
  UNIQUE KEY `mrid` (`mrid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `message`
--

CREATE TABLE IF NOT EXISTS `message` (
  `msgid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `mkey` text COLLATE utf8_unicode_ci,
  `place1` text COLLATE utf8_unicode_ci,
  `place2` text COLLATE utf8_unicode_ci,
  `type` text COLLATE utf8_unicode_ci,
  `name` text COLLATE utf8_unicode_ci,
  `body_0` text COLLATE utf8_unicode_ci,
  `body_1` text COLLATE utf8_unicode_ci,
  `body_2` text COLLATE utf8_unicode_ci,
  `body_3` text COLLATE utf8_unicode_ci,
  `body_4` text COLLATE utf8_unicode_ci,
  `memo` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`msgid`),
  UNIQUE KEY `mkey` (`mkey`(100))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=680 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `musr`
--

CREATE TABLE IF NOT EXISTS `musr` (
  `muid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `id` text COLLATE utf8_unicode_ci NOT NULL,
  `pw` text COLLATE utf8_unicode_ci NOT NULL,
  `divs` text COLLATE utf8_unicode_ci,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `flg` smallint(6) NOT NULL,
  `permitted` text COLLATE utf8_unicode_ci,
  `email` text COLLATE utf8_unicode_ci,
  `permitted_column` text COLLATE utf8_unicode_ci NOT NULL,
  `pwmisscount` smallint(6) NOT NULL DEFAULT '0',
  `pdate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`muid`),
  KEY `musr_muid_key` (`muid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=78 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `project`
--

CREATE TABLE IF NOT EXISTS `project` (
  `project_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci,
  `mdate_1` date DEFAULT NULL,
  `mdate_2` date DEFAULT NULL,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  PRIMARY KEY (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `reissue_url`
--

CREATE TABLE IF NOT EXISTS `reissue_url` (
  `token` varchar(100) NOT NULL,
  `serial_no` char(8) DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0',
  `cdate` datetime DEFAULT NULL,
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `sessiondata`
--

CREATE TABLE IF NOT EXISTS `sessiondata` (
  `id` char(32) NOT NULL,
  `expiry` int(10) unsigned NOT NULL DEFAULT '0',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- テーブルの構造 `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `setting_id` mediumint(9) NOT NULL,
  `define` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `title` text COLLATE utf8_unicode_ci,
  `choice` text COLLATE utf8_unicode_ci,
  `explain` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `define` (`define`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `subevent`
--

CREATE TABLE IF NOT EXISTS `subevent` (
  `seid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `evid` mediumint(9) DEFAULT NULL,
  `title` text COLLATE utf8_unicode_ci,
  `type1` mediumint(9) DEFAULT NULL COMMENT '1->SA 2->MA 4->フリー',
  `type2` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `choice` text COLLATE utf8_unicode_ci,
  `hissu` mediumint(9) DEFAULT NULL,
  `width` mediumint(9) DEFAULT NULL,
  `rows` mediumint(9) DEFAULT NULL,
  `cond` text COLLATE utf8_unicode_ci,
  `page` mediumint(9) DEFAULT NULL,
  `other` mediumint(9) DEFAULT NULL,
  `html1` text COLLATE utf8_unicode_ci,
  `html2` text COLLATE utf8_unicode_ci,
  `cond2` text COLLATE utf8_unicode_ci,
  `cond3` text COLLATE utf8_unicode_ci,
  `cond4` text COLLATE utf8_unicode_ci,
  `cond5` text COLLATE utf8_unicode_ci,
  `ext` text COLLATE utf8_unicode_ci,
  `fel` smallint(6) DEFAULT NULL,
  `chtable` text COLLATE utf8_unicode_ci,
  `matrix` smallint(6) DEFAULT NULL,
  `randomize` text COLLATE utf8_unicode_ci COMMENT 'ハイフン区切り（1-2,3-4）',
  `cond360` text COLLATE utf8_unicode_ci COMMENT '360度用条件',
  `num` smallint(6) NOT NULL DEFAULT '0' COMMENT '集計/結果出力時の番号',
  `category1` text COLLATE utf8_unicode_ci,
  `category2` text COLLATE utf8_unicode_ci,
  `num_ext` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`seid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=200036 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `subevent_data`
--

CREATE TABLE IF NOT EXISTS `subevent_data` (
  `evid` mediumint(9) DEFAULT NULL,
  `serial_no` char(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `seid` mediumint(9) DEFAULT NULL,
  `choice` mediumint(9) DEFAULT NULL,
  `other` text COLLATE utf8_unicode_ci,
  `event_data_id` mediumint(9) DEFAULT NULL,
  UNIQUE KEY `event_data_id` (`event_data_id`,`seid`,`choice`),
  KEY `seidx2` (`seid`),
  KEY `subevent_data_event_data_id` (`event_data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `subinquiry`
--

CREATE TABLE IF NOT EXISTS `subinquiry` (
  `subinqid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `inqid` mediumint(9) DEFAULT NULL,
  `title` text COLLATE utf8_unicode_ci,
  `body` text COLLATE utf8_unicode_ci,
  `cdate` datetime DEFAULT NULL,
  `muid` mediumint(9) DEFAULT NULL,
  PRIMARY KEY (`subinqid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `uniqrestore`
--

CREATE TABLE IF NOT EXISTS `uniqrestore` (
  `restore_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `restore_id` (`restore_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `uniqserial`
--

CREATE TABLE IF NOT EXISTS `uniqserial` (
  `serial_no` char(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `us1` (`serial_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `unique_uid`
--

CREATE TABLE IF NOT EXISTS `unique_uid` (
  `uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `user_type`
--

CREATE TABLE IF NOT EXISTS `user_type` (
  `user_type_id` mediumint(9) NOT NULL,
  `name` text COLLATE utf8_unicode_ci,
  `admin_name` text COLLATE utf8_unicode_ci,
  `utype` smallint(6) NOT NULL DEFAULT '1' COMMENT '紐付けタイプ 0=>本人,1=>他者,2=>承認者,3=>参照者',
  PRIMARY KEY (`user_type_id`),
  UNIQUE KEY `name` (`name`(100)),
  UNIQUE KEY `admin_name` (`admin_name`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `usr`
--

CREATE TABLE IF NOT EXISTS `usr` (
  `div1` text COLLATE utf8_unicode_ci COMMENT '区分1',
  `div2` text COLLATE utf8_unicode_ci COMMENT '区分2',
  `div3` text COLLATE utf8_unicode_ci COMMENT '区分3',
  `usr_id` mediumint(9) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ユーザID',
  `email` text COLLATE utf8_unicode_ci COMMENT 'Eメールアドレス',
  `serial_no` char(8) COLLATE utf8_unicode_ci NOT NULL COMMENT 'シリアルナンバー',
  `name` text COLLATE utf8_unicode_ci COMMENT '名前1',
  `pw` text COLLATE utf8_unicode_ci COMMENT 'ログインPW',
  `evid` mediumint(9) DEFAULT NULL COMMENT 'アンケートID',
  `upload_id` text COLLATE utf8_unicode_ci COMMENT '登録ID',
  `note` text COLLATE utf8_unicode_ci COMMENT '備考',
  `mflag` smallint(6) NOT NULL DEFAULT '0' COMMENT '360度_本人フラグ',
  `sheet_type` smallint(6) NOT NULL DEFAULT '0' COMMENT '360度_シートタイプ',
  `pwmisscount` smallint(6) NOT NULL DEFAULT '0' COMMENT '360度_パスワード間違い回数',
  `select_status` smallint(6) NOT NULL DEFAULT '0' COMMENT '360度_選定状況フラグ',
  `memo` text COLLATE utf8_unicode_ci COMMENT '360度_メモ欄',
  `news_flag` smallint(6) NOT NULL DEFAULT '0' COMMENT '360度_お知らせ非表示フラグ',
  `name_` text COLLATE utf8_unicode_ci COMMENT '名前(ローマ字)',
  `lang_flag` smallint(6) DEFAULT '0' COMMENT '多言語対応 フラグ',
  `lang_type` smallint(6) DEFAULT '0' COMMENT '言語タイプ',
  `login_flag` smallint(6) DEFAULT '0' COMMENT 'ログインフラグ',
  `test_flag` smallint(6) NOT NULL DEFAULT '0' COMMENT '1=>テストユーザ',
  `ext1` text COLLATE utf8_unicode_ci,
  `ext2` text COLLATE utf8_unicode_ci,
  `ext3` text COLLATE utf8_unicode_ci,
  `ext4` text COLLATE utf8_unicode_ci,
  `ext5` text COLLATE utf8_unicode_ci,
  `ext6` text COLLATE utf8_unicode_ci,
  `ext7` text COLLATE utf8_unicode_ci,
  `ext8` text COLLATE utf8_unicode_ci,
  `ext9` text COLLATE utf8_unicode_ci,
  `ext10` text COLLATE utf8_unicode_ci,
  `class` text COLLATE utf8_unicode_ci,
  `pw_flag` smallint(6) NOT NULL DEFAULT '0' COMMENT 'パスワード変更フラグ',
  `send_mail_flag` smallint(6) NOT NULL DEFAULT '0' COMMENT 'メール送信停止フラグ 0=>送信, 1=>停止',
  PRIMARY KEY (`usr_id`),
  UNIQUE KEY `serial_no` (`serial_no`),
  UNIQUE KEY `uid` (`uid`),
  KEY `usr_div1` (`div1`(200)),
  KEY `usr_div2` (`div2`(200)),
  KEY `usr_div3` (`div3`(200)),
  KEY `mflag` (`mflag`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=652 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `usr_relation`
--

CREATE TABLE IF NOT EXISTS `usr_relation` (
  `uid_a` text COLLATE utf8_unicode_ci NOT NULL COMMENT '本人',
  `uid_b` text COLLATE utf8_unicode_ci NOT NULL COMMENT '評価者',
  `user_type` smallint(6) NOT NULL COMMENT '1=>上司 2=>部下 3=>同僚',
  `add_type` smallint(6) NOT NULL DEFAULT '0' COMMENT '追加タイプ 0=>選定, 1=>追加',
  UNIQUE KEY `uid_a` (`uid_a`(200),`uid_b`(200),`user_type`),
  KEY `usr_relation_uid_a` (`uid_a`(200)),
  KEY `usr_relation_uid_b` (`uid_b`(200)),
  KEY `user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='360度_ユーザの関連';

--
-- テーブルの構造 `import_file`
--
CREATE TABLE IF NOT EXISTS `import_file` (
  `file_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `cdate` datetime DEFAULT NULL,
  `udate` datetime DEFAULT NULL,
  `muid` mediumint(9) DEFAULT NULL,
  `last_file` text DEFAULT NULL,
  `exec_file` text DEFAULT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
