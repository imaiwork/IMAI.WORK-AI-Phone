<template>
    <div>
        <el-card shadow="never" class="!border-none mt-[10px]">
            <el-form ref="formRef" class="mt-4" :model="queryParams" :inline="true">
                <el-form-item label="编号信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.keyword"
                        placeholder="请输入批次编号/卡密编号"
                        clearable />
                </el-form-item>
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user_keyword"
                        placeholder="请输入ID编号/昵称/手机号码"
                        clearable />
                </el-form-item>

                <el-form-item label="使用时间">
                    <daterange-picker
                        class="w-[280px]"
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <export-data
                        class="ml-2.5"
                        :fetch-fun="cardcodeRecordLists"
                        :params="queryParams"
                        :page-size="pager.size" />
                </el-form-item>
            </el-form>
        </el-card>
        <el-card shadow="never" class="!border-none mt-[10px]">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="批次编号" prop="sn" min-width="160" />
                <el-table-column label="卡密编号" prop="record_sn" min-width="160" />
                <el-table-column label="使用人" min-width="190">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <el-avatar :size="50" :src="row?.avatar">
                                {{ row.nickname }}
                            </el-avatar>
                            <div class="ml-[10px]">
                                {{ row.nickname }}
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="面额" prop="content" min-width="140" />
                <el-table-column label="使用时间" prop="use_time" min-width="140" />
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script setup lang="ts" name="redeemRecordList">
import { usePaging } from "@/hooks/usePaging";
import { cardcodeRecordLists } from "@/api/marketing/redeem_code";

const queryParams = reactive({
    keyword: "",
    user_keyword: "",
    type: "",
    status: 1,
    start_time: "",
    end_time: "",
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: cardcodeRecordLists,
    params: queryParams,
});

getLists();
</script>
