<?php

namespace app\api\logic\minimax;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\human\HumanVideoTask;
use app\common\model\human\HumanVoice;
use app\common\model\minimax\MinimaxShanjianTask;
use app\common\model\minimax\MinimaxUploadFile;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use app\common\service\UploadService;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

class VoiceLogic extends ApiLogic
{
    const MINIMAX_UPLOAD = 'minimaxUpload';
    const MINIMAX_VOICE_HD = 'minimaxVoiceHd';
    const MINIMAX_VOICE_TURBO = 'minimaxVoiceTurbo';
    const MINIMAX_AUDIO_HD = 'minimaxAudioHd';
    const MINIMAX_AUDIO_TURBO = 'minimaxAudioTurbo';

    public static function upload($params)
    {
        try {
            $taskId  = generate_unique_task_id();
            $scene    = self::MINIMAX_UPLOAD;
            $response = self::requestUrl([
                                             'user_id' => self::$uid,
                                             'url'     => $params['audio_url'],
                                             'now'     => time(),
                                         ], $scene, self::$uid, $taskId);
            Log::channel('minimax')->write('minimax音频文件上传' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ((int)$response['code'] !== 10000) {
                self::setError($response['message'] ?? '上传失败');
                return false;
            }
            $data             = array(
                'user_id'     => self::$uid,
                'audio_url'   => $params['audio_url'],
                'file_id'     => $response['data']['file']['file_id'],
                'file_name'   => $response['data']['file']['filename'],
                'bytes'       => $response['data']['file']['bytes'],
                'create_time' => time(),
            );
            $result           = MinimaxUploadFile::create($data);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function add($params)
    {
        try {
            $taskId    = generate_unique_task_id();
            $text      = $params['text'] ?? '你好，我是你的专属AI克隆声音';
            $scene     = $params['model'] == 'speech-2.8-hd' ? self::MINIMAX_VOICE_HD : self::MINIMAX_VOICE_TURBO;
            $microTime = str_replace('.', '', sprintf('%.6f', microtime(true)));
            $random    = bin2hex(random_bytes(4)); // 8位随机字符串
            $prefix    = $params['model'] == 'speech-2.8-hd' ? 'minimax_' . '28hd' : 'minimax_' . '28turbo';
            $voiceId   = $prefix . '_' . $microTime . $random;
            $find      = HumanVoice::where('voice_id', $voiceId)->findOrEmpty();
            if (!$find->isEmpty()) {
                self::setError('任务已存在，请稍后重试');
                return false;
            }
            $response = self::requestUrl(
                  [
                      'text'     => $text,
                      'model'    => $params['model'],
                      'file_id'  => $params['file_id'],
                      'voice_id' => $voiceId,
                      'task_id'  => $taskId,
                      'user_id'  => self::$uid,
                      'now'      => time(),
                  ]
                , $scene, self::$uid, $taskId
            );

            Log::channel('minimax')->write('minimax音色合成参数' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ((int)$response['code'] !== 10000) {
                self::setError($response['msg'] ?? '');
                return false;
            }
            $modelVersion = $params['model'] == 'speech-2.8-hd' ? 10 : 11;
            if (!empty($response['data']['demo_audio'])){
                $voiceUrl = FileService::downloadFileBySource($response['data']['demo_audio'], 'audio');
            }
            $data             = array(
                'user_id'        => self::$uid,
                'model_version'  => $modelVersion,
                'task_id'        => $taskId,
                'status'         => 1, //已生成
                'gender'         => $params['gender'] ?? '',
                'name'           => $params['name'] ?? date('YmdHi', time()),
                'type'           => 0,
                'audio_url'      => '',
                'language'       => 'zh-CN',
                'demo_text'      => $params['text'],
                'result_task_id' => '',
                'voice_id'       => $voiceId,
                'voice_urls'     => $voiceUrl ?? '',
            );
            $result           = HumanVoice::create($data);
            $result = $result->toArray();

            $audioParams = ['minimax_voice_id' => $result['id'], 'text'=>$params['text']];
            $result['audio_url'] = self::audio($audioParams, self::$uid);
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function audiosCreate($params)
    {
        try {
            $audios = [];
            $minimaxVoice = HumanVoice::where('status', 1)->where('id', $params['voice_id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($minimaxVoice->isEmpty()){
                throw new Exception('音色不存在');
            }
            $data['minimax_voice_id'] = $minimaxVoice['id'];
            foreach ($params['contents'] as $content){
                $data['text'] = $content;
                $res = self::audio($data, self::$uid);
                if ($res){
                    $audios[] = self::$returnData;
                }
            }
            self::$returnData = [];
            self::$returnData = $audios;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function shanjianAudioCreate()
    {
        try {
            $item = MinimaxShanjianTask::where('status', 0)->order('id', 'asc')->findOrEmpty();
            if ($item->isEmpty()) {
                return true;
            }

            // 原子抢占，避免定时任务并发执行时重复 TTS、重复扣费和重复建视频任务。
            $claimed = MinimaxShanjianTask::where('id', (int)$item->id)
                ->where('status', 0)
                ->update([
                    'status' => 1,
                    'update_time' => time(),
                ]);
            if (!$claimed) {
                return true;
            }

            $contents = json_decode($item->contents, true) ?: [];
            $audios = [];
            $asrPending = false;
            $minimaxVoice = HumanVoice::where('voice_id', $item->voice_id)
                ->where('user_id', (int)$item->user_id)
                ->where('status', 1)
                ->findOrEmpty();

            if ($minimaxVoice->isEmpty()) {
                $item->status = 3;
                $item->remark = 'Minimax音色不存在或已失效';
                $item->update_time = time();
                $item->save();
                self::handleMinimaxTtsFailure($item);
                Log::channel('minimax')->write('MiniMax音色不存在，跳过音频合成' . json_encode([
                    'minimax_task_id' => $item->id,
                    'user_id' => $item->user_id,
                    'voice_id' => $item->voice_id,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return true;
            }

            // 口播音量：从关联 setting.request_json.speech_volume / extra.volume 读取（0~1）
            $speechVolume = self::resolveSpeechVolumeFromSetting((int)$item->shanjian_setting_id);

            foreach ($contents as $content) {
                $data['text'] = $content;
                $data['minimax_voice_id'] = (int)$minimaxVoice->id;
                if ($speechVolume !== null) {
                    $data['volume'] = $speechVolume;
                }
                $res = self::audio($data, $item->user_id);
                if (!$res) {
                    continue;
                }

                $audioUrl = self::$returnData;
                $audioItem = [
                    'url'        => $audioUrl,
                    'text'       => (string)$content,
                    'asr_status' => 0, // 0待ASR 1成功 2失败
                    'asr_task_id'=> '',
                    'words'      => [],
                ];

                // 仅 Minimax：TTS 后提交闪剪 ASR，用原始文字对齐逐字时间戳
                $asrTaskId = self::submitVoiceAsr($item, $audioUrl, (string)$content);
                if ($asrTaskId !== '') {
                    $audioItem['asr_task_id'] = $asrTaskId;
                    $asrPending = true;
                } else {
                    // ASR 提交失败时仍保留音频，不阻塞后续（无时间戳）
                    $audioItem['asr_status'] = 2;
                    Log::channel('voiceAsr')->write('voiceAsr提交失败，跳过时间戳对齐 text=' . $content);
                }
                $audios[] = $audioItem;
            }

            if (empty($audios)) {
                $item->status = 3;
                $item->remark = self::getError() ?: 'minimax音频合成失败';
                $item->save();

                // TTS全部失败：更新关联的 ShanjianVideoSetting 和占位任务状态
                self::handleMinimaxTtsFailure($item);

                return true;
            }

            $item->results = json_encode($audios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($asrPending) {
                // 等待 ASR 回调完成后再创建闪剪视频任务
                $item->status = 4; // ASR处理中
                $item->save();
            } else {
                $item->status = 2;
                $item->save();
                self::createShanjianVideoTasksAfterAudio($item, $audios);
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * MiniMax TTS 全部失败时，同步更新关联的 ShanjianVideoSetting 和占位任务状态
     */
    private static function handleMinimaxTtsFailure(MinimaxShanjianTask $item): void
    {
        try {
            $settingId = (int)$item->shanjian_setting_id;
            if ($settingId <= 0) {
                return;
            }

            // 多文案会拆成多条 MiniMax 任务，只有全部失败时才将整个 setting 标记失败。
            $hasRemainingTask = MinimaxShanjianTask::where('shanjian_setting_id', $settingId)
                ->where('id', '<>', (int)$item->id)
                ->whereIn('status', [0, 1, 2, 4])
                ->count() > 0;
            if (!$hasRemainingTask) {
                ShanjianVideoSetting::where('id', $settingId)
                    ->where('status', 1)
                    ->update([
                        'status'      => 5,
                        'error_num'   => 1,
                        'update_time' => time(),
                    ]);
            }

            // 更新占位视频任务(status=-1)为失败(status=2)，用 minimax_task_id 精确匹配
            $failedTaskCount = ShanjianVideoTask::where('minimax_task_id', (int)$item->id)
                ->where('status', -1)
                ->update([
                    'status'      => 2,
                    'remark'      => $item->remark ?: 'minimax音频合成失败',
                    'update_time' => time(),
                ]);
            // 兼容旧自动化占位：未写 minimax_task_id 时按 setting 回退
            if ((int)$failedTaskCount <= 0) {
                ShanjianVideoTask::where('video_setting_id', $settingId)
                    ->where('status', -1)
                    ->where('user_id', (int)$item->user_id)
                    ->whereRaw('(minimax_task_id IS NULL OR minimax_task_id = 0)')
                    ->update([
                        'status'      => 2,
                        'remark'      => $item->remark ?: 'minimax音频合成失败',
                        'update_time' => time(),
                    ]);
            }

            Log::channel('minimax')->write('MiniMax TTS失败，已更新setting和task状态' . json_encode([
                'setting_id' => $settingId,
                'minimax_task_id' => $item->id,
                'remark' => $item->remark,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::channel('minimax')->write('MiniMax TTS失败状态更新异常: ' . $e->getMessage());
        }
    }

    /**
     * 提交闪剪 ASR（当前走假接口 voiceAsr）
     * @return string 本地 asr task_id，失败返回空串
     */
    private static function submitVoiceAsr(MinimaxShanjianTask $item, string $audioUrl, string $originalText): string
    {
        try {
            $taskId = generate_unique_task_id();
            $response = \app\common\service\ToolsService::Shanjian()->voiceAsr([
                'audioUrl'        => FileService::getFileUrl($audioUrl),
                'text'            => $originalText,
                'task_id'         => $taskId,
                'user_id'         => $item->user_id,
                'minimax_task_id' => $item->id,
                'now'             => time(),
            ]);
            Log::channel('voiceAsr')->write('voiceAsr提交' . json_encode([
                'task_id'         => $taskId,
                'minimax_task_id' => $item->id,
                'user_id'         => $item->user_id,
                'audioUrl'        => FileService::getFileUrl($audioUrl),
                'text'            => $originalText,
                'response'        => $response,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (!isset($response['code']) || (int)$response['code'] !== 10000) {
                return '';
            }
            return $taskId;
        } catch (\Throwable $e) {
            Log::channel('voiceAsr')->write('voiceAsr提交异常' . $e->getMessage());
            return '';
        }
    }

    /**
     * ASR 回调：对比原始文案，用正确文字覆盖 ASR 错别字，保留逐字时间戳后落库
     */
    public static function handleAsrNotify(array $data): bool
    {
        try {
            $minimaxTaskId = (int)($data['minimax_task_id'] ?? 0);
            $asrTaskId = (string)($data['task_id'] ?? '');
            if ($minimaxTaskId <= 0 || $asrTaskId === '') {
                self::setError('ASR回调参数缺失');
                return false;
            }

            $item = MinimaxShanjianTask::where('id', $minimaxTaskId)->where('status', 4)->findOrEmpty();
            if ($item->isEmpty()) {
                // 兼容：也可能仍在 status=1
                $item = MinimaxShanjianTask::where('id', $minimaxTaskId)->whereIn('status', [1, 4])->findOrEmpty();
            }
            if ($item->isEmpty()) {
                self::setError('minimax任务不存在或状态已变更');
                return false;
            }

            $audios = json_decode($item->results, true) ?: [];
            $found = false;
            $status = strtolower((string)($data['status'] ?? ''));
            $succeed = in_array($status, ['succeed', 'success', 'succeeded', 'ok'], true)
                || (isset($data['code']) && in_array($data['code'], [10000, '10000', 'Succeed'], true));

            foreach ($audios as &$audio) {
                if (($audio['asr_task_id'] ?? '') !== $asrTaskId) {
                    continue;
                }
                $found = true;
                $originalText = (string)($audio['text'] ?? '');
                if ($succeed) {
                    $asrWords = self::extractAsrWords($data);
                    $asrText = (string)($data['result']['text'] ?? '');
                    $aligned = self::alignOriginalTextWithAsrTimestamps($originalText, $asrWords);
                    $replaceCount = 0;
                    foreach ($aligned as $row) {
                        if (($row['asr_text'] ?? '') !== '' && ($row['asr_text'] ?? '') !== ($row['text'] ?? '')) {
                            $replaceCount++;
                        }
                    }
                    // 保存：原始文案 + 对齐后逐字字幕（错别字已替换）
                    $audio['asr_text'] = $asrText;
                    $audio['words'] = array_map(static function (array $row): array {
                        return [
                            'text'    => $row['text'],
                            'startMs' => (string)(int)$row['startMs'],
                            'endMs'   => (string)(int)$row['endMs'],
                        ];
                    }, $aligned);
                    $audio['aligned_text'] = implode('', array_column($audio['words'], 'text'));
                    $audio['asr_status'] = 1;
                    Log::channel('voiceAsr')->write('ASR对齐完成' . json_encode([
                        'minimax_task_id' => $minimaxTaskId,
                        'task_id'         => $asrTaskId,
                        'original_text'   => $originalText,
                        'asr_text'        => $asrText,
                        'aligned_text'    => $audio['aligned_text'],
                        'replace_count'   => $replaceCount,
                        'word_count'      => count($audio['words']),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } else {
                    $audio['words'] = [];
                    $audio['aligned_text'] = '';
                    $audio['asr_status'] = 2;
                    $audio['asr_remark'] = $data['errorMessage'] ?? ($data['message'] ?? 'ASR失败');
                    Log::channel('voiceAsr')->write('ASR回调失败' . json_encode([
                        'minimax_task_id' => $minimaxTaskId,
                        'task_id'         => $asrTaskId,
                        'remark'          => $audio['asr_remark'],
                        'raw'             => $data,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                break;
            }
            unset($audio);

            if (!$found) {
                self::setError('未找到对应ASR子任务');
                return false;
            }

            $item->results = json_encode($audios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $item->asr_result = json_encode(self::buildAsrResultPayload($audios), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $allDone = true;
            foreach ($audios as $audio) {
                if ((int)($audio['asr_status'] ?? 0) === 0) {
                    $allDone = false;
                    break;
                }
            }

            if ($allDone) {
                $item->status = 2;
                $item->save();
                self::createShanjianVideoTasksAfterAudio($item, $audios);
            } else {
                $item->save();
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('voiceAsr')->write('ASR回调处理失败' . $e->__toString());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 组装 asr_result 字段落库结构
     */
    private static function buildAsrResultPayload(array $audios): array
    {
        $payload = [];
        foreach ($audios as $audio) {
            $payload[] = [
                'url'          => $audio['url'] ?? '',
                'text'         => $audio['text'] ?? '',
                'asr_text'     => $audio['asr_text'] ?? '',
                'aligned_text' => $audio['aligned_text'] ?? '',
                'asr_status'   => (int)($audio['asr_status'] ?? 0),
                'words'        => $audio['words'] ?? [],
            ];
        }
        return $payload;
    }

    /**
     * 从 ASR 回调中提取逐字时间信息
     * 正式格式：result.subtitle[{text,startMs,endMs}]
     */
    private static function extractAsrWords(array $data): array
    {
        $candidates = [
            $data['result']['subtitle'] ?? null,
            $data['result']['words'] ?? null,
            $data['result']['wordList'] ?? null,
            $data['result']['word_list'] ?? null,
            $data['result']['utterances'] ?? null,
            $data['data']['subtitle'] ?? null,
            $data['data']['words'] ?? null,
            $data['subtitle'] ?? null,
            $data['words'] ?? null,
        ];

        foreach ($candidates as $list) {
            if (!is_array($list) || empty($list)) {
                continue;
            }
            // utterances 可能是句子级，再拆 words
            if (isset($list[0]['words']) && is_array($list[0]['words'])) {
                $flat = [];
                foreach ($list as $utt) {
                    foreach (($utt['words'] ?? []) as $w) {
                        $flat[] = $w;
                    }
                }
                return $flat;
            }
            return $list;
        }
        return [];
    }

    /**
     * 用 ASR 逐字时间戳对齐原始文字：
     * - 时间戳以 ASR 为准，不做整体平移
     * - 仅替换谐音字/错别字为原文，标点差异走序列对齐，避免空字符/标点导致后续时间错位
     *
     * @param string $originalText 原始准确文字
     * @param array  $asrWords     ASR 返回的字列表（含 startMs/endMs）
     * @return array [['text'=>'原','asr_text'=>'识','startMs'=>0,'endMs'=>120], ...]
     */
    public static function alignOriginalTextWithAsrTimestamps(string $originalText, array $asrWords): array
    {
        $originalChars = self::splitTextToChars($originalText);
        $normalizedAsr = [];
        foreach ($asrWords as $word) {
            if (!is_array($word)) {
                continue;
            }
            $text = (string)($word['text'] ?? $word['word'] ?? $word['char'] ?? '');
            // ASR 空字符（如 AI 前后空白）只会造成索引错位，直接丢弃
            if (trim($text) === '') {
                continue;
            }
            $start = $word['startMs'] ?? $word['start'] ?? $word['startTime'] ?? $word['begin_time'] ?? $word['beginTime'] ?? $word['start_time'] ?? 0;
            $end = $word['endMs'] ?? $word['end'] ?? $word['endTime'] ?? $word['end_time'] ?? $word['finish_time'] ?? $start;
            $chars = self::splitTextToChars($text);
            $charCount = count($chars);
            if ($charCount === 0) {
                continue;
            }
            if ($charCount === 1) {
                $normalizedAsr[] = [
                    'text'    => $chars[0],
                    'startMs' => (float)$start,
                    'endMs'   => (float)$end,
                ];
                continue;
            }
            // 一项含多字时按字均分时间
            $duration = max((float)$end - (float)$start, 0);
            $step = $duration / $charCount;
            foreach ($chars as $i => $ch) {
                $normalizedAsr[] = [
                    'text'    => $ch,
                    'startMs' => (float)$start + $step * $i,
                    'endMs'   => (float)$start + $step * ($i + 1),
                ];
            }
        }

        $origCount = count($originalChars);
        $asrCount = count($normalizedAsr);
        if ($origCount === 0) {
            return [];
        }
        if ($asrCount === 0) {
            $aligned = [];
            foreach ($originalChars as $ch) {
                $aligned[] = ['text' => $ch, 'asr_text' => '', 'startMs' => 0, 'endMs' => 0];
            }
            return $aligned;
        }

        return self::alignCharsByEditDistance($originalChars, $normalizedAsr);
    }

    /**
     * Needleman-Wunsch 序列对齐：保留 ASR 时间戳，文字取原文（替换错别字/谐音）
     */
    private static function alignCharsByEditDistance(array $originalChars, array $asrWords): array
    {
        $n = count($originalChars);
        $m = count($asrWords);

        // score[i][j]：原文前 i 字 vs ASR 前 j 字
        $score = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        $trace = array_fill(0, $n + 1, array_fill(0, $m + 1, 0)); // 0 diag, 1 up(原插入), 2 left(ASR多余)

        for ($i = 1; $i <= $n; $i++) {
            $score[$i][0] = $i * -1;
            $trace[$i][0] = 1;
        }
        for ($j = 1; $j <= $m; $j++) {
            $score[0][$j] = $j * -1;
            $trace[0][$j] = 2;
        }

        for ($i = 1; $i <= $n; $i++) {
            $o = $originalChars[$i - 1];
            $oPunct = self::isPunctuationChar($o);
            for ($j = 1; $j <= $m; $j++) {
                $a = (string)$asrWords[$j - 1]['text'];
                $aPunct = self::isPunctuationChar($a);

                if ($o === $a) {
                    $diag = $score[$i - 1][$j - 1] + 3;
                } elseif ($oPunct && $aPunct) {
                    // 标点形态不同（，。！？等）仍对齐，避免后续时间整体漂移
                    $diag = $score[$i - 1][$j - 1] + 2;
                } elseif ($oPunct !== $aPunct) {
                    // 正文与标点不对齐，宁可走 gap
                    $diag = $score[$i - 1][$j - 1] - 2;
                } else {
                    // 正文错别字/谐音：允许替换，优于插删
                    $diag = $score[$i - 1][$j - 1] + 1;
                }
                $up = $score[$i - 1][$j] - 1;   // 原文多一字
                $left = $score[$i][$j - 1] - 1; // ASR 多一字

                if ($diag >= $up && $diag >= $left) {
                    $score[$i][$j] = $diag;
                    $trace[$i][$j] = 0;
                } elseif ($up >= $left) {
                    $score[$i][$j] = $up;
                    $trace[$i][$j] = 1;
                } else {
                    $score[$i][$j] = $left;
                    $trace[$i][$j] = 2;
                }
            }
        }

        // 回溯得到对齐对：['orig'=>?,'asr'=>?]
        $pairs = [];
        $i = $n;
        $j = $m;
        while ($i > 0 || $j > 0) {
            $dir = $trace[$i][$j] ?? ($i > 0 ? 1 : 2);
            if ($i > 0 && $j > 0 && $dir === 0) {
                $pairs[] = ['orig' => $originalChars[$i - 1], 'asrIdx' => $j - 1];
                $i--;
                $j--;
            } elseif ($i > 0 && ($j === 0 || $dir === 1)) {
                $pairs[] = ['orig' => $originalChars[$i - 1], 'asrIdx' => null];
                $i--;
            } else {
                // ASR 多出的字：空字符已过滤；多余标点/噪声直接丢弃，避免污染时间轴
                $j--;
            }
        }
        $pairs = array_reverse($pairs);

        $aligned = [];
        $pairCount = count($pairs);
        for ($k = 0; $k < $pairCount; $k++) {
            $pair = $pairs[$k];
            $orig = $pair['orig'];
            $asrIdx = $pair['asrIdx'];
            if ($asrIdx !== null) {
                $asr = $asrWords[$asrIdx];
                $aligned[] = [
                    'text'     => $orig,
                    'asr_text' => (string)$asr['text'],
                    'startMs'  => (float)$asr['startMs'],
                    'endMs'    => (float)$asr['endMs'],
                ];
                continue;
            }

            // 原文多出的字：夹在相邻 ASR 时间之间，零时长挂在前一字结束点，避免打乱整体时间戳
            $prevEnd = 0.0;
            if (!empty($aligned)) {
                $prevEnd = (float)$aligned[count($aligned) - 1]['endMs'];
            } else {
                // 找后续第一个有 ASR 的时间
                for ($t = $k + 1; $t < $pairCount; $t++) {
                    if ($pairs[$t]['asrIdx'] !== null) {
                        $prevEnd = (float)$asrWords[$pairs[$t]['asrIdx']]['startMs'];
                        break;
                    }
                }
            }
            $aligned[] = [
                'text'     => $orig,
                'asr_text' => '',
                'startMs'  => $prevEnd,
                'endMs'    => $prevEnd,
            ];
        }

        return $aligned;
    }

    /**
     * 是否为标点/符号（中英文常见标点）
     */
    private static function isPunctuationChar(string $ch): bool
    {
        if ($ch === '') {
            return false;
        }
        return (bool)preg_match('/^[\p{P}\p{S}]$/u', $ch);
    }

    /**
     * 按字拆分（中文按字，英文/数字按字符；忽略空白）
     */
    private static function splitTextToChars(string $text): array
    {
        $text = preg_replace('/\s+/u', '', $text) ?? '';
        if ($text === '') {
            return [];
        }
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * ASR/TTS 全部完成后，创建闪剪视频任务
     */
    private static function createShanjianVideoTasksAfterAudio(MinimaxShanjianTask $item, array $audios): void
    {
        $shanjianSetting = ShanjianVideoSetting::where('id', $item->shanjian_setting_id)->findOrEmpty();
        if ($shanjianSetting->isEmpty() || empty($shanjianSetting->request_json)) {
            return;
        }

        $params = json_decode($shanjianSetting->request_json, true) ?: [];
        $params['user_id'] = $params['user_id'] ?? $item->user_id;

        $settingId = (int)$item->shanjian_setting_id;
        $minimaxTaskId = (int)$item->id;

        // 手动 type3 / 自动化：优先回填建单时预创建的占位任务(status=-1)
        // 优先用 minimax_task_id 精确匹配，兼容旧数据回退到 video_setting_id + status
        $placeholderTasks = ShanjianVideoTask::where('minimax_task_id', $minimaxTaskId)
            ->where('status', -1)
            ->order('id', 'asc')
            ->select();
        if (!$placeholderTasks->isEmpty()) {
            foreach ($placeholderTasks as $placeholderTask) {
                self::activatePendingMinimaxVideoTask($placeholderTask, $audios, $settingId);
            }
            return;
        }

        // 已按 minimax_task_id 关联过（含已回填/已失败）：重复 ASR/TTS 回调直接跳过，禁止再批量新建
        $linkedTaskCount = (int)ShanjianVideoTask::where('minimax_task_id', $minimaxTaskId)->count();
        if ($linkedTaskCount > 0) {
            Log::channel('minimax')->write('MiniMax完成后跳过新建: 占位任务已回填过' . json_encode([
                'minimax_task_id' => $minimaxTaskId,
                'setting_id' => $settingId,
                'linked_task_count' => $linkedTaskCount,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        // 兼容旧数据：旧占位任务没有 minimax_task_id，只能按 setting 查一条。
        $placeholderTask = ShanjianVideoTask::where('video_setting_id', $settingId)
            ->where('status', -1)
            ->where('user_id', (int)$item->user_id)
            ->order('id', 'asc')
            ->find();
        if ($placeholderTask) {
            self::activatePendingMinimaxVideoTask($placeholderTask, $audios, $settingId);
            return;
        }

        // setting 下已有视频任务时，禁止走「无占位则整批新建」兜底。
        // 典型误伤：video_count < 文案数时，未绑定占位的多余 MiniMax 任务完成后会再插一套 nested_loop 任务。
        $existingTaskCount = (int)ShanjianVideoTask::where('video_setting_id', $settingId)->count();
        if ($existingTaskCount > 0) {
            Log::channel('minimax')->write('MiniMax完成后跳过新建: setting已有视频任务' . json_encode([
                'minimax_task_id' => $minimaxTaskId,
                'setting_id' => $settingId,
                'existing_task_count' => $existingTaskCount,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        // 兼容旧自动化数据：无占位任务时按 auto_pending_task 新建
        if (!empty($params['auto_pending_task']) && is_array($params['auto_pending_task'])) {
            self::createAutoVideoTaskAfterMinimaxAudio($shanjianSetting, $params, $audios);
            return;
        }

        $decodedData = [];

        if ((int)$shanjianSetting->shanjian_type === 1) {
            $jsonFields = ['anchor', 'voice', 'character_design', 'material', 'clip', 'music', 'extra', 'audio'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            $params['audio'] = json_encode($audios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $decodedData['audio'] = $audios;
            ShanjianVideoSettingLogic::createVideoTasks($settingId, $params, $decodedData);
            return;
        }

        if ((int)$shanjianSetting->shanjian_type === 3) {
            $jsonFields = ['material', 'clip', 'music', 'extra', 'audio'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            // 无任何视频任务时才兼容旧数据整批新建
            $params['audio'] = json_encode($audios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $decodedData['audio'] = $audios;
            $extra = $decodedData['extra'] ?? [];
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;
            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $materialCount;
            ShanjianVideoSettingLogic::createVideoTasksType3($settingId, $params, $decodedData);
            return;
        }

        // type5 数字人口播无包装：无占位时用 TTS 音频兜底建任务
        if ((int)$shanjianSetting->shanjian_type === 5) {
            $jsonFields = ['anchor', 'voice', 'character_design', 'material', 'clip', 'music', 'extra', 'audio', 'copywriting'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            $params['audio'] = json_encode($audios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $decodedData['audio'] = $audios;
            $aiClipEnabled = !empty($params['ai_clip_enabled'])
                && filter_var($params['ai_clip_enabled'], FILTER_VALIDATE_BOOLEAN);
            $packaging = [];
            if (!empty($params['packaging'])) {
                if (is_array($params['packaging'])) {
                    $packaging = $params['packaging'];
                } else {
                    $decodedPackaging = json_decode((string)$params['packaging'], true);
                    $packaging = json_last_error() === JSON_ERROR_NONE ? ($decodedPackaging ?: []) : [];
                }
            }
            ShanjianVideoSettingLogic::createVideoTasksType5(
                $settingId,
                $params,
                $decodedData,
                $aiClipEnabled,
                $packaging
            );
        }
    }

    /**
     * 从 setting 解析口播音量（0~1），无则返回 null
     */
    private static function resolveSpeechVolumeFromSetting(int $settingId): ?float
    {
        if ($settingId <= 0) {
            return null;
        }
        $setting = ShanjianVideoSetting::where('id', $settingId)->findOrEmpty();
        if ($setting->isEmpty() || empty($setting->request_json)) {
            return null;
        }
        $req = json_decode((string)$setting->request_json, true) ?: [];
        if (isset($req['speech_volume']) && is_numeric($req['speech_volume'])) {
            $vol = (float)$req['speech_volume'];
            return max(0.0, min(1.0, $vol));
        }
        $extra = $req['extra'] ?? [];
        if (is_string($extra)) {
            $extra = json_decode($extra, true) ?: [];
        }
        if (isset($extra['volume']) && is_numeric($extra['volume'])) {
            $vol = (float)$extra['volume'];
            return max(0.0, min(1.0, $vol));
        }
        return null;
    }

    /**
     * 将 status=-1 的 MiniMax 占位任务回填为可下发(status=0)
     * 蝉镜桥接(engine_type=2)：回填 HumanVideoTask.audio_url，桥接任务保持 status=-1 等待蝉镜完成
     * 手动 addType3 / 自动化共用
     */
    private static function activatePendingMinimaxVideoTask(ShanjianVideoTask $pendingTask, array $audios, int $settingId): void
    {
        $pendingMsg = trim((string)($pendingTask->msg ?? ''));
        $audio = null;
        foreach ($audios as $item) {
            if (!is_array($item) && !is_string($item)) {
                continue;
            }
            $normalized = ShanjianVideoSettingLogic::normalizeAudioItem($item);
            if ($normalized['url'] === '') {
                continue;
            }
            if ($pendingMsg !== '' && trim($normalized['text']) === $pendingMsg) {
                $audio = $normalized;
                break;
            }
            if ($audio === null) {
                $audio = $normalized;
            }
        }
        if ($audio === null || $audio['url'] === '') {
            // 兜底取第一段
            $audio = ShanjianVideoSettingLogic::normalizeAudioItem($audios[0] ?? []);
        }
        if ($audio['url'] === '') {
            Log::channel('minimax')->write('MiniMax占位任务回填失败：无可用音频 setting_id=' . $settingId);
            return;
        }

        $taskExtra = is_array($pendingTask->extra)
            ? $pendingTask->extra
            : (json_decode((string)($pendingTask->extra ?? '{}'), true) ?: []);
        $isChanjingBridge = (int)($taskExtra['engine_type'] ?? 0) === ShanjianVideoSettingLogic::ENGINE_TYPE_CHANJING
            || ($taskExtra['waiting_engine'] ?? '') === 'chanjing';

        if ($isChanjingBridge) {
            // 回填蝉镜任务音频，放行 videoTaskCron；桥接 type5 仍保持 -1
            HumanVideoTask::where('task_id', (string)$pendingTask->task_id)
                ->where('model_version', 7)
                ->where('status', 0)
                ->update([
                    'audio_type' => 2,
                    'audio_url' => $audio['url'],
                    'upload_audio_url' => $audio['url'],
                    'update_time' => time(),
                ]);

            $updateData = [
                'audio_type'  => 2,
                'audio_url'   => $audio['url'],
                'msg'         => $audio['text'] !== '' ? $audio['text'] : ($pendingTask->msg ?? ''),
                'update_time' => time(),
            ];
            if (!empty($audio['words'])) {
                $taskExtra['timed_words'] = $audio['words'];
                $updateData['extra'] = json_encode($taskExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $affected = ShanjianVideoTask::where('id', (int)$pendingTask->id)
                ->where('status', -1)
                ->update($updateData);

            Log::channel('minimax')->write('MiniMax蝉镜桥接已回填音频' . json_encode([
                'task_id' => $pendingTask->task_id,
                'setting_id' => $settingId,
                'audio_url' => $audio['url'],
                'affected' => $affected,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        $updateData = [
            'status'      => 0, // 就绪，compositeVideoCron 可拾取
            'audio_type'  => 2, // 音频驱动（TTS 已完成）
            'audio_url'   => $audio['url'],
            'msg'         => $audio['text'] !== '' ? $audio['text'] : ($pendingTask->msg ?? ''),
            'update_time' => time(),
        ];
        if (!empty($audio['words'])) {
            $taskExtra['timed_words'] = $audio['words'];
            $updateData['extra'] = json_encode($taskExtra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $affected = ShanjianVideoTask::where('id', (int)$pendingTask->id)
            ->where('status', -1) // 原子条件，防并发重复更新
            ->update($updateData);

        Log::channel('minimax')->write('MiniMax占位任务已更新为就绪' . json_encode([
            'task_id'      => $pendingTask->task_id,
            'setting_id'   => $settingId,
            'audio_url'    => $audio['url'],
            'has_subtitle' => !empty($audio['words']),
            'affected'     => $affected,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 兼容旧自动化数据：无 status=-1 占位任务时，用预存模板新建闪剪视频任务
     */
    private static function createAutoVideoTaskAfterMinimaxAudio(ShanjianVideoSetting $setting, array $params, array $audios): void
    {
        $taskData = $params['auto_pending_task'] ?? [];
        if (!is_array($taskData) || empty($taskData)) {
            Log::channel('minimax')->write('自动化MiniMax缺少auto_pending_task setting_id=' . $setting->id);
            return;
        }

        $audio = null;
        $pendingMsg = trim((string)($taskData['msg'] ?? ''));
        foreach ($audios as $item) {
            if (!is_array($item) || empty($item['url'])) {
                continue;
            }
            if ($pendingMsg !== '' && trim((string)($item['text'] ?? '')) === $pendingMsg) {
                $audio = $item;
                break;
            }
            if ($audio === null) {
                $audio = $item;
            }
        }
        if ($audio === null || empty($audio['url'])) {
            Log::channel('minimax')->write('自动化MiniMax无可用音频 setting_id=' . $setting->id);
            return;
        }

        $audioUrl = $audio['url'];
        if (is_array($audioUrl)) {
            $audioUrl = (string)($audioUrl['url'] ?? '');
        }
        $audioUrl = trim((string)$audioUrl);
        if ($audioUrl === '') {
            Log::channel('minimax')->write('自动化MiniMax音频URL为空 setting_id=' . $setting->id);
            return;
        }

        $extra = [];
        if (!empty($taskData['extra'])) {
            $extra = is_array($taskData['extra'])
                ? $taskData['extra']
                : (json_decode((string)$taskData['extra'], true) ?: []);
        }
        if (!empty($audio['words']) && is_array($audio['words'])) {
            $extra['timed_words'] = $audio['words'];
        }

        $taskData['status'] = 0;
        $taskData['audio_url'] = $audioUrl;
        $taskData['audio_type'] = 2;
        if (!empty($audio['text'])) {
            $taskData['msg'] = (string)$audio['text'];
        }
        $taskData['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $taskData['video_setting_id'] = $setting->id;
        $taskData['user_id'] = (int)($taskData['user_id'] ?? $setting->user_id ?? 0);
        $taskData['create_time'] = time();
        $taskData['update_time'] = time();
        if (empty($taskData['task_id'])) {
            $taskData['task_id'] = generate_unique_task_id();
        }

        $materialLogs = $params['auto_material_logs'] ?? [];

        $task = Db::transaction(static function () use ($setting, $taskData, $materialLogs) {
            // 锁定 setting 串行化重复 ASR 回调；task_id 再做一次业务幂等判断。
            ShanjianVideoSetting::where('id', (int)$setting->id)->lock(true)->find();
            $existingTask = ShanjianVideoTask::where('task_id', (string)$taskData['task_id'])->findOrEmpty();
            if (!$existingTask->isEmpty()) {
                return $existingTask;
            }

            $createdTask = ShanjianVideoTask::create($taskData);
            if (!empty($materialLogs) && is_array($materialLogs)) {
                foreach ($materialLogs as &$log) {
                    if (!is_array($log)) {
                        continue;
                    }
                    $log['task_id'] = $createdTask->id;
                }
                unset($log);
                $validLogs = array_values(array_filter($materialLogs, static function ($log) {
                    return is_array($log);
                }));
                if (!empty($validLogs)) {
                    MaterialUseLog::insertAll($validLogs);
                }
            }

            return $createdTask;
        });

        Log::channel('minimax')->write('自动化MiniMax闪剪任务已创建(兼容旧数据)' . json_encode([
            'setting_id' => $setting->id,
            'task_id' => $task->id ?? 0,
            'audio_url' => $audioUrl,
            'has_timed_words' => !empty($extra['timed_words']),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function audio(array $params, int $userId)
    {
        try {
            $taskId = generate_unique_task_id();
            $find   = HumanVoice::where('status', 1)->where('id', $params['minimax_voice_id'])->where('user_id', $userId)->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('音色不存在');
                return false;
            }
            $model  = $find->model_version == 10 ? 'speech-2.8-hd' : 'speech-2.8-turbo';
            $scene  = $model == 'speech-2.8-hd' ? self::MINIMAX_AUDIO_HD : self::MINIMAX_AUDIO_TURBO;
            $request = [
                'text'             => $params['text'],
                'model'            => $model,
                'minimax_voice_id' => $find['voice_id'],
                'task_id'          => $taskId,
                'user_id'          => $userId,
                'now'              => time(),
            ];
            // 口播音量 0~1 → MiniMax vol 约 0.1~10（默认 1 对应 0.1）
            if (isset($params['volume']) && is_numeric($params['volume'])) {
                $vol01 = max(0.0, min(1.0, (float)$params['volume']));
                $request['volume'] = round(max(0.1, min(10.0, $vol01 * 10)), 1);
            }
            $response = self::requestUrl($request, $scene, $userId, $taskId);
            Log::channel('minimax')->write('minimax音频合成参数' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ((int)$response['code'] !== 10000) {
                $errMsg = $response['message'] ?? ($response['msg'] ?? 'Minimax音频合成失败');
                if (stripos($errMsg, 'voice id not exist') !== false) {
                    $errMsg = '未找到该音色ID';
                }
                self::setError($errMsg);
                return false;
            }
            $audioUrl = '';
            // 二进制音频文件保存本地，上传至oss后删除本地文件
            if (!empty($response['data']['data']['audio'])) {
                $hexAudio    = $response['data']['data']['audio'];
                $binaryAudio = hex2bin($hexAudio);
                if ($binaryAudio === false) {
                    throw new Exception('audio hex 解码失败');
                }
                $saveDir = root_path() . 'public/uploads/audio/' . date('Ymd') . '/';
                $ossSaveDir = 'uploads/audio/' . date('Ymd');
                if (!is_dir($saveDir)) {
                    mkdir($saveDir, 0755, true);
                }
                $fileName    = 'tmp_audio_' . date('YmdHis') . '_' . mt_rand(10000000, 99999999) . '.mp3';
                $filePath    = $saveDir . $fileName;
                $writeResult = file_put_contents($filePath, $binaryAudio, LOCK_EX);
                if ($writeResult === false) {
                    throw new Exception('音频文件保存失败');
                }
                $audioResult = UploadService::uploadFileToOSS($filePath, $ossSaveDir);
                if ($audioResult['result'] === false){
                    throw new Exception('上传文件到oss失败');
                }
                $audioUrl = $audioResult['url'];
                Log::channel('minimax')->write('minimax音频合成成功：' . $audioUrl);
                @unlink($filePath);
            }
            self::$returnData = $audioUrl;
            return true;
        } catch (\Exception $e) {
            Log::channel('minimax')->write('minimax音频合成失败：' . $e->__toString());
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Minimax();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::MINIMAX_VOICE_HD => ['human_voice_minimax_hd', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD],
            self::MINIMAX_VOICE_TURBO => ['human_voice_minimax_turbo', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO],
            self::MINIMAX_AUDIO_HD => ['human_audio_minimax_hd', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_HD],
            self::MINIMAX_AUDIO_TURBO => ['human_audio_minimax_turbo', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_TURBO],
            default => ['', '']
        };

        if (in_array($scene, [self::MINIMAX_AUDIO_HD, self::MINIMAX_AUDIO_TURBO])){
            $textNum = mb_strlen($request['text']) ?? 0;
            $request['text_num'] = $textNum;
            $unit = TokenLogService::checkToken($userId, $tokenScene, $textNum);
        }else{
            $unit = TokenLogService::checkToken($userId, $tokenScene);
        }

        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now']     = time();
        switch ($scene) {
            case self::MINIMAX_UPLOAD:
                $response = $requestService->upload($request);
                break;
            case self::MINIMAX_VOICE_HD:
            case self::MINIMAX_VOICE_TURBO:
                $response = $requestService->voiceClone($request);
                break;
            case self::MINIMAX_AUDIO_HD:
            case self::MINIMAX_AUDIO_TURBO:
                $response = $requestService->audio($request);
                break;
            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = match ($scene) {
                self::MINIMAX_AUDIO_TURBO, self::MINIMAX_AUDIO_HD => round( ($textNum ?? 0) * $unit,2),
                default                                           => $unit,
            };

            if ($points > 0) {

                $extra = [];
                switch ($scene) {
                    case self::MINIMAX_VOICE_TURBO:
                    case self::MINIMAX_VOICE_HD:
                        $extra = ['算力单价' => $unit.'算力/次', '实际消耗算力' => $points];
                        break;
                    case self::MINIMAX_AUDIO_TURBO:
                    case self::MINIMAX_AUDIO_HD:
                        $extra = ['算力单价' => $unit.'算力/字', '实际消耗算力' => $points];
                        break;
                    default:
                }
                //token扣除
                User::userTokensChange($userId, $points);

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        return $response;
    }

    public static function refundTokens(int $userId, string $result_id, string $taskId, string $type): bool
    {

        try {
            [$typeIndex, $typeID] = match ($type) {
                'human_voice_minimax_hd' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_HD],
                'human_voice_minimax_turbo' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CLONE_MINIMAX_TURBO],
                'human_audio_minimax_hd' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_HD],
                'human_audio_minimax_turbo' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CREATE_MINIMAX_TURBO],
            };
            // 请求查询接口
            $requestParams = [
                'taskId'  => $result_id,
                'task_id' => $taskId
            ];
            $response      = \app\common\service\ToolsService::minimax()->status($requestParams);
            if (isset($response['code']) && $response['code'] == 10000) {
                return true;
            }
            $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('minimax')->write('minimax退费失败' . $e->getMessage());
            return false;
        }
    }

    public static function musicLists()
    {
        for ($i = 1; $i <= 20; $i++) {
            $music[] = config('app.app_host') . '/static/audio/music/' . $i . '.mp3';
        }
        self::$returnData = $music;
        return true;
    }
}
