<template>
    <div class="data-item-container group/item">
        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex items-center gap-2">
                <span class="index-badge"> #{{ (index + 1).toString().padStart(2, "0") }} </span>
                <span class="text-xs font-medium text-[#94A3B8] truncate max-w-[260px]">
                    {{ name }}
                </span>
            </div>

            <div class="flex items-center gap-2 opacity-0 group-hover/item:opacity-100 transition-all duration-300">
                <ElTooltip content="快速编辑">
                    <div class="action-item hover:!bg-primary hover:!text-white" @click="handleEdit">
                        <Icon name="local-icon-edit3" :size="14" />
                    </div>
                </ElTooltip>
                <ElTooltip content="删除">
                    <div class="action-item hover:!bg-danger hover:!text-white" @click="handleDelete">
                        <Icon name="local-icon-delete" :size="14" />
                    </div>
                </ElTooltip>
            </div>
        </div>

        <div class="content-box">
            <div class="content-decorator"></div>
            <div class="text-[13px] text-[#475569] leading-6 break-all whitespace-pre-wrap">
                {{ stageValue }}
            </div>
        </div>
    </div>

    <popup
        ref="editPopRef"
        v-if="showEdit"
        width="640px"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        :show-close="false"
        class="custom-edit-popup">
        <div class="relative">
            <div class="absolute right-2 top-2 z-10 w-8 h-8" @click="showEdit = false">
                <close-btn />
            </div>

            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center">
                    <Icon name="local-icon-edit3" class="text-primary" :size="20" />
                </div>
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">编辑分段内容</div>
                    <div class="text-xs font-medium text-[#94A3B8]">修改后的内容将实时更新至预览列表</div>
                </div>
            </div>

            <div class="editor-wrapper">
                <ElInput
                    v-model="editValue"
                    type="textarea"
                    resize="none"
                    :rows="12"
                    placeholder="请输入分段内容..."
                    class="custom-edit-input" />
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <ElButton
                    class="!rounded-xl !h-11 !px-8 !font-black !text-[#64748B] !bg-[#F1F5F9] !border-none hover:!bg-[#E2E8F0]"
                    @click="showEdit = false">
                    取消
                </ElButton>
                <ElButton type="primary" class="save-btn" @click="handleSave"> 确认保存 </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
const props = defineProps<{
    index: number;
    name: string;
    data: string;
}>();

const emit = defineEmits<{
    (event: "delete"): void;
    (event: "update:data", value: string): void;
}>();

const stageValue = defineModel<string>("data", { required: true });

const showEdit = ref(false);
const editValue = ref("");
const editPopRef = shallowRef();

const handleEdit = async (): Promise<void> => {
    showEdit.value = true;
    editValue.value = stageValue.value;
    await nextTick();
    editPopRef.value?.open();
};

const handleSave = () => {
    if (!editValue.value.trim()) {
        feedback.msgWarning("请输入内容");
        return;
    }
    stageValue.value = editValue.value;
    showEdit.value = false;
};

const handleDelete = (): void => {
    useNuxtApp().$confirm({
        message: "确定要删除该段落吗？",
        onConfirm: () => {
            emit("delete");
        },
    });
};
</script>

<style scoped lang="scss">
.data-item-container {
    @apply transition-all duration-300;
}

.index-badge {
    @apply px-2 py-0.5 rounded bg-[#1E293B] text-white text-[10px] font-black italic tracking-tighter;
}

.content-box {
    @apply relative p-4 bg-slate-50 rounded-xl border border-[#F1F5F9] transition-all;

    .content-decorator {
        @apply absolute left-0 top-3 bottom-3 w-[3px] bg-[#0065fb]/[0.02] rounded-r-full transition-all;
    }

    &:hover {
        @apply bg-white border-[#0065fb]/[0.02];
        .content-decorator {
            @apply bg-primary;
        }
    }
}

.action-item {
    @apply w-7 h-7 rounded-lg bg-white border border-br flex items-center justify-center cursor-pointer transition-all text-[#64748B];
}

.editor-wrapper {
    @apply border-2 border-[#F1F5F9] rounded-2xl overflow-hidden focus-within:border-[#0065fb]/[0.2] transition-all p-2;
}

.save-btn {
    @apply rounded-xl h-11 px-12 font-black bg-primary border-[none];
    box-shadow: 0 8px 16px -4px rgba(var(--el-color-primary), 0.4);

    &:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 10px 20px -4px rgba(var(--el-color-primary), 0.5);
    }
}
:deep(.custom-edit-input) {
    .el-textarea__inner {
        box-shadow: none;
        border: none;
    }
}
</style>
