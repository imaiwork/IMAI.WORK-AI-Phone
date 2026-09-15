ALTER TABLE `la_coze_workflow`
ADD COLUMN `output_key` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '输出参数',
ADD COLUMN `source` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '来源类型0后台,1用户',
ADD COLUMN `source_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建者id';

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 2, 2, 0.50, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 4, 4, 0.50, 1.00, 0.20, 0.30, 4096, 3, 1, 1757554814, 1757554814, NULL);

ALTER TABLE `la_sv_crawling_record`
ADD COLUMN `status` tinyint NULL DEFAULT 0 COMMENT '0默认1线索有效2线索无效3内含有效线索' AFTER `exec_time`;

ALTER TABLE `la_sv_crawling_task`
ADD COLUMN `ocr_type` tinyint NULL DEFAULT 1 COMMENT '识别类型1云端2本地' AFTER `greeting_content`;

ALTER TABLE `la_sv_reply_strategy`
ADD COLUMN `non_working_reply` text COMMENT '非接管时间回复',
ADD COLUMN `working_time` json DEFAULT NULL COMMENT '接管时间',
ADD COLUMN `working_enable` tinyint(1) DEFAULT '0' COMMENT '接管状态 0: 关闭（全程接管） 1:开启 (时间段内接管)';

UPDATE `la_config` SET `value` = '{"channel":[{"id":"1","name":"DeepSeek-V3","model_id":4,"model_sub_id":4},{"id":"2","name":"IMAI-4b","model_id":2,"model_sub_id":2}]}' WHERE `name` = 'ai_model' AND `type` = 'chat';
UPDATE `la_models` SET `channel` = 'IMAI' WHERE `channel` = 'openai';
UPDATE `la_models` SET `name` = 'IMAI-4b' WHERE `name` = 'OpenAI(gpt-4o)';
UPDATE `la_models_cost` SET `channel` = 'IMAI' WHERE `channel` = 'openai';

ALTER TABLE `la_kb_robot`
ADD COLUMN `logprobs` tinyint(1) unsigned DEFAULT '0' COMMENT '显示候选词 0关闭 1开启',
ADD COLUMN `top_logprobs` tinyint(2) unsigned DEFAULT '0' COMMENT '显示前几个候选词对数概率(0到20)';

ALTER TABLE `la_models_setting`
ADD COLUMN `logprobs` tinyint(1) unsigned DEFAULT '0' COMMENT '显示候选词 0关闭 1开启',
ADD COLUMN `top_logprobs` tinyint(2) unsigned DEFAULT '0' COMMENT '显示前几个候选词对数概率(0到20)';