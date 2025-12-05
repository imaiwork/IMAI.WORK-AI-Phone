<?php


namespace app\api\logic;


use app\common\{enum\notice\NoticeEnum, enum\user\UserTerminalEnum, enum\YesNoEnum, logic\BaseLogic, model\user\User, model\user\UserAuth, service\FileService, service\sms\SmsDriver, service\wechat\WeChatMnpService};
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
            ->field('id,sn,sex,account,nickname,real_name,avatar,mobile,create_time,is_new_user,user_money,tokens,password')
            ->findOrEmpty();

        if (in_array($userInfo['terminal'], [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA])) {
            $auth = UserAuth::where(['user_id' => $userInfo['user_id'], 'terminal' => $userInfo['terminal']])->find();
            $user['is_auth'] = $auth ? YesNoEnum::YES : YesNoEnum::NO;
        }

        $user['has_password'] = !empty($user['password']);
        $user->hidden(['password']);
        return $user->toArray();
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
            ->field('id,sn,sex,account,password,nickname,real_name,avatar,mobile,create_time,user_money,tokens')
            ->findOrEmpty();
        $user['has_password'] = !empty($user['password']);
        $user['has_auth'] = self::hasWechatAuth($userId);
        $user['version'] = config('project.version');
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
            $response = (new WeChatMnpService())->getUserPhoneNumber($params['code']);
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
            $deviceBindCode = User::where('id', '=', $params['user_id'])->value('device_bind_qrcode');
            $domain         = $_SERVER['HTTP_HOST'];
            if (empty($deviceBindCode)) {
                $uuid       = (Uuid::uuid4())->toString();
                $writer     = new PngWriter();
                $publicPath = '/qrcode/user/' . $uuid . '.png';
                $filePath   = root_path() . 'public' . $publicPath;

                //创建目录
                if (!is_dir(dirname($filePath))) {
                    umask(0);
                    mkdir(dirname($filePath), 0777, true);
                }

                $jsonData   = json_encode([
                                    'domain'        => $domain,
                                    'user_id'       => $params['user_id'],
                                    'uuid'          => $uuid,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $QrCode     = QrCode::create($jsonData)
                                ->setSize(150) // 尺寸
                                ->setMargin(10);
                $Result     = $writer->write($QrCode);
                $Result->saveToFile($filePath);
                User::update([
                                 'id'                 => $params['user_id'],
                                 'device_bind_qrcode' => $publicPath
                             ]);
                $url = 'https://' . $domain . $publicPath;
            }else{
                // 从图片路径中获取uuid
                $uuid = pathinfo(basename($deviceBindCode), PATHINFO_FILENAME);
                $url  = 'https://' . $domain . $deviceBindCode;
            }

            self::$returnData = [
                'user_id' => $params['user_id'],
                'url'     => $url,
                'uuid'    => $uuid,
            ];
            return true;
        }catch (\Exception $e){
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取用户设备绑定状态
     * @param array $params
     * @return bool
     * @author L
     * @date 2025/11/4 10:45
     */
    public static function getDeviceBindStatus(array $params): bool
    {
        try {
            $user    = User::where('id', '=', $params['user_id'])->find();
            $status  = 1;
            $message = '绑定成功';
            if ($user['device_bind_num'] == 0) {
                $status  = 0;
                $message = '设备未绑定';
            }
            if ((time() - $user['device_bind_time']) > 15) {
                $status  = 0;
                $message = '设备未绑定，请稍后再试';
            }

            $domain = $_SERVER['HTTP_HOST'];
            $uuid   = pathinfo(basename($user['device_bind_code']), PATHINFO_FILENAME);
            $params = [
                'user_id' => $user['id'],
                'domain'  => $domain,
                'uuid'    => $uuid,
            ];
            $res    = self::getDeviceBindRequest($params);

            if (!$res) {
                $status  = 0;
                $message = '绑定失败';
            }

            self::$returnData = [
                'status'  => $status,
                'message' => $message
            ];
            return true;
        } catch (GuzzleException $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @throws GuzzleException
     */
    public static function getDeviceBindRequest($params): bool
    {
        $auth     = \app\common\service\ToolsService::Auth();
        $url      = $auth::CODE_REQUEST_URL;
        $token    = $auth::CODE_REQUEST_TOKEN;
        $body     = [
            'user_id' => $params['user_id'],
            'domain'  => $params['domain'],
            'uuid'    => $params['uuid'],
        ];
        $option   = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json'
            ],
            'json'    => $body
        ];
        $client   = new Client();
        $rsp      = $client->request('POST', $url, $option);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        if (($data['code'] ?? 0) === 1) {
            return true;
        }
        return false;
    }
}
