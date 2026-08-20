<template>
    <el-dialog
        v-model="visible"
        title="激活设备"
        width="780px"
        append-to-body
        destroy-on-close
        :close-on-click-modal="false"
        @closed="handleClosed">
        <div class="mb-3 text-tx-secondary">
            设备号：{{ device?.device_code || "-" }}
            <span class="ml-4">当前用户：{{ device?.nickname || "-" }}</span>
        </div>
        <el-form :inline="true" class="mb-[-8px]" @submit.prevent>
            <el-form-item label="CDK">
                <el-input
                    class="w-[240px]"
                    v-model="queryParams.code"
                    placeholder="请输入CDK搜索"
                    clearable
                    @keyup.enter="resetPage" />
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="resetPage">查询</el-button>
                <el-button @click="handleReset">重置</el-button>
            </el-form-item>
        </el-form>
        <el-table
            class="mt-3"
            size="large"
            v-loading="pager.loading"
            :data="pager.lists"
            highlight-current-row
            row-key="id"
            @current-change="handleCurrentChange"
            @row-click="handleRowClick">
            <el-table-column label="" width="70" align="center">
                <template #default="{ row }">
                    <el-radio v-model="selectedCdkId" :value="row.id" @click.stop>&nbsp;</el-radio>
                </template>
            </el-table-column>
            <el-table-column label="ID" prop="id" width="80" />
            <el-table-column label="CDK" prop="code" min-width="180" show-overflow-tooltip />
            <el-table-column label="套餐" min-width="100">
                <template #default="{ row }">{{ row.type_desc || row.type_name || "-" }}</template>
            </el-table-column>
            <el-table-column label="状态" min-width="90">
                <template #default="{ row }">{{ row.status_desc || row.status_name || "-" }}</template>
            </el-table-column>
            <el-table-column label="创建时间" prop="create_time" min-width="160" />
        </el-table>
        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="submitting" :disabled="!selectedCdkId" @click="handleConfirm">
                确定激活
            </el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import { usePaging } from "@/hooks/usePaging";
import { getAvailableCodesLists, redeemDevice } from "@/api/ai_application/device";
import feedback from "@/utils/feedback";

const props = defineProps<{
    modelValue: boolean;
    device?: Record<string, any> | null;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "success"): void;
}>();

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const queryParams = reactive({
    device_id: "" as number | string,
    code: "",
});

const selectedCdkId = ref<number | string | null>(null);
const submitting = ref(false);

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getAvailableCodesLists,
    params: queryParams,
    size: 25,
});

const handleCurrentChange = (row: Record<string, any> | null) => {
    selectedCdkId.value = row?.id ?? null;
};

const handleRowClick = (row: Record<string, any>) => {
    selectedCdkId.value = row.id;
};

const handleReset = () => {
    selectedCdkId.value = null;
    queryParams.code = "";
    resetPage();
};

const handleConfirm = async () => {
    if (!props.device?.id) {
        feedback.msgWarning("设备信息缺失");
        return;
    }
    if (!selectedCdkId.value) {
        feedback.msgWarning("请选择要兑换的CDK");
        return;
    }
    try {
        submitting.value = true;
        await redeemDevice({
            device_id: props.device.id,
            cdk_id: selectedCdkId.value,
        });
        visible.value = false;
        emit("success");
    } finally {
        submitting.value = false;
    }
};

const handleClosed = () => {
    selectedCdkId.value = null;
    queryParams.code = "";
    queryParams.device_id = "";
    pager.lists = [];
    pager.count = 0;
    pager.page = 1;
};

watch(
    () => props.modelValue,
    (val) => {
        if (!val) return;
        selectedCdkId.value = null;
        queryParams.code = "";
        queryParams.device_id = props.device?.id ?? "";
        if (queryParams.device_id) {
            resetPage();
        }
    },
);
</script>
