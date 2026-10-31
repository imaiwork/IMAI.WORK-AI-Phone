<?php
/**
 * TextQualityValidator 单元测试脚本（纯 PHP，不依赖框架）
 * 运行方式：php checks/test_text_validator.php
 *
 * 不执行 vendor/autoload，直接手动 require 类文件，绕过 thinkphp 容器。
 */

// 手动 require 被测类（仅依赖 PHP 内置函数 + mbstring）
$classFile = __DIR__ . '/../app/common/service/TextQualityValidator.php';
if (!file_exists($classFile)) {
    echo "找不到: $classFile\n";
    exit(1);
}
require_once $classFile;

$pass = 0;
$fail = 0;
$errors = [];

function assert_eq($label, $expected, $actual) {
    global $pass, $fail, $errors;
    if ($expected === $actual) {
        echo "  ✅ $label\n";
        $pass++;
    } else {
        echo "  ❌ $label\n     expected: " . json_encode($expected, JSON_UNESCAPED_UNICODE) . "\n     actual:   " . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n";
        $fail++;
        $errors[] = $label;
    }
}

function assert_true($label, $cond) {
    global $pass, $fail, $errors;
    if ($cond) {
        echo "  ✅ $label\n";
        $pass++;
    } else {
        echo "  ❌ $label\n";
        $fail++;
        $errors[] = $label;
    }
}

use app\common\service\TextQualityValidator;

// ─── 1. 语言检测 ─────────────────────────────────────────────
echo "\n=== 1. 语言检测 ===\n";

assert_eq("English 检测", TextQualityValidator::detectLanguage("Hello World This Is English"), 'english');
assert_eq("CJK 检测", TextQualityValidator::detectLanguage("这是一段中文文案测试"), 'cjk');
assert_eq("法文检测（SPACE_SEPARATED）", TextQualityValidator::detectLanguage("Bonjour le monde ceci est un texte français avec café et résumé"), 'space_separated');
assert_eq("西语检测（SPACE_SEPARATED）", TextQualityValidator::detectLanguage("Hola mundo esto es español con niña y señor"), 'space_separated');
assert_eq("德语检测（SPACE_SEPARATED）", TextQualityValidator::detectLanguage("Guten Tag Welt dies ist Deutsch mit Öffnung und Straße"), 'space_separated');
assert_eq("中日混合 = CJK", TextQualityValidator::detectLanguage("今天は良い天気ですね一起出去玩吧"), 'cjk');
assert_eq("中英混合 英文多 = english", TextQualityValidator::detectLanguage("Hello World Test Text abc def ghi jkl mno"), 'english');

// ─── 2. 英文空格检测 ─────────────────────────────────────────
echo "\n=== 2. 英文空格检测 ===\n";

$r = TextQualityValidator::detectLatinNoSpace("HelloWorldThisIsATestSentence");
assert_true("英文拼接被检测", $r['has_issue'] === true);
assert_true("修复后有空格", strpos($r['suggested_fix'], 'Hello World') !== false);
echo "     原文: HelloWorldThisIsATestSentence\n     修复: {$r['suggested_fix']}\n";

$r = TextQualityValidator::detectLatinNoSpace("This is a normal English sentence with proper spaces");
assert_true("正常英文不触发", $r['has_issue'] === false);

$r = TextQualityValidator::detectLatinNoSpace("iPhone15ProMaxReleaseDate");
assert_true("驼峰+数字混合被检测", $r['has_issue'] === true);
echo "     原文: iPhone15ProMaxReleaseDate\n     修复: {$r['suggested_fix']}\n";

$r = TextQualityValidator::detectLatinNoSpace("CNN ABC USA");
assert_true("全大写缩写不触发", $r['has_issue'] === false);

// ─── 3. 拉丁系空格检测（法西德等） ────────────────────────────
echo "\n=== 3. 拉丁系空格检测（法/西/德） ===\n";

$r = TextQualityValidator::detectLatinNoSpace("BonjourLeMondeCeciEstUnTexteFrançaisAvecCafé");
assert_true("法文拼接被检测", $r['has_issue'] === true);
echo "     原文: BonjourLeMondeCeciEstUnTexteFrançaisAvecCafé\n     修复: {$r['suggested_fix']}\n";

$r = TextQualityValidator::detectLatinNoSpace("HolaMundoEstoEsEspañolConNiña");
assert_true("西文拼接被检测（带 ñ）", $r['has_issue'] === true);
echo "     原文: HolaMundoEstoEsEspañolConNiña\n     修复: {$r['suggested_fix']}\n";

$r = TextQualityValidator::detectLatinNoSpace("GutenTagWeltDiesIstDeutschMitÖffnung");
assert_true("德文拼接被检测（带 ö）", $r['has_issue'] === true);
echo "     原文: GutenTagWeltDiesIstDeutschMitÖffnung\n     修复: {$r['suggested_fix']}\n";

