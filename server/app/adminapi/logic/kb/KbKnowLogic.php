<?php


namespace app\adminapi\logic\kb;

use app\common\enum\kb\KnowEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\Models;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbKnowQa;
use app\common\model\kb\KbKnowTeam;
use app\common\model\user\User;
use app\common\pgsql\KbEmbedding;
use app\common\service\FileService;
use Exception;

/**
 * 知识库逻辑类
 */
class KbKnowLogic extends BaseLogic
{
    /**
     * @notes 知识库详情
     * @param int $id
     * @return array
     * @author kb
     */
    public static function detail(int $id): array
    {
        $modelKbKnow = new KbKnow();
        return $modelKbKnow
            ->withoutField('user_id,delete_time')
            ->where(['id'=>$id])
            ->findOrEmpty()
            ->toArray();
    }

    /**
     * @notes 知识库删除
     * @param int|array $id
     * @return bool
     * @author kb
     */
    public static function del(int|array $id): bool
    {
        $modelKbKnow = new KbKnow();
        $modelKbKnow->startTrans();
        try {
            if (is_array($id)){
                $where[] = ['id','in',$id];
                $whereTeam[] = ['kb_id','in',$id];
                $whereFiles[] = ['know_id','in',$id];
            }else{
                $where[] = ['id','=',$id];
                $whereTeam[] = ['kb_id','=',$id];
                $whereFiles[] = ['know_id','=',$id];
            }
            $result = $modelKbKnow
                ->field(['id'])
                ->where($where)
                ->select()
                ->toArray();

            if (!$result) {
                throw new Exception('知识库不存在了!');
            }

            // 删除知识库
            KbKnow::destroy($id);

            // 删除团队

            (new KbKnowTeam())
                ->where($whereTeam)
                ->update([
                    'delete_time' => time()
                ]);

            // 删除拆分
            (new KbKnowQa())->where($whereTeam)
                ->update([
                    'delete_time' => time()
                ]);

            // 删除文件
            (new KbKnowFiles())->where($whereFiles)
                ->update([
                    'delete_time' => time()
                ]);

            // 删除数据
            (new KbEmbedding())->where($whereTeam)->update([
                'is_delete' => 1,
                'delete_time' => time()
            ]);

            $modelKbKnow->commit();
            return true;
        } catch (Exception $e) {
            $modelKbKnow->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 知识库转移
     * @param string $type
     * @param int $kbId
     * @param int $toUserId
     * @return bool
     * @author kb
     */
    public static function transfer(string $type, int $kbId, int $toUserId): bool
    {
        $modelKbKnow = new KbKnow();
        $modelKbKnow->startTrans();
        try {
            if (!in_array($type, ['all', 'kb']) ) {
                throw new Exception('请正确选择成员处理方式!');
            }

            $know = $modelKbKnow
                ->field(['id,user_id,name,is_enable'])
                ->where(['id'=>$kbId])
                ->findOrEmpty()
                ->toArray();

            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            if (!$know['is_enable']) {
                throw new Exception('知识库被禁用了!');
            }

            if ($know['user_id'] == $toUserId) {
                throw new Exception('目标用户没有任何变化哦!');
            }

            $modelUser = new User();
            $user = $modelUser->field(['id'])->where(['id'=>$toUserId])->findOrEmpty()->toArray();
            if (!$user) {
                throw new Exception('目标用户不存在哦!');
            }

            // 转移知识库
            KbKnow::update([
                'user_id' => $toUserId,
                'update_time' => time()
            ], ['id'=>$kbId]);

            // 不对用户成员进行转移
            if ($type === 'kb') {
                (new KbKnowTeam())
                    ->where(['kb_id'=>$kbId])
                    ->update([
                        'delete_time' => time()
                    ]);
            } else {
                // 拥有者变成团队成员
                KbKnowTeam::create([
                    'kb_id'       => $kbId,
                    'user_id'     => $know['user_id'],
                    'power'       => KnowEnum::POWER_VIEW,
                    'create_time' => time(),
                    'update_time' => time()
                ]);

                // 拥有者从团队列表中移除
                (new KbKnowTeam())
                    ->where(['kb_id'=>$kbId])
                    ->where(['user_id'=>$toUserId])
                    ->update([
                        'delete_time' => time()
                    ]);
            }

            $modelKbKnow->commit();
            return true;
        } catch (Exception $e) {
            $modelKbKnow->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改知识库状态
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function changeStatus(int $id): bool
    {
        try {
            $modelKbKnow = new KbKnow();
            $detail = $modelKbKnow
                ->field(['id,is_enable'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$detail) {
                throw new Exception('该知识库已不存在了!');
            }

            KbKnow::update([
                'is_enable'   => !$detail['is_enable'],
                'update_time' => time()
            ], ['id'=>$id]);

            if ($detail['is_enable']) {
                self::setError('禁用成功');
            } else {
                self::setError('启用成功');
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 文件列表
     * @param array $get
     * @return array
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public static function files(array $get): array
    {
        $pageNo   = intval($get['page_no']   ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $where    = [];
        if (!empty($get['keyword'])) {
            $where[] = ['kf.name','like','%'.$get['keyword'].'%'];
        }
        if (!empty($get['name'])) {
            $where[] = ['kf.name','like','%'.$get['name'].'%'];
        }
        if (!empty($get['start_time']) && !empty($get['end_time'])) {
            $where[] = ['kf.create_time','between',[strtotime($get['start_time']),strtotime($get['end_time'])]];
        }
        if (!empty($get['kb_id'])){
            $where[] = ['kf.know_id','=',$get['kb_id']];
        }

        $model = new KbKnowFiles();
        $lists = $model
            ->alias('kf')
            ->field([
                'kf.id,kf.know_id,kf.name,kf.type,kf.size,kf.is_default,kf.update_time,kf.create_time',
                'kf.user_id,u.sn,u.nickname,u.avatar,u.mobile'
            ])
            ->leftJoin('user u', 'u.id = kf.user_id')
            ->where($where)
            ->order('kf.is_default desc, kf.id desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        $embModel = (new Models())->where(['id'=>3])->value('name');

        $modelUser = new User();
        $pgKbEmbedding = new KbEmbedding();
        foreach ($lists['data'] as &$item) {
            $baseWhere = ['kb_id'=>$item['know_id'], 'fd_id'=>$item['id'], 'is_delete'=>0];
            $item['ok_sum']    = $pgKbEmbedding->where($baseWhere)->where(['status'=>KnowEnum::RUN_OK])->count();
            $item['wait_sum']  = $pgKbEmbedding->where($baseWhere)->where(['status'=>KnowEnum::RUN_WAIT])->count();
            $item['total_sum'] = $pgKbEmbedding->where($baseWhere)->count();
            $item['model']     = $embModel;

            if ($item['is_default']) {
                $user = $modelUser->field(['id,sn,nickname,mobile,avatar'])->where(['id'=>$item['user_id']])->findOrEmpty()->toArray();
                $item['user'] = [
                    'id'       => $user['id']??0,
                    'sn'       => $user['sn']??'',
                    'nickname' => $user['nickname']??'',
                    'mobile'   => $user['mobile']??'',
                    'avatar'   => FileService::getFileUrl($user['avatar']??''),
                ];
            } else {
                $item['user'] = [
                    'id'       => $item['user_id'],
                    'sn'       => $item['sn'],
                    'nickname' => $item['nickname'],
                    'mobile'   => $item['mobile'],
                    'avatar'   => FileService::getFileUrl($item['avatar']),
                ];
            }

            unset($item['user_id']);
            unset($item['sn']);
            unset($item['nickname']);
            unset($item['mobile']);
            unset($item['avatar']);
            unset($item['know_id']);
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $lists['data']
        ] ?? [];
    }

    /**
     * @notes 文件移除
     * @param array $fids
     * @return bool
     * @alias fzr
     */
    public static function fileRemove(array $fids): bool
    {
        $model = new KbKnowFiles();
        $model->startTrans();
        try {
            if (!$fids) { throw new Exception('请选择要删除的文件'); }

            (new KbKnowFiles())
                ->whereIn('id', $fids)
                ->update([
                    'delete_time' => time()
                ]);

            (new KbEmbedding())
                ->whereIn('fd_id', $fids)
                ->update([
                    'is_delete' => 1,
                    'delete_time' => time()
                ]);

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 文件数据
     * @param array $get
     * @return array
     * @throws @\think\db\exception\DbException
     * @alias fzr
     */
    public static function fileDatas(array $get): array
    {
        $pageNo   = intval($get['page_no']   ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $kid      = intval($get['kb_id']     ?? 0);
        $fid      = intval($get['fd_id']     ?? 0);

        $where = [];
        if (isset($get['keyword']) && $get['keyword']) {
            $where[] = ['question|answer', 'like', '%'.$get['keyword'].'%'];
        }

        if (isset($get['status']) && $get['status']) {
            $where[] = ['status', '=', intval($get['status'])];
        }

        if (!empty($get['start_time']) && !empty($get['end_time'])) {
            $where[] = ['create_time','between',[strtotime($get['start_time']),strtotime($get['end_time'])]];
        }

        $pgKbEmbedding = new KbEmbedding();
        $lists = $pgKbEmbedding
            ->field(['uuid,model,question,answer,annex,tokens,error,status,update_time,create_time'])
            ->where(['kb_id'=>$kid])
            ->where(['fd_id'=>$fid])
            ->where(['is_delete'=>0])
            ->where($where)
            ->order('create_time desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        foreach ($lists['data'] as &$item) {
            $item['status_msg'] = KnowEnum::getRunStatusDesc($item['status']);

            $item['annex'] = $item['annex'] ?: '[]';
            $annex = json_decode($item['annex'], true);

            $files = [];
            foreach ($annex['files']??[] as $file) {
                $files[] = ['url'=>FileService::getFileUrl($file['url']), 'name'=>$file['name']];
            }

            $images = [];
            foreach ($annex['images']??[] as $img) {
                $images[] = ['url'=>FileService::getFileUrl($img['url']), 'name'=>$img['name']];
            }

            $video = [];
            foreach ($annex['video']??[] as $v) {
                $video[] = ['url'=>FileService::getFileUrl($v['url']), 'name'=>$v['name']];
            }

            $item['images'] = $images;
            $item['video'] = $video;
            $item['files'] = $files;
            unset($item['annex']);
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $lists['data']
        ] ?? [];
    }
}