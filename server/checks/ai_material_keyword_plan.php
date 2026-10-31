<?php
/**
 * AI 找素材关键词规划 + 相关性闸门回归脚本（纯本地，不调中台）。
 * 运行：php checks/ai_material_keyword_plan.php
 *
 * 覆盖截图中「手动爆款复刻关键词找素材不搭」的 8 条场景：
 * - A. 关键词可检索化（纯规则兜底）：情绪词、泛化后缀、修饰前缀
 * - B. 文本闸门比例/命中数：不符场景应拒绝，勉强可接受场景应放行
 * - C. 画面闸门提示词：必须按「画面主体就是」判定，并排除品牌门头/近似品类
 * - D. 候选择优：同一批结果里最贴合的排第一，不再首条即用
 * - E. 规划器与拍平：保留句序、主查询优先、备选词去重
 */
require __DIR__ . '/../vendor/autoload.php';

use app\api\logic\videoImitation\TaskLogic;
use app\common\service\AiMaterialRelevanceService;
use app\common\service\MaterialKeywordPlanner;
use think\facade\Config;

$app = new \think\App();
$app->initialize();

$failed = 0;
$passed = 0;
$check = static function (bool $ok, string $msg) use (&$failed, &$passed): void {
    if ($ok) {
        $passed++;
        echo "PASS {$msg}\n";
        return;
    }
    $failed++;
    echo "FAIL {$msg}\n";
};

$originProjectConfig = Config::get('project');

// —— A. 关键词可检索化（规则兜底）—— //
$rulesCases = [
    // [原始关键词, 期望主查询, 期望主查询不含的词, 备选词必须包含]
    ['温馨街边小店', '街边小店', '温馨', null],
    ['一人食场景', '一人食', '场景', null],
    ['温馨的街边小店', '街边小店', '温馨', null],
    ['高级感婚礼场景', '婚礼', '高级感', null],
    ['焦边煎牛肉饼', '煎牛肉饼', '焦边', '牛肉饼'],
    ['网红小店', '小店', '网红', null],
    ['红咖喱酱', '红咖喱酱', '', null],
    ['wooden door sample', 'wooden door sample', '', null], // 英文保留空格与连字符
    ['朋友简餐聚会', '朋友简餐聚会', '', null], // 无连接词时规则不拆，交给模型改写
];
foreach ($rulesCases as [$keyword, $primary, $forbidden, $alternate]) {
    $rewritten = MaterialKeywordPlanner::rewriteByRules($keyword);
    $ok = $rewritten['primary'] === $primary && $forbidden !== ''
        ? ($rewritten['primary'] === $primary && mb_strpos($rewritten['primary'], $forbidden, 0, 'UTF-8') === false)
        : ($rewritten['primary'] === $primary);
    if ($alternate !== null) {
        $ok = $ok && in_array($alternate, $rewritten['alternates'], true);
    }
    $check($ok, "A 规则改写「{$keyword}」=>「{$rewritten['primary']}」备选=" . implode('/', $rewritten['alternates']));
}

// —— B. 文本闸门 —— //
Config::set([
    'ai_material_relevance' => [
        'enabled' => true,
        'min_hits' => 2,
        'min_ratio' => 0.4,
        'min_consecutive' => 0,
        'vision_enabled' => false,
        'vision_timeout' => 8,
    ],
], 'project');

$textCases = [
    // [关键词, 候选标题, 期望, 说明]
    ['温馨街边小店', '711便利店街边夜景门头', false, '截图不符场景：便利店门头只有「街边」一处交集'],
    ['温馨街边小店', '街边小店暖光招牌', true, '截图期望场景：主体就是街边小店'],
    ['朋友简餐聚会', '黑白男子用餐特写', false, '截图不符场景：男子用餐特写零交集'],
    ['朋友简餐聚会', '朋友聚会西式餐盘', true, '勉强可接受：聚会场景命中两个二元组'],
    ['一人食场景', '一碗沙拉摆盘', false, '截图不符场景：一碗沙拉零交集'],
    ['一人食场景', '一人食沙拉摆盘', true, '勉强可接受：命中「一人」「人食」'],
    ['红咖喱酱', '红咖喱块', true, '截图符合场景：主体品类一致'],
    ['焦边煎牛肉饼', '手撕肉饼', false, '截图不符场景：肉饼近似品类但主体词不足'],
    ['焦边煎牛肉饼', '煎牛肉饼特写', true, '主体词命中三个二元组'],
    ['木门样品', '木门展示厅', false, '只有单个二元组交集，按新阈值拒绝'],
    ['木门样品', '实木门样品图', true, '命中三个二元组放行'],
    ['木门样品', '', true, '无标题证据不足放行'],
];
foreach ($textCases as [$keyword, $title, $expect, $msg]) {
    $check(AiMaterialRelevanceService::passes(['title' => $title], $keyword) === $expect, 'B ' . $msg);
}
$check(
    AiMaterialRelevanceService::passes([], '木门样品') === true,
    'B 字段缺失证据不足放行'
);

