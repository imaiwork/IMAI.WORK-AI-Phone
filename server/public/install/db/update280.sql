CREATE TABLE IF NOT EXISTS `la_storyboard_video_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`result_id` varchar(255) DEFAULT '' COMMENT '阿里云生成的任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0草稿箱,1待处理,2生成中,3已完成,4失败,5部分完成',
`total_duration` smallint(6) DEFAULT NULL COMMENT '生成视频总时长',
`video_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '视频数量',
`type` tinyint(3) unsigned DEFAULT '1' COMMENT '视频类型:1 全局口播 2 分组口播',
`input_config` text COMMENT '输入,json',
`output_config` text COMMENT '输出,json',
`editing_config` text COMMENT '编辑,json',
`clip` text COMMENT '剪辑风格,json',
`extra` text COMMENT '附加字段内容,json',
`success_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
`error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分镜混剪视频设置表';

CREATE TABLE IF NOT EXISTS `la_storyboard_video_task` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`video_setting_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频设置id',
`type` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '视频类型:1 全局口播 2 分组口播',
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
`title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
`subtitle` varchar(500) NOT NULL DEFAULT '' COMMENT '副标题',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0待处理,1视频查询,2视频合成失败,3视频合成成功',
`duration` smallint(6) DEFAULT NULL COMMENT '生成视频时长',
`msg` text COMMENT '文案',
`width` varchar(10) DEFAULT '' COMMENT '宽',
`height` varchar(10) DEFAULT '' COMMENT '高',
`poi` varchar(100) NOT NULL DEFAULT '' COMMENT '位置信息',
`result_id` varchar(255) NOT NULL DEFAULT '' COMMENT '生成的视频id',
`video_result_url` text COMMENT '生成的视频地址',
`clip_type` tinyint(4) DEFAULT '1' COMMENT '剪辑风格 1:Ai智能推荐,2:科技风格,3:生活风格,4:营销风格,5:知识科普风格, 6:综艺风格',
`video_token` varchar(10) NOT NULL DEFAULT '0' COMMENT '视频扣费',
`extra` text COMMENT '附加字段内容,json',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`remark` varchar(255) DEFAULT '' COMMENT '失败原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分镜混剪视频合成任务表';


ALTER TABLE `la_shanjian_clip_template` 
ADD COLUMN `auto_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '自动化0否1是';

UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6846b24c1b0f5b0030239440' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6846b4e41b0f5b0030239ae5' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6847cfb9a6b684003173a2fb' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '684a7a1ad7c4ca003251c605' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6853cdbd0c508d0030268a8d' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6853cdd95440e50031367e4b' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6853e63e1e7b320034fa1d9f' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6863a624cbf589003866ed09' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6880433948505d00319b5299' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6880b5974761d400308c15a5' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '688212fe6de2ca00302da9e9' and `scene` =  'newsMixCutting'; 
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6882132248505d00319bec20' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6886e4d54894d300312c014c' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6886ea2648505d00319d21b0' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6886ea414894d300312c0449' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68872e4a48505d00319d3e26' and `scene` =  'newsMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68be53a18dad360031c30125' and `scene` =  'newsMixCutting';

UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b568321fc68f0030bc2133' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b800a17d86fd0030f969ff' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b535685fd04a002f002bfc' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68aec28c6028800031de5ec6' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68639acd0353060030b4d2a6' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '685529bb2b624a0030c84300' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '685518926ee6cf0031425f0f' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '685500add7c4ca00325301f3' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6854d903a6b68400317528ce' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '684be6e0f4c3530030d4792e' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '684bc9c21b0f5b00302b69f9' and `scene` =  'oralMixCutting';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '684261f814aa6c00308399e7' and `scene` =  'oralMixCutting';


CREATE TABLE IF NOT EXISTS `la_sv_lead_scraping_filter_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `filter` json DEFAULT NULL COMMENT '评论词筛选',
  `comment_speech` json DEFAULT NULL COMMENT '评论话术',
  `msg_speech` json DEFAULT NULL COMMENT '私信话术',
  `mark_filter` json DEFAULT NULL COMMENT '留痕筛选',
  `mark_speech` json DEFAULT NULL COMMENT '留痕话术',
  `number` int(11) DEFAULT '1' COMMENT '数量',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='截流获客筛选词话术历史表';

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('分镜混剪视频生成', 1, 0, '', 'storyboard_video_task', '', 1, '* * * * * ', NULL, 1747896903, '0', '0', 1744881498, 1744881498, NULL);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('storyboard_video_create', 10300, '算力/分钟', '分镜混剪', 20.00, '', 1, 1740799252, 1740799252);

UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '682d4ab281fc800038ace423' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '682d4baa01d7b50031341642' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '682edf4681fc800038acf69c' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '682ee05995206500312cffbd' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68342c3e4275200065cccc90' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68356ac75e3ec40066a33875' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6836b4f1cbab400030062e8b' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6836ca78cbab4000300668ef' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6836d1554275200065ccf969' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '683819b9cd336f005cb7f813' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6842b43414aa6c003083ab41' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6846ada8b3d336003003a657' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6847da4764fc05003246ce94' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68496071f4c3530030d42bcd' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6853770bf4c3530030d54ea1' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6876111d43c4a1003860cffe' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68786818c33a110031e3727a' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '688c612e00d042003017e131' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '688c70e77b75e500326f1dde' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '6891cbec39bd0400384859cc' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '689c567f4eaadd0030c154f8' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '689d8c6165fbf10039b153fe' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '689da586b27a9900310b74fb' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68a4415598ee6e0031d33c63' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68a54620b5b5a80030c71213' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68a595bfb5b5a80030c73a2d' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68a5b05641e77f0037c555d7' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68a6f9bb46591d00311c2ab3' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68a704bc46591d00311c3205' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68afc7e18cb1b40031e28ef3' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b1891a6028800031df742d' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b589a51a8e920031ee7f8b' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b6c63f49c0ae002fbf2dec' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b6d13accd8300033ca0c54' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68b93eb6a02ae2003036d063' and `scene` =  'virtualman';
UPDATE `la_shanjian_clip_template` SET `auto_type` = 1 WHERE `id` = '68bab05f49c0ae002fc0caec' and `scene` =  'virtualman';

UPDATE `la_config` SET `value` = '[{"id":"1","name":"数字人纯口播视频","image":"static/images/dh/dh1.jpg","video_case_url":"static/videos/dh/dh1.mp4"},{"id":"2","name":"数字人口播混剪","image":"static/images/dh/dh2.jpg","video_case_url":"static/videos/dh/dh2.mp4"},{"id":"3","name":"真人口播视频智剪","image":"static/images/dh/dh3.jpg","video_case_url":"static/videos/dh/dh3.mp4"},{"id":"4","name":"素材混剪神器","image":"static/images/dh/dh4.jpg","video_case_url":"static/videos/dh/dh4.mp4"},{"id":"5","name":"新闻体视频","image":"static/images/dh/dh5.jpg","video_case_url":"static/videos/dh/dh5.mp4"},{"id":"6","name":"一句话生成视频","image":"static/images/dh/dh6.jpg","video_case_url":"static/videos/dh/dh6.mp4"},{"id":"7","name":"分镜混剪生成视频","image":"static/images/dh/dh7.jpg","video_case_url":"static/videos/dh/dh7.mp4"}]' WHERE `name` = 'video_case' AND `type` = 'digital_human';