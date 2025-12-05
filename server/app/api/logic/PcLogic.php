<?php


namespace app\api\logic;


use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\model\article\Article;
use app\common\model\article\ArticleCate;
use app\common\model\article\ArticleCollect;
use app\common\model\decorate\DecoratePage;
use app\common\model\human\HumanVoice;
use app\common\service\ConfigService;
use app\common\service\FileService;


/**
 * index
 * Class IndexLogic
 * @package app\api\logic
 */
class PcLogic extends BaseLogic
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
        $decoratePage = DecoratePage::findOrEmpty(4);
        // 最新资讯
        $newArticle = self::getLimitArticle('new', 7);
        // 全部资讯
        $allArticle = self::getLimitArticle('all', 5);
        // 热门资讯
        $hotArticle = self::getLimitArticle('hot', 8);

        return [
            'page' => $decoratePage,
            'all' => $allArticle,
            'new' => $newArticle,
            'hot' => $hotArticle
        ];
    }


    /**
     * @notes 获取文章
     * @param string $sortType
     * @param int $limit
     * @return mixed
     * @author 段誉
     * @date 2022/10/19 9:53
     */
    public static function getLimitArticle(string $sortType, int $limit = 0, int $cate = 0, int $excludeId = 0)
    {
        // 查询字段
        $field = [
            'id',
            'cid',
            'title',
            'desc',
            'abstract',
            'image',
            'author',
            'click_actual',
            'click_virtual',
            'create_time'
        ];

        // 排序条件
        $orderRaw = 'sort desc, id desc';
        if ($sortType == 'new') {
            $orderRaw = 'id desc';
        }
        if ($sortType == 'hot') {
            $orderRaw = 'click_actual + click_virtual desc, id desc';
        }

        // 查询条件
        $where[] = ['is_show', '=', YesNoEnum::YES];
        if (!empty($cate)) {
            $where[] = ['cid', '=', $cate];
        }
        if (!empty($excludeId)) {
            $where[] = ['id', '<>', $excludeId];
        }

        $article = Article::field($field)
            ->where($where)
            ->append(['click'])
            ->orderRaw($orderRaw)
            ->hidden(['click_actual', 'click_virtual']);

        if ($limit) {
            $article->limit($limit);
        }

        return $article->select()->toArray();
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
            'qq_auth' => ConfigService::get('login', 'qq_auth', config('project.login.qq_auth'))
        ];

        // 网站信息
        $website = [
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),
            'pc_logo' => FileService::getFileUrl(ConfigService::get('website', 'pc_logo')),
            'pc_title' => ConfigService::get('website', 'pc_title'),
            'pc_ico' => FileService::getFileUrl(ConfigService::get('website', 'pc_ico')),
            'pc_desc' => ConfigService::get('website', 'pc_desc'),
            'pc_keywords' => ConfigService::get('website', 'pc_keywords'),
            // 登录页
            'login_image' => FileService::getFileUrl(ConfigService::get('website', 'login_image')),
            // 调查问卷
            'survey' => ConfigService::get('website', 'survey', []),
            // banner
            'banner' => FileService::getFileUrl(ConfigService::get('website', 'banner')),
            // 客服信息
            'customer_service' => self::getCustomerService(),

            'shop_title' => ConfigService::get('website', 'shop_title', 'AI时代，企业化AI工具的新星'),
            'client_download'                 => [
                'windows' =>  ConfigService::get('client_download','windows',''),
                'mac_intel' =>  ConfigService::get('client_download','mac_intel',''),
                'mac_apple' =>  ConfigService::get('client_download','mac_apple',''),
                'android' =>  ConfigService::get('client_download','android',''),
                'mini_programs' =>  ConfigService::get('client_download','mini_programs',''),
                'h5' =>  ConfigService::get('client_download','h5',''),
            ],
        ];

        //模型
        $modelList =  HumanVoice::getModelList();
        $hdList = ConfigService::get('hd', 'list', []);
        // 隐藏即梦
        foreach ($hdList['channel'] as $val) {
            if ($val['id'] == '2'){
                continue;
            }else{
                $hdmodel['channel'][] = $val;
            }
        }

        //模型
        $indexConfig =  ConfigService::get('index', 'config', []);

        // 备案信息
        $copyright = ConfigService::get('copyright', 'config', []);

        // 公众号二维码
        $oaQrCode = ConfigService::get('oa_setting', 'qr_code', '');
        $oaQrCode = empty($oaQrCode) ? $oaQrCode : FileService::getFileUrl($oaQrCode);
        // 小程序二维码
        $mnpQrCode = ConfigService::get('mnp_setting', 'qr_code', '');
        $mnpQrCode = empty($mnpQrCode) ? $mnpQrCode : FileService::getFileUrl($mnpQrCode);

        //版本信息
        $version = ConfigService::get('website', 'version', []);

        //会议纪要配置
        $meetingConfig = self::getMeetingConfig();

        //练练
        $lianlian = self::getLianlianConfig();

        //大语言模型
        $chatModels = ConfigService::get('chat', 'ai_model', []);
        foreach ($chatModels['channel'] as $key=>$value){
            $chatModels['channel'][$key]['logo'] = isset($value['logo']) ? FileService::getFileUrl($value['logo']) : '';
        }
        $banner =  config('app.app_host') . '/static/images/human/banner.png';
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
//            'domain' => FileService::getFileUrl(),
            'is_robot_show' => ConfigService::get('assistants', 'is_robot_show',0),
            'domain' => config('app.app_host') . '/',
            'login' => $loginConfig,
            'website' => $website,
            'is_oss_transcode' => $transcoding,
            'version' => $version,
            'copyright' => $copyright,
            'admin_url' => request()->domain() . '/admin',
            'qrcode' => [
                'oa' => $oaQrCode,
                'mnp' => $mnpQrCode,
            ],
            'index_config' => $indexConfig,
            'meeting_config' => $meetingConfig,
            'lianlian' => $lianlian,
            'digital_human' => [
                'privacy' => ConfigService::get('digital_human', 'privacy', []),
                'channel' => $modelList['channel'] ?? [],
                'voice' => $modelList['voice'] ?? [],
                'shanjian_auth' => ConfigService::get('digital_human', 'shanjian_auth', '闪剪AI'),
                'banner' =>  FileService::getFileUrl(ConfigService::get('digital_human', 'banner', $banner)),

            ],
            'card_code'                 => [
                'is_open'   => ConfigService::get('card_code','is_open',0),
            ],
            'recharge'                 => [
                'is_ios_open'   => ConfigService::get('recharge','is_ios_open',0),
            ],
            'draw' => [
                'channel' => $hdmodel['channel'] ?? [],
            ],
            'app_config' => ConfigService::get('app_config', 'redbook', []),
            'ai_live' =>  ConfigService::get('ai_live', 'config', []),
            'by_name'=>  self::getByName(),
            'ai_model' =>  $chatModels,
            'wechat_remarks' => ConfigService::get('add_remark', 'wechat', []),

        ];
    }


    /**
     * @notes 资讯中心
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/10/19 16:55
     */
    public static function getInfoCenter()
    {
        $data = ArticleCate::field(['id', 'name'])
            ->with(['article' => function ($query) {
                $query->hidden(['content', 'click_virtual', 'click_actual'])
                    ->order(['sort' => 'desc', 'id' => 'desc'])
                    ->append(['click'])
                    ->limit(10);
            }])
            ->where(['is_show' => YesNoEnum::YES])
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();

        return $data;
    }


    /**
     * @notes 获取文章详情
     * @param $userId
     * @param $articleId
     * @param string $source
     * @return array
     * @author 段誉
     * @date 2022/10/20 15:18
     */
    public static function getArticleDetail($userId, $articleId, $source = 'default')
    {
        // 文章详情
        $detail = Article::getArticleDetailArr($articleId);

        // 根据来源列表查找对应列表
        $nowIndex = 0;
        $lists = self::getLimitArticle($source, 0, $detail['cid']);
        foreach ($lists as $key => $item) {
            if ($item['id'] == $articleId) {
                $nowIndex = $key;
            }
        }
        // 上一篇
        $detail['last'] = $lists[$nowIndex - 1] ?? [];
        // 下一篇
        $detail['next'] = $lists[$nowIndex + 1] ?? [];

        // 最新资讯
        $detail['new'] = self::getLimitArticle('new', 8, $detail['cid'], $detail['id']);
        // 关注状态
        $detail['collect'] = ArticleCollect::isCollectArticle($userId, $articleId);
        // 分类名
        $detail['cate_name'] = ArticleCate::where('id', $detail['cid'])->value('name');

        return $detail;
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
