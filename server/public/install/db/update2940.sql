CREATE TABLE IF NOT EXISTS `la_minimax_shanjian_task` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`shanjian_setting_id` int(11) DEFAULT NULL COMMENT '闪剪任务id',
`voice_id` varchar(100) DEFAULT NULL COMMENT 'minimax音色id',
`contents` text COMMENT '文案',
`results` text COMMENT '生成的音频urls',
`status` tinyint(3) DEFAULT '0' COMMENT '0：待开始，1：生成中，2：成功，3：失败',
`remark` varchar(500) DEFAULT NULL COMMENT '错误原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_shanjian_setting_id` (`shanjian_setting_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='minimax音频合成闪剪任务表';

CREATE TABLE IF NOT EXISTS `la_sv_device_viral` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `task_name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `auto_type` tinyint(4) DEFAULT '0' COMMENT '是否自动1自动0手动',
  `accounts` varchar(1000) DEFAULT NULL COMMENT '账号集合',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
  `persona_id` int(11) DEFAULT '0' COMMENT '人设id',
  `generation_types` json DEFAULT NULL COMMENT '生成类型1数字人口播2新闻体混剪3纯素材混剪',
  `keywords` json DEFAULT NULL COMMENT '关键词',
  `custom_date` varchar(1000) DEFAULT NULL COMMENT '自定义日期',
  `time_config` varchar(1000) DEFAULT NULL COMMENT '每日任务执行区间集合',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备爆款复刻任务';

CREATE TABLE IF NOT EXISTS `la_sv_device_viral_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `viral_id` int(11) DEFAULT '0' COMMENT '复刻任务id',
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `account` varchar(255) DEFAULT NULL COMMENT '账号id',
  `account_type` tinyint(4) DEFAULT '0' COMMENT '账号类型0未知1微信视频号3小红书4抖音5快手',
  `nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `auto_type` tinyint(4) DEFAULT '0' COMMENT '是否自动1自动0手动',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `start_time` int(11) DEFAULT NULL COMMENT '执行开始时间',
  `end_time` int(11) DEFAULT NULL COMMENT '执行结束时间',
  `keywords` json DEFAULT NULL COMMENT '关键词',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败4中断',
  `persona_id` int(11) DEFAULT '0' COMMENT '人设id',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `viral_id` (`viral_id`) USING BTREE,
  KEY `account` (`account`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备爆款复刻账号表';


CREATE TABLE IF NOT EXISTS `la_sv_device_viral_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `viral_id` int(11) DEFAULT '0' COMMENT '复刻任务任务id',
  `viral_account_id` int(11) DEFAULT '0' COMMENT '复刻账号id',
  `auto_type` tinyint(4) DEFAULT '0' COMMENT '0手动1自动',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `account` varchar(255) DEFAULT NULL COMMENT '执行账号',
  `nickname` varchar(255) DEFAULT NULL COMMENT '好友名称',
  `keyword` varchar(255) DEFAULT NULL COMMENT '关键词',
  `content` varchar(500) DEFAULT NULL COMMENT '爆款分享信息',
  `generation_types` json DEFAULT NULL COMMENT '生成类型1数字人口播2新闻体混剪3纯素材混剪',
  `original_text` text COMMENT '视频转文字',
  `copywriting` text COMMENT '仿写文案',
  `copywriting_type` tinyint(4) DEFAULT '0' COMMENT '0待确定1爆款仿写2无文案3严重偏离4降级处理',
  `day` date DEFAULT NULL COMMENT '生成日期',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0开始1无文案视频2文案不符合3直接由coze纯ai生成4符合条件5异常',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `retry` tinyint(4) DEFAULT '0' COMMENT '重试次数',
  `hash` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL COMMENT '截图',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `viral_id` (`viral_id`) USING BTREE,
  KEY `viral_account_id` (`viral_account_id`) USING BTREE,
  KEY `device_code` (`device_code`) USING BTREE,
  KEY `hash` (`hash`) USING BTREE,
  KEY `account` (`account`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备爆款复刻记录表';


CREATE TABLE IF NOT EXISTS `la_ai_persona_synthesis_config` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
`generation_types` json NOT NULL COMMENT '生成类型(多选): [1,2,4]',
`persona_id` int(11) unsigned NOT NULL COMMENT '关联ai_persona主键ID',
`visual_material_source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '画面素材: 1-纯AI, 2-AI+素材库, 3-纯素材库',
`copywriting_source` tinyint(4) NOT NULL DEFAULT '2' COMMENT '文案来源: 1-仿写, 2-AI生成, 3-无需',
`video_cover_source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '视频封面: 1-默认, 2-AI自动, 3-手动',
`pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '视频封面',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
PRIMARY KEY (`id`),
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI合成规则配置表';

CREATE TABLE IF NOT EXISTS `la_ai_persona_synthesis_copywriting` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
`device_code` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '设备号',
`persona_id` int(11) unsigned NOT NULL COMMENT '关联ai_persona主键ID',
`sv_device_viral_record_id` int(11) unsigned NOT NULL COMMENT '设备爆款复刻记录表主键ID',
`copywriting` text COLLATE utf8mb4_unicode_ci COMMENT '文案',
`status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '关键词获取结果: 1-失败, 2成功',
`use_state` tinyint(4) NOT NULL DEFAULT '0' COMMENT '使用状态: 0-未使用, 1使用中 2已使用',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
PRIMARY KEY (`id`),
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='视频合成文案表';

ALTER TABLE `la_shanjian_video_task`
ADD COLUMN `copywriting_source` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文案类型:0旧数据1爆款仿写,2纯ai,3无文案',
ADD COLUMN `visual_material_source` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '素材类型:0旧数据1纯ai,2ai+素材库,3素材库',
ADD COLUMN `video_cover_source` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '封面类型:0旧数据1默认,2ai自动,3手动',
ADD COLUMN `cover_result_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '生成封面图id',
ADD COLUMN `thumb_status` tinyint(4) UNSIGNED NULL DEFAULT 4 COMMENT '封面图状态1处理中2成功3失败4原图',
ADD COLUMN `is_downgrade` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ai降级:0否1是';


INSERT INTO `la_sv_device_execution_schedule` (`persona_type`, `start_time`, `end_time`, `task_category`, `scene`, `platform`, `quantity_duration`, `cost_rule`, `remark`) VALUES
(1, '00:00', '06:00', '爆款仿写', 16, '[4]', '根据有多少个发布时段就获取多少个爆款文案', '视频解析，文案仿写都扣费', '每日凌晨开始执行'),
(2, '00:00', '06:00', '爆款仿写', 16, '[4]', '根据有多少个发布时段就获取多少个爆款文案', '视频解析，文案仿写都扣费', '每日凌晨开始执行'),
(3, '00:00', '06:00', '爆款仿写', 16, '[4]', '根据有多少个发布时段就获取多少个爆款文案', '视频解析，文案仿写都扣费', '每日凌晨开始执行');

ALTER TABLE `la_ai_persona_material` 
ADD COLUMN `grab_type` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '抓取类型0否1ai' ;

DELETE FROM `la_model_config` WHERE `scene` = 'grab_image' AND `code` = '10501';
DELETE FROM `la_model_config` WHERE `scene` = 'grab_video' AND `code` = '10502';
DELETE FROM `la_model_config` WHERE `scene` = 'get_hot_words' AND `code` = '10209';
DELETE FROM `la_model_config` WHERE `scene` = 'extract_keywords' AND `code` = '10210';
DELETE FROM `la_model_config` WHERE `scene` = 'shanjian_ai_cover' AND `code` = '5041';
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('grab_image', 10501, '算力/张', 'AI自动找素材图片扣费', 10, 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('grab_video', 10502, '算力/个', 'AI自动找素材视频扣费', 50,  1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('get_hot_words', 10209, '算力/次', '热点视频搜索词提取', 5,  1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('extract_keywords', 10210, '算力/次', 'AI素材关键词提取', 5,  1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ('shanjian_ai_cover', 5041, '算力/张', 'AI自动封面', 5,  1, 1740799252, 1740799252);



ALTER TABLE `la_ai_persona`
ADD COLUMN `workflow_template_id` int NULL DEFAULT 0 COMMENT '当前任务使用的工作流模板id' AFTER `wechat_publish_mode`;


DELETE FROM `la_dev_crontab`
WHERE `command` IN ('auto_device_video_synthesis', 'auto_imitation_video_synthesis', 'auto_video_synthesis');

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time` )
VALUES ( '自动合成视频任务', 1, 0, '', 'auto_video_synthesis', '', 1, '*/10 * * * *', NULL, 1766678409, '1.91', '1.91', 1766542031, 1766734271, NULL );

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time` )
VALUES ( 'minimax音频合成闪剪任务', 1, 0, '', 'minimax_shanjian_cron', '', 1, '* * * * * ', NULL, 1766678409, '0', '0', 1766542031, 1766734271, NULL );

ALTER TABLE `la_video_imitation_task` ADD COLUMN `visual_material_source` tinyint(1) NOT NULL DEFAULT 3 COMMENT '视觉素材来源：1=AI搜索，2=AI+本地，3=仅本地';

CREATE TABLE IF NOT EXISTS `la_marketing_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态1启用0未启用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `id` (`id`) USING BTREE,
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='营销模板分类表';
INSERT INTO `la_marketing_category` (`id`, `name`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (1, '专属', 6, 1, 1779985758, 1779985758, NULL);
INSERT INTO `la_marketing_category` (`id`, `name`, `sort`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (2, '自定义', 6, 1, 1779985758, 1779985758, NULL);
DELETE FROM `la_marketing_category` WHERE `id` in (1,2);


CREATE TABLE IF NOT EXISTS `la_marketing_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `type` tinyint(4) DEFAULT '0' COMMENT '模板类型：1专属2自定义3后台模板',
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `persona_id` int(11) DEFAULT '0' COMMENT '人设id',
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `category_id` int(10) unsigned NOT NULL COMMENT '关联分类ID',
  `operation_preference` tinyint(4) DEFAULT '0' COMMENT '运营偏好：1综合2获客3养号4运营',
  `description` varchar(500) DEFAULT NULL COMMENT '模板描述',
  `detail_content` varchar(600) DEFAULT NULL COMMENT '适用场景',
  `detail_task_types` varchar(600) DEFAULT NULL COMMENT '执行动作',
  `detail_users` varchar(600) DEFAULT NULL COMMENT '目标人群',
  `detail_images` json DEFAULT NULL COMMENT '详情页图片数组',
  `detail_videos` json DEFAULT NULL COMMENT '详情页视频数组',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=启用，0=停用',
  `is_system_generated` tinyint(4) DEFAULT '0' COMMENT '是否系统自动生成（专属模板不可更改）',
  `original_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '原始模板ID（仅对自定义模板有效）',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='营销模板表';


CREATE TABLE IF NOT EXISTS `la_marketing_template_schedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属用户ID（0表示系统级模板）',
  `persona_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '人设ID',
  `template_id` int(11) unsigned NOT NULL COMMENT '工作流模板ID',
  `start_time` char(5) NOT NULL COMMENT '起始时间，格式 HH:mm',
  `end_time` char(5) NOT NULL COMMENT '结束时间，格式 HH:mm',
  `task_category` varchar(64) DEFAULT '' COMMENT '执行任务',
  `scene` tinyint(4) DEFAULT '0' COMMENT '任务场景：1截流评论获客2截流私信获客3留痕获客/同城触达4视频号获客5视频发布6私信接管7朋友圈发布8朋友圈互动9自动加好友10自动养号11评论接管12同城曝光13同城截流14团购截流15评论点赞',
  `platform` json NOT NULL COMMENT '执行平台编码数组，如 [{"name":"抖音","order":1},{"name":"小红书","order":2},...] 对应 1=视频号2=微信3=小红书 4=抖音 5=快手',
  `remark` text COMMENT '执行动作备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `persona_id` (`persona_id`) USING BTREE,
  KEY `workflow_template_id` (`template_id`) USING BTREE,
  KEY `start_time` (`start_time`) USING BTREE,
  KEY `end_time` (`end_time`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='人设工作流执行计划表';


CREATE TABLE IF NOT EXISTS `la_ai_persona_workflow_schedule_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `persona_id` int(11) DEFAULT '0' COMMENT '人设id',
  `template_id` int(11) DEFAULT '0' COMMENT '工作流模板id',
  `schedule_id` int(11) DEFAULT '0' COMMENT '计划id',
  `scene` int(11) DEFAULT '0' COMMENT '任务场景',
  `status` tinyint(4) DEFAULT '0' COMMENT '计划状态1开0关',
  `start_time` char(5) DEFAULT NULL COMMENT '开始时间',
  `end_time` char(5) DEFAULT NULL COMMENT '结束时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `persona_id` (`persona_id`) USING BTREE,
  KEY `workflow_template_id` (`template_id`) USING BTREE,
  KEY `schedule_id` (`schedule_id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `start_time` (`start_time`) USING BTREE,
  KEY `end_time` (`end_time`) USING BTREE,
  KEY `scene` (`scene`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='用户工作流计划表';


INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (566, 368, 'M', '模版库管理', '', 0, '', 'task_template', '', '', '', 0, 1, 0, 1779431470, 1779431507);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (567, 566, 'C', '模版列表', '', 0, 'ai_application.device.task_template/lists', 'lists', 'ai_application/device/task_template/lists/index', '', '', 0, 1, 0, 1779431635, 1779431635);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (568, 567, 'A', '新增', '', 0, 'ai_application.device.task_template.lists/add', '', '', '', '', 0, 1, 0, 1779431659, 1779431659);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (569, 567, 'A', '编辑', '', 0, 'ai_application.device.task_template.lists/edit', '', '', '', '', 0, 1, 0, 1779431669, 1779431669);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (570, 567, 'A', '删除', '', 0, 'ai_application.device.task_template.lists/delete', '', '', '', '', 0, 1, 0, 1779431682, 1779431682);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (571, 567, 'A', '状态', '', 0, 'ai_application.device.task_template.lists/status', '', '', '', '', 0, 1, 0, 1779431695, 1779431695);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (572, 566, 'C', '分类列表', '', 0, 'ai_application.device.task_template/cate', 'cate', 'ai_application/device/task_template/cate/index', '', '', 0, 1, 0, 1779431723, 1779431723);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (573, 572, 'A', '新增', '', 0, 'ai_application.device.task_template.cate/add', '', '', '', '', 0, 1, 0, 1779431736, 1779431736);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (574, 572, 'A', '编辑', '', 0, 'ai_application.device.task_template.cate/edit', '', '', '', '', 0, 1, 0, 1779431748, 1779431748);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (575, 572, 'A', '删除', '', 0, 'ai_application.device.task_template.cate/delete', '', '', '', '', 0, 1, 0, 1779431758, 1779431758);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (576, 572, 'A', '状态', '', 0, 'ai_application.device.task_template.cate/status', '', '', '', '', 0, 1, 0, 1779431770, 1779431770);

INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (22, 1, 'claude', 'static/images/models/4.png', '克洛德4.6', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (22, 22, 1, 'claude', '克洛德4.6', 'claude-sonnet-4-6',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (23, 1, 'claude', 'static/images/models/4.png', '克洛德4.6-think', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (23, 23, 1, 'claude', '克洛德4.6-think', 'claude-sonnet-4-6-think',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (24, 1, 'openai', 'static/images/models/3.png', 'Ai-5.4', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (24, 24, 1, 'openai', 'Ai-5.4', 'gpt-5.4',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (25, 1, 'openai', 'static/images/models/3.png', 'Ai-5.4-mini', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (25, 25, 1, 'openai', 'Ai-5.4-mini', 'gpt-5.4-mini',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (26, 1, 'openai', 'static/images/models/3.png', 'Ai-5.0', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (26, 26, 1, 'openai', 'Ai-5.0', 'gpt-5',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (27, 1, 'openai', 'static/images/models/3.png', 'Ai-5.0-mini', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (27, 27, 1, 'openai', 'Ai-5.0-mini', 'gpt-5-mini',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (28, 1, 'google', 'static/images/models/2.png', '谷歌智元3.1 PRO', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (28, 28, 1, 'google', '谷歌智元3.1 PRO', 'gemini-3.1-pro-preview',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (29, 1, 'google', 'static/images/models/2.png', '谷歌智元4.0', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (29, 29, 1, 'google', '谷歌智元4.0', 'gemma-4-31b-it',0.0000, 0, 1, 1755929617);

UPDATE `la_config` SET `value` = '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"Ai-4.0","model_id":15,"model_sub_id":15,"status":"0","logo":"static/images/models/3.png"},{"id":"2","name":"Ai-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"13","name":"Ai-5.4","model_id":24,"model_sub_id":24,"status":"1","logo":"static/images/models/3.png"},{"id":"14","name":"Ai-5.4-mini","model_id":25,"model_sub_id":25,"status":"1","logo":"static/images/models/3.png"},{"id":"15","name":"Ai-5.0","model_id":26,"model_sub_id":26,"status":"1","logo":"static/images/models/3.png"},{"id":"16","name":"Ai-5.0-mini","model_id":27,"model_sub_id":27,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"Ai-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"Ai-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"17","name":"谷歌智元3.1 PRO","model_id":28,"model_sub_id":28,"status":"1","logo":"static/images/models/2.png"},{"id":"18","name":"谷歌智元4.0","model_id":29,"model_sub_id":29,"status":"1","logo":"static/images/models/2.png"},{"id":"3","name":"谷歌智元2.5 PRO","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"谷歌智元3.0","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"},{"id":"11","name":"克洛德4.6","model_id":22,"model_sub_id":22,"status":"1","logo":"static/images/models/4.png"},{"id":"12","name":"克洛德4.6-think","model_id":23,"model_sub_id":23,"status":"1","logo":"static/images/models/4.png"},{"id":"10","name":"克洛德4.5","model_id":18,"model_sub_id":18,"status":"1","logo":"static/images/models/4.png"}]}' WHERE `type` = 'chat' AND `name` = 'ai_model';

DELETE FROM `la_shanjian_clip_template` 
WHERE `id` IN (
    '6863a624cbf589003866ed09',
    '6876111d43c4a1003860cffe',
    '6882fc4f4894d300312b00ac',
    '68a43427d506f200380262dc',
    '68b56b77ccd8300033c97097',
    '68c2410f49c0ae002fc35d53',
    '68c243a75549a00037805dfd',
    '68c248ba1a8e920031f336a4',
    '6904552d68f703003047c54f',
    '68d8aa8957b27500381021d9'
);

ALTER TABLE `la_shanjian_video_setting` ADD COLUMN `request_json` json DEFAULT NULL COMMENT '请求体';

ALTER TABLE `la_sv_publish_setting_detail` 
MODIFY COLUMN `pic` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '封面' AFTER `material_tag`;


ALTER TABLE `la_ai_persona_material` 
ADD COLUMN `remote_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '远程URL';