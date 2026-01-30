// plugins/spin-directive.client.ts
import type { DirectiveBinding } from "vue";
import { useSpin } from "@/composables/useSpinLoading";

interface SpinDirectiveOptions {
    show?: boolean;
    text?: string;
    zIndex?: number;
}

type SpinDirectiveValue = boolean | SpinDirectiveOptions | string | null | undefined;

// 定义修饰符类型
interface SpinModifiers {
    fullscreen?: boolean;
    lock?: boolean;
}

const spinInstances = new WeakMap();
const spinStates = new WeakMap();

export default defineNuxtPlugin((nuxtApp) => {
    nuxtApp.vueApp.directive("spin", {
        mounted(el: HTMLElement, binding: DirectiveBinding<SpinDirectiveValue>) {
            updateSpin(el, binding);
        },

        updated(el: HTMLElement, binding: DirectiveBinding<SpinDirectiveValue>) {
            updateSpin(el, binding);
        },

        unmounted(el: HTMLElement) {
            const instance = spinInstances.get(el);
            if (instance) {
                instance.destroy();
                spinInstances.delete(el);
                spinStates.delete(el);
            }
        },
    });

    // 提供全局 $spin 方法
    const globalSpinInstance = useSpin(undefined, { text: "正在处理..." });

    nuxtApp.provide("spin", {
        show: (options?: SpinDirectiveOptions) => {
            globalSpinInstance.show(options);
            return globalSpinInstance;
        },
        hide: () => {
            globalSpinInstance.hide();
        },
    });
});

function updateSpin(el: HTMLElement, binding: DirectiveBinding<SpinDirectiveValue>) {
    const { value, modifiers = {}, arg } = binding;

    // 类型断言修饰符
    const spinModifiers = modifiers as SpinModifiers;

    // 增强的指令值解析
    const parseDirectiveValue = (val: SpinDirectiveValue) => {
        // 处理 null、undefined、false - 隐藏 loading
        if (val === null || val === undefined || val === false) {
            return { isSpinning: false, options: {} };
        }

        // 处理 true - 显示默认 loading
        if (val === true) {
            return { isSpinning: true, options: {} };
        }

        // 处理字符串 - 显示 loading 并设置文本
        if (typeof val === "string") {
            return {
                isSpinning: true,
                options: { text: val },
            };
        }

        // 处理对象配置
        if (val && typeof val === "object") {
            // 支持 { show: true/false, text: '...', zIndex: 1000 } 格式
            if ("show" in val) {
                return {
                    isSpinning: val.show === true,
                    options: {
                        text: val.text,
                        zIndex: val.zIndex,
                    },
                };
            }

            // 兼容旧格式 { text: '...', zIndex: 1000 }
            return {
                isSpinning: true,
                options: val as SpinDirectiveOptions,
            };
        }

        // 默认情况
        return { isSpinning: false, options: {} };
    };

    const { isSpinning, options } = parseDirectiveValue(value);

    // 处理特殊参数（如果需要支持 v-spin:text 这样的用法）
    if (arg === "text" && typeof value === "string") {
        // 如果有现有实例且正在显示，更新文本
        const instance = spinInstances.get(el);
        const currentState = spinStates.get(el);

        if (instance && currentState?.isSpinning) {
            instance.updateText(value);
            return;
        }

        // 否则将文本添加到选项中
        options.text = value;
    }

    // 获取上次的状态
    const lastState = spinStates.get(el) || { isSpinning: false, lastOptions: {} };

    // 如果状态没有变化且正在显示，检查选项是否有变化
    if (lastState.isSpinning === isSpinning && isSpinning) {
        const instance = spinInstances.get(el);
        if (instance) {
            // 检查选项是否有变化
            const optionsChanged = JSON.stringify(options) !== JSON.stringify(lastState.lastOptions);
            if (optionsChanged) {
                if (options.text !== undefined) {
                    instance.updateText(options.text);
                }
                // 如果需要更新其他选项，可以重新显示
                if (options.zIndex !== undefined && options.zIndex !== lastState.lastOptions.zIndex) {
                    instance.show(options);
                }
                // 更新状态
                spinStates.set(el, { isSpinning, lastOptions: { ...options } });
            }
        }
        return;
    }

    // 获取或创建实例
    let instance = spinInstances.get(el);

    if (!instance) {
        const target = spinModifiers.fullscreen ? undefined : el;
        instance = useSpin(target, {
            text: options.text || "正在加载...",
            zIndex: options.zIndex || (spinModifiers.fullscreen ? 9999 : 1000),
        });
        spinInstances.set(el, instance);
    }

    // 控制显示隐藏
    if (isSpinning) {
        instance.show(options);
    } else {
        instance.hide();
    }

    // 更新状态
    spinStates.set(el, { isSpinning, lastOptions: { ...options } });

    // 处理修饰符
    if (spinModifiers.lock) {
        if (isSpinning) {
            el.style.pointerEvents = "none";
            el.style.userSelect = "none";
            el.setAttribute("data-spin-locked", "true");
        } else {
            el.style.pointerEvents = "";
            el.style.userSelect = "";
            el.removeAttribute("data-spin-locked");
        }
    }
}
