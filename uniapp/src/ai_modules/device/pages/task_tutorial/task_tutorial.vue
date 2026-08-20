<template>
    <view class="h-screen flex flex-col overflow-hidden bg-[#F4F6FB]">
        <view class="flex-shrink-0 bg-[#F4F6FB]">
            <u-navbar title="" :border-bottom="false" :is-fixed="false" :background="{ background: 'transparent' }" />

            <view class="px-[32rpx] pb-[24rpx]">
                <view class="mb-[24rpx]">
                    <view class="flex items-center gap-x-[6rpx] mb-[8rpx]">
                        <view class="w-[8rpx] h-[40rpx] bg-primary rounded-full"></view>
                        <text class="text-[32rpx] font-extrabold text-[#212121] tracking-tight">
                            {{ deviceDetail.auto_type == 1 ? "24h 自动任务" : "手动任务" }}
                        </text>
                    </view>
                    <text class="text-[24rpx] text-[#676767] ml-[20rpx]">
                        共 {{ taskTimeConfig.length }} 个任务节点 ·
                        {{ deviceDetail.auto_type == 1 ? "AI 全自动执行" : "按设定时间自动执行" }}
                    </text>
                </view>

                <view class="flex gap-[16rpx]">
                    <view
                        class="flex-1 bg-white rounded-[24rpx] px-[28rpx] py-[20rpx] shadow-sm border border-solid border-[#f9f9f9] flex items-center gap-x-[16rpx]">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#EEF4FF] flex items-center justify-center flex-shrink-0">
                            <u-icon name="account" color="primary" size="28" />
                        </view>
                        <view>
                            <text class="text-[32rpx] font-extrabold text-[#212121] block leading-none">{{
                                getPersonTypeText
                            }}</text>
                            <text class="text-[20rpx] text-[#b4b4b4] mt-[6rpx] block">当前人设类型</text>
                        </view>
                    </view>
                    <view
                        class="flex-1 bg-white rounded-[24rpx] px-[28rpx] py-[20rpx] shadow-sm border border-solid border-[#f9f9f9] flex items-center gap-x-[16rpx]">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#EEF4FF] flex items-center justify-center flex-shrink-0">
                            <u-icon name="grid" color="#0065fb" size="28" />
                        </view>
                        <view>
                            <text class="text-[32rpx] font-extrabold text-primary block leading-none">{{
                                uniquePlatformCount
                            }}</text>
                            <text class="text-[20rpx] text-[#b4b4b4] mt-[6rpx] block">覆盖平台</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="flex-1 min-h-0 flex flex-col overflow-hidden">
            <view v-if="!loading" class="grow min-h-0">
                <z-paging
                    ref="pagingRef"
                    v-model="taskTimeConfig"
                    :fixed="false"
                    :loading-more-enabled="false"
                    @query="queryList">
                    <view class="px-[32rpx] pb-[60rpx] pt-[8rpx]">
                        <view class="relative">
                            <view
                                class="absolute left-[68rpx] top-[20rpx] bottom-[20rpx] w-[2rpx]"
                                style="background: linear-gradient(135deg, #60a5fa, #0065fb)" />

                            <view
                                v-for="(item, index) in taskTimeConfig"
                                :key="index"
                                class="flex items-start gap-[24rpx] mb-[24rpx]">
                                <view
                                    class="flex flex-col items-center flex-shrink-0 w-[140rpx] pt-[20rpx] relative z-10">
                                    <text class="text-[22rpx] font-bold leading-none text-primary">{{
                                        item.time[0]
                                    }}</text>
                                    <view
                                        class="my-[10rpx] w-[20rpx] h-[20rpx] rounded-full border-[4rpx] border-solid border-primary"
                                        :class="
                                            item.status === 1
                                                ? 'bg-primary shadow-[0_0_0_4rpx_rgba(59,130,246,0.15)]'
                                                : 'bg-white'
                                        " />
                                    <text class="text-[22rpx] text-gray-400 leading-none">{{ item.time[1] }}</text>
                                </view>

                                <view
                                    class="flex-1 bg-white rounded-[24rpx] overflow-hidden shadow-sm border border-solid border-[#f9f9f9] transition-all"
                                    :class="[item.disabled ? 'opacity-50' : '', item.status === 0 ? 'opacity-55' : '']">
                                    <view class="relative pl-[20rpx]">
                                        <view
                                            class="absolute left-0 top-0 bottom-0 w-[8rpx] rounded-l-[24rpx]"
                                            :style="{
                                                background: item.status === 1 ? item.color : '#E2E8F0',
                                            }" />

                                        <view class="pl-[16rpx] pr-[24rpx] pt-[24rpx] pb-[20rpx]">
                                            <view class="flex items-center justify-between mb-[16rpx]">
                                                <text
                                                    class="text-[28rpx] font-bold flex-1 mr-3"
                                                    :style="{
                                                        color: item.status === 1 ? '#1E293B' : '#94A3B8',
                                                    }">
                                                    {{ item.name }}
                                                </text>
                                                <template v-if="deviceDetail.auto_type == 1">
                                                    <view v-if="!item.disabled" class="flex-shrink-0" @click.stop>
                                                        <u-switch
                                                            v-model="item.status"
                                                            :loading="item._toggling"
                                                            :disabled="item._toggling"
                                                            inactive-color="#CBD5E1"
                                                            :active-value="1"
                                                            :inactive-value="0"
                                                            size="32"
                                                            @change="toggleTaskStatus(item)" />
                                                    </view>
                                                    <view
                                                        v-else
                                                        class="flex-shrink-0 bg-[#F4F6FB] rounded-full px-[20rpx] py-[8rpx]">
                                                        <text class="text-[#94A3B8] text-[20rpx]">敬请期待</text>
                                                    </view>
                                                </template>
                                            </view>

                                            <view class="flex items-center justify-between">
                                                <view class="flex items-center gap-[8rpx]">
                                                    <view
                                                        v-for="(val, pIdx) in item.platform"
                                                        :key="pIdx"
                                                        class="w-[44rpx] h-[44rpx] rounded-full bg-[#F4F6FB] border border-solid border-[#DBEAFE] flex items-center justify-center overflow-hidden"
                                                        :style="{
                                                            filter:
                                                                item.status === 0
                                                                    ? 'grayscale(1) opacity(0.5)'
                                                                    : 'none',
                                                        }">
                                                        <image :src="val.activeIcon" class="w-[30rpx] h-[30rpx]" />
                                                    </view>
                                                </view>

                                                <view
                                                    v-if="
                                                        !item.disabled &&
                                                        item.status === 1 &&
                                                        deviceDetail.auto_type == 1
                                                    "
                                                    class="flex-shrink-0 flex items-center gap-[8rpx] bg-primary rounded-full px-[20rpx] py-[10rpx] shadow-sm active:opacity-80"
                                                    @click.stop="handleDemo(item)">
                                                    <image
                                                        src="@/ai_modules/device/static/icons/window.svg"
                                                        class="w-[24rpx] h-[24rpx]" />
                                                    <text class="text-white text-[22rpx] font-semibold">立即执行</text>
                                                </view>

                                                <view
                                                    v-else-if="!item.disabled && item.status === 0"
                                                    class="flex-shrink-0 bg-[#F4F6FB] rounded-full px-[20rpx] py-[10rpx]">
                                                    <text class="text-[#94A3B8] text-[22rpx]">已暂停</text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>

            <view v-else class="px-[32rpx] pt-[16rpx] flex flex-col gap-[28rpx]">
                <view v-for="i in 5" :key="i" class="flex items-start gap-[24rpx]">
                    <view class="flex flex-col items-center w-[140rpx] pt-[20rpx] gap-[10rpx]">
                        <view class="h-[24rpx] w-[80rpx] rounded-full animate-pulse bg-[#DBEAFE]" />
                        <view class="w-[20rpx] h-[20rpx] rounded-full animate-pulse bg-[#DBEAFE]" />
                        <view class="h-[24rpx] w-[60rpx] rounded-full animate-pulse bg-[#DBEAFE]" />
                    </view>
                    <view class="flex-1 h-[140rpx] rounded-[24rpx] animate-pulse bg-[#DBEAFE]" />
                </view>
            </view>
        </view>

        <u-popup v-model="showChooseApp" mode="bottom" border-radius="40" :safe-area-inset-bottom="true">
            <view class="bg-[#F4F6FB] px-[32rpx] pt-[32rpx] pb-[48rpx]">
                <view class="flex justify-center mb-[32rpx]">
                    <view class="w-[80rpx] h-[8rpx] rounded-full bg-gray-200" />
                </view>

                <view class="flex items-center gap-x-[12rpx] mb-[8rpx]">
                    <view class="w-[8rpx] h-[36rpx] bg-primary rounded-full"></view>
                    <text class="text-[32rpx] font-bold text-[#212121]">选择平台</text>
                </view>
                <text class="text-[24rpx] text-[#676767] block mb-[32rpx] ml-[20rpx]">请选择您要执行的平台</text>

                <view class="flex flex-col gap-[16rpx] mb-[40rpx]">
                    <view
                        v-for="platform in chooseAppPlatforms"
                        :key="platform.id"
                        class="bg-white rounded-full px-[28rpx] py-[20rpx] flex items-center gap-x-[20rpx] shadow-sm border transition-all"
                        :class="
                            selectedPlatform?.type === platform.type
                                ? 'border-primary bg-[#EEF4FF] shadow-[0_2rpx_12rpx_rgba(59,130,246,0.15)]'
                                : 'border-[#f9f9f9]'
                        "
                        @click="selectPlatform(platform)">
                        <image :src="platform.activeIcon" class="w-[48rpx] h-[48rpx] rounded-[14rpx] flex-shrink-0" />
                        <text
                            class="flex-1 text-[28rpx] font-semibold"
                            :class="selectedPlatform?.type === platform.type ? 'text-primary' : 'text-[#424242]'">
                            {{ platform.name }}
                        </text>
                        <view
                            class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center flex-shrink-0"
                            :class="selectedPlatform?.type === platform.type ? 'bg-primary' : 'bg-[#f9f9f9]'">
                            <u-icon
                                name="checkmark"
                                :color="selectedPlatform?.type === platform.type ? '#ffffff' : '#CBD5E1'"
                                size="22" />
                        </view>
                    </view>
                </view>

                <view class="flex gap-[16rpx]">
                    <view
                        class="flex-1 h-[96rpx] rounded-full bg-white border border-solid border-[#f9f9f9] flex items-center justify-center shadow-sm active:opacity-70"
                        @click="showChooseApp = false">
                        <text class="text-[28rpx] font-bold text-[#676767]">取消</text>
                    </view>
                    <view
                        class="flex-[2] h-[96rpx] rounded-full bg-primary flex items-center justify-center shadow-md active:opacity-90"
                        @click="confirmSelection">
                        <text class="text-[28rpx] font-bold text-white">确认选择</text>
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
import {
    getDeviceDetail,
    getAutoTaskDetail,
    checkRealTask,
    createDemoTask,
    getAutoTaskExecutionPlan,
    updateAutoTaskExecutionPlan,
} from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import { AppTypeEnum, PersonTypeEnum, PersonTypeMap } from "@/enums/appEnums";
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

