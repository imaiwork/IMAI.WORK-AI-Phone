<?php

namespace app\adminapi\logic\knowledge;

use app\common\logic\BaseLogic;
use app\common\model\kb\KbKnow;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeBind;


/**
 * 形象
 */
class KnowledgeLogic extends BaseLogic
{

    const RERANK_MIN_SCORE = 0.2; //知识库检索最小分数
    /**
     * @notes 删除形象
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        try {

            if (is_string($data['id'])) {
                Knowledge::destroy(['id' => $data['id']]);
            } else {
                Knowledge::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 新绑定知识库
     *
     * @param array $params
     * @param [type] $data
     * @param $kbType
     * @return void
     * @throws Exception
     */
    public static function newBind(array $params, $data, $kbType)
    {
        //删除原来的绑定 添加新的绑定信息
        KnowledgeBind::where('data_id', $data['id'])
                     ->where('user_id', 0)
                     ->where('type', $params['type'])
                     ->select()
                     ->delete();

        if (isset($params['index_id']) && !empty($params['index_id']) && $kbType == 1) {
            $knowledge = Knowledge::where('index_id', $params['index_id'])->findOrEmpty();
        } else {
            $knowledge = KbKnow::where('id', $params['id'])->findOrEmpty();
        }

        if ($knowledge->isEmpty()) {
            throw new \Exception('知识库不存在');
            return false;
        }
        //挂载知识库
        KnowledgeBind::create([
                                  'user_id'          => 0,
                                  'kid'              => $knowledge['id'],
                                  'data_id'          => $data['id'],
                                  'type'             => $params['type'],
                                  'kb_type'          => $kbType,
                                  'index_id'         => $params['index_id'] ?? '',
                                  'rerank_min_score' => $params['rerank_min_score'] ?? self::RERANK_MIN_SCORE,
                                  'create_time'      => time(),
                              ]);
    }
}
