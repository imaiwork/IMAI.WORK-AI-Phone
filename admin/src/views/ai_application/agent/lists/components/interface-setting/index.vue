<template>
    <div class="h-full flex flex-col px-[30px] py-6">
        <div class="flex flex-col gap-3">
            <!-- 欢迎语设置 -->
            <div>
                <div class="font-bold">欢迎语</div>
                <div class="form-tips">
                    打开聊天窗口后会主动发送，添加双井号可添加提问示例，例如：#帮我写一则关于xxx的文案#多个问题请用回车换行。
                </div>
                <el-input
                    class="mt-3"
                    v-model="formData.welcome_introducer"
                    type="textarea"
                    placeholder="请输入文字内容 ..."
                    resize="none"
                    :maxlength="500"
                    :rows="4" />
            </div>
            <!-- 底部标识设置 -->
            <div>
                <div class="font-bold">底部标识</div>
                <el-input
                    class="mt-3 !h-11"
                    v-model="formData.copyright"
                    placeholder="请输入文字内容 ..."
                    :maxlength="100" />
            </div>
        </div>
        <el-divider class="!border-[#0000000d]" />
        <!-- 菜单设置 -->
        <div class="flex items-center justify-between gap-x-23">
            <div>
                <div class="font-bold">菜单设置</div>
                <div class="form-tips">用户点击菜单后，将回复对应内容。此类消息不消耗余额。</div>
            </div>
            <el-button type="primary" @click="handleMenuEdit()">添加菜单</el-button>
        </div>
        <!-- 菜单表格 -->
        <div class="grow min-h-0 mt-3 flex flex-col border border-[var(--el-border-color)] rounded-lg">
            <el-table
                height="100%"
                :data="formData.menus"
                stripe
                :header-row-style="{
                    height: '62px',
                }"
                :row-style="{
                    height: '50px',
                }">
                <el-table-column label="关键词" prop="keyword" min-width="60" show-overflow-tooltip />
                <el-table-column label="回复内容" prop="content" min-width="200" show-overflow-tooltip />
                <el-table-column label="操作" width="120">
                    <template #default="{ row, $index }">
                        <ElButton link type="primary" @click="handleMenuEdit(row, $index)">编辑</ElButton>
                        <ElButton link type="danger" @click="handleMenuDelete($index)">删除</ElButton>
                    </template>
                </el-table-column>
                <template #empty>
                    <el-empty description="暂无数据" />
                </template>
            </el-table>
        </div>
    </div>
    <!-- 菜单编辑弹窗 -->
    <menu-edit v-if="showMenuEdit" ref="menuEditRef" @close="showMenuEdit = false" @success="getMenus" />
</template>

<script setup lang="ts">
import MenuEdit from "./menu-edit.vue";
import type { Agent } from "../enums";

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
const formData = defineModel<Agent>("modelValue", {
    default: () => ({
        menus: [],
        welcome_introducer: "",
        copyright: "",
    }),
});

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
:deep(.el-table) {
    border-radius: 8px;
    thead th.el-table__cell.is-leaf {
        border-top: 0;
    }
    &.el-table--fit .el-table__inner-wrapper:before {
        display: none;
    }
}
</style>
