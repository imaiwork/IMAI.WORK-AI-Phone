<?php

namespace app\api\logic\videoImitation;

use AlibabaCloud\SDK\Mts\V20140618\Models\QueryCopyrightJobResponseBody\data;
use app\api\logic\service\TokenLogService;
use app\api\logic\sv\ToolsLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\Material;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use app\common\service\ToolsService;
use app\common\service\VideoInfoService;
use think\facade\Db;
use think\facade\Log;

class TaskLogic extends BaseLogic
{
    /**
     * 获取任务详情
     * @param int $id
     * @param int $userId
     * @return array|bool
     */
    public static function detail(int $id, int $userId)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        $data = $task->toArray();

        $data['avatar_name'] = '';
        if ($data['avatar_id']) {
            $avatar = AiPersonaDigitalAvatar::where('id', $data['avatar_id'])->find();
            if ($avatar) {
                $data['avatar_name'] = $avatar['avatar_name'];
            }
        }

        $data['voice_name'] = '';
        if ($data['is_material'] == 0) {
            $avatar = AiPersonaDigitalAvatar::where('id', $task->voice_id)->find();
            if ($avatar) {
                $data['voice_name'] = $avatar['voice_name'];
            }
        } else {
            if ($data['voice_id'] != 0) {
                $voice = AiPersonaDigitalVoice::where('voice_id', $data['voice_id'])->find();
                if ($voice) {
                    $data['voice_name'] = $voice['voice_name'];
                }
            }
        }


        $data['video_url'] = !empty($data['video_url']) ? FileService::getFileUrl($data['video_url']) : '';
        $data['thumbnail'] = !empty($data['thumbnail']) ? FileService::getFileUrl($data['thumbnail']) : '';
        if (!empty($data['analysis_tags']) && is_string($data['analysis_tags'])) {
            $data['analysis_tags'] = json_decode($data['analysis_tags'], true) ?: [];
        }

