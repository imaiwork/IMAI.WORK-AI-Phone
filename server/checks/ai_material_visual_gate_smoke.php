<?php
/**
 * AI 找素材画面闸门冒烟脚本：启动框架，验证守卫路径、文本闸门回归、真实中台判图。
 * 运行：php checks/ai_material_visual_gate_smoke.php
 *
 * 覆盖：
 * - A. passesVisual 守卫路径：开关关闭 / 空画面地址 / 空关键词，均应在发网络请求前直接放行
 * - B. passes 文本闸门回归：确保新增画面闸门未影响原有文本过滤逻辑
 * - C. 真实中台判图端到端：无关画面应被闸门拒绝（缺陷场景），相关画面应放行；网络失败记 SKIP 不记 FAIL
 */
require __DIR__ . '/../vendor/autoload.php';

use app\common\service\AiMaterialRelevanceService;
use app\common\service\ToolsService;
use think\facade\Config;

$app = new \think\App();
$app->initialize();

$failed = 0;
$passed = 0;
$skipped = 0;
$check = static function (bool $ok, string $msg) use (&$failed, &$passed): void {
    if ($ok) {
        $passed++;
        echo "PASS {$msg}\n";
        return;
    }
    $failed++;
    echo "FAIL {$msg}\n";
};
$skip = static function (string $msg) use (&$skipped): void {
    $skipped++;
    echo "SKIP {$msg}\n";
};

// —— A. 画面闸门守卫路径（不应触发网络请求）—— //
$originConfig = Config::get('project.ai_material_relevance');

Config::set(['ai_material_relevance' => ['enabled' => true, 'min_hits' => 1, 'vision_enabled' => false, 'vision_timeout' => 8]], 'project');
$check(
    AiMaterialRelevanceService::passesVisual('http://example.com/a.jpg', '木门样品') === true,
    'A1 vision_enabled=false 时直接放行'
);

Config::set(['ai_material_relevance' => ['enabled' => true, 'min_hits' => 1, 'vision_enabled' => true, 'vision_timeout' => 8]], 'project');
$check(
    AiMaterialRelevanceService::passesVisual('', '木门样品') === true,
    'A2 空画面地址（视频无封面场景）放行'
);
$check(
    AiMaterialRelevanceService::passesVisual('http://example.com/a.jpg', '  ') === true,
    'A3 空关键词放行'
);

// —— B. 文本闸门回归 —— //
$textCases = [
    // [关键词, 候选, 期望, 说明]
    ['木门样品', ['title' => '木门样品展示厅'], true, '标题全量包含关键词放行'],
    ['木门样品', ['title' => '实木门样品图'], true, '二元组有交集放行'],
    ['木门样品', ['title' => '时尚手链饰品批发'], false, '中文标题零交集拒绝'],
    ['木门样品', ['title' => ''], true, '无标题证据不足放行'],
    ['木门样品', [], true, '字段缺失证据不足放行'],
    ['door', ['title' => '时尚手链饰品'], true, '英文关键词对中文标题证据不足放行'],
    ['door', ['title' => 'wooden door sample'], true, '英文互相包含放行'],
    ['door', ['title' => 'bracelet jewelry'], false, '英文零交集拒绝'],
    ['', ['title' => '任意标题'], true, '空关键词放行'],
    ['木门样品', ['title' => 'wooden door'], true, '中文关键词对非中文标题证据不足放行'],
];
foreach ($textCases as [$keyword, $item, $expect, $msg]) {
    $check(AiMaterialRelevanceService::passes($item, $keyword) === $expect, 'B ' . $msg);
}

// —— C. 真实中台判图端到端 —— //
if (is_array($originConfig)) {
    Config::set(['ai_material_relevance' => $originConfig], 'project');
}
// 常量 IMAI_TOOLS_API_URL 在 ToolsService 类文件加载时才注入，先触发自动加载再判断
class_exists(ToolsService::class);
if (!defined('IMAI_TOOLS_API_URL') || IMAI_TOOLS_API_URL === '') {
    $skip('C 中台地址未配置，跳过真实判图');
} else {
    // 公开可访问的测试图（百度 logo）：对「百度logo」相关，对「木门样品」无关
    $testImage = 'https://www.baidu.com/img/flexible/logo/pc/result.png';

    // 先看一次模型原始回答作为证据
    $rawAnswer = '';
    try {
        $response = app(ToolsService::class)
            ->setApiUrl('/api/qwen/image/parse')
            ->setRequest([
                'file_url' => $testImage,
                'question' => '请判断这张图片展示的主体内容是否与「木门样品」直接相关，即画面主体就是该词所描述的事物或场景，而不是无关物品。只回答"是"或"否"。',
            ])
            ->setMethod('POST')
            ->setTimeout(3, 8)
            ->send()
            ->response;
        if ((int)($response['code'] ?? 0) === 10000) {
            $rawAnswer = trim((string)($response['data']['output'] ?? ''));
        }
    } catch (\Throwable $e) {
        $rawAnswer = '';
    }
    if ($rawAnswer === '') {
        $skip('C 中台判图不可用（网络异常或无结果），跳过端到端断言');
    } else {
        echo "INFO 模型对「木门样品」× 百度logo图 的原始回答：{$rawAnswer}\n";
        $check(
            AiMaterialRelevanceService::passesVisual($testImage, '木门样品') === false,
            'C1 无关画面（百度logo图 × 关键词「木门样品」）应被闸门拒绝（缺陷场景）'
        );
        $check(
            AiMaterialRelevanceService::passesVisual($testImage, '百度logo') === true,
            'C2 相关画面（百度logo图 × 关键词「百度logo」）应放行'
        );
    }
}

echo "---\n";
echo $failed === 0
    ? "ALL PASS（{$passed} 通过" . ($skipped > 0 ? "，{$skipped} 跳过" : '') . "）\n"
    : "{$failed} FAILED（{$passed} 通过，{$skipped} 跳过）\n";
exit($failed === 0 ? 0 : 1);
