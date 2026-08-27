<template>
    <view class="h-screen flex flex-col bg-[#F3F7FC]">
        <view class="customer-hero shrink-0 relative overflow-hidden">
            <view class="hero-blob hero-blob-lg"></view>
            <view class="hero-blob hero-blob-sm"></view>
            <view class="relative z-10">
                <u-navbar
                    title="客资线索"
                    title-bold
                    title-color="#1D2129"
                    back-icon-color="#1D2129"
                    :border-bottom="false"
                    :background="{ background: 'transparent' }"
                    :is-fixed="false">
                </u-navbar>

                <view v-if="statisticsLoading" class="px-[32rpx] pt-[18rpx]">
                    <view class="skeleton-hero-block w-[300rpx] h-[24rpx] mb-[32rpx]"></view>
                    <view class="grid grid-cols-4 mb-[40rpx]">
                        <view v-for="item in 4" :key="item" class="shrink-0 px-[8rpx]">
                            <view class="flex flex-col items-center">
                                <view class="skeleton-hero-block w-[88rpx] h-[52rpx]"></view>
                                <view class="skeleton-hero-block w-[104rpx] h-[24rpx] mt-[16rpx]"></view>
                            </view>
                        </view>
                    </view>
                    <view class="source-strip">
                        <view
                            v-for="item in 3"
                            :key="item"
                            class="skeleton-hero-block w-[160rpx] h-[48rpx] rounded-full"></view>
                    </view>
                </view>

                <view v-else class="px-[32rpx] pt-[18rpx]">
                    <text class="text-[24rpx] mb-[32rpx] text-[#6B7280] block">
                        {{ statistics.summaryText }}
                    </text>
                    <view class="grid grid-cols-4 mb-[40rpx]">
                        <view v-for="item in overviewStats" :key="item.label" class="shrink-0 px-[8rpx]">
                            <view class="min-w-0 text-center">
                                <text
                                    class="text-[56rpx] font-bold leading-none block line-clamp-1"
                                    :class="item.value ? 'text-[#111827]' : 'text-[#B0B6C0]'">
                                    {{ formatDisplayNumber(item.value) }}
                                </text>
                                <text
                                    class="text-[20rpx] mt-[12rpx] leading-[28rpx] block line-clamp-1"
                                    :class="item.value ? 'text-[#6B7280]' : 'text-[#B0B6C0]'">
                                    {{ item.label }}
                                </text>
                            </view>
                        </view>
                    </view>

                    <scroll-view v-if="sourceStats.length" scroll-x class="source-scroll">
                        <view class="source-strip" style="white-space: nowrap">
                            <view v-for="item in sourceStats" :key="item.key" class="source-pill">
                                <text class="source-pill-text">
                                    {{ item.label }} {{ formatDisplayNumber(item.value) }}{{ item.unit }}
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </view>

        <view class="tab-bar shrink-0">
            <view class="px-[32rpx] pt-[16rpx] pb-[32rpx]">
                <view class="seg-ctrl">
                    <view
                        v-for="tab in domainTabs"
                        :key="tab.key"
                        class="seg-btn"
                        :class="{ 'seg-btn-active': activeDomain === tab.key }"
                        @click="handleDomain(tab.key)">
                        <text class="text-[26rpx] font-semibold">
                            {{ tab.label }} {{ formatDisplayNumber(domainCounts[tab.key]) }}
                        </text>
                    </view>
                </view>
            </view>

            <scroll-view v-if="activeDomain === 'public'" scroll-x class="w-full pb-[20rpx]">
                <view class="flex gap-[16rpx] px-[24rpx]" style="white-space: nowrap">
                    <view
                        v-for="item in platformFilters"
                        :key="item.key"
                        class="platform-filter shrink-0"
                        :class="{ 'platform-filter-active': activePlatform === item.key }"
                        @click="handlePlatform(item.key)">
                        <text class="text-xs font-semibold">{{ item.label }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="grow min-h-0 relative">
            <view v-if="listLoading" class="absolute inset-0 z-10 bg-[#F3F7FC] overflow-hidden">
                <scroll-view scroll-y class="h-full">
                    <customer-leads-skeleton />
                </scroll-view>
            </view>
            <z-paging
                ref="pagingRef"
                v-model="customers"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryCustomerList">
                <view class="px-[32rpx] pt-[8rpx] pb-[80rpx]">
                    <view
                        v-for="customer in customers"
                        :key="customer.id"
                        class="cust-card"
                        @click="openDetail(customer)">
                        <view class="px-[32rpx] pt-[32rpx] pb-[24rpx] flex items-start justify-between gap-[20rpx]">
                            <view class="flex items-center gap-[24rpx] flex-1 min-w-0">
                                <view
                                    class="w-[96rpx] h-[96rpx] rounded-[28rpx] flex items-center justify-center shrink-0 overflow-hidden"
                                    :class="customer.avatarClass">
                                    <image
                                        v-if="customer.avatar"
                                        :src="customer.avatar"
                                        class="w-full h-full"
                                        mode="aspectFill"></image>
                                    <text v-else class="text-white text-[22rpx] font-bold leading-tight text-center">
                                        {{ formatAvatar(customer.name) }}
                                    </text>
                                </view>
                                <view class="flex-1 min-w-0">
                                    <view class="flex items-center gap-[10rpx] mb-[12rpx]">
                                        <text class="text-[30rpx] font-bold text-[#111827] line-clamp-1">
                                            {{ customer.name }}
                                        </text>
                                        <image
                                            :src="getPlatformIcon(customer.platform)"
                                            class="w-[40rpx] h-[40rpx] shrink-0"></image>
                                    </view>
                                    <view class="flex items-center gap-[10rpx] mb-[4rpx]">
                                        <text class="text-[22rpx] text-[#6B7280] line-clamp-1">
                                            微信号：{{ customer.wechat || "未识别到微信号" }}
                                        </text>
                                        <text
                                            v-if="customer.wxStatusText"
                                            class="text-[20rpx] font-semibold px-[12rpx] py-[2rpx] rounded-full"
                                            :class="
                                                customer.wxStatus === 'added'
                                                    ? 'text-[#16A34A] bg-[#E8FAF0]'
                                                    : 'text-[#D97706] bg-[#FEF3C7]'
                                            ">
                                            {{ customer.wxStatusText }}
                                        </text>
                                    </view>
                                    <text class="text-[22rpx] text-[#9CA3AF] line-clamp-1">
                                        来源：{{ customer.source }}
                                    </text>
                                </view>
                            </view>
                            <text
                                class="text-[20rpx] font-bold shrink-0"
                                :class="customer.domain === 'private' ? 'badge-private' : 'badge-public'">
                                {{ customer.domain === "private" ? "私域客户" : "公域客户" }}
                            </text>
                        </view>

                        <view v-if="customer.domain === 'private'" class="data-row">
                            <view class="data-col">
                                <text
                                    class="text-[38rpx] font-bold leading-none"
                                    :class="customer.privateMessages ? 'text-[#111827]' : 'text-[#D1D5DB]'">
                                    {{ formatDisplayNumber(customer.privateMessages) }}
                                </text>
                                <text class="text-[20rpx] text-[#9CA3AF] mt-[8rpx]"> 私聊消息 </text>
                            </view>
                            <view class="data-col">
                                <text
                                    class="text-[38rpx] font-bold leading-none"
                                    :class="customer.momentActions ? 'text-[#111827]' : 'text-[#D1D5DB]'">
                                    {{ formatDisplayNumber(customer.momentActions) }}
                                </text>
                                <text class="text-[20rpx] text-[#9CA3AF] mt-[8rpx]"> 朋友圈互动 </text>
                            </view>
                        </view>

                        <view v-else class="public-metric-row row-divider-b">
                            <view class="public-metric-col">
                                <view
                                    class="metric-icon"
                                    :class="customer.publicReplies ? 'metric-icon-active' : 'metric-icon-muted'">
                                    <image
                                        :src="MessageCircleIcon"
                                        class="w-[28rpx] h-[28rpx]"
                                        :class="{ 'opacity-30': !customer.publicReplies }"></image>
                                </view>
                                <view class="min-w-0">
                                    <text
                                        class="text-[40rpx] font-bold leading-none block"
                                        :class="customer.publicReplies ? 'text-[#111827]' : 'text-[#D1D5DB]'">
                                        {{ formatDisplayNumber(customer.publicReplies) }}
                                    </text>
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx] block"> 平台回复次数 </text>
                                </view>
                            </view>
                            <view class="public-metric-col">
                                <view
                                    class="metric-icon"
                                    :class="customer.firstChatTime ? 'metric-icon-active' : 'metric-icon-muted'">
                                    <image
                                        :src="ClockIcon"
                                        class="w-[28rpx] h-[28rpx]"
                                        :class="{ 'opacity-30': !customer.firstChatTime }"></image>
                                </view>
                                <view class="min-w-0">
                                    <text
                                        class="text-[24rpx] font-bold leading-[34rpx] block line-clamp-1"
                                        :class="customer.firstChatTime ? 'text-[#111827]' : 'text-[#D1D5DB]'">
                                        {{ customer.firstChatTime || "未私聊" }}
                                    </text>
                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx] block"> 首次私聊 </text>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="customer.domain === 'private'"
                            class="lead-act-row tap-area"
                            @click.stop="openActivity(customer, 'chat')">
                            <view class="lead-act-icon">
                                <image :src="MessageSquareIcon" class="w-[28rpx] h-[28rpx]"></image>
                            </view>
                            <view class="flex-1 min-w-0 flex items-center">
                                <text class="text-xs font-semibold text-[#374151]">私聊记录</text>
                                <text class="text-xs text-[#9CA3AF] ml-[8rpx] line-clamp-1">
                                    共 {{ formatDisplayNumber(customer.privateMessages) }}条私聊消息
                                </text>
                            </view>
                            <u-icon name="arrow-right" color="#D1D5DB" size="24"></u-icon>
                        </view>

                        <view
                            v-else-if="customer.latestMessage"
                            class="latest-preview row-divider-b tap-area"
                            @click.stop="openActivity(customer, 'chat')">
                            <view class="flex items-center justify-between mb-[10rpx]">
                                <text class="text-[20rpx] text-[#9CA3AF] font-medium"> 最近消息 </text>
                                <text class="text-[20rpx] text-[#2F73F6] font-semibold"> 查看全部 › </text>
                            </view>
                            <text class="text-xs text-[#4B5563] leading-relaxed line-clamp-2">
                                "{{ customer.latestMessage }}"
                            </text>
                        </view>

                        <view
                            v-else
                            class="empty-message-row row-divider-b tap-area"
                            @click.stop="openActivity(customer, 'chat')">
                            <image :src="InboxIcon" class="w-[26rpx] h-[26rpx] opacity-40"></image>
                            <text class="text-xs text-[#D1D5DB]">暂无私聊记录</text>
                        </view>

                        <action-row
                            :icon="customer.domain === 'private' ? HeartIcon : ImageIcon"
                            :title="
                                customer.domain === 'private' ? '点赞评论朋友圈' : getPublicInteractionTitle(customer)
                            "
                            :desc="customer.momentSummary"
                            @click="openActivity(customer, 'moments')" />

                        <action-row
                            v-if="customer.groupName"
                            :icon="UsersIcon"
                            title="当前所在群聊"
                            :desc="customer.groupName"
                            :show-arrow="false" />

                        <view class="btn-bar" @click.stop>
                            <view class="btn-outline flex-1" @click.stop="openTrack(customer)">
                                <image :src="ClockIcon" class="w-[32rpx] h-[32rpx]"></image>
                                <text class="text-[30rpx] font-semibold text-[#6B7280]"> 跟进记录 </text>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty text="暂无客资线索" />
                </template>
            </z-paging>
        </view>

        <detail-popup v-model="showDetailPopup" :customer="activeCustomer" />

        <track-popup v-model="showTrackPopup" :customer="activeCustomer" :loading="trackLoading" />

        <activity-popup
            v-model="showActivityPopup"
            :customer="activeCustomer"
            :activity-type="activityType"
            :loading="activityLoading" />
    </view>
