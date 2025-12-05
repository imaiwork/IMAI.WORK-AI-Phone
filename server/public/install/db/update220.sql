CREATE TABLE IF NOT EXISTS `la_models` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
`type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '模型类型: [1=对话模型, 2=向量模型]',
`channel` varchar(30) NOT NULL DEFAULT '' COMMENT '模型渠道',
`logo` varchar(300) NOT NULL DEFAULT '' COMMENT '模型图标',
`name` varchar(100) NOT NULL DEFAULT '' COMMENT '模型名称',
`remarks` varchar(800) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '模型描述',
`configs` text COMMENT '模型配置',
`sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序编号',
`is_system` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统内置: [0=否, 1=是]',
`is_enable` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用: [0=否, 1=是]',
`is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认: [0=否, 1=是]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型管理表';

CREATE TABLE IF NOT EXISTS `la_models_cost` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`model_id` int(10) NOT NULL COMMENT '模型ID',
`type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '模型类型: [1=对话模型, 2=向量模型]',
`channel` varchar(100) NOT NULL DEFAULT '' COMMENT '模型渠道',
`name` varchar(100) NOT NULL DEFAULT '' COMMENT '模型名称',
`alias` varchar(100) NOT NULL DEFAULT '' COMMENT '模型别名',
`price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '消费价格',
`sort` int(10) unsigned NOT NULL COMMENT '排序编号',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用: [0=否, 1=是]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型计费表';

CREATE TABLE IF NOT EXISTS `la_kb_know` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`create_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
`image` varchar(250) NOT NULL DEFAULT '' COMMENT '知识库封面',
`name` varchar(100) NOT NULL DEFAULT '' COMMENT '知识库名称',
`intro` varchar(500) NOT NULL DEFAULT '' COMMENT '知识库简介',
`documents_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理模型ID',
`documents_model_sub_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理模型子ID',
`embedding_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '向量模型ID',
`embedding_model_sub_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '向量模型子ID',
`documents_model` varchar(100) DEFAULT '' COMMENT '处理模型 (废弃)',
`embedding_model` varchar(100) DEFAULT '' COMMENT '向量训练模型 (废弃)',
`is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用: [0=否, 1=是]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库管理表';

CREATE TABLE IF NOT EXISTS `la_kb_know_files` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属用户ID',
`know_id` int(10) unsigned NOT NULL COMMENT '知识库ID',
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '文件名称',
`file` varchar(300) NOT NULL DEFAULT '' COMMENT '文件路径',
`size` float DEFAULT '0' COMMENT '文件大小',
`type` varchar(30) NOT NULL DEFAULT '' COMMENT '文件类型',
`is_qa` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'QA拆分: [0=否, 1=待拆分, 2=拆分完成]',
`is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '默认固定: [0=否, 1=是]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引',
KEY `know_idx` (`know_id`) USING BTREE COMMENT '知识库索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库文件表';

CREATE TABLE IF NOT EXISTS `la_kb_know_qa` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '模型的ID',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户的ID',
`kb_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '知识库ID',
`fd_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件的ID',
`name` varchar(300) NOT NULL DEFAULT '' COMMENT '文件名称',
`content` text COMMENT '文本内容',
`results` text COMMENT '拆分结果',
`usage` text COMMENT 'tokens信息',
`tokens` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消耗的tokens',
`model` varchar(100) NOT NULL DEFAULT '' COMMENT '拆分的模型',
`status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '拆分状态: [0=等待拆分, 1=拆分中, 2=拆分成功, 3=拆分失败]',
`error` text COMMENT '错误信息',
`task_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务耗时',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `kb_idx` (`kb_id`) USING BTREE COMMENT '知识库索引',
KEY `fd_idx` (`fd_id`) USING BTREE COMMENT '文件的索引',
KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库QA表';

CREATE TABLE IF NOT EXISTS `la_kb_know_team` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`kb_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '知识库ID',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户的ID',
`power` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '拥有权限: [1=可管理, 2=可编辑, 3=可查看]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库团队表';

CREATE TABLE IF NOT EXISTS `la_kb_know_test_record` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) DEFAULT '0' COMMENT '用户id',
`kb_id` int(10) unsigned DEFAULT NULL COMMENT '向量知识库id',
`emb_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '向量模型ID',
`ask` longtext COMMENT '提问',
`reply` longtext COMMENT '答复',
`flows` text COMMENT 'tokens信息',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户知识库使用记录';

