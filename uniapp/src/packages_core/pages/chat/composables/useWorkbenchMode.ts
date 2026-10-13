import { WorkbenchMode, WORKBENCH_PLACEHOLDER } from "../enums/workbench";

/** 工作台模式切换（互斥进入/退出） */
export function useWorkbenchMode() {
    const mode = ref<WorkbenchMode>(WorkbenchMode.Chat);

    const isChatMode = computed(() => mode.value === WorkbenchMode.Chat);
    const placeholder = computed(() => WORKBENCH_PLACEHOLDER[mode.value]);

    const enterMode = (next: WorkbenchMode) => {
        mode.value = next;
    };

    const exitMode = () => {
        mode.value = WorkbenchMode.Chat;
    };

    const toggleMode = (next: WorkbenchMode) => {
        if (mode.value === next) {
            exitMode();
            return;
        }
        enterMode(next);
    };

    return {
        mode,
        isChatMode,
        placeholder,
        enterMode,
        exitMode,
        toggleMode,
    };
}
