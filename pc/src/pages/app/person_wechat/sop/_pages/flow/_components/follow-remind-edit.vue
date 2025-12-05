<template>
    <popup
        ref="popupRef"
        :title="mode === 'add' ? '新建跟进提醒' : '编辑跟进提醒'"
        width="610px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
            <ElFormItem label="提醒规则" prop="status">
                <ElSelect v-model="formData.status" placeholder="请选择提醒规则">
                    <ElOption label="以停留标签天数为判定规则" :value="0"></ElOption>
                    <ElOption label="以沉默天数为判定规则" :value="1"></ElOption>
                </ElSelect>
            </ElFormItem>
            <ElFormItem label="时间规则" prop="send_time">
                <div class="flex items-center gap-x-2 text-xs">
                    <span>{{ formData.status == 0 ? "停留超过" : "沉默超过" }}</span>
                    <ElInputNumber v-model="formData.judgment" size="small"></ElInputNumber>
                    <span>天后，次日</span>
                    <ElTimePicker
                        v-model="formData.send_time"
                        placeholder="请选择提醒时间"
                        size="small"
                        class="!w-[150px]"
                        value-format="HH:mm:ss"></ElTimePicker>
                </div>
            </ElFormItem>
            <ElFormItem label="发送内容" prop="content">
                <ElInput
                    v-model="formData.content"
                    placeholder="请输入提醒内容"
                    type="textarea"
                    class="w-full"
                    :rows="5"></ElInput>
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import { sopAddAutoFollow, sopUpdateAutoFollow } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import dayjs from "dayjs";
const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const mode = ref<"add" | "edit">("add");

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    flow_id: "",
    stage_id: "",
    remind_id: "",
    status: 0,
    judgment: 0,
    send_time: dayjs().add(10, "minutes").format("HH:mm:ss"),
    content: "",
});
const rules = {
    send_time: [
        { required: true, message: "请选择提醒时间", trigger: "blur" },
        {
            validator: (rule: any, value: any, callback: any) => {
                // 判断是否大于当前时间，value 是HH:mm:ss, 所以只需要判断小时、分钟、秒
                const now = new Date();
                const valueDate = new Date(`${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()} ${value}`);
                if (formData.judgment == 0 && valueDate < now) {
                    callback(new Error("提醒时间不能小于当前时间"));
                } else {
                    callback();
                }
            },
        },
    ],
    content: [{ required: true, message: "请输入提醒内容", trigger: "blur" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    try {
        formData.remind_id ? await sopUpdateAutoFollow(formData) : await sopAddAutoFollow(formData);
        popupRef.value?.close();
        emit("success");
        feedback.msgSuccess("操作成功");
    } catch (error) {
        feedback.msgError(error);
    }
});

const open = (type: "add" | "edit") => {
    mode.value = type;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setFormData: (data: any) => {
        setFormData(data, formData);
    },
});
</script>

<style scoped></style>