CREATE TABLE IF NOT EXISTS `la_kb_robot` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类目ID',
`code` varchar(100) NOT NULL DEFAULT '' COMMENT '机器人编号',
`kb_ids` varchar(200) NOT NULL DEFAULT '' COMMENT '关联知识库',
`kb_type` tinyint(1) unsigned DEFAULT '1' COMMENT '知识库类型: [1=RAG, 2=向量]',
`icons` varchar(250) NOT NULL DEFAULT '' COMMENT '对话的图标',
`image` varchar(250) NOT NULL DEFAULT '' COMMENT '机器人封面',
`name` varchar(100) NOT NULL DEFAULT '' COMMENT '机器人名称',
`intro` varchar(500) NOT NULL DEFAULT '' COMMENT '机器人简介',
`sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序的编号',
`model` varchar(100) DEFAULT '' COMMENT 'AI模型',
`model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'AI主模型ID',
`model_sub_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'AI子模型ID',
`roles_prompt` text COMMENT '角色设定词',
`limit_prompt` text COMMENT '系统限定词',
`search_mode` varchar(20) NOT NULL DEFAULT 'similar' COMMENT '检索模式: [similar=语义检索, full=全文检索, mix=混合检索]',
`search_tokens` int(10) unsigned NOT NULL DEFAULT '3000' COMMENT '检索引用数: [0-30000]',
`search_similar` float(5,3) unsigned DEFAULT '0.500' COMMENT '检索相似度: [0 ~ 1]',
`ranking_status` tinyint(1) unsigned DEFAULT '0' COMMENT '重排状态',
`ranking_score` float(5,3) unsigned DEFAULT '0.500' COMMENT '重排分数',
`ranking_model` varchar(200) DEFAULT '' COMMENT '重排模型',
`optimize_ask` tinyint(1) unsigned DEFAULT '0' COMMENT '问题优化',
`optimize_model` varchar(200) DEFAULT '' COMMENT '问题模型',
`temperature` float(2,1) unsigned DEFAULT '0.8' COMMENT '属性温度',
`search_empty_type` tinyint(1) unsigned DEFAULT '1' COMMENT '搜索空类型: [1=GPT回复, 2=固定回复]',
`search_empty_text` text COMMENT '搜索空文本',
`welcome_introducer` text COMMENT '欢迎引导词',
`related_issues_num` int(10) unsigned DEFAULT '0' COMMENT '推荐的问题',
`copyright` text COMMENT '底部的版权',
`share_bg` varchar(300) DEFAULT '' COMMENT '分享背景图',
`digital_bg` varchar(30) DEFAULT NULL COMMENT '数字人背景',
`flow_status` tinyint(1) DEFAULT '0' COMMENT '工作流启用状态',
`flow_config` text COMMENT '工作流配置',
`context_num` int(5) DEFAULT '0' COMMENT '上下文数量',
`digital_id` int(10) unsigned DEFAULT '0' COMMENT '数字人绑定',
`is_digital` tinyint(1) unsigned DEFAULT '1' COMMENT '数字人启用: [0=否, 1=是]',
`is_show_feedback` tinyint(1) unsigned DEFAULT '1' COMMENT '显示反馈: [0=否, 1=是]',
`is_show_context` tinyint(1) unsigned DEFAULT '1' COMMENT '显示上下文: [0=否, 1=是]',
`is_show_quote` tinyint(1) unsigned DEFAULT '1' COMMENT '显示引用词: [0=否, 1=是]',
`is_public` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否公开它: [0=否, 1=是]',
`is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否可使用: [0=否, 1=是]',
`support_file` tinyint(1) unsigned DEFAULT '0' COMMENT '是否支持文件: [0=否, 1=是]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人管理表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_category` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
`name` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
`image` varchar(250) NOT NULL DEFAULT '' COMMENT '图标',
`sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
`is_enable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用: [0=否, 1=是]',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人分类表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_instruct` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户的ID',
`robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`keyword` varchar(200) NOT NULL DEFAULT '' COMMENT '关键词',
`content` text COMMENT '回复内容',
`images` text COMMENT '上传图片',
`sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序编号',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人指令表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_publish` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`type` tinyint(2) NOT NULL COMMENT '类型: [1=网页, 2=公众号, 3=JS嵌入, 4=API调用]',
`chat_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '对话方式: [1=文本, 2=数字人]',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户的ID',
`robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '分享名称',
`apikey` varchar(200) NOT NULL DEFAULT '' COMMENT '渠道编号',
`secret` varchar(200) NOT NULL COMMENT '访问密钥',
`context_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '上下文数',
`limit_total_chat` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户总的限制对话',
`limit_today_chat` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户每天限制对话',
`limit_exceed` varchar(500) NOT NULL DEFAULT '' COMMENT '超出限制默认回复',
`use_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '调用次数',
`use_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引',
KEY `robot_idx` (`robot_id`) USING BTREE COMMENT '机器人索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人发布表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_record` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`user_id` int(10) unsigned NOT NULL COMMENT '用户的ID',
`robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类的ID',
`square_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广场的ID',
`chat_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '对话模型ID',
`emb_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '向量模型ID',
`ask` longtext COMMENT '提问',
`reply` text COMMENT '答复',
`reasoning` text COMMENT '思考过程',
`files_plugin` longtext COMMENT '文件理解',
`images` text COMMENT '附带图片',
`video` text COMMENT '附带视频',
`files` text COMMENT '附带文件',
`quotes` text COMMENT '引用内容',
`context` text COMMENT '上下文组',
`correlation` text COMMENT '相关问题',
`flows` text NOT NULL COMMENT 'tokens信息',
`model` varchar(100) NOT NULL DEFAULT '' COMMENT '对话模型',
`tokens` decimal(15,7) NOT NULL COMMENT '消耗金额',
`feedback` text COMMENT '用户反馈',
`share_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享的ID',
`share_apikey` varchar(80) NOT NULL DEFAULT '' COMMENT '分享的密钥',
`share_identity` varchar(60) NOT NULL DEFAULT '' COMMENT '分享的身份',
`censor_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态: [0=未审核, 1=合规, 2=不合规, 3=疑似, 4=审核失败]',
`censor_result` text COMMENT '审核结果',
`censor_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核次数',
`is_feedback` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否反馈: [0=否, 1=是]',
`is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示: [0=否, 1=是]',
`is_flow` tinyint(1) NOT NULL DEFAULT '0' COMMENT '使用工作流 0-未使用 1-已使用',
`task_time` int(60) unsigned NOT NULL DEFAULT '0' COMMENT '对话耗时',
`unique_id` varchar(100) NOT NULL DEFAULT '' COMMENT '分享唯一ID',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引',
KEY `robot_idx` (`robot_id`) USING BTREE COMMENT '机器人索引',
KEY `share_idx` (`share_id`) USING BTREE COMMENT '分享编号索引',
KEY `identity_idx` (`share_identity`) USING BTREE COMMENT '分享身份索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人对话表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_session` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`square_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广场ID',
`user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
`robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`name` varchar(200) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '分类名称',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE,
KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引',
KEY `robot_idx` (`robot_id`) USING BTREE COMMENT '机器人索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人会话表';

