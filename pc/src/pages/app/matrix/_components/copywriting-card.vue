<template>
    <div class="h-full flex flex-col bg-white">
        <div class="flex justify-between items-end pb-4 border-b border-[#F3F4F6] px-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 bg-primary rounded-full"></span>
                    <span class="text-lg font-black text-[#111827]">{{ typeName }}管理</span>
                </div>
                <div class="flex items-center gap-x-2 mt-1.5">
                    <span class="text-[#9CA3AF] text-xs font-medium">
                        共 <span class="text-[#111827] font-medium">{{ valueList.length }}</span> 个素材，已配置
                        <span class="text-[#10B981] font-medium">{{ count }}</span> 个
                    </span>

                    <ElTooltip v-if="publishTypeName" placement="top" popper-class="custom-tooltip" :show-arrow="false">
                        <div
                            class="w-4 h-4 rounded-full flex items-center justify-center bg-[#F3F4F6] cursor-pointer hover:bg-[#E5E7EB] transition-colors">
                            <Icon name="local-icon-tips2" :size="12" color="#9CA3AF"></Icon>
                        </div>
                        <template #content>
                            <div class="p-1 space-y-1">
                                <p>1. 数量相等时：按顺序匹配内容。</p>
                                <p>2. 数量不等时：随机分配内容至各{{ publishTypeName }}。</p>
                            </div>
                        </template>
                    </ElTooltip>
                </div>
            </div>
            <button
                @click="handleAdd"
                class="px-5 h-10 rounded-xl bg-[#F3F4F6] text-primary font-medium text-sm hover:bg-primary hover:text-white transition-all active:scale-95 flex items-center gap-2">
                <i class="el-icon-plus"></i>
                <span>新增{{ typeName }}</span>
            </button>
        </div>

        <div class="grow min-h-0 mt-4">
            <ElScrollbar>
                <div class="flex flex-col gap-y-4 px-4 pb-6">
                    <template v-if="type == 1">
                        <div
                            v-for="(item, index) in valueList"
                            :key="index"
                            class="group flex items-center gap-x-4 p-3 bg-white border border-[#F3F4F6] rounded-2xl hover:border-[#0065fb]/30 hover: transition-all">
                            <div
                                class="w-10 h-10 flex-shrink-0 rounded-xl font-black text-sm transition-colors flex items-center justify-center"
                                :class="[item.content ? 'bg-[#10B981] text-white' : 'bg-[#F3F4F6] text-[#9CA3AF]']">
                                {{ index + 1 }}
                            </div>

                            <div class="flex-1">
                                <ElInput
                                    v-model="item.content"
                                    class="custom-title-input"
                                    maxlength="20"
                                    show-word-limit
                                    placeholder="输入吸引人的标题..."
                                    @blur="handleBlur(index)" />
                            </div>

                            <div class="w-8 h-8 opacity-0 group-hover:opacity-100" @click="handleDelete(index)">
                                <close-btn></close-btn>
                            </div>
                        </div>
                    </template>

                    <template v-if="type == 2 || type == 3">
                        <div
                            v-for="(item, index) in valueList"
                            :key="index"
                            class="group relative border border-[#F3F4F6] bg-white p-5 rounded-[24px] hover:border-[#10B981]/30 transition-all">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="px-3 h-6 rounded-lg font-black text-xs flex items-center justify-center"
                                        :class="[
                                            item.content ? 'bg-[#10B981] text-white' : 'bg-[#F3F4F6] text-[#9CA3AF]',
                                        ]">
                                        ITEM {{ index + 1 }}
                                    </div>
                                    <span v-if="item.content" class="text-[11px] text-[#10B981] font-medium"
                                        >已就绪</span
                                    >
                                </div>
                                <div class="w-7 h-7 opacity-0 group-hover:opacity-100" @click="handleDelete(index)">
                                    <close-btn :icon-size="12"></close-btn>
                                </div>
                            </div>

                            <div class="mt-2">
                                <ElInput
                                    v-model="item.content"
                                    type="textarea"
                                    :rows="5"
                                    maxlength="800"
                                    show-word-limit
                                    placeholder="请输入正文描述内容..."
                                    class="custom-textarea-input"
                                    resize="none"
                                    @blur="handleBlur(index)" />
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2 items-center" v-if="showTopic">
                                <div
                                    v-for="(topic, t_index) in item.topic"
                                    :key="t_index"
                                    class="group/tag relative flex items-center bg-white border border-br px-2 py-1 rounded-lg">
                                    <span class="text-[#6366F1] font-medium text-xs mr-1">#</span>
                                    <input
                                        v-model="item.topic[t_index]"
                                        class="bg-transparent border-none outline-none text-[11px] w-[70px] text-[#64748B]"
                                        placeholder="输入话题"
                                        @click.stop />
                                    <div
                                        class="ml-1 w-3 h-3 bg-[#94A3B8] text-white rounded-full flex items-center justify-center cursor-pointer opacity-0 group-hover/tag:opacity-100 transition-opacity"
                                        @click.stop="handleDeleteTopic(index, t_index)">
                                        <Icon name="local-icon-close" :size="8"></Icon>
                                    </div>
                                </div>

                                <button
                                    v-if="item.topic?.length < 5"
                                    class="px-3 py-1 rounded-lg border border-dashed border-[#CBD5E1] text-[#94A3B8] text-[11px] hover:border-[#6366F1] hover:text-[#6366F1] transition-all"
                                    @click.stop="handleAddTopic(index)">
                                    + 添加话题
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        type: 1 | 2 | 3;
        modelValue: any[];
        publishTypeName?: string;
        showTopic?: boolean;
    }>(),
    {
        showTopic: true,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: any): void;
}>();

