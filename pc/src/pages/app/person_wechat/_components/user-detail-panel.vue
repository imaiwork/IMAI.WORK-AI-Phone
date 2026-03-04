<template>
    <ElDrawer v-model="show" class="user-detail-panel" size="1000px" :show-close="false" @close="close">
        <div class="relative w-full h-full flex bg-transparent">
            <div class="flex-shrink-0 flex justify-center w-16 pt-6">
                <button
                    @click="close"
                    class="w-12 h-12 rounded-2xl bg-[#ffffff]/20 backdrop-blur-md text-white hover:bg-[#ffffff]/40 transition-all flex items-center justify-center shadow-xl border border-white/20">
                    <Icon name="local-icon-close" :size="28"></Icon>
                </button>
            </div>

            <div
                class="h-[calc(100vh-40px)] my-5 flex-1 flex flex-col bg-slate-50 rounded-l-[40px] shadow-[-20px_0_60px_rgba(0,0,0,0.1)] overflow-hidden">
                <div class="flex-shrink-0 bg-white px-8 pt-8 pb-6 relative">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-x-6">
                            <div class="relative">
                                <img
                                    :src="friendInfo.avatar"
                                    class="w-20 h-20 rounded-[28px] object-cover ring-4 ring-slate-50" />
                                <div
                                    class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 border-4 border-white"></div>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <h2 class="text-slate-800 text-[24px] font-medium">{{ friendInfo.nickname }}</h2>
                                    <span
                                        class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[11px] font-black rounded-md uppercase">
                                        {{ AccountTypeMap[AccountTypeEnum.Personal] }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-400 text-[13px] font-medium">
                                    <Icon name="el-icon-User" :size="14" />
                                    <span>备注：{{ friendInfo.remark || "未设置备注" }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="text-right">
                                <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">
                                    Join Source
                                </div>
                                <div class="text-[14px] text-slate-700 font-black">
                                    {{ AccountSource[friendInfo.source] || "未知渠道" }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-4 gap-6 p-5 bg-[#f8fafc]/50 rounded-[24px] border border-slate-100">
                        <div class="space-y-1">
                            <div class="text-[11px] text-slate-400 font-medium uppercase">出生日期</div>
                            <div class="text-[13px] text-slate-700 font-black">
                                {{ friendInfo.birth_date || "未填写" }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-[11px] text-slate-400 font-medium uppercase">联系地址</div>
                            <div class="text-[13px] text-slate-700 font-black truncate">
                                {{ friendInfo.contact_address || "未填写" }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-[11px] text-slate-400 font-medium uppercase">活跃程度</div>
                            <div class="flex items-center gap-1">
                                <div class="flex gap-0.5">
                                    <div v-for="i in 3" :key="i" class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                </div>
                                <span class="text-[13px] text-primary font-black ml-1">高活跃</span>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-[11px] text-slate-400 font-medium uppercase">最后互动</div>
                            <div class="text-[13px] text-slate-700 font-black">2小时前</div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-start gap-4">
                        <div
                            class="mt-1.5 shrink-0 px-3 py-1 bg-slate-800 text-white text-[10px] font-black rounded-lg uppercase tracking-tighter">
                            标签
                        </div>
                        <div class="flex flex-wrap gap-2 flex-1">
                            <div v-for="item in friendTagLists" :key="item.id" class="group relative">
                                <div
                                    class="px-3 py-1.5 bg-[#0065fb]/5 border border-[#0065fb]/10 text-primary text-xs font-black rounded-xl transition-all group-hover:bg-primary group-hover:text-white">
                                    {{ item.tag_name }}
                                </div>
                                <div
                                    v-if="isEditTag"
                                    @click="deleteTag(item.tag_id)"
                                    class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:scale-110 transition-transform">
                                    <Icon name="el-icon-Close" :size="10" />
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="handleAddTag"
                                    class="w-8 h-8 rounded-xl border-2 border-dashed border-slate-200 text-slate-400 flex items-center justify-center hover:border-primary hover:text-primary transition-all">
                                    <Icon name="el-icon-Plus" :size="14" />
                                </button>
                                <button
                                    @click="isEditTag = !isEditTag"
                                    class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-800 hover:text-white transition-all">
                                    <Icon :name="isEditTag ? 'el-icon-Check' : 'el-icon-Edit'" :size="14" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="grow min-h-0 mt-4 flex flex-col bg-white rounded-t-[40px] shadow-[0_-10px_40px_rgba(0,0,0,0.02)]">
                    <ElTabs v-model="activeTab" class="modern-tabs h-full" @tab-click="handleTabClick">
                        <ElTabPane label="客户流程" name="1">
                            <UserFlow :flow-data="friendSopFlow" />
                        </ElTabPane>
                        <ElTabPane label="待办活动" name="2">
                            <ElScrollbar class="h-full" @end-reached="handleTodoScrollEnd">
                                <div class="px-8 py-6">
                                    <UserTodo :list="todoPager.lists" @edit="handleEditTodo" @delete="deleteTodo" />
                                </div>
                            </ElScrollbar>
                        </ElTabPane>
                        <ElTabPane label="SOP 任务" name="3">
                            <ElScrollbar class="h-full"
                                ><div class="px-8 py-6"><UserSop :list="friendSopPush" @delete="handleDeleteSop" /></div
                            ></ElScrollbar>
                        </ElTabPane>
                        <ElTabPane label="群发任务" name="4">
                            <ElScrollbar class="h-full"
                                ><div class="px-8 py-6"><UserSop :list="friendSopPush" @delete="handleDeleteSop" /></div
                            ></ElScrollbar>
                        </ElTabPane>
                    </ElTabs>
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
.modern-tabs {
    :deep(.el-tabs__header) {
        @apply m-0 px-8 pt-4 bg-white border-none;
        .el-tabs__nav-wrap::after {
            display: none;
        }
        .el-tabs__active-bar {
            @apply h-[4px] rounded-full bg-primary shadow-[0_2px_10px_rgba(0,101,251,0.4)];
        }
        .el-tabs__item {
            @apply h-14 text-[15px] font-medium text-slate-400 transition-all;
            &.is-active {
                @apply text-primary font-medium text-[16px];
            }
        }
    }
    :deep(.el-tabs__content) {
        @apply flex-1 overflow-hidden bg-slate-50;
    }
}

:deep(.user-detail-panel) {
    .el-drawer__body {
        @apply bg-[#0f172a]/10 backdrop-blur-sm;
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
