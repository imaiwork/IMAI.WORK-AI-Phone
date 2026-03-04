<template>
    <div class="h-full flex flex-col px-4 pb-4">
        <div class="flex-shrink-0" v-loading="loading">
            <ElUpload
                ref="uploadRef"
                drag
                multiple
                :auto-upload="false"
                :show-file-list="false"
                :accept="accept"
                :on-change="onFileChange"
                class="custom-upload">
                <div class="flex flex-col items-center py-2">
                    <div class="w-12 h-12 rounded-full bg-[#F0F6FF] flex items-center justify-center mb-3">
                        <Icon name="local-icon-upload" :size="24" color="var(--color-primary)" />
                    </div>
                    <div class="text-[14px] font-medium text-[#64748B]">
                        拖拽 CSV/Excel 至此，或 <span class="text-primary font-[900]">点击选择文件</span>
                    </div>
                    <ElButton link type="primary" class="!text-xs mt-2 font-black" @click.stop>
                        <a href="/static/file/template/kn_qa.csv" target="_blank" class="flex items-center gap-1">
                            <Icon name="el-icon-Download" :size="14" /> 下载官方导入模版
                        </a>
                    </ElButton>
                </div>
            </ElUpload>

            <div class="mt-3 p-3 bg-[#FFF9F0] border border-[#FFE4BA] rounded-xl flex gap-3">
                <Icon name="el-icon-InfoFilled" color="#ED6A0C" :size="16" />
                <div class="text-xs text-[#A25D00] leading-5 font-medium">
                    请先完成模版填写后再上传。单文件建议不要超过 <span class="underline">1000条</span> 以保证流畅度。
                    <br />
                    系统将自动去重完全相同的问答对，但含有换行的内容目前暂不支持自动去重。
                </div>
            </div>
        </div>

        <div class="grow min-h-0 mt-4 flex gap-x-4" v-if="data.length > 0">
            <div class="w-1/4 h-full flex flex-col bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                <div class="px-4 py-3 border-b border-br bg-white text-[13px] font-[900] text-[#1E293B]">
                    已选文件 ({{ data.length }})
                </div>
                <div class="grow min-h-0 py-2">
                    <ElScrollbar>
                        <div class="px-3 flex flex-col gap-y-2">
                            <div
                                v-for="(item, index) in data"
                                :key="index"
                                class="file-card group"
                                :class="{ 'is-active': currIndex == index }"
                                @click="selectStage(index)">
                                <div class="flex items-center flex-1 min-w-0">
                                    <div class="icon-box" :class="{ 'is-active': currIndex == index }">
                                        <Icon name="local-icon-upload2" :size="14" />
                                    </div>
                                    <div
                                        class="ml-3 text-[13px] font-medium truncate flex-1"
                                        :class="currIndex == index ? 'text-primary' : 'text-[#475569]'">
                                        {{ item.name }}
                                    </div>
                                </div>
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                    @click.stop="handleDeleteFile(index)">
                                    <Icon
                                        name="el-icon-CircleCloseFilled"
                                        class="text-[#94A3B8] hover:text-red-500"
                                        :size="16" />
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-white rounded-[20px] border border-br overflow-hidden">
                <div class="px-5 py-4 border-b border-[#F1F5F9] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-primary rounded-full"></span>
                        <span class="text-[14px] font-[900] text-[#1E293B]"
                            >问答对预览（{{ data[currIndex]?.data.length }}）</span
                        >
                    </div>
                </div>
                <div class="grow min-h-0 bg-slate-50">
                    <ElScrollbar>
                        <div class="p-4 space-y-3">
                            <div
                                v-for="(item, index) in data[currIndex]?.data"
                                :key="index"
                                class="qa-item-wrapper transition-all">
                                <CsvItem
                                    v-model:q="item.q"
                                    v-model:a="item.a"
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
import { isSameFile, type IDataItem } from "./hook";
import CsvItem from "./csv-item.vue";

const uploadRef = shallowRef<UploadInstance>();

const data = defineModel<IDataItem[]>("modelValue", { required: true });

const fileAccept = [".csv", ".xlsx"];
const accept = fileAccept.join(", ");

const fileList = ref<File[]>([]);

const loading = ref(false);

const currIndex = ref(0);

const onFileChange = async ({ raw: file }: UploadFile) => {
    try {
        if (file) {
            const { uri } = await uploadFile({
                file: file,
            });
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
            if (!isArray(content)) {
                throw "解析失败";
            }

            data.value.push({
                name: file.name,
                size: file.size,
                path: uri,
                data: content,
            });
            //@ts-ignore
            file.data = content;

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

const parseFile = async (file: any) => {
    const suffix = file.name.substring(file.name.lastIndexOf(".") + 1);
    let res = "";
    switch (suffix) {
        case "csv":
            res = await readCsvContent(file);
            break;
        case "xlsx":
            res = await readXlsxContent(file);
            break;
    }
    return res;
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

.file-card {
    @apply flex items-center justify-between p-3 rounded-xl border border-[transparent] cursor-pointer transition-all bg-[transparent];
    &:hover {
        @apply bg-white border-[#F1F5F9];
    }
    &.is-active {
        @apply bg-white border-primary;
        box-shadow: 0 4px 12px rgba(var(--el-color-primary), 0.08);
    }
}

.icon-box {
    @apply w-7 h-7 rounded-lg bg-[#E2E8F0] text-[#64748B] flex items-center justify-center transition-all;
    &.is-active {
        @apply bg-primary text-white;
    }
}

.qa-item-wrapper {
    @apply bg-white rounded-xl border border-br p-4;
    &:hover {
        @apply border-[#0065fb]/30;
    }
}
</style>
