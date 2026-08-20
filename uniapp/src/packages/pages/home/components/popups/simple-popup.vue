<template>
    <popup-bottom
        v-model="show"
        height="78%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-white px-[40rpx] py-[24rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[28rpx] bg-[#e5e7eb] rounded-full" />
                <view class="mb-[24rpx] flex items-center justify-between">
                    <text class="block text-[36rpx] font-bold text-[#1f2937]">
                        {{ modal?.title }}
                    </text>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center text-[44rpx] leading-none"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#8B9199" :size="20"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="p-[32rpx] pb-[80rpx] flex flex-col gap-[32rpx]">
                    <view
                        v-for="item in modal?.items || []"
                        :key="item.title"
                        class="bg-white rounded-[32rpx] p-[28rpx]"
                        @click="toggle(item.title)">
                        <view class="flex items-center justify-between gap-[20rpx]">
                            <view
                                class="w-[72rpx] h-[72rpx] rounded-[24rpx] flex items-center justify-center font-bold text-primary bg-primary-light-9 flex-shrink-0">
                                {{ item.avatar }}
                            </view>
                            <view class="flex-1 min-w-0">
                                <text class="block text-sm font-bold text-[#1f2937] line-clamp-1">
                                    {{ item.title }}
                                </text>
                                <text class="block mt-[4rpx] text-[22rpx] text-[#9ca3af] line-clamp-1">
                                    {{ item.desc }}
                                </text>
                            </view>
                            <text
                                class="rounded-[12rpx] px-[12rpx] py-[4rpx] text-[20rpx] font-medium text-primary bg-primary-light-9">
                                {{ item.tag }}
                            </text>
                        </view>
                        <view
                            v-if="expanded === item.title"
                            class="mt-[24rpx] p-[24rpx] rounded-[24rpx] text-[#4b5563] bg-[#f9fafb] text-xs leading-[40rpx]">
                            <text>{{ item.detail }}</text>
                            <view
                                class="mt-[20rpx] w-[120rpx] flex items-center justify-center rounded-[24rpx] px-[16rpx] py-[12rpx] text-xs font-semibold text-primary bg-primary-light-9"
                                @click.stop="emit('toast', '内容已复制')">
                                复制
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

export interface SimpleModalItem {
    avatar: string;
    title: string;
    desc: string;
    tag: string;
    detail: string;
}

export interface SimpleModalConfig {
    title: string;
    items: SimpleModalItem[];
}

const props = defineProps<{
    modelValue: boolean;
    modal: SimpleModalConfig | undefined;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "toast", message: string): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(v) {
        emit("update:modelValue", v);
    },
});

const expanded = ref("");
const toggle = (title: string) => {
    expanded.value = expanded.value === title ? "" : title;
};
watch(
    () => props.modelValue,
    (v) => {
        if (!v) expanded.value = "";
    },
);
</script>
