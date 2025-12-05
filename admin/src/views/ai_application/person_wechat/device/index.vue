<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="微信ID">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.wechat_id"
                        placeholder="请输入微信ID"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="设备ID">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.device_code"
                        placeholder="请输入设备ID"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="设备状态">
                    <el-select
                        v-model="queryParams.device_status"
                        placeholder="请选择"
                        clearable
                        class="!w-[140px]"
                        :empty-values="[null, undefined]"
                        @change="getLists()">
                        <el-option label="全部" value />
                        <el-option label="在线" value="1" />
                        <el-option label="离线" value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="微信ID" prop="wechat_id" min-width="180" />
                <el-table-column label="微信名称" prop="wechat_nickname" min-width="120" />
                <el-table-column label="设备ID" prop="device_code" min-width="180" />
                <el-table-column label="设备版本" prop="sdk_version" min-width="140" />
                <el-table-column label="创建用户" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="当前状态" width="120">
                    <template #default="{ row }">
                        <el-tag type="success" v-if="row.device_status == 1">在线</el-tag>
                        <el-tag type="danger" v-else-if="row.device_status == 0">离线</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script lang="ts" setup>
import { getDeviceList } from "@/api/ai_application/person_wechat/device";
import { usePaging } from "@/hooks/usePaging";

const queryParams = reactive({
    wechat_id: "",
    device_code: "",
    device_status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDeviceList,
    params: queryParams,
});

getLists();
</script>
