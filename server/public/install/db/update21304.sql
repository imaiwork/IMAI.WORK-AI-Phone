-- ================ 小程序分包调整：首页 / AI 助手页面迁入 packages_core 分包 ================
-- 底部导航 link 字段存的是页面路径 JSON，旧路径 /packages/pages/chat/chat、/packages/pages/home/home
-- 在新版小程序中已不存在，需同步改写；可重复执行。
UPDATE `la_decorate_tabbar`
SET `link` = REPLACE(`link`, '/packages/pages/chat/chat', '/packages_core/pages/chat/chat'),
    `update_time` = UNIX_TIMESTAMP()
WHERE `link` LIKE '%/packages/pages/chat/chat%';

UPDATE `la_decorate_tabbar`
SET `link` = REPLACE(`link`, '/packages/pages/home/home', '/packages_core/pages/home/home'),
    `update_time` = UNIX_TIMESTAMP()
WHERE `link` LIKE '%/packages/pages/home/home%';
