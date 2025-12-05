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
import type { Directive, DirectiveBinding } from "vue";

// 定义指令的选项接口
interface NumberInputOptions {
    max?: number;
    min?: number;
    decimal?: number;
}

// 使用 WeakMap 存储事件处理器，避免内存泄漏
const handlerMap = new WeakMap<HTMLElement, Record<string, EventListener>>();

/**
 * 格式化并约束值
 * @param value 当前值
 * @param options 指令选项
 * @returns 格式化后的值
 */

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

const numberInputDirective: Directive<HTMLElement, NumberInputOptions> = {
    mounted(el, binding) {
        const input = (el.tagName === "INPUT" ? el : el.querySelector("input")) as HTMLInputElement | null;
        if (!input) return;

        const options = binding.value || {};
        const { decimal = 0 } = options;

        // 派发 input 事件以更新 v-model
        const dispatchInput = (target: HTMLInputElement) => {
            // 使用 nextTick 确保 DOM 更新后再通知 v-model
            nextTick(() => {
                target.dispatchEvent(new Event("input", { bubbles: true }));
            });
        };

        const handleKeydown = (e: KeyboardEvent) => {
            // 允许: 数字, 小数点, Backspace, Delete, Tab, Escape, Enter, Home, End, Arrow keys
            // 允许: Ctrl/Cmd+A, C, V, X, Z
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
                // 如果是小数点，检查是否已存在或是否允许小数
                if (e.key === "." && (input.value.includes(".") || decimal === 0)) {
                    e.preventDefault();
                }
            } else {
                // 阻止其他所有按键
                e.preventDefault();
            }
        };

        const handleInput = (e: Event) => {
            const target = e.target as HTMLInputElement;
            let value = target.value;

            // 清理粘贴或通过其他方式输入的无效字符 (例如，在中文输入法下输入)
            // 只保留数字和第一个小数点
            const oldValue = value;
            value = value.replace(/[^\d.]/g, "");
            const parts = value.split(".");
            if (parts.length > 2) {
                value = parts[0] + "." + parts.slice(1).join("");
            }

            // 如果值被清理，则更新输入框的值
            if (value !== oldValue) {
                target.value = value;
            }
        };

        const handleBlur = (e: Event) => {
            const target = e.target as HTMLInputElement;
            const options = binding.value || {};
            let rawValue = target.value;

            // 处理空值情况：当值为空且设置了最小值时，应用最小值
            if (rawValue === "" && options.min !== undefined) {
                rawValue = options.min.toString();
            }

            const formatted = formatValue(rawValue, options);

            // 当原始值与格式化值不同时更新
            if (target.value !== formatted) {
                target.value = formatted;
                dispatchInput(target);
            }
        };

        input.addEventListener("keydown", handleKeydown);
        input.addEventListener("input", handleInput);
        input.addEventListener("blur", handleBlur);

        handlerMap.set(el, {
            keydown: handleKeydown,
            input: handleInput,
            blur: handleBlur,
        });
    },

    unmounted(el) {
        const handlers = handlerMap.get(el);
        if (!handlers) return;

        const input = el.tagName === "INPUT" ? el : el.querySelector("input");
        if (input) {
            input.removeEventListener("keydown", handlers.keydown);
            input.removeEventListener("input", handlers.input);
            input.removeEventListener("blur", handlers.blur);
        }
        handlerMap.delete(el);
    },
};

export default defineNuxtPlugin((nuxtApp) => {
    nuxtApp.vueApp.directive("number-input", numberInputDirective);
});
