-- 热点追踪：日榜快照 / 创作任务（人设复用 la_ai_persona，不建新表）
-- 注意：早期版本误建过 iw_hotspot_daily_snapshot（错误前缀，模型按 la_ 解析）。
-- 已执行过旧脚本的环境请手动迁移历史快照后删除 iw_ 表：
--   RENAME TABLE `iw_hotspot_daily_snapshot` TO `la_hotspot_daily_snapshot`;（需先确认 la_ 表不存在或为空）

CREATE TABLE IF NOT EXISTS `la_hotspot_daily_snapshot` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `platform` varchar(16) NOT NULL DEFAULT '' COMMENT '平台:douyin/kuaishou/xiaohongshu/weibo',
  `snap_date` date NOT NULL COMMENT '快照日期',
  `topics_json` json NOT NULL COMMENT '归一化后的 HotTopic 数组',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_plat_date` (`platform`,`snap_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='热点追踪-日榜快照';

CREATE TABLE IF NOT EXISTS `la_hotspot_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `record_no` varchar(32) NOT NULL DEFAULT '' COMMENT '对外编号 ANA_+10位大写hex',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `persona_id` int(11) NOT NULL DEFAULT 0 COMMENT '人设id，0=未绑定',
  `topic` varchar(120) NOT NULL DEFAULT '' COMMENT '热点标题',
  `platform` varchar(16) NOT NULL DEFAULT '' COMMENT '平台',
  `persona_json` json DEFAULT NULL COMMENT '人设快照',
  `fit_score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '契合度0-100',
  `fit_reason` varchar(1000) NOT NULL DEFAULT '' COMMENT '评分理由',
  `hooks_json` json DEFAULT NULL COMMENT '切入方式',
  `risks_json` json DEFAULT NULL COMMENT '风险提醒',
  `recommended_goal` varchar(16) NOT NULL DEFAULT '' COMMENT '推荐目标',
  `recommended_direction` varchar(32) NOT NULL DEFAULT '' COMMENT '推荐方向',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_record_no` (`record_no`),
  KEY `idx_user_created` (`user_id`, `create_time`),
  KEY `idx_plat_created` (`platform`, `create_time`),
  KEY `idx_user_persona` (`user_id`, `persona_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='热点追踪-分析记录';

