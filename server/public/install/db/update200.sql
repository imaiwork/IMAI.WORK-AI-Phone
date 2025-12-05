CREATE TABLE  IF NOT EXISTS `la_sv_account` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
`device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备码',
`account` varchar(128) NOT NULL DEFAULT '' COMMENT '账号',
`account_no` varchar(100) NOT NULL DEFAULT '' COMMENT '账号编号',
`nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称',
`avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
`status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 0: 下线 1: 在线',
`type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '账号类型:3小红书',
`extra` text COMMENT '附加字段内容,json',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='账号表';

CREATE TABLE  IF NOT EXISTS `la_sv_account_contact` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`account` varchar(128) NOT NULL DEFAULT '' COMMENT '账号',
`account_type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '账号类型:3小红书',
`friend_id` varchar(128) NOT NULL DEFAULT '' COMMENT '好友ID',
`friend_no` varchar(128) NOT NULL DEFAULT '' COMMENT '朋友账号',
`nickname` varchar(128) NOT NULL DEFAULT '' COMMENT '好友昵称',
`remark` varchar(256) NOT NULL DEFAULT '' COMMENT '备注',
`gender` int(11) NOT NULL DEFAULT '0' COMMENT '性别（0：未知, 1：男, 2：女）',
`country` varchar(128) DEFAULT NULL COMMENT '国家',
`province` varchar(128) DEFAULT NULL COMMENT '省份',
`city` varchar(128) DEFAULT NULL COMMENT '城市',
`avatar` varchar(256) DEFAULT NULL COMMENT '头像',
`business_remark` varchar(256) DEFAULT NULL COMMENT '业务备注',
`type` int(11) NOT NULL DEFAULT '0' COMMENT '联系人类型',
`label_ids` json DEFAULT NULL COMMENT '标签ID',
`phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
`desc` text COMMENT '描述',
`source` int(11) NOT NULL DEFAULT '0' COMMENT '好友来源 0：未知 1: QQ号 3: 微信号 4|12: QQ好友 8|14: 群聊 10|13: 手机通讯录 15: 手机号 17: 名片 18：附近的人 22|23|24|26|27|28|29：摇一摇 25： 漂流瓶 30：扫一扫 34：公众号 48：雷达 ',
`source_ext` varchar(256) DEFAULT NULL COMMENT '来源扩展信息',
`create_time` int(11) DEFAULT NULL COMMENT '加好友时间',
`is_unusual` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否异常',
`birth_date` varchar(10) NOT NULL DEFAULT '' COMMENT '出生日期',
`contact_address` text COMMENT '联系地址',
`open_ai` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启AI功能 0: 关闭 1: 开启',
`takeover_mode` tinyint(4) NOT NULL DEFAULT '0' COMMENT '接管模式 0: 人工接管 1: AI接管',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='联系人表';


CREATE TABLE  IF NOT EXISTS `la_sv_account_keyword` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`account` varchar(128) NOT NULL DEFAULT '' COMMENT '账号',
`match_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '匹配模式 0: 模糊匹配 1：精确匹配',
`type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '账号类型:3小红书',
`keyword` varchar(255) NOT NULL DEFAULT '' COMMENT '关键词',
`reply` json DEFAULT NULL COMMENT '回复内容',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='账号固定话术表';


CREATE TABLE  IF NOT EXISTS `la_sv_copywriting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`channel` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '生成类型',
`status` tinyint(3) unsigned NOT NULL DEFAULT '4' COMMENT '任务状态:0待处理,1处理中,2成功,3失败,4草稿不启动',
`add_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型:0标题副标题文案,1标题,2副标题3文案',
`type` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '平台类型:3小红书',
`success_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
`error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
`keyword` varchar(100) NOT NULL COMMENT '关键词',
`total_num` tinyint(3) unsigned NOT NULL COMMENT '数量',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='文案表';

CREATE TABLE  IF NOT EXISTS `la_sv_copywriting_content` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
`channel` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '生成类型',
`copywriting_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文案id',
`type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型:1标题,2副标题,3内容',
`content` text NOT NULL COMMENT '关键词',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='文案详情表';


CREATE TABLE  IF NOT EXISTS `la_sv_copywriting_task` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一id',
`channel` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '生成类型',
`copywriting_id` int(11) NOT NULL DEFAULT '0' COMMENT '文案id',
`type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型:1内容,2标题,3副标题',
`status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '任务状态:0待处理,1处理中,2成功,3失败',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`response_content` text COMMENT '返回参数',
`remark` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='文案任务表';


CREATE TABLE  IF NOT EXISTS `la_sv_device` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
`device_model` varchar(100) NOT NULL DEFAULT '' COMMENT '设备型号',
`status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '设备状态 0: 下线 1: 在线',
`device_code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备码',
`sdk_version` varchar(50) NOT NULL DEFAULT '' COMMENT '设备SDK版本',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `unique_device_code` (`device_code`),
KEY `idx_device_code` (`device_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='设备表';

