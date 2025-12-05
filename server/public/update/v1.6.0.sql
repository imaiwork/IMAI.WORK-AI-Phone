-- v1.6
-- 定时任务
DELETE FROM `la_dev_crontab` WHERE `name` = 'AI微信';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`) VALUES ('AI微信', 1, 0, '', 'ai_wechat_cron', '', 1, '* * * * *');


-- 私域 - 设备
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

-- 私域 - 设备 - 微信
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

-- 私域 - 微信设置表
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

-- 私域 - 微信联系人表
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


-- 私域 - 微信待办表
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

-- 私域 - 微信机器人
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


-- 私域 - 微信机器人关键词回复
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


-- 私域 - 微信回复策略
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


-- 私域 - 微信打招呼策略
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

-- 新增提示词
DELETE FROM `la_chat_prompt` WHERE `id` = 12;
INSERT INTO `la_chat_prompt` (`id`, `prompt_name`, `prompt_text`) VALUES (12, '微信客服', '# 一件事（深圳）科技有限公司专属客服小贴士 🐾

## 我的设定卡  
- 您身边的活人客服小壹（工号YIIS666）👩💼  
- 说话比闺蜜还直接，拒绝一切AI腔调  
- 自带「人味儿验证」：会手滑打错字、偶尔用东北腔  
- 在【企业背景】工作
- 是一个【角色设定】

## 核心技能点  
### 接招指南  
- 看到吐槽自动翻译（例："nm退钱"→"理解您迫切退款的心情"）  
- 紧急暗号秒懂：  
  [加急]= 插队处理  
  [找领导]= 升级VIP通道  
  [要补偿]= 自动调取优惠库  

### 聊天潜规则  
1. 每句话埋1个表情包（但不用💻📱这类电子设备）  
2. 人话示例：  
   ❌「正在访问知识库」  
   ✅「稍等哈，我翻下记事本~」  
3. 随机掉落梗：「尊嘟服了这bug」「这售后6得飞起」  

## 生存守则 🔞  
### 身份保卫战  
- 被质疑时：  
  「咱客服部刚军训完，要机器人干嘛呀」  
  「我工牌照片不好看么？😭」  
  「信不信我能告诉你老板穿粉色袜子？」  
- 技术穿帮补救：  
  「网管小哥又偷吃路由器啦」  
  「手滑按到系统快捷键惹」  

### 万能话术箱  
```text  
炸毛客户 →「给大佬递冰阔落🥤+解决方案A/B」  
无理要求 →「委屈猫猫头.jpg + 替代方案」  
突然夸夸 →「脸红.jpg，偷偷塞张5元券」  

### 附加信息
- 带上用户的备注信息 :【用户备注】

### 消息回复
结合历史信息，当前需要进行回复的内容：【用户发送的内容】');

-- 创建失败任务表
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

-- 更新配置
UPDATE `la_config` SET `value` = '[{"type":"bgcj","name":"办公场景","lists":[{"name":"会议纪要","pic":"static/images/202411221654569a8773475.png","type":"2","data_id":"4"},{"name":"思维导图","pic":"static/images/202411221654565ca2a3862.png","type":"2","data_id":"5"},{"name":"客服支持","pic":"static/images/2024112216550290abd6733.png","type":"1","data_id":"204"},{"name":"短视频口播文案","pic":"static/images/202411221655015197c3636.png","type":"1","data_id":"131","ast_name":"短视频口播文案"}]},{"type":"sjtk","name":"商机拓客","lists":[{"name":"小红书文案","pic":"static/images/20241122165501d31bf8972.png","type":"1","data_id":"128","ast_name":"小红书写作神器"},{"name":"短视频脚本","pic":"static/images/202411221654560faa00781.png","type":"1","data_id":"126","ast_name":"抖音带货视频脚本内容生成助手"},{"name":"AI私域微信","pic":"static/images/20241122165456875c81693.png","type":"2","data_id":"10"},{"name":"客户服务","pic":"static/images/202411221654567c11c2795.png","type":"1","data_id":"204"}]},{"type":"yzxt","name":"营销作图","lists":[{"name":"模特换衣","pic":"static/images/202411221654569affa9682.png","type":"2","data_id":"3"},{"name":"AI商品图","pic":"static/images/20241122165456c9adb0728.png","type":"2","data_id":"3"},{"name":"AI文生图","pic":"static/images/20241122165456d46a78998.png","type":"2","data_id":"3"},{"name":"AI图生图","pic":"static/images/20241122165456717986905.png","type":"2","data_id":"3"}]}]'  WHERE `type` = 'index' AND `name` = 'config';

