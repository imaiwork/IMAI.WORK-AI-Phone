<?php
/**
 * 一句话视频标题口径离线校验（不连库、不走框架）
 * 1) VideoPromptService::extractTitle / fallbackTitle / normalizeTitle 的真实行为
 * 2) SoraVideoSettingLogic::isAutoName 里的三条正则（直接从源文件抽出来跑）
 */

require dirname(__DIR__) . '/app/common/service/copywriting/VideoPromptService.php';

use app\common\service\copywriting\VideoPromptService;

$fail = 0;
$ok = 0;
function check(string $name, $actual, $expect): void
{
    global $fail, $ok;
    if ($actual === $expect) {
        $ok++;
        return;
    }
    $fail++;
    echo "FAIL {$name}\n  期望: " . var_export($expect, true) . "\n  实际: " . var_export($actual, true) . "\n";
}

/* ── 1. 带标题行的新输出 ───────────────────────────── */
$new = "【标题】京都薇薇美容到家海报\n"
    . "7秒竖屏9:16，写实风格，以产品为主\n"
    . "镜头1（0.4秒）：特写，正面机位，缓推；一只手拿起 @图片1 的面霜；口播「秋冬就靠它」；字幕「补水」";
[$title, $body] = VideoPromptService::extractTitle($new);
check('标题行解析', $title, '京都薇薇美容到家海报');
check('标题行剥离', mb_substr($body, 0, 8), '7秒竖屏9:16');
check('剥离后不含标题字', strpos($body, '【标题】'), false);

/* ── 2. 旧输出：没有标题行，靠兜底提炼 ─────────────── */
$old = "7秒竖屏9:16，写实风格，京都薇薇美容到家海报，以 @图片1 为唯一外观依据\n"
    . "镜头1（0.4秒）：特写，正面机位，缓推";
[$t2, $b2] = VideoPromptService::extractTitle($old);
check('无标题行不误判', $t2, '');
check('无标题行正文不动', $b2, $old);
check('兜底提炼主题', VideoPromptService::fallbackTitle($old), '京都薇薇美容到家海报');

/* ── 3. 工单里的垃圾标题输入 ───────────────────────── */
check('纯参数首行', VideoPromptService::fallbackTitle('7秒竖屏9:16，写'), '写');
check('整段参数', VideoPromptService::fallbackTitle('7秒竖屏9:16，写实风格，高清2K'), '');
check('空内容', VideoPromptService::fallbackTitle('   '), '');

/* ── 4. 用户自己写的原话：取前半句 ─────────────────── */
check('用户原话', VideoPromptService::fallbackTitle('帮我生成一个京都薇薇美容到家的海报，主打秋冬补水，竖屏投放'), '京都薇薇美容到家的海报');
check('原话祈使句', VideoPromptService::fallbackTitle('写一段咖啡广告文案，主打手冲'), '咖啡广告文案');
check('原话带风格词', VideoPromptService::fallbackTitle('写实风格，京都薇薇美容到家海报'), '京都薇薇美容到家海报');
check('祈使词不当标题时保留', VideoPromptService::fallbackTitle('帮我生成一个'), '帮我生成一个');

/* ── 5. 标题规整 ───────────────────────────────────── */
check('去引号书名号', VideoPromptService::normalizeTitle('“《京都之旅》”'), '京都之旅');
check('去尾部标点', VideoPromptService::normalizeTitle('京都之旅。'), '京都之旅');
check('超长截断', mb_strlen(VideoPromptService::normalizeTitle(str_repeat('美', 40))), 20);
check('空白标题', VideoPromptService::normalizeTitle('  '), '');
check('标题里的素材 token', VideoPromptService::normalizeTitle('@图片1 面霜秋冬补水'), '面霜秋冬补水');

/* ── 6. 标题是「标题:」变体也要认 ──────────────────── */
foreach (['标题：京都之旅', '标题: 京都之旅', '【标题】 京都之旅', '视频标题-京都之旅', '记录标题：京都之旅'] as $variant) {
    [$t, $b] = VideoPromptService::extractTitle($variant . "\n镜头1：xxx");
    check("变体 {$variant}", [$t, $b], ['京都之旅', '镜头1：xxx']);
}

/* ── 6.1 正文里出现「标题」不受影响 ─────────────────── */
$bodyWithTitleWord = "7秒竖屏9:16，写实风格\n镜头1：标题写在画面左上角";
[$t9, $b9] = VideoPromptService::extractTitle($bodyWithTitleWord);
check('正文含标题字样不误删标题行判定', [$t9, $b9], ['', $bodyWithTitleWord]);
check('正文含标题字样兜底只看首行', VideoPromptService::fallbackTitle($bodyWithTitleWord), '');

/* ── 7. isAutoName 的三条正则（从源文件抽真实规则）─── */
$src = file_get_contents(dirname(__DIR__) . '/app/api/logic/sora/SoraVideoSettingLogic.php');
$fn = '';
if (preg_match('/private static function isAutoName\(string \$name\): bool\s*\{(.*?)\n    \}/su', $src, $m)) {
    $fn = $m[1];
}
preg_match_all('/preg_match\(\'(\/\^.*?\/[a-z]*)\'/u', $fn, $m2);
$patterns = $m2[1] ?? [];
check('抽到 3 条自动名规则', count($patterns), 3);

$isAuto = static function (string $name) use ($patterns): bool {
    foreach ($patterns as $p) {
        if (preg_match($p, $name)) {
            return true;
        }
    }
    return false;
};

check('PC 默认名', $isAuto('202610291545一句话生成视频'), true);
check('小程序默认名', $isAuto('202610291545一句话生成视频'), true);
check('数字前缀变体', $isAuto('202610291545一句话视频'), true);
check('后端历史默认名 SD', $isAuto('一句话视频-SD2.5(480p)2026-10-29 15:45:01'), true);
check('后端历史默认名 sora2', $isAuto('一句话视频-(sora2)2026-10-29 15:45:01'), true);
check('兜底名', $isAuto('一句话视频 10-29 15:45'), true);
check('用户自己填的名字', $isAuto('京都美容秋冬补水'), false);
check('AI 标题', $isAuto('京都薇薇美容到家海报'), false);
check('用户名字里含一句话视频但不匹配', $isAuto('我的第一支一句话视频作品'), false);

echo "\n通过 {$ok} 条，失败 {$fail} 条\n";
exit($fail > 0 ? 1 : 0);