const valueList = defineModel<any[]>("modelValue");

const count = computed(() => {
    return valueList.value.filter((item) => item.content).length;
});

const typeName = computed(() => {
    const types = { 1: "标题", 2: "正文描述", 3: "口播文案" };
    return types[props.type as keyof typeof types];
});

const handleAdd = () => {
    if (props.type == 2) {
        valueList.value.push({ content: "", topic: [] });
    } else {
        valueList.value.push({ content: "" });
    }
    emit("update:modelValue", valueList.value);
};

const handleDelete = (index: number) => {
    useNuxtApp().$confirm({
        message: `确定要彻底移除该${typeName.value}吗？`,
        onConfirm: () => {
            valueList.value.splice(index, 1);
            emit("update:modelValue", valueList.value);
        },
    });
};

const handleBlur = (index: number) => {
    emit("update:modelValue", valueList.value);
};

const handleAddTopic = (index: number) => {
    if (!valueList.value[index].topic) valueList.value[index].topic = [];
    valueList.value[index].topic.push("");
    emit("update:modelValue", valueList.value);
};

const handleDeleteTopic = (index: number, t_index: number) => {
    valueList.value[index].topic.splice(t_index, 1);
    emit("update:modelValue", valueList.value);
};

const handleTopicBlur = (index: number, t_index: number) => {
    emit("update:modelValue", valueList.value);
};
</script>

<style scoped lang="scss">
/* --- ElInput 精细化重塑 --- */

/* 标题样式：轻盈下划线 */
:deep(.custom-title-input) {
    .el-input__wrapper {
        background-color: transparent;
        box-shadow: none !important;
        border-bottom: 1px solid #f3f4f6;
        border-radius: 0;
        padding: 4px 0;
        transition: all 0.3s ease;
        &.is-focus {
            border-bottom-color: #4f46e5;
        }
    }
    .el-input__inner {
        font-weight: 600;
        color: #111827;
        font-size: 14px;
    }
    .el-input__count {
        background: transparent;
        font-size: 10px;
        color: #9ca3af;
    }
}

:deep(.custom-textarea-input) {
    .el-textarea__inner {
        background-color: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 16px;
        padding: 16px;
        font-size: 13px;
        line-height: 1.6;
        color: #374151;
        box-shadow: none;
        transition: all 0.3s;
        &:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.05);
        }
    }
    .el-input__count {
        background: transparent;
        font-weight: bold;
        bottom: 12px;
        right: 16px;
    }
}

/* 话题微型 Input */
:deep(.custom-topic-input) {
    .el-input__wrapper {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        height: 30px;
        box-shadow: none !important;
        &.is-focus {
            border-color: #4f46e5;
        }
    }
    .el-input__inner {
        font-size: 11px;
        color: #4f46e5;
        font-weight: bold;
    }
}

/* Tooltip 样式自定义 */
:global(.custom-tooltip) {
    background-color: #111827 !important;
    border: none !important;
    border-radius: 12px !important;
    font-size: 11px !important;
    line-height: 1.6 !important;
}

/* 滚动条 */
:deep(.el-scrollbar__thumb) {
    background-color: #e5e7eb !important;
}
</style>
