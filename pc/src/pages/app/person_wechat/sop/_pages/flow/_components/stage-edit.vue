<template>
    <popup
        ref="popupRef"
        :title="formData.id ? '编辑阶段配置' : '新建流程阶段'"
        width="480px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <div class="space-y-6">
            <div class="bg-[#0065fb]/5 border border-[#0065fb]/10 p-4 rounded-[16px] flex items-start gap-3">
                <div
                    class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center text-primary flex-shrink-0">
                    <Icon name="el-icon-InfoFilled" :size="18" />
                </div>
                <div class="text-xs leading-relaxed">
                    <p class="text-primary font-black mb-0.5">命名规范提示：</p>
                    <p class="text-slate-500 font-medium">
                        阶段名称需为 <span class="text-primary font-medium">纯汉字</span>，且长度
                        <span class="text-primary font-medium">不可超过 8 个字</span
                        >。系统建议按转化逻辑命名（如：初步接触、核心意向）。
                    </p>
                </div>
            </div>

            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                <ElFormItem label="阶段展示名称" prop="sub_stage_name">
                    <ElInput
                        v-model="formData.sub_stage_name"
                        placeholder="例如：初步建立信任"
                        maxlength="8"
                        show-word-limit
                        class="custom-input">
                        <template #prefix>
                            <Icon name="el-icon-CollectionTag" color="var(--slate-400)" />
                        </template>
                    </ElInput>
                </ElFormItem>

                <ElFormItem prop="sort">
                    <template #label>
                        <div class="flex items-center gap-1">
                            <span>流程权重排序</span>
                            <ElTooltip content="数值越大在流程中显示位置越靠前" placement="top">
                                <Icon name="el-icon-QuestionFilled" class="text-slate-300 cursor-help" :size="14" />
                            </ElTooltip>
                        </div>
                    </template>
                    <div class="flex items-center gap-4">
                        <ElInputNumber
                            v-model="formData.sort"
                            :min="0"
                            :max="999"
                            controls-position="right"
                            placeholder="0-999"
                            class="!w-full modern-number-input" />
                        <div
                            class="flex-shrink-0 text-[11px] text-slate-400 font-medium bg-slate-50 px-3 py-2 rounded-lg border border-slate-100">
                            MAX: 999
                        </div>
                    </div>
                </ElFormItem>
            </ElForm>
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
    sort: 0,
});

const rules = {
    sub_stage_name: [
        { required: true, message: "请输入阶段名称", trigger: "blur" },
        {
            validator: (rule: any, value: any, callback: any) => {
                if (!/^[\u4e00-\u9fa5]+$/.test(value)) {
                    callback(new Error("仅支持输入汉字"));
                } else {
                    callback();
                }
            },
            trigger: "change",
        },
    ],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const { lockFn, isLock } = useLockFn(async () => {
    const valid = await formRef.value?.validate().catch(() => false);
    if (!valid) return;

    try {
        formData.id ? await sopUpdateStage(formData) : await sopAddStage(formData);
        popupRef.value?.close();
        feedback.msgSuccess(formData.id ? "修改成功" : "创建成功");
        emit("success");
    } catch (error) {
        // feedback.msgError 已在基础库处理
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

<style scoped lang="scss"></style>
