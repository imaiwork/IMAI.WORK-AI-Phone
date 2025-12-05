<template>
    <div class="h-full bg-white rounded-[20px] overflow-x-auto dynamic-scroller">
        <div class="h-full flex flex-col min-w-[1000px]">
            <!-- 头部导航 -->
            <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#0000000d]">
                <div class="flex items-center gap-2 cursor-pointer" @click="handleBack">
                    <Icon name="el-icon-ArrowLeft"></Icon>
                    <div>返回上一步</div>
                </div>
                <div class="flex items-center gap-1">
                    <ElButton class="!rounded-full !h-10 w-[98px]" :disabled="isSubmitting" @click="handleCancel"
                        >取消</ElButton
                    >
                    <ElButton
                        v-if="step != 3"
                        type="primary"
                        class="!rounded-full !h-10 w-[98px]"
                        :loading="isSubmitting"
                        @click="handleNext">
                        {{ isLastStep ? "提交" : "下一步" }}
                    </ElButton>
                </div>
            </div>
            <!-- 步骤条 -->
            <div class="px-5">
                <div
                    class="flex-shrink-0 flex items-center justify-center h-[100px] border-b border-[#0000000d] gap-x-[150px]">
                    <div v-for="(item, index) in steps" :key="index" class="relative">
                        <div class="flex flex-col items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-full flex items-center justify-center"
                                :class="[step >= item.step ? 'bg-primary text-white' : 'bg-[#0000000d]']">
                                {{ index + 1 }}
                            </div>
                            <div>
                                {{ item.title }}
                            </div>
                            <div
                                class="absolute w-[120px] h-[1px] left-[calc(100%+15px)] top-[10px]"
                                :class="[step > item.step ? 'bg-primary' : 'bg-[#0000000d]']"
                                v-if="index != steps.length - 1"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 内容区域 -->
            <div class="grow min-h-0 flex flex-col p-5">
                <ElScrollbar>
                    <div class="w-[556px] mx-auto">
                        <!-- 步骤一：上传文件 -->
                        <template v-if="step == 1">
                            <div class="text-[#00000080]">上传文件</div>
                            <div class="mt-3">
                                <upload
                                    class="w-full"
                                    ref="uploadRef"
                                    drag
                                    multiple
                                    type="file"
                                    list-type="text"
                                    show-file-list
                                    :action="uploadAction"
                                    :data="{
                                        indexid: indexId,
                                    }"
                                    :max-size="uploadFileSize"
                                    :limit="uploadFileLimit"
                                    :accept="uploadFileTypes"
                                    @remove="handleUploadRemove"
                                    @success="handleUploadSuccess">
                                    <div class="flex flex-col items-center justify-center">
                                        <Icon name="local-icon-file_add" :size="28"></Icon>
                                        <div class="text-xs mt-3">点击或将文件拖拽到这里上传</div>
                                        <div class="text-xs text-[#0000004d] mt-3 w-[80%] leading-6 break-all">
                                            支持{{ uploadFileTypes }}格式，文件最大限制{{
                                                uploadFileSize
                                            }}MB,最多支持（{{ formData.fileLists.length }}/ {{ uploadMaxLimit }}）个
                                        </div>
                                    </div>
                                </upload>
                            </div>
                        </template>
                        <!-- 步骤二：分段设置 -->
                        <template v-if="step == 2">
                            <div class="text-[#00000080]">分段设置</div>
                            <div class="mt-3 rounded-xl border border-[#efefef] p-[14px]">
                                <div class="flex items-center gap-x-3">
                                    <div
                                        class="rounded-md border border-[rgba(0,0,0,0.1)] w-10 h-10 flex items-center justify-center">
                                        <Icon name="local-icon-fodder" :size="24"></Icon>
                                    </div>
                                    <div>
                                        <div class="font-bold">通用</div>
                                        <div class="text-[11px] text-[#00000080] mt-1">
                                            通用文本分块模式，检索和搜索的块是相同的
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-3 gap-3">
                                    <div class="">
                                        <div class="text-[#00000080]">分段标识符</div>
                                        <div class="mt-3">
                                            <ElSelect
                                                class="!h-11"
                                                v-model="formData.segmentationIdentifier"
                                                multiple
                                                clearable
                                                collapse-tags
                                                collapse-tags-tooltip
                                                placeholder="请选择分段标识符">
                                                <ElOption
                                                    v-for="item in punctuationOptions"
                                                    :key="item.value"
                                                    :label="`${item.label}：${item.value}`"
                                                    :value="item.value">
                                                    <span>{{ item.label }}：</span>
                                                    <span class="font-bold">{{ item.value }}</span>
                                                </ElOption>
                                            </ElSelect>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="text-[#00000080]">分段最大长度</div>
                                        <div class="mt-3">
                                            <ElInput
                                                class="!h-11"
                                                v-model="formData.segmentationMaxLength"
                                                v-number-input="{
                                                    decimalPlaces: 0,
                                                    min: 1,
                                                    max: 1024,
                                                }"
                                                type="number">
                                            </ElInput>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="text-[#00000080]">分段重叠长度</div>
                                        <div class="mt-3">
                                            <ElInput
                                                class="!h-11"
                                                v-model="formData.segmentationOverlapLength"
                                                v-number-input="{
                                                    decimalPlaces: 0,
                                                    min: 1,
                                                    max: 2048,
                                                }"
                                                type="number">
                                            </ElInput>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3" v-if="isRag">
                                    <div class="text-[#00000080] flex items-center gap-x-2">
                                        相似度阈值
                                        <ElTooltip popper-class="w-[200px]">
                                            <div class="leading-[0]">
                                                <Icon name="el-icon-QuestionFilled" color="#858585" />
                                            </div>
                                            <template #content>
                                                <div>
                                                    设定最低分数标准，只有达到或超过这个阈值的检索结果才会被考虑用于后续的排序和生成过程
                                                </div>
                                            </template>
                                        </ElTooltip>
                                    </div>
                                    <div class="mt-3">
                                        <ElSlider
                                            v-model="formData.similarityThreshold"
                                            :min="0"
                                            :max="1"
                                            :step="0.01"
                                            size="small"
                                            show-input />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!-- 步骤三：处理并完成 -->
                        <template v-if="step == 3">
                            <div class="text-[#00000080]">处理完成</div>
                            <div class="mt-3 rounded-xl border border-[#efefef] p-[14px]">
                                <div class="flex items-center gap-x-3">
                                    <div
                                        class="rounded-md border border-[rgba(0,0,0,0.1)] w-10 h-10 flex items-center justify-center">
                                        <Icon name="local-icon-upload" :size="24"></Icon>
                                    </div>
                                    <div>
                                        <div class="font-bold">文档已上传</div>
                                        <div class="text-[11px] text-[#00000080] mt-1">
                                            文档已上传至知识库：【{{ knName }}】，你可以在知识库的文档列表中找到它。
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="h-11 rounded-md border border-[#efefef] mt-6 bg-[#f6f6f6] flex items-center gap-x-3 px-3"
                                    v-for="item in formData.fileLists">
                                    <div class="w-5 h-5 rounded flex items-center justify-center bg-[#0000000d]">
                                        <Icon name="local-icon-upload2"></Icon>
                                    </div>
                                    <div class="flex-1">{{ item.name }}</div>
                                    <Icon name="local-icon-success_fill" color="var(--color-primary)"></Icon>
                                </div>
                                <div class="text-[11px] leading-6 mt-6 text-[#00000080]" v-if="isRag">
                                    <p>
                                        分段模式：
                                        <ElTag
                                            class="mx-1"
                                            v-for="item in formData.segmentationIdentifier"
                                            :key="item"
                                            type="success"
                                            size="small">
                                            {{ item }}
                                        </ElTag>
                                    </p>
                                    <p>最大分段长度：{{ formData.segmentationMaxLength }}</p>
                                    <p>分段重叠长度：{{ formData.segmentationOverlapLength }}</p>
                                    <p>相似度阈值：{{ formData.similarityThreshold }}</p>
                                </div>
                            </div>
                            <div class="flex justify-center mt-10">
                                <ElButton
                                    type="primary"
                                    class="!rounded-full !h-[50px] w-[318px]"
                                    @click="emit('back')">
                                    返回文档
                                </ElButton>
                            </div>
                        </template>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { knowledgeBaseFileAdd, vectorKnowledgeBaseFileAdd } from "@/api/knowledge_base";
