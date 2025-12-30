<template>
    <popup-bottom v-model="show" title="选择评论时间" height="40%" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 flex flex-col items-center justify-center">
                    <picker-view
                        :value="commentTimeIndex"
                        indicator-style="height: 100rpx;"
                        indicator-class="font-bold"
                        @change="changeCommentTime">
                        <picker-view-column>
                            <view
                                v-for="(item, index) in commentTimeList"
                                :key="index"
                                class="h-[100rpx] flex items-center justify-center"
                                >{{ item.label }}</view
                            >
                        </picker-view-column>
                    </picker-view>
                </view>
                <view class="flex justify-around p-4 border-[0] border-t-[1rpx] border-solid border-[rgba(0,0,0,0.03)]">
                    <view
                        class="w-[180rpx] h-[76rpx] flex items-center justify-center rounded-[10rpx] bg-[#F3F3F3] text-[30rpx] text-[#00000080] font-bold"
                        @click="cancelCommentTime"
                        >取消</view
                    >
                    <view
                        class="w-[180rpx] h-[76rpx] flex items-center justify-center rounded-[10rpx] bg-primary text-[30rpx] text-white font-bold"
                        @click="confirmCommentTime"
                        >确定</view
                    >
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        value: number[];
        list: any[];
    }>(),
    {
        modelValue: false,
        value: () => [0],
        list: () => [],
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", value: any): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value: boolean) {
        emit("update:modelValue", value);
    },
});

const commentTimeList = computed(() => props.list);
const commentTimeIndex = ref(props.value);

const changeCommentTime = (e: any) => {
    commentTimeIndex.value = e.detail.value;
};

const cancelCommentTime = () => {
    close();
    commentTimeIndex.value = props.value;
};

const confirmCommentTime = () => {
    emit("confirm", commentTimeIndex.value);
    close();
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style scoped lang="scss">
picker-view {
    height: 100%;
    width: 100%;
}
</style>
