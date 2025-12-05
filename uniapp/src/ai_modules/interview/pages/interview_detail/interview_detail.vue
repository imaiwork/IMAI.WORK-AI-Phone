<template>
    <view class="h-screen flex flex-col relative bg-[#F9F9F9]" v-if="detail">
        <view class="bg z-40"></view>
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :background="{
                    background: 'transparent',
                }"
                title="岗位选择"
                title-bold>
            </u-navbar>
        </view>
        <view class="grow min-h-0 box relative z-20 px-[32rpx] mt-4 overflow-y-auto flex flex-col">
            <view class="bg-white rounded-tl-[20rpx] rounded-tr-[20rpx] grow p-[40rpx]">
                <view>
                    <image class="w-[80rpx] h-[80rpx] rounded-full" :src="detail.avatar" mode="aspectFill"></image>
                    <view class="text-[32rpx] font-bold mt-2">
                        {{ detail.name }}
                    </view>
                    <view class="text-[#150B3D] text-xs mt-1">
                        {{ detail.company }}
                    </view>
                </view>
                <view class="mt-4 flex gap-2">
                    <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#B5B6B7] flex-shrink-0 mt-2"> </view>
                    <view class="text-[#150B3D]">
                        {{ detail.desc }}
                    </view>
                </view>
                <view class="mt-[64rpx]">
                    <view class="text-[#150B3D] font-bold"> 岗位要求 </view>
                    <view class="mt-2 flex gap-2">
                        <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#B5B6B7] flex-shrink-0 mt-2"> </view>
                        <view class="text-[#150B3D]">
                            {{ detail.jd }}
                        </view>
                    </view>
                </view>
                <view class="mt-[48rpx]">
                    <view class="text-[#150B3D] font-bold"> 请添加您的简历 </view>
                    <view class="mt-4">
                        <template v-if="isUploadFile">
                            <view class="flex gap-2" @click="handleAddResume">
                                <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#B5B6B7] flex-shrink-0 mt-2"> </view>
                                <view class="text-primary text-xs">
                                    {{ formData.name || "点击此手动添加简历" }}
                                </view>
                            </view>
                            <view class="flex gap-2 mt-4">
                                <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#B5B6B7] flex-shrink-0 mt-2"> </view>
                                <view class="text-[#524B6B] text-xs"> 或上传word、pdf类型文件 </view>
                            </view>
                        </template>
                        <view v-else>
                            <view class="flex gap-2">
                                <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#B5B6B7] flex-shrink-0 mt-2"> </view>
                                <view class="text-xs"> 点击文件名称可更换文件 </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[24rpx]">
                        <view
                            class="h-[148rpx] rounded-[40rpx] bg-[#F4F6FE] border border-dashed border-primary flex flex-col justify-center items-center px-[48rpx] relative"
                            @click="handleUploadFile">
                            <template v-if="isUploadFile">
                                <view class="flex items-center justify-center gap-2">
                                    <u-icon name="/static/images/icons/upload.svg" :size="32"></u-icon>
                                    <text class="text-[#150A33] text-xs"> 点击从微信上传文件 </text>
                                </view>
                                <view class="text-[#B5B6B7] text-[20rpx] mt-2">
                                    文件大小不超过{{ MAX_FILE_SIZE }}M
                                </view>
                            </template>
                            <view v-else class="flex items-center gap-2 w-full">
                                <view>
                                    <view class="text-[#150A33] text-xs line-clamp-1">
                                        {{ fileData.name }}
                                    </view>
                                    <view class="flex gap-2 text-[#B5B6B7] text-[20rpx] mt-1">
                                        <view>
                                            {{ fileData.size }}
                                        </view>
                                        <view>
                                            {{ fileData.date }}
                                        </view>
                                    </view>
                                </view>
                                <view class="absolute top-2 right-4" @click.stop="handleDeleteFile">
                                    <u-icon name="trash" :size="24"></u-icon>
                                </view>
                            </view>
                        </view>
                        <view class="mt-4 text-center" v-if="resumeStatus == 3">
                            <view class="text-error text-xs"> 简历信息识别不完整,请手动点击简历完善 </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="py-[32rpx] mx-[100rpx]">
            <button class="submit-btn" :disabled="resumeStatus != 1" @click="handleSubmit">
                <template v-if="resumeStatus == 0"> 您还未添加简历信息 </template>
                <template v-else-if="resumeStatus == 1"> 开始面试 </template>
                <template v-else-if="resumeStatus == 3"> 简历信息不完整,请手动点击简历完善 </template>
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ChooseResult, chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";
import { getInterviewJobDetail, getResumeRecognition, saveResume } from "@/api/interview";
import { formatFileSize } from "@/utils/util";

