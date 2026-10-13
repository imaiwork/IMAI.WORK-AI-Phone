-- ================ 小程序分包调整：首页 / AI 助手页面迁入 packages_core 分包 ================
-- link 由后台保存时写入 JSON，斜杠在库里是 \/。按 path 字段匹配，可重复执行。
UPDATE `la_decorate_tabbar`
SET `link` = JSON_SET(`link`, '$.path', '/packages_core/pages/chat/chat'),
    `update_time` = UNIX_TIMESTAMP()
WHERE JSON_VALID(`link`)
  AND JSON_UNQUOTE(JSON_EXTRACT(`link`, '$.path')) = '/packages/pages/chat/chat';

UPDATE `la_decorate_tabbar`
SET `link` = JSON_SET(`link`, '$.path', '/packages_core/pages/home/home'),
    `update_time` = UNIX_TIMESTAMP()
WHERE JSON_VALID(`link`)
  AND JSON_UNQUOTE(JSON_EXTRACT(`link`, '$.path')) = '/packages/pages/home/home';