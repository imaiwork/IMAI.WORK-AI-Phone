<template>
    <popup
        ref="popupRef"
        title="新建客户流程"
        width="500px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <div>
            <div class="bg-primary-light-9 p-2 rounded-xl flex items-center gap-2">
                <Icon name="local-icon-tip" color="var(--color-primary)" :size="20"></Icon>
                <div class="text-[#636363]">
                    请注意，流程名称<span class="text-error">不可重复</span>；流程名称请勿超过<span class="text-error"
                        >15个汉字</span
                    >；
                </div>
            </div>
            <div class="mt-4">
                <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                    <ElFormItem label="流程名称" prop="flow_name">
                        <ElInput v-model="formData.flow_name" placeholder="请输入流程名称" maxlength="15" />
                    </ElFormItem>
                </ElForm>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { sopFlowAdd } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    flow_name: "",
});
const rules = {
    flow_name: [
        { required: true, message: "请输入流程名称" },
        {
            validator: (rule: any, value: any, callback: any) => {
                if (!/^[\u4e00-\u9fa5]+$/.test(value)) {
                    callback(new Error("流程名称只能是汉字"));
                }
                callback();
            },
        },
    ],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    try {
        await sopFlowAdd(formData);
        feedback.msgSuccess("流程创建成功");
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
});

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped></style>
