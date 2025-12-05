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
                <el-form-item label="关键词">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.message"
                        placeholder="请输入关键词"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="场景类型">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.scene_name"
                        placeholder="请输入场景类型"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="提问时间">
                    <daterange-picker
                        v-model:startTime="queryParams.start_date"
                        v-model:endTime="queryParams.end_date" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4">
                <el-button
                    v-perms="['chat_records/del']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
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
                <el-table-column label="昵称" prop="nickname" min-width="100" show-overflow-tooltip />
                <el-table-column label="提问时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="用户提问内容" prop="message" min-width="180" show-overflow-tooltip>
                </el-table-column>
                <el-table-column label="场景类型" prop="scene_name" min-width="120" show-overflow-tooltip />
                <el-table-column label="消耗算力" min-width="100">
                    <template #default="{ row }">
                        <el-tooltip>
                            <div class="flex items-center justify-center gap-1 cursor-pointer">
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
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['chat_records/see']" type="primary" link @click="openPop(row)">
                            查看回复
                        </el-button>
                        <el-button type="danger" v-perms="['chat_records/del']" link @click="handleDelete([row.id])">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <replyPop ref="replyPopRef" v-if="showReply"></replyPop>
    </div>
</template>
<script lang="ts" setup name="dialogueRecord">
import { getDialogueRecord, deleteDialogueRecord } from "@/api/ai_application/chat/record";
import { usePaging } from "@/hooks/usePaging";
import ReplyPop from "./replyPop.vue";
import feedback from "@/utils/feedback";

const queryParams = reactive({
    user: "", //用户信息
    message: "", //关键词
    scene_name: "", //场景类型
    start_date: "",
    end_date: "",
    chat_type: 1,
});

//弹框ref
const replyPopRef = shallowRef<InstanceType<typeof ReplyPop>>();
const showReply = ref<boolean>(false);

const multipleSelection = ref<any[]>([]);

//打开弹框
const openPop = async (row: any) => {
    showReply.value = true;
    await nextTick();
    replyPopRef.value?.open(row);
};

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDialogueRecord,
    params: queryParams,
});

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除？");
    await deleteDialogueRecord({ id });
    getLists();
};

getLists();
</script>
