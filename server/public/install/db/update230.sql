ALTER TABLE `la_sv_video_setting`
    ADD COLUMN `ai_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ai创作 0原本';

ALTER TABLE `la_sv_video_task`
    MODIFY COLUMN `audio_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '驱动类型 1：文案驱动 2：音频驱动',
    ADD COLUMN `ai_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ai创作 0原本';

CREATE TABLE IF NOT EXISTS `la_models_setting`
(
    `id`                int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `user_id`           int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
    `model_id`          int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型id',
    `model_sub_id`      int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子模型id',
    `top_p`             decimal(5, 2) unsigned NOT NULL DEFAULT '0.50' COMMENT '多样性范围（0.01到1）词汇多样性',
    `temperature`       decimal(5, 2) unsigned NOT NULL DEFAULT '1.00' COMMENT '温度（创意开关 0到2）结果相似性',
    `presence_penalty`  decimal(5, 2) unsigned NOT NULL DEFAULT '0.20' COMMENT '避免重复力度（存在惩罚 0到1）特定词重复率',
    `frequency_penalty` decimal(5, 2) NOT NULL DEFAULT '0.30' COMMENT '避免重复用词力度（频率惩罚 -2到2）重复词频率',
    `max_tokens`        int(8) unsigned NOT NULL DEFAULT '4096' COMMENT '字数上限（1-4096）',
    `context_num`       tinyint(1) unsigned NOT NULL DEFAULT '3' COMMENT '上下文数量（1-5）',
    `is_default`        tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认: [0=否, 1=是]',
    `create_time`       int(11) unsigned DEFAULT NULL COMMENT '创建时间',
    `update_time`       int(11) unsigned DEFAULT NULL COMMENT '更新时间',
    `delete_time`       int(11) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='模型管理表';

CREATE TABLE IF NOT EXISTS `la_coze_workflow` (
                                                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `coze_agent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'cozeid',
    `inputs` text COMMENT '输入参数数组',
    `outputs` text COMMENT '输出参数数组',
    `ext` text COMMENT '拓展',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='coze工作流表';


