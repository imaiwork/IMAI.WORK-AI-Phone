<?php


namespace app\api\logic\device;

use app\api\logic\aiPersona\BasePersonaLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\logic\sv\DeviceLogic as SvDeviceLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\Material as MaterialModel;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceUsed;
use app\common\model\sv\SvSetting;
use app\common\model\user\User;
use app\common\service\aiPersona\AiPersonaTextService;
use app\common\service\device\RpaDeviceDispatchService;
use app\common\service\MemberService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 设备任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\device
 */
class DeviceLogic extends BasePersonaLogic
{

    const CONTENT_PUBLISH_SCENE = 5;


    public static function getTimesByType(int $personaType)
    {
        $maps = [
            1 => [
                1 => [
                    '08:00-08:30' => '08:02,0', //发布时间，表示用同一个生成的视频
                ]
            ],
            2 => [
                1 => [
                    '08:30-09:00' => '08:32,0',
                ]
            ],
            3 => [
                1 => [
                    '08:30-09:00' => '08:32,0',
                    '16:30-17:00' => '16:31,1',
                ]

            ],
        ];
        return $maps[$personaType] ?? [];
    }

    private static function getExecTime(array $times, SvDevice $device, int $personaType)
    {

        $res = [];
        foreach ($times as $key => $timeSlots) {
            foreach ($timeSlots as $slot => $value) {
                list($st, $et) = explode('-', $slot);
                if (!self::checkScheduleIsCreate([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'persona_type' => $personaType,
                    'start_time' => $st,
                    'end_time' => $et,
                    'persona_id'=>$device->persona_id,
                    'scene' => self::CONTENT_PUBLISH_SCENE
                ])) {
                    continue;
                }
                // 解析 "时间,标志"
                list($time, $flag) = explode(',', $value);
                $res[$flag][$slot][$key] = $time;
            }
        }
        return $res;
    }

