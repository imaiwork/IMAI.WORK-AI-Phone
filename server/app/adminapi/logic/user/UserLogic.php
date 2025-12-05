<?php

namespace app\adminapi\logic\user;

use app\common\enum\user\AccountLogEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\UserService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;
use think\facade\Config;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\recharge\GiftPackage;
use app\common\model\survey\Surveys;
use app\common\model\user\UserTokensLog;
use Exception;

/**
 * 用户逻辑层
 * Class UserLogic
 * @package app\adminapi\logic\user
 */
class UserLogic extends BaseLogic
{

    /**
     * @notes 用户详情
     * @param int $userId
     * @return array
     * @author 段誉
     * @date 2022/9/22 16:32
     */
    public static function detail(int $userId): array
    {
        $field = [
            'id',
            'sn',
            'account',
            'nickname',
            'avatar',
            'real_name',
            'sex',
            'mobile',
            'create_time',
            'login_time',
            'channel',
            'user_money',
            'tokens',
            'user_type'
        ];

        $user = User::where(['id' => $userId])->field($field)
            ->findOrEmpty();
        
        $user['channel'] = UserTerminalEnum::getTermInalDesc($user['channel']);

        $user->sex = $user->getData('sex');

        //加载企业信息
        $user['company_name'] = Surveys::where('user_id', $user['id'])->value('company_name') ?? '';

        // 累计充值金额
        $user['sum_price'] = GiftPackageOrder::where('user_id', $userId)->where('pay_status', 1)->sum('order_amount');

        $user['orders'] = [];

        // 累计算力使用次数
        $user['tokens_times']   = UserTokensLog::where('user_id', $userId)->where('task_id', '<>', '')->count('DISTINCT task_id');

        return $user->toArray();
    }


    /**
     * @notes 更新用户信息
     * @param array $params
     * @return User
     * @author 段誉
     * @date 2022/9/22 16:38
     */
    public static function setUserInfo(array $params)
    {
        return User::update([
            'id' => $params['id'],
            $params['field'] => $params['value']
        ]);
    }


    /**
     * @notes 调整用户余额
     * @param array $params
     * @return bool|string
     * @author 段誉
     * @date 2023/2/23 14:25
     */
    public static function adjustUserMoney(array $params)
    {
        Db::startTrans();
        try {
            $user = User::find($params['user_id']);
            if (AccountLogEnum::INC == $params['action']) {
                //调整可用余额
                $user->user_money += $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::UM_INC_ADMIN,
                    AccountLogEnum::INC,
                    $params['num'],
                    '',
                    $params['remark'] ?? ''
                );
            } else {
                $user->user_money -= $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::UM_DEC_ADMIN,
                    AccountLogEnum::DEC,
                    $params['num'],
                    '',
                    $params['remark'] ?? ''
                );
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }


    /**
     * @notes 调整用户算力
     * @param array $params
     * @return bool|string
     * @author 段誉
     * @date 2023/2/23 14:25
     */
    public static function adjustUserTokens(array $params)
    {
        Db::startTrans();
        try {
            $user = User::find($params['user_id']);
            if (AccountLogEnum::INC == $params['action']) {
                //调整可用余额
                $user->tokens += $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::TOKENS_INC_ADMIN,
                    AccountLogEnum::INC,
                    $params['num'],
                    '1',
                    '',
                    !empty($params['remark']) ? $params['remark'] : '管理员增加算力'
                );
            } else {
                $user->tokens -= $params['num'];
                $user->save();
                //记录日志
                AccountLogLogic::add(
                    $user->id,
                    AccountLogEnum::TOKENS_DEC_ADMIN,
                    AccountLogEnum::DEC,
                    $params['num'],
                    '1',
                    '',
                    !empty($params['remark']) ? $params['remark'] : '管理员扣除算力'
                );
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }


     /**
     * @notes 更新用户信息
     * @param array $params
     * @return User
     * @author 段誉
     * @date 2022/9/22 16:38
     */
    public static function setUserPas(array $params)
    {


        $user = User::findOrEmpty($params['id']);
        if (empty($user)) {
            return false;
        }
        if (!empty($params['password'])) {
            $passwordSalt = Config::get('project.unique_identification');
            $params['password'] = create_password($params['password'], $passwordSalt);
        }

        return User::update([
            'id' => $params['id'],
            'password' => $params['password']
        ]);
    }


    public static function createUser(array $params): bool
    {
        try {
            $userSn = User::createUserSn();
            $passwordSalt = Config::get('project.unique_identification');
            $password     = create_password($params['password'], $passwordSalt);

            if ($params['password'] != $params['password_confirm']) {
                throw new Exception('两次密码不一致');
            }

            $modelUser = new User();
            $isMobile = $modelUser->where(['mobile' => $params['mobile']])->findOrEmpty();
            if (!$isMobile->isEmpty()) {
                throw new Exception('手机已被占用,换一个吧！');
            }

            if (empty($params['nickname'])) {
                $params['nickname'] = '用户' . $userSn;
            }
            if (empty($params['avatar'])) {
                $params['avatar'] = ConfigService::get('default_image', 'user_avatar');
            }

            $user = User::create([
                'sn'        => $userSn,
                'avatar'    => FileService::setFileUrl($params['avatar']),
                'real_name' => $params['real_name'] ?? '',
                'nickname'  => $params['nickname']  ?? '',
                'mobile'    => $params['mobile'] ?? '',
                'account'   => $params['mobile'] ?? '',
                'password'  => $password,
                'channel'   => UserTerminalEnum::ADMIN,
            ]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 批量导入用户（仅手机号+算力）—— 真 · 一条 SQL
     */
    public static function import($file)
    {
        $fileinfo = $file->getRealPath();
        $spreadsheet = IOFactory::load($fileinfo);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true); // 二维数组
        $salt  = Config::get('project.unique_identification');
        $defaultAvatar = ConfigService::get('default_image', 'user_avatar');
        /* 1. 预生成所有数据 */
        $insertData = [];
        $now = time();
        $modelUser = new User();

        foreach ($rows as $k => $row) {
            if ($k == 1) continue; // 跳过表头
            if ($row['A'] == '' || $row['B'] == '') continue;
            $mobile = trim($row['A']);
            $token = trim($row['B']);

            if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
                throw new \Exception("第" . ($k + 1) . "行手机格式错误");
            }
            if ($token < 0) {
                throw new \Exception("第" . ($k + 1) . "行算力不能小于0");
            }
            $isMobile = $modelUser->where(['mobile' => $mobile])->findOrEmpty();
            if (!$isMobile->isEmpty()) {
                throw new Exception("第" . ($k + 1) . "行手机已被占用,换一个吧！");
            }
            $token = round($token, 2);
            $sn   = User::createUserSn();          // 还是原规则
            $pwd  = create_password($mobile, $salt);

            $insertData[] = [
                'sn'        => $sn,
                'account'   => $mobile,
                'mobile'    => $mobile,
                'password'  => $pwd,
                'nickname'  => '用户' . $sn,
                'tokens'    => $token,
                'avatar'    => $defaultAvatar,
                'channel'   => UserTerminalEnum::ADMIN,
                'create_time' => $now,
                'update_time' => $now,
            ];

        }
        Db::startTrans();          // 事务

        /* 2. 一条 SQL 插完 */
        try {
            (new User)->insertAll($insertData);   // TP 自带批量插入
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }


    }

}