CREATE TABLE IF NOT EXISTS `la_hotspot_creation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `record_no` varchar(32) NOT NULL DEFAULT '' COMMENT '对外编号 CRT_+10位大写hex',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `topic` varchar(120) NOT NULL DEFAULT '' COMMENT '热点标题',
  `platform` varchar(16) NOT NULL DEFAULT '' COMMENT '平台',
  `persona_name` varchar(64) NOT NULL DEFAULT '' COMMENT '人设名称',
  `goal` varchar(16) NOT NULL DEFAULT '' COMMENT '最终目的',
  `direction` varchar(32) NOT NULL DEFAULT '' COMMENT '内容方向',
  `material_mode` varchar(16) NOT NULL DEFAULT '' COMMENT '素材来源',
  `duration_sec` int(11) NOT NULL DEFAULT 0 COMMENT '目标时长秒',
  `video_type` varchar(16) NOT NULL DEFAULT '' COMMENT '视频类型',
  `avatar` varchar(64) NOT NULL DEFAULT '' COMMENT '数字人形象',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '视频标题',
  `script` mediumtext COMMENT '口播正文',
  `word_count` int(11) NOT NULL DEFAULT 0 COMMENT '文案字数',
  `est_duration_sec` int(11) NOT NULL DEFAULT 0 COMMENT '预估口播秒数',
  `hashtags_json` json DEFAULT NULL COMMENT '话题标签',
  `shots_json` json DEFAULT NULL COMMENT '画面建议',
  `task_no` varchar(32) NOT NULL DEFAULT '' COMMENT '关联视频任务号',
  `status` varchar(16) NOT NULL DEFAULT 'script' COMMENT 'script仅文案/video已建视频任务',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_record_no` (`record_no`),
  KEY `idx_user_created` (`user_id`, `create_time`),
  KEY `idx_plat_status` (`platform`, `status`, `create_time`),
  KEY `idx_task_no` (`task_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='热点追踪-创作记录';

CREATE TABLE IF NOT EXISTS `la_hotspot_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `task_no` varchar(32) NOT NULL DEFAULT '' COMMENT '对外任务号 HOT_+12位大写hex',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id,本期默认0',
  `topic` varchar(120) NOT NULL DEFAULT '' COMMENT '热点标题',
  `platform` varchar(16) NOT NULL DEFAULT '' COMMENT '平台',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '视频标题',
  `script` mediumtext COMMENT '口播正文',
  `publish_title` varchar(200) NOT NULL DEFAULT '' COMMENT '发布标题',
  `publish_content` mediumtext COMMENT '发布正文',
  `publish_tag` varchar(500) NOT NULL DEFAULT '' COMMENT '发布话题',
  `persona_json` json DEFAULT NULL COMMENT '人设快照',
  `core_points_json` json DEFAULT NULL COMMENT '核心要点',
  `citations_json` json DEFAULT NULL COMMENT '引用来源',
  `analysis_json` json DEFAULT NULL COMMENT '契合分析',
  `options_json` json DEFAULT NULL COMMENT '高级设置',
  `status` varchar(16) NOT NULL DEFAULT 'running' COMMENT 'running/wait/done/fail',
  `step_status_json` json NOT NULL COMMENT '五步状态',
  `error` varchar(500) NOT NULL DEFAULT '' COMMENT '失败原因',
  `video_url` varchar(2048) NOT NULL DEFAULT '' COMMENT '成片地址',
  `shanjian_video_task_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联闪剪任务id(iw_shanjian_video_task.id)',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_task_no` (`task_no`),
  KEY `idx_user_created` (`user_id`, `create_time`),
  KEY `idx_status` (`status`),
  KEY `idx_shanjian_video_task_id` (`shanjian_video_task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='热点追踪-创作任务';


-- 热点追踪：后台下发视频任务（队列优先，本命令每分钟补偿）
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '热点追踪视频下发', 1, 0, '补偿未入队或队列未消费的热点追踪视频下发', 'hotspot_video_dispatch', '', 1, '* * * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_dev_crontab`
WHERE NOT EXISTS (
    SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'hotspot_video_dispatch' LIMIT 1
)
LIMIT 1;


-- 热点追踪：TikHub 按次扣费场景（方舟按 models_cost 模型价，不走本表）
DELETE FROM `la_model_config` WHERE `scene` IN ('hotspot_insight', 'hotspot_hot_words');
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `status`, `create_time`, `update_time`) VALUES
('hotspot_insight', 15003, '算力/次', '热点追踪话题洞察', 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 热榜/热搜词拉取产品上免费：显式落一行 score=0 且停用，后台可见可控，避免代码回退默认价收费
('hotspot_hot_words', 15002, '算力/次', '热点追踪热搜词拉取', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());



UPDATE `la_model_config` SET `name` = '(自动化)获客本地OCR', `description` = '自动化视频号获客(本地)', `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'automation_ocr_local';
UPDATE `la_model_config` SET `name` = '(自动化)获客视频号OCR', `description` = '自动化视频号获客(百度OCR)', `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 'automation_ocr_img';


-- ================ 24h 视频生成整改（阶段一） ================
-- 新增覆盖率核对定时任务：每日 8:30（凌晨合成窗口结束后、重置窗口 9:00 开始前）
-- 核对应生成设备数与已完成数，覆盖率低于阈值时写告警日志，暴露"设备当天未生成视频"的无声失败
DELETE FROM `la_dev_crontab` WHERE `command` = 'video_synthesis_coverage';

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time` )
VALUES
	( '视频合成覆盖率核对', 1, 0, '', 'video_synthesis_coverage', '', 1, '30 8 * * *', NULL, NULL, '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL );


-- ================ 24h 视频生成整改（阶段二：合成完成状态日期化） ================
-- 完成日期字段：小于当天即视为"今天未完成"，0 点后自然解锁，最终替代 reset_video_synthesis 的每日重置；
-- retry_date：合成重试计数所属日期，非当天视为计数 0（按天惰性清零）。
-- 过渡期代码按"旧布尔锁 AND 日期"交集挑选设备，reset_video_synthesis 暂保留当安全网，观察一周后再停用
ALTER TABLE `la_sv_device`
ADD COLUMN `synthesis_m_date` date NULL DEFAULT NULL COMMENT '社媒视频合成完成日期(小于当天视为未完成)',
ADD COLUMN `synthesis_w_date` date NULL DEFAULT NULL COMMENT '朋友圈视频合成完成日期(小于当天视为未完成)',
ADD COLUMN `retry_date` date NULL DEFAULT NULL COMMENT '合成重试计数所属日期(非当天视为计数0)';

ALTER TABLE `la_sv_device` ADD INDEX `idx_auto_synthesis_m_date` (`auto_type`, `synthesis_m_date`);

-- 回填：当前已锁定(=1)的设备视为今天已完成，保证任意时间部署（含凌晨合成窗口内）都不会当天重复生成
UPDATE `la_sv_device` SET `synthesis_m_date` = CURDATE() WHERE `synthesis_m` = 1;
UPDATE `la_sv_device` SET `synthesis_w_date` = CURDATE() WHERE `synthesis_w` = 1;


-- ============================================================================
-- 后台热点追踪模块菜单(业务管理 → 热点追踪):概览/热榜快照/分析记录/创作记录/视频任务
-- 幂等,可重复执行;动态取 pid,不硬编码菜单 id。
-- perms 与真实路由 controller/action 严格一致(app/adminapi/controller/hotspot/*),
-- 保证 AuthMiddleware 真正拦截,而非只做前端按钮显隐。
-- 注意:全新安装脚本 public/install/db/ 按惯例需同步同批菜单(由安装包维护者处理)。
-- ============================================================================

-- 1) 目录:业务管理 下挂「热点追踪」
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'M', '热点追踪', '', 0, '', 'hotspot', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND (`name` = '业务管理' OR `id` = 195)
    ORDER BY (`name` = '业务管理') DESC, (`id` = 195) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `type` = 'M' AND `name` = '热点追踪'
);

-- 2) 五个页面(C):概览 / 热榜快照 / 分析记录 / 创作记录 / 视频任务
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', v.name, '', v.sort, v.perms, v.paths, v.component, '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND `name` = '热点追踪'
    ORDER BY id ASC
    LIMIT 1
) p
INNER JOIN (
    SELECT '概览' AS name, 50 AS sort, 'hotspot.overview/index' AS perms, 'overview' AS paths, 'ai_application/hotspot/overview/index' AS component UNION ALL
    SELECT '热榜快照', 40, 'hotspot.hot/lists', 'hot', 'ai_application/hotspot/hot/index' UNION ALL
    SELECT '分析记录', 30, 'hotspot.analysis/lists', 'analysis', 'ai_application/hotspot/analysis/index' UNION ALL
    SELECT '创作记录', 20, 'hotspot.creation/lists', 'creation', 'ai_application/hotspot/creation/index' UNION ALL
    SELECT '视频任务', 10, 'hotspot.task/lists', 'task', 'ai_application/hotspot/task/index'
) v ON 1 = 1
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` m WHERE m.`type` = 'C' AND m.`perms` = v.perms
);

-- 3) 按钮(A):挂在各自页面下,perms 与 adminapi 真实路由一致
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT page.id, 'A', v.name, '', 0, v.perms, '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` page
INNER JOIN (
    SELECT 'hotspot.hot/lists' AS page_perms, '历史日期' AS name, 'hotspot.hot/historyDates' AS perms UNION ALL
    SELECT 'hotspot.analysis/lists', '详情', 'hotspot.analysis/detail' UNION ALL
    SELECT 'hotspot.analysis/lists', '删除', 'hotspot.analysis/delete' UNION ALL
    SELECT 'hotspot.creation/lists', '详情', 'hotspot.creation/detail' UNION ALL
    SELECT 'hotspot.creation/lists', '删除', 'hotspot.creation/delete' UNION ALL
    SELECT 'hotspot.task/lists', '详情', 'hotspot.task/detail' UNION ALL
    SELECT 'hotspot.task/lists', '重试', 'hotspot.task/retry' UNION ALL
    SELECT 'hotspot.task/lists', '删除', 'hotspot.task/delete'
) v ON page.`type` = 'C' AND page.`perms` = v.page_perms
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms AND m.`type` = 'A'
);

