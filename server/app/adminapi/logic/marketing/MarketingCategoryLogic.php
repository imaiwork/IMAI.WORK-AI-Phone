<?php


namespace app\adminapi\logic\marketing;

use app\common\logic\BaseLogic;
use app\common\model\marketing\MarketingCategory;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;

use think\facade\Db;

/**
 * 分类管理逻辑
 * Class MarketingCategoryLogic
 * @package app\adminapi\logic\marketing
 */
class MarketingCategoryLogic extends BaseLogic
{
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            $find = MarketingCategory::where('name', $params['name'])->findOrEmpty();
            if (!$find->isEmpty()) {
                throw new \Exception('分类名称已存在');
            }
            $params['create_time'] = time();
            $params['update_time'] = time();
            $category = MarketingCategory::create($params);
            self::$returnData = $category->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        Db::startTrans();
        try {
            $category = MarketingCategory::find($params['id']);
            self::$returnData = $category->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            if (isset($params['name'])) {
                $exist = MarketingCategory::where('name', $params['name'])->where('id', '<>', $params['id'])->findOrEmpty();
                if (!$exist->isEmpty()) {
                    throw new \Exception('分类名称已存在');
                }
            }


            $find = MarketingCategory::where('id', $params['id'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('该分类不存在');
            }
            $params['update_time'] = time();
            $find->save($params);


            self::$returnData = $find->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(array $params): bool
    {
        Db::startTrans();
        try {
            $find = MarketingCategory::where('id', $params['id'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('该分类不存在');
            }
            MarketingTemplate::where('category_id', $params['id'])->select()->each(function ($template) {
                MarketingTemplateSchedule::where('template_id', $template->id)->select()->delete();
                $template->delete();
            });

            $find->delete();
            self::$returnData = [];
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}