    public static function detail(array $params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $find['accounts'] = SvAccount::alias('w')
                ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id')
                ->leftJoin('sv_setting s', 's.account = w.account')
                ->where('w.device_code', '=', $find['device_code'])
                // ->when($params['type'], function ($query) use ($params) {
                //     $query->where('w.type', $params['type']);
                // })
                ->group('w.type')
                ->order('w.id', 'desc')
                ->select()
                ->each(function ($item) {
                    if (empty($item['takeover_mode'])) {
                        $item['takeover_mode'] = 0;
                    }

                    if (empty($item['robot_id'])) {
                        $item['robot_id'] = 0;
                    }

                    $item['robot_name'] = \app\common\model\kb\KbRobot::where('id', $item['robot_id'])->where('user_id', self::$uid)->value('name', '');

                    if (!empty($item['extra'])) {
                        $extraArray = json_decode($item['extra'], true);
                    } else {
                        $extraArray = [];
                    }
                    foreach ($extraArray  as $key => $v) {
                        $item[$key] = $v;
                    }

                    return $item;
                })
                ->toArray();
            $find['device_name'] = is_null($find['device_name']) ? $find['device_model'] : $find['device_name'];

            $find['is_auto_setting'] = 0;
            if ($find['auto_type'] === 1) {
                $config = \app\common\model\auto\AutoDeviceConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty();
                list($setting, $task_status, $is_config) = self::getAutoConfigStatus($config);
                $find['is_auto_setting'] = $is_config;
            }
            $find['persona_info'] = AiPersona::where('id', $find->persona_id)->findOrEmpty();
            $find['auth_start_time'] = !empty($find['auth_start_time']) && $find['auth_start_time'] > 0 ? date('Y-m-d H:i:s', $find['auth_start_time']) : '';
            $find['auth_expire_time'] = !empty($find['auth_expire_time']) && $find['auth_expire_time'] > 0 ? date('Y-m-d H:i:s', $find['auth_expire_time']) : '永久';
            $find['is_used'] = SvDeviceUsed::where('user_id', $find->user_id)->where('device_code', $find['device_code'])->value('is_used');
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update(array $params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $beforeData = $find->toArray();
            $didWrite = false;
            if (isset($params['persona_id'])) {
                $persona = AiPersona::where('id', $params['persona_id'])->findOrEmpty();
                if ($persona->isEmpty()) {
                    self::setError('IP人设不存在');
                    return false;
                }
                //$oldPersonaType = AiPersona::where('id', $find->persona_id)->value('persona_type');
                if ((int)$params['persona_id'] !== (int)$find->persona_id) {
                    $find->persona_id = (int)$params['persona_id'];
                    $find->is_first = 1;
                    $find->save();
                    $didWrite = true;
                    self::deleteOldPersonaTask($find, '设备人设解绑，任务取消');
                    $materialIds = MaterialModel::where('user_id', $find->user_id)->where('persona_id', $find->persona_id)->column('id');
                    if ($materialIds) {
                        foreach ($materialIds as $materialId) {
                            $rediskey = 'material_' . $materialId . '_device_' . $find->device_code;
                            Cache::store('material_redis')->delete($rediskey);
                        }
                    }
                }
            } else {
                $find->save($params);
                $didWrite = true;
            }

            if ($didWrite) {
                $find->refresh();
                $afterData = $find->toArray();
                if ($beforeData !== $afterData) {
                    Log::channel('device')->write(json_encode([
                        'msg' => '设备更新数据变动',
                        'user_id' => self::$uid,
                        'device_code' => $params['device_code'] ?? '',
                        'request' => $params,
                        'before' => $beforeData,
                        'after' => $afterData,
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'update');
                }
            }

            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function qrcode(array $params): bool
    {
        try {
            $deviceCode = trim($params['device_code'] ?? '');
            if ($deviceCode === '') {
                self::setError('设备号不能为空');
                return false;
            }

            $device = SvDevice::where('device_code', $deviceCode)
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($device->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }

            $domain = self::getDeviceQrcodeDomain();
            $jsonData = json_encode([
                'device_code' => $deviceCode,
                'domain' => $domain,
                'user_id' => (int)self::$uid,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $publicPath = '/qrcode/deviceCode/' . md5($jsonData) . '.png';
            $filePath = root_path() . 'public' . $publicPath;

            if (!file_exists($filePath)) {
                if (!is_dir(dirname($filePath))) {
                    umask(0);
                    mkdir(dirname($filePath), 0777, true);
                }

                $writer = new PngWriter();
                $qrCode = QrCode::create($jsonData)
                    ->setSize(150)
                    ->setMargin(10);
                $writer->write($qrCode)->saveToFile($filePath);
            }

            self::$returnData = [
                'device_code' => $deviceCode,
                'domain' => $domain,
                'user_id' => (int)self::$uid,
                'url' => 'https://' . $domain . $publicPath,
                'path' => $publicPath,
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function getDeviceQrcodeDomain(): string
    {
        $host = trim((string)(env('app.host') ?: config('app.app_host')));
        $domain = parse_url($host, PHP_URL_HOST);
        if ($domain) {
            return $domain;
        }

        return $host !== '' ? trim($host, '/') : ($_SERVER['HTTP_HOST'] ?? '');
    }

    public static function scanOldQrcode(array $params): bool
    {
        Db::startTrans();
        try {
            $payload = self::validateLegacyDeviceQrcode($params);

            $exists = SvDevice::where('device_code', $payload['device_code'])->findOrEmpty();
            if (!$exists->isEmpty()) {
                throw new \Exception('设备已存在');
            }

            $userId = (int)self::$uid;
            $existing = (int)SvDevice::where('user_id', $userId)->count();
            $reason = '';
            if (!MemberService::canBindDevice($userId, $existing, $reason)) {
                throw new \Exception($reason);
            }

            $device = SvDevice::create([
                'device_code'  => $payload['device_code'],
                'user_id'      => $userId,
                'device_name'  => $payload['device_code'],
                'device_model' => '',
                'sdk_version'  => '',
                'status'       => 0,
                'auto_type'    => 1,
                'is_first'     => 1,
            ]);

            self::saveDeviceUsedRecord($userId, $payload['device_code'], (int)$device->id, 0);

            //校验是否是旧设备
            $res = SvDeviceLogic::applyMiddleDeviceAuthFields($payload);
            if (isset($res['error'])){
                throw new \Exception($res['error']);
            }
            if ($res['auth_status'] == 1){
                $device->auth_status = $res['auth_status'];
                $device->auth_start_time = $res['auth_start_time'];
                $device->auth_expire_time = $res['auth_expire_time'];
                $device->save();
            }

            Db::commit();
            RpaDeviceDispatchService::clearUnbindState($payload['device_code']);
            self::$returnData = $device->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function used(array $params): bool
    {
        Db::startTrans();
        try {
            $isUsed = SvDeviceUsed::where('device_code', $params['device_code'])->where('user_id', self::$uid)->findOrEmpty();
            if ($isUsed->isEmpty()) {
                throw new \Exception('设备已变动，请刷新重试');
            }
            if ($params['is_used'] == 0) {
                $find = SvDevice::field('*')
                                ->where('device_code', $params['device_code'])
                                ->where('user_id', self::$uid)
                                ->findOrEmpty();
                if ($find->isEmpty()) {
                    throw new \Exception('设备已解绑，请刷新重试');
                }
                // 删除关联的账号
                SvAccount::where('device_code', $find->device_code)->where('user_id', self::$uid)->select()->each(function ($account) {
                    // 删除AI设置
                    SvSetting::where('account', $account->account)->select()->delete();
                    // 删除好友
                    SvAccountContact::where('account', $account->account)->select()->delete();

                    $account->delete();
                });
                //删除设备rpa配置
                SvDeviceRpa::where('device_code', $find->device_code)->select()->delete();

                // 删除设备任务
                SvDeviceTask::where('device_code', $find->device_code)->select()->delete();
                // 删除设备接管任务
                // SvDeviceTakeOverTask::where('id', 'in', function ($query) use ($find) {
                //     $query->name('sv_device_take_over_task_account')->field('take_over_id')->where('device_code', $find->device_code);
                // })->select()->delete();
                // // 删除设备接管任务账号
                // SvDeviceTakeOverTaskAccount::where('device_code', $find->device_code)->select()->delete();

                // 删除设备激活任务
                // SvDeviceActive::where('id', 'in', function ($query) use ($find) {
                //     $query->name('sv_device_active_account')->field('active_id')->where('device_code', $find->device_code);
                // })->select()->delete();
                // // 删除设备激活任务账号
                // SvDeviceActiveAccount::where('device_code', $find->device_code)->select()->delete();


                \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                // 删除设备线索词配置
                \app\common\model\auto\AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                \app\common\model\auto\AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                \app\common\model\auto\AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                // 删除设备接管任务配置
                \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                // 删除设备截流获客任务配置
                \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                // 删除设备点赞评论任务配置
                \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
                // 删除设备点赞评论任务账号
                \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();

                \app\common\model\sv\SvDeviceTaskLog::where('device_code', $find->device_code)->select()->delete();

                // \app\common\model\sv\SvPublishSettingAccount::where('user_id', self::$uid)->where('device_code', $find->device_code)->where('auto_type', 1)->select()->delete();
                // \app\common\model\sv\SvPublishSettingDetail::where('user_id', self::$uid)->where('device_code', $find->device_code)->where('auto_type', 1)->select()->delete();
                $find->persona_id = 0;
                $find->save();
            }
            $isUsed->is_used = $params['is_used'];
            $isUsed->save();
            Db::commit();
            if ((int)$params['is_used'] === 0) {
                RpaDeviceDispatchService::afterServerUnbind(
                    (string)$params['device_code'],
                    'api_used_off',
                    ['user_id' => (int)self::$uid]
                );
            } else {
                RpaDeviceDispatchService::clearUnbindState((string)$params['device_code']);
            }
            self::$returnData = $isUsed->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function validateLegacyDeviceQrcode(array $params): array
    {
//        $allowedKeys = ['device_code'];
//        $extraKeys = array_diff(array_keys($params), $allowedKeys);
//        if ($extraKeys !== []) {
//            throw new \Exception('二维码格式不正确，仅支持旧设备二维码');
//        }

        $deviceCode = trim((string)($params['device_code'] ?? ''));

        if (!preg_match('/^[a-f0-9]{18}$/', $deviceCode)) {
            throw new \Exception('二维码格式不正确，仅支持旧设备二维码');
        }

        return [
            'device_code'         => $deviceCode,
        ];
    }

    public static function redeemCdk(array $params): bool
    {
        $params['user_id'] = self::$uid;
        $result = \app\api\logic\DeviceAuthLogic::redeem($params);
        if ($result) {
            self::$returnData = \app\api\logic\DeviceAuthLogic::getReturnData();
            return true;
        }

        self::setError(\app\api\logic\DeviceAuthLogic::getError());
        return false;
    }

    public static function remove(array $params)
    {
        Db::startTrans();
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                Db::rollback();
                self::setError('设备不存在');
                return false;
            }
            \think\facade\Log::channel('device')->write("删除设备：". json_encode($find->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'remove');
            // 删除关联的账号
            SvAccount::where('device_code', $find->device_code)->where('user_id', self::$uid)->select()->each(function ($account) {
                // 删除AI设置
                SvSetting::where('account', $account->account)->select()->delete();
                // 删除好友
                SvAccountContact::where('account', $account->account)->select()->delete();

                $account->delete();
            });
            //删除设备rpa配置
            SvDeviceRpa::where('device_code', $find->device_code)->select()->delete();

            // 删除设备任务
            SvDeviceTask::where('device_code', $find->device_code)->select()->delete();
            // 删除设备接管任务
            // SvDeviceTakeOverTask::where('id', 'in', function ($query) use ($find) {
            //     $query->name('sv_device_take_over_task_account')->field('take_over_id')->where('device_code', $find->device_code);
            // })->select()->delete();
            // // 删除设备接管任务账号
            // SvDeviceTakeOverTaskAccount::where('device_code', $find->device_code)->select()->delete();

            // 删除设备激活任务
            // SvDeviceActive::where('id', 'in', function ($query) use ($find) {
            //     $query->name('sv_device_active_account')->field('active_id')->where('device_code', $find->device_code);
            // })->select()->delete();
            // // 删除设备激活任务账号
            // SvDeviceActiveAccount::where('device_code', $find->device_code)->select()->delete();


            \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备线索词配置
            \app\common\model\auto\AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备接管任务配置
            \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备截流获客任务配置
            \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备点赞评论任务配置
            \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备点赞评论任务账号
            \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();

            \app\common\model\sv\SvDeviceTaskLog::where('device_code', $find->device_code)->select()->delete();

            // \app\common\model\sv\SvPublishSettingAccount::where('user_id', self::$uid)->where('device_code', $find->device_code)->where('auto_type', 1)->select()->delete();
            // \app\common\model\sv\SvPublishSettingDetail::where('user_id', self::$uid)->where('device_code', $find->device_code)->where('auto_type', 1)->select()->delete();

            SvDeviceUsed::deleteByDevice((int)self::$uid, $find->device_code, (int)$find->id);

            $find->delete();
            Db::commit();
            RpaDeviceDispatchService::afterServerUnbind(
                (string)$params['device_code'],
                'api_remove',
                ['user_id' => (int)self::$uid]
            );
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function removePersona(array $params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $beforeData = $find->toArray();
            $materialIds = MaterialModel::where('user_id', $find->user_id)->where('persona_id', $find->persona_id)->column('id');
            if ($materialIds) {
                foreach ($materialIds as $materialId) {
                    $rediskey = 'material_' . $materialId . '_device_' . $find->device_code;
                    Cache::store('material_redis')->delete($rediskey);
                }
            }
            // 移除绑定人设
            $find->persona_id = 0;
            $find->save();
            self::deleteOldPersonaTask($find, '人设解绑设备，任务取消');
            // 删除设备绑定的素材

            // 删除设备待执行的24小时任务
            //SvDeviceTask::where('device_code', $find->device_code)->where('auto_type', 1)->where('day', date('Y-m-d'))->select()->delete();

            $find->refresh();
            $afterData = $find->toArray();
            if ($beforeData !== $afterData) {
                Log::channel('device')->write(json_encode([
                    'msg' => '设备解绑人设数据变动',
                    'user_id' => self::$uid,
                    'device_code' => $params['device_code'] ?? '',
                    'request' => $params,
                    'before' => $beforeData,
                    'after' => $afterData,
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'update');
            }

            self::$returnData = $afterData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function bind(array $params): bool
    {
        Db::startTrans();
        try {
            Log::channel('device')->write(date('Y-m-d H:i:s').' 设备绑定开始：'.time());

            $deviceCode = trim((string)($params['device_code'] ?? ''));
            $userId = (int)($params['user_id'] ?? 0);
            if ($deviceCode === '' || $userId <= 0) {
                throw new \Exception('绑定参数错误');
            }

            $used = SvDeviceUsed::where([
                'device_code' => $deviceCode,
                'user_id'     => $userId,
            ])->lock(true)->findOrEmpty();
            if (!$used->isEmpty() && (int)$used->is_used === 1) {
                throw new \Exception('设备已被绑定');
            }

            $device = SvDevice::where('device_code', $deviceCode)->lock(true)->findOrEmpty();
            if (!$device->isEmpty() && (int)$device->user_id !== $userId) {
                throw new \Exception('设备已被其他用户绑定');
            }

            if (!$device->isEmpty()) {
                $device->save([
                    'device_model' => $params['device_model'] ?? $device->device_model,
                    'sdk_version'  => $params['sdk_version'] ?? $device->sdk_version,
                    'update_time'  => time(),
                ]);
            } else {
                $existing = (int)SvDevice::where('user_id', $userId)->count();
                $reason = '';
                if (!MemberService::canBindDevice($userId, $existing, $reason)) {
                    throw new \Exception($reason);
                }

                $insert = [
                    'device_code'  => $deviceCode,
                    'user_id'      => $userId,
                    'device_name'  => $deviceCode,
                    'device_model' => $params['device_model'] ?? '',
                    'sdk_version'  => $params['sdk_version'] ?? '',
                    'status'       => 0,
                    'auto_type'    => 1,
                    'is_first'     => 1,
                    'create_time'  => time(),
                ];
                $device = SvDevice::create($insert);

                //校验是否是旧设备
                $res = SvDeviceLogic::applyMiddleDeviceAuthFields($params);
                if (isset($res['error'])){
                    throw new \Exception($res['error']);
                }
                if ($res['auth_status'] == 1){
                    $device->auth_status = $res['auth_status'];
                    $device->auth_start_time = $res['auth_start_time'];
                    $device->auth_expire_time = $res['auth_expire_time'];
                    $device->save();
                }
            }

            self::saveDeviceUsedRecord($userId, $deviceCode, (int)$device->id, 1);

            $device_bind_num = SvDevice::where('user_id', $userId)->count();
            User::update(
                [
                    'device_bind_num'  => $device_bind_num,
                    'device_bind_time' => time(),
                    'last_bind_device_code' => $deviceCode,
                ],
                ['id' => $userId]
            );
            Db::commit();
            RpaDeviceDispatchService::clearUnbindState($deviceCode);
            self::$returnData = ['message' => '绑定成功'];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function saveDeviceUsedRecord(int $userId, string $deviceCode, int $deviceId, int $isUsed): void
    {
        SvDeviceUsed::saveRecord($userId, $deviceCode, $deviceId, $isUsed);
    }

    public static function videoSynthesis(string $deviceCode)
    {
        try {
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
            if ($device->isEmpty()) {
                $msg = '-不存在';
                throw new \Exception($msg);
            }
            // 添加缓存锁，10分钟内不能重复执行
            $cacheKey = 'video_synthesis_' . $deviceCode;
            if (Cache::store('material_redis')->get($cacheKey)) {
                throw new \Exception('视频合成任务正在执行中，请10分钟后再试');
            }
            Cache::store('material_redis')->set($cacheKey, 1, 800); // 800秒 = 13分钟

            if ($device->persona_id <= 0) {
                $msg = '-未绑定人设';
                throw new \Exception($msg);
            }
            if ($device->synthesis_m == 1) {
                $msg = '-视频合成任务已执行';
                throw new \Exception($msg);
            }
            $persona = AiPersona::where('id', $device->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                $msg = '-绑定的人设' . AiPersona::formatLabel(null, (int)$device->persona_id) . '不存在或发布模式不符合要求';
                throw new \Exception($msg);
            }
            $config = AiPersonaSynthesisConfig::where('persona_id', $device->persona_id)
                ->where('copywriting_source', 2)
                ->findOrEmpty();    
            if ($config->isEmpty()) {
                $copywritingSourceMap = [1 => '仿写', 2 => 'AI生成', 3 => '无需', 4 => '文案库'];
                $copywritingSourceText = $copywritingSourceMap[$config->copywriting_source] ?? '未知';
                $msg = '-绑定的人设ai合成规则，文案来源模式是：' . $copywritingSourceText;
                throw new \Exception($msg);
            }

            $exec_times = self::getExecTime(self::getTimesByType((int)$persona->persona_type), $device, (int)$persona->persona_type);
            if (empty($exec_times)) {
                $msg = '-绑定的人设' . AiPersona::formatLabel($persona) . '下没有可用的执行时间';
                throw new \Exception($msg);
            }
            $allMaterials = MaterialModel::where('persona_id', $device->persona_id)
                ->where('use_status', 1)
                ->where('is_wechat', 0)
                ->where('publish_mode', $persona->publish_mode)
                ->select()->toArray();

            if (empty($allMaterials)) {
                $msg = '-绑定的人设' . AiPersona::formatLabel($persona) . '下没有可用的素材';
                throw new \Exception($msg);
            }
            $groupedData = [
                'videos' => [], // 视频组 (material_type = 1)
                'images' => []  // 图片组 (material_type = 2)
            ];
            foreach ($allMaterials as $item) {
                // $rediskey = 'material_' . $item['id'] . '_device_' . $deviceCode;
                // $device_bind_num = Cache::store('material_redis')->get($rediskey);
                // if (empty($device_bind_num)) {
                //     $device_bind_num = 0;
                // }
                // if ($device_bind_num > 2) {
                //     continue;
                // }

                if ($item['material_type'] == 1) {
                    $groupedData['videos'][] = $item;
                } elseif ($item['material_type'] == 2) {
                    $groupedData['images'][] = $item;
                }
            }
            $allVideoTypes = $config->generation_types ?? [];
            if (empty($allVideoTypes)) {
                $msg = '-绑定的人设ai合成规则，生成视频类型为空';
                throw new \Exception($msg);
            }

            $taskCountNeeded = count($exec_times);
            $lastTask = Db::name('shanjian_video_task')
                ->where('device_code', $deviceCode)
                ->where('auto_type', 1)
                ->where('wechat_type', 0)
                ->where('persona_id', $device->persona_id)
                ->order('id', 'desc')
                ->field('shanjian_type')
                ->find();

            $lastType = $lastTask ? (int)$lastTask['shanjian_type'] : null;
            
            // 2. 确定本次执行的起点索引
            $startIndex = 0;
            if ($lastType !== null) {
                // 查找最后一次类型在当前配置数组中的位置
                $foundKey = array_search($lastType, $allVideoTypes);
                if ($foundKey !== false) {
                    // 如果找到了，从下一个位置开始
                    $startIndex = ($foundKey + 1) % count($allVideoTypes);
                } else {
                    // 如果配置变了（原来的类型被取消勾选了），则从第0个开始
                    $startIndex = 0;
                }
            }

            $selectedTypesForToday = [];
            $totalTypes = count($allVideoTypes);
            for ($i = 0; $i < $taskCountNeeded; $i++) {
                $index = ($startIndex + $i) % $totalTypes;
                $selectedTypesForToday[] = $allVideoTypes[$index];
            }
            return self::createVideoSynthesisTasks($device, $persona, $groupedData, $selectedTypesForToday);
        
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if ($errorMsg !== '-不存在') {
                $device->markSynthesisDone(SvDevice::SYNTHESIS_SCENE_SOCIAL);
                $device->save();
            }
            $msg = '设备号' . $deviceCode . '视频合成任务失败：' . $errorMsg;
            Log::channel('ipVideoSynthesis')->write($msg);
            self::setError($msg);
            return false;
        }
    }

    private static function createVideoSynthesisTasks(SvDevice $device, AiPersona $persona, array $groupedData, array $videoTypes)
    {
        $videos = $groupedData['videos'];
        $images = $groupedData['images'];
        $deviceCode = $device->device_code;
        $userId = $device->user_id;
        $createdSettings = [];
        $copywritingData = [];
        $persona_type = $persona->persona_type ?? 0;
        $card_name = $persona->persona_name ?? '';
        $card_introduced = $persona->persona_desc ?? '';
        $newsMixcutDuration = self::getNewsMixcutDuration((int)$persona->id, (int)$userId);
        try {
            switch ($persona_type) {
                case 1:
                    $personaInfo = AiPersonaIndividual::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                    if ($personaInfo->isEmpty()) {
                        $msg = '设备号' . $deviceCode . '绑定的人设' . AiPersona::formatLabel($persona) . '下个人IP人设不存在';
                        throw new \Exception($msg);
                    }
                    $nickname = $personaInfo->nickname ?? '';
                    $identity = $personaInfo->identity ?? '';
                    if (is_array($identity)) {
                        $identity = AiPersonaTextService::join($identity);
                    }
                    $personality_tags = $personaInfo->personality_tags ?? '';
                    if (is_array($personality_tags)) {
                        $personality_tags = AiPersonaTextService::join($personality_tags);
                    }

                    $core_value = $personaInfo->core_value ?? '';
                    if (is_array($core_value)) {
                        $core_value = AiPersonaTextService::join($core_value);
                    }
                    $target_audience = $personaInfo->target_audience ?? '';
                    if (is_array($target_audience)) {
                        $target_audience = AiPersonaTextService::join($target_audience);
                    }
                    $monetize_paths = $personaInfo->monetize_paths ?? '';
                    if (is_array($monetize_paths)) {
                        $monetize_paths = AiPersonaTextService::join($monetize_paths);
                    }
                    $highlight_story = $personaInfo->highlight_story ?? '';
                    if (is_array($highlight_story)) {
                        $highlight_story = AiPersonaTextService::join($highlight_story);
                    }
                    $coze['keywords'] = $personaInfo->getClueContent($persona);
                    $qianzui = '';                    
                    if ($card_name) {
                        $qianzui .= "我的人设是{$card_name}。" ;
                    }
                    if ($card_introduced) {
                        $qianzui .= "我的人设介绍是{$card_introduced}。";
                    }
                    $coze['keywords'] = $qianzui . $coze['keywords'];
                    break;

                case 2:
                    $personaInfo = AiPersonaEnterprise::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                    if ($personaInfo->isEmpty()) {
                        $msg = '设备号' . $deviceCode . '绑定的人设' . AiPersona::formatLabel($persona) . '下企业服务人设不存在';
                        throw new \Exception($msg);
                    }
                    $brand_name = $personaInfo->brand_name ?? '';
                    $spokesperson = $personaInfo->spokesperson ?? '';
                    if (is_array($spokesperson)) {
                        $spokesperson = AiPersonaTextService::join($spokesperson);
                    }
                    $brand_tone = $personaInfo->brand_tone ?? '';
                    if (is_array($brand_tone)) {
                        $brand_tone = AiPersonaTextService::join($brand_tone);
                    }
                    $main_product = $personaInfo->main_product ?? '';
                    if (is_array($main_product)) {
                        $main_product = AiPersonaTextService::join($main_product);
                    }
                    $target_customer = $personaInfo->target_customer ?? '';
                    if (is_array($target_customer)) {
                        $target_customer = AiPersonaTextService::join($target_customer);
                    }
                    $account_goal = $personaInfo->account_goal ?? '';
                    if (is_array($account_goal)) {
                        $account_goal = AiPersonaTextService::join($account_goal);
                    }
                    $industry_case = $personaInfo->industry_case ?? '';
                    if (is_array($industry_case)) {
                        $industry_case = AiPersonaTextService::join($industry_case);
                    }
                    $coze['keywords'] = $personaInfo->getClueContent($persona);
                    $qianzui = '';                    
                    if ($card_name) {
                        $qianzui .= "我的人设是{$card_name}。" ;
                    }
                    if ($card_introduced) {
                        $qianzui .= "我的人设介绍是{$card_introduced}。";
                    }
                    $coze['keywords'] = $qianzui . $coze['keywords'];
                    break;
                case 3:
                    $personaInfo = AiPersonaLocal::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                    if ($personaInfo->isEmpty()) {
                        $msg = '设备号' . $deviceCode . '绑定的人设' . AiPersona::formatLabel($persona) . '下本地门店人设不存在';
                        throw new \Exception($msg);
                    }
                    $store_name = $personaInfo->store_name ?? '';
                    $spokesperson = $personaInfo->spokesperson ?? '';
                    if (is_array($spokesperson)) {
                        $spokesperson = AiPersonaTextService::join($spokesperson);
                    }
                    $store_atmosphere = $personaInfo->store_atmosphere ?? '';
                    if (is_array($store_atmosphere)) {
                        $store_atmosphere = AiPersonaTextService::join($store_atmosphere);
                    }
                    $signature_feature = $personaInfo->signature_feature ?? '';
                    if (is_array($signature_feature)) {
                        $signature_feature = AiPersonaTextService::join($signature_feature);
                    }
                    $target_customer = $personaInfo->target_customer ?? '';
                    if (is_array($target_customer)) {
                        $target_customer = AiPersonaTextService::join($target_customer);
                    }
                    $content_preference = $personaInfo->content_preference ?? '';
                    if (is_array($content_preference)) {
                        $content_preference = AiPersonaTextService::join($content_preference);
                    }
                    $open_story = $personaInfo->open_story ?? '';
                    if (is_array($open_story)) {
                        $open_story = AiPersonaTextService::join($open_story);
                    }
                    $coze['keywords'] = $personaInfo->getClueContent($persona);
                    $qianzui = '';                    
                    if ($card_name) {
                        $qianzui .= "我的人设是{$card_name}。" ;
                    }
                    if ($card_introduced) {
                        $qianzui .= "我的人设介绍是{$card_introduced}。";
                    }
                    $coze['keywords'] = $qianzui . $coze['keywords'];
                    break;
                default:
                    $msg = '设备号' . $deviceCode . '绑定的人设' . AiPersona::formatLabel($persona) . '类型不存在';
                    throw new \Exception($msg);
            }
        } catch (\Exception $e) {
            $msg = '设备号' . $deviceCode . '视频合成任务失败：' . $e->getMessage();
            Log::channel('ipVideoSynthesis')->write($msg);
            throw new \Exception($msg);
        }


        $currentKey = 0;
        $currentShanjianType = 0;
        Db::startTrans();
        try {
            $auto_type = 1;
            $voice_id = '';
            $createdSettings = [];
            foreach ($videoTypes as $key => $shanjianType) {
                $currentKey = $key;
                $currentShanjianType = $shanjianType;
                $typeName = self::getShanjianTypeName($shanjianType);
                $uniqueId = generate_unique_task_id();
                $shanjianVideoSettingData = [
                    'auto_type' => 1,
                    'device_code' => $deviceCode,
                    'user_id' => $userId,
                    'task_id' => $uniqueId,
                    'status' => 1, // 1待处理
                    'video_count' => 1,
                    'shanjian_type' => $shanjianType, // 设置类型
                    'create_time' => time(),
                    'update_time' => time()
                ];
                $extradata = [
                    'setting_index' => 1,
                    'create_type' => 'batch'
                ];
                try {
                    $anchorId = '';
                    if ($shanjianType == 1) {
                        $avatarInfoList = AiPersonaDigitalAvatar::availableQuery()
                            ->field('ad.third_avatar_id,ad.third_voice_id,ad.cover_url')
                            ->where('ad.user_id', $userId)
                            ->where('ad.persona_id', $persona->id)
                            ->select()
                            ->toArray();
                        if (empty($avatarInfoList)) {
                            $shanjianType = 3;
                            $shanjianVideoSettingData['shanjian_type'] = 3;
                        } else {
                            $avatar_total = count($avatarInfoList) - 1;
                            if ($avatar_total == 1) {
                                $avatarInfo = 0;
                            } else {
                                $avatarInfo = random_int(0, $avatar_total);
                            }
                            $voice_id =  $avatarInfoList[$avatarInfo]['third_voice_id'] ?? '';
                            $anchorId =  $avatarInfoList[$avatarInfo]['third_avatar_id'] ?? '';
                            $pic = $avatarInfoList[$avatarInfo]['cover_url'] ?? '';
                        }
                    } else {
                        $anchorId = '';
                        $voice_id = '';
                        // 新闻体(4)不需要音色；其余非数字人类型才选音色
                        if ((int)$shanjianType !== 4) {
                            $voice_id_list = AiPersonaDigitalVoice::availableQuery()
                                ->where('ad.user_id', $userId)
                                ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
                                ->where('ad.persona_id', $persona->id)
                                ->column('ad.third_voice_id');
                            if (empty($voice_id_list)) {
                                $msg =  '绑定的人设' . AiPersona::formatLabel($persona) . '下人设没有绑定音色';
                                throw new \Exception($msg);
                            }
                            $voice_total = count($voice_id_list) - 1;
                            $avatarInfo = random_int(0, $voice_total);
                            $voice_id =  $voice_id_list[$avatarInfo] ?? '';
                        }
                    }


                    switch ($shanjianType) {
                        case 1:
                            $coze['sn'] = 5;
                            $coze['number'] = 1;
                            $coze['length'] = 100;
                            $scene = 'virtualman';
                            break;
                        case 2:
                            $coze['sn'] = 0;
                            $coze['number'] = 5;
                            $coze['length'] = 80;
                            $scene = 'realMan';
                            break;
                        case 3:
                            if ($key == 0) {
                                $coze['sn'] = 3;
                            } else {
                                $coze['sn'] = 4;
                            }
                            $coze['number'] = 1;
                            $coze['length'] = 80;
                            $scene = 'oralMixCutting';
                            $voice_id_list = AiPersonaDigitalVoice::availableQuery()
                                ->where('ad.user_id', $userId)
                                ->where('ad.persona_id', $persona->id)
                                ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
                                ->column('ad.third_voice_id');
                            if (empty($voice_id_list)) {
                                $msg =  '绑定的人设' . AiPersona::formatLabel($persona) . '下人设没有绑定音色';
                                throw new \Exception($msg);
                            }
                            $voice_total = count($voice_id_list) - 1;
                            $voice = random_int(0, $voice_total);
                            $voice_id =  $voice_id_list[$voice];
                            break;
                        case 4:
                            $coze['sn'] = 2;
                            $coze['number'] = 1;
                            $coze['length'] = 80;
                            $scene = 'newsMixCutting';
                            $voice_id = '';
                            break;
                        default:
                            $msg = '视频类型不存在';
                            throw new \Exception($msg);
                            break;
                    }

                    $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $userId, 6);
                    $copywritingResult2 = [];
                    $copywritingResult4 = [];
                    switch ($shanjianType) {
                        case 1:
                        case 2:
                            $copywritingResult2['0']['content'] = $copywritingResult['content']['0'] ?? '';
                            break;
                        case 3:
                            break;
                        case 4:
                            $copywritingResult4['0']['title'] = $copywritingResult['content']['0'] ?? '';
                            $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult4, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $extradata['videoDuration'] = $newsMixcutDuration;
                            break;

                        default:
                            $msg = '视频类型不存在';
                            throw new \Exception($msg);
                    }
                } catch (\Exception $e) {
                    // 报错，跳过这个循环
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，文案错误: ' . $e->getMessage();
                    Log::channel('ipVideoSynthesis')->write($msg);
                    continue;
                }

                $selectedMaterials = self::selectAndValidateMaterials($videos, $images, $shanjianType, $deviceCode);

                if (empty($selectedMaterials)) {
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，素材为空';
                    Log::channel('ipVideoSynthesis')->write($msg);
                    continue;
                }


                $taskMsg = $copywritingResult['content']['0'] ?? '';
                //根据文案内容获取标题
                $titlecoze['sn'] = 8;
                $titlecoze['number'] = 1;
                $titlecoze['length'] = 10;
                $titlecoze['keywords'] = $taskMsg;
                $titleResult = AutoDeviceSettingLogic::copywriting($titlecoze, $userId, 6);
                $taskTitle = $titleResult['content']['0'] ?? '';

                // $copywritingContent = '';
                // if (!empty($copywritingData) && isset($copywritingData['content'])) {
                //     $copywritingContent = $copywritingData['content'][0] ?? '';
                // }
                $material_use_log = [];
                foreach ($selectedMaterials as &$item) {
                    if ($shanjianType != 1) {
                        $pic = $item['thumbnail_url'];
                    }
                    $rediskey = 'material_' . $item['id'] . '_device_' . $deviceCode;
                    if (Cache::store('redis')->has($rediskey)) {
                        Cache::store('redis')->delete($rediskey);
                    }
                    // if (Cache::store('redis')->get($rediskey) < 3) {
                        Cache::store('material_redis')->inc($rediskey);
                    // }
                    $material_use_log[] = [
                        'material_id' => $item['id'],
                        'user_id' => $userId,
                        'persona_id' => $persona->id,
                        'use_scene' => 1,
                        'use_status' => 0,
                        'create_time' => time(),
                        'update_time' => time(),
                    ];
                    $extradata['device_code'] = $deviceCode;
                    unset($item['id']);
                    unset($item['thumbnail_url']);
                }
                if (in_array($shanjianType, [3, 4])) {
                    $extradata['volume'] = 0.6;
                }
                $shanjianVideoSettingData['name'] = '社媒平台-' . mb_substr($taskTitle, 0, 6) . '-' . date('YmdHis') . '-' . $typeName;
                if ($shanjianType == 1) {
                    $copywritingResult2['0']['title'] = $taskTitle;
                    $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                if ($shanjianType == 4) {
                    $taskTitle = $copywritingResult4['0']['title'] ?? $taskTitle;
                }
                $clip_template_id = ShanjianClipTemplate::where('scene', $scene)->where('auto_type', $auto_type)->column('id');
                $clip_template_total = count($clip_template_id) - 1;
                $clip = random_int(0, $clip_template_total);
                $clip_id =  $clip_template_id[$clip];
                $number = random_int(1, 20);
                $music_url = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
                $shanjianVideoSettingData['material'] = json_encode($selectedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                // $shanjianVideoSettingData['copywriting'] = json_encode($copywritingContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $shanjianVideoSettingData['voice'] = $voice_id;

                // 新闻体(4)不需要音色，不走 MiniMax TTS；仅数字人口播(1)/素材混剪(3)
                $isMinimaxVoice = in_array((int)$shanjianType, [1, 3], true)
                    && $voice_id !== ''
                    && ShanjianVideoSettingLogic::isMinimaxVoiceId((string)$voice_id, (int)$userId);
                $contentForTts = trim((string)$taskMsg);
                if ($isMinimaxVoice && $contentForTts === '') {
                    $fallbackVoices = AiPersonaDigitalVoice::availableQuery()
                        ->where('ad.user_id', $userId)
                        ->where('ad.persona_id', $persona->id)
                        ->where('ad.provider', 'shanjian')
                        ->whereRaw('(hv.model_version IS NULL OR hv.model_version NOT IN (10, 11))')
                        ->column('ad.third_voice_id');
                    $fallbackVoices = array_values(array_filter(array_map('strval', $fallbackVoices)));
                    if (empty($fallbackVoices)) {
                        throw new \Exception('MiniMax音色需要文案，当前任务无文案且无人设可用非MiniMax音色');
                    }
                    $voice_id = $fallbackVoices[random_int(0, count($fallbackVoices) - 1)];
                    $shanjianVideoSettingData['voice'] = $voice_id;
                    $isMinimaxVoice = false;
                }

                $taskId = generate_unique_task_id();
                $pendingTask = [
                    'shanjian_type' => $shanjianType,
                    'device_code' => $deviceCode,
                    'name' => $shanjianVideoSettingData['name'],
                    'pic' =>  $pic,
                    'task_id' => $taskId,
                    'persona_id' => $persona->id,
                    'status' => 0,
                    'audio_type' => 1,
                    'auto_type' => 1,
                    'user_id' => $userId,
                    'anchor_id' => $anchorId,
                    'voice_id' => $voice_id,
                    'card_name' => $card_name,
                    'card_introduced' =>  $card_introduced,
                    'title' =>  $taskTitle,
                    'msg' =>  $taskMsg,
                    'material' => json_encode($selectedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'extra' => json_encode($extradata, JSON_UNESCAPED_UNICODE),
                ];

                if ($isMinimaxVoice) {
                    // 对齐手动 addType3：同步创建 status=-1 占位视频任务，TTS/ASR 完成后回填
                    $pendingTask['status'] = -1;
                    $requestJson = [
                        'user_id' => $userId,
                        'auto_pending_task' => $pendingTask,
                        'copywriting' => [['title' => $taskTitle, 'content' => $contentForTts]],
                        'voice' => $voice_id,
                    ];
                    $shanjianVideoSettingData['request_json'] = json_encode($requestJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $setting = new ShanjianVideoSetting();
                    $setting->save($shanjianVideoSettingData);
                    $settingId = $setting->id;
                    $minimaxTask = ShanjianVideoSettingLogic::createAudioTask(
                        $settingId,
                        $voice_id,
                        [['content' => $contentForTts]],
                        (int)$userId
                    );
                    $minimaxTaskId = (int)($minimaxTask->id ?? 0);
                    $pendingTask['video_setting_id'] = $settingId;
                    $pendingTask['minimax_task_id'] = $minimaxTaskId;
                    $pendingTask['audio_url'] = '';
                    $pendingTask['create_time'] = time();
                    $pendingTask['update_time'] = time();
                    $shanjiantask = new ShanjianVideoTask();
                    $shanjiantask->save($pendingTask);
                    $task_id = $shanjiantask->id;
                    foreach ($material_use_log as &$log) {
                        $log['task_id'] = $task_id;
                    }
                    unset($log);
                    MaterialUseLog::insertAll($material_use_log);
                    $createdSettings[] = [
                        'setting_id' => $settingId,
                        'task_id' => $task_id,
                        'type' => $typeName,
                        'shanjian_type' => $shanjianType,
                        'minimax_pending' => 1,
                    ];
                    Log::channel('ipVideoSynthesis')->write('社媒旧链路命中MiniMax音色，已建占位任务等待TTS' . json_encode([
                        'setting_id' => $settingId,
                        'task_id' => $task_id,
                        'minimax_task_id' => $minimaxTaskId,
                        'voice_id' => $voice_id,
                        'device_code' => $deviceCode,
                        'shanjian_type' => $shanjianType,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    continue;
                }

                $setting = new ShanjianVideoSetting();
                $setting->save($shanjianVideoSettingData);
                $settingId = $setting->id;
                $pendingTask['video_setting_id'] = $settingId;
                $pendingTask['create_time'] = time();
                $pendingTask['update_time'] = time();
                $shanjiantask = new ShanjianVideoTask();
                $shanjiantask->save($pendingTask);
                $task_id = $shanjiantask->id;
                foreach ($material_use_log as &$log) {
                    $log['task_id'] = $task_id;
                }
                $createdSettings[] = [
                    'setting_id' => $settingId,
                    'type' => $typeName,
                    'shanjian_type' => $shanjianType
                ];
                MaterialUseLog::insertAll($material_use_log);
            }
            $device->markSynthesisDone(SvDevice::SYNTHESIS_SCENE_SOCIAL);
            $device->save();
            Db::commit();
            return [
                'device' => $device->toArray(),
                'persona' => $persona->toArray(),
                'settings' => $createdSettings,
                'count' => count($createdSettings)
            ];
        } catch (\Exception $e) {
            Db::rollback();
            $msg = '设备号: ' . $deviceCode . '第' . ($currentKey + 1) . '类型视频' . $currentShanjianType . '，混剪任务创建失败: ' . $e->getMessage();
            Log::channel('ipVideoSynthesis')->write($msg);
        }
    }

    private static function selectAndValidateMaterials(array $videos, array $images, int $shanjianType, string $deviceCode): array
    {
        try {
            $videoCount = 0;
            $imageCount = 0;

            switch ($shanjianType) {
                case 1:
                    $videoCount = rand(2, 3);
                    $imageCount = rand(2, 3);
                    break;
                case 2:
                    $videoCount = rand(1, 2);
                    $imageCount = rand(2, 3);
                    break;
                case 3:
                    $videoCount = 8;
                    $imageCount = rand(3, 4);
                    break;
                case 4:
                    $videoCount = 5;
                    $imageCount = rand(2, 3);
                    break;
                default:
                    $videoCount = 1;
                    $imageCount = 2;
            }

            // 记录原始需求的视频和图片数量
            $originalVideoCount = $videoCount;
            $originalImageCount = $imageCount;
            // $msg = '设备号: ' . $deviceCode . '，原始需求视频数量: ' . $originalVideoCount . '，原始需求图片数量: ' . $originalImageCount;
            // Log::channel('ipVideoSynthesis')->write($msg);  
            // foreach ($videos as $key => $video) {
            //     $rediskey = 'material_' . $video['id'] . '_device_' . $deviceCode;
            //     $device_bind_num = Cache::store('material_redis')->get($rediskey);
            //     // Log::channel('ipVideoSynthesis')->write('设备号: ' . $deviceCode . '，视频' . $video['id'] . '绑定次数: ' . $device_bind_num);
            //     if ($device_bind_num > 2) {
            //         unset($videos[$key]);
            //         continue;
            //     }
            // }
            // foreach ($images as $key => $image) {
            //     $rediskey = 'material_' . $image['id'] . '_device_' . $deviceCode;
            //     $device_bind_num = Cache::store('material_redis')->get($rediskey);
            //     // Log::channel('ipVideoSynthesis')->write('设备号: ' . $deviceCode . '，图片' . $image['id'] . '绑定次数: ' . $device_bind_num);
            //     if ($device_bind_num > 2) {
            //         unset($images[$key]);
            //         continue;
            //     }
            // }

            $hasVideos = !empty($videos);
            $hasImages = !empty($images);

            if (!$hasVideos && !$hasImages) {
                $msg = '设备号: ' . $deviceCode . '没有可用素材';
                Log::channel('ipVideoSynthesis')->write($msg);
                return [];
            }

            if (!$hasVideos && $hasImages) {
                $imageCount = $videoCount + $imageCount;
                $msg = '设备号: ' . $deviceCode . '，视频数量为0，补充图片数量: ' . $videoCount;
                $videoCount = 0;
                Log::channel('ipVideoSynthesis')->write($msg);
            } elseif (!$hasImages && $hasVideos) {
                $videoCount = $videoCount + $imageCount;
                $msg = '设备号: ' . $deviceCode . '，图片数量为0，补充视频数量: ' . $imageCount;
                $imageCount = 0;
                Log::channel('ipVideoSynthesis')->write($msg);
            }
            // 两种素材都有，但数量不足时，用另一种素材补充
            elseif ($hasVideos && $hasImages) {
                // 视频不足：用图片补充缺失的视频数量
                if (count($videos) < $originalVideoCount) {
                    $videoCount = count($videos);
                    $cjvideoCount = $originalVideoCount - $videoCount;
                    $imageCount = $cjvideoCount + $imageCount;
                    $msg = '设备号: ' . $deviceCode . '，原始需求视频数量: ' . $originalVideoCount . '，视频数量: ' . count($videos) . '，补充图片数量: ' . $cjvideoCount;
                    Log::channel('ipVideoSynthesis')->write($msg);
                }
                // 图片不足：用视频补充缺失的图片数量
                if (count($images) < $originalImageCount) {
                    $imageCount = count($images);
                    $cjimageCount = $originalImageCount - $imageCount;
                    $videoCount = $cjimageCount + $videoCount;
                    $msg = '设备号: ' . $deviceCode . '，原始需求图片数量: ' . $originalImageCount . '，图片数量: ' . count($images) . '，补充视频数量: ' . $cjimageCount;
                    Log::channel('ipVideoSynthesis')->write($msg);
                }
            }

            // 打乱素材顺序实现随机抽取
            shuffle($videos);
            shuffle($images);
            $selectedVideos = [];
            if ($videoCount > 0) {
                $selectedVideos = array_slice($videos, 0, $videoCount);
                // $msg = '设备号: ' . $deviceCode . '，原始需求视频数量: ' . $originalVideoCount . '，视频数量: ' . $videoCount;
                // Log::channel('ipVideoSynthesis')->write($msg);  
            }
            $selectedImages = [];
            if ($imageCount > 0) {
                $selectedImages = array_slice($images, 0, $imageCount);
                // $msg = '设备号: ' . $deviceCode . '，原始需求图片数量: ' . $originalImageCount . '，图片数量: ' . $imageCount;
                // Log::channel('ipVideoSynthesis')->write($msg);  
            }
            $mergedMaterials = [];
            if ($videoCount > 0 || $imageCount > 0) {
                $mergedMaterials = array_merge($selectedVideos, $selectedImages);
            }

            if (empty($mergedMaterials)) {
                return [];
            }
            foreach ($mergedMaterials as $key => &$value) {
                if ($value['material_type'] == 1) {
                    $value['type'] = 'video';
                } else {
                    $value['type'] = 'image';
                    $value['duration'] = 2;
                }
                $value['fileUrl'] = $value['file_url'];
                unset($value['user_id']);
                unset($value['persona_id']);
                unset($value['material_name']);
                unset($value['material_type']);
                unset($value['height']);
                unset($value['width']);
                unset($value['publish_mode']);
                unset($value['create_time']);
                unset($value['update_time']);
                unset($value['file_url']);
                unset($value['use_status']);
            }
            unset($value);

            return \app\api\logic\shanjian\ShanjianVideoSettingLogic::trimMaterialsByDuration(array_values($mergedMaterials));
        } catch (\Exception $e) {
            $msg = '设备号: ' . $deviceCode . '，素材选择异常: ' . $e->getMessage();
            Log::channel('ipVideoSynthesis')->write($msg);
            return [];
        }
    }

    private static function getShanjianTypeName(int $type): string
    {
        $typeNames = [
            1 => '数字人口播',
            2 => '真人口播',
            3 => '素材混剪',
            4 => '新闻体',
        ];
        return $typeNames[$type] ?? '未知类型';
    }

    private static function getNewsMixcutDuration(int $personaId, int $userId): int
    {
        $duration = AiPersonaSynthesisConfig::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->value('news_mixcut_duration');

        return AiPersonaSynthesisConfig::normalizeNewsMixcutDuration($duration);
    }
}
