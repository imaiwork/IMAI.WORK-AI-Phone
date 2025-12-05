<?php

namespace app\api\logic\kb;

use app\common\logic\BaseLogic;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotCategory;
use app\common\model\kb\KbRobotSession;
use app\common\model\kb\KbRobotSquare;
use app\common\service\ConfigService;
use Exception;
use think\db\exception\DbException;

/**
 * 机器人广场逻辑类
 */
class KbSquareLogic extends BaseLogic
{
    /**
     * @notes 机器人分类列表
     * @return array
     * @throws DbException
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\ModelNotFoundException
     */
    public static function category(): array
    {
        $modelKbRobotCategory = new KbRobotCategory();
        return $modelKbRobotCategory
            ->field(['id,name,image'])
            ->where(['is_enable'=>1])
            ->order('sort desc, id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 机器人广场列表
     * @param array $get
     * @return array
     * @throws DbException
     */
    public static function lists(array $get): array
    {
        $pageNo   = intval($get['page_no'] ?? 1);
        $pageSize = intval($get['page_size'] ?? 25);
        $cid      = intval($get['cid'] ?? 0);
        $keyword   = $get['keyword'] ?? '';

        $where[] = ['KRS.is_show', '=', 1];
        if ($cid) {
            $where[] = ['KRS.cate_id', '=', $cid];
        }
        if ($keyword) {
            $where[] = ['KR.name', 'like', '%'.$get['keyword'].'%'];
        }

        // 查询机器人
        $modelKbRobotSquare = new KbRobotSquare();
        $lists = $modelKbRobotSquare
            ->alias('KRS')
            ->field(['KRS.id,KRS.robot_id,KR.name,U.nickname as author,KR.image,KR.intro,KR.is_enable,KRS.create_time'])
            ->join('kb_robot KR', 'KR.id = KRS.robot_id')
            ->join('user U', 'U.id = KRS.user_id')
            ->where($where)
            ->order('KRS.sort desc, KRS.id desc')
            ->paginate([
                'page'      => $pageNo,
                'list_rows' => $pageSize,
                'var_page'  => 'page'
            ])->toArray();

        $showUser = ConfigService::get('robot_award', 'is_show_user');

        foreach ($lists['data'] as $key => $data){
            if(!$showUser){
                $lists['data'][$key]['author'] = '';
            }
        }

        return [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
            'count'     => $lists['total'],
            'lists'     => $lists['data']
        ] ?? [];
    }

    /**
     * @notes 机器人使用记录
     * @param int $userId
     * @return array
     * @throws DbException
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\ModelNotFoundException
     */
    public static function record(int $userId): array
    {
        $modelKbRobotSession = new KbRobotSession();
        $lists = $modelKbRobotSession
            ->alias('KRS')
            ->field(['KRS.id,KRS.robot_id,KR.image,KR.name,KR.is_public,KR.delete_time,KR.intro,KRS.create_time'])
            ->join('kb_robot KR', 'KR.id=KRS.robot_id')
            ->where(['KRS.user_id'=>$userId])
            ->where('KRS.square_id', '>', 0)
            ->order('KRS.id desc')
            ->select()
            ->toArray();

        $ids = [];
        $data = [];
        $robotIds = [];
        foreach ($lists as $item) {
            if (!$item['is_public'] or in_array($item['robot_id'], $robotIds) or $item['delete_time']) {
                $ids[] = $item['id'];
            } else {
                unset($item['delete_time']);
                $data[] = $item;
                $robotIds[] = $item['robot_id'];
            }
        }

        if ($ids) {
            KbRobotSession::destroy($ids);
        }

        return $data;
    }

    /**
     * @notes 使用机器人
     * @param int $squareId
     * @param int $userId
     * @return bool|array
     * @author kb
     */
    public static function add(int $squareId, int $userId): bool|array
    {
        $model = new KbRobotSession();
        $session = $model
            ->where(['user_id'=>$userId])
            ->where(['square_id'=>$squareId])
            ->findOrEmpty()
            ->toArray();

        if ($session) {
            return ['id'=>$session['id']];
        }

        try {
            $square = (new KbRobotSquare())->where(['id'=>$squareId])->findOrEmpty()->toArray();
            if (!$square) {
                throw new Exception('找不到对应的共享智能体!');
            }

            $kbRobot = (new KbRobot())
                ->field(['id,name,is_public'])
                ->where(['id'=>$square['robot_id']])
                ->findOrEmpty()
                ->toArray();

            if (!$kbRobot) {
                throw new Exception('智能体不存在');
            }

            if (!$kbRobot['is_public']) {
                throw new Exception('智能体未公开');
            }

            $sq = KbRobotSession::create([
                'user_id'     => $userId,
                'square_id'   => $squareId,
                'robot_id'    => $square['robot_id'],
                'name'        => $kbRobot['name'],
                'create_time' => time()
            ]);

            return ['id'=>$sq['id']];
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除使用记录
     * @param int $squareId
     * @param int $userId
     * @return bool
     */
    public static function del(int $squareId, int $userId): bool
    {
        try {
            $model = new KbRobotSession();
            $session = $model
                ->where(['user_id'=>$userId])
                ->where(['square_id'=>$squareId])
                ->findOrEmpty()
                ->toArray();

            if (!$session) {
                throw new Exception('数据不存在');
            }

            KbRobotSession::destroy($session['id']);

            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}