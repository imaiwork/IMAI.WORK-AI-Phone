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
                <el-form-item label="思维导图名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入思维导图名称"
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
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="['mind_map.record/del']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
                <el-button v-perms="['ai_application.mind_map/edit']" type="primary" @click="handleEditPrompt"
                    >markdown内置提示词编辑
                </el-button>
            </div>
            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="思维导图内容" prop="ask" min-width="200" show-overflow-tooltip />
                <el-table-column label="消耗算力" prop="points" min-width="120">
                    <template #default="{ row }">
                        <el-tooltip>
                            <div class="flex items-center gap-1 cursor-pointer">
                                <span class="text-red-500">{{ row.points }}</span
                                >算力
                                <Icon name="el-icon-Warning" />
                            </div>
                            <template #content>
                                <div class="text-sm">本次消耗tokens：{{ row.tokens }}</div>
                            </template>
                        </el-tooltip>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleReply(row.reply)"> 查看回复 </el-button>
                        <el-button v-perms="['mind_map.record/del']" type="danger" link @click="handleDelete([row.id])">
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
    <replyPop ref="replyPopRef" v-if="showReply" @close="showReply = false"></replyPop>
    <promptPop ref="promptRef" v-if="showPrompt" @close="showPrompt = false"></promptPop>
</template>
<script lang="ts" setup>
import { getMindMapRecordLists, deleteMindMapRecord } from "@/api/ai_application/mind_map";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
import ReplyPop from "./replyPop.vue";
import PromptPop from "./prompt.vue";
const replyPopRef = shallowRef<InstanceType<typeof ReplyPop>>();
const promptRef = shallowRef<InstanceType<typeof PromptPop>>();

const queryParams = reactive({
    name: "",
    user: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMindMapRecordLists,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const showPrompt = ref<boolean>(false);

const handleEditPrompt = async () => {
    showPrompt.value = true;
    await nextTick();
    promptRef.value?.open();
};

const showReply = ref<boolean>(false);

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleReply = async (reply: any) => {
    showReply.value = true;
    await nextTick();
    replyPopRef.value?.open(reply);
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMindMapRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
