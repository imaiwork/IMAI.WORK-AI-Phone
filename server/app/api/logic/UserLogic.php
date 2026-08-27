<?php


namespace app\api\logic;


use app\adminapi\logic\setting\DistributionAgentConfigLogic;
use app\common\{enum\notice\NoticeEnum, enum\user\UserTerminalEnum, enum\YesNoEnum, logic\BaseLogic, model\distribution\DistributionAgent, model\sv\SvDevice, model\user\User, model\user\UserAuth, model\user\UserLevel, service\FileService, service\MemberService, service\sms\SmsDriver, service\wechat\WeChatMnpService};
use app\common\service\deviceauth\DeviceAuthActivateWatchService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Ramsey\Uuid\Uuid;
use think\facade\Config;

/**
 * 会员逻辑层
 * Class UserLogic
 * @package app\shopapi\logic
 */
class UserLogic extends BaseLogic
{
    /**
     * @notes 个人中心
     * @param array $userInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 18:04
     */
    public static function center(array $userInfo): array
    {
        $user = User::where(['id' => $userInfo['user_id']])
            ->field('id,sn,sex,account,nickname,real_name,avatar,mobile,create_time,is_new_user,user_money,tokens,password,level_id,team_id,team_role')
            ->findOrEmpty();

        if (in_array($userInfo['terminal'], [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA])) {
            $auth = UserAuth::where(['user_id' => $userInfo['user_id'], 'terminal' => $userInfo['terminal']])->find();
            $user['is_auth'] = $auth ? YesNoEnum::YES : YesNoEnum::NO;
        }

        $user['has_password'] = !empty($user['password']);
        $user->hidden(['password']);

        $user = $user->toArray();
        // 企业空间可用算力口径:团队成员=企业钱包+团队长个人算力(与后端计费一致),
        // 供 pc/uniapp 的"算力不足"预检与顶部展示;团队主/散客/个人=各自个人算力(spendableTokens 已处理)。
        // personal_tokens 保留个人算力原值备用。
        $user['personal_tokens'] = $user['tokens'];
        $user['tokens'] = \app\common\service\TeamBillingService::spendableTokens((int)$user['id']);
        $user['level_name'] = intval($user['level_id'] ?? -1) > 0
            ? (UserLevel::where('id', intval($user['level_id']))->value('level_name') ?? '')
            : '';

        // 查找用户是否为代理用户
        $agent = DistributionAgent::where('user_id', $user['id'])->findOrEmpty();
        if (!$agent->isEmpty()) {
            if ($agent['status'] == 0 || $agent['level'] == 0){
                $user['is_distribution_agent'] = YesNoEnum::NO;
            }else{
                $user['is_distribution_agent'] = YesNoEnum::YES;
            }
            $parentId = (int)($agent['parent_id'] ?? 0);
            $user['has_parent_agent'] = $parentId > 0 ? YesNoEnum::YES : YesNoEnum::NO;
            $user['parent_agent_id'] = $parentId > 0 ? $parentId : 0;
        } else {
            $user['is_distribution_agent'] = YesNoEnum::NO;
            $user['has_parent_agent'] = YesNoEnum::NO;
            $user['parent_agent_id'] = 0;
        }

        return $user;
    }


    /**
     * @notes 个人信息
     * @param $userId
     * @return array
     * @author 段誉
     * @date 2022/9/20 19:45
     */
    public static function info(int $userId)
    {
        $user = User::where(['id' => $userId])
            ->field('id,sn,sex,account,password,nickname,real_name,avatar,mobile,create_time,user_money,tokens,level_id')
            ->findOrEmpty();
        $user['has_password'] = !empty($user['password']);
        $user['has_auth'] = self::hasWechatAuth($userId);
        $user['version'] = config('project.version');
        $user['level_name'] = intval($user['level_id'] ?? -1) > 0
            ? (UserLevel::where('id', intval($user['level_id']))->value('level_name') ?? '')
            : '';
        $user->hidden(['password']);
        return $user->toArray();
    }