enum TaskCategoryEnum {
    KEYWORD_CUSTOMER = "关键词获客",
    PRIVATE_MSG = "私信接管",
    PRIVATE_MSG_GROUP = "私信接管（拉群）",
    VIDEO_PUBLISH = "视频发布",
    CIRCLE_RELEASE = "朋友圈发布",
    CIRCLE_INTERACTION = "朋友圈互动",
    COMMENT_LIKE = "评论点赞",
    COMMENT_TAKEOVER = "评论接管",
    COMMENT_LIKE_ONLY = "评论接管（仅点赞）",
    TRACE_CUSTOMER = "留痕获客",
    INTERCEPT_CUSTOMER = "截流获客",
    AUTO_ADD_FRIEND = "自动加好友",
    AUTO_ACCOUNT = "自动养号",
}

const CATEGORY_KEY_MAP: Record<TaskCategoryEnum, TaskKeyEnum> = {
    [TaskCategoryEnum.KEYWORD_CUSTOMER]: TaskKeyEnum.CLUES_SETTING,
    [TaskCategoryEnum.PRIVATE_MSG]: TaskKeyEnum.TAKEOVER_SETTING,
    [TaskCategoryEnum.PRIVATE_MSG_GROUP]: TaskKeyEnum.TAKEOVER_SETTING,
    [TaskCategoryEnum.VIDEO_PUBLISH]: TaskKeyEnum.PUBLISH_SETTING,
    [TaskCategoryEnum.CIRCLE_RELEASE]: TaskKeyEnum.CIRCLE_RELEASE,
    [TaskCategoryEnum.CIRCLE_INTERACTION]: TaskKeyEnum.CIRCLE_INTERACTION,
    [TaskCategoryEnum.COMMENT_LIKE]: TaskKeyEnum.CIRCLE_INTERACTION,
    [TaskCategoryEnum.COMMENT_TAKEOVER]: TaskKeyEnum.TAKEOVER_SETTING,
    [TaskCategoryEnum.COMMENT_LIKE_ONLY]: TaskKeyEnum.CIRCLE_INTERACTION,
    [TaskCategoryEnum.TRACE_CUSTOMER]: TaskKeyEnum.TOUCH_SETTING,
    [TaskCategoryEnum.INTERCEPT_CUSTOMER]: TaskKeyEnum.TOUCH_SETTING,
    [TaskCategoryEnum.AUTO_ADD_FRIEND]: TaskKeyEnum.ADD_WECHAT_SETTING,
    [TaskCategoryEnum.AUTO_ACCOUNT]: TaskKeyEnum.AUTO_ACCOUNT,
};

