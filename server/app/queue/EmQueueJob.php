<?php
namespace app\queue;

use app\common\enum\ChatEnum;
use app\common\enum\kb\KnowEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
use app\common\model\user\User;
use app\common\pgsql\KbEmbedding;
use app\common\service\ai\VectorService;
use Exception;
use think\queue\Job;

class EmQueueJob
{
    public function fire(Job $job, array $data): bool|string
    {
        // 接收参数
        echo "\n";
        $startTime = time();
        $uuid = trim($data['uuid']);

        // 读取任务
        try {
            $modelKbEmbedding = new KbEmbedding();
            $kbEmbedding = $modelKbEmbedding
                ->field(['uuid,user_id,kb_id,status,question,answer'])
                ->where(['uuid' => $uuid,'status' => 0,'is_delete' => 0])
                ->findOrEmpty();
            if ($kbEmbedding->isEmpty()){
                $job->delete();
                return false;
            }
        } catch (Exception $e) {
            echo $e->getLine() . ':' . $e->getMessage();
            return false;
        }

        // 读知识库
        $modelKbKnow = new KbKnow();
        $kbId = $kbEmbedding['kb_id']??0;
        $kbKnow = $modelKbKnow->where(['id'=>$kbEmbedding['kb_id']??0])->findOrEmpty();

        // 查询模型
        $mainModels = (new Models())->where(['id'=>$kbKnow->embedding_model_id??0])->findOrEmpty();
        echo "开始读模型: " . ($kbKnow->embedding_model_id??0) . "\n";
        if ($mainModels->isEmpty())  { return $this->inputError("模型已下架", $uuid, $job); }
        if (!$mainModels->is_enable) { return $this->inputError("模型已停用", $uuid, $job); }

        // 训练模型
        $modelCost = (new ModelsCost())
            ->where(['type' => ChatEnum::MODEL_TYPE_EMB])
            ->where(['id' => $kbKnow->embedding_model_sub_id])
            ->where(['model_id' => $kbKnow->embedding_model_id])
            ->findOrEmpty()
            ->toArray();

        // 验证数据
        if ($kbKnow->isEmpty())      { return $this->inputError("知识库丢失了($kbId)", $uuid, $job, $kbId); }
        if ($kbEmbedding->isEmpty()) { return $this->inputError("不存在的数据", $uuid, $job); }
        if ($kbEmbedding->is_delete) { return $this->inputError("数据已被删除", $uuid, $job); }
        if (!$kbKnow->is_enable)     { return $this->inputError("知识库被禁用", $uuid, $job); }
        if (!$modelCost)             { return $this->inputError("模型不存在了", $uuid, $job); }
        if (!$modelCost['channel'])  { return $this->inputError("渠道不存在了", $uuid, $job); }

        // 验证用户
        $modelUser = new User();
        $user = $modelUser->where(['id'=>$kbEmbedding->user_id])->findOrEmpty();
        if ($user->isEmpty())    { return $this->inputError("用户不存在了", $uuid, $job); }

        // 读取密钥
        echo '训练的通道: ' . $modelCost['channel'] . "(" . $modelCost['model_id'] . ")\n";
        echo '训练的模型: ' . $modelCost['name'] . "(" . $modelCost['alias'] . ")\n";

        // 更新状态
        $kbEmbedding->status = KnowEnum::RUN_ING;
        $kbEmbedding->error  = '';
        $kbEmbedding->save();
        sleep(1);

        // 开始训练
        try {
            echo "开始转向量: ". $uuid . "\n";
            $vectorService = new VectorService($kbKnow->embedding_model_id);
            $embedding = $vectorService->toEmbedding($modelCost['channel'], $modelCost['name'], $kbEmbedding->question,$kbEmbedding->user_id);
            echo "转向量成功: ". $uuid . "\n";
            echo "向量的维度: ". count($embedding) . "\n";
            echo "全程的耗时: ". time() - $startTime . "s\n";
        } catch (Exception $e) {
            echo "转向量失败: " . $this->formatError($e->getMessage()) . "\n";
            $this->queueCount();
            $kbEmbedding->error  = $this->formatError($e->getMessage());
            $kbEmbedding->status = KnowEnum::RUN_FAIL;
            $kbEmbedding->save();
            $job->delete();
            sleep(1);
            return false;
        }

        // 训练计费

        // 保存向量
        $kbEmbedding->model = $modelCost['name'];
        $kbEmbedding->dimension = count($embedding);
        $kbEmbedding->embedding = json_encode($embedding);
        $kbEmbedding->status = KnowEnum::RUN_OK;
        $kbEmbedding->error = '';
        $kbEmbedding->tokens = 0;
        $kbEmbedding->update_time = time();
        $kbEmbedding->save();

        // Tokens消费记录

        // 用户扣费

        // 删除任务
        $job->delete();
        sleep(1);
        $this->queueCount();
        return true;
    }

