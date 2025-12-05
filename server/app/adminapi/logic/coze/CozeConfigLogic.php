<?php

namespace app\adminapi\logic\coze;

use app\common\logic\BaseLogic;
use app\common\model\coze\CozeConfig;

class CozeConfigLogic extends BaseLogic
{
    public static function add(array $params)
    {
        try {
            $data = [
                'source' => CozeConfig::SOURCE_ADMIN,
                'source_id' => $params['source_id'],
                'secret_token' => $params['secret_token'],
                'create_time' => time(),
            ];

            $model = new CozeConfig();
            $model->save($data);
            self::$returnData = $data + ['id' => $model->id ?? null];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update(array $params)
    {
        try {
            $exists = CozeConfig::where('id', $params['id'])
                ->where('source', CozeConfig::SOURCE_ADMIN)
                ->findOrEmpty()->toArray();
            if (!$exists) {
                self::setError('配置不存在');
                return false;
            }
            $updateData = [
                'id' => $params['id'],
            ];
            if (isset($params['secret_token'])) {
                $updateData['secret_token'] = $params['secret_token'];
            }
            CozeConfig::update($updateData);
            self::$returnData = CozeConfig::where('id', $params['id'])->findOrEmpty()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                CozeConfig::destroy(['id' => $id, 'source' => CozeConfig::SOURCE_ADMIN]);
            } else {
                CozeConfig::whereIn('id', $id)->where('source', CozeConfig::SOURCE_ADMIN)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function get()
    {
        $config = CozeConfig::where('source', CozeConfig::SOURCE_ADMIN)
            ->findOrEmpty()->toArray();
        if (!$config) {
            self::setError('配置不存在');
            return false;
        }
        self::$returnData = $config;
        return true;
    }
}


