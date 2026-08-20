<?php

namespace app\api\logic;
use app\common\enum\CardCodeEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardCodeRecord;
use app\common\model\user\UserLevel;
use app\common\model\member\MemberUser;
use app\common\model\recharge\RechangeCardCodeLog;
use app\common\model\user\User;
use app\common\service\MemberService;
use app\common\service\TeamBillingService;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;

/**
 * 卡密兑换逻辑类
 * Class CardCodeLogic
 * @package app\api\logic
 */
class CardCodeLogic extends BaseLogic
{


    /**
     * @notes 获取卡密
     * @param string $sn
     * @param int $userId
     * @param string $scene tokens=仅算力卡(OEM获取算力等入口)
     * @return array|string
     * @author kb
     * @date 2023/7/11 16:29
     */
    public function checkCard(string $sn, int $userId, string $scene = '')
    {
        try {
            $cardCode = $this->checkSn($sn)['card_code'];
            $this->assertSceneAllowsType($scene, (int)$cardCode->type);
            $content = '';
            $validTime = '';
            $now = time();
            switch ($cardCode->type) {
                case CardCodeEnum::TYPE_TOKENS:
                case CardCodeEnum::TYPE_DISTRIBUTION_TOKENS:
                    $content = $cardCode->balance;
                    break;
                case CardCodeEnum::TYPE_MEMBER:
                    $levelName = UserLevel::where('id', $cardCode->member_level_id)->value('level_name');
                    $content = ($levelName ?: '?') . ' ' . (int)$cardCode->member_days . ' 天';
                    break;
            }
            return [
                'id' => $cardCode->id,
                'sn' => $cardCode->sn,
                'type' => $cardCode->type,
                'type_desc' => CardCodeEnum::getTypeDesc($cardCode->type),
                'content' => $content,
                'valid_time' => $validTime,
                'failure_time' => $cardCode->valid_end_time > 0 ? date('Y-m-d H:i:s', $cardCode->valid_end_time) . ' 前可使用' : '永久有效'
            ];
        } catch (Exception $e) {
            return $e->getMessage();
        }


    }

    /**
     * @notes 卡密兑换
     * @param $sn
     * @param int $userId
     * @param string $scene tokens=仅算力卡
     * @author kb
     * @date 2023/7/11 17:11
     */
    public function useCard($sn, $userId, string $scene = '')
    {
        try {

            $cache = Cache::get('card_code_' . $sn);
            Cache::set('card_code_' . $sn, $sn, 2);
            if ($cache) {
                throw new Exception('请勿频繁操作');
            }

            Db::startTrans();
            $cardData = $this->checkSn($sn);
            $cardCode = $cardData['card_code'];
            $this->assertSceneAllowsType($scene, (int)$cardCode->type);
            $user = User::findOrEmpty($userId);


            if ($cardCode->type == CardCodeEnum::TYPE_MEMBER) {
                $levelId = (int)($cardCode->member_level_id ?? 0);
                $days = (int)($cardCode->member_days ?? 0);
                if ($levelId <= 0 || $days <= 0) {
                    throw new Exception('该会员兑换码配置无效');
                }
                MemberService::grant(
                    $userId, $levelId, $days,
                    MemberUser::SOURCE_CARDCODE,
                    '卡密兑换:' . $sn
                );
                MemberService::thawWithinQuota($userId);
            }

            //兑换算力值
            if (in_array($cardCode->type, [CardCodeEnum::TYPE_TOKENS, CardCodeEnum::TYPE_DISTRIBUTION_TOKENS])) {
                $balance = (float)($cardCode['balance'] ?? 0);
                if ($balance > 0) {
                    // 企业卡密:成员/管理员入企业钱包;团队主入个人算力(与划拨/消费口径一致)
                    // 个人卡密:始终入个人算力;显式 team_id=0,避免成员兑换时流水误挂企业且 left_tokens=0
                    $cardTeamId = (int)($cardCode->team_id ?? 0);
                    $creditedToTeam = false;
                    if ($cardTeamId > 0) {
                        $creditedToTeam = TeamBillingService::creditTeamWallet((int)$userId, $balance, $cardTeamId);
                    }
                    if (!$creditedToTeam) {
                        $user->tokens = bcadd((string)$user->tokens, (string)$balance, 2);
                        $user->save();
                    }
                    $extra = ['变动来源' => "卡密兑换增加算力", '变动详情' => $sn];
                    AccountLogLogic::add(
                        $userId,
                        AccountLogEnum::TOKENS_INC_CARDCODE_GIVE,
                        AccountLogEnum::INC,
                        $balance,
                        1,
                        $sn,
                        AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_INC_CARDCODE_GIVE),
                        $extra,
                        $cardTeamId > 0 ? $cardTeamId : 0
                    );
                }
            }
            if (!empty($cardData['is_direct']) && $cardData['is_direct']) {
                $cardCode->used_num += 1;
                $cardCode->save();

                // 动态新增一条子表兑换记录，用于日志/后台检索是谁兑换了这几次卡密
                CardCodeRecord::create([
                    'card_id' => $cardCode->id,
                    'sn' => $cardCode->sn . "_" . $cardCode->used_num,
                    'status' => 1,
                    'use_time' => time(),
                    'user_id' => $userId,
                ]);
            } else {
                // 更新子表卡密记录（向下兼容老数据）
                $cardCodeRecord = $cardData['card_code_record'];
                $cardCodeRecord->user_id = $userId;
                $cardCodeRecord->status = 1;
                $cardCodeRecord->use_time = time();
                $cardCodeRecord->save();
            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            // 回滚事务
            Db::rollback();
            return $e->getMessage();
        }

    }

