-- ---------- 1) GEO 项目(品牌)· 有 team_id,解散软删 ----------
CREATE TABLE IF NOT EXISTS `la_geo_project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间隔离',
  `brand_name` varchar(100) NOT NULL DEFAULT '' COMMENT '品牌名称',
  `website` varchar(255) NOT NULL DEFAULT '',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `industry` varchar(100) NOT NULL DEFAULT '' COMMENT '行业',
  `intro` text COMMENT '产品介绍',
  `features` text COMMENT '产品特点',
  `target_customer` text COMMENT '目标客户',
  `keywords` text COMMENT '核心关键词JSON',
  `competitors` text COMMENT '竞品JSON',
  `aliases` text COMMENT '品牌别名JSON数组',
  `gen_model` varchar(100) NOT NULL DEFAULT '' COMMENT '生成侧模型ID,空=系统默认',
  `auto_monitor` tinyint(1) NOT NULL DEFAULT 0 COMMENT '每日自动监测开关 0关1开',
  `last_auto_date` varchar(10) NOT NULL DEFAULT '' COMMENT '最近一次自动监测日期 Y-m-d(幂等闸)',
  `country` varchar(50) NOT NULL DEFAULT '中国',
  `lang` varchar(20) NOT NULL DEFAULT 'zh',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_user` (`user_id`), KEY `idx_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO项目(品牌)';

-- ---------- 2) GEO 关键词/问题库 ----------
CREATE TABLE IF NOT EXISTS `la_geo_keyword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '品牌词|行业词|产品词|长尾词|AI问题|场景问题|...',
  `value` varchar(500) NOT NULL DEFAULT '',
  `topic_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属话题；0=未挂话题的旧关键词',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=停用 1=启用',
  `source` varchar(20) NOT NULL DEFAULT '' COMMENT 'init|ai|ai_search|monitor|manual',
  `create_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_project` (`project_id`), KEY `idx_topic` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO关键词/问题库';

-- ---------- 3) GEO 知识实体 ----------
CREATE TABLE IF NOT EXISTS `la_geo_knowledge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `source` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名或URL',
  `source_type` varchar(30) NOT NULL DEFAULT '' COMMENT 'pdf|word|url|website|faq|...',
  `entity_type` varchar(30) NOT NULL DEFAULT '' COMMENT '品牌介绍|产品介绍|能力标签|...',
  `content` text,
  `create_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO知识实体';

-- ---------- 4) GEO 生成内容(含 v7 封面图列) ----------
CREATE TABLE IF NOT EXISTS `la_geo_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `content_type` varchar(30) NOT NULL DEFAULT '' COMMENT 'faq|博客|产品介绍|...',
  `topic_id` int(11) NOT NULL DEFAULT 0 COMMENT '归属话题',
  `keyword_id` int(11) NOT NULL DEFAULT 0 COMMENT '针对的场景问题',
  `keyword_ids` text COMMENT '场景问题ID JSON数组',
  `template` varchar(50) NOT NULL DEFAULT '' COMMENT '创作模板key,空=自定义风格',
  `style` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义风格',
  `extra` varchar(500) NOT NULL DEFAULT '' COMMENT '补充诉求',
  `use_kb` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否引用品牌语料 0否1是',
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` longtext,
  `tags` text COMMENT '标签JSON',
  `cover_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '封面图URL(签名链可能较长)',
  `cover_task_id` varchar(64) NOT NULL DEFAULT '' COMMENT '文生图任务ID(HdLog.task_id)',
  `cover_status` varchar(16) NOT NULL DEFAULT '' COMMENT '封面图状态: pending/success/failed',
  `cover_prompt` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图提示词',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=草稿 1=已发布',
  `adopted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=生成中未采纳 1=已采纳到内容管理',
  `source_task_id` int(11) NOT NULL DEFAULT 0,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_topic` (`topic_id`),
  KEY `idx_keyword` (`keyword_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO生成内容';

-- ---------- 5) GEO 监测结果 ----------
CREATE TABLE IF NOT EXISTS `la_geo_monitor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `engine` varchar(30) NOT NULL DEFAULT '' COMMENT '监测引擎 deepseek|doubao|tongyi|yuanbao',
  `keyword_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联场景问题',
  `topic_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联话题',
  `query` varchar(500) NOT NULL DEFAULT '',
  `brand_appear` tinyint(1) NOT NULL DEFAULT 0,
  `brand_rank` int(11) NOT NULL DEFAULT 0,
  `citation` text,
  `mentions` text COMMENT '回答中出现的品牌序列JSON [{brand,rank}]',
  `citations_json` text COMMENT '回答引用来源JSON [{title,site,url}]',
  `sentiment` tinyint(2) NOT NULL DEFAULT 0 COMMENT '品牌情绪 1=正面 0=中立 -1=负面',
  `search_mode` varchar(8) NOT NULL DEFAULT 'model' COMMENT '监测口径 model=模型直答 web=联网检索',
  `model` varchar(64) NOT NULL DEFAULT '' COMMENT '中台实际生效的上游模型名',
  `geo_score` int(11) NOT NULL DEFAULT 0,
  `exposure_score` int(11) NOT NULL DEFAULT 0,
  `citation_score` int(11) NOT NULL DEFAULT 0,
  `brand_visibility` int(11) NOT NULL DEFAULT 0,
  `raw_answer` longtext,
  `create_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_keyword` (`keyword_id`),
  KEY `idx_proj_time` (`project_id`, `create_time`),
  KEY `idx_proj_topic_time` (`project_id`, `topic_id`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO监测结果';

-- ---------- 6) GEO 异步任务 ----------
CREATE TABLE IF NOT EXISTS `la_geo_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `task_type` varchar(30) NOT NULL DEFAULT '' COMMENT 'build_context|parse_knowledge|analyze_brand|gen_keyword|gen_content|monitor|gen_suggestion|monitor_batch',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|running|success|failed',
  `input` mediumtext COMMENT '任务输入JSON(知识导入含全文)',
  `logs` text COMMENT 'JSON数组 [{ts,step,message}]',
  `result_ref` text COMMENT '任务结果引用/JSON(如建议列表)',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO异步任务';

-- ---------- 7) GEO 话题(场景问题挂在话题下) ----------
CREATE TABLE IF NOT EXISTS `la_geo_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '话题名,如 全流程私域运营',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=停用 1=启用',
  `question_target` int(11) NOT NULL DEFAULT 10 COMMENT '目标场景问题数',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO话题';

-- ---------- 8) GEO 官网站点(WordPress/webhook)· 有 team_id,解散软删 ----------
CREATE TABLE IF NOT EXISTS `la_geo_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间隔离',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '站点名称',
  `url` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'wordpress' COMMENT 'wordpress|webhook|manual|wechat_oa',
  `api_endpoint` varchar(255) NOT NULL DEFAULT '',
  `api_user` varchar(100) NOT NULL DEFAULT '',
  `api_key` varchar(1024) NOT NULL DEFAULT '' COMMENT 'AES-GCM密文(GeoCredentialService v1:iv.tag.cipher)',
  `last_check` varchar(255) NOT NULL DEFAULT '' COMMENT '最近连通性检测结果',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=停用 1=正常',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_user` (`user_id`), KEY `idx_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO官网站点';

-- ---------- 9) GEO 官网定时发布任务 ----------
CREATE TABLE IF NOT EXISTS `la_geo_site_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '官网定时发布',
  `daily_count` int(11) NOT NULL DEFAULT 1 COMMENT '每日发布篇数',
  `published_count` int(11) NOT NULL DEFAULT 0 COMMENT '累计已发布',
  `today_date` varchar(10) NOT NULL DEFAULT '' COMMENT '今日计数所属日期 Y-m-d',
  `today_count` int(11) NOT NULL DEFAULT 0 COMMENT '今日已发布篇数',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=停用 1=启用',
  `last_run` int(11) NOT NULL DEFAULT 0 COMMENT '最近执行时间',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_site` (`site_id`), KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO官网定时发布任务';

-- ---------- 10) GEO 内容投递记录(含 v5 投稿类型、v8 效果回收列) ----------
CREATE TABLE IF NOT EXISTS `la_geo_publish` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `content_id` int(11) NOT NULL DEFAULT 0,
  `media_id` int(11) NOT NULL DEFAULT 0,
  `site_id` int(11) NOT NULL DEFAULT 0 COMMENT '官网发布的站点ID；0=媒体投递',
  `channel` varchar(20) NOT NULL DEFAULT '' COMMENT '发布来源 空=媒体投递 site=官网站点 auth=授权直发 phone=AI手机',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '扣费人ID,退费按此原路退回',
  `media_name` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|published|failed',
  `mode` varchar(20) NOT NULL DEFAULT 'manual' COMMENT 'manual|api|register|auth|phone',
  `media_type` varchar(10) NOT NULL DEFAULT 'article' COMMENT '投稿类型 article=图文 video=视频',
  `video_id` int(11) NOT NULL DEFAULT 0 COMMENT '视频投稿关联 geo_video_task.id',
  `cost` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '投递扣费(元)',
  `published_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '已发布文章/视频链接(签名链可能较长)',
  `channel_type` varchar(30) NOT NULL DEFAULT '' COMMENT '登记渠道类型 portal|we_media|official|baike',
  `site_name` varchar(100) NOT NULL DEFAULT '' COMMENT '登记发布站点名',
  `account` varchar(500) NOT NULL DEFAULT '' COMMENT '媒体号名称(AI手机多账号逗号拼接)',
  `publish_time` int(11) NOT NULL DEFAULT 0 COMMENT '登记的发布时间',
  `error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '发布失败原因',
  `provider_order` varchar(64) NOT NULL DEFAULT '' COMMENT '发稿平台订单号(mode=api)；AI手机为 sv_publish_setting.id',
  `api_quota` int(11) NOT NULL DEFAULT 0 COMMENT '中台发稿场景扣费额度(quota),api 模式退款时回传',
  `stat_views` int(11) NOT NULL DEFAULT 0 COMMENT '播放/阅读数',
  `stat_likes` int(11) NOT NULL DEFAULT 0 COMMENT '点赞数',
  `stat_comments` int(11) NOT NULL DEFAULT 0 COMMENT '评论数',
  `stat_collects` int(11) NOT NULL DEFAULT 0 COMMENT '收藏数',
  `stat_shares` int(11) NOT NULL DEFAULT 0 COMMENT '分享数',
  `stat_sync_time` int(11) NOT NULL DEFAULT 0 COMMENT '最近一次回收时间',
  `stat_status` varchar(16) NOT NULL DEFAULT '' COMMENT '回收状态 空=未回收 ok|unsupported|failed',
  `stat_error` varchar(200) NOT NULL DEFAULT '' COMMENT '回收失败原因',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_project` (`project_id`), KEY `idx_content` (`content_id`), KEY `idx_site` (`site_id`), KEY `idx_stat_sync` (`status`, `stat_sync_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO内容投递记录';

-- ---------- 11) GEO 媒体库(后台预置投放渠道;含 v5 content_form/platform_code) ----------
CREATE TABLE IF NOT EXISTS `la_geo_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT 'b2b|blog|media|portal|media_v|ai_phone|...',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '行业分类',
  `region` varchar(50) NOT NULL DEFAULT '' COMMENT '覆盖区域',
  `pc_weight` int(11) NOT NULL DEFAULT 0 COMMENT 'PC权重',
  `mobile_weight` int(11) NOT NULL DEFAULT 0 COMMENT '移动权重',
  `success_rate` int(11) NOT NULL DEFAULT 0 COMMENT '成功率%',
  `publish_speed` varchar(30) NOT NULL DEFAULT '' COMMENT '发布速度',
  `include_status` varchar(50) NOT NULL DEFAULT '' COMMENT '收录情况',
  `allow_url` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否允许带链接',
  `can_geo_rank` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否可做GEO排名',
  `price` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '单篇价格(元)',
  `remark` varchar(500) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=下架 1=上架',
  `provider_code` varchar(50) NOT NULL DEFAULT '' COMMENT '渠道标识(AI手机平台映射权威字段)',
  `content_form` varchar(30) NOT NULL DEFAULT 'article' COMMENT '支持的投稿类型 article|video|article,video',
  `platform_code` varchar(30) NOT NULL DEFAULT '' COMMENT '关联授权平台标识(命中已授权账号时免代发费直发)',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '权重(倒序)',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  `active_provider` varchar(50) GENERATED ALWAYS AS (
    IF(`delete_time` IS NULL AND `provider_code` <> '', `provider_code`, NULL)
  ) STORED COMMENT '活跃渠道唯一键(软删/空码为NULL)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_active_provider` (`active_provider`),
  KEY `idx_category` (`category`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO媒体库(投放渠道)';

-- ---------- 12) GEO 文章转短视频任务(二期;已剔除废弃的 charge_split 列) ----------
CREATE TABLE IF NOT EXISTS `la_geo_video_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '提交人(计费/退费对象)',
  `project_id` int(11) NOT NULL DEFAULT 0,
  `content_id` int(11) NOT NULL DEFAULT 0 COMMENT '源文章',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '文章标题',
  `script` text COMMENT 'AI 口播文案',
  `ratio` varchar(8) NOT NULL DEFAULT '9:16' COMMENT '画幅',
  `provider` varchar(16) NOT NULL DEFAULT 'volc' COMMENT '生成通道 volc=即梦 mock=演示',
  `provider_task` varchar(64) NOT NULL DEFAULT '' COMMENT '中台任务ID(轮询用)',
  `status` varchar(16) NOT NULL DEFAULT 'generating' COMMENT 'generating/success/failed',
  `video_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '成片地址(签名链可能较长)',
  `error_msg` varchar(300) NOT NULL DEFAULT '',
  `cost` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '已扣算力(失败退费后清零防重复退)',
  `create_time` int(10) DEFAULT NULL,
  `update_time` int(10) DEFAULT NULL,
  `delete_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO 文章转短视频任务(二期)';

-- ---------- 13) GEO 诊断报告快照(v5) ----------
CREATE TABLE IF NOT EXISTS `la_geo_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '生成人(计费人)',
  `report` mediumtext COMMENT '报告数据快照 JSON(GeoInsightLogic::report 结构)',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO诊断报告快照';

-- ---------- 14) GEO 授权账号(v5 Web API 发布通道) ----------
CREATE TABLE IF NOT EXISTS `la_geo_auth_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间共享；0=个人',
  `platform` varchar(30) NOT NULL DEFAULT '' COMMENT '平台标识(GeoAuthLogic::PLATFORMS)',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '账号备注名',
  `credentials` text COMMENT '凭据 JSON(appid/secret/token 等,密钥字段为AES-GCM密文)',
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '投稿路由开关 0关1开',
  `last_check` varchar(255) NOT NULL DEFAULT '' COMMENT '最近连通性检测结果',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  `active_key` varchar(80) GENERATED ALWAYS AS (
    IF(`delete_time` IS NULL, CONCAT(`team_id`, ':', `user_id`, ':', `platform`), NULL)
  ) STORED COMMENT '活跃行唯一键(软删后为NULL,允许多条历史)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_active_key` (`active_key`),
  KEY `idx_space` (`team_id`,`user_id`),
  KEY `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GEO授权账号(Web API发布通道)';

-- ============================================================
-- 种子一:GEO 计费 —— 已切换为【模型计费】(2026-08-18)
-- GEO 不再使用 model_config 场景价:凡使用大模型的功能,直接按 models_cost 的
-- 模型单价计费(与对话同一定价体系,后台「AI模型-模型计费」可同步/调价),
-- 不叠加场景算力。账变编号 14001-14010 保留用于流水分类与历史账单渲染。
-- 此处清理历史 geo_* 场景行(幂等;新装库本就无这些行)。
-- ============================================================

DELETE FROM `la_model_config` WHERE `scene` LIKE 'geo\_%';

-- 监测引擎的上游模型需在 models_cost 有计价行(缺行则该次调用免费并告警)。
-- 四引擎监测模型(deepseek-v4-pro / doubao-seed-2-0-lite-260428 / qwen-flash / hy3)
-- 均随中台对话模型同步下发,无需本地种子;模型同步后会按
-- GeoChargeService::MONITOR_PRICE_MODELS 清单检查计价行并在后台提示。
-- (曾误种 doubao-seed-1-6-250615 / doubao-1-5-pro-32k-250115 两行:监测实际
--  不用这两个模型,已从本文件移除;老库若已插入,用下面语句清理,幂等)
DELETE FROM `la_models_cost`
WHERE `model_id` = 0 AND `type` = 1
  AND `alias` IN ('doubao-seed-1-6-250615', 'doubao-1-5-pro-32k-250115');

-- ============================================================
-- 种子二:GEO 媒体库(la_geo_media,8 条)。
-- 【媒体代发已下线】原 12 条收费代发媒体不再预置,仅保留发布渠道:
--   授权直发(博客园/百家号/微信公众号/语雀,price=0,需先在「设置-授权账号」授权)
--   + AI 手机 4 条(price=0)。
-- 幂等:按 provider_code 去重,可重复执行。
-- ============================================================

-- --- 授权直发渠道(price=0;有官方发布 API,授权后用自有账号直发) ---
INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '博客园','blog','IT科技','综合全国',7,7,98,'当日','包网页收录',1,1,0.00,'发到你自己的博客园,需先在「设置-授权账号」授权(MetaWeblog)',1,'cnblogs','article','cnblogs',100,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='cnblogs' AND `delete_time` IS NULL);

INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '百家号','media_v','综合','综合全国',8,7,75,'当日','包资讯收录',1,1,0.00,'发到你自己的百家号,需先在「设置-授权账号」授权；发文走平台统一审核',1,'baijiahao','article','baijiahao',25,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='baijiahao' AND `delete_time` IS NULL);

-- --- 官方授权自有账号(v5;price=0,需先在「设置-授权账号」授权) ---
INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '微信公众号(自有)','media_v','综合','综合全国',7,9,99,'当日','平台内收录',1,1,0.00,'发到你自己的公众号,需先在「设置-授权账号」授权；走草稿箱+自动群发,不收代发费',1,'wechat_oa','article','wechat_oa',9995,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='wechat_oa' AND `delete_time` IS NULL);

INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '语雀(自有知识库)','blog','IT科技','综合全国',6,5,99,'当日','包网页收录',1,1,0.00,'发到你自己的语雀知识库,需先在「设置-授权账号」授权；公开文档可被搜索引擎与AI收录',1,'yuque','article','yuque',9994,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='yuque' AND `delete_time` IS NULL);

-- --- AI 手机渠道 4 条(price=0;用系统已绑定的 AI 手机账号自动发布) ---
INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '小红书','ai_phone','综合','综合全国',6,9,95,'当日','平台内收录',0,1,0.00,'使用系统中已绑定的 AI 手机账号自动发布(图文/视频)',1,'xhs','article,video','xhs',9999,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='xhs' AND `delete_time` IS NULL);

INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '抖音','ai_phone','综合','综合全国',6,9,95,'当日','平台内收录',0,1,0.00,'使用系统中已绑定的 AI 手机账号自动发布(视频/图文)',1,'douyin','article,video','douyin_phone',9998,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='douyin' AND `delete_time` IS NULL);

INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '快手','ai_phone','综合','综合全国',5,8,95,'当日','平台内收录',0,1,0.00,'使用系统中已绑定的 AI 手机账号自动发布(视频)',1,'kuaishou','video','kuaishou_phone',9997,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='kuaishou' AND `delete_time` IS NULL);

INSERT INTO `la_geo_media`
  (name,type,category,region,pc_weight,mobile_weight,success_rate,publish_speed,include_status,allow_url,can_geo_rank,price,remark,status,provider_code,content_form,platform_code,sort,create_time)
SELECT '视频号','ai_phone','综合','综合全国',5,8,95,'当日','平台内收录',0,1,0.00,'使用系统中已绑定的 AI 手机账号自动发布(视频)',1,'sph','video','wx_channels',9996,UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_geo_media` WHERE `provider_code`='sph' AND `delete_time` IS NULL);

