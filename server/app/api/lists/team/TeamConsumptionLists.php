<?php

namespace app\api\lists\team;

use app\api\lists\BaseApiDataLists;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsExtendInterface;
use app\common\model\team\TeamMember;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
/**
 * 企业算力消耗明细(全员合集,团队主/管理员查看)
 */
class TeamConsumptionLists extends BaseApiDataLists implements ListsExtendInterface
{
    /**
     * 非业务消耗(转账类):划拨/回收/OEM/制卡等
     * 单一口径来源 AccountLogEnum::teamTransferTypes(),与成员「累计消耗」保持一致
     */
    public static function transferChangeTypes(): array
    {
        return AccountLogEnum::teamTransferTypes();
    }

    /** 团队资金往来类 INC(移出退回/划拨入账/消费失败退回等),需出现在明细列表 */
    public static function teamTransferIncTypes(): array
    {
        return [
            AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE,
            AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
            AccountLogEnum::TOKENS_INC_TEAM_CONSUME_REFUND,
            AccountLogEnum::TOKENS_INC_OEM_UPGRADE_REFUND,
            AccountLogEnum::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
        ];
    }

    /**
     * 业务类型筛选映射:biz_key => [label, 精确change_type[], 区间[[min,max],...]]
     * 与 bizType() 的归类保持一致;分 tab 提供给前端下拉
     */
    public static function bizFilterMap(string $listType): array
    {
        if ($listType === 'transfer') {
            return [
                'team_allocate' => ['算力划拨', [
                    AccountLogEnum::TOKENS_DEC_TEAM_ALLOCATE,
                    AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE,
                ], []],
                'team_allocate_refund' => ['算力退回', [
                    AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
                ], []],
                'team_settle' => ['团队结算', [
                    AccountLogEnum::TOKENS_DEC_TEAM_CONSUME,
                    AccountLogEnum::TOKENS_INC_TEAM_CONSUME_REFUND,
                ], []],
                'card_make' => ['卡密制卡', [
                    AccountLogEnum::TOKENS_DEC_DISTRIBUTION_CARD,
                    AccountLogEnum::TOKENS_INC_DISTRIBUTION_CARD_REFUND,
                ], []],
                'oem' => ['OEM升级', [
                    AccountLogEnum::TOKENS_DEC_OEM_UPGRADE,
                    AccountLogEnum::TOKENS_INC_OEM_UPGRADE_REFUND,
                ], []],
            ];
        }
        return [
            'chat' => ['AI对话', [], [[1001, 1999]]],
            'ai_image' => ['AI生图', [10601, 2030], [[2001, 2007], [2017, 2029]]],
            'ai_video' => ['AI视频', [10602, 10300], [[10106, 10113], [2008, 2016]]],
            'human' => ['数字人', [
                AccountLogEnum::TOKENS_INC_HUMAN,
                AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE1,
                AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE2,
                AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE3,
                AccountLogEnum::TOKENS_INC_SHANJIAN_TYPE4,
            ], [[5001, 5999]]],
            'voice' => ['AI语音', [], [[10400, 10403]]],
            'kb' => ['知识库', [9004, 9005, 9006], []],
            'xhs_picture' => ['小红书图文', [
                AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE_TITLE,
                AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE,
                AccountLogEnum::TOKENS_DEC_AI_XHS,
                AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
            ], []],
            'copywriting' => ['AI文案', [
                AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_TITLE,
                AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING,
                AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING_SENIOR,
                AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION,
                AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_ADD,
                AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
                AccountLogEnum::TOKENS_INC_VIDEO_IMITATION_REFUND,
                AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS,
                AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS,
            ], []],
            'sph' => ['视频号获客', [
                AccountLogEnum::TOKENS_DEC_SPH_ADD_WECHAT,
                AccountLogEnum::TOKENS_DEC_SPH_ADD_FRIENDS,
                AccountLogEnum::TOKENS_DEC_SPH_PRIVATE_CHAT,
                AccountLogEnum::TOKENS_DEC_SPH_SEARCH_TERMS,
                AccountLogEnum::TOKENS_DEC_SPH_OCR,
                AccountLogEnum::TOKENS_DEC_SPH_LOCAL_OCR,
            ], []],
            'map_lead' => ['地图获客', [10320, 2032], []],
            'social' => ['社媒获客', [], [[10301, 10399]]],
            'meeting' => ['会议纪要', [], [[3001, 3999]]],
            'mind' => ['思维导图', [], [[4001, 4999]]],
            'hotspot' => ['热点追踪', [
                AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_DAY,
                AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_WORDS,
                AccountLogEnum::TOKENS_DEC_HOTSPOT_INSIGHT,
                AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_CHAT,
                AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_SEARCH,
            ], []],
        ];
    }

