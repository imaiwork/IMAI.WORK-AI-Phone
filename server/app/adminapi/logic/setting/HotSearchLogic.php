<?php


namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\model\HotSearch;
use app\common\service\ConfigService;
use app\common\service\FileService;


/**
 * 热门搜素逻辑
 * Class HotSearchLogic
 * @package app\adminapi\logic\setting
 */
class HotSearchLogic extends BaseLogic
{

    /**
     * @notes 获取配置
     * @return array
     * @author 段誉
     * @date 2022/9/5 18:48
     */
    public static function getConfig()
    {
        return [
            // 功能状态 0-关闭 1-开启
            'status' => ConfigService::get('hot_search', 'status', 0),
            // 热门搜索数据
            'data' => HotSearch::field(['name', 'sort'])->order(['sort' => 'desc', 'id' => 'desc'])->select()->toArray(),
        ];
    }


    /**
     * @notes 设置热门搜搜
     * @param $params
     * @return bool
     * @author 段誉
     * @date 2022/9/5 18:58
     */
    public static function setConfig($params)
    {
        try {
            if (!empty($params['data'])) {
                $model = (new HotSearch());
                $model->where('id', '>', 0)->delete();
                $model->saveAll($params['data']);
            }

            $status = empty($params['status']) ? 0 : $params['status'];
            ConfigService::set('hot_search', 'status', $status);

            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }
}
