<template>
    <div class="h-full flex flex-col gap-8 p-1">
        <div class="grid grid-cols-4 gap-5">
            <div
                v-for="(card, index) in sendWayList"
                :key="index"
                class="send-way-card group"
                :class="{ 'is-active': formData?.type == card.type }"
                @click="handleSendWay(card)">
                <div class="active-check" v-if="formData?.type == card.type">
                    <Icon name="el-icon-Check" color="#fff" :size="14" />
                </div>

                <div class="flex flex-col items-center relative z-10">
                    <div
                        class="w-20 h-20 mb-4 rounded-2xl bg-slate-50 flex items-center justify-center group-hover:bg-white transition-all duration-500">
                        <img
                            :src="card.img"
                            class="w-14 h-14 object-contain group-hover:scale-110 transition-transform duration-500" />
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <span
                            class="text-[16px] font-black text-slate-800 mb-2 group-hover:text-primary transition-colors">
                            {{ card.title }}
                        </span>
                        <span class="text-slate-400 text-xs font-medium leading-relaxed px-2">
                            {{ card.desc }}
                        </span>
                    </div>
                </div>

                <div
                    class="absolute bottom-0 left-0 right-0 h-1 bg-primary scale-x-0 group-[.is-active]:scale-x-100 transition-transform duration-300"></div>
            </div>
        </div>

        <div class="flex items-center gap-4 py-2">
            <div class="flex-1 h-[1px] bg-slate-100"></div>
            <div class="flex items-center gap-2 text-slate-400 text-xs font-medium">
                <Icon name="el-icon-Setting" />
                <span>配置触发细节</span>
            </div>
            <div class="flex-1 h-[1px] bg-slate-100"></div>
        </div>

        <div class="bg-[#f8fafc]/50 rounded-[24px] p-8 border border-slate-100">
            <ElForm :model="formData" ref="formRef" :rules="rules" label-position="top">
                <div class="max-w-xl mx-auto">
                    <template v-if="formData?.type == SendWayEnum.SPECIFIED_PROCESS">
                        <ElFormItem label="选择客户流程" prop="flow_id">
                            <ElSelect
                                v-model="formData.flow_id"
                                placeholder="请选择客户流程"
                                filterable
                                class="custom-select">
                                <ElOption
                                    v-for="item in flowLists"
                                    :label="item.flow_name"
                                    :value="item.id"
                                    :key="item.id"></ElOption>
                            </ElSelect>
                            <div class="mt-2 text-xs text-slate-400">当用户进入此流程时，SOP 将自动开启</div>
                        </ElFormItem>
                    </template>

                    <template v-if="formData?.type == SendWayEnum.SPECIFIED_STAGE">
                        <div class="grid grid-cols-2 gap-6">
                            <ElFormItem label="选择所属流程" prop="flow_id">
                                <ElSelect
                                    v-model="formData.flow_id"
                                    placeholder="请选择流程"
                                    filterable
                                    class="custom-select">
                                    <ElOption
                                        v-for="item in flowLists"
                                        :label="item.flow_name"
                                        :value="item.id"
                                        :key="item.id"></ElOption>
                                </ElSelect>
                            </ElFormItem>
                            <ElFormItem label="选择特定阶段" prop="stage_id">
                                <ElSelect
                                    v-model="formData.stage_id"
                                    placeholder="请选择对应阶段"
                                    filterable
                                    :disabled="!formData.flow_id"
                                    class="custom-select">
                                    <ElOption
                                        v-for="item in getStageList"
                                        :label="item.sub_stage_name"
                                        :value="item.id"
                                        :key="item.id"></ElOption>
                                </ElSelect>
                            </ElFormItem>
                        </div>
                    </template>

                    <template v-if="formData?.type == SendWayEnum.BIRTHDAY_CUSTOMER">
                        <ElFormItem label="目标流程客户范围" prop="flow_id">
                            <ElSelect
                                v-model="formData.flow_id"
                                placeholder="请选择流程"
                                filterable
                                class="custom-select">
                                <ElOption
                                    v-for="item in flowLists"
                                    :label="item.flow_name"
                                    :value="item.id"
                                    :key="item.id"></ElOption>
                            </ElSelect>
                        </ElFormItem>
                    </template>

                    <template v-if="formData?.type == SendWayEnum.FESTIVAL_ACTIVITY">
                        <div class="grid grid-cols-2 gap-6">
                            <ElFormItem label="关联流程" prop="flow_id">
                                <ElSelect
                                    v-model="formData.flow_id"
                                    placeholder="请选择流程"
                                    filterable
                                    class="custom-select">
                                    <ElOption
                                        v-for="item in flowLists"
                                        :label="item.flow_name"
                                        :value="item.id"
                                        :key="item.id"></ElOption>
                                </ElSelect>
                            </ElFormItem>
                            <ElFormItem label="推送执行日期" prop="push_day">
                                <ElDatePicker
                                    v-model="formData.push_day"
                                    placeholder="选择日期"
                                    class="!w-full custom-picker"
                                    format="YYYY-MM-DD"
                                    value-format="YYYY-MM-DD"
                                    :disabled-date="getDisabledDate"></ElDatePicker>
                            </ElFormItem>
                        </div>
                    </template>
                </div>
            </ElForm>
        </div>
    </div>
</template>
<script setup lang="ts">
import { sopPushUpdate } from "@/api/person_wechat";
import SendWayImage1 from "../_assets/images/send_way_1.png";
import SendWayImage2 from "../_assets/images/send_way_2.png";
import SendWayImage3 from "../_assets/images/send_way_3.png";
import SendWayImage4 from "../_assets/images/send_way_4.png";
import { dayjs, ElForm } from "element-plus";
import { SendWayEnum } from "../_enums";
import useTask from "../_hooks/useTask";

const props = defineProps<{
    modelValue: any;
}>();

const emit = defineEmits<{
    (e: "success"): void;
}>();

const formData = defineModel<any>("modelValue");
const { flowLists, getFlowLists } = useTask();

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

const getStageList = computed(() => {
    return flowLists.value.find((item) => item.id == formData.value.flow_id)?.key_stages || [];
});

const formRef = ref<InstanceType<typeof ElForm>>();

const rules = {
    flow_id: [{ required: true, message: "请选择客户流程" }],
    stage_id: [{ required: true, message: "请选择客户阶段" }],
};

// 禁用当前日期之前的日期
const getDisabledDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

const handleSendWay = async (card: any) => {
    formData.value.type = card.type;
};

getFlowLists();

defineExpose({
    validateForm: () => {
        return formRef.value?.validate();
    },
});
</script>
<style scoped lang="scss">
.send-way-card {
    @apply relative bg-white border border-slate-100 rounded-[24px] p-6 cursor-pointer transition-all duration-300 overflow-hidden;

    &:hover {
        @apply shadow-[#0065fb]/5 border-[#0065fb]/20 -translate-y-1;
    }

    &.is-active {
        @apply border-primary bg-[#0065fb]/[0.02] shadow-[#0065fb]/10;
        &:hover {
            @apply -translate-y-0;
        }
    }
}

.active-check {
    @apply absolute top-0 right-0 w-8 h-8 bg-primary rounded-bl-2xl flex items-center justify-center z-20;
}
</style>
