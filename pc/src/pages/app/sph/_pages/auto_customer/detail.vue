<template>
    <div class="h-full flex flex-col gap-[10px]">
        <div class="bg-app-bg-2 rounded-[20px] p-[18px] flex justify-between gap-x-4">
            <div class="flex flex-col gap-y-3">
                <div class="flex">
                    <span class="text-[#ffffff80] flex-shrink-0">执行设备：</span>
                    <span class="flex flex-wrap gap-2">
                        <span
                            class="px-[10px] py-[4px] rounded-md text-[#ffffffcc] shadow-[0_0_0_1px_var(--app-border-color-2)]"
                            v-for="item in detail?.device_codes"
                            :key="item"
                            >{{ item }}</span
                        >
                    </span>
                </div>
                <div class="flex">
                    <span class="text-[#ffffff80] flex-shrink-0">线索词：</span>
                    <span class="flex flex-wrap gap-2">
                        <span
                            class="px-[10px] py-[4px] rounded-md text-[#ffffffcc] text-[11px] shadow-[0_0_0_1px_var(--app-border-color-2)]"
                            v-for="item in detail?.keywords"
                            :key="item"
                            >{{ item }}</span
                        >
                    </span>
                </div>
                <div>
                    <span class="text-[#ffffff80]">关键词执行数量：</span>
                    <span class="text-[#ffffffcc]"
                        >{{ detail?.number_of_implemented_keywords || 0 }} /
                        {{ detail?.implementation_keywords_number || 0 }}</span
                    >
                </div>
                <div>
                    <span class="text-[#ffffff80]">已获客数量：</span>
                    <span class="text-[#ffffffcc]">{{ detail?.crawl_number || 0 }}</span>
                </div>
            </div>
            <div></div>
        </div>
        <div class="grow min-h-0 bg-app-bg-2 rounded-[20px] flex flex-col">
            <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#ffffff1a]">
                <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                    <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                    <div class="text-white">返回</div>
                </div>
                <div class="flex items-center gap-1" v-if="detail?.status && detail?.status != 0">
                    <export-data :params="queryParams" :fetch-fun="getTaskClue" :export-fun="getTaskClue">
                        <template #trigger>
                            <ElButton type="primary" class="!rounded-full !h-10 w-[98px]">导出</ElButton>
                        </template>
                        <template #form-item="{ formData }">
                            <ElFormItem label="线索有效性：">
                                <ElSelect
                                    v-model="formData.status"
                                    class="!w-[260px] !h-10"
                                    placeholder="请选择"
                                    filterable
                                    clearable
                                    :empty-values="[undefined, null]"
                                    :show-arrow="false">
                                    <ElOption label="全部" value=""></ElOption>
                                    <ElOption label="未校验" value="0"></ElOption>
                                    <ElOption label="已通过校验" value="1"></ElOption>
                                    <ElOption label="未通过校验" value="2"></ElOption>
                                    <ElOption label="待执行" value="4"></ElOption>
                                </ElSelect>
                            </ElFormItem>
                        </template>
                    </export-data>
                </div>
            </div>
            <div class="grow min-h-0">
                <ElTable
                    height="100%"
                    :data="pager.lists"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn prop="username" label="用户名称" min-width="120" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.username || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="name" label="线索词" min-width="120">
                        <template #default="{ row }">
                            {{ row.exec_keyword || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="address" label="地区" min-width="80">
                        <template #default="{ row }">
                            {{ row.address || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="crawl_content" label="获取内容" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.crawl_content || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="device_code" label="执行设备" width="180"></ElTableColumn>
                    <ElTableColumn prop="reg_content" label="提取内容" min-width="200" show-overflow-tooltip>
                        <template #default="{ row }">
                            {{ row.reg_content || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="clue_type_name" label="线索类型" width="100"> </ElTableColumn>
                    <ElTableColumn label="线索检验" width="100">
                        <template #default="{ row }">
                            {{ formatStatus(row.status) }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn prop="tokens" label="算力消耗" min-width="80"> </ElTableColumn>
                    <ElTableColumn prop="create_time" label="执行时间" width="180"></ElTableColumn>
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
import { getTaskDetail, getTaskClue } from "@/api/sph";

const emit = defineEmits<{
    (e: "back"): void;
}>();

const query = searchQueryToObject();

const detail = ref<any>({});

const queryParams = reactive({
    task_id: query.id,
});

const { pager, getLists } = usePaging({
    fetchFun: getTaskClue,
    params: queryParams,
});

const formatStatus = (status: number) => {
    return {
        1: "线索有效",
        2: "线索无效",
        3: "内含有效线索",
    }[status];
};

const getDetail = async () => {
    const data = await getTaskDetail({ id: query.id });
    detail.value = data;
};

const init = async () => {
    await getDetail();
    await getLists();
};

onMounted(init);
</script>

<style scoped></style>
