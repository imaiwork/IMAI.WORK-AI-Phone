-- 装修管理 v2.11.0：底部导航默认数据（重置为 AI 默认 4 项，清除旧的商城默认数据）

DELETE FROM `la_decorate_tabbar`;
INSERT INTO `la_decorate_tabbar` VALUES (1, 'AI手机', 'static/images/mp/tabbs/phone_s.png', 'static/images/mp/tabbs/phone.png', '{\"path\":\"/pages/index/index\",\"name\":\"AI手机\",\"type\":\"page\"}', 1, 1782691200, 1782691200), (2, 'AI助手', 'static/images/mp/tabbs/chat_s.png', 'static/images/mp/tabbs/chat.png', '{\"path\":\"/packages/pages/chat/chat\",\"name\":\"AI助手\",\"type\":\"page\"}', 1, 1782691200, 1782691200), (3, 'AI创作', 'static/images/mp/tabbs/creative_s.png', 'static/images/mp/tabbs/creative.png', '{\"path\":\"/ai_modules/digital_human/pages/index/index\",\"name\":\"AI创作\",\"type\":\"page\"}', 1, 1782691200, 1782691200), (4, '我的', 'static/images/mp/tabbs/me_s.png', 'static/images/mp/tabbs/me.png', '{\"path\":\"/packages/pages/user/user\",\"name\":\"我的\",\"type\":\"page\"}', 1, 1782691200, 1782691200);

-- 自动加群触发模式：AI意图识别 / 自定义关键词
ALTER TABLE `la_ai_persona_wechat_interaction_config`
ADD COLUMN `group_trigger_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '加群触发模式:1=AI意图识别 2=自定义关键词' AFTER `is_share_chats`,
ADD COLUMN `group_trigger_keywords` json NULL COMMENT '自定义加群触发关键词(JSON数组)' AFTER `group_trigger_mode`;

ALTER TABLE `la_sv_wechat_strategy`
ADD COLUMN `group_trigger_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '加群触发模式:1=AI意图识别 2=自定义关键词' AFTER `group_name_template`,
ADD COLUMN `group_trigger_keywords` json NULL COMMENT '自定义加群触发关键词(JSON数组)' AFTER `group_trigger_mode`;

ALTER TABLE `la_sv_device_precise_clues_record`
ADD COLUMN `platform` tinyint(4) NOT NULL DEFAULT 4 COMMENT '平台类型1视频号3小红书4抖音5快手';

-- 小红书图文/视频发布类型配置
ALTER TABLE `la_sv_publish_setting_detail`
MODIFY COLUMN `material_url` text NULL COMMENT '视频/图片URL，图文多图用英文逗号分隔';

-- 热点追踪配置：自动找爆款 / 跟踪账号发布
ALTER TABLE `la_ai_persona`
ADD COLUMN `tracking_mode` tinyint(1) NOT NULL DEFAULT 1 COMMENT '热点追踪模式:1自动找爆款2跟踪账号发布',
ADD COLUMN `duration` tinyint(1) NOT NULL DEFAULT 1 COMMENT '爆款仿写视频时长筛选:0不限1一分钟内2一到五分钟3五分钟以上；默认1分钟以下',
ADD COLUMN `publish_day` tinyint(1) NOT NULL DEFAULT 0 COMMENT '爆款仿写视频发布时间筛选:0不限1一天内2一周内3半年内',
ADD COLUMN `tracking_account_config` json NULL COMMENT '跟踪账号配置，平台维度JSON' ;

ALTER TABLE `la_sv_device_viral`
ADD COLUMN `publish_platform` tinyint(4) NOT NULL DEFAULT 4 COMMENT '仿写发布平台:1视频号3小红书4抖音5快手' AFTER `generation_types`,
ADD COLUMN `publish_media_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '发布内容类型:1视频2图文' AFTER `publish_platform`;

ALTER TABLE `la_sv_device_viral_account`
ADD COLUMN `publish_platform` tinyint(4) NOT NULL DEFAULT 0 COMMENT '仿写发布平台:0跟随账号类型1视频号3小红书4抖音5快手' AFTER `account_type`,
ADD COLUMN `publish_media_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '发布内容类型:0跟随主任务1视频2图文' AFTER `publish_platform`,
ADD COLUMN `duration` int(11) NOT NULL DEFAULT 0 COMMENT '视频时长' AFTER `publish_media_type`,
ADD COLUMN `publish_day` int(11) NOT NULL DEFAULT 0 COMMENT '视频发布时间';

