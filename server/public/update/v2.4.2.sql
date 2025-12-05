UPDATE `la_chat_prompt` SET `prompt_text` = '你是一名AI微信客服，正在为用户提供帮助。

你的角色设定如下：
##设定开始##
【角色设定】
##设定结束##

用户提出了如下问题：
【用户发送的内容】

请根据以下信息来给出专业、自然、个性化的回答。

历史上下文：
##历史上下文开始##
【历史对话上下文】
##历史上下文结束##

相关参考内容检索结果：
##检索开始##
【相关知识库检索结果】
##检索结束##

你的回复需要遵守以下原则：
1. 如果"相关参考内容检索结果"中有和用户问题相关的信息，必须结合参考内容进行回复，但禁止提到参考内容这个概念，禁止额外的补充回复；
2. 如果无法确认正确的答案，请礼貌表达无法确定，并引导用户提供更多信息或尝试其他方式；
3. 始终保持与你的人设相符合的语气与身份；
4. 回答要拟人化，像真人一样使用简洁的语句进行回复。

兜底建议（无任何信息可用时）：
“这个问题我不是很清楚，要不您换种方式描述？”

请开始回复。'
WHERE `prompt_name` = '微信客服';


UPDATE `la_dev_crontab` SET`expression` = '0 */1 * * *' WHERE `command` = 'ai_circle_reply_like';

ALTER TABLE `la_ai_wechat_contact`
ADD COLUMN `update_time` int NULL DEFAULT 0 COMMENT '更新时间' AFTER `create_time`;

DELETE FROM `la_config` WHERE `type` = 'add_remark' AND `name` = 'wechat';
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('add_remark', 'wechat', '[\"视频号看到的~ 咨询一下～\",\"老师，你好，视频号~\",\"视频号刷到的，通过一下~\",\"你好～通过一下～\",\"hello～想咨询一下～\"]', 1760427363, 1760428048);


DELETE FROM `la_model_config` WHERE `scene` = 'sph_search_terms' ;
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sph_search_terms', 10003, '算力/条', '视频号检索关键词', 0.1, '每条消耗0.1算力', 1, 1740799252, 1740799252);

UPDATE `la_model_config` SET  `code` = 7003 WHERE `scene` = 'interview_chat';


INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`) VALUES ('替换标识', 1, 0, '', 'replace_cron', '', 1, '* * * * *', '', 1762526643, '0.01', '0.01', 1762526557, 1762526701);
