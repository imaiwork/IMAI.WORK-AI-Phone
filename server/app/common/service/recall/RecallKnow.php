<?php

namespace app\common\service\recall;

use app\common\enum\kb\KnowEnum;
use app\common\model\chat\Models;
use app\common\model\kb\KbKnowFiles;
use app\common\pgsql\KbEmbedding;
use app\common\service\ai\VectorService;
use app\common\service\JiebaService;
use Exception;

class RecallKnow
{
    /**
     * @notes 语义检索
     * @param string $model
     * @param string $query
     * @param array $knowIds
     * @param int $limit
     * @param int $userId
     * @return array
     * @throws Exception
     * @author fzr
     */
    public static function embeddingRecall(string $model, string $query, array $knowIds, int $userId, int $limit = 100): array
    {
        try {
            if (!$knowIds) {
                return [];
            }

            $embModel = Models::checkModels($model);
            $channel = $embModel['channel'];
            $modelId = $embModel['model_id'];
            $modelName = $embModel['model'];
            $vectorService = new VectorService($modelId);
            $embeddingArr = $vectorService->toEmbedding('doubao', $modelName, $query, $userId);
            $embeddingStr = '[' . implode(',', $embeddingArr) . ']';
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $modelKbEmbedding = new KbEmbedding();
        $sql = $modelKbEmbedding
            ->field("uuid, 'emb' AS type,fd_id,question,answer,annex,(embedding <=> :embedding) AS emb_score")
            ->whereIn('kb_id', $knowIds)
            ->where(['status' => KnowEnum::RUN_OK])
            ->where(['dimension' => count($embeddingArr)])
            ->where(['is_delete' => 0])
            ->bind(['embedding' => $embeddingStr])
            ->order('emb_score asc')
            ->limit($limit)
            ->buildSql();

        $sql = str_replace("( SELECT", "SET LOCAL hnsw.ef_search = 100;\n(SELECT", $sql);
        $lists = app('db')->connect('pgsql')->query($sql);

        $files = [];
        $fdIds = array_unique(array_column($lists, 'fd_id'))?:[0];
        if ($fdIds) {
            $files = (new KbKnowFiles())->whereIn('id', $fdIds)->column('name', 'id');

        }

        foreach ($lists as &$item) {
            $item['question'] = get_file_domain($item['question']);
            $item['answer'] = get_file_domain($item['answer']);
            $item['character'] = mb_strlen($item['question']) + mb_strlen(trim($item['answer']));
            $item['annex'] = json_decode($item['annex'], true);
            $item['emb_score'] = number_format(1 - $item['emb_score'], 5);
            $item['file'] = [
                'id'   => $item['fd_id'],
                'name' => $files[$item['fd_id']]??''
            ];
        }

        return $lists;
    }

    /**
     * @notes 全文检索
     * @param string $query
     * @param array $knowIds
     * @param int $limit
     * @return mixed
     * @author fzr
     */
    public static function fullTextRecall(string $query, array $knowIds, int $limit = 100): mixed
    {
        if (!$knowIds) {
            return [];
        }

        $keyword = JiebaService::jiebaSplit($query);
        $queries = JiebaService::preprocessQuery($keyword);
        $knowIds = implode(',', $knowIds);
        $table = (new KbEmbedding())->getTable();

        $sql = "SELECT uuid,'full' AS type,fd_id,question,answer,annex,";
        $sql .= "ts_rank_cd(phrases, to_tsquery('zh_en', '$queries')) AS full_score ";
        $sql .= "FROM $table ";
        $sql .= "WHERE kb_id IN ($knowIds) ";
        $sql .= "AND phrases @@ to_tsquery('zh_en', '$queries') ";
        $sql .= "ORDER BY full_score DESC LIMIT $limit;";
        $lists = app('db')->connect('pgsql')->query($sql);

        $files = [];
        $fdIds = array_unique(array_column($lists, 'fd_id'))?:[0];
        if ($fdIds) {
            $files = (new KbKnowFiles())->whereIn('id', $fdIds)->column('name', 'id');
        }

        foreach ($lists as &$item) {
            $item['question'] = get_file_domain($item['question']);
            $item['answer'] = get_file_domain($item['answer']);
            $item['character'] = mb_strlen($item['question']) + mb_strlen($item['answer']);
            $item['annex'] = json_decode($item['annex'], true);
            $item['emb_score'] = number_format($item['full_score'], 5);
            $item['file'] = [
                'id'   => $item['fd_id'],
                'name' => $files[$item['fd_id']]??''
            ];
        }

        return $lists;
    }

    /**
     * @notes 混合检索
     * @param string $model
     * @param string $query
     * @param array $knowIds
     * @param $userId
     * @return array
     * @throws Exception
     * @author fzr
     */
    public static function mixedRecall(string $model, string $query, array $knowIds, $userId): array
    {
        // 语义检索
        $embeddingResults = self::embeddingRecall($model, $query, $knowIds, $userId);
        // 混合检索
        $fullTextResults = self::fullTextRecall($query, $knowIds);
        // RRF融合
        return RecallUtils::rrfConcatResults([
            ["k" => 60, "list" => $embeddingResults],
            ["k" => 60, "list" => $fullTextResults],
        ]);
    }

    public static function destroy(): void
    {
        JiebaService::destroy();
    }
}