SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `la_admin`;
CREATE TABLE `la_admin` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `root` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否超级管理员 0-否 1-是',
    `name` varchar(32) NOT NULL DEFAULT '' COMMENT '名称',
    `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像',
    `account` varchar(32) NOT NULL DEFAULT '' COMMENT '账号',
    `password` varchar(32) NOT NULL COMMENT '密码',
    `login_time` int(10) DEFAULT NULL COMMENT '最后登录时间',
    `login_ip` varchar(15) DEFAULT '' COMMENT '最后登录ip',
    `multipoint_login` tinyint(1) unsigned DEFAULT '1' COMMENT '是否支持多处登录：1-是；0-否；',
    `disable` tinyint(1) unsigned DEFAULT '0' COMMENT '是否禁用：0-否；1-是；',
    `create_time` int(10) NOT NULL COMMENT '创建时间',
    `update_time` int(10) DEFAULT NULL COMMENT '修改时间',
    `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='管理员表';

DROP TABLE IF EXISTS `la_admin_dept`;
CREATE TABLE `la_admin_dept`  (
  `admin_id` int(10) NOT NULL DEFAULT 0 COMMENT '管理员id',
  `dept_id` int(10) NOT NULL DEFAULT 0 COMMENT '部门id',
  PRIMARY KEY (`admin_id`, `dept_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '部门关联表';

DROP TABLE IF EXISTS `la_admin_jobs`;
CREATE TABLE `la_admin_jobs`  (
  `admin_id` int(10) NOT NULL COMMENT '管理员id',
  `jobs_id` int(10) NOT NULL COMMENT '岗位id',
  PRIMARY KEY (`admin_id`, `jobs_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '岗位关联表';

DROP TABLE IF EXISTS `la_admin_role`;
CREATE TABLE `la_admin_role`  (
  `admin_id` int(10) NOT NULL COMMENT '管理员id',
  `role_id` int(10) NOT NULL COMMENT '角色id',
  PRIMARY KEY (`admin_id`, `role_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色关联表';

DROP TABLE IF EXISTS `la_admin_session`;
CREATE TABLE `la_admin_session`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) UNSIGNED NOT NULL COMMENT '用户id',
  `terminal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '客户端类型：1-pc管理后台 2-mobile手机管理后台',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '令牌',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `expire_time` int(10) NOT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_id_client`(`admin_id`, `terminal`) USING BTREE COMMENT '一个用户在一个终端只有一个token',
  UNIQUE INDEX `token`(`token`) USING BTREE COMMENT 'token是唯一的'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员会话表';

DROP TABLE IF EXISTS `la_article`;
CREATE TABLE `la_article`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `cid` int(11) NOT NULL COMMENT '文章分类',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文章标题',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '简介',
  `abstract` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '文章摘要',
  `image` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文章图片',
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '作者',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '文章内容',
  `click_virtual` int(10) NULL DEFAULT 0 COMMENT '虚拟浏览量',
  `click_actual` int(11) NULL DEFAULT 0 COMMENT '实际浏览量',
  `is_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示:1-是.0-否',
  `sort` int(5) NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章表';

BEGIN;
INSERT INTO `la_article` VALUES (1, 1, '让生活更精致！五款居家好物推荐，实用性超高', '##好物推荐🔥', '随着当代生活节奏的忙碌，很多人在闲暇之余都想好好的享受生活。随着科技的发展，也出现了越来越多可以帮助我们提升幸福感，让生活变得更精致的产品，下面周周就给大家盘点五款居家必备的好物，都是实用性很高的产品，周周可以保证大家买了肯定会喜欢。', '/resource/image/adminapi/default/article01.png', '红花', '<p>拥有一台投影仪，闲暇时可以在家里直接看影院级别的大片，光是想想都觉得超级爽。市面上很多投影仪大几千，其实周周觉得没必要，选泰捷这款一千多的足够了，性价比非常高。</p><p>泰捷的专业度很高，在电视TV领域研发已经十年，有诸多专利和技术创新，荣获国内外多项技术奖项，拿下了腾讯创新工场投资，打造的泰捷视频TV端和泰捷电视盒子都获得了极高评价。</p><p>这款投影仪的分辨率在3000元内无敌，做到了真1080P高分辨率，也就是跟市场售价三千DLP投影仪一样的分辨率，真正做到了分毫毕现，像桌布的花纹、天空的云彩等，这些细节都清晰可见。</p><p>亮度方面，泰捷达到了850ANSI流明，同价位一般是200ANSI。这是因为泰捷为了提升亮度和LCD技术透射率低的问题，首创高功率LED灯源，让其亮度做到同价位最好。专业媒体也进行了多次对比，效果与3000元价位投影仪相当。</p><p>操作系统周周也很喜欢，完全不卡。泰捷作为资深音视频品牌，在系统优化方面有十年的研发经验，打造出的“零极”系统是业内公认效率最高、速度最快的系统，用户也评价它流畅度能一台顶三台，而且为了解决行业广告多这一痛点，系统内不植入任何广告。</p>', 1, 2, 1, 0, 1663317759, 1663317759, NULL), (2, 1, '埋葬UI设计师的坟墓不是内卷，而是免费模式', '', '本文从另外一个角度，聊聊作者对UI设计师职业发展前景的担忧，欢迎从事UI设计的同学来参与讨论，会有赠书哦', '/resource/image/adminapi/default/article02.jpeg', '小明', '<p><br></p><p style=\"text-align: justify;\">一个职业，卷，根本就没什么大不了的，尤其是成熟且收入高的职业，不卷才不符合事物发展的规律。何况 UI 设计师的人力市场到今天也和 5 年前一样，还是停留在大型菜鸡互啄的场面。远不能和医疗、证券、教师或者演艺练习生相提并论。</p><p style=\"text-align: justify;\">真正会让我对UI设计师发展前景觉得悲观的事情就只有一件 —— 国内的互联网产品免费机制。这也是一个我一直以来想讨论的话题，就在这次写一写。</p><p style=\"text-align: justify;\">国内互联网市场的发展，是一部浩瀚的 “免费经济” 发展史。虽然今天免费已经是深入国内民众骨髓的认知，但最早的中文互联网也是需要付费的，网游也都是要花钱的。</p><p style=\"text-align: justify;\">只是自有国情在此，付费确实阻碍了互联网行业的扩张和普及，一批创业家就开始通过免费的模式为用户提供服务，从而扩大了自己的产品覆盖面和普及程度。</p><p style=\"text-align: justify;\">印象最深的就是免费急先锋周鸿祎，和现在鲜少出现在公众视野不同，一零年前他是当之无愧的互联网教主，因为他开发出了符合中国国情的互联网产品 “打法”，让 360 的发展如日中天。</p><p style=\"text-align: justify;\">就是他在自传中提到：</p><p style=\"text-align: justify;\">只要是在互联网上每个人都需要的服务，我们就认为它是基础服务，基础服务一定是免费的，这样的话不会形成价值歧视。就是说，只要这种服务是每个人都一定要用的，我一定免费提供，而且是无条件免费。增值服务不是所有人都需要的，这个比例可能会相当低，它只是百分之几甚至更少比例的人需要，所以这种服务一定要收费……</p><p style=\"text-align: justify;\">这就是互联网的游戏规则，它决定了要想建立一个有效的商业模式，就一定要有海量的用户基数……</p>', 2, 4, 1, 0, 1663322854, 1663322854, NULL), (3, 2, '金山电池公布“沪广深市民绿色生活方式”调查结果', '', '60%以上受访者认为高质量的10分钟足以完成“自我充电”', '/resource/image/adminapi/default/article03.png', '中网资讯科技', '<p style=\"text-align: left;\"><strong>深圳，2021年10月22日）</strong>生活在一线城市的沪广深市民一向以效率见称，工作繁忙和快节奏的生活容易缺乏充足的休息。近日，一项针对沪广深市民绿色生活方式而展开的网络问卷调查引起了大家的注意。问卷的问题设定集中于市民对休息时间的看法，以及从对循环充电电池的使用方面了解其对绿色生活方式的态度。该调查采用随机抽样的模式，并对最终收集的1,500份有效问卷进行专业分析后发现，超过60%的受访者表示，在每天的工作时段能拥有10分钟高质量的休息时间，就可以高效“自我充电”。该调查结果反映出，在快节奏时代下，人们需要高质量的休息时间，也要学会利用高效率的休息方式和工具来应对快节奏的生活，以时刻保持“满电”状态。</p><p style=\"text-align: left;\">　　<strong>60%以上受访者认为高质量的10分钟足以完成“自我充电”</strong></p><p style=\"text-align: left;\">　　这次调查超过1,500人，主要聚焦18至85岁的沪广深市民，了解他们对于休息时间的观念及使用充电电池的习惯，结果发现：</p><p style=\"text-align: left;\">　　· 90%以上有工作受访者每天工作时间在7小时以上，平均工作时间为8小时，其中43%以上的受访者工作时间超过9小时</p><p style=\"text-align: left;\">　　· 70%受访者认为在工作期间拥有10分钟“自我充电”时间不是一件困难的事情</p><p style=\"text-align: left;\">　　· 60%受访者认为在工作期间有10分钟休息时间足以为自己快速充电</p><p style=\"text-align: left;\">　　临床心理学家黄咏诗女士在发布会上分享为自己快速充电的实用技巧，她表示：“事实上，只要选择正确的休息方法，10分钟也足以为自己充电。以喝咖啡为例，我们可以使用心灵休息法 ── 静观呼吸，慢慢感受咖啡的温度和气味，如果能配合着聆听流水或海洋的声音，能够有效放松大脑及心灵。”</p><p style=\"text-align: left;\">　　这次调查结果反映出沪广深市民的希望在繁忙的工作中适时停下来，抽出10分钟喝杯咖啡、聆听音乐或小睡片刻，为自己充电。金山电池全新推出的“绿再十分充”超快速充电器仅需10分钟就能充好电，喝一杯咖啡的时间既能完成“自我充电”，也满足设备使用的用电需求，为提升工作效率和放松身心注入新能量。</p><p style=\"text-align: left;\">　　<strong>金山电池推出10分钟超快电池充电器*绿再十分充，以创新科技为市场带来革新体验</strong></p><p style=\"text-align: left;\">　　该问卷同时从沪广深市民对循环充电电池的使用方面进行了调查，以了解其对绿色生活方式的态度：</p><p style=\"text-align: left;\">　　· 87%受访者目前没有使用充电电池，其中61%表示会考虑使用充电电池</p><p style=\"text-align: left;\">　　· 58%受访者过往曾使用过充电电池，却只有20%左右市民仍在使用</p><p style=\"text-align: left;\">　　· 60%左右受访者认为充电电池尚未被广泛使用，主要障碍来自于充电时间过长、缺乏相关教育</p><p style=\"text-align: left;\">　　· 90%以上受访者认为充电电池充满电需要1小时或更长的时间</p><p style=\"text-align: left;\">　　金山电池一直致力于为大众提供安全可靠的充电电池，并与消费者的需求和生活方式一起演变及进步。今天，金山电池宣布推出10分钟超快电池充电器*绿再十分充，只需10分钟*即可将4粒绿再十分充充电电池充好电，充电速度比其他品牌提升3倍**。充电器的LED灯可以显示每粒电池的充电状态和模式，并提示用户是否错误插入已损坏电池或一次性电池。尽管其体型小巧，却具备多项创新科技 ，如拥有独特的充电算法以优化充电电流，并能根据各个电池类型、状况和温度用最短的时间为充电电池充好电;绿再十分充内置横流扇，有效防止电池温度过热和提供低噪音的充电环境等。<br></p>', 11, 2, 1, 0, 1663322665, 1663322665, NULL);
COMMIT;

DROP TABLE IF EXISTS `la_article_cate`;
CREATE TABLE `la_article_cate`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章分类id',
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '分类名称',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `is_show` tinyint(1) NULL DEFAULT 1 COMMENT '是否显示:1-是;0-否',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章分类表';

BEGIN;
INSERT INTO `la_article_cate` VALUES (1, '文章资讯', 0, 1, 1663317280, 1663317280, 1663317282), (2, '社会热点', 0, 1, 1663317280, 1663321464, 1663321494);
COMMIT;

DROP TABLE IF EXISTS `la_article_collect`;
CREATE TABLE `la_article_collect`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `article_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '文章ID',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏状态 0-未收藏 1-已收藏',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文章收藏表';


DROP TABLE IF EXISTS `la_assistants`;
CREATE TABLE `la_assistants` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `assistants_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'openai返回的ID',
    `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:通用聊天 1:机器人 2:思维导图 3:公共模块 4:HD图片',
    `scene_id` tinyint(1) NOT NULL DEFAULT '0' COMMENT '场景id',
    `model` varchar(255) NOT NULL DEFAULT '' COMMENT 'MODEL',
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
    `description` text NOT NULL COMMENT '描述',
    `instructions` text NOT NULL COMMENT '指令',
    `tools` varchar(255) NOT NULL DEFAULT '' COMMENT '工具集',
    `tool_resources` varchar(255) DEFAULT '' COMMENT '工具源',
    `vector_file_id` varchar(500) NOT NULL DEFAULT '' COMMENT '助手关联的向量  数据库侧',
    `gtp_vector_file_id` varchar(500) NOT NULL DEFAULT '' COMMENT '助手关联的向量  gtp侧',
    `metadata` varchar(255) NOT NULL DEFAULT '' COMMENT '元数据',
    `temperature` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '温度',
    `top_p` float(10,2) NOT NULL DEFAULT '0.00',
    `preliminary_ask` text COMMENT '预备问题',
    `extra` text COMMENT '扩展信息',
    `template_info` text COMMENT '问题模板',
    `form_info` text COMMENT '关键词信息',
    `logo` varchar(255) NOT NULL COMMENT 'logo',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：正常 0：禁用',
    `use_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后使用时间',
    `is_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:显示在左侧',
    `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序  大的在前',
    `is_default` tinyint default 0 not null comment '是否默认 1：默认 0：新增',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `assistants_id` (`assistants_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='助手表';


DROP TABLE IF EXISTS `la_assistants_channel`;
CREATE TABLE `la_assistants_channel` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
    `type` tinyint(4) NOT NULL COMMENT '类型: [1=个微]',
    `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户的ID',
    `assistants_id` varchar(200) NOT NULL DEFAULT '0' COMMENT '助理ID',
    `name` varchar(200) NOT NULL DEFAULT '' COMMENT '分享名称',
    `apikey` varchar(200) NOT NULL DEFAULT '' COMMENT '访问key',
    `limit_total_chat` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户总的限制对话',
    `limit_today_chat` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户每天限制对话',
    `limit_exceed` varchar(500) NOT NULL DEFAULT '' COMMENT '超出限制默认回复',
    `use_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '调用次数',
    `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
    `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user_idx` (`user_id`) USING BTREE,
    KEY `robot_idx` (`assistants_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='助理发布渠道表';


DROP TABLE IF EXISTS `la_assistants_share`;
CREATE TABLE `la_assistants_share` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `assistants_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'openai返回的ID',
    `group_id` int(11) NOT NULL COMMENT '部门id',
    `to_id` tinyint(1) NOT NULL COMMENT '机器人类型，作用',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 禁止使用 1：正常',
    `expiration_date` int(11) NOT NULL DEFAULT '0' COMMENT '有效天数 0 永久',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='助手表';


DROP TABLE IF EXISTS `la_audio`;
CREATE TABLE `la_audio` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL COMMENT '用户id',
    `order_id` varchar(255) NOT NULL DEFAULT '' COMMENT '上传讯飞返回的id',
    `file_id` int(11) NOT NULL COMMENT '文件id',
    `model_file_id` varchar(200) default '' not null COMMENT '模型训练返回的文件id',
    `file_name` varchar(255) NOT NULL COMMENT '名字',
    `file_path` varchar(255) NOT NULL COMMENT '文件路径',
    `json_info` varchar(255) DEFAULT '' COMMENT '转写参数',
    `result` text COMMENT '处理之后的结果',
    `key_word_info` text COMMENT '分析结果',
    `result_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：待转化 2：已提交任务 1：成功 -1：失败',
    `status_msg` varchar(255) NOT NULL DEFAULT '' COMMENT '状态信息',
    `msg` varchar(255) NOT NULL DEFAULT '' COMMENT '错误信息',
    `task_time` int(11) DEFAULT '0' COMMENT '任务时间',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `order_id` (`order_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='音频文件处理';


DROP TABLE IF EXISTS `la_audio_info`;
CREATE TABLE `la_audio_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key_word_id` int(11) NOT NULL DEFAULT '0' COMMENT '关键词id',
  `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
  `text` text COMMENT '文本',
  `markdown` text COMMENT 'makedown',
  `audio_id` int(11) NOT NULL COMMENT '音频id',
  `create_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='音频分析详情';


DROP TABLE IF EXISTS `la_audio_key_words`;
CREATE TABLE `la_audio_key_words` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '标题',
  `keyword` text COMMENT '问题内容',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0异常 1:正常',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='会议分析关键词列表';


DROP TABLE IF EXISTS `la_chat_log`;
CREATE TABLE `la_chat_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户的ID',
  `assistants_id` varchar(255) NOT NULL COMMENT '助理id',
  `thread_id` varchar(255) NOT NULL DEFAULT '0' COMMENT '线程（会话）ID',
  `run_id` varchar(255) NOT NULL DEFAULT '' COMMENT '运行id',
  `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
  `message` text COMMENT '用户的提问内容',
  `message_ext` varchar(255) NOT NULL DEFAULT '' COMMENT '表单字段补充',
  `reply` text COMMENT 'gpt的回复内容',
  `file_ids` varchar(500) DEFAULT '' COMMENT '消息附带的文件id集合',
  `share_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享的ID',
  `share_apikey` varchar(80) NOT NULL DEFAULT '' COMMENT '分享的密钥',
  `share_identity` varchar(60) NOT NULL DEFAULT '' COMMENT '分享的身份',
  `censor_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态: [0=未审核, 1=合规, 2=不合规, 3=疑似, 4=审核失败]',
  `censor_result` text COMMENT '审核结果',
  `censor_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核次数',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示: [0=否, 1=是]',
  `task_time` int(60) NOT NULL DEFAULT '0' COMMENT '对话耗时',
  `ask_ext` varchar(255) DEFAULT '' COMMENT '问题补充,json',
  `identity` varchar(255) NOT NULL DEFAULT '' COMMENT '个微标识',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='聊天记录表';


DROP TABLE IF EXISTS `la_config`;
CREATE TABLE `la_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '类型',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '值',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '配置表';

DROP TABLE IF EXISTS `la_decorate_page`;
CREATE TABLE `la_decorate_page`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT '页面类型 1=商城首页, 2=个人中心, 3=客服设置 4-PC首页',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '页面名称',
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '页面数据',
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '页面设置',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '装修页面配置表';


BEGIN;
INSERT INTO `la_decorate_page` VALUES (1, 1, '商城首页', '[{\"title\":\"搜索\",\"name\":\"search\",\"disabled\":1,\"content\":{},\"styles\":{}},{\"title\":\"首页轮播图\",\"name\":\"banner\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/banner001.png\",\"name\":\"\",\"link\":{\"id\":6,\"name\":\"来自瓷器的爱\",\"path\":\"/pages/news_detail/news_detail\",\"query\":{\"id\":6},\"type\":\"article\"},\"is_show\":\"1\",\"bg\":\"/resource/image/adminapi/default/banner001_bg.png\"},{\"image\":\"/resource/image/adminapi/default/banner002.png\",\"name\":\"\",\"link\":{\"id\":3,\"name\":\"金山电池公布“沪广深市民绿色生活方式”调查结果\",\"path\":\"/pages/news_detail/news_detail\",\"query\":{\"id\":3},\"type\":\"article\"},\"is_show\":\"1\",\"bg\":\"/resource/image/adminapi/default/banner002_bg.png\"},{\"is_show\":\"1\",\"image\":\"/resource/image/adminapi/default/banner003.png\",\"name\":\"\",\"link\":{\"id\":1,\"name\":\"让生活更精致！五款居家好物推荐，实用性超高\",\"path\":\"/pages/news_detail/news_detail\",\"query\":{\"id\":1},\"type\":\"article\"},\"bg\":\"/resource/image/adminapi/default/banner003_bg.png\"}],\"style\":1,\"bg_style\":1},\"styles\":{}},{\"title\":\"导航菜单\",\"name\":\"nav\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/nav01.png\",\"name\":\"资讯中心\",\"link\":{\"path\":\"/pages/news/news\",\"name\":\"文章资讯\",\"type\":\"shop\",\"canTab\":true},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav03.png\",\"name\":\"个人设置\",\"link\":{\"path\":\"/pages/user_set/user_set\",\"name\":\"个人设置\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav02.png\",\"name\":\"我的收藏\",\"link\":{\"path\":\"/pages/collection/collection\",\"name\":\"我的收藏\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav05.png\",\"name\":\"关于我们\",\"link\":{\"path\":\"/pages/as_us/as_us\",\"name\":\"关于我们\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/nav04.png\",\"name\":\"联系客服\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"}],\"style\":2,\"per_line\":5,\"show_line\":2},\"styles\":{}},{\"title\":\"首页中部轮播图\",\"name\":\"middle-banner\",\"content\":{\"enabled\":1,\"data\":[{\"is_show\":\"1\",\"image\":\"/resource/image/adminapi/default/index_ad01.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/agreement/agreement\",\"name\":\"隐私政策\",\"query\":{\"type\":\"privacy\"},\"type\":\"shop\"}}]},\"styles\":{}},{\"id\":\"l84almsk2uhyf\",\"title\":\"资讯\",\"name\":\"news\",\"disabled\":1,\"content\":{},\"styles\":{}}]', '[{\"title\":\"页面设置\",\"name\":\"page-meta\",\"content\":{\"title\":\"首页\",\"bg_type\":\"2\",\"bg_color\":\"#2F80ED\",\"bg_image\":\"/resource/image/adminapi/default/page_meta_bg01.png\",\"text_color\":\"2\",\"title_type\":\"2\",\"title_img\":\"/resource/image/adminapi/default/page_mate_title.png\"},\"styles\":{}}]', 1661757188, 1710989700), (2, 2, '个人中心', '[{\"title\":\"用户信息\",\"name\":\"user-info\",\"disabled\":1,\"content\":{},\"styles\":{}},{\"title\":\"我的服务\",\"name\":\"my-service\",\"content\":{\"style\":1,\"title\":\"我的服务\",\"data\":[{\"image\":\"/resource/image/adminapi/default/user_collect.png\",\"name\":\"我的收藏\",\"link\":{\"path\":\"/pages/collection/collection\",\"name\":\"我的收藏\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/user_setting.png\",\"name\":\"个人设置\",\"link\":{\"path\":\"/pages/user_set/user_set\",\"name\":\"个人设置\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/user_kefu.png\",\"name\":\"联系客服\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/wallet.png\",\"name\":\"我的钱包\",\"link\":{\"path\":\"/packages/pages/user_wallet/user_wallet\",\"name\":\"我的钱包\",\"type\":\"shop\"},\"is_show\":\"1\"}],\"enabled\":1},\"styles\":{}},{\"title\":\"个人中心广告图\",\"name\":\"user-banner\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/user_ad01.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"},{\"image\":\"/resource/image/adminapi/default/user_ad02.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/customer_service/customer_service\",\"name\":\"联系客服\",\"type\":\"shop\"},\"is_show\":\"1\"}]},\"styles\":{}}]', '[{\"title\":\"页面设置\",\"name\":\"page-meta\",\"content\":{\"title\":\"个人中心\",\"bg_type\":\"1\",\"bg_color\":\"#2F80ED\",\"bg_image\":\"\",\"text_color\":\"1\",\"title_type\":\"2\",\"title_img\":\"/resource/image/adminapi/default/page_mate_title.png\"},\"styles\":{}}]', 1661757188, 1710933097), (3, 3, '客服设置', '[{\"title\":\"客服设置\",\"name\":\"customer-service\",\"content\":{\"title\":\"添加客服二维码\",\"time\":\"早上 9:30 - 19:00\",\"mobile\":\"18578768757\",\"qrcode\":\"/resource/image/adminapi/default/kefu01.png\",\"remark\":\"长按添加客服或拨打客服热线\"},\"styles\":{}}]', '', 1661757188, 1710929953), (4, 4, 'PC设置', '[{\"id\":\"lajcn8d0hzhed\",\"title\":\"首页轮播图\",\"name\":\"pc-banner\",\"content\":{\"enabled\":1,\"data\":[{\"image\":\"/resource/image/adminapi/default/banner003.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/news/news\",\"name\":\"文章资讯\",\"type\":\"shop\"}},{\"image\":\"/resource/image/adminapi/default/banner002.png\",\"name\":\"\",\"link\":{\"path\":\"/pages/collection/collection\",\"name\":\"我的收藏\",\"type\":\"shop\"}},{\"image\":\"/resource/image/adminapi/default/banner001.png\",\"name\":\"\",\"link\":{}}]},\"styles\":{\"position\":\"absolute\",\"left\":\"40\",\"top\":\"75px\",\"width\":\"750px\",\"height\":\"340px\"}}]', '', 1661757188, 1710990175), (5, 5, '系统风格', '{\"themeColorId\":3,\"topTextColor\":\"white\",\"navigationBarColor\":\"#A74BFD\",\"themeColor1\":\"#A74BFD\",\"themeColor2\":\"#CB60FF\",\"buttonColor\":\"white\"}', '', 1710410915, 1710990415);
COMMIT;

DROP TABLE IF EXISTS `la_decorate_tabbar`;
CREATE TABLE `la_decorate_tabbar`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '导航名称',
  `selected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '未选图标',
  `unselected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '已选图标',
  `link` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '链接地址',
  `is_show` tinyint(255) UNSIGNED NOT NULL DEFAULT 1 COMMENT '显示状态',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '装修底部导航表';


BEGIN;
INSERT INTO `la_decorate_tabbar` VALUES (1, 'AI手机', 'static/images/mp/tabbs/phone_s.png', 'static/images/mp/tabbs/phone.png', '{\"path\":\"/pages/index/index\",\"name\":\"AI手机\",\"type\":\"page\"}', 1, 1662688157, 1662688157), (2, 'AI助手', 'static/images/mp/tabbs/chat_s.png', 'static/images/mp/tabbs/chat.png', '{\"path\":\"/packages/pages/chat/chat\",\"name\":\"AI助手\",\"type\":\"page\"}', 1, 1662688157, 1662688157), (3, 'AI创作', 'static/images/mp/tabbs/creative_s.png', 'static/images/mp/tabbs/creative.png', '{\"path\":\"/ai_modules/digital_human/pages/index/index\",\"name\":\"AI创作\",\"type\":\"page\"}', 1, 1662688157, 1662688157), (4, '我的', 'static/images/mp/tabbs/me_s.png', 'static/images/mp/tabbs/me.png', '{\"path\":\"/packages/pages/user/user\",\"name\":\"我的\",\"type\":\"page\"}', 1, 1662688157, 1662688157);
COMMIT;

DROP TABLE IF EXISTS `la_dept`;
CREATE TABLE `la_dept`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '部门名称',
  `pid` bigint(20) NOT NULL DEFAULT 0 COMMENT '上级部门id',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `leader` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '负责人',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联系电话',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '部门状态（0停用 1正常）',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '部门表';


BEGIN;
INSERT INTO `la_dept` VALUES (1, '公司', 0, 0, 'boss', '12345698745', 1, 1650592684, 1653640368, NULL);
COMMIT;

DROP TABLE IF EXISTS `la_dev_crontab`;
CREATE TABLE `la_dev_crontab`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '定时任务名称',
  `type` tinyint(1) NOT NULL COMMENT '类型 1-定时任务',
  `system` tinyint(4) NULL DEFAULT 0 COMMENT '是否系统任务 0-否 1-是',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `command` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '命令内容',
  `params` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '参数',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 1-运行 2-停止 3-错误',
  `expression` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '运行规则',
  `error` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '运行失败原因',
  `last_time` int(11) NULL DEFAULT NULL COMMENT '最后执行时间',
  `time` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '实时执行时长',
  `max_time` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '最大执行时长',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '计划任务表';


DROP TABLE IF EXISTS `la_dev_pay_config`;
CREATE TABLE `la_dev_pay_config`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模版名称',
  `pay_way` tinyint(1) NOT NULL COMMENT '支付方式:1-余额支付;2-微信支付;3-支付宝支付;',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '对应支付配置(json字符串)',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '图标',
  `sort` int(5) NULL DEFAULT NULL COMMENT '排序',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;


DROP TABLE IF EXISTS `la_dev_pay_way`;
CREATE TABLE `la_dev_pay_way`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pay_config_id` int(11) NOT NULL COMMENT '支付配置ID',
  `scene` tinyint(1) NOT NULL COMMENT '场景:1-微信小程序;2-微信公众号;3-H5;4-PC;5-APP;',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认支付:0-否;1-是;',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:0-关闭;1-开启;',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;


DROP TABLE IF EXISTS `la_dict_data`;
CREATE TABLE `la_dict_data`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据名称',
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '数据值',
  `type_id` int(11) NOT NULL COMMENT '字典类型id',
  `type_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '字典类型',
  `sort` int(10) NULL DEFAULT 0 COMMENT '排序值',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0-停用 1-正常',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '字典数据表';

BEGIN;
INSERT INTO `la_dict_data` VALUES (1, '隐藏', '0', 1, 'show_status', 0, 1, '', 1656381543, 1656381543, NULL), (2, '显示', '1', 1, 'show_status', 0, 1, '', 1656381550, 1656381550, NULL), (3, '进行中', '0', 2, 'business_status', 0, 1, '', 1656381410, 1656381410, NULL), (4, '成功', '1', 2, 'business_status', 0, 1, '', 1656381437, 1656381437, NULL), (5, '失败', '2', 2, 'business_status', 0, 1, '', 1656381449, 1656381449, NULL), (6, '待处理', '0', 3, 'event_status', 0, 1, '', 1656381212, 1656381212, NULL), (7, '已处理', '1', 3, 'event_status', 0, 1, '', 1656381315, 1656381315, NULL), (8, '拒绝处理', '2', 3, 'event_status', 0, 1, '', 1656381331, 1656381331, NULL), (9, '禁用', '1', 4, 'system_disable', 0, 1, '', 1656312030, 1656312030, NULL), (10, '正常', '0', 4, 'system_disable', 0, 1, '', 1656312040, 1656312040, NULL), (11, '未知', '0', 5, 'sex', 0, 1, '', 1656062988, 1656062988, NULL), (12, '男', '1', 5, 'sex', 0, 1, '', 1656062999, 1656062999, NULL), (13, '女', '2', 5, 'sex', 0, 1, '', 1656063009, 1656063009, NULL);
COMMIT;

DROP TABLE IF EXISTS `la_dict_type`;
CREATE TABLE `la_dict_type`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字典名称',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字典类型名称',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0-停用 1-正常',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '字典类型表';

BEGIN;
INSERT INTO `la_dict_type` VALUES (1, '显示状态', 'show_status', 1, '', 1656381520, 1656381520, NULL), (2, '业务状态', 'business_status', 1, '', 1656381393, 1656381393, NULL), (3, '事件状态', 'event_status', 1, '', 1656381075, 1656381075, NULL), (4, '禁用状态', 'system_disable', 1, '', 1656311838, 1656311838, NULL), (5, '用户性别', 'sex', 1, '', 1656062946, 1656380925, NULL);
COMMIT;

DROP TABLE IF EXISTS `la_file`;
CREATE TABLE `la_file`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `cid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类目ID',
  `source_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传者id',
  `source` tinyint(1) NOT NULL DEFAULT 0 COMMENT '来源类型[0-后台,1-用户]',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT '类型[10=图片, 20=视频]',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件名称',
  `uri` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件路径',
  `create_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件表';

DROP TABLE IF EXISTS `la_file_cate`;
CREATE TABLE `la_file_cate`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID',
  `type` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT '类型[10=图片，20=视频，30=文件]',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `create_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件分类表';

DROP TABLE IF EXISTS `la_generate_column`;
CREATE TABLE `la_generate_column`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `table_id` int(11) NOT NULL DEFAULT 0 COMMENT '表id',
  `column_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段名称',
  `column_comment` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段描述',
  `column_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `is_required` tinyint(1) NULL DEFAULT 0 COMMENT '是否必填 0-非必填 1-必填',
  `is_pk` tinyint(1) NULL DEFAULT 0 COMMENT '是否为主键 0-不是 1-是',
  `is_insert` tinyint(1) NULL DEFAULT 0 COMMENT '是否为插入字段 0-不是 1-是',
  `is_update` tinyint(1) NULL DEFAULT 0 COMMENT '是否为更新字段 0-不是 1-是',
  `is_lists` tinyint(1) NULL DEFAULT 0 COMMENT '是否为列表字段 0-不是 1-是',
  `is_query` tinyint(1) NULL DEFAULT 0 COMMENT '是否为查询字段 0-不是 1-是',
  `query_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '=' COMMENT '查询类型',
  `view_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'input' COMMENT '显示类型',
  `dict_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '字典类型',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代码生成表字段信息表';

DROP TABLE IF EXISTS `la_generate_table`;
CREATE TABLE `la_generate_table`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `table_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表名称',
  `table_comment` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '表描述',
  `template_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '模板类型 0-单表(curd) 1-树表(curd)',
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '作者',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `generate_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '生成方式  0-压缩包下载 1-生成到模块',
  `module_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '模块名',
  `class_dir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '类目录名',
  `class_comment` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '类描述',
  `admin_id` int(11) NULL DEFAULT 0 COMMENT '管理员id',
  `menu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '菜单配置',
  `delete` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '删除配置',
  `tree` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '树表配置',
  `relations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '关联配置',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代码生成表信息表';


DROP TABLE IF EXISTS `la_gift_package`;
CREATE TABLE `la_gift_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '名字',
  `desc` varchar(255) DEFAULT NULL COMMENT '介绍',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0:异常 1:正常',
  `selling_price` decimal(10,2) DEFAULT NULL COMMENT '售卖金额',
  `price` decimal(10,2) NOT NULL COMMENT '实际金额',
  `type` tinyint(1) NOT NULL COMMENT '1:加油包 2: 礼包',
  `package_info` text COMMENT '礼包信息',
  `sort` int(11) DEFAULT NULL COMMENT '排序 从大到小',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='礼包信息';


DROP TABLE IF EXISTS `la_gift_package_order`;
CREATE TABLE `la_gift_package_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `package_id` int(11) NOT NULL COMMENT '礼包id',
  `sn` varchar(64) NOT NULL COMMENT '订单编号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `pay_sn` varchar(255) DEFAULT '' COMMENT '支付编号-冗余字段，针对微信同一主体不同客户端支付需用不同订单号预留。',
  `pay_way` tinyint(2) NOT NULL DEFAULT '2' COMMENT '支付方式 2-微信支付 3-支付宝支付',
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态：0-待支付；1-已支付',
  `pay_time` int(10) DEFAULT NULL COMMENT '支付时间',
  `order_amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `order_terminal` tinyint(1) DEFAULT '1' COMMENT '终端',
  `transaction_id` varchar(128) DEFAULT NULL COMMENT '第三方平台交易流水号',
  `refund_status` tinyint(1) DEFAULT '0' COMMENT '退款状态 0-未退款 1-已退款',
  `refund_transaction_id` varchar(255) DEFAULT NULL COMMENT '退款交易流水号',
  `type` tinyint(1) NOT NULL COMMENT '1:加油包 2:礼包',
  `change_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: 未处理过期扣除 1: 已处理过期扣除',
  `expiration_time` int(11) unsigned DEFAULT NULL COMMENT '过期时间',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `la_gpt_chat`;
CREATE TABLE `la_gpt_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `file_ids` varchar(255) NOT NULL COMMENT '引用文件',
  `ask` text NOT NULL COMMENT '提问',
  `reply` text NOT NULL COMMENT '回复',
  `model` varchar(128) NOT NULL DEFAULT '' COMMENT '对话模型',
  `tokens` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消耗tokens',
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '记录类型: 1：对话',
  `task_time` int(11) NOT NULL DEFAULT '0' COMMENT '运行时间',
  `censor_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态: [0=未审核, 1=合规, 2=不合规, 3=疑似, 4=审核失败]',
  `censor_result` text COMMENT '审核结果',
  `censor_num` int(2) unsigned NOT NULL DEFAULT '0' COMMENT '审核次数',
  `extra` text COMMENT '预留字段',
  `flows` text COMMENT 'tokens信息',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='通用对话记录表';

DROP TABLE IF EXISTS `la_gpt_model`;
CREATE TABLE `la_gpt_model` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model` varchar(255) NOT NULL COMMENT '模型名字',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：正常  0：禁止使用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='模型列表';

DROP TABLE IF EXISTS `la_gpt_thread`;
CREATE TABLE `la_gpt_thread` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `assistants_id` varchar(255) NOT NULL COMMENT '助手id',
  `thread_id` varchar(255) NOT NULL COMMENT '线程会话id',
  `run_id` varchar(255) DEFAULT '' COMMENT '运行id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名字',
  `is_debug` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 :聊天  1：调试',
  `tool_resources` varchar(500) NOT NULL DEFAULT '' COMMENT '线程资源',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='会话表';


DROP TABLE IF EXISTS `la_gpt_file`;
CREATE TABLE `la_gpt_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:文件 1:图片',
  `file_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'chatgpt file_id',
  `file_path` varchar(255) NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_name` varchar(255) NOT NULL COMMENT '文件名',
  `bytes` bigint(15) NOT NULL DEFAULT '0' COMMENT '大小',
  `purpose` varchar(255) NOT NULL DEFAULT '' COMMENT '目的 assistants,vision,batch,fine-tune',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='文件表';


DROP TABLE IF EXISTS `la_group`;
CREATE TABLE `la_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '名字',
  `status` tinyint(1) NOT NULL COMMENT '0 禁止使用 1：正常',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='部门表';

DROP TABLE IF EXISTS `la_hd_cue_image`;
CREATE TABLE `la_hd_cue_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `cid` int(11) NOT NULL COMMENT '分类',
  `title` varchar(2000) NOT NULL DEFAULT '' COMMENT '标题',
  `pic` varchar(128) DEFAULT '' COMMENT '图片',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示:1-是.0-否',
  `sort` int(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='文生图图库表';


DROP TABLE IF EXISTS `la_hd_cue_image_category`;
CREATE TABLE `la_hd_cue_image_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示:1-是.0-否',
  `sort` int(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='文生图图库分类表';


DROP TABLE IF EXISTS `la_hd_cue_word`;
CREATE TABLE `la_hd_cue_word` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `cid` int(11) NOT NULL COMMENT '分类',
  `title` varchar(2000) NOT NULL DEFAULT '' COMMENT '标题',
  `pic` varchar(128) DEFAULT '' COMMENT '图片',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示:1-是.0-否',
  `sort` int(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='文生图提示词表';

DROP TABLE IF EXISTS `la_hd_cue_word_category`;
CREATE TABLE `la_hd_cue_word_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示:1-是.0-否',
  `sort` int(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='文生图提示词分类表';


DROP TABLE IF EXISTS `la_hd_log`;
CREATE TABLE `la_hd_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:商品图生图 2:ai试衣',
  `params` varchar(1000) NOT NULL DEFAULT '' COMMENT '请求的接口参数',
  `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '返回任务ID',
  `sub_task_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '返回子任务ID的列表，子任务定义为生成单张图片的任务',
  `request_id` varchar(80) NOT NULL DEFAULT '',
  `task_status` tinyint(1) DEFAULT '0' COMMENT '0:等待 1:成功 2:失败',
  `remark` text COMMENT '报错原因',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='HD请求图片表';


DROP TABLE IF EXISTS `la_hd_image`;
CREATE TABLE `la_hd_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `log_id` int(11) NOT NULL DEFAULT '0' COMMENT 'log_id',
  `image` varchar(2000) NOT NULL DEFAULT '' COMMENT '文件路径',
  `sub_task_id` varchar(255) NOT NULL DEFAULT '' COMMENT '子任务的task_id',
  `task_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '进程状态',
  `task_completion` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '子任务的进度，取值范围为0-1的小数点后保留2位的小数',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='HD请求图片结果表';


DROP TABLE IF EXISTS `la_hd_image_cases`;
CREATE TABLE `la_hd_image_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `case_type` tinyint default 1 not null comment '案例类型 0: 上下装 1: 连衣裙 2：场景图 3：文字图 4: 模特图',
  `params` json null comment '参数',
  `result_image` VARCHAR(255) NOT NULL COMMENT '成品图片链接',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '案例状态 0：禁用 1：正常',
  `create_time` int null comment '创建时间',
  `update_time` int null comment '更新时间',
  `delete_time` int null comment '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模特优秀案例表';

DROP TABLE IF EXISTS `la_human_anchor`;
CREATE TABLE `la_human_anchor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `model_version` int(11) NOT NULL DEFAULT 1 COMMENT '模型类型 1：标准 2: 极致',
  `task_id` varchar(50) NOT NULL DEFAULT '' comment '唯一任务ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-1:已生成',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
  `anchor_id` varchar(50) NOT NULL DEFAULT '' COMMENT '形象id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `gender` varchar(255) NOT NULL DEFAULT '' COMMENT '性别',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '音色文件地址',
  `preview_result_url` varchar(255) NOT NULL DEFAULT '' COMMENT '预览视频链接',
  `preview_audio_url` varchar(255) NOT NULL DEFAULT '' COMMENT '预览音频链接',
  `anchor_id_value` varchar(255) NOT NULL DEFAULT '' COMMENT '合成视频的时候使用这个',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  CONSTRAINT la_human_model_task_id UNIQUE (model_version, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数字人形象表';

DROP TABLE IF EXISTS `la_human_audio`;
CREATE TABLE `la_human_audio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `model_version` int(11) NOT NULL DEFAULT 1 COMMENT '模型类型 1：标准 2: 极致',
  `task_id` varchar(50) NOT NULL DEFAULT '' comment '唯一任务ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-1:已生成',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `msg` varchar(2000) NOT NULL DEFAULT '' COMMENT '文字',
  `voice_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音色id',
  `audio_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音频id',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '音频链接',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  CONSTRAINT la_human_model_task_id UNIQUE (model_version, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数字人音频表';

DROP TABLE IF EXISTS `la_human_video`;
CREATE TABLE `la_human_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `model_version` int(11) NOT NULL DEFAULT 1 COMMENT '模型类型 1：标准 2: 极致',
  `task_id` varchar(50) NOT NULL DEFAULT '' comment '唯一任务ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-1:已生成',
  `anchor_id` varchar(50) NOT NULL DEFAULT '' COMMENT '形象id',
  `gender` varchar(50) NOT NULL DEFAULT '' COMMENT '性别-male,female',
  `video_id` varchar(255) NOT NULL COMMENT '视频id',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  CONSTRAINT la_human_model_task_id UNIQUE (model_version, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数字人视频表';

DROP TABLE IF EXISTS `la_human_video_task`;
CREATE TABLE `la_human_video_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
  `gender` varchar(50) NOT NULL DEFAULT '' COMMENT '性别-male,female',
  `model_version` int(11) NOT NULL DEFAULT 1 COMMENT '模型类型 1：标准 2: 极致',
  `task_id` varchar(50) NOT NULL DEFAULT '' comment '唯一任务ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-1:已生成',
  `audio_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '配音类型 1:ai配音 2:上传录音 3:选择音频 4:在线录音',
  `upload_video_url` varchar(255) NOT NULL DEFAULT '' COMMENT '视频链接',
  `anchor_id` varchar(50) NOT NULL DEFAULT '' COMMENT '形象id',
  `voice_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音色id',
  `msg` varchar(2000) NOT NULL DEFAULT '' COMMENT '文字',
  `audio_url` varchar(255) NOT NULL DEFAULT '' COMMENT '音频id',
  `upload_audio_url` varchar(255) NOT NULL DEFAULT '' COMMENT '上传的语音链接',
  `result_id` varchar(255) NOT NULL DEFAULT '' COMMENT '生成的视频id',
  `result_url` TEXT COMMENT '生成的视频地址',
  `tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  CONSTRAINT la_human_model_task_id UNIQUE (model_version, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数字人视频生成任务表';


DROP TABLE IF EXISTS `la_human_voice`;
CREATE TABLE `la_human_voice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `model_version` int(11) NOT NULL DEFAULT 1 COMMENT '模型类型 1：标准 2: 极致',
  `task_id` varchar(50) NOT NULL DEFAULT '' comment '唯一任务ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-1:已生成 2:生成中',
  `gender` varchar(50) NOT NULL DEFAULT '' COMMENT '性别-male,female',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `voice_id` varchar(50) NOT NULL DEFAULT '' COMMENT '语音id',
  `voice_urls` varchar(2000) NOT NULL DEFAULT '' COMMENT '音色文件地址',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数字人音色表';


DROP TABLE IF EXISTS `la_staff`;
CREATE TABLE `la_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `tips` json DEFAULT NULL COMMENT '标签',
  `brief` varchar(150) NOT NULL DEFAULT '' COMMENT '简介',
  `content` text COMMENT '内容介绍',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_new` int(11) NOT NULL DEFAULT '0' COMMENT '是否新上 1：是 0 否',
  `key` varchar(50) NOT NULL DEFAULT '' COMMENT '标识',
  `release_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '上线状态，0：未上线 1：已上线',
  `show_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '显示状态，0：隐藏 1：显示',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COMMENT='AI-员工';


DROP TABLE IF EXISTS `la_hot_search`;
CREATE TABLE `la_hot_search`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `sort` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序号',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '热门搜索表';

DROP TABLE IF EXISTS `la_jobs`;
CREATE TABLE `la_jobs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '岗位名称',
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '岗位编码',
  `sort` int(11) NULL DEFAULT 0 COMMENT '显示顺序',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态（0停用 1正常）',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '修改时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '岗位表';


DROP TABLE IF EXISTS `la_ll_analyse`;
CREATE TABLE `la_ll_analyse` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `conversation_id` int(11) NOT NULL COMMENT '会话id',
  `analyse_info` text COMMENT '分析结果',
  `create_time` int(100) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(100) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='练练 聊天分析';

DROP TABLE IF EXISTS `la_ll_audio_type`;
CREATE TABLE `la_ll_audio_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` int(11) NOT NULL COMMENT '名字',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT 'logo',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序 高的在前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0不显示 1：显示',
  `create_time` int(100) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(100) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='练练模块  类型';

DROP TABLE IF EXISTS `la_ll_category`;
CREATE TABLE `la_ll_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '模块名字',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序 高的在前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0不显示 1：显示',
  `create_time` int(100) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(100) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='练练模块  分类';


DROP TABLE IF EXISTS `la_ll_category_info`;
CREATE TABLE `la_ll_category_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(11) NOT NULL COMMENT '模块id',
  `title` varchar(255) NOT NULL COMMENT '模块名字',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `logo` varchar(255) NOT NULL COMMENT 'logo',
  `start_word` text NOT NULL COMMENT '开始词',
  `prompt_word` varchar(255) NOT NULL DEFAULT '' COMMENT '提示词',
  `target_word` varchar(255) NOT NULL DEFAULT '' COMMENT '目标词',
  `demand_word` varchar(255) NOT NULL DEFAULT '' COMMENT '切入词',
  `scene_info` text COMMENT '场景列表',
  `scene_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：关闭 1：开启',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0不显示 1：显示',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序 高的在前',
  `create_time` int(100) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(100) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='练练模块   模块';

DROP TABLE IF EXISTS `la_ll_chat`;
CREATE TABLE `la_ll_chat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `model_info_id` int(11) NOT NULL COMMENT '模型id',
  `conversation_id` int(11) NOT NULL COMMENT '会话id',
  `ask` varchar(500) NOT NULL DEFAULT '' COMMENT '问题',
  `ask_audio` varchar(255) NOT NULL DEFAULT '' COMMENT '音频路径',
  `ask_audio_time` int(11) NOT NULL DEFAULT '0' COMMENT '音频时长',
  `reply` varchar(500) NOT NULL DEFAULT '' COMMENT '回答',
  `reply_audio` varchar(255) NOT NULL DEFAULT '' COMMENT '音频路径',
  `score` varchar(500) NOT NULL DEFAULT '' COMMENT '得分信息',
  `task_time` int(11) NOT NULL DEFAULT '0' COMMENT '运行时间',
  `create_time` int(100) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(100) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='练练聊天  记录';


DROP TABLE IF EXISTS `la_ll_conversation`;
CREATE TABLE `la_ll_conversation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '会话名字',
  `user_id` int(11) NOT NULL COMMENT '用户',
  `model_info_id` int(11) NOT NULL COMMENT '模块id',
  `key_words` text COMMENT '关键词',
  `scene_value` varchar(255) NOT NULL COMMENT '场景 替换第一句对话',
  `status` tinyint(1) NOT NULL COMMENT '1:运行中 2：分析中  3：分析完成',
  `score` float(3,2) NOT NULL DEFAULT '0.00' COMMENT '分数',
  `audio_sum_time` int(11) NOT NULL DEFAULT '0' COMMENT '总语音时长',
  `end_time` int(11) DEFAULT NULL COMMENT '会话结束时间',
  `analyse` text COMMENT '分析结果',
  `create_time` int(100) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(100) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='练练  会话';

DROP TABLE IF EXISTS `la_model_config`;
CREATE TABLE `la_model_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `scene` varchar(255) NOT NULL COMMENT '场景',
  `code` int(11) NOT NULL COMMENT 'code',
  `unit` char(15) default '次' not null comment '单位',
  `name` varchar(255) NOT NULL COMMENT '名字',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '算力',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '说明',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：正常  0 ：异常',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型配置';


DROP TABLE IF EXISTS `la_mind_map`;
CREATE TABLE `la_mind_map` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户',
  `ask` text COMMENT '问题',
  `reply` text COMMENT '回答',
  `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
  `task_time` int(11) NOT NULL DEFAULT '0' COMMENT '消耗时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='思维导图生成记录';


DROP TABLE IF EXISTS `la_notice_record`;
CREATE TABLE `la_notice_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户id',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '内容',
  `scene_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '场景',
  `read` tinyint(1) NULL DEFAULT 0 COMMENT '已读状态;0-未读,1-已读',
  `recipient` tinyint(1) NULL DEFAULT 0 COMMENT '通知接收对象类型;1-会员;2-商家;3-平台;4-游客(未注册用户)',
  `send_type` tinyint(1) NULL DEFAULT 0 COMMENT '通知发送类型 1-系统通知 2-短信通知 3-微信模板 4-微信小程序',
  `notice_type` tinyint(1) NULL DEFAULT NULL COMMENT '通知类型 1-业务通知 2-验证码',
  `extra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '其他',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知记录表';

DROP TABLE IF EXISTS `la_notice_setting`;
CREATE TABLE `la_notice_setting`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scene_id` int(10) NOT NULL COMMENT '场景id',
  `scene_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景名称',
  `scene_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '场景描述',
  `recipient` tinyint(1) NOT NULL DEFAULT 1 COMMENT '接收者 1-用户 2-平台',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '通知类型: 1-业务通知 2-验证码',
  `system_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '系统通知设置',
  `sms_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '短信通知设置',
  `oa_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '公众号通知设置',
  `mnp_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '小程序通知设置',
  `support` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支持的发送类型 1-系统通知 2-短信通知 3-微信模板消息 4-小程序提醒',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通知设置表';

BEGIN;
INSERT INTO `la_notice_setting` VALUES (1, 101, '登录验证码', '用户手机号码登录时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\"]}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在登录，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '2', NULL), (2, 102, '绑定手机验证码', '用户绑定手机号码时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\"}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在绑定手机号，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\"}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\"}', '2', NULL), (3, 103, '变更手机验证码', '用户变更手机号码时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\"]}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在变更手机号，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '2', NULL), (4, 104, '找回登录密码验证码', '用户找回登录密码号码时发送', 1, 2, '{\"type\":\"system\",\"title\":\"\",\"content\":\"\",\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\"]}', '{\"type\":\"sms\",\"template_id\":\"SMS_123456\",\"content\":\"您正在找回登录密码，验证码${code}，切勿将验证码泄露于他人，本条验证码有效期5分钟。\",\"status\":\"1\",\"is_show\":\"1\"}', '{\"type\":\"oa\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"first\":\"\",\"remark\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '{\"type\":\"mnp\",\"template_id\":\"\",\"template_sn\":\"\",\"name\":\"\",\"tpl\":[],\"status\":\"0\",\"is_show\":\"\",\"tips\":[\"可选变量 验证码:code\",\"配置路径：小程序后台 > 功能 > 订阅消息\"]}', '2', NULL);
COMMIT;

DROP TABLE IF EXISTS `la_official_account_reply`;
CREATE TABLE `la_official_account_reply`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '规则名称',
  `keyword` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `reply_type` tinyint(1) NOT NULL COMMENT '回复类型 1-关注回复 2-关键字回复 3-默认回复',
  `matching_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '匹配方式：1-全匹配；2-模糊匹配',
  `content_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '内容类型：1-文本',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '回复内容',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '启动状态：1-启动；0-关闭',
  `sort` int(11) UNSIGNED NOT NULL DEFAULT 50 COMMENT '排序',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '公众号消息回调表';

DROP TABLE IF EXISTS `la_operation_log`;
CREATE TABLE `la_operation_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员名称',
  `account` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '管理员账号',
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '操作名称',
  `type` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求方式',
  `url` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '访问链接',
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求数据',
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求结果',
  `ip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'ip地址',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统日志表';

DROP TABLE IF EXISTS `la_phone_list`;
CREATE TABLE `la_phone_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `login_id` int(11) NOT NULL COMMENT '登陆人id',
  `work_we_chat_id` int(11) NOT NULL DEFAULT '0' COMMENT '企业微信id',
  `file_id` int(11) NOT NULL COMMENT '文件id',
  `user_id` varbinary(100) DEFAULT '0' COMMENT '用户微信信息id',
  `phone` char(11) NOT NULL COMMENT '电话',
  `name` varchar(255) NOT NULL COMMENT '备注名字',
  `remarks` varchar(255) DEFAULT NULL COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:待添加 1: 待通过  2:添加成功 3:添加失败',
  `add_time` int(11) DEFAULT NULL COMMENT '发送好友请求时间',
  `success_time` int(11) DEFAULT NULL COMMENT '处理请求时间',
  `msg` varchar(255) DEFAULT NULL COMMENT '异常信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `la_recharge_order`;
CREATE TABLE `la_recharge_order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单编号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `pay_sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '支付编号-冗余字段，针对微信同一主体不同客户端支付需用不同订单号预留。',
  `pay_way` tinyint(2) NOT NULL DEFAULT 2 COMMENT '支付方式 2-微信支付 3-支付宝支付',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态：0-待支付；1-已支付',
  `pay_time` int(10) NULL DEFAULT NULL COMMENT '支付时间',
  `order_amount` decimal(10, 2) NOT NULL COMMENT '充值金额',
  `order_terminal` tinyint(1) NULL DEFAULT 1 COMMENT '终端',
  `transaction_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '第三方平台交易流水号',
  `refund_status` tinyint(1) NULL DEFAULT 0 COMMENT '退款状态 0-未退款 1-已退款',
  `refund_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款交易流水号',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;

DROP TABLE IF EXISTS `la_refund_log`;
CREATE TABLE `la_refund_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '编号',
  `record_id` int(11) NOT NULL COMMENT '退款记录id',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联用户',
  `handle_id` int(11) NOT NULL DEFAULT 0 COMMENT '处理人id（管理员id）',
  `order_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单总的应付款金额，冗余字段',
  `refund_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '本次退款金额',
  `refund_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态，0退款中，1退款成功，2退款失败',
  `refund_msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '退款信息',
  `create_time` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;

DROP TABLE IF EXISTS `la_refund_record`;
CREATE TABLE `la_refund_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '退款编号',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联用户',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '来源订单id',
  `order_sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '来源单号',
  `order_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'order' COMMENT '订单来源 order-商品订单 recharge-充值订单',
  `order_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '订单总的应付款金额，冗余字段',
  `refund_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '本次退款金额',
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '第三方平台交易流水号',
  `refund_way` tinyint(1) NOT NULL DEFAULT 1 COMMENT '退款方式 1-线上退款 2-线下退款',
  `refund_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '退款类型 1-后台退款',
  `refund_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态，0退款中，1退款成功，2退款失败',
  `create_time` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;

DROP TABLE IF EXISTS `la_sms_log`;
CREATE TABLE `la_sms_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `scene_id` int(11) NOT NULL COMMENT '场景id',
  `mobile` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号码',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '发送内容',
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '发送关键字（注册、找回密码）',
  `is_verify` tinyint(1) NULL DEFAULT 0 COMMENT '是否已验证；0-否；1-是',
  `check_num` int(5) NULL DEFAULT 0 COMMENT '验证次数',
  `send_status` tinyint(1) NOT NULL COMMENT '发送状态：0-发送中；1-发送成功；2-发送失败',
  `send_time` int(10) NOT NULL COMMENT '发送时间',
  `results` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '短信结果',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信记录表';


DROP TABLE IF EXISTS `la_robot_record`;
CREATE TABLE `la_robot_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户的ID',
  `assistants_id` int(11) NOT NULL COMMENT '助理id',
  `thread_id` int(10) NOT NULL DEFAULT '0' COMMENT '线程（会话）ID',
  `run_id` varchar(255) NOT NULL DEFAULT '' COMMENT '运行id',
  `ask` text COMMENT '提问',
  `reply` text COMMENT '答复',
  `images` text COMMENT '附带图片',
  `files` text COMMENT '附带文件',
  `context` text COMMENT '上下文组',
  `flows` text COMMENT 'tokens信息',
  `model` varchar(100) NOT NULL DEFAULT '' COMMENT '对话模型',
  `tokens` int(10) DEFAULT NULL COMMENT '消耗tokens',
  `share_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分享的ID',
  `share_apikey` varchar(80) NOT NULL DEFAULT '' COMMENT '分享的密钥',
  `share_identity` varchar(60) NOT NULL DEFAULT '' COMMENT '分享的身份',
  `censor_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '审核状态: [0=未审核, 1=合规, 2=不合规, 3=疑似, 4=审核失败]',
  `censor_result` text COMMENT '审核结果',
  `censor_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核次数',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示: [0=否, 1=是]',
  `task_time` varchar(60) NOT NULL DEFAULT '0' COMMENT '对话耗时',
  `ask_ext` varchar(255) DEFAULT '' COMMENT '问题补充,json',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_idx` (`user_id`) USING BTREE COMMENT '用户索引',
  KEY `robot_idx` (`thread_id`) USING BTREE COMMENT '机器人索引',
  KEY `share_idx` (`share_id`) USING BTREE COMMENT '分享编号索引',
  KEY `identity_idx` (`share_identity`) USING BTREE COMMENT '分享身份索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='机器人对话表';

DROP TABLE IF EXISTS `la_system_menu`;
CREATE TABLE `la_system_menu`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级菜单',
  `type` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限类型: M=目录，C=菜单，A=按钮',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '菜单图标',
  `sort` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT '菜单排序',
  `perms` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权限标识',
  `paths` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由地址',
  `component` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '前端组件',
  `selected` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '选中路径',
  `params` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由参数',
  `is_cache` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否缓存: 0=否, 1=是',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否显示: 0=否, 1=是',
  `is_disable` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否禁用: 0=否, 1=是',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统菜单表';


DROP TABLE IF EXISTS `la_scene`;
CREATE TABLE `la_scene` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '上级id',
  `name` varchar(255) NOT NULL COMMENT '场景名称',
  `logo` varchar(255) NOT NULL COMMENT 'logo',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 不可使用 1：正常',
  `description` text null comment '描述',
  `sort` int(11) DEFAULT '0' COMMENT '排序  大的在前',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='后台场景值';


DROP TABLE IF EXISTS `la_suno`;
CREATE TABLE `la_suno` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户',
  `ask` text COMMENT '问题',
  `title` varchar(255) NOT NULL COMMENT '风格',
  `tags` varchar(255) NOT NULL COMMENT '标签',
  `task_id` varchar(255) NOT NULL COMMENT '任务id',
  `model` varchar(255) NOT NULL COMMENT '使用的模型',
  `json_info` text COMMENT '生成信息',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1: 创建成功  2：任务完成',
  `dow_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：无下载  1：下载中',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `la_system_role`;
CREATE TABLE `la_system_role`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `desc` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色表';

DROP TABLE IF EXISTS `la_system_role_menu`;
CREATE TABLE `la_system_role_menu`  (
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '角色ID',
  `menu_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '菜单ID',
  PRIMARY KEY (`role_id`, `menu_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色菜单关系表';

DROP TABLE IF EXISTS `la_tools`;
CREATE TABLE `la_tools` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `logo` varchar(255) NOT NULL COMMENT '头像',
  `name` varchar(255) NOT NULL COMMENT '工具名字',
  `keyword` varchar(255) NOT NULL DEFAULT '' COMMENT '工具关键词',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0：禁止使用 1：正常',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '权重， 愈大 排序在前',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='工具表';


DROP TABLE IF EXISTS `la_tools_log`;
CREATE TABLE `la_tools_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `tools_id` int(11) NOT NULL COMMENT '工具id',
  `ask` text COMMENT '翻译后问题',
  `origin_ask` text COMMENT '原始问题',
  `reply` text COMMENT '回复',
  `file_id` int(11) NOT NULL DEFAULT '0' COMMENT '用到的文件id',
  `task_time` int(11) NOT NULL COMMENT '使用时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:待生成 1:成功 2:生成中 3:失败',
  `mode` varchar(255) NOT NULL DEFAULT '' COMMENT 'mode',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='工具使用记录';

DROP TABLE IF EXISTS `la_user`;
CREATE TABLE `la_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sn` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '编号',
  `avatar` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `real_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `nickname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户电话',
  `sex` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户性别: [1=男, 2=女]',
  `channel` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注册渠道: [1-微信小程序 2-微信公众号 3-手机H5 4-电脑PC 5-苹果APP 6-安卓APP]',
  `is_disable` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否禁用: [0=否, 1=是]',
  `login_ip` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `is_new_user` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是新注册用户: [1-是, 0-否]',
  `user_money` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '用户余额',
  `tokens` int(10) NOT NULL DEFAULT 0 COMMENT '用户剩余token数',
  `total_recharge_amount` decimal(10, 2) UNSIGNED NULL DEFAULT 0.00 COMMENT '累计充值',
  `last_survey_reminder_time` int(10) NULL DEFAULT NULL COMMENT '最近一次调查问卷提醒时间',
  `user_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户类型 0：个人 1：企业',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `sn`(`sn`) USING BTREE COMMENT '编号唯一',
  UNIQUE INDEX `account`(`account`) USING BTREE COMMENT '账号唯一'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户表';

DROP TABLE IF EXISTS `la_surveys`;
CREATE TABLE `la_surveys` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `company_name` varchar(200) NOT NULL DEFAULT '' COMMENT '公司名称',
  `company_size` varchar(20) NOT NULL DEFAULT '' COMMENT '公司规模',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_id`(`user_id`) USING BTREE COMMENT '用户ID唯一'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '调查问卷表';

DROP TABLE IF EXISTS `la_user_account_log`;
CREATE TABLE `la_user_account_log`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sn` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `change_object` tinyint(1) NOT NULL DEFAULT 0 COMMENT '变动对象',
  `change_type` smallint(5) NOT NULL COMMENT '变动类型',
  `action` tinyint(1) NOT NULL DEFAULT 0 COMMENT '动作 1-增加 2-减少',
  `change_amount` decimal(10, 2) NOT NULL COMMENT '变动数量',
  `left_amount` decimal(10, 2) NOT NULL DEFAULT 100.00 COMMENT '变动后数量',
  `source_sn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '关联单号',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `extra` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '预留扩展字段',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;

DROP TABLE IF EXISTS `la_user_active_log`;
CREATE TABLE `la_user_active_log` (
  `id` BIGINT(20) UNSIGNED AUTO_INCREMENT COMMENT '自增ID',
  `user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '用户ID',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) NULL DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户活跃表';

DROP TABLE IF EXISTS `la_user_auth`;
CREATE TABLE `la_user_auth`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `openid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '微信openid',
  `unionid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '微信unionid',
  `terminal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '客户端类型：1-微信小程序；2-微信公众号；3-手机H5；4-电脑PC；5-苹果APP；6-安卓APP',
  `create_time` int(10) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `openid`(`openid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户授权表';

DROP TABLE IF EXISTS `la_user_session`;
CREATE TABLE `la_user_session`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `terminal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '客户端类型：1-微信小程序；2-微信公众号；3-手机H5；4-电脑PC；5-苹果APP；6-安卓APP',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '令牌',
  `update_time` int(10) NULL DEFAULT NULL COMMENT '更新时间',
  `expire_time` int(10) NOT NULL COMMENT '到期时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `admin_id_client`(`user_id`, `terminal`) USING BTREE COMMENT '一个用户在一个终端只有一个token',
  UNIQUE INDEX `token`(`token`) USING BTREE COMMENT 'token是唯一的'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户会话表';

SET FOREIGN_KEY_CHECKS = 1;

DROP TABLE IF EXISTS `la_user_tokens_log`;
CREATE TABLE `la_user_tokens_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sn` varchar(32) NOT NULL DEFAULT '' COMMENT '流水号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `change_type` smallint(5) NOT NULL COMMENT '变动类型',
  `action` tinyint(1) NOT NULL DEFAULT '0' COMMENT '动作 1-增加 2-减少',
  `change_object` tinyint(1) NOT NULL DEFAULT '0' COMMENT '变动对象',
  `change_amount` int(10) NOT NULL COMMENT '变动数量',
  `left_tokens` int(10) NOT NULL DEFAULT '100' COMMENT '变动后数量',
  `source_sn` varchar(255) DEFAULT NULL COMMENT '关联单号',
  `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `extra` text COMMENT '预留扩展字段',
  `status` tinyint(1) DEFAULT '1' COMMENT '1：成功 2：失败退还',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `la_chat_prompt`;
CREATE TABLE `la_chat_prompt` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prompt_name` VARCHAR(255) NOT NULL COMMENT '提示词名称',
  `prompt_text` TEXT COMMENT '提示词内容',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聊天提示词表';


DROP TABLE IF EXISTS `la_vector`;
CREATE TABLE `la_vector` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `vs_id` varchar(255) NOT NULL DEFAULT '' COMMENT '向量ID gpt侧',
  `vector_files_id` text NOT NULL COMMENT '向量文件id  数据库侧',
  `gtp_vector_files_id` text COMMENT '向量文件id  gpt侧',
  `type` varchar(11) NOT NULL COMMENT '类型',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `image` varchar(255) DEFAULT '' COMMENT '背景图',
  `expires_after` varchar(255) NOT NULL DEFAULT '0' COMMENT '过期策略',
  `file_counts` varchar(255) DEFAULT '' COMMENT '文件统计',
  `metadata` text COMMENT '元数据',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='向量存储表';

DROP TABLE IF EXISTS `la_vector_file`;
CREATE TABLE `la_vector_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名字',
  `vector_file_id` varchar(255) NOT NULL DEFAULT '' COMMENT '向量文件id gpt侧',
  `vector_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '向量id集合 数据库侧',
  `gtp_vector_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '向量id集合 gpt侧',
  `chunking_strategy` varchar(255) NOT NULL DEFAULT '' COMMENT '文件切割方式',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0：不可使用 1：正常',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='向量存储文件表';

DROP TABLE IF EXISTS `la_work_config`;
CREATE TABLE `la_work_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '主用户id',
  `count` int(11) NOT NULL COMMENT '每天加几个人',
  `space_time` int(11) NOT NULL COMMENT '间隔多久',
  `create_time` int(10) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;


DROP TABLE IF EXISTS `la_work_we_chat`;
CREATE TABLE `la_work_we_chat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `login_user_id` int(11) NOT NULL COMMENT '登陆人的id',
  `ip` varchar(255) NOT NULL COMMENT 'ip',
  `port` int(11) NOT NULL COMMENT '端口',
  `nick_name` varchar(255) NOT NULL DEFAULT '' COMMENT '真名',
  `real_name` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `alias` varchar(255) NOT NULL DEFAULT '' COMMENT '别名',
  `avatar_url` varchar(500) NOT NULL DEFAULT '' COMMENT '头像',
  `sex` tinyint(1) DEFAULT NULL COMMENT '0:女 1:男',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0: 异常 1:正常使用',
  `msg` text COMMENT '加好友的第一句话',
  `login_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: 未登录 1:登陆',
  `login_out_time` int(11) DEFAULT NULL COMMENT '退出时间',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '每天可以添加的人数',
  `space_time` int(11) NOT NULL DEFAULT '0' COMMENT '间隔多久加一次(分钟)',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信登录列表';


CREATE TABLE  IF NOT EXISTS `la_knowledge` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库id',
  `name` varchar(255) DEFAULT NULL COMMENT '知识库名称',
  `category_id` varchar(255) DEFAULT NULL COMMENT '同名分类id',
  `description` text COMMENT '知识库描述',
  `rerank_min_score` float DEFAULT NULL COMMENT '相似度阈值',
  `separator` varchar(32) DEFAULT NULL COMMENT '分句标识符',
  `chunk_size` int(11) DEFAULT NULL COMMENT '分段预估长度',
  `overlap_size` int(11) DEFAULT NULL COMMENT '分段重叠长度',
  `structure_type` varchar(255) DEFAULT 'unstructured' COMMENT '知识库的数据类型',
  `source_type` varchar(255) DEFAULT 'DATA_CENTER_FILE' COMMENT '应用数据的数据类型',
  `sink_type` varchar(100) DEFAULT 'BUILT_IN' COMMENT '知识库的向量存储类型',
  `strategy` tinyint(4) DEFAULT '1' COMMENT '切割策略 1智能 2自定义',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态 默认1',
  `request_count` int(11) DEFAULT '0' COMMENT '调用次数',
  `tokens` int(11) DEFAULT '0' COMMENT '扣除算力',
  `is_bind` tinyint(4) DEFAULT '0' COMMENT '文件绑定进度1已绑定 0未绑定',
  `site` varchar(255) DEFAULT NULL COMMENT '站长地址',
  `is_delete` int(11) DEFAULT '0' COMMENT '1 删除',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS  `la_knowledge_bind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `kid` int(11) DEFAULT '0' COMMENT '知识库id',
  `data_id` int(11) DEFAULT '0' COMMENT '关联表id',
  `type` tinyint(2) DEFAULT '0' COMMENT '关联表 1个微机器人 2 陪练',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库索引id',
  `rerank_min_score` float DEFAULT '0.01' COMMENT '相似度阈值',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='知识库绑定';

CREATE TABLE IF NOT EXISTS  `la_knowledge_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库索引id',
  `kid` int(11) DEFAULT '0' COMMENT '知识库id',
  `category_id` varchar(255) DEFAULT NULL COMMENT '知识库分类id',
  `file_id` varchar(255) DEFAULT NULL COMMENT '文件id',
  `name` varchar(255) DEFAULT NULL COMMENT '文件名称',
  `type` varchar(30) DEFAULT NULL COMMENT '文件类型',
  `size` float DEFAULT NULL COMMENT '文件大小',
  `parser` varchar(100) DEFAULT 'DASHSCOPE_DOCMIND' COMMENT '解析器',
  `status` enum('INIT','PARSING','PARSE_SUCCESS','PARSE_FAILED') DEFAULT 'PARSE_SUCCESS' COMMENT '解析状态',
  `file_url` varchar(255) DEFAULT NULL COMMENT '文件地址',
  `is_completed` tinyint(4) DEFAULT '0' COMMENT '拉取切片是否完成 1完成0 未完成',
  `slice_count` int(11) DEFAULT '0' COMMENT '切片总数',
  `pull_count` int(11) DEFAULT '0' COMMENT '已拉取数',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS  `la_knowledge_file_slice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `rid` int(11) DEFAULT '0' COMMENT '检索id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库索引id',
  `file_id` varchar(255) DEFAULT NULL COMMENT '文档id',
  `content` text COMMENT '切片内容',
  `hash` varchar(255) DEFAULT NULL COMMENT '内容hash',
  `score` double DEFAULT NULL COMMENT '文本切片相似度得分',
  `metadata` longtext COMMENT '文本切片元数据',
  `source` varchar(255) DEFAULT NULL COMMENT '来源',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `la_knowledge_retrieve` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `kid` int(11) DEFAULT '0' COMMENT '知识库id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库索引id',
  `rerank_min_score` float DEFAULT '0.01' COMMENT '相似度阈值',
  `prompt` varchar(500) DEFAULT NULL COMMENT '文本内容',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

CREATE TABLE  IF NOT EXISTS  `la_knowledge_retrieve_slice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `rid` int(11) DEFAULT '0' COMMENT '检索id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库索引id',
  `content` text COMMENT '切片内容',
  `hash` varchar(255) DEFAULT NULL COMMENT '内容hash',
  `score` double DEFAULT NULL COMMENT '文本切片相似度得分',
  `metadata` longtext COMMENT '文本切片元数据',
  `source` varchar(255) DEFAULT NULL COMMENT '来源',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS  `la_knowledge_use_scene` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库id',
  `rerank_min_score` float DEFAULT NULL COMMENT '相似度阈值',
  `name` varchar(255) DEFAULT NULL COMMENT '场景名称',
  `type` tinyint(4) DEFAULT NULL COMMENT '场景类型',
  `description` varchar(255) DEFAULT NULL COMMENT '场景描述',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='知识库使用场景';

CREATE TABLE IF NOT EXISTS  `la_knowledge_use_scene_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `index_id` varchar(255) DEFAULT NULL COMMENT '知识库id',
  `prompt` text COMMENT '提示词',
  `rerank_min_score` double DEFAULT '0.01' COMMENT '相似度阈值',
  `retrieve_content` text COMMENT '检索内容',
  `retrieve_length` int(11) DEFAULT '0' COMMENT '检索内容字节数',
  `retrieve_tokens` double DEFAULT '0' COMMENT '检索内容token',
  `content` text COMMENT '模型输出内容',
  `prompt_tokens` double DEFAULT '0' COMMENT '用户的输入转换成 Token 后的长度',
  `completion_tokens` double DEFAULT NULL COMMENT '模型生成回复转换为 Token 后的长度',
  `total_tokens` double DEFAULT '0' COMMENT 'prompt_tokens与completion_tokens的总和',
  `tokens` double DEFAULT '0' COMMENT '知识库token和回复内容token的和',
  `task_id` varchar(255) DEFAULT NULL COMMENT '任务id',
  `scene` varchar(255) DEFAULT NULL COMMENT '当前知识库使用场景',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='用户知识库使用记录';

CREATE TABLE IF NOT EXISTS `la_human_task` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `video_task_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频定时任务id',
    `model_version` int(11) NOT NULL DEFAULT '1' COMMENT '模型类型 1：标准 2: 极速',
    `task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态- 0:处理中,1:成功,2失败',
    `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型 1:形象2:音色 3:音频 4:视频',
    `data_id` varchar(50) NOT NULL DEFAULT '' COMMENT '数据id',
    `extra` varchar(500) NOT NULL DEFAULT '' COMMENT '额外字段',
    `result_id` varchar(255) NOT NULL DEFAULT '' COMMENT '生成的id',
    `result_url` text COMMENT '生成地址',
    `upload_url` text COMMENT '下载地址',
    `tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
    `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
    `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
    `pend_time` int(11) DEFAULT NULL COMMENT '待执行时间',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
    `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='数字人定时任务表';

  CREATE TABLE IF NOT EXISTS `la_interview` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `interview_record_id` int(11) NOT NULL DEFAULT '0' COMMENT '面试记录ID',
  `job_id` int(11) NOT NULL DEFAULT '0' COMMENT '岗位ID',
  `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '分数',
  `comment` varchar(2000) NOT NULL DEFAULT '' COMMENT '评价',
  `analyze` varchar(2000) NOT NULL DEFAULT '' COMMENT '分析',
  `inspection_point` varchar(2000) NOT NULL DEFAULT '' COMMENT '考察点',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '整体状态 0:进行中,1:已完成,2:主动退出,3:重新开始,4意外中断,5分析中,6分析失败,7AI分析失败',
  `reason` varchar(500) NOT NULL DEFAULT '' COMMENT '中断/退出原因',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_record` (`interview_record_id`) COMMENT '面试记录索引',
  KEY `idx_user_job` (`user_id`,`job_id`) COMMENT '用户和岗位索引',
  KEY `idx_status` (`status`) COMMENT '状态索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='面试表(具体的面试会话)';

CREATE TABLE IF NOT EXISTS `la_interview_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL DEFAULT '0' COMMENT '岗位ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `auto_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:关闭 1:开启',
  `reply_link` varchar(255) NOT NULL DEFAULT '' COMMENT '自动回复链接',
  `niu_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '牛人特定招呼开关 0:关闭 1:开启',
  `niu_link` varchar(255) NOT NULL DEFAULT '' COMMENT '牛人链接',
  `degree` varchar(255) NOT NULL DEFAULT '' COMMENT ' 学历',
  `school` varchar(255) NOT NULL DEFAULT '0' COMMENT '院校',
  `work_years` varchar(50) NOT NULL DEFAULT '0' COMMENT '工作年限,经验要求',
  `intention` varchar(100) NOT NULL DEFAULT '0' COMMENT '求职意向',
  `salary` varchar(50) NOT NULL DEFAULT '0' COMMENT '薪资',
  `end_word` varchar(255) NOT NULL DEFAULT '' COMMENT '面试结束提醒页设置',
  `restart_word` varchar(255) NOT NULL DEFAULT '' COMMENT '重新面试提醒页',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='面试高级设置表';

CREATE TABLE IF NOT EXISTS `la_interview_cv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `interview_job_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '面试岗位id(主要用于第一次解析简历收费计算)',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '公司id，实际关联的是user表',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1:自己填2:解析',
  `word_url` varchar(150) NOT NULL DEFAULT '' COMMENT '简历url',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '姓名',
  `sex` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1:男 2:女',
  `age` int(11) NOT NULL DEFAULT '0' COMMENT '年龄',
  `mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '联系方式',
  `school` varchar(255) NOT NULL DEFAULT '' COMMENT '毕业院校',
  `degree` varchar(255) NOT NULL DEFAULT '' COMMENT ' 学历',
  `work_years` int(10) NOT NULL DEFAULT '0' COMMENT '工作年限',
  `work_ex` text NOT NULL COMMENT '工作经历',
  `project_ex` text NOT NULL COMMENT '项目经历',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='简历表';

CREATE TABLE IF NOT EXISTS `la_interview_dialog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interview_id` int(11) NOT NULL DEFAULT '0' COMMENT '面试ID',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:带关注的问题 2:深入的问题 3:不带关注的问题 4:开场白 5:中断信息 6:退出信息',
  `question` text COMMENT '提问内容',
  `answer` text COMMENT '用户回答内容',
  `question_url` varchar(255) NOT NULL DEFAULT '' COMMENT '问题的语音地址',
  `answer_url` varchar(255) NOT NULL DEFAULT '' COMMENT '回复的语音地址',
  `out_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '退出理由',
  `answer_duration` int(10) NOT NULL DEFAULT '0' COMMENT '回复语音时长',
  `question_duration` int(10) NOT NULL DEFAULT '0' COMMENT '问题语音时长',
  `restart_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '重新面试',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='面试对话记录表';

CREATE TABLE IF NOT EXISTS `la_interview_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `job_id` int(11) NOT NULL DEFAULT '0' COMMENT '岗位ID',
  `content` varchar(2000) NOT NULL DEFAULT '' COMMENT '评价',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='面试反馈表';

CREATE TABLE IF NOT EXISTS `la_interview_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:文字 2:语音',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '岗位名称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `company` varchar(255) NOT NULL DEFAULT '' COMMENT '公司名称',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT ' 职位详情',
  `jd` varchar(1000) NOT NULL DEFAULT '' COMMENT '任职要求',
  `extra` varchar(1000) NOT NULL DEFAULT '' COMMENT '附加考察',
  `attention` varchar(1000) NOT NULL DEFAULT '' COMMENT '面试关注',
  `hello_word` varchar(255) NOT NULL DEFAULT '' COMMENT '招呼语',
  `end_word` varchar(255) NOT NULL DEFAULT '' COMMENT '结束语',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0：禁用 1：正常',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='面试岗位表';


CREATE TABLE IF NOT EXISTS `la_interview_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `interview_name` varchar(255) NOT NULL DEFAULT '' COMMENT '面试者名字，取简历',
  `job_id` int(11) NOT NULL DEFAULT '0' COMMENT '岗位ID',
  `job_name` varchar(255) NOT NULL DEFAULT '' COMMENT '岗位名称',
  `first_start_time` int(11) NOT NULL DEFAULT '0' COMMENT '首次开始时间',
  `last_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后结束时间',
  `duration` int(11) NOT NULL DEFAULT '0' COMMENT '面试时长',
  `total_sessions` int(11) NOT NULL DEFAULT '0' COMMENT '总面试次数',
  `last_interview_id` int(11) NOT NULL DEFAULT '0' COMMENT '最后一次面试ID',
  `best_score` int(11) NOT NULL DEFAULT '0' COMMENT '最高分数',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '整体状态 0:进行中,1:已完成,2:主动退出,3:重新开始,4意外中断,5分析中,6分析失败,7AI分析失败',
  `degree` varchar(255) NOT NULL DEFAULT '' COMMENT ' 学历',
  `work_years` int(10) NOT NULL DEFAULT '0' COMMENT '工作年限',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_status` (`status`) COMMENT '状态索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='面试记录表(一个用户对一个岗位的记录)';

DROP TABLE IF EXISTS `la_failed_jobs`;
CREATE TABLE `la_failed_jobs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `job_id` VARCHAR(255) NOT NULL COMMENT '任务ID',
    `job_class` VARCHAR(255) NOT NULL COMMENT '任务类名',
    `job_data` TEXT NOT NULL COMMENT '任务数据',
    `error_message` TEXT NOT NULL COMMENT '错误信息',
    `attempts` INT(11) NOT NULL DEFAULT 0 COMMENT '重试次数',
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '失败时间',
    PRIMARY KEY (`id`),
    KEY `idx_job_class` (`job_class`),
    KEY `idx_failed_at` (`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='失败任务表';

DROP TABLE IF EXISTS `la_ai_wechat_greet_strategy`;
CREATE TABLE `la_ai_wechat_greet_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `is_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启打招呼策略 0：关闭 1：开启',
    `interval_time` INT(11) NOT NULL DEFAULT 1 COMMENT '打招呼间隔时间(单位：分钟)',
    `friend_greet_is_reply` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '主动打招呼回复类型 0: 关闭 1: 开启',
    `greet_after_ai_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '打招呼后，是否开启AI接管 0：关闭（人工） 1：开启 (AI)',
    `greet_content` JSON NULL COMMENT '打招呼内容',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信打招呼策略表';

DROP TABLE IF EXISTS `la_ai_wechat_reply_strategy`;
CREATE TABLE `la_ai_wechat_reply_strategy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `multiple_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '多轮回复类型 0: 逐条回复 1: 合并回复 2：只回复最后一条',
    `number_chat_rounds` INT(11) NOT NULL DEFAULT 0 COMMENT '聊天轮数',
    `voice_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启语音回复',
    `image_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启图片消息回复',
    `image_reply` TEXT NULL COMMENT '图片消息回复的内容',
    `stop_enable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启停止回复',
    `stop_keywords` JSON NULL COMMENT '触发停止回复的关键词',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信回复策略表';

DROP TABLE IF EXISTS `la_ai_wechat_robot_keyword`;
CREATE TABLE `la_ai_wechat_robot_keyword` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `robot_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '机器人ID',
    `match_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '匹配模式 0: 模糊匹配 1：精确匹配',
    `keyword` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '关键词',
    `reply` JSON NULL COMMENT '回复内容',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信机器人关键词表';

DROP TABLE IF EXISTS `la_ll_scene`;
CREATE TABLE `la_ll_scene` (
	`id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT 0 COMMENT '用户ID',
	`logo` VARCHAR ( 100 ) NOT NULL DEFAULT '' COMMENT '场景LOGO',
	`name` VARCHAR ( 100 ) NOT NULL DEFAULT '' COMMENT '场景名称',
	`description` TEXT NULL COMMENT '场景描述',
	`training_target` JSON NULL COMMENT '练习目标',
	`tips` JSON NULL COMMENT '温馨提示',
	`coach_name` VARCHAR ( 100 ) NOT NULL DEFAULT '' COMMENT '陪练者名称',
	`coach_persona` LONGTEXT NULL COMMENT '陪练者人设',
	`coach_language` VARCHAR ( 50 ) NOT NULL DEFAULT '' COMMENT '陪练者母语',
	`coach_voice` VARCHAR ( 50 ) NOT NULL DEFAULT '' COMMENT '陪练者音色',
	`practitioner_persona` LONGTEXT NULL COMMENT '练习者人设',
	`analysis_report_config` JSON NULL COMMENT '分析报告配置',
	`sort` INT NOT NULL DEFAULT 0 COMMENT '场景排序',
	`status` TINYINT NOT NULL DEFAULT 1 COMMENT '场景状态 0 不可使用 1：正常',
	`create_time` INT ( 11 ) DEFAULT NULL COMMENT '创建时间',
	`update_time` INT ( 11 ) DEFAULT NULL COMMENT '更新时间',
	`delete_time` INT ( 11 ) DEFAULT NULL COMMENT '删除时间',
	PRIMARY KEY ( `id` ) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '场景表';

DROP TABLE IF EXISTS `la_ll_chat`;
CREATE TABLE `la_ll_chat` (
	`id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT 0 COMMENT '用户ID',
	`scene_id` INT NOT NULL DEFAULT 0 COMMENT '场景ID',
	`analysis_id` INT NOT NULL DEFAULT 0 COMMENT '分析ID',
	`preliminary_ask` VARCHAR ( 500 ) NOT NULL DEFAULT '' COMMENT '陪练者开场白',
	`preliminary_ask_audio` VARCHAR ( 200 ) NOT NULL DEFAULT '' COMMENT '陪练者开场白  - 语音',
	`preliminary_ask_audio_duration` INT NOT NULL DEFAULT 0 COMMENT '陪练者开场白  - 语音时长',
	`ask` LONGTEXT NULL COMMENT '练习者提问',
	`ask_audio` VARCHAR ( 500 ) NOT NULL DEFAULT '' COMMENT '练习者提问 - 语音',
	`ask_audio_duration` INT NOT NULL DEFAULT 0 COMMENT '练习者语音时长',
	`reply` LONGTEXT NULL COMMENT '陪练者回复',
	`reply_audio` VARCHAR ( 500 ) NOT NULL DEFAULT '' COMMENT '陪练者回复 - 语音',
	`reply_audio_duration` INT NOT NULL DEFAULT 0 COMMENT '陪练者回复 - 语音时长',
	`performance` LONGTEXT NULL COMMENT '对话表现',
	`speechcraft` LONGTEXT NULL COMMENT '话术提炼',
	`create_time` INT ( 11 ) DEFAULT NULL COMMENT '创建时间',
	`update_time` INT ( 11 ) DEFAULT NULL COMMENT '更新时间',
	`delete_time` INT ( 11 ) DEFAULT NULL COMMENT '删除时间',
	PRIMARY KEY ( `id` ) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '场景聊天表';

DROP TABLE IF EXISTS `la_ll_analysis`;
CREATE TABLE `la_ll_analysis` (
	`id` INT ( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT 0 COMMENT '用户ID',
	`scene_id` INT NOT NULL DEFAULT 0 COMMENT '场景ID',
	`task_id` VARCHAR ( 200 ) NOT NULL DEFAULT '' COMMENT '任务ID',
	`status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0：对话中 1：分析中 2：分析成功 3：分析失败',
	`tries` INT NOT NULL DEFAULT 0 COMMENT '重试次数',
	`remark` VARCHAR ( 200 ) NOT NULL DEFAULT '' COMMENT '分析备注',
	`total_score` LONGTEXT NULL COMMENT '总分析得分',
	`total_response` LONGTEXT NULL COMMENT '总分析结果',
	`model_response` LONGTEXT NULL COMMENT '模块得分与分析结果',
   `start_time` INT ( 11 ) DEFAULT NULL COMMENT '训练开始时间',
	`end_time` INT ( 11 ) DEFAULT NULL COMMENT '训练结束时间',
	`create_time` INT ( 11 ) DEFAULT NULL COMMENT '创建时间',
	`update_time` INT ( 11 ) DEFAULT NULL COMMENT '更新时间',
	`delete_time` INT ( 11 ) DEFAULT NULL COMMENT '删除时间',
	PRIMARY KEY ( `id` ) USING BTREE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '分析报告表';

DROP TABLE IF EXISTS `la_chat_log`;
CREATE TABLE `la_chat_log` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户的ID',
    `task_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '唯一任务id',
    `assistant_id` INT(11) NOT NULL DEFAULT 0 COMMENT '助理ID',
    `message` TEXT NULL COMMENT '用户的提问内容',
    `reply` TEXT NULL COMMENT '回复内容',
    `reasoning_content` TEXT NULL COMMENT '推理内容',
    `usage_tokens` JSON NULL COMMENT '使用tokens',
    `chat_type` INT(11) NOT NULL DEFAULT 0 COMMENT '聊天类型',
    `file_ids` VARCHAR(500) NOT NULL  DEFAULT '' COMMENT '消息附带的文件id集合',
    `task_time` INT(11) UNSIGNED DEFAULT 0 COMMENT '对话耗时',
    `create_time` INT(10) NOT NULL COMMENT '创建时间',
    `update_time` INT(10) DEFAULT NULL COMMENT '修改时间',
    `delete_time` INT(10) DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='聊天记录表';

DROP TABLE IF EXISTS `la_ai_wechat_device`;
CREATE TABLE `la_ai_wechat_device` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL DEFAULT 0 COMMENT '用户ID',
    `device_model` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '设备型号',
    `device_status` TINYINT NOT NULL DEFAULT 1 COMMENT '设备状态 0: 下线 1: 在线',
    `device_code` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '设备码',
    `sdk_version` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '设备SDK版本',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `unique_device_code` (`device_code`),
    KEY `idx_device_code` (`device_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备表';

DROP TABLE IF EXISTS `la_ai_wechat`;
CREATE TABLE `la_ai_wechat` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL DEFAULT 0 COMMENT '用户ID',
    `device_code` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '设备码',
    `wechat_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '微信ID',
    `wechat_no` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信号',
    `wechat_nickname` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '微信昵称',
    `wechat_avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '微信头像',
    `wechat_status` TINYINT NOT NULL DEFAULT 1 COMMENT '微信状态 0: 下线 1: 在线',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `unique_wechat_id` (`wechat_id`),
    KEY `idx_wechat_id` (`wechat_id`),
    KEY `idx_device_code` (`device_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备表';

DROP TABLE IF EXISTS `la_ai_wechat_setting`;
CREATE TABLE `la_ai_wechat_setting` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `wechat_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '微信ID',
    `remark` VARCHAR(120) NOT NULL DEFAULT '' COMMENT '备注',
    `open_ai` TINYINT NOT NULL DEFAULT 0 COMMENT '是否开启AI功能 0: 关闭 1: 开启',
    `takeover_mode` TINYINT NOT NULL DEFAULT 0 COMMENT '接管模式 0: 人工接管 1: AI接管',
    `takeover_type` TINYINT NOT NULL DEFAULT 0 COMMENT '接管类型 0: 全部 1: 私聊 2: 群聊',
    `robot_id` INT(11) UNSIGNED NULL COMMENT '关联机器人ID',
    `takeover_range_mode` TINYINT NOT NULL DEFAULT 0 COMMENT '接管范围模式 0: 包含 1: 排除',
    `sort` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `unique_wechat_id` (`wechat_id`),
    KEY `idx_wechat_id` (`wechat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信设置表';

DROP TABLE IF EXISTS `la_ai_wechat_contact`;
CREATE TABLE `la_ai_wechat_contact` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `wechat_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '微信ID',
    `friend_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '好友ID',
    `friend_no` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '微信号',
    `nickname` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '好友昵称',
    `remark` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '备注',
    `gender` INT NOT NULL DEFAULT 0 COMMENT '性别（0：未知, 1：男, 2：女）',
    `country` VARCHAR(128) DEFAULT NULL COMMENT '国家',
    `province` VARCHAR(128) DEFAULT NULL COMMENT '省份',
    `city` VARCHAR(128) DEFAULT NULL COMMENT '城市',
    `avatar` VARCHAR(256) DEFAULT NULL COMMENT '头像',
    `business_remark` VARCHAR(256) DEFAULT NULL COMMENT '业务备注',
    `type` INT NOT NULL DEFAULT 0 COMMENT '联系人类型',
    `label_ids` JSON DEFAULT NULL COMMENT '标签ID',
    `phone` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
    `desc` TEXT DEFAULT NULL COMMENT '描述',
    `source` INT NOT NULL DEFAULT 0 COMMENT '好友来源 0：未知 1: QQ号 3: 微信号 4|12: QQ好友 8|14: 群聊 10|13: 手机通讯录 15: 手机号 17: 名片 18：附近的人 22|23|24|26|27|28|29：摇一摇 25： 漂流瓶 30：扫一扫 34：公众号 48：雷达 ',
    `source_ext` VARCHAR(256) DEFAULT NULL COMMENT '来源扩展信息',
    `create_time` INT(11) DEFAULT NULL COMMENT '加好友时间',
    `is_unusual` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否异常',
    `birth_date` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '出生日期',
    `contact_address` TEXT DEFAULT NULL COMMENT '联系地址',
    `open_ai` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启AI功能 0: 关闭 1: 开启',
    `takeover_mode` TINYINT NOT NULL DEFAULT 0 COMMENT '接管模式 0: 人工接管 1: AI接管',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `unique_wechat_id_friend_id` (`wechat_id`, `friend_id`),
    KEY `idx_wechat_id` (`wechat_id`),
    KEY `idx_friend_id` (`friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信联系人表';

DROP TABLE IF EXISTS `la_ai_wechat_todo`;
CREATE TABLE `la_ai_wechat_todo` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `wechat_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '微信ID',
    `friend_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '好友ID',
    `todo_type` TINYINT NOT NULL DEFAULT 0 COMMENT '待办类型 0: 代办提醒 1: 自动任务',
    `todo_content` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '待办内容',
    `todo_status` TINYINT NOT NULL DEFAULT 0 COMMENT '待办状态 0: 待执行 1: 已完成 2：执行失败',
    `todo_time` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '待办时间',
    `retry_num` INT(11) NOT NULL DEFAULT 0 COMMENT '重试次数',
    `fail_reason` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '失败原因',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_wechat_id_friend_id` (`wechat_id`, `friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信待办表';

DROP TABLE IF EXISTS `la_ai_wechat_robot`;
CREATE TABLE `la_ai_wechat_robot` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `logo` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '机器人logo',
    `name` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '机器人名称',
    `description` TEXT  NULL COMMENT '机器人描述指令',
    `company_background` TEXT  NULL COMMENT '公司背景',
    `question` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '问题',
    `answer` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '回答',
    `create_time` INT(11) DEFAULT NULL COMMENT '创建时间',
    `update_time` INT(11) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信机器人表';

