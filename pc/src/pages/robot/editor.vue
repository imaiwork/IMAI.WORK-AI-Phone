<template>
    <div class="flex h-screen w-full flex-col items-center">
        <div
            class="relative flex h-14 w-full items-center justify-between gap-2 border-b border-token-border-medium px-3 flex-shrink-0">
            <div class="flex items-center gap-2">
                <div
                    class="cursor-pointer leading-[0] p-2 hover:bg-token-sidebar-surface-secondary rounded-md"
                    @click="$router.back()">
                    <Icon name="el-icon-ArrowLeftBold" :size="20"></Icon>
                </div>
                <div class="flex items-center gap-2">
                    <ElAvatar :size="32" :src="formData.logo" v-if="formData.logo" />
                    <div class="w-8 h-8" v-else>
                        <default-icon></default-icon>
                    </div>
                    <div>
                        <div class="text-base">
                            {{ formData.name || "机器人名称" }}
                        </div>
                        <div class="text-sm text-[#b4b4b4]">
                            {{ formData.description || "机器人简介" }}
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <ElButton
                    type="primary"
                    round
                    :disabled="!isEdit"
                    :loading="editLoading"
                    class="flex w-full items-center justify-center gap-1.5"
                    @click="handleEdit('handle')">
                    {{ isEdit ? "更新" : "创建" }}
                </ElButton>
            </div>
        </div>
        <div class="relative flex w-full grow flex-col lg:flex-row overflow-hidden">
            <div class="w-full flex-1 lg:flex-auto lg:w-1/2 justify-center bg-white min-h-0">
                <div class="h-full grow">
                    <ElScrollbar>
                        <div class="flex h-full flex-col px-2 pt-2">
                            <div class="flex w-full items-center justify-center gap-4">
                                <upload
                                    :limit="1"
                                    :show-file-list="false"
                                    :multiple="false"
                                    @on-progress="handleAvatarProgress"
                                    @success="handleAvatar">
                                    <button class="h-20 w-20 mt-10 relative">
                                        <div v-if="formData.logo" class="absolute w-full h-full top-0">
                                            <img :src="formData.logo" class="rounded-full w-full h-full" />
                                        </div>
                                        <div
                                            class="flex h-full w-full items-center justify-center rounded-full border-2 border-dashed border-token-border-medium"
                                            v-else>
                                            <Icon name="el-icon-Plus" size="24"></Icon>
                                        </div>
                                        <div
                                            v-if="avatarLoading"
                                            class="absolute w-full h-full flex items-center justify-center top-0 rounded-full z-20 bg-primary-light-1">
                                            <Icon name="local-icon-loading2" custom-class="animate-spin"></Icon>
                                        </div>
                                    </button>
                                </upload>
                            </div>
                            <div class="px-2">
                                <ElForm :model="formData" label-position="top">
                                    <ElFormItem label="名称" prop="name">
                                        <ElInput v-model="formData.name" placeholder="为您的机器人命名"></ElInput>
                                    </ElFormItem>
                                    <ElFormItem label="简介" prop="description">
                                        <ElInput
                                            v-model="formData.description"
                                            placeholder="添加有关于此机器人的功能简短描述"></ElInput>
                                    </ElFormItem>
                                    <ElFormItem label="指令" prop="instructions">
                                        <ElInput
                                            v-model="formData.instructions"
                                            type="textarea"
                                            resize="none"
                                            :rows="8"
                                            maxlength="2000"
                                            placeholder="此机器人能做些什么?它的行为是怎么样的？它应该避免些什么?"></ElInput>
                                    </ElFormItem>
                                    <ElFormItem label="对话开场白">
                                        <div class="w-full flex flex-col gap-y-1">
                                            <div
                                                class="w-full"
                                                v-for="(item, index) in formData.preliminary_ask"
                                                :key="index">
                                                <ElInput
                                                    v-model="item.value"
                                                    placeholder=""
                                                    maxlength="50"
                                                    @input="inputDol($event, index)">
                                                    <template #append>
                                                        <div @click="delDol(index)">
                                                            <Icon name="el-icon-Close"></Icon>
                                                        </div>
                                                    </template>
                                                </ElInput>
                                            </div>
                                        </div>
                                    </ElFormItem>
                                    <ElFormItem label="关联知识库">
                                        <ElSelect
                                            v-model="formData.vector_file_id"
                                            placeholder="请选择需要关联的知识库">
                                            <ElOption
                                                v-for="(item, index) in optionsData.knowledgeBaseList"
                                                :label="item.name"
                                                :value="`${item.id}`"></ElOption>
                                        </ElSelect>
                                    </ElFormItem>
                                    <div class="grid grid-cols-3 gap-x-8">
                                        <div class="relative">
                                            <div class="absolute right-0 top-1">
                                                <ElTag size="small">
                                                    {{ formData.template_info.ST }}
                                                </ElTag>
                                            </div>
                                            <ElFormItem label="采样温度">
                                                <ElSlider
                                                    v-model="formData.template_info.ST"
                                                    :step="0.01"
                                                    size="small"
                                                    :max="2"></ElSlider>
                                            </ElFormItem>
                                        </div>
                                        <div class="relative">
                                            <div class="absolute right-0 top-1">
                                                <ElTag size="small">
                                                    {{ formData.template_info.NT }}
                                                </ElTag>
                                            </div>
                                            <ElFormItem label="核取温度">
                                                <ElSlider
                                                    v-model="formData.template_info.NT"
                                                    :step="0.01"
                                                    size="small"
                                                    :max="2"></ElSlider>
                                            </ElFormItem>
                                        </div>
                                        <div class="relative">
                                            <div class="absolute right-0 top-1">
                                                <ElTag size="small">
                                                    {{ formData.template_info.limit }}
                                                </ElTag>
                                            </div>
                                            <ElFormItem label="生成条数" prop="name">
                                                <ElSlider
                                                    v-model="formData.template_info.limit"
                                                    :max="5"
                                                    size="small"></ElSlider>
                                            </ElFormItem>
                                        </div>
                                    </div>
                                    <ElFormItem label="填写表单">
                                        <div class="w-full">
                                            <div class="flex justify-end mb-2">
                                                <ElButton type="primary" size="small" @click="handleOpenForm()"
                                                    >添加</ElButton
                                                >
                                            </div>
                                            <ElTable
                                                :data="formData.template_info.form"
                                                size="small"
                                                style="height: 200px"
                                                empty-text="暂无表单数据"
                                                v-draggable="draggableOptions">
                                                <ElTableColumn width="50">
                                                    <template #default>
                                                        <div class="move-icon cursor-move">
                                                            <Icon name="el-icon-Rank" />
                                                        </div>
                                                    </template>
                                                </ElTableColumn>
                                                <ElTableColumn
                                                    label="字段值"
                                                    prop="props[field]"
                                                    show-overflow-tooltip></ElTableColumn>
                                                <ElTableColumn
                                                    label="字段名称"
                                                    prop="props[title]"
                                                    show-overflow-tooltip></ElTableColumn>
                                                <ElTableColumn
                                                    label="占位文字"
                                                    prop="props[placeholder]"
                                                    show-overflow-tooltip></ElTableColumn>
                                                <ElTableColumn label="填写类型" show-overflow-tooltip>
                                                    <template #default="{ row }">
                                                        {{ FieldTypeMap[row.name] }}
                                                    </template>
                                                </ElTableColumn>
                                                <ElTableColumn label="输入长度" prop="props[maxlength]"></ElTableColumn>
                                                <ElTableColumn label="是否必填">
                                                    <template #default="{ row }">
                                                        <ElSwitch v-model="row.props.isRequired"></ElSwitch>
                                                    </template>
                                                </ElTableColumn>
                                                <ElTableColumn label="操作" width="120px" fixed="right">
                                                    <template #default="{ row, $index }">
                                                        <ElButton
                                                            type="primary"
                                                            size="small"
                                                            link
                                                            @click="handleOpenForm($index, row)"
                                                            >编辑</ElButton
                                                        >
                                                        <ElButton
                                                            type="danger"
                                                            size="small"
                                                            link
                                                            @click="handleDeleteForm($index)"
                                                            >删除</ElButton
                                                        >
                                                    </template>
                                                </ElTableColumn>
                                            </ElTable>
                                        </div>
                                    </ElFormItem>
                                    <ElFormItem label="表单词提问配置">
                                        <ElInput
                                            v-model="formData.form_info"
                                            type="textarea"
                                            ref="elInputRef"
                                            :rows="6"
                                            resize="none"></ElInput>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <div
                                                v-for="item in formData.template_info.form"
                                                @click="insertAFormField(item.props.field)">
                                                <ElButton>{{ item.props.title }}</ElButton>
                                            </div>
                                        </div>
                                    </ElFormItem>
                                </ElForm>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
            <div
                class="w-full px-5 lg:w-1/2 grow justify-center lg:border-l border-token-border-medium bg-token-main-surface-secondary pt-4 lg:flex">
                <div class="flex-grow h-full">
                    <div class="h-full flex flex-col">
                        <div class="relative mb-2 flex-shrink-0">
                            <div class="flex justify-center text-[18px]">预览测试</div>
                        </div>
                        <div class="grow pt-10 min-h-0">
                            <Chatting
                                v-if="isEdit"
                                ref="chattingRef"
                                :is-stop="isStopChat"
                                :is-preview="true"
                                :content-list="chatContentList"
                                :send-disabled="isReceiving"
                                @content-post="contentPost"
                                @update:file-lists="getFileLists">
                                <div
                                    v-if="!chatContentList.length"
                                    class="flex items-center justify-center flex-col absolute h-full w-full">
                                    <div class="mb-3">
                                        <ElAvatar :size="48" :src="formData.logo" v-if="formData.logo" />
                                        <div class="w-12 h-12" v-else>
                                            <default-icon :icon-size="28"></default-icon>
                                        </div>
                                    </div>
                                    <div class="text-center text-2xl font-semibold">
                                        {{ formData.name }}
                                    </div>
                                    <div class="text-base">
                                        {{ formData.description }}
                                    </div>
                                    <div>
                                        <div
                                            class="mx-3 mt-12 flex max-w-3xl flex-wrap items-stretch justify-center gap-4">
                                            <template
                                                v-for="(item, index) in formData.preliminary_ask"
                                                @click="contentPost(item.value)">
                                                <button
                                                    v-if="item.value"
                                                    class="relative flex w-40 flex-col gap-2 rounded-2xl border border-token-border-light px-3 pb-4 pt-3 text-start align-top text-[15px] shadow-[0_0_2px_0_rgba(0,0,0,0.05),0_4px_6px_0_rgba(0,0,0,0.02)] transition hover:bg-token-main-surface-secondary">
                                                    <div class="line-clamp-3 text-balance text-gray-600 break-all">
                                                        {{ item.value }}
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </Chatting>
                            <div
                                v-else
                                class="h-full flex flex-col justify-end px-10 pb-5 md:max-w-3xl lg:max-w-[40rem] xl:max-w-[48rem] mx-auto">
                                <div class="grow flex items-center justify-center">
                                    <div class="w-12 h-12">
                                        <div
                                            class="relative flex h-full items-center justify-center rounded-full bg-white">
                                            <Icon name="local-icon-app" size="32"></Icon>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-end gap-1.5 md:gap-2">
                                    <div
                                        class="flex items-center justify-center w-full flex-col gap-1.5 rounded-[26px] p-1.5 transition-colors bg-[#f4f4f4] h-14 text-tx-primary text-lg">
                                        从定义您的 GPT 开始。
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <form-designer
        ref="formDesignerRef"
        v-if="showFormEdit"
        @close="showFormEdit = false"
        @add="handleFormAdd"
        @edit="handleFormEdit" />
