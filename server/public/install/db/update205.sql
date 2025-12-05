

DELETE FROM `la_dev_crontab` WHERE command = "oss_migration_cron";

INSERT INTO `la_dev_crontab` ( `name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('oss迁移', 1, 0, '', 'oss_migration_cron', '', 1, '* * * * *', '', 1750903451, '2.8', '69.85', 1744881498, 1749895250, NULL);

UPDATE `la_config` SET  `name` = 'config', `value` = '{\"apk_url\":\"https://zhibooss.imai.work/uploads/apks/imaivideo10376_basic.apk?time=1750062043035\",\"description\":\"https://yijianshi.feishu.cn/docx/XcBxdUoBYos3kvxkKZHcLWBUn7c?from=from_copylink\",\"recharge_entrance_qr_code\":\" \"}', `create_time` = 1750405302, `update_time` = 1750405302 WHERE `type` = 'ai_live';

ALTER TABLE `la_sv_publish_setting_detail` ADD COLUMN `material_tag` varchar(255) NULL COMMENT '发布内容话题' ;

ALTER TABLE `la_sv_publish_setting` ADD COLUMN `data_type` tinyint NULL DEFAULT 0 COMMENT '数据类型1模拟发布 0正常发布' ;

ALTER TABLE `la_sv_publish_setting_account` ADD COLUMN `data_type` tinyint NULL DEFAULT 0 COMMENT '数据类型1模拟发布 0正常发布' ;

-- 提示词修改
UPDATE `la_chat_prompt` SET `prompt_text` = '你的角色是：【角色设定】
企业背景信息是：【企业背景】

消息回复：
结合历史信息，当前需要进行回复的内容：【用户发送的内容】' WHERE `id` = 12;


ALTER TABLE `la_sv_publish_setting_detail`
    MODIFY COLUMN `material_url` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '视频,图片utl',
    MODIFY COLUMN `material_subtitle` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '发布内容副标题' ;