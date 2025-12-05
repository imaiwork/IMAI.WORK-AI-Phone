<template>
    <popup ref="popupRef" async width="500px" @close="close" @confirm="confirm">
        <div>
            <ElForm ref="formRef" :model="formData" label-position="top" :rules="rules">
                <template v-if="handleType == BoardHandleTypeEnum.TRANSFER">
                    <ElFormItem label="请选择转移到本流程的其它阶段">
                        <ElSelect v-model="formData.to_stage_id" placeholder="请选择" filterable clearable>
                            <ElOption
                                v-for="item in transferStageLists"
                                :key="item.id"
                                :label="item.sub_stage_name"
                                :value="item.id"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                </template>
                <template v-if="handleType == BoardHandleTypeEnum.TRANSFER_TO_CYCLE">
                    <ElFormItem label="请选择已有的流程">
                        <ElSelect v-model="formData.to_flow_id" placeholder="请选择已有的流程">
                            <ElOption
                                v-for="item in transferFlowLists"
                                :key="item.id"
                                :label="item.flow_name"
                                :value="item.id"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <ElFormItem label="请选择对应流程的阶段">
                        <ElSelect
                            v-model="formData.to_stage_id"
                            placeholder="请选择对应流程的阶段"
                            :disabled="!formData.to_flow_id">
                            <ElOption
                                v-for="item in getStageLists"
                                :key="item.id"
                                :label="item.sub_stage_name"
                                :value="item.id"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                </template>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { BoardHandleTypeEnum } from "../../../_enums";
import { ElForm } from "element-plus";

const props = defineProps<{
    wechatId: string;
    stageLists: any[];
    flowLists: any[];
    stageId: string | number;
    flowId: string | number;
}>();

const emit = defineEmits<{
    (e: "confirm", data: any): void;
    (e: "close"): void;
}>();

const popupRef = ref<InstanceType<typeof Popup>>();

const formData = reactive<any>({
    stage_id: props.stageId,
    flow_id: props.flowId,
    wechat_id: props.wechatId,
    to_flow_id: "",
    to_stage_id: "",
    friend_id: [],
});

const formRef = ref<InstanceType<typeof ElForm>>();

const handleType = ref<BoardHandleTypeEnum>(BoardHandleTypeEnum.CLEAR);

const rules = {
    to_stage_id: [{ required: true, message: "请选择转移到的阶段" }],
    to_flow_id: [{ required: true, message: "请选择转移到的流程" }],
};

const transferStageLists = ref([]);
const transferFlowLists = ref([]);
const getStageLists = computed(() => {
    return transferFlowLists.value.find((item) => item.id == formData.to_flow_id)?.key_stages;
});

const open = (type: BoardHandleTypeEnum) => {
    handleType.value = type;
    popupRef.value?.open();
    if (type == BoardHandleTypeEnum.TRANSFER) {
        transferStageLists.value = props.stageLists.filter((item) => item.id != props.stageId);
        formData.to_flow_id = props.flowId;
        if (transferStageLists.value.length > 0) {
            formData.to_stage_id = transferStageLists.value[0].id;
        }
    } else if (type == BoardHandleTypeEnum.TRANSFER_TO_CYCLE) {
        transferFlowLists.value = props.flowLists.filter((item) => item.id != props.flowId);
        if (transferFlowLists.value.length > 0) {
            formData.to_flow_id = transferFlowLists.value[0].id;
        }
    }
};

const close = () => {
    emit("close");
};

const confirm = async () => {
    emit("confirm", formData);
    close();
};

defineExpose({
    open,
    close,
});
</script>

<style scoped></style>
