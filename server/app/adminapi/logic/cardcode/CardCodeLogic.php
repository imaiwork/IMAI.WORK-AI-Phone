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
            $type = (int)($post['type'] ?? 0);
            // 前端日期可能是毫秒时间戳，统一落到秒
            $validStart = (int)($post['valid_start_time'] ?? 0);
            $validEnd = (int)($post['valid_end_time'] ?? 0);
            if ($validStart > 9999999999) {
                $validStart = (int)floor($validStart / 1000);
            }
            if ($validEnd > 9999999999) {
                $validEnd = (int)floor($validEnd / 1000);
            }

            $data = [
                'sn' => card_sn(CardCode::class, 'sn'),
                'type' => $type,
                'card_num' => (int)$post['card_num'],
                'used_num' => 0,
                'valid_start_time' => $validStart,
                'valid_end_time' => $validEnd,
                'rule_type' => (int)($post['rule_type'] ?? 1),
                'remark' => trim((string)($post['remark'] ?? '')),
                'relation_id' => (int)($post['relation_id'] ?? 0),
                'balance' => 0,
                'member_level_id' => null,
                'member_days' => null,
            ];
            // 注意:validate 对「未传」的字段不会执行自定义规则,这里必须 ?? 兜底防 undefined key
            if ($type === CardCodeEnum::TYPE_TOKENS) {
                $data['balance'] = (float)($post['balance'] ?? 0);
            } elseif ($type === CardCodeEnum::TYPE_MEMBER) {
                $data['member_level_id'] = (int)($post['member_level_id'] ?? 0);
                $data['member_days'] = (int)($post['member_days'] ?? 0);
            }

            $cardCode = new CardCode();
            $cardCode->save($data);
            $cardCodeRecord = [];
            for ($i = 0; $i < $data['card_num']; $i++) {
                $cardCodeRecord[] = [
                    'card_id'   => $cardCode->id,
                    'sn'        => card_sn(CardCodeRecord::class,'sn','K',10,$data['rule_type']),
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

        $cardCode = CardCode::where(['id' => $id])
            ->field('id,sn,type,balance,card_num,relation_id,member_level_id,member_days,valid_start_time,valid_end_time,create_time,remark')
            ->findOrEmpty();
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
            case CardCodeEnum::TYPE_MEMBER:
                $levelName = \app\common\model\user\UserLevel::where('id', $cardCode->member_level_id)->value('level_name');
                $cardCode->content = ($levelName ?: '?') . ' ' . (int)$cardCode->member_days . ' 天';
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
        CardCode::destroy($id);
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