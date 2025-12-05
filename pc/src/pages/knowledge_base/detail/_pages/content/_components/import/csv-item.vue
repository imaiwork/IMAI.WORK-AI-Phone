<template>
    <div>
        <div class="flex items-center">
            <span class="bg-[#F6F6F6] px-4 rounded-md"> #{{ index + 1 }} </span>
            <span class="mx-2 text-[#000] flex-1 line-clamp-1">
                {{ name }}
            </span>
            <div class="flex items-center gap-x-2">
                <ElTooltip :content="isEdit ? '取消编辑' : '编辑'">
                    <div class="handle-item" @click="handleEdit">
                        <Icon name="local-icon-edit3" />
                    </div>
                </ElTooltip>
                <ElTooltip content="删除">
                    <div class="handle-item" @click="handleDelete">
                        <Icon name="local-icon-delete" />
                    </div>
                </ElTooltip>
            </div>
        </div>
        <div class="mt-2">
            <div class="rounded flex overflow-hidden">
                <div class="bg-[#DEE5FA] w-[48px] flex-shrink-0 flex items-center justify-center">
                    <span class="text-xl font-bold text-primary">Q</span
                    ><span class="text-[10px] font-bold text-[#FF8D1A]">A</span>
                </div>
                <div class="bg-[#E8F0FF] grow p-3">
                    <div class="flex gap-2">
                        <div class="text-primary font-bold">问:</div>
                        <div
                            class="font-bold"
                            ref="editRef"
                            :contenteditable="isEdit"
                            @input="(event) => emit('update:q', (event.target as HTMLElement).innerText)">
                            {{ q }}
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <div class="text-[#FF8D1A] font-bold">答:</div>
                        <div
                            class="text-xs text-[#585A73] leading-[20px]"
                            :contenteditable="isEdit"
                            @input="(event) => emit('update:a',(event.target as HTMLElement).innerText)">
                            {{ a }}
                        </div>
                    </div>
                </div>
            </div>
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
.handle-item {
    @apply w-6 h-6 rounded bg-[#0000000d] flex items-center justify-center cursor-pointer;
}
</style>
