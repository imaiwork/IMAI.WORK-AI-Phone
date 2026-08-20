<template>
    <popup-bottom
        v-model="show"
        title="执行记录详情"
        custom-class="bg-[#F6F6F6]"
        :show-footer="false"
        height="85%"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 pb-5">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-[26rpx] pt-3 flex flex-col gap-y-3">
                            <view class="bg-white rounded-[20rpx] p-5">
                                <view class="flex items-center justify-between mb-3">
                                    <view class="flex items-center gap-x-2 flex-1 min-w-0">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full flex-shrink-0 flex items-center justify-center text-white font-semibold text-[28rpx]"
                                            :class="getAvatarBg(detailData.status)">
                                            {{ detailData.account?.charAt(0) || "?" }}
                                        </view>
                                        <view class="flex-1 min-w-0">
                                            <text class="font-semibold text-[30rpx] text-[#000000e6] truncate block">
                                                {{ detailData.account || "-" }}
                                            </text>
                                            <text class="text-[#0000004d] text-[22rpx]">{{
                                                detailData.channel_name || "-"
                                            }}</text>
                                        </view>
                                    </view>
                                    <view class="flex flex-col items-end gap-y-1 flex-shrink-0 ml-3">
                                        <view
                                            class="px-[14rpx] py-[6rpx] rounded-[12rpx] text-[22rpx] font-medium"
                                            :class="getStatusStyle(detailData.status)">
                                            {{ getStatusText(detailData.status) }}
                                        </view>
                                        <view
                                            v-if="detailData.intention_type > 0"
                                            class="px-[14rpx] py-[6rpx] rounded-[12rpx] text-[22rpx] font-medium"
                                            :class="getFollowStatusStyle(detailData.intention_type)">
                                            {{ getFollowStatusText(detailData.intention_type) }}
                                        </view>
                                    </view>
                                </view>

                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>

                                <view class="flex flex-col gap-y-[20rpx]">
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">执行账号</text>
                                        <text class="text-[#000000cc]">{{ detailData.user_account || "-" }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">提取微信号</text>
                                        <view class="px-[14rpx] py-[4rpx] bg-[rgba(0,101,251,0.06)] rounded-[10rpx]">
                                            <text class="text-primary text-xs font-medium">{{
                                                detailData.reg_wechat || "-"
                                            }}</text>
                                        </view>
                                    </view>
                                    <!-- 意向状态（行内展示，intention_type <= 0 时也展示占位） -->
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">意向状态</text>
                                        <view
                                            class="flex items-center gap-x-2 px-[14rpx] py-[6rpx] rounded-[12rpx] text-[22rpx] font-medium"
                                            :class="getFollowStatusStyle(detailData.intention_type)">
                                            <view
                                                class="w-[12rpx] h-[12rpx] rounded-full flex-shrink-0"
                                                :class="getFollowDotColor(detailData.intention_type)"></view>
                                            <text>{{ getFollowStatusText(detailData.intention_type) }}</text>
                                        </view>
                                    </view>
                                    <view class="flex items-start">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0 leading-relaxed"
                                            >执行结果</text
                                        >
                                        <text
                                            class="flex-1 leading-relaxed"
                                            :class="getResultTextColor(detailData.status)">
                                            {{ detailData.result || "-" }}
                                        </text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">创建时间</text>
                                        <text class="text-[#000000cc]">{{ detailData.create_time || "-" }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">更新时间</text>
                                        <text class="text-[#000000cc]">{{ detailData.update_time || "-" }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5" v-if="detailData.wechat_name">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#07C160] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">微信账号</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view class="bg-[#F3F3F3] rounded-[12rpx] px-4 py-[20rpx] flex items-center gap-x-3">
                                    <view
                                        class="w-[80rpx] h-[80rpx] rounded-full bg-[#07C160] flex items-center justify-center flex-shrink-0">
                                        <text class="text-white text-[30rpx] font-semibold">
                                            {{ detailData.wechat_name?.charAt(0) || "?" }}
                                        </text>
                                    </view>
                                    <view class="flex-1 min-w-0">
                                        <view class="text-[#000000cc] text-[28rpx] font-medium">{{
                                            detailData.wechat_name
                                        }}</view>
                                        <view class="text-[#0000004d] text-[22rpx] mt-[4rpx] truncate">{{
                                            detailData.wechat_no || "-"
                                        }}</view>
                                    </view>
                                    <view
                                        v-if="detailData.intention_type > 0"
                                        class="flex-shrink-0 px-[14rpx] py-[6rpx] rounded-[12rpx] text-[22rpx] font-medium"
                                        :class="getFollowStatusStyle(detailData.intention_type)">
                                        {{ getFollowStatusText(detailData.intention_type) }}
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5" v-if="detailData.original_message">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#FF9500] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">原始内容</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view class="bg-[#FAFAFA] rounded-[12rpx] p-4">
                                    <text class="text-[#000000b3] leading-relaxed">{{
                                        detailData.original_message
                                    }}</text>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#9B59B6] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">任务信息</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view class="flex flex-col gap-y-[20rpx]">
                                    <view class="flex items-start">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0 leading-relaxed"
                                            >任务名称</text
                                        >
                                        <text class="text-[#000000cc] flex-1 leading-relaxed">{{
                                            detailData.task_name || "-"
                                        }}</text>
                                    </view>
                                    <view class="flex items-start">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0 leading-relaxed"
                                            >执行说明</text
                                        >
                                        <text class="text-[#000000cc] flex-1 leading-relaxed">{{
                                            detailData.task_detail_described || "-"
                                        }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#0000001a] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">设备信息</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view class="flex flex-col gap-y-[20rpx]">
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">设备名称</text>
                                        <text class="text-[#000000cc]">{{ detailData.device_name || "-" }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">设备型号</text>
                                        <text class="text-[#000000cc]">{{ detailData.device_model || "-" }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] w-[160rpx] flex-shrink-0">设备编码</text>
                                        <text class="text-[#00000066] text-xs font-mono">{{
                                            detailData.device_code || "-"
                                        }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5" v-if="detailData.image">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#9B59B6] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">执行截图</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <image
                                    :src="detailData.image"
                                    class="w-full rounded-[12rpx]"
                                    style="height: 320rpx"
                                    mode="aspectFill"
                                    @click="handlePreviewImage(detailData.image)"></image>
                            </view>

                            <view class="h-[20rpx]"></view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
    detailData: any;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

// ── 执行状态 ──────────────────────────────
const getStatusText = (status: number) => {
    switch (status) {
        case 0:
            return "无效/失败";
        case 1:
            return "添加成功";
        case 2:
            return "执行中";
        default:
            return "-";
    }
};

const getStatusStyle = (status: number) => {
    switch (status) {
        case 0:
            return "bg-[rgba(255,36,36,0.1)] text-[#FF2442]";
        case 1:
            return "bg-[rgba(0,192,142,0.1)] text-[#00C08E]";
        case 2:
            return "bg-[rgba(0,101,251,0.04)] text-primary";
        default:
            return "bg-[rgba(0,0,0,0.04)] text-[#00000066]";
    }
};

const getResultTextColor = (status: number) => {
    switch (status) {
        case 0:
            return "text-[#FF2442]";
        case 1:
            return "text-[#00C08E]";
        case 2:
            return "text-primary";
        default:
            return "text-[#0000004d]";
    }
};

const getAvatarBg = (status: number) => {
    switch (status) {
        case 0:
            return "bg-[#FF2442]";
        case 1:
            return "bg-[#00C08E]";
        case 2:
            return "bg-primary";
        default:
            return "bg-[#0000001a]";
    }
};

// ── 意向状态 ──────────────────────────────
const getFollowStatusText = (status: number) => {
    switch (status) {
        case 1:
            return "成交意愿";
        case 2:
            return "询价意愿";
        case 3:
            return "想要加微信";
        case 4:
            return "一般意愿";
        case 5:
            return "明确拒绝";
        default:
            return "待处理";
    }
};

const getFollowStatusStyle = (status: number) => {
    switch (status) {
        case 1:
            return "bg-[rgba(0,192,142,0.1)] text-[#00C08E]"; // 成交 - 绿
        case 2:
            return "bg-[rgba(0,101,251,0.08)] text-primary"; // 询价 - 蓝
        case 3:
            return "bg-[rgba(255,149,0,0.1)] text-[#FF9500]"; // 加微信 - 橙
        case 4:
            return "bg-[rgba(0,0,0,0.04)] text-[#00000066]"; // 一般 - 灰
        case 5:
            return "bg-[rgba(255,36,36,0.1)] text-[#FF2442]"; // 拒绝 - 红
        default:
            return "bg-[rgba(0,0,0,0.04)] text-[#00000066]";
    }
};

const getFollowDotColor = (status: number) => {
    switch (status) {
        case 1:
            return "bg-[#00C08E]";
        case 2:
            return "bg-primary";
        case 3:
            return "bg-[#FF9500]";
        case 4:
            return "bg-[#00000033]";
        case 5:
            return "bg-[#FF2442]";
        default:
            return "bg-[#00000033]";
    }
};

// ── 其他 ──────────────────────────────────
const handlePreviewImage = (url: string) => {
    uni.previewImage({ urls: [url], current: 0 });
};

const close = (): void => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