-- ================================================================
-- 创作记录去重:存量孤儿包装任务清理
-- type5 数字人口播(无包装)开启智剪后派生的 type=2 包装任务(origin_task_id>0)
-- 不再在创作记录单独展示,删除源任务时由 ShanjianVideoTask::deleteDerivedPackaging 级联。
-- 此处一次性清理历史数据:源任务已软删(或不存在)的终态包装行随之软删;
-- 处理中(status 0/1)的包装行保留,等闪剪回调完成算力结算后不受影响。
-- ================================================================
UPDATE `la_shanjian_video_task` p
LEFT JOIN `la_shanjian_video_task` s ON s.`id` = p.`origin_task_id`
SET p.`delete_time` = UNIX_TIMESTAMP(), p.`update_time` = UNIX_TIMESTAMP()
WHERE p.`shanjian_type` = 2
  AND p.`origin_task_id` > 0
  AND p.`delete_time` IS NULL
  AND p.`status` IN (2, 3)
  AND (s.`id` IS NULL OR s.`delete_time` IS NOT NULL);

-- ================================================================
-- 手动爆款复刻：未选人设·洗稿模式（仅 la_video_imitation_task）
-- 全部字段向后兼容，历史数据默认 rewrite_mode=1。
-- ================================================================
ALTER TABLE `la_video_imitation_task`
    ADD COLUMN `rewrite_mode` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '文案模式:1人设复刻2洗稿' AFTER `persona_id`,
    ADD COLUMN `generation_type` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '洗稿视频类型:0未选1数字人口播2素材口播3新闻体' AFTER `rewrite_mode`,
    ADD COLUMN `wash_avatar_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '洗稿所选digital_human_anchor.id' AFTER `generation_type`,
    ADD COLUMN `wash_voice_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '洗稿所选human_voice.id' AFTER `wash_avatar_id`,
    ADD COLUMN `wash_voice_provider` varchar(20) NOT NULL DEFAULT '' COMMENT '洗稿音色渠道:shanjian/minimax' AFTER `wash_voice_id`,
    ADD COLUMN `wash_third_avatar_id` varchar(100) NOT NULL DEFAULT '' COMMENT '确认时保存的中台形象ID快照' AFTER `wash_voice_provider`,
    ADD COLUMN `wash_third_voice_id` varchar(100) NOT NULL DEFAULT '' COMMENT '确认时保存的中台音色ID快照' AFTER `wash_third_avatar_id`,
    ADD COLUMN `generation_config_confirmed` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '洗稿生成配置是否确认:0否1是' AFTER `wash_third_voice_id`,
    ADD COLUMN `rewritten_text_confirmed` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '洗稿文案是否已确认:0否1是' AFTER `generation_config_confirmed`,
    ADD KEY `idx_rewrite_confirm_status` (`rewrite_mode`, `generation_config_confirmed`, `status`);

