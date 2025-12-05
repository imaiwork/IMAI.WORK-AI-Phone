<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\common\model\shanjian\ShanjianCharacterDesign;

class ShanjianCharacterDesignLogic extends ApiLogic
{
    public static function add(array $params)
    {
        try {
            $data = [
                'user_id' => self::$uid,
                'name' => $params['name'],
                'introduced' => $params['introduced'],
                'create_time' => time(),
            ];
            (new ShanjianCharacterDesign())->save($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update(array $params)
    {
        try {
            $model = ShanjianCharacterDesign::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($model->isEmpty()) {
                self::setError('记录不存在');
                return false;
            }
            $update = [
                'name' => $params['name'] ?? $model['name'],
                'introduced' => $params['introduced'] ?? $model['introduced'],
                'update_time' => time(),
            ];
            $model->save($update);
            self::$returnData = $model->refresh()->toArray();
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
                ShanjianCharacterDesign::destroy(['id' => $id, 'user_id' => self::$uid]);
            }else {
                ShanjianCharacterDesign::whereIn('id', $id)->where('user_id', self::$uid)
                    ->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params)
    {
        $model = ShanjianCharacterDesign::where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($model->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        self::$returnData = $model->toArray();
        return true;
    }
}


