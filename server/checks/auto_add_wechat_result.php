<?php
// 使用真实 ORM + 内存 SQLite；不启动应用、不加载 .env、不连接业务数据库。
require ($argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php');
require_once dirname(__DIR__) . '/app/common/model/sv/SvAddWechatRecord.php';
require_once dirname(__DIR__) . '/app/common/service/sv/AutoAddWechatResultService.php';
require_once dirname(__DIR__) . '/app/common/workerman/rpa/handlers/wechat/AddWechatTaskNoticeHandler.php';

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDeviceTask;
use app\common\service\sv\AutoAddWechatResultService as Results;

$db = new think\DbManager();
$db->setConfig([
    'default' => 'test',
    'connections' => ['test' => [
        'type' => 'sqlite', 'database' => ':memory:', 'prefix' => 'la_',
        'fields_strict' => false, 'trigger_sql' => false,
    ]],
]);
$conn = $db->connect();
$conn->execute('CREATE TABLE la_sv_device_task (id INTEGER PRIMARY KEY, user_id INTEGER, device_code TEXT, auto_type INTEGER, task_type INTEGER, status INTEGER, remark TEXT, end_time INTEGER, update_time INTEGER, delete_time INTEGER)');
$conn->execute('CREATE TABLE la_sv_add_wechat_record (id INTEGER PRIMARY KEY, user_id INTEGER, device_code TEXT, reg_wechat TEXT, wechat_no TEXT, exec_task_id INTEGER, dy_search_task_id INTEGER DEFAULT 0, status INTEGER, result TEXT, image TEXT, update_time INTEGER, add_time INTEGER, delete_time INTEGER)');
$conn->execute('CREATE TABLE la_ai_wechat (id INTEGER PRIMARY KEY, wechat_id TEXT, update_time INTEGER)');
$conn->execute('CREATE TABLE la_ai_wechat_log (id INTEGER PRIMARY KEY, user_id INTEGER, wechat_id TEXT, friend_id TEXT, log_type INTEGER, create_time INTEGER, update_time INTEGER)');
$now = 1800000000;
function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
function task(int $id, int $end, int $auto = 1): void {
    global $conn;
    $conn->name('sv_device_task')->insert(['id' => $id, 'user_id' => 10, 'device_code' => 'test-device', 'auto_type' => $auto, 'task_type' => 25, 'status' => 2, 'remark' => '加微任务完成', 'end_time' => $end]);
}
function record(int $id, int $taskId, int $status = 2, array $extra = []): void {
    global $conn, $now;
    $conn->name('sv_add_wechat_record')->insert(array_merge(['id' => $id, 'user_id' => 10, 'device_code' => 'test-device', 'reg_wechat' => 'test-contact-' . $id, 'exec_task_id' => $taskId, 'status' => $status, 'result' => '执行中', 'update_time' => $now - 600], $extra));
}
function row(int $id): array {
    global $conn;
    return $conn->name('sv_add_wechat_record')->where('id', $id)->find();
}
task(100, $now - 301);
task(101, $now - 299);
task(102, $now - 1000, 0);
record(1, 100);
record(2, 100, 1, ['result' => '添加请求成功']);
record(3, 101);
record(4, 102);
record(5, 100, 2, ['delete_time' => $now - 10]);
record(6, 100, 2, ['user_id' => 11]);
record(7, 100, 2, ['dy_search_task_id' => 10]);
record(8, 100, 2, ['update_time' => $now - 10]);
record(9, 100, 4);
record(10, 100, 0, ['result' => '用户不存在']);

check(Results::expire($now, 'another-device') === 0, '按设备启用时不修改其他设备');
check(Results::expire($now, 'test-device') === 1, '仅超时的自动加微明细结束等待');
check((int)row(1)['status'] === SvAddWechatRecord::STATUS_RESULT_UNKNOWN && row(1)['add_time'] === null, '结果未知不伪造成功或添加时间');
check(row(2)['result'] === '添加请求成功', '保留已成功结果');
foreach ([3, 4, 5, 6, 7, 8] as $id) check((int)row($id)['status'] === 2, "宽限期/手动/软删除/用户隔离/抖音/新执行记录 $id 不误改");
check(Results::expire($now) === 0, '重复扫描幂等');
$stale = SvAddWechatRecord::find(1);
$result = ['status' => 1, 'result' => '添加请求成功', 'add_time' => $now + 1, 'update_time' => $now + 1];
check(Results::acceptResult($stale, $result), '超时后迟到的结果可正常落库');
check(!Results::acceptResult($stale, ['status' => 0, 'result' => '用户不存在']), '重复失败回调不能覆盖已成功结果');
check((int)row(1)['status'] === 1, '成功状态保持不变');
record(11, 100);
$oldAttempt = SvAddWechatRecord::find(11);
$conn->name('sv_add_wechat_record')->where('id', 11)->update(['exec_task_id' => 101]);
check(!Results::acceptResult($oldAttempt, $result), '执行任务发生改变时不写入旧结果');
$task = SvDeviceTask::find(100);
check(str_contains(Results::taskRemark($task), '结果待确认'), '父任务不能把未回传明细显示为全部完成');
$conn->name('sv_add_wechat_record')->where('id', 'in', [7, 8])->update(['status' => 0]);
check(Results::taskRemark($task) === '加微时段已结束，结果已返回', '终态结果齐全后显示已返回');
task(103, $now - 1000);
check(Results::taskRemark(SvDeviceTask::find(103)) === '加微时段已结束，无下发记录', '无明细不能误报添加成功');

function generate_unique_task_id(): string { return 'test-only'; }
class BrokenScreenshotTool extends app\common\workerman\rpa\Tool\ToolUtil {
    public function saveBase64ToImage(string $content, string $code, string $type = 'ai'): string {
        throw new RuntimeException('test image storage unavailable');
    }
}
class TestCallbackHandler extends app\common\workerman\rpa\handlers\wechat\AddWechatTaskNoticeHandler {
    public function __construct() { $this->toolUtil = new BrokenScreenshotTool(); }
    public function setLog(array|string $content, $level = 'info') {}
}
record(12, 103, SvAddWechatRecord::STATUS_RESULT_UNKNOWN, ['wechat_no' => 'test-wechat']);
$callback = new ReflectionMethod(TestCallbackHandler::class, 'setAddWechatTaskNotice');
$callback->setAccessible(true);
$handler = new TestCallbackHandler();
$callback->invoke($handler, ['isManual' => 0, 'recordId' => 12, 'status' => 0, 'image' => 'test']);
check((int)row(12)['status'] === 1 && row(12)['result'] === '添加请求成功', '真实回调入口在截图失败时仍保存加微结果');
$callback->invoke($handler, ['isManual' => 0, 'recordId' => 12, 'status' => 1]);
check((int)row(12)['status'] === 1 && $conn->name('ai_wechat_log')->count() === 1, '重复回调不覆盖结果也不重复记日志');

record(13, 103, SvAddWechatRecord::STATUS_RESULT_UNKNOWN, ['update_time' => time() - 20]);
record(14, 103, 1, ['result' => '添加请求成功', 'update_time' => time() - 20]);
record(15, 103, SvAddWechatRecord::STATUS_RESULT_UNKNOWN, ['update_time' => time() + 20]);
check(Results::queueRetry(10, 'test-device', [13, 14, 15], time(), time() + 600) === 0, '预约时间之前不提前入队');
check(Results::queueRetry(11, 'test-device', [13], time(), time() - 1) === 0, '不能重试其他用户的记录');
check(Results::queueRetry(10, 'another-device', [13], time(), time() - 1) === 0, '不能重试其他设备的记录');
check(Results::queueRetry(10, 'test-device', [13, 14, 15], time(), time() - 1) === 1, '到点仅将指定且仍待确认的原尝试放回队列');
check((int)row(13)['status'] === 4 && row(13)['result'] === Results::QUEUED_RETRY_RESULT, '重试记录可被正常加微任务选取');
record(16, 103, 4);
$first = SvAddWechatRecord::alias('r')->where('r.id', 'in', [13, 16])->where('r.status', 4)
    ->orderRaw('CASE WHEN r.result = ? THEN 0 ELSE 1 END ASC', [Results::QUEUED_RETRY_RESULT])
    ->order('r.id', 'desc')->value('r.id');
check((int)$first === 13, '已预约的旧记录优先于新采集记录');
check(Results::queueRetry(10, 'test-device', [13, 14, 15], time(), time() - 1) === 0, '重复触发预约不重复入队');
check(Results::acceptResult(SvAddWechatRecord::find(13), $result), '排队尚未重发时迟到成功可取消重试');
check((int)row(13)['status'] === 1, '迟到成功后不再被待添加队列选取');
echo "All auto add WeChat result checks passed.\n";
