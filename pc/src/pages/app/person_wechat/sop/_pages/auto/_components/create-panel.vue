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
                                'cursor-pointer text-[#8A8C99] hover:text-primary': [
                                    StepKey.TYPE,
                                    StepKey.CONTENT,
                                ].includes(stepKey),
                            }"
                            @click="back">
                            {{ detail ? detail.push_name : "新建SOP" }}
                        </span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem v-if="stepKey == StepKey.TYPE">
                        <span> 设置推送方式 </span>
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
        <div class="grow min-h-0 bg-white rounded-xl mt-4 p-6" v-loading="loading">
            <template v-if="!loading">
                <div class="step-container h-full flex flex-col" v-if="stepKey == StepKey.NAME">
                    <div class="grow min-h-0 w-[680px] flex flex-col gap-10">
                        <div
                            v-for="(item, index) in stepLists"
                            :key="index"
                            class="h-[149px] rounded-2xl border border-solid border-primary-light-8 relative">
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
                <div class="h-full">
                    <send-container
                        v-if="stepKey == StepKey.CONTENT"
                        ref="sendContainerRef"
                        :type="PushTypeEnum.AUTO_SOP"
                        :task-id="detail?.id"
                        :push-day="detail?.push_day"
                        @back="back"
                        @success="handleSendContainerSuccess" />
                    <send-way v-if="stepKey == StepKey.TYPE" ref="sendWayRef" @success="getDetail(detail?.id)" />
                </div>
            </template>
        </div>
    </div>
    <send-name
        v-if="showNameEdit"
        ref="nameEditRef"
        :type="PushTypeEnum.AUTO_SOP"
        @success="handleNameSuccess"
        @close="showNameEdit = false" />
</template>

<script setup lang="ts">
import { sopPushDetail, sopPushContentTimeLists, sopPushUpdate } from "@/api/person_wechat";
import { ArrowRight } from "@element-plus/icons-vue";
import SendName from "../../../_components/send-name.vue";
import SendWay from "../../../_components/send-way.vue";
import SendContainer from "../../../_components/send-container.vue";
import useTask from "../../../_hooks/useTask";
import { PushTypeEnum } from "../../../_enums";
const emit = defineEmits<{
    (e: "back"): void;
}>();

enum StepKey {
    NAME = "name",
    TYPE = "type",
    CONTENT = "content",
}

const nuxtApp = useNuxtApp();
const stepKey = ref<StepKey>(StepKey.NAME);

const { resource } = useTask();

const stepLists = ref<any[]>([
    {
        img: resource.CreateImg,
        title: "给您的SOP起个名字",
        desc: "请输入一个独特的名称，以便更好的管理和识别",
        status: 0,
        key: StepKey.NAME,
    },
    {
        img: resource.PostImg,
        title: "您希望以什么方式推送",
        desc: "请选择推送渠道，确保信息传递符合您的偏好",
        status: 0,
        key: StepKey.TYPE,
    },
    {
        img: resource.ContentImg,
        title: "设置您需要推送的内容",
        desc: "选择并配置您希望目标对象接收的推送信息和推送类型",
        status: 0,
        key: StepKey.CONTENT,
    },
]);
const detail = ref<any>(null);

const nameEditRef = ref<InstanceType<typeof SendName>>();
const showNameEdit = ref(false);

const sendWayRef = ref<InstanceType<typeof SendWay>>();
const sendContainerRef = ref<InstanceType<typeof SendContainer>>();

const handleNameSuccess = async (result: any) => {
    showNameEdit.value = false;
    await getDetail(result?.id || detail.value.id);
    replaceState({
        id: result?.id || detail.value.id,
    });
};

const handleSendContainerSuccess = async () => {
    await getDetail(detail.value.id);
    sopPushUpdate({
        id: detail.value.id,
        type: detail.value.type,
        flow_id: detail.value.flow_id,
        stage_id: detail.value.stage_id,
        push_type: detail.value.push_type,
        status: 2,
    });
};

const handleStep = async (item: { key: StepKey }) => {
    stepKey.value = item.key;
    const { key } = item;
    if (key == StepKey.NAME) {
        showNameEdit.value = true;
        await nextTick();
        nameEditRef.value?.open();
        if (detail.value) {
            nameEditRef.value?.setFormData(detail.value);
        }
    } else if (key == StepKey.TYPE) {
        await setTypeFormData();
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

const cancel = async () => {
    await nuxtApp.$confirm({
        message: "确定取消吗？",
        onConfirm: async () => {
            close();
        },
    });
};

const back = () => {
    stepKey.value = StepKey.NAME;
    replaceState({
        step: StepKey.NAME,
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
        if (result.flow_id) {
            stepLists.value.forEach((item) => {
                if (item.key == StepKey.TYPE) {
                    item.status = 1;
                }
            });
        }
        if (StepKey.TYPE == stepKey.value) {
            setTypeFormData();
        }

        if (result.push_time_list && result.push_time_list.length) {
            setContentFormData();
            stepLists.value.forEach((item) => {
                if (item.key == StepKey.CONTENT) {
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

const setTypeFormData = async () => {
    await nextTick();
    sendWayRef.value?.setFormData(detail.value);
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
</style>

<style lang="scss">
.post-people-message-box {
    .el-message-box__message {
        width: 100%;
    }
}
</style>
