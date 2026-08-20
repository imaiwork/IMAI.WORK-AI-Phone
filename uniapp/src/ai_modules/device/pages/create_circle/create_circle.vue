<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="朋友圈发布"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }" />

        <view v-if="circleList.length > 0" class="flex items-center justify-between px-4 pt-[16rpx] pb-[8rpx]">
            <view class="flex items-center gap-[10rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">发布内容</text>
                <text class="text-xs text-[#9CA3AF]">（{{ circleList.length }}）</text>
            </view>
            <view
                class="flex items-center gap-[8rpx] bg-[#EBF2FF] px-[24rpx] py-[12rpx] rounded-full"
                @click="handleSetup()">
                <u-icon name="plus" color="#0065fb" size="18" />
                <text class="font-bold text-primary">新增内容</text>
            </view>
        </view>

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y v-if="circleList.length > 0">
                <view class="px-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                    <view
                        v-for="(item, index) in circleList"
                        :key="index"
                        class="bg-white rounded-[24rpx] overflow-hidden transition-all duration-300"
                        :class="
                            item.error
                                ? 'shadow-[0_2rpx_12rpx_rgba(239,68,68,0.15),0_0_0_1.5rpx_#FCA5A5]'
                                : 'shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]'
                        ">
                        <view
                            v-if="item.error"
                            class="flex items-start gap-[10rpx] bg-[#FEF2F2] px-[24rpx] py-[16rpx] border-[0] border-b border-solid border-[#FECACA]">
                            <u-icon name="info-circle-fill" size="26" color="#EF4444" />
                            <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ item.error }}</text>
                            <view
                                class="flex-shrink-0 px-[16rpx] py-[6rpx] rounded-full bg-[#EF4444]"
                                @click="handleSetup(index)">
                                <text class="text-[22rpx] text-white font-bold">去修改</text>
                            </view>
                        </view>

                        <view class="flex">
                            <view class="w-[6rpx] flex-shrink-0" :class="item.error ? 'bg-[#EF4444]' : 'bg-primary'" />
                            <view class="flex-1 px-[24rpx] pt-[20rpx] pb-[18rpx]">
                                <view class="flex items-center justify-between mb-[14rpx]">
                                    <view class="flex items-center gap-[10rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="item.error ? 'bg-[#FEF2F2]' : 'bg-[#EBF2FF]'">
                                            <text
                                                class="text-[22rpx] font-bold"
                                                :class="item.error ? 'text-[#EF4444]' : 'text-primary'">
                                                {{ index + 1 }}
                                            </text>
                                        </view>
                                        <text class="text-[30rpx] font-bold text-[#0D1117]">
                                            {{ item.title || item.name }}
                                        </text>
                                    </view>
                                    <view
                                        class="flex items-center gap-[4rpx] bg-[#EBF2FF] px-[16rpx] py-[8rpx] rounded-full"
                                        @click="handleSetup(index)">
                                        <text class="text-xs font-bold text-primary">设置</text>
                                        <u-icon name="arrow-right" size="20" color="#0065fb" />
                                    </view>
                                </view>

                                <text class="text-[#4B5563] leading-relaxed block mb-[16rpx]">
                                    {{ item.content }}
                                </text>

                                <view
                                    v-if="item.attachment_content && item.attachment_content.length > 0"
                                    class="grid grid-cols-4 gap-[10rpx] mb-[16rpx]">
                                    <view
                                        v-for="(material, idx) in item.attachment_content"
                                        :key="idx"
                                        class="aspect-square rounded-[12rpx] overflow-hidden">
                                        <video
                                            v-if="
                                                (material.type === 2 || !material.pic) && typeof material !== 'string'
                                            "
                                            :src="material.url || material"
                                            class="w-full h-full"
                                            :autoplay="false"
                                            :show-loading="false"
                                            :controls="false"
                                            :show-fullscreen-btn="false"
                                            :show-center-play-btn="false"
                                            :show-play-btn="false"
                                            mode="aspectFill" />
                                        <image
                                            v-else
                                            :src="material.pic || material"
                                            class="w-full h-full"
                                            mode="aspectFill" />
                                    </view>
                                </view>

                                <view
                                    class="pt-[16rpx] border-[0] border-t border-solid border-[#F0F2F5] flex items-center justify-between">
                                    <view class="flex items-center gap-[16rpx]">
                                        <view
                                            class="flex items-center gap-[6rpx] bg-[#F0F2F5] px-[14rpx] py-[6rpx] rounded-full">
                                            <image src="/static/images/icons/weixin.svg" class="w-[24rpx] h-[24rpx]" />
                                            <text class="text-[22rpx] text-[#4B5563] font-semibold">
                                                {{ item.wechat_ids.length }} 个账号
                                            </text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx]">
                                            <u-icon name="clock-fill" size="24" color="#16A34A" />
                                            <text class="text-[22rpx] text-[#4B5563]">{{ item.date }}</text>
                                        </view>
                                    </view>
                                    <view
                                        class="flex items-center gap-[6rpx] px-[16rpx] py-[8rpx] rounded-full bg-[#FEF2F2]"
                                        @tap="handleDelete(index)">
                                        <u-icon name="trash" color="#EF4444" size="18" />
                                        <text class="text-[22rpx] text-[#EF4444] font-semibold">删除</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <view v-else class="flex flex-col items-center justify-center h-full px-8">
                <view class="relative mb-[48rpx]">
                    <view class="w-[280rpx] h-[280rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                        <view class="w-[200rpx] h-[200rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-[32rpx] flex items-center justify-center shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <image
                                    src="/static/images/icons/weixin.svg"
                                    class="w-[64rpx] h-[64rpx]"
                                    style="filter: brightness(10)" />
                            </view>
                        </view>
                    </view>
                    <view
                        class="absolute top-[10rpx] right-[-10rpx] w-[56rpx] h-[56rpx] rounded-full bg-white shadow-[0_4rpx_12rpx_rgba(0,101,251,0.15)] flex items-center justify-center">
                        <u-icon name="plus" color="#0065fb" size="22" />
                    </view>
                    <view
                        class="absolute bottom-[10rpx] left-[-10rpx] w-[44rpx] h-[44rpx] rounded-full bg-[#FEF9C3] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.08)] flex items-center justify-center">
                        <u-icon name="clock-fill" color="#D97706" size="18" />
                    </view>
                </view>

                <text class="text-[34rpx] font-extrabold text-[#0D1117] mb-[16rpx]">还没有发布内容</text>
                <text class="text-[#9CA3AF] text-center leading-relaxed mb-[64rpx]">
                    点击下方按钮，添加您的第一条朋友圈发布内容
                </text>

                <view
                    class="flex items-center gap-[10rpx] h-[96rpx] px-[64rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleSetup()">
                    <u-icon name="plus" size="24" color="#fff" />
                    <text class="text-[30rpx] font-extrabold text-white">新增内容</text>
                </view>
            </view>
        </view>

        <view
            v-if="circleList.length > 0"
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[50rpx]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleCreateTask">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
            </view>
        </view>
    </view>

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
import WechatOA from "@/utils/wechat";
import { createCircleTask } from "@/api/device";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { on } = useEventBusManager();

const circleList = ref<any[]>([]);
const editIndex = ref<number>(-1);
const showCreateTaskSuccessDialog = ref(false);

const handleSetup = (index?: any) => {
    let params = {};
    if (index > -1) {
        params = circleList.value[index];
        editIndex.value = index;
    } else {
        editIndex.value = -1;
    }
    uni.$u.route({
        url: "/ai_modules/device/pages/setting_circle_interact/setting_circle_interact",
        params: {
            data: editIndex.value > -1 ? JSON.stringify(params) : "",
            index: editIndex.value,
            circleList: JSON.stringify(circleList.value),
        },
    });
};

const handleDelete = (index: number) => {
    uni.showModal({
        title: "提示",
        content: "确定删除该卡片吗？",
        success: (res) => {
            if (res.confirm) circleList.value.splice(index, 1);
        },
    });
};

const parseTimeMinutes = (timeStr: string) => {
    if (!timeStr) return 0;
    const [hours, minutes] = timeStr.split(":").map(Number);
    return hours * 60 + minutes;
};

const isTimeOverlap = (start1: number, end1: number, start2: number, end2: number) => {
    return start1 < end2 && start2 < end1;
};

const getTaskName = (item: any, index: number) => {
    return item.title || item.name || `内容${index + 1}`;
};

const getFormattedTimeStr = (timeConfig: any) => {
    if (Array.isArray(timeConfig)) {
        return `${timeConfig[0]}-${timeConfig[1]}`;
    }
    return timeConfig || "";
};

// 验证任务是否存在时间冲突
const validateTaskTimeConflict = () => {
    const compareList = circleList.value.map((item, index) => {
        delete item.error;

        let startStr = "",
            endStr = "";
        if (Array.isArray(item.time_config)) {
            startStr = item.time_config[0];
            endStr = item.time_config[1];
        } else if (typeof item.time_config === "string") {
            [startStr, endStr] = item.time_config.split("-");
        }

        return {
            index,
            originalItem: item,
            start: parseTimeMinutes(startStr),
            end: parseTimeMinutes(endStr),
            wechatIds: item.wechat_ids.map((id: any) => (typeof id === "object" ? id.account : id)),
            date: item.date,
            task_exec_type: item.task_exec_type,
        };
    });

    let hasConflict = false;

    for (let i = 0; i < compareList.length; i++) {
        for (let j = i + 1; j < compareList.length; j++) {
            const taskA = compareList[i];
            const taskB = compareList[j];

            if (taskA.task_exec_type === 1 || taskB.task_exec_type === 1) continue;

            if (taskA.date !== taskB.date) continue;

            const hasSharedAccount = taskA.wechatIds.some((id: string) => taskB.wechatIds.includes(id));
            if (!hasSharedAccount) continue;

            if (isTimeOverlap(taskA.start, taskA.end, taskB.start, taskB.end)) {
                hasConflict = true;
                const itemA = circleList.value[taskA.index];
                const itemB = circleList.value[taskB.index];
                const nameA = getTaskName(itemA, taskA.index);
                const nameB = getTaskName(itemB, taskB.index);
                if (!itemA.error) itemA.error = `${nameA}与${nameB}存在时间冲突`;
                if (!itemB.error) itemB.error = `${nameB}与${nameA}存在时间冲突`;
            }
        }
    }

    return hasConflict;
};

const handleCreateTask = async () => {
    if (circleList.value.length === 0) {
        uni.$u.toast("请先添加朋友圈发布内容");
        return;
    }

    uni.showLoading({ title: "检查任务冲突...", mask: true });

    const hasConflict = validateTaskTimeConflict();
    if (hasConflict) {
        uni.hideLoading();
        circleList.value = [...circleList.value];
        uni.showToast({ title: "存在时间冲突，请调整", icon: "none", duration: 3000 });
        return;
    }

    uni.showLoading({ title: `创建中(${circleList.value.length}个)...`, mask: true });

    try {
        const promises = circleList.value.map(async (item) => {
            const timeConfigStr = getFormattedTimeStr(item.time_config);

            const wechatIdList = item.wechat_ids.map((wechatId: any) =>
                typeof wechatId === "object" ? wechatId.account : wechatId,
            );

            return await createCircleTask({
                name: item.name || item.title,
                content: item.content,
                attachment_type: item.attachment_type || 1,
                attachment_content: item.attachment_content.map((att: any) => att.url || att),
                wechat_ids: wechatIdList,
                date: item.date,
                time_config: timeConfigStr,
                task_exec_type: item.task_exec_type,
                minutes: item.minutes,
                task_ids: item.task_ids,
            });
        });

        const results = await Promise.allSettled(promises);

        const failedTasks: any[] = [];

        results.forEach((result, index) => {
            if (result.status === "rejected") {
                const failedItem = circleList.value[index];
                failedItem.error = result.reason || "创建失败";

                failedTasks.push(failedItem);
                if (failedItem.error.indexOf("24小时自动执行任务") > -1) {
                    uni.showModal({
                        title: "提示",
                        content:
                            "您已开启24小时自动执行任务，无法创建手动任务，如您需手动创建任务，需先关闭24小时托管。",
                        success: (res) => {
                            if (res.confirm) {
                                uni.$u.route({
                                    url: "/ai_modules/device/pages/index/index",
                                });
                            }
                        },
                    });
                }
            }
        });

        circleList.value = failedTasks;

        uni.hideLoading();

        if (circleList.value.length === 0) {
            showCreateTaskSuccessDialog.value = true;
            WechatOA.notify();
        } else {
            uni.showToast({
                title: `部分任务创建失败，请检查`,
                icon: "none",
                duration: 3000,
            });
        }
    } catch (error: any) {
        uni.hideLoading();

        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/index/index",
        type: "reLaunch",
    });
    showCreateTaskSuccessDialog.value = false;
};

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CIRCLE_INTERACT) {
            if (data.length === 0) return;
            if (editIndex.value > -1) {
                circleList.value[editIndex.value] = {
                    ...circleList.value[editIndex.value],
                    ...data,
                };
            } else {
                circleList.value.push({
                    title: `发布内容${circleList.value.length + 1}`,
                    error: "",
                    ...data,
                });
            }
        }
    });
});
</script>
