<template>
    <view class="h-full flex flex-col bg-[#EEF0F6]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :is-back="showNavbarBack"
            :background="{ background: 'transparent' }"
            is-custom-back-icon
            :custom-back="backChat">
            <template #custom-back-icon v-if="showNavbarBack">
                <view class="flex items-center gap-x-4 leading-[0]">
                    <view class="glass-btn back-btn">
                        <u-icon name="arrow-left" :size="30"></u-icon>
                    </view>
                    <view v-if="chatContentList.length > 0" class="glass-btn icon-btn" @click.stop="handleAddSession">
                        <image src="/static/images/icons/chat_new.svg" class="w-[28rpx] h-[28rpx]" />
                    </view>
                </view>
            </template>

            <view class="navbar-center" v-if="chatContentList.length == 0 && !currAgent && !currModel">
                <view class="glass-tabs" v-if="tabList.length > 1">
                    <view
                        v-for="(tab, index) in tabList"
                        :key="`${tab.type}-${tab.name}`"
                        class="glass-tab-item"
                        :class="{ 'glass-tab-active': currTab === index }"
                        @click="handleTabChange(index)">
                        <text class="glass-tab-text" :class="{ 'glass-tab-text-active': currTab === index }">
                            {{ tab.name }}
                        </text>
                    </view>
                </view>
                <view class="page-title" v-else>
                    <text class="title-text">AI 智能体</text>
                </view>
            </view>
        </u-navbar>
        <view class="grow min-h-0 pt-2">
            <view class="h-full w-full" v-show="currType === 0">
                <!-- 图像模式：独立面板（对齐 HTML，不使用 chat-scroll-view） -->
                <image-studio-panel
                    v-if="workbenchMode === WorkbenchMode.Image"
                    ref="imageStudioRef"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :placeholder="workbenchPlaceholder"
                    :image-optimize="imageOptimize"
                    :current-model-name="imageCurrentModelName"
                    :size-label="imageSizeLabel"
                    :image-ratio="imageRatio"
                    :image-count="imageCount"
                    :aspect-ratio="imageAspectRatio"
                    :ref-images="imageRefImages"
                    :tabbar-visible="showWorkbenchTabbar"
                    @exit="handleWorkbenchModeChange(WorkbenchMode.Chat)"
                    @content-post="handleContentPost"
                    @show-history="openHistorySheet"
                    @open-case="openImageCaseSheet"
                    @open-model-sheet="openImageModelSheet"
                    @open-size-sheet="openImageSizeSheet"
                    @update:image-optimize="setImageOptimize"
                    @add-ref="handleAddWorkbenchRef"
                    @remove-ref="handleRemoveWorkbenchRef"
                    @optimize-update="handleImageOptimizeUpdate"
                    @optimize-regen="handleImageOptimizeRegen"
                    @optimize-confirm="handleImageOptimizeConfirm"
                    @optimize-cancel="handleImageOptimizeCancel"
                    @regenerate="handleImageRegenerate" />

                <!-- 地图获客：独立面板（对齐 HTML，不使用 chat-scroll-view） -->
                <map-studio-panel
                    v-else-if="workbenchMode === WorkbenchMode.Map"
                    ref="mapStudioRef"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :placeholder="workbenchPlaceholder"
                    :can-load-more="!!mapNextPage && !mapExhausted"
                    :can-export="!!mapLastMessageId"
                    :tabbar-visible="showWorkbenchTabbar"
                    @exit="handleWorkbenchModeChange(WorkbenchMode.Chat)"
                    @content-post="handleContentPost"
                    @show-history="openHistorySheet"
                    @map-more="handleMapLoadMore"
                    @map-export="handleMapExport" />

                <!-- PPT：独立面板（对齐 PC，不使用 chat-scroll-view） -->
                <ppt-studio-panel
                    v-else-if="workbenchMode === WorkbenchMode.Ppt"
                    ref="pptStudioRef"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :placeholder="workbenchPlaceholder"
                    :page-range="pptPageRange"
                    :scene="pptScene"
                    :followup-on="pptFollowupOn"
                    :current-model-name="pptCurrentModelName"
                    :tabbar-visible="showWorkbenchTabbar"
                    @exit="handleWorkbenchModeChange(WorkbenchMode.Chat)"
                    @content-post="handleContentPost"
                    @show-history="openHistorySheet"
                    @open-model-sheet="openPptModelSheet"
                    @update:page-range="setPptPageRange"
                    @open-pages-sheet="showPptPagesSheet = true"
                    @open-scene-sheet="showPptSceneSheet = true"
                    @update:scene="setPptScene"
                    @update:followup-on="setPptFollowupOn"
                    @followup-confirm="handlePptFollowupConfirm"
                    @followup-cancel="handlePptFollowupCancel"
                    @regenerate="handlePptRegenerate"
                    @regenerate-slide="handlePptRegenerateSlide" />

                <!-- 视频：独立面板（对齐图像创作交互） -->
                <video-studio-panel
                    v-else-if="workbenchMode === WorkbenchMode.Video"
                    ref="videoStudioRef"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :placeholder="workbenchPlaceholder"
                    :video-optimize="videoOptimize"
                    :current-model-name="videoCurrentModelName"
                    :size-label="videoSizeLabel"
                    :aspect-ratio="videoAspectRatio"
                    :ref-image="videoRefImage"
                    :tabbar-visible="showWorkbenchTabbar"
                    @exit="handleWorkbenchModeChange(WorkbenchMode.Chat)"
                    @content-post="handleContentPost"
                    @show-history="openHistorySheet"
                    @open-size-sheet="openVideoSizeSheet"
                    @update:video-optimize="setVideoOptimize"
                    @add-ref="handleAddWorkbenchRef"
                    @remove-ref="handleRemoveVideoRef"
                    @optimize-update="handleVideoOptimizeUpdate"
                    @optimize-regen="handleVideoOptimizeRegen"
                    @optimize-confirm="handleVideoOptimizeConfirm"
                    @optimize-cancel="handleVideoOptimizeCancel"
                    @regenerate="handleVideoRegenerate" />

                <!-- 对话：沿用工作台滚动面板 -->
                <workbench-chat-scroll
                    v-else
                    ref="chattingRef"
                    v-model:file-list="fileList"
                    :placeholder="workbenchPlaceholder"
                    :is-stop="isStopChat"
                    :content-list="chatContentList"
                    :send-disabled="isReceiving"
                    :tokens="tokensValue"
                    :is-home="chatContentList.length === 0 && !currAgent && !currModel"
                    :is-agent="!!currAgent"
                    :workbench-mode="workbenchMode"
                    @close="handleChatClose"
                    @add-session="handleAddSession"
                    @update:network="handleUpdateNetwork"
                    @content-post="handleContentPost"
                    @show-history="openHistorySheet"
                    @update:agent="handleUpdateAgent"
                    @update:model="handleUpdateModel"
                    @update:mounted-devices="handleMountedDevicesChange"
                    @workbench-change="handleWorkbenchModeChange"
                    @workbench-upload="handleWorkbenchUpload">
                    <!-- 空态走文档流，避免 fixed 层盖住 textarea 导致真机键盘弹不起 -->
                    <template #content>
                        <empty-content
                            :agent="currAgent"
                            :model="currModel"
                            :phone-control-active="mountedDevices.length > 0"
                            @select-model="handleSelectModel" />
                    </template>
                </workbench-chat-scroll>
            </view>
            <view class="h-full w-full flex flex-col" v-show="currType === 1">
                <view class="mt-[24rpx]">
                    <scroll-view scroll-x>
                        <view class="flex gap-[8rpx] px-[32rpx]">
                            <view
                                v-for="item in agentCateLists"
                                :key="item.value"
                                class="font-medium px-[24rpx] h-[64rpx] flex items-center rounded-full whitespace-nowrap text-[26rpx] transition-all"
                                :class="
                                    currAgentType == item.value
                                        ? 'bg-primary text-white'
                                        : 'bg-[#F4F6FB] text-[#676767]'
                                "
                                @click="handleAgentCateClick(item)">
                                {{ item.label }}
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="grow min-h-0 mt-[24rpx]" v-show="currAgentType !== AgentCateType.ASSISTANT">
                    <z-paging
                        ref="pagingCozeRef"
                        v-model="agentList"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="handleQueryAgentList">
                        <view class="flex flex-col gap-[16rpx] px-[32rpx]">
                            <view
                                class="bg-white rounded-[28rpx] flex p-[28rpx] relative shadow-sm border border-solid border-[#E5E7EB]"
                                :class="{ 'opacity-70': !canUseCurrentAgent(item) }"
                                v-for="(item, index) in agentList"
                                :key="index"
                                @click="handleSelectAgent(item)">
                                <view class="w-[96rpx] h-[96rpx] shrink-0">
                                    <image
                                        :src="item.logo || item.image || item.avatar"
                                        class="w-full h-full rounded-full"
                                        mode="aspectFill" />
                                </view>

                                <view class="flex-1 min-w-0 ml-[24rpx]">
                                    <view
                                        class="agent-title-row"
                                        :class="{
                                            'agent-title-row--with-more': isUserOwnedAgent(item),
                                        }">
                                        <text class="agent-title-name">
                                            {{ item.name }}
                                        </text>
                                        <text
                                            v-if="shouldShowAgentAccessTag(item)"
                                            class="agent-access-tag"
                                            :class="getAgentAccessTagClass(item)">
                                            {{ getAgentAccessTagText(item) }}
                                        </text>
                                    </view>

                                    <text
                                        class="text-[22rpx] text-[#676767] line-clamp-2 break-all leading-relaxed block mb-[16rpx]">
                                        {{ item.intro || item.introduced }}
                                    </text>

                                    <view class="flex items-center justify-between">
                                        <view
                                            class="flex items-center gap-x-[8rpx] bg-[#EEF4FF] rounded-full px-[20rpx] py-[10rpx] w-fit">
                                            <u-icon name="chat" :size="26" color="#0065fb" />
                                            <text class="text-[22rpx] font-medium text-primary">去对话</text>
                                        </view>
                                        <view
                                            class="shrink-0 rounded-full px-[14rpx] py-[4rpx] text-[20rpx] font-medium"
                                            :class="
                                                item.source == 1
                                                    ? 'bg-[#EEF4FF] text-primary'
                                                    : 'bg-[#FDF3E3] text-[#A13016]'
                                            ">
                                            {{ item.source == 0 ? "官方" : "用户" }}
                                        </view>
                                    </view>
                                </view>

                                <view
                                    v-if="isAgentOwner(item)"
                                    class="absolute top-[16rpx] right-[16rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70 z-[22]"
                                    @click.stop="handleMore(item)">
                                    <u-icon name="more-dot-fill" :size="22" color="#94A3B8" />
                                </view>
                            </view>
                        </view>

                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>

                <view class="grow min-h-0 flex flex-col" v-show="currAgentType === AgentCateType.ASSISTANT">
                    <view class="m-4 mt-2 relative z-10">
                        <u-search
                            v-model="queryParams.name"
                            bg-color="#ffffff"
                            :show-action="false"
                            placeholder="请输入关键词"
                            @search="search"
                            @clear="clear"></u-search>
                    </view>
                    <view class="grow min-h-0 relative z-10">
                        <view class="h-full flex">
                            <view
                                class="w-[248rpx] h-full flex flex-col overflow-hidden flex-shrink-0 rounded-tr-[36rpx] bg-white">
                                <view class="grow min-h-0">
                                    <scroll-view scroll-y class="h-full">
                                        <view
                                            v-for="(item, index) in optionsData.robotCate"
                                            class="robot-cate"
                                            :key="index"
                                            :class="[
                                                {
                                                    'robot-cate-active': robotCateActiveMenu == index,
                                                },
                                                {
                                                    'robot-cate-brother':
                                                        robotSubCateIndex == item.sub_list.length - 1 &&
                                                        isCurrMenu(item.sub_list),
                                                },
                                                {
                                                    'robot-cate-first':
                                                        robotSubCateIndex == 0 && isCurrMenu(item.sub_list),
                                                },
                                            ]"
                                            @click="handleRobotCate(index)">
                                            <view class="robot-cate-item">
                                                <view class="robot-cate-item-wrap">
                                                    <view class="flex items-center gap-2 w-full">
                                                        <view class="flex-1 text-[#6D6E70] text-xs font-medium">{{
                                                            item.name
                                                        }}</view>
                                                        <u-icon
                                                            :name="
                                                                robotCateActiveMenu == index
                                                                    ? 'arrow-down'
                                                                    : 'arrow-right'
                                                            "
                                                            :size="24"
                                                            color="#707173"></u-icon>
                                                    </view>
                                                    <view class="text-[20rpx] text-[#D0D0D0] mt-1">
                                                        {{ item.sub_list.length }}
                                                    </view>
                                                </view>
                                            </view>
                                            <template v-if="robotCateActiveMenu == index">
                                                <view
                                                    v-for="(subItem, subIndex) in item.sub_list"
                                                    :key="`${index}-${subIndex}`"
                                                    class="sub-robot"
                                                    :class="{
                                                        'sub-robot-active': currSubId == subItem.id,
                                                        'sub-robot-last':
                                                            robotSubCateIndex != 0 &&
                                                            robotSubCateIndex - 1 == subIndex &&
                                                            isCurrMenu(item.sub_list),
                                                    }"
                                                    @click.stop="handleRobotSubCate(Number(subIndex), subItem.id)">
                                                    <view class="sub-robot-item">
                                                        <view class="text-xs text-[#9A9A9C] line-clamp-1">
                                                            {{ subItem.name }}
                                                        </view>
                                                    </view>
                                                </view>
                                            </template>
                                        </view>
                                    </scroll-view>
                                </view>
                            </view>
                            <view class="flex-1 flex flex-col min-h-0 overflow-hidden">
                                <view class="text-[20rpx] font-medium text-[#6D6E70] mt-2 mx-[24rpx] mb-4">
                                    {{ total }}个智能体
                                </view>
                                <view class="grow relative">
                                    <view
                                        class="flex justify-center items-center absolute w-full h-full"
                                        v-if="queryLoading">
                                        <view class="loader"> </view>
                                    </view>
                                    <z-paging
                                        ref="pagingRobotRef"
                                        v-model="robots"
                                        :auto="true"
                                        :fixed="false"
                                        :safe-area-inset-bottom="true"
                                        @query="queryRobotList">
                                        <view class="pl-[24rpx] pr-[16rpx] flex flex-col gap-4">
                                            <view
                                                v-for="(item, index) in robots"
                                                :key="index"
                                                class="bg-white p-[24rpx] rounded-[24rpx]"
                                                @click="handleRobot(item)">
                                                <view class="flex gap-2">
                                                    <image
                                                        :src="item.logo"
                                                        lazy
                                                        class="rounded-full w-[108rpx] h-[108rpx] flex-shrink-0"
                                                        mode="aspectFill"></image>
                                                    <view class="">
                                                        <text class="font-medium mt-1 line-clamp-1">{{
                                                            item.name
                                                        }}</text>
                                                        <view class="text-[20rpx] mt-1 text-[#9C9C9E] line-clamp-2">
                                                            {{ item.description }}
                                                        </view>
                                                    </view>
                                                </view>
                                            </view>
                                        </view>
                                        <template #empty>
                                            <view class="mx-4">
                                                <empty />
                                            </view>
                                        </template>
                                    </z-paging>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="h-full w-full" v-show="currType === ChatTabType.KNOWLEDGE">
                <knowledge-list />
            </view>
        </view>
        <view
            v-if="currType === ChatTabType.AGENT && currAgentType !== AgentCateType.ASSISTANT"
            class="agent-fab"
            @click="handleCreateAgent">
            <u-icon name="plus" color="#ffffff" :size="30"></u-icon>
            <text class="text-[30rpx] font-bold text-white">新增智能体</text>
        </view>
        <view v-if="currType === ChatTabType.KNOWLEDGE" class="agent-fab" @click="handleCreateKnowledge">
            <u-icon name="plus" color="#ffffff" :size="30"></u-icon>
            <text class="text-[30rpx] font-bold text-white">新建知识库</text>
        </view>
        <!-- 空首页显示底栏：含图像/视频/PPT/地图工作台空态，便于切 Tab -->
        <tabbar v-if="showWorkbenchTabbar" />
    </view>
    <!-- 工作台弹窗挂页面根级，避免被 chat 底栏 stacking context / tabbar 遮盖 -->
    <image-model-popup
        v-model="showImageModelSheet"
        :models="imageModels"
        :selected-id="imageSelectedModelId"
        @select="setImageModelId" />
    <image-model-popup
        v-model="showPptModelSheet"
        :models="pptModels"
        :selected-id="pptSelectedModelId"
        @select="setPptModelId" />
    <image-size-popup
        v-model="showImageSizeSheet"
        :ratio="imageRatio"
        :resolution="imageResolution"
        :count="imageCount"
        :max-count="imageMaxCount"
        @confirm="handleImageSizeConfirm" />
    <video-size-popup
        v-model="showVideoSizeSheet"
        :ratio="videoRatio"
        :resolution="videoResolution"
        @confirm="handleVideoSizeConfirm" />
    <ppt-pages-popup v-model="showPptPagesSheet" :page-range="pptPageRange" @confirm="setPptPageRange" />
    <ppt-scene-popup v-model="showPptSceneSheet" :scene="pptScene" @confirm="setPptScene" />
    <image-case-popup v-model="showImageCaseSheet" @choose="handleChooseImageCase" />
    <history-popup
        :visible="showHistory"
        :phone-agent="mountedDevices.length > 0"
        :workbench-scene="workbenchMode"
        @update:visible="showHistory = $event"
        @select="handleSelectRecord" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>
