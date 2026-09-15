UPDATE `la_model_config` SET `unit` = '算力/次', `score` = 20, `description` = '每次消耗20算力' WHERE `scene` = 'sora_video_create';
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sora_pro_video_create', 10107, '算力/次', 'AI智能一句话生成视频(pro)', 170.00, '每次消耗170算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sora_copywriting_create', 10105, '算力/次', 'AI智能一句话生成视频文案优化', 1.00, '每次消耗1算力', 1, 1740799252, 1740799252);

UPDATE `la_chat_prompt` SET `prompt_text` = '你的角色设定如下：
【角色设定】
用户提出了如下问题：
【用户发送的内容】
历史上下文：
【历史对话上下文】
相关参考内容检索结果：
【相关知识库检索结果】' WHERE `prompt_name` = '微信客服';

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`) VALUES ('sora视频合成状态', 1, 0, '', 'sora_video_task', '', 1, '* * * * *', '', 1762526643, '0.01', '0.01', 1762526557, 1762526701);