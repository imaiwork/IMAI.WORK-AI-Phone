<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.nickname"
                        placeholder="请输入用户名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="粉丝数" prop="extra.fans" min-width="100" />
                <el-table-column label="关注数" prop="extra.followers" min-width="100" />
                <el-table-column label="获赞数" prop="extra.thumbup_collect" min-width="100" />
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>
<script lang="ts" setup>
import { usePaging } from "@/hooks/usePaging";
import { getAccountList } from "@/api/ai_application/service";
const queryParams = reactive({
    nickname: "",
    type: 3,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getAccountList,
    params: queryParams,
});

getLists();
</script>
