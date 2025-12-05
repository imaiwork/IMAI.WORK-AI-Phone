ALTER TABLE `la_sv_copywriting`
    MODIFY COLUMN `keyword` varchar(700) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '关键词';



ALTER TABLE `la_sv_copywriting_task`
    MODIFY COLUMN `remark` varchar(700) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因';


ALTER TABLE `la_interview_job`
    MODIFY COLUMN `desc` varchar(2100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT ' 职位详情',
    MODIFY COLUMN `jd` varchar(2100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '任职要求',
    MODIFY COLUMN `extra` varchar(2100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '附加考察',
    MODIFY COLUMN `attention` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '面试关注';


DELETE FROM `la_dev_crontab` WHERE command = "sv_video_cron";
DELETE FROM `la_dev_crontab` WHERE command = "query_sv_audio_cron";
DELETE FROM `la_dev_crontab` WHERE command = "query_sv_copywriting_cron";
DELETE FROM `la_dev_crontab` WHERE command = "publish_detail_cron";

INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('待发布任务拉取', 1, 0, '', 'publish_detail_cron', '', 1, '* * * * * ', NULL, 1747896903, '0', '0', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('短视频合成', 1, 0, '', 'sv_video_cron', '', 1, '* * * * *', '', 1747722842, '0.03', '2.79', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('音频查询', 1, 0, '', 'query_sv_audio_cron', '', 1, '* * * * *', '', 1747722842, '0.01', '0.9', 1744881498, 1744881498, NULL);
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('文案查询', 1, 0, '', 'query_sv_copywriting_cron', '', 3, '* * * * *', '', 1747635841, '0.01', '0.9', 1744881498, 1744881498, NULL);

DELETE FROM `la_model_config` WHERE id = 35;
INSERT INTO `la_model_config` (`id`, `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES (35, 'ai_xhs', 9104, 'tokens/算力', 'AI小红书', 301, '每300字消耗1算力', 1, 1740799262, 1740799262);
