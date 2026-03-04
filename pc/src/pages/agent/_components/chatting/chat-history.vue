<template>
    <div class="h-full flex flex-col w-[300px] border-r border-[#F1F5F9] shrink-0 bg-slate-50">
        <div class="h-[72px] shrink-0 flex items-center px-6 bg-white border-b border-[#F1F5F9]">
            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl transition-all cursor-pointer group hover:bg-[#F1F5F9]"
                @click="router.back()">
                <span class="text-[#64748B] group-hover:text-primary transition-colors leading-[0]">
                    <Icon name="el-icon-ArrowLeft" />
                </span>
                <span class="text-[14px] font-black text-[#64748B] group-hover:text-primary">返回列表</span>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between">
                <span class="text-xs font-black text-[#94A3B8] tracking-wider uppercase"
                    >历史记录 ({{ pager.lists.length }})</span
                >
            </div>

            <ElScrollbar v-if="pager.lists.length" class="flex-1" @end-reached="emit('load-more')">
                <div class="px-3 pb-4 space-y-1">
                    <div
                        v-for="item in pager.lists"
                        :key="item.id"
                        class="group relative h-[60px] px-4 rounded-2xl flex items-center gap-x-3 cursor-pointer transition-all duration-300"
                        :class="[
                            isActive(item)
                                ? 'bg-white border-br shadow-active-item'
                                : 'bg-[transparent] border-[transparent] hover:bg-[#F1F5F9]',
                        ]"
                        style="border-width: 1px"
                        @click="emit('select', item)">
                        <div
                            class="absolute left-1 w-1 h-6 bg-primary rounded-full transition-all duration-300"
                            :class="isActive(item) ? 'opacity-100' : 'opacity-0 scale-y-50'"></div>

                        <div class="flex-1 overflow-hidden">
                            <div
                                class="text-[14px] font-medium truncate transition-colors"
                                :class="isActive(item) ? 'text-primary' : 'text-[#475569]'">
                                {{ item.title || "新对话" }}
                            </div>
                            <div class="text-[11px] text-[#94A3B8] font-medium mt-0.5">
                                {{ item.create_time || "刚刚" }}
                            </div>
                        </div>

                        <div
                            class="shrink-0 w-6 h-6 rounded-lg flex items-center justify-center transition-all bg-[#F1F5F9] hover:bg-red-50 hover:text-red-500 opacity-0 group-hover:opacity-100"
                            @click.stop="emit('delete', item)">
                            <Icon name="el-icon-Delete" :size="14"></Icon>
                        </div>
                    </div>
                </div>

                <load-text :is-load="pager.isLoad"></load-text>
            </ElScrollbar>

            <div v-else class="grow flex flex-col items-center justify-center grayscale opacity-40">
                <ElEmpty :image-size="80" description="暂无对话记录" />
            </div>
        </div>

        <div class="shrink-0 p-5 bg-white border-t border-[#F1F5F9] space-y-3">
            <button
                class="w-full h-12 rounded-xl bg-[#0065FB] text-white flex items-center justify-center gap-2 font-black text-[14px] transition-all active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none custom-save-shadow"
                :disabled="isReceiving"
                @click="emit('create')">
                <Icon name="el-icon-Plus" :size="16" />
                新建对话
            </button>
            <button
                class="w-full h-11 rounded-xl bg-white border border-br text-[#64748B] flex items-center justify-center gap-2 font-black text-[13px] transition-all hover:bg-slate-50 hover:text-red-500 hover:border-red-100 disabled:opacity-50"
                :disabled="isReceiving"
                @click="emit('delete')">
                <Icon name="el-icon-RefreshRight" :size="16" />
                清除所有对话
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
// 逻辑保持一致...
const props = defineProps<{
    pager: any;
    currentRecordId: string | null;
    isReceiving: boolean;
}>();

const emit = defineEmits<{
    (e: "select", item: any): void;
    (e: "create"): void;
    (e: "delete", item?: any): void;
    (e: "load-more"): void;
}>();

const router = useRouter();

const isActive = (item: any) => {
    const id = item.task_id || item.conversation_id;
    return props.currentRecordId == id;
};
</script>

<style scoped lang="scss">
.shadow-active-item {
    box-shadow: 0 4px 12px -2px rgba(0, 101, 251, 0.08);
}

.custom-save-shadow {
    box-shadow: 0 8px 16px -4px rgba(0, 101, 251, 0.3);
}

:deep(.el-scrollbar__bar.is-vertical) {
    width: 4px;
    .el-scrollbar__thumb {
        background-color: #e2e8f0;
        &:hover {
            background-color: #cbd5e1;
        }
    }
}
</style>
