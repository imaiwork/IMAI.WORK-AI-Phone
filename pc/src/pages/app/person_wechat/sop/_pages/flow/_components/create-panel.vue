<template>
    <div class="h-full flex flex-col relative">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl flex-shrink-0">
            <div>
                <ElBreadcrumb :separator-icon="ArrowRight">
                    <ElBreadcrumbItem>
                        <span class="cursor-pointer text-[#8A8C99] hover:text-primary" @click="emit('back')">
                            任务管理
                        </span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem>
                        <span> {{ detail.flow_name }} </span>
                    </ElBreadcrumbItem>
                </ElBreadcrumb>
            </div>
            <ElButton type="danger" @click="emit('delete', flowId, true)">
                <Icon name="el-icon-Delete" :size="16"></Icon>
                <span>删除</span>
            </ElButton>
        </div>
        <div class="grow min-h-0 bg-white rounded-xl mt-4 flex flex-col py-4">
            <div class="flex-shrink-1 min-h-0">
                <ElScrollbar>
                    <ElCollapse>
                        <div class="px-4" v-draggable="draggableOptions">
                            <div class="stage-list flex flex-col gap-y-4 py-2">
                                <ElCollapseItem :title="item.sub_stage_name" v-for="(item, index) in stageLists">
                                    <template #title>
                                        <div class="flex px-4 justify-between items-center">
                                            <div class="flex items-center">
                                                <span class="move-icon cursor-move" @click.stop>
                                                    <Icon
                                                        name="local-icon-apps"
                                                        color="var(--color-primary)"
                                                        :size="20"></Icon>
                                                </span>
                                                <span class="ml-6">{{ item.sub_stage_name }}</span>
                                                <div class="ml-4">
                                                    <ElTag type="success" v-if="item.status == 0" disable-transitions
                                                        >关键阶段</ElTag
                                                    >
                                                    <ElTag type="warning" v-if="item.status == 1" disable-transitions
                                                        >警示阶段</ElTag
                                                    >
                                                </div>
                                                <div class="ml-4 flex gap-x-2">
                                                    <ElTag type="info" size="small" disable-transitions>
                                                        触发条件 -
                                                        {{ item.trigger_count || 0 }}个
                                                    </ElTag>
                                                    <ElTag type="info" size="small" disable-transitions>
                                                        自动跟进提醒 -
                                                        {{ item.remind || 0 }}个
                                                    </ElTag>
                                                </div>
                                            </div>
                                            <div>
                                                <ElButton
                                                    type="primary"
                                                    size="small"
                                                    @click.stop="
                                                        handleTriggerEventEdit({
                                                            stage_id: item.sub_stage_id,
                                                        })
                                                    ">
                                                    <Icon name="local-icon-click" color="#fff" :size="16"></Icon>
                                                    <span class="ml-2">新增触发条件</span>
                                                </ElButton>
                                                <ElButton
                                                    type="primary"
                                                    size="small"
                                                    @click.stop="
                                                        handleFollowRemindEdit({
                                                            stage_id: item.sub_stage_id,
                                                        })
                                                    ">
                                                    <Icon name="local-icon-alarm" color="#fff"></Icon>
                                                    <span class="ml-2">新增跟进提醒</span>
                                                </ElButton>
                                                <ElButton
                                                    type="primary"
                                                    size="small"
                                                    @click.stop="handleStageEdit(item, index)">
                                                    <Icon name="local-icon-edit" color="#fff"></Icon>
                                                    <span class="ml-2">编辑</span>
                                                </ElButton>
                                                <ElButton
                                                    type="danger"
                                                    size="small"
                                                    @click.stop="handleStageDelete(item.sub_stage_id, index)">
                                                    <Icon name="local-icon-delete_bin" color="#fff"></Icon>
                                                    <span class="ml-2">删除</span>
                                                </ElButton>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="bg-[#F7F7F7] rounded-lg p-4 mx-2 mb-2">
                                        <div>
                                            <div class="flex items-center gap-x-2">
                                                <span class="text-[#8A8C99]"
                                                    >标签触发条件配置（{{ item.trigger_count }}）</span
                                                >
                                                <ElButton link @click="toggleTriggerEventList(item.sub_stage_id)">
                                                    <Icon
                                                        :name="
                                                            currentTriggerEventId == item.sub_stage_id
                                                                ? 'el-icon-ArrowRight'
                                                                : 'el-icon-ArrowDown'
                                                        "
                                                        color="#8A8C99"></Icon>
                                                </ElButton>
                                            </div>
                                            <div class="mt-4" v-show="currentTriggerEventId != item.sub_stage_id">
                                                <div class="grid grid-cols-4 gap-4" v-if="item.trigger_count > 0">
                                                    <div
                                                        v-for="value in item.triggerlist"
                                                        class="bg-white rounded-lg border border-primary-light-8 cursor-pointer"
                                                        @click="
                                                            handleTriggerEventEdit({
                                                                trigger_id: value.id,
                                                                ...value,
                                                            })
                                                        ">
                                                        <div class="flex items-center justify-between">
                                                            <div
                                                                class="text-[10px] bg-primary py-1 px-1.5 text-white rounded-tl-lg rounded-br-lg">
                                                                {{ getMatchType("trigger", value) }}
                                                            </div>
                                                            <div class="mr-1">
                                                                <ElButton
                                                                    link
                                                                    @click.stop="handleTriggerEventDelete(value.id)">
                                                                    <Icon name="el-icon-Delete" color="#8A8C99"></Icon>
                                                                </ElButton>
                                                            </div>
                                                        </div>
                                                        <div class="">
                                                            <div
                                                                class="text-[#8A8C99] text-xs p-2"
                                                                v-if="value.match_type == 2">
                                                                {{ value.chat_keywords }}
                                                            </div>
                                                            <div
                                                                class="flex items-center justify-center gap-x-4 py-4"
                                                                v-else>
                                                                <Icon
                                                                    name="local-icon-user_add"
                                                                    color="var(--color-primary)"></Icon>
                                                                <span class="text-primary text-xs">刚刚成为好友</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ElEmpty v-else description="暂无数据" :image-size="60"></ElEmpty>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex items-center gap-x-2">
                                                <span class="text-[#8A8C99]"
                                                    >自动跟进提醒配置（{{ item.remind_count }}）</span
                                                >
                                                <ElButton link @click="toggleFollowRemindList(item.sub_stage_id)">
                                                    <Icon
                                                        :name="
                                                            currentFollowRemindId != item.sub_stage_id
                                                                ? 'el-icon-ArrowDown'
                                                                : 'el-icon-ArrowRight'
                                                        "
                                                        color="#8A8C99"></Icon>
                                                </ElButton>
                                            </div>
                                            <div class="mt-4" v-show="currentFollowRemindId != item.sub_stage_id">
                                                <div class="grid grid-cols-4 gap-4" v-if="item.remind_count > 0">
                                                    <div
                                                        v-for="value in item.remindlist"
                                                        class="bg-white rounded-lg border border-primary-light-8 cursor-pointer"
                                                        @click="
                                                            handleFollowRemindEdit({
                                                                remind_id: value.id,
                                                                ...value,
                                                            })
                                                        ">
                                                        <div class="flex items-center justify-between">
                                                            <div
                                                                class="text-[10px] bg-primary py-1 px-1.5 text-white rounded-tl-lg rounded-br-lg">
                                                                {{ getMatchType("follow", value) }}
                                                            </div>
                                                            <div class="mr-1">
                                                                <ElButton
                                                                    link
                                                                    @click.stop="handleFollowRemindDelete(value.id)">
                                                                    <Icon name="el-icon-Delete" color="#8A8C99"></Icon>
                                                                </ElButton>
                                                            </div>
                                                        </div>
                                                        <div class="p-2">
                                                            <div class="text-xs">
                                                                超过<span class="text-primary mx-1">{{
                                                                    value.judgment
                                                                }}</span
                                                                >天后，{{ value.judgment > 0 ? "次日" : "当天" }}
                                                                <span class="text-primary mx-1">{{
                                                                    value.send_time
                                                                }}</span
                                                                >跟进
                                                            </div>
                                                            <div class="text-xs mt-2 text-primary">
                                                                {{ value.content }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ElEmpty v-else description="暂无数据" :image-size="60"></ElEmpty>
                                            </div>
                                        </div>
                                    </div>
                                </ElCollapseItem>
                            </div>
                        </div>
                    </ElCollapse>
                </ElScrollbar>
            </div>
            <div class="flex justify-end px-4 mt-4">
                <ElButton type="primary" link @click="handleStageAdd">
                    <Icon name="local-icon-add_box_fill" :size="18"></Icon>
                    <span class="ml-2">新增阶段</span>
                </ElButton>
            </div>
        </div>
        <div class="absolute w-full h-full flex items-center justify-center" v-if="loading">
            <loader />
        </div>
    </div>
    <trigger-event-edit
        v-if="showTriggerEventEdit"
        ref="triggerEventEditRef"
        @close="showTriggerEventEdit = false"
        @success="getDetail" />
    <follow-remind-edit
        v-if="showFollowRemindEdit"
        ref="followRemindEditRef"
        @close="showFollowRemindEdit = false"
        @success="getDetail" />
    <stage-edit v-if="showStageEdit" ref="stageEditRef" @close="showStageEdit = false" @success="getDetail" />
</template>

<script setup lang="ts">
import {
    sopFlowDetail,
    sopDeleteStage,
    sopUpdateStage,
    sopDeleteTagTrigger,
    sopDeleteAutoFollow,
} from "@/api/person_wechat";
import { ArrowRight } from "@element-plus/icons-vue";
import TriggerEventEdit from "./trigger-event-edit.vue";
import FollowRemindEdit from "./follow-remind-edit.vue";
import StageEdit from "./stage-edit.vue";
const emit = defineEmits<{
    (e: "back"): void;
    (e: "delete", id: number | string, isClose: boolean): void;
}>();

const route = useRoute();
const nuxtApp = useNuxtApp();

const flowId = ref<any>(route.query.id);
const stageLists = ref<any[]>([]);

const showStageEdit = ref(false);
const stageEditRef = ref<InstanceType<typeof StageEdit>>();

// 拖拽配置选项
const draggableOptions = [
    {
        selector: ".stage-list",
        options: {
            animation: 150,
            handle: ".move-icon",
            onEnd: async ({ newIndex, oldIndex }: { newIndex: number; oldIndex: number }) => {
                const oldStage = stageLists.value[oldIndex];
                const newStage = stageLists.value[newIndex];
                // old 和 new 的sort 互换
                await Promise.all([
                    sopUpdateStage({
                        id: oldStage.sub_stage_id,
                        sort: newStage.sort,
                    }),
                    sopUpdateStage({
                        id: newStage.sub_stage_id,
                        sort: oldStage.sort,
                    }),
                ]);
                // 处理拖拽结束后的排序逻辑
                const arr = [...stageLists.value];
                const currRow = arr.splice(oldIndex, 1)[0];
                arr.splice(newIndex, 0, currRow);
                // 使用临时空数组和nextTick触发视图更新
                stageLists.value = [];
                nextTick(() => {
                    stageLists.value = arr;
                });
                // getDetail();
            },
        },
    },
];

const handleStageAdd = async () => {
    showStageEdit.value = true;
    await nextTick();
    stageEditRef.value?.open();
    stageEditRef.value?.setFormData({
        flow_id: flowId.value,
    });
};

const handleStageEdit = async (item: any, index: number) => {
    showStageEdit.value = true;
    await nextTick();
    stageEditRef.value?.open();
    stageEditRef.value?.setFormData({
        flow_id: flowId.value,
        id: item.sub_stage_id,
        ...item,
    });
};

const handleStageDelete = (id: string, index: number) => {
    nuxtApp.$confirm({
        message: "确定删除该客户流程吗？",
        onConfirm: async () => {
            try {
                await sopDeleteStage({ id });
                stageLists.value.splice(index, 1);
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const showTriggerEventEdit = ref(false);
const triggerEventEditRef = ref<InstanceType<typeof TriggerEventEdit>>();

const handleTriggerEventEdit = async (value: any) => {
    showTriggerEventEdit.value = true;
    await nextTick();
    triggerEventEditRef.value?.open(value.trigger_id ? "edit" : "add");
    triggerEventEditRef.value?.setFormData({
        flow_id: flowId.value,
        ...value,
    });
};

const showFollowRemindEdit = ref(false);
const followRemindEditRef = ref<InstanceType<typeof FollowRemindEdit>>();

const handleFollowRemindEdit = async (value: any) => {
    showFollowRemindEdit.value = true;
    await nextTick();
    followRemindEditRef.value?.open(value.id ? "edit" : "add");
    followRemindEditRef.value?.setFormData({
        flow_id: flowId.value,
        ...value,
    });
};
interface DeleteParams {
    id: string | number;
    confirmMessage: string;
    deleteApi: (params: any) => Promise<any>;
}

const handleDelete = ({ id, confirmMessage, deleteApi }: DeleteParams) => {
    nuxtApp.$confirm({
        message: confirmMessage,
        onConfirm: async () => {
            try {
                await deleteApi({ id });
                feedback.msgSuccess("删除成功");
                getDetail();
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const handleTriggerEventDelete = async (trigger_id: any) => {
    await handleDelete({
        id: trigger_id,
        confirmMessage: "确定删除该触发条件吗？",
        deleteApi: sopDeleteTagTrigger,
    });
};

const handleFollowRemindDelete = async (id: any) => {
    await handleDelete({
        id,
        confirmMessage: "确定删除该跟进提醒吗？",
        deleteApi: sopDeleteAutoFollow,
    });
};

const currentTriggerEventId = ref<any>(null);
const toggleTriggerEventList = (id: any) => {
    currentTriggerEventId.value ? (currentTriggerEventId.value = null) : (currentTriggerEventId.value = id);
};

const currentFollowRemindId = ref<any>(null);
const toggleFollowRemindList = (id: any) => {
    currentFollowRemindId.value ? (currentFollowRemindId.value = null) : (currentFollowRemindId.value = id);
};

const getMatchType = (type: string, value: any) => {
    const { match_type, chat_match_mode, status } = value;
    if (type == "trigger") {
        return match_type == 1 ? "动作匹配" : chat_match_mode == 1 ? "模糊匹配" : "精确匹配";
    } else if (type == "follow") {
        return status == 1 ? "未联系提醒" : "停留提醒";
    }
};

const loading = ref(false);
const detail = ref<any>({});
const getDetail = async (id?: string | number) => {
    loading.value = true;
    try {
        const data = await sopFlowDetail({ id: id || flowId.value });
        id && (flowId.value = id);
        detail.value = data;
        stageLists.value = data.sub_stage_list;
    } catch (error) {
        feedback.msgError(error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    if (flowId.value) {
        await getDetail();
    }
});

defineExpose({
    getDetail,
});
</script>

<style scoped lang="scss">
:deep(.el-collapse) {
    border: none;
    .el-collapse-item {
        box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        overflow: hidden;

        .el-collapse-item__wrap,
        .el-collapse-item__header {
            border-bottom: none;
        }
    }
}
</style>
