<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            :confirm-loading="submitting"
            width="560px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="220px">
                <el-form-item v-if="mode === 'edit'" label="代理等级">
                    <span>{{ formData.level }} 级</span>
                </el-form-item>
                <el-form-item v-else label="代理等级">
                    <span>{{ nextLevel }} 级</span>
                    <span class="text-xs text-tx-placeholder ml-2">新增等级会追加为当前最低一级</span>
                </el-form-item>

                <el-form-item label="等级名称" prop="name">
                    <el-input
                        class="ls-input"
                        v-model="formData.name"
                        placeholder="请输入等级名称"
                        clearable
                        maxlength="10"
                        show-word-limit />
                </el-form-item>

                <el-form-item label="备注">
                    <el-input
                        class="ls-input"
                        v-model="formData.remark"
                        type="textarea"
                        :rows="3"
                        placeholder="请输入备注，仅后台可见"
                        maxlength="255"
                        show-word-limit />
                </el-form-item>

                <template v-if="mode === 'edit'">
                    <el-divider content-position="left">下级人数上限</el-divider>
                    <template v-if="subLevels.length">
                        <el-form-item v-for="item in subLevels" :key="item.level" :label="`可发展 ${item.name} 上限`">
                            <el-input-number v-model="limitInputs[item.level]" :min="0" :max="999999" />
                            <span class="text-xs text-tx-placeholder ml-2">人；0 表示不限</span>
                        </el-form-item>
                    </template>
                    <div v-else class="text-xs text-tx-placeholder pl-4">
                        该等级已是最低一级，无法发展下级，无需设置上限。
                    </div>
                </template>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { addAgentGrade, setAgentGradeConfig, setAgentSubLimits } from "@/api/marketing/agent";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";

type AgentGrade = {
    level: number;
    name: string;
    remark: string;
};

type PopupMode = "add" | "edit";

const emit = defineEmits(["success", "close"]);

const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const mode = ref<PopupMode>("edit");
const submitting = ref(false);
const gradeList = ref<AgentGrade[]>([]);

const formData = reactive<AgentGrade>({
    level: 0,
    name: "",
    remark: "",
});

// 该等级可发展的下级等级 => 人数上限
const limitInputs = ref<Record<string, number>>({});

const popupTitle = computed(() => (mode.value === "add" ? "添加代理等级" : "编辑等级配置"));
const nextLevel = computed(() => Math.max(0, ...gradeList.value.map((item) => item.level)) + 1);
const subLevels = computed(() => gradeList.value.filter((item) => item.level > formData.level));

const rules = {
    name: [
        {
            required: true,
            message: "请输入等级名称",
            trigger: ["blur"],
        },
    ],
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    submitting.value = true;
    try {
        if (mode.value === "add") {
            await addAgentGrade({ name: formData.name, remark: formData.remark });
        } else {
            await setAgentGradeConfig({
                config: [{ level: formData.level, name: formData.name, remark: formData.remark }],
            });
            if (subLevels.value.length) {
                await setAgentSubLimits({ limits: { [formData.level]: limitInputs.value } });
            }
        }
        popupRef.value?.close();
        emit("success");
    } finally {
        submitting.value = false;
    }
};

const handleClose = () => emit("close");

/** 父级打开弹窗时把完整等级清单和完整 limits 传进来，编辑态额外传当前行 */
const open = (
    popupMode: PopupMode,
    allGrades: AgentGrade[],
    allLimits: Record<string, Record<string, number>>,
    row?: AgentGrade,
) => {
    mode.value = popupMode;
    gradeList.value = allGrades ?? [];

    formData.level = popupMode === "add" ? nextLevel.value : Number(row?.level ?? 0);
    formData.name = popupMode === "add" ? "" : row?.name ?? "";
    formData.remark = popupMode === "add" ? "" : row?.remark ?? "";

    const current = allLimits?.[formData.level] ?? {};
    limitInputs.value = Object.fromEntries(
        gradeList.value
            .filter((item) => item.level > formData.level)
            .map((item) => [item.level, Number(current[item.level] ?? 0)]),
    );

    popupRef.value?.open();
};

defineExpose({ open });
</script>
