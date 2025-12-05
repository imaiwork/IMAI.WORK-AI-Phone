<template>
    <popup
        ref="popupRef"
        async
        width="448px"
        confirm-button-text=""
        cancel-button-text=""
        style="
            padding: 18px;
            border-radius: 20px;
            background-color: var(--app-bg-color-3);
            box-shadow: 0 0 0 1px #2a2a2a;
        "
        :append-to-body="false"
        :show-close="false">
        <div class="-my-4">
            <div class="absolute w-6 h-6 top-[18px] right-[18px] z-[22]" @click="close">
                <close-btn />
            </div>
            <div class="text-[15px] text-white font-bold">任务名称设置</div>
            <div class="mt-4">
                <ElInput
                    v-model="formData.name"
                    class="!h-[50px]"
                    placeholder="请输入任务名称"
                    clearable
                    maxlength="30"
                    show-word-limit />
            </div>
            <div class="mt-4 flex gap-2">
                <ElButton color="#181818" class="!rounded-full w-full !h-[50px] !border-app-border-1" @click="close"
                    >取消</ElButton
                >
                <ElButton type="primary" class="!rounded-full w-full !h-[50px]" :loading="isLock" @click="lockFn"
                    >立即保存</ElButton
                >
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { updateDeviceAccountTask } from "@/api/device";

const emit = defineEmits(["close", "success"]);

const popupRef = ref();

const formData = reactive({
    id: "",
    name: "",
});

const open = () => {
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.name) {
        feedback.msgError("请输入任务名称");
        return;
    }
    try {
        await updateDeviceAccountTask(formData);
        feedback.msgSuccess("修改成功");
        emit("success");
        close();
    } catch (error) {
        feedback.msgError(error);
    }
});

defineExpose({
    open,
    setFormData: (data) => setFormData(data, formData),
});
</script>
