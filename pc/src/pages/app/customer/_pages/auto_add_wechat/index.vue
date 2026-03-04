<template>
    <div class="h-full flex flex-col bg-white rounded-[20px] border border-br overflow-hidden min-w-[1000px]">
        <div class="flex-shrink-0 p-6 bg-white">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-xl font-[900] text-gray-950">自动加微记录</h1>
                    <div class="flex gap-3">
                        <ElButton type="primary" class="!h-10 !rounded-full px-8 font-medium" @click="getLists()">
                            <Icon name="el-icon-Search" />
                            <span class="ml-1">搜索</span>
                        </ElButton>
                        <ElButton
                            class="!h-10 !rounded-full px-8 !bg-gray-100 !border-none !text-tx-regular hover:!bg-gray-200"
                            @click="resetParams()">
                            重置
                        </ElButton>
                    </div>
                </div>

                <div class="grid grid-cols-5 gap-4">
                    <div class="filter-item">
                        <div class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1">执行设备</div>
                        <ElSelect
                            v-model="queryParams.device_code"
                            class="custom-select"
                            placeholder="全部设备"
                            filterable
                            clearable>
                            <ElOption
                                v-for="item in optionsData.deviceLists"
                                :key="item.id"
                                :label="item.device_code"
                                :value="item.device_code">
                                {{ item.device_name }}（{{ item.device_code }}）
                            </ElOption>
                        </ElSelect>
                    </div>

                    <div class="filter-item">
                        <div class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1">添加渠道</div>
                        <ElSelect v-model="queryParams.channel" class="custom-select" placeholder="所有渠道" clearable>
                            <ElOption label="小红书" :value="AppTypeEnum.XHS"></ElOption>
                            <ElOption label="视频号" :value="AppTypeEnum.SPH"></ElOption>
                        </ElSelect>
                    </div>

                    <div class="filter-item">
                        <div class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1">执行类型</div>
                        <ElSelect
                            v-model="queryParams.exec_type"
                            class="custom-select"
                            placeholder="所有类型"
                            clearable>
                            <ElOption label="私信聊天" :value="ExecTypeEnum.PRIVATE_CHAT"></ElOption>
                            <ElOption label="自动爬取" :value="ExecTypeEnum.CRAWL"></ElOption>
                            <ElOption label="自动私信" :value="ExecTypeEnum.AUTO_PRIVATE_CHAT"></ElOption>
                        </ElSelect>
                    </div>

                    <div class="filter-item">
                        <div class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1">添加微信</div>
                        <ElSelect
                            v-model="queryParams.wechat_no"
                            class="custom-select"
                            placeholder="选择微信"
                            filterable
                            clearable>
                            <ElOption
                                v-for="item in optionsData.wechatLists"
                                :key="item.id"
                                :label="item.wechat_nickname"
                                :value="item.wechat_id"></ElOption>
                        </ElSelect>
                    </div>

                    <div class="filter-item">
                        <div class="text-xs font-medium text-tx-placeholder mb-1.5 ml-1">加微结果</div>
                        <ElSelect v-model="queryParams.status" class="custom-select" placeholder="全部状态" clearable>
                            <ElOption label="全部" value=""></ElOption>
                            <ElOption label="失败" value="0"></ElOption>
                            <ElOption label="成功" value="1"></ElOption>
                            <ElOption label="执行中" value="2"></ElOption>
                        </ElSelect>
                    </div>
                </div>
            </div>
        </div>
        <div class="grow min-h-0">
            <ElTable height="100%" :data="pager.lists" v-loading="pager.loading">
                <ElTableColumn prop="device_code" label="执行设备" width="220" fixed="left">
                    <template #default="{ row }">
                        <div class="flex flex-col">
                            <span class="text-gray-950 font-medium">{{ row.device_name || "-" }}</span>
                            <span class="text-[11px] text-tx-placeholder">ID: {{ row.device_code }}</span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="添加渠道" width="120">
                    <template #default="{ row }">
                        <span
                            class="px-2 py-0.5 rounded text-xs font-medium"
                            :class="
                                row.channel == AppTypeEnum.XHS ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'
                            ">
                            {{ getAppTypeName(row.channel) }}
                        </span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="执行类型" width="120">
                    <template #default="{ row }">
                        <span
                            class="text-tx-regular text-xs bg-gray-50 px-2 py-0.5 rounded border border-br-extra-light">
                            {{
                                row.exec_type == ExecTypeEnum.PRIVATE_CHAT
                                    ? "私信聊天"
                                    : row.exec_type == ExecTypeEnum.CRAWL
                                    ? "自动爬取"
                                    : "自动私信"
                            }}
                        </span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="original_message" label="匹配内容" min-width="200" show-overflow-tooltip />

                <ElTableColumn prop="reg_wechat" label="提取内容" min-width="160">
                    <template #default="{ row }">
                        <span class="font-mono font-medium text-primary">{{ row.reg_wechat || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="wechat_name" label="添加微信" min-width="140">
                    <template #default="{ row }">
                        <div class="flex items-center justify-center gap-1.5 text-tx-regular">
                            <Icon name="local-icon-wechat" :size="14" color="#22c55e" />
                            {{ row.wechat_name || "-" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="加微结果" width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span class="text-xs font-medium text-blue-500">{{ row.result || "-" }}</span>
                    </template>
                </ElTableColumn>
                <ElTableColumn
                    prop="create_time"
                    label="创建时间"
                    width="170"
                    class-name="text-xs text-tx-placeholder" />
                <ElTableColumn label="操作" width="100" fixed="right" align="right">
                    <template #default="{ row }">
                        <div class="flex justify-end items-center gap-1">
                            <template v-if="row.status == 0">
                                <ElButton
                                    type="primary"
                                    link
                                    size="small"
                                    class="!font-medium"
                                    @click="handleRetry(row.id)"
                                    >重试</ElButton
                                >
                                <div class="w-[1px] h-3 bg-br-extra-light mx-1"></div>
                            </template>
                            <ElButton type="danger" class="!font-medium" link size="small" @click="handleDelete(row.id)"
                                >删除</ElButton
                            >
                        </div>
                    </template>
                </ElTableColumn>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 条加微记录数据</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
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

const getStatusColor = (status: number | string) => {
    if (status == 1) return "text-green-600";
    if (status == 0) return "text-red-500";
    return "text-blue-500";
};

const handleRetry = (id: string) => {
    nuxtApp.$confirm({
        message: "确定重试该记录吗？",
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

const handleDelete = async (id: string) => {
    nuxtApp.$confirm({
        message: "确定删除该记录吗？",
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
<style lang="scss" scoped></style>
