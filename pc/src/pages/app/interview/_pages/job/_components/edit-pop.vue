<template>
    <ElDrawer
        v-model="show"
        ref="popupRef"
        :async="true"
        size="100%"
        confirm-button-text=""
        cancel-button-text=""
        @close="handleClose">
        <template #header>
            <div class="flex flex-col gap-2">
                <div class="flex font-bold text-xl text-black">{{ title }}</div>
                <div class="font-normal text-sm text-[#74798C]">
                    初次新增面试岗位需要保存提交后方可设置RPA自动招聘。
                </div>
            </div>
        </template>
        <div class="h-full flex flex-col w-[500px] mx-auto">
            <div class="grow min-h-0 -mx-4">
                <ElScrollbar>
                    <div class="px-4">
                        <ElForm
                            :model="formData"
                            :rules="formRules"
                            ref="formRef"
                            label-position="top"
                            :disabled="isLock">
                            <ElFormItem label="岗位名称" prop="name">
                                <ElInput
                                    v-model="formData.name"
                                    placeholder="请输入岗位名称"
                                    maxlength="30"
                                    show-word-limit />
                            </ElFormItem>
                            <ElFormItem label="岗位图片" prop="avatar">
                                <div class="">
                                    <upload
                                        class="w-full h-full"
                                        :limit="1"
                                        :show-file-list="false"
                                        show-progress
                                        @success="handleFileSuccess">
                                        <div
                                            class="h-20 w-20 bg-[#F4F4F4] p-1 rounded-lg hover:border-primary border border-dashed border-[transparent]">
                                            <div
                                                class="flex flex-col justify-center items-center h-full w-full"
                                                v-if="!formData.avatar">
                                                <Icon
                                                    name="el-icon-CirclePlusFilled"
                                                    color="var(--color-primary)"
                                                    :size="18"></Icon>
                                            </div>
                                            <div
                                                class="flex flex-col justify-center items-center h-full w-full relative"
                                                v-else>
                                                <ElImage :src="formData.avatar" />
                                                <div
                                                    class="cursor-pointer absolute -top-4 -right-4"
                                                    @click.stop="formData.avatar = ''">
                                                    <Icon
                                                        name="el-icon-CircleCloseFilled"
                                                        color="var(--color-redbook)"
                                                        :size="16"></Icon>
                                                </div>
                                            </div>
                                        </div>
                                    </upload>
                                </div>
                            </ElFormItem>
                            <ElFormItem label="招聘企业" prop="company">
                                <ElInput
                                    v-model="formData.company"
                                    placeholder="请输入招聘企业"
                                    maxlength="30"
                                    show-word-limit />
                            </ElFormItem>
                            <ElFormItem label="岗位介绍" prop="desc">
                                <ElInput
                                    v-model="formData.desc"
                                    type="textarea"
                                    placeholder="请输入您的招聘岗位介绍"
                                    show-word-limit
                                    maxlength="2000"
                                    :rows="4" />
                            </ElFormItem>

                            <ElFormItem label="面试方式" prop="type">
                                <ElRadioGroup v-model="formData.type">
                                    <ElRadio :value="1">文字</ElRadio>
                                    <ElRadio :value="2">语音</ElRadio>
                                </ElRadioGroup>
                            </ElFormItem>
                            <ElFormItem label="岗位JD" prop="jd">
                                <ElInput
                                    v-model="formData.jd"
                                    type="textarea"
                                    placeholder="请输入您的招聘的岗位JD"
                                    maxlength="2000"
                                    show-word-limit
                                    :rows="4" />
                            </ElFormItem>
                            <ElFormItem label="附加考察" prop="extra">
                                <ElInput
                                    v-model="formData.extra"
                                    type="textarea"
                                    placeholder="请输入您需要附加考察的方向"
                                    maxlength="2000"
                                    show-word-limit
                                    :rows="4" />
                            </ElFormItem>
                            <ElFormItem label="面试关注" prop="attention">
                                <div class="w-full flex flex-col gap-2">
                                    <div
                                        v-for="(item, index) in formData.attention"
                                        class="w-full flex items-center gap-2">
                                        <ElInput
                                            v-model="formData.attention[index]"
                                            type="textarea"
                                            placeholder="请输入您面试的关注点，如工作经历，家庭住址，加班程度等"
                                            maxlength="500"
                                            show-word-limit
                                            :rows="4" />
                                        <ElButton link type="danger" @click="deleteAttentionItem(index)">
                                            <Icon name="el-icon-Delete" :size="16"></Icon>
                                        </ElButton>
                                    </div>
                                    <div>
                                        <ElButton type="primary" @click="addAttentionItem">
                                            <Icon name="el-icon-CirclePlusFilled" :size="18"></Icon>
                                        </ElButton>
                                    </div>
                                </div>
                            </ElFormItem>
                        </ElForm>
                        <div class="flex gap-2 items-center mt-4">
                            <div class="flex gap-1 items-center">
                                <Icon name="el-icon-Warning" color="#FE975F"></Icon>
                                <span class="text-[#FE975F]">RPA设置</span>
                            </div>
                            <div class="text-[#9BA2AB]">初次建立岗位请在保存后前往列表设置</div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
            <div class="flex-shrink-0 flex justify-center">
                <ElButton @click="handleClose">取消</ElButton>
                <ElButton type="primary" :loading="isLock" @click="lockSubmit">确定 </ElButton>
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
    setFormData(data);
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
};

defineExpose({
    open,
    getDetail,
    setFormData,
});
</script>

<style scoped></style>