const CATEGORY_COLOR_MAP: Record<TaskCategoryEnum, string> = {
    [TaskCategoryEnum.KEYWORD_CUSTOMER]: "linear-gradient(135deg, #60a5fa, #0065fb)",
    [TaskCategoryEnum.PRIVATE_MSG]: "linear-gradient(135deg, #38bdf8, #0284c7)",
    [TaskCategoryEnum.PRIVATE_MSG_GROUP]: "linear-gradient(135deg, #38bdf8, #0284c7)",
    [TaskCategoryEnum.VIDEO_PUBLISH]: "linear-gradient(135deg, #34d399, #059669)",
    [TaskCategoryEnum.CIRCLE_RELEASE]: "linear-gradient(135deg, #6ee7b7, #10b981)",
    [TaskCategoryEnum.CIRCLE_INTERACTION]: "linear-gradient(135deg, #a78bfa, #7c3aed)",
    [TaskCategoryEnum.COMMENT_LIKE]: "linear-gradient(135deg, #c4b5fd, #8b5cf6)",
    [TaskCategoryEnum.COMMENT_TAKEOVER]: "linear-gradient(135deg, #60a5fa, #0065fb)",
    [TaskCategoryEnum.COMMENT_LIKE_ONLY]: "linear-gradient(135deg, #c4b5fd, #8b5cf6)",
    [TaskCategoryEnum.TRACE_CUSTOMER]: "linear-gradient(135deg, #fbbf24, #f59e0b)",
    [TaskCategoryEnum.INTERCEPT_CUSTOMER]: "linear-gradient(135deg, #fb923c, #ea580c)",
    [TaskCategoryEnum.AUTO_ADD_FRIEND]: "linear-gradient(135deg, #22d3ee, #0891b2)",
    [TaskCategoryEnum.AUTO_ACCOUNT]: "linear-gradient(135deg, #fb7185, #e11d48)",
};

