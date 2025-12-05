<?php

namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\hd\HdImage;
use app\common\model\hd\HdImageCases;
use app\common\model\hd\HdLog;
use app\common\model\user\User;
use app\common\service\FileService;
use think\facade\Log;


class HdLogic extends ApiLogic
{

    const HD_TXT2IMG = 'hd_txt_to_img'; //文生图
    const HD_TXT2POSTERIMG = 'hd_txt_to_posterimg'; //文生海报图

    const HD_TXT2IMG_STATUS = 'hd_txt_to_img_status'; //文生图状态
    const HD_TXT2POSTERIMG_STATUS = 'hd_txt_to_posterimg_status'; //文生海报图状态
    const HD_IMG2IMG = 'hd_img_to_img'; //图生图
    const HD_IMG2IMG_STATUS = 'hd_img_to_img_status'; //图生图状态
    const HD_TEMPLATE_LISTS = 'hd_template_lists'; //模板列表
    const HD_SHOP_IMG2IMG = 'hd_shop_img_to_img'; //商品图生图状态
    const HD_SHOP_IMG2IMG_STATUS = 'hd_shop_img_to_img_status'; //商品图生图状态
    const HD_SHOP_IMG_CUT = 'hd_shop_img_cut'; //商品图生图抠图
    const HD_AI_TRY = 'hd_ai_try'; //AI试衣生图
    const HD_AI_TRY_STATUS = 'hd_ai_try_status'; //AI试衣生图状态

    const VOLC_TXT2IMG = 'volc_txt_to_img'; //文生图
    const VOLC_TXT2POSTERIMG = 'volc_txt_to_posterimg'; //文生海报图
    
