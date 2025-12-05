<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title-bold
            title="视频号获客"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-4 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="step-item-icon" v-else> </view>
                    <text class="step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <view v-if="step === 1" class="px-4">
                <view class="text-[30rpx] font-bold">选择获客类型</view>
                <view class="grid grid-cols-2 gap-x-[20rpx] mt-[30rpx]">
                    <view
                        v-for="(item, index) in taskTypes"
                        :key="index"
                        :class="{ 'shadow-[0_0_0_2rpx_var(--color-primary)]': formData.crawl_type == item.value }"
                        class="bg-white h-[160rpx] flex flex-col items-center justify-center rounded-[10rpx]"
                        @click="formData.crawl_type = item.value">
                        <image
                            :src="formData.crawl_type == item.value ? item.primaryIcon : item.icon"
                            class="w-5 h-5"></image>
                        <text
                            class="text-[30rpx] mt-[10rpx]"
                            :class="{ 'text-primary': formData.crawl_type == item.value }"
                            >{{ item.title }}</text
                        >
                    </view>
                </view>
            </view>
            <view v-if="step === 2" class="flex flex-col h-full">
                <view class="flex items-center gap-x-2 px-4">
                    <view
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="handleEditClue()">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-bold text-[32rpx]">手动输入</text>
                    </view>
                    <navigator
                        :url="`/ai_modules/sph/pages/task_ai_clue/task_ai_clue?type=${
                            formData.crawl_type == 0 ? 2 : 3
                        }`"
                        hover-class="none"
                        class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-primary rounded-[10rpx]">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">AI生成</text>
                    </navigator>
                </view>
                <view class="font-bold text-[30rpx] px-4 mt-[60rpx]">线索词列表（{{ formData.keywords.length }}）</view>
                <view class="grow min-h-0 mt-[32rpx]">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-4 flex flex-wrap gap-4 pb-[100rpx]">
                            <view
                                v-for="(item, index) in formData.keywords"
                                :key="index"
                                class="keyword-item"
                                @click="handleEditClue(index)">
                                <view>{{ item }}</view>
                                <view
                                    class="w-4 h-4 flex items-center justify-center bg-[#0000004d] rounded-full"
                                    @click.stop="handleDeleteClue(index)">
                                    <u-icon name="close" size="16" color="#ffffff"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view v-if="step === 3" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-4 pb-[100rpx]">
                        <view class="bg-white rounded-[20rpx] p-[32rpx] mt-[32rpx]">
                            <view>
                                <view class="text-[#00000080] flex items-center gap-x-2" @click="showOCRTip = true">
                                    线索识别方式
                                    <view
                                        class="text-white bg-[rgba(0,0,0,0.3)] w-[24rpx] h-[24rpx] rounded-full flex items-center justify-center"
                                        ><u-icon name="info" :size="16" color="#ffffff"></u-icon
                                    ></view>
                                </view>
                                <view class="mt-[32rpx]">
                                    <u-radio-group v-model="formData.ocr_type">
                                        <u-radio :name="1" label-size="24" size="28">云端OCR识别</u-radio>
                                        <u-radio :name="2" label-size="24" size="28">本地识别</u-radio>
                                    </u-radio-group>
                                </view>
                            </view>
                            <view class="mt-[24rpx]">
                                <view class="flex items-center justify-between">
                                    <view class="text-[#00000080]"> 自动添加好友 </view>
                                    <view>
                                        <u-switch
                                            :model-value="formData.add_type == '1'"
                                            :size="32"
                                            active-value="1"
                                            inactive-value="0"
                                            @change="(e: any) => formData.add_type = e == '1' ? '0' : '1'" />
                                    </view>
                                </view>
                                <template v-if="formData.add_type == '1'">
                                    <view class="mt-[32rpx]">
                                        <view class="mb-3">加微微信</view>
                                        <data-select
                                            v-model="formData.wechat_id"
                                            multiple
                                            :localdata="optionsData.wechatLists"></data-select>
                                    </view>
                                    <view class="mt-[32rpx]">
                                        <view class="mb-3">加微规则</view>
                                        <data-select
                                            v-model="formData.wechat_reg_type"
                                            :clear="false"
                                            :localdata="[
                                                { text: '全部', value: 0 },
                                                { text: '微信号', value: 1 },
                                                { text: '手机号', value: 2 },
                                            ]"></data-select>
                                    </view>
                                    <view class="flex gap-x-[30rpx] mt-[32rpx]">
                                        <view class="flex-1">
                                            <view>当前执行</view>
                                            <view class="flex items-center gap-x-2 mt-[24rpx]">
                                                <view
                                                    class="flex-1 flex items-center h-[80rpx] rounded-xl shadow-[0_0_0_1px_#efefef] px-[24rpx]">
                                                    <u-input
                                                        v-model="formData.add_number"
                                                        type="digit"
                                                        placeholder="请输入"
                                                        placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)" /> </view
                                                >次
                                            </view>
                                        </view>
                                        <view class="flex-1">
                                            <view>每次间隔</view>
                                            <view class="flex items-center gap-x-2 mt-[24rpx]">
                                                <view
                                                    class="flex-1 flex items-center h-[80rpx] rounded-xl shadow-[0_0_0_1px_#efefef] px-[24rpx]">
                                                    <u-input
                                                        v-model="formData.add_interval_time"
                                                        type="digit"
                                                        placeholder="请输入"
                                                        placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)" /> </view
                                                >分钟
                                            </view>
                                        </view>
                                    </view>
                                </template>
                            </view>
                        </view>
                        <view class="mt-[24rpx] bg-white rounded-[20rpx] p-[32rpx]" v-if="formData.add_type == '1'">
                            <view class="flex items-center justify-between">
                                <view>加好友备注内容：</view>
                                <u-switch
                                    :model-value="formData.add_remark_enable"
                                    :size="32"
                                    active-value="1"
                                    inactive-value="0"
                                    @change="(e: any) => formData.add_remark_enable = e == 1 ? 0 : 1" />
                            </view>
                            <template v-if="formData.add_remark_enable == 0">
                                <view class="mt-[24rpx] rounded-xl shadow-[0_0_0_1px_#efefef] p-[12rpx]">
                                    <u-input
                                        v-model="formData.remark"
                                        type="textarea"
                                        height="160"
                                        placeholder="请输入打招呼内容，为了避免封控，系统将自动调用AI进行去重润色"
                                        placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)" />
                                </view>
                                <view class="flex justify-end mt-[24rpx]">
                                    <u-button
                                        type="primary"
                                        :custom-style="{
                                            width: '240rpx',
                                            height: '80rpx',
                                            boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                                            fontSize: '26rpx',
                                            borderRadius: '24rpx',
                                        }"
                                        @click="handleGreetingContentSetting(GreetingContentSettingTypeEnum.ADD_FRIEND)"
                                        >AI提示词设置</u-button
                                    >
                                </view>
                            </template>
                            <view v-if="formData.add_remark_enable == 1" class="mt-[24rpx]">
                                <view class="flex flex-wrap gap-2">
                                    <view
                                        v-for="(item, index) in formData.remarks"
                                        :key="index"
                                        class="border border-solid border-[#E5E5E5] rounded-md px-4 py-2 flex items-center cursor-pointer"
                                        @click="handleEditRemark(index)">
                                        <view class="text-xs">{{ item }}</view>
                                        <view class="w-[2rpx] h-[24rpx] bg-[#E5E5E5] mx-2"></view>
                                        <view
                                            class="flex-shrink-0 w-4 h-4 rounded-full border border-solid border-[#E5E5E5] flex items-center justify-center"
                                            @click.stop="handleDeleteRemark(index)">
                                            <u-icon name="close" :size="16" color="#00000080"></u-icon>
                                        </view>
                                    </view>
                                </view>
                                <view class="flex justify-end mt-[24rpx]">
                                    <u-button
                                        type="primary"
                                        :custom-style="{
                                            boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                                            fontSize: '26rpx',
                                            borderRadius: '24rpx',
                                        }"
                                        @click="showAddRemark = true">
                                        新增备注</u-button
                                    >
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
            <view v-if="step === 4" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-4 pb-[100rpx]">
                        <view>
                            <view class="flex items-center gap-x-1">
                                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                                <text class="font-bold">基础设置</text>
                            </view>
                            <view
                                class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                                <view>
                                    <view class="text-[#7C7E80]">任务名称</view>
                                    <view class="mt-[12rpx]">
                                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                            <u-input
                                                v-model="formData.name"
                                                placeholder-style="font-size: 24rpx;"
                                                placeholder="请输入任务名称"
                                                maxlength="50" />
                                        </view>
                                    </view>
                                </view>
                                <view class="mt-[28rpx]">
                                    <view class="text-[#7C7E80]">设备选择</view>
                                    <view class="mt-[12rpx]">
                                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                                            <navigator
                                                :url="`/ai_modules/device/pages/device_choose/device_choose?device=${JSON.stringify(
                                                    formData.device_codes
                                                )}`"
                                                class="flex items-center justify-between h-[70rpx]"
                                                hover-class="none">
                                                <text
                                                    :class="[
                                                        formData.device_codes.length
                                                            ? 'text-primary font-bold'
                                                            : 'text-[#00000033]',
                                                    ]"
                                                    >{{
                                                        formData.device_codes.length
                                                            ? `${formData.device_codes.length}个设备`
                                                            : "选择设备"
                                                    }}</text
                                                >
                                                <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                            </navigator>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[32rpx]">
                            <view class="flex items-center gap-x-1">
                                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                                <text class="font-bold">时间设置</text>
                            </view>
                            <view
                                class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                                <view>
                                    <view class="text-[#7C7E80]">任务频率</view>
                                    <view class="mt-[22rpx]">
                                        <view class="flex flex-wrap gap-x-2 gap-y-3">
                                            <view
                                                v-for="(item, index) in [1, 3, 5, 10, 30]"
                                                :key="index"
                                                :class="{ active: formData.task_frep == item && currentFrequency != 5 }"
                                                class="frequency-item"
                                                @click="handleFrequency(item, index)">
                                                {{ item }}天
                                            </view>
                                            <view
                                                class="frequency-item"
                                                :class="{ active: currentFrequency == 5 }"
                                                @click="handleCustomDate">
                                                自定义
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view class="mt-[28rpx]" v-if="formData.custom_date.length && currentFrequency == 5">
                                    <view class="flex items-center justify-between">
                                        <view class="text-[#7C7E80]">任务时间</view>
                                        <view
                                            class="flex items-center gap-x-1"
                                            v-if="formData.custom_date.length > 8"
                                            @click="isExpandDate = !isExpandDate">
                                            <text class="text-[rgba(0,0,0,0.5)]">{{
                                                isExpandDate ? "收起" : "展开"
                                            }}</text>
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
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view class="mt-[28rpx]">
                                    <view class="text-[#7C7E80]">每日执行时间</view>
                                    <view class="mt-[12rpx] flex items-center gap-x-4">
                                        <view
                                            class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                                            <picker
                                                mode="time"
                                                class="w-full"
                                                :value="formData.time_config[0]"
                                                @change="handleStartTimeChange">
                                                <view class="flex items-center justify-between h-[70rpx]">
                                                    <text
                                                        :class="[
                                                            formData.time_config[0]
                                                                ? 'text-primary font-bold'
                                                                : 'text-[#00000033]',
                                                        ]"
                                                        >{{ formData.time_config[0] || "开始时间" }}</text
                                                    >
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
                                                :value="formData.time_config[1]"
                                                :disabled="!formData.time_config[0]"
                                                @click="handleEndTimeClick"
                                                @change="handleEndTimeChange">
                                                <view class="flex items-center justify-between h-[70rpx]">
                                                    <text
                                                        :class="[
                                                            formData.time_config[1]
                                                                ? 'text-primary font-bold'
                                                                : 'text-[#00000033]',
                                                        ]"
                                                        >{{ formData.time_config[1] || "结束时间" }}</text
                                                    >
                                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                                </view>
                                            </picker>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[50rpx]" v-if="taskErrorMsg">
                                <view class="font-bold">任务冲突</view>
                                <view class="text-[#ff2442] mt-[20rpx]">
                                    {{ taskErrorMsg }}
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view>
                        <view
                            v-show="step != 1"
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="handleStep(step, 'prev')">
                            上一步
                        </view>
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-primary' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
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
    <clue-edit ref="clueEditRef" v-model="showClueEdit" @confirm="handleClueConfirm" @close="showClueEdit = false" />
    <u-popup v-model="showOCRTip" mode="bottom" border-radius="24" @close="showOCRTip = false">
        <view>
            <view class="text-center text-lg font-bold py-3"> 线索识别方式 </view>
            <u-line />
            <view class="w-full overflow-hidden text-[#4C4B6A] text-xs p-6">
                <view>
                    本地识别（每条扣{{
                        getOCRLocalToken
                    }}算力）使用系统内置识别逻辑完成，识别率较依赖本地环境，复杂图片可能不够精准
                </view>
                <view class="mt-3">
                    云端OCR识别（每条扣 {{ getOCRCloudToken }} 算力）使用云端OCR服务识别微信号，每条线索消耗{{
                        getOCRCloudToken
                    }}算力，识别率更高，支持更复杂的图片和场景
                </view>
            </view>
        </view>
    </u-popup>
    <u-popup v-model="showAddRemark" mode="center" border-radius="24" width="90%" closeable>
        <view class="w-full p-4">
            <view class="text-center text-lg font-bold"> 加好友备注文案 </view>
            <view class="border border-solid border-[#E5E5E5] rounded-md p-2 my-4">
                <u-input
                    v-model="addRemarkContent"
                    maxlength="100"
                    placeholder="请输入打招呼内容"
                    placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)" />
            </view>
            <u-button
                type="primary"
                :custom-style="{
                    height: '100rpx',
                    boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                    fontSize: '26rpx',
                    borderRadius: '24rpx',
                }"
                @click="handleAddRemark">
                立即保存
            </u-button>
        </view>
    </u-popup>
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
</template>

<script setup lang="ts">
import { createTask } from "@/api/sph";
import { getWeChatLists } from "@/api/person_wechat";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import AccountIcon from "@/ai_modules/sph/static/icons/account.svg";
import AccountPrimaryIcon from "@/ai_modules/sph/static/icons/account_primary.svg";
import VideoIcon from "@/ai_modules/sph/static/icons/video.svg";
import VideoPrimaryIcon from "@/ai_modules/sph/static/icons/video_primary.svg";
import { ListenerTypeEnum } from "@/ai_modules/sph/enums";
import { AppTypeEnum, TokensSceneEnum } from "@/enums/appEnums";
import { useDictOptions } from "@/hooks/useDictOptions";
import ClueEdit from "@/ai_modules/sph/components/clue-edit/clue-edit.vue";

enum CrawlType {
    ACCOUNT = 1,
    VIDEO = 0,
}

enum GreetingContentSettingTypeEnum {
    ADD_FRIEND = "add_friends_prompt",
    PRIVATE_CHAT = "private_message_prompt",
}

const appStore = useAppStore();
const userStore = useUserStore();
const getWechatRemarks = computed(() => {
    return appStore.config.wechat_remarks || [];
});

const getOCRCloudToken = computed(() => {
    return userStore.getTokenByScene(TokensSceneEnum.SPH_OCR)?.score;
});

const getOCRLocalToken = computed(() => {
    return userStore.getTokenByScene(TokensSceneEnum.SPH_LOCAL_OCR)?.score;
});

// 步骤
const steps = ref([
    { step: 1, title: "选择类型" },
    { step: 2, title: "设置线索" },
    { step: 3, title: "填设置" },
    { step: 4, title: "设定时间" },
]);
const step = ref(1);

const taskTypes = [
    { title: "账号获客", value: CrawlType.ACCOUNT, icon: AccountIcon, primaryIcon: AccountPrimaryIcon },
    { title: "视频获客", value: CrawlType.VIDEO, icon: VideoIcon, primaryIcon: VideoPrimaryIcon },
];

const formData = reactive<{
    name: string;
    crawl_type: CrawlType;
    chat_type: string;
    chat_number: number;
    chat_interval_time: number;
    greeting_content: string;
    add_type: string;
    remark: string;
    add_number: number;
    add_interval_time: number;
    private_message_prompt: string;
    add_friends_prompt: string;
    wechat_id: string;
    wechat_reg_type: number;
    add_remark_enable: number;
    remarks: string[];
    keywords: string[];
    device_codes: string[];
    ocr_type: 1 | 2;
    task_frep: number;
    time_config: [string, string];
    custom_date: string[];
}>({
    name: `视频号获客任务${uni.$u.timeFormat(Date.now(), "yyyymmddhhMM")}`,
    crawl_type: 1,
    chat_type: "0",
    chat_number: 30,
    chat_interval_time: 10,
    greeting_content: "",
    add_type: "1",
    remark: "",
    add_number: 15,
    add_interval_time: 10,
    private_message_prompt: "",
    add_friends_prompt: "",
    wechat_id: "",
    wechat_reg_type: 0,
    add_remark_enable: 1,
    ocr_type: 1,
    remarks: getWechatRemarks.value,
    keywords: [],
    device_codes: [],
    task_frep: 1,
    time_config: ["09:00", "09:15"],
    custom_date: [],
});

const showClueEdit = ref(false);
const clueEditRef = shallowRef();
// 编辑线索词
const editClueIndex = ref<number>(-1);

const showOCRTip = ref(false);
const showAddRemark = ref(false);
const editRemarkIndex = ref(-1);
const addRemarkContent = ref("");
// 当前任务频率
const currentFrequency = ref(0);
// 是否展开任务时间
const isExpandDate = ref(false);
// 任务冲突
const taskErrorMsg = ref("");

const timeInterval = 15;

const showCreateTaskSuccessDialog = ref(false);

const currentGreetingContentSettingType = ref<GreetingContentSettingTypeEnum>(
    GreetingContentSettingTypeEnum.PRIVATE_CHAT
);

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return true;
        case 2:
            if (formData.keywords.length == 0) {
                return false;
            }
            return true;
        case 3:
            if (formData.add_type == "1") {
                if (formData.wechat_id.length == 0) {
                    return false;
                } else if (formData.add_remark_enable == 1 && !formData.remarks.length) {
                    return false;
                }
                return true;
            }
            return true;
        case 4:
            return true;
        default:
            return false;
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 点击“上一步”
    if (type === "prev") {
        step.value--;
        return;
    }

    // 点击“下一步”
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            uni.$u.toast("请完成当前步骤");
        }
        return;
    }

    // 直接点击步骤条进行跳转
    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                const messages: { [key: number]: string } = {
                    2: "请至少添加一条线索",
                    3: "请完善加微设置",
                    4: "请设定时间",
                };
                uni.$u.toast(messages[i] || "请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const { optionsData } = useDictOptions<{
    wechatLists: any[];
}>({
    wechatLists: {
        api: getWeChatLists,
        params: { page_size: 9999 },
        transformData: (res: any) =>
            res.lists?.map((item: any) => ({
                text: item.wechat_nickname,
                value: item.wechat_id,
            })),
    },
});

const handleEditClue = async (index?: number) => {
    showClueEdit.value = true;
    await nextTick();
    if (index) {
        editClueIndex.value = index || -1;
        clueEditRef.value.setFormData(formData.keywords[index]);
    }
};

const handleClueConfirm = (val: string) => {
    if (editClueIndex.value == -1) {
        formData.keywords.push(val);
    } else {
        formData.keywords[editClueIndex.value] = val;
    }
    editClueIndex.value = -1;
    showClueEdit.value = false;
};

const handleDeleteClue = (index: number) => {
    formData.keywords.splice(index, 1);
};

const handleGreetingContentSetting = (type: GreetingContentSettingTypeEnum) => {
    currentGreetingContentSettingType.value = type;
    uni.$u.route({
        url: "/ai_modules/sph/pages/task_prompt/task_prompt",
        params: {
            type,
            prompt: JSON.stringify(formData[type]),
        },
    });
};

const handleAddRemark = () => {
    if (!addRemarkContent.value) {
        uni.$u.toast("请输入加好友备注内容");
        return;
    }
    if (editRemarkIndex.value == -1) {
        formData.remarks.push(addRemarkContent.value);
    } else {
        formData.remarks[editRemarkIndex.value] = addRemarkContent.value;
    }
    editRemarkIndex.value = -1;
    addRemarkContent.value = "";
    showAddRemark.value = false;
};

const handleDeleteRemark = (index: number) => {
    formData.remarks.splice(index, 1);
};

const handleEditRemark = (index: number) => {
    editRemarkIndex.value = index;
    addRemarkContent.value = formData.remarks[index];
    showAddRemark.value = true;
};

const handleFrequency = (item: number, index: number) => {
    currentFrequency.value = index;
    formData.task_frep = item;
};

const formatDate = (date: string) => {
    return uni.$u.timeFormat(new Date(date), "mm月dd日");
};

const handleCustomDate = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/custom_date/custom_date",
        params: {
            date: formData.custom_date.length > 0 ? JSON.stringify(formData.custom_date) : null,
        },
    });
};

const handleStartTimeChange = (e: any) => {
    const { value } = e.detail;
    const endTime = new Date(`2000/01/01 ${value}`);

    formData.time_config[0] = value;
    endTime.setMinutes(endTime.getMinutes() + timeInterval);
    formData.time_config[1] = uni.$u.timeFormat(endTime, "hh:MM");
};

const handleEndTimeChange = (e: any) => {
    const { value } = e.detail;
    // 这里需要判断结束时间是否大于开始时间，并且要大于开始
    if (value <= formData.time_config[0]) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const startTIme = new Date(`2000/01/01 ${formData.time_config[0]}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() - startTIme.getTime() < timeInterval * 60 * 1000) {
        uni.$u.toast(`结束时间不能小于开始时间${timeInterval}分钟`);
        return;
    }
    formData.time_config[1] = value;
};

