<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            title="创建发布任务"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4">
                    <view>
                        <view class="flex items-center gap-x-1">
                            <text class="text-[#FF3C26] text-[32rpx]">*</text>
                            <text class="font-bold">基础设置</text>
                        </view>
                        <view
                            class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                            <view>
                                <view class="text-[#7C7E80]">任务名称</view>
                                <view class="mt-[12rpx]">
                                    <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                        <u-input
                                            v-model="formData.name"
                                            placeholder-style="font-size: 24rpx;"
                                            placeholder="请输入任务名称"
                                            maxlength="50" />
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[28rpx]">
                                <view class="text-[#7C7E80]">发布账号选择</view>
                                <view class="mt-[12rpx]">
                                    <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                        <navigator
                                            :url="`/ai_modules/device/pages/account_choose/account_choose?account=${JSON.stringify(
                                                formData.accounts
                                            )}`"
                                            class="flex items-center justify-between h-[70rpx]"
                                            hover-class="none">
                                            <text
                                                :class="[
                                                    formData.accounts.length ? 'text-[#00B862]' : 'text-[#00000033]',
                                                ]"
                                                >{{
                                                    formData.accounts.length
                                                        ? `${formData.accounts.length}个账号`
                                                        : "选择账号"
                                                }}</text
                                            >
                                            <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                        </navigator>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[28rpx]">
                                <view class="text-[#7C7E80]">发布频率（每日）</view>
                                <view class="mt-[28rpx] flex items-center gap-x-[36rpx]">
                                    <view
                                        v-for="item in [1, 2, 3, 5, 10]"
                                        :key="item"
                                        class="prompt-length-item"
                                        :class="{ active: formData.publish_frep === item }"
                                        @click="handleFrequency(item)">
                                        {{ item }}条
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[32rpx]" v-if="formData.publish_frep > 0">
                        <view class="flex items-center gap-x-1">
                            <text class="text-[#FF3C26] text-[32rpx]">*</text>
                            <text class="font-bold">发布时间</text>
                        </view>
                        <view class="mb-[28rpx]">
                            <u-notice-bar
                                mode="vertical"
                                padding="20rpx 0"
                                border-radius="8"
                                font-size="24rpx"
                                :autoplay="false"
                                :volume-icon="false"
                                :list="[`发布的间隔时间必须大于${timeInterval}分钟`]"></u-notice-bar>
                        </view>
                        <view
                            class="mt-4 rounded-[16rpx] px-4 py-[28rpx] bg-white shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                            <view class="flex flex-col gap-y-[28rpx]">
                                <view v-for="(item, index) in formData.time_config" :key="index">
                                    <view class="text-[#7C7E80]">每天第{{ index + 1 }}个任务发布时间</view>
                                    <view class="mt-[12rpx] flex items-center gap-x-4">
                                        <view
                                            class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="item.start_time"
                                                @change="handleStartTimeChange($event, index)">
                                                <view class="flex items-center justify-between h-[70rpx]">
                                                    <text
                                                        :class="[
                                                            timeErrors[index]?.start_time
                                                                ? 'text-[#FF3C26] font-bold'
                                                                : item.start_time
                                                                ? 'text-[#00B862] font-bold'
                                                                : 'text-[#00000033]',
                                                        ]"
                                                        >{{ item.start_time || "开始时间" }}</text
                                                    >
                                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                                </view>
                                            </picker>
                                        </view>
                                        <view class="text-[#7C7E80]">至</view>
                                        <view
                                            class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="item.end_time"
                                                :disabled="!item.end_time"
                                                @click="handleEndTimeClick(index)"
                                                @change="handleEndTimeChange($event, index)">
                                                <view class="flex items-center justify-between h-[70rpx]">
                                                    <text
                                                        :class="[
                                                            timeErrors[index]?.end_time
                                                                ? 'text-[#FF3C26] font-bold'
                                                                : item.end_time
                                                                ? 'text-[#00B862] font-bold'
                                                                : 'text-[#00000033]',
                                                        ]"
                                                        >{{ item.end_time || "结束时间" }}</text
                                                    >
                                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                                </view>
                                            </picker>
                                        </view>
                                    </view>
                                </view>
                                <view v-if="Object.keys(timeErrors).length > 0" class="mt-2 text-[#FF3C26]">
                                    时间配置存在冲突
                                </view>
                            </view>
                        </view>
                    </view>
                    <view v-if="taskErrorMsg" class="mt-5">
                        <view>任务冲突</view>
                        <view class="text-[#FF2442] mt-[20rpx] text-xs">
                            {{ taskErrorMsg }}
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="flex-shrink-0 pb-5 pt-2">
            <view class="flex items-center justify-between px-4 gap-[48rpx]">
                <view
                    class="flex-1 flex items-center justify-center text-white rounded-[20rpx] h-[100rpx] font-bold"
                    :class="[canCreateTask ? 'bg-black' : 'bg-[#787878CC]']"
                    @click="createTask">
                    立即创建任务
                </view>
            </view>
        </view>
    </view>
    <u-popup v-model="showCreate" mode="center" border-radius="48" width="90%" :mask-close-able="false">
        <view class="bg-white rounded-[48rpx] p-[28rpx]">
            <view class="rounded-full w-[80rpx] h-[80rpx] mx-auto flex items-center justify-center bg-black mt-[40rpx]">
                <u-icon name="checkmark" color="#ffffff" size="28"></u-icon>
            </view>
            <view class="mt-[28rpx] text-center">创建成功</view>
            <view
                class="w-full h-[100rpx] text-white flex items-center justify-center rounded-[50rpx] bg-black mt-[66rpx] shadow-[0_12rpx_24rpx_0_rgba(0,101,251,0.2)]"
                @click="handleBackHome">
                返回
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { createShanjianPublish } from "@/api/digital_human";
import { isJson } from "@/utils/util";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";

const formData = reactive<{
    name: string;
    accounts: any[];
    publish_frep: number;
    video_ids: any[];
    time_config: any[];
    media_type: number;
    data_type: number;
    task_type: number;
    scene: number; // 1: 创建任务 2: 发布任务
}>({
    name: `混剪自动发布任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    accounts: [],
    publish_frep: 2,
    video_ids: [],
    time_config: [
        {
            start_time: "09:00",
            end_time: "09:30",
        },
        {
            start_time: "09:30",
            end_time: "10:30",
        },
    ],
    media_type: 1,
    data_type: 0,
    task_type: 2,
    scene: 1,
});

