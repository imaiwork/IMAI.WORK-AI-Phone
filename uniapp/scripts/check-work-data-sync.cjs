// Run: node scripts/check-work-data-sync.cjs (no network or live billing).
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const assert = require('node:assert/strict')
const ts = require('typescript')
const vue = require('vue')
const { parse } = require('@vue/compiler-sfc')
const root = path.resolve(__dirname, '..')
const file = 'src/packages/pages/effect_statistics/effect_statistics.vue'
const pending = () => {
    let resolve, reject
    const promise = new Promise((yes, no) => { resolve = yes; reject = no })
    return { promise, resolve, reject }
}
const flush = () => new Promise(setImmediate)
const calls = []
let read = async () => ({ bound: true, hasData: false })
let save = async (params) => ({ auto_sync_enabled: params.auto_sync_enabled })
const api = {
    getWorkDataPersonas: async () => ({ list: [
        { id: 1, name: '人设一', auto_sync_enabled: 0, accounts: { douyin: [{ account: 'demo' }] } },
        { id: 2, name: '人设二', auto_sync_enabled: 0, accounts: {} }
    ], cost: { score: 50, unit: '算力/次' } }),
    getWorkDataAnalytics: async (params) => { calls.push(params); return read(params) },
    setWorkDataSyncSettings: (params) => save(params)
}
function evaluate(source, imports = {}) {
    const exports = {}
    const compiled = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS } }).outputText
    vm.runInNewContext(compiled, { exports, require: (name) => {
        if (imports[name]) return imports[name]
        if (name === 'vue') return vue
        if (name.endsWith('.svg')) return ''
        throw Error(`Unexpected import ${name}`)
    }, ref: vue.ref, computed: vue.computed, onLoad: () => {}, uni: { $u: { toast() {} } } })
    return exports
}
const enums = evaluate(fs.readFileSync(path.join(root, 'src/packages/pages/effect_statistics/enums.ts'), 'utf8'))
const lock = evaluate(fs.readFileSync(path.join(root, 'src/hooks/useLockFn.ts'), 'utf8'))
const script = parse(fs.readFileSync(path.join(root, file), 'utf8')).descriptor.scriptSetup.content
const page = evaluate(script + '\nexport { loadPersonas, loadAnalytics, handleSyncChange, handlePersona, handleRefresh, runRefresh, personas, savingSync, analytics, loading, showRefreshConfirm }', {
    '@/api/work_data': api, '@/hooks/useLockFn': lock, './enums': enums,
    './utils': { formatUpdateTime: () => '' }
})
;(async () => {
    await page.loadPersonas()
    assert.equal(calls[0].refresh, 0)
    assert.equal(page.analytics.value, null)
    assert.equal(page.personas.value[0].auto_sync_enabled, false)
    page.handleRefresh()
    assert.equal(page.showRefreshConfirm.value, true)
    await page.runRefresh()
    assert.equal(calls.at(-1).refresh, 1)
    console.log('PASS 关闭状态仍能确认费用后手动刷新')

    const saving = pending()
    let saves = 0
    save = () => { saves++; return saving.promise }
    const work = page.handleSyncChange(true)
    assert.equal(page.savingSync.value, true)
    await page.handleSyncChange(true)
    assert.equal(saves, 1)
    page.handlePersona(2)
    saving.resolve({ auto_sync_enabled: true })
    await work
    assert.equal(page.personas.value[0].auto_sync_enabled, true)
    assert.equal(page.personas.value[1].auto_sync_enabled, false)
    assert.equal(page.savingSync.value, false)
    console.log('PASS 防重复保存，切换人设后响应不会改错开关')

    save = async () => { throw Error('save failed') }
    await page.handleSyncChange(true)
    assert.equal(page.personas.value[1].auto_sync_enabled, false)
    assert.equal(page.savingSync.value, false)
    console.log('PASS 保存失败保留原状态并解除加载')

    const oldRead = pending()
    read = () => oldRead.promise
    page.handlePersona(1)
    page.handlePersona(2)
    oldRead.resolve({ items: [{ title: '旧人设数据' }] })
    await flush()
    assert.equal(page.analytics.value, null)
    assert.equal(page.loading.value, false)
    page.handleRefresh()
    assert.equal(page.showRefreshConfirm.value, false)
    console.log('PASS 无绑定账号不弹扣费确认，旧请求不会覆盖新页面')
})().catch((error) => { console.error(error); process.exitCode = 1 })
