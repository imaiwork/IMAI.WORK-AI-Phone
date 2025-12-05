<template>
    <div class="w-full h-full flex">
        <!-- 左侧表单区域 -->
        <div class="w-[350px] flex-shrink-0 border-r-[1px] border-[#0000000d] py-4 flex flex-col">
            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="px-4">
                        <ElForm
                            ref="formRef"
                            :model="formData"
                            :rules="rules"
                            label-position="top"
                            @submit.native.prevent>
                            <!-- 动态表单项 -->
                            <template v-for="(field, index) in getFormItem" :key="index">
                                <ElFormItem :label="field.name" :prop="field.fields">
                                    <!-- 输入框 -->
                                    <template v-if="field.type === FormFieldTypeEnum.INPUT">
                                        <ElInput
                                            v-model="formData[field.fields]"
                                            class="!h-11"
                                            clearable
                                            placeholder="请输入" />
                                    </template>
                                    <!-- 文本域 -->
                                    <template v-if="field.type === FormFieldTypeEnum.TEXTAREA">
                                        <ElInput
                                            v-model="formData[field.fields]"
                                            clearable
                                            placeholder="请输入"
                                            type="textarea"
                                            resize="none"
                                            :maxlength="1000"
                                            :rows="5" />
                                    </template>
                                    <!-- 数字输入框 -->
                                    <template v-if="field.type === FormFieldTypeEnum.NUMBER">
                                        <ElInput v-model="formData[field.fields]" type="number" placeholder="请输入" />
                                    </template>
                                    <!-- 文件上传 -->
                                    <template
                                        v-if="
                                            field.type === FormFieldTypeEnum.VIDEO ||
                                            field.type === FormFieldTypeEnum.IMAGE ||
                                            field.type === FormFieldTypeEnum.FILE
                                        ">
                                        <upload
                                            class="w-full"
                                            drag
                                            :type="field.type"
                                            list-type="text"
                                            :limit="1"
                                            :max-size="500"
                                            @remove="handleUploadRemove($event, field.fields)"
                                            @success="handleUploadSuccess($event, field.fields)">
                                            <div
                                                class="h-[111px] rounded-md flex flex-col items-center justify-center relative leading-5">
                                                <Icon name="local-icon-file_add" :size="36" color="#000000"></Icon>
                                                <span class="text-gray-500 text-xs mt-2"
                                                    >点击或将文件拖拽到这里上传</span
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
            <!-- 运行按钮 -->
            <div class="mt-3 flex justify-center">
                <ElButton color="#000000" class="!h-[50px] w-[276px] !rounded-xl" :loading="isLock" @click="lockFn">
                    立即运行
                </ElButton>
            </div>
        </div>
        <!-- 右侧结果展示区域 -->
        <div class="flex-1">
            <template v-if="!genLoading">
                <ElScrollbar v-if="result">
                    <div class="p-4">
                        <div class="flex flex-col gap-y-3">
                            <!-- 结果项 -->
                            <div v-for="(value, key) in result" :key="key" class="border border-[#0000000d] rounded-xl">
                                <div
                                    class="flex items-center justify-between px-[15px] h-[52px] border-b-[1px] border-[#0000000d]">
                                    <div class="flex items-center gap-x-3">
                                        <span>{{ getOutputParams[key]?.name || "-" }}</span>
                                        <span class="px-2 py-[2px] bg-[#F2F2F2] rounded">
                                            {{ getOutputParams[key]?.type }}
                                        </span>
                                    </div>
                                    <ElButton
                                        color="#000000"
                                        size="small"
                                        v-if="getOutputParams[key]?.type == FormFieldTypeEnum.FILE"
                                        @click="downloadFile(value, getOutputParams[key]?.name)">
                                        下载
                                    </ElButton>
                                    <ElButton v-else color="#000000" size="small" @click="copy(value)">复制</ElButton>
                                </div>
                                <div class="py-4 px-[14px] flex flex-col gap-2">
                                    <div v-for="(item, index) in formatValue(value)" :key="index">
                                        <template
                                            v-if="
                                                [FormFieldTypeEnum.VIDEO, FormFieldTypeEnum.IMAGE].includes(
                                                    getOutputParams[key]?.type
                                                )
                                            ">
                                            <video
                                                v-if="getOutputParams[key]?.type == FormFieldTypeEnum.VIDEO"
                                                :src="item"
                                                class="w-full max-h-[200px] rounded-[10px]"
                                                controls />
                                            <ElImage
                                                v-if="getOutputParams[key]?.type == FormFieldTypeEnum.IMAGE"
                                                :src="item"
                                                fit="cover"
                                                class="w-full rounded-[10px]"
                                                :preview-src-list="[item]"
                                                preview-teleported />
                                            <div class="flex justify-end mt-2" v-if="item">
                                                <ElButton
                                                    color="#000000"
                                                    size="small"
                                                    @click="downloadFile(item, getOutputParams[key]?.name || '')"
                                                    >下载</ElButton
                                                >
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span class="break-all">{{ item }}</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
                <div v-else class="h-full flex items-center justify-center">
                    <ElEmpty />
                </div>
            </template>
            <!-- 加载状态 -->
            <div class="h-full flex items-center justify-center" v-else>
                <loader />
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
:deep(.el-upload) {
    .el-upload-dragger {
        border-style: solid;
        padding: 0;
    }
}
:deep(.el-upload-list) {
    .el-upload-list__item {
        @apply h-11 flex items-center shadow-[0_0_0_1px_#EFEFEF];
    }
    .el-progress {
        @apply top-[34px] left-0;
    }
}
</style>
