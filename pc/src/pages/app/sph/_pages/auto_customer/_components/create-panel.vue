<template>
    <div class="h-full bg-app-bg-2 rounded-[20px] flex flex-col">
        <!-- 头部导航 -->
        <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#ffffff1a]">
            <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                <div class="text-white">返回</div>
            </div>
            <div class="flex items-center gap-1">
                <ElButton type="primary" class="!rounded-full !h-10 w-[98px]" :loading="isLock" @click="lockFn">
                    创建任务
                </ElButton>
            </div>
        </div>
        <!-- 内容区 -->
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="w-[456px] mx-auto mt-6">
                    <div class="text-[20px] font-bold text-white">创建新的自动获客任务</div>
                    <div class="mt-6 flex flex-col gap-y-3">
                        <div>
                            <div class="text-[#ffffff80] mb-3">任务名称</div>
                            <ElInput
                                v-model="formData.name"
                                placeholder="请输入任务名称"
                                class="!h-11"
                                maxlength="30"
                                show-word-limit
                                clearable />
                        </div>
                        <div>
                            <div class="text-[#ffffff80] mb-3">任务类型</div>
                            <div>
                                <ElSelect
                                    v-model="formData.crawl_type"
                                    placeholder="请选择任务类型"
                                    class="!h-11"
                                    popper-class="dark-select-popper"
                                    :show-arrow="false">
                                    <ElOption
                                        v-for="item in crawlTypeOptions"
                                        :label="item.label"
                                        :value="item.value"
                                        :key="item.value"></ElOption>
                                </ElSelect>
                            </div>
                        </div>
                        <div>
                            <div class="text-[#ffffff80] mb-3">执行设备</div>
                            <div>
                                <ElSelect
                                    v-model="formData.device_codes"
                                    placeholder="请选择执行设备"
                                    class="!h-11"
                                    multiple
                                    filterable
                                    clearable
                                    collapse-tags
                                    collapse-tags-tooltip
                                    popper-class="dark-select-popper"
                                    :show-arrow="false">
                                    <ElOption
                                        v-for="item in deviceOptions.deviceLists"
                                        :label="item.device_code"
                                        :value="item.device_code"
                                        :key="item.device_code"></ElOption>
                                </ElSelect>
                            </div>
                        </div>
                        <div>
                            <div class="text-[#ffffff80] mb-3">检索关键词</div>
                            <div class="keyword-content">
                                <div class="max-h-[200px] overflow-y-auto flex flex-col gap-y-2 dynamic-scroller">
                                    <div
                                        v-for="(item, index) in formData.keywords"
                                        :key="index"
                                        class="flex items-center bg-app-bg-3 gap-x-2 border border-app-border-2 px-3 h-11 rounded-md">
                                        <div
                                            class="rounded-[100px] text-white min-w-[32px] h-5 flex items-center justify-center"
                                            :class="[
                                                item ? 'bg-[#3BB840]' : 'shadow-[0_0_0_1px_var(--app-border-color-2)]',
                                            ]">
                                            {{ index + 1 }}
                                        </div>
                                        <div class="flex-1">
                                            <ElInput
                                                v-model="formData.keywords[index]"
                                                class="!h-11"
                                                placeholder="请输入相关关键词"
                                                maxlength="30"
                                                show-word-limit />
                                        </div>
                                        <div class="w-[1px] h-[12px] bg-[#ffffff1a]"></div>
                                        <div>
                                            <div class="w-4 h-4" @click="handleKeywordDelete(index)">
                                                <close-btn :icon-size="10" :theme="ThemeEnum.DARK"></close-btn>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end mt-3">
                                    <ElButton
                                        type="primary"
                                        class="!h-[26px] !text-[11px]"
                                        @click="handleAddKeyword('ai')"
                                        >AI添加</ElButton
                                    >
                                    <ElButton
                                        class="!h-[26px] !text-[11px] !border-app-border-2"
                                        color="#181818"
                                        @click="handleAddKeyword('manual')"
                                        >手动添加</ElButton
                                    >
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="text-[#ffffff80] mb-3">时间设置</div>
                            <div class="bg-app-bg-3 rounded-xl shadow-[0_0_0_1px_var(--app-border-color-2)] p-4">
                                <div class="text-white">任务执行天数</div>
                                <div class="mt-4">
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(item, index) in [1, 3, 5, 10, 30]"
                                            :key="index"
                                            class="cursor-pointer rounded-md px-4 py-2 border border-app-border-2 text-white hover:bg-app-bg-1"
                                            :class="[
                                                formData.task_frep == item && currentFrequency != 5 ? 'bg-primary' : '',
                                            ]"
                                            @click="handleFrequency(item, index)">
                                            {{ item }}天
                                        </div>
                                        <div
                                            class="cursor-pointer rounded-md px-4 py-2 border border-app-border-2 text-white"
                                            :class="[currentFrequency == 5 ? 'bg-primary' : '']"
                                            @click="currentFrequency = 5">
                                            自定义
                                        </div>
                                    </div>
                                    <div class="mt-2" v-if="currentFrequency == 5">
                                        <ElDatePicker
                                            v-model="formData.custom_date"
                                            type="dates"
                                            placeholder="请选择自定义日期"
                                            format="MM-DD"
                                            value-format="YYYY-MM-DD"
                                            popper-class="dark-date-picker-popper"
                                            :disabled-date="disabledDate" />
                                    </div>
                                </div>
                                <div class="text-white mt-4">每日执行时间</div>
                                <div class="mt-4">
                                    <ElTimePicker
                                        v-model="formData.time_config"
                                        type="time"
                                        is-range
                                        start-placeholder="请选择开始时间"
                                        end-placeholder="请选择结束时间"
                                        format="HH:mm"
                                        value-format="HH:mm"
                                        popper-class="dark-select-popper"
                                        :show-arrow="false" />
                                </div>
                                <div v-if="taskErrorMsg" class="mt-2">
                                    <div>任务冲突</div>
                                    <view class="text-[#FF2442] mt-1 text-xs">
                                        {{ taskErrorMsg }}
                                    </view>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-x-2">
                                <div class="text-[#ffffff80]">线索识别方式</div>
                                <ElTooltip content="请选择线索识别方式" placement="top">
                                    <div class="cursor-pointer">
                                        <Icon name="local-icon-tips2" color="#ffffff"></Icon>
                                    </div>
                                    <template #content>
                                        <div class="w-[303px]">
                                            <div>
                                                本地识别（每条扣{{
                                                    getOCRLocalToken
                                                }}算力）使用系统内置识别逻辑完成，识别率较依赖本地环境，复杂图片可能不够精准
                                            </div>
                                            <div class="mt-5">
                                                云端OCR识别（每条扣
                                                {{ getOCRCloudToken }} 算力）使用云端OCR服务识别微信号，每条线索消耗{{
                                                    getOCRCloudToken
                                                }}算力，识别率更高，支持更复杂的图片和场景
                                            </div>
                                        </div>
                                    </template>
                                </ElTooltip>
                            </div>
                            <div>
                                <ElRadioGroup v-model="formData.ocr_type">
                                    <ElRadio :value="1">
                                        <span class="text-white">云端OCR识别</span>
                                    </ElRadio>
                                    <ElRadio :value="2">
                                        <span class="text-white">本地识别</span>
                                    </ElRadio>
                                </ElRadioGroup>
                            </div>
                        </div>
                        <div
                            class="bg-app-bg-3 rounded-xl shadow-[0_0_0_1px_var(--app-border-color-2)] p-4"
                            v-if="false">
                            <div class="flex justify-between items-center">
                                <div class="text-white">未检测到联系方式自动私聊</div>
                                <div>
                                    <ElSwitch
                                        v-model="formData.chat_type"
                                        style="--el-switch-off-color: #333333"
                                        active-value="1"
                                        inactive-value="0" />
                                </div>
                            </div>
                            <template v-if="formData.chat_type == '1'">
                                <div class="mt-2">
                                    <div class="text-[#ffffff80] mb-3">私聊频率</div>
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-1">
                                            <div class="text-white mb-3">当天执行</div>
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex-1">
                                                    <ElInput
                                                        v-model="formData.chat_number"
                                                        v-number-input="{
                                                            min: 0,
                                                            max: 99,
                                                        }"
                                                        type="number"
                                                        class="!h-11" />
                                                </div>
                                                <span class="text-[#ffffff80]">次</span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-white mb-3">每次间隔</div>
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex-1">
                                                    <ElInput
                                                        v-model="formData.chat_interval_time"
                                                        v-number-input="{
                                                            min: 0,
                                                            max: 999,
                                                        }"
                                                        type="number"
                                                        class="!h-11" />
                                                </div>
                                                <span class="text-[#ffffff80]">分钟</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="text-white mb-3">私信招呼内容</div>
                                    <ElInput
                                        v-model="formData.greeting_content"
                                        type="textarea"
                                        placeholder="请输入打招呼内容，为了避免封控，系统将自动调用AI进行去重润色"
                                        resize="none"
                                        :rows="3" />
                                    <div class="flex justify-end mt-4">
                                        <ElButton
                                            type="primary"
                                            class="!h-[26px] !text-[11px]"
                                            @click="
                                                handleGreetingContentSetting(
                                                    GreetingContentSettingTypeEnum.PRIVATE_CHAT
                                                )
                                            "
                                            >AI提示词设置</ElButton
                                        >
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="bg-app-bg-3 rounded-xl shadow-[0_0_0_1px_var(--app-border-color-2)] p-4">
                            <div class="flex justify-between items-center">
                                <div class="text-white">自动加好友设置</div>
                                <div>
                                    <ElSwitch
                                        v-model="formData.add_type"
                                        style="--el-switch-off-color: #333333"
                                        active-value="1"
                                        inactive-value="0" />
                                </div>
                            </div>
                            <template v-if="formData.add_type == '1'">
                                <div class="mt-2">
                                    <div class="text-[#ffffff80] mb-3">加微微信：</div>
                                    <ElSelect
                                        v-model="formData.wechat_id"
                                        placeholder="请选择添加的微信"
                                        class="!h-11"
                                        multiple
                                        filterable
                                        clearable
                                        collapse-tags
                                        collapse-tags-tooltip
                                        popper-class="dark-select-popper"
                                        :show-arrow="false">
                                        <ElOption
                                            v-for="item in deviceOptions.wechatLists"
                                            :label="item.wechat_nickname"
                                            :value="item.wechat_id"
                                            :key="item.wechat_id"></ElOption>
                                    </ElSelect>
                                </div>
                                <div class="mt-2">
                                    <div class="text-[#ffffff80] mb-3">加微规则：</div>
                                    <ElSelect
                                        v-model="formData.wechat_reg_type"
                                        class="!h-11"
                                        popper-class="dark-select-popper"
                                        :show-arrow="false">
                                        <ElOption label="全部" :value="0"></ElOption>
                                        <ElOption label="微信号" :value="1"></ElOption>
                                        <ElOption label="手机号" :value="2"></ElOption>
                                    </ElSelect>
                                </div>
                                <div class="mt-2">
                                    <div class="text-[#ffffff80] mb-3">自动加好友频率：</div>
                                    <div class="flex items-center gap-x-4">
                                        <div class="flex-1">
                                            <div class="text-white mb-3">当天执行</div>
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex-1">
                                                    <ElInput
                                                        v-model="formData.add_number"
                                                        v-number-input="{
                                                            min: 1,
                                                            max: 99,
                                                        }"
                                                        type="number"
                                                        class="!h-11" />
                                                </div>
                                                <span class="text-[#ffffff80]">条</span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-white mb-3">每次间隔</div>
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex-1">
                                                    <ElInput
                                                        v-model="formData.add_interval_time"
                                                        v-number-input="{
                                                            min: 1,
                                                            max: 999,
                                                        }"
                                                        type="number"
                                                        class="!h-11" />
                                                </div>
                                                <span class="text-[#ffffff80]">分钟</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="flex items-center justify-between">
                                        <div class="text-white mb-3">加好友验证内容</div>
                                        <ElSwitch
                                            v-model="formData.add_remark_enable"
                                            style="--el-switch-off-color: #333333"
                                            :active-value="1"
                                            :inactive-value="0" />
                                    </div>
                                    <div class="mt-3" v-if="formData.add_remark_enable == 0">
                                        <ElInput
                                            v-model="formData.remark"
                                            type="textarea"
                                            placeholder="请输入备注内容，为了避免封控，系统将自动调用AI进行去重润色"
                                            resize="none"
                                            :rows="3" />
                                        <div class="flex justify-end mt-4">
                                            <ElButton
                                                type="primary"
                                                class="!h-[26px] !text-[11px]"
                                                @click="
                                                    handleGreetingContentSetting(
                                                        GreetingContentSettingTypeEnum.ADD_FRIEND
                                                    )
                                                "
                                                >AI提示词设置</ElButton
                                            >
                                        </div>
                                    </div>
                                    <div v-if="formData.add_remark_enable == 1">
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="(item, index) in formData.remarks"
                                                :key="index"
                                                class="cursor-pointer hover:bg-app-bg-1 transition-all duration-300 border border-app-border-2 rounded-md px-4 py-2 flex items-center"
                                                @click="handleEditRemark(item, index)">
                                                <div class="text-white text-xs">{{ item }}</div>
                                                <div class="w-[1px] h-[8px] bg-app-border-2 mx-2"></div>
                                                <div class="w-4 h-4" @click.stop="handleDeleteRemark(index)">
                                                    <close-btn :icon-size="10"></close-btn>
                                                </div>
                                            </div>
                                        </div>
                                        <ElDivider class="!my-4 !border-app-border-2" />
                                        <div class="flex justify-end">
                                            <ElButton
                                                class="!h-8 !border-[#ffffff1a]"
                                                color="#121212"
                                                @click="handleAddRemark">
                                                <Icon name="el-icon-Plus" color="#ffffff" :size="12"></Icon>
                                                <div class="text-white text-xs ml-1">新增文案</div>
                                            </ElButton>
                                        </div>
                                    </div>
                                </div>
                            </template>
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
import { createTask } from "@/api/sph";
import dayjs from "dayjs";
import { AppTypeEnum, TokensSceneEnum, ThemeEnum } from "@/enums/appEnums";
import { CreateTypeEnum } from "@/pages/app/sph/_enums";
import { useUserStore } from "@/stores/user";
import AiAddKeyword from "./ai-add-keyword.vue";
import AiAddFriend from "./ai-add-friend.vue";
import AiPrivateChat from "./ai-private-chat.vue";
import RemarkPop from "@/pages/app/sph/_components/remark-pop.vue";
import { useCreateTask } from "../../../_hooks/useCreateTask";
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
});

const taskErrorMsg = ref("");

const {
    getWechatRemarks,
    deviceOptions,
    currentFrequency,
    disabledDate,
    handleFrequency,
    isAddRemarkGen,
    remarkPopupRef,
    handleAddRemark,
    handleAddRemarkConfirm,
    handleEditRemark,
    handleDeleteRemark,
    checkTimeConfig,
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
.keyword-content {
    :deep(.el-input) {
        .el-input__wrapper {
            background-color: transparent !important;
            box-shadow: none;
        }
    }
}
:deep(.el-radio__input) {
    &:not(.is-checked) {
        .el-radio__inner {
            background-color: transparent !important;
            border-color: #2a2a2a !important;
        }
    }
}
</style>
