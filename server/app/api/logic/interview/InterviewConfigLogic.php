<?php

namespace app\api\logic\interview;

use app\common\logic\BaseLogic;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewConfig;
use app\common\model\interview\InterviewJob;
use app\common\service\ConfigService;

class InterviewConfigLogic extends BaseLogic
{


    /**
     * @notes  编辑资讯分类
     * @param array $params
     * @return bool
     * @author heshihu
     * @date 2022/2/21 17:50
     */
    public static function edit(array $params): bool
    {
        try {
            $config = InterviewConfig::where(['user_id' => $params['user_id'], 'job_id' => $params['job_id']])->findOrEmpty();
            if ($config->isEmpty())
            {
                InterviewConfig::create($params);
            } else {
                InterviewConfig::update($params, ['id' => $config->id]);
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 详情
     * @param string $id
     * @return bool
     * @author L
     * @data 2024/6/29 10:30
     */
    public static function detail(array $params): bool
    {
        try {
            $result =  InterviewConfig::where('user_id', $params['user_id'])->where('job_id', $params['job_id'])->findOrEmpty();

            if ($result->isEmpty()) {

                throw new \Exception('设置不存在');
            }

            self::$returnData = $result->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}