<script lang="ts" setup>
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { chatSendTextStream, getChatLog } from "@/api/chat";
import {
    cancelPhoneAgentTask,
    dispatchPhoneAgentTask,
    getPhoneAgentConversationDetail,
    getPhoneAgentTaskDetail,
    getPhoneAgentTaskEvents,
    type PhoneAgentEvent,
    type PhoneAgentMessage,
} from "@/api/phone_agent";
import {
    robotCategory,
    robotLists,
    getAgentList,
    getAgentDetail as getAgentDetailApi,
    getCozeAgentList,
    deleteAgent,
    deleteCozeAgent,
} from "@/api/agent";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useDictOptions } from "@/hooks/useDictOptions";
import {
    AGENT_UNAVAILABLE_TIP,
    canUseAgent,
    getAgentAccessStatus,
    getAgentAccessTagText as getAgentPermissionTagText,
    isUserOwnedAgent,
    shouldShowAgentAccessTag,
} from "@/utils/agentPermission";
import { parseChatStreamErrorPayload, resolveChatErrorMessage } from "@/utils/chatStream";
import { RequestCodeEnum } from "@/enums/requestEnums";
import usePolling from "@/hooks/usePolling";
import EmptyContent from "./components/empty-content.vue";
import HistoryPopup from "./components/history-popup.vue";
import KnowledgeList from "./components/knowledge-list.vue";
import WorkbenchChatScroll from "./components/workbench-chat-scroll.vue";
import ImageStudioPanel from "./components/image-studio/image-studio-panel.vue";
import MapStudioPanel from "./components/map-studio/map-studio-panel.vue";
import PptStudioPanel from "./components/ppt-studio/ppt-studio-panel.vue";
import VideoStudioPanel from "./components/video-studio/video-studio-panel.vue";
import ImageModelPopup from "./components/popups/image-model-popup.vue";
import ImageSizePopup from "./components/popups/image-size-popup.vue";
import ImageCasePopup from "./components/popups/image-case-popup.vue";
import VideoSizePopup from "./components/popups/video-size-popup.vue";
import PptPagesPopup from "./components/popups/ppt-pages-popup.vue";
import PptScenePopup from "./components/popups/ppt-scene-popup.vue";
import { WorkbenchMode, resolvePptPageCount } from "./enums/workbench";
import { useWorkbenchMode } from "./composables/useWorkbenchMode";
import { useWorkbenchImage } from "./composables/useWorkbenchImage";
import { useWorkbenchVideo } from "./composables/useWorkbenchVideo";
import { useWorkbenchMap } from "./composables/useWorkbenchMap";
import { useWorkbenchPpt } from "./composables/useWorkbenchPpt";
import { DRAW_POLL_ABORTED } from "./composables/useDrawTaskPoll";
import { getMapLeadMessages, normalizeMapLeadChatResult } from "@/api/map_lead";
import { drawConversationDetail, normalizeConversationDetail, getDrawAssetUrls, getDrawVideoUrls } from "@/api/draw";

// 类型定义
interface ChatMessage {
    type: 1 | 2; // 1: 用户消息, 2: AI回复
    message?: string;
    fileList?: FileInfo[];
    loading?: boolean;
    reply?: string;
    error?: string;
    reasoning_content?: string;
    consume_tokens?: Record<string, any>;
    is_reasoning_finished?: boolean;
    tokens_info?: Record<string, any>;
    file_info?: Record<string, any>;
    refCount?: number;
    /** 用户消息附带的参考图（对齐 PC 图+文同一气泡） */
    refImages?: string[];
    /** 重新生成时使用的最终提示词 */
    genPrompt?: string;
    workbench?: {
        kind: "image" | "video" | "ppt" | "map" | "text" | "prompt-optimize" | "prompt-optimizing";
        title?: string;
        text?: string;
        /** 优化卡：用户原文，用于重新生成 */
        original?: string;
        /** 优化卡：发送时快照的参考图，确认后继续走图生图 */
        refImages?: string[];
        regenerating?: boolean;
        status?: "generating" | "done" | "error";
        count?: number;
        modelName?: string;
        ratio?: string;
        sizeLabel?: string;
        aspectRatio?: number;
        urls?: string[];
        slides?: any[];
        cards?: any[];
    };
}

interface FileInfo {
    url: string;
    name: string;
    size: number;
    type: string;
}

interface ChatLogParams {
    page_no: number;
    page_size: number;
    assistant_id: number;
    task_id?: string;
}

// 状态管理
const appStore = useAppStore();
const userStore = useUserStore();
const { chatConfig } = toRefs(appStore);
const { userTokens, isLogin, userInfo } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.CHAT)?.score;

// 组件引用
const rechargePopupRef = ref();
const chattingRef = shallowRef();
const imageStudioRef = shallowRef();
const mapStudioRef = shallowRef();
const pptStudioRef = shallowRef();
const videoStudioRef = shallowRef();

/** 重试由各面板的 scrollToBottom 内部负责，这里只触发一次，避免重试嵌套放大 */
const scrollActiveBottom = () => {
    nextTick(() => {
        if (workbenchMode.value === WorkbenchMode.Image) {
            imageStudioRef.value?.scrollToBottom?.();
            return;
        }
        if (workbenchMode.value === WorkbenchMode.Map) {
            mapStudioRef.value?.scrollToBottom?.();
            return;
        }
        if (workbenchMode.value === WorkbenchMode.Ppt) {
            pptStudioRef.value?.scrollToBottom?.();
            return;
        }
        if (workbenchMode.value === WorkbenchMode.Video) {
            videoStudioRef.value?.scrollToBottom?.();
            return;
        }
        chattingRef.value?.scrollToBottom?.();
    });
};

/** 工作台异步任务令牌：退出/新建/切历史时递增，丢弃过期回调写入 */
let workbenchJobSeq = 0;
const beginWorkbenchJob = () => {
    workbenchJobSeq += 1;
    return workbenchJobSeq;
};
const isWorkbenchJobActive = (jobId: number) => jobId === workbenchJobSeq;
const isWorkbenchAbortError = (error: any) => String(error?.message || error || "").includes(DRAW_POLL_ABORTED);

// 页面状态
const isAgent = ref(false);
const isNetwork = ref(false);
const showHistory = ref(false);
const isReceiving = ref(false);
const isStopChat = ref(false);
const fileList = ref<FileInfo[]>([]);
const chatContentList = ref<ChatMessage[]>([]);
const taskId = ref<string>("");
const phoneAgentTaskId = ref<string>("");
const phoneAgentLastEventId = ref<number | string>("");
const phoneAgentSeenMessages = new Set<string>();
const mountedDevices = ref<any[]>([]);

const currAgent = ref<any>(null);
const currModel = ref<any>(null);

const isShowRobot = computed(() => appStore.getIsShowRobot);

// ─── 工作台多模式（图片 / PPT / 地图 / 视频）────────────────────
const { mode: workbenchMode, placeholder: workbenchPlaceholder, enterMode, exitMode, isChatMode } = useWorkbenchMode();
/** 空首页底栏：工作室据此去掉 safe-area，并参与键盘顶起扣减 */
const showWorkbenchTabbar = computed(
    () => currType.value === 0 && chatContentList.value.length === 0 && !currAgent.value && !currModel.value,
);
/** 有会话/智能体，或处于图像/视频/PPT/地图工作台时显示返回 */
const showNavbarBack = computed(
    () => chatContentList.value.length > 0 || !!currAgent.value || !!currModel.value || !isChatMode.value,
);
const imageWb = useWorkbenchImage();
const videoWb = useWorkbenchVideo();
const mapWb = useWorkbenchMap();
const pptWb = useWorkbenchPpt();

