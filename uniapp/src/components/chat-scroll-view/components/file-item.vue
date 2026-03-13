<template>
    <view class="relative rounded-xl bg-[#F8F8F8] w-full h-full">
        <view v-if="isImage(item.url)" class="w-full h-full flex items-center justify-center">
            <view class="h-full w-full min-w-[86rpx] min-h-[86rpx] p-1.5" @click="previewImage(item.url)">
                <slot name="image" v-if="$slots.image" :url="item.url"></slot>
                <image v-else class="w-full h-full" :src="item.url" mode="aspectFill"></image>
            </view>
        </view>
        <view class="p-2 min-w-40" v-else>
            <view class="flex flex-row items-center gap-2">
                <view
                    class="relative h-7 w-7 flex items-center justify-center shrink-0 overflow-hidden rounded-md"
                    :style="{ backgroundColor: getFileTypeValue.theme }">
                    <image :src="getFileTypeValue.icon" class="w-[50rpx] h-[50rpx]" color="#ffffff"></image>
                </view>
                <view class="max-w-[200rpx]">
                    <view class="text-sm overflow-hidden whitespace-nowrap text-ellipsis">
                        {{ item.name }}
                    </view>
                    <view class="text-gray-400 text-[20rpx]">
                        {{ getFileTypeValue.fileType }}
                    </view>
                </view>
            </view>
        </view>
        <view class="absolute right-[-6rpx] top-[-6rpx] z-10" @click="del(index)" v-if="showDel">
            <view class="bg-[#0000004C] w-[24rpx] h-[24rpx] rounded-full flex items-center justify-center">
                <u-icon name="close" :size="12" color="#ffffff"></u-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import IconFileText from "@/static/images/icons/file_text.svg";
import IconFileDoc from "@/static/images/icons/file_doc.svg";
import IconFileXlsx from "@/static/images/icons/file_xlsx.svg";
import { isImageUrl } from "@/utils/util";

const props = withDefaults(
    defineProps<{
        item: any;
        index: number;
        showDel?: boolean;
    }>(),
    {
        item: () => ({
            image: "",
            url: "",
        }),
        index: 0,
        showDel: true,
    }
);

const emit = defineEmits<{
    (event: "on-delete", value: number): void;
}>();

const del = (index: number) => {
    emit("on-delete", index);
};

const getFileTypeValue = computed(() => {
    const { name } = props.item;
    const fileName = name.split(".").pop();
    switch (fileName) {
        case "txt":
            return { theme: "#FF5588", fileType: "文档", icon: IconFileText };
        case "xlsx":
        case "xls":
            return {
                theme: "#10A37F",
                fileType: "电子表格",
                icon: IconFileXlsx,
            };
        default:
            return { theme: "#0000FF", fileType: "文件", icon: IconFileDoc };
    }
});

const isImage = (file: any) => {
    return isImageUrl(file) || isBase64Image(file);
};

const isBase64Image = (str: string) => {
    return str.startsWith("data:image/");
};

const previewImage = (url: string) => {
    uni.previewImage({
        urls: [url],
    });
};
</script>

<style scoped></style>
