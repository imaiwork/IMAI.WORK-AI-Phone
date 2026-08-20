<template>
    <view class="min-h-screen bg-[#F3F5FA] text-[#1f2937] pb-[180rpx] relative overflow-x-hidden">
        <image
            :src="indexBgUrl"
            mode="scaleToFill"
            class="absolute left-0 top-0 z-0 w-full h-[520rpx] pointer-events-none" />
        <view class="relative z-10 overflow-hidden px-[40rpx] pt-[96rpx] pb-[32rpx] text-white">
            <view
                class="absolute rounded-full bg-[rgba(255,255,255,0.05)] w-[384rpx] h-[384rpx] right-[-80rpx] top-[-80rpx]" />
            <view
                class="absolute rounded-full bg-[rgba(255,255,255,0.05)] w-[224rpx] h-[224rpx] right-[-32rpx] top-[64rpx]" />
            <view class="relative z-10 mb-[16rpx] flex items-start justify-between gap-[24rpx]">
                <text class="text-[48rpx] leading-[64rpx] font-bold">AI 智能数字员工</text>
            </view>
            <text class="relative z-10 block text-sm text-[#dbeafe] mb-[40rpx]">助力企业高效增长，让业务自动运转</text>
            <view class="relative z-10 flex gap-[24rpx] flex-wrap">
                <view
                    class="flex items-center gap-[12rpx] border-[2rpx] border-[rgba(255,255,255,0.2)] bg-[rgba(255,255,255,0.15)] rounded-full px-[24rpx] py-[12rpx] text-xs">
                    <view class="w-[16rpx] h-[16rpx] rounded-full bg-[#4ade80]" />
                    <text>1台手机 = 6个员工</text>
                </view>
                <view
                    class="flex items-center gap-[12rpx] border-[2rpx] border-[rgba(255,255,255,0.2)] bg-[rgba(255,255,255,0.15)] rounded-full px-[24rpx] py-[12rpx] text-xs">
                    <view class="w-[16rpx] h-[16rpx] rounded-full bg-[#fb923c]" />
                    <text>7×24h 自动运行</text>
                </view>
            </view>
        </view>

        <view
            class="relative z-10 mx-[32rpx] mt-[40rpx] rounded-[32rpx] px-[16rpx] py-[40rpx] bg-white flex justify-around shadow-[0_8rpx_56rpx_rgba(99,120,200,0.13),0_2rpx_8rpx_rgba(0,0,0,0.05)]">
            <view
                v-for="item in quickActions"
                :key="item.label"
                class="flex flex-col items-center gap-[20rpx]"
                @click="go(item.url)">
                <view
                    class="w-[100rpx] h-[100rpx] rounded-full flex items-center justify-center"
                    :class="item.iconClass">
                    <image :src="item.icon" class="w-[48rpx] h-[48rpx]" mode="aspectFill" />
                </view>
                <text class="text-xs font-medium text-[#374151]">{{ item.label }}</text>
            </view>
        </view>

        <view class="relative z-10 mt-[40rpx] px-[32rpx]">
            <view class="mb-[32rpx] flex items-center justify-between gap-[16rpx]">
                <view class="min-w-0 flex items-center gap-[16rpx] text-xl font-bold">
                    <view class="w-[8rpx] h-[32rpx] rounded-full bg-primary flex-shrink-0" />
                    <text class="truncate"
                        >我的AI员工 <text class="text-[#9ca3af] font-normal">({{ personaTotal }})</text></text
                    >
                </view>
                <view class="flex flex-shrink-0 items-center gap-[12rpx]">
                    <view
                        class="flex items-center gap-[6rpx] px-[20rpx] py-[12rpx] text-primary bg-primary-light-9 rounded-full text-xs font-medium"
                        @click="goCreatePersona">
                        <text class="text-[28rpx] leading-none">＋</text>
                        <text>新增人设</text>
                    </view>
                    <view
                        class="flex items-center gap-[6rpx] px-[20rpx] py-[12rpx] text-primary bg-primary-light-9 rounded-full text-xs font-medium"
                        @click="go('/ai_modules/person/pages/index/index')">
                        <u-icon name="grid" size="28" color="#0065FB"></u-icon>
                        <text>全部</text>
                    </view>
                </view>
            </view>

            <scroll-view scroll-x class="w-full whitespace-nowrap" show-scrollbar="false">
                <view class="inline-flex gap-[24rpx] px-[8rpx] pt-[4rpx] pb-[32rpx]">
                    <block v-if="personaLoading">
                        <view
                            v-for="item in 2"
                            :key="item"
                            class="w-[544rpx] rounded-[48rpx] p-[40rpx] bg-white flex-shrink-0 shadow-[0_4rpx_20rpx_rgba(99,120,200,0.10),0_1rpx_4rpx_rgba(0,0,0,0.04)]">
                            <view class="flex items-center">
                                <view class="w-[112rpx] h-[112rpx] rounded-[32rpx] bg-[#f3f4f6]" />
                                <view class="flex-1 ml-[24rpx]">
                                    <view class="w-[260rpx] h-[32rpx] rounded-full bg-[#f3f4f6]" />
                                    <view class="mt-[16rpx] w-[120rpx] h-[32rpx] rounded-full bg-[#f3f4f6]" />
                                </view>
                            </view>
                            <view class="mt-[24rpx] h-[72rpx] rounded-[32rpx] bg-[#f9fafb]" />
                            <view class="mt-[24rpx] h-[80rpx] rounded-full bg-[#f9fafb]" />
                        </view>
                    </block>

                    <view
                        v-else-if="personaError"
                        class="w-[544rpx] min-h-[320rpx] rounded-[48rpx] p-[40rpx] bg-white flex-shrink-0 shadow-[0_4rpx_20rpx_rgba(99,120,200,0.10),0_1rpx_4rpx_rgba(0,0,0,0.04)] flex flex-col items-center justify-center text-center"
                        @click="queryPersonaList">
                        <view
                            class="w-[96rpx] h-[96rpx] rounded-[32rpx] bg-error-light-9 flex items-center justify-center">
                            <u-icon name="reload" size="44" color="#EF4444"></u-icon>
                        </view>
                        <text class="block mt-[24rpx] text-sm font-bold text-[#1f2937]">人设加载失败</text>
                        <text class="block mt-[8rpx] text-xs text-[#9ca3af]">网络异常，点击重新加载</text>
                    </view>

                    <view
                        v-else-if="personas.length === 0"
                        class="w-[544rpx] min-h-[320rpx] rounded-[48rpx] p-[40rpx] bg-white flex-shrink-0 shadow-[0_4rpx_20rpx_rgba(99,120,200,0.10),0_1rpx_4rpx_rgba(0,0,0,0.04)] flex flex-col items-center justify-center text-center"
                        @click="goCreatePersona">
                        <view
                            class="w-[96rpx] h-[96rpx] rounded-[32rpx] bg-primary-light-9 flex items-center justify-center">
                            <u-icon name="plus-circle" size="44" color="#0065FB"></u-icon>
                        </view>
                        <text class="block mt-[24rpx] text-sm font-bold text-[#1f2937]">还没有 AI 员工</text>
                        <text class="block mt-[8rpx] text-xs text-[#9ca3af]">创建第一个人设，开启自动化工作</text>
                        <view
                            class="mt-[24rpx] inline-flex items-center justify-center px-[40rpx] py-[16rpx] rounded-full bg-primary text-white text-xs font-semibold">
                            创建 AI 员工
                        </view>
                    </view>

                    <block v-else>
                        <view
                            v-for="persona in personas"
                            :key="persona.key"
                            class="w-[544rpx] rounded-[48rpx] border-[4rpx] border-solid border-[transparent] p-[40rpx] bg-white flex-shrink-0 transition-all"
                            :style="personaCardStyle(persona)"
                            @click="selectPersona(persona.key)">
                            <view class="flex items-center justify-between">
                                <view class="relative w-[112rpx] h-[112rpx] flex-shrink-0">
                                    <image :src="persona.avatar" class="w-full h-full rounded-full" mode="aspectFill" />
                                    <view
                                        class="absolute right-[2rpx] bottom-[2rpx] w-[28rpx] h-[28rpx] bg-[#4ade80] border-[4rpx] border-white rounded-full" />
                                </view>
                                <view class="flex-1 min-w-0 ml-[24rpx]">
                                    <text class="block text-sm font-bold text-[#1f2937] line-clamp-1">{{
                                        persona.name
                                    }}</text>
                                    <text
                                        class="inline-flex mt-[8rpx] px-[16rpx] py-[4rpx] rounded-full text-[20rpx] font-medium"
                                        :style="personaToneStyle(persona)">
                                        {{ persona.tag }}
                                    </text>
                                </view>
                                <view
                                    class="w-[64rpx] h-[64rpx] flex items-center justify-center text-[#9ca3af] bg-[#F3F4F6] rounded-full flex-shrink-0"
                                    @click.stop="openPersonaSetting(persona)">
                                    <u-icon name="setting" size="34" color="#9CA3AF"></u-icon>
                                </view>
                            </view>
                            <view
                                class="mt-[24rpx] flex items-center gap-[16rpx] px-[24rpx] py-[16rpx] rounded-[32rpx] bg-[#f9fafb] text-[#6b7280] text-xs">
                                <u-icon name="mobile"></u-icon>
                                <text v-if="persona.deviceCount > 0" class="text-[#6b7280]">
                                    正在控制
                                    <text class="text-black"> {{ persona.deviceCount }} </text>
                                    部手机
                                </text>
                                <text v-else class="text-[#6b7280]">{{ persona.deviceText }}</text>
                            </view>
                            <view
                                class="mt-[24rpx] h-[80rpx] flex items-center justify-center gap-[12rpx] rounded-full text-sm font-bold"
                                :style="personaToneStyle(persona)"
                                @click.stop="openMaterialLibrary(persona)">
                                <u-icon name="plus-circle"></u-icon>
                                <text>喂养AI素材</text>
                            </view>
                        </view>
                    </block>
                </view>
            </scroll-view>
        </view>

        <view class="relative z-10 mt-[32rpx] px-[32rpx]">
            <view class="mb-[24rpx] flex items-start justify-between gap-[16rpx]">
                <view class="min-w-0 flex-1">
                    <view class="flex items-center gap-[16rpx] text-xl font-bold">
                        <view class="w-[8rpx] h-[32rpx] rounded-full bg-primary flex-shrink-0" />
                        <text>AI自动干活流水线</text>
                    </view>
                    <text
                        v-if="activePersonaName"
                        class="block mt-[8rpx] ml-[24rpx] text-xs font-medium text-[#9ca3af]">
                        ({{ activePersonaName }})
                    </text>
                </view>
                <view
                    class="flex flex-shrink-0 items-center gap-[8rpx] px-[24rpx] py-[12rpx] text-primary bg-primary-light-9 rounded-full text-xs font-medium"
                    @click="go('/ai_modules/device/pages/choose_task_type/choose_task_type')">
                    <text class="text-[28rpx]">＋</text>
                    <text>新增手动任务</text>
                </view>
            </view>

            <view v-if="personaLoading" class="bg-white rounded-[32rpx] p-[32rpx] animate-pulse">
                <view class="flex items-center gap-[24rpx]">
                    <view class="w-[80rpx] h-[80rpx] rounded-[24rpx] bg-[#f3f4f6]" />
                    <view class="flex-1">
                        <view class="w-[260rpx] h-[28rpx] rounded-full bg-[#f3f4f6]" />
                        <view class="mt-[12rpx] w-[180rpx] h-[24rpx] rounded-full bg-[#f3f4f6]" />
                    </view>
                </view>
            </view>

            <view
                v-else-if="!workflowPanels.length"
                class="bg-white rounded-[32rpx] pt-[72rpx] pb-[56rpx] px-[40rpx] flex flex-col items-center text-center shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)] relative overflow-hidden"
                @click="workflowEmptyConfig.ctaHandler">
                <view class="relative z-10">
                    <view
                        class="w-[128rpx] h-[128rpx] rounded-[40rpx] bg-primary-light-9 flex items-center justify-center mx-auto">
                        <u-icon name="play-circle-fill" size="56" color="#0065FB"></u-icon>
                    </view>
                    <text class="block mt-[28rpx] text-base font-bold text-[#1f2937]">
                        {{ workflowEmptyConfig.title }}
                    </text>
                    <text class="block mt-[12rpx] text-xs text-[#9ca3af] max-w-[440rpx] mx-auto leading-[40rpx]">
                        {{ workflowEmptyConfig.desc }}
                    </text>
                    <view
                        class="mt-[32rpx] inline-flex items-center justify-center px-[48rpx] py-[20rpx] rounded-full bg-primary text-white text-xs font-semibold">
                        {{ workflowEmptyConfig.ctaText }}
                    </view>
                </view>
            </view>

            <view v-else class="flex flex-col gap-[24rpx]">
                <view
                    v-for="workflow in workflowPanels"
                    :key="workflow.id"
                    class="bg-white rounded-[32rpx] overflow-hidden shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]">
                    <view class="p-[32rpx] flex items-center justify-between" @click="handleWorkflow(workflow)">
                        <view class="flex items-center gap-[24rpx]">
                            <view
                                class="w-[80rpx] h-[80rpx] rounded-[24rpx] flex items-center justify-center flex-shrink-0 bg-primary-light-9">
                                <image :src="workflow.icon" mode="aspectFit" class="w-[44rpx] h-[44rpx]" />
                            </view>
                            <view>
                                <text class="block text-sm font-bold text-[#1f2937]">{{ workflow.title }}</text>
                                <text class="block mt-[4rpx] text-xs text-[#9ca3af]">
                                    {{ workflow.subtitle || `包含 ${workflow.taskCount} 个时段任务` }}
                                </text>
                            </view>
                        </view>
                        <u-icon
                            :name="isExpanded(workflow.id) ? 'arrow-down' : 'arrow-right'"
                            :size="24"
                            color="#9ca3af"></u-icon>
                    </view>

                    <view
                        v-if="workflow.expandable && isExpanded(workflow.id)"
                        class="border-t-[2rpx] border-[#f9fafb] bg-[rgba(249,250,251,0.5)] px-[32rpx] pt-[24rpx] pb-[32rpx] flex flex-col gap-[24rpx]">
                        <view
                            v-for="task in workflow.tasks"
                            :key="task.title"
                            class="bg-white flex items-start justify-between border-[2rpx] border-[#f3f4f6] rounded-[24rpx] p-[24rpx] shadow-[0_2rpx_4rpx_rgba(0,0,0,0.03)]"
                            @click="openTask(task)">
                            <view class="flex-1 min-w-0">
                                <view class="flex items-center gap-[16rpx] flex-wrap">
                                    <text class="text-sm text-[#1f2937] font-bold">{{ task.title }}</text>
                                    <text
                                        class="rounded-[12rpx] px-[12rpx] py-[4rpx] text-[20rpx] font-medium"
                                        :class="task.tagClass"
                                        >{{ task.tag }}</text
                                    >
                                </view>
                                <view
                                    v-if="task.timeTexts.length"
                                    class="mt-[14rpx] flex items-center gap-[12rpx] flex-wrap">
                                    <image
                                        :src="workflowClockIcon"
                                        mode="aspectFit"
                                        class="w-[24rpx] h-[24rpx] flex-shrink-0" />
                                    <text
                                        v-for="timeText in task.timeTexts"
                                        :key="timeText.text"
                                        class="rounded-[12rpx] px-[12rpx] py-[4rpx] text-[20rpx] font-medium"
                                        :class="timeText.chipClass">
                                        {{ timeText.text }}{{ timeText.statusText ? ` ${timeText.statusText}` : "" }}
                                    </text>
                                </view>
                            </view>
                            <text
                                class="rounded-[12rpx] px-[12rpx] py-[4rpx] text-[20rpx] font-medium whitespace-nowrap"
                                :class="task.statusClass"
                                >{{ task.statusText }}</text
                            >
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <tabbar />

        <hot-popup
            v-model="hotPopupVisible"
            :persona-id="activePersonaId"
            :time-range="hotTimeRange"
            :task-status="hotTaskStatus"
            :status-text="hotStatusText"
            @open-script="openScript"
            @toast="showToast"
            @go="go" />

        <script-popup v-model="scriptVisible" :script="currentScript" />

        <create-popup v-model="createPopupVisible" :persona-id="activePersonaId" @toast="showToast" />

        <publish-popup v-model="publishPopupVisible" :persona-id="activePersonaId" />

        <rival-internet-popup
            v-model="rivalInternetVisible"
            :persona-id="activePersonaId"
            :expanded-customer-id="expandedRivalCustomer"
            :header-icon="rivalInternetIcon"
            :settings-icon="settingsIcon"
            :badge-of="rivalActionBadge"
            @edit-tracking="goEditPersonaTracking"
            @toggle-customer="toggleRivalCustomer"
            @copy="copyText"
            @preview="previewScreenshot" />

        <rival-local-popup
            v-model="rivalLocalVisible"
            :persona-id="activePersonaId"
            :expanded-customer-id="expandedRivalCustomer"
            :map-pin-icon="mapPinIcon"
            :settings-icon="settingsIcon"
            :badge-of="rivalActionBadge"
            @edit-tracking="goEditPersonaTracking"
            @toggle-customer="toggleRivalCustomer"
            @copy="copyText"
            @preview="previewScreenshot" />

        <store-target-popup
            v-model="storeTargetVisible"
            :persona-id="activePersonaId"
            :expanded-customer-id="expandedRivalCustomer"
            :header-icon="storeIcon"
            :settings-icon="settingsIcon"
            :badge-of="rivalActionBadge"
            @edit-tracking="goEditPersonaTracking"
            @toggle-customer="toggleRivalCustomer"
            @copy="copyText"
            @preview="previewScreenshot" />

        <wx-video-lead-popup
            v-model="wxVideoLeadVisible"
            :persona-id="activePersonaId"
            :video-icon="videoIcon"
            :settings-icon="settingsIcon"
            :map-pin-icon="mapPinIcon"
            :copy-icon="copyIcon"
            :clock-icon="workflowClockIcon"
            :image-icon="imageIcon"
            :contact-badge="wxLeadContactBadge"
            @edit-tracking="goEditPersonaTracking"
            @copy="copyText"
            @preview="previewScreenshot" />

        <chat-social-popup v-model="chatSocialVisible" :persona-id="activePersonaId" />
        <chat-comment-popup v-model="chatCommentVisible" :persona-id="activePersonaId" />
        <chat-wechat-popup v-model="chatWechatVisible" :persona-id="activePersonaId" @preview="previewScreenshot" />
        <chat-moments-popup v-model="chatMomentsVisible" :persona-id="activePersonaId" @preview="previewScreenshot" />

        <simple-popup
            v-model="simplePopupVisible"
            :modal="simpleModal"
            @update:visible="simplePopupVisible = $event"
            @toast="showToast" />
    </view>
