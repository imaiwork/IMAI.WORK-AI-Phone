<template>
    <div class="h-full flex gap-x-3 overflow-hidden">
        <div class="w-[340px] flex-shrink-0 bg-white rounded-[20px] border border-br flex overflow-hidden">
            <div class="w-[84px] flex-shrink-0 border-r border-slate-50 bg-[#FBFDFF]">
                <SidebarPanel
                    :current-wechat="currentWechat"
                    :wechat-list="wechatLists"
                    :show-add-we-chat="false"
                    @update:current-wechat="handleSelectWeChat" />
            </div>

            <div class="flex-1 flex flex-col overflow-hidden" v-loading="tagPager.loading">
                <div class="px-5 py-4 border-b border-slate-50">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[15px] font-black text-tx-primary">标签筛选</span>
                        <ElButton type="primary" link @click="clearSelectTags" class="!text-xs">重置</ElButton>
                    </div>
                    <div class="bg-[#0065fb]/5 rounded-xl p-3 flex items-center justify-between">
                        <div class="text-[11px] text-primary font-medium">已选标签</div>
                        <div class="flex items-center gap-1">
                            <span class="text-[14px] font-black text-primary">{{ selectedTags.length }}</span>
                            <span class="text-[10px] text-[#0065fb]/60">个</span>
                        </div>
                    </div>
                </div>

                <div class="grow min-h-0 w-full py-2">
                    <DynamicScroller
                        class="h-full px-4"
                        :items="tagPager.lists"
                        :min-item-size="52"
                        @scroll="handleTagScroll">
                        <template #default="{ item, index, active }">
                            <DynamicScrollerItem :item="item" :active="active" class="pb-2">
                                <div
                                    class="modern-tag-card"
                                    :class="{ 'is-active': selectedTags.includes(item.id) }"
                                    @click="handleSelectTag(item.id)">
                                    <div class="flex-1 flex items-center min-w-0">
                                        <span class="mr-2 leading-[0]">
                                            <Icon name="el-icon-PriceTag" :size="14" />
                                        </span>
                                        <template v-if="!isEditMode || item.id === 0">
                                            <span class="truncate text-[13px] font-medium">{{ item.tag_name }}</span>
                                        </template>
                                        <ElInput
                                            v-else
                                            v-model="item.tag_name"
                                            class="compact-input"
                                            size="small"
                                            autofocus
                                            @blur="handleUpdateTag(item)" />
                                    </div>

                                    <div class="tag-count">{{ item.friend_count }}</div>

                                    <div
                                        v-if="isEditMode && item.id !== 0"
                                        class="ml-2 text-error hover:scale-110 transition-transform"
                                        @click.stop="handleDeleteTag(item.id)">
                                        <Icon name="el-icon-CircleCloseFilled" :size="16" />
                                    </div>
                                </div>
                            </DynamicScrollerItem>
                        </template>
                    </DynamicScroller>
                </div>

                <div class="p-4 bg-[#f8fafc]/50 border-t border-slate-100 flex flex-col gap-2">
                    <ElButton type="primary" class="!rounded-xl !h-10 !font-medium" @click="openTagEditPopup">
                        <Icon name="el-icon-Plus" />
                        <span class="ml-1"> 新增标签 </span>
                    </ElButton>
                    <ElButton
                        link
                        :type="isEditMode ? 'warning' : 'info'"
                        class="!text-xs !font-medium"
                        @click="isEditMode = !isEditMode">
                        <Icon :name="isEditMode ? 'el-icon-CircleClose' : 'el-icon-Edit'" />
                        <span class="ml-1">
                            {{ isEditMode ? "退出管理模式" : "管理已有标签" }}
                        </span>
                    </ElButton>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 bg-white rounded-[20px] border border-br overflow-hidden flex flex-col">
            <div class="h-[72px] px-6 flex items-center justify-between border-b border-slate-50 bg-[#FBFDFF]">
                <div class="flex items-center gap-x-4">
                    <ElInput
                        v-model="friendParams.friend_nickname"
                        placeholder="搜索好友昵称..."
                        class="custom-input !w-[240px]"
                        clearable
                        @clear="resetFriendPageAndFetch"
                        @keyup.enter="resetFriendPageAndFetch">
                        <template #prefix>
                            <Icon name="el-icon-Search" />
                        </template>
                    </ElInput>
                    <div class="text-xs text-tx-placeholder font-medium">
                        已选 <span class="text-primary font-black">{{ selectedFriends.length }}</span> 位好友
                    </div>
                </div>

                <div class="flex items-center gap-x-2">
                    <ElButton type="danger" class="!rounded-xl !h-9" @click="openTagPopup('remove')">
                        <Icon name="el-icon-Minus" />
                        <span class="ml-1"> 批量移除标签 </span>
                    </ElButton>
                    <ElButton type="primary" class="!rounded-xl !h-9" @click="openTagPopup('add')">
                        <Icon name="el-icon-Plus" />
                        <span class="ml-1"> 批量添加标签 </span>
                    </ElButton>
                </div>
            </div>

            <div class="grow min-h-0">
                <ElTable
                    ref="friendTableRef"
                    :data="friendPager.lists"
                    v-loading="friendPager.loading"
                    height="100%"
                    @selection-change="handleFriendSelectionChange">
                    <ElTableColumn type="selection" width="60" align="center" />
                    <ElTableColumn label="好友信息" min-width="250">
                        <template #default="{ row }">
                            <div class="flex justify-center items-center gap-3">
                                <ElAvatar
                                    :src="row.friend_avatar"
                                    :size="36"
                                    shape="square"
                                    class="rounded-lg shadow-light" />
                                <div class="flex flex-col">
                                    <span class="text-[14px] font-medium text-tx-primary">{{
                                        row.friend_nickname
                                    }}</span>
                                    <span class="text-[11px] text-tx-placeholder font-mono"
                                        >ID: {{ row.friend_id }}</span
                                    >
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="操作" width="180" align="right" fixed="right">
                        <template #default="{ row }">
                            <ElButton
                                v-if="!isFriendNoTag"
                                type="danger"
                                link
                                class="!text-xs"
                                @click="openTagPopup('remove', row)">
                                移出当前标签
                            </ElButton>
                        </template>
                    </ElTableColumn>

                    <template #empty>
                        <ElEmpty :image-size="120" description="暂无符合条件的好友" />
                    </template>
                </ElTable>
            </div>

            <div class="flex justify-end items-center px-6 py-4">
                <pagination v-model="friendPager" @change="getTagFriendList" />
            </div>
        </div>
    </div>
    <tag-edit-popup
        v-if="showEditTagPopup"
        ref="editTagPopupRef"
        @close="showEditTagPopup = false"
        @success="resetTagPage" />
    <popup
        :title="tagType == 'add' ? '分配标签' : '移除标签'"
        v-if="showTagPopup"
        ref="tagPopupRef"
        async
        :confirm-loading="isSaving"
        @confirm="saveFriendTags"
        @close="closeTagPopup">
        <div>
            <ElSelect
                v-model="tagsToAssign"
                class="custom-select"
                :show-arrow="false"
                :placeholder="tagType == 'add' ? '请选择要分配标签' : '请选择要移除标签'"
                multiple
                filterable>
                <ElOption v-for="item in getSavaTagLists" :key="item.id" :label="item.tag_name" :value="item.id" />
            </ElSelect>
        </div>
    </popup>
