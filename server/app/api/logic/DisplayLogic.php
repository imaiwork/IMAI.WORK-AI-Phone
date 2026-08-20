<?php


namespace app\api\logic;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvPrivateMessage;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\model\sv\SvCityExposureRecord;
use app\common\model\sv\SvCityTouchRecord;
use app\common\model\sv\SvGroupBuyRecord;
use app\common\model\wechat\AiWechatCreateGroupLog;
use app\common\model\wechat\AiWechatLog;
use app\common\model\sv\SvDeviceTakeOverRecord;
use app\common\service\display\IntentionCustomerService;
use app\common\service\FileService;
use app\common\model\aiPersona\Material as AiPersonaMaterial;

/**
 * 设备任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\device
 */
class DisplayLogic extends ApiLogic
{
    public static function display(array $params)
    {
        try {
            $date = $params['date'] ?? date('Y-m-d');
            $st = strtotime(date('Y-m-d 00:00:00', strtotime($date)));
            $et = strtotime(date('Y-m-d 23:59:59', strtotime($date)));

            $today_task_count = SvDeviceTask::where('user_id', self::$uid)->where('day', $date)->count(); //今日任务数
            $today_complete_task_count = SvDeviceTask::where('user_id', self::$uid)->where('day', $date)->where('status', 2)->count(); //今日完成任务数
            $today_rate = $today_task_count ? round(($today_complete_task_count / $today_task_count) * 100, 2) : 0; //今日完成率
            $worker_device_count = SvDevice::where('user_id', self::$uid)->where('status', 2)->count(); //统计设备数
            $today_touch_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 0)
                ->where('r.create_time', 'between', [$st, $et])->count(); //今日触达人数
            $touch_expose_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 1)
                ->where('r.create_time', 'between', [$st, $et])->count();
            $msg_expose_number = SvPrivateMessage::where('user_id', self::$uid)->where('create_time', 'between', [$st, $et])->where('reply_time', '<>', 'null')->count(); //今日曝光人数
            $today_expose_number = $touch_expose_number + $msg_expose_number;  //今日曝光人数

