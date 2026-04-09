<template>
    <popup-bottom
        v-model="show"
        title="线索详情"
        custom-class="bg-[#F6F6F6]"
        :show-footer="false"
        height="85%"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 pb-5">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-[26rpx] pt-3 flex flex-col gap-y-3 pb-[200rpx]">
                            <view class="bg-white rounded-[20rpx] p-5">
                                <view class="flex items-center justify-between mb-3">
                                    <view class="flex items-center gap-x-2 flex-1 min-w-0">
                                        <view class="w-[8rpx] h-[32rpx] bg-primary rounded-full flex-shrink-0"></view>
                                        <text class="font-semibold text-[30rpx] text-[#000000e6] truncate">
                                            {{ detailData.username || "-" }}
                                        </text>
                                    </view>
                                    <view class="flex items-center gap-x-2 flex-shrink-0 ml-3">
                                        <view
                                            class="px-[12rpx] py-[6rpx] rounded-[12rpx] text-[22rpx] font-medium"
                                            :class="
                                                detailData.is_verify === 1
                                                    ? 'bg-[rgba(0,192,142,0.1)] text-[#00C08E]'
                                                    : 'bg-[rgba(0,0,0,0.04)] text-[#00000066]'
                                            ">
                                            {{ detailData.is_verify === 1 ? "已核实" : "未核实" }}
                                        </view>
                                    </view>
                                </view>

                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>

                                <view class="flex flex-col gap-y-[20rpx]">
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >线索类型</text
                                        >
                                        <view class="px-[14rpx] py-[4rpx] bg-[rgba(0,101,251,0.06)] rounded-[10rpx]">
                                            <text class="text-primary text-[24rpx] font-medium">{{
                                                detailData.clue_type_name || "-"
                                            }}</text>
                                        </view>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >所在地区</text
                                        >
                                        <text class="text-[#000000cc] text-[26rpx]">{{
                                            detailData.address || "-"
                                        }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >匹配关键词</text
                                        >
                                        <view class="px-[14rpx] py-[4rpx] bg-[#00000005] rounded-[10rpx]">
                                            <text class="text-[#000000cc] text-[24rpx]">{{
                                                detailData.exec_keyword || "-"
                                            }}</text>
                                        </view>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >消耗积分</text
                                        >
                                        <view class="flex items-center gap-x-1">
                                            <text class="text-[#FF9500] font-semibold text-[28rpx]">{{
                                                detailData.tokens ?? "-"
                                            }}</text>
                                            <text class="text-[#0000004d] text-[24rpx]">积分</text>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5" v-if="detailData.reg_content">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#00C08E] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">联系方式</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view class="flex flex-wrap gap-2">
                                    <view
                                        v-for="(item, index) in contactList"
                                        :key="index"
                                        class="flex items-center gap-x-2 bg-[#F3F3F3] rounded-[12rpx] px-[20rpx] py-[14rpx]">
                                        <view
                                            class="w-[12rpx] h-[12rpx] rounded-full bg-[#00C08E] flex-shrink-0"></view>
                                        <text class="text-[#000000cc] text-[26rpx] font-medium">{{ item }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5" v-if="detailData.crawl_content">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#FF9500] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">原始内容</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view class="bg-[#FAFAFA] rounded-[12rpx] p-4">
                                    <text class="text-[#000000b3] text-[26rpx] leading-relaxed">{{
                                        detailData.crawl_content
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
                                        <text
                                            class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0 leading-relaxed"
                                            >任务名称</text
                                        >
                                        <text class="text-[#000000cc] text-[26rpx] flex-1 leading-relaxed">{{
                                            detailData.task_name || "-"
                                        }}</text>
                                    </view>
                                    <view class="flex items-start">
                                        <text
                                            class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0 leading-relaxed"
                                            >录入说明</text
                                        >
                                        <text class="text-[#000000cc] text-[26rpx] flex-1 leading-relaxed">{{
                                            detailData.task_detail_described || "-"
                                        }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >执行时间</text
                                        >
                                        <text class="text-[#000000cc] text-[26rpx]">{{
                                            detailData.exec_time || "-"
                                        }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="bg-white rounded-[20rpx] p-5">
                                <view class="flex items-center gap-x-2 mb-3">
                                    <view class="w-[8rpx] h-[32rpx] bg-[#0000001a] rounded-full"></view>
                                    <text class="font-semibold text-[28rpx] text-[#000000e6]">执行信息</text>
                                </view>
                                <view class="h-[2rpx] bg-[#00000005] mb-3"></view>
                                <view
                                    class="bg-[#F3F3F3] rounded-[12rpx] px-4 py-[20rpx] flex items-center gap-x-3 mb-[20rpx]">
                                    <view
                                        class="w-[72rpx] h-[72rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                                        <text class="text-white text-[28rpx] font-semibold">
                                            {{ detailData.exec_account_name?.charAt(0) || "?" }}
                                        </text>
                                    </view>
                                    <view class="flex-1 min-w-0">
                                        <view class="text-[#000000cc] text-[26rpx] font-medium truncate">
                                            {{ detailData.exec_account_name || "-" }}
                                        </view>
                                        <view class="text-[#0000004d] text-[22rpx] mt-[4rpx] truncate">
                                            {{ detailData.exec_account || "-" }}
                                        </view>
                                    </view>
                                </view>
                                <view class="flex flex-col gap-y-[20rpx]">
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >设备名称</text
                                        >
                                        <text class="text-[#000000cc] text-[26rpx]">{{
                                            detailData.device_name || "-"
                                        }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >设备型号</text
                                        >
                                        <text class="text-[#000000cc] text-[26rpx]">{{
                                            detailData.device_model || "-"
                                        }}</text>
                                    </view>
                                    <view class="flex items-center">
                                        <text class="text-[#0000004d] text-[26rpx] w-[160rpx] flex-shrink-0"
                                            >设备编码</text
                                        >
                                        <text class="text-[#00000066] text-[24rpx] font-mono">{{
                                            detailData.device_code || "-"
                                        }}</text>
                                    </view>
                                </view>
                            </view>
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

const contactList = computed(() => {
    const raw = props.detailData?.reg_content;
    if (!raw) return [];
    return raw
        .split(",")
        .map((s: string) => s.trim())
        .filter(Boolean);
});

const close = (): void => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
