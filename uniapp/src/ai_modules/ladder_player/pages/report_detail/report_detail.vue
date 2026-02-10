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
                            '!text-primary font-medium': previewActiveTab === item.id,
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
    </view>
</template>

<script setup lang="ts">
import { lpSceneDetail, lpAnalysisDetail, lpKnbTrain } from "@/api/ladder_player";
import Analysis from "./analysis.vue";
import ChatLog from "./chatlog.vue";

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
