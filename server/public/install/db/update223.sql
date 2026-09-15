ALTER TABLE `la_sv_crawling_task` 
ADD COLUMN `private_message_prompt` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '私信内容提示词' AFTER `chat_interval_time`,
MODIFY COLUMN `greeting_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '打招呼内容' AFTER `private_message_prompt`,
MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '加微好友备注' AFTER `greeting_content`,
ADD COLUMN `wechat_id` varchar(1000) NULL COMMENT '添加好友的微信号,多个逗号分隔' AFTER `remark`,
ADD COLUMN `wechat_reg_type` tinyint(0) NULL DEFAULT 0 COMMENT '加微匹配规则0全部1微信号2手机号' AFTER `wechat_id`,
ADD COLUMN `add_friends_prompt` varchar(1000) NULL COMMENT '添加好友提示词' AFTER `wechat_reg_type`,
MODIFY COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态-0未开始,1进行中,2暂停中,3已完成4已结束' AFTER `add_friends_prompt`;


DELETE FROM `la_model_config` WHERE `scene` = 'sph_ocr';
DELETE FROM `la_model_config` WHERE `scene` = 'human_prompt';
INSERT INTO `la_model_config`( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sph_ocr', 11003, '算力/次', '视频号OCR', 0.00, '视频号OCR识别0算力/次', 1, NULL, NULL);
