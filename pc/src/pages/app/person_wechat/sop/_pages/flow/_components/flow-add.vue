<template>
    <popup ref="popupRef" width="460px" async :confirm-loading="isLock" @close="close" @confirm="lockFn">
        <div class="space-y-6">
            <div class="flex flex-col items-center text-center py-2">
                <div
                    class="w-14 h-14 rounded-2xl bg-[#0065fb]/5 text-primary flex items-center justify-center mb-3 shadow-light border border-[#0065fb]/10">
                    <Icon name="el-icon-Files" :size="28" />
                </div>
                <h4 class="text-[16px] font-black text-slate-800">定义新的客户培育路径</h4>
                <p class="text-xs text-slate-400 mt-1 font-medium">通过标准化流程，提升私域转化效率</p>
            </div>

            <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl flex gap-3">
                <Icon name="local-icon-tip" color="var(--color-primary)" :size="18" class="mt-0.5" />
                <div class="text-xs leading-relaxed text-slate-500 font-medium">
                    流程名称需满足：
                    <span class="text-primary">唯一性</span>（不重复）、 <span class="text-primary">纯汉字</span>、
                    且字数不超过 <span class="text-primary">15字</span>。
                </div>
            </div>

            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                <ElFormItem label="流程识别名称" prop="flow_name">
                    <ElInput
                        v-model="formData.flow_name"
                        placeholder="例如：高净值客户转化流程"
                        maxlength="15"
                        show-word-limit
                        class="custom-input">
                        <template #prefix>
                            <Icon name="el-icon-EditPen" color="var(--slate-400)" />
                        </template>
                    </ElInput>
                </ElFormItem>
            </ElForm>
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
        { required: true, message: "请输入流程名称", trigger: "blur" },
        {
            validator: (rule: any, value: any, callback: any) => {
                if (!/^[\u4e00-\u9fa5]+$/.test(value)) {
                    callback(new Error("仅支持输入汉字名称"));
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
        await sopFlowAdd(formData);
        feedback.msgSuccess("流程初始化成功");
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        // feedback.msgError 已在全局处理
    }
});

const open = () => {
    formData.flow_name = ""; // 重置表单
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>
