<template>
    <popup
        ref="popupRef"
        async
        width="600px"
        title="编辑微信账号"
        :confirm-loading="isLock"
        @confirm="lockConfirm"
        @close="close">
        <ElForm :model="formData" ref="formRef" :rules="formRules" label-position="top">
            <ElFormItem label="AI总功能开关" prop="open_ai">
                <ElSwitch v-model="formData.open_ai" :active-value="1" :inactive-value="0" />
            </ElFormItem>
            <ElFormItem label="账号备注" prop="remark">
                <ElInput v-model="formData.remark" placeholder="点击输入账号备注" />
            </ElFormItem>
            <ElFormItem label="接管模式" prop="takeover_mode">
                <ElRadioGroup v-model="formData.takeover_mode">
                    <ElRadio label="AI接管" :value="1"></ElRadio>
                    <ElRadio label="人工介入" :value="0"></ElRadio>
                </ElRadioGroup>
            </ElFormItem>
            <ElFormItem label="AI接管机器人" prop="robot_id" v-if="formData.takeover_mode === 1">
                <ElSelect
                    v-model="formData.robot_id"
                    filterable
                    clearable
                    remote
                    placeholder="请选择接管机器人AI"
                    :loading="agentLoading"
                    :remote-method="getAgentFn">
                    <ElOption v-for="item in agentLists" :key="item.id" :label="item.name" :value="item.id"></ElOption>
                </ElSelect>
            </ElFormItem>
            <!-- 接管类型 -->
            <ElFormItem label="接管类型" prop="takeover_type">
                <ElRadioGroup v-model="formData.takeover_type">
                    <ElRadio label="全部" :value="0"></ElRadio>
                    <ElRadio label="私聊" :value="1"></ElRadio>
                    <!-- <ElRadio label="群聊" :value="2"></ElRadio> -->
                </ElRadioGroup>
            </ElFormItem>
            <!-- 接管范围 -->
            <!-- <ElFormItem label="接管范围" prop="range" v-if="false">
				<ElRadioGroup v-model="formData.range">
					<ElRadio label="包含" value="1"></ElRadio>
					<ElRadio label="除了" value="2"></ElRadio>
				</ElRadioGroup>
				<ElSelect
					class="mt-2"
					v-model="formData.tags"
					multiple
					clearable
					filterable
					placeholder="请选择接管范围">
					<ElOption label="包含" value="1"></ElOption>
					<ElOption label="除了" value="2"></ElOption>
				</ElSelect>
			</ElFormItem> -->
            <!-- 排序 -->
            <ElFormItem label="排序" prop="sort">
                <ElInputNumber v-model="formData.sort" :precision="0" :min="0" :max="100" />
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { getWeChatAi, saveWeChatAi } from "@/api/person_wechat";
import { getAgentList } from "@/api/agent";
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
const emit = defineEmits(["close", "success"]);

const formRef = shallowRef<FormInstance>();
const formData = reactive<any>({
    wechat_id: "", //微信ID，微信提供的ID
    open_ai: 1, //AI总功能开关 0：关闭 1：开启
    remark: "wechat_ai", //备注
    takeover_mode: 1, //接管模式 0：人工接管 1：AI接管
    takeover_type: 1, //接管类型 0：全部 1：私聊 2：群聊
    robot_id: "", //AI接管机器人
    sort: 1, //排序
});

const formRules = {
    remark: [{ required: true, message: "请输入账号备注" }],
    open_ai: [{ required: true, message: "请选择AI接管" }],
    takeover_mode: [{ required: true, message: "请选择接管模式" }],
    takeover_type: [{ required: true, message: "请选择接管类型" }],
    robot_id: [{ required: true, message: "请选择AI接管机器人" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const agentLists = ref<any[]>([]);
const agentLoading = ref(false);
const getAgentFn = async (query?: string) => {
    agentLoading.value = true;
    const data = await getAgentList({ keyword: query, source: 1 });
    agentLists.value = data.lists;
    agentLoading.value = false;
};

const open = () => {
    popupRef.value?.open();
    getAgentFn();
};

const close = () => {
    emit("close");
};

const handleConfirm = async () => {
    await formRef.value?.validate();
    try {
        await saveWeChatAi(formData);
        popupRef.value?.close();
        feedback.msgSuccess("保存成功");
        emit("success");
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const getDetail = async (wechat_id: string) => {
    const data = await getWeChatAi({ wechat_id });
    setFormData(data, formData);
};

defineExpose({
    open,
    getDetail,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped></style>
