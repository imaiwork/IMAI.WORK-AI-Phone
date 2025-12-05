-- 知识库
-- 知识库
-- 创建知识库表
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

-- 知识库绑定
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

-- 知识库文档表
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


-- 知识库文档切片表
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


-- 知识库检索表
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

-- 知识库检索结果表
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

-- 知识库使用场景
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

-- 知识库使用记录表
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


-- 数字人
UPDATE `la_config`
SET `type` = 'model',
    `name` = 'list',
    `value` = '[{\"id\":\"1\",\"name\":\"标准版\",\"status\":\"0\"},{\"id\":\"2\",\"name\":\"极致版\",\"status\":\"1\"},{\"id\":\"4\",\"name\":\"高级版\",\"status\":\"1\"},{\"id\":\"6\",\"name\":\"尊享版\",\"status\":\"1\"}]',
    `create_time` = 1730688127,
    `update_time` = 1744269569
WHERE
    `id` = 4;




-- 知识库定时
INSERT INTO `la_dev_crontab` (`id`, `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES (7, '知识库获取任务状态', 1, 0, '', 'file_status_cron', '', 1, '* * * * *', '', NULL, '0.01', '12', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`id`, `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES (8, '知识库文档切片拉取', 1, 0, '', 'file_chunks_pull_cron', '', 1, '* * * * *', '', NULL, '0', '45.14', 1744881498, 1744881498, NULL);

-- 知识库
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (26, 'knowledge_create', 9005, '次', '知识库创建', 20, '每次创建消耗20算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (27, 'knowledge_chat', 9006, 'tokens/算力', '知识库聊天', 200, '每200字约消耗1算力', 1, 1740799252, 1740799252);


-- 尊享版扣费
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (28, 'human_avatar_ymt', 5014, '算力/次', '数字人形象-尊享版', 0, '（数字人尊享版）每次克隆形象不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (29, 'human_voice_ymt', 5015, '算力/次', '数字人音色-尊享版', 1600, '（数字人尊享版）每次克隆音色约消耗1600算力，若使用已有音色则不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (30, 'human_audio_ymt', 5016, '算力/秒', '数字人音频-尊享版', 1, '（数字人尊享版）每次合成音频时，1秒约消耗1算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (31, 'human_video_ymt', 5017, '算力/秒', '数字人视频合成-尊享版', 1, '（数字人尊享版）每次合成视频时，1秒约消耗1算力', 1, 1740799252, 1740799252);



-- 新增知识库菜单
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (362, 195, 'M', '知识库', '', 100, '', 'knowledge', '', '', '', 0, 1, 0, 1745498578, 1745498595);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (363, 362, 'C', '知识库列表', '', 0, '', 'lists', 'ai_application/knowledge_base/lists', '', '', 0, 1, 0, 1745498678, 1745498678);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (364, 363, 'A', '删除', '', 0, 'ai_application.knowledge/delete', '', '', '', '', 0, 1, 0, 1745498738, 1745498738);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (365, 362, 'C', '文件列表', '', 0, 'ai_application.kn/files', 'files', 'ai_application/knowledge_base/files', '', '', 0, 1, 0, 1745498795, 1745598855);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (366, 365, 'A', '删除', '', 0, 'ai_application.files/delete', '', '', '', '', 0, 1, 0, 1745498812, 1745498812);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (367, 119, 'A', '重置密码', '', 0, 'user.user/editPas', '', '', '', '', 0, 1, 0, 1745577870, 1745577870);

ALTER TABLE `la_human_video_task` 
MODIFY COLUMN `msg` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文字' ;
