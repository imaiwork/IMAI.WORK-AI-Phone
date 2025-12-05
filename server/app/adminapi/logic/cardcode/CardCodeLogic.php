<?php

namespace app\adminapi\logic\cardcode;
use app\common\enum\CardCodeEnum;
use app\common\enum\CardCodeRecordEnum;
use app\common\model\cardcode\CardCode;
use app\common\model\cardcode\CardCodeRecord;
use app\common\service\ConfigService;
use think\facade\Db;

/**
 * 卡密逻辑类
 * Class CardCodeController
 * @package app\adminapi\logic\cardcode
 */
class CardCodeLogic
{


    /**
     * @notes 添加卡密
     * @param array $post
     * @return bool
     * @author kb
     * @date 2023/7/10 15:47
     */
    public function add(array $post)
    {

        try{
            Db::startTrans();
            $post['sn'] = card_sn(CardCode::class,'sn');
            $cardCode   = new CardCode();
            $cardCode->save($post);
            $cardCodeRecord = [];
            for ($i = 0; $i < $post['card_num']; $i++) {
                $cardCodeRecord[] = [
                    'card_id'   => $cardCode->id,
                    'sn'        => card_sn(CardCodeRecord::class,'sn','K',10,$post['rule_type']),
                ];
            }
            (new CardCodeRecord())->saveAll($cardCodeRecord);

            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            return $e->getMessage();
        }
    }


    /**
     * @notes 卡密详情
     * @param int $id
     * @author kb
     * @date 2023/7/10 17:18
     */
    public function detail(int $id)
    {

        $cardCode = CardCode::where(['id' => $id])->field('id,sn,type,balance,card_num,relation_id,valid_start_time,valid_end_time,create_time,remark')->findOrEmpty();
        if ($cardCode->isEmpty()) {
            return [];
        }
        $cardCode->type_desc = CardCodeEnum::getTypeDesc($cardCode->type);
        $cardCode->content = '';
        $cardCode->package_id = '';
        switch ($cardCode->type){
            case CardCodeEnum::TYPE_TOKENS:
                $cardCode->content = $cardCode->balance;
                break;
        }
        $cardCode->valid_time_desc = date('Y-m-d H:i:s',$cardCode->valid_start_time).'~'.date('Y-m-d H:i:s',$cardCode->valid_end_time);
        $useNum = CardCodeRecord::where(['card_id'=>$cardCode->id,'status'=>CardCodeRecordEnum::STATYS_YES])->count();
        $cardCode->use_num = $useNum;
        $cardCode->unused_num = $cardCode->card_num - $useNum;
        return $cardCode->toArray();
    }


    /**
     * @notes 删除卡密
     * @param int $id
     * @author kb
     * @date 2023/7/10 17:33
     */
    public function del(int $id)
    {
        CardCode::where(['id'=>$id])->delete();
    }


    /**
     * @notes 获取卡密配置
     * @return array|int|mixed|string
     * @author kb
     * @date 2023/7/11 11:53
     */
    public function getConfig()
    {
        return [
            'is_open' =>  ConfigService::get('card_code','is_open',0),
        ];
    }


    /**
     * @notes 设置卡密设置
     * @param array $post
     * @author kb
     * @date 2023/7/11 11:55
     */
    public function setConfig(array $post)
    {
         ConfigService::set('card_code','is_open',$post['is_open']);
    }


}