        return $data;
    }

    /**
     * 确认发布文案
     * @param int $id
     * @param mixed $userId
     * @param string $publishText
     * @return bool
     */
    public static function confirmPublishText(int $id, $userId, string $publishText, string $publishTopic): bool
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        $task->publish_text = $publishText;
        $task->publish_topic = $publishTopic;
        $task->publish_confirm = 1;
        $task->save();

        return true;
    }

    /**
     * 删除任务 (软删除)仅修改删除任务标识
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function delete(int $id, int $userId)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError('未找到该视频复刻记录');
            return false;
        }
        $task->task_delete = 1;
        return $task->save();
    }

    /**
     * 用户确认文案并下发生成视频任务
     * @param int $id 任务ID
     * @param int $userId 用户ID
     * @param string $rewrittenText 仿写文案
     * @return array|bool
     * @throws \Exception
     */
    public static function generate(int $id, int $userId, string $rewrittenText)
    {
        $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("未找到该视频复刻记录");
            return false;
        }

        if ($task->status >= 2) {
            self::setError("该任务已在生成处理中或已完成，无法再次下发");
            return false;
        }

        // 分析使用的资源
        $isMaterial = 0;
        $avatarId = '';
        $voiceId = '';
        $materials = [];

        $introduceCard = [];
        $persona = AiPersona::where('id', $task->persona_id)->where('user_id', $userId)->find();
        if ($persona) {
            if ($persona['persona_name'] != '') {
                $introduceCard['name'] = $persona['persona_name'];

                if ($persona['quick_desc'] != '') {
                    $introduceCard['description'] = $persona['quick_desc'];
                }
            }

            // 获取随机辅助素材集（不区分类型均需配备对应的素材以作兜底或补刀混合）
            $materials = self::getRandomMaterials($task->persona_id, $userId);

            // 1. 优先提取数字人形象（随机选择）
            $avatar = AiPersonaDigitalAvatar::where('persona_id', $task->persona_id)->orderRand()->find();
            if ($avatar && !empty($avatar['third_avatar_id'])) {
                $isMaterial = 0;
                $avatarId = $avatar['third_avatar_id'];
                $task->avatar_id = $avatar['id'];

                $voiceId = $avatar['third_voice_id'];
                $task->voice_id = $avatar['id'];
            } else {
                // 2. 降级使用素材
                $isMaterial = 1;

                // 随机取一个音色
                $voice = AiPersonaDigitalVoice::where('persona_id', $task->persona_id)
                    ->where('user_id', $userId)
                    ->where('provider', 'shanjian')
                    ->orderRand()
                    ->find();
                if ($voice && !empty($voice['third_voice_id'])) {
                    $voiceId = $voice['third_voice_id'];
                    $task->voice_id = $voice['voice_id'];
                }
            }
        }

        // 检查当前人设是否满足生成视频条件
        if ($isMaterial == 0 && (empty($avatarId) || empty($voiceId))) {
            self::setError("当前AI人设未绑定可用的数字人形象和音色，无法生成视频");
            return false;
        }
        if ($isMaterial == 1 && (empty($materials) || empty($voiceId))) {
            self::setError("当前AI人设未绑定可用的形象或图片、视频素材，无法生成视频");
            return false;
        }

        // 估算时长并扣费
        $duration = (int) (mb_strlen($rewrittenText, 'UTF-8') / 3);
        $duration = $duration > 0 ? $duration : 1;

        $tokenScene = $isMaterial == 0 ? 'human_video_shanjian' : 'shanjian_broadcast_mixcut';
        $tokenCode = $isMaterial == 0 ? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN : AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN;

        try {
            $unit = TokenLogService::checkToken($userId, $tokenScene, 1);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }

        $extraDesc = $isMaterial == 0 ? '数字人混剪' : '素材混剪';
        $points = $unit * $duration;

        // 预扣费与任务状态保存(开启事务)
        Db::startTrans();
        try {
            if ($points > 0) {
                User::userTokensChange($userId, $points);
                $extra = ['扣费项目' => '爆款视频复刻-' . $extraDesc, '算力单价' => $unit, "预估时长" => $duration . '秒', '实际消耗算力' => $points];
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, (string) $task->id, $extra);
            }
            $task->duration = $duration;
            $task->status = 2; // 先更新为处理中
            $task->save();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            self::setError("启动生成前预扣费失败：" . $e->getMessage());
            return false;
        }

        // 组装接口数据
        $shanjianPayload = [
            "packRules" => [
                "backgroundMusic" => [
                    "audioSwitch" => true,
                    "volume" => 0.5
                ]
            ],
            "processRules" => [
                "watermarkShow" => false
            ],
            "materials" => $materials,
            "task_id" => (string) $task->id,
            "user_id" => (string) $userId,
            "now" => time(),
            "title" => $task->title ?: date('Y-m-d H:i:s'),
            "content" => $rewrittenText,
            "speakerId" => $voiceId,
        ];

        $task->title = $shanjianPayload['title'];

        if (!empty($introduceCard)) {
            $shanjianPayload['introduceCard'] = $introduceCard;
        }

        // 获取视频风格(剪辑模板)
        $styleScene = $isMaterial == 0 ? 'virtualman' : 'oralMixCutting';
        $clip = ShanjianClipTemplate::where('scene', $styleScene)->orderRand()->find();
        $shanjianPayload['styleId'] = $clip['id'];

        $videoService = ToolsService::VideoImitation();
        if ($isMaterial == 0) {
            $shanjianPayload['virtualmanId'] = $avatarId;
            $response = $videoService->virtualmanBroadcast($shanjianPayload);
        } else {
            $shanjianPayload['materials'] = $materials;
            $shanjianPayload['materialSoundSwitch'] = true;
            $response = $videoService->mixcutBroadcast($shanjianPayload);
        }
        Log::channel("shanjian")->write("[响应]下发视频仿写任务：" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (isset($response['code']) && $response['code'] == 10000 && $response['data']['code'] == 'Succeed') {
            // 第三方任务下发成功
            $task->shanjian_task_id = $response['data']['data']['taskId'] ?? '';
            $task->is_material = $isMaterial;

            // 提取标题 (生成短标题)
            ToolsLogic::getMatrixCopywriting(['user_id' => $userId, 'keywords' => $rewrittenText, 'number' => 1]);
            $titleResponse = ToolsLogic::getReturnData();
            if (!empty($titleResponse[0])) {
                $task->publish_title = $titleResponse[0]['title'];
                $task->publish_text = $titleResponse[0]['content'];
                $task->publish_topic = json_encode($titleResponse[0]['topic'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $task->publish_title = mb_substr($rewrittenText, 0, 10, 'UTF-8');
                $task->publish_text = $rewrittenText;
            }

            // 保存用户确认的仿写文案
            $task->rewritten_text = $rewrittenText;

            // status 已经在上面变成 2 了
            $task->save();
            return ['task_id' => $task->id];
        } else {
            // 下发失败回退(开启事务)
            Db::startTrans();
            try {
                $task->status = 1; // 回退状态
                $task->save();
                if ($points > 0) {
                    AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $points, (string) $task->id, ['扣费项目' => '生成接口下发失败回退']);
                }
                Db::commit();
            } catch (\Exception $ex) {
                Db::rollback();
            }

            self::setError($response['message'] ?? '第三方平台任务下发失败');
            return false;
        }
    }

    /**
     * 服务端回调 - 更新视频状态并处理完结资源等
     */
    public static function notify(array $data)
    {
        $userId = $data['user_id'] ?? 0;
        $taskId = $data['task_id'] ?? 0;
        $statusStr = $data['status'] ?? '';

        // 【第一重检查】获取锁之前先查一次数据库
        $task = VideoImitationTask::where('id', $taskId)->where('user_id', $userId)->find();
        if (!$task) {
            self::setError("不存在的任务");
            return false;
        }

        // 如果已经成功或者失败了，无需再尝试获取锁和处理
        if ($task->status >= 3) {
            return true;
        }

        $lockKey = 'video_imitation_notify_' . $taskId;
        // 使用 Redis 分布式锁 (setnx)
        try {
            $redis = \think\facade\Cache::store('redis')->handler();
            if (!$redis->setnx($lockKey, 1)) {
                self::setError("任务正在处理中，请勿重复请求");
                return false;
            }
            $redis->expire($lockKey, 60); // 锁60秒
        } catch (\Exception $e) {
            // 如果 redis 异常，报错
            self::setError("获取分布式锁失败");
            return false;
        }

        try {
            // 【第二重检查】获取锁之后，重新加载数据，防止在等待锁的极短期间状态已被其他并发请求更改
            $task->refresh();
            if ($task->status >= 3) {
                return true;
            }

            $videoUrl = $data['url'] ?? ($data['result']['videoUrl'] ?? '');
            $duration = $data['duration'] ?? ($data['result']['duration'] ?? 0);
            $errorMessage = $data['message'] ?? ($data['errorMessage'] ?? ($data['reason'] ?? ''));

            $tokenCode = $task->is_material == 0 ? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN : AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN;

            if ($statusStr === 'succeed' || $statusStr == 3 || !empty($videoUrl)) {
                // 生成成功
                Db::startTrans();
                try {
                    $task->status = 3; // 成功
                    $task->video_url = FileService::downloadFileBySource($videoUrl, 'video');

                    //生成缩略图
                    $videos          = [
                        'video_url' => FileService::getFileUrl($task->video_url),
                        'time'      => 1.0,
                        'options'   => [
                            'quality' => 2
                        ]
                    ];
                    $thumbnailResult = (new VideoInfoService())->commonVideoThumbnail($videos);
                    if ($thumbnailResult['result']) {
                        $task->thumbnail = FileService::setFileUrl($thumbnailResult['url']);
                    }
                    if ((float)$duration > 0) {
                        $actualDuration = $duration; // 不取整，允许扣除小数算力
                        $preDeductDuration = $task->duration;

                        // 如果实际时长和预估时长不一致，进行补扣或退费
                        if ($actualDuration != $preDeductDuration) {
                            $tokenScene = $task->is_material == 0 ? 'human_video_shanjian' : 'shanjian_broadcast_mixcut';
                            $tokenCode = $task->is_material == 0 ? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN : AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN;
                            $unit = TokenLogService::getTypeScore($tokenScene); // 获取单价

                            if ($actualDuration > $preDeductDuration) {
                                // 补扣 (实际时长大于预估)
                                $diffDuration = (float)bcsub((string)$actualDuration, (string)$preDeductDuration, 2);
                                $diffPoints = (float)bcmul((string)$unit, (string)$diffDuration, 2);
                                if ($diffPoints > 0) {
                                    User::userTokensChange($userId, $diffPoints); // 扣减所需积分
                                    $extraAdd = [
                                        '扣费项目' => '爆款视频复刻-超出预估补扣',
                                        '算力单价' => $unit,
                                        '预估时长' => $preDeductDuration . '秒',
                                        '实际时长' => $actualDuration . '秒',
                                        '补扣时长' => $diffDuration . '秒',
                                        '实际消耗算力' => $diffPoints
                                    ];
                                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $diffPoints, (string)$taskId, $extraAdd);
                                }
                            } else {
                                // 退费 (实际时长小于预估)
                                $diffDuration = (float)bcsub((string)$preDeductDuration, (string)$actualDuration, 2);
                                $diffPoints = (float)bcmul((string)$unit, (string)$diffDuration, 2);
                                if ($diffPoints > 0) {
                                    // recordUserTokensLog 传false将自动调用 User::userTokensChange($userId, $diffPoints, 'inc') 进行退费
                                    $extraRefund = [
                                        '扣费项目' => '爆款视频复刻-结余预估退费',
                                        '算力单价' => $unit,
                                        '预估时长' => $preDeductDuration . '秒',
                                        '实际时长' => $actualDuration . '秒',
                                        '退费时长' => $diffDuration . '秒',
                                        '实际退费算力' => $diffPoints
                                    ];
                                    AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $diffPoints, (string)$taskId, $extraRefund);
                                }
                            }
                        }
                        $task->duration = $actualDuration;
                    }
                    $task->save();
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    self::setError('更新回调状态异常');
                    return false;
                }
            } elseif ($statusStr === 'failed' || $statusStr == 4) {
                // 生成失败
                Db::startTrans();
                try {
                    $task->status = 4; // 失败
                    $task->remarks = mb_substr($errorMessage ?: '第三方生成失败', 0, 490, 'UTF-8');
                    $task->save();

                    // 执行失败退款操作
                    $pointsAmount = UserTokensLog::where('user_id', $userId)->where('change_type', $tokenCode)->where('task_id', $taskId)->where('action', 2)->value('change_amount') ?? 0;
                    $refundUnit = abs($pointsAmount);
                    if ($refundUnit > 0) {
                        AccountLogLogic::recordUserTokensLog(false, $userId, $tokenCode, $refundUnit, (string) $taskId, ['扣费项目' => '视频仿写失败算力退回']);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    self::setError('更新回调失败状态异常');
                    return false;
                }
            }
            return true;
        } finally {
            // 无论如何，执行完毕后释放锁
            try {
                $redis->del($lockKey);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * 定时生成任务 (Cron服务调用)
     * 支持超时（例如30分钟）未由用户主动确认文案的任务 (status=1) 自动拉起下发生成逻辑
     */
    public static function autoGenerateTasksCron()
    {
        $timeoutDuration = 1800; // 30 mins
        $deadlineTime = time() - $timeoutDuration;

        try {
            $pendingTasks = VideoImitationTask::where('status', 1)
                ->where('create_time', '<', $deadlineTime)
                ->limit(50) // 每次定时脚本处理上限防堆积
                ->select();

            foreach ($pendingTasks as $task) {
                // 如果用户在中途丢失了 persona_id 或原复刻文案丢失，则自动失败
                if (empty($task->persona_id) || empty($task->rewritten_text)) {
                    $task->status = 4;
                    $task->remarks = '自动下发：缺少必要的AI人设ID或复刻文案';
                    $task->save();
                    continue;
                }

                // 直接内部调用已有的下发逻辑
                $result = self::generate($task->id, $task->user_id, $task->rewritten_text);

                Log::channel('shanjian')->write("定时触发视频仿写生成任务：" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            Log::channel('shanjian')->write("定时触发视频仿写生成任务异常：" . $e->getMessage());
        }
    }

    /**
     * 随机抽取指定人设的素材集合
     * - 视频素材抽取 1~2 个（不足或重复时，单视频最多用2次）
     * - 图片素材抽取 2~3 个（不足或重复时，单图片最多用3次）
     */
    private static function getRandomMaterials($personaId, $userId)
    {
        $allMaterials = Material::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->where('use_status', 1)
            ->select()->toArray();

        $videos = [];
        $images = [];
        foreach ($allMaterials as $m) {
            if (!empty($m['file_url'])) {
                if ($m['material_type'] == 1) {
                    $videos[] = FileService::getFileUrl($m['file_url']);
                } else {
                    $images[] = FileService::getFileUrl($m['file_url']);
                }
            }
        }

        $result = [];

        // 抽取视频：1~2 个
        if (!empty($videos)) {
            $videoCount = rand(1, 2);
            $pool = [];
            // 每个视频放入2次（确保最多被抽中2次）
            foreach ($videos as $v) {
                $pool[] = $v;
                $pool[] = $v;
            }
            shuffle($pool);
            $selected = array_slice($pool, 0, min($videoCount, count($pool)));
            foreach ($selected as $v) {
                $result[] = ['fileUrl' => $v, 'type' => 'video'];
            }
        }

        // 抽取图片：2~3 个
        if (!empty($images)) {
            $imageCount = rand(2, 3);
            $pool = [];
            // 每个图片放入3次（确保最多被抽中3次）
            foreach ($images as $img) {
                $pool[] = $img;
                $pool[] = $img;
                $pool[] = $img;
            }
            shuffle($pool);
            $selected = array_slice($pool, 0, min($imageCount, count($pool)));
            foreach ($selected as $img) {
                $result[] = ['fileUrl' => $img, 'type' => 'image'];
            }
        }

        return $result;
    }
}