const handleEndTimeClick = () => {
    if (!formData.time_config[0]) {
        uni.$u.toast("请先选择开始时间");
        return;
    }
};

const handleCreateTaskSuccess = () => {
    showCreateTaskSuccessDialog.value = false;
    uni.$u.route({
        url: "/ai_modules/sph/pages/index/index",
        type: "reLaunch",
    });
};

const handleCreateTask = async () => {
    if (formData.device_codes.length == 0) {
        uni.$u.route({
            url: "/ai_modules/device/pages/device_choose/device_choose",
        });
        return;
    }
    if (formData.time_config[0] == "" || formData.time_config[1] == "") {
        uni.$u.toast("请选择时间");
        return;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createTask({
            ...formData,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            type: [AppTypeEnum.SPH],
        });
        uni.hideLoading();
        showCreateTaskSuccessDialog.value = true;
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

watch(
    () => appStore.config.wechat_remarks,
    () => {
        formData.remarks = getWechatRemarks.value;
    }
);

onLoad(({ type }: any) => {
    if (type) {
        formData.crawl_type = parseInt(type);
    }
    uni.$on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.TASK_AI_CLUE) {
            if (data.length === 0) return;
            formData.keywords.push(...data);
        }
        if (type === ListenerTypeEnum.CHOOSE_DEVICE) {
            if (data.length === 0) return;
            formData.device_codes = data;
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) return;
            currentFrequency.value = 5;
            formData.custom_date = data;
        }
    });
});

