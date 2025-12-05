<template>
    <view class="report-detail-page" v-if="detail">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
            <view class="flex justify-center w-full">
                <view
                    class="w-[250rpx] h-[78rpx] bg-[#7E9EF8] rounded-full flex items-center justify-center px-1 gap-2 relative"
                    id="tabs-container">
                    <view
                        class="absolute top-[8rpx] bottom-[8rpx] bg-white rounded-full transition-all duration-300 ease-in-out"
                        :style="sliderStyle">
                    </view>
                    <view
                        v-for="item in previewTabs"
                        :key="item.id"
                        :id="'tab-' + item.id"
                        class="relative z-10 flex-1 h-[62rpx] text-center flex items-center justify-center rounded-full text-white transition-all duration-300"
                        :class="{
                            '!text-primary font-bold': previewActiveTab === item.id,
                        }"
                        @click="handlePreviewTab(item.id)">
                        {{ item.label }}
                    </view>
                </view>
            </view>
        </u-navbar>
        <view class="grow min-h-0 pb-[120rpx]">
            <analysis v-if="previewActiveTab === 1" :detail="detail"></analysis>
            <chat-log v-if="previewActiveTab === 2" :id="state.id" :detail="detail" :scene-detail="sceneDetail" />
        </view>
        <view class="absolute bottom-[40rpx] left-0 right-0">
            <view class="flex items-center justify-center">
                <u-button type="primary" shape="circle" :custom-style="{ width: '500rpx' }" @click="openKnbBind">
                    <image class="w-[42rpx] h-[42rpx] mr-2" src="/static/images/common/kn_icon.png"></image>
                    将报告训练至知识库</u-button
                >
            </view>
        </view>
    </view>
    <KnbBind ref="knbBindRef" @confirm="handleKnbBind" />
</template>

<script setup lang="ts">
import { lpSceneDetail, lpAnalysisDetail, lpKnbTrain } from "@/api/ladder_player";
import Analysis from "./analysis.vue";
import ChatLog from "./chatlog.vue";
import KnbBind from "@/components/knb-bind/knb-bind.vue";
import { ref, watch, onMounted, nextTick, getCurrentInstance } from "vue";

const state = reactive({
    id: "",
});

const detail = ref<any>(null);
const sceneDetail = ref<any>(null);
const previewActiveTab = ref(1);
const previewTabs = [
    { label: "报告", id: 1 },
    { label: "对话", id: 2 },
];

const knbBindRef = ref<InstanceType<typeof KnbBind>>();
const openKnbBind = () => {
    knbBindRef.value?.open();
};

const handleKnbBind = async (knbId: string) => {
    uni.showLoading({
        title: "训练中",
        mask: true,
    });
    try {
        await lpKnbTrain({
            ids: [state.id],
            indexid: knbId,
        });
        knbBindRef.value?.close();
        uni.hideLoading();
        uni.showToast({
            title: "训练成功",
            icon: "none",
            duration: 3000,
        });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "绑定知识库失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中",
        mask: true,
    });
    try {
        const data = await lpAnalysisDetail({ id: state.id });
        detail.value = data;
        getSceneDetail();
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "获取报告详情失败",
            icon: "none",
            duration: 2000,
        });
    }
};

const getSceneDetail = async () => {
    const data = await lpSceneDetail({ id: detail.value.scene_id });
    sceneDetail.value = data;
};

const sliderStyle = ref({});
const instance = getCurrentInstance();

const updateSliderStyle = () => {
    if (!instance) return;

    nextTick(() => {
        const query = uni.createSelectorQuery().in(instance);
        const activeTabSelector = `#tab-${previewActiveTab.value}`;
        const containerSelector = "#tabs-container";

        query.select(activeTabSelector).boundingClientRect();
        query.select(containerSelector).boundingClientRect();

        query.exec((res) => {
            if (res && res[0] && res[1]) {
                const activeTabData = res[0];
                const containerData = res[1];

                if (activeTabData && containerData) {
                    const left = activeTabData.left - containerData.left;
                    const width = activeTabData.width;

                    sliderStyle.value = {
                        left: `${left}px`,
                        width: `${width}px`,
                    };
                } else {
                    console.error("Could not get bounding client rect for tab or container", {
                        activeTabData,
                        containerData,
                    });
                }
            } else {
                console.error("Query execution failed or returned unexpected result:", res);
            }
        });
    });
};

const handlePreviewTab = (id: number) => {
    previewActiveTab.value = id;
    updateSliderStyle();
};

onMounted(() => {
    setTimeout(() => {
        updateSliderStyle();
    }, 500);
});

onLoad((options: any) => {
    state.id = options.id;
    getDetail();
});
</script>

<style scoped lang="scss">
.report-detail-page {
    background: linear-gradient(180deg, rgba(223, 231, 252, 1) 0.43%, rgba(247, 255, 252, 0) 100%);

    @apply h-screen flex flex-col;
}
</style>
