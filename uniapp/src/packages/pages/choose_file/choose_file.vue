<template>
    <view class="h-screen flex flex-col bg-[#F6F7F9] px-[50rpx]">
        <view class="grow flex flex-col items-center mt-[10vh]">
            <view>
                <image src="@/packages/static/images/common/file_bg.png" class="" mode="aspectFit"></image>
            </view>
            <view class="text-center text-[#6D7074]">
                {{ acceptHint }}，最多上传{{ fileLimit }}个文件，图片不超过{{ CHAT_IMAGE_MAX_SIZE }}M，其他文件不超过{{ CHAT_FILE_MAX_SIZE }}M
            </view>
            <view class="w-[80%] mt-[80rpx] flex flex-col gap-4">
                <view
                    class="h-[80rpx] flex items-center justify-center gap-2 bg-[#E4EAF8] rounded-full w-full"
                    @click="openFile('record')">
                    <u-icon name="/static/images/icons/weixin.svg" :size="32"></u-icon>
                    <text class="font-medium text-xl">从微信聊天记录选择文件</text>
                </view>
                <view
                    class="h-[80rpx] flex items-center justify-center gap-2 bg-[#E4EAF8] rounded-full w-full"
                    @click="openFile('album')"
                    v-if="sumImage > 0">
                    <text class="font-medium text-xl">从相册选择图片</text>
                </view>
                <view
                    class="h-[80rpx] flex items-center justify-center gap-2 bg-[#E4EAF8] rounded-full w-full"
                    @click="openFile('camera')"
                    v-if="sumImage > 0">
                    <text class="font-medium text-xl">拍照</text>
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
import {
    CHAT_UPLOAD_EXTS,
    CHAT_IMAGE_MAX_SIZE,
    CHAT_FILE_MAX_SIZE,
    getChatSizeLimit,
} from "@/components/file-upload/upload-rules";

const fileLimit = ref<number>(1);
const sumImage = ref<number>(0);
const fileLists = ref<any[]>([]);

/** 默认 chat 全量格式（文档+图片+视频，对齐 PC）；页面可传 accept=pdf,doc,docx,txt 按场景收窄 */
const accept = ref([...CHAT_UPLOAD_EXTS]);

/** 默认全量时按类别汇总，避免 21 个扩展名撑爆文案；收窄时逐个列出 */
const acceptHint = computed(() => {
    if (accept.value.length === CHAT_UPLOAD_EXTS.length) {
        return "支持常见文档、图片、视频格式";
    }
    return `支持${accept.value.join("、")}格式`;
});

const openFile = async (type: string) => {
    if (fileLists.value.length >= fileLimit.value) {
        uni.showToast({
            title: `您最多选择 ${fileLimit.value} 个文件`,
            icon: "none",
        });
        return;
    }
    let filesResult: ChooseResult | undefined;
    try {
        if (type === "record") {
            filesResult = await chooseFile({
                // "all"：微信聊天记录里的图片/视频消息也可选（accept 已含图片/视频格式）
                type: "all",
                extension: accept.value,
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
                sourceType: ["camera"],
            });
        }
    } catch (e: any) {
        // 用户取消选择时静默返回
        const errMsg = e?.errMsg || "";
        if (errMsg.includes("cancel")) return;
        // 相机/相册权限被拒：引导去小程序设置里开启，而不是抛英文 errMsg
        if (errMsg.includes("auth") || errMsg.includes("deny") || errMsg.includes("privacy")) {
            uni.showModal({
                title: "无法使用相机/相册",
                content: "请在小程序设置中开启相机与相册权限后重试",
                confirmText: "去设置",
                success: (res) => {
                    if (res.confirm) uni.openSetting({});
                },
            });
            return;
        }
        uni.showToast({ title: errMsg || "选择文件失败", icon: "none" });
        return;
    }
    if (!filesResult) return;
    chooseFileCallback(filesResult);
};

const chooseFileCallback = async (filesResult: ChooseResult) => {
    const isOne = Number(fileLimit.value) === 1;
    if (isOne) {
        fileLists.value = [];
    }
    const { files } = getFilesByExtname(filesResult, []);
    const currentData = [];
    const oversizeNames: string[] = [];
    for (let i = 0; i < files.length; i++) {
        if (fileLimit.value - fileLists.value.length <= 0) break;
        const fileData = normalizeFileData(files[i]);
        // 图片 ≤ 20M，其他 ≤ 150M
        const limit = getChatSizeLimit(fileData.extname);
        if (fileData.size <= limit * 1024 * 1024) {
            fileLists.value.push(fileData);
            currentData.push(fileData);
        } else {
            oversizeNames.push(`${fileData.name}(超过${limit}M)`);
        }
    }
    if (oversizeNames.length) {
        uni.showToast({
            title: `文件大小超出限制：${oversizeNames.join("、")}`,
            icon: "none",
            duration: 4000,
        });
    }
    // 没有可上传的文件时直接留在本页，不触发上传/返回
    if (!currentData.length && !fileLists.value.length) return;
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
        // 空数组直接完成，否则并发循环不会执行，loading 永远不关闭
        if (!len) {
            resolve();
            return;
        }
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
                    },
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

onLoad(({ limit, sum_image, accept: acceptParam }: any) => {
    fileLimit.value = Number(limit);
    sumImage.value = Number(sum_image);
    if (typeof acceptParam === "string" && acceptParam.trim()) {
        const next = acceptParam
            .split(",")
            .map((s) => s.trim().toLowerCase())
            .filter(Boolean);
        if (next.length) accept.value = next;
    }
});
</script>

<style scoped></style>