const {
    ratio: imageRatio,
    resolution: imageResolution,
    count: imageCount,
    imageMaxCount,
    clampCount: clampImageCount,
    optimizePrompt: imageOptimize,
    selectedModelId: imageSelectedModelId,
    imageModels,
    currentModel: imageCurrentModel,
    refImages: imageRefImages,
    sizeLabel: imageSizeLabel,
    sizeWH: imageSizeWH,
    addRefImage,
    applyCaseRef,
    removeRefImage,
    optimizeKeywords: optimizeImageKeywords,
    submit: submitImage,
    cancelPending: cancelImagePending,
    resetConversation: resetImageSession,
} = imageWb;
const imageCurrentModelName = computed(
    () => imageCurrentModel.value?.name || imageCurrentModel.value?.alias || "选择模型",
);
const imageAspectRatio = computed(() => {
    const [w, h] = imageSizeWH.value || [9, 16];
    return w > 0 && h > 0 ? w / h : 9 / 16;
});
const {
    optimizePrompt: videoOptimize,
    ratio: videoRatio,
    resolution: videoResolution,
    sizeLabel: videoSizeLabel,
    aspectRatio: videoAspectRatio,
    refImage: videoRefImage,
    currentModelName: videoCurrentModelName,
    optimizeKeywords: optimizeVideoKeywords,
    submit: submitVideo,
    cancelPending: cancelVideoPending,
    resetConversation: resetVideoSession,
} = videoWb;
const showImageModelSheet = ref(false);
const showImageSizeSheet = ref(false);
const showImageCaseSheet = ref(false);
const showVideoSizeSheet = ref(false);
const showPptPagesSheet = ref(false);
const showPptSceneSheet = ref(false);
const showPptModelSheet = ref(false);
/** 键盘刚收起 / 切模式时忽略误触打开弹层 */
let studioSheetLockUntil = 0;
const lockStudioSheets = (ms = 560) => {
    studioSheetLockUntil = Date.now() + ms;
};
const openStudioSheet = (open: () => void) => {
    if (Date.now() < studioSheetLockUntil) return;
    open();
};
const openImageModelSheet = () => openStudioSheet(() => (showImageModelSheet.value = true));
const openImageSizeSheet = () => openStudioSheet(() => (showImageSizeSheet.value = true));
const openImageCaseSheet = () => openStudioSheet(() => (showImageCaseSheet.value = true));
const openPptModelSheet = () => openStudioSheet(() => (showPptModelSheet.value = true));
const openVideoSizeSheet = () => openStudioSheet(() => (showVideoSizeSheet.value = true));
const openHistorySheet = () => openStudioSheet(() => (showHistory.value = true));

/** 键盘收起时锁定弹层，避免收键盘点穿透打开模型选择 */
const onStudioKeyboardHeightChange = (res: { height: number }) => {
    if (res.height === 0) lockStudioSheets();
};

const {
    nextPage: mapNextPage,
    exhausted: mapExhausted,
    lastMessageId: mapLastMessageId,
    submit: submitMap,
    loadMore: loadMoreMap,
    exportExcel: exportMapExcel,
    resetConversation: resetMapSession,
    cancelPending: cancelMapPending,
} = mapWb;
const {
    pageRange: pptPageRange,
    pageCount: pptPageCount,
    scene: pptScene,
    followupOn: pptFollowupOn,
    selectedModelId: pptSelectedModelId,
    pptModels,
    currentModel: pptCurrentModel,
    fetchFollowup: fetchPptFollowup,
    generateFromOptions: generatePptFromOptions,
    regenerateSlide: regeneratePptSlide,
    cancelPending: cancelPptPending,
    resetConversation: resetPptSession,
} = pptWb;
const pptCurrentModelName = computed(() => pptCurrentModel.value?.name || pptCurrentModel.value?.alias || "image-2");

const setImageOptimize = (v: boolean) => {
    imageOptimize.value = v;
};
const setImageModelId = (v: string) => {
    imageSelectedModelId.value = v;
    // 切到 seedream4.0 等模型时自动把张数压回上限
    clampImageCount(imageCount.value);
};
const setPptModelId = (v: string) => {
    pptSelectedModelId.value = v;
};
const handleImageSizeConfirm = (payload: { ratio: string; resolution: string; count: number }) => {
    imageRatio.value = payload.ratio as any;
    imageResolution.value = payload.resolution;
    clampImageCount(payload.count);
};
/** 优秀案例：填充提示词 + 替换参考图（对齐 PC） */
const handleChooseImageCase = (payload: { title: string; pic: string }) => {
    const title = String(payload?.title || "").trim();
    const pic = String(payload?.pic || "").trim();
    if (title) imageStudioRef.value?.setUserInput?.(title);
    if (pic) applyCaseRef(pic);
};
const handleVideoSizeConfirm = (payload: { ratio: string; resolution: string }) => {
    videoRatio.value = payload.ratio;
    videoResolution.value = payload.resolution;
};
const setPptPageCount = (v: number) => {
    pptPageCount.value = v as any;
};
const setPptPageRange = (v: string) => {
    pptPageRange.value = v as any;
};
const setPptScene = (v: string) => {
    pptScene.value = v;
};
const setPptFollowupOn = (v: boolean) => {
    pptFollowupOn.value = v;
};
const setVideoOptimize = (v: boolean) => {
    videoOptimize.value = v;
};

/** 切工具时间戳：用于忽略切模式瞬间的误触发发送 */
let workbenchModeSwitchAt = 0;
const handleWorkbenchModeChange = async (mode: WorkbenchMode | string) => {
    // 先收起键盘，避免切到工作室后残留点击/confirm 误触发发送
    uni.hideKeyboard();
    chattingRef.value?.hideKeyboard?.();
    workbenchModeSwitchAt = Date.now();
    lockStudioSheets();
    const next = mode as WorkbenchMode;
    if (next === WorkbenchMode.Chat) {
        // 退出图像/视频等模式时中止进行中的任务，避免结果写回
        abortChatStream();
        clearChatContent();
        // 退出要回到初始首页：父层模型/智能体不清会让对话面板既不是首页也没有消息，显示成空白页
        fileList.value = [];
        currAgent.value = null;
        currModel.value = null;
        await nextTick();
        chattingRef.value?.handleAgentClear();
        chattingRef.value?.syncDefaultModel?.(true);
        return;
    }
    if (mountedDevices.value.length) {
        uni.$u.toast("手机操控模式下不可切换工作台模式");
        return;
    }
    // 切换工作台模式：取消旧任务并清空消息；对话流也要断，否则回调会操作已卸载的对话面板
    if (next !== workbenchMode.value) {
        abortChatStream();
        cancelWorkbenchJobs();
        chatContentList.value = [];
    }
    // 进入专用模式时清空对话附件，避免混用聊天文件与参考图
    fileList.value = [];
    enterMode(next);
};

const handleAddWorkbenchRef = (url: string) => {
    if (workbenchMode.value === WorkbenchMode.Image) {
        addRefImage(url);
        return;
    }
    if (workbenchMode.value === WorkbenchMode.Video) {
        videoRefImage.value = url;
    }
};

const handleRemoveWorkbenchRef = (index: number) => {
    if (workbenchMode.value === WorkbenchMode.Image) {
        removeRefImage(index);
        return;
    }
    if (workbenchMode.value === WorkbenchMode.Video) {
        videoRefImage.value = "";
    }
};

const handleRemoveVideoRef = () => {
    videoRefImage.value = "";
};

/** 输入框加号 / 工具栏上传：按 PC accept 分流 */
const handleWorkbenchUpload = () => {
    if (workbenchMode.value === WorkbenchMode.Map) return;
    if (workbenchMode.value === WorkbenchMode.Image) {
        imageStudioRef.value?.pickRefImage?.();
        return;
    }
    if (workbenchMode.value === WorkbenchMode.Video) {
        videoStudioRef.value?.pickRefImage?.();
        return;
    }
};

/** 取消进行中的工作台生成（轮询 + 丢弃结果写入） */
const cancelWorkbenchJobs = () => {
    workbenchJobSeq += 1;
    isReceiving.value = false;
    isStopChat.value = false;
    cancelImagePending();
    cancelVideoPending();
    cancelPptPending();
    cancelMapPending();
};

const resetWorkbenchSessions = (exit = true) => {
    cancelWorkbenchJobs();
    resetImageSession();
    resetVideoSession();
    resetMapSession();
    resetPptSession();
    if (exit) exitMode();
};

const pushWorkbenchUserMessage = (text: string): string[] => {
    const isImage = workbenchMode.value === WorkbenchMode.Image;
    // 发送时快照参考图到用户消息（气泡内图+文同显），随后清空输入区缩略条
    const refImages = isImage ? [...imageRefImages.value] : [];
    const refCount = refImages.length || (workbenchMode.value === WorkbenchMode.Video && videoRefImage.value ? 1 : 0);
    chatContentList.value.push({
        type: 1,
        message: text,
        fileList: [],
        refImages,
        refCount: refCount || undefined,
    });
    if (isImage) imageRefImages.value = [];
    return refImages;
};

const createWorkbenchAssistant = () => {
    const result = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "",
        error: "",
        reasoning_content: "",
        consume_tokens: {},
    });
    chatContentList.value.push(result);
    return result;
};

/** 用指定提示词执行图像生成，写入结果卡（对齐 HTML doGenerate，无文案气泡） */
const runImageGenerate = async (result: ChatMessage, prompt: string, jobId?: number) => {
    const jid = jobId ?? workbenchJobSeq;
    result.loading = true;
    result.reply = "";
    result.error = "";
    result.genPrompt = prompt;
    result.workbench = {
        kind: "image",
        status: "generating",
        count: imageCount.value,
        modelName: imageCurrentModelName.value,
        ratio: imageRatio.value,
        sizeLabel: imageSizeLabel.value,
        aspectRatio: imageAspectRatio.value,
        urls: [],
    };
    try {
        const res = await submitImage(prompt, result.refImages || []);
        if (!isWorkbenchJobActive(jid)) return;
        const n = res.urls?.length || 0;
        result.loading = false;
        result.workbench = {
            kind: "image",
            status: "done",
            count: n || imageCount.value,
            modelName: imageCurrentModelName.value,
            ratio: imageRatio.value,
            sizeLabel: imageSizeLabel.value,
            aspectRatio: imageAspectRatio.value,
            title: n ? `已生成 ${n} 张 · ${imageSizeLabel.value}` : imageSizeLabel.value,
            urls: res.urls,
        };
    } catch (error: any) {
        if (!isWorkbenchJobActive(jid) || isWorkbenchAbortError(error)) return;
        result.loading = false;
        result.error = error?.message || error || "生成失败，请重试";
        result.workbench = {
            kind: "image",
            status: "error",
            count: imageCount.value,
            modelName: imageCurrentModelName.value,
            ratio: imageRatio.value,
            sizeLabel: imageSizeLabel.value,
            aspectRatio: imageAspectRatio.value,
            urls: [],
        };
        throw error;
    }
};

const handleImageOptimizeUpdate = (index: number, text: string) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "prompt-optimize") return;
    item.workbench.text = text;
};

const handleImageOptimizeRegen = async (index: number) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "prompt-optimize") return;
    if (isReceiving.value || item.workbench.regenerating) return;
    item.workbench.regenerating = true;
    try {
        const seed = item.workbench.original || item.workbench.text || "";
        item.workbench.text = await optimizeImageKeywords(seed);
        uni.$u.toast("已重新优化");
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "优化失败");
    } finally {
        item.workbench.regenerating = false;
        scrollActiveBottom();
    }
};

/** 用卡片里编辑后的优化文案生图（对齐 HTML useOptimized） */
const handleImageOptimizeConfirm = async (index: number, text: string) => {
    const prompt = String(text || "").trim();
    if (!prompt) {
        uni.$u.toast("请输入提示词");
        return;
    }
    if (isReceiving.value) return;

    // 移除优化卡，再追加生成结果消息
    const refImages = chatContentList.value[index]?.workbench?.refImages || [];
    chatContentList.value.splice(index, 1);
    const result = createWorkbenchAssistant();
    result.refImages = refImages;
    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    isStopChat.value = false;
    try {
        await runImageGenerate(result, prompt, jobId);
    } catch {
        /* runImageGenerate 已写入 error 状态 */
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

const handleImageOptimizeCancel = (index: number) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "prompt-optimize") return;
    chatContentList.value.splice(index, 1);
};

