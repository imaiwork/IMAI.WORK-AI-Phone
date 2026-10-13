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
                            class="w-[64rpx] h-[64rpx] rounded-[20rpx] bg-primary-light-9 flex items-center justify-center">
                            <image :src="headerIcon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <view>
                            <text class="block text-sm font-bold text-[#1f2937]">帮我回复社媒私信</text>
                            <text class="block mt-[4rpx] text-[20rpx] text-[#9ca3af]"
                                >抖音 · 快手 · 小红书 ·私信回复</text
                            >
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
                        <text class="block text-base font-bold text-success">{{ statistics.replied_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">已回复</text>
                    </view>
                    <view class="flex-1 bg-[#fefce8] rounded-[20rpx] py-[20rpx] text-center">
                        <text class="block text-base font-bold text-[#eab308]">{{ statistics.contact_count }}</text>
                        <text class="block text-[20rpx] text-[#9ca3af] mt-[4rpx]">联系方式</text>
                    </view>
                </view>
                <scroll-view v-if="tabs.length" scroll-x class="mt-[24rpx] whitespace-nowrap" show-scrollbar="false">
                    <view class="inline-flex gap-[16rpx]">
                        <view
                            v-for="plat in tabs"
                            :key="String(plat.platform_type)"
                            class="flex-shrink-0 rounded-full px-[28rpx] py-[12rpx] text-xs font-bold whitespace-nowrap"
                            :class="
                                activePlatform === plat.platform_type
                                    ? 'text-white bg-primary'
                                    : 'text-[#4b5563] bg-[#f3f4f6]'
                            "
                            @click="switchPlatform(plat.platform_type)">
                            <text>{{ plat.platform_name }}（{{ plat.count }}）</text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
        <template #content>
            <z-paging
                ref="pagingRef"
                v-model="messages"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryMessageList">
                <view class="p-[32rpx] pb-[40rpx] flex flex-col gap-[20rpx]">
                    <view
                        v-for="msg in messages"
                        :key="msg.id"
                        class="bg-white rounded-[32rpx] p-[28rpx] flex flex-col gap-[20rpx]">
                        <view class="flex items-center justify-between">
                            <view class="flex items-center gap-[12rpx] flex-1 min-w-0">
                                <text
                                    class="text-[18rpx] font-bold rounded-[6rpx] px-[12rpx] py-[2rpx] text-white flex-shrink-0"
                                    :style="{ background: platformColor(msg.platform_type) }">
                                    {{ msg.platform_name }}
                                </text>
                                <text class="text-xs font-medium text-[#374151] line-clamp-1">{{
                                    msg.author_name
                                }}</text>
                            </view>
                            <text class="text-[20rpx] text-[#9ca3af] flex-shrink-0">{{ msg.message_time }}</text>
                        </view>
                        <view class="bg-[#f9fafb] rounded-[20rpx] px-[20rpx] py-[16rpx]">
                            <text class="text-xs text-[#4b5563] leading-[40rpx]">{{ msg.message_content }}</text>
                            <view v-if="msg.contact_value" class="inline-flex items-center gap-[8rpx] mt-[8rpx]">
                                <text
                                    class="text-xs font-semibold bg-[#fef9c3] text-[#854d0e] rounded-[8rpx] px-[10rpx] py-[2rpx]">
                                    {{ msg.contact_value }}
                                </text>
                            </view>
                        </view>
                        <view v-if="msg.reply_content" class="bg-primary-light-9 rounded-[20rpx] px-[20rpx] py-[16rpx]">
                            <text class="block text-[20rpx] font-medium text-primary mb-[6rpx]">AI 回复</text>
                            <text class="text-xs text-[#374151] leading-[40rpx]">{{ msg.reply_content }}</text>
                        </view>
                        <view class="flex items-center justify-between">
                            <text class="text-[20rpx] text-[#9ca3af]">
                                {{ msg.reply_time ? `回复于 ${msg.reply_time}` : "" }}
                            </text>
                            <text
                                v-if="msg.is_reply === 1"
                                class="text-[20rpx] text-success bg-success-light-9 rounded-full px-[16rpx] py-[4rpx]">
                                ✓ {{ msg.status_text || "已发送" }}
                            </text>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getMessageTaskList, getMessageTaskStatistics } from "@/api/person";
import { AppTypeEnum } from "@/enums/appEnums";
import config from "@/config";

interface ChatSocialMessage {
    id: string;
    platform_type: number;
    platform_name: string;
    author_name: string;
    avatar: string;
    message_content: string;
    reply_content: string;
    is_reply: number;
    contact_value: string;
    contact_display: string;
    contact_type: string;
    status_text: string;
    message_time: string;
    reply_time: string;
}
interface ChatTab {
    platform_type: number | "all";
    platform_name: string;
    count: number;
}

const props = defineProps<{
    modelValue: boolean;
    personaId: string | number;
}>();
const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
}>();

