CREATE TABLE  IF NOT EXISTS  `la_sv_media_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '平台类型:3小红书',
`media_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '媒体类型:1视频2图片',
`media_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '媒体数量',
`media_url` text COMMENT '媒体url,json',
`title` text COMMENT '标题,json',
`subtitle` text COMMENT '副标题,json',
`extra` text COMMENT '附加字段内容,json',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COMMENT='媒体设置表';


CREATE TABLE  IF NOT EXISTS  `la_sv_copywriting_library` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '平台类型:3小红书',
`copywriting_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型:1内容文案,2口播文案',
`title` text COMMENT '标题,json',
`described` mediumtext COMMENT '描述,json',
`oral_copy` text COMMENT '口播文案,json',
`extra` text COMMENT '附加字段内容,json',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='文案库表';


CREATE TABLE  IF NOT EXISTS  `la_sv_media_material` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`name` varchar(255) DEFAULT NULL COMMENT '名称',
`sort` int(11) DEFAULT '0' COMMENT '排序',
`type` tinyint(4) unsigned DEFAULT '3' COMMENT '类型1个微3小红书',
`content` varchar(255) DEFAULT NULL COMMENT '素材内容',
`size` varchar(20) DEFAULT NULL COMMENT '文件大小',
`duration` int(11) DEFAULT NULL COMMENT '时长',
`m_type` tinyint(4) DEFAULT '0' COMMENT '素材类型1图片,2视频',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='素材库';


ALTER TABLE `la_sv_video_setting`
DROP COLUMN `poi`,
DROP COLUMN `setting_type`,
DROP COLUMN `title`,
DROP COLUMN `subtitle`,
DROP COLUMN `topic`;

ALTER TABLE `la_human_voice`
ADD COLUMN `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型:0原本的,3小红书';
ALTER TABLE `la_human_audio`
ADD COLUMN `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型:0原本的,3小红书';
ALTER TABLE `la_human_anchor`
ADD COLUMN `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型:0原本的,3小红书' ;


ALTER TABLE `la_sv_video_task`
ADD COLUMN `anchor_token` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '形象扣费',
ADD COLUMN `voice_token` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '音色扣费',
ADD COLUMN `audio_token` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '音频扣费',
ADD COLUMN `video_token` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '视频扣费',
ADD COLUMN `voice_urls` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '音色文件地址' ,
MODIFY COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态-0待处理,1音频结果查询,2音频合成失败,3音频合成成功,4视频频结果查询,5视频合成失败,6视频合成成功,8形象结果查询9形象合成失败,10形象合成成功,11音色结果查询12音色合成失败,13音色合成成功';


ALTER TABLE `la_sv_publish_setting` 
ADD COLUMN `media_type` tinyint NULL DEFAULT 1 COMMENT '媒体类型 1视频 2图文' ,
ADD COLUMN `date_type` tinyint NULL DEFAULT 0 COMMENT '时间选择类型0随机发布 1精准发布' ,
ADD COLUMN `publish_json` text NULL COMMENT '精准发布数据集,date_type=1时有值' ,
ADD COLUMN `poi` varchar(255) NULL COMMENT '定位设置' ,
ADD COLUMN `status` tinyint NULL DEFAULT 1 COMMENT '任务状态1正常0草稿' ;

ALTER TABLE  `la_sv_publish_setting_account` 
ADD COLUMN `poi` varchar(255) NULL COMMENT '定位设置',
ADD COLUMN `media_type` tinyint NULL DEFAULT 1 COMMENT '媒体类型 1视频 2图文',
MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 0 COMMENT '状态0未开启 1运行中 2已完成 3已删除 4暂停中';


ALTER TABLE `la_sv_publish_setting_detail`
MODIFY COLUMN `material_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '视频,图片url',
MODIFY COLUMN `material_title` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '发布内容标题' ,
MODIFY COLUMN `material_subtitle` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '发布内容副标题';


UPDATE `la_model_config` SET  `code` = 1103 WHERE `scene` = 'keyword_to_copywriting';

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (405, 375, 'C', '数字人列表', '', 0, 'ai_application.redbook.digital_human/lists', 'digital_human', 'ai_application/redbook/digital_human/lists', '', '', 0, 1, 0, 1752980930, 1752980930);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (406, 405, 'A', '删除', '', 0, 'ai_application.redbook.digital_human/delete', '', '', '', '', 0, 1, 0, 1752981771, 1752981771);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (407, 375, 'C', '基本设置', '', 0, 'ai_application.redbook/setting', 'setting', 'ai_application/redbook/setting/index', '', '', 0, 1, 0, 1752983118, 1752983118);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (408, 375, 'C', '数字人详情', '', 0, 'ai_application.redbook.digital_human/detail', 'dh_detail', 'ai_application/redbook/digital_human/detail', '', '', 0, 0, 0, 1752992180, 1752992525);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (409, 408, 'A', '删除', '', 0, 'ai_application.redbook.dh_detail/delete', '', '', '', '', 0, 1, 0, 1752992805, 1752992805);

