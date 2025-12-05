<template>
    <popup
        ref="popupRef"
        async
        width="1068px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        body-class="!p-0"
        style="padding: 0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 z-10 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <!-- 顶部背景和Logo -->
            <div
                class="w-full h-[235px] bg-cover bg-no-repeat flex flex-col justify-center items-center"
                :style="{ backgroundImage: `url(${formData.bg_image || CozeBg})`, backgroundPositionY: '-80px' }">
                <div class="mt-10">
                    <agent-logo v-model="formData.avatar" />
                </div>
                <div class="mt-[25px]">
                    <upload :limit="1" show-progress :show-file-list="false" @success="getBgSuccessImage">
                        <div
                            class="w-[72px] h-[29px] flex items-center justify-center rounded-[32px] bg-[#00000066] text-white">
                            更换背景
                        </div>
                    </upload>
                </div>
            </div>
            <!-- 表单主体 -->
            <div class="p-8">
                <div class="text-lg font-bold">新增Coze工作流</div>
                <div class="text-xs text-[#0000004d] mt-2">快速搭建对话式智能体</div>
                <el-form ref="formRef" :model="formData" :rules="rules" label-position="top" class="mt-6">
                    <div class="flex w-full gap-x-4">
                        <!-- 左侧基础信息 -->
                        <div class="w-[351px] flex-shrink-0">
                            <el-form-item label="智能体名称" prop="name">
                                <el-input v-model="formData.name" class="!h-10" placeholder="请输入名称" />
                            </el-form-item>
                            <el-form-item label="智能体介绍" prop="introduced">
                                <ElInput
                                    v-model="formData.introduced"
                                    type="textarea"
                                    placeholder="请输入智能体的说明"
                                    resize="none"
                                    :rows="6" />
                            </el-form-item>
                            <el-form-item label="智能体分类" prop="agent_cate_id">
                                <el-select
                                    v-model="formData.agent_cate_id"
                                    class="!h-10"
                                    placeholder="请选择智能体分类"
                                    filter-method
                                    remote
                                    reserve-keyword
                                    :remote-method="getCategoryList">
                                    <el-option
                                        :label="item.name"
                                        :value="item.id"
                                        v-for="item in categoryList"
                                        :key="item.id">
                                    </el-option>
                                </el-select>
                                <router-link
                                    class="text-primary"
                                    :to="getRoutePath('ai_application.agent/cate')"
                                    target="_blank">
                                    去创建分类
                                </router-link>
                            </el-form-item>
                            <el-form-item label="Coze智能体ID" prop="coze_id">
                                <el-input v-model="formData.coze_id" class="!h-10" placeholder="请输入Coze智能体ID" />
                            </el-form-item>
                            <el-form-item label="输出方式" prop="stream">
                                <el-radio-group v-model="formData.stream">
                                    <el-radio :value="1" disabled>流式输出</el-radio>
                                    <el-radio :value="0">直接返回</el-radio>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="扣费方式">
                                <el-radio-group v-model="formData.deduction">
                                    <el-radio label="Token扣费" :value="0" disabled></el-radio>
                                    <el-radio label="按次数扣费" :value="1"></el-radio>
                                </el-radio-group>
                                <el-input-number
                                    v-model="formData.tokens"
                                    :min="0"
                                    controls-position="right"
                                    disabled-scientific>
                                    <template #append>算力/次</template>
                                </el-input-number>
                            </el-form-item>
                        </div>
                        <!-- 右侧输入输出配置 -->
                        <div class="flex-1">
                            <el-scrollbar>
                                <!-- 输入配置 -->
                                <el-form-item label="输入配置（每个字段值不能重复）" prop="inputs" class="relative">
                                    <div class="absolute right-0 -top-[35px]">
                                        <el-button
                                            type="primary"
                                            size="small"
                                            @click="handleAddRow(formData.inputs, 'inputs', { required: 'true' })">
                                            新增
                                        </el-button>
                                    </div>
                                    <div class="w-full border border-[var(--el-border-color)] rounded-lg">
                                        <el-table
                                            ref="inputTableRef"
                                            class="inout-table"
                                            :data="formData.inputs"
                                            stripe
                                            max-height="200px"
                                            :row-style="{ height: '40px' }"
                                            :header-cell-style="{ height: '40px' }"
                                            v-draggable="draggableInputOptions">
                                            <el-table-column width="50px">
                                                <template #default>
                                                    <div class="move-icon cursor-move">
                                                        <Icon name="el-icon-Rank" />
                                                    </div>
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="名称">
                                                <template #default="{ row }">
                                                    <el-input
                                                        v-model="row.name"
                                                        placeholder="请输入"
                                                        clearable
                                                        maxlength="30" />
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="类型">
                                                <template #default="{ row }">
                                                    <el-select v-model="row.type" placeholder="请选择">
                                                        <el-option
                                                            v-for="item in formFieldSelect"
                                                            :label="item"
                                                            :value="item"
                                                            :key="item"></el-option>
                                                    </el-select>
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="字段值">
                                                <template #default="{ row }">
                                                    <el-input
                                                        v-model="row.fields"
                                                        placeholder="请输入"
                                                        clearable
                                                        maxlength="30" />
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="是否必填">
                                                <template #default="{ row }">
                                                    <el-radio-group v-model="row.required">
                                                        <el-radio value="true">是</el-radio>
                                                        <el-radio value="false">否</el-radio>
                                                    </el-radio-group>
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="操作" width="80px">
                                                <template #default="{ $index }">
                                                    <el-button
                                                        type="danger"
                                                        size="small"
                                                        @click="handleDeleteRow(formData.inputs, $index)">
                                                        删除
                                                    </el-button>
                                                </template>
                                            </el-table-column>
                                        </el-table>
                                    </div>
                                </el-form-item>

                                <!-- 输出配置 -->
                                <el-form-item label="输出配置（每个字段值不能重复）" prop="outputs" class="relative">
                                    <div class="flex items-center justify-between w-full">
                                        <el-button
                                            type="primary"
                                            size="small"
                                            @click="handleAddRow(formData.outputs, 'outputs')">
                                            新增
                                        </el-button>
                                    </div>
                                    <div class="mt-3 w-full border border-[var(--el-border-color)] rounded-lg">
                                        <el-table
                                            ref="outputTableRef"
                                            :data="formData.outputs"
                                            stripe
                                            max-height="200px"
                                            :row-style="{ height: '40px' }"
                                            :header-cell-style="{ height: '40px' }"
                                            v-draggable="draggableOutputOptions">
                                            <el-table-column width="50px">
                                                <template #default>
                                                    <div class="move-icon cursor-move">
                                                        <Icon name="el-icon-Rank" />
                                                    </div>
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="名称">
                                                <template #default="{ row }">
                                                    <el-input
                                                        v-model="row.name"
                                                        placeholder="请输入"
                                                        clearable
                                                        maxlength="30" />
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="类型">
                                                <template #default="{ row }">
                                                    <el-select v-model="row.type" placeholder="请选择">
                                                        <el-option
                                                            v-for="item in formFieldSelect"
                                                            :label="item"
                                                            :value="item"
                                                            :key="item"></el-option>
                                                    </el-select>
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="字段值">
                                                <template #default="{ row }">
                                                    <el-input
                                                        v-model="row.fields"
                                                        placeholder="请输入"
                                                        clearable
                                                        maxlength="30" />
                                                </template>
                                            </el-table-column>
                                            <el-table-column label="操作" width="80px">
                                                <template #default="{ $index }">
                                                    <el-button
                                                        type="danger"
                                                        size="small"
                                                        @click="handleDeleteRow(formData.outputs, $index)">
                                                        删除
                                                    </el-button>
                                                </template>
                                            </el-table-column>
                                        </el-table>
                                    </div>
                                </el-form-item>
                            </el-scrollbar>
                        </div>
                    </div>
                </el-form>
                <!-- 保存按钮 -->
                <div class="flex justify-center mt-4">
                    <el-button
                        color="#000000"
                        class="!rounded-full !h-[50px] w-[310px] shadow-[0_6px_12px_0px_#0065FB33]"
                        :loading="isLock"
                        @click="lockFn">
                        保存
                    </el-button>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { cozeAgentAdd, cozeAgentUpdate, getCozeAgentDetail } from "@/api/ai_application/agent/coze";
