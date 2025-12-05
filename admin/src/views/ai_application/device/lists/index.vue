<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="设备号">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.device_code"
                        placeholder="请输入设备号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
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
                        <image-contain
                            radius="50%"
                            class="flex-none"
                            v-if="row.avatar"
                            :src="row.avatar"
                            :width="48"
                            :height="48"
                            :preview-src-list="[row.avatar]"
                            preview-teleported
                            fit="cover" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="设备号" prop="device_code" min-width="180" />
                <el-table-column label="创建时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_application.device/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id, row.device_code)">
                            删除
                        </el-button>
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
import { usePaging } from "@/hooks/usePaging";
import { getDeviceLists, deleteDevice } from "@/api/ai_application/device";
import feedback from "@/utils/feedback";
const queryParams = reactive({
    device_code: "",
    nickname: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDeviceLists,
    params: queryParams,
});

const handleDelete = async (id: number, device_code: string) => {
    await feedback.confirm("确定要删除该设备吗？");
    await deleteDevice({ id, device_code });
    getLists();
};

getLists();
</script>
