-- 爆款库手动导入待执行表
CREATE TABLE IF NOT EXISTS `la_sv_device_viral_manual_import` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `persona_id` int(11) NOT NULL DEFAULT '0' COMMENT '人设id',
  `share_content` varchar(2000) NOT NULL DEFAULT '' COMMENT '原始分享文案',
  `share_url` varchar(500) NOT NULL DEFAULT '' COMMENT '提取出的URL',
  `publish_platform` tinyint(4) NOT NULL DEFAULT '0' COMMENT '平台:1视频号3小红书4抖音5快手',
  `publish_media_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '内容类型:0未知1视频2图文',
  `hash` varchar(64) NOT NULL DEFAULT '' COMMENT '去重hash',
  `title_normalized` varchar(512) NOT NULL DEFAULT '' COMMENT '清洗标题',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待执行1执行中2成功3失败4部分成功',
  `is_interested` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否感兴趣：1是 0否',
  `retry` tinyint(4) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `result_json` json DEFAULT NULL COMMENT '各设备展开结果摘要',
  `parsed_payload` json DEFAULT NULL COMMENT '解析中间结果缓存',
  `scheduled_day` date DEFAULT NULL COMMENT '计划执行所属自然日',
  `started_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始执行时间',
  `finished_at` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_day` (`status`,`scheduled_day`),
  KEY `idx_user_persona` (`user_id`,`persona_id`),
  KEY `idx_hash_user` (`user_id`,`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='爆款库手动导入待执行表';

DELETE FROM `la_dev_crontab` WHERE `command` = 'viral_manual_import_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('爆款手动导入解析', 1, 0, '每日00:00-03:00消费手动导入队列', 'viral_manual_import_cron', '', 1, '* * * * *', NULL, UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 图文发布填坑：按未使用仿写库存 id 升序填充当天发布时段
DELETE FROM `la_dev_crontab` WHERE `command` = 'viral_image_text_publish_fill_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('图文发布兜底', 1, 0, '按未使用图文仿写库存id升序生成当天发布记录', 'viral_image_text_publish_fill_cron', '', 1, '*/5 * * * *', NULL, UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

UPDATE `la_dev_crontab` SET `name` = '社媒平台待发布任务拉取队列', `update_time` = UNIX_TIMESTAMP() WHERE `command` = 'publish_detail_cron';