const DISABLED_CATEGORIES = new Set<TaskCategoryEnum>([]);

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

const uniquePlatformCount = computed(() => {
    const types = new Set<number>();
    taskTimeConfig.value.forEach((t) => t.platform?.forEach((p: any) => p.type && types.add(p.type)));
    return types.size;
});

const getPersonTypeText = computed(() => PersonTypeMap[personType.value] || "-");

const mapPlatformIds = (platformIds: number[]): any[] => {
    return platformIds.map((id) => {
        switch (id) {
            case AppTypeEnum.WECHAT:
                return platform.value[AppTypeEnum.WECHAT];
            case AppTypeEnum.XHS:
                return platform.value[AppTypeEnum.XHS];
            case AppTypeEnum.DOUYIN:
                return platform.value[AppTypeEnum.DOUYIN];
            case AppTypeEnum.KUAISHOU:
                return platform.value[AppTypeEnum.KUAISHOU];
            case AppTypeEnum.SPH:
                return { activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH };
            default:
                // @ts-ignore
                return platform.value[id] ?? { activeIcon: "", name: String(id), type: id };
        }
    });
};

const transformApiData = (apiList: any[]): any[] => {
    return apiList.map((item) => {
        const category = item.task_category as TaskCategoryEnum;
        return {
            id: item.id,
            persona_type: item.persona_type,
            name: category,
            key: CATEGORY_KEY_MAP[category] ?? TaskKeyEnum.TAKEOVER_SETTING,
            color: CATEGORY_COLOR_MAP[category] ?? "linear-gradient(135deg, #60a5fa, #0065fb)",
            status: item.status ?? 0,
            time: item.time ?? [item.start_time, item.end_time],
            platform: mapPlatformIds((item.platform || []).map((item: any) => Number(item.account_type)) ?? []),
            disabled: DISABLED_CATEGORIES.has(category),
            source: item.scene,
            _toggling: false,
        };
    });
};

