<?php

namespace app\api\logic\sv;
use think\facade\Db;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvSetting;

/**
 * SvAccountLogic
 * @desc
 * @author Qasim
 */
class SvAccountLogic extends SvBaseLogic
{

    /**
     * @desc 添加
     * @param array $params
     * @return bool
     */
    public static function addSvAccount(array $params)
    {

        try {
            // 获取设备信息
            $device = self::deviceInfo($params['device_code']);
            if (is_bool($device)) {
                return false;
            }

            // 获取信息
            $account = self::accountInfo($params['account'], false, $params['type']);
            if ($account instanceof SvAccount) {
                $params['user_id'] = self::$uid;
                $params['update_time'] = time();
                $res = SvAccount::where('account', $params['account'])->where('user_id', self::$uid)->update($params);
                $account = self::accountInfo($params['account'], false, $params['type']);

                $data = $account->toArray();
                self::$returnData = $data;
                return true;
            }

            $params['user_id'] = self::$uid;

            // 添加
            $account = SvAccount::create($params);

            // 添加默认设置
            $setting = SvSetting::where('account', $account->account)->findOrEmpty();
            if ($setting->isEmpty()) {
                $setting = [
                    'takeover_type' => 1,
                    'account' => $account->account,
                    'user_id' => self::$uid
                ];
                SvSetting::create($setting);
            }

            // 返回设备信息 
            $data = $account->toArray();
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取详情
     * @param array $params
     * @return bool
     */
    public static function detailSvAccount(array $params)
    {

        try {
            // 检查是否存在
            $account = SvAccount::alias('w')
                ->join('sv_setting s', 's.account = w.account')
                ->where('w.account', $params['account'])
                ->where('w.user_id', self::$uid)
                ->findOrEmpty();

            if ($account->isEmpty()) {
                self::setError('账号不存在');
                return false;
            }

            $account->robot_id = $account->robot_id ?? 0;
            if($account->robot_id === 0){
                $account->robot_id = '';
            }
            // 返回设备信息 
            self::$returnData = $account->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新
     * @param array $params
     * @return bool
     */
    public static function updateSvAccount(array $params)
    {  Db::startTrans();
        try {
            $params['status'] = 1;
            // 获取信息
            $account = self::accountInfo($params['account'], false, $params['type']);
            if (is_bool($account)) {
                if(isset($params['id']) && $params['id'] > 0){
                    $account = SvAccount::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
                    if ($account->isEmpty()) {
                        self::setError('账号不存在');
                        return false;
                    }
                    SvPublishSettingAccount::where('account', $account['account'])
                        ->where('user_id', self::$uid)
                        ->where('status',1)
                        ->where('account_type', $account['type'])
                        ->update(['status'=>0]);
                }


                unset($params['id']);
                $params['user_id'] = self::$uid;
                
                // 添加
                $account = SvAccount::create($params);
                $data = $account->toArray();
                self::$returnData = $data;
                // 添加默认设置
                $setting = SvSetting::where('account', $account->account)->findOrEmpty();
                if ($setting->isEmpty()) {
                    $setting = [
                        'takeover_type' => 1,
                        'account' => $account->account,
                        'user_id' => self::$uid
                    ];
                    SvSetting::create($setting);
                }
            }else{
                unset($params['id']);
                SvAccount::where('id', $account->id)->update($params);
                self::$returnData = $account->refresh()->toArray();
            }

            // 获取设备信息
            $device = self::deviceInfo($params['device_code']);
            if (is_bool($device)) {
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 更新Ai模式
     * @param array $params
     * @return bool
     */
    public static function updateSvAccountAi(array $params)
    {
        try {
            // 获取信息
            $account = self::accountInfo($params['account'], true, $params['account_type'] ?? 3);
            if (is_bool($account)) {
                return false;
            }

            // 是否存在设置
            $setting = SvSetting::where('account', $account->account)->findOrEmpty();
            if ($setting->isEmpty()) {
                $setting = [
                    'takeover_type' => 1,
                    'account' => $account->account,
                    'account_type' => $account->type,
                    'user_id' => self::$uid
                ];
                SvSetting::create($setting);
            }

            // 更新设置
            SvSetting::where('account', $account->account)->update($params);

            self::$returnData = $setting->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 下线
     * @param array $params
     * @return bool
     */
    public static function offlineSvAccount(array $params)
    {
        try {
            // 获取信息
            $account = self::accountInfo($params['account']);
            if (is_bool($account)) {
                return false;
            }

            $account->status = 0;
            $account->save();

            self::$returnData = $account->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function deleteSvAccount(array $params)
    {
        Db::startTrans();
        try {
            $account = SvAccount::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('账号不存在');
                return false;
            }
            $account->delete();
            SvPublishSettingAccount::where('account', $account['account'])
                ->where('user_id', self::$uid)
                ->where('status',1)
                ->where('account_type', $account['type'])
                ->update(['status'=>0]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
//            clogger($e);
            self::setError($e->getMessage());
            return false;
        }
    }
}
