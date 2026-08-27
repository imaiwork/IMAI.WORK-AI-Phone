const path = require("path");
const fs = require("fs");

// 兼容依赖装在「本目录」或「项目根 node_modules」两种布局
function resolveMiniprogramCi() {
    const candidates = [
        path.join(__dirname, "node_modules", "miniprogram-ci"),
        path.join(__dirname, "..", "..", "node_modules", "miniprogram-ci"),
        path.join(__dirname, "..", "node_modules", "miniprogram-ci"),
    ];
    for (const dir of candidates) {
        if (fs.existsSync(path.join(dir, "package.json"))) {
            return require(dir);
        }
    }
    // 最后走默认解析，便于抛出原始错误
    return require("miniprogram-ci");
}

const ci = resolveMiniprogramCi();

let data = process.argv[2];
data = JSON.parse(data);
if (data.length == 0) {
    console.log("参数缺失");
    process.exit(-1);
}

let appid = data.appid;
// 主站默认 mp-weixin；OEM 可传入 projectPath / privateKeyPath 隔离工作区
let projectPath = data.projectPath ? path.resolve(String(data.projectPath)) : path.join(__dirname, "mp-weixin");
let privateKeyPath = data.privateKeyPath
    ? path.resolve(String(data.privateKeyPath))
    : path.join(__dirname, `private.${appid}.key`);
// 团队路径不存在时，回退 ci 根目录 private.{appid}.key（兼容未部署新脚本/未同步的情况）
if (!fs.existsSync(privateKeyPath)) {
    const fallbackKey = path.join(__dirname, `private.${appid}.key`);
    if (fs.existsSync(fallbackKey)) {
        privateKeyPath = fallbackKey;
    }
}
if (!fs.existsSync(privateKeyPath)) {
    console.error(`[upload] 私钥文件不存在: ${privateKeyPath}`);
    process.exit(1);
}
if (!fs.existsSync(projectPath)) {
    console.error(`[upload] 小程序代码目录不存在: ${projectPath}`);
    process.exit(1);
}
let desc = data.desc;
let version = data.version;
const UPLOAD_TIMEOUT_MS = Number(process.env.MNP_UPLOAD_TIMEOUT_MS || 15 * 60 * 1000);
// 编译期「多久没有任何进度事件」判定为卡死。miniprogram-ci 的 worker 被 OOM 杀掉后
// 任务队列不再流动，进程还活着但永远不动，只靠总超时要白等到 UPLOAD_TIMEOUT_MS。
const STALL_TIMEOUT_MS = Number(process.env.MNP_STALL_TIMEOUT_MS || 3 * 60 * 1000);
const STALL_CHECK_INTERVAL_MS = 15 * 1000;

const compileSetting = {
    // es6/es7: 将 JS 编译为 ES5（含 ??、?. 等，对应开发者工具「增强编译」）
    es6: process.env.MNP_ES6 !== "false",
    es7: process.env.MNP_ES7 !== "false",
    // 上传时压缩所有代码，对应于微信开发者工具的 "上传时压缩代码"
    minify: process.env.MNP_MINIFY === "true",
};

// ---------------------------------------------------------------- 日志落盘
// 卡死排查全靠它：PHP 侧只拿得到 stdout，进程被 php-fpm/nginx 掐断后就什么都不剩了。
// 用 appendFileSync 逐条落盘，避免进程被 kill 时丢掉缓冲区里的尾巴。
const logFile = (() => {
    try {
        const dir = path.join(__dirname, "logs");
        fs.mkdirSync(dir, { recursive: true });
        const t = new Date();
        const p = (n) => String(n).padStart(2, "0");
        const stamp = `${t.getFullYear()}${p(t.getMonth() + 1)}${p(t.getDate())}_${p(t.getHours())}${p(t.getMinutes())}${p(t.getSeconds())}`;
        return path.join(dir, `${stamp}_${appid}_upload.log`);
    } catch (err) {
        return "";
    }
})();

function writeLog(line) {
    if (!logFile) {
        return;
    }
    try {
        fs.appendFileSync(logFile, `[${new Date().toISOString()}] ${line}\n`);
    } catch (err) {
        // 日志写不进去不影响上传本身
    }
}

// 打到 stdout 的同时落盘；stdout 会被 PHP 全量读进内存，所以只放关键行
function logBoth(line) {
    writeLog(line);
    console.log(line);
}

function stringifyResult(result) {
    try {
        return JSON.stringify(result || {});
    } catch (err) {
        return String(result);
    }
}

// ------------------------------------------------------- 进度追踪 / 卡死检测
let lastProgressAt = Date.now();
let progressCount = 0;
// id -> 描述，收到 done 就删；卡死时剩下的就是「卡在哪几个文件」
const pendingTasks = new Map();

function trackProgress(progress) {
    if (!progress || typeof progress !== "object") {
        return;
    }
    const id = progress.id;
    const status = progress.status;
    const message = progress.message ? String(progress.message) : "";
    if (id === undefined || id === null) {
        return;
    }
    if (status === "done" || status === "fail") {
        pendingTasks.delete(id);
    } else {
        pendingTasks.set(id, message.slice(0, 120));
    }
}

