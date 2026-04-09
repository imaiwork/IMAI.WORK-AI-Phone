CREATE TABLE IF NOT EXISTS `la_ai_persona` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `persona_name` varchar(100) NOT NULL COMMENT '人设名称(如: 好滑的滑雪场)',
    `persona_type` tinyint(1) NOT NULL COMMENT '人设类型: 1=个人IP 2=企业服务 3=本地商家',
    `publish_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '发布模式: 1=根据素材制作视频发送 2=直接发送素材内容',
    `avatar_url` varchar(1000) DEFAULT '' COMMENT '人设头像URL',
    `quick_desc` varchar(1000) DEFAULT '' COMMENT '一句话极速描述',
    `persona_desc` text COMMENT '人设详细描述',
    `industry` varchar(200) DEFAULT '' COMMENT '所属行业',
    `is_configured` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否配置完成: 0=未完成 1=已完成',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用 1=启用',
    `report_content` longtext COMMENT 'AI生成的人设报告内容(JSON格式)',
    `report_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '报告生成状态: 0=未生成 1=生成中 2=已生成 3=生成失败',
    `report_gen_time` int(11) DEFAULT NULL COMMENT '报告最新生成时间',
    `main_business` text COMMENT '知识库：主营业务与产品',
    `target_pain_points` text COMMENT '知识库：目标客户与痛点',
    `conversion_hook` text COMMENT '知识库：核心优势与转化诱饵',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    `delete_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_persona_type` (`persona_type`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设主表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_individual` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联iw_ai_persona主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `nickname` varchar(100) DEFAULT NULL COMMENT '昵称/网名',
    `identity` varchar(1000) DEFAULT NULL COMMENT '真实身份/职业: 创业者/职场精英/全职宝妈/自由职业/学生党/行业专家',
    `personality_tags` varchar(1000) DEFAULT NULL COMMENT '性格标签(JSON数组): 热情开朗/专业严谨/幽默风趣/成熟稳重/接地气',
    `core_value` text COMMENT '我能提供的核心价值(如: 搞钱思路、穿搭技巧、情感咨询)',
    `highlight_story` text COMMENT '个人高光/逆袭故事',
    `target_audience` text COMMENT '想吸引什么样的粉丝',
    `monetize_paths` varchar(1000) DEFAULT NULL COMMENT '主要变现路径(JSON数组): 知识付费/直播带货/商单广告/私域咨询/纯分享积累',
    `clue_acquire_keywords` json DEFAULT NULL COMMENT '获客截流线索词',
    `clue_intercept_keywords` json DEFAULT NULL COMMENT '获客截流匹配词',
    `clue_comment_scripts` json DEFAULT NULL COMMENT '获客截流评论词',
    `clue_dm_scripts` json DEFAULT NULL COMMENT '获客截流私信话术',
    `wechat_add_friend_script` json DEFAULT NULL COMMENT '好友申请备注',
    `wechat_comment_speech` json DEFAULT NULL COMMENT '朋友圈评论话术',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    `delete_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_persona_id` (`persona_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设-个人IP扩展表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_enterprise` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联iw_ai_persona主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `brand_name` varchar(100) DEFAULT NULL COMMENT '企业/品牌名称',
    `spokesperson` varchar(1000) DEFAULT NULL COMMENT '谁代表公司出镜: 老板/技术大牛/金牌销售/产品经理/官方虚拟人/客服代表',
    `brand_tone` varchar(1000) DEFAULT NULL COMMENT '品牌调性(JSON数组): 热情开朗/专业严谨/幽默风趣/成熟稳重/接地气',
    `main_product` text COMMENT '主打的产品/解决方案(如: 财务一体化、获客系统、法律咨询)',
    `industry_case` text COMMENT '行业背书/标杆案例',
    `target_customer` text COMMENT '目标客户画像',
    `account_goal` varchar(1000) DEFAULT NULL COMMENT '账号核心目的(JSON数组): 留资获客/品牌宣发/展会引流/客户教育/招商加盟',
    `clue_acquire_keywords` json DEFAULT NULL COMMENT '获客截流线索词',
    `clue_intercept_keywords` json DEFAULT NULL COMMENT '获客截流匹配词',
    `clue_comment_scripts` json DEFAULT NULL COMMENT '获客截流评论词',
    `clue_dm_scripts` json DEFAULT NULL COMMENT '获客截流私信话术',
    `wechat_add_friend_script` json DEFAULT NULL COMMENT '好友申请备注',
    `wechat_comment_speech` json DEFAULT NULL COMMENT '朋友圈评论话术',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    `delete_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_persona_id` (`persona_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设-企业服务扩展表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_local` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联iw_ai_persona主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `store_name` varchar(200) DEFAULT NULL COMMENT '门店名称+所在商圈',
    `spokesperson` varchar(1000) DEFAULT NULL COMMENT '谁出镜揽客: 老板/漂亮老板娘/搞笑店长/探店顾客',
    `store_atmosphere` varchar(1000) DEFAULT NULL COMMENT '门店氛围感(JSON数组): 市井烟火气/网红打卡地/高端奢华/排队王/温馨解压/接地气',
    `signature_feature` text COMMENT '招牌特色(如: 秘制配方、网红爆款、性价比之王)',
    `open_story` text COMMENT '开店初衷/故事',
    `target_customer` text COMMENT '主要想吸引谁进店',
    `content_preference` varchar(1000) DEFAULT NULL COMMENT '偏好的引流内容(JSON数组): 团购口播/沉浸式创/探店剧情/老板日常/客户真实反馈',
    `clue_acquire_keywords` json DEFAULT NULL COMMENT '获客截流线索词',
    `clue_intercept_keywords` json DEFAULT NULL COMMENT '获客截流匹配词',
    `clue_comment_scripts` json DEFAULT NULL COMMENT '获客截流评论词',
    `clue_dm_scripts` json DEFAULT NULL COMMENT '获客截流私信话术',
    `wechat_add_friend_script` json DEFAULT NULL COMMENT '好友申请备注',
    `wechat_comment_speech` json DEFAULT NULL COMMENT '朋友圈评论话术',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    `delete_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_persona_id` (`persona_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设-本地商家扩展表';

ALTER TABLE `la_sv_publish_setting`
MODIFY COLUMN `accounts` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '账号集合' AFTER `name`;

ALTER TABLE `la_sv_account`
ADD INDEX (`device_code`) USING BTREE,
ADD INDEX (`type`) USING BTREE;

ALTER TABLE `la_sv_device_task`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `sub_data_id`;

ALTER TABLE `la_sv_device`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT 'ip人设id' AFTER `mode`;

ALTER TABLE `la_sv_device_circle_like_reply`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT 'ip人设id' AFTER `auto_reply_config_id`;

ALTER TABLE `la_sv_device_circle_like_reply_account`
ADD COLUMN `remark` varchar(500) DEFAULT NULL COMMENT '执行结果' AFTER `status`,
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT 'ip人设id' AFTER `remark`;

ALTER TABLE `la_sv_lead_scraping_setting`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT 'ip人设id' AFTER `task_date`;

ALTER TABLE `la_sv_lead_scraping_setting_account`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT 'ip人设id' AFTER `published_count`;

ALTER TABLE `la_sv_device_active`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `time_config`;

ALTER TABLE `la_sv_device_active_account`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `interactive_times`;

ALTER TABLE `la_sv_crawling_task`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `wechat_custom_date`;

ALTER TABLE `la_sv_crawling_task_device_bind`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `device_code`;

ALTER TABLE `la_sv_device_take_over_task`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `time_config`;

ALTER TABLE `la_sv_device_take_over_task_account`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `status`;

ALTER TABLE `la_ai_wechat_circle_task`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `send_status`;

ALTER TABLE `la_ai_wechat_circle_task_config`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `auto_type`;

ALTER TABLE `la_sv_publish_setting`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `status`;

ALTER TABLE `la_sv_publish_setting_account`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `data_type`;

ALTER TABLE `la_sv_publish_setting_detail`
ADD COLUMN `persona_id` int NULL DEFAULT 0 COMMENT '人设id' AFTER `exec_time`;

ALTER TABLE `la_sv_crawling_task`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_sv_device_take_over_task`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_sv_lead_scraping_setting`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_sv_device_circle_like_reply`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_sv_crawling_manual_task`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `custom_date`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_sv_device_active`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_ai_wechat_circle_task_config`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`,
ADD COLUMN `minutes` int NULL DEFAULT 0 COMMENT '执行时长（min）' AFTER `task_exec_type`;

ALTER TABLE `la_sv_publish_setting`
ADD COLUMN `task_exec_type` tinyint NULL DEFAULT 0 COMMENT '执行类型1立即执行0定时执行' AFTER `persona_id`;

ALTER TABLE `la_sv_device`
ADD COLUMN `is_first` tinyint NULL DEFAULT 0 COMMENT '人设切换值更新为1' AFTER `persona_id`;

ALTER TABLE `la_sv_device_task` 
ADD INDEX(`auto_type`) USING BTREE,
ADD INDEX(`day`) USING BTREE;

UPDATE `la_dev_crontab` SET `status` = 2 WHERE `command` = 'ai_circle_reply_like';


ALTER TABLE `la_sv_add_wechat_record`
ADD COLUMN `intention_type` tinyint(1) NULL DEFAULT -1 COMMENT '意图类型-1待处理,0其他,1成交意愿,2询价意愿,3想要加微信,4一般意愿,5明确拒绝';

CREATE TABLE IF NOT EXISTS `la_ai_persona_material` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联人设ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `material_name` varchar(200) NOT NULL COMMENT '素材名称',
    `material_type` tinyint(1) NOT NULL COMMENT '素材类型: 1=视频 2=图片',
    `file_url` varchar(1000) NOT NULL COMMENT '文件存储URL',
    `thumbnail_url` varchar(1000) DEFAULT NULL COMMENT '缩略图URL',
    `duration` int(8) DEFAULT NULL COMMENT '视频时长(秒)',
    `width` int(8) DEFAULT NULL COMMENT '宽度(px)',
    `height` int(8) DEFAULT NULL COMMENT '高度(px)',
    `use_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '使用状态: 0=已删除 1=启用中 2=已停用',
    `publish_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '发布模式: 1=根据素材制作视频发送 2=直接发送素材内容',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_persona_id` (`persona_id`),
    KEY `idx_material_type` (`material_type`),
    KEY `idx_use_status` (`use_status`),
    KEY `idx_user_id` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '人设素材库表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_digital_avatar` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联人设ID',
    `dh_id` int(11) unsigned NOT NULL COMMENT '关联公共形象ID',
    `avatar_name` varchar(128) NOT NULL COMMENT '形象名称 (如: 数字人111)',
    `cover_url` varchar(1000) NOT NULL COMMENT '形象封面图URL',
    `video_url` varchar(1000) DEFAULT NULL COMMENT '形象视频/动作素材URL',
    `duration` int(6) DEFAULT '0' COMMENT '素材时长(秒)，用于展示如 01:30',
    `width` int(6) DEFAULT NULL COMMENT '视频/封面宽度(px)',
    `height` int(6) DEFAULT NULL COMMENT '视频/封面高度(px)',
    `third_avatar_id` varchar(128) DEFAULT NULL COMMENT '第三方平台形象ID(用于调用生成接口)',
    `sort` int(8) NOT NULL DEFAULT '0' COMMENT '排序权重(越大越靠前)',
    `third_voice_id` varchar(128) NOT NULL DEFAULT '' COMMENT '第三方平台音色ID(用于调用生成接口)',
    `is_original_voice` tinyint(1) NOT NULL DEFAULT '1' COMMENT '形象原音 0否 1是',
    `voice_url` varchar(1000) DEFAULT '' NULL COMMENT '形象音色URL',
    `voice_name` varchar(128) DEFAULT '' COMMENT '音色名称',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_persona_id` (`persona_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设的数字人形象配置表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_digital_voice` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联人设ID',
    `voice_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '音色库ID',
    `voice_name` varchar(128) NOT NULL COMMENT '音色名称 (如: 12345678998)',
    `provider` varchar(64) DEFAULT NULL COMMENT '服务提供商/标签 (如: 闪剪)',
    `preview_audio_url` varchar(1000) DEFAULT NULL COMMENT '试听音频URL',
    `third_voice_id` varchar(128) DEFAULT NULL COMMENT '第三方平台音色ID(用于调用生成接口)',
    `sort` int(8) NOT NULL DEFAULT '0' COMMENT '排序权重(越大越靠前)',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_persona_id` (`persona_id`),
    KEY `idx_voice_id` (`voice_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设音色配置表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_agent_config` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联ai_persona主键ID',
    `comment_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '社媒评论区接管开关: 0=关闭 1=开启',
    `comment_agent_id` int(11) unsigned DEFAULT NULL COMMENT '社媒评论区执行智能体ID',
    `dm_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '社媒私信接管开关: 0=关闭 1=开启',
    `dm_agent_id` int(11) unsigned DEFAULT NULL COMMENT '社媒私信执行智能体ID',
    `wechat_chat_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '微信1V1私聊接管开关: 0=关闭 1=开启',
    `wechat_chat_agent_id` int(11) unsigned DEFAULT NULL COMMENT '微信1V1私聊执行智能体ID',
    `moments_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '朋友圈互动接管开关: 0=关闭 1=开启',
    `moments_agent_id` int(11) unsigned DEFAULT NULL COMMENT '朋友圈互动执行智能体ID',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_persona_id` (`persona_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_comment_agent_id` (`comment_agent_id`),
    KEY `idx_dm_agent_id` (`dm_agent_id`),
    KEY `idx_wechat_chat_agent_id` (`wechat_chat_agent_id`),
    KEY `idx_moments_agent_id` (`moments_agent_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '智能体设置配置表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_material_use_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
  `persona_id` int(11) unsigned NOT NULL COMMENT '关联人设ID',
  `material_id` int(11) unsigned NOT NULL COMMENT '关联素材ID',
  `task_id` int(11) unsigned DEFAULT NULL COMMENT '关联AI生成任务ID（任务使用时记录）',
  `publish_mode` tinyint(4) NOT NULL DEFAULT '1' COMMENT '发布模式: 1=根据素材制作视频发送 2=直接发送素材内容',
  `use_scene` tinyint(1) NOT NULL DEFAULT '1' COMMENT '使用场景: 1=AI生成任务 2=内容发布 3=设备分发',
  `use_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '使用状态: 0=使用中 1=使用成功 2=使用失败',
  `fail_reason` varchar(500) DEFAULT NULL COMMENT '失败原因',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_persona_id` (`persona_id`),
  KEY `idx_material_id` (`material_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_use_scene` (`use_scene`),
  KEY `idx_use_status` (`use_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材使用记录表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_traffic_config` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联ai_persona主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `acquire_keywords` json DEFAULT NULL COMMENT '获客线索词 ["火锅店","麻辣火锅","牛油火锅"]',
    `intercept_keywords` json DEFAULT NULL COMMENT '截流线索词 ["火锅店","麻辣火锅","牛油火锅"]',
    `comment_scripts` json DEFAULT NULL COMMENT '评论区引流话术 ["私信你了宝子~","看看我"]',
    `dm_scripts` json DEFAULT NULL COMMENT '私信逼单话术 ["加我~","您的联系呢~"]',
    `message_number` int(11) DEFAULT '0' COMMENT '每天私信人数',
    `comment_number` int(11) DEFAULT '0' COMMENT '每天评论人数（同城触达评论）',
    `reply_number` int(11) DEFAULT '0' COMMENT '私信每个用户回复数量(私信接管)',
    `content_publish_day` int(11) DEFAULT '0' COMMENT '内容发布时间 0表示不限制 其余数字表示对应的天数',
    `comment_publish_day` int(11) DEFAULT '0' COMMENT '评论发布时间 0表示不限制 其余数字表示对应的天数',
    `status` tinyint(1) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
    `exec_date` date DEFAULT NULL COMMENT '执行时间',
    `remark` varchar(1000) DEFAULT NULL COMMENT '备注',
    `is_first` tinyint(1) DEFAULT '1' COMMENT '是否初始1是0否',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE,
    KEY `idx_persona_id` (`persona_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'AI人设-获客截流配置表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_wechat_interaction_config` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` int(11) unsigned NOT NULL COMMENT '所属用户ID',
    `persona_id` int(11) unsigned NOT NULL COMMENT '关联ai_persona主键ID',
    `add_friend_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启主动加好友: 0=关闭 1=开启',
    `add_friend_source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '数据源: 1=客资线索库',
    `add_friend_script` text COMMENT '好友验证申请话术',
    `is_like` tinyint(1) DEFAULT '0' COMMENT '自动点赞0否1是',
    `is_comment` tinyint(1) DEFAULT '0' COMMENT '自动评论0否1是',
    `comment_method` tinyint(1) DEFAULT '1' COMMENT '评论方式1智能拟人评论2固定话术随机',
    `comment_robot_prompt` text COMMENT '评论机器人提示词',
    `robot_params` json DEFAULT NULL COMMENT '机器人精准模式参数',
    `number` int(11) DEFAULT '15' COMMENT '每天最大互动任务，默认15',
    `comment_speech` json DEFAULT NULL COMMENT '评论固定话术',
    `status` tinyint(1) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
    `exec_time` json DEFAULT NULL COMMENT '执行时间区间',
    `exec_date` date DEFAULT NULL COMMENT '配置任务执行日期',
    `is_first` tinyint(1) DEFAULT '1' COMMENT '是否初次',
    `remark` text COMMENT '备注',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE,
    KEY `idx_persona_id` (`persona_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '微信私域互动管家配置表';

ALTER TABLE `la_shanjian_video_task`
ADD COLUMN `persona_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联人设ID';

INSERT INTO
    `la_model_config` (
        `scene`,
        `code`,
        `unit`,
        `name`,
        `score`,
        `description`,
        `status`,
        `create_time`,
        `update_time`
    )
VALUES (
        'video_copywriting_imitation',
        10205,
        '算力/次',
        '爆款仿写',
        50.00,
        '爆款仿写文案扣除算力',
        1,
        1774256953,
        1774256953
    );

CREATE TABLE IF NOT EXISTS `la_video_imitation_task` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
    `persona_id` int(11) unsigned DEFAULT '0' COMMENT '使用的AI人设ID',
    `avatar_id` int(11) unsigned default 0 not null comment '随机获取的AI人设绑定的形象ID',
    `voice_id` int(11) unsigned default 0 not null comment '音色ID',
    `is_material` tinyint(1) DEFAULT '0' COMMENT '0:使用形象 1:使用素材',
    `platform_task_id` varchar(100) DEFAULT '' COMMENT '提取任务ID',
    `shanjian_task_id` varchar(100) DEFAULT '' COMMENT '闪剪生成的远端任务ID',
    `title` varchar(255) DEFAULT '' COMMENT '视频标题',
    `prompt` text COMMENT '提取指令/URL',
    `original_text` mediumtext COMMENT '原文',
    `rewritten_text` mediumtext COMMENT '复刻文案',
    `word_count` int(11) DEFAULT '0' COMMENT '字数',
    `publish_title` varchar(255) null comment '发布标题',
    `publish_text` mediumtext null comment '发布文案',
    `publish_topic` varchar(255) null comment '发布主题',
    `publish_confirm` tinyint(1) default 0 not null comment '发布确认，0：未确认；1：已确认；',
    `analysis_tags` text COMMENT '分析标签',
    `compliance_status` varchar(200) DEFAULT '' COMMENT '合规状态',
    `persona_role` varchar(200) DEFAULT '' COMMENT '人设角色',
    `persona_tone` varchar(200) DEFAULT '' COMMENT '语气',
    `status` tinyint(1) DEFAULT '0' COMMENT '0:解析中 1:待确认文案 2:视频生成中 3:生成成功 4:生成/解析失败',
    `video_url` varchar(1000) DEFAULT '' COMMENT '最终合成视频URL',
    `thumbnail` varchar(255) DEFAULT '' COMMENT '视频封面图',
    `duration` int(11) DEFAULT '0' COMMENT '视频时长(秒)',
    `remarks` varchar(1000) DEFAULT '' COMMENT '失败原因/备注',
    `task_delete` tinyint(1) DEFAULT '0' COMMENT '任务是否删除（软删除），0：否；1：是',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id_status` (`user_id`, `status`),
    KEY `idx_user_id_persona` (`user_id`, `persona_id`),
    KEY `idx_shanjian_task_id` (`shanjian_task_id`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '视频复刻任务表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_report` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
    `persona_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '人设id',
    `contents` json DEFAULT NULL COMMENT '对话记录',
    `result` json DEFAULT NULL COMMENT '分析报告:industryType (行业类型),targetAudience (目标人群),contentType (内容类型),mainPlatform (主攻平台),platformLogic (原因),auxiliaryPlatform (辅助平台),ipContent (ip内容),bestTime (发布时间),mainLeadTopic (线索词主题),dailyReach (每天主动触达),dailyPrivateLeads (每天新增私域),dailyValidIntents (每天有效意向)',
    `is_draft` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否草稿，0：否，1：是',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE,
    KEY `idx_persona_id` (`persona_id`) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'ai人设报告表';

INSERT INTO
    `la_dev_crontab` (
        `name`,
        `type`,
        `system`,
        `remark`,
        `command`,
        `params`,
        `status`,
        `expression`,
        `error`,
        `last_time`,
        `time`,
        `max_time`,
        `create_time`,
        `update_time`,
        `delete_time`
    )
VALUES (
        '爆款仿写视频发布',
        1,
        0,
        '',
        'video_imitation_publish',
        '',
        1,
        '*/5 * * * *',
        '',
        null,
        DEFAULT,
        DEFAULT,
        1774852195,
        1774852195,
        null
    );

DELETE FROM `la_dev_crontab`
WHERE
    command = "auto_device_video_synthesis";

INSERT INTO
    `la_dev_crontab` (
        `name`,
        `type`,
        `system`,
        `remark`,
        `command`,
        `params`,
        `status`,
        `expression`,
        `error`,
        `last_time`,
        `time`,
        `max_time`,
        `create_time`,
        `update_time`,
        `delete_time`
    )
VALUES (
        '24小时ip人设视频任务生成',
        1,
        0,
        '',
        'auto_device_video_synthesis',
        '',
        1,
        '0 0 * * *',
        NULL,
        1766678409,
        '1.91',
        '1.91',
        1766542031,
        1766734271,
        NULL
    );

INSERT INTO
    `la_model_config` (
        `scene`,
        `code`,
        `unit`,
        `name`,
        `score`,
        `description`,
        `status`,
        `create_time`,
        `update_time`
    )
VALUES (
        'ai_persona_analysis',
        10314,
        '算力/次',
        'Ip人设分析',
        10.00,
        'Ip人设分析每次消耗10算力',
        1,
        1740799252,
        1740799252
    );

INSERT INTO
    `la_model_config` (
        `scene`,
        `code`,
        `unit`,
        `name`,
        `score`,
        `description`,
        `status`,
        `create_time`,
        `update_time`
    )
VALUES (
        'ai_persona_report',
        10315,
        '算力/次',
        'Ip人设报告',
        100.00,
        'Ip人设报告每次消耗100算力',
        1,
        1740799252,
        1740799252
    );

UPDATE `la_system_menu` SET `pid` = 195, `type` = 'M', `name` = 'AI手机', `icon` = '', `sort` = 0, `perms` = '', `paths` = 'device', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1747033226, `update_time` = 1774924017 WHERE `id` = 368;
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (537, 463, 'C', '分镜混剪', '', 0, 'ai_application.montage.create/storyboard', 'storyboard', 'ai_application/montage/create/storyboard/index', '', '', 0, 1, 0, 1774923448, 1774923448);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (538, 463, 'C', '分镜混剪详情', '', 0, 'ai_application.montage.create.storyboard/detail', 'storyboard_detail', 'ai_application/montage/create/storyboard/detail', '/ai_application/montage/create/storyboard', '', 0, 0, 0, 1774923519, 1774923716);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (539, 538, 'A', '删除', '', 0, 'ai_application.montage.create.storyboard.detail/delete', '', '', '', '', 0, 1, 0, 1774923550, 1774923550);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (540, 537, 'A', '删除', '', 0, 'ai_application.montage.create.storyboard/delete', '', '', '', '', 0, 1, 0, 1774923574, 1774923574);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (541, 368, 'M', 'IP人设', '', 0, '', 'person', 'ai_application/device/person/lists', '', '', 0, 1, 0, 1774924159, 1774924193);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (542, 541, 'C', '人设列表', '', 0, 'ai_application.device.person/lists', 'lists', 'ai_application/device/person/lists', '', '', 0, 1, 0, 1774924229, 1774924229);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (543, 541, 'C', '人设详情', '', 0, 'ai_application.device.person/detail', 'detail', 'ai_application/device/person/detail', '/ai_application/device/person/lists', '', 0, 0, 0, 1774924327, 1774924374);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (544, 542, 'A', '删除', '', 0, 'ai_application.device.person.lists/delete', '', '', '', '', 0, 1, 0, 1774924494, 1774924508);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (545, 543, 'A', '删除', '', 0, 'ai_application.device.person.detail/delete', '', '', '', '', 0, 1, 0, 1774924527, 1774924527);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (546, 543, 'A', '编辑', '', 0, 'ai_application.device.person.detail/edit', '', '', '', '', 0, 1, 0, 1774924631, 1774924631);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (547, 195, 'M', '爆款仿写', '', 0, '', 'hot_write', '', '', '', 0, 1, 0, 1774943558, 1774943558);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (548, 547, 'C', '创作记录', '', 0, 'ai_application.hot_write/record', 'record', 'ai_application/hot_write/record/index', '', '', 0, 1, 0, 1774943581, 1774943581);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (549, 548, 'A', '删除', '', 0, 'ai_application.hot_write.record/delete', '', '', '', '', 0, 1, 0, 1774943605, 1774943605);

ALTER TABLE `la_sv_publish_setting_detail`
    modify `task_type` tinyint default 1 null comment '任务类型1原发布模式2闪剪发布3矩阵发布4sora6爆款仿写99自动';

ALTER TABLE `la_sv_publish_setting_account`
    modify `task_type` tinyint default 1 null comment '任务类型1原发布模式2闪剪发布3矩阵发布4sora6爆款仿写99自动';

ALTER TABLE `la_sv_publish_setting`
    modify `task_type` tinyint default 1 null comment '任务类型1原发布模式2闪剪发布3矩阵发布4sora6爆款仿写99自动';

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('recharge', 'is_and_open', '1', 1749805431, 1751353142);
DELETE FROM `la_shanjian_clip_template` WHERE  `id` = "67dbbe408e231d0030bd072b";


UPDATE `la_dev_crontab` SET `expression` =  '*/3 * * * *' WHERE `command` = 'shanjian_video_task';
