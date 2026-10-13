<?php

namespace app\common\service\wechat;

/**
 * miniprogram-ci 上传脚本的执行参数
 *
 * 单独抽出来是因为这几个参数决定了上传会不会卡死，主站(MnpSettingsLogic)和
 * 团队 OEM(TeamLogic) 两条路径必须保持一致，改一处漏一处就会复发。
 *
 * Class MnpCiService
 * @package app\common\service\wechat
 */
class MnpCiService
{
    /**
     * 编译线程数默认值
     *
     * miniprogram-ci 的线程池默认取 os.cpus()(见 dist/modules/corecompiler/original/
     * workerThread/getWorkerPoolSize.js)，且 worker 走 worker_threads 共享同一进程堆。
     * 小程序包里的 echarts.min.js(约 1MB、单行 93 万字符)这类已压缩的第三方文件，
     * 单个走 babel+terser 峰值就要 700MB 以上，8 路并发必然打爆默认堆；
     * worker 被杀之后 WorkerManager._run() 拿不到 taskDone 事件，任务队列不再流动，
     * 表现为进程还活着但永久卡住(不是慢，是死锁)。
     */
    private const DEFAULT_COMPILE_THREADS = 1;

    /**
     * node 老生代堆上限(MB)，0 = 不传 --max-old-space-size
     *
     * 默认不传：node 自己的默认上限是按机器内存推算的(如 8G 机器约 2096MB)，
     * 手动调大反而有害——2026-08-25 在 znt 生产机实测，传 4096 让 V8 迟迟不 GC，
     * 涨到 2.28GB 被内核 OOM killer 直接打死(该机可用内存仅约 2.4G 且 swap=0)。
     * 只有在确认机器内存充裕时才通过 .env 显式调大。
     */
    private const DEFAULT_NODE_HEAP_MB = 0;

    /**
     * 经 NODE_OPTIONS 下发的堆上限(MB)，0 = 不下发
     *
     * COMPILE_THREADS 只能约束 miniprogram-ci 的 worker_threads 线程池
     * (dist/.../workerThread/workerManager.js)；真正吃内存的是
     * dist/.../workerThread/childprocessManager.js —— 它按 `2 * os.cpus().length`
     * 把并发任务塞进一个 fork 出来的独立 node 子进程，且无任何开关可调。
     * 命令行的 --max-old-space-size 管不到 fork 出的子进程，只有 NODE_OPTIONS
     * 会随环境变量被继承进去，是唯一能压住它的手段。
     * 2026-08-25 实测：不设时该子进程涨到 2.4GB 被内核 OOM killer 打死
     * (COMPILE_THREADS=1 和 2 的 RSS 几乎一致，印证它不受线程数影响)。
     */
    private const DEFAULT_CHILD_HEAP_MB = 1536;

    /**
     * 上传时是否压缩代码(对应开发者工具的「上传时压缩代码」)
     *
     * 必须开：es6/es7 转译会把 JS 显著撑大(babel 塞辅助函数 + 语法展开)，
     * 不压缩直接撞微信的分包 2048KB 上限。2026-08-25 实测 znt 的 /packages/
     * 分包磁盘上实际内容约 1607KB，不压缩时微信量到 2401KB 被拒(80200)。
     * 关掉只应作为排查内存问题时的临时手段，且需确认机器有 swap。
     */
    private const DEFAULT_MINIFY = true;

    /**
     * 判定残留文件的时间容差(秒)
     *
     * 一次解压产出的全部文件 mtime 落在同一批次内，但慢机器上 PHP 逐个写 2800 个文件
     * 可能拖好几分钟(2026-09-24 huoke 实测 7 分钟)，容差必须远大于这个量级；
     * 而残留文件通常是几天到几个月前的版本留下的，1 小时足够把两者分开。
     */
    private const STALE_GRACE_SECONDS = 3600;

    /**
     * @notes 清掉小程序代码目录里上一版残留的文件
     *
     * 2026-09-28 之前后台「上传小程序代码」是把 zip 解压覆盖到旧目录上、从不清空，
     * 被删掉/挪走的页面会一直躺在目录里；微信按目录实际文件算分包体积(不看 app.json)，
     * 存量站点多个版本积累下来直接撞 2048KB 上限。UploadController 已改成解压前清空，
     * 这里是给「zip 早已解压、只点一键上传」的存量站点兜底，不用再登服务器手动删。
     *
     * 以 app.js 的 mtime 为本次解压批次的基准(它在解压时写入、之后不会再被改写，
     * 而 app.json / project.config.json / config/index.js 上传前都会被重写，不能当基准)。
     *
     * @param string $projectDir mp-weixin 目录(绝对路径)
     * @return int 删除的文件数
     */
    public static function pruneStaleFiles(string $projectDir): int
    {
        $anchor = rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.js';
        if (!is_dir($projectDir) || !is_file($anchor)) {
            return 0;
        }
        $threshold = filemtime($anchor) - self::STALE_GRACE_SECONDS;
        $deleted = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isFile()) {
                if ($item->getMTime() < $threshold && @unlink($item->getPathname())) {
                    $deleted++;
                }
            } elseif ($item->isDir()) {
                // 只会删空目录，非空的 rmdir 直接失败
                @rmdir($item->getPathname());
            }
        }
        if ($deleted > 0) {
            \think\facade\Log::info("[mnp] 一键上传前清理上一版残留文件 {$deleted} 个: {$projectDir}");
        }
        return $deleted;
    }

    /**
     * @notes 拼装执行 upload.js 的 shell 命令(带并发与内存限制)
     * @param string $scriptPath upload.js 路径(相对或绝对，调用方自行保证可达)
     * @param string $jsonArg    传给脚本的 JSON 参数
     * @param string $prefix     需要前置的命令片段，如 'cd xxx && NODE_PATH=yyy'
     * @return string
     */
    public static function buildUploadCommand(string $scriptPath, string $jsonArg, string $prefix = ''): string
    {
        $threads = (int)env('MNP.COMPILE_THREADS', self::DEFAULT_COMPILE_THREADS);
        $threads = max(1, $threads);
        $heapMb = (int)env('MNP.NODE_HEAP_MB', self::DEFAULT_NODE_HEAP_MB);
        $childHeapMb = (int)env('MNP.CHILD_HEAP_MB', self::DEFAULT_CHILD_HEAP_MB);
        $minify = (bool)env('MNP.MINIFY', self::DEFAULT_MINIFY);

        $command = $prefix !== '' ? rtrim($prefix) . ' ' : '';
        $command .= 'MNP_MINIFY=' . ($minify ? 'true' : 'false')
            . ' COMPILE_THREADS=' . $threads
            . ($childHeapMb > 0
                ? ' NODE_OPTIONS=' . escapeshellarg('--max-old-space-size=' . max(512, $childHeapMb))
                : '')
            . ' node'
            . ($heapMb > 0 ? ' --max-old-space-size=' . max(512, $heapMb) : '')
            . ' ' . $scriptPath
            . ' ' . escapeshellarg($jsonArg)
            . ' 2>&1';

        return $command;
    }
}
