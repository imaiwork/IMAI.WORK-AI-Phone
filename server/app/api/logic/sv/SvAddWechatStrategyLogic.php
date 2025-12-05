<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvAddWechatStrategy;
use app\common\model\sv\SvAccount;

/**
 * StrategyLogic
 * @desc 机器人策略
 * @author Qasim
 */
class SvAddWechatStrategyLogic extends SvBaseLogic
{


    public static function strategyUpdate(array $params)
    {
        try {

            $account = SvAccount::where("account", $params['account'])->where('user_id', self::$uid)->findOrEmpty();
            if ( $account->isEmpty()) {
                self::setError('小红书账号不存在');
                return false;
            }
            $params['device_code'] = $account['device_code'];
            $params['account_type'] = $account['type'];
            if(is_array($params['wechat_id'])){
                $params['wechat_id'] = implode(',', $params['wechat_id']);
            }
            //print_r($params);die;
            // 查询
            $strategy = SvAddWechatStrategy::where('user_id', self::$uid)->where('account', $params['account'])->findOrEmpty();

            if ($strategy->isEmpty()) {
                $params['user_id'] = self::$uid;
                
                $strategy = SvAddWechatStrategy::create($params);
            } else {
                SvAddWechatStrategy::where('id', $strategy->id)->update($params);
            }

            self::$returnData = $strategy->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * @desc 策略信息
     * @return bool
     */
    public static function strategyDetail($params)
    {
        try {

            $strategy = SvAddWechatStrategy::where('account', $params['account'])->where('user_id', self::$uid)->findOrEmpty();
            if ($strategy->isEmpty()) {
                self::$returnData = [
                    "user_id" => self::$uid,
                    "account_type" => 3,
                    "wechat_enable" => 0,
                    "wechat_reg_type" => 0,
                    "wechat_id" => '',
                    "remark" => ''
                ];
                return true;
            }
            $strategy->wechat_id = explode(',', $strategy->wechat_id);
            self::$returnData = $strategy->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}