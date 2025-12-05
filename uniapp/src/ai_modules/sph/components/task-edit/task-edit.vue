<template>
    <u-popup
        v-model="showEditPopup"
        mode="center"
        width="90%"
        border-radius="64"
        :closeable="false"
        @close="handleCloseEditPopup">
        <view class="flex flex-col p-[32rpx]">
            <view class="text-[26rpx] text-center mt-[14rpx]">请输入任务名称</view>
            <view class="h-[100rpx] rounded-xl bg-[#00000005] flex items-center px-[18rpx] mt-[46rpx]">
                <u-input
                    v-model="formData.name"
                    class="w-full"
                    placeholder="请输入任务名称"
                    placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)"
                    focus
                    :maxlength="30" />
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
                        @click="close"
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
                        :disabled="!formData.name"
                        @click="lockFn"
                        >确定</u-button
                    >
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { useLockFn } from "@/hooks/useLockFn";
import { updateTask } from "@/api/sph";

const emit = defineEmits(["close", "success"]);

const formData = reactive({
    id: "",
    name: "",
});

const showEditPopup = ref(false);

const handleCloseEditPopup = () => {
    showEditPopup.value = false;
};

const open = () => {
    showEditPopup.value = true;
};

const close = () => {
    showEditPopup.value = false;
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    uni.showLoading({
        title: "更新中...",
        mask: true,
    });
    try {
        await updateTask(formData);
        close();
        emit("success");
        uni.hideLoading();
        uni.showToast({
            title: "更新成功",
            icon: "none",
            duration: 3000,
        });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
});

const setFormData = (data: any) => {
    formData.id = data.id;
    formData.name = data.name;
};

defineExpose({
    open,
    setFormData,
});
</script>

<style scoped></style>