// —— C. 画面闸门提示词 —— //
$question = AiMaterialRelevanceService::visualQuestion('温馨街边小店');
$check(strpos($question, '就是') !== false, 'C1 提示词要求「画面主体就是关键词」');
$check(strpos($question, '品牌') !== false, 'C2 提示词排除未指定品牌的品牌门头/logo');
$check(strpos($question, '相似品类') !== false, 'C3 提示词排除同行业近似品类');
$check(strpos($question, '只回答') !== false, 'C4 仍要求模型只答是/否');

// —— D. 候选择优 —— //
$rankKeyword = '温馨街边小店';
$rankList = [
    ['title' => '711便利店街边夜景', 'image' => 'http://example.com/a.jpg', 'duration' => 8, 'link' => 'a'],
    ['title' => '街边小店暖光招牌', 'image' => 'http://example.com/b.jpg', 'duration' => 8, 'link' => 'b'],
    ['title' => '手链饰品展示', 'image' => '', 'duration' => 6, 'link' => 'c'],
];
$ranked = TaskLogic::rankWashStrictCandidates($rankList, 'video', $rankKeyword);
$check(($ranked[0]['title'] ?? '') === '街边小店暖光招牌', 'D1 最贴合的候选排到第一位');
$check(($ranked[count($ranked) - 1]['title'] ?? '') === '手链饰品展示', 'D2 零交集候选排在最后');
$check(
    AiMaterialRelevanceService::candidateScore($rankList[1], $rankKeyword, 'video')
    > AiMaterialRelevanceService::candidateScore($rankList[0], $rankKeyword, 'video'),
    'D3 贴合候选分数高于字面近邻'
);

// —— E. 规划器与拍平（关闭模型调用，验证规则路径与句序）—— //
Config::set([
    'material_keyword_plan' => [
        'enabled' => false,
        'model' => '',
        'timeout' => 10,
        'max_alternates' => 1,
        'max_keywords' => 30,
    ],
], 'project');

$planText = '今天去街边小店吃饭。朋友简餐聚会很热闹。最后吃红咖喱酱。';
$plan = MaterialKeywordPlanner::plan(['街边小店', '朋友简餐聚会', '红咖喱酱'], $planText);
$check($plan['source'] === 'rules', 'E1 关闭模型后走规则改写');
$check(count($plan['items']) === 3, 'E2 每个原始关键词都有规划条目');
$check(($plan['items'][0]['sentence_index'] ?? -1) === 0, 'E3 第一句关键词句序为 0');
$check(($plan['items'][1]['sentence_index'] ?? -1) === 1, 'E4 第二句关键词句序为 1');
$check(($plan['items'][2]['sentence_index'] ?? -1) === 2, 'E5 第三句关键词句序为 2');

$queries = MaterialKeywordPlanner::flatten($plan['items'], 1);
$check(array_slice($queries, 0, 3) === ['街边小店', '朋友简餐聚会', '红咖喱酱'], 'E6 主查询按句序排在前面');
$check(count($queries) === count(array_unique($queries)), 'E7 查询词去重');
$check($queries[0] === '街边小店', 'E8 情绪词未混入查询');

// —— F. 关键词类型识别与分类型阈值 —— //
$typeCases = [
    ['红咖喱酱', MaterialKeywordPlanner::TYPE_DISH],
    ['煎牛肉饼', MaterialKeywordPlanner::TYPE_DISH],
    ['温馨街边小店', MaterialKeywordPlanner::TYPE_SCENE],
    ['街边小店', MaterialKeywordPlanner::TYPE_SCENE],
    ['朋友聚会', MaterialKeywordPlanner::TYPE_PERSON],
    ['7-Eleven便利店', MaterialKeywordPlanner::TYPE_BRAND],
    ['蜜雪冰城', MaterialKeywordPlanner::TYPE_BRAND],
    ['温馨', MaterialKeywordPlanner::TYPE_MOOD],
    ['打工人日常', MaterialKeywordPlanner::TYPE_ABSTRACT],
    ['切菜', MaterialKeywordPlanner::TYPE_ACTION],
    ['木门样品', MaterialKeywordPlanner::TYPE_ENTITY],
    ['wooden door sample', MaterialKeywordPlanner::TYPE_ENTITY],
];
foreach ($typeCases as [$keyword, $expectType]) {
    $actualType = MaterialKeywordPlanner::classify($keyword);
    $check($actualType === $expectType, "F1 类型识别「{$keyword}」=> {$actualType}");
}

