<?php

namespace app\common\service\chat;

use app\common\enum\ChatEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\FileService;
use app\common\service\MemberService;
use Exception;

class ChatModelsService
{
    /**
     * 普通智能体不可选的模型别名。
     * 这些模型仍可保留在模型库中供专用业务调用。
     */
    private const AGENT_EXCLUDED_MODEL_ALIASES = [
        'glm-4v-flash',
        'autoglm-phone',
    ];

    public static function getAgentExcludedModelAliases(): array
    {
        return self::AGENT_EXCLUDED_MODEL_ALIASES;
    }

    public static function isAgentModelSelectable(string $alias): bool
    {
        return !in_array(
            strtolower(trim($alias)),
            self::AGENT_EXCLUDED_MODEL_ALIASES,
            true
        );
    }

    /**
     * @throws Exception
     */
    public static function assertAgentModelSelectable(string $alias): void
    {
        if (!self::isAgentModelSelectable($alias)) {
            throw new Exception('该模型不能用于智能体');
        }
    }

    private const TOKENS_DISPLAY_BASE = 1000;

    /** 计费类型：按 token */
    public const QUOTA_TYPE_TOKEN = 0;
    /** 计费类型：按量（次/张/秒，由 media_type 细分） */
    public const QUOTA_TYPE_TIMES = 1;

    /** 展示单位：按 tokens */
    public const BILL_UNIT_TOKEN = 'token';
    /** 展示单位：按次 */
    public const BILL_UNIT_TIMES = 'times';
    /** 展示单位：按张（生图） */
    public const BILL_UNIT_SHEET = 'sheet';
    /** 展示单位：按秒（生视频） */
    public const BILL_UNIT_SECOND = 'second';

    /**
     * 根据中台 quota_type + 媒体类型解析计费单位
     * - quota_type=0 → tokens
     * - quota_type=1 + image → 张
     * - quota_type=1 + video → 秒
     * - quota_type=1 + 其它 → 次
     */
    public static function resolveBillUnit(int $quotaType, string $mediaType = '', string $alias = ''): string
    {
        if ($quotaType !== self::QUOTA_TYPE_TIMES) {
            return self::BILL_UNIT_TOKEN;
        }

        $media = strtolower(trim($mediaType));
        $aliasLower = strtolower(trim($alias));

        if (
            $media === 'video'
            || str_contains($media, 'video')
            || str_contains($aliasLower, 'seedance')
            || str_contains($aliasLower, 'video')
        ) {
            return self::BILL_UNIT_SECOND;
        }

        if (
            $media === 'image'
            || str_contains($media, 'image')
            || str_contains($media, 'img')
            || str_contains($aliasLower, 'seedream')
            || str_contains($aliasLower, 'gpt-image')
            || str_contains($aliasLower, 'image')
        ) {
            return self::BILL_UNIT_SHEET;
        }

        return self::BILL_UNIT_TIMES;
    }

    /**
     * 价格单位文案（对齐中台 price_desc 口径，并区分 次/张/秒）
     * @param int $quotaType 0=按 token，1=按次/张/秒
     */
    public static function priceDesc(
        float|string|null $price,
        int $quotaType = self::QUOTA_TYPE_TOKEN,
        string $mediaType = '',
        string $alias = ''
    ): string {
        $price = (float)$price;
        $unit = self::resolveBillUnit($quotaType, $mediaType, $alias);

        $suffixMap = [
            self::BILL_UNIT_SHEET => '张',
            self::BILL_UNIT_SECOND => '秒',
            self::BILL_UNIT_TIMES => '次',
        ];
        if (isset($suffixMap[$unit])) {
            $suffix = $suffixMap[$unit];
            if ($price <= 0) {
                return '0算力/' . $suffix;
            }
            $formatted = rtrim(rtrim(number_format($price, 4, '.', ''), '0'), '.');
            return $formatted . '算力/' . $suffix;
        }

        if ($price <= 0) {
            return '0算力/1000 tokens';
        }

        $tokens = (int)round(self::TOKENS_DISPLAY_BASE / $price);
        $tokens = max($tokens, 1);
        return '约1算力/' . $tokens . ' tokens';
    }

    public static function quotaTypeDesc(int $quotaType, string $mediaType = '', string $alias = ''): string
    {
        return match (self::resolveBillUnit($quotaType, $mediaType, $alias)) {
            self::BILL_UNIT_SHEET => '按张',
            self::BILL_UNIT_SECOND => '按秒',
            self::BILL_UNIT_TIMES => '按次',
            default => '按tokens',
        };
    }

