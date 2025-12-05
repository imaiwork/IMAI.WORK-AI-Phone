<template>
    <popup
        ref="popupRef"
        async
        width="656px"
        title="用户批量导入"
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close">
        <div>
            <div>
                <ElSteps simple :active="-1">
                    <ElStep title="上传文件" :icon="FolderOpened"></ElStep>
                    <ElStep title="导入数据" :icon="UploadFilled"></ElStep>
                    <ElStep title="导入完成" :icon="SuccessFilled"></ElStep>
                </ElSteps>
            </div>
            <div class="mt-6">
                <div class="font-bold">
                    <span>一、请按照数据模板的格式准备要导入的数据。</span>
                    <a class="text-primary cursor-pointer" href="/static/file/template/user.csv" target="_blank">
                        点击下载模版
                    </a>
                </div>
                <div class="text-xs text-[#C4C6C8] indent-7 mt-1">导入文件请勿超过10MB（约100条数据）</div>
            </div>
            <div class="mt-6">
                <div class="font-bold">
                    <span>二、请选择需要导入的文件</span>
                </div>
                <div class="mt-2">
                    <upload type="file" accept=".csv" list-type="text" show-file-list :limit="1" @change="getFile">
                        <ElButton type="primary">上传文件</ElButton>
                    </upload>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { userBatchImport } from "@/api/consumer";
import Popup from "@/components/popup/index.vue";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";
import { FolderOpened, SuccessFilled, UploadFilled } from "@element-plus/icons-vue";

const emit = defineEmits(["success", "close"]);

const file = ref<string>("");

const popupRef = ref<InstanceType<typeof Popup>>();
const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const getFile = (result: any) => {
    const { uri, raw } = result;
    file.value = raw;
};

const handleConfirm = async () => {
    if (!file.value) {
        feedback.msgError("请上传文件");
        return;
    }
    const formData = new FormData();
    formData.append("file", file.value);
    try {
        await userBatchImport(formData);
        feedback.msgSuccess("导入成功");
        emit("success");
        close();
    } catch (error) {
        feedback.msgError(error as string);
    }
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

defineExpose({
    open,
});
</script>

<style scoped></style>
