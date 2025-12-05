<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
//use app\common\enum\ChatEnum;
//use app\common\lists\ListsExtendInterface;
//use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
//use app\common\model\kb\KbKnowShared;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbKnowQa;
use app\common\model\kb\KbKnowTeam;
use app\common\model\user\User;

/**
 * 知识库列表
 */
class KbKnowLists extends BaseApiDataLists
{
    /**
     * @notes 筛选条件
     * @return array
     * @author kb
     */
    public function where(): array
    {
        $where = [];
        $type = intval($this->request->get('type', 0));
        if (!empty($this->request->get('name'))) {
            $where[] = ['name', 'like', '%'.$this->request->get('name').'%'];
        }
        switch ($type){
            case 1: // 我的知识库
                $where[] = ['user_id', '=', $this->userId];
                break;
            case 2: // 共享给我的
                $shareKbIds = (new KbKnowTeam())->where(['user_id'=>$this->userId])->column('kb_id');

                if ($shareKbIds) {
                    $kbUidS  = (new KbKnow())->whereIn('id', $shareKbIds)->column('user_id', 'id');
                    $userIds = (new User())->whereIn('id', (array_values($kbUidS)?:[0]))->column('id');
                    $kbIds = [];
                    $deleteIds = [];
                    foreach ($kbUidS as $kbId => $uid) {
                        if (!in_array($uid, $userIds)) {
                            $deleteIds[] = $kbId;
                        } else {
                            $kbIds[] = $kbId;
                        }
                    }

                    $shareKbIds = $kbIds;
                    if ($deleteIds) {
                        KbKnowTeam::destroy($deleteIds);
                    }
                }

                $where[] = ['id', 'in', $shareKbIds??[0]];
                break;
            default: // 我的 + 共享的
                $shareKbIds = (new KbKnowTeam())->where(['user_id'=>$this->userId])->column('kb_id');
                if ($shareKbIds) {
                    $kbUidS  = (new KbKnow())->whereIn('id', $shareKbIds)->column('user_id', 'id');
                    $userIds = (new User())->whereIn('id', (array_values($kbUidS)?:[0]))->column('id');
                    $deleteIds = [];
                    $kbIds = [];
                    foreach ($kbUidS as $kbId => $uid) {
                        if (!in_array($uid, $userIds)) {
                            $deleteIds[] = $kbId;
                        } else {
                            $kbIds[] = intval($kbId);
                        }
                    }
                    $shareKbIds = $kbIds;
                    if ($deleteIds) {
                        KbKnowTeam::destroy($deleteIds);
                    }
                }

                $where[] = ['user_id', '=', $this->userId];
//                $where[] = ['id', 'in', $shareKbIds??[0]];
                break;
        }

        return $where;
    }

    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $model = new KbKnow();
        $lists = $model
            ->field(['id,user_id,image,name,intro,is_enable,create_time'])
            ->where($this->where())
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $modelKbKnowTeam = new KbKnowTeam();
//        $modelModelsCost = new ModelsCost();
//        $modelsCostList = $modelModelsCost->field(['channel', 'name', 'alias'])->where(['type'=>ChatEnum::MODEL_TYPE_EMB])->select()->toArray();
//        $vectorModels = config('ai.VectorModels');
        foreach ($lists as &$item) {
//            $embeddingModel = '';
//            foreach ($modelsCostList as $m) {
//                if ($m['name'] == $item['embedding_model']) {
//                    $embeddingModel = $m['alias'];
//                    break;
//                }
//            }
//
//            if (!$embeddingModel) {
//                foreach ($vectorModels as $m) {
//                    if ($m['name'] == $item['embedding_model']) {
//                        $embeddingModel = $m['alias'];
//                        break;
//                    }
//                }
//            }
            $stats = KbKnowQa::where('kb_id', $item['id'])
                             ->field([
                                         'SUM(tokens) as token_counts',
                                         'COUNT(*) as request_counts'
                                     ])
                             ->find();
            $item['file_counts'] = KbKnowFiles::where('know_id',$item['id'])->count();
            $item['token_counts'] = $stats['token_counts'] ?? 0;
            $item['request_counts'] = $stats['request_counts'] ?? 0;
            // $item['embedding_model'] = $embeddingModel;
            $item['team_people'] = $modelKbKnowTeam->where(['kb_id'=>$item['id']])->count() + 1;
            $item['is_super'] = $item['user_id'] == $this->userId ? 1 : 0;
            unset($item['user_id']);
            unset($stats);
        }

        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbKnow();
        return $model
            ->where($this->where())
            ->count();
    }
}