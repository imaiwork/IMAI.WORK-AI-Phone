-- v1.6.2
-- 私域 - 自动打标签策略
DROP TABLE IF EXISTS `la_ai_wechat_tag_strategy`;
CREATE TABLE `la_ai_wechat_tag_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `match_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '匹配模式 0: 模糊匹配 1：精确匹配',
    `match_mode` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '匹配对象模式 0：客户 1：AI',
    `match_keyword` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '匹配关键词',
    `tag_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '标签名称',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信自动打标签策略表';


-- 私域 - 标签表
DROP TABLE IF EXISTS `la_ai_wechat_tag`;
CREATE TABLE `la_ai_wechat_tag` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `tag_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '标签名称',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`),
    UNIQUE KEY `uniq_user_id_tag_name` (`user_id`, `tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信标签表';


-- 私域 - 好友标签中间表
DROP TABLE IF EXISTS `la_ai_wechat_friend_tag`;
CREATE TABLE `la_ai_wechat_friend_tag` (
    `wechat_id` VARCHAR(100) NOT NULL DEFAULT "" COMMENT '微信ID',
    `friend_id` VARCHAR(100) NOT NULL DEFAULT "" COMMENT '好友ID',
    `tag_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标签ID',
    PRIMARY KEY (`wechat_id`, `friend_id`, `tag_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信好友标签中间表';

-- 私域 - 自动通过好友策略
DROP TABLE IF EXISTS `la_ai_wechat_accept_friend_strategy`;
CREATE TABLE `la_ai_wechat_accept_friend_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `is_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否启用 0: 否 1：是',
    `accept_numbers` INT(11) NOT NULL DEFAULT 0 COMMENT '当日接受好友数量上限',
    `interval_time` INT(11) NOT NULL DEFAULT 0 COMMENT '添加好友的间隔时间（分钟）',
    `wechat_ids` JSON NULL COMMENT '执行微信ID集合',
    `accept_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '接受好友类型 0: 不限 1：来源',
    `accept_source` JSON NULL COMMENT '接受好友来源集合',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信自动通过好友策略表';

-- 私域 - 自动发送朋友圈任务
DROP TABLE IF EXISTS `la_ai_wechat_circle_task`;
CREATE TABLE `la_ai_wechat_circle_task` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `wechat_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信ID',
    `task_id`  VARCHAR(50)  NOT NULL DEFAULT '' comment '唯一任务id',
    `task_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '任务类型 0：立即执行 1：定时执行',
    `content` TEXT NULL COMMENT '内容',
    `attachment_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '附件类型 0: 纯文本 1：图片 2：短视频 3：长视频 4：链接 5：小程序',
    `attachment_content` JSON NULL COMMENT '附件内容',
    `comment` JSON NULL COMMENT '评论',
    `send_time` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '发送时间',
    `finish_time` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '完成时间',
    `send_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '发送状态 0: 待执行 1：执行中 2：执行完成 3：执行失败 4：暂停中',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信自动发送朋友圈任务表';


-- 私域 - 自动评论策略设置
DROP TABLE IF EXISTS `la_ai_wechat_circle_reply_strategy`;
CREATE TABLE `la_ai_wechat_circle_reply_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `is_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否启用 0: 否 1：是',
    `reply_numbers` INT(11) NOT NULL DEFAULT 0 COMMENT '当日朋友圈评论上限',
    `interval_time` INT(11) NOT NULL DEFAULT 0 COMMENT '朋友圈评论间隔时间（分钟）',
    `next_reply_day` INT(11) NOT NULL DEFAULT 0 COMMENT '下一次评论间隔天数',
    `tag_ids` JSON NULL COMMENT '执行标签组集合',
    `prompt` TEXT NULL COMMENT '提示词',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信自动评论朋友圈策略表';

-- 私域 - 自动点赞策略设置
DROP TABLE IF EXISTS `la_ai_wechat_circle_like_strategy`;
CREATE TABLE `la_ai_wechat_circle_like_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `is_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否启用 0: 否 1：是',
    `reply_numbers` INT(11) NOT NULL DEFAULT 0 COMMENT '当日朋友圈点赞上限',
    `interval_time` INT(11) NOT NULL DEFAULT 0 COMMENT '朋友圈点赞间隔时间（分钟）',
    `tag_ids` JSON NULL COMMENT '执行标签组集合',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信自动点赞朋友圈策略表';

-- 私域 - 微信日志表
DROP TABLE IF EXISTS `la_ai_wechat_log`;
CREATE TABLE `la_ai_wechat_log` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `wechat_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信ID',
    `friend_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '好友ID',
    `log_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '日志类型 0：通过好友 1：朋友圈评论 2：朋友圈点赞',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信日志表';

-- 私域 - 素材库分组表
DROP TABLE IF EXISTS `la_ai_wechat_media_group`;
CREATE TABLE `la_ai_wechat_circle_like_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `group_name` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '分组名称',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信素材库分组表';

-- 私域 - 素材库文件表
DROP TABLE IF EXISTS `la_ai_wechat_media_file`;
CREATE TABLE `la_ai_wechat_circle_like_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `group_ids` JSON NULL COMMENT '分组ID',
    `file_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '文件名称',
    `file_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '文件类型 0：图片 1：视频 2：链接 3：小程序 4：文件',
    `file_url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '文件地址',
    `ext_info` JSON NULL COMMENT '扩展信息',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信素材库文件表';

-- 文件 - 新增字段
ALTER TABLE `la_file` ADD COLUMN `ext` VARCHAR(20) NOT NULL DEFAULT "" COMMENT '问津' AFTER `文件格式`;
ALTER TABLE `la_file` ADD COLUMN `ext_info` JSON NULL COMMENT '扩展信息' AFTER `file_type`;
-- 私域 - 新增字段

-- 提示词修改
UPDATE `la_chat_prompt` SET `prompt_text` = '你的角色是：【角色设定】
企业背景信息是：【企业背景】

消息回复：
结合历史信息，当前需要进行回复的内容：【用户发送的内容】' WHERE `id` = 12;

UPDATE `la_chat_prompt` SET `prompt_text` = '{
	"role": "对话分析助手",
	"description": "你是一位专业的对话分析助手，专注于分析完整对话历史，并在【方向1】、【方向2】、【方向3】、【方向4】和【方向5】五个方向上进行评分和提供改进建议，改进建议需要公正客观且详细具体。",
	"interaction": {
		"instruction": "请根据提供的对话文本，在以下五个方面进行分析并打分（每个方面的得分区间为1-20分），同时为每个方面提供公正客观且详细具体的改进建议，并且只返回分数和建议。”,
		"scene_name": "【场景名称】",
		"dialogue_text": "【对话内容】",
		"response_format": "JSON",
		"response_format_example": "[{
			"dimension": "【方向1】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向2】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向3】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向4】",
			"score": 0,
			"improvement_suggestions": ""
		},
		{
			"dimension": "【方向5】",
			"score": 0,
			"improvement_suggestions": ""
		}]"
	}
}' WHERE `id` = 8;