</template>

<script setup lang="ts">
import config from "@/config";
import { getHomeDisplay } from "@/api/app";
import {
    usePersonas,
    isDemoPersona,
    PERSONA_THEME_COLOR,
    PERSONA_THEME_BG,
    PERSONA_NORMAL_COLOR,
    PERSONA_NORMAL_BG,
    type HomePersona,
} from "./hooks/usePersonas";
import { useWorkflowPanels, type Workflow, type Task } from "./hooks/useWorkflowPanels";
import { HomeModalType } from "./enums";
import { useUserStore } from "@/stores/user";
import { navigateTo, setFormData } from "@/utils/util";
import ChatSocialPopup from "./components/chat-social-popup.vue";
import ChatCommentPopup from "./components/chat-comment-popup.vue";
import ChatWechatPopup from "./components/chat-wechat-popup.vue";
import ScriptPopup from "./components/popups/script-popup.vue";
import PublishPopup from "./components/popups/publish-popup.vue";
import SimplePopup from "./components/popups/simple-popup.vue";
import HotPopup from "./components/popups/hot-popup.vue";
import RivalInternetPopup from "./components/popups/rival-internet-popup.vue";
import StoreTargetPopup from "./components/popups/store-target-popup.vue";
import CreatePopup from "./components/popups/create-popup.vue";
import RivalLocalPopup from "./components/popups/rival-local-popup.vue";
import WxVideoLeadPopup from "./components/popups/wx-video-lead-popup.vue";
import ChatMomentsPopup from "./components/chat-moments-popup.vue";