-- ============================================================
-- 种子三:GEO 定时任务(la_dev_crontab,4 条;幂等)
-- 注意 last_time 必须给初值:调度器对 last_time 为空的行首轮只记 next_time 不执行。
-- ⚠️ 种子先落库、代码后部署时,调度器找不到命令会把任务标 status=3(错误)并从此跳过;
--    部署完成后执行本文件尾部的复位 UPDATE。
-- ============================================================

INSERT INTO `la_dev_crontab` (`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`last_time`,`create_time`,`update_time`)
SELECT 'GEO每日自动监测',1,0,'对开启自动监测的品牌每天自动全量采集一轮(内部有当日幂等闸)','geo_daily_monitor','',1,'0 3 * * *',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command`='geo_daily_monitor' AND `delete_time` IS NULL);

INSERT INTO `la_dev_crontab` (`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`last_time`,`create_time`,`update_time`)
SELECT 'GEO监测cell定时执行',1,0,'每分钟执行一键诊断/每日监测落库的 pending cell(单轮限流,剩余下轮继续)','geo_monitor_cron','',1,'* * * * *',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command`='geo_monitor_cron' AND `delete_time` IS NULL);

-- 已入库的也强制为每分钟,避免沿用旧间隔
UPDATE `la_dev_crontab` SET `expression`='* * * * *', `status`=1, `error`='', `update_time`=UNIX_TIMESTAMP()
WHERE `command`='geo_monitor_cron' AND `delete_time` IS NULL;

