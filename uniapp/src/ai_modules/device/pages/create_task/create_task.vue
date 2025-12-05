<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            :title="getTitle"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-3 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="step-item-icon" v-else> </view>
                    <text class="step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <view v-if="step === 1" class="flex flex-col h-full">
                <view class="flex items-center justify-between px-4">
                    <text class="font-bold"
                        >{{ taskType == TaskType.IMAGE ? "图组列表" : "视频素材" }}（{{
                            formData.materialLists.length
                        }}）</text
                    >
                    <view
                        v-if="taskType == TaskType.IMAGE"
                        class="px-[28rpx] py-[12rpx] bg-primary text-white rounded-[50rpx] font-bold"
                        @click="handleEditMaterial()"
                        >添加图组</view
                    >
                </view>
                <view class="grow min-h-0 mt-[38rpx]">
                    <template v-if="taskType == TaskType.IMAGE">
                        <scroll-view scroll-y class="h-full" v-if="formData.materialLists.length > 0">
                            <view class="px-4 flex flex-col gap-2">
                                <view
                                    v-for="(item, index) in formData.materialLists"
                                    :key="index"
                                    class="material-image-item">
                                    <view class="flex items-center justify-between">
                                        <view class="font-bold">
                                            {{
                                                `图组${
                                                    formData.materialLists.length < 10 ? `0${index + 1}` : index + 1
                                                }`
                                            }}
                                        </view>
                                        <view class="flex items-center gap-x-1" @click="handleEditMaterial(index)">
                                            <view class="flex items-center gap-x-[4rpx] font-bold">
                                                <text>{{ item.url.length }}</text>
                                                <text class="text-[#0000004d]">张</text>
                                            </view>
                                            <u-icon name="arrow-right" size="20" color="#00000099"></u-icon>
                                        </view>
                                    </view>
                                    <view class="mt-[18rpx] grid grid-cols-4 gap-2">
                                        <view v-for="(image, iindex) in item.url" :key="iindex" class="image-item">
                                            <image
                                                :src="image"
                                                class="w-full h-full rounded-[10rpx]"
                                                mode="aspectFill"></image>
                                        </view>
                                    </view>
                                    <view
                                        class="mt-[22rpx] flex items-center gap-x-1"
                                        @click="handleDeleteMaterial(index)">
                                        <u-icon name="trash" size="28" color="#0000004d"></u-icon>
                                        <text class="text-[#0000004d]">删除</text>
                                    </view>
                                </view>
                            </view>
                        </scroll-view>
                        <view v-else class="mt-[100rpx]">
                            <empty :size="260" text="您还没有图组哦" />
                            <view class="mt-[44rpx] flex justify-center">
                                <view
                                    class="w-[220rpx] h-[88rpx] rounded-[20rpx] border border-solid flex items-center justify-center gap-x-2"
                                    @click="handleEditMaterial()">
                                    <u-icon name="plus" size="24"></u-icon>
                                    <text class="font-bold">添加图组</text>
                                </view>
                            </view>
                        </view>
                    </template>
                    <template v-else>
                        <scroll-view scroll-y class="h-full">
                            <view class="px-4 grid grid-cols-3 gap-2">
                                <view
                                    v-if="formData.materialLists.length < videoLimit"
                                    class="bg-white rounded-[20rpx] h-[288rpx] flex flex-col items-center justify-center"
                                    @click="openVideoUploadDialog">
                                    <view
                                        class="w-[32rpx] h-[32rpx] bg-[#00000066] flex items-center justify-center rounded-full">
                                        <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                                    </view>
                                    <text class="mt-3 font-bold text-[#00000066]">添加视频</text>
                                </view>
                                <view
                                    v-for="(item, index) in formData.materialLists"
                                    :key="index"
                                    class="material-video-item">
                                    <image
                                        :src="item.url[0]"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                    <view class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                        <view
                                            class="rounded-full bg-[#ffffff33] w-[48rpx] h-[48rpx]"
                                            style="backdrop-filter: blur(5px)"
                                            @click="handlePlay(item.url)">
                                            <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                        </view>
                                    </view>
                                    <view
                                        class="absolute top-2 right-2 w-5 h-5 rounded-full flex items-center justify-center bg-[#000000cc] z-[22]"
                                        @click="handleDeleteVideo(index)">
                                        <u-icon name="close" size="20" color="#ffffff"></u-icon>
                                    </view>
                                    <div class="absolute bottom-2 w-full z-[33] flex justify-center">
                                        <view class="dh-version-name" @click="handleReplaceVideo(index)"> 替换 </view>
                                    </div>
                                </view>
                            </view>
                        </scroll-view>
                    </template>
                </view>
            </view>
            <view v-if="step === 2" class="flex flex-col h-full">
                <view class="flex items-center px-4 gap-x-2">
                    <navigator
                        url="/ai_modules/device/pages/task_copywriter/task_copywriter"
                        hover-class="none"
                        class="flex-1 flex items-center justify-center gap-x-2 bg-primary h-[100rpx] rounded-[10rpx]">
                        <image
                            src="@/ai_modules/device/static/images/common/add_circle_white.png"
                            class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">添加文案...</text>
                    </navigator>
                    <navigator
                        url="/ai_modules/device/pages/task_ai_copywriter/task_ai_copywriter"
                        hover-class="none"
                        class="w-[230rpx] h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">AI生成</text>
                    </navigator>
                </view>
                <view class="px-4 font-bold text-[30rpx] mt-[60rpx]"
                    >文案列表（{{ formData.copywriterList.length }}）</view
                >
                <view class="grow min-h-0 mt-[24rpx]">
                    <scroll-view scroll-y class="h-full" v-if="formData.copywriterList.length > 0">
                        <view class="px-4 flex flex-col gap-y-[30rpx]">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="copywriter-item"
                                @click="handleEditCopywriter(index)">
                                <view class="text-[30rpx] font-bold"> {{ item.title }} </view>
                                <view class="font-bold mt-[26rpx]">
                                    {{ item.content }}
                                </view>
                                <view class="mt-[50rpx] flex items-center flex-wrap gap-2">
                                    <view
                                        v-for="(tag, tindex) in item.topic"
                                        :key="tindex"
                                        class="text-xs text-[#0000004d]">
                                        #{{ tag }}
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-5 h-5 flex items-center justify-center bg-[#0000004d] rounded-full"
                                    @click.stop="handleDeleteCopywriter(index)">
                                    <u-icon name="close" size="20" color="#ffffff"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="mt-[100rpx]">
                        <empty :size="260" text="您还没有文案哦" />
                    </view>
                </view>
            </view>
            <view v-if="step === 3" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[100rpx]">
                        <bast-setting v-model="formData" ref="baseFormRef" />
                        <view v-if="taskErrorMsg" class="mt-5">
                            <view>任务冲突</view>
                            <view class="text-[#FF2442] mt-[20rpx] text-xs">
                                {{ taskErrorMsg }}
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.materialLists.length > 0 ? 'bg-primary' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ formData.materialLists.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view v-else>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="handleStep(step, 'prev')">
                            上一步
                        </view>
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-primary' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-primary text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>
    <confirm-dialog
        v-model="confirmDialogVisible"
        confirm-text="删除"
        center
        content="是否确定删除图组？"
        @confirm="handleDeleteMaterialConfirm" />
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        confirm-text="确定"
        center
        content="创建成功，回到任务列表？"
        :show-close="false"
        @confirm="handleCreateTaskSuccess" />
    <confirm-dialog
        v-model="showVideoUploadTip"
        confirm-text="去上传"
        :content="getVideoTipsContent"
        @confirm="handleVideoUpload" />
    <upload-progress v-model="showVideoUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview>
</template>

<script setup lang="ts">
import { addMatrixTask, publishDeviceTask } from "@/api/device";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { uploadFile } from "@/api/app";
import { chooseFile } from "@/components/file-upload/choose-file";
import BastSetting from "@/ai_modules/device/components/bast-setting/bast-setting.vue";

enum TaskType {
    IMAGE = 2,
    VIDEO = 1,
}

// 创作任务类型
const taskType = ref<TaskType>(TaskType.VIDEO);

// 步骤
const steps = ref([
    { step: 1, title: "选择素材" },
    { step: 2, title: "填写文案" },
    { step: 3, title: "设定时间" },
]);
const step = ref(1);

const formData = reactive<{
    name: string;
    introduction: string;
    copywriterList: {
        title: string;
        content: string;
        topic: string[];
    }[];
    materialLists: Array<{
        url: any[];
    }>;
    time_config: any[];
    accounts: any[];
    publish_frep: number;
    custom_date: string[];
    task_frep: number;
}>({
    name: ``,
    introduction: "",
    // 文案列表
    copywriterList: [],
    // 素材列表
    materialLists: [],
    time_config: [
        {
            start_time: "09:00",
            end_time: "09:30",
        },
        {
            start_time: "09:30",
            end_time: "10:30",
        },
    ],
    accounts: [],
    publish_frep: 2,
    custom_date: [],
    task_frep: 1,
});

const baseFormRef = shallowRef<InstanceType<typeof BastSetting>>();

// 删除索引
const deleteImgIndex = ref<number>(-1);
// 编辑素材索引
const editImgIndex = ref<number>(-1);

const confirmDialogVisible = ref<boolean>(false);
// 视频上传提示
const showVideoUploadTip = ref<boolean>(false);
// 视频上传进度
const showVideoUploadProgress = ref<boolean>(false);
// 视频上传限制
const videoLimit = 99;
// 视频上传大小
const videoSize = 100;
// 视频上传格式
const videoFormat = ["mp4", "mov"];
// 视频上传是否初次打开
const isVideoInitialOpen = ref<boolean>(true);
// 替换视频索引
const replaceVideoIndex = ref<number>(-1);
// 视频预览
const playItem = reactive<any>({
    url: "",
    pic: "",
});
const showVideoPreview = ref(false);
const uploadMaterialList = ref<any[]>([]);

//错误提示
const taskErrorMsg = ref<string>("");

// 编辑文案索引
const editCopywriterIndex = ref<number>(-1);
// 创建任务成功提示
const showCreateTaskSuccessDialog = ref<boolean>(false);

const getTitle = computed(() => {
    return taskType.value == TaskType.IMAGE ? "发布图文" : "发布视频";
});

const getVideoTipsContent = computed<string>(() => {
    return `
        <div>· 视频素材支持：${videoFormat.join("、")}格式，${videoSize}M以内</div>
    <div class="mt-2">· 最多可传${videoLimit}个视频</div>
    <div class="mt-2">· 不符合条件的视频会被自动删除</div>
    `;
});

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.materialLists.length > 0;
        case 2:
            return formData.copywriterList.length > 0;
        case 3:
            return true;
        default:
            return false;
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 点击“上一步”
    if (type === "prev") {
        step.value--;
        return;
    }

    // 点击“下一步”
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: string } = {
                1: "请至少选择一个图组",
                2: "请至少添加一条文案",
            };
            uni.$u.toast(messages[step.value] || "请完成当前步骤");
        }
        return;
    }

    // 直接点击步骤条进行跳转
    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const handleEditMaterial = (index?: number) => {
    editImgIndex.value = index ?? -1;
    uni.$u.route({
        url: "/ai_modules/device/pages/task_img_group/task_img_group",
        params: {
            imgs: editImgIndex.value !== -1 ? JSON.stringify(formData.materialLists[editImgIndex.value].url) : "",
        },
    });
};

