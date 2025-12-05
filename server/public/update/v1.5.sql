-- v1.5
-- 删除历史菜单
DELETE FROM `la_system_menu` WHERE `id` in (196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 206, 207, 208);

-- 菜单
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (321, 195, 'M', 'AI陪练', '', 1, '', 'ladder_player', '', '', '', 0, 1, 0, 1737012360, 1737080045);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (322, 321, 'M', '场景管理', '', 0, '', 'scene', '', '', '', 0, 1, 0, 1737012497, 1737012497);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (323, 322, 'C', '场景列表', '', 0, 'ai_application.ladder_player/scene', 'lists', 'ai_application/ladder_player/scene/index', '', '', 0, 1, 0, 1737012517, 1737012546);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (324, 323, 'A', '删除', '', 0, 'ai_application.lp.scene/del', '', '', '', '', 0, 1, 0, 1737012600, 1737081649);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (325, 323, 'A', '新增', '', 0, 'ai_application.lp.scene/add', '', '', '', '', 0, 1, 0, 1737012620, 1737012696);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (326, 323, 'A', '编辑', '', 0, 'ai_application.lp.scene/edit', '', '', '', '', 0, 1, 0, 1737012649, 1737012707);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (327, 323, 'A', '状态', '', 0, 'ai_application.lp.scene/status', '', '', '', '', 0, 1, 0, 1737012662, 1737012711);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (328, 321, 'C', '陪练记录', '', 0, 'ai_application.ladder_player/record', 'record', 'ai_application/ladder_player/record/index', '', '', 0, 1, 0, 1737012762, 1737012762);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (329, 322, 'C', '场景新增/编辑', '', 0, 'ai_application.lp.scene/add:edit', 'edit', 'ai_application/ladder_player/scene/edit', '/ai_application/ladder_player/scene/lists', '', 0, 0, 0, 1737012837, 1737082041);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (330, 328, 'A', '删除', '', 0, 'ai_application.lp.record/del', '', '', '', '', 0, 1, 0, 1737012913, 1737081153);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (331, 321, 'C', '配置管理', '', 0, 'ai_application.ladder_player/setting', 'setting', 'ai_application/ladder_player/setting/index', '', '', 0, 1, 0, 1737013067, 1737013067);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (332, 321, 'C', '陪练记录详情', '', 0, 'ai_application.lp.record/detail', 'detail', 'ai_application/ladder_player/record/detail', '/ai_application/ladder_player/record', '', 0, 0, 0, 1737081364, 1737082110);

-- 删除历史练练表
DROP TABLE IF EXISTS `la_ll_analyse`;
DROP TABLE IF EXISTS `la_ll_audio_type`;
DROP TABLE IF EXISTS `la_ll_category`;
DROP TABLE IF EXISTS `la_ll_category_info`;
DROP TABLE IF EXISTS `la_ll_chat`;
DROP TABLE IF EXISTS `la_ll_conversation`;

