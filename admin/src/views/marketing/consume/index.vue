<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="用户昵称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="调用类型">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.type_id"
                        placeholder="请选择调用类型"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option value="" label="全部" />
                        <el-option value="9001" label="充值" />
                        <el-option
                            v-for="item in optionsData.typesLists"
                            :key="item.code"
                            :label="item.name"
                            :value="item.code" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创建时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card shadow="never" class="!border-none mt-4">
            <el-table size="large" :data="pager.lists" v-loading="pager.loading">
                <el-table-column label="ID" prop="id" min-width="80"></el-table-column>
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="手机号" prop="mobile" min-width="120" show-overflow-tooltip />
                <el-table-column label="调用类型" min-width="150" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="truncate">{{ row.type_name }}</span>
                            <el-tooltip
                                v-if="Number(row.team_id) > 0"
                                :content="row.team_name ? `团队：${row.team_name}` : '团队空间消耗'">
                                <el-tag type="warning" size="small" effect="light" class="shrink-0">团队</el-tag>
                            </el-tooltip>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="当前算力" min-width="120">
                    <template #default="{ row }">
                        <div class="flex items-center gap-1 cursor-pointer">
                            <div>
                                <span class="">{{ row.action == 1 ? "+" : "-" }}{{ row.change_amount }}</span
                                >算力
                            </div>
                            <el-tooltip v-if="shouldShowExtraTip(row)">
                                <div class="leading-[0]">
                                    <Icon name="el-icon-Warning" />
                                </div>
                                <template #content>
                                    <div class="text-sm flex flex-col gap-1">
                                        <span v-for="(value, key) in row.extra" :key="key">
                                            {{ key }}:{{ value }}
                                        </span>
                                    </div>
                                </template>
                            </el-tooltip>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="备注" prop="remark" min-width="120" show-overflow-tooltip />
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script setup lang="ts">
import { getCreditSet } from "@/api/marketing/creditset";
import { getConsumeLists } from "@/api/marketing/consume";
import { usePaging } from "@/hooks/usePaging";
import { useDictOptions } from "@/hooks/useDictOptions";

const queryParams = reactive({
    user: "",
    type_id: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getConsumeLists,
    params: queryParams,
});

const { optionsData } = useDictOptions<{
    typesLists: any[];
}>({
    typesLists: {
        api: getCreditSet,
    },
});

/** 有明细 extra 时展示提示；充值类增加流水不展示 */
const shouldShowExtraTip = (row: any) => {
    const extra = row?.extra;
    if (!extra || typeof extra !== "object" || Object.keys(extra).length === 0) {
        return false;
    }
    const typeName = String(row?.type_name ?? "");
    if (typeName.includes("充值") || typeName.includes("加油包") || typeName.includes("礼包")) {
        return false;
    }
    return true;
};

getLists();
</script>
