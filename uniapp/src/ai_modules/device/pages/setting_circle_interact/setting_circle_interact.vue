<template>
    <view class="min-h-screen bg-[#F4F5F9]">
        <view class="p-4 pb-[200rpx] flex flex-col gap-y-[32rpx]">
            <view class="section-card">
                <view class="section-title">朋友圈文字内容</view>
                <view class="mt-[20rpx] relative">
                    <u-input
                        v-model="formData.content"
                        placeholder="粘贴或输入内容..."
                        placeholder-style="color:#ccc;font-size:26rpx;"
                        height="200"
                        maxlength="500"
                        type="textarea"
                        :custom-style="{ fontSize: '26rpx', lineHeight: '1.7' }" />
                    <view class="text-right text-[#ccc] text-[22rpx] mt-[12rpx]">
                        {{ formData.content.length }}/500
                    </view>
                </view>
            </view>

            <view class="section-card">
                <view class="section-title">发布内容</view>

                <view class="mt-[20rpx] flex gap-x-[24rpx]">
                    <view
                        v-for="opt in attachmentTypeOptions"
                        :key="opt.value"
                        class="attachment-type-btn"
                        :class="{ active: formData.attachment_type === opt.value }"
                        @click="handleAttachmentTypeChange(opt.value as 1 | 2)">
                        <text>{{ opt.label }}</text>
                    </view>
                </view>

                <view class="mt-[28rpx] grid grid-cols-4 gap-[16rpx]">
                    <view
                        v-if="formData.attachment_content.length < (isVideo ? 1 : 9)"
                        class="upload-add-btn"
                        @click="chooseUploadType">
                        <u-icon name="plus" size="28" color="#bbb"></u-icon>
                        <text class="text-[22rpx] text-[#bbb] mt-[6rpx]">添加</text>
                        <text class="text-[18rpx] text-[#ddd]">最多{{ isVideo ? "1个" : "9张" }}</text>
                    </view>
                    <view
                        v-for="(item, idx) in formData.attachment_content"
                        :key="idx"
                        class="relative aspect-[1/1] leading-[0]">
                        <video
                            v-if="isVideo || !item.pic"
                            :src="item.url"
                            class="w-full h-full rounded-[16rpx]"
                            :autoplay="false"
                            :controls="false"
                            :show-fullscreen-btn="false"
                            :show-center-play-btn="false"
                            :show-play-btn="false"
                            mode="aspectFill" />
                        <image v-else :src="item.pic" class="w-full h-full rounded-[16rpx]" mode="aspectFill" />
                        <view
                            class="absolute -top-[12rpx] -right-[12rpx] w-[36rpx] h-[36rpx] bg-[#00000066] rounded-full flex items-center justify-center"
                            @click="handleDeleteAttachment(idx)">
                            <u-icon name="close" size="18" color="#fff"></u-icon>
                        </view>
                        <view
                            v-if="isVideo"
                            class="absolute bottom-[8rpx] left-[8rpx] bg-[#00000066] rounded-[6rpx] px-[8rpx] py-[4rpx]">
                            <text class="text-[18rpx] text-white">视频</text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="section-card">
                <view class="section-title">发布时间</view>
                <view class="mt-[20rpx] bg-[#F4F5F9] rounded-[16rpx] p-[6rpx] flex gap-2" v-if="canShowExecTypeToggle">
                    <view
                        v-for="item in taskExecTypeOptions"
                        :key="item.value"
                        class="flex-1 flex items-center justify-center gap-x-[8rpx] h-[72rpx] rounded-[12rpx] text-[28rpx] transition-all"
                        :class="
                            formData.task_exec_type === item.value
                                ? 'bg-white text-primary font-medium shadow-[0_2rpx_8rpx_rgba(0,0,0,0.06)]'
                                : 'text-[#00000066]'
                        "
                        @click="formData.task_exec_type = item.value">
                        <u-icon
                            :name="item.icon"
                            size="30"
                            :color="formData.task_exec_type === item.value ? '#2979ff' : '#00000066'" />
                        <text>{{ item.text }}</text>
                    </view>
                </view>

                <view
                    v-if="formData.task_exec_type === 1 && canShowExecTypeToggle && showImmediateExecution"
                    class="mt-[28rpx] form-row border-[0] border-b border-solid border-[#F4F5F9]">
                    <view>
                        <view class="form-row-label">任务执行时间</view>
                        <view class="text-[#00000066] text-[22rpx] mt-[6rpx] leading-[1.6]">
                            当内容执行完成后，任务会根据<br />设定时间提前结束
                        </view>
                    </view>
                    <view class="flex items-center gap-x-[16rpx]">
                        <view class="minute-btn" @click="handleExecuteMinuteChange(-1)">
                            <text class="text-[36rpx] text-[#333] leading-none">−</text>
                        </view>
                        <view class="max-w-[130rpx] flex items-center justify-center">
                            <u-input
                                class="flex-1 font-bold"
                                v-model="formData.minutes"
                                type="digit"
                                placeholder=""
                                :custom-style="{ textAlign: 'center' }" />
                            <text class="text-[#00000066] text-[26rpx]"> 分钟</text>
                        </view>
                        <view class="minute-btn" @click="handleExecuteMinuteChange(1)">
                            <text class="text-[36rpx] text-[#333] leading-none">+</text>
                        </view>
                    </view>
                </view>

                <view class="form-row border-b border-[#F4F5F9]" @click="handleChooseAccount">
                    <view>
                        <view class="form-row-label">发布账号</view>
                        <view class="mt-[6rpx]">
                            <text
                                :class="
                                    formData.wechat_ids.length
                                        ? 'text-primary font-medium text-[28rpx]'
                                        : 'text-[#ccc] text-[26rpx]'
                                ">
                                {{
                                    formData.wechat_ids.length
                                        ? `已选 ${formData.wechat_ids.length} 个账号`
                                        : "点击选择账号"
                                }}
                            </text>
                        </view>
                    </view>
                    <u-icon name="arrow-right" size="24" color="#ccc" />
                </view>

                <view class="form-row border-b border-[#F4F5F9]">
                    <view class="form-row-label">发布日期</view>
                    <picker mode="date" @change="handleDateChange">
                        <view class="flex items-center gap-x-[8rpx]">
                            <text
                                :class="
                                    formData.date ? 'text-primary font-medium text-[28rpx]' : 'text-[#ccc] text-[26rpx]'
                                ">
                                {{ formData.date ? formatDate(formData.date) : "点击选择日期" }}
                            </text>
                            <u-icon name="arrow-right" size="24" color="#ccc" />
                        </view>
                    </picker>
                </view>

                <view class="pt-[28rpx]">
                    <view class="form-row-label">发布时间段</view>
                    <view class="mt-[16rpx]">
                        <view
                            v-if="formData.task_exec_type === 1 && showImmediateExecution && canShowExecTypeToggle"
                            class="immediate-badge">
                            <u-icon name="arrow-upward" size="26" color="#2979ff" />
                            <text class="text-primary font-medium text-[26rpx]">立即执行</text>
                        </view>
                        <view v-else class="flex items-center gap-x-[16rpx]">
                            <view class="time-picker-wrap flex-1">
                                <picker mode="time" :value="formData.time_config[0]" @change="handleStartTimeChange">
                                    <view class="flex items-center justify-between">
                                        <text
                                            :class="
                                                formData.time_config[0] ? 'text-primary font-medium' : 'text-[#ccc]'
                                            ">
                                            {{ formData.time_config[0] || "开始时间" }}
                                        </text>
                                        <u-icon name="arrow-down" size="22" color="#ccc" />
                                    </view>
                                </picker>
                            </view>
                            <view class="flex-shrink-0 text-[#ccc] text-[26rpx]">至</view>
                            <view class="time-picker-wrap flex-1">
                                <picker
                                    mode="time"
                                    :value="formData.time_config[1]"
                                    :disabled="!formData.time_config[0]"
                                    @click="handleEndTimeClick"
                                    @change="handleEndTimeChange">
                                    <view class="flex items-center justify-between">
                                        <text
                                            :class="
                                                formData.time_config[1] ? 'text-primary font-medium' : 'text-[#ccc]'
                                            ">
                                            {{ formData.time_config[1] || "结束时间" }}
                                        </text>
                                        <u-icon name="arrow-down" size="22" color="#ccc" />
                                    </view>
                                </picker>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="taskErrorMsg" class="error-card">
                <view class="flex items-center gap-x-[10rpx] mb-[12rpx]">
                    <u-icon name="warning-fill" size="28" color="#FF2442" />
                    <text class="font-medium text-[28rpx] text-[#FF2442]">任务冲突</text>
                </view>
                <text class="text-[#FF2442] text-[24rpx] leading-relaxed">{{ taskErrorMsg }}</text>
            </view>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] shadow-[0_-4rpx_16rpx_rgba(0,0,0,0.06)]">
            <u-button
                type="primary"
                :custom-style="{ height: '96rpx', fontWeight: 'bold', borderRadius: '16rpx', fontSize: '30rpx' }"
                @click="handleSubmit">
                确定保存
            </u-button>
        </view>
    </view>

    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material
        v-model="showChooseMaterial"
        :type="isVideo ? 'video' : 'image'"
        :limit="isVideo ? 1 : 9 - formData.attachment_content.length"
        @select="handleChooseMaterial" />
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm" />
</template>

