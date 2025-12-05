<?php

namespace app\api\logic;
use app\common\enum\CardCodeEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardCodeRecord;
use app\common\model\recharge\RechangeCardCodeLog;
use app\common\model\user\User;
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
     * @param string $userId
     * @return array|string
     * @author kb
     * @date 2023/7/11 16:29
     */
    public function checkCard(string $sn,int $userId)
    {
        try{
            $cardCode = $this->checkSn($sn)['card_code'];
            $content = '';
            $validTime = '';
            $now = time();
            switch ($cardCode->type){
                case CardCodeEnum::TYPE_TOKENS:
                    $content = $cardCode->balance;
                    break;
            }
            return [
                'id'            => $cardCode->id,
                'sn'            => $cardCode->sn,
                'type'          => $cardCode->type,
                'type_desc'     => CardCodeEnum::getTypeDesc($cardCode->type),
                'content'       => $content,
                'valid_time'    => $validTime,
                'failure_time'  => date('Y-m-d H:i:s',$cardCode->valid_end_time).' 前可使用'
            ];
        }catch (Exception $e){
            return $e->getMessage();
        }

        
    }

    /**
     * @notes 卡密兑换
     * @param $sn
     * @author kb
     * @date 2023/7/11 17:11
     */
    public function useCard($sn,$userId)
    {
        try{

            $cache = Cache::get('card_code_'.$sn);
            Cache::set('card_code_'.$sn,$sn,2);
            if($cache){
                throw new Exception('请勿频繁操作');
            }

            Db::startTrans();
            $cardData = $this->checkSn($sn);
            $cardCode = $cardData['card_code'];
            $cardCodeRecord = $cardData['card_code_record'];
            $user = User::findOrEmpty($userId);


            //兑换算力值
            if(CardCodeEnum::TYPE_TOKENS == $cardCode->type){
                $balance = $cardCode['balance'] ?? 0;
                if($balance > 0){
                    //用户添加次数
                    $user->tokens += $balance;
                    $user->save();
                    //记录流水
                    $extra = ['变动来源' => "卡密兑换增加算力", '变动详情' => $sn];
                    AccountLogLogic::add(
                        $userId,
                        AccountLogEnum::TOKENS_INC_CARDCODE_GIVE,
                        AccountLogEnum::INC,
                        $balance,
                        1,
                        $sn,
                        AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_INC_CARDCODE_GIVE),
                        $extra
                    );
                }
            }
            // 更新卡密兑换记录
            $cardCodeRecord->user_id = $userId;
            $cardCodeRecord->status = 1;
            $cardCodeRecord->use_time = time();
            $cardCodeRecord->save();

            Db::commit();
            return true;
        }catch (Exception $e){
            // 回滚事务
            Db::rollback();
            return $e->getMessage();
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

        if(empty($sn)){
            throw new Exception('查询失败，请输入卡密');
        }

        $cardCodeRecord = CardCodeRecord::where(['sn'=>$sn])->findOrEmpty();
        if($cardCodeRecord->isEmpty()) {
            throw new Exception('查询失败，卡密编号不存在');
        }
        if($cardCodeRecord->status){
            throw new Exception('查询失败，卡密已被使用');
        }
        $cardCode = CardCode::where(['id' => $cardCodeRecord->card_id])->findOrEmpty();
        $now = time();
        if($now < $cardCode->valid_start_time) {
            throw new Exception('该卡密未到生效时间');
        }
        if($cardCode->valid_end_time < $now) {
            throw new Exception('卡密已过期');
        }
        return [
            'card_code'         => $cardCode,
            'card_code_record'  => $cardCodeRecord
        ];
    }

}