/** 结果卡「重新生成」 */
const handleImageRegenerate = async (index: number) => {
    const item = chatContentList.value[index];
    if (!item || item.type !== 2) return;
    const prompt = String(item.genPrompt || "").trim();
    if (!prompt) {
        uni.$u.toast("缺少提示词，请重新发送");
        return;
    }
    if (isReceiving.value) return;
    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    try {
        await runImageGenerate(item, prompt, jobId);
    } catch {
        /* 已写入 error */
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

/** 用指定提示词执行视频生成（对齐图像：无文案气泡，只渲染结果卡） */
const runVideoGenerate = async (result: ChatMessage, prompt: string, jobId?: number) => {
    const jid = jobId ?? workbenchJobSeq;
    result.loading = true;
    result.reply = "";
    result.error = "";
    result.genPrompt = prompt;
    result.workbench = {
        kind: "video",
        status: "generating",
        modelName: videoCurrentModelName.value,
        ratio: videoRatio.value,
        sizeLabel: videoSizeLabel.value,
        aspectRatio: videoAspectRatio.value,
        urls: [],
    };
    try {
        const res = await submitVideo(prompt);
        if (!isWorkbenchJobActive(jid)) return;
        const n = res.urls?.length || 0;
        result.loading = false;
        result.workbench = {
            kind: "video",
            status: "done",
            modelName: videoCurrentModelName.value,
            ratio: res.ratio || videoRatio.value,
            resolution: res.resolution || videoResolution.value,
            sizeLabel: `${res.ratio || videoRatio.value} · ${res.resolution || videoResolution.value}`,
            aspectRatio: videoAspectRatio.value,
            title: n
                ? `视频已生成 · ${res.ratio || videoRatio.value} · ${res.resolution || videoResolution.value}`
                : videoSizeLabel.value,
            urls: res.urls,
        };
    } catch (error: any) {
        if (!isWorkbenchJobActive(jid) || isWorkbenchAbortError(error)) return;
        result.loading = false;
        result.error = error?.message || error || "生成失败，请重试";
        result.workbench = {
            kind: "video",
            status: "error",
            modelName: videoCurrentModelName.value,
            ratio: videoRatio.value,
            sizeLabel: videoSizeLabel.value,
            aspectRatio: videoAspectRatio.value,
            urls: [],
        };
        throw error;
    }
};

const handleVideoOptimizeUpdate = (index: number, text: string) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "prompt-optimize") return;
    item.workbench.text = text;
};

const handleVideoOptimizeRegen = async (index: number) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "prompt-optimize") return;
    if (isReceiving.value || item.workbench.regenerating) return;
    item.workbench.regenerating = true;
    try {
        const seed = item.workbench.original || item.workbench.text || "";
        item.workbench.text = await optimizeVideoKeywords(seed);
        uni.$u.toast("已重新优化");
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "优化失败");
    } finally {
        item.workbench.regenerating = false;
        scrollActiveBottom();
    }
};

const handleVideoOptimizeConfirm = async (index: number, text: string) => {
    const prompt = String(text || "").trim();
    if (!prompt) {
        uni.$u.toast("请输入提示词");
        return;
    }
    if (isReceiving.value) return;
    chatContentList.value.splice(index, 1);
    const result = createWorkbenchAssistant();
    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    isStopChat.value = false;
    try {
        await runVideoGenerate(result, prompt, jobId);
    } catch {
        /* 已写入 error */
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

const handleVideoOptimizeCancel = (index: number) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "prompt-optimize") return;
    chatContentList.value.splice(index, 1);
};

const handleVideoRegenerate = async (index: number) => {
    const item = chatContentList.value[index];
    if (!item || item.type !== 2) return;
    const prompt = String(item.genPrompt || "").trim();
    if (!prompt) {
        uni.$u.toast("缺少提示词，请重新发送");
        return;
    }
    if (isReceiving.value) return;
    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    try {
        await runVideoGenerate(item, prompt, jobId);
    } catch {
        /* 已写入 error */
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

const handleWorkbenchContentPost = async (userInput?: string) => {
    const text = String(userInput || "").trim();
    // 切换工具后短时间内的空发送视为误触，不提示
    if (!text) {
        if (Date.now() - workbenchModeSwitchAt < 500) return;
        uni.$u.toast("请输入内容");
        return;
    }
    if (isReceiving.value) return;

    const refImages = pushWorkbenchUserMessage(text);
    isReceiving.value = true;
    isStopChat.value = false;

    try {
        if (workbenchMode.value === WorkbenchMode.Image) {
            // 对齐 HTML：开启优化 → thinking → 可编辑卡 → 确认后生图
            const jobId = beginWorkbenchJob();
            scrollActiveBottom();
            if (imageOptimize.value) {
                const thinking = reactive<ChatMessage>({
                    type: 2,
                    loading: true,
                    reply: "",
                    workbench: { kind: "prompt-optimizing" },
                });
                chatContentList.value.push(thinking);
                scrollActiveBottom();
                try {
                    const optimized = await optimizeImageKeywords(text);
                    if (!isWorkbenchJobActive(jobId)) return;
                    const idx = chatContentList.value.indexOf(thinking);
                    const card: ChatMessage = {
                        type: 2,
                        reply: "",
                        workbench: {
                            kind: "prompt-optimize",
                            text: optimized,
                            original: text,
                            refImages,
                        },
                    };
                    if (idx >= 0) chatContentList.value.splice(idx, 1, card);
                    else chatContentList.value.push(card);
                } catch (error: any) {
                    if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) return;
                    const idx = chatContentList.value.indexOf(thinking);
                    if (idx >= 0) chatContentList.value.splice(idx, 1);
                    const fallback = createWorkbenchAssistant();
                    fallback.refImages = refImages;
                    await runImageGenerate(fallback, text, jobId);
                }
            } else {
                const result = createWorkbenchAssistant();
                result.refImages = refImages;
                scrollActiveBottom();
                await runImageGenerate(result, text, jobId);
            }
        } else if (workbenchMode.value === WorkbenchMode.Video) {
            // 对齐图像：开启优化 → thinking → 可编辑卡 → 确认后生视频
            const jobId = beginWorkbenchJob();
            scrollActiveBottom();
            if (videoOptimize.value) {
                const thinking = reactive<ChatMessage>({
                    type: 2,
                    loading: true,
                    reply: "",
                    workbench: { kind: "prompt-optimizing" },
                });
                chatContentList.value.push(thinking);
                scrollActiveBottom();
                try {
                    const optimized = await optimizeVideoKeywords(text);
                    if (!isWorkbenchJobActive(jobId)) return;
                    const idx = chatContentList.value.indexOf(thinking);
                    const card: ChatMessage = {
                        type: 2,
                        reply: "",
                        workbench: {
                            kind: "prompt-optimize",
                            text: optimized,
                            original: text,
                        },
                    };
                    if (idx >= 0) chatContentList.value.splice(idx, 1, card);
                    else chatContentList.value.push(card);
                } catch (error: any) {
                    if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) return;
                    const idx = chatContentList.value.indexOf(thinking);
                    if (idx >= 0) chatContentList.value.splice(idx, 1);
                    const fallback = createWorkbenchAssistant();
                    await runVideoGenerate(fallback, text, jobId);
                }
            } else {
                const result = createWorkbenchAssistant();
                scrollActiveBottom();
                await runVideoGenerate(result, text, jobId);
            }
        } else if (workbenchMode.value === WorkbenchMode.Map) {
            const jobId = beginWorkbenchJob();
            const thinking = reactive<ChatMessage>({
                type: 2,
                loading: true,
                reply: "",
                workbench: { kind: "map-searching" },
            });
            chatContentList.value.push(thinking);
            scrollActiveBottom();
            try {
                const res = await submitMap(text);
                if (!isWorkbenchJobActive(jobId)) {
                    const dead = chatContentList.value.indexOf(thinking);
                    if (dead >= 0) chatContentList.value.splice(dead, 1);
                    return;
                }
                const idx = chatContentList.value.indexOf(thinking);
                const card: ChatMessage = {
                    type: 2,
                    loading: false,
                    reply: "",
                    error: res.isError ? res.errorMessage || "抓取失败" : "",
                    workbench: res.isError
                        ? undefined
                        : {
                              kind: "map",
                              title: `共 ${res.totalCount || res.cards.length} 条`,
                              cards: res.cards,
                              query: res.query || text,
                              pageLabel: res.pageLabel,
                          },
                };
                if (idx >= 0) chatContentList.value.splice(idx, 1, card);
                else chatContentList.value.push(card);
            } catch (error: any) {
                if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) {
                    const dead = chatContentList.value.indexOf(thinking);
                    if (dead >= 0) chatContentList.value.splice(dead, 1);
                    if (isWorkbenchAbortError(error)) return;
                    throw error;
                }
                throw error;
            }
        } else if (workbenchMode.value === WorkbenchMode.Ppt) {
            const jobId = beginWorkbenchJob();
            const pageCount = resolvePptPageCount(pptPageRange.value);
            const sceneSnap = pptScene.value;
            if (pptFollowupOn.value) {
                const thinking = reactive<ChatMessage>({
                    type: 2,
                    loading: true,
                    reply: "",
                    workbench: {
                        kind: "ppt-thinking",
                        text: "AI 正在思考，生成定制化问卷…",
                        topic: text,
                        pageCount,
                        pptScene: sceneSnap,
                    },
                });
                chatContentList.value.push(thinking);
                scrollActiveBottom();
                try {
                    const fu = await fetchPptFollowup(text);
                    if (!isWorkbenchJobActive(jobId)) {
                        const dead = chatContentList.value.indexOf(thinking);
                        if (dead >= 0) chatContentList.value.splice(dead, 1);
                        return;
                    }
                    const idx = chatContentList.value.indexOf(thinking);
                    const card: ChatMessage = {
                        type: 2,
                        loading: false,
                        reply: "",
                        workbench: {
                            kind: "ppt-followup",
                            topic: text,
                            pageCount,
                            pptScene: sceneSnap,
                            description: fu.description,
                            pptType: fu.pptType,
                            fields: fu.fields,
                        },
                    };
                    if (fu.fields.length) {
                        if (idx >= 0) chatContentList.value.splice(idx, 1, card);
                        else chatContentList.value.push(card);
                    } else {
                        // 无问卷字段：直接生成
                        if (idx >= 0) chatContentList.value.splice(idx, 1);
                        await runPptGenerate(
                            {
                                topic: text,
                                pageCount,
                                pptScene: sceneSnap,
                            },
                            jobId,
                        );
                    }
                } catch (error: any) {
                    if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) {
                        const dead = chatContentList.value.indexOf(thinking);
                        if (dead >= 0) chatContentList.value.splice(dead, 1);
                        if (isWorkbenchAbortError(error)) return;
                        throw error;
                    }
                    // 问卷失败降级：直接生成
                    const idx = chatContentList.value.indexOf(thinking);
                    if (idx >= 0) chatContentList.value.splice(idx, 1);
                    await runPptGenerate(
                        {
                            topic: text,
                            pageCount,
                            pptScene: sceneSnap,
                        },
                        jobId,
                    );
                }
            } else {
                await runPptGenerate(
                    {
                        topic: text,
                        pageCount,
                        pptScene: sceneSnap,
                    },
                    jobId,
                );
            }
        }
    } catch (error: any) {
        if (isWorkbenchAbortError(error)) return;
        const last = chatContentList.value[chatContentList.value.length - 1];
        if (last?.type === 2) {
            last.loading = false;
            last.error = error?.message || error || "生成失败，请重试";
            last.reply = "";
            if (last.workbench?.kind === "ppt-thinking" || last.workbench?.kind === "ppt-followup") {
                last.workbench = undefined;
            }
        } else {
            uni.$u.toast(error?.message || error || "生成失败，请重试");
        }
    } finally {
        isReceiving.value = false;
        scrollActiveBottom();
    }
};

