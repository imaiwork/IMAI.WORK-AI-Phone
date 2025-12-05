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
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="120" show-overflow-tooltip />
                <el-table-column label="提问时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column
                    label="用户提问"
                    prop="message"
                    min-width="200"
                    show-overflow-tooltip></el-table-column>
                <el-table-column label="消耗算力" width="100">
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
                <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleReply(row)"> 查看回复 </el-button>
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
import { getRecordList } from "@/api/ai_application/person_wechat/record";
import { usePaging } from "@/hooks/usePaging";
import { ElMessage, ElMessageBox } from "element-plus";

const queryParams = reactive({
    user: "", //用户信息
    message: "", //关键词
    start_date: "",
    end_date: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getRecordList,
    params: queryParams,
});

// 查看回复
const handleReply = (row: any) => {
    ElMessageBox.alert(row.reply, "回复内容", {
        showConfirmButton: false,
        showCancelButton: false,
        closeOnClickModal: false,
        closeOnPressEscape: false,
    });
};

getLists();
</script>
