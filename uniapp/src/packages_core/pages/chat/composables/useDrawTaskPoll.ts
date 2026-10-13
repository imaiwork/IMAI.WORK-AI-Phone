import { drawTaskStatus, isDrawTaskFailed, isDrawTaskSuccess } from "@/api/draw";

export interface DrawPollCallbacks {
    onProgress?: (progress: number, task: any) => void;
}

export const DRAW_POLL_ABORTED = "DRAW_POLL_ABORTED";

/** draw.draw 任务轮询（3s，对齐 PC useDrawTaskPoll） */
export function useDrawTaskPoll() {
    const timers = new Set<ReturnType<typeof setTimeout>>();
    const pendingRejects = new Set<(reason?: any) => void>();
    let aborted = false;

    const clearAllPolling = () => {
        aborted = true;
        timers.forEach((id) => clearTimeout(id));
        timers.clear();
        const err = new Error(DRAW_POLL_ABORTED);
        pendingRejects.forEach((reject) => {
            try {
                reject(err);
            } catch {
                /* ignore */
            }
        });
        pendingRejects.clear();
    };

    const pollTask = (taskNo: string, callbacks: DrawPollCallbacks = {}): Promise<any> => {
        if (!taskNo) return Promise.reject(new Error("缺少 task_no"));
        // 新一轮轮询前复位，不影响已 reject 的旧 Promise
        aborted = false;

        return new Promise((resolve, reject) => {
            let isRequesting = false;
            let attempts = 0;
            const maxAttempts = 200;
            let timerId: ReturnType<typeof setTimeout> | null = null;
            let settled = false;

            const settle = (fn: () => void) => {
                if (settled) return;
                settled = true;
                pendingRejects.delete(reject);
                clearThis();
                fn();
            };

            pendingRejects.add(reject);

            const clearThis = () => {
                if (timerId !== null) {
                    clearTimeout(timerId);
                    timers.delete(timerId);
                    timerId = null;
                }
            };

            const schedule = (fn: () => void, ms: number) => {
                clearThis();
                if (aborted) {
                    settle(() => reject(new Error(DRAW_POLL_ABORTED)));
                    return;
                }
                timerId = setTimeout(fn, ms);
                timers.add(timerId);
            };

            const tick = async () => {
                if (aborted) {
                    settle(() => reject(new Error(DRAW_POLL_ABORTED)));
                    return;
                }
                if (isRequesting) {
                    schedule(tick, 1000);
                    return;
                }
                isRequesting = true;
                try {
                    const res: any = await drawTaskStatus({ task_no: taskNo });
                    if (aborted) {
                        settle(() => reject(new Error(DRAW_POLL_ABORTED)));
                        return;
                    }
                    const task = res?.task || res;
                    const status = Number(task?.status);
                    const progress = Number(task?.progress || 0);
                    callbacks.onProgress?.(progress, task);
                    if (isDrawTaskSuccess(status)) {
                        settle(() => resolve(task));
                        return;
                    }
                    if (isDrawTaskFailed(status)) {
                        settle(() => reject(new Error(task?.error_msg || "生成失败")));
                        return;
                    }
                    attempts += 1;
                    if (attempts >= maxAttempts) {
                        settle(() => reject(new Error("生成超时，请稍后在历史中查看")));
                        return;
                    }
                    schedule(tick, 3000);
                } catch (error) {
                    if (aborted) {
                        settle(() => reject(new Error(DRAW_POLL_ABORTED)));
                        return;
                    }
                    settle(() => reject(error));
                } finally {
                    isRequesting = false;
                }
            };

            tick();
        });
    };

    onUnmounted(() => clearAllPolling());

    return { pollTask, clearAllPolling };
}
