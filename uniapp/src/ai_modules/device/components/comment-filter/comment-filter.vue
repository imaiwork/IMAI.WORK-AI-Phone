<template>
    <popup-bottom v-model="show" title="评论词筛选" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex items-center gap-[12rpx] px-4 pt-[20rpx] pb-[16rpx]">
                    <view
                        class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] flex items-center border border-solid border-[#E5E9F0]">
                        <u-input
                            class="w-full"
                            v-model="commentFilterInput"
                            placeholder="请输入评论词筛选"
                            placeholder-style="font-size:26rpx;color:#C0C4CC;"
                            maxlength="100"
                            clearable />
                    </view>
                    <view
                        class="w-[140rpx] h-[80rpx] flex items-center justify-center rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.20)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="handleAddCommentFilter">
                        <text class="font-bold text-white">添加</text>
                    </view>
                </view>

                <template v-if="commentFilterList.length > 0">
                    <view
                        class="flex items-center justify-between px-4 py-[16rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                        <view class="flex items-center gap-[24rpx]">
                            <u-checkbox-group>
                                <u-checkbox v-model="isCommentFilterAll" label-size="26" :size="28">全选</u-checkbox>
                            </u-checkbox-group>
                            <view
                                class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] rounded-[14rpx] bg-[#FFF2F2] border border-solid border-[#FECACA]"
                                @click="handleDeleteCommentFilter">
                                <u-icon name="trash" color="#EF4444" size="20" />
                                <text class="text-xs font-semibold text-[#EF4444]">删除关键词</text>
                            </view>
                        </view>
                        <view class="flex items-center gap-[6rpx] bg-[#EBF2FF] rounded-full px-[16rpx] py-[8rpx]">
                            <text class="text-[22rpx] font-semibold text-primary">
                                已选 {{ commentFilterList.filter((item) => item.checked).length }}
                            </text>
                        </view>
                    </view>

                    <view class="grow min-h-0">
                        <scroll-view scroll-y class="h-full">
                            <view class="px-4 py-[20rpx] flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="(item, index) in commentFilterList"
                                    :key="index"
                                    class="flex items-center gap-[10rpx] rounded-[16rpx] px-[20rpx] py-[12rpx] border border-solid transition-all duration-200"
                                    :class="
                                        item.checked ? 'bg-[#EBF2FF] border-[#BFDBFE]' : 'bg-[#F7F9FC] border-[#E5E9F0]'
                                    "
                                    @click="item.checked = !item.checked">
                                    <view
                                        class="w-[32rpx] h-[32rpx] rounded-full border-[2rpx] border-solid flex items-center justify-center flex-shrink-0 transition-all duration-200"
                                        :class="
                                            item.checked ? 'bg-primary border-primary' : 'bg-white border-[#D1D5DB]'
                                        ">
                                        <u-icon v-if="item.checked" name="checkmark" color="#fff" size="16" />
                                    </view>
                                    <text class="font-medium" :class="item.checked ? 'text-primary' : 'text-[#4B5563]'">
                                        {{ item.value }}
                                    </text>
                                </view>
                            </view>
                        </scroll-view>
                    </view>
                </template>

                <view v-else class="flex-1 flex flex-col items-center justify-center py-[80rpx]">
                    <view
                        class="w-[200rpx] h-[200rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[32rpx]">
                        <view
                            class="w-[120rpx] h-[120rpx] rounded-[28rpx] flex items-center justify-center shadow-[0_6rpx_20rpx_rgba(0,101,251,0.25)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                            <u-icon name="search" color="#fff" size="40" />
                        </view>
                    </view>
                    <text class="text-[28rpx] font-extrabold text-[#0D1117] mb-[10rpx]">暂无评论词</text>
                    <text class="text-xs text-[#9CA3AF]">输入关键词并点击添加</text>
                </view>

                <view
                    class="px-4 pt-[16rpx] pb-[calc(16rpx+env(safe-area-inset-bottom))] border-[0] border-t border-solid border-[#F0F2F5]">
                    <view
                        class="h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="handleCommentFilterConfirm">
                        <text class="text-[30rpx] font-extrabold text-white tracking-wide">确定保存</text>
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
    commentFilterList.value.unshift({ value: commentFilterInput.value, checked: true });
    commentFilterInput.value = "";
};

const handleDeleteCommentFilter = () => {
    commentFilterList.value = commentFilterList.value.filter((item) => !item.checked);
};

const handleCommentFilterConfirm = () => {
    emit(
        "confirm",
        commentFilterList.value.filter((item) => item.checked),
    );
    close();
};

const close = () => {
    show.value = false;
    emit("close");
};

const setFormData = (data: any[]) => {
    commentFilterList.value = data;
};

defineExpose({
    setFormData,
});
</script>

<style scoped></style>
