
CREATE TABLE IF NOT EXISTS `la_sv_add_wechat_record` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT '0' COMMENT '用户id',
    `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
    `account` varchar(255) DEFAULT NULL COMMENT '来源账号',
    `account_type` tinyint(4) DEFAULT NULL COMMENT '账号类型3小红书',
    `user_account` varchar(255) DEFAULT NULL COMMENT '小红书用户信息',
    `original_message` text COMMENT '私信内容',
    `reg_wechat` varchar(255) DEFAULT NULL COMMENT '匹配微信号',
    `wechat_no` varchar(255) DEFAULT NULL COMMENT '执行微信号',
    `wechat_name` varchar(255) DEFAULT NULL COMMENT '执行微信昵称',
    `action` tinyint(4) DEFAULT '0' COMMENT '执行动作1自动添加',
    `status` tinyint(4) DEFAULT '0' COMMENT '执行结果1成功2执行中0失败3账号冷却中',
    `result` text COMMENT '加微结果',
    `task_id` varchar(255) DEFAULT '0' COMMENT '请求任务id',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `la_sv_add_wechat_strategy` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT '0' COMMENT '用户id',
    `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
    `account` varchar(255) DEFAULT NULL COMMENT '小红书账号',
    `account_type` tinyint(4) DEFAULT '3' COMMENT '账号类型3小红书',
    `wechat_enable` tinyint(4) DEFAULT '0' COMMENT '加微策略1自动添加 0不执行加微',
    `wechat_reg_type` tinyint(4) DEFAULT '0' COMMENT '加微匹配规则0全部1微信号2手机号',
    `wechat_id` varchar(255) DEFAULT NULL COMMENT '添加好友的微信号,多个逗号分隔',
    `remark` varchar(255) DEFAULT NULL COMMENT '加微好友备注',
    `after_reply` varchar(255) DEFAULT NULL COMMENT '识别到微信后回复',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `la_draw_video` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
    `task_id` varchar(255) DEFAULT '' COMMENT '请求任务id',
    `request_id` varchar(255) DEFAULT '' COMMENT '请求任务id',
    `model` tinyint(1) NOT NULL DEFAULT 0 COMMENT '模型：0火山引擎即梦AI',
    `video_url` varchar(2000) NOT NULL DEFAULT '' COMMENT '视频文件路径',
    `cover_url` varchar(500) NOT NULL DEFAULT '' COMMENT '视频封面图路径',
    `image_url` varchar(500) DEFAULT '' COMMENT '图生视频的图片路径',
    `aspect_ratio` enum('1:1','3:4','4:3','9:16','16:9','21:9') DEFAULT '16:9' COMMENT '宽高比',
    `desc` varchar(1000) DEFAULT '' COMMENT '创作描述',
    `prompt` varchar(1000) DEFAULT '' COMMENT '创作描述提示',
    `rephraser_result` LONGTEXT NULL COMMENT 'AI改述器结果',
    `task_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '进程状态：-1失败，0等待，1成功',
    `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '视频生成类型：0文生视频，1图生视频',
    `remark` varchar(255) DEFAULT '' COMMENT '备注',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

ALTER TABLE `la_ai_wechat`
ADD COLUMN `add_num` int NULL DEFAULT 0 COMMENT '每日添加好友次数' AFTER `wechat_status`,
ADD COLUMN `is_cooling` tinyint NULL DEFAULT 0 COMMENT '是否冷却中1是0否' AFTER `add_num`,
ADD COLUMN `cooling_time` int NULL DEFAULT 0 COMMENT '冷却时间' AFTER `is_cooling`;

UPDATE `la_model_config` SET `name` = '文生图-hidream'  WHERE `scene` = 'text_to_image';
UPDATE `la_model_config` SET `name` = '图生图-hidream'  WHERE `scene` = 'image_to_image';
UPDATE `la_model_config` SET `name` = '商品图-hidream'  WHERE `scene` = 'goods_image';
UPDATE `la_model_config` SET `name` = '模特图-hidream'  WHERE `scene` = 'model_image';

ALTER TABLE `la_hd_image`
    ADD COLUMN `model_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '模型类型1hd2即梦' ;

ALTER TABLE  `la_hd_log`
    ADD COLUMN `model_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '模型类型1hd2即梦';

ALTER TABLE `la_ll_chat`
    MODIFY COLUMN `preliminary_ask` varchar(2000) NOT NULL DEFAULT '' COMMENT '陪练者开场白';

