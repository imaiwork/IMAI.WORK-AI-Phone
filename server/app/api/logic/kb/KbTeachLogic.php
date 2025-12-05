<?php


namespace app\api\logic\kb;

use app\api\logic\service\TokenLogService;
use app\common\enum\ChatEnum;
use app\common\enum\kb\KnowEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbKnowQa;
use app\common\model\kb\KbKnowTeam;
use app\common\model\kb\KbKnowTestRecord;
use app\common\pgsql\KbEmbedding;
use app\common\service\ai\VectorService;
use app\common\service\FileService;
use app\common\service\recall\RecallKnow;
use app\common\service\recall\RecallUtils;
use app\queue\BaseQueue;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

/**
 * 训练数据逻辑类
 */
class KbTeachLogic extends BaseLogic
{
    /**
     * @notes 检测数据状态
     * @param int $userId
     * @param int $kid
     * @param int $fid
     * @param array $uuids
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     */
    public static function detection(int $userId, int $kid, int $fid, array $uuids): array
    {
        if (empty($uuids)) {
            return [
                'tasks' => [],
                'lists' => []
            ]??[];
        }

        $modelKbEmbedding = new KbEmbedding();
        $embeddings = $modelKbEmbedding
            ->field(['uuid,error,tokens,status,create_time,update_time'])
            ->where(['user_id'=>$userId])
            ->where(['kb_id'=>$kid])
            ->where(['fd_id'=>$fid])
            ->where(['is_delete'=>0])
            ->whereIn('uuid', $uuids)
            ->limit(50)
            ->select()
            ->toArray();

        $tasks = [];
        foreach ($embeddings as &$item) {
            $item['tokens'] = format_amount_zero($item['tokens']);
            $item['status_msg'] = KnowEnum::getRunStatusDesc($item['status']);
            if ($item['status'] == KnowEnum::RUN_WAIT || $item['status'] == KnowEnum::RUN_ING) {
                $tasks[] = $item['uuid'];
            }
        }

        return [
            'tasks' => $tasks,
            'lists' => $embeddings
        ]??[];
    }

    /**
     * @notes 训练数据详情
     * @param string $uuid
     * @return array
     * @author kb
     */
    public static function detail(string $uuid): array
    {
        $modelKbEmbedding = new KbEmbedding();
        $embedding = $modelKbEmbedding
            ->field(['kb_id,fd_id,uuid,question,answer,annex'])
            ->where(['uuid'=>$uuid])
            ->where(['is_delete'=>0])
            ->findOrEmpty()
            ->toArray();

        if ($embedding) {
            $images = [];
            $video  = [];
            $files  = [];
            $embedding['annex'] = json_decode($embedding['annex']??'[]', true);
            foreach ($embedding['annex']['images']??[] as $item) {
                $images[] = ['name'=>$item['name'], 'url'=>FileService::getFileUrl($item['url'])];
            }
            foreach ($embedding['annex']['video']??[] as $item) {
                $video[] = ['name'=>$item['name'], 'url'=>FileService::getFileUrl($item['url'])];
            }
            foreach ($embedding['annex']['files']??[] as $item) {
                $files[] = ['name'=>$item['name'], 'url'=>FileService::getFileUrl($item['url'])];
            }

            $embedding['images'] = $images;
            $embedding['video']  = $video;
            $embedding['files']  = $files;
        }

        return $embedding;
    }

    /**
     * @notes 搜索测试数据详情
     * @param int $tr_id
     * @return array
     * @author kb
     */
    public static function testRecordDetail(int $tr_id): array
    {
        $modelKbTest = new KbKnowTestRecord();
        $record = $modelKbTest
            ->where(['id'=>$tr_id])
            ->findOrEmpty()
            ->toArray();

        if ($record) {
            $record = json_decode($record['reply']??'[]', true);
        }
        return $record;
    }