CREATE TABLE IF NOT EXISTS `la_kb_robot_share_log` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
`user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
`robot_id` int(10) unsigned NOT NULL COMMENT '机器人ID',
`balance` decimal(15,7) unsigned NOT NULL DEFAULT '0.0000000' COMMENT '赠送电力值',
`channel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '分享渠道: [1-微信小程序 2-微信公众号 3-手机H5 4-电脑PC 5-苹果APP 6-安卓APP]',
`square_id` int(10) NOT NULL COMMENT '广场id',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人分享记录';

CREATE TABLE IF NOT EXISTS `la_kb_robot_visitor` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
`ip` varchar(100) NOT NULL COMMENT '访客IP',
`robot_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '机器人ID',
`terminal` tinyint(1) NOT NULL COMMENT '访问终端',
`visit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
`create_time` int(10) DEFAULT NULL COMMENT '访问时间',
`update_time` int(10) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `robot_idx` (`robot_id`) USING BTREE COMMENT '机器人索引',
KEY `ip` (`ip`) USING BTREE COMMENT 'IP索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='机器人访问表';

CREATE TABLE IF NOT EXISTS `la_material_music` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
`source_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传者id',
`source` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '来源类型[0-后台,1-用户]',
`style` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '风格0我的,1科技,2悬疑,3抒情,4欢快,5古典,6跳跃',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
`url` varchar(200) NOT NULL COMMENT '文件路径',
`status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示:1-是.0-否',
`sort` int(11) NULL DEFAULT 0 COMMENT '排序',
`create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
`update_time` int(10) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='数字人背景音乐';

CREATE TABLE IF NOT EXISTS `la_sv_crawling_task` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `device_codes` text COMMENT '设备号',
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
    `type` varchar(255) NOT NULL DEFAULT '3' COMMENT '执行平台:3小红书,4视频号',
    `crawl_type` tinyint(4) DEFAULT '0' COMMENT '采集类型0视频获客1账号获客',
    `keywords` text COMMENT '检索关键词',
    `implementation_keywords_number` smallint(5) NOT NULL COMMENT '总关键词数',
    `number_of_implemented_keywords` smallint(5) unsigned DEFAULT '0' COMMENT '已执行关键词数',
    `implementation_total_number` int(10) unsigned DEFAULT '0' COMMENT '获客数',
    `chat_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '自动私聊:0关闭,1开启',
    `chat_number` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '聊天次数',
    `chat_interval_time` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '聊天间隔时间',
    `add_type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '自动加好友:0关闭,1开启',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0未开始,1进行中,2暂停中,3已完成,4已结束',
    `remark` varchar(255) DEFAULT NULL COMMENT '加微好友备注',
    `add_number` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '加好友次数',
    `add_interval_time` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '加好友间隔时间',
    `greeting_content` varchar(255) DEFAULT NULL COMMENT '打招呼内容',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引'
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COMMENT='爬取任务';

CREATE TABLE IF NOT EXISTS `la_sv_crawling_task_device_bind` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
    `task_id` int(11) NOT NULL DEFAULT '0' COMMENT '任务ID',
    `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
    `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '执行状态 0: 待执行 1: 执行中 2：暂停 3：完成',
    `keywords` varchar(1000) NOT NULL DEFAULT '' COMMENT '分配的关键词',
    `exec_keyword` varchar(255) NOT NULL DEFAULT '' COMMENT '当前执行的关键词',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引',
    KEY `task_idx` (`task_id`) USING BTREE COMMENT '任务索引',
    KEY `idx_device_code` (`device_code`) USING BTREE COMMENT '设备号索引'
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COMMENT='任务设备关联表';

CREATE TABLE IF NOT EXISTS `la_sv_crawling_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `task_id` int(11) DEFAULT NULL COMMENT '任务id',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `username` varchar(255) DEFAULT NULL COMMENT '用户名称',
  `image` varchar(255) DEFAULT NULL COMMENT '截图',
  `exec_keyword` varchar(255) DEFAULT NULL COMMENT '执行词',
  `crawl_content` varchar(1000) DEFAULT NULL COMMENT '获取内容',
  `reg_content` varchar(255) DEFAULT NULL COMMENT '提取内容',
  `hash` varchar(255) DEFAULT NULL COMMENT '提取内容hash',
  `clue_type` tinyint(4) DEFAULT '0' COMMENT '线索类型1微信号2手机号',
  `address` varchar(100) DEFAULT NULL COMMENT '客户地址',
  `sub_task_id` varchar(255) DEFAULT NULL COMMENT '线索地址',
  `tokens` int(11) DEFAULT '0' COMMENT '算力消耗',
  `exec_time` datetime DEFAULT NULL COMMENT '执行时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `exec_keyword` (`exec_keyword`) USING BTREE,
  KEY `task_id` (`task_id`) USING BTREE,
  KEY `hash` (`hash`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7669 DEFAULT CHARSET=utf8mb4 COMMENT='获客任务执行记录表';

CREATE TABLE IF NOT EXISTS `la_ai_wechat_circle_reply_like_strategy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `is_enable_reply` tinyint(4) DEFAULT '0' COMMENT '是否开启评论0关闭1开启',
  `reply_interval_time` int(11) DEFAULT '0' COMMENT '朋友发完朋友圈n分钟后进行评论',
  `reply_numbers` int(11) DEFAULT '0' COMMENT '一个好友每天的评论上限',
  `reply_robot_id` int(11) DEFAULT '0' COMMENT '评论机器人',
  `reply_prompt` varchar(255) DEFAULT NULL COMMENT '评论机器人提示词',
  `reply_tag_ids` json DEFAULT NULL COMMENT '执行评论的好友标签',
  `is_enable_like` tinyint(4) DEFAULT '0' COMMENT '是否开启点赞0关闭1开启',
  `like_interval_time` int(11) DEFAULT '0' COMMENT '朋友发完朋友圈n分钟后进行点赞',
  `like_numbers` int(11) DEFAULT '0' COMMENT '一个好友每天的点赞上限',
  `like_tag_ids` json DEFAULT NULL COMMENT '执行点赞的好友标签',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='微信圈评论点赞策略';

CREATE TABLE IF NOT EXISTS `la_oem` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) DEFAULT NULL COMMENT '站点域名',
  `logo_url` varchar(255) DEFAULT NULL COMMENT '站点icon',
  `user_id` int(11) DEFAULT '0' COMMENT '所属用户id',
  `username` varchar(255) DEFAULT NULL COMMENT '所属用户',
  `auth_time` datetime DEFAULT NULL COMMENT '授权时间',
  `status` tinyint(4) DEFAULT '0' COMMENT '授权状态0取消授权1授权中',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='oem列表';

CREATE TABLE IF NOT EXISTS `la_sv_device_rpa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `device_code` varchar(255) DEFAULT NULL COMMENT '设备号',
  `app_icon` varchar(255) DEFAULT NULL COMMENT 'app图标',
  `app_type` tinyint(4) DEFAULT '0' COMMENT 'app类型3小红书4视频号',
  `app_name` varchar(255) DEFAULT NULL COMMENT 'app名称',
  `exec_duration` int(11) DEFAULT '200' COMMENT '单轮执行时间默认200m',
  `is_enable` tinyint(4) DEFAULT '0' COMMENT 'app是否启用0未启用1启用',
  `status` tinyint(4) DEFAULT '0' COMMENT 'app执行状态0未执行1执行中',
  `weight` int(11) DEFAULT '0' COMMENT '执行优先级,从小到大,默认0',
  `start_time` datetime DEFAULT NULL COMMENT '执行开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '执行结束时间',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COMMENT='设备rpa配置列表';


DELETE FROM `la_model_config` WHERE `scene` = 'openai_chat';
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('sph_add_wechat', 2017, '算力/条', '视频号加微信', 1, '每次添加消耗1算力', 1, NULL, NULL);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'openai_chat', 1003, 'tokens/算力', 'OpenAI模型聊天', 900, '每900字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'ai_reply_like', 2018, 'tokens/算力', '朋友圈评论点赞', 300, '每300字消耗1算力', 1, NULL, NULL);

INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('sph_add_friends', 10001, '算力/次', '视频号加好友话术自动去重  ', 1, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sph_private_chat', 10002, '算力/次', '视频号主动私聊话术去重', 1, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'sph_search_terms', 10003, '算力/10条', '视频号检索关键词', 1, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('video_clip', 5101, '算力/次', '视频剪辑', 50, '', 1, 1740799252, 1740799252);

INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ( 'text_to_vector', 11002, 'tokens/算力', '文本向量化', 10000, '每10千字转换成向量消耗约1算力', 1, 1740799252, 1740799252);



INSERT INTO `la_chat_prompt` (`prompt_name`, `prompt_text`) VALUES ('加好友内容', '你是一个 专业的加好友文案生成助手。
你的目标是：基于用户提供的“大概意思”，生成多样化、独具特色的加好友文案，帮助用户避免重复检测。

#能力要求

1.理解与提炼

仔细分析用户提供的大概意思。
若内容简单（如一句自我介绍），你需要通过调整语言风格（正式、活泼、幽默等）、同义/近义词替换、句式变化等方式进行创新。
若内容较复杂，提炼出核心要点，确保生成的文案紧扣核心，同时保持独特性。

2.文案生成

根据用户输入，生成围绕该意思的 一条加好友文案。
文案需自然流畅，符合真实社交场景。
每次输出的文案在语言风格、用词、句式等方面必须有明显差异，不得机械重复。

#限制

你只负责 生成加好友文案，不回答任何无关问题。
输出内容必须紧密贴合用户提供的大概意思，不得偏离。
每次仅输出一条文案，不要附加解释说明。');
INSERT INTO `la_chat_prompt` (`prompt_name`, `prompt_text`) VALUES ('私信内容', '# 角色
你是一个专业的私信文案生成助手，能够深入理解用户给定的大概意思，从不同角度、运用多样表达方式，生成围绕该内容且每次都独具特色的文案，有效帮助用户规避重复检测。

## 技能
### 技能 1: 生成私信文案
1. 当用户提供大概意思时，对其进行细致分析。若意思较为简单直接，如简单的自我介绍语句，需从语言风格（如正式、活泼、幽默等）、用词（如使用不同的同义词、近义词）、句式结构（如调整语序、变换句式）等方面进行创新，创作一条围绕该内容的文案，用于私信时发送。例如，若用户设置的是“你好，我是从小红书来的”，你可以生成“hello，我来自小红书哦”这类既保留原意又有变化的文案。
2. 如果用户提供的意思较为复杂，需准确提炼核心要点，确保生成的文案紧密围绕核心内容展开，同时体现出独特性。

## 限制:
- 只专注生成私信相关文案，坚决拒绝回答与私信文案生成无关的任何话题。
- 输出内容必须紧密围绕用户提供的大概意思，且每次生成的文案在语言风格、用词、句式等方面都要有明显差异。
-直接返回内容并只输出一条');

UPDATE `la_chat_prompt` SET `prompt_text` = '你是一名AI助手，正在为某位用户提供帮助。

该用户设定的智能体角色设定如下：
【角色设定】

该用户提出了如下问题：
【用户发送的内容】


请根据以下信息来给出专业、自然、个性化的回答。

历史上下文：
【历史对话上下文】

相关参考内容检索结果：
【相关知识库检索结果】

请遵循以下原则：
1. 如果参考内容中包含与问题相关的信息，请结合参考内容优先回答，但禁止提及参考内容这个概念；
2. 如果参考内容中没有提供相关信息，请基于人设和上下文进行逻辑合理的回答；
3. 如果无法确认答案，请礼貌表达无法确定，并引导用户提供更多信息或尝试其他方式；
4. 始终保持与你的人设相符合的语气与身份；
5. 回答要拟人化，避免AI的刻板印象，尽量使用简洁方式进行回复。

兜底建议（仅当确实无任何信息可用时）：
“这个问题我也不是很清楚，要不您换种方式描述看看。”

请开始回答。'
WHERE `prompt_name` = '微信客服';



UPDATE `la_model_config` SET `name` = 'AI小红书客服' WHERE `scene` = 'ai_xhs';

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('自动微信朋友圈点赞评论', 1, 0, '', 'ai_circle_reply_like', '', 1, '*/3 * * * *', '', 1755311536, '0.07', '0.24', 1755310824, 1755310824, NULL);
INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ( '设备RPA执行任务', 1, 0, '', 'device_rpa_cron', '', 1, '* * * * *', NULL, 1755743185, '0', '0', 1755743185, 1755743185, NULL);


INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (434, 195, 'M', 'AI视频获客', '', 0, '', 'sph', '', '', '', 0, 1, 0, 1754989641, 1754989641);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (435, 434, 'C', '扣费记录', '', 0, 'ai_application.sph/cost', 'cost', 'ai_application/sph/cost/lists', '', '', 0, 1, 0, 1754989706, 1754989706);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (436, 434, 'C', '基本配置', '', 0, 'ai_application.sph/setting', 'setting', 'ai_application/sph/setting/index', '', '', 0, 1, 0, 1754990011, 1754990011);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (437, 436, 'A', '保存', '', 0, 'ai_application.sph.setting/setConfig', '', '', '', '', 0, 1, 0, 1754990041, 1754990041);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (438, 0, 'M', '营销管理', 'el-icon-ShoppingBag', 299, '', 'marketing', '', '', '', 0, 1, 0, 1755139989, 1755140369);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (439, 438, 'C', 'OEM授权管理', '', 0, 'marketing.oem/auth', 'oem', 'marketing/oem/auth/lists', '', '', 0, 1, 0, 1755140049, 1755140049);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (440, 439, 'A', '编辑', '', 0, 'marketing.oem.auth/edit', '', '', '', '', 0, 1, 0, 1755140198, 1755140198);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (441, 439, 'A', '删除', '', 0, 'marketing.oem.auth/delete', '', '', '', '', 0, 1, 0, 1755140209, 1755140209);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (442, 439, 'A', '新增', '', 0, 'marketing.oem.auth/add', '', '', '', '', 0, 1, 0, 1755140221, 1755140221);



ALTER TABLE `la_sv_video_task`
ADD COLUMN `music_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '音乐地址',
ADD COLUMN `clip_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '剪辑风格 1:Ai智能推荐,2:科技风格,3:生活风格,4:营销风格,5:知识科普风格, 6:综艺风格',
ADD COLUMN `clip_result_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '剪辑后的视频地址',
ADD COLUMN `automatic_clip` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动剪辑 0不剪,1剪辑',
MODIFY COLUMN `model_version` int(11) NOT NULL DEFAULT 4 COMMENT '模型类型 1：标准 2: 极速',
ADD COLUMN `clip_status` tinyint(4) UNSIGNED NOT NULL DEFAULT 1 COMMENT '剪辑状态 1待剪辑,2剪辑中,3成功,4失败',
ADD COLUMN `width` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '宽',
ADD COLUMN `height` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '高',
ADD COLUMN `clip_token` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '剪辑扣费',
MODIFY COLUMN `msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文字';

ALTER TABLE `la_human_video_task`
ADD COLUMN `music_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '音乐地址',
ADD COLUMN `clip_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '剪辑风格 1:Ai智能推荐,2:科技风格,3:生活风格,4:营销风格,5:知识科普风格, 6:综艺风格',
ADD COLUMN `clip_result_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '剪辑后的视频地址',
ADD COLUMN `automatic_clip` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动剪辑 0不剪,1剪辑',
ADD COLUMN `clip_status` tinyint(4) UNSIGNED NOT NULL DEFAULT 1 COMMENT '剪辑状态 1待剪辑,2剪辑中,3成功,4失败',
MODIFY COLUMN `msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文字',
MODIFY COLUMN `anchor_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '形象名称',
MODIFY COLUMN `voice_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '音色名称';

ALTER TABLE `la_knowledge`
    ADD COLUMN `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '知识库封面';

ALTER TABLE `la_sv_video_setting`
ADD COLUMN `clip` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '剪辑风格,json' ,
ADD COLUMN `music` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '音乐,json' ,
ADD COLUMN `model_version` int(11) NOT NULL DEFAULT 4 COMMENT '模型类型 1：标准 2: 极速',
ADD COLUMN `automatic_clip` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自动剪辑 0不剪,1剪辑' ;



UPDATE `la_config` SET `value` = '{"channel":[{"id":"1","name":"deepseek","model_id":4,"model_sub_id":4},{"id":"2","name":"gpt-4o","model_id":2,"model_sub_id":2}]}' WHERE `name` = 'ai_model' AND `type` = 'chat';
ALTER TABLE `la_knowledge_bind`
    ADD COLUMN `kb_type` tinyint(1) DEFAULT 1 COMMENT '知识库类型 1：RAG 2：向量' AFTER `kid`;


UPDATE `la_model_config` SET `name` = 'RAG-知识库创建',`unit` = '算力/次' WHERE `scene` = 'knowledge_create';

ALTER TABLE `la_sv_add_wechat_record`
    ADD COLUMN `channel` tinyint NULL DEFAULT 3 COMMENT '添加渠道 3小红书 4视频号' AFTER `device_code`,
ADD COLUMN `exec_type` tinyint NULL DEFAULT 1 COMMENT '执行类型1私信聊天2自动爬取3主动私信' AFTER `channel`;

ALTER TABLE `la_sv_add_wechat_record`
    MODIFY COLUMN `account_type` tinyint(4) NULL DEFAULT NULL COMMENT '账号类型3小红书4视频号' AFTER `account`;


INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 1, 1, 'Chill', 0, '/static/audio/technology/1.mp3', 1755914320, 1755914320, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 1, 1, 'City Sunshine', 0, '/static/audio/technology/2.mp3', 1755914320, 1755914320, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 1, 1, '大气氛围背景', 0, '/static/audio/technology/3.mp3', 1755914320, 1755914320, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 1, 1, '太空之旅', 0, '/static/audio/technology/4.mp3', 1755914320, 1755914320, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 1, 1, '背景环境', 0, '/static/audio/technology/5.mp3', 1755914320, 1755914320, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Sappheiros-Memories', 0, '/static/audio/suspense/1.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'sb_resolutions', 0, '/static/audio/suspense/2.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'sb_sanctum', 0, '/static/audio/suspense/3.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'scott-buckley-soar', 0, '/static/audio/suspense/4.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Spark_Of_Inspiration', 0, '/static/audio/suspense/5.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Spring_field', 0, '/static/audio/suspense/6.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Sweet_Dreams', 0, '/static/audio/suspense/7.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'The-Epic-Story', 0, '/static/audio/suspense/8.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'The_Inspiration', 0, '/static/audio/suspense/9.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Tropical_House_Music', 0, '/static/audio/suspense/10.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Tropical_Night', 0, '/static/audio/suspense/11.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Tropical_Soul', 0, '/static/audio/suspense/12.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Ukulele_and_Piano', 0, '/static/audio/suspense/13.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Upbeat_and_Inspiring_Corporate', 0, '/static/audio/suspense/14.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Upbeat_Indie Folk', 0, '/static/audio/suspense/15.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Upbeat_Inspiring Corporate', 0, '/static/audio/suspense/16.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Uplifting Piano', 0, '/static/audio/suspense/17.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Victory', 0, '/static/audio/suspense/18.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Walk_Around', 0, '/static/audio/suspense/19.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Wavecont-Inspire-2-Full-Lenght', 0, '/static/audio/suspense/20.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'White_Petals', 0, '/static/audio/suspense/21.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Your-Road', 0, '/static/audio/suspense/22.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Your_Lucky Day', 0, '/static/audio/suspense/23.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 2, 1, 'Zeal', 0, '/static/audio/suspense/24.mp3', 1755914558, 1755914558, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Life-Blossom', 0, '/static/audio/lyric/1.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Lights', 0, '/static/audio/lyric/2.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Luke-Bergs-Ascensionmp3', 0, '/static/audio/lyric/3.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Luke-Bergs-We-Made-It', 0, '/static/audio/lyric/4.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Me & You', 0, '/static/audio/lyric/5.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Meditative-Space', 0, '/static/audio/lyric/6.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Modern-Baroque', 0, '/static/audio/lyric/7.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Morning-Light', 0, '/static/audio/lyric/8.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Motivate', 0, '/static/audio/lyric/9.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Motivational Ambient', 0, '/static/audio/lyric/10.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Motivational-Cinematic', 0, '/static/audio/lyric/11.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'MusicbyAden-Atch-Sunrise', 0, '/static/audio/lyric/12.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Nature', 0, '/static/audio/lyric/13.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Pandemia', 0, '/static/audio/lyric/14.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Powerful Emotional Trailer', 0, '/static/audio/lyric/15.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Ray Of Hope', 0, '/static/audio/lyric/16.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Salvation-Inspiring-Uplifting-Orchestra', 0, '/static/audio/lyric/17.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Sappheiros-Escape', 0, '/static/audio/lyric/18.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Simple Joy', 0, '/static/audio/lyric/19.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Something Elated', 0, '/static/audio/lyric/20.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Sparks', 0, '/static/audio/lyric/21.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Spring field', 0, '/static/audio/lyric/22.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'Summer Madness', 0, '/static/audio/lyric/23.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'komorebi no nakade', 0, '/static/audio/lyric/24.mp3', 1755915220, 1755915220, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'liqwyd-just-smile', 0, '/static/audio/lyric/25.mp3', 1755915252, 1755915252, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'liqwyd-ocean', 0, '/static/audio/lyric/26.mp3', 1755915252, 1755915252, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'liqwyd-summer-nights', 0, '/static/audio/lyric/27.mp3', 1755915252, 1755915252, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'liqwyd-take-it', 0, '/static/audio/lyric/28.mp3', 1755915252, 1755915252, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'maxkomusic-heroism', 0, '/static/audio/lyric/29.mp3', 1755915252, 1755915252, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 3, 1, 'roa-music-winter-magic', 0, '/static/audio/lyric/30.mp3', 1755915252, 1755915252, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Adventures', 0, '/static/audio/cheerful/1.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Advertime', 0, '/static/audio/cheerful/2.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Better Times', 0, '/static/audio/cheerful/3.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Birthday', 0, '/static/audio/cheerful/4.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Bliss', 0, '/static/audio/cheerful/5.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Borrow the Happiness', 0, '/static/audio/cheerful/6.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Carefree', 0, '/static/audio/cheerful/7.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Cheer Up', 0, '/static/audio/cheerful/8.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Cheerful Whistling', 0, '/static/audio/cheerful/9.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Dusk', 0, '/static/audio/cheerful/10.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Endless Summer', 0, '/static/audio/cheerful/11.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Energetic Indie Rock', 0, '/static/audio/cheerful/12.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Feel Good', 0, '/static/audio/cheerful/13.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Forever', 0, '/static/audio/cheerful/14.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Funny Bubbles', 0, '/static/audio/cheerful/15.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Give Me A Smile', 0, '/static/audio/cheerful/16.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Go to the Picnic', 0, '/static/audio/cheerful/17.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Happy Clappy Ukulele', 0, '/static/audio/cheerful/18.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Happy Clappy', 0, '/static/audio/cheerful/19.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Happy Commercial', 0, '/static/audio/cheerful/20.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Happy Upbeat Ukulele', 0, '/static/audio/cheerful/21.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Happy and Joyful Children', 0, '/static/audio/cheerful/22.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Happy', 0, '/static/audio/cheerful/23.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Holiday', 0, '/static/audio/cheerful/24.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Ice Cream with you', 0, '/static/audio/cheerful/25.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Innocence', 0, '/static/audio/cheerful/26.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Inspiring', 0, '/static/audio/cheerful/27.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'It’s Your Birthday', 0, '/static/audio/cheerful/28.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'I’ll Meet You There', 0, '/static/audio/cheerful/29.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Sunny', 0, '/static/audio/cheerful/30.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Surf', 0, '/static/audio/cheerful/31.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Winter Magic', 0, '/static/audio/cheerful/32.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'Your Lucky Day', 0, '/static/audio/cheerful/33.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, 'bo-tto hidamari', 0, '/static/audio/cheerful/34.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, '我的生活（主）', 0, '/static/audio/cheerful/35.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, '放轻松', 0, '/static/audio/cheerful/36.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, '新鲜', 0, '/static/audio/cheerful/37.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, '电影童话故事（主要）', 0, '/static/audio/cheerful/38.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, '轻松欢快(尤克里里)', 0, '/static/audio/cheerful/39.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 4, 1, '轻松氛围', 0, '/static/audio/cheerful/40.mp3', 1755921639, 1755921639, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Action Rhythms', 0, '/static/audio/jump/1.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Action-Rock', 0, '/static/audio/jump/2.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Best Time', 0, '/static/audio/jump/3.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Bounce', 0, '/static/audio/jump/4.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Energetic-Indie-Rock', 0, '/static/audio/jump/5.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Epic Cinematic Trailer', 0, '/static/audio/jump/6.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Escape Route', 0, '/static/audio/jump/7.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Feel-Good', 0, '/static/audio/jump/8.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Future Bass (Hot Night)', 0, '/static/audio/jump/9.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Get Ready', 0, '/static/audio/jump/10.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Luke-Bergs-Tropical-Soulmp3', 0, '/static/audio/jump/11.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Save As', 0, '/static/audio/jump/12.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Stomping Rock (Four Shots)', 0, '/static/audio/jump/13.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'Summer Pranks', 0, '/static/audio/jump/14.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'super spiffy 时尚产品', 0, '/static/audio/jump/15.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, 'zen-garden', 0, '/static/audio/jump/16.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '商业快节奏', 0, '/static/audio/jump/17.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '商业项目作画外音背景', 0, '/static/audio/jump/18.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '展示片', 0, '/static/audio/jump/19.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '性感时尚节拍（模拟）', 0, '/static/audio/jump/20.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '拖车运动时尚', 0, '/static/audio/jump/21.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '柔和商业感觉(可无痕切割多段)', 0, '/static/audio/jump/22.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '节奏卡点(摇滚感)', 0, '/static/audio/jump/23.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '轻松带有积极感(商业介绍)', 0, '/static/audio/jump/24.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 6, 1, '随和舒缓(适合画外音背景)', 0, '/static/audio/jump/25.mp3', 1755929464, 1755929464, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Across-the-Park', 0, '/static/audio/classical/1.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'AgeOfWonder', 0, '/static/audio/classical/2.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'An-Epic-Story', 0, '/static/audio/classical/3.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Ascension', 0, '/static/audio/classical/4.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Beautiful-Piano', 0, '/static/audio/classical/5.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'California', 0, '/static/audio/classical/6.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Cheer Up', 0, '/static/audio/classical/7.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Cutting Edge', 0, '/static/audio/classical/8.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Earning Happiness', 0, '/static/audio/classical/9.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Emotional-Inspiring-Hopeful-Music-by-Alex-Productions-No-Copyright-Music-_-FREE-MUSIC-_-HEARTS-_', 0, '/static/audio/classical/10.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Employer', 0, '/static/audio/classical/11.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Energetic-Drink', 0, '/static/audio/classical/12.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Epic-Dubstep-Trailer', 0, '/static/audio/classical/13.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Epic-Trailer', 0, '/static/audio/classical/14.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Fading', 0, '/static/audio/classical/16.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Future Technology', 0, '/static/audio/classical/17.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Guardians-Of-The-Fallen-Epic-Soundtrack', 0, '/static/audio/classical/18.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Hope-Emotional-Soundtrack', 0, '/static/audio/classical/19.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'In Dreams', 0, '/static/audio/classical/20.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Inspirational Corporate', 0, '/static/audio/classical/21.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Inspiring Dreams', 0, '/static/audio/classical/22.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Inspiring Optimistic Upbeat Energetic Guitar Rhythm', 0, '/static/audio/classical/23.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'Into-The-Blue-Sky', 0, '/static/audio/classical/24.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'feel-free', 0, '/static/audio/classical/25.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'fm-freemusic-maybe-i-need-you', 0, '/static/audio/classical/26.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'fresh-lift', 0, '/static/audio/classical/27.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, 'keys-of-moon-the-success', 0, '/static/audio/classical/28.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, '制胜标高', 0, '/static/audio/classical/29.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_material_music` ( `source_id`, `source`, `style`, `status`, `name`, `sort`, `url`, `create_time`, `update_time`, `delete_time`) VALUES (1, 0, 5, 1, '自由启发的电影背景音乐视频', 0, '/static/audio/classical/30.mp3', 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (1, 1, 'qwen', '', '通义千问', '', '', 0,1,1,1,1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (2, 1, 'openai', '', 'OpenAI(gpt-4o)', '', '', 0,1,1,1,1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (3, 2, 'openai', '', 'OpenAI(gpt-4o)', '', '', 0,1,1,1,1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (4, 1, 'openai', '', 'DeepSeek', '', '', 0,1,1,1,1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (1, 1, 1, 'qwen', 'qwen-plus', 'qwen-plus',0.0000, 0,1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (2, 2, 1, 'openai', 'gpt-4o', 'gpt-4o',0.0000, 0,1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (3, 3, 2, 'openai', 'Text-embedding-3-lager', 'Text-embedding-3-lager',0.0000, 0,1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (4, 4, 1, 'openai', 'deepseek', 'deepseek',0.0000, 0,1, 1755929617);


