<?php


namespace app\api\logic;


use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\article\Article;
use app\common\model\decorate\DecoratePage;
use app\common\model\decorate\DecorateTabbar;
use app\common\model\human\HumanVoice;
use app\common\model\pay\PayConfig;
use app\common\service\chat\ChatModelsService;
use app\common\service\ConfigService;
use app\common\service\draw\MediaModelsService;
use app\common\service\FileService;
use app\common\service\transcoding\OssMediaProcessService;
use app\common\service\UserDisplaySanitizer;


/**
 * index
 * Class IndexLogic
 * @package app\api\logic
 */
class IndexLogic extends BaseLogic
{

    /**
     * @notes 首页数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:15
     */
    public static function getIndexData()
    {
        // 装修配置
        $decoratePage = DecoratePage::findOrEmpty(1);

        // 首页文章
        $field = [
            'id',
            'title',
            'desc',
            'abstract',
            'image',
            'author',
            'click_actual',
            'click_virtual',
            'create_time'
        ];

        $article = Article::field($field)
            ->where(['is_show' => 1])
            ->order(['id' => 'desc'])
            ->limit(20)->append(['click'])
            ->hidden(['click_actual', 'click_virtual'])
            ->select()->toArray();

        return [
            'page' => $decoratePage,
            'article' => $article
        ];
    }


    /**
     * @notes 获取政策协议
     * @param string $type
     * @return array
     * @author 段誉
     * @date 2022/9/20 20:00
     */
    public static function getPolicyByType(string $type)
    {
        return [
            'title' => ConfigService::get('agreement', $type . '_title', ''),
            'content' => ConfigService::get('agreement', $type . '_content', ''),
        ];
    }


    /**
     * @notes 装修信息
     * @param $id
     * @return array
     * @author 段誉
     * @date 2022/9/21 18:37
     */
    public static function getDecorate($id)
    {
        return DecoratePage::field(['type', 'name', 'data', 'meta'])
            ->findOrEmpty($id)->toArray();
    }


    /**
     * @notes 获取配置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:38
     */
    public static function getConfigData()
    {
        // 底部导航
        $tabbar = DecorateTabbar::getTabbarLists();
        // 导航颜色
        $style = ConfigService::get('tabbar', 'style', config('project.decorate.tabbar_style'));
        // 登录配置
        $loginConfig = [
            // 登录方式
            'login_way' => ConfigService::get('login', 'login_way', config('project.login.login_way')),
            // 注册强制绑定手机
            'coerce_mobile' => ConfigService::get('login', 'coerce_mobile', config('project.login.coerce_mobile')),
            // 政策协议
            'login_agreement' => ConfigService::get('login', 'login_agreement', config('project.login.login_agreement')),
            // 第三方登录 开关
            'third_auth' => ConfigService::get('login', 'third_auth', config('project.login.third_auth')),
            // 微信授权登录
            'wechat_auth' => ConfigService::get('login', 'wechat_auth', config('project.login.wechat_auth')),
            // qq授权登录
            'qq_auth' => ConfigService::get('login', 'qq_auth', config('project.login.qq_auth')),
        ];

        //模型
        $modelList =  HumanVoice::getModelList();
        $hdList = ConfigService::get('hd', 'list', []);

        $indexConfig =  ConfigService::get('index', 'config', []);
        $banner =  config('app.app_host') . '/static/images/human/banner.png';

        // 网址信息
        $website = [
            'h5_favicon' => FileService::getFileUrl(ConfigService::get('website', 'h5_favicon')),
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),
            'pc_logo' => FileService::getFileUrl(ConfigService::get('website', 'pc_logo')),
            // 登录页
            'login_image' => FileService::getFileUrl(ConfigService::get('website', 'login_image')),
            'shop_title' => ConfigService::get('website', 'shop_title', 'AI时代，企业化AI工具的新星'),

            'customer_service' => self::getCustomerService(),
            'client_download'                 => [
                'windows' =>  ConfigService::get('client_download', 'windows', ''),
                'mac_intel' =>  ConfigService::get('client_download', 'mac_intel', ''),
                'mac_apple' =>  ConfigService::get('client_download', 'mac_apple', ''),
                'android' =>  ConfigService::get('client_download', 'android', ''),
                'mini_programs' =>  ConfigService::get('client_download', 'mini_programs', ''),
                'h5' =>  ConfigService::get('client_download', 'h5', ''),
            ],
        ];

