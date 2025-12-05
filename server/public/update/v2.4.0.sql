

CREATE TABLE IF NOT EXISTS `la_shanjian_clip_template` (
`id` varchar(40) NOT NULL COMMENT 'Mongo风格主键',
`name` varchar(128) NOT NULL DEFAULT '' COMMENT '模板名称',
`cover_url` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图URL',
`scene` varchar(32) NOT NULL DEFAULT '' COMMENT '场景标识',
`demo_url` varchar(255) NOT NULL DEFAULT '' COMMENT '演示视频URL',
`create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
`update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频风格模板表';


DELETE FROM `la_shanjian_clip_template` WHERE `scene` = "oralMixCutting";
DELETE FROM `la_shanjian_clip_template` WHERE `scene` = "virtualman";

INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682d4ab281fc800038ace423', '专业蓝白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/48d4cbff096a6d25bff07ecd7c8e8dc2.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/91909e52726208aef9d7054b5f36670a.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682d4baa01d7b50031341642', '红蓝标题', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/06/14/79ee4c6ec41d48b45fbd96bd949f503e.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/17/d56b070a45af5d923e12123c09897e47.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682da35895206500312cf452', '简易黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/21/17/caec00e4bb90d015cb0e62f9b69320ad.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/21/17/24669e1594a5a3bd8e5bc97b05f490a9.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682edf4681fc800038acf69c', '基础橙白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/06/18/9acb4a65cb0f3e696069bbdd20e31b8a.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/17/689133a2a659fe1e5eb099ae0910ee4b.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682ee05995206500312cffbd', '实用橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/05/18/fadd6ad2787e8fc92bf09d1538f60371.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/16/2d3e12c01da161b9969f99665a756b39.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682ee0b6ad487700387a8d97', '简洁黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/03/19/a9c2f945379b1d5698d75d43f6348eca.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/17/48b3000019bffb6bc40c69c9631edeb4.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682ee8747a7bde0030479d7d', '清新端午', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/30/11/0c9b48ce20fbdd6422073959885835c4.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/30/11/9a8aa7ddf20b51413a0276eb676c4442.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('682ff0f7691efe00316e2807', '白橙风格', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/10/da962ccc6c4af86bdc1edabcb4d965fb.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/e6b110624c1cf14f2860c06b7f5830c4.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68302bd68a14430033a60a68', '文艺黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/23/21/a1a561effec5cb9b9b76a055053f7f57.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/17/9810f89f7b66e067a1b2c65b2b200aaf.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6833ff3cbe66110038f515bf', '综艺渐变', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/11/11/adf1e29027b4b335c0ea6dcb277bf758.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/15/8352b8f378daddb726dd2be6921e32d0.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68342c3e4275200065cccc90', '简单黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/06/16/5f67405fb6dfe7af6f891bc8ace81645.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/15/e0d03460b012422911558315c1e1318e.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68343a063f03bb005eec97f4', '淡雅黑白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/03/20/311896eabcb0ece95c6549d241c75925.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/16/eda30aa0aaa898f1ef0f151a1271c34f.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68344a5fcd336f005cb7b4d3', '棕黄风格', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/03/20/9103b7dd7aa271eb23e9cd49d9741441.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/15/06ae41afda50cbf8d0f700b48fc0eaa9.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68356ac75e3ec40066a33875', '硬核紫', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/30/18/d11f80d0f0d32f561b320654a0fe65c0.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/15/78f4d1bd7cc1a88ae8f9f4622f0491ee.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6836b4f1cbab400030062e8b', '极简黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/18/ca9c6c64810f173aef10879f7c3cf70c.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/18/bc9eb521ea8a813d777fe0c1da233930.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6836ca78cbab4000300668ef', '黄白字裂开', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/14/21ed1081c61a88020c0e6728fa52d7bf.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/16/59a997a7f3ff254b273b29a2ff93b48c.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6836d1554275200065ccf969', '商务蓝白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/30/11/cc2c6d45a607f61c06ff62d4bcce31df.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/05/30/11/030808c03d7af50907bfcbb3a0d4b1d6.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6837cf4ccbab40003008b871', '基础黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/09/18/af5813efbffbe6620d0731454000972c.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/09/18/deff44e1dfef2d5b0afa92a7a0eba05a.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6838069ccbab400030093f54', '活泼黄蓝', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/10/15/28c37157d1e7cccf71802f6db160ce5d.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/03/18/d4f521a5e6f0a5d1347d61c94e677e29.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('683819b9cd336f005cb7f813', '高级橙白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/12/18/bf895862c3850c2747794bfd6303b623.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/11/e144b84004596fbe8de0a6eb7c9df099.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68384590cbab40003009bf3c', '粉白渐变', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/18/d3b7de6d8a4fabad34d5d834001d0113.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/18/06463ae2bf8d2e11a3cfd7daee029b9c.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68395e5c7c2fc5003012eb39', '白红渐变', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/48c4ae112d6711af6c80997d46dbb16e.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/cd06317d7725fcdf0422e99024cdb7f8.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6842b43414aa6c003083ab41', '黑白聚焦', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/13/18/f4e76537288ec7b396d4c76a65638930.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/15/3ab3eaae3d146911b8ee6957fd3f5f2a.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6846ada8b3d336003003a657', '醒目橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/5480eb1594ff405727f9da14ceca20e0.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/18/54604a14ad5cbb9c3a819f262079ad52.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6847d5d1f4c3530030d3f753', '粉白吸睛', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/11/3c5618752a0c0258ef21273baf6c08e4.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/11/1f801525e9c8f4cb52ec371c789ddb77.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6847da4764fc05003246ce94', '黄橙商务', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/11/279aec55057b6ecaaaee4546a834b2b7.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/11/f5d243693753060ca0d2f431db36623d.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6848054b00eac800384d7313', '活力粉白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/21/14/36c0bb2a7c0941b328c579c862c85f85.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/21/14/fadf49e42871abb23873490ce43d2ab3.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68492ca30c508d0030254a74', '棕黄简约', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/14/b3a8626ea56a37a70dfe2d43db0af120.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/14/6941084345bef0e23efcf61525d21f48.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68496071f4c3530030d42bcd', '红蓝科普', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/10/5a0615ab81d50c8c199fe1233a0bebe0.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/10/5eca4274559a7349131c89aa20bd0bfa.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684bd620d7c4ca003251f4c5', '综艺绝绝紫', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/16/17/d3011c4bc60f28480a25a93118ca83a4.png', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/06/17/15/5eb23814f0372be289c38cea214d4363.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('684bec5800eac800384deb7f', '简约黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/21/18/c9f0a8de13ecd07d11724d068cc7f2d6.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/21/18/4cefc4201f0db943051d2380f31abc67.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('685126890c508d0030262c9f', '常规黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/21/18/b4b6fe96cf42ab743069b8f33d00d8d6.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/21/18/11718a0eb30a87e59488b632ad58cf15.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6853770bf4c3530030d54ea1', '简易橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/16/7de8a4d672ad2aa5911e08d2da061f75.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/28/16/4d5e9b05e9e65da28d97892c5f0b37a7.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68537d7ea6b684003174fb59', '跟随字幕', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/16/9146829225fec231b0faf36989ef8249.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/22/16/acd3bd589ce2ffa08351c399cd71d2c3.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6876111d43c4a1003860cffe', '网感白橙', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/16/f7fc0c65b704efeb511424c84e2e1c0e.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/23/16/3cce23771698b60113d164951f58ef35.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68778be8d2d59300301ef6b4', '黄白双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/11/3342b712d2573c66fee6a232aaa4357a.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/16/6427f54fa90d78c0e099a089fc4daeb5.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68778cd5d1a79c003d3a3fc8', '红白双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/11/8696c2df196c846e5da10b85d0e59e41.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/16/efefe1482188e1af135485d35e53fa5f.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68786818c33a110031e3727a', '纯白双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/11/1d1d12e0505978b811865fe1a0f02713.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/17/16/32b3e6ce4f0ade5b9b0a65230e772e2f.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6879dfe03b9dea0033f9d86d', '简约双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/13/517cddbc5c67f9d8dc778cdc195f4a91.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/18/13/eced65968946876d751efc8ce1f466a6.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6882fc4f4894d300312b00ac', '通用粉黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/25/14/b617bd21afa5987910a03c4cbc60998b.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/07/25/11/28f93444edf37036a1c50730b7438fd6.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('688c612e00d042003017e131', '百搭暖黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/01/18/cf12f194f2bb71d8dfb8920e4be90a51.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/01/14/86f05ad5b279abf0d02fd2df5735406d.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('688c70e77b75e500326f1dde', '经典黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/08/14/2ba20c409b8c0211dbe5be9742442d1e.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/08/14/64c51102422e437589aa80e95394a71c.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6891cbec39bd0400384859cc', '营销橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/06/16/f107abbe71e00132b21c49473828e891.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/06/15/f2f90b198dc872391f04328604a59f96.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68944784308d030032249c7f', '百搭蓝白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/07/18/631f6decc11b16c07da5ecf13a6af743.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/07/14/26eb1543f8f26ecc9dcf5e40d05af99c.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('6895b582ecf75a0038bde7f1', '情感紫黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/08/17/0b80dfb7157d950258b04b880cdefa29.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/08/17/6e1850c63dbc04b40e5b39100be05a74.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('689c567f4eaadd0030c154f8', '金融', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/14/18/b72a666eef412a39f5027e0848cccb8f.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/14/18/e8949cbd0e481a4692dab5ceb0a45265.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('689d8c6165fbf10039b153fe', '金融', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/14/18/f55168afa70452ea6bbdbc2a289d37c3.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/15/15/bf765c89eced67a16d5b09003526936a.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('689da586b27a9900310b74fb', '保险', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/14/18/b3382e0bc884bbe24e32d0e78aa314c5.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/14/18/cc8271d43a6aa515f355cfdc3750193e.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a43427d506f200380262dc', '书法黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/22/17/2a30e9b8206f2d37349485454894b0c9.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/20/14/77dc3d906e30354e8c81de1bea7547b3.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a4415598ee6e0031d33c63', '重点橙红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/19/18/ece37610bdb1bf723bd35334584c5157.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/19/17/9866c8ea607504d60582e88c75205e0c.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a54620b5b5a80030c71213', '情感黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/26/11/65f4d06938693b8df60ab3ec740b3b85.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/20/14/6be10e421fe3a9ac3142611e3fa8d1fe.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a595bfb5b5a80030c73a2d', '简易黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/20/18/dd429444e796f566ea62c361a934804e.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/27/10/22f15135fd99ebc73191643cde1f7040.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a5b05641e77f0037c555d7', '商务白蓝', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/22/10/a8f9dee2bed785bd482ae178c61929bb.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/02/18/1e1659a9cc468e55936cbf3705a020ca.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a6d9b4550be9003978e93e', '重点红白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/26/11/afe095990aaedba00f71b6feaf1c2726.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/21/16/6c92da508e28f7d6852fd41e8e0941a8.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a6f9bb46591d00311c2ab3', '通用黄红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/21/19/ad1d45ea6c98da1fd03a085fab803fff.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/21/18/65eb7643423b8cf8a20fef17d658aef3.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68a704bc46591d00311c3205', '黄白字幕', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/22/10/5858f2bd31d500a04d1b0ecbb2ca0a8e.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/21/19/fb56e2bc3927fbc809a9c9c1c2b68ca1.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68afc7e18cb1b40031e28ef3', '简易橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/28/14/06e34b1551b58ee11270008ade66a9ad.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/28/14/522f0cff99e981aa3cbc4dd65e40f45e.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b1891a6028800031df742d', '通用橙黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/29/19/5782c2ac3637d5f8c53d985c7105956e.jpg', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/08/29/19/fd2bfa81bac3193eed70a6cfd154b30f.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b56b77ccd8300033c97097', '书法双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/01/19/265c78f508485e5a2df3e5254f115709.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/01/18/25268fdff26cc5265fb1b834b9ed3a84.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b589a51a8e920031ee7f8b', '高级黄白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/02/19/2e57eed623d01c804857cd1f0b4c8264.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/01/19/ed8a54cd275597f7a5c77c0433af64f9.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b6aad9af3b800032935c7a', '黑底双语', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/02/16/3c0e631b3b7f7e67e1630515410ba9d9.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/02/16/d8298452313a72dc9f6a4327386f4a59.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b6c63f49c0ae002fbf2dec', '高级白橙', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/02/19/91190e9f0b7e433630a733331954aec0.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/02/19/0e035cd59a9bd230e060fb647161b8c1.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b93eb6a02ae2003036d063', '重点红黄', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/04/15/785f4d758e03c598578221bd6a45fecf.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/04/15/896c769b8dcab41a494534f9f819fb2b.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68bab05f49c0ae002fc0caec', '翻转红白', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/05/19/5b210880ab1ac96ae5ed9909f0163f62.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/05/17/64fcf16b663cef003412171492fbb6f5.mp4', 1757756773, 1757756773);
INSERT INTO `la_shanjian_clip_template` (`id`, `name`, `cover_url`, `scene`, `demo_url`, `create_time`, `update_time`) VALUES ('68b6d13accd8300033ca0c54', '高级黄红', 'https://img-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/03/11/f9009b612fe5037a27bdb3ae6b406012.webp', 'virtualman', 'https://vod-vidflow.oss-cn-beijing.aliyuncs.com/video_style/2025/09/03/11/2284dfd05858f8b0d22e8c5d7c57c326.mp4', 1757756773, 1757756773);

CREATE TABLE IF NOT EXISTS `la_shanjian_character_design` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
`introduced` text NOT NULL COMMENT '人物介绍',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='人设表';


ALTER TABLE `la_sv_publish_setting`
MODIFY COLUMN `accounts` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '账号集合' AFTER `name`,
ADD COLUMN `video_ids` varchar(255) NULL AFTER `video_setting_id`,
ADD COLUMN `publish_frep` tinyint NULL DEFAULT 0 COMMENT '每日发布频率' AFTER `publish_json`,
ADD COLUMN `task_type` tinyint NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布' AFTER `user_id`,
MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 1 COMMENT '任务状态0草稿1待执行2执行中3已完成4暂停' AFTER `poi`;


ALTER TABLE `la_sv_publish_setting_account`
ADD COLUMN `video_ids` varchar(255) NULL COMMENT '视频集合ids' AFTER `video_setting_id`,
ADD COLUMN `task_type` tinyint NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布' AFTER `publish_id`,
MODIFY COLUMN `status` tinyint(4) NULL DEFAULT 0 COMMENT '状态0未开启 1运行中 2已完成 3已删除4暂停' AFTER `video_ids`,
ADD COLUMN `task_status` tinyint NULL DEFAULT 1 COMMENT '生成待发布视频任务状态1执行中2已完成' AFTER `status`;


ALTER TABLE `la_sv_publish_setting_detail`
ADD COLUMN `task_type` tinyint NULL DEFAULT 1 COMMENT '任务类型1原发布模式2闪剪发布' AFTER `video_task_id`,
ADD COLUMN `sub_task_id` varchar(255) NULL COMMENT '子任务id' AFTER `task_id`,
ADD COLUMN `pic` varchar(255) NULL COMMENT '封面' AFTER `material_tag`,
ADD COLUMN `video_setting_id` int NULL DEFAULT 0 COMMENT '视频任务配置id' AFTER `video_task_id`,
ADD INDEX(`account_type`) USING BTREE,
ADD INDEX(`status`) USING BTREE;


CREATE TABLE IF NOT EXISTS `la_shanjian_video_task` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态-0待处理,1视频查询,2视频合成失败,3视频合成成功',
`audio_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '驱动类型 1：文案驱动 2：音频驱动',
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`video_setting_id` int(11) NOT NULL DEFAULT '0' COMMENT '视频设置id',
`anchor_id` varchar(50) NOT NULL DEFAULT '' COMMENT '形象id',
`voice_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音色id',
`card_name` varchar(255) NOT NULL DEFAULT '' COMMENT '人设名字',
`card_introduced` text NOT NULL COMMENT '人设介绍',
`title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
`msg` text NOT NULL COMMENT '文字',
`material` text NOT NULL COMMENT '素材.json',
`result_id` varchar(255) NOT NULL DEFAULT '' COMMENT '生成的视频id',
`music_url` text COMMENT '音乐地址',
`video_result_url` text COMMENT '生成的视频地址',
`clip_id` varchar(40) NOT NULL DEFAULT '' COMMENT '剪辑风格',
`video_token` varchar(10) NOT NULL DEFAULT '0' COMMENT '视频扣费',
`extra` text COMMENT '附加字段内容,json',
`tries` tinyint(1) NOT NULL DEFAULT '0' COMMENT '尝试次数',
`remark` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='视频合成任务表';


INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('coze_publish_content_generated', 10103, '算力/次', 'COZE发布内容生成', 1.00, 'COZE发布内容生成', 1, NULL, NULL);
INSERT INTO `la_model_config` (`scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('shanjian_copywriting_create', 10102, '算力/次', '口播混剪视频文案生成', 1.00, '口播混剪视频文案生成', 1, NULL, NULL);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_avatar_shanjian', 5030, '算力/次', 'AI智能口播混剪形象', 1.00, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_voice_shanjian', 5031, '算力/次', 'AI智能口播混剪音色', 1.00, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('human_video_shanjian', 5032, '算力/秒', 'AI智能口播混剪视频合成', 2.00, '', 1, 1740799252, 1740799252);
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('sph_local_ocr', 11004, '算力/次', '本地OCR', 1.00, '本地ocr识别，1次/算力', 1, NULL, NULL);

CREATE TABLE IF NOT EXISTS `la_shanjian_anchor` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态-1:形象合成,2形象合成失败,3形象合成成功,4音色合成,5音色合成失败,6音色合成成功',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`anchor_id` varchar(50) NOT NULL DEFAULT '' COMMENT '形象id',
`voice_id` varchar(50) NOT NULL DEFAULT '' COMMENT '音色id',
`voice_model` varchar(50) NOT NULL DEFAULT 'v2' COMMENT '音色模型',
`voice_url` varchar(255) NOT NULL DEFAULT '' COMMENT '音色文件地址',
`remark` varchar(500) NOT NULL DEFAULT '' COMMENT '原因',
`token` varchar(10) NOT NULL DEFAULT '' COMMENT '消耗',
`anchor_url` varchar(255) NOT NULL DEFAULT '' COMMENT '头像文件地址',
`authorized_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '授权封面',
`authorized_url` varchar(255) NOT NULL DEFAULT '' COMMENT '授权视频',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='闪剪数字人形象表';

CREATE TABLE IF NOT EXISTS `la_shanjian_video_setting` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
`name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
`task_id` varchar(50) NOT NULL DEFAULT '' COMMENT '唯一任务ID',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态1待处理,2生成中,3已完成',
`video_count` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '视频数量',
`anchor` text COMMENT '形象,json',
`voice` text COMMENT '音色,json',
`copywriting` text COMMENT '文案,json',
`character_design` text COMMENT '人设,json',
`material` text COMMENT '素材,json',
`clip` text COMMENT '剪辑风格,json',
`music` text COMMENT '音乐,json',
`extra` text COMMENT '附加字段内容,json',
`success_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成功次数',
`error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
`create_time` int(11) DEFAULT NULL COMMENT '创建时间',
`update_time` int(11) DEFAULT NULL COMMENT '更新时间',
`delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='视频设置表';


CREATE TABLE IF NOT EXISTS `la_sv_crawling_manual_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL COMMENT '任务名称',
  `source` tinyint(4) DEFAULT NULL COMMENT '线索来源1表格导入2获客任务引用',
  `fileurl` varchar(255) DEFAULT NULL COMMENT '文件地址',
  `crawling_task_ids` varchar(255) DEFAULT NULL COMMENT '获客任务id集合',
  `add_type` tinyint(4) DEFAULT '0' COMMENT '自动加好友:0关闭,1开启',
  `add_number` int(11) DEFAULT '0' COMMENT '加好友次数',
  `add_interval_time` int(11) DEFAULT '0' COMMENT '加好友间隔时间/分钟',
  `add_friends_prompt` varchar(1000) DEFAULT NULL COMMENT '加好友内容提示词',
  `add_remark_enable` tinyint(4) DEFAULT '0' COMMENT '是否开启加好友备注0否1是',
  `remarks` varchar(1000) DEFAULT NULL COMMENT '加微好友备注集合',
  `wechat_id` varchar(1000) DEFAULT NULL COMMENT '添加好友的微信号,多个逗号分隔',
  `wechat_reg_type` tinyint(4) DEFAULT '0' COMMENT '加微匹配规则0全部1微信号2手机号',
  `exec_add_count` int(11) DEFAULT '0' COMMENT '任务执行加微的总次数',
  `completed_add_count` int(11) DEFAULT '0' COMMENT '已执行加微信的次数',
  `status` int(11) DEFAULT '0' COMMENT '状态-0未开始,1进行中,2暂停中,3已完成4已结束',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `la_sv_crawling_manual_task_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `task_id` int(11) DEFAULT '0' COMMENT '手动任务id',
  `clue_wechat` varchar(255) DEFAULT NULL COMMENT '线索微信',
  `wechat_no` varchar(255) DEFAULT NULL COMMENT '执行微信号',
  `wechat_name` varchar(255) DEFAULT NULL COMMENT '执行微信昵称',
  `remark` varchar(500) DEFAULT NULL COMMENT '申请备注',
  `exec_task_id` varchar(255) DEFAULT NULL COMMENT '申请任务id',
  `exec_time` datetime DEFAULT NULL COMMENT '申请时间',
  `status` tinyint(4) DEFAULT '0' COMMENT '执行结果1成功2执行中0失败4待执行',
  `result` varchar(500) DEFAULT NULL COMMENT '执行结果',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=317 DEFAULT CHARSET=utf8mb4;



ALTER TABLE `la_sv_crawling_task`
CHANGE COLUMN `remark` `remarks` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '加微好友备注集合' AFTER `add_friends_prompt`,
ADD COLUMN `add_remark_enable` tinyint NULL DEFAULT 0 COMMENT '是否开启加好友备注0否1是' AFTER `add_friends_prompt`;

DELETE FROM `la_dev_crontab` WHERE `command` = "shanjian_video_task";
DELETE FROM `la_dev_crontab` WHERE `command` = "crawling_manual_cron";
INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ( '闪剪视频合成', 1, 0, '', 'shanjian_video_task', '', 1, '* * * * *', '', 1760432522, '0.01', '103.49', 1758180005, 1758184800, NULL);
INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ( '手动加微任务', 1, 0, '', 'crawling_manual_cron', '', 1, '* * * * *', NULL, 1760500601, '0', '0', 1760500601, 1760500601, NULL);


INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('add_remark', 'wechat', '[\"您好，我是刷到您这边的内容，感觉挺专业的，想加您沟通一下～\",\"您好，看到您这边在做相关方向，方便加个好友交流下吗？\",\"您好，我这边有点合作想法，想先加您微信简单聊两句～\",\"您好，看到您做的项目挺有意思的，想了解下您这边的情况～\",\"您好，我这边在做类似业务，觉得可以互相学习一下～\"]', 1760427363, 1760428048);



INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (5, 3, 'hidream', '', 'hidreamai', '', '', 0, 1, 1, 1, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (6, 3, 'doubao', '', 'Seedream', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (7, 4, '禅镜', 'static/images/human/7.png', '禅镜', '', '', 0, 1, 1, 1, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (8, 4, '优秘', 'static/images/human/4.png', '优秘V5', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (9, 4, '闪剪', 'static/images/human/8.png', '闪剪', '', '', 0, 1, 1, 1, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (10, 3, 'doubao', '', 'Seedance 1.0 pro', '', '', 0, 1, 1, 1, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (11, 1, 'google', 'static/images/models/2.png', 'Jemini 2.5 pro', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (14, 1, 'google', 'static/images/models/2.png', 'Jemma 3', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (15, 1, 'openai', 'static/images/models/3.png', 'gpt-4', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (16, 1, 'openai', 'static/images/models/3.png', 'gpt-4o-mini', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models` ( `id`, `type`, `channel`, `logo`, `name`, `remarks`, `configs`, `sort`, `is_system`, `is_enable`, `is_default`, `create_time`, `update_time`, `delete_time`) VALUES (17, 1, 'openai', 'static/images/models/3.png', 'gpt-3.5-turbo', '', '', 0, 1, 1, 0, 1755929617, 1755929617, NULL);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (5, 5, 3, 'hidream', 'hidreamai', 'hidreamai',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (6, 6, 3, 'doubao', 'Seedream', 'Seedream',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (7, 7, 4, '禅镜', '禅镜', '禅镜',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (8, 8, 4, '优秘', '优秘V5', '优秘V5',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (9, 9, 4, '闪剪', '闪剪', '闪剪',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (10, 10, 3, 'doubao', 'Seedance 1.0 pro', 'Seedance 1.0 pro',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (11, 11, 1, 'google', 'Jemini 2.5 pro', 'google/gemini-2.5-pro',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (14, 14, 1, 'google', 'Jemma 3', 'google/gemma-3-4b-it',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (15, 15, 1, 'openai', 'gpt-4', 'gpt-4',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (16, 16, 1, 'openai', 'gpt-4o-mini', 'gpt-4o-mini',0.0000, 0, 1, 1755929617);
INSERT INTO `la_models_cost` ( `id`, `model_id`, `type`, `channel`, `name`, `alias`, `price`, `sort`, `status`, `create_time`) VALUES (17, 17, 1, 'openai', 'gpt-3.5-turbo', 'gpt-3.5-turbo',0.0000, 0, 1, 1755929617);

UPDATE `la_models` SET `is_default` = 0 WHERE `id` = 1;
UPDATE `la_models` SET `name` = 'gpt-4o',`logo` = 'static/images/models/3.png',`is_default` = 0 WHERE `id` = 2;
UPDATE `la_models` SET `name` = 'gpt-4o',`logo` = 'static/images/models/3.png' WHERE `id` = 3;
UPDATE `la_models` SET `logo` = 'static/images/models/1.png' WHERE `id` = 4;

UPDATE `la_config` SET `value` = '{\"channel\":[{\"id\":\"1\",\"name\":\"hidreamai\",\"status\":\"0\",\"model_id\":5},{\"id\":\"2\",\"name\":\"即梦general_v21\",\"status\":\"1\",\"model_id\":0},{\"id\":\"3\",\"name\":\"Seedream\",\"status\":\"1\",\"model_id\":6},{\"id\":\"4\",\"name\":\"Seedance 1.0 pro\",\"status\":\"1\",\"model_id\":10}]}'
WHERE `type` = 'hd' AND `name` = 'list';

UPDATE `la_config` SET  `value` =  '{\"channel\":[{\"id\":\"1\",\"name\":\"标准版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"/static/images/human/1.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"2\",\"name\":\"极致版\",\"described\":\"轻量化呈现，快速生成，高效传播\",\"icon\":\"/static/images/human/2.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"4\",\"name\":\"优秘V5\",\"described\":\"满足多场景运用，助力企业打造沉浸式体验\",\"icon\":\"/static/images/human/4.png\",\"status\":\"1\",\"model_id\":8},{\"id\":\"6\",\"name\":\"优秘V7\",\"described\":\"高度还原，打造独一无二的虚拟代言人\",\"icon\":\"/static/images/human/6.png\",\"status\":\"1\",\"model_id\":0},{\"id\":\"7\",\"name\":\"禅境\",\"described\":\"为数字化浪潮高频迭代的内容营销提供强劲驱动力\",\"icon\":\"/static/images/human/7.png\",\"status\":\"1\",\"model_id\":7}],\"voice\":[{\"name\":\"智小敏(女)\",\"code\":\"10000\",\"status\":\"1\"},{\"name\":\"智小柔(女)\",\"code\":\"10001\",\"status\":\"1\"},{\"name\":\"智小满(女)\",\"code\":\"10002\",\"status\":\"1\"},{\"name\":\"爱小芊(女)\",\"code\":\"10003\",\"status\":\"1\"},{\"name\":\"爱小静(女)\",\"code\":\"10004\",\"status\":\"1\"},{\"name\":\"千嶂(男)\",\"code\":\"10005\",\"status\":\"1\"},{\"name\":\"智皓(男)\",\"code\":\"10006\",\"status\":\"1\"},{\"name\":\"爱小杭(男)\",\"code\":\"10007\",\"status\":\"1\"},{\"name\":\"爱小辰(男)\",\"code\":\"10008\",\"status\":\"1\"},{\"name\":\"飞镜(男)\",\"code\":\"10009\",\"status\":\"1\"}]}'
WHERE `type` = 'model' AND `name` = 'list';

UPDATE `la_config` SET  `value` =  '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"gpt-4","model_id":15,"model_sub_id":15,"status":"1","logo":"static/images/models/3.png"},{"id":"2","name":"gpt-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"gpt-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"gpt-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"3","name":"Jemini 2.5 pro","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"Jemma 3","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"}]}'
WHERE `type` = 'chat' AND `name` = 'ai_model';

DELETE FROM `la_system_menu` WHERE `id` IN(461, 462, 463, 464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475);
INSERT INTO `la_system_menu` VALUES (461, 195, 'M', '口播混剪', '', 0, '', 'montage', '', '', '', 0, 1, 0, 1760060292, 1760060292);
INSERT INTO `la_system_menu` VALUES (462, 461, 'C', '发布记录', '', 0, 'ai_application.montage/publish_record', 'publish_record', 'ai_application/montage/publish/reocrd', '', '', 0, 1, 0, 1760060325, 1760060806);
INSERT INTO `la_system_menu` VALUES (463, 461, 'C', '创作记录', '', 0, 'ai_application.montage/create_reocrd', 'create_record', 'ai_application/montage/create/reocrd', '', '', 0, 1, 0, 1760060394, 1760063380);
INSERT INTO `la_system_menu` VALUES (464, 461, 'C', '形象列表', '', 0, 'ai_application.montage/anchor', 'anchor', 'ai_application/montage/anchor/index', '', '', 0, 1, 0, 1760060438, 1760060438);
INSERT INTO `la_system_menu` VALUES (465, 461, 'C', '发布详情', '', 0, 'ai_application.montage/publish_detail', 'publish/detail', 'ai_application/montage/publish/detail', '/ai_application/montage/publish_record', '', 0, 0, 0, 1760060590, 1760062767);
INSERT INTO `la_system_menu` VALUES (466, 461, 'C', '创作详情', '', 0, 'ai_application.montage/create_detail', 'create_detail', 'ai_application/montage/create/detail', '/ai_application/montage/create_record', '', 0, 0, 0, 1760060711, 1760060711);
INSERT INTO `la_system_menu` VALUES (467, 462, 'A', '删除', '', 0, 'ai_application.montage.publish_record/delete', '', '', '', '', 0, 1, 0, 1760060980, 1760060980);
INSERT INTO `la_system_menu` VALUES (468, 463, 'A', '删除', '', 0, 'ai_application.montage.create_reocrd/delete', '', '', '', '', 0, 1, 0, 1760060998, 1760060998);
INSERT INTO `la_system_menu` VALUES (469, 464, 'A', '删除', '', 0, 'ai_application.montage.anchor/delete', '', '', '', '', 0, 1, 0, 1760061015, 1760061015);
INSERT INTO `la_system_menu` VALUES (470, 465, 'A', '删除', '', 0, 'ai_application.montage.publish_detail/delete', '', '', '', '', 0, 1, 0, 1760061036, 1760061036);
INSERT INTO `la_system_menu` VALUES (471, 466, 'A', '删除', '', 0, 'ai_application.montage.create_detail/delete', '', '', '', '', 0, 1, 0, 1760061054, 1760061054);
INSERT INTO `la_system_menu` VALUES (472, 462, 'A', '开启:暂停', '', 0, 'ai_application.montage.publish_record/start:pause', '', '', '', '', 0, 1, 0, 1760061626, 1760061656);
INSERT INTO `la_system_menu` VALUES (473, 427, 'C', 'AI模型', '', 0, 'ai_setting.ai_models/lists', 'ai_model', 'ai_setting/ai_model/index', '', '', 0, 1, 0, 1760344867, 1760344867);
INSERT INTO `la_system_menu` VALUES (474, 473, 'A', '编辑', '', 0, 'setting.ai.models/edit', '', '', '', '', 0, 1, 0, 1760344890, 1760344890);
INSERT INTO `la_system_menu` VALUES (475, 427, 'C', '编辑模型', '', 0, 'ai_setting.ai_models/edit', 'ai_model/edit', 'ai_setting/ai_model/edit', '', '', 0, 0, 0, 1760510684, 1760510684);


ALTER TABLE `la_kb_robot`
ADD COLUMN `threshold` float NULL DEFAULT 0.7 COMMENT '模糊匹配阈值' AFTER `is_enable`;