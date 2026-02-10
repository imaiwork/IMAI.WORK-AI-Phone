ALTER TABLE `la_sv_lead_scraping_setting`
DROP COLUMN `marker_method`;
ALTER TABLE `la_sv_lead_scraping_setting`
ADD COLUMN `marker_method` json NULL COMMENT '留痕方式1点赞评论2关注3点赞作品4评论作品5收藏作品' AFTER `account_feature`;


CREATE TABLE IF NOT EXISTS `la_sv_wechat_strategy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `is_manual_agree` tinyint(4) DEFAULT '0' COMMENT '手动同意好友1是0否',
  `greet_strategy` tinyint(4) DEFAULT '0' COMMENT '打招呼策略0不打招呼1对方先打招呼后不再打招呼2无论如何都固定打招呼',
  `greet_content` text COMMENT '打招呼内容',
  `paragraph_enable` tinyint(4) DEFAULT '0' COMMENT '分段回复1是0否',
  `multiple_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '多轮回复类型 0: 逐条回复 1: 合并回复 2：只回复最后一条',
  `number_chat_rounds` int(11) NOT NULL DEFAULT '0' COMMENT '聊天轮数',
  `voice_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启语音回复',
  `voice_reply_type` tinyint(4) DEFAULT NULL COMMENT '语音回复类型1不回复2转文字后回复3固定回复',
  `voice_reply` text COMMENT '语音固定回复内容',
  `image_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启图片消息回复',
  `image_reply_type` tinyint(4) DEFAULT '0' COMMENT '图片回复类型1固定回复2ai识别回复3不回复',
  `image_reply` text COMMENT '图片消息回复的内容',
  `stop_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启停止回复',
  `stop_keywords` json DEFAULT NULL COMMENT '触发停止回复的关键词',
  `bottom_enable` tinyint(4) DEFAULT '0' COMMENT '是否开启兜底回复',
  `bottom_reply` text COMMENT '兜底回复',
  `task_name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `account` varchar(255) DEFAULT NULL COMMENT '账号',
  `account_type` tinyint(4) DEFAULT '0' COMMENT '账号类型',
  `task_frep` int(11) DEFAULT '0' COMMENT '任务频率执行几天',
  `custom_date` json DEFAULT NULL COMMENT '自定义日期',
  `time_config` json DEFAULT NULL COMMENT '每日任务执行区间集合',
  `is_free_time` tinyint(4) DEFAULT '0' COMMENT '是否空闲时间1是0否',
  `is_init` tinyint(4) DEFAULT '0' COMMENT '空闲任务是否已初始化0否1是',
  `status` tinyint(4) DEFAULT '0' COMMENT '任务状态0待执行1执行中2执行完成3执行失败',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='微信回复策略表';

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('个微RPA接管空闲时段任务', 1, 0, '', 'wechat_rpa_cron', '', 1, '* * * * *', 'Command \"wechat_rpa_cron\" is not defined.\n\nDid you mean this?\n    device_rpa_cron', 1770204004, '0.03', '0.03', 1770203963, 1770204042, NULL);
UPDATE `la_dev_crontab` SET `status` = 2 WHERE `command` = 'file_status_cron';
UPDATE `la_dev_crontab` SET `status` = 2  WHERE `command` = 'file_chunks_pull_cron';


INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'coze_copywriting', 10203, '算力/条', 'Ai智能发布文案', 1.00, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('douyin_js', 10204, '算力/次', '扫码发布', 10.00, '', 1, 1740799252, 1740799252);


ALTER TABLE `la_sv_device`
ADD COLUMN `mode` enum('root','rpa') NULL DEFAULT 'root' COMMENT '设备模式' AFTER `auto_type`;

CREATE TABLE IF NOT EXISTS  `la_sv_media_manual_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `media_url` text COMMENT '媒体url,json',
  `copywriting` text COMMENT '文案,json',
  `extra` text COMMENT '附加字段内容,json',
  `media_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '媒体数量',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='媒体手动设置表';

