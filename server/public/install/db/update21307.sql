INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('general_ai_analyze', 10701, '算力/次', '通用AI分析', 20.00, '通用AI分析', 1, 1793954640, 1793954640);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('general_ocr', 10702, '算力/次', '通用OCR识别', 1.00, '通用OCR识别', 1, 1793954640, 1793954640);

-- 手动创作视频封面：记录用户在创建时选择的封面方式
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_video_setting`') AND COLUMN_NAME = 'video_cover_source');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_video_setting` ADD COLUMN `video_cover_source` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''视频封面: 0-未设置, 1-首帧, 2-AI封面, 3-手动上传''', 'DO 0');
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- AI封面异步生成状态：与视频合成解耦，thumb_status=4 时不阻塞合成
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_video_task`') AND COLUMN_NAME = 'cover_status');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_video_task` ADD COLUMN `cover_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''AI封面状态: 0-未生成, 1-生成中, 2-成功, 3-失败''', 'DO 0');
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 文案行绑定的公共音乐分组：0=未绑定，出片时按人设配置/系统随机
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_copywriting_library`') AND COLUMN_NAME = 'music_group_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_copywriting_library` ADD COLUMN `music_group_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''绑定的公共音乐分组ID，0=未绑定'' AFTER `publish_topic`', 'DO 0');
PREPARE copywriting_music_group_stmt FROM @s;
EXECUTE copywriting_music_group_stmt;
DEALLOCATE PREPARE copywriting_music_group_stmt;