</template>

<script setup lang="ts">
import {
    getIntentionCircleInteractionDetail,
    getIntentionCustomerChatRecord,
    getIntentionCustomerLists,
    getIntentionFollowRecord,
    getIntentionStatistics,
} from "@/api/app";
import CommonDouyinIcon from "@/static/images/common/douyin_s.png";
import CommonKuaishouIcon from "@/static/images/common/kuaishou_s.png";
import CommonRedbookIcon from "@/static/images/common/redbook_s.png";
import CommonSphIcon from "@/static/images/common/sph_s.png";
import ClockIcon from "@/packages/static/icons/intention-customer/clock.svg";
import CrosshairIcon from "@/packages/static/icons/intention-customer/crosshair.svg";
import HeartIcon from "@/packages/static/icons/intention-customer/heart.svg";
import ImageIcon from "@/packages/static/icons/intention-customer/image.svg";
import InboxIcon from "@/packages/static/icons/intention-customer/inbox.svg";
import MessageCircleIcon from "@/packages/static/icons/intention-customer/message-circle.svg";
import MessageSquareIcon from "@/packages/static/icons/intention-customer/message-square.svg";
import UserCheckIcon from "@/packages/static/icons/intention-customer/user-check.svg";
import UsersIcon from "@/packages/static/icons/intention-customer/users.svg";
import { formatNumberToWanOrYi } from "@/utils/util";
import ActionRow from "./components/action-row.vue";
import CustomerLeadsSkeleton from "./components/customer-leads-skeleton.vue";
import PopupHeader from "./components/popup-header.vue";
import DetailPopup from "./components/popups/detail-popup.vue";
import TrackPopup from "./components/popups/track-popup.vue";
import ActivityPopup from "./components/popups/activity-popup.vue";
import type {
    ActivityType,
    ChatMessage,
    Customer,
    CustomerPlatform,
    DetailRow,
    Domain,
    MomentLog,
    Platform,
    StatisticsData,
    TrackItem,
} from "./components/types";

