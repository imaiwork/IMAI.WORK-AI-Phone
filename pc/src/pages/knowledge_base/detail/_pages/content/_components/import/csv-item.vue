<template>
    <div class="group/item relative">
        <div class="flex items-center justify-between mb-3 px-1">
            <div class="flex items-center gap-2">
                <span class="bg-[#1E293B] text-white px-2 py-0.5 rounded text-[10px] font-black italic">
                    #{{ (index + 1).toString().padStart(2, "0") }}
                </span>
                <span class="text-xs font-medium text-[#94A3B8] truncate max-w-[200px]">
                    {{ name }}
                </span>
            </div>

            <div class="flex items-center gap-2 opacity-0 group-hover/item:opacity-100 transition-opacity">
                <div class="action-btn" :class="{ 'is-editing': isEdit }" @click="handleEdit">
                    <Icon :name="isEdit ? 'el-icon-CircleCheck' : 'local-icon-edit3'" :size="14" />
                    <span class="text-[11px] ml-1">{{ isEdit ? "保存" : "修改" }}</span>
                </div>
                <div class="action-btn hover:!text-red-500 hover:!bg-red-50" @click="handleDelete">
                    <Icon name="local-icon-delete" :size="14" />
                </div>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden border border-[#F1F5F9] transition-all" :class="{ 'edit-ring': isEdit }">
            <div class="flex min-h-[40px]">
                <div class="w-10 flex-shrink-0 bg-[#F0F6FF] flex items-center justify-center border-r border-white">
                    <span class="text-[16px] font-black text-primary">Q</span>
                </div>
                <div class="grow p-3 bg-slate-50">
                    <div
                        class="text-[14px] font-black text-[#1E293B] outline-none"
                        ref="editRef"
                        :contenteditable="isEdit"
                        :class="{ 'bg-white p-1 rounded border border-primary/20': isEdit }"
                        @input="(e) => emit('update:q', (e.target as HTMLElement).innerText)">
                        {{ q }}
                    </div>
                </div>
            </div>

            <div class="flex min-h-[60px] border-t border-white">
                <div class="w-10 flex-shrink-0 bg-[#FFF5EB] flex items-center justify-center border-r border-white">
                    <span class="text-[16px] font-black text-[#FF8D1A]">A</span>
                </div>
                <div class="grow p-3 bg-[#FFFBF7]">
                    <div
                        class="text-[13px] text-[#585A73] leading-6 outline-none"
                        :contenteditable="isEdit"
                        :class="{ 'bg-white p-1 rounded border border-[#FF8D1A]/20': isEdit }"
                        @input="(e) => emit('update:a', (e.target as HTMLElement).innerText)">
                        {{ a }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isEdit" class="mt-2 text-[11px] font-medium text-primary flex items-center gap-1">
            <Icon name="el-icon-Edit" /> 正在编辑此条目，修改后点击上方保存
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    index: number;
    name: string;
    q: string;
    a: string;
}>();

const emit = defineEmits<{
    (event: "delete"): void;
    (event: "update:q", value: string): void;
    (event: "update:a", value: string): void;
}>();

const editRef = shallowRef();
const isEdit = ref(false);
const handleEdit = async (): Promise<void> => {
    isEdit.value = !isEdit.value;
    await nextTick();
    editRef.value.focus();
};

const handleDelete = (): void => {
    useNuxtApp().$confirm({
        message: "确定要删除该问答吗？",
        onConfirm: () => {
            emit("delete");
        },
    });
};
</script>

<style scoped lang="scss">
$primary: var(--el-color-primary);

.action-btn {
    @apply flex items-center px-2 py-1 rounded bg-[#F1F5F9] text-[#64748B] cursor-pointer transition-all;
    &:hover {
        @apply bg-[#0065fb]/[0.02] text-primary;
    }

    &.is-editing {
        @apply bg-primary text-white;
        box-shadow: 0 4px 8px rgba($primary, 0.2);
    }
}

.edit-ring {
    border-color: $primary !important;
    box-shadow: 0 0 0 3px rgba($primary, 0.1);
}
</style>
