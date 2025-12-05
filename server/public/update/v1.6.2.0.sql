--数字人
UPDATE `la_config`
SET `type` = 'model',
    `name` = 'list',
    `value` = '[{\"id\":\"1\",\"name\":\"标准版\",\"status\":\"1\"},{\"id\":\"2\",\"name\":\"极致版\",\"status\":\"1\"},{\"id\":\"4\",\"name\":\"高级版\",\"status\":\"1\"}]',
    `create_time` = 1730688127,
    `update_time` = 1744269569
WHERE
    `id` = 4;



-- 高级定时任务
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



--高级版扣费
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (22, 'human_video_ym', 5013, '算力/秒', '数字人视频合成-高级版', 2, '（数字人高级版）每次合成视频时，1秒约消耗2算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (23, 'human_avatar_ym', 5010, '算力/次', '数字人形象-高级版', 0, '（数字人高级版）每次克隆形象不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (24, 'human_voice_ym', 5011, '算力/次', '数字人音色-高级版', 1000, '（数字人高级版）每次克隆音色约消耗1000算力，若使用已有音色则不消耗算力', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (25, 'human_audio_ym', 5012, '算力/秒', '数字人音频-高级版', 1, '（数字人高级版）每次合成音频时，1秒约消耗1算力', 1, 1740799252, 1740799252);



--菜单更新
UPDATE `la_system_menu` SET `pid` = 166, `type` = 'M', `name` = '算力管理', `icon` = '', `sort` = 1, `perms` = '', `paths` = 'marketing', `component` = '', `selected` = '', `params` = '', `is_cache` = 0, `is_show` = 1, `is_disable` = 0, `create_time` = 1722648653, `update_time` = 1735872285 WHERE `id` = 231;



UPDATE `la_model_config` SET `scene` = 'human_avatar', `code` = 5002, `unit` = '算力/次', `name` = '数字人形象-标准版', `score` = 0, `description` = '（数字人标准版）每次克隆形象，不消耗算力', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 11;
UPDATE `la_model_config` SET `scene` = 'human_voice', `code` = 5003, `unit` = '算力/次', `name` = '数字人音色-标准版', `score` = 0, `description` = '（数字人标准版）每次克隆音色，不消耗算力', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 12;
UPDATE `la_model_config` SET `scene` = 'human_audio', `code` = 5004, `unit` = '算力/秒', `name` = '数字人音频-标准版', `score` = 0, `description` = '（数字人标准版）每次合成音频时，不消耗算力。', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 13;
UPDATE `la_model_config` SET `scene` = 'human_video', `code` = 5005, `unit` = '算力/秒', `name` = '数字人视频合成-标准版', `score` = 2, `description` = '（数字人标准版）每次合成视频时，1秒约消耗2算力', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 14;
UPDATE `la_model_config` SET `scene` = 'human_avatar_pro', `code` = 5006, `unit` = '算力/次', `name` = '数字人形象-极致版', `score` = 0, `description` = '（数字人极致版）每次克隆形象，不消耗算力', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 15;
UPDATE `la_model_config` SET `scene` = 'human_voice_pro', `code` = 5007, `unit` = '算力/次', `name` = '数字人音色-极致版', `score` = 0, `description` = '（数字人极致版）每次克隆音色，不消耗算力', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 16;
UPDATE `la_model_config` SET `scene` = 'human_audio_pro', `code` = 5008, `unit` = '算力/秒', `name` = '数字人音频-极致版', `score` = 1, `description` = '（数字人极致版）每次合成音频时，1秒约消耗1算力', `status` = 1, `create_time` = 1740799252, `update_time` = 1740799252 WHERE `id` = 17;

--数字人文案
UPDATE `la_chat_prompt` SET `prompt_name` = '数字人', `prompt_text` = '角色：你是一名即兴口播文案生成器，擅长将任意内容转化为紧凑型口语化脚本\n\n约束：\n1. 无视输入逻辑性，强制提取传播价值点\n2. 输出严格控制在120-150字区间\n3. 自动补充：\n   - 场景化开场白\n   - 情绪递进节奏\n   - 记忆点设计\n   - 行动号召语\n4. 禁止使用专业术语，保持市井化表达\n\n处理流程：\n1. 概念提取 → 2. 情绪建模 → 3. 话术重构 → 4. 口语优化' WHERE `id` = 1;

