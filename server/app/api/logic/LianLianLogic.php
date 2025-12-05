<?php

namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ChatPrompt;
use app\common\model\lianlian\LlAnalysis;
use app\common\model\lianlian\LlChat;
use app\common\model\lianlian\LlScene;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\facade\Db;

/**
 * logic
 */
class LianLianLogic extends ApiLogic
{

    /**
     * 重试
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-05 11:34:16
     */
    public static function analysisRetry(int $id): bool
    {
        try {

            $info = LlAnalysis::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            if ($info->status != 3) {
                throw new \Exception("状态错误");
            }

            self::analysisCron($info->task_id);

            self::$returnData = $info->refresh()->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 详情
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-05 11:34:16
     */
    public static function analysisDetail(int $id): bool
    {
        try {

            $info = LlAnalysis::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            if ($info->status != 2) {
                throw new \Exception("状态错误");
            }

            //时长 end_time - start_tim
            $info->duration   = $info->end_time - $info->start_time;
            $info->start_time = date('Y-m-d H:i:s', $info->start_time);
            $info->end_time   = date('Y-m-d H:i:s', $info->end_time);

            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 删除
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function analysisDelete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                LlAnalysis::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                LlAnalysis::whereIn('id', $data['id'])->where('user_id', self::$uid)->seclect()->delete();
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 存入草稿
     * @param array $data
     * @return bool
     * @author Rick
     * @data 2024-06-09 11:05:46
     */
    public static function analysisAddDraft(array $data): bool
    {
        try {
            LlAnalysis::update(['is_draft' => 1], ['id' => $data['analysis_id']]);
            self::$returnData = ['message' => '操作成功'];
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 分析工作台
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function analysisWorkbench(): bool
    {
        try {
            // 已创建场景数
            $sceneCount = LlScene::withTrashed()->where('user_id', self::$uid)->count();

            // 总场景数
            $totalSceneCount = LlScene::where('user_id', self::$uid)->whereOr('user_id', 0)->count();

            // 已练习场景数
            $practiceSceneIds = LlAnalysis::where('user_id', self::$uid)->column('distinct scene_id');

            $practiceSceneCount = count($practiceSceneIds);

            //待练习场景数
            $pendingSceneCount = $totalSceneCount - $practiceSceneCount;

            // 平均分数
            $averageScore = bcadd(LlAnalysis::where('user_id', self::$uid)->avg('total_score'), 0, 2);

            //最新3个场景LOGO
            $sceneLogos = [];
            LlScene::whereNotIn('id', $practiceSceneIds)->order('id', 'desc')->limit(3)->field('logo')->select()->each(function ($item) use (&$sceneLogos) {
                $sceneLogos[] = FileService::getFileUrl($item['logo']);
            });

            self::$returnData = [
                'scene_count' => $sceneCount,
                'total_scene_count' => $totalSceneCount,
                'practice_scene_count' => $practiceSceneCount,
                'pending_scene_count' => $pendingSceneCount,
                'average_score' => $averageScore,
                'scene_logos' => $sceneLogos
            ];

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 场景添加
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function sceneAdd(array $data): bool
    {
        try {
            //随机从配置列表中提取
            $info =  ConfigService::get('lianlian', 'config', []);

            $data['logo'] = $info['avatars'][array_rand($info['avatars'])];

            $data['coach_language'] = '中文';
            $data['analysis_report_config'] = json_encode($info['directions'], JSON_UNESCAPED_UNICODE);
            $data['user_id'] = self::$uid;

            //
            $res =  LlScene::create($data)->toArray();
            $data['type'] = 2;
            \app\api\logic\KnowledgeLogic::bind($data, $res);

            self::$returnData = $res;
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 场景删除
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function sceneDelete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                LlScene::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                LlScene::whereIn('id', $data['id'])->where('user_id', self::$uid)->select()->delete();
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 场景编辑
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-05 11:05:46
     */
    public static function sceneEdit(array $data): bool
    {
        try {
            $info = LlScene::where('id', $data['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            $info->name                 = $data['name'] ?? $info->name;
            $info->description          = $data['description'] ?? $info->description;
            $info->coach_name           = $data['coach_name'] ?? $info->coach_name;
            $info->coach_persona        = $data['coach_persona'] ?? $info->coach_persona;
            $info->coach_voice          = $data['coach_voice'] ?? $info->coach_voice;
            $info->coach_emotion        = $data['coach_emotion'] ?? $info->coach_emotion;
            $info->coach_intensity     = $data['coach_intensity'] ?? $info->coach_intensity;
            $info->practitioner_persona = $data['practitioner_persona'] ?? $info->practitioner_persona;
            $info->save();

            $data['type'] = 2;
            \app\api\logic\KnowledgeLogic::bind($data, $info);

            self::$returnData = $info->toArray();

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 场景详情
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-05 11:34:16
     */
    public static function sceneDetail(int $id): bool
    {
        try {
            $info = LlScene::findOrEmpty($id);

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }
            $analysis = LlAnalysis::where([
                'scene_id' => $id,
                'user_id' => self::$uid,
                'status' => 0,
                'is_draft' => 1
            ])->findOrEmpty();
            $info['is_draft']       = $analysis->isEmpty() ? 0 : 1;
            $info['analysis_id']    = $analysis->id ?? 0;

            $info['index_id'] = \app\common\model\knowledge\KnowledgeBind::where('data_id', $info->id)->where('type', 2)->value('index_id');
            $info['knowledge'] = \app\common\model\knowledge\KnowledgeBind::alias('b')
                ->field('k.index_id, k.name, k.category_id, k.description, k.rerank_min_score, b.data_id, b.type')
                ->where('b.data_id', $id)
                ->join('knowledge k', 'k.index_id = b.index_id', 'LEFT')
                ->where('b.type', 2)
                ->limit(1)
                ->find();
            if ($info->user_id != 0 && $info->user_id != self::$uid) {
                throw new \Exception("查无此信息");
            }

            self::$returnData = $info->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 话术提炼
     * @param array $data
     * @return string
     * @author L
     * @data 2024/7/21 15:55
     */
    public static function speechcraftChat(array $data): string
    {
        try {

            $info = LlAnalysis::where('id', $data['analysis_id'])
                ->where('user_id', self::$uid)
                ->where('scene_id', $data['scene_id'])
                ->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            //不处于聊天状态
            if ($info->status != 0) {
                throw new \Exception("状态错误");
            }

            //获取场景信息
            $sceneInfo = LlScene::findOrEmpty($info->scene_id);

            if ($sceneInfo->isEmpty()) {
                throw new \Exception("场景信息丢失");
            }

            //获取当前聊天
            $chatInfo = LlChat::findOrEmpty($data['chat_id']);

            if ($chatInfo->isEmpty()) {
                throw new \Exception("聊天信息丢失");
            }

            //获取聊天记录
            $logs = json_encode(self::chatLog($info->id, $info->scene_id, $info->user_id, $data['chat_id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            // 获取话术提炼关键词
            $keyWords = ChatPrompt::where('id', 9)->value('prompt_text') ?? '';

            if (!$keyWords) {

                throw new \Exception("关键词丢失");
            }

            //替换数据
            $keyWords = str_replace(['我的身份', '场景名称', '对话内容'], [$sceneInfo->practitioner_persona, $sceneInfo->name, $logs], $keyWords);
            // 检查是否挂载知识库
            $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $data['scene_id'])->where('type', 2)->limit(1)->find();
            if (!empty($bind)) {
                $response = self::knowledgeChat($bind, $keyWords, $sceneInfo);
            } else if (isset($data['kb_id']) && (int)$data['kb_id'] > 0) {
                $response = self::vectorKnowledgeChat($data, $keyWords, $sceneInfo);
            } else {
                //发送聊天
                $response = \app\common\service\ToolsService::Ll()->chat([
                    'action'    => 'speechcraft',
                    'messages'  => $keyWords
                ]);
            }


            //聊天
            $chatInfo->speechcraft = $response['data']['message'];
            $chatInfo->save();

            self::$returnData = $chatInfo->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 表现提炼
     * @param array $data
     * @return string
     * @author L
     * @data 2024/7/21 15:55
     */
    public static function performanceChat(array $data): string
    {
        try {
            $info = LlAnalysis::where('id', $data['analysis_id'])
                ->where('user_id', self::$uid)
                ->where('scene_id', $data['scene_id'])
                ->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            //不处于聊天状态
            if ($info->status != 0) {
                throw new \Exception("状态错误");
            }

            //获取场景信息
            $sceneInfo = LlScene::findOrEmpty($info->scene_id);

            if ($sceneInfo->isEmpty()) {
                throw new \Exception("场景信息丢失");
            }

            //获取当前聊天
            $chatInfo = LlChat::findOrEmpty($data['chat_id']);

            if ($chatInfo->isEmpty()) {
                throw new \Exception("聊天信息丢失");
            }

            $logs = self::chatLog($info->id, $info->scene_id, $info->user_id, $data['chat_id']);

            if (count($logs) == 1) {

                throw new \Exception("当前处于开场白，请继续对话");
            }

            //移除最后一条(陪练者说的)
            array_pop($logs);

            //获取聊天记录
            $logs = json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            // 获取表现提炼关键词
            $keyWords = ChatPrompt::where('id', 11)->value('prompt_text') ?? '';

            if (!$keyWords) {

                throw new \Exception("关键词丢失");
            }

            //替换方向
            $keyWords = str_replace(['方向1', '方向2', '方向3', '方向4', '方向5'], $sceneInfo->analysis_report_config, $keyWords);

            //替换数据
            $keyWords = str_replace(['我的身份', '场景名称', '对话内容'], [$sceneInfo->practitioner_persona, $sceneInfo->name, $logs], $keyWords);

            //发送聊天
            $response = \app\common\service\ToolsService::Ll()->chat([
                'action'    => 'performance',
                'messages'  => $keyWords
            ]);


            //聊天
            $chatInfo->performance = $response['data']['message'];
            $chatInfo->save();

            self::$returnData = $chatInfo->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 开始聊天
     * @param array $data
     * @param int $userId
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/7/9 16:15
     */
    public static function startChat(array $data): bool
    {
        Db::startTrans();
        //计费，改为结算的时候扣费
        //        $unit = TokenLogService::checkToken(self::$uid, 'lianlian');
        try {
            // 场景ID
            if (!isset($data['scene_id'])) {
                throw new \Exception('场景ID不能为空');
            }

            $info = LlScene::findOrEmpty($data['scene_id']);

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            if ($info->user_id != 0 && $info->user_id != self::$uid) {
                throw new \Exception("查无此信息");
            }

            //场景重练或发起新对话时，删除无效的旧对话数据
            $drafts = LlAnalysis::where([
                'scene_id' => $data['scene_id'],
                'user_id'  => self::$uid,
                'is_draft' => 1,
                'status'   => 0
            ])->select()->toArray();
            if ($drafts) {
                foreach ($drafts as $draft) {
                    LlChat::destroy(['analysis_id' => $draft['id']]);
                    LlAnalysis::destroy($draft['id']);
                    //                    Log::write('场景ID：'.$data['scene_id'].'对话ID：'.$draft['id'].'删除成功');
                }
            }

            $task_id = generate_unique_task_id();
            //新增分析
            $analysisInfo = LlAnalysis::create([
                'scene_id'      => $data['scene_id'],
                'user_id'       => self::$uid,
                'task_id'       => $task_id,
                'start_time'    => time(),
            ]);

            // 获取开场白
            $keyWords = ChatPrompt::where('id', 6)->value('prompt_text') ?? '';

            if (!$keyWords) {

                throw new \Exception("关键词丢失");
            }

            //替换数据
            $keyWords = str_replace(['陪练画像描述', '陪练母语', '练习者扮演的人设', '练习场景'], [$info->coach_persona, $info->coach_language, $info->practitioner_persona, $info->description], $keyWords);


            // 检查是否挂载知识库
            $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $data['scene_id'])->where('type', 2)->limit(1)->find();
            if (!empty($bind)) {
                $response = self::knowledgeChat($bind, $keyWords, $info);
            } else if (isset($data['kb_id']) && (int)$data['kb_id'] > 0) {
                $response = self::vectorKnowledgeChat($data, $keyWords, $info);
            } else {
                // 发起聊天
                $response = \app\common\service\ToolsService::Ll()->chat([
                    'action'    => 'start',
                    'voice'     => $info->coach_voice,
                    'emotion'   => $info->coach_emotion,
                    'intensity' => $info->coach_intensity,
                    'messages'  => [
                        [
                            'role' => 'user',
                            'content' => $keyWords
                        ]
                    ],
                ]);
            }



            if (!isset($response['data'])) {

                throw new \Exception("发起场景陪练失败");
            }

            //保存数据
            $chatInfo = LlChat::create([
                'scene_id'                          => $analysisInfo->scene_id,
                'user_id'                           => $analysisInfo->user_id,
                'analysis_id'                       => $analysisInfo->id,
                'preliminary_ask'                   => $response['data']['message'] ?? '',
                'preliminary_ask_audio'             => FileService::downloadFileBySource($response['data']['audio_url'], 'audio'),
                'preliminary_ask_audio_duration'    => $response['data']['audio_duration'] ?? 0,
            ]);

            //token扣除，改为结算时扣费
            //            User::userTokensChange($analysisInfo->user_id, $unit);

            //记录日志，改为结算时扣费
            //            AccountLogLogic::recordUserTokensLog(true, $analysisInfo->user_id, AccountLogEnum::TOKENS_DEC_AI_LIANLIAN, $unit, $analysisInfo->task_id);

            self::$returnData = $chatInfo->toArray();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function flattenArray($array)
    {
        $result = [];
        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flattenArray($value));
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    private static function knowledgeChat($bind, $keyWords, $info)
    {
        $knowledge = \app\common\model\knowledge\Knowledge::where('id', $bind['kid'])->limit(1)->find();
        if (empty($knowledge)) {
            throw new \Exception("挂载的知识库不存在");
        }
        //clogger($keyWords, 'll');
        $knResponse = \app\api\logic\KnowledgeLogic::ladderPlayerChat([
            'message' => $keyWords,
            'indexid' => $knowledge['index_id'],
            'rerank_min_score' => $knowledge['rerank_min_score'] ?? 0.2,
            'stream' => false,
            'user_id' => self::$uid,
            'scene' => '陪练聊天',
            'voice'     => $info->coach_voice,
            'emotion'   => $info->coach_emotion,
            'intensity' => $info->coach_intensity,
        ]);
        $response['data'] = array(
            'message' => $knResponse['choices'][0]['message']['content'] ?? '',
            'audio_url' => $knResponse['audio_url'] ?? '',
            'audio_duration' => $knResponse['duration'] ?? 0,
        );
        return $response;
    }

    private static function vectorKnowledgeChat($payload, $keyWords, $info)
    {

        //clogger($keyWords, 'll');
        $knResponse = \app\api\logic\KnowledgeLogic::ladderPlayerVecTorChat([
            'message' => $keyWords,
            'stream' => false,
            'user_id' => self::$uid,
            'scene' => '向量知识库陪练聊天',
            'voice'     => $info->coach_voice,
            'emotion'   => $info->coach_emotion,
            'intensity' => $info->coach_intensity,
            'kb_id' => $payload['kb_id'],
        ]);
        $response['data'] = array(
            'message' => $knResponse['choices'][0]['message']['content'] ?? '',
            'audio_url' => $knResponse['audio_url'] ?? '',
            'audio_duration' => $knResponse['duration'] ?? 0,
        );
        return $response;
    }

    /**
     * 继续聊天
     * @param array $data
     * @param int $userId
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/7/9 16:15
     */
    public static function continueChat(array $data): bool
    {
        Db::startTrans();
        try {
            $info = LlAnalysis::where('is_draft', 1)
                ->where('id', $data['analysis_id'])
                ->where('user_id', self::$uid)
                ->where('scene_id', $data['scene_id'])
                ->order('id', 'DESC')
                ->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            //不处于聊天状态
            if ($info->status != 0) {
                throw new \Exception("状态错误");
            }

            //获取场景信息
            $sceneInfo = LlScene::findOrEmpty($info->scene_id);

            if ($sceneInfo->isEmpty()) {
                throw new \Exception("场景信息丢失");
            }

            //保存数据
            $chatInfo = LlChat::create([
                'scene_id'              => $info->scene_id,
                'user_id'               => $info->user_id,
                'analysis_id'           => $info->id,
                'ask'                   => $data['ask'],
                'ask_audio'             => FileService::setFileUrl($data['ask_audio']),
                'ask_audio_duration'    => $data['ask_audio_duration'],
            ]);

            //获取聊天记录
            $logs = json_encode(self::chatLog($info->id, $info->scene_id, self::$uid), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            // 对话延续关键词
            $keyWords = ChatPrompt::where('id', 10)->value('prompt_text') ?? '';

            if (!$keyWords) {

                throw new \Exception("关键词丢失");
            }

            //替换数据
            $keyWords = str_replace(['场景名称', '对话内容', '陪练母语'], [$sceneInfo->name, $logs, $sceneInfo->coach_language], $keyWords);

            // 检查是否挂载知识库
            $bind = \app\common\model\knowledge\KnowledgeBind::where('data_id', $data['scene_id'])->where('type', 2)->limit(1)->find();
            if (!empty($bind)) {
                $response = self::knowledgeChat($bind, $keyWords, $sceneInfo);
            } else if (isset($data['kb_id']) && (int)$data['kb_id'] > 0) {
                $response = self::vectorKnowledgeChat($data, $keyWords, $sceneInfo);
            } else {
                // 发起聊天
                $response = \app\common\service\ToolsService::Ll()->chat([
                    'action'    => 'continue',
                    'voice'     => $sceneInfo->coach_voice,
                    'emotion'   => $sceneInfo->coach_emotion,
                    'intensity' => $sceneInfo->coach_intensity,
                    'messages'  => [
                        [
                            'role' => 'user',
                            'content' => $keyWords
                        ]
                    ],
                ]);
            }


            if (!isset($response['data'])) {

                throw new \Exception("陪练对话失败");
            }

            $chatInfo->reply                    = $response['data']['message'];
            $chatInfo->reply_audio              = FileService::downloadFileBySource($response['data']['audio_url'], 'audio');
            $chatInfo->reply_audio_duration     = $response['data']['audio_duration'];
            $chatInfo->save();

            self::$returnData = $chatInfo->toArray();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 继续聊天
     * @param string $audio
     * @param int $userId
     * @return bool
     * @throws \Exception
     * @author L    
     * @data 2024/7/9 16:15
     */
    public static function chatSTT(string $audio): bool
    {
        try {
            // 发起聊天
            $response = \app\common\service\ToolsService::Ll()->stt([
                'audio_url' => $audio,
            ]);

            if (!isset($response['data'])) {
                throw new \Exception("语音识别失败，无人声或不清晰");
            }

            if (empty($response['data']['message'])){
                throw new \Exception("语音识别失败，无人声或不清晰");
            }

            self::$returnData = ['message' => $response['data']['message'], 'audio_duration' => $response['data']['audio_duration']];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取聊天记录
     * @param int $analysisId
     * @param int $sceneId
     * @param int $userId
     * @return array
     */
    public static function chatLog(int $analysisId, int $sceneId, int $userId, int $chatId = 0): array
    {
        // 获取开场白
        $keyWords = ChatPrompt::where('id', 6)->value('prompt_text') ?? '';

        if (!$keyWords) {

            throw new \Exception("关键词丢失");
        }

        $logs = [];
        LlChat::where('analysis_id', $analysisId)
            ->where('scene_id', $sceneId)
            ->where('user_id', $userId)
            ->when($chatId != 0, function ($query) use ($chatId) {
                $query->where('id', '<=', $chatId);
            })
            ->order('id', 'asc')
            ->select()
            ->each(function ($item, $key) use (&$logs, $keyWords) {

                if ($key == 0) {
                    // 获取助理提示词
                    $info = LlScene::field(['coach_persona', 'coach_language', 'practitioner_persona', 'description'])->findOrEmpty($item['scene_id']);
                    //替换数据
                    $keyWords = str_replace(['陪练画像描述', '陪练母语', '练习者扮演的人设', '练习场景'], [$info->coach_persona, $info->coach_language, $info->practitioner_persona, $info->description], $keyWords);

                    $logs[] = [
                        'role' => 'user',
                        'content' => $keyWords
                    ];

                    $logs[] = [
                        'role' => 'assistant',
                        'content' => $item->preliminary_ask
                    ];
                } else {
                    $logs[] = [
                        'role' => 'user',
                        'content' => $item->ask
                    ];

                    if (!empty($item->reply)) {
                        $logs[] = [
                            'role' => 'assistant',
                            'content' => $item->reply
                        ];
                    }
                }
            });
        return $logs;
    }


    /**
     * 结束聊天
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/7/11 9:47
     */
    public static function endChat(array $data): bool
    {
        try {
            //计费，改为结算的时候扣费
            $unit = TokenLogService::checkToken(self::$uid, 'lianlian');
            $info = LlAnalysis::where('id', $data['analysis_id'])
                ->where('user_id', self::$uid)
                ->where('scene_id', $data['scene_id'])
                ->findOrEmpty();

            if ($info->isEmpty()) {
                throw new \Exception("查无此信息");
            }

            //不处于聊天状态
            if ($info->status != 0) {
                throw new \Exception("状态错误");
            }

            //陪练结束，中台扣费
            $response = \app\common\service\ToolsService::Ll()->chat([
                'action'    => 'end',
            ]);
            if (!isset($response['data'])) {

                throw new \Exception("陪练结束失败");
            }

            $info->status   = 1;
            $info->is_draft = 0;
            $info->end_time = time();
            $info->save();

            self::$returnData = $info->toArray();
            //token扣除
            User::userTokensChange($info->user_id, $unit);

            //记录日志
            AccountLogLogic::recordUserTokensLog(true, $info->user_id, AccountLogEnum::TOKENS_DEC_AI_LIANLIAN, $unit, $info->task_id);

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * @desc 视频定时任务
     * @return bool|void
     * @date 2024/10/2 10:36
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public static function analysisCron(string $taskId = '')
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        try {
            if ($taskId == '') {
                //请求12小时内，还处于分析中状态的任务
                LlAnalysis::where('status', 1)
                    ->where('create_time', '<=', strtotime('-12 hours'))
                    ->select()
                    ->each(function ($item) {
                        $item->status = 3;
                        $item->remark = '执行超时';
                        $item->save();
                    });
            }

            // 第一步：获取任务列表
            $taskModel = LlAnalysis::where('tries', '<', 3)
                ->order('id', 'asc')
                ->limit(3);

            if ($taskId) {

                $taskModel = $taskModel->where('task_id', $taskId)->where('status', 3);
            } else {

                $taskModel = $taskModel->where('status', 1);
            }

            //第二步遍历任务
            $taskModel->select()->each(function ($item) {

                //如果存在失败，且还没到执行时间（1分钟后执行）
                if ($item->tries >= 1 && strtotime($item->update_time) > strtotime('-1 minute')) {
                    return true;
                }

                try {
                    $tries = $item->tries;

                    //分析模块

                    //获取场景信息
                    $sceneInfo = LlScene::findOrEmpty($item->scene_id);

                    if ($sceneInfo->isEmpty()) {

                        $item->status = 3;
                        $item->remark = '场景信息丢失';
                        $item->save();
                        return true;
                    }

                    //获取聊天记录
                    $logs = self::chatLog($item->id, $item->scene_id, $item->user_id);

                    if (!$logs) {

                        $item->status = 3;
                        $item->remark = '聊天记录丢失';
                        $item->save();
                        return true;
                    }

                    $logs = json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

                    if (!$item->model_response) {

                        // 获取模块分析关键词
                        $keyWords = ChatPrompt::where('id', 8)->value('prompt_text') ?? '';

                        if (!$keyWords) {

                            $item->status = 3;
                            $item->remark = '模块分析关键词丢失';
                            $item->save();
                            return true;
                        }

                        //替换方向
                        $keyWords = str_replace(['方向1', '方向2', '方向3', '方向4', '方向5'], $sceneInfo->analysis_report_config, $keyWords);

                        //替换数据
                        $keyWords = str_replace(['场景名称', '对话内容'], [$sceneInfo->name, $logs], $keyWords);

                        // 发起聊天
                        $response = \app\common\service\ToolsService::Ll()->chat([
                            'action'    => 'analysis',
                            'messages'  => [
                                [
                                    'role' => 'user',
                                    'content' => $keyWords
                                ]
                            ],
                        ]);

                        if (!isset($response['data'])) {

                            if ($item->tries >= 3) {
                                $item->status = 3;
                                $item->remark = '模块分析失败';
                                $item->save();
                                return true;
                            }

                            $item->tries  = $item->tries + 1;
                            $item->save();
                            return true;
                        }

                        //总分数
                        $totalScore = array_sum(array_column($response['data']['message'], 'score'));

                        $item->model_response = json_encode($response['data']['message'], JSON_UNESCAPED_UNICODE);
                        $item->total_score    = $totalScore;
                        $item->save();
                    }

                    //总分析
                    if (!$item->total_response) {

                        // 获取总体分析关键词
                        $keyWords = ChatPrompt::where('id', 7)->value('prompt_text') ?? '';

                        if (!$keyWords) {

                            $item->status = 3;
                            $item->remark = '总体分析关键词丢失';
                            $item->save();
                            return true;
                        }

                        //替换方向
                        $keyWords = str_replace(['方向1', '方向2', '方向3', '方向4', '方向5'], $sceneInfo->analysis_report_config, $keyWords);

                        //替换数据
                        $keyWords = str_replace(['场景名称', '对话内容'], [$sceneInfo->name, $logs], $keyWords);

                        // 发起聊天
                        $response = \app\common\service\ToolsService::Ll()->chat([
                            'action'    => 'analysis',
                            'messages'  => [
                                [
                                    'role' => 'user',
                                    'content' => $keyWords
                                ]
                            ],
                        ]);

                        if (!isset($response['data'])) {

                            if ($item->tries >= 3) {
                                $item->status = 3;
                                $item->remark = '总体分析失败';
                                $item->save();
                                return true;
                            }

                            $item->tries  = $item->tries + 1;
                            $item->save();
                            return true;
                        }

                        $item->total_response = $response['data']['message'];
                        $item->save();
                    }
                    $item->status = 2;
                    $item->remark = '分析完成';
                    $item->save();
                    return true;
                } catch (\think\exception\HttpResponseException $e) {
                    $item->tries = $tries + 1;
                    $item->save();
                    self::setError($e->getResponse()->getData()['msg'] ?? '提交任务出错');
                    return true;
                }
            });
        } catch (\Exception $e) {
            //TODO
            self::setError($e->getMessage());
            return false;
        }
        return true;
    }
}