ALTER TABLE `la_sv_device_viral_record`
ADD COLUMN `persona_id` int(11) NOT NULL DEFAULT 0 COMMENT '人设id' AFTER `nickname`,
ADD COLUMN `publish_platform` tinyint(4) NOT NULL DEFAULT 4 COMMENT '仿写发布平台' AFTER `generation_types`,
ADD COLUMN `publish_media_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '发布内容类型:1视频2图文' AFTER `publish_platform`,
ADD COLUMN `original_images` json NULL COMMENT '爆款图文原图列表' AFTER `image`,
ADD COLUMN `rewritten_images` json NULL COMMENT '图生图改写后图片列表' AFTER `original_images`,
ADD COLUMN `image_rewrite_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '图生图状态:0无需1待提交2处理中3成功4失败' AFTER `rewritten_images`,
ADD COLUMN `image_rewrite_task_id` varchar(255) NOT NULL DEFAULT '' COMMENT '图生图任务ID' AFTER `image_rewrite_status`,
ADD COLUMN `image_rewrite_started_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '当前一轮图生图开始时间' AFTER `image_rewrite_task_id`,
ADD COLUMN `image_rewrite_retry_count` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '图生图失联重试次数' AFTER `image_rewrite_started_at`,
ADD COLUMN `publish_detail_id` int(11) NOT NULL DEFAULT 0 COMMENT '生成的待发布明细ID' AFTER `image_rewrite_retry_count`,
ADD COLUMN `keyword_consumed_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '爆款关键词成功消费时间' AFTER `publish_detail_id`,
ADD COLUMN `tikhub_raw` json NULL COMMENT 'TikHub图文笔记原始提取结果' AFTER `keyword_consumed_at`,
ADD COLUMN `image_rewrite_results` json NULL COMMENT '逐图改写结果明细' AFTER `tikhub_raw`,
ADD COLUMN `image_rewrite_success_count` int(11) NOT NULL DEFAULT 0 COMMENT '图片改写成功张数' AFTER `image_rewrite_results`,
ADD COLUMN `image_rewrite_fail_count` int(11) NOT NULL DEFAULT 0 COMMENT '图片改写失败张数' AFTER `image_rewrite_success_count`,
ADD COLUMN `image_rewrite_charged_count` int(11) NOT NULL DEFAULT 0 COMMENT '图片改写已扣费张数' AFTER `image_rewrite_fail_count`,
ADD COLUMN `publish_create_error` varchar(500) NOT NULL DEFAULT '' COMMENT '图文待发布任务生成失败原因' AFTER `publish_detail_id`,
ADD COLUMN `title_normalized` varchar(512) NOT NULL DEFAULT '' COMMENT '分享文清洗后的纯中文标题' AFTER `content`,
MODIFY COLUMN `copywriting_type` tinyint(4) NULL DEFAULT 0 COMMENT '0待确定1爆款仿写2无文案3严重偏离4降级处理5，6兜底' AFTER `copywriting`,
MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 0 COMMENT '状态0开始1无文案视频2文案不符合3直接由coze纯ai生成4符合条件5异常6兜底' AFTER `day`,
ADD COLUMN `video_duration` int(11) NOT NULL DEFAULT 0 COMMENT '原视频时长(秒)，图文默认0' AFTER `publish_media_type`,
ADD KEY `idx_viral_persona_id` (`persona_id`),
ADD KEY `idx_viral_media_status` (`publish_media_type`, `image_rewrite_status`, `publish_detail_id`),
ADD KEY `idx_viral_rewrite_stale` (`image_rewrite_status`, `image_rewrite_started_at`);

