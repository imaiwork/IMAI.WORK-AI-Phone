<?php

namespace app\api\logic\catering;

use app\api\logic\ApiLogic;
use app\common\model\catering\CateringFranchise;

class CateringFranchiseLogic extends ApiLogic
{
    public static function get(array $params)
    {
        $franchise = CateringFranchise::where('id', $params['id'])
            ->append(['category_type_text', 'status_text'])
            ->where('status', 1)
            ->findOrEmpty()->toArray();

        if (!$franchise) {
            self::setError('招商项目不存在');
            return false;
        }
        $taskTypes = $franchise['task_types'] ?? [];
        $franchise['task_types_count'] = is_array($taskTypes) ? count($taskTypes) : 0;

        self::$returnData = $franchise;
        return true;
    }
}
