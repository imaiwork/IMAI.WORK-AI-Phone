<template>
    <div class="w-full h-full bg-white flex flex-col">
        <div class="grow min-h-0">
            <ElScrollbar ref="scrollbarRef" @scroll="scroll">
                <div class="px-4 pb-4 flex flex-col gap-y-4 mt-4">
                    <div class="shadow-lighter rounded-lg p-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-2">
                                <span class="w-[4px] h-[14px] bg-primary"></span>
                                <span class="">基本信息</span>
                            </div>
                            <div
                                class="cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-lg leading-[0]"
                                @click="openUserInfoPop">
                                <Icon name="el-icon-Edit" :size="20" color="#9E9E9E" />
                            </div>
                        </div>
                        <template v-if="friendInfo.friend_id">
                            <div
                                class="flex gap-x-2 cursor-pointer py-2 px-1 rounded hover:bg-token-sidebar-surface-secondary"
                                @click="openUserDetailPop">
                                <div class="flex-shrink-0">
                                    <img
                                        v-if="friendInfo.avatar"
                                        :src="friendInfo.avatar"
                                        class="w-[56px] h-[56px] rounded-[2px] object-cover" />
                                    <ElAvatar v-else :size="56" :icon="Avatar" />
                                </div>
                                <div>
                                    <div class="">
                                        {{ friendInfo.remark || friendInfo.nickname }}
                                    </div>
                                    <div class="text-[#9E9E9E] text-xs mt-1 break-all">
                                        微信昵称：{{ friendInfo.nickname }}
                                    </div>
                                    <div class="text-[#9E9E9E] text-xs break-all">
                                        微信ID：{{ friendInfo.friend_no || friendInfo.friend_id }}
                                    </div>
                                </div>
                            </div>
                            <ElDivider border-style="dashed" class="!my-2" />
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs flex">
                                    <span class="text-[#9E9E9E] flex-shrink-0">账号类型：</span>
                                    <span class="text-[#07C160]">@{{ AccountTypeMap[AccountTypeEnum.Personal] }}</span>
                                </div>
                                <div class="text-xs flex">
                                    <span class="text-[#9E9E9E] flex-shrink-0">手机号码：</span>
                                    <span>{{ friendInfo.phone || "暂无" }}</span>
                                </div>
                                <div class="text-xs flex">
                                    <span class="text-[#9E9E9E] flex-shrink-0">来源方式：</span>
                                    <span>{{ AccountSource[friendInfo.source] || "未知" }}</span>
                                </div>
                                <div class="text-xs flex">
                                    <span class="text-[#9E9E9E] flex-shrink-0">出生日期：</span>
                                    <span>{{ friendInfo.birth_date || "暂无" }}</span>
                                </div>
                                <div class="text-xs flex">
                                    <span class="text-[#9E9E9E] flex-shrink-0">联系地址：</span>
                                    <span>{{ friendInfo.contact_address || "暂无" }}</span>
                                </div>
                            </div>
                        </template>
                        <div v-else>
                            <ElEmpty description="暂无数据" :image-size="100" />
                        </div>
                    </div>
                    <div class="shadow-lighter rounded-lg p-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-2">
                                <span class="w-[4px] h-[14px] bg-primary"></span>
                                <span class="">标签信息</span>
                            </div>
                            <div
                                class="cursor-pointer p-1 hover:bg-token-sidebar-surface-secondary rounded-lg leading-[0]"
                                @click="isEditTag = !isEditTag">
                                <Icon name="el-icon-Setting" :size="20" color="#9E9E9E" />
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <div class="h-[24px] w-[64px] el-tag el-tag--primary el-tag--light" @click="handleAddTag">
                                <span
                                    class="w-[14px] h-[14px] bg-primary flex items-center justify-center rounded-[2px]">
                                    <Icon name="el-icon-Plus" :size="10" color="#ffffff" />
                                </span>
                            </div>
                            <div v-for="item in friendTagLists" :key="item.tag_id" class="relative">
                                <ElTag type="primary">{{ item.tag_name }}</ElTag>
                                <div class="absolute -top-1 -right-1" v-if="isEditTag">
                                    <span
                                        class="bg-[#D43030] rounded-full w-[14px] h-[14px] flex items-center justify-center cursor-pointer"
                                        @click="deleteTag(item.tag_id)">
                                        <Icon name="el-icon-Close" :size="10" color="#ffffff" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="shadow-lighter rounded-lg p-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-x-2">
                                <span class="w-[4px] h-[14px] bg-primary"></span>
                                <span class="">跟进信息</span>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-col gap-y-5" v-if="todoPager.lists.length">
                            <div v-for="(item, index) in todoPager.lists" :key="item.id" class="flex gap-x-4">
                                <div class="relative">
                                    <div class="bg-black rounded-full w-2 h-2 mt-[6px]"></div>
                                    <div
                                        class="h-[80%] border-r border-dashed absolute right-1 mt-3"
                                        v-if="index < todoPager.lists.length"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            {{ dayjs(item.create_time).format("YYYY-MM-DD HH:mm") }}
                                        </div>
                                        <ElButton link type="danger" size="small" @click="handleDeleteTodo(item.id)">
                                            删除
                                        </ElButton>
                                    </div>
                                    <div class="flex flex-col gap-y-1 mt-1">
                                        <div class="text-xs flex">
                                            <span class="text-[#9E9E9E] flex-shrink-0">活动类型：</span>
                                            <span>{{ item.todo_type == 0 ? "添加代办" : "自动跟进" }}</span>
                                        </div>
                                        <div class="text-xs flex">
                                            <span class="text-[#9E9E9E] flex-shrink-0">办理日期：</span>
                                            <span>{{ item.todo_time }}</span>
                                        </div>
                                        <div class="text-xs flex">
                                            <span class="text-[#9E9E9E] flex-shrink-0">待办内容：</span>
                                            <span>{{ item.todo_content }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <ElEmpty description="暂无数据" :image-size="100" />
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="h-[65px] shadow-lighter bg-white flex-shrink-0 px-4">
            <div class="flex items-center h-full">
                <div
                    class="flex flex-col items-center gap-x-2 py-[6px] hover:bg-token-sidebar-surface-secondary rounded-lg cursor-pointer flex-1"
                    @click="openTodoPop(0)">
                    <Icon name="local-icon-todo" :size="20"></Icon>
                    <span class="mt-1 text-[#727D8F] text-xs">添加待办</span>
                </div>
                <ElDivider direction="vertical" class="!h-[50%]" />
                <div
                    class="flex flex-col items-center gap-x-2 py-[6px] hover:bg-token-sidebar-surface-secondary rounded-lg cursor-pointer flex-1"
                    @click="openTodoPop(1)">
                    <Icon name="local-icon-todo_list" :size="20"></Icon>
                    <span class="mt-1 text-[#727D8F] text-xs">自动跟进</span>
                </div>
            </div>
        </div>
    </div>
    <popup
        ref="userInfoPopRef"
        v-if="showUserInfoPop"
        async
        title="信息修改"
        width="487px"
        confirm-button-text="确认保存"
        :confirm-loading="userInfoIsLocked"
        @confirm="confirmUserInfo"
        @close="showUserInfoPop = false">
        <ElForm ref="userInfoFormRef" :model="userInfoForm" label-position="top">
            <ElFormItem label="请输入修改后的微信备注名称（若为空则不进行备注）">
                <ElInput v-model="userInfoForm.remark" placeholder="请输入微信备注名称" />
            </ElFormItem>
            <ElFormItem label="请输入修改后的手机号码（若为空则清空手机信息）">
                <ElInput v-model="userInfoForm.phone" placeholder="请输入手机号码" />
            </ElFormItem>
            <ElFormItem label="请输入修改后的联系地址（若为空则清空地址信息）">
                <ElInput v-model="userInfoForm.contact_address" placeholder="请输入联系地址" />
            </ElFormItem>
            <ElFormItem label="请输入修改后的出生日期（若为空则清空出生日期）">
                <ElDatePicker
                    class="!w-full"
                    v-model="userInfoForm.birth_date"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="请选择出生日期" />
            </ElFormItem>
        </ElForm>
    </popup>
    <user-todo-edit
        v-if="showTodoPop"
        ref="userTodoEditRef"
        :wechat-id="currentWechat.wechat_id"
        :friend-id="currentFriend.UserName"
        @change-tag="getFriendTagDetail"
        @change-todo="resetTodoPage"
        @close="showTodoPop = false"
        @confirm="handleSuccessTodo" />
    <friends-bind-tag v-if="showTagPop" ref="friendsBindTagRef" @close="showTagPop = false" />
    <user-detail-panel v-if="showUserDetailPop" ref="userDetailPopRef" @close="showUserDetailPop = false" />
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { Avatar } from "@element-plus/icons-vue";
import { dayjs, type ElScrollbar } from "element-plus";
import { AccountSource, AccountTypeEnum, AccountTypeMap, HandleEventEnum } from "~/pages/app/person_wechat/_enums";
import FriendsBindTag from "./friends-bind-tag.vue";
import useHandle from "../../_hooks/useHandle";
import useTodo from "../../_hooks/useTodo";
import UserDetailPanel from "../../_components/user-detail-panel.vue";
import UserTodoEdit from "../../_components/user-todo-edit.vue";

const nuxtApp = useNuxtApp();

const {
    currentWechat,
    currentFriend,
    friendInfo,
    userInfoIsLocked,
    userInfoForm,
    friendTagLists,
    getFriendTagDetail,
    deleteFriendTag,
    updateUserInfo,
    triggerHandleEvent,
} = useHandle();

const { todoPager, todoParams, getTodoLists, resetTodoPage, handleDeleteTodo } = useTodo();

const userInfoPopRef = ref<InstanceType<typeof Popup> | null>(null);
const showUserInfoPop = ref<boolean>(false);

const openUserInfoPop = async () => {
    showUserInfoPop.value = true;
    await nextTick();
    userInfoPopRef.value?.open();
    setFormData(friendInfo.value, userInfoForm);
};

const confirmUserInfo = async () => {
    await updateUserInfo(userInfoForm);
    triggerHandleEvent("action", {
        type: HandleEventEnum.UpdateUserInfo,
        ...userInfoForm,
    });
    showUserInfoPop.value = false;
};

const showUserDetailPop = ref<boolean>(false);
const userDetailPopRef = ref<InstanceType<typeof UserDetailPanel>>();

const openUserDetailPop = async () => {
    showUserDetailPop.value = true;
    await nextTick();
    userDetailPopRef.value?.open();
};

const userTodoEditRef = ref<InstanceType<typeof UserTodoEdit>>();
const showTodoPop = ref<boolean>(false);

const openTodoPop = async (type: number) => {
    showTodoPop.value = true;
    await nextTick();
    userTodoEditRef.value?.open(type);
};

const handleSuccessTodo = async () => {
    todoPager.lists = [];
    resetTodoPage();
};

const friendsBindTagRef = ref<InstanceType<typeof FriendsBindTag>>();
const showTagPop = ref<boolean>(false);
const isEditTag = ref<boolean>(false);

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
                feedback.msgSuccess("删除标签成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

//滚动条ref
const scrollbarRef = ref<any>(null);

// 判断是否滚动到底部
const isScrollToBottom = (scrollbarRef: Ref<InstanceType<typeof ElScrollbar> | null>) => {
    if (!scrollbarRef.value?.wrapRef) return false;

    const { scrollTop, scrollHeight, clientHeight } = scrollbarRef.value.wrapRef;
    // 考虑1px的误差
    return Math.abs(scrollHeight - scrollTop - clientHeight) <= 1;
};

//对话框滚动
const scroll = async () => {
    if (isScrollToBottom(scrollbarRef) && !todoPager.isLoad) {
        todoParams.page_no++;
        await getTodoLists();
    }
};

const init = async () => {
    todoPager.lists = [];
    todoParams.page_no = 1;
    todoParams.wechat_id = currentWechat.value.wechat_id;
    todoParams.friend_id = currentFriend.value.UserName;
    await Promise.allSettled([getTodoLists(), getFriendTagDetail()]);
};

defineExpose({
    init,
});
</script>

<style scoped></style>
