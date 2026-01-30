<?php
declare(strict_types=1);

namespace app\job;

use think\facade\Log;
use think\facade\Cache;
use app\common\service\VideoInfoService;
use think\queue\Job;
use Exception;

class VideoInfoJob
{
  /**
   * 处理视频信息获取任务
   */
  public function fire(Job $job, $data)
  {
      try {
          Log::info('开始处理视频信息任务', $data);
          
          $videoInfoService = new VideoInfoService();
          
          if (isset($data['batch_id'])) {
              // 批量任务
              $this->processBatchTask($job, $data, $videoInfoService);
          } else {
              // 单个任务
              $this->processSingleTask($job, $data, $videoInfoService);
          }
          
          // 任务完成，删除任务
          $job->delete();
          
      } catch (Exception $e) {
          Log::error('视频信息任务处理失败', [
              'data' => $data,
              'error' => $e->getMessage(),
              'attempts' => $job->attempts()
          ]);
          
          // 重试逻辑
          if ($job->attempts() < 3) {
              // 延迟重试
              $job->release(60); // 60秒后重试
          } else {
              // 最终失败
              $this->handleFailedTask($data, $e->getMessage());
              $job->delete();
          }
      }
  }

  /**
   * 处理批量任务
   */
  private function processBatchTask(Job $job, array $data, VideoInfoService $service)
  {
      $batchId = $data['batch_id'];
      $index = $data['index'];
      $url = $data['url'];
      $timeout = $data['timeout'];
      
      $result = [
          'index' => $index,
          'url' => $url,
          'success' => false,
          'data' => null,
          'error' => null,
          'processed_at' => date('Y-m-d H:i:s')
      ];

      try {
          // 直接调用核心方法，跳过限流检查（队列任务已经控制了并发）
          $videoInfo = $service->extractVideoInfo($url, $timeout);
          
          if ($videoInfo) {
              $result['success'] = true;
              $result['data'] = $videoInfo;
              
              // 缓存结果
              $service->cacheVideoInfo($url, $videoInfo);
          } else {
              $result['error'] = '无法解析视频信息';
          }
      } catch (Exception $e) {
          $result['error'] = $e->getMessage();
      }

      // 将结果存储到批次缓存中
      $batchCacheKey = 'batch_result_' . $batchId;
      $batchResults = Cache::get($batchCacheKey, []);
      $batchResults[$index] = $result;
      Cache::set($batchCacheKey, $batchResults, 300);
      
      Log::info('批量任务处理完成', [
          'batch_id' => $batchId,
          'index' => $index,
          'success' => $result['success']
      ]);
  }

  /**
   * 处理单个任务
   */
  private function processSingleTask(Job $job, array $data, VideoInfoService $service)
  {
      $taskId = $data['task_id'];
      $url = $data['url'];
      $timeout = $data['timeout'];
      
      // 更新任务状态
      Cache::set('task_' . $taskId, [
          'status' => 'processing',
          'url' => $url,
          'started_at' => date('Y-m-d H:i:s')
      ], 1800);

      try {
          $videoInfo = $service->extractVideoInfo($url, $timeout);
          
          // 更新任务结果
          Cache::set('task_' . $taskId, [
              'status' => 'completed',
              'url' => $url,
              'data' => $videoInfo,
              'completed_at' => date('Y-m-d H:i:s')
          ], 1800);
          
          // 缓存视频信息
          $service->cacheVideoInfo($url, $videoInfo);
          
          Log::info('单个任务处理完成', [
              'task_id' => $taskId,
              'url' => $url
          ]);
          
      } catch (Exception $e) {
          // 更新任务状态为失败
          Cache::set('task_' . $taskId, [
              'status' => 'failed',
              'url' => $url,
              'error' => $e->getMessage(),
              'failed_at' => date('Y-m-d H:i:s')
          ], 1800);
          
          throw $e;
      }
  }

  /**
   * 处理最终失败的任务
   */
  private function handleFailedTask(array $data, string $error)
  {
      if (isset($data['batch_id'])) {
          // 批量任务失败
          $batchCacheKey = 'batch_result_' . $data['batch_id'];
          $batchResults = Cache::get($batchCacheKey, []);
          $batchResults[$data['index']] = [
              'index' => $data['index'],
              'url' => $data['url'],
              'success' => false,
              'error' => '任务最终失败: ' . $error,
              'processed_at' => date('Y-m-d H:i:s')
          ];
          Cache::set($batchCacheKey, $batchResults, 300);
      } else {
          // 单个任务失败
          Cache::set('task_' . $data['task_id'], [
              'status' => 'failed',
              'url' => $data['url'],
              'error' => '任务最终失败: ' . $error,
              'failed_at' => date('Y-m-d H:i:s')
          ], 1800);
      }
  }

  /**
   * 任务失败处理
   */
  public function failed($data)
  {
      Log::error('视频信息任务最终失败', $data);
  }
}