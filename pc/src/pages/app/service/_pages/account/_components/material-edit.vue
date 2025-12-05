<template>
    <popup
        ref="popupRef"
        async
        width="600px"
        :title="popupTitle"
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close">
        <ElForm ref="formRef" :model="formData" :rules="formRules" label-position="top">
            <ElFormItem label="匹配方式" prop="match_type">
                <ElRadioGroup v-model="formData.match_type">
                    <ElRadio :value="0">模糊匹配</ElRadio>
                    <ElRadio :value="1">精确匹配</ElRadio>
                </ElRadioGroup>
            </ElFormItem>
            <ElFormItem label="匹配内容" prop="keyword">
                <ElInput v-model="formData.keyword" placeholder="点击输入提问案例" />
            </ElFormItem>
            <ElFormItem label="选择名片" prop="reply">
                <div class="h-[300px] w-full -mx-4" v-if="materialLists.length">
                    <ElScrollbar>
                        <div class="grid grid-cols-3 gap-4 px-4 mt-4">
                            <div
                                v-for="(item, index) in materialLists"
                                class="w-full h-[72px] border border-[#E0E0E0] rounded p-2 flex items-center gap-2 cursor-pointer relative"
                                :class="{ 'border-primary bg-primary-light-9': chooseIds.includes(item.id) }"
                                :key="index"
                                @click="handleChoose(item.id)">
                                <div class="leading-6">
                                    <div class="font-bold">{{ formatContent(item.content, "name") }}</div>
                                    <div>{{ formatContent(item.content, "code") }}</div>
                                </div>
                                <div
                                    class="absolute -top-2 -right-2 z-[1000] w-6 h-6 bg-white rounded-full"
                                    v-if="chooseIds.includes(item.id)">
                                    <Icon name="el-icon-SuccessFilled" color="var(--color-primary)" :size="24"></Icon>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
                <div v-else class="flex items-center justify-center w-full">
                    <ElEmpty description="暂无名片数据" />
                </div>
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import {
    addAccountKeyword,
    updateAccountKeyword,
    getAccountKeywordDetail,
    getMaterialList as getMaterialListApi,
} from "@/api/service";
import { type FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";

const props = defineProps({
    type: {
        type: Number,
        default: "",
    },
    appType: {
        type: String as PropType<string>,
        default: "",
    },
    account: {
        type: String as PropType<string>,
        default: "",
    },
});

const emit = defineEmits(["close", "success"]);

const route = useRoute();

const popupRef = ref<InstanceType<typeof Popup>>();

const mode = ref("add");
const popupTitle = computed(() => {
    return mode.value === "add" ? "新增关键词回复" : "编辑关键词回复";
});

const formRef = ref<FormInstance>();
const formData = reactive({
    id: "",
    match_type: 0,
    keyword: "",
    reply: [],
    account: "",
    type: "",
});

const formRules = {
    keyword: [{ required: true, message: "请输入匹配内容" }],
    reply: [{ required: true, message: "请选择名片" }],
};
const materialLists = ref<any[]>([]);
const getMaterialList = async () => {
    const { lists } = await getMaterialListApi({
        type: props.appType,
        account: props.account,
        page_size: 1000,
    });
    materialLists.value = lists;
};

const formatContent = (content: any, key: string) => {
    content = isJson(content) ? JSON.parse(content) : content;
    return content[key] || "";
};

const chooseIds = ref<any[]>([]);
const handleChoose = (id: string) => {
    if (chooseIds.value.includes(id)) {
        chooseIds.value = chooseIds.value.filter((item) => item !== id);
    } else {
        chooseIds.value.push(id);
    }
    const selectedMaterials = materialLists.value.filter((item) => chooseIds.value.includes(item.id));
    formData.reply = selectedMaterials.map((item) => {
        const content = isJson(item.content) ? JSON.parse(item.content) : item;
        return { ...content, type: props.type, id: item.id };
    });
};

const open = (type?: any) => {
    mode.value = type ? "edit" : "add";
    formData.account = props.account;
    formData.type = props.appType;
    popupRef.value?.open();
    getMaterialList();
};

const close = () => {
    emit("close");
};

const handleConfirm = async () => {
    await formRef.value.validate();
    try {
        formData.id ? await updateAccountKeyword(formData) : await addAccountKeyword(formData);
        feedback.msgSuccess(`${mode.value === "add" ? "新增" : "编辑"}成功`);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        feedback.msgError(error || `${mode.value === "add" ? "新增" : "编辑"}失败`);
    }
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const getDetail = async (id: string) => {
    const data = await getAccountKeywordDetail({ id });
    setFormData(data);
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
    chooseIds.value = formData.reply.map((item) => item.id);
};

defineExpose({
    open,
    setFormData,
    getDetail,
});
</script>

<style scoped></style>