const timeInterval = 30;
const taskErrorMsg = ref<string>("");
// 时间错误
const timeErrors = ref<any>({});

const showCreate = ref(false);

// 判断是否可以创建任务
const canCreateTask = computed(() => {
    return formData.accounts.length > 0;
});

const handleFrequency = (item: number) => {
    if (item == formData.publish_frep) return;
    formData.publish_frep = item;
    // 这里每次更改频率，都要重新生成时间
    formData.time_config = Array.from({ length: item }, (_, index) => {
        const baseTime = new Date();
        // 要使用当前早上九点的时分秒
        baseTime.setHours(9, 0, 0);
        const startTime = new Date(baseTime.getTime() + index * timeInterval * 60 * 1000);
        const endTime = new Date(startTime.getTime() + timeInterval * 60 * 1000);
        return {
            start_time: uni.$u.timeFormat(startTime, "hh:MM"),
            end_time: uni.$u.timeFormat(endTime, "hh:MM"),
        };
    });
    timeErrors.value = {};
};

const handleEndTimeClick = (index: number) => {
    const data = formData.time_config[index];
    if (!data.start_time) {
        uni.$u.toast("请先选择开始时间");
        return;
    }
};

const handleStartTimeChange = (e: any, index: number) => {
    const { value } = e.detail;
    const data = formData.time_config[index];
    // 判断时间不能小于当前时间
    const endTime = new Date(`2000/01/01 ${value}`);

    data.start_time = value;
    endTime.setMinutes(endTime.getMinutes() + 30);
    data.end_time = uni.$u.timeFormat(endTime, "hh:MM");
    const { errors } = validateSchedule(formData.time_config);

    timeErrors.value = errors;
};

