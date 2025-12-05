<template>
    <div class="h-full">
        <div class="grid grid-cols-4 gap-4 h-full">
            <div
                v-for="(card, index) in sendWayList"
                class="shadow-[2px_2px_7px_1px_rgba(35,83,244,0.12)] rounded-lg p-4 border border-primary-light-8 cursor-pointer h-[70%] flex items-center justify-center hover:bg-primary-light-9 relative"
                :class="{ 'bg-primary-light-9': formData.type == card.type }"
                :key="index"
                @click="handleSendWay(card)">
                <div class="absolute top-2 right-2" v-if="formData.type == card.type">
                    <Icon name="el-icon-SuccessFilled" color="#5580F9" :size="24" />
                </div>
                <div class="flex flex-col items-center relative">
                    <img :src="card.img" class="w-[153px] h-[153px]" />
                    <div class="flex flex-col items-center mt-5 h-[100px]">
                        <span class="text-lg font-bold">{{ card.title }}</span>
                        <span class="text-[#969696] mt-3 text-center">{{ card.desc }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <popup
        v-if="showPopup"
        ref="popupRef"
        :title="currentCard.title"
        width="610px"
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <ElForm :model="formData" ref="formRef" :rules="rules" label-width="100px" label-position="top">
            <template v-if="currentCard.type == SendWayEnum.SPECIFIED_PROCESS">
                <ElFormItem label="请选择客户流程" prop="flow_id">
                    <ElSelect v-model="formData.flow_id" placeholder="请选择客户流程" filterable>
                        <ElOption
                            v-for="item in flowLists"
                            :label="item.flow_name"
                            :value="item.id"
                            :key="item.id"></ElOption>
                    </ElSelect>
                </ElFormItem>
            </template>
            <template v-if="currentCard.type == SendWayEnum.SPECIFIED_STAGE">
                <ElFormItem label="请选择流程" prop="flow_id">
                    <ElSelect v-model="formData.flow_id" placeholder="请选择流程" filterable>
                        <ElOption
                            v-for="item in flowLists"
                            :label="item.flow_name"
                            :value="item.id"
                            :key="item.id"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="请选择对应流程的阶段" prop="stage_id">
                    <ElSelect
                        v-model="formData.stage_id"
                        placeholder="请选择对应流程的阶段"
                        filterable
                        :disabled="!formData.flow_id">
                        <ElOption
                            v-for="item in getStageList"
                            :label="item.sub_stage_name"
                            :value="item.id"
                            :key="item.id"></ElOption>
                    </ElSelect>
                </ElFormItem>
            </template>
            <template v-if="currentCard.type == SendWayEnum.BIRTHDAY_CUSTOMER">
                <ElFormItem label="请要执行的流程客户" prop="flow_id">
                    <ElSelect v-model="formData.flow_id" placeholder="请选择流程" filterable>
                        <ElOption
                            v-for="item in flowLists"
                            :label="item.flow_name"
                            :value="item.id"
                            :key="item.id"></ElOption>
                    </ElSelect>
                </ElFormItem>
            </template>
            <template v-if="currentCard.type == SendWayEnum.FESTIVAL_ACTIVITY">
                <ElFormItem label="请选择流程" prop="festival">
                    <ElSelect v-model="formData.flow_id" placeholder="请选择流程" filterable>
                        <ElOption
                            v-for="item in flowLists"
                            :label="item.flow_name"
                            :value="item.id"
                            :key="item.id"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="请选择日期" prop="push_day">
                    <ElDatePicker
                        v-model="formData.push_day"
                        placeholder="请选择日期"
                        class="!w-full"
                        format="YYYY-MM-DD"
                        value-format="YYYY-MM-DD"
                        :disabled-date="getDisabledDate"></ElDatePicker>
                </ElFormItem>
            </template>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { sopPushUpdate } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import SendWayImage1 from "../_assets/images/send_way_1.png";
import SendWayImage2 from "../_assets/images/send_way_2.png";
import SendWayImage3 from "../_assets/images/send_way_3.png";
import SendWayImage4 from "../_assets/images/send_way_4.png";
import { dayjs, ElForm } from "element-plus";
import { SendWayEnum } from "../_enums";
import useTask from "../_hooks/useTask";

const emit = defineEmits<{
    (e: "success"): void;
}>();

const sendWayList = ref([
    {
        type: SendWayEnum.SPECIFIED_PROCESS,
        img: SendWayImage1,
        title: "指定的流程",
        desc: "选择您需要设置推送的指定流程，当用户进入流程时任务便开始进行",
    },
    {
        type: SendWayEnum.SPECIFIED_STAGE,
        img: SendWayImage2,
        title: "流程中的特定阶段",
        desc: "请选择客户旅程中的特定阶段，以便客户达到这些阶段时能够接收您的SOP",
    },
    {
        type: SendWayEnum.BIRTHDAY_CUSTOMER,
        img: SendWayImage3,
        title: "生日客户",
        desc: "为生日客户设置专属推送通知和祝福SOP",
    },
    {
        type: SendWayEnum.FESTIVAL_ACTIVITY,
        img: SendWayImage4,
        title: "节日活动",
        desc: "为节日活动设置专属推送通知",
    },
]);
const { flowLists, getFlowLists } = useTask();

const getStageList = computed(() => {
    return flowLists.value.find((item) => item.id == formData.flow_id)?.key_stages || [];
});

const currentCard = ref<any>(null);

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    id: "",
    flow_id: "",
    stage_id: "",
    type: "",
    status: "",
    push_day: "",
    push_type: 1,
});
const rules = {
    flow_id: [{ required: true, message: "请选择客户流程" }],
    stage_id: [{ required: true, message: "请选择客户阶段" }],
};

// 禁用当前日期之前的日期
const getDisabledDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

const popupRef = ref<InstanceType<typeof Popup>>();
const showPopup = ref(false);
const handleSendWay = async (card: any) => {
    currentCard.value = card;
    showPopup.value = true;
    await nextTick();
    popupRef.value?.open();
};

const close = () => {
    showPopup.value = false;
};

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    try {
        if (currentCard.value.type == SendWayEnum.SPECIFIED_PROCESS) {
            formData.stage_id = undefined;
        }
        await sopPushUpdate({
            ...formData,
            type: currentCard.value.type,
        });
        feedback.msgSuccess("设置成功");
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
});

getFlowLists();

defineExpose({
    setFormData: (data: any) => {
        setFormData(data, formData);
        if (data.flow_id) {
            formData.flow_id = parseInt(data.flow_id);
        }
    },
});
</script>

<style scoped></style>
