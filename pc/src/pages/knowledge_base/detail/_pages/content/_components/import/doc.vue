<template>
    <div class="h-full flex flex-col px-4 pb-4">
        <div class="flex-shrink-0" v-loading="loading">
            <ElUpload
                ref="uploadRef"
                drag
                show-progress
                multiple
                :auto-upload="false"
                :show-file-list="false"
                :accept="accept"
                :limit="50"
                :on-change="onFileChange"
                class="custom-upload">
                <div class="flex flex-col items-center py-2">
                    <div class="w-12 h-12 rounded-full bg-[#F0F6FF] flex items-center justify-center mb-2 group">
                        <Icon name="local-icon-upload" :size="24" color="var(--color-primary)" />
                    </div>
                    <div class="text-[14px] font-medium text-[#64748B]">
                        拖拽文件至此，或 <span class="text-primary font-black">点击上传</span>
                    </div>
                    <div class="text-xs text-[#94A3B8] mt-1 italic">支持 {{ accept }} 格式</div>
                </div>
            </ElUpload>
        </div>

        <div class="grow min-h-0 mt-4 flex gap-x-4" v-if="data.length > 0">
            <div class="w-1/4 h-full flex flex-col bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                <div class="p-4 border-b border-br bg-white">
                    <div class="text-[13px] font-[900] text-[#1E293B]">已上传文件 ({{ data.length }})</div>
                </div>

                <div class="grow min-h-0 py-2">
                    <ElScrollbar>
                        <div class="px-3 flex flex-col gap-y-2">
                            <div
                                v-for="(item, index) in data"
                                :key="index"
                                class="file-item-card group"
                                :class="{ 'is-active': currIndex == index }"
                                @click="selectStage(index)">
                                <div class="flex items-center flex-1 min-w-0">
                                    <div class="icon-tag" :class="{ 'is-active': currIndex == index }">
                                        <Icon name="local-icon-document" :size="14" />
                                    </div>
                                    <div
                                        class="ml-3 text-[13px] font-medium truncate flex-1"
                                        :class="currIndex == index ? 'text-primary' : 'text-[#475569]'">
                                        {{ item.name }}
                                    </div>
                                </div>
                                <div
                                    class="delete-btn opacity-0 group-hover:opacity-100"
                                    @click.stop="handleDeleteFile(index)">
                                    <Icon name="el-icon-Close" :size="14" />
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>

                <div class="flex-shrink-0 bg-white border-t border-br p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-[13px] font-black text-[#1E293B]">
                            分段长度
                            <ElTooltip content="建议中文 400-1000，英文 600-1200" placement="top">
                                <div class="text-[#94A3B8] cursor-help">
                                    <Icon name="el-icon-QuestionFilled" :size="14" />
                                </div>
                            </ElTooltip>
                        </div>
                        <div class="text-xs font-medium text-primary bg-[#F0F6FF] px-2 py-0.5 rounded">Token Limit</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <ElInput
                            v-model="stageLen"
                            v-number-input="{ min: 0, decimal: 0 }"
                            type="number"
                            class="custom-input-compact" />
                        <ElButton
                            type="primary"
                            class="!rounded-xl !h-[40px] !bg-primary !border-none !font-black grow"
                            @click="reSplit">
                            重新预览
                        </ElButton>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-white rounded-[20px] border border-br overflow-hidden">
                <div class="px-5 py-4 border-b border-[#F1F5F9] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-primary rounded-full"></span>
                        <span class="text-[14px] font-[900] text-[#1E293B]">预览解析详情</span>
                    </div>
                    <div class="text-xs font-medium text-[#94A3B8]">
                        当前文件共 <span class="text-primary">{{ data[currIndex]?.data.length }}</span> 组分段
                    </div>
                </div>

                <div class="grow min-h-0 bg-slate-50">
                    <ElScrollbar>
                        <div class="p-5 space-y-3">
                            <div
                                v-for="(item, index) in data[currIndex]?.data"
                                :key="index"
                                class="stage-preview-card group">
                                <div class="stage-badge"># {{ index + 1 }}</div>
                                <data-item
                                    v-model:data="item.q"
                                    :index="index"
                                    :name="data[currIndex]?.name"
                                    @delete="handleDeleteStage(index)" />
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { uploadFile } from "@/api/app";
import type { UploadFile, UploadInstance } from "element-plus";
import { readDocContent, readPdfContent, readTxtContent } from "@/utils/file-reader";
import { splitText2ChunksArray } from "@/utils/text-splitter";
import DataItem from "./data-item.vue";
import { isSameFile, type IDataItem } from "./hook";

