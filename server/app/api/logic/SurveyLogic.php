<?php


namespace app\api\logic;


use app\common\service\ConfigService;
use app\common\model\survey\Surveys;
use app\common\model\user\User;


/**
 * 调查问卷逻辑
 * Class SurveyLogic
 * @package app\api\logic
 */
class SurveyLogic extends ApiLogic
{

    /**
     * @notes 检查用户是否填写过调查问卷
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/21 19:15
     */
    public static function check()
    {
        // 查询用户是否填写过调查问卷
        $survey = Surveys::withTrashed()->where('user_id', self::$uid)->findOrEmpty();

        // 获取调查问卷配置
        $surveyConfig = ConfigService::get('website', 'survey', []);

        if ($survey->isEmpty() && $surveyConfig['enable'] == 1) {

            // 获取用户上一次提醒时间
            $lastRemindTime = User::where('id', self::$uid)->value('last_survey_reminder_time');

            if (!$lastRemindTime || strtotime('+' . $surveyConfig['remind_days'] . ' days') > strtotime($lastRemindTime)) {

                //更新用户最后提醒时间
                User::where('id', self::$uid)->update(['last_survey_reminder_time' => time()]);

                // 需要填写问卷
                return true;
            }
        }

        return false;
    }

    /**
     * @notes 提交问卷
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/10/19 9:53
     */
    public static function add(array $params)
    {

        if (!isset($params['company_name']) || !isset($params['company_size'])) {

            self::setError('参数错误');
            return false;
        }

        //判断用户是否已填写过问卷
        $survey = Surveys::withTrashed()->where('user_id', self::$uid)->findOrEmpty();
        if (!$survey->isEmpty()) {
            self::setError('您已填写过问卷');
            return false;
        }

        $data = [
            'company_name'  => $params['company_name'],
            'company_size'  => $params['company_size'],
            'user_id'       => self::$uid,
            'create_time'   => time(),
        ];

        $result = Surveys::create($data);

        // 更新企业用户类型
        if ($params['company_size'] != 1) {

            User::where('id', self::$uid)->update(['user_type' => 1]);
        }

        return $result;
    }
}
