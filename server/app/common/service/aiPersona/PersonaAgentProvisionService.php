<?php

namespace app\common\service\aiPersona;

use app\api\logic\kb\KbRobotLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\knowledge\Knowledge;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\MemberService;
use app\common\service\ToolsService;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 人设报告生成后自动开通四个场景智能体。
 *
 * 前台自建人设与后台代客管理人设原本各抄了一份实现，后台那份调用的是 adminapi 下的
 * KbRobotLogic，建出来的实体 user_id 写死 0、team_id 也默认 0，正好落进智能体列表的
 * 「站点预置」分支（user_id=0 且 team_id=0），对全站每个用户可见——而这些实体的
 * roles_prompt 由该客户的人设报告生成，等于把客户经营信息发布给了所有人。
 *
 * 现统一走本服务：一份实现、api 侧 KbRobotLogic，实体归属人设所属用户，
 * team_id 由 TeamContextService 按该用户的企业空间解析（资源跟人）。
 */
class PersonaAgentProvisionService
{
    /** 智能体来源：用户手工创建 */
    public const ORIGIN_MANUAL = 1;
    /** 智能体来源：人设自动开通 */
    public const ORIGIN_PERSONA = 2;

    /** 名称里人设名的截断长度，kb_robot.name 只有 varchar(100) */
    private const PERSONA_NAME_MAX = 60;

    /**
     * 场景顺序即 AiPersonaAgentConfig::syncAutoCreatedAgentConfig 的下标约定：
     * 0 评论 / 1 私信 / 2 朋友圈 / 3 微信私聊，调整顺序会导致绑定错位。
     */
    private const SCENES = [
        ['key' => 'Comment_agent', 'name' => '社媒评论区智能体', 'short' => '评论区'],
        ['key' => 'Reply_agent', 'name' => '社媒私信智能体', 'short' => '私信'],
        ['key' => 'Wechat_Moment_agent', 'name' => '微信朋友圈互动智能体', 'short' => '朋友圈'],
        ['key' => 'Wechat_agent', 'name' => '微信私聊智能体', 'short' => '微信私聊'],
    ];

    private static string $error = '';

    public static function getError(): string
    {
        return self::$error;
    }

