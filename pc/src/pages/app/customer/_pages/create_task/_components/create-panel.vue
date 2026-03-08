<template>
    <div class="h-full bg-white rounded-2xl flex flex-col min-w-[1000px] border border-br overflow-hidden">
        <div
            class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-br-extra-light bg-white z-10">
            <div class="flex items-center gap-2 cursor-pointer group" @click="emit('back')">
                <div
                    class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                    <span class="text-tx-regular group-hover:text-primary leading-[0]">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                    </span>
                </div>
                <div class="text-sm font-medium text-tx-regular">返回列表</div>
            </div>
            <div class="flex items-center gap-3">
                <ElButton
                    type="primary"
                    class="!rounded-full !h-11 px-8 !font-black shadow-light hover:scale-105 active:scale-95 transition-all"
                    :loading="isLock"
                    @click="lockFn">
                    确认发布任务
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0 bg-gray-50/50">
            <ElScrollbar>
                <div class="w-[640px] mx-auto py-10 pb-24">
                    <div class="mb-8">
                        <h1 class="text-3xl font-[900] text-gray-950">创建自动获客任务</h1>
                        <p class="text-sm text-tx-secondary mt-2">配置您的线索监测策略，AI 将自动为您筛选并触达客户</p>
                    </div>

                    <div class="flex flex-col gap-y-3">
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                基本信息
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <ElFormItem label="任务名称" tip="给任务起一个好记的名字" />
                                    <ElInput
                                        v-model="formData.name"
                                        placeholder="例如：美妆行业潜在客户监测"
                                        class="custom-input"
                                        maxlength="30"
                                        show-word-limit
                                        clearable />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <ElFormItem label="任务类型" />
                                        <ElSelect
                                            v-model="formData.crawl_type"
                                            class="custom-select w-full"
                                            :show-arrow="true">
                                            <ElOption
                                                v-for="item in crawlTypeOptions"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value" />
                                        </ElSelect>
                                    </div>
                                    <div>
                                        <ElFormItem label="执行设备" />
                                        <ElSelect
                                            v-model="formData.device_codes"
                                            multiple
                                            collapse-tags
                                            class="custom-select w-full">
                                            <ElOption
                                                v-for="item in deviceOptions.deviceLists"
                                                :key="item.device_code"
                                                :label="item.device_name"
                                                :value="item.device_code" />
                                        </ElSelect>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="flex justify-between items-center mb-6">
                                <div class="text-base font-black flex items-center gap-2">
                                    <span class="w-1 h-4 bg-primary rounded-full"></span>
                                    检索关键词
                                </div>
                                <div class="flex gap-2">
                                    <ElButton
                                        type="primary"
                                        link
                                        class="!text-xs font-medium"
                                        @click="handleAddKeyword('ai')">
                                        <Icon name="el-icon-MagicStick" />
                                        <span class="ml-1">AI 智能扩词</span>
                                    </ElButton>
                                    <ElButton
                                        type="info"
                                        link
                                        class="!text-xs font-medium"
                                        @click="handleAddKeyword('manual')">
                                        <Icon name="el-icon-Plus" />
                                        <span class="ml-1">手动添加</span>
                                    </ElButton>
                                </div>
                            </div>

                            <div class="space-y-3 max-h-[320px] overflow-y-auto pr-2 custom-scrollbar">
                                <div
                                    v-for="(item, index) in formData.keywords"
                                    :key="index"
                                    class="flex items-center gap-3 bg-gray-50 p-2 rounded-xl border border-[transparent] hover:border-br-light hover:bg-white transition-all group">
                                    <div
                                        class="w-8 h-8 flex-shrink-0 rounded-lg bg-white shadow-sm flex items-center justify-center text-xs font-medium text-tx-secondary group-hover:text-primary">
                                        {{ index + 1 }}
                                    </div>
                                    <ElInput
                                        v-model="formData.keywords[index]"
                                        placeholder="请输入关键词"
                                        class="flex-1 keyword-input" />
                                    <div
                                        @click="handleKeywordDelete(index)"
                                        class="p-2 cursor-pointer opacity-0 group-hover:opacity-100 text-tx-placeholder hover:text-danger transition-all">
                                        <Icon name="el-icon-Delete" :size="16" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                时间与频率
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <div class="text-sm font-medium text-tx-regular mb-4">执行周期</div>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="item in [1, 3, 5, 10, 30]"
                                            :key="item"
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                            :class="
                                                formData.task_frep == item && currentFrequency != 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="handleFrequency(item, 0)">
                                            {{ item }}天
                                        </div>
                                        <div
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                            :class="
                                                currentFrequency == 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="currentFrequency = 5">
                                            自定义日期
                                        </div>
                                    </div>
                                    <div v-if="currentFrequency == 5" class="mt-4">
                                        <ElDatePicker
                                            v-model="formData.custom_date"
                                            class="!w-full custom-date-picker"
                                            placeholder="请选择日期"
                                            type="dates"
                                            value-format="YYYY-MM-DD"
                                            :disabled-date="getDisabledTaskDate" />
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-dashed border-br-light">
                                    <div class="text-sm font-medium text-tx-regular mb-4">每日执行时段</div>
                                    <ElTimePicker
                                        v-model="formData.time_config"
                                        type="time"
                                        is-range
                                        range-separator="至"
                                        start-placeholder="开始"
                                        end-placeholder="结束"
                                        format="HH:mm"
                                        value-format="HH:mm"
                                        class="!w-full custom-time-picker"
                                        :show-arrow="false" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black flex items-center gap-2">
                                <span class="w-1 h-4 bg-green-500 rounded-full"></span>
                                线索识别方式
                            </div>
                            <div class="mt-4">
                                <ElRadioGroup v-model="formData.ocr_type">
                                    <ElRadio :value="1">
                                        <ElTooltip
                                            popper-class="w-[200px]"
                                            :content="`云端OCR识别（每条扣 ${getOCRCloudToken} 算力）使用云端OCR服务识别微信号，每次识别消耗${getOCRCloudToken}算力，识别率更高，支持更复杂的图片和场景`">
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-tx-secondary">云端OCR识别</span>
                                                <Icon name="el-icon-QuestionFilled" color="var(--gray-500)" />
                                            </div>
                                        </ElTooltip>
                                    </ElRadio>
                                    <ElRadio :value="2">
                                        <ElTooltip
                                            popper-class="w-[200px]"
                                            :content="`本地识别（每条扣${getOCRLocalToken}算力）使用系统内置识别逻辑完成，识别率较依赖本地环境，复杂图片可能不够精准`">
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-tx-secondary">本地识别</span>
                                                <Icon name="el-icon-QuestionFilled" color="var(--gray-500)" />
                                            </div>
                                        </ElTooltip>
                                    </ElRadio>
                                </ElRadioGroup>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light relative overflow-hidden">
                            <div class="flex justify-between items-center">
                                <div class="text-base font-black flex items-center gap-2">
                                    <span class="w-1 h-4 bg-orange-500 rounded-full"></span>
                                    自动加好友设置
                                </div>
                                <ElSwitch v-model="formData.add_type" active-value="1" inactive-value="0" />
                            </div>

                            <div v-if="formData.add_type == '1'" class="mt-6 space-y-6">
                                <section class="bg-white p-7 rounded-[24px] border border-slate-100">
                                    <header class="flex items-center justify-between mb-6">
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-base font-[1000] text-slate-800 tracking-tight">
                                                加微任务执行设置
                                            </h3>
                                        </div>
                                    </header>

                                    <div class="space-y-6">
                                        <div class="bg-[#f8fafc]/80 p-1.5 rounded-2xl inline-flex w-full">
                                            <div
                                                v-for="opt in [
                                                    { label: '当日获客完成后执行', val: 0 },
                                                    { label: '自定义执行时间', val: 1 },
                                                ]"
                                                :key="opt.val"
                                                @click="formData.wechat_time_type = opt.val as 0 | 1"
                                                :class="[
                                                    'flex-1 py-2.5 text-center text-sm font-black rounded-xl cursor-pointer transition-all duration-300',
                                                    formData.wechat_time_type === opt.val
                                                        ? 'bg-white text-primary '
                                                        : 'text-slate-400 hover:text-slate-600',
                                                ]">
                                                {{ opt.label }}
                                            </div>
                                        </div>

                                        <div v-if="formData.wechat_time_type == 1" class="space-y-6 pt-2">
                                            <div>
                                                <div
                                                    class="text-[13px] font-black text-slate-500 mb-4 flex items-center gap-2">
                                                    <Icon name="el-icon-Calendar" :size="14" /> 执行周期选择
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <div
                                                        v-for="item in [1, 3, 5, 10, 30]"
                                                        :key="item"
                                                        class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                                        :class="
                                                            formData.wechat_task_frep == item &&
                                                            currentWechatFrequency != 5
                                                                ? 'bg-primary text-white border-primary shadow-light'
                                                                : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                                        "
                                                        @click="handleWechatFrequency(item, 0)">
                                                        {{ item }}天
                                                    </div>
                                                    <div
                                                        class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                                        :class="
                                                            currentWechatFrequency == 5
                                                                ? 'bg-primary text-white border-primary shadow-light'
                                                                : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                                        "
                                                        @click="currentWechatFrequency = 5">
                                                        自定义日期
                                                    </div>
                                                </div>

                                                <div
                                                    v-if="currentWechatFrequency == 5"
                                                    class="mt-4 animate-in zoom-in-95 duration-300">
                                                    <ElDatePicker
                                                        v-model="formData.wechat_custom_date"
                                                        class="!w-full premium-picker"
                                                        placeholder="选择具体执行日期"
                                                        type="dates"
                                                        value-format="YYYY-MM-DD"
                                                        :disabled-date="getDisabledTaskDate" />
                                                </div>
                                            </div>

                                            <div class="pt-6 border-t border-dashed border-slate-100">
                                                <div class="flex justify-between items-end mb-4">
                                                    <div>
                                                        <div
                                                            class="text-[13px] font-black text-slate-500 mb-1 flex items-center gap-2">
                                                            <Icon name="el-icon-AlarmClock" :size="14" /> 每日执行时段
                                                        </div>
                                                        <p class="text-[11px] text-slate-400 font-medium">
                                                            早于获客任务时，将在次日顺延执行
                                                        </p>
                                                    </div>
                                                </div>
                                                <ElTimePicker
                                                    v-model="formData.wechat_time_config"
                                                    is-range
                                                    range-separator="至"
                                                    start-placeholder="开始时间"
                                                    end-placeholder="结束时间"
                                                    format="HH:mm"
                                                    value-format="HH:mm"
                                                    class="!w-full"
                                                    :show-arrow="false" />
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <div class="grid grid-cols-2 gap-5">
                                    <div class="bg-white p-6 rounded-[24px] border border-slate-100">
                                        <label
                                            class="text-[13px] font-black text-slate-400 uppercase tracking-widest mb-3 block ml-1"
                                            >添加微信</label
                                        >
                                        <ElSelect
                                            v-model="formData.wechat_id"
                                            multiple
                                            collapse-tags
                                            class="w-full"
                                            placeholder="选择执行账号">
                                            <ElOption
                                                v-for="item in deviceOptions.wechatLists"
                                                :key="item.account"
                                                :label="item.nickname"
                                                :value="item.account" />
                                        </ElSelect>
                                    </div>
                                    <div class="bg-white p-6 rounded-[24px] border border-slate-100">
                                        <label
                                            class="text-[13px] font-black text-slate-400 uppercase tracking-widest mb-3 block ml-1"
                                            >加微规则</label
                                        >
                                        <ElSelect v-model="formData.wechat_reg_type" class="w-full">
                                            <ElOption label="全部方式" :value="0" />
                                            <ElOption label="仅微信号" :value="1" />
                                            <ElOption label="仅手机号" :value="2" />
                                        </ElSelect>
                                    </div>
                                </div>

                                <div
                                    class="p-7 rounded-[28px] border border-slate-100 flex gap-8 relative overflow-hidden group">
                                    <div class="flex-1 space-y-4 relative z-10">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                                                <Icon name="el-icon-Refresh" :size="16" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[13px] font-[1000] text-slate-700">每日执行次数</span>
                                                <span
                                                    class="text-[9px] text-slate-400 font-black uppercase tracking-tighter"
                                                    >Daily Total Count</span
                                                >
                                            </div>
                                        </div>
                                        <div class="premium-number-wrapper">
                                            <ElInputNumber
                                                v-model="formData.add_number"
                                                :min="1"
                                                controls-position="right"
                                                class="premium-input-number !w-full" />
                                        </div>
                                    </div>

                                    <div
                                        class="w-[1px] h-12 bg-gradient-to-b from-transparent via-slate-200 to-transparent self-center"></div>

                                    <div class="flex-1 space-y-4 relative z-10">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-blue-50 text-primary flex items-center justify-center">
                                                <Icon name="el-icon-Timer" :size="16" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[13px] font-[1000] text-slate-700"
                                                    >间隔时间 (分钟)</span
                                                >
                                                <span
                                                    class="text-[9px] text-slate-400 font-black uppercase tracking-tighter"
                                                    >Wait Interval</span
                                                >
                                            </div>
                                        </div>
                                        <div class="premium-number-wrapper">
                                            <ElInputNumber
                                                v-model="formData.add_interval_time"
                                                :min="1"
                                                controls-position="right"
                                                class="premium-input-number !w-full" />
                                        </div>
                                    </div>

                                    <div
                                        class="absolute -right-4 -bottom-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                                </div>

                                <div class="bg-white p-7 rounded-[28px] border border-slate-100">
                                    <header class="flex items-center justify-between mb-5">
                                        <h4 class="text-[15px] font-[1000] text-slate-800">验证备注内容</h4>
                                        <div class="flex items-center bg-slate-100 p-1 rounded-xl">
                                            <button
                                                @click="formData.add_remark_enable = 0"
                                                :class="[
                                                    'px-3 py-1.5 text-[11px] font-black rounded-lg transition-all',
                                                    formData.add_remark_enable == 0
                                                        ? 'bg-white text-primary '
                                                        : 'text-slate-400',
                                                ]">
                                                单一内容
                                            </button>
                                            <button
                                                @click="formData.add_remark_enable = 1"
                                                :class="[
                                                    'px-3 py-1.5 text-[11px] font-black rounded-lg transition-all',
                                                    formData.add_remark_enable == 1
                                                        ? 'bg-white text-primary shadow-sm'
                                                        : 'text-slate-400',
                                                ]">
                                                随机库
                                            </button>
                                        </div>
                                    </header>

                                    <div
                                        v-if="formData.add_remark_enable == 1"
                                        class="p-5 bg-slate-50 rounded-[20px] border border-dashed border-slate-200">
                                        <div class="flex flex-wrap gap-2.5">
                                            <div
                                                v-for="(item, index) in formData.remarks"
                                                :key="index"
                                                class="bg-white border border-slate-100 pl-3 pr-2 py-2 rounded-xl flex items-center shadow-sm group hover:border-primary transition-all cursor-pointer">
                                                <span class="text-xs font-medium text-slate-600 mr-3">{{ item }}</span>
                                                <button
                                                    @click.stop="handleDeleteRemark(index)"
                                                    class="w-5 h-5 rounded-md bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center">
                                                    <Icon name="el-icon-Close" :size="10" />
                                                </button>
                                            </div>
                                            <button
                                                @click="handleAddRemark"
                                                class="h-[34px] px-4 rounded-xl border-2 border-dashed border-[#0065fb]/30 text-primary hover:bg-primary hover:text-white transition-all flex items-center gap-2 text-xs font-black">
                                                <Icon name="el-icon-Plus" /> 添加文案
                                            </button>
                                        </div>
                                    </div>

                                    <ElInput
                                        v-else
                                        v-model="formData.remark"
                                        type="textarea"
                                        :rows="4"
                                        placeholder="你好，我是..."
                                        class="custom-textarea" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <ai-add-keyword
        v-if="isAddKeywordGen"
        ref="aiAddKeywordRef"
        :type="formData.crawl_type == CreateTypeEnum.VIDEO ? 2 : 3"
        @close="isAddKeywordGen = false"
        @success="handleAddKeywordSuccess" />
    <ai-add-friend
        v-if="isAddFriendGen"
        ref="aiAddFriendRef"
        @close="isAddFriendGen = false"
        @confirm="handleAddFriendSuccess" />
    <ai-private-chat
        v-if="isPrivateChatGen"
        ref="aiPrivateChatRef"
        @close="isPrivateChatGen = false"
        @confirm="handleAddPrivateChatSuccess" />
    <remark-pop
        v-if="isAddRemarkGen"
        ref="remarkPopupRef"
        @close="isAddRemarkGen = false"
        @confirm="handleAddRemarkConfirm" />
