
ALTER TABLE `la_sv_lead_scraping_setting` 
ADD COLUMN `touch_type` tinyint NULL DEFAULT 1 COMMENT '触达方式1固定话术 2ai回复 3ai根据固话去优化' AFTER `account_feature`,
MODIFY COLUMN `task_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '任务类型1评论2私信3留痕' AFTER `user_id`,
ADD COLUMN `marker_method` tinyint NULL DEFAULT 0 COMMENT '留痕方式1点赞评论2关注3点赞作品4评论作品5收藏作品' AFTER `industry_num`;

ALTER TABLE `la_digital_human_anchor`
    MODIFY COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0生成中 1部分完成 2已完成 3生成失败4ai视频生成中';

INSERT INTO `la_notice_setting` (`scene_id`, `scene_name`, `scene_desc`, `recipient`, `type`, `system_notice`, `sms_notice`, `oa_notice`, `mnp_notice`, `support`, `update_time`) VALUES (401, 'RPA任务', 'RPA任务创建、状态改变时发送', 1, 1, '{"type":"system","title":"","content":"","status":"0","is_show":"","tips":["可选变量 验证码:code"]}' , '{"type":"sms","template_id":"","content":"","status":"0","is_show":"0","tips":""}', '{"type":"oa","template_id":"","template_sn":"","name":"","first":"","remark":"","tpl":[],"status":"0","is_show":"","tips":["可选变量 验证码:code","配置路径：小程序后台 > 功能 > 订阅消息"]}', '{"type":"mnp","template_id":"","template_sn":"73806","name":"RPA任务通知","tpl":["RPA任务名称{{thing1.DATA}}开始时间{{time4.DATA}}结束时间{{time5.DATA}}状态{{phrase2.DATA}}"],"status":"1","is_show":"1","tips":["固定变量 RPA任务名称:thing1 开始时间:time4 结束时间:time5 状态:phrase2","RPA任务名称{{thing1.DATA}}开始时间{{time4.DATA}}结束时间{{time5.DATA}}状态{{phrase2.DATA}}","生效条件：1、微信小程序后台完成模板设置。"]}', 4, 1769400000);
INSERT INTO `la_notice_setting` (`scene_id`, `scene_name`, `scene_desc`, `recipient`, `type`, `system_notice`, `sms_notice`, `oa_notice`, `mnp_notice`, `support`, `update_time`) VALUES (402, '视频合成任务', '视频任务创建、状态改变时发送', 1, 1, '{"type":"system","title":"","content":"","status":"0","is_show":"","tips":["可选变量 验证码:code"]}' , '{"type":"sms","template_id":"","content":"","status":"0","is_show":"0","tips":""}', '{"type":"oa","template_id":"","template_sn":"","name":"","first":"","remark":"","tpl":[],"status":"0","is_show":"","tips":["可选变量 验证码:code","配置路径：小程序后台 > 功能 > 订阅消息"]}', '{"type":"mnp","template_id":"","template_sn":"57938","name":"视频合成任务通知","tpl":["视频合成任务名称{{thing1.DATA}}状态{{phrase4.DATA}}时间{{time3.DATA}}"],"status":"1","is_show":"1","tips":["固定变量 视频合成任务名称:thing1 状态:phrase4 时间:time3"]}', 4, 1769400000);

ALTER TABLE `la_digital_human_anchor`
    ADD COLUMN `task_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '唯一任务ID' ,
    ADD COLUMN `ai_type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ai生成：0否 1是';

DELETE FROM `la_model_config` WHERE scene = "ai_shanjian_authorized_video";
INSERT INTO `la_model_config` ( `scene`, `code`, `unit`, `name`, `score`, `description`, `status`, `create_time`, `update_time`) VALUES ('ai_shanjian_authorized_video', 5040, '算力/次', 'AI自动生成授权形象视频', 1.00, '', 1, 1740799252, 1740799252);


ALTER TABLE `la_sv_media_material` 
ADD COLUMN `group_id` int(11) NULL DEFAULT 0 COMMENT '素材分组id' ;


DELETE FROM `la_dev_crontab` WHERE `command` = 'ai_digital_human_anchor_cron';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) VALUES ('授权视频形象任务', 1, 0, '', 'ai_digital_human_anchor_cron', '', 1, '* * * * * ', NULL, 1747896903, '0', '0', 1744881498, 1744881498, NULL);


ALTER TABLE `la_sv_lead_scraping_record` 
ADD COLUMN `pusher_timer` varchar(255) NULL COMMENT '发布时间' AFTER `exec_time`,
ADD COLUMN `address` varchar(255) NULL COMMENT '地址' AFTER `pusher_timer`;

CREATE TABLE IF NOT EXISTS `la_sv_media_material_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '用户id',
  `name` varchar(255) DEFAULT NULL COMMENT '名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材库分组';

ALTER TABLE  `la_sv_media_material`
    MODIFY COLUMN `size` int(11) UNSIGNED NULL DEFAULT 1 COMMENT '文件大小' ;