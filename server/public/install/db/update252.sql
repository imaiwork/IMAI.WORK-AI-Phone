ALTER TABLE `la_ai_wechat_sop_sub_flow_remind`
MODIFY COLUMN `judgment` int(5) unsigned NOT NULL DEFAULT '0' COMMENT '判断时间(天)';