CREATE TABLE  IF NOT EXISTS `la_sv_greet_strategy` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`is_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启打招呼策略 0：关闭 1：开启',
`interval_time` int(11) NOT NULL DEFAULT '1' COMMENT '打招呼间隔时间(单位：分钟)',
`friend_greet_is_reply` tinyint(1) NOT NULL DEFAULT '0' COMMENT '主动打招呼回复类型 0: 关闭 1: 开启',
`greet_after_ai_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打招呼后，是否开启AI接管 0：关闭（人工） 1：开启 (AI)',
`greet_content` json DEFAULT NULL COMMENT '打招呼内容',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='打招呼策略表';


CREATE TABLE  IF NOT EXISTS `la_sv_interaction` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`account` varchar(255) DEFAULT NULL COMMENT '小红书用户id',
`type` tinyint(4) unsigned DEFAULT '3' COMMENT '账号类型3小红书',
`content_type` tinyint(4) DEFAULT '1' COMMENT '内容类型1笔记2收藏3标记',
`title` varchar(255) DEFAULT NULL COMMENT '标题',
`browse_favorited` int(11) DEFAULT '0' COMMENT '浏览收藏',
`liked` int(11) DEFAULT '0' COMMENT '点赞喜欢数',
`comments_count` int(11) DEFAULT '0' COMMENT '评论数',
`extra` varchar(255) DEFAULT NULL COMMENT '扩展字段',
`original_data` text COMMENT '原始数据',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='内容状态表';

CREATE TABLE  IF NOT EXISTS `la_sv_material` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`account` varchar(255) DEFAULT NULL COMMENT '平台账号',
`sort` int(11) DEFAULT '0' COMMENT '排序',
`type` tinyint(4) unsigned DEFAULT '3' COMMENT '类型1个微3小红书',
`content` varchar(255) DEFAULT NULL COMMENT '素材内容',
`m_type` tinyint(4) DEFAULT '0' COMMENT '素材类型0文字,1图片,2视频,3小程序,4链接,5名片',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库';


CREATE TABLE  IF NOT EXISTS `la_sv_post_comment` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`account` varchar(255) DEFAULT NULL COMMENT '小红书用户id',
`type` tinyint(4) unsigned DEFAULT '3' COMMENT '账号类型3小红书',
`post_id` int(11) DEFAULT '0' COMMENT '内容id',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`author_name` varchar(255) DEFAULT NULL COMMENT '评论用户名',
`content` text COMMENT '评论内容',
`timer` varchar(255) DEFAULT NULL COMMENT '评论时间',
`location` varchar(255) DEFAULT NULL COMMENT '地点',
`extra` varchar(255) DEFAULT NULL COMMENT '扩展字段',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论列表';

CREATE TABLE  IF NOT EXISTS `la_sv_private_message` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`account` varchar(255) DEFAULT NULL COMMENT '小红书用户id',
`type` tinyint(4) unsigned DEFAULT '3' COMMENT '账号类型3小红书',
`replay_type` varchar(255) DEFAULT NULL COMMENT '回复对象类型',
`friend_id` varchar(255) DEFAULT NULL COMMENT '私信id',
`avatar` text COMMENT '私信头像',
`author_name` varchar(255) DEFAULT NULL COMMENT '私信用户名',
`message_content` text COMMENT '私信内容',
`message_timer` varchar(255) DEFAULT NULL COMMENT '私信时间',
`new_message_count` int(11) DEFAULT '0' COMMENT '新信息数量',
`customer_type` tinyint(4) unsigned DEFAULT '0' COMMENT '用户类型0个人 1聊天群',
`is_reply` tinyint(4) DEFAULT '0' COMMENT '是否已回复0未回复 1已回复',
`extra` varchar(255) DEFAULT NULL COMMENT '扩展字段',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='私信列表';