    /** 前端下拉选项 */
    public static function bizOptions(string $listType): array
    {
        $out = [];
        foreach (self::bizFilterMap($listType) as $key => [$label]) {
            $out[] = ['key' => $key, 'label' => $label];
        }
        return $out;
    }

    /** 业务大类映射:按 change_type 段归类 */
    public static function bizType(int $ct): array
    {
        // [类型key, 类型名, 产出类型 chat|image|video|none]
        // 团队算力划拨/回收(非AI消耗,无产出)
        if ($ct === AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND) {
            return ['team_allocate_refund', '算力退回', 'none'];
        }
        if (in_array($ct, [
            AccountLogEnum::TOKENS_DEC_TEAM_ALLOCATE,
            AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE,
        ], true)) {
            return ['team_allocate', '算力划拨', 'none'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_TEAM_CONSUME
            || $ct === AccountLogEnum::TOKENS_INC_TEAM_CONSUME_REFUND) {
            return ['team_settle', '团队结算', 'none'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_DISTRIBUTION_CARD
            || $ct === AccountLogEnum::TOKENS_INC_DISTRIBUTION_CARD_REFUND) {
            return ['card_make', '卡密制卡', 'none'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_OEM_UPGRADE
            || $ct === AccountLogEnum::TOKENS_INC_OEM_UPGRADE_REFUND) {
            return ['oem', 'OEM升级', 'none'];
        }
        if ($ct >= 1001 && $ct <= 1999) return ['chat', 'AI对话', 'chat'];
        // draw 统一链路(新):AI生图 / AI生视频 / PPT(逐页图)
        if ($ct === 10601) return ['ai_image', 'AI生图', 'image'];
        if ($ct === 10602) return ['ai_video', 'AI生视频', 'video'];
        // 视频类(各自任务表存 video_result_url,按 task_id 关联)
        if ($ct >= 10106 && $ct <= 10113) return ['sora', 'AI视频', 'video'];
        if ($ct === 10300) return ['storyboard', '分镜混剪', 'video'];
        // 语音/音色(human_voice)
        if ($ct >= 10400 && $ct <= 10403) return ['voice', 'AI语音', 'audio'];
        // 地图获客(map_lead_message 文本+线索)
        if ($ct === 10320 || $ct === 2032) return ['map_lead', '地图获客', 'text'];
        // 知识库
        if ($ct === 9006) return ['kb_chat', '知识库问答', 'chat'];
        if ($ct === 9004) return ['kb_retrieve', '知识库检索', 'text'];
        if ($ct === 9005) return ['kb_create', '知识库创建', 'none'];
        // 102xx:小红书图文/文案/仿写等(勿整段标成「视频号获客」)
        if ($ct === AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE_TITLE
            || $ct === AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE
            || $ct === AccountLogEnum::TOKENS_DEC_AI_XHS) {
            return ['xhs_picture', '小红书图文', 'image'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_TITLE) {
            return ['news_mixcut', '新闻体标题', 'text'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING
            || $ct === AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING_SENIOR) {
            return ['coze_copy', 'Coze文案', 'text'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_DOUYIN_JS) {
            return ['douyin_publish', '抖音发布', 'none'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION
            || $ct === AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_ADD
            || $ct === AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE
            || $ct === AccountLogEnum::TOKENS_INC_VIDEO_IMITATION_REFUND) {
            return ['imitation_text', '爆款文案仿写', 'text'];
        }
        // 数字人/混剪专用退费 INC(9100/915x),不在 5001-5999 段内
        if (in_array($ct, AccountLogEnum::teamBizRefundIncTypes(), true)
            && $ct !== AccountLogEnum::TOKENS_INC_VIDEO_IMITATION_REFUND) {
            return ['human', '数字人', 'video'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS) {
            return ['hot_words', '热点搜词', 'text'];
        }
        if ($ct === AccountLogEnum::TOKENS_DEC_EXTRACT_KEYWORDS) {
            return ['extract_keywords', '关键词提取', 'text'];
        }
        if (in_array($ct, [
            AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_DAY,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_HOT_WORDS,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_INSIGHT,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_CHAT,
            AccountLogEnum::TOKENS_DEC_HOTSPOT_ARK_SEARCH,
        ], true)) {
            return ['hotspot', '热点追踪', 'text'];
        }
        // 图文爆款仿写(勿落入下方社媒获客段)
        if ($ct === AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE) {
            return ['xhs_rewrite', '图文爆款仿写', 'image'];
        }
        // 视频号获客(真实 change_type,非 102xx)
        if (in_array($ct, [
            AccountLogEnum::TOKENS_DEC_SPH_ADD_WECHAT,
            AccountLogEnum::TOKENS_DEC_SPH_ADD_FRIENDS,
            AccountLogEnum::TOKENS_DEC_SPH_PRIVATE_CHAT,
            AccountLogEnum::TOKENS_DEC_SPH_SEARCH_TERMS,
            AccountLogEnum::TOKENS_DEC_SPH_OCR,
            AccountLogEnum::TOKENS_DEC_SPH_LOCAL_OCR,
        ], true)) {
            return ['sph', '视频号获客', 'none'];
        }
        if (in_array($ct, [2030])) return ['ai_draw', 'AI作图', 'image'];
        if ($ct >= 2008 && $ct <= 2016) return ['video', 'AI视频', 'video'];
        if ($ct >= 2001 && $ct <= 2029) return ['draw', 'AI绘画', 'image'];
        if ($ct >= 3001 && $ct <= 3999) return ['meeting', '会议纪要', 'text'];
        if ($ct >= 4001 && $ct <= 4999) return ['mind', '思维导图', 'text'];
        if ($ct >= 5001 && $ct <= 5999) return ['human', '数字人', 'video'];
        if ($ct >= 10301 && $ct <= 10399) return ['social', '社媒获客', 'none'];
        return ['other', '其他消耗', 'none'];
    }

    /** 当前操作者管理的企业成员 user_id */
    private function memberIds(): array
    {
        $owner = User::findOrEmpty($this->userId);
        if ($owner->isEmpty() || !in_array((int)$owner->team_role, [2, 3])) {
            return [];
        }
        $ids = TeamMember::where('team_id', $owner->team_id)->column('user_id');
        // 含散客归属
        $attr = User::where('team_id', $owner->team_id)->where('team_role', 0)->column('id');
        return array_values(array_unique(array_merge($ids, $attr)));
    }

    /** 时间参数 → unix 秒:兼容数字时间戳与日期字符串两种传法 */
    private static function toTimestamp($value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return (int)strtotime((string)$value);
    }

    /** 快捷时间范围 → 起始时间戳(含当日 00:00) */
    private function rangeStart(): int
    {
        $range = trim((string)($this->params['range'] ?? ''));
        $today = strtotime(date('Y-m-d 00:00:00'));
        return match ($range) {
            'today' => $today,
            '7d' => strtotime('-6 days', $today),
            '30d' => strtotime('-29 days', $today),
            'month' => strtotime(date('Y-m-01 00:00:00')),
            default => 0,
        };
    }

    /** 当前列表类型:consume=消耗列表(默认) / transfer=算力流转(划拨/回收/制卡/OEM等) */
    private function listType(): string
    {
        return trim((string)($this->params['list_type'] ?? '')) === 'transfer' ? 'transfer' : 'consume';
    }

    private function baseQuery()
    {
        $ids = $this->memberIds();
        if (!$ids) {
            return UserTokensLog::where('id', 0);
        }
        // 仅本企业空间内的流水(team_id=当前企业)
        $teamId = (int)User::where('id', $this->userId)->value('team_id');
        $transfer = self::transferChangeTypes();
        // status:1=正常扣费;2=失败/超额退回(recordUserTokensLog 失败退费),个人积分明细会展示,消耗明细须同口径计入
        $q = UserTokensLog::whereIn('user_id', $ids)
            ->where('team_id', $teamId)
            ->whereIn('status', [1, 2]);
        // 后台平台调账(增减算力)不属于企业业务明细,强制排除(含历史误挂 team_id 的脏数据)
        $adminTypes = [
            AccountLogEnum::TOKENS_DEC_ADMIN,
            AccountLogEnum::TOKENS_INC_ADMIN,
        ];
        $q->whereNotIn('change_type', $adminTypes);
        if ($this->listType() === 'transfer') {
            // 算力流转:划拨/回收/退回/制卡/OEM 等转账类(INC+DEC 全量)
            $q->whereIn('change_type', array_values(array_diff($transfer, $adminTypes)));
        } else {
            // 消耗列表:业务 DEC + 业务退回 INC(同 DEC 码退回 + 9100/915x 专用退费)
            $bizIncTypes = AccountLogEnum::teamConsumeIncTypes();
            $q->where(function ($w) use ($transfer, $bizIncTypes) {
                $w->where(function ($dec) use ($transfer) {
                    $dec->where('action', AccountLogEnum::DEC)
                        ->whereNotIn('change_type', $transfer);
                })->whereOr(function ($inc) use ($bizIncTypes) {
                    $inc->where('action', AccountLogEnum::INC)
                        ->whereIn('change_type', $bizIncTypes);
                });
            });
        }
        // 业务类型筛选
        $biz = trim((string)($this->params['biz'] ?? ''));
        $bizMap = self::bizFilterMap($this->listType());
        if ($biz !== '' && isset($bizMap[$biz])) {
            [, $types, $ranges] = $bizMap[$biz];
            $q->where(function ($w) use ($types, $ranges) {
                $applied = false;
                if ($types) {
                    $w->whereIn('change_type', $types);
                    $applied = true;
                }
                foreach ($ranges as $r) {
                    if ($applied) {
                        $w->whereOr(function ($x) use ($r) {
                            $x->whereBetween('change_type', $r);
                        });
                    } else {
                        $w->whereBetween('change_type', $r);
                        $applied = true;
                    }
                }
                if (!$applied) {
                    $w->where('id', 0);
                }
            });
        }
        if (!empty($this->params['user_id'])) {
            // 组合筛选:只看该成员本人流水。
            // 划拨扣减记在创始人钱包时,额外按 operator_id 命中「管理员代操作」;
            // 不再按 target_user_id/备注扩到对方,否则选成员 A 会混进创始人扣减行。
            $uid = (int)$this->params['user_id'];
            $this->applyMemberScope($q, [$uid]);
        }
        // 快捷时间:today|7d|30d|month；也可传 start_time/end_time(日期字符串或 unix 秒,
        // 注意 ListsValidate 全局校验 date 规则,前端应传日期字符串)
        $rangeStart = $this->rangeStart();
        if ($rangeStart > 0) {
            $q->where('create_time', '>=', $rangeStart);
        } elseif (!empty($this->params['start_time'])) {
            $q->where('create_time', '>=', self::toTimestamp($this->params['start_time']));
        }
        if (!empty($this->params['end_time'])) {
            $q->where('create_time', '<=', self::toTimestamp($this->params['end_time']));
        }
        // 关键词:按成员昵称/手机号筛(含划拨操作人)
        $kw = trim((string)($this->params['keyword'] ?? ''));
        if ($kw !== '') {
            $kwIds = User::whereIn('id', $ids)
                ->where(function ($w) use ($kw) {
                    $w->where('nickname', 'like', '%' . $kw . '%')
                        ->whereOr('mobile', 'like', '%' . $kw . '%');
                })
                ->column('id');
            $kwIds = array_values(array_filter(array_map('intval', $kwIds ?: [])));
            if (!$kwIds) {
                $q->where('user_id', 0);
            } else {
                $this->applyMemberScope($q, $kwIds);
            }
        }
        return $q;
    }

    /**
     * @notes 成员维度组合筛选:本人流水 ∪ 划拨类中 operator_id 命中(管理员代操作记在创始人钱包)
     * @param \think\db\BaseQuery $q
     * @param int[] $userIds
     */
    private function applyMemberScope($q, array $userIds): void
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds)));
        if (!$userIds) {
            $q->where('user_id', 0);
            return;
        }
        $opTypes = [
            AccountLogEnum::TOKENS_DEC_TEAM_ALLOCATE,
            AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
        ];
        $q->where(function ($w) use ($userIds, $opTypes) {
            $w->whereIn('user_id', $userIds);
            foreach ($userIds as $uid) {
                $w->whereOr(function ($or) use ($uid, $opTypes) {
                    $or->whereIn('change_type', $opTypes)
                        ->where(function ($x) use ($uid) {
                            $x->where('extra', 'like', '%"operator_id":' . $uid . ',%')
                                ->whereOr('extra', 'like', '%"operator_id":' . $uid . '}%');
                        });
                });
            }
        });
    }

    /**
     * 当前筛选下的合计(供汇总卡) + 业务类型下拉选项
     * 消耗列表:total_cost = 业务净消耗(DEC - 同类型失败退回),
     *   与成员列表「算力累计消耗」(AccountLogLogic::getTeamConsumedMap)完全同口径;
     * 算力流转:total_out=划出/扣除合计(DEC), total_in=入账/退回合计(INC)。
     */
    public function extend(): array
    {
        $listType = $this->listType();
        $dec = (float)$this->baseQuery()
            ->where('action', AccountLogEnum::DEC)
            ->sum('change_amount');
        $inc = (float)$this->baseQuery()
            ->where('action', AccountLogEnum::INC)
            ->sum('change_amount');
        $out = [
            'list_type' => $listType,
            'biz_options' => self::bizOptions($listType),
        ];
        if ($listType === 'transfer') {
            $out['total_out'] = round($dec, 2);
            $out['total_in'] = round($inc, 2);
        } else {
            // baseQuery 已限定业务 DEC + 业务失败退回 INC
            $out['total_cost'] = round($dec - $inc, 2);
        }
        return $out;
    }

    public function lists(): array
    {
        $lists = $this->baseQuery()
            ->field('id,user_id,change_type,action,change_amount,left_tokens,remark,task_id,source_sn,extra,create_time')
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        $uids = array_values(array_unique(array_filter(array_column($lists, 'user_id'))));
        // 划拨/回收:展示真实操作人(创始人/管理员),钱包主体仍是 user_id
        $operatorIds = [];
        foreach ($lists as $row) {
            $extra = is_array($row['extra'] ?? null)
                ? $row['extra']
                : (json_decode((string)($row['extra'] ?? ''), true) ?: []);
            $opId = (int)($extra['operator_id'] ?? 0);
            if ($opId > 0) {
                $operatorIds[] = $opId;
            }
        }
        $uids = array_values(array_unique(array_merge($uids, $operatorIds)));
        // 勿用 column('a,b,c','id')：多字段易串值
        $userMap = [];
        if ($uids) {
            $userMap = array_column(
                User::whereIn('id', $uids)->field('id,nickname,mobile,avatar')->select()->toArray(),
                null,
                'id'
            );
        }
        $allocDisplayTypes = [
            AccountLogEnum::TOKENS_DEC_TEAM_ALLOCATE,
            AccountLogEnum::TOKENS_INC_TEAM_ALLOCATE_REFUND,
        ];
        foreach ($lists as &$item) {
            [$bizKey, $bizName, $output] = self::bizType((int)$item['change_type']);
            $extra = is_array($item['extra'] ?? null)
                ? $item['extra']
                : (json_decode((string)($item['extra'] ?? ''), true) ?: []);
            $opId = (int)($extra['operator_id'] ?? 0);
            $opRole = (int)($extra['operator_role'] ?? 0);
            $displayUid = ((int)$item['user_id']);
            // 划拨/回收明细头像昵称用操作人,避免管理员操作却显示创始人
            if ($opId > 0 && in_array((int)$item['change_type'], $allocDisplayTypes, true)) {
                $displayUid = $opId;
            }
            $u = $userMap[$displayUid] ?? [];
            $item['user_name'] = $u['nickname'] ?? ('用户' . $displayUid);
            $item['mobile'] = $u['mobile'] ?? '';
            // User 模型 getAvatarAttr 已补全域名
            $item['avatar'] = (string)($u['avatar'] ?? '');
            $item['operator_id'] = $opId;
            $item['operator_role'] = $opRole;
            $item['operator_role_name'] = match ($opRole) {
                2 => '创始人',
                3 => '管理员',
                default => '',
            };
            // 剩余算力=该笔流水记账后的余额快照(left_tokens),勿用现时钱包覆盖
            $item['tokens'] = round((float)($item['left_tokens'] ?? 0), 2);
            $item['biz_key'] = $bizKey;
            $item['biz_name'] = $bizName;
            $item['output_type'] = $output; // chat|image|video|none
            $item['type_desc'] = AccountLogEnum::getChangeTypeDesc((int)$item['change_type']);
            if ($item['operator_role_name'] !== '' && in_array((int)$item['change_type'], $allocDisplayTypes, true)) {
                $item['type_desc'] = $item['operator_role_name'] . ' · ' . $item['type_desc'];
            }
            unset($item['extra']);
        }
        unset($item);
        return $lists;
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }
}
