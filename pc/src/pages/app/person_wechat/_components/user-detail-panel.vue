<template>
    <ElDrawer v-model="show" class="user-detail-panel" size="1000px" :show-close="false" @close="close">
        <div class="relative w-full h-full flex">
            <div class="flex-shrink-0">
                <ElButton type="primary" class="w-10 h-10 mt-2" @click="close">
                    <Icon name="local-icon-close" :size="24" color="#ffffff"></Icon>
                </ElButton>
            </div>
            <div class="h-full flex-1 flex flex-col bg-[#F5F6F9] overflow-hidden">
                <div class="flex-shrink-0 bg-white px-4 border-b border-[#E6E6E6] pb-5 relative">
                    <div class="flex items-center gap-x-4 mt-5">
                        <img :src="friendInfo.avatar" class="w-12 h-12 rounded object-cover" />
                        <div>
                            <div class="text-xs text-[#9E9E9E]">{{ friendInfo.remark || "-" }}</div>
                            <div class="text-[#42453F] text-[15px] font-bold">{{ friendInfo.nickname }}</div>
                        </div>
                    </div>
                    <div class="mt-5 flex gap-x-12">
                        <div>
                            <div class="text-[#8A8A8A]">账号类型：</div>
                            <div class="text-[#07C160] text-xs">@{{ AccountTypeMap[AccountTypeEnum.Personal] }}</div>
                        </div>
                        <div>
                            <div class="text-[#8A8A8A]">来源方式：</div>
                            <div class="text-[#494949] text-xs mt-1">
                                {{ AccountSource[friendInfo.source] || "未知" }}
                            </div>
                        </div>
                        <div class="t">
                            <div class="text-[#8A8A8A]">出生日期：</div>
                            <div class="text-xs">{{ friendInfo.birth_date || "暂无" }}</div>
                        </div>
                        <div class="">
                            <div class="text-[#8A8A8A]">联系地址：</div>
                            <div class="text-xs">{{ friendInfo.contact_address || "暂无" }}</div>
                        </div>
                    </div>
                    <div class="mt-5 border border-[#DBDBDB] rounded-lg p-3">
                        <div class="flex items-center justify-between">
                            <div class="font-bold">客户标签</div>
                            <ElButton link @click="isEditTag = !isEditTag">
                                <Icon name="el-icon-Edit" color="#9E9E9E" :size="16"></Icon>
                            </ElButton>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-4">
                            <div v-for="item in friendTagLists" :key="item.id" class="relative">
                                <ElTag type="primary">{{ item.tag_name }}</ElTag>
                                <div class="absolute -top-1 -right-1" v-if="isEditTag">
                                    <span
                                        class="bg-[#D43030] rounded-full w-[14px] h-[14px] flex items-center justify-center cursor-pointer"
                                        @click="deleteTag(item.tag_id)">
                                        <Icon name="el-icon-Close" :size="10" color="#ffffff" />
                                    </span>
                                </div>
                            </div>
                            <div class="">
                                <div
                                    class="h-[24px] w-[64px] el-tag el-tag--primary el-tag--light"
                                    @click="handleAddTag">
                                    <span
                                        class="w-[14px] h-[14px] bg-primary flex items-center justify-center rounded-[2px]">
                                        <Icon name="el-icon-Plus" :size="10" color="#ffffff" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grow min-h-0 flex gap-x-6 mt-6 w-full">
                    <div class="flex-1 flex flex-col w-full overflow-hidden" v-loading="loading">
                        <ElTabs
                            v-model="activeTab"
                            class="tab-panel h-full w-full rounded-tr-md"
                            type="border-card"
                            @tab-click="handleTabClick">
                            <ElTabPane label="客户流程" name="1">
                                <UserFlow :flow-data="friendSopFlow" />
                            </ElTabPane>
                            <ElTabPane label="待办活动" name="2">
                                <ElScrollbar class="h-full" ref="todoScrollRef" @end-reached="handleTodoScrollEnd">
                                    <div class="px-4 py-6">
                                        <UserTodo
                                            :list="todoPager.lists"
                                            v-if="todoPager.lists.length > 0"
                                            @edit="handleEditTodo"
                                            @delete="deleteTodo" />
                                        <div v-else class="mt-14">
                                            <ElEmpty description="暂无内容"></ElEmpty>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </ElTabPane>
                            <ElTabPane label="SOP任务" name="3">
                                <ElScrollbar class="h-full">
                                    <div class="px-4 py-6">
                                        <UserSop
                                            :list="friendSopPush"
                                            v-if="friendSopPush.length > 0"
                                            @delete="handleDeleteSop" />
                                        <div v-else class="mt-14">
                                            <ElEmpty description="暂无内容"></ElEmpty>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </ElTabPane>
                            <ElTabPane label="群发任务" name="4">
                                <ElScrollbar class="h-full">
                                    <div class="px-4 py-6">
                                        <UserSop
                                            :list="friendSopPush"
                                            v-if="friendSopPush.length > 0"
                                            @delete="handleDeleteSop" />
                                        <div v-else class="mt-14">
                                            <ElEmpty description="暂无内容"></ElEmpty>
                                        </div>
                                    </div>
                                </ElScrollbar>
                            </ElTabPane>
                        </ElTabs>
                    </div>
                </div>
            </div>
        </div>
    </ElDrawer>
    <friends-bind-tag
        v-if="showTagPop"
        ref="friendsBindTagRef"
        @close="showTagPop = false"
        @success="
            emit('changeTag');
            getFriendTagDetail();
        " />
    <user-todo-edit
        v-if="showTodoPop"
        ref="userTodoEditRef"
        :wechat-id="currentWechat.wechat_id"
        :friend-id="currentFriend.UserName"
        @close="showTodoPop = false"
        @confirm="handleSuccessTodo" />
