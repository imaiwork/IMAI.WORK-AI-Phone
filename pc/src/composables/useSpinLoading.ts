import { createApp, ref, defineComponent, h, type App } from "vue";
import SpinLoading from "@/components/spin-loading/spin-loading.vue";

export interface SpinOptions {
    text?: string;
    zIndex?: number;
}

export interface SpinInstance {
    show: (options?: SpinOptions) => void;
    hide: () => void;
    updateText: (text: string) => void;
    destroy: () => void;
}

export function useSpin(target?: HTMLElement | string, defaultOptions: SpinOptions = {}): SpinInstance {
    let app: App | null = null;
    let container: HTMLElement | null = null;

    const visible = ref(false);
    const text = ref(defaultOptions.text || "正在加载...");
    const zIndex = ref(defaultOptions.zIndex || 1000);

    const getTargetElement = (): HTMLElement => {
        if (!target) return document.body;
        if (typeof target === "string") {
            return (document.querySelector(target) as HTMLElement) || document.body;
        }
        return target;
    };

    // ✅ 改为同步函数，去掉 async/await
    const createInstance = () => {
        if (app) return;

        const targetElement = getTargetElement();

        container = document.createElement("div");
        container.className = "spin-container";
        container.style.cssText = "position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;";

        if (targetElement !== document.body) {
            const position = window.getComputedStyle(targetElement).position;
            if (position === "static") {
                targetElement.style.position = "relative";
            }
            targetElement.appendChild(container);
        } else {
            document.body.appendChild(container);
        }

        const WrapperComponent = defineComponent({
            name: "SpinWrapper",
            setup() {
                return {
                    visible,
                    text,
                    zIndex,
                    target: targetElement === document.body ? undefined : targetElement,
                };
            },
            render() {
                return h(SpinLoading, {
                    visible: this.visible,
                    text: this.text,
                    zIndex: this.zIndex,
                    target: this.target,
                });
            },
        });

        app = createApp(WrapperComponent);
        app.mount(container);
    };

    return {
        // ✅ show 也改为同步
        show: (options: SpinOptions = {}) => {
            if (options.text !== undefined) text.value = options.text;
            if (options.zIndex !== undefined) zIndex.value = options.zIndex;

            // ✅ 同步创建，立即可用
            if (!app) createInstance();

            visible.value = true;

            if (container) {
                container.style.pointerEvents = "auto";
                container.style.zIndex = String(zIndex.value);
            }
        },

        hide: () => {
            visible.value = false;
            if (container) {
                container.style.pointerEvents = "none";
            }
        },

        updateText: (newText: string) => {
            text.value = newText;
        },

        destroy: () => {
            if (app) {
                app.unmount();
                app = null;
            }
            if (container?.parentNode) {
                container.parentNode.removeChild(container);
                container = null;
            }
        },
    };
}

export const useGlobalSpin = () => {
    const instance = useSpin(undefined, { text: "正在处理..." });
    return {
        show: (options?: SpinOptions) => {
            instance.show(options);
            return instance;
        },
        hide: () => instance.hide(),
    };
};
