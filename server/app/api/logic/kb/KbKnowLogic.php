<?php


namespace app\api\logic\kb;

use app\common\enum\ChatEnum;
use app\common\enum\kb\KnowEnum;
use app\common\logic\BaseLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbKnowFiles;
use app\common\model\kb\KbKnowQa;
use app\common\model\kb\KbKnowTeam;
use app\common\model\kb\KbRobot;
use app\common\model\knowledge\KnowledgeBind;
use app\common\model\user\User;
use app\common\pgsql\KbEmbedding;
use app\common\service\FileService;
use app\common\service\recall\RecallKnow;
use app\common\service\recall\RecallUtils;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class KbKnowLogic extends BaseLogic
{
    /**
     * @notes 所有知识库
     * @param int $userId
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public static function all(int $userId): array
    {
        $teamKbIds = (new KbKnowTeam())->where(['user_id'=>$userId])->column('kb_id');

        $modelKbKnow = new KbKnow();
        $lists = $modelKbKnow
            ->field('id,name,image,embedding_model')
            ->where([
                ['user_id', '=', $userId],
                ['id', 'in', $teamKbIds]
            ])
            ->order('id desc')
            ->select()
            ->toArray();

        $models = (new ModelsCost())->where(['type'=>ChatEnum::MODEL_TYPE_EMB])->column('name', 'alias');
        foreach ($lists as &$item) {
            $m = $models[$item['embedding_model']] ?? $item['embedding_model'];
            $item['name'] = $item['name'] . ' ('.$m.')';
            unset($item['embedding_model']);
        }

        return $lists;
    }

    /**
     * @notes 知识库详情
     * @param $id
     * @param int $userId
     * @return array
     * @throws Exception
     * @author kb
     */
    public static function detail($id, int $userId): array
    {
        // 权限验证
        self::checkKbPower($id, $userId, KnowEnum::POWER_VIEW);

        $modelKbKnow = new KbKnow();
        $detail = $modelKbKnow
            ->withoutField('delete_time')
            ->where(['id'=>$id])
            ->findOrEmpty()
            ->toArray();

        if (!$detail) {
            self::setError('知识库不存在');
            return [];
        }

        $channel = (new ModelsCost())
            ->where(['type'=>ChatEnum::MODEL_TYPE_CHAT])
            ->where(['name'=>$detail['documents_model']])
            ->value('channel');

        $maxStrNums = [
            2000  => 500, // tokens => 字符数
            4096  => 1800,
            6000  => 2800,
            7000  => 3000,
            8192  => 3800,
            14096 => 6000,
            16385 => 7000,
            18192 => 8500,
            32768 => 13000
        ];

        $detail['documents_model_id'] = $detail['documents_model_id'] ?: '';
        $detail['embedding_model_id'] = $detail['embedding_model_id'] ?: '';
        $detail['documents_model_sub_id'] = $detail['documents_model_sub_id'] ?: '';
        $detail['embedding_model_sub_id'] = $detail['embedding_model_sub_id'] ?: '';

        $detail['qa_length'] = 1800;
        $chatModels = config('ai.ChatModels')[$channel]['models']??[];
        foreach ($chatModels as $item) {
            if ($item['model'] == $detail['documents_model']) {
                $detail['qa_length'] = $maxStrNums[$item['maxContext']] ?? 1800;
                break;
            }
        }

        if ($detail['user_id'] === $userId) {
            $detail['owned'] = KnowEnum::OWNED_SUPER;
            $detail['power'] = KnowEnum::POWER_ALL;
        } else {
            $detail['owned'] = KnowEnum::OWNED_MEMBER;
            $detail['power'] = (new KbKnowTeam())
                ->where(['kb_id'=>$detail['id']])
                ->where(['user_id'=>$userId])
                ->value('power')??-1;
        }

        return $detail;
    }

    /**
     * @notes 知识库新增
     * @param array $post
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function add(array $post, int $userId): bool|array
    {
        $model = new KbKnow();
        $model->startTrans();
        try {
//            $unit = TokenLogService::checkToken($userId, 'create_vector_knowledge');
            // 主模型检测
//            $mainModel = (new Models())->where(['id'=>intval($post['documents_model_id'])])->findOrEmpty();
//            if (!$mainModel || !$mainModel['is_enable']) {
//                throw new Exception('文件处理模型已被下架');
//            }

            // 子模型检测
//            $subModel = (new ModelsCost())->where(['id'=>intval($post['documents_model_sub_id'])])->findOrEmpty();
//            if (!$subModel || !$subModel['status']) {
//                throw new Exception('文件处理模型已被下架!');
//            }

            // 主向量模型
            $mainEmbModel = (new Models())->where(['id'=>intval($post['embedding_model_id'])])->findOrEmpty();
            if ($mainEmbModel->isEmpty() || !$mainEmbModel['is_enable']) {
                throw new Exception('向量模型已被下架!');
            }

            // 子向量模型
            $subEmbModel = (new ModelsCost())->where(['model_id'=>intval($mainEmbModel['id'])])->findOrEmpty();
            if (!$subEmbModel) {
                throw new Exception('向量模型已被下架');
            }

            $know = KbKnow::create([
                'user_id'                => $userId,
                'create_uid'             => $userId,
                'image'                  => FileService::setFileUrl($post['image']??''),
                'name'                   => $post['name'],
                'intro'                  => $post['intro']??'',
                'documents_model_id'     => $post['documents_model_id'],
                'documents_model_sub_id' => $post['documents_model_sub_id'],

                'embedding_model_id'     => $post['embedding_model_id'],
                'embedding_model_sub_id' => $subEmbModel['id']
            ]);

//            KbKnowFiles::create([
//                'user_id'     => $userId,
//                'know_id'     => $know['id'],
//                'name'        => '手动录入',
//                'is_default'  => 1,
//                'create_time' => time(),
//                'update_time' => time()
//            ]);

            $model->commit();
//            $response = ToolsService::VectorKnowledge()->create_vector();
//            if ($response['code']==10000){
//                $points = $unit;
//                $extra = ['向量知识库创建' => 1, '算力单价' => '20算力/次', '实际消耗算力' => $points];
//                User::userTokensChange($userId, $points);
//                //扣费记录
//                AccountLogLogic::recordUserTokensLog(true, $userId, 11001, $points, '', $extra);
//            }
            return ['id'=>$know['id']];
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 知识库编辑
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function edit(array $post, int $userId): bool
    {
        try {
            // 权限验证
            self::checkKbPower(intval($post['id']), $userId, KnowEnum::POWER_ALL);

            $modelKbKnow = new KbKnow();
            $know = $modelKbKnow
                ->field(['id,user_id,name,is_enable'])
                ->where(['id'=>intval($post['id'])])
                ->findOrEmpty()
                ->toArray();

            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            if (!$know['is_enable']) {
                throw new Exception('知识库被禁用了!');
            }

            // 主模型检测
//            $mainModel = (new Models())->where(['id'=>intval($post['documents_model_id'])])->findOrEmpty();
//            if (!$mainModel || !$mainModel['is_enable']) {
//                throw new Exception('文件处理模型已被下架了');
//            }

            // 子模型检测
//            $subModel = (new ModelsCost())->where(['id'=>intval($post['documents_model_sub_id'])])->findOrEmpty();
//            if (!$subModel || !$subModel['status']) {
//                throw new Exception('文件处理模型已被下架了!');
//            }

            KbKnow::update([
                'image'                  => FileService::setFileUrl($post['image']??''),
                'name'                   => $post['name'],
                'intro'                  => $post['intro']??'',
                'documents_model_id'     => $post['documents_model_id']??0,
                'documents_model_sub_id' => $post['documents_model_sub_id']??0,
            ], ['id'=>intval($post['id'])]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 知识库删除
     * @param int $id
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function del(int $id, int $userId): bool
    {
        $modelKbKnow = new KbKnow();
        $modelKbKnow->startTrans();
        try {
            // 权限验证
            self::checkKbPower($id, $userId, KnowEnum::POWER_ALL);


            $know = $modelKbKnow
                ->field(['id,user_id,name,is_enable'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            // 删除团队
            (new KbKnowTeam())
                ->where(['kb_id'=>$know['id']])
                ->update([
                    'delete_time' => time()
                ]);

            // 删除拆分
            (new KbKnowQa())->where(['kb_id'=>$id])
                ->update([
                    'delete_time' => time()
                ]);

            // 删除文件
            (new KbKnowFiles())->where(['know_id'=>$id])
                ->update([
                    'delete_time' => time()
                ]);

            // 删除机器人绑定关系
            $robot = (new KbRobot())->where('kb_ids', 'like', '%' . $id . '%')->select()->toArray();
            if ($robot) {
                foreach ($robot as $item) {
                    $item['kb_ids'] = explode(',', $item['kb_ids']);
                    foreach ($item['kb_ids'] as $key => $value) {
                        if ($value == $id) {
                            unset($item['kb_ids'][$key]);
                        }
                    }
                    KbRobot::update([
                                        'kb_ids' => implode(',', $item['kb_ids'])
                                    ], ['id' => $item['id']]);
                }
                KnowledgeBind::where('kid', $id)->delete();
            }

            // 删除知识库
            KbKnow::destroy($id);

            // 删除数据
            (new KbEmbedding())->where(['kb_id'=>$id])->update([
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
     * @param int $kbId
     * @param string $type
     * @param int $userId
     * @param string $toUserSn
     * @return bool
     */
    public static function transfer(int $kbId, string $type, int $userId, string $toUserSn): bool
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

            if ($know['user_id'] !== $userId) {
                throw new Exception('仅知识库拥有者才能转移!');
            }

            $modelUser = new User();
            $user = $modelUser->field(['id'])->where(['sn'=>$toUserSn])->findOrEmpty()->toArray();
            if (!$user) {
                throw new Exception('目标用户不存在!');
            }

            $toUserId = $user['id'];
            if ($know['user_id'] === $toUserId) {
                throw new Exception('转移的目标用户不能是自己!');
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
                    'user_id'     => $userId,
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
     * @notes 文件重命名
     * @param int $fid
     * @param string $name
     * @param int $userId
     * @return bool
     */
    public static function fileRename(int $fid, string $name, int $userId): bool
    {
        try {
            $model = new KbKnowFiles();
            $files = $model->where(['id'=>$fid])->findOrEmpty()->toArray();

            if (!$files) {
                throw new Exception('文件已不存在!');
            }

            // 权限验证
            $power = self::checkKbPower($files['know_id'], $userId);
            if ($power > KnowEnum::POWER_ALL and $files['user_id'] !== $userId) {
                throw new Exception('您仅可操作自己的数据');
            }

            KbKnowFiles::update([
                'name'        => $name,
                'update_time' => time()
            ], ['id'=>$fid]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 文件移除
     * @param int $fid
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function fileRemove(int $fid, int $userId): bool
    {
        $model = new KbKnowFiles();
        $model->startTrans();
        try {
            $files = $model->where(['id'=>$fid])->findOrEmpty()->toArray();
            if (!$files) {
                throw new Exception('文件已不存在了!');
            }

            if ($files['is_default']) {
                throw new Exception('默认文件不可删除!');
            }

            $power = self::checkKbPower($files['know_id'], $userId);
            if ($power > KnowEnum::POWER_ALL and $files['user_id'] !== $userId) {
                throw new Exception('您仅可操作自己的数据');
            }

            (new KbEmbedding())
                ->where(['kb_id'=>$files['know_id']])
                ->where(['fd_id'=>$fid])
                ->update([
                    'is_delete' => 1,
                    'delete_time' => time()
                ]);

            KbKnowFiles::destroy($fid);

            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 团队用户筛选
     * @param array $get
     * @param int $userId
     * @return array
     * @throws DbException
     * @author kb
     */
    public static function teamUsers(array $get, int $userId): array
    {
        $pageNo   = intval($get['page_no'] ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $keyword  = trim($get['keyword']??'');
        $kbId     = intval($get['kb_id']??0);

        if ($keyword) {
            $modelUser = new User();
            $lists = $modelUser
                ->field(['id,sn,nickname,avatar'])
                ->where('sn|nickname|mobile', 'like', '%'.$keyword.'%')
                ->order(['id'=>'desc'])
                ->paginate([
                    'page'      => $pageNo,
                    'list_rows' => $pageSize,
                    'var_page'  => 'page'
                ])->toArray();

            $userIds = [];
            if ($kbId){
                $userIds = (new KbKnowTeam())->where(['kb_id'=>$kbId])->column('user_id');
            }

            foreach ($lists['data'] as &$item) {
                $item['is_added'] = 0;
                if (in_array($item['id'], $userIds)) {
                    $item['is_added'] = 1;
                }
                if ($item['id'] == $userId) {
                    $item['is_added'] = 1;
                }
            }
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'] ?? 0,
            'lists'     => $lists['data'] ?? []
        ] ?? [];
    }

    /**
     * @notes 团队成员列表
     * @param int $kbId
     * @return array|bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author kb
     */
    public static function teamLists(int $kbId): array|bool
    {
        $know = (new KbKnow())->field(['id,user_id,create_time,update_time'])->where(['id'=>$kbId])->findOrEmpty()->toArray();
        if (!$know) {
            self::setError('知识库不存在!');
            return false;
        }

        // 拥有者不需要记录到团队表
        $team = (new KbKnowTeam())->where(['user_id'=>$know['user_id']??0])->findOrEmpty()->toArray();
        if ($team) {
            KbKnowTeam::destroy($team['id']);
        }

        // 获取知识库团队成员的列表
        $lists = (new KbKnowTeam())
            ->alias('kt')
            ->field([
                'kt.id,u.sn,u.nickname,u.avatar',
                'kt.power,kt.create_time,kt.update_time'
            ])
            ->join('user u', 'u.id=kt.user_id')
            ->where(['kt.kb_id'=>$kbId])
            ->order('kt.id desc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['avatar'] = FileService::getFileUrl($item['avatar']);
            $item['owned'] = KnowEnum::OWNED_MEMBER;
        }

        // 加入知识库库拥有者
        $user = (new User())->field(['id,sn,nickname,avatar'])->where(['id'=>$know['user_id']])->findOrEmpty()->toArray();
        array_unshift($lists, [
            'id'       => 0,
            'sn'       => $user['sn'],
            'nickname' => $user['nickname'],
            'avatar'   => $user['avatar'],
            'power'    => KnowEnum::POWER_ALL,
            'owned'    => KnowEnum::OWNED_SUPER,
            'create_time' => $know['create_time'],
            'update_time' => $know['update_time']
        ]);

        return $lists;
    }

    /**
     * @notes 团队成员添加
     * @param array $post
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function teamAdd(array $post, int $userId): bool
    {
        try {
            $knowId = intval($post['kb_id']);
            $select = $post['users'];

            // 验证知识库信息
            $know = (new KbKnow())->where(['id'=>$knowId])->findOrEmpty()->toArray();
            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            if (!$know['is_enable']) {
                throw new Exception('知识库被禁用了,不可操作!');
            }

            // 验证权限
            $team = (new KbKnowTeam())->where(['kb_id'=>$know['id'], 'user_id'=>$userId])->findOrEmpty()->toArray();
            if ($know['user_id'] !== $userId) {
                if (!$team) {
                    throw new Exception('抱歉,您不是团队成员,无权操作');
                }
                if ($team['power'] > KnowEnum::POWER_ALL) {
                    throw new Exception('权限不足,无权操作!');
                }
            }

            // 验证用户的信息
            $snArr = array_keys($select);
            $users = (new User())->whereIn('sn', $snArr)->column('id', 'sn');

            // 筛选需创建数据
            $createData = [];
            $teams = (new KbKnowTeam())->where(['kb_id'=>$knowId])->column('user_id');
            foreach ($users as $key => $uid) {
                if (!empty($select[$key]) && !in_array($uid, $teams) && $uid !== $userId) {
                    $createData[] = [
                        'user_id' => $uid,
                        'user_sn' => $key,
                        'power'   => intval($select[$key])
                    ];
                }
            }

            // 如存在则不添加
            foreach ($createData as $item) {
                KbKnowTeam::create([
                    'kb_id'   => $knowId,
                    'user_id' => $item['user_id'],
                    'power'   => $item['power']
                ]);
            }

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 团队成员编辑
     * @param int $id
     * @param string $power
     * @param int $userId
     * @return bool
     */
    public static function teamEdit(int $id, string $power, int $userId): bool
    {
        try {
            // 验证成员
            $teamData = (new KbKnowTeam())->where(['id'=>$id])->findOrEmpty()->toArray();
            if (!$teamData) {
                throw new Exception('该成员不存在');
            }

            // 知识库验证
            $know = (new KbKnow())->where(['id'=>$teamData['kb_id']])->findOrEmpty()->toArray();
            if (!$know) {
                throw new Exception('知识库不存在了!');
            }

            if (!$know['is_enable']) {
                throw new Exception('知识库已被禁用!');
            }

            // 权限验证 (不是拥有者则验证权限)
            $team = (new KbKnowTeam())->where(['kb_id'=>$know['id'], 'user_id'=>$userId])->findOrEmpty()->toArray();
            if ($know['user_id'] !== $userId) {
                if (!$team) {
                    throw new Exception('抱歉,您不是团队成员,无权操作');
                }
                if ($team['power'] > KnowEnum::POWER_ALL) {
                    throw new Exception('权限不足,无权操作!');
                }
            }

            KbKnowTeam::update([
                'power' => $power,
                'update_time' => time()
            ], ['id'=>$id]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 团队成员删除
     * @param int $id
     * @param int $userId
     * @return bool
     * @author kb
     */
    public static function teamDel(int $id, int $userId): bool
    {
        try {
            // 验证成员
            $team = (new KbKnowTeam())->where(['id'=>$id])->findOrEmpty()->toArray();
            if (!$team) {
                throw new Exception('该成员不存在');
            }

            // 验证知识库
            $know = (new KbKnow())->where(['id'=>$team['kb_id']])->findOrEmpty()->toArray();
            if (!$know) {
                throw new Exception('知识库不存在!');
            }
            if (!$know['is_enable']) {
                throw new Exception('知识库已被禁用!');
            }

            // 验证权限
            $team = (new KbKnowTeam())->where(['kb_id'=>intval($know['id']), 'user_id'=>$userId])->findOrEmpty()->toArray();
            if ($know['user_id'] !== $userId) {
                if (!$team) {
                    throw new Exception('抱歉,您不是团队成员,无权操作');
                }
                if ($team['user_id'] !== $userId and $team['power'] > KnowEnum::POWER_ALL) {
                    throw new Exception('权限不足,无权操作!');
                }
            }

            KbKnowTeam::destroy($id);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @param int $kbId
     * @param int $userId
     * @param int $power
     * @return int
     * @throws Exception
     */
    public static  function checkKbPower(int $kbId, int $userId, int $power = KnowEnum::POWER_EDIT): int
    {
        // 管理权: [所有权限]
        // 编辑权: [仅可对知识库录入自己的数据/或删除自己的数据, 其它人不可以动]
        // 查看权: [仅可查看信息,无任何其它权限]

        $modelKbKnow = new KbKnow();
        $modelKbKnowTeam = new KbKnowTeam();

        // 找出知识库数据
        $know = $modelKbKnow->where(['id'=>$kbId])->findOrEmpty()->toArray();
        if (!$know) {
            throw new Exception('知识库异常,请刷新页面!');
        }

        if (!$know['is_enable']) {
            throw new Exception('知识库被禁用,禁止操作!');
        }

        // 如果不是拥有者
        if ($know['user_id'] !== $userId) {
            $team = $modelKbKnowTeam->where(['kb_id'=>$know['id'], 'user_id' => $userId])->findOrEmpty()->toArray();
            if (!$team) {
                throw new Exception('您不具备任何权限进行操作!');
            }

            if ($team['power'] > $power) {
                $error = [1=>'管理者无权限操作!', 2=>'编辑者无权限操作!', 3=>'查看者无权限操作!'];
                throw new Exception($error[$team['power']]??'无权限操作!');
            }

            return $team['power'];
        }

        return KnowEnum::POWER_ALL;
    }

    /**
     * @notes 向量内容检索
     * @throws Exception
     * @author kb
     */
    public static function embContentSearch($robotId,$content): string
    {
        $robot = KbRobot::where('id', $robotId)->where('is_enable',1)->where('kb_type',2)->findOrEmpty();
        if ($robot->isEmpty()){
            throw new Exception('机器人信息错误');
        }
        $kb_ids = explode(',', $robot['kb_ids']);
        $kbIds = [];
            $modelKbKnow = new KbKnow();
            foreach ($kb_ids as $kb_id) {
                $know = $modelKbKnow->where(['id'=>$kb_id])->findOrEmpty()->toArray();
                if (!$know) {
                    throw new Exception('知识库不存在了!');
                }
                $kbIds[] = intval($kb_id);
            }
            // 接收参数
            $question       = $content;
            $searchMode     = $robot['search_mode'] ?? 'similar';
            $searchTokens   = $robot['search_tokens'] ?? 3000;
            $searchSimilar  = isset($robot['search_similar']) ? (float)sprintf("%.1f", $robot['search_similar']) : 0.5;
            $rankingStatus  = 0;
            $rankingScore   = isset($robot['ranking_score']) ? (float)sprintf("%.1f", $robot['ranking_score']) : 0.5;
            $embModels      = '3:3';

            $questions = [$question];
            // 发起检索
            $results = [];
            foreach ($questions as $query) {
                if ($searchMode == 'similar') {
                    $lists = RecallKnow::embeddingRecall($embModels, $query, $kbIds, $robot['user_id']);
                    $results[] = ['k'=>60, 'list'=>$lists];
                } elseif ($searchMode == 'full') {
                    $lists = RecallKnow::fullTextRecall($query, $kbIds);
                    $results[] = ['k'=>60, 'list'=>$lists];
                } elseif ($searchMode == 'mix') {
                    $lists = RecallKnow::mixedRecall($embModels, $query, $kbIds, $robot['user_id']);
                    $results[] = ['k'=>60, 'list'=>$lists];
                }
            }

            // 结果处理(1): RRF融合
            $results = RecallUtils::rrfConcatResults($results);

            // 结果处理(2): 重排模型
            $similar = $searchSimilar;
            if ($rankingStatus and $results) {
                $similar = $rankingScore;
            }

            // 结果处理(3): 相似度过滤
            $results = RecallUtils::filterMaxScore($results, $searchMode, $rankingStatus, $similar);

            // 结果处理(4): 过滤最大Tokens
            $pgList = RecallUtils::filterMaxTokens($results, $searchTokens);

            $searchContent = '对"'.$content.'"进行知识库检索'."\n";
            $num = 1;

            foreach ($pgList as $val) {
                if (!isset($val['emb_score']) || $val['emb_score'] < $searchSimilar){
                    continue;
                }
                $searchContent .= '知识库检索出的内容'.$num.":".$val['question']." \n";
                if (!empty($val['answer'])){
                    $searchContent .= '你的第'.$num.'参考回复是:'.$val['answer']."。\n";
                }else{
                    $searchContent .= '内容'.$num."未设置回复，结合上下文，你来用简短的答案自行补充回复。\n";
                }

                $num++;
            }
            $searchContent .= '知识库检索完毕。';
            RecallKnow::destroy();
            return $searchContent;
    }


    /**
     * @notes AI聊天绑向量知识库内容检索
     * @throws Exception
     * @author kb
     */
    public static function embAiChatSearch($KbId,$content): string
    {
        $know = KbKnow::where('id', $KbId)->where('is_enable',1)->findOrEmpty();
        if ($know->isEmpty()){
            throw new Exception('知识库信息错误');
        }

        // 接收参数
        $kbIds = [$KbId];
        $question       = $content;
        $searchMode     = 'similar';
        $searchTokens   = 3000;
        $searchSimilar  = 0.5;
        $rankingStatus  = 0;
        $rankingScore   = 0.5;
        $embModels      = '3:3';

        $questions = [$question];
        // 发起检索
        $results = [];
        foreach ($questions as $query) {
            if ($searchMode == 'similar') {
                $lists = RecallKnow::embeddingRecall($embModels, $query, $kbIds, $know['user_id']);
                $results[] = ['k'=>60, 'list'=>$lists];
            } elseif ($searchMode == 'full') {
                $lists = RecallKnow::fullTextRecall($query, $kbIds);
                $results[] = ['k'=>60, 'list'=>$lists];
            } elseif ($searchMode == 'mix') {
                $lists = RecallKnow::mixedRecall($embModels, $query, $kbIds, $know['user_id']);
                $results[] = ['k'=>60, 'list'=>$lists];
            }
        }

        // 结果处理(1): RRF融合
        $results = RecallUtils::rrfConcatResults($results);

        // 结果处理(2): 重排模型
        $similar = $searchSimilar;
        if ($rankingStatus and $results) {
            $similar = $rankingScore;
        }

        // 结果处理(3): 相似度过滤
        $results = RecallUtils::filterMaxScore($results, $searchMode, $rankingStatus, $similar);

        // 结果处理(4): 过滤最大Tokens
        $pgList = RecallUtils::filterMaxTokens($results, $searchTokens);

        if (!$pgList) {
            return '未找到匹配内容';
        }

        $searchContent = '对"'.$content.'"进行知识库检索'."\n";
        $num = 1;
        foreach ($pgList as $val) {

            $searchContent .= '知识库检索出的内容'.$num.":".$val['question']." \n";
            if (!empty($val['answer'])){
                $searchContent .= '你的第'.$num.'参考回复是:'.$val['answer']."。\n";
            }else{
                $searchContent .= '内容'.$num."未设置回复，结合上下文，你来用简短的答案自行补充回复。\n";
            }

            $num++;
        }
        $searchContent .= '知识库检索完毕。';
        RecallKnow::destroy();
        return $searchContent;
    }
}