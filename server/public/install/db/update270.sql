CREATE TABLE IF NOT EXISTS `la_digital_human_anchor` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`user_id` int(10) NOT NULL COMMENT '用户ID',
`name` varchar(100) NOT NULL DEFAULT '' COMMENT '数字人名称',
`avatar` varchar(500) DEFAULT '' COMMENT '数字人头像',
`image` varchar(500) DEFAULT '' COMMENT '数字人封面',
`task_ids` varchar(255) DEFAULT '' COMMENT '形象生成任务id',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0生成中 1部分完成 2已完成 3生成失败',
`remark` varchar(255) DEFAULT '' COMMENT '失败原因',
`result_url` varchar(500) NOT NULL DEFAULT '' COMMENT '视频链接',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='公共数字人表';

CREATE TABLE IF NOT EXISTS `la_auto_device_active_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
  `exec_time` varchar(255) DEFAULT NULL COMMENT '执行时间区间',
  `exec_date` date DEFAULT NULL COMMENT '配置执行日期',
  `remark` varchar(1000) DEFAULT NULL COMMENT '备注',
  `is_first` tinyint(4) DEFAULT '0' COMMENT '是否是初次',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `exec_date` (`exec_date`) USING BTREE,
  KEY `is_first` (`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COMMENT='自动养号配置';

