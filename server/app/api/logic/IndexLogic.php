<?php


namespace app\api\logic;


use app\common\logic\BaseLogic;
use app\common\model\article\Article;
use app\common\model\decorate\DecoratePage;
use app\common\model\decorate\DecorateTabbar;
use app\common\model\human\HumanVoice;
use app\common\service\ConfigService;
use app\common\service\FileService;


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
                'windows' =>  ConfigService::get('client_download','windows',''),
                'mac_intel' =>  ConfigService::get('client_download','mac_intel',''),
                'mac_apple' =>  ConfigService::get('client_download','mac_apple',''),
                'android' =>  ConfigService::get('client_download','android',''),
                'mini_programs' =>  ConfigService::get('client_download','mini_programs',''),
                'h5' =>  ConfigService::get('client_download','h5',''),
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
        $chatModels = ConfigService::get('chat', 'ai_model', []);
        foreach ($chatModels['channel'] as $key=>$value){
            $chatModels['channel'][$key]['logo'] = isset($value['logo']) ? FileService::getFileUrl($value['logo']) : '';
        }
        $transcoding = true;
        $config = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ];

        switch ($config['default']) {
            case 'aliyun':
                $aliyun = $config['engine']['aliyun'];
                if (!isset($aliyun['PipelineId']) || !isset($aliyun['Location']) || !isset($aliyun['TemplateId'])) {
                    $transcoding = false;
                }
                if (empty($aliyun['PipelineId']) || empty($aliyun['Location']) || empty($aliyun['TemplateId'])) {
                    $transcoding = false;
                }
                break;
            case 'qcloud':
                $qcloud = $config['engine']['qcloud'];
                if (!isset($qcloud['TemplateId'])) {
                    $transcoding = false;
                }
                if (empty($qcloud['TemplateId'])) {
                    $transcoding = false;
                }
                break;
            default:
                $transcoding = false;
                break;
            }


        return [
            'is_robot_show' => ConfigService::get('assistants', 'is_robot_show',0),
            'copyright' => ConfigService::get('copyright', 'config',''),
//            'domain' => FileService::getFileUrl(),
            'domain' => config('app.app_host') . '/',
            'style' => $style,
            'tabbar' => $tabbar,
            'login' => $loginConfig,
            'website' => $website,
            'is_oss_transcode' => $transcoding,
            'webPage' => $webPage,
            'index_config' => $indexConfig,
            'version' => $version,
            'meeting_config' => $meetingConfig,
            'lianlian' => $lianlian,
            'mnp_share_config' => $mnpShareConfig,
            'digital_human' => [
                'privacy' => ConfigService::get('digital_human', 'privacy', []),
                'channel' => $modelList['channel'] ?? [],
                'voice' => $modelList['voice'] ?? [],
                'shanjian_auth' => ConfigService::get('digital_human', 'shanjian_auth', '闪剪AI'),
                'banner' =>  FileService::getFileUrl(ConfigService::get('digital_human', 'banner', $banner)),
            ],
            'draw' => [
                'channel' => $hdList['channel'] ?? [],
            ],
            'card_code'                 => [
                'is_open'   => ConfigService::get('card_code','is_open',0),
            ],
            'recharge'                 => [
                'is_ios_open'   => ConfigService::get('recharge','is_ios_open',0),
            ],
            'app_config' => ConfigService::get('app_config', 'redbook', []),
            'ai_live' =>  ConfigService::get('ai_live', 'config', []),
            'by_name'=>  self::getByName(),
            'ai_model' => $chatModels,
            'wechat_remarks' => ConfigService::get('add_remark', 'wechat', []),
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

                if ($value['status'] != 1) {

                    unset($info['voice'][$key]);

                    continue;
                }

                $info['voice'][$key]['logo']    = FileService::getFileUrl($value['logo']);
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
}