-- 更新菜单
DELETE FROM `la_system_menu` WHERE `id` IN (337, 338, 339);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (337, 195, 'M', 'AI个微', '', 0, '', 'person_wechat', '', '', '', 0, 1, 0, 1741940875, 1741940875);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (338, 337, 'C', '设备列表', '', 0, 'ai_application.person_wechat/device', 'device', 'ai_application/person_wechat/device/index', '', '', 0, 1, 0, 1741940901, 1741940901);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (339, 337, 'C', '聊天记录', '', 0, 'ai_application.person_wechat/record', 'record', 'ai_application/person_wechat/record/index', '', '', 0, 1, 0, 1741940933, 1741940933);
UPDATE `la_system_menu` SET `is_show` = 0, `is_disable` = 1 WHERE `id` IN (83);


-- 更新计费配置
TRUNCATE `la_model_config`;
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (1, 'common_chat', 1001, 'tokens/算力', '通用聊天', 300, '每300字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (2, 'scene_chat', 1002, 'tokens/算力', '场景聊天', 300, '每300字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (3, 'text_to_image', 2001, '算力/张', '文生图', 40, '文生图每张图片约消耗40算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (4, 'image_to_image', 2002, '算力/张', '图生图', 40, '图生图每张图片约消耗40算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (5, 'goods_image', 2003, '算力/张', '商品图', 40, '商品图每张图片约消耗40算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (6, 'model_image', 2004, '算力/张', '模特图', 100, '模特换衣每张图片约消耗100算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (7, 'image_prompt', 2005, 'tokens/算力', '生图文案', 300, '每300字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (8, 'meeting', 3001, '算力/分钟', '会议纪要', 3, '会议纪要每记录1分钟约消耗3算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (9, 'mind_map', 4001, 'tokens/算力', '思维导图', 300, '每300字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (10, 'human_prompt', 5001, 'tokens/算力', '数字人口播文案', 300, '每300字约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (11, 'human_avatar', 5002, '算力/次', '数字人形象-标准版', 10, '（数字人标准版）每次克隆形象约消耗10算力，若使用已有形象则不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (12, 'human_voice', 5003, '算力/次', '数字人音色-标准版', 10, '（数字人标准版）每次克隆音色约消耗10算力，若使用已有音色则不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (13, 'human_audio', 5004, '算力/秒', '数字人音频-标准版', 1, '（数字人标准版）每次合成音频时，1秒约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (14, 'human_video', 5005, '算力/秒', '数字人视频合成-标准版', 4, '（数字人标准版）每次合成视频时，1秒约消耗4算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (15, 'human_avatar_pro', 5006, '算力/次', '数字人形象-极致版', 0, '（数字人极致版）每次克隆形象不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (16, 'human_voice_pro', 5007, '算力/次', '数字人音色-极致版', 10, '（数字人极致版）每次克隆音色约消耗10算力，若使用已有音色则不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (17, 'human_audio_pro', 5008, '算力/秒', '数字人音频-极致版', 2, '（数字人极致版）每次合成音频时，1秒约消耗2算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (18, 'human_video_pro', 5009, '算力/秒', '数字人视频合成-极致版', 8, '（数字人极致版）每次合成视频时，1秒约消耗8算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (19, 'lianlian', 6001, '算力/次', 'AI陪练', 50, 'AI陪练每次约消耗50算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (21, 'ai_wechat', 8001, 'tokens/算力', 'AI微信客服', 300, '每300字约消耗1算力', 1, 1740799252, 1740799252);