// 所有 type / interface 已迁至 ./components/types.ts

const domainTabs: Array<{ key: Domain; label: string }> = [
    { key: "public", label: "公域客户" },
    { key: "private", label: "私域客户" },
];

const platformFilters: Array<{ key: Platform; label: string }> = [
    { key: "all", label: "全部" },
    { key: "xhs", label: "小红书" },
    { key: "douyin", label: "抖音" },
    { key: "kuaishou", label: "快手" },
    { key: "shipinhao", label: "视频号" },
];

const platformIconMap: Record<CustomerPlatform, string> = {
    douyin: CommonDouyinIcon,
    xhs: CommonRedbookIcon,
    kuaishou: CommonKuaishouIcon,
    shipinhao: CommonSphIcon,
};

const platformParamMap: Record<Platform, string | number> = {
    all: "",
    xhs: 3,
    douyin: 4,
    kuaishou: 5,
    shipinhao: 1,
};

const avatarClassList = ["avatar-blue", "avatar-pink", "avatar-cyan", "avatar-green", "avatar-purple", "avatar-orange"];

const customers = ref<Customer[]>([]);
const activeDomain = ref<Domain>("public");
const activePlatform = ref<Platform>("all");
const activeCustomer = ref<Customer | null>(null);
const activityType = ref<ActivityType>("chat");
const showDetailPopup = ref(false);
const showTrackPopup = ref(false);
const showActivityPopup = ref(false);
const trackLoading = ref(false);
const activityLoading = ref(false);
const pagingRef = shallowRef();
const domainCounts = reactive<Record<Domain, number>>({
    public: 0,
    private: 0,
});
const statistics = ref<StatisticsData>(createEmptyStatisticsData());
const statisticsLoading = ref(true);
const listLoading = ref(true);
let activityRequestId = 0;
let trackRequestId = 0;

