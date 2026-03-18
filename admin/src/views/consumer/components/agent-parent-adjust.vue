<template>
    <popup ref="popupRef" title="代理上级调整" width="500px" :async="true" @close="close" @confirm="handleConfirm">
        <div class="pr-8">
            <el-form ref="formRef" :model="formData" label-width="120px" @submit.native.prevent>
                <el-form-item label="用户信息"> {{ userInfo.nickname }}({{ userInfo.sn }}) </el-form-item>

                <el-form-item label="当前邀请人">
                    {{ userInfo.distribution_parent_name || "-" }}
                </el-form-item>

                <el-form-item label="选择邀请人">
                    <UserPicker
                        title="代理上级"
                        v-model="formData.leader_id"
                        v-model:select-data="selectData"
                        type="single">
                        <template #popup>
                            <div class="flex">
                                <span class="mr-2" v-if="selectData?.id">
                                    {{ selectData!.nickname || "" }}({{ selectData!.sn }})
                                </span>
                                <el-button type="primary" link> 选择用户 </el-button>
                            </div>
                        </template>
                    </UserPicker>
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>
<script lang="ts" setup>
import type Popup from "@/components/popup/index.vue";
import type { FormInstance } from "element-plus";
import { adjustLeader } from "@/api/consumer";

type AdjustLeaderType = {
    id: number | string;
    leader_id: number | string;
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
});
const formData = reactive<AdjustLeaderType>({
    id: "",
    leader_id: "",
});
const selectData = ref<any>({});
const popupRef = shallowRef<InstanceType<typeof Popup>>();

const handleConfirm = async () => {
    await adjustLeader({ id: formData.id, field: "parent_id", value: formData.leader_id });
    emit("success");
    close();
};

const close = () => {
    emit("close");
};

const open = (id: number) => {
    formData.id = id;
    popupRef.value?.open();
};

defineExpose({ open });
</script>
