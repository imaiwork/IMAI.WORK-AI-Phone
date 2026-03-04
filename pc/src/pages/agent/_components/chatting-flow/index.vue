<template>
    <div class="w-full h-full flex bg-slate-50">
        <div class="w-[380px] flex-shrink-0 border-r border-[#F1F5F9] bg-white py-6 flex flex-col">
            <div class="px-6 mb-6">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                    <span class="text-[15px] font-[900] text-[#1E293B]">参数配置</span>
                </div>
                <p class="text-xs font-medium text-[#94A3B8]">请填写下方工作流所需的输入参数</p>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="px-6">
                        <ElForm
                            ref="formRef"
                            :model="formData"
                            :rules="rules"
                            label-position="top"
                            class="custom-workflow-form"
                            @submit.native.prevent>
                            <template v-for="(field, index) in getFormItem" :key="index">
                                <ElFormItem :label="field.name" :prop="field.fields" class="!mb-6">
                                    <template v-if="field.type === FormFieldTypeEnum.INPUT">
                                        <ElInput
                                            v-model="formData[field.fields]"
                                            class="workflow-input"
                                            clearable
                                            placeholder="请输入内容..." />
                                    </template>

                                    <template v-if="field.type === FormFieldTypeEnum.TEXTAREA">
                                        <ElInput
                                            v-model="formData[field.fields]"
                                            class="workflow-textarea"
                                            clearable
                                            placeholder="请输入详细描述..."
                                            type="textarea"
                                            resize="none"
                                            :maxlength="1000"
                                            :rows="4" />
                                    </template>

                                    <template
                                        v-if="
                                            [
                                                FormFieldTypeEnum.VIDEO,
                                                FormFieldTypeEnum.IMAGE,
                                                FormFieldTypeEnum.FILE,
                                            ].includes(field.type)
                                        ">
                                        <upload
                                            class="w-full group"
                                            drag
                                            :type="field.type"
                                            list-type="text"
                                            :limit="1"
                                            :max-size="500"
                                            @remove="handleUploadRemove($event, field.fields)"
                                            @success="handleUploadSuccess($event, field.fields)">
                                            <div
                                                class="h-[120px] rounded-2xl border-2 border-dashed border-br group-hover:border-primary group-hover:bg-[#0065fb]/10 transition-all flex flex-col items-center justify-center relative bg-slate-50">
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-white flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                    <Icon
                                                        name="local-icon-file_add"
                                                        :size="24"
                                                        class="text-[#475569] group-hover:text-primary" />
                                                </div>
                                                <span
                                                    class="text-[#94A3B8] text-xs font-medium group-hover:text-primary"
                                                    >点击或拖拽上传文件</span
                                                >
                                            </div>
                                        </upload>
                                    </template>
                                </ElFormItem>
                            </template>
                        </ElForm>
                    </div>
                </ElScrollbar>
            </div>

            <div class="px-6 mt-4">
                <button
                    class="w-full h-14 rounded-2xl bg-[#0065FB] text-white flex items-center justify-center gap-3 font-black text-[15px] transition-all active:scale-[0.98] disabled:opacity-50 run-btn-shadow"
                    :disabled="isLock"
                    @click="lockFn">
                    <Icon v-if="!isLock" name="el-icon-VideoPlay" :size="20" />
                    <span v-if="isLock" class="animate-spin"><Icon name="el-icon-Loading" /></span>
                    {{ isLock ? "正在处理中..." : "立即运行工作流" }}
                </button>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="h-[72px] shrink-0 px-8 flex items-center justify-between bg-white border-b border-[#F1F5F9]">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center text-primary">
                        <Icon name="el-icon-Cpu" :size="18" />
                    </div>
                    <span class="text-[16px] font-[900] text-[#1E293B]">输出结果</span>
                </div>
                <div v-if="result" class="flex items-center gap-2">
                    <span class="text-xs font-medium text-[#94A3B8]">处理完成</span>
                </div>
            </div>

            <div class="grow relative">
                <template v-if="!genLoading">
                    <ElScrollbar v-if="result">
                        <div class="p-8 max-w-[900px] mx-auto">
                            <div class="grid grid-cols-1 gap-6">
                                <div
                                    v-for="(value, key) in result"
                                    :key="key"
                                    class="bg-white rounded-[24px] border border-br overflow-hidden transition-all hover:border-primary/30">
                                    <div
                                        class="flex items-center justify-between px-6 py-4 bg-[#f8fafc]/50 border-b border-[#F1F5F9]">
                                        <div class="flex items-center gap-3">
                                            <span class="text-[14px] font-[900] text-[#1E293B]">{{
                                                getOutputParams[key]?.name || key
                                            }}</span>
                                            <span
                                                class="px-2 py-0.5 bg-white border border-br rounded-md text-[10px] font-black text-[#64748B] uppercase tracking-wider">
                                                {{ getOutputParams[key]?.type }}
                                            </span>
                                        </div>
                                        <button
                                            v-if="getOutputParams[key]?.type === FormFieldTypeEnum.FILE"
                                            class="text-xs font-black text-primary hover:underline"
                                            @click="downloadFile(value, getOutputParams[key]?.name)">
                                            下载文件
                                        </button>
                                        <button
                                            v-else
                                            class="text-xs font-black text-[#64748B] hover:text-primary transition-colors flex items-center gap-1"
                                            @click="copy(value)">
                                            <Icon name="el-icon-DocumentCopy" /> 复制
                                        </button>
                                    </div>

                                    <div class="p-6">
                                        <div v-for="(item, index) in formatValue(value)" :key="index">
                                            <div
                                                v-if="getOutputParams[key]?.type == FormFieldTypeEnum.VIDEO"
                                                class="group relative">
                                                <video
                                                    :src="item"
                                                    class="w-full rounded-2xl bg-black max-h-[400px]"
                                                    controls />
                                                <div class="mt-4 flex justify-end">
                                                    <ElButton
                                                        type="primary"
                                                        link
                                                        class="!font-black"
                                                        @click="downloadFile(item, 'video')"
                                                        >下载视频</ElButton
                                                    >
                                                </div>
                                            </div>

                                            <div
                                                v-else-if="getOutputParams[key]?.type == FormFieldTypeEnum.IMAGE"
                                                class="flex flex-col items-center">
                                                <div class="relative group w-full">
                                                    <ElImage
                                                        :src="item"
                                                        fit="contain"
                                                        class="w-full rounded-2xl border border-[#F1F5F9] transition-transform duration-500 group-hover:scale-[1.01]"
                                                        :preview-src-list="[item]"
                                                        preview-teleported />
                                                </div>
                                                <div class="mt-4 w-full flex justify-end">
                                                    <ElButton
                                                        type="primary"
                                                        link
                                                        class="!font-black"
                                                        @click="downloadFile(item, 'image')"
                                                        >保存图片</ElButton
                                                    >
                                                </div>
                                            </div>

                                            <template v-else>
                                                <div
                                                    class="text-[14px] text-[#475569] leading-[1.8] font-medium bg-slate-50 p-4 rounded-xl border border-[#F1F5F9] whitespace-pre-wrap break-all">
                                                    {{ item }}
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>

                    <div v-else class="h-full flex flex-col items-center justify-center opacity-40">
                        <div class="w-24 h-24 mb-4 bg-[#F1F5F9] rounded-full flex items-center justify-center">
                            <Icon name="el-icon-Compass" :size="48" class="text-[#94A3B8]" />
                        </div>
                        <p class="text-[14px] font-medium text-[#94A3B8]">运行工作流以查看输出结果</p>
                    </div>
                </template>

                <div v-else class="h-full flex flex-col items-center justify-center">
                    <loader />
                    <p class="mt-4 text-[14px] font-medium text-primary animate-pulse">正在处理数据，请稍候...</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { cozeWorkflowGenerate } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import { FormFieldTypeEnum } from "../../_enums";

