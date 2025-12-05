<template>
    <popup
        ref="popupRef"
        async
        width="550px"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        :show-close="false">
        <div>
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <div class="text-2xl font-bold mb-5">{{ formData.id ? "编辑" : "添加" }}关键词回复</div>
            <!-- 表单 -->
            <el-form ref="formRef" :model="formData" :rules="formRules" label-position="top">
                <el-form-item label="匹配方式" prop="match_type">
                    <el-radio-group v-model="formData.match_type">
                        <el-radio :value="0">模糊匹配</el-radio>
                        <el-radio :value="1">精确匹配</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="匹配内容" prop="keyword">
                    <el-input
                        v-model="formData.keyword"
                        placeholder="回复内容支持多词组匹配，以分号相隔; 例如：你好;你好啊;你好呀" />
                </el-form-item>
                <el-form-item label="回复内容" prop="reply">
                    <!-- 富文本内容编辑器 -->
                    <div class="h-[600px] w-full overflow-hidden">
                        <AddContent ref="addContentRef" :type="2" v-model="formData.reply" :show-preview="false" />
                    </div>
                </el-form-item>
            </el-form>
            <!-- 操作按钮 -->
            <div class="flex">
                <el-button class="!rounded-full flex-1 !h-[50px]" @click="close">取消</el-button>
                <el-button type="primary" class="!rounded-full flex-1 !h-[50px]" :loading="isLock" @click="lockFn">
                    保存
                </el-button>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { addRobotKeywords, updateRobotKeywords } from "@/api/ai_application/agent";
import { type FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";
import { setFormData } from "@/utils/util";
import AddContent from "./add-content.vue";

/**
 * @description 添加或编辑关键词回复的弹窗组件
 */

const emit = defineEmits(["close", "success"]);
const route = useRoute();

const popupRef = ref<InstanceType<typeof Popup>>();

// 弹窗模式：add 或 edit
const mode = ref("add");

// 表单ref和数据
const formRef = ref<FormInstance>();
const formData = reactive({
    id: "",
    match_type: 0, // 0-模糊匹配 1-精确匹配
    keyword: "",
    reply: [] as any[], // 回复内容，由AddContent组件管理
});

// 表单验证规则
const formRules = {
    keyword: [{ required: true, message: "请输入匹配内容" }],
    reply: [{ required: true, message: "请输入回复内容" }],
};

/**
 * @description 打开弹窗
 * @param type - 模式 ('add' or 'edit')
 */
const open = (type?: "add" | "edit") => {
    mode.value = type || "add";
    popupRef.value?.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

/**
 * @description 提交表单
 */
const handleConfirm = async () => {
    await formRef.value?.validate();
    try {
        const apiCall = formData.id ? updateRobotKeywords : addRobotKeywords;
        const params = {
            ...formData,
            robot_id: route.query.id,
        };
        await apiCall(params);

        feedback.msgSuccess(`${mode.value === "add" ? "新增" : "编辑"}成功`);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        feedback.msgError((error as string) || `${mode.value === "add" ? "新增" : "编辑"}失败`);
    }
};

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(handleConfirm);

/**
 * @description 设置表单数据 (用于编辑)
 * @param data - 从API获取的关键词数据
 * @summary 特别处理reply字段，将API返回的字符串类型type转换为整数
 */
const setFormDataForEdit = (data: any) => {
    setFormData(data, formData);
    if (Array.isArray(formData.reply)) {
        formData.reply = formData.reply.map((item: any) => ({
            ...item,
            type: parseInt(item.type, 10),
        }));
    }
};

// 暴露方法
defineExpose({
    open,
    setFormData: setFormDataForEdit,
});
</script>

<style scoped></style>
