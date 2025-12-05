


DELETE FROM `la_config` WHERE `name` = 'banner' and `type` = 'digital_human';
INSERT INTO `la_config` ( `type`, `name`, `value`, `create_time`, `update_time`) VALUES ('digital_human', 'banner', 'static/images/human/banner.jpeg', 1760780862, 1763370311);




ALTER TABLE `la_human_anchor`
    ADD COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因';

ALTER TABLE `la_human_audio`
    ADD COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因';

ALTER TABLE `la_human_voice`
    ADD COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因';

ALTER TABLE `la_human_video`
    ADD COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因';

UPDATE `la_models` SET `name` = 'Gemini 2.5 pro' WHERE `id` = 11;
UPDATE `la_models` SET `name` = 'Gemma 3' WHERE `id` = 14;
UPDATE `la_models_cost` SET `name` = 'Gemini 2.5 pro' WHERE `id` = 11;
UPDATE `la_models_cost` SET `name` = 'Gemma 3' WHERE `id` = 14;
UPDATE `la_config` SET  `value` =  '{"channel":[{"id":"1","name":"DeepSeek","model_id":4,"model_sub_id":4,"status":"1","logo":"static/images/models/1.png"},{"id":"7","name":"gpt-4","model_id":15,"model_sub_id":15,"status":"1","logo":"static/images/models/3.png"},{"id":"2","name":"gpt-4o","model_id":2,"model_sub_id":2,"status":"1","logo":"static/images/models/3.png"},{"id":"8","name":"gpt-4o-mini","model_id":16,"model_sub_id":16,"status":"1","logo":"static/images/models/3.png"},{"id":"9","name":"gpt-3.5-turbo","model_id":17,"model_sub_id":17,"status":"1","logo":"static/images/models/3.png"},{"id":"3","name":"Gemini 2.5 pro","model_id":11,"model_sub_id":11,"status":"1","logo":"static/images/models/2.png"},{"id":"6","name":"Gemma 3","model_id":14,"model_sub_id":14,"status":"1","logo":"static/images/models/2.png"}]}'
WHERE `type` = 'chat' AND `name` = 'ai_model';


ALTER TABLE `la_oem` 
ADD COLUMN `site_logo` varchar(255) NULL COMMENT '站点logo' AFTER `logo_url`;