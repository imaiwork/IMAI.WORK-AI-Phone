<template>
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between bg-white p-4 rounded-lg flex-shrink-0">
            <div>
                <ElBreadcrumb :separator-icon="ArrowRight">
                    <ElBreadcrumbItem>
                        <span class="cursor-pointer text-[#8A8C99] hover:text-primary" @click="close"> 任务管理 </span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem>
                        <span
                            :class="{
                                'cursor-pointer text-[#8A8C99] hover:text-primary': stepKey == StepKey.CONTENT,
                            }"
                            @click="back">
                            {{ detail?.push_name || "新建任务" }}
                        </span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem v-if="stepKey == StepKey.CONTENT">
                        <span> 设置推送内容 </span>
                    </ElBreadcrumbItem>
                </ElBreadcrumb>
            </div>
            <div class="flex justify-center">
                <ElButton type="primary" class="!h-10 w-[100px] !rounded-full" @click="cancel">取消</ElButton>
                <ElButton class="!h-10 w-[100px] !rounded-full" v-if="stepKey != StepKey.NAME" @click="back"
                    >返回</ElButton
                >
            </div>
        </div>
        <div class="grow min-h-0 bg-white rounded-lg mt-4 p-6" v-loading="loading">
            <template v-if="!loading">
                <div class="step-container h-full flex flex-col" v-if="stepKey == StepKey.NAME">
                    <div class="grow min-h-0 w-[680px] flex flex-col gap-10">
                        <div
                            v-for="(item, index) in stepLists"
                            :key="index"
                            class="h-[149px] rounded-2xl border border-primary-light-8 relative">
                            <div
                                class="absolute top-0 right-0 rounded-tr-2xl rounded-bl-2xl w-[100px] p-2 text-center text-white text-lg font-bold"
                                :class="[item.status == 1 ? 'bg-success' : 'bg-primary']">
                                {{ item.status == 1 ? "已设置" : `第${index + 1}步` }}
                            </div>
                            <div class="px-8 mt-8 flex gap-4">
                                <img :src="item.img" alt="" class="w-[56px] h-[56px]" />
                                <div class="flex flex-col gap-2">
                                    <div class="font-bold text-[20px]">
                                        {{ item.title }}
                                    </div>
                                    <div class="text-lg text-[#8A8C99]">
                                        {{ item.desc }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end px-3 mt-4">
                                <div
                                    v-if="index != 0 && item.status == 0 && stepLists[index - 1].status == 0"
                                    class="flex items-center gap-2">
                                    <span class="opacity-50 text-primary"> 您还未完成上一步</span>
                                    <Icon name="el-icon-QuestionFilled" color="#5580F9"></Icon>
                                </div>
                                <div v-else class="cursor-pointer flex items-center gap-2" @click="handleStep(item)">
                                    <span class="hover:underline text-primary">前往设置</span>
                                    <Icon name="el-icon-Right" :size="16" color="var(--color-primary)"></Icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="stepKey == StepKey.CONTENT" class="h-full flex flex-col gap-x-4">
                    <div class="mb-4 text-lg text-[#8A8C99]">推送时间：{{ detail.push_day }}</div>
                    <send-container
                        ref="sendContainerRef"
                        :type="PushTypeEnum.TASK"
                        :task-id="detail?.id"
                        :push-day="detail?.push_day"
                        @back="back"
                        @success="getDetail(detail?.id)" />
                </div>
            </template>
        </div>
    </div>
    <send-name
        v-if="showNameEdit"
        ref="nameEditRef"
        :type="PushTypeEnum.TASK"
        @success="handleNameSuccess"
        @close="showNameEdit = false" />
    <send-flow
        v-if="showSendFlow"
        ref="sendFlowRef"
        :type="SendWayEnum.GROUP_SEND"
        @success="handleSendFlowSuccess"
        @close="showSendFlow = false" />
</template>

<script setup lang="ts">
import { sopPushDetail, sopPushContentTimeLists } from "@/api/person_wechat";
import { ArrowRight } from "@element-plus/icons-vue";
import { PushTypeEnum, SendWayEnum } from "../../../_enums";
import useTask from "../../../_hooks/useTask";
import SendName from "../../../_components/send-name.vue";
import SendContainer from "../../../_components/send-container.vue";
import SendFlow from "../../../_components/send-flow.vue";

const emit = defineEmits<{
    (e: "back"): void;
}>();

enum StepKey {
    NAME = "name",
    CONTENT = "content",
    PEOPLE = "people",
}
const nuxtApp = useNuxtApp();
const stepKey = ref<StepKey>(StepKey.NAME);
const { resource } = useTask();

const stepLists = ref<any[]>([
    {
        img: resource.CreateImg,
        title: "给您的任务起个名字",
        desc: "请输入一个独特的名称，以便更好的管理和识别",
        status: 0,
        key: StepKey.NAME,
    },
    {
        img: resource.ContentImg,
        title: "设置您需要推送的内容",
        desc: "选择并配置您希望目标对象接收的推送信息和推送类型",
        status: 0,
        key: StepKey.CONTENT,
    },
    {
        img: resource.PostImg,
        title: "选择您希望推送的人员",
        desc: "请从列表中选择人员或组，您可以根据需要随时调整选择。",
        status: 0,
        key: StepKey.PEOPLE,
    },
]);
const detail = ref<any>(null);

const nameEditRef = ref<InstanceType<typeof SendName>>();
const showNameEdit = ref(false);

const sendContainerRef = ref<InstanceType<typeof SendContainer>>();

const sendFlowRef = ref<InstanceType<typeof SendFlow>>();
const showSendFlow = ref(false);

const handleNameSuccess = async (result: any) => {
    showNameEdit.value = false;
    await getDetail(result?.id || detail.value.id);
    replaceState({
        id: result?.id || detail.value.id,
    });
};

const handleSendFlowSuccess = async () => {
    showSendFlow.value = false;
    getDetail(detail.value.id);
};

const handleStep = async (item: { key: StepKey }) => {
    if (item.key == StepKey.PEOPLE) {
        showSendFlow.value = true;
        await nextTick();
        sendFlowRef.value?.open();
        if (detail.value) {
            sendFlowRef.value?.setFormData(detail.value);
        }
        return;
    }
    stepKey.value = item.key;
    const { key } = item;
    if (key == StepKey.NAME) {
        showNameEdit.value = true;
        await nextTick();
        nameEditRef.value?.open();
        if (detail.value) {
            nameEditRef.value?.setFormData(detail.value);
        }
    } else if (key == StepKey.CONTENT) {
        setContentFormData();
    }
    replaceState({
        step: stepKey.value,
    });
};

const close = () => {
    emit("back");
};

const cancel = () => {
    nuxtApp.$confirm({
        message: "确定取消吗？",
        onConfirm: () => {
            close();
        },
    });
};

const back = () => {
    stepKey.value = StepKey.NAME;
    replaceState({
        step: stepKey.value,
    });
};

const loading = ref(false);

const getDetail = async (id?: number | string) => {
    if (!id) return;
    loading.value = true;
    try {
        const result = await sopPushDetail({
            id,
        });
        detail.value = result;

        if (result.push_name) {
            stepLists.value.forEach((item) => {
                if (item.key == StepKey.NAME) {
                    item.status = 1;
                }
            });
        }

        if (result.push_time_list && result.push_time_list.length) {
            setContentFormData();
            stepLists.value.forEach((item) => {
                if (item.key == StepKey.CONTENT) {
                    item.status = 1;
                }
            });
        }
        if (result.flow_id) {
            setPeopleFormData();
            stepLists.value.forEach((item) => {
                if (item.key == StepKey.PEOPLE) {
                    item.status = 1;
                }
            });
        }
    } finally {
        loading.value = false;
    }
};

const setContentFormData = async () => {
    await nextTick();
    sendContainerRef.value?.setFormData(detail.value);
    const result = await sopPushContentTimeLists({ push_id: detail.value.id });
    sendContainerRef.value?.setDateList(result);
};

const setPeopleFormData = async () => {
    await nextTick();
    sendFlowRef.value?.setFormData(detail.value);
};

const init = async () => {
    const { id, step } = searchQueryToObject();
    if (step && stepLists.value.some((item) => item.key == step)) {
        stepKey.value = step as StepKey;
    } else {
        stepKey.value = StepKey.NAME;
    }

    id && getDetail(id);
};

onMounted(init);

defineExpose({
    getDetail,
});
</script>

<style scoped lang="scss">
.step-container {
    background-image: url("../../_assets/images/task_create_bg.png");
    background-size: 300px;
    background-position: right 60px top 40px;
    background-repeat: no-repeat;
}
:deep(.el-loading-mask) {
    background-color: #ffffff;
}
</style>

<style lang="scss">
.post-people-message-box {
    .el-message-box__message {
        width: 100%;
    }
}
</style>