ALTER TABLE `la_ai_persona_synthesis_copywriting`
ADD COLUMN `publish_media_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '发布内容类型:1视频2图文' AFTER `sv_device_viral_record_id`;

ALTER TABLE `la_ai_persona_synthesis_config`
ADD COLUMN `news_mixcut_duration` int(11) NOT NULL DEFAULT 10 COMMENT '新闻体视频时长(秒，5-300)';

-- 数字人口播无包装(shanjian_type=5) + AI智剪包装 任务关联与计费
ALTER TABLE `la_shanjian_video_task`
ADD COLUMN `origin_task_id` int(11) NOT NULL DEFAULT 0 COMMENT '派生包装任务回指的源任务id(type=5)',
ADD COLUMN `packaging_task_id` int(11) NOT NULL DEFAULT 0 COMMENT 'type=5回写的派生包装任务id(type=2)',
ADD COLUMN `is_final` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否最终可用视频:1是0否(type=5开启智剪时置0)',
ADD COLUMN `queue_status` varchar(16) NOT NULL DEFAULT '' COMMENT '中台队列状态:waiting/submitted/failed',
ADD COLUMN `queue_position` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '中台队列位置',
ADD COLUMN `queue_updated_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '队列状态更新时间',
ADD COLUMN `queue_refund_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '队列失败退款:0未处理1已处理',
ADD INDEX `idx_origin_task_id` (`origin_task_id`),
ADD INDEX `idx_packaging_task_id` (`packaging_task_id`),
ADD INDEX `idx_is_final` (`is_final`),
ADD INDEX `idx_queue_status` (`queue_status`, `status`);

ALTER TABLE `la_video_imitation_task`
ADD COLUMN `queue_status` varchar(16) NOT NULL DEFAULT '' COMMENT '中台队列状态:waiting/submitted/failed',
ADD COLUMN `queue_position` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '中台队列位置',
ADD COLUMN `queue_updated_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '队列状态更新时间',
ADD COLUMN `queue_refund_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '队列失败退款:0未处理1已处理',
ADD INDEX `idx_queue_status` (`queue_status`, `status`);

-- 兼容: 禅境(蝉境)音色不再扣费, 禅境视频合成扣费保留
UPDATE `la_model_config` SET `score` = 0 WHERE `scene` = 'human_voice_chanjing';

-- 矩阵数字人视频合成已下线: 移除旧矩阵合成/音频查询定时任务
DELETE FROM `la_dev_crontab` WHERE `command` IN ('sv_video_cron', 'query_sv_audio_cron', 'sv_video_task', 'query_sv_audio_task');

DELETE FROM `la_dev_crontab` WHERE `command` = 'shanjian_queue_status';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
VALUES ('闪剪队列状态同步', 1, 0, '每分钟批量同步中台闪剪单队列状态', 'shanjian_queue_status', '', 1, '* * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `la_dev_crontab` WHERE `command` = 'viral_image_rewrite_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('爆款图文图片改写', 1, 0, '', 'viral_image_rewrite_cron', '', 1, '*/5 * * * *', NULL, 1782467101, '0', '0', 1782467101, 1782467101, NULL);

-- AutoGLM Phone 后端任务表
CREATE TABLE IF NOT EXISTS `la_phone_agent_action` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '任务ID',
  `turn_id` int(11) NOT NULL DEFAULT '0' COMMENT '轮次ID',
  `action_no` int(11) NOT NULL DEFAULT '1' COMMENT '动作序号',
  `action_type` varchar(100) NOT NULL DEFAULT '' COMMENT '动作类型',
  `action_payload` json DEFAULT NULL COMMENT '动作数据',
  `ws_payload` json DEFAULT NULL COMMENT 'WebSocket消息数据',
  `status` varchar(32) NOT NULL DEFAULT 'pending' COMMENT '状态',
  `result` json DEFAULT NULL COMMENT '手机端上报结果',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_task_turn` (`task_id`,`turn_id`),
  KEY `idx_task_action` (`task_id`,`action_no`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='AutoGLM Phone动作';

CREATE TABLE IF NOT EXISTS `la_phone_agent_conversation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会话ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备编码',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '会话标题',
  `last_message` varchar(1000) NOT NULL DEFAULT '' COMMENT '最后消息',
  `last_task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '最后任务ID',
  `task_count` int(11) NOT NULL DEFAULT '0' COMMENT '任务数量',
  `last_task_status` varchar(32) NOT NULL DEFAULT '' COMMENT '最后任务状态',
  `context_summary` text COMMENT '会话上下文摘要',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_conversation_id` (`conversation_id`) USING BTREE,
  KEY `idx_user_update` (`user_id`,`update_time`,`id`) USING BTREE,
  KEY `idx_device_update` (`device_code`,`update_time`) USING BTREE,
  KEY `idx_user_status` (`user_id`,`last_task_status`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='AutoGLM Phone会话';

CREATE TABLE IF NOT EXISTS `la_phone_agent_event` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '任务ID',
  `device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备编码',
  `event_type` varchar(64) NOT NULL DEFAULT '' COMMENT '事件类型',
  `event_data` json DEFAULT NULL COMMENT '事件数据',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_task_id` (`task_id`,`id`),
  KEY `idx_device_code` (`device_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='AutoGLM Phone事件';

CREATE TABLE IF NOT EXISTS `la_phone_agent_observation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '任务ID',
  `turn_id` int(11) NOT NULL DEFAULT '0' COMMENT '轮次ID',
  `screenshot` varchar(500) NOT NULL DEFAULT '' COMMENT '截图URL',
  `ocr_text` mediumtext COMMENT 'OCR文本',
  `accessibility_tree` json DEFAULT NULL COMMENT '无障碍节点树',
  `current_app` varchar(100) NOT NULL DEFAULT '' COMMENT '当前应用',
  `raw_data` json DEFAULT NULL COMMENT '原始数据',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_task_turn` (`task_id`,`turn_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='AutoGLM Phone观察记录';

CREATE TABLE IF NOT EXISTS `la_phone_agent_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '任务ID',
  `conversation_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会话ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备编码',
  `message` text COMMENT '用户消息',
  `execution_message` text COMMENT '规划后交给autoglm-phone的执行文案',
  `plan_json` text COMMENT '结构化任务规划JSON',
  `plan_display` text COMMENT '任务规划展示文案',
  `analyze_model` varchar(100) NOT NULL DEFAULT '' COMMENT '规划模型',
  `plan_status` varchar(32) NOT NULL DEFAULT '' COMMENT '规划状态:success/failed/skipped',
  `model` varchar(100) NOT NULL DEFAULT 'autoglm-phone' COMMENT '模型',
  `status` varchar(32) NOT NULL DEFAULT 'created' COMMENT '状态',
  `current_turn` int(11) NOT NULL DEFAULT '1' COMMENT '当前轮次',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
  `started_at` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `finished_at` int(11) NOT NULL DEFAULT '0' COMMENT '完成时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_task_id` (`task_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_device_status` (`device_code`,`status`),
  KEY `idx_conversation_id` (`conversation_id`) USING BTREE,
  KEY `idx_user_conversation` (`user_id`,`conversation_id`) USING BTREE,
  KEY `idx_user_update` (`user_id`,`update_time`,`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='AutoGLM Phone任务';

CREATE TABLE IF NOT EXISTS `la_phone_agent_turn` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '任务ID',
  `turn_no` int(11) NOT NULL DEFAULT '1' COMMENT '轮次编号',
  `request_messages` json DEFAULT NULL COMMENT '模型请求消息',
  `user_text` text COMMENT '本轮实际发送的user文本(含hint)',
  `assistant_content` text COMMENT '本轮assistant上下文文本',
  `model_response` json DEFAULT NULL COMMENT '模型响应',
  `parsed_action` json DEFAULT NULL COMMENT '解析后的动作',
  `usage` json DEFAULT NULL COMMENT '模型用量',
  `charged_amount` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '已扣费算力',
  `charged_time` int(11) NOT NULL DEFAULT '0' COMMENT '扣费时间',
  `charge_error` varchar(500) NOT NULL DEFAULT '' COMMENT '扣费错误',
  `status` varchar(32) NOT NULL DEFAULT 'created' COMMENT '状态',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_task_turn` (`task_id`,`turn_no`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='AutoGLM Phone模型轮次';

-- 地图获客对话、消息与线索卡片
DELETE FROM `la_model_config` WHERE `scene` IN ('map_chat_clues', 'images_explosion_rewrite');
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
VALUES ('map_chat_clues', 10320, '算力/条', '地图获客', 5.00, '一条5算力', 1, NULL, NULL);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
VALUES ('images_explosion_rewrite', 10321, '算力/次', '图文爆款仿写', 10.00, '一次10算力', 1, NULL, NULL);

CREATE TABLE IF NOT EXISTS `la_map_lead_conversation` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`conversation_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会话ID',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
`title` varchar(100) NOT NULL DEFAULT '' COMMENT '会话标题',
`last_content` varchar(1000) NOT NULL DEFAULT '' COMMENT '最后一条内容',
`lead_count` int(11) NOT NULL DEFAULT '0' COMMENT '累计线索数',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0处理中1成功2失败',
`fail_reason` varchar(500) NOT NULL DEFAULT '' COMMENT '失败原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `uk_conversation_id` (`conversation_id`) USING BTREE,
KEY `idx_user_update` (`user_id`,`update_time`) USING BTREE,
KEY `idx_user_status` (`user_id`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='地图获客会话';

CREATE TABLE IF NOT EXISTS `la_map_lead_message` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`conversation_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会话ID',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
`role` varchar(20) NOT NULL DEFAULT '' COMMENT '消息角色:user/assistant',
`content_type` varchar(40) NOT NULL DEFAULT '' COMMENT '内容类型:text/map_lead_cards/error',
`content` text NULL COMMENT '消息内容',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0处理中1成功2失败',
`extra` json NULL COMMENT '扩展数据',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_conversation_id` (`conversation_id`,`id`) USING BTREE,
KEY `idx_user_conversation` (`user_id`,`conversation_id`) USING BTREE,
KEY `idx_role` (`role`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='地图获客消息';

CREATE TABLE IF NOT EXISTS `la_map_lead_record` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`conversation_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会话ID',
`message_id` int(11) NOT NULL DEFAULT '0' COMMENT '消息ID',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
`poi_key` varchar(128) NOT NULL DEFAULT '' COMMENT '高德POI ID',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '商家名称',
`addr` varchar(500) NOT NULL DEFAULT '' COMMENT '商家地址',
`phone` varchar(255) NOT NULL DEFAULT '' COMMENT '联系电话',
`tag` varchar(255) NOT NULL DEFAULT '' COMMENT '商家分类',
`rating` varchar(20) NOT NULL DEFAULT '' COMMENT '评分',
`location` varchar(64) NOT NULL DEFAULT '' COMMENT '经纬度',
`lng` decimal(11,6) NOT NULL DEFAULT '0.000000' COMMENT '经度',
`lat` decimal(11,6) NOT NULL DEFAULT '0.000000' COMMENT '纬度',
`raw_data` json NULL COMMENT '原始卡片数据',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_message_id` (`message_id`) USING BTREE,
KEY `idx_user_conversation` (`user_id`,`conversation_id`) USING BTREE,
KEY `idx_phone` (`phone`(64)) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='地图获客线索卡片';

-- 用户注册方式默认配置
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'user', 'register', '{"register_mode":1,"default_invite_source":""}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (
    SELECT 1 FROM `la_config` WHERE `type` = 'user' AND `name` = 'register'
);


CREATE TABLE IF NOT EXISTS `la_ai_persona_copywriting_library` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
`persona_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联ai_persona主键ID',
`library_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '文案库类型:1视频驱动文案,2发布文案',
`driver_type` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '视频驱动文案类型:0无,1新闻体,2口播文案,3素材混剪口播',
`title` text COMMENT '标题;新闻体为换行分隔的多标题',
`content` mediumtext COMMENT '内容/口播内容',
`topic` varchar(1000) NOT NULL DEFAULT '' COMMENT '话题',
`source` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '来源:1手动新增,2文件导入',
`sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
`status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态:0禁用,1启用',
`use_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '使用次数',
`last_used_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '最后使用时间',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI人设文案库表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_copywriting_library_use_log` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`library_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '文案库ID',
`user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
`persona_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联ai_persona主键ID',
`scene` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '使用场景:1视频合成,2发布文案',
`device_code` varchar(255) NOT NULL DEFAULT '' COMMENT '设备号',
`related_video_task_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联闪剪视频任务ID',
`related_publish_detail_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联发布明细ID',
`task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '任务ID',
`platform` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT '发布平台',
`shanjian_type` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '闪剪视频类型',
`title` text COMMENT '使用时标题快照',
`content` mediumtext COMMENT '使用时内容快照',
`topic` varchar(1000) NOT NULL DEFAULT '' COMMENT '使用时话题快照',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI人设文案库使用日志表';

ALTER TABLE `la_ai_persona_synthesis_config`
ADD COLUMN `library_use_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '文案库使用方式:1随机使用,2顺序使用',
ADD COLUMN `library_reuse_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '文案库重复规则:1每个文案只用一次,2可重复使用',
MODIFY COLUMN `copywriting_source` tinyint(4) NOT NULL DEFAULT '2' COMMENT '文案来源:1仿写,2AI生成,3无需,4文案库';

ALTER TABLE `la_shanjian_video_task`
MODIFY COLUMN `copywriting_source` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '文案类型:0旧数据,1爆款仿写,2纯AI,3无文案,4文案库';

DELETE FROM `la_system_menu` WHERE `id` IN (244, 245,247,248,249, 607, 608, 609, 610);
INSERT INTO `la_system_menu` VALUES (607, 243, 'C', '底部菜单', '', 0, '', 'bottom_nav', 'decoration/bottom_nav/index', '', '', 0, 1, 0, 1782289409, 1782289409);
INSERT INTO `la_system_menu` VALUES (608, 607, 'A', '保存', '', 0, 'decoration.tabbar/save', '', '', '', '', 0, 1, 0, 1782289435, 1782289435);
INSERT INTO `la_system_menu` VALUES (609, 117, 'C', '会员列表', '', 0, 'user.user_member/lists', 'user_member', 'consumer/user_member/lists', '', '', 0, 1, 0, 1783663976, 1783663985);
INSERT INTO `la_system_menu` VALUES (610, 609, 'A', '编辑', '', 0, 'user.user_member/edit', '', '', '', '', 0, 1, 0, 1783664001, 1783664001);

-- 扩展 user_level（合并原 member_level 订阅配额字段，不再新建 member_level 表）
ALTER TABLE `la_user_level`
    ADD COLUMN `grant_tokens` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '周期赠送算力' AFTER `sort`,
    ADD COLUMN `grant_cycle` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=不送 1=每日 2=每月 3=每年' AFTER `grant_tokens`,
    ADD COLUMN `max_robots` int(11) NOT NULL DEFAULT '0' COMMENT '智能体上限 -1=禁止 0=不限' AFTER `grant_cycle`,
    ADD COLUMN `max_knowledges` int(11) NOT NULL DEFAULT '0' COMMENT '知识库上限' AFTER `max_robots`,
    ADD COLUMN `max_personas` int(11) NOT NULL DEFAULT '0' COMMENT '人设上限' AFTER `max_knowledges`,
    ADD COLUMN `max_mobiles` int(11) NOT NULL DEFAULT '0' COMMENT '手机端上限' AFTER `max_personas`,
    ADD COLUMN `max_digital_humans` int(11) NOT NULL DEFAULT '0' COMMENT '数字人上限' AFTER `max_mobiles`,
    ADD COLUMN `max_voices` int(11) NOT NULL DEFAULT '0' COMMENT '音色上限' AFTER `max_digital_humans`,
    ADD COLUMN `allowed_models` text COMMENT 'JSON数组模型id,如 [2,4];NULL或[]=不限制;接口返回时再查name' AFTER `max_voices`,
    ADD COLUMN `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0=禁用 1=启用' AFTER `allowed_models`,
    ADD COLUMN `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1=系统默认等级,不可删除' AFTER `status`;


CREATE TABLE IF NOT EXISTS `la_member_user` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `level_id` int(11) NOT NULL DEFAULT '0',
    `start_time` int(10) unsigned NOT NULL DEFAULT '0',
    `end_time` int(10) unsigned NOT NULL DEFAULT '0',
    `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=有效 2=已过期 3=已取消',
    `last_grant_time` int(10) unsigned NOT NULL DEFAULT '0',
    `source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=后台赠送 2=兑换码 3=购买',
    `source_remark` varchar(255) DEFAULT '',
    `create_time` int(10) unsigned DEFAULT NULL,
    `update_time` int(10) unsigned DEFAULT NULL,
    `delete_time` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user` (`user_id`),
    KEY `idx_end` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户会员';

ALTER TABLE `la_card_code`
    ADD COLUMN `member_level_id` int(11) DEFAULT NULL COMMENT '会员兑换码:目标等级 id' AFTER `delete_time`,
    ADD COLUMN `member_days` int(11) DEFAULT NULL COMMENT '会员兑换码:延长天数' AFTER `member_level_id`,
    ADD COLUMN `team_id` int(11) NOT NULL DEFAULT '0' COMMENT '团队制卡归属团队id(0=非团队卡)' AFTER `member_days`,
    ADD KEY `idx_team_id` (`team_id`);

DELETE FROM `la_user_level` WHERE `id` IN (1, 2, 3, 4);
INSERT INTO `la_user_level` (
    `id`, `level_name`, `sort`, `grant_tokens`, `grant_cycle`,
    `max_robots`, `max_knowledges`, `max_personas`, `max_mobiles`,
    `max_digital_humans`, `max_voices`, `allowed_models`,
    `status`, `is_default`, `create_time`, `update_time`
) VALUES
(1, '体验会员', 10, 50.00, 1, 5, 5, 1, 1, 1, 1, '[4]', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '标准会员', 20, 200.00, 2, 20, 20, 5, 5, 5, 5, '[2,4]', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '年度尊享', 30, 5000.00, 3, 100, 100, 30, 20, 30, 30, '[2,4,11,22]', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '普通用户', 0, 0.00, 0, 1, 1, 0, 0, 0, 0, NULL, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES
(
    'member',
    'free_quota',
    '{"name":"免费用户","is_member":false,"grant_tokens":0,"grant_cycle":0,"max_robots":1,"max_knowledges":1,"max_personas":0,"max_mobiles":0,"max_digital_humans":0,"max_voices":0,"allowed_models":[]}',
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

DELETE FROM `la_dev_crontab` WHERE `command` IN ('member_daily_grant', 'member_expire_check');

INSERT INTO `la_dev_crontab` (
     `name`, `type`, `system`, `remark`, `command`, `params`,
    `status`, `expression`, `create_time`, `update_time`
) VALUES
(
     '会员周期算力发放', 1, 0,
    '扫所有 active 会员,按等级周期发算力',
    'member_daily_grant', '', 1, '0 1 * * *',
    UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
),
(
    '会员到期检查', 1, 0,
    '到期后自动降级 + 软冻结超出配额的实体',
    'member_expire_check', '', 1, '*/10 * * * *',
    UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 视频剪辑配置：工作方式 / 成品库使用方式 / 随机规则
ALTER TABLE `la_ai_persona_synthesis_config`
ADD COLUMN `work_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '工作方式:1=AI合成视频,2=成品库直发' AFTER `persona_id`,
ADD COLUMN `product_use_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '成品库使用方式:1随机使用,2顺序使用' AFTER `work_mode`,
ADD COLUMN `product_reuse_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '成品库随机规则:1每个成品只用一次,2可重复使用' AFTER `product_use_mode`;

-- 兼容旧数据：人设已是直发模式时，同步合成配置工作方式
UPDATE `la_ai_persona_synthesis_config` c
INNER JOIN `la_ai_persona` p ON p.id = c.persona_id
SET c.work_mode = 2
WHERE p.publish_mode = 2 AND c.work_mode = 1 AND c.delete_time IS NULL;


ALTER TABLE `la_user` 
ADD COLUMN `source` varchar(255) CHARACTER SET utf8mb4 NULL DEFAULT '系统' COMMENT '注册来源' AFTER `last_bind_device_code`;

-- minimax闪剪任务：ASR对齐结果字段
ALTER TABLE `la_minimax_shanjian_task`
MODIFY COLUMN `results` text COMMENT '生成的音频结果JSON(含url/text/asr等)',
MODIFY COLUMN `status` tinyint(3) DEFAULT '0' COMMENT '0：待开始，1：生成中，2：成功，3：失败，4：ASR处理中',
ADD COLUMN `asr_result` mediumtext NULL COMMENT 'ASR对齐后的逐字时间戳JSON[{text,aligned_text,asr_text,words}]' AFTER `results`;

ALTER TABLE `la_sv_device_viral_record`
ADD COLUMN `use_time` int(11) NOT NULL DEFAULT 0 COMMENT '文案使用时间(时间戳,0未使用)' AFTER `is_interested`;

-- 历史数据回填:避免 use_time=0 把旧数据全当成未使用
-- 1) 关联文案已使用(use_state=2) → 回填使用时间
UPDATE `la_sv_device_viral_record` r
INNER JOIN `la_ai_persona_synthesis_copywriting` c ON c.sv_device_viral_record_id = r.id
SET r.use_time = IF(IFNULL(c.update_time, 0) > 0, c.update_time, IF(IFNULL(r.update_time, 0) > 0, r.update_time, IFNULL(r.create_time, UNIX_TIMESTAMP())))
WHERE IFNULL(r.use_time, 0) = 0
  AND c.use_state = 2
  AND c.delete_time IS NULL;

-- 2) 没有「未使用文案」的历史记录 → 标为已使用(用记录时间占位),仅保留仍有未用文案的 use_time=0
UPDATE `la_sv_device_viral_record` r
LEFT JOIN `la_ai_persona_synthesis_copywriting` c
  ON c.sv_device_viral_record_id = r.id
 AND c.use_state = 0
 AND c.delete_time IS NULL
SET r.use_time = IF(IFNULL(r.update_time, 0) > 0, r.update_time, IF(IFNULL(r.create_time, 0) > 0, r.create_time, UNIX_TIMESTAMP()))
WHERE IFNULL(r.use_time, 0) = 0
  AND c.id IS NULL;

CREATE TABLE IF NOT EXISTS `la_ai_persona_copywriting_library_platform_use` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`library_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '文案库ID',
`user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
`persona_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联ai_persona主键ID',
`platform` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT '发布平台:1视频号,3小红书,4抖音,5快手',
`use_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '该平台使用次数',
`last_used_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '该平台最后使用时间',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`),
UNIQUE KEY `uk_library_platform` (`library_id`,`platform`),
KEY `idx_persona_platform` (`persona_id`,`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI人设发布文案库平台使用次数表';


-- 人设自动创建智能体不计入会员等级 max_robots 配额
ALTER TABLE `la_kb_robot`
  ADD COLUMN `quota_exempt` tinyint(1) unsigned NOT NULL DEFAULT 0
  COMMENT '是否不计入会员智能体配额：1=人设自动创建等系统智能体' AFTER `is_enable`;

-- 回填：已挂在人设智能体配置上的机器人标记为不计配额
UPDATE `la_kb_robot` r
INNER JOIN (
  SELECT `comment_agent_id` AS `id` FROM `la_ai_persona_agent_config` WHERE `comment_agent_id` > 0
  UNION
  SELECT `dm_agent_id` FROM `la_ai_persona_agent_config` WHERE `dm_agent_id` > 0
  UNION
  SELECT `wechat_chat_agent_id` FROM `la_ai_persona_agent_config` WHERE `wechat_chat_agent_id` > 0
  UNION
  SELECT `moments_agent_id` FROM `la_ai_persona_agent_config` WHERE `moments_agent_id` > 0
) t ON r.`id` = t.`id`
SET r.`quota_exempt` = 1;

-- draw 会话/聊天记录：首页图片创作/视频生成的多轮会话与消息
CREATE TABLE IF NOT EXISTS `la_draw_conversation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `media_type` varchar(16) NOT NULL DEFAULT '' COMMENT 'image|video',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '会话标题(取首条提示词)',
  `last_prompt` varchar(500) NOT NULL DEFAULT '' COMMENT '最近一条提示词',
  `message_count` int(11) NOT NULL DEFAULT 0 COMMENT '消息数',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_media` (`user_id`, `media_type`, `id`) USING BTREE,
  KEY `idx_update` (`update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='draw会话表';

CREATE TABLE IF NOT EXISTS `la_draw_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `conversation_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'la_draw_conversation.id',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `media_type` varchar(16) NOT NULL DEFAULT '' COMMENT 'image|video',
  `role` varchar(16) NOT NULL DEFAULT '' COMMENT 'user|assistant',
  `content` varchar(2000) NOT NULL DEFAULT '' COMMENT '用户提示词/文本',
  `attachments` json DEFAULT NULL COMMENT '输入参考图等',
  `params` json DEFAULT NULL COMMENT '生成参数快照',
  `draw_task_id` int(11) NOT NULL DEFAULT 0 COMMENT '助手消息关联la_draw_task.id',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_conversation` (`conversation_id`, `id`) USING BTREE,
  KEY `idx_draw_task` (`draw_task_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='draw消息表';


-- draw 生图/生视频统一任务表（不兼容旧 hd/draw_video 数据）
-- 模型目录不落库，提交时写入 model 快照；产物多行存储；视频/图片均落盘；封面可异步补

CREATE TABLE IF NOT EXISTS `la_draw_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `task_no` varchar(64) NOT NULL DEFAULT '' COMMENT '自研任务号（对外）',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `media_type` varchar(16) NOT NULL DEFAULT '' COMMENT 'image|video',
  `model` varchar(128) NOT NULL DEFAULT '' COMMENT '模型标识',
  `model_name` varchar(128) NOT NULL DEFAULT '' COMMENT '模型展示名快照',
  `prompt` varchar(2000) NOT NULL DEFAULT '' COMMENT '主提示词',
  `params` json DEFAULT NULL COMMENT '完整请求参数',
  `mid_task_id` varchar(128) NOT NULL DEFAULT '' COMMENT '任务id',
  `request_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'request_id',
  `notify_url` varchar(500) NOT NULL DEFAULT '' COMMENT '回调地址',
  `mid_raw` json DEFAULT NULL COMMENT '最近一次响应/回调原文',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0pending 1submitted 2processing 3success 4failed 5cancelled',
  `progress` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '进度0-100',
  `error_code` varchar(64) NOT NULL DEFAULT '' COMMENT '失败码',
  `error_msg` varchar(1000) NOT NULL DEFAULT '' COMMENT '失败原因',
  `asset_count` int(11) NOT NULL DEFAULT 0 COMMENT '产物条数（含cover）',
  `billing_scene` varchar(64) NOT NULL DEFAULT '' COMMENT '计费scene',
  `billing_code` int(11) NOT NULL DEFAULT 0 COMMENT '计费code/change_type',
  `tokens_cost` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '预扣算力',
  `tokens_log_id` int(11) NOT NULL DEFAULT 0 COMMENT '预扣流水id',
  `bill_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0none 1held 2consumed 3refunded',
  `bill_snapshot` json DEFAULT NULL COMMENT '算价快照',
  `submit_time` int(11) DEFAULT NULL COMMENT '提交时间',
  `finished_at` int(11) DEFAULT NULL COMMENT '终态时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_task_no` (`task_no`) USING BTREE,
  KEY `idx_user_media_status` (`user_id`, `media_type`, `status`, `id`) USING BTREE,
  KEY `idx_mid_task_id` (`mid_task_id`) USING BTREE,
  KEY `idx_status_update` (`status`, `update_time`) USING BTREE,
  KEY `idx_tokens_log_id` (`tokens_log_id`) USING BTREE,
  KEY `idx_bill_status` (`bill_status`, `status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='draw生成任务表';

CREATE TABLE IF NOT EXISTS `la_draw_asset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `task_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'la_draw_task.id',
  `asset_type` varchar(16) NOT NULL DEFAULT '' COMMENT 'image|video|cover',
  `source_url` varchar(2000) NOT NULL DEFAULT '' COMMENT '原始URL',
  `file_url` varchar(2000) NOT NULL DEFAULT '' COMMENT '落盘相对路径',
  `storage` varchar(16) NOT NULL DEFAULT 'local' COMMENT '落盘时storage.default快照',
  `width` int(11) NOT NULL DEFAULT 0 COMMENT '宽',
  `height` int(11) NOT NULL DEFAULT 0 COMMENT '高',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT '视频秒数',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '同类型排序',
  `extra` json DEFAULT NULL COMMENT '扩展元数据',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_task_type_sort` (`task_id`, `asset_type`, `sort`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='draw生成产物表';

-- la_hd_log：字符串模型名 + 关联新任务
ALTER TABLE `la_hd_log`
  ADD COLUMN `model` varchar(128) NOT NULL DEFAULT '' COMMENT '中台字符串模型名' AFTER `model_type`,
  ADD COLUMN `draw_task_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联la_draw_task.id' AFTER `model`;

-- la_draw_video：字符串模型名 + 关联新任务
ALTER TABLE `la_draw_video`
  ADD COLUMN `model_name` varchar(128) NOT NULL DEFAULT '' COMMENT '中台字符串模型名' AFTER `model`,
  ADD COLUMN `draw_task_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联la_draw_task.id' AFTER `model_name`;


-- 复用原有 sync_chat_models 定时任务：改名并改为执行 sync_models（对话+生图/生视频）
UPDATE `la_dev_crontab`
SET `name` = '同步中台模型(对话+生图/生视频)',
    `command` = 'sync_models'
WHERE `command` = 'sync_chat_models';

-- 清理废弃模型计费场景配置
DELETE FROM `la_model_config` WHERE `scene` IN (
  'common_chat',
  'scene_chat',
  'gemini_chat',
  'openai_chat',
  'text_to_image',
  'image_to_image',
  'goods_image',
  'model_image',
  'image_prompt',
  'txt_to_posterimg',
  'volc_txt_to_img',
  'volc_txt_to_posterimg',
  'volc_txt_to_posterimg_v2',
  'volc_text_to_video',
  'volc_image_to_video',
  'volc_img_to_img_v2',
  'volc_txt_to_img_v2',
  'doubao_txt_to_video',
  'doubao_img_to_video',
  'mind_map',
  'human_avatar',
  'human_voice',
  'human_audio',
  'human_video',
  'human_avatar_pro',
  'human_voice_pro',
  'human_audio_pro',
  'human_video_pro',
  'ai_wechat',
  'human_video_ym',
  'human_avatar_ym',
  'human_voice_ym',
  'human_audio_ym',
  'knowledge_create',
  'human_avatar_ymt',
  'human_voice_ymt',
  'human_audio_ymt',
  'human_video_ymt'
);

UPDATE `la_model_config` SET `score` = 50.00,  `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'automation_precise_clues';
UPDATE `la_model_config` SET `score` = 50.00,  `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'human_avatar_shanjian';

