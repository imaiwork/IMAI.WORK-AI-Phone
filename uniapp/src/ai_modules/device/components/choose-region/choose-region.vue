<template>
    <popup-bottom v-model="show" title="选择地区" custom-class="bg-[#F3F3F3]" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="text-xs text-[#00000080] mt-2 px-4"> 已选：{{ chooseLists.length }} </view>
                <view class="grow min-h-0 px-4 mt-4">
                    <view class="grid grid-cols-4 gap-2">
                        <view
                            v-for="item in regionList"
                            :key="item"
                            class="bg-white rounded-[20rpx] py-[18rpx] text-center font-bold"
                            :class="isChoose(item) ? 'text-primary shadow-[0_0_0_2rpx_#0065FB]' : ''"
                            @click="handleSelect(item)">
                            {{ item }}
                        </view>
                    </view>
                </view>
                <view class="flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-4">
                    <view class="flex items-center gap-x-2" @click="toggleSelect">
                        <view class="w-[32rpx] h-[32rpx]">
                            <image
                                v-if="chooseLists.length > 0 && chooseLists.length == regionList.length"
                                src="/static/images/icons/success.svg"
                                class="w-full h-full"
                                lazy></image>
                            <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else> </view>
                        </view>
                        <view>全选</view>
                    </view>
                    <view
                        class="text-white font-bold text-[30rpx] rounded-[20rpx] bg-primary h-[90rpx] w-[460rpx] flex items-center justify-center"
                        @click="confirm">
                        确定选择
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