const handleEndTimeChange = (e: any, index: number) => {
    const { value } = e.detail;
    const data = formData.time_config[index];
    // 这里需要判断结束时间是否大于开始时间，并且要大于开始
    if (value <= data.start_time) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const startTIme = new Date(`2000/01/01 ${data.start_time}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() - startTIme.getTime() < 30 * 60 * 1000) {
        uni.$u.toast(`结束时间不能小于开始时间${timeInterval}分钟`);
        return;
    }
    data.end_time = value;
    const { errors } = validateSchedule(formData.time_config);
    timeErrors.value = errors;
};

function validateSchedule(list: any[]) {
    const toMin = (t: any) => {
        if (!t) return NaN;
        const [h, m] = (t || "").split(":").map(Number);
        return h * 60 + m;
    };

    const schedule = list.map((item) => ({
        start_time: item.start_time,
        end_time: item.end_time,
        s: toMin(item.start_time),
        e: toMin(item.end_time),
    }));

    const errors = schedule.reduce((acc, cur, i, arr) => {
        const addError = (index: number, field: "start_time" | "end_time") => {
            if (!acc[index]) acc[index] = { start_time: false, end_time: false };
            acc[index][field] = true;
        };

        if (cur.start_time == null || cur.start_time === "") {
            addError(i, "start_time");
        }
        if (cur.end_time == null || cur.end_time === "") {
            addError(i, "end_time");
        }

        if (isNaN(cur.s) || isNaN(cur.e)) {
            return acc;
        }

        if (cur.s >= cur.e) {
            addError(i, "start_time");
            addError(i, "end_time");
        }

        if (i > 0) {
            const prev = arr[i - 1];
            if (!isNaN(prev.e) && cur.s < prev.e) {
                addError(i - 1, "end_time");
                addError(i, "start_time");
            }
        }

        return acc;
    }, {} as { [key: number]: { start_time: boolean; end_time: boolean } });

    const isValid = Object.keys(errors).length === 0;
    return { valid: isValid, errors };
}

const createTask = async () => {
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    } else if (!canCreateTask.value) {
        uni.$u.toast("请选择发布账号");
        return;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createShanjianPublish({
            ...formData,
            time_config: formData.time_config.map((item) => `${item.start_time}-${item.end_time}`),
        });
        showCreate.value = true;
        uni.hideLoading();
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleBackHome = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/index/index",
        type: "reLaunch",
    });
};

onLoad((options: any) => {
    formData.video_ids = isJson(options.task_id) ? JSON.parse(options.task_id) : options.task_id;
    formData.scene = options.scene;
    uni.$on("confirm", (result: any) => {
        const { type, data } = result;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length == 0) return;
            formData.accounts = data.map((item: any) => ({ account: item.account, type: item.type, id: item.id }));
        }
    });
});

onUnload(() => {
    uni.$off("confirm");
});
</script>

<style scoped lang="scss">
.prompt-length-item {
    @apply flex items-center justify-center bg-[#F7F8FC] w-[114rpx] h-[72rpx] text-[#7C7E80] relative rounded-[16rpx];
    &.active {
        @apply font-bold text-black;
        &::before {
            @apply absolute top-[-4rpx] left-[-4rpx] w-[100%] h-[100%]  p-[4rpx] rounded-[16rpx] content-[''];
            background: conic-gradient(#47d59f, #37cced);
            -webkit-mask: linear-gradient(#47d59f 0 100%) content-box, linear-gradient(#37cced 0 100%);
            -webkit-mask-composite: xor;
        }
    }
}
</style>
