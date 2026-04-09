<template>
    <view class="h-screen flex flex-col bg-[#F5F7FA]">
        <view class="relative overflow-hidden" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)">
            <view
                class="absolute -right-12 -top-12 w-[280rpx] h-[280rpx] rounded-full bg-[#ffffff]/5 pointer-events-none"></view>
            <view
                class="absolute -left-8 bottom-0 w-[180rpx] h-[180rpx] rounded-full bg-[#ffffff]/5 pointer-events-none"></view>

            <u-navbar
                title="使用教程"
                :border-bottom="false"
                :background="{ background: 'transparent' }"
                title-color="#ffffff"
                back-icon-color="#ffffff">
            </u-navbar>

            <view class="px-4 pb-4">
                <view class="flex items-center gap-2 bg-[#ffffff]/15 rounded-[20rpx] px-4 h-[76rpx] mb-4">
                    <u-icon name="search" color="rgba(255,255,255,0.7)" size="30"></u-icon>
                    <input
                        v-model="searchKeyword"
                        class="flex-1 text-[26rpx] text-white"
                        placeholder="搜索教程名称"
                        placeholder-style="color:rgba(255,255,255,0.45)" />
                    <view
                        v-if="searchKeyword"
                        class="w-[40rpx] h-[40rpx] flex items-center justify-center rounded-full bg-white/20"
                        @click="searchKeyword = ''">
                        <u-icon name="close" color="#fff" size="20"></u-icon>
                    </view>
                </view>

                <view class="flex bg-[#ffffff]/10 rounded-[16rpx] p-[5rpx]" v-if="false">
                    <view
                        v-for="(tab, index) in topTabs"
                        :key="index"
                        class="flex-1 h-[64rpx] flex items-center justify-center rounded-[12rpx] text-[26rpx] font-semibold transition-all"
                        :class="currentTopTab === index ? 'bg-white text-[#1e40af]' : 'text-[#ffffff]/70'"
                        @click="handleTopTab(index)">
                        {{ tab.label }}
                    </view>
                </view>
            </view>
        </view>

        <view class="flex-1 min-h-0">
            <view v-if="currentTopTab === 0 || currentTopTab === 1" class="flex h-full">
                <scroll-view scroll-y class="w-[152rpx] flex-shrink-0 bg-white">
                    <view class="flex flex-col py-2">
                        <view
                            v-for="(cat, ci) in categoryList"
                            :key="ci"
                            class="relative flex flex-col items-center justify-center py-5 gap-[8rpx]"
                            @click="currentCategory = ci">
                            <view
                                v-if="currentCategory === ci"
                                class="absolute left-0 top-1/2 w-[5rpx] h-[48rpx] rounded-r-full bg-[#3b82f6]"
                                style="transform: translateY(-50%)">
                            </view>
                            <view
                                class="w-[56rpx] h-[56rpx] rounded-[16rpx] flex items-center justify-center"
                                :style="
                                    currentCategory === ci
                                        ? 'background:linear-gradient(135deg,#3b82f6,#6366f1)'
                                        : 'background:#F3F4F6'
                                ">
                                <u-icon
                                    :name="cat.icon"
                                    :color="currentCategory === ci ? '#ffffff' : '#9CA3AF'"
                                    size="26">
                                </u-icon>
                            </view>
                            <text
                                class="text-[22rpx] text-center leading-tight"
                                :class="currentCategory === ci ? 'text-[#1e40af] font-bold' : 'text-[#9CA3AF]'">
                                {{ cat.name }}
                            </text>
                        </view>
                    </view>
                </scroll-view>

                <scroll-view scroll-y class="flex-1 min-w-0 px-3 py-3">
                    <view v-if="currentTopTab === 0" class="flex flex-col gap-4">
                        <view v-if="filteredVideoList.length === 0" class="flex flex-col items-center py-24 gap-3">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-full bg-[#EFF6FF] flex items-center justify-center">
                                <u-icon name="search" color="#93C5FD" size="56"></u-icon>
                            </view>
                            <text class="text-[#C0C4CC] text-[24rpx]">暂无相关教程</text>
                        </view>

                        <view
                            v-for="(item, vi) in filteredVideoList"
                            :key="vi"
                            class="rounded-[24rpx] overflow-hidden"
                            style="box-shadow: 0 8rpx 32rpx rgba(59, 130, 246, 0.15)">
                            <view
                                class="relative h-[180rpx]"
                                style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%)"
                                @click="handleVideoPlay(item)">
                                <view class="absolute inset-0 px-5 pt-5">
                                    <view class="flex items-center gap-2 mb-2">
                                        <view
                                            class="px-[12rpx] py-[4rpx] rounded-full bg-[#ffffff]/20 flex items-center gap-1">
                                            <u-icon
                                                name="play-right-fill"
                                                color="rgba(255,255,255,0.8)"
                                                size="16"></u-icon>
                                            <text class="text-[18rpx] text-[#ffffff]/80 tracking-wide">视频教程</text>
                                        </view>
                                    </view>
                                    <text class="text-[30rpx] font-extrabold text-white leading-snug">{{
                                        item.title
                                    }}</text>
                                </view>
                                <view
                                    class="absolute right-5 bottom-5 w-[72rpx] h-[72rpx] rounded-full bg-[#ffffff]/25 flex items-center justify-center">
                                    <u-icon name="play-right-fill" color="#fff" size="34"></u-icon>
                                </view>
                            </view>
                            <view class="bg-white">
                                <view
                                    v-for="(sub, si) in item.subList"
                                    :key="si"
                                    class="flex justify-between px-4 py-[24rpx]"
                                    :class="si < item.subList.length - 1 ? 'border-b border-[#F1F5F9]' : ''">
                                    <view class="flex items-center gap-3">
                                        <view
                                            class="w-[36rpx] h-[36rpx] rounded-[10rpx] bg-[#EFF6FF] flex items-center justify-center">
                                            <u-icon name="play-right-fill" color="#3b82f6" size="16"></u-icon>
                                        </view>
                                        <text class="text-[26rpx] text-[#374151] font-medium">{{ sub.title }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-if="currentTopTab === 1" class="flex flex-col gap-3">
                        <view v-if="filteredArticleList.length === 0" class="flex flex-col items-center py-24 gap-3">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-full bg-[#EFF6FF] flex items-center justify-center">
                                <u-icon name="search" color="#93C5FD" size="56"></u-icon>
                            </view>
                            <text class="text-[#C0C4CC] text-[24rpx]">暂无相关教程</text>
                        </view>

                        <view
                            v-for="(item, ai) in filteredArticleList"
                            :key="ai"
                            class="bg-white rounded-[24rpx] overflow-hidden active:scale-[0.99] transition-transform"
                            style="box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06)"
                            @click="handleArticleDetail(item)">
                            <view class="relative">
                                <image :src="item.cover" class="w-full h-[180rpx]" mode="aspectFill"></image>
                                <view
                                    class="absolute inset-0"
                                    style="
                                        background: linear-gradient(to top, rgba(0, 0, 0, 0.25) 0%, transparent 50%);
                                    "></view>
                                <view
                                    class="absolute top-3 left-3 px-3 py-1 rounded-full bg-[#3b82f6] flex items-center gap-1">
                                    <u-icon name="file-text" color="#ffffff" size="16"></u-icon>
                                    <text class="text-white text-[18rpx] font-medium">图文</text>
                                </view>
                            </view>
                            <view class="p-4">
                                <text class="text-[28rpx] font-bold text-[#111827] line-clamp-1 block">{{
                                    item.title
                                }}</text>
                                <text class="text-[22rpx] text-[#9CA3AF] mt-2 line-clamp-2 leading-relaxed block">{{
                                    item.summary
                                }}</text>
                                <view class="flex items-center justify-between mt-3 pt-3 border-t border-[#F1F5F9]">
                                    <view class="flex items-center gap-1">
                                        <u-icon name="clock" color="#D1D5DB" size="20"></u-icon>
                                        <text class="text-[20rpx] text-[#D1D5DB]">{{ item.date }}</text>
                                    </view>
                                    <view class="flex items-center gap-1 bg-[#EFF6FF] px-3 py-1 rounded-full">
                                        <text class="text-[22rpx] text-[#3b82f6] font-semibold">查看详情</text>
                                        <u-icon name="arrow-right" color="#3b82f6" size="20"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <scroll-view v-if="currentTopTab === 2" scroll-y class="h-full">
                <view class="px-4 pt-4 pb-8">
                    <view v-if="filteredTipList.length === 0" class="flex flex-col items-center py-24 gap-3">
                        <view class="w-[120rpx] h-[120rpx] rounded-full bg-[#EFF6FF] flex items-center justify-center">
                            <u-icon name="search" color="#93C5FD" size="56"></u-icon>
                        </view>
                        <text class="text-[#C0C4CC] text-[24rpx]">暂无相关提示</text>
                    </view>

                    <view
                        v-if="filteredTipList.length > 0"
                        class="rounded-[28rpx] overflow-hidden mb-4 active:scale-[0.99] transition-transform"
                        style="box-shadow: 0 8rpx 32rpx rgba(59, 130, 246, 0.15)"
                        @click="handleArticleDetail(filteredTipList[0])">
                        <view class="relative h-[280rpx]">
                            <image :src="filteredTipList[0].cover" class="w-full h-full" mode="aspectFill"></image>
                            <view class="absolute inset-0 bg-mask"></view>
                            <view
                                class="absolute top-4 left-4 px-3 py-1 rounded-full bg-[#3b82f6] flex items-center gap-1">
                                <u-icon name="star" color="#ffffff" size="16"></u-icon>
                                <text class="text-white text-[18rpx] font-bold">置顶推荐</text>
                            </view>
                            <view class="absolute bottom-0 left-0 right-0 p-5">
                                <text class="text-[30rpx] font-extrabold text-white line-clamp-2 leading-snug block">
                                    {{ filteredTipList[0].title }}
                                </text>
                                <view class="flex items-center justify-between mt-2">
                                    <view class="flex items-center gap-1">
                                        <u-icon name="clock" color="rgba(255,255,255,0.6)" size="18"></u-icon>
                                        <text class="text-[20rpx] text-[#ffffff]/60">{{
                                            filteredTipList[0].date
                                        }}</text>
                                    </view>
                                    <view class="flex items-center gap-1">
                                        <text class="text-[22rpx] text-[#ffffff]/80">查看详情</text>
                                        <u-icon name="arrow-right" color="rgba(255,255,255,0.8)" size="20"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="flex flex-col gap-3">
                        <view
                            v-for="(item, ti) in filteredTipList.slice(1)"
                            :key="ti"
                            class="bg-white rounded-[24rpx] overflow-hidden flex active:scale-[0.99] transition-transform"
                            style="box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05)"
                            @click="handleArticleDetail(item)">
                            <view class="relative w-[200rpx] flex-shrink-0">
                                <image
                                    :src="item.cover"
                                    class="w-full h-full"
                                    style="min-height: 150rpx"
                                    mode="aspectFill"></image>

                                <view
                                    class="absolute top-2 left-2 w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center"
                                    style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                    <text class="text-[18rpx] text-white font-bold">{{ ti + 2 }}</text>
                                </view>
                            </view>
                            <view class="flex-1 min-w-0 p-3 flex flex-col justify-between">
                                <view>
                                    <view
                                        class="inline-flex items-center gap-1 bg-[#EFF6FF] px-2 py-[3rpx] rounded-full mb-2">
                                        <u-icon name="info-circle" color="#3b82f6" size="16"></u-icon>
                                        <text class="text-[18rpx] text-[#3b82f6] font-medium">使用提示</text>
                                    </view>
                                    <text
                                        class="text-[26rpx] font-bold text-[#111827] line-clamp-2 leading-snug block"
                                        >{{ item.title }}</text
                                    >
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-1 line-clamp-2 leading-relaxed block">{{
                                        item.summary
                                    }}</text>
                                </view>
                                <view class="flex items-center justify-between mt-2">
                                    <view class="flex items-center gap-1">
                                        <u-icon name="clock" color="#D1D5DB" size="16"></u-icon>
                                        <text class="text-[18rpx] text-[#D1D5DB]">{{ item.date }}</text>
                                    </view>
                                    <view class="flex items-center gap-0.5">
                                        <text class="text-[20rpx] text-[#3b82f6] font-medium">详情</text>
                                        <u-icon name="arrow-right" color="#3b82f6" size="18"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import config from "@/config";
