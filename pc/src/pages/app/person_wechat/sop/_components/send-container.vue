<template>
    <div class="h-full flex flex-col gap-3">
        <div class="grow min-h-0 flex gap-3">
            <div class="flex-1 flex flex-col h-full bg-white rounded-[20px] border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                        <span class="text-[16px] font-black text-slate-800">推送计划配置</span>
                    </div>
                    <div class="text-xs text-slate-400 font-medium">共 {{ formData.push_time_list.length }} 条计划</div>
                </div>

                <div class="grow min-h-0 py-4">
                    <template v-if="formData.push_time_list.length">
                        <ElScrollbar>
                            <div class="px-6 flex flex-col gap-y-6">
                                <template v-for="(item, index) in formData.push_time_list">
                                    <send-container-item
                                        :item="item"
                                        @delete="handleContentDelete"
                                        @edit="handleContentEdit" />
                                </template>
                            </div>
                        </ElScrollbar>
                    </template>
                    <div class="flex items-center justify-center h-full" v-else>
                        <ElEmpty description="暂无推送计划，请点击下方添加" :image-size="100"> </ElEmpty>
                    </div>
                </div>

                <div class="p-4 bg-[#f8fafc]/50 border-t border-slate-50">
                    <div
                        class="h-12 bg-white border border-[#0065fb]/20 rounded-[16px] flex items-center justify-center cursor-pointer gap-x-2 transition-all hover:shadow-lg hover:shadow-[#0065fb]/10 hover:border-primary group"
                        @click.stop="handleMaterialAdd">
                        <div
                            class="w-7 h-7 rounded-full bg-[#0065fb]/10 flex items-center justify-center group-hover:bg-primary transition-colors">
                            <span class="text-primary group-hover:text-white">
                                <Icon name="local-icon-add_box_fill" :size="18"></Icon>
                            </span>
                        </div>
                        <span class="text-primary font-black text-[14px]"> 添加推送内容 </span>
                    </div>
                </div>
            </div>

            <div class="w-[420px] flex-shrink-0 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4 px-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                            <img src="@/assets/images/7_day.png" class="w-5 h-5" />
                        </div>
                        <span class="font-black text-slate-800">推送日期预览</span>
                    </div>
                    <div
                        v-if="dateList.length"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-100 text-slate-400 hover:text-primary hover:border-primary transition-all cursor-pointer"
                        @click="handleShowSendDatePreview">
                        <Icon name="el-icon-FullScreen" :size="18"></Icon>
                    </div>
                </div>

                <div class="grow min-h-0 bg-white border border-slate-100 rounded-[20px] p-4 relative overflow-hidden">
                    <template v-if="dateList.length">
                        <ElScrollbar>
                            <send-date :date-list="dateList" @edit="handleContentEdit" />
                        </ElScrollbar>
                        <div
                            class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
                    </template>
                    <div class="flex items-center justify-center h-full" v-else>
                        <div class="flex flex-col items-center gap-2">
                            <Icon name="el-icon-Calendar" :size="40" color="#CBD5E1"></Icon>
                            <span class="text-slate-400 text-xs font-medium">暂无日期预览</span>
                        </div>
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
        :push-day="formData.push_day"
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
}>();

const emit = defineEmits<{
    (e: "back"): void;
    (e: "success"): void;
}>();

const nuxtApp = useNuxtApp();

const formData = reactive({
    id: "",
    push_day: "",
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
                  push_id: formData.id,
                  ...result,
              })
            : await sopPushContentAdd({
                  push_id: formData.id,
                  ...result,
              });
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
};

const showSendDatePreview = ref(false);
const sendDatePreviewRef = ref<InstanceType<typeof SendDatePreview>>();

const handleShowSendDatePreview = async () => {
    showSendDatePreview.value = true;
    await nextTick();
    sendDatePreviewRef.value?.open();
};

defineExpose({
    setFormData: (data) => setFormData(data, formData),
    setDateList: (data) => {
        dateList.value = data;
    },
});
</script>

<style scoped lang="scss">
:deep(.el-scrollbar__view) {
    height: 100%;
}

:deep(.el-empty) {
    padding: 0;
    .el-empty__description {
        margin-top: 8px;
        font-weight: 800;
        font-size: 13px;
        color: #94a3b8;
    }
}
</style>
