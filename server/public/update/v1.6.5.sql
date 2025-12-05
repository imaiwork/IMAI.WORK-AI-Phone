
UPDATE `la_model_config` SET `description` = '每900字约消耗1算力' WHERE `scene` = 'common_chat';

UPDATE `la_model_config` SET `description` = '每900字约消耗1算力' WHERE `scene` = 'scene_chat';

UPDATE `la_model_config` SET `description` = '每900字约消耗1算力' WHERE `scene` = 'human_prompt';

UPDATE `la_model_config` SET `description` = '（数字人尊享版）每次合成视频时，1秒约消耗2算力' WHERE `scene` = 'human_video_ymt';

UPDATE `la_model_config` SET `description` = '文生图每张图片约消耗30算力' WHERE `scene` = 'text_to_image';

UPDATE `la_model_config` SET `description` = '图生图每张图片约消耗30算力' WHERE `scene` = 'image_to_image';

UPDATE `la_model_config` SET `description` = '商品图每张图片约消耗30算力' WHERE `scene` = 'goods_image';

UPDATE `la_model_config` SET `description` = '模特换衣每张图片约消耗80算力' WHERE `scene` = 'model_image';

UPDATE `la_model_config` SET `description` = '每900字约消耗1算力' WHERE `scene` = 'image_prompt';

UPDATE `la_model_config` SET `description` = '每900字约消耗1算力' WHERE `scene` = 'mind_map';

UPDATE `la_model_config` SET `description` = 'AI陪练每次约消耗20算力' WHERE `scene` = 'lianlian';


INSERT INTO `la_config` (`type`, `name`, `value`, `create_time`, `update_time`) VALUES ('digital_human', 'privacy', '\"<p>IMAIWORK数字人克隆服务法律申明</p><p> 尊敬的用户：欢迎使用IMAIWORK数字人克隆服务。在您使用本软件进行服务之前，请仔细阅读以下法律申明。</p><p><br></p><p> 一、数据采集与使用</p><p> 我们将根据您主动的操作合法、正当、必要的原则采集您提供的用于数字人克隆的数据，包括但不限于您的视频、声音和其它必要的数据。采集的数据仅用于为您创建数字人克隆，我们保证不会用于其他任何未经您授权的目的。</p><p><br></p><p> 二、用户权利与义务</p><p> 您有权了解您的数据被处理的情况，并有权通过软件自行更正或删除不准确的数据。</p><p> 您有义务确保您提供的数据的真实性、合法性和完整性，不得提供虚假、违法或侵权的数据。</p><p> 提供上传的素材不得侵犯他人的合法权益，包括但不限于肖像权、形象权、声音音色等。未经被克隆对象本人或其家属的明确授权，进行数字克隆导致的一切法律责任由用户自行承担，平台不承担任何责任。</p><p> 生成的视频应确保内容真实、合法、合规，不得利用该服务进行违法活动。</p><p><br></p><p> 三、知识产权</p><p> 用户在使用本服务过程中产生的所有数字人克隆内容，包括但不限于视频、音频、文本等，其知识产权归用户所有。IMAIWORK仅提供技术服务，不拥有上述内容的任何权利。</p><p> 用户应保证其上传的素材不侵犯任何第三方的知识产权，包括但不限于版权、商标权、专利权等。若因用户上传的素材引发知识产权纠纷，用户应自行承担全部责任。</p><p> IMAIWORK保留对服务技术、软件及相关文档的知识产权，用户不得擅自复制、修改、传播或用于其他商业目的。</p><p><br></p><p> 四、服务限制与免责声明</p><p> IMAIWORK提供的数字人克隆服务可能存在一定的局限性，包括但不限于技术限制、数据采集限制等，用户应理解并接受这些限制。</p><p> IMAIWORK不对用户上传的数据的准确性、完整性、合法性承担任何责任，用户应自行负责。</p><p> IMAIWORK不保证服务的连续性、稳定性、安全性，对于因不可抗力、网络故障、计算机病毒等非IMAIWORK原因导致的服务中断或数据丢失，IMAIWORK不承担责任。</p><p><br></p><p> 五、用户行为规范</p><p> 用户在使用IMAIWORK数字人克隆服务时，应遵守相关法律法规，不得利用服务进行违法活动，包括但不限于诽谤、侵犯他人隐私、传播淫秽物品等。</p><p> 用户不得利用服务进行任何形式的欺诈、误导或虚假宣传。</p><p> 用户不得利用服务进行任何形式的歧视、骚扰或侵犯他人权益的行为。</p><p> 六、隐私保护</p><p><br></p><p> IMAIWORK重视用户隐私保护，将采取必要措施保护用户个人信息的安全。</p><p> IMAIWORK将遵循相关法律法规和行业标准，对用户数据进行加密存储和处理，防止数据泄露。</p><p> IMAIWORK不会在未经用户同意的情况下，向第三方披露、出售或出租用户的个人信息。</p><p><br></p><p> 七、违约责任</p><p> 如用户违反本协议的任何条款，IMAIWORK有权立即终止服务，并要求用户赔偿因此造成的损失。</p><p> 如因用户违反本协议导致第三方对IMAIWORK提起诉讼或索赔，用户应负责解决并赔偿IMAIWORK因此遭受的所有损失。</p><p><br></p><p> 八、协议的修改和终止</p><p> IMAIWORK有权根据业务发展需要，随时修改本协议的条款。修改后的协议将在服务页面上公布，并立即生效。</p><p> 用户如不同意修改后的协议，应立即停止使用服务。继续使用服务视为接受修改后的协议。</p><p> IMAIWORK有权在任何时候单方面终止服务，并不承担因此给用户带来的任何损失。</p><p><br></p><p> 九、争议解决</p><p> 本协议的解释、适用及争议解决均适用中华人民共和国法律。</p><p> 因本协议引起的或与本协议有关的任何争议，双方应首先通过友好协商解决；协商不成时，任何一方均可向IMAIWORK所在地的人民法院提起诉讼。</p><p><br></p><p> 十、其他</p><p> 本协议构成双方之间关于IMAIWORK数字人克隆服务的完整协议，并取代双方之前关于该服务的所有口头或书面协议。</p><p> 如果本协议的任何条款被有管辖权的法院认定为无效或不可执行，其余条款仍然有效。</p><p> 除非另有书面明确约定，用户同意不将本协议项下的权利或义务转让给任何第三方。</p><p> 请仔细阅读上述条款，并在确认无误后继续使用我们的服务。感谢您的理解与合作。</p><p><br></p><p> IMAIWORK团队敬上</p><p><br></p><p><br></p>\"', 1743509615, 1747102195);

