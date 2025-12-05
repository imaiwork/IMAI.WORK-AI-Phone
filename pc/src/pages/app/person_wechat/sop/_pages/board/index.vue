<template>
    <div class="h-full flex flex-col">
        <div class="bg-white p-4 rounded-xl">
            <div class="flex items-center gap-2">
                <ElSelect
                    v-model="queryParams.wechat_id"
                    placeholder="请选择微信"
                    class="!w-[200px]"
                    @change="getBoardLists">
                    <template #prefix>
                        <div>选择微信</div>
                    </template>
                    <ElOption
                        v-for="item in wechatLists"
                        :key="item.id"
                        :label="item.wechat_nickname"
                        :value="item.wechat_id"></ElOption>
                </ElSelect>
                <ElSelect
                    v-model="queryParams.flow_id"
                    placeholder="请选择客户流程"
                    class="!w-[200px]"
                    @change="getBoardLists">
                    <template #prefix>
                        <div>客户流程</div>
                    </template>
                    <ElOption
                        v-for="item in flowLists"
                        :key="item.id"
                        :label="item.flow_name"
                        :value="item.id"></ElOption>
                </ElSelect>
            </div>
        </div>
        <div class="grow min-h-0 bg-white rounded-xl mt-4 px-6" v-loading="loading">
            <ElScrollbar v-if="boardLists.length > 0">
                <div class="flex gap-5 py-6 h-full whitespace-nowrap">
                    <div
                        v-for="({ members, sub_stage_name }, index) in boardLists"
                        :key="index"
                        class="rounded-md border border-[#E0E0E0] w-[243px] flex flex-col flex-shrink-0 overflow-hidden">
                        <div class="flex-shrink-0">
                            <div class="h-[4px] bg-[#E0E0E0]">
                                <div
                                    class="h-full"
                                    :style="{
                                        width: `${((index + 1) / boardLists.length) * 100}%`,
                                        backgroundColor: 'var(--color-primary)',
                                    }"></div>
                            </div>
                            <div
                                class="h-[72px] flex items-center justify-between border-b border-primary-light-8 px-4">
                                <div class="flex items-center gap-2">
                                    <Icon name="el-icon-LocationFilled" color="var(--color-primary)" :size="20"></Icon>
                                    <span class="text-[#5E656E] text-lg">{{ sub_stage_name }}</span>
                                </div>
                                <ElPopover
                                    trigger="click"
                                    :show-arrow="false"
                                    popper-class="!w-[290px] !rounded-lg !p-0">
                                    <div class="py-2">
                                        <div class="flex flex-col">
                                            <div
                                                v-for="option in handleOptions"
                                                :key="option.value"
                                                class="flex items-center gap-2 h-9 px-4 cursor-pointer hover:bg-primary-light-9 hover:text-primary"
                                                :class="{
                                                    'bg-primary-light-8 text-primary':
                                                        selectedMap[index] === option.value,
                                                }"
                                                @click="handleOptionClick(option.value, index)">
                                                {{ option.label }}
                                            </div>
                                        </div>
                                    </div>
                                    <template #reference>
                                        <ElButton type="primary" link>操作</ElButton>
                                    </template>
                                </ElPopover>
                            </div>
                            <div
                                class="flex items-center justify-center gap-x-4 bg-primary-light-9 p-2 rounded-md mx-2 mt-6 cursor-pointer"
                                @click="handleAddUser(index)">
                                <Icon name="local-icon-add_box_fill" color="var(--color-primary)" :size="20"></Icon>
                                <span class="text-primary">添加用户</span>
                            </div>
                        </div>
                        <div class="min-h-0 grow">
                            <ElScrollbar>
                                <div
                                    class="p-4 flex flex-col gap-4 h-full"
                                    :ref="(el) => setListRef(el, index)"
                                    :data-index="index">
                                    <div
                                        v-for="item in members"
                                        :key="`List${index + 1}-Item${item.friend_id}`"
                                        class="flex gap-x-4 p-3 bg-white shadow-lighter mb-2 rounded cursor-move"
                                        @click="openUserDetail(item)">
                                        <div class="flex-shrink-0">
                                            <img class="w-12 h-12 rounded-md" :src="item.avatar" v-if="item.avatar" />
                                            <ElAvatar
                                                v-else
                                                :size="48"
                                                :src="item.avatar"
                                                shape="square"
                                                icon="el-icon-UserFilled" />
                                        </div>
                                        <div>
                                            <div class="font-medium mb-1">
                                                {{ item.remark || "-" }}
                                            </div>
                                            <div class="text-[#9E9E9E] text-xs whitespace-normal">
                                                微信昵称：{{ item.nickname }}
                                            </div>
                                            <div class="text-[#9E9E9E] text-xs whitespace-normal">
                                                微信ID：{{ item.friend_id }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ElScrollbar>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
            <div v-else class="flex items-center justify-center h-full">
                <ElEmpty description="暂无数据" />
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
:deep(.el-select__wrapper) {
    min-height: 34px;
}
:deep(.el-scrollbar__view) {
    height: 100%;
}

.sortable-ghost {
    opacity: 0.4;
    background: #c8ebfb;
}

.sortable-chosen {
    background: #f0f9ff;
}

.sortable-drag {
    opacity: 1 !important;
    transform: rotate(2deg);
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
</style>

<style lang="scss">
.transfer-message-box {
    .el-message-box__message {
        width: 100%;
    }
}
</style>
