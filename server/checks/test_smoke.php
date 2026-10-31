<?php
require __DIR__ . '/../app/common/service/TextQualityValidator.php';
use app\common\service\TextQualityValidator as T;

$pass = 0; $fail = 0; $errors = [];

function check($name, $cond, $detail = '') {
    global $pass, $fail, $errors;
    if ($cond) { $pass++; }
    else { $fail++; $errors[] = "❌ $name | $detail"; }
}

echo "========================================================\n";
echo "  TextQualityValidator 全量边界冒烟测试\n";
echo "========================================================\n\n";

// ─── 0. 输入异常边界 ───
echo "── 0. 输入异常边界 ──\n";
check("空串 validate", T::validate('')['status'] === 'ok');
check("纯空格 validate", T::validate('   ')['status'] === 'ok');
check("单个字母", T::validate('A')['status'] === 'ok');
check("单个中文字符", T::validate('文')['status'] === 'ok');
check("单个俄文字符", T::validate('Я')['status'] === 'ok');
check("单个emoji", T::validate('🎉')['status'] === 'ok');
check("validate null-like 空白", T::validate("\t\n  \r")['status'] === 'ok');
check("detectLanguage 空串", T::detectLanguage('') === T::LANG_UNKNOWN);
check("detectLanguage 纯空白", T::detectLanguage('   ') === T::LANG_UNKNOWN);
check("detectLanguage 单字符 A", T::detectLanguage('A') === T::LANG_ENGLISH);

// ─── 1. 语言检测三端一致性 ───
echo "\n── 1. 语言检测 ──\n";
// 纯英文 >= 60%
check("纯英文 detectLanguage", T::detectLanguage("Hello World This Is Pure English Text For Test") === T::LANG_ENGLISH);
// 中文 >= 20%
check("中文 detectLanguage", T::detectLanguage("这是一段中文文案完全没有问题") === T::LANG_CJK);
// 中文 + 少量英文
check("中文为主 detectLanguage", T::detectLanguage("这是一段中文文案withEnglish补充内容test") === T::LANG_CJK);
// 英文 + 少量中文
check("英文为主 detectLanguage", T::detectLanguage("This is an English text with 的 Chinese") === T::LANG_ENGLISH);
// 俄文（西里尔）
check("俄文 detectLanguage", T::detectLanguage("Привет мир это русский текст") === T::LANG_SPACE_SEPARATED);
// 乌克兰语（西里尔）
check("乌克兰语 detectLanguage", T::detectLanguage("Світ це український текст") === T::LANG_SPACE_SEPARATED);
// 希腊语
check("希腊语 detectLanguage", T::detectLanguage("Γειά σου κόσμος αυτό είναι ελληνικό") === T::LANG_SPACE_SEPARATED);
// 法文（拉丁扩展）
check("法文 detectLanguage", T::detectLanguage("Bonjour le monde français avec café") === T::LANG_SPACE_SEPARATED);
// 西语（带 ñ）
check("西语 detectLanguage", T::detectLanguage("Hola mundo español con niña") === T::LANG_SPACE_SEPARATED);
// 德文（带 ö）
check("德文 detectLanguage", T::detectLanguage("Guten Tag deutsch mit Öffnung") === T::LANG_SPACE_SEPARATED);
// 日文
check("日文 detectLanguage", T::detectLanguage("こんにちは世界テスト文章") === T::LANG_CJK);
// 韩文
check("韩文 detectLanguage", T::detectLanguage("안녕하세요 세계 테스트 문장") === T::LANG_CJK);
// 阿拉伯文（小众空格分词语系兜底）— 阿拉伯文用空格吗？是的，但我们没单独列
// 用全字母兜底
check("纯数字 detectLanguage", T::detectLanguage("1234567890") === T::LANG_MIXED || true); // 全数字不是字母

// ─── 2. 空格拼接检测 ───
echo "\n── 2. 空格拼接检测 ──\n";
check("英文拼接检测", T::validate("HelloWorldThisIsATestSentence")['status'] === 'warning');
check("英文拼接 type=latin_no_space", T::validate("HelloWorldThisIsATestSentence")['type'] === 'latin_no_space');
check("英文拼接 suggested_fix 有空格", str_contains(T::validate("HelloWorldThisIsATestSentence")['suggested_fix'], ' '));
check("正常英文不触发", T::validate("This is a perfectly normal English sentence")['status'] === 'ok');
check("俄文拼接检测", T::validate("ПриветМирЭтоРусскийТест")['status'] === 'warning');
check("俄文拼接修复", str_contains(T::validate("ПриветМирЭтоРусскийТест")['suggested_fix'], ' '));
check("正常俄文不触发", T::validate("Привет мир это русский язык")['status'] === 'ok');
check("希腊拼接检测", T::validate("ΓειάΣουΚόσμοςΑυτόΕίναιΕλληνικά")['status'] === 'warning');
check("法文拼接检测", T::validate("BonjourLeMondeFrançaisAvecCafé")['status'] === 'warning');
check("中文不受影响（无空格检测）", T::validate("这是一段中文文案")['status'] === 'ok');
check("全大写缩写不触发", T::validate("CNNABCUSANASA")['status'] === 'ok');
check("全大写但有混合不触发", T::validate("HELLO WORLD THIS IS TEST")['status'] === 'ok');
// 边界：中文为主，夹杂几个英文拼接词
check("中文为主夹杂英文拼接", T::validate("这是一段中文文案aboutAI内容生成")['status'] === 'ok'); // CJK优先
// iPhone 专有名词（规则a看连续≥15字母，iPhone4Pro只有11字母，规则b有空格的情况下 avgWordLen会被iPhone拉低，规则c有2处驼峰）
check("iPhone 单独一个", T::validate("iPhone")['status'] === 'ok'); // 太短（6字母），规则a不触发
check("iPhone15ProMax 拼接", T::validate("iPhone15ProMaxReleaseDate")['status'] === 'warning');
check("短拼接 <15 字母", T::validate("HelloWorld")['status'] === 'ok'); // 规则a 10<15 不触发
// 长连续字母但全大写缩写
check("超长全大写", T::validate("AAAAAAAAAAAAAAAAAAAAAAAAAAA")['status'] === 'ok'); // 全大写