    /**
     * @notes 训练数据删除
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function delete(array $post, int $userId): bool
    {
        try {
            $uuids = $post['uuids'];
            $kid   = intval($post['kb_id']);

            // 验证知识库的数据
            $modelKbEmbedding = new KbEmbedding();
            $pgEmbeddings = $modelKbEmbedding
                ->field(['uuid', 'user_id'])
                ->whereIn('uuid', $uuids)
                ->where(['kb_id'=>$kid])
                ->where(['is_delete'=>0])
                ->select()
                ->toArray();
            if (!$pgEmbeddings) {
                throw new Exception('数据不存在了!');
            }

            // 验证操作权限 (不是管理者,则需要验证是不是上传者)
            $power = KbKnowLogic::checkKbPower($kid, $userId);
            foreach ($pgEmbeddings as $item) {
                if ($power > KnowEnum::POWER_ALL and $item['user_id'] !== $userId) {
                    $error = [1=>'管理者', 2=>'编辑者', 3=>'查看者'];
                    throw new Exception($error[$power].'无权限删除其它用户的数据!');
                }
            }

            $modelKbEmbedding
                ->whereIn('uuid', $uuids)
                ->where(['kb_id'=>$kid])
                ->where(['is_delete'=>0])
                ->update([
                    'is_delete'   => 1,
                    'delete_time' => time()
                ]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 训练数据修正
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function update(array $post, int $userId): bool
    {
        try {
            $uuid     = $post['uuid'];
            $question = $post['question'] ?? '';
            $answer   = $post['answer']   ?? '';
            $files    = $post['files']    ?? [];
            $images   = $post['images']   ?? [];
            $video    = $post['video']    ?? [];

            // 验证数据
            $modelKbEmbedding = new KbEmbedding();
            $embedding = $modelKbEmbedding->field(['uuid,kb_id,user_id,salt,status'])->where(['uuid'=>$uuid, 'is_delete'=>0])->findOrEmpty()->toArray();
            if (!$embedding) {
                throw new Exception('数据不存在了!');
            }

            // 验证操作权限
            $power = KbKnowLogic::checkKbPower($embedding['kb_id'], $userId);
            if ($power > KnowEnum::POWER_ALL and $embedding['user_id'] !== $userId) {
                $error = [1=>'管理者', 2=>'编辑者', 3=>'查看者'];
                throw new Exception($error[$power].'无权限操作其它用户的数据!');
            }

            // 处理附件
            foreach ($files as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 处理图片
            foreach ($images as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 处理视频
            foreach ($video as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            $modelKbEmbedding = new KbEmbedding();
            $modelKbEmbedding
                ->where(['uuid'=>$uuid])
                ->where(['is_delete'=>0])
                ->update([
                    'question'    => $question,
                    'answer'      => $answer,
                    'salt'        => md5($question),
                    'error'       => '',
                    'status'      => KnowEnum::RUN_WAIT,
                    'annex'       => json_encode(['images'=>$images, 'video'=>$video, 'files'=>$files], JSON_UNESCAPED_UNICODE),
                    'update_time' => time()
                ]);

            KbEmbedding::updateTsVector([$uuid]);
            BaseQueue::pushEM(['uuid'=>$uuid]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 训练失败重试
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function reset(array $post, int $userId): bool
    {
        try {
            $uuids = $post['uuids'];
            $kid   = intval($post['kb_id']);

            // 验证数据
            $modelKbEmbedding = new KbEmbedding();
            $pgEmbeddings = $modelKbEmbedding
                ->field(['uuid,user_id,kb_id'])
                ->whereIn('uuid', $uuids)
                ->where(['kb_id'=>$kid])
                ->where(['is_delete'=>0])
                ->where(['status'=>KnowEnum::RUN_FAIL])
                ->select()
                ->toArray();

            // 修改状态
            if ($pgEmbeddings) {
                // 验证操作权限 (不是管理者,则需要验证是不是上传者)
                $power = KbKnowLogic::checkKbPower($kid, $userId);
                if ($power > KnowEnum::POWER_ALL and $pgEmbeddings['user_id'] !== $userId) {
                    foreach ($pgEmbeddings as $item) {
                        if ($item['user_id'] !== $userId) {
                            throw new Exception('存在无权限操作的数据!');
                        }
                    }
                }

                $modelKbEmbedding
                    ->whereIn('uuid', $uuids)
                    ->where(['kb_id'=>$kid])
                    ->where(['status'=>KnowEnum::RUN_FAIL])
                    ->where(['is_delete'=>0])
                    ->update([
                        'error'       => '',
                        'status'      => KnowEnum::RUN_WAIT,
                        'is_delete'   => 0,
                        'delete_time' => 0
                    ]);

                foreach ($pgEmbeddings as $item) {
                    BaseQueue::pushEM(['uuid'=>$item['uuid']]);
                }
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 录入训练数据
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function insert(array $post, int $userId): bool
    {
        try {
            $kid      = intval($post['kb_id']);
            $fid      = intval($post['fd_id']);
            $question = $post['question'] ?? '';
            $answer   = $post['answer']   ?? '';
            $video    = $post['video']    ?? [];
            $files    = $post['files']    ?? [];
            $images   = $post['images']   ?? [];

            // 验证知识库
            $modelKbKnow = new KbKnow();
            $know = $modelKbKnow->where(['id'=>$kid])->findOrEmpty();
            if ($know->isEmpty()) {
                throw new Exception('知识库丢失了,请刷新页面!');
            }

            // 验证操作权限
            KbKnowLogic::checkKbPower($know['id'], $userId);

            // 验证是否禁用
            if (!$know->is_enable) {
                throw new Exception('知识库被禁用,禁止操作!');
            }

            // 验证主模型
            $mainModel = (new Models())->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'id'=>$know['embedding_model_id']])->findOrEmpty();
            if ($mainModel->isEmpty() || !$mainModel->is_enable) {
                throw new Exception('训练模型已被下架!');
            }

            // 验证模型
            $subModels = (new ModelsCost())->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'id'=>$know['embedding_model_sub_id']])->findOrEmpty()->toArray();
            if (!$subModels) {
                throw new Exception('训练模型已下架,无法再训练!');
            }

            // 处理附件
            foreach ($files as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 处理图片
            foreach ($images as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 处理视频
            foreach ($video as &$item) {
                $item['url'] = FileService::setFileUrl($item['url']);
            }

            // 为-1时则不限制
//            $totalSpace = (new User())->where(['id' =>$userId])->value('total_space');
//            if ($totalSpace >= 0) {
//                $useSpace = (new KbEmbedding())->where(['user_id'=>$userId, 'is_delete'=> 0])->count();
//                $surplus = $totalSpace - $useSpace;
//                if ($surplus < 1) {
//                    return '知识库存储空间不足: ' . $surplus;
//                }
//            }

            $batchCode = md5(time().$fid.$kid);

            $uuid = (Uuid::uuid4())->toString();
            $modelKbEmbedding = new KbEmbedding();
            $modelKbEmbedding
                ->insert([
                    'uuid'          => $uuid,
                    'user_id'       => $userId,
                    'kb_id'         => $kid,
                    'fd_id'         => $fid,
                    'emb_model_id'  => $mainModel['id'],
                    'index'         => 1,
                    'code'          => $batchCode,
                    'salt'          => md5($question),
                    'channel'       => $subModels['channel'],
                    'model'         => $subModels['name'],
                    // 'dimension'   => $vectorModels[$embModel]['dimension'],
                    'question'      => $question,
                    'answer'        => $answer,
                    'annex'         => json_encode(['images'=>$images, 'video'=>$video, 'files'=>$files], JSON_UNESCAPED_UNICODE),
                    'status'        => KnowEnum::RUN_WAIT,
                    'create_time'   => time(),
                    'update_time'   => time(),
                    'delete_time'   => 0
                ]);

            KbEmbedding::updateTsVector([$uuid]);
            BaseQueue::pushEM(['uuid'=>$uuid]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 导入训练数据
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function import(array $post, int $userId): bool
    {
        //算力不足20时拦截上传
        TokenLogService::checkToken($userId, 'create_vector_knowledge');
        // 接收参数
        $kid    = intval($post['kb_id']);  // 知识库ID
        $method = intval($post['method']); // 录入方式: [1=文件导入, 2=QA拆分, 3=CSV导入]

        // 模型定义
        $modelKbKnow = new KbKnow();
        $modelModelsCost  = new ModelsCost();
        $modelKbEmbedding = new KbEmbedding();
        try {
            // 查知识库
            $know = $modelKbKnow->where(['id' => $kid])->findOrEmpty()->toArray();
            if (!$know) {
                throw new Exception('知识库丢失了,请刷新页面!');
            }

            // 验证操作权限
            KbKnowLogic::checkKbPower($know['id'], $userId);

            // 验证是否禁用
            if ($know['is_enable'] == 0) {
                throw new Exception('知识库被禁用,禁止操作!');
            }

            if ($method != 2) {
                // 验证主模型
                $mainModel = (new Models())->where(['type' => ChatEnum::MODEL_TYPE_EMB, 'id' => $know['embedding_model_id']])->findOrEmpty();
                if ($mainModel->isEmpty() || !$mainModel->is_enable) {
                    throw new Exception('训练模型已被下架!');
                }

                // 验证模型
                $subModels = $modelModelsCost->where(['type' => ChatEnum::MODEL_TYPE_EMB, 'id' => $know['embedding_model_sub_id']])->findOrEmpty()->toArray();
                if (!$subModels) {
                    throw new Exception('训练模型已下架,无法再训练!');
                }
            } else {
                // 验证主模型
                $mainModel = (new Models())->where(['type' => ChatEnum::MODEL_TYPE_CHAT, 'id' => $know['documents_model_id']])->findOrEmpty();
                if ($mainModel->isEmpty() || !$mainModel->is_enable) {
                    throw new Exception('QA拆分模型已被下架!');
                }

                // 验证模型
                $subModels = $modelModelsCost->where(['type' => ChatEnum::MODEL_TYPE_CHAT, 'id' => $know['documents_model_sub_id']])->findOrEmpty()->toArray();
                if (!$subModels) {
                    throw new Exception('QA拆分模型已被下架,无法再训练!');
                }
            }
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }

        try {
            $qaIds = [];
            $lists = [];
            foreach ($post['documents'] as $item) {
                // 接收参数
                $name  = trim($item['name']);
                $path  = trim($item['path']);
                $data  = $item['data'];
                $size  = $item['size'];

                // 文件名截断
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                if ($extension) {
                    $nameWithoutExtension = pathinfo($name, PATHINFO_FILENAME);
                    $truncatedName = mb_substr($nameWithoutExtension, 0, 190);
                    $name = $truncatedName . '.' . $extension;
                    $type = $extension;
                } else {
                    $name = mb_substr($name, 0, 190);
                }

                // 处理数据
                switch ($method) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        $fid = KbKnowFiles::create([
                            'user_id' => $userId,
                            'know_id' => $kid,
                            'name'    => $name,
                            'type'    => $type??'',
                            'size'    => $size,
                            'file'    => FileService::setFileUrl($path),
                            'is_qa'   => 0
                        ])['id'];

                        $index = 1;
                        $batchCode = md5(time().$fid.$name);
                        foreach ($data as $word) {
                            if (empty($word['q'])){
                                KbKnowFiles::destroy(['id' => $fid]);
                                throw new Exception('上传格式有误，请参考csv模板文件的格式上传！');
                            }
                            $lists[] = [
                                'uuid'         => (Uuid::uuid4())->toString(),
                                'user_id'      => $userId,
                                'kb_id'        => $kid,
                                'fd_id'        => $fid,
                                'emb_model_id' => $mainModel['id'],
                                'index'        => $index,
                                'code'         => $batchCode,
                                'salt'         => md5($word['q']),
                                'channel'      => $subModels['channel'],
                                'model'        => $subModels['name'],
                                //'dimension'   => $vectorModels[$embModel]['dimension'],
                                'question'     => $word['q'],
                                'answer'       => $word['a'],
                                'status'       => KnowEnum::RUN_WAIT,
                                'create_time'  => time(),
                                'update_time'  => time(),
                                'delete_time'  => 0
                            ];
                            $index++;
                        }
                        break;
                    case 5:
                        $qi = 1;
                        foreach ($data as $word) {
                            $pre = '';
                            if (count($data) > 1) {
                                $pre = $qi.'-';
                                $qi += 1;
                            }
                            $fid = KbKnowFiles::create([
                                'user_id' => $userId,
                                'know_id' => $kid,
                                'name'    => $pre .$name,
                                'file'    => FileService::setFileUrl($path),
                                'is_qa'   => 1
                            ])['id'];

                            $qa = KbKnowQa::create([
                                'user_id' => $userId,
                                'kb_id'   => $kid,
                                'fd_id'   => $fid,
                                'name'    => $pre . $name,
                                'content' => $word['q'],
                                'status'  => KnowEnum::QA_WAIT
                            ]);
                            $qaIds[] = $qa['id'];
                        }
                        break;
                }
            }

            $uuids = [];
            foreach ($lists as $item) {
                $modelKbEmbedding->insert($item);
                $uuids[] = strval($item['uuid']);
                BaseQueue::pushEM(['uuid'=>$item['uuid']]);
            }

            foreach ($qaIds as $id) {
                BaseQueue::pushQA(['id'=>$id]);
            }

            if ($uuids) {
                KbEmbedding::updateTsVector($uuids);
            }
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 搜索测试
     * @param array $post
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function tests(array $post,int $userId): bool|array
    {
        try {
            $modelKbKnow = new KbKnow();
            $know = $modelKbKnow->where(['id'=>intval($post['kb_id'])])->findOrEmpty()->toArray();
            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            // 验证参数
            if (empty($post['question'])) {
                throw new Exception('请填写要搜索的问题');
            }

            // 接收参数
            $kbIds = [intval($post['kb_id'])];
            $question       = $post['question'];
            $searchMode     = $post['search_mode'] ?? 'similar';
            $searchTokens   = $post['search_tokens'] ?? 8000;
            $searchSimilar  = $post['search_similar'] ?? 0.5;
            $rankingStatus  = $post['ranking_status'] ?? 0;
            $rankingScore   = $post['ranking_score'] ?? 0.5;
            $rankingModel   = $post['ranking_model'] ?? '';
            $optimizeAsk    = intval($post['optimize_ask'] ?? 0);
            $embModels      = $know['embedding_model_id'].':'.$know['embedding_model_sub_id'];
            $optimizeModel  = ($post['optimize_m_id']??'').':'.($post['optimize_s_id']??'');

            $questions = [$question];
            // 发起检索
            $results = [];
            foreach ($questions as $query) {
                if ($searchMode == 'similar') {
                    $lists = RecallKnow::embeddingRecall($embModels, $query, $kbIds, $userId);
                    $results[] = ['k'=>60, 'list'=>$lists];
                } elseif ($searchMode == 'full') {
                    $lists = RecallKnow::fullTextRecall($query, $kbIds);
                    $results[] = ['k'=>60, 'list'=>$lists];
                } elseif ($searchMode == 'mix') {
                    $lists = RecallKnow::mixedRecall($embModels, $query, $kbIds, $userId);
                    $results[] = ['k'=>60, 'list'=>$lists];
                }
            }

            // 结果处理(1): RRF融合
            $results = RecallUtils::rrfConcatResults($results);

            // 结果处理(2): 重排模型
            $similar = $searchSimilar;
            if ($rankingStatus and $results) {
                $similar = $rankingScore;
//                $results = (new RankerService($rankingModel))->sendAuto($question, $results, 0);
            }

            // 结果处理(3): 相似度过滤
            $results = RecallUtils::filterMaxScore($results, $searchMode, $rankingStatus, $similar);

            // 结果处理(4): 过滤最大Tokens
            $pgList = RecallUtils::filterMaxTokens($results, $searchTokens);
            $returnList = [];
            foreach ($pgList as $key => $val) {
                if (!isset($val['emb_score']) || $val['emb_score'] < $searchSimilar){
                    continue;
                }
                $returnList[$key]['uuid'] = $val['uuid'];
                $returnList[$key]['fd_id'] = $val['fd_id'];
                $returnList[$key]['emb_model_id'] = 3;
                $returnList[$key]['question'] = $val['question'];
                $returnList[$key]['answer'] = $val['answer'];
                $returnList[$key]['score'] = $val['emb_score'] ?? 0;
                $file = (new KbKnowFiles())->where(['id' => $val['fd_id']])->findOrEmpty()->toArray();
                $returnList[$key]['source_path'] = $file ? FileService::getFileUrl($file['file']) : '';
                $returnList[$key]['source'] = $file ? $file['name'] : '';
            }

            $insert = [
                'user_id'      => $userId,
                'kb_id'        => $post['kb_id'],
                'emb_model_id' => 3,
                'ask'          => $post['question'],
                'reply'        => json_encode($returnList, JSON_UNESCAPED_UNICODE),
            ];
            KbKnowTestRecord::create($insert);

            return $returnList;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 旧版搜索测试
     * @param int $kbId
     * @param string $text
     * @return bool|array
     * @author kb
     */
    public static function tests_old(int $kbId, string $text,int $userId): bool|array
    {
        try {
            $modelKbKnow = new KbKnow();
            $know = $modelKbKnow->where(['id'=>$kbId])->findOrEmpty()->toArray();
            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            // 验证主模型
            $mainModel = (new Models())->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'id'=>$know['embedding_model_id']])->findOrEmpty();
            if ($mainModel->isEmpty() || !$mainModel->is_enable) {
                throw new Exception('训练模型已被下架!');
            }

