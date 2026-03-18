<template>
    <popup ref="popupRef" title="代理等级调整" width="500px" :async="true" @close="close" @confirm="handleConfirm">
        <div class="pr-8">
            <el-form ref="formRef" :model="formData" label-width="120px" @submit.native.prevent>
                <el-form-item label="用户信息"> {{ userInfo.nickname }}({{ userInfo.sn }}) </el-form-item>
                <el-form-item label="代理等级" prop="value">
                    <el-select v-model="formData.value" placeholder="请选择代理等级">
                        <el-option label="普通用户" value="0" />
                        <el-option
                            v-for="item in agentGradeConfigList"
                            :key="item.level"
                            :label="item.name"
                            :value="`${item.level}`" />
                    </el-select>
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>
<script lang="ts" setup>
import type Popup from "@/components/popup/index.vue";
import type { FormInstance } from "element-plus";
import { adjustLeader } from "@/api/consumer";
import { getAgentGradeConfig } from "@/api/marketing/agent";

type AdjustLeaderType = {
    id: number | string;
    field: "level";
    value: number | string;
};

const emit = defineEmits<{
    (event: "success", value: void): void;
    (event: "close", value: void): void;
}>();
const formRef = shallowRef<FormInstance>();
const props = defineProps({
    userInfo: {
        type: Object,
        default: {},
    },
    title: {
        type: String,
        required: true,
    },
    show: {
        type: Boolean,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
});
const formData = reactive<AdjustLeaderType>({
    id: "",
    field: "level",
    value: "",
});
const selectData = ref<any>({});
const popupRef = shallowRef<InstanceType<typeof Popup>>();

const handleConfirm = async () => {
    await adjustLeader(formData);
    emit("success");
    close();
};

const close = () => {
    emit("close");
    popupRef.value?.close();
};

const open = (data: any) => {
    formData.id = data.id;
    formData.value = data.distribution_level?.toString();
    popupRef.value?.open();
    getAgentGradeConfigList();
};

const agentGradeConfigList = ref<any[]>([]);
const getAgentGradeConfigList = async () => {
    const res = await getAgentGradeConfig();
    agentGradeConfigList.value = res;
};

defineExpose({ open });
</script>
