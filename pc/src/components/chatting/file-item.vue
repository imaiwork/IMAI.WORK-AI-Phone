<template>
    <div class="relative overflow-hidden rounded-xl border border-token-border-light bg-white w-full h-full">
        <div class="p-2">
            <div class="flex flex-row items-center gap-2">
                <div class="relative h-10 w-10 shrink-0 overflow-hidden rounded-md">
                    <div
                        class="absolute inset-0 flex items-center justify-center bg-black/5 text-white"
                        v-if="item.loading">
                        <Icon name="local-icon-loading" :size="24" color="#ffffff"></Icon>
                    </div>
                    <template v-else>
                        <div class="w-full h-full" v-if="isImage(item.url)">
                            <ElImage
                                :src="item.url"
                                :preview-src-list="[item.url]"
                                class="w-full h-full"
                                fit="cover"></ElImage>
                        </div>
                        <div class="w-full h-full" v-else :style="{ backgroundColor: getFileTypeValue.theme }">
                            <Icon :name="`local-icon-${getFileTypeValue.icon}`" size="40" color="#ffffff"></Icon>
                        </div>
                    </template>
                </div>
                <div class="">
                    <div class="truncate font-semibold max-w-[100px]">
                        {{ formatFileName(item.file?.name) }}
                    </div>
                    <div class="text-gray-400 text-xs">
                        <span>{{ getFileTypeValue.fileType }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button
        class="absolute right-1 top-1 -translate-y-1/2 translate-x-1/2 rounded-full border border-token-border-heavy bg-token-main-surface-secondary p-0.5 text-token-text-primary group-hover:opacity-100 md:opacity-0"
        @click="del(index)"
        v-if="showClose">
        <span class="">
            <Icon name="local-icon-close" :size="12"></Icon>
        </span>
    </button>
</template>

<script setup lang="ts">
import { FileParams, UPLOAD_STATUS } from "@/composables/usePasteImage";
const props = withDefaults(
    defineProps<{
        item: FileParams;
        index: number;
        showClose?: boolean;
    }>(),
    {
        item: () => ({
            loading: false,
            image: "",
            file: "",
            url: "",
            status: UPLOAD_STATUS.UPLOADING,
        }),
        index: 0,
        showClose: true,
    }
);

const emit = defineEmits<{
    (event: "on-delete", value: number): void;
}>();

const del = (index: number) => {
    emit("on-delete", index);
};

const formatFileName = (name: string) => {
    if (name) {
        return name.substring(0, name.lastIndexOf("."));
    }
    return name;
};

const getFileTypeValue = computed(() => {
    const { url, status } = props.item;
    const { name } = props.item.file;
    const fileName = name.split(".").pop();
    switch (fileName) {
        case "txt":
            return { theme: "#FF5588", fileType: "文档", icon: "file_text" };
        case "xlsx":
        case "xls":
            return {
                theme: "#10A37F",
                fileType: "电子表格",
                icon: "file_xlsx",
            };
        default:
            return { theme: "#0000FF", fileType: "文件", icon: "file_doc" };
    }
});

const isImage = (file) => {
    if (file instanceof File) {
        return file.type.startsWith("image/");
    } else if (typeof file === "string") {
        return isImageUrl(file) || isBase64Image(file);
    }
    return false;
};

const isBase64Image = (str) => {
    return str.startsWith("data:image/");
};

const isImageUrl = (url) => {
    return url.match(/\.(jpeg|jpg|gif|png|bmp|svg|webp)$/i) !== null;
};
</script>

<style scoped lang="scss">
.image-container {
    @apply relative w-14 h-14;
}
</style>