// 品牌类：必须品牌名原文出现，同行业其他门店拒绝
$check(AiMaterialRelevanceService::passes(['title' => '街边奶茶店招牌'], '蜜雪冰城') === false, 'F2 品牌类拒绝同行业其他门店');
$check(AiMaterialRelevanceService::passes(['title' => '蜜雪冰城门店招牌'], '蜜雪冰城') === true, 'F3 品牌类放行原文品牌名');
$check(AiMaterialRelevanceService::passes(['title' => '7-Eleven便利店夜景门头'], '7-Eleven便利店') === true, 'F3b 品牌名大小写/连字符不影响原文包含');
$check(AiMaterialRelevanceService::passes(['title' => '全家便利店夜景门头'], '7-Eleven便利店') === false, 'F3c 只有品类后缀相同的其他便利店仍拒绝');
// 人物类：呈现方式多样，单个二元组交集即放行（实体类同场景会被拒绝）
$check(AiMaterialRelevanceService::passes(['title' => '朋友合影留念'], '朋友聚会') === true, 'F4 人物类放宽到 1 个二元组');
$check(AiMaterialRelevanceService::passes(['title' => '朋友合影留念'], '朋友聚会', '', '', 'entity') === false, 'F5 同一候选按实体类阈值则拒绝');
// 氛围/抽象类：没有可检字面主体，跳过文本闸门交给画面闸门
$check(AiMaterialRelevanceService::passes(['title' => '毫无关系的素材标题'], '温馨') === true, 'F6 氛围类跳过文本闸门');
$check(AiMaterialRelevanceService::passes(['title' => '毫无关系的素材标题'], '打工人日常') === true, 'F7 抽象类跳过文本闸门');

// 配置覆盖：把人物类收紧后，原本放行的候选应被拒绝
Config::set([
    'ai_material_relevance' => [
        'enabled' => true,
        'min_hits' => 2,
        'min_ratio' => 0.4,
        'min_consecutive' => 0,
        'vision_enabled' => false,
        'vision_timeout' => 8,
        'keyword_type_thresholds' => [
            'person' => ['min_hits' => 2, 'min_ratio' => 1.0, 'text_enabled' => true],
        ],
    ],
], 'project');
$check(AiMaterialRelevanceService::passes(['title' => '朋友合影留念'], '朋友聚会') === false, 'F8 配置可覆盖人物类阈值');

// 画面提示词按类型换问法
$brandQuestion = AiMaterialRelevanceService::visualQuestion('蜜雪冰城');
$personQuestion = AiMaterialRelevanceService::visualQuestion('朋友聚会');
$check(strpos($brandQuestion, '品牌标识') !== false, 'F9 品牌类提示词判品牌标识');
$check(strpos($personQuestion, '人物') !== false, 'F10 人物类提示词判人物主体');
$check(strpos($brandQuestion, '只回答') !== false && strpos($personQuestion, '只回答') !== false, 'F11 分类型提示词仍只答是/否');

// 规划结果保留类型
$typedPlan = MaterialKeywordPlanner::plan(['温馨街边小店', '红咖喱酱'], $planText);
$typedQueries = MaterialKeywordPlanner::plannedQueries($typedPlan['items'], 1);
$check(($typedQueries[0]['query'] ?? '') === '街边小店' && ($typedQueries[0]['type'] ?? '') === MaterialKeywordPlanner::TYPE_SCENE, 'F12 主查询保留场景类型');
$check(($typedQueries[1]['query'] ?? '') === '红咖喱酱' && ($typedQueries[1]['type'] ?? '') === MaterialKeywordPlanner::TYPE_DISH, 'F13 主查询保留菜品类型');
$check(
    MaterialKeywordPlanner::flatten($typedPlan['items'], 1) === array_column($typedQueries, 'query'),
    'F14 flatten 与 plannedQueries 顺序一致'
);

// —— 恢复原始配置 —— //
if (is_array($originProjectConfig)) {
    Config::set($originProjectConfig, 'project');
}

echo "---\n";
echo $failed === 0
    ? "ALL PASS（{$passed} 通过）\n"
    : "{$failed} FAILED（{$passed} 通过）\n";
exit($failed === 0 ? 0 : 1);
