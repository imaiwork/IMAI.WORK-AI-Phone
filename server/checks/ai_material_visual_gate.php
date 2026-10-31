<?php
/**
 * AI 找素材画面闸门：视觉模型否定回答判定回归脚本（纯函数，不调中台）。
 * 运行：php checks/ai_material_visual_gate.php
 */
require __DIR__ . '/../vendor/autoload.php';

use app\common\service\AiMaterialRelevanceService;

$cases = [
    // 明确否定 => 应拒绝（true）
    '否' => true,
    '否。' => true,
    '否，画面主体是手链饰品。' => true,
    '不相关' => true,
    '不是，画面是手链' => true,
    '该画面与关键词无关' => true,
    '不符合' => true,
    'No' => true,
    'no, it shows a bracelet' => true,
    // 肯定或模糊 => 应放行（false）
    '是' => false,
    '是，画面主体就是木门样品' => false,
    '是相关的' => false,
    'Yes' => false,
    '这张图片展示的是木门样品' => false,
    '' => false,
    // —— 边界：肯定结论 + 解释文字含否定词，不能误判为拒绝 —— //
    '是，画面中没有无关物品' => false,
    '是，画面主体与关键词并非无关' => false,
    // —— 边界：设问式模糊回答，证据不足放行 —— //
    '是否相关：无法判断' => false,
    '无法判断，画面模糊' => false,
    // —— 边界：30 字截断窗口，否定词在窗口外不应触发拒绝 —— //
    str_repeat('画', 29) . '否' => true,  // 「否」在第 30 字（索引 29），窗口内
    str_repeat('画', 30) . '否' => false, // 「否」在第 31 字，窗口外
    // —— 边界：英文否定仅识别句首 no 单词，nope/not 不误判 —— //
    'Nope' => false,
    'not related' => false,
    // —— 边界：空白包裹、否定结论在句中 —— //
    '  否，画面是手链  ' => true,
    '回答：否。' => true,
];

$fail = 0;
foreach ($cases as $input => $expect) {
    $got = AiMaterialRelevanceService::isNegativeVisualAnswer((string)$input);
    if ($got !== $expect) {
        $fail++;
        echo "FAIL: [{$input}] expect=" . var_export($expect, true) . ' got=' . var_export($got, true) . PHP_EOL;
    }
}
echo $fail === 0 ? 'ALL PASS' : "{$fail} FAILED";
echo PHP_EOL;
