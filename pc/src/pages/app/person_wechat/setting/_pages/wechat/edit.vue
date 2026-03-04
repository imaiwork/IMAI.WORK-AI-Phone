<template>
    <popup
        ref="popupRef"
        async
        width="580px"
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close"
        custom-class="modern-config-popup">
        <div class="flex items-center gap-x-3 mb-8 px-2">
            <div
                class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary border border-primary/10 shadow-sm shadow-primary/5">
                <Icon name="el-icon-Setting" :size="24" />
            </div>
            <div>
                <h3 class="text-[18px] font-[900] text-[#0F172A] leading-tight">配置接管策略</h3>
                <p class="text-xs text-slate-400 font-medium mt-1 tracking-wide uppercase">AI Strategy Configuration</p>
            </div>
        </div>

        <ElForm :model="formData" ref="formRef" :rules="formRules" label-position="top" class="custom-modern-form">
            <div class="config-section mb-6">
                <div class="section-title">
                    <Icon name="el-icon-Operation" class="mr-2" />
                    核心状态控制
                </div>
                <div class="grid grid-cols-2 gap-6 bg-[#f8fafc]/50 p-5 rounded-[20px] border border-slate-100">
                    <ElFormItem label="AI 总功能开关" prop="open_ai" class="!mb-0">
                        <div class="flex items-center h-[40px] gap-3">
                            <ElSwitch
                                v-model="formData.open_ai"
                                :active-value="1"
                                :inactive-value="0"
                                inline-prompt
                                active-text="已开启"
                                inactive-text="已关闭"
                                class="custom-large-switch" />
                            <span class="text-[11px] text-slate-400 font-medium leading-tight"
                                >决定此账号是否允许 AI 介入聊天</span
                            >
                        </div>
                    </ElFormItem>
                    <ElFormItem label="显示排序" prop="sort" class="!mb-0">
                        <ElInputNumber
                            v-model="formData.sort"
                            :precision="0"
                            :min="0"
                            :max="100"
                            class="!w-full modern-number-input"
                            controls-position="right" />
                    </ElFormItem>
                </div>
            </div>

            <div class="config-section mb-6">
                <div class="section-title">
                    <Icon name="el-icon-Cpu" class="mr-2" />
                    智能接管配置
                </div>
                <div class="bg-[#f8fafc]/50 p-5 rounded-[20px] border border-slate-100 space-y-5">
                    <ElFormItem label="接管模式选择" prop="takeover_mode">
                        <ElRadioGroup v-model="formData.takeover_mode" class="modern-segmented-control">
                            <ElRadioButton :value="1">AI 自动接管</ElRadioButton>
                            <ElRadioButton :value="0">人工手动介入</ElRadioButton>
                        </ElRadioGroup>
                    </ElFormItem>

                    <Transition name="el-fade-in-linear">
                        <ElFormItem label="指定 AI 机器人" prop="robot_id" v-if="formData.takeover_mode === 1">
                            <ElSelect
                                v-model="formData.robot_id"
                                filterable
                                clearable
                                remote
                                placeholder="搜索并关联接管机器人"
                                :loading="agentLoading"
                                :remote-method="getAgentFn"
                                class="modern-select">
                                <template #prefix><Icon name="el-icon-Robot" class="text-primary" /></template>
                                <ElOption
                                    v-for="item in agentLists"
                                    :key="item.id"
                                    :label="item.name"
                                    :value="item.id" />
                            </ElSelect>
                        </ElFormItem>
                    </Transition>

                    <ElFormItem label="接管对话范围" prop="takeover_type">
                        <ElRadioGroup v-model="formData.takeover_type" class="modern-radio-group">
                            <ElRadio :value="0" border>全部场景</ElRadio>
                            <ElRadio :value="1" border>仅限私聊</ElRadio>
                        </ElRadioGroup>
                    </ElFormItem>
                </div>
            </div>

            <div class="config-section">
                <div class="section-title">
                    <Icon name="el-icon-Edit" class="mr-2" />
                    账号备注信息
                </div>
                <ElFormItem prop="remark">
                    <ElInput
                        v-model="formData.remark"
                        placeholder="请输入此账号的内部备注名称"
                        class="modern-input"
                        clearable>
                        <template #prefix><Icon name="el-icon-Notebook" class="text-slate-400" /></template>
                    </ElInput>
                </ElFormItem>
            </div>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { getWeChatAi, saveWeChatAi } from "@/api/person_wechat";