const overviewStats = computed(() => [
    { label: "总触达客户", value: statistics.value.totalTouchCustomerCount },
    { label: "线索数", value: statistics.value.clueCount },
    { label: "添加好友", value: statistics.value.addFriendCount },
    { label: "拉群数", value: statistics.value.createGroupCount },
]);

const sourceStats = computed(() => statistics.value.sourceStats);

const handleDomain = (domain: Domain) => {
    if (activeDomain.value === domain) return;
    activeDomain.value = domain;
    activePlatform.value = "all";
    listLoading.value = true;
    pagingRef.value?.reload();
};

const handlePlatform = (platform: Platform) => {
    if (activePlatform.value === platform) return;
    activePlatform.value = platform;
    listLoading.value = true;
    pagingRef.value?.reload();
};

const queryCustomerList = async (page_no: number, page_size: number) => {
    try {
        const platform = activeDomain.value === "public" ? platformParamMap[activePlatform.value] : "";
        const data = await getIntentionCustomerLists({
            page_no,
            page_size,
            domain: activeDomain.value,
            platform,
        });
        const lists = Array.isArray(data?.lists) ? data.lists : [];
        const count = toNumber(data?.count);

        if (activeDomain.value === "private" || activePlatform.value === "all") {
            domainCounts[activeDomain.value] = count;
        }

        pagingRef.value?.complete(
            lists.map((item: Record<string, any>, index: number) => normalizeCustomer(item, index)),
        );
    } catch (error) {
        pagingRef.value?.complete(false);
    } finally {
        if (page_no === 1) listLoading.value = false;
    }
};

const openDetail = (customer: Customer) => {
    activeCustomer.value = customer;
    showDetailPopup.value = true;
};

const openTrack = async (customer: Customer) => {
    activeCustomer.value = customer;
    showTrackPopup.value = true;

    if (customer.trackLoaded) return;

    const requestId = ++trackRequestId;
    trackLoading.value = true;
    try {
        const data = await getIntentionFollowRecord(buildCustomerParams(customer));
        if (requestId !== trackRequestId || activeCustomer.value?.id !== customer.id) return;

        customer.track = normalizeTrackRecords(data?.records);
        customer.trackLoaded = true;
    } catch (error) {
        if (requestId === trackRequestId) {
            uni.$u.toast("获取跟进记录失败");
        }
    } finally {
        if (requestId === trackRequestId) {
            trackLoading.value = false;
        }
    }
};