</template>

<script setup lang="ts">
import { createTask } from "@/api/customer";
import dayjs from "dayjs";
import { AppTypeEnum, TokensSceneEnum } from "@/enums/appEnums";
import { useCreateTask } from "@/pages/app/customer/_hooks/useCreateTask";
import { CreateTypeEnum } from "@/pages/app/customer/_enums";
import { useUserStore } from "@/stores/user";
import AiAddKeyword from "./ai-add-keyword.vue";
import AiAddFriend from "./ai-add-friend.vue";
import AiPrivateChat from "./ai-private-chat.vue";
import RemarkPop from "@/pages/app/customer/_components/remark-pop.vue";

const emit = defineEmits(["back"]);

interface FormData {
    name: string;
    device_codes: string[];
    type: number[];
    keywords: string[];
    chat_type: string;
    chat_number: number;
    chat_interval_time: number;
    add_type: "0" | "1";
    add_number: number;
    add_interval_time: number;
    remark: string;
    greeting_content: string;
    crawl_type: CreateTypeEnum;
    private_message_prompt: string;
    add_friends_prompt: string;
    wechat_id: string[];
    wechat_reg_type: 0 | 1 | 2;
    ocr_type: 1 | 2;
    add_remark_enable: 0 | 1;
    remarks: any[];
    task_frep: number;
    custom_date: string[];
    time_config: string[];
    wechat_time_type: 0 | 1;
    wechat_task_frep: number;
    wechat_time_config: string[];
    wechat_custom_date: string[];
}
enum GreetingContentSettingTypeEnum {
    ADD_FRIEND = "add_friend",
    PRIVATE_CHAT = "private_chat",
}

