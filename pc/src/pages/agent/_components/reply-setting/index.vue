<template>
    <div class="h-full flex flex-col bg-white">
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-[30px] py-[24px] flex flex-col gap-[20px]">
                    <div class="strategy-card">
                        <div class="card-header">
                            <span class="title">多条信息回复策略</span>
                            <span class="desc">设置短时间内连续收到多条消息的处理方式</span>
                        </div>
                        <div class="mt-[16px]">
                            <ElRadioGroup v-model="formData.multiple_type" class="custom-radio-group">
                                <ElRadio :value="0" border>逐条回复</ElRadio>
                                <ElRadio :value="1" border>
                                    <div class="flex items-center gap-1">
                                        <span>合并回复</span>
                                        <ElTooltip content="2分钟内未继续发送则合并回复">
                                            <div class="text-gray-400 leading-[0]">
                                                <Icon name="el-icon-QuestionFilled" :size="14" />
                                            </div>
                                        </ElTooltip>
                                    </div>
                                </ElRadio>
                                <ElRadio :value="2" border>
                                    <div class="flex items-center gap-1">
                                        <span>仅回复最后一条</span>
                                        <ElTooltip content="2分钟内未继续发送则仅回复最后一条">
                                            <div class="text-gray-400 leading-[0]">
                                                <Icon name="el-icon-QuestionFilled" :size="14" />
                                            </div>
                                        </ElTooltip>
                                    </div>
                                </ElRadio>
                            </ElRadioGroup>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-[20px]">
                        <div class="strategy-card-mini">
                            <div class="flex flex-col">
                                <span class="font-[900] text-tx-primary text-[14px]">语音回复 (WeChat)</span>
                                <span class="text-xs text-tx-secondary mt-[4px]">支持将语音转文字后回复</span>
                            </div>
                            <ElSwitch v-model="formData.voice_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="strategy-card-mini">
                            <div class="flex flex-col">
                                <span class="font-[900] text-tx-primary text-[14px]">分段展示回复</span>
                                <span class="text-xs text-tx-secondary mt-[4px]">按段落逐条展示，模拟真人输入</span>
                            </div>
                            <ElSwitch v-model="formData.paragraph_enable" :active-value="1" :inactive-value="0" />
                        </div>
                    </div>

                    <div class="strategy-card" :class="{ 'is-active': formData.image_enable == 1 }">
                        <div class="flex items-center justify-between mb-[12px]">
                            <div class="flex flex-col">
                                <span class="title">图片回复策略</span>
                                <span class="desc">接收到图片时发送的固定引导或说明</span>
                            </div>
                            <ElSwitch v-model="formData.image_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-[12px] transition-all" v-if="formData.image_enable == 1">
                            <ElInput
                                v-model="formData.image_reply"
                                type="textarea"
                                placeholder="请输入图片场景下的自动回复内容..."
                                resize="none"
                                :rows="3"
                                class="custom-textarea" />
                        </div>
                    </div>

                    <div class="strategy-card" :class="{ 'is-active': formData.stop_enable == 1 }">
                        <div class="flex items-center justify-between mb-[12px]">
                            <div class="flex flex-col">
                                <span class="title">敏感词停止策略</span>
                                <span class="desc">命中特定关键词时停止回复该条信息</span>
                            </div>
                            <ElSwitch v-model="formData.stop_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-[12px]" v-if="formData.stop_enable == 1">
                            <ElInput
                                v-model="formData.stop_keywords"
                                type="textarea"
                                placeholder="输入停用词，用英文分号 (;) 分隔"
                                resize="none"
                                :rows="3"
                                class="custom-textarea" />
                        </div>
                    </div>

                    <div class="strategy-card" :class="{ 'is-active': formData.working_enable == 1 }">
                        <div class="flex items-center justify-between mb-[16px]">
                            <div class="flex flex-col">
                                <span class="title">AI 自动接管时间段</span>
                                <ElTooltip
                                    popper-class="!w-[300px]"
                                    content="提醒：用户可以设置 AI 在指定的时间段内接管对话，超出接管时间的消息，则会自动发送用户预先设定的固定回复。">
                                    <div class="flex items-center gap-[6px] mt-[4px]">
                                        <span class="desc text-error font-medium">一小时内仅生效一次</span>
                                        <span class="text-error opacity-70 cursor-pointer leading-[0]">
                                            <Icon name="el-icon-InfoFilled" :size="14" />
                                        </span>
                                    </div>
                                </ElTooltip>
                            </div>
                            <ElSwitch v-model="formData.working_enable" :active-value="1" :inactive-value="0" />
                        </div>

                        <div
                            v-if="formData.working_enable == 1"
                            class="space-y-[20px] pt-[12px] border-t border-br-extra-light">
                            <div>
                                <div class="text-[13px] font-medium text-tx-primary mb-[10px]">生效日期</div>
                                <ElCheckboxGroup v-model="weekList" class="custom-checkbox-group">
                                    <ElCheckbox v-for="i in 7" :key="i" :value="i" border class="!mr-0">
                                        {{ ["周一", "周二", "周三", "周四", "周五", "周六", "周日"][i - 1] }}
                                    </ElCheckbox>
                                </ElCheckboxGroup>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-[10px]">
                                    <span class="text-[13px] font-medium text-tx-primary">生效时间段</span>
                                    <ElButton type="primary" link @click="addWorkingTime" class="font-medium">
                                        <Icon name="el-icon-Plus" />
                                        <span class="ml-[2px]">新增时段</span>
                                    </ElButton>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 mt-3">
                                    <div v-for="(item, index) in workingTime" :key="index">
                                        <div
                                            class="time-select-wrapper"
                                            :class="{
                                                '!border-error': workTimeErrorIndex.includes(index),
                                            }">
                                            <ElTimeSelect
                                                v-model="item.start_time"
                                                class="!w-[80px]"
                                                prefix-icon=""
                                                start="00:00"
                                                step="00:15"
                                                end="23:59"
                                                :max-time="item.end_time" />
                                            <div class="">至</div>
                                            <ElTimeSelect
                                                v-model="item.end_time"
                                                class="!w-[80px]"
                                                prefix-icon=""
                                                start="00:00"
                                                step="00:15"
                                                end="23:59"
                                                :min-time="item.start_time" />
                                            <div class="ml-2 w-4 h-4 flex-shrink-0" @click="deleteWorkingTime(index)">
                                                <close-btn :icon-size="10" />
                                            </div>
                                        </div>
                                    </div>
                                    <ElButton type="primary" size="small" @click="addWorkingTime">新增时间段</ElButton>
                                </div>
                            </div>

                            <div>
                                <div class="text-[13px] font-medium text-tx-primary mb-[10px]">
                                    接管时间外的自动回复
                                </div>
                                <ElInput
                                    v-model="formData.non_working_reply"
                                    type="textarea"
                                    placeholder="抱歉，当前不在 AI 接管时间，请留下您的联系方式..."
                                    :rows="4"
                                    class="custom-textarea" />
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="p-[20px] border-t border-br flex justify-center bg-white shrink-0">
            <ElButton type="primary" class="save-btn" :loading="isLockSubmit" @click="lockSubmit">
                保存策略配置
            </ElButton>
        </div>
    </div>
