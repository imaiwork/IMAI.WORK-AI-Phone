<?php


namespace app\common\model\user;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\BaseModel;
use app\common\service\wechat\WeChatConfigService;

/**
 * 用户授权表
 * Class UserAuth
 * @package app\common\model
 */
class UserAuth extends BaseModel
{
    /** 退役(被顶掉的历史)授权行的 openid 标记 */
    const RETIRED_FLAG = '_retired_';


    /**
     * @notes 排除退役脏数据的授权查询
     * @param int $userId
     * @param int $terminal
     * @return \think\db\Query
     */
    public static function activeQuery(int $userId, int $terminal)
    {
        return self::where(['user_id' => $userId, 'terminal' => $terminal])
            ->where('openid', 'not like', '%\_retired\_%');
    }


    /**
     * @notes 取指定 appid 下的授权行
     * @param int $userId
     * @param int $terminal
     * @param string $appId 本次要使用的微信应用 appid
     * @return UserAuth|array|\think\Model
     * @remark 同一用户在主站小程序与 OEM 小程序下是两个 openid,必须按 appid 取,
     *         否则会拿 A 应用的 openid 去 B 应用下单,微信报 appid/openid 不匹配。
     *         历史数据未记录 app_id(空串),按旧行为回退取最新一条兜底。
     */
    public static function findByAppId(int $userId, int $terminal, string $appId)
    {
        if ($appId !== '') {
            $auth = self::activeQuery($userId, $terminal)
                ->where('app_id', $appId)
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$auth->isEmpty()) {
                return $auth;
            }
        }

        return self::activeQuery($userId, $terminal)
            ->where('app_id', '')
            ->order('id', 'desc')
            ->findOrEmpty();
    }


    /**
     * @notes 主站小程序 appid 名下的 openid
     * @param int $userId
     * @return string
     * @remark 订阅消息、支付等都用主站 access_token,拿到 OEM 小程序的 openid 会被微信判无效。
     *         取不到返回空串,由调用方决定是报错还是静默跳过。
     */
    public static function mnpOpenid(int $userId): string
    {
        $appId = (string)(WeChatConfigService::getMnpConfig()['app_id'] ?? '');
        $auth = self::findByAppId($userId, UserTerminalEnum::WECHAT_MMP, $appId);
        return (string)($auth->openid ?? '');
    }


    /**
     * @notes 取指定 appid 下的全部候选 openid(优先本 appid,其次历史未标记数据)
     * @param int $userId
     * @param int $terminal
     * @param string $appId
     * @return array
     */
    public static function openidsByAppId(int $userId, int $terminal, string $appId): array
    {
        $openids = [];
        if ($appId !== '') {
            $openids = self::activeQuery($userId, $terminal)
                ->where('app_id', $appId)
                ->order('id', 'desc')
                ->column('openid');
        }

        $legacy = self::activeQuery($userId, $terminal)
            ->where('app_id', '')
            ->order('id', 'desc')
            ->column('openid');

        $openids = array_merge($openids, $legacy);
        return array_values(array_unique(array_filter(array_map('strval', $openids))));
    }
}
