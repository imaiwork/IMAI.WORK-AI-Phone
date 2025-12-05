<?php


namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\kb\KnowEnum;
use app\common\lists\ListsExtendInterface;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowQa;
use app\common\model\user\User;
use app\common\pgsql\KbEmbedding;
use app\common\service\FileService;
use app\queue\BaseQueue;

class KbTeachLists extends BaseAdminDataLists implements ListsExtendInterface
{
    /**
     * @notes 条件
     * @return array
     * @author kb
     */
    public function where(): array
    {
        $where[] = ['is_delete', '=', 0];
        if (isset($this->params['keyword']) && $this->params['keyword']) {
            $where[] = ['question|answer', 'like', '%'.$this->params['keyword'].'%'];
        }

        if (isset($this->params['status']) && is_numeric($this->params['status'])) {
            $where[] = ['status', '=', intval($this->params['status'])];
        }

        if (isset($this->params['model']) && $this->params['model']) {
            $where[] = ['model', '=', $this->params['model']];
        }

        if (isset($this->params['know']) && $this->params['know']) {
            $modelKbKnow = new KbKnow();
            $kbs = $modelKbKnow
                ->where('name',  'like', '%'.$this->params['know'].'%')
                ->limit(200)
                ->column('id');

            if ($kbs) {
                $where[] = ['kb_id', 'in', $kbs];
            }
        }

        if (isset($this->params['user']) && $this->params['user']) {
            $modelUser = new User();
            $users = $modelUser
                ->where('sn|nickname',  'like', '%'.$this->params['user'].'%')
                ->limit(200)
                ->column('id');

            if ($users) {
                $where[] = ['user_id', 'in', $users];
            }
        }

        return $where;
    }

    /**
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $model = new KbEmbedding();
        $lists = $model
            ->field(['uuid,user_id,kb_id,question,answer,annex,model,dimension,tokens,error,status,update_time,create_time'])
            ->where($this->where())
            ->limit($this->limitOffset, $this->limitLength)
            ->order('create_time desc')
            ->select()
            ->toArray();

        $vectorModels = config('ai.VectorModels');

        // 数据处理
        $userIds = [];
        $knowIds = [];
        foreach ($lists as &$item) {
            $userIds[] = $item['user_id'];
            $knowIds[] = $item['kb_id'];
            $item['status_msg'] = KnowEnum::getRunStatusDesc($item['status']);
            $item['model'] = $vectorModels[$item['model']]['name'] ?? $item['model'];
            $item['tokens'] = format_amount_zero($item['tokens']);

            $item['annex'] = $item['annex'] ?: '[]';
            $annex = json_decode($item['annex'], true);

            $images = [];
            foreach ($annex['images']??[] as $img) {
                $images[] = ['url'=>FileService::getFileUrl($img['url']), 'name'=>$img['name']??''];
            }

            $files = [];
            foreach ($annex['files']??[] as $file) {
                $files[] = ['url'=>FileService::getFileUrl($file['url']), 'name'=>$file['name']??''];
            }

            $video = [];
            foreach ($annex['video']??[] as $v) {
                $video[] = ['url'=>FileService::getFileUrl($v['url']), 'name'=>$v['name']??''];
            }

            $item['images'] = $images;
            $item['video'] = $video;
            $item['files'] = $files;
            unset($item['annex']);
        }

        // 所属用户
        if ($userIds) {
            $modelUser = new User();
            $users = $modelUser->field(['id,nickname'])->whereIn('id', array_unique($userIds))->select()->toArray();

            $userLists = [];
            foreach ($users as $user) {
                $userLists[$user['id']] = $user['nickname'];
            }

            foreach ($lists as &$item) {
                $item['nickname'] = $userLists[$item['user_id']]??'-';
            }
        }

        // 所属知识库
        if ($knowIds) {
            $modelKbKnow = new KbKnow();
            $knows = $modelKbKnow->field(['id,name'])->whereIn('id', array_unique($knowIds))->select()->toArray();

            $kbLists = [];
            foreach ($knows as $kb) {
                $kbLists[$kb['id']] = $kb['name'];
            }

            foreach ($lists as &$item) {
                $item['kb_name'] = $kbLists[$item['kb_id']]??'-';
            }
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
        $model = new KbEmbedding();
        return $model->where($this->where())->count();
    }

    /**
     * @notes 重载队列任务
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function extend(): array
    {
        // 加载向量任务
        $model = new KbEmbedding();
        $emCount = BaseQueue::getEmbJobLength();
        if ($emCount <= 0) {
            $ems = $model
                ->field(['uuid'])
                ->where(['is_delete'=>0])
                ->where(['status'=>KnowEnum::RUN_WAIT])
                ->limit(51)
                ->select()
                ->toArray();

            foreach ($ems as $e) {
                BaseQueue::pushEM(['uuid'=>$e['uuid']]);
            }
            $emCount = count($ems);
        }

        // 加载QA任务
        $qaCount = BaseQueue::getQaJobLength();
        if ($qaCount <= 0) {
            $modelKbKnowQa = new KbKnowQa();
            $qaList = $modelKbKnowQa
                ->where(['status'=>KnowEnum::QA_WAIT])
                ->limit(40)
                ->select()
                ->toArray();

            foreach ($qaList as $q) {
                BaseQueue::pushQA(['id'=>$q['id']]);
            }

            $qaCount = count($qaList);
        }

        $allCount = $model->where(['is_delete'=>0])->count();
        $waitCount = $model->where(['is_delete'=>0])->where(['status'=>KnowEnum::RUN_WAIT])->count();
        $ingCount = $model->where(['is_delete'=>0])->where(['status'=>KnowEnum::RUN_ING])->count();
        $okCount = $model->where(['is_delete'=>0])->where(['status'=>KnowEnum::RUN_OK])->count();
        $failCount = $model->where(['is_delete'=>0])->where(['status'=>KnowEnum::RUN_FAIL])->count();

        return [
            'emQueue'   => $emCount,
            'qaQueue'   => $qaCount,

            'allCount'  => $allCount,
            'waitCount' => $waitCount,
            'ingCount'  => $ingCount,
            'okCount'   => $okCount,
            'failCount' => $failCount
        ]??[];
    }
}