            // 验证模型
            $subModels = (new ModelsCost())->where(['type'=>ChatEnum::MODEL_TYPE_EMB, 'id'=>$know['embedding_model_sub_id']])->findOrEmpty()->toArray();
            if (!$subModels) {
                throw new Exception('训练模型已下架了!');
            }

            // 问题转向量
            $embeddingStr = (new VectorService($mainModel['id']))->toEmbedding('doubao', $subModels['name'], $text, true);

            // 匹配的规则
            $symbol = '=';
            $orders = 'score asc';

            // 查询相似度
            $modelKbEmbedding = new KbEmbedding();
            $sql = $modelKbEmbedding
                ->alias('pe')
                ->field('pe.uuid,pe.fd_id,pe.emb_model_id,pe.question,pe.answer,(pe.embedding <'.$symbol.'> :embedding) AS score')
                ->whereIn('pe.kb_id', [$kbId])
                ->where(['pe.status'=>KnowEnum::RUN_OK])
                //->where(['pe.model'=>$subModels['name']])
                ->where(['pe.is_delete'=>0])
                ->bind(['embedding'=>$embeddingStr])
                ->order($orders)
                ->limit(20)
                ->buildSql();

            // 执行数据查询
            $sql = str_replace("( SELECT", "SET LOCAL hnsw.ef_search = 100;\n(SELECT", $sql);
            $pgList = app('db')->connect('pgsql')->query($sql);