/** PPT 逐页生成：写入同一条结果卡 */
const runPptGenerate = async (
    opts: {
        topic: string;
        pageCount: number;
        pptScene?: string;
        summary?: Record<string, string>;
        pptType?: string;
        replaceIndex?: number;
    },
    jobId: number,
) => {
    const result = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "",
        workbench: {
            kind: "ppt",
            status: "generating",
            topic: opts.topic,
            pageCount: opts.pageCount,
            pptScene: opts.pptScene,
            summary: opts.summary,
            pptType: opts.pptType || "",
            slides: [],
            urls: [],
        },
    });
    if (opts.replaceIndex != null && opts.replaceIndex >= 0) {
        chatContentList.value.splice(opts.replaceIndex, 1, result);
    } else {
        chatContentList.value.push(result);
    }
    scrollActiveBottom();

    try {
        const res = await generatePptFromOptions({
            topic: opts.topic,
            pageCount: opts.pageCount,
            pptScene: opts.pptScene,
            summary: opts.summary,
            pptType: opts.pptType,
            onSlidesUpdate: (slides) => {
                if (!isWorkbenchJobActive(jobId)) return;
                if (result.workbench) {
                    result.workbench.slides = slides;
                    result.workbench.urls = slides.map((s) => s.url).filter(Boolean) as string[];
                }
                scrollActiveBottom();
            },
        });
        if (!isWorkbenchJobActive(jobId)) return;
        result.loading = false;
        result.reply = "";
        result.workbench = {
            kind: "ppt",
            status: "done",
            topic: res.topic,
            pageCount: res.pageCount,
            pptScene: res.pptScene,
            summary: res.summary,
            pptType: res.pptType || "",
            slides: res.slides,
            urls: res.urls,
        };
    } catch (error: any) {
        if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) {
            const dead = chatContentList.value.indexOf(result);
            if (dead >= 0 && (isWorkbenchAbortError(error) || !isWorkbenchJobActive(jobId))) {
                chatContentList.value.splice(dead, 1);
            }
            if (isWorkbenchAbortError(error) || !isWorkbenchJobActive(jobId)) return;
        }
        result.loading = false;
        result.error = error?.message || error || "生成失败，请重试";
        result.reply = "";
        if (result.workbench) result.workbench.status = "error";
        throw error;
    }
};

const handlePptFollowupConfirm = async (
    index: number,
    payload: {
        answers: Record<string, any>;
        summary: Record<string, string>;
        pageCount?: number;
    },
) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "ppt-followup") return;
    if (isReceiving.value) return;

    const topic = String(item.workbench.topic || "").trim();
    if (!topic) {
        uni.$u.toast("缺少主题");
        return;
    }
    const pageCount = resolvePptPageCount(pptPageRange.value, payload.pageCount || item.workbench.pageCount);
    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    try {
        await runPptGenerate(
            {
                topic,
                pageCount,
                pptScene: item.workbench.pptScene || pptScene.value,
                summary: payload.summary,
                pptType: item.workbench.pptType,
                replaceIndex: index,
            },
            jobId,
        );
    } catch (error: any) {
        if (!isWorkbenchAbortError(error)) {
            /* runPptGenerate 已写 error */
        }
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

const handlePptFollowupCancel = (index: number) => {
    // 取消：移除问卷卡 + 上一条用户消息
    if (index < 0 || index >= chatContentList.value.length) return;
    const start = Math.max(0, index - 1);
    chatContentList.value.splice(start, index - start + 1);
};

const handlePptRegenerate = async (index: number) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "ppt") return;
    if (isReceiving.value) return;
    const topic = String(item.workbench.topic || "").trim();
    if (!topic) {
        uni.$u.toast("缺少主题");
        return;
    }
    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    try {
        await runPptGenerate(
            {
                topic,
                pageCount: Number(item.workbench.pageCount) || resolvePptPageCount(pptPageRange.value),
                pptScene: item.workbench.pptScene || pptScene.value,
                summary: item.workbench.summary,
                pptType: item.workbench.pptType,
                replaceIndex: index,
            },
            jobId,
        );
    } catch {
        /* 已写入 error */
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

const handlePptRegenerateSlide = async (index: number, slideIndex: number) => {
    const item = chatContentList.value[index];
    if (!item?.workbench || item.workbench.kind !== "ppt") return;
    if (isReceiving.value) return;
    const slides = item.workbench.slides || [];
    const topic = String(item.workbench.topic || "").trim();
    if (!topic || !slides[slideIndex]) return;

    const jobId = beginWorkbenchJob();
    isReceiving.value = true;
    item.loading = true;
    try {
        await regeneratePptSlide({
            topic,
            slides,
            index: slideIndex,
            pptType: item.workbench.pptType,
            summary: item.workbench.summary,
        });
        if (!isWorkbenchJobActive(jobId)) return;
        item.workbench.urls = slides.map((s: any) => s.url).filter(Boolean);
    } catch (error: any) {
        if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) return;
        uni.$u.toast(error?.message || error || "单页重生失败");
    } finally {
        if (isWorkbenchJobActive(jobId)) {
            isReceiving.value = false;
            item.loading = false;
        }
        scrollActiveBottom();
    }
};

const handleMapLoadMore = async () => {
    if (isReceiving.value) return;
    const jobId = beginWorkbenchJob();
    const thinking = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "",
        workbench: { kind: "map-searching" },
    });
    chatContentList.value.push(thinking);
    isReceiving.value = true;
    scrollActiveBottom();
    try {
        const res = await loadMoreMap();
        if (!isWorkbenchJobActive(jobId)) {
            const dead = chatContentList.value.indexOf(thinking);
            if (dead >= 0) chatContentList.value.splice(dead, 1);
            return;
        }
        const idx = chatContentList.value.indexOf(thinking);
        const card: ChatMessage = {
            type: 2,
            loading: false,
            reply: "",
            error: res.isError ? res.errorMessage || "抓取失败" : "",
            workbench: res.isError
                ? undefined
                : {
                      kind: "map",
                      title: `共 ${res.totalCount || res.cards.length} 条`,
                      cards: res.cards,
                      query: res.query || mapWb.lastQuery.value,
                      pageLabel: res.pageLabel,
                  },
        };
        if (idx >= 0) chatContentList.value.splice(idx, 1, card);
        else chatContentList.value.push(card);
    } catch (error: any) {
        if (!isWorkbenchJobActive(jobId) || isWorkbenchAbortError(error)) {
            const dead = chatContentList.value.indexOf(thinking);
            if (dead >= 0) chatContentList.value.splice(dead, 1);
            return;
        }
        const idx = chatContentList.value.indexOf(thinking);
        const card: ChatMessage = {
            type: 2,
            loading: false,
            reply: "",
            error: error?.message || error || "获取失败",
        };
        if (idx >= 0) chatContentList.value.splice(idx, 1, card);
        else chatContentList.value.push(card);
    } finally {
        if (isWorkbenchJobActive(jobId)) isReceiving.value = false;
        scrollActiveBottom();
    }
};

const handleMapExport = async () => {
    try {
        await exportMapExcel();
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "导出失败");
    }
};

// 初始化Tab列表
// 一级 tab 类型
const ChatTabType = {
    CHAT: 0,
    AGENT: 1,
    KNOWLEDGE: 3,
} as const;

const tabList = ref<any[]>([
    { name: "对话", type: ChatTabType.CHAT },
    { name: "智能体", type: ChatTabType.AGENT },
    { name: "知识库", type: ChatTabType.KNOWLEDGE },
]);
const currTab = ref(0);
const currType = ref(tabList.value[0].type);

const prevTab = ref<number | null>(null);

const handleTabChange = async (index: number) => {
    currType.value = tabList.value[index].type;
    currTab.value = index;
    if (currTab.value == 1) {
        // setTimeout(() => {
        //     // pagingRobotRef.value?.reload();
        // }, 300);
    }
};

// 流式请求读取器
let streamReader: any = null;
let phoneAgentPollingEnd: any = null;
let phoneAgentResult: ChatMessage | null = null;

const PHONE_AGENT_TERMINAL_STATUS = ["completed", "failed", "canceled"] as string[];

// 聊天记录请求参数
const chatLogParams = reactive<ChatLogParams>({
    page_no: 1,
    page_size: 1500,
    assistant_id: 0,
});

/**
 * 网络状态更新处理
 */
const handleUpdateNetwork = (value: boolean) => {
    isNetwork.value = value;
};

/**
 * 选择模型处理
 */
const handleSelectModel = (item: any) => {
    if (mountedDevices.value.length) {
        uni.$u.toast("已挂载设备时不能选择大模型");
        return;
    }
    currModel.value = item;
    chattingRef.value?.handleModel(item);
};

/**
 * 历史记录选择处理
 */
const handleSelectRecord = async (item: any) => {
    if (mountedDevices.value.length && (item?.conversation_id || item?.last_task_id || item?.device_code)) {
        await handleSelectPhoneAgentRecord(item);
        return;
    }

    // 工作台 draw / 地图会话恢复
    if (!isChatMode.value) {
        await handleSelectWorkbenchRecord(item);
        return;
    }

    const { robot_id, avatar, robot_name, task_id } = item;
    chattingRef.value?.setAgentConfig({
        id: robot_id,
        avatar,
        name: robot_name,
    });
    taskId.value = task_id;
    await getChatList();
    showHistory.value = false;
};

/** 从 draw 任务参数拼结果卡标题 */
const buildDrawHistoryTitle = (task: any, urls: string[], kind: string) => {
    const params = task?.params && typeof task.params === "object" ? task.params : {};
    const ratio = String(params.ratio || "").trim();
    const resolution = String(params.resolution || "").trim();
    const n = urls.length || Number(params.n || params.count || 0) || 0;
    if (kind === "image") {
        const parts = [n > 0 ? `已生成 ${n} 张` : "已生成", ratio || "", resolution || ""].filter(Boolean);
        return parts.join(" · ");
    }
    if (kind === "video") {
        return [n > 0 ? `已生成 ${n} 个视频` : "视频已生成", ratio || ""].filter(Boolean).join(" · ");
    }
    return task?.model_name || task?.model || "";
};