const userStore = useUserStore();

const getOCRCloudToken = computed(() => {
    return userStore.getTokenByScene(TokensSceneEnum.SPH_OCR)?.score;
});

const getOCRLocalToken = computed(() => {
    return userStore.getTokenByScene(TokensSceneEnum.SPH_LOCAL_OCR)?.score;
});

const formData = reactive<FormData>({
    name: `视频号获客任务${dayjs().format("YYYYMMDDHHmmss")}`,
    device_codes: [],
    type: [4],
    keywords: [""],
    chat_type: "0",
    chat_number: 30,
    chat_interval_time: 10,
    add_type: "1",
    remark: "",
    add_number: 15,
    add_interval_time: 10,
    greeting_content: "",
    crawl_type: CreateTypeEnum.ACCOUNT,
    private_message_prompt: "",
    add_friends_prompt: "",
    wechat_id: [],
    wechat_reg_type: 0,
    ocr_type: 1,
    add_remark_enable: 1,
    remarks: [],
    task_frep: 1,
    custom_date: [],
    time_config: ["", ""],
    wechat_time_type: 0,
    wechat_task_frep: 1,
    wechat_time_config: ["", ""],
    wechat_custom_date: [],
});

const taskErrorMsg = ref("");

const {
    getWechatRemarks,
    deviceOptions,
    currentFrequency,
    currentWechatFrequency,
    disabledDate,
    handleFrequency,
    handleWechatFrequency,
    isAddRemarkGen,
    remarkPopupRef,
    handleAddRemark,
    handleEditRemark,
    handleAddRemarkConfirm,
    handleDeleteRemark,
    checkTimeConfig,
    checkWechatTimeConfig,
} = useCreateTask(formData);

