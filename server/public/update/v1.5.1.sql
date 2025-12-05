-- v1.5.1
-- 删除历史菜单
DELETE FROM `la_system_menu` WHERE `id` in (333, 334, 335);

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (333, 35, 'C', '系统更新', '', 0, 'setting.system.system/update', 'update', 'setting/system/update/index', '', '', 0, 1, 0, 1739352197, 1739352212);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (334, 333, 'A', '一键更新', '', 0, 'setting.system.upgrade/upgrade', '', '', '', '', 0, 1, 0, 1739352237, 1739352237);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (335, 282, 'A', '删除', '', 0, 'dh_record.audio/del', '', '', '', '', 0, 1, 0, 1739418106, 1739418106);

-- 更新菜单
UPDATE `la_system_menu` SET `is_show` = 1, `is_disable` = 0 WHERE `id` = 282;
