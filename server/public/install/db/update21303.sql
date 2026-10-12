-- ================ 手动加微任务：加我方式字段 ================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_crawling_manual_task`')
             AND COLUMN_NAME = 'add_friend_type');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_sv_crawling_manual_task` ADD COLUMN `add_friend_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT ''加我方式: 1ai获客 2ai销售'' AFTER `wechat_reg_type`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- 线索来源注释补充来源4（可重复执行）
ALTER TABLE `la_sv_crawling_manual_task`
    MODIFY COLUMN `source` tinyint(4) DEFAULT NULL COMMENT '线索来源1表格导入2获客任务引用3抖音获客任务4客资线索动态获取';

ALTER TABLE `la_sv_dy_search_task`
    MODIFY COLUMN `source` tinyint NOT NULL DEFAULT 1 COMMENT '1手动 2自动';

INSERT INTO `la_auto_task_scene_config` (`scene`, `name`, `allow_add`, `allow_platforms`, `create_time`, `update_time`)
SELECT 18, '抖音获客', 1, CAST('[{"account_type":4,"status":1}]' AS JSON), UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_auto_task_scene_config` WHERE `scene` = 18);

-- 热点库待用：24H「热点生成」取用池，与分析/创作/手动任务台账分离
CREATE TABLE IF NOT EXISTS `la_hotspot_pool` (
                                                 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
    `persona_id` int(11) NOT NULL DEFAULT 0 COMMENT '人设id',
    `platform` varchar(16) NOT NULL DEFAULT '' COMMENT '平台',
    `topic` varchar(120) NOT NULL DEFAULT '' COMMENT '热点标题',
    `analysis_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联分析记录id',
    `creation_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联创作记录id',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '视频标题',
    `script` mediumtext COMMENT '口播正文，空=仅标题',
    `summary` mediumtext COMMENT '背景材料快照',
    `core_points_json` json DEFAULT NULL COMMENT '核心要点快照',
    `analysis_json` json DEFAULT NULL COMMENT '契合分析快照',
    `goal` varchar(16) NOT NULL DEFAULT '' COMMENT '内容目标',
    `direction` varchar(32) NOT NULL DEFAULT '' COMMENT '内容方向',
    `use_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计占用次数',
    `used_time` int(11) NOT NULL DEFAULT 0 COMMENT '最近使用时间',
    `related_video_task_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联闪剪任务id',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_persona_pending` (`user_id`, `persona_id`, `use_count`, `id`),
    KEY `idx_user_persona_topic` (`user_id`, `persona_id`, `platform`, `topic`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='热点库待用';

-- 热点生成取用规则：1最新入库优先 2每条只用一次（按设备）
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`')
             AND COLUMN_NAME = 'hotspot_pick_mode');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `hotspot_pick_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT ''热点取用:1最新入库优先 2每条只用一次'' AFTER `library_reuse_mode`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- 热点库按设备占用日志（模式1同设备可多条，不要 unique）
CREATE TABLE IF NOT EXISTS `la_hotspot_pool_use` (
                                                     `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `pool_id` int(11) NOT NULL DEFAULT 0 COMMENT '热点库id',
    `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
    `persona_id` int(11) NOT NULL DEFAULT 0 COMMENT '人设id',
    `device_code` varchar(64) NOT NULL DEFAULT '' COMMENT '设备号',
    `hotspot_pick_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '占用时取用:1最新入库优先 2每条只用一次',
    `related_video_task_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联闪剪任务id',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '本次占用新生成的视频标题',
    `script` mediumtext NULL COMMENT '本次占用新生成的口播正文',
    `summary` mediumtext NULL COMMENT '本次占用的背景材料',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_device_pool` (`device_code`, `pool_id`),
    KEY `idx_pool` (`pool_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='热点库设备占用';

-- PC/小程序克隆音色下拉读 la_models(type=4,is_enable=1)。数字人s3 必须有 model_version=15 行。
SET @sj_s3_name := '数字人s3';
SET @sj_s3_channel := 's3';
SET @sj_s3_logo := IFNULL((
    SELECT NULLIF(TRIM(`logo`), '') FROM `la_models`
    WHERE `type` = 4 AND `model_version` = 8 AND `delete_time` IS NULL
    ORDER BY `id` ASC
    LIMIT 1
), 'static/images/human/8.png');

INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
SELECT 'human_voice_shanjian_s3', 5043, '算力/次', CONCAT(@sj_s3_name, '音色克隆'), 600.00, CONCAT(@sj_s3_name, '音色克隆，1次/600算力'), 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `la_model_config` WHERE `scene` = 'human_voice_shanjian_s3' LIMIT 1);

INSERT INTO `la_models` (`type`, `channel`, `logo`, `name`, `model_version`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`)
SELECT 4, @sj_s3_channel, @sj_s3_logo, @sj_s3_name, 15, '', '', 0, 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `la_models`
    WHERE `type` = 4 AND `model_version` = 15 AND `delete_time` IS NULL
    LIMIT 1
);

UPDATE `la_models`
SET `name` = @sj_s3_name,
    `channel` = @sj_s3_channel,
    `is_enable` = 1,
    `logo` = IF(NULLIF(TRIM(`logo`), '') IS NULL, @sj_s3_logo, `logo`),
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 4 AND `model_version` = 15 AND `delete_time` IS NULL;

SET @sj_s3_id := (
    SELECT `id` FROM `la_models`
    WHERE `type` = 4 AND `model_version` = 15 AND `delete_time` IS NULL
    LIMIT 1
);

INSERT INTO `la_models_cost` (`model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`)
SELECT @sj_s3_id, 4, @sj_s3_channel, @sj_s3_name, 's3', 0.0000, 0, 1, UNIX_TIMESTAMP()
FROM DUAL
WHERE @sj_s3_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `la_models_cost` WHERE `model_id` = @sj_s3_id AND `alias` = 's3' LIMIT 1);

UPDATE `la_models_cost` `c`
    INNER JOIN `la_models` `m` ON `m`.`id` = `c`.`model_id` AND `m`.`type` = 4 AND `m`.`model_version` = 15 AND `m`.`delete_time` IS NULL
    SET `c`.`type` = 4, `c`.`name` = @sj_s3_name, `c`.`channel` = @sj_s3_channel, `c`.`status` = 1
WHERE @sj_s3_id IS NOT NULL;

UPDATE `la_model_config`
SET `name` = CONCAT(@sj_s3_name, '音色克隆'),
    `description` = CONCAT(@sj_s3_name, '音色克隆，1次/600算力'),
    `update_time` = UNIX_TIMESTAMP()
WHERE `scene` = 'human_voice_shanjian_s3';

-- ================ 素材库广场：分组 / 素材 / 人设引用 ================
CREATE TABLE IF NOT EXISTS `la_square_asset_group` (
                                                       `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `name` varchar(16) NOT NULL DEFAULT '' COMMENT '分组名称',
    `desc` varchar(60) NOT NULL DEFAULT '' COMMENT '备注，仅后台可见',
    `enabled` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1上架 0下架，下架级联隐藏组内素材',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '越小越前',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    `delete_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_enabled_sort` (`enabled`, `sort`, `id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库广场分组';

CREATE TABLE IF NOT EXISTS `la_square_asset` (
                                                 `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '所属分组',
    `kind` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1视频 2图片',
    `name` varchar(24) NOT NULL DEFAULT '' COMMENT '素材名称',
    `url` varchar(1000) NOT NULL DEFAULT '' COMMENT '文件地址',
    `cover_url` varchar(1000) NOT NULL DEFAULT '' COMMENT '封面地址',
    `duration_sec` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '时长秒',
    `tags` json DEFAULT NULL COMMENT '标签最多6个',
    `enabled` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '组内越小越前',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    `delete_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_group_enabled` (`group_id`, `enabled`, `sort`, `id`),
    KEY `idx_kind` (`kind`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库广场素材';

CREATE TABLE IF NOT EXISTS `la_square_asset_ref` (
                                                     `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户id',
    `persona_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '人设id',
    `asset_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '广场素材id',
    `name` varchar(24) NOT NULL DEFAULT '' COMMENT '引用时名称冗余',
    `kind` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '引用时类型冗余',
    `group_name` varchar(16) NOT NULL DEFAULT '' COMMENT '引用时分组名冗余',
    `ref_at` int(11) NOT NULL DEFAULT 0 COMMENT '引用时间',
    `create_time` int(11) DEFAULT NULL,
    `update_time` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_persona_asset` (`persona_id`, `asset_id`),
    KEY `idx_user_persona` (`user_id`, `persona_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库广场人设引用';

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`')
             AND COLUMN_NAME = 'square_group_ids');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `square_group_ids` json DEFAULT NULL COMMENT ''素材库+广场时限定的分组id'' AFTER `visual_material_source`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- 后台菜单：业务管理下挂素材库广场
INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '素材库广场', '', 0, 'square.group/lists', 'square', 'ai_application/square/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu`
         WHERE `type` = 'M' AND (`name` = '业务管理' OR `id` = 195)
         ORDER BY (`name` = '业务管理') DESC, (`id` = 195) DESC, `id` ASC
             LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'square.group/lists'
);

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT page.id, 'A', v.name, '', 0, v.perms, '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` page
         INNER JOIN (
    SELECT 'square.group/add' AS perms, '新建分组' AS name UNION ALL
    SELECT 'square.group/update', '编辑分组' UNION ALL
    SELECT 'square.group/delete', '删除分组' UNION ALL
    SELECT 'square.asset/lists', '素材列表' UNION ALL
    SELECT 'square.asset/add', '添加素材' UNION ALL
    SELECT 'square.asset/update', '编辑素材' UNION ALL
    SELECT 'square.asset/delete', '删除素材'
) v ON 1 = 1
WHERE page.`perms` = 'square.group/lists'
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms
);

-- 创作台账补人设id，避免同名口播互串
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_hotspot_creation`')
             AND COLUMN_NAME = 'persona_id');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_hotspot_creation` ADD COLUMN `persona_id` int(11) NOT NULL DEFAULT 0 COMMENT ''人设id，0=未绑定'' AFTER `persona_name`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_hotspot_creation`')
             AND INDEX_NAME = 'idx_user_persona_topic');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_hotspot_creation` ADD KEY `idx_user_persona_topic` (`user_id`, `persona_id`, `platform`, `topic`)',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- ================ 公共文案库 ================
CREATE TABLE IF NOT EXISTS `la_public_copywriting` (
                                                       `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `title` varchar(30) NOT NULL DEFAULT '' COMMENT '标题',
    `content` mediumtext COMMENT '正文，可含占位符',
    `topic` varchar(200) NOT NULL DEFAULT '' COMMENT '话题，仅发布文案',
    `ui_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1成品文案 2提示词框架',
    `library_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1视频驱动文案 2发布文案',
    `driver_type` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0发布 1新闻体 2口播 3素材混剪口播',
    `industry` varchar(16) NOT NULL DEFAULT '' COMMENT '行业，空=通用',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序，小在前',
    `enabled` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
    `version` int(11) unsigned NOT NULL DEFAULT 1 COMMENT '正文/标题/分类变更才加一',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_type_enabled_sort` (`library_type`, `driver_type`, `enabled`, `sort`, `id`),
    KEY `idx_ui_enabled_sort` (`ui_type`, `enabled`, `sort`, `id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公共文案库';

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_public_copywriting`')
             AND COLUMN_NAME = 'ui_type');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_public_copywriting` ADD COLUMN `ui_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT ''1成品文案 2提示词框架'' AFTER `topic`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_public_copywriting`')
             AND INDEX_NAME = 'idx_ui_enabled_sort');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_public_copywriting` ADD KEY `idx_ui_enabled_sort` (`ui_type`, `enabled`, `sort`, `id`)',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`')
             AND COLUMN_NAME = 'source_id');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `source_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''公共文案id，0=自建'' AFTER `source`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`')
             AND COLUMN_NAME = 'source_version');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `source_version` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''加入时公共文案版本'' AFTER `source_id`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`')
             AND COLUMN_NAME = 'edited');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `edited` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT ''1=客户手改过公共来源文案'' AFTER `source_version`',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

ALTER TABLE `la_ai_persona_copywriting_library`
    MODIFY COLUMN `source` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '来源:1手动新增,2文件导入,3公共文案';

SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`')
             AND INDEX_NAME = 'idx_persona_source');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona_copywriting_library` ADD KEY `idx_persona_source` (`persona_id`, `source_id`)',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '公共文案库', '', 0, 'copywriting.publicCopy/lists', 'publicCopy', 'ai_application/copywriting/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
         SELECT id FROM `la_system_menu`
         WHERE `type` = 'M' AND (`name` = '业务管理' OR `id` = 195)
         ORDER BY (`name` = '业务管理') DESC, (`id` = 195) DESC, `id` ASC
             LIMIT 1
     ) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'copywriting.publicCopy/lists'
);

INSERT INTO `la_system_menu`
(`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT page.id, 'A', v.name, '', 0, v.perms, '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` page
         INNER JOIN (
    SELECT 'copywriting.publicCopy/add' AS perms, '新建文案' AS name UNION ALL
    SELECT 'copywriting.publicCopy/update', '编辑文案' UNION ALL
    SELECT 'copywriting.publicCopy/delete', '删除文案'
) v ON 1 = 1
WHERE page.`perms` = 'copywriting.publicCopy/lists'
  AND NOT EXISTS (
    SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms
);

