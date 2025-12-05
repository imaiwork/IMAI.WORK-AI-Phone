<?php

namespace app\adminapi\lists\lianlian;

use app\common\model\lianlian\LlAnalysis;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\model\lianlian\LlChat;


/**
 * 列表
 * Class LlAnalysisLists
 * @package app\Adminapi\lists\lianlian
 */
class LlAnalysisLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists(): array
    {

       return LlAnalysis::alias('la')
            ->join('user u', 'u.id = la.user_id')
            ->join('ll_scene ls', 'ls.id = la.scene_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('la.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->whereIn('la.status', [1, 2, 3])
            ->field('la.id, la.scene_id, la.task_id, ls.name as scene_name, ls.user_id as scene_user_id, ls.logo as scene_logo, la.user_id as ask_user_id, la.start_time, la.end_time, la.status, u.nickname as ask_user_name, u.avatar as ask_user_avatar')
            ->order('la.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if($item['user_id'] == 0){
                    $item['scene_user_name']      = '管理员';
                }else{
                    $sceneUserInfo = User::where('id', $item['scene_user_id'])->field('nickname, avatar')->find();
                    $item['scene_user_name']      = $sceneUserInfo->nickname ?? '';
                    $item['scene_user_avatar']    = $sceneUserInfo->avatar ? FileService::getFileUrl($sceneUserInfo->avatar) : '';
                }

                $item['ask_user_avatar']    = $item['ask_user_avatar'] ? FileService::getFileUrl($item['ask_user_avatar']) : '';
                $item['scene_logo']         = $item['scene_logo'] ? FileService::getFileUrl($item['scene_logo']) : '';

                //AI陪练扣费
                $item['points'] = UserTokensLog::where('user_id', $item['ask_user_id'])
                    ->where('change_type', AccountLogEnum::TOKENS_DEC_AI_LIANLIAN)
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type')
                    ->sum('change_amount');

                //时长 end_time - start_time
                $item['duration']   = $item['end_time'] - $item['start_time'];
                $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
                $item['end_time']   = date('Y-m-d H:i:s', $item['end_time']);
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function count(): int
    {
        return LlAnalysis::alias('la')
            ->join('user u', 'u.id = la.user_id')
            ->join('ll_scene ls', 'ls.id = la.scene_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('la.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->whereIn('la.status', [1, 2, 3])
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function setSearch(): array
    {
        return [
            "%like%" =>  ['ls.name'],
            '=' => ['la.status'],
        ];
    }
}
            