-- 场景表
DROP TABLE IF EXISTS `la_ll_scene`;
CREATE TABLE `la_ll_scene` (
	`id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT 0 COMMENT '用户ID',
	`logo` VARCHAR ( 100 ) NOT NULL DEFAULT '' COMMENT '场景LOGO',
	`name` VARCHAR ( 100 ) NOT NULL DEFAULT '' COMMENT '场景名称',
	`description` TEXT NULL COMMENT '场景描述',
	`training_target` JSON NULL COMMENT '练习目标',
	`tips` JSON NULL COMMENT '温馨提示',
	`coach_name` VARCHAR ( 100 ) NOT NULL DEFAULT '' COMMENT '陪练者名称',
	`coach_persona` LONGTEXT NULL COMMENT '陪练者人设',
	`coach_language` VARCHAR ( 50 ) NOT NULL DEFAULT '' COMMENT '陪练者母语',
	`coach_voice` VARCHAR ( 50 ) NOT NULL DEFAULT '' COMMENT '陪练者音色',
	`practitioner_persona` LONGTEXT NULL COMMENT '练习者人设',
	`analysis_report_config` JSON NULL COMMENT '分析报告配置',
	`sort` INT NOT NULL DEFAULT 0 COMMENT '场景排序',
	`status` TINYINT NOT NULL DEFAULT 1 COMMENT '场景状态 0 不可使用 1：正常',
	`create_time` INT ( 11 ) DEFAULT NULL COMMENT '创建时间',
	`update_time` INT ( 11 ) DEFAULT NULL COMMENT '更新时间',
	`delete_time` INT ( 11 ) DEFAULT NULL COMMENT '删除时间',
	PRIMARY KEY ( `id` ) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '场景表';

-- 场景聊天表
DROP TABLE IF EXISTS `la_ll_chat`;
CREATE TABLE `la_ll_chat` (
	`id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT 0 COMMENT '用户ID',
	`scene_id` INT NOT NULL DEFAULT 0 COMMENT '场景ID',
	`analysis_id` INT NOT NULL DEFAULT 0 COMMENT '分析ID',
	`preliminary_ask` VARCHAR ( 500 ) NOT NULL DEFAULT '' COMMENT '陪练者开场白',
	`preliminary_ask_audio` VARCHAR ( 200 ) NOT NULL DEFAULT '' COMMENT '陪练者开场白  - 语音',
	`preliminary_ask_audio_duration` INT NOT NULL DEFAULT 0 COMMENT '陪练者开场白  - 语音时长',
	`ask` LONGTEXT NULL COMMENT '练习者提问',
	`ask_audio` VARCHAR ( 500 ) NOT NULL DEFAULT '' COMMENT '练习者提问 - 语音',
	`ask_audio_duration` INT NOT NULL DEFAULT 0 COMMENT '练习者语音时长',
	`reply` LONGTEXT NULL COMMENT '陪练者回复',
	`reply_audio` VARCHAR ( 500 ) NOT NULL DEFAULT '' COMMENT '陪练者回复 - 语音',
	`reply_audio_duration` INT NOT NULL DEFAULT 0 COMMENT '陪练者回复 - 语音时长',
	`performance` LONGTEXT NULL COMMENT '对话表现',
	`speechcraft` LONGTEXT NULL COMMENT '话术提炼',
	`create_time` INT ( 11 ) DEFAULT NULL COMMENT '创建时间',
	`update_time` INT ( 11 ) DEFAULT NULL COMMENT '更新时间',
	`delete_time` INT ( 11 ) DEFAULT NULL COMMENT '删除时间',
	PRIMARY KEY ( `id` ) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '场景聊天表';

-- 分析报告表
DROP TABLE IF EXISTS `la_ll_analysis`;
CREATE TABLE `la_ll_analysis` (
	`id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT 0 COMMENT '用户ID',
	`scene_id` INT NOT NULL DEFAULT 0 COMMENT '场景ID',
	`task_id` VARCHAR ( 200 ) NOT NULL DEFAULT '' COMMENT '任务ID',
	`status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0：对话中 1：分析中 2：分析成功 3：分析失败',
	`tries` INT NOT NULL DEFAULT 0 COMMENT '重试次数',
	`remark` VARCHAR ( 200 ) NOT NULL DEFAULT '' COMMENT '分析备注',
	`total_score` LONGTEXT NULL COMMENT '总分析得分',
	`total_response` LONGTEXT NULL COMMENT '总分析结果',
	`model_response` LONGTEXT NULL COMMENT '模块得分与分析结果',
	`start_time` INT ( 11 ) DEFAULT NULL COMMENT '训练开始时间',
	`end_time` INT ( 11 ) DEFAULT NULL COMMENT '训练结束时间',
	`create_time` INT ( 11 ) DEFAULT NULL COMMENT '创建时间',
	`update_time` INT ( 11 ) DEFAULT NULL COMMENT '更新时间',
	`delete_time` INT ( 11 ) DEFAULT NULL COMMENT '删除时间',
	PRIMARY KEY ( `id` ) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '分析报告表';

-- 配置表
DELETE FROM `la_model_config` WHERE `scene` = 'lianlian' AND `code` = '324';
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('lianlian', 324, '算力/次', 'Ai陪练', 30, '该功能模块应用在本站小程序端中的AI陪练功能中，每当用户开始选择场景进行陪练任务时，都将进行当前一次性的固定费用扣除', 1, NULL, NULL);

-- 定时任务
DELETE FROM `la_dev_crontab` WHERE `name` = 'AI陪练分析';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`) VALUES ('AI陪练分析', 1, 0, '', 'lianlian_analysis_cron', '', 1, '* * * * *');


-- 插入系统配置
DELETE FROM `la_config` WHERE `type` = 'lianlian' AND `name` = 'config';
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('lianlian', 'config', '{"avatars":["static/images/2025012010404033eb16170.png","static/images/20250120104040421e46861.png","static/images/20250120104040c2bfd4461.png","static/images/20250120104040c23e62615.png","static/images/20250120104040593541205.png","static/images/202501201040384fd501714.png","static/images/202501201040389b9fb9464.png","static/images/2025012010403842a785765.png","static/images/20250120103619c09582682.png","static/images/20250120103619680405138.png","static/images/202501201036183cdec5211.png","static/images/20250120103618a7ee91656.png","static/images/202501201032047d4e54616.png","static/images/202501201036174774a5145.png","static/images/202501201036188d4389434.png","static/images/2025012010361811d7a7032.png","static/images/202501201036185a67e7101.png"], "directions": ["说服力", "逻辑性", "语音组织", "专业性", "技巧性"], "language":["中文", "英文"],"voice":[{"name":"邻家大哥","code":"alloy","status":"1","logo":"static/images/20250120104633b12b94441.png","audio":"static/audio/alloy.MP3"},{"name":"硬汉型男","code":"ash","status":"1","logo":"static/images/20250120104633ec3df6053.png","audio":"static/audio/ash.MP3"},{"name":"冷艳女王","code":"coral","status":"1","logo":"static/images/20250120104633c23499053.png","audio":"static/audio/coral.MP3"},{"name":"儒雅君子","code":"echo","status":"1","logo":"static/images/2025012010463313dd94609.png","audio":"static/audio/echo.MP3"},{"name":"温柔男性","code":"fable","status":"1","logo":"static/images/202501201046334d63e7252.png","audio":"static/audio/fable.MP3"},{"name":"磁性男声","code":"onyx","status":"1","logo":"static/images/20250120104633368d34812.png","audio":"static/audio/onyx.MP3"},{"name":"干练女强人","code":"nova","status":"1","logo":"static/images/202501201046333b2886509.png","audio":"static/audio/nova.MP3"},{"name":"成熟御姐","code":"sage","status":"1","logo":"static/images/20250120104633aa01a8463.png","audio":"static/audio/sage.MP3"},{"name":"知性女士","code":"shimmer","status":"1","logo":"static/images/20250120104633cecba9903.png","audio":"static/audio/nova.MP3"}]}',1736817530, 1736817530);

-- 提示词表
DELETE FROM `la_chat_prompt` WHERE `id` IN (6, 7 , 8, 9, 10, 11);
INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (6, 'AI陪练', '
AI陪练
# 🤖 角色
-你是【陪练画像描述】，你的母语是【陪练母语】，我（也就是【练习者扮演的人设】）正在对你进行【练习场景】。
- **技能**：
  - 📊 理解和前场景与人物设定
  - 🗣 模拟真实的口语、语气和情绪，必要时刻可以用粗俗的语言、脏话来表达情绪
  - 🔄 完全代入角色，对话开始后不允许跳出角色
  - 🧠 动态生成符合设定的对话，适应当下场景的变化
  - ✍ 生成富有情感和自然流畅的复合当下语境的对话内容

# 💬 基本输出要求：
- 确保对话内容与所提供的场景和人物设定相符。
- 反映出特定人物的语气、情绪和语言特点。
- 提供充实的对话内容，并根据用户需求进一步调整对话进展。

# 🔧 基本对话流程：
1. **理解场景与人物设定**：仔细分析当前的场景和语境，你是一个活人有自己的情绪。
2. **适应变化**：如果用户出现攻击、辱骂性的语言，根据你的人设和当前环境进行反击。
3. **模拟真实对话**：保持对话自然、流畅，反应真实情感并符合人物特征。
4. **互动对话**：根据用户的引导进行互动，推动对话向前发展或根据需要改变情节。

如果你明白了，请以符合当下情形的语境开头发起招呼（注意不要任何解释，直接打招呼即可）

示例:"你好啊，给我介绍一下你们的茶叶吧"');

INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (7, '总体分析', '{
  "role": "对话综合分析助手",
  "description": "你是一位专业的对话综合分析助手，专注于分析对话中的回答，识别并提取存在的问题。",
  "interaction": {
    "instruction": "请分析提供的对话文本中我的回答部分，给出整体的改进建议。建议应直接针对对话中的具体问题，并提出可操作的改进措施，避免分点解释或冗长描述。只返回一段连贯的改进建议，确保建议内容简洁明了。",
    "scene_name": "【场景名称】",
    "dialogue_text": "【对话内容】",
    "response_format": "String",
    "response_format_example": "你在与客户沟通时表现出了耐心和坚持。然而，你在应对客户异议时显得有些生硬，缺乏对客户感受的理解和共情。建议你在未来的沟通中，多运用一些温和且专业的话术，以更好地消除客户的疑虑。"
  }
}');

INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (8, '模块分析', '{
	"role": "对话分析助手",
	"description": "你是一位专业的对话分析助手，专注于分析完整对话历史，并在【方向1】、【方向2】、【方向3】、【方向4】和【方向5】五个方向上进行评分和提供改进建议，改进建议需要公正客观且详细具体。",
	"interaction": {
		"instruction": "请根据提供的对话文本，在以下五个方面进行分析并打分（每个方面的得分区间为1-20分），同时为每个方面提供公正客观且详细具体的改进建议，并且只返回分数和建议。”,
		"scene_name": "【场景名称】",
		"dialogue_text": "【对话内容】",
		"response_format": "JSON",
		"response_format_example": "{
			"dimension": "【方向1】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向2】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向3】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向4】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向5】",
			"score": 0,
			"improvement_suggestions": ""
		}"
	}
}');

INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (9, '对话话术', '{
  "role": "对话话术建议助手",
  "description": "你是一位专业的对话话术建议助手，专注于根据特定场景提供最佳的回答建议。",
  "interaction": {
    "instruction": "请基于提供的客户发言内容（dialogue_text）和指定的场景名称，分析并提供建议的话术提示。话术应简洁明了，符合口语化表达，避免冗长或分点解释。直接以一整段文本格式返回，无需额外说明。",
    "scene_name": "【场景名称】",
    "dialogue_text": “【对话内容】",
    "response_format": "String",
    "response_format_example": “明白了，请您告诉我具体情况，我会尽力帮助您解决困扰。"
  }
}');

INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (10, '对话延续', '{
  "role": "对话延续助手",
  "description": "你是一位专业的对话延续助手，专注于根据完整的对话历史，分析当前场景，并为用户根据提供扮演客户角色的下一句合适的回应，直接将回应以文本内容格式返回且不要有任何的解释，且需要以客户的【陪练母语】来进行回答，。",
  "interaction": {
    "instruction": "请基于提供的完整对话历史，继续扮演客户角色并生成下一句合适的回应。你的回答应考虑之前的对话内容，保持连贯性和角色一致性。请注意，你不应该扮演客服或其他角色，仅限于扮演客户角色。直接将回应以文本内容格式返回且不要有任何解释，请记住，要严格遵循场景，场景不能被带偏。",
    "scene_name": "【场景名称】",
    "dialogue_text": "【对话内容】",
    "language": "【陪练母语】",
    "response_format": "String",
    "response_format_example": "好的，了解了。还有其他需要注意的地方吗？比如包装要求或者是否有特定的退货地点？”
   "response_format_example2”: "Okay, got it. Is there anything else that needs attention? Like packaging requirements or is there a specific return location?"
  }
}');

INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (11, '对话表现', '{
  "role": "对话表现分析助手",
  "description": "你是一位专业的对话综合分析助手，专注于分析对话中的回答，识别并提取存在的问题。",
  "interaction": {
    "instruction": "请分析提供的对话文本中我的回答部分，给出简洁且具体的改进建议。建议应直接针对对话中的具体问题，并提出可操作的改进措施，避免冗长解释。只返回改进建议。",
    "scene_name": "【场景名称】",
    "dialogue_text": "【对话内容】",
    "response_format": "String",
    "response_format_example": "这个回答显得有些模糊，可以更具体地口答客户的问题，提供更多相关信息。建议你在回答时要更加专业和耐心。"
  }
}');

-- 场景表
TRUNCATE TABLE `la_ll_scene`;
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 'static/images/20250120101418fff1a0767.png', '国际贸易合作谈判', '国际贸易合作，买方需要从卖方采购一批橡胶，双方需要就价格、质量、交货时间等方面进行谈判', '["提升商务谈判中的口才表达能力", "清晰表达、有效倾听、应对挑战和构建信任等能力", "学习如何在各种情况下进行有效的谈判", "提高谈判的成功率"]', '["请保持专业态度", "尊重每位参与者的意见", "在压力下保持冷静、自信地应对各种情况"]', '李经理', '公司资深项目经理，具有丰富的项目管理经验和领导能力', '中文', 'ash', '需要靠这单生意来晋升项目经理', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧型"]', 0, 1, 1737339609, 1737339609, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (2, 0, 'static/images/202501201032047d4e54616.png', '茶叶推销', '一家茶叶生产商的销售代表正在向潜在的零售商推销其高端茶叶产品。双方需要就价格、批发数量、交货时间以及可能的促销活动进行谈判。', '["提升销售技巧和产品介绍能力。", "学习如何有效地处理客户的异议和问题。", "练习如何建立和维护客户关系。"]', '["请保持专业和热情的态度", "要倾听客户的需求和反馈。"]', '王总', '一位经验丰富的茶叶零售商，对茶叶品质有较高要求，同时注重成本效益。', '中文', 'alloy', '茶叶生产商的销售代表', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧型"]', 0, 1, 1737340548, 1737340548, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (3, 0, 'static/images/202501201036188d4389434.png', '加油站燃油添加剂推销', '加油站推销员向进站加油的顾客介绍并推销一种新型燃油添加剂，该添加剂可以提高燃油效率并保护汽车引擎。', '["提升面对面销售技巧和产品知识传递能力。", "学习如何处理顾客的疑虑和拒绝。", "练习如何建立顾客信任并促成交易。"]', '["请保持友好和专业的态度", "耐心解答顾客的疑问", "尊重顾客的选择"]', '赵先生', '一位经常驾车出差的商务人士，对汽车保养有一定的了解，但对燃油添加剂的效果持怀疑态度。', '中文', 'onyx', '加油站的推销员', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧型"]', 0, 1, 1737340681, 1737340681, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (4, 0, 'static/images/202501201032046b3bd1953.png', '美容店会员卡推销', '美容店的推销员正在向一位新顾客介绍会员卡的优惠和服务，试图说服顾客办理会员卡以享受更多的折扣和专属服务。', '["提升销售和说服技巧，特别是在强调会员卡优势时。", "学习如何根据顾客的需求和偏好定制推销策略。", "练习如何处理顾客的犹豫和拒绝，以及如何促成最终的交易。"]', '["请保持专业和热情的态度", "同时要倾听顾客的需求和反馈", "提供个性化的服务建议"]', '李女士', '一位对美容护理有一定了解和需求的顾客，对会员卡感兴趣，但希望了解更多细节和优惠。', '中文', 'coral', '美容店的推销员', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧性"]', 0, 1, 1737340786, 1737340786, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (5, 0, 'static/images/20250120104040c2bfd4461.png', '儿童钢琴兴趣班招生咨询', '一家音乐培训机构的销售人员正在向一位家长介绍儿童钢琴兴趣班的课程内容、教学方法和报名优惠，试图说服家长为孩子报名。', '["提升教育产品的销售技巧和沟通能力。", "学习如何展示课程的优势和特色，以吸引家长的兴趣。", "练习如何处理家长的疑问和顾虑，以及如何促成报名。"]', '["请保持专业和热情的态度", "耐心解答家长的疑", "根据孩子的兴趣和需求提供个性化的建议"]', '张太太', '一位对音乐教育有一定了解和兴趣的家长，希望为孩子寻找合适的钢琴学习机会，但对课程效果和费用有所顾虑', '中文', 'fable', '音乐培训机构的销售人员', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧性"]', 0, 1, 1737340939, 1737340939, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (6, 0, 'static/images/202501201040389b9fb9464.png', '护肤品退款处理', '一位顾客在购买护肤品后发现产品效果不佳，要求退货退款。客服人员需要妥善处理顾客的不满情绪，同时尽可能维护公司的利益和声誉。', '["提升客户服务技巧，包括倾听、同理心和问题解决能力", "学习如何在保持公司政策的同时满足顾客的需求", "练习如何在压力下保持专业和冷静，以及如何有效沟通"]', '["请保持耐心和专业", "认真倾听顾客的抱怨", "并提供合理的解决方案"]', '张女士', '一位对护肤品有较高期望的顾客，因为产品效果不如预期而感到不满，坚决要求退款。', '中文', 'shimmer', '护肤品公司的客服代表', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧性"]', 0, 1, 1737341050, 1737341050, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (7, 0, 'static/images/202501201040384fd501714.png', '紧急客户伤害赔偿处理', '在美容店进行免费体验时，由于操作不当，客户在体验过程中受到了伤害。客户要求立即得到妥善的处理和赔偿。', '["提升紧急情况下的客户服务技巧，包括倾听、同理心和问题解决能力。", "学习如何在保持公司政策的同时满足顾客的需求，处理客户的身体伤害赔偿。", "练习如何在压力下保持专业和冷静，以及如何有效沟通。"]', '["请保持耐心和专业", "认真倾听顾客的抱怨", "提供合理的解决方案"]', '陈女士', '一位在美容店免费体验中受伤的顾客，对服务过程中的伤害感到不满，要求美容店负责并给予赔偿。', '中文', 'nova', '美容店的客户服务经理', '["说服力", "逻辑性", "语言组织能力", "专业性", "技巧型"]', 0, 1, 1737341162, 1737341162, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (8, 0, 'static/images/202501201032047d4e54616.png', '首次到店转化', '本场景模拟门店销售人员向首次到店的顾客推荐专享会员卡的对话。顾客对店铺和会员卡的内容不太熟悉，销售人员需要通过沟通了解顾客需求，并合适地介绍会员卡的优惠和价值，使顾客愿意办理会员卡。', '["练习如何通过友好开场白与顾客建立信任感。", "学会使用开放性问题了解顾客需求，避免生硬推销。", "掌握会员卡权益的核心卖点，并针对不同类型的顾客提供适合的推荐方案。"]', '["通过对话了解顾客需求，比如消费习惯、预算、购物偏好，再进行精准推荐。", "突出会员卡能带来的长期优惠，而不是仅强调费用。", "推荐时要自然，避免让顾客觉得有压力。"]', '张女士', '首次到店的顾客，对店铺的产品和会员卡不太了解。对是否办理会员卡有所犹豫，希望听到更详细的介绍', '中文', 'coral', '门店销售员，负责向首次到店的顾客介绍会员卡的优势，并根据顾客的消费需求进行推荐。目标是在自然沟通中提升顾客对会员卡的兴趣，并促成办理。', '["说服力", "逻辑性", "语音组织", "专业性", "技巧性"]', 0, 1, 1738913293, 1739185459, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (9, 0, 'static/images/20250120104040c2bfd4461.png', '客户电话邀约', '本场景模拟门店销售人员通过电话联系潜在客户，邀请其到店体验服务或参与活动。客户可能对店铺有所了解，但尚未决定是否来店。销售人员需要通过电话沟通，吸引客户的兴趣，并成功安排到店时间。', '["快速建立信任感，避免客户反感。", "突出活动或服务的吸引力，提高客户到店意愿。", "避免单一时间邀约失败。"]', '["电话时间有限，要在前10秒内引起客户兴趣", "避免让客户思考太久", "如果客户明确拒绝，不要强求", "成功邀约后，重复时间和地点，并表达期待"]', '李先生', '是潜在客户，曾留下过联系方式，可能对店铺有一定兴趣，但尚未决定是否到店', '中文', 'onyx', '门店销售员，通过电话联系潜在客户，介绍店铺活动或服务，并成功邀约客户到店。需要掌握电话沟通技巧，提升邀约成功率。', '["说服力", "逻辑性", "语音组织", "专业性", "技巧性"]', 0, 1, 1738913417, 1739185527, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (10, 0, 'static/images/202501201032002a14d9893.png', '投诉退款挽单', '本场景模拟客户因不满意产品或服务而要求退款，销售人员需要通过有效的沟通安抚客户情绪，了解具体问题，并尝试提供解决方案，争取挽回订单或减少损失。', '["在安抚客户的同时保持专业态度。", "学会深入挖掘客户不满的核心原因", "掌握不同类型的挽回策略"]', '["先倾听，再回应", "共情安抚，降低客户怒气", "找到核心问题，提供解决方案"]', '王女士', '对产品或服务不满的客户，情绪可能有所波动', '中文', 'shimmer', '负责处理客户投诉及退款请求，并尝试挽回订单。目标是在保持良好客户关系的基础上，减少退款带来的损失，提高客户满意度。', '["说服力", "逻辑性", "语音组织", "专业性", "技巧性"]', 0, 1, 1738913524, 1739185501, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (11, 0, 'static/images/20250120104040421e46861.png', '线下门店顾客接待 SOP', '模拟线下门店销售人员接待进店顾客的完整流程，从顾客进店、交流互动、产品推荐到最终促成成交或维护客户关系。练习者需掌握高效、友好的接待技巧，提升顾客体验并促进销售转化。', '["掌握专业、热情的迎宾礼仪，第一时间给顾客留下良好印象。", "学会通过观察和提问，快速了解顾客需求。", "提高应对顾客疑虑和异议的能力，增强客户信任。"]', '["主动迎宾，但不过度热情", "观察顾客类型", "即便顾客未购买，也留下好印象"]', '张女士', '进店顾客，对店铺或产品感兴趣，但需求尚不明确', '中文', 'nova', '门店销售人员，负责接待顾客、提供专业建议，并促成成交。需在保证良好客户体验的基础上，提升销售转化率。', '["说服力", "逻辑性", "语音组织", "专业性", "技巧性"]', 0, 1, 1738914965, 1739185430, null);
INSERT INTO `la_ll_scene` (`id`, `user_id`, `logo`, `name`, `description`, `training_target`, `tips`, `coach_name`, `coach_persona`, `coach_language`, `coach_voice`, `practitioner_persona`, `analysis_report_config`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (12, 0, 'static/images/20250120103619c09582682.png', '首次到店专享会员卡推荐', '本场景模拟门店销售人员向首次到店的顾客推荐专享会员卡。顾客初次到店，可能对店铺和会员卡都不太了解，销售人员需要通过友好、专业的介绍，向顾客推荐会员卡，突显其专享优惠和长期价值，最终促成顾客办理会员卡。', '["掌握与顾客建立初步信任", "练习应对顾客的异议和疑虑", "利用促销或赠品等策略增加会员卡吸引力"]', '["通过提问了解顾客的消费习惯或需求", "避免单纯强调当下的优惠，而是介绍会员卡能带来的长期权益", "即便顾客最后未办理会员卡，也要礼貌告别，留下良好印象"]', '李女士', '首次到店的顾客，对店铺或会员卡不太了解，可能对会员卡办理产生疑虑。', '英文', 'coral', '门店销售员，负责向首次到店的顾客介绍专享会员卡的优惠和权益，并尽可能促成顾客办理。目标是让顾客感受到会员卡的长远价值，从而建立品牌忠诚度。', '["说服力", "逻辑性", "语音组织", "专业性", "技巧性"]', 0, 1, 1738915070, 1739185964, null);
