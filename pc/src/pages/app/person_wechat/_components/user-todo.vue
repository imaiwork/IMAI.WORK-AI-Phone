<template>
    <div class="flex flex-col gap-5 p-1">
        <div
            v-for="(item, index) in list"
            :key="index"
            class="todo-card group"
            :class="{ 'is-folded': foldIndex.includes(item.id) }">
            <div
                class="absolute top-0 left-0 right-0 h-1 transition-all duration-300"
                :class="item.todo_type == 0 ? 'bg-amber-400' : 'bg-primary'"></div>

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-slate-50 rounded-full border border-slate-100">
                        <Icon name="local-icon-send_plane_fill" color="var(--color-primary)" :size="14"></Icon>
                        <span class="text-slate-700 font-black text-xs">{{ item.todo_time }}</span>
                    </div>
                    <span
                        class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider"
                        :class="item.todo_type == 0 ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-primary'">
                        {{ item.todo_type == 0 ? "手动待办" : "自动跟进" }}
                    </span>
                </div>

                <div
                    class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                    <ElButton
                        circle
                        size="small"
                        class="!border-none !bg-slate-50 hover:!bg-red-50 hover:!text-red-500 !text-slate-400"
                        @click="emit('delete', item.id)">
                        <Icon name="el-icon-Delete" :size="14" />
                    </ElButton>
                    <ElButton
                        circle
                        size="small"
                        class="!border-none !bg-slate-50 hover:!bg-blue-50 hover:!text-primary !text-slate-400"
                        @click="emit('edit', item)">
                        <Icon name="el-icon-Edit" :size="14" />
                    </ElButton>
                    <ElButton
                        circle
                        size="small"
                        class="!border-none !bg-slate-50 hover:!bg-slate-100 !text-slate-400"
                        @click="handleFoldContent(item.id)">
                        <Icon
                            :name="foldIndex.includes(item.id) ? 'el-icon-ArrowDown' : 'el-icon-ArrowUp'"
                            :size="14" />
                    </ElButton>
                </div>
            </div>

            <div class="todo-content" :class="foldIndex.includes(item.id) ? 'is-collapsed' : 'is-expanded'">
                <div
                    class="p-4 bg-[#f8fafc]/50 rounded-2xl border border-slate-50 text-slate-600 text-[14px] leading-relaxed relative overflow-hidden">
                    <div class="absolute -right-2 -bottom-4 opacity-[0.03] pointer-events-none">
                        <Icon name="el-icon-ChatDotSquare" :size="80" />
                    </div>
                    {{ item.todo_content }}
                </div>
            </div>

            <div
                v-if="foldIndex.includes(item.id)"
                class="py-3 px-4 bg-[#f8fafc]/50 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-400 font-medium cursor-pointer hover:bg-slate-100 transition-colors"
                @click="handleFoldContent(item.id)">
                点击展开待办详情...
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    list: any[];
}>();

const emit = defineEmits<{
    (e: "edit", id: string | number): void;
    (e: "delete", id: string | number): void;
}>();

const foldIndex = ref<string[]>([]);

const handleFoldContent = (id: any) => {
    if (foldIndex.value.includes(id)) {
        foldIndex.value = foldIndex.value.filter((item) => item !== id);
    } else {
        foldIndex.value.push(id);
    }
};
</script>

<style scoped lang="scss">
.todo-card {
    @apply relative bg-white rounded-[24px] p-6 transition-all duration-300 border border-slate-100 overflow-hidden;

    &:hover {
        @apply shadow-light shadow-[#0065fb]/5 border-[#0065fb]/20 -translate-y-0.5;
    }
}

.todo-content {
    @apply transition-all duration-500 ease-in-out overflow-hidden;

    &.is-collapsed {
        @apply max-h-0 opacity-0 scale-y-95;
    }

    &.is-expanded {
        @apply max-h-[1000px] opacity-100 scale-y-100;
    }
}
</style>
