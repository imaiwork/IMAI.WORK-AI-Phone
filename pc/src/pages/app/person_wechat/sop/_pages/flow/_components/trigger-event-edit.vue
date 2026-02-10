<template>
    <popup ref="popupRef" width="560px" async :confirm-loading="isLock" @close="close" @confirm="lockFn">
        <div class="space-y-6">
            <div class="flex items-center gap-3 px-1">
                <div
                    class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center border border-[#0065fb]/10 shadow-light shadow-[#0065fb]/5">
                    <Icon name="el-icon-Pointer" :size="20" />
                </div>
                <div>
                    <h4 class="text-[15px] font-[900] text-[#0F172A]">触发条件配置</h4>
                    <p class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">
                        Trigger Logic Configuration
                    </p>
                </div>
            </div>

            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top" class="modern-form">
                <ElFormItem label="条件类型" prop="match_type">
                    <ElRadioGroup
                        v-model="formData.match_type"
                        class="modern-segmented-control"
                        @change="handleMatchTypeChange">
                        <ElRadioButton :value="1">
                            <div class="flex items-center gap-2"><Icon name="el-icon-User" /> 关键动作触发</div>
                        </ElRadioButton>
                        <ElRadioButton :value="2">
                            <div class="flex items-center gap-2">
                                <Icon name="el-icon-ChatDotSquare" /> 聊天内容触发
                            </div>
                        </ElRadioButton>
                    </ElRadioGroup>
                </ElFormItem>

                <Transition name="el-fade-in-linear" mode="out-in">
                    <div v-if="formData.match_type == 1" class="config-panel is-action">
                        <ElFormItem label="选择触发动作" prop="action_type">
                            <div class="grid grid-cols-1 gap-3">
                                <div
                                    class="action-selectable-card"
                                    :class="{ 'is-active': formData.action_type === 1 }"
                                    @click="formData.action_type = 1">
                                    <div
                                        class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                        <Icon name="el-icon-UserFilled" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[14px] font-[900]">刚成为好友</div>
                                        <div class="text-[11px] text-slate-400 font-medium">
                                            当客户通过好友申请后，自动进入此阶段
                                        </div>
                                    </div>
                                    <div class="check-icon">
                                        <Icon name="el-icon-Check" />
                                    </div>
                                </div>

                                <div class="action-selectable-card is-disabled">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center">
                                        <Icon name="el-icon-Star" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-[14px] font-[900] text-slate-400">设置星标客户 (暂未开放)</div>
                                    </div>
                                </div>
                            </div>
                        </ElFormItem>
                    </div>

                    <div v-else class="config-panel is-content space-y-5">
                        <div class="grid grid-cols-2 gap-5">
                            <ElFormItem label="匹配模式" prop="chat_match_mode">
                                <ElRadioGroup v-model="formData.chat_match_mode" class="modern-radio-group">
                                    <ElRadio :value="1" border>模糊匹配</ElRadio>
                                    <ElRadio :value="2" border>精确匹配</ElRadio>
                                </ElRadioGroup>
                            </ElFormItem>
                            <ElFormItem label="检测对象" prop="chat_match_object">
                                <ElRadioGroup v-model="formData.chat_match_object" class="modern-radio-group">
                                    <ElRadio :value="1" border>AI 回复</ElRadio>
                                    <ElRadio :value="2" border>客户回复</ElRadio>
                                </ElRadioGroup>
                            </ElFormItem>
                        </div>

                        <ElFormItem label="关键词配置" prop="chat_keywords">
                            <ElInput
                                v-model="formData.chat_keywords"
                                placeholder="输入触发词，多个词请用逗号隔开"
                                class="custom-input">
                                <template #prefix>
                                    <Icon name="el-icon-Search" color="var(--slate-400)" />
                                </template>
                            </ElInput>
                            <p class="mt-2 text-[11px] text-slate-400 font-medium pl-1 italic">
                                * 提示：当
                                {{ formData.chat_match_object === 1 ? "AI" : "客户" }}
                                发送的消息中包含上述关键词时触发。
                            </p>
                        </ElFormItem>
                    </div>
                </Transition>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { sopAddTagTrigger, sopUpdateTagTrigger } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const mode = ref<"add" | "edit">("add");
const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    flow_id: "",
    stage_id: "",
    trigger_id: "",
    match_type: 1,
    action_type: 1,
    chat_match_mode: 1,
    chat_match_object: 1,
    chat_keywords: "",
});

const rules = {
    chat_keywords: [{ required: true, message: "请输入匹配关键词", trigger: "blur" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const handleMatchTypeChange = () => {
    if (formData.match_type == 1) {
        formData.action_type = 1;
    } else {
        formData.chat_match_mode = 1;
        formData.chat_match_object = 1;
        formData.chat_keywords = "";
    }
};

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    let params: any = {
        flow_id: formData.flow_id,
        stage_id: formData.stage_id,
        trigger_id: formData.trigger_id,
        match_type: formData.match_type,
        action_type: formData.match_type == 1 ? formData.action_type : "",
        chat_match_mode: formData.match_type == 2 ? formData.chat_match_mode : "",
        chat_match_object: formData.match_type == 2 ? formData.chat_match_object : "",
        chat_keywords: formData.match_type == 2 ? formData.chat_keywords : "",
    };
    try {
        formData.trigger_id ? await sopUpdateTagTrigger(params) : await sopAddTagTrigger(params);
        popupRef.value?.close();
        emit("success");
        feedback.msgSuccess("触发规则已成功配置");
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
.modern-segmented-control {
    border: none;
    @apply bg-slate-100 p-1 rounded-xl w-full flex gap-x-3;
    :deep(.el-radio-button) {
        @apply flex-1;
        .el-radio-button__inner {
            @apply w-full bg-[transparent] border-none rounded-lg text-[13px] font-medium text-slate-500 h-10 flex items-center justify-center transition-all;
        }
        &.is-active .el-radio-button__inner {
            @apply bg-white text-primary shadow-light shadow-[#0065fb]/5;
        }
    }
}

.config-panel {
    @apply bg-[#f8fafc]/50 border border-slate-100 rounded-[20px] p-5;
}

.action-selectable-card {
    @apply flex items-center gap-4 p-4 bg-white border border-slate-100 rounded-xl cursor-pointer transition-all relative;
    &:hover:not(.is-disabled) {
        @apply border-primary shadow-light;
    }
    &.is-active {
        @apply border-primary ring-4 ring-[#0065fb]/5;
    }

    .check-icon {
        @apply w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center scale-0 transition-transform;
    }
    &.is-active .check-icon {
        @apply scale-100;
    }

    &.is-disabled {
        @apply opacity-60 cursor-not-allowed bg-slate-50 border-dashed;
    }
}
</style>
