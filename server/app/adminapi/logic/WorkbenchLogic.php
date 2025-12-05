<?php


namespace app\adminapi\logic;


use app\common\logic\BaseLogic;
use app\common\model\ModelConfig;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\model\user\User;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\UserActiveLog;
use app\common\model\recharge\GiftPackageOrder;

/**
 * 工作台
 * Class WorkbenchLogic
 * @package app\adminapi\logic
 */
class WorkbenchLogic extends BaseLogic
{
    /**
     * @notes 工作套
     * @param $adminInfo
     * @return array
     * @author 段誉
     * @date 2021/12/29 15:58
     */
    public static function index()
    {
        return [
            // 版本信息
            'version' => self::versionInfo(),
            // 用户数据
            'members' => self::members(),
            //财务数据
            'finance' => self::finance(),
            // 常用功能
            'menu' => self::menu(),
            // 近15日访客数
            'visitor' => self::visitor(),
            // 服务支持
            'support' => self::support(),
            // 算力信息
            'tokens_info' => self::tokensInfo(),
            // 算力计费列表
            'tokens_lists' => self::tokensLists(),
        ];
    }


    /**
     * @notes 常用功能
     * @return array[]
     * @author 段誉
     * @date 2021/12/29 16:40
     */
    public static function menu(): array
    {
        return [
            [
                'name' => '管理员',
                'image' => FileService::getFileUrl(config('project.default_image.menu_admin')),
                'url' => '/permission/admin'
            ],
            [
                'name' => '角色管理',
                'image' => FileService::getFileUrl(config('project.default_image.menu_role')),
                'url' => '/permission/role'
            ],
            [
                'name' => '部门管理',
                'image' => FileService::getFileUrl(config('project.default_image.menu_dept')),
                'url' => '/organization/department'
            ],
            [
                'name' => '字典管理',
                'image' => FileService::getFileUrl(config('project.default_image.menu_dict')),
                'url' => '/dev_tools/dict'
            ],
            [
                'name' => '代码生成器',
                'image' => FileService::getFileUrl(config('project.default_image.menu_generator')),
                'url' => '/dev_tools/code'
            ],
            [
                'name' => '素材中心',
                'image' => FileService::getFileUrl(config('project.default_image.menu_file')),
                'url' => '/material/index'
            ],
            [
                'name' => '菜单权限',
                'image' => FileService::getFileUrl(config('project.default_image.menu_auth')),
                'url' => '/permission/menu'
            ],
            [
                'name' => '网站信息',
                'image' => FileService::getFileUrl(config('project.default_image.menu_web')),
                'url' => '/setting/website/information'
            ],
        ];
    }


    /**
     * @notes 版本信息
     * @return array
     * @author 段誉
     * @date 2021/12/29 16:08
     */
    public static function versionInfo(): array
    {
        return ConfigService::get('website', 'version', []);
    }

    /**
     * @notes 算力计费列表
     * @return array
     * @author 段誉
     * @date 2021/12/29 16:08
     */
    public static function tokensLists(): array
    {

        $response = \app\common\service\ToolsService::DataCenter()->tokensLists();

        if (!isset($response['data']['cast_list'])) {

            return [];
        }

        $data = $response['data']['cast_list'];
        foreach ($data as $key => $value) {

            $info = ModelConfig::where('scene', $value['code'])->field('name,unit,score,code,scene,description')->findOrEmpty();

            if ($info->isEmpty()) {

                unset($data[$key]);

                continue;
            }

            $data[$key]['unit']         = $info['score'] . $info['unit'];
            $data[$key]['price']        = $info['score'];
            $data[$key]['name']         = $info['name'];
            $data[$key]['code']         = $info['code'];
            $data[$key]['scene']        = $info['scene'];
            $data[$key]['description']  = $value['description'];
        }

        return array_values($data);
    }

    /**
     * @notes 算力信息
     * @return array
     * @author 段誉
     * @date 2021/12/29 16:08
     */
    public static function tokensInfo(): array
    {

        $response = \app\common\service\ToolsService::DataCenter()->tokensInfo();

        if (!isset($response['data']['info'])) {

            return [];
        }

        $data = $response['data']['info'];

        return [

            //今日使用量
            'today_use'     => $data['today_use'],
            //总使用量
            'total_use'     => $data['total_use'],
            //可使用量
            'total_balance' => $data['total_balance'],
        ];
    }

    /**
     * @notes 用户数据
     * @return array
     * @author 段誉
     * @date 2021/12/29 16:08
     */
    public static function members(): array
    {
        //总用户数
        $total_members  = User::count();

        //今日新增用户数
        $today_members  = User::where('create_time', '>=', strtotime(date('Y-m-d 00:00:00')))->count();

        //当前活跃用户数 近30分钟
        // 获取当前时间和30分钟前的时间戳
        $now = time();
        $thirtyMinutesAgo = strtotime('-30 minutes'); // 30分钟前

        $active_members = UserActiveLog::whereBetween('create_time', [strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time()))])
            ->whereBetween('update_time', [$thirtyMinutesAgo, $now])->count();

        //充值用户数
        $recharge_members = GiftPackageOrder::where('pay_status', 1)->count('DISTINCT user_id');

        return [
            'total_members' => $total_members,
            'today_members' => $today_members,
            'active_members' => $active_members,
            'recharge_members' => $recharge_members,
        ];
    }

    /**
     * @notes 财务数据
     * @return array
     * @author 段誉
     * @date 2021/12/29 16:08
     */
    public static function finance(): array
    {
        //今日收入
        $today_income  = GiftPackageOrder::where('pay_status', 1)->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00')))->sum('order_amount');

        //今日订单数
        $today_orders = GiftPackageOrder::where('pay_status', 1)->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00')))->count();

        //总收入
        $total_income  = GiftPackageOrder::where('pay_status', 1)->sum('order_amount');

        //总订单数
        $total_orders  = GiftPackageOrder::where('pay_status', 1)->count();

        return [
            'today_income' => $today_income,
            'today_orders' => $today_orders,
            'total_income' => $total_income,
            'total_orders' => $total_orders,
        ];
    }

    /**
     * @notes 访问数
     * @return array
     * @author 段誉
     * @date 2021/12/29 16:57
     */
    public static function visitor(): array
    {

        // 获取近7天活跃用户 生成近7天活跃用户数组

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $startTime = date('Y-m-d 00:00:00', strtotime("-{$i} days")); // 每天开始时间
            $endTime = date('Y-m-d 23:59:59', strtotime("-{$i} days"));   // 每天结束时间

            $days[] = [
                'start_time' => strtotime($startTime),
                'end_time'   => strtotime($endTime),
                'date'       => date('Y-m-d', strtotime("-{$i} days")),
            ];
        }

        $data = [
            'date' => [],
            'list' => [
                'name' => '访客数',
                'data' => [],
            ],
        ];

        foreach ($days as $key => $day) {
            $active_members = UserActiveLog::whereBetween('create_time', [$day['start_time'], $day['end_time']])->count();

            $data['list']['data'][] = $active_members;
            $data['date'][] = $day['date'];
        }

        return $data;
    }


    /**
     * @notes 服务支持
     * @return array[]
     * @author 段誉
     * @date 2022/7/18 11:18
     */
    public static function support()
    {
        return [
            [
                'image' => FileService::getFileUrl(config('project.default_image.qq_group')),
                'title' => '官方公众号',
                'desc' => '关注官方公众号',
            ],
            [
                'image' => FileService::getFileUrl(config('project.default_image.customer_service')),
                'title' => '添加企业客服微信',
                'desc' => '想了解更多请添加客服',
            ]
        ];
    }
}
