CREATE TABLE `la_ai_wechat_circle_task_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `task_name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `wechat_ids` json DEFAULT NULL COMMENT '微信ID',
  `content` text COMMENT '内容',
  `attachment_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '附件类型 0: 纯文本 1：图片 2：短视频 3：长视频 4：链接 5：小程序',
  `attachment_content` json DEFAULT NULL COMMENT '附件内容',
  `comment` json DEFAULT NULL COMMENT '评论',
  `send_time` varchar(20) NOT NULL DEFAULT '' COMMENT '发送时间',
  `date` date DEFAULT NULL COMMENT '发送日期',
  `time_config` varchar(255) DEFAULT NULL COMMENT '时间配置',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0:草稿 1待执行 2执行中 3执行完成',
  `auto_type` tinyint(4) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='微信自动发送朋友圈任务配置表';

CREATE TABLE `la_sv_device_circle_like_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `auto_type` tinyint(4) DEFAULT '0',
  `task_name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `accounts` json DEFAULT NULL COMMENT '账号集合',
  `task_frep` int(11) DEFAULT '0' COMMENT '任务频率',
  `custom_date` varchar(1000) DEFAULT NULL COMMENT '自定义日期',
  `time_config` json DEFAULT NULL COMMENT '执行时间区间',
  `action` tinyint(4) DEFAULT '0' COMMENT '执行动作1仅评论2仅点赞3评论点赞',
  `number` int(11) DEFAULT '0' COMMENT '每个好友当前任务互动数量',
  `interval` int(11) DEFAULT '0' COMMENT '每次互动间隔',
  `range` tinyint(4) DEFAULT '0' COMMENT '执行范围0仅当天1三天内2七天内',
  `robot_id` int(11) DEFAULT '0' COMMENT '评论智能体',
  `comment_type` tinyint(4) DEFAULT '0' COMMENT '图片视频朋友圈0ai识别并评论1不评论2固定评论',
  `comment` varchar(1000) DEFAULT NULL COMMENT '固定评论内容',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='朋友圈点赞评论任务表';

CREATE TABLE `la_sv_device_circle_like_reply_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `circle_like_reply_id` int(11) DEFAULT '0' COMMENT '点赞评论任务id',
  `user_id` int(11) DEFAULT '0',
  `device_code` varchar(255) DEFAULT NULL,
  `auto_type` tinyint(4) DEFAULT '0',
  `task_name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `account` varchar(255) DEFAULT NULL COMMENT '账号集合',
  `account_type` int(11) DEFAULT '0' COMMENT '账号类型',
  `start_time` int(11) DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) DEFAULT '0' COMMENT '结束时间',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败4中断',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='朋友圈点赞评论任务表';

CREATE TABLE `la_sv_device_circle_like_reply_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `like_reply_account` int(11) DEFAULT '0' COMMENT '任务id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `account` varchar(255) DEFAULT NULL COMMENT '执行账号',
  `nickname` varchar(255) DEFAULT NULL COMMENT '好友名称',
  `content` text COMMENT '朋友圈文本内容',
  `comment` text COMMENT '评论内容',
  `task_id` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='朋友圈点赞评论记录表';


