<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\sv\SvDevice;
use think\facade\Cache;

class MaterialLogic extends ApiLogic
{
    public static function add(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            $params['user_id'] = self::$uid;
            $params['use_status'] = $params['use_status'] ?? 1;
            $params['publish_mode'] = $params['publish_mode'] ?? 1;
            $params['create_time'] = time();
            $params['update_time'] = time();
            $result = Material::create($params);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function addBatch(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }

            $items = $params['items'] ?? [];
            if (empty($items)) {
                self::setError('批量数据不能为空');
                return false;
            }

            $insertData = [];
            foreach ($items as $key => $item) {
                $file_url = $item['file_url'] ?? '';
                if (empty($file_url)) {
                    continue;
                }
                $insertData[] = [
                    'persona_id' => $params['persona_id'],
                    'user_id' => self::$uid,
                    'material_name' => $item['material_name'] ?? '',
                    'material_type' => $item['material_type'] ?? 1,
                    'file_url' => $item['file_url'] ?? '',
                    'thumbnail_url' => $item['thumbnail_url'] ?? '',
                    'duration' => $item['duration'] ?? 0,
                    'width' => $item['width'] ?? 0,
                    'height' => $item['height'] ?? 0,
                    'use_status' => $item['use_status'] ?? 1,
                    'publish_mode' => $item['publish_mode'] ?? 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ];
            }

            $result = (new Material())->saveAll($insertData);
            self::$returnData = [
                'count' => count($result),
                'items' => $result
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function update(array $params): bool
    {
        try {
            $material = Material::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }

            unset($params['id']);
            $params['update_time'] = time();
            $material->save($params);
            self::$returnData = $material->refresh()->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            $material = Material::where('id', $id)
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }

            $material->use_status = 0;
            $material->delete_time = time();
            $material->save();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        try {
            $material = Material::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            $devices = SvDevice::where('user_id', self::$uid)->where('persona_id',$material->persona_id)->column('device_code,device_name,device_model');
            $devicelist = [];
            foreach ($devices as $key => $value) {
                $devices[$key]['device_code'] = $value['device_code'];
                $rediskey = 'material_' . $material->id . '_device_' . $value['device_code'];
                $device_use_num = Cache::store('redis')->get($rediskey) ?? 0;
                $devicelist[] = [
                    'device_code' => $value['device_code'],
                    'device_name' => $value['device_name'],
                    'device_model' => $value['device_model'],
                    'use_num' => $device_use_num,
                ];
            }

            self::$returnData = [
                'material' => $material->toArray(),
                'devicelist' => $devicelist,
                'devicenum' => count($devicelist),

            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function updateStatus(array $params): bool
    {
        try {
            $material = Material::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            $device_code = $params['device_code'] ?? '';
            $device = SvDevice::where('user_id', self::$uid)
                ->where('device_code', $device_code)
                ->findOrEmpty();
            if ($device->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $rediskey = 'material_' . $material->id . '_device_' . $device_code;
            $status = $params['status'] ?? 0;
            Cache::store('redis')->set($rediskey, $status);
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}