</template>
<script setup lang="ts">
import { ElTable } from "element-plus";
import {
    getWeChatLists,
    tagListsV2,
    deleteTagV2,
    tagUpdateV2,
    tagFriendLists,
    tagFriendAdd,
    tagFriendDelete,
} from "@/api/person_wechat";
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import SidebarPanel from "@/pages/app/person_wechat/chat/_components/sidebar-panel.vue";
import TagEditPopup from "../auto/edit.vue";

// --- 1. 状态定义 ---

const nuxtApp = useNuxtApp();
// 标签页状态
const activeTab = ref<"tag">("tag");

// 微信账号列表与当前选中的微信
const wechatLists = ref<any[]>([]);
const currentWechat = ref<any>({});

// 标签列表相关
const tagParams = reactive({ page_no: 1, page_size: 15, wechat_id: "" });
const {
    pager: tagPager,
    getLists: getTagList,
    resetPage: resetTagPage,
} = usePaging({
    fetchFun: tagListsV2,
    params: tagParams,
    isScroll: true,
});
const selectedTags = ref<number[]>([]);
const firstTagId = ref<number | null>(null); // 用于记录第一个标签的ID
const isEditMode = ref(false); // 是否为标签编辑模式

// 好友列表相关
const friendParams = reactive({ tag_ids: "", friend_nickname: "", wechat_id: "" });
const {
    pager: friendPager,
    getLists: getTagFriendList,
    resetPage: resetFriendPage,
} = usePaging({
    fetchFun: tagFriendLists,
    params: friendParams,
});
const friendTableRef = ref<InstanceType<typeof ElTable>>();
const selectedFriends = ref<any[]>([]);

