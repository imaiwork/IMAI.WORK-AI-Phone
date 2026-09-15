CREATE TABLE IF NOT EXISTS `la_auto_needs_analysis` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`conversation_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'coze会话id',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
`contents` json DEFAULT NULL COMMENT '对话记录',
`result` json DEFAULT NULL COMMENT '分析结果 business_type (业务类型),account_stage (账号阶段),target_audience (客户对象),core_pain (客户核心痛点),main_platform (主要运营平台),platform_focus (平台侧重点),content_style (内容风格倾向),main_block (当前最大运营卡点),risk_tolerance (账号风险承受度),benchmark_account (对标账号)',
`is_draft` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否草稿，0：否，1：是',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='自动任务运营分析表';

ALTER TABLE `la_sv_device_circle_like_reply_record`
ADD COLUMN `hash` varchar(255) NULL AFTER `task_id`,
ADD INDEX(`user_id`) USING BTREE,
ADD INDEX(`like_reply_account`) USING BTREE,
ADD INDEX(`device_code`) USING BTREE,
ADD INDEX(`hash`) USING BTREE,
ADD INDEX(`account`) USING BTREE;

ALTER TABLE `la_shanjian_video_setting`
ADD COLUMN `audio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '音频,json';

ALTER TABLE `la_shanjian_video_task`
ADD COLUMN `audio_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '音频地址';

ALTER TABLE `la_sv_lead_scraping_setting` 
ADD COLUMN `industry_type` tinyint NULL DEFAULT 0 COMMENT '获客行业类型 0自由获客 1同城获客' AFTER `region`,
ADD COLUMN `city` varchar(255) NULL COMMENT '选择同城获客时录入' AFTER `industry_type`,
ADD COLUMN `is_content_author` tinyint NULL DEFAULT 0 COMMENT '跳过内容作者 0不跳过 1跳过' AFTER `city`,
ADD COLUMN `is_execed_clues` tinyint NULL DEFAULT 0 COMMENT '过滤已执行客户 0不过滤 1过滤' AFTER `is_content_author`,
ADD COLUMN `content_publish_day` int NULL DEFAULT 0 COMMENT '内容发布时间 0表示不限制 其余数字表示对应的天数' AFTER `is_execed_clues`,
ADD COLUMN `comment_publish_day` int NULL DEFAULT 0 COMMENT '评论发布时间 0表示不限制 其余数字表示对应的天数' AFTER `content_publish_day`,
ADD COLUMN `ip_address` json NULL COMMENT '客户所属IP地址 为空不限制，不为空时自定义' AFTER `comment_publish_day`;

ALTER TABLE `la_auto_device_config`
ADD COLUMN `conversation_id` varchar(255) DEFAULT '' COMMENT 'coze会话id' AFTER `text_theme`,
ADD COLUMN `analysis` json DEFAULT NULL COMMENT '分析结果 business_type (业务类型),account_stage (账号阶段),target_audience (客户对象),core_pain (客户核心痛点),main_platform (主要运营平台),platform_focus (平台侧重点),content_style (内容风格倾向),main_block (当前最大运营卡点),risk_tolerance (账号风险承受度),benchmark_account (对标账号)' AFTER `conversation_id`;


ALTER TABLE `la_sv_device_circle_like_reply_record` 
ADD COLUMN `type` tinyint NULL DEFAULT 0 COMMENT '互动动作1点赞2评论3点赞加评论' AFTER `task_id`;


UPDATE `la_model_config` SET `name` = '视频号获客' WHERE `scene` = 'sph_add_wechat';

INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('automation_account_ip_analysis', 10313, '算力/次', '账号Ip人设分析报告', 5.00, '账号Ip人设分析报告每次消耗5算力' , 1, 1740799252, 1740799252);