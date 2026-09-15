ALTER TABLE  `la_ffmpeg_file`
ADD COLUMN `width` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '宽' ,
ADD COLUMN `height` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '高' ;


INSERT INTO `la_model_config` (scene, code, unit, name, score, description, status) VALUES ( 'coze_copywriting_senior', 10207, '算力/条', 'Ai智能发布文案(高级版)', 10, 'AI生成文案(高级版)10算力/条', 1);

ALTER TABLE `la_ai_persona_wechat_interaction_config` 
ADD COLUMN `is_auto_group` tinyint NULL DEFAULT 0 COMMENT '自动加群1开启0关闭' AFTER `comment_speech`,
ADD COLUMN `sales_wechat` json NULL COMMENT '销售微信号' AFTER `is_auto_group`,
ADD COLUMN `group_name_template` varchar(255) NULL COMMENT '群名称模板' AFTER `sales_wechat`,
ADD COLUMN `is_greeting` tinyint NULL DEFAULT 0 COMMENT '是否自动发送欢迎语1是0否' AFTER `group_name_template`,
ADD COLUMN `greeting_text` varchar(500) NULL COMMENT '欢迎语' AFTER `is_greeting`;

ALTER TABLE `la_sv_wechat_strategy` 
ADD COLUMN `is_auto_group` tinyint NULL DEFAULT 0 COMMENT '自动加群1开启0关闭' AFTER `time_config`,
ADD COLUMN `sales_wechat` json NULL COMMENT '销售微信号' AFTER `is_auto_group`,
ADD COLUMN `group_name_template` varchar(255) NULL COMMENT '群名称模板' AFTER `sales_wechat`,
ADD COLUMN `is_greeting` tinyint NULL DEFAULT 0 COMMENT '是否自动发送欢迎语1是0否' AFTER `group_name_template`,
ADD COLUMN `greeting_text` varchar(500) NULL COMMENT '欢迎语' AFTER `is_greeting`;


ALTER TABLE `la_ai_persona` 
ADD COLUMN `is_shopping_cart` tinyint NULL DEFAULT 0 COMMENT '知识库：平台购物车1开启0关闭' AFTER `conversion_hook`,
ADD COLUMN `goods_name` text NULL COMMENT '知识库：商品名称' AFTER `is_shopping_cart`,
ADD COLUMN `is_store_position` tinyint NULL DEFAULT 0 COMMENT '知识库：商家定位1开启0关闭' AFTER `goods_name`,
ADD COLUMN `store_position` varchar(255) NULL COMMENT '知识库：门店位置' AFTER `is_store_position`;

ALTER TABLE `la_ai_persona_traffic_config` 
ADD COLUMN `clue_keywords` json NULL COMMENT '获客线索词' AFTER `user_id`,
MODIFY COLUMN `acquire_keywords` json NULL COMMENT '截流行业词' AFTER `user_id`,
ADD COLUMN `clue_max_number` int NULL DEFAULT 0 COMMENT '每个线索词获客上限0不限' AFTER `clue_keywords`,
ADD COLUMN `clue_keyword_used_type` tinyint NULL DEFAULT 2 COMMENT '线索词达标耗尽后动作1ai自动补充2循环使用3停止使用' AFTER `clue_max_number`,
MODIFY COLUMN `intercept_keywords` json NULL COMMENT '评论匹配词' AFTER `clue_keywords`,
ADD COLUMN `intercept_max_number` int NULL DEFAULT 0 COMMENT '每个匹配词截流上限0不限' AFTER `intercept_keywords`,
ADD COLUMN `intercept_keyword_used_type` tinyint NULL DEFAULT 2 COMMENT '截流词达标耗尽后动作1ai自动补充2循环使用3停止使用' AFTER `intercept_max_number`,
ADD COLUMN `view_video_time` int NULL DEFAULT 10 COMMENT '观看视频时长(s)' AFTER `comment_publish_day`,
ADD COLUMN `touch_interval` int NULL DEFAULT 10 COMMENT '触达间隔(s)' AFTER `view_video_time`,
ADD COLUMN `gender` tinyint NULL DEFAULT 0 COMMENT '性别0不限1男2女' AFTER `touch_interval`,
ADD COLUMN `age_range` json NULL COMMENT '年龄范围' AFTER `gender`,
ADD COLUMN `filter_ip` varchar(255) NULL COMMENT '筛选ip' AFTER `age_range`,
ADD COLUMN `filter_address` varchar(255) NULL COMMENT '筛选地区' AFTER `filter_ip`,
ADD COLUMN `filter_nikename` json NULL COMMENT '昵称中不包含的词汇' AFTER `filter_address`;

