<?php
require __DIR__ . '/../app/common/service/TextQualityValidator.php';
use app\common\service\TextQualityValidator as T;

echo "=== 新增语种检测 ===\n";

// 俄文（西里尔）
$ru = "ПриветМирЭтоРусскийТест";
echo "俄文 detectLanguage: " . T::detectLanguage($ru) . "\n";
$v = T::validate($ru);
echo "俄文 validate status={$v['status']} type={$v['type']}\n";
echo "  修复前: $ru\n";
echo "  修复后: {$v['suggested_fix']}\n\n";

// 乌克兰语（西里尔）
$uk = "СвітЦеУкраїнськаМова";
echo "乌克兰语 detectLanguage: " . T::detectLanguage($uk) . "\n";
$v = T::validate($uk);
echo "乌克兰语 validate status={$v['status']} type={$v['type']}\n\n";

// 希腊文
$el = "ΓειάΣουΚόσμοςΑυτόΕίναιΕλληνικά";
echo "希腊文 detectLanguage: " . T::detectLanguage($el) . "\n";
$v = T::validate($el);
echo "希腊文 validate status={$v['status']} type={$v['type']}\n\n";

// 正常俄文（有空格）
$ruNormal = "Привет мир это русский язык с пробелами";
echo "正常俄文 detectLanguage: " . T::detectLanguage($ruNormal) . "\n";
$v = T::validate($ruNormal);
echo "正常俄文 validate status={$v['status']} (应为 ok)\n\n";

// 保持原有语种测试
echo "=== 原有语种保持 ===\n";
echo "英文: " . T::detectLanguage("Hello World Test") . " (应为 english)\n";
echo "法文: " . T::detectLanguage("Bonjour le monde français avec café") . " (应为 space_separated)\n";
echo "中文: " . T::detectLanguage("这是一段中文文案") . " (应为 cjk)\n";

echo "\n=== 批量 sanitizeArray ===\n";
$batch = [
    "HelloWorldThisIsATestSentence",
    "ПриветМирРусскийТест",       // 俄文拼接
    "正常中文文案，没问题",
    "ΓειάΣουΚόσμοςΕλληνικά",       // 希腊拼接
    "BonjourLeMondeFrançais",      // 法文拼接
];
$result = T::sanitizeArray($batch);
echo "warnings: " . count($result['warnings']) . " 条\n";
foreach ($result['warnings'] as $w) {
    echo "  [{$w['index']}] type={$w['type']} orig=" . mb_substr($w['original'], 0, 30) . "...\n";
}
