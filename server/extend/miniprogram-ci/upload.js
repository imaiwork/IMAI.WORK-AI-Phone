const ci = require('miniprogram-ci')
const path = require('path');

let data = process.argv[2];
data = JSON.parse(data);
if(data.length ==  0) {
    console.log('参数缺失');
    process.exit(-1);
}

let appid = data.appid;
let projectPath = path.join(__dirname,'mp-weixin');
let privateKeyPath = path.join(__dirname,`/private.${appid}.key`);
let desc = data.desc;
let version = data.version;

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
        type: 'miniProgram',
        projectPath: projectPath,
        privateKeyPath: privateKeyPath,
        ignores: ['node_modules/**/*'],
    })

    try {
        const result = await ci.upload({
            project,
            version,
            desc,
            setting: {
                es6: true,//对应于微信开发者工具的 "es6 转 es5"
                es7: false,//对应于微信开发者工具的 "增强编译"
                minify: true,//上传时压缩所有代码，对应于微信开发者工具的 "上传时压缩代码"
            },
            onProgressUpdate: console.log,
        })

    } catch (err) {
    }finally {
        process.exit(0);
    }
}

upload()