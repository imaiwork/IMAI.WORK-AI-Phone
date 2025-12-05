<?php

namespace app\adminapi\logic\interview;

use app\common\logic\BaseLogic;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewCv;
use app\common\model\interview\InterviewDialog;
use app\common\model\interview\InterviewFeedback;
use app\common\model\interview\InterviewJob;
use app\common\model\user\User;
use app\common\service\wechat\WeChatMnpService;

class InterviewLogic extends BaseLogic
{
    public static function detail($id)
    {
        try {
            $result =  Interview::where('id', $id)->findOrEmpty()->toArray();

            if (empty($result)) {

                throw new \Exception('面试不存在');
            }

            $cv = InterviewCv::where('user_id', $result['user_id'])->findOrEmpty()->toArray();
            $result['cv'] = $cv;

            $job = InterviewJob::where('id', $result['job_id'])->findOrEmpty()->toArray();
            $result['job'] = $job;

            $dialogs = InterviewDialog::where('interview_id', $result['id'])->json(['dialog'])->select()->toArray();
            $result['dialogs'] = $dialogs;

            $create_user = User::where('id', $job['user_id'])->findOrEmpty()->toArray();
            $result['create_user'] = $create_user;


            self::$returnData = $result;
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
    
    /**
     * @desc 岗位链接
     * @param array $params
     * @return array
     * @date 2025/2/14 10:42
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public static function jobLink(array $params): array
    {
        $path = '/pages/index/index';
        $query = 'user_id=' . $params['user_id'] . '&job_id=' . $params['job_id'];
        $result = (new WeChatMnpService)->urlLink($path, $query);
        return ['url' => $result['url_link']];
    }

    /**
     * @desc 我的岗位链接
     * @param array $params
     * @return array
     * @date 2025/2/14 10:58
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public static function myJobLink(array $params): array
    {
        $path = '/pages/index/index';
        $query = 'user_id=' . $params['user_id'];
        $result = (new WeChatMnpService)->urlLink($path, $query);

        return ['url' => $result['url_link']];
    }

    public static function delete($params)
    {
        if (is_string($params['id'])) {
            Interview::destroy(['id' => $params['id']]);
        } else {
            Interview::destroy($params['id']);
        }
    }

    public static function deleteFeedback($params)
    {
        if (is_string($params['id'])) {
            InterviewFeedback::destroy(['id' => $params['id']]);
        } else {
            InterviewFeedback::destroy($params['id']);
        }
    }
}