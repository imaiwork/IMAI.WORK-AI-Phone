/**
 * 首页 draw 任务轮询（对齐 AI 设计页 useDrawingTask 模式）
 * - setTimeout 递归轮询，间隔 3s
 * - isRequesting 防并发请求
 * - 卸载/完成/失败时 clearTimeout
 * - 对接 /draw.draw/getTaskStatus（status: 3成功 4/5失败）
 */
import { onUnmounted } from "vue";
import { drawTaskStatus } from "@/api/drawing";

export interface DrawPollCallbacks {
    onProgress?: (progress: number, task: any) => void;
    onSuccess?: (task: any) => void;
    onFail?: (error: any) => void;
}

export default function useDrawTaskPoll() {
    const activeTimers = new Set<ReturnType<typeof setTimeout>>();
    const aborted = { value: false };

    const clearAllPolling = () => {
        aborted.value = true;
        activeTimers.forEach((id) => clearTimeout(id));
        activeTimers.clear();
    };

    /**
     * 轮询单个 draw 任务直到终态
     * @returns 成功时返回带 assets 的 task；失败抛错
     */
    const pollTask = (taskNo: string, callbacks: DrawPollCallbacks = {}): Promise<any> => {
        if (!taskNo) {
            return Promise.reject(new Error("缺少 task_no"));
        }

        return new Promise((resolve, reject) => {
            let isRequesting = false;
            let attempts = 0;
            const maxAttempts = 200; // ~10 分钟（3s * 200）
            let timerId: ReturnType<typeof setTimeout> | null = null;

            const clearThis = () => {
                if (timerId !== null) {
                    clearTimeout(timerId);
                    activeTimers.delete(timerId);
                    timerId = null;
                }
            };

            const schedule = (fn: () => void, ms: number) => {
                clearThis();
                if (aborted.value) return;
                timerId = setTimeout(fn, ms);
                activeTimers.add(timerId);
            };

            const finishOk = (task: any) => {
                clearThis();
                callbacks.onSuccess?.(task);
                resolve(task);
            };

            const finishErr = (err: any) => {
                clearThis();
                callbacks.onFail?.(err);
                reject(err);
            };

            const executePolling = async () => {
                if (aborted.value) return;

                // 与 AI 设计页一致：上一轮还在请求则稍后再试
                if (isRequesting) {
                    schedule(executePolling, 1000);
                    return;
                }

                isRequesting = true;
                try {
                    const res: any = await drawTaskStatus({ task_no: taskNo });
                    if (aborted.value) return;

                    const task = res?.task ?? res;
                    const status = Number(task?.status ?? 0);
                    const progress = Number(task?.progress ?? 0);

                    callbacks.onProgress?.(progress, task);

                    if (status === 3) {
                        isRequesting = false;
                        finishOk(task);
                        return;
                    }

                    if (status === 4 || status === 5) {
                        isRequesting = false;
                        finishErr(new Error(task?.error_msg || "生成失败"));
                        return;
                    }

                    attempts++;
                    if (attempts >= maxAttempts) {
                        isRequesting = false;
                        finishErr(new Error("超时未完成"));
                        return;
                    }

                    isRequesting = false;
                    schedule(executePolling, 3000);
                } catch (err) {
                    isRequesting = false;
                    finishErr(err);
                }
            };

            // 立即开始第一次查询（与 AI 设计页一致，不等首轮 3s）
            executePolling();
        });
    };

    onUnmounted(() => {
        clearAllPolling();
    });

    return {
        pollTask,
        clearAllPolling,
    };
}