function describePendingTasks(limit = 10) {
    const items = [];
    for (const [id, message] of pendingTasks) {
        items.push(`  #${id} ${message}`);
        if (items.length >= limit) {
            break;
        }
    }
    const more = pendingTasks.size - items.length;
    if (more > 0) {
        items.push(`  ...另有 ${more} 个任务未完成`);
    }
    return items.join("\n");
}

function getProgressMessage(progress) {
    if (!progress || typeof progress !== "object") {
        return String(progress || "");
    }

    const message = progress.message ? String(progress.message) : "";
    return stringifyResult({
        id: progress.id,
        status: progress.status,
        message: message.length > 500 ? `${message.slice(0, 500)}...` : message,
    });
}

function handleProgressUpdate(progress) {
    lastProgressAt = Date.now();
    progressCount += 1;
    trackProgress(progress);

    const text = getProgressMessage(progress);
    // 全量进入日志文件，方便事后还原卡在哪一步
    writeLog(`progress ${text}`);

    if (
        text.includes("upload") ||
        text.includes("Upload") ||
        text.includes("request url") ||
        text.includes("package") ||
        text.includes("Compile miniprogram") ||
        text.includes("warn") ||
        text.includes("error") ||
        text.includes("done")
    ) {
        console.log(`[upload-progress] ${text}`);
    }
}

// 总超时 + 卡死超时，谁先到算谁
function withTimeout(promise, timeoutMs) {
    let timer = null;
    let stallTimer = null;

    const clear = () => {
        if (timer) {
            clearTimeout(timer);
        }
        if (stallTimer) {
            clearInterval(stallTimer);
        }
    };

    const timeoutPromise = new Promise((_, reject) => {
        timer = setTimeout(() => {
            reject(
                new Error(
                    `[upload] ci.upload 超过 ${Math.ceil(timeoutMs / 1000)} 秒仍未返回，可能停在编译阶段或 miniprogram-ci 未正常 resolve/reject`,
                ),
            );
        }, timeoutMs);

        stallTimer = setInterval(() => {
            const idle = Date.now() - lastProgressAt;
            if (idle < STALL_TIMEOUT_MS) {
                return;
            }
            const detail = describePendingTasks();
            reject(
                new Error(
                    `[upload] 编译已停滞 ${Math.round(idle / 1000)} 秒无任何进度（共收到 ${progressCount} 条进度、${pendingTasks.size} 个任务未完成）。\n` +
                        `这通常是 miniprogram-ci 的编译 worker 被内存打爆后任务队列不再流动。\n` +
                        `可尝试调小并发：COMPILE_THREADS=1，或关闭增强编译/压缩：MNP_ES7=false、MNP_MINIFY=false。\n` +
                        (detail ? `未完成任务：\n${detail}\n` : "") +
                        (logFile ? `完整日志：${logFile}` : ""),
                ),
            );
        }, STALL_CHECK_INTERVAL_MS);
    });

    return Promise.race([promise, timeoutPromise]).finally(clear);
}

// 注意： new ci.Project 调用时，请确保项目代码已经是完整的，避免编译过程出现找不到文件的报错。
async function upload() {
    const project = new ci.Project({
        appid: appid,
        type: "miniProgram",
        projectPath: projectPath,
        privateKeyPath: privateKeyPath,
        ignores: ["node_modules/**/*"],
    });

    try {
        logBoth(
            `[upload] appid: ${appid}, version: ${version}, desc: ${desc}, projectPath: ${projectPath}, privateKeyPath: ${privateKeyPath}`,
        );
        logBoth(
            `[upload] setting: ${stringifyResult(compileSetting)}, COMPILE_THREADS: ${process.env.COMPILE_THREADS || "(默认=CPU核数)"}, 日志: ${logFile || "(未启用)"}`,
        );

        const originalExit = process.exit;
        process.exit = (code = 0) => {
            throw new Error(
                `[upload] miniprogram-ci 在上传完成前调用了 process.exit(${code})，已拦截以避免接口误判成功`,
            );
        };

        let result;
        try {
            result = await withTimeout(
                ci.upload({
                    project,
                    version,
                    desc,
                    setting: compileSetting,
                    onProgressUpdate: handleProgressUpdate,
                }),
                UPLOAD_TIMEOUT_MS,
            );
        } finally {
            process.exit = originalExit;
        }
        logBoth(`[upload] result: ${stringifyResult(result)}`);
        logBoth(
            `UPLOAD_SUCCESS ${stringifyResult({
                appid,
                version,
                desc,
                result,
            })}`,
        );
    } catch (err) {
        const message = err && err.message ? err.message : String(err);
        writeLog(`FAILED ${message}`);
        console.error(message);
        process.exit(1);
    }
}

upload().catch((err) => {
    const message = err && err.message ? err.message : String(err);
    writeLog(`FAILED ${message}`);
    console.error(message);
    process.exit(1);
});
