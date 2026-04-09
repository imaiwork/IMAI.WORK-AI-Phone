<template>
    <view class="auto-task-page">
        <view class="header-area relative z-10 pb-[60rpx]">
            <u-navbar
                title=""
                :border-bottom="false"
                :is-fixed="false"
                :background="{ background: 'transparent' }"
                :title-color="'#ffffff'"
                back-icon-color="#ffffff" />
            <view class="px-[36rpx]">
                <view class="mt-[8rpx]">
                    <text class="text-white text-[48rpx] font-extrabold tracking-tight block leading-tight">
                        24h 自动任务
                    </text>
                    <text class="text-[#ffffff]/50 text-[26rpx] mt-[10rpx] block">
                        共 {{ taskTimeConfig.length }} 个任务节点 · 全天候自动运行
                    </text>
                </view>

                <view class="flex gap-3 mt-[36rpx]">
                    <view class="stat-pill">
                        <text class="stat-num">{{ getPersonTypeText }}</text>
                        <text class="stat-label">当前人设类型</text>
                    </view>
                    <view class="stat-pill">
                        <text class="stat-num">{{ uniquePlatformCount }}</text>
                        <text class="stat-label">覆盖平台</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="content-sheet">
            <view class="flex justify-center pt-[20rpx] pb-[32rpx]">
                <view class="w-[80rpx] h-[8rpx] rounded-full bg-[#E5E7EB]" />
            </view>

            <view v-if="!loading" class="grow min-h-0">
                <z-paging
                    ref="pagingRef"
                    v-model="taskTimeConfig"
                    :auto="false"
                    :fixed="false"
                    :loading-more-enabled="false"
                    @query="queryList">
                    <view class="px-[36rpx] pb-[60rpx]">
                        <view class="relative">
                            <view
                                class="absolute left-[68rpx] top-[20rpx] bottom-[20rpx] w-[2rpx]"
                                style="background: linear-gradient(to bottom, #e0e7ff 0%, #c7d2fe 100%)" />

                            <view
                                v-for="(item, index) in taskTimeConfig"
                                :key="index"
                                class="flex items-start gap-[24rpx] mb-[28rpx]">
                                <view
                                    class="flex flex-col items-center flex-shrink-0 w-[140rpx] pt-[20rpx] relative z-10">
                                    <text class="text-[22rpx] font-bold text-[#6366F1] leading-none">{{
                                        item.time[0]
                                    }}</text>
                                    <view
                                        class="my-[10rpx] w-[20rpx] h-[20rpx] rounded-full border-[4rpx] border-[#6366F1] bg-white shadow-sm"
                                        :class="item.status > 0 ? 'bg-[#6366F1] border-[#6366F1]' : 'bg-white'" />
                                    <text class="text-[22rpx] text-[#9CA3AF] leading-none">{{ item.time[1] }}</text>
                                </view>

                                <view
                                    class="flex-1 rounded-[24rpx] overflow-hidden active:scale-[0.98] transition-transform"
                                    :class="item.disabled ? 'opacity-50' : ''">
                                    <view
                                        class="relative bg-white border border-solid border-[#F3F4F6] shadow-[0_2rpx_16rpx_rgba(0,0,0,0.04)]">
                                        <view
                                            class="absolute left-0 top-0 bottom-0 w-[8rpx] rounded-l-[24rpx]"
                                            :style="{ background: item.color }" />

                                        <view class="pl-[28rpx] pr-[24rpx] py-[28rpx]">
                                            <view class="flex items-center justify-between mb-[16rpx]">
                                                <text class="text-[30rpx] font-bold text-[#111827] flex-1 mr-3">{{
                                                    item.name
                                                }}</text>
                                                <view
                                                    v-if="!item.disabled"
                                                    class="flex-shrink-0 flex items-center gap-[8rpx] bg-[#FFF7ED] border border-solid border-[#FED7AA] rounded-[14rpx] px-[20rpx] py-[10rpx]"
                                                    @click.stop="handleDemo(item)">
                                                    <image
                                                        src="@/ai_modules/device/static/icons/window.svg"
                                                        class="w-[24rpx] h-[24rpx]" />
                                                    <text class="text-[#C2410C] text-[24rpx] font-semibold"
                                                        >立即执行</text
                                                    >
                                                </view>
                                                <view
                                                    v-else
                                                    class="flex-shrink-0 flex items-center gap-[8rpx] bg-[#F3F4F6] rounded-[14rpx] px-[20rpx] py-[10rpx]">
                                                    <text class="text-[#9CA3AF] text-[24rpx]">敬请期待</text>
                                                </view>
                                            </view>

                                            <view class="flex items-center justify-between">
                                                <view class="flex items-center gap-[8rpx]">
                                                    <view
                                                        v-for="(val, pIdx) in item.platform"
                                                        :key="pIdx"
                                                        class="w-[44rpx] h-[44rpx] rounded-full bg-[#F9FAFB] border border-solid border-[#F3F4F6] flex items-center justify-center overflow-hidden">
                                                        <image :src="val.activeIcon" class="w-[30rpx] h-[30rpx]" />
                                                    </view>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </z-paging>
            </view>

            <view v-else class="px-[36rpx] flex flex-col gap-[28rpx]">
                <view v-for="i in 5" :key="i" class="flex items-start gap-[24rpx]">
                    <view class="flex flex-col items-center w-[140rpx] pt-[20rpx] gap-[10rpx]">
                        <view class="h-[24rpx] w-[80rpx] bg-[#F3F4F6] rounded-full animate-pulse" />
                        <view class="w-[20rpx] h-[20rpx] rounded-full bg-[#F3F4F6] animate-pulse" />
                        <view class="h-[24rpx] w-[60rpx] bg-[#F3F4F6] rounded-full animate-pulse" />
                    </view>
                    <view class="flex-1 h-[140rpx] rounded-[24rpx] bg-[#F3F4F6] animate-pulse" />
                </view>
            </view>
        </view>

        <u-popup v-model="showChooseApp" mode="bottom" border-radius="40" :safe-area-inset-bottom="true">
            <view class="bg-white px-[40rpx] pt-[40rpx] pb-[60rpx]">
                <view class="flex justify-center mb-[40rpx]">
                    <view class="w-[80rpx] h-[8rpx] rounded-full bg-[#E5E7EB]" />
                </view>

                <text class="text-[36rpx] font-extrabold text-[#111827] block mb-[8rpx]">选择平台</text>
                <text class="text-[26rpx] text-[#9CA3AF] block mb-[40rpx]">请选择您要执行的平台</text>

                <view class="flex flex-col gap-[16rpx] mb-[48rpx]">
                    <view
                        v-for="platform in chooseAppPlatforms"
                        :key="platform.id"
                        class="flex items-center gap-[24rpx] p-[28rpx] rounded-[24rpx] border-[2rpx] border-solid transition-all"
                        :class="
                            selectedPlatform?.type === platform.type
                                ? 'border-[#6366F1] bg-[#EEF2FF]'
                                : 'border-[#F3F4F6] bg-[#F9FAFB]'
                        "
                        @click="selectPlatform(platform)">
                        <view
                            class="w-[88rpx] h-[88rpx] rounded-[20rpx] flex items-center justify-center"
                            :class="selectedPlatform?.type === platform.type ? 'bg-[#E0E7FF]' : 'bg-white'">
                            <image :src="platform.activeIcon" class="w-[52rpx] h-[52rpx]" />
                        </view>
                        <view class="flex-1">
                            <text class="text-[30rpx] font-bold text-[#111827] block">{{ platform.name }}</text>
                            <text v-if="platform.desc" class="text-[24rpx] text-[#9CA3AF] mt-[4rpx] block">{{
                                platform.desc
                            }}</text>
                        </view>
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center transition-all"
                            :class="selectedPlatform?.type === platform.type ? 'bg-[#6366F1]' : 'bg-[#F3F4F6]'">
                            <u-icon
                                name="checkmark"
                                :color="selectedPlatform?.type === platform.type ? '#fff' : '#D1D5DB'"
                                size="24rpx" />
                        </view>
                    </view>
                </view>

                <view class="flex gap-[20rpx]">
                    <view
                        class="flex-1 h-[96rpx] rounded-[24rpx] bg-[#F3F4F6] flex items-center justify-center active:opacity-70"
                        @click="showChooseApp = false">
                        <text class="text-[30rpx] font-bold text-[#6B7280]">取消</text>
                    </view>
                    <view
                        class="flex-[2] h-[96rpx] rounded-[24rpx] bg-[#6366F1] flex items-center justify-center shadow-[0_8rpx_24rpx_rgba(99,102,241,0.3)] active:opacity-90"
                        @click="confirmSelection">
                        <text class="text-[30rpx] font-bold text-white">确认选择</text>
                    </view>
                </view>
            </view>
        </u-popup>

        <confirm-dialog
            v-model="showConfirmDemoDialog"
            title="提示"
            content="当前暂无真实数据，将使用模拟数据进行演示。模拟数据仅用于展示效果，不会影响后续实际使用。是否确认进入演示模式?"
            @confirm="startDemoTask" />
    </view>
