<template>
    <div class="h-full flex flex-col gap-x-4">
        <div class="grow min-h-0 flex">
            <div class="flex-1 flex flex-col h-full">
                <div class="flex-shrink min-h-0">
                    <template v-if="formData.push_time_list.length">
                        <ElScrollbar>
                            <div class="px-4 flex flex-col gap-y-4">
                                <template v-for="(item, index) in formData.push_time_list">
                                    <send-container-item
                                        :item="item"
                                        @delete="handleContentDelete"
                                        @edit="handleContentEdit" />
                                </template>
                            </div>
                        </ElScrollbar>
                    </template>
                    <div class="mt-14" v-else>
                        <ElEmpty description="请添加内容"></ElEmpty>
                    </div>
                </div>
                <div
                    class="m-4 p-2 bg-primary-light-9 rounded-xl flex items-center justify-center cursor-pointer gap-x-2"
                    @click.stop="handleMaterialAdd">
                    <Icon name="local-icon-add_box_fill" color="var(--color-primary)" :size="20"></Icon>
                    <span class="text-primary font-bold"> 添加内容 </span>
                </div>
            </div>
            <div class="w-[456px] flex-shrink-0">
                <div class="flex items-center gap-2 mb-4">
                    <img src="@/assets/images/7_day.png" class="w-6 h-6" />
                    <span>推送日期预览</span>
                </div>
                <div class="h-full">
                    <div class="border border-primary-light-8 rounded-xl py-4 relative px-2" v-if="dateList.length">
                        <send-date :date-list="dateList" @edit="handleContentEdit" />
                        <div
                            class="absolute top-1 right-1 z-40 p-1 hover:bg-primary-light-8 rounded-md cursor-pointer"
                            @click="handleShowSendDatePreview">
                            <Icon name="local-icon-fullscreen" color="var(--color-primary)"></Icon>
                        </div>
                    </div>
                    <div class="" v-else>
                        <ElEmpty description="请添加内容"></ElEmpty>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <send-date-preview
        v-if="showSendDatePreview"
        ref="sendDatePreviewRef"
        :date-list="dateList"
        @close="showSendDatePreview = false"
        @edit="handleContentEdit" />
    <material-edit
        v-if="showMaterialEdit"
        ref="materialEditRef"
        :push-day="pushDay"
        @success="handleMaterialSuccess"
        @close="showMaterialEdit = false" />
</template>

<script setup lang="ts">
import {
    sopPushContentAdd,
    sopPushContentUpdate,
    sopPushContentDetail,
    sopPushContentDelete,
} from "@/api/person_wechat";
import MaterialEdit from "./material-edit.vue";
import SendDate from "./send-date.vue";
import SendDatePreview from "./send-date-preview.vue";
import SendContainerItem from "./send-container-item.vue";
import { PushTypeEnum } from "../_enums";

const props = defineProps<{
    type: PushTypeEnum;
    taskId: number | string;
    pushDay: string;
}>();

const emit = defineEmits<{
    (e: "back"): void;
    (e: "success"): void;
}>();

const nuxtApp = useNuxtApp();

const formData = reactive({
    push_time_list: [],
});

const dateList = ref<any[]>([]);

const handleContentDelete = (id: number) => {
    nuxtApp.$confirm({
        message: "确定删除该内容吗？",
        onConfirm: async () => {
            try {
                await sopPushContentDelete({
                    content_id: id,
                });
                emit("success");
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const showMaterialEdit = ref(false);
const materialEditRef = shallowRef<InstanceType<typeof MaterialEdit>>();

// 添加素材
const handleMaterialAdd = async () => {
    showMaterialEdit.value = true;
    await nextTick();
    materialEditRef.value?.open("add");
};

// 编辑素材
const handleContentEdit = async (id: string) => {
    try {
        const result = await sopPushContentDetail({
            content_id: id,
        });
        showMaterialEdit.value = true;
        await nextTick();
        materialEditRef.value?.open("edit");
        materialEditRef.value?.setFormData({
            content_id: result.id,
            content: result.content,
            time: {
                order_day: result.order_day,
                push_time: result.push_time,
            },
        });
    } catch (error) {
        feedback.msgError(error);
    }
};

const handleMaterialSuccess = async (result: any) => {
    try {
        result.content_id
            ? await sopPushContentUpdate({
                  push_id: props.taskId,
                  ...result,
              })
            : await sopPushContentAdd({
                  push_id: props.taskId,
                  ...result,
              });
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
};

const showSendDatePreview = ref(false);
const showSendType = ref(false);
const sendDatePreviewRef = ref<InstanceType<typeof SendDatePreview>>();
// const sendTypeRef = ref<InstanceType<typeof SendType>>();

const handleShowSendDatePreview = async () => {
    showSendDatePreview.value = true;
    await nextTick();
    sendDatePreviewRef.value?.open();
};

const { lockFn, isLock } = useLockFn(async () => {
    if (formData.push_time_list.length == 0) {
        ElMessage.error("请添加内容");
        return;
    }
    emit("success");
    back();
});

const back = () => {
    emit("back");
};

defineExpose({
    setFormData: (data) => setFormData(data, formData),
    setDateList: (data) => {
        dateList.value = data;
    },
});
</script>

<style scoped></style>
