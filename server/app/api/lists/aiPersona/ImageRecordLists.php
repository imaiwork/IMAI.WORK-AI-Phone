<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\service\FileService;

/**
 * 人设内容记录 - 图片仿写记录列表
 * 对应前端「自动生成的图片」
 */
class ImageRecordLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private array $platforms = [
        DeviceEnum::ACCOUNT_TYPE_DY,
        DeviceEnum::ACCOUNT_TYPE_XHS,
        DeviceEnum::ACCOUNT_TYPE_KS,
        DeviceEnum::ACCOUNT_TYPE_SPH,
    ];

    public function setSearch(): array
    {
        return [
            '=' => ['ps.image_rewrite_status'],
        ];
    }

    public function lists(): array
    {
        return $this->buildQuery()
            ->field($this->listFields())
            ->order(['ps.day' => 'desc', 'ps.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $copywriting = $this->normalizeCopywriting($item['copywriting'] ?? []);
                $publishPlatform = (int)($item['publish_platform'] ?? 0) ?: (int)($item['account_type'] ?? 0);
                $imageRewriteStatus = (int)($item['image_rewrite_status'] ?? 0);
                $rewrittenImages = $this->normalizeImages($item['rewritten_images'] ?? []);
                $originalImages = $this->normalizeImages($item['original_images'] ?? []);

                $item['publish_platform'] = $publishPlatform;
                $item['platform'] = $publishPlatform;
                $item['platform_name'] = DeviceEnum::getAccountTypeDesc($publishPlatform);
                $item['publish_media_type'] = AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
                $item['publish_media_type_text'] = '图文';
                $item['copywriting'] = $copywriting;
                $item['title'] = (string)($copywriting['title'] ?? ($item['keyword'] ?? ''));
                $item['content'] = (string)($copywriting['rewritten_text'] ?? $copywriting['content'] ?? $copywriting['text'] ?? '');
                $item['rewritten_text'] = $item['content'];
                $item['original_images'] = $originalImages;
                $item['rewritten_images'] = $rewrittenImages;
                // 列表展示图：改写成功优先用改写图，否则回退原图
                $item['images'] = !empty($rewrittenImages) ? $rewrittenImages : $originalImages;
                $item['image_rewrite_status'] = $imageRewriteStatus;
                $item['image_rewrite_status_text'] = $this->getImageRewriteStatusText(
                    $imageRewriteStatus,
                    (int)($item['image_rewrite_retry_count'] ?? 0)
                );
                $item['persona_id'] = (int)($item['persona_id'] ?? 0);
                $item['publish_detail_id'] = (int)($item['publish_detail_id'] ?? 0);
                $item['publish_create_error'] = (string)($item['publish_create_error'] ?? '');
                $item['status'] = (int)($item['status'] ?? 0);

                return $item;
            })
            ->toArray();
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    public function extend(): array
    {
        return [
            'tabs' => $this->buildPlatformTabs(),
        ];
    }

    /**
     * 平台 tabs 统计（不受 publish_platform 筛选影响）
     */
    private function buildPlatformTabs(): array
    {
        $rows = $this->buildQuery(false)
            ->field('IFNULL(NULLIF(ps.publish_platform, 0), IFNULL(va.account_type, 0)) as account_type, COUNT(ps.id) as total')
            ->group('account_type')
            ->select()
            ->toArray();

        $counts = [];
        $all = 0;
        foreach ($rows as $row) {
            $accountType = (int)($row['account_type'] ?? 0);
            $total = (int)($row['total'] ?? 0);
            $counts[$accountType] = $total;
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

    private function buildQuery(bool $withPlatform = true)
    {
        $personaId = $this->getPersonaId();
        $platform = $this->getPublishPlatform();

        return SvDeviceViralRecord::alias('ps')
            ->join('sv_device_viral_account va', 'va.id = ps.viral_account_id', 'left')
            ->where('ps.user_id', '=', $this->userId)
            ->where('ps.publish_media_type', '=', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
            ->where($this->searchWhere)
            ->when($personaId > 0, function ($query) use ($personaId) {
                $query->where(function ($query) use ($personaId) {
                    $query->where('ps.persona_id', '=', $personaId)
                        ->whereOr('va.persona_id', '=', $personaId);
                });
            })
            ->when($withPlatform && $platform > 0, function ($query) use ($platform) {
                $query->where(function ($query) use ($platform) {
                    $query->where('ps.publish_platform', '=', $platform)
                        ->whereOr('va.account_type', '=', $platform);
                });
            });
    }

    private function listFields(): string
    {
        return implode(',', [
            'ps.id',
            'ps.viral_id',
            'ps.viral_account_id',
            'ps.device_code',
            'ps.account',
            'ps.nickname',
            'ps.keyword',
            'ps.content',
            'ps.original_text',
            'ps.copywriting',
            'ps.copywriting_type',
            'ps.day',
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
            'ps.image_rewrite_success_count',
            'ps.image_rewrite_fail_count',
            'ps.publish_detail_id',
            'ps.publish_create_error',
            'ps.create_time',
            'ps.update_time',
            'IFNULL(NULLIF(ps.publish_platform, 0), IFNULL(va.account_type, 0)) as account_type',
            'IFNULL(NULLIF(ps.persona_id, 0), IFNULL(va.persona_id, 0)) as persona_id',
        ]);
    }

    private function getPersonaId(): int
    {
        return max(0, (int)$this->request->get('persona_id', 0));
    }

    private function getPublishPlatform(): int
    {
        // 兼容 account_type / publish_platform
        $platform = (int)$this->request->get('publish_platform', 0);
        if ($platform <= 0) {
            $platform = (int)$this->request->get('account_type', 0);
        }
        return in_array($platform, $this->platforms, true) ? $platform : 0;
    }

    private function normalizeCopywriting($copywriting): array
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

    private function normalizeImages($images): array
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
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_NONE => '无需改写',
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT => '排队中',
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING => '改写处理中',
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS => '成功',
            SvDeviceViralRecord::IMAGE_REWRITE_STATUS_FAIL => '失败',
        ][$status] ?? '未知';
    }
}
