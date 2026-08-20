CREATE TABLE IF NOT EXISTS `la_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '团队名称',
  `owner_id` int(11) NOT NULL DEFAULT 0 COMMENT '团队主 user_id',
  `seat_limit` int(11) NOT NULL DEFAULT 1 COMMENT '坐席上限(含团队主)',
  `member_count` int(11) NOT NULL DEFAULT 1 COMMENT '当前成员数(含团队主)',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '团队自有域名',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0=禁用 1=正常',
  `oem_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'OEM状态 0=免费版 1=待审核 2=已开通',
  `oem_apply_time` int(11) NOT NULL DEFAULT 0 COMMENT 'OEM申请时间',
  `oem_pay_tokens` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'OEM预缴算力',
  `oem_audit_time` int(11) NOT NULL DEFAULT 0 COMMENT 'OEM审核时间',
  `oem_audit_remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'OEM审核备注',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_owner` (`owner_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_oem_status` (`oem_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团队表';

CREATE TABLE IF NOT EXISTS `la_team_invite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '团队id',
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT '邀请码',
  `inviter_id` int(11) NOT NULL DEFAULT 0 COMMENT '邀请人 user_id',
  `max_uses` int(11) NOT NULL DEFAULT 0 COMMENT '最多使用次数 0=不限',
  `used_count` int(11) NOT NULL DEFAULT 0 COMMENT '已使用次数',
  `expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '过期时间戳 0=永久',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0=停用 1=启用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团队邀请表';

CREATE TABLE IF NOT EXISTS `la_team_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '团队id',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `role` tinyint(1) NOT NULL DEFAULT 1 COMMENT '角色 1=成员 2=团队主 3=管理员',
  `team_tokens` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '企业算力(该企业内划拨,独立于个人算力)',
  `expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '成员到期时间(0=永久)',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `delete_time` int(11) NOT NULL DEFAULT 0 COMMENT '删除时间(0=未删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_team_user` (`team_id`,`user_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团队成员关系表';

-- 既有表补列（列已存在时手工执行请跳过报错）
ALTER TABLE `la_user` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '当前选中企业id(0=个人空间)';
ALTER TABLE `la_user` ADD COLUMN `team_role` tinyint(1) NOT NULL DEFAULT 0 COMMENT '团队角色 0=无/散客 1=成员 2=团队主 3=管理员';
ALTER TABLE `la_user` ADD COLUMN `team_expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '团队成员到期时间戳(0=永久)';
ALTER TABLE `la_user` ADD COLUMN `origin_team_id` int(11) NOT NULL DEFAULT 0 COMMENT 'OEM账号归属站点(0=平台账号,>0=OEM站点专属)';
ALTER TABLE `la_user` ADD INDEX `idx_team_id` (`team_id`);
ALTER TABLE `la_user` ADD INDEX `idx_origin_team` (`origin_team_id`);

ALTER TABLE `la_config` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属团队(0=平台全局)';
ALTER TABLE `la_config` ADD INDEX `idx_team_type_name` (`team_id`, `type`, `name`);

ALTER TABLE `la_card_package` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属团队(0=平台)';
ALTER TABLE `la_card_package` ADD INDEX `idx_team_id` (`team_id`);
ALTER TABLE `la_card_package` ADD COLUMN `expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '套餐卡密有效期至(时间戳,0=永久)';

ALTER TABLE `la_user_tokens_log` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间id(0=个人)' AFTER `user_id`;
ALTER TABLE `la_user_tokens_log` ADD INDEX `idx_team_id` (`team_id`);

ALTER TABLE `la_kb_robot` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间id(0=个人)' AFTER `user_id`;
ALTER TABLE `la_kb_robot` ADD INDEX `idx_team_id` (`team_id`);

ALTER TABLE `la_kb_know` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间id(0=个人)' AFTER `user_id`;
ALTER TABLE `la_kb_know` ADD INDEX `idx_team_id` (`team_id`);

ALTER TABLE `la_knowledge` ADD COLUMN `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '企业空间id(0=个人)' AFTER `user_id`;
ALTER TABLE `la_knowledge` ADD INDEX `idx_team_id` (`team_id`);

ALTER TABLE `la_sv_device_task` ADD COLUMN `team_id` int(11) NULL DEFAULT NULL COMMENT '任务创建时所在企业(NULL=历史未记录,0=个人空间)' AFTER `user_id`;

-- ======================== B. DML（更新器会执行，须幂等） ========================

-- 软删口径：历史 NULL 刷成 0（更新器会执行本 UPDATE）
UPDATE `la_team_member` SET `delete_time` = 0 WHERE `delete_time` IS NULL;

-- 列默认值改为 0，后续 INSERT 不写该字段也会是 0（更新器会过滤 ALTER，需库结构比对或手工执行）
ALTER TABLE `la_team_member`
    MODIFY COLUMN `delete_time` int(11) NOT NULL DEFAULT 0 COMMENT '删除时间(0=未删除)';

-- 已在团队中的用户写入成员关系表
INSERT IGNORE INTO `la_team_member` (`team_id`,`user_id`,`role`,`team_tokens`,`expire_time`,`create_time`,`update_time`,`delete_time`)
SELECT u.team_id, u.id, u.team_role, 0, u.team_expire_time, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0
FROM `la_user` u
WHERE u.team_id > 0 AND u.team_role IN (1, 2, 3);

-- 智能体/知识库未打标资源归入当前企业
UPDATE `la_kb_robot` r
INNER JOIN `la_user` u ON u.id = r.user_id
SET r.team_id = u.team_id
WHERE r.team_id = 0
  AND u.team_id > 0
  AND u.team_role IN (1, 2, 3);

UPDATE `la_kb_know` k
INNER JOIN `la_user` u ON u.id = k.user_id
SET k.team_id = u.team_id
WHERE k.team_id = 0
  AND u.team_id > 0
  AND u.team_role IN (1, 2, 3);

UPDATE `la_knowledge` k
INNER JOIN `la_user` u ON u.id = k.user_id
SET k.team_id = u.team_id
WHERE k.team_id = 0
  AND u.team_id > 0
  AND u.team_role IN (1, 2, 3);

-- 纠正已入团但 user.team_role 仍为散客0
UPDATE `la_user` u
INNER JOIN `la_team_member` m
    ON m.team_id = u.team_id
   AND m.user_id = u.id
   AND (m.delete_time IS NULL OR m.delete_time = 0)
SET u.team_role = m.role,
    u.team_expire_time = IFNULL(m.expire_time, 0)
WHERE u.team_id > 0
  AND m.role IN (1, 2, 3)
  AND (u.team_role <> m.role OR u.team_role = 0);


-- 0) 顶级「营销管理」
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '营销管理', 'el-icon-ShoppingBag', 299, '', 'marketing', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
);

-- 1) 旧 C 页升为「团队管理」目录（尚无目录时）
UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
            ORDER BY (`name` = '营销管理') DESC, (`id` = 438) DESC, `id` ASC
            LIMIT 1
        ) AS _mkt
    ),
    `type` = 'M',
    `name` = '团队管理',
    `icon` = IF(`icon` = '', '', `icon`),
    `perms` = '',
    `paths` = 'team',
    `component` = '',
    `sort` = 50,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND `perms` = 'team.team/lists'
  AND IFNULL(`component`, '') NOT IN ('marketing/team/index', 'marketing/team/oem-apply')
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
            ORDER BY (`name` = '营销管理') DESC, (`id` = 438) DESC, `id` ASC
            LIMIT 1
        ) AS _mkt2
      ) IS NOT NULL
  AND NOT EXISTS (
        SELECT 1 FROM (
            SELECT `id` FROM `la_system_menu` WHERE `type` = 'M' AND `name` = '团队管理' LIMIT 1
        ) AS _exist_dir
      );

