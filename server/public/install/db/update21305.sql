-- ============================================================================
-- 人设剪辑原片分组：文件夹表 + 素材/切割任务/上传文件记下所属分组
-- 未分组用 group_id=0，不单独建行。ADD COLUMN 已存在则跳过，可重复执行
-- ============================================================================

CREATE TABLE IF NOT EXISTS `la_ai_persona_material_group` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属用户',
    `persona_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属人设',
    `name` varchar(30) NOT NULL DEFAULT '' COMMENT '分组名称',
    `keep_original` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0自动适配 1原图',
    `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_persona` (`user_id`, `persona_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人设剪辑原片分组';

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material`') AND COLUMN_NAME = 'group_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material` ADD COLUMN `group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''所属分组，0=未分组'' AFTER `persona_id`, ADD KEY `idx_persona_group` (`persona_id`, `group_id`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_slices`') AND COLUMN_NAME = 'group_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_slices` ADD COLUMN `group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''入库时写入的素材分组，0=未分组'' AFTER `persona_id`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_file`') AND COLUMN_NAME = 'group_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_file` ADD COLUMN `group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''人设素材上传所属分组，0=未分组'' AFTER `persona_id`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- 视频驱动文案可带一份对应的发布文案。列已存在则跳过，可重复执行。
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`') AND COLUMN_NAME = 'publish_title');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `publish_title` text COMMENT ''对应的发布标题'' AFTER `topic`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`') AND COLUMN_NAME = 'publish_content');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `publish_content` mediumtext COMMENT ''对应的发布正文'' AFTER `publish_title`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`') AND COLUMN_NAME = 'publish_topic');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `publish_topic` varchar(1000) NOT NULL DEFAULT '''' COMMENT ''对应的发布话题'' AFTER `publish_content`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- 原图和自动适配各用各的分组。已建表补列；只含原图素材的旧分组归到原图，其余留在自动适配。
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material_group`') AND COLUMN_NAME = 'keep_original');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material_group` ADD COLUMN `keep_original` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT ''0自动适配 1原图'' AFTER `name`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

UPDATE `la_ai_persona_material_group` g
SET g.keep_original = 1
WHERE g.delete_time IS NULL
  AND g.keep_original = 0
  AND EXISTS (
      SELECT 1 FROM `la_ai_persona_material` m
      WHERE m.group_id = g.id AND m.keep_original = 1 AND m.delete_time IS NULL
  )
  AND NOT EXISTS (
      SELECT 1 FROM `la_ai_persona_material` m
      WHERE m.group_id = g.id AND m.keep_original = 0 AND m.delete_time IS NULL
  );

UPDATE `la_ai_persona_material` m
INNER JOIN `la_ai_persona_material_group` g ON g.id = m.group_id AND g.delete_time IS NULL
SET m.group_id = 0
WHERE m.delete_time IS NULL
  AND m.group_id > 0
  AND m.keep_original <> g.keep_original;
