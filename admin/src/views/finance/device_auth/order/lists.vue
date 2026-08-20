<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="订单编号">
                    <el-input
                        class="w-[240px]"
                        v-model="queryParams.sn"
                        placeholder="请输入订单编号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[200px]"
                        v-model="queryParams.user_keyword"
                        placeholder="用户昵称/手机号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="业务类型">
                    <el-select
                        class="!w-[150px]"
                        v-model="queryParams.biz_type"
                        placeholder="全部"
                        :empty-values="[undefined, null]">
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in DEVICE_AUTH_BIZ_TYPE"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="支付方式">
                    <el-select
                        class="!w-[150px]"
                        v-model="queryParams.pay_type"
                        placeholder="全部"
                        :empty-values="[undefined, null]">
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in DEVICE_AUTH_PAY_TYPE"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="支付状态">
                    <el-select
                        class="!w-[140px]"
                        v-model="queryParams.pay_status"
                        placeholder="全部"
                        :empty-values="[undefined, null]">
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in DEVICE_AUTH_PAY_STATUS"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="下单时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <export-data
                        v-perms="['deviceauth.deviceAuthOrder/export']"
                        class="ml-2.5"
                        :fetch-fun="deviceAuthOrderLists"
                        :params="queryParams"
                        :page-size="pager.size" />
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="订单编号" prop="sn" min-width="200" show-overflow-tooltip />
                <el-table-column label="用户" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.nickname || "-" }}</div>
                        <div class="text-tx-secondary text-xs">{{ row.mobile || "-" }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="业务类型" prop="biz_type_desc" min-width="110" />
                <el-table-column label="套餐" prop="auth_type_desc" min-width="100" />
                <el-table-column label="数量" prop="quantity" min-width="80" />
                <el-table-column label="订单金额(元)" prop="order_amount" min-width="110" />
                <el-table-column label="算力金额" prop="tokens_amount" min-width="100" />
                <el-table-column label="支付类型" prop="pay_type_desc" min-width="100" />
                <el-table-column label="支付方式" prop="pay_way_desc" min-width="110">
                    <template #default="{ row }">{{ row.pay_way_desc || "-" }}</template>
                </el-table-column>
                <el-table-column label="支付状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.pay_status === DeviceAuthPayStatus.PAID ? 'success' : 'info'">
                            {{ row.pay_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="支付时间" min-width="180">
                    <template #default="{ row }">{{ row.pay_time || "-" }}</template>
                </el-table-column>
                <el-table-column label="下单时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="90" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row.id)">详情</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <detail-popup v-if="showDetail" ref="detailRef" @close="showDetail = false" />
    </div>
</template>

<script lang="ts" setup name="deviceAuthOrderLists">
import { usePaging } from "@/hooks/usePaging";
import { deviceAuthOrderLists } from "@/api/ai_application/device_auth";
import {
    DEVICE_AUTH_BIZ_TYPE,
    DEVICE_AUTH_PAY_TYPE,
    DEVICE_AUTH_PAY_STATUS,
    DeviceAuthPayStatus,
} from "@/enums/deviceAuthEnums";
import DetailPopup from "./detail.vue";

const queryParams = reactive({
    sn: "",
    user_keyword: "",
    biz_type: "",
    pay_type: "",
    pay_status: "" as number | string,
    start_time: "",
    end_time: "",
});

const showDetail = ref(false);
const detailRef = shallowRef<InstanceType<typeof DetailPopup>>();

const handleDetail = async (id: number) => {
    showDetail.value = true;
    await nextTick();
    detailRef.value?.open(id);
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: deviceAuthOrderLists,
    params: queryParams,
});

getLists();
</script>