</template>

<script setup lang="ts">
import { ElScrollbar } from "element-plus";
import { AccountSource, AccountTypeEnum, AccountTypeMap } from "~/pages/app/person_wechat/_enums";
import { PushTypeEnum } from "../sop/_enums";
import UserFlow from "./user-flow.vue";
import UserTodo from "./user-todo.vue";
import UserSop from "./user-sop.vue";
import useHandle from "../_hooks/useHandle";
import useTodo from "../_hooks/useTodo";
import FriendsBindTag from "../chat/_components/friends-bind-tag.vue";
import UserTodoEdit from "./user-todo-edit.vue";
const emit = defineEmits<{
    (event: "close"): void;
    (event: "changeTodo"): void;
    (event: "changeTag"): void;
}>();

const nuxtApp = useNuxtApp();

const {
    currentWechat,
    currentFriend,
    friendInfo,
    friendTagLists,
    friendSopFlow,
    friendSopPush,
    getFriendTagDetail,
    deleteFriendTag,
    getWeChatFriendSopFlow,
    getWeChatFriendSopPush,
    deleteWeChatFriendSopPush,
} = useHandle();

const { todoPager, todoParams, getTodoLists, resetTodoPage, handleDeleteTodo } = useTodo();

const show = ref<boolean>(false);
const activeTab = ref("1");

const handleTabClick = async (tab: any) => {
    if (tab.paneName == activeTab.value) {
        return;
    }
    if (tab.paneName === "1") {
        await getWeChatFriendSopFlow();
    }
    if (tab.paneName === "2") {
        await resetTodoPage();
    }
    if (tab.paneName === "3") {
        await getWeChatFriendSopPush({ push_type: PushTypeEnum.AUTO_SOP });
    } else if (tab.paneName === "4") {
        await getWeChatFriendSopPush({ push_type: PushTypeEnum.TASK });
    }
};

const isEditTag = ref<boolean>(false);

const showTagPop = ref<boolean>(false);
const friendsBindTagRef = ref<InstanceType<typeof FriendsBindTag>>();

const handleAddTag = async () => {
    showTagPop.value = true;
    await nextTick();
    friendsBindTagRef.value?.open();
};

const deleteTag = (id: number) => {
    nuxtApp.$confirm({
        message: "确定要删除该标签吗？",
        onConfirm: async () => {
            try {
                await deleteFriendTag(id);
                emit("changeTag");
                feedback.msgSuccess("删除标签成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const deleteTodo = async (id: number) => {
    await handleDeleteTodo(id);
    emit("changeTodo");
};

const todoScrollRef = ref<InstanceType<typeof ElScrollbar>>();

const handleTodoScrollEnd = (e: any) => {
    if (e == "bottom" && todoPager.isLoad && !todoPager.loading) {
        todoPager.page++;
        getTodoLists();
    }
};

const handleDeleteSop = async (id: number) => {
    try {
        await deleteWeChatFriendSopPush({ id });
        feedback.msgSuccess("删除成功");
        if (activeTab.value == "3") {
            getWeChatFriendSopPush({ push_type: PushTypeEnum.AUTO_SOP });
        } else if (activeTab.value == "4") {
            getWeChatFriendSopPush({ push_type: PushTypeEnum.TASK });
        }
    } catch (error) {
        feedback.msgError(error);
    }
};

const showTodoPop = ref<boolean>(false);
const userTodoEditRef = ref<InstanceType<typeof UserTodoEdit>>();

const handleEditTodo = async (item: any) => {
    showTodoPop.value = true;
    await nextTick();
    userTodoEditRef.value?.open(item.todo_type);
    userTodoEditRef.value.setFormData(item);
};

const handleSuccessTodo = async () => {
    todoPager.lists = [];
    resetTodoPage();
};

const loading = ref(true);

const open = async () => {
    show.value = true;
    loading.value = true;

    todoParams.friend_id = currentFriend.value.UserName;
    todoParams.wechat_id = currentWechat.value.wechat_id;
    try {
        await Promise.allSettled([getFriendTagDetail(), getWeChatFriendSopFlow()]);
    } finally {
        loading.value = false;
    }
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.tab-panel {
    :deep(.el-tabs__content) {
        @apply h-full p-0;
    }
    :deep(.el-tab-pane) {
        @apply h-full;
    }
    :deep(.el-tabs__header) {
        @apply rounded-tr-md bg-[#F5F6F9];
        .el-tabs__item {
            @apply h-12;
            &.is-active {
                @apply relative;
                &::after {
                    @apply content-[''] absolute top-0 left-0 w-full h-[2px] bg-primary;
                }
            }
        }
    }
}
</style>

<style lang="scss">
.user-detail-panel {
    background-color: transparent !important;
    box-shadow: none !important;
    .el-drawer__header {
        display: none;
    }
    .el-drawer__body {
        padding: 0;
    }
}
</style>