</template>

<script setup lang="ts">
import { robotDetail, robotAdd, robotEdit } from "@/api/robot";
import { chatRobotSendTextStream } from "@/api/chat";
import { knowledgeBaseLists } from "@/api/knowledge_base";
import { FieldTypeMap } from "./_components/formEnums";
import { useDebounceFn, useThrottleFn } from "@vueuse/core";
import { setRangeText } from "@/utils/dom";
import type { InputInstance } from "element-plus";
import { useUserStore } from "@/stores/user";

const route = useRoute();
const router = useRouter();
const nuxtApp = useNuxtApp();
const userStore = useUserStore();
const { userInfo } = toRefs(userStore);

const pageLoading = ref<boolean>(false);
const avatarLoading = ref<boolean>(false);
const handleAvatarProgress = (number: number) => {
    avatarLoading.value = true;
    if (number === 100) {
        avatarLoading.value = false;
    }
};

const handleAvatar = (res: any) => {
    const {
        data: { uri },
    } = res;
    formData.logo = uri;
};

const formData = reactive<Record<string, any>>({
    id: "",
    assistants_id: "",
    logo: "",
    name: "",
    description: "",
    instructions: "",
    preliminary_ask: [
        {
            value: "",
        },
    ],
    vector_file_id: "",
    template_info: {
        ST: 0, //采样温度
        NT: 0, //核取温度
        limit: 0, // 生成条数
        form: [], // 填写表单
    },
    form_info: "",
    type: 1,
});

