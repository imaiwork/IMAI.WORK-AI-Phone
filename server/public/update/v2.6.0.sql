CREATE TABLE IF NOT EXISTS `la_sora_video_task` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`type` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '视频类型:3小红书',
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
`title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
`subtitle` varchar(500) NOT NULL DEFAULT '' COMMENT '副标题',
`speed` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '视频合成速度类型:0闲时,1普通,2插队',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`gender` varchar(50) NOT NULL DEFAULT '' COMMENT '性别-male,female',
`model_version` int(11) NOT NULL DEFAULT '1' COMMENT '模型类型 1：标准 2: 极速',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0待处理,1视频查询,2视频合成失败,3视频合成成功',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`video_setting_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频设置id',
`topic` varchar(200) NOT NULL DEFAULT '' COMMENT '话题',
`poi` varchar(100) NOT NULL DEFAULT '' COMMENT '位置信息',
`result_id` varchar(255) NOT NULL DEFAULT '' COMMENT '生成的视频id',
`video_result_url` text COMMENT '生成的视频地址',
`clip_type` tinyint(4) DEFAULT '1' COMMENT '剪辑风格 1:Ai智能推荐,2:科技风格,3:生活风格,4:营销风格,5:知识科普风格, 6:综艺风格',
`video_token` varchar(10) NOT NULL DEFAULT '0' COMMENT '视频扣费',
`clip_token` varchar(10) DEFAULT '0' COMMENT '剪辑扣费',
`extra` text COMMENT '附加字段内容,json',
`clip_result_url` varchar(255) DEFAULT NULL COMMENT '剪辑后的视频地址',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`automatic_clip` tinyint(4) unsigned DEFAULT '0' COMMENT '自动剪辑 0不剪,1剪辑',
`remark` varchar(255) DEFAULT '' COMMENT '失败原因',
`clip_status` tinyint(4) unsigned DEFAULT '1' COMMENT '剪辑状态 1待剪辑,2剪辑中,3成功,4失败',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
`width` varchar(10) DEFAULT '' COMMENT '宽',
`height` varchar(10) DEFAULT '' COMMENT '高',
`ai_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Ai创作 0原本',
`duration` tinyint(4) DEFAULT '10' COMMENT '生成视频时长 10s 15s',
`msg` text COMMENT '文案',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='视频合成任务表';

CREATE TABLE IF NOT EXISTS `la_sora_video_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0草稿箱,1待处理,2生成中,3已完成,4失败,5部分完成',
`video_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '视频数量',
`type` tinyint(3) unsigned DEFAULT '3' COMMENT '视频类型:3小红书',
`speed` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '视频合成速度类型:0闲时,1普通,2插队',
`copywriting` text COMMENT '文案,json',
`clip` text COMMENT '剪辑风格,json',
`model_version` int(11) NOT NULL DEFAULT '1' COMMENT '模型类型 1：标准 2: 极速',
`automatic_clip` tinyint(1) NOT NULL DEFAULT '0' COMMENT '自动剪辑 0不剪,1剪辑',
`extra` text COMMENT '附加字段内容,json',
`success_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
`error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
`ai_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Ai创作 0原本',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='视频设置表';

ALTER TABLE  `la_sv_account_contact`
MODIFY COLUMN `source` int(11) NOT NULL DEFAULT 0 COMMENT '好友来源 0：未知 1: QQ号 3: 微信号 4|12: QQ好友 8|14: 群聊 10|13: 手机通讯录 15: 手机号 17: 名片 18：附近的人 22|23|24|26|27|28|29：摇一摇 25： 漂流瓶 30：扫一扫 34：公众号 48：雷达 60小红书70抖音80快手' AFTER `desc`;


