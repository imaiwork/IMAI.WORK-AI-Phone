<template>
    <popup
        ref="popupRef"
        async
        width="656px"
        :title="props.title"
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close">
        <div>
            <!-- 步骤条 -->
            <div>
                <ElSteps simple :active="-1">
                    <ElStep title="上传文件" :icon="FolderOpened"></ElStep>
                    <ElStep title="导入数据" :icon="UploadFilled"></ElStep>
                    <ElStep title="导入完成" :icon="SuccessFilled"></ElStep>
                </ElSteps>
            </div>
            <!-- 步骤一：下载模板 -->
            <div class="mt-6">
                <div class="font-bold">
                    <span>一、请按照数据模板的格式准备要导入的数据。</span>
                    <a class="text-primary cursor-pointer" @click="handleDownloadTemplate">
                        点击此处下载《话术导入模板》
                    </a>
                </div>
                <div class="text-xs text-[#C4C6C8] indent-7 mt-1">导入文件请勿超过10MB（约100条数据）</div>
            </div>
            <!-- 步骤二：上传文件 -->
            <div class="mt-6">
                <div class="font-bold">
                    <span>二、请选择需要导入的文件</span>
                </div>
                <div class="mt-2">
                    <upload type="file" accept=".csv" list-type="text" :limit="1" @success="getFile">
                        <ElButton type="primary">上传文件</ElButton>
                    </upload>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { batchAddRobotKeywords } from "@/api/agent";
import Popup from "@/components/popup/index.vue";
import { FolderOpened, SuccessFilled, UploadFilled } from "@element-plus/icons-vue";

// 定义组件props
const props = defineProps<{
    title: string; // 弹窗标题
    agentId: string | number; // 智能体ID
}>();

const emit = defineEmits(["success", "close"]);

// 已上传文件的URI
const file = ref<string>("");

const popupRef = ref<InstanceType<typeof Popup>>();

// 打开弹窗
const open = () => {
    popupRef.value?.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

/**
 * @description 处理模板下载
 */
const handleDownloadTemplate = () => {
    const origin = window.location.origin;
    const url = `${origin}/static/file/template/speech.csv`;
    window.open(url, "_blank");
};

/**
 * @description 文件上传成功回调
 * @param result - 上传接口返回的数据
 */
const getFile = (result: any) => {
    const {
        data: { uri },
    } = result;
    file.value = uri;
};

/**
 * @description 确认导入操作
 */
const handleConfirm = async () => {
    if (!file.value) {
        feedback.msgError("请上传文件");
        return;
    }
    try {
        await batchAddRobotKeywords({
            file: file.value,
            robot_id: props.agentId as string,
        });
        emit("success");
        popupRef.value?.close();
        feedback.msgSuccess("导入成功");
    } catch (error) {
        feedback.msgError((error as string) || "导入失败");
    }
};

// 使用 useLockFn 防止重复提交
const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

// 暴露open方法
defineExpose({
    open,
});
</script>

<style scoped></style>
