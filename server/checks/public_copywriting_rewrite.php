<?php
/**
 * 公共文案 AI 仿写：离线断言脚本（不联网、不写库）
 * 运行方式：php checks/public_copywriting_rewrite.php
 *
 * 覆盖：分段解析、按类型归位、user message 参数计算、计费枚举注册、校验场景。
 */

require __DIR__ . '/../vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use app\api\validate\copywriting\PublicCopyValidate;
use app\common\enum\user\AccountLogCatEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\aiPersona\AiPersonaCopywritingLibrary;
use app\common\service\copywriting\PublicCopywritingRewriteService as Svc;

$pass = 0;
$fail = 0;
$errors = [];

function assert_eq($label, $expected, $actual)
{
    global $pass, $fail, $errors;
    if ($expected === $actual) {
        echo "  [ok] $label\n";
        $pass++;
        return;
    }
    echo "  [FAIL] $label\n     expected: " . json_encode($expected, JSON_UNESCAPED_UNICODE)
        . "\n     actual:   " . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n";
    $fail++;
    $errors[] = $label;
}

function assert_true($label, $actual)
{
    assert_eq($label, true, (bool)$actual);
}

echo "1) 分段解析 + 类型归位\n";

$oral = <<<TXT
【标题】
老房翻新别乱砸墙
【正文】
很多人拿到老房子第一件事就是砸墙，其实承重墙一碰就是大麻烦。
我在成都做老房翻新十年，先量结构再动工，合同价不增项。
拿不准的可以私信我，免费帮你看一眼。
【话题】
#老房翻新 #成都装修
TXT;

$r = Svc::parse($oral, AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER, AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL);
assert_eq('口播-标题', '老房翻新别乱砸墙', $r['title']);
assert_true('口播-正文保留', str_contains($r['content'], '承重墙'));
assert_eq('口播-话题置空', '', $r['topic']);

$news = "【标题】\n老房翻新别再盲目砸墙\n成都老房改造最常见的三个误区\n【正文】\n\n【话题】\n";
$r = Svc::parse($news, AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER, AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS);
assert_eq('新闻体-多行标题', "老房翻新别再盲目砸墙\n成都老房改造最常见的三个误区", $r['title']);
assert_eq('新闻体-正文置空', '', $r['content']);

$publish = "【标题】\n老房翻新前先看这三条\n【正文】\n第一段。\n\n第二段。\n【话题】\n#老房翻新，#成都装修，#不增项，#多余话题";
$r = Svc::parse($publish, AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH, AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE);
assert_eq('发布-标题', '老房翻新前先看这三条', $r['title']);
assert_eq('发布-话题最多3个', '#老房翻新 #成都装修 #不增项', $r['topic']);

$mixcut = "【标题】\n标题带句号。\n【正文】\n一句话一个意思。\n画面能对上。\n【话题】\n";
$r = Svc::parse($mixcut, AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER, AiPersonaCopywritingLibrary::DRIVER_TYPE_MATERIAL_MIXCUT);
assert_eq('混剪-标题去句号', '标题带句号', $r['title']);
assert_eq('混剪-话题置空', '', $r['topic']);

echo "\n2) 残稿与脏格式兜底\n";
$noLabel = "老房翻新别乱砸墙，先看结构再动工。合同价不增项，有问题私信我。";
$r = Svc::parse($noLabel, AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER, AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL);
assert_true('无标签时整段当正文', str_contains($r['content'], '先看结构'));
assert_true('无标签时标题兜底非空', $r['title'] !== '');

$md = "```text\n【标题】\n**加粗标题**\n【正文】\n# 小标题\n正文内容。\n【话题】\n```";
$r = Svc::parse($md, AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER, AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL);
assert_eq('Markdown 清洗-标题', '加粗标题', $r['title']);
assert_eq('Markdown 清洗-正文去 #', "小标题\n正文内容。", $r['content']);

$emptyBody = "【标题】\n只有标题\n【正文】\n\n【话题】\n";
$thrown = '';
try {
    Svc::parse($emptyBody, AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER, AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL);
} catch (\Throwable $e) {
    $thrown = $e->getMessage();
}
assert_eq('空正文判失败', 'AI 未返回正文', $thrown);

echo "\n3) user message 参数计算\n";
$long = str_repeat('这是一句参考文案，讲的是老房翻新要先看结构再动工。', 20);
$tpl = [
    'library_type' => AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER,
    'driver_type' => AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS,
];
$msg = Svc::buildUserMessage('新闻体', $tpl, $long, '人设名：李师傅');
assert_true('含参考文案段', str_contains($msg, '【参考文案】'));
assert_true('含人设段', str_contains($msg, '【人设资料】'));
assert_true('新闻体标题行数出现', str_contains($msg, '需要输出的标题行数：1 行'));
assert_true('新闻体正文留空指令', str_contains($msg, '- 正文：留空'));

$tpl['driver_type'] = AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL;
$msg = Svc::buildUserMessage('口播文案', $tpl, $long, '');
$assert = preg_match('/- 正文：约 (\d+) 字，分 (\d) 段/u', $msg, $m) === 1;
assert_true('口播字数与段数写进 prompt', $assert);
if ($assert) {
    $len = (int)$m[1];
    assert_true('口播字数被夹到 400 以内', $len <= 400 && $len >= 80);
    $expectPara = max(2, min(4, (int)ceil($len / 120)));
    assert_eq('段数与字数一致', (string)$expectPara, $m[2]);
}
assert_true('空人设给通用稿兜底', str_contains($msg, '人设资料缺失'));

$pubTpl = [
    'library_type' => AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH,
    'driver_type' => AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE,
];
$msg = Svc::buildUserMessage('发布文案', $pubTpl, $long, '人设名：李师傅');
assert_true('发布文案话题指令', str_contains($msg, '最多 3 个'));

echo "\n4) 计费项与账单大类注册\n";
assert_eq('枚举值', 10114, AccountLogEnum::TOKENS_DEC_PUBLIC_COPYWRITING_REWRITE);
assert_true('TOKENS_DEC 汇总包含新项', in_array(AccountLogEnum::TOKENS_DEC_PUBLIC_COPYWRITING_REWRITE, AccountLogEnum::TOKENS_DEC, true));
assert_eq('账单描述', '公共文案AI仿写减少算力', AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_DEC_PUBLIC_COPYWRITING_REWRITE));
assert_eq('账单大类=AI文案', AccountLogCatEnum::COPY, AccountLogCatEnum::catOf(AccountLogEnum::TOKENS_DEC_PUBLIC_COPYWRITING_REWRITE));

echo "\n5) 校验场景与来源常量\n";
$v = new PublicCopyValidate();
assert_true('sceneRewrite 存在', method_exists($v, 'sceneRewrite'));
assert_eq('SOURCE_AI_REWRITE', 4, AiPersonaCopywritingLibrary::SOURCE_AI_REWRITE);
assert_eq('MAX_BATCH', 5, Svc::MAX_BATCH);

echo "\n6) 人设资料截断\n";
$personaText = str_repeat('人设资料很长的一段描述。', 200);
$norm = \app\common\service\copywriting\VideoPromptService::normalizePersona($personaText);
assert_true('人设截断到 600 字以内', mb_strlen($norm) <= 600);

echo "\n-----------------------------\n";
echo "通过 $pass 项，失败 $fail 项\n";
if ($fail > 0) {
    echo '失败用例：' . implode(' / ', $errors) . "\n";
    exit(1);
}
exit(0);
