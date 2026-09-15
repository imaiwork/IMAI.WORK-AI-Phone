
ALTER TABLE `la_human_video_task`
    ADD COLUMN `music_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '音乐风格 1:推荐,2:科技,3:悬疑,4:震惊,5:抒情,6:欢乐,7:激情,8:新闻';

ALTER TABLE  `la_sv_video_setting`
    ADD COLUMN `music_type` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '音乐风格,json';

ALTER TABLE `la_sv_video_task`
    ADD COLUMN `music_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '音乐风格 1:推荐,2:科技,3:悬疑,4:震惊,5:抒情,6:欢乐,7:激情,8:新闻';

ALTER TABLE `la_oem`
    ADD COLUMN `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '站点名称';

ALTER TABLE `la_assistants`
    MODIFY COLUMN `scene_id` int(11) NOT NULL DEFAULT 0 COMMENT '场景id';

ALTER TABLE `la_user`
    MODIFY COLUMN `tokens` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '用户剩余token数';

ALTER TABLE `la_user_tokens_log`
    MODIFY COLUMN `change_amount` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '变动数量',
    MODIFY COLUMN `left_tokens` decimal(11,2) NOT NULL DEFAULT '100.00' COMMENT '变动后数量';

ALTER TABLE `la_model_config`
    MODIFY COLUMN `score` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '算力';


UPDATE `la_config` SET   `value` = '{\"channel\":[{\"id\":\"4\",\"name\":\"优秘V5\",\"described\":\"满足多场景运用，助力企业打造沉浸式体验\",\"icon\":\"/static/images/human/4.png\",\"status\":\"1\"},{\"id\":\"6\",\"name\":\"优秘V7\",\"described\":\"高度还原，打造独一无二的虚拟代言人\",\"icon\":\"/static/images/human/6.png\",\"status\":\"1\"},{\"id\":\"7\",\"name\":\"禅境\",\"described\":\"为数字化浪潮高频迭代的内容营销提供强劲驱动力\",\"icon\":\"/static/images/human/7.png\",\"status\":\"1\"}],\"voice\":[{\"name\":\"智小敏(女)\",\"code\":\"10000\",\"status\":\"1\"},{\"name\":\"智小柔(女)\",\"code\":\"10001\",\"status\":\"1\"},{\"name\":\"智小满(女)\",\"code\":\"10002\",\"status\":\"1\"},{\"name\":\"爱小芊(女)\",\"code\":\"10003\",\"status\":\"1\"},{\"name\":\"爱小静(女)\",\"code\":\"10004\",\"status\":\"1\"},{\"name\":\"千嶂(男)\",\"code\":\"10005\",\"status\":\"1\"},{\"name\":\"智皓(男)\",\"code\":\"10006\",\"status\":\"1\"},{\"name\":\"爱小杭(男)\",\"code\":\"10007\",\"status\":\"1\"},{\"name\":\"爱小辰(男)\",\"code\":\"10008\",\"status\":\"1\"},{\"name\":\"飞镜(男)\",\"code\":\"10009\",\"status\":\"1\"}]}'
                                    WHERE `type` = 'model' AND `name` = 'list' ;

DELETE FROM `la_dev_crontab` WHERE `command` = 'device_rpa_cron';


DELETE FROM `la_system_menu` WHERE `id` IN(427,443, 444, 445, 446, 447, 448);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (427, 0, 'M', 'AI设置', 'el-icon-Reading', 300, '', 'ai_setting', '', '', '', 0, 1, 0, 1754385113, 1754385113);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (443, 427, 'C', '背景音乐', '', 0, 'ai_setting.ai_setting/bg_music', 'bg_music', 'ai_setting/bg_music/lists', '', '', 0, 0, 1, 1755501711, 1756707084);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (444, 443, 'A', '新增', '', 0, 'ai_setting.bg_music/add', '', '', '', '', 0, 1, 0, 1755501728, 1755501728);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (445, 443, 'A', '编辑', '', 0, 'ai_setting.bg_music/edit', '', '', '', '', 0, 1, 0, 1755501738, 1755501738);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (446, 443, 'A', '删除', '', 0, 'ai_setting.bg_music/delete', '', '', '', '', 0, 1, 0, 1755501749, 1755501749);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (447, 182, 'A', '导入', '', 0, 'ai_assistant.category/import', '', '', '', '', 0, 1, 0, 1756777361, 1756777361);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (448, 184, 'A', '导入', '', 0, 'ai_assistant.model/import', '', '', '', '', 0, 1, 0, 1756777371, 1756777397);