// 定义组件props
const props = withDefaults(
    defineProps<{
        detail: Record<string, any>; // 工作流详情
        result: Record<string, any>; // 对话结果
    }>(),
    {
        detail: () => ({}),
        result: () => ({}),
    }
);

const emit = defineEmits<{
    (e: "success", data: any): void;
}>();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const { copy } = useCopy();

// 表单数据、引用和规则
const formData = ref<Record<string, any>>({});
const formRef = shallowRef();
const rules = ref<Record<string, any>>({});

// 从详情生成表单项
const getFormItem = computed(() => {
    if (!props.detail) return [];
    let { inputs } = props.detail;
    inputs = isJson(inputs) ? JSON.parse(inputs) : [];
    inputs.forEach((item: any) => {
        formData.value[item.fields] = item.value;
        if (item.required === "true") {
            const isFile = item.type === FormFieldTypeEnum.FILE;
            rules.value[item.fields] = [
                {
                    required: true,
                    message: isFile ? "请上传文件" : "请输入",
                    trigger: isFile ? "change" : "blur",
                },
            ];
        }
    });
    return inputs;
});

// 获取输出参数配置
const getOutputParams = computed(() => {
    if (!props.detail) return {};
    const outputs = isJson(props.detail.outputs) ? JSON.parse(props.detail.outputs) : [];
    const outputObj: Record<string, any> = {};
    outputs.forEach((item: any) => {
        outputObj[item.fields] = { ...item };
    });
    return outputObj;
});

