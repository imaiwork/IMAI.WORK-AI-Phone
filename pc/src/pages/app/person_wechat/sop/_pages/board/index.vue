<template>
    <div class="h-full flex flex-col">
        <header class="bg-white p-[20px] rounded-[20px] border border-br flex items-center gap-[16px]">
            <div class="flex items-center gap-[12px]">
                <div class="flex items-center bg-gray-50 rounded-[8px] px-[12px] py-1 border border-br-extra-light">
                    <span class="text-[13px] font-medium text-tx-secondary mr-[8px]">归属微信</span>
                    <ElSelect
                        v-model="queryParams.wechat_id"
                        placeholder="请选择微信"
                        class="custom-select !w-[200px]"
                        :show-arrow="false"
                        @change="getBoardLists">
                        <ElOption
                            v-for="item in wechatLists"
                            :key="item.id"
                            :label="item.wechat_nickname"
                            :value="item.wechat_id" />
                    </ElSelect>
                </div>

                <div class="flex items-center bg-gray-50 rounded-[8px] px-[12px] py-1 border border-br-extra-light">
                    <span class="text-[13px] font-medium text-tx-secondary mr-[8px]">当前流程</span>
                    <ElSelect
                        v-model="queryParams.flow_id"
                        placeholder="请选择流程"
                        class="custom-select !w-[200px]"
                        :show-arrow="false"
                        @change="getBoardLists">
                        <ElOption v-for="item in flowLists" :key="item.id" :label="item.flow_name" :value="item.id" />
                    </ElSelect>
                </div>
            </div>

            <div class="ml-auto flex items-center gap-[8px] text-xs text-tx-secondary">
                <span class="text-primary opacity-70 leading-[0]">
                    <Icon name="el-icon-InfoFilled" />
                </span>
                <span>左右拖拽成员卡片可快速调整阶段</span>
            </div>
        </header>
        <div class="grow min-h-0 mt-3 bg-white rounded-[20px] border border-br overflow-hidden" v-loading="loading">
            <ElScrollbar v-if="boardLists.length > 0">
                <div class="flex gap-[20px] p-[20px] h-full">
                    <div v-for="({ members, sub_stage_name }, index) in boardLists" :key="index" class="kanban-column">
                        <div class="kanban-header shrink-0">
                            <div class="h-[3px] bg-gray-100 relative overflow-hidden">
                                <div
                                    class="h-full bg-primary transition-all duration-500"
                                    :style="{ width: `${((index + 1) / boardLists.length) * 100}%` }"></div>
                            </div>

                            <div class="flex items-center justify-between px-[16px] h-[64px]">
                                <div class="flex items-center gap-[8px]">
                                    <div
                                        class="w-[8px] h-[8px] rounded-full bg-primary shadow-[0_0_8px_rgba(0,101,251,0.5)]"></div>
                                    <div class="text-[15px] font-[900] text-tx-primary">{{ sub_stage_name }}</div>
                                    <div
                                        class="shrink-0 text-xs px-[6px] py-[1px] bg-gray-100 text-tx-secondary rounded-full font-medium">
                                        {{ members?.length || 0 }}
                                    </div>
                                </div>

                                <ElPopover trigger="click" :show-arrow="false" popper-class="custom-board-popover">
                                    <template #reference>
                                        <div class="more-action-btn">
                                            <Icon name="el-icon-MoreFilled" />
                                        </div>
                                    </template>
                                    <div class="p-[6px] flex flex-col gap-[2px]">
                                        <div
                                            v-for="option in handleOptions"
                                            :key="option.value"
                                            class="popover-item"
                                            :class="{ 'is-disabled': option.disabled }"
                                            @click="handleOptionClick(option.value, index)">
                                            {{ option.label }}
                                        </div>
                                    </div>
                                </ElPopover>
                            </div>

                            <div class="px-[12px] pb-[12px]">
                                <div class="add-user-trigger" @click="handleAddUser(index)">
                                    <Icon name="el-icon-Plus" />
                                    <span class="ml-1"> 添加客户 </span>
                                </div>
                            </div>
                        </div>

                        <div class="min-h-0 grow px-[12px] py-[4px]">
                            <ElScrollbar>
                                <div
                                    class="flex flex-col gap-[12px] h-full min-h-[100px] py-5"
                                    :ref="(el) => setListRef(el, index)"
                                    :data-index="index">
                                    <div
                                        v-for="item in members"
                                        :key="item.friend_id"
                                        class="member-card shadow-light"
                                        @click="openUserDetail(item)">
                                        <div class="flex gap-[12px]">
                                            <div class="relative shrink-0">
                                                <img
                                                    v-if="item.avatar"
                                                    class="w-[44px] h-[44px] rounded-[10px] border border-br-extra-light"
                                                    :src="item.avatar" />
                                                <div
                                                    v-else
                                                    class="w-[44px] h-[44px] rounded-[10px] bg-blue-50 text-primary flex items-center justify-center">
                                                    <Icon name="el-icon-UserFilled" :size="20" />
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-[14px] font-[900] text-tx-primary truncate">
                                                    {{ item.remark || item.nickname || "未知用户" }}
                                                </div>
                                                <div
                                                    class="text-[11px] text-tx-secondary mt-[4px] flex flex-col gap-[2px]">
                                                    <span class="opacity-70">ID: {{ item.friend_id }}</span>
                                                    <span class="flex items-center gap-[4px]">
                                                        <Icon
                                                            name="local-icon-wechat"
                                                            :size="10"
                                                            color="var(--green-500)" />
                                                        {{ item.nickname }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
            <div v-else class="h-full flex flex-col items-center justify-center bg-white">
                <ElEmpty description="当前选择的流程暂无客户数据" :image-size="120" />
            </div>
        </div>
    </div>

    <add-friend
        ref="addFriendRef"
        v-if="showAddFriend"
        :wechat-id="queryParams.wechat_id"
        @close="showAddFriend = false"
        @success="handleAddUserSuccess" />
    <user-detail-panel ref="userDetailRef" v-if="showUserDetail" @close="showUserDetail = false" />
    <board-handle
        ref="boardHandleRef"
        v-if="showBoardHandle"
        :wechat-id="queryParams.wechat_id"
        :flow-id="queryParams.flow_id"
        :stage-id="getStageId"
        :flow-lists="flowLists"
        :stage-lists="getStageLists"
        @close="showBoardHandle = false"
        @confirm="handleBoardHandleConfirm" />
</template>
<script setup lang="ts">
import {
    getWeChatLists,
    sopFlowBoard,
    sopFlowAddUser,
    sopFlowTransferUser,
    sopFlowDeleteUser,
} from "@/api/person_wechat";
import { ElOption, ElSelect } from "element-plus";
import Sortable from "sortablejs";
import { BoardHandleTypeEnum } from "../../_enums";
import AddFriend from "../../../_components/add-friend.vue";
import UserDetailPanel from "../../../_components/user-detail-panel.vue";
import BoardHandle from "./_components/board-handle.vue";
import useTask from "../../_hooks/useTask";
import useHandle from "../../../_hooks/useHandle";

const { currentWechat, currentFriend, friendInfo } = useHandle();

const queryParams = reactive({
    wechat_id: "",
    flow_id: "",
});

const loading = ref(false);
const boardLists = ref([]);
const listRefs = ref<any[]>([]);

const { flowLists, getFlowLists } = useTask();

const selectedMap = ref<Record<number, BoardHandleTypeEnum>>({});

const handleOptions = [
    {
        label: "清除此阶段内的所有客户",
        value: BoardHandleTypeEnum.CLEAR,
    },
    {
        label: "将此阶段内的客户转移到别的阶段",
        value: BoardHandleTypeEnum.TRANSFER,
    },
    {
        label: "将此阶段内的客户转移到别的周期阶段中",
        value: BoardHandleTypeEnum.TRANSFER_TO_CYCLE,
    },
    {
        label: "给此阶段内的客户群发消息（内测中）",
        value: BoardHandleTypeEnum.SEND_MESSAGE,
        disabled: true,
    },
];

const nuxtApp = useNuxtApp();

const getFriendLists = computed(() => {
    return boardLists.value[currentIndex.value].members;
});

const getStageLists = computed(() => {
    return flowLists.value.find((item) => item.id == queryParams.flow_id)?.key_stages;
});

const getStageId = computed(() => {
    return boardLists.value[currentIndex.value].stage_id;
});

const showBoardHandle = ref(false);
const boardHandleRef = ref<InstanceType<typeof BoardHandle>>();
const handleOptionClick = async (value: BoardHandleTypeEnum, index: number) => {
    currentIndex.value = index;

    if (value === BoardHandleTypeEnum.CLEAR) {
        if (getFriendLists.value.length == 0) {
            feedback.msgWarning("此阶段内没有数据");
            return;
        }
        nuxtApp.$confirm({
            title: "是否确认清除此阶段内的所有客户？",
            message: "删除选择的此阶段的客户后，这些删除的客户将不会出现在此阶段中，且该操作不可逆。",
            onConfirm: async () => {
                try {
                    await sopFlowDeleteUser({
                        wechat_id: queryParams.wechat_id,
                        flow_id: queryParams.flow_id,
                        stage_id: getStageId.value,
                        friend_id: getFriendLists.value.map((item) => item.friend_id),
                    });
                    initBoard();
                    feedback.msgSuccess("清空成功");
                } catch (error) {
                    feedback.msgError(error);
                }
            },
        });
        return;
    } else if (value == BoardHandleTypeEnum.SEND_MESSAGE) {
        feedback.msgWarning("该功能暂未开放");
        return;
    }
    showBoardHandle.value = true;
    await nextTick();
    boardHandleRef.value?.open(value);
};

const userDetailRef = shallowRef<InstanceType<typeof UserDetailPanel>>();
const showUserDetail = ref(false);

const openUserDetail = async (item: any) => {
    currentFriend.value = {
        UserName: item.friend_id,
    };
    currentWechat.value = {
        ...wechatLists.value.find((item) => item.wechat_id == queryParams.wechat_id),
    };
    friendInfo.value = {
        ...friendInfo.value,
        ...item,
    };
    showUserDetail.value = true;
    await nextTick();
    userDetailRef.value?.open();
};

const handleBoardHandleConfirm = async (data: any) => {
    try {
        await sopFlowTransferUser({
            ...data,
            wechat_id: queryParams.wechat_id,
            flow_id: queryParams.flow_id,
            friend_id: getFriendLists.value.map((item) => item.friend_id),
        });
        initBoard();
        feedback.msgSuccess("转移成功");
    } catch (error) {
        feedback.msgError(error);
    }
};

const addFriendRef = shallowRef<InstanceType<typeof AddFriend>>();
const showAddFriend = ref(false);
const currentIndex = ref(-1);
const handleAddUser = async (index: number) => {
    currentIndex.value = index;
    showAddFriend.value = true;
    await nextTick();
    addFriendRef.value?.open();
};

const handleAddUserSuccess = async (data: any[]) => {
    const friend_id = data.map((item) => item.friend_id);
    const stage_id = boardLists.value[currentIndex.value].stage_id;
    try {
        await sopFlowAddUser({
            wechat_id: queryParams.wechat_id,
            flow_id: queryParams.flow_id,
            friend_id: friend_id,
            stage_id: stage_id,
        });
        initBoard();
    } catch (error) {
        feedback.msgError(error);
    }
};

// 初始化 Sortable 实例
let sortables: Sortable[] = [];

const initSortable = () => {
    boardLists.value.forEach((_, index) => {
        const el = listRefs.value![index];
        if (el) {
            const sortable = Sortable.create(el, {
                group: "sharedGroup",
                animation: 150,
                forceFallback: true,
                ghostClass: "sortable-ghost", // 自定义拖拽时的样式
                chosenClass: "sortable-chosen", // 自定义选中时的样式
                dragClass: "sortable-drag", // 自定义拖拽时的样式
                onEnd: (event) => handleDragEnd(event, index),
            });
            sortables.push(sortable);
        }
    });
};

// 处理拖拽结束事件
const handleDragEnd = async (event: any, sourceIndex: number) => {
    const { oldIndex, newIndex, from, to } = event;
    const sourceData = boardLists.value[sourceIndex];
    const item = sourceData.members.splice(oldIndex, 1);
    // 确定目标列表的索引
    const targetIndex = boardLists.value.findIndex((list, idx) => listRefs.value![idx] === to);
    if (targetIndex !== -1) {
        boardLists.value[targetIndex].members.splice(newIndex, 0, ...item);
    }
    const targetData = boardLists.value[targetIndex];
    const membersData = targetData.members[newIndex];

    const sourceStageId = sourceData.stage_id;
    const targetStageId = targetData.stage_id;

    const params = {
        wechat_id: queryParams.wechat_id,
        flow_id: queryParams.flow_id,
        to_flow_id: queryParams.flow_id,
        friend_id: [membersData.friend_id],
        stage_id: sourceStageId,
        to_stage_id: targetStageId,
    };
    await sopFlowTransferUser(params);
    initBoard();
};

// 绑定列表元素引用
const setListRef = (el: any, index: number) => {
    listRefs.value![index] = el;
};

const wechatLists = ref<any[]>([]);
const getWechatLists = async () => {
    const { lists } = await getWeChatLists({ page_size: 999 });
    wechatLists.value = lists;
};

const getBoardLists = async () => {
    const data = await sopFlowBoard(queryParams);
    boardLists.value = data.sub_stage_list;
};

const init = async () => {
    await Promise.all([getWechatLists(), getFlowLists()]);
    if (wechatLists.value.length > 0) {
        queryParams.wechat_id = wechatLists.value[0].wechat_id;
    }

    if (flowLists.value.length > 0) {
        queryParams.flow_id = flowLists.value[0].id;
    }

    initBoard();
};

const initBoard = async () => {
    loading.value = true;
    try {
        await getBoardLists();
        await nextTick();
        await initSortable();
    } finally {
        loading.value = false;
    }
};

onMounted(init);

onBeforeUnmount(() => {
    sortables.forEach((s) => s.destroy());
});
</script>
<style scoped lang="scss">
.kanban-column {
    @apply w-[280px] bg-slate-50 border border-br flex flex-col shrink-0 rounded-[16px] overflow-hidden transition-all duration-300;

    &:hover {
        @apply border-primary-light-8 shadow-light;
    }
}

.kanban-header {
    @apply bg-white;
}

.more-action-btn {
    @apply w-[28px] h-[28px] flex items-center justify-center rounded-full text-gray-400 cursor-pointer transition-all;
    &:hover {
        @apply bg-gray-100 text-tx-primary;
    }
}

.add-user-trigger {
    @apply flex items-center justify-center h-[36px] bg-blue-50 text-primary text-[13px] font-medium rounded-[10px] cursor-pointer transition-all border border-primary-light-8;
    &:hover {
        @apply bg-primary text-white border-primary shadow-light;
    }
}

.member-card {
    @apply p-[14px] bg-white border border-br rounded-[12px] cursor-move transition-all;

    &:hover {
        @apply border-primary-light-5 -translate-y-[2px];
        box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.05);
    }
}

:deep(.sortable-ghost) {
    @apply opacity-40 bg-blue-100 border-2 border-dashed border-primary rounded-[12px];
}

:deep(.sortable-drag) {
    @apply rotate-[2deg] !scale-105;
    box-shadow: 0 10px 20px rgba(0, 101, 251, 0.15) !important;
}

:deep(.el-scrollbar__view) {
    @apply h-full;
}
</style>

<style lang="scss">
.custom-board-popover {
    @apply rounded-[12px] !border-br !p-[4px];
    .popover-item {
        @apply px-[12px] py-[8px] text-[13px] text-tx-primary rounded-[8px] cursor-pointer transition-all;
        &:hover {
            @apply bg-blue-50 text-primary;
        }
        &.is-disabled {
            @apply text-gray-300 cursor-not-allowed pointer-events-none;
        }
    }
}
</style>