const { optionsData } = useDictOptions<{
    knowledgeBaseList: any[];
}>({
    knowledgeBaseList: {
        api: knowledgeBaseLists,
        params: {
            page_size: 999,
        },
        transformData: (data: any) => data.lists,
    },
});

const draggableOptions = [
    {
        selector: "tbody",
        options: {
            animation: 150,
            handle: ".move-icon",
            onEnd: ({ newIndex, oldIndex }: any) => {
                const arr = formData.template_info.form;
                const currRow = arr.splice(oldIndex, 1)[0];
                arr.splice(newIndex, 0, currRow);
                formData.template_info.form = [];
                nextTick(() => {
                    formData.template_info.form = arr;
                });
            },
        },
    },
];

const inputDol = (e: any, index: number) => {
    if (formData.preliminary_ask.length > 5) return;

    const laseIndex = formData.preliminary_ask.length - 1;
    // 判断 preliminary_ask
    if (formData.preliminary_ask.length === 1 && !formData.preliminary_ask[0].value) return;
    if (formData.preliminary_ask[index].value && formData.preliminary_ask[laseIndex].value != "") {
        formData.preliminary_ask.push({ value: "" });
    } else if (!formData.preliminary_ask[index].value) {
        delDol(index);
    }
};

const delDol = (index: number) => {
    if (formData.preliminary_ask.length === 1) return;
    const laseIndex = formData.preliminary_ask.length - 1;
    if (formData.preliminary_ask[laseIndex].value != "") {
        formData.preliminary_ask.push({ value: "" });
    }
    formData.preliminary_ask.splice(index, 1);
};

