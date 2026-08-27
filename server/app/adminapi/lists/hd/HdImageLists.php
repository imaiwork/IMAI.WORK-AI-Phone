<?php


namespace app\adminapi\lists\hd;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\draw\DrawTask;
use app\common\model\hd\HdLog;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\model\hd\HdImage;
use app\common\service\draw\DrawBillingService;


/**
 * HdImage列表
 * Class HdImageLists
 * @package app\adminapi\listsmp
 */
class HdImageLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * 列表展示的 hd_log.type 范围（lists 与 count 必须一致）
     */
    protected const LIST_TYPES = [1, 2, 3, 4, 5];


    /**
     * @desc 设置搜索条件
     * @return array[]
     * @date 2024/5/23 11:52
     * @author dagouzi
     */
    public function setSearch(): array
    {

        return [];
    }


    /**
     * @desc 获取列表
     * @return array
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {
        $rows = $this->buildQuery()
            ->field('l.id,l.user_id,l.type,l.create_time,u.nickname,u.avatar,l.task_id,l.params,l.model_type,l.draw_task_id,l.task_status')
            ->order('l.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        if (empty($rows)) {
            return [];
        }

        // 当页数据一次性预取，避免逐条查库
        $imageMap    = $this->getImageMap(array_column($rows, 'id'));
        $tokensMap   = $this->getTokensLogMap(array_column($rows, 'task_id'));
        $drawCostMap = $this->getDrawTaskCostMap(array_column($rows, 'draw_task_id'));

        foreach ($rows as &$item) {
            $item['avatar'] = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
            // 获取对应列表
            $item['images'] = $imageMap[$item['id']] ?? [];

            [$scene, $typeName] = $this->resolveScene((int)$item['type']);

            $item['type_name'] = $typeName;
            $item['params']    = json_decode($item['params'], true) ?? [];

            // 命中本条记录的流水：user_id + change_type + (task_id 或 source_sn)，口径同原逐条查询
            $logs = $this->matchLogs($tokensMap, (string)($item['task_id'] ?? ''), (int)$item['user_id'], $scene);

            $images = 0;
            foreach ($logs as $log) {
                $info = is_array($log['extra'] ?? null)
                    ? $log['extra']
                    : (json_decode((string)($log['extra'] ?? ''), true) ?: []);
                $images += $info['生成图片数'] ?? 0;
            }
            $item['image_count'] = $images;

            $drawTaskId     = (int)($item['draw_task_id'] ?? 0);
            $item['points'] = DrawBillingService::resolveRecordPointsFromLogs(
                $logs,
                $drawTaskId > 0 ? (float)($drawCostMap[$drawTaskId] ?? 0) : null
            );

            $imageState = match ((int)($item['task_status'] ?? 0)) {
                1 => 'consume',
                2 => 'refund',
                default => 'hold',
            };
            $item['points_remark'] = DrawBillingService::consumePointsRemark($imageState, (float)$item['points']);
        }
        unset($item);

        return $rows;
    }


    /**
     * @desc 获取数量
     * @return int
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DbException
     * @author dagouzi
     */
    public function count(): int
    {
        return $this->buildQuery()->count();
    }


    /**
     * 列表与计数共用的查询条件
     */
    protected function buildQuery()
    {
        return HdLog::alias('l')
            ->leftJoin('user u', 'u.id = l.user_id and l.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('type', []), function ($query) {
                $query->whereIn('l.type', $this->request->get('type'));
            })
            ->when($this->request->get('model_type'), function ($query) {
                $query->where('l.model_type', $this->request->get('model_type'));
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->whereIn('l.type', self::LIST_TYPES);
    }


    /**
     * hd_log.type → [算力流水的 change_type, 类型名]
     */
    protected function resolveScene(int $type): array
    {
        [$scene, $typeName] = match ($type) {
            1 => [[AccountLogEnum::TOKENS_DEC_GOODS_IMAGE], '商品图'],
            2 => [[AccountLogEnum::TOKENS_DEC_MODEL_IMAGE], '模特换衣图'],
            3 => [[AccountLogEnum::TOKENS_DEC_TEXT_TO_IMAGE, AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_IMAGE, AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE], '文生图'],
            4 => [[AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE, AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE], '图生图'],
            5 => [[AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE], '海报图'],
            default => [[], ''],
        };

        $scene[] = AccountLogEnum::TOKENS_DEC_DRAW_IMAGE;

        return [$scene, $typeName];
    }


    /**
     * 从预取的流水里挑出属于某条记录的行
     */
    protected function matchLogs(array $tokensMap, string $taskId, int $userId, array $scene): array
    {
        if ($taskId === '' || empty($tokensMap[$taskId])) {
            return [];
        }

        $logs = [];
        foreach ($tokensMap[$taskId] as $log) {
            if ((int)$log['user_id'] === $userId && in_array((int)$log['change_type'], $scene, true)) {
                $logs[] = $log;
            }
        }

        return $logs;
    }


    /**
     * 当页图片：log_id => [['image'=>, 'task_status'=>], ...]
     */
    protected function getImageMap(array $logIds): array
    {
        $logIds = array_values(array_unique(array_filter($logIds)));
        if (empty($logIds)) {
            return [];
        }

        $map = [];
        // 用 select() 而非 column()，保留 image 字段的访问器（补全图片域名）
        foreach (HdImage::whereIn('log_id', $logIds)->field('log_id,image,task_status')->select()->toArray() as $row) {
            $logId = $row['log_id'];
            unset($row['log_id']);
            $map[$logId][] = $row;
        }

        return $map;
    }


    /**
     * 当页算力流水：task 标识 => [流水行, ...]
     * 老流水可能 task_id 为空、只对得上 source_sn，两个字段都要建索引
     */
    protected function getTokensLogMap(array $taskIds): array
    {
        $taskIds = array_values(array_unique(array_filter(array_map('strval', $taskIds), function ($v) {
            return $v !== '';
        })));
        if (empty($taskIds)) {
            return [];
        }

        $rows = UserTokensLog::where(function ($query) use ($taskIds) {
                $query->whereIn('task_id', $taskIds)->whereOr('source_sn', 'in', $taskIds);
            })
            ->field('user_id,task_id,source_sn,change_type,action,change_amount,extra')
            ->select()
            ->toArray();

        $wanted = array_flip($taskIds);
        $map    = [];
        foreach ($rows as $row) {
            // 同一行的 task_id 与 source_sn 可能相同，去重后再入桶，避免重复累加
            $keys = array_unique(array_filter([
                (string)($row['task_id'] ?? ''),
                (string)($row['source_sn'] ?? ''),
            ]));
            foreach ($keys as $key) {
                if (isset($wanted[$key])) {
                    $map[$key][] = $row;
                }
            }
        }

        return $map;
    }


    /**
     * 当页 draw_task 的已结算算力：id => tokens_cost
     */
    protected function getDrawTaskCostMap(array $drawTaskIds): array
    {
        $drawTaskIds = array_values(array_unique(array_filter(array_map('intval', $drawTaskIds))));
        if (empty($drawTaskIds)) {
            return [];
        }

        return DrawTask::whereIn('id', $drawTaskIds)->column('tokens_cost', 'id');
    }
}
