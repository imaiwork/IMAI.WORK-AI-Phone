-- ============================================================
-- GEO 一键诊断支持用户取消:geo_task.status 新增终态 canceled
-- (仅注释同步,列类型 varchar(20) 不变;canceled 由取消接口写入,
--  cron 只消费 running,终态语义与 success/failed 一致)
-- ============================================================
ALTER TABLE `la_geo_task` MODIFY COLUMN `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|running|success|failed|canceled(用户取消,仅monitor_batch)';
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_lead_scraping_setting`') AND COLUMN_NAME = 'agent_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_sv_lead_scraping_setting` ADD COLUMN `agent_id` int NOT NULL DEFAULT 0 COMMENT ''AI回复智能体ID，固定话术为0'' AFTER `touch_type`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;


-- ============================================================================
-- 修复:OEM 站注册的用户登录主站后购买算力报「微信:PARAM_ERROR-appid和openid不匹配」
-- 根因:la_user_auth 只有 (user_id, terminal) 维度,没有记录 openid 属于哪个 appid。
--      OEM 独立小程序与主站小程序是两个 appid、两个 openid,同一用户同一 terminal
--      只能存一条 → 要么互相覆盖(WechatUserService::bindUserAuth),
--      要么并存两条而支付时按主键顺序取到了 OEM 那条(WeChatPayService)。
--      下单用主站 appid + OEM openid,微信直接判 appid/openid 不匹配。
-- 方案:授权行记录来源 appid,登录按 appid 各存一条,支付按本次下单 appid 取号。
--      历史数据 app_id='' 保持旧行为兜底(支付时匹配不到 appid 才回退),
--      用户下次微信登录/绑定时自动补齐 app_id,不做猜测式回填。
-- ADD COLUMN / ADD INDEX 先查 information_schema，已存在则 DO 0，可重复执行
-- ============================================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_user_auth`') AND COLUMN_NAME = 'app_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_user_auth` ADD COLUMN `app_id` varchar(64) NOT NULL DEFAULT '''' COMMENT ''openid 所属微信应用appid(主站/OEM小程序各一条;空=历史数据未知)'' AFTER `unionid`, ADD INDEX `idx_user_terminal_appid` (`user_id`, `terminal`, `app_id`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ============================================================
-- 手动爆款复刻:记录本次下发实际使用的剪辑模板ID
-- 人设模式跟随 la_ai_persona_synthesis_config.template_config(自动随机/自定义已选);
-- 洗稿(persona_id=0)与取值降级时为全局按 scene 随机的结果。
-- 建列语句与本表其余新增列合并在下文「la_video_imitation_task 本版新增列」一处。
-- ============================================================


-- ============================================================================
-- 闪剪原片链路:素材标记 keep_original + 人设合成配置 material_use_mode
-- ADD COLUMN / ADD INDEX 先查 information_schema，已存在则 DO 0，可重复执行
-- 仓库按安装前缀 la_ 写；执行时按环境真实前缀改表名
-- ============================================================================

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_material`') AND COLUMN_NAME = 'keep_original');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_material` ADD COLUMN `keep_original` tinyint unsigned NOT NULL DEFAULT 0 COMMENT ''1=未转码原片 0=转码完整片或切片'' AFTER `source_type`, ADD KEY `idx_keep_original` (`persona_id`, `keep_original`, `use_status`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'material_use_mode');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `material_use_mode` tinyint unsigned NOT NULL DEFAULT 1 COMMENT ''闪剪素材使用方式:1切片 2原片'' AFTER `visual_material_source`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ============================================================================
-- 人设主表素材模式:与合成配置 material_use_mode 双写,读以人设表为准
-- ADD COLUMN 先查 information_schema,已存在则跳过,可重复执行
-- 仓库按安装前缀 la_ 写;执行时按环境真实前缀改表名
-- ============================================================================

