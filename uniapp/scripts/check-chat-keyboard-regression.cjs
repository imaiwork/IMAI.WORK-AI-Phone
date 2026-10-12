// Run: node scripts/check-chat-keyboard-regression.cjs [path/to/mp-weixin]
const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const { resolve } = require('node:path')
const vm = require('node:vm')
const { test } = require('node:test')
const ts = require('typescript')
const vue = require('vue')

function createKeyboard() {
    let now = 0
    let timerId = 0
    let heightChanged
    const timers = new Map()
    const mounted = []
    const unmounted = []
    const runtime = {
        ...vue,
        onMounted: (fn) => mounted.push(fn),
        onUnmounted: (fn) => unmounted.push(fn)
    }
    const context = {
        ...runtime,
        uni: {
            getSystemInfoSync: () => ({ uniPlatform: 'mp-weixin' }),
            onKeyboardHeightChange: (fn) => {
                heightChanged = fn
            },
            hideKeyboard: () => undefined
        },
        setTimeout: (fn, delay) => {
            const id = ++timerId
            timers.set(id, { fn, at: now + delay })
            return id
        },
        clearTimeout: (id) => timers.delete(id)
    }
    const load = (file) => {
        const exports = {}
        const path = process.argv[2]
            ? resolve(process.argv[2], file.replace(/\.ts$/, '.js'))
            : resolve(__dirname, '../src', file)
        const source = readFileSync(path, 'utf8')
        const { outputText } = ts.transpileModule(source, {
            compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2020 }
        })
        vm.runInNewContext(outputText, {
            ...context,
            exports,
            require: () => ({ ...runtime, index: context.uni })
        })
        return exports
    }
    const keyboardModule = load('hooks/useKeyboardHeight.ts')
    const toolbarModule = load('packages/pages/chat/composables/useToolbarRevealGuard.ts')
    const keyboard = (keyboardModule.default || keyboardModule.useKeyboardHeight)()
    const toolbar = toolbarModule.useToolbarRevealGuard(
        vue.computed(() => keyboard.dynamicHeight.value > 0)
    )
    mounted.forEach((fn) => fn())
    return {
        ...keyboard,
        ...toolbar,
        focus: keyboardModule.cancelKeyboardClosing,
        height: (height) => heightChanged({ height }),
        unmount: () => unmounted.forEach((fn) => fn()),
        advance: (ms) => {
            const end = now + ms
            while (timers.size) {
                const next = [...timers].sort((a, b) => a[1].at - b[1].at)[0]
                if (!next || next[1].at > end) break
                now = next[1].at
                timers.delete(next[0])
                next[1].fn()
            }
            now = end
        }
    }
}

test('toolbar blocks taps while typing and recovers after keyboard closes', () => {
    const state = createKeyboard()
    let calls = 0
    state.height(300)
    state.guardToolbarAction(() => calls++)
    assert.equal(calls, 0)
    state.height(0)
    state.advance(600)
    state.guardToolbarAction(() => calls++)
    assert.equal(calls, 1)
    state.unmount()
})

test('a late nonzero callback after close must not permanently disable the toolbar', () => {
    const state = createKeyboard()
    state.height(300)
    state.hideKeyboard()
    state.height(0)
    state.advance(60)
    state.height(180)
    state.advance(1000)
    assert.equal(state.dynamicHeight.value, 0)
    let calls = 0
    state.guardToolbarAction(() => calls++)
    assert.equal(calls, 1, 'model, mount and mode actions must become available again')
    state.unmount()
})

test('an intentional refocus during close protection can open the keyboard immediately', () => {
    const state = createKeyboard()
    state.height(300)
    state.hideKeyboard()
    state.height(0)
    state.advance(60)
    state.focus()
    state.height(300)
    assert.equal(state.dynamicHeight.value, 300)
    state.advance(1000)
    assert.equal(state.showToolbar.value, false)
    state.height(0)
    state.advance(600)
    assert.equal(state.showToolbar.value, true)
    state.unmount()
})

test('keyboard callbacks after the last consumer unmounts cannot retain stale height', () => {
    const state = createKeyboard()
    state.height(300)
    state.unmount()
    state.height(180)
    assert.equal(state.dynamicHeight.value, 0)
})