const userStore = useUserStore();
const { isLogin } = toRefs(userStore);

const indexBgUrl = config.baseUrl + "static/images/mp/index_bg.png";
const workflowClockIcon = config.baseUrl + "static/images/mp/workflow_clock.svg";
const rivalInternetIcon = config.baseUrl + "static/images/mp/workflow_crosshair.svg";
const settingsIcon = config.baseUrl + "static/images/mp/workflow_settings.svg";
const imageIcon = config.baseUrl + "static/images/mp/workflow_image.svg";
const copyIcon = config.baseUrl + "static/images/mp/workflow_copy.svg";
const mapPinIcon = config.baseUrl + "static/images/mp/workflow_map_pin.svg";
const storeIcon = config.baseUrl + "static/images/mp/workflow_store.svg";
const videoIcon = config.baseUrl + "static/images/mp/workflow_video.svg";

const quickActions = [
    {
        label: "任务日历",
        icon: `${config.baseUrl}static/images/mp/home_nav_1.svg`,
        iconClass: "bg-[#eff6ff]",
        url: "/ai_modules/device/pages/task_calendar_full/task_calendar_full",
    },
    {
        label: "数据报表",
        icon: `${config.baseUrl}static/images/mp/home_nav_2.svg`,
        iconClass: "bg-[#FEF7EE]",
        url: "/packages/pages/effect_statistics/effect_statistics",
    },
    {
        label: "客资线索",
        icon: `${config.baseUrl}static/images/mp/home_nav_3.svg`,
        iconClass: "bg-[#FCF3F2]",
        url: "/packages/pages/customer_leads/customer_leads",
    },
    {
        label: "客户案例",
        icon: `${config.baseUrl}static/images/mp/home_nav_4.svg`,
        iconClass: "bg-[#F3FDF5]",
        url: "/packages/pages/operate_case/operate_case",
    },
];

