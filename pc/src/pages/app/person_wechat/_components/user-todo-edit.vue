<template>
    <popup
        ref="popupRef"
        :title="formData.todo_type == 0 ? '添加待办' : '添加自动跟进'"
        async
        width="487px"
        confirm-button-text="确认创建"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <ElForm ref="formRef" :model="formData" label-position="top" :rules="formRules">
            <ElFormItem
                :label="`请输入需要记录的${formData.todo_type == 0 ? '代办' : '自动跟进'}内容`"
                prop="todo_content">
                <ElInput
                    v-model="formData.todo_content"
                    type="textarea"
                    :rows="5"
                    :placeholder="`请输入${formData.todo_type == 0 ? '代办' : '自动跟进'}内容`" />
            </ElFormItem>
            <ElFormItem label="日期时间" prop="todo_time">
                <ElDatePicker
                    class="!w-full"
                    v-model="formData.todo_time"
                    type="datetime"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    :disabled="!!formData.id"
                    :disabled-date="getTodoDisabledDate"
                    placeholder="点击选择时间" />
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { addTodo, editTodo } from "@/api/person_wechat";
import { dayjs, type FormInstance } from "element-plus";

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

<style scoped></style>