            $st7 = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
            $seven_touch_expose_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 1)
                ->where('r.create_time', 'between', [$st7, $et])->count();
            $seven_msg_expose_number = SvPrivateMessage::where('user_id', self::$uid)->where('create_time', 'between', [$st7, $et])->where('reply_time', '<>', 'null')->count(); //7天曝光人数  
            $seven_expose_number = $seven_touch_expose_number + $seven_msg_expose_number;  //7天曝光人数

            $seven_touch_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 0)
                ->where('r.create_time', 'between', [$st7, $et])->count(); //7天触达人数
            $seven_intention_number = SvAddWechatRecord::where('intention_type', 'in', [1, 2, 3, 4])
                ->where('user_id', self::$uid)
                ->where('create_time', 'between', [$st7, $et])->count(); //7天意向人数


            $touch_number = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)->count();; //截流触达人数
            $msg_number = SvPrivateMessage::where('user_id', self::$uid)->where('reply_time', '<>', 'null')->count(); //截流消息人数
            $sph_number = SvCrawlingRecord::where('user_id', self::$uid)->count(); //截流接待人数
            $total_touch_number = $touch_number + $sph_number + $msg_number; //总触达人数

            $total_expose_number = SvAddWechatRecord::where('channel', 'in', [3, 4, 5])->where('user_id', self::$uid)->count(); //总曝光人数

            self::$returnData = array(
                'today_task_count' => $today_task_count, //今日任务数
                'today_complete_task_count' => $today_complete_task_count, //今日完成任务数
                'today_rate' => $today_rate, //今日完成率
                'worker_device_count' => $worker_device_count, //统计设备数
                'today_touch_number' => $today_touch_number, //今日触达人数
                'today_expose_number' => $today_expose_number, //今日曝光人数

                'seven_expose_number' => $seven_expose_number, //7天曝光人数
                'seven_touch_number' => $seven_touch_number, //7天触达人数
                'seven_intention_number' => $seven_intention_number, //7天意向人数

                'ai_clues' => [
                    'touch_number' => $total_touch_number, //触达任务
                    'expose_number' => $total_expose_number, //意向
                    'rate' => $total_touch_number > 0 ? round(($total_expose_number / $total_touch_number) * 100, 2) : 0, //意向完成率
                ],
                'content_operation' => [
                    'count' => SvPublishSettingDetail::where('user_id', self::$uid)->where('auto_type', 1)->count(), //总生成发布数
                    'publish_count' => SvPublishSettingDetail::where('user_id', self::$uid)->where('auto_type', 1)->where('status', 1)->count(), //发布成功数
                ],
                'ai_customer' => [
                    'receive' => SvPrivateMessage::where('user_id', self::$uid)->where('reply_time', '<>', 'null')->count(), //接待
                    'drainage' => 0, //引流
                ],
                'ai_sales' => [
                    'add_friends' => SvAddWechatRecord::where('user_id', self::$uid)->where('status', 'in', [1, 2])->count(), //添加好友
                    'group' => 0, //群聊
                ]
            );
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function statistics(array $params): bool
    {
        try {
            $date = $params['date'] ?? date('Y-m-d');
            $timestamp = strtotime((string)$date);
            if (false === $timestamp) {
                $timestamp = time();
            }
            $date = date('Y-m-d', $timestamp);
            $startTimeText = date('Y-m-d 00:00:00', $timestamp);
            $endTimeText = date('Y-m-d 23:59:59', $timestamp);
            $st = strtotime($startTimeText);
            $et = strtotime($endTimeText);

            $todayTaskCount = SvDeviceTask::where('user_id', self::$uid)
                ->where('day', $date)
                ->count();
            $todayCompleteTaskCount = SvDeviceTask::where('user_id', self::$uid)
                ->where('day', $date)
                ->where('status', 2)
                ->count();
            $todayRate = $todayTaskCount > 0 ? round(($todayCompleteTaskCount / $todayTaskCount) * 100, 2) : 0;

            $touchExposeNumber = SvLeadScrapingRecord::alias('r')
                ->field('r.id')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id')
                ->where('r.user_id', self::$uid)
                ->where('s.industry_type', 1)
                ->where('r.create_time', 'between', [$st, $et])
                ->count();
            $msgExposeNumber = SvPrivateMessage::where('user_id', self::$uid)
                ->where('create_time', 'between', [$st, $et])
                ->where('reply_time', '<>', 'null')
                ->count();
            $todayExposeNumber = $touchExposeNumber + $msgExposeNumber;

            $todayClueNumber = SvAddWechatRecord::where('user_id', self::$uid)
                ->where('create_time', 'between', [$st, $et])
                ->count();
            $customerAssetCount = SvAddWechatRecord::where('user_id', self::$uid)
                ->where('create_time', 'between', [$st, $et])
                ->where('status', 'in', [1, 2])
                ->count();

            $materialCount = AiPersonaMaterial::where('user_id', self::$uid)
                ->where('create_time', 'between', [$st, $et])
                ->where('grab_type', 1)
                ->count();
            $viralCount = SvDeviceViralRecord::where('user_id', self::$uid)
                ->where(function ($query) use ($date, $st, $et) {
                    $query->where('day', $date)->whereOr('create_time', 'between', [$st, $et]);
                })
                ->count();
            $viralHitCount = SvDeviceViralRecord::where('user_id', self::$uid)
                ->where(function ($query) use ($date, $st, $et) {
                    $query->where('day', $date)->whereOr('create_time', 'between', [$st, $et]);
                })
                ->where(function ($query) {
                    $query->where('copywriting_type', 1)->whereOr('status', 4);
                })
                ->count();

            $videoPublishCount = SvDeviceTask::where('user_id', self::$uid)
                ->where('day', $date)
                ->where('task_scene', 'in', [5, 7])
                ->count();
            $videoPublishSuccessCount = SvDeviceTask::where('user_id', self::$uid)
                ->where('day', $date)
                ->where('task_scene', 'in', [5, 7])
                ->where('status', 2)
                ->count();

            $platformPrivateReplyCount = SvPrivateMessage::where('user_id', self::$uid)
                ->where('message_task_type', 2)
                ->where('type', '<>', 1)
                ->where('reply_time', 'between', [$startTimeText, $endTimeText])
                ->count();
            $platformCommentReplyCount = SvPrivateMessage::where('user_id', self::$uid)
                ->where('message_task_type', 1)
                ->where('type', '<>', 1)
                ->where('reply_time', 'between', [$startTimeText, $endTimeText])
                ->count();
            $wechatReplyCount = SvPrivateMessage::where('user_id', self::$uid)
                ->where('message_task_type', 2)
                ->where('type', 1)
                ->where('reply_time', 'between', [$startTimeText, $endTimeText])
                ->count();
            $wechatReplyUserIds = SvPrivateMessage::where('user_id', self::$uid)
                ->where('message_task_type', 2)
                ->where('type', 1)
                ->where('reply_time', 'between', [$startTimeText, $endTimeText])
                ->group('account')
                ->column('account');
            $createGroupCount = AiWechatCreateGroupLog::where('user_id', self::$uid)
                ->where('status', 1)
                ->where('create_time', 'between', [$st, $et])
                ->count();

            $circleCommentCount = SvDeviceCircleLikeReplyRecord::where('user_id', self::$uid)
                ->where('type', 'in', [2, 3])
                ->where('create_time', 'between', [$st, $et])
                ->count();
            $circleLikeCount =  SvDeviceCircleLikeReplyRecord::where('user_id', self::$uid)
                ->where('type', 'in', [1, 3])
                ->where('create_time', 'between', [$st, $et])
                ->count();

            self::$returnData = [
                'date' => $date, // 统计日期，格式 Y-m-d
                'date_text' => date('m月d日', $timestamp), // 页面展示日期
                'top' => [ // 顶部核心指标
                    'today_expose_number' => $todayExposeNumber, // 今日曝光人数
                    'today_clue_number' => $todayClueNumber, // 今日获客线索数
                    'today_rate' => $todayRate, // 今日任务完成率
                ],
                'core_tasks' => [ // 核心任务执行统计
                    'material_count' => $materialCount, // 自动找素材今日新增数
                    'viral_count' => $viralCount, // 自动找爆款今日处理数
                    'viral_hit_count' => $viralHitCount, // 自动找爆款今日命中数
                    'video_publish_count' => $videoPublishCount, // 今日视频发布次数
                    'video_publish_success_count' => $videoPublishSuccessCount, // 今日视频发布成功次数
                    'video_publish_all_success' => $videoPublishCount === $videoPublishSuccessCount, // 今日视频发布是否全部成功
                ],
                'peer_acquisition' => [ // 同行获客统计
                    'clue_count' => $todayClueNumber, // 今日找到线索数
                    'customer_asset_count' => $customerAssetCount, // 今日获取客资数
                    'has_output' => ($todayClueNumber + $customerAssetCount) > 0, // 今日是否有产出
                ],
                'guard' => [ // 私信与社群值守统计
                    'platform_private_reply_count' => $platformPrivateReplyCount, // 平台私信回复条数
                    'platform_private_ai_count' => $platformPrivateReplyCount, // 平台私信 AI 处理条数
                    'platform_comment_reply_count' => $platformCommentReplyCount, // 平台评论回复条数
                    'platform_comment_ai_count' => $platformCommentReplyCount, // 平台评论 AI 处理条数
                    'wechat_reply_count' => $wechatReplyCount, // 微信聊天回复条数
                    'wechat_reply_user_count' => count($wechatReplyUserIds), // 微信聊天涉及用户数
                    'create_group_count' => $createGroupCount, // 自动拉群/建群数量
                ],
                'circle_maintenance' => [ // 朋友圈日常维护统计
                    'comment_count' => $circleCommentCount, // 朋友圈评论数量
                    'like_count' => $circleLikeCount, // 朋友圈点赞数量
                ],
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function autoPipeline(array $params): bool
    {
        try {
            if (empty($params['persona_id'])) {
                throw new \Exception('人设ID不能为空');
            }
            $date = self::normalizePipelineDate($params['date'] ?? '');
            $tasks = self::autoPipelineTasks($params, $date);
            $groups = [];

            foreach (self::autoPipelineDefinitions() as $group) {
                $items = [];
                foreach ($group['items'] as $item) {
                    $items[] = self::buildAutoPipelineItem($item, $tasks, $date);
                }

                $groups[] = [
                    'key' => $group['key'],
                    'title' => $group['title'],
                    'subtitle' => '包含 ' . count($items) . ' 个时段任务',
                    'items' => $group['key'] === 'auto_publish' ? [] : $items,
                ];
            }

            self::$returnData = [
                'date' => $date,
                'now_time' => date('H:i'),
                'groups' => $groups,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function autoPipelineTasks(array $params, string $date): array
    {
        $query = SvDeviceTask::field('id,task_scene,account_type,start_time,end_time,status,device_code,persona_id')
            ->where('user_id', self::$uid)
            ->where('day', $date)
            ->where('auto_type', 1)
            ->whereNull('delete_time');

        if (!empty($params['persona_id'])) {
            $query->where('persona_id', (int)$params['persona_id']);
        }

        if (!empty($params['device_code'])) {
            $query->where('device_code', trim((string)$params['device_code']));
        }

        return $query->order('start_time', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    private static function normalizePipelineDate(mixed $date): string
    {
        $timestamp = strtotime((string)$date);
        if (false === $timestamp) {
            $timestamp = time();
        }

        return date('Y-m-d', $timestamp);
    }

    private static function autoPipelineDefinitions(): array
    {
        return [
            [
                'key' => 'content',
                'title' => '1. 帮我找爆款做内容',
                'items' => [
                    [
                        'key' => 'viral_topic',
                        'name' => '帮我找今天的爆款选题',
                        'tag' => '爆款分析',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_VIRAL_REWRITER],
                        'fixed_slots' => [['start_time' => '00:00', 'end_time' => '03:00']],
                    ],
                    [
                        'key' => 'ai_content',
                        'name' => 'AI帮我做内容',
                        'tag' => 'AI创作',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH],
                        'fixed_slots' => [['start_time' => '03:00', 'end_time' => '05:00']],
                    ],
                ],
            ],
            [
                'key' => 'auto_publish',
                'title' => '2. 全网自动发布',
                'items' => [
                    [
                        'key' => 'auto_publish',
                        'name' => '自动发布',
                        'tag' => '自动发布',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH, DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH],
                        'account_types' => [
                            DeviceEnum::ACCOUNT_TYPE_SPH,
                            DeviceEnum::ACCOUNT_TYPE_XHS,
                            DeviceEnum::ACCOUNT_TYPE_DY,
                            DeviceEnum::ACCOUNT_TYPE_KS,
                        ],
                    ],
                ],
            ],
            [
                'key' => 'peer_customer',
                'title' => '3. 同行找客户',
                'items' => [
                    [
                        'key' => 'peer_all_network',
                        'name' => '找全网同行的客户',
                        'tag' => '跨平台截流',
                        'scenes' => [
                            DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT,
                            DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG,
                            DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE,
                        ],
                        'account_types' => [
                            DeviceEnum::ACCOUNT_TYPE_XHS,
                            DeviceEnum::ACCOUNT_TYPE_DY,
                            DeviceEnum::ACCOUNT_TYPE_KS,
                        ],
                    ],
                    [
                        'key' => 'nearby_customer',
                        'name' => '找附近的客户',
                        'tag' => '同城曝光/电子传单',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF],
                        'account_types' => [DeviceEnum::ACCOUNT_TYPE_DY],
                    ],
                    [
                        'key' => 'peer_store_nearby',
                        'name' => '去同行门店附近找客户',
                        'tag' => '团购截流',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY],
                        'account_types' => [DeviceEnum::ACCOUNT_TYPE_DY],
                    ],
                    [
                        'key' => 'business_invite',
                        'name' => 'B端招商获客',
                        'tag' => '视频号',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE],
                        'account_types' => [DeviceEnum::ACCOUNT_TYPE_SPH],
                    ],
                ],
            ],
            [
                'key' => 'chat_wechat',
                'title' => '4. 聊客户加微信',
                'items' => [
                    [
                        'key' => 'social_private_reply',
                        'name' => '帮我回复社媒私信',
                        'tag' => '私信',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER],
                        'account_types' => [
                            DeviceEnum::ACCOUNT_TYPE_XHS,
                            DeviceEnum::ACCOUNT_TYPE_DY,
                            DeviceEnum::ACCOUNT_TYPE_KS,
                        ],
                        'all_day' => true,
                    ],
                    [
                        'key' => 'social_comment_reply',
                        'name' => '帮我回复社媒评论',
                        'tag' => '评论',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER],
                        'account_types' => [
                            DeviceEnum::ACCOUNT_TYPE_XHS,
                            DeviceEnum::ACCOUNT_TYPE_DY,
                            DeviceEnum::ACCOUNT_TYPE_KS,
                        ],
                        'all_day' => true,
                    ],
                    [
                        'key' => 'wechat_customer',
                        'name' => '帮我管理微信客户',
                        'tag' => '私域运营',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_FRIENDS],
                        'account_types' => [DeviceEnum::ACCOUNT_TYPE_SPH],
                        'all_day' => true,
                    ],
                    [
                        'key' => 'wechat_circle',
                        'name' => '帮我发朋友圈',
                        'tag' => '朋友圈',
                        'scenes' => [DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT],
                        'account_types' => [DeviceEnum::ACCOUNT_TYPE_SPH],
                    ],
                ],
            ],
        ];
    }

    private static function buildAutoPipelineItem(array $definition, array $tasks, string $date): array
    {
        $matchedTasks = self::filterAutoPipelineTasks($tasks, $definition);
        $isAllDay = !empty($definition['all_day']);
        $timeSlots = $isAllDay
            ? []
            : (!empty($definition['fixed_slots']) ? $definition['fixed_slots'] : self::pipelineSlotsFromTasks($matchedTasks));

        $status = $isAllDay ? DeviceEnum::TASK_STATUS_RUNNING : self::pipelineStatusBySlots($timeSlots, $date);

        return [
            'key' => $definition['key'],
            'name' => $definition['name'],
            'tag' => $definition['tag'],
            'time_text' => $isAllDay ? ['全天运行' => self::pipelineStatusText(DeviceEnum::TASK_STATUS_RUNNING)] : self::pipelineTimeText($timeSlots, $date),
            'time_slots' => $timeSlots,
            'status' => $status,
            'status_text' => self::pipelineStatusText($status),
        ];
    }

    private static function filterAutoPipelineTasks(array $tasks, array $definition): array
    {
        return array_values(array_filter($tasks, static function (array $task) use ($definition) {
            $scene = (int)($task['task_scene'] ?? 0);
            if (!empty($definition['scenes']) && !in_array($scene, $definition['scenes'], true)) {
                return false;
            }

            $accountType = (int)($task['account_type'] ?? 0);
            if (!empty($definition['account_types']) && !in_array($accountType, $definition['account_types'], true)) {
                return false;
            }

            return true;
        }));
    }

    private static function pipelineSlotsFromTasks(array $tasks): array
    {
        $slots = [];
        foreach ($tasks as $task) {
            $startTime = self::pipelineFormatTime($task['start_time'] ?? 0);
            $endTime = self::pipelineFormatTime($task['end_time'] ?? 0);
            if ($startTime === '' || $endTime === '') {
                continue;
            }

            $key = $startTime . '-' . $endTime;
            $slots[$key] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        uasort($slots, static function (array $a, array $b) {
            return strcmp($a['start_time'], $b['start_time']) ?: strcmp($a['end_time'], $b['end_time']);
        });

        return array_values($slots);
    }

    private static function pipelineFormatTime(mixed $timestamp): string
    {
        $timestamp = (int)$timestamp;
        return $timestamp > 0 ? date('H:i', $timestamp) : '';
    }

    private static function pipelineTimeText(array $slots, string $date, ?int $now = null): array
    {
        if (empty($slots)) {
            return [];
        }

        $now = $now ?? time();
        $result = [];
        foreach ($slots as $slot) {
            $startText = (string)($slot['start_time'] ?? '');
            $endText = (string)($slot['end_time'] ?? '');
            $timeText = $startText . '-' . $endText;
            $start = self::pipelineSlotTimestamp($date, $startText);
            $end = self::pipelineSlotTimestamp($date, $endText);

            if ($start <= 0 || $end <= 0 || $startText === '' || $endText === '') {
                continue;
            }
            if ($end <= $start) {
                $end += 86400;
            }
            $end += 59;

            if ($now >= $start && $now <= $end) {
                $status = DeviceEnum::TASK_STATUS_RUNNING;
            } elseif ($now > $end) {
                $status = DeviceEnum::TASK_STATUS_FINISHED;
            } else {
                $status = DeviceEnum::TASK_STATUS_WAIT;
            }

            $result[$timeText] = self::pipelineStatusText($status);
        }

        return $result;
    }

    private static function pipelineStatusBySlots(array $slots, string $date, ?int $now = null): int
    {
        if (empty($slots)) {
            return DeviceEnum::TASK_STATUS_WAIT;
        }

        $now = $now ?? time();
        $validSlots = [];
        foreach ($slots as $slot) {
            $start = self::pipelineSlotTimestamp($date, (string)($slot['start_time'] ?? ''));
            $end = self::pipelineSlotTimestamp($date, (string)($slot['end_time'] ?? ''));
            if ($start <= 0 || $end <= 0) {
                continue;
            }
            if ($end <= $start) {
                $end += 86400;
            }
            $end += 59;
            $validSlots[] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        if (empty($validSlots)) {
            return DeviceEnum::TASK_STATUS_WAIT;
        }

        usort($validSlots, static function (array $a, array $b) {
            return $a['start'] <=> $b['start'] ?: $a['end'] <=> $b['end'];
        });

        foreach ($validSlots as $slot) {
            $start = (int)$slot['start'];
            $end = (int)$slot['end'];
            if ($now >= $start && $now <= $end) {
                return DeviceEnum::TASK_STATUS_RUNNING;
            }
        }

        $lastSlot = end($validSlots);
        if ($lastSlot && $now > (int)$lastSlot['end']) {
            return DeviceEnum::TASK_STATUS_FINISHED;
        }

        return DeviceEnum::TASK_STATUS_WAIT;
    }

    private static function pipelineSlotTimestamp(string $date, string $time): int
    {
        $timestamp = strtotime($date . ' ' . $time . ':00');
        return false === $timestamp ? 0 : $timestamp;
    }

    private static function pipelineStatusText(int $status): string
    {
        $maps = [
            DeviceEnum::TASK_STATUS_WAIT => '待执行',
            DeviceEnum::TASK_STATUS_RUNNING => '执行中',
            DeviceEnum::TASK_STATUS_FINISHED => '已完成',
        ];

        return $maps[$status] ?? '';
    }

    public static function intentionStatistics(array $params): bool
    {
        try {
            $platform = IntentionCustomerService::parsePlatform($params['platform'] ?? 'all');
            $customers = IntentionCustomerService::customers((int)self::$uid, $platform);
            $customerCounts = IntentionCustomerService::customerCounts($customers);

            $clueCount = SvCrawlingRecord::where('user_id', self::$uid)
                ->where('reg_content', '<>', '')
                ->where('hash', '<>', '')
                ->when($platform !== null, function ($query) use ($platform) {
                    if ($platform === DeviceEnum::ACCOUNT_TYPE_SPH) {
                        return;
                    }
                    $query->where('id', 0);
                })
                ->group('task_id,reg_content')
                ->count();
            $addFriendCount = SvAddWechatRecord::where('user_id', self::$uid)
                ->where('status', 'in', [1, 2])
                ->when($platform !== null, function ($query) use ($platform) {
                    $query->where(function ($query) use ($platform) {
                        $query->where('account_type', $platform)->whereOr('channel', $platform);
                    });
                })
                ->group('account')
                ->count();
            $createGroupCount = AiWechatCreateGroupLog::where('user_id', self::$uid)
                ->where('status', 1)
                ->group('group_name')
                ->count();
            $sourceStats = self::formatIntentionSourceStats(IntentionCustomerService::summarySourceStats((int)self::$uid, $platform));

            self::$returnData = [
                'summary_text' => '累计触达意向客户',
                'total_touch_customer_count' => $customerCounts['total'],
                'clue_count' => $clueCount,
                'add_friend_count' => $addFriendCount,
                'create_group_count' => $createGroupCount,
                'douyin_nearby' => $sourceStats['douyin_nearby'],
                'xhs_peer' => $sourceStats['xhs_peer'],
                'group_buy' => $sourceStats['group_buy'],
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function formatIntentionSourceStats(array $stats): array
    {
        $defaults = [
            'douyin_nearby' => [
                'key' => 'douyin_nearby',
                'name' => '抖音附近的客户',
                'count' => 0,
                'unit' => '人',
            ],
            'xhs_peer' => [
                'key' => 'xhs_peer',
                'name' => '小红书同行的客户',
                'count' => 0,
                'unit' => '人',
            ],
            'group_buy' => [
                'key' => 'group_buy',
                'name' => '团购的客户',
                'count' => 0,
                'unit' => '人',
            ],
        ];

        foreach ($stats as $item) {
            $key = (string)($item['key'] ?? '');
            if (isset($defaults[$key])) {
                $defaults[$key] = [
                    'key' => $key,
                    'name' => (string)($item['name'] ?? $defaults[$key]['name']),
                    'count' => (int)($item['count'] ?? 0),
                    'unit' => (string)($item['unit'] ?? $defaults[$key]['unit']),
                ];
            }
        }

        return $defaults;
    }

    public static function privateMessageRecord(array $params): bool
    {
        try {
            $id = trim((string)($params['id'] ?? ''));
            if ($id === '') {
                self::setError('缺少参数id');
                return false;
            }

            $pageNo = max(1, (int)($params['page_no'] ?? 1));
            $pageSize = (int)($params['page_size'] ?? 50);
            $pageSize = $pageSize > 0 ? min($pageSize, 200) : 50;
            $pageType = (int)($params['page_type'] ?? 1);
            $platform = IntentionCustomerService::parsePlatform($params['platform'] ?? '');
            $account = trim((string)($params['account'] ?? ''));
            $customerName = trim((string)($params['customer_name'] ?? ''));

            $records = [];
            if (str_starts_with($id, 'private_message:')) {
                $recordId = (int)substr($id, strlen('private_message:'));
                if ($recordId <= 0) {
                    self::setError('私信记录不存在');
                    return false;
                }
                $record = self::findPrivateMessageRecord($recordId);
                if (!empty($record)) {
                    if ($platform !== null && $platform > 0 && $customerName !== '') {
                        $record = self::findLargestPrivateMessageConversationRecord($record, $platform, $customerName);
                    }
                    $records = self::getPrivateMessageConversationByRecord($record);
                }
            } else {
                if ($platform === null || $platform <= 0) {
                    self::setError('缺少参数platform');
                    return false;
                }
                if ($account === '') {
                    self::setError('缺少参数account');
                    return false;
                }
                $records = self::getPrivateMessageConversationByCustomer($platform, $account, $customerName);
            }

            $records = array_values($records);
            $replyAccountMap = self::getPrivateMessageReplyAccountMap($records);
            $messages = self::formatPrivateMessageRows($records, false, $replyAccountMap);
            $screenshots = self::extractPrivateMessageScreenshots($messages);
            $count = count($messages);
            $pageMessages = $pageType === 1
                ? array_slice($messages, ($pageNo - 1) * $pageSize, $pageSize)
                : $messages;

            self::$returnData = [
                'customer' => self::formatPrivateMessageCustomer($records, [
                    'platform' => $platform,
                    'account' => $account,
                    'customer_name' => $customerName,
                ], $count),
                'messages' => $pageMessages,
                'screenshots' => $screenshots,
                'count' => $count,
                'page_no' => $pageNo,
                'page_size' => $pageType === 1 ? $pageSize : $count,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function circleInteractionDetail(array $params): bool
    {
        try {
            $id = trim((string)($params['id'] ?? ''));
            if ($id === '') {
                self::setError('缺少参数id');
                return false;
            }

            $platform = IntentionCustomerService::parsePlatform($params['platform'] ?? 'all');
            $customer = self::findFollowCustomer($id, $platform);
            if (empty($customer) && $platform !== null) {
                $customer = self::findFollowCustomer($id, null);
            }
            if (empty($customer)) {
                $customer = self::fallbackCircleInteractionCustomer($id, $platform, $params);
            }

            $nicknames = self::circleInteractionNicknames($customer, $params);
            $pageNo = max(1, (int)($params['page_no'] ?? 1));
            $pageSize = (int)($params['page_size'] ?? 20);
            $pageSize = $pageSize > 0 ? min($pageSize, 200) : 20;

            $rows = [];
            if (!empty($nicknames)) {
                $rows = self::circleInteractionRecordQuery($nicknames)
                    ->field('id,user_id,like_reply_account,device_code,account,nickname,content,comment,task_id,type,hash,image,create_time,update_time')
                    ->order('create_time desc,id desc')
                    ->select()
                    ->toArray();
            }

            $executeAccounts = self::circleInteractionExecuteAccounts($rows);
            $realLists = array_map(function (array $row) use ($executeAccounts) {
                return self::formatCircleInteractionRecord($row, $executeAccounts[(int)($row['like_reply_account'] ?? 0)] ?? []);
            }, $rows);
            $sourceLists = self::circleInteractionSourceRecords($id, $platform, $customer, $params);
            $lists = self::sortCircleInteractionLists(array_merge($realLists, $sourceLists));
            $summary = self::circleInteractionSummaryFromLists($lists);
            $pageLists = array_slice($lists, ($pageNo - 1) * $pageSize, $pageSize);

            self::$returnData = [
                'customer' => self::formatCircleInteractionCustomer($customer),
                'summary' => $summary,
                'lists' => $pageLists,
                'count' => $summary['interaction_count'],
                'page_no' => $pageNo,
                'page_size' => $pageSize,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function fallbackCircleInteractionCustomer(string $id, ?int $platform, array $params): array
    {
        $customerName = trim((string)($params['customer_name'] ?? ''))
            ?: trim((string)($params['account_name'] ?? ''))
            ?: trim((string)($params['account'] ?? ''));

        return [
            'id' => $id,
            'customer_name' => $customerName,
            'account_name' => trim((string)($params['account_name'] ?? '')) ?: $customerName,
            'avatar' => trim((string)($params['avatar'] ?? '')),
            'platform' => $platform ?? 0,
            'platform_desc' => $platform !== null && $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '',
            'account' => trim((string)($params['account'] ?? '')),
        ];
    }

    private static function formatCircleInteractionCustomer(array $customer): array
    {
        $platform = (int)($customer['platform'] ?? 0);
        $customerName = trim((string)($customer['customer_name'] ?? ''));
        $account = trim((string)($customer['account'] ?? ''));

        return [
            'id' => (string)($customer['id'] ?? ''),
            'customer_name' => $customerName !== '' ? $customerName : $account,
            'account_name' => (string)($customer['account_name'] ?? ($customerName !== '' ? $customerName : $account)),
            'avatar' => self::completePrivateMessageFileUrl((string)($customer['avatar'] ?? '')),
            'platform' => $platform,
            'platform_desc' => (string)($customer['platform_desc'] ?? ($platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '')),
            'account' => $account,
        ];
    }

    private static function circleInteractionNicknames(array $customer, array $params): array
    {
        $values = [
            $customer['customer_name'] ?? '',
            $customer['account_name'] ?? '',
            $params['customer_name'] ?? '',
            $params['account_name'] ?? '',
        ];
        $nicknames = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '' || strtolower($value) === 'null' || $value === '未知客户') {
                continue;
            }
            $nicknames[] = $value;
        }

        return array_values(array_unique($nicknames));
    }

    private static function circleInteractionRecordQuery(array $nicknames)
    {
        return SvDeviceCircleLikeReplyRecord::where('user_id', self::$uid)
            ->whereNull('delete_time')
            ->where('nickname', 'in', $nicknames);
    }

    private static function circleInteractionSummary(array $nicknames): array
    {
        if (empty($nicknames)) {
            return [
                'interaction_count' => 0,
                'like_count' => 0,
                'comment_count' => 0,
            ];
        }

        return [
            'interaction_count' => (int)self::circleInteractionRecordQuery($nicknames)->count(),
            'like_count' => (int)self::circleInteractionRecordQuery($nicknames)->where('type', 'in', [1, 3])->count(),
            'comment_count' => (int)self::circleInteractionRecordQuery($nicknames)->where('type', 'in', [2, 3])->count(),
        ];
    }

    private static function circleInteractionSummaryFromLists(array $lists): array
    {
        $summary = [
            'interaction_count' => 0,
            'like_count' => 0,
            'comment_count' => 0,
        ];

        foreach ($lists as $item) {
            $summary['interaction_count']++;
            if (!empty($item['is_liked'])) {
                $summary['like_count']++;
            }
            if (!empty($item['is_commented'])) {
                $summary['comment_count']++;
            }
        }

        return $summary;
    }

    private static function sortCircleInteractionLists(array $lists): array
    {
        usort($lists, static function (array $a, array $b) {
            $aTime = strtotime((string)($a['create_time'] ?? '')) ?: 0;
            $bTime = strtotime((string)($b['create_time'] ?? '')) ?: 0;
            if ($aTime !== $bTime) {
                return $bTime <=> $aTime;
            }

            $aSource = !empty($a['is_source_record']) ? 1 : 0;
            $bSource = !empty($b['is_source_record']) ? 1 : 0;
            if ($aSource !== $bSource) {
                return $aSource <=> $bSource;
            }

            return (int)($b['record_id'] ?? $b['id'] ?? 0) <=> (int)($a['record_id'] ?? $a['id'] ?? 0);
        });

        return array_values($lists);
    }

    private static function circleInteractionSourceRecords(string $id, ?int $platform, array $customer, array $params): array
    {
        if (($customer['domain'] ?? 'public') === 'private') {
            return [];
        }

        $sourceRefs = self::circleInteractionSourceRefs($id, $platform, $customer, $params);
        if (empty($sourceRefs)) {
            return [];
        }

        $records = [];
        foreach ($sourceRefs as $sourceKey => $ids) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (empty($ids)) {
                continue;
            }

            foreach (self::circleInteractionFetchSourceRows($sourceKey, $ids, $platform) as $row) {
                $record = self::formatCircleInteractionSourceRecord($row, $sourceKey);
                if (!empty($record)) {
                    $records[] = $record;
                }
            }
        }

        return self::dedupeCircleInteractionSourceRecords($records);
    }

    private static function circleInteractionSourceRefs(string $id, ?int $platform, array $customer, array $params): array
    {
        $refs = [];
        $sourceKey = self::followSourceKeyFromId($id);
        $sourceRecordId = self::sourceRecordIdFromId($id);
        if (self::isCircleInteractionSourceKey($sourceKey) && $sourceRecordId > 0) {
            $refs[$sourceKey][] = $sourceRecordId;
        }

        $customerRefs = self::circleInteractionCustomerSourceRefs($id, $platform);
        if (empty($customerRefs) && $platform !== null) {
            $customerRefs = self::circleInteractionCustomerSourceRefs($id, null);
        }
        foreach ($customerRefs as $key => $ids) {
            if (!self::isCircleInteractionSourceKey($key)) {
                continue;
            }
            $refs[$key] = array_merge($refs[$key] ?? [], $ids);
        }

        if (empty($refs)) {
            $fallbackRefs = self::circleInteractionFallbackSourceRefs($platform, $customer, $params);
            foreach ($fallbackRefs as $key => $ids) {
                $refs[$key] = array_merge($refs[$key] ?? [], $ids);
            }
        }

        foreach ($refs as $key => $ids) {
            $refs[$key] = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (empty($refs[$key])) {
                unset($refs[$key]);
            }
        }

        return $refs;
    }

    private static function circleInteractionCustomerSourceRefs(string $id, ?int $platform): array
    {
        foreach (IntentionCustomerService::customers((int)self::$uid, $platform) as $item) {
            if ((string)($item['id'] ?? '') !== $id) {
                continue;
            }

            return self::sourceRefsFromDedupeKeys($item['_dedupe_keys'] ?? []);
        }

        return [];
    }

    private static function sourceRefsFromDedupeKeys(array $keys): array
    {
        $refs = [];
        foreach ($keys as $key) {
            $key = trim((string)$key);
            if (!str_starts_with($key, 'source:')) {
                continue;
            }

            $sourceId = substr($key, strlen('source:'));
            $sourceKey = self::followSourceKeyFromId($sourceId);
            $recordId = self::sourceRecordIdFromId($sourceId);
            if (self::isCircleInteractionSourceKey($sourceKey) && $recordId > 0) {
                $refs[$sourceKey][] = $recordId;
            }
        }

        return $refs;
    }

    private static function sourceRecordIdFromId(string $id): int
    {
        $parts = explode(':', $id, 2);
        return (int)($parts[1] ?? 0);
    }

    private static function isCircleInteractionSourceKey(string $sourceKey): bool
    {
        return in_array($sourceKey, ['lead_scraping', 'city_touch', 'group_buy'], true);
    }

    private static function circleInteractionFallbackSourceRefs(?int $platform, array $customer, array $params): array
    {
        $account = trim((string)($customer['account'] ?? ($params['account'] ?? '')));
        $names = array_values(array_unique(array_filter(array_map(static function ($value): string {
            $value = trim((string)$value);
            return $value !== '' && strtolower($value) !== 'null' ? $value : '';
        }, [
            $customer['customer_name'] ?? '',
            $customer['account_name'] ?? '',
            $params['customer_name'] ?? '',
            $params['account_name'] ?? '',
        ]))));

        if ($account === '' && empty($names)) {
            return [];
        }

        return [
            'lead_scraping' => self::circleInteractionSourceIdsByCustomer('lead_scraping', $platform, $account, $names),
            'city_touch' => self::circleInteractionSourceIdsByCustomer('city_touch', $platform, $account, $names),
            'group_buy' => self::circleInteractionSourceIdsByCustomer('group_buy', $platform, $account, $names),
        ];
    }

    private static function circleInteractionSourceIdsByCustomer(string $sourceKey, ?int $platform, string $account, array $names): array
    {
        $query = self::circleInteractionSourceBaseQuery($sourceKey, $platform)
            ->field('r.id')
            ->where(function ($query) use ($account, $names) {
                $index = 0;
                if ($account !== '') {
                    $query->where('r.account', $account);
                    $index++;
                }
                if (!empty($names)) {
                    $method = $index === 0 ? 'where' : 'whereOr';
                    $query->{$method}('r.account_name', 'in', $names);
                }
            });

        return array_map('intval', $query->column('r.id'));
    }

    private static function circleInteractionFetchSourceRows(string $sourceKey, array $ids, ?int $platform): array
    {
        $field = self::circleInteractionSourceFields($sourceKey);
        if ($field === '') {
            return [];
        }

        return self::circleInteractionSourceBaseQuery($sourceKey, $platform)
            ->where('r.id', 'in', $ids)
            ->field($field)
            ->select()
            ->toArray();
    }

    private static function circleInteractionSourceBaseQuery(string $sourceKey, ?int $platform)
    {
        if ($sourceKey === 'city_touch') {
            $query = SvCityTouchRecord::alias('r')
                ->join('sv_city_touch_task t', 't.id = r.city_touch_id and t.user_id = r.user_id', 'left');
        } elseif ($sourceKey === 'group_buy') {
            $query = SvGroupBuyRecord::alias('r')
                ->join('sv_group_buy_task_account gta', 'gta.id = r.group_buy_account_id and gta.user_id = r.user_id', 'left')
                ->join('sv_group_buy_task gt', 'gt.id = r.group_buy_id and gt.user_id = r.user_id', 'left');
        } else {
            $query = SvLeadScrapingRecord::alias('r')
                ->join('sv_lead_scraping_setting s', 's.id = r.scraping_id and s.user_id = r.user_id', 'left');
        }

        $query->where('r.user_id', self::$uid)
            ->whereNull('r.delete_time')
            ->where('r.status', '<>', 4)
            ->where('r.account', '<>', '');
        self::applyCircleInteractionPlatformFilter($query, $platform);

        return $query;
    }

    /**
     * 应用平台筛选条件
     * @param \think\db\Query $query 查询对象
     * @param int|null $platform 平台类型
     * @return void
     */
    private static function applyCircleInteractionPlatformFilter($query, ?int $platform): void
    {
        if ($platform === null) {
            return;
        }

        $query->where(function ($query) use ($platform) {
            $query->where('r.account_type', $platform)->whereOr('r.platform', $platform);
        });
    }

    private static function circleInteractionSourceFields(string $sourceKey): string
    {
        if ($sourceKey === 'city_touch') {
            return 'r.id,r.user_id,r.account,r.account_name,r.account_type,r.platform,r.avatar,r.content,r.filter_keyword,r.touch_content,r.comment_content,r.exec_time,r.create_time,r.task_type,r.device_code,r.task_id,r.image,t.task_type as setting_task_type,t.marker_method,t.chat_type';
        }
        if ($sourceKey === 'group_buy') {
            return 'r.id,r.user_id,r.account,r.account_name,r.account_type,r.platform,r.avatar,r.content,r.filter_keyword,r.touch_content,r.comment_content,r.exec_time,r.create_time,r.task_type,r.device_code,r.task_id,r.image,gta.task_type as account_task_type,gt.task_type as setting_task_type';
        }
        if ($sourceKey === 'lead_scraping') {
            return 'r.id,r.user_id,r.account,r.account_name,r.account_type,r.platform,r.avatar,r.content,r.filter_keyword,r.touch_content,r.comment_content,r.exec_time,r.create_time,r.task_type,r.device_code,r.task_id,r.image,s.task_type as setting_task_type';
        }

        return '';
    }

    private static function formatCircleInteractionSourceRecord(array $row, string $sourceKey): array
    {
        $stats = IntentionCustomerService::sourceInteractionStats($row, $sourceKey);
        if ($stats['interaction_count'] <= 0) {
            return [];
        }

        $type = 0;
        if ($stats['like_count'] > 0 && $stats['comment_count'] > 0) {
            $type = 3;
        } elseif ($stats['like_count'] > 0) {
            $type = 1;
        } elseif ($stats['comment_count'] > 0) {
            $type = 2;
        }

        $images = self::parseCircleInteractionImages($row['image'] ?? '');
        $comment = (string)($row['comment_content'] ?? '');
        $content = self::firstCircleInteractionSourceText([
            $row['content'] ?? '',
            $row['touch_content'] ?? '',
            $row['filter_keyword'] ?? '',
        ]);
        $time = self::formatPrivateMessageTime($row['exec_time'] ?? '', $row['create_time'] ?? '');
        $recordId = (int)($row['id'] ?? 0);

        return [
            'id' => self::circleInteractionSourceVirtualId($sourceKey, $recordId),
            'record_id' => $recordId,
            'nickname' => (string)($row['account_name'] ?? ''),
            'content' => $content,
            'image' => $images[0] ?? '',
            'images' => $images,
            'comment' => $comment,
            'comments' => self::parseCircleInteractionComments($comment),
            'type' => $type,
            'type_desc' => self::circleInteractionTypeDesc($type),
            'is_liked' => in_array($type, [1, 3], true),
            'is_commented' => in_array($type, [2, 3], true),
            'execute_account' => '',
            'execute_name' => '',
            'execute_avatar' => '',
            'create_time' => $time,
            'source_key' => $sourceKey,
            'source_name' => self::followSourceName($sourceKey),
            'source_record_id' => $recordId,
            'is_source_record' => true,
        ];
    }

    private static function circleInteractionSourceVirtualId(string $sourceKey, int $recordId): int
    {
        $offsets = [
            'lead_scraping' => 1000000000,
            'city_touch' => 2000000000,
            'group_buy' => 3000000000,
        ];

        return -(($offsets[$sourceKey] ?? 9000000000) + $recordId);
    }

    private static function firstCircleInteractionSourceText(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '' && strtolower($value) !== 'null') {
                return $value;
            }
        }

        return '';
    }

    private static function dedupeCircleInteractionSourceRecords(array $records): array
    {
        $result = [];
        $seen = [];
        foreach ($records as $record) {
            $key = (string)($record['source_key'] ?? '') . ':' . (int)($record['source_record_id'] ?? 0);
            if ($key === ':0' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $record;
        }

        return $result;
    }

    private static function circleInteractionExecuteAccounts(array $records): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static function (array $record) {
            return (int)($record['like_reply_account'] ?? 0);
        }, $records))));
        if (empty($ids)) {
            return [];
        }

        $accounts = SvDeviceCircleLikeReplyAccount::where('user_id', self::$uid)
            ->where('id', 'in', $ids)
            ->field('id,account,nickname,avatar')
            ->select()
            ->toArray();
        $maps = [];
        foreach ($accounts as $account) {
            $maps[(int)$account['id']] = $account;
        }

        return $maps;
    }

    private static function formatCircleInteractionRecord(array $record, array $executeAccount): array
    {
        $type = (int)($record['type'] ?? 0);
        $images = self::parseCircleInteractionImages($record['image'] ?? '');
        $comments = self::parseCircleInteractionComments($record['comment'] ?? '');

        return [
            'id' => (int)($record['id'] ?? 0),
            'record_id' => (int)($record['id'] ?? 0),
            'nickname' => (string)($record['nickname'] ?? ''),
            'content' => (string)($record['content'] ?? ''),
            'image' => $images[0] ?? '',
            'images' => $images,
            'comment' => (string)($record['comment'] ?? ''),
            'comments' => $comments,
            'type' => $type,
            'type_desc' => self::circleInteractionTypeDesc($type),
            'is_liked' => in_array($type, [1, 3], true),
            'is_commented' => in_array($type, [2, 3], true),
            'execute_account' => (string)($executeAccount['account'] ?? ($record['account'] ?? '')),
            'execute_name' => (string)($executeAccount['nickname'] ?? ''),
            'execute_avatar' => self::completePrivateMessageFileUrl((string)($executeAccount['avatar'] ?? '')),
            'create_time' => self::formatPrivateMessageTime($record['create_time'] ?? ''),
        ];
    }

    private static function parseCircleInteractionImages(mixed $image): array
    {
        if (is_array($image)) {
            $values = $image;
        } else {
            $image = trim((string)$image);
            if ($image === '') {
                return [];
            }
            $decoded = json_decode($image, true);
            $values = is_array($decoded) ? $decoded : preg_split('/[,，]+/u', $image);
        }

        $images = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $images[] = self::completePrivateMessageFileUrl($value);
            }
        }

        return array_values(array_unique(array_filter($images)));
    }

    private static function parseCircleInteractionComments(mixed $comment): array
    {
        if (is_array($comment)) {
            $values = $comment;
        } else {
            $comment = trim((string)$comment);
            if ($comment === '') {
                return [];
            }
            $decoded = json_decode($comment, true);
            $values = is_array($decoded) ? $decoded : preg_split('/[,，]+/u', $comment);
        }

        $comments = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $comments[] = $value;
            }
        }

        return array_values(array_unique($comments));
    }

    private static function circleInteractionTypeDesc(int $type): string
    {
        $maps = [
            1 => '已点赞',
            2 => '已评论',
            3 => '点赞并评论',
        ];

        return $maps[$type] ?? '未知';
    }

    public static function followRecord(array $params): bool
    {
        try {
            $id = trim((string)($params['id'] ?? ''));
            if ($id === '') {
                self::setError('缺少参数id');
                return false;
            }

            $platform = IntentionCustomerService::parsePlatform($params['platform'] ?? 'all');
            $customer = self::findFollowCustomer($id, $platform);
            if (empty($customer) && $platform !== null) {
                $customer = self::findFollowCustomer($id, null);
            }
            if (empty($customer) && str_starts_with($id, 'private_message:')) {
                $record = self::findPrivateMessageRecord((int)substr($id, strlen('private_message:')));
                if (!empty($record)) {
                    $customer = self::formatFollowCustomerFromPrivateRecord($id, $record, $params);
                }
            }
            if (empty($customer)) {
                $customer = self::fallbackFollowCustomer($id, $platform, $params);
            }

            $privateRecords = self::getFollowPrivateMessageRecords($id, $customer, $params);
            $messages = self::formatPrivateMessageRows($privateRecords);
            $wechatRecord = self::findFollowWechatRecord($customer, $messages);
            $customer = self::mergeFollowCustomerData($customer, $privateRecords, $messages, $wechatRecord);

            self::$returnData = [
                'customer' => self::formatFollowCustomer($customer),
                'records' => [
                    self::buildFollowInflowRecord($customer),
                    self::buildFollowPublicChatRecord($messages),
                    self::buildFollowWechatAddRecord($customer, $wechatRecord),
                    self::buildFollowPrivateChatRecord($customer, $wechatRecord),
                ],
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function findFollowCustomer(string $id, ?int $platform): array
    {
        foreach (IntentionCustomerService::customers((int)self::$uid, $platform) as $customer) {
            if ((string)($customer['id'] ?? '') === $id) {
                return IntentionCustomerService::responseItem($customer);
            }
        }

        return [];
    }

    private static function formatFollowCustomerFromPrivateRecord(string $id, array $record, array $params): array
    {
        $platform = (int)($record['type'] ?? 0);
        $friendId = trim((string)($record['friend_id'] ?? ''));
        $account = $friendId !== '' ? $friendId : trim((string)($record['account'] ?? ''));
        $customerName = trim((string)($record['author_name'] ?? ''))
            ?: trim((string)($params['customer_name'] ?? ''))
            ?: $account;
        $wechatNo = self::extractFollowContactToken([
            $record['message_content'] ?? '',
            $record['reply_content'] ?? '',
            $params['wechat_no'] ?? '',
        ]);
        $replyTime = self::formatPrivateMessageTime($record['reply_time'] ?? '');
        $isReply = (int)($record['is_reply'] ?? 0) === 1 || $replyTime !== '';

        return [
            'id' => $id,
            'customer_name' => $customerName,
            'account_name' => $customerName,
            'avatar' => self::completePrivateMessageFileUrl((string)($record['avatar'] ?? '')),
            'platform' => $platform,
            'platform_desc' => $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '',
            'account' => $account,
            'wechat_no' => $wechatNo,
            'wechat_status' => $isReply ? 'replied' : ($wechatNo !== '' ? 'recognized' : 'unrecognized'),
            'first_private_time' => $isReply ? ($replyTime ?: self::formatPrivateMessageTime($record['message_timer'] ?? '', $record['create_time'] ?? '')) : '',
            'platform_message_count' => max(1, (int)($record['new_message_count'] ?? 0)),
            'platform_reply_count' => $isReply ? 1 : 0,
            'domain' => ($wechatNo !== '' || $isReply) ? 'private' : 'public',
            'source_key' => 'private_message',
            'source_name' => self::followSourceName('private_message'),
            'source_text' => '通过【' . self::followSourceName('private_message') . '】流入',
            'latest_intention' => (string)($record['message_content'] ?? ''),
            'create_time' => self::formatPrivateMessageTime($record['create_time'] ?? ''),
        ];
    }

    private static function fallbackFollowCustomer(string $id, ?int $platform, array $params): array
    {
        $sourceKey = self::followSourceKeyFromId($id);
        $sourceName = self::followSourceName($sourceKey);
        $account = trim((string)($params['account'] ?? ''));
        $customerName = trim((string)($params['customer_name'] ?? '')) ?: $account;
        $wechatNo = trim((string)($params['wechat_no'] ?? ''));

        return [
            'id' => $id,
            'customer_name' => $customerName,
            'account_name' => $customerName,
            'avatar' => '',
            'platform' => $platform ?? 0,
            'platform_desc' => $platform !== null && $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '',
            'account' => $account,
            'wechat_no' => $wechatNo,
            'wechat_status' => $wechatNo !== '' ? 'recognized' : 'unrecognized',
            'domain' => $wechatNo !== '' ? 'private' : 'public',
            'source_key' => $sourceKey,
            'source_name' => $sourceName,
            'source_text' => $sourceName !== '' ? '通过【' . $sourceName . '】流入' : '',
            'create_time' => '',
        ];
    }

    private static function getFollowPrivateMessageRecords(string $id, array $customer, array $params): array
    {
        $records = [];
        if (str_starts_with($id, 'private_message:')) {
            $recordId = (int)substr($id, strlen('private_message:'));
            if ($recordId > 0) {
                $record = self::findPrivateMessageRecord($recordId);
                if (!empty($record)) {
                    $records = self::getPrivateMessageConversationByRecord($record);
                }
            }
        }

        if (!empty($records)) {
            return $records;
        }

        $platform = (int)($customer['platform'] ?? 0);
        if ($platform <= 0) {
            $platform = (int)(IntentionCustomerService::parsePlatform($params['platform'] ?? '') ?? 0);
        }
        $account = trim((string)($customer['account'] ?? ($params['account'] ?? '')));
        $customerName = trim((string)($customer['customer_name'] ?? ($params['customer_name'] ?? '')));
        if ($platform <= 0 || $account === '') {
            return [];
        }

        return self::getPrivateMessageConversationByCustomer($platform, $account, $customerName);
    }

    private static function findFollowWechatRecord(array $customer, array $messages): array
    {
        $messageContents = array_map(static fn(array $message) => (string)($message['content'] ?? ''), $messages);
        $wechatNo = trim((string)($customer['wechat_no'] ?? '')) ?: self::extractFollowContactToken($messageContents);
        $platform = (int)($customer['platform'] ?? 0);
        $account = trim((string)($customer['account'] ?? ''));
        $customerName = trim((string)($customer['customer_name'] ?? ''));
        if ($wechatNo === '' && $account === '' && $customerName === '') {
            return [];
        }

        $query = SvAddWechatRecord::where('user_id', self::$uid)
            ->whereNull('delete_time');

        if ($platform > 0) {
            $query->where(function ($query) use ($platform) {
                $query->where('account_type', $platform)->whereOr('channel', $platform);
            });
        }

        $query->where(function ($query) use ($wechatNo, $account, $customerName) {
            if ($wechatNo !== '') {
                $query->where('reg_wechat', $wechatNo)
                    ->whereOr('original_message', 'like', '%' . $wechatNo . '%');
            }
            if ($account !== '') {
                if ($wechatNo === '') {
                    $query->where('user_account', $account);
                } else {
                    $query->whereOr('user_account', $account);
                }
                $query
                    ->whereOr('account', $account)
                    ->whereOr('original_message', 'like', '%' . $account . '%');
            }
            if ($customerName !== '') {
                if ($wechatNo === '' && $account === '') {
                    $query->where('user_account', $customerName);
                } else {
                    $query->whereOr('user_account', $customerName);
                }
                $query->whereOr('original_message', 'like', '%' . $customerName . '%');
            }
        });

        $record = $query
            ->field('id,user_id,account,account_type,user_account,original_message,reg_wechat,wechat_no,wechat_name,wechat_avatar,action,status,result,task_id,channel,exec_type,remark,image,create_time,update_time')
            ->order('id desc')
            ->find();

        return empty($record) ? [] : $record->toArray();
    }

    private static function mergeFollowCustomerData(array $customer, array $privateRecords, array $messages, array $wechatRecord): array
    {
        $firstRecord = $privateRecords[0] ?? [];
        if (($customer['customer_name'] ?? '') === '' && !empty($firstRecord['author_name'])) {
            $customer['customer_name'] = (string)$firstRecord['author_name'];
        }
        if (($customer['account_name'] ?? '') === '' && !empty($customer['customer_name'])) {
            $customer['account_name'] = (string)$customer['customer_name'];
        }
        if (($customer['avatar'] ?? '') === '' && !empty($firstRecord['avatar'])) {
            $customer['avatar'] = self::completePrivateMessageFileUrl((string)$firstRecord['avatar']);
        }
        if ((int)($customer['platform'] ?? 0) <= 0 && !empty($firstRecord['type'])) {
            $customer['platform'] = (int)$firstRecord['type'];
            $customer['platform_desc'] = DeviceEnum::getAccountTypeDesc((int)$firstRecord['type']);
        }
        if (($customer['account'] ?? '') === '' && !empty($firstRecord['friend_id'])) {
            $customer['account'] = (string)$firstRecord['friend_id'];
        }

        $wechatNo = trim((string)($customer['wechat_no'] ?? ''))
            ?: trim((string)($wechatRecord['reg_wechat'] ?? ''))
            ?: self::extractFollowContactToken(array_map(static fn(array $message) => (string)($message['content'] ?? ''), $messages));
        $customer['wechat_no'] = $wechatNo;

        $hasReply = !empty(array_filter($messages, static fn(array $message) => ($message['direction'] ?? '') === 'self'));
        if ($wechatNo !== '' || !empty($wechatRecord) || $hasReply) {
            $customer['domain'] = 'private';
            $customer['wechat_status'] = $hasReply ? 'replied' : (($customer['wechat_status'] ?? '') ?: 'recognized');
            if (($customer['wechat_status'] ?? '') === 'unrecognized') {
                $customer['wechat_status'] = 'recognized';
            }
        }

        return $customer;
    }

    private static function formatFollowCustomer(array $customer): array
    {
        $platform = (int)($customer['platform'] ?? 0);
        $customerName = trim((string)($customer['customer_name'] ?? ''));
        $account = trim((string)($customer['account'] ?? ''));

        return [
            'id' => (string)($customer['id'] ?? ''),
            'customer_name' => $customerName !== '' ? $customerName : $account,
            'account_name' => (string)($customer['account_name'] ?? ($customerName !== '' ? $customerName : $account)),
            'avatar' => self::completePrivateMessageFileUrl((string)($customer['avatar'] ?? '')),
            'platform' => $platform,
            'platform_desc' => (string)($customer['platform_desc'] ?? ($platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '')),
            'account' => $account,
            'wechat_no' => (string)($customer['wechat_no'] ?? ''),
            'wechat_status' => (string)($customer['wechat_status'] ?? 'unrecognized'),
            'domain' => (string)($customer['domain'] ?? 'public'),
            'source_key' => (string)($customer['source_key'] ?? ''),
            'source_name' => (string)($customer['source_name'] ?? ''),
            'source_text' => (string)($customer['source_text'] ?? ''),
        ];
    }

    private static function buildFollowInflowRecord(array $customer): array
    {
        $sourceName = trim((string)($customer['source_name'] ?? ''));
        $sourceText = trim((string)($customer['source_text'] ?? ''));

        return [
            'key' => 'inflow',
            'title' => '流入',
            'status' => 'done',
            'time' => self::formatPrivateMessageTime($customer['create_time'] ?? '', $customer['first_private_time'] ?? ''),
            'desc' => $sourceName !== '' ? '通过【' . $sourceName . '】识别流入' : ($sourceText ?: '客户识别流入'),
            'content' => [],
            'extra' => (object)[],
        ];
    }

    private static function buildFollowPublicChatRecord(array $messages): array
    {
        $customerMessageCount = 0;
        $replyCount = 0;
        $contents = [];
        foreach ($messages as $message) {
            if (($message['direction'] ?? '') === 'customer') {
                $customerMessageCount++;
            } elseif (($message['direction'] ?? '') === 'self') {
                $replyCount++;
            }

            $content = trim((string)($message['content'] ?? ''));
            if ($content !== '') {
                $contents[] = $content;
            }
        }

        $latestMessage = !empty($messages) ? $messages[array_key_last($messages)] : [];
        $messageCount = $customerMessageCount + $replyCount;
        $done = $messageCount > 0;

        return [
            'key' => 'public_chat',
            'title' => '公域聊天',
            'status' => $done ? 'done' : 'pending',
            'time' => $done ? (string)($latestMessage['time'] ?? '') : '',
            'desc' => $done ? '平台私聊 ' . $customerMessageCount . ' 条，询问客户需求' : '暂无公域聊天记录',
            'content' => array_slice($contents, -3),
            'extra' => [
                'message_count' => $customerMessageCount,
                'reply_count' => $replyCount,
            ],
        ];
    }

    private static function buildFollowWechatAddRecord(array $customer, array $wechatRecord): array
    {
        $wechatNo = trim((string)($wechatRecord['reg_wechat'] ?? '')) ?: trim((string)($customer['wechat_no'] ?? ''));
        $hasRecord = !empty($wechatRecord);
        $status = $hasRecord ? (int)($wechatRecord['status'] ?? 0) : 0;
        $statusDesc = $hasRecord ? self::followWechatApplyStatusDesc($status) : '';
        $done = $hasRecord || $wechatNo !== '';

        if (!$done) {
            $desc = '未识别到微信号，等待进一步互动';
        } elseif (!$hasRecord) {
            $desc = '已识别微信号，等待提交好友申请';
        } else {
            $desc = $statusDesc !== '' ? $statusDesc : '已提交添加好友申请';
        }

        return [
            'key' => 'wechat_add',
            'title' => '识别微信加好友',
            'status' => $done ? 'done' : 'pending',
            'time' => $hasRecord ? self::formatPrivateMessageTime($wechatRecord['create_time'] ?? '', $wechatRecord['update_time'] ?? '') : '',
            'desc' => $desc,
            'content' => [],
            'extra' => [
                'wechat_no' => $wechatNo,
                'apply_status' => $status,
                'apply_status_desc' => $statusDesc,
            ],
        ];
    }

    private static function buildFollowPrivateChatRecord(array $customer, array $wechatRecord): array
    {
        [$replyCount, $latestTime] = self::followWechatReplyStats($customer, $wechatRecord);

        return [
            'key' => 'private_chat',
            'title' => '私域聊天',
            'status' => $replyCount > 0 ? 'done' : 'pending',
            'time' => $latestTime,
            'desc' => $replyCount > 0 ? '微信私域已回复 ' . $replyCount . ' 条' : '添加好友后开始私域跟进',
            'content' => [],
            'extra' => [
                'wechat_reply_count' => $replyCount,
            ],
        ];
    }

    private static function followWechatReplyStats(array $customer, array $wechatRecord): array
    {
        $wechatId = trim((string)($wechatRecord['wechat_no'] ?? ''));
        $friendId = trim((string)($wechatRecord['reg_wechat'] ?? '')) ?: trim((string)($customer['wechat_no'] ?? ''));
        if ($wechatId === '' && $friendId === '') {
            return [0, ''];
        }

        $count = self::followWechatReplyQuery($wechatId, $friendId)->count();
        $latestTime = '';
        if ($count > 0) {
            $latestCreateTime = self::followWechatReplyQuery($wechatId, $friendId)
                ->order('create_time desc,id desc')
                ->value('create_time');
            $latestTime = self::formatPrivateMessageTime($latestCreateTime);
        }

        return [(int)$count, $latestTime];
    }

    private static function followWechatReplyQuery(string $wechatId, string $friendId)
    {
        $query = AiWechatLog::where('user_id', self::$uid)
            ->where('log_type', AiWechatLog::TYPE_MESSAGE_REPLY);
        if ($wechatId !== '' && $friendId !== '') {
            $query->where('wechat_id', $wechatId)->where('friend_id', $friendId);
        } elseif ($wechatId !== '') {
            $query->where('wechat_id', $wechatId);
        } else {
            $query->where('friend_id', $friendId);
        }

        return $query;
    }

    private static function followWechatApplyStatusDesc(int $status): string
    {
        $maps = [
            0 => '添加好友失败',
            1 => '已添加好友',
            2 => '已提交添加好友申请，等待通过',
            3 => '当前账号存在安全风险，暂停添加',
            4 => '待执行添加好友',
            5 => '冷却中，等待后可继续添加',
        ];

        return $maps[$status] ?? '';
    }

    private static function followSourceKeyFromId(string $id): string
    {
        $parts = explode(':', $id, 2);
        return trim((string)($parts[0] ?? ''));
    }

    private static function followSourceName(string $sourceKey): string
    {
        $maps = [
            'lead_scraping' => '截流',
            'city_exposure' => '曝光',
            'city_touch' => '同城视频',
            'group_buy' => '团购',
            'sph_like' => '视频号点赞',
            'private_message' => '私信/评论',
        ];

        return $maps[$sourceKey] ?? '';
    }

    private static function extractFollowContactToken(array $values): string
    {
        $text = implode(' ', array_map(static fn($value) => (string)$value, $values));
        if ($text === '') {
            return '';
        }

        if (preg_match('/1[3-9]\d{9}/', $text, $matches)) {
            return $matches[0];
        }
        if (preg_match('/(?:微信|wx|v信|VX|WeChat|wechat)[:：\s]*([a-zA-Z][-_a-zA-Z0-9]{5,19})/u', $text, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private static function findPrivateMessageRecord(int $recordId): array
    {
        $record = SvPrivateMessage::where('user_id', self::$uid)
            ->where('id', $recordId)
            ->whereNull('delete_time')
            ->find();

        return empty($record) ? [] : $record->toArray();
    }

    private static function findLargestPrivateMessageConversationRecord(array $record, int $platform, string $customerName): array
    {
        $customerName = trim($customerName);
        if ($platform <= 0 || $customerName === '') {
            return $record;
        }

        $rows = SvPrivateMessage::where('user_id', self::$uid)
            ->whereNull('delete_time')
            ->where('type', $platform)
            ->where('author_name', $customerName)
            ->where('message_task_type', 'in', [1, 2])
            ->where(function ($query) {
                $query->where('friend_id', '<>', '')->whereOr('account', '<>', '');
            })
            ->field('id,user_id,device_code,account,type,friend_id,avatar,author_name,message_content,message_timer,new_message_count,is_reply,reply_content,reply_time,create_time,update_time')
            ->order('create_time asc,id asc')
            ->select()
            ->toArray();

        if (empty($rows)) {
            return $record;
        }

        $recordKey = self::privateMessageConversationKey($record);
        $groups = [];
        foreach ($rows as $row) {
            $key = self::privateMessageConversationKey($row);
            if ($key === '') {
                continue;
            }
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'record' => $row,
                    'count' => 0,
                    'contains_record' => false,
                ];
            }

            $groups[$key]['count'] += self::privateMessageRowMessageCount($row);
            if ((int)($row['id'] ?? 0) === (int)($record['id'] ?? 0)) {
                $groups[$key]['contains_record'] = true;
            }
        }

        if (empty($groups)) {
            return $record;
        }

        $best = null;
        foreach ($groups as $key => $group) {
            if ($best === null
                || (int)$group['count'] > (int)$groups[$best]['count']
                || ((int)$group['count'] === (int)$groups[$best]['count']
                    && $key === $recordKey
                    && empty($groups[$best]['contains_record']))
            ) {
                $best = $key;
            }
        }

        if ($best === null || (int)$groups[$best]['count'] <= 0) {
            return $record;
        }

        return $groups[$best]['record'];
    }

    private static function privateMessageConversationKey(array $record): string
    {
        $platform = (int)($record['type'] ?? 0);
        $account = strtolower(trim((string)($record['account'] ?? '')));
        $friendId = strtolower(trim((string)($record['friend_id'] ?? '')));
        if ($platform <= 0 || ($account === '' && $friendId === '')) {
            return '';
        }

        return $platform . ':' . $account . ':' . $friendId;
    }

    private static function privateMessageRowMessageCount(array $record): int
    {
        $count = 0;
        foreach (['message_content', 'reply_content'] as $field) {
            if (self::parsePrivateMessageContent($record[$field] ?? '')['content'] !== '') {
                $count++;
            }
        }

        return $count;
    }

    private static function getPrivateMessageConversationByRecord(array $record): array
    {
        return SvPrivateMessage::where('user_id', self::$uid)
            ->whereNull('delete_time')
            ->where('type', (int)($record['type'] ?? 0))
            ->where('account', (string)($record['account'] ?? ''))
            ->where('friend_id', (string)($record['friend_id'] ?? ''))
            ->field('id,user_id,device_code,account,type,friend_id,avatar,author_name,message_content,message_timer,new_message_count,is_reply,reply_content,reply_time,create_time,update_time')
            ->order('create_time asc,id asc')
            ->select()
            ->toArray();
    }

    private static function getPrivateMessageConversationByCustomer(int $platform, string $account, string $customerName): array
    {
        $fields = 'id,user_id,device_code,account,type,friend_id,avatar,author_name,message_content,message_timer,new_message_count,is_reply,reply_content,reply_time,create_time,update_time';

        // 优先通过 friend_id 查询
        $records = SvPrivateMessage::where('user_id', self::$uid)
            ->whereNull('delete_time')
            ->where('type', $platform)
            ->where('friend_id', $account)
            ->field($fields)
            ->order('create_time asc,id asc')
            ->select()
            ->toArray();

        // 如果没查到，通过 author_name 查询
        if (empty($records) && $customerName !== '') {
            $records = SvPrivateMessage::where('user_id', self::$uid)
                ->whereNull('delete_time')
                ->where('type', $platform)
                ->where('author_name', $customerName)
                ->field($fields)
                ->order('create_time asc,id asc')
                ->select()
                ->toArray();
        }

        // 如果还没查到，使用模糊匹配
        if (empty($records)) {
            $records = SvPrivateMessage::where('user_id', self::$uid)
                ->whereNull('delete_time')
                ->where('type', $platform)
                ->where(function ($query) use ($account, $customerName) {
                    if ($account !== '') {
                        $query->where('friend_id', 'like', "%{$account}%");
                    }
                    if ($customerName !== '') {
                        $query->whereOr('author_name', 'like', "%{$customerName}%");
                    }
                })
                ->field($fields)
                ->order('create_time asc,id asc')
                ->select()
                ->toArray();
        }

        return $records;
    }

    private static function formatPrivateMessageRows(array $records, bool $useMessageTimer = true, array $replyAccountMap = []): array
    {
        $messages = [];
        foreach ($records as $record) {
            $customerParticipant = self::privateMessageCustomerParticipant($record);
            $selfParticipant = self::privateMessageSelfParticipant($record, $replyAccountMap);

            $customerContent = self::parsePrivateMessageContent($record['message_content'] ?? '');
            if ($customerContent['content'] !== '') {
                $messages[] = [
                    'id' => 'customer:' . $record['id'],
                    'record_id' => (int)$record['id'],
                    'direction' => 'customer',
                    'content' => self::formatPrivateMessageContent($customerContent['content'], $customerContent['content_type']),
                    'content_type' => $customerContent['content_type'],
                    'time' => $useMessageTimer
                        ? self::formatPrivateMessageTime($record['message_timer'] ?? '', $record['create_time'] ?? '')
                        : self::formatPrivateMessageTime($record['create_time'] ?? ''),
                    'avatar' => $customerParticipant['avatar'],
                    'is_self' => 0,
                    'bubble_side' => 'left',
                    'sender' => $customerParticipant,
                    'receiver' => $selfParticipant,
                ];
            }

            $replyContent = self::parsePrivateMessageContent($record['reply_content'] ?? '');
            if ($replyContent['content'] !== '') {
                $messages[] = [
                    'id' => 'self:' . $record['id'],
                    'record_id' => (int)$record['id'],
                    'direction' => 'self',
                    'content' => self::formatPrivateMessageContent($replyContent['content'], $replyContent['content_type']),
                    'content_type' => $replyContent['content_type'],
                    'time' => self::formatPrivateMessageTime($record['reply_time'] ?? '', $record['update_time'] ?? ($record['create_time'] ?? '')),
                    'avatar' => $selfParticipant['avatar'],
                    'is_self' => 1,
                    'bubble_side' => 'right',
                    'sender' => $selfParticipant,
                    'receiver' => $customerParticipant,
                ];
            }
        }

        usort($messages, function (array $a, array $b) {
            return self::privateMessageTimeSortValue($a['time'] ?? '') <=> self::privateMessageTimeSortValue($b['time'] ?? '');
        });

        return $messages;
    }

    private static function getPrivateMessageReplyAccountMap(array $records): array
    {
        $accounts = [];
        $types = [];
        foreach ($records as $record) {
            $account = trim((string)($record['account'] ?? ''));
            $type = (int)($record['type'] ?? 0);
            if ($account === '' || $type <= 0) {
                continue;
            }
            $accounts[] = $account;
            $types[] = $type;
        }

        $accounts = array_values(array_unique($accounts));
        $types = array_values(array_unique($types));
        if (empty($accounts) || empty($types)) {
            return [];
        }

        $rows = SvAccount::field('user_id,device_code,account,type,nickname,avatar')
            ->where('user_id', self::$uid)
            ->whereIn('account', $accounts)
            ->whereIn('type', $types)
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $exactKey = self::privateMessageReplyAccountKey(
                (int)($row['user_id'] ?? 0),
                (int)($row['type'] ?? 0),
                (string)($row['account'] ?? ''),
                (string)($row['device_code'] ?? '')
            );
            $genericKey = self::privateMessageReplyAccountKey(
                (int)($row['user_id'] ?? 0),
                (int)($row['type'] ?? 0),
                (string)($row['account'] ?? ''),
                ''
            );
            $map[$exactKey] = $row;
            $map[$genericKey] = $map[$genericKey] ?? $row;
        }

        return $map;
    }

    private static function privateMessageCustomerParticipant(array $record): array
    {
        $platform = (int)($record['type'] ?? 0);
        $friendId = trim((string)($record['friend_id'] ?? ''));
        $account = $friendId !== '' ? $friendId : trim((string)($record['account'] ?? ''));
        $name = trim((string)($record['author_name'] ?? ''));
        if ($name === '') {
            $name = $account;
        }

        return [
            'role' => 'customer',
            'name' => $name,
            'avatar' => self::completePrivateMessageFileUrl((string)($record['avatar'] ?? '')),
            'account' => $account,
            'platform' => $platform,
            'platform_desc' => $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '',
            'device_code' => '',
        ];
    }

    private static function privateMessageSelfParticipant(array $record, array $replyAccountMap): array
    {
        $account = trim((string)($record['account'] ?? ''));
        $platform = (int)($record['type'] ?? 0);
        $deviceCode = trim((string)($record['device_code'] ?? ''));
        $accountInfo = self::findPrivateMessageReplyAccount($record, $replyAccountMap);
        $name = trim((string)($accountInfo['nickname'] ?? ''));
        if ($name === '') {
            $name = $account;
        }

        return [
            'role' => 'self',
            'name' => $name,
            'avatar' => self::completePrivateMessageFileUrl((string)($accountInfo['avatar'] ?? '')),
            'account' => $account !== '' ? $account : trim((string)($accountInfo['account'] ?? '')),
            'platform' => $platform,
            'platform_desc' => $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '',
            'device_code' => trim((string)($accountInfo['device_code'] ?? '')) ?: $deviceCode,
        ];
    }

    private static function findPrivateMessageReplyAccount(array $record, array $replyAccountMap): array
    {
        $userId = (int)($record['user_id'] ?? self::$uid);
        $platform = (int)($record['type'] ?? 0);
        $account = (string)($record['account'] ?? '');
        $deviceCode = (string)($record['device_code'] ?? '');
        $exactKey = self::privateMessageReplyAccountKey($userId, $platform, $account, $deviceCode);
        $genericKey = self::privateMessageReplyAccountKey($userId, $platform, $account, '');

        return $replyAccountMap[$exactKey] ?? $replyAccountMap[$genericKey] ?? [];
    }

    private static function privateMessageReplyAccountKey(int $userId, int $platform, string $account, string $deviceCode): string
    {
        return $userId . '|' . $platform . '|' . trim($account) . '|' . trim($deviceCode);
    }

    private static function formatPrivateMessageCustomer(array $records, array $fallback, int $messageCount): array
    {
        $first = $records[0] ?? [];
        $platform = (int)($first['type'] ?? ($fallback['platform'] ?? 0));
        $friendId = trim((string)($first['friend_id'] ?? ''));
        $account = $friendId !== '' ? $friendId : trim((string)($fallback['account'] ?? ($first['account'] ?? '')));
        $customerName = trim((string)($first['author_name'] ?? '')) ?: trim((string)($fallback['customer_name'] ?? ''));

        return [
            'customer_name' => $customerName !== '' ? $customerName : $account,
            'avatar' => self::completePrivateMessageFileUrl((string)($first['avatar'] ?? '')),
            'platform' => $platform,
            'platform_desc' => $platform > 0 ? DeviceEnum::getAccountTypeDesc($platform) : '',
            'account' => $account,
            'friend_id' => $friendId,
            'message_count' => $messageCount,
            'record_count' => count($records),
        ];
    }

    private static function parsePrivateMessageContent(mixed $content): array
    {
        $content = trim((string)$content);
        if ($content === '') {
            return ['content' => '', 'content_type' => 1];
        }

        $parts = preg_split('/\s*&&\s*/', $content, 2);
        $text = trim((string)($parts[0] ?? ''));
        $contentType = isset($parts[1]) && is_numeric(trim((string)$parts[1]))
            ? (int)trim((string)$parts[1])
            : 1;

        return [
            'content' => $text,
            'content_type' => $contentType > 0 ? $contentType : 1,
        ];
    }

    private static function formatPrivateMessageContent(string $content, int $contentType): string
    {
        if ($contentType === 2) {
            return self::completePrivateMessageFileUrl($content);
        }

        return $content;
    }

    private static function extractPrivateMessageScreenshots(array $messages): array
    {
        $images = [];
        foreach ($messages as $message) {
            $content = (string)($message['content'] ?? '');
            if ((int)($message['content_type'] ?? 1) === 2 && $content !== '') {
                $images[] = self::completePrivateMessageFileUrl($content);
            }
            foreach (self::extractImageUrlsFromText($content) as $image) {
                $images[] = self::completePrivateMessageFileUrl($image);
            }
        }

        return array_values(array_unique(array_filter($images)));
    }

    private static function extractImageUrlsFromText(string $content): array
    {
        if ($content === '') {
            return [];
        }

        preg_match_all('/(?:https?:\/\/|\/?uploads\/)[^\s"\']+\.(?:png|jpe?g|gif|webp)(?:\?[^\s"\']*)?/i', $content, $matches);
        return $matches[0] ?? [];
    }

    private static function completePrivateMessageFileUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return FileService::getFileUrl(ltrim($url, '/'));
    }

    private static function formatPrivateMessageTime(mixed $value, mixed $fallback = ''): string
    {
        $value = trim((string)$value);
        if ($value === '' || strtolower($value) === 'null') {
            $value = trim((string)$fallback);
        }
        if ($value === '' || strtolower($value) === 'null') {
            return '';
        }
        if (is_numeric($value)) {
            $timestamp = (int)$value;
            return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
        }

        $timestamp = strtotime($value);
        if (false === $timestamp) {
            $fallback = trim((string)$fallback);
            if ($fallback !== '' && $fallback !== $value) {
                return self::formatPrivateMessageTime($fallback);
            }
            return $value;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private static function privateMessageTimeSortValue(string $time): int
    {
        if ($time === '') {
            return 0;
        }

        $timestamp = strtotime($time);
        return false === $timestamp ? 0 : $timestamp;
    }

    private static function parseIntentionTimeRange(array $params): array
    {
        $date = '';
        $dateText = '';
        $startTime = 0;
        $endTime = 0;

        if (!empty($params['date'])) {
            $timestamp = strtotime((string)$params['date']);
            if (false === $timestamp) {
                $timestamp = time();
            }
            $date = date('Y-m-d', $timestamp);
            $dateText = date('n月j日', $timestamp);
            $startTime = strtotime($date . ' 00:00:00');
            $endTime = strtotime($date . ' 23:59:59');
        } elseif (!empty($params['start_time']) && !empty($params['end_time'])) {
            $startTime = is_numeric($params['start_time']) ? (int)$params['start_time'] : strtotime((string)$params['start_time']);
            $endTime = is_numeric($params['end_time']) ? (int)$params['end_time'] : strtotime((string)$params['end_time']);
            if ($startTime <= 0 || $endTime <= 0) {
                $startTime = 0;
                $endTime = 0;
            } elseif ($startTime > $endTime) {
                [$startTime, $endTime] = [$endTime, $startTime];
            }
        }

        return [
            'date' => $date,
            'date_text' => $dateText,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_range' => ($startTime > 0 && $endTime > 0) ? [$startTime, $endTime] : [],
        ];
    }

}