const handleDeleteMaterial = (index: number) => {
    deleteImgIndex.value = index;
    confirmDialogVisible.value = true;
};

const handleDeleteMaterialConfirm = () => {
    formData.materialLists.splice(deleteImgIndex.value, 1);
    confirmDialogVisible.value = false;
    deleteImgIndex.value = -1;
};

const openVideoUploadDialog = () => {
    if (!isVideoInitialOpen.value) {
        handleVideoUpload();
        return;
    }
    isVideoInitialOpen.value = false;
    showVideoUploadTip.value = true;
};

const handleVideoUpload = async () => {
    uploadMaterialList.value = [];
    try {
        const { tempFiles } = await chooseFile({
            type: "video",
            count: videoLimit,
            extension: videoFormat,
        });
        const fileList = [];
        for (const file of tempFiles) {
            if (file.size > videoSize * 1024 * 1024) {
                uni.$u.toast(`视频大小不能超过${videoSize}M`);
                continue;
            }
            fileList.push(file);
        }
        if (fileList.length === 0) {
            uni.$u.toast(`所选视频素材均不符合条件，请重新上传`);
            return;
        }
        uploadMaterialList.value = fileList;
        showVideoUploadProgress.value = true;
        for (const item of uploadMaterialList.value) {
            const coverRes: any = await uploadFile("image", { filePath: item.thumbTempFilePath });
            const fileRes: any = await uploadFile("video", { filePath: item.tempFilePath }, (progress) =>
                progressCallback(progress, item)
            );
            if (replaceVideoIndex.value !== -1) {
                formData.materialLists[replaceVideoIndex.value].url = [coverRes.uri, fileRes.uri];
                replaceVideoIndex.value = -1;
            } else {
                formData.materialLists.push({
                    url: [coverRes.uri, fileRes.uri],
                });
            }
        }

        if (uploadMaterialList.value.every((item) => item.progress === 100)) {
            showVideoUploadProgress.value = false;
        }
    } catch (error) {
        uni.$u.toast(error);
        uploadMaterialList.value = [];
        showVideoUploadProgress.value = false;
    }
};

