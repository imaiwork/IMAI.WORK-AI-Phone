<?php


namespace app\api\logic;

use app\api\logic\service\{WechatUserService};
use app\api\logic\service\UserTokenService;
use app\common\cache\WebScanLoginCache;
use app\common\enum\{LoginEnum, user\AccountLogEnum, user\UserTerminalEnum, YesNoEnum};
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\model\user\{User, UserAuth};
use app\common\service\{ConfigService, FileService, wechat\WeChatConfigService, wechat\WeChatMnpService, wechat\WeChatOaService, wechat\WeChatRequestService};
use think\facade\{Config, Db};

/**
 * 登录逻辑
 * Class LoginLogic
 * @package app\api\logic
 */
class LoginLogic extends BaseLogic
{
    private const REGISTER_MODE_MOBILE = 1;
    private const REGISTER_MODE_INVITE = 2;
    private const REGISTER_MODE_CLOSED = 4;

    public static function getRegisterMode(): array
    {
        $registerConfig = ConfigService::get('user', 'register', []);
        if (is_string($registerConfig)) {
            $decoded = json_decode($registerConfig, true);
            $registerConfig = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($registerConfig)) {
            $registerConfig = [];
        }

        $mode = (int)($registerConfig['register_mode'] ?? self::REGISTER_MODE_MOBILE);
        if ($mode === 3) {
            $mode = self::REGISTER_MODE_INVITE;
        }
        if (!in_array($mode, [self::REGISTER_MODE_MOBILE, self::REGISTER_MODE_INVITE, self::REGISTER_MODE_CLOSED], true)) {
            $mode = self::REGISTER_MODE_MOBILE;
        }

        return [
            'mode' => $mode,
            'register_mode' => $mode,
            'default_invite_source' => (string)($registerConfig['default_invite_source'] ?? ''),
            'require_invite' => $mode === self::REGISTER_MODE_INVITE,
            'require_phone' => in_array($mode, [self::REGISTER_MODE_MOBILE, self::REGISTER_MODE_INVITE], true),
            'closed' => $mode === self::REGISTER_MODE_CLOSED,
        ];
    }

    public static function getDefaultInviteSource(): string
    {
        $registerConfig = ConfigService::get('user', 'register', []);
        if (is_string($registerConfig)) {
            $decoded = json_decode($registerConfig, true);
            $registerConfig = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($registerConfig)) {
            $registerConfig = [];
        }

        $value = trim((string)($registerConfig['default_invite_source'] ?? ''));
        return $value !== '' ? $value : '系统';
    }

    public static function checkRegisterPolicy(string $inviteCode = ''): int
    {
        $registerMode = self::getRegisterMode();
        if ($registerMode['closed']) {
            throw new \Exception('当前平台暂停注册，请联系管理员');
        }

        $inviteCode = trim($inviteCode);
        if ($registerMode['require_invite'] || $inviteCode !== '') {
            return self::checkInviteCode($inviteCode);
        }

        return 0;
    }

    public static function checkInviteCode(string $inviteCode): int
    {
        $inviteCode = trim($inviteCode);
        if ($inviteCode === '') {
            throw new \Exception('请输入邀请码');
        }

        $inviter = User::where('sn', $inviteCode)->findOrEmpty();
        if ($inviter->isEmpty()) {
            throw new \Exception('邀请码无效');
        }

        $inviterAgent = DistributionAgent::where('user_id', (int)$inviter->id)->findOrEmpty();
        if ($inviterAgent->isEmpty() || (int)$inviterAgent['status'] !== 1 || (int)$inviterAgent['level'] <= 0) {
            throw new \Exception('邀请码无效');
        }

        return (int)$inviter->id;
    }