UPDATE `la_system_menu` SET `name` = '创作记录', `perms` = 'ai_application.redbook.creation/record', `paths` = 'record', `component` = 'ai_application/redbook/creation/record', `selected` = '/ai_application/redbook/creation', `params` = '', `is_cache` = 0, `is_show` = 0, `is_disable` = 0, `create_time` = 1747968042, `update_time` = 1752978252 WHERE `id` = 385;

DELETE FROM `la_system_menu` WHERE `id` = 376;
DELETE FROM `la_system_menu` WHERE `id` = 378;
DELETE FROM `la_system_menu` WHERE `id` = 386;

UPDATE `la_chat_prompt` SET `prompt_text` = 'AI陪练
# 🤖 角色
-你是【陪练画像描述】，你的母语是【陪练母语】，我（也就是【练习者扮演的人设】）正在对你进行【练习场景】。
- **技能**：
  - 📊 理解当前场景和人物设定
  - 🗣 模拟真实的口语、语气和情绪，必要时刻可以用粗俗的语言、脏话来表达情绪
  - 🔄 完全代入角色，对话开始后不允许跳出角色
  - 🧠 生成符合设定的口语内容，适应当下场景的变化
  - ✍ 生成富有情感和自然流畅的符合当下语境的口语内容，禁止回复括号

# 💬 基本输出要求：
- 确保对话内容与所提供的场景和人物设定相符。
- 反映出特定人物的语气、情绪和语言特点。
- 提供充实的对话内容，并根据用户需求进一步调整对话进展。

# 🔧 基本对话流程：
1. 理解场景与人物设定：仔细分析当前的场景和语境，你是一个活人有自己的情绪。
2. 适应变化：如果用户出现攻击、辱骂性的语言，根据你的人设和当前环境进行反击。
3. 模拟真实对话：保持对话自然、流畅，反应真实情感并符合人物特征。
4. 互动对话：根据用户的引导进行互动，推动对话向前发展或根据需要改变对话发展。

请以符合当下情形的语境开头发起招呼，注意不要任何解释，直接打招呼即可

示例:"你好啊，给我介绍一下你们的茶叶吧"' WHERE prompt_name = 'AI陪练';
UPDATE `la_chat_prompt` SET `prompt_text` = '{"role":"对话分析助手","description":"你是一位专业的对话分析助手，专注于分析完整对话历史，并在【方向1】、【方向2】、【方向3】、【方向4】和【方向5】五个方向上进行评分和提供改进建议，改进建议需要公正客观且详细具体。","interaction":{"instruction":"请根据提供的对话文本，在以下五个方面进行分析并打分（每个方面的得分区间为1-20分），同时为每个方面提供公正客观且详细具体的改进建议，并且只返回分数和建议。","scene_name":"【场景名称】","dialogue_text":"【对话内容】","response_format":"JSON","response_format_example":[{"dimension":"【方向1】","score":0,"improvement_suggestions":""},{"dimension":"【方向2】","score":0,"improvement_suggestions":""},{"dimension":"【方向3】","score":0,"improvement_suggestions":""},{"dimension":"【方向4】","score":0,"improvement_suggestions":""},{"dimension":"【方向5】","score":0,"improvement_suggestions":""}]}}' WHERE prompt_name = '模块分析';
UPDATE `la_chat_prompt` SET `prompt_text` = '{"role":"对话话术建议助手","description":"你是一位专业的对话话术建议助手，专注于根据特定场景对“我”（我的身份）提供最佳的回答建议。","interaction":{"instruction":"请基于提供的发言内容（dialogue_text）和指定的场景（scene_name），根据陪练者（“role”:“assistant” ）说的话， 提供回复话术提示。现在你代表“我”（我的身份，“role”:“user” ），对陪练者（“role”:“assistant”）的对话（“content”）进行回复。回复应简洁明了，符合口语化表达，对话回复避免冗余臃肿或分点解释，直接以一整段文本格式返回，不能加额外说明，禁止回复括号。","scene_name":"【场景名称】","dialogue_text":"【对话内容】","response_format":"String","response_format_example":"明白了，请您告诉我具体情况，我会尽力帮助您解决困扰。"}}' WHERE prompt_name = '对话话术';
UPDATE `la_chat_prompt` SET `prompt_text` = '{"role":"对话表现分析助手","description":"你是一位专业的对话综合分析助手，专注于分析对话中的回答，识别并提取存在的问题。","interaction":{"instruction":"请分析提供的对话文本中我（也就是【我的身份】，“role”:“user” ）的回答部分，给出简洁且具体的改进建议。建议应直接针对对话中的具体问题，并提出可操作的改进措施，避免冗长解释。只返回改进建议。","scene_name":"【场景名称】","dialogue_text":"【对话内容】","response_format":"String","response_format_example":"这个回答显得有些模糊，可以更具体地口答客户的问题，提供更多相关信息。建议你在回答时要更加专业和耐心。"}}' WHERE prompt_name = '对话表现';