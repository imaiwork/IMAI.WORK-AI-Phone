<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            :title="navTitle"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }">
        </u-navbar>

        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-3 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="common-step-item"
                    :class="{ active: currentStep == item.step }"
                    @click="handleStepJump(item.step)">
                    <view
                        v-if="currentStep > item.step"
                        class="common-step-item-success-icon bg-primary border-primary">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="common-step-item-icon" v-else> </view>
                    <text class="common-step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="common-step-item-line"
                        :class="{ '!border-primary': currentStep > item.step }"></view>
                </view>
            </view>
        </view>

        <view class="grow min-h-0 mt-[24rpx]">
            <view v-show="currentStep === 1" class="flex flex-col h-full">
                <view class="flex items-center justify-between px-4">
                    <text class="font-bold">
                        {{ taskType == TaskType.IMAGE ? "图组列表" : "视频素材" }}（{{
                            formData.materialLists.length
                        }}）
                    </text>
                    <view
                        v-if="taskType == TaskType.IMAGE"
                        class="px-[28rpx] py-[12rpx] bg-primary text-white rounded-[50rpx] font-bold"
                        @click="handleEditMaterial()">
                        添加图组
                    </view>
                </view>

                <view class="grow min-h-0">
                    <template v-if="taskType == TaskType.IMAGE">
                        <scroll-view scroll-y class="h-full" v-if="formData.materialLists.length > 0">
                            <view class="p-4 flex flex-col gap-2">
                                <view
                                    v-for="(item, index) in formData.materialLists"
                                    :key="index"
                                    class="material-image-item">
                                    <view class="flex items-center justify-between">
                                        <view class="font-bold">
                                            {{ `图组${index + 1 < 10 ? "0" + (index + 1) : index + 1}` }}
                                        </view>
                                        <view class="flex items-center gap-x-1" @click="handleEditMaterial(index)">
                                            <view class="flex items-center gap-x-[4rpx] font-bold">
                                                <text>{{ item.url.length }}</text>
                                                <text class="text-[#0000004d]">张</text>
                                            </view>
                                            <u-icon name="arrow-right" size="20" color="#00000099"></u-icon>
                                        </view>
                                    </view>
                                    <view class="mt-[18rpx] grid grid-cols-4 gap-2">
                                        <view v-for="(image, iindex) in item.url" :key="iindex" class="image-item">
                                            <image
                                                :src="image"
                                                class="w-full h-full rounded-[10rpx]"
                                                mode="aspectFill"></image>
                                        </view>
                                    </view>
                                    <view
                                        class="mt-[22rpx] flex items-center gap-x-1"
                                        @click="handleDeleteMaterial(index)">
                                        <image
                                            src="/static/images/icons/delete.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="text-[#0000004d]">删除</text>
                                    </view>
                                </view>
                            </view>
                        </scroll-view>
                        <view v-else class="mt-[100rpx]">
                            <empty :size="260" text="您还没有图组哦" />
                            <view class="mt-[44rpx] flex justify-center">
                                <view
                                    class="w-[220rpx] h-[88rpx] rounded-[20rpx] border border-solid flex items-center justify-center gap-x-2"
                                    @click="handleEditMaterial()">
                                    <u-icon name="plus" size="24"></u-icon>
                                    <text class="font-bold">添加图组</text>
                                </view>
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <scroll-view scroll-y class="h-full">
                            <view class="p-4 grid grid-cols-3 gap-2">
                                <view
                                    v-for="(item, index) in formData.materialLists"
                                    :key="index"
                                    class="material-video-item">
                                    <image
                                        :src="item.url[0]"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                    <view class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                        <view
                                            class="rounded-full bg-[#ffffff33] w-[48rpx] h-[48rpx]"
                                            style="backdrop-filter: blur(5px)"
                                            @click="handlePlayVideo(item.url)">
                                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                        </view>
                                    </view>
                                    <view
                                        class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                        @click="handleDeleteVideo(index)">
                                        <u-icon name="close" size="20" color="#ffffff"></u-icon>
                                    </view>
                                    <view class="absolute bottom-2 w-full z-[33] flex justify-center">
                                        <view class="dh-version-name" @click="handleReplaceVideo(index)"> 替换 </view>
                                    </view>
                                </view>
                                <view
                                    v-if="formData.materialLists.length < VIDEO_CONFIG.limit"
                                    class="bg-white rounded-[20rpx] h-[288rpx] flex flex-col items-center justify-center"
                                    @click="triggerVideoUploadSelection">
                                    <view
                                        class="w-[32rpx] h-[32rpx] bg-[#00000066] flex items-center justify-center rounded-full">
                                        <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                                    </view>
                                    <text class="mt-3 font-bold text-[#00000066]">添加视频</text>
                                </view>
                            </view>
                        </scroll-view>
                    </template>
                </view>
            </view>

            <view v-show="currentStep === 2" class="flex flex-col h-full">
                <view class="flex items-center px-4 gap-x-2">
                    <navigator
                        url="/ai_modules/device/pages/task_copywriter/task_copywriter"
                        hover-class="none"
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="editCopywriterIndex = -1">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-bold text-[32rpx]">添加文案...</text>
                    </navigator>
                    <navigator
                        url="/ai_modules/device/pages/task_ai_copywriter/task_ai_copywriter"
                        hover-class="none"
                        class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]"
                        @click="editCopywriterIndex = -1">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">AI生成</text>
                    </navigator>
                </view>
                <view class="px-4 font-bold text-[30rpx] mt-[60rpx]">
                    文案列表（{{ formData.copywriterList.length }}）
                </view>
                <view class="grow min-h-0 mt-[24rpx]">
                    <scroll-view scroll-y class="h-full" v-if="formData.copywriterList.length > 0">
                        <view class="px-4 flex flex-col gap-y-[30rpx] pb-[100rpx]">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="copywriter-item"
                                @click="handleEditCopywriter(index)">
                                <view class="text-[30rpx] font-bold"> {{ item.title }} </view>
                                <view class="font-bold mt-[26rpx]">
                                    {{ item.content }}
                                </view>
                                <view class="mt-[50rpx] flex items-center flex-wrap gap-2">
                                    <view
                                        v-for="(tag, tindex) in item.topic"
                                        :key="tindex"
                                        class="text-xs text-[#0000004d]">
                                        #{{ tag }}
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-5 h-5 flex items-center justify-center bg-[#0000004d] rounded-full"
                                    @click.stop="handleDeleteCopywriter(index)">
                                    <u-icon name="close" size="20" color="#ffffff"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="mt-[100rpx]">
                        <empty :size="260" text="您还没有文案哦" />
                    </view>
                </view>
            </view>

            <view v-show="currentStep === 3" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[100rpx]">
                        <view>
                            <view class="text-[30rpx] font-bold"> 基础设置 </view>
                            <view class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx]">
                                <view>
                                    <view class="text-[#7C7E80]">任务名称</view>
                                    <view class="mt-[12rpx]">
                                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                            <u-input
                                                v-model="formData.name"
                                                placeholder-style="font-size: 24rpx;"
                                                placeholder="请输入任务名称"
                                                maxlength="30"
                                                :custom-style="{ fontSize: '26rpx' }" />
                                        </view>
                                    </view>
                                </view>
                                <view class="mt-[28rpx]">
                                    <view class="text-[#7C7E80]">发布账号选择</view>
                                    <view class="mt-[12rpx]">
                                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                            <navigator
                                                :url="`/ai_modules/device/pages/account_choose/account_choose?accounts=${JSON.stringify(
                                                    formData.accounts
                                                )}`"
                                                class="flex items-center justify-between h-[70rpx]"
                                                hover-class="none">
                                                <text
                                                    :class="[
                                                        formData.accounts.length
                                                            ? 'text-primary font-bold'
                                                            : 'text-[#00000033]',
                                                    ]">
                                                    {{
                                                        formData.accounts.length
                                                            ? `${formData.accounts.length}个账号`
                                                            : "选择账号"
                                                    }}
                                                </text>
                                                <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                            </navigator>
                                        </view>
                                    </view>
                                </view>
                                <view class="mt-[28rpx]">
                                    <view class="text-[#7C7E80]">发布频率(每日)</view>
                                    <view class="mt-[28rpx] flex flex-wrap gap-2">
                                        <view
                                            v-for="item in publishFrequencyOptions"
                                            :key="item"
                                            class="frequency-item"
                                            :class="{
                                                active: formData.publish_frep === item && currentFrequencyIdx !== 5,
                                            }"
                                            @click="handlePublishFrequency(item)">
                                            {{ item }}条
                                        </view>
                                        <view
                                            class="frequency-item"
                                            :class="{ active: currentFrequencyIdx == 5 }"
                                            @click="showNumberPop = true">
                                            {{ customPublishFrep ? `${customPublishFrep}条` : "自定义" }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="mt-[32rpx]">
                            <view class="text-[30rpx] font-bold"> 时间设置 </view>
                            <view
                                class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                                <view>
                                    <view class="text-[#7C7E80]">任务频率</view>
                                    <view class="mt-[22rpx]">
                                        <view class="flex flex-wrap gap-x-2 gap-y-3">
                                            <view
                                                v-for="(item, index) in taskFrequencyOptions"
                                                :key="index"
                                                :class="{
                                                    active: formData.task_frep == item && currentDayFrequencyIdx != 5,
                                                }"
                                                class="frequency-item"
                                                @click="handleDayFrequency(item, index)">
                                                {{ item }}天
                                            </view>
                                            <view
                                                class="frequency-item"
                                                :class="{ active: currentDayFrequencyIdx == 5 }"
                                                @click="handleCustomDate">
                                                {{
                                                    formData.custom_date && currentDayFrequencyIdx == 5
                                                        ? "更改日期"
                                                        : "自定义"
                                                }}
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="mt-[28rpx]"
                                    v-if="formData.custom_date.length && currentDayFrequencyIdx == 5">
                                    <view class="flex items-center justify-between">
                                        <view class="text-[#7C7E80]">任务时间</view>
                                        <view
                                            class="flex items-center gap-x-1"
                                            v-if="formData.custom_date.length > 8"
                                            @click="isExpandDate = !isExpandDate">
                                            <text class="text-[#00000080]">{{ isExpandDate ? "收起" : "展开" }}</text>
                                            <u-icon
                                                :name="isExpandDate ? 'arrow-up' : 'arrow-down'"
                                                size="24"
                                                color="#00000033"></u-icon>
                                        </view>
                                    </view>
                                    <view
                                        class="mt-[22rpx]"
                                        :class="{ 'max-h-[120rpx] overflow-hidden': !isExpandDate }">
                                        <view class="flex flex-wrap gap-2">
                                            <view
                                                v-for="(item, index) in formData.custom_date"
                                                :key="index"
                                                class="date-item">
                                                {{ formatDate(item) }}
                                                <!-- <view
                                                    class="w-[24rpx] h-[24rpx] flex items-center justify-center rounded-full bg-[#FF2442]"
                                                    @click="handleDeleteCustomDate(index)">
                                                    <u-icon name="close" size="12" color="#FFFFFF"></u-icon>
                                                </view> -->
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="mt-[32rpx]">
                            <view class="flex items-center gap-x-2">
                                <view class="text-[30rpx] font-bold"> 发布时间 </view>
                                <view class="text-[#E07B00] text-[22rpx]">
                                    {{ `(发布的间隔时间必须大于${TIME_INTERVAL}分钟)` }}
                                </view>
                            </view>
                            <view
                                class="mt-4 rounded-[16rpx] px-4 py-[28rpx] bg-white"
                                v-for="(item, index) in formData.time_config"
                                :key="index">
                                <view class="text-primary font-bold text-[30rpx]">{{ formatDate(item.date) }}</view>
                                <view class="flex flex-col gap-y-[28rpx] mt-[30rpx]">
                                    <view v-for="(time, timeIndex) in item.times" :key="timeIndex">
                                        <view class="text-[#7C7E80]">第{{ timeIndex + 1 }}个内容任务发布时间</view>
                                        <view class="mt-[12rpx] flex items-center gap-x-4">
                                            <view
                                                class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                                <picker
                                                    mode="time"
                                                    class="w-full"
                                                    :value="time.start_time"
                                                    @change="handleStartTimeChange($event, index, timeIndex)">
                                                    <view class="flex items-center justify-between h-[70rpx]">
                                                        <text
                                                            :class="[
                                                                timeErrors[timeIndex]?.start_time
                                                                    ? 'text-[#FF3C26] font-bold'
                                                                    : time.start_time
                                                                    ? 'font-bold'
                                                                    : 'text-[#00000033]',
                                                            ]">
                                                            {{ time.start_time || "开始时间" }}
                                                        </text>
                                                        <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                                    </view>
                                                </picker>
                                            </view>
                                            <view class="text-[#7C7E80]">至</view>
                                            <view
                                                class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                                <picker
                                                    mode="time"
                                                    class="w-full"
                                                    :value="time.end_time"
                                                    :disabled="!time.start_time"
                                                    @click="handleEndTimeClick(time.start_time)"
                                                    @change="handleEndTimeChange($event, index, timeIndex)">
                                                    <view class="flex items-center justify-between h-[70rpx]">
                                                        <text
                                                            :class="[
                                                                timeErrors[timeIndex]?.end_time
                                                                    ? 'text-[#FF3C26] font-bold'
                                                                    : time.end_time
                                                                    ? 'font-bold'
                                                                    : 'text-[#00000033]',
                                                            ]">
                                                            {{ time.end_time || "结束时间" }}
                                                        </text>
                                                        <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                                    </view>
                                                </picker>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view v-if="Object.keys(timeErrors).length > 0" class="mt-2 text-[#FF3C26]">
                                    时间配置存在冲突
                                </view>
                            </view>
                        </view>

                        <view v-if="taskErrorMsg" class="mt-5">
                            <view>任务冲突</view>
                            <view class="text-[#FF2442] mt-[20rpx] text-xs">
                                {{ taskErrorMsg }}
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="currentStep != steps.length">
                    <view
                        v-if="currentStep === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.materialLists.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ formData.materialLists.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view v-else>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="navigateStep('prev')">
                            上一步
                        </view>
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canProceedNext ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="navigateStep('next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-primary text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>

    <confirm-dialog
        v-if="confirmDialogVisible"
        v-model="confirmDialogVisible"
        confirm-text="删除"
        center
        content="是否确定删除图组？"
        @confirm="handleDeleteMaterialConfirm" />
    <confirm-dialog
        v-if="showCreateTaskSuccessDialog"
        v-model="showCreateTaskSuccessDialog"
        confirm-text="确定"
        center
        content="创建成功，回到任务列表？"
        :show-close="false"
        @confirm="handleCreateTaskSuccess" />
    <confirm-dialog
        v-if="showVideoUploadTip"
        v-model="showVideoUploadTip"
        confirm-text="去上传"
        :content="getVideoTipsContent"
        @close="
            isVideoInitialOpen = false;
            showVideoUploadTip = false;
        "
        @confirm="uploadAndProcessFiles('video')" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview>
    <choose-material
        v-model="showVideoMaterial"
        type="video"
        :multiple="replaceVideoIndex == -1"
        :limit="replaceVideoIndex == -1 ? VIDEO_CONFIG.limit - formData.materialLists.length : 1"
        @select="handleSelectVideoMaterial" />
    <choose-history
        v-model="showHistory"
        type="video"
        :limit="replaceVideoIndex == -1 ? VIDEO_CONFIG.limit - formData.materialLists.length : 1"
        @select="handleSelectHistory" />
    <number-pop
        v-model="showNumberPop"
        :max="99"
        :number="formData.publish_frep"
        title="发布频率"
        placeholder="请输入发布频率"
        confirmText="确定"
        @confirm="handleNumberPopConfirm" />
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { getVideoCreationRecord } from "@/api/app";
import { createMatrixTask, publishDeviceTask } from "@/api/device";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { getPuzzleTaskResultList } from "@/api/drawing";
import useUpload from "@/hooks/useUpload";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import NumberPop from "@/ai_modules/device/components/number-pop/number-pop.vue";
import ChooseHistory from "@/ai_modules/device/components/choose-history/choose-history.vue";

const { on } = useEventBusManager();

// --- 类型定义 ---
enum TaskType {
    VIDEO = 1,
    IMAGE = 2,
}

interface TimeConfig {
    date: string;
    times: { start_time: string; end_time: string }[];
}

interface MaterialItem {
    url: string[]; // 视频时: [pic, url], 图片时: [url1, url2...]
}

interface CopywriterItem {
    title: string;
    content: string;
    topic: string[];
}

interface FormData {
    name: string;
    introduction: string;
    copywriterList: CopywriterItem[];
    materialLists: MaterialItem[];
    time_config: TimeConfig[];
    accounts: any[];
    publish_frep: number;
    custom_date: string[];
    task_frep: number;
}

// --- 常量配置 ---
const steps = [
    { step: 1, title: "选择素材" },
    { step: 2, title: "填写文案" },
    { step: 3, title: "设定时间" },
];

const VIDEO_CONFIG = {
    limit: 99,
    size: 200, // MB
    format: ["mp4", "mov"],
};

const TIME_INTERVAL = 30; // 分钟
const publishFrequencyOptions = [1, 2, 3, 5, 10];
const taskFrequencyOptions = [1, 3, 5, 10, 30];

// --- 状态管理 ---
const taskType = ref<TaskType>(TaskType.VIDEO);
const currentStep = ref(1);

const formData = reactive<FormData>({
    name: "",
    introduction: "",
    copywriterList: [],
    materialLists: [],
    time_config: [],
    accounts: [],
    publish_frep: 2,
    custom_date: [],
    task_frep: 1,
});

// UI 控制状态
const deleteImgIndex = ref<number>(-1);
const editImgIndex = ref<number>(-1);
const showVideoMaterial = ref<boolean>(false);
const showHistory = ref<boolean>(false);
const confirmDialogVisible = ref<boolean>(false);
const showVideoUploadTip = ref<boolean>(false);
const isVideoInitialOpen = ref<boolean>(true);
const replaceVideoIndex = ref<number>(-1);
const showVideoPreview = ref(false);
const editCopywriterIndex = ref<number>(-1);
const showNumberPop = ref<boolean>(false);
const showCreateTaskSuccessDialog = ref<boolean>(false);
const isExpandDate = ref(false);

const playItem = reactive({ url: "", pic: "" });

// 频率选择状态
const customPublishFrep = ref<number | null>(null);
const currentFrequencyIdx = ref(0);
const currentDayFrequencyIdx = ref(0);

// 校验状态
const timeErrors = ref<Record<number, { start_time?: boolean; end_time?: boolean }>>({});
const taskErrorMsg = ref<string>("");

// --- 计算属性 ---
const navTitle = computed(() => (taskType.value == TaskType.IMAGE ? "发布图文" : "发布视频"));

const getVideoTipsContent = computed(
    () => `
    <div>· 视频素材支持：${VIDEO_CONFIG.format.join("、")}格式，${VIDEO_CONFIG.size}M以内</div>
    <div class="mt-2">· 最多可传${VIDEO_CONFIG.limit}个视频</div>
    <div class="mt-2">· 不符合条件的视频会被自动删除</div>