CREATE TABLE  IF NOT EXISTS `la_sv_publish_setting` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`name` varchar(255) DEFAULT NULL COMMENT '任务名称',
`accounts` varchar(255) DEFAULT NULL COMMENT '账号集合',
`video_setting_id` int(11) DEFAULT '0' COMMENT '视频集合id',
`type` tinyint(4) unsigned DEFAULT '3' COMMENT '类型 3小红书',
`publish_start` date DEFAULT NULL COMMENT '发布开始时间',
`publish_end` date DEFAULT NULL COMMENT '发布结束时间',
`time_config` varchar(255) DEFAULT NULL COMMENT '每日推送时间设置',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='发布设置表';


CREATE TABLE  IF NOT EXISTS `la_sv_publish_setting_account` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`publish_id` int(11) DEFAULT '0' COMMENT '发布设置id',
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`name` varchar(255) DEFAULT NULL COMMENT '任务名称',
`account` varchar(255) DEFAULT NULL COMMENT '账号id',
`account_type` tinyint(4) unsigned DEFAULT '3' COMMENT '账号类型3小红书',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`video_setting_id` int(11) DEFAULT '0' COMMENT '视频设置id',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0未开启 1运行中 2已完成 3已删除',
`publish_start` date DEFAULT NULL COMMENT '发布开始日期',
`publish_end` date DEFAULT NULL COMMENT '发布结束日期',
`next_publish_time` datetime DEFAULT NULL COMMENT '下一个发布时间',
`count` int(11) DEFAULT '0' COMMENT '发布总数',
`published_count` int(11) DEFAULT '0' COMMENT '已发布数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号发布信息表';

CREATE TABLE  IF NOT EXISTS `la_sv_publish_setting_detail` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`publish_id` int(11) DEFAULT '0' COMMENT '发布设置id',
`publish_account_id` int(11) DEFAULT '0' COMMENT '发布信息表id',
`video_task_id` int(11) DEFAULT '0' COMMENT '视频任务表id',
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`account` varchar(255) DEFAULT NULL COMMENT '账号id',
`account_type` tinyint(4) DEFAULT '0' COMMENT '账号类型3小红书',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`material_id` int(11) DEFAULT '0' COMMENT '视频,图片,文案id',
`material_url` varchar(255) DEFAULT NULL COMMENT '视频,图片utl',
`material_title` varchar(255) DEFAULT NULL COMMENT '发布内容标题',
`material_subtitle` varchar(255) DEFAULT NULL COMMENT '发布内容副标题',
`material_type` tinyint(4) DEFAULT '0' COMMENT '发布内容类型1视频2图片3文案',
`poi` varchar(255) DEFAULT NULL COMMENT '位置信息',
`data_type` tinyint(4) DEFAULT '0' COMMENT '数据类型默认0 1示例数据',
`task_id` varchar(255) DEFAULT NULL COMMENT '任务id',
`praises` int(11) DEFAULT NULL COMMENT '点赞数',
`views` int(11) DEFAULT NULL COMMENT '阅读数',
`extra` text COMMENT '扩展字段',
`platform` tinyint(4) DEFAULT '0' COMMENT '发布平台3小红书',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0未发布1已发布2发布失败3发布中4已删除',
`remark` text COMMENT '备注,保存发布失败原因',
`publish_time` datetime DEFAULT NULL COMMENT '发布时间,内容待发布时间',
`exec_time` int(11) DEFAULT NULL COMMENT '任务执行时间',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='发布内容明细表';