/** 恢复图片/视频/PPT/地图历史会话到消息流 */
const handleSelectWorkbenchRecord = async (item: any) => {
    // 先取消进行中的生成，避免旧结果写进新会话
    cancelWorkbenchJobs();
    showHistory.value = false;
    try {
        uni.showLoading({ title: "加载中...", mask: true });
        const nextList: ChatMessage[] = [];

        if (workbenchMode.value === WorkbenchMode.Map) {
            const cid = String(item.conversation_id || item.id || "");
            if (!cid) throw new Error("会话无效");
            mapWb.conversationId.value = cid;
            const res: any = await getMapLeadMessages({ conversation_id: cid, page_no: 1, page_size: 50 });
            const messages = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
            for (const msg of messages) {
                if (String(msg.role || "").toLowerCase() === "user") {
                    nextList.push({ type: 1, message: msg.content || "", fileList: [] });
                } else {
                    const normalized = normalizeMapLeadChatResult({ assistant_message: msg, conversation_id: cid }, "");
                    nextList.push({
                        type: 2,
                        reply: "",
                        error: normalized.isError ? normalized.errorMessage || "抓取失败" : "",
                        workbench: normalized.isError
                            ? undefined
                            : {
                                  kind: "map",
                                  cards: normalized.cards,
                                  title: `共 ${normalized.totalCount || normalized.cards.length} 条`,
                                  query: normalized.query,
                                  pageLabel: "",
                              },
                    });
                    if (normalized.messageId) mapWb.lastMessageId.value = normalized.messageId;
                    mapWb.nextPage.value = normalized.nextPage;
                    mapWb.exhausted.value = normalized.exhausted;
                    if (normalized.query) mapWb.lastQuery.value = normalized.query;
                }
            }
        } else {
            const convId = item.id ?? item.conversation_id;
            if (convId == null || convId === "") throw new Error("会话无效");
            const detail = normalizeConversationDetail(await drawConversationDetail({ id: convId }));
            if (!detail) throw new Error("会话不存在");
            if (!detail.messages.length) throw new Error("该会话暂无消息");

            if (workbenchMode.value === WorkbenchMode.Image) {
                imageWb.conversationId.value = detail.id;
            } else if (workbenchMode.value === WorkbenchMode.Video) {
                videoWb.conversationId.value = detail.id;
            } else if (workbenchMode.value === WorkbenchMode.Ppt) {
                pptWb.conversationId.value = detail.id;
            }

            if (workbenchMode.value === WorkbenchMode.Ppt) {
                // PPT：按 user 切轮，其后连续 assistant 归本轮；同页保留最后一次
                let pendingTopic = "";
                let pendingPageCount = 0;
                const pendingSlides = new Map<number, any>();
                const flushPptTurn = () => {
                    if (!pendingTopic && !pendingSlides.size) return;
                    const slides = Array.from(pendingSlides.values()).sort((a, b) => a.page - b.page);
                    nextList.push({
                        type: 1,
                        message: pendingTopic || detail.title || "PPT",
                        fileList: [],
                    });
                    nextList.push({
                        type: 2,
                        reply: "",
                        workbench: {
                            kind: "ppt",
                            status: "done",
                            topic: pendingTopic || detail.title || "PPT",
                            pageCount: pendingPageCount || slides.length || 1,
                            slides,
                            urls: slides.map((s) => s.url).filter(Boolean),
                        },
                    });
                    pendingTopic = "";
                    pendingPageCount = 0;
                    pendingSlides.clear();
                };
                for (const msg of detail.messages) {
                    if (msg.role === "user") {
                        flushPptTurn();
                        pendingTopic = String(msg.content || detail.title || "").trim();
                        const up = msg.params || {};
                        pendingPageCount = Number(up.pages || up.page_count || 0) || 0;
                        continue;
                    }
                    if (msg.role !== "assistant") continue;
                    const params = {
                        ...(msg.task?.params || {}),
                        ...(msg.params || {}),
                    };
                    const urls = getDrawAssetUrls(msg.task);
                    const page = Number(params.ppt_page || params.page || pendingSlides.size + 1) || 1;
                    pendingSlides.set(page, {
                        page,
                        title: String(params.title || msg.content || `第 ${page} 页`),
                        content: String(params.content || ""),
                        url: urls[0] || "",
                        loading: false,
                        error: String(msg.task?.error_msg || ""),
                    });
                }
                flushPptTurn();
            } else {
                const kind = workbenchMode.value === WorkbenchMode.Video ? "video" : "image";
                for (const msg of detail.messages) {
                    if (msg.role === "user") {
                        nextList.push({
                            type: 1,
                            message: msg.content || detail.title || "",
                            fileList: [],
                            refImages: Array.isArray(msg.attachments) ? msg.attachments : [],
                        });
                        continue;
                    }
                    const params = msg.task?.params && typeof msg.task.params === "object" ? msg.task.params : {};
                    const meta = params.metadata && typeof params.metadata === "object" ? params.metadata : {};
                    if (kind === "video") {
                        // 只取视频资源，排除封面图
                        const urls = getDrawVideoUrls(msg.task);
                        const title = buildDrawHistoryTitle(msg.task, urls, kind);
                        const ratio = String(params.aspect_ratio || params.ratio || meta.ratio || videoRatio.value);
                        const resolution = String(params.resolution || meta.resolution || videoResolution.value);
                        const sizeLabel = `${ratio} · ${resolution}`;
                        const arParts = ratio.match(/(\d+)\s*:\s*(\d+)/);
                        const ar =
                            arParts && Number(arParts[1]) > 0 && Number(arParts[2]) > 0
                                ? Number(arParts[1]) / Number(arParts[2])
                                : videoAspectRatio.value;
                        nextList.push({
                            type: 2,
                            reply: "",
                            genPrompt: String(msg.task?.prompt || msg.content || ""),
                            workbench: {
                                kind: "video",
                                status: urls.length ? "done" : "error",
                                urls,
                                title: title || (urls.length ? `视频已生成 · ${sizeLabel}` : sizeLabel),
                                modelName: msg.task?.model_name || msg.task?.model || "",
                                ratio,
                                resolution,
                                sizeLabel,
                                aspectRatio: ar,
                            },
                        });
                        continue;
                    }
                    const urls = getDrawAssetUrls(msg.task);
                    const title = buildDrawHistoryTitle(msg.task, urls, kind);
                    const [bw, bh] = imageSizeWH.value || [9, 16];
                    const ar =
                        Number(params.width) > 0 && Number(params.height) > 0
                            ? Number(params.width) / Number(params.height)
                            : bw / bh;
                    nextList.push({
                        type: 2,
                        reply: "",
                        genPrompt: String(msg.task?.prompt || msg.content || ""),
                        workbench: {
                            kind,
                            status: urls.length ? "done" : "error",
                            urls,
                            title: title || (urls.length ? "生成完成" : msg.content || "生成完成"),
                            count: urls.length || Number(params.n || 1) || 1,
                            modelName: msg.task?.model_name || msg.task?.model || "",
                            ratio: String(params.ratio || imageRatio.value),
                            sizeLabel: imageSizeLabel.value,
                            aspectRatio: ar,
                        },
                    });
                }
            }
        }

        chatContentList.value = nextList;
        await nextTick();
        scrollActiveBottom();
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "加载失败");
    } finally {
        uni.hideLoading();
    }
};

const handleSelectPhoneAgentRecord = async (item: any) => {
    await cancelActivePhoneAgentTask();
    taskId.value = "";
    phoneAgentTaskId.value = item.last_task_id || item.task_id || "";
    phoneAgentLastEventId.value = "";
    phoneAgentSeenMessages.clear();

    const userMessage = item.title || item.message || "";
    chatContentList.value = [
        {
            type: 1,
            message: userMessage,
            fileList: [],
        },
    ];

    const initialStatus = item.last_task_status || item.status || "";
    const result = reactive<ChatMessage>({
        type: 2,
        loading: !PHONE_AGENT_TERMINAL_STATUS.includes(initialStatus),
        reply: "正在加载手机任务记录...",
        error: "",
        reasoning_content: "",
        consume_tokens: {},
    });
    chatContentList.value.push(result);
    phoneAgentResult = result;

    try {
        if (!item.conversation_id) {
            throw new Error("会话信息不完整");
        }

        const detail = await getPhoneAgentConversationDetail({
            conversation_id: item.conversation_id,
        });
        const task = detail?.task || detail?.conversation || item;
        const messages = detail?.messages || [];
        const events = detail?.events || [];
        const lastEvent = events[events.length - 1];
        const taskStatus = task.status || task.last_task_status || initialStatus;

        phoneAgentTaskId.value = task.task_id || task.last_task_id || item.last_task_id || item.task_id || "";
        phoneAgentLastEventId.value = lastEvent?.id || "";

        if (messages.length) {
            result.reply = buildPhoneAgentMessagesReply(messages);
        } else {
            result.reply = buildPhoneAgentHistoryReply(task, events);
        }

        result.loading = !PHONE_AGENT_TERMINAL_STATUS.includes(taskStatus);

        if (result.loading) {
            isReceiving.value = true;
            isStopChat.value = true;
            startPhoneAgentPolling(result);
        } else {
            clearPhoneAgentState();
            resetChat();
        }
    } catch (error: any) {
        handlePhoneAgentError(error, result, "任务记录加载失败");
    }

    showHistory.value = false;
    nextTick(() => chattingRef.value?.scrollToBottom());
};

const buildPhoneAgentMessagesReply = (messages: PhoneAgentMessage[]) => {
    const lines: string[] = [];
    for (const item of messages) {
        const content = String(item?.content || "").trim();
        if (!content) continue;

        const idKey = item?.id ? `id:${item.id}` : "";
        if (idKey && phoneAgentSeenMessages.has(idKey)) continue;
        if (phoneAgentSeenMessages.has(content)) continue;

        if (idKey) phoneAgentSeenMessages.add(idKey);
        phoneAgentSeenMessages.add(content);
        lines.push(content);
    }
    return lines.join("\n\n");
};

const buildPhoneAgentHistoryReply = (task: any, events: PhoneAgentEvent[]) => {
    const lines = collectPhoneAgentMessages(events);
    if (lines.length) return lines.join("\n\n");

    if (task.last_message) {
        phoneAgentSeenMessages.add(task.last_message);
        return task.last_message;
    }

    return [
        `任务状态：${getPhoneAgentStatusText(task.status || task.last_task_status)}`,
        task.device_code ? `执行设备：${task.device_code}` : "",
        task.error_msg ? `失败原因：${task.error_msg}` : "",
    ]
        .filter(Boolean)
        .join("\n");
};

const getPhoneAgentStatusText = (status: string) => {
    const statusMap: Record<string, string> = {
        created: "已创建",
        observing: "观察中",
        model_pending: "思考中",
        waiting_report: "执行中",
        completed: "已完成",
        failed: "失败",
        canceled: "已取消",
    };
    return statusMap[status] || status || "-";
};

// handleQueryRecordList 已迁至 components/history-popup.vue（内部自管 pagingRef + recordLists）

/**
 * 获取聊天记录
 */
const getChatList = async () => {
    try {
        const data = await getChatLog({
            ...chatLogParams,
            task_id: taskId.value,
        });
        const transformData = data?.map((item: ChatMessage) => {
            if (item.type === 1)
                return {
                    ...item,
                    fileList: item?.file_info
                        ? Array.isArray(item.file_info)
                            ? item.file_info
                            : [item.file_info]
                        : [],
                };
            return {
                ...item,
                is_reasoning_finished: true,
                consume_tokens: item.tokens_info,
            };
        });

        chatContentList.value = transformData;

        await nextTick();
        setTimeout(() => {
            chattingRef.value.scrollToBottom();
        }, 150);
    } catch (err) {
        console.error("获取聊天记录失败:", err);
    }
};

/**
 * 发送消息处理
 */
const handleContentPost = async (userInput?: string, isNewChat = false) => {
    // 切模式瞬间小程序可能把点击分发到错误回调，此时的发送一律视为误触
    if (Date.now() - workbenchModeSwitchAt < 500) return;
    if (currAgent.value?.id && !ensureAgentAvailable(currAgent.value)) return;
    if (userTokens.value <= 1) {
        powerInsufficientTip();
        rechargePopupRef.value?.open();
        return;
    }
    if (isReceiving.value) return;

    // 工作台非对话模式：走 draw / map 链路
    if (!isNewChat && !isChatMode.value) {
        await handleWorkbenchContentPost(userInput);
        return;
    }

    const mountedDevices = isNewChat ? [] : chattingRef.value?.getMountedDevices?.() || [];
    if (mountedDevices.length) {
        await handlePhoneAgentContentPost(userInput, mountedDevices);
        return;
    }

    if (!isNewChat) {
        chatContentList.value.push({
            type: 1,
            message: userInput,
            fileList: fileList.value,
        });
    }

    const result = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "",
        error: "",
        reasoning_content: "",
        consume_tokens: {},
    });
    chatContentList.value.push(result);
    isReceiving.value = true;

    try {
        await chatSendTextStream(
            {
                message: userInput,
                task_id: taskId.value,
                open_reasoning: 0,
                is_network_search: isNetwork.value ? 1 : 0,
                file_info: fileList.value[0],
                ...(chattingRef.value?.getChatConfig?.() || {}),
            },
            {
                onstart(reader) {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage(value) {
                    handleStreamMessage(value, result);
                },
                onclose() {
                    handleStreamClose(result);
                },
            },
        );
    } catch (error: any) {
        handleStreamError(error, result);
    }

    nextTick(() => chattingRef.value?.scrollToBottom());
};

/**
 * 挂载设备后，发送到手机智能体任务接口
 */
const handlePhoneAgentContentPost = async (userInput?: string, mountedDevices: any[] = []) => {
    const message = (userInput || "").trim();
    const device = mountedDevices.find((item) => item?.device_code);

    if (!message) {
        uni.$u.toast("请输入要在手机上执行的任务");
        return;
    }
    if (!device?.device_code) {
        uni.$u.toast("请选择有效设备");
        return;
    }
    if (mountedDevices.length > 1) {
        uni.$u.toast("当前任务将下发到第一个挂载设备");
    }

    chatContentList.value.push({
        type: 1,
        message,
        fileList: [],
    });

    const result = reactive<ChatMessage>({
        type: 2,
        loading: true,
        reply: "正在创建手机任务...",
        error: "",
        reasoning_content: "",
        consume_tokens: {},
    });
    chatContentList.value.push(result);
    phoneAgentResult = result;
    isReceiving.value = true;
    isStopChat.value = true;

    try {
        const task = await dispatchPhoneAgentTask({
            message,
            device_code: device.device_code,
            model: "autoglm-phone",
        });

        if (!task?.task_id) throw new Error("任务创建失败");
        if (phoneAgentResult !== result || !isReceiving.value) {
            try {
                await cancelPhoneAgentTask({ task_id: task.task_id });
            } catch (error) {
                console.error("取消手机任务失败:", error);
            }
            return;
        }

        phoneAgentTaskId.value = task.task_id;
        phoneAgentLastEventId.value = "";
        phoneAgentSeenMessages.clear();
        const lastMessage = String(task.last_message || "").trim();
        if (lastMessage) {
            phoneAgentSeenMessages.add(lastMessage);
            result.reply = lastMessage;
        } else {
            result.reply = `任务已下发到设备 ${task.device_code || device.device_code}，等待执行反馈...`;
        }
        startPhoneAgentPolling(result);
    } catch (error: any) {
        handlePhoneAgentError(error, result);
    }

    nextTick(() => chattingRef.value?.scrollToBottom());
};