const openActivity = async (customer: Customer, type: ActivityType) => {
    // 爬取来源(crawling)的线索没有聊天记录,"查看全部"直接预览截图
    if (type === "chat" && customer.sourceKey === "crawling") {
        if (customer.image) {
            uni.previewImage({ urls: [customer.image] });
        } else {
            uni.showToast({ title: "暂无图片", icon: "none" });
        }
        return;
    }

    activeCustomer.value = customer;
    activityType.value = type;
    showActivityPopup.value = true;

    if (type === "chat" && customer.chatLoaded) return;
    if (type === "moments" && customer.momentsLoaded) return;

    const requestId = ++activityRequestId;
    activityLoading.value = true;
    try {
        if (type === "chat") {
            const data = await getIntentionCustomerChatRecord(buildCustomerParams(customer));
            if (!isCurrentActivityRequest(requestId, customer, type)) return;

            customer.chatLog = normalizeChatMessages(data?.messages);
            customer.chatScreenshots = normalizeImages(data?.screenshots);
            customer.chatLoaded = true;
            return;
        }

        const data = await getIntentionCircleInteractionDetail(buildCustomerParams(customer));
        if (!isCurrentActivityRequest(requestId, customer, type)) return;

        customer.momentsLog = normalizeMomentLogs(data?.lists);
        const detailCount = toNumber(data?.summary?.interaction_count);
        const detailLikeCount = toNumber(data?.summary?.like_count);
        const detailCommentCount = toNumber(data?.summary?.comment_count);
        // 详情接口未返回互动统计时，保留列表接口已有的点赞/评论数据，避免覆盖成“暂无互动”
        if (detailCount || detailLikeCount || detailCommentCount) {
            customer.momentActions = detailCount;
            customer.momentSummary = buildMomentSummary({
                count: detailCount,
                likeCount: detailLikeCount,
                commentCount: detailCommentCount,
            });
        }
        customer.momentsLoaded = true;
    } catch (error) {
        if (requestId === activityRequestId) {
            uni.$u.toast(type === "chat" ? "获取聊天记录失败" : "获取朋友圈互动失败");
        }
    } finally {
        if (requestId === activityRequestId) {
            activityLoading.value = false;
        }
    }
};

const isCurrentActivityRequest = (requestId: number, customer: Customer, type: ActivityType) => {
    return requestId === activityRequestId && activeCustomer.value?.id === customer.id && activityType.value === type;
};

const buildCustomerParams = (customer: Customer) => ({
    id: customer.id,
    platform: customer.platformValue || "",
    account: customer.account || "",
    customer_name: customer.name || "",
    account_name: customer.accountName || "",
});

const queryStatistics = async () => {
    try {
        const data = await getIntentionStatistics();
        statistics.value = normalizeStatistics(data || {});
    } finally {
        statisticsLoading.value = false;
    }
};

const queryDomainCount = async (domain: Domain) => {
    try {
        const data = await getIntentionCustomerLists({
            page_no: 1,
            page_size: 1,
            domain,
            platform: "",
        });
        domainCounts[domain] = toNumber(data?.count);
    } catch (error) {
        domainCounts[domain] = domainCounts[domain] || 0;
    }
};

const formatAvatar = (name: string) => {
    if (!name) return "";
    if (name.length <= 2) return name;
    return `${name.slice(0, 2)}\n${name.slice(2, 4)}`;
};

const getPlatformIcon = (platform: Customer["platform"]) => {
    return platformIconMap[platform] || CommonDouyinIcon;
};

const getPublicInteractionTitle = (customer: Customer) => {
    return `${customer.platformDesc || getPlatformLabel(customer.platform)}互动`;
};

const formatDisplayNumber = (value: number) => {
    return String(formatNumberToWanOrYi(value || 0));
};

function normalizeStatistics(data: Record<string, any>): StatisticsData {
    return {
        summaryText: String(data.summary_text || "累计触达客资线索").replace(/意向客户/g, "客资线索"),
        totalTouchCustomerCount: toNumber(data.total_touch_customer_count),
        clueCount: toNumber(data.clue_count),
        addFriendCount: toNumber(data.add_friend_count),
        createGroupCount: toNumber(data.create_group_count),
        sourceStats: Array.isArray(data.source_stats)
            ? data.source_stats.map((item: Record<string, any>) => ({
                  key: String(item.key || item.name || ""),
                  label: item.name || item.key || "未知来源",
                  value: toNumber(item.count),
                  unit: item.unit || "人",
              }))
            : [],
    };
}

