DELETE FROM `la_dev_crontab` WHERE `command` = 'sync_shanjian_clip_templates';
INSERT INTO `la_dev_crontab`
(`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`)
VALUES
('同步中台剪辑模板', 1, 0, '每小时从中台同步风格模板，并清理本地旧数据', 'sync_shanjian_clip_templates', '', 1, '0 * * * *', '', NULL, '0', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);