const progressCallback = (progress: number, options: { tempFilePath: string }) => {
    const targetIndex = uploadMaterialList.value.findIndex(
        (material) => material.tempFilePath === options.tempFilePath
    );
    if (targetIndex !== -1) {
        uploadMaterialList.value[targetIndex] = {
            ...uploadMaterialList.value[targetIndex],
            progress: progress,
        };
    }
};

const handlePlay = (item: any) => {
    playItem.pic = item[0];
    playItem.url = item[1];
    showVideoPreview.value = true;
};

const handleDeleteVideo = (index: number) => {
    formData.materialLists.splice(index, 1);
};

const handleReplaceVideo = (index: number) => {
    replaceVideoIndex.value = index;
    handleVideoUpload();
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

const handleEditCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    uni.$u.route({
        url: "/ai_modules/device/pages/task_copywriter/task_copywriter",
        params: {
            copywriter: JSON.stringify(formData.copywriterList[index]),
        },
    });
};

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
        type: "reLaunch",
    });
    showCreateTaskSuccessDialog.value = false;
};

const handleCreateTask = async () => {
    if (baseFormRef.value?.validateForm()) {
        uni.$u.toast("时间配置存在冲突");
        return;
    }
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    if (formData.accounts.length === 0) {
        uni.$u.toast("请选择发布账号");
        uni.$u.route({
            url: "/ai_modules/device/pages/account_choose/account_choose",
        });
        return;
    }

    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        const { id } = await addMatrixTask({
            name: formData.name,
            media_type: taskType.value,
            media_url: formData.materialLists,
            copywriting: formData.copywriterList,
        });
        await publishDeviceTask({
            name: formData.name,
            matrix_media_setting_id: id,
            time_config: formData.time_config.map((item) => `${item.start_time}-${item.end_time}`),
            accounts: formData.accounts,
            publish_frep: formData.publish_frep,
            media_type: taskType.value,
            task_type: 3,
            scene: 2,
            data_type: 0,
        });
        showCreateTaskSuccessDialog.value = true;
        uni.hideLoading();
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

onLoad((options: any) => {
    if (options.type) {
        taskType.value = options.type as TaskType;
        const taskNamePrefix = taskType.value == TaskType.IMAGE ? "图文" : "视频";
        formData.name = `${taskNamePrefix}矩阵任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`;
    }
    uni.$on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CHOOSE_IMG) {
            if (editImgIndex.value !== -1) {
                if (data.length === 0) {
                    formData.materialLists.splice(editImgIndex.value, 1);
                    return;
                }

                formData.materialLists[editImgIndex.value].url = data;
            } else {
                formData.materialLists.push({
                    url: data,
                });
            }
        }
        if (type === ListenerTypeEnum.TASK_COPYWRITER || type === ListenerTypeEnum.TASK_AI_COPYWRITER) {
            if (data.length === 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
            } else {
                formData.copywriterList = formData.copywriterList.concat(data);
            }
        }
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) return;

            formData.accounts = data.map((item: any) => ({ account: item.account, type: item.type, id: item.id }));
        }
    });
});

