<template>
    <div class="rounded-md bg-app-bg-3 border border-app-border-2 p-[6px] flex gap-x-2 w-full">
        <div class="flex-1 flex flex-col items-center justify-center">
            <upload
                class="w-full h-[112px]"
                show-progress
                :show-file-list="false"
                :ratio-size="ratioSize"
                :max-size="maxSize"
                :min-size="minSize"
                :accept="'.jpg,.jpeg,.png'"
                :limit="limit"
                @success="getUploadImage">
                <div class="flex flex-col items-center justify-center gap-2 h-[112px] w-full">
                    <template v-if="!formData[imgKey]">
                        <Icon name="local-icon-file_add" :size="28" color="#ffffff"></Icon>
                        <span class="text-white text-[11px]">{{ content }}</span>
                    </template>
                    <div class="border border-primary rounded-md w-full h-full overflow-hidden relative" v-else>
                        <img :src="formData[imgKey]" class="w-full h-full object-contain" />
                        <div class="absolute top-1 right-1 cursor-pointer w-6 h-6" @click.stop="formData[imgKey] = ''">
                            <close-btn />
                        </div>
                        <div class="absolute bottom-3 w-full flex justify-center">
                            <div
                                class="cursor-pointer px-[10px] text-white rounded-full border border-[#ffffff1a] bg-[#ffffff4d] shadow-[0px_6px_12px_0px_rgba(0,0,0,0.24)] h-[28px] flex items-center justify-center">
                                更换图片
                            </div>
                        </div>
                    </div>
                </div>
            </upload>
        </div>
        <div
            class="w-[112px] h-[112px] rounded-md border border-app-border-2 flex items-center justify-center overflow-hidden text-white"
            v-if="!formData[imgKey]">
            <video :src="templateVideoUrl" class="w-full h-full object-cover" autoplay loop></video>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    maxSize?: number;
    minSize?: number;
    ratioSize?: [number, number];
    limit?: number;
    content?: string;
    imgKey?: string;
    templateVideoUrl?: string;
}
const props = withDefaults(defineProps<Props>(), {
    label: "",
    maxSize: 10,
    minSize: 0,
    ratioSize: () => [2, 1],
    limit: 1,
    content: "上传图片",
    imgKey: "image",
    templateVideoUrl: "",
});
const emit = defineEmits(["update:formData"]);

const formData = defineModel<any>("formData");

const getUploadImage = (result: any) => {
    formData.value[props.imgKey] = result.data.uri;
};
</script>

<style scoped></style>
