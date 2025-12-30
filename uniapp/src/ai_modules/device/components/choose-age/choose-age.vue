<template>
    <u-popup
        v-model="show"
        mode="bottom"
        border-radius="24"
        :safe-area-inset-bottom="true"
        :mask-close-able="true"
        @close="close">
        <view
            class="bg-white px-4 pt-4 transition-all duration-200"
            :style="{ paddingBottom: dynamicHeight > 0 ? dynamicHeight + 'px' : 'env(safe-area-inset-bottom)' }">
            <view class="flex items-center justify-between mb-6">
                <view class="text-[#0000004d]" @click="close">取消</view>
                <view class="font-bold text-[32rpx]">选择用户年龄</view>
                <view class="text-primary font-bold" @click="handleAgeConfirm">确定</view>
            </view>

            <view class="mb-6">
                <view class="text-sm font-bold mb-3 text-gray-500">常用年龄段</view>
                <view class="flex flex-wrap gap-3">
                    <view
                        v-for="(item, index) in agePresets"
                        :key="index"
                        class="px-4 py-2 rounded-full text-xs font-bold border transition-all"
                        :class="
                            isAgeSelected(item)
                                ? 'bg-[#EBF5FF] text-primary border-primary'
                                : 'bg-[#F6F7F9] text-gray-500 border-transparent'
                        "
                        @click="handleSelectAgePreset(item)">
                        {{ item.label }}
                    </view>
                </view>
            </view>

            <view class="mb-4" :class="{ 'mb-0': dynamicHeight > 0 }">
                <view class="text-sm font-bold mb-3 text-gray-500">自定义范围</view>
                <view class="flex items-center gap-3">
                    <view class="flex-1 bg-[#F6F7F9] rounded-[12rpx] h-[80rpx] flex items-center px-3">
                        <u-input
                            v-model="tempAgeMin"
                            type="number"
                            placeholder="最小年龄"
                            :custom-style="{ textAlign: 'center' }"
                            placeholder-style="font-size: 26rpx"
                            :adjust-position="false" />
                    </view>
                    <view class="text-gray-400 font-bold">-</view>
                    <view class="flex-1 bg-[#F6F7F9] rounded-[12rpx] h-[80rpx] flex items-center px-3">
                        <u-input
                            v-model="tempAgeMax"
                            type="number"
                            placeholder="最大年龄"
                            :custom-style="{ textAlign: 'center' }"
                            placeholder-style="font-size: 26rpx"
                            :adjust-position="false" />
                    </view>
                    <view class="text-sm font-bold text-gray-600">岁</view>
                </view>
            </view>

            <view class="h-[20rpx]" v-if="dynamicHeight === 0"></view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import useKeyboardHeight from "@/hooks/useKeyboardHeight";

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "close"): void;
    (e: "confirm", value: string): void;
}>();

const { dynamicHeight } = useKeyboardHeight();

const show = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
const tempAgeMin = ref("");
const tempAgeMax = ref("");

// 常用年龄段预设
const agePresets = [
    { label: "不限", min: "", max: "" },
    { label: "18-23岁", min: "18", max: "23" },
    { label: "24-30岁", min: "24", max: "30" },
    { label: "31-40岁", min: "31", max: "40" },
    { label: "41-50岁", min: "41", max: "50" },
    { label: "50岁以上", min: "50", max: "99" },
];
const isAgeSelected = (item: any) => {
    // 特殊处理"不限"
    if (item.label === "不限") {
        return !tempAgeMin.value && !tempAgeMax.value;
    }
    return tempAgeMin.value === item.min && tempAgeMax.value === item.max;
};

const handleSelectAgePreset = (item: any) => {
    tempAgeMin.value = item.min;
    tempAgeMax.value = item.max;
};

const handleAgeConfirm = () => {
    if (!tempAgeMin.value && !tempAgeMax.value) {
        emit("confirm", "不限");
        close();
        return;
    }

    const min = parseInt(tempAgeMin.value || "0");
    const max = parseInt(tempAgeMax.value || "100");

    if (tempAgeMin.value && tempAgeMax.value && min > max) {
        uni.$u.toast("最小年龄不能大于最大年龄");
        return;
    }

    // 格式化输出
    let result = "";
    if (!tempAgeMin.value && tempAgeMax.value) {
        result = `0-${max}岁`;
    } else if (tempAgeMin.value && !tempAgeMax.value) {
        result = `${min}-99岁`;
    } else {
        result = `${min}-${max}岁`;
    }
    emit("confirm", result);
    close();
};

const close = () => {
    show.value = false;
    emit("close");
};

const setFormData = (data: string) => {
    if (data === "不限") {
        tempAgeMin.value = "";
        tempAgeMax.value = "";
    } else {
        const [min, max] = data.replace("岁", "").split("-");
        tempAgeMin.value = min || "";
        tempAgeMax.value = max || "";
    }
};
defineExpose({
    setFormData,
});
</script>

<style scoped></style>