onUnload(() => {
    uni.$off("confirm");
});
</script>

<style scoped lang="scss">
.step-item {
    @apply flex flex-col items-center justify-center relative;
    &.active {
        .step-item-icon {
            @apply shadow-[0_0_0_2rpx_rgba(0,101,251,0.3)]  flex items-center justify-center;
            &::before {
                content: "";
                @apply w-[60%] h-[60%] bg-primary rounded-full;
            }
        }
        .step-item-title {
            @apply text-[#00000099];
        }
    }
    &-success-icon {
        @apply bg-[#0065fb4d] rounded-full w-[28rpx] h-[28rpx] flex items-center justify-center;
    }
    &-icon {
        @apply w-[28rpx] h-[28rpx] rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.1)];
    }
    &-title {
        @apply mt-[20rpx] text-[rgba(0,0,0,0.2)] font-bold text-xs;
    }
    &-line {
        @apply absolute right-[-18%] top-[15rpx] w-[40%] border-[0] border-b border-dashed border-[#D1D6D4];
    }
}

.material-image-item {
    @apply rounded-[20rpx] bg-white p-[28rpx] relative;
    .image-item {
        @apply h-[146rpx] rounded-[10rpx];
    }
}

.material-video-item {
    @apply rounded-[20rpx] h-[288rpx] relative;
}
.copywriter-item {
    @apply rounded-[20rpx] bg-white p-[38rpx] relative;
}
.prompt-length-item {
    @apply flex items-center justify-center bg-[#F7F8FC] w-[114rpx] h-[72rpx] text-[#7C7E80] relative rounded-[16rpx];
    &.active {
        @apply font-bold text-black;
        &::before {
            @apply absolute top-[-4rpx] left-[-4rpx] w-[100%] h-[100%]  p-[4rpx] rounded-[16rpx] content-[''];
            background: conic-gradient(#47d59f, #37cced);
            -webkit-mask: linear-gradient(#47d59f 0 100%) content-box, linear-gradient(#37cced 0 100%);
            -webkit-mask-composite: xor;
        }
    }
}
.change-material-btn {
    @apply text-white text-[22rpx] mt-8 border border-[#ffffff1a] shadow-[0_0_0_1px_rgba(0,0,0,0.24)] rounded-[50rpx] w-full h-[88rpx] flex items-center justify-center;
}
</style>