        // H5配置
        $webPage = [
            // 渠道状态 0-关闭 1-开启
            'status' => ConfigService::get('web_page', 'status', 1),
            // 关闭后渠道后访问页面 0-空页面 1-自定义链接
            'page_status' => ConfigService::get('web_page', 'page_status', 0),
            // 自定义链接
            'page_url' => ConfigService::get('web_page', 'page_url', ''),
            'url' => request()->domain() . '/mobile'
        ];

        $version = ConfigService::get('website', 'version', []);

        //会议纪要配置
        $meetingConfig = self::getMeetingConfig();

        //练练
        $lianlian = self::getLianLianConfig();

        //小程序分享配置
        $shareImage = ConfigService::get('website', 'share_image', '');
        $shareImage = empty($shareImage) ? $shareImage : FileService::getFileUrl($shareImage);
        $mnpShareConfig =  [
            'share_title'   => ConfigService::get('website', 'share_title', ''),
            'share_desc'    => ConfigService::get('website', 'share_desc', ''),
            'share_image'   => $shareImage,
        ];

        //大语言模型
        $chatModels = ChatModelsService::getChannelList();
        // 生图/生视频模型（与 PC PcLogic.draw_model 同源）
        $drawModels = MediaModelsService::getChannelList();
        //视频案例
        $videoCases = ConfigService::get('digital_human', 'video_case', []);
        foreach ($videoCases as $key => $videoCase) {
            $videoCases[$key]['image'] = FileService::getFileUrl($videoCase['image']);
            $videoCases[$key]['video_case_url'] = FileService::getFileUrl($videoCase['video_case_url']);
        }

        // 团队 OEM 站点用品牌管理的备案号/企业名称覆盖平台 copyright
        $copyright = ConfigService::get('copyright', 'config', '');
        $oemCopyright = \app\api\logic\TeamLogic::siteCopyright();
        if ($oemCopyright !== null) {
            $copyright = $oemCopyright;
        }

