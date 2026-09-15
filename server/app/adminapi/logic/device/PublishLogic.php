<?php

namespace app\adminapi\logic\device;

use app\common\logic\BaseLogic;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechat;
use think\facade\Db;

class PublishLogic extends BaseLogic
{


    public static function delete($params)
    {
        Db::startTrans();
        try {
            if (is_string($params['id'])) {
                SvPublishSettingAccount::destroy(['id' => $params['id']]);
                SvPublishSettingDetail::where('publish_account_id', $params['id'])->select()->delete();

            } else {
                SvPublishSettingAccount::whereIn('id', $params['id'])->select()->delete();
                SvPublishSettingDetail::where('publish_account_id', 'in', $params['id'])->select()->delete();

            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 获取发布详情
     * @param array $params
     * @return bool
     */
    public static function detail(array $params)
    {
        try {
            $publish = SvPublishSettingAccount::alias('pa')
                ->field('pa.*,u.nickname,u.avatar')
                ->leftjoin('user u', 'u.id = pa.user_id')
                ->where('pa.id', $params['id'])
                ->findOrEmpty();
            if ($publish->isEmpty()) {
                self::setError('发布任务不存在');
                return false;
            }
            $return = $publish->toArray();
            $times = SvPublishSettingDetail::where('publish_id', $publish['id'])->where('status', 'in', [0, 3])->column('publish_time');
            $return['times'] = array_map(function ($time) {
                return date('h:i A', strtotime($time));
            }, $times);

            $last = SvPublishSettingDetail::where('publish_id', $publish['id'])->order('publish_time', 'desc')->limit(1)->find();
            $return['publish_end'] = !empty($last) ? date('Y-m-d', strtotime($last['publish_time'])) : '';

            $return['publish_cycle'] = ceil((strtotime($return['publish_end'] . ' 23:59:59') - strtotime($return['publish_start'] . ' 00:00:00')) / 86400);
            $return['accounts'] = json_decode($return['accounts'], true);
            $return['count'] = SvPublishSettingAccount::where('publish_id', $publish['id'])->sum('count');
            $return['published_count'] = SvPublishSettingDetail::where('publish_id', $publish['id'])->where('status', 'in', [1, 2])->count();
            self::$returnData = $return;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除发布详情
     * @param array $params
     * @return bool
     */
    public static function recordDelete(array $params)
    {
        try {
            if (is_string($params['id'])) {
                SvPublishSettingDetail::destroy(['id' => $params['id']]);
            } else {
                SvPublishSettingDetail::destroy($params['id']);
            }
            self::$returnData = $params;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function recordDetail(array $params)
    {
        try {
            // 检查任务发布计划是否存在
            $record = SvPublishSettingDetail::field('*')
                ->where('id', $params['id'])
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('任务记录不存在');
                return false;
            }
            $result = $record->toArray();
            if ($result['account_type'] === 1) {
                $account = AiWechat::alias('a')
                    ->join('ai_wechat_device d', 'a.device_code = d.device_code and a.user_id = d.user_id')
                    ->where('a.user_id', $result['user_id'])
                    ->where('a.wechat_id', $result['account'])
                    ->where('a.device_code', $result['device_code'])
                    ->field('a.wechat_nickname as nickname, a.wechat_avatar as avatar, d.device_model, d.sdk_version')
                    ->findOrEmpty();
            } elseif ($result['account_type'] === 3) {
                $account = SvAccount::alias('a')
                    ->join('sv_device d', 'a.device_code = d.device_code and a.user_id = d.user_id')
                    ->where('a.user_id', $result['user_id'])
                    ->where('a.account', $result['account'])
                    ->where('a.device_code', $result['device_code'])
                    ->field('a.nickname, a.avatar, d.device_model, d.sdk_version')
                    ->findOrEmpty();
            }
            if ($account->isEmpty()) {
                $result['nickname'] = '';
                $result['avatar'] = '';
                $result['device_model'] = '';
                $result['sdk_version'] = '';
            } else {
                $result['nickname'] = $account['nickname'];
                $result['avatar'] = $account['avatar'];
                $result['device_model'] = $account['device_model'];
                $result['sdk_version'] = $account['sdk_version'];
            }

            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function change(array $params)
    {
        $find = SvPublishSetting::where('id', $params['id'])->findOrEmpty();
        if ($find->isEmpty()) {
            self::setError('任务不存在');
            return false;
        }
        $find->status = $params['status'];
        $find->updated_time = time();
        $find->save();
        self::$returnData = $find->refresh()->toArray();
        return true;
    }

}
