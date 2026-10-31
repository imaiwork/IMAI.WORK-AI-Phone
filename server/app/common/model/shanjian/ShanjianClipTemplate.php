<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;


class ShanjianClipTemplate extends BaseModel
{
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    /**
     * 生效排序表达式：后台设过本站排序就用它，否则跟随模板中心下发的 sort。
     * 只用于 ORDER BY，不要拿去当字段值读写。
     */
    public const SORT_EXPRESSION = 'IFNULL(sort_override, sort)';

    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    /**
     * 某个场景下前台可用的模板 id（status=1，已按本站排序）。
     * 手动创作、24 小时自动创作、热点/爆款复刻随机选模板统一走这里，
     * 避免各处写 where('scene', ...)->column('id') 时漏掉显隐。
     *
     * @return string[]
     */
    public static function visibleIdsByScene(string $scene, ?int $autoType = null): array
    {
        $scene = trim($scene);
        if ($scene === '') {
            return [];
        }

        $query = self::where('scene', $scene)->where('status', 1);
        if ($autoType !== null) {
            $query->where('auto_type', $autoType);
        }

        $ids = $query->orderRaw(self::SORT_EXPRESSION . ' DESC, id DESC')->column('id');

        return array_values(array_map('strval', is_array($ids) ? $ids : []));
    }

    /**
     * 过滤掉已隐藏 / 已不存在 / 跨场景的剪辑模板选择。
     * 前端草稿或历史设置里可能还存着被后台隐藏的 id，进到下单一律视为无效，
     * 全部被过滤后由调用方回落到可见模板池随机（与模板为空时的既有行为一致）。
     *
     * @param array<int, mixed> $clipData 形如 [['clip_template_id' => 'xxx'], ...]
     * @return array<int, mixed>
     */
    public static function filterVisibleClipData(array $clipData, string $scene): array
    {
        $scene = trim($scene);
        if ($clipData === [] || $scene === '') {
            return $clipData;
        }

        $candidates = [];
        foreach ($clipData as $item) {
            $id = is_array($item) ? trim((string)($item['clip_template_id'] ?? '')) : '';
            if ($id !== '') {
                $candidates[$id] = true;
            }
        }
        if ($candidates === []) {
            return [];
        }

        $visible = self::whereIn('id', array_keys($candidates))
            ->where('scene', $scene)
            ->where('status', 1)
            ->column('id');
        $visibleMap = array_fill_keys(array_map('strval', is_array($visible) ? $visible : []), true);

        $filtered = [];
        foreach ($clipData as $item) {
            $id = is_array($item) ? trim((string)($item['clip_template_id'] ?? '')) : '';
            if ($id !== '' && isset($visibleMap[$id])) {
                $filtered[] = $item;
            }
        }

        return array_values($filtered);
    }
}