        $siteClosed = \app\api\logic\TeamLogic::currentRequestSiteClosed() ? 1 : 0;
        return [
            'is_robot_show' => ConfigService::get('assistants', 'is_robot_show', 0),
            'copyright' => $copyright,
            //            'domain' => FileService::getFileUrl(),
            'domain' => config('app.app_host') . '/',
            'site_closed' => $siteClosed,
            'style' => $style,
            'tabbar' => $tabbar,
            'login' => $loginConfig,
            'register' => LoginLogic::getRegisterMode(),
            'website' => $website,
            // 与后台「媒体处理」一致：仅阿里云 + OSS 切割/转码(MPS) 且配置完整时为 true
            'is_oss_transcode' => OssMediaProcessService::isEnabled(),
            'webPage' => $webPage,
            'index_config' => $indexConfig,
            'version' => $version,
            'meeting_config' => $meetingConfig,
            'lianlian' => $lianlian,
            'mnp_share_config' => $mnpShareConfig,
            'digital_human' => [
                'privacy' => ConfigService::get('digital_human', 'privacy', []),
                'voice' => $modelList['voice'] ?? [],
                'shanjian_auth' => UserDisplaySanitizer::digitalHumanAuthName(ConfigService::get('digital_human', 'shanjian_auth', '数字人')),
                'video_case' => $videoCases,
                'video_case_open' => (int)ConfigService::get('digital_human', 'video_case_open', 0),
            ],
            'draw' => [
                'channel' => $hdList['channel'] ?? [],
            ],
            'draw_model' => $drawModels,
            'card_code'                 => [
                'is_open'   => ConfigService::get('card_code', 'is_open', 0),
            ],
            'recharge'                 => [
                'is_ios_open'   => ConfigService::get('recharge', 'is_ios_open', 0),
                'is_and_open'   => ConfigService::get('recharge', 'is_and_open', 0),
                // 小程序算力充值支付方式：1普通微信支付 2虚拟支付
                'mnp_pay_type'  => self::getMnpPayType(),
            ],
            'app_config' => ConfigService::get('app_config', 'redbook', []),
            'ai_live' =>  ConfigService::get('ai_live', 'config', []),
            'by_name' =>  self::getByName(),
            'ai_model' => $chatModels,
            'wechat_remarks' => ConfigService::get('add_remark', 'wechat', []),
            'comment_screening' => ConfigService::get('touch_clue', 'comment_screening', []),
            'touch_speech' => ConfigService::get('touch_clue',  'touch_speech',  []),
        ];
    }

    /**
     * @desc 获取客服信息
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getCustomerService()
    {
        $info =  ConfigService::get('website', 'customer_service', []);

        if (isset($info['image'])) {

            $info['image'] = FileService::getFileUrl($info['image']);
        }

        if (isset($info['wx_image'])) {

            $info['wx_image'] = FileService::getFileUrl($info['wx_image']);
        }

        if (isset($info['fs_image'])) {

            $info['fs_image'] = FileService::getFileUrl($info['fs_image']);
        }

        // 团队 OEM 站点(PC/小程序均按请求域名解析):联系客服统一用 OEM 设置的管理员联系二维码
        try {
            $tenant = \app\api\logic\TeamLogic::resolveTenant((string)\think\facade\Request::domain(), '');
            $adminQr = (string)($tenant['admin_qr'] ?? '');
            if ((int)($tenant['team_id'] ?? 0) > 0 && $adminQr !== '') {
                $info['image'] = $adminQr;
                $info['wx_image'] = $adminQr;
                $info['fs_image'] = $adminQr;
            }
        } catch (\Throwable $e) {
        }

        return $info;
    }



    /**
     * @desc 获取会议纪要配置
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getMeetingConfig()
    {
        $info =  ConfigService::get('meeting', 'config', []);

        if (isset($info['avatars'])) {

            foreach ($info['avatars'] as $key => $value) {

                $info['avatars'][$key] = FileService::getFileUrl($value);
            }
        }

        if (isset($info['language'])) {

            foreach ($info['language'] as $key => $value) {

                if ($value['status'] != 1) {

                    unset($info['language'][$key]);
                }
            }

            $info['language'] = array_values($info['language']);
        }

        if (isset($info['translation'])) {

            foreach ($info['translation'] as $key => $value) {

                if ($value['status'] != 1) {

                    unset($info['translation'][$key]);
                }
            }

            $info['translation'] = array_values($info['translation']);
        }

        return $info;
    }


    /**
     * @desc 获取练练配置
     * @return array
     * @date 2024/12/30 10:18
     * @author dagouzi
     */
    public static function getLianlianConfig()
    {
        $info =  ConfigService::get('lianlian', 'config', []);

        if (isset($info['avatars'])) {

            foreach ($info['avatars'] as $key => $value) {

                $info['avatars'][$key] = FileService::getFileUrl($value);
            }
        }

        if (isset($info['voice'])) {

            foreach ($info['voice'] as $key => $value) {

                if ($value['status'] != 1 || !isset($value['type']) || $value['type'] != 3) {

                    unset($info['voice'][$key]);

                    continue;
                }

                $info['voice'][$key]['logo']    = FileService::getFileUrl($value['logo'] ?? '');
                $info['voice'][$key]['url']    = FileService::getFileUrl($value['url'] ?? '');
            }

            $info['voice'] = array_values($info['voice']);
        }

        return $info;
    }
    public static function getByName()
    {
        $response =  \app\common\service\ToolsService::Auth()->checkby();;

        return  $response['byname'] ?? '';
    }

    /**
     * @notes 小程序微信支付模式：1普通支付 2虚拟支付
     */
    private static function getMnpPayType(): int
    {
        $pay = PayConfig::where(['pay_way' => PayEnum::WECHAT_PAY])->findOrEmpty();
        if ($pay->isEmpty()) {
            return 1;
        }
        $config = $pay['config'] ?? [];
        if (!is_array($config)) {
            return 1;
        }
        $mnpPayType = (int)($config['mnp_pay_type'] ?? 1);
        return in_array($mnpPayType, [1, 2], true) ? $mnpPayType : 1;
    }
}
