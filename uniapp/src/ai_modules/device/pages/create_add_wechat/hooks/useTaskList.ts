// ============================================================
// hooks/useTaskList.ts  —  Step1 线索任务列表管理（上传文件 / 从获客任务选择）
// ============================================================
import config from "@/config";
import { chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";
import { useCopy } from "@/hooks/useCopy";
import type { AddFriendFormData } from "./types";

export function useTaskList(formData: AddFriendFormData) {
    const { copy } = useCopy();

    const taskList = ref<any[]>([]);
    const uploadMaterialList = ref<any[]>([]);
    const showUploadProgress = ref(false);

    const progressCallback = (progress: number, options: { tempFilePath: string }) => {
        const idx = uploadMaterialList.value.findIndex((m) => m.tempFilePath === options.tempFilePath);
        if (idx !== -1) {
            uploadMaterialList.value[idx] = { ...uploadMaterialList.value[idx], progress };
        }
    };

    const handleDeleteTask = (index: number) => {
        taskList.value.splice(index, 1);
    };

    const handleAddTask = () => {
        uni.showActionSheet({
            itemList: ["复制模版链接", "从聊天记录中选择文件（需要符合模板）", "从获客任务中选择线索"],
            success: async (res) => {
                if (res.tapIndex === 0) {
                    copy(config.baseUrl + "static/file/template/wechatidcsv.csv");
                } else if (res.tapIndex === 1) {
                    try {
                        uploadMaterialList.value = [];
                        const { tempFiles } = await chooseFile({
                            type: "file",
                            extension: ["csv", "xlsx"],
                            count: 1,
                        });
                        const fileList = tempFiles.filter((f) => {
                            if (f.size > 20 * 1024 * 1024) {
                                uni.$u.toast("文件大小不能超过20M");
                                return false;
                            }
                            return true;
                        });
                        if (!fileList.length) return;

                        uploadMaterialList.value = fileList;
                        showUploadProgress.value = true;

                        for (const item of uploadMaterialList.value) {
                            const fileRes: any = await uploadFile("file", { filePath: item.path }, (progress) =>
                                progressCallback(progress, item),
                            );
                            if (formData.source === 2) formData.source = 1;
                            taskList.value.length = 0;
                            taskList.value.push({
                                url: fileRes.uri,
                                name: item.name,
                                size: item.size,
                                file_type: 1,
                            });
                        }
                        if (uploadMaterialList.value.every((m) => m.progress === 100)) {
                            showUploadProgress.value = false;
                        }
                    } catch (error: any) {
                        uni.$u.toast(error);
                        uploadMaterialList.value = [];
                        showUploadProgress.value = false;
                    }
                } else if (res.tapIndex === 2) {
                    uni.navigateTo({
                        url: "/ai_modules/device/pages/wechat_clue/wechat_clue",
                    });
                }
            },
            fail: (err) => console.log(err),
        });
    };

    return {
        taskList,
        uploadMaterialList,
        showUploadProgress,
        handleAddTask,
        handleDeleteTask,
    };
}
