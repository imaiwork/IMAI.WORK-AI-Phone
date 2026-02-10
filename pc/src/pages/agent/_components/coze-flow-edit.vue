<template>
    <popup
        ref="popupRef"
        async
        width="1120px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        body-class="flow-edit-container"
        :show-close="false">
        <div class="relative bg-[#FDFDFE] rounded-[24px] overflow-hidden">
            <div
                class="relative w-full h-[180px] bg-cover bg-center transition-all duration-500"
                :style="{ backgroundImage: `url(${formData.bg_image || CozeBg})` }">
                <div class="absolute inset-0 bg-gradient-to-b from-black/30 to-black/10"></div>
                <button
                    class="absolute right-4 top-4 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 transition-all text-white backdrop-blur-md"
                    @click="close">
                    <Icon name="el-icon-Close" :size="18" />
                </button>

                <div class="absolute inset-0 flex flex-col items-center justify-center pt-4">
                    <agent-logo v-model="formData.avatar" />
                    <div class="mt-4">
                        <upload :limit="1" show-progress :show-file-list="false" @success="getBgSuccessImage">
                            <div class="bg-glass-btn group">
                                <span class="mr-1 group-hover:rotate-12 transition-transform leading-[0]">
                                    <Icon name="el-icon-Picture" :size="14" />
                                </span>
                                <span>更换封面背景</span>
                            </div>
                        </upload>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <div class="mb-6">
                    <h3 class="text-xl font-[900] text-[#0F172A]">配置 Coze 工作流</h3>
                    <p class="text-xs text-[#94A3B8] font-medium mt-1 uppercase tracking-widest">
                        Workflow input & output orchestration
                    </p>
                </div>

                <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
                    <div class="flex gap-x-8">
                        <div class="w-[320px] flex-shrink-0 space-y-2">
                            <div class="p-5 rounded-2xl bg-white border border-[#F1F5F9] shadow-sm">
                                <div class="flex items-center gap-2 mb-4 text-primary">
                                    <Icon name="el-icon-InfoFilled" />
                                    <span class="text-xs font-[900] uppercase">基础信息</span>
                                </div>
                                <ElFormItem label="工作流名称" prop="name">
                                    <ElInput v-model="formData.name" class="custom-input" placeholder="请输入名称" />
                                </ElFormItem>
                                <ElFormItem label="功能介绍" prop="introduced">
                                    <ElInput
                                        v-model="formData.introduced"
                                        type="textarea"
                                        placeholder="描述该工作流的作用..."
                                        resize="none"
                                        :rows="4"
                                        class="custom-textarea" />
                                </ElFormItem>
                                <ElFormItem label="Bot ID" prop="coze_id">
                                    <ElInput
                                        v-model="formData.coze_id"
                                        class="custom-input"
                                        placeholder="Coze Agent ID" />
                                </ElFormItem>
                                <ElFormItem label="响应模式" prop="stream">
                                    <div
                                        class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-[#F1F5F9]">
                                        <span class="text-xs font-medium text-[#64748B]">流式输出</span>
                                        <ElSwitch
                                            v-model="formData.stream"
                                            :active-value="1"
                                            :inactive-value="0"
                                            disabled
                                            class="brand-switch" />
                                    </div>
                                </ElFormItem>
                            </div>
                        </div>

                        <div class="flex-1 space-y-6 min-w-0">
                            <div class="config-section">
                                <div class="section-header">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-4 bg-primary rounded-full"></div>
                                        <span class="font-[900] text-[#0F172A]">输入参数配置</span>
                                        <span class="text-[11px] text-[#94A3B8] font-normal">(Inputs)</span>
                                    </div>
                                    <ElButton
                                        type="primary"
                                        size="small"
                                        class="!rounded-lg !bg-primary border-none shadow-md shadow-primary/20"
                                        @click="handleAddRow(formData.inputs, 'inputs', { required: 'true' })">
                                        <Icon name="el-icon-Plus" class="mr-1" />新增字段
                                    </ElButton>
                                </div>
                                <div class="table-wrapper">
                                    <ElTable
                                        ref="inputTableRef"
                                        :data="formData.inputs"
                                        v-draggable="draggableInputOptions"
                                        :cell-style="{ borderBottom: 'none' }"
                                        max-height="240px">
                                        <ElTableColumn width="40" align="center">
                                            <template #default>
                                                <div class="move-icon">
                                                    <Icon name="el-icon-Rank" />
                                                </div>
                                            </template>
                                        </ElTableColumn>
                                        <ElTableColumn label="参数显示名称" prop="name">
                                            <template #default="{ row }"
                                                ><ElInput
                                                    v-model="row.name"
                                                    placeholder="如：用户姓名"
                                                    class="custom-input"
                                            /></template>
                                        </ElTableColumn>
                                        <ElTableColumn label="字段 Key" prop="fields">
                                            <template #default="{ row }"
                                                ><ElInput
                                                    v-model="row.fields"
                                                    placeholder="如：user_name"
                                                    class="custom-input"
                                            /></template>
                                        </ElTableColumn>
                                        <ElTableColumn label="数据类型" width="120">
                                            <template #default="{ row }">
                                                <ElSelect v-model="row.type" class="custom-select">
                                                    <ElOption
                                                        v-for="item in formFieldSelect"
                                                        :label="item"
                                                        :value="item"
                                                        :key="item" />
                                                </ElSelect>
                                            </template>
                                        </ElTableColumn>
                                        <ElTableColumn label="必填" width="120" align="center">
                                            <template #default="{ row }">
                                                <ElCheckbox
                                                    v-model="row.required"
                                                    true-label="true"
                                                    false-label="false"
                                                    class="brand-checkbox"
                                                    >必填</ElCheckbox
                                                >
                                            </template>
                                        </ElTableColumn>
                                        <ElTableColumn label="操作" width="70" align="center">
                                            <template #default="{ $index }">
                                                <div class="del-btn" @click="handleDeleteRow(formData.inputs, $index)">
                                                    <Icon name="el-icon-Delete" />
                                                </div>
                                            </template>
                                        </ElTableColumn>
                                    </ElTable>
                                </div>
                            </div>

                            <div class="config-section">
                                <div class="section-header">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-4 bg-primary rounded-full"></div>
                                        <span class="font-[900] text-[#0F172A]">输出结果配置</span>
                                        <span class="text-[11px] text-[#94A3B8] font-normal">(Outputs)</span>
                                    </div>
                                    <ElButton
                                        type="primary"
                                        size="small"
                                        class="!rounded-lg !bg-primary border-none shadow-md shadow-primary/20"
                                        @click="handleAddRow(formData.outputs, 'outputs')">
                                        <Icon name="el-icon-Plus" class="mr-1" />新增字段
                                    </ElButton>
                                </div>
                                <div class="table-wrapper">
                                    <ElTable
                                        ref="outputTableRef"
                                        :data="formData.outputs"
                                        v-draggable="draggableOutputOptions"
                                        max-height="240px">
                                        <ElTableColumn width="40" align="center">
                                            <template #default>
                                                <div class="move-icon">
                                                    <Icon name="el-icon-Rank" />
                                                </div>
                                            </template>
                                        </ElTableColumn>
                                        <ElTableColumn label="结果名称" prop="name">
                                            <template #default="{ row }"
                                                ><ElInput
                                                    v-model="row.name"
                                                    placeholder="结果标题"
                                                    class="custom-input"
                                            /></template>
                                        </ElTableColumn>
                                        <ElTableColumn label="字段 Key" prop="fields">
                                            <template #default="{ row }"
                                                ><ElInput
                                                    v-model="row.fields"
                                                    placeholder="output_key"
                                                    class="custom-input"
                                            /></template>
                                        </ElTableColumn>
                                        <ElTableColumn label="数据类型" width="120">
                                            <template #default="{ row }">
                                                <ElSelect v-model="row.type" class="custom-select">
                                                    <ElOption
                                                        v-for="item in formFieldSelect"
                                                        :label="item"
                                                        :value="item"
                                                        :key="item" />
                                                </ElSelect>
                                            </template>
                                        </ElTableColumn>
                                        <ElTableColumn label="操作" width="70" align="center">
                                            <template #default="{ $index }">
                                                <div class="del-btn" @click="handleDeleteRow(formData.outputs, $index)">
                                                    <Icon name="el-icon-Delete" />
                                                </div>
                                            </template>
                                        </ElTableColumn>
                                    </ElTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElForm>

                <div class="flex justify-center mt-10">
                    <ElButton
                        type="primary"
                        class="!h-[54px] !w-[360px] !rounded-[18px] !bg-primary border-none !text-white !font-[900] !text-base shadow-xl shadow-primary/30 transition-all active:scale-[0.98]"
                        :loading="isLock"
                        @click="lockFn">
                        保存工作流配置
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { cozeAgentAdd, cozeAgentUpdate, getCozeAgentDetail } from "@/api/agent";
import { uploadImage } from "@/api/app";
import { CozeTypeEnum, FormFieldTypeEnum } from "@/pages/agent/_enums";
import { useAppStore } from "@/stores/app";
import CozeBg from "@/assets/images/coze_bg.png";
import AgentLogo from "./agent-logo.vue";
// 为组件命名，便于调试
defineOptions({ name: "CozeFlowEdit" });

const emit = defineEmits(["close", "success"]);

const appStore = useAppStore();

const getWebSiteLogo = computed(() => {
    const { shop_logo } = appStore.getWebsiteConfig || {};
    return shop_logo;
});

// Refs
const popupRef = shallowRef();
const formRef = shallowRef();
const inputTableRef = shallowRef();
const outputTableRef = shallowRef();

// 表单字段类型选项
const formFieldSelect = Object.values(FormFieldTypeEnum);

// 初始化表单数据结构
const initialFormData = () => ({
    id: "",
    name: "",
    type: CozeTypeEnum.FLOW,
    introduced: "",
    bg_image: "",
    coze_id: "",
    stream: 0, // 工作流默认为非流式
    avatar: getWebSiteLogo.value,
    permissions: 0,
    output_key: "",
    outputs: [],
    inputs: [],
});

const formData = reactive(initialFormData());

// 表单验证规则
const rules = {
    name: [{ required: true, message: "请输入智能体名称" }],
    introduced: [{ required: true, message: "请输入智能体介绍" }],
    coze_id: [{ required: true, message: "请输入Coze智能体ID" }],
    avatar: [{ required: true, message: "请上传智能体logo" }],
    inputs: [{ required: true, message: "请配置输入参数" }],
    outputs: [{ required: true, message: "请配置输出参数" }],
    output_key: [{ required: true, message: "请输入Output_key，按顺序用英文;隔开" }],
};

/**
 * @description 背景图片上传成功回调
 */
const getBgSuccessImage = (res: any) => {
    const { uri } = res.data;
    formData.bg_image = uri;
};

/**
 * @description 上传默认背景图
 */
const uploadDefaultBackground = async () => {
    try {
        const file = await urlToFile(CozeBg, "coze_bg.png");
        const { uri } = await uploadImage({ file });
        formData.bg_image = uri;
    } catch (error) {
        console.error("默认背景上传失败:", error);
    }
};

/**
 * @description 创建表格拖拽排序的配置
 * @param itemsRef - 响应式数组的引用
 */
const createDraggableOptions = (itemsRef: Ref<any[]>) => [
    {
        selector: "tbody",
        options: {
            animation: 150,
            handle: ".move-icon", // 指定拖拽手柄
            onEnd: ({ newIndex, oldIndex }: any) => {
                const list = itemsRef.value;
                const movedItem = list.splice(oldIndex, 1)[0];
                list.splice(newIndex, 0, movedItem);
                // 强制更新视图
                itemsRef.value = [...list];
            },
        },
    },
];

const draggableInputOptions = createDraggableOptions(toRef(formData, "inputs"));
const draggableOutputOptions = createDraggableOptions(toRef(formData, "outputs"));

/**
 * @description 新增表格行
 * @param items - 目标数组
 * @param key - 表格的key ('inputs' or 'outputs')
 * @param defaults - 新增行的默认值
 */
const handleAddRow = (items: any[], key: "inputs" | "outputs", defaults = {}) => {
    const newItem = {
        name: "",
        type: FormFieldTypeEnum.INPUT,
        fields: "",
        value: "",
        ...defaults,
    };
    items.push(newItem);
    // 滚动到新增行
    nextTick(() => {
        const tableRef = key === "inputs" ? inputTableRef.value : outputTableRef.value;
        tableRef?.setScrollTop(items.length * 50);
    });
};

/**
 * @description 删除表格行
 */
const handleDeleteRow = (items: any[], index: number) => {
    items.splice(index, 1);
};

/**
 * @description 验证表格数据是否完整且字段名不重复
 * @param items - 要验证的数组
 * @param name - 配置名称（用于错误提示）
 */
const validateTableData = (items: any[], name: string): boolean => {
    if (items.some((item) => !item.name || !item.fields)) {
        feedback.msgError(`请填写完整的${name}参数`);
        return false;
    }
    const fieldValues = items.map((item) => item.fields);
    if (new Set(fieldValues).size !== fieldValues.length) {
        feedback.msgError(`${name}的字段名称不能重复`);
        return false;
    }
    return true;
};

// 保存操作（防重）
const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.avatar) {
        return feedback.msgError("请上传智能体头像");
    }

    await formRef.value?.validate();

    if (!validateTableData(formData.inputs, "输入配置")) return;
    if (!validateTableData(formData.outputs, "输出配置")) return;

    if (!formData.bg_image) {
        await uploadDefaultBackground();
    }

    try {
        const apiCall = formData.id ? cozeAgentUpdate : cozeAgentAdd;
        await apiCall(formData);
        feedback.msgSuccess(`${formData.id ? "编辑" : "添加"}成功`);
        close();
        emit("success");
    } catch (error) {
        feedback.msgError(error as string);
    }
});

