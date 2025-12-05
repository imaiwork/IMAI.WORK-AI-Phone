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
                    请注意，若新增标签，切记名称<span class="text-error">不可重复</span>；且勿超过<span
                        class="text-error"
                        >8个汉字</span
                    >；
                </div>
            </div>
            <div class="mt-4">
                <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                    <ElFormItem label="阶段名称" prop="sub_stage_name">
                        <ElInput v-model="formData.sub_stage_name" placeholder="请输入阶段名称" maxlength="8" />
                    </ElFormItem>
                    <!-- 排序 -->
                    <ElFormItem label="排序" prop="sort">
                        <ElInput
                            v-model="formData.sort"
                            type="number"
                            v-number-input="{ min: 0, max: 999 }"
                            placeholder="请输入排序" />
                        <div class="text-xs text-gray-500">排序越大越靠前，最大值为999</div>
                    </ElFormItem>
                </ElForm>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { sopAddStage, sopUpdateStage } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    flow_id: "",
    id: "",
    sub_stage_name: "",
    sort: "",
});
const rules = {
    sub_stage_name: [
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
        formData.id ? await sopUpdateStage(formData) : await sopAddStage(formData);
        popupRef.value?.close();
        feedback.msgSuccess("新增成功");
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
    setFormData: (data) => setFormData(data, formData),
});
</script>

<style scoped></style>
