<template>
    <div class="h-full flex flex-col">
        <div class="grow min-h-0 w-[600px] px-4">
            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                <!-- 接管类型 -->
                <ElFormItem label="接管类型" prop="takeover_mode">
                    <ElSelect v-model="formData.takeover_mode" placeholder="请选择接管类型">
                        <ElOption label="人工接管" :value="0"></ElOption>
                        <ElOption label="AI接管" :value="1"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <!-- 接管Ai -->
                <ElFormItem label="接管机器人AI" prop="robot_id" v-if="formData.takeover_mode === 1">
                    <div class="w-full">
                        <ElSelect
                            v-model="formData.robot_id"
                            filterable
                            clearable
                            remote
                            placeholder="请选择接管机器人AI"
                            :loading="agentLoading"
                            :remote-method="getAgentFn">
                            <ElOption
                                v-for="item in agentLists"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"></ElOption>
                        </ElSelect>
                        <div class="flex justify-end mt-2">
                            <router-link to="/agent" target="_blank">
                                <ElButton link type="primary" size="small"> 点击这里创建您的专属机器人 > </ElButton>
                            </router-link>
                        </div>
                    </div>
                </ElFormItem>
            </ElForm>
        </div>
        <div class="flex items-center justify-center my-4">
            <ElButton type="primary" class="w-[166px] !h-[40px]" :loading="isLockSubmit" @click="lockSubmit">
                保存
            </ElButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getAgentList } from "@/api/agent";
import { getAccountDetail, changeAccountStatus } from "@/api/service";
import { type FormInstance } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const route = useRoute();

const account = computed(() => route.query.account);

const formRef = ref<FormInstance>();
const formData = reactive({
    account: account.value,
    takeover_mode: 0,
    robot_id: "",
});

const rules = {
    takeover_mode: [{ required: true, message: "请选择接管类型", trigger: "blur" }],
    robot_id: [{ required: true, message: "请选择接管机器人AI", trigger: "blur" }],
};

const agentLists = ref<any[]>([]);
const agentLoading = ref(false);
const getAgentFn = async (query?: string) => {
    agentLoading.value = true;
    const data = await getAgentList({ keyword: query, source: 1 });
    agentLists.value = data.lists;
    agentLoading.value = false;
};

const handleSubmit = async () => {
    await formRef.value.validate();
    try {
        await changeAccountStatus(formData);
        emit("success");
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error || "提交失败");
    }
};

const { lockFn: lockSubmit, isLock: isLockSubmit } = useLockFn(handleSubmit);

const getDetail = async () => {
    const data = await getAccountDetail({ account: route.query.account });
    setFormData(data, formData);
};
watch(
    () => route.query.account,
    (val) => {
        if (val) {
            formData.account = val;
            getDetail();
            getAgentFn();
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped></style>
