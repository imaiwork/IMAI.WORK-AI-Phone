import path from 'path'
import fsExtra from 'fs-extra'
import minimist from 'minimist'
const { existsSync, remove, copy } = fsExtra
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

async function build() {
    if (existsSync(releasePath)) {
        await remove(releasePath)
    }
    console.log(
        `文件正在复制：dist/build/${argv.target} ==> ${releaseRelativePath}`
    )
    try {
        await copyFile(distPath, releasePath)
    } catch (error) {
        console.log(`\n ${error}`)
    }
    console.log(
        `文件已复制：dist/build/${argv.target} ==> ${releaseRelativePath}`
    )
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
