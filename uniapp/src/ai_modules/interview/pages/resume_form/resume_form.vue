<template>
    <view class="h-screen flex flex-col">
        <view class="grow min-h-0 overflow-y-auto p-4">
            <view class="text-[32rpx] text-[#150B3D] font-bold"> 请填写您的简历信息 </view>
            <view class="mt-4 flex flex-col gap-4">
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 姓名 </text>
                    </view>
                    <view class="input-wrap">
                        <input
                            v-model="formData.name"
                            class="text-xs"
                            placeholder="请输入您的姓名"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
                <view class="flex gap-4">
                    <view class="flex-1">
                        <view class="flex items-center gap-2">
                            <text class="text-[#FA3C55] mt-1">*</text>
                            <text class="text-[#150B3D] text-xs"> 性别 </text>
                        </view>
                        <view class="input-wrap">
                            <data-select
                                class="w-full"
                                v-model="formData.sex"
                                :localdata="[
                                    { value: 1, text: '男' },
                                    { value: 2, text: '女' },
                                ]"
                                placeholder="请选择您的性别"></data-select>
                        </view>
                    </view>
                    <view class="flex-1">
                        <view class="flex items-center gap-2">
                            <text class="text-[#FA3C55] mt-1">*</text>
                            <text class="text-[#150B3D] text-xs"> 年龄 </text>
                        </view>
                        <view class="input-wrap">
                            <input
                                v-model="formData.age"
                                class="text-xs"
                                type="number"
                                placeholder="请输入您的年龄"
                                placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                        </view>
                    </view>
                </view>
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 联系方式 </text>
                    </view>
                    <view class="input-wrap">
                        <input
                            v-model="formData.mobile"
                            class="text-xs"
                            type="tel"
                            placeholder="请输入您的联系方式"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 毕业院校 </text>
                    </view>
                    <view class="input-wrap">
                        <input
                            v-model="formData.school"
                            class="text-xs"
                            placeholder="请输入您的毕业院校"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 学历 </text>
                    </view>
                    <view class="input-wrap">
                        <input
                            v-model="formData.degree"
                            class="text-xs"
                            placeholder="请输入您的学历"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 工作年限 </text>
                    </view>
                    <view class="input-wrap">
                        <input
                            v-model="formData.work_years"
                            class="text-xs"
                            type="number"
                            placeholder="请输入您的工作年限"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 工作经历 </text>
                    </view>
                    <view class="input-wrap">
                        <textarea
                            v-model="formData.work_ex"
                            class="text-xs w-full"
                            :maxlength="-1"
                            placeholder="请输入您的工作经历"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
                <view class="">
                    <view class="flex items-center gap-2">
                        <text class="text-[#FA3C55] mt-1">*</text>
                        <text class="text-[#150B3D] text-xs"> 项目经历 </text>
                    </view>
                    <view class="input-wrap">
                        <textarea
                            v-model="formData.project_ex"
                            class="text-xs w-full"
                            :maxlength="-1"
                            placeholder="请输入您的项目经历"
                            placeholder-style="color: #AAA6B9;font-size: 24rpx" />
                    </view>
                </view>
            </view>
        </view>
        <view class="p-4 flex justify-between gap-4">
            <button
                class="bg-white rounded-lg after:border-none h-[86rpx] flex-1 font-bold text-base flex items-center justify-center"
                @click="handleReset">
                重置简历
            </button>
            <button
                class="bg-primary text-white rounded-lg h-[86rpx] flex-1 font-bold text-base flex items-center justify-center"
                @click="handleSave">
                保存
            </button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { saveResume } from "@/api/interview";
import { setFormData } from "@/utils/util";

const state = reactive({
    id: "",
});

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

const formRules: any = {
    name: {
        required: true,
        message: "请输入您的姓名",
    },
    sex: {
        required: true,
        message: "请选择您的性别",
    },
    age: {
        required: true,
        message: "请输入您的年龄",
    },
    mobile: {
        required: true,
        message: "请输入您的联系方式",
    },
    school: {
        required: true,
        message: "请输入您的毕业院校",
    },
    degree: {
        required: true,
        message: "请输入您的学历",
    },
    work_years: {
        required: true,
        message: "请输入您的工作年限",
    },
    work_ex: {
        required: true,
        message: "请输入您的工作经历",
    },
    project_ex: {
        required: true,
        message: "请输入您的项目经历",
    },
};
const handleReset = () => {
    Object.keys(formData).forEach((key) => {
        // @ts-ignore
        formData[key] = "";
    });
};
const handleSave = async () => {
    const invalidField = Object.keys(formData).find((key) => {
        const isRequired = formRules[key]?.required;
        const isEmpty = !formData[key];
        return isRequired && isEmpty;
    });

    if (invalidField) {
        uni.showToast({
            title: formRules[invalidField].message,
            icon: "none",
        });
        return;
    }
    try {
        await saveResume({
            interview_job_id: state.id,
            ...formData,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        setTimeout(() => {
            uni.$emit("update-resume", {
                formData,
            });
            uni.navigateBack();
        }, 500);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

onLoad((options: any) => {
    state.id = options.id;
    if (options.data) {
        setFormData(JSON.parse(options.data), formData);
    }
});
</script>

<style scoped lang="scss">
.input-wrap {
    @apply mt-2 bg-white rounded-lg px-4 py-3;
    box-shadow: 0rpx 8rpx 124rpx rgba(153, 171, 198, 0.18);
}
:deep(.uni-select) {
    border: none;
    height: 46rpx;
    .uni-select__input-text {
        font-size: 24rpx;
        color: #aaa6b9;
    }
    .uni-select__input-box {
        font-size: 24rpx;
    }
}
</style>
