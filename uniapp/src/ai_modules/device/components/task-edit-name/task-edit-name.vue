<template>
    <u-popup v-model="show" mode="center" width="90%" border-radius="64" :closeable="false" @close="show">
        <view class="flex flex-col p-[32rpx]">
            <view class="text-[26rpx] text-center mt-[14rpx]">编辑名称</view>
            <view class="h-[100rpx] rounded-xl bg-[#00000005] flex items-center px-[18rpx] mt-[46rpx]">
                <input
                    v-model="formData.name"
                    class="w-full"
                    placeholder="请输入任务名称"
                    placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)"
                    :focus="show"
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
                        @click="show = false"
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
                        @click="handleConfirm"
                        >确定</u-button
                    >
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { updateDeviceTaskName } from "@/api/device";
import { setFormData } from "@/utils/util";

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits(["update:modelValue", "success"]);

const show = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const formData = reactive({
    id: "",
    sub_task_id: "",
    source: "",
    name: "",
});

const handleConfirm = async () => {
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await updateDeviceTaskName(formData);
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        show.value = false;
        emit("success", formData);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

defineExpose({
    setFormData: (data: any) => {
        setFormData(data, formData);
    },
});
</script>

<style scoped></style>