-- 已是 M 的「团队管理」校正挂到营销管理
UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
            ORDER BY (`name` = '营销管理') DESC, (`id` = 438) DESC, `id` ASC
            LIMIT 1
        ) AS _mkt
    ),
    `type` = 'M',
    `name` = '团队管理',
    `icon` = IF(`icon` = '', '', `icon`),
    `perms` = '',
    `paths` = 'team',
    `component` = '',
    `sort` = 50,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'M'
  AND `name` = '团队管理'
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
            ORDER BY (`name` = '营销管理') DESC, (`id` = 438) DESC, `id` ASC
            LIMIT 1
        ) AS _mkt2
      ) IS NOT NULL;

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'M', '团队管理', 'el-icon-OfficeBuilding', 50, '', 'team', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND (`name` = '营销管理' OR `id` = 438)
    ORDER BY (`name` = '营销管理') DESC, (`id` = 438) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu` WHERE `type` = 'M' AND `name` = '团队管理'
);

-- 2) 子页「团队列表」
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT d.id, 'C', '团队列表', '', 100, 'team.team/lists', 'list', 'marketing/team/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` d
WHERE d.`type` = 'M' AND d.`name` = '团队管理' AND d.`paths` = 'team'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `component` = 'marketing/team/index' AND `type` = 'C'
  )
