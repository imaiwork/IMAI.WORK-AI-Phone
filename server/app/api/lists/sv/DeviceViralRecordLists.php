<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceViralManualImport;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\service\FileService;

/**
 * 爆款库记录列表
 */
class DeviceViralRecordLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private const MANUAL_KEYWORD = '手动入库';

    private array $platforms = [
        DeviceEnum::ACCOUNT_TYPE_SPH,
        DeviceEnum::ACCOUNT_TYPE_XHS,
        DeviceEnum::ACCOUNT_TYPE_DY,
        DeviceEnum::ACCOUNT_TYPE_KS,
    ];

    public function setSearch(): array
    {
        return [
            '=' => ['ps.keyword'],
        ];
    }

    public function lists(): array
    {
        $manualRows = $this->fetchManualImportRows();
        $manualCount = count($manualRows);
        $offset = (int)$this->limitOffset;
        $length = (int)$this->limitLength;

        $manualSlice = [];
        $viralOffset = $offset;
        $viralLimit = $length;

        if ($manualCount > 0 && $length > 0) {
            if ($offset < $manualCount) {
                $manualSlice = array_slice($manualRows, $offset, $length);
                $taken = count($manualSlice);
                $viralOffset = 0;
                $viralLimit = max(0, $length - $taken);
            } else {
                $viralOffset = $offset - $manualCount;
                $viralLimit = $length;
            }
        }

        $viralList = [];
        if ($viralLimit > 0) {
            // Collection::each 对 Model 对象会忽略回调返回值；先 toArray 再格式化
            $viralList = array_map(
                fn($item) => $this->formatViralListItem($item),
                $this->buildQuery()
                    ->field($this->listFields())
                    ->order('ps.id', 'desc')
                    ->limit($viralOffset, $viralLimit)
                    ->select()
                    ->toArray()
            );
        }

        $manualSlice = $this->dedupeManualAgainstViral($manualSlice, $viralList);

        return array_merge($manualSlice, $viralList);
    }

    public function count(): int
    {
        $viralCount = $this->buildQuery()->count();
        $manualCount = $this->buildManualImportQuery(true)->count();
        return $viralCount + $manualCount;
    }

    public function extend(): array
    {
        return [
            // tabs 为当前 day/persona/兴趣态下的全平台汇总,不受 account_type 筛选影响
            'tabs' => $this->buildPlatformTabs(),
            'keyword_list' => $this->getKeywordList(),
            'uninterested_count' => $this->buildQuery(0, false)->count()
                + $this->buildManualImportQuery(false, 0)->count(),
        ];
    }

    /**
     * 平台 tabs 统计:
     * - 始终按全平台汇总(等同不传 account_type)
     * - 切前端 tab 时数字不变,不读请求里的 account_type / keyword
     * - 按记录快照 publish_platform 归属
     * - 感兴趣态下叠加手动导入数量（含成功，见 buildManualImportQuery）
     */
    private function buildPlatformTabs(): array
    {
        $rows = $this->buildTabsQuery()
            ->field('IFNULL(ps.publish_platform, 0) as platform, COUNT(ps.id) as total')
            ->group('ps.publish_platform')
            ->select()
            ->toArray();

        $counts = [];
        $all = 0;
        foreach ($rows as $row) {
            $platform = (int)($row['platform'] ?? 0);
            $total = (int)($row['total'] ?? 0);
            $counts[$platform] = $total;
            $all += $total;
        }

        $manualRows = $this->buildManualImportQuery(false)
            ->field('publish_platform, COUNT(*) as total')
            ->group('publish_platform')
            ->select()
            ->toArray();
        foreach ($manualRows as $row) {
            $platform = (int)($row['publish_platform'] ?? 0);
            $total = (int)($row['total'] ?? 0);
            $counts[$platform] = ($counts[$platform] ?? 0) + $total;
            $all += $total;
        }

        $tabs = [
            [
                'account_type' => 0,
                'name' => '全部',
                'count' => $all,
            ],
        ];

        foreach ($this->platforms as $platform) {
            $tabs[] = [
                'account_type' => $platform,
                'name' => DeviceEnum::getAccountTypeDesc($platform),
                'count' => $counts[$platform] ?? 0,
            ];
        }

        return $tabs;
    }

    /**
     * tabs 专用查询:故意不复用 buildQuery,杜绝 account_type/keyword 渗入
     */
    private function buildTabsQuery()
    {
        $isInterested = $this->getInterestedParam();
        $personaId = $this->getPersonaId();

        $query = SvDeviceViralRecord::alias('ps')
            ->where($this->baseWhere($isInterested, false));
        $this->applyStatusAndTimeFilter($query);

        if ($personaId > 0) {
            $query->join('sv_device_viral_account va', 'va.id = ps.viral_account_id', 'left')
                ->where(function ($query) use ($personaId) {
                    $query->where('ps.persona_id', '=', $personaId)
                        ->whereOr('va.persona_id', '=', $personaId);
                });
        }

        return $query;
    }

    private function buildQuery(?int $isInterested = null, bool $withAccountType = true, bool $withKeyword = true)
    {
        $isInterested = $isInterested ?? $this->getInterestedParam();
        $accountType = $this->getAccountType();
        $personaId = $this->getPersonaId();

        $query = SvDeviceViralRecord::alias('ps')
            ->join('sv_device_viral_account va', 'va.id = ps.viral_account_id', 'left')
            ->where($this->baseWhere($isInterested, $withKeyword));
        $this->applyStatusAndTimeFilter($query);

        return $query
            ->when($withAccountType && $accountType > 0, function ($query) use ($accountType) {
                // 只按记录快照平台筛选,避免账号类型变更后历史数据串到新 tab
                $query->where('ps.publish_platform', '=', $accountType);
            })
            ->when($personaId > 0, function ($query) use ($personaId) {
                $query->where(function ($query) use ($personaId) {
                    $query->where('ps.persona_id', '=', $personaId)
                        ->whereOr('va.persona_id', '=', $personaId);
                });
            });
    }

    /**
     * 基础条件（不含 status / 时间维度，由 applyStatusAndTimeFilter 处理）
     */
    private function baseWhere(int $isInterested, bool $withKeyword = true): array
    {
        $where = $withKeyword ? $this->searchWhere : [];
        $where[] = ['ps.user_id', '=', $this->userId];
        $where[] = ['ps.is_interested', '=', $isInterested];

        return $where;
    }

    /**
     * 爆款库可见：
     * - 未使用库存(day>今天)：仅 status=4 且 use_time=0（不含 status=6/7）
     * - 当天(day空/等于今天)：实际使用记录 status=4/6 且 use_time 落在当日
     *   （含仿写成功与3点兜底降级文案；不含 status=7 失败占位）
     * - 历史日(day<今天)：沿用旧口径 status=4 按 use_time；status=7 按记录 day
     *
     * @param mixed $query
     */
    private function applyStatusAndTimeFilter($query): void
    {
        if ($this->isQueryUnused()) {
            $query->where('ps.status', '=', SvDeviceViralRecord::STATUS_QUALIFIED)
                ->where('ps.use_time', '=', 0);
            return;
        }

        $day = $this->getDayParam();
        $today = date('Y-m-d');
        $dayStart = strtotime($day . ' 00:00:00');
        $dayEnd = strtotime($day . ' 23:59:59');

        // 当天：与视频合成实际消耗的爆款一致
        if ($day === $today) {
            $query->whereIn('ps.status', [
                    SvDeviceViralRecord::STATUS_QUALIFIED,
                    //SvDeviceViralRecord::STATUS_DEADLINE_FALLBACK,
                    SvDeviceViralRecord::STATUS_FALLBACK_ERROR,
                ])
                ->whereBetween('ps.use_time', [$dayStart, $dayEnd]);
            return;
        }

        // 历史日：保持旧逻辑
        $query->whereIn('ps.status', [
            SvDeviceViralRecord::STATUS_QUALIFIED,
            SvDeviceViralRecord::STATUS_FALLBACK_ERROR,
        ]);
        $query->where(function ($q) use ($day, $dayStart, $dayEnd) {
            $q->where(function ($q4) use ($dayStart, $dayEnd) {
                $q4->where('ps.status', '=', SvDeviceViralRecord::STATUS_QUALIFIED)
                    ->whereBetween('ps.use_time', [$dayStart, $dayEnd]);
            })->whereOr(function ($q7) use ($day) {
                $q7->where('ps.status', '=', SvDeviceViralRecord::STATUS_FALLBACK_ERROR)
                    ->where('ps.day', '=', $day);
            });
        });
    }

    /**
     * 未传 day/date 或等于当天时不展示 source=manual；历史日与未来未使用日仍展示。
     */
    private function shouldIncludeManualImport(): bool
    {
        $day = $this->getRawDayParam();
        $today = date('Y-m-d');
        return $day !== '' && $day !== $today;
    }

    /**
     * @param bool $withAccountType 列表跟随 account_type；tabs 汇总时传 false
     * @param int|null $isInterested 默认取请求参数
     */
    private function buildManualImportQuery(bool $withAccountType = true, ?int $isInterested = null)
    {
        if (!$this->shouldIncludeManualImport()) {
            return SvDeviceViralManualImport::whereRaw('1=0');
        }

        $personaId = $this->getPersonaId();
        $isInterested = $isInterested ?? $this->getInterestedParam();
        $accountType = $this->getAccountType();

        $query = SvDeviceViralManualImport::where('user_id', $this->userId)
            ->where('status', '<>', SvDeviceViralManualImport::STATUS_SUCCESS)
            ->where('is_interested', $isInterested);

        // 未来日未使用库存：不展示解析失败(status=3)
        if ($this->isQueryUnused()) {
            $query->where('status', '<>', SvDeviceViralManualImport::STATUS_FAIL);
        }

        if ($personaId > 0) {
            $query->where('persona_id', $personaId);
        }

        // 与 buildQuery 一致：按记录快照 publish_platform 跟随当前 tab
        if ($withAccountType && $accountType > 0) {
            $query->where('publish_platform', '=', $accountType);
        }

        $keyword = trim((string)$this->request->get('keyword', ''));
        if ($keyword !== '' && $keyword !== self::MANUAL_KEYWORD) {
            $query->whereRaw('1=0');
        }

        return $query;
    }

    private function fetchManualImportRows(): array
    {
        $rows = $this->buildManualImportQuery(true)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->formatManualListItem($row);
        }
        return $result;
    }

    /**
     * 与本页 viral 去重：优先 manual_import_id，旧数据兜底同 content
     */
    private function dedupeManualAgainstViral(array $manualRows, array $viralList): array
    {
        if ($manualRows === [] || $viralList === []) {
            return $manualRows;
        }

        $viralImportIds = [];
        $viralContents = [];
        foreach ($viralList as $item) {
            $importId = (int)($item['manual_import_id'] ?? 0);
            if ($importId > 0) {
                $viralImportIds[$importId] = true;
            }
            $content = trim((string)($item['content'] ?? $item['link'] ?? ''));
            if ($content !== '') {
                $viralContents[$content] = true;
            }
        }

        $kept = [];
        foreach ($manualRows as $row) {
            $importId = (int)($row['manual_import_id'] ?? $row['id'] ?? 0);
            if ($importId > 0 && isset($viralImportIds[$importId])) {
                continue;
            }
            $content = trim((string)($row['content'] ?? $row['link'] ?? ''));
            if ($content !== '' && isset($viralContents[$content])) {
                continue;
            }
            $kept[] = $row;
        }
        return $kept;
    }

    private function formatManualListItem(array $row): array
    {
        $importId = (int)($row['id'] ?? 0);
        $publishPlatform = (int)($row['publish_platform'] ?? 0);
        $publishMediaType = (int)($row['publish_media_type'] ?? 0);
        $shareContent = (string)($row['share_content'] ?? '');
        $shareUrl = (string)($row['share_url'] ?? '');
        $content = $shareContent !== '' ? $shareContent : $shareUrl;
        $importStatus = (int)($row['status'] ?? 0);
        $isInterested = (int)($row['is_interested'] ?? 1);
        $remark = (string)($row['remark'] ?? '');
        $day = (string)($row['scheduled_day'] ?? '');
        $createTime = $row['create_time'] ?? 0;
        $updateTime = $row['update_time'] ?? 0;

        return [
            'id' => $importId,
            'manual_import_id' => $importId,
            'source' => 'manual',
            'is_manual_import' => 1,
            'viral_id' => 0,
            'viral_account_id' => 0,
            'device_code' => '',
            'account' => '',
            'nickname' => '',
            'keyword' => self::MANUAL_KEYWORD,
            'keyword_label' => '词条：' . self::MANUAL_KEYWORD,
            'content' => $content,
            'link' => $content,
            'title_normalized' => (string)($row['title_normalized'] ?? ''),
            'original_text' => '',
            'copywriting' => [],
            'copywriting_type' => 0,
            'day' => $day !== '' ? $day : date('Y-m-d'),
            'likes' => '0',
            'comments' => '0',
            // 兼容爆款库可见 status；真实导入状态见 manual_import_status
            'status' => SvDeviceViralRecord::STATUS_QUALIFIED,
            'remark' => $remark,
            'publish_platform' => $publishPlatform,
            'platform' => $publishPlatform,
            'account_type' => $publishPlatform,
            'platform_name' => DeviceEnum::getAccountTypeDesc($publishPlatform),
            'publish_media_type' => $publishMediaType,
            'publish_media_type_text' => $publishMediaType === 2 ? '图文' : ($publishMediaType === 1 ? '视频' : ''),
            'image' => '',
            'original_images' => [],
            'rewritten_images' => [],
            'image_rewrite_status' => 0,
            'image_rewrite_status_text' => '无需改写',
            'image_rewrite_task_id' => '',
            'image_rewrite_started_at' => 0,
            'image_rewrite_retry_count' => 0,
            'publish_detail_id' => 0,
            'publish_create_error' => '',
            'is_interested' => $isInterested,
            'use_time' => 0,
            'use_time_text' => '',
            'persona_id' => (int)($row['persona_id'] ?? 0),
            'auto_type' => 0,
            'source_text' => '手动导入',
            'title' => self::MANUAL_KEYWORD,
            'rewritten_text' => '',
            'create_time' => $createTime,
            'update_time' => $updateTime,
            'manual_import_status' => $importStatus,
            'manual_import_status_text' => $this->getManualImportStatusText($importStatus),
        ];
    }

    private function formatViralListItem(mixed $item): array
    {
        if (!is_array($item)) {
            $item = (is_object($item) && method_exists($item, 'toArray'))
                ? $item->toArray()
                : (array)$item;
        }

        $copywriting = $this->normalizeCopywriting($item['copywriting'] ?? []);
        $publishPlatform = (int)($item['publish_platform'] ?? 0);
        $publishMediaType = (int)($item['publish_media_type'] ?? 1);
        $autoType = (int)($item['auto_type'] ?? 1);
        $manualImportId = (int)($item['manual_import_id'] ?? 0);

        $item['account_type'] = $publishPlatform;
        $item['platform'] = $publishPlatform;
        $item['platform_name'] = DeviceEnum::getAccountTypeDesc($publishPlatform);
        $item['publish_platform'] = $publishPlatform;
        $item['publish_media_type'] = $publishMediaType;
        $item['publish_media_type_text'] = $publishMediaType === 2 ? '图文' : '视频';
        $item['link'] = $item['content'] ?? '';
        $item['copywriting'] = $copywriting;
        $item['original_text'] = (string)($item['original_text'] ?? '');
        $item['title'] = $copywriting['title'] ?? ($item['keyword'] ?? '');
        $item['rewritten_text'] = $copywriting['rewritten_text'] ?? '';
        $item['title_normalized'] = (string)($item['title_normalized'] ?? '');
        $keyword = trim((string)($item['keyword'] ?? ''));
        $item['keyword_label'] = $keyword !== '' ? ('词条：' . $keyword) : '';
        $item['likes'] = (string)($item['likes'] ?? '0');
        $item['comments'] = (string)($item['comments'] ?? '0');
        $item['is_interested'] = (int)($item['is_interested'] ?? 1);
        $item['use_time'] = (int)($item['use_time'] ?? 0);
        $item['use_time_text'] = $item['use_time'] > 0
            ? date('Y-m-d H:i:s', $item['use_time'])
            : '';
        $item['persona_id'] = (int)($item['persona_id'] ?? 0);
        $item['original_images'] = $this->normalizeImages($item['original_images'] ?? []);
        $item['rewritten_images'] = $this->normalizeImages($item['rewritten_images'] ?? []);
        $item['image_rewrite_status'] = (int)($item['image_rewrite_status'] ?? 0);
        $item['image_rewrite_status_text'] = $this->getImageRewriteStatusText(
            $item['image_rewrite_status'],
            (int)($item['image_rewrite_retry_count'] ?? 0)
        );
        $item['publish_detail_id'] = (int)($item['publish_detail_id'] ?? 0);
        $item['publish_create_error'] = (string)($item['publish_create_error'] ?? '');
        $item['auto_type'] = $autoType;
        $item['source'] = 'auto';
        $item['source_text'] = $autoType === 0 ? '手动导入' : '自动抓取';
        $item['manual_import_id'] = $manualImportId;
        $item['is_manual_import'] = $manualImportId > 0 ? 1 : 0;

        return $item;
    }

    private function getManualImportStatusText(int $status): string
    {
        return [
            SvDeviceViralManualImport::STATUS_PENDING => '排队中',
            SvDeviceViralManualImport::STATUS_PROCESSING => '解析中',
            SvDeviceViralManualImport::STATUS_FAIL => '解析失败',
            SvDeviceViralManualImport::STATUS_PARTIAL => '部分成功',
        ][$status] ?? '未知';
    }

    private function listFields(): string
    {
        return implode(',', [
            'ps.id',
            'ps.viral_id',
            'ps.viral_account_id',
            'ps.manual_import_id',
            'ps.device_code',
            'ps.account',
            'ps.nickname',
            'ps.keyword',
            'ps.content',
            'ps.title_normalized',
            'ps.original_text',
            'ps.copywriting',
            'ps.copywriting_type',
            'ps.day',
            'ps.likes',
            'ps.comments',
            'ps.status',
            'ps.remark',
            'ps.publish_platform',
            'ps.publish_media_type',
            'ps.image',
            'ps.original_images',
            'ps.rewritten_images',
            'ps.image_rewrite_status',
            'ps.image_rewrite_task_id',
            'ps.image_rewrite_started_at',
            'ps.image_rewrite_retry_count',
            'ps.publish_detail_id',
            'ps.publish_create_error',
            'ps.is_interested',
            'ps.use_time',
            'ps.auto_type',
            'ps.create_time',
            'ps.update_time',
            'IFNULL(ps.publish_platform, 0) as account_type',
            'IFNULL(NULLIF(ps.persona_id, 0), IFNULL(va.persona_id, 0)) as persona_id',
        ]);
    }

    private function getInterestedParam(): int
    {
        return (string)$this->request->get('is_interested', 1) === '0' ? 0 : 1;
    }

    private function getAccountType(): int
    {
        $accountType = (int)$this->request->get('account_type', 0);
        return in_array($accountType, $this->platforms, true) ? $accountType : 0;
    }

    private function getPersonaId(): int
    {
        return max(0, (int)$this->request->get('persona_id', 0));
    }

    /**
     * 原始 day 参数(空表示未传)
     */
    private function getRawDayParam(): string
    {
        $day = trim((string)$this->request->get('day', ''));
        if ($day === '') {
            $day = trim((string)$this->request->get('date', ''));
        }
        return $day;
    }

    /**
     * 是否按「未使用」查询:传了大于当天的日期
     */
    private function isQueryUnused(): bool
    {
        $day = $this->getRawDayParam();
        if ($day === '') {
            return false;
        }
        return $day > date('Y-m-d');
    }

    /**
     * 列表使用日期:未传则当天;未来日期仅作「未使用」开关,使用日查询用当天或传入的过去/当天
     */
    private function getDayParam(): string
    {
        $day = $this->getRawDayParam();
        $today = date('Y-m-d');
        if ($day === '' || $day > $today) {
            return $today;
        }
        return $day;
    }

    private function getKeywordList(): array
    {
        // 关键词列表跟随 account_type：选小红书时只返回该平台下的关键词
        $keywords = $this->buildQuery($this->getInterestedParam(), true, false)
            ->whereNotNull('ps.keyword')
            ->where('ps.keyword', '<>', '')
            ->group('ps.keyword')
            ->order('ps.keyword', 'asc')
            ->column('ps.keyword');

        if ($this->buildManualImportQuery(true)->count() > 0) {
            if (!in_array(self::MANUAL_KEYWORD, $keywords, true)) {
                $keywords[] = self::MANUAL_KEYWORD;
                sort($keywords);
            }
        }

        return $keywords;
    }

    private function normalizeCopywriting(mixed $copywriting): array
    {
        while (is_string($copywriting) && $copywriting !== '') {
            $decoded = json_decode($copywriting, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['rewritten_text' => $copywriting];
            }
            $copywriting = $decoded;
        }

        return is_array($copywriting) ? $copywriting : [];
    }

    private function normalizeImages(mixed $images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : preg_split('/[,，\s]+/', $images);
        }
        if (!is_array($images)) {
            return [];
        }

        $result = [];
        foreach ($images as $image) {
            if (is_array($image)) {
                $image = $image['url'] ?? $image['src'] ?? $image['path'] ?? '';
            }
            $image = trim((string)$image);
            if ($image !== '') {
                $result[] = FileService::getFileUrl($image);
            }
        }

        return array_values(array_unique($result));
    }

    private function getImageRewriteStatusText(int $status, int $retryCount = 0): string
    {
        if ($status === SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT && $retryCount > 0) {
            return '超时重试中';
        }

        return [
            0 => '无需改写',
            1 => '排队中',
            2 => '改写处理中',
            3 => '成功',
            4 => '失败',
        ][$status] ?? '未知';
    }
}
