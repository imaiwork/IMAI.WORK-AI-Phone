import { createVNode, render } from "vue";
import type { ComponentPublicInstance } from "vue";
import ConfirmBox from "@/components/confirm-box/index.vue";

interface ConfirmOptions {
    title?: string;
    message: string;
    width?: string;
    theme?: "light" | "dark";
    confirmButtonText?: string;
    cancelButtonText?: string;
    onConfirm?: () => void;
    onCancel?: () => void;
    onClose?: () => void;
}

interface ConfirmInstance extends ComponentPublicInstance {
    open: () => void;
}

export default defineNuxtPlugin((nuxtApp) => {
    let container: HTMLElement | null = null;

    const createContainer = () => {
        if (!container) {
            container = document.createElement("div");
            container.setAttribute("class", "confirm-container");
            document.body.appendChild(container);
        }
        return container;
    };

    const removeContainer = () => {
        if (container) {
            render(null, container);
            container.parentNode?.removeChild(container);
            container = null;
        }
    };

    const showConfirm = (options: ConfirmOptions): Promise<boolean> => {
        return new Promise((resolve) => {
            if (container) {
                removeContainer();
            }

            createContainer();

            const vnode = createVNode(ConfirmBox, {
                ...options,
                onConfirm: () => {
                    options.onConfirm?.();
                    removeContainer();
                    resolve(true);
                },
                onCancel: () => {
                    options.onCancel?.();
                    removeContainer();
                    resolve(false);
                },
                onClose: () => {
                    options.onClose?.();
                    removeContainer();
                    resolve(false);
                },
            });

            if (container) {
                render(vnode, container);
                const instance = vnode.component?.proxy as ConfirmInstance;
                instance.$.exposed.open();
            }
        });
    };

    return {
        provide: {
            confirm: showConfirm,
        },
    };
});