<script setup lang="ts">
import { checkCirclePublishTime, checkTaskPublishTime } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { setFormData } from "@/utils/util";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import useUpload from "@/hooks/useUpload";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";

const { emit, on } = useEventBusManager();

const formData = reactive<{
    name: string;
    content: string;
    attachment_type: 1 | 2;
    attachment_content: { url: string; pic: string; size: number; type: 1 | 2 }[];
    wechat_ids: string[];
    time_config: string[];
    date: string;
    task_exec_type: number;
    minutes: number;
    task_ids: number[];
}>({
    name: `朋友圈任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    content: "",
    attachment_type: 1,
    attachment_content: [],
    wechat_ids: [],
    time_config: [
        uni.$u.timeFormat(new Date(), "hh:MM"),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), "hh:MM"),
    ],
    date: uni.$u.timeFormat(new Date(), "yyyy-mm-dd"),
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
});

const circleList = ref<any[]>([]);
const taskErrorMsg = ref("");
const showTaskMsgPop = ref(false);
const taskMsgPopContent = ref<string[]>([]);
const showChooseMaterial = ref(false);
const editIndexInList = ref(-1);

const taskExecTypeOptions = [
    { icon: "arrow-upward", text: "即时执行", value: 1 },
    { icon: "clock", text: "定时执行", value: 0 },
];

const attachmentTypeOptions = [
    { label: "图片内容", value: 1, icon: "photo" },
    { label: "视频内容", value: 2, icon: "play-right" },
];

const isVideo = computed(() => formData.attachment_type === 2);

const today = uni.$u.timeFormat(new Date(), "yyyy-mm-dd");

const showImmediateExecution = computed(() => {
    if (formData.date !== today) return false;
    const hasImmediateTask = circleList.value.some((item: any) => item.task_exec_type === 1);
    const hasIndex = circleList.value.findIndex((item: any) => item.task_exec_type === 1);
    return !hasImmediateTask || hasIndex === editIndexInList.value;
});

const canShowExecTypeToggle = computed(() => {
    if (formData.date !== today) return false;

    // 新增模式：只看是否满足立即执行条件
    // showImmediateExecution 内部已包含：今天 且 无其他立即执行任务
    return showImmediateExecution.value;
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
        formData.attachment_content.push(
            ...materials.map((item: any) => ({
                url: item.url,
                pic: item.pic || item.url,
                size: item.size,
                type: formData.attachment_type,
            }))
        );
    },
});

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ['从"微信聊天"中选择', '从"素材库"中选择', '从"手机相册"中选择'],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("file");
            else if (res.tapIndex === 1) showChooseMaterial.value = true;
            else uploadAndProcessFiles(formData.attachment_type === 1 ? "image" : "video");
        },
    });
};

const handleAttachmentTypeChange = (val: 1 | 2) => {
    formData.attachment_type = val;
    formData.attachment_content = [];
};

const handleChooseMaterial = (materials: any[]) => {
    formData.attachment_content.push(
        ...materials.map((item: any) => ({
            url: item.url,
            pic: item.pic,
            size: item.size,
            type: formData.attachment_type,
        }))
    );
};

const handleDeleteAttachment = (idx: number) => {
    formData.attachment_content.splice(idx, 1);
};

const handleExecuteMinuteChange = (delta: number) => {
    const next = formData.minutes + delta;
    if (next < 1) return;
    if (next > 9999) return;
    formData.minutes = next;
};

const handleStartTimeChange = (e: any) => {
    const { value } = e.detail;
    formData.time_config[0] = value;
    const end = new Date(`2000/01/01 ${value}`);
    end.setMinutes(end.getMinutes() + 30);
    formData.time_config[1] = uni.$u.timeFormat(end, "hh:MM");
};

const handleEndTimeClick = () => {
    if (!formData.time_config[0]) uni.$u.toast("请先选择开始时间");
};

const handleEndTimeChange = (e: any) => {
    const { value } = e.detail;
    if (value <= formData.time_config[0]) return uni.$u.toast("结束时间不能小于开始时间");
    const start = new Date(`2000/01/01 ${formData.time_config[0]}`);
    const end = new Date(`2000/01/01 ${value}`);
    if (end.getTime() - start.getTime() < 30 * 60 * 1000) return uni.$u.toast("结束时间不能小于开始时间30分钟");
    formData.time_config[1] = value;
};

const handleDateChange = (e: any) => {
    formData.date = e.detail.value;
};

const formatDate = (date: string) => uni.$u.timeFormat(new Date(date), "yyyy年mm月dd日");

const handleChooseAccount = () => {
    uni.navigateTo({
        url: `/ai_modules/device/pages/account_choose/account_choose?accounts=${JSON.stringify(
            formData.wechat_ids
        )}&platformTypes=${JSON.stringify([AppTypeEnum.WECHAT])}`,
    });
};

const getFinalExecType = (): number => {
    if (formData.task_exec_type === 1 && showImmediateExecution.value) {
        return 1;
    }
    return 0;
};

const executeSubmit = () => {
    emit("confirm", {
        type: ListenerTypeEnum.CIRCLE_INTERACT,
        data: {
            ...formData,
            task_exec_type: getFinalExecType(),
        },
    });
    uni.hideLoading();
    uni.navigateBack();
};

const handleSubmit = async () => {
    if (!formData.content) return uni.$u.toast("请输入内容");
    if (formData.attachment_content.length === 0) return uni.$u.toast(`请添加${isVideo.value ? "视频" : "图片"}`);
    if (formData.wechat_ids.length === 0) return handleChooseAccount();
    if (formData.task_exec_type == 1) {
        if (formData.minutes < 1) return uni.$u.toast("执行时间不能小于1分钟");
        if (formData.minutes > 9999) return uni.$u.toast("执行时间不能超过9999分钟");
    }

    if (!formData.date) return uni.$u.toast("请选择发布时间");

    uni.showLoading({ title: "检测中...", mask: true });

    try {
        const finalExecType = getFinalExecType();

        if (finalExecType === 1) {
            const { messages, task_ids } = await checkTaskPublishTime({
                wechat_ids: formData.wechat_ids,
                task_exec_type: finalExecType,
                minutes: formData.minutes,
            });

            uni.hideLoading();

            if (messages && messages.length > 0) {
                taskMsgPopContent.value = messages;
                formData.task_ids = task_ids;
                showTaskMsgPop.value = true;
                return;
            }

            executeSubmit();
        } else {
            await checkCirclePublishTime({
                wechat_ids: formData.wechat_ids.map((item: any) => item.account),
                time_config: `${formData.time_config[0]}-${formData.time_config[1]}`,
                date: formData.date,
            });

            executeSubmit();
        }
    } catch (error: any) {
        uni.hideLoading();
        taskErrorMsg.value = error;
        uni.$u.toast(error);
    }
};

const handleTaskMsgPopConfirm = () => {
    executeSubmit();
};

watch(
    () => formData.date,
    (newDate) => {
        if (newDate !== today && formData.task_exec_type === 1) {
            formData.task_exec_type = 0;
        }
    }
);

onLoad((options: any) => {
    if (options.data) {
        const editData = JSON.parse(options.data);
        setFormData(editData, formData);
    }
    if (options.index) {
        editIndexInList.value = parseInt(options.index);
    }
    if (options.circleList) {
        circleList.value = JSON.parse(options.circleList);
    }

    on("confirm", (res) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT && data.length > 0) {
            formData.wechat_ids = data.map((item: any) => ({
                id: item.id,
                account: item.account,
                type: item.type,
            }));
        }
    });
});
</script>

<style scoped lang="scss">
.section-card {
    @apply bg-white rounded-[24rpx] p-[32rpx];
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);
}

.section-title {
    @apply text-[30rpx] font-semibold text-[#1a1a1a];
}

.attachment-type-btn {
    @apply flex items-center gap-x-[10rpx] px-[28rpx] py-[14rpx] rounded-[12rpx] text-[26rpx] text-[#999] bg-[#F4F5F9];
    transition: all 0.2s;

    &.active {
        @apply bg-[#EEF3FF] text-primary font-medium;
        box-shadow: 0 0 0 2rpx #2979ff33;
    }
}

.upload-add-btn {
    @apply aspect-[1/1] border border-dashed border-[#ddd] rounded-[16rpx]
           flex flex-col items-center justify-center bg-[#FAFAFA];
}

.form-row {
    @apply flex items-center justify-between py-[28rpx];
}

.form-row-label {
    @apply text-[24rpx] text-[#999];
}

.minute-btn {
    @apply w-[60rpx] h-[60rpx] rounded-[12rpx] border border-solid border-[#EDEDED]
           flex items-center justify-center bg-[#FAFAFA];
}

.immediate-badge {
    @apply inline-flex items-center gap-x-[8rpx] px-[24rpx] py-[12rpx] rounded-full bg-[#EEF3FF];
}

.time-picker-wrap {
    @apply bg-[#F8F9FF] rounded-[12rpx] px-[20rpx] py-[16rpx];
    border: 1rpx solid #e8eeff;
}

.error-card {
    @apply rounded-[16rpx] p-[28rpx] bg-[#FFF2F4];
    border: 1rpx solid #ffcdd3;
}
</style>
