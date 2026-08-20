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

function stringifyResult(result) {
    try {
        return JSON.stringify(result || {});
    } catch (err) {
        return String(result);
    }
}

function withTimeout(promise, timeoutMs) {
    let timer = null;
    const timeoutPromise = new Promise((_, reject) => {
        timer = setTimeout(() => {
            reject(
                new Error(
                    `[upload] ci.upload 超过 ${Math.ceil(timeoutMs / 1000)} 秒仍未返回，可能停在编译阶段或 miniprogram-ci 未正常 resolve/reject`,
                ),
            );
        }, timeoutMs);
    });

    return Promise.race([promise, timeoutPromise]).finally(() => {
        if (timer) {
            clearTimeout(timer);
        }
    });
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
    const text = getProgressMessage(progress);
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

// 注意： new ci.Project 调用时，请确保项目代码已经是完整的，避免编译过程出现找不到文件的报错。
// const project = new ci.Project({
//     appid: appid,
//     type: 'miniProgram',
//     projectPath: './weapp',
//     privateKeyPath: privateKeyPath,
//     ignores: ['node_modules/**/*'],
// })
//
// ci.upload({
//     project,
//     version,
//     desc,
//     setting: {
//         es6: false,//对应于微信开发者工具的 "es6 转 es5"
//         es7: false,//对应于微信开发者工具的 "增强编译"
//         minify: true,//上传时压缩所有代码，对应于微信开发者工具的 "上传时压缩代码"
//     },
//     onProgressUpdate: console.log,
// })

async function upload() {
    // 注意： new ci.Project 调用时，请确保项目代码已经是完整的，避免编译过程出现找不到文件的报错。
    const project = new ci.Project({
        appid: appid,
        type: "miniProgram",
        projectPath: projectPath,
        privateKeyPath: privateKeyPath,
        ignores: ["node_modules/**/*"],
    });

    try {
        console.log(
            `[upload] appid: ${appid}, version: ${version}, desc: ${desc}, projectPath: ${projectPath}, privateKeyPath: ${privateKeyPath}`,
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
                    setting: {
                        // es6/es7: 将 JS 编译为 ES5（含 ??、?. 等，对应开发者工具「增强编译」）
                        es6: true,
                        es7: true,
                        minify: false, //上传时压缩所有代码，对应于微信开发者工具的 "上传时压缩代码"
                    },
                    onProgressUpdate: handleProgressUpdate,
                }),
                UPLOAD_TIMEOUT_MS,
            );
        } finally {
            process.exit = originalExit;
        }
        console.log(`[upload] result: ${stringifyResult(result)}`);
        console.log(
            `UPLOAD_SUCCESS ${stringifyResult({
                appid,
                version,
                desc,
                result,
            })}`,
        );
    } catch (err) {
        console.error(err && err.message ? err.message : err);
        process.exit(1);
    }
}

upload().catch((err) => {
    console.error(err && err.message ? err.message : err);
    process.exit(1);
});
