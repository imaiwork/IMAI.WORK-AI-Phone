<template>
    <popup ref="popupRef" width="560px" async :confirm-loading="isLock" @close="close" @confirm="lockFn">
        <div class="space-y-6">
            <div class="flex items-center gap-3 px-1">
                <div
                    class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shadow-light border border-amber-100">
                    <Icon name="el-icon-AlarmClock" :size="20" />
                </div>
                <div>
                    <h4 class="text-[15px] font-black text-[#0F172A]">自动化跟进策略</h4>
                    <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">
                        Automated Follow-up Strategy
                    </p>
                </div>
            </div>

            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top" class="modern-form">
                <div class="bg-[#f8fafc]/50 border border-slate-100 rounded-[20px] p-5 space-y-5">
                    <ElFormItem label="判定触发逻辑" prop="status">
                        <ElSelect v-model="formData.status" class="custom-select w-full" :show-arrow="false">
                            <template #prefix>
                                <Icon name="el-icon-Filter" color="var(--primary)" />
                            </template>
                            <ElOption label="停留判定：客户在此阶段停留过久" :value="0"></ElOption>
                            <ElOption label="沉默判定：客户已有一段时间未互动" :value="1"></ElOption>
                        </ElSelect>
                    </ElFormItem>

                    <ElFormItem label="执行时间计划" prop="send_time">
                        <div class="flex flex-col gap-3">
                            <div
                                class="flex items-center gap-2 p-3 bg-white rounded-xl border border-slate-100 shadow-light">
                                <span class="text-[13px] font-medium text-slate-500 min-w-[60px]">
                                    {{ formData.status == 0 ? "停留超过" : "沉默超过" }}
                                </span>
                                <ElInputNumber
                                    v-model="formData.judgment"
                                    :min="0"
                                    controls-position="right"
                                    class="!w-[100px] modern-number-input" />
                                <span class="text-[13px] font-medium text-slate-500 px-2">天后</span>

                                <div class="w-[1px] h-4 bg-slate-200 mx-1"></div>

                                <span class="text-[13px] font-medium text-slate-500 px-2">
                                    {{ formData.judgment > 0 ? "次日" : "当天" }}
                                </span>
                                <ElTimePicker
                                    v-model="formData.send_time"
                                    placeholder="时刻"
                                    format="HH:mm"
                                    value-format="HH:mm:ss"
                                    :clearable="false"
                                    class="!w-[110px] modern-time-picker" />
                            </div>
                            <p class="text-[11px] text-slate-400 flex items-center gap-1 pl-1">
                                <Icon name="el-icon-InfoFilled" :size="12" />
                                提醒将在满足天数条件的 {{ formData.judgment > 0 ? "次日" : "当天" }}
                                {{ formData.send_time.substring(0, 5) }} 准时推送
                            </p>
                        </div>
                    </ElFormItem>
                </div>

                <div class="pt-2">
                    <ElFormItem label="自动推送提醒文案" prop="content">
                        <ElInput
                            v-model="formData.content"
                            type="textarea"
                            :rows="5"
                            placeholder="请输入针对该阶段客户的自动化跟进提醒话术..."
                            maxlength="500"
                            show-word-limit
                            class="custom-textarea" />
                    </ElFormItem>
                </div>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
// 逻辑保持不变，确保功能可用性
import { sopAddAutoFollow, sopUpdateAutoFollow } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import dayjs from "dayjs";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const mode = ref<"add" | "edit">("add");
const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    flow_id: "",
    stage_id: "",
    remind_id: "",
    status: 0,
    judgment: 0,
    send_time: dayjs().add(10, "minutes").format("HH:mm:ss"),
    content: "",
});

const rules = {
    send_time: [
        { required: true, message: "请选择提醒时间", trigger: "blur" },
        {
            validator: (rule: any, value: any, callback: any) => {
                const now = new Date();
                const valueDate = new Date(`${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()} ${value}`);
                if (formData.judgment == 0 && valueDate < now) {
                    callback(new Error("设为0天时，提醒时间需晚于当前"));
                } else {
                    callback();
                }
            },
        },
    ],
    content: [{ required: true, message: "请输入提醒内容", trigger: "blur" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();
const { lockFn, isLock } = useLockFn(async () => {
    const valid = await formRef.value?.validate().catch(() => false);
    if (!valid) return;
    try {
        formData.remind_id ? await sopUpdateAutoFollow(formData) : await sopAddAutoFollow(formData);
        popupRef.value?.close();
        emit("success");
        feedback.msgSuccess("规则配置已生效");
    } catch (error) {
        // feedback.msgError(error);
    }
});

const open = (type: "add" | "edit") => {
    mode.value = type;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss">
:deep(.modern-remind-popup) {
    .el-dialog__header {
        @apply p-6 pb-0;
    }
    .el-dialog__body {
        @apply p-6 pt-4;
    }
}
</style>
