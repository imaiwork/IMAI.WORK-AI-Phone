<template>
    <scroll-view scroll-y class="scroll-view h-full" :scroll-top="scrollTop">
        <view class="h-full flex flex-col">
            <view class="font-bold text-[#3D3D3D] text-[48rpx] mx-4 mt-[48rpx]"
                >恭喜完成练习，这是为您生成的评测报告:</view
            >
            <view class="bg-[#F5F8FF] rounded-tl-xl rounded-tr-xl p-3 mt-[48rpx] pb-[100rpx]">
                <view class="bg-white rounded-xl p-3">
                    <view>
                        <view class="text-[#97969C] font-bold">总得分</view>
                        <view>
                            <text class="text-primary font-bold text-[72rpx]">{{ detail.total_score || 0 }}</text>
                            <text class="text-[#97969C]">/100</text>
                        </view>
                    </view>
                    <view class="w-full mx-auto mt-2">
                        <pie-chat :indicator="indicator"></pie-chat>
                    </view>
                    <view class="rounded-xl bg-[#FAFAFA] p-3 mt-[12rpx]">
                        <view class="flex items-center gap-2">
                            <image
                                src="@/ai_modules/ladder_player/static/icons/time.svg"
                                class="w-[36rpx] h-[36rpx] flex-shrink-0"></image>
                            <text class="text-xs text-[#545358]"
                                >您本次的练习时长：{{ formatTime(detail.duration) }}</text
                            >
                        </view>
                        <view class="flex gap-2 mt-3">
                            <image
                                src="@/ai_modules/ladder_player/static/icons/edit.svg"
                                class="w-[36rpx] h-[36rpx] flex-shrink-0 mt-1"></image>
                            <text class="text-xs text-[#545358] leading-[44rpx] whitespace-pre-line">
                                {{ detail.total_response }}
                            </text>
                        </view>
                    </view>
                </view>
                <view class="mt-4">
                    <u-sticky :offset-top="stickyTop" bg-color="transparent">
                        <view class="flex gap-2 bg-[#7E9EF8] rounded-[16rpx] p-1">
                            <view
                                v-for="(item, index) in indicator"
                                :key="index"
                                class="h-[70rpx] px-2 text-xs text-white rounded-[16rpx] whitespace-nowrap flex items-center justify-center flex-1"
                                :class="{
                                    'bg-white !text-primary': indicatorTab === index,
                                }"
                                @click="handleIndicatorTab(index)">
                                {{ item.name }}
                            </view>
                        </view>
                    </u-sticky>
                </view>
                <view
                    class="bg-white rounded-xl p-3 mt-4 column"
                    :id="item.id"
                    v-for="item in indicator"
                    :key="item.id">
                    <view class="text-primary font-bold text-xl">
                        {{ item.name }}
                    </view>
                    <view class="flex items-center -mt-2">
                        <view class="w-[356rpx] mt-2">
                            <u-line />
                        </view>
                        <view class="ml-[64rpx]">
                            <text class="text-primary font-bold text-[48rpx]">{{ item.score || 0 }}</text>
                            <text class="text-[#97969C]">/20</text>
                        </view>
                    </view>
                    <view class="flex justify-between">
                        <view class="flex items-center gap-2">
                            <image
                                src="@/ai_modules/ladder_player/static/icons/tongdian.svg"
                                class="w-[48rpx] h-[48rpx] flex-shrink-0"></image>
                            <text class="text-xs font-bold text-[#60636B]">痛点分析</text>
                        </view>
                        <view class="flex items-center mt-2 gap-[2rpx]">
                            <view v-for="index in 5" :key="index" class="w-[24rpx] h-[24rpx]">
                                <image
                                    src="@/ai_modules/ladder_player/static/icons/score_star_fill.svg"
                                    class="w-full h-full"
                                    v-if="getStar(item.score) >= index"></image>
                                <image
                                    src="@/ai_modules/ladder_player/static/icons/score_star.svg"
                                    class="w-full h-full"
                                    v-else></image>
                            </view>
                        </view>
                    </view>
                    <view class="rounded-xl bg-[#F8F9FE] p-3 mt-3 leading-[44rpx] text-xs break-words">
                        {{ item.content }}
                    </view>
                </view>
            </view>
        </view>
    </scroll-view>
