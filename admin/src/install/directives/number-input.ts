/**
 * 数字输入框
 *
 * 功能：
 * 1. 限制输入为数字和小数点。
 * 2. 在用户输入时提供即时过滤，在失焦时进行最终格式化。
 * 3. 支持最大值、最小值和自定义小数位数。
 *
 * 使用方法：
 * <ElInput v-model="formData.keyword" v-number-input="{ max: 100, min: 0, decimal: 2 }" />
 *
 * 参数 (options):
 * - max?: number - 允许的最大值
 * - min?: number - 允许的最小值 (如需正数，请设置 min: 0)
 * - decimal?: number - 允许的小数位数 (默认为 0，即整数)
 */
import { nextTick } from "vue";
import type { Directive } from "vue";

// 扩展 HTMLElement 类型，添加 _numberInput 属性
declare global {
    interface HTMLElement {
        _numberInput?: {
            options: NumberInputOptions;
            handlers: Record<string, EventListener>;
        };
    }
}

interface NumberInputOptions {
    max?: number;
    min?: number;
    decimal?: number;
}

const formatValue = (value: string, options: NumberInputOptions): string => {
    if (value === "" || value === "-") return "";

    const { max, min, decimal = 0 } = options;
    let numValue = parseFloat(value);

    if (isNaN(numValue)) return "";

    if (min !== undefined && numValue < min) {
        numValue = min;
    }
    if (max !== undefined && numValue > max) {
        numValue = max;
    }

    const formatted = numValue.toFixed(decimal);

    // 如果小数点后都是0，则显示为整数
    if (decimal > 0 && Number(formatted) % 1 === 0) {
        return Number(formatted).toString();
    }

    return formatted;
};

export const vNumberInput: Directive<HTMLElement, NumberInputOptions> = {
    // Vue3 指令钩子
    beforeMount(el, binding) {
        // 在 beforeMount 钩子中初始化数据存储（Vue3 中 created 改为 beforeMount）
        el._numberInput = {
            options: binding.value || {},
            handlers: {} as Record<string, EventListener>,
        };
    },

    mounted(el, binding) {
        const input = (el.tagName === "INPUT" ? el : el.querySelector("input")) as HTMLInputElement | null;
        if (!input) return;

        const options = binding.value || {};
        const { decimal = 0 } = options;

        const dispatchInput = (target: HTMLInputElement) => {
            nextTick(() => {
                target.dispatchEvent(new Event("input", { bubbles: true }));
            });
        };

        const handleKeydown = (e: KeyboardEvent) => {
            if (
                [
                    "0",
                    "1",
                    "2",
                    "3",
                    "4",
                    "5",
                    "6",
                    "7",
                    "8",
                    "9",
                    ".",
                    "Backspace",
                    "Delete",
                    "Tab",
                    "Escape",
                    "Enter",
                    "Home",
                    "End",
                    "ArrowLeft",
                    "ArrowRight",
                    "ArrowUp",
                    "ArrowDown",
                ].includes(e.key) ||
                ((e.ctrlKey || e.metaKey) && ["a", "c", "v", "x", "z"].includes(e.key.toLowerCase()))
            ) {
                if (e.key === "." && (input.value.includes(".") || decimal === 0)) {
                    e.preventDefault();
                }
            } else {
                e.preventDefault();
            }
        };

        const handleInput = (e: Event) => {
            const target = e.target as HTMLInputElement;
            let value = target.value;

            const oldValue = value;
            value = value.replace(/[^\d.]/g, "");
            const parts = value.split(".");
            if (parts.length > 2) {
                value = parts[0] + "." + parts.slice(1).join("");
            }

            if (value !== oldValue) {
                target.value = value;
            }
        };

        const handleBlur = (e: Event) => {
            const target = e.target as HTMLInputElement;
            const options = binding.value || {};
            let rawValue = target.value;

            if (rawValue === "" && options.min !== undefined) {
                rawValue = options.min.toString();
            }

            const formatted = formatValue(rawValue, options);

            if (target.value !== formatted) {
                console.log("formatted", formatted);
                target.value = formatted;
                dispatchInput(target);
            }
        };

        // 存储事件处理器
        if (el._numberInput) {
            el._numberInput.handlers = {
                // 使用类型断言确保类型兼容
                keydown: handleKeydown as unknown as EventListener,
                input: handleInput as EventListener,
                blur: handleBlur as EventListener,
            };
        }

        // 绑定事件
        input.addEventListener("keydown", handleKeydown as unknown as EventListener);
        input.addEventListener("input", handleInput as EventListener);
        input.addEventListener("blur", handleBlur as EventListener);
    },

    updated(el, binding) {
        // 更新选项（Vue3 中 beforeUpdate 改为 updated）
        if (el._numberInput) {
            el._numberInput.options = binding.value || {};
        }
    },

    unmounted(el) {
        // Vue3 中 beforeUnmount 改为 unmounted
        const input = el.tagName === "INPUT" ? el : el.querySelector("input");
        if (!input || !el._numberInput?.handlers) return;

        // 移除事件监听
        Object.entries(el._numberInput.handlers).forEach(([event, handler]) => {
            // 类型断言确保 handler 是 EventListener
            input.removeEventListener(event as keyof HTMLElementEventMap, handler as EventListener);
        });

        // 清理存储的数据
        delete el._numberInput;
    },
};

export default vNumberInput;
