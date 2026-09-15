ALTER TABLE `la_kb_robot`
ADD COLUMN `mode_type` tinyint(2) unsigned DEFAULT '1' COMMENT '拟人化模式：1=自定义,2=平衡模式,3=精准模式,4=创意模式';

ALTER TABLE `la_sv_publish_setting` 
ADD COLUMN `scene` int NULL DEFAULT 0 COMMENT '场景来源1从创作开始 2从历史开始' AFTER `video_ids`;


ALTER TABLE `la_sv_publish_setting_account` 
ADD COLUMN `scene` int NULL DEFAULT 0 COMMENT '1从创作开始 2从历史开始' AFTER `video_ids`;

ALTER TABLE `la_sv_publish_setting_detail` 
ADD COLUMN `scene` int NULL DEFAULT 0 COMMENT '1从创作开始 2从历史开始' AFTER `sub_task_id`;

ALTER TABLE `la_sv_crawling_manual_task` 
ADD COLUMN `start_time` int NULL COMMENT '执行开始时间' AFTER `status`,
ADD COLUMN `end_time` int NULL COMMENT '执行结束时间' AFTER `start_time`;