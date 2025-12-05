<template>
    <div>
        <div>
            <div class="grid grid-cols-4 gap-4">
                <div class="text-white bg-[#F59B22] p-4 rounded-lg flex justify-between gap-2">
                    <Icon name="el-icon-Money" :size="24"></Icon>
                    <div class="text-right">
                        <div class="text-5xl font-bold leading-6">
                            {{ workbenchData.total_income.toFixed(2) }}
                        </div>
                        <div class="text-lg mt-3">总收入</div>
                    </div>
                </div>
                <div class="text-white bg-[#3D3CDD] p-4 rounded-lg flex justify-between gap-2">
                    <Icon name="el-icon-Tickets" :size="24"></Icon>
                    <div class="text-right">
                        <div class="text-5xl font-bold leading-6">
                            {{ workbenchData.total_orders }}
                        </div>
                        <div class="text-lg mt-3">订单数</div>
                    </div>
                </div>
                <div class="text-white bg-[#38C66B] p-4 rounded-lg flex justify-between gap-2">
                    <Icon name="el-icon-Money" :size="24"></Icon>
                    <div class="text-right">
                        <div class="text-5xl font-bold leading-6">
                            {{ workbenchData.today_income.toFixed(2) }}
                        </div>
                        <div class="text-lg mt-3">今日收入</div>
                    </div>
                </div>
                <div class="text-white bg-[#E9E305] p-4 rounded-lg flex justify-between gap-2">
                    <Icon name="el-icon-Tickets" :size="24"></Icon>
                    <div class="text-right">
                        <div class="text-5xl font-bold leading-6">
                            {{ workbenchData.today_orders }}
                        </div>
                        <div class="text-lg mt-3">今日订单数</div>
                    </div>
                </div>
            </div>
        </div>
        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="购买时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item label="订单状态">
                    <el-select
                        v-model="queryParams.pay_status"
                        class="!w-[120px]"
                        placeholder="请选择订单状态"
                        :empty-values="[undefined, null]">
                        <el-option label="全部" value="" />
                        <el-option label="交易成功" value="1" />
                        <el-option label="交易失败" value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="手机号" prop="mobile" width="130" />
                <el-table-column label="支付类型" prop="pay_way" min-width="100" />
                <el-table-column label="实付价格" prop="order_amount" min-width="100" />
                <el-table-column label="流水号" prop="sn" min-width="180" show-overflow-tooltip />
                <el-table-column label="记录时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="订单状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag type="success" v-if="row.pay_status == 1">交易成功</el-tag>
                        <el-tag type="danger" v-if="row.pay_status == 0">交易失败</el-tag>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script lang="ts" setup>
import { getWorkbench } from "@/api/app";
import { accountLog } from "@/api/finance";
import { usePaging } from "@/hooks/usePaging";
const queryParams = reactive({
    user: "",
    start_time: "",
    end_time: "",
    pay_status: "",
});

const workbenchData = reactive({
    today_income: 0,
    today_orders: 0,
    total_orders: 0,
    total_income: 0,
});

const getData = async () => {
    const { finance = {} } = await getWorkbench();
    workbenchData.today_income = finance.today_income;
    workbenchData.today_orders = finance.today_orders;
    workbenchData.total_orders = finance.total_orders;
    workbenchData.total_income = finance.total_income;
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: accountLog,
    params: queryParams,
});

getLists();
getData();
</script>
