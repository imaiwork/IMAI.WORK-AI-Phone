<template>
    <div class="flex flex-col h-full bg-white rounded-[20px]">
        <div class="p-4">
            <ElForm :model="queryParams" inline class="w-full">
                <ElFormItem label="来源账号">
                    <ElSelect v-model="queryParams.account" class="!w-[200px]" placeholder="请选择来源账号">
                        <ElOption
                            v-for="(item, index) in optionsData.redbookLists"
                            :key="index"
                            :label="item.nickname"
                            :value="item.account"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="执行微信">
                    <ElSelect v-model="queryParams.wechat_no" class="!w-[200px]" placeholder="请选择执行微信">
                        <ElOption
                            v-for="(item, index) in optionsData.wechatLists"
                            :key="index"
                            :label="item.wechat_nickname"
                            :value="item.wechat_id"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="执行动作">
                    <ElSelect
                        v-model="queryParams.action"
                        class="!w-[200px]"
                        placeholder="请选择执行动作"
                        :empty-values="[null, undefined]">
                        <ElOption label="自动添加" value=""></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="执行结果">
                    <ElSelect
                        v-model="queryParams.status"
                        class="!w-[200px]"
                        placeholder="请选择执行结果"
                        :empty-values="[null, undefined]">
                        <ElOption label="全部" value=""></ElOption>
                        <ElOption label="成功" value="1"></ElOption>
                        <ElOption label="失败" value="2"></ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem>
                    <ElButton type="primary" @click="getLists()"> 搜索 </ElButton>
                    <ElButton @click="resetParams"> 重置 </ElButton>
                </ElFormItem>
            </ElForm>
        </div>
        <div class="grow min-h-0">
            <ElTable :data="pager.lists" height="100%" :row-style="{ height: '60px' }">
                <ElTableColumn prop="device_code" label="设备码" width="180"></ElTableColumn>
                <ElTableColumn prop="account" label="来源账号" width="150"></ElTableColumn>
                <ElTableColumn prop="user_account" label="小红书用户信息" min-width="180"></ElTableColumn>
                <ElTableColumn
                    prop="original_message"
                    label="私信内容"
                    min-width="180"
                    show-overflow-tooltip></ElTableColumn>
                <ElTableColumn prop="reg_wechat" label="匹配微信号" min-width="180"></ElTableColumn>
                <ElTableColumn prop="wechat_name" label="执行微信" min-width="160">
                    <template #default="{ row }">
                        {{ row.wechat_name || "-" }}
                    </template>
                </ElTableColumn>
                <ElTableColumn label="执行动作" width="120">
                    <template #default="{ row }">
                        <div>自动添加</div>
                    </template>
                </ElTableColumn>
                <ElTableColumn prop="result" label="加微结果" min-width="180"></ElTableColumn>
                <ElTableColumn label="操作" fixed="right" width="100">
                    <template #default="{ row }">
                        <ElButton v-if="row.status == 0" type="primary" link @click="handleRetry(row.id)"
                            >重试</ElButton
                        >
                        <ElButton type="danger" link @click="handleDelete(row.id)">删除</ElButton>
                    </template>
                </ElTableColumn>
                <template #empty>
                    <ElEmpty description="暂无数据" />
                </template>
            </ElTable>
        </div>
        <div class="flex justify-end p-4">
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getAccountList, getAutoAddWechatRecord, deleteAutoAddWechat, retryAutoAddWechat } from "@/api/service";
import { getWeChatLists } from "@/api/person_wechat";

const nuxtApp = useNuxtApp();
const queryParams = reactive({
    account: "",
    wechat_no: "",
    action: "",
    status: "",
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: getAutoAddWechatRecord,
    params: queryParams,
});

const { optionsData } = useDictOptions<{
    redbookLists: any[];
    wechatLists: any[];
}>({
    redbookLists: {
        api: getAccountList,
        params: {
            page_size: 999,
        },
        transformData: (data) => data.lists,
    },
    wechatLists: {
        api: getWeChatLists,
        params: {
            page_size: 999,
        },
        transformData: (data) => data.lists,
    },
});

const handleRetry = (id: string) => {
    nuxtApp.$confirm({
        message: "确定重试该记录吗？",
        onConfirm: async () => {
            try {
                await retryAutoAddWechat({ id });
                getLists();
                feedback.msgSuccess("重试成功");
            } catch (error) {
                feedback.msgError(error || "重试失败");
            }
        },
    });
};

const handleDelete = (id: string) => {
    nuxtApp.$confirm({
        message: "确定删除该记录吗？",
        onConfirm: async () => {
            try {
                await deleteAutoAddWechat({ id });
                getLists();
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

getLists();
</script>

<style scoped lang="scss">
:deep(.el-card__body) {
    @apply flex flex-col h-full !p-0;
    .el-table-fixed-column--right {
        background-color: #fff !important;
    }
}
</style>