import { getCateList } from "@/api/ai_application/agent/cate";
import { uploadFile } from "@/api/app";
import { isJson, setFormData, urlToFile } from "@/utils/util";
import feedback from "@/utils/feedback";
import { getRoutePath } from "@/router";
import { useLockFn } from "@/hooks/useLockFn";
import type { Ref } from "vue";
import CozeBg from "@/assets/images/coze_bg.png";
import AgentLogo from "./agent-logo.vue";
import { CozeTypeEnum, FormFieldTypeEnum } from "./enums";

// 为组件命名，便于调试
defineOptions({ name: "CozeFlowEdit" });

const emit = defineEmits(["close", "success"]);

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
    avatar: "",
    permissions: 0,
    output_key: "",
    outputs: [],
    inputs: [],
    agent_cate_id: "",
    deduction: 1,
    tokens: 0,
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
    agent_cate_id: [{ required: true, message: "请选择智能体分类" }],
};

const categoryList = ref<any[]>([]);
const getCategoryList = async (query?: any) => {
    const { lists } = await getCateList({
        name: query,
        type: 3,
        page_size: 25000,
    });
    categoryList.value = lists;
};

/**
 * @description 背景图片上传成功回调
 */
const getBgSuccessImage = (res: any) => {
    const { uri } = res;
    formData.bg_image = uri;
};

/**
 * @description 上传默认背景图
 */
const uploadDefaultBackground = async () => {
    try {
        const file = await urlToFile(CozeBg, "coze_bg.png");
        const { uri } = await uploadFile("image", { file });
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

    const apiCall = formData.id ? cozeAgentUpdate : cozeAgentAdd;
    await apiCall(formData);
    close();
    emit("success");
});

// 打开弹窗（重置表单）
const open = async () => {
    Object.assign(formData, initialFormData());
    formRef.value?.clearValidate();
    popupRef.value.open();
    getCategoryList();
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
    const res = await getCozeAgentDetail({ id });
    setFormData(res, formData);
    // outputs 和 inputs 是JSON字符串，需要解析
    formData.outputs = isJson(res.outputs) ? JSON.parse(res.outputs) : [];
    formData.inputs = isJson(res.inputs) ? JSON.parse(res.inputs) : [];
};

// 暴露方法
defineExpose({
    open,
    getDetail,
});
</script>

<style lang="scss" scoped>
:deep(.el-table) {
    border-radius: 8px;
    thead th.el-table__cell.is-leaf {
        border-top: 0;
    }
    &.el-table--fit .el-table__inner-wrapper:before {
        display: none;
    }
}
:deep(.el-radio) {
    margin-right: 10px;
}
</style>
