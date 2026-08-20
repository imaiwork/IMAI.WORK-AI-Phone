<?php

namespace app\api\logic\tutorial;

use app\api\logic\ApiLogic;
use app\common\model\tutorial\Tutorial;
use app\common\model\tutorial\TutorialCategory;

class TutorialLogic extends ApiLogic
{
    public static function get(array $params)
    {
        $tutorial = Tutorial::where('id', $params['id'])
            ->findOrEmpty()->toArray();

        if (!$tutorial) {
            self::setError('教程不存在');
            return false;
        }

        $tutorial['cate_name'] = TutorialCategory::where('id', $tutorial['tutorial_category_id'])->value('name');
        $tutorial['main_type_text'] = $tutorial['main_type'] == 1 ? '视频' : '图片';

        self::$returnData = $tutorial;
        return true;
    }
}
