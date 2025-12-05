<template>
    <ElDrawer v-model="visible" size="960px" @close="close">
        <template #header>
            <div class="border-b border-gray-200 h-[50px] flex items-center text-xl text-black font-bold">
                {{ title }}
            </div>
        </template>
        <div class="h-full flex flex-col">
            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="flex items-center gap-x-4">
                        <div class="w-1 h-[14px] bg-primary"></div>
                        <div class="flex items-center gap-x-2">
                            <span>设置后</span>
                            <ElInputNumber
                                v-model="formData.time.order_day"
                                :min="0"
                                :step="1"
                                size="small"
                                :disabled="isEdit" />
                            <span>天起，</span>
                            <ElTimePicker
                                class="!w-[120px]"
                                v-model="formData.time.push_time"
                                value-format="HH:mm"
                                placeholder="请选择时间"
                                size="small"
                                :disabled="isEdit" />
                            <span>发送</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <add-content v-model="formData.content" />
                    </div>
                </ElScrollbar>
            </div>
            <div class="pt-4 border-t border-gray-200 flex justify-end gap-x-2">
                <ElButton type="primary" :loading="isLock" @click="lockFn">确定</ElButton>
                <ElButton @click="close">取消</ElButton>
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import AddContent from "../../_components/add-content.vue";
import dayjs from "dayjs";

const props = defineProps<{
    pushDay: string;
}>();
const emit = defineEmits<{
    (e: "close"): void;
    (e: "success", data: any): void;
}>();

const visible = ref(false);
const formData = reactive({
    content_id: "",
    content: [],
    time: {
        order_day: 0,
        push_time: dayjs().add(15, "minutes").format("HH:mm"),
    },
});

const mode = ref("add");

const isEdit = computed(() => {
    return mode.value == "edit";
});

const title = computed(() => {
    return mode.value == "add" ? "添加营销内容" : "编辑营销内容";
});

const open = (type: "add" | "edit") => {
    mode.value = type;
    visible.value = true;
};

const close = () => {
    visible.value = false;
    emit("close");
};

const save = async () => {
    // 判断formData.time 个数HH:mm 不能小于当前时间
    function isTimeBeforeNow(timeStr: string) {
        const [hours, minutes] = timeStr.split(":").map(Number);
        const now = dayjs();
        const inputTime = now.hour(hours).minute(minutes).second(0).millisecond(0);
        return inputTime.isBefore(now);
    }
    // 示例用法
    if (!formData.time) {
        feedback.msgError("请选择时间");
        return;
    } else if (
        !isEdit.value &&
        formData.time.order_day == 0 &&
        isTimeBeforeNow(formData.time.push_time) &&
        props.pushDay &&
        props.pushDay == dayjs().format("YYYY-MM-DD")
    ) {
        feedback.msgError("请选择的时间小于当前时间");
        return;
    } else if (formData.content.length === 0) {
        feedback.msgError("请添加内容");
        return;
    }
    emit("success", formData);
    close();
};

const { lockFn, isLock } = useLockFn(save);

defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss">
.material-edit-drawer {
    :deep(.el-drawer__header) {
        margin-bottom: 0;
    }
    :deep(.el-drawer__body) {
        padding: 0;
    }
}
</style>