const formatValue = (value: any) => {
    if (!value) return [];
    return isArray(value) ? value : [value];
};

/**
 * @description 文件上传成功回调
 * @param res - 上传接口返回的数据
 * @param fields - 表单字段名
 */
const handleUploadSuccess = (res: any, fields: string) => {
    formData.value[fields] = res.data.uri;
};

/**
 * @description 文件移除回调
 * @param result - 移除结果
 * @param fields - 表单字段名
 */
const handleUploadRemove = (result: any, fields: string) => {
    formData.value[fields] = "";
};

/**
 * @description 处理结果点击事件，如果是链接则打开，否则复制
 * @param value - 结果值
 */
const handleResult = (value: string) => {
    if (isLinkHttp(value)) {
        window.open(value);
    } else {
        copy(value);
    }
};

// 运行结果和加载状态
const result = ref<any>(null);
const genLoading = ref(false);

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(async () => {
    if (userTokens.value <= 1) {
        feedback.msgPowerInsufficient();
        return;
    }
    await formRef.value?.validate();
    try {
        genLoading.value = true;
        const data = await cozeWorkflowGenerate({
            id: props.detail.id,
            ...formData.value,
        });
        result.value = data;
        emit("success", data);
    } catch (error) {
        feedback.msgError(error as string);
    } finally {
        genLoading.value = false;
    }
});

watch(
    () => props.result,
    (newVal) => {
        if (newVal) {
            const { content } = newVal;
            result.value = isJson(content) ? JSON.parse(content) : content;
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped lang="scss">
.run-btn-shadow {
    box-shadow: 0 10px 25px -5px rgba(0, 101, 251, 0.4);
}

:deep(.workflow-input),
:deep(.workflow-textarea) {
    .el-input__wrapper,
    .el-textarea__inner {
        @apply rounded-xl bg-slate-50 shadow-[none] border border-br px-4 transition-all duration-300;
        &:hover {
            @apply border-[#CBD5E1];
        }
        &.is-focus,
        &:focus {
            @apply border-primary !bg-white;
            box-shadow: 0 0 0 4px rgba(0, 101, 251, 0.08) !important;
        }
    }
}

:deep(.el-form-item__label) {
    @apply text-[13px] font-black text-[#475569] mb-2;
}

:deep(.el-upload-list) {
    .el-upload-list__item {
        @apply rounded-xl border-[#EFEFEF] h-12 bg-white mb-2;
    }
}
:deep(.el-upload-dragger) {
    @apply p-0 border-none;
}
</style>
