CREATE TABLE  IF NOT EXISTS `la_sensitive_word` (
`id` int NOT NULL AUTO_INCREMENT,
`word` text COMMENT '敏感词',
`status` tinyint(1) DEFAULT '1' COMMENT '状态：1-开启，0-关闭',
`create_time` int unsigned DEFAULT '0' COMMENT '创建时间',
`update_time` int unsigned DEFAULT '0' COMMENT '更新时间',
`delete_time` int unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='敏感词库';

DELETE FROM `la_model_config` WHERE `scene` = 'openai_chat';

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (427, 0, 'M', 'AI设置', 'el-icon-Reading', 300, '', 'ai_setting', '', '', '', 0, 1, 0, 1754385113, 1754385113);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (428, 427, 'C', '内容审核', '', 0, 'ai_setting.content.censor/index', 'examine', 'ai_setting/examine/index', '', '', 0, 1, 0, 1754385149, 1754385149);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (429, 427, 'C', '敏感词库', '', 0, 'ai_setting.sensitive.word/lists', 'sensitive', 'ai_setting/sensitive/index', '', '', 0, 1, 0, 1754385176, 1754385176);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (430, 428, 'A', '保存', '', 0, 'ai_setting.content.censor/setConfig', '', '', '', '', 0, 1, 0, 1754385196, 1754385196);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (431, 429, 'A', '新增', '', 0, 'ai_setting.sensitive.word/add', '', '', '', '', 0, 1, 0, 1754385213, 1754385213);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (432, 429, 'A', '删除', '', 0, 'ai_setting.sensitive.word/delete', '', '', '', '', 0, 1, 0, 1754385231, 1754385231);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (433, 429, 'A', '编辑', '', 0, 'ai_setting.sensitive.word/edit', '', '', '', '', 0, 1, 0, 1754385245, 1754385245);
