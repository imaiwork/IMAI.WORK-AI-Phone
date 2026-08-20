<?php


namespace app\api\logic\service;


use app\common\enum\user\UserTerminalEnum;
use app\common\enum\YesNoEnum;
use app\common\model\user\{User, UserAuth};
use app\common\service\{ConfigService, storage\Driver as StorageDriver};
use think\Exception;
use app\common\logic\AccountLogLogic;
use app\common\enum\user\AccountLogEnum;

/**
 * 用户功能类（主要微信登录后创建和更新用户）
 * Class WechatUserService
 * @package app\api\service
 */
class WechatUserService
{

    protected int $terminal = UserTerminalEnum::WECHAT_MMP;
    protected array $response = [];
    protected ?string $code = null;
    protected ?string $openid = null;
    protected ?string $unionid = null;
    protected ?string $nickname = null;
    protected ?string $headimgurl = null;
    protected ?string $mobile = null;
    protected ?string $inviteCode = null;
    protected User $user;


    public function __construct(array $response, int $terminal)
    {
        $this->terminal = $terminal;
        $this->setParams($response);
    }


    /**
     * @notes 设置微信返回的用户信息
     * @param $response
     * @author kb
     * @date 2021/8/2 11:49
     */
    private function setParams($response): void
    {
        $this->response = $response;
        $this->openid = $response['openid'];
        $this->unionid = $response['unionid'] ?? '';
        $this->nickname = $response['nickname'] ?? '';
        $this->headimgurl = $response['headimgurl'] ?? '';
        $this->mobile = $this->normalizeMobile($response['phoneNumber'] ?? '');
        $this->inviteCode = $response['invite_code'] ?? request()->param('invite_code', '');
    }

    /**
     * @notes 规范化手机号（去空格、去 +86/86 前缀）
     */
    private function normalizeMobile($mobile): string
    {
        $mobile = trim((string)$mobile);
        if ($mobile === '') {
            return '';
        }
        if (preg_match('/^\+?86(\d{11})$/', $mobile, $matches)) {
            return $matches[1];
        }
        return $mobile;
    }

    /**
     * @notes 按手机号查找已有账号（重复时取最早创建的）
     */
    private function findUserByMobile()
    {
        if (empty($this->mobile)) {
            return User::where('id', 0)->findOrEmpty();
        }
        return User::where('mobile', $this->mobile)
            ->order('id', 'asc')
            ->findOrEmpty();
    }

    /**
     * @notes 有手机号时优先合并到已占用该号的账号（后台建号 / 其他端）
     */
    private function resolveUserByMobile(): void
    {
        $mobileUser = $this->findUserByMobile();
        if ($mobileUser->isEmpty()) {
            return;
        }
        if (!isset($this->user) || $this->user->isEmpty() || (int)$this->user->id !== (int)$mobileUser->id) {
            $this->user = $mobileUser;
        }
    }

    public function checkPhoneNumber()
    {
        if (empty($this->mobile)) {
            return false;
        }

        return !$this->findUserByMobile()->isEmpty();
    }


    /**
     * @notes 根据手机号 / openid / unionid 获取系统用户信息
     * @param bool|string $check true=按手机号；其余按 openid/unionid（含 silent）
     * @return $this
     * @author 段誉
     * @date 2022/9/23 16:09
     */
    public function getResopnseByUserInfo($check = false): self
    {
        $openid = $this->openid;
        $unionid = $this->unionid;

        $query = User::alias('u')
            ->field('u.id,u.sn,u.mobile,u.nickname,u.avatar,u.mobile,u.is_disable,u.is_new_user')
            ->join('user_auth au', 'au.user_id = u.id', 'left');

        // 必须严格 true：silentLogin 传 'silent' 时不能误走手机号分支
        if ($check === true) {
            $query->where('u.mobile', '=', $this->mobile)->order('u.id', 'asc');
        } else {
            $query->where(function ($query) use ($openid, $unionid) {
                $query->whereOr(['au.openid' => $openid]);
                if (isset($unionid) && $unionid) {
                    $query->whereOr(['au.unionid' => $unionid]);
                }
            });
        }
        $user = $query->findOrEmpty();

        $this->user = $user;
        return $this;
    }

    public function hasUser(): bool
    {
        return isset($this->user) && !$this->user->isEmpty();
    }

    /**
     * @notes openid 已绑账号 A，但本次授权的是未占用的新手机号 B，且 A 已有不同手机号时：
     * 视为注册新账号，而非给 A 换绑手机号。
     * 随后 createUser + bindUserAuth 会把当前 openid 归到 B，并退役 A 上的同 openid。
     */
    public function releaseUserForNewMobileRegistration(): self
    {
        if (!$this->hasUser() || $this->mobile === '') {
            return $this;
        }

        $existingMobile = trim((string)$this->user->mobile);
        if ($existingMobile === '' || $existingMobile === $this->mobile) {
            return $this;
        }

        // 新手机号已被占用时由 resolveUserByMobile 合并到主人账号，这里不建号
        if (!$this->findUserByMobile()->isEmpty()) {
            return $this;
        }

        $this->user = User::where('id', 0)->findOrEmpty();
        return $this;
    }


