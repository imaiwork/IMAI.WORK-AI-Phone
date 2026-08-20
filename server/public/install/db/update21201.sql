-- v2.12.1 热更新脚本（可重入）
-- 约定：
-- 1) ADD COLUMN / ADD INDEX 先查 information_schema，已存在则 DO 0，避免重跑或结构比对抢先执行后再次失败
-- 2) 表名在 information_schema 中写成 TRIM(BOTH '`' FROM '`la_xxx`')，兼容执行器替换 `la_` 前缀
-- 3) 每条语句单独一行并以分号结尾（ToolsService 只按“行末分号”拆分）
-- 4) 加唯一索引前必须先按唯一键口径去重，禁止直接 ALTER ADD UNIQUE

-- ============================================================
-- 人设合成配置：背景音乐来源/音量、数字人语速
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'music_source');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `music_source` tinyint NOT NULL DEFAULT 1 COMMENT ''背景音乐来源:1系统音乐库2人设音乐库3不使用'' AFTER `template_config`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'music_volume');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `music_volume` decimal(2,1) NOT NULL DEFAULT 0.3 COMMENT ''背景音乐音量0.0-1.0'' AFTER `music_source`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'speech_rate');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `speech_rate` decimal(2,1) NOT NULL DEFAULT 1.0 COMMENT ''数字人语速speedRatio 0.5-2.0'' AFTER `music_volume`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;