import { punctuationOptions } from "@/config/common";
import { KnTypeEnum } from "@/pages/knowledge_base/_enums";

const props = withDefaults(
    defineProps<{
        knName: string | string[];
        knId: string | string[];
        indexId?: string | string[];
        categoryId?: string | string[];
    }>(),
    {
        knName: "",
        knId: "",
        indexId: "",
        categoryId: "",
    }
);

const emit = defineEmits<{
    (e: "back"): void;
}>();

const route = useRoute();
const nuxtApp = useNuxtApp();

const query = searchQueryToObject();

const isRag = computed(() => {
    return route.query.kn_type == KnTypeEnum.RAG;
});

const uploadAction = computed(() => {
    return isRag.value ? `${getApiUrl()}${getApiPrefix()}/knowledge/fileUpload` : ``;
});

const formData = reactive({
    segmentationIdentifier: [],
    segmentationMaxLength: 1024,
    segmentationOverlapLength: 100,
    similarityThreshold: 0.5,
    fileLists: [],
});

const steps = [
    { step: 1, title: "上传文件" },
    { step: 2, title: "文本分段与清洗" },
    { step: 3, title: "处理并完成" },
];
const step = ref(Number(query.step) || 1);

const isLastStep = computed(() => step.value === steps.length);

const handleBack = async () => {
    if (step.value === 1 || step.value === 3) {
        closePanel();
    } else {
        step.value--;
        if (step.value === 1) {
            await nextTick();
            uploadRef.value.setFileList(formData.fileLists);
        }
        replaceState({ ...route.query, step: step.value });
    }
};

