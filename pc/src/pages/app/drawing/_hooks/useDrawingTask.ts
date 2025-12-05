import { ref, onUnmounted } from "vue";
import { drawingImageStatus, drawingVolcVideoStatus, drawingDoubaoVideoStatus } from "@/api/drawing";
import { cloneDeep } from "lodash-es";
import { ModelEnum } from "../_enums";

// --- 接口定义 ---

/**
 * 表示绘图任务结果列表中的单个项。
 */
interface TaskResultItem {
    image: string; // 图像URL
    loading: boolean; // 是否正在加载
    error: boolean; // 是否发生错误
    index: number; // 任务项的索引
    progress: number; // 进度 (0-100)
    status: number; // 任务状态 (例如：3 表示成功，4 表示错误)
    formData: any; // 与任务关联的表单数据，如果结构已知，请考虑更具体的类型
}

/**
 * 处理绘图任务所需的参数。
 */
interface TaskParams {
    callback?: (dataLists: ResultItem[]) => void; // 任务结果更新时的回调函数
    onsuccess?: (data: ResultItem[]) => void; // 任务成功完成时的回调函数
    onfail?: (error: any) => void; // 任务失败时的回调函数
}

/**
 * 绘图状态API返回的单个子任务结果的结构。
 */
interface SubTaskResult {
    image: string; // 图像URL
    task_status: number; // 任务状态，例如：3 表示成功，4 表示错误
    task_completion: string; // 任务完成度，例如："0.5" 表示 50%
    // 如果子任务结果需要通过除数组索引以外的方式映射到 TaskResultItem，
    // 则需要添加 task_id 或其他唯一标识符。
}

export interface ResultItem {
    id?: string;
    url: string;
    loading: boolean;
    progress: number;
    status: number;
    error: boolean;
    msg?: string;
}

/**
 * 一个 Vue Composition API Hook，用于管理绘图任务，包括轮询状态更新。
 */
