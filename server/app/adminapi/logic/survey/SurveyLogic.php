<?php


namespace app\adminapi\logic\survey;


use app\common\model\survey\Surveys;
use app\common\logic\BaseLogic;

/**
 * 调查问卷逻辑
 * Class SurveyLogic
 * @package app\adminapi\logic\survey   
 */
class SurveyLogic extends BaseLogic
{

    /**
     * @notes 删除
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/10/19 9:53
     */
    public static function delete(array $data)
    {
        try {

            if (is_string($data['id'])) {
                HumanVoice::destroy(['id' => $data['id']]);
            } else {
                HumanVoice::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
