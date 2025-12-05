<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechat;
use app\api\logic\wechat\MessageLogic;
use app\common\model\wechat\AiWechatRobot;
use app\common\model\kb\KbRobot;

/**
 * 微信列表
 * Class WechatLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class WechatLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['w.wechat_status', 's.takeover_mode', 'w.wechat_id', 'wechat_nickname'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return AiWechat::alias('w')
            ->field('w.id,w.device_code,w.wechat_id,w.wechat_nickname,w.wechat_avatar,w.wechat_status,w.create_time, s.model, s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type, s.robot_id')
            ->join('ai_wechat_setting s', 's.wechat_id = w.wechat_id')
            ->where($this->searchWhere)
            ->order(['s.sort' => 'desc', 'w.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if (empty($item['takeover_mode'])) {
                    $item['takeover_mode'] = 0;
                }

                if (empty($item['robot_id'])) {
                    $item['robot_id'] = 0;
                }

                $item['robot_name'] = KbRobot::where('id', $item['robot_id'])->where('user_id', $this->userId)->value('name', '');

                $item['wechat_status'] = 0;

                // 请求在线状态
                $result = MessageLogic::getOnlineStatus($item['wechat_id'], $item['device_code']);
                if ($result) {
                    $item['wechat_status'] = MessageLogic::getReturnData();
                    $item->wechat_status = $item['wechat_status'];
                    $item->save();
                }else{
                    throw new \think\Exception(MessageLogic::getError());
                }
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return AiWechat::alias('w')
            ->field('w.id,w.device_code,w.wechat_id,w.wechat_nickname,w.wechat_avatar,w.wechat_status,w.create_time,s.takeover_mode, s.takeover_type, s.robot_id')
            ->leftJoin('ai_wechat_setting s', 's.wechat_id = w.wechat_id')
            ->where($this->searchWhere)
            ->count();
    }
}
