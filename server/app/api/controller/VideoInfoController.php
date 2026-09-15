<?php

declare(strict_types=1);

namespace app\api\controller;

use app\common\service\VideoInfoService;
use Exception;
use think\facade\Cache;
use think\facade\Log;
use think\Response;

/**
 * 视频信息控制器
 * 提供视频信息获取、批量处理、缩略图生成等接口
 */
class VideoInfoController extends BaseApiController
{
    /**
     * @var VideoInfoService 视频信息服务
     */
    private VideoInfoService $videoInfoService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct(app());
        $this->videoInfoService = new VideoInfoService();
    }

    // ==================== 核心接口方法 ====================

    /**
     * 获取视频信息
     * 
     * POST /api/video/info
     * 
     * 请求参数:
     * - video_url: 视频URL（必填）
     * - timeout: 超时时间，单位秒（可选，默认60）
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "获取视频信息成功",
     *   "data": {
     *     "duration": 120.5,
     *     "size": 10485760,
     *     "bit_rate": 1000000,
     *     "format_name": "mp4",
     *     "video": {
     *       "codec": "h264",
     *       "width": 1920,
     *       "height": 1080,
     *       "fps": 30
     *     },
     *     "audio": {
     *       "codec": "aac",
     *       "sample_rate": 44100,
     *       "channels": 2
     *     }
     *   }
     * }
     * 
     * @return Response
     */
    public function getInfo(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');
            $timeout = (int)$this->request->param('timeout', 60);

            // 参数验证
            if (empty($videoUrl)) {
                return $this->fail('视频URL不能为空');
            }

            if ($timeout < 1 || $timeout > 300) {
                return $this->fail('超时时间必须在1-300秒之间');
            }

            // 调用服务层
            $videoInfo = $this->videoInfoService->getInfo($videoUrl, $timeout);

            return $this->success('获取视频信息成功', $videoInfo);
        } catch (Exception $e) {
            Log::error('获取视频信息接口异常', [
                'video_url' => $videoUrl ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 批量获取视频信息
     * 
     * POST /api/video/batch
     * 
     * 请求参数:
     * - video_urls: 视频URL数组（必填）
     * - timeout: 超时时间（可选，默认60）
     * - use_queue: 是否使用队列（可选，默认true）
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "批量获取视频信息完成",
     *   "data": {
     *     "total": 10,
     *     "processed": 10,
     *     "use_queue": true,
     *     "results": {
     *       "http://example.com/video1.mp4": {...},
     *       "http://example.com/video2.mp4": {...}
     *     }
     *   }
     * }
     * 
     * @return Response
     */
    public function batchGetInfo(): Response
    {
        try {
            $videoUrls = $this->request->param('video_urls', []);
            $timeout = (int)$this->request->param('timeout', 60);
            $useQueue = (bool)$this->request->param('use_queue', true);

            // 参数验证
            if (empty($videoUrls) || !is_array($videoUrls)) {
                return $this->fail('视频URL列表不能为空且必须是数组');
            }

            if (count($videoUrls) > 50) {
                return $this->fail('单次批量处理最多支持50个视频');
            }

            // 调用服务层
            $results = $this->videoInfoService->batchGetInfo($videoUrls, $timeout, $useQueue);

            return $this->success('批量获取视频信息完成', [
                'total' => count($videoUrls),
                'processed' => count($results),
                'use_queue' => $useQueue,
                'results' => $results
            ]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 异步获取视频信息
     * 
     * POST /api/video/async
     * 
     * 请求参数:
     * - video_url: 视频URL（必填）
     * - callback_url: 回调URL（可选）
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "视频信息获取任务已提交",
     *   "data": {
     *     "task_id": "abc123",
     *     "status": "processing"
     *   }
     * }
     * 
     * @return Response
     */
    public function asyncInfo(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');
            $callbackUrl = $this->request->param('callback_url', '');

            if (empty($videoUrl)) {
                return $this->fail('视频URL不能为空');
            }

            // 生成任务ID
            $taskId = md5($videoUrl . time() . uniqid());

            // 推送到队列
            $jobId = \think\facade\Queue::push('app\common\Jobs\VideoInfoJob', [
                'task_id' => $taskId,
                'video_url' => $videoUrl,
                'callback_url' => $callbackUrl,
            ]);

            // 缓存任务状态
            Cache::set('video_task_' . $taskId, [
                'status' => 'processing',
                'job_id' => $jobId,
                'video_url' => $videoUrl,
                'created_at' => time()
            ], 3600);

            return $this->success('视频信息获取任务已提交', [
                'task_id' => $taskId,
                'status' => 'processing',
                'check_url' => url('video/checkStatus', ['task_id' => $taskId])
            ]);
        } catch (Exception $e) {
            Log::error('异步获取视频信息接口异常', [
                'video_url' => $videoUrl ?? '',
                'error' => $e->getMessage()
            ]);
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 生成视频缩略图（优化版 - 逻辑已迁移到 Service）
     * 
     * POST /api/video/thumbnail
     * 
     * 支持两种调用方式：
     * 1. HTTP 请求：通过 request 参数传递
     * 2. 内部调用：通过数组参数传递
     * 
     * HTTP 请求参数:
     * - video_url: 视频URL（必填）
     * - time: 截取时间点，单位秒（可选，默认1.0）
     * - width: 宽度（可选）
     * - height: 高度（可选）
     * - quality: 质量1-31（可选，默认2）
     * - format: 格式jpg/png（可选，默认jpg）
     * - force: 是否强制重新生成（可选，默认false）
     * 
     * 内部调用参数:
     * [
     *   'video_url' => 'uploads/video/test.mp4',
     *   'time' => 1.0,
     *   'options' => [
     *     'width' => 640,
     *     'height' => 360,
     *     'quality' => 2,
     *     'format' => 'jpg',
     *     'force' => false
     *   ]
     * ]
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "缩略图生成成功",
     *   "data": {
     *     "url": "uploads/thumbnails/20250124/thumb_xxx.jpg",
     *     "full_url": "http://example.com/uploads/thumbnails/20250124/thumb_xxx.jpg",
     *     "path": "/path/to/public/uploads/thumbnails/20250124/thumb_xxx.jpg",
     *     "size": 45678,
     *     "cached": false
     *   },
     *   "url": "uploads/thumbnails/20250124/thumb_xxx.jpg",
     *   "full_url": "http://example.com/uploads/thumbnails/20250124/thumb_xxx.jpg"
     * }
     * 
     * @param array $data 内部调用时传入的参数数组
     * @return array|Response
     */
    public function videoThumbnail(array $data = [])
    {
        try {
            // 1. 解析参数（支持HTTP请求和内部调用）
            $params = $this->parseThumbnailParams($data);

            // 2. 验证必填参数
            if (empty($params['video_url'])) {
                return $this->formatResponse(400, '视频URL不能为空', null, !empty($data));
            }

            // 3. 验证时间参数
            if ($params['time'] < 0) {
                return $this->formatResponse(400, '时间参数必须大于等于0', null, !empty($data));
            }

            // 4. 验证尺寸参数
            if ($params['width'] !== null && $params['width'] < 1) {
                return $this->formatResponse(400, '宽度必须大于0', null, !empty($data));
            }
            if ($params['height'] !== null && $params['height'] < 1) {
                return $this->formatResponse(400, '高度必须大于0', null, !empty($data));
            }

            Log::info('开始生成视频缩略图', [
                'video_url' => $params['video_url'],
                'time' => $params['time'],
                'width' => $params['width'],
                'height' => $params['height'],
                'quality' => $params['quality'],
                'format' => $params['format'],
                'force' => $params['force'],
                'call_type' => empty($data) ? 'http' : 'internal'
            ]);

            // 5. 调用 Service 层生成缩略图
            $result = $this->videoInfoService->generateThumbnail(
                $params['video_url'],
                $params['time'],
                [
                    'width' => $params['width'],
                    'height' => $params['height'],
                    'quality' => $params['quality'],
                    'format' => $params['format'],
                    'force' => $params['force'],
                ]
            );

            // 6. 返回结果
            if ($result) {
                Log::info('视频缩略图生成成功', [
                    'video_url' => $params['video_url'],
                    'thumbnail_url' => $result['url'],
                    'size' => $result['size'] ?? 0,
                    'cached' => $result['cached'] ?? false
                ]);

                return $this->formatResponse(200, '缩略图生成成功', $result, !empty($data));
            }

            return $this->formatResponse(500, '缩略图生成失败', null, !empty($data));
        } catch (Exception $e) {
            Log::error('缩略图生成接口异常', [
                'params' => $params ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->formatResponse(500, $e->getMessage(), null, !empty($data));
        }
    }

    /**
     * 检查任务状态
     * 
     * GET /api/video/checkStatus
     * 
     * 请求参数:
     * - task_id: 任务ID（必填）
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "任务状态查询成功",
     *   "data": {
     *     "task_id": "abc123",
     *     "status": "completed",
     *     "result": {...}
     *   }
     * }
     * 
     * @return Response
     */
    public function checkStatus(): Response
    {
        try {
            $taskId = $this->request->param('task_id');

            if (empty($taskId)) {
                return $this->fail('任务ID不能为空');
            }

            $taskInfo = Cache::get('video_task_' . $taskId);

            if (!$taskInfo) {
                return $this->fail('任务不存在或已过期');
            }

            return $this->success('任务状态查询成功', $taskInfo);
        } catch (Exception $e) {
            Log::error('检查任务状态接口异常', [
                'task_id' => $taskId ?? '',
                'error' => $e->getMessage()
            ]);
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 清除视频信息缓存
     * 
     * POST /api/video/clearCache
     * 
     * 请求参数:
     * - video_url: 视频URL（可选，不传则清除所有）
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "缓存清除成功",
     *   "data": null
     * }
     * 
     * @return Response
     */
    public function clearCache(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url', '');

            if (!empty($videoUrl)) {
                // 清除指定视频的缓存
                $cacheKey = 'video_info_' . md5($videoUrl);
                Cache::delete($cacheKey);

                Log::info('清除指定视频缓存', ['video_url' => $videoUrl]);
                return $this->success('缓存清除成功');
            } else {
                // 清除所有视频信息缓存
                Cache::tag('video_info')->clear();

                Log::info('清除所有视频缓存');
                return $this->success('所有缓存清除成功');
            }
        } catch (Exception $e) {
            Log::error('清除缓存接口异常', [
                'video_url' => $videoUrl ?? '',
                'error' => $e->getMessage()
            ]);
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取支持的视频格式列表
     * 
     * GET /api/video/formats
     * 
     * 返回示例:
     * {
     *   "code": 200,
     *   "msg": "获取成功",
     *   "data": {
     *     "formats": ["mp4", "avi", "mov", ...]
     *   }
     * }
     * 
     * @return Response
     */
    public function getSupportedFormats(): Response
    {
        try {
            $formats = [
                'mp4',
                'avi',
                'mov',
                'wmv',
                'flv',
                'webm',
                'mkv',
                '3gp',
                'ogv',
                'ts',
                'm4v',
                'mpg',
                'mpeg',
                'f4v',
                'm3u8'
            ];

            return $this->success('获取成功', [
                'formats' => $formats,
                'total' => count($formats)
            ]);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    // ==================== 辅助方法 ====================

    /**
     * 解析缩略图参数（兼容HTTP请求和内部调用）
     * 
     * @param array $data 内部调用时传入的数据
     * @return array 标准化的参数数组
     */
    private function parseThumbnailParams(array $data): array
    {
        if (empty($data)) {
            // HTTP 请求方式 - 从 request 获取参数
            return [
                'video_url' => $this->request->param('video_url', ''),
                'time' => floatval($this->request->param('time', 1.0)),
                'width' => $this->request->param('width') ? intval($this->request->param('width')) : null,
                'height' => $this->request->param('height') ? intval($this->request->param('height')) : null,
                'quality' => intval($this->request->param('quality', 2)),
                'format' => $this->request->param('format', 'jpg'),
                'force' => (bool)$this->request->param('force', false),
            ];
        } else {
            // 内部调用方式 - 从数组参数获取
            return [
                'video_url' => $data['video_url'] ?? '',
                'time' => floatval($data['time'] ?? 1.0),
                'width' => isset($data['options']['width']) ? intval($data['options']['width']) : null,
                'height' => isset($data['options']['height']) ? intval($data['options']['height']) : null,
                'quality' => intval($data['options']['quality'] ?? 2),
                'format' => $data['options']['format'] ?? 'jpg',
                'force' => (bool)($data['options']['force'] ?? false),
            ];
        }
    }

    /**
     * 格式化响应（兼容HTTP和内部调用）
     * 
     * @param int $code 状态码
     * @param string $msg 消息
     * @param mixed $data 数据
     * @param bool $isInternalCall 是否为内部调用
     * @return array|Response
     */
    private function formatResponse(int $code, string $msg, $data = null, bool $isInternalCall = false)
    {
        $response = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];

        // 如果有数据，添加快捷访问字段（兼容旧接口）
        if ($data && is_array($data)) {
            $response['url'] = $data['url'] ?? null;
            $response['full_url'] = $data['full_url'] ?? null;
        }

        // 内部调用，直接返回数组
        if ($isInternalCall) {
            return $response;
        }

        // HTTP 请求，返回 Response 对象
        return $code === 200
            ? $this->success($msg, $data)
            : $this->fail($msg, $data);
    }

    /**
     * 验证通用参数
     * 
     * @param array $params 参数数组
     * @param array $rules 验证规则
     * @return array [bool $isValid, string $errorMsg]
     */
    private function validateParams(array $params, array $rules): array
    {
        foreach ($rules as $field => $rule) {
            // 必填验证
            if (isset($rule['required']) && $rule['required']) {
                if (!isset($params[$field]) || $params[$field] === '') {
                    return [false, $rule['message'] ?? "{$field}不能为空"];
                }
            }

            // 类型验证
            if (isset($params[$field]) && isset($rule['type'])) {
                $value = $params[$field];
                switch ($rule['type']) {
                    case 'int':
                        if (!is_numeric($value) || intval($value) != $value) {
                            return [false, $rule['message'] ?? "{$field}必须是整数"];
                        }
                        break;
                    case 'float':
                        if (!is_numeric($value)) {
                            return [false, $rule['message'] ?? "{$field}必须是数字"];
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            return [false, $rule['message'] ?? "{$field}必须是有效的URL"];
                        }
                        break;
                    case 'array':
                        if (!is_array($value)) {
                            return [false, $rule['message'] ?? "{$field}必须是数组"];
                        }
                        break;
                }
            }

            // 范围验证
            if (isset($params[$field]) && isset($rule['range'])) {
                $value = $params[$field];
                [$min, $max] = $rule['range'];
                if ($value < $min || $value > $max) {
                    return [false, $rule['message'] ?? "{$field}必须在{$min}-{$max}之间"];
                }
            }
        }

        return [true, ''];
    }
}
