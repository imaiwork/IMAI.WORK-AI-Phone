<template>
    <div class="h-full flex flex-col py-6">
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-[30px] flex flex-col gap-4">
                    <!-- 多条信息回复策略 -->
                    <div class="flex flex-col">
                        <div>短时间内多条信息回复策略</div>
                        <div class="mt-2">
                            <ElRadioGroup v-model="formData.multiple_type">
                                <ElRadio :value="0">逐条回复</ElRadio>
                                <ElRadio :value="1">
                                    <ElTooltip content="当用户在两分钟以外，没有继续发送消息，则将多条信息合并回复">
                                        <div class="flex items-center gap-2">
                                            <span>合并回复</span>
                                            <Icon name="el-icon-WarningFilled" color="#9E9E9E" />
                                        </div>
                                    </ElTooltip>
                                </ElRadio>
                                <ElRadio :value="2">
                                    <ElTooltip content="当用户在两分钟以外，没有继续发送消息，只回复最后一条信息">
                                        <div class="flex items-center gap-2">
                                            <span> 只回复最后一条 </span>
                                            <Icon name="el-icon-WarningFilled" color="#9E9E9E" />
                                        </div>
                                    </ElTooltip>
                                </ElRadio>
                            </ElRadioGroup>
                        </div>
                    </div>
                    <!-- 语音消息回复策略 -->
                    <div class="flex flex-col">
                        <div>语音消息回复策略（目前只支持微信）</div>
                        <div class="mt-2">
                            <ElSwitch v-model="formData.voice_enable" :active-value="1" :inactive-value="0" />
                        </div>
                    </div>
                    <!-- 图片固定回复策略 -->
                    <div class="flex flex-col">
                        <div>智能体被调用时，接收到图片进行固定回复</div>
                        <div class="mt-2">
                            <ElSwitch v-model="formData.image_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-2" v-if="formData.image_enable == 1">
                            <ElInput
                                v-model="formData.image_reply"
                                type="textarea"
                                placeholder="点击输入您要回复的内容"
                                resize="none"
                                :rows="4" />
                        </div>
                    </div>
                    <!-- 停止回复策略 -->
                    <div class="flex flex-col">
                        <div class="">智能体被调用时，接收到某些信息后停止对该条信息进行回复</div>
                        <div class="mt-2">
                            <ElSwitch v-model="formData.stop_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-2" v-if="formData.stop_enable == 1">
                            <ElInput
                                v-model="formData.stop_keywords"
                                type="textarea"
                                placeholder="点击输入您要检测的词语"
                                resize="none"
                                :rows="4" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div>
                            <div>分段回复</div>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xs">
                                用户在聊天界面看到的 AI 回复 不是一次性的大段话，而是按段落逐条展示
                            </div>
                            <ElSwitch v-model="formData.paragraph_enable" :active-value="1" :inactive-value="0" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div>接管时间(AI回复)</div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xs text-error">
                                提醒：用户可以设置 AI
                                在指定的时间段内接管对话，超出接管时间的消息，则会自动发送用户预先设定的固定回复。<span
                                    class="font-bold"
                                    >（一小时内仅剩生效一次）</span
                                >
                            </div>
                            <ElSwitch v-model="formData.working_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-2" v-if="formData.working_enable == 1">
                            <ElCheckboxGroup v-model="weekList">
                                <ElCheckbox :value="1">周一</ElCheckbox>
                                <ElCheckbox :value="2">周二</ElCheckbox>
                                <ElCheckbox :value="3">周三</ElCheckbox>
                                <ElCheckbox :value="4">周四</ElCheckbox>
                                <ElCheckbox :value="5">周五</ElCheckbox>
                                <ElCheckbox :value="6">周六</ElCheckbox>
                                <ElCheckbox :value="7">周日</ElCheckbox>
                            </ElCheckboxGroup>
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
                                        <div
                                            class="ml-2 w-4 h-4 flex-shrink-0 bg-app-bg-1 rounded-full flex items-center justify-center cursor-pointer"
                                            @click="deleteWorkingTime(index)">
                                            <Icon name="el-icon-Close" color="#ffffff" :size="10"></Icon>
                                        </div>
                                    </div>
                                </div>
                                <ElButton type="primary" size="small" @click="addWorkingTime">新增时间段</ElButton>
                            </div>
                            <div class="mt-3">
                                <div class="mb-3">固定回复内容</div>
                                <ElInput
                                    v-model="formData.non_working_reply"
                                    type="textarea"
                                    placeholder="请输入在接管时间外的自动回复内容"
                                    resize="none"
                                    :rows="5" />
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <!-- 保存按钮 -->
        <div class="flex items-center justify-center mt-4">
            <ElButton
                type="primary"
                class="w-[318px] !rounded-full !h-[50px]"
                :loading="isLockSubmit"
                @click="lockSubmit">
                保存
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
.time-select-wrapper {
    @apply flex items-center gap-x-2  rounded-md px-2 border border-[var(--el-border-color)];
    :deep(.el-select .el-select__wrapper) {
        padding: 0;
        box-shadow: none;
    }
}
</style>
