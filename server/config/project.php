<?php
return [
    // 系统版本号
    'version'                  => 'v2.13.6',
    'version_number'           => '21306',
    // 官网
    'website'               => [
        'name'        => env('project.web_name', ''), // 网站名称
        'url'         => env('project.web_url', ''), // 网站地址
        'domain'      => env('project.domain', ''), // 网站域名
        'login_image' => 'resource/image/adminapi/default/login_image.png',
        'web_logo'    => 'resource/image/adminapi/default/web_logo.jpg', // 网站logo
        'web_favicon' => 'resource/image/adminapi/default/web_favicon.ico', // 网站图标
        'shop_name'   => 'AI获客系统', // 商城名称
        'shop_logo'   => 'resource/image/adminapi/default/shop_logo.png', // 商城图标
        'pc_logo'     => 'resource/image/adminapi/default/pc_logo.png', // pc_logo
        'pc_ico'      => 'resource/image/adminapi/default/web_favicon.ico', // pc_ico
        'pc_title'    => 'AI获客系统', // PC网站标题
        'h5_favicon'  => 'resource/image/adminapi/default/web_favicon.ico', // 网站图标
    ],

    // 后台登录
    'admin_login'           => [
        // 管理后台登录限制 0-不限制 1-需要限制
        'login_restrictions'   => 0,
        // 限制密码错误次数
        'password_error_times' => 5,
        // 限制禁止多少分钟不能登录
        'limit_login_time'     => 30,
    ],

    // 唯一标识，密码盐、路径加密等
    'unique_identification' => env('project.unique_identification', '1234'),

    // 后台管理员token（登录令牌）配置
    'admin_token'           => [
        'expire_duration'    => 3600 * 24 * 30, //管理后台token过期时长(单位秒）
        'be_expire_duration' => 3600, //管理后台token临时过期前时长，自动续期
    ],

    // 商城用户token（登录令牌）配置
    'user_token'            => [
        'expire_duration'    => 3600 * 24 * 30, //用户token过期时长(单位秒）
        'be_expire_duration' => 3600, //用户token临时过期前时长，自动续期
    ],

    // 列表页
    'lists'                 => [
        'page_size_max' => 25000, //列表页查询数量限制（列表页每页数量、导出每页数量）
        'page_size'     => 25, //默认每页数量
    ],

    // AI 找素材候选相关性闸门（保守负向过滤：候选有标题类文本且与搜索词无任何文字交集时才拒绝）
    'ai_material_relevance' => [
        'enabled'         => true, //文本闸门总开关，false 时所有候选一律放行
        'min_hits'        => 2,    //全局兜底：具体主体类（实体/菜品/场景/动作）未单独配置时的最低二元组命中数
        'min_ratio'       => 0.4,  //全局兜底：具体主体类未单独配置时的最低命中比例，长关键词按比例提高门槛
        'min_consecutive' => 0,    //最少连续命中二元组数，0=不启用；调高可减少零散字面交集误放行
        'vision_enabled'  => true, //画面闸门总开关：转存成功后（视频在下载前用封面）用视觉模型判图
        'vision_timeout'  => 8,    //单次判图请求超时（秒），超时/异常/无结果按放行处理并记日志
        // 分类型文本闸门阈值：key 为关键词类型（entity 实体 / dish 菜品 / scene 场景 / action 动作 /
        // person 人物 / brand 品牌 / mood 氛围 / abstract 抽象），未列出的类型按 entity 处理
        'keyword_type_thresholds' => [
            'entity'   => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],  //具体物件，必须有字面证据
            'dish'     => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],  //菜品食品，同实体
            'scene'    => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],  //场景场所，同实体
            'action'   => ['min_hits' => 2, 'min_ratio' => 0.4, 'text_enabled' => true],  //动作过程，同实体
            'person'   => ['min_hits' => 1, 'min_ratio' => 0.3, 'text_enabled' => true],  //人物人群，呈现方式多样，放宽
            'brand'    => ['min_hits' => 1, 'min_ratio' => 1.0, 'text_enabled' => true],  //品牌：品牌名必须原文出现（实际走原文包含，不看二元组阈值）
            'mood'     => ['min_hits' => 0, 'min_ratio' => 0.0, 'text_enabled' => false], //情绪氛围，无字面主体，交给画面闸门
            'abstract' => ['min_hits' => 0, 'min_ratio' => 0.0, 'text_enabled' => false], //抽象概念，同上
        ],
    ],

    // AI 找素材词级账本与预算（v4 方案）：总开关关闭时全链路保持旧行为，仅作为灰度开关
    'ai_material_grab' => [
        'enabled'                    => false, //总开关：false=完全走旧逻辑（词循环不带账本、不启用泛词打底）
        'generic_ratio'              => 0.3,   //词集合中泛词打底最低占比（0~1），仅供开启后抽词供给侧校验用
        'max_candidates_per_keyword' => 2,     //单个关键词每类型最多尝试的候选数，0=不限（旧的数字口播链路口径）
        'max_transcode_fail'         => 3,     //连续转存失败熔断阈值，0=不熔断
        'max_transcode_sec'          => 15,    //单笔下载/转码硬超时（秒），0=不启用（仅日志检查点）
        'max_keyword_elapsed_sec'    => 25,    //词级墙钟预算（秒），0=不启用
        'max_elapsed_sec'            => 60,    //抓取阶段全局墙钟预算（秒），0=不启用；素材混剪等大缺口场景按 90 计算
    ],

    // 素材检索关键词规划：把叙事短语改写成素材库能检索的具体画面查询词
    'material_keyword_plan' => [
        'enabled'        => true, //是否用对话模型改写关键词；false 时只走纯规则改写
        'model'          => '',   //改写模型别名，空=默认 deepseek
        'timeout'        => 10,   //模型改写请求超时（秒），剩余时间不足 3 秒时自动跳过
        'max_alternates' => 1,    //每个关键词最多追加的备选查询数（主查询之外）
        'max_keywords'   => 30,   //单次规划最多处理的原始关键词数量
    ],

    // 各种默认图片
    'default_image'         => [
        'admin_avatar'     => 'resource/image/adminapi/default/avatar.png',
        'user_avatar'      => 'resource/image/adminapi/default/default_avatar.png',
        'qq_group'         => 'resource/image/adminapi/default/qq_group.png', // qq群
        'customer_service' => 'resource/image/adminapi/default/customer_service.jpg', // 客服
        'menu_admin'       => 'resource/image/adminapi/default/menu_admin.png', // 首页快捷菜单-管理员
        'menu_role'        => 'resource/image/adminapi/default/menu_role.png', // 首页快捷菜单-角色
        'menu_dept'        => 'resource/image/adminapi/default/menu_dept.png', // 首页快捷菜单-部门
        'menu_dict'        => 'resource/image/adminapi/default/menu_dict.png', // 首页快捷菜单-字典
        'menu_generator'   => 'resource/image/adminapi/default/menu_generator.png', // 首页快捷菜单-代码生成器
        'menu_auth'        => 'resource/image/adminapi/default/menu_auth.png', // 首页快捷菜单-菜单权限
        'menu_web'         => 'resource/image/adminapi/default/menu_web.png', // 首页快捷菜单-网站信息
        'menu_file'        => 'resource/image/adminapi/default/menu_file.png', // 首页快捷菜单-素材中心
    ],

    // 文件上传限制 (图片)
    'file_image'            => [
        'jpg',
        'png',
        'gif',
        'jpeg',
        'webp',
        'ico'
    ],

    // 文件上传限制 (视频)
    'file_video'            => [
        'wmv',
        'avi',
        'mpg',
        'mpeg',
        '3gp',
        'mov',
        'mp4',
        'flv',
        'f4v',
        'rmvb',
        'mkv'
    ],

    //上传文件的格式 (文件)
    'file_file'             => [
        'zip',
        'rar',
        'txt',
        'pdf',
        'docx',
        'doc',
        'wps',
        'xls',
        'md',
        'xlsx',
        'ppt',
        'pptx',
        'csv',
        'ftr',
        '7z',
        'gz',
        'jpg',
        'bmp',
        'png',
        'gif',
        'jpeg',
        'webp',
        'ico',
        'json',
        'jsonl',
        'mp3',
        'mp4',
        'm4a',
        'wav',
        'wma',
        'speex',
        'aac',
        'amr',
        'mkv',
        'mpeg',
        'avi',
        'mov',
        'ogg',
        'opus',
        'flv',
        'f4v',
        'flac',
        'webm',
        'rmvb',
        'wmv',
        'excel',
        'xml',
        'aiff',
        'html'

    ],
    //上传文件的格式 (音频)
    'file_audio'            => [
        'mp3',
        'pcm',
        'wav',
        'm4a',
        'wma',
        'speex',
        'ogg',
        'amr',
        'flac',
        'aiff',
        'aac'
    ],
    //上传文件的格式 (csv)
    'csv_file'            => [
        'csv'
    ],
    //上传文件的格式 (zip)
    'zip_file'            => [
        'zip'
    ],

    // 登录设置
    'login'                 => [
        // 登录方式：1-账号密码登录；2-手机短信验证码登录；3-第三方登录；4-小程序授权PC登录
        'login_way'       => ['1', '2', '3', '4'],
        // 注册强制绑定手机 0-关闭 1-开启
        'coerce_mobile'   => 1,
        // 第三方授权登录 0-关闭 1-开启
        'third_auth'      => 1,
        // 微信授权登录 0-关闭 1-开启
        'wechat_auth'     => 1,
        // qq授权登录 0-关闭 1-开启
        'qq_auth'         => 0,
        // 登录政策协议 0-关闭 1-开启
        'login_agreement' => 1,
    ],

    // 后台装修
    'decorate'              => [
        // 底部导航栏样式设置
        'tabbar_style' => ['default_color' => '#999999', 'selected_color' => '#0065FB'],
        // 底部导航默认数据（数据库无数据时返回）
        // link.type 取值：page=小程序页面链接, webview=web-view链接, miniapp=其他小程序
        'tabbar_default' => [
            [
                'name'       => 'AI手机',
                'selected'   => 'static/images/mp/tabbs/phone_s.png',
                'unselected' => 'static/images/mp/tabbs/phone.png',
                'link'       => ['path' => '/pages/index/index', 'name' => 'AI手机', 'type' => 'page'],
                'is_show'    => 1,
            ],
            [
                'name'       => 'AI助手',
                'selected'   => 'static/images/mp/tabbs/chat_s.png',
                'unselected' => 'static/images/mp/tabbs/chat.png',
                'link'       => ['path' => '/packages_core/pages/chat/chat', 'name' => 'AI助手', 'type' => 'page'],
                'is_show'    => 1,
            ],
            [
                'name'       => 'AI创作',
                'selected'   => 'static/images/mp/tabbs/creative_s.png',
                'unselected' => 'static/images/mp/tabbs/creative.png',
                'link'       => ['path' => '/ai_modules/digital_human/pages/index/index', 'name' => 'AI创作', 'type' => 'page'],
                'is_show'    => 1,
            ],
            [
                'name'       => '我的',
                'selected'   => 'static/images/mp/tabbs/me_s.png',
                'unselected' => 'static/images/mp/tabbs/me.png',
                'link'       => ['path' => '/packages/pages/user/user', 'name' => '我的', 'type' => 'page'],
                'is_show'    => 1,
            ],
        ],
    ],

    // 1:普通聊天 2:画图 3:会议妙计 4:思维导图 5:音乐 6:场景聊天
];
