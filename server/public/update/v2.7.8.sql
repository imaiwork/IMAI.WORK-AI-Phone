ALTER TABLE `la_sv_lead_scraping_record`
ADD COLUMN `note_title` text NULL COMMENT '笔记标题' AFTER `industry_keyword`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `status`,
MODIFY COLUMN `likes` varchar(255) NULL DEFAULT 0 COMMENT '获赞数' AFTER `address`,
MODIFY COLUMN `fans` varchar(255) NULL DEFAULT 0 COMMENT '粉丝数' AFTER `likes`,
MODIFY COLUMN `follows` varchar(255) NULL DEFAULT 0 COMMENT '关注数' AFTER `fans`;

ALTER TABLE `la_sv_add_wechat_record`
ADD COLUMN `remark` varchar(255) NULL COMMENT '加好友备注' AFTER `wechat_name`,
ADD COLUMN `wechat_avatar` varchar(255) NULL COMMENT '头像' ;

ALTER TABLE `la_sv_publish_setting_account`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_device_active_account`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_device_circle_like_reply_account`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_device_take_over_task_account`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_device_task`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_lead_scraping_setting_account`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_wechat_strategy`
ADD COLUMN `nickname` varchar(255) NULL COMMENT '昵称' AFTER `account_type`,
ADD COLUMN `avatar` varchar(255) NULL COMMENT '头像' AFTER `nickname`;

ALTER TABLE `la_sv_crawling_manual_task_record`
ADD COLUMN `wechat_avatar` varchar(255) NULL COMMENT '头像' AFTER `wechat_name`;



ALTER TABLE `la_kb_robot`
ADD COLUMN `is_indexed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:未建立检索 1:建立检索';

INSERT INTO
    `la_dev_crontab` (
        `name`,
        `type`,
        `system`,
        `remark`,
        `command`,
        `params`,
        `status`,
        `expression`,
        `error`,
        `last_time`,
        `time`,
        `max_time`,
        `create_time`,
        `update_time`,
        `delete_time`
    )
VALUES (
        '知识库任务',
        1,
        0,
        '',
        'kb_cron',
        '',
        1,
        '* * * * * ',
        NULL,
        1747896903,
        '0',
        '0',
        1744881498,
        1744881498,
        NULL
    );


ALTER TABLE `la_sora_video_task`
MODIFY COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态-0待处理,1视频查询,2视频合成失败,3视频合成成功,4失败重试中',
MODIFY COLUMN `remark` VARCHAR(1000) DEFAULT '' COMMENT '失败原因';

UPDATE `la_model_config`
SET
    `unit` = '算力/秒',
    `score` = 80,
    `description` = '每秒消耗80算力'
WHERE
    `scene` = 'sora_video_create';

UPDATE `la_model_config`
SET
    `score` = 800,
    `description` = '每次消耗800算力'
WHERE
    `scene` = 'sora_pro_video_create';
