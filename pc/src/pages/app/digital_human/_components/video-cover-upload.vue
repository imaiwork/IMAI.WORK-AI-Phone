<template>
    <div class="bg-slate-50 rounded-[20px] border border-br overflow-hidden w-full min-w-0">
        <div class="px-4 py-3 border-b border-slate-100 bg-[#f8fafc]/80 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider">视频封面</span>
                <span
                    class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-400 text-[10px] font-black uppercase tracking-wide">
                    非必填
                </span>
            </div>
            <button
                v-if="modelValue"
                class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-slate-300 hover:text-red-400 hover:bg-red-50 transition-all"
                @click="handleDelete">
                <Icon name="el-icon-Delete" :size="13" />
            </button>
        </div>

        <div class="p-4 min-w-0 overflow-hidden">
            <template v-if="!modelValue">
                <upload
                    class="w-full"
                    type="image"
                    :data="{
                        ffmpeg: 1,
                    }"
                    :image-resolution="[
                        [0, 2000],
                        [0, 2000],
                    ]"
                    :limit="1"
                    :max-size="maxSize"
                    :show-file-list="false"
                    show-progress
                    accept="image/jpeg,image/jpg,image/png"
                    drag
                    @success="handleUpload">
                    <div
                        class="group flex flex-col items-center justify-center gap-3 py-6 px-4 rounded-[16px] border-2 border-dashed border-slate-200 bg-white hover:border-primary hover:bg-[#0065fb]/5 transition-all cursor-pointer">
                        <div
                            class="w-12 h-12 rounded-2xl bg-slate-50 group-hover:bg-[#0065fb]/10 border border-slate-100 flex items-center justify-center transition-all group-hover:scale-110">
                            <span class="group-hover:!text-primary text-slate-300 leading-[0]">
                                <Icon name="el-icon-Picture" :size="24" />
                            </span>
                        </div>
                        <div class="text-center space-y-1">
                            <p class="text-[13px] font-black text-slate-500 group-hover:text-primary transition-colors">
                                点击或拖拽上传封面图
                            </p>
                            <p class="text-[11px] text-slate-300 font-medium">
                                支持 JPG / PNG / WEBP · 建议比例 16:9 或 9:16 · 最大 {{ maxSize }}MB
                            </p>
                        </div>
                    </div>
                </upload>
            </template>

            <template v-else>
                <div class="flex items-center gap-3 min-w-0 overflow-hidden">
                    <div
                        class="relative flex-shrink-0 rounded-[14px] overflow-hidden border border-slate-100 group cursor-pointer shadow-sm"
                        style="width: 100px; height: 68px"
                        @click="handlePreview">
                        <ElImage :src="modelValue" fit="cover" class="w-full h-full" />
                        <div
                            class="absolute inset-0 bg-[#000000]/0 group-hover:bg-[#000000]/40 transition-all flex items-center justify-center">
                            <Icon
                                name="el-icon-ZoomIn"
                                color="#fff"
                                :size="20"
                                class="opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <div
                            v-if="imageRatio"
                            class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 bg-[#000000]/50 backdrop-blur-sm rounded-md text-[9px] text-white font-black">
                            {{ imageRatio }}
                        </div>
                    </div>

                    <div class="flex-1 min-w-0 flex flex-col gap-2.5">
                        <div class="flex items-center gap-1.5">
                            <Icon name="el-icon-CircleCheckFilled" color="#22c55e" :size="15" class="flex-shrink-0" />
                            <span class="text-[13px] font-black text-slate-700 truncate">封面已上传</span>
                        </div>
                        <p class="text-[11px] text-slate-400 truncate font-medium min-w-0">{{ fileName }}</p>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                class="h-7 px-3 rounded-lg border border-slate-200 bg-white text-slate-500 text-[11px] font-black hover:border-red-300 hover:text-red-400 transition-all flex items-center gap-1.5 whitespace-nowrap"
                                @click="handleDelete">
                                <Icon name="el-icon-Delete" :size="12" />
                                删除
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <ElImageViewer v-if="showPreview" :url-list="[modelValue]" @close="showPreview = false" />
</template>

<script setup lang="ts">
interface Props {
    modelValue?: string;
    maxSize?: number;
}

interface Emits {
    (e: "update:modelValue", value: string): void;
    (e: "change", value: string): void;
    (e: "delete"): void;
    (e: "error", msg: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: "",
    maxSize: 10,
});

const emit = defineEmits<Emits>();

const fileName = ref("");
const imageRatio = ref("");
const showPreview = ref(false);

// 监听外部传入 URL 时同步检测比例
watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            detectRatio(val);
        } else {
            fileName.value = "";
            imageRatio.value = "";
        }
    },
    { immediate: true },
);

// 检测图片宽高比
const detectRatio = (url: string) => {
    const img = new Image();
    img.onload = () => {
        const w = img.naturalWidth;
        const h = img.naturalHeight;
        if (!w || !h) return;
        const gcd = (a: number, b: number): number => (b === 0 ? a : gcd(b, a % b));
        const d = gcd(w, h);
        imageRatio.value = `${w / d}:${h / d}`;
    };
    img.src = url;
};

// 上传成功回调
const handleUpload = (res: any) => {
    const { uri, name } = res.data || {};
    if (!uri) return;
    fileName.value = name;
    emit("update:modelValue", uri);
    emit("change", uri);
};

// 预览
const handlePreview = () => {
    if (!props.modelValue) return;
    showPreview.value = true;
};

// 删除
const handleDelete = () => {
    fileName.value = "";
    imageRatio.value = "";
    emit("update:modelValue", "");
    emit("delete");
};
</script>

<style scoped lang="scss">
:deep(.el-upload),
:deep(.el-upload-dragger) {
    width: 100%;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    border-radius: 16px !important;
}

:deep(.el-upload-dragger:hover) {
    background: transparent !important;
}
</style>