ALTER TABLE  `la_shanjian_video_setting`
    ADD COLUMN `shanjian_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型1数字人口播2真人口播,3素材,4新闻体';

ALTER TABLE  `la_shanjian_video_task`
    ADD COLUMN `shanjian_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型1数字人口播2真人口播,3素材,4新闻体',
     MODIFY COLUMN `anchor_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '形象id';

DELETE FROM  `la_model_config` WHERE `scene` = 'shanjian_realman_broadcast';
DELETE FROM  `la_model_config` WHERE `scene` = 'shanjian_broadcast_mixcut';
DELETE FROM  `la_model_config` WHERE `scene` = 'shanjian_news_mixcut';
DELETE FROM  `la_model_config` WHERE `scene` = 'news_mixcut_title';
DELETE FROM  `la_model_config` WHERE `scene` = 'combined_picture_title';
DELETE FROM  `la_model_config` WHERE `scene` = 'combined_picture';
DELETE FROM  `la_model_config` WHERE `scene` = 'human_copywriting';


INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'human_copywriting', 5018, '算力/条', '数字人文案', 1.00,  1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'shanjian_realman_broadcast', 5033, '算力/秒',  'AI智能真人口播混剪视频合成',2.00, 1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'shanjian_broadcast_mixcut', 5034, '算力/秒', 'AI智能素材混剪视频合成',2.00, 1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'shanjian_news_mixcut', 5035, '算力/秒', 'AI智能新闻体混剪视频合成',2.00, 1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'news_mixcut_title', 10200, '算力/条', '智能新闻体标题生成', 1.00, 1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'combined_picture_title', 10201, '算力/条', '小红书图片合成封面标题内容生成', 1.00, 1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'combined_picture', 10202, '算力/张', '小红书图片自动合成', 1.00, 1, 1740799252, 1740799252);
INSERT INTO  `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`,  `status`, `create_time`, `update_time`) VALUES ( 'sora_video_create', 10106, '算力/秒', 'AI智能一句话生成视频', 1.00, 1, 1740799252, 1740799252);





DELETE FROM `la_shanjian_clip_template` WHERE `scene` = 'realMan';

INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec0b', '高级橙', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/17/f7af40b4a3db095d1e6d9528f8260f25.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/17/3df4143976687f5bff0d08ea091a9731.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec0d', '橙色大标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/10/bf9a23170cf35ef93bf56408ba6d0930.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/10/8782f00d3c3599d2012a3808d32f51e9.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec0e', '半透简黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/10/13bdeab766a457647ffa53f820faf70f.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/11/682fc05764c9bcd96a340eea7235df57.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec0f', '黄色大标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/04/01/11/897290cca53344cb6f32723449b3f509.jpg', 'realMan', '', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec10', '黄白IP', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/17/9c88dbc85ec6fa2072b706d7ea9fad2e.png', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/17/ba756b255f8e61c22ebbc4195251085b.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec11', '通用门店', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/09/1b4528e66cdd06697e8522c50f63c9a8.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/09/276587d1e37836a8e2056336e1608b6a.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec12', '醒目标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/04/24/14/c3bb2fb8f339bbc35111d8ea7c072888.png', 'realMan', '', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec14', '蓝底商业', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/04/29/18/671741f62611e27fe008a82b3112c6b6.png', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/04/29/18/dd9d20d6df7cdd306eda4ff97b1e1430.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6858cef34e178cd82c01ec16', '金融黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/03/18/17/0c19930b8773d2acf2ec83d93d0828db.jpg', 'realMan', '', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6879b376bf49c00033b0d9b7', '纯白双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/10/3124cae9e2a6af1720f7f27e61593eb5.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/10/63ef3b270cba478061d4722563d57233.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6879b3e09afb52003109c4ce', '红白双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/10/e19a02ee01511ef3a683c3dd58130c9d.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/10/484a5facb018944a15857eb100398915.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6879b40dbf49c00033b0d9dc', '黄白双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/10/92e328d79808ff848a18d750611f986b.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/10/8538f2c8fc78b45087d294f1265879f5.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6879e391d13c7f0031567868', '简约双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/14/6374d1697971b133eeb56cb7cac4bc52.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/14/6fb4e247d15b1785a3419661745d9472.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68ad2d77550be900397ad674', '真人口播（动态字幕）', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/26/11/8aaf06bfff5bea5e7da18aab9fecea82.png', 'realMan', '', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c240378dad360031c49acb', '经典黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/d17bf33ecbc4e11a87d95e265014fc4f.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/f3a104977edc769dd1f3cff2772f79d9.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c240cba02ae200303a0a67', '深蓝三屏', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/5686098583d83db003f37b3b1e22ad88.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/42166fbd5d756e7dafacc3b0183d4c9c.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2410da02ae200303a0aa7', '重点橙红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/77c6338c33aa4e19182d9e272204ad01.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/eb4042907536e78b6793ee5a6006de51.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2410f49c0ae002fc35d53', '通用粉黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/18a2d2b8e7c2e007ec52e87aaf3d5fcb.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/5f352f98ed57a9a3698d0d46a821a313.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2417faf3b80003297aa3c', '简易黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/24da4974d807b748850db0c32fb70385.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/14d78f8339a2c54829420618d9624f5c.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c241c07d86fd0030fd34fe', '黄白字幕', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/39894327e2b194cf2702a3fb86987c9c.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/b2b80ed7d92a57ec7a174b836f90f682.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2423849c0ae002fc35e4d', '通用橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/c396d9d3994a52933fe82fa75222577b.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/ab2198a01a14cedb693cfa6b81d5e63b.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2423f49c0ae002fc35e56', '营销橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/76972110f0ac9b1ed2f0e978e0361bdf.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/3527ac138468b7af6fe8f95c90bde33f.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c242797d86fd0030fd358b', '高级黄红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/6d5a9748ab17360aedcecde36de9fb30.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/fc3d2e00d07adb4fd821ccad19feeb95.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c242d98dad360031c49c9f', '情感紫黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/8506798258e37b97a98b0b33262eae15.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/d0ccbdf9128780c93ce71de57d0acab0.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c24365a02ae200303a0c25', '三屏白绿', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/68cb620e6c21009f54025227d48a5311.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/11/05/19/937521703984f1d0e2d1829c24b81559.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c243a75549a00037805dfd', '书法黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/22/17/568c12d41a9e84f1538e829dd422cbe2.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/cc8f8c73a8cd27e847fdc42d0a5cf828.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2482c7d86fd0030fd3a2d', '商务白蓝', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/26e107c01baed47e0bf186606e71708d.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/34c37fe57aeaed2af9e78651d50ed23c.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c24882ccd8300033ce3880', '通用黄红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/355e16c5f7001c155f43bbe2de25a0e5.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/5c2e324808777352f75ac413c5af0ec6.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c248ba1a8e920031f336a4', '书法双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/4c9b91381f7d4049aea3c9685ff7e88b.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/37e74be9a3213281b6dfe93b4666e340.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c248ed8dad360031c4a1be', '黑底双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/10/20/16/e8ba26efd753ef7eea4e0f86fbef31cc.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/11/6577cebf88603af5914720a93582480b.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2709aaf3b80003297c20e', '百搭暖黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/38e33b1b80ad5c0ffd674e1c61fa4085.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/ebdf55a4f4b3acb1dbc84cbf4695e3e2.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c270d57d86fd0030fd4ceb', '百搭蓝白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/b750a26be4755471fe4514d1a92edb53.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/276b0430f4e02db5b6410b011d17629f.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2716f49c0ae002fc376a5', '棕色三屏', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/15/85fdace732b29410caf8498637cbefdc.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/adf29b3e978c5493a889c89c2ab094a0.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c271c7a02ae200303a243a', '情感黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/15/a27ca36c59ce41a1ec5791eab51bfe14.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/131db935b128d38a04c22b1a16f09358.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c272288dad360031c4b570', '重点红白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/15/85ea3fe4ccd6577176bc572ad81f68e0.jpg', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/3c442e0c04bd2a923590377f9e0fe095.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c272cdccd8300033ce4cc4', '高级黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/ae5574ddd65e2a30ffd619217d0ad186.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/3a61e675c34f818620588bd9db5aea69.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c273181a8e920031f34b02', '高级白橙', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/1f4c0d1af671a0e0fe1bb8b11bb5300a.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/24e20b87c1dd6c4090aed5193e61c4bf.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c2734e1a8e920031f34b22', '重点红黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/30c2b3e5e5921b4e33b3e293fc1ca88a.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/879df405639e54624bfe6d6209a237df.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68c273827d86fd0030fd4fb5', '翻转红白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/fe2252cb2698ce3b514548084a71c3ca.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/11/14/2a7e1a1008ce3957a20e658a4ead256f.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68d0c61bbe5c9d0032e8a58e', '可爱彩色', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/22/11/a8e443d471e3433279a9851ced76c691.webp', 'realMan', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/22/11/2b9141614e8ef131c774783c92a9443f.mp4', 0, 0);


DELETE FROM `la_shanjian_clip_template` WHERE `scene` = 'oralMixCutting';

INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684261f814aa6c00308399e7', '爆款混剪', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/10/20/16/5cd642d3d5b4a47b056aa43627d2154a.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/12/18/6d1dc8f7dff149b1ac0e4ecb8dfc6b6f.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684bc9c21b0f5b00302b69f9', '情感口播', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/11/b49a7b75d8a94790def2339576e22122.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/13/14/9cca7f093d1e04336bfc872d46fa11e2.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684be6e0f4c3530030d4792e', '新闻口播', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/28/09/7de0dcfa7dc20f6957eab1e6d72af815.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/28/09/2e6dd97683ab20d40559e7f3ed043c72.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684feb8d64fc05003247b4a1', '每日问候', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/11/d7aa8adf34384d302fcc5776a56d9fd0.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/24/13/29c6bb8c25ba99a13475d48dfd95ff84.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6854d903a6b68400317528ce', '黑金风格', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/11/8247bacc4b93eb95663d3f5c9f9657f8.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/24/15/7a4e7717fd9a8a2a2dad2a3618e16160.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('685500add7c4ca00325301f3', '新闻4', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/28/09/067878cedd8612ed02a2e6e64552a788.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/28/09/a97529c44f68669f0b28edef014cdc8a.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('685518926ee6cf0031425f0f', '科技三屏', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/11/c4926285cc3c8383595a994520670980.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/24/14/15c67750d0b6fb3848644ce5f0a6dded.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('685529bb2b624a0030c84300', '综艺三屏', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/11/65047e7979fb55f1934d87b61691d85f.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/24/16/00022e163b6dbb439d2fc6d88a3bc54d.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68639acd0353060030b4d2a6', '半透黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/11/c58a74ca08b6143d8b08e4d03462865f.jpg', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/01/17/6bafffcee021ffef404985dfe1e4bd52.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('688056fa4761d400308be7e2', '双语励志', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/12/a36d1a6a5a9ca581a53d76b2580ee89a.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/11/0b5bebc900a6166c8e4ad3339b389a33.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68809f2e5d8046003d902c47', '红白翻转大字', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/29/10/295e004898f36c8000d995b4d516a243.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/29/10/582c12b50f9e557c7ca7462f75ed01e6.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68aec28c6028800031de5ec6', '七夕促销', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/27/18/d9497aca51182c2f06f9b748e2d5b84e.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/27/18/34b6fa400a6537a88598d5a2dab4ddba.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b535685fd04a002f002bfc', '黄色双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/10/20/16/230adf9b0fafc9d085fb1693287f2305.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/01/15/43822b653624e33007096c8391b01a0a.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b568321fc68f0030bc2133', '通用黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/01/19/960ebd35220d440f66840ac4e209ee76.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/01/19/39641d667a3541c9c689b4de47240a21.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b800a17d86fd0030f969ff', '分裂黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/10/20/16/e51ac08fba4e82b58ec885b64294056c.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/03/16/0f00e2d088fd5b62d3d08b0143e35694.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68ca88e4be5c9d0032e6b9be', '双层白红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/17/18/d8761915bd77ccf902b69c24234bb4e2.webp', 'oralMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/17/18/9f7e988cbed3bd1aec65d63dc71cbd64.mp4', 0, 0);

DELETE FROM `la_shanjian_clip_template` WHERE `scene` = 'newsMixCutting';
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6846b24c1b0f5b0030239440', '红黄警示', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/13/b5ee6cff727dc2f7b27183987672bb07.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/25/16/6bbfc8fd749e2ee79f69e9db3378b79c.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6846b4e41b0f5b0030239ae5', '红黄黑框', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/13/df1cbc1ad26865cf6b082ab7d14864c6.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/12/10/56a003bedd538a5d108a652bdfec29d9.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6847cfb9a6b684003173a2fb', '黄色头条', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/17/51919ca7da1864abcf3db91ea4426956.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/17/96ba72502147dfa94c356a5a4f1ae5f6.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684a7a1ad7c4ca003251c605', '蓝框热点', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/13/78af3517caafd367846d0572faa7800d.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/12/15/715c32b3bd01b712990b0d646a0a2b13.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6853cdbd0c508d0030268a8d', '人设混剪', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/14/9ac6c9593780346f5df5a6c066d42f4f.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/23/09/4c46fa90dd0996251dca7fb1869cb483.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6853cdd95440e50031367e4b', '醒目红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/13/68621307574bc8cfaec09916b675f77d.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/20/18/92df6e412ec7a2684010454fa57c9f40.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6853e63e1e7b320034fa1d9f', '蓝色三屏', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/13/91795c128f2e233e20c9ab8e4b78a17f.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/24/10/88559ec8ef0ca7f87c65621c7b123af2.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6863a624cbf589003866ed09', '现场新闻', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/11/13/1dd6d9cf272f41d0b725f26a13dce5bf.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/01/19/c7d9d9c34e01ad8dfa944ce7eaae9642.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6865f0e80353060030b51900', '经典黄白文案', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/18/47210ae18a0a8c9dd58a2600190ea830.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/18/04d72222eb9133c669f628e5c15f7671.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6880433948505d00319b5299', '人设混剪2', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/18/6b2161bcc9b05d79aac2d2b0a069014f.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/18/bcfeaa59f46aaacf22f3ae5ec5b0cbed.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6880b5974761d400308c15a5', '简约黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/17/f7d195c050025045be2bc52860498c66.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/29/15/eae1d3c1cf715f89e9784012f2e40790.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6880bd984761d400308c187b', '常规黄黑-上', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/24/09/bee048482eb6e44e5612d2e90bf1b508.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/20/1d5a74591ceac7979cb243e42fb4d9a4.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6880c4ec6de2ca00302d4e50', '常规黄黑-下', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/24/09/c73c9ad371670a85ba1bd1406c995c77.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/20/60f9f6acb1b950f2b0dee063394c5f7a.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('688212fe6de2ca00302da9e9', '红黄标题-上', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/24/19/a073332e7539e0a80ae2c0dba827d312.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/24/19/6ff067aee668a5e1fd71691b6e98c39f.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6882132248505d00319bec20', '红黄标题-下', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/24/19/7a64a98f9162f23e929b3ca6cc9b053f.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/24/19/3e65fe139d7fd2ab8ad17007404013bc.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6886e4d54894d300312c014c', '蓝白标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/13/e0017ff8b997816a723eff96ced1330c.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/13/b70ed3d405511612a17d2a3cc767fa12.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6886ea2648505d00319d21b0', '白橙标题-上', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/14/bc38dc5175c00efbf3416262963bad5a.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/29/15/2fa94417574fb7a6f68e3b8510a44384.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6886ea414894d300312c0449', '白橙标题-下', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/11/dead3e5ab6a2e0c5eeb40458e386d680.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/29/15/aba8033f493ec467792f99ea53fb3134.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68872e4a48505d00319d3e26', '清新绿底', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/16/65657da2dbf5e7c0a335d5e0f9ff6765.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/29/15/9d22583a715bb962cb19b3061b4d8f5b.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68ae64298cb1b40031e2085a', '七夕粉白字幕', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/27/17/c23330fdd8d0ef7df09c5d150de7d8c6.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/27/18/85daee970edec6083dfef15a240c5298.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68be53a18dad360031c30125', '醒目标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/08/14/d86c0dc8b474640b11aee392713e257b.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/08/14/9aa65cc5ca7dfe3ece9a2b2dee63ea1d.mp4', 0, 0);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('69203fff18247100336f6031', '蓝红标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/11/24/10/11af18cf7f7e79018728fd09e7afcb0e.jpg', 'newsMixCutting', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/11/24/10/9d524ea45467c8ddb9c2bf2022656b4b.mp4', 0, 0);



ALTER TABLE `la_sv_crawling_record`
ADD COLUMN `is_verify` tinyint NULL DEFAULT 0 COMMENT '是否验证0否1是' AFTER `status`;

DELETE FROM `la_dev_crontab` where `command` = 'wechat_verify';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('微信号校验', 1, 0, '', 'wechat_verify', '', 1, '* * * * *', '', 1764906366, '0.07', '30.44', 1764752848, 1764756995, NULL);

ALTER TABLE `la_kb_robot`
ADD COLUMN `max_tokens` int(10) unsigned NOT NULL DEFAULT '4096' COMMENT '最大返回长度',
MODIFY COLUMN `roles_prompt` longtext COMMENT '角色设定词';

UPDATE `la_config` SET  `value` =  '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"gpt-4","model_id":15,"model_sub_id":15,"status":"1","logo":"static/images/models/3.png"},{"id":"2","name":"gpt-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"gpt-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"gpt-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"3","name":"Gemini 2.5 pro","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"Gemma 3","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"},{"id":"10","name":"claude-sonnet-4-5","model_id":18,"model_sub_id":18,"status":"1","logo":"https://www.resource.nestsound.cn/logos_svg/logo_claude2.svg"}]}'
WHERE `type` = 'chat' AND `name` = 'ai_model';
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (18, 1, 'claude', '', 'claude-sonnet-4-5', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (18, 18, 1, 'claude', 'claude-sonnet-4-5', 'claude-sonnet-4-5',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 11, 11, 0.50, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 14, 14, 0.50, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 15, 15, 0.50, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 16, 16, 0.50, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 17, 17, 0.50, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);
INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 18, 18, 0, 1.00, 0.20, 0.30, 4096, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ('digital_human', 'video_case', '[{"id":"1","name":"数字人纯口播视频","image":"static/images/dh/dh1.jpg","video_case_url":"static/videos/dh/dh1.mp4"},{"id":"2","name":"数字人口播混剪","image":"static/images/dh/dh2.jpg","video_case_url":"static/videos/dh/dh2.mp4"},{"id":"3","name":"真人口播视频智剪","image":"static/images/dh/dh3.jpg","video_case_url":"static/videos/dh/dh3.mp4"},{"id":"4","name":"素材混剪神器","image":"static/images/dh/dh4.jpg","video_case_url":"static/videos/dh/dh4.mp4"},{"id":"5","name":"新闻体视频","image":"static/images/dh/dh5.jpg","video_case_url":"static/videos/dh/dh5.mp4"},{"id":"6","name":"一句话生成视频","image":"static/images/dh/dh6.jpg","video_case_url":"static/videos/dh/dh6.mp4"}]', 1760780862, 1763370311);




CREATE TABLE IF NOT EXISTS `la_traceability` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `creator_ip` varchar(255) DEFAULT NULL COMMENT '生产者id',
  `mate_id` int(11) DEFAULT '0' COMMENT '标识对应的数据id',
  `mate_type` tinyint(4) DEFAULT '0' COMMENT '标识对应的表',
  `input` text COMMENT '输入文本',
  `input_url` varchar(255) DEFAULT NULL COMMENT '输入资源地址',
  `output` text COMMENT '输出文本',
  `output_url` varchar(255) DEFAULT NULL COMMENT '输入资源地址',
  `task_id` varchar(255) DEFAULT NULL COMMENT '唯一标识',
  `enterprise_code` varchar(255) DEFAULT NULL COMMENT '企业代码',
  `compression` varchar(255) DEFAULT NULL COMMENT '文本压缩格式',
  `thumbnail_offset` int(11) DEFAULT '0' COMMENT '缩略图字节偏移量',
  `thunmnail_length` int(11) DEFAULT '0' COMMENT '缩略图长度',
  `preview_text_offset` int(11) DEFAULT '0' COMMENT '预览文本偏移量',
  `preview_text_length` int(11) DEFAULT '0' COMMENT '预览文本长度',
  `xmp_toolkit` varchar(255) DEFAULT NULL COMMENT 'XMP工具包',
  `text_encoding` varchar(255) DEFAULT NULL COMMENT '文本编码格式',
  `total_character_count` int(11) DEFAULT '0' COMMENT '文本总字符数',
  `aigc_label` varchar(255) DEFAULT NULL COMMENT 'aigic标识',
  `aigc_content_producer` varchar(255) DEFAULT NULL COMMENT 'aigc内容生产者标识',
  `aigc_producer_id` varchar(255) DEFAULT NULL COMMENT 'aigc生产标识',
  `aigc_reserve_code1` varchar(255) DEFAULT NULL COMMENT 'aigc备用编码1',
  `aigc_propagator` varchar(255) DEFAULT NULL COMMENT 'aigc内容传播者标识',
  `aigc_propagatot_id` varchar(255) DEFAULT NULL COMMENT 'aigc传播标识',
  `aigc_reserve_code2` varchar(255) DEFAULT NULL COMMENT 'aigc备用编码2',
  `aigc_propagator_ip` varchar(255) DEFAULT NULL COMMENT 'aigc传播者ip',
  `aigc_propagator_time` int(11) DEFAULT NULL COMMENT 'aigc传播时间',
  `profile_cmm_type` varchar(255) DEFAULT NULL,
  `profile_version` varchar(255) DEFAULT NULL,
  `profile_class` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


ALTER TABLE `la_human_voice`
ADD COLUMN `audio_url` varchar(255) NULL COMMENT '源语音地址' AFTER `type`,
ADD COLUMN `language` varchar(100) NULL COMMENT '复刻音频语种' AFTER `audio_url`,
ADD COLUMN `demo_text` varchar(500) NULL COMMENT '试听音频文案' AFTER `language`,
ADD COLUMN `result_task_id` varchar(255) NULL COMMENT '任务返回id' AFTER `demo_text`,
MODIFY COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态-0生成中,1:已生成 2:生成失败' AFTER `task_id`;


UPDATE `la_config` SET `value` = '{\"channel\":[{\"id\":\"1\",\"name\":\"标准版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"\\/static\\/images\\/human\\/1.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"2\",\"name\":\"极致版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"\\/static\\/images\\/human\\/2.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"4\",\"name\":\"优秘V5\",\"described\":\"满足多场景运用，助力企业打造沉浸式体验\",\"icon\":\"\\/static\\/images\\/human\\/4.png\",\"status\":\"1\",\"model_id\":8},{\"id\":\"6\",\"name\":\"优秘V7\",\"described\":\"高度还原，打造独一无二的虚拟代言人\",\"icon\":\"\\/static\\/images\\/human\\/6.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"7\",\"name\":\"禅镜\",\"described\":\"为数字化浪潮高频迭代的内容营销提供强劲驱动力\",\"icon\":\"\\/static\\/images\\/human\\/7.png\",\"status\":\"1\",\"model_id\":7},{\"id\":\"8\",\"name\":\"闪剪\",\"described\":\"适配多渠道发布，构建全域内容传播矩阵\",\"icon\":\"\\/static\\/images\\/human\\/8.png\",\"status\":\"1\",\"model_id\":9}],\"voice\":[{\"name\":\"智小敏(女)\",\"code\":\"10000\",\"status\":\"1\"},{\"name\":\"智小柔(女)\",\"code\":\"10001\",\"status\":\"1\"},{\"name\":\"智小满(女)\",\"code\":\"10002\",\"status\":\"1\"},{\"name\":\"爱小芊(女)\",\"code\":\"10003\",\"status\":\"1\"},{\"name\":\"爱小静(女)\",\"code\":\"10004\",\"status\":\"1\"},{\"name\":\"千嶂(男)\",\"code\":\"10005\",\"status\":\"1\"},{\"name\":\"智皓(男)\",\"code\":\"10006\",\"status\":\"1\"},{\"name\":\"爱小杭(男)\",\"code\":\"10007\",\"status\":\"1\"},{\"name\":\"爱小辰(男)\",\"code\":\"10008\",\"status\":\"1\"},{\"name\":\"飞镜(男)\",\"code\":\"10009\",\"status\":\"1\"}]}' WHERE `type` = 'model' and`name` = 'list';


UPDATE `la_model_config` SET `name` = '极速版音色克隆' WHERE `scene` = 'human_voice_shanjian';


CREATE TABLE IF NOT EXISTS `la_hd_puzzle_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态1待处理,2生成中,3已完成,4部份完成,5失败',
`result_count` mediumint(6) unsigned NOT NULL DEFAULT '0' COMMENT '生成数量',
`task_count` mediumint(6) unsigned NOT NULL DEFAULT '0' COMMENT '任务数量',
`success_puzzle_count` mediumint(6) unsigned NOT NULL DEFAULT '0' COMMENT '预计拼图数量',
`copywriting` text COMMENT '文案,json',
`material` text COMMENT '素材,json',
`extra` text COMMENT '附加字段内容,json',
`success_num` mediumint(6) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
`error_num` mediumint(6) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='拼图设置表';