const handleCancel = () => {
    nuxtApp.$confirm({
        message: "确定要取消吗？",
        onConfirm: closePanel,
    });
};

const handleNext = async () => {
    let success = false;
    if (step.value === 1) {
        success = await submitStep1();
    } else if (step.value === 2) {
        success = await submitStep2();
    } else if (isLastStep.value) {
        return; // 提交后直接返回，不进入下一步
    }

    if (success) {
        step.value++;
    }
};

const closePanel = () => {
    emit("back");
    step.value = 1;
};

// =================================================================================================
// Step 1: 上传文件
// =================================================================================================
const uploadRef = ref<any>(null);
const uploadFileSize = 20;
const uploadMaxLimit = 10;
const uploadFileLimit = computed(() => uploadMaxLimit - formData.fileLists.length);
const uploadFileTypes = ".pdf,.docx,.doc,.txt,.md,.pptx,.ppt,.xlsx,.xls,.png,.jpg,.jpeg,.bmp,.gif";

const handleUploadSuccess = (result: any) => {
    formData.fileLists.push(result.data);
};

const handleUploadRemove = (result: any) => {
    if (result.response) {
        const { data } = result.response;
        const index = formData.fileLists.findIndex((item) => item.id === data.id);
        if (index !== -1) {
            formData.fileLists.splice(index, 1);
        }
    }
};

const submitStep1 = async () => {
    try {
        if (formData.fileLists.length === 0) {
            feedback.msgWarning("请上传文件");
            return false;
        }
        return true;
    } catch (error) {
        feedback.msgError(error);
    }
    return true;
};

// =================================================================================================
// Step 2: 文本分段与清洗
// =================================================================================================

const submitStep2 = async () => {
    if (formData.segmentationIdentifier.length === 0) {
        feedback.msgWarning("请选择分段标识符");
        return false;
    }
    await submitForm();
    return true;
};

const { lockFn: submitForm, isLock: isSubmitting } = useLockFn(async () => {
    return new Promise(async (resolve, reject) => {
        try {
            if (isRag.value) {
                await knowledgeBaseFileAdd({
                    id: props.knId,
                    index_id: props.indexId,
                    category_id: props.categoryId,
                    chunk_size: formData.segmentationMaxLength,
                    documents: formData.fileLists,
                    overlap_size: formData.segmentationOverlapLength,
                    rerank_min_score: formData.similarityThreshold,
                    separator: formData.segmentationIdentifier,
                    strategy: 2,
                });
            } else {
                await vectorKnowledgeBaseFileAdd({
                    name: formData.fileLists[0].name,
                    file_id: formData.fileLists[0].id,
                });
            }
            resolve(true);
        } catch (error) {
            feedback.msgError(error);
            reject(false);
        }
    });
});

window.onbeforeunload = () => {
    return "请勿刷新页面";
};
</script>

<style scoped lang="scss">
:deep(.el-upload) {
    .el-upload-dragger {
        @apply bg-[#f6f6f6] border-solid;
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