function normalizeCustomer(data: Record<string, any>, index: number): Customer {
    const domain = normalizeDomain(data.domain);
    const platform = normalizePlatform(data.platform, data.platform_desc);
    const name = data.customer_name || data.account_name || data.account || "未知客户";
    const wechat = data.wechat_no || "";
    const wechatStatus = normalizeWechatStatus(data.wechat_status, wechat);
    const platformMessages = toNumber(data.platform_message_count);
    const publicReplies = toNumber(data.platform_reply_count);
    const privateMessages = toNumber(data.private_chat_count);
    const momentActions = toNumber(data.circle_interaction_count);
    const latestMessage = data.latest_intention || "";
    const firstChatTime = formatDateTimeText(data.first_private_time);
    const source = data.source_text || data.source_name || "未知来源";

    const customer: Customer = {
        id: String(data.id || `${domain}_${data.account || index}`),
        name,
        accountName: data.account_name || name,
        account: data.account || "",
        avatar: data.avatar || "",
        wechat,
        ...wechatStatus,
        domain,
        platform,
        platformValue: data.platform || "",
        platformDesc: data.platform_desc || getPlatformLabel(platform),
        source,
        sourceName: data.source_name || "",
        sourceKey: data.source_key || "",
        image: data.image || "",
        avatarClass: avatarClassList[index % avatarClassList.length],
        privateMessages,
        momentActions,
        publicReplies,
        platformMessages,
        firstChatTime,
        groupName: data.group_name || "",
        latestMessage,
        momentSummary: buildMomentSummary({
            count: momentActions,
            likeCount: toNumber(data.circle_like_count),
            commentCount: toNumber(data.circle_comment_count),
        }),
        rows: [],
        chatLog: latestMessage
            ? [
                  {
                      from: "customer",
                      text: latestMessage,
                      time: firstChatTime || formatDateTimeText(data.create_time),
                  },
              ]
            : [],
        chatScreenshots: [],
        momentsLog: [],
        track: [],
        chatLoaded: false,
        momentsLoaded: false,
        trackLoaded: false,
    };

    customer.rows = createDetailRows(customer);
    return customer;
}

function createDetailRows(customer: Customer): DetailRow[] {
    const rows: DetailRow[] = [
        { icon: CrosshairIcon, label: "客户来源", value: customer.source },
        {
            icon: MessageSquareIcon,
            label: "平台私聊条数",
            value: `共 ${customer.platformMessages} 条`,
        },
        { icon: ClockIcon, label: "首次私聊时间", value: customer.firstChatTime || "未私聊" },
        {
            icon: HeartIcon,
            label: "朋友圈互动",
            value: customer.momentSummary,
        },
    ];

    rows.push({
        icon: UserCheckIcon,
        label: "微信号",
        value: customer.wechat || "未识别到微信号",
    });

    if (customer.latestMessage) {
        rows.push({ icon: InboxIcon, label: "最新意图", value: customer.latestMessage });
    }
    if (customer.groupName) {
        rows.push({ icon: UsersIcon, label: "当前所在群聊", value: customer.groupName });
    }
    return rows;
}

function normalizeTrackRecords(records: any): TrackItem[] {
    if (!Array.isArray(records)) return [];

    let hasActive = false;
    return records.map((item: Record<string, any>) => {
        const done = item.status === "done" || item.status === 1 || item.done === true;
        const active = !done && !hasActive && (item.status === "pending" || item.status === "active" || !item.status);
        if (active) {
            hasActive = true;
        }

        return {
            stage: item.title || item.key || "跟进节点",
            time: formatDateTimeText(item.time),
            desc: item.desc || "",
            done,
            active,
            chat: Array.isArray(item.content) ? item.content.map((text: unknown) => String(text)) : [],
        };
    });
}

function normalizeChatMessages(messages: any): ChatMessage[] {
    if (!Array.isArray(messages)) return [];
    return messages
        .map((item: Record<string, any>) => {
            const text = item.content || item.message || item.text || item.msg || "";
            return {
                from: normalizeMessageSender(item),
                text,
                time: formatDateTimeText(item.time || item.create_time || item.send_time),
            };
        })
        .filter((item) => item.text);
}

function normalizeMessageSender(item: Record<string, any>): ChatMessage["from"] {
    // 后端新增 is_self（客户 0 / 回复方 1）与 bubble_side（left / right），优先据此区分气泡归属
    if (item.is_self !== undefined && item.is_self !== null) {
        return Number(item.is_self) === 1 ? "agent" : "customer";
    }
    if (item.bubble_side === "right" || item.bubble_side === "left") {
        return item.bubble_side === "right" ? "agent" : "customer";
    }
    const sender = String(item.from || item.role || item.sender || item.sender_type || item.type || "");
    if (
        sender.includes("agent") ||
        sender.includes("ai") ||
        sender.includes("assistant") ||
        sender.includes("system") ||
        item.is_ai === 1 ||
        item.is_ai === true
    ) {
        return "agent";
    }
    return "customer";
}

