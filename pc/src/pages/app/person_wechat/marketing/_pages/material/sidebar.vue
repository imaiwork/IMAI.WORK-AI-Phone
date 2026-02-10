<template>
    <div class="w-full h-full flex flex-col bg-white rounded-xl border border-br overflow-hidden">
        <div class="px-5 py-4 border-b border-br-extra-light flex items-center justify-between bg-gray-50/30">
            <span class="text-[14px] font-[900] text-tx-primary tracking-tight">内容分组</span>
            <span class="text-primary opacity-50 leading-[0]">
                <Icon name="el-icon-FolderOpened" />
            </span>
        </div>

        <div class="grow min-h-0 py-3">
            <ElScrollbar>
                <div class="px-2 space-y-1">
                    <div
                        v-for="item in cateLists"
                        :key="item.id"
                        class="group-item"
                        :class="{ 'is-active': currentGroupId === item.id }"
                        @click="selectGroup(item)">
                        <div class="active-bar"></div>

                        <div class="icon-box">
                            <Icon
                                :name="currentGroupId === item.id ? 'el-icon-FolderOpened' : 'el-icon-Folder'"
                                :size="16" />
                        </div>

                        <div class="flex-1 flex items-center justify-between min-w-0 ml-3">
                            <span class="group-name">{{ item.group_name }}</span>
                            <span class="count-badge">{{ item.file_count || 0 }}</span>
                        </div>

                        <div class="action-trigger" v-if="item.id > 0" @click.stop>
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                                placement="right-start"
                                :offset="10">
                                <template #reference>
                                    <div class="more-btn">
                                        <Icon name="el-icon-MoreFilled" />
                                    </div>
                                </template>
                                <div class="p-1 flex flex-col gap-0.5">
                                    <div class="table-action-item" @click="openEditModal(item)">
                                        <Icon name="el-icon-EditPen" />
                                        <span>重命名</span>
                                    </div>
                                    <div
                                        class="table-action-item !text-red-500 hover:!bg-red-50"
                                        @click="handleDeleteGroup(item.id)">
                                        <Icon name="el-icon-Delete" />
                                        <span>删除分组</span>
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>

        <div class="p-4 border-t border-br-extra-light bg-[#f9f9f9]/30">
            <ElButton
                class="!w-full !rounded-xl !h-10 !border-dashed transition-all !font-medium text-tx-primary hover:!text-primary"
                @click="openAddModal">
                <Icon name="el-icon-Plus" />
                <span class="ml-1">添加分组</span>
            </ElButton>
        </div>

        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="400px"
            :confirm-loading="isSubmitting"
            @confirm="submitWithLock">
            <div class="p-2">
                <ElForm :model="formState" label-position="top">
                    <ElFormItem label="分组名称">
                        <ElInput
                            v-model="formState.name"
                            placeholder="例如：话术库、产品图"
                            maxlength="20"
                            show-word-limit
                            class="custom-input" />
                    </ElFormItem>
                </ElForm>
            </div>
        </popup>
    </div>
</template>
<script setup lang="ts">
import { ref, reactive, computed } from "vue";
import Popup from "@/components/popup/index.vue";
import { useCate, useFile } from "../../_hooks/useMaterial";

// --- 组合式函数 ---
const {
    currentGroupId,
    cateLists,
    handleAddGroup,
    handleGroupSelect,
    handleDeleteGroup,
    handleEditGroup,
    getCateLists,
} = useCate();
const { queryParams, getLists } = useFile();

// --- 弹窗与表单状态 ---
const popupRef = ref<InstanceType<typeof Popup>>();
const formState = reactive({
    id: undefined as number | undefined,
    name: "",
});

const isEditMode = computed(() => formState.id !== undefined);
const popupTitle = computed(() => (isEditMode.value ? "修改分组" : "添加分组"));

// --- 分组操作 ---

/**
 * 选择分组并获取该分组下的素材列表
 * @param item 分组对象
 */
const selectGroup = (item: { id: number }) => {
    queryParams.group_id = item.id;
    handleGroupSelect(item);
    getLists();
};

/**
 * 打开新增分组弹窗
 */
const openAddModal = () => {
    formState.id = undefined;
    formState.name = "";
    popupRef.value?.open();
};

/**
 * 打开编辑分组弹窗
 * @param item 要编辑的分组对象
 */
const openEditModal = (item: { id: number; group_name: string }) => {
    formState.id = item.id;
    formState.name = item.group_name;
    popupRef.value?.open();
};

/**
 * 提交表单（新增或修改）
 */
const submitGroupForm = async () => {
    if (!formState.name.trim()) {
        feedback.msgError("请输入分组名称");
        return;
    }

    try {
        if (isEditMode.value) {
            await handleEditGroup(formState.name, formState.id!);
        } else {
            await handleAddGroup(formState.name);
        }
        popupRef.value?.close();
        // 刷新分组列表
        await getCateLists();
    } catch (error) {
        console.error("提交分组失败:", error);
    }
};

// 使用 useLockFn 防止重复提交
const { isLock: isSubmitting, lockFn: submitWithLock } = useLockFn(submitGroupForm);

// --- 初始化 ---
getCateLists();
</script>

<style scoped lang="scss">
.group-item {
    @apply relative h-[42px] px-3 mx-1 rounded-[10px] flex items-center cursor-pointer transition-all duration-200 text-tx-secondary;

    &:hover:not(.is-active) {
        @apply bg-gray-100 text-tx-primary;
    }

    &.is-active {
        @apply bg-blue-50 text-primary font-[900];
        .icon-box {
            @apply text-primary scale-110;
        }
        .count-badge {
            @apply bg-primary text-white;
        }
        .active-bar {
            @apply scale-y-100 opacity-100;
        }
    }
}

.active-bar {
    @apply absolute left-0 top-[25%] h-[50%] w-[3px] bg-primary rounded-r-full opacity-0 scale-y-0 transition-all duration-300;
}

.icon-box {
    @apply text-tx-placeholder transition-transform leading-[0];
}

.group-name {
    @apply text-[13px] truncate flex-1 pr-2;
}

.count-badge {
    @apply text-[10px] px-1.5 py-0.5 rounded-md bg-gray-100 text-tx-placeholder font-medium transition-colors;
}

.action-trigger {
    @apply ml-1;
}

.more-btn {
    @apply w-6 h-6 flex items-center justify-center rounded-md  text-tx-placeholder hover:bg-gray-200 hover:text-tx-primary transition-all;
}

:deep(.custom-input .el-input__wrapper) {
    @apply rounded-lg bg-white shadow-[none] border border-br-extra-light;
    &.is-focus {
        @apply bg-white border-primary;
    }
}
</style>