const state = reactive({
    id: "",
});
const detail = ref<any>(null);
const formData = reactive<any>({
    name: "",
    sex: "",
    age: "",
    mobile: "",
    school: "",
    degree: "",
    work_years: "",
    work_ex: "",
    word_url: "",
    project_ex: "",
});

// 简历状态 0.未上传 1.成功 2.失败 3.信息不完整
const resumeStatus = ref<number>(0);

// 限制文件大小
const MAX_FILE_SIZE = 20;

// 文件
const fileData = reactive<Record<string, any>>({
    name: "",
    date: "",
    size: "",
    url: "",
});

// 判断是否上传文件
const isUploadFile = computed(() => {
    return !fileData.url;
});

const handleUploadFile = async () => {
    if (isUploadFile.value) {
        const result = await chooseFile({
            type: "file",
        });
        chooseFileCallback(result);
    } else {
        handleAddResume();
    }
};

// 选择文件回调
const chooseFileCallback = async (filesResult: ChooseResult) => {
    const { tempFilePaths, tempFiles } = filesResult;
    const file = tempFiles[0];
    // 判断是否大于20M
    const fileSize = file.size;
    if (fileSize > MAX_FILE_SIZE * 1024 * 1024) {
        uni.$u.toast(`文件大小不能超过${MAX_FILE_SIZE}M`);
        return;
    }
    uploadFileFn(file);
};

// 上传文件
const uploadFileFn = async (file: any) => {
    const showLoading = (title: string) => uni.showLoading({ title, mask: true });
    const showToast = (title: string, duration = 5000) => uni.showToast({ title, icon: "none", duration });

    try {
        // 上传文件
        showLoading("上传中");
        const { uri }: any = await uploadFile("file", { filePath: file.path });
        uni.hideLoading();

        // 解析简历
        showLoading("解析中");
        const data = await getResumeRecognition({
            word: uri,
            interview_job_id: state.id,
        });
        uni.hideLoading();

        // 更新表单数据
        formData.word_url = uri;
        setFormData(data);

        // 更新文件信息
        Object.assign(fileData, {
            name: file.name,
            date: uni.$u.timeFormat(file.date * 1000, "yyyy/mm/dd hh:MM"),
            size: formatFileSize(file.size),
            url: uri,
        });
        // 校验必填字段
        const hasEmptyField = Object.values(formData).some((value) => !value);
        if (hasEmptyField) {
            resumeStatus.value = 3;
            showToast("当前简历信息不完整，请点击简历信息完善");
            return;
        }

        // 保存简历
        await saveResumeFn();
    } catch (error: any) {
        uni.hideLoading();
        showToast(error || "解析失败");
    }
};

const handleDeleteFile = () => {
    fileData.url = "";
    fileData.name = "";
    fileData.date = "";
    fileData.size = "";
    resumeStatus.value = 0;
    Object.keys(formData).forEach((key) => {
        formData[key] = "";
    });
};

function showLoading(title: string) {
    return uni.showLoading({ title, mask: true });
}

function showToast(title: string, icon: UniApp.ShowToastOptions["icon"] = "none", duration = 2000) {
    return uni.showToast({ title, icon, duration });
}

const saveResumeFn = async () => {
    showLoading("保存中");
    try {
        await saveResume({ interview_job_id: state.id, ...formData });
        resumeStatus.value = 1;
        uni.hideLoading();

        showToast("保存成功");
    } catch (error: any) {
        uni.hideLoading();
        showToast(error || "保存失败");
    }
};

const handleAddResume = async () => {
    uni.$u.route({
        url: `/ai_modules/interview/pages/resume_form/resume_form`,
        params: {
            id: state.id,
            data: JSON.stringify(formData),
        },
    });
};

const handleSubmit = async () => {
    uni.$u.route({
        url: `/ai_modules/interview/pages/full_screen/full_screen`,
        params: {
            id: state.id,
        },
        type: "reLaunch",
    });
};

const getDetail = async () => {
    const data = await getInterviewJobDetail({
        id: state.id,
    });
    detail.value = data;
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
};

onLoad((options: any) => {
    state.id = options.id;
    getDetail();
    uni.$on("update-resume", async (data: any) => {
        resumeStatus.value = 1;
        setFormData(data.formData);
    });
});

onUnload(() => {
    uni.$off("update-resume");
});
</script>

<style scoped lang="scss">
.bg {
    height: 520rpx;
    top: 0;
    z-index: 1;
    left: 0;
    width: 100%;
    position: absolute;
    background: linear-gradient(135deg, rgba(208, 247, 236, 1) 0%, rgba(247, 255, 252, 1) 100%);
}

.submit-btn {
    @apply flex items-center justify-center rounded-[12rpx] h-[92rpx] after:border-none text-base bg-primary text-white;
    &[disabled] {
        @apply bg-primary-light-7 text-white;
    }
}
</style>
