<?php

namespace app\api\logic\sv;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvMediaManualTask;
use app\common\model\sv\SvMediaManualSetting;
use app\common\model\user\User;
use think\facade\Db;
use think\facade\Log;

/**
 * SvMediaManualTaskLogic
 * @desc 媒体手动任务逻辑处理
 */
class SvMediaManualTaskLogic extends SvBaseLogic
{
    /**
     * @desc 添加媒体手动任务
     * @param array $params
     * @return bool
     */
    public static function addSvMediaManualTask(array $params)
    {
        try {
            $params['user_id'] = self::$uid;
            
            // 验证手动设置是否存在
            $setting = SvMediaManualSetting::where('id', $params['manual_setting_id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if (!$setting) {
                self::setError('媒体手动设置不存在');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['topic', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $params[$field] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $params[$field] = json_encode([]);
                }
            }

            try {
                // 添加媒体手动任务
                $task = SvMediaManualTask::create($params);

                self::$returnData = $task->toArray();
                return true;
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取媒体手动任务详情
     * @param array $params
     * @return bool
     */
    public static function detailSvMediaManualTask(array $params)
    {
        try {
            // 检查媒体手动任务是否存在
            $task = SvMediaManualTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$task) {
                self::setError('媒体手动任务不存在');
                return false;
            }

            $data = $task->toArray();
            
            // 转换2个特定字段为数组
            $jsonFields = ['topic', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                } else {
                    $data[$field] = [];
                }
            }
            
            // 添加状态描述
            $statusMap = [
                0 => '未发布',
                1 => '已发布', 
                2 => '发布失败',
                3 => '发布中'
            ];
            $data['status_text'] = $statusMap[$data['status']] ?? '未知状态';
            
            // 添加媒体类型描述
            $mediaTypeMap = [
                1 => '视频',
                2 => '图片'
            ];
            $data['media_type_text'] = $mediaTypeMap[$data['media_type']] ?? '未知类型';
            
            // 返回媒体手动任务信息
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新媒体手动任务
     * @param array $params
     * @return bool
     */
    public static function updateSvMediaManualTask(array $params)
    {
        try {
            // 检查媒体手动任务是否存在
            $task = SvMediaManualTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$task) {
                self::setError('媒体手动任务不存在');
                return false;
            }

            // 验证手动设置是否存在
            if (!empty($params['manual_setting_id'])) {
                $setting = SvMediaManualSetting::where('id', $params['manual_setting_id'])
                    ->where('user_id', self::$uid)
                    ->findOrEmpty();
                if (!$setting) {
                    self::setError('媒体手动设置不存在');
                    return false;
                }
            }

            // 预处理JSON字段
            $jsonFields = ['topic', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $params[$field] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else if (isset($params[$field])) {
                    $params[$field] = json_encode([]);
                }
            }

            try {
                // 更新媒体手动任务
                SvMediaManualTask::where('id', $params['id'])->update($params);
                self::$returnData = SvMediaManualTask::find($params['id'])->toArray();
                return true;
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除媒体手动任务
     * @param array $params
     * @return bool
     */
    public static function deleteSvMediaManualTask(array $params)
    {
        try {
            // 检查媒体手动任务是否存在
            $task = SvMediaManualTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$task) {
                self::setError('媒体手动任务不存在');
                return false;
            }

            try {
                // 删除媒体手动任务
                SvMediaManualTask::destroy($params['id']);
                return true;
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 生成抖音H5二维码
     * @param array $params
     * @return bool
     */
    public static function publish(array $params)
    {
        try {
            // 检查必要参数
            if (empty($params['id'])) {
                self::setError('任务ID不能为空');
                return false;
            }

            // 获取任务详情
            $task = SvMediaManualTask::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('媒体手动任务不存在');
                return false;
            }
            // 获取任务数据并转换JSON字段
            $taskData = $task->toArray();
            $jsonFields = ['topic', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($taskData[$field])) {
                    $taskData[$field] = json_decode($taskData[$field], true);
                } else {
                    $taskData[$field] = [];
                }
            }
            $taskData['subtitle'] = mb_substr( $taskData['subtitle'] ?? '', 0, 30, 'UTF-8');
            $taskData['title'] = mb_substr( $taskData['title'] ?? '', 0, 30, 'UTF-8');
            // 构建抖音H5发布内容所需的参数
            $douyinParams = [
                'short_title' => $taskData['title'] ?? '',
                'title' => $taskData['subtitle'] ?? ''
            ];
            $title_length = mb_strlen($taskData['subtitle'] ?? '', 'UTF-8') + 2;
            foreach ($taskData['topic'] as $key => $value) {
                 $douyinParams['title_hashtag_list'][] = [
                    'start' =>  $title_length,
                    'name' => $value,
                 ];
            }

            if(!empty($taskData['topic'])){
                $douyinParams['title_hashtag_list'] = json_encode($douyinParams['title_hashtag_list'],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if($taskData['media_type'] == 1){
                  $douyinParams['video_path'] = $taskData['media_url'];
            }else{
                $douyinParams['image_path'] = $taskData['media_url'];
            }

            Log::channel('douyin')->write('媒体信息：' . json_encode($douyinParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // 使用DouyinService生成抖音H5参数
            $douyinService = new \app\common\service\media\DouyinService();
            // 生成签名参数（包含nonce_str, timestamp, ticket, sign等）
            $signParams = $douyinService->generateSignParams($douyinParams);
            if (empty($signParams) || !is_array($signParams) || count($signParams) === 0) {
                self::setError('获取抖音H5参数失败');
                return false;
            }
            $unit = TokenLogService::checkToken($task->user_id, 'douyin_js');

            if ($signParams && $unit > 0) {
                User::userTokensChange($task->user_id, $unit);
                $extra = ['发布平台' => '抖音H5', '算力单价' => $unit, '实际消耗算力' =>$unit];
                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_DOUYIN_JS, $unit, $task->id, $extra);  
            }
            Log::channel('douyin')->write('生成分享二维码前参数：' . json_encode($signParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $signParams['share_to_type'] = 0;
            $shareSchemaUrl = 'snssdk1128://openplatform/share?share_type=h5&' . http_build_query($signParams);
            Log::channel('douyin')->write('生成分享二维码连接参数：' . $shareSchemaUrl);
            $output = 'uploads/appqrcode/' . date('YmdHis') .'/';
            $root_path = public_path();
            // 创建目录（如果不存在）
            if (!is_dir(dirname($root_path . $output))) {
                mkdir(dirname($root_path . $output), 0777, true);
            }
    
            $shareUrl = \app\common\service\QrCodeService::save(
                $shareSchemaUrl,
                $output,
                600,
                20
            );
            $url = config('app.app_host') . '/share/index.html?share_url=' .   $shareSchemaUrl;

            $output = 'uploads/h5qrcode/' . date('YmdHis') .'/';
            if (!is_dir(dirname($root_path . $output))) {
                mkdir(dirname($root_path . $output), 0777, true);
            }

            $qrUrl = \app\common\service\QrCodeService::save(
                $url,
                $output,
                600,
                20
            );
            // 构建返回数据
            $result = [
                'h5_url' => $qrUrl,
                'app_url' => $shareUrl
            ];

            self::$returnData = $result;
            return true;

        } catch (\Exception $e) {
            self::setError('生成抖音H5二维码失败：' . $e->getMessage());
            return false;
        }
    }




}