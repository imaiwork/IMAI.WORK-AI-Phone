<template>
    <view class="min-h-screen p-4">
        <view class="pb-[300rpx]">
            <view>
                <view class="text-[30rpx] font-bold">朋友圈文字内容</view>
                <view class="mt-[20rpx] bg-white rounded-[20rpx] p-[24rpx] relative">
                    <u-input
                        v-model="formData.content"
                        placeholder="粘贴或输入内容"
                        class="w-full"
                        height="160"
                        maxlength="500"
                        type="textarea" />
                    <view class="text-right text-[#ccc] text-xs mt-[10rpx]"> {{ formData.content.length }}/500 </view>
                </view>
            </view>

            <view class="mt-[40rpx]">
                <view class="text-[30rpx] font-bold">发布内容</view>
                <view class="mt-[24rpx]">
                    <u-radio-group v-model="formData.attachment_type" @change="handleAttachmentTypeChange">
                        <u-radio :name="1" size="32" label-size="26">图片内容</u-radio>
                        <u-radio :name="2" size="32" label-size="26">视频内容</u-radio>
                    </u-radio-group>
                </view>
                <view class="mt-[34rpx] grid grid-cols-4 gap-[16rpx]">
                    <view
                        v-if="formData.attachment_content.length < (isVideo ? 1 : 9)"
                        class="w-[160rpx] h-[160rpx] border border-solid border-[#eee] rounded-[12rpx] flex flex-col items-center justify-center bg-white"
                        @click="chooseUploadType">
                        <u-icon name="plus" size="24" color="#999"></u-icon>
                        <text class="text-[24rpx] text-[#333] mt-[4rpx]">添加</text>
                        <text class="text-[20rpx] text-[#ccc]">最多{{ isVideo ? 1 + "个" : 9 + "张" }}</text>
                    </view>
                    <view
                        v-for="(item, idx) in formData.attachment_content"
                        :key="idx"
                        class="relative w-[160rpx] h-[160rpx] leading-[0]">
                        <video
                            v-if="isVideo || !item.pic"
                            :src="item.url"
                            class="w-full h-full rounded-[12rpx]"
                            :autoplay="false"
                            :show-loading="false"
                            :controls="false"
                            :show-fullscreen-btn="false"
                            :show-center-play-btn="false"
                            :show-play-btn="false"
                            mode="aspectFill"></video>
                        <image v-else :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill" />
                        <view
                            class="absolute top-[-10rpx] right-[-10rpx] bg-[#999] rounded-full w-4 h-4 flex items-center justify-center">
                            <u-icon name="close" size="16" color="#fff" @click="handleDeleteAttachment(idx)"></u-icon>
                        </view>
                    </view>
                </view>
            </view>

            <view class="mt-[40rpx]">
                <view class="text-[32rpx] font-bold text-[#333]">发布时间</view>
                <view class="mt-[20rpx] bg-white rounded-[20rpx] px-[24rpx]">
                    <view
                        @click="handleChooseAccount"
                        class="py-[30rpx] flex justify-between items-center border-[0] border-b border-solid border-[#f5f5f5]">
                        <view class="flex flex-col">
                            <text class="text-xs text-[#000000]/30 mb-[8rpx]">发布账号</text>
                            <text
                                :class="[formData.wechat_ids.length ? 'text-primary font-medium' : 'text-[#000000]/30']"
                                >{{ formData.wechat_ids.length || "请选择" }}个账号</text
                            >
                        </view>
                        <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                    </view>
                    <view class="py-[30rpx] border-[0] border-b border-solid border-[#f5f5f5]">
                        <view class="text-[24rpx] text-[#999] mb-[8rpx]">发布时间</view>
                        <picker mode="date" @change="handleDateChange">
                            <view class="flex items-center justify-between">
                                <text :class="[formData.date ? 'text-primary  font-medium' : 'text-[#000000]/30']">{{
                                    formData.date ? formatDate(formData.date) : "请选择"
                                }}</text>
                                <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                            </view>
                        </picker>
                    </view>
                    <view class="py-[30rpx] flex items-center gap-x-4">
                        <view class="flex items-center gap-2 flex-1">
                            <picker
                                mode="time"
                                class="w-full"
                                :value="formData.time_config[0]"
                                @change="handleStartTimeChange">
                                <view class="flex items-center justify-between h-[50rpx]">
                                    <text
                                        :class="[
                                            formData.time_config[0] ? 'text-primary font-bold' : 'text-[#00000033]',
                                        ]"
                                        >{{ formData.time_config[0] || "开始时间" }}</text
                                    >
                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                </view>
                            </picker>
                        </view>
                        <text class="text-[#999] text-[28rpx]">至</text>
                        <view class="flex items-center gap-2 flex-1">
                            <picker
                                mode="time"
                                class="w-full"
                                :value="formData.time_config[1]"
                                :disabled="!formData.time_config[0]"
                                @click="handleEndTimeClick"
                                @change="handleEndTimeChange">
                                <view class="flex items-center justify-between h-[50rpx]">
                                    <text
                                        :class="[
                                            formData.time_config[1] ? 'text-primary font-bold' : 'text-[#00000033]',
                                        ]"
                                        >{{ formData.time_config[1] || "结束时间" }}</text
                                    >
                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                </view>
                            </picker>
                        </view>
                    </view>
                </view>
            </view>
            <view class="mt-[50rpx]" v-if="taskErrorMsg">
                <view class="font-bold">任务冲突：</view>
                <view class="text-font-bold text-[#ff2442] text-xs mt-[20rpx]">
                    {{ taskErrorMsg }}
                </view>
            </view>
        </view>
        <view class="fixed bottom-0 left-0 right-0 bg-white p-4 z-[77]">
            <u-button type="primary" :custom-style="{ height: '90rpx', fontWeight: 'bold' }" @click="handleSubmit"
                >确定保存</u-button
            >
        </view>
    </view>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material
        v-model="showChooseMaterial"
        :type="isVideo ? 'video' : 'image'"
        :limit="isVideo ? 1 : 9 - formData.attachment_content.length"
        @select="handleChooseMaterial" />
