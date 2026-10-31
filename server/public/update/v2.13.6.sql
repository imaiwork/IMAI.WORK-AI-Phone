-- 设备任务表新增 exec_version 字段：记录任务执行时所用 RPA 版本号
ALTER TABLE `la_sv_device_task`
ADD COLUMN `exec_version` varchar(50) NULL DEFAULT NULL COMMENT '任务执行时所用RPA版本号(NULL=执行前未读取到,前端展示兜底读Redis)' AFTER `device_code`;

-- ============================================================================
-- AI销售加微-线索定时任务（可重复执行）
-- 新增 1 张执行记录表 + 注册 1 条定时任务；表前缀按 IMAI update 脚本约定 la_。
-- 功能说明：
--   定时任务 ai_sales_add_wechat_cron 使用 la_ai_wechat_accept_friend_strategy
--   （is_enable=1）的 wechat_ids 决定执行加微的AI销售账号，对
--   la_sv_add_wechat_record 中 status in (3,4,5) 的线索下发添加好友；
--   已执行过的线索按本表去重，不重复添加。
--   当日配额沿用策略 accept_numbers（但与自动通过好友的配额分开单独统计），
--   执行间隔沿用策略 interval_time。
-- ============================================================================
CREATE TABLE IF NOT EXISTS `la_ai_wechat_add_record` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户id',
    `record_id` int unsigned NOT NULL DEFAULT 0 COMMENT '线索记录id(la_sv_add_wechat_record.id)',
    `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
    `wechat_id` varchar(128) NOT NULL DEFAULT '' COMMENT '执行微信id(la_ai_wechat.wechat_id)',
    `device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '执行设备号',
    `friend_wechat` varchar(255) NOT NULL DEFAULT '' COMMENT '被加微信号(线索reg_wechat)',
    `channel` tinyint DEFAULT NULL COMMENT '线索渠道(同线索表channel)',
    `message` varchar(255) NOT NULL DEFAULT '' COMMENT '验证消息(线索remark,空则默认您好)',
    `status` tinyint NOT NULL DEFAULT 2 COMMENT '执行状态2执行中1成功0失败3账号安全冷却4频繁',
    `result` varchar(500) NOT NULL DEFAULT '' COMMENT '加微结果',
    `exec_time` int DEFAULT NULL COMMENT '下发时间',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `uk_user_record_wechat` (`user_id`, `record_id`, `wechat_id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_wechat_create` (`wechat_id`, `create_time`),
    KEY `idx_record_id` (`record_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'AI销售加微执行记录表';

-- 注册定时任务（每分钟执行，仅注册不覆盖，可重复执行）
SET @cmd := CONVERT('ai_sales_add_wechat_cron' USING utf8mb4) COLLATE utf8mb4_general_ci;
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
SELECT 'AI销售加微线索任务', 1, 0, 'AI销售按自动通过好友策略对线索执行添加微信', @cmd, '', 1, '*/1 * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `la_dev_crontab` WHERE `command` = @cmd AND `delete_time` IS NULL
);


SET @cmd := CONVERT('sv:service-contact-clean' USING utf8mb4) COLLATE utf8mb4_general_ci;
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
SELECT '官方服务号存量清理', 1, 0, '按统一黑名单重放视频号/抖音加微记录，把历史漏放的官方服务号联系方式置无效（明文留痕，日志见 sv_contact_clean 通道）', @cmd, '--execute', 1, '0 3 * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `la_dev_crontab` WHERE `command` = @cmd AND `delete_time` IS NULL
);


-- ============================================================
-- 后台菜单:用户列表「导出」按钮,导出数据做成独立权限
-- 接口:app/adminapi/controller/user/UserController::export(/user.user/export)
-- 幂等,可重复执行;动态取「用户列表」菜单 id 为 pid,不硬编码菜单节点 id
-- 权限赋予:超管默认可用;其他角色需在权限管理中勾选该按钮
-- ============================================================
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '导出', '', 0, 'user.user/export', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'user.user/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'user.user/export'
);


-- 人设素材库「一键清理」功能
-- 后台「IP人设」栏目下新增「人设设置」菜单 + 保存按钮权限（幂等：已存在则跳过）
INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 541, 'C', '人设设置', '', 0, 'aiPersona.personaMaterialSetting/getConfig', 'setting', 'ai_application/device/person/setting/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `la_system_menu` WHERE `component` = 'ai_application/device/person/setting/index') t);
SET @persona_material_setting_id = LAST_INSERT_ID();

-- 菜单已存在时 LAST_INSERT_ID 为 0，改用该菜单自身 id 关联
SET @persona_material_setting_id = IF(@persona_material_setting_id = 0,
    (SELECT `id` FROM `la_system_menu` WHERE `component` = 'ai_application/device/person/setting/index' LIMIT 1),
    @persona_material_setting_id);

INSERT INTO `la_system_menu` (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT @persona_material_setting_id, 'A', '保存', '', 0, 'aiPersona.personaMaterialSetting/setConfig', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `la_system_menu` WHERE `perms` = 'aiPersona.personaMaterialSetting/setConfig' AND `type` = 'A') t);

-- 配置默认值：一键清理开关默认隐藏，阈值默认 3 次（ConfigService 读取未配置时也会回落默认值，此处显式落库便于后台识别）
INSERT INTO `la_config` (`type`, `name`, `value`, `team_id`, `create_time`, `update_time`)
SELECT 'aiPersona', 'material_clean_switch', '0', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `la_config` WHERE `type` = 'aiPersona' AND `name` = 'material_clean_switch') t);

INSERT INTO `la_config` (`type`, `name`, `value`, `team_id`, `create_time`, `update_time`)
SELECT 'aiPersona', 'material_clean_threshold', '3', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM `la_config` WHERE `type` = 'aiPersona' AND `name` = 'material_clean_threshold') t);

-- ============================================================
-- 闪剪剪辑模板 / 公共音乐库：本站显隐与排序
-- 内容仍由模板中心运营，定时任务每 5 分钟同步进本地表；本站新增两个覆盖位：
--   status        本站显隐，1 前台可用 0 隐藏（同步只写内容字段，永不覆盖）
--   sort_override 本站排序，NULL 表示跟随模板中心下发的 sort
-- source_hash 记录远端内容指纹，内容未变则跳过素材下载/探活，降低同步开销。
-- 全部语句按 information_schema 判断后执行，可重复执行。
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_clip_template`') AND COLUMN_NAME = 'status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_clip_template` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''本站显隐:1前台可用 0隐藏(同步不覆盖)'' AFTER `sort`', 'DO 0');
PREPARE clip_status_stmt FROM @s;
EXECUTE clip_status_stmt;
DEALLOCATE PREPARE clip_status_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_clip_template`') AND COLUMN_NAME = 'sort_override');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_clip_template` ADD COLUMN `sort_override` int(11) NULL DEFAULT NULL COMMENT ''本站排序覆盖,NULL=跟随模板中心'' AFTER `status`', 'DO 0');
PREPARE clip_sort_stmt FROM @s;
EXECUTE clip_sort_stmt;
DEALLOCATE PREPARE clip_sort_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_clip_template`') AND COLUMN_NAME = 'source_hash');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_clip_template` ADD COLUMN `source_hash` varchar(32) NOT NULL DEFAULT '''' COMMENT ''远端内容指纹,未变化则跳过素材转存'' AFTER `sort_override`', 'DO 0');
PREPARE clip_hash_stmt FROM @s;
EXECUTE clip_hash_stmt;
DEALLOCATE PREPARE clip_hash_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_clip_template`') AND INDEX_NAME = 'idx_scene_auto_status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_clip_template` ADD KEY `idx_scene_auto_status` (`scene`,`auto_type`,`status`)', 'DO 0');
PREPARE clip_idx_stmt FROM @s;
EXECUTE clip_idx_stmt;
DEALLOCATE PREPARE clip_idx_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_system_music`') AND COLUMN_NAME = 'status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_system_music` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''本站显隐:1前台可用 0隐藏(同步不覆盖)'' AFTER `sort`', 'DO 0');
PREPARE music_status_stmt FROM @s;
EXECUTE music_status_stmt;
DEALLOCATE PREPARE music_status_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_system_music`') AND COLUMN_NAME = 'sort_override');
SET @s := IF(@c = 0, 'ALTER TABLE `la_system_music` ADD COLUMN `sort_override` int(11) NULL DEFAULT NULL COMMENT ''本站排序覆盖,NULL=跟随模板中心'' AFTER `status`', 'DO 0');
PREPARE music_sort_stmt FROM @s;
EXECUTE music_sort_stmt;
DEALLOCATE PREPARE music_sort_stmt;

-- 曲目来源：center=模板中心同步（内容只读，同步维护 enabled/sort），local=站长后台上传（可改名/删除）
-- 同步只管理 center 行，绝不下架或删除本站上传的曲目
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_system_music`') AND COLUMN_NAME = 'source');
SET @s := IF(@c = 0, 'ALTER TABLE `la_system_music` ADD COLUMN `source` varchar(16) NOT NULL DEFAULT ''center'' COMMENT ''来源:center模板中心 local本站上传'' AFTER `enabled`', 'DO 0');
PREPARE music_source_stmt FROM @s;
EXECUTE music_source_stmt;
DEALLOCATE PREPARE music_source_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_system_music`') AND INDEX_NAME = 'idx_status_sort');
SET @s := IF(@c = 0, 'ALTER TABLE `la_system_music` ADD KEY `idx_status_sort` (`status`,`sort`)', 'DO 0');
PREPARE music_idx_stmt FROM @s;
EXECUTE music_idx_stmt;
DEALLOCATE PREPARE music_idx_stmt;

-- 模板中心同步只保留一条定时任务：剪辑模板 + 公共音乐库每轮（5分钟）同步，
-- 玩法模板在命令内部按小时闸门控制（含视频/封面转存，不适合 5 分钟一轮）。
-- 历史的三条独立任务行直接删除，避免指向已移除的命令导致 crontab 报错。
DELETE FROM `la_dev_crontab`
WHERE `command` IN ('sync_shanjian_clip_templates', 'sync_system_music', 'sync_play_video_templates');

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `create_time`, `update_time`)
SELECT '同步模板中心', 1, 0, '每5分钟从模板中心同步剪辑模板与公共音乐库（内容未变不重复转存素材），玩法模板每小时一次', 'sync_template_center', '', 1, '*/5 * * * *', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'sync_template_center' AND `delete_time` IS NULL);

-- 后台菜单：口播混剪 下挂「闪剪模板」「公共音乐库」（模板由模板中心同步，本页只管本站显隐与排序）
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '闪剪模板', '', 0, 'shanjian.shanjianClipTemplate/lists', 'clip_template', 'ai_application/montage/clip_template/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE (`type` = 'M' AND `name` = '口播混剪') OR `id` = 461
    ORDER BY (`name` = '口播混剪') DESC, (`id` = 461) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.shanjianClipTemplate/lists' AND `component` = 'ai_application/montage/clip_template/index'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '显隐控制', '', 0, 'shanjian.shanjianClipTemplate/changeStatus', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.shanjianClipTemplate/lists' AND `component` = 'ai_application/montage/clip_template/index' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.shanjianClipTemplate/changeStatus'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '排序设置', '', 0, 'shanjian.shanjianClipTemplate/updateSort', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.shanjianClipTemplate/lists' AND `component` = 'ai_application/montage/clip_template/index' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.shanjianClipTemplate/updateSort'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '公共音乐库', '', 0, 'shanjian.systemMusic/lists', 'system_music', 'ai_application/montage/system_music/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE (`type` = 'M' AND `name` = '口播混剪') OR `id` = 461
    ORDER BY (`name` = '口播混剪') DESC, (`id` = 461) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '显隐控制', '', 0, 'shanjian.systemMusic/changeStatus', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/changeStatus'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '排序设置', '', 0, 'shanjian.systemMusic/updateSort', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/updateSort'
);

-- 本站上传曲目（source=local）的按钮权限：上传音频 / 保存（新增入库、改名） / 删除
-- 上传单独一个权限：AuthMiddleware 对「未在任何菜单登记的接口」是放行的，
-- 不登记的话，任何登录后台的受限管理员都能往曲库目录传文件
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '上传音频', '', 0, 'shanjian.systemMusic/upload', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/upload'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '保存曲目', '', 0, 'shanjian.systemMusic/save', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/save'
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '删除曲目', '', 0, 'shanjian.systemMusic/delete', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/delete'
);

-- ============================================================================
-- 人设素材库：记录「从素材库分组整组导入」的来源
-- 用于两件事：
--   1) 素材卡上标出该素材来自素材库的哪个分组（source_group_name 为导入时的名称快照，
--      分组改名或删除后标签仍能显示）；
--   2) 同一个分组重复导入时跳过已导入过的素材（按 persona_id + source_group_id +
--      source_material_id 判重，软删行不参与判重，删掉后可重新导入）。
-- 单独点选素材导入不带来源，保持原有「可以重复导入」的行为。
-- 全部语句按 information_schema 判断后执行，可重复执行。
-- ============================================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material`') AND COLUMN_NAME = 'source_group_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material` ADD COLUMN `source_group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''来源素材库分组ID，0=非分组导入'' AFTER `group_id`', 'DO 0');
PREPARE persona_source_group_stmt FROM @s;
EXECUTE persona_source_group_stmt;
DEALLOCATE PREPARE persona_source_group_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material`') AND COLUMN_NAME = 'source_material_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material` ADD COLUMN `source_material_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''来源素材库素材ID，0=非分组导入'' AFTER `source_group_id`', 'DO 0');
PREPARE persona_source_material_stmt FROM @s;
EXECUTE persona_source_material_stmt;
DEALLOCATE PREPARE persona_source_material_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material`') AND COLUMN_NAME = 'source_group_name');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material` ADD COLUMN `source_group_name` varchar(60) NOT NULL DEFAULT '''' COMMENT ''来源素材库分组名称快照，用于素材卡标签'' AFTER `source_material_id`', 'DO 0');
PREPARE persona_source_group_name_stmt FROM @s;
EXECUTE persona_source_group_name_stmt;
DEALLOCATE PREPARE persona_source_group_name_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material`') AND INDEX_NAME = 'idx_source_group');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material` ADD KEY `idx_source_group` (`persona_id`,`source_group_id`,`source_material_id`)', 'DO 0');
PREPARE persona_source_group_idx_stmt FROM @s;
EXECUTE persona_source_group_idx_stmt;
DEALLOCATE PREPARE persona_source_group_idx_stmt;

