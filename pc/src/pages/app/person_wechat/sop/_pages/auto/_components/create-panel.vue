<template>
    <div class="h-full flex flex-col gap-3 relative">
        <div
            class="flex items-center justify-between bg-white px-6 py-4 rounded-[20px] border border-slate-100 flex-shrink-0">
            <div>
                <ElBreadcrumb :separator-icon="ArrowRight" class="custom-breadcrumb">
                    <ElBreadcrumbItem>
                        <span class="nav-text hover:text-primary" @click="close">任务管理</span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem>
                        <span class="nav-text">
                            {{ detail ? detail.push_name : "新建 SOP 计划" }}
                        </span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem>
                        <span class="px-3 py-1 bg-[#0065fb]/10 text-primary rounded-full text-xs font-black">
                            {{ stepKey === StepKey.TYPE ? "配置触发方式" : "编排推送内容" }}
                        </span>
                    </ElBreadcrumbItem>
                </ElBreadcrumb>
            </div>

            <div class="flex items-center gap-3">
                <ElButton
                    class="!h-10 !px-6 !rounded-xl !border-slate-100 hover:!bg-slate-50 !text-slate-500 font-medium"
                    @click="cancel">
                    取消编辑
                </ElButton>
                <ElButton
                    v-if="stepKey != StepKey.TYPE"
                    class="!h-10 !rounded-xl !bg-white !border-primary !text-primary hover:!bg-primary/5 font-black"
                    @click="handleStepChange(StepKey.TYPE)">
                    返回上一步
                </ElButton>
            </div>
        </div>

        <div
            class="grow min-h-0 bg-white rounded-[20px] border border-slate-100 flex flex-col overflow-hidden relative"
            v-loading="loading">
            <div class="flex items-center justify-center py-6 bg-[#f8fafc]/50 border-b border-slate-50 gap-4">
                <div v-for="(step, i) in [StepKey.TYPE, StepKey.CONTENT]" :key="i" class="flex items-center">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black transition-all"
                            :class="
                                stepKey == step
                                    ? 'bg-primary text-white shadow-light shadow-[#0065fb]/30 scale-110'
                                    : 'bg-slate-200 text-slate-500'
                            ">
                            {{ i + 1 }}
                        </div>
                        <span
                            class="text-[14px] font-black"
                            :class="stepKey == step ? 'text-primary' : 'text-slate-400'">
                            {{ i == 0 ? "基本信息" : i == 1 ? "触发设置" : "内容编排" }}
                        </span>
                    </div>
                    <div
                        v-if="i < 1"
                        class="w-12 h-[2px] mx-4 rounded-full"
                        :class="stepKey == step ? 'bg-[#0065fb]/20' : 'bg-slate-100'"></div>
                </div>
            </div>

            <template v-if="!loading">
                <ElScrollbar>
                    <div class="p-8 max-w-5xl mx-auto w-full">
                        <div v-if="stepKey == StepKey.TYPE" class="animate-in fade-in duration-500">
                            <div class="mb-8 border-b border-slate-50 pb-6">
                                <h2 class="text-[22px] font-medium text-slate-800 tracking-tight">触发方式设置</h2>
                                <p class="text-slate-400 text-[14px] mt-1">
                                    请选择一种触发机制，当客户满足条件时将自动启动 SOP 推送流程
                                </p>
                            </div>

                            <div
                                class="bg-[#0065fb]/5 p-4 rounded-2xl flex items-center gap-4 mb-4 border border-[#0065fb]/10">
                                <div class="text-primary font-black text-[14px] shrink-0">任务名称</div>
                                <ElInput
                                    v-model="taskFormData.push_name"
                                    class="custom-input"
                                    placeholder="输入任务名称..."
                                    maxlength="50"
                                    show-word-limit
                                    clearable />
                                <div class="text-xs text-[#0065fb]/60 italic shrink-0">
                                    * 此名称仅用于任务列表管理区分
                                </div>
                            </div>
                            <div class="mt-4">
                                <send-way v-model="taskFormData" ref="sendWayRef" />
                            </div>
                        </div>

                        <div v-if="stepKey == StepKey.CONTENT" class="animate-in fade-in duration-500">
                            <send-container
                                ref="sendContainerRef"
                                :type="PushTypeEnum.AUTO_SOP"
                                @success="handleSendContainerSuccess" />
                        </div>
                    </div>
                </ElScrollbar>
                <div class="static bottom-2 left-0 right-0 w-full text-center my-4">
                    <ElButton
                        type="primary"
                        class="!h-11 !px-10 !rounded-xl hover:scale-105 active:scale-95 transition-all font-black"
                        @click="handleMainSave">
                        <Icon name="el-icon-CircleCheck" />
                        <span class="ml-2">
                            {{ stepKey == StepKey.CONTENT ? "完成并发布 SOP" : "保存并下一步" }}
                        </span>
                    </ElButton>
                </div>
            </template>
        </div>
    </div>
