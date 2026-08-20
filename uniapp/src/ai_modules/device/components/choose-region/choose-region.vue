<template>
    <popup-bottom v-model="show" title="选择地区" custom-class="bg-[#F7F9FC]" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <!-- 已选计数 -->
                <view class="flex items-center justify-between px-4 pt-[16rpx] pb-[12rpx]">
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full" />
                        <text class="font-extrabold text-[#0D1117]">选择省份 / 地区</text>
                    </view>
                    <view
                        class="flex items-center gap-[6rpx] px-[16rpx] py-[8rpx] rounded-full"
                        :class="chooseLists.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                        <text
                            class="text-[22rpx] font-semibold"
                            :class="chooseLists.length > 0 ? 'text-primary' : 'text-[#9CA3AF]'">
                            已选 {{ chooseLists.length }}
                        </text>
                    </view>
                </view>

                <!-- 地区网格 -->
                <view class="grow min-h-0 px-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-4 gap-[12rpx] pb-[20rpx]">
                            <view
                                v-for="item in regionList"
                                :key="item"
                                class="flex items-center justify-center py-[18rpx] rounded-[20rpx] transition-all duration-200"
                                :class="
                                    isChoose(item)
                                        ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                        : 'bg-white shadow-[0_1rpx_4rpx_rgba(0,0,0,0.04),0_0_0_1rpx_rgba(0,0,0,0.03)]'
                                "
                                @click="handleSelect(item)">
                                <text
                                    class="text-xs font-bold"
                                    :class="isChoose(item) ? 'text-primary' : 'text-[#4B5563]'">
                                    {{ item }}
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <!-- 底部：全选 + 确定 -->
                <view
                    class="px-4 pt-[16rpx] pb-[calc(16rpx+env(safe-area-inset-bottom))] border-[0] border-t border-solid border-[#F0F2F5] bg-white flex items-center gap-[16rpx]">
                    <!-- 全选 -->
                    <view
                        class="flex items-center gap-[10rpx] h-[96rpx] px-[24rpx] rounded-[24rpx] border border-solid transition-all duration-200"
                        :class="
                            chooseLists.length === regionList.length
                                ? 'bg-[#EBF2FF] border-[#BFDBFE]'
                                : 'bg-[#F7F9FC] border-[#E5E9F0]'
                        "
                        @click="toggleSelect">
                        <view
                            class="w-[36rpx] h-[36rpx] rounded-full border-[2rpx] border-solid flex items-center justify-center flex-shrink-0 transition-all duration-200"
                            :class="
                                chooseLists.length === regionList.length
                                    ? 'bg-primary border-primary'
                                    : 'bg-white border-[#D1D5DB]'
                            ">
                            <u-icon
                                v-if="chooseLists.length === regionList.length"
                                name="checkmark"
                                color="#fff"
                                size="18" />
                        </view>
                        <text
                            class="font-bold"
                            :class="chooseLists.length === regionList.length ? 'text-primary' : 'text-[#4B5563]'">
                            全选
                        </text>
                    </view>

                    <!-- 确定按钮 -->
                    <view
                        class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden transition-all duration-200"
                        :class="chooseLists.length > 0 ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                        :style="
                            chooseLists.length > 0
                                ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                                : 'background: #C0C4CC'
                        "
                        @click="confirm">
                        <text class="text-[30rpx] font-extrabold text-white tracking-wide">确定选择</text>
                    </view>
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
    (e: "confirm", value: { regionList: string[]; isAll: boolean }): void;
    (e: "close"): void;
}>();
//
const regionList = [
    "广东",
    "四川",
    "湖南",
    "北京",
    "重庆",
    "内蒙古",
    "黑龙江",
    "宁夏",
    "江苏",
    "河南",
    "上海",
    "陕西",
    "云南",
    "贵州",
    "吉林",
    "青海",
    "山东",
    "湖北",
    "安徽",
    "江西",
    "广西",
    "新疆",
    "甘肃",
    "西藏",
    "浙江",
    "福建",
    "河北",
    "辽宁",
    "山西",
    "天津",
    "海南",
    "中国香港",
    "中国澳门",
];
const chooseLists = ref<string[]>([]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const isChoose = (data: any) => {
    return chooseLists.value.some((item) => item === data);
};

const handleSelect = (data: any) => {
    if (isChoose(data)) {
        chooseLists.value = chooseLists.value.filter((item) => item !== data);
    } else {
        chooseLists.value.push(data);
    }
};

const toggleSelect = () => {
    if (chooseLists.value.length == regionList.length) {
        chooseLists.value = [];
    } else {
        chooseLists.value = regionList.slice(0, regionList.length);
    }
};

const confirm = () => {
    emit("confirm", {
        regionList: chooseLists.value,
        isAll: chooseLists.value.length == regionList.length,
    });
    close();
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
