<template>
    <popup-bottom
        v-model="show"
        title="请选择形象"
        custom-class="bg-[#F9FAFB]"
        :is-disabled-touch="true"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="text-xs text-[#00000080] mt-2 px-4"> 已选：{{ chooseLists.length }} </view>
                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :hide-empty-view="true"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="py-[30rpx] px-[32rpx] grid grid-cols-3 gap-2">
                            <view
                                class="bg-[#E6E6E6] h-[288rpx] rounded-[24rpx] flex flex-col items-center justify-center"
                                @click="toClone">
                                <view
                                    class="w-[32rpx] h-[32rpx] rounded-full flex items-center justify-center bg-[#00000080]">
                                    <u-icon name="plus" color="#ffffff" size="20"></u-icon>
                                </view>
                                <view class="text-[#000000b3] mt-[18rpx]">去克隆</view>
                            </view>
                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="h-[288rpx] rounded-[24rpx] relative overflow-hidden card-gradient"
                                @click.stop="handleSelect(item)">
                                <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                                <view
                                    v-if="[0, 1].includes(item.status)"
                                    class="absolute top-0 left-0 w-full h-full bg-[#00000080] flex flex-col items-center justify-center">
                                    <view class="text-xs text-white">正在生成中</view>
                                    <view class="text-[22rpx] text-white mt-2">几分钟即可生成形象</view>
                                </view>
                                <view
                                    class="absolute top-0 left-0 w-full h-full bg-[#00000080]"
                                    v-if="isChoose(item) && item.status == 2">
                                    <view class="absolute top-2 right-2">
                                        <image
                                            src="/static/images/icons/success.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white"
                                    v-else></view>
                            </view>
                        </view>
                    </z-paging>
                </view>
                <view class="flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-4">
                    <view class="flex items-center gap-x-2" @click="toggleSelect">
                        <view class="w-[32rpx] h-[32rpx]">
                            <image
                                v-if="chooseLists.length > 0 && chooseLists.length == dataLists.length"
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
import { getPublicAnchorListV2 } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        limit?: number;
    }>(),
    {
        modelValue: false,
    }
);

const emit = defineEmits(["update:modelValue", "confirm", "close"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);
const chooseLists = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPublicAnchorListV2({
            page_no,
            page_size,
            status: [0, 1, 2],
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const isChoose = (data: any) => {
    return chooseLists.value.some((item) => item.id === data.id);
};

const handleSelect = (data: any) => {
    if ([0, 1].includes(data.status)) {
        uni.$u.toast(`该形象正在生成中，请稍后再选择`);
        return;
    }
    if (isChoose(data)) {
        chooseLists.value = chooseLists.value.filter((item) => item.id !== item.id);
    } else {
        if (props.limit && chooseLists.value.length >= props.limit) {
            uni.$u.toast(`最多选择${props.limit}个形象`);
            return;
        }
        chooseLists.value.push(data);
    }
};

const toggleSelect = () => {
    if (chooseLists.value.length == dataLists.value.length) {
        chooseLists.value = [];
    } else {
        chooseLists.value = dataLists.value.slice(0, props.limit || dataLists.value.length);
    }
};

const confirm = () => {
    emit("confirm", chooseLists.value);
    close();
};

const close = () => {
    chooseLists.value = [];
    show.value = false;
    emit("close");
};

const toClone = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
        params: {
            source: [DigitalHumanModelVersionEnum.CHANJING, DigitalHumanModelVersionEnum.STANDARD],
        },
    });
};

watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            pagingRef.value?.reload();
        }
    }
);
</script>

<style scoped></style>