CREATE TABLE `la_sv_crawling_wechat_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `craw_task_ids` json DEFAULT NULL COMMENT '获客任务id集合',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `time_config` json DEFAULT NULL COMMENT '时间区间',
  `start_time` int(11) DEFAULT NULL COMMENT '开始时间',
  `end_time` int(11) DEFAULT NULL COMMENT '结束时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='获客加微任务表';

ALTER TABLE `la_ai_wechat_circle_task` 
ADD COLUMN `task_config_id` int NULL DEFAULT 0 COMMENT '任务配置id' AFTER `user_id`,
ADD COLUMN `device_code` varchar(255) NULL COMMENT '设备id' AFTER `task_config_id`,
ADD COLUMN `auto_type` tinyint NULL DEFAULT 0 AFTER `device_code`,
ADD COLUMN `task_name` varchar(255) NULL COMMENT '任务名称' AFTER `user_id`;


ALTER TABLE `la_sv_crawling_task` 
ADD COLUMN `is_wechat_task` tinyint NULL DEFAULT 0 COMMENT '是否生成微信加好友执行任务1是0否' AFTER `end_time`,
ADD COLUMN `wechat_time_type` tinyint NULL DEFAULT 0 COMMENT '加微任务触发类型0当日线索采集完成后触发执行1自定义时间执行,等于1时wechat_time_config和wechat_task_frep才会生效' AFTER `is_wechat_task`,
ADD COLUMN `wechat_task_frep` tinyint NULL DEFAULT 0 COMMENT '加微RPA任务执行频率' AFTER `wechat_time_type`,
ADD COLUMN `wechat_time_config` json NULL COMMENT '每日执行加微时间' AFTER `wechat_task_frep`,
ADD COLUMN `wechat_custom_date` json NULL COMMENT '自定义日期' AFTER `wechat_time_config`;


ALTER TABLE `la_sv_publish_setting` 
ADD COLUMN `custom_date` json NULL COMMENT '自定义日期' AFTER `publish_end`,
MODIFY COLUMN `time_config` json NULL COMMENT '每日推送时间设置' AFTER `publish_end`;

CREATE TABLE `la_ffmpeg_file` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`file_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件ID',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上传者id',
`type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '类型[10=图片, 20=视频]',
`status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '执行状态0代处理,1处理中,2成功,3失败',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`remark` varchar(500) NOT NULL COMMENT '错误原因',
`uri` varchar(200) NOT NULL COMMENT '文件路径',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='文件处理表';

DELETE FROM `la_dev_crontab` WHERE `command` = 'ffmpeg_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('文件处理任务', 1, 0, '', 'ffmpeg_cron', '', 1, '* * * * * ', NULL, 1747896903, '0', '0', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('公共形象任务', 1, 0, '', 'digital_human_anchor_cron', '', 1, '* * * * * ', NULL, 1747896903, '0', '0', 1744881498, 1744881498, NULL);

ALTER TABLE `la_sora_anchor`
ADD COLUMN `image_url` varchar(255) NOT NULL DEFAULT '' COMMENT '上传角色图片链接' AFTER `anchor_url`,
ADD COLUMN `sora_video_task_id` varchar(50) DEFAULT '' COMMENT '角色转绘视频唯一任务ID' AFTER `sora_task_id`,
ADD COLUMN `draw_image_url` varchar(255) DEFAULT '' COMMENT '转绘后图片链接' AFTER `image_url`,
ADD COLUMN `upload_type` tinyint(1) DEFAULT '0' COMMENT '1图片转绘真人角色 2视频角色';

ALTER TABLE `la_digital_human_anchor`
ADD COLUMN `authorized_url` varchar(255) DEFAULT '' COMMENT '授权视频链接',
ADD COLUMN `authorized_pic` varchar(255) DEFAULT '' COMMENT '授权视频封面',
ADD COLUMN `width` varchar(10) NOT NULL DEFAULT '' COMMENT '宽',
ADD COLUMN `height` varchar(10) NOT NULL DEFAULT '' COMMENT '高';

INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sora_draw_avatar', 10109, '算力/次', 'AI智能一句话角色转绘', 30.00, '角色转绘每张图片约消耗30算力', 1, 1740799252, 1740799252);


DELETE FROM `la_model_config` WHERE `scene` = 'automation_social_media_released' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_social_media_released', 10301, '算力/次', '(自动化)社媒平台发布', 20,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_shut_off_comments' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_shut_off_comments', 10302, '算力/次', '(自动化)截流评论', 1,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_shut_off_obtain' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_shut_off_obtain', 10303, '算力/次', '(自动化)截流私信', 1,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_shut_off_private_letter' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_shut_off_private_letter', 10304, '算力/个', '(自动化)截流触达', 2,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_friends_circle_comments' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_friends_circle_comments', 10305, 'token/算力', '(自动化)朋友圈评论', 500,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_friends_circle_released' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_friends_circle_released', 10306, '算力/条', '(自动化)朋友圈发布', 2,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_friends_circle_praise' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_friends_circle_praise', 10307, '算力/次', '(自动化)朋友圈点赞', 2,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_wechat_add_friend' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_wechat_add_friend', 10308, '算力/次', '(自动化)自动加微', 2,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_social_media_obtain' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_social_media_obtain', 10309, 'token/算力', '(自动化)社媒平台私信接管', 500,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_social_media_nursing' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_social_media_nursing', 10310, '算力/分钟', '(自动化)社媒平台自动养号', 2,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_ocr_local' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_ocr_local', 10311, '算力/条', '(自动化)获客视频号OCR', 2,  1, NULL, NULL);