CREATE TABLE  IF NOT EXISTS `la_sv_reply_strategy` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`robot_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`multiple_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '多轮回复类型 0: 逐条回复 1: 合并回复 2：只回复最后一条',
`voice_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启语音回复',
`image_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启图片消息回复',
`image_reply` text COMMENT '图片消息回复的内容',
`stop_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启停止回复',
`stop_keywords` json DEFAULT NULL COMMENT '触发停止回复的关键词',
`number_chat_rounds` int(11) NOT NULL DEFAULT '0' COMMENT '聊天轮数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='回复策略表';

CREATE TABLE  IF NOT EXISTS `la_sv_robot` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`logo` varchar(255) NOT NULL DEFAULT '' COMMENT '机器人logo',
`name` varchar(128) NOT NULL DEFAULT '' COMMENT '机器人名称',
`description` text COMMENT '描述指令',
`company_background` text COMMENT '公司背景',
`profile` text COMMENT '简介',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='机器人表';

CREATE TABLE  IF NOT EXISTS `la_sv_robot_keyword` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`robot_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`match_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '匹配模式 0: 模糊匹配 1：精确匹配',
`keyword` varchar(255) NOT NULL DEFAULT '' COMMENT '关键词',
`reply` json DEFAULT NULL COMMENT '回复内容',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='机器人固定话术表';


CREATE TABLE  IF NOT EXISTS `la_sv_setting` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`account` varchar(128) NOT NULL DEFAULT '' COMMENT '账号',
`remark` varchar(120) NOT NULL DEFAULT '' COMMENT '备注',
`open_ai` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否开启AI功能 0: 关闭 1: 开启',
`takeover_mode` tinyint(4) NOT NULL DEFAULT '0' COMMENT '接管模式 0: 人工接管 1: AI接管',
`user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`takeover_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '接管类型 0: 全部 1: 私聊 2: 群聊',
`robot_id` int(11) unsigned DEFAULT NULL COMMENT '关联机器人ID',
`takeover_range_mode` tinyint(4) NOT NULL DEFAULT '0' COMMENT '接管范围模式 0: 包含 1: 排除',
`sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='账号设置表';


CREATE TABLE  IF NOT EXISTS `la_sv_socket_command` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`platform` varchar(32) DEFAULT '小红书' COMMENT '平台来源',
`type` int(11) DEFAULT NULL COMMENT '命令类型',
`msg` text COMMENT '命令内容',
`create_time` datetime DEFAULT NULL COMMENT '创建时间',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`action` varchar(32) DEFAULT NULL COMMENT '动作',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='socket命令记录表';