</template>

<script setup lang="ts">
import { checkCirclePublishTime } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import useUpload from "@/hooks/useUpload";
import { setFormData } from "@/utils/util";

const { emit, on } = useEventBusManager();

const formData = reactive<{
    name: string;
    content: string;
    attachment_type: 1 | 2;
    attachment_content: { url: string; pic: string; size: number; type: 1 | 2 }[];
    wechat_ids: string[];
    time_config: string[];
    date: string;
}>({
    name: `朋友圈任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    content: "", //内容
    attachment_type: 1, //附件类型 1：图片 2：短视频
    attachment_content: [],
    wechat_ids: [],
    time_config: ["00:00", "00:30"], //时间区间
    date: "", //日期
});

const showChooseMaterial = ref(false);
const taskErrorMsg = ref("");

const isVideo = computed(() => {
    return formData.attachment_type === 2;
});

const { showUploadProgress, uploadAndProcessFiles, uploadMaterialList } = useUpload({
    count: 9,
    imageAccept: ["jpg", "png", "jpeg", "webp", "gif"],
    imageSize: 20,
    videoAccept: ["mp4", "mov", "m4a"],
    videoSize: 200,
    onSuccess: (materials: any[]) => {
        if (isVideo.value) {
            formData.attachment_content = [materials[0]];
            return;
        }
        materials = materials.map((item: any) => ({
            url: item.url,
            pic: item.pic || item.url,
            size: item.size,
            type: formData.attachment_type,
        }));
        formData.attachment_content.push(...materials);
    },
});

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ['从"微信聊天"中选择', '从"素材库"中选择', '从"手机相册"中选择'],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("file");
            else if (res.tapIndex === 1) showChooseMaterial.value = true;
            else if (res.tapIndex === 2) uploadAndProcessFiles(formData.attachment_type === 1 ? "image" : "video");
        },
    });
};

const handleAttachmentTypeChange = (e: any) => {
    formData.attachment_content = [];
};

const handleChooseMaterial = (materials: any[]) => {
    materials = materials.map((item: any) => ({
        url: item.content,
        pic: item.pic,
        size: item.size,
        type: formData.attachment_type,
    }));
    formData.attachment_content.push(...materials);
};

const handleDeleteAttachment = (idx: number) => {
    formData.attachment_content.splice(idx, 1);
};

const handleStartTimeChange = (e: any) => {
    const { value } = e.detail;
    // 判断时间不能小于当前时间
    const endTime = new Date(`2000/01/01 ${value}`);
    formData.time_config[0] = value;
    endTime.setMinutes(endTime.getMinutes() + 30);
    formData.time_config[1] = uni.$u.timeFormat(endTime, "hh:MM");
};

const handleEndTimeClick = () => {
    if (!formData.time_config[0]) {
        uni.showToast({
            title: "请先选择开始时间",
            icon: "none",
        });
        return;
    }
};

const handleChooseAccount = () => {
    uni.navigateTo({
        url: `/ai_modules/device/pages/account_choose/account_choose?accounts=${JSON.stringify(
            formData.wechat_ids
        )}&platformTypes=${JSON.stringify([AppTypeEnum.WECHAT])}`,
    });
};

const handleEndTimeChange = (e: any) => {
    const { value } = e.detail;
    // 这里需要判断结束时间是否大于开始时间，并且要大于开始
    if (value <= formData.time_config[0]) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const startTIme = new Date(`2000/01/01 ${formData.time_config[0]}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() - startTIme.getTime() < 30 * 60 * 1000) {
        uni.$u.toast(`结束时间不能小于开始时间30分钟`);
        return;
    }
    formData.time_config[1] = value;
};

const handleDateChange = (e: any) => {
    const { value } = e.detail;
    formData.date = value;
};

const formatDate = (date: string) => {
    return uni.$u.timeFormat(new Date(date), "yyyy年mm月dd日");
};

const handleSubmit = async () => {
    if (!formData.content && formData.attachment_content.length === 0) {
        uni.$u.toast("请输入内容或添加图片/视频");
        return;
    }
    if (formData.wechat_ids.length === 0) {
        handleChooseAccount();
        return;
    }
    if (!formData.date) {
        uni.$u.toast("请选择发布时间");
        return;
    }
    uni.showLoading({
        title: "保存中...",
    });
    try {
        await checkCirclePublishTime({
            wechat_ids: formData.wechat_ids.map((item: any) => item.account),
            time_config: `${formData.time_config[0]}-${formData.time_config[1]}`,
            date: formData.date,
        });
        emit("confirm", {
            type: ListenerTypeEnum.CIRCLE_INTERACT,
            data: formData,
        });
        uni.navigateBack();
    } catch (error: any) {
        taskErrorMsg.value = error;
    } finally {
        uni.hideLoading();
    }
};

onLoad((options: any) => {
    if (options.data) {
        setFormData(JSON.parse(options.data), formData);
    }
    on("confirm", (res) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length > 0) {
                formData.wechat_ids = data.map((item: any) => ({ id: item.id, account: item.account }));
            }
        }
    });
});
</script>

<style></style>
