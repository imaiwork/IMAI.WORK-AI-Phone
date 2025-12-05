
DELETE FROM `la_config` WHERE type = "ai_live";

INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ( 'ai_live', 'config', '{\"apk_url\":\"https://zhibooss.imai.work/uploads/apks/imaivideo10376_basic.apk?time=1750062043035\",\"description\":\"https://yijianshi.feishu.cn/docx/XcBxdUoBYos3kvxkKZHcLWBUn7c?from=from_copylink\",\"recharge_entrance_qr_code\":\" \"}', 1750405302, 1750405302);

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'website', 'share_image', 'static/images/mnp_share_image.jpg', 1751017631 , 1751017631
    WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'website' AND `name` = 'share_image');

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'website', 'share_title', 'AI数字员工', 1751017631 , 1751017631
    WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'website' AND `name` = 'share_title');

INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`)
SELECT 'website', 'share_desc', '你的专属AI助手，支持图文创作、多端对接、多行业适配，助你降本增效！', 1751017631 , 1751017631
    WHERE NOT EXISTS (SELECT 1 FROM `la_config` WHERE `type` = 'website' AND `name` = 'share_desc');