CREATE TABLE IF NOT EXISTS `la_hd_puzzle` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0待处理,1成功,2失败',
`type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '图片类型1三张图2四张图3五张图4六张图5九张图',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`puzzle_setting_id` int(11) NOT NULL DEFAULT '0' COMMENT '拼图设置id',
`title` text NOT NULL COMMENT '标题.json',
`material` text NOT NULL COMMENT '素材.json',
`puzzle_url` text COMMENT '拼图.json',
`img_token` varchar(10) NOT NULL DEFAULT '0' COMMENT '图片扣费',
`extra` text COMMENT '附加字段内容,json',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`remark` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='图片合成任务表';


DELETE FROM `la_system_menu` WHERE `id` in (463, 466, 468,471);;
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (463, 461, 'M', '创作管理', '', 0, 'ai_application.montage/create_reocrd', 'create', 'ai_application/montage/create/reocrd', '', '', 0, 1, 0, 1760060394, 1764927873);

DELETE FROM `la_system_menu` WHERE `id` BETWEEN 493 AND 498;
DELETE FROM `la_system_menu` WHERE `id` BETWEEN 500 AND 508;
DELETE FROM `la_system_menu` WHERE `id` BETWEEN 510 AND 519;

INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (493, 463, 'C', '数字人口播混剪', '', 0, 'ai_application.montage.create/szr', 'szr', 'ai_application/montage/create/szr/index', '', '', 0, 1, 0, 1764920778, 1764927115);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (494, 463, 'C', '真人口播混剪', '', 0, 'ai_application.montage.create/realperson', 'realperson', 'ai_application/montage/create/realperson/index', '', '', 0, 1, 0, 1764920868, 1764927779);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (495, 463, 'C', '素材混剪', '', 0, 'ai_application.montage.create/material', 'material', 'ai_application/montage/create/material/index', '', '', 0, 1, 0, 1764920920, 1764927105);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (496, 463, 'C', '新闻体视频', '', 0, 'ai_application.montage.create/news', 'news', 'ai_application/montage/create/news/index', '', '', 0, 1, 0, 1764920950, 1764927101);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (497, 463, 'C', '创意视频', '', 0, 'ai_application.montage.create/creativity', 'creativity', 'ai_application/montage/create/creativity/index', '', '', 0, 1, 0, 1764921085, 1764927098);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (498, 463, 'C', '真人口播混剪详情', '', 0, 'ai_application.montage.create.realperson/detail', 'realperson_detail', 'ai_application/montage/create/realperson/detail', '/ai_application/montage/create/realperson', '', 0, 0, 0, 1764921182, 1764991070);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (500, 463, 'C', '素材混剪详情', '', 0, 'ai_application.montage.create.material/detail', 'material_detail', 'ai_application/montage/create/material/detail', '/ai_application/montage/create/material', '', 0, 0, 0, 1764921315, 1764927081);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (501, 463, 'C', '新闻体视频详情', '', 0, 'ai_application.montage.create.news/detail', 'new_detail', 'ai_application/montage/create/news/detail', '/ai_application/montage/create/news', '', 0, 0, 0, 1764921368, 1764927077);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (502, 463, 'C', '创意视频详情', '', 0, 'ai_application.montage.create.creativity/detail', 'creativity_detail', 'ai_application/montage/create/creativity/detail', '/ai_application/montage/create/creativity', '', 0, 0, 0, 1764921422, 1764927071);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (503, 493, 'A', '删除', '', 0, 'ai_application.montage.create.szr/delete', '', '', '', '', 0, 1, 0, 1764922719, 1765242647);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (504, 494, 'A', '删除', '', 0, 'ai_application.montage.create_record.realperson/delete', '', '', '', '', 0, 1, 0, 1764922732, 1765242601);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (505, 495, 'A', '删除', '', 0, 'ai_application.montage.create.material/delete', '', '', '', '', 0, 1, 0, 1764922751, 1765242640);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (506, 496, 'A', '删除', '', 0, 'ai_application.montage.create.news/delete', '', '', '', '', 0, 1, 0, 1764922772, 1765242635);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (507, 497, 'A', '删除', '', 0, 'ai_application.montage.create.creativity/delete', '', '', '', '', 0, 1, 0, 1764922810, 1765242655);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (508, 498, 'A', '删除', '', 0, 'ai_application.montage.create.szr.detail/delete', '', '', '', '', 0, 1, 0, 1764922834, 1765242665);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (510, 500, 'A', '删除', '', 0, 'ai_application.montage.create.material.detail/delete', '', '', '', '', 0, 1, 0, 1764922866, 1765242672);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (511, 501, 'A', '删除', '', 0, 'ai_application.montage.create.news.detail/delete', '', '', '', '', 0, 1, 0, 1764922882, 1765242679);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (512, 502, 'A', '删除', '', 0, 'ai_application.montage.create.creativity.detail/delete', '', '', '', '', 0, 1, 0, 1764922912, 1765242686);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (513, 267, 'M', 'AI拼图', '', 0, '', 'puzzle', 'ai_application/draw/puzzle/lists', '', '', 0, 1, 0, 1764923266, 1764923288);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (514, 513, 'C', '创作记录', '', 0, 'ai_application.draw.puzzle/record', 'record', 'ai_application/draw/puzzle/record/index', '', '', 0, 1, 0, 1764923377, 1764923937);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (515, 514, 'A', '删除', '', 0, 'ai_application.draw.puzzle.record/delete', '', '', '', '', 0, 1, 0, 1764923389, 1764923389);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (516, 513, 'C', '创作记录详情', '', 0, 'ai_application.draw.puzzle.record/detail', 'record_detail', 'ai_application/draw/puzzle/record/detail', '/ai_application/draw/puzzle/record', '', 0, 0, 0, 1764923455, 1764923455);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (517, 516, 'A', '删除', '', 0, 'ai_application.draw.puzzle.record.detail/delete', '', '', '', '', 0, 1, 0, 1764923532, 1764923532);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (518, 463, 'C', '数字人口播混剪详情', '', 0, 'ai_application.montage.create.szr/detail', 'szr_detail', 'ai_application/montage/create/szr/detail', '/ai_application/montage/create/szr', '', 0, 0, 0, 1764927024, 1764927416);
INSERT INTO `la_system_menu` (`id`, `pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`) VALUES (519, 518, 'A', '删除', '', 0, 'ai_application.montage.create.szr.detail/delete', '', '', '', '', 0, 1, 0, 1764927045, 1764927045);


DELETE FROM `la_dev_crontab` WHERE `command` = "hd_puzzle_cron";
INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ( '小红书拼图', 1, 0, '', 'hd_puzzle_cron', '', 1, '* * * * *', '', 1760432522, '0.01', '103.49', 1758180005, 1758184800, NULL);