// 操作相关
const tagsToAssign = ref<number[]>([]); // 待分配给好友的标签
const showEditTagPopup = ref(false);
const editTagPopupRef = ref<InstanceType<typeof TagEditPopup>>();
const tagPopupRef = ref();
const getSavaTagLists = computed(() => {
    return tagPager.lists.filter((item) => item.id !== 0);
});

const isFriendNoTag = computed(() => {
    return selectedTags.value.length == 1 && selectedTags.value[0] == firstTagId.value;
});

const initialize = async () => {
    await getWeChatListsFn();
    tagParams.wechat_id = currentWechat.value?.wechat_id;
    await getInitialTags();
    friendParams.wechat_id = currentWechat.value?.wechat_id;
    getTagFriendList();
};

const getWeChatListsFn = async () => {
    try {
        const { lists } = await getWeChatLists({ page_size: 999 });
        wechatLists.value = lists;
        currentWechat.value = lists.find((item) => item.wechat_status == 1);
    } catch (error) {
        feedback.msgError("获取微信列表失败");
    }
};

const getInitialTags = async () => {
    await resetTagPage();
    if (tagPager.lists.length > 0) {
        const initialTagId = tagPager.lists[0].id;
        firstTagId.value = initialTagId;
        selectedTags.value = [initialTagId];
    } else {
        firstTagId.value = null;
        selectedTags.value = [];
    }
};

const setSelectFriendTable = () => {
    selectedFriends.value = [];
    friendTableRef.value?.clearSelection();
    nextTick(() => {
        friendPager.lists.forEach((item) => {
            friendTableRef.value?.toggleRowSelection(item);
        });
    });
};

// --- 3. 微信与标签面板逻辑 ---

const handleSelectWeChat = async (wechat: any) => {
    currentWechat.value = wechat;
    tagParams.wechat_id = wechat.wechat_id;
    friendParams.tag_ids = "";
    friendParams.wechat_id = wechat.wechat_id;
    friendParams.friend_nickname = "";
    selectedTags.value = [];
    selectedFriends.value = [];
    isEditMode.value = false;
    friendTableRef.value?.clearSelection();
    await getInitialTags();
    resetFriendPage();
};

const handleSelectTag = async (id: number) => {
    if (isEditMode.value) return; // 编辑模式下不可选择
    friendTableRef.value?.clearSelection();
    if (id == 0) {
        selectedTags.value = [0];
        selectedFriends.value = [];
        friendParams.tag_ids = "";
        resetFriendPage();
        return;
    }
    selectedTags.value = selectedTags.value.filter((item) => item !== 0);
    const index = selectedTags.value.indexOf(id);
    if (index > -1) {
        // 如果只剩最后一个选中的标签，则不允许取消
        if (selectedTags.value.length > 1) {
            selectedTags.value.splice(index, 1);
        }
    } else {
        selectedTags.value.push(id);
    }
    friendParams.tag_ids = selectedTags.value.join(",");
    await resetFriendPage();
};

const clearSelectTags = () => {
    selectedTags.value = [firstTagId.value];
    friendParams.friend_nickname = "";
};

