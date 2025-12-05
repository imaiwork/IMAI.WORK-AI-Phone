<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户昵称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="场景名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入场景名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.status"
                        placeholder="请选择状态"
                        clearable
                        :empty-values="[null, undefined]"
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="启用" value="1" />
                        <el-option label="禁用" value="0" />
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
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4 flex gap-4">
                <el-button
                    v-perms="['ai_application.lp.scene/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
                <router-link
                    v-perms="['ai_application.lp.scene/add']"
                    :to="getRoutePath('ai_application.lp.scene/add:edit')">
                    <el-button type="primary">新增</el-button>
                </router-link>
            </div>
            <el-table
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="场景名称" prop="name" min-width="180" />
                <el-table-column label="场景头像" min-width="100">
                    <template #default="{ row }">
                        <image-contain
                            :src="row.logo"
                            radius="50%"
                            width="60"
                            height="60"
                            preview-teleported
                            :preview-src-list="[row.logo]" />
                    </template>
                </el-table-column>
                <el-table-column label="场景简介" prop="description" min-width="180" show-overflow-tooltip />
                <el-table-column label="状态" width="100" v-perms="['ai_application.lp.scene/status']">
                    <template #default="{ row }">
                        <el-switch
                            @change="changeStatus(row.id)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="user_name" min-width="140" show-overflow-tooltip />
                <el-table-column label="使用次数" prop="use_count" width="100" />
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.lp.scene/edit']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.lp.scene/add:edit'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                编辑
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.lp.scene/delete']"
                            type="danger"
                            link
                            @click="handleDelete([row.id])">
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
import { getLpSceneLists, lpSceneDelete, lpSceneChangeStatus } from "@/api/ai_application/ladder_player/scene";
import { formatAudioTime } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";

const queryParams = reactive({
    name: "",
    user: "",
    start_time: "",
    end_time: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getLpSceneLists,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await lpSceneDelete({ id });
    getLists();
};

//修改状态
const changeStatus = async (id: any) => {
    try {
        await lpSceneChangeStatus({ id });
    } finally {
        getLists();
    }
};

getLists();
</script>
