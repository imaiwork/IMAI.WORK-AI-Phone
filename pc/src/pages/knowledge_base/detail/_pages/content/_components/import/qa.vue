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
                <div class="flex flex-col items-center py-4">
                    <div class="w-12 h-12 rounded-full bg-[#F0F6FF] flex items-center justify-center mb-3">
                        <Icon name="local-icon-upload" class="text-primary" :size="24" />
                    </div>
                    <div class="text-[14px] font-medium text-[#64748B]">
                        将文件拖拽至此，或 <span class="text-primary font-[900] cursor-pointer">点击选择文件</span>
                    </div>
                    <div class="text-xs text-[#94A3B8] mt-2 italic">支持 {{ accept }} 格式，最大 50 个文件</div>
                </div>
            </ElUpload>
        </div>

        <div class="grow min-h-0 mt-4 flex gap-x-4" v-if="data.length > 0">
            <div class="w-1/4 h-full flex flex-col bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                <div class="p-4 border-b border-br bg-white flex items-center justify-between">
                    <span class="text-[13px] font-[900] text-[#1E293B]">待解析列表</span>
                    <span class="text-[11px] font-black text-primary bg-[#F0F6FF] px-2 py-0.5 rounded-md">
                        {{ data.length }} Files
                    </span>
                </div>

                <div class="grow min-h-0 py-2">
                    <ElScrollbar>
                        <div class="px-3 flex flex-col gap-y-2">
                            <div
                                v-for="(item, index) in data"
                                :key="index"
                                class="file-nav-item group"
                                :class="{ 'is-active': currIndex == index }"
                                @click="selectStage(index)">
                                <div class="flex items-center flex-1 min-w-0">
                                    <div class="icon-tag" :class="{ 'is-active': currIndex == index }">
                                        <Icon name="local-icon-upload2" :size="14" />
                                    </div>
                                    <div
                                        class="ml-3 text-[13px] font-medium truncate flex-1"
                                        :class="currIndex == index ? 'text-primary' : 'text-[#475569]'">
                                        {{ item.name }}
                                    </div>
                                </div>
                                <div
                                    class="close-action opacity-0 group-hover:opacity-100"
                                    @click.stop="handleDeleteFile(index)">
                                    <Icon name="el-icon-Close" :size="14" />
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-white rounded-[20px] border border-br overflow-hidden">
                <div class="px-5 py-4 border-b border-[#F1F5F9] flex items-center justify-between bg-white">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-primary rounded-full"></span>
                        <span class="text-[14px] font-[900] text-[#1E293B]">分段内容预览</span>
                    </div>
                    <div class="text-xs font-medium text-[#94A3B8]">
                        共计 <span class="text-primary">{{ data[currIndex]?.data.length || 0 }}</span> 组
                    </div>
                </div>

                <div class="grow min-h-0 bg-slate-50">
                    <ElScrollbar>
                        <div class="p-5 space-y-3">
                            <div
                                v-for="(item, index) in data[currIndex]?.data"
                                :key="index"
                                class="stage-item-card transition-all">
                                <div class="flex items-start gap-4">
                                    <div class="stage-num">#{{ index + 1 }}</div>
                                    <div class="flex-1">
                                        <data-item
                                            v-model:data="item.q"
                                            :index="index"
                                            :name="data[currIndex]?.name"
                                            @delete="handleDeleteStage(index)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { UploadFile, UploadInstance } from "element-plus";
import { splitText2ChunksArray } from "@/utils/text-splitter";
import { isSameFile, type IDataItem } from "./hook";
import DataItem from "./data-item.vue";

const uploadRef = shallowRef<UploadInstance>();

const data = defineModel<IDataItem[]>("modelValue", { required: true });

const fileAccept = [".txt", ".docx", ".pdf", ".md"];
const accept = fileAccept.join(", ");

const fileList = ref<File[]>([]);

const loading = ref(false);

const currIndex = ref(0);

const onFileChange = async ({ raw: file }: UploadFile) => {
    try {
        if (file) {
            // 验证文件类型
            const fileExtension = "." + file.name.split(".").pop()?.toLowerCase();
            if (!fileAccept.includes(fileExtension)) {
                throw `不支持的文件类型，请上传 ${accept} 格式的文件`;
            }

            loading.value = true;
            await isSameFile(file, fileList.value);
            const content = await parseFile(file);
            if (!content) {
                throw "解析结果为空，已自动忽略";
            }

            const isSplitContent: any = splitContent(content);
            data.value.push({
                name: file.name,
                size: file.size,
                path: "",
                data: isSplitContent,
            });
            //@ts-ignore
            file.data = isSplitContent;

            fileList.value.push(file);
            selectStage(fileList.value.length - 1);
        }
    } catch (error: any) {
        feedback.msgError(error);
    } finally {
        loading.value = false;
        uploadRef.value?.clearFiles();
    }
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

const splitContent = (content: string) => {
    const data: { q: string; a: string }[] = [];
    const contentList = splitText2ChunksArray({
        text: content,
        chunkLen: 1000,
    });
    contentList.forEach((item) => {
        data.push({ q: item, a: "" });
    });
    return data;
};

const handleDeleteFile = async (index: any) => {
    useNuxtApp().$confirm({
        message: "确定要删除该段落吗？",
        onConfirm: () => {
            data.value.splice(index, 1);
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
        @apply bg-slate-50 border-2 border-dashed border-br rounded-[24px] transition-all;
        &:hover {
            @apply border-primary bg-[#0065fb]/[0.02];
        }
    }
}

.file-nav-item {
    @apply flex items-center justify-between p-3 rounded-xl border-2 border-[transparent] cursor-pointer transition-all bg-[transparent];

    &:hover {
        @apply bg-white border-[#F1F5F9];
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
        box-shadow: 0 4px 8px rgba(var(--el-color-primary), 0.2);
    }
}

.close-action {
    @apply w-6 h-6 flex items-center justify-center rounded-md text-[#94A3B8] hover:bg-[#FEE2E2] hover:text-danger transition-all;
}

.stage-item-card {
    @apply bg-white rounded-xl border border-br p-4;
    &:hover {
        @apply border-[#0065fb]/30;
    }
}

.stage-num {
    @apply px-2 py-0.5 rounded bg-[#F1F5F9] text-[10px] font-black text-[#94A3B8] mt-1;
}

:deep(.el-scrollbar__thumb) {
    @apply bg-[#E2E8F0];
    &:hover {
        @apply bg-[#CBD5E1];
    }
}
</style>
