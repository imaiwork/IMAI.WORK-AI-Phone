<?php

namespace app\api\logic\sv;

use app\api\logic\service\TokenLogService;
use app\common\model\sv\SvCopywriting;
use app\common\model\sv\SvCopywritingContent;
use app\common\model\sv\SvSetting;
use app\common\model\sv\SvCopywritingTask;
use think\facade\Db;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use think\facade\Log;

/**
 * SvCopywritingLogic
 * @desc 文案逻辑处理
 */
class SvCopywritingLogic extends SvBaseLogic
{
    /**
     * @desc 添加文案
     * @param array $params
     * @return bool
     */
    const KEYWORD_TO_TITLE = 'keywordToTitle'; //关键词转标题
    const KEYWORD_TO_SUBTITLE = 'keywordToSubtitle'; //关键词转副标题
    const KEYWORD_TO_COPYWRITING = 'keywordToCopywriting'; //关键词转副文案
    const KEYWORD_TO_DETAIL = 'keywordToDetail'; //详情

    public static function addSvCopywriting(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            // 开启事务
            Db::startTrans();
            try {
                // 添加文案
                if ($params['id'] > 0){
                    $copywriting = SvCopywriting::where('id',$params['id'])
                        ->where('user_id', $params['user_id'])
                        ->findOrEmpty();
                    if ($copywriting->isEmpty()){
                        throw new \Exception('文案不存在');
                    }
                    SvCopywriting::where('id',$params['id'])
                        ->where('user_id', $params['user_id'])
                        ->update(['keyword'=> $params['keyword'],'total_num'=> $params['total_num']]);
                }else{
                    $copywriting = SvCopywriting::create($params);
                }
                $params['status'] = $params['channel'] == 2 ? 0 : $params['status'];
                // 如果状态为待处理，则创建任务
                if ($params['status'] == 0) {
                    // 准备基础任务数据
                    $baseTaskData = [
                        'copywriting_id' => $copywriting->id,
                        'status' => 1,
                        'tries' => 0,
                        'content' => $params['keyword'],
                        'user_id' => $params['user_id'],
                        'create_time' => time(),
                        'update_time' => time()
                    ];

                    $tasks = [];

                    if ($params['add_type'] == 0) {
                        // 创建三种类型的任务
                        $taskTypes = [
                            ['type' => 1, 'scene' => self::KEYWORD_TO_TITLE],
                            ['type' => 2, 'scene' => self::KEYWORD_TO_SUBTITLE],
                        ];

                        foreach ($taskTypes as $type) {
                            $res = self::prepareTaskData($baseTaskData, $type['type'], $type['scene'], $copywriting, $params);
                            if (isset($res['code']) && $res['code'] == 10005) {
                                throw new \Exception( $res['message']);
                            }
                            if ($params['channel'] == 2 ){
                                $res['status'] = 2;
                            }
                            $tasks[] = $res;
                        }
                    } else {
                        // 创建单个类型的任务
                        $scene = self::getSceneByType($params['add_type']);
                        $res = self::prepareTaskData($baseTaskData, $params['add_type'], $scene, $copywriting, $params);
                        if (isset($res['code']) && $res['code'] == 10005) {
                            throw new \Exception( $res['message']);
                        }
                        if ($params['channel'] == 2 ){
                            $res['status'] = 2;
                        }
                        $tasks[] = $res;
                    }

                    // 批量保存任务
                    (new SvCopywritingTask())->saveAll($tasks);
                    $copywriting->status = 1;
                    if ($params['channel'] == 2 ){
                        $copywriting->status = 2;
                    }
                    $copywriting->save();
                }
                foreach ($tasks as &$task){
                    $task['response_content'] = json_decode($task['response_content'], JSON_UNESCAPED_UNICODE);
                }

                self::$returnData = $tasks;
                Db::commit();
                // 返回文案信息

                return true;
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 根据类型获取场景
     * @param int $type
     * @return string
     */
    private static function getSceneByType(int $type): string
    {
        return match ($type) {
            1 => self::KEYWORD_TO_TITLE,
            2 => self::KEYWORD_TO_SUBTITLE,
            default => self::KEYWORD_TO_COPYWRITING,
        };
    }

    /**
     * @desc 准备任务数据
     * @param array $baseTaskData
     * @param int $type
     * @param string $scene
     * @param SvCopywriting $copywriting
     * @param array $params
     * @return array
     */
    private static function prepareTaskData(array $baseTaskData, int $type, string $scene, SvCopywriting $copywriting, array $params): array
    {
        $taskData = array_merge($baseTaskData, ['type' => $type]);
        $taskData['task_id'] = generate_unique_task_id();

        $requestData = [
            'id' => $copywriting->id,
            'targetCount' => $params['total_num'],
            'keywords' => $taskData['content'],
            'channel' => $params['channel'],
            'description' => $taskData['content'],
            'notifyUrl' => '/api/sv.copywriting/notify'
        ];

        $response = self::requestUrl($requestData, $scene, $taskData['user_id'], $taskData['task_id']);
        if (isset($response['code']) && $response['code'] == 10005) {
           return $response;
        }

        if ( $params['channel'] == 2){
            $uid = self::$uid;
            $contents = [];
            foreach ($response['content'] as $content){
                $contents[] = [
                    'user_id' => $uid,
                    'copywriting_id' => $copywriting->id,
                    'type' => $type,
                    'channel' => $params['channel'],
                    'create_time' => time(),
                    'create_time' => time(),
                    'content'=> $content,
                ];
            }
            if(count( $contents )>0){
                $result =  (new SvCopywritingContent())->saveAll( $contents);
                $insertIds = [];
                foreach ($result as $key => $item) {
                    $response['content'][$key] = [
                        'id' => $item->id,
                        'content' => $response['content'][$key]
                    ];
                }
            }
        }
        $taskData['response_content'] = json_encode($response);

        return $taskData;
    }

    /**
     * @desc 获取文案详情
     * @param array $params
     * @return bool
     */
    public static function detailSvCopywriting(array $params)
    {
        try {
            // 检查文案是否存在
            $copywriting = SvCopywriting::where('id', $params['id'])->where('user_id',self::$uid)->findOrEmpty();
            if (!$copywriting) {
                self::setError('文案不存在');
                return false;
            }

            $params['type'] = $params['type'] ?? 1;
            $SvCopywritingContent =SvCopywritingContent::where('copywriting_id', $params['id'])
            ->where('type', $params['type'])->select();
         
            // 返回文案信息
            self::$returnData = $SvCopywritingContent->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除文案
     * @param array $params
     * @return bool
     */
    public static function deleteSvCopywriting(array $params)
    {
        try {
            // 检查文案是否存在
            $copywriting = SvCopywriting::where('id', $params['id'])->where('user_id',self::$uid)->findOrEmpty();
            if (!$copywriting) {
                self::setError('文案不存在');
                return false;
            }

            // 删除文案
            SvCopywriting::destroy($params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

     /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param string $taskId
     * @return array
     * @throws \Exception
     */
    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Sv();
        [$tokenScene, $tokenCode] = match ($scene) {
            self::KEYWORD_TO_TITLE => ['keyword_to_title', AccountLogEnum::KEYWORD_TO_TITLE],
            self::KEYWORD_TO_SUBTITLE => ['keyword_to_subtitle', AccountLogEnum::KEYWORD_TO_SUBTITLE],
            self::KEYWORD_TO_COPYWRITING => ['keyword_to_copywriting', AccountLogEnum::KEYWORD_TO_COPYWRITING],
            self::KEYWORD_TO_DETAIL => ['keyword_to_detail', ''],
        };  
        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);

        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        switch ($scene) {
      
            case self::KEYWORD_TO_TITLE:
                $response = $requestService->title($request);
                break;
            case self::KEYWORD_TO_SUBTITLE:
                $response = $requestService->subtitle($request);
                break; 
            case self::KEYWORD_TO_COPYWRITING:
                $response = $requestService->text($request);
                break; 
            case self::KEYWORD_TO_DETAIL:
                $response = $requestService->detail($request);
                break; 
            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = $unit;
            if ($points > 0) {

                $extra = [];

                //合成视频按时长扣费
                if (in_array($scene, [
                   self::KEYWORD_TO_TITLE, self::KEYWORD_TO_SUBTITLE,
                   self::KEYWORD_TO_COPYWRITING
                ])) {

                    $count = $response['data']['image_count'] ?? 1;

                    $points = round($count * $unit,2);

                    $extra = ['总条数' => $count, '算力单价' => $unit, '实际消耗算力' => $points];
                }

                //token扣除
                User::userTokensChange($userId, $points);

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        if(isset($response['code']) && $response['code'] == 10005){
            return $response;
        }
        return $response['data'] ?? [];
    }


    public static function notify(array $params, $status){


        $copywriting = SvCopywriting::where('id', $params['id'])->where('user_id', $params['user_id'])
        ->where('status',1)
        ->find();
       
        if (!$copywriting) {
            self::setError('文案不存在');
            return false;
        }
        $copywritingTask = SvCopywritingTask::where('task_id', $params['task_id'])
        ->where('status',1)
        ->find();
        if (!$copywritingTask) {
            self::setError('任务不存在');
            return false;
        }
        $id =  $params['MessageBody']['JobId']; 
        $requestData = [
            'id' => $id,
            'type' => $copywritingTask->type
        ];
        if($status == 'Finished'){
           
            $response = self::requestUrl($requestData, self::KEYWORD_TO_DETAIL, $params['user_id'], $params['task_id']);
         
            $copywritingcontent = [];
            $contents = json_decode($response['content'], true);
            foreach ( $contents as $content) {
                $copywritingcontent[] = [
                    'user_id'=>$params['user_id'],
                    'copywriting_id'=>$params['id'],
                    'type'=> $copywritingTask->type,
                    'content'=> $content
                ];
            }
            (new SvCopywritingContent())->saveAll($copywritingcontent);

            $copywritingTask->status = 2;
            $copywritingTask->save();
            $copywriting->success_num = $copywriting->success_num + 1;
            if( $copywriting->add_type != 0 ){
                $copywriting->status = 2;
            } 
            if( $copywriting->add_type == 0 && $copywriting->success_num == 3){
                $copywriting->status = 2;
            }
            $copywriting->save(); 
        }

        if($status == 'Failed'){
            
            $userId =  $params['user_id'];
            $taskId = $params['task_id'];
            $alltype = [
                '1' => AccountLogEnum::KEYWORD_TO_TITLE,
                '2' =>  AccountLogEnum::KEYWORD_TO_SUBTITLE,
                '3' =>  AccountLogEnum::KEYWORD_TO_COPYWRITING
            ];
            $typeID = $alltype[$copywritingTask['type']]; 
            $response = self::requestUrl($requestData, self::KEYWORD_TO_DETAIL, $params['user_id'], $params['task_id']);
            if( $response['State'] == 'Failed'){

                  //查询是否已返还
                if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() == 0) {

                    $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;

                    AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                }
                $copywritingTask->status = 3;
                $copywritingTask->save();

                $copywriting->error_num = $copywriting->error_num + 1;
                $num =  $copywriting->error_num  +  $copywriting->success_num ;
                if($num == 3){
                    $copywriting->status = 3;
                    $copywriting->save(); 
                }
            }
            
        
        }

    }


    public static function addSvCopywritingName(array $params)
    {
        try {
            $params['user_id'] = self::$uid;
            $params['status'] = 2;

            $item = [
              '0'=>'内容文案',
              '1'=>'标题',
              '2'=>'副标题',
              '3'=>'口播文案'
            ];
            $params['name'] = $item[$params['add_type']] . ' '. date('Y-m-d H:i', time());
            // 开启事务
            try {
                // 添加文案
                $copywriting = SvCopywriting::create($params);
                self::$returnData = $copywriting->toArray();;
                return true;
            } catch (\Exception $e) {
                throw $e;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 更新文案内容
     * @param array $params
     * @return bool
     */
    public static function updateSvCopywriting(array $params)
    {
        try {
            // 检查文案内容是否存在
            $content = SvCopywriting::where('id', $params['id'])->where('user_id',self::$uid)->find();
            if (!$content) {
                self::setError('文案内容不存在');
                return false;
            }
            $res = SvCopywriting::where('id', $params['id'])->update($params);
            if ($res){
                return true;
            }
            // 更新文案内容信息
            self::setError('更新失败');
            return false;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }




}