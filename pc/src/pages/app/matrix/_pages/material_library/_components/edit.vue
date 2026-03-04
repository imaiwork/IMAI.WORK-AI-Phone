<template>
    <popup
        ref="popupRef"
        append-to-body
        width="550px"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="rounded-[28px] overflow-hidden bg-white shadow-2xl relative">
            <div class="flex items-center justify-between h-[64px] px-6 border-b border-[#F1F5F9]">
                <div class="flex items-center gap-x-2">
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-EditPen" :size="16"></Icon>
                    </div>
                    <div class="text-[16px] text-[#1E293B] font-black tracking-tight">重命名</div>
                </div>
                <div class="w-7 h-7" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="p-8 bg-slate-50">
                <div class="bg-white p-6 rounded-2xl border border-br">
                    <label class="block text-xs font-black text-[#94A3B8] uppercase mb-2 ml-1">新的名称</label>
                    <ElInput
                        v-model="formData.name"
                        class="custom-edit-input"
                        clearable
                        :placeholder="placeholder"
                        :maxlength="maxlength" />

                    <p class="mt-3 text-[11px] text-[#94A3B8] font-medium px-1">* 名称修改后将同步更新数据</p>
                </div>
            </div>

            <div class="p-6 bg-white border-t border-[#F1F5F9] flex justify-center">
                <ElButton
                    type="primary"
                    :loading="isLock"
                    class="!h-[50px] !w-full !rounded-full !text-[15px] !font-black !shadow-xl !shadow-[#0065fb]/20 active:scale-95 transition-all"
                    @click="lockFn()">
                    确定修改
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        maxlength: number;
        placeholder: string;
        fetchFn: (data: any) => Promise<void>;
    }>(),
    {
        maxlength: 50,
        placeholder: "请输入名称",
        fetchFn: () => Promise.resolve(),
    }
);

const emit = defineEmits(["success", "close"]);

const formData = reactive({
    id: "",
    name: "",
});

const popupRef = ref();

const confirm = async () => {
    if (!formData.name.trim()) {
        feedback.msgWarning("请输入名称");
        return;
    }
    try {
        await props.fetchFn(formData);
        feedback.msgSuccess("修改成功");
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

const setFormData = (data: any) => {
    formData.id = data.id;
    formData.name = data.name;
};

const { lockFn, isLock } = useLockFn(confirm);

defineExpose({
    open,
    close,
    setFormData,
});
</script>

<style scoped lang="scss">
/* 弹窗容器深度覆盖 */
:deep(.edit-material-popup) {
    .el-dialog {
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
    }
    .el-dialog__header {
        display: none;
    }
    .el-dialog__body {
        padding: 0 !important;
    }
}

/* 输入框样式重刻 */
:deep(.custom-edit-input) {
    .el-input__wrapper {
        border-radius: 12px !important;
        height: 48px !important;
        background-color: #f8fafc !important;
        box-shadow: 0 0 0 1px #e2e8f0 inset !important;
        transition: all 0.2s;
        &.is-focus {
            background-color: #ffffff !important;
            box-shadow: 0 0 0 1px #4f46e5 inset !important;
        }
    }
    .el-input__inner {
        font-weight: 700;
        color: #1e293b;
    }
}
</style>