-- ============================================================================
-- AI 人设文案库：来源枚举补充「公共文案AI仿写」(source=4)
-- 功能说明：
--   1) 公共文案库新增「AI仿写」能力，产物按当前 IP 人设重写后写入人设文案库，
--      记为 source=4，source_id=0，与公共模板无版本关系，因此不参与「有更新」提示，
--      也不会被「更新」按钮覆盖。
--   2) 本次只更新字段注释，无结构变更，可重复执行。
-- 表前缀按约定写 la_。
-- ============================================================================

ALTER TABLE `la_ai_persona_copywriting_library`
    MODIFY COLUMN `source` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '来源:1手动新增,2文件导入,3公共文案,4公共文案AI仿写';

-- ============================================================================
-- 公共音乐库：分组
-- 功能说明：
--   1) 新增分组表，曲目通过 group_id 归组，0 = 无分组（后台列表显示「无」）。
--   2) 预置「热门」「纯音乐」两个分组；存量曲目按名称一次性归组：
--      名称以 c_ 开头的进「纯音乐」，其余进「热门」（匹配受排序规则影响，
--      默认 utf8mb4_general_ci 下大小写不敏感）。
--   3) 只处理 group_id=0 的曲目，站长后续手工调整过的归属不会被重复执行改回去。
--   4) 分组是本站设置：模板中心同步只写曲目内容字段，不会改动 group_id；
--      新同步进来的曲目默认无分组，由站长在后台归组。
--   5) 用户端选曲页据此出「全部 + 各分组」tab，空分组不下发。
-- 所有语句可重复执行。
-- ============================================================================

