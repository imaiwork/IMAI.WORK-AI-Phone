// Run: node scripts/check-chat-mode-events-regression.cjs
const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const { resolve } = require('node:path')
const vm = require('node:vm')
const { test } = require('node:test')
const ts = require('typescript')
const { parse } = require('@vue/compiler-sfc')

const root = resolve(__dirname, '..')
const file = process.argv[2] || resolve(root, 'src/packages/pages/chat/components/workbench-chat-scroll.vue')
const source = parse(readFileSync(file, 'utf8')).descriptor
const ast = (text) => ts.createSourceFile('check.ts', text, ts.ScriptTarget.Latest, true)
function find(node, predicate) {
    if (predicate(node)) return node
    let result
    ts.forEachChild(node, (child) => {
        if (!result) result = find(child, predicate)
    })
    return result
}
const script = ast(source.scriptSetup.content)
const unmount = find(
    script,
    (node) => ts.isCallExpression(node) && node.expression.getText(script) === 'onUnmounted'
)
const close = find(
    script,
    (node) => ts.isVariableDeclaration(node) && node.name.getText(script) === 'chatClose'
)
assert.ok(unmount && close)
const runtime = ast(
    readFileSync(resolve(root, 'node_modules/@dcloudio/uni-mp-vue/dist/vue.runtime.esm.js'), 'utf8')
)
const eventRuntime = ['vOn', 'createInvoker']
    .map((name) => {
        const node = find(runtime, (n) => ts.isFunctionDeclaration(n) && n.name?.text === name)
        assert.ok(node, name)
        return node.getText(runtime)
    })
    .join('\n')

function scenario() {
    const instance = { $ei: 3, ctx: { $mpPlatform: 'mp-weixin', $scope: {} } }
    const sandbox = vm.createContext({
        getCurrentInstance: () => instance,
        isString: (v) => typeof v === 'string',
        isArray: Array.isArray,
        isPromise: (v) => Boolean(v?.then),
        patchMPEvent: () => undefined,
        patchStopImmediatePropagation: (_, fn) => fn,
        callWithAsyncErrorHandling: (fn, _, __, args) => fn(...args),
        bubbles: ['tap'],
        setTimeout: (fn) => fn()
    })
    vm.runInContext(eventRuntime, sandbox)
    let mode = 'chat'
    const closeEvent = sandbox.vOn(() => undefined)
    const emit = () => instance.ctx.$scope[closeEvent]({ type: 'close', detail: {} })
    return {
        switchMode(next) {
            mode = next
            instance.$ei = 3
            // The conditional panels reuse the same native event name for close/exit.
            assert.equal(
                sandbox.vOn(() => {
                    mode = 'chat'
                }),
                closeEvent
            )
        },
        mode: () => mode,
        emit
    }
}

test('legacy unmount close is delivered to the newly rendered panel exit handler', () => {
    const state = scenario()
    const chatClose = vm.runInNewContext(`(${close.initializer.getText(script)})`, {
        emit: state.emit
    })
    state.switchMode('image')
    chatClose()
    assert.equal(state.mode(), 'chat', 'baseline reproduces a mode switch being undone')
})

test('unmount preserves every selected mode and cleans keyboard/timer state', () => {
    for (const mode of ['image', 'map', 'ppt', 'video']) {
        const state = scenario()
        let hidden = 0
        let cleared = 0
        const context = {
            emit: state.emit,
            hideKeyboard: () => hidden++,
            scrollToLowerTimer: 7,
            clearTimeout: (timer) => {
                assert.equal(timer, 7)
                cleared++
            }
        }
        context.chatClose = vm.runInNewContext(`(${close.initializer.getText(script)})`, context)
        const dispose = vm.runInNewContext(`(${unmount.arguments[0].getText(script)})`, context)
        state.switchMode(mode)
        dispose()
        assert.equal(state.mode(), mode)
        assert.equal(hidden, 1)
        assert.equal(cleared, 1)
    }
})

test('the composer has no native catchtap that can be rebound to textarea input', () => {
    const tag = source.template.content.match(/<view\b[^>]*\bchat-bottom-box\b[^>]*>/)?.[0]
    assert.ok(tag)
    assert.doesNotMatch(tag, /@(?:tap|click)\b/)
})
