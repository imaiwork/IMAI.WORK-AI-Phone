// composables/useSpinLoading.ts
import { createApp, ref, defineComponent, nextTick, type App } from "vue";

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
            const element = document.querySelector(target) as HTMLElement;
            return element || document.body;
        }

        return target;
    };

    const createInstance = async () => {
        if (app) return;

        await nextTick();
        const targetElement = getTargetElement();

        container = document.createElement("div");
        container.className = "spin-container";
        container.style.position = "absolute";
        container.style.top = "0";
        container.style.left = "0";
        container.style.right = "0";
        container.style.bottom = "0";
        container.style.pointerEvents = "none";

        // 设置定位
        if (targetElement !== document.body) {
            const position = window.getComputedStyle(targetElement).position;
            if (position === "static") {
                targetElement.style.position = "relative";
            }
            targetElement.appendChild(container);
        } else {
            document.body.appendChild(container);
        }

        try {
            const { default: SpinLoading } = await import("@/components/spin-loading/spin-loading.vue");

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
        } catch (error) {}
    };

    return {
        show: async (options: SpinOptions = {}) => {
            if (options.text !== undefined) {
                text.value = options.text;
            }
            if (options.zIndex !== undefined) {
                zIndex.value = options.zIndex;
            }

            if (!app) await createInstance();

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

// 全局 spin 方法
export const useGlobalSpin = () => {
    const instance = useSpin(undefined, {
        text: "正在处理...",
    });

    return {
        show: (options?: SpinOptions) => {
            instance.show(options);
            return instance;
        },
        hide: () => instance.hide(),
    };
};