const uploadRef = shallowRef<UploadInstance>();
const data = defineModel<IDataItem[]>("modelValue", { required: true });
const fileAccept = [".txt", ".docx", ".pdf", ".md"];
const accept = fileAccept.join(", ");
const fileList = ref<File[]>([]);
const loading = ref(false);
const currIndex = ref(0);

//分段长度

const stageLen = ref(512);

const onFileChange = async ({ raw: file }: UploadFile) => {
    try {
        if (file) {
            loading.value = true;
            // 验证文件类型
            const fileExtension = "." + file.name.split(".").pop()?.toLowerCase();
            if (!fileAccept.includes(fileExtension)) {
                throw `不支持的文件类型，请上传 ${accept} 格式的文件`;
            }
            const { uri } = await uploadFile({
                file: file,
            });

            await isSameFile(file, fileList.value);
            const content = await parseFile(file);
            if (!content) {
                throw "解析结果为空，已自动忽略";
            }
            data.value.push({
                name: file.name,
                size: file.size,
                path: uri,
                data: [],
            });
            //@ts-ignore
            file.data = content;
            loading.value = false;
            fileList.value.push(file);
            selectStage(fileList.value.length - 1);
            reSplit();
        }
    } catch (error: any) {
        feedback.msgError(error);
    } finally {
        loading.value = false;

        uploadRef.value?.clearFiles();
    }
};

const reSplit = () => {
    data.value.forEach((item: any) => {
        item.data.length = 0;
        const index = fileList.value.findIndex((fileItem) => fileItem.name == item.name);
        const contentList = splitText2ChunksArray({
            //@ts-ignore
            text: fileList.value[index].data,
            chunkLen: stageLen.value,
        });

        contentList.forEach((contentListItem) => {
            item.data.push({ q: contentListItem, a: "" });
        });
    });
};

const parseFile = async (file: File) => {
    const suffix = file.name.substring(file.name.lastIndexOf(".") + 1);

    let res = "";

    switch (suffix) {
        case "md":
        case "txt":
            res = await readTxtContent(file);
            break;
        case "pdf":
            res = await readPdfContent(file);
            break;

        case "doc":
        case "docx":
            res = await readDocContent(file);
            break;
        default:
            res = await readTxtContent(file);
            break;
    }

    return res;
};

const handleDeleteFile = async (index: any) => {
    useNuxtApp().$confirm({
        message: "确定要删除该段落吗？",

        onConfirm: () => {
            data.value.splice(index, 1);

            fileList.value.splice(index, 1);
        },
    });
};

const handleDeleteStage = async (index: any) => {
    data.value[currIndex.value].data.splice(index, 1);
};

const selectStage = (index: number) => {
    currIndex.value = index;
};
</script>
<style scoped lang="scss">
:deep(.custom-upload) {
    .el-upload-dragger {
        @apply border-2 border-dashed border-br bg-slate-50 rounded-[20px] transition-all;
        &:hover {
            @apply border-primary bg-[#0065fb]/[0.02];
        }
    }
}

.file-item-card {
    @apply flex items-center justify-between p-3 rounded-xl  border-[transparent] cursor-pointer transition-all bg-[transparent];

    &:hover {
        @apply bg-white border-br shadow-light;
    }

    &.is-active {
        @apply border-primary bg-white;
        box-shadow: 0 4px 12px rgba(var(--el-color-primary), 0.08);
    }
}

.icon-tag {
    @apply w-7 h-7 rounded-lg bg-[#E2E8F0] text-[#64748B] flex items-center justify-center transition-all;
    &.is-active {
        @apply bg-primary text-white;
    }
}

.delete-btn {
    @apply w-6 h-6 flex items-center justify-center rounded-md text-[#94A3B8] hover:bg-[#FEE2E2] hover:text-danger transition-all;
}
.stage-preview-card {
    @apply relative bg-white rounded-xl border border-br p-4 transition-all;
    &:hover {
        @apply border-[#0065fb]/40;
    }
}

.stage-badge {
    @apply absolute top-3 right-3 text-[10px] font-black text-[#CBD5E1] italic;
}
:deep(.custom-input-compact) {
    .el-input__wrapper {
        @apply rounded-xl bg-slate-50 shadow-[none] border border-br h-[40px];
        &.is-focus {
            @apply border-primary;
        }
    }
}
</style>