-- 本版给人设表加的五列与一个索引合并成一次 ALTER(避免六次重建表);
-- run_mode 与 cloud_* 三列的用途见下文「人设主表运行模式」「无手机模式云端出片账本」两节。
-- 紧跟其后的回填 UPDATE 依赖 material_use_mode,故建列放在这里而不是那两节。
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona`') AND COLUMN_NAME = 'material_use_mode');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona`
    ADD COLUMN `material_use_mode` tinyint unsigned NOT NULL DEFAULT 1 COMMENT ''素材模式:1自动适配 2原图'',
    ADD COLUMN `run_mode` tinyint unsigned NOT NULL DEFAULT 1 COMMENT ''运行模式:1手机模式 2无手机模式'',
    ADD COLUMN `cloud_synthesis_date` date NULL DEFAULT NULL COMMENT ''无手机模式当天出片完成日期'',
    ADD COLUMN `cloud_retry_count` int unsigned NOT NULL DEFAULT 0 COMMENT ''无手机模式当天合成尝试次数'',
    ADD COLUMN `cloud_retry_date` date NULL DEFAULT NULL COMMENT ''无手机模式尝试计数日期'',
    ADD INDEX `idx_run_mode_cloud_date` (`run_mode`, `status`, `cloud_synthesis_date`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

UPDATE `la_ai_persona` p
INNER JOIN `la_ai_persona_synthesis_config` c ON c.persona_id = p.id AND c.delete_time IS NULL
SET p.material_use_mode = c.material_use_mode
WHERE c.material_use_mode IN (1, 2);


-- ============================================================
-- 截流任务 accounts 由 varchar(500) 加宽为 text
-- 根因:多账号+头像 URL 的 JSON 超过 500 字节后，非严格 sql_mode 静默截断，
--      add() 再 json_decode 得到 null，foreach 直接 500。
-- 已是 text 则跳过，可重复执行。
-- ============================================================
SET @c := (SELECT COUNT(1)  FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_lead_scraping_setting`') AND COLUMN_NAME = 'accounts' AND DATA_TYPE <> 'text');
SET @s := IF(@c > 0, 'ALTER TABLE `la_sv_lead_scraping_setting` MODIFY COLUMN `accounts` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT ''账号集合''', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ───────────────────────────────────────────────
-- 爆款玩法（影视换脸改词/跳舞换脸,P0）:任务表 + 人脸私有桶登记 + 场景价 + cron
-- ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `la_play_video_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` varchar(64) NOT NULL COMMENT '任务号=station_task_id,幂等键',
  `user_id` int(11) NOT NULL DEFAULT 0,
  `play` varchar(10) NOT NULL COMMENT 'clip|face|dance',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '记录名(玩法·模板)',
  `pic` varchar(512) NOT NULL DEFAULT '' COMMENT '封面(模板cover)',
  `template_ref_id` varchar(64) NOT NULL DEFAULT '' COMMENT '外部模板系统id',
  `template_snapshot` text COMMENT '提交时模板快照JSON(展示用)',
  `input` text COMMENT '用户输入JSON(face_file_id/lines/voice/bg等)',
  `mid_task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '生成任务id',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待提交 1生成中 2成功 3失败 4超时',
  `step_label` varchar(32) NOT NULL DEFAULT '' COMMENT '当前步骤文案',
  `video_url` varchar(512) NOT NULL DEFAULT '' COMMENT '成片(平台长期URL)',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT '成片时长(秒)',
  `fail_reason` varchar(255) NOT NULL DEFAULT '',
  `bill_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未扣 1预扣 2已结算 3已退款',
  `tokens_cost` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '预扣算力',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `finish_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL COMMENT '软删',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sn` (`sn`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_status_update` (`status`,`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='爆款玩法任务';

-- 人脸素材登记(私有桶对象归属,签名URL只签发给素材归属用户)
CREATE TABLE IF NOT EXISTS `la_play_video_face` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `object_path` varchar(255) NOT NULL COMMENT 'OSS对象路径',
  `bucket` varchar(64) NOT NULL DEFAULT '' COMMENT '空=站点公共桶(未配私有桶的降级)',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='爆款玩法-人脸素材登记';

-- 场景秒价(算力/秒,按成片时长计费:预估预扣,实际多退少补;后台模型计费配置可调)
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
SELECT 'play_face', 16002, '算力/秒', '爆款玩法-影视换脸改词', 184, '按成片时长计费:预估预扣,实际多退少补,不足3秒按3秒,失败自动退还', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `la_model_config` WHERE `scene` = 'play_face' LIMIT 1);

INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
SELECT 'play_dance', 16003, '算力/秒', '爆款玩法-跳舞换脸', 184, '按成片时长计费:预估预扣,实际多退少补,不足3秒按3秒,失败自动退还', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `la_model_config` WHERE `scene` = 'play_dance' LIMIT 1);

-- 影视引流拼接(P1 接口上线后生效,行先占位)
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
SELECT 'play_splice', 16001, '算力/秒', '爆款玩法-影视引流拼接', 0.11, '按成片时长计费:预估预扣,实际多退少补,失败自动退还', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `la_model_config` WHERE `scene` = 'play_splice' LIMIT 1);

-- cron 兜底(回调为主,本命令每分钟补偿同步 + 35min 超时判死退款)
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '爆款玩法任务同步', 1, 0, '爆款玩法任务状态兜底同步与超时退款', 'play_video_task', '', 1, '* * * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_dev_crontab`
WHERE NOT EXISTS (
    SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'play_video_task' LIMIT 1
)
LIMIT 1;

-- ============================================================
-- 手动爆款复刻·洗稿模式:开放音乐音量与口播语速给用户自选
-- 洗稿无人设,无法跟随 ai_persona_synthesis_config,故存到任务自身
-- -1 = 用户未设置,回落原硬编码默认(音量 0.5 / 新闻体 0.6,语速不下发)
-- 注意:0 是合法音量(等于关闭 BGM),不能用 0 当哨兵
-- 建列语句见下文「la_video_imitation_task 本版新增列」。
-- ============================================================

-- 后台菜单:创作管理 下挂「爆款玩法」创作记录页
-- 幂等,可重复执行;动态取 pid,不硬编码菜单 id。
-- perms 与真实路由 controller/action 严格一致(app/adminapi/controller/playVideo/*)。
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '爆款玩法', '', 0, 'playVideo.playVideoTask/lists', 'play_video', 'ai_application/montage/create/play_video/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE (`perms` = 'ai_application.montage/create_reocrd' OR `id` = 463)
    ORDER BY (`perms` = 'ai_application.montage/create_reocrd') DESC, (`id` = 463) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'playVideo.playVideoTask/lists'
);

-- 玩法模板本地表:定时任务(sync_shanjian_clip_templates,每小时)从模板中心同步,
-- 前端模板墙与生成链读本地数据,素材已转存本站存储
CREATE TABLE IF NOT EXISTS `la_play_video_template` (
  `id` varchar(64) NOT NULL COMMENT '模板中心 ref_id',
  `play` varchar(10) NOT NULL COMMENT 'clip|face|dance',
  `name` varchar(100) NOT NULL DEFAULT '',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类(拼接玩法前端筛选用)',
  `heat` varchar(20) NOT NULL DEFAULT '' COMMENT '热度角标',
  `duration_sec` int(11) NOT NULL DEFAULT 0 COMMENT '素材时长(秒)',
  `video_url` varchar(512) NOT NULL DEFAULT '' COMMENT '模板视频(已转存本站,相对路径)',
  `cover_url` varchar(512) NOT NULL DEFAULT '' COMMENT '封面(已转存本站,相对路径)',
  `face_count` tinyint(1) NOT NULL DEFAULT 0,
  `allow_bg_replace` tinyint(1) NOT NULL DEFAULT 0,
  `script` text COMMENT '台本JSON [{role,name,text}]',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '本地显隐:1前台显示 0隐藏(同步不覆盖)',
  `sort` int(11) NOT NULL DEFAULT 0,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_play_sort` (`play`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='玩法模板(模板中心同步)';

-- 后台菜单:口播混剪 下挂「爆款模板」只读列表(模板由模板中心同步,本页核对生效情况)
-- 幂等,动态取 pid;perms 与真实路由 controller/action 一致。
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', '爆款模板', '', 0, 'playVideo.playVideoTemplate/lists', 'play_template', 'ai_application/montage/play_template/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE (`type` = 'M' AND `name` = '口播混剪') OR `id` = 461
    ORDER BY (`name` = '口播混剪') DESC, (`id` = 461) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'playVideo.playVideoTemplate/lists'
);

-- 「爆款模板」菜单补充按钮:本地显隐开关权限
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'A', '显隐控制', '', 0, 'playVideo.playVideoTemplate/changeStatus', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu` WHERE `perms` = 'playVideo.playVideoTemplate/lists' LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `perms` = 'playVideo.playVideoTemplate/changeStatus'
);

-- 「选择视频类型」页示例封面/视频按数组下标与卡片一一对应;爆款玩法复刻卡插在第3位,
-- 配置数组同位插入占位项(素材留空,前端回落默认底图;后台数字人设置页可直接上传素材)
UPDATE `la_config`
SET `value` = JSON_ARRAY_INSERT(`value`, '$[2]', JSON_OBJECT('id', '7', 'name', '爆款玩法复刻', 'image', '', 'video_case_url', ''))
WHERE `type` = 'digital_human' AND `name` = 'video_case' AND `value` NOT LIKE '%爆款玩法复刻%';

-- 爆款玩法复刻卡默认示例素材路径(资源文件由运营上传:封面进仓库 public/static/images/dh/,
-- 视频放各环境服务器 public/static/videos/dh/);仅在该位为爆款玩法复刻且素材为空时填充
UPDATE `la_config`
SET `value` = JSON_SET(`value`, '$[2].image', 'static/images/dh/dh8.jpg', '$[2].video_case_url', 'static/videos/dh/dh8.mp4')
WHERE `type` = 'digital_human' AND `name` = 'video_case'
  AND JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[2].name')) = '爆款玩法复刻'
  AND JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[2].image')) = '';


-- 作品数据报表：整次取数一口价（缓存命中不扣）
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
SELECT 'work_data_fetch', 17001, '算力/次', '作品数据报表', 50, '作品数据报表每次拉取消耗50算力，缓存命中不扣费', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_model_config` WHERE `scene` = 'work_data_fetch');



-- ============================================================================
-- 默认对话模型改为 glm-5.3：启用、设为默认；非空白名单补上其主模型 id
-- 本地尚无该计费行时整段无效果，等模型同步后再跑或由同步服务保证
-- ============================================================================

UPDATE `la_models_cost`
SET `status` = 1
WHERE `type` = 1 AND `alias` = 'glm-5.3' AND `status` = 0;

UPDATE `la_models` m
    INNER JOIN `la_models_cost` c ON c.model_id = m.id AND c.type = 1 AND c.alias = 'glm-5.3'
    SET m.is_enable = 1;

UPDATE `la_models` SET `is_default` = 0
WHERE `type` = 1 AND `is_default` = 1
  AND EXISTS (SELECT 1 FROM `la_models_cost` WHERE `type` = 1 AND `alias` = 'glm-5.3');

UPDATE `la_models` m
    INNER JOIN `la_models_cost` c ON c.model_id = m.id AND c.type = 1 AND c.alias = 'glm-5.3'
    SET m.is_default = 1;

SET @glm_mid := (SELECT `model_id` FROM `la_models_cost` WHERE `type` = 1 AND `alias` = 'glm-5.3' LIMIT 1);

UPDATE `la_user_level`
SET `allowed_models` = JSON_ARRAY_APPEND(`allowed_models`, '$', CAST(@glm_mid AS UNSIGNED))
WHERE @glm_mid IS NOT NULL
  AND `delete_time` IS NULL
  AND `allowed_models` IS NOT NULL
  AND `allowed_models` != ''
  AND `allowed_models` != '[]'
  AND JSON_VALID(`allowed_models`)
  AND JSON_TYPE(`allowed_models`) = 'ARRAY'
  AND NOT JSON_CONTAINS(`allowed_models`, CAST(CAST(@glm_mid AS UNSIGNED) AS JSON), '$');

UPDATE `la_user_level`
SET `allowed_models` = JSON_SET(`allowed_models`, CONCAT('$.', CAST(@glm_mid AS UNSIGNED)), '')
WHERE @glm_mid IS NOT NULL
  AND `delete_time` IS NULL
  AND `allowed_models` IS NOT NULL
  AND `allowed_models` != ''
  AND `allowed_models` != '{}'
  AND JSON_VALID(`allowed_models`)
  AND JSON_TYPE(`allowed_models`) = 'OBJECT'
  AND JSON_EXTRACT(`allowed_models`, CONCAT('$.', CAST(@glm_mid AS UNSIGNED))) IS NULL;

-- ============================================================
-- 用户资料新增:所在门店、资质证书图片(小程序完善资料弹窗填写,后台用户详情展示/编辑)
-- la_user 为历史表,ADD COLUMN 先查 information_schema,已存在则 DO 0,可重复执行
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_user`') AND COLUMN_NAME = 'store_name');
SET @s := IF(@c = 0, 'ALTER TABLE `la_user` ADD COLUMN `store_name` varchar(100) NOT NULL DEFAULT '''' COMMENT ''所在门店'' AFTER `sex`, ADD COLUMN `certificate_image` varchar(255) NOT NULL DEFAULT '''' COMMENT ''资质证书图片'' AFTER `store_name`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ============================================================================
-- 人设主表运行模式:1手机模式 2无手机模式,默认1保持现有行为
-- ADD COLUMN 先查 information_schema,已存在则跳过,可重复执行
-- 建列语句与本表其余新增列合并在上文「人设主表素材模式」一处。
-- ============================================================================

-- ============================================================================
-- 无手机模式云端出片账本:人设表三列,对应设备表 synthesis_m_date / synthesis_m_retry_count / retry_date
-- 建列语句同上,合并在「人设主表素材模式」一处。
-- ============================================================================

-- ============================================================================
-- 视频剪辑配置新增每日出片条数:0=未配置,仅无手机模式消费,手机模式仍按发布时段数出片
-- ADD COLUMN 先查 information_schema,已存在则跳过,可重复执行
-- ============================================================================

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona_synthesis_config`') AND COLUMN_NAME = 'daily_video_count');
SET @s := IF(@c = 0, 'ALTER TABLE `la_ai_persona_synthesis_config` ADD COLUMN `daily_video_count` tinyint unsigned NOT NULL DEFAULT 0 COMMENT ''每日出片条数:0未配置,仅无手机模式使用'' AFTER `material_use_mode`', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ============================================================
-- 闪剪多 key 池:存量形象/音色上报中台(资产级 key 绑定)
-- console.php 只注册命令,真正调度靠本行;不入库则升级站永远不追平、
-- 训练请求也不带 X-Shanjian-Assets-Synced,中台恒走默认 key。
-- 每 5 分钟一轮(单轮最多 20 批 x 50 条,Redis 游标增量,追平后空跑置位)。
-- INSERT 幂等,可重复执行。无新建表(资产仍在既有形象/音色表)。
-- ============================================================
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '闪剪资产上报', 1, 0, '存量形象/音色上报中台建立资产级key绑定', 'shanjian_asset_sync', '', 1, '*/5 * * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'shanjian_asset_sync' LIMIT 1);

-- ============================================================================
-- 历史数据订正:人设自动开通的智能体归属修正
--
-- 后台代客管理人设时,自动开通的四个场景智能体被建成了 user_id=0 的站点预置资源,
-- 而站点预置资源对全站所有用户可见,这些智能体的提示词又来自该客户的人设报告。
-- 代码侧已改为按人设所属用户建号,此处把存量归还给对应用户。
--
-- 只订正同时满足三个条件的记录,尽量避免误伤站长手工建的站点预置智能体:
--   1) user_id = 0
--   2) quota_exempt = 1(自动开通时写入,不占用户智能体数量配额)
--   3) 正在被某个人设的智能体配置引用
-- 只改 user_id,team_id 保持 0(归个人空间,后续按该用户所在企业空间共享)。
-- 语句只命中 user_id = 0 的行,可重复执行。
--
-- 执行前建议先跑一遍下面的清单人工确认,顺便得到受影响的人设与用户数:
--   SELECT r.id, r.name, r.create_time, c.persona_id, c.user_id
--     FROM `la_kb_robot` r
--     JOIN `la_ai_persona_agent_config` c
--       ON r.id IN (c.comment_agent_id, c.dm_agent_id, c.wechat_chat_agent_id, c.moments_agent_id)
--    WHERE r.user_id = 0 AND r.quota_exempt = 1 AND r.delete_time IS NULL;
-- ============================================================================