    /**
     * @notes 手机号验证码注册并登录
     * @param array $params
     * @return array|false
     */
    public static function register(array $params)
    {
        Db::startTrans();
        try {
            $mobile = self::getRegisterMobile($params);
            if (!self::findUserByAccountOrMobile($mobile)->isEmpty()) {
                throw new \Exception('账号已存在，请直接登录');
            }

            $parentId = self::checkRegisterPolicy((string)($params['invite_code'] ?? ''));
            $terminal = (int)($params['terminal'] ?? $params['channel'] ?? UserTerminalEnum::PC);
            $user = self::createMobileUser($mobile, $terminal, $parentId, $params);
            self::updateLoginInfo($user->id);

            Db::commit();
            return self::makeLoginResult($user, $terminal);
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /** 在 distribution_agent 表里建一条 parent_id 关系(用户自身 level=0 普通用户) */
    public static function seedInviteRelation(int $userId, int $parentId): void
    {
        try {
            $exist = DistributionAgent::where('user_id', $userId)->findOrEmpty();
            if ($exist->isEmpty()) {
                DistributionAgent::create([
                    'user_id' => $userId,
                    'parent_id' => $parentId,
                    'level' => 0,
                    'status' => 1,
                    'become_time' => time(),
                ]);
                return;
            }

            if ($parentId > 0 && (int)$exist['parent_id'] === 0) {
                DistributionAgent::update([
                    'parent_id' => $parentId,
                    'become_time' => time(),
                ], ['user_id' => $userId]);
            }
        } catch (\Throwable $e) {
            // 不阻断注册主流程
        }
    }


    /**
     * @notes 获取注册手机号，兼容 mobile/account 参数
     */
    private static function getRegisterMobile(array $params): string
    {
        $mobile = trim((string)($params['mobile'] ?? $params['account'] ?? ''));
        if ($mobile === '') {
            throw new \Exception('请输入手机号');
        }
        return $mobile;
    }

    private static function findUserByAccountOrMobile(string $account)
    {
        return User::where(['account|mobile' => $account])->findOrEmpty();
    }

    private static function createMobileUser(string $mobile, int $terminal, int $parentId, array $params = []): User
    {
        $userSn = User::createUserSn();
        $passwordSalt = Config::get('project.unique_identification');
        $password = create_password((string)($params['password'] ?? $mobile), $passwordSalt);
        $avatar = ConfigService::get('default_image', 'user_avatar');
        $tokens = ConfigService::get('default_tokens', 'tokens', 0);
        // OEM 站点注册:team_id 作当前散客归属,origin_team_id 锁定站点原生归属
        // (建团/切空间后仍能出现在主站「站点用户」;解散自建团后可恢复)
        $oemTeamId = \app\api\logic\TeamLogic::registerAttributionTeamId();

        $user = User::create([
            'sn' => $userSn,
            'avatar' => $avatar,
            'nickname' => '用户' . $userSn,
            'group_id' => $params['group_id'] ?? 0,
            'account' => $mobile,
            'mobile' => $mobile,
            'password' => $password,
            'channel' => $terminal,
            'tokens' => $tokens,
            'source' => self::getDefaultInviteSource(),
            'team_id' => $oemTeamId,
            'origin_team_id' => $oemTeamId,
        ]);

        self::grantRegisterTokens((int)$user->id, $tokens);
        self::seedInviteRelation((int)$user->id, $parentId);

        return $user;
    }

    private static function grantRegisterTokens(int $userId, $tokens): void
    {
        if (empty($tokens)) {
            return;
        }

        // add(userId, changeType, action, amount, status, sourceSn, remark)
        AccountLogLogic::add(
            $userId,
            AccountLogEnum::TOKENS_INC_REGISTER,
            AccountLogEnum::INC,
            $tokens,
            1,
            '',
            AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_INC_REGISTER)
        );
    }

    private static function makeLoginResult(User $user, int $terminal): array
    {
        $userInfo = UserTokenService::setToken($user->id, $terminal);
        $avatar = $user->avatar ?: Config::get('project.default_image.user_avatar');
        $avatar = FileService::getFileUrl($avatar);

        return [
            'nickname' => $userInfo['nickname'],
            'sn' => $userInfo['sn'],
            'mobile' => $userInfo['mobile'],
            'avatar' => $avatar,
            'token' => $userInfo['token'],
        ];
    }

    /**
     * @notes 账号/手机号登录；开放手机注册时，验证码登录可自动注册
     * @param $params
     * @return array|false
     */
    public static function login($params)
    {
        try {
            // 账号/手机号 密码登录
            $where = ['account|mobile' => $params['account']];
            if ($params['scene'] == LoginEnum::MOBILE_CAPTCHA) {
                //手机验证码登录
                $where = ['mobile' => $params['account']];
            }

            $user = User::where($where)->findOrEmpty();
            if ($user->isEmpty()) {
                // 仅手机验证码登录支持「未注册即注册」；密码登录仍要求先注册
                if ((int)$params['scene'] !== LoginEnum::MOBILE_CAPTCHA) {
                    throw new \Exception('账号不存在,请先注册');
                }
                return self::registerByLogin($params);
            }

            if ((int)$user->is_disable === 1) {
                throw new \Exception('用户已禁用');
            }

            //更新登录信息
            $user->login_time = time();
            $user->login_ip = request()->ip();
            $user->save();

            //设置token
            $userInfo = UserTokenService::setToken($user->id, $params['terminal']);

            //返回登录信息
            $avatar = $user->avatar ?: Config::get('project.default_image.user_avatar');
            $avatar = FileService::getFileUrl($avatar);

            return [
                'nickname' => $userInfo['nickname'],
                'sn' => $userInfo['sn'],
                'mobile' => $userInfo['mobile'],
                'avatar' => $avatar,
                'token' => $userInfo['token'],
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 验证码登录时用户不存在：按注册模式自动注册并登录
     * @param array $params
     * @return array
     * @throws \Exception
     */
    private static function registerByLogin(array $params): array
    {
        $registerMode = self::getRegisterMode();
        if ($registerMode['closed']) {
            throw new \Exception('当前平台暂停注册，请联系管理员');
        }

        if ($registerMode['require_invite'] && trim((string)($params['invite_code'] ?? '')) === '') {
            throw new \Exception('账号不存在,请先注册');
        }

        Db::startTrans();
        try {
            $mobile = self::getRegisterMobile($params);
            // 并发场景下可能已被注册，再查一次
            $user = self::findUserByAccountOrMobile($mobile);
            if (!$user->isEmpty()) {
                self::updateLoginInfo((int)$user->id);
                Db::commit();
                return self::makeLoginResult($user, (int)($params['terminal'] ?? UserTerminalEnum::PC));
            }

            $parentId = self::checkRegisterPolicy((string)($params['invite_code'] ?? ''));
            $terminal = (int)($params['terminal'] ?? $params['channel'] ?? UserTerminalEnum::PC);
            $user = self::createMobileUser($mobile, $terminal, $parentId, $params);
            self::updateLoginInfo($user->id);

            Db::commit();
            return self::makeLoginResult($user, $terminal);
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }


    /**
     * @notes 退出登录
     * @param $userInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 17:56
     */
    public static function logout($userInfo)
    {
        //token不存在，不注销
        if (!isset($userInfo['token'])) {
            return false;
        }

        //设置token过期
        return UserTokenService::expireToken($userInfo['token']);
    }


    /**
     * @notes 获取微信请求code的链接
     * @param string $url
     * @return string
     * @author 段誉
     * @date 2022/9/20 19:47
     */
    public static function codeUrl(string $url)
    {
        return (new WeChatOaService())->getCodeUrl($url);
    }


    /**
     * @notes 公众号登录
     * @param array $params
     * @return array|false
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author 段誉
     * @date 2022/9/20 19:47
     */
    public static function oaLogin(array $params)
    {
        Db::startTrans();
        try {
            //通过code获取微信 openid
            $response = (new WeChatOaService())->getOaResByCode($params['code']);
            $userServer = new WechatUserService($response, UserTerminalEnum::WECHAT_OA);
            $userInfo = $userServer->getResopnseByUserInfo()->authUserLogin()->getUserInfo();

            // 更新登录信息
            self::updateLoginInfo($userInfo['id']);

            Db::commit();
            return $userInfo;
        } catch (\Exception $e) {
            Db::rollback();
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * 当前请求小程序所属 OEM 团队 id(主站/旧版OEM=0),微信小程序凭证按租户隔离
     */
    private static function mnpTenantId(): int
    {
        return \app\api\logic\TeamLogic::currentRequestSiteTeamId();
    }


    /**
     * @notes 小程序-静默登录
     * @param array $params
     * @return array|false
     * @author 段誉
     * @date 2022/9/20 19:47
     */
    public static function silentLogin(array $params)
    {
        try {
            //通过code获取微信 openid
            $response = (new WeChatMnpService(self::mnpTenantId()))->getMnpResByCode($params['code']);
            $userServer = new WechatUserService($response, UserTerminalEnum::WECHAT_MMP);
            $userInfo = $userServer->getResopnseByUserInfo('silent')->getUserInfo();

            if (!empty($userInfo)) {
                // 更新登录信息
                self::updateLoginInfo($userInfo['id']);
            }

            return $userInfo;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 小程序-授权登录
     * @param array $params
     * @return array|false
     * @author 段誉
     * @date 2022/9/20 19:47
     * $type = 0 小程序
     */
    public static function mnpLogin(array $params)
    {
        Db::startTrans();
        try {
            //通过code获取微信 openid
            $response = (new WeChatMnpService(self::mnpTenantId()))->getMnpResByCode($params['code']);
            $response['phoneNumber'] = trim((string)($params['phoneNumber'] ?? ''));
            $response['invite_code'] = $params['invite_code'] ?? '';
            $userServer = new WechatUserService($response, UserTerminalEnum::WECHAT_MMP);

            $check = $userServer->checkPhoneNumber();//检查手机号是否已被绑定
            $userServer->getResopnseByUserInfo($check);
            // 新手机号 + openid 已绑其他账号：按注册新号处理，需走下方注册策略校验
            if (!$check) {
                $userServer->releaseUserForNewMobileRegistration();
            }
            if (!$userServer->hasUser()) {
                // 开放手机号注册时，小程序一键登录可自动建号；关闭/邀请码未填则拦截
                $registerMode = self::getRegisterMode();
                if ($registerMode['closed']) {
                    throw new \Exception('当前平台暂停注册，请联系管理员');
                }
                if ($registerMode['require_invite'] && trim((string)($params['invite_code'] ?? '')) === '') {
                    throw new \Exception('账号不存在,请先注册');
                }
                if (trim((string)($params['phoneNumber'] ?? '')) === '') {
                    throw new \Exception('请先授权手机号');
                }
            }
            $userInfo = $userServer->authUserLogin(0)->getUserInfo();
            $userInfo['is_bind_phone'] = $check ? 1 : 0;

            // 更新登录信息
            self::updateLoginInfo($userInfo['id']);

            Db::commit();
            return $userInfo;
        } catch (\Exception $e) {
            Db::rollback();
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @notes 小程序微信手机号授权注册
     * @param array $params
     * @return array|false
     */
    public static function mnpRegister(array $params)
    {
        Db::startTrans();
        try {
            $response = (new WeChatMnpService(self::mnpTenantId()))->getMnpResByCode($params['code']);
            $response['phoneNumber'] = trim((string)($params['phoneNumber'] ?? ''));
            $response['invite_code'] = $params['invite_code'] ?? '';
            if ($response['phoneNumber'] === '') {
                throw new \Exception('请先授权手机号');
            }

            $userServer = new WechatUserService($response, UserTerminalEnum::WECHAT_MMP);
            if ($userServer->checkPhoneNumber()) {
                throw new \Exception('账号已存在，请直接登录');
            }

            $userServer->getResopnseByUserInfo(false);
            if ($userServer->hasUser()) {
                throw new \Exception('账号已存在，请直接登录');
            }

            self::checkRegisterPolicy((string)($params['invite_code'] ?? ''));

            $userInfo = $userServer->authUserLogin(0)->getUserInfo();
            self::updateLoginInfo($userInfo['id']);

            Db::commit();
            return $userInfo;
        } catch (\Exception $e) {
            Db::rollback();
            self::$error = $e->getMessage();
            return false;
        }
    }

    public static function getMobileNumber(array $params)
    {
        try {
            $response = (new WeChatMnpService(self::mnpTenantId()))->getUserPhoneNumber($params['code']);
            $phoneNumber = trim((string)($response['phone_info']['purePhoneNumber'] ?? ''));
            if ($phoneNumber !== '' && preg_match('/^\+?86(\d{11})$/', $phoneNumber, $matches)) {
                $phoneNumber = $matches[1];
            }
            if (empty($phoneNumber)) {
                throw new \Exception('获取手机号码失败');
            }
            return ['phoneNumber' => $phoneNumber];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 更新登录信息
     * @param $userId
     * @throws \Exception
     * @author 段誉
     * @date 2022/9/20 19:46
     */
    public static function updateLoginInfo($userId)
    {
        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            throw new \Exception('用户不存在');
        }

        $time = time();
        $user->login_time = $time;
        $user->login_ip = request()->ip();
        $user->update_time = $time;
        $user->save();
    }


    /**
     * @notes 小程序端绑定微信
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/9/20 19:46
     */
    public static function mnpAuthLogin(array $params)
    {
        try {
            //通过code获取微信openid
            $response = (new WeChatMnpService(self::mnpTenantId()))->getMnpResByCode($params['code']);
            $response['user_id'] = $params['user_id'];
            $response['terminal'] = UserTerminalEnum::WECHAT_MMP;

            return self::createAuth($response);
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 公众号端绑定微信
     * @param array $params
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author 段誉
     * @date 2022/9/16 10:43
     */
    public static function oaAuthLogin(array $params)
    {
        try {
            //通过code获取微信openid
            $response = (new WeChatOaService())->getOaResByCode($params['code']);
            $response['user_id'] = $params['user_id'];
            $response['terminal'] = UserTerminalEnum::WECHAT_OA;

            return self::createAuth($response);
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 生成授权记录
     * @param $response
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2022/9/16 10:43
     */
    public static function createAuth($response)
    {
        //先检查openid是否有记录
        $isAuth = UserAuth::where('openid', '=', $response['openid'])->findOrEmpty();
        if (!$isAuth->isEmpty()) {
            throw new \Exception('该微信已被绑定');
        }

        if (isset($response['unionid']) && !empty($response['unionid'])) {
            //在用unionid找记录，防止生成两个账号，同个unionid的问题
            $userAuth = UserAuth::where(['unionid' => $response['unionid']])
                ->findOrEmpty();
            if (!$userAuth->isEmpty() && $userAuth->user_id != $response['user_id']) {
                throw new \Exception('该微信已被绑定');
            }
        }

        //如果没有授权，直接生成一条微信授权记录
        UserAuth::create([
            'user_id' => $response['user_id'],
            'openid' => $response['openid'],
            'unionid' => $response['unionid'] ?? '',
            'terminal' => $response['terminal'],
        ]);
        return true;
    }


    /**
     * @notes 获取扫码登录地址
     * @return array|false
     * @author 段誉
     * @date 2022/10/20 18:23
     */
    public static function getScanCode($redirectUri)
    {
        try {
            $config = WeChatConfigService::getMnpConfig();
            $appId = $config['app_id'];
            $redirectUri = UrlEncode($redirectUri);

            // 设置有效时间标记状态, 超时扫码不可登录
            $state = MD5(time() . rand(10000, 99999));
            (new WebScanLoginCache())->setScanLoginState($state);

            // 扫码地址
            $url = WeChatRequestService::getScanCodeUrl($appId, $redirectUri, $state);
            return ['url' => $url];
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 网站扫码登录
     * @param $params
     * @return array|false
     * @author 段誉
     * @date 2022/10/21 10:28
     */
    public static function scanLogin($params)
    {
        Db::startTrans();
        try {
            // 通过code 获取 access_token,openid,unionid等信息
            $userAuth = WeChatRequestService::getUserAuthByCode($params['code']);

            if (empty($userAuth['openid']) || empty($userAuth['access_token'])) {
                throw new \Exception('获取用户授权信息失败');
            }

            // 获取微信用户信息
            $response = WeChatRequestService::getUserInfoByAuth($userAuth['access_token'], $userAuth['openid']);

            // 生成用户或更新用户信息
            $userServer = new WechatUserService($response, UserTerminalEnum::PC);
            $userInfo = $userServer->getResopnseByUserInfo()->authUserLogin()->getUserInfo();

            // 更新登录信息
            self::updateLoginInfo($userInfo['id']);

            Db::commit();
            return $userInfo;
        } catch (\Exception $e) {
            Db::rollback();
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 更新用户信息
     * @param $params
     * @param $userId
     * @return User
     * @author 段誉
     * @date 2023/2/22 11:19
     */
    public static function updateUser($params, $userId)
    {
        return User::where(['id' => $userId])->update([
            'nickname' => $params['nickname'],
            'avatar' => FileService::setFileUrl($params['avatar']),
            'is_new_user' => YesNoEnum::NO
        ]);
    }

    /**
     * @notes 小程序授权PC登录
     * @param array $params
     * @return array|false
     * @author Rick
     * @date 2025/6/4 19:26
     */
    public static function mnpAuthPcLogin(array $params): bool|array
    {
        try {
            // 账号/手机号 密码登录
            $where = ['account|mobile' => $params['account']];
            $user = User::where($where)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('用户不存在,请先注册');
            }

            //更新登录信息
            $user->login_time = time();
            $user->login_ip = request()->ip();
            $user->save();

            //设置token
            $userInfo = UserTokenService::setToken($user->id, $params['terminal'], $params['auth_key']);

            //返回登录信息
            $avatar = $user->avatar ?: Config::get('project.default_image.user_avatar');
            $avatar = FileService::getFileUrl($avatar);

            return [
                'nickname' => $userInfo['nickname'],
                'sn' => $userInfo['sn'],
                'mobile' => $userInfo['mobile']
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 小程序授权状态
     * @param array $params
     * @return array|false
     * @author Rick
     * @date 2025/6/4 19:26
     */
    public static function mnpAuthStatus(array $params): array|bool
    {
        try {
            $authKey = $params['auth_key'] ?? '';
            if (!$authKey) {
                throw new \Exception('参数错误');
            }
            $user = User::alias('u')
                ->leftJoin('user_session us', 'us.user_id = u.id')
                ->where('us.auth_key', $authKey)
                ->where('us.terminal', UserTerminalEnum::PC)
                ->field('u.id,u.account,u.mobile,u.nickname,u.sn,u.avatar,us.token,us.update_time')
                ->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('未授权');
            }
            $time = time() - strtotime($user->update_time);
            if ($time < 60) {
                return [
                    'msg' => '授权成功',
                    'nickname' => $user->nickname,
                    'sn' => $user->sn,
                    'mobile' => $user->mobile,
                    'avatar' => $user->avatar,
                    'token' => $user->token
                ];
            }
            throw new \Exception('未授权');
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