</template>

<script setup lang="ts">
import { getDeviceDetail, getAutoTaskDetail, checkRealTask, createDemoTask } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import { AppTypeEnum, PersonTypeEnum, PersonTypeMap } from "@/enums/appEnums";
import CircleIcon from "@/ai_modules/device/static/images/common/circle.png";
import SphIcon from "@/static/images/common/sph_s.png";

enum TaskKeyEnum {
    CLUES_SETTING = "clues_setting",
    TAKEOVER_SETTING = "takeover_setting",
    PUBLISH_SETTING = "publish_setting",
    TOUCH_SETTING = "touch_setting",
    ADD_WECHAT_SETTING = "add_wechat_setting",
    AUTO_ACCOUNT = "auto_account",
    CIRCLE_INTERACTION = "circle_like_reply_setting",
    CIRCLE_RELEASE = "wechat_circle_setting",
}

const { platform, initializePlatform } = useDevice();
const pagingRef = ref<any>(null);
const loading = ref(true);
const deviceCode = ref("");
const personType = ref<PersonTypeEnum>(PersonTypeEnum.PERSONAL_IP);
const deviceDetail = ref<any>({});
const autoTaskDetail = ref<any>({});
const showChooseApp = ref(false);
const showConfirmDemoDialog = ref(false);
const isCompleteConfig = ref(false);
const taskTimeConfig = ref<any[]>([]);

