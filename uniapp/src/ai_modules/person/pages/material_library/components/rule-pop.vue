<template>
    <popup-bottom
        v-model="show"
        title="规则说明"
        :show-cancel-button="false"
        custom-class="bg-[#F7F9FC]"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 pt-5">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-4 pb-2">
                            <view class="flex flex-row items-start gap-3 mb-6">
                                <view
                                    class="w-10 h-10 rounded-xl bg-[#eff6ff] flex items-center justify-center shrink-0">
                                    <u-icon name="grid" size="40" color="#4a8cff" />
                                </view>
                                <view class="flex flex-col flex-1 gap-1">
                                    <text class="text-base font-bold text-[#171717]">素材调用范围</text>
                                    <text class="text-sm text-[#9b9b9b] leading-relaxed">
                                        系统执行视频发布任务时，仅从当前IP绑定的
                                        <text class="font-bold text-gray-900">"人设素材库"</text>
                                        中提取素材（包含视频和图片）。
                                    </text>
                                </view>
                            </view>

                            <view class="flex flex-row items-start gap-3 mb-6">
                                <view
                                    class="w-10 h-10 rounded-xl bg-[#f0fdf4] flex items-center justify-center shrink-0">
                                    <u-icon name="checkmark" size="40" color="#2dc87a" />
                                </view>
                                <view class="flex flex-col flex-1 gap-1">
                                    <text class="text-base font-bold text-[#171717]">素材选择机制</text>
                                    <view
                                        class="bg-[#FAFBFC] rounded-2xl p-4 mt-1 flex flex-col gap-4 border border-solid border-[#E5E7EB]">
                                        <view class="flex flex-row items-start gap-2">
                                            <view class="w-5 h-5 shrink-0 mt-0.5">
                                                <u-icon name="checkmark-circle" size="28" color="#2dc87a" />
                                            </view>
                                            <text class="text-sm text-[#676767] leading-relaxed flex-1">
                                                <text class="font-bold text-[#171717]">优先启用状态：</text>
                                                仅选择处于"启用"状态的素材，停用或删除的素材不参与。
                                            </text>
                                        </view>

                                        <view class="flex flex-row items-start gap-2">
                                            <view class="w-5 h-5 shrink-0 mt-0.5">
                                                <u-icon name="checkmark-circle" size="28" color="#2dc87a" />
                                            </view>
                                            <text class="text-sm text-[#16a34a] leading-relaxed flex-1">
                                                <text class="font-bold text-[#14532d]">随机抽取原则：</text>
                                                在所有可用素材中随机抽取，保证发布内容多样性。
                                            </text>
                                        </view>

                                        <view class="flex flex-row items-start gap-2">
                                            <view class="w-5 h-5 shrink-0 mt-0.5">
                                                <u-icon name="warning-fill" size="28" color="#f5a623" />
                                            </view>
                                            <text class="text-sm text-[#676767] leading-relaxed flex-1">
                                                <text class="font-bold text-[#171717]">智能防重复：</text>
                                                自动避免连续重复使用同一素材（遵循 24H 1.0 防重规则）。
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view class="flex flex-row items-start gap-3 mb-6">
                                <view
                                    class="w-10 h-10 rounded-xl bg-[#faf5ff] flex items-center justify-center shrink-0">
                                    <u-icon name="play-circle" size="40" color="#9b6dff" />
                                </view>
                                <view class="flex flex-col flex-1 gap-1">
                                    <text class="text-base font-bold text-[#171717]">视频生成方式</text>
                                    <view class="flex flex-col gap-2 mt-1">
                                        <view class="flex flex-row flex-wrap">
                                            <text class="text-sm font-bold text-[#171717]">启用数字人：</text>
                                            <text class="text-sm text-[#9b9b9b] leading-relaxed"
                                                >结合选中的素材与数字人生成视频。</text
                                            >
                                        </view>
                                        <view class="flex flex-row flex-wrap">
                                            <text class="text-sm font-bold text-[#171717]">未启用数字人：</text>
                                            <text class="text-sm text-[#9b9b9b] leading-relaxed">
                                                采用 <text class="text-[#3b82f6] font-medium">素材混剪</text> 或
                                                <text class="text-[#4a8cff] font-medium">新闻体</text>
                                                形式自动生成并替代视频内容。
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="px-4 pb-5">
                    <u-button
                        type="primary"
                        shape="circle"
                        ripple
                        :custom-style="{ fontSize: '28rpx', fontWeight: 'bold', height: '88rpx' }"
                        @click="show = false">
                        我知道了
                    </u-button>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
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

const close = (): void => {
    show.value = false;
    emit("close");
};
</script>
