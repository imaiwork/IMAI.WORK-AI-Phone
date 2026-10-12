import path from 'path'
import fsExtra from 'fs-extra'
import minimist from 'minimist'
const { existsSync, remove, copy, readJson, writeJson } = fsExtra
const cwd = process.cwd()
const argv = minimist(process.argv.slice(2), {
    alias: {
        target: 't',
        output: 'o'
    }
})
//打包发布路径，谨慎改动
const releaseRelativePath = `../server/public/${argv.output}`
const distPath = path.resolve(cwd, `dist/build/${argv.target}`)
const releasePath = path.resolve(cwd, releaseRelativePath)

async function disableWeixinUnusedFileFilter(projectDir) {
    if (argv.target !== 'mp-weixin') return
    for (const name of ['project.config.json', 'project.private.config.json']) {
        const file = path.resolve(projectDir, name)
        if (!existsSync(file)) continue
        const json = await readJson(file)
        json.setting = json.setting || {}
        json.setting.ignoreDevUnusedFiles = false
        json.setting.ignoreUploadUnusedFiles = false
        await writeJson(file, json, { spaces: 4 })
        console.log(`已关闭微信无依赖文件过滤：${name}`)
    }
}

async function build() {
    await disableWeixinUnusedFileFilter(distPath)
    if (existsSync(releasePath)) {
        await remove(releasePath)
    }
    console.log(`文件正在复制：dist/build/${argv.target} ==> ${releaseRelativePath}`)
    try {
        await copyFile(distPath, releasePath)
    } catch (error) {
        console.log(`\n ${error}`)
    }
    console.log(`文件已复制：dist/build/${argv.target} ==> ${releaseRelativePath}`)
}

function copyFile(sourceDir, targetDir) {
    return new Promise((resolve, reject) => {
        copy(sourceDir, targetDir, (err) => {
            if (err) {
                reject(err)
            } else {
                resolve()
            }
        })
    })
}

build()