export default function useDrawingTask({
    type,
    model,
    task_id,
    dataLists,
    drawType = "image",
}: {
    dataLists: ResultItem[];
    type: number;
    model?: ModelEnum;
    task_id: string;
    drawType?: "image" | "video";
}) {
    // 存储 setTimeout 的 ID，以便在需要时清除轮询。
    const pollingTimeoutId = ref<NodeJS.Timeout | null>(null);
    // 存储每个子任务的结果列表。
    const taskResultList = ref<ResultItem[]>(cloneDeep(dataLists));
    // 标志，用于防止在轮询期间同时发送多个API请求。
    const isRequesting = ref(false);

    const isAllTasksCompleted = ref(false);

    /**
     * 获取最新的绘图状态并更新 `taskResultList`。
     * 判断所有子任务是否已完成。
     * @param params 任务参数，包括 `task_id`、`drawType` 和 `callback`。
     * @returns 一个 Promise，如果所有子任务都已完成，则解析为 `true`，否则为 `false`。
     * @throws 如果API调用失败。
     */
    const updateTaskResults = async ({ callback }: Pick<TaskParams, "callback">): Promise<boolean> => {
        try {
            let allTasksCompleted = false;
            const params = {
                task_id,
                type,
            };
            if (drawType == "image") {
                const res = await drawingImageStatus(params);
                const { result } = res.result; // 直接解构 result
                if (!result || !result.sub_task_results || result.sub_task_results.length === 0) {
                    console.warn("API 响应缺少 'result' 或 'sub_task_results'。", res);
                    // 根据预期行为，你可能希望抛出错误或返回 false。
                    // 返回 false 以便在出现间歇性问题时继续轮询。
                    return false;
                }

                taskResultList.value = result.sub_task_results.map((item, index) => {
                    return {
                        url: item.image,
                        status: item.task_status,
                        error: [3, 4].includes(item.task_status),
                        loading: item.task_status == 0 || item.task_status == 2,
                        progress: Math.round(parseFloat(item.task_completion) * 100),
                    };
                });

                // 检查所有子任务是否已达到最终状态（已接收图像或状态为成功/错误）
                allTasksCompleted = result.sub_task_results.every(
                    (item) => item.image || [3, 4].includes(item.task_status)
                );
            } else if (drawType == "video") {
                function getVideoRequest(params: any) {
                    return {
                        [ModelEnum.GENERAL]: drawingVolcVideoStatus,
                        [ModelEnum.SEEDANCE]: drawingDoubaoVideoStatus,
                    }[model](params);
                }

                const res = await getVideoRequest(params);
                taskResultList.value = [
                    {
                        url: res.video_url,
                        status: res.status || 0,
                        error: res.status == -1,
                        loading: res.status == 2 || res.status == 0,
                        progress: res.status == 1 ? 100 : 0,
                        msg: res.msg || "",
                    },
                ];
                allTasksCompleted = res.status == 1 || res.status == -1;
            }

            // 通知调用者列表已更新
            callback?.(taskResultList.value);

            return allTasksCompleted;
        } catch (err) {
            console.error("获取或更新任务结果时出错：", err);
            throw err; // 重新抛出以便被轮询机制捕获
        }
    };

    /**
     * 处理轮询过程中发生的错误。
     * 显示错误消息并更新 `taskResultList` 以反映错误。
     * @param error 错误对象。
     * @param reject Promise 的 reject 函数，用于传播错误。
     */
    const handlePollingError = (error: any, reject: (reason?: any) => void) => {
        feedback.msgError(error);

        // 将任何仍在加载的任务标记为错误
        taskResultList.value.forEach((item) => {
            if (item.loading && !item.url) {
                item.error = true;
                item.loading = false;
            }
        });
        clearPolling(); // 立即停止轮询
        reject(error); // 使用错误拒绝主 Promise
    };

    /**
     * 清除任何活动的轮询超时。
     */
    const clearPolling = () => {
        if (pollingTimeoutId.value !== null) {
            clearTimeout(pollingTimeoutId.value);
            pollingTimeoutId.value = null;
        }
    };

    /**
     * 启动递归轮询循环以检查任务状态。
     * @param params 任务参数，包括 `task_id`、`drawType`、`callback`、`onsuccess`、`onfail`，
     *               以及主 Promise 的 `resolve`/`reject` 函数。
     */
    const startPollingLoop = ({
        callback,
        onsuccess,
        onfail,
        resolve,
        reject,
    }: TaskParams & { resolve: (value: boolean) => void; reject: (reason?: any) => void }) => {
        const executePolling = async () => {
            // 如果请求已在进行中，则跳过此迭代并安排下一次。
            if (isRequesting.value) {
                pollingTimeoutId.value = setTimeout(executePolling, 1000);
                return;
            }

            isRequesting.value = true; // 标记请求正在进行中
            try {
                isAllTasksCompleted.value = await updateTaskResults({
                    callback,
                });
                isRequesting.value = false; // 标记请求完成

                if (isAllTasksCompleted.value) {
                    clearPolling(); // 所有任务完成后停止轮询
                    onsuccess?.(dataLists); // 调用成功回调
                    resolve(true); // 解析主 Promise
                } else {
                    // 如果并非所有任务都已完成，则延迟后安排下一次轮询
                    pollingTimeoutId.value = setTimeout(executePolling, 3000);
                }
            } catch (err) {
                isRequesting.value = false; // 即使出错也要确保重置标志
                handlePollingError(err, reject); // 处理错误并拒绝主 Promise
                onfail?.(err); // 如果提供了失败回调，则调用它
            }
        };

        // 启动第一次轮询迭代
        executePolling();
    };

    /**
     * 启动并管理绘图任务的主函数。
     * 初始化任务列表并开始轮询状态更新。
     * @param params 任务参数，包括 `task_id`、`drawType`、`formData`、`callback`、`onsuccess`、`onfail`。
     * @returns 一个 Promise，在所有子任务成功完成时解析为 `true`，或在出错时拒绝。
     */
    const processDrawingTask = async ({
        callback = () => {},
        onsuccess = () => {},
        onfail = () => {},
    }: TaskParams = {}): Promise<boolean> => {
        // 返回一个新的 Promise，该 Promise 将由轮询循环解析/拒绝
        return new Promise<boolean>((resolve, reject) => {
            startPollingLoop({
                callback,
                onsuccess,
                onfail,
                resolve,
                reject,
            });
        });
    };

    // 生命周期钩子：确保在使用此 hook 的组件卸载时清除任何活动的轮询。
    onUnmounted(() => {
        clearPolling();
    });

    // 将响应式状态和函数暴露给使用此 hook 的组件。
    return {
        taskResultList,
        isAllTasksCompleted,
        processDrawingTask,
    };
}
