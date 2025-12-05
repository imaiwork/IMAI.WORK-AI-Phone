<template>
    <div class="w-full h-full flex flex-col bg-white rounded-xl">
        <!-- 分组列表 -->
        <div class="grow min-h-0 pt-5">
            <ElScrollbar>
                <div>
                    <div
                        v-for="item in cateLists"
                        :key="item.id"
                        class="h-12 px-5 relative cursor-pointer flex items-center"
                        :class="{ 'bg-[#E5F0FF]': currentGroupId === item.id }"
                        @click="selectGroup(item)">
                        <!-- 选中状态指示器 -->
                        <div
                            v-if="currentGroupId === item.id"
                            class="absolute left-0 top-0 h-full w-[2px] bg-primary"></div>

                        <!-- 图标 -->
                        <div class="w-5">
                            <Icon name="local-icon-file_fill" color="var(--color-primary)" :size="18" />
                        </div>

                        <!-- 名称和数量 -->
                        <div class="flex-1 flex items-center gap-x-2 w-0 ml-4">
                            <span class="overflow-hidden text-ellipsis whitespace-nowrap">{{ item.group_name }}</span>
                            <span class="text-[#A5ADC7]">({{ item.file_count || 0 }})</span>
                        </div>

                        <!-- 操作菜单 -->
                        <div class="flex-shrink-0" @click.stop>
                            <ElPopover
                                v-if="item.id > 0"
                                :show-arrow="false"
                                popper-class="!w-[130px] !min-w-[130px] !p-[6px] !rounded-xl"
                                placement="bottom-end">
                                <template #reference>
                                    <div class="p-2">
                                        <Icon name="el-icon-MoreFilled" color="#A5ADC7" />
                                    </div>
                                </template>
                                <div class="flex flex-col gap-1">
                                    <div
                                        class="px-3 py-2 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                        @click="openEditModal(item)">
                                        <Icon name="el-icon-Setting" />
                                        <span>修改分组</span>
                                    </div>
                                    <div
                                        class="px-3 py-2 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2 text-error"
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

        <!-- 底部操作区 -->
        <ElDivider class="!my-0" />
        <div class="flex-shrink-0 h-[53px] flex justify-center items-center px-4">
            <ElButton @click="openAddModal">添加分组</ElButton>
        </div>

        <!-- 添加/修改分组弹窗 -->
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            :confirm-loading="isSubmitting"
            @confirm="submitWithLock">
            <div>
                <ElForm :model="formState" @submit.prevent>
                    <ElFormItem label="分组名称">
                        <ElInput v-model="formState.name" placeholder="请输入分组名称" maxlength="20" show-word-limit />
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

<style scoped>
/* 样式保持不变 */
</style>
