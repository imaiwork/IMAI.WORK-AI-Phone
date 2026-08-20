<?php

namespace app\api\logic\sv;

use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceUsed;
use app\common\model\sv\SvSetting;
use app\common\service\ToolsService;
use Channel\Client as ChannelClient;
use think\facade\Db;

/**
 * DeviceLogic
 * @desc 设备
 * @author Qasim
 */
class DeviceLogic extends SvBaseLogic
{

    protected static array  $appMaps = [
        1 => '视频号',
        3 => '小红书',
        4 => '抖音',
        5 => '快手'
    ];
    /**
     * @desc 添加设备
     * @param array $params
     * @return bool
     */
    public static function addDevice(array $params)
    {

        $transStarted = false;
        try {
            // 获取设备信息
            $device = self::deviceInfo($params['device_code'], false);
            if ($device instanceof SvDevice) {
                self::setError('设备已存在');
                return false;
            }

            $params['user_id'] = self::$uid;

            \think\facade\Cache::store('redis')->select(env('redis.WS_SELECT', 8));
            $wechatCode = \think\facade\Cache::store('redis')->get("xhs:device:{$params['device_code']}:wechat_code");
            if ($wechatCode) {
                $params['wechat_device_code'] = $wechatCode;
            }

            $params['is_first'] = 1;
            $params = self::applyMiddleDeviceAuthFields($params);
            Db::startTrans();
            $transStarted = true;
            // 添加设备  
            $device = SvDevice::create($params);
            SvDeviceUsed::saveRecord((int)self::$uid, (string)$params['device_code'], (int)$device->id, 1);
            Db::commit();
            $transStarted = false;

            // 返回设备信息 
            $data = $device->toArray();

            //添加设备rpa配置
            //self::addDeviceRpa($data);

            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            if ($transStarted) {
                Db::rollback();
            }
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function applyMiddleDeviceAuthFields(array $params): array
    {
        $params['auth_status'] = DeviceAuthCodeEnum::DEVICE_AUTH_INACTIVE;
        $params['auth_start_time'] = 0;
        $params['auth_expire_time'] = 0;

        $deviceCode = trim((string)($params['device_code'] ?? ''));
        if ($deviceCode === '') {
            return $params;
        }

        try {
            $response = ToolsService::DataCenter()->deviceAuthCodeDeviceAuth([
                'device_code' => $deviceCode,
            ]);
        } catch (\Throwable) {
            return $params;
        }

        if ((int)($response['code'] ?? 0) !== 10000) {
            return $params;
        }

        $data = $response['data'] ?? [];
        if (!is_array($data)) {
            return $params;
        }

        $remoteDomain = self::normalizeDeviceAuthDomain((string)($data['domain'] ?? $data['site'] ?? ''));
        $localDomain = self::normalizeDeviceAuthDomain(self::getCurrentStationDomain());
        if ($remoteDomain === '' || $localDomain === '' || $remoteDomain !== $localDomain) {
            $params['error'] = '设备授权域名不匹配';
            return $params;
        }

        $authStatus = (int)($data['auth_status'] ?? 0);
        $activatedTime = (int)($data['activated_time'] ?? 0);
        $expiredTime = (int)($data['expired_time'] ?? 0);
        if ($authStatus !== DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE || $expiredTime === 0) {
            return $params;
        }

        $params['auth_status'] = DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE;
        if ($activatedTime > 0) {
            $params['auth_start_time'] = $activatedTime;
        }
        $params['auth_expire_time'] = $expiredTime < 0 ? 0 : $expiredTime;
        return $params;
    }

    private static function getCurrentStationDomain(): string
    {
        $host = trim((string)(env('app.host') ?: config('app.app_host')));
        if ($host === '') {
            $host = $_SERVER['HTTP_HOST'] ?? '';
        }
        return $host;
    }

    private static function normalizeDeviceAuthDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '') {
            return '';
        }
        $host = parse_url($domain, PHP_URL_HOST);
        if (!$host) {
            $host = parse_url('http://' . ltrim($domain, '/'), PHP_URL_HOST);
        }
        return strtolower(trim((string)$host));
    }

    private static function addDeviceRpa(array $data)
    {
        SvDeviceRpa::where('device_code', $data['device_code'])->select()->delete();
        $maps = array(
            ['app_icon' => '', 'app_type' => 1, 'app_name' => '视频号', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 1],
            ['app_icon' => '', 'app_type' => 3, 'app_name' => '小红书', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 0],
            ['app_icon' => '', 'app_type' => 4, 'app_name' => '抖音', 'exec_duration' => 200, 'is_enable' => 0, 'weight' => 2],
            ['app_icon' => '', 'app_type' => 5, 'app_name' => '快手', 'exec_duration' => 200, 'is_enable' => 0, 'weight' => 3],
        );
        foreach ($maps as &$item) {
            $item['device_code'] = $data['device_code'];
            $item['user_id'] = self::$uid;
            $item['create_time'] = time();
        }
        $model = new SvDeviceRpa();
        $model->insertAll($maps);
    }