const handleTagScroll = (e: any) => {
    if (tagPager.isLoad || tagPager.loading) return;
    const { scrollHeight, clientHeight, scrollTop } = e.target;
    if (scrollHeight - clientHeight - scrollTop < 1) {
        tagParams.page_no++;
        getTagList();
    }
};

// --- 4. 标签管理 (增删改) ---

const openTagEditPopup = async () => {
    showEditTagPopup.value = true;
    await nextTick();
    editTagPopupRef.value?.open();
    editTagPopupRef.value?.setFormData({
        wechat_id: currentWechat.value.wechat_id,
    });
};

const handleDeleteTag = async (id: number) => {
    await nuxtApp.$confirm({
        message: "确定要删除该标签吗？",
        onConfirm: async () => {
            try {
                await deleteTagV2({ id });
                tagPager.lists = tagPager.lists.filter((item) => item.id !== id);
                await resetFriendPage();
                setSelectFriendTable();

                if (tagPager.lists.length == 1) {
                    selectedTags.value = [firstTagId.value];
                }

                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};

const handleUpdateTag = async (item: any) => {
    if (!item.tag_name.trim()) {
        feedback.msgWarning("标签名不能为空");
        resetTagPage(); // 恢复原名
        return;
    }
    try {
        await tagUpdateV2({ id: item.id, tag_name: item.tag_name });
        feedback.msgSuccess("修改成功");
    } catch (error) {
        feedback.msgError(error);
    }
};

const resetFriendPageAndFetch = async () => {
    await resetFriendPage();
    const selectionRows = friendTableRef.value?.getSelectionRows();
    friendTableRef.value?.clearSelection();
    selectionRows.forEach((item) => {
        friendTableRef.value?.toggleRowSelection(item);
    });
};

const handleFriendSelectionChange = (val: any[]) => {
    selectedFriends.value = val;
};

// --- 5. 保存逻辑 ---

const showTagPopup = ref(false);
const tagType = ref<"add" | "remove">("add");

const openTagPopup = async (type: "add" | "remove", row?: any) => {
    tagType.value = type;
    showTagPopup.value = true;
    await nextTick();
    tagPopupRef.value?.open();
    if (row) {
        selectedFriends.value = [row];
    }
};

const closeTagPopup = () => {
    showTagPopup.value = false;
    tagsToAssign.value = [];
};

const { isLock: isSaving, lockFn: saveFriendTags } = useLockFn(async () => {
    if (selectedFriends.value.length === 0) {
        return feedback.msgWarning("请选择要分配标签的好友");
    }
    if (tagsToAssign.value.length === 0) {
        return feedback.msgWarning("请选择要分配的标签");
    }

    try {
        tagType.value == "add"
            ? await tagFriendAdd({
                  tag_ids: tagsToAssign.value,
                  friend_ids: selectedFriends.value.map((item) => item.friend_id),
                  wechat_id: currentWechat.value.wechat_id,
              })
            : await tagFriendDelete({
                  tag_id: tagsToAssign.value,
                  friend_id: selectedFriends.value.map((item) => item.friend_id),
                  wechat_id: currentWechat.value.wechat_id,
              });
        selectedFriends.value = [];
        feedback.msgSuccess("保存成功");
        friendTableRef.value?.clearSelection();
        closeTagPopup();
        resetTagPage();
        resetFriendPage();
    } catch (error) {
        feedback.msgError(error);
    }
});

onMounted(async () => {
    await initialize();
});
</script>

<style scoped lang="scss">
.modern-tag-card {
    @apply flex items-center px-4 py-3 rounded-xl border border-[transparent] bg-[#f8fafc]/50 cursor-pointer transition-all;

    &:hover {
        @apply bg-[#f1f5f9]/80;
    }

    &.is-active {
        @apply bg-[#0065fb]/[0.05] border-[#0065fb]/[0.20] text-primary;
        .tag-count {
            @apply bg-primary text-white;
        }
        .modern-tag-card .opacity-40 {
            @apply opacity-100;
        }
    }

    .tag-count {
        @apply ml-2 px-1.5 py-0.5 rounded-md bg-slate-200 text-slate-500 text-[10px] font-black min-w-[24px] text-center transition-all;
    }
}
</style>
