CREATE TABLE IF NOT EXISTS `la_auto_device_wechat_circle_config` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT NULL COMMENT '用户id',
`video_material` json DEFAULT NULL COMMENT '视频集合',
`image_material` json DEFAULT NULL COMMENT '图片集合',
`industry_type` varchar(1000) DEFAULT NULL COMMENT '行业类型',
`device_config_id` int(11) DEFAULT NULL COMMENT '自动化配置id',
`status` tinyint(4) unsigned DEFAULT '0' COMMENT '配置状态0待执行1执行中2执行完成3执行失败',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`is_ai` tinyint(4) unsigned DEFAULT '0' COMMENT 'Ai类型:0否,1是',
`exec_time` varchar(255) DEFAULT '["08:30-09:00"]' COMMENT '执行时间区间',
`remark` varchar(1000) DEFAULT NULL COMMENT '失败原因',
`exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备自动化配置子任务表';

CREATE TABLE IF NOT EXISTS `la_auto_device_circle_like_reply_config` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`is_like` tinyint(4) DEFAULT '0' COMMENT '自动点赞0否1是',
`is_comment` tinyint(4) DEFAULT '0' COMMENT '自动评论0否1是',
`comment_method` tinyint(4) DEFAULT '1' COMMENT '评论方式1智能拟人评论2固定话术随机',
`comment_robot_prompt` text COMMENT '评论机器人提示词',
`robot_params` json DEFAULT NULL COMMENT '机器人精准模式参数',
`number` int(11) DEFAULT '15' COMMENT '每天最大互动任务，默认15',
`comment_speech` json DEFAULT NULL COMMENT '评论固定话术',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
`exec_time` json DEFAULT NULL COMMENT '执行时间区间',
`exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
`is_first` tinyint(4) DEFAULT '0' COMMENT '是否初次',
`remark` text,
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `device_code` (`device_code`) USING BTREE,
KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='24小时朋友圈点赞评论配置表';