-- 四个场景列一条语句带过,口径与上面的核对 SELECT 完全一致
UPDATE `la_kb_robot` r
  JOIN `la_ai_persona_agent_config` c
    ON r.`id` IN (c.`comment_agent_id`, c.`dm_agent_id`, c.`wechat_chat_agent_id`, c.`moments_agent_id`)
   SET r.`user_id` = c.`user_id`, r.`update_time` = UNIX_TIMESTAMP()
 WHERE r.`user_id` = 0 AND r.`quota_exempt` = 1 AND r.`delete_time` IS NULL AND c.`user_id` > 0;

-- 回复策略跟随智能体归属,一并订正
UPDATE `la_sv_reply_strategy` s
  JOIN `la_kb_robot` r ON r.`id` = s.`robot_id`
   SET s.`user_id` = r.`user_id`
 WHERE s.`user_id` = 0 AND r.`user_id` > 0 AND r.`quota_exempt` = 1;

-- ============================================================================
-- 智能体来源标记:区分用户手工创建与人设自动开通
--
-- 智能体列表原本把两者平铺在一起,一个人设开通四个场景智能体,人设一多列表就被淹没。
-- 加两个字段后,列表可只展示手工创建的,人设自动开通的收进折叠区按人设归拢。
-- 注意 origin 与列表接口既有的 source 是两码事:source 由 user_id 派生,
-- 区分站点预置与用户资源,会员等级门禁依赖它,不要混用。
-- ADD COLUMN / ADD INDEX 先查 information_schema,已存在则跳过,可重复执行
-- ============================================================================

SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_kb_robot`') AND COLUMN_NAME = 'persona_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_kb_robot` ADD COLUMN `persona_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''所属人设id:0独立智能体'', ADD COLUMN `origin` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT ''来源:1手工创建 2人设自动开通'', ADD INDEX `idx_persona_origin` (`persona_id`, `origin`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ----------------------------------------------------------------------------
-- 存量回填:只认「正在被某个人设的智能体配置引用」的记录,归属确定,可重复执行。
-- 未被引用的历史遗留(重复生成报告留下的旧智能体)不在此处理,它们归属已不可考,
-- 保持 origin=1 停留在列表里,不做自动清理,避免误删用户资产。如需人工排查:
--   SELECT id, name, user_id, quota_exempt, create_time FROM `la_kb_robot`
--    WHERE persona_id = 0 AND delete_time IS NULL
--      AND (name LIKE '个人IP - %' OR name LIKE '企业IP - %' OR name LIKE '本地IP - %');
-- 这里不加 quota_exempt = 1:实测存在配额标记为 0 的历史遗留,加上会漏报。
-- ----------------------------------------------------------------------------

UPDATE `la_kb_robot` r
  JOIN `la_ai_persona_agent_config` c
    ON r.`id` IN (c.`comment_agent_id`, c.`dm_agent_id`, c.`moments_agent_id`, c.`wechat_chat_agent_id`)
   SET r.`persona_id` = c.`persona_id`, r.`origin` = 2
 WHERE c.`persona_id` > 0 AND r.`delete_time` IS NULL AND (r.`persona_id` <> c.`persona_id` OR r.`origin` <> 2);

-- ----------------------------------------------------------------------------
-- 存量改名:旧名只有人设类型没有人设名,同类型多个人设的卡片完全同名无法分辨。
-- 只改仍是自动生成原名的记录,用户改过名的不动;人设名为空的跳过。可重复执行。
-- ----------------------------------------------------------------------------

UPDATE `la_kb_robot` r
  JOIN `la_ai_persona_agent_config` c ON r.`id` = c.`comment_agent_id`
  JOIN `la_ai_persona` p ON p.`id` = c.`persona_id`
   SET r.`name` = CONCAT(LEFT(p.`persona_name`, 60), ' · 评论区')
 WHERE r.`delete_time` IS NULL AND p.`persona_name` <> ''
   AND r.`name` IN ('个人IP - 社媒评论区智能体', '企业IP - 社媒评论区智能体', '本地IP - 社媒评论区智能体');

UPDATE `la_kb_robot` r
  JOIN `la_ai_persona_agent_config` c ON r.`id` = c.`dm_agent_id`
  JOIN `la_ai_persona` p ON p.`id` = c.`persona_id`
   SET r.`name` = CONCAT(LEFT(p.`persona_name`, 60), ' · 私信')
 WHERE r.`delete_time` IS NULL AND p.`persona_name` <> ''
   AND r.`name` IN ('个人IP - 社媒私信智能体', '企业IP - 社媒私信智能体', '本地IP - 社媒私信智能体');

UPDATE `la_kb_robot` r
  JOIN `la_ai_persona_agent_config` c ON r.`id` = c.`moments_agent_id`
  JOIN `la_ai_persona` p ON p.`id` = c.`persona_id`
   SET r.`name` = CONCAT(LEFT(p.`persona_name`, 60), ' · 朋友圈')
 WHERE r.`delete_time` IS NULL AND p.`persona_name` <> ''
   AND r.`name` IN ('个人IP - 微信朋友圈互动智能体', '企业IP - 微信朋友圈互动智能体', '本地IP - 微信朋友圈互动智能体');

UPDATE `la_kb_robot` r
  JOIN `la_ai_persona_agent_config` c ON r.`id` = c.`wechat_chat_agent_id`
  JOIN `la_ai_persona` p ON p.`id` = c.`persona_id`
   SET r.`name` = CONCAT(LEFT(p.`persona_name`, 60), ' · 微信私聊')
 WHERE r.`delete_time` IS NULL AND p.`persona_name` <> ''
   AND r.`name` IN ('个人IP - 微信私聊智能体', '企业IP - 微信私聊智能体', '本地IP - 微信私聊智能体');



-- 抖音获客
-- 仓库按安装前缀 la_ 写；执行时按环境真实前缀改表名

CREATE TABLE IF NOT EXISTS `la_sv_dy_search_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0 COMMENT '用户id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `device_codes` text COMMENT '设备号JSON数组',
  `search_mode` varchar(16) NOT NULL DEFAULT 'keyword' COMMENT 'keyword关键词 account指定账号',
  `keywords` text COMMENT '线索词或账号名单JSON数组',
  `per_kw` int unsigned NOT NULL DEFAULT 30 COMMENT '每词最多取账号数,0不限',
  `scan_config` json DEFAULT NULL COMMENT '扫描与识别配置',
  `exclude_default_id` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '排除默认抖音号,恒为1',
  `add_type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '自动加好友 0关 1开',
  `wechat_id` varchar(255) NOT NULL DEFAULT '' COMMENT '加微客服微信id,逗号分隔',
  `add_rule` varchar(20) NOT NULL DEFAULT 'all' COMMENT 'all全部 phone仅手机 wechat仅微信 phone_first手机优先',
  `add_number` smallint unsigned NOT NULL DEFAULT 15 COMMENT '加好友次数',
  `add_interval_time` smallint unsigned NOT NULL DEFAULT 10 COMMENT '加好友间隔分钟',
  `add_remark_enable` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否启用加好友备注',
  `remarks` text COMMENT '加好友备注话术JSON数组',
  `task_exec_type` tinyint NOT NULL DEFAULT 0 COMMENT '0定时 1即时',
  `minutes` int NOT NULL DEFAULT 0 COMMENT '即时执行时长分钟',
  `task_frep` varchar(32) NOT NULL DEFAULT '' COMMENT '任务频率天数',
  `time_config` text COMMENT '每日执行时间区间JSON',
  `custom_date` text COMMENT '自定义日期JSON',
  `start_time` int NOT NULL DEFAULT 0 COMMENT '开始时间戳',
  `end_time` int NOT NULL DEFAULT 0 COMMENT '结束时间戳',
  `day_cap` int unsigned NOT NULL DEFAULT 50 COMMENT '单日新增客资上限,0不限',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0未开始 1进行中 2暂停 3已完成 4已结束',
  `exec_count` int unsigned NOT NULL DEFAULT 0 COMMENT '已走访账号数',
  `clue_count` int unsigned NOT NULL DEFAULT 0 COMMENT '已入库客资数',
  `ocr_type` tinyint NOT NULL DEFAULT 1 COMMENT '1云端识别 2本地识别',
  `source` tinyint NOT NULL DEFAULT 1 COMMENT '1手动',
  `persona_id` int NOT NULL DEFAULT 0 COMMENT '人设id',
  `remark` varchar(255) DEFAULT NULL COMMENT '任务备注',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '更新时间',
  `delete_time` int DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抖音获客任务';

CREATE TABLE IF NOT EXISTS `la_sv_dy_search_task_device` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0 COMMENT '用户id',
  `task_id` int NOT NULL DEFAULT 0 COMMENT '获客任务id',
  `device_code` varchar(255) NOT NULL DEFAULT '' COMMENT '设备号',
  `keywords` text COMMENT '分配给该设备的线索JSON数组',
  `exec_keyword` varchar(255) NOT NULL DEFAULT '' COMMENT '当前执行线索',
  `exec_count` int unsigned NOT NULL DEFAULT 0 COMMENT '本设备已走访账号数',
  `clue_count` int unsigned NOT NULL DEFAULT 0 COMMENT '本设备已入库客资数',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0待执行 1执行中 2暂停 3完成',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '更新时间',
  `delete_time` int DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_device_code` (`device_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抖音获客任务设备绑定';

CREATE TABLE IF NOT EXISTS `la_sv_dy_search_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0 COMMENT '用户id',
  `task_id` int NOT NULL DEFAULT 0 COMMENT '获客任务id',
  `bind_id` int NOT NULL DEFAULT 0 COMMENT '设备绑定id',
  `device_code` varchar(255) NOT NULL DEFAULT '' COMMENT '设备号',
  `exec_keyword` varchar(255) NOT NULL DEFAULT '' COMMENT '执行线索',
  `dy_uid` varchar(64) NOT NULL DEFAULT '' COMMENT '抖音用户唯一标识',
  `dy_id` varchar(64) NOT NULL DEFAULT '' COMMENT '抖音号',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(512) NOT NULL DEFAULT '' COMMENT '头像',
  `fans` int unsigned NOT NULL DEFAULT 0 COMMENT '粉丝数',
  `works` int unsigned NOT NULL DEFAULT 0 COMMENT '作品数',
  `is_enterprise` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '1企业号或蓝V',
  `scan_source` varchar(32) NOT NULL DEFAULT '' COMMENT '命中位置 bio/dyid/cover,多个逗号分隔',
  `ocr_text` text COMMENT '识别原文',
  `enterprise_text` text COMMENT '企业号或蓝V名称',
  `reg_content` varchar(255) NOT NULL DEFAULT '' COMMENT '提取出的联系方式',
  `clue_type` tinyint NOT NULL DEFAULT 0 COMMENT '0未识别 1微信号 2手机号 3QQ号',
  `result` tinyint NOT NULL DEFAULT 0 COMMENT '0走访中 1已入库 2未识别跳过 3已私信 4初筛跳过 5已有跳过 6默认号丢弃 7日上限跳过',
  `hash` varchar(64) NOT NULL DEFAULT '' COMMENT '联系方式去重指纹',
  `cover_urls` text COMMENT '置顶封面地址JSON',
  `stitch_url` varchar(512) NOT NULL DEFAULT '' COMMENT '拼接后图片',
  `tokens` int NOT NULL DEFAULT 0 COMMENT '算力消耗',
  `is_verify` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '微信号是否已校验:0待校验1已处理',
  `exec_time` datetime DEFAULT NULL COMMENT '执行时间',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '更新时间',
  `delete_time` int DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_dy_uid` (`task_id`, `dy_uid`),
  KEY `idx_user_task` (`user_id`, `task_id`),
  KEY `idx_dy_uid` (`dy_uid`),
  KEY `idx_hash` (`hash`),
  KEY `idx_result` (`result`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_device_daycap` (`user_id`, `device_code`, `result`, `create_time`),
  KEY `idx_task_kw` (`task_id`, `exec_keyword`, `result`),
  KEY `idx_is_verify` (`is_verify`, `result`, `update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抖音获客走访记录';

CREATE TABLE IF NOT EXISTS `la_sv_dy_search_seen` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0 COMMENT '用户id',
  `dy_uid` varchar(64) NOT NULL DEFAULT '' COMMENT '抖音用户唯一标识',
  `dy_id` varchar(64) NOT NULL DEFAULT '' COMMENT '抖音号',
  `action` tinyint NOT NULL DEFAULT 1 COMMENT '1已看过 2已私信 3已入库',
  `last_task_id` int NOT NULL DEFAULT 0 COMMENT '最近一次任务id',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_dy_uid` (`user_id`, `dy_uid`),
  KEY `idx_action` (`user_id`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抖音获客已看已问名单';

CREATE TABLE IF NOT EXISTS `la_sv_dy_search_filter_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0 COMMENT '用户id',
  `keywords` json DEFAULT NULL COMMENT '线索词',
  `remarks` json DEFAULT NULL COMMENT '加好友备注',
  `miss_scripts` json DEFAULT NULL COMMENT '未识别私信话术',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '更新时间',
  `delete_time` int DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抖音获客线索词与话术历史';

-- 计费场景:线索入库;封面识别复用视频号 sph_ocr / automation_ocr_img,不另开场景
INSERT INTO `la_model_config`
  (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`)
SELECT 'dy_search_clue', 11005, '算力/条', '抖音获客', 1.00, '每成功入库一条客资消耗1算力', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_model_config` WHERE `scene` = 'dy_search_clue');



SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_add_wechat_record`') AND COLUMN_NAME = 'dy_search_task_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_sv_add_wechat_record` ADD COLUMN `dy_search_task_id` int NOT NULL DEFAULT 0 COMMENT ''抖音获客任务id'', ADD INDEX `idx_dy_search_task_id` (`dy_search_task_id`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;


-- ============================================================
-- 爆款复刻:文案长度控制(目标字数)
-- 0=未设置,按 VideoImitationTask::COPYWRITING_LENGTH_DEFAULT 处理;
-- 目标字数经 length 参数下发 Coze 工作流,同时写进提示词兜底。
-- 成片时长按「字数/3」估算,故字数即约等于秒数上限。
-- 建列语句见下文「la_video_imitation_task 本版新增列」。
-- ============================================================


-- ============================================================
-- 爆款复刻洗稿模式:自选剪辑模板与背景音乐
-- 两个字段都是「空=沿用原有自动行为」，不改人设模式链路。
-- ============================================================


-- ============================================================
-- la_video_imitation_task 本版新增列(合并为一次 ALTER,避免七次重建表)
-- 该表 v2.9.0 就有,列由 information_schema 守卫,已存在则整段 DO 0。
-- 各列用途见上文对应小节:
--   clip_template_id        手动爆款复刻·本次下发使用的剪辑模板
--   wash_music_volume       洗稿·背景音乐音量
--   wash_speech_rate        洗稿·口播语速
--   wash_voice_is_original  洗稿·是否用所选形象原音
--   copywriting_length      爆款复刻·仿写文案目标字数
--   wash_clip_template_id   洗稿·自选剪辑模板
--   wash_music_url          洗稿·自选背景音乐
-- 一律不写 AFTER:列的物理位置无业务含义,而合并成一条后只要有一个 AFTER 目标列
-- 缺失(实测存在未跑完 v2.13.1 洗稿迁移的环境),七列会一起加不上。
-- ============================================================
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_video_imitation_task`') AND COLUMN_NAME = 'clip_template_id');
SET @s := IF(@c = 0, 'ALTER TABLE `la_video_imitation_task`
    ADD COLUMN `clip_template_id` varchar(40) NOT NULL DEFAULT '''' COMMENT ''本次下发使用的剪辑模板ID'',
    ADD COLUMN `wash_music_volume` decimal(3,2) NOT NULL DEFAULT -1.00 COMMENT ''洗稿背景音乐音量0~1,-1未设置'',
    ADD COLUMN `wash_speech_rate` decimal(3,2) NOT NULL DEFAULT -1.00 COMMENT ''洗稿口播语速0.5~2,-1未设置'',
    ADD COLUMN `wash_voice_is_original` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT ''洗稿音色是否用所选形象原音:0否1是'',
    ADD COLUMN `copywriting_length` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT ''仿写文案目标字数,0未设置'',
    ADD COLUMN `wash_clip_template_id` varchar(40) NOT NULL DEFAULT '''' COMMENT ''洗稿自选剪辑模板ID,空=自动随机'',
    ADD COLUMN `wash_music_url` varchar(500) NOT NULL DEFAULT '''' COMMENT ''洗稿自选背景音乐,空=系统音乐随机''', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- 24h任务生成：按设备 id 游标续跑，当天扫完即停。
-- 运营每分钟 15 台、0:00-3:00（小时 0-2）；发布每分钟 2 台、0:00-6:00（小时 0-5）。
UPDATE `la_dev_crontab`
SET `name` = '24小时运营任务生成',
    `remark` = '运营/爆款建单，与素材发布分开续跑',
    `expression` = '* 0-2 * * *',
    `status` = 1,
    `error` = '',
    `update_time` = UNIX_TIMESTAMP()
WHERE `command` = 'auto_device_create_cron' AND `delete_time` IS NULL;

-- 素材发布/朋友圈建单从运营 cron 拆出，独立短锁续跑，避免被爆款/线索拖死。
-- last_time 必须给初值：调度器对 last_time 为空的行首轮只记 next_time 不执行。
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '24小时素材发布任务生成', 1, 0, '成品库直发的内容发布与朋友圈建单，与运营任务分开续跑', 'auto_device_publish_create_cron', '', 1, '* 0-5 * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'auto_device_publish_create_cron' AND `delete_time` IS NULL);



-- ================ 出图任务兜底：轮询 + 提交中断回收退费 ================
-- DrawGenerateService::pollPending() 此前全仓库无调用方，出图链路实际没有任何兜底；
-- 而「status=0(PENDING) 且 mid_task_id 为空」的任务连 pollPending 都捞不到——那是同步
-- 等中台时 PHP 进程被 nginx/php-fpm 的 600s 掐断留下的僵尸，算力永远挂在预扣态。
-- 2026-09-09 上游停摆 14 分钟即留下 92 条僵尸、2700 算力退不回来。
-- 轮询要逐条打中台，与整张表共用同一个 600s 请求预算，故取 */5 而非每分钟。
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '出图任务轮询与回收', 1, 0, '轮询未完成出图任务；回收提交中途被掐断的任务并退还预扣算力', 'draw:recover', '', 1, '*/5 * * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'draw:recover' LIMIT 1
);



-- ================ 算力流水按「用户 + 时间」查询补索引 ================
-- la_user_tokens_log 只有主键与 team_id / task_id / source_sn 索引。小程序算力账单按用户与日期查、
-- 充值记录按用户与时段筛选（列表 + 计数 + 合计三条查询），全部走主键全表扫描：
-- 27 万行热缓存单条 120~300ms，冷缓存可达数秒，且表体积已超过 InnoDB 缓冲池，每次都要回磁盘。
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_user_tokens_log`') AND INDEX_NAME = 'idx_user_time');
SET @s := IF(@c = 0, 'ALTER TABLE `la_user_tokens_log` ADD INDEX `idx_user_time` (`user_id`, `create_time`)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;


-- ================ 成品直发人设的运行模式归一 ================
-- 合成规则弹窗里「运行模式」那一节只在工作方式=视频剪辑时渲染，切成成品库直发的人设
-- 界面上再也改不到它，可 run_mode=2 的旧值会留在人设表里。
-- 发布建单 cron（AutoDeviceDailyCreateRunner::queryDevices）直接排除无手机模式，
-- 于是绑了设备、工作方式也选了成品库直发的人设，工作日历里一条发布任务都建不出来，
-- 而界面只显示「当前为无手机模式」，看不出任务为什么没了。
-- 写入侧已在 SynthesisConfigLogic::update / AiPersonaLogic::update 归一，这里修存量数据。
-- 顺序不能反：先按「还没归一」的人设标设备，再改 run_mode。
-- 反过来写会把本来就正常的成品直发人设的设备也一并标上补建。
-- 归一后当天的发布任务需要补建：auto_device_frist_create_cron 按 is_first=1 补，
-- 已存在的时段槽位由 SvDeviceTaskExistenceService 去重，不会建出重复任务。
UPDATE `la_sv_device` d
INNER JOIN `la_ai_persona` p ON p.`id` = d.`persona_id`
SET d.`is_first` = 1,
    d.`update_time` = UNIX_TIMESTAMP()
WHERE p.`publish_mode` = 2 AND p.`run_mode` <> 1 AND p.`delete_time` IS NULL AND d.`auto_type` = 1;

UPDATE `la_ai_persona`
SET `run_mode` = 1,
    `update_time` = UNIX_TIMESTAMP()
WHERE `publish_mode` = 2 AND `run_mode` <> 1 AND `delete_time` IS NULL;


-- ================ 会员到期误冻实体的修复 ================
-- MemberService::freezeExcess 把配额 0 当成「免费禁止」全部冻结，而 canCreate 与后台
-- 等级表（user_level 列表的 fmt）里 0 一律是「不限」，-1 才是禁止，两处口径正好相反。
-- 于是默认等级只要把某项配额留成 0（界面显示「不限」），用户会员一到期就被冻光：
-- 人设 status=0 后两条 24h 合成 cron（只捞 status=1）当天起一条内容都不再生成。
-- 代码侧已按 -1 禁止 / 0 不限 / N 最多 N 归一，这里恢复被误冻的存量数据。
-- 只修「状态值专用于冻结」的四张表；智能体与知识库的 is_enable=0 也可能是用户自己关的，
-- 无法与误冻区分，不在这里动。

-- 人设：默认等级人设配额为 0（不限）时，落到默认等级的用户不该有任何被冻结的人设
UPDATE `la_ai_persona` p
INNER JOIN `la_user` u ON u.`id` = p.`user_id`
INNER JOIN `la_user_level` l ON l.`is_default` = 1 AND l.`delete_time` IS NULL
SET p.`status` = 1,
    p.`update_time` = UNIX_TIMESTAMP()
WHERE p.`status` = 0 AND p.`delete_time` IS NULL
  AND u.`level_id` = l.`id` AND l.`max_personas` = 0;

-- 数字人形象（蝉镜）
UPDATE `la_human_anchor` a
INNER JOIN `la_user` u ON u.`id` = a.`user_id`
INNER JOIN `la_user_level` l ON l.`is_default` = 1 AND l.`delete_time` IS NULL
SET a.`status` = 1,
    a.`update_time` = UNIX_TIMESTAMP()
WHERE a.`status` = 9
  AND u.`level_id` = l.`id` AND l.`max_digital_humans` = 0;

-- 数字人形象（闪剪，成功态是 6）
UPDATE `la_shanjian_anchor` a
INNER JOIN `la_user` u ON u.`id` = a.`user_id`
INNER JOIN `la_user_level` l ON l.`is_default` = 1 AND l.`delete_time` IS NULL
SET a.`status` = 6,
    a.`update_time` = UNIX_TIMESTAMP()
WHERE a.`status` = 9
  AND u.`level_id` = l.`id` AND l.`max_digital_humans` = 0;

-- 音色
UPDATE `la_human_voice` v
INNER JOIN `la_user` u ON u.`id` = v.`user_id`
INNER JOIN `la_user_level` l ON l.`is_default` = 1 AND l.`delete_time` IS NULL
SET v.`status` = 1,
    v.`update_time` = UNIX_TIMESTAMP()
WHERE v.`status` = 9
  AND u.`level_id` = l.`id` AND l.`max_voices` = 0;

-- ================ 24h 设备任务建单分页索引 ================
-- AutoDeviceDailyCreateRunner::queryDevices 按 auto_type=1 / is_first=0 / persona_id>0 / id>cursor 分页。
-- 现有索引无法同时覆盖这些过滤条件，设备量大时可能触发全表扫描；复合索引让 MySQL 直接定位到游标并按 id 顺序 LIMIT。
SET @c := (SELECT COUNT(1) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_sv_device`')
             AND INDEX_NAME = 'idx_auto_daily_cursor');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_sv_device` ADD INDEX `idx_auto_daily_cursor` (`auto_type`, `is_first`, `persona_id`, `id`)',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

-- 作品数据定时同步：默认关闭，由用户按人设开启。
SET @c := (SELECT COUNT(1) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = TRIM(BOTH '`' FROM '`la_ai_persona`')
             AND COLUMN_NAME = 'work_data_auto_sync');
SET @s := IF(@c = 0,
    'ALTER TABLE `la_ai_persona` ADD COLUMN `work_data_auto_sync` tinyint unsigned NOT NULL DEFAULT 0 COMMENT ''作品数据定时同步：0关闭1开启''',
    'DO 0');
PREPARE p FROM @s;
EXECUTE p;
DEALLOCATE PREPARE p;

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`)
SELECT '作品数据定时同步', 1, 0, '每分钟检查，每小时同步已开启人设的到期数据，成功取数按次计费', 'work_data_sync', '', 1, '* * * * *', '', UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command` = 'work_data_sync' AND `delete_time` IS NULL);