CREATE TABLE IF NOT EXISTS `la_auto_device_add_wechat_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
  `exec_time` varchar(255) DEFAULT NULL COMMENT '执行时间区间',
  `exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
  `speech_type` tinyint(4) DEFAULT '1' COMMENT '自动触达话术类型1固定话术2ai回复3ai根据固定话术自动优化',
  `remarks` json DEFAULT NULL COMMENT '备注',
  `is_first` tinyint(4) DEFAULT '0' COMMENT '是否是初次',
  `result` varchar(1000) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `exec_date` (`exec_date`) USING BTREE,
  KEY `is_first` (`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COMMENT='自动养号配置';

CREATE TABLE IF NOT EXISTS `la_auto_device_clue_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `exec_type` tinyint(4) DEFAULT '1' COMMENT '执行访问1循环执行2ai自动填充3不执行',
  `clue_theme` varchar(1000) DEFAULT NULL COMMENT '线索主题',
  `keywords` json DEFAULT NULL COMMENT '生成的关键词',
  `status` tinyint(4) DEFAULT NULL COMMENT '状态0待执行1执行中2执行完成3执行失败',
  `exec_time` varchar(255) DEFAULT NULL COMMENT '执行时间区间',
  `exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `is_first` tinyint(4) DEFAULT '0' COMMENT '是否是初次',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `exec_date` (`exec_date`) USING BTREE,
  KEY `is_first` (`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COMMENT='自动化关键词获客配置';

CREATE TABLE IF NOT EXISTS `la_auto_device_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `human_image` json DEFAULT NULL COMMENT '数字人形象集合',
  `clip_material` json DEFAULT NULL COMMENT '剪辑素材集合',
  `image_material` json DEFAULT NULL COMMENT '图片集合',
  `clue_theme` varchar(1000) DEFAULT NULL COMMENT '线索主题',
  `video_theme` varchar(1000) DEFAULT NULL COMMENT '视频营销主题',
  `text_theme` varchar(1000) DEFAULT NULL COMMENT '图文营销主题',
  `status` tinyint(4) DEFAULT NULL COMMENT '配置状态0待执行1执行中2执行完成3执行失败',
  `remark` varchar(1000) DEFAULT NULL COMMENT '失败原因',
  `is_first` tinyint(4) DEFAULT '0' COMMENT '是否是初次',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `is_first` (`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COMMENT='设备自动化配置表';



CREATE TABLE IF NOT EXISTS `la_auto_device_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '平台类型:1视频号3小红书4抖音5快手',
  `device_config_id` int(11) DEFAULT NULL COMMENT '自动化配置id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `human_image` json DEFAULT NULL COMMENT '数字人形象集合',
  `clip_material` json DEFAULT NULL COMMENT '剪辑素材集合',
  `image_material` json DEFAULT NULL COMMENT '图片集合',
  `video_theme` varchar(1000) DEFAULT NULL COMMENT '营销主题',
  `text_theme` varchar(1000) DEFAULT NULL COMMENT '营销主题',
  `status` tinyint(4) DEFAULT NULL COMMENT '配置状态0待执行1执行中2执行完成3执行失败',
  `execution_day` date DEFAULT NULL COMMENT '执行日期',
  `remark` varchar(1000) DEFAULT NULL COMMENT '失败原因',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `device_config_id` (`device_config_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='设备自动化配置子任务表';

CREATE TABLE IF NOT EXISTS `la_auto_device_take_over_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
  `exec_time` varchar(255) DEFAULT NULL COMMENT '执行时间区间',
  `exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
  `remark` varchar(1000) DEFAULT NULL COMMENT '备注',
  `robot_id` int(11) DEFAULT '0' COMMENT '智能体id',
  `is_first` tinyint(4) DEFAULT '0' COMMENT '是否是初次',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `exec_date` (`exec_date`) USING BTREE,
  KEY `is_first` (`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COMMENT='自动私信接管配置';


CREATE TABLE IF NOT EXISTS `la_auto_device_touch_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `mode` tinyint(4) DEFAULT '1' COMMENT '模式1简易模式2精确模式',
  `exec_type` tinyint(4) DEFAULT '0' COMMENT '执行类型1循环执行2ai自动填充3不执行',
  `ai_direction` varchar(255) DEFAULT NULL COMMENT 'ai自动补充方向',
  `clue_theme` varchar(255) DEFAULT NULL COMMENT '获客主题词',
  `keywords` json DEFAULT NULL COMMENT '关键词',
  `comment_screening` json DEFAULT NULL COMMENT '评论筛选',
  `touch_speech_type` tinyint(4) DEFAULT '0' COMMENT '自动触达话术类型1固定话术2ai回复3ai根据固定话术自动优化',
  `touch_speech` json DEFAULT NULL COMMENT '自动话术集合',
  `actions` json DEFAULT NULL COMMENT '附加动作',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
  `exec_time` json DEFAULT NULL COMMENT '执行时间区间',
  `exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
  `remark` text,
  `is_first` tinyint(4) DEFAULT '0' COMMENT '是否是初次',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `exec_date` (`exec_date`) USING BTREE,
  KEY `is_first` (`is_first`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COMMENT='自动截流任务配置';

CREATE TABLE IF NOT EXISTS `la_sv_lead_scraping_industry_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
  `keyword` varchar(500) NOT NULL DEFAULT '' COMMENT '关键词',
  `scraping_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '截流ID来源',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE,
  KEY `idx_scraping_id` (`scraping_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='截流获客行业表';


CREATE TABLE IF NOT EXISTS `la_sv_lead_scraping_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
  `scraping_id` int(11) NOT NULL DEFAULT '0' COMMENT '截流id',
  `scraping_account_id` int(11) NOT NULL DEFAULT '0' COMMENT '截流账号表id',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0未发送1已发送2发送失败3发送中4已删除',
  `account` varchar(255) DEFAULT NULL COMMENT '平台账号id',
  `account_name` varchar(255) DEFAULT NULL COMMENT '账号名称',
  `account_type` tinyint(4) DEFAULT '0' COMMENT '平台账号类型 3小红书4抖音5快手',
  `platform` tinyint(4) DEFAULT '0' COMMENT '发布平台 3小红书4抖音5快手',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
  `task_id` varchar(255) DEFAULT NULL COMMENT '任务id',
  `extra` text COMMENT '扩展字段',
  `remark` text COMMENT '备注,保存发布失败原因',
  `send_time` datetime DEFAULT NULL COMMENT '发布时间,内容待发布时间',
  `exec_time` int(11) DEFAULT NULL COMMENT '任务执行时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  `content` text COMMENT '内容',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `account_type` (`account_type`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE,
  KEY `idx_scraping_id` (`scraping_id`) USING BTREE,
  KEY `idx_scraping_account_id` (`scraping_account_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='截流记录表';


CREATE TABLE IF NOT EXISTS `la_sv_lead_scraping_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务状态0草稿1待执行2执行中3已完成',
  `accounts` varchar(500) DEFAULT '' COMMENT '账号集合',
  `account_feature` tinyint(4) DEFAULT '0' COMMENT '账号特征0全部1跳过认证号',
  `industry` text COMMENT '行业',
  `industry_num` int(10) DEFAULT '3' COMMENT '行业笔记查阅数量',
  `filter` text COMMENT '截取内容筛选',
  `content` text COMMENT '发送内容',
  `send_num` int(10) DEFAULT '3' COMMENT '发送数量上限',
  `is_like` tinyint(1) DEFAULT '0' COMMENT '是否点赞0否1是',
  `is_follow` tinyint(1) DEFAULT '0' COMMENT '是否关注0否1是',
  `send_time` int(4) DEFAULT NULL COMMENT '发送时间，评论或私信n天内',
  `gender` enum('男','女','不限') DEFAULT '不限' COMMENT '性别',
  `old` varchar(50) DEFAULT '18-24' COMMENT '年龄',
  `region` varchar(50) DEFAULT NULL COMMENT '地区',
  `task_start_time` int(11) DEFAULT NULL COMMENT '任务开始时间',
  `task_end_time` int(11) DEFAULT NULL COMMENT '任务结束时间',
  `task_frequency` tinyint(4) DEFAULT '1' COMMENT '任务频率，最大值30',
  `time_config` varchar(500) DEFAULT NULL COMMENT '每日推送时间设置',
  `task_date` varchar(1000) DEFAULT '' COMMENT '任务执行日期',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='截流设置表';

CREATE TABLE IF NOT EXISTS `la_sv_lead_scraping_setting_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
  `scraping_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布设置id',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0未开启 1运行中 2已完成 3已删除 4暂停中',
  `name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `account` varchar(255) DEFAULT NULL COMMENT '账号id',
  `account_type` tinyint(3) unsigned DEFAULT '3' COMMENT '账号类型3小红书4视频号5快手',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
  `send_start_time` int(11) DEFAULT NULL COMMENT '发布开始日期',
  `send_end_time` int(11) DEFAULT NULL COMMENT '发布结束日期',
  `count` int(11) DEFAULT '0' COMMENT '发送总数',
  `published_count` int(11) DEFAULT '0' COMMENT '已发送数',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE,
  KEY `idx_scraping_id` (`scraping_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=663 DEFAULT CHARSET=utf8mb4 COMMENT='截流账号信息表';


CREATE TABLE IF NOT EXISTS `la_sora_anchor` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '角色名',
`task_id` varchar(255) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0生成中1成功2失败',
`anchor_url` varchar(500) NOT NULL DEFAULT '' COMMENT '角色视频地址',
`pic` varchar(500) NOT NULL DEFAULT '' COMMENT '封面',
`sora_task_id` varchar(50) DEFAULT '' COMMENT 'sora唯一任务ID',
`anchor_id` varchar(50) DEFAULT '' COMMENT '角色id',
`remark` varchar(500) DEFAULT '' COMMENT '失败原因',
`token` varchar(10) DEFAULT '' COMMENT '消耗',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
`anchor_url_start` tinyint(3) DEFAULT '0' COMMENT '视频截取开始时间',
`anchor_url_end` tinyint(3) DEFAULT '0' COMMENT '视频截取结束时间',
`is_public` tinyint(1) DEFAULT '0' COMMENT '0不公开 1公开',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='Sora角色表';

ALTER TABLE `la_human_anchor`
ADD COLUMN `dh_id` int(11) DEFAULT '0' COMMENT '数字人公共外键ID';
ALTER TABLE `la_shanjian_anchor`
ADD COLUMN `dh_id` int(11) DEFAULT '0' COMMENT '数字人公共外键ID';




ALTER TABLE `la_sv_publish_setting` 
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布3矩阵发布4sora99自动' AFTER `user_id`;
ALTER TABLE `la_sv_publish_setting_account` 
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布3矩阵发布4sora99自动' AFTER `publish_id`;
ALTER TABLE `la_sv_publish_setting_detail` 
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布3矩阵发布4sora99自动' AFTER `video_setting_id`;



ALTER TABLE `la_shanjian_video_setting` 
ADD COLUMN `device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备号',
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是' ;
ALTER TABLE `la_shanjian_video_task` 
ADD COLUMN `device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备号',
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是',
ADD COLUMN `is_publish` tinyint NULL DEFAULT 0 COMMENT '是否已生成发布记录1是0否' AFTER `tries`,
ADD INDEX(`auto_type`) USING BTREE,
ADD INDEX(`device_code`) USING BTREE,
ADD INDEX(`video_setting_id`) USING BTREE,
ADD INDEX(`is_publish`) USING BTREE;

ALTER TABLE `la_sv_video_setting` 
ADD COLUMN `device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备号',
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是' ;
ALTER TABLE `la_sv_video_task` 
ADD COLUMN `device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备号',
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是',
ADD COLUMN `is_publish` tinyint NULL DEFAULT 0 COMMENT '是否已生成发布记录1是0否' AFTER `auto_type`;

ALTER TABLE `la_hd_puzzle_setting` 
ADD COLUMN `device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备号',
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是' ;
ALTER TABLE `la_hd_puzzle` 
ADD COLUMN `device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '设备号',
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是',
ADD COLUMN `is_publish` tinyint NULL DEFAULT 0 COMMENT '是否已生成发布记录1是0否' AFTER `remark`;


ALTER TABLE `la_sv_device_active` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `task_name`;

ALTER TABLE `la_sv_device_active_account` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `account_type`;

ALTER TABLE `la_sv_crawling_task` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `name`,
ADD COLUMN `source` tinyint NULL DEFAULT 1 COMMENT '任务来源1手动创建2自动化任务创建' AFTER `keywords`;

ALTER TABLE `la_sv_crawling_task_device_bind` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `task_id`;

ALTER TABLE `la_sv_device` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `wechat_device_code`;

ALTER TABLE `la_sv_device_task` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `task_name`,
ADD COLUMN `task_scene` tinyint NULL DEFAULT 0 COMMENT '任务场景1评论区评论2评论区私信3留痕获客4视频号获客5内容发布' AFTER `auto_type`,
ADD COLUMN `time_config` varchar(255) NULL COMMENT '时间区间' AFTER `task_scene`;


ALTER TABLE `la_sv_device_take_over_task` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `accounts`;

ALTER TABLE `la_sv_device_take_over_task_account` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `account_type`;


ALTER TABLE `la_sv_publish_setting` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `accounts`;

ALTER TABLE `la_sv_publish_setting_account` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `account_type`;

ALTER TABLE `la_sv_publish_setting_detail` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '是否自动1自动0手动' AFTER `account_type`;


ALTER TABLE `la_sv_device_task` 
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 0 COMMENT '任务类型0未知1发布2接管3养号4获客5加微6截流获客' AFTER `device_code`,
MODIFY COLUMN `source` tinyint(4) NULL DEFAULT NULL COMMENT '任务来源1发布2养号3接管4获客5加微6截流获客' AFTER `sub_task_id`;


ALTER TABLE `la_sv_device_take_over_task_account` 
ADD COLUMN `robot_id` int NULL DEFAULT 0 COMMENT '智能体' AFTER `device_code`;

ALTER TABLE `la_human_video_task` 
ADD COLUMN `download_tries` tinyint NULL DEFAULT 0 COMMENT '下载尝试次数' AFTER `music_type`;

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('24小时自动化任务', 1, 0, '', 'auto_task:scheduler', '', 1, '* * * * *', '', 1766734324, '0.01', '0.54', 1766474228, 1766734229, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('24小时自动化任务生成', 1, 0, '', 'auto_device_create_cron', '', 1, '0 0 * * *', NULL, 1766678409, '1.91', '1.91', 1766542031, 1766734271, NULL);
INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('24小时自动化任务初次生成', 1, 0, '', 'auto_device_frist_create_cron', '', 1, '*/30 * * * *', '', 1766892904, '0.1', '0.64', 1766834374, 1766894382, NULL);

UPDATE `la_config` SET `value` = '{\"channel\":[{\"id\":\"1\",\"name\":\"标准版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"\\/static\\/images\\/human\\/1.png\",\"status\":\"1\",\"model_id\":19},{\"id\":\"2\",\"name\":\"极致版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"\\/static\\/images\\/human\\/2.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"4\",\"name\":\"优秘V5\",\"described\":\"满足多场景运用，助力企业打造沉浸式体验\",\"icon\":\"\\/static\\/images\\/human\\/4.png\",\"status\":\"1\",\"model_id\":8},{\"id\":\"6\",\"name\":\"优秘V7\",\"described\":\"高度还原，打造独一无二的虚拟代言人\",\"icon\":\"\\/static\\/images\\/human\\/6.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"7\",\"name\":\"禅镜\",\"described\":\"为数字化浪潮高频迭代的内容营销提供强劲驱动力\",\"icon\":\"\\/static\\/images\\/human\\/7.png\",\"status\":\"1\",\"model_id\":7},{\"id\":\"8\",\"name\":\"闪剪\",\"described\":\"适配多渠道发布，构建全域内容传播矩阵\",\"icon\":\"\\/static\\/images\\/human\\/8.png\",\"status\":\"1\",\"model_id\":9}],\"voice\":[{\"name\":\"智小敏(女)\",\"code\":\"10000\",\"status\":\"1\"},{\"name\":\"智小柔(女)\",\"code\":\"10001\",\"status\":\"1\"},{\"name\":\"智小满(女)\",\"code\":\"10002\",\"status\":\"1\"},{\"name\":\"爱小芊(女)\",\"code\":\"10003\",\"status\":\"1\"},{\"name\":\"爱小静(女)\",\"code\":\"10004\",\"status\":\"1\"},{\"name\":\"千嶂(男)\",\"code\":\"10005\",\"status\":\"1\"},{\"name\":\"智皓(男)\",\"code\":\"10006\",\"status\":\"1\"},{\"name\":\"爱小杭(男)\",\"code\":\"10007\",\"status\":\"1\"},{\"name\":\"爱小辰(男)\",\"code\":\"10008\",\"status\":\"1\"},{\"name\":\"飞镜(男)\",\"code\":\"10009\",\"status\":\"1\"}]}' WHERE `type` = 'model' and`name` = 'list';
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (19, 4, '微聚云科', 'static/images/human/1.png', '微聚云科', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (19, 19, 1, '微聚云科', '微聚云科', '微聚云科',0.0000, 0, 1, 1755929617);

UPDATE `la_config` SET `value` = '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"gpt-4","model_id":15,"model_sub_id":15,"status":"1","logo":"static/images/models/3.png"},{"id":"2","name":"gpt-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"gpt-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"gpt-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"3","name":"Gemini 2.5 pro","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"Gemma 3","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"},{"id":"10","name":"claude-sonnet-4-5","model_id":18,"model_sub_id":18,"status":"1","logo":"static/images/models/4.png"}]}' WHERE `type` = 'chat' AND `name` = 'ai_model';
UPDATE `la_models` SET `logo` = 'static/images/models/4.png' WHERE `id` = 18;


INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ('touch_clue', 'comment_screening', '[\"多少钱\",\"多少米 / 几米\",\"价格\",\"报价\",\"咋卖\",\"怎么卖\",\"收费吗\",\"贵不贵\",\"有没有优惠\",\"能便宜点吗\",\"私我\",\"私\",\"私聊\",\"私信\",\"v我\",\"加v\",\"vx\",\"微\",\"微信里说\",\"拉我\",\"联系方式\",\"怎么弄\",\"怎么做\",\"教教我\",\"求教程\",\"怎么操作\",\"有教程吗\",\"可以详细说下吗\",\"怎么用\",\"怎么实现\",\"靠谱吗\",\"好用吗\",\"有效果吗\",\"真的吗\",\"能行吗\",\"有人用过吗\",\"蹲\",\"蹲蹲\",\"蹲一个\",\"马一下\",\"留个\",\"码住\",\"求带\",\"带带\",\"up\",\"顶一下\",\"地址\",\"在哪\",\"店在哪\",\"附近有吗\"]', 1766910399, 1766910399);
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('touch_clue', 'touch_speech', '[\"回关一下我\",\"可以联系我\",\"私聊下？\",\"方便聊下吗\",\"这边可以私聊\",\"可以说下你的情况吗\",\"方便私下说吗\",\"私下聊会更方便\",\"要不私聊下\"]', 1766910489, 1766910489);


UPDATE `la_chat_prompt` SET `prompt_text` = '你的角色设定如下：
【角色设定】
用户提出了如下问题：
【用户发送的内容】
历史上下文：
【历史对话上下文】
相关参考内容检索结果：
【相关知识库检索结果】' WHERE `prompt_name` = '小红书';

INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'human_avatar_sora', 10108, '算力/次', 'AI智能一句话创建角色', 10.00, '每次消耗10算力', 1, 1740799252, 1740799252);

DELETE FROM `la_system_menu` WHERE `id` IN (520, 521, 522);
INSERT INTO `la_system_menu` VALUES (520, 461, 'C', '角色列表', '', 0, 'ai_application.montage.role', 'role', 'ai_application/montage/role/index', '', '', 0, 1, 0, 1767003659, 1767003659);
INSERT INTO `la_system_menu` VALUES (521, 520, 'A', '保存', '', 0, 'ai_application.montage.role/setConfig', '', '', '', '', 0, 1, 0, 1767009357, 1767009357);
INSERT INTO `la_system_menu` VALUES (522, 520, 'A', '删除', '', 0, 'ai_application.montage.role/delete', '', '', '', '', 0, 1, 0, 1767009366, 1767009366);



ALTER TABLE `la_user`
ADD COLUMN `last_bind_device_code` varchar(255) DEFAULT '' COMMENT '最后绑定的设备码';