DELETE FROM `la_model_config` WHERE `scene` = 'automation_ocr_img ' ;
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('automation_ocr_img', 10312, '算力/条', '(自动化)获客本地OCR', 2,  1, NULL, NULL);


UPDATE `la_dev_crontab` SET `status` = 2 WHERE `command` = 'sph_clues_add_wechat';

ALTER TABLE `la_sv_device_task` 
MODIFY COLUMN `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注' AFTER `status`;

UPDATE `la_chat_prompt` SET `prompt_text` = '你的角色定位如下：
【角色设定】
用户提出了如下问题：
【用户发送的内容】
相关参考内容检索结果：
【相关知识库检索结果】' WHERE `prompt_name` = '微信客服';

UPDATE `la_chat_prompt` SET `prompt_text` = '你的角色定位如下：
【角色设定】
用户提出了如下问题：
【用户发送的内容】
相关参考内容检索结果：
【相关知识库检索结果】' WHERE `prompt_name` = '小红书';


ALTER TABLE `la_sv_crawling_task` 
ADD INDEX(`user_id`) USING BTREE,
ADD INDEX(`type`) USING BTREE,
ADD INDEX(`status`) USING BTREE;

UPDATE `la_models` SET `name` = 'Ai-4o' WHERE `id` = 2;
UPDATE `la_models` SET `name` = '谷歌智元2.5 PRO' WHERE `id` = 11;
UPDATE `la_models` SET `name` = '谷歌智元3.0' WHERE `id` = 14;
UPDATE `la_models` SET `name` = 'Ai-4.0' WHERE `id` = 15;
UPDATE `la_models` SET `name` = 'Ai-4o-mini' WHERE `id` = 16;
UPDATE `la_models` SET `name` = 'Ai-3.5-turbo' WHERE `id` = 17;
UPDATE `la_models` SET `name` = '克洛德4.5' WHERE `id` = 18;

UPDATE `la_models_cost` SET `name` = 'Ai-4o' WHERE `id` = 2;
UPDATE `la_models_cost` SET `name` = '谷歌智元2.5 PRO' WHERE `id` = 11;
UPDATE `la_models_cost` SET `name` = '谷歌智元3.0' WHERE `id` = 14;
UPDATE `la_models_cost` SET `name` = 'Ai-4.0' WHERE `id` = 15;
UPDATE `la_models_cost` SET `name` = 'Ai-4o-mini' WHERE `id` = 16;
UPDATE `la_models_cost` SET `name` = 'Ai-3.5-turbo' WHERE `id` = 17;
UPDATE `la_models_cost` SET `name` = '克洛德4.5' WHERE `id` = 18;

UPDATE `la_config` SET `value` = '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"Ai-4.0","model_id":15,"model_sub_id":15,"status":"1","logo":"static/images/models/3.png"},{"id":"2","name":"Ai-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"Ai-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"Ai-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"3","name":"谷歌智元2.5 PRO","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"谷歌智元3.0","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"},{"id":"10","name":"克洛德4.5","model_id":18,"model_sub_id":18,"status":"1","logo":"static/images/models/4.png"}]}' WHERE `type` = 'chat' AND `name` = 'ai_model';

ALTER TABLE `la_kb_robot`
MODIFY `kb_type` tinyint(1) unsigned DEFAULT '2' COMMENT '知识库类型: [1=RAG, 2=向量]';