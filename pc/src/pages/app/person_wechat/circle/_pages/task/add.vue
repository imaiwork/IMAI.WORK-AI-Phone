<template>
    <ElDrawer v-model="show" title="" size="470px" :with-header="false" class="task-add-drawer relative" @close="close">
        <CircleSend
            v-model="formData"
            :show-close-btn="true"
            ref="circleSendRef"
            @close="close"
            @success="
                emit('success');
                show = false;
            " />
    </ElDrawer>
</template>

<script setup lang="ts">
import { dayjs } from "element-plus";
import { circleTaskInfo } from "@/api/person_wechat";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import CircleSend from "../../_components/circle-send.vue";

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const show = ref(false);
const formData = reactive<any>({
    id: "",
    name: `朋友圈任务${dayjs().format("YYYYMMDDHHmm")}`,
    content: "",
    attachment_type: MaterialTypeEnum.IMAGE,
    attachment_content: [],
    date: dayjs().format("YYYY-MM-DD"),
    time_config: ["00:00", "00:30"],
    wechat_ids: [],
});

const circleSendRef = ref<any>(null);

const open = () => {
    show.value = true;
};

const close = () => {
    show.value = false;
    emit("close");
};

const getDetail = async (id: any) => {
    const data = await circleTaskInfo({ id });
    setFormData(data);
    circleSendRef.value.setAssetData(data);
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
    if (data.wechat_id) {
        formData.wechat_ids = [data.wechat_id];
    }
};

defineExpose({
    open,
    getDetail,
});
</script>

<style lang="scss">
.task-add-drawer {
    .el-drawer__body {
        padding: 0;
    }
}
</style>