</template>
<script setup lang="ts">
import { saveReplyStrategy, getReplyStrategy } from "@/api/agent";
import dayjs from "dayjs";

/**
 * @description 智能体回复策略设置组件
 * @summary 用户可以配置多种场景下的自动回复行为。
 */

const props = defineProps<{
    agentId: string | number;
}>();

const emit = defineEmits<{ (event: "close"): void }>();

// 回复策略表单数据
const formData = reactive({
    multiple_type: 0, // 多轮回复类型 0: 逐条回复 1: 合并回复 2：只回复最后一条
    number_chat_rounds: 3, // 聊天轮数策略
    voice_enable: 0, // 语音消息回复策略 0：关闭 1：开启
    image_enable: 0, // 图片消息回复策略 0：关闭 1：开启
    image_reply: "", // 图片消息回复内容
    stop_enable: 0, // 停止回复策略 0：关闭 1：开启
    stop_keywords: "", // 停止回复关键词(用英文;分割)
    paragraph_enable: 0, // 段落回复策略 0：关闭 1：开启
    working_enable: 0, // 接管时间策略 0：关闭 1：开启
    non_working_reply: "",
    working_time: {
        1: [],
        2: [],
        3: [],
        4: [],
        5: [],
        6: [],
        7: [],
    },
});

