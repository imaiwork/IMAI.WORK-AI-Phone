<template>
    <ElDrawer
        v-model="show"
        ref="popupRef"
        size="640px"
        append-to-body
        :with-header="true"
        class="custom-job-drawer"
        @close="handleClose">
        <template #header>
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-5 bg-primary rounded-full"></div>
                    <span class="font-[900] text-xl text-[#0F172A]">{{ title }}</span>
                </div>
                <div class="font-medium text-[13px] text-[#94A3B8]">完善岗位详情，AI 将根据 JD 自动为您筛选人才</div>
            </div>
        </template>

        <div class="h-full flex flex-col bg-[#FFFFFF]">
            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="px-8 py-4">
                        <ElForm
                            :model="formData"
                            :rules="formRules"
                            ref="formRef"
                            label-position="top"
                            class="custom-form"
                            :disabled="isLock">
                            <div class="section-title">基础配置</div>

                            <div class="grid grid-cols-2 gap-x-6">
                                <ElFormItem label="岗位名称" prop="name" class="col-span-2">
                                    <ElInput
                                        v-model="formData.name"
                                        class="custom-input"
                                        placeholder="例如：高级前端开发工程师"
                                        maxlength="30"
                                        clearable
                                        show-word-limit />
                                </ElFormItem>

                                <ElFormItem label="岗位图标" prop="avatar">
                                    <upload
                                        :limit="1"
                                        :show-file-list="false"
                                        show-progress
                                        @success="handleFileSuccess">
                                        <div class="upload-box group">
                                            <div
                                                v-if="!formData.avatar"
                                                class="flex flex-col items-center justify-center">
                                                <Icon name="el-icon-Plus" color="#94A3B8" :size="20" />
                                                <span class="text-[11px] text-[#94A3B8] mt-1 font-medium"
                                                    >上传LOGO</span
                                                >
                                            </div>
                                            <div v-else class="w-full h-full relative">
                                                <ElImage
                                                    :src="formData.avatar"
                                                    class="w-full h-full rounded-xl object-cover" />
                                                <div
                                                    class="absolute inset-0 bg-[rgba(0,0,0,0.4)] opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-xl"
                                                    @click.stop="formData.avatar = ''">
                                                    <Icon name="el-icon-Delete" color="#FFFFFF" :size="18" />
                                                </div>
                                            </div>
                                        </div>
                                    </upload>
                                </ElFormItem>

                                <ElFormItem label="面试方式" prop="type">
                                    <div class="flex h-[80px] items-center">
                                        <ElRadioGroup v-model="formData.type" class="custom-radio-group">
                                            <ElRadio :value="1">文本面试</ElRadio>
                                            <ElRadio :value="2">语音面试</ElRadio>
                                        </ElRadioGroup>
                                    </div>
                                </ElFormItem>
                            </div>

                            <ElFormItem label="招聘企业" prop="company">
                                <ElInput
                                    v-model="formData.company"
                                    class="custom-input"
                                    placeholder="请输入企业全称"
                                    maxlength="30"
                                    show-word-limit />
                            </ElFormItem>

                            <div class="section-title mt-8">岗位详情</div>

                            <ElFormItem label="岗位介绍" prop="desc">
                                <ElInput
                                    v-model="formData.desc"
                                    class="custom-textarea"
                                    type="textarea"
                                    :rows="4"
                                    placeholder="简述岗位职责..."
                                    maxlength="2000"
                                    show-word-limit />
                            </ElFormItem>

                            <ElFormItem label="岗位 JD" prop="jd">
                                <ElInput
                                    v-model="formData.jd"
                                    class="custom-textarea"
                                    type="textarea"
                                    :rows="5"
                                    placeholder="粘贴完整的招聘要求（JD）..."
                                    maxlength="2000"
                                    show-word-limit />
                            </ElFormItem>

                            <div class="section-title mt-8">AI 考察偏好</div>

                            <ElFormItem label="附加考察点" prop="extra">
                                <ElInput
                                    v-model="formData.extra"
                                    class="custom-textarea"
                                    type="textarea"
                                    :rows="3"
                                    placeholder="例如：性格测试、过往作品、特定行业经验等..."
                                    maxlength="2000"
                                    show-word-limit />
                            </ElFormItem>

                            <ElFormItem label="面试关注项" prop="attention">
                                <div class="space-y-3 w-full">
                                    <div
                                        v-for="(item, index) in formData.attention"
                                        :key="index"
                                        class="flex gap-3 items-start animate-fade-in">
                                        <div class="grow">
                                            <ElInput
                                                v-model="formData.attention[index]"
                                                class="custom-textarea"
                                                type="textarea"
                                                :rows="2"
                                                placeholder="如：技术深度、沟通能力、离职原因等"
                                                maxlength="500"
                                                show-word-limit />
                                        </div>
                                        <button
                                            type="button"
                                            class="mt-2 w-8 h-8 rounded-lg flex items-center justify-center bg-[#FEF2F2] hover:bg-[#FEE2E2] transition-colors"
                                            @click="deleteAttentionItem(index)">
                                            <Icon name="el-icon-Delete" color="#EF4444" :size="14" />
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        class="w-full h-10 rounded-xl border-2 border-dashed border-[#E2E8F0] hover:border-primary hover:text-primary text-[#94A3B8] flex items-center justify-center gap-2 transition-all font-medium text-xs"
                                        @click="addAttentionItem">
                                        <Icon name="el-icon-Plus" :size="14" />
                                        添加关注点
                                    </button>
                                </div>
                            </ElFormItem>
                        </ElForm>

                        <div class="my-8 p-4 rounded-2xl bg-[#FFF7ED] border border-[#FFEDD5] flex gap-3">
                            <Icon name="el-icon-MagicStick" color="#C2410C" :size="18" />
                            <div class="flex flex-col gap-1">
                                <span class="text-[13px] font-black text-[#C2410C]">温馨提示：RPA 自动招聘设置</span>
                                <p class="text-xs text-[#9A3412] font-medium leading-relaxed">
                                    初次建立岗位请在“保存”后，前往岗位列表点击“RPA设置”来配置自动投递与筛选逻辑。
                                </p>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>

            <div class="p-6 border-t border-[#F1F5F9] flex justify-end gap-3 bg-[#FFFFFF]">
                <ElButton
                    class="!h-11 !px-8 !rounded-xl !text-[#64748B] font-medium"
                    color="#F8FAFC"
                    @click="handleClose"
                    >取消</ElButton
                >
                <ElButton
                    type="primary"
                    class="!h-11 !px-10 !rounded-xl !font-black"
                    :loading="isLock"
                    @click="lockSubmit">
                    确认提交
                </ElButton>
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import { addJob, editJob, getJobDetail } from "@/api/interview";
import type { FormInstance } from "element-plus";
import { useAppStore } from "@/stores/app";

