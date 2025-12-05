<template>
    <div class="h-full flex flex-col px-3 pb-3">
        <div class="flex-shrink-0">
            <ElUpload
                ref="uploadRef"
                drag
                show-progress
                multiple
                :auto-upload="false"
                :show-file-list="false"
                :accept="accept"
                :limit="50"
                :on-change="onFileChange">
                <div class="text-[#00000080] flex items-center gap-2 justify-center">
                    <Icon name="local-icon-upload" />
                    拖拽文件至此，或点击<span class="text-primary"> 选择文件 </span>
                </div>
                <div class="text-[#00000080] mt-2">支持 {{ accept }} 文件</div>
            </ElUpload>
        </div>
        <div class="grow min-h-0 mt-3 flex gap-x-2" v-if="data.length > 0">
            <div class="w-1/4 h-full flex flex-col bg-[#F6F6F6] rounded-xl border border-[#efefef]">
                <div class="flex-shrink-1 min-h-0 mb-3">
                    <ElScrollbar>
                        <div class="p-3 flex flex-col gap-y-2">
                            <div
                                v-for="(item, index) in data"
                                :key="index"
                                class="flex items-center p-2 rounded-lg mt-1 cursor-pointer"
                                :class="[
                                    currIndex == index
                                        ? 'bg-[#0065fb0d] shadow-[0_0_0_1px_var(--color-primary)]'
                                        : 'bg-[#f6f6f6] shadow-[0_0_0_1px_#EFEFEF]',
                                ]"
                                @click="selectStage(index)">
                                <div
                                    class="w-5 h-5 rounded bg-[#0000000d] flex items-center justify-center"
                                    :class="{ 'bg-primary text-white': currIndex == index }">
                                    <Icon name="local-icon-upload2"></Icon>
                                </div>
                                <div class="ml-2 line-clamp-1 flex-1">
                                    {{ item.name }}
                                </div>
                                <div @click="handleDeleteFile(index)">
                                    <close-btn />
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
            <div class="flex-1 flex flex-col bg-[#F6F6F6] rounded-xl border border-[#efefef]">
                <div class="px-3 mt-3">分段预览（{{ data[currIndex]?.data.length }}组）</div>
                <div class="grow min-h-0">
                    <ElScrollbar>
                        <div class="px-3">
                            <div
                                v-for="(item, index) in data[currIndex]?.data"
                                :key="index"
                                class="rounded-xl p-[10px] mt-2 break-all bg-white">
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

<style lang="scss"></style>
