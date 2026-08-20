INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 22, 22, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 23, 23, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 24, 24, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 25, 25, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 26, 26, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 27, 27, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 28, 28, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

INSERT INTO `la_models_setting` (`user_id`, `model_id`, `model_sub_id`, `top_p`, `temperature`, `presence_penalty`,
                                 `frequency_penalty`, `max_tokens`, `context_num`, `is_default`, `create_time`,
                                 `update_time`, `delete_time`)
VALUES (0, 29, 29, 1, 0.8, 0, 0, 1024, 3, 0, 1757554814, 1757554814, NULL);

DELETE FROM `la_dev_crontab` WHERE `command` = 'init_persona_template';
INSERT INTO `la_dev_crontab` (`name`, `type`, `system`, `remark`, `command`, `params`, `status`, `expression`, `error`, `last_time`, `time`, `max_time`, `create_time`, `update_time`, `delete_time`) 
VALUES ('设置人设初始工作流', 1, 0, '', 'init_persona_template', '', 1, '*/5 * * * *', '', 1780140307, '0', '0.26', 1780113718, 1780120883, NULL);
