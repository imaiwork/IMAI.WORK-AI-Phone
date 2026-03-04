<template>
    <popup
        ref="popupRef"
        width="460px"
        async
        :show-close="false"
        header-class="!p-0"
        footer-class="!p-0"
        cancel-button-text=""
        confirm-button-text="">
        <div class="relative">
            <div class="absolute right-2 top-2 w-8 h-8 z-20" @click="close">
                <close-btn />
            </div>
            <div class="mb-6 relative">
                <div class="text-[20px] font-[900] text-[#1E293B]">智能接管配置</div>
                <div class="text-xs font-medium text-[#94A3B8] mt-1">设置当前账号的接管模式，实现自动化运营</div>
            </div>

            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top" class="custom-config-form">
                <ElFormItem label="接管工作模式" prop="takeover_mode" class="!mb-8">
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div
                            v-for="item in takeoverModeList"
                            class="relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300"
                            :class="
                                formData.takeover_mode === item.value
                                    ? 'border-primary bg-[#0065fb]/10'
                                    : 'border-[#F1F5F9] hover:border-br'
                            "
                            @click="formData.takeover_mode = item.value">
                            <div
                                class="flex items-center gap-2 mb-1"
                                :class="formData.takeover_mode === item.value ? 'text-primary' : 'text-[#94A3B8]'">
                                <Icon :name="item.icon" />
                                <span
                                    class="text-[14px] font-[900]"
                                    :class="formData.takeover_mode === item.value ? 'text-primary' : 'text-[#475569]'"
                                    >{{ item.label }}</span
                                >
                            </div>
                            <div class="text-[11px] font-medium text-[#94A3B8]">{{ item.description }}</div>
                            <div
                                v-if="formData.takeover_mode === item.value"
                                class="absolute -top-2 -right-2 w-5 h-5 bg-primary rounded-full flex items-center justify-center border-2 border-white">
                                <Icon name="el-icon-Check" color="white" :size="10" />
                            </div>
                        </div>
                    </div>
                </ElFormItem>

                <Transition name="fade-slide">
                    <ElFormItem
                        label="指定接管机器人 (AI Agent)"
                        prop="robot_id"
                        v-if="formData.takeover_mode === 1"
                        class="!mb-6">
                        <div class="w-full">
                            <ElSelect
                                v-model="formData.robot_id"
                                filterable
                                clearable
                                remote
                                class="workflow-select w-full"
                                placeholder="搜索并关联您的 AI 机器人"
                                :loading="agentLoading"
                                :remote-method="getAgentFn">
                                <ElOption v-for="item in agentLists" :key="item.id" :label="item.name" :value="item.id">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-md bg-[#0065fb]/10 flex items-center justify-center text-primary text-[10px]">
                                            AI
                                        </div>
                                        <span>{{ item.name }}</span>
                                    </div>
                                </ElOption>
                            </ElSelect>
                            <div
                                class="mt-4 p-4 rounded-2xl bg-slate-50 border border-[#F1F5F9] flex items-center justify-between">
                                <div class="text-[11px] font-medium text-[#64748B]">没有合适的机器人？</div>
                                <router-link to="/agent" target="_blank">
                                    <button
                                        type="button"
                                        class="text-[11px] font-black text-primary hover:underline flex items-center gap-1">
                                        立即创建 <Icon name="el-icon-ArrowRight" />
                                    </button>
                                </router-link>
                            </div>
                        </div>
                    </ElFormItem>
                </Transition>
            </ElForm>

            <div class="mt-10 flex gap-3">
                <ElButton class="flex-1 !h-12 !rounded-xl !font-medium !text-[#64748B]" @click="close"> 取消 </ElButton>
                <ElButton
                    class="flex-1 !h-12 !rounded-xl !font-medium"
                    type="primary"
                    :disabled="isLock"
                    :loading="isLock"
                    @click="lockFn">
                    保存配置
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getAgentList } from "@/api/agent";
import { getAccountDetail, changeAccountStatus } from "@/api/service";
import { type FormInstance } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const popupRef = ref<any>(null);
const formRef = ref<FormInstance>();
const formData = reactive({
    account: "",
    takeover_mode: 0,
    robot_id: undefined,
    account_type: "",
});

const rules = {
    takeover_mode: [{ required: true, message: "请选择接管类型", trigger: "blur" }],
    robot_id: [{ required: true, message: "请选择接管机器人AI", trigger: "blur" }],
};

// 接管方式
const takeoverModeList = [
    {
        label: "人工接管",
        value: 0,
        description: "手动处理消息与任务",
        icon: "el-icon-User",
    },
    {
        label: "AI 自动化",
        value: 1,
        description: "由机器人全天候接管",
        icon: "el-icon-Cpu",
    },
];

const agentLists = ref<any[]>([]);
const agentLoading = ref(false);
const getAgentFn = async (query?: string) => {
    agentLoading.value = true;
    const data = await getAgentList({ keyword: query });
    agentLists.value = data.lists;
    agentLoading.value = false;
};

const open = () => {
    popupRef.value.open();
    getAgentFn();
};

const close = () => {
    emit("close");
};

const handleSubmit = async () => {
    await formRef.value.validate();
    try {
        await changeAccountStatus({
            ...formData,
            robot_id: formData.robot_id === 0 ? undefined : formData.robot_id,
        });
        emit("success");
        close();
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error || "提交失败");
    }
};

const { lockFn, isLock } = useLockFn(handleSubmit);

defineExpose({
    open,
    close,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss">
.save-btn-shadow {
    box-shadow: 0 8px 20px -6px rgba(var(--el-primary-color), 0.4);
}

:deep(.custom-config-form) {
    .el-form-item__label {
        @apply text-[13px] font-black text-[#475569] mb-3;
    }
}

/* 下拉框定制 */
:deep(.workflow-select) {
    .el-input__wrapper {
        @apply h-12 rounded-2xl bg-slate-50 shadow-[none] border border-br transition-all px-4;
        &.is-focus {
            @apply border-primary bg-white;
            box-shadow: 0 0 0 4px rgba(var(--el-primary-color), 0.08) !important;
        }
    }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
