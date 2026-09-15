<?php

namespace app\api\logic\sv;


use think\facade\Db;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvSetting;
use app\common\service\sv\SvAccountBindService;
use app\common\service\sv\SvAccountChangeLog;

/**
 * SvAccountLogic
 * @desc
 * @author Qasim
 */
class SvAccountLogic extends SvBaseLogic
{

    protected static $platformMap = [
        1 => '微信(视频号)',
        3 => '小红书',
        4 => '抖音',
        5 => '快手'
    ];
    /**
     * @desc 添加
     * @param array $params
     * @return bool
     */
    public static function addSvAccount(array $params)
    {
        try {
            $device = self::deviceInfo($params['device_code']);
            if (is_bool($device)) {
                return false;
            }

            $account = trim((string)($params['account'] ?? ''));
            $deviceCode = trim((string)($params['device_code'] ?? ''));
            $type = (int)($params['type'] ?? 0);
            if ($account === '' || $deviceCode === '' || $type <= 0) {
                self::setError('账号不能为空');
                return false;
            }

            $occupied = SvAccountBindService::findOccupiedRow($type, $account, $deviceCode);
            if ($occupied !== null) {
                $msg = '该账号已经绑定在设备：' . ($occupied['device_code'] ?? '');
                self::setError($msg);
                SvAccountChangeLog::record(
                    SvAccountChangeLog::SOURCE_ADD_SV_ACCOUNT,
                    SvAccountChangeLog::ACTION_CONFLICT,
                    [
                        'user_id' => (int)self::$uid,
                        'device_code' => $deviceCode,
                        'type' => $type,
                        'account' => $account,
                        'account_id' => (int)($occupied['id'] ?? 0),
                        'before_json' => SvAccountChangeLog::snapshot($occupied),
                        'msg' => $msg,
                    ]
                );
                return false;
            }

            $extra = $params['extra'] ?? [];
            if (is_string($extra)) {
                $extra = json_decode($extra, true) ?: [];
            }
            if (!is_array($extra)) {
                $extra = [];
            }

            $payload = [
                'user_id' => (int)self::$uid,
                'device_code' => $deviceCode,
                'account' => $account,
                'account_no' => (string)($params['account_no'] ?? $account),
                'nickname' => (string)($params['nickname'] ?? $account),
                'avatar' => (string)($params['avatar'] ?? ''),
                'status' => (int)($params['status'] ?? 1),
                'type' => $type,
                'is_verified' => (int)($extra['is_verified'] ?? $params['is_verified'] ?? 0),
                'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ];

            Db::startTrans();
            try {
                $existing = SvAccount::where('type', $type)->where('account', $account)->findOrEmpty();
                if ($existing->isEmpty()) {
                    $accountModel = SvAccount::create($payload);
                    SvAccountChangeLog::record(
                        SvAccountChangeLog::SOURCE_ADD_SV_ACCOUNT,
                        SvAccountChangeLog::ACTION_INSERT,
                        [
                            'user_id' => (int)self::$uid,
                            'device_code' => $deviceCode,
                            'type' => $type,
                            'account' => $account,
                            'account_id' => (int)($accountModel->id ?? 0),
                            'after_json' => SvAccountChangeLog::snapshot(array_merge($payload, [
                                'id' => (int)($accountModel->id ?? 0),
                            ])),
                            'msg' => '新增账号',
                        ]
                    );
                } else {
                    $before = $existing->toArray();
                    $changed = [];
                    foreach (['user_id', 'status', 'type', 'is_verified'] as $intField) {
                        if ((int)($before[$intField] ?? 0) !== (int)$payload[$intField]) {
                            $changed[] = $intField;
                        }
                    }
                    foreach (['device_code', 'account', 'account_no', 'nickname', 'avatar'] as $strField) {
                        if ((string)($before[$strField] ?? '') !== (string)$payload[$strField]) {
                            $changed[] = $strField;
                        }
                    }
                    $beforeExtra = $before['extra'] ?? '';
                    if (is_array($beforeExtra)) {
                        $beforeExtra = json_encode($beforeExtra, JSON_UNESCAPED_UNICODE);
                    }
                    if ((string)$beforeExtra !== (string)$payload['extra']) {
                        $changed[] = 'extra';
                    }

                    if ($changed !== []) {
                        SvAccount::where('id', (int)$existing->id)->update($payload);
                        SvAccountChangeLog::record(
                            SvAccountChangeLog::SOURCE_ADD_SV_ACCOUNT,
                            SvAccountChangeLog::ACTION_UPDATE,
                            [
                                'user_id' => (int)self::$uid,
                                'device_code' => $deviceCode,
                                'type' => $type,
                                'account' => $account,
                                'account_id' => (int)$existing->id,
                                'changed_fields' => $changed,
                                'before_json' => SvAccountChangeLog::snapshot($before),
                                'after_json' => SvAccountChangeLog::snapshot(array_merge($before, $payload)),
                                'msg' => '更新账号资料',
                            ]
                        );
                    }
                    $accountModel = $existing->refresh();
                }

                $setting = SvSetting::where('account', $account)->findOrEmpty();
                if ($setting->isEmpty()) {
                    SvSetting::create([
                        'takeover_type' => 1,
                        'account' => $account,
                        'user_id' => self::$uid,
                    ]);
                }

                Db::commit();
                self::$returnData = $accountModel->toArray();
                return true;
            } catch (\Throwable $e) {
                Db::rollback();
                if (SvAccountBindService::isDuplicateEntry($e)) {
                    $occupiedDevice = SvAccountBindService::findOccupiedDeviceCode($type, $account, $deviceCode);
                    $msg = $occupiedDevice !== ''
                        ? ('该账号已经绑定在设备：' . $occupiedDevice)
                        : '账号已存在，请先解绑后重试';
                    self::setError($msg);
                    SvAccountChangeLog::record(
                        SvAccountChangeLog::SOURCE_ADD_SV_ACCOUNT,
                        SvAccountChangeLog::ACTION_CONFLICT,
                        [
                            'user_id' => (int)self::$uid,
                            'device_code' => $deviceCode,
                            'type' => $type,
                            'account' => $account,
                            'msg' => $msg,
                        ]
                    );
                    return false;
                }
                throw $e;
            }
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


    public static function updateSvAccount(array $params)
    {
        Db::startTrans();
        try {
            $params['status'] = 1;
            SvAccount::where('device_code', $params['device_code'])->where('type', $params['type'])->select()->delete();
            $oldSetting = SvSetting::where('account', $params['account'])->findOrEmpty();
            if (!$oldSetting->isEmpty()) {
                SvSetting::where('id', $oldSetting->id)->delete();
            }
            // 获取信息
            unset($params['id']);
            $params['user_id'] = self::$uid;
            $extra = json_decode($params['extra'], true);
            $params['is_verified'] = $extra['is_verified'] ?? 0;
            $params['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE);
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
                    'user_id' => self::$uid,
                    'open_ai' => $oldSetting->open_ai ?: 0,
                    'robot_id' => $oldSetting->robot_id ?: 0,
                    'takeover_mode' => $oldSetting->takeover_mode ?: 0,
                ];
                SvSetting::create($setting);
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
     * @desc 更新
     * @param array $params
     * @return bool
     */
    public static function updateSvAccount1(array $params)
    {
      
        Db::startTrans();
        try {
            $params['status'] = 1;
            self::checkAccount($params);
            SvAccount::where('account', $params['account'])->where('type', $params['type'])->select()->delete();
            SvSetting::where('account', $params['account'])->select()->delete();
            // 获取信息
            $account = self::accountInfo($params['account'], false, $params['type']);
            if (is_bool($account)) {
                // if (isset($params['id']) && $params['id'] > 0) {
                //     $account = SvAccount::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
                //     if ($account->isEmpty()) {
                //         self::setError('账号不存在');
                //         return false;
                //     }
                //     SvPublishSettingAccount::where('account', $account['account'])
                //         ->where('user_id', self::$uid)
                //         ->where('status', 1)
                //         ->where('account_type', $account['type'])
                //         ->update(['status' => 0]);
                // }


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
            }  else  {
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

    private static function checkAccount(array $params)
    {
        try {
            $account = SvAccount::where('type', $params['type'])->where('device_code', $params['device_code'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$account->isEmpty()) {
                if ($params['account'] !== $account->account) {
                    throw new \Exception('该设备已经绑定了' . self::$platformMap[$account->type] . '账号' . $account->account . '(' . $account->nickname . ')');
                }
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
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

            if ((int)$params['account_type'] === 1) {
                \app\common\model\wechat\AiWechatSetting::where('wechat_id', $params['account'])->update([
                    'open_ai' => $params['open_ai'] ?? 0,
                    'robot_id' => $params['robot_id'] ?? 0,
                    'takeover_mode' => $params['takeover_mode'] ?? 0,
                    'update_time' => time(),
                ]);
            }
            unset($params['account_type']);
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
                ->where('status', 1)
                ->where('account_type', $account['type'])
                ->update(['status' => 0]);

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