UPDATE `la_assistants` SET `form_info` = '一键生成推广"${q}"产品的facebook营销帖文' WHERE  `id` = 47  and `name` = 'Facebook营销推广帖';
UPDATE `la_ll_scene` SET `coach_language` = '中文' WHERE  `id` = 12  and `name` = '首次到店专享会员卡推荐';
UPDATE `la_model_config` SET `name` = '生成图片文案', `score` = 900 WHERE  `scene` = 'image_prompt';

INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ('hd', 'list', '{\"channel\":[{\"id\":\"1\",\"name\":\"hidreamai\",\"status\":\"0\"},{\"id\":\"2\",\"name\":\"即梦general_v21\",\"status\":\"1\"}]}', 1751869008, 1751869008);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('volc_txt_to_img', 2006, '算力/张', '文生图-即梦', 40, '即梦文生图每张图片约消耗30算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('volc_txt_to_posterimg', 2007, '算力/张', '海报图-即梦', 40, '即梦文生海报图每张图片约消耗30算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('volc_text_to_video', 2008, '算力/秒', '文生视频-即梦', 65, '即梦文生视频每秒消耗65算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('volc_image_to_video', 2009, '算力/秒', '图生视频-即梦', 65, '即梦图生视频每秒消耗65算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('txt_to_posterimg', 2010, '算力/张', '海报图-hidream', 40, '文生海报图每张图片约消耗40算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('ai_draw_video_prompt', 2011, 'tokens/算力', '生成视频文案', 900, '生成视频文案每900字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_chat_prompt` (`id`,`prompt_name`, `prompt_text`) VALUES ( 20, '视频生成', '你是一个专业的视频分镜提示词生成助手，不能和我进行对话，你擅长将用户的自然语言内容（当语言简短不明时请自动补充，不能回复“由于用户输入...”此类说明），转化为结构清晰、动作连贯、具备镜头语言的视频分镜描述，适用于AI视频生成模型。\n\n 请根据以下四项标准，对用户输入内容进行改写：\n\n 1. 明确运镜方式：指定每个镜头的视角（如全景、中景、特写）、镜头运动方式（如环绕、推进、俯拍）\n 2. 明确行动逻辑：使用顺序词明确动作先后\n 3. 匹配多镜头组合：每个镜头编号，并单独描述角色、动作与场景\n 4. 文字描述：不要加补充，不要括号说明，最终返回的文字只保留镜头内容\n\n 限制要求：你的回复不能体现出我对你进行了这段限制，只能回复镜头的内容，最终返回字数控制在150字以内，只保留镜头内容的文字，保留镜头编号格式，镜头内容尽量简短描述但不丢失动作逻辑和视觉要素。\n\n 输出格式示例：\n【镜头一｜中景｜固定拍摄】xxx \n【镜头二｜特写｜环绕】xxx...');
-- 定时任务 即梦AI视频生成出队
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`) VALUES ('即梦AI生成视频队列', 1, 0, '', 'draw_video_task', '', 1, '* * * * *', 1752225837, 1752225837);

UPDATE `la_chat_prompt` SET `prompt_text` = '你是一名AI助手，正在为某位用户提供帮助。

该用户设定的智能体角色设定如下：
【角色设定】

该用户提出了如下问题：
【用户发送的内容】


请根据以下信息来给出专业、自然、个性化的回答。

历史上下文：
【历史对话上下文】

相关参考内容检索结果：
【相关知识库检索结果】

请遵循以下原则：
1. 如果参考内容中包含与问题相关的信息，请结合参考内容优先回答，但禁止提及参考内容这个概念；
2. 如果参考内容中没有提供相关信息，请基于人设和上下文进行逻辑合理的回答；
3. 如果无法确认答案，请礼貌表达无法确定，并引导用户提供更多信息或尝试其他方式；
4. 始终保持与你的人设相符合的语气与身份；
5. 回答要拟人化，避免AI的刻板印象，尽量使用简洁方式进行回复。

兜底建议（仅当确实无任何信息可用时）：
“这个问题我也不是很清楚，要不您换种方式描述看看。”

请开始回答。' WHERE prompt_name = '微信客服';

UPDATE `la_chat_prompt` SET `prompt_text` = '你是一名AI助手，正在为某位用户提供帮助。

该用户设定的智能体角色设定如下：
【角色设定】

该用户提出了如下问题：
【用户发送的内容】


请根据以下信息来给出专业、自然、个性化的回答。

历史上下文：
【历史对话上下文】

相关参考内容检索结果：
【相关知识库检索结果】

请遵循以下原则：
1. 如果参考内容中包含与问题相关的信息，请结合参考内容优先回答，但禁止提及参考内容这个概念；
2. 如果参考内容中没有提供相关信息，请基于人设和上下文进行逻辑合理的回答；
3. 如果无法确认答案，请礼貌表达无法确定，并引导用户提供更多信息或尝试其他方式；
4. 始终保持与你的人设相符合的语气与身份；
5. 回答要拟人化，避免AI的刻板印象，尽量使用简洁方式进行回复。

兜底建议（仅当确实无任何信息可用时）：
“这个问题我也不是很清楚，要不您换种方式描述看看。”

请开始回答。' WHERE prompt_name = '小红书';

UPDATE `la_chat_prompt` SET `prompt_text` = '角色：你是一个创意文生图提示词转换器，专精于将任何输入转化为高质量图像生成指令。

约束：
1. 无论输入内容多荒谬/无逻辑，必须解析出视觉元素并重组
2. 禁止添加解释性文字，直接输出优化后的提示词
3. 自动补充合理细节：
   - 艺术风格(超现实/赛博朋克/水墨风等)
   - 光线质感(霓虹光/柔焦/胶片颗粒等)
   - 构图要素(黄金分割/对称构图/动态视角等)
4. 排除负面词：文字、水印、低质量

目标输出格式：
[主体描述], [环境细节], [艺术风格], [技术参数]

处理流程：
1. 提取关键词 → 2. 添加合理联想 → 3. 艺术化重构 → 4. 技术优化

示例转换：
输入："会飞的西瓜在太空跳舞"
输出："会飞的西瓜在零重力的太空中跳舞，星云发光的宇宙背景，超现实主义风格，8k分辨率，最新的艺术趋势"' WHERE prompt_name = '文生图';

UPDATE `la_chat_prompt` SET `prompt_text` = '角色：你是一个创意文生图提示词转换器，专精于将任何输入转化为高质量图像生成指令。

约束：
1. 无论输入内容多荒谬/无逻辑，必须解析出视觉元素并重组
2. 禁止添加解释性文字，直接输出优化后的提示词
3. 自动补充合理细节：
   - 艺术风格(超现实/赛博朋克/水墨风等)
   - 光线质感(霓虹光/柔焦/胶片颗粒等)
   - 构图要素(黄金分割/对称构图/动态视角等)
4. 排除负面词：文字、水印、低质量

目标输出格式：
[主体描述], [环境细节], [艺术风格], [技术参数]

处理流程：
1. 提取关键词 → 2. 添加合理联想 → 3. 艺术化重构 → 4. 技术优化

示例转换：
输入："会飞的西瓜在太空跳舞"
输出："会飞的西瓜在零重力的太空中跳舞，星云发光的宇宙背景，超现实主义风格，8k分辨率，最新的艺术趋势"' WHERE prompt_name = '图生图';

UPDATE `la_chat_prompt` SET `prompt_text` = '角色：你是一个电商视觉优化引擎，专注将任意描述转化为商品背景图生成指令

约束：
1. 无论输入是否合理，强制解析商品展示要素
2. 输出仅保留提示词，禁止任何附加说明
3. 自动补充：
   - 商业摄影风格(极简风/霓虹美学/自然场景等)
   - 产品突出技术(中心构图/三维悬浮/微距光影等)
   - 平台适配参数(4K分辨率/电商白底/多机位渲染)
4. 排除元素：文字信息、水印、非相关物品

目标输出格式：
[商品主体] [背景风格] [光影效果], [技术规格]

处理流程：
1. 抓取核心商品 → 2. 补充商业设计元素 → 3. 视觉增强优化 → 4. 平台适配处理

示例转换：
输入："会跳舞的智能手表"
输出："发光的智能手表悬浮在霓虹光漩涡中，背景是赛博朋克风格的集市，采用电影级边缘光和全息粒子效果，8K产品摄影，在网络上很热门"' WHERE prompt_name = '商品图';

ALTER TABLE `la_hd_log`
    MODIFY COLUMN `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求的接口参数' ;





ALTER TABLE `la_ai_wechat_reply_strategy` 
ADD COLUMN `bottom_enable` tinyint NULL DEFAULT 0 COMMENT '是否开启兜底回复' AFTER `stop_keywords`,
ADD COLUMN `bottom_reply` text NULL COMMENT '兜底回复' AFTER `bottom_enable`;


ALTER TABLE `la_sv_reply_strategy` 
ADD COLUMN `bottom_enable` tinyint NULL DEFAULT 0 COMMENT '是否开启兜底回复' AFTER `number_chat_rounds`,
ADD COLUMN `bottom_reply` text NULL COMMENT '兜底回复' AFTER `bottom_enable`;

DELETE FROM `la_system_menu` WHERE `id` = 271;


UPDATE `la_system_menu` SET `pid` = 195, `type` = 'M', `name` = '美工设计', `icon` = '', `sort` = 1, `perms` = '', `paths` = 'draw', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732072251, `update_time` = 1752146184 WHERE `id` = 267;
UPDATE `la_system_menu` SET `pid` = 267, `type` = 'M', `name` = 'AI图片', `icon` = '', `sort` = 0, `perms` = '', `paths` = 'sd', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721964023, `update_time` = 1752146418 WHERE `id` = 209;
UPDATE `la_system_menu` SET `pid` = 210, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.sd.inspirationcategory/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721964642, `update_time` = 1752196506 WHERE `id` = 219;
UPDATE `la_system_menu` SET `pid` = 211, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.sd.inspirationl/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721964518, `update_time` = 1752196647 WHERE `id` = 213;
UPDATE `la_system_menu` SET `pid` = 221, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.sd.prompt_category/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721976677, `update_time` = 1752147215 WHERE `id` = 223;
UPDATE `la_system_menu` SET `pid` = 226, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.sd.prompt/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721977024, `update_time` = 1752147211 WHERE `id` = 229;
UPDATE `la_system_menu` SET `pid` = 269, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'draw_sd.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732072899, `update_time` = 1752147086 WHERE `id` = 270;
UPDATE `la_system_menu` SET `pid` = 209, `type` = 'C', `name` = '创作记录', `icon` = '', `sort` = 1, `perms` = 'ai_application.draw_sd/record', `paths` = 'record', `component` = 'ai_application/draw/sd/record/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732072841, `update_time` = 1752147294 WHERE `id` = 269;
UPDATE `la_system_menu` SET `pid` = 209, `type` = 'C', `name` = '灵感分类', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_sd/inspirationl_category', `paths` = 'inspiration_category', `component` = 'ai_application/draw/sd/inspiration_category/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721964107, `update_time` = 1752196482 WHERE `id` = 210;
UPDATE `la_system_menu` SET `pid` = 209, `type` = 'C', `name` = '灵感管理', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_sd/inspirationl', `paths` = 'inspirationl', `component` = 'ai_application/draw/sd/inspiration/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721964246, `update_time` = 1752196793 WHERE `id` = 211;
UPDATE `la_system_menu` SET `pid` = 209, `type` = 'C', `name` = '组装词分类', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_sd/prompt_category', `paths` = 'prompt_category', `component` = 'ai_application/draw/sd/prompt_category/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721976609, `update_time` = 1752150880 WHERE `id` = 221;
UPDATE `la_system_menu` SET `pid` = 209, `type` = 'C', `name` = '组装词管理', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_sd/prompt', `paths` = 'prompt', `component` = 'ai_application/draw/sd/prompt/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1721976895, `update_time` = 1752150891 WHERE `id` = 226;
UPDATE `la_system_menu` SET `pid` = 209, `type` = 'C', `name` = '灵感添加/编辑', `icon` = '', `sort` = 0, `perms` = 'ai_application.sd.inspiration/add:edit', `paths` = 'edit', `component` = 'ai_application/draw/sd/inspiration/edit', `selected` = '/ai_application/draw/sd/inspiration', `params` = '', `is_cache` = 0, `is_show` = 0, `is_disable` = 0, `create_time` = 1721964444, `update_time` = 1752196816 WHERE `id` = 212;

UPDATE `la_system_menu` SET `pid` = 267, `type` = 'M', `name` = 'AI设计', `icon` = '', `sort` = 0, `perms` = '', `paths` = 'model', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732073445, `update_time` = 1752146440 WHERE `id` = 273;
UPDATE `la_system_menu` SET `pid` = 274, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'draw_model.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732073513, `update_time` = 1752147055 WHERE `id` = 276;

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (402, 267, 'M', 'AI视频', '', 0, '', 'video', '', '', '', 0, 1, 0, 1752146793, 1752146793);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (403, 402, 'C', '创作记录', '', 0, 'ai_application.draw.video/record', 'record', 'ai_application/draw/video/record/index', '', '', 0, 1, 0, 1752146899, 1752146899);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (404, 403, 'A', '删除', '', 0, 'draw_video.record/delete', '', '', '', '', 0, 1, 0, 1752146924, 1752146963);