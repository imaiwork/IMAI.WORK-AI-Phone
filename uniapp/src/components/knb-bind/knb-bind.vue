<template>
    <u-popup v-model="show" mode="center" width="80%" border-radius="16" closeable @close="close">
        <view class="w-full bg-white rounded-lg p-4 h-[500rpx] flex flex-col">
            <view class="text-lg font-bold text-center">知识库训练</view>
            <view class="mt-4 grow min-h-0">
                <view class="text-gray-500">请选择知识库</view>
                <view class="mt-2">
                    <data-select v-model="knbId" :localdata="knbList" />
                </view>
            </view>
            <view class="mt-4">
                <u-button type="primary" shape="circle" @click="handleConfirm">确定</u-button>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { knowledgeBaseLists } from "@/api/knowledge_base";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "confirm", knbId: string): void;
}>();

const show = ref(false);

const knbList = ref<any[]>([]);
const knbId = ref<string>("");
const getKnbList = async () => {
    const { lists } = await knowledgeBaseLists({ page_no: 1, page_size: 25000 });
    if (lists.length > 0) {
        knbList.value = lists.map((item: any) => ({
            text: item.name,
            value: item.index_id,
        }));
    }
};

const open = () => {
    show.value = true;
    getKnbList();
};

const close = () => {
    show.value = false;
    emit("close");
};

const handleConfirm = () => {
    if (!knbId.value) {
        uni.showToast({
            title: "请选择知识库",
            icon: "none",
        });
        return;
    }
    emit("confirm", knbId.value);
};

defineExpose({
    open,
    close,
});
</script>

<style scoped></style>
