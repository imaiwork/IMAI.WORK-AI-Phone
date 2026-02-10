<template>
    <popup
        ref="popupRef"
        :title="formData.todo_type === 0 ? '新建待办任务' : '设置自动跟进'"
        async
        width="520px"
        confirm-button-text="保存并生效"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="mb-6 flex items-start gap-3 p-4 bg-[#0065fb]/5 rounded-2xl border border-[#0065fb]/10">
            <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 flex items-center justify-center shrink-0">
                <Icon
                    :name="formData.todo_type === 0 ? 'el-icon-Edit' : 'local-icon-send_plane_fill'"
                    color="var(--color-primary)"
                    :size="20" />
            </div>
            <div class="flex flex-col">
                <span class="text-[14px] font-black text-slate-800">
                    {{ formData.todo_type === 0 ? "记录待办事项" : "配置自动跟进计划" }}
                </span>
                <span class="text-xs text-slate-500 font-medium">系统将在设定的时间准时提醒或执行操作</span>
            </div>
        </div>

        <ElForm ref="formRef" :model="formData" label-position="top" :rules="formRules" class="custom-styled-form">
            <ElFormItem prop="todo_content" class="!mb-6">
                <template #label>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[14px] font-medium text-slate-700">内容描述</span>
                        <span class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">Content</span>
                    </div>
                </template>
                <ElInput
                    v-model="formData.todo_content"
                    type="textarea"
                    :rows="4"
                    resize="none"
                    class="premium-textarea"
                    :placeholder="`请详细描述${
                        formData.todo_type == 0 ? '需要记录的待办事项' : '自动跟进的执行内容'
                    }...`" />
            </ElFormItem>

            <ElFormItem prop="todo_time" class="!mb-2">
                <template #label>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[14px] font-medium text-slate-700">提醒时间</span>
                        <span class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">Schedule</span>
                    </div>
                </template>
                <ElDatePicker
                    class="premium-date-picker !w-full"
                    v-model="formData.todo_time"
                    type="datetime"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    :disabled="!!formData.id"
                    :disabled-date="getTodoDisabledDate"
                    placeholder="点击设置任务执行的具体时间"
                    :prefix-icon="renderCalendarIcon" />
            </ElFormItem>
        </ElForm>

        <div class="mt-4 flex items-center justify-center gap-2 text-slate-400 text-[11px] font-medium">
            <Icon name="el-icon-Lock" :size="12" />
            <span>任务设置成功后，将自动同步至云端提醒</span>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { addTodo, editTodo } from "@/api/person_wechat";
import { dayjs, type FormInstance } from "element-plus";
import { h } from "vue";
import { Calendar } from "@element-plus/icons-vue";

const props = defineProps<{
    wechatId: string;
    friendId: string;
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "confirm"): void;
}>();

const popupRef = ref<InstanceType<typeof Popup> | null>(null);
const formData = reactive({
    id: "",
    todo_type: 0,
    todo_content: "",
    todo_time: "",
    wechat_id: props.wechatId,
    friend_id: props.friendId,
});
const formRef = ref<FormInstance>();
const formRules = {
    todo_content: [{ required: true, message: "请输入待办内容" }],
    todo_time: [{ required: true, message: "请选择时间" }],
};

const renderCalendarIcon = () => h(Calendar, { style: "color: #0065fb" });

const getTodoDisabledDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

const confirm = async () => {
    await formRef.value?.validate();
    try {
        formData.id ? await editTodo(formData) : await addTodo(formData);
        close();
        emit("confirm");
        feedback.msgSuccess("添加成功");
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn, isLock } = useLockFn(confirm);

const open = (type: number) => {
    formData.todo_type = type;
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

<style scoped lang="scss">
:deep(.custom-styled-form) {
    .el-form-item__label {
        @apply pb-0 mb-2 leading-none;
    }

    .el-textarea__inner,
    .el-input__wrapper {
        @apply rounded-2xl bg-slate-50 border border-slate-100 shadow-[none] transition-all duration-300 p-4;

        &:hover {
            @apply bg-white border-[#0065fb]/30;
            box-shadow: 0 4px 12px rgba(0, 101, 251, 0.05) !important;
        }

        &:focus-within {
            @apply bg-white border-primary ring-4 ring-[#0065fb]/10;
        }
    }

    .el-input__wrapper {
        @apply h-[52px];
    }
}

:deep(.el-form-item.is-error) {
    .el-textarea__inner,
    .el-input__wrapper {
        @apply border-red-200 bg-[#fef2f2]/30;
    }
}
</style>
