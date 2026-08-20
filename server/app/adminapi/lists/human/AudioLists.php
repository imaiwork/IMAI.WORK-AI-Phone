<?php

namespace app\adminapi\lists\human;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanAudio;
use app\common\model\human\HumanVoice;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\ChatEnum;
use app\common\model\chat\Models;

/**
 * 音频
 */
class AudioLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
            "=" => ['model_version'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        // 模型列表（以 id 为键）
        $modelMap = $this->getModelMap();

        // 音色名称预加载
        $voiceMap = HumanVoice::column('name', 'voice_id');

        return $this->buildQuery()
            ->field('ha.id,ha.name,ha.user_id,ha.model_version,ha.type,ha.voice_id,
                ha.task_id,ha.create_time,ha.update_time,ha.url,ha.status,u.nickname,u.avatar')
            ->order(['ha.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelMap, $voiceMap) {
                // 文件 URL
                $item['url']    = FileService::getFileUrl($item['url']);
                $item['avatar'] = FileService::getFileUrl($item['avatar']);

                // 音色名称
                $item['voice_name'] = $voiceMap[$item['voice_id']] ?? '';

                // 模型名称
                $item['model_name'] = $modelMap[$item['model_version']]['name'] ?? '';

                // 消耗统计
                $changeType = $this->getChangeType((int)$item['model_version']);
                [$points, $duration] = $changeType
                    ? $this->getTokensCost($item['user_id'], $item['task_id'], $changeType)
                    : [0, 0];

                $item['points']   = $points;
                $item['duration'] = $duration;
            })
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    /**
     * 构建公共查询条件
     */
    protected function buildQuery()
    {
        $type      = $this->getType();
        $user      = $this->request->get('user');
        $startTime = $this->request->get('start_time');
        $endTime   = $this->request->get('end_time');

        return HumanAudio::alias('ha')
            ->join('user u', 'u.id = ha.user_id')
            ->when($user, function ($query) use ($user) {
                $query->where('u.nickname', 'like', '%' . $user . '%');
            })
            ->when($type, function ($query) use ($type) {
                $query->where('ha.type', $type);
            })
            ->when($startTime && $endTime, function ($query) use ($startTime, $endTime) {
                $query->whereBetween('ha.create_time', [strtotime($startTime), strtotime($endTime)]);
            })
            ->where($this->searchWhere);
    }

    /**
     * 获取 type 参数（空字符串视为 0）
     */
    protected function getType(): int
    {
        $type = trim((string)$this->request->get('type', '0'));
        return $type === '' ? 0 : (int)$type;
    }

    /**
     * 获取模型列表，以 id 为键
     */
    protected function getModelMap(): array
    {
        $list = (new Models())
            ->field(['id', 'type', 'channel', 'logo', 'name', 'is_enable'])
            ->where(['type' => ChatEnum::MODEL_TYPE_HUMAN])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        return array_column($list, null, 'id');
    }

    /**
     * 根据 model_version 获取对应的 change_type
     */
    protected function getChangeType(int $modelVersion): ?int
    {
        return match ($modelVersion) {
            1       => AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO,
            2       => AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_PRO,
            4       => AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM,
            6       => AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YMT,
            7       => AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_CHANJING,
            default => null,
        };
    }

    /**
     * 统计某任务的 tokens 消耗
     *
     * @param  int    $userId
     * @param  mixed  $taskId
     * @param  int    $changeType
     * @return array  [points, duration]
     *   - action=1 扣减: points -= change_amount
     *   - action=2 退还: points += change_amount，并从 extra.音视频时长 中提取 duration
     */
    protected function getTokensCost(int $userId, $taskId, int $changeType): array
    {
        $points   = 0;
        $duration = 0;

        UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('change_type', $changeType)
            ->field('extra,change_type,change_amount,action')
            ->select()
            ->each(function ($log) use (&$points, &$duration) {
                if ($log['action'] == 1) {
                    $points -= $log['change_amount'];
                } else {
                    $points  += $log['change_amount'] ?? 0;
                    $extra    = json_decode($log['extra'], true);
                    $duration = $extra['音视频时长'] ?? $duration;
                }
            });

        return [$points, $duration];
    }
}
