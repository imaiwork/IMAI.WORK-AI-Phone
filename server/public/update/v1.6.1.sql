-- 新增面试表
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

-- 新增面试高级设置表
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

-- 新增面试简历表
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

-- 新增面试对话记录表
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

-- 新增面试反馈表
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

-- 新增面试岗位表
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

-- 新增面试记录表
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





-- 更新菜单
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (349, 195, 'M', 'AI面试', '', 0, '', 'interview', '', '', '', 0, 1, 0, 1741080757, 1741080757);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (350, 349, 'M', '岗位管理', '', 0, 'ai_application.interview/job', 'job', 'ai_application/interview/job/index', '', '', 0, 1, 0, 1741081000, 1741081195);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (352, 350, 'C', '岗位列表', '', 0, 'ai_application.interview.job/lists', 'lists', 'ai_application/interview/job/index', '', '', 0, 1, 0, 1741081232, 1741081993);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (353, 352, 'A', '详情', '', 0, 'ai_application.interview.job/detail', '', '', '', '', 0, 1, 0, 1741081278, 1741081278);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (354, 352, 'A', '删除', '', 0, 'ai_application.interview.job/del', '', '', '', '', 0, 1, 0, 1741081351, 1741081351);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (355, 350, 'C', '岗位详情', '', 0, 'ai_application.interview.job/detail', 'detail', 'ai_application/interview/job/detail', '/ai_application/interview/job/lists', '', 0, 0, 0, 1741081441, 1741419452);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (356, 349, 'C', '面试记录', '', 0, 'ai_application.interview/record', 'record', 'ai_application/interview/record/index', '', '', 0, 1, 0, 1741081608, 1741081712);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (357, 349, 'C', '面试记录详情', '', 0, 'ai_application.interview.record/detail', 'record_detail', 'ai_application/interview/record/detail', '/ai_application/interview/record', '', 0, 0, 0, 1741081681, 1741081681);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (358, 356, 'A', '详情', '', 0, 'ai_application.interview.record/detail', '', '', '', '', 0, 1, 0, 1741081735, 1741081735);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (359, 356, 'A', '删除', '', 0, 'ai_application.interview.record/del', '', '', '', '', 0, 1, 0, 1741081755, 1741081755);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (361, 349, 'C', '面试反馈', '', 0, 'ai_application.interview.feedback', 'feedback', 'ai_application/interview/feedback/index', '', '', 0, 1, 0, 1741081821, 1741081821);


-- 新增模型配置
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (20, 'interview_chat', 7001, '算力/次', 'Ai面试-岗位', 200, '该功能模块应用在本站小程序端中的AI人事功能中，每当面试者开始面试时候，都将进行当前一次性的固定费用扣除', 1, NULL, NULL);

--标题配置插入
INSERT INTO`la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('website', 'pc_home_title', 'AI时代，企业化AI工具的新星', 1743509615, 1743509615);

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



--更新配置
UPDATE `la_config`
SET `type` = 'index',
`name` = 'config',
`value` = '[{\"type\":\"bgcj\",\"name\":\"办公场景\",\"lists\":[{\"name\":\"会议纪要\",\"pic\":\"https://demo.imai.work/static/images/202411221654569a8773475.png\",\"type\":\"2\",\"data_id\":\"4\",\"ast_name\":\"meeting_minutes\"},{\"name\":\"思维导图\",\"pic\":\"https://demo.imai.work/static/images/202411221654565ca2a3862.png\",\"type\":\"2\",\"data_id\":\"5\",\"ast_name\":\"mind_map\"},{\"name\":\"客服支持\",\"pic\":\"https://demo.imai.work/static/images/2024112216550290abd6733.png\",\"type\":\"1\",\"data_id\":\"204\"},{\"name\":\"短视频口播文案\",\"pic\":\"https://demo.imai.work/static/images/202411221655015197c3636.png\",\"type\":\"1\",\"data_id\":\"131\",\"ast_name\":\"短视频口播文案\"}]},{\"type\":\"sjtk\",\"name\":\"商机拓客\",\"lists\":[{\"name\":\"小红书文案\",\"pic\":\"https://demo.imai.work/static/images/20241122165501d31bf8972.png\",\"type\":\"1\",\"data_id\":\"128\",\"ast_name\":\"小红书写作神器\"},{\"name\":\"短视频脚本\",\"pic\":\"https://demo.imai.work/static/images/202411221654560faa00781.png\",\"type\":\"1\",\"data_id\":\"126\",\"ast_name\":\"抖音带货视频脚本内容生成助手\"},{\"name\":\"AI私域微信\",\"pic\":\"https://demo.imai.work/static/images/20241122165456875c81693.png\",\"type\":\"2\",\"data_id\":\"10\",\"ast_name\":\"pw_marketing\"},{\"name\":\"客户服务\",\"pic\":\"https://demo.imai.work/static/images/202411221654567c11c2795.png\",\"type\":\"1\",\"data_id\":\"204\"}]},{\"type\":\"yzxt\",\"name\":\"营销作图\",\"lists\":[{\"name\":\"模特换衣\",\"pic\":\"https://demo.imai.work/static/images/202411221654569affa9682.png\",\"type\":\"2\",\"data_id\":\"3\",\"ast_name\":\"drawing\"},{\"name\":\"AI商品图\",\"pic\":\"https://demo.imai.work/static/images/20241122165456c9adb0728.png\",\"type\":\"2\",\"data_id\":\"3\",\"ast_name\":\"drawing\"},{\"name\":\"AI文生图\",\"pic\":\"https://demo.imai.work/static/images/20241122165456d46a78998.png\",\"type\":\"2\",\"data_id\":\"3\",\"ast_name\":\"drawing\"},{\"name\":\"AI图生图\",\"pic\":\"https://demo.imai.work/static/images/20241122165456717986905.png\",\"type\":\"2\",\"data_id\":\"3\",\"ast_name\":\"drawing\"}]}]',
`create_time` = 1730688127,
`update_time` = 1743643118 
WHERE
	`id` = 5;


  --登录注册 这个菜单隐藏、停用
  UPDATE `la_system_menu`
SET `pid` = 112,
`type` = 'C',
`name` = '登录注册',
`icon` = '',
`sort` = 0,
`perms` = 'setting.user.user/getRegisterConfig',
`paths` = 'login_register',
`component` = 'setting/user/login_register',
`selected` = '',
`params` = '',
`is_cache` = 0,
`is_show` = 0,
`is_disable` = 1,
`create_time` = 1663903832,
`update_time` = 1743643626 
WHERE
	`id` = 115;