<template>
    <ElDrawer
        v-model="visible"
        ref="popupRef"
        :async="true"
        size="500px"
        confirm-button-text=""
        cancel-button-text=""
        @close="handleClose">
        <template #header>
            <div class="font-bold text-xl text-black">{{ title }}</div>
        </template>
        <div class="pb-10">
            <ElForm :model="formData" :rules="formRules" ref="formRef" label-position="top">
                <ElFormItem label="机器人logo" prop="logo">
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
                                    v-if="!formData.logo">
                                    <Icon
                                        name="el-icon-CirclePlusFilled"
                                        color="var(--color-primary)"
                                        :size="18"></Icon>
                                </div>
                                <div class="flex flex-col justify-center items-center h-full w-full" v-else>
                                    <ElImage :src="formData.logo" />
                                </div>
                            </div>
                        </upload>
                    </div>
                </ElFormItem>
                <ElFormItem label="机器人名称" prop="name">
                    <ElInput v-model="formData.name" placeholder="请输入机器人名称" />
                </ElFormItem>

                <ElFormItem label="机器人角色设定" prop="description">
                    <ElInput
                        v-model="formData.description"
                        type="textarea"
                        show-word-limit
                        placeholder="点击输入您要要设定的内容"
                        :maxlength="2000"
                        :rows="6" />
                </ElFormItem>
                <ElFormItem label="您的企业背景信息" prop="company_background">
                    <ElInput
                        v-model="formData.company_background"
                        type="textarea"
                        show-word-limit
                        placeholder="点击输入您的企业背景信息，机器人将会配合该信息进行回复"
                        :maxlength="2000"
                        :rows="6" />
                </ElFormItem>
                <!-- 挂靠知识库 -->
                <ElFormItem label="挂靠知识库" prop="index_id">
                    <ElSelect
                        v-model="formData.index_id"
                        placeholder="请选择挂靠知识库"
                        clearable
                        @change="handleKnbChange">
                        <ElOption
                            v-for="item in optionsData.knbLists"
                            :key="item.id"
                            :label="item.name"
                            :value="item.index_id" />
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="AI模型" prop="model">
                    <ElSelect v-model="formData.model" placeholder="请选择AI模型" filterable>
                        <ElOption
                            v-for="item in aiModelChannel"
                            :key="item.id"
                            :label="item.name"
                            :value="item.name"></ElOption>
                    </ElSelect>
                </ElFormItem>
            </ElForm>
        </div>
        <div class="absolute bottom-0 left-0 right-0 p-3 bg-white">
            <div class="flex justify-end mt-3">
                <ElButton @click="handleClose">取消</ElButton>
                <ElButton type="primary" :loading="isLockSubmit" @click="lockSubmit"> 确定 </ElButton>
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import { addRobot, updateRobot, robotDetail } from "@/api/person_wechat";
import { knowledgeBaseLists } from "@/api/knowledge_base";
import { type FormInstance, type ElDrawer } from "element-plus";
import { useAppStore } from "@/stores/app";

const emit = defineEmits<{
    (event: "success"): void;
    (event: "close"): void;
}>();

const appStore = useAppStore();
const aiModelChannel = computed(() => appStore.getAiModelConfig.channel || []);
const getWebSiteLogo = computed(() => {
    const { shop_logo } = appStore.getWebsiteConfig || {};
    return shop_logo;
});

const popupRef = shallowRef<InstanceType<typeof ElDrawer>>();
const visible = ref(false);

const formRef = ref<FormInstance>();
const formData = reactive({
    id: "",
    logo: getWebSiteLogo.value,
    name: "",
    description: "",
    company_background: "",
    index_id: "",
    rerank_min_score: "",
    model: "",
});

const formRules = {
    logo: [{ required: true, message: "请上传机器人logo" }],
    name: [{ required: true, message: "请输入机器人名称" }],
    description: [{ required: true, message: "请输入机器人角色设定" }],
    company_background: [{ required: true, message: "请输入您的企业背景信息" }],
    question: [{ required: true, message: "请输入提问案例" }],
    answer: [{ required: true, message: "请输入回答格式" }],
};

const mode = ref("add");
const title = computed(() => {
    return mode.value === "add" ? "添加机器人" : "编辑机器人";
});

const { optionsData } = useDictOptions<{
    knbLists: any[];
}>({
    knbLists: {
        api: knowledgeBaseLists,
        params: { page_size: 25000 },
        transformData: (data) => data.lists,
    },
});

const handleKnbChange = (val: any) => {
    formData.rerank_min_score = optionsData.knbLists.find((item) => item.index_id === val)?.rerank_min_score;
};

const handleFileSuccess = (result: any) => {
    const {
        data: { uri },
    } = result;
    formData.logo = uri;
};

const open = (type: string = "add") => {
    mode.value = type;
    visible.value = true;
    formData.logo = getWebSiteLogo.value;
};

const handleSubmit = async () => {
    await formRef.value.validate();
    try {
        formData.id ? await updateRobot(formData) : await addRobot(formData);
        visible.value = false;
        emit("success");
    } catch (error) {
        feedback.msgError(error || "提交失败");
    }
};

const handleClose = () => {
    visible.value = false;
    emit("close");
};

const { lockFn: lockSubmit, isLock: isLockSubmit } = useLockFn(handleSubmit);

const getDetail = async (id: number) => {
    const data = await robotDetail({ id });
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
