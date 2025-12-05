<?php

namespace app\adminapi\logic\kb;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\BaseLogic;
use app\common\logic\NoticeLogic;
use app\common\model\draw\DrawSquare;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotShareLog;
use app\common\model\kb\KbRobotSquare;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\service\ConfigService;
use Exception;
use think\facade\Db;

class KbSquareLogic extends BaseLogic
{
    /**
     * @notes 机器人广场列表
     * @param array $get
     * @return array
     * @author kb
     */
    public static function lists(array $get): array
    {
        $pageNo   = intval($get['page_no'] ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $cid      = intval($get['cid'] ?? 0);
        $name     = $get['name']    ?? '';
        $author   = $get['author']  ?? '';
        $is_show  = $get['is_show'] ?? -1;
        $start_time = $get['start_time'] ?? '';
        $end_time = $get['end_time'] ?? '';

        $where = [];
        if ($cid) {
            $where[] = ['KRS.cate_id', '=', $cid];
        }
        if ($name) {
            $where[] = ['KR.name', 'like', '%'.$name.'%'];
        }
        if ($author) {
            $where[] = ['U.sn|U.nickname', 'like', '%'.$author.'%'];
        }
        if ($is_show>=0) {
            $where[] = ['KRS.is_show', '=', $is_show];
        }
        if ($start_time) {
            $where[] = ['KRS.create_time', '>=', strtotime($start_time)];
        }
        if ($end_time) {
            $where[] = ['KRS.create_time', '<=', strtotime($end_time)];
        }

        $modelKbRobotSquare = new KbRobotSquare();
        $lists = $modelKbRobotSquare
            ->alias('KRS')
            ->field([
                'KRS.id,KRS.cate_id as cid,KRS.robot_id,KR.name,U.nickname as author,KC.name as category,KRS.verify_status,KRS.verify_result',
                'KRS.is_show,KR.image,KR.intro,KRS.sort,KR.create_time'
            ])
            ->leftJoin('kb_robot_category KC', 'KC.id = KRS.cate_id')
            ->join('kb_robot KR', 'KR.id = KRS.robot_id')
            ->join('user U', 'U.id = KR.user_id')
            ->where($where)
            ->order('KRS.sort desc, KRS.id desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        foreach ($lists['data'] as &$item) {
            $item['category'] = $item['category'] ?: '全部';
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $lists['data']
        ] ?? [];
    }

    /**
     * @notes 机器人广场编辑
     * @param array $post
     * @return bool
     * @author kb
     */
    public static function edit(array $post): bool
    {
        $modelKbRobotSquare = new KbRobotSquare();
        $modelKbRobotSquare->startTrans();
        try {
            $id      = intval($post['id']);
            $cid     = intval($post['cid']??0);
            $sort    = intval($post['sort']??0);
            $is_show = intval($post['is_show']??0);

            $square = $modelKbRobotSquare->where(['id'=>$id])->findOrEmpty()->toArray();
            if (!$square) {
                throw new Exception('记录已不存在了!');
            }

            KbRobotSquare::update([
                'cate_id' => $cid,
                'sort'    => $sort,
                'is_show' => $is_show,
                'update_time' => time()
            ], ['id'=>$id]);

            KbRobot::update([
                'cate_id'     => $cid,
                'update_time' => time()
            ], ['id'=>$square['robot_id']]);

            $modelKbRobotSquare->commit();
            return true;
        } catch (Exception $e) {
            $modelKbRobotSquare->rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 机器人广场删除
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function del(int $id): bool
    {
        $modelKbRobotSquare = new KbRobotSquare();
        try {
            $square = $modelKbRobotSquare->where(['id'=>$id])->findOrEmpty()->toArray();
            if (!$square) {
                throw new Exception('记录已不存在了!');
            }

            KbRobot::update([
                'is_public' => 0,
                'update_time' => time()
            ], ['id'=>$square['robot_id']]);

            KbRobotSquare::destroy($id);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 设置机器人广场状态
     * @param int $id
     * @param int $status
     * @return bool
     * @author kb
     */
    public static function setStatus(int $id, int $status): bool
    {
        try {
            $modelKbRobotSquare = new KbRobotSquare();
            $square = $modelKbRobotSquare->where(['id'=>$id])->findOrEmpty()->toArray();
            if (!$square) {
                throw new Exception('记录已不存在了!');
            }

            KbRobotSquare::update([
                'is_show' => $status
            ], ['id'=>$id]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 设置机器人广场排序
     * @param int $id
     * @param int $sort
     * @return bool
     * @author kb
     */
    public static function setSort(int $id, int $sort): bool
    {
        try {
            $modelKbRobotSquare = new KbRobotSquare();
            $square = $modelKbRobotSquare->where(['id'=>$id])->findOrEmpty()->toArray();
            if (!$square) {
                throw new Exception('记录已不存在了!');
            }

            KbRobotSquare::update([
                'sort' => $sort
            ], ['id'=>$id]);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 机器人审核
     * @param array $params
     * @return bool
     * @throws \think\db\exception\DbException
     * @author kb
     * @date 2024/7/25 14:57
     */
    public static function verifyStatus(array $params)
    {
        try {
            Db::startTrans();

            if(!isset($params['id'])){
                throw new Exception('请选择智能体');
            }
            if(!isset($params['verify_status'])){
                throw new Exception('请选择审核状态');
            }
            foreach ($params['id'] as $id) {
                $robotSquare = KbRobotSquare::where(['id'=>$id])->findOrEmpty()->toArray();
                if(empty($robotSquare)){
                    continue;
                }
                if($params['verify_status']){
//                    $shareLog = KbRobotShareLog::where(['user_id'=>$robotSquare['user_id'],'robot_id'=>$robotSquare['robot_id']])
//                        ->findOrEmpty();
                    //第一次分享获取的奖励
//                    if($shareLog->isEmpty()){
                        $dayNum   = ConfigService::get('robot_award', 'day_num');
                        $oneAward   = ConfigService::get('robot_award', 'one_award');
                        $shareNum = KbRobotSquare::where(['user_id'=>$robotSquare['user_id'],'verify_status'=>1])
                            ->whereDay('create_time')
                            ->count();
                        if ($dayNum > $shareNum  && $oneAward) {
                            User::update(['balance'=>['inc',$oneAward]],['id'=>$robotSquare['user_id']]);
                            // 记录账户流水
                            UserAccountLog::add(
                                $robotSquare['user_id'],
                                AccountLogEnum::UM_INC_ROBTO_SHARE,
                                AccountLogEnum::INC,
                                $oneAward
                            );
                            KbRobotShareLog::where(['square_id'=>$robotSquare['id']])->update([
                                'balance'  => $oneAward,
                            ]);
                        }
//                    }
                    KbRobot::where(['id'=>$robotSquare['robot_id']])->update(['is_public'=>1]);

                }

                KbRobotSquare::update([
                    'verify_status' => $params['verify_status'],
                    'verify_result' => $params['verify_result'],
                    'is_show' => $params['verify_status'] == 1 ? 1 : 0,
                ],['id'=>$id]);

                //添加信息通知
                NoticeLogic::addSquareNotice(
                    $robotSquare['user_id'],
                    7,
                    $params['verify_status'],
                    [
                        'square_id'     => $robotSquare['id'],
                        'robot_id'      => $robotSquare['robot_id'],
                        'verify_status' => $params['verify_status'],
                        'verify_result' => $params['verify_result'],
                        'balance'       => $oneAward ?? 0,
                    ]
                );
            }

            Db::commit();
            return true;
        }catch (Exception $e){
            Db::rollback();
            return $e->getMessage();
        }



    }

    /**
     * @notes 获取配置
     * @return array
     * @author kb
     * @date 2024/7/26 15:34
     */
    public static function getConfig(){
        return [
            'is_show_user' =>         ConfigService::get('robot_award', 'is_show_user'),
            'auto_audit' =>         ConfigService::get('robot_award', 'auto_audit'),
        ];

    }

    /**
     * @notes 设置配置
     * @param $params
     * @return void
     * @author kb
     * @date 2024/7/26 15:35
     */
    public static function setConfig($params){
        ConfigService::set('robot_award', 'is_show_user', $params['is_show_user']);
        ConfigService::set('robot_award', 'auto_audit',$params['auto_audit']);


    }
}