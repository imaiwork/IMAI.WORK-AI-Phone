<template>
    <popup-bottom v-model="show" :clearable="false" height="auto" custom-class="bg-white">
        <template #content>
            <view class="pt-[12rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]">
                <!-- 头部 -->
                <view class="flex items-start gap-x-[20rpx] px-[36rpx] pb-[24rpx]">
                    <view class="w-[68rpx] h-[68rpx] rounded-[18rpx] bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                        <u-icon name="setting" color="#2563EB" :size="36" />
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="text-[32rpx] font-extrabold text-[#0F172A]">向量检索配置</text>
                        <text class="block text-[24rpx] text-[#94A3B8] mt-[4rpx]">
                            配置模型检索的精度、上限及重排策略
                        </text>
                    </view>
                </view>

                <!-- 基础检索 -->
                <view class="ms-sec">基础检索</view>
                <view class="ms-card">
                    <view class="py-[16rpx]">
                        <text class="ms-label">检索模式</text>
                        <view class="seg">
                            <view
                                v-for="item in SEARCH_MODES"
                                :key="item.value"
                                class="seg-btn"
                                :class="{ sel: form.search_mode === item.value }"
                                @click="form.search_mode = item.value">
                                {{ item.label }}
                            </view>
                        </view>
                    </view>
                    <view class="ms-row">
                        <view class="flex items-center justify-between">
                            <text class="ms-label">引用上限</text>
                            <text class="ms-val">{{ form.search_tokens }}</text>
                        </view>
                        <slider
                            :value="form.search_tokens"
                            :min="100"
                            :max="30000"
                            :step="100"
                            activeColor="#2563EB"
                            backgroundColor="#E5EAF3"
                            block-size="18"
                            @changing="form.search_tokens = $event.detail.value"
                            @change="form.search_tokens = $event.detail.value" />
                    </view>
                    <view v-if="form.search_mode === SearchMode.SIMILAR" class="ms-row">
                        <view class="flex items-center justify-between">
                            <text class="ms-label">最低相似度</text>
                            <text class="ms-val">{{ form.search_similar.toFixed(2) }}</text>
                        </view>
                        <slider
                            :value="form.search_similar"
                            :min="0"
                            :max="1"
                            :step="0.01"
                            activeColor="#2563EB"
                            backgroundColor="#E5EAF3"
                            block-size="18"
                            @changing="form.search_similar = $event.detail.value"
                            @change="form.search_similar = $event.detail.value" />
                    </view>
                </view>

                <!-- 结果重排 -->
                <view class="ms-sec">
                    结果重排<text class="text-[22rpx] font-medium text-[#94A3B8] ml-[8rpx]">(Rerank)</text>
                </view>
                <view class="ms-card">
                    <view class="flex items-center justify-between">
                        <view class="flex-1 min-w-0 pr-[24rpx]">
                            <text class="text-[26rpx] font-semibold text-[#1D2129]">语义重排</text>
                            <text class="block text-[22rpx] text-[#94A3B8] mt-[6rpx] leading-relaxed">
                                开启后对检索内容进行二次精密排序，建议混合检索时开启。
                            </text>
                        </view>
                        <u-switch v-model="form.ranking_status" :active-value="1" :inactive-value="0" size="44" />
                    </view>
                </view>

                <!-- 操作 -->
                <view class="flex gap-x-[20rpx] px-[32rpx] pt-[16rpx]">
                    <view class="action-btn action-btn--cancel" @click="emit('update:modelValue', false)">取消修改</view>
                    <view class="action-btn action-btn--save" @click="handleSave">保存配置</view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
enum SearchMode {
    SIMILAR = "similar",
    FULL = "full",
    MIX = "mix",
}
const SEARCH_MODES = [
    { label: "语义检索", value: SearchMode.SIMILAR },
    { label: "全文检索", value: SearchMode.FULL },
    { label: "混合检索", value: SearchMode.MIX },
];

const props = defineProps<{
    modelValue: boolean;
    params: Record<string, any>;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "confirm", v: Record<string, any>): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const form = reactive({
    search_mode: SearchMode.SIMILAR as string,
    search_tokens: 8000,
    search_similar: 0,
    ranking_status: 0,
    ranking_score: 0,
});

// 打开时以父层当前参数回填
watch(
    () => props.modelValue,
    (v) => {
        if (v) Object.assign(form, props.params);
    },
);

const handleSave = () => {
    const payload: Record<string, any> = { ...form };
    // 非语义检索不传相似度（与 PC 一致）
    if (payload.search_mode !== SearchMode.SIMILAR) delete payload.search_similar;
    emit("confirm", payload);
    emit("update:modelValue", false);
};
</script>

<style lang="scss" scoped>
.ms-sec {
    @apply flex items-center text-[26rpx] font-bold text-[#1D2129] px-[36rpx] pt-[24rpx] pb-[12rpx];
    &::before {
        content: "";
        @apply w-[6rpx] h-[28rpx] bg-[#2563EB] rounded-full mr-[12rpx] flex-shrink-0;
    }
}
.ms-card {
    @apply bg-[#F7F9FC] rounded-[20rpx] mx-[32rpx] px-[28rpx] py-[12rpx];
}
.ms-row {
    @apply py-[16rpx] border-0 border-t border-solid border-[#EAEEF5];
}
.ms-label {
    @apply text-[26rpx] font-semibold text-[#1D2129];
}
.ms-val {
    @apply text-[26rpx] font-bold text-[#2563EB];
}
.seg {
    @apply flex bg-white rounded-[16rpx] p-[8rpx] mt-[16rpx] border border-solid border-[#E5EAF3];
}
.seg-btn {
    @apply flex-1 text-center py-[14rpx] rounded-[12rpx] text-[24rpx] font-bold text-[#9CA3AF] transition-all;
    &.sel {
        @apply bg-[#EBF2FF] text-[#2563EB];
    }
}
.action-btn {
    @apply flex-1 h-[88rpx] rounded-[20rpx] flex items-center justify-center text-[28rpx] font-bold active:opacity-85;
}
.action-btn--cancel {
    @apply bg-[#F1F5F9] text-[#475569];
}
.action-btn--save {
    @apply text-white;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 8rpx 28rpx rgba(47, 115, 246, 0.32);
}
</style>