    /**
     * @notes 设置用户信息
     * @param int $userId
     * @param array $params
     * @return User|false
     * @author 段誉
     * @date 2022/9/21 16:53
     */
    public static function setInfo(int $userId, array $params)
    {
        try {
            if ($params['field'] == "avatar") {
                $params['value'] = FileService::setFileUrl($params['value']);
            }

            return User::update(
                [
                    'id' => $userId,
                    $params['field'] => $params['value']
                ]
            );
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 是否有微信授权信息
     * @param $userId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 19:36
     */
    public static function hasWechatAuth(int $userId)
    {
        //是否有微信授权登录
        $terminal = [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA, UserTerminalEnum::PC];
        $auth = UserAuth::where(['user_id' => $userId])
            ->whereIn('terminal', $terminal)
            ->findOrEmpty();
        return !$auth->isEmpty();
    }


    /**
     * @notes 重置登录密码
     * @param $params
     * @return bool
     * @author 段誉
     * @date 2022/9/16 18:06
     */
    public static function resetPassword(array $params)
    {
        try {
            // 校验验证码
            $smsDriver = new SmsDriver();
            if (!$smsDriver->verify($params['mobile'], $params['code'], NoticeEnum::FIND_LOGIN_PASSWORD_CAPTCHA)) {
                throw new \Exception('验证码错误');
            }

            // 重置密码
            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($params['password'], $passwordSalt);

            // 更新
            User::where('mobile', $params['mobile'])->update([
                'password' => $password
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 修稿密码
     * @param $params
     * @param $userId
     * @return bool
     * @author 段誉
     * @date 2022/9/20 19:13
     */
    public static function changePassword(array $params, int $userId)
    {
        try {
            $user = User::findOrEmpty($userId);
            if ($user->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            // 密码盐
            $passwordSalt = Config::get('project.unique_identification');

            if (!empty($user['password'])) {
                if (empty($params['old_password'])) {
                    throw new \Exception('请填写旧密码');
                }
                $oldPassword = create_password($params['old_password'], $passwordSalt);
                if ($oldPassword != $user['password']) {
                    throw new \Exception('原密码不正确');
                }
            }

            // 保存密码
            $password = create_password($params['password'], $passwordSalt);
            $user->password = $password;
            $user->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 获取小程序手机号
     * @param array $params
     * @return bool
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/2/27 11:49
     */
    public static function getMobileByMnp(array $params)
    {
        try {
            $response = (new WeChatMnpService(\app\api\logic\TeamLogic::currentRequestSiteTeamId()))->getUserPhoneNumber($params['code']);
            $phoneNumber = $response['phone_info']['purePhoneNumber'] ?? '';
            if (empty($phoneNumber)) {
                throw new \Exception('获取手机号码失败');
            }

            $user = User::where([
                ['mobile', '=', $phoneNumber],
                ['id', '<>', $params['user_id']]
            ])->findOrEmpty();

            if (!$user->isEmpty()) {
                throw new \Exception('手机号已被其他账号绑定');
            }

            // 绑定手机号
            User::update([
                'id' => $params['user_id'],
                'mobile' => $phoneNumber
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 绑定手机号
     * @param $params
     * @return bool
     * @author 段誉
     * @date 2022/9/21 17:28
     */
    public static function bindMobile(array $params)
    {
        try {
            // 变更手机号场景
            $sceneId = NoticeEnum::CHANGE_MOBILE_CAPTCHA;
            $where = [
                ['id', '=', $params['user_id']],
                ['mobile', '=', $params['mobile']]
            ];

            // 绑定手机号场景
            if ($params['type'] == 'bind') {
                $sceneId = NoticeEnum::BIND_MOBILE_CAPTCHA;
                $where = [
                    ['mobile', '=', $params['mobile']]
                ];
            }

            // 校验短信
            $checkSmsCode = (new SmsDriver())->verify($params['mobile'], $params['code'], $sceneId);
            if (!$checkSmsCode) {
                throw new \Exception('验证码错误');
            }

            $user = User::where($where)->findOrEmpty();
            if (!$user->isEmpty()) {
                throw new \Exception('该手机号已被使用');
            }

            User::update([
                'id' => $params['user_id'],
                'mobile' => $params['mobile'],
            ]);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取用户设备绑定二维码
     * @param array $params
     * @return bool
     * @author L
     * @date 2025/11/4 10:45
     */
    public static function getDeviceBindCode(array $params): bool
    {
        try {
            $userId = (int)($params['user_id'] ?? 0);
            $existing = (int)SvDevice::where('user_id', $userId)->count();
            $reason = '';
            if (!MemberService::canBindDevice($userId, $existing, $reason)) {
                throw new \Exception($reason);
            }

            $deviceBindCode = User::where('id', '=', $params['user_id'])->value('device_bind_qrcode');
            $host = env('app.host');
            $domain = parse_url($host)['host'] ?? $_SERVER['HTTP_HOST'];
            if (empty($deviceBindCode) || !file_exists(public_path() . $deviceBindCode)) {
                $uuid = (Uuid::uuid4())->toString();
                $writer = new PngWriter();
                $publicPath = '/qrcode/user/' . $uuid . '.png';
                $filePath = root_path() . 'public' . $publicPath;

                //创建目录
                if (!is_dir(dirname($filePath))) {
                    umask(0);
                    mkdir(dirname($filePath), 0777, true);
                }

                $jsonData = json_encode([
                    'domain' => $domain,
                    'user_id' => $params['user_id'],
                    'uuid' => $uuid,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $QrCode = QrCode::create($jsonData)
                    ->setSize(150) // 尺寸
                    ->setMargin(10);
                $Result = $writer->write($QrCode);
                $Result->saveToFile($filePath);
                User::update([
                    'id' => $params['user_id'],
                    'device_bind_qrcode' => $publicPath
                ]);
                $url = 'https://' . $domain . $publicPath;
            } else {
                // 从图片路径中获取uuid
                $uuid = pathinfo(basename($deviceBindCode), PATHINFO_FILENAME);
                $url = 'https://' . $domain . $deviceBindCode;
            }

            $snapshot = DeviceAuthActivateWatchService::snapshot($params['user_id']);

            self::$returnData = [
                'user_id' => $params['user_id'],
                'url' => $url,
                'uuid' => $uuid,
                'watch_code_count' => DeviceAuthActivateWatchService::unusedCountFromMap($snapshot),
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取用户设备绑定/激活状态（轮询本地设备CDK状态变化）
     * @param array $params
     * @return bool
     * @author L
     * @date 2025/11/4 10:45
     */
    public static function getDeviceBindStatus(array $params): bool
    {
        try {
            $userId = (int)$params['user_id'];
            if (!DeviceAuthActivateWatchService::hasSnapshot($userId)) {
                self::$returnData = [
                    'status'         => 0,
                    'message'        => '请先获取绑定二维码',
                    'device_code'    => '',
                    'code'           => '',
                    'auth_type_desc' => '',
                ];
                return true;
            }

            $activated = DeviceAuthActivateWatchService::detectActivated($userId);
            if ($activated === null) {
                self::$returnData = [
                    'status'         => 0,
                    'message'        => '等待激活',
                    'device_code'    => '',
                    'code'           => '',
                    'auth_type_desc' => '',
                ];
                return true;
            }

            DeviceAuthActivateWatchService::clear($userId);
            $status = (int)($activated['status'] ?? 1);
            self::$returnData = [
                'status'         => $status,
                'message'        => $activated['message'] ?? ($status === 1 ? '激活成功' : '绑定失败'),
                'device_code'    => $activated['device_code'] ?? '',
                'code'           => $activated['code'] ?? '',
                'auth_type_desc' => $activated['auth_type_desc'] ?? '',
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 绑定代理
     */
    public static function bindUser($params): bool
    {
        try {
            $sn = $params['sn'];
            if (empty($sn)) {
                throw new \Exception('请输入上级用户编号');
            }
            $inviter = User::where('sn', '=', $sn)->findOrEmpty();
            if ($inviter->isEmpty()) {
                throw new \Exception('上级用户不存在');
            }
            if ($inviter['id'] == $params['user_id']) {
                throw new \Exception('无法绑定自己');
            }
            // 邀请人是否为代理、验证代理状态
            $inviterAgent = DistributionAgent::where('user_id', $inviter['id'])->findOrEmpty();
            if ($inviterAgent->isEmpty() || $inviterAgent->status == 0) {
                throw new \Exception('上级用户还不是代理');
            }
            $agent = DistributionAgent::where('user_id', $params['user_id'])->findOrEmpty();
            // 幂等：已绑定的上级就是本次扫码的代理时直接返回成功（重复扫码/重复请求不报错，也不重复过人数校验）
            if (!$agent->isEmpty() && $agent['parent_id'] == $inviter['id']) {
                return true;
            }
            if (!$agent->isEmpty() && $agent['parent_id'] != 0) {
                throw new \Exception('已存在上级用户，无法再次绑定');
            }
            // 按后台 getSubLimits：对被绑用户当前等级做对应类型人数校验
            DistributionAgentConfigLogic::checkCanAcceptBind((int)$inviter['id'], (int)$params['user_id']);
            if ($agent->isEmpty()) {
                DistributionAgent::create([
                    'user_id' => $params['user_id'],
                    'parent_id' => $inviter['id'],
                    'level' => 0,   // 默认为普通用户
                    'status' => 1,  // 默认为启用
                    'become_time' => time(),
                ]);
            } else {
                DistributionAgent::update([
                    'parent_id' => $inviter['id'],
                    'become_time' => time(),
                ], ['user_id' => $params['user_id']]);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage() ?? '绑定失败');
            return false;
        }
    }
}