function normalizeMomentLogs(lists: any): MomentLog[] {
    if (!Array.isArray(lists)) return [];
    return lists.map((item: Record<string, any>) => ({
        time: formatDateTimeText(item.time || item.create_time || item.interaction_time),
        content: item.content || item.text || item.desc || item.moment_content || item.title || "朋友圈动态",
        liked: Boolean(item.liked || item.is_like || item.like_status || item.like_count),
        comment: item.comment || item.comment_content || item.reply_content || "",
        image: normalizeImage(item.image || item.screenshot || item.cover || item.url),
    }));
}

function normalizeImages(images: any): string[] {
    if (!Array.isArray(images)) return [];
    return images
        .map((item) => {
            if (typeof item === "string") return item;
            return normalizeImage(item?.url || item?.image || item?.screenshot);
        })
        .filter(Boolean);
}

function normalizeImage(value: unknown) {
    return typeof value === "string" ? value : "";
}

function normalizeWechatStatus(status: unknown, wechat: string) {
    if (!wechat) return {};

    const value = String(status || "");
    const statusTextMap: Record<string, string> = {
        added: "已添加",
        passed: "已添加",
        success: "已添加",
        pending: "待验证",
        applied: "已申请",
        replied: "已回复",
    };
    const isAdded = ["added", "passed", "success", "1"].includes(value);

    return {
        wxStatus: isAdded ? "added" : "pending",
        wxStatusText: statusTextMap[value] || (isAdded ? "已添加" : "待验证"),
    } as Pick<Customer, "wxStatus" | "wxStatusText">;
}

function normalizeDomain(value: unknown): Domain {
    return value === "private" ? "private" : "public";
}

function normalizePlatform(platform: unknown, platformDesc?: string): CustomerPlatform {
    const value = Number(platform);
    if (value === 3) return "xhs";
    if (value === 4) return "douyin";
    if (value === 5) return "kuaishou";
    if (value === 1) return "shipinhao";

    const desc = String(platformDesc || "");
    if (desc.includes("小红书")) return "xhs";
    if (desc.includes("快手")) return "kuaishou";
    if (desc.includes("视频号")) return "shipinhao";
    return "douyin";
}

function getPlatformLabel(platform: CustomerPlatform) {
    const map: Record<CustomerPlatform, string> = {
        douyin: "抖音",
        xhs: "小红书",
        kuaishou: "快手",
        shipinhao: "视频号",
    };
    return map[platform];
}

function buildMomentSummary(options: { count: number; likeCount: number; commentCount: number }) {
    if (!options.count && !options.likeCount && !options.commentCount) return "暂无互动";

    const parts = [];
    if (options.likeCount) parts.push(`点赞 ${options.likeCount}次`);
    if (options.commentCount) parts.push(`评论 ${options.commentCount}次`);
    return parts.length ? parts.join(" · ") : `共 ${options.count}次`;
}

function formatDateTimeText(value: unknown) {
    const text = String(value || "");
    if (!text) return "";

    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/);
    if (match) {
        return `${match[2]}月${match[3]}日 ${match[4]}:${match[5]}`;
    }
    return text;
}

function toNumber(value: unknown) {
    const numberValue = Number(value);
    return Number.isFinite(numberValue) ? numberValue : 0;
}

function createEmptyStatisticsData(): StatisticsData {
    return {
        summaryText: "累计触达客资线索",
        totalTouchCustomerCount: 0,
        clueCount: 0,
        addFriendCount: 0,
        createGroupCount: 0,
        sourceStats: [],
    };
}

onLoad(() => {
    queryStatistics();
    queryDomainCount(activeDomain.value === "public" ? "private" : "public");
});
</script>