</template>
<script setup lang="ts">
import { sopPushDetail, sopPushContentTimeLists, sopPushAdd, sopPushUpdate } from "@/api/person_wechat";
import dayjs from "dayjs";
import { ArrowRight } from "@element-plus/icons-vue";
import SendWay from "../../../_components/send-way.vue";
import SendContainer from "../../../_components/send-container.vue";
import { PushTypeEnum, SendWayEnum } from "../../../_enums";

const emit = defineEmits<{ (e: "back"): void }>();

enum StepKey {
    TYPE = "type", // 设置类型/名称
    CONTENT = "content", // 设置内容
}

const nuxtApp = useNuxtApp();
const query = searchQueryToObject();

const loading = ref(false);
const stepKey = ref<StepKey>((query.step as StepKey) || StepKey.TYPE);
const detail = ref<any>(null);

// 表单数据初始状态抽取，方便重置
const createInitialFormData = () => ({
    id: "",
    push_name: `自动SOP任务${dayjs().format("YYYYMMDDHHmm")}`,
    content: [],
    people: [],
    flow_id: "",
    stage_id: "",
    type: SendWayEnum.SPECIFIED_PROCESS,
    status: 0,
    push_day: [],
    push_type: 1,
});

const taskFormData = reactive(createInitialFormData());

const sendWayRef = ref<InstanceType<typeof SendWay>>();
const sendContainerRef = ref<InstanceType<typeof SendContainer>>();

const updateUrlParams = (params: Record<string, any>) => {
    replaceState({ id: detail.value?.id, step: stepKey.value, ...params });
};

const syncFormDataFromDetail = async (data: any) => {
    if (!data) return;
    setFormData(data, taskFormData);
    if (stepKey.value === StepKey.CONTENT) {
        setTimeout(() => {
            sendContainerRef.value?.setFormData(data);
        }, 300);
    }
    if (data.type == -1 || !data.type) {
        taskFormData.type = SendWayEnum.SPECIFIED_PROCESS;
    }
};

const fetchDetail = async (id: string | number) => {
    if (!id) return;
    loading.value = true;
    try {
        const result = await sopPushDetail({ id });
        detail.value = result;
        syncFormDataFromDetail(result);
        if (stepKey.value === StepKey.CONTENT) {
            const timeLists = await sopPushContentTimeLists({ push_id: id });
            setTimeout(() => {
                sendContainerRef.value?.setDateList(timeLists);
            }, 150);
        }
    } finally {
        loading.value = false;
    }
};

const handleStepChange = async (targetKey: StepKey) => {
    stepKey.value = targetKey;
    updateUrlParams({ step: targetKey });

    if (targetKey === StepKey.CONTENT && detail.value) {
        await fetchDetail(detail.value.id);
    }
};

const handleMainSave = async () => {
    if (stepKey.value === StepKey.TYPE) {
        if (!taskFormData.push_name) return feedback.msgWarning("请输入任务名称");
        await sendWayRef.value?.validateForm();

        try {
            await sopPushUpdate(taskFormData);
            await handleStepChange(StepKey.CONTENT);
        } catch (error) {
            feedback.msgError(error || "保存失败");
        }
    } else {
        close();
    }
};

const handleSendContainerSuccess = async () => {
    if (!detail.value?.id) return;

    await sopPushUpdate({
        ...detail.value,
        status: 2,
    });
    await fetchDetail(detail.value.id);
    feedback.msgSuccess("保存成功");
};

const init = async () => {
    const { id } = query;
    if (id) {
        await fetchDetail(id as string);
    } else {
        // 新增模式：先预创建
        const result = await sopPushAdd(taskFormData);
        detail.value = result;
        syncFormDataFromDetail(result);
        updateUrlParams({ id: result.id });
        taskFormData.type = SendWayEnum.SPECIFIED_PROCESS;
    }
};

const close = () => emit("back");

const cancel = async () => {
    nuxtApp.$confirm({
        message: "确定取消吗？未保存的内容将丢失",
        onConfirm: close,
    });
};

onMounted(init);
</script>
<style scoped lang="scss">
.nav-text {
    @apply font-medium text-slate-400 cursor-pointer transition-colors;
    &.is-active {
        @apply text-slate-800;
    }
}

.animate-in {
    animation-fill-mode: forwards;
}

:deep(.el-breadcrumb__separator) {
    @apply font-normal text-slate-300;
}

.flex-shrink-0 {
    animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
