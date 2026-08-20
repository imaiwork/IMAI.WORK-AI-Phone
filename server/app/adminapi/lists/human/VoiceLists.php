<?php

namespace app\adminapi\lists\human;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\ChatEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\Models;
use app\common\model\human\HumanVoice;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;

/**
 * 音色
 */
class VoiceLists extends BaseAdminDataLists implements ListsSearchInterface
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
        // 获取模型列表并以 id 为键重组，便于 O(1) 查找
        $modelMap = array_column(
            (new Models())
                ->field(['id', 'type', 'channel', 'logo', 'name', 'is_enable'])
                ->where(['type' => ChatEnum::MODEL_TYPE_HUMAN])
                ->order('sort asc, id desc')
                ->select()
                ->toArray(),
            null,
            'id'
        );

        return $this->buildQuery()
            ->field('hv.id,hv.name,hv.user_id,hv.model_version,hv.gender,hv.type,
                hv.task_id,hv.create_time,hv.update_time,hv.voice_urls,hv.status,u.nickname,u.avatar')
            ->order(['hv.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelMap) {
                $item['url']    = FileService::getFileUrl($item['voice_urls']);
                $item['avatar'] = trim($item['avatar']) ? FileService::getFileUrl($item['avatar']) : '';

                // 模型名称
                $item['model_name'] = $modelMap[$item['model_version']]['name'] ?? '';

                // 消耗类型映射
                $changeType = $this->getChangeType((int)$item['model_version']);

                // 消耗 Tokens 统计（增加 + 退还）
                $item['points'] = $changeType ? $this->getTokensCost($item['user_id'], $item['task_id'], $changeType) : 0;
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

        return HumanVoice::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->when($user, function ($query) use ($user) {
                $query->where('u.nickname', 'like', '%' . $user . '%');
            })
            ->when($type, function ($query) use ($type) {
                $query->where('hv.type', $type);
            })
            ->when($startTime && $endTime, function ($query) use ($startTime, $endTime) {
                $query->whereBetween('hv.create_time', [strtotime($startTime), strtotime($endTime)]);
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
     * 根据 model_version 获取对应的 change_type
     */
    protected function getChangeType(int $modelVersion): ?int
    {
        return match ($modelVersion) {
            1       => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE,
            2       => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_PRO,
            4       => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM,
            6       => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YMT,
            7       => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CHANJING,
            8       => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN,
            10      => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD,
            11      => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO,
            default => null,
        };
    }

    /**
     * 统计某任务的 tokens 消耗（扣减 + 退还）
     */
    protected function getTokensCost(int $userId, $taskId, int $changeType): int
    {
        return (int)UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('change_type', $changeType)
            ->whereIn('action', [1, 2])
            ->sum('change_amount');
    }
}