-- ============================================================
-- 视频复刻：小红书图文字段 + 按张计费配置
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'platform_type');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `platform_type` tinyint(1) NOT NULL DEFAULT 4 COMMENT ''平台:3小红书4抖音'' AFTER `persona_id`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'media_type');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `media_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1视频2图文'' AFTER `platform_type`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'original_images');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `original_images` json NULL COMMENT ''图文原图列表'' AFTER `thumbnail`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'selected_images');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `selected_images` json NULL COMMENT ''用户确认要改写的图片列表'' AFTER `original_images`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'rewritten_images');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `rewritten_images` json NULL COMMENT ''图文改写后图片列表'' AFTER `selected_images`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT ''图生图状态:0无需1待提交2处理中3成功4失败'' AFTER `rewritten_images`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_task_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_task_id` varchar(255) NOT NULL DEFAULT '''' COMMENT ''图生图任务ID'' AFTER `image_rewrite_status`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_started_at');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_started_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''当前一轮图生图开始时间'' AFTER `image_rewrite_task_id`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_retry_count');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_retry_count` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT ''图生图失联重试次数'' AFTER `image_rewrite_started_at`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_results');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_results` json NULL COMMENT ''逐图改写结果明细'' AFTER `image_rewrite_retry_count`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_success_count');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_success_count` int(11) NOT NULL DEFAULT 0 COMMENT ''图片改写成功张数'' AFTER `image_rewrite_results`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_fail_count');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_fail_count` int(11) NOT NULL DEFAULT 0 COMMENT ''图片改写失败张数'' AFTER `image_rewrite_success_count`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'image_rewrite_charged_count');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `image_rewrite_charged_count` int(11) NOT NULL DEFAULT 0 COMMENT ''图片改写已扣费张数'' AFTER `image_rewrite_fail_count`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'tikhub_raw');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `tikhub_raw` json NULL COMMENT ''TikHub原始响应'' AFTER `image_rewrite_charged_count`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'billing_round');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `billing_round` int(11) unsigned NOT NULL DEFAULT 1 COMMENT ''计费轮次：新建1，重跑+1；改写超时重试不升轮'' AFTER `tikhub_raw`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND INDEX_NAME = 'idx_media_rewrite_status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD KEY `idx_media_rewrite_status` (`media_type`, `image_rewrite_status`, `status`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND INDEX_NAME = 'idx_platform_status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD KEY `idx_platform_status` (`platform_type`, `status`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;


-- ============================================================
-- 定时任务（表名必须用 la_，禁止写死 iw_）
-- ============================================================
DELETE FROM `la_dev_crontab` WHERE `command` = 'video_imitation_image_rewrite_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('手动-爆款复刻小红书图文图片改写', 1, 0, '', 'video_imitation_image_rewrite_cron', '', 1, '*/5 * * * *', NULL, UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

DELETE FROM `la_dev_crontab` WHERE `command` = 'video_imitation_parse_recover_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('手动-爆款复刻图文提取超时回收', 1, 0, 'PARSING 超过30分钟标失败，便于重试；建议每分钟执行', 'video_imitation_parse_recover_cron', '', 1, '* * * * *', '', NULL, '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);


-- ============================================================
-- 自动任务场景开放配置
-- ============================================================
CREATE TABLE IF NOT EXISTS `la_auto_task_scene_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `scene` tinyint(3) unsigned NOT NULL COMMENT '任务场景 1-17',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '场景名称',
  `allow_add` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '允许新增 0关闭 1开启',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scene` (`scene`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='自动任务场景开关配置';

INSERT INTO `la_auto_task_scene_config` (`scene`, `name`, `allow_add`, `create_time`, `update_time`) VALUES
(1, '截流评论获客', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '截流私信获客', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '留痕获客/同城触达', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '视频号获客', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '视频发布', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '私信接管', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '朋友圈发布', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(8, '朋友圈互动', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, '自动加好友', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10, '自动养号', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(11, '评论接管', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(12, '同城曝光', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(13, '同城截流', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(14, '团购截流', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(15, '评论点赞', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `update_time` = VALUES(`update_time`);


-- ============================================================
-- MiniMax闪剪占位任务 / 专业数字人克隆
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_video_task`') AND COLUMN_NAME = 'minimax_task_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_video_task` ADD COLUMN `minimax_task_id` int(11) NOT NULL DEFAULT 0 COMMENT ''关联MiniMax任务ID(iw_minimax_shanjian_task.id)'' AFTER `origin_task_id`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_digital_human_anchor`') AND COLUMN_NAME = 'clone_mode');
SET @s := IF(@c = 0, 'ALTER TABLE `la_digital_human_anchor` ADD COLUMN `clone_mode` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT ''克隆模式：2一克二 3一克三'' AFTER `ai_type`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_anchor`') AND COLUMN_NAME = 'clone_type');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_anchor` ADD COLUMN `clone_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT ''克隆类型：1极速 2专业'' AFTER `dh_id`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

DELETE FROM `la_model_config` WHERE `scene` = 'human_avatar_shanjian_pro';
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
VALUES ('human_avatar_shanjian_pro', 5042, '算力/次', '口播混剪专业数字人克隆', 500.00, '专业数字人克隆，1次/500算力', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- ============================================================
-- 模版库管理 · 任务类型
-- ============================================================
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '任务类型', '', 0, 'setting.autoTaskScene/getConfig', 'scene', 'ai_application/device/task_template/scene/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND `name` = '模版库管理'
    ORDER BY id ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `type` = 'C'
      AND (
          `perms` = 'setting.autoTaskScene/getConfig'
          OR (`name` = '任务类型' AND `component` = 'ai_application/device/task_template/scene/index')
      )
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT c.id, 'A', '保存', '', 0, 'setting.autoTaskScene/setConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'C' AND `perms` = 'setting.autoTaskScene/getConfig'
    ORDER BY id ASC
    LIMIT 1
) c
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `type` = 'A' AND `perms` = 'setting.autoTaskScene/setConfig'
);


-- ============================================================
-- 清理过期设备日志定时任务
-- ============================================================
DELETE FROM `la_dev_crontab` WHERE `command` = 'device_log_clean';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('清理设备日志', 1, 0, '保留最近30天 sv_device_log，分批硬删', 'device_log_clean', '', 2, '33 3 * * *', NULL, UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);


-- ============================================================
-- setAccount 并发防护：去重后再加唯一索引
-- 旧自连接去重会漏掉 TRIM 不一致、多行同值等口径，导致
-- Duplicate entry 'xxx' for key 'uk_account'
-- ============================================================
UPDATE `la_sv_account` SET `account` = TRIM(`account`) WHERE BINARY `account` <> BINARY TRIM(`account`);
DELETE FROM `la_sv_account` WHERE `account` IS NULL OR TRIM(`account`) = '';

DELETE a FROM `la_sv_account` a
INNER JOIN (
  SELECT x.`type`, x.`account`, MAX(x.`id`) AS keep_id
  FROM `la_sv_account` x
  INNER JOIN (
    SELECT `type`, `account`, MAX(IFNULL(`update_time`, 0)) AS max_ut
    FROM `la_sv_account`
    GROUP BY `type`, `account`
  ) m ON x.`type` = m.`type`
    AND x.`account` = m.`account`
    AND IFNULL(x.`update_time`, 0) = m.max_ut
  GROUP BY x.`type`, x.`account`
) k ON a.`type` = k.`type` AND a.`account` = k.`account` AND a.`id` <> k.keep_id;

UPDATE `la_sv_setting` SET `account` = TRIM(`account`) WHERE BINARY `account` <> BINARY TRIM(`account`);
DELETE FROM `la_sv_setting` WHERE `account` IS NULL OR TRIM(`account`) = '';

DELETE s FROM `la_sv_setting` s
INNER JOIN (
  SELECT
    `account`,
    COALESCE(
      MAX(CASE WHEN IFNULL(`robot_id`, 0) > 0 THEN `id` END),
      MAX(`id`)
    ) AS keep_id
  FROM `la_sv_setting`
  GROUP BY `account`
) k ON s.`account` = k.`account` AND s.`id` <> k.keep_id;

DELETE s FROM `la_sv_setting` s
INNER JOIN (
  SELECT `account`, MAX(`id`) AS keep_id
  FROM `la_sv_setting`
  GROUP BY `account`
  HAVING COUNT(*) > 1
) k ON s.`account` = k.`account` AND s.`id` <> k.keep_id;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_account`') AND INDEX_NAME = 'uk_type_account');
SET @s := IF(@c = 0, 'ALTER TABLE `la_sv_account` ADD UNIQUE KEY `uk_type_account` (`type`, `account`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_setting`') AND INDEX_NAME = 'uk_account');
SET @s := IF(@c = 0, 'ALTER TABLE `la_sv_setting` ADD UNIQUE KEY `uk_account` (`account`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;


-- ============================================================
-- 恢复被会员降级误冻的系统默认音色 / 形象
-- ============================================================
UPDATE `la_human_voice`
SET `status` = 1
WHERE `status` = 9
  AND `remark` = 'system_default_voice';

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_device_circle_like_reply`') AND COLUMN_NAME = 'action');
SET @s := IF(@c = 0, 'DO 0', 'ALTER TABLE `la_sv_device_circle_like_reply` MODIFY COLUMN `action` tinyint(4) DEFAULT ''0'' COMMENT ''执行动作1仅点赞2仅评论3点赞+评论''');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