CREATE TABLE IF NOT EXISTS `la_coze_agent` (
                                               `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'coze类型1智能体2工作流',
    `bg_image` varchar(255) NOT NULL DEFAULT '' COMMENT '背景图',
    `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
    `name` varchar(128) NOT NULL DEFAULT '' COMMENT '名称',
    `agent_cate_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属分类场景id',
    `permissions` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '权限类型0没有限制',
    `source` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '来源类型0后台,1用户',
    `source_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
    `introduced` text COMMENT '介绍',
    `stream` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '输出类型0直接输出,1流式',
    `deduction` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '扣类型0token,1按次',
    `tokens` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '扣费算力/次，tokens/算力',
    `coze_id` varchar(255) DEFAULT 'deepseek' COMMENT 'cozeid',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COMMENT='coze表';


CREATE TABLE IF NOT EXISTS `la_coze_config` (
                                                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `source` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '来源类型0后台,1用户',
    `source_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
    `secret_token` varchar(255) NOT NULL DEFAULT '' COMMENT 'secret_token',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='coze配置表';

ALTER TABLE `la_kb_robot`
    MODIFY COLUMN `temperature` decimal (5,2) UNSIGNED DEFAULT 1.00 COMMENT '属性温度',
    MODIFY COLUMN `context_num` int (5) DEFAULT 3 COMMENT '上下文数量',
    ADD COLUMN `top_p` decimal (5,2) UNSIGNED DEFAULT 0.50 COMMENT '词汇多样性',
    ADD COLUMN `presence_penalty` decimal (5,2) UNSIGNED DEFAULT 0.20 COMMENT '词汇多样性',
    ADD COLUMN `frequency_penalty` decimal (5,2) DEFAULT 0 COMMENT '重复词频率',
    ADD COLUMN `bg_image` varchar (250) NOT NULL DEFAULT '' COMMENT '机器人背景图';

ALTER TABLE `la_sv_reply_strategy`
    ADD COLUMN `paragraph_enable` tinyint(1) NOT NULL DEFAULT 0 COMMENT '分段回复 0：关 1：开';

ALTER TABLE `la_chat_log`
    ADD COLUMN `extra` text NULL COMMENT '预留扩展字段' AFTER `task_time`,
    ADD COLUMN `robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人的ID',
    ADD COLUMN `channel` tinyint(1) DEFAULT '0' COMMENT '应用渠道 1:个微 2:小红书 3:通用聊天';

DELETE FROM `la_model_config` WHERE `scene` = 'coze_agent_chat';
DELETE FROM `la_model_config` WHERE `scene` = 'coze_workflow';
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'coze_agent_chat', 10100, '算力/次', 'COZE智能体聊天', 1.00, '', 1, NULL, NULL);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('coze_workflow', 10101, '算力/次', 'COZE工作流', 1.00, '', 1, NULL, NULL);


CREATE TABLE IF NOT EXISTS `la_coze_log` (
                                             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `conversation_id` varchar(64) NOT NULL DEFAULT '' COMMENT '对话会话 ID（同 chat_log）',
    `message_id` varchar(64) NOT NULL DEFAULT '' COMMENT '消息id',
    `bot_id` varchar(64) NOT NULL DEFAULT '' COMMENT '机器人 ID',
    `user_id` varchar(64) NOT NULL DEFAULT '' COMMENT '业务用户 ID',
    `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'coze类型1智能体2工作流',
    `role` enum('workflow','user','assistant') NOT NULL DEFAULT 'workflow',
    `content` longtext NOT NULL COMMENT '节点输出摘要或整段结果',
    `status` varchar(16) NOT NULL DEFAULT 'success' COMMENT 'success|failed|skipped|running',
    `token_in` int(10) unsigned NOT NULL DEFAULT '0',
    `token_out` int(10) unsigned NOT NULL DEFAULT '0',
    `token_total` int(10) unsigned NOT NULL DEFAULT '0',
    `extra` json DEFAULT NULL COMMENT '拓展：logid、debug_url、usage 等',
    `create_time` int(10) unsigned DEFAULT NULL,
    `update_time` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='coze日志';

CREATE TABLE IF NOT EXISTS `la_agent_cate` (
                                               `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `pid` int(11) NOT NULL DEFAULT '0' COMMENT '上级id',
    `name` varchar(255) NOT NULL COMMENT '场景名称',
    `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 AI智能体 2 Coze智能体 3 Coze工作流',
    `logo` varchar(255) NOT NULL COMMENT 'logo',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 不可使用 1：正常',
    `description` text COMMENT '描述',
    `sort` int(11) DEFAULT '0' COMMENT '排序  大的在前',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='智能体分类';



ALTER TABLE `la_sv_add_wechat_record`
    MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 0 COMMENT '执行结果1成功2执行中0失败3当前账号存在安全风险，暂停添加4待执行5冷却中，等待后可继续添加' AFTER `action`,
    ADD COLUMN `crawling_task_id` int NULL DEFAULT 0 COMMENT '获客任务id' AFTER `task_id`,
    ADD INDEX(`crawling_task_id`) USING BTREE,
    ADD INDEX(`status`) USING BTREE,
    ADD INDEX(`user_id`) USING BTREE,
    ADD INDEX(`device_code`) USING BTREE,
    ADD INDEX(`channel`) USING BTREE,
    ADD INDEX(`reg_wechat`) USING BTREE;

ALTER TABLE `la_sv_crawling_task`
    ADD COLUMN `exec_add_count` int NULL DEFAULT 0 COMMENT '任务执行加微的总次数' AFTER `wechat_reg_type`,
ADD COLUMN `completed_add_count` int NULL DEFAULT 0 COMMENT '已执行加微信的次数' AFTER `exec_add_count`;

DELETE FROM `la_dev_crontab` WHERE `command` = 'sph_clues_add_wechat';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('视频号获客队列加微信', 1, 0, '', 'sph_clues_add_wechat', '', 1, '* * * * *', '', 1758180182, '0.13', '0.14', 1758180005, 1758180005, NULL);
UPDATE `la_dev_crontab` SET `expression` = '*/5 * * * *' WHERE `command` = 'ai_circle_reply_like';



DELETE FROM `la_system_menu` WHERE `id` IN(188,374, 443,447, 448, 449,450,451,452,453,454,455,456,457,458,459,460);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (443, 427, 'C', '背景音乐', '', 0, 'ai_setting.ai_setting/bg_music', 'bg_music', 'ai_setting/bg_music/lists', '', '', 0, 0, 1, 1755501711, 1756707084);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (374, 373, 'C', '智能体列表', '', 0, 'ai_application.agent/lists', 'lists', 'ai_application/agent/lists/index', '', '', 0, 1, 0, 1747033756, 1757572815);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (188, 184, 'A', '状态', '', 0, 'ai_assistant.model/status', '', '', '', '', 0, 1, 0, 1719908764, 1756777384);

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (447, 182, 'A', '导入', '', 0, 'ai_assistant.category/import', '', '', '', '', 0, 1, 0, 1756777361, 1756777361);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (448, 184, 'A', '导入', '', 0, 'ai_assistant.model/import', '', '', '', '', 0, 1, 0, 1756777371, 1756777397);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (449, 373, 'C', '智能体分类', '', 0, 'ai_application.agent/cate', 'cate', 'ai_application/agent/cate/index', '', '', 0, 1, 0, 1757573083, 1757573083);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (450, 449, 'A', '删除', '', 0, 'ai_application.agent.cate/delete', '', '', '', '', 0, 1, 0, 1757573101, 1757573101);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (451, 449, 'A', '编辑', '', 0, 'ai_application.agent.cate/edit', '', '', '', '', 0, 1, 0, 1757573114, 1757573114);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (452, 449, 'A', '新增', '', 0, 'ai_application.agent.cate/add', '', '', '', '', 0, 1, 0, 1757573127, 1757573127);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (453, 374, 'A', '新增', '', 0, 'ai_application.agent/add', '', '', '', '', 0, 1, 0, 1757573147, 1757573147);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (454, 374, 'A', '编辑', '', 0, 'ai_application.agent/edit', '', '', '', '', 0, 1, 0, 1757573155, 1757573155);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (455, 373, 'C', '对话记录', '', 0, 'ai_application.agent/record', 'record', 'ai_application/agent/record/index', '', '', 0, 1, 0, 1757573225, 1757573225);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (456, 455, 'A', '删除', '', 0, 'ai_application.agent.record/delete', '', '', '', '', 0, 1, 0, 1757573238, 1757573238);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (457, 373, 'C', '基本设置', '', 0, 'ai_application.agent/setting', 'setting', 'ai_application/agent/setting/index', '', '', 0, 1, 0, 1757573276, 1757577533);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (458, 457, 'A', '保存', '', 0, 'ai_application.agent/setConfig', '', '', '', '', 0, 1, 0, 1757573295, 1757642196);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (459, 373, 'C', '智能体新增:编辑', '', 0, 'ai_application.agent/add:edit', 'edit', 'ai_application/agent/lists/edit', '/ai_application/agent/lists', '', 0, 0, 0, 1757577314, 1757580653);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (460, 449, 'A', '状态', '', 0, 'ai_application.agent.cate/status', '', '', '', '', 0, 1, 0, 1758022230, 1758022243);


