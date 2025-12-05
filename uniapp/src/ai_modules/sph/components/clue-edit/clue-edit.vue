<template>
    <u-popup v-model="show" mode="center" width="90%" border-radius="64" :closeable="false" @close="close">
        <view class="flex flex-col p-[32rpx]">
            <view class="text-[26rpx] text-center mt-[14rpx]">请输入检索词</view>
            <view class="h-[100rpx] rounded-xl bg-[#00000005] flex items-center px-[18rpx] mt-[46rpx]">
                <input
                    v-model="editValue"
                    class="w-full"
                    placeholder="请输入检索词"
                    placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)"
                    :focus="show"
                    :maxlength="50" />
            </view>
            <view class="flex gap-x-[24rpx] mt-[36rpx] w-full">
                <view class="flex-1">
                    <u-button
                        shape="circle"
                        :custom-style="{
                            height: '100rpx',
                            boxShadow: '0 0 0 1px rgba(0, 0, 0, 0.1)',
                            backgroundColor: 'transparent',
                            color: 'rgba(0,0,0,0.3)',
                            fontSize: '26rpx',
                        }"
                        @click="close()"
                        >取消</u-button
                    >
                </view>
                <view class="flex-1">
                    <u-button
                        type="primary"
                        shape="circle"
                        :custom-style="{
                            flex: 1,
                            height: '100rpx',
                            boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                            fontSize: '26rpx',
                        }"
                        @click="confirm"
                        >确定</u-button
                    >
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits(["confirm", "close", "update:modelValue"]);

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});

const editValue = ref<string>("");

const confirm = () => {
    emit("confirm", editValue.value);
};

const close = () => {
    show.value = false;
    editValue.value = "";
    emit("close");
};

const setFormData = (data: any) => {
    editValue.value = data;
};

defineExpose({
    setFormData,
});
</script>

<style scoped></style>