const toggleTaskStatus = async (item: any) => {
    if (item._toggling) return;
    item._toggling = true;
    try {
        await updateAutoTaskExecutionPlan({
            id: item.id,
            status: item.status,
            persona_type: personType.value,
            device_code: deviceCode.value,
            persona_id: deviceDetail.value?.persona_info?.id,
        });
        uni.showToast({ title: "操作成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        item.status = item.status === 1 ? 1 : 0;
        uni.showToast({ title: error ?? "操作失败，请重试", icon: "none", duration: 3000 });
    } finally {
        item._toggling = false;
    }
};

const queryList = async () => {
    try {
        const apiData = await getAutoTaskExecutionPlan({ device_code: deviceCode.value });
        taskTimeConfig.value = transformApiData(apiData);
        pagingRef.value?.complete(taskTimeConfig.value);
    } catch {
        pagingRef.value?.complete([]);
    }
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
    const { is_empty, persona_type } = data;
    personType.value = persona_type;
    autoTaskDetail.value = data;
    isCompleteConfig.value = is_empty === 0;
    if (isCompleteConfig.value) initializePlatform(deviceDetail.value.accounts);
};

const chooseAppPlatforms = ref<any[]>([]);
const selectedPlatform = ref<any>({});
const currTaskKey = ref<any>(null);
const demoParams = ref<any>({ device_code: deviceCode.value, account_type: null, source: null });

const selectPlatform = (p: any) => {
    selectedPlatform.value = p;
    demoParams.value.account_type = p.type;
};

const handleDemo = (item: any) => {
    uni.showModal({
        title: "提示",
        content: "检测有任务在执行中，演示任务会中断当前任务，是否确定继续演示任务？",
        success: (res) => {
            if (!res.confirm) return;
            demoParams.value = {
                device_code: deviceCode.value,
                source: item.source,
                account_type: item.platform[0].type,
                start_time: item.time[0],
                end_time: item.time[1],
                persona_type: personType.value,
            };
            currTaskKey.value = item.key;
            if (item.platform.length > 1) {
                chooseAppPlatforms.value = item.platform;
                selectedPlatform.value = chooseAppPlatforms.value[0];
                showChooseApp.value = true;
            } else {
                handleCheckRealTask();
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
});
onUnload(() => {
    close();
});
</script>

<style scoped lang="scss">
.animate-pulse {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.45;
    }
}
</style>
