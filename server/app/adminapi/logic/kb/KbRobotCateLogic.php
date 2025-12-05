<?php

namespace app\adminapi\logic\kb;

use app\common\logic\BaseLogic;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotCategory;
use app\common\service\FileService;
use Exception;

class KbRobotCateLogic extends BaseLogic
{
    /**
     * @notes 所有分类
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     */
    public static function all(): array
    {
        $model = new KbRobotCategory();
        return $model
            ->withoutField('delete_time')
            ->order('sort desc, id desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 分类详情
     * @param int $id
     * @return array
     * @author kb
     */
    public static function detail(int $id): array
    {
        $model = new KbRobotCategory();
        return $model
            ->withoutField('delete_time')
            ->where(['id'=>$id])
            ->findOrEmpty()
            ->toArray();
    }

    /**
     * @notes 分类新增
     * @param array $post
     * @return bool
     * @author kb
     */
    public static function add(array $post): bool
    {
        try {
            $cate = (new KbRobotCategory())->where(['name'=>$post['name']])->findOrEmpty();
            if (!$cate->isEmpty()) {
                throw new Exception('该分类已存在了,换一个吧');
            }

            KbRobotCategory::create([
                'name'      => $post['name'],
                'image'     => FileService::setFileUrl($post['image']??''),
                'sort'      => $post['sort']??0,
                'is_enable' => $post['is_enable']??0,
            ]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 分类编辑
     * @param array $post
     * @return bool
     * @author kb
     */
    public static function edit(array $post): bool
    {
        try  {
            $cate = (new KbRobotCategory())
                ->where('id', '<>', intval($post['id']))
                ->where(['name'=>$post['name']])
                ->findOrEmpty();

            if (!$cate->isEmpty()) {
                throw new Exception('该分类已存在了,换一个吧');
            }

            KbRobotCategory::update([
                'name'      => $post['name'],
                'image'     => FileService::setFileUrl($post['image']??''),
                'sort'      => $post['sort']??0,
                'is_enable' => $post['is_enable']??0,
            ], ['id'=>intval($post['id'])]);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 分类编辑
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function del(int $id): bool
    {
        try {
            $cate = (new KbRobotCategory())->where(['id'=>$id])->findOrEmpty();
            if ($cate->isEmpty()) {
                throw new Exception('类目已不存在,请刷新页面');
            }

            $robot = (new KbRobot())->field(['id,name'])->where(['cate_id'=>$id])->findOrEmpty();
            if (!$robot->isEmpty()) {
                throw new Exception('该类目已被机器人《'.$robot['name'].'》使用,不可删除');
            }

            KbRobotCategory::destroy($id);
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 修改分类状态
     * @param int $id
     * @return bool
     * @author kb
     */
    public static function changeStatus(int $id): bool
    {
        try {
            $modelKbRobotCategory = new KbRobotCategory();
            $detail = $modelKbRobotCategory
                ->field(['id,is_enable'])
                ->where(['id'=>$id])
                ->findOrEmpty()
                ->toArray();

            if (!$detail) {
                throw new Exception('该分类已不存在了!');
            }

            KbRobotCategory::update([
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
}