    /**
     * @notes 获取用户信息
     * @param bool $isCheck 是否验证账号是否可用
     * @return array
     * @throws Exception
     * @author kb
     * @date 2021/8/3 11:42
     */
    public function getUserInfo($isCheck = true): array
    {
        if (!$this->user->isEmpty() && $isCheck) {
            $this->checkAccount();
        }
        if (!$this->user->isEmpty()) {
            $this->getToken();
        }
        return $this->user->toArray();
    }


    /**
     * @notes 校验账号
     * @throws Exception
     * @author 段誉
     * @date 2022/9/16 10:14
     */
    private function checkAccount()
    {
        if ($this->user->is_disable) {
            throw new Exception('您的账号异常，请联系客服。');
        }
    }


    /**
     * @notes 创建用户
     * @throws Exception
     * @author 段誉
     * @date 2022/9/16 10:06
     */
    private function createUser(int $type = 1): void
    {
        // 并发/漏检兜底：手机号已被占用则合并，禁止再建号
        if (!empty($this->mobile)) {
            $existUser = $this->findUserByMobile();
            if (!$existUser->isEmpty()) {
                $this->user = $existUser;
                $this->updateUser($type);
                return;
            }
        }

        $parentId = \app\api\logic\LoginLogic::checkRegisterPolicy((string)$this->inviteCode);

        //设置头像
        if (empty($this->headimgurl)) {
            // 默认头像
            $defaultAvatar = config('project.default_image.user_avatar');
            $avatar = ConfigService::get('default_image', 'user_avatar', $defaultAvatar);
        } else {
            // 微信获取到的头像信息
            $avatar = $this->getAvatarByWechat();
        }
        $tokens = ConfigService::get('default_tokens', 'tokens', 0);
        $userSn = User::createUserSn();
        $this->user->sn = $userSn;
        $this->user->account = 'u' . $userSn;
        $this->user->nickname = "用户" . $userSn;
        $this->user->avatar = $avatar;
        $this->user->channel = $this->terminal;
        $this->user->mobile = $this->mobile;
        $this->user->is_new_user = YesNoEnum::YES;
        $this->user->tokens = $tokens;
        $this->user->source = \app\api\logic\LoginLogic::getDefaultInviteSource();
        // OEM 站点注册:team_id 散客归属 + origin_team_id 锁定站点原生归属
        $oemTeamId = \app\api\logic\TeamLogic::registerAttributionTeamId();
        $this->user->team_id = $oemTeamId;
        $this->user->origin_team_id = $oemTeamId;

        if ($this->terminal != UserTerminalEnum::WECHAT_MMP && !empty($this->nickname)) {
            $this->user->nickname = $this->nickname;
        }

        $this->user->save();


        //注册赠送算力
        if (!empty($tokens)) {
            // add(userId, changeType, action, amount, status, sourceSn, remark)
            AccountLogLogic::add(
                $this->user->id,
                AccountLogEnum::TOKENS_INC_REGISTER,
                AccountLogEnum::INC,
                $tokens,
                1,
                '',
                AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_INC_REGISTER)
            );
        }

        // 分销邀请绑定
        \app\api\logic\LoginLogic::seedInviteRelation($this->user->id, $parentId);

