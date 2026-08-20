CREATE TABLE IF NOT EXISTS `la_sv_group_buy_task` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`group_buy_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '团购类型1收藏夹团购2搜索团购类型',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
`status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务状态0草稿1待执行2执行中3已完成',
`accounts` varchar(500) DEFAULT '' COMMENT '账号集合',
`account_feature` tinyint(3) DEFAULT '0' COMMENT '账号特征0全部1跳过认证号',
`marker_method` json DEFAULT NULL COMMENT '留痕方式：1点赞2关注3评论4私信',
`chat_type` tinyint(3) DEFAULT '1' COMMENT '私信方式：1文字2图片3广告4团购5小店商品',
`like_type` tinyint(3) DEFAULT '1' COMMENT '点赞方式：1头像2视频',
`group_type` varchar(50) DEFAULT NULL COMMENT '团购类型',
`send_num` int(10) DEFAULT '3' COMMENT '发送数量上限',
`radius` smallint(4) DEFAULT '0' COMMENT 'xx公里之内 1km 3km 5km 0代表全城',
`interval_time` smallint(4) DEFAULT '10' COMMENT '触达间隔xx秒',
`watch_time` smallint(4) DEFAULT '10' COMMENT '观看视频xx秒',
`content_publish_day` smallint(4) DEFAULT NULL COMMENT '视频发布时间xx天',
`comment_offset` smallint(4) DEFAULT NULL COMMENT '从第几个评论开始',
`gender` enum('男','女','不限') DEFAULT '不限' COMMENT '性别',
`old` varchar(50) DEFAULT '18-24' COMMENT '年龄',
`region` varchar(50) DEFAULT '不限' COMMENT '地区(省)',
`city` varchar(50) DEFAULT '不限' COMMENT '地区(市)',
`comment_keyword` text COMMENT '评论关键词',
`filter` text COMMENT '评论词筛选',
`nickname_filter` text COMMENT '对方昵称中不包含筛选',
`task_start_time` int(11) DEFAULT NULL COMMENT '任务开始时间',
`task_end_time` int(11) DEFAULT NULL COMMENT '任务结束时间',
`task_frequency` tinyint(3) DEFAULT '1' COMMENT '任务频率，最大值30',
`time_config` varchar(50) DEFAULT NULL COMMENT '每日发送时间设置',
`task_date` varchar(500) DEFAULT NULL COMMENT '任务执行日期',
`persona_id` int(11) DEFAULT '0' COMMENT 'ip人设id',
`task_exec_type` tinyint(3) DEFAULT '0' COMMENT '执行类型1立即执行0定时执行',
`minutes` int(11) DEFAULT '0' COMMENT '执行时长（min）',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购截流任务表';

