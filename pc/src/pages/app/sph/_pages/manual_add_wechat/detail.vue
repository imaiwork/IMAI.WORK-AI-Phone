<template>
    <div class="h-full flex flex-col gap-[10px]">
        <div class="bg-app-bg-2 rounded-[20px] p-[18px]">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                    <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                    <div class="text-white">返回</div>
                </div>
            </div>
            <div class="flex justify-between gap-x-4">
                <div class="flex flex-col gap-y-3">
                    <div>
                        <span class="text-[#ffffff80]">添加频率：</span>
                        <span class="text-[#ffffffcc]"
                            >相隔{{ detail?.add_interval_time }}分钟添加{{ detail?.add_number }}个微信号</span
                        >
                    </div>
                    <div>
                        <span class="text-[#ffffff80]">微信数量：</span>
                        <span class="text-[#ffffffcc]">共有{{ detail?.wechat_id?.length || 0 }}个微信执行该任务</span>
                    </div>
                    <div class="flex">
                        <span class="text-[#ffffff80] flex-shrink-0">申请备注：</span>
                        <span class="flex flex-wrap gap-2">
                            <span
                                class="px-[10px] py-[4px] rounded-md text-[#ffffffcc] shadow-[0_0_0_1px_var(--app-border-color-2)]"
                                v-for="item in detail?.remarks"
                                :key="item"
                                >{{ item }}</span
                            >
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="grow min-h-0 bg-app-bg-2 rounded-[20px] flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    height="100%"
                    :data="pager.lists"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="clue_wechat" label="线索" min-width="120" show-overflow-tooltip>
                    </ElTableColumn>
                    <ElTableColumn prop="wechat_no" label="执行账号" min-width="140">
                        <template #default="{ row }">
                            {{ row.wechat_no || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="申请备注词" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.remark || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="任务状态" width="160" align="center">
                        <template #default="{ row }">
                            <div class="flex items-center justify-center gap-x-2">
                                <div class="w-[6px] h-[6px] rounded-full" :class="getStatusStyle(row.status)"></div>
                                <span>
                                    <template v-if="row.status == 0">添加失败</template>
                                    <template v-if="row.status == 1">添加成功</template>
                                    <template v-if="row.status == 2">执行中</template>
                                    <template v-if="row.status == 4">等待添加</template>
                                </span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="exec_time" label="执行时间" width="180"></ElTableColumn>
                    <ElTableColumn label="操作" width="80" fixed="right" align="right">
                        <template #default="{ row }">
                            <ElButton type="danger" link @click="handleDelete(row.id)">删除</ElButton>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="flex justify-center items-center h-full">
                            <ElEmpty description="暂无数据"></ElEmpty>
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
import { getManualAddWechatDetail, getManualAddWechatRecord, deleteManualAddWechatRecord } from "@/api/sph";

const emit = defineEmits(["back"]);

const query = searchQueryToObject();

const detail = ref<any>({});

const queryParams = reactive({
    id: query.id,
});

const { pager, getLists } = usePaging({
    fetchFun: getManualAddWechatRecord,
    params: queryParams,
});

const getStatusStyle = (status: number) => {
    const styles = {
        0: "bg-danger",
        2: "bg-[#FFBC50]",
        1: "bg-[#3BB840]",
        4: "bg-primary",
    };
    return styles[status];
};

const handleDelete = async (id) => {
    useNuxtApp().$confirm({
        message: "确定删除该记录吗？",
        theme: "dark",
        onConfirm: async () => {
            try {
                await deleteManualAddWechatRecord({ id });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};
const getDetail = async () => {
    const data = await getManualAddWechatDetail({ id: query.id });
    detail.value = data;
};

const init = async () => {
    await getDetail();
    await getLists();
};

onMounted(init);
</script>

<style scoped></style>