const weekList = ref<any[]>([]);

const workingTime = ref<{ start_time: string; end_time: string }[]>([]);

const workTimeErrorIndex = ref<number[]>([]);

/**
 * @description 新增接管时间
 */
const addWorkingTime = () => {
    if (workingTime.value.length === 0) {
        workingTime.value.push({ start_time: "09:00", end_time: "09:15" });
    } else {
        workingTime.value.push({
            start_time: workingTime.value.at(-1).end_time,
            end_time: dayjs(workingTime.value.at(-1).end_time, "HH:mm").add(15, "minute").format("HH:mm"),
        });
    }
};

/**
 * @description 删除接管时间
 */
const deleteWorkingTime = (index: number) => {
    useNuxtApp().$confirm({
        title: "提示",
        message: "确定删除该时间段吗？",
        onConfirm: () => {
            workingTime.value.splice(index, 1);
        },
    });
};

/**
 * @description 验证并保存回复策略
 */
const handleConfirm = async () => {
    if (formData.image_enable == 1 && !formData.image_reply) {
        feedback.msgWarning("请输入图片消息回复内容");
        return;
    }
    if (formData.stop_enable == 1 && !formData.stop_keywords) {
        feedback.msgWarning("请输入停止回复关键词");
        return;
    }
    if (formData.working_enable == 1) {
        if (weekList.value.length === 0) {
            feedback.msgWarning("请选择接管日期");
            return;
        }
        if (workingTime.value.length === 0) {
            feedback.msgWarning("请设置接管时间");
            return;
        }
        const { valid, indexes, errorType } = validateSchedule(workingTime.value);
        if (!valid) {
            workTimeErrorIndex.value = indexes;
            feedback.msgWarning(errorType);
            return;
        }
        if (!formData.non_working_reply) {
            feedback.msgWarning("请输入在接管时间外的自动回复内容");
            return;
        }

        // 把workingTime的start_time 和end_time 拼接为start_time-end_time
        const workingTimeStr = workingTime.value.map((item) => {
            return `${item.start_time}-${item.end_time}`;
        });
        Object.keys(formData.working_time).forEach((item) => {
            // 判断item 是否选中，如果选中，则把workingTimeStr 添加到formData.working_time[item]
            if (weekList.value.includes(Number(item))) {
                formData.working_time[item] = workingTimeStr;
            } else {
                formData.working_time[item] = [];
            }
        });
    }
    try {
        await saveReplyStrategy({
            ...formData,
            robot_id: props.agentId,
        });
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError((error as string) || "保存失败");
    }
};

function validateSchedule(list) {
    const toMin = (t) => {
        const [h, m] = (t || "").split(":").map(Number);
        return h * 60 + m;
    };

    for (let i = 0; i < list.length; i++) {
        const cur = list[i];

        // 1. 空值检查
        if (!cur || cur.start_time == null || cur.start_time === "" || cur.end_time == null || cur.end_time === "") {
            return { valid: false, errorType: "选择时间不能为空", indexes: [i] };
        }

        const s = toMin(cur.start_time);
        const e = toMin(cur.end_time);

        // 2. 自己倒序
        if (s >= e) {
            return { valid: false, errorType: "选择时间冲突", indexes: [i] };
        }

        // 3. 与上一段比较
        if (i > 0) {
            const prev = list[i - 1];
            const pe = toMin(prev.end_time);

            if (s < pe) {
                // 重叠 / 顺序错误
                return { valid: false, errorType: "选择时间冲突", indexes: [i - 1, i] };
            }
        }
    }
    return { valid: true, indexes: [] };
}

