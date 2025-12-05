<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            title="自动加好友"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-3 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="step-item-icon" v-else> </view>
                    <text class="step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <view v-if="step === 1" class="flex flex-col h-full">
                <view class="px-4">
                    <view
                        class="flex items-center justify-center gap-x-2 bg-primary h-[100rpx] rounded-[10rpx]"
                        @click="handleAddTask">
                        <image
                            src="@/ai_modules/device/static/images/common/add_circle_white.png"
                            class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">添加任务</text>
                    </view>
                </view>
                <view class="px-4 font-bold text-[30rpx] mt-[60rpx]"> 任务列表({{ taskList.length }}) </view>
                <view class="grow min-h-0 mt-2">
                    <scroll-view class="h-full" scroll-y v-if="taskList.length">
                        <view class="px-4 pb-[100rpx] flex flex-col gap-y-2">
                            <view class="relative" v-for="(item, index) in taskList" :key="index">
                                <view
                                    class="absolute top-2 right-2 w-5 h-5 rounded-full flex items-center justify-center bg-[#0000004d] z-[22]"
                                    @click="handleDeleteTask(index)">
                                    <u-icon name="close" size="20" color="#ffffff"></u-icon>
                                </view>
                                <clue-card :data="item" :type="item.file_type" />
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="mt-[100rpx]">
                        <empty :size="260" text="您还没有添加任务哦" />
                    </view>
                </view>
            </view>
            <view v-if="step === 2" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-4 pb-[100rpx]">
                        <view>
                            <view class="text-[30rpx] font-bold">加微设置</view>
                            <view class="mt-[24rpx] rounded-[24rpx] bg-white px-5 py-[24rpx]">
                                <view>
                                    <view class="text-xs text-[#0000004d] mb-3">加微微信</view>
                                    <data-select
                                        v-model="formData.wechat_id"
                                        multiple
                                        :localdata="optionsData.wechatLists"></data-select>
                                </view>
                                <view class="mt-[32rpx]">
                                    <view class="text-xs text-[#0000004d] mb-3">加微规则</view>
                                    <data-select
                                        v-model="formData.wechat_reg_type"
                                        :clear="false"
                                        :localdata="[
                                            { text: '全部', value: 0 },
                                            { text: '微信号', value: 1 },
                                            { text: '手机号', value: 2 },
                                        ]"></data-select>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[32rpx]">
                            <view class="text-[30rpx] font-bold">频率设置</view>
                            <view class="mt-[24rpx] rounded-[24rpx] bg-white px-5 py-[24rpx]">
                                <view>
                                    <view class="text-xs text-[#0000004d]">每日添加线索数量</view>
                                    <view class="grid grid-cols-5 gap-x-2 mt-[20rpx]">
                                        <view
                                            v-for="(item, index) in dayNumList"
                                            :key="index"
                                            class="day-num-item"
                                            :class="{ active: formData.add_number == item }"
                                            @click="handleDayNum(item)">
                                            {{ item }}条
                                        </view>
                                    </view>
                                </view>
                                <view class="h-[2rpx] bg-[#00000008] my-4"></view>
                                <view>
                                    <view class="text-xs text-[#0000004d]">每个账号添加时间间隔</view>
                                    <view class="grid grid-cols-4 gap-2 mt-[20rpx]">
                                        <view
                                            v-for="(item, index) in timeIntervalList"
                                            :key="index"
                                            class="time-interval-item"
                                            :class="{ active: timeIntervalIndex == index }"
                                            @click="handleTimeInterval(item, index)">
                                            {{ item }}分钟
                                        </view>
                                    </view>
                                    <view class="flex mt-2">
                                        <view
                                            class="bg-[#F6F6F6] flex items-center px-4 py-2 rounded-[16rpx] border border-solid text-xs border-[#F1F2F5]"
                                            :class="{
                                                ' border-primary bg-white text-primary font-bold':
                                                    timeIntervalIndex == 4,
                                            }"
                                            @click="timeIntervalIndex = 4">
                                            <text>自定义</text>
                                            <template v-if="timeIntervalIndex == 4">
                                                <view class="px-2 w-[120rpx]">
                                                    <u-input
                                                        v-model="timeInterval"
                                                        type="digit"
                                                        height="20"
                                                        placeholder="请输入"
                                                        placeholder-style="color: #00000080;font-size: 24rpx;"
                                                        @focus="timeIntervalIndex = 4" />
                                                </view>
                                                <text>分钟</text>
                                            </template>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-5">
                            <view class="flex items-center justify-between gap-x-2">
                                <view class="font-bold text-[30rpx]">加好友备注设置</view>
                                <view class="flex items-center gap-x-2" @click="handleEditRemark(-1)">
                                    <image
                                        src="@/ai_modules/device/static/images/common/add_circle.png"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="font-bold">新增</text>
                                </view>
                            </view>
                            <view class="mt-5 flex flex-wrap gap-2">
                                <view
                                    v-for="(item, index) in formData.remarks"
                                    :key="index"
                                    class="remark-item"
                                    @click="handleEditRemark(index)">
                                    {{ item }}
                                    <view
                                        class="absolute top-[-10rpx] right-[-10rpx] w-[32rpx] h-[32rpx] rounded-full bg-[#0000004d] flex items-center justify-center"
                                        @click.stop="handleDeleteRemark(index)">
                                        <u-icon name="close" size="16" color="#ffffff"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
            <view v-if="step === 3" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[100rpx]">
                        <bast-setting-v2
                            v-model="formData"
                            :current-frequency="currentFrequency"
                            @change-frequency="currentFrequency = $event" />
                    </view>
                </scroll-view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[taskList.length > 0 ? 'bg-primary' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ taskList.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view v-else>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="handleStep(step, 'prev')">
                            上一步
                        </view>
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-primary' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-primary text-white font-bold flex items-center justify-center"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <u-popup v-model="showRemarkPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">输入加好友备注</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newRemark"
                    placeholder="请输入备注内容"
                    maxlength="100"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="closeRemarkPopup">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleRemarkConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        :show-close="false"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
</template>

<script setup lang="ts">
import { createManualAddWechat } from "@/api/device";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";
import { useAppStore } from "@/stores/app";
import { getWeChatLists } from "@/api/person_wechat";
import { useDictOptions } from "@/hooks/useDictOptions";
import ClueCard from "@/ai_modules/device/components/clue-card/clue-card.vue";
import BastSettingV2 from "@/ai_modules/device/components/bast-setting-v2/bast-setting-v2.vue";

const appStore = useAppStore();

// 步骤
const steps = ref([
    { step: 1, title: "选择线索" },
    { step: 2, title: "调设置" },
    { step: 3, title: "设定时间" },
]);
const step = ref(1);

const getWechatRemarks = computed(() => {
    return appStore.config.wechat_remarks || [];
});

const formData = reactive<{
    name: string;
    source: 1 | 2;
    fileurl: string;
    crawling_task_ids: string[];
    add_type: "0" | "1";
    add_number: number;
    add_interval_time: number;
    add_friends_prompt: string;
    add_remark_enable: 0 | 1;
    remarks: string[];
    wechat_id: string[];
    wechat_reg_type: 0 | 1 | 2;
    time_config: string[];
    task_frep: number;
    device_codes: string[];
    custom_date: string[];
}>({
    name: `自动加好友任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    crawling_task_ids: [],
    add_number: 1,
    add_interval_time: 5,
    remarks: getWechatRemarks.value || [],
    add_remark_enable: 1,
    add_friends_prompt: "",
    source: 1,
    fileurl: "",
    add_type: "1",
    wechat_id: [],
    wechat_reg_type: 0,
    time_config: ["09:00", "09:30"],
    task_frep: 1,
    device_codes: [],
    custom_date: [],
});

const taskList = ref<any[]>([]);

const uploadMaterialList = ref<any[]>([]);
const showUploadProgress = ref(false);

const dayNumList = [1, 3, 5, 10, 15];
// 时间间隔
const timeIntervalList = [5, 10, 15, 20];
const timeIntervalIndex = ref(0);
const timeInterval = ref();

const showRemarkPopup = ref(false);
const newRemark = ref("");
const editRemarkIndex = ref(-1);

const currentFrequency = ref(0);
const taskErrorMsg = ref("");

const showCreateTaskSuccessDialog = ref(false);

const { optionsData } = useDictOptions<{
    wechatLists: any[];
}>({
    wechatLists: {
        api: getWeChatLists,
        params: { page_size: 9999 },
        transformData: (res: any) =>
            res.lists?.map((item: any) => ({
                text: item.wechat_nickname,
                value: item.wechat_id,
            })),
    },
});

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return taskList.value.length > 0;
        case 2:
            if (formData.wechat_id.length == 0) {
                return false;
            }
            if (timeIntervalIndex.value == 4 && !timeInterval.value) {
                return false;
            }
            formData.add_remark_enable = formData.remarks.length == 0 ? 0 : 1;
            return true;
        case 3:
            return true;
        default:
            return false;
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 点击“上一步”
    if (type === "prev") {
        step.value--;
        return;
    }

    // 点击“下一步”
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: string } = {
                1: "请至少添加一个线索",
                3: "请设定时间",
            };
            uni.$u.toast(messages[step.value] || "请完成当前步骤");
        }
        return;
    }

    // 直接点击步骤条进行跳转
    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const progressCallback = (progress: number, options: { tempFilePath: string }) => {
    const targetIndex = uploadMaterialList.value.findIndex(
        (material) => material.tempFilePath === options.tempFilePath
    );
    if (targetIndex !== -1) {
        uploadMaterialList.value[targetIndex] = {
            ...uploadMaterialList.value[targetIndex],
            progress: progress,
        };
    }
};

const handleDeleteTask = (index: number) => {
    taskList.value.splice(index, 1);
};

const handleDayNum = (item: number) => {
    formData.add_number = item;
};

const handleTimeInterval = (item: number, index: number) => {
    timeIntervalIndex.value = index;
    formData.add_interval_time = item;
};

const handleAddTask = () => {
    uni.showActionSheet({
        itemList: ["从聊天记录中选择文件（需要符合模板）", "从获客任务中选择线索"],
        success: async (res) => {
            if (res.tapIndex === 0) {
                try {
                    uploadMaterialList.value = [];
                    const { tempFiles } = await chooseFile({
                        type: "file",
                        extension: [".csv", ".xlsx"],
                        count: 1,
                    });
                    const fileList = [];
                    for (const file of tempFiles) {
                        if (file.size > 20 * 1024 * 1024) {
                            uni.$u.toast(`文件大小不能超过20M`);
                            continue;
                        }
                        fileList.push(file);
                    }
                    if (fileList.length === 0) return;
                    uploadMaterialList.value = fileList;
                    showUploadProgress.value = true;
                    for (const item of uploadMaterialList.value) {
                        const fileRes: any = await uploadFile("file", { filePath: item.path }, (progress) =>
                            progressCallback(progress, item)
                        );
                        if (formData.source == 2) {
                            formData.source = 1;
                        }
                        taskList.value.length = 0;
                        taskList.value.push({
                            url: fileRes.uri,
                            name: item.name,
                            size: item.size,
                            file_type: 1,
                        });
                    }
                    if (uploadMaterialList.value.every((item) => item.progress === 100)) {
                        showUploadProgress.value = false;
                    }
                } catch (error) {
                    uni.$u.toast(error);
                    uploadMaterialList.value = [];
                    showUploadProgress.value = false;
                }
            } else {
                uni.navigateTo({
                    url: "/ai_modules/device/pages/wechat_clue/wechat_clue",
                });
            }
        },
        fail: (err) => {
            console.log(err);
        },
    });
};

const handleEditRemark = (index: number) => {
    showRemarkPopup.value = true;
    editRemarkIndex.value = index ?? -1;

    if (index > -1) {
        newRemark.value = formData.remarks[index];
    }
};

const handleRemarkConfirm = () => {
    if (editRemarkIndex.value == -1) {
        formData.remarks.push(newRemark.value);
    } else {
        formData.remarks[editRemarkIndex.value] = newRemark.value;
    }
    editRemarkIndex.value = -1;
    showRemarkPopup.value = false;
};

const closeRemarkPopup = () => {
    showRemarkPopup.value = false;
    newRemark.value = "";
    editRemarkIndex.value = -1;
};

const handleDeleteRemark = (index: number) => {
    formData.remarks.splice(index, 1);
};

const handleCreateTaskSuccess = () => {
    showCreateTaskSuccessDialog.value = false;
    uni.$u.route({
        url: "/pages/phone/phone",
        type: "reLaunch",
    });
};

const handleCreateTask = async () => {
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return false;
    }
    if (formData.device_codes.length == 0) {
        uni.$u.toast("请选择设备");
        return false;
    }
    if (currentFrequency.value === 5 && !formData.custom_date.length) {
        uni.$u.toast("请选择任务日期");
        return;
    }
    if (!formData.time_config[0] || !formData.time_config[1]) {
        uni.$u.toast("请选择时间");
        return false;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createManualAddWechat({
            ...formData,
            crawling_task_ids: formData.source == 2 ? taskList.value.map((item) => item.id) : [],
            fileurl: formData.source == 1 ? taskList.value[0].url : "",
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            add_interval_time: timeIntervalIndex.value == 4 ? timeInterval.value : formData.add_interval_time,
        });
        uni.hideLoading();
        showCreateTaskSuccessDialog.value = true;
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

watch(
    () => appStore.config.wechat_remarks,
    (newVal) => {
        formData.remarks = newVal || [];
    }
);

onLoad(() => {
    uni.$on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.WECHAT_CLUE) {
            if (data && data.length > 0) {
                if (formData.source == 1) {
                    taskList.value = [];
                    formData.fileurl = "";
                    formData.source = 2;
                }
                taskList.value = taskList.value.concat(data);
            }
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) return;
            formData.custom_date = data;
            currentFrequency.value = 5;
        }
        if (type === ListenerTypeEnum.CHOOSE_DEVICE) {
            if (data.length === 0) return;
            formData.device_codes = data;
        }
    });
});

onUnload(() => {
    uni.$off("confirm");
});
</script>

<style scoped lang="scss">
.step-item {
    @apply flex flex-col items-center justify-center relative;
    &.active {
        .step-item-icon {
            @apply shadow-[0_0_0_2rpx_rgba(0,101,251,0.3)]  flex items-center justify-center;
            &::before {
                content: "";
                @apply w-[60%] h-[60%] bg-primary rounded-full;
            }
        }
        .step-item-title {
            @apply text-[#00000099];
        }
    }
    &-success-icon {
        @apply bg-[#0065fb4d] rounded-full w-[28rpx] h-[28rpx] flex items-center justify-center;
    }
    &-icon {
        @apply w-[28rpx] h-[28rpx] rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)];
    }
    &-title {
        @apply mt-[20rpx] text-[rgba(0,0,0,0.2)] font-bold text-xs;
    }
    &-line {
        @apply absolute right-[-18%] top-[15rpx] w-[40%] border-[0] border-b border-dashed border-[#D1D6D4];
    }
}
.day-num-item,
.time-interval-item {
    @apply bg-[#F6F6F6] rounded-[16rpx]  py-2 text-center text-xs text-[#00000080] border border-solid border-[#F6F6F6];
    &.active {
        @apply bg-white border-primary text-primary font-bold;
    }
}
.remark-item {
    @apply bg-white rounded-[10rpx] text-[#000000b3] px-[28rpx] py-[16rpx] text-xs relative;
}
</style>
