<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\UploadService;
use app\common\service\VideoInfoService;
use Exception;
use think\Response;

class VideoInfoController extends BaseApiController
{
    private VideoInfoService $videoInfoService;

    public function __construct()
    {
        parent::__construct(app());
        $this->videoInfoService = new VideoInfoService();
    }

    /**
     * 获取视频信息
     * POST /api/video/info
     * 参数: video_url, timeout(可选)
     */
    public function getInfo(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');
            $timeout  = (int)$this->request->param('timeout', 60);

            if (empty($videoUrl)) {
                return $this->fail('视频URL不能为空');
            }

            $videoInfo = $this->videoInfoService->getInfo($videoUrl, $timeout);

            return $this->success('获取视频信息成功', $videoInfo);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 批量获取视频信息
     * POST /api/video/batch
     * 参数: video_urls[], timeout(可选), use_queue(可选)
     */
    public function batchGetInfo(): Response
    {
        try {
            $videoUrls = $this->request->param('video_urls', []);
            $timeout   = (int)$this->request->param('timeout', 60);
            $useQueue  = (bool)$this->request->param('use_queue', true);

            if (empty($videoUrls) || !is_array($videoUrls)) {
                return $this->fail('视频URL列表不能为空且必须是数组');
            }

            // 验证数组大小
            if (count($videoUrls) > 50) {
                return $this->fail('单次批量处理最多支持50个视频');
            }

            $results = $this->videoInfoService->batchGetInfo($videoUrls, $timeout, $useQueue);

            return $this->success('批量获取视频信息完成', [
                'total'     => count($videoUrls),
                'processed' => count($results),
                'use_queue' => $useQueue,
                'results'   => $results
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 异步获取视频信息
     * POST /api/video/async
     * 参数: video_url, timeout(可选)
     */
    public function asyncInfo(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');
            $timeout  = (int)$this->request->param('timeout', 60);

            if (empty($videoUrl)) {
                return $this->fail('视频URL不能为空');
            }

            $taskId = $this->videoInfoService->getVideoInfoAsync($videoUrl, $timeout);

            return $this->success('异步任务已创建', [
                'task_id'        => $taskId,
                'status_url'     => '/api/video/async/status?task_id=' . $taskId,
                'estimated_time' => '30-60秒'
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取异步任务状态
     * GET /api/video/async/status
     * 参数: task_id
     */
    public function asyncStatus(): Response
    {
        try {
            $taskId = $this->request->param('task_id');

            if (empty($taskId)) {
                return $this->fail('任务ID不能为空');
            }

            $result = $this->videoInfoService->getAsyncResult($taskId);

            if (!$result) {
                return $this->fail('任务不存在或已过期');
            }

            return $this->success('获取任务状态成功', $result);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 生成视频缩略图
     * POST /api/video/thumbnail
     * 参数: video_url, time(可选), options(可选)
     */
    public function thumbnail(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');
            $time     = (float)$this->request->param('time', 1.0);
            $options  = $this->request->param('options', []);

            if (empty($videoUrl)) {
                return $this->fail('视频URL不能为空');
            }

            // 验证时间参数
            if ($time < 0) {
                return $this->fail('时间参数不能为负数');
            }

            // 验证选项参数
            if (!empty($options) && !is_array($options)) {
                return $this->fail('选项参数必须是数组');
            }

            // 设置默认选项
            $defaultOptions = [
                'width'   => 320,
                'height'  => 240,
                'quality' => 2
            ];
            $options        = array_merge($defaultOptions, $options);

            // 验证尺寸限制
            if ($options['width'] > 1920 || $options['height'] > 1080) {
                return $this->fail('缩略图尺寸不能超过1920x1080');
            }

            $thumbnailUrl = $this->videoInfoService->generateThumbnail($videoUrl, $time, $options);

            if ($thumbnailUrl) {
                return $this->success('缩略图生成成功', [
                    'thumbnail_url' => $thumbnailUrl,
                    'time'          => $time,
                    'options'       => $options
                ]);
            } else {
                return $this->fail('缩略图生成失败');
            }

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取支持的格式
     * GET /api/video/formats
     */
    public function formats(): Response
    {
        try {
            $formats = $this->videoInfoService->getSupportedFormats();

            return $this->success('获取支持格式成功', [
                'formats' => $formats,
                'total'   => count($formats)
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 清理缓存
     * POST /api/video/cache/clear
     * 参数: video_url(可选)
     */
    public function clearCache(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');

            $result = $this->videoInfoService->clearCache($videoUrl);

            if ($result) {
                $message = $videoUrl ? '指定视频缓存清理成功' : '所有缓存清理成功';
                return $this->success($message, [
                    'cleared_url' => $videoUrl,
                    'cleared_at'  => date('Y-m-d H:i:s')
                ]);
            } else {
                return $this->fail('缓存清理失败');
            }

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取系统状态
     * GET /api/video/status
     */
    public function status(): Response
    {
        try {
            $status     = $this->videoInfoService->getSystemStatus();
            $systemLoad = $this->videoInfoService->getSystemLoad();

            return $this->success('获取系统状态成功', [
                'service_status' => $status,
                'system_load'    => $systemLoad,
                'checked_at'     => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 测试媒体文件 - 调试用
     * POST /api/video/test
     * 参数: video_url
     */
    public function test(): Response
    {
        try {
            $videoUrl = $this->request->param('video_url');

            if (empty($videoUrl)) {
                return $this->fail('视频URL不能为空');
            }

            $testResult = $this->videoInfoService->testMediaFile($videoUrl);

            return $this->success('媒体文件测试完成', [
                'test_result' => $testResult,
                'tested_at'   => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取FFmpeg调试信息
     * GET /api/video/debug
     */
    public function debug(): Response
    {
        try {
            $debugInfo = $this->videoInfoService->getFFmpegDebugInfo();

            return $this->success('获取调试信息成功', [
                'debug_info'   => $debugInfo,
                'generated_at' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 健康检查
     * GET /api/video/health
     */
    public function health(): Response
    {
        try {
            $systemStatus = $this->videoInfoService->getSystemStatus();
            $systemLoad   = $this->videoInfoService->getSystemLoad();

            $health = [
                'service'          => 'VideoInfoService',
                'status'           => 'running',
                'timestamp'        => date('Y-m-d H:i:s'),
                'ffmpeg_available' => $systemStatus['ffmpeg_available'] ?? false,
                'ffmpeg_version'   => $systemStatus['ffmpeg_version'] ?? null,
                'memory_usage'     => $systemLoad['memory_usage'] ?? 0,
                'memory_peak'      => $systemLoad['memory_peak'] ?? 0,
                'processing_count' => $systemLoad['processing_count'] ?? 0
            ];

            // 判断服务健康状态
            $isHealthy = $health['ffmpeg_available'] &&
                         ($health['memory_usage'] < 200 * 1024 * 1024); // 200MB以下

            $health['healthy'] = $isHealthy;
            $health['status']  = $isHealthy ? 'healthy' : 'unhealthy';

            return $this->success('服务运行正常', $health);

        } catch (Exception $e) {
            return $this->fail('服务异常: ' . $e->getMessage());
        }
    }

    /**
     * 获取API文档信息
     * GET /api/video/docs
     */
    public function docs(): Response
    {
        $docs = [
            'service'           => 'VideoInfoService API',
            'version'           => '1.0.0',
            'endpoints'         => [
                [
                    'method'      => 'POST',
                    'path'        => '/api/video/info',
                    'description' => '获取单个视频信息',
                    'parameters'  => [
                        'video_url' => '视频URL（必需）',
                        'timeout'   => '超时时间，默认60秒（可选）'
                    ]
                ],
                [
                    'method'      => 'POST',
                    'path'        => '/api/video/batch',
                    'description' => '批量获取视频信息',
                    'parameters'  => [
                        'video_urls' => '视频URL数组（必需）',
                        'timeout'    => '超时时间，默认60秒（可选）',
                        'use_queue'  => '是否使用队列，默认true（可选）'
                    ]
                ],
                [
                    'method'      => 'POST',
                    'path'        => '/api/video/async',
                    'description' => '异步获取视频信息',
                    'parameters'  => [
                        'video_url' => '视频URL（必需）',
                        'timeout'   => '超时时间，默认60秒（可选）'
                    ]
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/video/async/status',
                    'description' => '获取异步任务状态',
                    'parameters'  => [
                        'task_id' => '任务ID（必需）'
                    ]
                ],
                [
                    'method'      => 'POST',
                    'path'        => '/api/video/thumbnail',
                    'description' => '生成视频缩略图',
                    'parameters'  => [
                        'video_url' => '视频URL（必需）',
                        'time'      => '截取时间点，默认1.0秒（可选）',
                        'options'   => '选项参数，包含width、height、quality（可选）'
                    ]
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/video/formats',
                    'description' => '获取支持的视频格式'
                ],
                [
                    'method'      => 'POST',
                    'path'        => '/api/video/cache/clear',
                    'description' => '清理缓存',
                    'parameters'  => [
                        'video_url' => '指定视频URL，不传则清理所有缓存（可选）'
                    ]
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/video/status',
                    'description' => '获取系统状态'
                ],
                [
                    'method'      => 'POST',
                    'path'        => '/api/video/test',
                    'description' => '测试媒体文件（调试用）',
                    'parameters'  => [
                        'video_url' => '视频URL（必需）'
                    ]
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/video/debug',
                    'description' => '获取FFmpeg调试信息'
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/video/health',
                    'description' => '健康检查'
                ],
                [
                    'method'      => 'GET',
                    'path'        => '/api/video/docs',
                    'description' => '获取API文档'
                ]
            ],
            'response_format'   => [
                'success' => [
                    'code' => 200,
                    'msg'  => '操作成功消息',
                    'data' => '返回的数据'
                ],
                'error'   => [
                    'code' => '错误码',
                    'msg'  => '错误消息',
                    'data' => null
                ]
            ],
            'rate_limits'       => [
                'per_minute'     => 30,
                'per_hour'       => 500,
                'batch_per_hour' => 10
            ],
            'supported_formats' => [
                'video'     => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'ogv', 'ts', 'm4v', 'mpg', 'mpeg', 'f4v'],
                'streaming' => ['m3u8', 'rtmp']
            ]
        ];

        return $this->success('API文档获取成功', $docs);
    }

    /**
     * 批量删除缓存
     * POST /api/video/cache/batch-clear
     * 参数: video_urls[]
     */
    public function batchClearCache(): Response
    {
        try {
            $videoUrls = $this->request->param('video_urls', []);

            if (empty($videoUrls) || !is_array($videoUrls)) {
                return $this->fail('视频URL列表不能为空且必须是数组');
            }

            if (count($videoUrls) > 100) {
                return $this->fail('单次批量清理最多支持100个视频缓存');
            }

            $results      = [];
            $successCount = 0;
            $failCount    = 0;

            foreach ($videoUrls as $index => $url) {
                try {
                    $result    = $this->videoInfoService->clearCache($url);
                    $results[] = [
                        'index'      => $index,
                        'url'        => $url,
                        'success'    => $result,
                        'cleared_at' => date('Y-m-d H:i:s')
                    ];

                    if ($result) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } catch (Exception $e) {
                    $results[] = [
                        'index'   => $index,
                        'url'     => $url,
                        'success' => false,
                        'error'   => $e->getMessage()
                    ];
                    $failCount++;
                }
            }

            return $this->success('批量清理缓存完成', [
                'total'         => count($videoUrls),
                'success_count' => $successCount,
                'fail_count'    => $failCount,
                'results'       => $results
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取缓存统计信息
     * GET /api/video/cache/stats
     */
    public function cacheStats(): Response
    {
        try {
            // 这里需要根据实际缓存实现来获取统计信息
            $stats = [
                'cache_type'          => 'Redis/File', // 根据实际配置
                'total_cached_videos' => 0, // 需要实现统计逻辑
                'cache_hit_rate'      => 0.0,
                'cache_size'          => '0 MB',
                'oldest_cache'        => null,
                'newest_cache'        => null,
                'stats_generated_at'  => date('Y-m-d H:i:s')
            ];

            return $this->success('获取缓存统计成功', $stats);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 预热缓存
     * POST /api/video/cache/warmup
     * 参数: video_urls[]
     */
    public function warmupCache(): Response
    {
        try {
            $videoUrls = $this->request->param('video_urls', []);

            if (empty($videoUrls) || !is_array($videoUrls)) {
                return $this->fail('视频URL列表不能为空且必须是数组');
            }

            if (count($videoUrls) > 20) {
                return $this->fail('单次预热最多支持20个视频');
            }

            // 使用批量处理来预热缓存
            $results = $this->videoInfoService->batchGetInfo($videoUrls, 30, true);

            $warmedCount = 0;
            foreach ($results as $result) {
                if ($result['success']) {
                    $warmedCount++;
                }
            }

            return $this->success('缓存预热完成', [
                'total'        => count($videoUrls),
                'warmed_count' => $warmedCount,
                'results'      => $results,
                'warmed_at'    => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 生成视频缩略图
     * 参数: video_url, time(可选), options(可选)
     */
    public function videoThumbnail($params): array
    {
        try {
            $videoUrl = $params['video_url'];
            $time     = $params['time'] ? (float)$params['time'] : 1.0;
            $options  = $params['options'] ?? [];

            if (empty($videoUrl)) {
                return ['result' => false, 'url' => '', 'msg' => '视频URL不能为空'];
            }

            // 验证时间参数
            if ($time < 0) {
                return ['result' => false, 'url' => '', 'msg' => '时间参数不能为负数'];
            }

            // 验证选项参数
            if (!empty($options) && !is_array($options)) {
                return ['result' => false, 'url' => '', 'msg' => '选项参数必须是数组'];
            }

            $targetWidth = $options['width'] ?? 960;
            $targetHeight = $options['height'] ?? 0;

            // ========== 方式1：用FFmpeg命令行解析（无需扩展，兼容性好） ==========
            $ffmpegCmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 " . escapeshellarg($videoUrl);
            exec($ffmpegCmd, $output, $returnVar);
            if ($returnVar !== 0 || empty($output[0])) {
                $finalWidth = 320;
                $finalHeight = 240;
            }else{
                list($originWidth, $originHeight) = explode('x', $output[0]);
                $originWidth = (int)$originWidth;
                $originHeight = (int)$originHeight;

                // 3. 关键计算：按原比例缩放，适配目标宽/高（只传宽/只传高/都传的情况）
                $scaleRatio = min(
                    $targetWidth > 0 ? $targetWidth / $originWidth : 1,
                    $targetHeight > 0 ? $targetHeight / $originHeight : 1
                );
                // 最终等比例尺寸（取整，避免小数）
                $finalWidth = (int)round($originWidth * $scaleRatio);
                $finalHeight = (int)round($originHeight * $scaleRatio);
            }

            // 设置默认选项
            $defaultOptions = [
                'width'   => $finalWidth,
                'height'  => $finalHeight,
                'quality' => 2
            ];
            $options        = array_merge($defaultOptions, $options);

            // 验证尺寸限制
            if ($options['width'] > 2000 || $options['height'] > 2000) {
                return ['result' => false, 'url' => '', 'msg' => '缩略图宽高不能超过1920'];
            }

            $thumbnailUrl = $this->videoInfoService->generateThumbnail($videoUrl, $time, $options);
            if (!$thumbnailUrl) {
                return ['result' => false, 'url' => '', 'msg' => '缩略图生成失败'];
            }

            $localPath    = public_path() . $thumbnailUrl;
            $thumbnailUrl = FileService::getFileUrl($thumbnailUrl);
            $default      = ConfigService::get('storage', 'default', 'local');
            if ($default != 'local') {
                if (preg_match('/uploads\/(.+?)\/\d{8}/', $thumbnailUrl, $matches)) {
                    $ossPath = $matches[0];
                    $url     = UploadService::uploadToOSS($localPath, $ossPath);
                }
            }

            return [
                'result'  => true,
                'msg'     => '缩略图生成成功',
                'url'     => isset($url) ? FileService::getFileUrl($url) : $thumbnailUrl,
                'time'    => $time,
                'options' => $options
            ];

        } catch (Exception $e) {
            return ['result' => false, 'url' => '', 'msg' => $e->getMessage()];
        }
    }
}