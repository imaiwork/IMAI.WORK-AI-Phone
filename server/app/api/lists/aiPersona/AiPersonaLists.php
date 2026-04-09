<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\api\logic\aiPersona\AiPersonaLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvDevice;
use app\common\service\FileService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * AI人设列表
 */
class AiPersonaLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['ap.persona_name', 'ap.quick_desc', 'ap.industry'],
        ];
    }

    /**
     * 构建基础查询条件
     * @return array
     */
    public function where(): array
    {
        $where = [
            ['ap.user_id', '=', $this->userId],
            ['ap.delete_time', '=', null]
        ];

        // 人设类型筛选（精准匹配）
        if (isset($this->params['persona_type']) && is_numeric($this->params['persona_type']) && $this->params['persona_type'] > 0) {
            $where[] = ['ap.persona_type', '=', intval($this->params['persona_type'])];
        }

        // 状态筛选（精准匹配）
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['ap.status', '=', intval($this->params['status'])];
        }

        // 报告状态筛选（扩展筛选条件，按需启用）
        if (isset($this->params['report_status']) && in_array($this->params['report_status'], [0,1,2])) {
            $where[] = ['ap.report_status', '=', intval($this->params['report_status'])];
        }

        // 配置状态筛选（扩展筛选条件，按需启用）
        if (isset($this->params['is_configured']) && in_array($this->params['is_configured'], [0,1])) {
            $where[] = ['ap.is_configured', '=', intval($this->params['is_configured'])];
        }

        return $where;
    }

    /**
     * 获取列表数据
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function lists(): array
    {
        // 预加载三张子表并过滤软删除数据，避免N+1查询+无效数据
        $model = new AiPersona();
        $lists = $model->with([
                                  'individual' => function ($query) {
                                      $query->where('delete_time', null); // 过滤子表软删除数据
                                  },
                                  'enterprise' => function ($query) {
                                      $query->where('delete_time', null);
                                  },
                                  'local' => function ($query) {
                                      $query->where('delete_time', null);
                                  }
                              ])
                       ->alias('ap')
                       ->field([
                                   'ap.id', 'ap.user_id', 'ap.persona_name', 'ap.persona_type', 'ap.avatar_url',
                                   'ap.quick_desc', 'ap.industry', 'ap.is_configured', 'ap.status',
                                   'ap.create_time', 'ap.report_status', 'ap.report_gen_time', 'ap.publish_mode'
                               ])
                       ->where($this->where())
                       ->where($this->searchWhere) // 使用 setSearch 定义的搜索条件
                       ->limit($this->limitOffset, $this->limitLength)
                       ->order('ap.create_time desc')
                       ->select()
                       ->toArray();

        // 数据格式化（精简+标准化）
        $reportStatusMap = [0 => '未生成', 1 => '生成中', 2 => '已生成'];
        foreach ($lists as &$item) {
            // 头像URL处理（兼容空值）
            $item['avatar_url'] = FileService::getFileUrl($item['avatar_url'] ?? '');

            // 子表数据整合（只保留对应类型的子表数据，减少返回体积）
            $item['sub_data'] = match ($item['persona_type']) {
                1 => $item['individual'] ?? [],
                2 => $item['enterprise'] ?? [],
                3 => $item['local'] ?? [],
                default => []
            };
            // 清理无用的子表字段
            unset($item['individual'], $item['enterprise'], $item['local']);

            // 状态文本映射（前端友好）
            $item['status_text'] = $item['status'] ? '启用' : '禁用';
            $item['report_status_text'] = $reportStatusMap[$item['report_status']] ?? '未生成';

            // 时间格式化（可选，根据前端需求调整）
            $item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['report_gen_time'] = $item['report_gen_time'] ? date('Y-m-d H:i:s', $item['report_gen_time']) : '';

            // 查询AI人设绑定了多少设备
            $item['device_num'] = SvDevice::where('persona_id', $item['id'])->count();

            // 检查AI人设配置状态
            $result = AiPersonaLogic::checkAiPersonaConfigStatus($item['id'], $item['user_id']);
            $item['is_configured'] = $result['is_configured'] ?? 0;
        }

        return $lists;
    }

    /**
     * 统计总数（优化查询效率，只查主键计数）
     * @return int
     * @throws DbException
     */
    public function count(): int
    {
        return (new AiPersona())->alias('ap')
                                ->where($this->where())
                                ->where($this->searchWhere)
                                ->count('ap.id'); // 显式指定主键计数，比count()更高效
    }
}