    /**
     * @desc 更新设备
     * @param array $params
     * @return bool
     */
    public static function updateDevice(array $params)
    {
        try {
            // 获取设备信息
            $device = self::deviceInfo($params['device_code']);
            if (is_bool($device)) {
                return false;
            }

            // 更新设备  
            SvDevice::where('id', $device->id)->update($params);
            self::$returnData = $device->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除设备
     * @param array $params
     * @return bool
     */
    public static function removeDevice(array $params)
    {
        try {
            // 获取设备信息
            $device = self::deviceInfo($params['device_code']);
            if (is_bool($device)) {
                return false;
            }

            // 删除关联的账号
            SvAccount::where('device_code', $device->device_code)->where('user_id', self::$uid)->select()->each(function ($account) {

                // 删除AI设置
                SvSetting::where('account', $account->account)->select()->delete();
                // 删除好友
                SvAccountContact::where('account', $account->account)->select()->delete();

                $account->delete();
            });
            //删除设备rpa配置
            SvDeviceRpa::where('device_code', $device->device_code)->select()->delete();

            $device->delete();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function check()
    {
        try {
            $res = \app\common\service\ToolsService::Auth()->checkUrl();
            self::$returnData = $res;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新设备rpa配置
     * @param array $params
     * @return bool
     */
    public static function rpaUpdate(array $params)
    {
        try {
            if (isset($params[0]['device_code'])) {
                $device = self::deviceInfo($params[0]['device_code']);
                if (is_bool($device)) {
                    return false;
                }
            }
            $deviceCode = $params[0]['device_code'];

            foreach ($params as $key => $value) {
                $row = SvDeviceRpa::where('device_code', $value['device_code'])->where('id', $value['id'])->findOrEmpty();
                if ($row->isEmpty()) {
                    $value['user_id'] = self::$uid;
                    $value['create_time'] = time();
                    SvDeviceRpa::create($value);
                } else {
                    $row->exec_duration = $value['exec_duration'];
                    $row->is_enable = $value['is_enable'];
                    $row->weight = $value['weight'];
                    $row->app_icon = $value['app_icon'];
                    $row->update_time = time();
                    $row->start_time = null;
                    $row->end_time = null;
                    $row->status = 0;
                    $row->save();
                }
            }
            // 更新设备rpa配置
            $rpaList = SvDeviceRpa::where('device_code', $deviceCode)->select();
            self::$returnData = $rpaList->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function execDeviceRpaCron()
    {
        //只允许在线设备
        $devices = SvDeviceRpa::field('device_code')->where('device_code', 'in', function ($query) {
            $query->name('sv_device')->where('status', 1)->field('device_code');
        })->where('is_enable', 1)->group('device_code')->select()->toArray();

        foreach ($devices as $key => $device) {
            //当前设备是否有正在执行的设备
            $running = SvDeviceRpa::where('device_code', $device['device_code'])->where('status', '=', 1)->findOrEmpty();
            if (!$running->isEmpty()) {
                //有正在执行的设备
                //判断是否过期
                $endTime = strtotime($running->start_time) + ((int)$running->exec_duration * 60);
                if ($endTime > time()) {
                    //未过期
                    continue;
                }
                //发送过期消息
                self::sendNextExecApp($running, true);
            } else {
                //没有正在执行的设备
                $running = SvDeviceRpa::where('device_code', $device['device_code'])
                    ->where('status', '=', 0)
                    ->where('is_enable', 1)
                    ->order('start_time asc, weight asc')
                    ->findOrEmpty();
                self::sendNextExecApp($running);
            }
        }
    }

    private static function sendNextExecApp(SvDeviceRpa $running, bool $isNext = false)
    {
        if ($isNext) {
            $appinfo = SvDeviceRpa::where('device_code', $running->device_code)
                ->where('is_enable', 1)
                ->where('id', '<>', $running->id)
                ->where('status', 0)
                ->order('start_time asc, weight asc')
                ->findOrEmpty();
        } else {
            $appinfo = $running;
        }

        if ($appinfo->isEmpty()) {
            return;
        }

        $payload = [
            "messageId" => 2,
            "type" => 90, //执行那个app指令
            "appType" => $appinfo->app_type,
            "content" => json_encode([
                "deviceId" => $appinfo->device_code,
                "appType" => $appinfo->app_type,
                'msg' => self::$appMaps[$appinfo->app_type],
                'task_id' => $appinfo->id
            ], JSON_UNESCAPED_UNICODE),
            "deviceId" => $appinfo->device_code,
            "appVersion" => \app\common\enum\DeviceEnum::APP_VERSION,
        ];
        print_r($payload);

        $channel = "device.{$appinfo->device_code}.message";
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        ChannelClient::publish($channel, [
            'data' => json_encode($payload)
        ]);
    }
}