type RivalCustomerAction = "private" | "comment" | "like" | "flyer";

const RIVAL_ACTION_BADGE: Record<RivalCustomerAction, { label: string; cls: string }> = {
    private: { label: "私信", cls: "text-primary bg-primary-light-9" },
    comment: { label: "评论", cls: "text-[#9333ea] bg-[#faf5ff]" },
    like: { label: "点赞", cls: "text-[#ec4899] bg-[#fdf2f8]" },
    flyer: { label: "电子传单", cls: "text-[#d97706] bg-[#fef3c7]" },
};

type WxLeadContactType = "wechat" | "phone" | "work_wechat";

const WX_LEAD_CONTACT_BADGE: Record<WxLeadContactType, { label: string; cls: string }> = {
    wechat: { label: "微信号", cls: "text-success bg-success-light-9" },
    phone: { label: "手机号", cls: "text-primary bg-primary-light-9" },
    work_wechat: { label: "企业微信", cls: "text-[#0d9488] bg-[#f0fdfa]" },
};

const simpleModalMap: Record<
    string,
    {
        title: string;
        items: Array<{
            avatar: string;
            title: string;
            desc: string;
            tag: string;
            detail: string;
        }>;
    }
> = {};

const expandedWorkflow = ref<string | null>(null);

const { personas, personaTotal, personaLoading, personaError, activePersona, queryPersonaList } = usePersonas({
    isLogin,
    onActiveChange: () => {
        expandedWorkflow.value = null;
    },
});