        $this->bindUserAuth();
    }


    /**
     * @notes 更新用户信息
     * @throws Exception
     * @author 段誉
     * @date 2022/9/16 10:06
     * @remark 该端没授权信息,重新写入一条该端的授权信息
     */
    private function updateUser($type = 1): void
    {

        // 无头像需要更新头像
        if (empty($this->user->avatar)) {
            $this->user->avatar = $this->getAvatarByWechat();
            $this->user->save();
        }

        // 登录流程只允许「首次绑定」空手机号；已有手机号绝不在此覆盖（换号走独立注册）
        if ($this->mobile !== '' && trim((string)$this->user->mobile) === '') {
            $conflict = User::where('mobile', $this->mobile)
                ->where('id', '<>', $this->user->id)
                ->findOrEmpty();
            if ($conflict->isEmpty()) {
                $this->user->mobile = $this->mobile;
                $this->user->save();
            }
        }

        $this->bindUserAuth();
    }

    /**
     * 绑定/更新当前端授权：
     * - 优先复用已有 openid 记录（避免 UNIQUE openid 冲突）
     * - 否则按 user_id + terminal 更新/创建
     * - 禁止把小程序 openid 写到公众号等其他端授权行上
     */
    private function bindUserAuth(): void
    {
        $openid = (string)$this->openid;
        if ($openid === '') {
            throw new Exception('微信openid缺失');
        }

        $userId = (int)$this->user->id;

        // 1) openid 已存在：归并到当前用户
        $byOpenid = UserAuth::where('openid', $openid)->findOrEmpty();
        if (!$byOpenid->isEmpty()) {
            if ((int)$byOpenid->user_id !== $userId) {
                // 旧绑定释放：改写 openid，保留历史痕迹
                $byOpenid->openid = $openid . '_' . $byOpenid->user_id . '_' . time();
                $byOpenid->save();
            } else {
                if (empty($byOpenid->unionid) && !empty($this->unionid)) {
                    $byOpenid->unionid = $this->unionid;
                }
                // 纠正终端标记（历史脏数据）
                if ((int)$byOpenid->terminal !== (int)$this->terminal) {
                    $byOpenid->terminal = $this->terminal;
                }
                $byOpenid->save();
                // 清理同用户同端的其他脏行，避免支付时按 terminal 查到旧 openid
                self::retireDuplicateTerminalAuth($userId, (int)$this->terminal, (int)$byOpenid->id);
                return;
            }
        }

        // 2) 当前用户在该端是否已有授权行（取 id 最小的一条作为主记录）
        $byTerminal = UserAuth::where([
            'user_id'  => $userId,
            'terminal' => (int)$this->terminal,
        ])->order('id', 'asc')->findOrEmpty();

        if (!$byTerminal->isEmpty()) {
            if ((string)$byTerminal->openid !== $openid) {
                $byTerminal->openid = $openid;
            }
            if (empty($byTerminal->unionid) && !empty($this->unionid)) {
                $byTerminal->unionid = $this->unionid;
            }
            $byTerminal->save();
            self::retireDuplicateTerminalAuth($userId, (int)$this->terminal, (int)$byTerminal->id);
            return;
        }

        // 3) 新建该端授权
        $created = UserAuth::create([
            'user_id'  => $userId,
            'openid'   => $openid,
            'unionid'  => $this->unionid,
            'terminal' => (int)$this->terminal,
        ]);
        self::retireDuplicateTerminalAuth($userId, (int)$this->terminal, (int)$created->id);
    }

    /**
     * 同用户同端只保留一条有效授权，其余改写 openid 退役，避免唯一键与支付校验冲突
     */
    private static function retireDuplicateTerminalAuth(int $userId, int $terminal, int $keepId): void
    {
        $duplicates = UserAuth::where([
            'user_id'  => $userId,
            'terminal' => $terminal,
        ])->where('id', '<>', $keepId)->select();

        foreach ($duplicates as $row) {
            $oldOpenid = (string)$row->openid;
            // 已退役过的不再重复改写
            if (str_contains($oldOpenid, '_retired_')) {
                continue;
            }
            $row->openid = $oldOpenid . '_retired_' . $row->id . '_' . time();
            $row->save();
        }
    }


    /**
     * @notes 获取token
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author kb
     * @date 2021/8/2 16:45
     */
    private function getToken(): void
    {
        $user = UserTokenService::setToken($this->user->id, $this->terminal);
        $this->user->token = $user['token'];
    }


    /**
     * @notes 用户授权登录，
     * 如果用户不存在，创建用户；用户存在，更新用户信息，并检查该端信息是否需要写入
     * @return WechatUserService
     * @throws Exception
     * @author kb
     * @date 2021/8/2 16:35
     */
    public function authUserLogin($type = 1): self
    {
        // openid 命中孤儿号、但手机号已属于后台账号时：切到手机号主人再绑定
        $this->resolveUserByMobile();
        // openid 命中已有手机号的账号，但本次是未占用的新号：释放旧用户，走建号
        $this->releaseUserForNewMobileRegistration();

        if ($this->user->isEmpty()) {
            $this->createUser((int)$type);
        } else {
            $this->updateUser($type);
        }
        return $this;
    }


    /**
     * @notes 处理从微信获取到的头像信息
     * @return string
     * @throws Exception
     * @author 段誉
     * @date 2022/9/16 9:50
     */
    public function getAvatarByWechat(): string
    {
        // 存储引擎
        $config = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine' => ConfigService::get('storage')
        ];

        $fileName = md5($this->openid . time()) . '.jpeg';

        if ($config['default'] == 'local') {
            // 本地存储
            $avatar = download_file($this->headimgurl, 'uploads/user/avatar/', $fileName);
        } else {
            // 第三方存储
            $avatar = 'uploads/user/avatar/' . $fileName;
            $StorageDriver = new StorageDriver($config);
            if (!$StorageDriver->fetch($this->headimgurl, $avatar)) {
                throw new Exception('头像保存失败:' . $StorageDriver->getError());
            }
        }
        return $avatar;
    }
}