`
);

// 判断是否允许进入下一步
const canProceedNext = computed(() => {
    switch (currentStep.value) {
        case 1:
            return formData.materialLists.length > 0;
        case 2:
            return formData.copywriterList.length > 0;
        case 3:
            return true;
        default:
            return false;
    }
});

// --- 方法逻辑 ---

// 步骤跳转逻辑
const handleStepJump = (targetStep: number) => {
    if (targetStep === currentStep.value) return;

    // 如果是回退，直接跳转
    if (targetStep < currentStep.value) {
        currentStep.value = targetStep;
    } else {
        // 如果是前进，必须确保中间每一步都符合条件
        for (let i = 1; i < targetStep; i++) {
            if (!checkStepValidity(i)) {
                return; // checkStepValidity 内部处理 Toast
            }
        }
        currentStep.value = targetStep;
    }
};

const navigateStep = (direction: "next" | "prev") => {
    if (direction === "prev") {
        currentStep.value--;
        return;
    }

    if (direction === "next") {
        if (canProceedNext.value) {
            currentStep.value++;
        } else {
            const messages: Record<number, string> = {
                1: "请至少选择一个图组",
                2: "请至少添加一条文案",
            };
            uni.$u.toast(messages[currentStep.value] || "请完成当前步骤");
        }
    }
};

// 辅助校验函数
const checkStepValidity = (stepNumber: number): boolean => {
    let isValid = false;
    let msg = "";
    if (stepNumber === 1) {
        isValid = formData.materialLists.length > 0;
        msg = "请至少选择一个图组";
    } else if (stepNumber === 2) {
        isValid = formData.copywriterList.length > 0;
        msg = "请至少添加一条文案";
    }

    if (!isValid) uni.$u.toast(msg);
    return isValid;
};

// 上传 hook
const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: VIDEO_CONFIG.limit,
    imageSize: VIDEO_CONFIG.size,
    fileAccept: VIDEO_CONFIG.format,
    videoAccept: VIDEO_CONFIG.format,
    fileSize: VIDEO_CONFIG.size,
    onSuccess: (res: any[]) => {
        const data = res.map((item: any) => ({ url: [item.pic, item.url] }));
        if (replaceVideoIndex.value !== -1) {
            formData.materialLists[replaceVideoIndex.value] = data[0];
        } else {
            formData.materialLists.push(...data);
        }
        replaceVideoIndex.value = -1;
    },
});

// 视频/素材相关逻辑
const triggerVideoUploadSelection = () => {
    showVideoUploadTip.value = false;
    uni.showActionSheet({
        itemList: ['从"素材库"中选择', '从"手机相册"中选择', '从"创作历史"中选择'],
        success: (res) => {
            if (res.tapIndex === 0) {
                showVideoMaterial.value = true;
            } else if (res.tapIndex === 1) {
                if (isVideoInitialOpen.value) {
                    isVideoInitialOpen.value = false;
                    showVideoUploadTip.value = true;
                } else {
                    uploadAndProcessFiles("video");
                }
            } else if (res.tapIndex === 2) {
                showHistory.value = true;
            }
        },
    });
};

const handleSelectVideoMaterial = async (res: any[]) => {
    const validVideos = res
        .filter((item: any) => {
            const suffix = item.content.split(".").pop()?.toLowerCase();
            const isValid =
                VIDEO_CONFIG.format.includes(suffix || "") && parseInt(item.size) <= VIDEO_CONFIG.size * 1024 * 1024;
            if (!isValid) {
                uni.showToast({ title: "部分视频不符合格式或大小限制，已过滤", icon: "none" });
            }
            return isValid;
        })
        .map((item: any) => ({ url: [item.pic, item.content] }));

    if (replaceVideoIndex.value !== -1) {
        if (validVideos.length) formData.materialLists[replaceVideoIndex.value] = validVideos[0];
    } else {
        formData.materialLists.push(...validVideos);
    }
    replaceVideoIndex.value = -1;
};

const handleEditMaterial = (index?: number) => {
    editImgIndex.value = index ?? -1;
    uni.$u.route({
        url: "/ai_modules/device/pages/task_img_group/task_img_group",
        params: {
            imgs: editImgIndex.value !== -1 ? JSON.stringify(formData.materialLists[editImgIndex.value].url) : "",
        },
    });
};

const handleSelectHistory = (res: any[]) => {
    if (replaceVideoIndex.value !== -1) {
        formData.materialLists[replaceVideoIndex.value] = {
            url: [res[0].pic, res[0].clip_result_url || res[0].video_result_url],
        };
    } else {
        formData.materialLists.push(
            ...res.map((item: any) => ({ url: [item.pic, item.clip_result_url || item.video_result_url] }))
        );
    }
    replaceVideoIndex.value = -1;
};

const handleDeleteMaterial = (index: number) => {
    deleteImgIndex.value = index;
    confirmDialogVisible.value = true;
};

const handleDeleteMaterialConfirm = () => {
    formData.materialLists.splice(deleteImgIndex.value, 1);
    confirmDialogVisible.value = false;
    deleteImgIndex.value = -1;
};

const handlePlayVideo = (item: string[]) => {
    playItem.pic = item[0];
    playItem.url = item[1];
    showVideoPreview.value = true;
};

const handleDeleteVideo = (index: number) => {
    formData.materialLists.splice(index, 1);
};

const handleReplaceVideo = (index: number) => {
    replaceVideoIndex.value = index;
    triggerVideoUploadSelection();
};

// 文案相关逻辑
const handleEditCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    uni.$u.route({
        url: "/ai_modules/device/pages/task_copywriter/task_copywriter",
        params: { copywriter: JSON.stringify(formData.copywriterList[index]) },
    });
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

// 时间配置相关逻辑
const formatDate = (dateStr: string) => {
    if (!dateStr) return "";
    return uni.$u.timeFormat(new Date(dateStr), "mm月dd日");
};

const handleNumberPopConfirm = (value: number) => {
    const maxFrequency = Math.floor((24 * 60) / TIME_INTERVAL);
    if (value < 1) {
        uni.$u.toast("请输入有效的发布数量");
        return;
    }
    if (value > maxFrequency) {
        uni.$u.toast(`每日发布频率最高为${maxFrequency}次`);
        return;
    }
    currentFrequencyIdx.value = 5;
    formData.publish_frep = value;
    customPublishFrep.value = value;
    showNumberPop.value = false;
    changeTimeConfig();
};

const handlePublishFrequency = (freq: number) => {
    currentFrequencyIdx.value = 0;
    formData.publish_frep = freq;
    changeTimeConfig();
};

const handleDayFrequency = (days: number, index: number) => {
    if (currentDayFrequencyIdx.value === index) return;
    formData.task_frep = days;
    formData.custom_date = [];
    currentDayFrequencyIdx.value = index;
    changeTimeConfig();
};

const handleCustomDate = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/custom_date/custom_date",
        params: { date: JSON.stringify(formData.custom_date) },
    });
};

const handleDeleteCustomDate = (index: number) => {
    if (formData.custom_date.length === 1) {
        uni.$u.toast("至少保留一个日期");
        return;
    }
    formData.custom_date.splice(index, 1);
    changeTimeConfig();
};

// 核心：重新生成时间配置
const changeTimeConfig = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // 归一化到今天0点

    const generateTimesForDay = () => {
        return Array.from({ length: formData.publish_frep }, (_, i) => {
            const startMs = today.getTime() + i * TIME_INTERVAL * 60 * 1000;
            const endMs = startMs + TIME_INTERVAL * 60 * 1000;

            // 处理跨天逻辑：如果结束时间跨天，设为当天23:59
            const startDate = new Date(startMs);
            let endDate = new Date(endMs);
            if (endDate.getDate() !== startDate.getDate()) {
                endDate = new Date(startDate);
                endDate.setHours(23, 59, 59, 999);
            }

            return {
                start_time: uni.$u.timeFormat(startDate, "hh:MM"),
                end_time: uni.$u.timeFormat(endDate, "hh:MM"),
            };
        });
    };

    // 如果是自定义日期模式
    if (currentDayFrequencyIdx.value === 5 && formData.custom_date.length > 0) {
        formData.time_config = formData.custom_date.map((dateStr) => ({
            date: dateStr,
            times: generateTimesForDay(),
        }));
    } else {
        // 普通模式
        formData.time_config = Array.from({ length: formData.task_frep }, (_, i) => {
            const dateObj = new Date(today.getTime() + i * 24 * 60 * 60 * 1000);
            return {
                date: uni.$u.timeFormat(dateObj, "yyyy-mm-dd"),
                times: generateTimesForDay(),
            };
        });
    }

    timeErrors.value = {};
};

const handleEndTimeClick = (startTime?: string) => {
    if (!startTime) uni.$u.toast("请先选择开始时间");
};

const handleStartTimeChange = (e: any, configIndex: number, timeIndex: number) => {
    const value = e.detail.value;
    const timeItem = formData.time_config[configIndex].times[timeIndex];
    timeItem.start_time = value;

    // 自动推算结束时间 (+30min)
    const [h, m] = value.split(":").map(Number);
    const date = new Date();
    date.setHours(h, m + TIME_INTERVAL, 0, 0);
    timeItem.end_time = uni.$u.timeFormat(date, "hh:MM");

    validateAndSetErrors(configIndex);
};

const handleEndTimeChange = (e: any, configIndex: number, timeIndex: number) => {
    const value = e.detail.value;
    const timeItem = formData.time_config[configIndex].times[timeIndex];

    if (!timeItem.start_time) return;

    // 构造日期对象进行比较，确保跨零点等逻辑正确（假设都在同一天或次日）
    const d = new Date().toDateString(); // "Mon Jan 01 2000"
    const start = new Date(`${d} ${timeItem.start_time}`);
    const end = new Date(`${d} ${value}`);

    if (end <= start) {
        uni.$u.toast("结束时间必须晚于开始时间");
        // 重置回原来的有效值或清空? 这里选择暂不修改，等待用户再次选择，或者恢复自动计算值
        return;
    }

    if (end.getTime() - start.getTime() < TIME_INTERVAL * 60 * 1000) {
        uni.$u.toast(`间隔时间必须大于${TIME_INTERVAL}分钟`);
        return;
    }

    timeItem.end_time = value;
    validateAndSetErrors(configIndex);
};

// 校验特定某天的时间安排
const validateAndSetErrors = (configIndex: number) => {
    const times = formData.time_config[configIndex].times;
    const toMin = (t: string) => {
        const [h, m] = t.split(":").map(Number);
        return h * 60 + m;
    };

    const errors: Record<number, { start_time: boolean; end_time: boolean }> = {};
    let hasError = false;

    // 检查每一项的完整性与顺序
    for (let i = 0; i < times.length; i++) {
        const cur = times[i];
        if (!cur.start_time || !cur.end_time) continue;

        const s = toMin(cur.start_time);
        const e = toMin(cur.end_time);

        // 自身开始结束检查
        if (s >= e) {
            errors[i] = { start_time: true, end_time: true };
            hasError = true;
        }

        // 与上一项重叠检查
        if (i > 0) {
            const prev = times[i - 1];
            if (prev.end_time) {
                const prevE = toMin(prev.end_time);
                if (s < prevE) {
                    errors[i] = { ...errors[i], start_time: true };
                    errors[i - 1] = { ...errors[i - 1], end_time: true };
                    hasError = true;
                }
            }
        }
    }

    timeErrors.value = errors;
    return !hasError;
};

// 创建任务提交
const handleCreateTask = async () => {
    // 基础校验
    if (Object.keys(timeErrors.value).length > 0) return uni.$u.toast("时间配置存在冲突");
    if (!formData.name) return uni.$u.toast("请输入任务名称");
    if (formData.accounts.length === 0) {
        uni.$u.toast("请选择发布账号");
        uni.$u.route({ url: "/ai_modules/device/pages/account_choose/account_choose" });
        return;
    }

    uni.showLoading({ title: "创建中...", mask: true });

    try {
        const { id } = await createMatrixTask({
            name: formData.name,
            media_type: taskType.value,
            media_url: formData.materialLists,
            copywriting: formData.copywriterList,
        });

        await publishDeviceTask({
            name: formData.name,
            matrix_media_setting_id: id,
            time_config: formData.time_config.map((item: any) => ({
                date: item.date,
                times: item.times.map((time: any) => `${time.start_time}-${time.end_time}`),
            })),
            accounts: formData.accounts,
            publish_frep: formData.publish_frep,
            media_type: taskType.value,
            task_type: 3,
            scene: 2,
            data_type: 0,
        });
        uni.hideLoading();
        showCreateTaskSuccessDialog.value = true;
        WechatOA.notify();
    } catch (error: any) {
        uni.hideLoading();
        if (error.indexOf("24小时自动执行任务") > -1) {
            uni.showModal({
                title: "提示",
                content: "您已开启24小时自动执行任务，无法创建手动任务，如您需手动创建任务，需先关闭24小时托管。",
                success: (res) => {
                    if (res.confirm) {
                        uni.$u.route({
                            url: "/pages/phone/phone",
                        });
                    }
                },
            });
        } else {
            taskErrorMsg.value = error;
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
        }
    }
};

const handleCreateTaskSuccess = () => {
    uni.$u.route({ url: "/pages/phone/phone", type: "reLaunch" });
    showCreateTaskSuccessDialog.value = false;
};

function selectRandomElements<T>(sourceArray: T[], count: number): T[] {
    const result: T[] = [];
    const arrCopy = [...sourceArray];
    for (let i = 0; i < count; i++) {
        if (arrCopy.length === 0) break;
        const randomIndex = Math.floor(Math.random() * arrCopy.length);
        result.push(arrCopy[randomIndex]);
    }
    return result;
}

function createRandomImageGroups(allImages: string[], numberOfGroups: number) {
    if (numberOfGroups <= 0) return [];

    return Array.from({ length: numberOfGroups }, () => {
        const countToAllocate = Math.floor(Math.random() * Math.min(allImages.length, 9)) + 1;
        return { url: selectRandomElements(allImages, countToAllocate) };
    });
}

// --- 生命周期 ---
onLoad(async (options: any) => {
    // 初始化类型与默认名称
    if (options.type) {
        taskType.value = Number(options.type) as TaskType;
        const prefix = taskType.value == TaskType.IMAGE ? "图文" : "视频";
        formData.name = `${prefix}矩阵任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`;
    }

    // 处理来源数据: 视频创作
    if (options.source === "creation_video") {
        const videoIds = JSON.parse(options.ids || "[]");
        const { lists } = await getVideoCreationRecord({ page_size: 99999 });
        if (lists?.length) {
            lists
                .filter((item: any) => videoIds.includes(item.task_id))
                .forEach((item: any) => {
                    formData.materialLists.push({
                        url: [item.pic, item.clip_result_url || item.video_result_url],
                    });
                });
        }
    }
    // 处理来源数据: 拼图
    else if (options.source === "puzzle") {
        const { id, count } = options;
        const { lists } = await getPuzzleTaskResultList({ puzzle_setting_id: id, page_size: 999 });
        if (lists?.length) {
            const allImages = lists.flatMap((curr: any) => curr.puzzle_url);
            formData.materialLists = createRandomImageGroups(allImages, Number(count));
        }
    }

    // 事件监听
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (!data || data.length === 0) {
            // 如果是编辑模式下清空了数据，则删除该项
            if (type === ListenerTypeEnum.CHOOSE_IMG && editImgIndex.value !== -1) {
                formData.materialLists.splice(editImgIndex.value, 1);
            }
            if (type === ListenerTypeEnum.CHOOSE_DATE) {
                currentDayFrequencyIdx.value = 0;
                formData.custom_date = [];
                changeTimeConfig();
                return;
            }
            return;
        }

        switch (type) {
            case ListenerTypeEnum.CHOOSE_IMG:
                if (editImgIndex.value !== -1) {
                    formData.materialLists[editImgIndex.value].url = data;
                } else {
                    formData.materialLists.push({ url: data });
                }
                break;
            case ListenerTypeEnum.TASK_COPYWRITER:
            case ListenerTypeEnum.TASK_AI_COPYWRITER:
                if (editCopywriterIndex.value !== -1) {
                    formData.copywriterList[editCopywriterIndex.value] = data[0];
                } else {
                    formData.copywriterList.push(...data);
                }
                break;
            case ListenerTypeEnum.CHOOSE_ACCOUNT:
                formData.accounts = data.map((item: any) => ({
                    account: item.account,
                    type: item.type,
                    id: item.id,
                }));
                break;
            case ListenerTypeEnum.CHOOSE_DATE:
                formData.custom_date = data;
                currentDayFrequencyIdx.value = 5;
                // 日期改变，重新计算时间表
                changeTimeConfig();
                break;
        }
    });

    // 初始化时间配置
    changeTimeConfig();
});
</script>

<style scoped lang="scss">
.material-image-item {
    @apply rounded-[20rpx] bg-white p-[28rpx] relative;
    .image-item {
        @apply aspect-[3/4] rounded-[10rpx];
    }
}

.material-video-item {
    @apply rounded-[20rpx] h-[288rpx] relative;
}
.copywriter-item {
    @apply rounded-[20rpx] bg-white p-[38rpx] relative;
}

.frequency-item,
.prompt-length-item {
    @apply px-[32rpx] py-[16rpx] rounded-[10rpx] bg-[#F6F6F6];

    &.active {
        // 使用 primary 颜色变量
        @apply text-primary shadow-[0_0_0_2rpx_#0065fb] font-bold bg-white;
    }
}
.date-item {
    @apply text-xs font-bold text-[#000000b3] rounded-[10rpx] px-[20rpx] py-[10rpx] bg-[#F6F6F6];
}
.change-material-btn {
    @apply text-white text-[22rpx] mt-8 border border-[#ffffff1a] shadow-[0_0_0_1px_rgba(0,0,0,0.24)] rounded-[50rpx] w-full h-[88rpx] flex items-center justify-center;
}
</style>
