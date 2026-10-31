<?php

declare(strict_types=1);

namespace app\api\logic\sora;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\user\User;
use app\common\service\copywriting\VideoPromptService;

/**
 * 一句话生成视频 · AI 帮写（P0-b）
 *
 * 只做三件事：校验入参、按 coze_copywriting 场景扣费、把提示词交回去。
 * 提示词本身由 VideoPromptService 产出（system prompt 在仓库里，可版本化）。
 *
 * 计费口径与旧的 /kb.robot/getCopywriting 完全一致：
 * 同场景、同枚举、同「失败不扣费」的顺序 —— 只是换了模型与提示词，不动价格。
 * 多条（number）是 N 次独立模型调用，所以按 N × 单价扣；任一条失败整单不扣。
 */
class SoraPromptLogic extends ApiLogic
{
    /** 单次最多几条：每条都是一次 3000-token 级调用，串行 3 条约 15~30s，再多就是白等 */
    private const MAX_NUMBER = 3;

    /** 比例白名单（与前端 VIDEO_PROPORTIONS 一致） */
    private const ASPECT_RATIOS = ['16:9', '4:3', '1:1', '3:4', '9:16', '21:9'];

    /** 用户输入上限（与前端 MAX_DESC_LENGTH 一致） */
    private const MAX_CONTENT_LENGTH = 1000;

    /** 台词语言白名单 */
    private const LANGUAGES = ['zh', 'en', 'id', 'th', 'vi'];

    /**
     * @param array<string, mixed> $params
     */
    public static function prompt(array $params): bool
    {
        try {
            $content      = trim((string)($params['content'] ?? ''));
            $duration     = (int)($params['duration'] ?? 5);
            $aspectRatio  = (string)($params['aspect_ratio'] ?? '9:16');
            $mode         = (string)($params['mode'] ?? 'commerce');
            $language     = (string)($params['language'] ?? 'zh');
            $personaId    = (int)($params['persona_id'] ?? 0);
            $number       = (int)($params['number'] ?? 1);

            if ($content === '') {
                throw new \Exception('请输入您的想法');
            }
            if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
                throw new \Exception('想法最多 ' . self::MAX_CONTENT_LENGTH . ' 字');
            }
            if ($duration < 4 || $duration > 15) {
                throw new \Exception('视频时长需在 4~15 秒之间');
            }
            if (!in_array($aspectRatio, self::ASPECT_RATIOS, true)) {
                throw new \Exception('画面比例不支持');
            }
            if (!in_array($mode, ['commerce', 'creative'], true)) {
                throw new \Exception('模式参数错误');
            }
            if (!in_array($language, self::LANGUAGES, true)) {
                throw new \Exception('台词语言不支持');
            }
            if ($number < 1 || $number > self::MAX_NUMBER) {
                throw new \Exception('生成条数需在 1~' . self::MAX_NUMBER . ' 之间');
            }

            // 人设归属/存在性在扣费之前就校验掉：绑了一个不属于自己或已下架的人设，
            // 不该让用户先扣算力再收「人设不存在」。
            $personaContext = self::resolvePersonaContext($personaId, self::$uid);

            // 余额不足在这里抛出，模型调用还没发生 —— 与旧链路同序，不产生「扣了钱没结果」
            $unit = TokenLogService::checkToken(self::$uid, 'coze_copywriting', $number);

            $payload = [
                'content'          => $content,
                'duration'         => $duration,
                'aspect_ratio'     => $aspectRatio,
                'image_count'      => (int)($params['image_count'] ?? 0),
                'video_count'      => (int)($params['video_count'] ?? 0),
                'anchor_ids'       => (array)($params['anchor_ids'] ?? []),
                'first_last_frame' => (int)($params['first_last_frame'] ?? 0),
                'mode'             => $mode,
                'language'         => $language,
                'persona_context'  => $personaContext,
            ];

            // 逐条生成完再扣费：第 2 条失败时第 1 条已经烧了模型调用，
            // 但「只为成功的部分计费」会让前端拿到半套结果还得解释怎么退，
            // 所以整单失败直接不扣 —— 与旧的单条链路保持同一种心智。
            $list = [];
            for ($i = 0; $i < $number; $i++) {
                $list[] = VideoPromptService::generate($payload);
            }

            $points = round($number * $unit, 2);
            if ($points > 0) {
                $taskId = generate_unique_task_id();
                User::userTokensChange(self::$uid, $points);
                $extra = [
                    '生成条数'    => $number,
                    '算力单价'    => $unit,
                    '实际消耗算力' => $points,
                ];
                AccountLogLogic::recordUserTokensLog(
                    true,
                    self::$uid,
                    AccountLogEnum::TOKENS_DEC_COZE_COPYWRITING,
                    $points,
                    $taskId,
                    $extra
                );
            }

            $messages = array_map(static fn(array $item): string => (string)$item['content'], $list);
            $titles   = array_map(static fn(array $item): string => (string)($item['title'] ?? ''), $list);

            self::$returnData = [
                // message 保持单条口径：小程序只读这一个字段，多条时取第一条
                'message' => $messages[0] ?? '',
                'list'    => $messages,
                // 标题与 list 一一对应，前端「使用提示词」时顺手填进视频名称
                'titles'  => $titles,
                'meta'    => $list[0]['meta'] ?? [],
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 取人设语料。persona_id=0 返回空串（未绑定），绑定但不可用时抛异常。
     *
     * 三型扩展表各有一套字段，但 getClueContent($persona) 已经把差异吃掉，
     * 与 KbRobotLogic::getCopywriting() 第 1102-1167 行同一口径。
     */
    private static function resolvePersonaContext(int $personaId, int $userId): string
    {
        if ($personaId <= 0) {
            return '';
        }
        $persona = AiPersona::where('id', $personaId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->find();
        if (!$persona) {
            throw new \Exception('人设不存在或已停用');
        }

        $model = match ((int)$persona->persona_type) {
            1 => AiPersonaIndividual::class,
            2 => AiPersonaEnterprise::class,
            3 => AiPersonaLocal::class,
            default => null,
        };
        if ($model === null) {
            throw new \Exception('人设类型不支持');
        }
        $detail = $model::where('user_id', $userId)->where('persona_id', $persona->id)->find();
        if (!$detail) {
            throw new \Exception('人设资料不完整，请先补全人设信息');
        }

        return VideoPromptService::normalizePersona((string)$detail->getClueContent($persona));
    }
}