-- 部署后复位(代码上线后执行一次,幂等):
UPDATE `la_dev_crontab` SET `status`=1, `error`=''
WHERE `command` IN ('geo_daily_monitor','geo_monitor_cron') AND `status`=3 AND `delete_time` IS NULL;

-- ============================================================
-- PPT 生成提示词扩大字符限制
-- 用户输入 10000 字需能正常生成；组装后的幻灯片 prompt
-- (主题+章节正文+版式说明)会超过原 varchar(2000),prompt/content 扩为 text
-- last_prompt 同步扩到 10000,会话预览可完整记下用户输入
-- ============================================================
ALTER TABLE `la_draw_task`
    MODIFY COLUMN `prompt` text NOT NULL COMMENT '主提示词';

ALTER TABLE `la_draw_message`
    MODIFY COLUMN `content` text NOT NULL COMMENT '用户提示词/文本';

ALTER TABLE `la_draw_conversation`
    MODIFY COLUMN `last_prompt` varchar(10000) NOT NULL DEFAULT '' COMMENT '最近一条提示词';



-- ============================================================================
-- GEO:下线「文章转短视频」(对齐 product 分支 v9 补丁的视频部分)
-- GEO 不再自己合成视频:文章转成口播稿后带入「数字人纯口播视频」出片,
-- 按数字人文案/合成口径计费。geo_video_task 表保留(发布台账反查历史任务)。
-- 放在文件末尾:上文种子会把 geo_video 场景按旧口径播入,这里统一收口停用。
-- 幂等,可重复执行。
-- ============================================================================

