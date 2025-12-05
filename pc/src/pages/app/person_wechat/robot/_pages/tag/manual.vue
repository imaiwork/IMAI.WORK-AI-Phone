<template>
    <div class="h-full flex">
        <!-- 左侧面板: 微信列表 & 标签管理 -->
        <div class="w-[320px] flex-shrink-0 rounded-xl bg-white flex overflow-hidden">
            <div class="w-[94px] flex-shrink-0">
                <SidebarPanel
                    :current-wechat="currentWechat"
                    :wechat-list="wechatLists"
                    :show-add-we-chat="false"
                    @update:current-wechat="handleSelectWeChat" />
            </div>
            <div class="flex-1 flex flex-col overflow-hidden" v-loading="tagPager.loading">
                <div class="flex-shrink-0">
                    <ElTabs v-model="activeTab">
                        <ElTabPane label="标签管理" name="tag"></ElTabPane>
                    </ElTabs>
                    <div class="flex justify-center">
                        <ElButton type="primary" link @click="clearSelectTags"> 取消已勾选的标签 </ElButton>
                    </div>
                </div>
                <div class="grow min-h-0 w-full">
                    <DynamicScroller
                        class="dynamic-scroller w-full h-full"
                        :items="tagPager.lists"
                        :min-item-size="37"
                        @scroll="handleTagScroll">
                        <template #default="{ item, index, active }">
                            <DynamicScrollerItem
                                class="px-6 py-4 relative"
                                :item="item"
                                :active="active"
                                :size-dependencies="[item.tag_name]">
                                <div
                                    class="tag-item"
                                    :class="[
                                        selectedTags.includes(item.id)
                                            ? 'border-[transparent] bg-primary text-white is-selected'
                                            : 'border-primary border-dashed text-primary',
                                    ]"
                                    @click="handleSelectTag(item.id)">
                                    <div v-if="item.id == 0">
                                        {{ item.tag_name }}
                                    </div>
                                    <template v-else>
                                        <span
                                            v-if="!isEditMode"
                                            class="text-ellipsis overflow-hidden whitespace-nowrap">
                                            {{ item.tag_name }}
                                        </span>
                                        <ElInput
                                            v-else
                                            v-model="item.tag_name"
                                            class="w-full tag-input"
                                            autofocus
                                            @blur="handleUpdateTag(item)" />
                                    </template>
                                    <span>（{{ item.friend_count }}）</span>
                                </div>
                                <div
                                    v-if="isEditMode && item.id !== 0"
                                    class="absolute top-2 right-2 cursor-pointer"
                                    @click="handleDeleteTag(item.id)">
                                    <Icon
                                        name="local-icon-close_circle_fill"
                                        color="var(--el-color-error)"
                                        :size="16" />
                                </div>
                            </DynamicScrollerItem>
                        </template>
                    </DynamicScroller>
                </div>
                <div class="flex-shrink-0 p-3 flex flex-col gap-2 items-center">
                    <ElButton type="primary" @click="openTagEditPopup">
                        <Icon name="local-icon-add_box_fill" color="#ffffff" />
                        <span class="ml-2">添加标签</span>
                    </ElButton>
                    <ElButton link color="#9E9E9E" @click="isEditMode = !isEditMode">
                        <span class="text-[#9E9E9E] text-xs mr-2">{{ isEditMode ? "取消编辑" : "编辑标签" }}</span>
                        <Icon name="el-icon-Edit" color="#9E9E9E" />
                    </ElButton>
                </div>
            </div>
        </div>
        <!-- 中间面板: 好友列表 -->
        <div class="grow min-h-0 bg-white ml-4 rounded-xl overflow-hidden" v-loading="friendPager.loading">
            <div class="flex flex-col h-full">
                <div class="flex-shrink-0">
                    <div class="h-[70px] flex items-center justify-between gap-x-2 px-4">
                        <ElInput
                            v-model="friendParams.friend_nickname"
                            placeholder="搜索"
                            class="!w-[200px] h-[32px]"
                            clearable
                            @clear="resetFriendPageAndFetch"
                            @keyup.enter="resetFriendPageAndFetch">
                            <template #append>
                                <ElButton @click="resetFriendPageAndFetch">
                                    <Icon name="el-icon-Search" />
                                </ElButton>
                            </template>
                        </ElInput>
                        <div>
                            <ElButton type="danger" @click="openTagPopup('remove')"> 批量移除标签 </ElButton>
                            <ElButton type="primary" @click="openTagPopup('add')"> 批量新增标签 </ElButton>
                        </div>
                    </div>
                </div>
                <div class="grow min-h-0">
                    <ElTable
                        ref="friendTableRef"
                        v-loading="friendPager.loading"
                        :data="friendPager.lists"
                        stripe
                        height="100%"
                        row-key="friend_id"
                        :row-style="{ height: '60px' }"
                        :header-row-style="{ height: '63px' }"
                        @selection-change="handleFriendSelectionChange">
                        <ElTableColumn type="selection" reserve-selection width="55" fixed="left" />
                        <ElTableColumn prop="friend_nickname" label="微信名称" />
                        <ElTableColumn prop="friend_avatar" label="头像">
                            <template #default="{ row }">
                                <img :src="row.friend_avatar" class="w-8 h-8 rounded mx-auto" />
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="操作" width="160" v-if="!isFriendNoTag">
                            <template #default="{ row }">
                                <ElButton type="primary" link @click="openTagPopup('remove', row)">
                                    从已选标签中移除
                                </ElButton>
                            </template>
                        </ElTableColumn>
                    </ElTable>
                </div>
                <div class="p-4 flex justify-end">
                    <pagination v-model="friendPager" @change="getTagFriendList" />
                </div>
            </div>
        </div>
    </div>
    <!-- 标签编辑弹窗 -->
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
import TagEditPopup from "./edit.vue";
import SidebarPanel from "../../../chat/_components/sidebar-panel.vue";

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
:deep(.el-tabs__nav-scroll) {
    @apply flex justify-center;
    .el-tabs__item {
        @apply h-[48px];
    }
}
.tag-item {
    @apply h-[37px] flex items-center justify-center border rounded-md px-2 cursor-pointer;
    :deep(.tag-input) {
        .el-input__inner {
            --el-input-inner-height: 32px;
            @apply text-primary;
        }
        .el-input__wrapper {
            box-shadow: none;
            background-color: transparent;
        }
    }
    &.is-selected {
        color: #ffffff;
        :deep(.tag-input) {
            .el-input__inner {
                @apply text-white;
            }
            .el-input__wrapper {
                @apply bg-primary;
            }
        }
    }
}

:deep(.el-input-group__append) {
    background-color: transparent;
    border: none;
}
</style>