CREATE TABLE IF NOT EXISTS `la_system_music_group` (
                                                       `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID',
    `name` varchar(32) NOT NULL DEFAULT '' COMMENT '分组名称',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序，小的在前',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公共音乐分组';

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_system_music`') AND COLUMN_NAME = 'group_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_system_music` ADD COLUMN `group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''所属分组ID，0=无分组''', 'DO 0');
PREPARE music_group_col_stmt FROM @s;
EXECUTE music_group_col_stmt;
DEALLOCATE PREPARE music_group_col_stmt;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_system_music`') AND INDEX_NAME = 'idx_group_status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_system_music` ADD KEY `idx_group_status` (`group_id`,`status`,`enabled`)', 'DO 0');
PREPARE music_group_idx_stmt FROM @s;
EXECUTE music_group_idx_stmt;
DEALLOCATE PREPARE music_group_idx_stmt;

INSERT INTO `la_system_music_group` (`name`, `sort`, `create_time`, `update_time`)
SELECT '热门', 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `la_system_music_group` WHERE `name` = '热门');

INSERT INTO `la_system_music_group` (`name`, `sort`, `create_time`, `update_time`)
SELECT '纯音乐', 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `la_system_music_group` WHERE `name` = '纯音乐');

UPDATE `la_system_music`
SET `group_id` = IFNULL((SELECT `id` FROM `la_system_music_group` WHERE `name` = '纯音乐' LIMIT 1), 0)
WHERE `group_id` = 0
  AND `name` LIKE 'c\_%';

UPDATE `la_system_music`
SET `group_id` = IFNULL((SELECT `id` FROM `la_system_music_group` WHERE `name` = '热门' LIMIT 1), 0)
WHERE `group_id` = 0;

-- ----------------------------------------------------------------------------
-- 后台菜单：公共音乐库 由单页改为目录，下挂「列表」「分组」
--   列表沿用原菜单行（id 不变，角色已授权的菜单关系自动继承），
--   分组是新增页面，各按钮权限一并登记，避免受限管理员绕过权限直接调接口。
-- ----------------------------------------------------------------------------

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'M', '公共音乐库', '', 0, '', 'system_music', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu`
         WHERE (`type` = 'M' AND `name` = '口播混剪') OR `id` = 461
         ORDER BY (`name` = '口播混剪') DESC, (`id` = 461) DESC, `id` ASC
             LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `type` = 'M' AND `name` = '公共音乐库' AND `paths` = 'system_music'
);

-- 原「公共音乐库」页面菜单挂到新目录下，改名为「列表」；
-- 它下面的 A 类按钮权限 pid 不变，仍然跟着这个页面走
UPDATE `la_system_menu` c
    JOIN `la_system_menu` p
ON p.`type` = 'M' AND p.`name` = '公共音乐库' AND p.`paths` = 'system_music'
    SET c.`pid` = p.`id`,
        c.`name` = '列表',
        c.`paths` = 'list',
        c.`update_time` = UNIX_TIMESTAMP()
WHERE c.`perms` = 'shanjian.systemMusic/lists'
  AND c.`type` = 'C'
  AND c.`pid` <> p.`id`;

-- 换了父级要给已有角色补上新目录：菜单树从 pid=0 起算，
-- 父级不在角色的授权集合里时，子节点会被整块丢掉——不补的话，
-- 原来能进「公共音乐库」的受限管理员会突然看不到这个菜单。
INSERT INTO `la_system_role_menu` (`role_id`, `menu_id`)
SELECT DISTINCT rm.`role_id`, p.`id`
FROM `la_system_role_menu` rm
         JOIN `la_system_menu` c ON c.`id` = rm.`menu_id` AND c.`perms` = 'shanjian.systemMusic/lists'
         JOIN `la_system_menu` p ON p.`type` = 'M' AND p.`name` = '公共音乐库' AND p.`paths` = 'system_music'
         LEFT JOIN `la_system_role_menu` x ON x.`role_id` = rm.`role_id` AND x.`menu_id` = p.`id`
WHERE x.`role_id` IS NULL;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '分组', '', 0, 'shanjian.systemMusicGroup/lists', 'group', 'ai_application/montage/system_music/group/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu` WHERE `type` = 'M' AND `name` = '公共音乐库' AND `paths` = 'system_music' LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/lists'
);

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '新增与编辑分组', '', 0, 'shanjian.systemMusicGroup/save', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/lists' LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/save'
);

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '删除分组', '', 0, 'shanjian.systemMusicGroup/delete', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/lists' LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/delete'
);

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '分组排序', '', 0, 'shanjian.systemMusicGroup/updateSort', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/lists' LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusicGroup/updateSort'
);

-- 列表页行内改分组：同样要登记，否则未授权管理员可直接调接口调整曲目归属
INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '修改曲目分组', '', 0, 'shanjian.systemMusic/changeGroup', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/lists' LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'shanjian.systemMusic/changeGroup'
);



-- ============================================================
-- 作品数据定时同步频率修复：改为每天一次
-- 根因：cron 表达式 `* * * * *` 每分钟触发 WorkDataSyncCron，
--       虽然有 1 小时缓存节流，但缓存过期/驱逐后会重复取数，
--       导致单个账号一天被定时同步多次。
-- 方案：cron 改为每天凌晨 2 点执行 + 代码层 Redis 每日标记双重保障。
-- ============================================================
UPDATE `la_dev_crontab`
SET `expression` = '0 2 * * *',
    `remark` = '每天凌晨2点执行，每个账号每天只同步一次',
    `update_time` = UNIX_TIMESTAMP()
WHERE `command` = 'work_data_sync' AND `delete_time` IS NULL;

-- ============================================================================
-- 视频剪辑分类型配置：按生成类型（数字人口播 1 / 纯素材混剪 3 / 新闻体混剪 4）
-- 分别存视频条数、背景音乐来源、系统音乐分组、指定曲目与音量。
--   1) 视频条数仅无手机模式消费，手机模式仍按发布时段数出片；
--   2) 背景音乐与音量两种运行模式都按类型生效；
--   3) 未配置时三类继承顶层 music_source / system_music_id / music_volume。
-- 语句按 information_schema 判断后执行，可重复执行。
-- ============================================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'type_config');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `type_config` json DEFAULT NULL COMMENT ''分类型配置:按生成类型存条数/音乐来源/分组/指定曲目/音量'' AFTER `music_volume`', 'DO 0');
PREPARE type_config_stmt FROM @s;
EXECUTE type_config_stmt;
DEALLOCATE PREPARE type_config_stmt;
