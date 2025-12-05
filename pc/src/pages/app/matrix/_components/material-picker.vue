<template>
    <DefineMaterialMenuTemplate>
        <div class="flex flex-col gap-y-2">
            <div class="type-menu-item" @click="handleImportMaterial">
                <span class="flex items-center justify-center rounded p-1 bg-[#ffffff0d]">
                    <Icon name="local-icon-import" color="#ffffff"></Icon>
                </span>
                <span class="text-[#ffffffcc]"> 素材库导入 </span>
            </div>
            <upload
                class="w-full"
                show-progress
                v-bind="getUploadProps"
                :show-file-list="false"
                @success="getUploadSuccess">
                <div class="type-menu-item">
                    <span class="flex items-center justify-center rounded p-1 bg-[#ffffff0d]">
                        <Icon name="local-icon-upload" color="#ffffff"></Icon>
                    </span>
                    <span class="text-[#ffffffcc]"> 本地上传</span>
                </div>
            </upload>
        </div>
    </DefineMaterialMenuTemplate>
    <div class="grid grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-2">
        <ElPopover
            v-if="materialList.length < getUploadProps.limit"
            trigger="click"
            width="212"
            popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2 choose-type-popover"
            :show-arrow="false">
            <template #reference>
                <div
                    class="material-item p-[6px] cursor-pointer hover:!border-[#ffffff33]"
                    @click="handleReplaceMaterial(-1)">
                    <div
                        class="w-8 h-8 rounded-xl flex items-center justify-center border border-dashed border-[#ffffff1a] hover:border-[#ffffff33] cursor-pointer mb-4">
                        <Icon name="el-icon-Plus" color="#ffffff"></Icon>
                    </div>
                    <div class="absolute bottom-2 w-full z-[33] px-2">
                        <div class="change-material-btn">添加素材</div>
                    </div>
                </div>
            </template>
            <MaterialTemplate />
        </ElPopover>
        <div v-for="(item, index) in materialList" :key="index" class="material-item">
            <video
                :src="item.url"
                class="w-full h-full object-cover rounded-md"
                v-if="type == PublishTaskTypeEnum.VIDEO"
                @click="handlePreviewVideo(item)"></video>
            <ElImage
                v-else-if="type == PublishTaskTypeEnum.IMAGE"
                :src="item.url"
                class="w-full h-full rounded-md"
                fit="cover"
                preview-teleported
                :preview-src-list="[item.url]"></ElImage>
            <div class="absolute top-1 right-1 z-[22] w-4 h-4 rounded-full" @click="handleDeleteMaterial(index)">
                <close-btn :icon-size="10" :theme="ThemeEnum.DARK"></close-btn>
            </div>
            <ElPopover
                trigger="click"
                width="212"
                popper-class="!rounded-xl !bg-app-bg-2 !border-app-border-2 !p-2 choose-type-popover"
                :show-arrow="false">
                <template #reference>
                    <div class="absolute bottom-2 w-full z-[33] px-2">
                        <div
                            class="change-material-btn cursor-pointer"
                            style="backdrop-filter: blur(6px)"
                            @click="handleReplaceMaterial(index)">
                            替换
                        </div>
                    </div>
                </template>
                <MaterialTemplate />
            </ElPopover>
        </div>
    </div>
</template>

<script setup lang="ts">
import { uploadImage } from "@/api/app";
import { PublishTaskTypeEnum, MaterialActionType } from "../_enums";
import { ThemeEnum } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        type: PublishTaskTypeEnum;
        accept?: string;
        materialList: any[];
        maxVideoCount?: number;
        maxImageCount?: number;
        maxSize?: number;
        videoMinResolution?: number | null;
        videoMaxResolution?: number | null;
        videoMinDuration?: number;
        videoMaxDuration?: number;
    }>(),
    {
        type: PublishTaskTypeEnum.VIDEO,
        accept: "",
        materialList: () => [],
        maxVideoCount: 30,
        maxImageCount: 18,
        maxSize: 100,
        videoMinResolution: null,
        videoMaxResolution: null,
        videoMinDuration: 0,
        videoMaxDuration: 99999,
    }
);

const emit = defineEmits(["update:materialList", "previewVideo", "importMaterial", "changeMaterial"]);

const {
    type,
    accept,
    maxVideoCount,
    maxImageCount,
    maxSize,
    videoMinResolution,
    videoMaxResolution,
    videoMinDuration,
    videoMaxDuration,
} = toRefs(props);

const materialList = defineModel<any[]>("materialList");
const replaceMaterialIndex = ref();

const getUploadProps = computed(() => {
    return type.value == PublishTaskTypeEnum.VIDEO
        ? {
              type: "video",
              accept: accept.value,
              limit: maxVideoCount.value,
              maxSize: maxSize.value,
              videoMinWidth: videoMinResolution.value,
              videoMaxWidth: videoMaxResolution.value,
              minDuration: videoMinDuration.value,
              maxDuration: videoMaxDuration.value,
          }
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
            materialList.value.push({ url: uri, pic: res.uri });
        }
    } else {
        if (replaceMaterialIndex.value > -1) {
            materialList.value[replaceMaterialIndex.value].url = uri;
        } else {
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
    emit("importMaterial", {
        index: replaceMaterialIndex.value,
        type: MaterialActionType.REPLACE,
    });
};

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
};

const handleDeleteMaterial = (index: number) => {
    useNuxtApp().$confirm({
        message: "确定要删除该素材吗？",
        theme: "dark",
        onConfirm: () => {
            if (type.value == PublishTaskTypeEnum.VIDEO) {
                materialList.value.splice(index, 1);
            } else if (type.value == PublishTaskTypeEnum.IMAGE) {
                materialList.value.splice(index, 1);
            }
            emit("update:materialList", materialList.value);
            emit("changeMaterial", {
                type: MaterialActionType.DELETE,
            });
        },
    });
};

const handlePreviewVideo = (url: string) => {
    emit("previewVideo", url);
};

const { DefineTemplate: DefineMaterialMenuTemplate, UseTemplate: MaterialTemplate } = useTemplate();
</script>

<style scoped lang="scss">
.material-item {
    @apply cursor-pointer rounded-md border border-app-border-1 flex flex-col items-center justify-center h-[150px] relative;
}
.change-material-btn {
    @apply text-white text-[11px] mt-8 border border-[rgba(255,255,255,0.1)] shadow-[0_0_0_1px_rgba(0,0,0,0.24)] rounded-md w-full h-[26px] flex items-center justify-center;
}
</style>
<style lang="scss"></style>