// 使用 useLockFn 防止重复提交
const { lockFn: lockSubmit, isLock: isLockSubmit } = useLockFn(handleConfirm);

/**
 * @description 获取已保存的回复策略
 */
const getReplyStrategyFn = async () => {
    try {
        const data = await getReplyStrategy({ id: props.agentId });
        // 使用 setFormData 更新表单，确保只更新存在的字段
        if (data) {
            setFormData(data, formData);
        }
        // 设置接管时间
        if (data.working_time) {
            Object.keys(data.working_time).forEach((key) => {
                // 判断data.working_time[key]数组是否有值
                if (data.working_time[key].length > 0) {
                    weekList.value.push(Number(key));
                    // 把data.working_time[key]数组转换为start_time-end_time
                    workingTime.value = data.working_time[key].map((item: any) => {
                        return { start_time: item.split("-")[0], end_time: item.split("-")[1] };
                    });
                }
            });
        }
    } catch (error) {
        console.error("获取回复策略失败:", error);
    }
};

// 组件挂载时获取数据
onMounted(() => {
    getReplyStrategyFn();
});
</script>

<style scoped lang="scss">
/* 卡片基础样式 */
.strategy-card {
    @apply p-[20px] bg-white border border-br rounded-lg transition-all;

    .card-header {
        @apply flex flex-col;
        .title {
            @apply text-[15px] font-[900] text-tx-primary;
        }
        .desc {
            @apply text-xs text-tx-secondary mt-[2px];
        }
    }

    &.is-active {
        @apply border-primary-light-8 shadow-[0_8px_24px_-10px_rgba(0,101,251,0.1)];
    }
}

.strategy-card-mini {
    @apply flex items-center justify-between p-[16px] bg-gray-50 border border-[transparent] rounded-lg;
}

/* 时间段选择框 */
.time-range-box {
    @apply flex items-center gap-[8px] px-[12px] py-[6px] bg-white border border-br rounded-[10px] relative transition-all;

    &:hover {
        @apply border-primary-light-5;
    }
    &.is-error {
        @apply border-error bg-red-50;
    }

    .close-btn {
        @apply w-[18px] h-[18px] flex items-center justify-center rounded-full bg-gray-100 text-gray-400 cursor-pointer hover:bg-error hover:text-white transition-all ml-[4px];
        font-size: 10px;
    }
}

:deep(.custom-radio-group) {
    @apply flex gap-[12px];
    .el-radio {
        @apply mr-0 flex-1 h-[44px] rounded-[10px] bg-gray-50 border-[transparent] transition-all;
        &.is-bordered.is-checked {
            @apply bg-blue-50 border-primary-light-7;
        }
    }
}

:deep(.custom-checkbox-group) {
    @apply grid grid-cols-7 gap-[8px];
    .el-checkbox {
        @apply mr-0 h-[40px] flex items-center justify-center rounded-[8px] bg-gray-50 border-[transparent] transition-all;
        &.is-bordered.is-checked {
            @apply bg-blue-50 border-primary-light-7;
        }
        .el-checkbox__label {
            @apply pl-0 text-xs font-medium;
        }
        .el-checkbox__input {
            @apply hidden;
        }
    }
}
.time-select-wrapper {
    @apply flex items-center gap-x-2  rounded-md px-2 border border-[var(--el-border-color)];
    :deep(.el-select .el-select__wrapper) {
        padding: 0;
        box-shadow: none;
    }
}
.save-btn {
    @apply w-[360px] !h-[48px] !rounded-xl font-[900] shadow-[0_10px_20px_-5px_rgba(0,101,251,0.2)];
}
</style>