const startPhoneAgentPolling = (result: ChatMessage) => {
    stopPhoneAgentPolling();
    const poll = async () => {
        try {
            await pollPhoneAgentTask(result);
        } catch (error: any) {
            stopPhoneAgentPolling();
            handlePhoneAgentError(error, result, "任务状态查询失败");
        }
    };
    const { start, end } = usePolling(poll, { time: 2000 });
    phoneAgentPollingEnd = end;
    // 立即拉一次，避免 usePolling 首个 interval 延迟导致首批 messages 丢失
    poll().finally(() => {
        if (phoneAgentTaskId.value && phoneAgentResult === result) {
            start();
        }
    });
};

const pollPhoneAgentTask = async (result: ChatMessage) => {
    if (!phoneAgentTaskId.value) {
        stopPhoneAgentPolling();
        return;
    }

    const params: { task_id: string; last_id?: number | string } = {
        task_id: phoneAgentTaskId.value,
    };
    const lastId = phoneAgentLastEventId.value;
    if (lastId !== "" && lastId !== undefined && lastId !== null) {
        params.last_id = lastId;
    }
    const data = await getPhoneAgentTaskEvents(params);

    if (data?.last_id !== undefined && data?.last_id !== null && data?.last_id !== "") {
        phoneAgentLastEventId.value = data.last_id;
    }

    const messages = data?.messages || [];
    if (messages.length) {
        appendPhoneAgentDisplayMessages(result, messages);
    } else {
        appendPhoneAgentEvents(result, data?.lists || []);
    }

    const taskStatus = data?.task_status || "";
    if (PHONE_AGENT_TERMINAL_STATUS.includes(taskStatus)) {
        await finishPhoneAgentTask(result, taskStatus);
    }
};

const collectPhoneAgentMessages = (events: PhoneAgentEvent[]) => {
    const lines: string[] = [];
    for (const event of events) {
        const message = getPhoneAgentEventMessage(event);
        if (!message || phoneAgentSeenMessages.has(message)) continue;
        phoneAgentSeenMessages.add(message);
        lines.push(message);
    }
    return lines;
};

const appendPhoneAgentDisplayMessages = (result: ChatMessage, messages: PhoneAgentMessage[]) => {
    const lines: string[] = [];
    for (const item of messages) {
        const content = String(item?.content || "").trim();
        if (!content) continue;

        const idKey = item?.id ? `id:${item.id}` : "";
        if (idKey && phoneAgentSeenMessages.has(idKey)) continue;
        if (phoneAgentSeenMessages.has(content)) continue;

        if (idKey) phoneAgentSeenMessages.add(idKey);
        phoneAgentSeenMessages.add(content);
        lines.push(content);
    }
    if (!lines.length) return;

    const current = String(result.reply || "").trim();
    result.reply = current ? `${current}\n\n${lines.join("\n\n")}` : lines.join("\n\n");
    nextTick(() => chattingRef.value?.scrollToBottom());
};

const appendPhoneAgentEvents = (result: ChatMessage, events: PhoneAgentEvent[]) => {
    const lines = collectPhoneAgentMessages(events);
    if (!lines.length) return;

    const current = String(result.reply || "").trim();
    result.reply = current ? `${current}\n\n${lines.join("\n\n")}` : lines.join("\n\n");
    nextTick(() => chattingRef.value?.scrollToBottom());
};

const getPhoneAgentEventMessage = (event: PhoneAgentEvent) => {
    const data = parsePhoneAgentEventData(event.event_data);
    if (!data) return "";

    if (typeof data === "string") return data.trim();
    if (typeof data !== "object") return String(data).trim();

    const text = data.message ?? data.msg ?? "";
    return String(text || "").trim();
};

const parsePhoneAgentEventData = (value: any) => {
    if (!value) return null;
    if (typeof value !== "string") return value;

    try {
        return JSON.parse(value);
    } catch {
        return value;
    }
};

const finishPhoneAgentTask = async (result: ChatMessage, status: string) => {
    stopPhoneAgentPolling();

    let errorMsg = "";
    try {
        const detail = await getPhoneAgentTaskDetail({ task_id: phoneAgentTaskId.value });
        errorMsg = detail?.task?.error_msg || "";
    } catch (error) {
        console.error("查询手机任务详情失败:", error);
    }

    result.loading = false;
    if (status === "canceled") {
        result.error = errorMsg || "任务已取消";
    } else if (status !== "completed") {
        result.error = errorMsg || "任务执行失败";
    }

    clearPhoneAgentState();
    resetChat();
    userStore.getUser();
    nextTick(() => chattingRef.value?.scrollToBottom());
};

const handlePhoneAgentError = (error: any, result: ChatMessage, fallback = "任务创建失败") => {
    const message = String(error?.message || error || fallback);
    result.loading = false;
    result.error = message;
    clearPhoneAgentState();
    resetChat();
    uni.$u.toast(message);
};

const stopPhoneAgentPolling = () => {
    if (phoneAgentPollingEnd) {
        phoneAgentPollingEnd();
        phoneAgentPollingEnd = null;
    }
};

const clearPhoneAgentState = () => {
    stopPhoneAgentPolling();
    phoneAgentTaskId.value = "";
    phoneAgentLastEventId.value = "";
    phoneAgentSeenMessages.clear();
    phoneAgentResult = null;
};

const cancelActivePhoneAgentTask = async () => {
    if (!phoneAgentTaskId.value) {
        stopPhoneAgentPolling();
        if (phoneAgentResult?.loading) {
            phoneAgentResult.loading = false;
            phoneAgentResult.error = "用户已停止任务";
            clearPhoneAgentState();
            resetChat();
        }
        return;
    }

    const currentTaskId = phoneAgentTaskId.value;
    stopPhoneAgentPolling();
    try {
        await cancelPhoneAgentTask({ task_id: currentTaskId });
    } catch (error) {
        console.error("取消手机任务失败:", error);
    }

    if (phoneAgentResult) {
        phoneAgentResult.loading = false;
        phoneAgentResult.error = "用户已停止任务";
    }
    clearPhoneAgentState();
    resetChat();
};

/**
 * 处理流式消息
 */
const handleStreamMessage = (value: string, result: ChatMessage) => {
    value
        .trim()
        .split("data:")
        .forEach((text) => {
            if (!text) return;
            try {
                const data = JSON.parse(text);
                const streamError = parseChatStreamErrorPayload(data);
                if (streamError) {
                    result.error = streamError;
                    result.loading = false;
                    return;
                }
                const { object, content, task_id, usage, check_robot_id, reasoning_content } = data;
                if ((content || reasoning_content) && object === "loading") {
                    if (reasoning_content) {
                        result.reasoning_content += reasoning_content;
                    } else {
                        result.reply += content;
                    }
                }
                if (object === "finished") {
                    result.loading = false;
                    result.consume_tokens = usage;
                    if (!taskId.value) {
                        taskId.value = task_id;
                    }
                    if (check_robot_id) {
                        chattingRef.value?.handleAgent({
                            id: check_robot_id,
                        });
                    }
                    return;
                }
                chattingRef.value?.scrollToBottom();
            } catch (error) {
                console.error("解析流式消息失败:", error);
            }
        });
};

/**
 * 处理流式请求关闭
 */
const handleStreamClose = (result: ChatMessage) => {
    result.loading = false;
    resetChat();
    userStore.getUser();
    setTimeout(() => chattingRef.value?.scrollToBottom(), 600);
};

/**
 * 处理流式请求错误
 */
const handleStreamError = (error: any, result: ChatMessage) => {
    const message = resolveChatErrorMessage(error);
    result.error = message;
    result.loading = false;
    if (error?.errno !== RequestCodeEnum.ABORT) {
        uni.$u.toast(message);
    }
    resetChat();
};

/**
 * 添加新会话
 */
const handleAddSession = async () => {
    await cancelActivePhoneAgentTask();
    resetChat();
    // 工作台模式：新建会话是在当前创作模式下重开一条，不退回对话、也不发问候语
    if (!isChatMode.value) {
        abortChatStream();
        taskId.value = "";
        chatContentList.value = [];
        resetWorkbenchSessions(false);
        return;
    }
    clearChatContent();
    await handleChatClose();
    // clearChatContent 会退出工作台模式，对话面板要等这次渲染才挂载，早发会取不到模型配置
    await nextTick();
    handleContentPost(chatConfig.value.new_chat_prompt, true);
};

/**
 * 重置聊天状态
 */
const resetChat = () => {
    fileList.value = [];
    isReceiving.value = false;
    isStopChat.value = false;
};

/**
 * 清除聊天内容
 */
const clearChatContent = () => {
    cancelWorkbenchJobs();
    taskId.value = "";
    chatContentList.value = [];
    resetWorkbenchSessions();
};

/**
 * 返回聊天
 */
const backChat = async () => {
    await cancelActivePhoneAgentTask();
    resetChat();
    clearChatContent();
    await handleChatClose();
    await nextTick();
    // 回到首页：清空父层模型/智能体，再静默同步子组件默认模型（不 emit，保持 isHome）
    currAgent.value = null;
    currModel.value = null;
    chattingRef.value?.handleAgentClear();
    chattingRef.value?.syncDefaultModel?.(true);
    chattingRef.value?.hideKeyboard();

    if (prevTab.value !== null) {
        currTab.value = prevTab.value;
        currType.value = tabList.value[prevTab.value].type;
        prevTab.value = null;
    }
};

/** 中断进行中的聊天流 */
const abortChatStream = () => {
    //#ifdef H5
    streamReader?.cancel();
    //#endif
    //#ifdef MP-WEIXIN
    streamReader?.abort();
    //#endif
    streamReader = null;
    isReceiving.value = false;
    isStopChat.value = false;
};

/**
 * 关闭聊天
 */
const handleChatClose = async () => {
    await cancelActivePhoneAgentTask();
    abortChatStream();
};

/**
 * 监听文件选择
 */
const watchFile = () => {
    uni.$on("chooseFile", (data: FileInfo[]) => {
        fileList.value = data;
    });
};

// 页面状态
const state = reactive({
    cate_id: "",
});

// AI助理（机器人）部分
const robotCateIndex = ref<number>(0);
const robotCateActiveMenu = ref<number>(-1);
const pagingRobotRef = shallowRef();
const robots = ref<any[]>([]);
const total = ref<number>(0);
const queryParams = reactive({
    type: 3,
    scene_id: "",
    name: "",
});
const queryLoading = ref<boolean>(false);

// AI智能体部分
const pagingCozeRef = shallowRef();
// 智能体二级 tab 类型，AI助理(原一级 tab)并入为最后一个
const AgentCateType = {
    AGENT: 1,
    RPO: 2,
    FLOW: 3,
    ASSISTANT: 4,
} as const;

const agentCateLists = computed(() => {
    const base = [
        { label: "智能体", value: AgentCateType.AGENT },
        { label: "智能体RPO", value: AgentCateType.RPO },
        { label: "工作流", value: AgentCateType.FLOW },
    ];
    if (isShowRobot.value == "1") base.push({ label: "AI助理", value: AgentCateType.ASSISTANT });
    return base;
});
const currAgentType = ref<any>(AgentCateType.AGENT);
const agentList = ref<any[]>([]);

const canUseCurrentAgent = (item: any) => canUseAgent(item, userInfo.value);

const ensureAgentAvailable = (item: any) => {
    if (canUseCurrentAgent(item)) return true;
    uni.$u.toast(AGENT_UNAVAILABLE_TIP);
    return false;
};

const getAgentAccessTagText = (item: any) => getAgentPermissionTagText(item, userInfo.value);

const getAgentAccessTagClass = (item: any) =>
    getAgentAccessStatus(item, userInfo.value) === "free" ? "agent-access-tag--free" : "agent-access-tag--member";

const { optionsData } = useDictOptions<{
    robotCate: any[];
}>({
    robotCate: {
        api: robotCategory,
        params: {
            pageSize: 9999,
            pid: 0,
        },
        transformData: (data) => {
            if (data.lists.length) {
                if (state.cate_id) {
                    robotCateIndex.value = data.lists.findIndex((item: any) => item.id == state.cate_id);
                }
                // pagingRobotRef.value?.reload();
            }
            return data.lists;
        },
    },
});

