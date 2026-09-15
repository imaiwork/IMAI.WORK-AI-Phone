<?php

namespace app\api\logic\tutorial;

use app\api\logic\ApiLogic;
use app\common\model\tutorial\TutorialCategory;

class TutorialCategoryLogic extends ApiLogic
{
    public static function get(array $params)
    {
        $category = TutorialCategory::where('id', $params['id'])
            ->findOrEmpty()->toArray();

        if (!$category) {
            self::setError('教程分类不存在');
            return false;
        }

        self::$returnData = $category;
        return true;
    }
}