ALTER TABLE `la_ai_persona_enterprise` 
ADD COLUMN `clue_keywords` json NULL COMMENT '获客行业词' AFTER `account_goal`;

ALTER TABLE `la_ai_persona_individual` 
ADD COLUMN `clue_keywords` json NULL COMMENT '获客行业词' AFTER `monetize_paths`;

ALTER TABLE `la_ai_persona_local` 
ADD COLUMN `clue_keywords` json NULL COMMENT '获客行业词' AFTER `content_preference`;


ALTER TABLE `la_human_video_task` 
ADD COLUMN `is_ai` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ai授权 0否1是' ;

INSERT INTO `la_model_config` 
    (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) 
VALUES 
    ('video_imitation_copywriting_parse', 10208, '算力/分钟', '爆款仿写视频文案提取', 10.00, '爆款仿写提取视频文案扣除算力，10算力/分钟', 1, 1776065039, 1776065039);
ALTER TABLE `la_video_imitation_task`
    ADD `origin_video_duration` int default 0 not null comment '原视频时长' after `word_count`;



INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) 
VALUES ('wechat_create_group', 8002, '算力/次', '个微自动拉群', 5.00, '个微自动接管识别到相关意图并自动拉群', 1, 1776304800, 1776304800);


CREATE TABLE IF NOT EXISTS `la_ai_wechat_create_group_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `friend_id` varchar(255) DEFAULT NULL COMMENT '客户id',
  `wechat_id` varchar(255) DEFAULT NULL COMMENT '执行账号id',
  `sales_wechat` varchar(255) DEFAULT NULL COMMENT '销售微信',
  `group_name` varchar(255) DEFAULT NULL COMMENT '群名称',
  `scene` tinyint(4) DEFAULT '0' COMMENT '创建场景1自动0手动',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0失败1成功',
  `result` text COMMENT '执行结果',
  `task_id` varchar(255) DEFAULT NULL COMMENT '扣费任务id',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='rpa执行微信拉群日志';


UPDATE `la_human_video_task`  SET `is_ai` = 1  WHERE `name` LIKE '%ai授权视频%';

ALTER TABLE `la_sv_account` 
ADD COLUMN `is_verified` tinyint NULL DEFAULT 0 COMMENT '是否实名1是0否' AFTER `extra`;

ALTER TABLE `la_sv_add_wechat_record` 
ADD COLUMN `exec_task_id` int NULL DEFAULT 0 COMMENT '执行任务id' AFTER `crawling_task_id`;

ALTER TABLE `la_ai_persona_enterprise` 
ADD COLUMN `is_clue_updated` tinyint NULL DEFAULT 0 COMMENT '重新生成标识1是0否' AFTER `clue_dm_scripts`,
ADD COLUMN `is_wechat_updated` tinyint NULL DEFAULT 0 COMMENT '重新生成标识1是0否' AFTER `wechat_comment_speech`;

ALTER TABLE `la_ai_persona_individual` 
ADD COLUMN `is_clue_updated` tinyint NULL DEFAULT 0 COMMENT '重新生成标识1是0否' AFTER `clue_dm_scripts`,
ADD COLUMN `is_wechat_updated` tinyint NULL DEFAULT 0 COMMENT '重新生成标识1是0否' AFTER `wechat_comment_speech`;

ALTER TABLE `la_ai_persona_local` 
ADD COLUMN `is_clue_updated` tinyint NULL DEFAULT 0 COMMENT '重新生成标识1是0否' AFTER `clue_dm_scripts`,
ADD COLUMN `is_wechat_updated` tinyint NULL DEFAULT 0 COMMENT '重新生成标识1是0否' AFTER `wechat_comment_speech`;

INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('seedance2_480p_image2video_create', 10110, '算力/秒', 'AI智能一句话生成视频(seedance2.0-480p-image)', 100.00, '480p不含参考视频每秒消耗100算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('seedance2_480p_video2video_create', 10111, '算力/秒', 'AI智能一句话生成视频(seedance2.0-480p-video)', 120.00, '480p包含参考视频每秒消耗120算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('seedance2_720p_image2video_create', 10112, '算力/秒', 'AI智能一句话生成视频(seedance2.0-720p-image)', 140.00, '720p不含参考视频每秒消耗140算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('seedance2_720p_video2video_create', 10113, '算力/秒', 'AI智能一句话生成视频(seedance2.0-720p-video)', 200.00, '720p包含参考视频每秒消耗200算力', 1, 1740799252, 1740799252);