// ── 统计数据
const uniquePlatformCount = computed(() => {
    const types = new Set<number>();
    taskTimeConfig.value.forEach((t) => t.platform?.forEach((p: any) => p.type && types.add(p.type)));
    return types.size;
});
const getPersonTypeText = computed(() => {
    return PersonTypeMap[personType.value];
});

const taskMap: any = {
    keyword_customer: {
        key: TaskKeyEnum.CLUES_SETTING,
        name: "关键词获客",
        status: 0,
        platform: [platform.value[AppTypeEnum.SPH]],
        color: "linear-gradient(135deg,#A5B4FC,#818CF8)",
    },
    private_message_takeover: {
        key: TaskKeyEnum.TAKEOVER_SETTING,
        name: "私信接管",
        status: 0,
        platform: [
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
        ],
        color: "linear-gradient(135deg,#93C5FD,#60A5FA)",
    },
    social_media_content: {
        key: TaskKeyEnum.PUBLISH_SETTING,
        name: "社媒平台发布内容",
        status: 0,
        platform: [
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
            { activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH },
        ],
        color: "linear-gradient(135deg,#6EE7B7,#34D399)",
    },
    circle_release: {
        key: TaskKeyEnum.CIRCLE_RELEASE,
        name: "朋友圈发布",
        status: 0,
        platform: [{ activeIcon: CircleIcon, type: AppTypeEnum.WECHAT }],
        color: "linear-gradient(135deg,#6EE7B7,#34D399)",
    },
    circle_interaction: {
        key: TaskKeyEnum.CIRCLE_INTERACTION,
        name: "朋友圈互动",
        status: 4,
        platform: [{ activeIcon: CircleIcon, type: AppTypeEnum.WECHAT }],
        color: "linear-gradient(135deg,#C4B5FD,#A78BFA)",
    },
    comment_area_customer: {
        key: TaskKeyEnum.TOUCH_SETTING,
        name: "评论区获客",
        status: 0,
        platform: [
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.SPH],
        ],
        color: "linear-gradient(135deg,#FCD34D,#F59E0B)",
    },
    auto_add_wechat: {
        key: TaskKeyEnum.ADD_WECHAT_SETTING,
        name: "自动加微",
        status: 0,
        platform: [platform.value[AppTypeEnum.WECHAT]],
        color: "linear-gradient(135deg,#C4B5FD,#A78BFA)",
    },
    auto_account: {
        key: TaskKeyEnum.AUTO_ACCOUNT,
        name: "自动养号",
        status: 3,
        platform: [
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
        ],
        color: "linear-gradient(135deg,#FCA5A5,#F87171)",
    },
};