const searchKeyword = ref("");
const currentTopTab = ref(0);
const currentCategory = ref(0);

const topTabs = [{ label: "视频教程" }, { label: "图文教程" }, { label: "使用提示" }];

const categoryList = [
    { name: "内容分发", icon: "share" },
    { name: "AI获客", icon: "search" }, // index 1 → 评论获客 + 私信获客
    { name: "私域运营", icon: "grid" }, // index 2 → 私聊接管
    { name: "客服接管", icon: "chat" }, // index 3 → 自动获线索 + 自动加好友 + 自动养号
];

const videoList = ref<any[]>([
    {
        category: 0,
        title: "【发布图文】演示",
        subList: [{ title: "如何快速创建并发布一篇图文内容？" }, { title: "图文排版技巧与发布注意事项" }],
        url: `${config.baseUrl}static/videos/task_image_publish.mp4`,
    },
    {
        category: 0,
        title: "【发布视频】演示",
        subList: [{ title: "如何上传并发布短视频内容？" }, { title: "视频封面设置与发布前检查要点" }],
        url: `${config.baseUrl}static/videos/task_video_publish.mp4`,
    },

    {
        category: 1,
        title: "【截流获客】演示",
        subList: [{ title: "如何通过批量评论精准触达目标用户？" }, { title: "评论话术配置与获客转化技巧" }],
        url: `${config.baseUrl}static/videos/task_comment_marketing.mp4`,
    },
    {
        category: 1,
        title: "【留痕获客】演示",
        subList: [{ title: "如何设置自动私信触达潜在客户？" }, { title: "私信话术模板配置与发送策略说明" }],
        url: `${config.baseUrl}static/videos/task_dm_marketing.mp4`,
    },

    {
        category: 3,
        title: "【私聊接管】演示",
        subList: [{ title: "如何开启私聊自动接管并配置回复规则？" }, { title: "智能话术匹配与人工切换操作指南" }],
        url: `${config.baseUrl}static/videos/task_chat_manage.mp4`,
    },

    {
        category: 1,
        title: "【自动获线索】演示",
        subList: [{ title: "如何通过 AI 自动抓取并筛选意向线索？" }, { title: "线索来源配置与质量过滤规则设置" }],
        url: `${config.baseUrl}static/videos/task_clue_auto.mp4`,
    },
    {
        category: 2,
        title: "【自动加好友】演示",
        subList: [{ title: "如何批量发起好友申请并设置打招呼话术？" }, { title: "加好友频率控制与账号安全注意事项" }],
        url: `${config.baseUrl}static/videos/task_friend_add.mp4`,
    },
    {
        category: 2,
        title: "【自动养号】演示",
        subList: [{ title: "如何配置自动养号任务提升账号活跃度？" }, { title: "养号行为策略设置与风险规避建议" }],
        url: `${config.baseUrl}static/videos/task_account_maintain.mp4`,
    },
]);
const articleList = ref<any[]>([]);