const elInputRef = shallowRef<InputInstance>();
const insertAFormField = (field: string) => {
    formData.form_info = setRangeText(elInputRef.value?.textarea!, `\${${field}}`);
};

const showFormEdit = ref(false);
const formDesignerRef = shallowRef();
const currentFormIndex = ref(0);

const isEdit = computed({
    get() {
        return !!route.query.id;
    },
    set(value) {
        isEdit.value = value;
    },
});

const handleOpenForm = async (index?: number, data?: any) => {
    showFormEdit.value = true;
    await nextTick();
    if (data) {
        currentFormIndex.value = index;
        formDesignerRef.value.open("edit");
        formDesignerRef.value?.setFormData(data);
    } else {
        formDesignerRef.value.open("add");
    }
};

const handleFormAdd = useThrottleFn((value: any) => {
    const isSameField = !!formData.template_info.form.find((item: any) => item.props.field === value.props.field);
    const isSameType = !!formData.template_info.form.find((item: any) => item.name === "WidgetFile");
    if (isSameType) {
        return feedback.msgError("只能添加一个上传文件字段");
    }
    if (isSameField) {
        return feedback.msgError("字段值重复，请修改字段值");
    }
    formData.template_info.form.push(value);
    formDesignerRef.value?.close();
});

const handleFormEdit = useThrottleFn((value: any) => {
    formData.value.template_info.form[currentFormIndex.value] = value;
    formDesignerRef.value?.close();
});

const handleDeleteForm = (index: number) => {
    nuxtApp.$confirm({
        message: "确定要删除此字段吗？",
        onConfirm: async () => {
            const currFiledValue = formData.template_info.form[index].props.field;
            formData.form_info = replaceDynamicString(formData.form_info, currFiledValue);
            formData.template_info.form.splice(index, 1);
        },
    });
};

const replaceDynamicString = (originalStr, dynamicStr) => {
    let regex = new RegExp(`\\$\\{${dynamicStr}\\}`, "g");
    let newStr = originalStr.replace(regex, "");
    return newStr;
};

const getDetail = async () => {
    pageLoading.value = true;
    const data = await robotDetail({
        id: route.query.id,
    });
    Object.keys(formData).forEach((key) => {
        //@ts-ignore
        formData[key] = data[key];
    });
    formData.template_info = JSON.parse(formData.template_info);
    formData.preliminary_ask = JSON.parse(formData.preliminary_ask);
    await nextTick();
    pageLoading.value = false;
};