const emit = defineEmits<{
    (event: "success"): void;
    (event: "close"): void;
}>();

const appStore = useAppStore();

const getWebSiteLogo = computed(() => {
    const { shop_logo } = appStore.getWebsiteConfig || {};
    return shop_logo;
});

const show = ref(false);

const formRef = ref<FormInstance>();
const formData = reactive({
    id: "",
    avatar: "",
    name: "",
    type: 1,
    company: "",
    desc: "",
    jd: "",
    extra: "",
    attention: [],
});

const formRules = {
    name: [{ required: true, message: "请输入岗位名称" }],
    avatar: [{ required: true, message: "请上传岗位图片" }],
    company: [{ required: true, message: "请输入招聘企业" }],
    desc: [{ required: true, message: "请输入岗位介绍" }],
    jd: [{ required: true, message: "请输入岗位JD" }],
    extra: [{ required: true, message: "请输入附加考察" }],
    attention: [{ required: true, message: "请输入面试关注" }],
};

const mode = ref("add");
const title = computed(() => {
    return mode.value === "add" ? "添加面试岗位" : "编辑面试岗位";
});

const deleteAttentionItem = (index: number) => {
    formData.attention.splice(index, 1);
};

const addAttentionItem = () => {
    formData.attention.push("");
};

const handleFileSuccess = (result: any) => {
    const {
        data: { uri },
    } = result;
    formData.avatar = uri;
};

const open = (type: string = "add") => {
    mode.value = type;
    show.value = true;
    if (mode.value === "add") {
        formData.avatar = getWebSiteLogo.value;
    }
};

const handleSubmit = async () => {
    await formRef.value.validate();
    try {
        formData.id ? await editJob(formData) : await addJob(formData);
        show.value = false;
        feedback.msgSuccess(`${formData.id ? "编辑" : "新增"}成功`);
        emit("success");
    } catch (error: any) {
        feedback.msgError(error || `${formData.id ? "编辑" : "新增"}失败`);
    }
};

const handleClose = () => {
    show.value = false;
    emit("close");
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

const getDetail = async (id: number) => {
    const data = await getJobDetail({ id });
    setFormData(data, formData);
};

defineExpose({
    open,
    getDetail,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped></style>

<style scoped lang="scss">
.section-title {
    @apply flex items-center text-[13px] font-black text-[#64748B] uppercase tracking-[2px] mb-4;
    &::after {
        content: "";
        @apply flex-1 h-[1px] bg-[#F1F5F9] ml-4;
    }
}

.upload-box {
    @apply w-20 h-20 rounded-2xl border-2 border-dashed border-[#E2E8F0] bg-slate-50 flex items-center justify-center cursor-pointer transition-all hover:border-primary hover:bg-[#FFFFFF];
}

:deep(.el-form-item) {
    @apply mb-6;
    .el-form-item__label {
        @apply text-[13px] font-black text-[#0F172A] pb-1.5;
    }
}

:deep(.custom-radio-group) {
    @apply flex gap-3;
    .el-radio {
        @apply h-11 px-5 rounded-xl mr-0 bg-slate-50 border-2 border-[transparent] font-medium;
    }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