-- 停掉短视频轮询定时任务(代码中 geo_video_cron 命令已移除;新装库无此行,UPDATE 空转无害)
-- ⚠️ 执行前若存在 status='generating' 的存量任务,先让 geo_video_cron 跑完一轮
--    (或人工结算退费),否则这些任务的 cost 无法自动退回:
--    SELECT COUNT(*) FROM `la_geo_video_task` WHERE `status`='generating' AND `delete_time` IS NULL;
UPDATE `la_dev_crontab` SET `status` = 0, `error` = 'GEO短视频已下线,改由数字人模块出片'
WHERE `command` = 'geo_video_cron' AND `delete_time` IS NULL;

-- 停收「文章转短视频」算力(保留行不删:历史算力账单靠 scene 渲染文案)
UPDATE `la_model_config` SET `status` = 0, `description` = '已下线:GEO 改为只产出口播稿,视频由数字人模块生成并按数字人口径计费'
WHERE `scene` = 'geo_video';

-- 复位:调度器找不到已删除的 geo_video_cron 会把行标成 status=3,上面已置 0;
-- 其余 GEO 定时任务若曾因此连坐报错,一并复位
UPDATE `la_dev_crontab` SET `status` = 1, `error` = ''
WHERE `command` IN ('geo_daily_monitor', 'geo_publish_sync', 'geo_site_publish', 'geo_monitor_cron')
  AND `status` = 3 AND `delete_time` IS NULL;


