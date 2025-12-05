const fs = require("fs");
const { spawn } = require("child_process");

class InitializeItem {
    static instance = null;

    constructor() {
        if (InitializeItem.instance) {
            return InitializeItem.instance;
        }
        InitializeItem.instance = this;
    }

    async promptUser(question) {
        return new Promise((resolve, reject) => {
            const readline = require("readline");
            const rl = readline.createInterface({
                input: process.stdin,
                output: process.stdout,
            });
            rl.question(question, (res) => {
                resolve(res);
                rl.close();
            });
        });
    }

    async shouldInstallDependencies() {
        const isInstall = await this.promptUser("是否需要自动帮您安装依赖（y/n）：");
        if (isInstall.toLowerCase() === "y") {
            return true;
        } else if (isInstall.toLowerCase() === "n") {
            return false;
        } else {
            return this.shouldInstallDependencies();
        }
    }

    async installDependencies() {
        return new Promise((resolve, reject) => {
            console.log("开始安装相关依赖...");
            const command = process.platform === "win32" ? "cmd.exe" : "npm";
            const args = process.platform === "win32" ? ["/c", "npm", "install"] : ["install"];
            const installProcess = spawn(command, args);

            installProcess.stdout.on("data", (data) => {
                console.log(data.toString());
            });
            installProcess.stderr.on("data", (data) => {
                console.error(data.toString());
            });
            installProcess.on("close", (code) => {
                if (code !== 0) {
                    reject(new Error(`运行安装依赖命令错误，请查看以下报错信息寻找解决方法`));
                } else {
                    console.log("安装依赖成功！");
                    resolve();
                }
            });
        });
    }

    async runNpmScript(scriptName) {
        return new Promise((resolve, reject) => {
            const command = process.platform === "win32" ? "cmd.exe" : "npm";
            const args = process.platform === "win32" ? ["/c", "npm", "run", scriptName] : ["run", scriptName];
            const runProcess = spawn(command, args);
            runProcess.stdout.on("data", (data) => {
                console.log(data.toString());
            });
            runProcess.stderr.on("data", (data) => {
                console.error(data.toString());
            });
            runProcess.on("close", (code) => {
                if (code !== 0) {
                    reject(new Error(`运行错误，请查看以下报错信息寻找解决方法: ${error.message}`));
                } else {
                    resolve();
                }
            });
        });
    }

    async copyFile(sourceDir, targetDir) {
        return new Promise((resolve, reject) => {
            fs.copyFile(sourceDir, targetDir, (error) => {
                if (error) {
                    reject(error);
                    throw new Error(`复制文件失败： ${error.message}`);
                }
                resolve();
            });
        });
    }

    async writeToFile(filePath, { sourceData, targetData }) {
        return new Promise((resolve, reject) => {
            fs.readFile(filePath, "utf8", (err, data) => {
                if (err) {
                    console.error("读取文件失败：", err);
                    return;
                }
                const modifiedData = data.replace(sourceData, targetData);
                fs.writeFile(filePath, modifiedData, "utf8", (err) => {
                    if (err) {
                        console.error("写入文件错误：", err);
                        return;
                    }
                    resolve();
                });
            });
        });
    }

    async initialize(targetVersion) {
        const currentVersion = process.versions.node;
        if (currentVersion < targetVersion) {
            throw new Error(`你的当前node版本为(${currentVersion})，需要安装目标版本为 ${targetVersion} 以上！！`);
        }

        const shouldInstall = await this.shouldInstallDependencies();
        if (shouldInstall) {
            await this.installDependencies();
        }
        await this.copyFile(".env.example", ".env");
        await this.copyFile(".env.development.example", ".env.development");
        await this.copyFile(".env.production.example", ".env.production");
        const domain = await this.promptUser("请输入您的服务器域名地址：");
        await this.writeToFile(".env.development", {
            sourceData: "NUXT_API_URL=''",
            targetData: `NUXT_API_URL='${domain}'`,
        });
        await this.writeToFile(".env.production", {
            sourceData: "NUXT_API_URL=''",
            targetData: `NUXT_API_URL='${domain}'`,
        });
        this.runNpmScript("dev");
    }

    static getInstance() {
        if (!InitializeItem.instance) {
            InitializeItem.instance = new InitializeItem();
        }
        return InitializeItem.instance;
    }
}

(async () => {
    const initializeItem = InitializeItem.getInstance();
    try {
        await initializeItem.initialize("20.11.0");
    } catch (error) {
        console.error(error.message);
    }
})();

// node init (当前)
// 0. 判断当前设备是否安装指定 node 版本以上
// 1. 一键初始化 询问是否已安装npm run install 依赖，如果没有则自动运行安装依赖
// 2. 复制应用相关配置
// 3. 让用户手动输入相关配置
// 4. 初始化完成 运行开发环境

// node fix (如果init后还是不行则考虑运行此插件修复)
// tips (通常因为插件版本问题，所以这边只需要删除锁定的版本文件和相关依赖重新安装)
// 0. 删除yarn.lock 和package-lock.json
// 1. 删除node_modules 文件夹
// 2. 运行npm install 安装依赖
// 3. 安装以后自动运行开发环境
