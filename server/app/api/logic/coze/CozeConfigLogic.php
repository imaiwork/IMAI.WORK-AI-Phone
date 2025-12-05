<?php

namespace app\api\logic\coze;

use app\api\logic\ApiLogic;
use app\common\model\coze\CozeConfig;

class CozeConfigLogic extends ApiLogic
{
    public static function add(array $params)
    {
        try {
            $data = [
                'source' => CozeConfig::SOURCE_USER,
                'source_id' => self::$uid,
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
                ->where('source_id', self::$uid)
                ->where('source', CozeConfig::SOURCE_USER)
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
                CozeConfig::destroy(['id' => $id, 'source_id' => self::$uid, 'source' => CozeConfig::SOURCE_USER]);
            } else {
                CozeConfig::whereIn('id', $id)->where('source_id', self::$uid)
                    ->where('source', CozeConfig::SOURCE_USER)
                    ->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function get()
    {
        $config = CozeConfig::where('source_id', self::$uid)
            ->where('source', CozeConfig::SOURCE_USER)
            ->findOrEmpty()->toArray();

        if (!$config) {
            self::setError('配置不存在');
            return false;
        }
        $config['source_text'] = CozeConfig::getSourceText((int)($config['source'] ?? 0));
        self::$returnData = $config;
        return true;
    }
}


