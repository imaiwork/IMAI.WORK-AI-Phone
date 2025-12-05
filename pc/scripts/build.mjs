import path from 'node:path'
import fsExtra from 'fs-extra'
import dotenv from 'dotenv'
const { copy, existsSync, remove, readdirSync } = fsExtra
const cwd = process.cwd()
dotenv.config()
dotenv.config({ path: `.env.${process.env.NODE_ENV}` })
const isSSR = Boolean(process.env.NUXT_SSR)
//打包发布路径，可能会覆盖重要文件，请谨慎改动
const releaseRelativePath = process.env.NUXT_RELEASE_PATH
const releaseMap = isSSR
    ? ['.output', 'static', 'package.json', '.env', '.env.production'].reduce(
          (prev, item) => {
              prev[item] = item
              return prev
          },
          {}
      )
    : ['.output/public'].reduce((prev, item) => {
          const readDir = readdirSync(item)
          for (const dir of readDir) {
              prev[path.join(item, dir)] = dir
          }
          return prev
      }, {})
const releasePath = path.resolve(cwd, releaseRelativePath)

async function build() {
    if (!releaseRelativePath) {
        throw Error('NUXT_RELEASE_PATH is undefined')
    }
    // console.log(releasePath)
    // if (existsSync(releasePath)) {
    //   await remove(releasePath)
    // }
    console.log(`文件正在复制 ==> ${releaseRelativePath}`)

    try {
        const allTask = Object.keys(releaseMap).map(async (key) => {
            const srcPath = path.resolve(cwd, key)
            const destPath = path.resolve(releasePath, releaseMap[key])
            if (existsSync(srcPath)) {
                if (existsSync(destPath)) {
                    await remove(destPath)
                }
                await copyFile(srcPath, destPath)
            }
        })
        await Promise.all(allTask)
    } catch (error) {
        console.log(`\n ${error}`)
    }
    console.log(`文件已复制 ==> ${releaseRelativePath}`)
}

function copyFile(sourceDir, targetDir) {
    return new Promise((resolve, reject) => {
        copy(sourceDir, targetDir, { overwrite: true }, (err) => {
            if (err) {
                reject(err)
            } else {
                resolve()
            }
        })
    })
}

build()