// 当前激活人设的真实后端 ID（演示数据返回空字符串，弹窗/流水线内部会跳过请求）
const activePersonaId = computed(() => {
    const persona = personas.value.find((item) => item.key === activePersona.value);
    if (!persona || isDemoPersona(persona)) return "";
    return persona.id || "";
});

const activePersonaName = computed(() => {
    const persona = personas.value.find((item) => item.key === activePersona.value);
    return persona?.name || "";
});
const activeModal = ref<HomeModalType | "">("");
const scriptVisible = ref(false);
const currentScript = ref("");
const expandedRivalCustomer = ref("");
const expandedLead = ref("");

const { workflowPanels, refreshWorkflowPanels } = useWorkflowPanels({
    activePersona,
    personaId: activePersonaId,
});

const simpleModal = computed(() => simpleModalMap[activeModal.value]);

const modalVisible = (type: HomeModalType) =>
    computed({
        get: () => activeModal.value === type,
        set: (v: boolean) => {
            if (!v && activeModal.value === type) closeModal();
        },
    });

const hotPopupVisible = modalVisible(HomeModalType.HOT);
const createPopupVisible = modalVisible(HomeModalType.CREATE);
const publishPopupVisible = modalVisible(HomeModalType.PUBLISH);
const rivalInternetVisible = modalVisible(HomeModalType.RIVAL_INTERNET);
const rivalLocalVisible = modalVisible(HomeModalType.RIVAL_LOCAL);
const storeTargetVisible = modalVisible(HomeModalType.STORE_TARGET);
const wxVideoLeadVisible = modalVisible(HomeModalType.WX_VIDEO_LEAD);
const chatSocialVisible = modalVisible(HomeModalType.CHAT_SOCIAL);
const chatCommentVisible = modalVisible(HomeModalType.CHAT_COMMENT);
const chatWechatVisible = modalVisible(HomeModalType.CHAT_WECHAT);
const chatMomentsVisible = modalVisible(HomeModalType.CHAT_MOMENTS);