-- 系统曲库：定时任务从曲库源同步，空曲库时出片仍回退本地静态曲
CREATE TABLE IF NOT EXISTS `la_system_music` (
                                                 `id` varchar(32) NOT NULL COMMENT '曲目id',
    `name` varchar(128) NOT NULL DEFAULT '' COMMENT '歌名',
    `audio_url` varchar(512) NOT NULL DEFAULT '' COMMENT '音频地址',
    `duration_sec` int(11) NOT NULL DEFAULT 0 COMMENT '时长秒',
    `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序，小的在前',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_enabled_sort` (`enabled`, `sort`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统曲库';

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'system_music_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `system_music_id` varchar(32) NOT NULL DEFAULT '''' COMMENT ''指定系统曲目id，空=每次随机'' AFTER `music_source`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'wash_system_music_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task` ADD COLUMN `wash_system_music_id` varchar(32) NOT NULL DEFAULT '''' COMMENT ''洗稿指定系统曲目id，空=随机'' AFTER `wash_music_url`', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '同步系统曲库', 1, 0, '每5分钟同步系统曲库，空结果不覆盖本地曲目', 'sync_system_music', '', 1, '*/5 * * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'sync_system_music' AND `delete_time` IS NULL LIMIT 1
);


-- 一句话创作改用 Seedance 2.5 后，同步扣费场景展示名。
UPDATE `la_model_config` SET `name` = 'AI智能一句话生成视频(seedance2.5-480p-image)', `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'seedance2_480p_image2video_create' AND `name` LIKE '%seedance2.0%';
UPDATE `la_model_config` SET `name` = 'AI智能一句话生成视频(seedance2.5-480p-video)', `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'seedance2_480p_video2video_create' AND `name` LIKE '%seedance2.0%';
UPDATE `la_model_config` SET `name` = 'AI智能一句话生成视频(seedance2.5-720p-image)', `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'seedance2_720p_image2video_create' AND `name` LIKE '%seedance2.0%';
UPDATE `la_model_config` SET `name` = 'AI智能一句话生成视频(seedance2.5-720p-video)', `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'seedance2_720p_video2video_create' AND `name` LIKE '%seedance2.0%';