LIMIT 1;

UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND `name` = '团队管理' AND `paths` = 'team'
            LIMIT 1
        ) AS _tdir
    ),
    `name` = '团队列表',
    `perms` = 'team.team/lists',
    `paths` = 'list',
    `component` = 'marketing/team/index',
    `sort` = 100,
    `is_show` = 1,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND `component` = 'marketing/team/index'
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND `name` = '团队管理' AND `paths` = 'team'
            LIMIT 1
        ) AS _tdir2
      ) IS NOT NULL;

-- 3) 团队列表按钮
UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'C' AND `component` = 'marketing/team/index'
            LIMIT 1
        ) AS _list
    ),
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'A'
  AND `perms` IN (
    'team.team/create','team.team/detail','team.team/setSeat',
    'team.team/changeStatus','team.team/openOem','team.team/cancelOem','team.team/tenant','team.team/setTenant',
    'team.team/members','team.team/wallet','team.team/delete',
    'team.team/oemPricing','team.team/saveOemPricing'
  )
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'C' AND `component` = 'marketing/team/index'
            LIMIT 1
        ) AS _list2
      ) IS NOT NULL;

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT list.id, 'A', v.name, '', 0, v.perms, '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` list
INNER JOIN (
    SELECT '创建团队' AS name, 'team.team/create' AS perms UNION ALL
    SELECT '详情', 'team.team/detail' UNION ALL
    SELECT '设置坐席', 'team.team/setSeat' UNION ALL
    SELECT '启用停用', 'team.team/changeStatus' UNION ALL
    SELECT '开通OEM', 'team.team/openOem' UNION ALL
    SELECT '取消OEM', 'team.team/cancelOem' UNION ALL
    SELECT '租户配置', 'team.team/tenant' UNION ALL
    SELECT '保存租户配置', 'team.team/setTenant' UNION ALL
    SELECT '成员列表', 'team.team/members' UNION ALL
    SELECT '算力钱包', 'team.team/wallet' UNION ALL
    SELECT '删除团队', 'team.team/delete'
) v ON 1 = 1
WHERE list.`type` = 'C' AND list.`component` = 'marketing/team/index'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms AND m.`type` = 'A');

-- 4) OEM申请列表
INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT d.id, 'C', 'OEM申请列表', '', 90, 'team.team/lists', 'oem-apply', 'marketing/team/oem-apply', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` d
WHERE d.`type` = 'M' AND d.`name` = '团队管理' AND d.`paths` = 'team'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `component` = 'marketing/team/oem-apply' AND `type` = 'C'
  )
LIMIT 1;

UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND `name` = '团队管理' AND `paths` = 'team'
            LIMIT 1
        ) AS _tdir
    ),
    `name` = 'OEM申请列表',
    `perms` = 'team.team/lists',
    `paths` = 'oem-apply',
    `component` = 'marketing/team/oem-apply',
    `sort` = 90,
    `is_show` = 1,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND `component` = 'marketing/team/oem-apply'
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND `name` = '团队管理' AND `paths` = 'team'
            LIMIT 1
        ) AS _tdir2
      ) IS NOT NULL;

UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'C' AND `component` = 'marketing/team/oem-apply'
            LIMIT 1
        ) AS _apply
    ),
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'A'
  AND `perms` IN ('team.team/getInfo', 'team.team/oemReview')
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'C' AND `component` = 'marketing/team/oem-apply'
            LIMIT 1
        ) AS _apply2
      ) IS NOT NULL;

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT apply.id, 'A', v.name, '', 0, v.perms, '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` apply
INNER JOIN (
    SELECT '剩余名额' AS name, 'team.team/getInfo' AS perms UNION ALL
    SELECT 'OEM审核', 'team.team/oemReview'
) v ON 1 = 1
WHERE apply.`type` = 'C' AND apply.`component` = 'marketing/team/oem-apply'
  AND NOT EXISTS (SELECT 1 FROM `la_system_menu` m WHERE m.`perms` = v.perms AND m.`type` = 'A');