const simplePopupVisible = computed({
    get: () => Boolean(simpleModal.value),
    set: (v: boolean) => {
        if (!v && simpleModal.value) closeModal();
    },
});

const wxLeadContactBadge = (type: WxLeadContactType) => WX_LEAD_CONTACT_BADGE[type];

const statistics = reactive({
    today_task_count: 0,
    today_complete_task_count: 0,
    today_rate: 0,
    worker_device_count: 0,
    today_touch_number: "",
    today_expose_number: 0,
    seven_expose_number: "",
    seven_touch_number: 0,
    seven_intention_number: 0,
});

const isPersonaActive = (persona: HomePersona) => activePersona.value === persona.key;

const personaToneStyle = (persona: HomePersona) => {
    const active = isPersonaActive(persona);
    return {
        color: active ? PERSONA_THEME_COLOR : PERSONA_NORMAL_COLOR,
        background: active ? PERSONA_THEME_BG : PERSONA_NORMAL_BG,
    };
};

const personaCardStyle = (persona: HomePersona) => {
    const active = isPersonaActive(persona);
    return {
        borderColor: active ? PERSONA_THEME_COLOR : "transparent",
    };
};

const isExpanded = (id: string) => expandedWorkflow.value === id;

const selectPersona = (key: string) => {
    activePersona.value = key;
    expandedWorkflow.value = null;
    const name = personas.value.find((item) => item.key === key)?.name || "";
    if (name) showToast(`已加载 ${name} 的工作流水线`);
};