UPDATE `la_human_anchor` ha
INNER JOIN `la_digital_human_anchor` dha ON dha.id = ha.dh_id AND dha.remark = 'system_default'
SET ha.`status` = 1
WHERE ha.`status` = 9;

UPDATE `la_shanjian_anchor` sa
INNER JOIN `la_digital_human_anchor` dha ON dha.id = sa.dh_id AND dha.remark = 'system_default'
SET sa.`status` = 6
WHERE sa.`status` = 9;

UPDATE `la_dev_crontab` SET `expression` = '*/30 * * * *' WHERE `command` = 'reset_video_synthesis';


-- ============================================================
-- 下级充值业绩清零字段 / 礼包订单索引 / 代理加入时间回填
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_user`') AND COLUMN_NAME = 'recharge_stats_reset_time');
SET @s := IF(@c = 0, 'ALTER TABLE `la_user` ADD COLUMN `recharge_stats_reset_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''下级充值业绩统计清零时间点(0=未清零,该用户的下级业绩仅统计此时间点之后支付的订单)'' AFTER `total_recharge_amount`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_gift_package_order`') AND INDEX_NAME = 'idx_user_pay');
SET @s := IF(@c = 0, 'ALTER TABLE `la_gift_package_order` ADD INDEX `idx_user_pay` (`user_id`, `pay_status`, `pay_time`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

UPDATE `la_distribution_agent`
SET `become_time` = IFNULL(NULLIF(`create_time`, 0), `update_time`)
WHERE `level` > 0
  AND (`become_time` IS NULL OR `become_time` = 0)
  AND (IFNULL(`create_time`, 0) > 0 OR IFNULL(`update_time`, 0) > 0);


-- ============================================================
-- 菜单与权限（幂等）
-- ============================================================
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT page.id, 'A', v.name, '', 0, v.perms, '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` page
INNER JOIN (
    SELECT '添加等级' AS name, 'marketing.agent.grade/addLevel' AS perms UNION ALL
    SELECT '删除等级', 'marketing.agent.grade/delLevel'
) v ON 1 = 1
WHERE page.`type` = 'C' AND page.`component` = 'marketing/agent/grade/index'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms AND m.`type` = 'A');

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT page.id, 'A', '下级充值业绩清零', '', 0, 'user.user/resetRechargeStats', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` page
WHERE page.`type` = 'C' AND page.`perms` = 'user.user/lists'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = 'user.user/resetRechargeStats' AND m.`type` = 'A'
  )
LIMIT 1;
