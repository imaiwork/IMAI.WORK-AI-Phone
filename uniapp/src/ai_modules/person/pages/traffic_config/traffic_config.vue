<template>
    <view class="min-h-screen bg-[#F4F7FA]">
        <u-navbar :border-bottom="false" :background="{ background: '#F4F7FA' }" title="获客与截流设置" title-bold>
        </u-navbar>
        <view class="px-[30rpx] pt-2 flex flex-col gap-4">
            <view
                v-for="item in typeList"
                :key="item.title"
                class="bg-white rounded-3xl p-5 flex items-center justify-between shadow-[0_2px_10px_rgba(0,0,0,0.02)] cursor-pointer hover:shadow-md transition group"
                @click="handleClick(item.type)">
                <view class="flex items-center gap-3">
                    <view
                        class="w-12 h-12 rounded-2xl flex items-center justify-center"
                        :style="{ backgroundColor: item.bgColor }">
                        <image :src="item.icon" class="w-[48rpx] h-[48rpx]" />
                    </view>
                    <view class="flex flex-col gap-2 flex-1">
                        <text class="text-[28rpx] font-bold text-[#212121]">{{ item.title }}</text>
                        <text class="text-[#888888] text-[22rpx]">{{ item.desc }}</text>
                    </view>
                </view>
                <u-icon name="arrow-right" color="#9CA3AF" size="24"></u-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { TrafficConfigType } from "./components/type";
import AddIcon from "@/ai_modules/person/static/icons/add.svg";
import ShareIcon from "@/ai_modules/person/static/icons/share.svg";
import GroupPurchaseIcon from "@/ai_modules/person/static/icons/group_purchase.svg";
import MapIcon from "@/ai_modules/person/static/icons/map.svg";
import ChatIcon from "@/ai_modules/person/static/icons/chat.svg";
import GuardIcon from "@/ai_modules/person/static/icons/guard.svg";

const personId = ref<string>("");

const typeList = [
    {
        bgColor: "#FFF0F0",
        icon: AddIcon,
        title: "视频号获客",
        desc: "监控视频号账号，精准寻找线索",
        type: TrafficConfigType.SPH,
    },
    {
        bgColor: "#FFF5E5",
        icon: ShareIcon,
        title: "视频截流设置",
        desc: "搜索同行视频，监控评论区截流",
        type: TrafficConfigType.Video,
    },
    {
        bgColor: "#EBF1FF",
        icon: GroupPurchaseIcon,
        title: "团购截流设置",
        desc: "监控团购评论区，截流高意向客户",
        type: TrafficConfigType.GroupPurchase,
    },
    {
        bgColor: "#F3E8FF",
        icon: MapIcon,
        title: "同城截流设置",
        desc: "扫描同城附近视频评论区，精准触达",
        type: TrafficConfigType.City,
    },
    // {
    //     bgColor: "#D1FAFE",
    //     icon: ChatIcon,
    //     title: "话术设置",
    //     desc: "设置话术，精准触达",
    //     type: TrafficConfigType.Chat,
    // },
    {
        bgColor: "#F0FDF4",
        icon: GuardIcon,
        title: "防封与频率设置",
        desc: "拟人停顿及每日互动上限、频率限制",
        type: TrafficConfigType.Guard,
    },
];

const handleClick = (type: TrafficConfigType) => {
    uni.$u.route({
        url: `/ai_modules/person/pages/traffic_config/traffic_config_setting`,
        params: {
            id: personId.value,
            type: type,
        },
    });
};

onLoad((options: any) => {
    personId.value = options.id;
});
</script>

<style scoped></style>