            // 处理数据格式
            foreach ($pgList as &$item) {
                $item['score'] = ltrim($item['score'], '-');
                $item['score'] = number_format(1 - $item['score'], 5);
                $file = (new KbKnowFiles())->where(['id'=>$item['fd_id']])->findOrEmpty()->toArray();
                $item['source_path'] = $file?FileService::getFileUrl($file['file']):'';
                $item['source'] = $file?$file['name']:'';
                unset($file);
            }

            $insert = [
                'user_id' => $userId,
                'kb_id' => $kbId,
                'emb_model_id' => 3,
                'ask' => $text,
                'reply' => json_encode($pgList,JSON_UNESCAPED_UNICODE),
            ];
            KbKnowTestRecord::create($insert);

            return $pgList;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 费用计算
     * @param int $kbId
     * @param string $text
     * @return bool|array
     */
    public static function charging(int $kbId, string $text): bool|array
    {
        try {
            // 验证文本
            if (!$text) {
                throw new Exception('文本内容不能为空');
            }

            // 验证知识库
            $modelKbKnow = new KbKnow();
            $know = $modelKbKnow->field(['id,embedding_model'])->where(['id' => $kbId])->findOrEmpty();
            if ($know->isEmpty()) {
                throw new Exception('知识库丢失,请刷新页面!');
            }

            // 计算token费用
            $strLength = mb_strlen($text);
            $tokens = tokens_price('emb', $know->embedding_model, $strLength);
            return [
                'tokens'     => $tokens,
                'str_length' => $strLength
            ]??[];
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes QA检测
     * @param array $fdIds
     * @param int $userId
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public static function qaCheck(array $fdIds, int $userId): array
    {
        if (!$fdIds) {
            return [
                'tasks' => [],
                'lists' => []
            ]??[];
        }

        $model = new KbKnowQa();
        $qaLists = $model
            ->field(['id,error,tokens,status,create_time,update_time'])
            ->whereIn('fd_id', $fdIds)
            ->where(['user_id'=>$userId])
            ->select()
            ->toArray();

        $tasks = [];
        foreach ($qaLists as &$item) {
            $item['status_msg'] = KnowEnum::getQaStatusDesc(intval($item['status']));
            if ($item['status'] == KnowEnum::QA_WAIT || $item['status'] == KnowEnum::QA_ING) {
                $tasks[] = $item['id'];
            }
        }

        return [
            'tasks' => $tasks,
            'lists' => $qaLists
        ]??[];
    }

    /**
     * @notes QA拆分重试
     * @param int $kbId
     * @param int $fdId
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function qaRetry(int $kbId, int $fdId, int $userId): bool
    {
        try {
            $model = new KbKnowQa();
            $qa = $model
                ->where(['kb_id'=>$kbId])
                ->where(['fd_id'=>$fdId])
                ->where(['user_id'=>$userId])
                ->findOrEmpty()
                ->toArray();

            if (!$qa) {
                throw new Exception('无法重试,记录丢失!');
            }

            if ($qa['user_id'] !== $userId) {
                $share = (new KbKnowTeam())->where(['kb_id'=>$qa['kb_id'], 'user_id'=>$userId])->findOrEmpty();
                if (!$share) {
                    throw new Exception('您不具备操作权限!');
                }
            }

            if ($qa['status'] == KnowEnum::QA_ING) {
                throw new Exception('正在拆分中,不能重试!');
            }

            KbKnowQa::update([
                'error'  => '',
                'tokens' => 0,
                'price'  => 0,
                'usage'  => '',
                'status' => KnowEnum::QA_WAIT,
                'task_time' => 0
            ], ['id'=>$qa['id']]);

            BaseQueue::pushQA(['id'=>$qa['id']]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function capture(array $urlList): bool|array
    {
        try {
            if(empty($urlList)){
                throw  new Exception('请输入需要解析的网页');
            }
            $data = [];
            foreach ($urlList as $url){
                $content = '';
                //设置请求超时时间为60秒
                $httpClient = HttpClient::create(['timeout' => 60]);
                $client = new HttpBrowser($httpClient);
                $crawler = $client->request('GET', $url);
                // 获取标题
                $titleNodeList = $crawler->filter('title');
                if($titleNodeList->count() > 0 && $titleNodeList->text()){
                    $content = $titleNodeList->text() . PHP_EOL;
                }
                //去掉爬取到的js部分
                $crawler->filter('body')
                    ->filter('script')->each(function ($node) {
                        $node->getNode(0)->parentNode->removeChild($node->getNode(0));
                    });

                $content .= $crawler->filter('body')->text();
                $data[] = [
                    'url'       => $url,
                    'content'   => $content
                ];
            }
            return $data;
        }catch (Exception $e){
            self::setError($e->getMessage());
            return false;
        }
    }
}