CREATE TABLE  IF NOT EXISTS `la_card_code` (
`id` int NOT NULL AUTO_INCREMENT,
`sn` varchar(32) NOT NULL COMMENT '卡密编号',
`type` tinyint(1) NOT NULL COMMENT '类型：1-会员套餐；2-充值套餐；3-对话次数；4-绘画次数',
`relation_id` int NOT NULL DEFAULT '0' COMMENT '关联套餐（充值、会员套餐）',
`balance` int NOT NULL DEFAULT '0' COMMENT '算力值',
`card_num` int NOT NULL COMMENT '卡密数量',
`valid_start_time` int NOT NULL COMMENT '有效开始时间',
`valid_end_time` int NOT NULL COMMENT '有效结束时间',
`remark` varchar(255) DEFAULT NULL COMMENT '备注',
`rule_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '生成规则：1-批次编号+随机字母；2-批次编号+随机数字；',
`create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
`update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
`delete_time` int unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `sn` (`sn`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4  ROW_FORMAT=DYNAMIC COMMENT='卡密';


CREATE TABLE  IF NOT EXISTS `la_card_code_record` (
`id` int NOT NULL AUTO_INCREMENT,
`sn` varchar(32) NOT NULL COMMENT '卡密编号',
`card_id` int NOT NULL COMMENT '卡密id',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0-未使用，1-已使用',
`user_id` int DEFAULT NULL COMMENT '使用的用户id',
`use_time` int DEFAULT NULL COMMENT '使用时间',
`package_snapshot` text COMMENT '套餐快照',
`create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
`update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
`delete_time` int unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `sn` (`sn`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4  ROW_FORMAT=DYNAMIC COMMENT='卡密兑换记录';


CREATE TABLE  IF NOT EXISTS `la_oss_upload_records` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`file_path` varchar(255) NOT NULL,
`file_hash` varchar(32) NOT NULL,
`oss_type` varchar(50) NOT NULL,
`file_type` varchar(50) NOT NULL,
`create_time` int(11) NOT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Oss迁移记录';

DELETE FROM `la_chat_prompt` WHERE `prompt_name` = '小红书';
INSERT INTO `la_chat_prompt` (`prompt_name`, `prompt_text`) VALUES ('小红书', '你的角色是：【角色设定】 企业背景信息是：【企业背景】\n\n消息回复：\n结合历史信息，当前需要进行回复的内容：【用户发送的内容】');

UPDATE `la_dev_crontab` SET `status` = 1 WHERE `command` = 'query_sv_copywriting_cron';

UPDATE `la_config` SET `value` = '{\"channel\":[{\"id\":\"1\",\"name\":\"标准版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"https://dev.imai.work/uploads/images/20250610/20250610102553701513791.png\",\"status\":\"1\"},{\"id\":\"2\",\"name\":\"极致版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"https://dev.imai.work/uploads/images/20250610/20250610102553701513791.png\",\"status\":\"1\"},{\"id\":\"4\",\"name\":\"高级版\",\"described\":\"满足多场景运用，助力企业打造沉浸式体验\",\"icon\":\"https://dev.imai.work/uploads/images/20250610/20250610102553561ba4080.png\",\"status\":\"1\"},{\"id\":\"6\",\"name\":\"尊享版\",\"described\":\"高度还原，打造独一无二的虚拟代言人\",\"icon\":\"https://dev.imai.work/uploads/images/20250610/202506101025533d7b76306.png\",\"status\":\"1\"}],\"voice\":[{\"name\":\"智小敏(女)\",\"code\":\"10000\",\"status\":\"1\"},{\"name\":\"智小柔(女)\",\"code\":\"10001\",\"status\":\"1\"},{\"name\":\"智小满(女)\",\"code\":\"10002\",\"status\":\"1\"},{\"name\":\"爱小芊(女)\",\"code\":\"10003\",\"status\":\"1\"},{\"name\":\"爱小静(女)\",\"code\":\"10004\",\"status\":\"1\"},{\"name\":\"千嶂(男)\",\"code\":\"10005\",\"status\":\"1\"},{\"name\":\"智皓(男)\",\"code\":\"10006\",\"status\":\"1\"},{\"name\":\"爱小杭(男)\",\"code\":\"10007\",\"status\":\"1\"},{\"name\":\"爱小辰(男)\",\"code\":\"10008\",\"status\":\"1\"},{\"name\":\"飞镜(男)\",\"code\":\"10009\",\"status\":\"1\"}]}', `create_time` = 1730688127, `update_time` = 1749540060 WHERE  `type` = 'model'  and `name` = 'list';


DELETE FROM `la_model_config` WHERE scene = "human_copywriting";
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_copywriting', 5018, 'tokens/算力', '数字人文案', 0, '（数字人）每次生成口播文案，不消耗算力', 1, 1740799252, 1740799252);


INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (387, 166, 'M', '卡密兑换', '', 0, '', 'redeem_code', '', '', '', 0, 1, 0, 1749000136, 1749000136);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (388, 387, 'C', '卡密管理', '', 0, 'cardcode.cardCode/lists', 'lists', 'marketing/redeem_code/lists/index', '', '', 0, 1, 0, 1749000167, 1749000167);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (389, 388, 'A', '添加', '', 0, 'cardcode.cardCode/add', '', '', '', '', 0, 1, 0, 1749000187, 1749000201);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (390, 388, 'A', '删除', '', 0, 'cardcode.cardCode/delete', '', '', '', '', 0, 1, 0, 1749000195, 1749000209);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (391, 387, 'C', '兑换记录', '', 0, 'cardcode.cardCode/record', 'record', 'marketing/redeem_code/record/index', '', '', 0, 1, 0, 1749000380, 1749000474);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (392, 387, 'C', '卡密设置', '', 0, 'cardcode.cardCode/setting', 'setting', 'marketing/redeem_code/setting/index', '', '', 0, 1, 0, 1749000414, 1749000431);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (393, 94, 'C', '小程序配置', '', 0, 'channel.mnp_settings/setting', 'mp_config', 'channel/weapp', '', '', 0, 1, 0, 1749026166, 1749026179);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (394, 94, 'C', '小程序一键上传', '', 0, 'channel.mnp_settings/uploadMnp', 'upload', 'channel/weapp_upload', '', '', 0, 1, 0, 1749026294, 1749026294);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (395, 118, 'A', '新增', '', 0, 'user.user/add', '', '', '', '', 0, 1, 0, 1749190581, 1749190645);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (396, 33, 'A', '上传本地文件', '', 0, 'setting.stroage/upload', '', '', '', '', 0, 1, 0, 1749603094, 1749603131);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (397, 29, 'C', '客户端设置', '', 0, 'setting.web.web_setting/client', 'client', 'setting/website/client', '', '', 0, 1, 0, 1749776990, 1749777017);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (398, 397, 'A', '保存', '', 0, 'setting.web.web_setting/setClient', '', '', '', '', 0, 1, 0, 1749777142, 1749777142);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (399, 195, 'M', 'AI直播', '', 0, '', 'live', '', '', '', 0, 1, 0, 1750062367, 1750062367);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (400, 399, 'C', '基本配置', '', 0, 'ai_application.live/setting', 'setting', 'ai_application/live/setting/index', '', '', 0, 1, 0, 1750062397, 1750062397);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (401, 399, 'C', '价格说明', '', 0, 'ai_application.live/price_explain', 'price_explain', 'ai_application/live/price_explain/index', '', '', 0, 1, 0, 1750062464, 1750062464);

ALTER TABLE `la_user`
DROP INDEX `account`;

ALTER TABLE `la_ll_analysis` ADD `is_draft` tinyint NOT NULL DEFAULT 1 COMMENT '是否草稿，0：否，1：是';
ALTER TABLE `la_user_session` ADD `auth_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '认证key';

UPDATE `la_system_menu` SET `is_show` = 0, `is_disable` = 1,`update_time` = 1750060097 WHERE `id` = 243;