const openPersonaSetting = (persona: HomePersona) => {
    if (isDemoPersona(persona) || !persona.id) {
        go("/ai_modules/person/pages/index/index");
        return;
    }
    go(`/ai_modules/person/pages/detail/detail?id=${persona.id}&mode=edit`);
};

const openMaterialLibrary = (persona: HomePersona) => {
    if (isDemoPersona(persona)) {
        go("/ai_modules/person/pages/index/index");
        return;
    }
    if (!persona.id) {
        showToast("请先选择人设");
        return;
    }
    go(`/ai_modules/person/pages/detail/detail?id=${persona.id}&tab=materials`);
};

const handleWorkflow = (workflow: Workflow) => {
    if (workflow.expandable) {
        expandedWorkflow.value = expandedWorkflow.value === workflow.id ? null : workflow.id;
        return;
    }
    if (workflow.modalType) {
        activeModal.value = workflow.modalType;
    }
};

// 今日爆款速递头部时段/状态：由对应工作流任务（找今天的爆款选题）带入
const hotTimeRange = ref("");
const hotTaskStatus = ref(0);
const hotStatusText = ref("");

const openTask = (task: Task) => {
    if (task.modalType) {
        if (task.modalType === HomeModalType.HOT) {
            hotTimeRange.value = task.timeTexts?.[0]?.text || "";
            hotTaskStatus.value = task.status;
            hotStatusText.value = task.statusText || "";
        }
        activeModal.value = task.modalType;
    }
};

