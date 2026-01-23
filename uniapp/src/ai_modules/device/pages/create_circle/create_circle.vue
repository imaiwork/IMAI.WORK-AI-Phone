<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            title="朋友圈互动"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex justify-end px-4 mt-4" v-if="circleList.length > 0">
            <view class="bg-black rounded-full px-[20rpx] py-[10rpx]" @click="handleSetup()">
                <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                <text class="text-sm text-white font-bold ml-1">新增内容</text>
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y v-if="circleList.length > 0">
                <view class="p-4 pb-[100rpx]">
                    <view
                        v-for="(item, index) in circleList"
                        :key="index"
                        class="bg-white rounded-[16rpx] p-4 mb-4 shadow-[0_4rpx_12rpx_rgba(0,0,0,0.05)] transition-all duration-300"
                        :class="[
                            item.error ? 'border border-[#ff4d4f] shadow-[0_4rpx_12rpx_rgba(255,77,79,0.15)]' : '',
                        ]">
                        <view
                            v-if="item.error"
                            class="flex items-start bg-[#fff2f0] -mx-4 -mt-4 mb-3 px-4 py-2 border-b border-[#ffccc7]">
                            <u-icon name="info-circle-fill" size="28" color="#ff4d4f" style="margin-top: 4rpx"></u-icon>

                            <view class="flex-1 ml-2 mr-2">
                                <text class="text-xs text-[#ff4d4f] font-bold block leading-normal">
                                    {{ item.error }}
                                </text>
                            </view>

                            <view
                                class="text-xs text-[#ff4d4f] underline whitespace-nowrap mt-[2rpx]"
                                @click="handleSetup(index)">
                                去修改
                            </view>
                        </view>

                        <view class="flex justify-between items-center mb-2">
                            <text class="text-lg font-bold text-gray-800">{{ item.title || item.name }}</text>
                            <view class="flex items-center" @click="handleSetup(index)">
                                <text class="text-sm font-medium" style="color: #0065fb">设置</text>
                                <u-icon name="arrow-right" size="24" color="#0065fb"></u-icon>
                            </view>
                        </view>

                        <view class="text-gray-600 mb-3 text-md leading-relaxed">
                            {{ item.content }}
                        </view>

                        <view class="grid grid-cols-4 gap-2">
                            <view
                                v-for="(material, idx) in item.attachment_content"
                                :key="idx"
                                class="w-[156rpx] h-[156rpx]">
                                <video
                                    v-if="(material.type === 2 || !material.pic) && typeof material !== 'string'"
                                    :src="material.url || material"
                                    class="w-full h-full rounded-[12rpx]"
                                    :autoplay="false"
                                    :show-loading="false"
                                    :controls="false"
                                    :show-fullscreen-btn="false"
                                    :show-center-play-btn="false"
                                    :show-play-btn="false"
                                    mode="aspectFill"></video>
                                <image
                                    v-else
                                    :src="material.pic || material"
                                    class="w-full h-full rounded-[12rpx]"
                                    mode="aspectFill" />
                            </view>
                        </view>

                        <view class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
                            <view class="flex items-center gap-4">
                                <view class="flex items-center gap-1">
                                    <image src="/static/images/icons/weixin.svg" class="w-[24rpx] h-[24rpx]"></image>
                                    <text class="text-xs text-gray-500">{{ item.wechat_ids.length }}个账号</text>
                                </view>
                                <view class="flex items-center gap-1">
                                    <u-icon name="clock-fill" size="26" color="#28C445"></u-icon>
                                    <text class="text-[22rpx] text-[#000000]/70"> 发布：{{ item.date }} </text>
                                </view>
                            </view>

                            <view
                                class="flex items-center gap-1 opacity-40 hover:opacity-100"
                                @tap="handleDelete(index)">
                                <image src="/static/images/icons/delete.svg" class="w-[24rpx] h-[24rpx]"></image>
                                <text class="text-sm text-[#000000]/30">删除</text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <view class="flex flex-col items-center justify-center h-full" v-else>
                <empty :size="200" text="暂无朋友圈发布内容" />
                <view class="bg-black rounded-full px-[20rpx] py-[10rpx] mt-[40rpx]" @click="handleSetup()">
                    <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                    <text class="text-sm text-white font-bold ml-1">新增内容</text>
                </view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 p-4">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-primary text-white font-bold flex items-center justify-center"
                @click="handleCreateTask">
                创建任务
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
        // 清除旧错误
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
            index: index,
            originalItem: item,
            start: parseTimeMinutes(startStr),
            end: parseTimeMinutes(endStr),
            wechatIds: item.wechat_ids.map((id: any) => (typeof id === "object" ? id.account : id)),
            date: item.date,
        };
    });

    let hasConflict = false;

    for (let i = 0; i < compareList.length; i++) {
        for (let j = i + 1; j < compareList.length; j++) {
            const taskA = compareList[i];
            const taskB = compareList[j];

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
                typeof wechatId === "object" ? wechatId.account : wechatId
            );

            return await createCircleTask({
                name: item.name || item.title,
                content: item.content,
                attachment_type: item.attachment_type || 1,
                attachment_content: item.attachment_content.map((att: any) => att.url || att),
                wechat_ids: wechatIdList,
                date: item.date,
                time_config: timeConfigStr, // 提交字符串格式
            });
        });

        const results = await Promise.allSettled(promises);

        const failedTasks: any[] = [];

        results.forEach((result, index) => {
            if (result.status === "rejected") {
                const failedItem = circleList.value[index];
                failedItem.error = result.reason?.message || "创建失败";
                failedTasks.push(failedItem);
            }
        });

        circleList.value = failedTasks;

        uni.hideLoading();

        if (circleList.value.length === 0) {
            showCreateTaskSuccessDialog.value = true;
        } else {
            uni.showToast({
                title: `部分任务创建失败，请检查`,
                icon: "none",
                duration: 3000,
            });
        }
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
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

<style scoped lang="scss"></style>
