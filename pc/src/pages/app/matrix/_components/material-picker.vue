<template>
    <DefineMaterialMenuTemplate>
        <div class="p-1 flex flex-col gap-y-1">
            <div
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-[#F1F5F9] cursor-pointer transition-colors group"
                @click="handleImportMaterial">
                <div
                    class="w-8 h-8 rounded-lg bg-[#EEF2FF] flex items-center justify-center group-hover:bg-white transition-colors">
                    <Icon name="local-icon-import" :size="16" color="var(--color-primary)"></Icon>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-[#1E293B]">从素材库导入</span>
                    <span class="text-[10px] text-[#94A3B8]">选择已有的云端资源</span>
                </div>
            </div>

            <upload
                class="w-full"
                show-progress
                v-bind="getUploadProps"
                :show-file-list="false"
                @success="getUploadSuccess">
                <div
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-[#ECFDF5] cursor-pointer transition-colors group">
                    <div
                        class="w-8 h-8 rounded-lg bg-[#ECFDF5] flex items-center justify-center group-hover:bg-white transition-colors">
                        <Icon name="local-icon-upload" :size="16" color="#10B981"></Icon>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-[#1E293B]">本地直接上传</span>
                        <span class="text-[10px] text-[#94A3B8]"
                            >支持常见{{ type == PublishTaskTypeEnum.VIDEO ? "视频" : "图片" }}格式</span
                        >
                    </div>
                </div>
            </upload>
        </div>
    </DefineMaterialMenuTemplate>

    <div class="grid grid-cols-2 2xl:grid-cols-3 3xl:grid-cols-4 gap-4">
        <div v-for="(item, index) in materialList" :key="index" class="material-card group">
            <div class="relative w-full h-full overflow-hidden rounded-[18px] bg-slate-50">
                <video
                    v-if="type == PublishTaskTypeEnum.VIDEO"
                    :src="item.url"
                    class="w-full h-full object-cover"
                    @click="handlePreviewVideo(item.url)"></video>
                <ElImage
                    v-else-if="type == PublishTaskTypeEnum.IMAGE"
                    :src="item.url"
                    class="w-full h-full"
                    fit="cover"
                    preview-teleported
                    :preview-src-list="[item.url]"></ElImage>

                <div
                    class="absolute top-2 right-2 z-20 translate-y-[-10px] opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                    <div class="w-8 h-8" @click="handleDeleteMaterial(index)">
                        <close-btn :icon-size="12"></close-btn>
                    </div>
                </div>

                <div
                    class="absolute inset-x-0 bottom-0 z-10 p-2 translate-y-[20px] opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                    <ElPopover trigger="click" width="240" popper-class="custom-material-popover" :show-arrow="false">
                        <template #reference>
                            <div
                                class="w-full h-9 rounded-xl bg-white/90 backdrop-blur-md flex items-center justify-center gap-2 cursor-pointer hover:bg-white active:scale-95 transition-all"
                                @click="handleReplaceMaterial(index)">
                                <Icon name="el-icon-Refresh" :size="14" color="var(--color-primary)"></Icon>
                                <span class="text-xs font-black text-primary">替换素材</span>
                            </div>
                        </template>
                        <MaterialTemplate />
                    </ElPopover>
                </div>

                <div
                    class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors pointer-events-none"></div>
            </div>
        </div>
        <ElPopover
            v-if="materialList.length < getUploadProps.limit"
            trigger="click"
            width="240"
            popper-class="custom-material-popover"
            :show-arrow="false">
            <template #reference>
                <div class="material-item-add group relative" @click="handleReplaceMaterial(-1)">
                    <div class="flex flex-col items-center justify-center gap-y-3 relative z-10">
                        <div
                            class="w-14 h-14 rounded-2xl bg-[#F1F5F9] text-slate-400 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all duration-500 group-hover:rotate-90">
                            <Icon name="el-icon-Plus" :size="24"></Icon>
                        </div>

                        <div class="text-center">
                            <div
                                class="text-[13px] font-black text-slate-600 group-hover:text-primary transition-colors">
                                添加素材
                            </div>
                            <div class="mt-1 flex items-center justify-center gap-1.5">
                                <span
                                    class="px-2 py-0.5 rounded-full bg-slate-100 text-[10px] font-medium text-slate-400 group-hover:bg-primary/10 group-hover:text-primary transition-all">
                                    {{ materialList.length }} / {{ getUploadProps.limit }}
                                </span>
                                <span
                                    v-if="getUploadProps.limit - materialList.length <= 3"
                                    class="text-[10px] font-medium text-orange-500 animate-pulse">
                                    还可传 {{ getUploadProps.limit - materialList.length }} 个
                                </span>
                                <span v-else class="text-[10px] font-medium text-slate-300">剩余可传</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <MaterialTemplate />
        </ElPopover>
    </div>
</template>

<script setup lang="ts">
import { uploadImage } from "@/api/app";
import { createReusableTemplate } from "@vueuse/core";
import { PublishTaskTypeEnum, MaterialActionType } from "../_enums";

// Props & Emits 保持逻辑一致，增加类型安全性
const props = withDefaults(
    defineProps<{
        type: PublishTaskTypeEnum;
        accept?: string;
        materialList: any[];
        maxVideoCount?: number;
        maxImageCount?: number;
        maxSize?: number;
    }>(),
    {
        type: PublishTaskTypeEnum.VIDEO,
        accept: "",
        materialList: () => [],
        maxVideoCount: 30,
        maxImageCount: 18,
        maxSize: 100,
    }
);

const emit = defineEmits(["update:materialList", "previewVideo", "importMaterial", "changeMaterial"]);

const { type, accept, maxVideoCount, maxImageCount, maxSize } = toRefs(props);
const materialList = defineModel<any[]>("materialList");
const replaceMaterialIndex = ref(-1);

const getUploadProps = computed(() => {
    return type.value == PublishTaskTypeEnum.VIDEO
        ? { type: "video", accept: accept.value, limit: maxVideoCount.value, maxSize: maxSize.value }
        : { type: "image", accept: accept.value, limit: maxImageCount.value, maxSize: maxSize.value };
});

const getUploadSuccess = async (result: any) => {
    const { uri } = result.data;
    if (type.value == PublishTaskTypeEnum.VIDEO) {
        const { file } = await getVideoFirstFrame(uri);
        const res = await uploadImage({ file });
        if (replaceMaterialIndex.value > -1) {
            materialList.value[replaceMaterialIndex.value].url = uri;
            materialList.value[replaceMaterialIndex.value].pic = res.uri;
        } else {
            // 这里要判断上传的素材是不是超过最大数量
            if (materialList.value.length > maxVideoCount.value) {
                return;
            }
            materialList.value.push({ url: uri, pic: res.uri });
        }
    } else {
        if (replaceMaterialIndex.value > -1) {
            materialList.value[replaceMaterialIndex.value].url = uri;
        } else {
            if (materialList.value.length > maxImageCount.value) {
                return;
            }
            materialList.value.push({ url: uri });
        }
    }
    emit("update:materialList", materialList.value);
    emit("changeMaterial", {
        type: replaceMaterialIndex.value == -1 ? MaterialActionType.ADD : MaterialActionType.REPLACE,
    });
    replaceMaterialIndex.value = -1;
};

const handleImportMaterial = () => {
    emit("importMaterial", { index: replaceMaterialIndex.value, type: MaterialActionType.REPLACE });
};

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
};

const handleDeleteMaterial = (index: number) => {
    useNuxtApp().$confirm({
        message: "确定要从本次任务中移除该素材吗？",
        onConfirm: () => {
            materialList.value.splice(index, 1);
            emit("update:materialList", materialList.value);
            emit("changeMaterial", { type: MaterialActionType.DELETE });
        },
    });
};

const handlePreviewVideo = (url: string) => {
    emit("previewVideo", url);
};

const [DefineMaterialMenuTemplate, MaterialTemplate] = createReusableTemplate();
</script>

<style scoped lang="scss">
/* 添加素材占位符 */
.material-item-add {
    @apply h-[180px] rounded-[24px] border-2 border-dashed border-[#E5E7EB] 
           flex flex-col items-center justify-center cursor-pointer 
           transition-all duration-300 hover:border-[#0065fb] hover:bg-slate-50;
}

/* 素材卡片基础容器 */
.material-card {
    @apply h-[180px] rounded-[24px] relative bg-white transition-all duration-300;

    &:hover {
        transform: translateY(-4px);
    }
}

/* 视频/图片容器阴影 */
.material-card > div {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* 自定义 Popover 样式 */
:global(.custom-material-popover) {
    padding: 8px !important;
    border-radius: 20px !important;
    border: 1px solid #f1f5f9 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}
</style>