    const DB_TXT2IMG = 'volc_txt_to_img_v2'; //文生图
    const DB_IMG2IMG = 'volc_img_to_img_v2'; //图生图
    const DB_IMG2IMG_STATUS = 'volc_img_to_img_v2_status'; //图生图状态
    const DB_TXT2POSTERIMG = 'volc_txt_to_posterimg_v2'; //文生海报图
    /**
     * @desc 删除图片
     * @param array $data
     * @return true
     * @date 2024/7/6 15:59
     * @author dagouzi
     */
    public static function deleteImage(array $data)
    {
        try {

            if (is_string($data['log_id'])) {
                HdImage::destroy(['log_id' => $data['log_id']]);
                HdLog::destroy(['id' => $data['log_id']]);
            } else {
                HdImage::whereIn('log_id', $data['log_id'])->select()->delete();
                HdLog::whereIn('id', $data['log_id'])->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 保存日志
     * @param $type
     * @param $request_id
     * @param $params
     * @param $task_id
     * @param $sub_task_id
     * @return true
     * @date 2024/7/6 15:57
     * @author dagouzi
     */
    public static function saveLog($type, $request_id, $params, $task_id, $sub_task_id,$status = 0,$model = 1,$image = '')
    {

        if($image != ''){
            $image = FileService::downloadFileBySource($image, 'image');
        }

        $data = [
            'user_id' => self::$uid,
            'request_id' => $request_id,
            'task_status' => $status,
            'type' => $type,
            'model_type' => $model,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'task_id' => $task_id,
            'sub_task_ids' => json_encode($sub_task_id, JSON_UNESCAPED_UNICODE)
        ];
        $log = HdLog::create($data);

        foreach ($sub_task_id as $id) {
            $imageData = [
                'log_id' => $log->id,
                'image' => $image,
                'model_type' => $model,
                'sub_task_id' => $id,
                'task_status' => $status,
                'task_completion' => 0,
            ];
            HdImage::create($imageData);
        }
        return true;
    }

    /**
     * @desc 获取模板
     * @param $params
     * @return true
     * @date 2024/7/5 10:10
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function templates($params)
    {
        $page_index = $params['page'] ?? 1;
        $page_size  = max($params['page_size'] ?? 20, 100);

        $response = self::requestUrl([
            'page_index' => $page_index,
            'page_size'  => $page_size,
        ], self::HD_TEMPLATE_LISTS, self::$uid);

        self::$returnData = ['result' => $response];
        return true;
    }

    /**
     * @desc 提交商品图图生图任务
     * @param $params
     * @return bool
     * @date 2024/7/5 14:48
     * @throws \Exception
     * @author dagouzi
     */
    public static function segmentImage($params)
    {
        $request = [];

        if (!empty($params['prompt'])) {
            // 默认位置
            $defaultLocation = [
                'horizontal_direction'  => 'AlignLeft',
                'horizontal_proportion' => 0.5,
                'vertical_direction'    => 'AlignTop',
                'vertical_proportion'   => 0.5
            ];
            // 提示词
            $request['prompt']        = $params['prompt'];
            $request['location_desc'] = !empty($params['location_desc']) ? $params['location_desc'] : $defaultLocation;
        } else {
            $request['template_category'] = $params['template_category'];
            $request['template_name']     = $params['template_name'];
            $request['custom_template']   = $params['custom_template'] ?? true;
        }
        foreach ($params as $key => $value) {
            if (in_array($key, ['negative_prompt', 'resolution', 'img_count', 'size', 'style'])) {
                if (!empty($value)) {
                    $request[$key] = $value;
                }
            }
            if ($key == 'resolution' && !empty($value)) {
                foreach ($value as &$valueResolution) {
                    $valueResolution = intval($valueResolution);
                }
                $request['resolution'] = $value;
            }
            if ($key == 'ref_image' && !empty($value)) {
                foreach ($value as &$valueImg) {
                    $valueImg = self::imageToStream($valueImg);
                }
                $request['ref_image'] = $value;
            }
        }
        $request['image'] = self::imageToStream($params['image']);

        $response = self::requestUrl($request, self::HD_SHOP_IMG2IMG, self::$uid);

        if (!$response) {
            throw new \Exception('提交商品图生图任务发生错误');
        }
        self::saveLog(1, $response['request_id'], $params, $response['task_id'], $response['sub_task_ids']);

        self::$returnData = ['result' => $response];
        return true;
    }

    /**
     * @desc 提交ai试衣生图任务
     * @param $params
     * @return bool
     * @date 2024/7/5 14:48
     * @throws \Exception
     * @author dagouzi
     */
    public static function vton($params)
    {

        $request = [];

        // 组装数据
        foreach ($params['persons'] as $item) {
            $request['persons'][] = $item;
        }
        if (!empty($params['lower_clothes'])) {
            $request['lower_clothes'] = $params['lower_clothes'];
        }
        if (!empty($params['upper_clothes'])) {
            $request['upper_clothes'] = $params['upper_clothes'];
        }

        $request['img_count'] = $params['img_count'] ?? 1;
        $request['models'] = 'bailian';

        $response = self::requestUrl($request, self::HD_AI_TRY, self::$uid);

        if (!$response) {
            throw new \Exception('提交ai试衣生图任务错误');
        }

        self::saveLog(2, $response['request_id'], $params, $response['task_id'], $response['sub_task_ids'] ?? '');
        self::$returnData = ['result' => $response];
        return true;
    }

    /**
     * @desc 添加模特案例
     * @param $params
     * @return bool
     * @date 2024/12/25 15:50
     * @author dagouzi
     */
    public static function addModelCase($params)
    {
        $data = [
            'user_id'       => self::$uid,
            'case_type'     => 4,
            'params'        => json_encode([]),
            'result_image'  => FileService::setFileUrl($params['result_image']),
        ];
        HdImageCases::create($data);
        return true;
    }


    /**
     * @desc 删除模特案例
     * @param $params
     * @return bool
     * @date 2024/12/25 15:50
     * @author dagouzi
     */
    public static function delModelCase($params)
    {
        $image = HdImageCases::where(['id' => $params['id'], 'user_id' => self::$uid])->findOrEmpty();
        if ($image->isEmpty()) {
            return false;
        }
        if (is_string($params['id'])) {
            HdImageCases::destroy(['id' => $params['id'], 'user_id' => self::$uid]);
        } else {
            HdImageCases::whereIn('id', $params['id'])->where('user_id', self::$uid)->select()->delete();
        }
        return true;
    }


    /**
     * @desc 定时任务查询结果
     * @return true|void
     * @date 2024/7/5 17:57
     * @author dagouzi
     */
    public static function cron()
    {
        try{
            $now = time();
            // 延迟5秒执行
            $task = HdLog::where(['task_status' => 0])->where('create_time', '<', $now - 30)->findOrEmpty();
            if ($task->isEmpty()) {
                return true;
            }
            if (empty($task->task_id)) {
                self::taskStatus($task, 3);
                return true;
            }
            self::handleResult($task->task_id, $task->type);
            return true;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * @desc 提交文生图任务
     * @return bool
     * @date 2024/7/20 10:50
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function txt2img($params)
    {
        $request = [];
        // 组装数据
        foreach ($params as $key => $value) {
            if (in_array($key, ['negative_prompt', 'aspect_ratio', 'img_count',
                'poster_type', 'poster_color', 'poster_title',
                'poster_subtitle', 'poster_description'
            ])) {
                if (!empty($value)) {
                    if ($key == 'img_count') {
                        $request[$key] = (int)$value;
                    } else {
                        $request[$key] = $value;
                    }
                }
            }
        }

        $scene = self::HD_TXT2IMG;
        $type = 3;
        if ($params['prompt'] == ''){
            $scene = self::HD_TXT2POSTERIMG;
            $type = 5;
        }



        $request['prompt'] = $params['prompt'];

        $response = self::requestUrl($request, $scene, self::$uid);

        if ($params['prompt'] != '' && !$response){
            throw new \Exception('提交文生图任务错误');
        }

        if ($params['prompt'] == '' && !$response ) {
            throw new \Exception('提交文生海报图任务错误');
        }
        $sub_task_ids = $response['sub_task_ids'] ?? '';
        self::saveLog($type, $response['request_id'], $params, $response['task_id'], $sub_task_ids);

        self::$returnData = ['result' => $response];
        return true;
    }

    /**
     * @desc 提交图生图任务
     * @return bool
     * @date 2024/7/20 10:50
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function img2img($params)
    {
        $request = [];
        // 组装数据
        foreach ($params as $key => $value) {
            if (in_array($key, ['prompt', 'negative_prompt', 'aspect_ratio', 'img_count'])) {
                if (!empty($value)) {
                    if ($key == 'img_count') {
                        $request[$key] = (int)$value;
                    } else {
                        $request[$key] = $value;
                    }
                }
            }
        }

        foreach ($params['image'] as $item) {
            $request['image'][] = self::imageToStream($item);
        }
        Log::write('画图参数1'.json_encode($request));

        $response = self::requestUrl($request, self::HD_IMG2IMG, self::$uid);

        if (!$response) {
            throw new \Exception('提交图生图任务错误');
        }

        self::saveLog(4, $response['request_id'], $params, $response['task_id'], $response['sub_task_ids']);
        self::$returnData = ['result' => $response];
        return true;
    }

    /**
     * @desc 获取任务状态
     * @param $request
     * @return true
     * @date 2024/7/18 17:15
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function getTaskStatus($request)
    {
        $task_id = $request['task_id'];
        $type = $request['type'];
        $result = self::handleResult($task_id, $type);
        self::$returnData = ['result' => $result];
        return true;
    }

    /**
     * @desc 处理查询结果
     * @param $task_id
     * @param $type
     * @return array|mixed|true|null
     * @date 2024/7/18 18:30
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    private static function handleResult($task_id, $type)
    {
        $result = [];
        $task = HdLog::where(['task_id' => $task_id])->findOrEmpty();
        $typeName = '';
        //转换
        switch ($type) {
            case 1:
                $scene = self::HD_SHOP_IMG2IMG_STATUS;
                $typeName = '商品图';
                break;
            case 2:
                $scene = self::HD_AI_TRY_STATUS;
                $typeName = 'ai试衣';
                break;
            case 3:
                $scene = self::HD_TXT2IMG_STATUS;
                $typeName = '文生图';
                break;
            case 4:
                $scene = self::HD_IMG2IMG_STATUS;
                $typeName = '图生图';
                break;
            case 5:
                $scene = self::HD_TXT2POSTERIMG_STATUS;
                $typeName = '文生海报图';
                break;
            case 7:
                $scene = self::DB_IMG2IMG_STATUS;
                $typeName = '豆包图生图';
                break;
            default:
                throw new \Exception('参数错误');
        }

        try {
            $result = self::requestUrl(['task_id' => $task_id], $scene, self::$uid);

            if (!$result) {
                //记录 失败进行恢复
                throw new \Exception('获取[' . $typeName . ']结果错误');
            }

            if (empty($result['sub_task_results'])) {
                self::taskStatus($task, 4, json_encode($result));
                return true;
            }

            $sub_task_results = $result['sub_task_results'];

            $scene = match ((int)$type) {
                1 => ['scene' => 'goods_image', "type" => AccountLogEnum::TOKENS_DEC_GOODS_IMAGE],
                2 => ['scene' => 'model_image', "type" => AccountLogEnum::TOKENS_DEC_MODEL_IMAGE],
                3 => ['scene' => 'text_to_image', "type" => AccountLogEnum::TOKENS_DEC_TEXT_TO_IMAGE],
                4 => ['scene' => 'image_to_image', "type" => AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE],
                5 => ['scene' => 'txt_to_posterimg', "type" => AccountLogEnum::TOKENS_DEC_TEXT_TO_POSTERIMAGE],
                7 => ['scene' => 'volc_img_to_img_v2', "type" => AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE],
            };
            $unit = TokenLogService::getTypeScore($scene['scene']);

            foreach ($sub_task_results as $item) {
                // 循环每个图的生成进度
                $sub_task = HdImage::where(['sub_task_id' => $item['sub_task_id']])->findOrEmpty();

                $image = $item['image'] ?? '';
                
                if($image){
                    
                    $image = FileService::downloadFileBySource($image, 'image');
                }
                
                if ($sub_task->isEmpty()) {
                    
                    $sub_task_data = [
                        'log_id'            => $task->id,
                        'image'             => $image,
                        'sub_task_id'       => $item['sub_task_id'],
                        'task_status'       => $item['task_status'],
                        'task_completion'   => $item['task_completion']
                    ];
                    HdImage::create($sub_task_data);
                } else {
                    
                    if (!in_array($item['task_status'], [0, 2])) {
                        $sub_task->task_status      = $item['task_status'];
                        $sub_task->task_completion  = $item['task_completion'];
                        $sub_task->image            = $image;
                        $sub_task->save();
                    }
                }
     
                $task_status = $item['task_status'];

                //假设某张失败 则恢复对应的算力
                if (in_array($task_status, [3, 4])) {
                    $extra = ['图片生成失败' => 1, '算力单价' => $unit,'恢复算力'=> $unit];

                    if (isset($item['task_id'])){
                        AccountLogLogic::recordUserTokensLog(false, $task->user_id, $scene['type'], $unit, $item['task_id'],$extra);
                    }
                    if (isset($item['sub_task_id'])){
                        AccountLogLogic::recordUserTokensLog(false, $task->user_id, $scene['type'], $unit, $item['sub_task_id'],$extra);
                    }
                }
            }
            $status_array = array_column($sub_task_results, 'task_status');
            // 子任务的状态，0: 等待中，1: 完成，2: 处理中，3: 失败，4: 未通过审核
            if (in_array(0, $status_array) || in_array(2, $status_array)) {
                self::taskStatus($task, 0);
            } else {
                self::taskStatus($task, 1);
            }
        } catch (\Exception $e) {

            self::taskStatus($task, 5, $e->getMessage());
        }
        return ['result' => $result];
    }

    /**
     * @desc 修改任务状态
     * @param $task
     * @param $status
     * @return void
     * @date 2024/7/5 18:12
     * @author dagouzi
     */
    public static function taskStatus($task, $status = 2, $remark = '')
    {
        $task->task_status = $status;
        if (!empty($remark)) {
            $task->remark = $remark;
        }
        $task->save();
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
    private static function requestUrl(array $request, string $scene, int $userId): array
    {

        $requestService = \app\common\service\ToolsService::HiDream();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::HD_TXT2IMG => ['text_to_image', AccountLogEnum::TOKENS_DEC_TEXT_TO_IMAGE],
            self::HD_TXT2POSTERIMG => ['txt_to_posterimg', AccountLogEnum::TOKENS_DEC_TEXT_TO_POSTERIMAGE],
            self::HD_IMG2IMG => ['image_to_image', AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE],
            self::HD_SHOP_IMG2IMG => ['goods_image', AccountLogEnum::TOKENS_DEC_GOODS_IMAGE],
            self::HD_AI_TRY => ['model_image', AccountLogEnum::TOKENS_DEC_MODEL_IMAGE],
            self::VOLC_TXT2IMG => ['volc_txt_to_img', AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_IMAGE],
            self::VOLC_TXT2POSTERIMG => ['volc_txt_to_posterimg', AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_POSTERIMAGE],
            self::DB_TXT2IMG => ['volc_txt_to_img_v2', AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_IMAGE],
            self::DB_IMG2IMG => ['volc_img_to_img_v2', AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_IMAGE],
            self::DB_TXT2POSTERIMG => ['volc_txt_to_posterimg_v2', AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_POSTERIMAGE],
            default => ['', '']
        };

        if ($tokenScene) {

            //补充
            $request['img_count'] = $request['img_count'] ?? 1;
        }
        switch ($scene) {
            case self::DB_TXT2IMG:
                $response = $requestService->doubaoTxt2Img($request);
                break;
            case self::DB_IMG2IMG:
                $response = $requestService->doubaoImg2Img($request);
                break;
            case self::DB_IMG2IMG_STATUS:
                $response = $requestService->doubaoImg2ImgStatus($request);
                break;
            case self::DB_TXT2POSTERIMG:
                $response = $requestService->doubaoTxt2PosterImg($request);
                break;
            case self::HD_TXT2IMG:
                $response = $requestService->txt2Img($request);
                break;
            case self::HD_TXT2POSTERIMG:
                $response = $requestService->txt2PosterImg($request);
                break;
            case self::VOLC_TXT2IMG:
                $response = $requestService->volctxt2Img($request);
                break;
            case self::VOLC_TXT2POSTERIMG:
                $response = $requestService->volctxt2PosterImg($request);
                break;
            case self::HD_IMG2IMG:

                $response = $requestService->img2Img($request);
                break;
            case self::HD_SHOP_IMG2IMG:

                $response = $requestService->shopImg2Img($request);
                break;
            case self::HD_TEMPLATE_LISTS:

                $response = $requestService->templateList($request);
                break;
            case self::HD_AI_TRY:

                $response = $requestService->vtonCreate($request);
                break;
            case self::HD_TXT2IMG_STATUS:

                $response = $requestService->txt2ImgStatus($request);
                break;
            case self::HD_TXT2POSTERIMG_STATUS:

                $response = $requestService->txt2PosterImgStatus($request);
                break;
            case self::HD_IMG2IMG_STATUS:

                $response = $requestService->img2ImgStatus($request);
                break;
            case self::HD_SHOP_IMG2IMG_STATUS:

                $response = $requestService->shopImg2ImgStatus($request);
                break;
            case self::HD_AI_TRY_STATUS:

                $response = $requestService->vtonStatus($request);
                break;
            default:
                break;
        }
        if ($tokenScene && isset($response['code']) && $response['code'] == 10000) {

            $taskId = $response['data']['task_id'];

            if(!isset($response['data']['sub_task_ids']) && $scene !== self::DB_IMG2IMG){
                throw new \Exception('目前生图正在维护中，请稍后再试');
            };
            //计费
            $unit = TokenLogService::checkToken($userId, $tokenScene);

            $points = round($request['img_count'] * $unit,2);

            //token扣除
            User::userTokensChange(self::$uid, $points);

            $extra = ['生成图片数' => $request['img_count'], '算力单价' => $unit, '实际消耗算力' => $points];

            //扣费记录
            AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
        }

        return $response['data'] ?? [];
    }

    /**
     * @desc 图片转base64
     * @param $url
     * @return string
     * @date 2024/7/5 10:55
     * @author dagouzi
     */
    private static function imageToStream($url)
    {
        $img = file_get_contents($url);

        return base64_encode($img);
    }


    /**
     * @desc 提交文生图任务
     * @return bool
     * @date 2024/7/20 10:50
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function txt2volcimg($params)
    {

        $prompt = trim($params['prompt']);
        $scene = self::VOLC_TXT2IMG;
        $type = 3;
        if ($prompt == ''){
            $scene = self::VOLC_TXT2POSTERIMG;
            $type = 5;
        }

        $response = self::requestUrl($params, $scene, self::$uid);
        if ($prompt != '' && !$response){
            throw new \Exception('提交文生图任务错误');
        }

        if ($prompt == '' && !$response ) {
            throw new \Exception('提交文生海报图任务错误');
        }

        $sub_task_ids[0] = $response['sub_task_ids'] ?? '';
        self::saveLog($type, $response['request_id'], $params, $response['task_id'], $sub_task_ids,1,2,$response['image_urls']);
        self::$returnData = ['result' => $response];
        return true;
    }


    public static function text2img($params){
        $prompt = trim($params['prompt']);
        $scene = self::DB_TXT2IMG;
        $type = 3;
        if ($prompt == ''){
            $scene = self::DB_TXT2POSTERIMG;
            $type = 5;
        }

        TokenLogService::checkToken(self::$uid, $scene);
        
        $response = self::requestUrl($params, $scene, self::$uid);
        if ($prompt != '' && !$response){
            throw new \Exception('提交文生图任务错误');
        }

        if ($prompt == '' && !$response ) {
            throw new \Exception('提交文生海报图任务错误');
        }

        $sub_task_ids[0] = $response['sub_task_ids'] ?? '';
        //豆包文生图
        self::saveLog($type, $response['request_id']??'', $params, $response['task_id']??'', $sub_task_ids, 1, 3, $response['image_urls']);
        self::$returnData = ['result' => $response];
        return true;
    }

    public static function img2volcimg($params)
    {
        try {
            $scene = self::DB_IMG2IMG;
            TokenLogService::checkToken(self::$uid, $scene);
            $response = self::requestUrl($params, $scene, self::$uid);

            $sub_task_ids[0] = $response['sub_task_ids'] ?? '';

            self::saveLog(4, $response['request_id']??'', $params, $response['task_id']??'', $sub_task_ids, 1, 3, $response['image_urls']);
            self::$returnData = ['result' => $response];
            return true;
        } catch (\Throwable $th) {
            //self::setError($th->getMessage());
            throw new \Exception($th->getMessage());
            return false;
        }
    }



}