-- ============================================================================
-- 补种:GEO 定时任务缺失的 2 条(上文"种子三"注释写 4 条实际只播了 2 条)
-- geo_publish_sync:AI手机投稿回执回填 + 发布互动数据回收
-- geo_site_publish:公众号定时发布(按每日配额推进草稿箱/群发)
-- 幂等,可重复执行。last_time 给初值,调度器对空值首轮只记 next_time 不执行。
-- ============================================================================

INSERT INTO `la_dev_crontab` (`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`last_time`,`create_time`,`update_time`)
SELECT 'GEO发稿回执同步',1,0,'AI手机投稿回执回填发布台账；已发布内容互动数据回收(冷却6小时)','geo_publish_sync','',1,'*/10 * * * *',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command`='geo_publish_sync' AND `delete_time` IS NULL);

INSERT INTO `la_dev_crontab` (`name`,`type`,`system`,`remark`,`command`,`params`,`status`,`expression`,`last_time`,`create_time`,`update_time`)
SELECT 'GEO公众号定时发布',1,0,'按站点每日配额把 GEO 内容推进公众号草稿箱/自动群发','geo_site_publish','',1,'0 * * * *',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `la_dev_crontab` WHERE `command`='geo_site_publish' AND `delete_time` IS NULL);

-- ============================================================================
-- 后台 GEO 管理模块菜单(营销管理 → GEO管理):概览/项目管理/媒体库/发布记录
-- 幂等,可重复执行;动态取 pid,不硬编码菜单 id。
-- perms 与真实路由 controller/action 严格一致(app/adminapi/controller/geo/*),
-- 保证 AuthMiddleware 真正拦截,而非只做前端按钮显隐。
-- 注意:全新安装脚本 public/install/db/ 按惯例需同步同批菜单(由安装包维护者处理)。
-- ============================================================================

-- 1) 目录:营销管理 下挂「GEO管理」
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'M', 'GEO管理', '', 50, '', 'geo', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
    ORDER BY (`name` = '营销管理') DESC, (`id` = 438) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `type` = 'M' AND `name` = 'GEO管理'
);

-- 2) 四个页面(C):概览 / 项目管理 / 媒体库 / 发布记录
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', v.name, '', v.sort, v.perms, v.paths, v.component, '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND `name` = 'GEO管理'
    ORDER BY id ASC
    LIMIT 1
) p
INNER JOIN (
    SELECT '概览' AS name, 40 AS sort, 'geo.overview/index' AS perms, 'overview' AS paths, 'marketing/geo/overview/index' AS component UNION ALL
    SELECT '项目管理', 30, 'geo.project/lists', 'project', 'marketing/geo/project/index' UNION ALL
    SELECT '媒体库', 20, 'geo.media/lists', 'media', 'marketing/geo/media/index' UNION ALL
    SELECT '发布记录', 10, 'geo.publish/lists', 'publish', 'marketing/geo/publish/index'
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
    SELECT 'geo.project/lists' AS page_perms, '详情' AS name, 'geo.project/detail' AS perms UNION ALL
    SELECT 'geo.project/lists', '自动监测开关', 'geo.project/setAutoMonitor' UNION ALL
    SELECT 'geo.project/lists', '删除', 'geo.project/delete' UNION ALL
    SELECT 'geo.media/lists', '选项', 'geo.media/options' UNION ALL
    SELECT 'geo.media/lists', '新增', 'geo.media/add' UNION ALL
    SELECT 'geo.media/lists', '编辑', 'geo.media/edit' UNION ALL
    SELECT 'geo.media/lists', '状态', 'geo.media/status' UNION ALL
    SELECT 'geo.media/lists', '删除', 'geo.media/delete' UNION ALL
    SELECT 'geo.publish/lists', '删除', 'geo.publish/delete'
) v ON page.`type` = 'C' AND page.`perms` = v.page_perms
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms AND m.`type` = 'A'
);

-- GEO/矩阵发布文案超过 varchar(2000) 会 1406，副标题改为 text
ALTER TABLE `la_sv_publish_setting_detail`
MODIFY COLUMN `material_subtitle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '发布内容副标题';