const editLoading = ref(false);
const isManualSave = ref(false);
const handleEditType = ref("");
const addRobotResult = reactive({
    id: "",
    assistants_id: "",
});
const handleEdit = async (type: "auto" | "handle" = "auto") => {
    handleEditType.value = type;
    isManualSave.value = type == "handle";
    if (formData.name == "") {
        ElMessage.error("请输入机器人名称");
        return;
    }
    if (editLoading.value) return;
    editLoading.value = type == "auto" ? isLock.value : true;
    try {
        if (isEdit.value) {
            if (addRobotResult.assistants_id || addRobotResult.id) {
                formData.id = addRobotResult.id;
                formData.assistants_id = addRobotResult.assistants_id;
            }
            await robotEdit({
                ...formData,
                template_info: JSON.stringify(formData.template_info),
                preliminary_ask: JSON.stringify(formData.preliminary_ask),
            });
        } else {
            const data = await robotAdd({
                ...formData,
                template_info: JSON.stringify(formData.template_info),
                preliminary_ask: JSON.stringify(formData.preliminary_ask),
            });
            addRobotResult.id = data.id;
            addRobotResult.assistants_id = data.assistants_id;
            router.replace({
                path: route.path,
                query: {
                    id: data.id,
                },
            });
            isEdit.value = true;
        }
        if (type == "handle") {
            setTimeout(() => {
                editLoading.value = false;
                isManualSave.value = false;
            }, 1500);
        } else {
            isManualSave.value = false;
            editLoading.value = false;
        }
    } catch (error) {
        isManualSave.value = false;
        editLoading.value = false;
    }
};

const { lockFn: lockEdit, isLock } = useLockFn(handleEdit);
const saveRobot = useDebounceFn(
    () => {
        if (isManualSave.value) return;
        lockEdit();
    },
    handleEditType.value == "auto" ? 3000 : 1500
);

watch(formData, (value) => {
    if (!pageLoading.value) {
        saveRobot();
    }
});

//  聊天内容区 S
const chatContentList = ref<any[]>([]);

let streamReader: ReadableStreamDefaultReader<Uint8Array> | null = null;

const isReceiving = ref(false);
const chattingRef = ref(null);
const isStopChat = ref(false);
const newUserInput = ref<string>("");
const fileLists = ref([]);

// 发送问题
const contentPost = async (userInput: any) => {
    if (isReceiving.value) return;
    chatContentList.value.push({
        type: 1,
        message: userInput,
        fileLists: fileLists.value,
        from_avatar: userInfo.value.avatar,
    });
    const result = reactive({
        type: 2,
        loading: true,
        reply: "",
        error: "",
        from_avatar: formData.logo,
    });
    chatContentList.value.push(result);
    try {
        await chatRobotSendTextStream(
            {
                message: userInput,
                assistants_id: formData.assistants_id,
                file_id: fileLists.value.map((item) => item.file_id),
                is_debug: 1,
            },
            {
                onstart(reader) {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage(value) {
                    value
                        .trim()
                        .split("data:")
                        .forEach((text, index) => {
                            if (text !== "") {
                                try {
                                    const dataJson = JSON.parse(text);
                                    const { object, content } = dataJson;
                                    if (content && object === "load") {
                                        result.reply += content;
                                    }
                                    if (object === "finish") {
                                        result.loading = false;
                                        return;
                                    }
                                } catch (error) {}
                            }
                        });
                },
                onclose() {
                    result.loading = false;
                    resetChat();
                    setTimeout(async () => {
                        await nextTick();
                        chattingRef.value.scrollToBottom();
                    }, 600);
                },
            }
        );
    } catch (error) {
        result.loading = false;
        result.error = "发生错误";
        resetChat();
    }
    nextTick(() => {
        chattingRef.value.scrollToBottom();
    });
};

const resetChat = () => {
    fileLists.value = [];
    isReceiving.value = false;
    isStopChat.value = false;
};

// 获取上传文件
const getFileLists = (file: any[]) => {
    fileLists.value = file;
};
// 聊天内容区 E

onMounted(() => {
    isEdit.value && getDetail();
});

definePageMeta({
    layout: false,
});
</script>

<style lang="scss" scoped></style>