UPDATE `la_scene` SET `update_time` = 1746780765, `delete_time` = 1746780765 WHERE `id` = 10;
UPDATE `la_scene` SET `update_time` = 1746784273, `delete_time` = 1746784273 WHERE `id` = 16;
UPDATE `la_scene` SET `update_time` = 1746784313, `delete_time` = 1746784313 WHERE `id` = 55;
UPDATE `la_scene` SET `logo` = 'static/images/20241211150932e8c0b5251.png', `update_time` = 1740645904 WHERE `id` = 61;



UPDATE `la_hd_image_cases` SET `delete_time` = 1746783745 WHERE `id` = 43;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/202505091800393ea205513.png\", \"static/images/20250509180039b8a235909.png\", \"static/images/20250509174146cbdfe0372.png\"]}', `result_image` = 'static/images/20250107144115837193050.png', `update_time` = 1746784844 WHERE `id` = 44;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/202505091758412a1f23022.png\", \"static/images/20250509175841a8ae48822.png\", \"static/images/202501071443544fe062371.png\"]}', `result_image` = 'static/images/202501071444017c41e2601.png', `update_time` = 1746784728 WHERE `id` = 45;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/202505091755129c19f3932.png\", \"static/images/202505091737449d9543384.png\", \"\"]}', `result_image` = 'static/images/202501071448136ce315079.png', `update_time` = 1746784515 WHERE `id` = 46;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/20250509175430aba557640.png\", \"static/images/2025050917362870b9d7361.png\", \"\"]}', `result_image` = 'static/images/20250107144926ebdd69712.png', `update_time` = 1746784476 WHERE `id` = 47;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/20250509175356339733645.png\", \"static/images/202505091735064e7cf5088.png\", \"\"]}', `result_image` = 'static/images/202501071459366e9142929.png',  `update_time` = 1746784438 WHERE `id` = 48;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/2025050917502344eb10577.png\", \"static/images/202505091733058989a7185.jpg\", \"\"]}', `result_image` = 'static/images/202501071500111e0ab6257.png',  `update_time` = 1746784230 WHERE `id` = 49;
UPDATE `la_hd_image_cases` SET `params` = '{\"text\": \"\", \"images\": [\"static/images/2025050917480954b603491.png\", \"/static/images/20250107150047312f23598.png\", \"\"]}', `result_image` = 'static/images/20250107150057cc6b07969.png', `update_time` = 1746784093 WHERE `id` = 50;

DELETE FROM `la_hd_image_cases` where `id` = 83;
INSERT INTO `la_hd_image_cases` (`id`, `user_id`, `case_type`, `params`, `result_image`, `status`, `create_time`, `update_time`, `delete_time`) VALUES (83, 0, 1, '{\"text\": \"\", \"images\": [\"static/images/20250509174716b3a506159.png\", \"static/images/2025050917440094bb84982.png\", \"\"]}', 'static/images/20250509174359060ad6778.jpg', 1, 1746783908, 1746784039, NULL);