watch(
    getWechatRemarks,
    (val) => {
        formData.remarks = [...(val || [])];
    },
    { immediate: true }
);

const crawlTypeOptions = [
    {
        label: "视频获客",
        value: CreateTypeEnum.VIDEO,
    },
    {
        label: "账号获客",
        value: CreateTypeEnum.ACCOUNT,
    },
];

const getDisabledTaskDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

const isAddKeywordGen = ref(false);
const aiAddKeywordRef = shallowRef<InstanceType<typeof AiAddKeyword>>();
const handleAddKeyword = async (type: "ai" | "manual") => {
    if (type == "ai") {
        isAddKeywordGen.value = true;
        await nextTick();
        aiAddKeywordRef.value.open();
    } else {
        formData.keywords.push("");
    }
};

const handleAddKeywordSuccess = (keywords: string[]) => {
    if (formData.keywords.length == 0) return;
    formData.keywords.push(...keywords);
};

const handleKeywordDelete = (index: number) => {
    if (formData.keywords.length == 1) {
        feedback.msgWarning("检索关键词至少存在一个！");
        return;
    }
    formData.keywords.splice(index, 1);
};

const isAddFriendGen = ref(false);
const isPrivateChatGen = ref(false);
const aiAddFriendRef = shallowRef<InstanceType<typeof AiAddFriend>>();
const aiPrivateChatRef = shallowRef<InstanceType<typeof AiPrivateChat>>();
const handleGreetingContentSetting = async (type: GreetingContentSettingTypeEnum) => {
    if (type == GreetingContentSettingTypeEnum.ADD_FRIEND) {
        isAddFriendGen.value = true;
        await nextTick();
        aiAddFriendRef.value?.open();
        aiAddFriendRef.value?.setFormData({
            content: formData.add_friends_prompt,
        });
    } else {
        isPrivateChatGen.value = true;
        await nextTick();
        aiPrivateChatRef.value?.open();
        aiPrivateChatRef.value?.setFormData({
            content: formData.private_message_prompt,
        });
    }
};