    /**
     * @notes 按兑换入口校验卡密类型
     * @param string $scene tokens=仅允许算力卡/代理算力卡
     */
    private function assertSceneAllowsType(string $scene, int $type): void
    {
        if ($scene !== 'tokens') {
            return;
        }
        if (!in_array($type, [CardCodeEnum::TYPE_TOKENS, CardCodeEnum::TYPE_DISTRIBUTION_TOKENS], true)) {
            throw new Exception('此处仅支持兑换算力卡密，会员兑换码请在会员中心兑换');
        }
    }

    /**
     * @notes 验证卡密
     * @param $sn
     * @return array
     * @author kb
     * @date 2023/7/11 17:03
     */
    public function checkSn($sn)
    {

        if (empty($sn)) {
            throw new Exception('查询失败，请输入卡密');
        }

        // 优先验证主表的SN
        $cardCode = CardCode::where(['sn' => $sn])->findOrEmpty();
        if (!$cardCode->isEmpty()) {
            if ($cardCode->used_num >= $cardCode->card_num) {
                throw new Exception('查询失败，卡密已无剩余使用次数');
            }
            $now = time();
            if ($now < $cardCode->valid_start_time) {
                throw new Exception('该卡密未到生效时间');
            }
            if ($cardCode->valid_end_time > 0 && $cardCode->valid_end_time < $now) {
                throw new Exception('卡密已过期');
            }
            return [
                'card_code' => $cardCode,
                'is_direct' => true
            ];
        }

        // 兼容旧版：去记录表查询子表的SN
        $cardCodeRecord = CardCodeRecord::where(['sn' => $sn])->findOrEmpty();
        if ($cardCodeRecord->isEmpty()) {
            throw new Exception('查询失败，卡密编号不存在');
        }
        if ($cardCodeRecord->status) {
            throw new Exception('查询失败，该次卡密已被使用');
        }
        $cardCode = CardCode::where(['id' => $cardCodeRecord->card_id])->findOrEmpty();
        if ($cardCode->isEmpty()) {
            throw new Exception('查询失败，主卡密信息丢失');
        }
        $now = time();
        if ($now < $cardCode->valid_start_time) {
            throw new Exception('该卡密未到生效时间');
        }
        if ($cardCode->valid_end_time > 0 && $cardCode->valid_end_time < $now) {
            throw new Exception('卡密已过期');
        }
        return [
            'card_code' => $cardCode,
            'card_code_record' => $cardCodeRecord,
            'is_direct' => false
        ];
    }

}