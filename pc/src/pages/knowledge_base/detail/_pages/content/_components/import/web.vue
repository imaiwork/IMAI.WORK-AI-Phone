<template>
    <div class="h-full flex flex-col px-4 pb-4">
        <div class="flex-shrink-0 bg-white rounded-[24px] border border-br p-5 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                <span class="text-[14px] font-[900] text-[#1E293B]">链接解析</span>
            </div>

            <div class="relative">
                <ElInput
                    v-model="url"
                    placeholder="请输入以 http:// 或 https:// 开头的网页地址..."
                    type="textarea"
                    resize="none"
                    :rows="5"
                    class="custom-textarea" />

                <div class="flex items-center justify-between mt-3 pl-1">
                    <div class="text-[11px] text-[#94A3B8] font-medium flex items-center gap-1">
                        <Icon name="el-icon-QuestionFilled" :size="14" />
                        解析后将自动提取网页正文内容
                    </div>
                    <ElButton
                        type="primary"
                        class="!rounded-xl !h-10 !px-8 !font-black bg-primary border-primary transition-all hover:opacity-90"
                        :loading="isLock"
                        @click="lockFn">
                        开始解析
                    </ElButton>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col bg-slate-50 rounded-[24px] border border-[#F1F5F9] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#F1F5F9] bg-white flex items-center justify-between">
                <div class="text-[13px] font-[900] text-[#1E293B]">解析结果明细</div>
                <div class="text-xs font-medium text-primary bg-[#F0F6FF] px-2 py-0.5 rounded">
                    已解析 {{ formData.length }} 条内容
                </div>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar v-if="formData.length > 0">
                    <div class="p-5 flex flex-col gap-y-4">
                        <div v-for="(item, itemIndex) in formData" :key="itemIndex" class="parse-result-card group">
                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-[#F1F5F9]">
                                <Icon name="el-icon-Link" class="text-primary" :size="14" />
                                <span class="text-xs font-black text-[#64748B] truncate flex-1">
                                    {{ item.name }}
                                </span>
                                <div
                                    class="w-6 h-6 rounded flex items-center justify-center cursor-pointer hover:bg-red-50 hover:text-red-500 transition-all text-[#CBD5E1]"
                                    @click="handleDeleteStage(itemIndex)">
                                    <Icon name="el-icon-Delete" :size="14" />
                                </div>
                            </div>

                            <data-item
                                v-for="(value, index) in item.data"
                                v-model:data="value.q"
                                :name="item.name"
                                :key="index"
                                :index="index"
                                @delete="handleDeleteStage(itemIndex)" />
                        </div>
                    </div>
                </ElScrollbar>

                <div v-else class="py-10 opacity-40 grayscale text-center">
                    <p class="text-[13px] font-medium text-[#94A3B8] mt-2">暂无解析数据，请在上方输入链接</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { webHtmlCapture } from "@/api/knowledge_base";
import type { IDataItem } from "./hook";
import DataItem from "./data-item.vue";

const formData = defineModel<IDataItem[]>("modelValue", { required: true });
const url = ref("");

const { lockFn, isLock } = useLockFn(async () => {
    if (!url.value) return feedback.msgError("请输入网页链接");
    try {
        const urls = url.value.split("\n").filter(Boolean);
        const data = await webHtmlCapture({
            url: urls,
        });

        const newItems = data.map((item: any) => ({
            data: [{ a: "", q: item.content }],
            path: item.url,
            name: item.url,
            size: 0,
        }));

        formData.value = [...newItems, ...formData.value];
        url.value = "";
        feedback.msgSuccess("解析成功");
    } catch (error) {
        feedback.msgError(error as string);
    }
});

const handleDeleteStage = (index: number) => {
    formData.value.splice(index, 1);
};
</script>

<style scoped lang="scss">
.parse-result-card {
    @apply bg-white rounded-xl border border-br p-4 transition-all;
    &:hover {
        @apply border-[#0065fb]/30;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }
}

:deep(.el-button--primary) {
    @apply bg-primary border-primary;

    &:hover {
        opacity: 0.9;
        box-shadow: 0 8px 16px rgba(var(--el-color-primary), 0.2);
    }
}
</style>