CREATE TABLE IF NOT EXISTS  `la_sv_media_manual_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `manual_setting_id` int(11) NOT NULL DEFAULT '0' COMMENT '媒体手动设置表id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `media_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '媒体类型:1视频2图片',
  `media_url` text COMMENT '媒体url',
  `title` text COMMENT '标题',
  `subtitle` text COMMENT '副标题',
  `topic` text COMMENT '话题,json',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0未发布1已发布2发布失败3发布中',
  `poi` text COMMENT '位置',
  `extra` text COMMENT '附加字段内容,json',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='媒体手动详情表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_group` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`name` varchar(255) DEFAULT NULL COMMENT '名称',
`sort` int(11) DEFAULT '0' COMMENT '排序',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='智能体分组';

ALTER TABLE `la_kb_robot`
    ADD COLUMN `group_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '0:未分组';

ALTER TABLE `la_coze_agent`
    ADD COLUMN `group_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '0:未分组';

ALTER TABLE `la_shanjian_video_task`
    ADD COLUMN `duration` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '时长';

ALTER TABLE `la_human_task`
    ADD COLUMN `duration` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '时长';

ALTER TABLE `la_human_video_task`
    ADD COLUMN `duration` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '时长';

ALTER TABLE `la_sv_video_task`
    ADD COLUMN `duration` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 0 COMMENT '时长';

ALTER TABLE `la_auto_needs_analysis`
    ADD COLUMN `step` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:分析 2:报告' AFTER `is_draft`,
    ADD COLUMN `device_code` varchar(255) DEFAULT '' COMMENT '设备号' AFTER `user_id`,
    DROP COLUMN `conversation_id`;

ALTER TABLE `la_auto_device_config`
    DROP COLUMN `conversation_id`;

ALTER TABLE `la_chat_log`
    ADD COLUMN `quotes` text COMMENT '引用的内容';


ALTER TABLE `la_sv_device_circle_like_reply_record`
ADD COLUMN `image` varchar(255) NULL COMMENT '截图' AFTER `hash`;

ALTER TABLE `la_sv_add_wechat_record`
ADD COLUMN `image` varchar(255) NULL COMMENT '截图' AFTER `result`;

ALTER TABLE `la_sv_crawling_manual_task_record`
ADD COLUMN `image` varchar(255) NULL COMMENT '截图' AFTER `result`;

ALTER TABLE `la_sv_lead_scraping_record`
ADD COLUMN `image` varchar(255) NULL COMMENT '截图' AFTER `address`,
ADD COLUMN `hash` varchar(255) NULL AFTER `delete_time`,
ADD COLUMN `likes` int NULL DEFAULT 0 COMMENT '获赞数' AFTER `address`,
ADD COLUMN `fans` int NULL DEFAULT 0 COMMENT '粉丝数' AFTER `likes`,
ADD COLUMN `follows` int NULL DEFAULT 0 COMMENT '关注数' AFTER `fans`,
ADD COLUMN `industry_keyword` varchar(255) NULL COMMENT '行业线索词' AFTER `follows`,
ADD COLUMN `notes` text NULL COMMENT '当前进入笔记内容' AFTER `industry_keyword`,
ADD COLUMN `filter_keyword` varchar(255) NULL COMMENT '评论匹配词' AFTER `notes`,
ADD COLUMN `comment_content` varchar(1000) NULL COMMENT '执行账号评论/私信内容' AFTER `filter_keyword`,
ADD COLUMN `touch_content` text NULL COMMENT '留痕触达内容' AFTER `comment_content`,
MODIFY COLUMN `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '目标用户评论内容' AFTER `hash`;

UPDATE `la_model_config` SET `name` = '文案库AI生成发布文案' WHERE `scene` = 'matrix_copywriting';

ALTER TABLE `la_shanjian_video_task`
    MODIFY COLUMN `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标题' ;