</template>

<script setup lang="ts">
import { getRect } from "@/utils/util";
import { useAppStore } from "@/stores/app";
import PieChat from "../../components/pie-chat/pie-chat.vue";

const props = defineProps({
    detail: {
        type: Object,
        default: () => ({}),
    },
});

const appStore = useAppStore();

const stickyTop = ref(0);
const offsetTop = ref(0);

const indicator = ref([
    {
        id: "sfl",
        name: "说服力",
        top: 0,
        height: 0,
        score: 0,
        max: 20,
        content: "",
    },
    {
        id: "lld",
        name: "流利度",
        top: 0,
        height: 0,
        score: 0,
        max: 20,
        content: "",
    },
    {
        id: "yybd",
        name: "语言表达",
        top: 0,
        height: 0,
        score: 0,
        max: 20,
        content: "",
    },
    {
        id: "zqx",
        name: "准确性",
        top: 0,
        height: 0,
        score: 0,
        max: 20,
        content: "",
    },
    {
        id: "yyzz",
        name: "语言组织能力",
        top: 0,
        height: 0,
        score: 0,
        max: 20,
        content: "",
    },
]);

const { proxy }: any = getCurrentInstance();
const getTagDom = async () => {
    await nextTick();
    const elements = await getRect(".column", true, proxy);
    if (elements) {
        //@ts-ignore
        elements.forEach((item: any, index: number) => {
            indicator.value[index].top = item.top;
            indicator.value[index].height = item.height;
        });
        const { screenHeight } = uni.$u.sys();
        uni.createIntersectionObserver(proxy, {
            observeAll: true,
            thresholds: [1],
            initialRatio: 0.5,
        })
            .relativeTo(".scroll-view", {
                top: -(screenHeight / 2),
            })
            .observe(".column", (res: any) => {
                if (res && res.intersectionRatio == 1) {
                    const visibleIndex = indicator.value.findIndex((item) => item.id === res.id);
                    if (visibleIndex !== -1 && !isClickTab.value) {
                        indicatorTab.value = visibleIndex;
                    }
                }
            });
    }
};

const indicatorTab = ref(0);
const scrollTop = ref(0);
const isClickTab = ref(false);
const handleIndicatorTab = async (index: number) => {
    await nextTick();
    indicatorTab.value = index;
    const { top, height } = indicator.value[index];
    scrollTop.value = top - height / 2 - offsetTop.value + 20;
    isClickTab.value = true;
    // 防止触发赋值indicatorTab
    setTimeout(() => {
        isClickTab.value = false;
    }, 50);
};

const formatTime = (time: any) => {
    if (time > 0) {
        const minutes = Math.floor(time / 60);
        const remainingSeconds = time % 60;
        return `${minutes}"${remainingSeconds}'`;
    }
    return "0";
};

const getStar = (score: number) => {
    return (score / 20) * 5;
};

watch(
    () => props.detail,
    (data) => {
        if (data) {
            data.model_response.forEach((item: any, index: number) => {
                indicator.value[index].score = item.score;
                indicator.value[index].content = item.improvement_suggestions;
            });
            getTagDom();
        }
    },
    { immediate: true }
);

watch(
    () => appStore.getLadderConfig.directions,
    (val) => {
        indicator.value.forEach((item, index) => {
            if (val[index]) {
                item.name = val[index];
            }
        });
    }
);

onMounted(() => {
    //  #ifdef H5
    stickyTop.value = 30;
    // #endif
    // #ifdef MP-WEIXIN
    stickyTop.value = 190;
    // #endif
    offsetTop.value = uni.$u.sys().statusBarHeight;
});
</script>

<style scoped></style>
