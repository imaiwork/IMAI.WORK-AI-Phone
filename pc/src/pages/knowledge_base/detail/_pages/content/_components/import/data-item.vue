<template>
    <div>
        <div class="flex items-center">
            <span class="bg-[#F6F6F6] px-4 rounded-md"> #{{ index + 1 }} </span>
            <span class="mx-2 text-[#000] flex-1 line-clamp-1">
                {{ name }}
            </span>
            <div class="flex items-center gap-x-2">
                <ElTooltip content="编辑">
                    <div class="handle-item" @click="handleEdit">
                        <Icon name="local-icon-edit3" />
                    </div>
                </ElTooltip>
                <div class="handle-item" @click="handleDelete">
                    <Icon name="local-icon-delete" />
                </div>
            </div>
        </div>
        <div class="mt-2">
            <div ref="editRef">
                {{ stageValue }}
            </div>
        </div>
    </div>
    <popup
        ref="editPopRef"
        v-if="showEdit"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        width="600px"
        :show-close="false">
        <div class="px-4">
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="showEdit = false">
                <close-btn />
            </div>
            <div class="text-2xl font-bold mb-5">编辑分段</div>
            <div>
                <ElInput v-model="editValue" type="textarea" resize="none" :rows="10" />
            </div>
            <div class="flex justify-center mt-5">
                <ElButton
                    type="primary"
                    class="!rounded-full !h-[50px] w-[310px] shadow-[0_6px_12px_0px_#0065FB33]"
                    @click="handleSave">
                    保存
                </ElButton>
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

const editRef = shallowRef();
const showEdit = ref(false);
const editValue = ref("");
const editPopRef = shallowRef();
const handleEdit = async (): Promise<void> => {
    showEdit.value = true;
    await nextTick();
    editValue.value = stageValue.value;
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
.handle-item {
    @apply w-6 h-6 rounded bg-[#0000000d] flex items-center justify-center cursor-pointer;
}
</style>