    /**
     * @param array $params ['Content' => 报告结果, 'Model' => 人设类型名]
     * @param int   $personaId 人设id
     * @param int   $userId 人设所属用户，决定智能体归属，不可传后台管理员id
     */
    public static function provision(array $params, int $personaId, int $userId): bool
    {
        self::$error = '';
        $userId      = (int)$userId;
        $personaId   = (int)$personaId;
        $createdIds  = [];

        // 归属缺失时宁可不建：建出来就是 user_id=0 的全站可见实体
        if ($userId <= 0) {
            self::$error = '人设归属用户不存在，已跳过智能体开通';
            Log::channel('ipPersona')->write('自动创建智能体跳过：persona_id=' . $personaId . ' 归属用户为空');
            return false;
        }

        try {
            $params['Content'] = json_encode($params['Content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $res               = self::flowRequest($params);
            if (empty($res)) {
                return true;
            }

            $modelPair = MemberService::pickRandomChatModelPair($userId);
            if ($modelPair === null) {
                self::$error = '当前等级暂无可用对话模型，请联系站长开启';
                return false;
            }

            $personaName = (string)AiPersona::where('id', $personaId)->value('persona_name');
            // 重新生成报告时复用上一轮已绑定的智能体，否则每次都新建一批，旧的既不解绑也不删除
            $reusableIds = self::resolveReusableRobotIds($userId, $personaId);

            $ids = [];
            foreach (self::SCENES as $index => $scene) {
                $robotId  = (int)($reusableIds[$index] ?? 0);
                $isReused = $robotId > 0;
                $existing = null;

                if ($isReused) {
                    $existing = KbRobot::where('id', $robotId)->findOrEmpty();
                } else {
                    $robot = KbRobotLogic::add([
                        'context_num'  => 3,
                        'kb_type'      => 2,
                        'quota_exempt' => 1,
                    ], $userId);
                    if ($robot === false || empty($robot['id'])) {
                        self::rollbackCreatedRobots($createdIds, $userId);
                        self::$error = KbRobotLogic::getError() ?: '创建智能体失败';
                        return false;
                    }
                    $robotId      = (int)$robot['id'];
                    $createdIds[] = $robotId;
                }
                $ids[$index] = $robotId;

                $robotEdit = self::buildRobotEditData(
                    $robotId,
                    self::buildAgentName($personaName, $scene, (string)($params['Model'] ?? '')),
                    (string)($res[$scene['key']] ?? ''),
                    $modelPair,
                    $existing
                );
                if (!KbRobotLogic::edit($robotEdit, $userId)) {
                    self::rollbackCreatedRobots($createdIds, $userId);
                    self::$error = KbRobotLogic::getError() ?: '配置智能体失败';
                    return false;
                }

                KbRobot::where('id', $robotId)->update([
                    'persona_id'  => $personaId,
                    'origin'      => self::ORIGIN_PERSONA,
                    'update_time' => time(),
                ]);
            }

            AiPersonaAgentConfig::syncAutoCreatedAgentConfig($userId, $personaId, $ids);
            return true;
        } catch (\Throwable $e) {
            self::rollbackCreatedRobots($createdIds, $userId);
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * 取该人设上一轮已绑定、且仍可编辑的智能体，下标与 SCENES 对齐。
     * 智能体被删除、被转让、或已禁用（edit 会拒绝）时不复用，走新建。
     *
     * @return array<int,int>
     */
    private static function resolveReusableRobotIds(int $userId, int $personaId): array
    {
        $config = AiPersonaAgentConfig::where([
            'user_id'    => $userId,
            'persona_id' => $personaId,
        ])->findOrEmpty();
        if ($config->isEmpty()) {
            return [];
        }

        $bound = [
            0 => (int)$config->comment_agent_id,
            1 => (int)$config->dm_agent_id,
            2 => (int)$config->moments_agent_id,
            3 => (int)$config->wechat_chat_agent_id,
        ];

        $reusable = [];
        foreach ($bound as $index => $robotId) {
            if ($robotId <= 0) {
                continue;
            }
            $robot = KbRobot::where('id', $robotId)->field('id,user_id,is_enable')->findOrEmpty();
            if ($robot->isEmpty() || (int)$robot->user_id !== $userId || !$robot->is_enable) {
                continue;
            }
            $reusable[$index] = $robotId;
        }
        return $reusable;
    }

    /**
     * 人设名 + 场景短名，同类型多个人设的智能体才分得清。
     * 人设名为空时回落到人设类型前缀，与历史命名保持一致。
     */
    private static function buildAgentName(string $personaName, array $scene, string $typeName): string
    {
        $personaName = trim($personaName);
        if ($personaName === '') {
            return $typeName . ' - ' . $scene['name'];
        }
        return mb_substr($personaName, 0, self::PERSONA_NAME_MAX) . ' · ' . $scene['short'];
    }

    /**
     * 回滚本轮自动创建失败留下的智能体空壳，避免「默认助理」无提示词残留。
     * 只回滚本轮新建的，复用到的历史智能体不动。
     */
    private static function rollbackCreatedRobots(array $robotIds, int $userId): void
    {
        foreach ($robotIds as $robotId) {
            $robotId = (int)$robotId;
            if ($robotId <= 0) {
                continue;
            }
            try {
                KbRobotLogic::del($robotId, $userId);
            } catch (\Throwable $e) {
                Log::channel('ipPersona')->write('回滚自动创建智能体失败 id=' . $robotId . ' err=' . $e->getMessage());
            }
        }
    }

    /**
     * 复用已有智能体时，知识库绑定与自定义菜单沿用原值：
     * KbRobotLogic::edit 收到空 kb_ids 会解绑知识库、收到数组 menus 会清空菜单，
     * 按新建预设整包覆盖会把用户在这个智能体上的配置抹掉。其余参数仍回到预设。
     */
    private static function buildRobotEditData(
        int $robotId,
        string $name,
        string $rolesPrompt,
        array $modelPair,
        ?KbRobot $existing = null
    ): array {
        $kbType = 2;
        $kbIds  = [];
        $menus  = [];
        if ($existing !== null && !$existing->isEmpty()) {
            $kbType = (int)($existing->kb_type ?: 2);
            $kbIds  = self::filterUsableKbIds($kbType, explode(',', (string)$existing->kb_ids));
            $menus  = null; // null 时 edit 跳过菜单处理，保留原有自定义菜单
        }

        return [
            'id'                 => $robotId,
            'roles_prompt'       => $rolesPrompt,
            'kb_type'            => $kbType,
            'kb_ids'             => $kbIds,
            'icons'              => '',
            'image'              => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')) ?? '',
            'bg_image'           => '',
            'name'               => $name,
            'intro'              => '默认助理简介',
            'model_id'           => $modelPair['model_id'],
            'model_sub_id'       => $modelPair['model_sub_id'],
            'search_mode'        => 'similar',
            'search_tokens'      => 3000,
            'search_similar'     => 0.5,
            'ranking_status'     => 0,
            'ranking_score'      => 0.5,
            'context_num'        => 3,
            'is_public'          => 0,
            'is_enable'          => 1,
            'optimize_ask'       => 0,
            'optimize_m_id'      => '',
            'optimize_s_id'      => '',
            'search_empty_type'  => 1,
            'search_empty_text'  => '',
            'top_p'              => 0.8,
            'temperature'        => 0.3,
            'presence_penalty'   => 0,
            'frequency_penalty'  => 0,
            'logprobs'           => 0,
            'top_logprobs'       => 0,
            'welcome_introducer' => '',
            'copyright'          => '',
            'menus'              => $menus,
            'flow_status'        => 0,
            'flow_config'        => [
                'workflow_id' => '',
                'bot_id'      => '',
                'app_id'      => '',
                'api_token'   => '',
            ],
            'threshold'          => 0.7,
            'mode_type'          => 3,
            'max_tokens'         => 4096,
        ];
    }

    /**
     * 知识库可能已被删除或禁用，带着失效 id 走 edit 会整体抛错导致这轮开通失败，
     * 这里只把仍然可用的绑定带回去。
     *
     * @param array $kbIds
     * @return array<int,int>
     */
    private static function filterUsableKbIds(int $kbType, array $kbIds): array
    {
        $kbIds = array_values(array_unique(array_filter(array_map('intval', $kbIds))));
        if (empty($kbIds)) {
            return [];
        }
        $usable = $kbType === 1
            ? Knowledge::whereIn('id', $kbIds)->where('status', 1)->column('id')
            : KbKnow::whereIn('id', $kbIds)->where('is_enable', 1)->column('id');

        return array_values(array_map('intval', $usable));
    }

    /**
     * 自动创建智能体的工作流请求
     */
    private static function flowRequest(array $params): array
    {
        $automationService = ToolsService::AiPersona();
        $url               = $automationService::URL;
        $workflowId        = $automationService::AGENT_WORKFLOW_ID;
        Log::channel('ipPersona')->write(
            '自动创建智能体请求：' . json_encode([
                'url' => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $body     = [
            'workflow_id' => $workflowId,
            'parameters'  => $params,
        ];
        $request  = [
            'headers' => [
                'Authorization' => 'Bearer ' . $automationService::TOKEN,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $body,
        ];
        $client   = new Client(['timeout' => 600, 'verify' => false]);
        $rsp      = $client->post($url, $request);
        $contents = $rsp->getBody()->getContents();
        $data     = json_decode($contents, true);
        Log::channel('ipPersona')->write('自动创建智能体请求结果' . $contents);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (($data['code'] ?? -1) !== 0) {
            return [];
        }
        $data['data'] = json_decode($data['data'], true);
        if (!empty($data['data'])) {
            return $data['data'];
        }
        return [];
    }
}
