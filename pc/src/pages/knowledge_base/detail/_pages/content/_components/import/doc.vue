<template>
    <div class="h-full flex flex-col px-3 pb-3">
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
                                    :class="{
                                        'bg-primary text-white': currIndex == index,
                                    }">
                                    <Icon name="local-icon-upload2"></Icon>
                                </div>
                                <div class="ml-2 line-clamp-1 flex-1 break-all">
                                    {{ item.name }}
                                </div>
                                <div class="flex-shrink-0 ml-2 w-4 h-4" @click="handleDeleteFile(index)">
                                    <close-btn :theme="ThemeEnum.LIGHT" :icon-size="12" />
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
                <div class="flex-shrink-0 border-t border-[#EFEFEF] pt-3 mx-3">
                    <div class="flex items-center gap-x-2">
                        <div>分段长度</div>
                        <ElTooltip
                            popper-class="w-[400px]"
                            content="按结束符号进行分段。我们建议您的文档应合理的使用标点符号，以确保每个完整的句子长度不要超过该值中文文档建议400~1000英文文档建议600~1200"
                            placement="top">
                            <span class="cursor-pointer">
                                <Icon name="local-icon-privacy" color="#00000080"></Icon>
                            </span>
                        </ElTooltip>
                    </div>
                    <div class="mt-4 flex items-center gap-x-2">
                        <ElInput
                            v-model="stageLen"
                            v-number-input="{ min: 0, decimal: 0 }"
                            type="number"
                            class="w-[100px]"></ElInput>
                        <ElButton type="primary" class="!rounded-full !h-[38px]" @click="reSplit">重新预览</ElButton>
                    </div>
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
import { uploadFile } from "@/api/app";
import type { UploadFile, UploadInstance } from "element-plus";
import { ThemeEnum } from "@/enums/appEnums";
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

<style lang="scss"></style>
