<template>
    <div class="h-full flex flex-col gap-y-3">
        <div class="flex items-center justify-between bg-white px-6 py-4 rounded-[20px] border border-br flex-shrink-0">
            <div class="flex items-center gap-4">
                <div
                    class="p-2 rounded-xl bg-[#0065fb]/5 text-primary cursor-pointer hover:bg-[#0065fb]/10 transition-colors leading-[0]"
                    @click="emit('back')">
                    <Icon name="el-icon-ArrowLeft" :size="18" />
                </div>
                <ElBreadcrumb :separator-icon="ArrowRight" class="modern-breadcrumb">
                    <ElBreadcrumbItem class="cursor-pointer" @click="emit('back')">任务管理</ElBreadcrumbItem>
                    <ElBreadcrumbItem>
                        <span class="font-black text-[#0F172A]">{{ detail.flow_name }}</span>
                    </ElBreadcrumbItem>
                </ElBreadcrumb>
            </div>
            <ElButton type="danger" link class="!font-medium" @click="emit('delete', flowId, true)">
                <Icon name="el-icon-Delete" />
                <span class="ml-1">删除整条流程</span>
            </ElButton>
        </div>

        <div class="grow min-h-0 bg-white rounded-[20px] border border-br flex flex-col overflow-hidden relative">
            <ElScrollbar>
                <div class="p-6">
                    <div class="stage-container relative" v-draggable="draggableOptions">
                        <div class="stage-list space-y-6">
                            <div
                                v-for="(item, index) in stageLists"
                                :key="item.sub_stage_id"
                                class="stage-card-wrapper">
                                <div
                                    class="bg-white rounded-[20px] shadow-[0_0_10px_0_rgba(0,0,0,0.05)] border border-slate-100 overflow-hidden">
                                    <div
                                        class="flex items-center justify-between px-5 py-4 bg-white border-b border-slate-50">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="move-icon cursor-move p-1 text-slate-300 hover:text-primary transition-colors">
                                                <Icon name="local-icon-apps" :size="20" />
                                            </div>
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-[16px] font-[900] text-tx-primary">{{
                                                        item.sub_stage_name
                                                    }}</span>
                                                    <span
                                                        :class="[
                                                            'status-pill',
                                                            item.status == 0 ? 'is-key' : 'is-warn',
                                                        ]">
                                                        {{ item.status == 0 ? "关键阶段" : "警示阶段" }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="flex items-center gap-3 mt-1.5 text-[11px] font-medium text-slate-400">
                                                    <span class="flex items-center gap-1"
                                                        ><Icon name="local-icon-click" :size="12" />触发器:
                                                        {{ item.trigger_count }}</span
                                                    >
                                                    <span class="flex items-center gap-1"
                                                        ><Icon name="local-icon-alarm" :size="12" />跟进提醒:
                                                        {{ item.remind_count }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <ElButton
                                                type="primary"
                                                link
                                                class="!text-xs !font-black"
                                                @click="handleStageEdit(item, index)"
                                                >配置阶段</ElButton
                                            >
                                            <div class="w-[1px] h-3 bg-slate-200 mx-1"></div>
                                            <ElButton
                                                type="danger"
                                                link
                                                class="!text-xs !font-black"
                                                @click="handleStageDelete(item.sub_stage_id, index)"
                                                >移除</ElButton
                                            >
                                        </div>
                                    </div>

                                    <div class="p-5 grid grid-cols-2 gap-6 bg-[#FBFCFE]">
                                        <div class="config-block">
                                            <div class="flex items-center justify-between mb-3 px-1">
                                                <span class="text-xs font-black text-slate-500 uppercase tracking-wider"
                                                    >01. 标签/动作触发</span
                                                >
                                                <ElButton
                                                    type="primary"
                                                    link
                                                    class="!p-0"
                                                    @click="handleTriggerEventEdit({ stage_id: item.sub_stage_id })">
                                                    <Icon name="el-icon-CirclePlusFilled" :size="18" />
                                                </ElButton>
                                            </div>

                                            <div v-if="item.trigger_count > 0" class="space-y-3">
                                                <div
                                                    v-for="value in item.triggerlist"
                                                    :key="value.id"
                                                    class="item-node group"
                                                    @click="handleTriggerEventEdit({ trigger_id: value.id, ...value })">
                                                    <div class="node-icon">
                                                        <Icon
                                                            :name="
                                                                value.match_type == 1
                                                                    ? 'el-icon-Pointer'
                                                                    : 'el-icon-ChatLineRound'
                                                            " />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-[10px] text-primary font-black mb-0.5">
                                                            {{ getMatchType("trigger", value) }}
                                                        </div>
                                                        <div class="text-xs font-medium text-tx-regular truncate">
                                                            {{
                                                                value.match_type == 2
                                                                    ? value.chat_keywords
                                                                    : "新好友加入"
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="node-action"
                                                        @click.stop="handleTriggerEventDelete(value.id)">
                                                        <Icon name="el-icon-Close" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="empty-placeholder">未配置触发条件</div>
                                        </div>

                                        <div class="config-block border-l border-slate-100 pl-6">
                                            <div class="flex items-center justify-between mb-3 px-1">
                                                <span class="text-xs font-black text-slate-500 uppercase tracking-wider"
                                                    >02. 自动化跟进任务</span
                                                >
                                                <ElButton
                                                    type="primary"
                                                    link
                                                    class="!p-0"
                                                    @click="handleFollowRemindEdit({ stage_id: item.sub_stage_id })">
                                                    <Icon name="el-icon-CirclePlusFilled" :size="18" />
                                                </ElButton>
                                            </div>

                                            <div v-if="item.remind_count > 0" class="space-y-3">
                                                <div
                                                    v-for="value in item.remindlist"
                                                    :key="value.id"
                                                    class="item-node group is-remind"
                                                    @click="handleFollowRemindEdit({ remind_id: value.id, ...value })">
                                                    <div class="node-icon"><Icon name="el-icon-AlarmClock" /></div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-[10px] text-amber-500 font-black mb-0.5">
                                                            {{ getMatchType("follow", value) }}
                                                        </div>
                                                        <div class="text-xs font-medium text-tx-regular truncate">
                                                            {{ value.judgment }}天后 {{ value.send_time }}
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="node-action"
                                                        @click.stop="handleFollowRemindDelete(value.id)">
                                                        <Icon name="el-icon-Close" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="empty-placeholder">未配置跟进提醒</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
            <div class="mt-8 flex justify-center sticky bottom-5 z-[222]">
                <div class="add-stage-btn" @click="handleStageAdd">
                    <Icon name="el-icon-Plus" />
                    <span class="ml-2">插入流程新阶段</span>
                </div>
            </div>
        </div>

        <div class="absolute w-full h-full flex items-center justify-center bg-white/50 z-50" v-if="loading">
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
.status-pill {
    @apply px-2 py-0.5 rounded-lg text-[10px] font-black uppercase;
    &.is-key {
        @apply bg-emerald-50 text-emerald-600;
    }
    &.is-warn {
        @apply bg-amber-50 text-amber-600;
    }
}

.item-node {
    @apply flex items-center gap-3 p-3 bg-white border border-slate-100 rounded-xl transition-all cursor-pointer relative;
    &:hover {
        @apply border-primary shadow-light -translate-y-0.5;
    }

    .node-icon {
        @apply w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 transition-colors;
    }
    &:hover .node-icon {
        @apply bg-[#0065fb]/5 text-primary;
    }

    &.is-remind:hover .node-icon {
        @apply bg-amber-50 text-amber-500;
    }

    .node-action {
        @apply absolute -top-1.5 -right-1.5 w-5 h-5 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center scale-0 opacity-0 transition-all hover:bg-error hover:text-white;
    }
    &:hover .node-action {
        @apply scale-100 opacity-100;
    }
}

.empty-placeholder {
    @apply h-[100px] rounded-xl border-2 border-dashed border-slate-100 flex items-center justify-center text-xs text-slate-300 font-medium;
}

.stage-list {
    @apply relative;
    &::before {
        content: "";
        @apply absolute left-7 top-4 bottom-4 w-0.5 bg-slate-100 -z-10;
    }
}

.add-stage-btn {
    @apply flex items-center px-8 py-3 bg-primary text-white rounded-full font-black text-sm cursor-pointer shadow-light shadow-[#0065fb]/20 transition-all hover:scale-105 active:scale-95;
}

.modern-breadcrumb :deep(.el-breadcrumb__inner) {
    @apply font-medium text-slate-400;
    &.is-link:hover {
        @apply text-primary;
    }
}
</style>
