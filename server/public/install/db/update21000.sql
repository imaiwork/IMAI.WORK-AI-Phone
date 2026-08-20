-- 设备授权码 v3.0.0

CREATE TABLE IF NOT EXISTS `la_device_auth_plan` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(64) NOT NULL DEFAULT '' COMMENT '套餐名称',
`type` tinyint unsigned NOT NULL COMMENT '授权类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
`duration_days` int unsigned NOT NULL DEFAULT '0' COMMENT '授权天数，0=永久',
`price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '现金售价(元)',
`tokens_price` int unsigned NOT NULL DEFAULT '0' COMMENT '算力售价(点)',
`is_recommend` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐: 0否 1是',
`sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序(大在前)',
`status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态: 0禁用 1启用',
`remark` varchar(255) DEFAULT NULL COMMENT '备注',
`create_time` int unsigned NOT NULL DEFAULT '0',
`update_time` int unsigned NOT NULL DEFAULT '0',
`delete_time` int unsigned DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_status_sort` (`status`, `sort`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备授权套餐表';

CREATE TABLE IF NOT EXISTS `la_device_auth_batch` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`sn` varchar(32) NOT NULL COMMENT '批次编号',
`type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '授权类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
`total_num` int unsigned NOT NULL DEFAULT '0' COMMENT '本批次授权码总数',
`used_num` int unsigned NOT NULL DEFAULT '0' COMMENT '已使用数量',
`source` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '来源: 1中台生成 2文件导入',
`admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
`import_file` varchar(255) DEFAULT NULL COMMENT '导入文件路径(source=2时使用)',
`rule_type` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '生成规则: 1随机字母 2随机数字',
`remark` varchar(255) DEFAULT NULL COMMENT '备注',
`create_time` int unsigned NOT NULL DEFAULT '0',
`update_time` int unsigned NOT NULL DEFAULT '0',
`delete_time` int unsigned DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `uk_sn` (`sn`) USING BTREE,
KEY `idx_type_source` (`type`, `source`) USING BTREE,
KEY `idx_create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备授权码批次表';

CREATE TABLE IF NOT EXISTS `la_device_auth_code` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`batch_id` int unsigned NOT NULL DEFAULT '0' COMMENT '批次ID',
`code` varchar(64) NOT NULL COMMENT '授权码串码',
`type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '授权类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
`status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0未使用 1已使用 2已作废',
`source` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '入库来源: 1中台生成 2文件导入',
`owner_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '归属用户ID，0=码池未分配',
`purchase_time` int unsigned NOT NULL DEFAULT '0' COMMENT '用户购买时间，0=未购买',
`order_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联购买订单ID',
`user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '使用用户ID(激活时)',
`device_id` int unsigned NOT NULL DEFAULT '0' COMMENT '使用设备ID',
`device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '使用设备号',
`use_time` int unsigned NOT NULL DEFAULT '0' COMMENT '使用时间(激活时间)',
`auth_start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '授权开始时间',
`auth_expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT '授权到期时间(0=永久或未激活)',
`admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '入库操作管理员ID',
`remark` varchar(255) DEFAULT NULL COMMENT '备注',
`create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '入库时间',
`update_time` int unsigned NOT NULL DEFAULT '0',
`delete_time` int unsigned DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `uk_code` (`code`) USING BTREE,
KEY `idx_batch_id` (`batch_id`) USING BTREE,
KEY `idx_type_status` (`type`, `status`) USING BTREE,
KEY `idx_owner_status` (`owner_user_id`, `status`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_device_id` (`device_id`) USING BTREE,
KEY `idx_device_code` (`device_code`) USING BTREE,
KEY `idx_order_id` (`order_id`) USING BTREE,
KEY `idx_create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备授权码表';

CREATE TABLE IF NOT EXISTS `la_device_auth_order` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`sn` varchar(64) NOT NULL COMMENT '订单编号',
`user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`biz_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '业务类型: 1购买授权码 2设备续费',
`plan_id` int unsigned NOT NULL DEFAULT '0' COMMENT '套餐ID',
`auth_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '授权类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
`quantity` int unsigned NOT NULL DEFAULT '1' COMMENT '购买数量',
`unit_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价(元)',
`unit_tokens` int unsigned NOT NULL DEFAULT '0' COMMENT '单价(算力)',
`order_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单金额(元)',
`tokens_amount` int unsigned NOT NULL DEFAULT '0' COMMENT '订单算力',
`pay_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '支付类型: 1在线支付 2算力支付',
`pay_way` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '在线支付方式: 2微信 3支付宝',
`pay_sn` varchar(255) NOT NULL DEFAULT '' COMMENT '支付单号',
`pay_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '支付状态: 0待支付 1已支付 2已取消 3已退款',
`pay_time` int unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
`transaction_id` varchar(128) DEFAULT NULL COMMENT '第三方交易流水号',
`device_id` int unsigned NOT NULL DEFAULT '0' COMMENT '续费设备ID',
`device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '续费设备号',
`refund_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '退款状态: 0未退款 1已退款',
`refund_transaction_id` varchar(255) DEFAULT NULL COMMENT '退款流水号',
`order_terminal` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '下单终端',
`account_log_id` int unsigned NOT NULL DEFAULT '0' COMMENT '算力流水ID',
`remark` varchar(255) DEFAULT NULL COMMENT '备注',
`create_time` int unsigned NOT NULL DEFAULT '0',
`update_time` int unsigned NOT NULL DEFAULT '0',
`delete_time` int unsigned DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `uk_sn` (`sn`) USING BTREE,
KEY `idx_user_pay_status` (`user_id`, `pay_status`) USING BTREE,
KEY `idx_pay_type_status` (`pay_type`, `pay_status`) USING BTREE,
KEY `idx_pay_time` (`pay_time`) USING BTREE,
KEY `idx_biz_type` (`biz_type`) USING BTREE,
KEY `idx_create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备授权码购买/续费订单表';

CREATE TABLE IF NOT EXISTS `la_sv_device_take_over_record` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`task_account_id` int(11) DEFAULT '0' COMMENT '任务账号id',
`auto_type` tinyint(4) DEFAULT '0' COMMENT '0手动1自动',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`account` varchar(255) DEFAULT NULL COMMENT '执行账号',
`nickname` varchar(255) DEFAULT NULL COMMENT '执行账号昵称',
`avatar` varchar(255) DEFAULT NULL COMMENT '执行账号头像',
`user_account` varchar(255) DEFAULT NULL COMMENT '用户账号',
`user_nickname` varchar(255) DEFAULT NULL COMMENT '用户昵称',
`user_avatar` varchar(255) DEFAULT NULL COMMENT '用户头像',
`type` tinyint(4) DEFAULT '0' COMMENT '互动动作1评论2私信3点赞',
`content` text COMMENT '评论内容',
`hash` varchar(255) DEFAULT NULL COMMENT '哈希值',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE,
KEY `task_account_id` (`task_account_id`) USING BTREE,
KEY `device_code` (`device_code`) USING BTREE,
KEY `hash` (`hash`) USING BTREE,
KEY `account` (`account`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频号点赞接管记录表';


DELETE FROM `la_sv_device_execution_schedule` WHERE `end_time` = '06:00' AND `scene` = 16;
DELETE FROM `la_marketing_template_schedule` WHERE `end_time` = '06:00' AND `scene` = 16;


CREATE TABLE IF NOT EXISTS `la_user_level` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '等级ID',
`level_name` varchar(50) NOT NULL DEFAULT '' COMMENT '等级名称',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序权重',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员等级配置表';

ALTER TABLE `la_user`
ADD COLUMN `level_id` int(11) NOT NULL DEFAULT '-1' COMMENT '会员等级ID:-1=默认会员类型';

ALTER TABLE `la_kb_robot`
ADD COLUMN `permissions` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '权限类型:0全部可用1仅会员等级可用',
ADD COLUMN `member_level_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '可用会员等级ID，逗号分隔';

ALTER TABLE `la_coze_agent`
MODIFY COLUMN `permissions` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '权限类型:0全部可用1仅会员等级可用',
ADD COLUMN `member_level_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '可用会员等级ID，逗号分隔';



CREATE TABLE IF NOT EXISTS `la_sv_device_precise_clues` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`task_name` varchar(255) DEFAULT NULL COMMENT '任务名称',
`auto_type` tinyint(4) DEFAULT '0' COMMENT '是否自动1自动0手动',
`accounts` varchar(1000) DEFAULT NULL COMMENT '账号集合',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败',
`persona_id` int(11) DEFAULT '0' COMMENT '人设id',
`custom_date` varchar(1000) DEFAULT NULL COMMENT '自定义日期',
`time_config` varchar(1000) DEFAULT NULL COMMENT '每日任务执行区间集合',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备精准线索任务';

CREATE TABLE IF NOT EXISTS `la_sv_device_precise_clues_account` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`precise_clues_id` int(11) DEFAULT '0' COMMENT '精准线索任务id',
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`account` varchar(255) DEFAULT NULL COMMENT '账号id',
`account_type` tinyint(4) DEFAULT '0' COMMENT '账号类型0未知1微信视频号3小红书4抖音5快手',
`nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`auto_type` tinyint(4) DEFAULT '0' COMMENT '是否自动1自动0手动',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`start_time` int(11) DEFAULT NULL COMMENT '执行开始时间',
`end_time` int(11) DEFAULT NULL COMMENT '执行结束时间',
`day` date DEFAULT NULL COMMENT '执行日期',
`clues` json DEFAULT NULL COMMENT '客户线索',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0待执行1执行中2执行完成3执行失败4中断',
`persona_id` int(11) DEFAULT '0' COMMENT '人设id',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE,
KEY `precise_clues_id` (`precise_clues_id`) USING BTREE,
KEY `account` (`account`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备精准线索账号表';


CREATE TABLE IF NOT EXISTS `la_sv_device_precise_clues_record` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`precise_clues_id` int(11) DEFAULT '0' COMMENT '精准线索任务任务id',
`precise_clues_account_id` int(11) DEFAULT '0' COMMENT '精准线索账号id',
`auto_type` tinyint(4) DEFAULT '0' COMMENT '0手动1自动',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`account` varchar(255) DEFAULT NULL COMMENT '执行账号',
`nickname` varchar(255) DEFAULT NULL COMMENT '好友名称',
`target_user_id` varchar(128) NOT NULL DEFAULT '' COMMENT '抖音用户ID',
`target_nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '触达用户昵称',
`video_id` varchar(255) NOT NULL DEFAULT '' COMMENT '所属视频ID',
`video_key` char(64) NOT NULL DEFAULT '' COMMENT '所属视频去重键',
`video_title` varchar(500) NOT NULL DEFAULT '' COMMENT '所属视频标题',
`video_url` varchar(1000) NOT NULL DEFAULT '' COMMENT '所属视频链接',
`round_no` int(11) NOT NULL DEFAULT '0' COMMENT '执行轮次',
`status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '执行状态:0待处理1成功2失败3跳过',
`edit_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '编辑状态:0未编辑1成功2失败',
`delete_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '删除上一轮艾特状态:0未删除1成功2失败',
`touch_time` int(11) DEFAULT NULL COMMENT '触达时间',
`remove_time` int(11) DEFAULT NULL COMMENT '删除上一轮艾特时间',
`retry_count` int(11) NOT NULL DEFAULT '0' COMMENT '重试次数',
`total_count` int(11) NOT NULL DEFAULT '0' COMMENT '待触达总人数',
`touched_count` int(11) NOT NULL DEFAULT '0' COMMENT '已触达人数',
`remaining_count` int(11) NOT NULL DEFAULT '0' COMMENT '剩余待触达人数',
`edit_success_count` int(11) NOT NULL DEFAULT '0' COMMENT '编辑成功次数',
`delete_success_count` int(11) NOT NULL DEFAULT '0' COMMENT '删除成功次数',
`fail_reason` varchar(1000) NOT NULL DEFAULT '' COMMENT '失败原因',
`exception_log` json DEFAULT NULL COMMENT '异常日志',
`extra` json DEFAULT NULL COMMENT '触达用户扩展信息',
`raw_payload` json DEFAULT NULL COMMENT 'RPA上报原始内容',
`day` date DEFAULT NULL COMMENT '生成日期',
`remark` varchar(255) DEFAULT NULL COMMENT '备注',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE,
KEY `precise_clues_id` (`precise_clues_id`) USING BTREE,
KEY `precise_clues_account_id` (`precise_clues_account_id`) USING BTREE,
KEY `device_code` (`device_code`) USING BTREE,
KEY `day` (`day`) USING BTREE,
KEY `account` (`account`) USING BTREE,
KEY `target_user_id` (`target_user_id`) USING BTREE,
KEY `video_key` (`video_key`) USING BTREE,
KEY `round_no` (`round_no`) USING BTREE,
KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备精准线索记录表';


CREATE TABLE IF NOT EXISTS `la_sv_device_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`app_type` tinyint(4) DEFAULT NULL COMMENT '平台类型',
`content` json DEFAULT NULL COMMENT '日志内容',
`app_version` varchar(100) DEFAULT NULL COMMENT 'rpa版本号',
`day` date DEFAULT NULL COMMENT '日期',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE,
KEY `device_code` (`device_code`) USING BTREE,
KEY `day` (`day`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备日志表';


ALTER TABLE `la_sv_device_viral_record`
ADD COLUMN `likes` varchar(255) default '0'  COMMENT '点赞数',
ADD COLUMN `comments` varchar(255) default '0'  COMMENT '评论数',
ADD COLUMN `is_interested` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否感兴趣:0否1是',
ADD KEY `idx_user_interested_day` (`user_id`, `is_interested`, `day`);


ALTER TABLE `la_ai_persona_synthesis_copywriting`
ADD COLUMN `day` date NULL COMMENT '使用日期' AFTER `use_state`;

ALTER TABLE `la_ai_persona_enterprise`
ADD COLUMN `hot_words` json NULL COMMENT '爆款关键词' AFTER `is_wechat_updated`,
ADD COLUMN `global_option` json NULL COMMENT '全局选项' AFTER `hot_words`;

ALTER TABLE `la_ai_persona_individual`
ADD COLUMN `hot_words` json NULL COMMENT '爆款关键词' AFTER `is_wechat_updated`,
ADD COLUMN `global_option` json NULL COMMENT '全局选项' AFTER `hot_words`;

ALTER TABLE `la_ai_persona_local`
ADD COLUMN `hot_words` json NULL COMMENT '爆款关键词' AFTER `is_wechat_updated`,
ADD COLUMN `global_option` json NULL COMMENT '全局选项' AFTER `hot_words`;

CREATE TABLE IF NOT EXISTS `la_video_slices` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`original_video_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '原视频ID',
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`original_name` varchar(255) NOT NULL DEFAULT '' COMMENT '原素材名称',
`original_duration` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原视频时长(秒)',
`original_path` varchar(1000) NOT NULL DEFAULT '' COMMENT '原文件路径，切片成功删除后留空',
`slice_count` int unsigned NOT NULL DEFAULT '0' COMMENT '切片总数',
`status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态:0待处理 1处理中 2成功 3失败',
`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`deleted_at` timestamp NULL DEFAULT NULL COMMENT '软删除时间',
PRIMARY KEY (`id`),
KEY `idx_original_video_id` (`original_video_id`),
KEY `idx_user_id` (`user_id`),
KEY `idx_status` (`status`),
KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频切片记录表';

CREATE TABLE IF NOT EXISTS `la_video_slice_items` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`slice_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联video_slices.id',
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`sequence` int unsigned NOT NULL DEFAULT '0' COMMENT '切片序号',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '切片素材名称',
`time_start` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '起始时间(秒)',
`time_end` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '结束时间(秒)',
`duration` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '该片时长(秒)',
`width` varchar(10) NOT NULL DEFAULT '' COMMENT '宽',
`height` varchar(10) NOT NULL DEFAULT '' COMMENT '高',
`file_path` varchar(1000) NOT NULL DEFAULT '' COMMENT '文件存储路径',
`file_size` bigint unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
`material_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联素材库ID',
`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
PRIMARY KEY (`id`),
KEY `idx_slice_id` (`slice_id`),
KEY `idx_user_id` (`user_id`),
KEY `idx_material_id` (`material_id`),
KEY `idx_sequence` (`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频切片明细表';

ALTER TABLE `la_ai_persona_material`
MODIFY COLUMN `width` varchar(10) NOT NULL DEFAULT '' COMMENT '宽',
MODIFY COLUMN `height` varchar(10) NOT NULL DEFAULT '' COMMENT '高',
ADD COLUMN `source_type` varchar(32) NOT NULL DEFAULT '' COMMENT '来源类型: slice=视频切片 original=原视频',
ADD COLUMN `source_video_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源原视频ID',
ADD COLUMN `slice_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '切片状态:0无需分割 1待分割 2分割中 3分割成功 4分割失败',
ADD KEY `idx_slice_status` (`slice_status`);

ALTER TABLE `la_ai_persona`
ADD COLUMN `content_publish_config` json NULL COMMENT '人设内容发布生成配置';

DELETE FROM `la_model_config` WHERE  `scene`= 'automation_precise_clues';
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
VALUES ('automation_precise_clues', 10319, '算力/次', '(自动化)精准获客任务', 1.00, '一次1算力', 1, NULL, NULL);

ALTER TABLE `la_ai_persona_agent_config`
ADD COLUMN `platform_agent_config` json NULL COMMENT '社媒平台智能客服独立配置';


ALTER TABLE `la_file`
ADD COLUMN `transcode_status` TINYINT(4) NOT NULL DEFAULT 0 COMMENT '转码状态:0=无需转码 1=待转码 2=转码中 3=完成 4=失败' AFTER `uri`,
ADD INDEX `idx_transcode_status` (`transcode_status`);


ALTER TABLE `la_ai_wechat_circle_task`
ADD COLUMN `shanjian_video_task_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联闪剪视频任务id';

ALTER TABLE `la_ai_wechat_circle_task_config`
ADD COLUMN `shanjian_video_task_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联闪剪视频任务id';

ALTER TABLE `la_ai_wechat_create_group_log`
ADD COLUMN `message_content` text NULL COMMENT '触发话术' AFTER `group_name`;

DELETE FROM `la_system_menu` WHERE `id` in (577,578,579,580,581,582);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (577, 117, 'C', '会员等级', '', 95, 'user.user_level/lists', 'user_level', 'consumer/user_level/lists', '', '', 0, 1, 0, 1780483200, 1780993231);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (578, 577, 'A', '新增', '', 0, 'user.user_level/add', '', '', '', '', 0, 1, 0, 1780483200, 1780992132);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (579, 577, 'A', '编辑', '', 0, 'user.user_level/edit', '', '', '', '', 0, 1, 0, 1780483200, 1780992137);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (580, 577, 'A', '删除', '', 0, 'user.user_level/delete', '', '', '', '', 0, 1, 0, 1780483200, 1780992146);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (581, 577, 'A', '状态', '', 0, 'user.user_level/changeStatus', '', '', '', '', 0, 1, 0, 1780483200, 1780992157);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (582, 362, 'C', '搜索测试', '', 0, 'ai_application.kn/search_test', 'search_test', 'ai_application/knowledge_base/search_test', '/ai_application/knowledge/lists', '', 0, 0, 0, 1781144349, 1781144567);

DELETE FROM `la_system_menu` WHERE `id` in (597,598,599,600,601,602,603,604,605,606);
INSERT INTO `la_system_menu` VALUES (597, 166, 'M', '激活码管理', '', 0, '', 'device_auth', '', '', '', 0, 1, 0, 1781495345, 1781495356);
INSERT INTO `la_system_menu` VALUES (598, 597, 'C', '激活码列表', '', 0, 'deviceauth.deviceAuthCode/lists', 'lists', 'finance/device_auth/code/lists', '', '', 0, 1, 0, 1781495388, 1781507331);
INSERT INTO `la_system_menu` VALUES (599, 598, 'A', '导出', '', 0, 'deviceauth.deviceAuthCode/export', '', '', '', '', 0, 1, 0, 1781495476, 1781495476);
INSERT INTO `la_system_menu` VALUES (600, 597, 'C', '激活码套餐', '', 0, 'deviceauth.deviceAuthPlan/lists', 'plan/lists', 'finance/device_auth/plan/lists', '', '', 0, 1, 0, 1781495531, 1781507340);
INSERT INTO `la_system_menu` VALUES (601, 600, 'A', '新增', '', 0, 'deviceauth.deviceAuthPlan/add', '', '', '', '', 0, 1, 0, 1781495689, 1781495689);
INSERT INTO `la_system_menu` VALUES (602, 600, 'A', '编辑', '', 0, 'deviceauth.deviceAuthPlan/edit', '', '', '', '', 0, 1, 0, 1781495701, 1781495701);
INSERT INTO `la_system_menu` VALUES (603, 600, 'A', '删除', '', 0, 'deviceauth.deviceAuthPlan/delete', '', '', '', '', 0, 1, 0, 1781495712, 1781495712);
INSERT INTO `la_system_menu` VALUES (604, 600, 'A', '状态', '', 0, 'deviceauth.deviceAuthPlan/changeStatus', '', '', '', '', 0, 1, 0, 1781495731, 1781495731);
INSERT INTO `la_system_menu` VALUES (605, 597, 'C', '订单记录', '', 0, 'deviceauth.deviceAuthOrder/lists', 'order/lists', 'finance/device_auth/order/lists', '', '', 0, 1, 0, 1781495852, 1781507346);
INSERT INTO `la_system_menu` VALUES (606, 605, 'A', '导出', '', 0, 'deviceauth.deviceAuthOrder/export', '', '', '', '', 0, 1, 0, 1781495874, 1781495874);

UPDATE `la_dev_crontab` SET `expression` = '0,10,20,30 0 * * *', `update_time` = UNIX_TIMESTAMP() WHERE `command` = 'auto_device_create_cron';

ALTER TABLE `la_ai_wechat_contact`
MODIFY COLUMN `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号' AFTER `label_ids`;

ALTER TABLE `la_sv_publish_setting_detail`
ADD INDEX(`publish_account_id`) USING BTREE,
ADD INDEX(`device_code`) USING BTREE,
ADD INDEX(`account`) USING BTREE,
ADD INDEX(`publish_time`) USING BTREE;

ALTER TABLE `la_sv_device_task`
ADD INDEX(`persona_id`) USING BTREE;

ALTER TABLE `la_sv_add_wechat_record`
ADD COLUMN `add_time` int NULL COMMENT '账号添加时间' AFTER `exec_task_id`;

ALTER TABLE `la_sv_crawling_manual_task_record`
ADD COLUMN `add_time` int NULL COMMENT '添加时间' AFTER `image`;

ALTER TABLE `la_sv_device_viral_record`
MODIFY COLUMN `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '爆款分享信息' AFTER `keyword`;

ALTER TABLE `la_sv_device`
ADD COLUMN `auth_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '授权类型: 0无 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
ADD COLUMN `auth_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '授权状态: 0未激活 1运行中 2已过期' AFTER `auth_type`,
ADD COLUMN `auth_start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '授权开始时间' AFTER `auth_status`,
ADD COLUMN `auth_expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT '授权到期时间(0=永久)' AFTER `auth_start_time`,
ADD COLUMN `last_auth_code_id` int unsigned NOT NULL DEFAULT '0' COMMENT '最近一次激活使用的授权码ID' AFTER `auth_expire_time`,
ADD KEY `idx_auth_status_expire` (`auth_status`, `auth_expire_time`) USING BTREE;

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES
('device_auth', 'is_open', '1', 1781489264, 1781489264),
('device_auth', 'code_prefix', 'CARD', 1781489264, 1781489264);

ALTER TABLE `la_models_cost`
ADD COLUMN `cost_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本价(算力/1k tokens)' AFTER `price`;

DELETE FROM `la_dev_crontab` WHERE `command` = 'sync_chat_models';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('同步中台对话模型', 1, 0, '', 'sync_chat_models', '', 1, '0 */6 * * *', '', 0, '0', '0', 0, 0, NULL);

ALTER TABLE `la_device_auth_code`
ADD COLUMN `middle_license_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '中台授权码ID' AFTER `order_id`,
ADD COLUMN `middle_order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '中台订单号' AFTER `middle_license_id`,
ADD COLUMN `duration_days` int unsigned NOT NULL DEFAULT 0 COMMENT '授权天数(自定义类型或中台同步)' AFTER `type`,
ADD COLUMN `middle_device_code` varchar(64) NOT NULL DEFAULT '' COMMENT '中台预绑设备码' AFTER `device_code`,
ADD UNIQUE KEY `uk_middle_license_id` (`middle_license_id`),
ADD KEY `idx_middle_order_no` (`middle_order_no`);

DELETE FROM `la_dev_crontab` WHERE `command` = 'sync_device_auth_codes';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('同步中台激活码', 1, 0, '', 'sync_device_auth_codes', '', 1, '0 */2 * * *', '', 0, '0', '0', 0, 0, NULL);

ALTER TABLE `la_models_cost`
ADD COLUMN `output_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '输出售价(算力/1k tokens)' AFTER `cost_price`,
ADD COLUMN `quota_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '计费类型:0=按token,1=按次' AFTER `output_price`,
ADD COLUMN `model_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '按次售价(算力/次)' AFTER `quota_type`;

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-catering-franchise/01.jpg","/static/case/templates/enterprise-catering-franchise/02.jpg","/static/case/templates/enterprise-catering-franchise/03.jpg","/static/case/templates/enterprise-catering-franchise/04.jpg","/static/case/templates/enterprise-catering-franchise/05.jpg","/static/case/templates/enterprise-catering-franchise/06.jpg","/static/case/templates/enterprise-catering-franchise/07.jpg","/static/case/templates/enterprise-catering-franchise/08.jpg","/static/case/templates/enterprise-catering-franchise/09.jpg","/static/case/templates/enterprise-catering-franchise/10.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-catering-franchise/01.mp4"]'
WHERE `title` = '特色餐饮品牌全国招商';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-business-franchise/01.jpg","/static/case/templates/enterprise-business-franchise/02.jpg","/static/case/templates/enterprise-business-franchise/03.jpg","/static/case/templates/enterprise-business-franchise/04.jpg","/static/case/templates/enterprise-business-franchise/05.jpg","/static/case/templates/enterprise-business-franchise/06.jpg","/static/case/templates/enterprise-business-franchise/07.jpg","/static/case/templates/enterprise-business-franchise/08.jpg","/static/case/templates/enterprise-business-franchise/09.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-business-franchise/01.mp4"]'
WHERE `title` = '创新体验馆全国加盟招商';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-real-estate-sales/01.jpg","/static/case/templates/enterprise-real-estate-sales/02.jpg","/static/case/templates/enterprise-real-estate-sales/03.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-real-estate-sales/01.mp4"]'
WHERE `title` = '同城新房楼盘精准拓客';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-equipment-sales/01.jpg","/static/case/templates/enterprise-equipment-sales/02.jpg","/static/case/templates/enterprise-equipment-sales/03.jpg","/static/case/templates/enterprise-equipment-sales/04.jpg","/static/case/templates/enterprise-equipment-sales/05.jpg","/static/case/templates/enterprise-equipment-sales/06.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-equipment-sales/01.mp4"]'
WHERE `title` = '农村空气能供暖设备直销';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-machinery-equipment/01.jpg","/static/case/templates/enterprise-machinery-equipment/02.jpg","/static/case/templates/enterprise-machinery-equipment/03.jpg","/static/case/templates/enterprise-machinery-equipment/04.jpg","/static/case/templates/enterprise-machinery-equipment/05.jpg","/static/case/templates/enterprise-machinery-equipment/06.jpg","/static/case/templates/enterprise-machinery-equipment/07.jpg","/static/case/templates/enterprise-machinery-equipment/08.jpg","/static/case/templates/enterprise-machinery-equipment/09.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-machinery-equipment/01.mp4"]'
WHERE `title` = '工业硬度计设备精准直销';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-tile-franchise/01.jpg","/static/case/templates/enterprise-tile-franchise/02.jpg","/static/case/templates/enterprise-tile-franchise/03.jpg","/static/case/templates/enterprise-tile-franchise/04.jpg","/static/case/templates/enterprise-tile-franchise/05.jpg","/static/case/templates/enterprise-tile-franchise/06.jpg","/static/case/templates/enterprise-tile-franchise/07.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-tile-franchise/01.mp4"]'
WHERE `title` = '瓷砖源头厂家全国招商';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-exhibition-franchise/01.jpg","/static/case/templates/enterprise-exhibition-franchise/02.jpg","/static/case/templates/enterprise-exhibition-franchise/03.jpg","/static/case/templates/enterprise-exhibition-franchise/04.jpg","/static/case/templates/enterprise-exhibition-franchise/05.jpg","/static/case/templates/enterprise-exhibition-franchise/06.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-exhibition-franchise/01.mp4"]'
WHERE `title` = '全国茶博会展商定向邀约';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-finance-software/01.jpg","/static/case/templates/enterprise-finance-software/02.jpg","/static/case/templates/enterprise-finance-software/03.jpg","/static/case/templates/enterprise-finance-software/04.jpg","/static/case/templates/enterprise-finance-software/05.jpg","/static/case/templates/enterprise-finance-software/06.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-finance-software/01.mp4"]'
WHERE `title` = '财务软件精准营销与推广';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-finance-platform/01.jpg","/static/case/templates/enterprise-finance-platform/02.jpg","/static/case/templates/enterprise-finance-platform/03.jpg","/static/case/templates/enterprise-finance-platform/04.jpg","/static/case/templates/enterprise-finance-platform/05.jpg","/static/case/templates/enterprise-finance-platform/06.jpg","/static/case/templates/enterprise-finance-platform/07.jpg","/static/case/templates/enterprise-finance-platform/08.jpg","/static/case/templates/enterprise-finance-platform/09.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-finance-platform/01.mp4"]'
WHERE `title` = '财税平台全国合伙人招募';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-insulation-material/01.jpg","/static/case/templates/enterprise-insulation-material/02.jpg","/static/case/templates/enterprise-insulation-material/03.jpg","/static/case/templates/enterprise-insulation-material/04.jpg","/static/case/templates/enterprise-insulation-material/05.jpg","/static/case/templates/enterprise-insulation-material/06.jpg","/static/case/templates/enterprise-insulation-material/07.jpg","/static/case/templates/enterprise-insulation-material/08.jpg","/static/case/templates/enterprise-insulation-material/09.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-insulation-material/01.mp4"]'
WHERE `title` = '冷库保温工程精准拓客';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/enterprise-agriculture-industry/01.jpg","/static/case/templates/enterprise-agriculture-industry/02.jpg"]',
`detail_videos` = '["/static/case/templates/enterprise-agriculture-industry/01.mp4"]'
WHERE `title` = '农资产品定向推广(樱桃园)';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-driving-school-franchise/01.jpg","/static/case/templates/personalip-driving-school-franchise/02.jpg","/static/case/templates/personalip-driving-school-franchise/03.jpg","/static/case/templates/personalip-driving-school-franchise/04.jpg"]',
`detail_videos` = '["/static/case/templates/personalip-driving-school-franchise/01.mp4"]'
WHERE `title` = '驾考理论培训项目全国招商';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-building-material-franchise/01.jpg","/static/case/templates/personalip-building-material-franchise/02.jpg","/static/case/templates/personalip-building-material-franchise/03.jpg","/static/case/templates/personalip-building-material-franchise/04.jpg","/static/case/templates/personalip-building-material-franchise/05.jpg"]',
`detail_videos` = '["/static/case/templates/personalip-building-material-franchise/01.mp4"]'
WHERE `title` = '定制淋浴房全国渠道招商';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-audio-equipment/01.jpg","/static/case/templates/personalip-audio-equipment/02.jpg","/static/case/templates/personalip-audio-equipment/03.jpg","/static/case/templates/personalip-audio-equipment/04.jpg","/static/case/templates/personalip-audio-equipment/05.jpg","/static/case/templates/personalip-audio-equipment/06.jpg","/static/case/templates/personalip-audio-equipment/07.jpg","/static/case/templates/personalip-audio-equipment/08.jpg"]',
`detail_videos` = '["/static/case/templates/personalip-audio-equipment/01.mp4"]'
WHERE `title` = '幼儿园定向音箱设备直销';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-home-renovation/01.jpg","/static/case/templates/personalip-home-renovation/02.jpg","/static/case/templates/personalip-home-renovation/03.jpg","/static/case/templates/personalip-home-renovation/04.jpg","/static/case/templates/personalip-home-renovation/05.jpg","/static/case/templates/personalip-home-renovation/06.jpg","/static/case/templates/personalip-home-renovation/07.jpg"]',
`detail_videos` = '["/static/case/templates/personalip-home-renovation/01.mp4"]'
WHERE `title` = '同城家装精准获客与转化';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-national-defense-education/01.jpg","/static/case/templates/personalip-national-defense-education/02.jpg","/static/case/templates/personalip-national-defense-education/03.jpg","/static/case/templates/personalip-national-defense-education/04.jpg","/static/case/templates/personalip-national-defense-education/05.jpg","/static/case/templates/personalip-national-defense-education/06.jpg"]',
`detail_videos` = '["/static/case/templates/personalip-national-defense-education/01.mp4"]'
WHERE `title` = '同城少儿国防夏令营招生';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-wedding-hotel/01.jpg","/static/case/templates/local-business-wedding-hotel/02.jpg","/static/case/templates/local-business-wedding-hotel/03.jpg","/static/case/templates/local-business-wedding-hotel/04.jpg","/static/case/templates/local-business-wedding-hotel/05.jpg","/static/case/templates/local-business-wedding-hotel/06.jpg","/static/case/templates/local-business-wedding-hotel/07.jpg","/static/case/templates/local-business-wedding-hotel/08.jpg","/static/case/templates/local-business-wedding-hotel/09.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-wedding-hotel/01.mp4"]'
WHERE `title` = '同城婚宴酒店精准预订';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-medical-beauty/01.jpg","/static/case/templates/local-business-medical-beauty/02.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-medical-beauty/01.mp4"]'
WHERE `title` = '同城医美机构精准拓客';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-commercial-renovation/01.jpg","/static/case/templates/local-business-commercial-renovation/02.jpg","/static/case/templates/local-business-commercial-renovation/03.jpg","/static/case/templates/local-business-commercial-renovation/04.jpg","/static/case/templates/local-business-commercial-renovation/05.jpg","/static/case/templates/local-business-commercial-renovation/06.jpg","/static/case/templates/local-business-commercial-renovation/07.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-commercial-renovation/01.mp4"]'
WHERE `title` = '同城商业空间公装服务';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-beauty-industry/01.jpg","/static/case/templates/local-business-beauty-industry/02.jpg","/static/case/templates/local-business-beauty-industry/03.jpg","/static/case/templates/local-business-beauty-industry/04.jpg","/static/case/templates/local-business-beauty-industry/05.jpg","/static/case/templates/local-business-beauty-industry/06.jpg","/static/case/templates/local-business-beauty-industry/07.jpg","/static/case/templates/local-business-beauty-industry/08.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-beauty-industry/01.mp4"]'
WHERE `title` = '周边美容院精准引流到店';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-employment-training/01.jpg","/static/case/templates/local-business-employment-training/02.jpg","/static/case/templates/local-business-employment-training/03.jpg","/static/case/templates/local-business-employment-training/04.jpg","/static/case/templates/local-business-employment-training/05.jpg","/static/case/templates/local-business-employment-training/06.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-employment-training/01.mp4"]'
WHERE `title` = '同城国企就业培训招募';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-adult-experience/01.jpg","/static/case/templates/local-business-adult-experience/02.jpg","/static/case/templates/local-business-adult-experience/03.jpg","/static/case/templates/local-business-adult-experience/04.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-adult-experience/01.mp4"]'
WHERE `title` = 'AI体验馆同城精准引流';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-score-improvement/01.jpg","/static/case/templates/local-business-score-improvement/02.jpg","/static/case/templates/local-business-score-improvement/03.jpg","/static/case/templates/local-business-score-improvement/04.jpg","/static/case/templates/local-business-score-improvement/05.jpg","/static/case/templates/local-business-score-improvement/06.jpg","/static/case/templates/local-business-score-improvement/07.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-score-improvement/01.mp4"]'
WHERE `title` = '中高考冲刺提分精准招生';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-college-exam-registration/01.jpg","/static/case/templates/local-business-college-exam-registration/02.jpg","/static/case/templates/local-business-college-exam-registration/03.jpg","/static/case/templates/local-business-college-exam-registration/04.jpg","/static/case/templates/local-business-college-exam-registration/05.jpg","/static/case/templates/local-business-college-exam-registration/06.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-college-exam-registration/01.mp4"]'
WHERE `title` = '高考志愿填报精准指导';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-cake-dessert/01.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-cake-dessert/01.mp4"]'
WHERE `title` = '周边烘焙甜品店引流促销';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-catering-industry/01.jpg","/static/case/templates/local-business-catering-industry/02.jpg","/static/case/templates/local-business-catering-industry/03.jpg","/static/case/templates/local-business-catering-industry/04.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-catering-industry/01.mp4"]'
WHERE `title` = '同城餐饮门店团购大促';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-scalp-care/01.jpg","/static/case/templates/local-business-scalp-care/02.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-scalp-care/01.mp4"]'
WHERE `title` = '高端头皮抗衰门店拓客';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/local-business-indoor-entertainment/01.jpg","/static/case/templates/local-business-indoor-entertainment/02.jpg","/static/case/templates/local-business-indoor-entertainment/03.jpg"]',
`detail_videos` = '["/static/case/templates/local-business-indoor-entertainment/01.mp4"]'
WHERE `title` = '室内潮玩娱乐同城曝光';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-workplace-sidejob/01.png","/static/case/templates/personalip-workplace-sidejob/02.png","/static/case/templates/personalip-workplace-sidejob/03.png","/static/case/templates/personalip-workplace-sidejob/04.png","/static/case/templates/personalip-workplace-sidejob/05.png"]',
`detail_videos` = '["/static/case/templates/personalip-workplace-sidejob/01.mp4"]'
WHERE `title` = '职场人副业变现个人IP精准引流';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-fitness-trainer/01.png","/static/case/templates/personalip-fitness-trainer/02.png","/static/case/templates/personalip-fitness-trainer/03.png","/static/case/templates/personalip-fitness-trainer/04.png","/static/case/templates/personalip-fitness-trainer/05.png"]',
`detail_videos` = '["/static/case/templates/personalip-fitness-trainer/01.mp4"]'
WHERE `title` = '健身私教个人IP同城精准获客';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-lawyer-ip/01.png","/static/case/templates/personalip-lawyer-ip/02.png","/static/case/templates/personalip-lawyer-ip/03.png"]',
`detail_videos` = '["/static/case/templates/personalip-lawyer-ip/01.mp4"]'
WHERE `title` = '律师个人IP精准引流变现';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-parenting-education/01.png","/static/case/templates/personalip-parenting-education/02.png","/static/case/templates/personalip-parenting-education/03.png","/static/case/templates/personalip-parenting-education/04.png"]',
`detail_videos` = '["/static/case/templates/personalip-parenting-education/01.mp4"]'
WHERE `title` = '亲子教育博主课程精准招生';

UPDATE `la_catering_franchise` SET
`detail_images` = '["/static/case/templates/personalip-ielts-toefl/01.png","/static/case/templates/personalip-ielts-toefl/02.png"]',
`detail_videos` = '["/static/case/templates/personalip-ielts-toefl/01.mp4"]'
WHERE `title` = '雅思托福备考社群精准招募';