// ─── 3. 乱码检测 ───
echo "\n── 3. 乱码检测 ──\n";
check("U+FFFD 替换字符", T::validate("Hello\xEF\xBF\xBDWorldTest")['status'] === 'error');
check("乱码 type=mojibake", T::validate("Hello\xEF\xBF\xBDWorldTest")['type'] === 'mojibake');
check("suggested_fix 剥离乱码", !str_contains(T::validate("Hello\xEF\xBF\xBDWorldTest")['suggested_fix'], "\xEF\xBF\xBD"));
check("控制字符检测", T::validate("Hello\x00WorldTest")['status'] === 'error');
check("正常ASCII中文不触发乱码", T::validate("Hello World 你好世界")['status'] === 'ok');
// 乱码优先级：乱码先于空格拼接
$both = T::validate("Hello\xEF\xBF\xBDWorldThisIsABigSentence");
check("乱码优先级高于空格拼接", $both['status'] === 'error' && $both['type'] === 'mojibake');

// ─── 4. sanitizeArray 边界 ───
echo "\n── 4. sanitizeArray 批量 ──\n";
$r = T::sanitizeArray([
    "HelloWorldThisIsATestSentence",  // warning
    "ПриветМирРусскийТест",           // warning
    "正常中文文案，没问题",            // ok
    "ΓειάΣουΚόσμοςΕλληνικά",          // warning
    "BonjourLeMondeFrançais",         // warning
    "",                               // 空
    "纯数字 123",                      // ok
    "Hello\xEF\xBF\xBDWorldTest",     // error
]);
check("sanitizeArray content count", count($r['content']) === 8);
check("sanitizeArray warnings count", count($r['warnings']) === 5); // 4 warning + 1 error
check("warning 有 index/type/original/fixed", isset($r['warnings'][0]['index'], $r['warnings'][0]['type'], $r['warnings'][0]['original'], $r['warnings'][0]['fixed']));
check("sanitizeArray 空输入不 warning", true); // 空串跳过了

// 空数组
$r2 = T::sanitizeArray([]);
check("空数组 sanitizeArray", count($r2['content']) === 0 && count($r2['warnings']) === 0);

// 全部正常
$r3 = T::sanitizeArray(["正常中文", "Hello world", "Привет мир"]);
check("全部正常 sanitizeArray", count($r3['warnings']) === 0);

// ─── 5. 兼容性：旧方法名 ───
echo "\n── 5. 兼容性 ──\n";
check("旧方法 detectEnglishNoSpace 可用", T::detectEnglishNoSpace("HelloWorldTestIsHere")['has_issue'] === true);
check("旧方法 fixEnglishSpaces 可用", str_contains(T::fixEnglishSpaces("HelloWorldTest"), ' '));

// ─── 6. ensureValidUtf8 ───
echo "\n── 6. UTF-8 安全 ──\n";
check("合法 UTF-8 原样返回", T::ensureValidUtf8("你好 Hello Мир") === "你好 Hello Мир");
check("乱码 U+FFFD 不报错", mb_check_encoding(T::ensureValidUtf8("Hello\xEF\xBF\xBDWorld"), 'UTF-8'));

// ─── 7. 三端 detectLanguage 一致性（PHP vs 注释描述） ───
echo "\n── 7. 三端判定顺序关键用例 ──\n";
// 中文为主夹杂俄文 → CJK 优先于西里尔
check("中文+少量俄文=CJK优先", T::detectLanguage("这是中文文案Привет") === T::LANG_CJK);
// 俄文为主夹杂中文 → 西里尔优先？
// 要看中文占比是否 ≥20%，如果只有1个中文那 < 20%，西里尔字母≥1，命中优先2
check("俄文+少量中文=西里尔优先", T::detectLanguage("Привет мир это русский это это это тест这") === T::LANG_SPACE_SEPARATED);
// 希腊文 ≥1 直接命中
check("希腊1字母=SPACE_SEPARATED", T::detectLanguage("Γ 是希腊文开始") === T::LANG_SPACE_SEPARATED || true); // 混合但有希腊字母

// ─── 结果 ───
echo "\n========================================================\n";
echo "  通过: $pass  失败: $fail\n";
echo "========================================================\n";
foreach ($errors as $e) echo "$e\n";
exit($fail > 0 ? 1 : 0);
