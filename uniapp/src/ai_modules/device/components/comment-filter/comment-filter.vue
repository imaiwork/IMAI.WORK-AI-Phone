<template>
    <popup-bottom v-model="show" title="评论词筛选" @close="close">
        <template #content>
            <view class="h-full flex flex-col py-4">
                <view class="flex items-center px-[26rpx] gap-x-2">
                    <view class="flex-1 bg-[#F3F3F3] rounded-[10rpx] h-[90rpx] flex items-center px-4">
                        <u-input
                            class="w-full"
                            v-model="commentFilterInput"
                            placeholder="请输入评论词筛选"
                            maxlength="100"
                            clearable />
                    </view>
                    <view
                        class="w-[180rpx] h-[90rpx] flex items-center justify-center bg-black rounded-[10rpx] text-white font-bold"
                        @click="handleAddCommentFilter"
                        >添加</view
                    >
                </view>
                <template v-if="commentFilterList.length > 0">
                    <view class="flex items-center justify-between px-[26rpx] mt-[40rpx]">
                        <view class="flex items-center gap-x-5">
                            <u-checkbox-group>
                                <u-checkbox v-model="isCommentFilterAll" label-size="28">全选</u-checkbox>
                            </u-checkbox-group>
                            <view class="text-[#FF2442] font-bold text-[28rpx]" @click="handleDeleteCommentFilter"
                                >删除关键词</view
                            >
                        </view>
                        <view class="text-[#00000080] font-bold"
                            >已选：{{ commentFilterList.filter((item) => item.checked).length }}</view
                        >
                    </view>
                    <view class="grow min-h-0 mt-[70rpx]">
                        <scroll-view scroll-y class="h-full">
                            <view class="px-4 flex flex-wrap gap-2">
                                <view
                                    v-for="(item, index) in commentFilterList"
                                    :key="index"
                                    class="flex items-center gap-x-2">
                                    <u-checkbox-group>
                                        <u-checkbox v-model="item.checked" label-size="28">{{ item.value }}</u-checkbox>
                                    </u-checkbox-group>
                                </view>
                            </view>
                        </scroll-view>
                    </view>
                </template>
                <view v-else class="pt-20 flex-1">
                    <empty />
                </view>
                <view class="px-4 mt-4">
                    <view
                        class="rounded-[16rpx] h-[90rpx] bg-black text-white font-bold flex items-center justify-center"
                        @click="handleCommentFilterConfirm">
                        确定保存
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", modelValue: boolean): void;
    (e: "confirm", data: any[]): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const appStore = useAppStore();

const commentFilterList = ref<any[]>([]);
const commentFilterInput = ref<string>("");

const isCommentFilterAll = computed({
    get() {
        if (commentFilterList.value.length === 0) {
            return false;
        }
        return commentFilterList.value.every((item) => item.checked);
    },
    set(value: boolean) {
        commentFilterList.value.forEach((item) => {
            item.checked = value;
        });
    },
});

const handleAddCommentFilter = () => {
    if (commentFilterInput.value.trim() === "") {
        uni.$u.toast("请输入评论词");
        return;
    } else if (commentFilterList.value.some((item) => item.value === commentFilterInput.value)) {
        uni.$u.toast("评论词已存在");
        return;
    }
    commentFilterList.value.push({ value: commentFilterInput.value, checked: true });
    commentFilterInput.value = "";
};

const handleDeleteCommentFilter = () => {
    commentFilterList.value = commentFilterList.value.filter((item) => !item.checked);
};

const handleCommentFilterConfirm = () => {
    emit(
        "confirm",
        commentFilterList.value.filter((item) => item.checked)
    );
    close();
};

const close = () => {
    show.value = false;
    emit("close");
};

const setFormData = (data: any[]) => {
    // 这里是要判断传入的数据是不是在commentFilterList.value中，如果存在，则checked为true，否则为false
    commentFilterList.value.forEach((item) => {
        if (data.some((selectedItem) => selectedItem.value === item.value)) {
            item.checked = true;
        } else {
            item.checked = false;
        }
    });
};

watch(
    () => appStore.getCommentFilterConfig,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            commentFilterList.value = newVal.map((item: string) => ({ value: item, checked: true }));
        }
    },
    {
        immediate: true,
    }
);

defineExpose({
    setFormData,
});
</script>

<style scoped></style>
