ALTER TABLE `la_marketing_template`
ADD COLUMN `persona_type` tinyint NULL DEFAULT 0 COMMENT '人设类型：0通用 1个人IP 2企业服务 3本地商家' AFTER `persona_id`,
ADD INDEX(`persona_type`) USING BTREE;

UPDATE `la_sv_device_execution_schedule` 
SET `end_time` = '08:40', `task_category` = '视频发布'
WHERE `persona_type` = 1 and `start_time` = '08:00' and `end_time` = '08:30' and `scene` = 5;

UPDATE `la_sv_device_execution_schedule` 
SET  `start_time` = '08:40', `task_category` = '留痕获客'
WHERE `persona_type` = 1 and `start_time` = '08:30' and `end_time` = '09:00' and `scene` = 3;

UPDATE `la_sv_device_execution_schedule` 
SET `end_time` = '09:10', `task_category` = '视频发布'
WHERE `persona_type` = 2 and `start_time` = '08:30' and `end_time` = '09:00' and `scene` = 5;

UPDATE `la_sv_device_execution_schedule` 
SET `start_time` = '09:10', `task_category` = '私信接管'
WHERE `persona_type` = 2 and `start_time` = '09:00' and `end_time` = '09:30' and  `scene` = 6;

UPDATE `la_sv_device_execution_schedule` 
SET `end_time` = '09:10', `task_category` = '视频发布', `scene` = 5
WHERE `persona_type` = 3 and `start_time` = '08:30' and `end_time` = '09:00' and  `scene` = 5;

UPDATE `la_sv_device_execution_schedule` 
SET `start_time` = '09:10', `task_category` = '私信接管'
WHERE `persona_type` = 3 and `start_time` = '09:00' and `end_time` = '09:15' and `scene` = 6;

UPDATE `la_sv_device_execution_schedule` 
SET `end_time` = '17:10', `task_category` = '视频发布'
WHERE `persona_type` = 3 and `start_time` = '16:30' and `end_time` = '17:00' and `scene` = 5;

DELETE FROM `la_sv_device_execution_schedule`
WHERE `persona_type` = 3 and `start_time` = '17:00' and `end_time` = '17:10'  and `scene` = 6;

-- 设备CDK同步字段 middle_license_id 对应中台 cdk_codes.id；本地码池记录默认 0，不能使用唯一索引。
SET @idx_exists := (
    SELECT COUNT(1)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_device_auth_code'
      AND INDEX_NAME = 'uk_middle_license_id'
);
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `la_device_auth_code` DROP INDEX `uk_middle_license_id`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(1)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_device_auth_code'
      AND INDEX_NAME = 'idx_middle_license_id'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `la_device_auth_code` ADD KEY `idx_middle_license_id` (`middle_license_id`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 将已上线 v2.10.0/update21000 中设备授权码相关文案迁移为设备CDK。
UPDATE `la_system_menu`
SET `name` = CASE `id`
    WHEN 597 THEN '设备CDK管理'
    WHEN 598 THEN '设备CDK列表'
    WHEN 600 THEN '设备CDK套餐'
    ELSE `name`
END
WHERE `id` IN (597, 598, 600);

UPDATE `la_dev_crontab`
SET `name` = '同步中台设备CDK',
    `expression` = '* * * * *'
WHERE `command` = 'sync_device_auth_codes';

ALTER TABLE `la_device_auth_batch`
MODIFY COLUMN `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'CDK类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
MODIFY COLUMN `total_num` int unsigned NOT NULL DEFAULT '0' COMMENT '本批次设备CDK总数',
COMMENT='设备CDK批次表';

ALTER TABLE `la_device_auth_code`
MODIFY COLUMN `code` varchar(64) NOT NULL COMMENT '设备CDK串码',
MODIFY COLUMN `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'CDK类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
MODIFY COLUMN `middle_license_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '中台设备CDK ID',
MODIFY COLUMN `duration_days` int unsigned NOT NULL DEFAULT 0 COMMENT 'CDK天数(自定义类型或中台同步)',
COMMENT='设备CDK表';

ALTER TABLE `la_device_auth_order`
MODIFY COLUMN `biz_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '业务类型: 1购买设备CDK 2设备续费',
MODIFY COLUMN `auth_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'CDK类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
COMMENT='设备CDK购买/续费订单表';

ALTER TABLE `la_sv_device`
MODIFY COLUMN `auth_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '历史授权类型: 0无 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
MODIFY COLUMN `last_auth_code_id` int unsigned NOT NULL DEFAULT '0' COMMENT '最近一次激活使用的设备CDK ID';

-- 设备CDK新表迁移：旧 la_device_auth_code 表保留但业务不再使用。
CREATE TABLE IF NOT EXISTS `la_device_cdk_code` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`batch_id` int unsigned NOT NULL DEFAULT '0' COMMENT '批次ID',
`code` varchar(64) NOT NULL COMMENT '设备CDK串码',
`type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'CDK类型: 1永久卡 2周卡 3月卡 4季卡 5半年卡 6年卡 7自定义',
`duration_days` int unsigned NOT NULL DEFAULT 0 COMMENT 'CDK天数(自定义类型或中台同步)',
`status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态: 0未使用 1已使用 2已作废',
`source` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '入库来源: 1中台生成 2文件导入',
`owner_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '归属用户ID，0=码池未分配',
`purchase_time` int unsigned NOT NULL DEFAULT '0' COMMENT '用户购买时间，0=未购买',
`order_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联购买订单ID',
`middle_license_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '中台设备CDK ID',
`middle_order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '中台订单号',
`user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '使用用户ID(兑换时)',
`device_id` int unsigned NOT NULL DEFAULT '0' COMMENT '使用设备ID',
`device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '使用设备号',
`middle_device_code` varchar(64) NOT NULL DEFAULT '' COMMENT '中台预绑设备码',
`use_time` int unsigned NOT NULL DEFAULT '0' COMMENT '使用时间(兑换时间)',
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
KEY `idx_middle_license_id` (`middle_license_id`) USING BTREE,
KEY `idx_middle_order_no` (`middle_order_no`) USING BTREE,
KEY `idx_create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备CDK表';

SET @column_exists := (
    SELECT COUNT(1)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_sv_device'
      AND COLUMN_NAME = 'last_cdk_code_id'
);
SET @sql := IF(@column_exists = 0, 'ALTER TABLE `la_sv_device` ADD COLUMN `last_cdk_code_id` int unsigned NOT NULL DEFAULT ''0'' COMMENT ''最近一次兑换使用的设备CDK ID'' AFTER `last_auth_code_id`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(1)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'la_sv_device'
      AND INDEX_NAME = 'idx_last_cdk_code_id'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `la_sv_device` ADD KEY `idx_last_cdk_code_id` (`last_cdk_code_id`) USING BTREE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `la_sv_device_used` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
`device_id` int unsigned NOT NULL DEFAULT '0' COMMENT '设备ID',
`user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备码',
`is_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已使用: 0否 1是',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_device_id` (`device_id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_device_code` (`device_code`) USING BTREE,
UNIQUE KEY `uk_user_device` (`user_id`, `device_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备使用记录表';

INSERT INTO `la_dev_crontab`
(`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES
('素材转码卡住恢复', 1, 0, '恢复长时间停留在待转码/转码中的素材任务', 'media:transcode-recover', '--stale 900 --limit 50', 1, '*/2 * * * *', '', NULL, '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

ALTER TABLE `la_shanjian_video_task` 
MODIFY COLUMN `remark` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因' ;