<?php
require __DIR__ . '/../app/common/service/TextQualityValidator.php';
use app\common\service\TextQualityValidator as T;

// 模拟 AI 返回异常数据类型（sanitizeArray 有防护吗？）
echo "传入非 string 元素（null）...";
$r = T::sanitizeArray(["Hello", null, "World"]);
echo " ✅ content=".count($r['content'])." warnings=".count($r['warnings'])."\n";

echo "传入整数 0...";
$r = T::sanitizeArray(["Hello", 0, "World"]);
echo " ✅ content=".count($r['content'])." warnings=".count($r['warnings'])."\n";

echo "传入空数组...";
$r = T::sanitizeArray([]);
echo " ✅ content=".count($r['content'])." warnings=".count($r['warnings'])."\n";

echo "全部通过！sanitizeArray 类型安全。\n";
