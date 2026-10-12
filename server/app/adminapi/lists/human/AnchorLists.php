<?php

namespace app\adminapi\lists\human;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanAnchor;
use app\common\service\digitalHuman\PendingAnchorTaskService;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\ChatEnum;
use app\common\model\chat\Models;

/**
 * 形象
 */
class AnchorLists extends BaseAdminDataLists implements ListsSearchInterface
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
        // 未分发的公共形象先落成渠道表真实行，列表 id 用自增主键
        PendingAnchorTaskService::materialize('human');
        // 模型列表（以 id 为键）
        $modelMap = $this->getModelMap();

        $list = $this->buildQuery()
            ->field('ha.id,ha.name,ha.user_id,ha.model_version,ha.task_id,ha.gender,ha.type,
                ha.create_time,ha.update_time,ha.pic,ha.url,ha.status,u.nickname,u.avatar,ha.remark')
            ->order(['ha.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelMap) {
                // 文件 URL
                $item['pic']    = FileService::getFileUrl($item['pic']);
                $item['url']    = FileService::getFileUrl($item['url']);
                $item['avatar'] = FileService::getFileUrl($item['avatar']);

                // 模型名称
                $item['model_name'] = $modelMap[$item['model_version']]['name'] ?? '';

                // 消耗统计
                $changeType = $this->getChangeType((int)$item['model_version']);
                $item['points'] = $changeType
                    ? $this->getTokensCost($item['user_id'], $item['task_id'], $changeType)
                    : 0;
            })
            ->toArray();

        return $list;
    }

    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        PendingAnchorTaskService::materialize('human');
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

        return HumanAnchor::alias('ha')
            ->join('user u', 'u.id = ha.user_id')
            ->when($type, function ($query) use ($type) {
                $query->where('ha.type', $type);
            })
            ->when($user, function ($query) use ($user) {
                $query->where('u.nickname', 'like', '%' . $user . '%');
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
            1       => AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR,
            2       => AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_PRO,
            4       => AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YM,
            6       => AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YMT,
            7       => AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING,
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