const visibleProxy = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const MESSAGE_TASK_TYPE = 2;

// 联系方式标签：接口 contact_display 有时直接回填了 contact_value（导致重复展示），
// 此时退回按 contact_type 取类型名做标签
const CONTACT_TYPE_LABEL: Record<string, string> = {
    wechat: "微信",
    work_wechat: "企业微信",
    phone: "手机号",
    mobile: "手机号",
    qq: "QQ",
};
const contactLabel = (msg: ChatSocialMessage) => {
    if (msg.contact_display && msg.contact_display !== msg.contact_value) return msg.contact_display;
    return CONTACT_TYPE_LABEL[msg.contact_type] || "联系方式";
};

const tabs = ref<ChatTab[]>([]);
const activePlatform = ref<number | "all">("all");
const messages = ref<ChatSocialMessage[]>([]);
const statistics = reactive({ replied_count: 0, contact_count: 0 });
const pagingRef = shallowRef<any>();

const loadStatistics = async () => {
    if (!props.personaId) return;
    try {
        const data: any = await getMessageTaskStatistics({
            persona_id: props.personaId,
            message_task_type: MESSAGE_TASK_TYPE,
        });
        statistics.replied_count = Number(data?.replied_count) || 0;
        statistics.contact_count = Number(data?.contact_count) || 0;
        tabs.value = Array.isArray(data?.tabs) ? data.tabs : [];
    } catch (error) {
        console.warn("getMessageTaskStatistics(social) failed", error);
    }
};

const queryMessageList = async (page_no: number, page_size: number) => {
    if (!props.personaId) {
        pagingRef.value?.complete([]);
        return;
    }
    try {
        const platform_type = activePlatform.value === "all" ? "" : String(activePlatform.value);
        const data: any = await getMessageTaskList({
            persona_id: props.personaId,
            message_task_type: MESSAGE_TASK_TYPE,
            platform_type,
            page_no,
            page_size,
        });
        const lists = Array.isArray(data?.lists) ? data.lists : [];
        // 列表接口也返回 extend.tabs，覆盖以保证当前计数最新
        if (Array.isArray(data?.extend?.tabs) && data.extend.tabs.length) {
            tabs.value = data.extend.tabs;
        }
        pagingRef.value?.complete(lists);
    } catch (error) {
        console.warn("getMessageTaskList(social) failed", error);
        pagingRef.value?.complete(false);
    }
};

const switchPlatform = (key: number | "all") => {
    if (activePlatform.value === key) return;
    activePlatform.value = key;
    pagingRef.value?.reload();
};

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            activePlatform.value = "all";
            loadStatistics();
            nextTick(() => pagingRef.value?.reload());
        }
    },
);

const PLATFORM_COLOR: Record<number, string> = {
    [AppTypeEnum.SPH]: "#16a34a",
    [AppTypeEnum.XHS]: "#ef4444",
    [AppTypeEnum.DOUYIN]: "#000000",
    [AppTypeEnum.KUAISHOU]: "#f97316",
};
const platformColor = (p: number) => PLATFORM_COLOR[p] || "#6b7280";

const headerIcon = config.baseUrl + "static/images/mp/workflow_message_square_blue.svg";
</script>
