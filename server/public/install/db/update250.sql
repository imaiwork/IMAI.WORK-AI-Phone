CREATE TABLE IF NOT EXISTS `la_sv_device_task` (
`id` int NOT NULL AUTO_INCREMENT,
`user_id` int DEFAULT '0' COMMENT '用户id',
`device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '设备号',
`task_type` tinyint DEFAULT '0' COMMENT '任务类型0未知1发布2接管3养号4获客5加微',
`account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '账号id',
`account_type` tinyint DEFAULT '0' COMMENT '账号类型0未知1微信视频号3小红书4抖音5快手',
`task_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '任务名称',
`status` tinyint DEFAULT '0' COMMENT '任务状态0等待中1执行中2执行完成3执行失败4中断',
`remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '备注',
`start_time` int DEFAULT NULL COMMENT '任务开始时间',
`end_time` int DEFAULT NULL COMMENT '任务结束时间',
`day` date DEFAULT NULL COMMENT '日期',
`sub_task_id` int DEFAULT '0' COMMENT '子任务id',
`source` tinyint DEFAULT NULL COMMENT '任务来源1发布2养号3接管4获客5加微',
`sub_data_id` int DEFAULT '0' COMMENT '执行任务记录id',
`create_time` int DEFAULT NULL COMMENT '创建时间',
`update_time` int DEFAULT NULL COMMENT '更新时间',
`delete_time` int DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `device_code` (`device_code`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `start_time` (`start_time`) USING BTREE,
KEY `end_time` (`end_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='设备任务执行表';

CREATE TABLE IF NOT EXISTS `la_sv_device_active` (
`id` int NOT NULL AUTO_INCREMENT,
`user_id` int DEFAULT '0' COMMENT '用户id',
`task_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '任务名称',
`accounts` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '账号集合',
`status` tinyint DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
`task_frep` tinyint DEFAULT '0' COMMENT '任务频率执行几天',
`custom_date` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '自定义日期',
`time_config` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '每日任务执行区间集合',
`create_time` int DEFAULT NULL COMMENT '创建时间',
`update_time` int DEFAULT NULL COMMENT '更新时间',
`delete_time` int DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='设备养号任务';

CREATE TABLE IF NOT EXISTS `la_sv_device_active_account` (
`id` int NOT NULL AUTO_INCREMENT,
`active_id` int DEFAULT '0' COMMENT '养号任务id',
`user_id` int DEFAULT '0' COMMENT '用户id',
`account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '账号id',
`account_type` tinyint DEFAULT '0' COMMENT '账号类型0未知1微信视频号3小红书4抖音5快手',
`device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '设备号',
`start_time` int DEFAULT NULL COMMENT '执行开始时间',
`end_time` int DEFAULT NULL COMMENT '执行结束时间',
`exec_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '执行动作内容',
`status` tinyint DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败4中断',
`duration` int DEFAULT '0' COMMENT '执行时长',
`action_times` int DEFAULT '0' COMMENT '动作次数',
`interactive_times` int DEFAULT '0' COMMENT '互动次数',
`create_time` int DEFAULT NULL COMMENT '创建时间',
`update_time` int DEFAULT NULL COMMENT '更新时间',
`delete_time` int DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='设备养号账号表';

CREATE TABLE IF NOT EXISTS `la_sv_device_take_over_task` (
`id` int NOT NULL AUTO_INCREMENT,
`user_id` int DEFAULT '0' COMMENT '用户id',
`task_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '任务名称',
`accounts` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '账号集合',
`status` tinyint DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
`task_frep` tinyint DEFAULT '0' COMMENT '任务频率执行几天',
`custom_date` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '自定义日期',
`time_config` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '每日任务执行区间集合',
`create_time` int DEFAULT NULL COMMENT '创建时间',
`update_time` int DEFAULT NULL COMMENT '更新时间',
`delete_time` int DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='设备接管任务';

CREATE TABLE IF NOT EXISTS `la_sv_device_take_over_task_account` (
`id` int NOT NULL AUTO_INCREMENT,
`take_over_id` int DEFAULT '0' COMMENT '接管任务id',
`user_id` int DEFAULT '0' COMMENT '用户id',
`account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '账号id',
`account_type` tinyint DEFAULT '0' COMMENT '账号类型0未知1微信视频号3小红书4抖音5快手',
`device_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '设备号',
`start_time` int DEFAULT NULL COMMENT '执行开始时间',
`end_time` int DEFAULT NULL COMMENT '执行结束时间',
`status` tinyint DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败4中断',
`create_time` int DEFAULT NULL COMMENT '创建时间',
`update_time` int DEFAULT NULL COMMENT '更新时间',
`delete_time` int DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='设备接管账号表';





ALTER TABLE `la_sv_crawling_task`
ADD COLUMN `time_config` varchar(500) NULL COMMENT '任务执行时间区间' AFTER `status`,
ADD COLUMN `start_time` int NULL COMMENT '执行开始时间' AFTER `time_config`,
ADD COLUMN `end_time` int NULL COMMENT '执行结束时间' AFTER `start_time`;


ALTER TABLE `la_knowledge_file_slice`
ADD INDEX(`index_id`) USING BTREE,
ADD INDEX(`file_id`) USING BTREE,
ADD INDEX(`user_id`) USING BTREE,
ADD INDEX(`hash`) USING BTREE;

ALTER TABLE `la_sv_crawling_manual_task`
ADD COLUMN `time_config` varchar(1000) NULL COMMENT '任务执行区间集合' AFTER `end_time`,
ADD COLUMN `task_frep` int NULL DEFAULT 0 COMMENT '任务频率执行几天' AFTER `time_config`,
ADD COLUMN `custom_date` varchar(1000) NULL COMMENT '自定义日期' AFTER `task_frep`,
ADD COLUMN `device_codes` varchar(1000) NULL COMMENT '设备号集合' AFTER `wechat_id`;


CREATE TABLE IF NOT EXISTS `la_sv_matrix_media_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`media_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '媒体类型:1视频2图片',
`media_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '媒体数量',
`media_url` text COMMENT '媒体url,json',
`copywriting` text COMMENT '文案json',
`extra` text COMMENT '附加字段内容,json',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='矩阵媒体设置表';


CREATE TABLE IF NOT EXISTS `la_sv_account_log` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`user_id` int DEFAULT NULL COMMENT '用户id',
`account` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '账号id',
`account_type` tinyint DEFAULT NULL COMMENT '账号类型3xhs 4dy 5ks',
`friend_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '好友',
`log_type` tinyint DEFAULT NULL COMMENT '日志类型5 私聊回复',
`create_time` int DEFAULT NULL COMMENT '创建时间',
`update_time` int DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='sv日志表';



ALTER TABLE `la_sv_device`
MODIFY COLUMN `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '设备状态 0: 下线 1: 在线2工作中' AFTER `device_model`,
ADD COLUMN `device_name` varchar(255) NULL COMMENT '设备名称' AFTER `user_id`,
ADD COLUMN `wechat_device_code` varchar(255) NULL COMMENT '个微设备码' AFTER `sdk_version`;

ALTER TABLE  `la_sv_publish_setting`
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布3矩阵发布',
ADD COLUMN `matrix_media_setting_id` int(11) NULL DEFAULT 0 COMMENT '矩阵媒体id';


ALTER TABLE `la_sv_publish_setting_account`
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布3矩阵发布',
ADD COLUMN `matrix_media_setting_id` int(11) NULL DEFAULT 0 COMMENT '矩阵媒体id' ;

ALTER TABLE `la_sv_publish_setting_detail`
MODIFY COLUMN `task_type` tinyint(4) NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布3矩阵发布',
ADD COLUMN `matrix_media_setting_id` int(11) NULL DEFAULT 0 COMMENT '矩阵媒体id';


DELETE FROM `la_model_config` WHERE `scene` = 'matrix_copywriting';
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('matrix_copywriting', 10104, '算力/条', 'Ai智能发布文案', 1.00, '1算力/条', 1, 1740799252, 1740799252);



INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('设备任务执行', 1, 0, '', 'task:scheduler', '', 1, '* * * * *', NULL, 1761620450, '0', '0', 1761620450, 1761620450, NULL);

ALTER TABLE `la_sv_private_message`
ADD COLUMN `reply_content` text NULL COMMENT '回复内容' AFTER `message_timer`,
ADD COLUMN `reply_time` datetime NULL COMMENT '回复时间' AFTER `reply_content`;


ALTER TABLE `la_sv_setting`
ADD COLUMN `account_type` int NULL DEFAULT 3 COMMENT '账号类型3小红书' AFTER `account`;

ALTER TABLE `la_user`
ADD COLUMN `device_bind_qrcode` varchar(200) NOT NULL DEFAULT '' COMMENT '设备绑定二维码',
ADD COLUMN `device_bind_num` tinyint(4) unsigned DEFAULT '0' COMMENT '设备绑定数量',
ADD COLUMN `device_bind_time` int(10) unsigned DEFAULT '0' COMMENT '设备绑定时间';

ALTER TABLE `la_sv_media_material`
ADD COLUMN `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '封面图' ;




DELETE FROM `la_system_menu` WHERE `id` = 369;
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (369, 368, 'C', '设备列表', '', 0, 'ai_application.device/lists', 'lists', 'ai_application/device/lists/index', '', '', 0, 1, 0, 1747033323, 1762831149);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (476, 368, 'M', '发布管理', '', 0, '', 'publish', 'publish', '', '', 0, 1, 0, 1762831220, 1762831220);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (477, 476, 'C', '小红书发布', '', 0, 'ai_application.device.publish/xhs', 'xhs', 'ai_application/device/publish/lists', '', 'type=3', 0, 1, 0, 1762831276, 1762909563);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (478, 476, 'C', '抖音发布', '', 0, 'ai_application.device.publish/dy', 'dy', 'ai_application/device/publish/lists', '', 'type=4', 0, 1, 0, 1762831324, 1762909566);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (479, 476, 'C', '快手发布', '', 0, 'ai_application.device.publish/ks', 'ks', 'ai_application/device/publish/lists', '', 'type=5', 0, 1, 0, 1762831353, 1762909570);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (480, 476, 'C', '视频号发布', '', 0, 'ai_application.device.publish/sph', 'sph', 'ai_application/device/publish/lists', '', 'type=1', 0, 1, 0, 1762831383, 1762909573);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (481, 477, 'A', '删除', '', 0, 'ai_application.device.publish.xhs/delete', '', '', '', '', 0, 1, 0, 1762831411, 1762831411);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (482, 478, 'A', '删除', '', 0, 'ai_application.device.publish.dy/delete', '', '', '', '', 0, 1, 0, 1762831425, 1762831425);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (483, 479, 'A', '删除', '', 0, 'ai_application.device.publish.dy/delete', '', '', '', '', 0, 1, 0, 1762831435, 1762831435);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (484, 480, 'A', '删除', '', 0, 'ai_application.device.publish.sph/delete', '', '', '', '', 0, 1, 0, 1762831445, 1762831445);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (485, 476, 'C', '小红书发布详情', '', 0, 'ai_application.device.publish.xhs/detail', 'xhs/detail', 'ai_application/device/publish/detail', '/ai_application/device/publish/xhs', 'type=3', 0, 0, 0, 1762831936, 1762909579);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (486, 485, 'A', '删除', '', 0, 'ai_application.device.publish.xhs.detail/delete', '', '', '', '', 0, 1, 0, 1762831960, 1762831960);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (487, 476, 'C', '抖音发布详情', '', 0, 'ai_application.device.publish.dy/detail', 'dy/detail', 'ai_application/device/publish/detail', '/ai_application/device/publish/dy', 'type=4', 0, 0, 0, 1762832005, 1762909583);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (488, 487, 'A', '删除', '', 0, 'ai_application.device.publish.xhs.detail/delete', '', '', '', '', 0, 1, 0, 1762832032, 1762832032);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (489, 476, 'C', '视频号发布详情', '', 0, 'ai_application.device.publish.sph/detail', 'sph/detail', 'ai_application/device/publish/detail', '/ai_application/device/publish/sph', 'type=1', 0, 0, 0, 1762832087, 1762909589);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (490, 489, 'A', '删除', '', 0, 'ai_application.device.publish.sph.detail/delete', '', '', '', '', 0, 1, 0, 1762832104, 1762832104);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (491, 476, 'C', '快手发布详情', '', 0, 'ai_application.device.publish.ks/detail', 'ks/detail', 'ai_application/device/publish/detail', '/ai_application/device/publish/ks', 'type=5', 0, 0, 0, 1762832155, 1762909593);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (492, 491, 'A', '删除', '', 0, 'ai_application.device.publish.ks.detail/delete', '', '', '', '', 0, 1, 0, 1762832170, 1762832170);

UPDATE `la_models_cost` SET `alias` = 'gemini-2.5-pro' WHERE `id` = 11;
UPDATE `la_models_cost` SET `alias` = 'gemma-3-4b-it' WHERE `id` = 14;