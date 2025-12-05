<?php


namespace app\api\logic\knowledge;


use app\common\model\knowledge\KnowledgeBind;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * KnowledgeBind逻辑
 * Class KnowledgeBindLogic
 * @package app\api\logic\knowledge
 */
class KnowledgeBindLogic extends BaseLogic
{


    /**
     * @notes 添加
     * @param array $params
     * @return bool
     * @author my
     * @date 2025/04/18 16:19
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            KnowledgeBind::create([
                'user_id' => $params['user_id'],
                'kid' => $params['kid'],
                'data_id' => $params['data_id'],
                'type' => $params['type'],
                'index_id' => $params['index_id'],
                'rerank_min_score' => $params['rerank_min_score']
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 编辑
     * @param array $params
     * @return bool
     * @author my
     * @date 2025/04/18 16:19
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            KnowledgeBind::where('id', $params['id'])->update([
                'user_id' => $params['user_id'],
                'kid' => $params['kid'],
                'data_id' => $params['data_id'],
                'type' => $params['type'],
                'index_id' => $params['index_id'],
                'rerank_min_score' => $params['rerank_min_score']
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除
     * @param array $params
     * @return bool
     * @author my
     * @date 2025/04/18 16:19
     */
    public static function delete(array $params): bool
    {
        return KnowledgeBind::destroy($params['id']);
    }


    /**
     * @notes 获取详情
     * @param $params
     * @return array
     * @author my
     * @date 2025/04/18 16:19
     */
    public static function detail($params): array
    {
        return KnowledgeBind::findOrEmpty($params['id'])->toArray();
    }
}