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
                <div class="text-sm font-bold text-tx-regular">返回列表</div>
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
                                        class="!text-xs font-bold"
                                        @click="handleAddKeyword('ai')">
                                        <Icon name="el-icon-MagicStick" />
                                        <span class="ml-1">AI 智能扩词</span>
                                    </ElButton>
                                    <ElButton
                                        type="info"
                                        link
                                        class="!text-xs font-bold"
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
                                        class="w-8 h-8 flex-shrink-0 rounded-lg bg-white shadow-sm flex items-center justify-center text-xs font-bold text-tx-secondary group-hover:text-primary">
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
                                    <div class="text-sm font-bold text-tx-regular mb-4">执行周期</div>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="item in [1, 3, 5, 10, 30]"
                                            :key="item"
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-bold transition-all border"
                                            :class="
                                                formData.task_frep == item && currentFrequency != 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="handleFrequency(item, 0)">
                                            {{ item }}天
                                        </div>
                                        <div
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-bold transition-all border"
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
                                            placeholder="请选择日期"
                                            type="dates"
                                            :disabled-date="getDisabledTaskDate"
                                            class="!w-full custom-date-picker" />
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-dashed border-br-light">
                                    <div class="text-sm font-bold text-tx-regular mb-4">每日执行时段</div>
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

                        <div class="bg-white p-6 rounded-2xl border border-br relative overflow-hidden">
                            <div class="flex justify-between items-center mb-6">
                                <div class="text-base font-black flex items-center gap-2">
                                    <span class="w-1 h-4 bg-orange-500 rounded-full"></span>
                                    自动加好友设置
                                </div>
                                <ElSwitch v-model="formData.add_type" active-value="1" inactive-value="0" />
                            </div>

                            <div
                                v-if="formData.add_type == '1'"
                                class="space-y-5 animate-in fade-in slide-in-from-top-2 duration-300">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-1">
                                        <ElFormItem label="使用微信" />
                                        <ElSelect
                                            v-model="formData.wechat_id"
                                            multiple
                                            collapse-tags
                                            :show-arrow="false"
                                            class="custom-select w-full">
                                            <ElOption
                                                v-for="item in deviceOptions.wechatLists"
                                                :key="item.wechat_id"
                                                :label="item.wechat_nickname"
                                                :value="item.wechat_id" />
                                        </ElSelect>
                                    </div>
                                    <div class="col-span-1">
                                        <ElFormItem label="加微规则" />
                                        <ElSelect
                                            v-model="formData.wechat_reg_type"
                                            class="custom-select w-full"
                                            :show-arrow="false">
                                            <ElOption label="全部" :value="0" />
                                            <ElOption label="微信号" :value="1" />
                                            <ElOption label="手机号" :value="2" />
                                        </ElSelect>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-xl flex gap-8">
                                    <div class="flex-1">
                                        <div class="text-xs font-bold text-tx-secondary mb-2 text-center">
                                            每天执行次数
                                        </div>
                                        <ElInputNumber
                                            v-model="formData.add_number"
                                            :min="1"
                                            controls-position="right"
                                            class="!w-full" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-xs font-bold text-tx-secondary mb-2 text-center">
                                            间隔时间 (分钟)
                                        </div>
                                        <ElInputNumber
                                            v-model="formData.add_interval_time"
                                            :min="1"
                                            controls-position="right"
                                            class="!w-full" />
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <ElFormItem label="验证备注内容" />
                                        <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-lg scale-90">
                                            <span
                                                class="text-[10px] font-bold px-2"
                                                :class="
                                                    formData.add_remark_enable == 0
                                                        ? 'text-primary'
                                                        : 'text-tx-placeholder'
                                                "
                                                >单一内容</span
                                            >
                                            <ElSwitch
                                                v-model="formData.add_remark_enable"
                                                :active-value="1"
                                                :inactive-value="0"
                                                size="small" />
                                            <span
                                                class="text-[10px] font-bold px-2"
                                                :class="
                                                    formData.add_remark_enable == 1
                                                        ? 'text-primary'
                                                        : 'text-tx-placeholder'
                                                "
                                                >随机库</span
                                            >
                                        </div>
                                    </div>

                                    <div
                                        v-if="formData.add_remark_enable == 1"
                                        class="border border-br-light rounded-xl p-3 bg-gray-50/50">
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="(item, index) in formData.remarks"
                                                :key="index"
                                                class="bg-white border border-br-light px-3 py-1.5 rounded-lg flex items-center shadow-sm text-xs group hover:border-primary transition-all">
                                                <span class="text-tx-regular mr-2">{{ item }}</span>
                                                <div class="w-4 h-4" @click="handleDeleteRemark(index)">
                                                    <close-btn :icon-size="14" />
                                                </div>
                                            </div>
                                            <ElButton class="!h-7 !px-3" plain @click="handleAddRemark">
                                                <Icon name="el-icon-Plus" />
                                                <span class="ml-1">添加文案</span>
                                            </ElButton>
                                        </div>
                                    </div>
                                    <ElInput
                                        v-else
                                        v-model="formData.remark"
                                        type="textarea"
                                        :rows="3"
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
import { createTask } from "~/api/customer";
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
</style>