-- 5) OEM授权管理迁入团队管理
UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND `name` = '团队管理' AND `paths` = 'team'
            LIMIT 1
        ) AS _tdir
    ),
    `paths` = 'oem',
    `sort` = 80,
    `update_time` = UNIX_TIMESTAMP()
WHERE `perms` = 'marketing.oem/auth'
  AND `type` = 'C'
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND `name` = '团队管理' AND `paths` = 'team'
            LIMIT 1
        ) AS _tdir2
      ) IS NOT NULL;

-- 6) 财务管理 → OEM收费配置
DELETE FROM `la_system_menu`
WHERE `type` = 'A' AND `perms` IN ('team.team/oemPricing', 'team.team/saveOemPricing');

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT 0, 'M', '财务管理', 'local-icon-user_gaikuang', 899, '', 'finance', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE `type` = 'M' AND (`name` = '财务管理' OR `id` = 166)
);

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT p.id, 'C', 'OEM收费配置', '', 50, 'oem.oem/oemPricing', 'oem_pricing', 'finance/oem_pricing/index', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM (
    SELECT id FROM `la_system_menu`
    WHERE `type` = 'M' AND (`name` = '财务管理' OR `id` = 166)
    ORDER BY (`name` = '财务管理') DESC, (`id` = 166) DESC, `id` ASC
    LIMIT 1
) p
WHERE NOT EXISTS (
    SELECT 1 FROM `la_system_menu`
    WHERE (`component` = 'finance/oem_pricing/index' OR `perms` = 'oem.oem/oemPricing') AND `type` = 'C'
);

UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND (`name` = '财务管理' OR `id` = 166)
            ORDER BY (`name` = '财务管理') DESC, (`id` = 166) DESC, `id` ASC
            LIMIT 1
        ) AS _fin
    ),
    `name` = 'OEM收费配置',
    `perms` = 'oem.oem/oemPricing',
    `paths` = 'oem_pricing',
    `component` = 'finance/oem_pricing/index',
    `sort` = 50,
    `is_show` = 1,
    `is_disable` = 0,
    `update_time` = UNIX_TIMESTAMP()
WHERE `type` = 'C'
  AND (`component` = 'finance/oem_pricing/index' OR `perms` = 'oem.oem/oemPricing')
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'M' AND (`name` = '财务管理' OR `id` = 166)
            ORDER BY (`name` = '财务管理') DESC, (`id` = 166) DESC, `id` ASC
            LIMIT 1
        ) AS _fin2
      ) IS NOT NULL;

UPDATE `la_system_menu`
SET `pid` = (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'C' AND `component` = 'finance/oem_pricing/index'
            LIMIT 1
        ) AS _page
    ),
    `update_time` = UNIX_TIMESTAMP()
WHERE `perms` = 'oem.oem/saveOemPricing'
  AND `type` = 'A'
  AND (
        SELECT `id` FROM (
            SELECT `id` FROM `la_system_menu`
            WHERE `type` = 'C' AND `component` = 'finance/oem_pricing/index'
            LIMIT 1
        ) AS _page2
      ) IS NOT NULL;

INSERT INTO `la_system_menu`
  (`pid`, `type`, `name`, `icon`, `sort`, `perms`, `paths`, `component`, `selected`, `params`, `is_cache`, `is_show`, `is_disable`, `create_time`, `update_time`)
SELECT page.id, 'A', '保存', '', 0, 'oem.oem/saveOemPricing', '', '', '', '', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `la_system_menu` page
WHERE page.`type` = 'C' AND page.`component` = 'finance/oem_pricing/index'
  AND NOT EXISTS (
      SELECT 1 FROM `la_system_menu` WHERE `perms` = 'oem.oem/saveOemPricing' AND `type` = 'A'
  )
LIMIT 1;

UPDATE `la_dev_crontab` SET    `expression` = '*/8 * * * *'   WHERE `command` = 'auto_video_synthesis';