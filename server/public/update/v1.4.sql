-- 1.4
-- 更新原菜单
UPDATE `la_system_menu` SET `perms` = 'meeting_minutes.record/del' WHERE `id` = 261;

-- 菜单
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (318, 255, 'C', '应用配置', '', 2, '', 'setting', 'ai_application/meeting_minutes/setting/index', '', '', 0, 1, 0, 1736835838, 1736835844);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (319, 255, 'C', '会议详情', '', 0, 'ai_application.meeting_minutes/detail', 'detail', 'ai_application/meeting_minutes/record/detail', '/ai_application/meeting_minutes/record', '', 0, 0, 0, 1736835931, 1736835931);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (320, 257, 'A', '详情', '', 0, 'meeting_minutes.record/detail', '', '', '', '', 0, 1, 0, 1736837756, 1736837756);

-- 会议纪要
-- 1. 删除已有列
ALTER TABLE `la_audio_info`
DROP COLUMN `text`,
DROP COLUMN `key_word_id`,
DROP COLUMN `audio_id`,
DROP COLUMN `markdown`;

-- 2. 添加新列
ALTER TABLE `la_audio_info`
	ADD `user_id` int NOT NULL DEFAULT 0 COMMENT '用户ID' AFTER id,
	ADD `url` varchar(255) NOT NULL DEFAULT '' COMMENT '音频文件' AFTER user_id,
    ADD `task_type` TINYINT NOT NULL DEFAULT 1 COMMENT '任务类型，1: 离线 2: 实时' AFTER task_id,
	ADD `name` varchar(150) NOT NULL DEFAULT ''COMMENT '转写名称' after task_type,
	ADD `speaker` tinyint NOT NULL DEFAULT 0 COMMENT '说话人分离人数 0: 不开启  1：2人  2：不定人数' after task_type,
	ADD `language` char(20) NOT NULL DEFAULT 'cn' COMMENT '语种' AFTER speaker,
	ADD `translation` char(20) NOT NULL DEFAULT '' COMMENT '翻译语种' AFTER language,
	ADD `response` LONGTEXT NULL COMMENT '响应结果' AFTER translation,
    ADD `text` TEXT NULL COMMENT '用户自定义内容' AFTER response,
    ADD `status` TINYINT DEFAULT 0 NOT NULL COMMENT '转写状态 0: 待处理 1: 录音中, 2: 暂停录音, 3：转写中 4：转成成功 5: 转写失败' AFTER text,
    ADD `remark` VARCHAR(255) NULL COMMENT '失败原因' AFTER status,
	ADD `ws_url` TEXT NULL COMMENT '实时推送链接' AFTER remark,
    ADD `update_time` int NULL COMMENT '更新时间' after create_time;

-- 更新扣费配置
UPDATE `la_model_config` SET `unit` = '算力/分钟', `name` = '会议纪要', `score` = 1 WHERE `id` = 6;

-- 插入系统配置
DELETE FROM `la_config` WHERE `type` = 'meeting' AND `name` = 'config';
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('meeting','config','{"avatars":["static/images/2025021118314434efa6888.png","static/images/2025021118315174f930648.png","static/images/20250211183154b43b89809.png","static/images/2025021118320066d331258.png","static/images/20250211183204802709473.png","static/images/202502111832080a75a8693.png","static/images/20250211183211f28d87828.png","static/images/20250211183214628122906.png","static/images/202502111832180c05d9631.png","static/images/202502111832277eb0a2326.png"],"language":[{"name":"中文","code":"cn","status":"1"},{"name":"英文","code":"en","status":"1"},{"name":"中英文自由说","code":"fspk","status":"1"},{"name":"日文","code":"ja","status":"1"},{"name":"粤语","code":"yue","status":"1"}],"translation":[{"name":"中文","code":"cn","status":"1"},{"name":"英文","code":"en","status":"1"},{"name":"日文","code":"ja","status":"1"}]}',1736817530, 1736817530);