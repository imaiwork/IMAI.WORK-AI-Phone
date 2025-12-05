UPDATE  `la_config` SET `type` = 'website', `name` = 'shop_title'  WHERE `name` = 'pc_home_title';

UPDATE `la_system_menu` SET `type` = 'M'  WHERE `name` = '微信小程序';

UPDATE `la_config` SET `value` = 'static/images/mnp_share_image.jpg' WHERE  `type` = 'website' and `name` = 'share_image';
UPDATE `la_config` SET `value` = 'AI数字员工' WHERE  `type` = 'website' and `name` = 'share_title';
UPDATE `la_config` SET `value` = '你的专属AI助手，支持图文创作、多端对接、多行业适配，助你降本增效！' WHERE  `type` = 'website' and `name` = 'share_desc';
