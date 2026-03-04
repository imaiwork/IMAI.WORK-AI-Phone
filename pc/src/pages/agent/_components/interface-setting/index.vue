<template>
    <div class="h-full flex flex-col bg-[#FFFFFF]">
        <div class="grow min-h-[0]">
            <ElScrollbar>
                <div class="px-[30px] py-[24px]">
                    <ElForm :model="formData" label-position="top">
                        <section class="mb-[32px]">
                            <div class="flex items-center gap-[10px] mb-[16px]">
                                <div class="w-[4px] h-[16px] bg-[#0065fb] rounded-[full]"></div>
                                <span class="text-[15px] font-[900] text-[#0F172A]">初始引导</span>
                            </div>

                            <div class="space-y-[24px]">
                                <ElFormItem>
                                    <template #label>
                                        <div class="flex flex-col gap-[2px]">
                                            <span class="text-[14px] font-[900] text-[#0F172A]">对话欢迎语</span>
                                            <span class="text-xs font-normal text-[#94A3B8]">
                                                用户进入对话窗口时显示的开场白。添加双井号（如
                                                #示例问题#）可快速生成引导词。
                                            </span>
                                        </div>
                                    </template>
                                    <ElInput
                                        v-model="formData.welcome_introducer"
                                        type="textarea"
                                        class="custom-textarea mt-4"
                                        placeholder="你好！我是你的 AI 助理，你可以试着问我：&#10;#帮我写一则关于夏天的文案#"
                                        resize="none"
                                        :maxlength="500"
                                        :rows="5" />
                                </ElFormItem>

                                <ElFormItem label="版权底部标识 (Copyright)">
                                    <ElInput
                                        v-model="formData.copyright"
                                        placeholder="例如：由 XXX 提供技术支持"
                                        :maxlength="100"
                                        class="custom-input" />
                                </ElFormItem>
                            </div>
                        </section>

                        <div class="h-[1px] bg-[#F1F5F9] my-[32px] border-[transparent] w-full"></div>

                        <section class="flex flex-col grow">
                            <div class="flex items-center justify-between mb-[20px]">
                                <div class="flex items-center gap-[10px]">
                                    <div class="w-[4px] h-[16px] bg-primary rounded-[full]"></div>
                                    <div class="flex flex-col">
                                        <span class="text-[15px] font-[900] text-[#0F172A]">快捷菜单</span>
                                        <span class="text-xs text-[#94A3B8]">点击即复，此类消息不消耗 Token 余额</span>
                                    </div>
                                </div>
                                <ElButton type="primary" class="add-menu-btn" @click="handleMenuEdit()">
                                    <Icon name="el-icon-Plus" />
                                    <span class="ml-1">添加菜单项</span>
                                </ElButton>
                            </div>

                            <div class="table-wrapper">
                                <ElTable :data="formData.menus" stripe :cell-style="{ borderBottom: 'none' }">
                                    <ElTableColumn label="触发关键词" prop="keyword" min-width="120">
                                        <template #default="{ row }">
                                            <span class="font-[900] text-[#0F172A]">{{ row.keyword }}</span>
                                        </template>
                                    </ElTableColumn>
                                    <ElTableColumn
                                        label="自动回复内容"
                                        prop="content"
                                        min-width="200"
                                        show-overflow-tooltip />
                                    <ElTableColumn label="操作" width="120" align="right">
                                        <template #default="{ row, $index }">
                                            <div class="flex justify-end gap-[12px]">
                                                <ElButton link class="edit-btn" @click="handleMenuEdit(row, $index)"
                                                    >编辑</ElButton
                                                >
                                                <ElButton link class="del-btn" @click="handleMenuDelete($index)"
                                                    >删除</ElButton
                                                >
                                            </div>
                                        </template>
                                    </ElTableColumn>
                                    <template #empty>
                                        <ElEmpty :image-size="80" description="暂无快捷菜单" />
                                    </template>
                                </ElTable>
                            </div>
                        </section>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <menu-edit v-if="showMenuEdit" ref="menuEditRef" @close="showMenuEdit = false" @success="getMenus" />
</template>
<script setup lang="ts">
import MenuEdit from "./menu-edit.vue";
import { Agent } from "../../_enums";

// 定义组件props
const props = withDefaults(
    defineProps<{
        agentId: string | number;
        modelValue: Agent;
    }>(),
    {
        modelValue: () => ({} as Agent),
        agentId: "",
    }
);

// 使用 defineModel 实现与父组件的双向绑定
const formData = defineModel<Agent>("modelValue");

// 菜单编辑弹窗的ref和显示状态
const menuEditRef = shallowRef<InstanceType<typeof MenuEdit>>();
const showMenuEdit = ref(false);

// 当前正在编辑的菜单索引
const currentMenuIndex = ref<number>(-1);

/**
 * @description 处理菜单项的添加或编辑
 * @param row - 要编辑的菜单项数据，如果为add模式则无
 * @param index - 要编辑的菜单项索引
 */
const handleMenuEdit = async (row?: any, index?: number) => {
    showMenuEdit.value = true;
    await nextTick();
    const mode = row ? "edit" : "add";
    menuEditRef.value?.open(mode);
    if (row) {
        menuEditRef.value?.setFormData(row);
        currentMenuIndex.value = index as number;
    }
};

/**
 * @description 删除菜单项
 * @param index - 要删除的菜单项索引
 */
const handleMenuDelete = (index: number) => {
    formData.value.menus.splice(index, 1);
};

/**
 * @description 从编辑弹窗获取并更新菜单数据
 * @param data - 包含类型(add/edit)和菜单数据的对象
 */
const getMenus = (data: { type: "add" | "edit"; data: any }) => {
    const { type, data: menu } = data;
    if (type === "add") {
        // 如果是新增，则推入新菜单
        formData.value.menus.push(menu);
    } else {
        // 如果是编辑，则替换当前索引的菜单
        formData.value.menus[currentMenuIndex.value] = menu;
    }
};

// 暴露validate方法以符合父组件的统一接口
defineExpose({
    validate: () => {
        // 当前表单没有需要验证的字段，直接返回成功
        return Promise.resolve();
    },
});
</script>
<style scoped lang="scss">
.custom-ui-form {
    .custom-input-h {
        @apply h-[46px];
        :deep(.el-input__wrapper) {
            @apply h-full;
        }
    }
}

.add-menu-btn {
    @apply rounded-[12px] h-[38px] px-[16px] font-[900] shadow-[0_8px_15px_-3px_rgba(0,101,251,0.15)];
}

.table-wrapper {
    @apply border border-br rounded-2xl overflow-hidden;
}

:deep(.el-table) {
    thead th.el-table__cell.is-leaf {
        border-top: none;
    }
    .el-table--border .el-table__inner-wrapper:after,
    .el-table--border:after,
    .el-table--border:before,
    .el-table__inner-wrapper:before {
        background-color: transparent;
    }
}

.edit-btn {
    @apply font-[900] text-[13px];
    --el-button-text-color: #0065fb;
}

.del-btn {
    @apply font-[900] text-[13px];
    --el-button-text-color: #ef4444;
}
</style>