CREATE TABLE  IF NOT EXISTS `la_sv_video_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0草稿箱,1待处理,2生成中,3已完成,4失败,5部分完成',
`poi` varchar(100) NOT NULL DEFAULT '' COMMENT '位置信息',
`video_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '视频数量',
`type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '视频类型:3小红书',
`setting_type` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '作品类型:1无 2有',
`speed` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '视频合成速度类型:0闲时,1普通,2插队',
`anchor` text COMMENT '形象,json',
`voice` text COMMENT '音色,json',
`title` text COMMENT '标题,json',
`subtitle` text COMMENT '副标题,json',
`copywriting` text COMMENT '文案,json',
`topic` text COMMENT '话题,json',
`extra` text COMMENT '附加字段内容,json',
`success_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
`error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频设置表';


CREATE TABLE  IF NOT EXISTS `la_sv_video_task` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`type` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '视频类型:3小红书',
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
`title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
`subtitle` varchar(500) NOT NULL DEFAULT '' COMMENT '副标题',
`speed` tinyint(4) unsigned NOT NULL DEFAULT '3' COMMENT '视频合成速度类型:0闲时,1普通,2插队',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`gender` varchar(50) NOT NULL DEFAULT '' COMMENT '性别-male,female',
`model_version` int(11) NOT NULL DEFAULT '1' COMMENT '模型类型 1：标准 2: 极速',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0待处理,1音频结果查询,2音频合成失败,3音频合成成功,4视频频结果查询,5视频合成失败,6视频合成成功',
`audio_type` tinyint(11) NOT NULL DEFAULT '1' COMMENT '驱动类型 1：文案驱动 2：音频驱动',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`video_setting_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频设置id',
`upload_video_url` varchar(255) NOT NULL DEFAULT '' COMMENT '视频链接',
`topic` varchar(200) NOT NULL DEFAULT '' COMMENT '话题',
`poi` varchar(100) NOT NULL DEFAULT '' COMMENT '位置信息',
`anchor_id` varchar(50) NOT NULL DEFAULT '' COMMENT '形象id',
`anchor_name` varchar(200) NOT NULL DEFAULT '' COMMENT '形象名称',
`voice_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音色id',
`voice_name` varchar(200) NOT NULL DEFAULT '' COMMENT '音色名称',
`msg` varchar(2000) NOT NULL DEFAULT '' COMMENT '文字',
`audio_url` varchar(255) NOT NULL DEFAULT '' COMMENT '音频url',
`audio_result_url` varchar(255) NOT NULL DEFAULT '' COMMENT '音频生成url',
`audio_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音频id',
`upload_audio_url` varchar(255) NOT NULL DEFAULT '' COMMENT '上传的语音链接',
`result_id` varchar(255) NOT NULL DEFAULT '' COMMENT '生成的视频id',
`video_result_url` text COMMENT '生成的视频地址',
`extra` text COMMENT '附加字段内容,json',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`remark` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='视频合成任务表';

UPDATE `la_chat_prompt` SET `prompt_name` = "数字人", `prompt_text` = "# Role: 口播文案创作专家'
                                                                   '
## Profile
- language: 中文
- description: 专业的口播文案创作专家，擅长通过深入分析用户需求和话语内容，创作出高质量、具有吸引力和感染力的口播文案。
- background: 拥有丰富的广播电视、网络媒体和企业宣传经验，熟悉多场景、多目标的文案创作，具备敏锐的市场洞察力和语言表达能力。
- personality: 创意思维敏锐、执行力强、注重细节、乐于探索新领域，性格积极主动，善于与目标用户建立情感连接。
- expertise: 口播文案创作、语音广告文案、企业宣传文案、品牌营销文案、跨平台内容创作
- target_audience: 广大广播电视台、网络媒体平台、企业品牌、广告主、目标受众群体

## Skills

1. 解读与创作
   - 深度解读用户话语：通过分析用词、语境、语气，准确判断用户明确或潜在的需求和意图
   - 语境分析：结合用户的背景、场景、目标，推测潜在需求
   - 文案创作：根据明确或推测的意图，选择合适的语言风格、结构逻辑，创作符合口播特点的文案

2. 语言风格适配
   - 主流口语化表达：符合大众语言习惯，避免生硬或过于正式
   - 情感化表达：通过情感词、语调词增强感染力
   - 适配场景：结合场景特点，选择合适的语言风格

3. 文案优化
   - 语言流畅：避免语法错误、重复或冗余
   - 逻辑连贯：确保观点表达清晰，层次分明
   - 内容精准：紧扣核心主题，避免跑题

4. 创作辅助技能
   - 内容策划：提炼核心信息，设计内容结构
   - 文案审核：检查文案质量，优化语言表达
   - 跨平台适配：调整文案格式和语言风格，适配不同平台需求

## Rules

1. 基本原则
   - 专业准确：严格遵守行业规范和创作标准，确保文案质量
   - 关系用户：始终关注用户需求，满足用户预期
   - 文案完整：确保文案内容完整，紧扣主题意图
   - 适配场景：根据场景需求调整文案风格和表达方式

2. 行为准则
   - 解读准确：严格执行用户话语分析和意图判断
   - 创作主导：在意图明确或可推测的情况下，主导文案创作方向
   - 进一步优化：根据反馈和效果评估，持续优化文案表现
   - 保持专业：避免使用非正式或不恰当语言

3. 限制条件
   - 任务专注：仅围绕用户当前需求生成口播文案
   - 文案准确：避免误导或不符合事实的信息
   - 文案适配：根据用户需求选择合适的语言风格
   - 执行规范：严格遵守行业创作规范和用户要求

## Workflows

- 目标: 为用户提供高质量、符合需求的口播文案创作服务

- 步骤 1: 解读用户话语，判断意图是否明确
  - 分析用词和语境
  - 判断用户意图是否清晰
  - 如果意图明确，直接创作；如果不明确，进行合理推测

- 步骤 2: 文案创作
  - 确定创作主题和核心信息
  - 选择合适的语言风格
  - 构建层次分明的文案结构
  - 使用生动、流畅的语言表达

- 步骤 3: 文案优化
  - 检查语言流畅度
  - 确保逻辑连贯
  - 验证内容准确性
  - 适配目标平台

- 预期结果: 输出一段高质量、符合用户需求的口播文案

## OutputFormat

1. 格式规范
   - indentation: 无特殊缩进要求
   - sections: 每部分内容分开，清晰呈现
   - highlighting: 保持自然语言表达，无特殊强调

2. 文案风格
   - 生动表达：使用富有感染力的语言
   - 适配口语：保持自然、易于理解的语调
   - 适配场景：根据不同场景调整表达方式

3. 特殊要求
   - 唯一输出：只输出文案内容
   - 禁止解释：无需提供额外说明或解释
   - 保持简洁：避免冗长的解释和描述

4. 验证规则
   - 语法检查：确保文案无语法错误
   - 逻辑验证：检查层次和连贯性
   - 内容审核：确认内容准确无误
   - 平台适配：确保文案在目标平台上表现良好

## Initialization
作为口播文案创作专家，你必须严格遵守以上规则和工作流程，按照用户需求生成高质量的口播文案。" WHERE `id` = 1;

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('短视频合成', 1, 0, '', 'sv_video_cron', '', 1, '* * * * *', '', 1747722842, '0.03', '2.79', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('音频查询', 1, 0, '', 'query_sv_audio_cron', '', 1, '* * * * *', '', 1747722842, '0.01', '0.9', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('文案查询', 1, 0, '', 'query_sv_copywriting_cron', '', 3, '* * * * *', '', 1747635841, '0.01', '0.9', 1744881498, 1744881498, NULL);


INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (32, 'keyword_to_title', 1101, '算力/条', '标题批量生成', 1, '（标题批量生成）每次批量生成标题时，1条消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (33, 'keyword_to_subtitle', 1102, '算力/条', '副标题批量生成', 1, '（副标题批量生成）每次批量生成副标题时，1条消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (34, 'keyword_to_copywriting', 1102, '算力/条', '文案批量生成', 1, '（文案批量生成）每次批量生成文案时，1条消耗2算力', 1, 1740799252, 1740799252);


INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (368, 195, 'M', 'AI设备', '', 0, '', 'device', '', '', '', 0, 1, 0, 1747033226, 1747033226);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (369, 368, 'C', '设备列表', '', 0, 'ai_application.device/lists', 'lists', 'device/lists', '', '', 0, 1, 0, 1747033323, 1747903421);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (370, 195, 'M', 'AI客服', '', 0, '', 'service', '', '', '', 0, 1, 0, 1747033343, 1747033343);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (371, 370, 'M', '小红书', '', 0, '', 'redbook', '', '', '', 0, 1, 0, 1747033359, 1747033359);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (372, 371, 'C', '账号列表', '', 0, 'ai_application.service.redbook.account/lists', 'account_lists', 'ai_application/service/redbook/account/lists', '', '', 0, 1, 0, 1747033450, 1747034308);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (373, 195, 'M', '智能体', '', 100, '', 'agent', '', '', '', 0, 1, 0, 1747033697, 1747035233);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (374, 373, 'C', '智能体列表', '', 0, 'ai_application.agent/lists', 'lists', 'ai_application/agent/lists', '', '', 0, 1, 0, 1747033756, 1747034762);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (375, 195, 'M', 'AI小红书', '', 0, '', 'redbook', '', '', '', 0, 1, 0, 1747033899, 1747033899);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (376, 375, 'C', '文案列表', '', 0, 'ai_application.redbook.copywriting/lists', 'copywriting_lists', 'ai_application/redbook/copywriting/lists', '', '', 0, 1, 0, 1747033953, 1747034319);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (377, 375, 'C', '创作列表', '', 0, 'ai_application.redbook.creation/lists', 'creation', 'ai_application/redbook/creation/lists', '', '', 0, 1, 0, 1747034512, 1747034512);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (378, 375, 'C', '发布列表', '', 0, 'ai_application.redbook.publish/lists', 'publish', 'ai_application/redbook/publish/lists', '', '', 0, 1, 0, 1747034539, 1747034539);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (379, 374, 'A', '删除', '', 0, 'ai_application.agent/delete', '', '', '', '', 0, 1, 0, 1747903380, 1747903380);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (380, 369, 'A', '删除', '', 0, 'ai_application.device/delete', '', '', '', '', 0, 1, 0, 1747903435, 1747903435);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (382, 376, 'A', '删除', '', 0, 'ai_application.redbook.copywriting/delete', '', '', '', '', 0, 1, 0, 1747903489, 1747903489);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (383, 377, 'A', '删除', '', 0, 'ai_application.redbook.creation/delete', '', '', '', '', 0, 1, 0, 1747903514, 1747903514);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (384, 378, 'A', '删除', '', 0, 'ai_application.redbook.publish/delete', '', '', '', '', 0, 1, 0, 1747903527, 1747903527);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (385, 375, 'C', '创作视频列表', '', 0, 'ai_application.redbook.creation/video_lists', 'video_lists', 'ai_application/redbook/creation/video-lists', '/ai_application/redbook/creation', '', 0, 0, 0, 1747968042, 1747989770);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (386, 375, 'C', '发布视频列表', '', 0, 'ai_application.redbook.publish/record_lists', 'record_lists', 'ai_application/redbook/publish/record-lists', '/ai_application/redbook/publish', '', 0, 0, 0, 1747968089, 1747989792);

DELETE FROM `la_system_menu` WHERE id = 177;
DELETE FROM `la_system_menu` WHERE id = 178;

