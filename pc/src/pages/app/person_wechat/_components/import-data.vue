<template>
    <popup
        ref="popupRef"
        width="600px"
        async
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close"
        custom-class="modern-import-popup">
        <div class="px-2 mb-6">
            <div class="flex items-center gap-x-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <Icon name="el-icon-Upload" :size="20" />
                </div>
                <div>
                    <h3 class="text-[18px] font-black text-tx-primary leading-tight">{{ title || "数据批量导入" }}</h3>
                    <p class="text-[11px] text-tx-placeholder font-medium uppercase tracking-widest mt-1">
                        Batch Data Import
                    </p>
                </div>
            </div>
        </div>

        <div class="px-2">
            <div class="bg-slate-50/80 p-4 rounded-[20px] border border-slate-100 mb-8">
                <ElSteps :active="file ? 1 : 0" finish-status="success" align-center class="custom-steps">
                    <ElStep title="上传文件" :icon="FolderOpened" />
                    <ElStep title="解析数据" :icon="UploadFilled" />
                    <ElStep title="导入完成" :icon="SuccessFilled" />
                </ElSteps>
            </div>

            <div class="space-y-8 px-2">
                <div class="relative pl-8">
                    <div
                        class="absolute left-0 top-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-black flex items-center justify-center">
                        1
                    </div>
                    <div class="flex flex-col gap-y-1">
                        <span class="text-[15px] font-black text-tx-primary">准备导入数据</span>
                        <p class="text-[13px] text-tx-regular">请下载标准模板并按格式填入数据</p>
                        <div class="mt-3">
                            <div
                                class="inline-flex items-center gap-x-2 px-4 py-2.5 rounded-xl bg-primary/5 border border-primary/10 text-primary cursor-pointer hover:bg-primary/10 transition-all group"
                                @click="handleDownloadTemplate">
                                <Icon name="el-icon-Document" :size="16" />
                                <span class="text-[13px] font-medium">下载《数据导入模板》</span>
                                <span class="group-hover:translate-y-0.5 transition-transform leading-[0]">
                                    <Icon name="el-icon-Download" :size="14" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative pl-8 pb-4">
                    <div
                        class="absolute left-0 top-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-black flex items-center justify-center">
                        2
                    </div>
                    <div class="flex flex-col gap-y-1">
                        <span class="text-[15px] font-black text-tx-primary">上传数据文件</span>
                        <p class="text-[13px] text-tx-regular">
                            支持 <span class="text-tx-primary font-medium">.csv</span> 格式，文件大小不超过
                            <span class="text-orange-500 font-medium">10MB</span>
                        </p>

                        <div class="mt-4">
                            <upload type="file" accept=".csv" list-type="text" :limit="1" @success="getFile">
                                <div
                                    class="w-full border-2 border-dashed border-slate-200 rounded-[20px] p-8 flex flex-col items-center justify-center hover:border-primary/50 hover:bg-[#0065fb]/[0.02] transition-all cursor-pointer group">
                                    <div
                                        class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 group-hover:bg-[#0065fb]/10 group-hover:text-primary flex items-center justify-center transition-all mb-3">
                                        <Icon
                                            :name="file ? 'el-icon-DocumentChecked' : 'el-icon-UploadFilled'"
                                            :size="24" />
                                    </div>
                                    <span class="text-[14px] font-medium text-tx-regular group-hover:text-primary">
                                        {{ file ? "已选中：" + file.split("/").pop() : "点击或拖拽文件至此处上传" }}
                                    </span>
                                </div>
                            </upload>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { batchAddRobotKeywords, batchAddTags } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { FolderOpened, SuccessFilled, UploadFilled } from "@element-plus/icons-vue";

const props = defineProps({
    title: { type: String, default: "" },
    type: { type: String, default: "speech" },
});

const emit = defineEmits(["success", "close"]);
const route = useRoute();
const file = ref<string>("");
const popupRef = ref<InstanceType<typeof Popup>>();

const open = () => {
    popupRef.value?.open();
};
const close = () => {
    emit("close");
};

const handleDownloadTemplate = () => {
    const origin = window.location.origin;
    const url = `${origin}/static/file/template/${props.type}.csv`;
    window.open(url, "_blank");
};

const getFile = (result: any) => {
    const {
        data: { uri },
    } = result;
    file.value = uri;
};

const handleConfirm = async () => {
    if (!file.value) {
        feedback.msgError("请上传文件");
        return;
    }
    try {
        if (props.type == "speech") {
            await batchAddRobotKeywords({ file: file.value, robot_id: route.query.id });
        } else if (props.type == "tags") {
            await batchAddTags({ file: file.value });
        }
        emit("success");
        popupRef.value?.close();
        feedback.msgSuccess("导入成功");
    } catch (error) {
        feedback.msgError(error || "导入失败");
    }
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);
defineExpose({ open });
</script>

<style scoped lang="scss">
.custom-steps {
    :deep(.el-step__title) {
        @apply text-[13px] font-black mt-1;
    }
    :deep(.el-step__icon) {
        @apply w-8 h-8;
    }
    :deep(.el-step__line) {
        @apply bg-slate-200 top-4;
    }
}

:deep(.modern-import-popup .el-dialog__header) {
    @apply hidden;
}

.relative.pl-8::after {
    content: "";
    @apply absolute left-3 top-7 bottom-0 w-[1px] bg-slate-100;
}
.relative.pl-8:last-child::after {
    @apply hidden;
}
</style>