const robotSubCateIndex = ref<number>(-1);
const currSubId = ref<number>();

/**
 * 搜索机器人
 */
const search = async () => {
    pagingRobotRef.value?.reload();
};

/**
 * 清除搜索关键词并重新加载
 */
const clear = async () => {
    pagingRobotRef.value?.reload();
};

/**
 * 查询机器人列表
 */
const queryRobotList = async (pageNo: number, pageSize: number) => {
    try {
        const { lists = [], count } = await robotLists({
            page_size: pageSize,
            page_no: pageNo,
            ...queryParams,
        });
        total.value = count;
        pagingRobotRef.value?.complete(lists);
        queryLoading.value = false;
    } catch (error) {
        queryLoading.value = false;
        pagingRobotRef.value?.complete(false);
    }
};

/**
 * 点击一级机器人分类
 */
const handleRobotCate = (index: number) => {
    if (index == robotCateActiveMenu.value) {
        robotCateActiveMenu.value = -1;
        return;
    }
    robotCateActiveMenu.value = index;
};

/**
 * 点击二级机器人分类
 */
const handleRobotSubCate = (index: number, id: number) => {
    if (index == robotSubCateIndex.value && id == currSubId.value) {
        return;
    }
    currSubId.value = id;
    robotSubCateIndex.value = index;
    queryLoading.value = true;

    const currSubLists = optionsData.robotCate[robotCateActiveMenu.value]?.sub_list;
    queryParams.scene_id = currSubLists[index]?.id || "";
    pagingRobotRef.value?.reload();
};

/**
 * 检查当前二级分类是否属于给定的一级分类列表
 */
const isCurrMenu = (lists: any[]) => {
    return lists.some((item) => item.id == currSubId.value);
};

/**
 * 点击机器人项
 */
const handleRobot = (data: any) => {
    uni.$u.route({
        url: "/packages/pages/robot_chat/robot_chat",
        params: {
            id: data.id,
        },
    });
};

/**
 * 点击智能体分类
 */
const handleAgentCateClick = (item: any) => {
    currAgentType.value = item.value;
    // AI助理走独立的机器人列表，不复用智能体分页
    if (item.value !== AgentCateType.ASSISTANT) pagingCozeRef.value?.reload();
};

/**
 * 新增智能体
 */
const handleCreateAgent = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/packages/pages/login/login" });
        return;
    }
    uni.$u.route({ url: "/packages/pages/create_agent/create_agent" });
};

/**
 * 新建知识库（新增/编辑后续补充，先占位）
 */
const handleCreateKnowledge = () => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/packages/pages/login/login" });
        return;
    }
    uni.$u.route({ url: "/packages/pages/create_knowledge/create_knowledge" });
};

/**
 * 点击智能体项
 */
const handleSelectAgent = async (item: any) => {
    if (!isLogin.value) {
        uni.$u.route({ url: "/packages/pages/login/login" });
        return;
    }
    if (!ensureAgentAvailable(item)) return;
    if (currAgentType.value == 1) {
        uni.$u.route({
            url: "/packages/pages/agent_chat/agent_chat",
            params: {
                id: item.id,
            },
        });
    } else {
        uni.$u.route({
            url: "/packages/pages/coze_chat/coze_chat",
            params: {
                id: item.id,
                type: item.type,
            },
        });
    }
};

/**
 * 更新智能体
 */
const handleUpdateAgent = (item: any) => {
    if (!taskId.value) {
        clearChatContent();
    }
    if (!item) {
        currAgent.value = null;
        return;
    }
    currAgent.value = item;
};

/**
 * 更新模型
 */
const handleUpdateModel = (item: any) => {
    currModel.value = item;
};

const handleMountedDevicesChange = async (devices: any[]) => {
    const prevHadDevices = mountedDevices.value.length > 0;
    mountedDevices.value = devices || [];
    // 挂载时不清空模型；退出时取消手机任务，并回到对话首页（空会话 + 默认模型）
    if (prevHadDevices && !mountedDevices.value.length) {
        await cancelActivePhoneAgentTask();
        resetChat();
        clearChatContent();
        currAgent.value = null;
        currModel.value = null;
        await nextTick();
        chattingRef.value?.handleAgentClear?.();
        // 只走一次 restore，内部含 sync + 同模型缓存的设置请求
        chattingRef.value?.restoreBottomBarAfterPhoneCtrl?.();
    }
};

/** 标准智能体看 is_owner;Coze 仍按用户自建(source=1) */
const isAgentOwner = (item: any) => {
    if (currAgentType.value === 1) {
        return Number(item.is_owner) === 1;
    }
    return Number(item.source) === 1;
};

/**
 * 编辑智能体（仅标准智能体支持，复用新建页）
 */
const handleEditAgent = (item: any) => {
    if (!isAgentOwner(item) || currAgentType.value !== 1) return;
    uni.$u.route({
        url: "/packages/pages/create_agent/create_agent",
        params: { id: item.id },
    });
};

/**
 * 删除智能体
 */
const handleDeleteAgent = (item: any) => {
    if (!isAgentOwner(item)) return;
    uni.showModal({
        title: "提示",
        content: "确定删除该智能体吗？",
        success: async (res) => {
            if (!res.confirm) return;
            uni.showLoading({ title: "删除中...", mask: true });
            try {
                const api = currAgentType.value == 1 ? deleteAgent : deleteCozeAgent;
                await api({ id: item.id });
                uni.hideLoading();
                uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                agentList.value = agentList.value.filter((value) => value.id != item.id);
            } catch (error: any) {
                uni.hideLoading();
                uni.showToast({ title: error, icon: "none", duration: 3000 });
            }
        },
    });
};

/**
 * 更多操作（团队共享:非创建者无编辑/删除入口，仅可点卡片对话）
 */
const handleMore = (item: any) => {
    if (!isAgentOwner(item)) return;
    const canEdit = currAgentType.value === 1;
    const itemList = canEdit ? ["编辑", "删除"] : ["删除"];
    uni.showActionSheet({
        itemList,
        success: (res) => {
            if (canEdit && res.tapIndex == 0) {
                handleEditAgent(item);
            } else {
                handleDeleteAgent(item);
            }
        },
    });
};

/**
 * 查询智能体列表
 */
const handleQueryAgentList = async (page_no: number, page_size: number) => {
    try {
        const isType1 = currAgentType.value === 1;
        const api = isType1 ? getAgentList : getCozeAgentList;
        const params = {
            page_no,
            page_size,
            ...(isType1 ? {} : { type: currAgentType.value === 2 ? 1 : 2 }),
        };
        // @ts-ignore
        const response = await api(params);
        pagingCozeRef.value?.complete(response.lists || []);
    } catch (error) {
        console.error("查询智能体列表失败:", error);
        pagingCozeRef.value?.complete(false);
    }
};

/**
 * 初始化
 */
const init = async (options?: Record<string, any>) => {
    await nextTick();
    if (options?.agent_name) {
        chattingRef.value?.setAgentConfig({
            name: options.agent_name,
            id: options.agent_id,
            avatar: options.agent_logo,
        });
        isAgent.value = true;
        await nextTick();
        chattingRef.value?.openKeyboard();
    }
};

onLoad((options?: Record<string, any>) => {
    if (options?.task_id) {
        taskId.value = options.task_id;
        getChatList();
    }
    init(options);
    watchFile();
    uni.onKeyboardHeightChange?.(onStudioKeyboardHeightChange);
});

onUnload(() => {
    // 该 uni-app 版本的运行时未暴露 offKeyboardHeightChange（仅 on 可用），需能力守卫
    if (typeof uni.offKeyboardHeightChange === "function") {
        uni.offKeyboardHeightChange(onStudioKeyboardHeightChange);
    }
    uni.$off("chooseFile");
    cancelWorkbenchJobs();
    handleChatClose();
    chattingRef.value?.hideKeyboard();
});

onHide(() => {
    chattingRef.value?.hideKeyboard();
});

onShow(async () => {
    await Promise.all([
        appStore.getConfig(), // 拉取 draw_model（生图模型列表，对齐 PC）
        appStore.getChatConfig(),
        appStore.ensureMemberQuota(),
        appStore.getAiModelsData(),
    ]);
    await nextTick();
    // 只同步默认模型展示，不强制刷新 getUserModelsSetting（同模型有缓存）
    chattingRef.value?.syncDefaultModel?.(true);
});

const handleAgentCreated = () => {
    pagingCozeRef.value?.reload();
};
onMounted(() => uni.$on("agentCreated", handleAgentCreated));
onUnmounted(() => uni.$off("agentCreated", handleAgentCreated));
</script>

<style lang="scss" scoped>
.agent-selected-wrap {
    @apply w-full h-full flex flex-col items-center pt-[160rpx] relative overflow-hidden;
}

.agent-fab {
    @apply fixed left-1/2 z-30 flex items-center gap-x-[12rpx] px-[52rpx] py-[24rpx] rounded-full;
    bottom: calc(env(safe-area-inset-bottom) + 140rpx);
    transform: translateX(-50%);
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 16rpx 48rpx rgba(47, 115, 246, 0.42), 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
    &:active {
        transform: translateX(-50%) scale(0.97);
        opacity: 0.9;
    }
}

.navbar-center {
    @apply w-full flex items-center justify-center px-2;
}

.agent-title-row {
    @apply flex min-w-0 items-center gap-x-[10rpx] mb-[8rpx];
}

.agent-title-row--with-more {
    @apply pr-[64rpx];
}

.agent-title-name {
    @apply min-w-0 flex-1 line-clamp-1 break-all text-[28rpx] font-bold text-[#212121];
}

.agent-access-tag {
    @apply shrink-0 rounded-full border border-solid px-[14rpx] py-[4rpx] text-[20rpx] font-semibold leading-none;
}

.agent-access-tag--free {
    @apply border-[#BBF7D0] bg-[#F0FDF4] text-[#16A34A];
}

.agent-access-tag--member {
    @apply border-[#DDD6FE] bg-[#F5F3FF] text-[#8B5CF6];
}

.glass-tabs {
    @apply flex items-center rounded-full p-[6rpx];
    background: #ffffff;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
}

.glass-tab-item {
    @apply px-[32rpx] py-[12rpx] rounded-full transition-all;
}

.glass-tab-active {
    background: #3b82f6;
    box-shadow: none;
}

.glass-tab-text {
    @apply font-medium text-[#64748B];
}

.glass-tab-text-active {
    @apply text-white font-semibold;
}

.page-title {
    @apply flex items-center;
}

.title-text {
    @apply text-[34rpx] font-bold text-[#1E3A5F];
}

.robot-cate {
    @apply overflow-hidden gap-2 relative;
    &-active {
        &.robot-cate-first {
            .robot-cate-item {
                @apply relative z-40;
                .robot-cate-item-wrap {
                    @apply rounded-br-[36rpx];
                }
                &::after {
                    content: "";
                    @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6] z-10;
                }
            }
            .robot-cate-item {
                @apply rounded-br-[36rpx];
            }
        }
        &.robot-cate-brother {
            & + .robot-cate {
                .robot-cate-item-wrap {
                    @apply rounded-tr-[36rpx];
                }
                .robot-cate-item {
                    &::after {
                        content: "";
                        @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6] z-10;
                    }
                }
            }
        }
    }
}

.robot-cate-item-wrap {
    @apply relative z-30 px-[24rpx] py-3 bg-white;
}

.sub-robot-item {
    @apply w-full h-full flex items-center pl-[24rpx] relative z-10;
}

.sub-robot {
    @apply h-[100rpx] relative;
    &-last {
        &::after {
            content: "";
            @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6];
        }
        .sub-robot-item {
            @apply rounded-br-[36rpx] bg-white overflow-hidden;
        }
        .sub-robot-item-bg {
            @apply bg-[#F5F6F6];
        }
    }
}

.sub-robot-active {
    .sub-robot-item {
        @apply bg-[#F5F6F6] rounded-tl-[36rpx] rounded-bl-[36rpx];
    }

    & + .sub-robot {
        &::after {
            content: "";
            @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6];
        }
        .sub-robot-item {
            @apply rounded-tr-[36rpx] bg-white overflow-hidden;
        }
        .sub-robot-item-bg {
            @apply bg-[#F5F6F6];
        }
    }
}
</style>
