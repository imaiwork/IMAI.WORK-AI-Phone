<template>
    <popup
        ref="tagPopRef"
        title="管理客户标签"
        async
        width="520px"
        confirm-button-text="保存标签设置"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="mb-6 flex items-center gap-4 p-4 bg-[#0065fb]/5 rounded-2xl border border-[#0065fb]/10">
            <div class="w-12 h-12 rounded-xl bg-[#0065fb]/10 flex items-center justify-center shrink-0">
                <Icon name="local-icon-tag" color="var(--color-primary)" :size="24" />
            </div>
            <div class="flex flex-col">
                <span class="text-[15px] font-medium text-slate-800">精准画像标记</span>
                <span class="text-xs text-slate-500 font-medium">为客户添加标签，助力 SOP 精准自动化推送</span>
            </div>
        </div>

        <ElForm ref="tagFormRef" :model="tagForm" label-position="top" :rules="tagFormRules" class="custom-tag-form">
            <ElFormItem prop="tag_ids">
                <template #label>
                    <div class="flex items-center justify-between w-full mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[14px] font-medium text-slate-700">选择适用标签</span>
                            <span class="text-[11px] text-slate-400 font-medium uppercase tracking-wider"
                                >Select Tags</span
                            >
                        </div>
                    </div>
                </template>

                <ElSelect
                    v-model="tagForm.tag_ids"
                    placeholder="搜索或从下拉列表中选择标签..."
                    multiple
                    filterable
                    clearable
                    collapse-tags
                    collapse-tags-tooltip
                    class="premium-tag-select">
                    <ElOption v-for="item in wechatTagLists" :key="item.id" :label="item.tag_name" :value="item.id">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-primary/40"></div>
                            <span>{{ item.tag_name }}</span>
                        </div>
                    </ElOption>
                </ElSelect>
            </ElFormItem>
        </ElForm>

        <div class="mt-8 py-3 px-4 bg-slate-50 rounded-xl flex items-center gap-2">
            <Icon name="el-icon-InfoFilled" class="text-slate-300" :size="14" />
            <span class="text-[11px] text-slate-400 font-medium">
                已选 {{ tagForm.tag_ids.length }} 个标签。标签将实时同步至企微侧边栏。
            </span>
        </div>
    </popup>
</template>
<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { type FormInstance } from "element-plus";
import useHandle from "../../_hooks/useHandle";

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const { wechatTagLists, addFriendTag, getWechatTagLists } = useHandle();

const tagPopRef = ref<InstanceType<typeof Popup> | null>(null);
const showTagPop = ref<boolean>(false);
const tagForm = reactive({
    tag_ids: [],
});
const tagFormRef = ref<FormInstance>();
const tagFormRules = {
    tag_ids: [{ required: true, message: "请选择标签" }],
};

const open = async () => {
    tagPopRef.value?.open();
    getWechatTagLists();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    await tagFormRef.value?.validate();
    try {
        await addFriendTag({
            tag_ids: tagForm.tag_ids,
        });
        showTagPop.value = false;
        tagForm.tag_ids = [];
        tagPopRef.value?.close();
        feedback.msgSuccess("添加标签成功");
    } catch (error) {
        feedback.msgError(error);
    }
});

defineExpose({
    open,
});
</script>
<style scoped lang="scss">
:deep(.premium-tag-select) {
    width: 100% !important;

    .el-select__wrapper {
        @apply rounded-2xl bg-slate-50 border border-slate-100 shadow-[none] p-3 transition-all duration-300;
        min-height: 56px;

        &:hover {
            @apply bg-white border-[#0065fb]/30;
            box-shadow: 0 4px 12px rgba(0, 101, 251, 0.05) !important;
        }

        &.is-focus {
            @apply bg-white border-primary ring-4 ring-[#0065fb]/10;
        }
    }

    .el-tag.el-tag--info {
        @apply bg-primary text-white border-none rounded-lg px-3 font-medium;
        .el-tag__close {
            @apply text-white hover:bg-[#ffffff]/20;
        }
    }

    .el-select__placeholder {
        @apply text-slate-300 font-medium ml-1;
    }
}

:deep(.el-select-dropdown__item) {
    @apply mx-2 my-1 rounded-lg font-medium text-slate-600;
    &.selected {
        @apply text-primary bg-[#0065fb]/5;
    }
    &.hover {
        @apply bg-slate-50;
    }
}

:deep(.el-form-item__label) {
    @apply p-0 mb-2 w-full;
}
</style>
