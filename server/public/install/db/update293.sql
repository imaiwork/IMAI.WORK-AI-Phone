CREATE TABLE IF NOT EXISTS `la_minimax_upload_file` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_id` varchar(100) DEFAULT NULL COMMENT '系统任务id',
`audio_url` varchar(500) DEFAULT NULL COMMENT '源语音地址',
`file_id` varchar(100) DEFAULT NULL COMMENT 'minimax文件id',
`file_name` varchar(100) DEFAULT NULL COMMENT 'minimax文件名称',
`bytes` int(11) DEFAULT NULL COMMENT '文件大小',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='minimax音色上传文件表';

DELETE FROM `la_models`
WHERE `id` = 19
AND `name` = '微聚云科'
AND `channel` = '微聚云科';

DELETE FROM `la_models_cost`
WHERE `id` = 19
AND `name` = '微聚云科'
AND `channel` = '微聚云科';

DELETE FROM `la_models`
WHERE `id` = 8
AND `name` = '优秘V5'
AND `channel` = '优秘';

DELETE FROM `la_models_cost`
WHERE `id` = 8
AND `name` = '优秘V5'
AND `channel` = '优秘';

UPDATE `la_config`
SET `value` = JSON_REMOVE(CAST(`value` AS JSON), '$.channel')
WHERE `type` = 'model' AND `name` = 'list';

ALTER TABLE `la_models` ADD COLUMN `model_version` INT(11) NULL COMMENT '模型版本' AFTER `channel`;

UPDATE `la_models` SET `model_version` = CASE `id`
WHEN 7 THEN 7
WHEN 9 THEN 8
END
WHERE `id` IN (7, 9);

INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `model_version`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (20, 4, 'minimax-hd', 'static/images/human/11.png', 'minimax-hd',10, '', '', 0, 1, 1, 1, 1755929617, 1755929617, NULL);

INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `model_version`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (21, 4, 'minimax-turbo', 'static/images/human/11.png', 'minimax-turbo',11, '', '', 0, 1, 1, 1, 1755929617, 1755929617, NULL);

INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (20, 20, 1, 'minimax-hd', 'minimax-hd', 'speech-2.8-hd',0.0000, 0, 1, 1755929617);

INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (21, 21, 1, 'minimax-turbo', 'minimax-turbo', 'speech-2.8-turbo',0.0000, 0, 1, 1755929617);

INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_voice_minimax_hd', 10400, '算力/次', '音色克隆(minimax-hd)', 1500.00, '音色克隆，1次/1500算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_voice_minimax_turbo', 10401, '算力/次', '音色克隆(minimax-turbo)', 1200.00, '音色克隆，1次/1200算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_audio_minimax_hd', 10402, '算力/字', '音频合成(minimax-hd)', 0.15, '音频合成，1字/0.15算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_audio_minimax_turbo', 10403, '算力/字', '音频合成(minimax-turbo)', 0.10, '音频合成，1字/0.10算力', 1, 1740799252, 1740799252);

UPDATE `la_config` SET `value` = '{\"voice\": [{\"code\": \"10000\", \"type\": \"1\", \"name\": \"智小敏(女)\", \"status\": \"1\"}, {\"code\": \"10001\", \"type\": \"1\", \"name\": \"智小柔(女)\", \"status\": \"1\"}, {\"code\": \"10002\", \"type\": \"1\", \"name\": \"智小满(女)\", \"status\": \"1\"}, {\"code\": \"10003\", \"type\": \"1\", \"name\": \"爱小芊(女)\", \"status\": \"1\"}, {\"code\": \"10004\", \"type\": \"1\", \"name\": \"爱小静(女)\", \"status\": \"1\"}, {\"code\": \"10005\", \"type\": \"1\", \"name\": \"千嶂(男)\", \"status\": \"1\"}, {\"code\": \"10006\", \"type\": \"1\", \"name\": \"智皓(男)\", \"status\": \"1\"}, {\"code\": \"10007\", \"type\": \"1\", \"name\": \"爱小杭(男)\", \"status\": \"1\"}, {\"code\": \"10008\", \"type\": \"1\", \"name\": \"爱小辰(男)\", \"status\": \"1\"}, {\"code\": \"10009\", \"type\": \"1\", \"name\": \"飞镜(男)\", \"status\": \"1\"}, {\"code\": \"10010\", \"type\": \"2\", \"name\": \"龙橙2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"longcheng_v2\", \"recommended\": \"译制片\", \"url\":\"static/audio/voice/longcheng_v2.mp3\"}, {\"code\": \"10011\", \"type\": \"2\", \"name\": \"龙华2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"longhua_v2\", \"recommended\": \"智能客服、对话闲聊\", \"url\":\"static/audio/voice/longhua_v2.mp3\"}, {\"code\": \"10012\", \"type\": \"2\", \"name\": \"龙书2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"longshu_v2\", \"recommended\": \"新闻播报、有声读物\", \"url\":\"static/audio/voice/longshu_v2.mp3\"}, {\"code\": \"10013\", \"type\": \"2\", \"name\": \"Bella2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"loongbella_v2\", \"recommended\": \"智能客服、新闻播报、对话闲聊\", \"url\":\"static/audio/voice/Bella.mp3\"}, {\"code\": \"10014\", \"type\": \"2\", \"name\": \"龙婉2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"longwan_v2\", \"recommended\": \"智能客服、新闻播报、对话闲聊\", \"url\":\"static/audio/voice/longwan_v2.mp3\"}, {\"code\": \"10015\", \"type\": \"2\", \"name\": \"龙小淳2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"longxiaochun_v2\", \"recommended\": \"智能客服、新闻播报、对话闲聊\", \"url\":\"static/audio/voice/longxiaochun_v2.mp3\"}, {\"code\": \"10016\", \"type\": \"2\", \"name\": \"龙小夏2.0\", \"status\": \"1\", \"model\": \"cosyvoice-v2\", \"voice\": \"longxiaoxia_v2\", \"recommended\": \"智能客服、新闻播报、对话闲聊\", \"url\":\"static/audio/voice/longxiaoxia_v2.mp3\"}]}' WHERE `type` = 'model' AND `name` = 'list';