CREATE TABLE IF NOT EXISTS `la_distribution_agent` (
`id` int unsigned NOT NULL AUTO_INCREMENT primary key,
`user_id` int unsigned NOT NULL COMMENT '用户ID',
`level` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '分销等级: 0=普通用户, 1=一级代理, 2=二级代理, 3=三级代理',
`parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '直属上级用户ID',
`qr_code` varchar(255) NOT NULL DEFAULT '' COMMENT '联系二维码',
`status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '代理状态: 0=冻结, 1=正常',
`become_time` int unsigned DEFAULT '0' COMMENT '成为代理的时间',
`create_time` int unsigned DEFAULT '0' COMMENT '创建时间',
`update_time` int unsigned DEFAULT '0' COMMENT '最后更新时间',
UNIQUE KEY `user_id` (`user_id`),
KEY `idx_parent_id` (`parent_id`),
KEY `idx_level` (`level`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '分销代理及关系表';

CREATE TABLE IF NOT EXISTS `la_card_package` (
`id` int unsigned NOT NULL AUTO_INCREMENT primary key,
`name` varchar(64) NOT NULL COMMENT '套餐名称',
`tokens` int unsigned NOT NULL DEFAULT '0' COMMENT '套餐包含的算力点数',
`status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=启用',
`sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
`create_time` int unsigned DEFAULT '0',
`update_time` int unsigned DEFAULT '0',
`delete_time` int unsigned DEFAULT NULL,
KEY `idx_status_sort` (`status`, `sort`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '卡密套餐表';

CREATE TABLE IF NOT EXISTS `la_sv_device_task_log` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0',
`task_id` int(11) DEFAULT '0' COMMENT '任务详情id',
`task_source` tinyint(4) DEFAULT NULL COMMENT '任务场景',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`message` varchar(255) DEFAULT NULL COMMENT '日志消息',
`image` varchar(255) DEFAULT NULL COMMENT '日志截图',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE,
KEY `task_id` (`task_id`) USING BTREE,
KEY `task_type` (`task_source`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='发布日志记录表';

ALTER TABLE `la_card_code`
MODIFY COLUMN `type` tinyint(1) NOT NULL COMMENT '类型: 1-会员套餐; 2-充值套餐; 3-对话次数; 4-绘画次数; 5-卡密套餐',
Add COLUMN `used_num` int(8) DEFAULT 0 NOT NULL COMMENT '已使用次数' AFTER `card_num`,
ADD COLUMN `user_id` int(11) DEFAULT 0 NOT NULL COMMENT '用户ID: 0=系统生成; 其他=用户ID' AFTER `type`,
ADD COLUMN `package_id` int(11) DEFAULT 0 NOT NULL COMMENT '套餐ID' AFTER `user_id`;

ALTER TABLE `la_sv_device_circle_like_reply` 
ADD COLUMN `auto_reply_config_id` int NULL DEFAULT 0 COMMENT '24小时配置id' AFTER `comment`;

ALTER TABLE `la_sv_device_circle_like_reply_record` 
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 COMMENT '0手动1自动' AFTER `like_reply_account`;

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('wechat_circle', 'comment_speech', '[\"可以啊\",\"挺好的\",\"不错不错\",\"这个可以\",\"挺不错的\"]', 1773297822, 1773297822);

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (523, 438, 'M', '代理管理', '', 0, '', 'agent', '', '', '', 0, 1, 0, 1773280589, 1773280589);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (524, 523, 'C', '代理等级', '', 0, 'marketing.agent/grade', 'grade', 'marketing/agent/grade/index', '', '', 0, 1, 0, 1773280622, 1773281362);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (525, 524, 'A', '编辑', '', 0, 'marketing.agent.grade/setConfig', '', '', '', '', 0, 1, 0, 1773310344, 1773310344);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (526, 523, 'C', '代理详情', '', 0, 'marketing.agent/detail', 'detail', 'marketing/agent/lists/detail', '', '', 0, 0, 0, 1773310430, 1773310430);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (527, 526, 'A', '保存', '', 0, 'marketing.agent/setConfig', '', '', '', '', 0, 1, 0, 1773310589, 1773312250);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (528, 523, 'C', '代理下级详情', '', 0, 'marketing.agent/lower', 'lowerDetail', 'marketing/agent/lists/lower', '', '', 0, 0, 0, 1773312177, 1773312177);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (529, 528, 'A', '编辑', '', 0, 'marketing.agent.lower/detail', '', '', '', '', 0, 1, 0, 1773312269, 1773312269);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (530, 523, 'C', '代理套餐', '', 0, 'marketing.agent/package', 'package', 'marketing/agent/package/index', '', '', 0, 1, 0, 1773391756, 1773391756);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (531, 530, 'A', '新增', '', 0, 'marketing.agent.package/add', '', '', '', '', 0, 1, 0, 1773391800, 1773391800);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (532, 530, 'A', '编辑', '', 0, 'marketing.agent.package/edita', '', '', '', '', 0, 1, 0, 1773391809, 1773391809);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (533, 530, 'A', '删除', '', 0, 'marketing.agent.package/delete', '', '', '', '', 0, 1, 0, 1773391822, 1773391822);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (534, 236, 'A', '删除', '', 0, 'finance.marketing.rp/delete', '', '', '', '', 0, 1, 0, 1773392031, 1773392031);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (535, 236, 'A', '新增', '', 0, 'finance.marketing.rp/add', '', '', '', '', 0, 1, 0, 1773392135, 1773392135);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (536, 236, 'A', '编辑', '', 0, 'finance.marketing.rp/edit', '', '', '', '', 0, 1, 0, 1773392145, 1773392145);

ALTER TABLE `la_shanjian_video_task` 
ADD COLUMN `wechat_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '微信0否1是' ;
ALTER TABLE `la_shanjian_video_setting`
ADD COLUMN `wechat_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '微信0否1是' ;