const tipList = ref<any[]>([]);

const showVideoPreview = ref(false);
const playItem = ref<{ url: string; poster: string }>({ url: "", poster: "" });

const filteredVideoList = computed(() =>
    videoList.value.filter((item) => {
        const matchCat = item.category === currentCategory.value;
        const matchSearch =
            !searchKeyword.value ||
            item.title.includes(searchKeyword.value) ||
            item.subList.some((s: any) => s.title.includes(searchKeyword.value));
        return matchCat && matchSearch;
    })
);

const filteredArticleList = computed(() =>
    articleList.value.filter((item) => {
        const matchCat = item.category === currentCategory.value;
        const matchSearch =
            !searchKeyword.value ||
            item.title.includes(searchKeyword.value) ||
            item.summary.includes(searchKeyword.value);
        return matchCat && matchSearch;
    })
);

const filteredTipList = computed(() =>
    tipList.value.filter(
        (item) =>
            !searchKeyword.value ||
            item.title.includes(searchKeyword.value) ||
            item.summary.includes(searchKeyword.value)
    )
);

const handleTopTab = (index: number) => {
    currentTopTab.value = index;
    currentCategory.value = 0;
};

const handleVideoPlay = (item: any) => {
    playItem.value = { url: item.url, poster: "" };
    showVideoPreview.value = true;
};

const handleSubItem = (sub: any) => {
    toPage();
};

const handleArticleDetail = (item: any) => {
    toPage();
};

const toPage = () => {
    uni.navigateTo({ url: "/packages/pages/operate_case_detail/operate_case_detail" });
};
</script>

<style scoped lang="scss">
.bg-mask {
    background: linear-gradient(to top, rgba(15, 23, 42, 0.78) 0%, rgba(15, 23, 42, 0.08) 60%);
}
</style>
