<template>
    <div class="image-upload-wrapper">
        <div class="preview-section">
            <template v-if="formData[imgKey]">
                <div class="preview-container group">
                    <img :src="formData[imgKey]" class="bg-blur" />
                    <img :src="formData[imgKey]" class="main-img" />

                    <div class="action-overlay">
                        <div class="close-btn-wrapper" @click.stop="formData[imgKey] = ''">
                            <close-btn />
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="template-container">
                    <video
                        v-if="templateVideoUrl"
                        :src="templateVideoUrl"
                        class="w-full h-full object-cover"
                        autoplay
                        loop
                        muted
                        playsinline></video>
                    <div v-else class="empty-placeholder">
                        <Icon name="el-icon-Picture" :size="32" color="#CBD5E1"></Icon>
                    </div>
                    <div class="type-tag">Sample Reference</div>
                </div>
            </template>
        </div>

        <div class="upload-trigger-section">
            <upload
                class="w-full"
                show-progress
                :show-file-list="false"
                :ratio-size="ratioSize"
                :max-size="maxSize"
                :min-size="minSize"
                :accept="'.jpg,.jpeg,.png,.webp'"
                :limit="limit"
                @success="getUploadImage">
                <div class="upload-dropzone group">
                    <div class="icon-circle group-hover:scale-110">
                        <Icon name="local-icon-file_add" :size="22"></Icon>
                    </div>
                    <div class="text-content">
                        <span class="main-text">{{ formData[imgKey] ? "更换图片" : content }}</span>
                        <span class="sub-text">支持 JPG, PNG, WEBP (Max {{ maxSize }}MB)</span>
                    </div>
                </div>
            </upload>
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
    maxSize: 5,
    minSize: 0,
    ratioSize: () => [0, 0],
    limit: 1,
    content: "上传参考图",
    imgKey: "image",
    templateVideoUrl: "",
});

const emit = defineEmits(["update:modelValue", "change"]);
const formData: any = defineModel("modelValue");

const getUploadImage = (res: any) => {
    const { uri } = res.data;
    formData.value[props.imgKey] = uri;
    emit("change", uri);
};
</script>

<style scoped lang="scss">
.image-upload-wrapper {
    @apply flex flex-col gap-3 w-full p-2;
}

/* 顶部预览/示例区 */
.preview-section {
    @apply w-full h-[180px] rounded-[18px] overflow-hidden bg-slate-50 relative border border-[#F1F5F9];

    .preview-container {
        @apply w-full h-full relative flex items-center justify-center;

        .bg-blur {
            @apply absolute inset-0 w-full h-full object-cover blur-xl opacity-30 scale-110;
        }
        .main-img {
            @apply relative z-10 max-w-full max-h-full object-contain p-2 transition-transform duration-500;
        }

        .action-overlay {
            @apply absolute inset-0 bg-[#000000]/20  transition-all flex items-start justify-end p-2 z-20;
        }
    }

    .template-container {
        @apply w-full h-full relative;
        .empty-placeholder {
            @apply w-full h-full flex items-center justify-center bg-slate-50;
        }
        .type-tag {
            @apply absolute bottom-2 left-2 px-2 py-0.5 bg-[#000000]/40 backdrop-blur-md text-white text-[9px] font-medium rounded-md uppercase tracking-widest;
        }
    }
}

/* 下方上传触发区 */
.upload-trigger-section {
    .upload-dropzone {
        @apply w-full flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 border-2 border-dashed border-br transition-all cursor-pointer hover:border-[#0065fb] hover:bg-[#F5F7FF];

        .icon-circle {
            @apply w-10 h-10 rounded-xl bg-white shadow-light flex items-center justify-center text-[#94A3B8] transition-all group-hover:text-primary group-hover:shadow-light;
        }

        .text-content {
            @apply flex flex-col items-start;
            .main-text {
                @apply text-[13px] font-[900] text-[#1E293B] group-hover:text-primary transition-colors;
            }
            .sub-text {
                @apply text-[10px] text-[#94A3B8] font-medium;
            }
        }
    }
}

.close-btn-wrapper {
    @apply w-8 h-8 flex items-center justify-center bg-white rounded-full shadow-light cursor-pointer transform hover:scale-110 active:scale-95 transition-all;
}
</style>