$r = TextQualityValidator::detectLatinNoSpace("Bonjour le monde ceci est un texte français");
assert_true("正常法文不触发", $r['has_issue'] === false);

// ─── 4. 乱码检测 ─────────────────────────────────────────────
echo "\n=== 4. 乱码检测 ===\n";

$r = TextQualityValidator::detectMojibake("Hello\xEF\xBF\xBDWorldThisIsTest");
assert_true("替换字符 U+FFFD 被检测", $r['has_garbage'] === true);

$r = TextQualityValidator::stripGarbage("Hello\xEF\xBF\xBDWorldThisIsTest");
assert_eq("剥离替换字符", "HelloWorldThisIsTest", $r);

$r = TextQualityValidator::detectMojibake("正常中文文案，没有乱码");
assert_true("正常中文不触发", $r['has_garbage'] === false);

$r = TextQualityValidator::detectMojibake("日本語のテストです");
assert_true("正常日文不触发", $r['has_garbage'] === false);

// ─── 5. validate 统一入口 ────────────────────────────────────
echo "\n=== 5. validate 统一入口 ===\n";

$v = TextQualityValidator::validate("HelloWorldThisIsATestSentence");
assert_eq("英文拼接 → latin_no_space", 'warning', $v['status']);
assert_eq("type = latin_no_space", 'latin_no_space', $v['type']);
assert_true("有 suggested_fix", !empty($v['suggested_fix']));

$v = TextQualityValidator::validate("BonjourLeMondeCeciEstUnTexteFrançais");
assert_eq("法文拼接 → latin_no_space (LATIN)", 'warning', $v['status']);

$v = TextQualityValidator::validate("正常中文文案，没有任何问题");
assert_eq("正常中文 → ok", 'ok', $v['status']);
assert_eq("type = null", null, $v['type']);

$v = TextQualityValidator::validate("Hello\xEF\xBF\xBDWorldTest");
assert_eq("含乱码 → error", 'error', $v['status']);
assert_eq("type = mojibake", 'mojibake', $v['type']);

// ─── 6. sanitizeArray 批量处理 ───────────────────────────────
echo "\n=== 6. sanitizeArray 批量处理 ===\n";

$batch = [
    "HelloWorldThisIsATestSentence",           // 英文拼接
    "正常中文文案，没问题",                      // 正常中文
    "BonjourLeMondeCeciEstUnTexteFrançais",    // 法文拼接
    "This is normal English with spaces",       // 正常英文
    "Hello\xEF\xBF\xBDWorldTest",              // 含乱码
];
$result = TextQualityValidator::sanitizeArray($batch);
assert_eq("返回 5 条清理后文案", 5, count($result['content']));
// 至少：英文拼接 + 法文拼接 + 乱码 = 3 条 warning
assert_true("返回至少 3 条 warnings（英文拼接+法文拼接+乱码）", count($result['warnings']) >= 3);

foreach ($result['warnings'] as $w) {
    assert_true("warning 有 index/type/original/fixed",
        isset($w['index']) && isset($w['type']) && isset($w['original']) && isset($w['fixed']));
    echo "     warning[{$w['index']}] type={$w['type']}\n";
}

// ─── 7. 边界用例 ─────────────────────────────────────────────
echo "\n=== 7. 边界用例 ===\n";

$v = TextQualityValidator::validate("");
assert_eq("空串 → ok", 'ok', $v['status']);

$v = TextQualityValidator::validate("   ");
assert_eq("纯空格 → ok", 'ok', $v['status']);

$v = TextQualityValidator::validate("A");
assert_eq("单字母 → ok", 'ok', $v['status']);

// 中英混合（中文为主，英文占比 < 60%）应跳过英文检测
$v = TextQualityValidator::validate("这是一段中文文案aboutAI内容生成");
assert_true("中英混合（中文为主）→ ok 或 error(乱码)", in_array($v['status'], ['ok', 'error']));

// 短英文（不触发 15 字规则）
$v = TextQualityValidator::validate("HelloWorld");
assert_true("短拼接（<15字）", $v['status'] === 'ok');

// ─── 8. 兼容旧方法名 ─────────────────────────────────────────
echo "\n=== 8. 兼容旧方法名 ===\n";

$old = TextQualityValidator::detectEnglishNoSpace("HelloWorldThisIsATestSentence");
assert_true("旧方法 detectEnglishNoSpace 仍可用", $old['has_issue'] === true);

$oldFix = TextQualityValidator::fixEnglishSpaces("HelloWorldThisIsATestSentence");
assert_true("旧方法 fixEnglishSpaces 仍可用", strpos($oldFix, 'Hello World') !== false);

// ─── 总结 ────────────────────────────────────────────────────
echo "\n======== 测试结果 ========\n";
echo "通过: $pass / " . ($pass + $fail) . "\n";
echo "失败: $fail\n";
if ($fail > 0) {
    echo "失败用例:\n";
    foreach ($errors as $e) echo "  ❌ $e\n";
    exit(1);
}
echo "🎉 全部通过!\n";
exit(0);
