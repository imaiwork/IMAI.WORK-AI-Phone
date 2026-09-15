INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ('digital_human', 'video_case_open', '0', 1760780862, 1763370311);

CREATE TABLE IF NOT EXISTS `la_kb_robot_relation` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`robot_id` int(11) DEFAULT '0' COMMENT '智能体id',
`group_id` int(11) DEFAULT '0' COMMENT '智能体分组id',
`type` tinyint(1) DEFAULT '0' COMMENT '0:后台 1:用户',
`is_indexed` tinyint(1) unsigned DEFAULT '0' COMMENT '0:未建立检索 1:建立检索',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户绑定智能体';

UPDATE `la_system_menu` SET `pid` = 195, `type` = 'M', `name` = 'AI矩阵', `icon` = '', `sort` = 0, `perms` = '', `paths` = 'matrix', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1747033899, `update_time` = 1772938419 WHERE `id` = 375;
UPDATE `la_system_menu` SET `pid` = 375, `type` = 'C', `name` = '创作列表', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.creation/lists', `paths` = 'creation', `component` = 'ai_application/matrix/creation/lists', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1747034512, `update_time` = 1772938427 WHERE `id` = 377;
UPDATE `la_system_menu` SET `pid` = 377, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.creation/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1747903514, `update_time` = 1772938432 WHERE `id` = 383;
UPDATE `la_system_menu` SET `pid` = 375, `type` = 'C', `name` = '创作记录', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.creation/record', `paths` = 'record', `component` = 'ai_application/matrix/creation/record', `selected` = '/ai_application/matrix/creation', `params` = '', `is_cache` = 0, `is_show` = 0, `is_disable` = 0, `create_time` = 1747968042, `update_time` = 1772938469 WHERE `id` = 385;
UPDATE `la_system_menu` SET `pid` = 375, `type` = 'C', `name` = '数字人列表', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.digital_human/lists', `paths` = 'digital_human', `component` = 'ai_application/matrix/digital_human/lists', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1752980930, `update_time` = 1772938440 WHERE `id` = 405;
UPDATE `la_system_menu` SET `pid` = 405, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.digital_human/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1752981771, `update_time` = 1772938445 WHERE `id` = 406;
UPDATE `la_system_menu` SET `pid` = 375, `type` = 'C', `name` = '基本设置', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix/setting', `paths` = 'setting', `component` = 'ai_application/matrix/setting/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1752983118, `update_time` = 1772938450 WHERE `id` = 407;
UPDATE `la_system_menu` SET `pid` = 375, `type` = 'C', `name` = '数字人详情', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.digital_human/detail', `paths` = 'dh_detail', `component` = 'ai_application/matrix/digital_human/detail', `selected` = '/ai_application/matrix/digital_human', `params` = '', `is_cache` = 0, `is_show` = 0, `is_disable` = 0, `create_time` = 1752992180, `update_time` = 1772938457 WHERE `id` = 408;
UPDATE `la_system_menu` SET `pid` = 408, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix.dh_detail/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1752992805, `update_time` = 1772938474 WHERE `id` = 409;
UPDATE `la_system_menu` SET `pid` = 407, `type` = 'A', `name` = '保存', `icon` = '', `sort` = 0, `perms` = 'ai_application.matrix/setConfig', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1753241392, `update_time` = 1772938462 WHERE `id` = 416;
