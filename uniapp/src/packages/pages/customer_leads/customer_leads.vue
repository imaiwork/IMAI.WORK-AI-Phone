<template>
    <view class="h-screen flex flex-col bg-[#F5F7FB]">
        <view class="shrink-0 bg-white">
            <u-navbar
                title="客资线索"
                title-bold
                title-color="#1E293B"
                back-icon-color="#1E293B"
                :border-bottom="false"
                :background="{ background: '#FFFFFF' }"
                :is-fixed="false" />
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="customers"
                :fixed="false"
                :safe-area-inset-bottom="true"
                :empty-view-center="false"
                :auto-scroll-to-top-when-reload="false"
                @query="queryCustomerList">
                <leads-overview :statistics="statistics" :loading="statisticsLoading" />
                <view class="flex items-center justify-between gap-[16rpx] mx-[40rpx] mt-[48rpx] mb-[16rpx]">
                    <text class="text-xl font-semibold text-[#1E293B]">客户明细</text>
                    <text class="text-[22rpx] text-[#617087]">沟通 · 互动 · 跟进</text>
                </view>
                <view class="domain-tabs">
                    <view
                        v-for="tab in domainTabs"
                        :key="tab.key"
                        class="domain-tab"
                        :class="{ 'domain-tab-active': activeDomain === tab.key }"
                        role="button"
                        :aria-label="tab.label"
                        @click="handleDomain(tab.key)">
                        <text>{{ tab.label }}</text>
                        <view class="domain-count">
                            <view
                                v-if="activeDomain === tab.key"
                                class="absolute inset-0 bg-primary opacity-[0.06]"></view>
                            <text class="relative">{{ formatDisplayNumber(domainCounts[tab.key]) }}</text>
                        </view>
                    </view>
                </view>
                <view v-if="activeDomain === 'public'" class="flex gap-[8rpx] px-[28rpx] py-[16rpx]">
                    <view
                        v-for="item in platformFilters"
                        :key="item.key"
                        class="platform-filter"
                        :class="{ 'platform-filter-active': activePlatform === item.key }"
                        role="button"
                        :aria-label="item.label"
                        @click="handlePlatform(item.key)">
                        <text class="relative">{{ item.label }}</text>
                    </view>
                </view>
                <view v-else class="h-[32rpx]"></view>

                <view class="mx-[28rpx]">
                    <customer-leads-skeleton v-if="listLoading" />
                    <view v-else class="overflow-hidden rounded-[30rpx] bg-white">
                        <view v-for="(customer, index) in customers" :key="customer.id">
                            <view v-if="index" class="h-[12rpx] bg-[#F5F7FB]"></view>
                            <customer-record
                                :customer="customer"
                                @detail="openDetail"
                                @activity="openActivity"
                                @track="openTrack" />
                        </view>
                    </view>
                </view>
                <template #empty="{ isLoadFailed }">
                    <view v-if="!listLoading" class="mx-[28rpx] mb-[48rpx] rounded-[30rpx] bg-white">
                        <leads-empty
                            :text="isLoadFailed ? '客资线索加载失败' : emptyListTitle"
                            :description="isLoadFailed ? '请检查网络后重试。' : emptyListDescription" />
                        <view v-if="isLoadFailed || activePlatform !== 'all'" class="flex justify-center pb-[32rpx]">
                            <view
                                class="min-h-[88rpx] px-[32rpx] flex items-center justify-center text-xs text-primary"
                                role="button"
                                @click="isLoadFailed ? pagingRef?.reload() : handlePlatform('all')">
                                {{ isLoadFailed ? "重新加载" : "查看全部平台" }}
                            </view>
                        </view>
                    </view>
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
import ClockIcon from "@/packages/static/icons/intention-customer/clock.svg";
import CrosshairIcon from "@/packages/static/icons/intention-customer/crosshair.svg";
import HeartIcon from "@/packages/static/icons/intention-customer/heart.svg";
import InboxIcon from "@/packages/static/icons/intention-customer/inbox.svg";
import MessageSquareIcon from "@/packages/static/icons/intention-customer/message-square.svg";
import UserCheckIcon from "@/packages/static/icons/intention-customer/user-check.svg";
import UsersIcon from "@/packages/static/icons/intention-customer/users.svg";
import { formatNumberToWanOrYi } from "@/utils/util";
import LeadsOverview from "./components/leads-overview.vue";
import LeadsSourceChart from "./components/leads-source-chart.vue";
import CustomerRecord from "./components/customer-record.vue";
import LeadsEmpty from "./components/leads-empty.vue";
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

const emptyListTitle = computed(() => {
    if (activePlatform.value !== "all") return "这个平台还没有线索";
    return activeDomain.value === "private" ? "暂时没有私域客户" : "暂时没有客资线索";
});
const emptyListDescription = computed(() =>
    activePlatform.value !== "all" ? "切换平台，查看其他来源的客户。" : "有新的客户记录后，会显示在这里。",
);

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
        recognized: "已验证",
        unrecognized: "待验证",
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
    };
}

onLoad(() => {
    queryStatistics();
    queryDomainCount(activeDomain.value === "public" ? "private" : "public");
});
</script>

<style scoped lang="scss">
.domain-tabs {
    @apply flex gap-[48rpx] mx-[36rpx] border-[0] border-b-[2rpx] border-solid border-[#E8EDF4];
}

.domain-tab {
    @apply relative flex items-center gap-[16rpx] min-h-[88rpx] text-lg text-[#617087];
}

.domain-tab-active {
    @apply font-semibold text-[#1E293B];

    &::after {
        @apply absolute left-0 right-0 bottom-[-2rpx] h-[4rpx] rounded-full bg-primary;
        content: "";
    }

    .domain-count {
        @apply text-primary bg-white;
    }
}

.domain-count {
    @apply relative overflow-hidden flex items-center justify-center min-w-[38rpx] min-h-[40rpx] px-[8rpx] rounded-[10rpx] bg-[#E9EEF5] text-xs font-medium;
}

.platform-filter {
    @apply relative flex flex-1 min-w-0 items-center justify-center min-h-[88rpx] text-xs text-[#617087];
}

.platform-filter-active {
    @apply text-primary font-semibold;

    &::before {
        @apply absolute inset-x-0 inset-y-[14rpx] rounded-[16rpx] bg-white;
        content: "";
        box-shadow: 0 2rpx 6rpx rgba(37, 59, 98, 0.04);
    }
}
</style>
