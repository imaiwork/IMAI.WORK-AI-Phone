<template>
    <popup-bottom
        v-model="visibleProxy"
        height="88%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true">
        <template #header>
            <view
                class="bg-white rounded-t-[32rpx] pt-[24rpx] pb-[24rpx] px-[40rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]">
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[24rpx] bg-[#e5e7eb] rounded-full" />
                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-[16rpx]">
                        <view
                            class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-success-light-9 flex items-center justify-center">
                            <image :src="headerIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">
                                {{ overview.title || "帮我管理微信客户" }}
                            </text>
                            <text class="block mt-[4rpx] text-[20rpx] text-[#9ca3af]">
                                {{ overview.subtitle || "私信回复 · 自动加好友 · 自动拉群" }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center"
                        @click="visibleProxy = false">
                        <u-icon name="close" size="28" color="#6b7280"></u-icon>
                    </view>
                </view>
                <view class="flex gap-[16rpx] mt-[24rpx]">
                    <view class="flex-1 bg-success-light-9 rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-success">{{ summary.new_friend_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">新增好友</text>
                    </view>
                    <view class="flex-1 bg-primary-light-9 rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-primary">{{ summary.auto_reply_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">自动回复</text>
                    </view>
                    <view class="flex-1 bg-[#faf5ff] rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-[#9333ea]">{{ summary.auto_group_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">自动拉群</text>
                    </view>
                </view>
                <view class="flex gap-[8rpx] mt-[24rpx] p-[8rpx] bg-[#f3f4f6] rounded-[24rpx]">
                    <view
                        v-for="tab in tabs"
                        :key="tab.key"
                        class="flex-1 py-[14rpx] rounded-[20rpx] text-xs font-bold text-center transition-all"
                        :class="
                            activeTab === tab.key
                                ? 'bg-white text-[#1f2937] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.06)]'
                                : 'text-[#9ca3af]'
                        "
                        @click="switchTab(tab.key)">
                        {{ tab.name }}（{{ tab.count }}）
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <z-paging
                ref="pagingRef"
                v-model="lists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryTabList">
                <view class="p-[32rpx] pb-[40rpx] flex flex-col gap-[20rpx]">
                    <!-- 私信回复 -->
                    <template v-if="activeTab === 'private_reply'">
                        <view v-for="msg in lists" :key="msg.id" class="bg-white rounded-[32rpx] p-[28rpx]">
                            <view class="flex items-center justify-between mb-[20rpx]">
                                <text class="text-xs font-semibold text-[#1f2937]">
                                    {{ msg.nickname || msg.author_name || msg.friend_id }}
                                </text>
                                <text class="text-[20rpx] text-[#9ca3af]">{{
                                    msg.message_time || msg.create_time
                                }}</text>
                            </view>
                            <view
                                v-if="msg.message_content"
                                class="bg-[#f9fafb] rounded-[20rpx] px-[20rpx] py-[16rpx] mb-[16rpx]">
                                <text class="block text-[18rpx] text-[#9ca3af] mb-[6rpx]">用户</text>
                                <text class="text-xs text-[#4b5563] leading-[40rpx]">
                                    {{ msg.message_content }}
                                </text>
                            </view>
                            <view
                                v-if="msg.reply_content"
                                class="bg-success-light-9 rounded-[20rpx] px-[20rpx] py-[16rpx]">
                                <text class="block text-[18rpx] font-medium text-success mb-[6rpx]">AI 回复</text>
                                <text class="text-xs text-[#374151] leading-[40rpx]">
                                    {{ msg.reply_content }}
                                </text>
                            </view>
                        </view>
                    </template>

                    <!-- 新好友 -->
                    <template v-else-if="activeTab === 'new_friend'">
                        <view
                            v-for="req in lists"
                            :key="req.id"
                            class="bg-white rounded-[32rpx] p-[28rpx] flex flex-col gap-[16rpx]">
                            <view class="flex items-center justify-between">
                                <text class="text-xs font-semibold text-[#1f2937] line-clamp-1">
                                    {{ req.customer_name || req.reg_wechat || req.request_account }}
                                </text>
                                <text class="text-[20rpx] text-[#9ca3af] flex-shrink-0">{{ req.create_time }}</text>
                            </view>
                            <view class="flex flex-col gap-[12rpx]">
                                <view v-if="req.add_remark" class="flex items-start gap-[16rpx] text-xs">
                                    <text class="text-[#9ca3af] w-[120rpx] flex-shrink-0"> 加好友备注 </text>
                                    <text
                                        class="flex-1 text-[#374151] bg-[#f9fafb] rounded-[12rpx] px-[16rpx] py-[8rpx] leading-[36rpx] break-all">
                                        {{ req.add_remark }}
                                    </text>
                                </view>
                                <view v-if="req.request_remark" class="flex items-start gap-[16rpx] text-xs">
                                    <text class="text-[#9ca3af] w-[120rpx] flex-shrink-0"> 请求备注 </text>
                                    <text
                                        class="flex-1 text-[#374151] bg-[#f9fafb] rounded-[12rpx] px-[16rpx] py-[8rpx] leading-[36rpx] break-all">
                                        {{ req.request_remark }}
                                    </text>
                                </view>
                                <view v-if="req.request_account" class="flex items-center gap-[16rpx] text-xs">
                                    <text class="text-[#9ca3af] w-[120rpx] flex-shrink-0"> 请求账号 </text>
                                    <text class="text-success font-medium">
                                        {{ req.request_account }}
                                    </text>
                                </view>
                            </view>
                            <view
                                v-if="req.status_text || req.image"
                                class="flex items-center justify-between">
                                <text
                                    v-if="req.status_text"
                                    class="text-[20rpx] font-medium text-primary bg-primary-light-9 rounded-full px-[16rpx] py-[6rpx]">
                                    {{ req.status_text }}
                                </text>
                                <view v-else />
                                <view
                                    v-if="req.image"
                                    class="flex items-center gap-[6rpx] text-[22rpx] text-primary bg-primary-light-9 px-[16rpx] py-[6rpx] rounded-full"
                                    @click="emit('preview', req.image)">
                                    <image :src="imageIcon" mode="aspectFit" class="w-[22rpx] h-[22rpx]" />
                                    <text>查看截图</text>
                                </view>
                            </view>
                        </view>
                    </template>

                    <!-- 拉群记录 -->
                    <template v-else>
                        <view
                            v-for="grp in lists"
                            :key="grp.id"
                            class="bg-white rounded-[32rpx] p-[28rpx] flex flex-col gap-[20rpx]">
                            <view class="flex items-center justify-between">
                                <view class="flex items-center gap-[12rpx] min-w-0">
                                    <image
                                        :src="usersIcon"
                                        mode="aspectFit"
                                        class="w-[28rpx] h-[28rpx] flex-shrink-0" />
                                    <text class="text-sm font-bold text-[#1f2937] line-clamp-1">
                                        {{ grp.group_name }}
                                    </text>
                                </view>
                                <text class="text-[20rpx] text-[#9ca3af] flex-shrink-0">{{ grp.create_time }}</text>
                            </view>
                            <view
                                v-if="grp.result || grp.message_content"
                                class="bg-warning-light-9 rounded-[20rpx] px-[20rpx] py-[16rpx]">
                                <text class="block text-[18rpx] font-medium text-warning mb-[6rpx]">
                                    {{ grp.scene_text || "拉群" }} · {{ grp.status_text }}
                                </text>
                                <text v-if="grp.message_content" class="text-xs text-[#374151] leading-[40rpx]">
                                    {{ grp.message_content }}
                                </text>
                            </view>
                            <view class="flex flex-wrap gap-[12rpx]">
                                <text
                                    v-if="grp.sales_wechat"
                                    class="text-[20rpx] font-medium text-primary bg-primary-light-9 rounded-full px-[16rpx] py-[6rpx]">
                                    销售 · {{ grp.sales_wechat }}
                                </text>
                                <text
                                    v-if="grp.wechat_id"
                                    class="text-[20rpx] text-[#6b7280] bg-[#f3f4f6] rounded-full px-[16rpx] py-[6rpx]">
                                    本号 · {{ grp.wechat_id }}
                                </text>
                                <text
                                    v-if="grp.friend_id"
                                    class="text-[20rpx] text-[#6b7280] bg-[#f3f4f6] rounded-full px-[16rpx] py-[6rpx]">
                                    客户 · {{ grp.friend_id }}
                                </text>
                            </view>
                            <view v-if="grp.screenshot_url" class="flex justify-end">
                                <view
                                    class="flex items-center gap-[6rpx] text-[22rpx] text-primary bg-primary-light-9 px-[16rpx] py-[6rpx] rounded-full"
                                    @click="emit('preview', grp.screenshot_url)">
                                    <image :src="imageIcon" mode="aspectFit" class="w-[22rpx] h-[22rpx]" />
                                    <text>查看截图</text>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getWechatStatistics, getWechatMessageReply, getWechatCustomer, getWechatCreateGroup } from "@/api/person";
import config from "@/config";

type WechatTabKey = "private_reply" | "new_friend" | "group_record";
interface WechatTab {
    key: WechatTabKey;
    name: string;
    count: number;
}
interface WechatSummary {
    new_friend_count: number;
    auto_reply_count: number;
    auto_group_count: number;
}

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
}>();
const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "preview", url: string): void;
}>();

const visibleProxy = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const overview = reactive({ title: "", subtitle: "" });
const summary = reactive<WechatSummary>({
    new_friend_count: 0,
    auto_reply_count: 0,
    auto_group_count: 0,
});
const tabs = ref<WechatTab[]>([
    { key: "private_reply", name: "私信回复", count: 0 },
    { key: "new_friend", name: "新好友", count: 0 },
    { key: "group_record", name: "拉群记录", count: 0 },
]);
const activeTab = ref<WechatTabKey>("private_reply");
const lists = ref<any[]>([]);
const pagingRef = shallowRef<any>();

const loadStatistics = async () => {
    if (!props.personaId) return;
    try {
        const data: any = await getWechatStatistics({ persona_id: props.personaId });
        overview.title = data?.title || "";
        overview.subtitle = data?.subtitle || "";
        const s = data?.summary || {};
        summary.new_friend_count = Number(s.new_friend_count) || 0;
        summary.auto_reply_count = Number(s.auto_reply_count) || 0;
        summary.auto_group_count = Number(s.auto_group_count) || 0;
        if (Array.isArray(data?.tabs) && data.tabs.length) {
            tabs.value = data.tabs.map((t: any) => ({
                key: t.key,
                name: t.name,
                count: Number(t.count) || 0,
            }));
        }
    } catch (error) {
        console.warn("getWechatStatistics failed", error);
    }
};

const queryTabList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    const params = { persona_id: props.personaId, page_no, page_size };
    const fetcher =
        activeTab.value === "private_reply"
            ? getWechatMessageReply
            : activeTab.value === "new_friend"
            ? getWechatCustomer
            : getWechatCreateGroup;
    try {
        const data: any = await fetcher(params);
        const items = Array.isArray(data?.lists) ? data.lists : [];
        pagingRef.value?.complete(items);
    } catch (error) {
        pagingRef.value?.complete(false);
    }
};

const switchTab = (key: WechatTabKey) => {
    if (activeTab.value === key) return;
    activeTab.value = key;
    pagingRef.value?.reload();
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            activeTab.value = "private_reply";
            loadStatistics();
            nextTick(() => pagingRef.value?.reload());
        }
    },
);

const headerIcon = config.baseUrl + "static/images/mp/workflow_smartphone_green.svg";
const usersIcon = config.baseUrl + "static/images/mp/workflow_users_purple.svg";
const imageIcon = config.baseUrl + "static/images/mp/workflow_image.svg";
</script>