const buildTaskTimeConfig = (): any[] => {
    const tm = taskMap;
    const p = platform.value;

    if (personType.value === PersonTypeEnum.PERSONAL_IP) {
        return [
            {
                ...tm.private_message_takeover,
                time: ["06:00", "07:00"],
                platform: [p[AppTypeEnum.XHS], p[AppTypeEnum.DOUYIN], p[AppTypeEnum.KUAISHOU], p[AppTypeEnum.WECHAT]],
            },
            { ...tm.auto_add_wechat, time: ["07:00", "07:15"] },
            { ...tm.private_message_takeover, time: ["07:15", "07:30"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.circle_release, time: ["07:30", "08:00"] },
            {
                ...tm.social_media_content,
                time: ["08:00", "08:30"],
                platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS], p[AppTypeEnum.KUAISHOU]],
            },
            { ...tm.comment_area_customer, time: ["08:30", "09:00"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["09:00", "09:30"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.auto_add_wechat, time: ["09:30", "09:45"] },
            { ...tm.comment_area_customer, time: ["09:45", "10:30"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["10:30", "11:00"], platform: [p[AppTypeEnum.WECHAT]] },
            {
                ...tm.auto_account,
                time: ["11:00", "12:30"],
                platform: [p[AppTypeEnum.KUAISHOU], p[AppTypeEnum.XHS], p[AppTypeEnum.DOUYIN]],
            },
            { ...tm.auto_add_wechat, time: ["12:30", "12:45"] },
            { ...tm.comment_area_customer, time: ["12:45", "13:15"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["13:15", "13:30"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.comment_area_customer, time: ["13:30", "14:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["14:00", "14:30"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.auto_add_wechat, time: ["14:30", "14:45"] },
            { ...tm.auto_account, time: ["14:45", "17:00"], platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS]] },
            {
                ...tm.social_media_content,
                time: ["17:00", "17:30"],
                platform: [
                    { activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH },
                    { activeIcon: CircleIcon, type: AppTypeEnum.WECHAT },
                ],
            },
            {
                ...tm.private_message_takeover,
                time: ["17:30", "18:15"],
                platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS], p[AppTypeEnum.KUAISHOU]],
            },
            { ...tm.auto_add_wechat, time: ["18:15", "18:30"] },
            { ...tm.comment_area_customer, time: ["18:30", "19:15"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["19:15", "19:30"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.comment_area_customer, time: ["19:30", "20:00"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["20:00", "20:15"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.auto_add_wechat, time: ["20:15", "20:30"] },
            { ...tm.auto_account, time: ["20:30", "21:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            {
                ...tm.private_message_takeover,
                time: ["21:00", "22:00"],
                platform: [p[AppTypeEnum.XHS], p[AppTypeEnum.KUAISHOU]],
            },
            { ...tm.circle_interaction, time: ["22:00", "22:30"] },
            { ...tm.auto_add_wechat, time: ["22:30", "22:45"] },
            { ...tm.private_message_takeover, time: ["22:45", "23:30"], platform: [p[AppTypeEnum.WECHAT]] },
        ];
    }
    if (personType.value === PersonTypeEnum.LOCAL_BUSINESS) {
        return [
            { ...tm.auto_account, time: ["08:00", "09:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            {
                ...tm.social_media_content,
                time: ["09:00", "09:30"],
                platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS]],
            },
            { ...tm.private_message_takeover, time: ["09:30", "10:00"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.comment_area_customer, time: ["10:00", "11:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["11:00", "11:15"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.auto_add_wechat, time: ["11:15", "11:30"] },
            { ...tm.comment_area_customer, time: ["11:30", "12:00"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["12:00", "12:15"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.circle_interaction, time: ["12:15", "12:30"] },
            { ...tm.private_message_takeover, time: ["12:30", "13:15"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.circle_release, time: ["13:15", "13:30"] },
            {
                ...tm.private_message_takeover,
                time: ["13:30", "14:00"],
                platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS]],
            },
            { ...tm.private_message_takeover, time: ["14:00", "14:30"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.auto_add_wechat, time: ["14:30", "14:45"] },
            { ...tm.comment_area_customer, time: ["14:45", "15:30"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["15:30", "15:45"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.comment_area_customer, time: ["15:45", "16:45"], platform: [p[AppTypeEnum.XHS]] },
            {
                ...tm.social_media_content,
                time: ["16:45", "17:00"],
                platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS]],
            },
            {
                ...tm.private_message_takeover,
                time: ["17:00", "17:45"],
                platform: [p[AppTypeEnum.DOUYIN], p[AppTypeEnum.XHS], p[AppTypeEnum.WECHAT]],
            },
            { ...tm.auto_add_wechat, time: ["17:45", "18:00"] },
            { ...tm.comment_area_customer, time: ["18:00", "18:30"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["18:30", "18:45"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.auto_add_wechat, time: ["18:45", "18:50"] },
            { ...tm.comment_area_customer, time: ["18:50", "19:30"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["19:30", "20:00"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.auto_account, time: ["20:00", "21:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["21:00", "21:30"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["21:30", "22:00"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["22:00", "22:30"], platform: [p[AppTypeEnum.KUAISHOU]] },
            { ...tm.auto_add_wechat, time: ["22:30", "22:45"] },
        ];
    }
    if (personType.value === PersonTypeEnum.BUSINESS_SERVICE) {
        return [
            { ...tm.auto_account, time: ["08:00", "08:30"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.auto_account, time: ["08:30", "09:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            {
                ...tm.social_media_content,
                time: ["09:00", "09:30"],
                platform: [
                    { activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH },
                    p[AppTypeEnum.DOUYIN],
                    p[AppTypeEnum.XHS],
                ],
            },
            { ...tm.private_message_takeover, time: ["09:30", "09:45"], platform: [p[AppTypeEnum.WECHAT]] },
            {
                ...tm.keyword_customer,
                time: ["09:45", "10:45"],
                platform: [{ activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH }],
            },
            { ...tm.auto_add_wechat, time: ["10:45", "11:00"] },
            { ...tm.comment_area_customer, time: ["11:00", "11:45"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["11:45", "12:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.circle_interaction, time: ["12:00", "12:30"] },
            { ...tm.comment_area_customer, time: ["12:30", "13:00"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["13:00", "13:15"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.auto_add_wechat, time: ["13:15", "13:30"] },
            {
                ...tm.keyword_customer,
                time: ["13:30", "14:30"],
                platform: [{ activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH }],
            },
            { ...tm.private_message_takeover, time: ["14:30", "14:45"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.comment_area_customer, time: ["14:45", "15:30"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["15:30", "15:45"], platform: [p[AppTypeEnum.DOUYIN]] },
            {
                ...tm.keyword_customer,
                time: ["15:45", "16:30"],
                platform: [{ activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH }],
            },
            { ...tm.private_message_takeover, time: ["16:30", "16:45"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.auto_add_wechat, time: ["16:45", "17:00"] },
            { ...tm.auto_account, time: ["17:00", "17:30"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["17:30", "17:45"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["17:45", "18:00"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.circle_release, time: ["18:00", "18:15"] },
            { ...tm.circle_interaction, time: ["18:15", "18:30"] },
            {
                ...tm.keyword_customer,
                time: ["18:30", "19:30"],
                platform: [{ activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH }],
            },
            { ...tm.auto_account, time: ["19:30", "20:00"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["20:00", "20:15"], platform: [p[AppTypeEnum.DOUYIN]] },
            { ...tm.private_message_takeover, time: ["20:15", "20:30"], platform: [p[AppTypeEnum.XHS]] },
            { ...tm.private_message_takeover, time: ["20:30", "20:45"], platform: [p[AppTypeEnum.WECHAT]] },
            { ...tm.auto_add_wechat, time: ["20:45", "21:00"] },
            { ...tm.private_message_takeover, time: ["21:00", "23:00"], platform: [p[AppTypeEnum.WECHAT]] },
        ];
    }
    return [];
};

const queryList = () => {
    getTaskConfig();
    pagingRef.value?.complete(taskTimeConfig.value);
};

const getDetail = async () => {
    try {
        const res = await getDeviceDetail({ device_code: deviceCode.value });
        deviceDetail.value = res;
        await getTaskConfig();
    } finally {
        loading.value = false;
        uni.hideLoading();
    }
};

const getTaskConfig = async () => {
    const data = await getAutoTaskDetail({ device_code: deviceCode.value });
    const { auto_setting, is_empty } = data;
    autoTaskDetail.value = data;
    isCompleteConfig.value = is_empty === 0;
    if (isCompleteConfig.value) initializePlatform(deviceDetail.value.accounts);
    taskTimeConfig.value = buildTaskTimeConfig();
    taskTimeConfig.value.forEach((item: any) => {
        if (auto_setting[item.key]) item.status = auto_setting[item.key].is_config;
    });
};

const chooseAppPlatforms = ref<any[]>([]);
const selectedPlatform = ref<any>({});
const currTaskKey = ref<any>(null);
const demoParams = ref<any>({ device_code: deviceCode.value, account_type: null, source: null });

const selectPlatform = (p: any) => {
    selectedPlatform.value = p;
};

const handleDemo = (item: any) => {
    uni.showModal({
        title: "提示",
        content: "检测有任务在执行中，演示任务会中断当前任务，是否确定继续演示任务？",
        success: (res) => {
            if (!res.confirm) return;
            demoParams.value.device_code = deviceCode.value;
            currTaskKey.value = item.key;
            switch (item.key) {
                case TaskKeyEnum.CLUES_SETTING:
                    demoParams.value.account_type = 1;
                    demoParams.value.source = 3;
                    handleCheckRealTask();
                    break;
                case TaskKeyEnum.TAKEOVER_SETTING:
                case TaskKeyEnum.PUBLISH_SETTING:
                case TaskKeyEnum.AUTO_ACCOUNT:
                    chooseAppPlatforms.value = item.platform;
                    selectedPlatform.value = chooseAppPlatforms.value[0];
                    showChooseApp.value = true;
                    break;
                case TaskKeyEnum.TOUCH_SETTING:
                    demoParams.value.account_type = item.platform[0].type;
                    demoParams.value.source = 5;
                    handleCheckRealTask();
                    break;
                case TaskKeyEnum.ADD_WECHAT_SETTING:
                    demoParams.value.account_type = item.platform[0].type;
                    demoParams.value.source = 7;
                    handleCheckRealTask();
                    break;
                case TaskKeyEnum.CIRCLE_RELEASE:
                    demoParams.value.account_type = item.platform[0].type;
                    demoParams.value.source = 9;
                    handleCheckRealTask();
                    break;
                case TaskKeyEnum.CIRCLE_INTERACTION:
                    demoParams.value.account_type = item.platform[0].type;
                    demoParams.value.source = 10;
                    handleCheckRealTask();
                    break;
            }
        },
    });
};

const handleCheckRealTask = async () => {
    uni.showLoading({ title: "检查任务中...", mask: true });
    try {
        const res = await checkRealTask(demoParams.value);
        uni.hideLoading();
        if (res.is_demo_data == 1) {
            showConfirmDemoDialog.value = true;
            return;
        }
        startDemoTask();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const confirmSelection = () => {
    showChooseApp.value = false;
    const { type } = selectedPlatform.value;
    switch (currTaskKey.value) {
        case TaskKeyEnum.TAKEOVER_SETTING:
            demoParams.value.account_type = type;
            demoParams.value.source = 4;
            break;
        case TaskKeyEnum.PUBLISH_SETTING:
            demoParams.value.account_type = type;
            demoParams.value.source = type == AppTypeEnum.XHS ? 1 : 2;
            break;
        case TaskKeyEnum.AUTO_ACCOUNT:
            demoParams.value.account_type = type;
            demoParams.value.source = 8;
            break;
    }
    handleCheckRealTask();
};

const startDemoTask = async () => {
    uni.showLoading({ title: "创建中...", mask: true });
    try {
        await createDemoTask(demoParams.value);
        uni.hideLoading();
        uni.showToast({ title: "创建成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

onShow(() => {
    getDetail();
});
onLoad((options: any) => {
    deviceCode.value = options.device_code;
    personType.value = Number(options.person_type) || PersonTypeEnum.PERSONAL_IP;
});
onUnload(() => {
    close();
});
</script>

<style scoped lang="scss">
.auto-task-page {
    background: #0f0c29;
    @apply h-screen flex flex-col overflow-hidden;
}

.header-area {
    background: linear-gradient(145deg, #1a1a3e 0%, #2d1b69 50%, #1e3a5f 100%);
    flex-shrink: 0;
}

.content-sheet {
    flex: 1;
    min-height: 0;
    background: #f8f9fc;
    border-top-left-radius: 40rpx;
    border-top-right-radius: 40rpx;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.stat-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(255, 255, 255, 0.08);
    border: 1rpx solid rgba(255, 255, 255, 0.12);
    border-radius: 20rpx;
    padding: 16rpx 28rpx;
    backdrop-filter: blur(10px);
    gap: 4rpx;

    .stat-num {
        font-size: 36rpx;
        font-weight: 800;
        color: #ffffff;
        line-height: 1;
    }

    .stat-label {
        font-size: 20rpx;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 500;
    }
}
</style>
