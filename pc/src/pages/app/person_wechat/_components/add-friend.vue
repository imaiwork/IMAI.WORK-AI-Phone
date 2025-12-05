<template>
    <popup
        custom-class="add-friend"
        ref="popupRef"
        hide-title
        width="900px"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text="">
        <div class="w-full bg-white rounded-lg flex h-[50vh] overflow-hidden">
            <div class="w-[94px] flex-shrink-0">
                <SidebarPanel
                    :wechat-list="wechatLists"
                    :show-add-we-chat="false"
                    :current-wechat="{ wechat_id: wechatId }" />
            </div>
            <div class="flex-1 flex flex-col border-r border-gray-200">
                <div class="flex-shrink-0">
                    <div class="h-[70px] flex items-center justify-end px-4">
                        <ElInput
                            v-model="friendParams.nickname"
                            placeholder="搜索"
                            class="!w-[200px] h-[32px]"
                            clearable
                            @clear="search()"
                            @keyup.enter="search()">
                            <template #append>
                                <ElButton @click="search()">
                                    <Icon name="el-icon-Search"></Icon>
                                </ElButton>
                            </template>
                        </ElInput>
                    </div>
                </div>
                <div class="grow min-h-0">
                    <ElTable
                        ref="friendTableRef"
                        :data="friendPager.lists"
                        stripe
                        height="100%"
                        :row-style="{ height: '60px' }"
                        row-key="friend_id"
                        @selection-change="handleSelectionChange">
                        <ElTableColumn type="selection" reserve-selection width="55" fixed="left" />
                        <ElTableColumn prop="friend_nickname" label="微信名称" />
                        <ElTableColumn prop="avatar" label="头像">
                            <template #default="{ row }">
                                <img :src="row.friend_avatar" class="w-8 h-8 rounded mx-auto" />
                            </template>
                        </ElTableColumn>
                        <ElTableColumn prop="remark" label="备注名称">
                            <template #default="{ row }">
                                {{ row.remark || "-" }}
                            </template>
                        </ElTableColumn>
                    </ElTable>
                </div>
                <div class="p-4 flex justify-end">
                    <pagination v-model="friendPager" @change="getFriendLists" />
                </div>
            </div>
            <div class="w-[248px] flex-shrink-0 flex flex-col">
                <div class="flex-shrink-0">
                    <div class="w-full h-[66px] bg-primary flex justify-between items-center px-4">
                        <div class="text-white">已选好友（{{ multipleSelection.length }}）人</div>
                        <ElButton link @click="handleClear">
                            <Icon name="local-icon-clean" :size="16" color="#ffffff"></Icon>
                            <span class="text-white ml-2">清空</span>
                        </ElButton>
                    </div>
                </div>
                <div class="grow min-h-0 py-4">
                    <ElScrollbar v-if="multipleSelection.length > 0">
                        <div class="flex flex-col gap-y-4 px-4">
                            <div
                                v-for="item in multipleSelection"
                                :key="item.friend_id"
                                class="flex items-center gap-x-4">
                                <img :src="item.friend_avatar" class="flex-shrink-0 w-8 h-8 rounded" />
                                <div class="flex-1 text-[#414142]">
                                    <div class="line-clamp-1">{{ item.friend_nickname }}</div>
                                    <div>{{ item.remark || "-" }}</div>
                                </div>
                                <div
                                    class="flex-shrink-0 w-4 h-4 rounded-full bg-error flex items-center justify-center cursor-pointer"
                                    @click="handleRemove(item)">
                                    <Icon name="local-icon-close" color="#ffffff"></Icon>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                    <div v-else class="flex items-center justify-center h-full">
                        <ElEmpty description="暂无数据" />
                    </div>
                </div>
                <div class="flex-shrink-0 mx-2 py-2">
                    <ElButton type="primary" class="w-full !h-[37px]" :loading="isLock" @click="lockFn">
                        确定添加
                    </ElButton>
                </div>
            </div>
        </div>
        <div class="absolute -right-10 top-2">
            <ElButton circle type="primary" @click="close">
                <Icon name="local-icon-close" color="#ffffff" :size="16"></Icon>
            </ElButton>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import {
    getWeChatLists as getWeChatListsApi,
    getWeChatFriendLists as getWeChatFriendListsApi,
} from "@/api/person_wechat";
import { ElTable } from "element-plus";
import SidebarPanel from "../chat/_components/sidebar-panel.vue";

const props = defineProps<{
    wechatId: string;
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "success", data: any[]): void;
}>();

const nuxtApp = useNuxtApp();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const friendTableRef = shallowRef<InstanceType<typeof ElTable>>();

const wechatLists = ref<any[]>([]);
const friendParams = reactive({
    wechat_id: "",
    nickname: "",
});

const { pager: friendPager, getLists: getFriendLists } = usePaging({
    fetchFun: getWeChatFriendListsApi,
    params: friendParams,
});

const search = async () => {
    await getFriendLists();
    const selectionRows = friendTableRef.value?.getSelectionRows();
    friendTableRef.value?.clearSelection();
    selectionRows.forEach((item) => {
        friendTableRef.value?.toggleRowSelection(item);
    });
};

const multipleSelection = ref<any[]>([]);
const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleRemove = async (data: any) => {
    friendTableRef.value?.toggleRowSelection(data, undefined);
};

const handleClear = async () => {
    await nuxtApp.$confirm({
        message: "确定要清空吗？",
        onConfirm: async () => {
            multipleSelection.value = [];
            friendTableRef.value?.clearSelection();
        },
    });
};

const { isLock, lockFn } = useLockFn(async () => {
    if (multipleSelection.value.length === 0) {
        feedback.msgWarning("请选择要添加的好友");
        return;
    }
    close();
    emit("success", multipleSelection.value);
});

const getWeChatLists = async () => {
    const { lists } = await getWeChatListsApi({ page_size: 999, wechat_id: props.wechatId });
    wechatLists.value = lists;
    if (wechatLists.value.length > 0) {
        friendParams.wechat_id = props.wechatId;
        getFriendLists();
    }
};

const open = () => {
    popupRef.value?.open();
    getWeChatLists();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});
</script>

<style lang="scss" scoped></style>

<style lang="scss">
.el-dialog {
    &[custom-class="add-friend"] {
        padding: 0;
        .el-dialog__header,
        .el-dialog__footer {
            display: none;
        }
    }
}
</style>