<style scoped lang="scss">
.customer-hero {
    background: linear-gradient(180deg, #cfe0ff 0%, #dce8ff 45%, #f3f7fc 100%);
}

.hero-blob {
    @apply absolute rounded-full;

    background: rgba(255, 255, 255, 0.46);
}

.hero-blob-lg {
    @apply top-[-76rpx] right-[-72rpx] w-[260rpx] h-[260rpx];
}

.hero-blob-sm {
    @apply left-[34rpx] bottom-[84rpx] w-[136rpx] h-[136rpx];

    opacity: 0.52;
}

.source-pill {
    @apply flex items-center justify-center px-[24rpx] py-[10rpx] border-[2rpx] border-solid rounded-full shrink-0;

    border-color: rgba(47, 115, 246, 0.16);
    background: rgba(47, 115, 246, 0.07);
}

.skeleton-hero-block {
    @apply rounded-[12rpx] shrink-0;

    background: linear-gradient(90deg, rgba(255, 255, 255, 0.4) 25%, rgba(255, 255, 255, 0.7) 50%, rgba(255, 255, 255, 0.4) 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

@keyframes skeleton-loading {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

.source-scroll {
    @apply w-full;
}

.source-strip {
    @apply flex items-center gap-[16rpx] min-h-[68rpx];
}

.source-pill-text {
    @apply text-[#2f73f6] text-[22rpx] font-semibold leading-[30rpx];
}

.tab-bar {
    @apply bg-[#f3f7fc];
}

.seg-ctrl {
    @apply flex gap-[8rpx] p-[8rpx] rounded-[24rpx] bg-[#eef1f6];
}

.seg-btn {
    @apply flex flex-1 items-center justify-center min-w-0 px-0 py-[26rpx] rounded-[20rpx] text-[#6b7280];
}

.seg-btn-active {
    @apply text-[#2f73f6] font-bold bg-white;

    box-shadow: 0 4rpx 12rpx rgba(47, 115, 246, 0.1);
}

.platform-filter {
    @apply flex items-center justify-center px-[28rpx] py-[12rpx] rounded-full text-[#4b5563] bg-white border-[2rpx] border-solid border-[#e2e6ee];
}

.platform-filter-active {
    @apply text-white bg-[#2f73f6] border-[#2f73f6];
}

.cust-card {
    @apply mb-[24rpx] overflow-hidden rounded-[40rpx] bg-white;

    box-shadow: 0 4rpx 28rpx rgba(47, 115, 246, 0.07);
}

.cust-card:active {
    transform: scale(0.985);
}

.badge-private,
.badge-public {
    @apply px-[18rpx] py-[6rpx] rounded-full;
}

.badge-private {
    @apply text-[#2f73f6] bg-[#ebf2ff];
}

.badge-public {
    @apply text-[#6b7280] bg-[#f3f4f6];
}

.data-row {
    @apply flex items-stretch bg-white;
}

.data-col {
    @apply flex flex-1 flex-col items-center justify-center min-w-0 px-[8rpx] py-[24rpx];
}

.public-metric-row {
    @apply flex items-center bg-white;
}

.public-metric-col {
    @apply flex flex-1 items-center min-w-0 px-[32rpx] py-[26rpx] gap-[18rpx];
}

.metric-icon {
    @apply flex items-center justify-center w-[64rpx] h-[64rpx] rounded-[20rpx] shrink-0;
}

.metric-icon-active {
    @apply bg-[#ebf2ff];
}

.metric-icon-muted {
    @apply bg-[#f5f5f5];
}

.row-divider-b {
    @apply relative;
}

.row-divider-b::after {
    @apply absolute right-[32rpx] bottom-0 left-[32rpx] h-[2rpx] bg-[#eef1f6];

    content: "";
}

.lead-act-row {
    @apply relative flex items-center px-[32rpx] py-[20rpx] gap-[24rpx];
}

.lead-act-row::after {
    @apply absolute right-[32rpx] bottom-0 left-[32rpx] h-[2rpx] bg-[#eef1f6];

    content: "";
}

.lead-act-icon {
    @apply flex items-center justify-center w-[64rpx] h-[64rpx] rounded-[20rpx] bg-[#ebf2ff] shrink-0;
}

.latest-preview {
    @apply px-[32rpx] py-[22rpx];
}

.empty-message-row {
    @apply flex items-center px-[32rpx] py-[28rpx] gap-[14rpx];
}

.btn-bar {
    @apply relative flex items-center px-[32rpx] py-[26rpx] gap-[20rpx];
}

.btn-bar::before {
    @apply absolute top-0 right-[32rpx] left-[32rpx] h-[2rpx] bg-[#eef1f6];

    content: "";
}

.btn-outline {
    @apply flex items-center justify-center min-h-[88rpx] px-[26rpx] py-0 border-[3rpx] border-solid border-[#e5e7eb] rounded-[24rpx] gap-[12rpx] bg-white;
}

.avatar-blue {
    background: linear-gradient(140deg, #4f6ef7, #2f73f6);
}

.avatar-pink {
    background: linear-gradient(140deg, #f472b6, #ec4899);
}

.avatar-cyan {
    background: linear-gradient(140deg, #22d3ee, #0891b2);
}

.avatar-green {
    background: linear-gradient(140deg, #34d399, #059669);
}

.avatar-orange {
    background: linear-gradient(140deg, #fb923c, #ea580c);
}

.avatar-purple {
    background: linear-gradient(140deg, #a78bfa, #7c3aed);
}
</style>
