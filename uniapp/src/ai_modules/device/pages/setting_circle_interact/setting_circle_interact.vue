<template>
    <view class="flex flex-col min-h-screen bg-[#F7F9FC]">
        <view class="p-4 pb-[200rpx] flex flex-col gap-y-[24rpx]">
            <view
                class="bg-white rounded-[28rpx] p-[32rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                <view class="flex items-center gap-[10rpx] mb-[24rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[30rpx] font-extrabold text-[#0D1117]">朋友圈文字内容</text>
                </view>
                <view class="bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[20rpx]">
                    <u-input
                        v-model="formData.content"
                        placeholder="粘贴或输入内容..."
                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                        height="200"
                        maxlength="500"
                        type="textarea"
                        :auto-height="false"
                        :custom-style="{ fontSize: '26rpx', lineHeight: '1.8', color: '#0D1117' }" />
                </view>
                <view class="flex items-center justify-end mt-[12rpx]">
                    <text
                        class="text-[22rpx]"
                        :class="formData.content.length >= 500 ? 'text-[#EF4444] font-bold' : 'text-[#C0C4CC]'">
                        {{ formData.content.length }} / 500
                    </text>
                </view>
            </view>
            <view
                class="bg-white rounded-[28rpx] p-[32rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                <view class="flex items-center gap-[10rpx] mb-[24rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[30rpx] font-extrabold text-[#0D1117]">发布内容</text>
                </view>

                <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] mb-[28rpx]">
                    <view
                        v-for="opt in attachmentTypeOptions"
                        :key="opt.value"
                        class="flex-1 h-[72rpx] rounded-[16rpx] flex items-center justify-center font-semibold transition-all duration-200"
                        :class="
                            formData.attachment_type === opt.value
                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                : 'text-[#9CA3AF]'
                        "
                        @click="handleAttachmentTypeChange(opt.value as 1 | 2)">
                        {{ opt.label }}
                    </view>
                </view>

                <view class="grid grid-cols-4 gap-[16rpx]">
                    <view
                        v-if="formData.attachment_content.length < (isVideo ? 1 : 9)"
                        class="aspect-[1/1] border border-dashed border-[#BFDBFE] rounded-[20rpx] flex flex-col items-center justify-center bg-[#F7F9FC]"
                        @click="showUploadCategoryPanel = true">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[8rpx]">
                            <u-icon name="plus" size="24" color="#0065fb" />
                        </view>
                        <text class="text-[20rpx] text-[#9CA3AF]">最多{{ isVideo ? "1个" : "9张" }}</text>
                    </view>
                    <view
                        v-for="(item, idx) in formData.attachment_content"
                        :key="idx"
                        class="relative aspect-[1/1] leading-[0]">
                        <video
                            v-if="isVideo || !item.pic"
                            :src="item.url"
                            class="w-full h-full rounded-[20rpx]"
                            :autoplay="false"
                            :controls="false"
                            :show-fullscreen-btn="false"
                            :show-center-play-btn="false"
                            :show-play-btn="false"
                            mode="aspectFill" />
                        <image v-else :src="item.pic" class="w-full h-full rounded-[20rpx]" mode="aspectFill" />
                        <view
                            class="absolute -top-[12rpx] -right-[12rpx] w-[40rpx] h-[40rpx] bg-[#0D1117]/50 rounded-full flex items-center justify-center"
                            @click="handleDeleteAttachment(idx)">
                            <u-icon name="close" size="16" color="#fff" />
                        </view>
                        <view
                            v-if="isVideo"
                            class="absolute bottom-[10rpx] left-[10rpx] bg-[#000000]/40 rounded-[8rpx] px-[10rpx] py-[4rpx]">
                            <text class="text-[18rpx] text-white font-medium">视频</text>
                        </view>
                    </view>
                </view>
            </view>

            <view
                class="bg-white rounded-[28rpx] p-[32rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                <view class="flex items-center gap-[10rpx] mb-[24rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[30rpx] font-extrabold text-[#0D1117]">发布时间</text>
                </view>

                <view v-if="canShowExecTypeToggle" class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] mb-[24rpx]">
                    <view
                        v-for="item in taskExecTypeOptions"
                        :key="item.value"
                        class="flex-1 flex items-center justify-center gap-x-[8rpx] h-[72rpx] rounded-[16rpx] font-semibold transition-all duration-200"
                        :class="
                            formData.task_exec_type === item.value
                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                : 'text-[#9CA3AF]'
                        "
                        @click="formData.task_exec_type = item.value">
                        <u-icon
                            :name="item.icon"
                            size="28"
                            :color="formData.task_exec_type === item.value ? '#0065fb' : '#9CA3AF'" />
                        <text>{{ item.text }}</text>
                    </view>
                </view>

                <view
                    v-if="formData.task_exec_type === 1 && canShowExecTypeToggle && showImmediateExecution"
                    class="flex items-center justify-between py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5] mb-[8rpx]">
                    <view>
                        <text class="text-[28rpx] font-semibold text-[#0D1117] block mb-[8rpx]">任务执行时间</text>
                        <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed"
                            >当内容执行完成后，任务会根据设定时间提前结束</text
                        >
                    </view>
                    <view class="flex items-center gap-[12rpx] flex-shrink-0 ml-[16rpx]">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                            @click="handleExecuteMinuteChange(-1)">
                            <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                        </view>
                        <view
                            class="w-[100rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                            <u-input
                                v-model="formData.minutes"
                                type="digit"
                                placeholder=""
                                :custom-style="{
                                    color: '#0065fb',
                                    fontWeight: '800',
                                    fontSize: '28rpx',
                                    textAlign: 'center',
                                }"
                                input-align="center" />
                        </view>
                        <text class="text-xs text-[#9CA3AF]">分钟</text>
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                            @click="handleExecuteMinuteChange(1)">
                            <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                        </view>
                    </view>
                </view>

                <view
                    class="flex items-center justify-between py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                    @click="handleChooseAccount">
                    <view>
                        <text class="text-xs text-[#9CA3AF] block mb-[8rpx]">发布账号</text>
                        <text
                            class="text-[28rpx] font-semibold"
                            :class="formData.wechat_ids.length ? 'text-primary' : 'text-[#C0C4CC]'">
                            {{
                                formData.wechat_ids.length
                                    ? `已选 ${formData.wechat_ids.length} 个账号`
                                    : "点击选择账号"
                            }}
                        </text>
                    </view>
                    <u-icon name="arrow-right" size="22" color="#C0C4CC" />
                </view>

                <view
                    class="flex items-center justify-between py-[24rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <text class="text-xs text-[#9CA3AF]">发布日期</text>
                    <picker mode="date" @change="handleDateChange">
                        <view class="flex items-center gap-[8rpx]">
                            <text
                                class="text-[28rpx] font-semibold"
                                :class="formData.date ? 'text-primary' : 'text-[#C0C4CC]'">
                                {{ formData.date ? formatDate(formData.date) : "点击选择日期" }}
                            </text>
                            <u-icon name="arrow-right" size="22" color="#C0C4CC" />
                        </view>
                    </picker>
                </view>

                <view class="pt-[24rpx]">
                    <text class="text-xs text-[#9CA3AF] block mb-[16rpx]">发布时间段</text>
                    <view
                        v-if="formData.task_exec_type === 1 && showImmediateExecution && canShowExecTypeToggle"
                        class="inline-flex items-center gap-[8rpx] px-[24rpx] py-[14rpx] rounded-full bg-[#EBF2FF]">
                        <u-icon name="arrow-upward" size="24" color="#0065fb" />
                        <text class="text-primary font-semibold">立即执行</text>
                    </view>
                    <view v-else class="flex items-center gap-[16rpx]">
                        <view
                            class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                            <picker mode="time" :value="formData.time_config[0]" @change="handleStartTimeChange">
                                <view class="flex items-center justify-between">
                                    <text
                                        class="font-semibold"
                                        :class="formData.time_config[0] ? 'text-primary' : 'text-[#C0C4CC]'">
                                        {{ formData.time_config[0] || "开始时间" }}
                                    </text>
                                    <u-icon name="arrow-down" size="20" color="#C0C4CC" />
                                </view>
                            </picker>
                        </view>
                        <text class="text-xs text-[#9CA3AF] flex-shrink-0">至</text>
                        <view
                            class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                            <picker
                                mode="time"
                                :value="formData.time_config[1]"
                                :disabled="!formData.time_config[0]"
                                @click="handleEndTimeClick"
                                @change="handleEndTimeChange">
                                <view class="flex items-center justify-between">
                                    <text
                                        class="font-semibold"
                                        :class="formData.time_config[1] ? 'text-primary' : 'text-[#C0C4CC]'">
                                        {{ formData.time_config[1] || "结束时间" }}
                                    </text>
                                    <u-icon name="arrow-down" size="20" color="#C0C4CC" />
                                </view>
                            </picker>
                        </view>
                    </view>
                </view>
            </view>

            <view
                v-if="taskErrorMsg"
                class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                <view
                    class="flex items-center gap-[10rpx] px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="w-[6rpx] h-[32rpx] bg-[#EF4444] rounded-full" />
                    <u-icon name="warning-fill" size="26" color="#EF4444" />
                    <text class="text-[28rpx] font-bold text-[#EF4444]">任务冲突</text>
                </view>
                <view class="px-[28rpx] py-[20rpx]">
                    <text class="text-[#EF4444] leading-relaxed">{{ taskErrorMsg }}</text>
                </view>
            </view>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] shadow-[0_-4rpx_16rpx_rgba(0,0,0,0.06)]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleSubmit">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">确定保存</text>
            </view>
        </view>
    </view>

    <upload-category-panel
        v-model="showUploadCategoryPanel"
        :show-categories="[
            isVideo ? UploadAlbumTypeEnum.Video : UploadAlbumTypeEnum.Image,
            UploadCategoryEnum.Library,
            UploadCategoryEnum.Creation,
        ]"
        @select="handleSelectCategory" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <choose-material
        v-model="showChooseMaterial"
        :type="isVideo ? 'video' : 'image'"
        :limit="isVideo ? 1 : 9 - formData.attachment_content.length"
        @select="handleChooseMaterial" />
    <choose-history
        v-model="showHistory"
        :type="isVideo ? 'video' : 'image'"
        :limit="isVideo ? 1 : 9 - formData.attachment_content.length"
        @select="handleSelectHistory" />
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm" />
</template>
<script setup lang="ts">
import { checkCirclePublishTime, checkTaskPublishTime } from "@/api/device";
import { AppTypeEnum, UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";
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

const showUploadCategoryPanel = ref(false);

const circleList = ref<any[]>([]);
const taskErrorMsg = ref("");
const showTaskMsgPop = ref(false);
const taskMsgPopContent = ref<string[]>([]);
const showChooseMaterial = ref(false);
const editIndexInList = ref(-1);
const showHistory = ref(false);

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
    imageResolution: [99999, 99999],
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
            })),
        );
    },
});

const handleSelectCategory = (category: UploadAlbumTypeEnum | UploadCategoryEnum) => {
    if (category === UploadAlbumTypeEnum.Image || category === UploadAlbumTypeEnum.Video) {
        uploadAndProcessFiles(category);
    } else if (category === UploadCategoryEnum.Library) {
        showChooseMaterial.value = true;
    } else if (category === UploadCategoryEnum.Creation) {
        showHistory.value = true;
    }
};

const handleSelectHistory = (history: any[]) => {
    const validHistory = history.map((item: any) => ({
        url: item.url,
        pic: item.pic,
        size: item.size,
        type: formData.attachment_type,
    }));
    if (isVideo.value) {
        formData.attachment_content = [validHistory[0]];
    } else {
        formData.attachment_content.push(...validHistory);
    }
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
        })),
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
            formData.wechat_ids,
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
    },
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
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) {
                formData.wechat_ids = [];
                return;
            }
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
    @apply flex items-center gap-x-[10rpx] px-[28rpx] py-[14rpx] rounded-[12rpx]  text-[#999] bg-[#F4F5F9];
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
    @apply text-xs text-[#999];
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
