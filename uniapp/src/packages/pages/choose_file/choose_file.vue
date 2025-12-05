<template>
    <view class="h-screen flex flex-col bg-[#F6F7F9] px-[50rpx]">
        <view class="grow flex flex-col items-center mt-[10vh]">
            <view>
                <image src="@/packages/static/images/common/file_bg.png" class="" mode="aspectFit"></image>
            </view>
            <view class="text-center text-[#6D7074]">
                支持{{ accept.join(",") }}格式，最多上传{{ fileLimit }}个文件，每个文件不超过20M
            </view>
            <view class="w-[80%] mt-[80rpx] flex flex-col gap-4">
                <view
                    class="h-[80rpx] flex items-center justify-center gap-2 bg-[#E4EAF8] rounded-full w-full"
                    @click="openFile('record')">
                    <u-icon name="/static/images/icons/weixin.svg" :size="32"></u-icon>
                    <text class="font-bold text-xl">从微信聊天记录选择文件</text>
                </view>
                <view
                    class="h-[80rpx] flex items-center justify-center gap-2 bg-[#E4EAF8] rounded-full w-full"
                    @click="openFile('album')"
                    v-if="sumImage > 0">
                    <text class="font-bold text-xl">从相册选择图片</text>
                </view>
                <view
                    class="h-[80rpx] flex items-center justify-center gap-2 bg-[#E4EAF8] rounded-full w-full"
                    @click="openFile('camera')"
                    v-if="sumImage > 0">
                    <text class="font-bold text-xl">拍照</text>
                </view>
            </view>
        </view>
        <view class="mb-[80rpx] flex items-center justify-center">
            <view
                class="w-[60rpx] h-[60rpx] flex items-center justify-center rounded-full bg-[#E9EBEC]"
                @click="close()">
                <u-icon name="close" :size="24" color="#8B8B96"></u-icon>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { uploadFile } from "@/api/app";
import {
    ChooseResult,
    FileData,
    chooseFile,
    getFileName,
    getFilesByExtname,
    normalizeFileData,
} from "@/components/file-upload/choose-file";

const fileLimit = ref<number>(1);
const sumImage = ref<number>(0);
const fileLists = ref<any[]>([]);

const accept = ["html", "xml", "doc", "docx", "txt", "pdf", "csv", "xlsx"];

const openFile = async (type: string) => {
    if (fileLists.value.length >= fileLimit.value) {
        uni.showToast({
            title: `您最多选择 ${fileLimit.value} 个文件`,
            icon: "none",
        });
        return;
    }
    let filesResult: any = {};
    if (type === "record") {
        filesResult = await chooseFile({
            type: "file",
            extension: accept,
            count: fileLimit.value - fileLists.value.length,
        });
    } else if (type === "album") {
        filesResult = await chooseFile({
            type: "image",
            count: 1,
            sizeType: ["original", "compressed"],
            sourceType: ["album"],
        });
    } else if (type === "camera") {
        filesResult = await chooseFile({
            type: "image",
            count: 1,
            sizeType: ["original", "compressed"],
            sourceType: ["album"],
        });
    }
    chooseFileCallback(filesResult);
};

const chooseFileCallback = async (filesResult: ChooseResult) => {
    const isOne = Number(fileLimit.value) === 1;
    if (isOne) {
        fileLists.value = [];
    }
    const { files } = getFilesByExtname(filesResult, []);
    const currentData = [];
    for (let i = 0; i < files.length; i++) {
        if (fileLimit.value - fileLists.value.length <= 0) break;
        const fileData = normalizeFileData(files[i]);
        // @ts-ignore
        if (fileData.size < 20 * 1024 * 1024) {
            fileLists.value.push(fileData);
            currentData.push(fileData);
        }
    }
    await upload(currentData);
    uni.$emit("chooseFile", fileLists.value);
    uni.navigateBack();
};

//上传，并处理并发问题
const upload = (files: FileData[]): Promise<void> => {
    const len = files.length;
    let index = 0;
    let count = 0;
    return new Promise((resolve) => {
        uni.showLoading({
            title: `上传中,请稍后`,
            mask: true,
        });
        const run = async () => {
            const cur = index++;
            const fileItem = files[cur];
            const currentIndex = fileLists.value.findIndex((item) => item.path === fileItem.path);

            try {
                const { uri, id }: any = await uploadFile(
                    "file",
                    {
                        filePath: fileItem.path,
                    },
                    (progress: number) => {
                        fileLists.value[currentIndex].progress = progress;
                    }
                );
                fileLists.value[currentIndex].status = "success";
                fileLists.value[currentIndex].url = uri;
                fileLists.value[currentIndex].id = id;
            } catch (error) {
                fileLists.value[currentIndex].errMsg = error as string;
                fileLists.value[currentIndex].status = "error";
            }
            count++;
            if (count === len) {
                uni.hideLoading();
                resolve();
                return;
            }
            if (index < len) {
                run();
            }
        };
        for (let i = 0; i < Math.min(len, 2); i++) {
            run();
        }
    });
};

const close = () => {
    uni.navigateBack();
};

onLoad(({ limit, sum_image }: any) => {
    fileLimit.value = Number(limit);
    sumImage.value = Number(sum_image);
});
</script>

<style scoped></style>