// 打开弹窗（重置表单）
const open = async () => {
    Object.assign(formData, initialFormData());
    formRef.value?.clearValidate();
    popupRef.value.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
    popupRef.value.close();
};

/**
 * @description 获取详情并填充表单
 */
const getDetail = async (id: any) => {
    try {
        const res = await getCozeAgentDetail({ id });
        setFormData(res, formData);
        // outputs 和 inputs 是JSON字符串，需要解析
        formData.outputs = isJson(res.outputs) ? JSON.parse(res.outputs) : [];
        formData.inputs = isJson(res.inputs) ? JSON.parse(res.inputs) : [];
    } catch (error) {
        feedback.msgError("获取详情失败");
    }
};

// 暴露方法
defineExpose({
    open,
    getDetail,
});
</script>

<style lang="scss" scoped>
.config-section {
    @apply flex flex-col gap-3;
    .section-header {
        @apply flex items-center justify-between px-2;
    }
}

.bg-glass-btn {
    @apply flex items-center justify-center px-4 py-1.5 rounded-full bg-[#ffffff]/20 backdrop-blur-md  border border-[#ffffff]/30 text-white text-xs font-black cursor-pointer transition-all hover:bg-[#ffffff]/40;
}

:deep(.el-table) {
    thead th.el-table__cell.is-leaf {
        border-top: none;
    }
    .el-table--border .el-table__inner-wrapper:after,
    .el-table--border:after,
    .el-table--border:before,
    .el-table__inner-wrapper:before {
        background-color: transparent;
    }
}

.table-wrapper {
    @apply border border-br rounded-2xl overflow-hidden bg-white;
}

.move-icon {
    @apply text-[#CBD5E1] cursor-move hover:text-primary transition-colors;
}
.del-btn {
    @apply text-[#CBD5E1] cursor-pointer hover:text-[#EF4444] transition-colors;
}

.brand-checkbox {
    :deep(.el-checkbox__input.is-checked .el-checkbox__inner) {
        @apply bg-primary border-primary;
    }
}
.brand-switch {
    --el-switch-on-color: #0065fb;
}
</style>
