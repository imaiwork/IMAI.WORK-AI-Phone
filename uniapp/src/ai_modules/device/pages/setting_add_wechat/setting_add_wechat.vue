<template>
    <view class="h-screen flex flex-col">
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="p-4">
                    <view class="text-[30rpx] font-bold">加好友备注设置</view>
                    <view class="mt-4 rounded-[20rpx] bg-white p-[40rpx]">
                        <u-radio-group v-model="formData.type" class="w-full">
                            <view class="flex justify-between w-full">
                                <u-radio
                                    v-for="(item, index) in [
                                        { value: 1, label: '固定话术' },
                                        { value: 2, label: 'AI回复' },
                                        { value: 3, label: 'AI根据固话优化' },
                                    ]"
                                    :name="item.value"
                                    :size="28">
                                    <text class="text-base">{{ item.label }}</text>
                                </u-radio>
                            </view>
                        </u-radio-group>
                        <view class="flex flex-wrap gap-2 mt-[36rpx]" v-if="formData.type == 1">
                            <view
                                v-for="(item, index) in formData.wechat_remarks"
                                :key="index"
                                class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 py-[12rpx] flex items-center gap-x-2 break-all"
                                @click="handleEditWechatRemark(index)">
                                {{ item }}
                                <view
                                    class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click.stop="handleWechatRemarkDelete(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                            <view
                                class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] py-[12rpx] flex items-center justify-center gap-x-1"
                                @click="handleEditWechatRemark(-1)">
                                <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                <text class="text-primary font-bold">添加</text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="好友备注"
        @confirm="handleKeywordsEditConfirm" />
</template>

<script setup lang="ts">
import { createAutoTaskAddWechatConfig, getAutoTaskAddWechatConfigDetail } from "@/api/device";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";

import { useAppStore } from "@/stores/app";

const appStore = useAppStore();

const deviceCode = ref<string>("");

const getWechatRemarks = computed(() => {
    return appStore.config.wechat_remarks || [];
});

const formData = reactive<{
    type: number;
    wechat_remarks: any[];
}>({
    type: 1,
    wechat_remarks: getWechatRemarks.value || [],
});

const showKeywordsEdit = ref(false);
const editIndex = ref<number>(-1);
const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>();

const handleEditWechatRemark = (index: number) => {
    showKeywordsEdit.value = true;
    editIndex.value = index;
    keywordsEditRef.value?.setFormData(formData.wechat_remarks[index] || "");
};

const handleWechatRemarkDelete = (index: number) => {
    formData.wechat_remarks.splice(index, 1);
};

const handleKeywordsEditConfirm = (keywords: string[]) => {
    if (editIndex.value === -1) {
        formData.wechat_remarks.push(keywords);
    } else {
        formData.wechat_remarks[editIndex.value!] = keywords;
    }
    editIndex.value = -1;
    showKeywordsEdit.value = false;
};

const handleSaveConfig = async () => {
    if (formData.type === 1 && formData.wechat_remarks.length === 0) {
        uni.$u.toast("请添加好友备注");
        return;
    }
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await createAutoTaskAddWechatConfig({
            device_code: deviceCode.value,
            speech_type: formData.type,
            remarks: formData.wechat_remarks,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        uni.navigateBack();
    } catch (error) {
        uni.hideLoading();
        uni.showToast({
            title: "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getDetail = async () => {
    const res = await getAutoTaskAddWechatConfigDetail({
        device_code: deviceCode.value,
    });
    formData.type = res.speech_type;
    formData.wechat_remarks = res.remarks;
};

watch(
    () => getWechatRemarks.value,
    (newVal) => {
        formData.wechat_remarks = newVal || [];
    }
);

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    getDetail();
});
</script>

<style scoped></style>
