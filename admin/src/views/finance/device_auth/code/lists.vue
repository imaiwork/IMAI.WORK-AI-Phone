<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="CDK">
                    <el-input
                        class="w-[240px]"
                        v-model="queryParams.code"
                        placeholder="请输入CDK"
                        clearable
                        @keyup.enter="handleSearch" />
                </el-form-item>
                <el-form-item label="套餐类型">
                    <el-select
                        class="!w-[160px]"
                        v-model="queryParams.type"
                        placeholder="全部"
                        :empty-values="[undefined, null]">
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in DEVICE_AUTH_PLAN_TYPE"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="使用人">
                    <el-input
                        class="w-[200px]"
                        v-model="queryParams.user_keyword"
                        placeholder="用户昵称/手机号"
                        clearable
                        @keyup.enter="handleSearch" />
                </el-form-item>
                <el-form-item label="设备号">
                    <el-input
                        class="w-[200px]"
                        v-model="queryParams.keyword"
                        placeholder="请输入设备号"
                        clearable
                        @keyup.enter="handleSearch" />
                </el-form-item>
                <el-form-item label="创建时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>

                    <export-data
                        v-perms="['deviceauth.deviceAuthPlan/export']"
                        class="ml-2.5"
                        :fetch-fun="deviceAuthCodeLists"
                        :params="queryParams"
                        :page-size="pager.size" />
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex items-center justify-between gap-x-4">
                <div class="flex-1">
                    <el-tabs v-model="activeStatus" @tab-change="handleTabChange">
                        <el-tab-pane
                            v-for="tab in statusTabs"
                            :key="String(tab.value)"
                            :name="tab.value"
                            :label="tab.label" />
                    </el-tabs>
                </div>
                <el-button
                    v-perms="['deviceauth.deviceAuthCode/syncFromPlatform']"
                    type="primary"
                    :loading="syncLoading"
                    @click="handleSyncFromPlatform">
                    从远程同步
                </el-button>
            </div>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="CDK" prop="code" min-width="200" show-overflow-tooltip />
                <el-table-column label="套餐" prop="type_desc" min-width="100" />
                <el-table-column label="状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.status === DeviceAuthCodeStatus.USED ? 'info' : 'success'">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="来源" prop="source_desc" min-width="100" />
                <el-table-column label="使用设备" min-width="160" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.device_code || "-" }}</template>
                </el-table-column>
                <el-table-column label="拥有者" min-width="120" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.owner_user_name || "-" }}</template>
                </el-table-column>
                <el-table-column label="使用人" min-width="120">
                    <template #default="{ row }">{{ row.nickname || "-" }}</template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" />
                <el-table-column label="使用时间" min-width="180">
                    <template #default="{ row }">{{ row.use_time || "-" }}</template>
                </el-table-column>
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.status !== DeviceAuthCodeStatus.USED"
                            v-perms="['deviceauth.deviceAuthCode/transfer']"
                            type="primary"
                            link
                            @click="handleTransfer(row)">
                            转移CDK
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <transfer-popup v-model="transferVisible" :cdk="currentCdk" @success="getLists" />
    </div>
</template>

<script lang="ts" setup name="deviceAuthCodeLists">
import { usePaging } from "@/hooks/usePaging";
import { deviceAuthCodeLists, deviceAuthCodeSyncFromPlatform } from "@/api/ai_application/device_auth";
import { DEVICE_AUTH_PLAN_TYPE, DEVICE_AUTH_CODE_STATUS, DeviceAuthCodeStatus } from "@/enums/deviceAuthEnums";
import feedback from "@/utils/feedback";
import TransferPopup from "./components/transfer-popup.vue";

const queryParams = reactive({
    code: "",
    type: "",
    user_keyword: "",
    keyword: "",
    status: "" as number | string,
    start_time: "",
    end_time: "",
});

const activeStatus = ref<number | string>("");

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: deviceAuthCodeLists,
    params: queryParams,
});

// 各状态数量取自列表接口返回的 extend（无论是否过滤都返回总数）
const STATUS_COUNT_KEY: Record<number, string> = {
    [DeviceAuthCodeStatus.UNUSED]: "unused",
    [DeviceAuthCodeStatus.USED]: "used",
};

const readCount = (value: number | string) => {
    const extend: any = pager.extend || {};
    const unused = Number(extend.unused ?? 0);
    const used = Number(extend.used ?? 0);
    if (value === "") return unused + used;
    const key = STATUS_COUNT_KEY[value as number];
    return key ? Number(extend[key] ?? 0) : 0;
};

const statusTabs = computed(() => [
    { label: `全部 (${readCount("")})`, value: "" as number | string },
    ...DEVICE_AUTH_CODE_STATUS.map((item) => ({
        label: `${item.label} (${readCount(item.value)})`,
        value: item.value as number | string,
    })),
]);

const handleSearch = () => {
    resetPage();
};

const handleReset = () => {
    activeStatus.value = "";
    resetParams();
};

const handleTabChange = (name: number | string) => {
    queryParams.status = name;
    resetPage();
};

const syncLoading = ref(false);
const handleSyncFromPlatform = async () => {
    await feedback.confirm("确定要从中台同步CDK？");
    try {
        syncLoading.value = true;
        await deviceAuthCodeSyncFromPlatform();
        getLists();
    } finally {
        syncLoading.value = false;
    }
};

const currentCdk = ref<Record<string, any> | null>(null);
const transferVisible = ref(false);

const handleTransfer = (row: Record<string, any>) => {
    currentCdk.value = row;
    transferVisible.value = true;
};

onBeforeUnmount(() => {
    transferVisible.value = false;
    currentCdk.value = null;
});

getLists();
</script>