const openScript = (script: string) => {
    currentScript.value = script;
    scriptVisible.value = true;
};

const closeModal = () => {
    activeModal.value = "";
    expandedRivalCustomer.value = "";
    expandedLead.value = "";
};

const rivalActionBadge = (action: RivalCustomerAction) => RIVAL_ACTION_BADGE[action];

const toggleRivalCustomer = (id: string) => {
    expandedRivalCustomer.value = expandedRivalCustomer.value === id ? "" : id;
};

const copyText = (text: string) => {
    uni.setClipboardData({
        data: text,
        success: () => showToast("已复制"),
    });
};

const previewScreenshot = (url: string) => {
    if (!url) return;
    uni.previewImage({ urls: [url], current: url });
};

const goEditPersonaTracking = () => {
    const persona = personas.value.find((item) => item.key === activePersona.value);
    if (!persona || isDemoPersona(persona)) {
        go("/ai_modules/person/pages/index/index");
        return;
    }
    go(`/ai_modules/person/pages/ai_employee/ai_employee?id=${persona.id}`);
};

const showToast = (message: string) => {
    uni.showToast({
        title: message,
        icon: "none",
    });
};

const go = (url?: string) => {
    if (!url) return;
    uni.navigateTo({
        url,
    });
};

const goCreatePersona = () => go("/ai_modules/person/pages/create/create");

const workflowEmptyConfig = computed(() => ({
    title: "工作流水线待开启",
    desc: "创建你的第一个 AI 员工，自动生成全天候任务安排",
    ctaText: "创建 AI 员工",
    ctaHandler: goCreatePersona,
}));

const getHomeDisplayData = async () => {
    try {
        const data = await getHomeDisplay();
        setFormData(data, statistics);
    } catch (error) {
        console.warn("getHomeDisplay failed", error);
    }
};

onShow(() => {
    queryPersonaList();
    getHomeDisplayData();
    refreshWorkflowPanels();
});
</script>