import { getAgentList } from "@/api/agent";
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
const emit = defineEmits(["close", "success"]);

const formRef = shallowRef<FormInstance>();
const formData = reactive<any>({
    wechat_id: "", //微信ID，微信提供的ID
    open_ai: 1, //AI总功能开关 0：关闭 1：开启
    remark: "wechat_ai", //备注
    takeover_mode: 1, //接管模式 0：人工接管 1：AI接管
    takeover_type: 1, //接管类型 0：全部 1：私聊 2：群聊
    robot_id: "", //AI接管机器人
    sort: 1, //排序
});

const formRules = {
    remark: [{ required: true, message: "请输入账号备注" }],
    open_ai: [{ required: true, message: "请选择AI接管" }],
    takeover_mode: [{ required: true, message: "请选择接管模式" }],
    takeover_type: [{ required: true, message: "请选择接管类型" }],
    robot_id: [{ required: true, message: "请选择AI接管机器人" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const agentLists = ref<any[]>([]);
const agentLoading = ref(false);
const getAgentFn = async (query?: string) => {
    agentLoading.value = true;
    const data = await getAgentList({ keyword: query });
    agentLists.value = data.lists;
    agentLoading.value = false;
};

const open = () => {
    popupRef.value?.open();
    getAgentFn();
};

const close = () => {
    emit("close");
};

const handleConfirm = async () => {
    await formRef.value?.validate();
    try {
        await saveWeChatAi(formData);
        popupRef.value?.close();
        feedback.msgSuccess("保存成功");
        emit("success");
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const getDetail = async (wechat_id: string) => {
    const data = await getWeChatAi({ wechat_id });
    setFormData(data, formData);
};

defineExpose({
    open,
    getDetail,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>
<style scoped lang="scss">
.section-title {
    @apply flex items-center text-[13px] font-[900] text-slate-500 mb-3 pl-1 uppercase tracking-wider;
}

.modern-segmented-control {
    @apply bg-white p-1 rounded-xl border border-slate-100 w-full flex;
    :deep(.el-radio-button) {
        @apply flex-1;
        .el-radio-button__inner {
            @apply w-full bg-[transparent] border-[none] rounded-lg text-[13px] font-medium text-slate-500 h-9 flex items-center justify-center transition-all;
        }
        &.is-active .el-radio-button__inner {
            @apply bg-primary text-white shadow-light shadow-[#0065fb]/20;
        }
    }
}

.modern-radio-group {
    @apply flex gap-4;
    :deep(.el-radio) {
        @apply mr-0 rounded-xl border-slate-100 bg-white h-11 px-6 transition-all;
        &.is-bordered.is-checked {
            @apply border-primary bg-[#0065fb]/5;
        }
        .el-radio__label {
            @apply font-medium text-[13px];
        }
    }
}

.custom-large-switch {
    --el-switch-on-color: #0065fb;
    height: 32px;
    :deep(.el-switch__core) {
        @apply border-none rounded-lg h-8;
        .el-switch__inner {
            @apply text-[11px] font-black;
        }
    }
}

.custom-modern-form :deep(.el-form-item__label) {
    @apply font-black text-[13px] text-[#1E293B] pb-1;
}

:deep(.el-input-number.modern-number-input) {
    .el-input-number__increase,
    .el-input-number__decrease {
        @apply bg-slate-50 border-slate-100 rounded-lg;
    }
}
</style>
