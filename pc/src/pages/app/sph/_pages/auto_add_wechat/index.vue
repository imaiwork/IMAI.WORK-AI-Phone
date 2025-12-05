<template>
    <div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]">
        <div class="flex-shrink-0 p-[14px]">
            <div class="flex items-center">
                <ElForm :model="queryParams" inline class="w-full -mb-4">
                    <ElFormItem label="执行设备：">
                        <ElSelect
                            v-model="queryParams.device_code"
                            class="!w-[260px] !h-10"
                            popper-class="dark-select-popper"
                            placeholder="请选择执行设备"
                            filterable
                            clearable
                            :show-arrow="false">
                            <ElOption
                                v-for="item in optionsData.deviceLists"
                                :key="item.id"
                                :label="item.device_code"
                                :value="item.device_code">
                            </ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <ElFormItem label="添加渠道：">
                        <ElSelect
                            v-model="queryParams.channel"
                            class="!w-[260px] !h-10"
                            popper-class="dark-select-popper"
                            placeholder="请选择添加渠道"
                            filterable
                            clearable
                            :show-arrow="false">
                            <ElOption label="小红书" :value="AppTypeEnum.XHS"></ElOption>
                            <ElOption label="视频号" :value="AppTypeEnum.SPH"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <ElFormItem label="执行类型：">
                        <ElSelect
                            v-model="queryParams.exec_type"
                            class="!w-[260px] !h-10"
                            popper-class="dark-select-popper"
                            placeholder="请选择执行类型"
                            filterable
                            clearable
                            :show-arrow="false">
                            <ElOption label="私信聊天" :value="ExecTypeEnum.PRIVATE_CHAT"></ElOption>
                            <ElOption label="自动爬取" :value="ExecTypeEnum.CRAWL"></ElOption>
                            <ElOption label="自动私信" :value="ExecTypeEnum.AUTO_PRIVATE_CHAT"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <ElFormItem label="添加微信：">
                        <ElSelect
                            v-model="queryParams.wechat_no"
                            class="!w-[260px] !h-10"
                            popper-class="dark-select-popper"
                            placeholder="请选择添加微信"
                            filterable
                            clearable
                            :show-arrow="false">
                            <ElOption
                                v-for="item in optionsData.wechatLists"
                                :key="item.id"
                                :label="item.wechat_nickname"
                                :value="item.wechat_id">
                            </ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <ElFormItem label="加微结果：">
                        <ElSelect
                            v-model="queryParams.status"
                            class="!w-[260px] !h-10"
                            popper-class="dark-select-popper"
                            placeholder="请选择加微结果"
                            filterable
                            clearable
                            :empty-values="[undefined, null]"
                            :show-arrow="false">
                            <ElOption label="全部" value=""></ElOption>
                            <ElOption label="失败" value="0"></ElOption>
                            <ElOption label="成功" value="1"></ElOption>
                            <ElOption label="执行中" value="2"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <ElFormItem>
                        <ElButton type="primary" class="!h-10 !rounded-full w-[140px]" @click="getLists()"
                            >搜索</ElButton
                        >
                        <ElButton
                            color="#181818"
                            class="!h-10 !rounded-full w-[140px] !border-app-border-2"
                            @click="resetParams()"
                            >重置</ElButton
                        >
                    </ElFormItem>
                </ElForm>
            </div>
        </div>
        <div class="grow min-h-0 overflow-hidden flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    height="100%"
                    :data="pager.lists"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="device_code" label="执行设备" width="240" fixed="left"> </ElTableColumn>
                    <ElTableColumn label="添加渠道" width="140">
                        <template #default="{ row }">
                            {{ getAppTypeName(row.channel) }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="执行类型" width="140">
                        <template #default="{ row }">
                            <template v-if="row.exec_type == ExecTypeEnum.PRIVATE_CHAT">私信聊天</template>
                            <template v-if="row.exec_type == ExecTypeEnum.CRAWL">自动爬取</template>
                            <template v-if="row.exec_type == ExecTypeEnum.AUTO_PRIVATE_CHAT">自动私信</template>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="original_message" label="匹配内容" min-width="200" show-overflow-tooltip>
                    </ElTableColumn>
                    <ElTableColumn prop="reg_wechat" label="提取内容" min-width="160"></ElTableColumn>
                    <ElTableColumn prop="wechat_name" label="添加微信" min-width="120">
                        <template #default="{ row }">{{ row.wechat_name || "-" }}</template>
                    </ElTableColumn>
                    <ElTableColumn prop="result" label="加好友结果" min-width="120" show-overflow-tooltip>
                    </ElTableColumn>
                    <ElTableColumn prop="create_time" label="创建时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="120" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex justify-end items-center">
                                <ElButton
                                    v-if="row.status == 0"
                                    class="!border-app-border-2"
                                    color="#181818"
                                    size="small"
                                    @click="handleRetry(row.id)"
                                    >重试</ElButton
                                >
                                <ElButton type="danger" link size="small" @click="handleDelete(row.id)">删除</ElButton>
                            </div>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="flex justify-center items-center h-full">
                            <ElEmpty description="暂无数据" />
                        </div>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-center p-4">
                <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getAutoAddWechatRecord, deleteAutoAddWechat, retryAutoAddWechat } from "@/api/service";
import { getWeChatLists } from "@/api/person_wechat";
import { getDeviceList } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";

enum ExecTypeEnum {
    PRIVATE_CHAT = "1",
    CRAWL = "2",
    AUTO_PRIVATE_CHAT = "3",
}

const nuxtApp = useNuxtApp();

const queryParams = reactive({
    device_code: "",
    wechat_no: "",
    channel: "",
    exec_type: "",
    name: "",
    status: "",
    page_size: 20,
});

const { pager, getLists, resetParams } = usePaging({
    fetchFun: getAutoAddWechatRecord,
    params: queryParams,
});

const { optionsData } = useDictOptions<{
    deviceLists: any[];
    wechatLists: any[];
}>({
    deviceLists: {
        api: getDeviceList,
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

const getAppTypeName = (account_type: number) => {
    const types = {
        [AppTypeEnum.SPH]: "视频号",
        [AppTypeEnum.XHS]: "小红书",
    };
    return types[account_type] || "-";
};

const handleRetry = (id: string) => {
    nuxtApp.$confirm({
        message: "确定重试该记录吗？",
        theme: "dark",
        onConfirm: async () => {
            try {
                await retryAutoAddWechat({ id });
                feedback.msgSuccess("重试成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "重试失败");
            }
        },
    });
};

const handleDelete = async (id) => {
    nuxtApp.$confirm({
        message: "确定删除该记录吗？",
        theme: "dark",
        onConfirm: async () => {
            try {
                await deleteAutoAddWechat({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

getLists();
</script>

<style scoped lang="scss">
:deep(.el-form-item__label) {
    line-height: 40px;
}
</style>
