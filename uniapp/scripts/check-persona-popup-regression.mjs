// Run after a safe MP build: node scripts/check-persona-popup-regression.mjs [dist/build/mp-weixin]
// Read-only: no application imports, requests, account data, or generated-file writes.
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import vm from 'node:vm'
import ts from 'typescript'
import { parse } from '@vue/compiler-sfc'
import { isProxy } from 'vue'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const output = resolve(root, process.argv[2] || 'dist/build/mp-weixin')
const page = 'ai_modules/person/pages/detail'
const read = (file) => readFileSync(file, 'utf8')
const ast = (code) => ts.createSourceFile('check.ts', code, ts.ScriptTarget.Latest, true)
const find = (node, predicate) => {
    if (predicate(node)) return node
    let result
    ts.forEachChild(node, (child) => {
        if (!result) result = find(child, predicate)
    })
    return result
}
const required = (node, predicate, label) => {
    const result = find(node, predicate)
    assert.ok(result, `Missing ${label}`)
    return result
}
const sourceScript = (file) => parse(read(file)).descriptor.scriptSetup.content
const parentSource = read(resolve(root, 'src', page, 'detail.vue'))
const childSource = read(resolve(root, 'src', page, 'components/materials-tab.vue'))
assert.doesNotMatch(parentSource, /@popup-change=/)
assert.doesNotMatch(childSource, /emit\(['"]popup-change['"]|defineEmits/)

const parentAst = ast(sourceScript(resolve(root, 'src', page, 'detail.vue')))
const getVariable = (name) =>
    required(
        parentAst,
        (node) => ts.isVariableDeclaration(node) && node.name.getText(parentAst) === name,
        name
    )
assert.match(getVariable('handleSetupBar').getText(parentAst), /await handleSetupStep\(key\)/)
assert.match(getVariable('handleSetupStep').getText(parentAst), /handleOpenComposeUpload\(\)/)
assert.match(getVariable('handleSetupStep').getText(parentAst), /handleManageSetupMaterials\(\)/)

// Use the installed MP runtime, not a reimplementation of its event allocator or props cache.
const runtimeAst = ast(
    read(resolve(root, 'node_modules/@dcloudio/uni-mp-vue/dist/vue.runtime.esm.js'))
)
const runtimeNames = [
    'vOn',
    'createInvoker',
    'guardReactiveProps',
    'renderProps',
    'findComponentPropsData'
]
const runtimeFunctions = runtimeNames
    .map((name) =>
        required(
            runtimeAst,
            (node) => ts.isFunctionDeclaration(node) && node.name?.text === name,
            name
        ).getText(runtimeAst)
    )
    .join('\n')
const instance = {
    uid: 1,
    __counter: 0,
    $ei: 13,
    ctx: { $mpPlatform: 'mp-weixin', $scope: {} }
}
const sandbox = vm.createContext({
    getCurrentInstance: () => instance,
    isString: (value) => typeof value === 'string',
    isArray: Array.isArray,
    isPromise: (value) => Boolean(value?.then),
    isProxy,
    InternalObjectKey: '__vInternal',
    extend: Object.assign,
    propsCaches: {},
    patchMPEvent: () => undefined,
    patchStopImmediatePropagation: (_, handler) => handler,
    callWithAsyncErrorHandling: (handler, _, __, args) => handler(...args),
    bubbles: ['tap'],
    setTimeout: (handler) => handler()
})
vm.runInContext(runtimeFunctions, sandbox)

// Minimal slice of the old compiled render: conditional popupChange before the footer CTA.
// The full old artifact independently reproduced this exact e13 -> e14 -> e13 transition.
let primaryCalls = 0
const primary = () => {
    primaryCalls += 1
}
const oldPopupEvent = sandbox.vOn(() => undefined)
assert.equal(oldPopupEvent, 'e13')
assert.equal(sandbox.vOn(primary), 'e14')
instance.$ei = 13
assert.equal(sandbox.vOn(primary), oldPopupEvent)
instance.ctx.$scope[oldPopupEvent]({
    type: 'popupChange',
    detail: { __args__: [false] }
})
assert.equal(primaryCalls, 1, 'Legacy baseline must reproduce the erroneous CTA')
primaryCalls = 0

// The actual parent callback must survive MP's JS-side props cache, including a shallow copy.
const action = required(
    getVariable('materialsTabActions'),
    (node) => ts.isPropertyAssignment(node) && node.name.getText(parentAst) === 'popupChange',
    'popupChange action'
)
const overlay = { value: false }
const callbackContext = vm.createContext({ materialsOverlayVisible: overlay })
vm.runInContext(
    ts.transpileModule(`globalThis.callback = ${action.initializer.getText(parentAst)}`, {
        compilerOptions: { target: ts.ScriptTarget.ES2020 }
    }).outputText,
    callbackContext
)
const actions = { popupChange: callbackContext.callback }
const pointer = sandbox.renderProps({ actions, __vInternal: true })
const cachedProps = sandbox.findComponentPropsData(pointer)
assert.equal(cachedProps.actions.popupChange, actions.popupChange)

// Check and execute the new compiled lifecycle callbacks (works with minified build output).
const compiledChild = read(resolve(output, page, 'components/materials-tab.js'))
const compiledParent = read(resolve(output, page, 'detail.js'))
const markup = read(resolve(output, page, 'detail.wxml'))
assert.doesNotMatch(markup, /bindpopupChange/)
assert.doesNotMatch(compiledChild, /["']popup-change["']/)
assert.match(compiledParent, /popupChange:/)
const childAst = ast(compiledChild)
const lifecycle = (name) =>
    required(
        childAst,
        (node) =>
            ts.isCallExpression(node) &&
            ts.isPropertyAccessExpression(node.expression) &&
            node.expression.name.text === name &&
            node.getText(childAst).includes('.actions.popupChange'),
        name
    )
const watcher = lifecycle('watch')
const unmount = lifecycle('onUnmounted')
const access = required(
    watcher,
    (node) => ts.isPropertyAccessExpression(node) && node.name.text === 'popupChange',
    'compiled callback access'
)
const propsName = access.expression.expression.getText(childAst)
const childContext = vm.createContext({ [propsName]: cachedProps })
const notify = vm.runInContext(`(${watcher.arguments[1].getText(childAst)})`, childContext)
const dispose = vm.runInContext(`(${unmount.arguments[0].getText(childAst)})`, childContext)
assert.equal(
    vm.runInContext(`(${watcher.arguments[2].getText(childAst)})`, childContext).immediate,
    true
)
notify(false)
assert.equal(overlay.value, false)
notify(true)
assert.equal(overlay.value, true)
dispose()
assert.equal(overlay.value, false, 'Unmount must restore the parent footer')
assert.equal(primaryCalls, 0, 'Visibility reset must never dispatch the native CTA event')
instance.ctx.$scope[oldPopupEvent]({
    type: 'tap',
    target: { dataset: { eventsync: 'true' } }
})
assert.equal(primaryCalls, 1, 'The deliberate CTA must remain functional')

console.log('PASS legacy MP event collision reproduced; callback props preserve identity')
console.log(
    'PASS source/native binding removed; compiled visibility/unmount reset bypasses native events'
)
console.log('PASS explicit setup/upload CTA retained; output:', output)
