export interface SpinOptions {
    text?: string;
    target?: HTMLElement | string;
    zIndex?: number;
}

export interface SpinInstance {
    show: (options?: SpinOptions) => void;
    hide: () => void;
    updateText: (text: string) => void;
    destroy: () => void;
}

declare module "#app" {
    interface NuxtApp {
        $spin: {
            show: (options?: SpinOptions) => SpinInstance;
            hide: () => void;
        };
    }
}

declare module "vue" {
    interface ComponentCustomProperties {
        $spin: {
            show: (options?: SpinOptions) => SpinInstance;
            hide: () => void;
        };
    }
}