    /**
     * @notes 超出重试次数
     * @param array $data
     * @author kb
     */
    public function failed(array $data): void
    {
        $kbId = intval($data['kid']);
        $uuid = trim($data['uuid']);

        $modelKbEmbedding = new KbEmbedding();
        $kbEmbedding = $modelKbEmbedding
            ->where(['uuid'=>$uuid])
            ->where(['kd_id'=>$kbId])
            ->findOrEmpty();

        if (!$kbEmbedding->isEmpty()) {
            if (!$kbEmbedding->error) {
                $kbEmbedding->error = '已达到最大重试次数~';
            }
            $kbEmbedding->status = KnowEnum::RUN_FAIL;
            $kbEmbedding->update_time = time();
            $kbEmbedding->save();
        }
    }

    /**
     * @notes 输出错误信息
     * @param string $err
     * @param string $uuid
     * @param Job $job
     * @param int $kbId
     * @return string
     * @author kb
     */
    private function inputError(string $err, string $uuid, Job $job, int $kbId=0): string
    {
        echo "\n";
        echo $err . ": " . $uuid . "\n";
        $job->delete();
        sleep(1);

        $modelKbEmbedding = new KbEmbedding();
        if ($kbId) {
            // 因为知识库不存在了,所以这个知识库所有数据都不需要训练
            // 由于是单队列的问题,这边需要清空队列数据,重装任务
            $modelKbEmbedding
                ->where(['kb_id'=>$kbId])
                ->whereIn('status', [KnowEnum::RUN_WAIT, KnowEnum::RUN_ING])
                ->update([
                    'error'  => $err,
                    'status' => KnowEnum::RUN_FAIL,
                ]);

            BaseQueue::clearEmbQueue();
            sleep(1);
        } else {
            $modelKbEmbedding->where(['uuid'=>$uuid])->update([
                'error'  => $err,
                'status' => KnowEnum::RUN_FAIL,
            ]);
        }

        $this->queueCount();
        return $err;
    }

    /**
     * @ntoes 格式化异常输出
     * @param string $err
     * @return string
     * @author kb
     */
    private function formatError(string $err): string
    {
        if (str_starts_with($err, 'Rate limit reached for text-embedding-ada-002')) {
            return '密钥请求数（RPM）上达到速率限制';
        }
        return $err;
    }

    /**
     * @notes 获取队列中的任务
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    private function queueCount(): void
    {
        sleep(1);
        $queues = BaseQueue::getEmbJobLength();
        echo "剩余的任务: " . $queues . "\n";
        if ($queues <= 0) {
            $model = new KbEmbedding();
            $lists = $model
                ->field(['uuid'])
                ->where(['is_delete'=>0])
                ->whereIn('status', [KnowEnum::RUN_WAIT])
                ->order('status desc')
                ->limit(100)
                ->select()
                ->toArray();

            if ($lists) {
                foreach ($lists as $item) {
                    BaseQueue::pushEM(['uuid'=>$item['uuid']]);
                }
                echo "重载任务数: " . count($lists) ."\n";
            }
        }
    }
}