-- ================================================================
-- 2026-08-22 意向客户/数据看板接口提速
-- /api/display/statistics、intentionStatistics、intentionCustomerLists 均读取 sv_private_message，
-- 该表此前只有主键，按 user_id 查询全表扫描；补 user_id 索引。
-- ================================================================
ALTER TABLE `la_sv_private_message` ADD KEY `idx_user_id` (`user_id`);

-- 热点追踪 AI 找素材改存 SV 素材库：仅加来源，不加 remote_url / persona_id
ALTER TABLE `la_sv_media_material`
  ADD COLUMN `source` varchar(32) NOT NULL DEFAULT '' COMMENT '来源:hotspot热点追踪,video_imitation手动爆款复刻',
  ADD COLUMN `remote_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '来源平台原链,仅热点写入' AFTER `source`,
  ADD KEY `idx_user_source` (`user_id`, `source`);
  


-- 人设合成配置：AI生成文案类型 + 自定义方向
ALTER TABLE `la_ai_persona_synthesis_config` 
  ADD COLUMN `copywriting_generation_type` tinyint NOT NULL DEFAULT 1 COMMENT '文案生成类型:1干货科普2带货种草3观点评论4剧情段子5情感共鸣6揭秘避坑7自定义' AFTER `speech_rate`,
  ADD COLUMN `copywriting_generation_custom` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义文案生成方向,仅类型7使用' AFTER `copywriting_generation_type`;


ALTER TABLE `la_auto_task_scene_config` 
  ADD COLUMN `allow_platforms` json NULL COMMENT '平台开关 [{account_type,status}] status=1开0关' AFTER `allow_add`;

-- 默认全开 status=1；只回填 NULL，不覆盖已保存配置
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":3,"status":1},{"account_type":4,"status":1},{"account_type":5,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 1 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":3,"status":1},{"account_type":4,"status":1},{"account_type":5,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 2 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":3,"status":1},{"account_type":4,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 3 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":1,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 4 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":4,"status":1},{"account_type":3,"status":1},{"account_type":5,"status":1},{"account_type":1,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 5 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":1,"status":1},{"account_type":3,"status":1},{"account_type":4,"status":1},{"account_type":5,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 6 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":1,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 7 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":1,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 8 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":1,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 9 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":4,"status":1},{"account_type":5,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 10 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":3,"status":1},{"account_type":4,"status":1},{"account_type":5,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 11 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":4,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 12 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":4,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 13 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":4,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 14 AND `allow_platforms` IS NULL;
UPDATE `la_auto_task_scene_config` SET `allow_platforms` = CAST('[{"account_type":1,"status":1}]' AS JSON), `update_time` = UNIX_TIMESTAMP() WHERE `scene` = 15 AND `allow_platforms` IS NULL;

-- ============================================================
-- 后台会话列表（adminapi/kb.robot/chatLists）性能优化
-- ============================================================

-- la_user_tokens_log 27万行仅有 PRIMARY 与 idx_team_id，按 task_id 查算力消耗每次全表扫描（单次 200~460ms，冷缓存可达数秒）
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_user_tokens_log`') AND INDEX_NAME = 'idx_task_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_user_tokens_log` ADD INDEX `idx_task_id` (`task_id`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- la_chat_log 除主键外无任何索引：按 task_id 取会话首条记录为全表扫描
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_chat_log`') AND INDEX_NAME = 'idx_task_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_chat_log` ADD INDEX `idx_task_id` (`task_id`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- la_chat_log 按 chat_type 过滤 + update_time 排序的分组查询为全表扫描 + 临时表 + filesort
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_chat_log`') AND INDEX_NAME = 'idx_chat_type_update_time');
SET @s := IF(@c = 0, 'ALTER TABLE `la_chat_log` ADD INDEX `idx_chat_type_update_time` (`chat_type`, `update_time`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- ============================================================
-- 后台数字人视频列表（adminapi/human.video/lists）性能优化
-- ============================================================

-- la_shanjian_video_task 有 8 个索引却唯独缺 task_id：human 列表去重桥接时每行都要全表扫一遍该表
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_shanjian_video_task`') AND INDEX_NAME = 'idx_task_id_type');
SET @s := IF(@c = 0, 'ALTER TABLE `la_shanjian_video_task` ADD INDEX `idx_task_id_type` (`task_id`, `shanjian_type`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- ============================================================
-- 后台 AI 绘图列表（adminapi/hd.hdImage/lists）性能优化
-- ============================================================

-- la_user_tokens_log.source_sn 无索引，导致 (task_id=? OR source_sn=?) 整条放弃 idx_task_id 走全表扫描
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_user_tokens_log`') AND INDEX_NAME = 'idx_source_sn');
SET @s := IF(@c = 0, 'ALTER TABLE `la_user_tokens_log` ADD INDEX `idx_source_sn` (`source_sn`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- la_hd_image 除主键外无索引：按 log_id 取绘图结果为全表扫描
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_hd_image`') AND INDEX_NAME = 'idx_log_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_hd_image` ADD INDEX `idx_log_id` (`log_id`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- la_hd_log 除主键外无索引：列表按 type 过滤 + id 倒序分页
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_hd_log`') AND INDEX_NAME = 'idx_type_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_hd_log` ADD INDEX `idx_type_id` (`type`, `id`)', 'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- ============================================================
-- 后台菜单：隐藏「AI矩阵 > 基本设置」「AI客服」整个分支、「网站设置 > 客户端设置」
-- 前端 router 用 hidden = !is_show 渲染侧边栏，父节点 is_show=0 即隐藏整个分支
-- 按 perms/paths 匹配而非写死 id，兼容各环境菜单 id 不一致
-- ============================================================
UPDATE `la_system_menu` SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP() WHERE `perms` = 'ai_application.matrix/setting' AND `is_show` = 1;
SET @ai_app := (SELECT `id` FROM (SELECT `id` FROM `la_system_menu` WHERE `pid` = 0 AND `paths` = 'ai_application' LIMIT 1) t);
UPDATE `la_system_menu` SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP() WHERE `pid` = @ai_app AND `paths` = 'service' AND `name` = 'AI客服' AND `is_show` = 1;
UPDATE `la_system_menu` SET `is_show` = 0, `update_time` = UNIX_TIMESTAMP() WHERE `perms` = 'setting.web.web_setting/client' AND `is_show` = 1;


ALTER TABLE `la_shanjian_clip_template` 
  ADD COLUMN `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序权重(越大越靠前)' AFTER `auto_type`;