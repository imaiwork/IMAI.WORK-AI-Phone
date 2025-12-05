CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_flow`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT COMMENT '流程ID',
    `flow_name`   varchar(60) NOT NULL COMMENT '流程名称',
    `status`      tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '流程状态 0-关闭 1-开启',
    `user_id`     int(11) NOT NULL COMMENT '创建人ID',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY           `sop_user_id` (`user_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流流程表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_push`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT COMMENT '流程推送ID',
    `push_name`   varchar(60)  NOT NULL COMMENT '推送名称',
    `push_day`    varchar(60)  NOT NULL COMMENT '初始推送日期',
    `status`      tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态 0-未配置,1-暂停,2-开启',
    `push_type`   tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '推送类型 0-群发任务(主动推送),1-sop(被动触发推送)',
    `type`        tinyint(3) NOT NULL DEFAULT '0' COMMENT '类型 -1-待选择,0-群发任务,1-流程推送,2-阶段推送,3-生日推送,4-节日推送',
    `all_day`     smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '总推送天数',
    `user_id`     int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `flow_id`     varchar(255) NOT NULL DEFAULT '' COMMENT '所属流程ID',
    `stage_id`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'sop_sub_stage子阶段ID',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    `is_publish_edit` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '编辑状态 1-不可编辑,2-可编辑',
    PRIMARY KEY (`id`) USING BTREE,
    KEY           `sop_user_id` (`user_id`) USING BTREE,
    KEY           `sop_stage_id` (`stage_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_push_content`
(
    `id`             int(11) NOT NULL AUTO_INCREMENT COMMENT '流程ID',
    `content`        text         NOT NULL COMMENT '推送内容',
    `extend_content` varchar(300) NOT NULL COMMENT '拓展字段',
    `sort`           int(11) NOT NULL DEFAULT '0' COMMENT '权重， 愈大 排序在前',
    `user_id`        int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `push_time_id`   int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推送时间ID',
    `push_id`        int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推送ID',
    `create_time`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time`    int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY              `sop_user_id` (`user_id`) USING BTREE,
    KEY              `sop_push_id` (`push_id`) USING BTREE,
    KEY              `sop_push_time_id` (`push_time_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送内容表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_push_log`
(
    `id`             int(11) NOT NULL AUTO_INCREMENT COMMENT '推送记录ID',
    `member_id`      int(10) unsigned NOT NULL DEFAULT '0' COMMENT '成员ID',
    `user_id`        int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `push_id`        int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推送ID',
    `content_id`     int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推送内容ID',
    `content`        json        NOT NULL COMMENT '推送内容',
    `push_real_day`  varchar(60) NOT NULL DEFAULT '' COMMENT '推送日期',
    `push_real_time` time        NOT NULL DEFAULT '00:00:00' COMMENT '实际推送时间',
    `status`         tinyint(1) NOT NULL DEFAULT '0' COMMENT '推送结果：0-待推送，1-成功，2-失败',
    `create_time`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time`    int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY              `sop_user_id` (`user_id`) USING BTREE,
    KEY              `sop_push_id` (`push_id`) USING BTREE,
    KEY              `sop_content_id` (`content_id`) USING BTREE,
    KEY              `sop_member_id` (`member_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送记录表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_push_member`
(
    `id`              int(11) NOT NULL AUTO_INCREMENT,
    `wechat_id`       varchar(128) NOT NULL COMMENT '推送者微信id（创建人）',
    `friend_id`       varchar(128) NOT NULL COMMENT '接收者微信id',
    `nickname`        varchar(128) NOT NULL DEFAULT '' COMMENT '好友昵称',
    `remark`          varchar(256) NOT NULL DEFAULT '' COMMENT '备注',
    `avatar`          varchar(256) NOT NULL DEFAULT '' COMMENT '头像',
    `status`          tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-未选择 1-已选择',
    `user_id`         int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `push_id`         int(10) unsigned DEFAULT '0' COMMENT '推送ID',
    `flow_id`         int(10) unsigned DEFAULT '0' COMMENT '用户所处流程id',
    `stage_id`        int(10) unsigned DEFAULT '0' COMMENT '用户所处阶段id',
    `join_flow_time`  int(10) unsigned NOT NULL DEFAULT '0' COMMENT '进入流程时间',
    `join_stage_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '进入阶段时间',
    `create_time`     int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time`     int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time`     int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY               `sop_wechat_id` (`wechat_id`),
    KEY               `sop_friend_id` (`friend_id`),
    KEY               `sop_user_id` (`user_id`) USING BTREE,
    KEY               `sop_flow_id` (`flow_id`) USING BTREE,
    KEY               `sop_stage_id` (`stage_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送人员表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_push_time`
(
    `id`            int(11) NOT NULL AUTO_INCREMENT COMMENT '流程ID',
    `order_day`     smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序天数',
    `user_id`       int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
    `push_day`      varchar(60) NOT NULL DEFAULT '' COMMENT '初始推送日期',
    `push_real_day` varchar(60) NOT NULL DEFAULT '' COMMENT '实际推送日期',
    `push_id`       int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推送ID',
    `push_time`     time        NOT NULL DEFAULT '00:00:00' COMMENT '推送时间',
    `create_time`   int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time`   int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time`   int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY             `sop_user_id` (`user_id`) USING BTREE,
    KEY             `sop_push_id` (`push_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推送时间表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_stage_trigger`
(
    `id`                int(11) NOT NULL AUTO_INCREMENT COMMENT '触发条件ID',
    `flow_id`           int(11) NOT NULL COMMENT '所属流程ID',
    `stage_id`          int(11) NOT NULL COMMENT '所属阶段ID',
    `match_type`        tinyint(3) unsigned NOT NULL COMMENT '匹配类型 0-无 1-动作匹配 2-聊天内容匹配',
    `action_type`       tinyint(3) unsigned DEFAULT NULL COMMENT '动作类型 1-刚加好友',
    `chat_match_mode`   tinyint(3) unsigned DEFAULT NULL COMMENT '聊天匹配模式 1-模糊匹配 2-精确匹配',
    `chat_match_object` tinyint(3) unsigned DEFAULT NULL COMMENT '聊天匹配对象 1-AI回复 2-客户回复',
    `chat_keywords`     varchar(255) DEFAULT NULL COMMENT '聊天关键词',
    `status`            tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0-禁用 1-启用',
    `user_id`           int(11) NOT NULL COMMENT '创建人ID',
    `create_time`       int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time`       int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time`       int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY                 `sop_stage_id` (`stage_id`) USING BTREE,
    KEY                 `sop_user_id` (`user_id`) USING BTREE,
    KEY                 `sop_flow_id` (`flow_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='阶段触发条件表(流程子阶段)';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_sub_flow_remind`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `flow_id`     int(11) NOT NULL COMMENT '所属流程ID',
    `stage_id`    int(11) NOT NULL COMMENT 'sop_sub_stage子阶段ID',
    `user_id`     int(11) NOT NULL COMMENT '创建人ID',
    `content`     varchar(300) NOT NULL COMMENT '内容',
    `status`      tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '提醒规则 0-停留,1-未联系',
    `judgment`    tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '判断时间(天)',
    `send_time`   time         NOT NULL DEFAULT '00:00:00' COMMENT '发送时间',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY           `sop_user_id` (`user_id`) USING BTREE,
    KEY           `sop_stage_id` (`stage_id`) USING BTREE,
    KEY           `sop_flow_id` (`flow_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='跟进提醒表(流程子阶段)';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_sop_sub_stage`
(
    `id`             int(11) NOT NULL AUTO_INCREMENT COMMENT '阶段ID',
    `flow_id`        int(11) NOT NULL COMMENT '所属流程ID',
    `user_id`        int(11) NOT NULL COMMENT '创建人ID',
    `sub_stage_name` varchar(32) NOT NULL COMMENT '阶段名称',
    `status`         tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '阶段状态 0-关键,1-警示,2-其他',
    `sort`           int(11) NOT NULL DEFAULT '0' COMMENT '权重， 愈大 排序在前',
    `create_time`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time`    int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `delete_time`    int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY              `sop_flow_id` (`flow_id`) USING BTREE,
    KEY              `sop_user_id` (`user_id`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流子阶段表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_tag` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `tag_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标签名称',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `uniq_user_id_tag_name` (`user_id`,`tag_name`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信标签表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_tag_strategy` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `match_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '匹配模式 0: 模糊匹配 1：精确匹配',
    `match_mode` tinyint(1) NOT NULL DEFAULT '0' COMMENT '匹配对象模式 0：客户 1：AI',
    `match_keywords` json DEFAULT NULL COMMENT '匹配关键词',
    `tag_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标签名称',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信自动打标签策略表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_accept_friend_strategy` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `is_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用 0: 否 1：是',
    `accept_numbers` int NOT NULL DEFAULT '0' COMMENT '当日接受好友数量上限',
    `interval_time` int NOT NULL DEFAULT '0' COMMENT '添加好友的间隔时间（分钟）',
    `wechat_ids` json DEFAULT NULL COMMENT '执行微信ID集合',
    `accept_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '接受好友类型 0: 不限 1：来源',
    `accept_source` json DEFAULT NULL COMMENT '接受好友来源集合',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信自动通过好友策略表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_friend_tag` (
    `wechat_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '微信ID',
    `friend_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '好友ID',
    `tag_id` int unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
    PRIMARY KEY (`wechat_id`,`friend_id`,`tag_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信好友标签中间表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_circle_task` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `wechat_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信ID',
    `task_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '唯一任务id',
    `task_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '任务类型 0：立即执行 1：定时执行',
    `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '内容',
    `attachment_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '附件类型 0: 纯文本 1：图片 2：短视频 3：长视频 4：链接 5：小程序',
    `attachment_content` json DEFAULT NULL COMMENT '附件内容',
    `comment` json DEFAULT NULL COMMENT '评论',
    `send_time` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '发送时间',
    `finish_time` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '完成时间',
    `send_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送状态 0: 待执行 1：执行中 2：执行完成 3：执行失败 4：暂停中',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信自动发送朋友圈任务表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_circle_reply_strategy` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `is_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用 0: 否 1：是',
    `reply_numbers` int NOT NULL DEFAULT '0' COMMENT '当日朋友圈评论上限',
    `interval_time` int NOT NULL DEFAULT '0' COMMENT '朋友圈评论间隔时间（分钟）',
    `next_reply_day` int NOT NULL DEFAULT '0' COMMENT '下一次评论间隔天数',
    `tag_ids` json DEFAULT NULL COMMENT '执行标签组集合',
    `prompt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '提示词',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信自动评论朋友圈策略表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_circle_like_strategy` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `is_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用 0: 否 1：是',
    `reply_numbers` int NOT NULL DEFAULT '0' COMMENT '当日朋友圈点赞上限',
    `interval_time` int NOT NULL DEFAULT '0' COMMENT '朋友圈点赞间隔时间（分钟）',
    `tag_ids` json DEFAULT NULL COMMENT '执行标签组集合',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信自动点赞朋友圈策略表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_media_group` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `group_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分组名称',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信素材库分组表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_media_file` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `group_ids` json DEFAULT NULL COMMENT '分组ID',
    `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名称',
    `file_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '文件类型 0：图片 1：视频 2：链接 3：小程序 4：文件',
    `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件地址',
    `ext_info` json DEFAULT NULL COMMENT '扩展信息',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信素材库文件表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_accept_friend_log` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `wechat_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信ID',
    `friend_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '好友ID',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信自动通过好友策略表';


CREATE TABLE IF NOT EXISTS `la_ai_wechat_log` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `wechat_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信ID',
    `friend_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '好友ID',
    `log_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '日志类型 0：通过好友 1：朋友圈评论 2：朋友圈点赞',
    `create_time` int DEFAULT NULL COMMENT '创建时间',
    `update_time` int DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微信日志表';

INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'volc_img_to_img_v2', 2012, '算力/张', '图生图-Seedream', 30, 'Doubao模型图生图每张图片约消耗30算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'volc_txt_to_img_v2', 2013, '算力/张', '文生图-Seedream', 30, 'Doubao模型文生图每张图片约消耗30算力', 1, NULL, NULL);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'doubao_txt_to_video', 2014, '算力/秒', '文生视频-Seedance 1.0 pro', 20, 'Seedance 1.0 pro模型文生视频每秒约消耗20算力', 1, NULL, NULL);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'doubao_img_to_video', 2015, '算力/秒', '图生视频-Seedance 1.0 pro', 20, 'Seedance 1.0 pro模型图生视频每秒约消耗20算力', 1, NULL, NULL);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (  'volc_txt_to_posterimg_v2', 2016, '算力/张', '海报图-Seedream', 30, 'Doubao模型文生海报图每张图片约消耗30算力', 1, NULL, NULL);

INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_avatar_chanjing', 5019, '算力/次', '数字人形象-蝉境通道', 0, '（数字人蝉境通道）每次克隆形象，不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_voice_chanjing', 5020, '算力/次', '数字人音色-蝉境通道', 0, '（数字人蝉境通道）每次克隆音色，不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_audio_chanjing', 5021, '算力/秒', '数字人音频-蝉境通道', 1, '（数字人蝉境通道）每次合成音频时，1秒约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_video_chanjing', 5022, '算力/秒', '数字人视频合成-蝉境通道', 2, '（数字人蝉境通道）每次合成视频时，1秒约消耗2算力', 1, 1740799252, 1740799252);

ALTER TABLE `la_draw_video`
    MODIFY COLUMN `model` tinyint(1) NOT NULL DEFAULT 0 COMMENT '模型：0火山引擎即梦AI 1豆包' AFTER `request_id`;

-- sop定时任务
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`) VALUES ('AI微信SOP消息推送', 1, 0, '', 'ai_wechat_sop_cron', '', 1, '* * * * *');

ALTER TABLE `la_human_anchor`
    ADD COLUMN `width` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '宽',
ADD COLUMN `height` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '高';


ALTER TABLE `la_human_video_task`
    ADD COLUMN `width` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '宽' ,
ADD COLUMN `height` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '高';



UPDATE `la_dev_crontab` SET `name` = '小红书待发布任务拉取队列' WHERE `command` = 'publish_detail_cron';

UPDATE `la_config` SET `value` = '{\"channel\":[{\"id\":\"1\",\"name\":\"hidreamai\",\"status\":\"0\"},{\"id\":\"2\",\"name\":\"即梦general_v21\",\"status\":\"1\"},{\"id\":\"3\",\"name\":\"Seedream\",\"status\":\"1\"},{\"id\":\"4\",\"name\":\"Seedance 1.0 pro\",\"status\":\"1\"}]}'
WHERE `type` = 'hd' AND `name` = 'list';

UPDATE `la_config` SET  `value` =  '{\"channel\":[{\"id\":\"1\",\"name\":\"标准版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"/static/images/human/1.png\",\"status\":\"1\"},{\"id\":\"2\",\"name\":\"极致版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"/static/images/human/2.png\",\"status\":\"1\"},{\"id\":\"4\",\"name\":\"优秘V5\",\"described\":\"满足多场景运用，助力企业打造沉浸式体验\",\"icon\":\"/static/images/human/4.png\",\"status\":\"1\"},{\"id\":\"6\",\"name\":\"优秘V7\",\"described\":\"高度还原，打造独一无二的虚拟代言人\",\"icon\":\"/static/images/human/6.png\",\"status\":\"1\"},{\"id\":\"7\",\"name\":\"禅境\",\"described\":\"为数字化浪潮高频迭代的内容营销提供强劲驱动力\",\"icon\":\"/static/images/human/7.png\",\"status\":\"1\"}],\"voice\":[{\"name\":\"智小敏(女)\",\"code\":\"10000\",\"status\":\"1\"},{\"name\":\"智小柔(女)\",\"code\":\"10001\",\"status\":\"1\"},{\"name\":\"智小满(女)\",\"code\":\"10002\",\"status\":\"1\"},{\"name\":\"爱小芊(女)\",\"code\":\"10003\",\"status\":\"1\"},{\"name\":\"爱小静(女)\",\"code\":\"10004\",\"status\":\"1\"},{\"name\":\"千嶂(男)\",\"code\":\"10005\",\"status\":\"1\"},{\"name\":\"智皓(男)\",\"code\":\"10006\",\"status\":\"1\"},{\"name\":\"爱小杭(男)\",\"code\":\"10007\",\"status\":\"1\"},{\"name\":\"爱小辰(男)\",\"code\":\"10008\",\"status\":\"1\"},{\"name\":\"飞镜(男)\",\"code\":\"10009\",\"status\":\"1\"}]}'
WHERE `type` = 'model' AND `name` = 'list';




UPDATE `la_model_config` SET   `name` = '数字人形象-优秘V7' WHERE `scene` = 'human_avatar_ymt';
UPDATE `la_model_config` SET   `name` = '数字人音色-优秘V7'  WHERE `scene` = 'human_voice_ymt';
UPDATE `la_model_config` SET   `name` = '数字人音频-优秘V7'  WHERE `scene` = 'human_audio_ymt';
UPDATE `la_model_config` SET   `name` = '数字人视频-优秘V7'  WHERE `scene` = 'human_video_ymt';

UPDATE `la_model_config` SET   `name` = '数字人形象-优秘V5' WHERE `scene` = 'human_avatar_ym';
UPDATE `la_model_config` SET   `name` = '数字人音色-优秘V5'  WHERE `scene` = 'human_voice_ym';
UPDATE `la_model_config` SET   `name` = '数字人音频-优秘V5'  WHERE `scene` = 'human_audio_ym';
UPDATE `la_model_config` SET   `name` = '数字人视频-优秘V5'  WHERE `scene` = 'human_video_ym';


INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (410, 309, 'A', '保存', '', 0, 'ai_application.chat/setConfig', '', '', '', '', 0, 1, 0, 1753238034, 1753238082);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (411, 253, 'A', '编辑', '', 0, 'ai_application.digital_human/edit', '', '', '', '', 0, 1, 0, 1753238772, 1753238801);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (412, 318, 'A', '保存', '', 0, 'ai_application.meeting_minutes/setConfig', '', '', '', '', 0, 1, 0, 1753239218, 1753239218);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (413, 265, 'A', '编辑', '', 0, 'ai_application.mind_map/edit', '', '', '', '', 0, 1, 0, 1753239707, 1753239745);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (414, 269, 'A', '编辑', '', 0, 'ai_application.draw_sd.record/edit', '', '', '', '', 0, 1, 0, 1753240109, 1753240109);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (415, 331, 'A', '保存', '', 0, 'ai_application.lp/setConfig', '', '', '', '', 0, 1, 0, 1753241158, 1753241240);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (416, 407, 'A', '保存', '', 0, 'ai_application.redbook/setConfig', '', '', '', '', 0, 1, 0, 1753241392, 1753241392);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (422, 400, 'A', '保存', '', 0, 'ai_application.live/setConfig', '', '', '', '', 0, 1, 0, 1753241470, 1753241470);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (423, 236, 'A', '保存', '', 0, 'finance.marketing.recharge/setConfig', '', '', '', '', 0, 1, 0, 1753241803, 1753242059);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (424, 392, 'A', '保存', '', 0, 'cardcode.cardCode/setConfig', '', '', '', '', 0, 1, 0, 1753242304, 1753242304);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (425, 317, 'A', '保存', '', 0, 'setting.service/setConfig', '', '', '', '', 0, 1, 0, 1753242573, 1753242626);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (426, 316, 'A', '保存', '', 0, 'setting.activate/setConfig', '', '', '', '', 0, 1, 0, 1754020840, 1754020878);

UPDATE `la_system_menu` SET `pid` = 255, `type` = 'C', `name` = '应用配置', `icon` = '', `sort` = 2, `perms` = 'ai_application.meeting_minutes/setting', `paths` = 'setting', `component` = 'ai_application/meeting_minutes/setting/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1736835838, `update_time` = 1753239203 WHERE `id` = 318;
UPDATE `la_system_menu` SET `pid` = 257, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.meeting_minutes.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732071226, `update_time` = 1753239815 WHERE `id` = 261;
UPDATE `la_system_menu` SET `pid` = 257, `type` = 'A', `name` = '详情', `icon` = '', `sort` = 0, `perms` = 'ai_application.meeting_minutes.record/detail', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1736837756, `update_time` = 1753239836 WHERE `id` = 320;
UPDATE `la_system_menu` SET `pid` = 255, `type` = 'C', `name` = '会议详情', `icon` = '', `sort` = 0, `perms` = 'ai_application.meeting_minutes.record/detail', `paths` = 'detail', `component` = 'ai_application/meeting_minutes/record/detail', `selected` = '/ai_application/meeting_minutes/record', `params` = '', `is_cache` = 0, `is_show` = 0, `is_disable` = 0, `create_time` = 1736835931, `update_time` = 1753239988 WHERE `id` = 319;
UPDATE `la_system_menu` SET `pid` = 269, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_sd.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732072899, `update_time` = 1753240064 WHERE `id` = 270;
UPDATE `la_system_menu` SET `pid` = 274, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1732073513, `update_time` = 1753240414 WHERE `id` = 276;
UPDATE `la_system_menu` SET `pid` = 297, `type` = 'A', `name` = '新增', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.case/add', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735128007, `update_time` = 1753240452 WHERE `id` = 298;
UPDATE `la_system_menu` SET `pid` = 297, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.case/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735128024, `update_time` = 1753240456 WHERE `id` = 299;
UPDATE `la_system_menu` SET `pid` = 297, `type` = 'A', `name` = '编辑', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.case/edit', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735128034, `update_time` = 1753240461 WHERE `id` = 300;
UPDATE `la_system_menu` SET `pid` = 302, `type` = 'A', `name` = '新增', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.lists/add', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735198847, `update_time` = 1753240869 WHERE `id` = 303;
UPDATE `la_system_menu` SET `pid` = 302, `type` = 'A', `name` = '编辑', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.lists/edit', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735198858, `update_time` = 1753240874 WHERE `id` = 304;
UPDATE `la_system_menu` SET `pid` = 302, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_model.lists/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735198868, `update_time` = 1753240877 WHERE `id` = 305;
UPDATE `la_system_menu` SET `pid` = 403, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.draw_video.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1752146924, `update_time` = 1753240952 WHERE `id` = 404;
UPDATE `la_system_menu` SET `pid` = 323, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.lp.scene/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1737012600, `update_time` = 1753241016 WHERE `id` = 324;
UPDATE `la_system_menu` SET `pid` = 328, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.lp.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1737012913, `update_time` = 1753241097 WHERE `id` = 330;
UPDATE `la_system_menu` SET `pid` = 352, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.interview.job/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1741081351, `update_time` = 1753241270 WHERE `id` = 354;
UPDATE `la_system_menu` SET `pid` = 356, `type` = 'A', `name` = '删除', `icon` = '', `sort` = 0, `perms` = 'ai_application.interview.record/delete', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1741081755, `update_time` = 1753241324 WHERE `id` = 359;
UPDATE `la_system_menu` SET `pid` = 231, `type` = 'C', `name` = '消耗记录', `icon` = '', `sort` = 9, `perms` = 'finance.marketing/consume', `paths` = 'consume', `component` = 'marketing/consume/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735873268, `update_time` = 1753242039 WHERE `id` = 306;
UPDATE `la_system_menu` SET `pid` = 231, `type` = 'C', `name` = '套餐设置', `icon` = '', `sort` = 2, `perms` = 'finance.marketing/recharge', `paths` = 'recharge', `component` = 'marketing/recharge/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1723713891, `update_time` = 1753242052 WHERE `id` = 236;
UPDATE `la_system_menu` SET `pid` = 231, `type` = 'C', `name` = '套餐详情', `icon` = '', `sort` = 2, `perms` = 'finance.marketing.rp/add:edit', `paths` = 'edit', `component` = 'marketing/recharge/edit', `selected` = '/marketing/recharge', `params` = '', `is_cache` = 0, `is_show` = 0, `is_disable` = 0, `create_time` = 1723714803, `update_time` = 1753242088 WHERE `id` = 237;
UPDATE `la_system_menu` SET `pid` = 231, `type` = 'C', `name` = '价格配置', `icon` = '', `sort` = 0, `perms` = 'finance.marketing/creditset', `paths` = 'creditset', `component` = 'marketing/creditset/index', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1722648724, `update_time` = 1753242151 WHERE `id` = 232;
UPDATE `la_system_menu` SET `pid` = 232, `type` = 'A', `name` = '保存', `icon` = '', `sort` = 0, `perms` = 'finance.marketing.creditset/save', `paths` = '', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1722648808, `update_time` = 1753242189 WHERE `id` = 233;
UPDATE `la_system_menu` SET `pid` = 28, `type` = 'C', `name` = '客服配置', `icon` = '', `sort` = 89, `perms` = 'setting.setting/service', `paths` = 'service', `component` = 'setting/service', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735960576, `update_time` = 1753242546 WHERE `id` = 317;
UPDATE `la_system_menu` SET `pid` = 28, `type` = 'C', `name` = '系统激活', `icon` = '', `sort` = 250, `perms` = 'setting.setting/activate', `paths` = 'activate', `component` = 'setting/activate', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1735959862, `update_time` = 1754020865 WHERE `id` = 316;

ALTER TABLE `la_sv_setting` ADD COLUMN `model` varchar(255) NULL DEFAULT 'deepseek' COMMENT '模型' AFTER `sort`;
ALTER TABLE `la_sv_robot` ADD COLUMN `model` varchar(255) NULL DEFAULT 'deepseek' AFTER `profile`;
ALTER TABLE `la_ai_wechat_robot` ADD COLUMN `model` varchar(255) NULL DEFAULT 'deepseek' AFTER `answer`;
ALTER TABLE `la_ai_wechat_setting` ADD COLUMN `model` varchar(255) NULL DEFAULT 'deepseek' AFTER `sort`;


INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ( 'chat', 'ai_model', '{\"channel\":[{\"id\":\"1\",\"name\":\"deepseek\"},{\"id\":\"2\",\"name\":\"gpt-4o\"}]}', 1754105075, 1754105075);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'openai_chat', 1003, 'tokens/算力', 'OpenAI聊天', 900, '每900字约消耗1算力', 1, 1740799252, 1740799252);
ALTER TABLE `la_assistants`
MODIFY COLUMN `scene_id` int(11) NOT NULL DEFAULT 0 COMMENT '场景id' ;

UPDATE `la_model_config` SET `code` = 1003 WHERE `scene` = 'openai_chat';