const handleAddFriendSuccess = (content: string) => {
    isAddFriendGen.value = false;
    formData.add_friends_prompt = content;
};

const handleAddPrivateChatSuccess = (content: string) => {
    isPrivateChatGen.value = false;
    formData.private_message_prompt = content;
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.name) {
        feedback.msgWarning("请输入任务名称");
        return;
    } else if (formData.device_codes.length == 0) {
        feedback.msgWarning("请选择执行设备");
        return;
    } else if (formData.keywords.length == 1 && !formData.keywords[0]) {
        feedback.msgWarning("请输入检索关键词");
        return;
    } else if (formData.add_type == "1" && formData.wechat_id.length == 0) {
        feedback.msgWarning("请选择加微微信");
        return;
    } else if (currentFrequency.value == 5 && formData.custom_date.length == 0) {
        feedback.msgWarning("请选择自定义日期");
        return;
    } else if (!checkTimeConfig()) {
        return;
    } else if (formData.wechat_time_type == 1 && !checkWechatTimeConfig()) {
        feedback.msgWarning("请选择加微任务执行时段");
        return;
    } else if (formData.add_remark_enable == 1 && formData.remarks.length == 0) {
        feedback.msgWarning("请输入加好友备注内容");
        return;
    }
    try {
        await createTask({
            ...formData,
            keywords: formData.keywords.filter((item) => item),
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            type: [AppTypeEnum.SPH],
        });
        feedback.msgSuccess("创建成功");
        emit("back");
    } catch (error: any) {
        taskErrorMsg.value = error;
        feedback.msgError(error);
    }
});
</script>
<style scoped lang="scss">
:deep(.el-form-item) {
    @apply mb-1;
}

:deep(.custom-input),
:deep(.custom-select),
:deep(.custom-date-picker) {
    .el-input__wrapper {
        @apply rounded-xl bg-slate-50 border-[none] shadow-[none] h-12 px-4 transition-all;
        &:hover {
            @apply bg-slate-100;
        }
        &.is-focus {
            @apply bg-white shadow-[0_0_0_2px_#0065fb];
        }
    }
}

:deep(.custom-time-picker) {
    @apply bg-slate-50;
}

:deep(.premium-input-number) {
    .el-input__wrapper {
        .el-input__inner {
            @apply text-slate-700 font-[1000] text-lg text-left;
        }
    }
}
</style>