CREATE TABLE IF NOT EXISTS `la_sv_group_buy_task_account` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`group_buy_id` int(11) NOT NULL DEFAULT '0' COMMENT '团购截流任务id',
`status` tinyint(3) DEFAULT '0' COMMENT '状态0未开启 1运行中 2已完成 3已删除 4暂停中',
`name` varchar(255) DEFAULT NULL COMMENT '任务名称',
`account` varchar(255) DEFAULT NULL COMMENT '账号id',
`account_type` tinyint(3) unsigned DEFAULT '3' COMMENT '账号类型 3小红书4视频号5快手',
`nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`send_start_time` int(11) DEFAULT NULL COMMENT '发布开始日期',
`send_end_time` int(11) DEFAULT NULL COMMENT '发布结束日期',
`count` int(11) DEFAULT '0' COMMENT '发送总数',
`published_count` int(11) DEFAULT '0' COMMENT '已发送数',
`persona_id` int(11) DEFAULT '0' COMMENT 'ip人设id',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_group_buy_id` (`group_buy_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购截流账号表';

CREATE TABLE IF NOT EXISTS `la_sv_group_buy_record` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`group_buy_id` int(11) NOT NULL DEFAULT '0' COMMENT '团购截流id',
`group_buy_account_id` int(11) NOT NULL DEFAULT '0' COMMENT '团购截流账号表id',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0未发送1已发送2发送失败3发送中4已删除',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`account` varchar(255) DEFAULT NULL COMMENT '平台账号id',
`account_name` varchar(255) DEFAULT NULL COMMENT '账号名称',
`account_type` tinyint(4) DEFAULT '0' COMMENT '平台账号类型 3小红书4抖音5快手',
`platform` tinyint(4) DEFAULT '0' COMMENT '发布平台 3小红书4抖音5快手',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`task_id` varchar(255) DEFAULT NULL COMMENT '任务id',
`extra` text COMMENT '扩展字段',
`remark` text COMMENT '备注,保存发布失败原因',
`send_time` datetime DEFAULT NULL COMMENT '发布时间,内容待发布时间',
`exec_time` int(11) DEFAULT NULL COMMENT '任务执行时间',
`pusher_timer` varchar(255) DEFAULT NULL COMMENT '发布时间',
`address` varchar(255) DEFAULT NULL COMMENT '地址',
`likes` varchar(255) DEFAULT '0' COMMENT '获赞数',
`fans` varchar(255) DEFAULT '0' COMMENT '粉丝数',
`follows` varchar(255) DEFAULT '0' COMMENT '关注数',
`industry_keyword` varchar(255) DEFAULT NULL COMMENT '行业线索词',
`note_title` text COMMENT '笔记标题',
`notes` text COMMENT '当前进入笔记内容',
`filter_keyword` varchar(255) DEFAULT NULL COMMENT '评论匹配词',
`comment_content` varchar(1000) DEFAULT NULL COMMENT '执行账号评论/私信内容',
`touch_content` text COMMENT '留痕触达内容',
`image` varchar(255) DEFAULT NULL COMMENT '截图',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
`hash` varchar(255) DEFAULT NULL,
`content` text COMMENT '目标用户评论内容',
PRIMARY KEY (`id`) USING BTREE,
KEY `account_type` (`account_type`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_group_buy_id` (`group_buy_id`) USING BTREE,
KEY `idx_group_buy_account_id` (`group_buy_account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购截流记录表';

CREATE TABLE IF NOT EXISTS `la_sv_group_buy_filter_history` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0',
`filter` json DEFAULT NULL COMMENT '评论词筛选',
`nickname_filter` json DEFAULT NULL COMMENT '昵称筛选',
`comment_keyword` json DEFAULT NULL COMMENT '评论包含关键词',
`number` int(11) DEFAULT '1' COMMENT '数量',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团购截流筛选词历史表';

CREATE TABLE IF NOT EXISTS `la_sv_city_touch_task` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
`status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务状态0草稿1待执行2执行中3已完成',
`accounts` varchar(500) DEFAULT '' COMMENT '账号集合',
`account_feature` tinyint(3) DEFAULT '0' COMMENT '账号特征0全部1跳过认证号',
`marker_method` json DEFAULT NULL COMMENT '留痕方式：1点赞2关注3评论4私信',
`chat_type` tinyint(3) DEFAULT '1' COMMENT '私信方式：1文字2图片3广告4团购5小店商品',
`radius` smallint(4) DEFAULT '1' COMMENT 'xx公里之内',
`interval_time` smallint(4) DEFAULT '10' COMMENT '触达间隔xx秒',
`watch_time` smallint(4) DEFAULT '10' COMMENT '观看视频xx秒',
`gender` enum('男','女','不限') DEFAULT '不限' COMMENT '性别',
`old` varchar(50) DEFAULT '18-24' COMMENT '年龄',
`region` varchar(50) DEFAULT '不限' COMMENT '地区(省)',
`city` varchar(50) DEFAULT '不限' COMMENT '地区(市)',
`send_num` int(10) DEFAULT '3' COMMENT '发送数量上限',
`like_num` int(10) DEFAULT '3' COMMENT '视频点赞数',
`comment_num` int(10) DEFAULT '3' COMMENT '视频最大评论数',
`comment_fans_num` varchar(50) DEFAULT '10-200' COMMENT '评论的目标粉丝数',
`comment_follow_num` varchar(50) DEFAULT '10-200' COMMENT '评论的目标关注数',
`filter` text COMMENT '评论词筛选',
`nickname_filter` text COMMENT '对方昵称中不包含筛选',
`task_start_time` int(11) DEFAULT NULL COMMENT '任务开始时间',
`task_end_time` int(11) DEFAULT NULL COMMENT '任务结束时间',
`task_frequency` tinyint(4) DEFAULT '1' COMMENT '任务频率，最大值30',
`time_config` varchar(50) DEFAULT NULL COMMENT '每日发送时间设置',
`task_date` varchar(500) DEFAULT NULL COMMENT '任务执行日期',
`persona_id` int(11) DEFAULT '0' COMMENT 'ip人设id',
`task_exec_type` tinyint(4) DEFAULT '0' COMMENT '执行类型1立即执行0定时执行',
`minutes` int(11) DEFAULT '0' COMMENT '执行时长（min）',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城视频截流任务表';

CREATE TABLE IF NOT EXISTS `la_sv_city_touch_task_account` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`city_touch_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布设置id',
`status` tinyint(3) DEFAULT '0' COMMENT '状态0未开启 1运行中 2已完成 3已删除 4暂停中',
`name` varchar(255) DEFAULT NULL COMMENT '任务名称',
`account` varchar(255) DEFAULT NULL COMMENT '账号id',
`account_type` tinyint(3) unsigned DEFAULT '3' COMMENT '账号类型 3小红书4视频号5快手',
`nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`send_start_time` int(11) DEFAULT NULL COMMENT '发布开始日期',
`send_end_time` int(11) DEFAULT NULL COMMENT '发布结束日期',
`count` int(11) DEFAULT '0' COMMENT '发送总数',
`published_count` int(11) DEFAULT '0' COMMENT '已发送数',
`persona_id` int(11) DEFAULT '0' COMMENT 'ip人设id',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_city_touch_id` (`city_touch_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城视频截流账号表';

CREATE TABLE IF NOT EXISTS `la_sv_city_touch_record` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`city_touch_id` int(11) NOT NULL DEFAULT '0' COMMENT '同城截流id',
`city_touch_account_id` int(11) NOT NULL DEFAULT '0' COMMENT '同城截流账号表id',
`status` tinyint(3) DEFAULT '0' COMMENT '状态0未发送1已发送2发送失败3发送中4已删除',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`account` varchar(255) DEFAULT NULL COMMENT '平台账号id',
`account_name` varchar(255) DEFAULT NULL COMMENT '账号名称',
`account_type` tinyint(3) DEFAULT '0' COMMENT '平台账号类型 3小红书4抖音5快手',
`platform` tinyint(3) DEFAULT '0' COMMENT '发布平台 3小红书4抖音5快手',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`task_id` varchar(255) DEFAULT NULL COMMENT '任务id',
`extra` text COMMENT '扩展字段',
`remark` text COMMENT '备注,保存发布失败原因',
`send_time` datetime DEFAULT NULL COMMENT '发布时间,内容待发布时间',
`exec_time` int(11) DEFAULT NULL COMMENT '任务执行时间',
`pusher_timer` varchar(255) DEFAULT NULL COMMENT '发布时间',
`address` varchar(255) DEFAULT NULL COMMENT '地址',
`likes` varchar(255) DEFAULT '0' COMMENT '获赞数',
`fans` varchar(255) DEFAULT '0' COMMENT '粉丝数',
`follows` varchar(255) DEFAULT '0' COMMENT '关注数',
`industry_keyword` varchar(255) DEFAULT NULL COMMENT '行业线索词',
`note_title` text COMMENT '笔记标题',
`notes` text COMMENT '当前进入笔记内容',
`filter_keyword` varchar(255) DEFAULT NULL COMMENT '评论匹配词',
`comment_content` varchar(1000) DEFAULT NULL COMMENT '执行账号评论/私信内容',
`touch_content` text COMMENT '留痕触达内容',
`image` varchar(255) DEFAULT NULL COMMENT '截图',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
`hash` varchar(255) DEFAULT NULL,
`content` text COMMENT '目标用户评论内容',
PRIMARY KEY (`id`) USING BTREE,
KEY `account_type` (`account_type`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_city_touch_id` (`city_touch_id`) USING BTREE,
KEY `idx_city_touch_account_id` (`city_touch_account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城视频截流记录表';

CREATE TABLE IF NOT EXISTS `la_sv_city_touch_filter_history` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0',
`filter` json DEFAULT NULL COMMENT '评论词筛选',
`nickname_filter` json DEFAULT NULL COMMENT '昵称筛选',
`number` int(11) DEFAULT '1' COMMENT '数量',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城视频截流筛选词历史表';

CREATE TABLE IF NOT EXISTS `la_sv_city_exposure_task` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(3) NOT NULL DEFAULT '3' COMMENT '任务类型1评论2私信3仅访问',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
`status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '任务状态0草稿1待执行2执行中3已完成',
`accounts` varchar(500) DEFAULT '' COMMENT '账号集合',
`account_feature` tinyint(3) DEFAULT '0' COMMENT '账号特征0全部1跳过认证号',
`radius` tinyint(4) DEFAULT NULL COMMENT 'xx公里之内',
`interval_time` tinyint(4) DEFAULT NULL COMMENT '触达间隔xx秒',
`visit_num` smallint(5) DEFAULT '100' COMMENT '访问数',
`task_start_time` int(11) DEFAULT NULL COMMENT '任务开始时间',
`task_end_time` int(11) DEFAULT NULL COMMENT '任务结束时间',
`task_frequency` tinyint(4) DEFAULT '1' COMMENT '任务频率，最大值30',
`time_config` varchar(50) DEFAULT NULL COMMENT '每日发送时间设置',
`task_date` varchar(500) DEFAULT NULL COMMENT '任务执行日期',
`persona_id` int(11) DEFAULT '0' COMMENT 'ip人设id',
`task_exec_type` tinyint(4) DEFAULT '0' COMMENT '执行类型1立即执行0定时执行',
`minutes` int(11) DEFAULT '0' COMMENT '执行时长（min）',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城曝光任务表';

CREATE TABLE IF NOT EXISTS `la_sv_city_exposure_task_account` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`city_exposure_id` int(11) NOT NULL DEFAULT '0' COMMENT '发布设置id',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0未开启 1运行中 2已完成 3已删除 4暂停中',
`name` varchar(255) DEFAULT NULL COMMENT '任务名称',
`account` varchar(255) DEFAULT NULL COMMENT '账号id',
`account_type` tinyint(3) unsigned DEFAULT '3' COMMENT '账号类型 3小红书4视频号5快手',
`nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`send_start_time` int(11) DEFAULT NULL COMMENT '发布开始日期',
`send_end_time` int(11) DEFAULT NULL COMMENT '发布结束日期',
`count` int(11) DEFAULT '0' COMMENT '发送总数',
`published_count` int(11) DEFAULT '0' COMMENT '已发送数',
`persona_id` int(11) DEFAULT '0' COMMENT 'ip人设id',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_city_exposure_id` (`city_exposure_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城曝光账号表';

CREATE TABLE IF NOT EXISTS `la_sv_city_exposure_record` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '任务类型1评论2私信',
`city_exposure_id` int(11) NOT NULL DEFAULT '0' COMMENT '同城曝光任务id',
`city_exposure_account_id` int(11) NOT NULL DEFAULT '0' COMMENT '同城曝光任务账号表id',
`status` tinyint(4) DEFAULT '0' COMMENT '状态0未发送1已发送2发送失败3发送中4已删除',
`avatar` varchar(255) DEFAULT NULL COMMENT '头像',
`account` varchar(255) DEFAULT NULL COMMENT '平台账号id',
`account_name` varchar(255) DEFAULT NULL COMMENT '账号名称',
`account_type` tinyint(4) DEFAULT '0' COMMENT '平台账号类型 3小红书4抖音5快手',
`platform` tinyint(4) DEFAULT '0' COMMENT '发布平台 3小红书4抖音5快手',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备id',
`task_id` varchar(255) DEFAULT NULL COMMENT '任务id',
`extra` text COMMENT '扩展字段',
`remark` text COMMENT '备注,保存发布失败原因',
`send_time` datetime DEFAULT NULL COMMENT '发布时间,内容待发布时间',
`exec_time` int(11) DEFAULT NULL COMMENT '任务执行时间',
`image` varchar(255) DEFAULT NULL COMMENT '截图',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
`hash` varchar(255) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `account_type` (`account_type`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `idx_user_id` (`user_id`) USING BTREE,
KEY `idx_city_exposure_id` (`city_exposure_id`) USING BTREE,
KEY `idx_city_exposure_account_id` (`city_exposure_account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='同城曝光记录表';

CREATE TABLE IF NOT EXISTS `la_catering_franchise` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目ID',
  `category_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '分类类型:1=本地生活, 2=个人ip, 3=企业服务',
  `title` varchar(128) NOT NULL COMMENT '项目标题',
  `exposure` varchar(11) DEFAULT NULL COMMENT '曝光量',
  `leads` varchar(11) DEFAULT '0' COMMENT '线索数',
  `convert_users` varchar(11) DEFAULT '0' COMMENT '成交客户数',
  `intro` text COMMENT '项目简介',
  `target_users` json DEFAULT NULL COMMENT '目标用户数组',
  `task_types` json NOT NULL COMMENT '任务节点配置（含排序、执行时间）',
  `detail_content` varchar(600) DEFAULT NULL COMMENT '适用场景',
  `detail_task_types` varchar(600) DEFAULT NULL COMMENT '执行动作',
  `detail_users` varchar(600) DEFAULT NULL COMMENT '目标人群',
  `detail_images` json DEFAULT NULL COMMENT '详情页图片数组',
  `detail_videos` json DEFAULT NULL COMMENT '详情页视频数组',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1启用 0停用',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='招商项目表';


CREATE TABLE IF NOT EXISTS `la_tutorial_category` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
`name` VARCHAR(64) NOT NULL COMMENT '分类名称',
`sort` INT DEFAULT '0' COMMENT '排序权重',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态0关闭1开启',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='教程卡片分类表';

CREATE TABLE IF NOT EXISTS `la_tutorial` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
`tutorial_category_id` int(11) NOT NULL COMMENT '分类ID',
`title` varchar(128) NOT NULL COMMENT '卡片主标题',
`main_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '主内容类型：1视频 2图片',
`main_url` varchar(512) NOT NULL DEFAULT '' COMMENT '主内容CDN地址',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态0关闭1开启',
`sub_items` json NOT NULL COMMENT '副列表JSON',
`sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序权重',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='教程卡片表';


ALTER TABLE `la_sv_device_take_over_task` 
ADD COLUMN `task_type` tinyint NULL DEFAULT 2 COMMENT '任务接管类型1回复评论2回复私信3全部回复' AFTER `minutes`,
ADD COLUMN `message_type` tinyint NULL DEFAULT 1 COMMENT '私信回复方式1ai智能回复2固定话术回复3仅点赞' AFTER `task_type`,
ADD COLUMN `message_robot_id` int NULL DEFAULT 0 COMMENT '私信智能体' AFTER `message_type`,
ADD COLUMN `message_speech` json NULL COMMENT '私信固定话术' AFTER `message_robot_id`,
ADD COLUMN `message_number` int NULL DEFAULT 0 COMMENT '私信每个接管用户回复数0不限制' AFTER `message_robot_id`,
ADD COLUMN `comment_type` tinyint NULL DEFAULT 1 COMMENT '互动评论回复方式1ai智能回复2固定话术回复3仅点赞' AFTER `message_number`,
ADD COLUMN `comment_robot_id` int NULL DEFAULT 0 COMMENT '评论智能体' AFTER `comment_type`,
ADD COLUMN `comment_speech` json NULL COMMENT '评论固定话术' AFTER `comment_robot_id`;



ALTER TABLE `la_sv_private_message` 
ADD COLUMN `message_task_type` tinyint NULL DEFAULT 2 COMMENT '消息类型1回复评论2回复私信' AFTER `author_name`;

ALTER TABLE `la_ai_persona_agent_config` 
ADD COLUMN `comment_type` tinyint NULL DEFAULT 1 COMMENT '社媒评论回复方式1ai智能回复2固定话术回复3仅点赞' AFTER `comment_agent_id`,
ADD COLUMN `comment_speech` json NULL COMMENT '评论固定话术' AFTER `comment_type`,
ADD COLUMN `dm_type` tinyint NULL DEFAULT 1 COMMENT '社媒私信方式1ai智能回复2固定话术' AFTER `dm_agent_id`,
ADD COLUMN `dm_speech` json NULL COMMENT '社媒固定话术' AFTER `dm_type`,
ADD COLUMN `wechat_chat_type` tinyint NULL DEFAULT 1 COMMENT '微信私信方式1ai智能回复2固定话术' AFTER `wechat_chat_agent_id`,
ADD COLUMN `wechat_chat_speech` json NULL COMMENT '微信私信固定话术' AFTER `wechat_chat_type`,
ADD COLUMN `moments_action` tinyint NULL DEFAULT 3 COMMENT '朋友圈执行动作1仅点赞2仅评论3评论点赞' AFTER `moments_agent_id`,
ADD COLUMN `moments_type` tinyint NULL DEFAULT 1 COMMENT '朋友圈接管方式1ai智能回复2固定话术' AFTER `moments_action`,
ADD COLUMN `moments_speech` json NULL COMMENT '朋友圈固定话术' AFTER `moments_type`,
ADD COLUMN `shutoff_comment_type` tinyint NULL DEFAULT 1 COMMENT '截流评论方式1ai智能回复2固定话术' AFTER `moments_speech`,
ADD COLUMN `shutoff_comment_agent_id` int NULL DEFAULT 0 COMMENT '截流评论智能体' AFTER `shutoff_comment_type`,
ADD COLUMN `shutoff_comment_speech` json NULL COMMENT '截流评论话术' AFTER `shutoff_comment_agent_id`,
ADD COLUMN `shutoff_msg_type` tinyint NULL DEFAULT 1 COMMENT '截流私信方式1ai智能体2固定话术' AFTER `shutoff_comment_speech`,
ADD COLUMN `shutoff_msg_agent_id` int NULL DEFAULT 0 COMMENT '截流私信智能体' AFTER `shutoff_msg_type`,
ADD COLUMN `shutoff_msg_speech` json NULL COMMENT '截流私信固定话术' AFTER `shutoff_msg_agent_id`;


ALTER TABLE `la_ai_persona_wechat_interaction_config` 
ADD COLUMN `is_share_chats` tinyint NULL DEFAULT 1 COMMENT '是否发送聊天消息1是0否' AFTER `greeting_text`;

ALTER TABLE `la_sv_wechat_strategy` 
ADD COLUMN `is_share_chats` tinyint NULL DEFAULT 1 COMMENT '是否发送聊天消息1是0否' AFTER `greeting_text`;

ALTER TABLE `la_ai_persona_traffic_config` 
DROP COLUMN `view_video_time`,
DROP COLUMN `touch_interval`,
DROP COLUMN `gender`,
DROP COLUMN `age_range`,
DROP COLUMN `filter_ip`,
DROP COLUMN `filter_address`,
DROP COLUMN `filter_nikename`;

ALTER TABLE `la_ai_persona_traffic_config` 
ADD COLUMN `group_buy_config` json NULL COMMENT '团购截流配置' AFTER `comment_publish_day`,
ADD COLUMN `same_city_config` json NULL COMMENT '同城视频评论截流' AFTER `group_buy_config`,
ADD COLUMN `video_cutoff_number` int NULL DEFAULT 15 COMMENT '每日视频截流人数上限' AFTER `same_city_config`,
ADD COLUMN `city_cutoff_number` int NULL DEFAULT 15 COMMENT '每日同城截流人数上限' AFTER `video_cutoff_number`,
ADD COLUMN `group_cutoff_number` int NULL DEFAULT 15 COMMENT '每日团购截流人数上限' AFTER `city_cutoff_number`;


CREATE TABLE IF NOT EXISTS `la_sv_device_take_over_speech_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `type` tinyint(4) DEFAULT NULL COMMENT '话术类型1评论2私信',
  `keyword` varchar(255) DEFAULT NULL COMMENT '话术',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `keyword` (`keyword`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='私信和评论历史固定话术';

CREATE TABLE IF NOT EXISTS `la_sv_device_execution_schedule` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
`persona_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '人设类型：1=个人IP 2=企业服务 3=本地商家',
`start_time` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '起始时间，格式 HH:mm',
`end_time` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '结束时间，格式 HH:mm',
`task_category` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '执行任务',
`scene` tinyint(4) DEFAULT '0' COMMENT '任务场景：1截流评论获客2=截流私信获客 3=留痕获客/同城触达 4=视频号获客 5=视频发布 6=私信接管 7=朋友圈发布 8=朋友圈互动 9=自动加好友 10=自动养号 11=评论接管 12=同城曝光 13=同城截流 14=团购截流 15=评论点赞',
`platform` json NOT NULL COMMENT '执行平台编码数组，如 [1,3] 对应 1=微信生态 3=小红书 4=抖音 5=快手',
`quantity_duration` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '控制数量/时长',
`cost_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '算力计价规则',
`remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '执行动作备注',
`create_time` int(11) DEFAULT NULL,
`update_time` int(11) DEFAULT NULL,
`delete_time` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_persona_type` (`persona_type`),
KEY `start_time` (`start_time`) USING BTREE,
KEY `end_time` (`end_time`) USING BTREE,
KEY `id` (`id`) USING BTREE,
KEY `scene` (`scene`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='设备执行时间表';

CREATE TABLE IF NOT EXISTS `la_sv_device_execution_schedule_user` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT NULL COMMENT '用户id',
`device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
`persona_type` tinyint(4) DEFAULT NULL COMMENT '人设类型',
`schedule_id` int(11) DEFAULT NULL COMMENT '计划id',
`status` tinyint(4) DEFAULT NULL COMMENT '计划状态1开0关',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`) USING BTREE,
KEY `device_code` (`device_code`) USING BTREE,
KEY `persona_type` (`persona_type`) USING BTREE,
KEY `schedule_id` (`schedule_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户设备计划表';


INSERT INTO `la_sv_device_execution_schedule` (`persona_type`, `start_time`, `end_time`, `task_category`, `scene`, `platform`, `quantity_duration`, `cost_rule`, `remark`) VALUES
(1, '06:00', '06:10', '私信接管', 6, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '06:10', '06:20', '评论接管', 11, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '06:20', '06:30', '私信接管', 6, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '06:30', '06:40', '评论接管', 11, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '06:40', '06:50', '私信接管', 6, '[5]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '06:50', '07:00', '评论接管', 11, '[5]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '07:00', '07:10', '评论点赞', 15, '[1]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '07:10', '07:30', '私信接管', 6, '[1]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理昨夜消息'),
(1, '07:30', '07:40', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '发送好友请求，附带话术（如：你好，抖音/小红书来的）'),
(1, '07:40', '07:50', '私信接管', 6, '[1]', '拉群', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '及时跟进早间活跃用户的消息'),
(1, '07:50', '08:00', '朋友圈发布', 7, '[1]', '1条', '20算力/条', '发布视频，展示个人IP日常或专业干货'),
(1, '08:00', '08:30', '视频发布', 5, '[4,3,5,1]', '各1条', '20算力/条', '矩阵分发，抓住早通勤流量'),
(1, '08:30', '09:00', '留痕获客', 3, '[3]', '5人', '5算力/人', '搜索关键词找同行笔记。对评论区意向用户执行：点赞其评论+关注该人+点赞其个人中心第一条作品'),
(1, '09:00', '09:30', '私信接管', 6, '[1]', '拉群', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '承接早间视频发布和留痕带来的咨询'),
(1, '09:30', '09:45', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '少量多次添加，打散频率防封控'),
(1, '09:45', '10:30', '截流私信获客', 2, '[4]', '10条私信', '5算力/人', '搜索关键词找同行视频。在评论区找符合预设匹配词的用户，直接发送私信截流'),
(1, '10:30', '11:00', '私信接管', 6, '[1]', '拉群', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '集中处理微信端的好友通过及初步沟通'),
(1, '11:00', '11:30', '自动养号', 10, '[5]', '', '0.1算力/分钟', '浏览行业相关图文/视频，模拟正常用户'),
(1, '11:30', '12:00', '自动养号', 10, '[3]', '', '0.1算力/分钟', '浏览行业相关图文/视频，模拟正常用户'),
(1, '12:00', '12:30', '自动养号', 10, '[4]', '', '0.1算力/分钟', '浏览行业相关图文/视频，模拟正常用户'),
(1, '12:30', '12:45', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '午休时间用户看手机频率高，通过率高'),
(1, '12:45', '13:10', '截流私信获客', 2, '[3]', '5条私信', '5算力/人', '刷同城页面，在同城热门笔记评论区寻找目标用户，发送私信截流'),
(1, '13:10', '13:20', '私信接管', 6, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '紧跟截流动作，极速响应回复，防止线索流失'),
(1, '13:20', '13:30', '评论接管', 11, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '紧跟截流动作，极速响应回复，防止线索流失'),
(1, '13:30', '14:00', '留痕获客', 3, '[4]', '10人', '5算力/人', '刷同城视频。对符合画像的视频作者执行：关注作者+点赞当前作品+评论当前作品'),
(1, '14:00', '14:30', '私信接管', 6, '[1]', '拉群', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '处理午间积累的微信消息'),
(1, '14:30', '14:45', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '持续消化线索库'),
(1, '14:45', '16:00', '自动养号', 10, '[4]', '', '0.1算力/分钟', '下午长时段挂机养号，提升账号权重'),
(1, '16:00', '17:00', '自动养号', 10, '[3]', '', '0.1算力/分钟', '下午长时段挂机养号，提升账号权重'),
(1, '17:00', '17:10', '自动加好友', 9, '[1]', '加2人', '20算力/条', '晚高峰前夕发布，触达微信私域及视频号公域'),
(1, '17:10', '17:20', '私信接管', 6, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '17:20', '17:30', '评论接管', 11, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '17:30', '17:40', '私信接管', 6, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '17:40', '17:50', '评论接管', 11, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '17:50', '18:00', '私信接管', 6, '[5]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '18:00', '18:10', '评论接管', 11, '[5]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '18:10', '18:20', '评论点赞', 15, '[1]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚高峰红点清理'),
(1, '18:20', '18:30', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '下班通勤时间添加'),
(1, '18:30', '19:15', '截流私信获客', 2, '[4]', '10条私信', '5算力/人', '搜索关键词找同行视频。在评论区找符合预设匹配词的用户，直接发送私信截流'),
(1, '19:15', '19:30', '私信接管', 6, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '及时承接晚间截流反馈'),
(1, '19:30', '19:40', '评论接管', 11, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '及时承接晚间截流反馈'),
(1, '19:40', '20:00', '留痕获客', 3, '[3]', '10个用户', '5算力/人', '搜索精准词。对评论区用户执行：点赞评论+关注+收藏其个人中心第一条作品'),
(1, '20:00', '20:10', '私信接管', 6, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚间高频互动期，保持响应'),
(1, '20:10', '20:20', '评论接管', 11, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '晚间高频互动期，保持响应'),
(1, '20:20', '20:30', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '晚间休息期添加'),
(1, '20:30', '20:40', '自动养号', 10, '[4]', '', '0.1算力/分钟', '晚间黄金档轻度养号'),
(1, '20:40', '20:50', '私信接管', 6, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '20:50', '21:00', '评论接管', 11, '[4]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '21:00', '21:10', '自动养号', 10, '[3]', '', '0.1算力/分钟', '晚间活跃期养号'),
(1, '21:10', '21:20', '私信接管', 6, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '21:20', '21:30', '评论接管', 11, '[3]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '21:30', '21:40', '自动养号', 10, '[5]', '', '0.1算力/分钟', '晚间活跃期养号'),
(1, '21:40', '21:50', '私信接管', 6, '[5]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '21:50', '22:00', '评论接管', 11, '[5]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '22:00', '22:10', '评论点赞', 15, '[1]', '', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '轮询回复'),
(1, '22:10', '22:30', '朋友圈互动', 8, '[1]', '', '点赞1算力/次\n评论5算力/人', '自动给微信好友朋友圈点赞/评论，做私域激活与留痕'),
(1, '22:30', '22:45', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '睡前最后一次添加'),
(1, '22:45', '23:30', '私信接管', 6, '[1]', '拉群', '按照AI客服中的算力计价扣费：\n1000tokens/ 1算力', '睡前最后一次全盘清理未读消息，随后进入静默');


INSERT INTO `la_sv_device_execution_schedule` (`persona_type`, `start_time`, `end_time`, `task_category`, `scene`, `platform`, `quantity_duration`, `cost_rule`, `remark`) VALUES
(2, '08:00', '08:15', '自动养号', 10, '[3]', '', '0.1算力/分钟', '浏览行业垂直内容（如财经、管理、制造），打牢商务标签。'),
(2, '08:15', '08:30', '自动养号', 10, '[4]', '', '0.1算力/分钟', '搜索并浏览B端行业资讯，随机点赞，提升账号活跃度。'),
(2, '08:30', '09:00', '视频发布', 5, '[1,4,3,5]', '1条', '20算力/条', '发布专业干货、工厂实拍或客户案例视频。'),
(2, '09:00', '09:30', '私信接管', 6, '[1]', '拉群', '1000tokens/1算力', '处理早间客户微信留言，进行初步商务对接。'),
(2, '09:30', '10:00', '视频号获客', 4, '[1]', '', '识别1算力/个 验证1算力/次', '搜索行业关键词，进入目标账号主页，通过OCR识别提取微信号并存入线索库。'),
(2, '10:00', '10:10', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '提取上午OCR识别的线索，发送带公司名头的专业验证语（如：您好，我是XX公司的业务经理）。'),
(2, '10:10', '10:30', '截流私信获客', 2, '[4]', '10条私信', '5算力/人', '在抖音头部商业博主或同行视频评论区，寻找问价格、求合作的企业主，直接发送私信截流。'),
(2, '10:30', '11:00', '留痕获客', 3, '[4]', '10条私信', '5算力/人', '在抖音头部商业博主或同行视频评论区，寻找问价格、求合作的企业主，直接发送私信截流。'),
(2, '11:00', '11:20', '私信接管', 6, '[4]', '10条私信', '1000tokens/1算力', '极速承接抖音截流带来的私信回复。'),
(2, '11:20', '11:30', '评论接管', 11, '[4]', '10条私信', '1000tokens/1算力', '极速承接抖音截流带来的私信回复。'),
(2, '11:30', '12:00', '朋友圈互动', 8, '[1]', '10条私信', '点赞1算力/次 评论5算力/人', '模拟真人刷朋友圈，给已有B端客户点赞评论，维系商务人脉关系。'),
(2, '12:00', '12:30', '留痕获客', 3, '[3]', '10条私信', '点赞1算力/次 评论5算力/人', '模拟真人刷朋友圈，给已有B端客户点赞评论，维系商务人脉关系。'),
(2, '12:30', '12:50', '私信接管', 6, '[3]', '10个动作', '5算力/人', '搜索ToB业务词（如财税/代账/设计/源头厂家），对评论区意向用户执行：点赞评论+关注+点赞其个人作品。'),
(2, '12:50', '13:00', '评论接管', 11, '[3]', '10个动作', '1000tokens/1算力', '承接小红书留痕动作带来的咨询。'),
(2, '13:00', '13:10', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '午休时间零星添加客户微信。'),
(2, '13:30', '14:30', '视频号获客', 4, '[1]', '加2人', '识别1算力/个 验证1算力/次', '下午场继续深挖视频号同行，OCR提取主页微信号，持续扩充客资库。'),
(2, '14:30', '14:45', '私信接管', 6, '[1]', '加2人', '1000tokens/1算力', '随时跟进微信端可能通过的好友，发送公司介绍资料。'),
(2, '14:45', '15:20', '截流私信获客', 2, '[4]', '10条私信', '5算力/人', '下午继续在抖音同行视频评论区找精准客户发私信。'),
(2, '15:20', '15:35', '私信接管', 6, '[4]', '10条私信', '1000tokens/1算力', '及时回复抖音私信，引导添加微信。'),
(2, '15:35', '15:45', '评论接管', 11, '[4]', '10条私信', '1000tokens/1算力', '及时回复抖音私信，引导添加微信。'),
(2, '15:45', '16:15', '视频号获客', 4, '[1]', '10条私信', '识别1算力/个 验证1算力/次', '给潜在B端客户的视频号点赞/评论，吸引对方老板查看自己的主页。'),
(2, '16:15', '16:30', '私信接管', 6, '[1]', '拉群', '1000tokens/1算力', '处理私信。'),
(2, '16:30', '16:40', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '消耗下午OCR提取的线索，分批添加。'),
(2, '16:40', '17:00', '留痕获客', 3, '[3]', '加2人', '0.1算力/分钟', '浏览B端商业笔记，提升小红书账号权重。'),
(2, '17:00', '17:15', '私信接管', 6, '[3]', '加2人', '1000tokens/1算力', '下班前检查小红书私信。'),
(2, '17:15', '17:30', '评论接管', 11, '[3]', '加2人', '1000tokens/1算力', '下班前检查小红书私信。'),
(2, '17:30', '18:00', '私信接管', 6, '[1]', '拉群', '1000tokens/1算力', '下班前集中处理所有商务咨询，做今日收尾。'),
(2, '18:00', '18:15', '朋友圈发布', 7, '[1]', '1条', '20算力/条', '发布公司日常、发货视频或行业见解，触达私域客户。'),
(2, '18:15', '18:30', '朋友圈互动', 8, '[1]', '1条', '点赞1算力/次 评论5算力/人', '发布公司日常、发货视频或行业见解，触达私域客户。'),
(2, '18:30', '19:30', '视频号获客', 4, '[1]', '1条', '识别1算力/个 验证1算力/次', '晚间挂机看行业直播，提升账号权重。'),
(2, '19:30', '20:00', '同城触达', 3, '[4]', '1条', '0.1算力/分钟', '晚间浏览商业类长视频。'),
(2, '20:00', '20:15', '私信接管', 6, '[4]', '1条', '1000tokens/1算力', '晚间红点清理。'),
(2, '20:15', '20:30', '评论接管', 11, '[3]', '1条', '1000tokens/1算力', '晚间红点清理。'),
(2, '20:30', '20:45', '私信接管', 6, '[3]', '1条', '1000tokens/1算力', '晚间红点清理。'),
(2, '20:45', '21:00', '评论接管', 11, '[3]', '1条', '1000tokens/1算力', '晚间红点清理。'),
(2, '21:00', '21:30', '私信接管', 6, '[1]', '拉群', '1000tokens/1算力', '晚间微信端轻度维护。'),
(2, '21:30', '21:40', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '全天最后一次添加，消化今日剩余线索。'),
(2, '21:40', '23:00', '私信接管', 6, '[1]', '加2人', '1000tokens/1算力', '全天最后一次添加，消化今日剩余线索。');

INSERT INTO `la_sv_device_execution_schedule` (`persona_type`, `start_time`, `end_time`, `task_category`, `scene`, `platform`, `quantity_duration`, `cost_rule`, `remark`) VALUES
(3, '07:00', '08:00', '同城曝光', 12, '[4]', '', '', ''),
(3, '08:00', '08:30', '团购截流', 14, '[4]', '', '0.1算力/分钟', '只刷同城页面，给账号打上强烈的本地地域标签，随机点赞完播。'),
(3, '08:30', '09:00', '视频发布', 5, '[4,3,5,1]', '', '20算力/条', '只刷同城页面，给账号打上强烈的本地地域标签，随机点赞完播。'),
(3, '09:00', '09:15', '私信接管', 6, '[1]', '', '1000tokens/1算力', '处理老客户微信预订及夜间遗留消息。'),
(3, '09:15', '09:30', '同城曝光', 12, '[4]', '15个用户', '5算力/人', '刷同城视频流。对刷到的本地作者执行：关注作者+点赞当前作品+评论当前作品（如“欢迎有空来XX店坐坐”）。'),
(3, '09:30', '10:00', '同城触达', 3, '[4]', '15个用户', '5算力/人', '刷同城视频流。对刷到的本地作者执行：关注作者+点赞当前作品+评论当前作品（如“欢迎有空来XX店坐坐”）。'),
(3, '10:00', '10:30', '同城截流', 13, '[4]', '15个用户', '5算力/人', '刷同城视频流。对刷到的本地作者执行：关注作者+点赞当前作品+评论当前作品（如“欢迎有空来XX店坐坐”）。'),
(3, '10:30', '10:45', '私信接管', 6, '[4]', '15个用户', '5算力/人', '刷同城视频流。对刷到的本地作者执行：关注作者+点赞当前作品+评论当前作品（如“欢迎有空来XX店坐坐”）。'),
(3, '10:45', '11:00', '评论接管', 11, '[4]', '15分钟', '1000tokens/1算力', '午市前夕，极速响应抖音同城带来的咨询，发送团购链接或门店定位。'),
(3, '11:00', '11:15', '私信接管', 6, '[1]', '15分钟', '1000tokens/1算力', '处理老客户微信预订及夜间遗留消息。'),
(3, '11:15', '11:30', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '将刚咨询的高意向客户加微，方便发送定位和后续触达。'),
(3, '11:30', '11:45', '留痕获客', 3, '[3]', '10个用户', '5算力/人', '刷同城页面。对本地用户执行：关注作者+点赞当前笔记+评论当前笔记，增加本地圈子曝光。'),
(3, '11:45', '12:00', '私信接管', 6, '[3]', '10个用户', '1000tokens/1算力', '极速响应小红书午市咨询。'),
(3, '12:00', '12:15', '评论接管', 11, '[3]', '10个用户', '1000tokens/1算力', '极速响应小红书午市咨询。'),
(3, '12:15', '12:30', '朋友圈互动', 8, '[1]', '10个用户', '点赞1算力/次 评论5算力/人', '午市高峰期，AI手机在后台给朋友圈本地客户点赞/评论，刷存在感，促使复购。'),
(3, '12:30', '13:15', '私信接管', 6, '[1]', '10个用户', '1000tokens/1算力', '午市高峰期，AI手机在后台给朋友圈本地客户点赞/评论，刷存在感，促使复购。'),
(3, '13:15', '13:30', '朋友圈发布', 7, '[1]', '1条', '20算力/条', '午市高峰期，AI手机在后台给朋友圈本地客户点赞/评论，刷存在感，促使复购。'),
(3, '13:30', '13:40', '私信接管', 6, '[4]', '1条', '1000tokens/1算力', '统一回复遗漏信息。'),
(3, '13:40', '13:50', '评论接管', 11, '[4]', '1条', '1000tokens/1算力', '统一回复遗漏信息。'),
(3, '13:50', '14:00', '私信接管', 6, '[3]', '1条', '1000tokens/1算力', '统一回复小红书遗漏信息。'),
(3, '14:00', '14:10', '评论接管', 11, '[3]', '1条', '1000tokens/1算力', '统一回复小红书遗漏信息。'),
(3, '14:10', '14:20', '评论点赞', 15, '[1]', '1条', '1000tokens/1算力', '集中处理微信端客户咨询与售后。'),
(3, '14:20', '14:30', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '零星添加午间积累的线索。'),
(3, '14:30', '14:45', '同城截流', 13, '[4]', '加2人', '10算力加一次', '零星添加午间积累的线索。'),
(3, '14:45', '15:30', '私信接管', 6, '[4]', '加2人', '5算力/人', '搜索“本地地名+探店/美食”。在本地大V评论区找求推荐/问价的用户，发送私信截流。'),
(3, '15:30', '15:45', '评论接管', 11, '[4]', '加2人', '1000tokens/1算力', '紧跟截流动作，极速承接私信回复。'),
(3, '15:45', '16:30', '留痕获客', 3, '[3]', '加2人', '5算力/人', '搜索本地竞品词。对评论区用户执行：点赞其评论+关注该人+点赞其个人中心第一条作品。'),
(3, '16:30', '17:00', '视频发布', 5, '[4,3,5,1]', '各1条', '20算力/条', '发布晚餐推荐或夜间活动预告，卡位晚市流量。'),
(3, '17:00', '17:10', '私信接管', 6, '[4]', '各1条', '1000tokens/1算力', '晚市前夕红点清理。'),
(3, '17:10', '17:20', '评论接管', 11, '[4]', '各1条', '1000tokens/1算力', '晚市前夕红点清理。'),
(3, '17:20', '17:30', '私信接管', 6, '[3]', '各1条', '1000tokens/1算力', '晚市前夕红点清理。'),
(3, '17:30', '17:40', '评论接管', 11, '[3]', '各1条', '1000tokens/1算力', '晚市前夕红点清理。'),
(3, '17:40', '17:50', '评论点赞', 15, '[1]', '各1条', '1000tokens/1算力', '晚市前夕红点清理，处理客户预订。'),
(3, '17:50', '18:00', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '晚市预订高峰，加微发定位。'),
(3, '18:00', '18:30', '团购截流', 14, '[4]', '加2人', '5算力/人', '晚间流量最大，高频执行同城关注+点赞当前作品+评论当前作品，强力曝光。'),
(3, '18:30', '18:40', '私信接管', 6, '[4]', '加2人', '1000tokens/1算力', '极速响应晚间咨询。'),
(3, '18:40', '18:50', '评论接管', 11, '[4]', '加2人', '1000tokens/1算力', '极速响应晚间咨询。'),
(3, '18:50', '19:00', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '零星添加。'),
(3, '19:00', '19:30', '同城触达', 3, '[3]', '5个用户', '5算力/人', '晚间小红书同城曝光，执行关注+点赞当前笔记+评论当前笔记。'),
(3, '19:30', '19:45', '私信接管', 6, '[3]', '5个用户', '1000tokens/1算力', '响应小红书晚间咨询。'),
(3, '19:45', '20:00', '评论接管', 11, '[3]', '5个用户', '1000tokens/1算力', '响应小红书晚间咨询。'),
(3, '20:00', '20:30', '团购截流', 14, '[4]', '5个用户', '0.1算力/分钟', '晚间挂机看本地生活类直播或同城视频，提升账号权重。'),
(3, '20:30', '20:40', '私信接管', 6, '[4]', '5个用户', '1000tokens/1算力', '深度回复，做售后关怀。'),
(3, '20:40', '20:50', '评论接管', 11, '[4]', '5个用户', '1000tokens/1算力', '深度回复，做售后关怀。'),
(3, '20:50', '21:00', '私信接管', 6, '[3]', '5个用户', '1000tokens/1算力', '睡前红点清理。'),
(3, '21:00', '21:10', '评论接管', 11, '[3]', '5个用户', '1000tokens/1算力', '睡前红点清理。'),
(3, '21:10', '21:20', '私信接管', 6, '[5]', '5个用户', '1000tokens/1算力', '睡前红点清理。'),
(3, '21:20', '21:30', '评论接管', 11, '[5]', '5个用户', '1000tokens/1算力', '睡前红点清理。'),
(3, '21:30', '21:40', '评论点赞', 15, '[1]', '5个用户', '1000tokens/1算力', '睡前红点清理。'),
(3, '21:40', '22:00', '自动加好友', 9, '[1]', '加2人', '10算力加一次', '睡前最后一次添加，随后进入夜间静默。');

INSERT INTO `la_notice_setting` (`scene_id`, `scene_name`, `scene_desc`, `recipient`, `type`, `system_notice`, `sms_notice`, `oa_notice`, `mnp_notice`, `support`, `update_time`) 
VALUES (403, '设备掉线/重连', '设备掉线/重连时发送', 1, 1, '{"type":"system","title":"","content":"","status":"0","is_show":"","tips":["可选变量 验证码:code"]}' , 
'{"type":"sms","template_id":"","content":"","status":"0","is_show":"0","tips":""}', 
'{"type":"oa","template_id":"","template_sn":"","name":"","first":"","remark":"","tpl":[],"status":"0","is_show":"","tips":["可选变量 验证码:code","配置路径：小程序后台 > 功能 > 订阅消息"]}', 
'{"type":"mnp","template_id":"UelIQ28U41juxBHJHt8ZMhitaCDh38Ep8lopBqFkXhM","template_sn":"18004","name":"设备报警通知","tpl":["设备名称{{thing2.DATA}}触发时间{{time6.DATA}}触发数据{{character_string4.DATA}}"],"status":"1","is_show":"1","tips":["固定变量 设备名称:thing2 触发时间:time6 触发数据:character_string4"]}', 
4, 1777361004);

INSERT INTO `la_notice_setting` (`scene_id`, `scene_name`, `scene_desc`, `recipient`, `type`, `system_notice`, `sms_notice`, `oa_notice`, `mnp_notice`, `support`, `update_time`) 
VALUES (404, '自动任务通知', '自动任务通知时发送', 1, 1, '{"type":"system","title":"","content":"","status":"0","is_show":"","tips":["可选变量 验证码:code"]}' , 
'{"type":"sms","template_id":"","content":"","status":"0","is_show":"0","tips":""}', 
'{"type":"oa","template_id":"","template_sn":"","name":"","first":"","remark":"","tpl":[],"status":"0","is_show":"","tips":["可选变量 验证码:code","配置路径：小程序后台 > 功能 > 订阅消息"]}', 
'{"type":"mnp","template_id":"6PJuDpt1uFMjHKFhOzTiUw-krS8nsig2iRGZALilk_o","template_sn":"802","name":"任务接收通知","tpl":["任务内容{{thing4.DATA}}执行时间{{date7.DATA}}任务进度{{thing30.DATA}}"],"status":"1","is_show":"1","tips":["固定变量 任务内容:thing4 执行时间:date7 任务进度:thing30"]}', 
4, 1777361004);


INSERT INTO `la_notice_setting` (`scene_id`, `scene_name`, `scene_desc`, `recipient`, `type`, `system_notice`, `sms_notice`, `oa_notice`, `mnp_notice`, `support`, `update_time`) 
VALUES (405, '新增客户线索提醒', '新增客户线索提醒时发送', 1, 1, '{"type":"system","title":"","content":"","status":"0","is_show":"","tips":["可选变量 验证码:code"]}' , 
'{"type":"sms","template_id":"","content":"","status":"0","is_show":"0","tips":""}', 
'{"type":"oa","template_id":"","template_sn":"","name":"","first":"","remark":"","tpl":[],"status":"0","is_show":"","tips":["可选变量 验证码:code","配置路径：小程序后台 > 功能 > 订阅消息"]}', 
'{"type":"mnp","template_id":"WAnm8vvpvcHTvsyyKWPZnPDGInmvqzOax6kYr6szXb8","template_sn":"68489","name":"新增客户线索提醒","tpl":["新增线索{{thing4.DATA}}更新日期{{time2.DATA}}联系方式{{phone_number3.DATA}}"],"status":"1","is_show":"1","tips":["固定变量 新增线索:thing4 更新日期:time2 联系方式:phone_number3"]}', 
4, 1777361004);

INSERT INTO `la_notice_setting` (`scene_id`, `scene_name`, `scene_desc`, `recipient`, `type`, `system_notice`, `sms_notice`, `oa_notice`, `mnp_notice`, `support`, `update_time`) 
VALUES (406, '群加入申请通知', '群加入申请通知时发送', 1, 1, '{"type":"system","title":"","content":"","status":"0","is_show":"","tips":["可选变量 验证码:code"]}' , 
'{"type":"sms","template_id":"","content":"","status":"0","is_show":"0","tips":""}', 
'{"type":"oa","template_id":"","template_sn":"","name":"","first":"","remark":"","tpl":[],"status":"0","is_show":"","tips":["可选变量 验证码:code","配置路径：小程序后台 > 功能 > 订阅消息"]}', 
'{"type":"mnp","template_id":"JIGcgmZQWeluhmISSroyMUBMiJ-qdnMJy7a69E8P6uU","template_sn":"10696","name":"群加入申请通知","tpl":["群名称{{thing3.DATA}}申请时间{{time4.DATA}}备注{{thing5.DATA}}"],"status":"1","is_show":"1","tips":["固定变量 群名称:thing3 申请时间:thing4 备注:thing5"]}', 
4, 1777361004);

ALTER TABLE `la_notice_record` 
MODIFY COLUMN `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '其他' AFTER `notice_type`;

UPDATE `la_system_menu` SET `name` = 'AI获客' WHERE `id` = 368;
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (550, 368, 'M', '教程管理', '', 0, '', 'tutorial', '', '', '', 0, 1, 0, 1776741485, 1776906421);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (551, 550, 'C', '教程列表', '', 0, 'ai_application.device.tutorial/lists', 'lists', 'ai_application/device/tutorial/lists/index', '', '', 0, 1, 0, 1776741516, 1776906334);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (552, 550, 'C', '教程类目', '', 0, 'ai_application.device.tutorial/cate', 'cate', 'ai_application/device/tutorial/cate/index', '', '', 0, 1, 0, 1776741563, 1776906339);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (553, 551, 'A', '新增', '', 0, 'ai_application.device.tutorial.lists/add', '', '', '', '', 0, 1, 0, 1776741606, 1776906357);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (554, 551, 'A', '编辑', '', 0, 'ai_application.device.tutorial.lists/edit', '', '', '', '', 0, 1, 0, 1776741619, 1776906361);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (555, 551, 'A', '删除', '', 0, 'ai_application.device.tutorial.lists/delete', '', '', '', '', 0, 1, 0, 1776741635, 1776906364);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (556, 551, 'A', '状态', '', 0, 'ai_application.device.tutorial.lists/status', '', '', '', '', 0, 1, 0, 1776741799, 1776906367);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (557, 552, 'A', '新增', '', 0, 'ai_application.device.tutorial.cate/add', '', '', '', '', 0, 1, 0, 1776741838, 1776906342);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (558, 552, 'A', '编辑', '', 0, 'ai_application.device.tutorial.cate/edit', '', '', '', '', 0, 1, 0, 1776741849, 1776906345);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (559, 552, 'A', '删除', '', 0, 'ai_application.device.tutorial.cate/delete', '', '', '', '', 0, 1, 0, 1776741858, 1776906348);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (560, 552, 'A', '状态', '', 0, 'ai_application.device.tutorial.cate/status', '', '', '', '', 0, 1, 0, 1776741874, 1776906352);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (561, 438, 'C', '运营案例', '', 0, 'marketing.operate_case/lists', 'operate_case', 'marketing/operate_case/lists', '', '', 0, 1, 0, 1776741907, 1776741907);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (562, 561, 'A', '新增', '', 0, 'marketing.operate_case.lists/add', '', '', '', '', 0, 1, 0, 1776741937, 1776741937);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (563, 561, 'A', '编辑', '', 0, 'marketing.operate_case.lists/edit', '', '', '', '', 0, 1, 0, 1776741947, 1776741947);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (564, 561, 'A', '删除', '', 0, 'marketing.operate_case.lists/delete', '', '', '', '', 0, 1, 0, 1776741960, 1776741960);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (565, 561, 'A', '状态', '', 0, 'marketing.operate_case.lists/status', '', '', '', '', 0, 1, 0, 1776741971, 1776741971);




INSERT INTO `la_catering_franchise` (
  `category_type`, `title`, `exposure`, `leads`, `convert_users`, `intro`,
  `target_users`, `task_types`, `detail_content`, `detail_task_types`,
  `detail_users`, `detail_images`, `detail_videos`, `status`, `create_time`, `update_time`
) VALUES 
(3, '特色餐饮品牌全国招商', '12.8w', '286', '56', '餐饮品牌面向全国寻找有意向加盟的创业者及投资人，快速扩大品牌版图。', '["餐饮创业者","寻找项目的投资人","待业青年","实体店转型老板"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"评论区截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布品牌优势及门店火爆视频，全天监控\'餐饮加盟\'等关键词截流，下午自动接管私信解答加盟疑问，晚上引导加微发送招商政策。', '每天发布与"特色餐饮品牌全国招商"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '餐饮创业者、寻找项目的投资人、待业青年、实体店转型老板', '["/static/case/templates/enterprise-catering-franchise/01.jpg","/static/case/templates/enterprise-catering-franchise/02.jpg","/static/case/templates/enterprise-catering-franchise/03.jpg","/static/case/templates/enterprise-catering-franchise/04.jpg","/static/case/templates/enterprise-catering-franchise/05.jpg","/static/case/templates/enterprise-catering-franchise/06.jpg","/static/case/templates/enterprise-catering-franchise/07.jpg","/static/case/templates/enterprise-catering-franchise/08.jpg","/static/case/templates/enterprise-catering-franchise/09.jpg","/static/case/templates/enterprise-catering-franchise/10.jpg"]', '["/static/case/templates/enterprise-catering-franchise/01.mp4"]', 1, 1713945600, 1713945600),
(3, '创新体验馆全国加盟招商', '9.6w', '214', '9', '新型成人体验馆面向全国招募加盟商及城市合伙人，共同开拓蓝海市场。', '["寻找新奇特项目的创业者","有闲置资金的投资人","美业/休闲娱乐转型老板"]', '[{"type":"同行截流","time":"12:00-18:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布门店沉浸式体验视频，中午监控同行评论区截流意向客户，下午私信初步沟通投资预算，晚上加微发送投资回报测算表。', '每天发布与"创新体验馆全国加盟招商"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '寻找新奇特项目的创业者、有闲置资金的投资人、美业/休闲娱乐转型老板', '["/static/case/templates/enterprise-business-franchise/01.jpg","/static/case/templates/enterprise-business-franchise/02.jpg","/static/case/templates/enterprise-business-franchise/03.jpg","/static/case/templates/enterprise-business-franchise/04.jpg","/static/case/templates/enterprise-business-franchise/05.jpg","/static/case/templates/enterprise-business-franchise/06.jpg","/static/case/templates/enterprise-business-franchise/07.jpg","/static/case/templates/enterprise-business-franchise/08.jpg","/static/case/templates/enterprise-business-franchise/09.jpg"]', '["/static/case/templates/enterprise-business-franchise/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城新房楼盘精准拓客', '18.3w', '412', '74', '房产中介机构针对同城有购房需求的客户进行精准触达，促进新房销售转化。', '["刚需购房者","改善型购房者","准备结婚的青年","学区房需求家长"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"同城截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布楼盘实地测评视频，中午在同城房产热点话题下截流，下午私信发送户型图与优缺点分析，晚上加微邀约周末实地看房。', '每天发布与"同城新房楼盘精准拓客"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '刚需购房者、改善型购房者、准备结婚的青年、学区房需求家长', '["/static/case/templates/enterprise-real-estate-sales/01.jpg","/static/case/templates/enterprise-real-estate-sales/02.jpg","/static/case/templates/enterprise-real-estate-sales/03.jpg"]', '["/static/case/templates/enterprise-real-estate-sales/01.mp4"]', 1, 1713945600, 1713945600),
(1, '农村空气能供暖设备直销', '7.5w', '165', '83', '环保设备厂家精准寻找农村及城郊有供暖改造需求的家庭，推广空气能采暖设备。', '["农村自建房业主","城郊别墅业主","有煤改电及供暖改造需求的家庭"]', '[{"type":"区域获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布设备节能省电对比视频，中午监控\'取暖\'\'煤改电\'关键词截流，下午私信解答安装条件及补贴政策，晚上加微发送设备报价单。', '每天发布与"农村空气能供暖设备直销"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '农村自建房业主、城郊别墅业主、有煤改电及供暖改造需求的家庭', '["/static/case/templates/enterprise-equipment-sales/01.jpg","/static/case/templates/enterprise-equipment-sales/02.jpg","/static/case/templates/enterprise-equipment-sales/03.jpg","/static/case/templates/enterprise-equipment-sales/04.jpg","/static/case/templates/enterprise-equipment-sales/05.jpg","/static/case/templates/enterprise-equipment-sales/06.jpg"]', '["/static/case/templates/enterprise-equipment-sales/01.mp4"]', 1, 1713945600, 1713945600),
(3, '工业硬度计设备精准直销', '6.2w', '138', '100', '仪器设备厂家精准寻找机械加工、制造企业的采购负责人或老板，促成设备采购合作。', '["机械制造公司老板","工厂厂长","企业采购负责人","质检部门主管"]', '[{"type":"行业截流","time":"12:00-18:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布设备精准度测试操作视频，全天监控工业制造相关话题截流，下午私信发送产品详细参数，晚上加微预约线下试机或寄送样品。', '每天发布与"工业硬度计设备精准直销"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '机械制造公司老板、工厂厂长、企业采购负责人、质检部门主管', '["/static/case/templates/enterprise-machinery-equipment/01.jpg","/static/case/templates/enterprise-machinery-equipment/02.jpg","/static/case/templates/enterprise-machinery-equipment/03.jpg","/static/case/templates/enterprise-machinery-equipment/04.jpg","/static/case/templates/enterprise-machinery-equipment/05.jpg","/static/case/templates/enterprise-machinery-equipment/06.jpg","/static/case/templates/enterprise-machinery-equipment/07.jpg","/static/case/templates/enterprise-machinery-equipment/08.jpg","/static/case/templates/enterprise-machinery-equipment/09.jpg"]', '["/static/case/templates/enterprise-machinery-equipment/01.mp4"]', 1, 1713945600, 1713945600),
(3, '瓷砖源头厂家全国招商', '8.1w', '176', '32', '瓷砖生产厂家面向全国寻找建材经销商、装修公司老板，提供一手货源及加盟合作。', '["建材店老板","装修公司负责人","全屋定制门店老板","建材行业创业者"]', '[{"type":"同行截流","time":"12:00-18:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布工厂生产线实拍视频，中午截流同行招商视频评论区，下午私信发送最新产品电子图册，晚上加微沟通代理政策并寄送小样。', '每天发布与"瓷砖源头厂家全国招商"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '建材店老板、装修公司负责人、全屋定制门店老板、建材行业创业者', '["/static/case/templates/enterprise-tile-franchise/01.jpg","/static/case/templates/enterprise-tile-franchise/02.jpg","/static/case/templates/enterprise-tile-franchise/03.jpg","/static/case/templates/enterprise-tile-franchise/04.jpg","/static/case/templates/enterprise-tile-franchise/05.jpg","/static/case/templates/enterprise-tile-franchise/06.jpg","/static/case/templates/enterprise-tile-franchise/07.jpg"]', '["/static/case/templates/enterprise-tile-franchise/01.mp4"]', 1, 1713945600, 1713945600),
(3, '全国茶博会展商定向邀约', '10.4w', '233', '90', '展会主办方精准寻找全国各地的茶叶品牌商、茶具厂家，邀约其参展茶博会。', '["茶叶品牌创始人","茶具生产厂家老板","茶业大经销商","农业合作社负责人"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"行业截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布往届展会火爆盛况及成交数据，全天监控\'茶叶批发\'\'茶博会\'关键词，下午私信发送展位分布图，晚上加微发送正式招展函。', '每天发布与"全国茶博会展商定向邀约"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '茶叶品牌创始人、茶具生产厂家老板、茶业大经销商、农业合作社负责人', '["/static/case/templates/enterprise-exhibition-franchise/01.jpg","/static/case/templates/enterprise-exhibition-franchise/02.jpg","/static/case/templates/enterprise-exhibition-franchise/03.jpg","/static/case/templates/enterprise-exhibition-franchise/04.jpg","/static/case/templates/enterprise-exhibition-franchise/05.jpg","/static/case/templates/enterprise-exhibition-franchise/06.jpg"]', '["/static/case/templates/enterprise-exhibition-franchise/01.mp4"]', 1, 1713945600, 1713945600),
(3, '财务软件精准营销与推广', '11.2w', '258', '49', '软件服务商精准寻找企业财务人员及代账公司，推广高效的智能财务管理软件。', '["企业财务总监","基层会计人员","代账公司老板","审计人员"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"同行截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布实用的财务做账干货教程，中午截流财税知识博主评论区，下午私信赠送软件7天试用账号，晚上加微发送系统操作手册。', '每天发布与"财务软件精准营销与推广"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '企业财务总监、基层会计人员、代账公司老板、审计人员', '["/static/case/templates/enterprise-finance-software/01.jpg","/static/case/templates/enterprise-finance-software/02.jpg","/static/case/templates/enterprise-finance-software/03.jpg","/static/case/templates/enterprise-finance-software/04.jpg","/static/case/templates/enterprise-finance-software/05.jpg","/static/case/templates/enterprise-finance-software/06.jpg"]', '["/static/case/templates/enterprise-finance-software/01.mp4"]', 1, 1713945600, 1713945600),
(3, '财税平台全国合伙人招募', '9.1w', '201', '76', '综合性财税服务平台面向全国招募代账公司老板及财税从业者，共建行业生态。', '["代账公司老板","财税工作室负责人","资深注册会计师","企业服务领域创业者"]', '[{"type":"行业截流","time":"12:00-18:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布代账行业痛点分析视频，全天监控\'代账创业\'\'财税获客\'等关键词，下午私信探讨业务赋能模式，晚上加微发送合伙人政策。', '每天发布与"财税平台全国合伙人招募"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '代账公司老板、财税工作室负责人、资深注册会计师、企业服务领域创业者', '["/static/case/templates/enterprise-finance-platform/01.jpg","/static/case/templates/enterprise-finance-platform/02.jpg","/static/case/templates/enterprise-finance-platform/03.jpg","/static/case/templates/enterprise-finance-platform/04.jpg","/static/case/templates/enterprise-finance-platform/05.jpg","/static/case/templates/enterprise-finance-platform/06.jpg","/static/case/templates/enterprise-finance-platform/07.jpg","/static/case/templates/enterprise-finance-platform/08.jpg","/static/case/templates/enterprise-finance-platform/09.jpg"]', '["/static/case/templates/enterprise-finance-platform/01.mp4"]', 1, 1713945600, 1713945600),
(3, '冷库保温工程精准拓客', '6.8w', '149', '37', '保温材料工程商精准寻找拥有冷库设施的企业主，提供专业的保温材料及施工服务。', '["冷链物流老板","农产品批发商","食品加工厂厂长","生鲜电商供应链负责人"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"行业截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布冷库节能改造施工案例，中午监控\'冷库建设\'\'冷链物流\'关键词截流，下午私信提供免费上门测算服务，晚上加微发送定制施工方案。', '每天发布与"冷库保温工程精准拓客"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '冷链物流老板、农产品批发商、食品加工厂厂长、生鲜电商供应链负责人', '["/static/case/templates/enterprise-insulation-material/01.jpg","/static/case/templates/enterprise-insulation-material/02.jpg","/static/case/templates/enterprise-insulation-material/03.jpg","/static/case/templates/enterprise-insulation-material/04.jpg","/static/case/templates/enterprise-insulation-material/05.jpg","/static/case/templates/enterprise-insulation-material/06.jpg","/static/case/templates/enterprise-insulation-material/07.jpg","/static/case/templates/enterprise-insulation-material/08.jpg","/static/case/templates/enterprise-insulation-material/09.jpg"]', '["/static/case/templates/enterprise-insulation-material/01.mp4"]', 1, 1713945600, 1713945600);

INSERT INTO `la_catering_franchise` (
  `category_type`, `title`, `exposure`, `leads`, `convert_users`, `intro`,
  `target_users`, `task_types`, `detail_content`, `detail_task_types`,
  `detail_users`, `detail_images`, `detail_videos`, `status`, `create_time`, `update_time`
) VALUES 
(3, '农资产品定向推广(樱桃园)', '13.4w', '307', '42', '农资企业或服务商精准寻找樱桃采摘园及种植大户，推广专用肥料或农业技术服务。', '["樱桃采摘园园主","果树种植大户","农业合作社负责人","基层农资经销商"]', '[{"type":"区域获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布樱桃增产及防裂果技术视频，中午监控\'樱桃种植\'关键词，下午私信解答果农病虫害问题，晚上加微寄送肥料试用装。', '每天发布与"农资产品定向推广(樱桃园)"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '樱桃采摘园园主、果树种植大户、农业合作社负责人、基层农资经销商', '["/static/case/templates/enterprise-agriculture-industry/01.jpg","/static/case/templates/enterprise-agriculture-industry/02.jpg"]', '["/static/case/templates/enterprise-agriculture-industry/01.mp4"]', 1, 1713945600, 1713945600),
(3, '驾考理论培训项目全国招商', '8.9w', '156', '80', '驾考理论速成项目方精准寻找全国驾校教练及驾校校长，推广理论培训合作方案。', '["驾校教练","驾校校长","驾考招生代理","驾培行业创业者"]', '[{"type":"同行截流","time":"12:00-18:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布学员理论快速过考反馈截图，全天截流驾考通关相关视频评论区，下午私信介绍学员分成模式，晚上加微发送合作协议。', '每天发布与"驾考理论培训项目全国招商"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '驾校教练、驾校校长、驾考招生代理、驾培行业创业者', '["/static/case/templates/enterprise-driving-school-franchise/01.jpg","/static/case/templates/enterprise-driving-school-franchise/02.jpg","/static/case/templates/enterprise-driving-school-franchise/03.jpg","/static/case/templates/enterprise-driving-school-franchise/04.jpg"]', '["/static/case/templates/enterprise-driving-school-franchise/01.mp4"]', 1, 1713945600, 1713945600),
(3, '定制淋浴房全国渠道招商', '6.7w', '98', '94', '淋浴房源头工厂精准寻找全国各地的装修公司及设计师，建立长期供货与返佣合作。', '["装修公司老板","独立室内设计师","建材门店老板","工程项目包工头"]', '[{"type":"行业截流","time":"12:00-18:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布极简高端淋浴房安装案例，中午监控\'全屋定制\'\'卫生间装修\'关键词，下午私信发送渠道底价报价单，晚上加微寄送玻璃/五金材质小样。', '每天发布与"定制淋浴房全国渠道招商"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '装修公司老板、独立室内设计师、建材门店老板、工程项目包工头', '["/static/case/templates/enterprise-building-material-franchise/01.jpg","/static/case/templates/enterprise-building-material-franchise/02.jpg","/static/case/templates/enterprise-building-material-franchise/03.jpg","/static/case/templates/enterprise-building-material-franchise/04.jpg","/static/case/templates/enterprise-building-material-franchise/05.jpg"]', '["/static/case/templates/enterprise-building-material-franchise/01.mp4"]', 1, 1713945600, 1713945600),
(3, '幼儿园定向音箱设备直销', '5.9w', '121', '97', '校园广播设备厂家精准寻找幼儿园园长及后勤负责人，推广不扰民的定向音箱设备。', '["幼儿园园长","早教中心负责人","学校后勤采购主任","教育局装备负责人"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"行业截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布设备解决校园广播扰民痛点视频，全天监控\'幼儿园装修\'\'幼教设备\'关键词，下午私信发送声场测试对比，晚上加微预约上门试听体验。', '每天发布与"幼儿园定向音箱设备直销"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '幼儿园园长、早教中心负责人、学校后勤采购主任、教育局装备负责人', '["/static/case/templates/enterprise-audio-equipment/01.jpg","/static/case/templates/enterprise-audio-equipment/02.jpg","/static/case/templates/enterprise-audio-equipment/03.jpg","/static/case/templates/enterprise-audio-equipment/04.jpg","/static/case/templates/enterprise-audio-equipment/05.jpg","/static/case/templates/enterprise-audio-equipment/06.jpg","/static/case/templates/enterprise-audio-equipment/07.jpg","/static/case/templates/enterprise-audio-equipment/08.jpg"]', '["/static/case/templates/enterprise-audio-equipment/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城家装精准获客与转化', '10.2w', '227', '35', '装修公司精准锁定同城近期购房的准交房业主，推送免费量房及装修优惠活动。', '["近期交房小区业主","二手房买家","准备结婚的刚需购房者","改善型住房业主"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"同城截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布同城热门小区实景避坑案例，中午截流同城房产博主及验房博主评论区，下午私信提供3个免费设计名额，晚上加微发送半包/全包报价明细。', '每天发布与"同城家装精准获客与转化"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '近期交房小区业主、二手房买家、准备结婚的刚需购房者、改善型住房业主', '["/static/case/templates/enterprise-home-renovation/01.jpg","/static/case/templates/enterprise-home-renovation/02.jpg","/static/case/templates/enterprise-home-renovation/03.jpg","/static/case/templates/enterprise-home-renovation/04.jpg","/static/case/templates/enterprise-home-renovation/05.jpg","/static/case/templates/enterprise-home-renovation/06.jpg","/static/case/templates/enterprise-home-renovation/07.jpg"]', '["/static/case/templates/enterprise-home-renovation/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城少儿国防夏令营招生', '7.8w', '168', '50', '素质教育机构精准寻找同城中小学生家长，推广培养孩子独立性的国防军事夏令营。', '["7-15岁中小学生家长","关注素质教育的父母","周边社区全职妈妈"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布往期夏令营高燃军训混剪，中午监控\'同城带娃\'\'暑期安排\'关键词，下午私信发送早鸟报名优惠券，晚上加微发送详细行程安排表。', '每天发布与"同城少儿国防夏令营招生"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '7-15岁中小学生家长、关注素质教育的父母、周边社区全职妈妈', '["/static/case/templates/enterprise-national-defense-education/01.jpg","/static/case/templates/enterprise-national-defense-education/02.jpg","/static/case/templates/enterprise-national-defense-education/03.jpg","/static/case/templates/enterprise-national-defense-education/04.jpg","/static/case/templates/enterprise-national-defense-education/05.jpg","/static/case/templates/enterprise-national-defense-education/06.jpg"]', '["/static/case/templates/enterprise-national-defense-education/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城婚宴酒店精准预订', '16.5w', '389', '72', '婚宴主题酒店精准寻找同城备婚新人，推送婚宴场地实景及备婚一站式优惠套餐。', '["备婚准新娘/新郎","近期订婚的情侣","寻找婚宴场地的长辈"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布唯美婚礼现场及灯光秀视频，中午监控\'同城备婚\'\'婚宴场地\'关键词截流，下午私信邀约周末到店试菜，晚上加微发送档期及菜单报价。', '每天发布与"同城婚宴酒店精准预订"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '备婚准新娘/新郎、近期订婚的情侣、寻找婚宴场地的长辈', '["/static/case/templates/enterprise-wedding-hotel/01.jpg","/static/case/templates/enterprise-wedding-hotel/02.jpg","/static/case/templates/enterprise-wedding-hotel/03.jpg","/static/case/templates/enterprise-wedding-hotel/04.jpg","/static/case/templates/enterprise-wedding-hotel/05.jpg","/static/case/templates/enterprise-wedding-hotel/06.jpg","/static/case/templates/enterprise-wedding-hotel/07.jpg","/static/case/templates/enterprise-wedding-hotel/08.jpg","/static/case/templates/enterprise-wedding-hotel/09.jpg"]', '["/static/case/templates/enterprise-wedding-hotel/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城医美机构精准拓客', '22.1w', '542', '86', '医疗美容机构针对同城有抗衰、微调等变美需求的女性群体，推送体验卡及团购项目。', '["20-45岁关注颜值的女性","有抗衰需求的职场白领","产后修复宝妈群体"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"同行截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布素人改造或抗衰科普对比视频，中午截流同城美妆/探店博主评论区，下午私信发送新客水光针/光子体验券，晚上加微预约专家面诊时间。', '每天发布与"同城医美机构精准拓客"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '20-45岁关注颜值的女性、有抗衰需求的职场白领、产后修复宝妈群体', '["/static/case/templates/enterprise-medical-beauty/01.jpg","/static/case/templates/enterprise-medical-beauty/02.jpg"]', '["/static/case/templates/enterprise-medical-beauty/01.mp4"]', 1, 1713945600, 1713945600),
(3, '同城商业空间公装服务', '9.4w', '205', '91', '公装公司精准寻找同城准备开店或翻新门店的商铺老板，提供专业的商业空间设计与装修服务。', '["餐饮店准老板","服装店主","美容院老板","准备创业开实体店的人群"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布同城网红店面装修落地案例，全天监控\'同城商铺出租\'\'旺铺转让\'关键词，下午私信提供免费平面布局规划，晚上加微发送公装预算表。', '每天发布与"同城商业空间公装服务"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '餐饮店准老板、服装店主、美容院老板、准备创业开实体店的人群', '["/static/case/templates/enterprise-commercial-renovation/01.jpg","/static/case/templates/enterprise-commercial-renovation/02.jpg","/static/case/templates/enterprise-commercial-renovation/03.jpg","/static/case/templates/enterprise-commercial-renovation/04.jpg","/static/case/templates/enterprise-commercial-renovation/05.jpg","/static/case/templates/enterprise-commercial-renovation/06.jpg","/static/case/templates/enterprise-commercial-renovation/07.jpg"]', '["/static/case/templates/enterprise-commercial-renovation/01.mp4"]', 1, 1713945600, 1713945600),
(1, '周边美容院精准引流到店', '17.3w', '421', '40', '实体美容门店精准锁定周边3公里的适龄女性，通过推送超值体验团购券吸引到店消费。', '["周边3公里小区20-55岁女性","全职宝妈","附近写字楼女白领"]', '[{"type":"附近推流","time":"10:00-20:00"},{"type":"同城获客","time":"10:00-20:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布沉浸式肩颈疏通或护肤体验视频，中午利用附近推流触达周边人群，下午私信发送9.9元拓客体验券，晚上加微确认到店时间及核销。', '每天发布与"周边美容院精准引流到店"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '周边3公里小区20-55岁女性、全职宝妈、附近写字楼女白领', '["/static/case/templates/enterprise-beauty-industry/01.jpg","/static/case/templates/enterprise-beauty-industry/02.jpg","/static/case/templates/enterprise-beauty-industry/03.jpg","/static/case/templates/enterprise-beauty-industry/04.jpg","/static/case/templates/enterprise-beauty-industry/05.jpg","/static/case/templates/enterprise-beauty-industry/06.jpg","/static/case/templates/enterprise-beauty-industry/07.jpg","/static/case/templates/enterprise-beauty-industry/08.jpg"]', '["/static/case/templates/enterprise-beauty-industry/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城国企就业培训招募', '11.6w', '274', '71', '就业培训机构精准寻找同城有求职需求的高校毕业生及待业青年，提供国企央企考前培训服务。', '["应届大学毕业生","待业青年","对现状不满意的职场人","求职者家长"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布同城国企/烟草/电网招聘公告解读，中午监控\'同城找工作\'\'考编\'关键词截流，下午私信发送历年真题资料包，晚上加微邀请试听名师公开课。', '每天发布与"同城国企就业培训招募"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '应届大学毕业生、待业青年、对现状不满意的职场人、求职者家长', '["/static/case/templates/enterprise-employment-training/01.jpg","/static/case/templates/enterprise-employment-training/02.jpg","/static/case/templates/enterprise-employment-training/03.jpg","/static/case/templates/enterprise-employment-training/04.jpg","/static/case/templates/enterprise-employment-training/05.jpg","/static/case/templates/enterprise-employment-training/06.jpg"]', '["/static/case/templates/enterprise-employment-training/01.mp4"]', 1, 1713945600, 1713945600);

INSERT INTO `la_catering_franchise` (
  `category_type`, `title`, `exposure`, `leads`, `convert_users`, `intro`,
  `target_users`, `task_types`, `detail_content`, `detail_task_types`,
  `detail_users`, `detail_images`, `detail_videos`, `status`, `create_time`, `update_time`
) VALUES 
(1, 'AI体验馆同城精准引流', '8.3w', '147', '69', '新型AI科技体验馆精准锁定同城及周边20公里内对前沿科技感兴趣的男性群体，邀约到店体验。', '["18-40岁科技发烧友","极客青年","游戏玩家","周边高校男大学生"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布AI设备震撼视觉体验视频，中午监控\'同城周末去哪\'\'VR体验\'关键词，下午私信发送早鸟特惠体验票，晚上加微发送门店导航及停车指引。', '每天发布与"AI体验馆同城精准引流"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '18-40岁科技发烧友、极客青年、游戏玩家、周边高校男大学生', '["/static/case/templates/enterprise-adult-experience/01.jpg","/static/case/templates/enterprise-adult-experience/02.jpg","/static/case/templates/enterprise-adult-experience/03.jpg","/static/case/templates/enterprise-adult-experience/04.jpg"]', '["/static/case/templates/enterprise-adult-experience/01.mp4"]', 1, 1713945600, 1713945600),
(1, '中高考冲刺提分精准招生', '14.7w', '318', '53', '课外辅导机构精准寻找家有中高考考生的家长群体，推广名师冲刺提分课程及辅导资料。', '["初三/高三学生家长","成绩遇瓶颈的考生","极度关注升学率的父母"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"同城获客","time":"10:00-20:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布中高考压轴题提分技巧视频，中午截流同城教育博主评论区，下午私信发送学科弱点诊断测试卷，晚上加微预约名师一对一升学规划。', '每天发布与"中高考冲刺提分精准招生"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '初三/高三学生家长、成绩遇瓶颈的考生、极度关注升学率的父母', '["/static/case/templates/enterprise-score-improvement/01.jpg","/static/case/templates/enterprise-score-improvement/02.jpg","/static/case/templates/enterprise-score-improvement/03.jpg","/static/case/templates/enterprise-score-improvement/04.jpg","/static/case/templates/enterprise-score-improvement/05.jpg","/static/case/templates/enterprise-score-improvement/06.jpg","/static/case/templates/enterprise-score-improvement/07.jpg"]', '["/static/case/templates/enterprise-score-improvement/01.mp4"]', 1, 1713945600, 1713945600),
(1, '高考志愿填报精准指导', '12.3w', '266', '94', '升学规划机构精准寻找高三应届生家长，提供专业的高考志愿填报一对一指导服务。', '["高三应届生家长","复读生家长","对志愿填报及专业选择迷茫的考生"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"行业截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布热门专业就业前景解析视频，全天监控\'高考志愿\'\'张雪峰\'等关键词截流，下午私信发送本省历年录取分数线，晚上加微邀约线下公益讲座。', '每天发布与"高考志愿填报精准指导"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '高三应届生家长、复读生家长、对志愿填报及专业选择迷茫的考生', '["/static/case/templates/enterprise-college-exam-registration/01.jpg","/static/case/templates/enterprise-college-exam-registration/02.jpg","/static/case/templates/enterprise-college-exam-registration/03.jpg","/static/case/templates/enterprise-college-exam-registration/04.jpg","/static/case/templates/enterprise-college-exam-registration/05.jpg","/static/case/templates/enterprise-college-exam-registration/06.jpg"]', '["/static/case/templates/enterprise-college-exam-registration/01.mp4"]', 1, 1713945600, 1713945600),
(1, '周边烘焙甜品店引流促销', '13.8w', '352', '46', '社区烘焙蛋糕店精准锁定周边3公里内的居民及上班族，通过发放专属优惠券促进日常消费与生日预订。', '["周边3公里社区居民","附近写字楼白领","有生日订制需求的客户","宝妈"]', '[{"type":"附近推流","time":"10:00-20:00"},{"type":"同城获客","time":"10:00-20:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布新鲜出炉高颜值甜品视频，中午利用附近推流发放下午茶满减优惠券，下午私信接单生日蛋糕预订，晚上加微沉淀至私域福利社群。', '每天发布与"周边烘焙甜品店引流促销"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '周边3公里社区居民、附近写字楼白领、有生日订制需求的客户、宝妈', '["/static/case/templates/enterprise-cake-dessert/01.jpg"]', '["/static/case/templates/enterprise-cake-dessert/01.mp4"]', 1, 1713945600, 1713945600),
(1, '同城餐饮门店团购大促', '31.5w', '876', '58', '餐饮实体门店精准覆盖周边10公里内的吃货群体，通过推送高性价比团购套餐提升门店上座率。', '["周边10公里居民","同城美食爱好者","周末聚餐人群","公司团建负责人"]', '[{"type":"附近推流","time":"10:00-20:00"},{"type":"同城获客","time":"10:00-20:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布招牌菜诱人制作过程视频，中午饭点前推送特价双人餐团购链接，下午私信解答包厢预订及营业时间，晚上加微引导好评返现或赠送小菜。', '每天发布与"同城餐饮门店团购大促"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '周边10公里居民、同城美食爱好者、周末聚餐人群、公司团建负责人', '["/static/case/templates/enterprise-catering-industry/01.jpg","/static/case/templates/enterprise-catering-industry/02.jpg","/static/case/templates/enterprise-catering-industry/03.jpg","/static/case/templates/enterprise-catering-industry/04.jpg"]', '["/static/case/templates/enterprise-catering-industry/01.mp4"]', 1, 1713945600, 1713945600),
(1, '高端头皮抗衰门店拓客', '10.9w', '244', '95', '高端头皮管理中心精准寻找周边10公里内有脱发困扰及注重保养的高净值人群，邀约到店体验。', '["30-55岁高净值人群","有脱发/白发焦虑的职场高管","注重抗衰的贵妇群体"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布头皮检测与脱发科普视频，中午监控\'防脱发\'\'植发\'关键词截流，下午私信发送价值598元免费毛囊检测名额，晚上加微发送日常护理建议。', '每天发布与"高端头皮抗衰门店拓客"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '30-55岁高净值人群、有脱发/白发焦虑的职场高管、注重抗衰的贵妇群体', '["/static/case/templates/enterprise-scalp-care/01.jpg","/static/case/templates/enterprise-scalp-care/02.jpg"]', '["/static/case/templates/enterprise-scalp-care/01.mp4"]', 1, 1713945600, 1713945600),
(1, '室内潮玩娱乐同城曝光', '19.2w', '463', '85', '室内游乐场及潮玩空间精准触达周边10公里内的亲子家庭与年轻情侣，通过持续曝光打造同城网红打卡地。', '["周末遛娃的年轻父母","同城大学生","年轻情侣","团建活动组织者"]', '[{"type":"附近推流","time":"10:00-20:00"},{"type":"同城获客","time":"10:00-20:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '上午发布游乐设施沉浸式第一视角体验视频，中午利用附近推流触达目标人群，下午私信发送门票代金券或双人同行半价券，晚上加微拉入玩家组局社群。', '每天发布与"室内潮玩娱乐同城曝光"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '周末遛娃的年轻父母、同城大学生、年轻情侣、团建活动组织者', '["/static/case/templates/enterprise-indoor-entertainment/01.jpg","/static/case/templates/enterprise-indoor-entertainment/02.jpg","/static/case/templates/enterprise-indoor-entertainment/03.jpg"]', '["/static/case/templates/enterprise-indoor-entertainment/01.mp4"]', 1, 1713945600, 1713945600),
(2, '职场人副业变现个人IP精准引流', '10.2w', '231', '81', '有副业变现经验的职场博主，精准触达对副业感兴趣的上班族，推广付费课程或1对1咨询服务。', '["25-40岁上班族","对现状不满的打工人","想增加收入的宝妈","应届毕业生"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"评论区截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布月入X万副业真实收入截图视频，全天监控\'副业\'\'下班赚钱\'等关键词截流，下午私信发送免费副业测评资料包，晚上加微邀约进入付费社群或1对1咨询。', '每天发布与"职场人副业变现个人IP精准引流"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '25-40岁上班族、对现状不满的打工人、想增加收入的宝妈、应届毕业生', '["/static/case/templates/personalip-workplace-sidejob/01.png","/static/case/templates/personalip-workplace-sidejob/02.png","/static/case/templates/personalip-workplace-sidejob/03.png","/static/case/templates/personalip-workplace-sidejob/04.png","/static/case/templates/personalip-workplace-sidejob/05.png"]', '["/static/case/templates/personalip-workplace-sidejob/01.mp4"]', 1, 1713945600, 1713945600),
(2, '健身私教个人IP同城精准获客', '9.6w', '214', '63', '健身私教博主精准锁定同城有减脂塑形需求的人群，通过内容种草吸引私教课程报名。', '["20-45岁有减脂需求的女性","想增肌的男性","产后恢复宝妈","备婚新娘"]', '[{"type":"同城获客","time":"10:00-20:00"},{"type":"关键词监控","time":"09:00-22:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布学员30天减脂前后对比视频，中午监控\'同城减肥\'\'健身房推荐\'关键词截流，下午私信发送免费体测+1节体验课名额，晚上加微发送课程方案及优惠套餐。', '每天发布与"健身私教个人IP同城精准获客"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '20-45岁有减脂需求的女性、想增肌的男性、产后恢复宝妈、备婚新娘', '["/static/case/templates/personalip-fitness-trainer/01.png","/static/case/templates/personalip-fitness-trainer/02.png","/static/case/templates/personalip-fitness-trainer/03.png","/static/case/templates/personalip-fitness-trainer/04.png","/static/case/templates/personalip-fitness-trainer/05.png"]', '["/static/case/templates/personalip-fitness-trainer/01.mp4"]', 1, 1713945600, 1713945600),
(2, '律师个人IP精准引流变现', '8.7w', '196', '61', '执业律师或法律博主通过内容输出建立专业信任，精准触达有法律咨询需求的个人与企业。', '["有劳动纠纷的打工人","面临离婚诉讼的当事人","有合同纠纷的企业主","创业者"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"行业截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布真实案例解析视频（劳动仲裁/离婚财产分割等），全天监控\'劳动仲裁\'\'被公司开除\'等关键词截流，下午私信提供15分钟免费法律咨询，晚上加微沉淀至私域并推送付费咨询服务。', '每天发布与"律师个人IP精准引流变现"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '有劳动纠纷的打工人、面临离婚诉讼的当事人、有合同纠纷的企业主、创业者', '["/static/case/templates/personalip-lawyer-ip/01.png","/static/case/templates/personalip-lawyer-ip/02.png","/static/case/templates/personalip-lawyer-ip/03.png"]', '["/static/case/templates/personalip-lawyer-ip/01.mp4"]', 1, 1713945600, 1713945600);

INSERT INTO `la_catering_franchise` (
  `category_type`, `title`, `exposure`, `leads`, `convert_users`, `intro`,
  `target_users`, `task_types`, `detail_content`, `detail_task_types`,
  `detail_users`, `detail_images`, `detail_videos`, `status`, `create_time`, `update_time`
) VALUES 
(2, '亲子教育博主课程精准招生', '11.3w', '253', '38', '专注家庭教育的博主，精准触达有育儿焦虑的家长群体，推广正面管教/亲子沟通付费课程。', '["3-12岁孩子的家长","有亲子沟通困扰的父母","全职妈妈","关注孩子心理健康的家庭"]', '[{"type":"关键词监控","time":"09:00-22:00"},{"type":"同行截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布孩子不听话怎么办实用沟通技巧视频，中午截流同城育儿博主评论区，下午私信发送0-12岁孩子教育误区免费电子书，晚上加微邀约进入家长成长社群并推送课程。', '每天发布与"亲子教育博主课程精准招生"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '3-12岁孩子的家长、有亲子沟通困扰的父母、全职妈妈、关注孩子心理健康的家庭', '["/static/case/templates/personalip-parenting-education/01.png","/static/case/templates/personalip-parenting-education/02.png","/static/case/templates/personalip-parenting-education/03.png","/static/case/templates/personalip-parenting-education/04.png"]', '["/static/case/templates/personalip-parenting-education/01.mp4"]', 1, 1713945600, 1713945600),
(2, '雅思托福备考社群精准招募', '9.1w', '203', '84', '备考博主通过输出实用雅思/托福备考内容，精准吸引有考试提升需求的人群，转化付费打卡社群或课程。', '["有出国留学计划的大学生","职场人士考雅思移民/晋升","多次考试未达目标的刷题党","想送孩子出国的家长"]', '[{"type":"关键词截流","time":"09:00-22:00"},{"type":"同行截流","time":"12:00-18:00"},{"type":"私信接管","time":"15:00-22:00"},{"type":"自动加微","time":"18:00-23:00"}]', '早上发布每天5分钟雅思/托福备考干货短视频，中午截流雅思/托福机构博主及留学博主评论区，下午私信赠送雅思托福备考资料包，晚上加微邀约进入21天雅思冲分付费打卡社群。', '每天发布与"雅思托福备考社群精准招募"相关的核心内容（案例展示、效果对比、用户反馈等）吸引目标用户；同时监控行业关键词并在同行内容评论区主动截流潜在客户，通过评论互动或私信触达；下午集中进行私信沟通筛选意向用户并解答问题；晚上分批引导添加微信，发送详细资料、方案或报价，完成转化沉淀。', '有出国留学计划的大学生、职场人士考雅思移民/晋升、多次考试未达目标的刷题党、想送孩子出国的家长', '["/static/case/templates/personalip-ielts-toefl/01.png","/static/case/templates/personalip-ielts-toefl/02.png"]', '["/static/case/templates/personalip-ielts-toefl/01.mp4"]', 1, 1713945600, 1713945600);



ALTER TABLE `la_ai_persona` 
ADD COLUMN `wechat_publish_mode` tinyint(1) NOT NULL DEFAULT 3 COMMENT '发布模式: 1=素材制作视频发送 2=直接发送素材内容 3=ip人设素材制作视频发送' ;

ALTER TABLE `la_ai_persona_material` 
ADD COLUMN `is_wechat` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '微信朋友圈: 0=否 1=是';

ALTER TABLE `la_sv_device` 
ADD COLUMN `synthesis_w` tinyint(4) UNSIGNED NULL DEFAULT 0 COMMENT '微信朋友圈视频合成0没有1合成',
ADD COLUMN `synthesis_m` tinyint(4) UNSIGNED NULL DEFAULT 0 COMMENT '社媒平台视频合成 0没有1合成';

DELETE FROM `la_dev_crontab`
WHERE `command` = "wechat_video_synthesis";

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time` )
VALUES
	( '朋友圈自动合成视频任务生成', 1, 0, '', 'wechat_video_synthesis', '', 1, '*/10 * * * *', NULL, 1766678409, '1.91', '1.91', 1766542031, 1766734271, NULL );

DELETE FROM `la_dev_crontab`
WHERE
    `command` = "auto_device_video_synthesis";

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time` )
VALUES
	( '自动合成视频任务生成', 1, 0, '', 'auto_device_video_synthesis', '', 1, '*/10 * * * *', NULL, 1766678409, '1.91', '1.91', 1766542031, 1766734271, NULL );


DELETE FROM `la_dev_crontab`
WHERE
    `command` = "reset_video_synthesis";

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time` )
VALUES
	( '重置合成视频任务', 1, 0, '', 'reset_video_synthesis', '', 1, '0 0 * * *', NULL, 1766678409, '1.91', '1.91', 1766542031, 1766734271, NULL );  


ALTER TABLE `la_ai_persona_material_use_log` 
ADD COLUMN `is_wechat` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '微信朋友圈: 0=否 1=是' ;

UPDATE `la_model_config` SET `score` = 2.00 WHERE `scene` = 'human_video_ymt';
UPDATE `la_model_config` SET `score` = 3.00 WHERE `scene` = 'human_video_chanjing';
UPDATE `la_model_config` SET `score` = 5.00 WHERE `scene` = 'human_video_shanjian';
UPDATE `la_model_config` SET `score` = 25.00 WHERE `scene` = 'doubao_img_to_video';
UPDATE `la_model_config` SET `score` = 1.00 WHERE `scene` = 'sph_ocr';
UPDATE `la_model_config` SET `score` = 2.00 WHERE `scene` = 'keyword_to_copywriting';

INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('automation_city_exposure', 10316, '算力/次', '(自动化)同城曝光任务', 1.00, '按照主动私评论时进行扣费，一次1算力', 1, NULL, NULL);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('automation_city_touch', 10317, '算力/次', '(自动化)同城视频截流任务', 1.00, '按照主动私评论时进行扣费，一次1算力', 1, NULL, NULL);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('automation_group_buy', 10318, '算力/次', '(自动化)团购任务', 1.00, '按照主动私评论时进行扣费，一次1算力', 1, NULL, NULL);

UPDATE `la_models` SET `is_enable` = 0 WHERE `id` = 15;
UPDATE `la_models_cost` SET `status` = 0 WHERE `id` = 15;
UPDATE `la_config` SET `value` = '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"Ai-4.0","model_id":15,"model_sub_id":15,"status":"0","logo":"static/images/models/3.png"},{"id":"2","name":"Ai-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"Ai-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"Ai-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"3","name":"谷歌智元2.5 PRO","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"谷歌智元3.0","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"},{"id":"10","name":"克洛德4.5","model_id":18,"model_sub_id":18,"status":"1","logo":"static/images/models/4.png"}]}' WHERE `type` = 'chat' AND `name` = 'ai_model';