DELETE FROM `la_config` WHERE `type` = 'rpa' AND `name` = 'demo_switch';
INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('rpa', 'demo_switch', '\"0\"', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `la_model_config` WHERE `scene` = 'ai_reply_like';

ALTER TABLE `la_sv_device_viral_record` 
MODIFY COLUMN `copywriting_type` tinyint(4) NULL DEFAULT 0 COMMENT '0待确定1爆款仿写2无文案3严重偏离4降级处理6兜底7错误记录' AFTER `copywriting`,
MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 0 COMMENT '状态0开始1无文案视频2文案不符合3直接由coze纯ai生成4符合条件5异常6兜底7错误记录' AFTER `comments`,
ADD COLUMN `manual_import_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '手动导入表ID，0表示非手动导入' AFTER `viral_account_id`,
ADD KEY `idx_manual_import_id` (`manual_import_id`);

-- 算力礼包：微信小程序虚拟支付道具产品ID（与小程序后台道具 productId 一致）
ALTER TABLE `la_gift_package`
ADD COLUMN `product_id` varchar(64) NOT NULL DEFAULT '' COMMENT '微信小程序虚拟支付产品ID(productId)' AFTER `package_info`;

-- CDK套餐：微信小程序虚拟支付道具产品ID（与小程序后台道具 productId 一致）
ALTER TABLE `la_device_auth_plan`
ADD COLUMN `product_id` varchar(64) NOT NULL DEFAULT '' COMMENT '微信小程序虚拟支付产品ID(productId)' AFTER `remark`;

ALTER TABLE `la_video_slices`
ADD COLUMN `batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '素材分割批次ID' AFTER `user_id`,
ADD COLUMN `process_mode` varchar(16) NOT NULL DEFAULT 'local' COMMENT '切割通道:local/oss' AFTER `batch_id`,
ADD KEY `idx_batch_id` (`batch_id`);

-- 上传时记录本次 ffmpeg=0/1/2 选择及通道快照，后续批次确认不能被前端临时改变
ALTER TABLE `la_file`
ADD COLUMN `slice_mode` tinyint NOT NULL DEFAULT '0' COMMENT '上传处理意图:0原样入库 1仅转码 2转码+切割' AFTER `transcode_status`,
ADD COLUMN `persona_id` int unsigned NOT NULL DEFAULT '0' COMMENT '归属IP人设ID' AFTER `slice_mode`,
ADD COLUMN `scene` varchar(32) NOT NULL DEFAULT 'persona' COMMENT '素材库场景:ai_creation/persona' AFTER `persona_id`,
ADD COLUMN `process_channel` varchar(16) NOT NULL DEFAULT '' COMMENT '上传时通道快照:local/oss' AFTER `scene`,
ADD KEY `idx_persona_slice_mode` (`persona_id`,`slice_mode`);

ALTER TABLE `la_ai_persona_material`
ADD COLUMN `slice_batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '素材分割批次ID' AFTER `slice_status`,
ADD KEY `idx_slice_batch_id` (`slice_batch_id`);

DELETE FROM `la_model_config` WHERE `scene` IN ('material_slice_local', 'material_slice_oss');
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES
('material_slice_local', 5102, '算力/秒', '素材本地切割', 1.00, '使用站长服务器FFmpeg切割，默认原素材1秒消耗1算力，成本0', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('material_slice_oss', 5102, '算力/秒', '素材服务器切割', 3.00, '使用阿里云OSS MPS切割，默认原素材1秒消耗3算力，成本0', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `la_dev_crontab` WHERE `command` = 'material:split-recover';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('素材分割超时回收', 1, 0, '超时或中断的整批素材分割任务回滚片段并全额退款', 'material:split-recover', '', 1, '* * * * *', NULL, UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 切割素材缺少封面时自动补生成（新切割已入库截帧，此任务用于兜底历史空封面）
DELETE FROM `la_dev_crontab` WHERE `command` = 'material:slice-thumb-backfill';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES ('切割素材封面补全', 1, 0, '为缺少封面的切割素材补生成 thumbnail_url', 'material:slice-thumb-backfill', '--limit 50', 1, '*/10 * * * *', NULL, UNIX_TIMESTAMP(), '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 闪剪成片：保留原链接 + 下载状态，支持前端手动下载转存
ALTER TABLE `la_shanjian_video_task`
ADD COLUMN `video_source_url` varchar(1000) NOT NULL DEFAULT '' COMMENT '闪剪成片原链接' AFTER `video_result_url`,
ADD COLUMN `download_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '成片下载状态:0待下载1下载中2成功3失败' AFTER `video_source_url`;

-- 历史成功且已是本地路径的任务，标记为已下载
UPDATE `la_shanjian_video_task`
SET `download_status` = 2
WHERE `status` = 3
  AND `video_result_url` <> ''
  AND `video_result_url` NOT LIKE 'http%'
  AND `download_status` = 0;

-- 历史成功且仍是远端链接的任务，回填原链接并置为待下载
UPDATE `la_shanjian_video_task`
SET `video_source_url` = `video_result_url`,
    `download_status` = 0
WHERE `status` = 3
  AND `video_result_url` LIKE 'http%'
  AND (`video_source_url` = '' OR `video_source_url` IS NULL);

  -- 上传时写入视频时长，转码中 statistics 可预估待切割子素材数
ALTER TABLE `la_file`
ADD COLUMN `duration` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '视频时长秒(上传写入，切割预估用)' AFTER `process_channel`;


-- 切割任务并入 video_slices：承接原 material_slice_batches/items 的计费与进度
ALTER TABLE `la_video_slices`
ADD COLUMN `batch_no` varchar(64) NULL DEFAULT NULL COMMENT '切割任务号' AFTER `id`,
ADD COLUMN `persona_id` int unsigned NOT NULL DEFAULT '0' COMMENT '人设ID' AFTER `user_id`,
ADD COLUMN `scene` varchar(32) NOT NULL DEFAULT 'persona' COMMENT '素材库场景:ai_creation/persona' AFTER `persona_id`,
ADD COLUMN `billing_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '计费:0无需1已预扣2已确认3已退款' AFTER `status`,
ADD COLUMN `success_slice_count` int unsigned NOT NULL DEFAULT '0' COMMENT '已完成片段数' AFTER `slice_count`,
ADD COLUMN `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '算力单价快照' AFTER `success_slice_count`,
ADD COLUMN `cost_unit` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '成本单价快照' AFTER `unit_price`,
ADD COLUMN `tokens_cost` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '预扣算力' AFTER `cost_unit`,
ADD COLUMN `tokens_log_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '预扣流水ID' AFTER `tokens_cost`,
ADD COLUMN `error_message` varchar(500) NOT NULL DEFAULT '' COMMENT '失败原因' AFTER `tokens_log_id`,
ADD COLUMN `thumbnail_url` varchar(1000) NOT NULL DEFAULT '' COMMENT '原片封面' AFTER `original_path`,
ADD COLUMN `width` int unsigned NOT NULL DEFAULT '0' COMMENT '原片宽' AFTER `thumbnail_url`,
ADD COLUMN `height` int unsigned NOT NULL DEFAULT '0' COMMENT '原片高' AFTER `width`,
ADD COLUMN `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳' AFTER `created_at`,
ADD COLUMN `finish_time` int unsigned NOT NULL DEFAULT '0' COMMENT '完成时间戳' AFTER `update_time`,
ADD UNIQUE KEY `uk_batch_no` (`batch_no`),
ADD KEY `idx_user_persona_status` (`user_id`,`persona_id`,`status`),
ADD KEY `idx_original_status` (`original_video_id`,`status`);

ALTER TABLE `la_ai_persona_material`
MODIFY COLUMN `slice_batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '切割任务ID(video_slices.id)';

ALTER TABLE `la_ai_persona_synthesis_config`
ADD COLUMN `template_config` json DEFAULT NULL COMMENT '视频模板配置:按生成类型独立配置自动随机/自定义模板池' AFTER `generation_types`;

UPDATE `la_config` SET `value` = '{"voice":[{"code":"10000","type":"1","name":"智小敏(女)","status":"1"},{"code":"10001","type":"1","name":"智小柔(女)","status":"1"},{"code":"10002","type":"1","name":"智小满(女)","status":"1"},{"code":"10003","type":"1","name":"爱小芊(女)","status":"1"},{"code":"10004","type":"1","name":"爱小静(女)","status":"1"},{"code":"10005","type":"1","name":"千嶂(男)","status":"1"},{"code":"10006","type":"1","name":"智皓(男)","status":"1"},{"code":"10007","type":"1","name":"爱小杭(男)","status":"1"},{"code":"10008","type":"1","name":"爱小辰(男)","status":"1"},{"code":"10009","type":"1","name":"飞镜(男)","status":"1"},{"code":"10010","type":"2","name":"龙橙2.0","status":"1","model":"cosyvoice-v2","voice":"longcheng_v2","recommended":"译制片","url":"static/audio/voice/longcheng_v2.mp3"},{"code":"10011","type":"2","name":"龙华2.0","status":"1","model":"cosyvoice-v2","voice":"longhua_v2","recommended":"智能客服、对话闲聊","url":"static/audio/voice/longhua_v2.mp3"},{"code":"10012","type":"2","name":"龙书2.0","status":"1","model":"cosyvoice-v2","voice":"longshu_v2","recommended":"新闻播报、有声读物","url":"static/audio/voice/longshu_v2.mp3"},{"code":"10013","type":"2","name":"Bella2.0","status":"1","model":"cosyvoice-v2","voice":"loongbella_v2","recommended":"智能客服、新闻播报、对话闲聊","url":"static/audio/voice/Bella.mp3"},{"code":"10014","type":"2","name":"龙婉2.0","status":"1","model":"cosyvoice-v2","voice":"longwan_v2","recommended":"智能客服、新闻播报、对话闲聊","url":"static/audio/voice/longwan_v2.mp3"},{"code":"10015","type":"2","name":"龙小淳2.0","status":"1","model":"cosyvoice-v2","voice":"longxiaochun_v2","recommended":"智能客服、新闻播报、对话闲聊","url":"static/audio/voice/longxiaochun_v2.mp3"},{"code":"10016","type":"2","name":"龙小夏2.0","status":"1","model":"cosyvoice-v2","voice":"longxiaoxia_v2","recommended":"智能客服、新闻播报、对话闲聊","url":"static/audio/voice/longxiaoxia_v2.mp3"},{"code":"10017","type":"3","name":"芊悦","status":"1","model":"Qwen3-TTS-Flash","voice":"Cherry","recommended":"","url":"static/audio/voice/Cherry.mp3"},{"code":"10018","type":"3","name":"苏瑶","status":"1","model":"Qwen3-TTS-Flash","voice":"Serena","recommended":"","url":"static/audio/voice/Serena.mp3"},{"code":"10019","type":"3","name":"千雪","status":"1","model":"Qwen3-TTS-Flash","voice":"Chelsie","recommended":"","url":"static/audio/voice/Chelsie.mp3"},{"code":"10020","type":"3","name":"茉兔","status":"1","model":"Qwen3-TTS-Flash","voice":"Momo","recommended":"","url":"static/audio/voice/Momo.mp3"},{"code":"10021","type":"3","name":"十三","status":"1","model":"Qwen3-TTS-Flash","voice":"Vivian","recommended":"","url":"static/audio/voice/Vivian.mp3"},{"code":"10022","type":"3","name":"四月","status":"1","model":"Qwen3-TTS-Flash","voice":"Maia","recommended":"","url":"static/audio/voice/Maia.mp3"},{"code":"10023","type":"3","name":"墨讲师","status":"1","model":"Qwen3-TTS-Flash","voice":"Elias","recommended":"","url":"static/audio/voice/Elias.mp3"},{"code":"10024","type":"3","name":"晨煦","status":"1","model":"Qwen3-TTS-Flash","voice":"Ethan","recommended":"","url":"static/audio/voice/Ethan.mp3"},{"code":"10025","type":"3","name":"月白","status":"1","model":"Qwen3-TTS-Flash","voice":"Moon","recommended":"","url":"static/audio/voice/Moon.mp3"},{"code":"10026","type":"3","name":"凯","status":"1","model":"Qwen3-TTS-Flash","voice":"Kai","recommended":"","url":"static/audio/voice/Kai.mp3"},{"code":"10027","type":"3","name":"不吃鱼","status":"1","model":"Qwen3-TTS-Flash","voice":"Nofish","recommended":"","url":"static/audio/voice/Nofish.mp3"},{"code":"10028","type":"3","name":"田叔","status":"1","model":"Qwen3-TTS-Flash","voice":"Vincent","recommended":"","url":"static/audio/voice/Vincent.mp3"}]}', `create_time` = 1730688127, `update_time` = 1749540060 WHERE `type` = 'model'  and `name` = 'list';
UPDATE `la_config` SET `value` = '{"emotions":[{"name":"中性","value":"neutral"},{"name":"高兴","value":"happy"},{"name":"生气","value":"angry"},{"name":"悲伤","value":"sad"},{"name":"恐惧","value":"fear"}],"intensity":[{"name":"自然","value":"50"},{"name":"标准","value":"100"},{"name":"增强","value":"200"}],"avatars":["static/images/2025012010404033eb16170.png","static/images/20250120104040421e46861.png","static/images/20250120104040c2bfd4461.png","static/images/20250120104040c23e62615.png","static/images/20250120104040593541205.png","static/images/202501201040384fd501714.png","static/images/202501201040389b9fb9464.png","static/images/2025012010403842a785765.png","static/images/20250120103619c09582682.png","static/images/20250120103619680405138.png","static/images/202501201036183cdec5211.png","static/images/20250120103618a7ee91656.png","static/images/202501201032047d4e54616.png","static/images/202501201036174774a5145.png","static/images/202501201036188d4389434.png","static/images/2025012010361811d7a7032.png","static/images/202501201036185a67e7101.png"],"directions":["说服力","逻辑性","口才","专业性","技巧性"],"voice":[{"name":"优雅百变","code":"301039","status":"1","logo":"static/images/20250120104633b12b94441.png"},{"name":"磁性男声","code":"301036","status":"1","logo":"static/images/20250120104633ec3df6053.png"},{"name":"自然女声","code":"301035","status":"1","logo":"static/images/20250120104633c23499053.png"},{"name":"自然男声","code":"301034","status":"1","logo":"static/images/2025012010463313dd94609.png"},{"name":"清冷女声","code":"301032","status":"1","logo":"static/images/202501201046334d63e7252.png"},{"name":"清冷男声","code":"301014","status":"1","logo":"static/images/20250120104633368d34812.png"},{"name":"活力男声","code":"301013","status":"1","logo":"static/images/202501201046333b2886509.png"},{"name":"亲切女声","code":"301012","status":"1","logo":"static/images/20250120104633aa01a8463.png"},{"name":"舒适男声","code":"301002","status":"1","logo":"static/images/20250120104633cecba9903.png"},{"name":"大方女声","code":"301027","status":"1","logo":"static/images/2025012010463bqffxvl040.png"},{"name":"温和女声","code":"301026","status":"1","logo":"static/images/2025012010463fwz9m15z98.png"},{"name":"播音男声","code":"301006","status":"1","logo":"static/images/2025012010463l75sf5c8cq.png"},{"name":"播音女声","code":"301004","status":"1","logo":"static/images/2025012010463295injao6i.png"},{"code":"10017","type":"3","name":"芊悦","status":"1","model":"Qwen3-TTS-Flash","voice":"Cherry","recommended":"","url":"static/audio/voice/Cherry.mp3","logo":"static/images/20250120104633b12b94441.png"},{"code":"10018","type":"3","name":"苏瑶","status":"1","model":"Qwen3-TTS-Flash","voice":"Serena","recommended":"","url":"static/audio/voice/Serena.mp3","logo":"static/images/20250120104633c23499053.png"},{"code":"10019","type":"3","name":"千雪","status":"1","model":"Qwen3-TTS-Flash","voice":"Chelsie","recommended":"","url":"static/audio/voice/Chelsie.mp3","logo":"static/images/202501201046334d63e7252.png"},{"code":"10020","type":"3","name":"茉兔","status":"1","model":"Qwen3-TTS-Flash","voice":"Momo","recommended":"","url":"static/audio/voice/Momo.mp3","logo":"static/images/20250120104633aa01a8463.png"},{"code":"10021","type":"3","name":"十三","status":"1","model":"Qwen3-TTS-Flash","voice":"Vivian","recommended":"","url":"static/audio/voice/Vivian.mp3","logo":"static/images/2025012010463bqffxvl040.png"},{"code":"10022","type":"3","name":"四月","status":"1","model":"Qwen3-TTS-Flash","voice":"Maia","recommended":"","url":"static/audio/voice/Maia.mp3","logo":"static/images/2025012010463fwz9m15z98.png"},{"code":"10023","type":"3","name":"墨讲师","status":"1","model":"Qwen3-TTS-Flash","voice":"Elias","recommended":"","url":"static/audio/voice/Elias.mp3","logo":"static/images/20250120104633ec3df6053.png"},{"code":"10024","type":"3","name":"晨煦","status":"1","model":"Qwen3-TTS-Flash","voice":"Ethan","recommended":"","url":"static/audio/voice/Ethan.mp3","logo":"static/images/20250120104633368d34812.png"},{"code":"10025","type":"3","name":"月白","status":"1","model":"Qwen3-TTS-Flash","voice":"Moon","recommended":"","url":"static/audio/voice/Moon.mp3","logo":"static/images/202501201046333b2886509.png"},{"code":"10026","type":"3","name":"凯","status":"1","model":"Qwen3-TTS-Flash","voice":"Kai","recommended":"","url":"static/audio/voice/Kai.mp3","logo":"static/images/2025012010463313dd94609.png"},{"code":"10027","type":"3","name":"不吃鱼","status":"1","model":"Qwen3-TTS-Flash","voice":"Nofish","recommended":"","url":"static/audio/voice/Nofish.mp3","logo":"static/images/20250120104633cecba9903.png"},{"code":"10028","type":"3","name":"田叔","status":"1","model":"Qwen3-TTS-Flash","voice":"Vincent","recommended":"","url":"static/audio/voice/Vincent.mp3","logo":"static/images/2025012010463l75sf5c8cq.png"}]}', `create_time` = 1730688127, `update_time` = 1749540060 WHERE `type` = 'lianlian'  and `name` = 'config';

DELETE FROM `la_system_menu` WHERE `id` IN (611, 612, 613, 614);
INSERT INTO `la_system_menu` VALUES (611, 369, 'A', '激活', '', 0, 'ai_application.device/redeem', '', '', '', '', 0, 1, 0, 1784862891, 1784862891);
INSERT INTO `la_system_menu` VALUES (612, 369, 'A', '转移', '', 0, 'ai_application.device/deviceTransfer', '', '', '', '', 0, 1, 0, 1784862905, 1784862905);
INSERT INTO `la_system_menu` VALUES (613, 368, 'C', '基本配置', '', 0, 'ai_application.device/setting', 'setting', 'ai_application/device/setting/index', '', '', 0, 1, 0, 1785143031, 1785143089);
INSERT INTO `la_system_menu` VALUES (614, 613, 'A', '保存', '', 0, 'ai_application.device/setConfig', '', '', '', '', 0, 1, 0, 1785143109, 1785143109);

UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/20250120101418fff1a0767.png', `name` = '国际贸易合作谈判', `description` = '国际贸易合作，买方需要从卖方采购一批橡胶，双方需要就价格、质量、交货时间等方面进行谈判', `training_target` = '[\"提升商务谈判中的口才表达能力\", \"清晰表达、有效倾听、应对挑战和构建信任等能力\", \"学习如何在各种情况下进行有效的谈判\", \"提高谈判的成功率\"]', `tips` = '[\"请保持专业态度\", \"尊重每位参与者的意见\", \"在压力下保持冷静、自信地应对各种情况\"]', `coach_name` = '李经理', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '公司资深项目经理，具有丰富的项目管理经验和领导能力', `coach_language` = '中文', `coach_voice` = 'Vincent', `practitioner_persona` = '需要靠这单生意来晋升项目经理', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧型\"]', `sort` = 0, `status` = 1, `create_time` = 1737339609, `update_time` = 1737339609, `delete_time` = NULL WHERE `id` = 1;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201032047d4e54616.png', `name` = '茶叶推销', `description` = '一家茶叶生产商的销售代表正在向潜在的零售商推销其高端茶叶产品。双方需要就价格、批发数量、交货时间以及可能的促销活动进行谈判。', `training_target` = '[\"提升销售技巧和产品介绍能力。\", \"学习如何有效地处理客户的异议和问题。\", \"练习如何建立和维护客户关系。\"]', `tips` = '[\"请保持专业和热情的态度\", \"要倾听客户的需求和反馈。\"]', `coach_name` = '王总', `coach_emotion` = 'happy', `coach_intensity` = '100', `coach_persona` = '一位经验丰富的茶叶零售商，对茶叶品质有较高要求，同时注重成本效益。', `coach_language` = '中文', `coach_voice` = 'Nofish', `practitioner_persona` = '茶叶生产商的销售代表', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧型\"]', `sort` = 0, `status` = 1, `create_time` = 1737340548, `update_time` = 1737340548, `delete_time` = NULL WHERE `id` = 2;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201036188d4389434.png', `name` = '加油站燃油添加剂推销', `description` = '加油站推销员向进站加油的顾客介绍并推销一种新型燃油添加剂，该添加剂可以提高燃油效率并保护汽车引擎。', `training_target` = '[\"提升面对面销售技巧和产品知识传递能力。\", \"学习如何处理顾客的疑虑和拒绝。\", \"练习如何建立顾客信任并促成交易。\"]', `tips` = '[\"请保持友好和专业的态度\", \"耐心解答顾客的疑问\", \"尊重顾客的选择\"]', `coach_name` = '赵先生', `coach_emotion` = 'angry', `coach_intensity` = '50', `coach_persona` = '一位经常驾车出差的商务人士，对汽车保养有一定的了解，但对燃油添加剂的效果持怀疑态度。', `coach_language` = '中文', `coach_voice` = 'Kai', `practitioner_persona` = '加油站的推销员', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧型\"]', `sort` = 0, `status` = 1, `create_time` = 1737340681, `update_time` = 1737340681, `delete_time` = NULL WHERE `id` = 3;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201032046b3bd1953.png', `name` = '美容店会员卡推销', `description` = '美容店的推销员正在向一位新顾客介绍会员卡的优惠和服务，试图说服顾客办理会员卡以享受更多的折扣和专属服务。', `training_target` = '[\"提升销售和说服技巧，特别是在强调会员卡优势时。\", \"学习如何根据顾客的需求和偏好定制推销策略。\", \"练习如何处理顾客的犹豫和拒绝，以及如何促成最终的交易。\"]', `tips` = '[\"请保持专业和热情的态度\", \"同时要倾听顾客的需求和反馈\", \"提供个性化的服务建议\"]', `coach_name` = '李女士', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '一位对美容护理有一定了解和需求的顾客，对会员卡感兴趣，但希望了解更多细节和优惠。', `coach_language` = '中文', `coach_voice` = 'Elias', `practitioner_persona` = '美容店的推销员', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1737340786, `update_time` = 1737340786, `delete_time` = NULL WHERE `id` = 4;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/20250120104040c2bfd4461.png', `name` = '儿童钢琴兴趣班招生咨询', `description` = '一家音乐培训机构的销售人员正在向一位家长介绍儿童钢琴兴趣班的课程内容、教学方法和报名优惠，试图说服家长为孩子报名。', `training_target` = '[\"提升教育产品的销售技巧和沟通能力。\", \"学习如何展示课程的优势和特色，以吸引家长的兴趣。\", \"练习如何处理家长的疑问和顾虑，以及如何促成报名。\"]', `tips` = '[\"请保持专业和热情的态度\", \"耐心解答家长的疑\", \"根据孩子的兴趣和需求提供个性化的建议\"]', `coach_name` = '张太太', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '一位对音乐教育有一定了解和兴趣的家长，希望为孩子寻找合适的钢琴学习机会，但对课程效果和费用有所顾虑', `coach_language` = '中文', `coach_voice` = 'Maia', `practitioner_persona` = '音乐培训机构的销售人员', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1737340939, `update_time` = 1737340939, `delete_time` = NULL WHERE `id` = 5;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201040389b9fb9464.png', `name` = '护肤品退款处理', `description` = '一位顾客在购买护肤品后发现产品效果不佳，要求退货退款。客服人员需要妥善处理顾客的不满情绪，同时尽可能维护公司的利益和声誉。', `training_target` = '[\"提升客户服务技巧，包括倾听、同理心和问题解决能力\", \"学习如何在保持公司政策的同时满足顾客的需求\", \"练习如何在压力下保持专业和冷静，以及如何有效沟通\"]', `tips` = '[\"请保持耐心和专业\", \"认真倾听顾客的抱怨\", \"并提供合理的解决方案\"]', `coach_name` = '张女士', `coach_emotion` = 'angry', `coach_intensity` = '100', `coach_persona` = '一位对护肤品有较高期望的顾客，因为产品效果不如预期而感到不满，坚决要求退款。', `coach_language` = '中文', `coach_voice` = 'Vivian', `practitioner_persona` = '护肤品公司的客服代表', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1737341050, `update_time` = 1737341050, `delete_time` = NULL WHERE `id` = 6;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201040384fd501714.png', `name` = '紧急客户伤害赔偿处理', `description` = '在美容店进行免费体验时，由于操作不当，客户在体验过程中受到了伤害。客户要求立即得到妥善的处理和赔偿。', `training_target` = '[\"提升紧急情况下的客户服务技巧，包括倾听、同理心和问题解决能力。\", \"学习如何在保持公司政策的同时满足顾客的需求，处理客户的身体伤害赔偿。\", \"练习如何在压力下保持专业和冷静，以及如何有效沟通。\"]', `tips` = '[\"请保持耐心和专业\", \"认真倾听顾客的抱怨\", \"提供合理的解决方案\"]', `coach_name` = '陈女士', `coach_emotion` = 'angry', `coach_intensity` = '100', `coach_persona` = '一位在美容店免费体验中受伤的顾客，对服务过程中的伤害感到不满，要求美容店负责并给予赔偿。', `coach_language` = '中文', `coach_voice` = 'Momo', `practitioner_persona` = '美容店的客户服务经理', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语言组织能力\", \"专业性\", \"技巧型\"]', `sort` = 0, `status` = 1, `create_time` = 1737341162, `update_time` = 1737341162, `delete_time` = NULL WHERE `id` = 7;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201032047d4e54616.png', `name` = '首次到店转化', `description` = '本场景模拟门店销售人员向首次到店的顾客推荐专享会员卡的对话。顾客对店铺和会员卡的内容不太熟悉，销售人员需要通过沟通了解顾客需求，并合适地介绍会员卡的优惠和价值，使顾客愿意办理会员卡。', `training_target` = '[\"练习如何通过友好开场白与顾客建立信任感。\", \"学会使用开放性问题了解顾客需求，避免生硬推销。\", \"掌握会员卡权益的核心卖点，并针对不同类型的顾客提供适合的推荐方案。\"]', `tips` = '[\"通过对话了解顾客需求，比如消费习惯、预算、购物偏好，再进行精准推荐。\", \"突出会员卡能带来的长期优惠，而不是仅强调费用。\", \"推荐时要自然，避免让顾客觉得有压力。\"]', `coach_name` = '张女士', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '首次到店的顾客，对店铺的产品和会员卡不太了解。对是否办理会员卡有所犹豫，希望听到更详细的介绍', `coach_language` = '中文', `coach_voice` = 'Chelsie', `practitioner_persona` = '门店销售员，负责向首次到店的顾客介绍会员卡的优势，并根据顾客的消费需求进行推荐。目标是在自然沟通中提升顾客对会员卡的兴趣，并促成办理。', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语音组织\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1738913293, `update_time` = 1739185459, `delete_time` = NULL WHERE `id` = 8;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/20250120104040c2bfd4461.png', `name` = '客户电话邀约', `description` = '本场景模拟门店销售人员通过电话联系潜在客户，邀请其到店体验服务或参与活动。客户可能对店铺有所了解，但尚未决定是否来店。销售人员需要通过电话沟通，吸引客户的兴趣，并成功安排到店时间。', `training_target` = '[\"快速建立信任感，避免客户反感。\", \"突出活动或服务的吸引力，提高客户到店意愿。\", \"避免单一时间邀约失败。\"]', `tips` = '[\"电话时间有限，要在前10秒内引起客户兴趣\", \"避免让客户思考太久\", \"如果客户明确拒绝，不要强求\", \"成功邀约后，重复时间和地点，并表达期待\"]', `coach_name` = '李先生', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '是潜在客户，曾留下过联系方式，可能对店铺有一定兴趣，但尚未决定是否到店', `coach_language` = '中文', `coach_voice` = 'Moon', `practitioner_persona` = '门店销售员，通过电话联系潜在客户，介绍店铺活动或服务，并成功邀约客户到店。需要掌握电话沟通技巧，提升邀约成功率。', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语音组织\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1738913417, `update_time` = 1739185527, `delete_time` = NULL WHERE `id` = 9;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/202501201032002a14d9893.png', `name` = '投诉退款挽单', `description` = '本场景模拟客户因不满意产品或服务而要求退款，销售人员需要通过有效的沟通安抚客户情绪，了解具体问题，并尝试提供解决方案，争取挽回订单或减少损失。', `training_target` = '[\"在安抚客户的同时保持专业态度。\", \"学会深入挖掘客户不满的核心原因\", \"掌握不同类型的挽回策略\"]', `tips` = '[\"先倾听，再回应\", \"共情安抚，降低客户怒气\", \"找到核心问题，提供解决方案\"]', `coach_name` = '王女士', `coach_emotion` = 'angry', `coach_intensity` = '200', `coach_persona` = '对产品或服务不满的客户，情绪可能有所波动', `coach_language` = '中文', `coach_voice` = 'Serena', `practitioner_persona` = '负责处理客户投诉及退款请求，并尝试挽回订单。目标是在保持良好客户关系的基础上，减少退款带来的损失，提高客户满意度。', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语音组织\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1738913524, `update_time` = 1739185501, `delete_time` = NULL WHERE `id` = 10;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/20250120104040421e46861.png', `name` = '线下门店顾客接待 SOP', `description` = '模拟线下门店销售人员接待进店顾客的完整流程，从顾客进店、交流互动、产品推荐到最终促成成交或维护客户关系。练习者需掌握高效、友好的接待技巧，提升顾客体验并促进销售转化。', `training_target` = '[\"掌握专业、热情的迎宾礼仪，第一时间给顾客留下良好印象。\", \"学会通过观察和提问，快速了解顾客需求。\", \"提高应对顾客疑虑和异议的能力，增强客户信任。\"]', `tips` = '[\"主动迎宾，但不过度热情\", \"观察顾客类型\", \"即便顾客未购买，也留下好印象\"]', `coach_name` = '张女士', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '进店顾客，对店铺或产品感兴趣，但需求尚不明确', `coach_language` = '中文', `coach_voice` = 'Cherry', `practitioner_persona` = '门店销售人员，负责接待顾客、提供专业建议，并促成成交。需在保证良好客户体验的基础上，提升销售转化率。', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语音组织\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 1, `create_time` = 1738914965, `update_time` = 1739185430, `delete_time` = NULL WHERE `id` = 11;
UPDATE `la_ll_scene` SET `user_id` = 0, `logo` = 'static/images/20250120103619c09582682.png', `name` = '首次到店专享会员卡推荐', `description` = '本场景模拟门店销售人员向首次到店的顾客推荐专享会员卡。顾客初次到店，可能对店铺和会员卡都不太了解，销售人员需要通过友好、专业的介绍，向顾客推荐会员卡，突显其专享优惠和长期价值，最终促成顾客办理会员卡。', `training_target` = '[\"掌握与顾客建立初步信任\", \"练习应对顾客的异议和疑虑\", \"利用促销或赠品等策略增加会员卡吸引力\"]', `tips` = '[\"通过提问了解顾客的消费习惯或需求\", \"避免单纯强调当下的优惠，而是介绍会员卡能带来的长期权益\", \"即便顾客最后未办理会员卡，也要礼貌告别，留下良好印象\"]', `coach_name` = '李女士', `coach_emotion` = 'neutral', `coach_intensity` = '100', `coach_persona` = '首次到店的顾客，对店铺或会员卡不太了解，可能对会员卡办理产生疑虑。', `coach_language` = '中文', `coach_voice` = 'Cherry', `practitioner_persona` = '门店销售员，负责向首次到店的顾客介绍专享会员卡的优惠和权益，并尽可能促成顾客办理。目标是让顾客感受到会员卡的长远价值，从而建立品牌忠诚度。', `analysis_report_config` = '[\"说服力\", \"逻辑性\", \"语音组织\", \"专业性\", \"技巧性\"]', `sort` = 0, `status` = 0, `create_time` = 1738915070, `update_time` = 1741655104, `delete_time` = NULL WHERE `id` = 12;