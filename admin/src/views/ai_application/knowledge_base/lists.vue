<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="状态" v-if="activeTab == KnowledgeType.RAG">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.is_bind"
                        placeholder="请选择状态"
                        :empty-values="[null, undefined]"
                        clearable
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="未导入" value="0" />
                        <el-option label="已导入" value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item label="知识库名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入知识库名称"
                        clearable
                        @keyup.enter="resetPage" />
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
            <div class="mb-4">
                <el-tabs v-model="activeTab" @tab-click="handleTabClick">
                    <el-tab-pane label="向量知识库" :name="KnowledgeType.VECTOR"></el-tab-pane>
                    <el-tab-pane label="RAG知识库" :name="KnowledgeType.RAG"></el-tab-pane>
                </el-tabs>
            </div>
            <div class="mb-4">
                <el-button
                    v-perms="['ai_application.interview.record/del']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
                size="large"
                ref="tableRef"
                v-loading="pager.loading"
                :data="pager.lists"
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column prop="name" label="知识库名称" min-width="140" show-overflow-tooltip></el-table-column>
                <el-table-column label="知识库封面" width="120" v-if="activeTab == KnowledgeType.VECTOR">
                    <template #default="{ row }">
                        <image-contain
                            :src="row.image"
                            width="60"
                            height="60"
                            fit="cover"
                            preview-teleported
                            :preview-src-list="[row.image]" />
                    </template>
                </el-table-column>
                <el-table-column label="所属用户" min-width="140" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ row.nickname || row.create_user }}
                    </template>
                </el-table-column>
                <el-table-column prop="file_count" label="文件数量" min-width="100" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ row.file_counts || row.file_count }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="tokens" min-width="100" v-if="activeTab == KnowledgeType.RAG">
                    <template #default="{ row }"> {{ row.tokens }}算力 </template>
                </el-table-column>
                <el-table-column
                    label="使用次数"
                    prop="request_count"
                    min-width="100"
                    v-if="activeTab == KnowledgeType.RAG">
                </el-table-column>
                <el-table-column label="状态" width="110" show-overflow-tooltip v-if="activeTab == KnowledgeType.RAG">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_bind == 1" type="success">已导入</el-tag>
                        <el-tag v-else type="danger">未导入</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="180"></el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" v-perms="['ai_application.kn/files']" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.kn/files'),
                                    query: {
                                        id: row.id,
                                        type: activeTab,
                                    },
                                }">
                                查看文件
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.kn.lists/del']"
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
import {
    knowKnowledgeList,
    knowKnowledgeDelete,
    knowKnowledgeVectorList,
    knowKnowledgeVectorDelete,
} from "@/api/ai_application/knowledge_base/lists";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { getRoutePath } from "@/router";
import { ElTable } from "element-plus";

enum KnowledgeType {
    VECTOR = "vector",
    RAG = "rag",
}

const queryParams = reactive({
    is_bind: "",
    name: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: (params) => {
        if (activeTab.value === KnowledgeType.VECTOR) {
            return knowKnowledgeVectorList(params);
        } else {
            return knowKnowledgeList(params);
        }
    },
    params: queryParams,
});

const activeTab = ref("vector");

const tableRef = ref<InstanceType<typeof ElTable>>();

const handleTabClick = (tab: any) => {
    activeTab.value = tab.paneName;
    getLists();
};

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    activeTab.value == KnowledgeType.VECTOR
        ? await knowKnowledgeVectorDelete({ id })
        : await knowKnowledgeDelete({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