    /** 输入框旁单位短文案：算力/张 | 算力/秒 | 算力/次 | 算力 */
    public static function priceUnitLabel(int $quotaType, string $mediaType = '', string $alias = ''): string
    {
        return match (self::resolveBillUnit($quotaType, $mediaType, $alias)) {
            self::BILL_UNIT_SHEET => '算力/张',
            self::BILL_UNIT_SECOND => '算力/秒',
            self::BILL_UNIT_TIMES => '算力/次',
            default => '算力',
        };
    }

    public static function getChannelList(bool $filterDisabled = true): array
    {
        $lists = (new Models())
            ->alias('m')
            ->join('models_cost c', 'c.model_id = m.id')
            ->field([
                'm.id' => 'model_id',
                'm.name',
                'm.logo',
                'm.is_enable',
                'c.id' => 'model_sub_id',
                'c.status',
            ])
            ->where('m.type', ChatEnum::MODEL_TYPE_CHAT)
            ->where('c.alias', 'not in', self::getAgentExcludedModelAliases())
            ->whereNull('m.delete_time')
            ->order('m.sort asc, c.sort asc, c.id asc')
            ->select()
            ->toArray();

        $channel = [];
        foreach ($lists as $item) {
            $isEnabled = (int)$item['is_enable'] === 1 && (int)$item['status'] === 1;
            if ($filterDisabled && !$isEnabled) {
                continue;
            }

            $channel[] = [
                'id'           => (string)$item['model_sub_id'],
                'name'         => $item['name'],
                'model_id'     => (int)$item['model_id'],
                'model_sub_id' => (int)$item['model_sub_id'],
                'status'       => (string)(int)$isEnabled,
                'logo'         => FileService::getFileUrl($item['logo'] ?? ''),
            ];
        }

        return ['channel' => $channel];
    }

    /**
     * 断言对话主/子模型可用（启用态 + 可选会员白名单）
     * @throws Exception
     */
    public static function assertChatModelUsable(int $modelId = 0, int $modelSubId = 0, ?int $userId = null, string $modelAlias = ''): void
    {
        $subModel = null;
        $mainModel = null;

        if ($modelSubId > 0) {
            $subModel = (new ModelsCost())
                ->where(['id' => $modelSubId, 'type' => ChatEnum::MODEL_TYPE_CHAT])
                ->findOrEmpty();
            if ($subModel->isEmpty()) {
                throw new Exception($modelSubId ? '对话模型可能已被下架了' : '请配置机器人对话模型');
            }
            if (!(int)$subModel['status']) {
                throw new Exception('对话模型已被下架了');
            }
            $modelId = $modelId > 0 ? $modelId : (int)$subModel['model_id'];
            if ($modelId > 0 && (int)$subModel['model_id'] !== $modelId) {
                throw new Exception('模型匹配关系异常');
            }
            $modelId = (int)$subModel['model_id'];
        } elseif ($modelId > 0) {
            $subModel = (new ModelsCost())
                ->where(['model_id' => $modelId, 'type' => ChatEnum::MODEL_TYPE_CHAT, 'status' => 1])
                ->order('sort asc, id desc')
                ->findOrEmpty();
            if ($subModel->isEmpty()) {
                throw new Exception('对话模型已被下架了');
            }
        } elseif ($modelAlias !== '') {
            $subModel = (new ModelsCost())
                ->where(['type' => ChatEnum::MODEL_TYPE_CHAT, 'status' => 1])
                ->where(function ($query) use ($modelAlias) {
                    $query->where('alias', $modelAlias)->whereOr('name', $modelAlias);
                })
                ->order('id desc')
                ->findOrEmpty();
            if ($subModel->isEmpty()) {
                throw new Exception('对话模型已被下架了');
            }
            $modelId = (int)$subModel['model_id'];
            $modelSubId = (int)$subModel['id'];
        } else {
            return;
        }

        $mainModel = (new Models())
            ->where(['id' => $modelId, 'type' => ChatEnum::MODEL_TYPE_CHAT])
            ->findOrEmpty();
        if ($mainModel->isEmpty()) {
            throw new Exception('主模型已被下架!');
        }
        if (!(int)$mainModel['is_enable']) {
            throw new Exception('主模型已被下架!');
        }

        if ($userId !== null && $userId > 0 && !MemberService::canUseModel($userId, $modelId)) {
            $modelLabel = $mainModel['name'] ?: (string)$modelId;
            throw new Exception('当前会员等级不支持模型 ' . $modelLabel . ',请升级会员');
        }
    }
}