onUnload(() => {
    uni.$off("confirm");
});
</script>

<style scoped lang="scss">
.step-item {
    @apply flex flex-col items-center justify-center relative;
    &.active {
        .step-item-icon {
            @apply shadow-[0_0_0_2rpx_rgba(0,101,251,0.3)]  flex items-center justify-center;
            &::before {
                content: "";
                @apply w-[60%] h-[60%] bg-primary rounded-full;
            }
        }
        .step-item-title {
            @apply text-[#00000099];
        }
    }
    &-success-icon {
        @apply bg-[#0065fb4d] rounded-full w-[28rpx] h-[28rpx] flex items-center justify-center;
    }
    &-icon {
        @apply w-[28rpx] h-[28rpx] rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)];
    }
    &-title {
        @apply mt-[20rpx] text-[rgba(0,0,0,0.2)] font-bold text-xs;
    }
    &-line {
        @apply absolute right-[-18%] top-[15rpx] w-[40%] border-[0] border-b border-dashed border-[#D1D6D4];
    }
}

.keyword-item {
    @apply bg-white rounded-[20rpx] px-4 py-2 flex items-center gap-x-2 relative;
}

.frequency-item {
    @apply px-[32rpx] py-[16rpx] rounded-[10rpx] bg-[#F6F6F6];
    &.active {
        @apply text-primary shadow-[0_0_0_2rpx_#0065FB] bg-white;
    }
}
.date-item {
    @apply text-xs font-bold text-[#000000b3] rounded-[10rpx] px-[20rpx] py-[10rpx] bg-[#F6F6F6];
}
</style>
