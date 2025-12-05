<template>
    <popup
        ref="popupRef"
        append-to-body
        :show-close="false"
        style="background-color: var(--app-bg-color-2)"
        cancel-button-text=""
        confirm-button-text=""
        @close="close">
        <div class="-my-4">
            <div class="absolute top-2 right-2 w-6 h-6" @click="close">
                <close-btn :icon-size="12" :theme="ThemeEnum.DARK"></close-btn>
            </div>
            <div class="text-white text-2xl font-bold">重命名</div>
            <div class="mt-2">
                <ElInput v-model="formData.name" placeholder="请输入名称" class="!h-11" clearable maxlength="50" />
            </div>
            <div class="mt-6 flex justify-center text-white">
                <ElButton
                    type="primary"
                    round
                    class="!h-[50px] shadow-[0px_6px_12px_0px_rgba(0,101,251,0.20)] w-[70%] !rounded-full"
                    :loading="isLock"
                    @click="lockFn()">
                    确定
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { updateMaterialLibrary } from "@/api/redbook";
import { ThemeEnum } from "@/enums/appEnums";

const emit = defineEmits(["success", "close"]);

const formData = reactive({
    id: "",
    name: "",
});

const popupRef = ref();

const confirm = async () => {
    try {
        await updateMaterialLibrary(formData);
        emit("success");
        close();
    } catch (error) {
        feedback.msgWarning(error);
    }
};

const open = () => {
    popupRef.value.open();
};

const close = () => {
    popupRef.value.close();
    emit("close");
};

const { lockFn, isLock } = useLockFn(confirm);

defineExpose({
    open,
    setFormData: (data: any) => {
        setFormData(data, formData);
    },
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
</style>
