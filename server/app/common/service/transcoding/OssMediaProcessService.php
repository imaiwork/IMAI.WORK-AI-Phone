<?php

namespace app\common\service\transcoding;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credential\Config as CredentialConfig;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Mts;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\VideoInfoService;
use Darabonba\OpenApi\Models\Config;
use think\facade\Log;

/**
 * 阿里云 MPS 素材切割。
 */
class OssMediaProcessService
{
    public const MEDIA_PROCESS_OSS = 'oss';

    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: (array)ConfigService::get('storage', 'aliyun', []);
    }

    /**
     * 当前站长媒体处理通道（转码 + 切割共用同一单选值）。
     * 仅阿里云 OSS 存储且 media_process=oss 且 MPS 配置完整时返回 oss，其余固定 local。
     */
    public static function mediaProcessMode(): string
    {
        return self::isSliceEnabled() ? self::MEDIA_PROCESS_OSS : 'local';
    }

    /**
     * 转码是否走阿里云 OSS/MPS 通道。转码与切割统一由后台 media_process 单选决定。
     */
    public static function isEnabled(): bool
    {
        return self::isSliceEnabled();
    }

    public static function isSliceEnabled(): bool
    {
        $default = ConfigService::get('storage', 'default', 'local');
        if ($default !== 'aliyun') {
            return false;
        }

        $cfg = (array)ConfigService::get('storage', 'aliyun', []);
        // 兼容历史数组写法，统一按单值判断
        $mode = $cfg['media_process'] ?? 'local';
        if (is_array($mode)) {
            $mode = in_array(self::MEDIA_PROCESS_OSS, $mode, true) ? self::MEDIA_PROCESS_OSS : 'local';
        }
        if ((string)$mode !== self::MEDIA_PROCESS_OSS) {
            return false;
        }

        foreach (['access_key', 'secret_key', 'bucket', 'Location', 'PipelineId', 'TemplateId'] as $key) {
            if (trim((string)($cfg[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    public static function make(): self
    {
        return self::makeForSlice();
    }

    public static function makeForSlice(): self
    {
        if (!self::isSliceEnabled()) {
            throw new \RuntimeException('未启用 OSS 切割通道或 MPS 配置不完整');
        }

        return new self();
    }

    /**
     * 把业务 uri / 完整 URL 归一为 Bucket 内 Object Key
     */
    public static function toObjectKey(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            throw new \InvalidArgumentException('对象路径不能为空');
        }

        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            $path = (string)parse_url($uri, PHP_URL_PATH);
            return ltrim($path, '/');
        }

        return ltrim(FileService::setFileUrl($uri), '/');
    }

    /**
     * 提交转码作业（可选 Clip 截取）
     *
     * @param array|null $clipTimeSpan ['Seek' => '00:00:00.000', 'Duration' => '5.00']
     * @return string JobId
     */
    public function submit(string $inputObject, string $outputObject, ?array $clipTimeSpan = null): string
    {
        $inputObject = ltrim($inputObject, '/');
        $outputObject = ltrim($outputObject, '/');

        $input = json_encode([
            'Bucket' => $this->config['bucket'],
            'Location' => $this->config['Location'],
            'Object' => rawurlencode($inputObject),
        ], JSON_UNESCAPED_SLASHES);

        $output = [
            'OutputObject' => rawurlencode($outputObject),
            'TemplateId' => $this->config['TemplateId'],
            // 小于模板分辨率时沿用输入尺寸，而不是放大或让任务失败。
            'TransConfig' => [
                'IsCheckReso' => 'true',
                'IsCheckResoFail' => 'false',
            ],
        ];
        if ($clipTimeSpan !== null) {
            // 模板中未显式覆盖的帧率仍可能生效，不能靠省略 Fps 保留原片。
            $info = (new VideoInfoService())->extractVideoInfo(FileService::getFileUrl($inputObject), 30);
            $video = $info['video'] ?? [];
            $fps = (float)($video['fps'] ?? 0);
            $width = (int)($video['width'] ?? 0);
            $height = (int)($video['height'] ?? 0);
            if ($fps <= 0 || $width <= 0 || $height <= 0) {
                throw new \RuntimeException('无法识别原片尺寸或帧率，停止提交 OSS 切片');
            }
            $output['Video'] = [
                'Codec' => 'H.264',
                'Crf' => '20',
                'Preset' => 'medium',
                'PixFmt' => 'yuv420p',
                'Width' => (string)$width,
                'Height' => (string)$height,
                'LongShortMode' => 'false',
                'Fps' => (string)min(60.0, $fps),
            ];
            $output['TransConfig']['TransMode'] = 'fixCRF';
            $output['Clip'] = [
                'TimeSpan' => [
                    'Seek' => (string)$clipTimeSpan['Seek'],
                    'Duration' => (string)$clipTimeSpan['Duration'],
                ],
            ];
        }

        $outputs = json_encode([$output], JSON_UNESCAPED_SLASHES);

        $request = new SubmitJobsRequest([
            'input' => $input,
            'outputs' => $outputs,
            'outputBucket' => $this->config['bucket'],
            'pipelineId' => $this->config['PipelineId'],
            'outputLocation' => $this->config['Location'],
        ]);

        try {
            $resp = $this->createClient()->submitJobsWithOptions($request, new RuntimeOptions([]));
            $map = $resp->toMap();
            $jobId = $map['body']['JobResultList']['JobResult'][0]['Job']['JobId']
                ?? $map['body']['JobResultList']['JobResult'][0]['JobId']
                ?? null;

            if (empty($jobId)) {
                $success = $map['body']['JobResultList']['JobResult'][0]['Success'] ?? null;
                $message = $map['body']['JobResultList']['JobResult'][0]['Message'] ?? '提交失败';
                throw new \RuntimeException("MPS SubmitJobs 未返回 JobId success={$success} message={$message}");
            }

            Log::channel('ffmpeg')->write(
                "[OSS-MPS] 已提交 job_id={$jobId} input={$inputObject} output={$outputObject}"
                    . ($clipTimeSpan ? ' clip=' . json_encode($clipTimeSpan, JSON_UNESCAPED_UNICODE) : '')
            );

            return (string)$jobId;
        } catch (\Throwable $e) {
            if ($e instanceof TeaError) {
                throw new \RuntimeException('MPS 提交切割任务失败: ' . $e->message, 0, $e);
            }
            throw $e;
        }
    }

    /**
     * 查询作业状态
     *
     * @return array{state:string,success:bool,message:string,output_object?:string}
     */
    public function query(string $jobId): array
    {
        $request = new QueryJobListRequest(['jobIds' => $jobId]);
        try {
            $resp = $this->createClient()->queryJobListWithOptions($request, new RuntimeOptions([]));
            $map = $resp->toMap();
            $job = $map['body']['JobList']['Job'][0] ?? null;
            if (!$job) {
                return [
                    'state' => 'Unknown',
                    'success' => false,
                    'message' => '未查询到切割作业',
                ];
            }

            $state = (string)($job['State'] ?? 'Unknown');
            $outputObject = $job['Output']['OutputFile']['Object'] ?? '';
            if (is_string($outputObject) && $outputObject !== '') {
                $outputObject = rawurldecode($outputObject);
            }

            return [
                'state' => $state,
                'success' => $state === 'TranscodeSuccess',
                'message' => (string)($job['Message'] ?? ''),
                'output_object' => is_string($outputObject) ? ltrim($outputObject, '/') : '',
            ];
        } catch (\Throwable $e) {
            if ($e instanceof TeaError) {
                throw new \RuntimeException('MPS 查询切割任务失败: ' . $e->message, 0, $e);
            }
            throw $e;
        }
    }

    /**
     * 提交并轮询至完成
     *
     * @return string 输出 Object Key（相对路径）
     */
    public function submitAndWait(
        string $inputObject,
        string $outputObject,
        ?array $clipTimeSpan = null,
        int $timeoutSeconds = 540,
        int $pollIntervalSeconds = 5
    ): string {
        $jobId = $this->submit($inputObject, $outputObject, $clipTimeSpan);
        $deadline = time() + max(30, $timeoutSeconds);

        while (time() < $deadline) {
            $result = $this->query($jobId);
            $state = $result['state'];

            if ($state === 'TranscodeSuccess') {
                $out = $result['output_object'] !== '' ? $result['output_object'] : ltrim($outputObject, '/');
                Log::channel('ffmpeg')->write("[OSS-MPS] 完成 job_id={$jobId} output={$out}");
                return $out;
            }

            if (in_array($state, ['TranscodeFail', 'TranscodeCancelled'], true)) {
                throw new \RuntimeException(
                    "MPS 转码失败 job_id={$jobId} state={$state} message=" . ($result['message'] ?: '-')
                );
            }

            sleep(max(2, $pollIntervalSeconds));
        }

        throw new \RuntimeException("MPS 转码等待超时 job_id={$jobId} timeout={$timeoutSeconds}s");
    }

    public static function formatSeek(float $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = (int)floor($seconds / 3600);
        $minutes = (int)floor(fmod($seconds, 3600) / 60);
        $secs = fmod($seconds, 60);

        return sprintf('%02d:%02d:%06.3f', $hours, $minutes, $secs);
    }

    public static function formatDuration(float $seconds): string
    {
        return number_format(max(0.01, $seconds), 2, '.', '');
    }

    private function createClient(): Mts
    {
        $credConfig = new CredentialConfig([
            'type' => 'access_key',
            'accessKeyId' => $this->config['access_key'],
            'accessKeySecret' => $this->config['secret_key'],
        ]);
        $credClient = new Credential($credConfig);
        $region = str_replace('oss-', '', (string)$this->config['Location']);

        $config = new Config([
            'credential' => $credClient,
            'endpoint' => 'mts.' . $region . '.aliyuncs.com',
        ]);

        return new Mts($config);
    }
}
