<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <el-form class="ls-form" :model="formData" label-width="140px">
                <div class="text-xl font-medium mb-[20px]">功能状态</div>
                <el-form-item label="百度云平敏感词库" prop="name">
                    <div>
                        <el-switch
                            v-perms="['ai_setting.sensitive.word/edit']"
                            :active-value="1"
                            :inactive-value="0"
                            v-model="formData.is_sensitive"
                            @change="handleSubmit" />
                        <div class="form-tips">过滤百度云平敏感词，默认开启</div>
                    </div>
                </el-form-item>
                <el-form-item label="自定义敏感词库" prop="name">
                    <div>
                        <el-switch
                            v-perms="['ai_setting.sensitive.word/edit']"
                            :active-value="1"
                            :inactive-value="0"
                            v-model="formData.is_sensitive_system"
                            @change="handleSubmit" />
                        <div class="form-tips">过滤自定义敏感词，默认开启</div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card shadow="never" class="!border-none mt-4">
            <el-form ref="formRef" class="mb-[16px]" :model="queryParams" :inline="true">
                <el-form-item label="关键词">
                    <el-input class="w-[280px]" v-model="queryParams.keyword" placeholder="请输入" clearable />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select class="!w-[140px]" v-model="queryParams.status" :empty-values="[null, undefined]">
                        <el-option value="" label="全部"></el-option>
                        <el-option :value="1" label="已开启"></el-option>
                        <el-option :value="0" label="已关闭"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
            <el-button v-perms="['ai_setting.sensitive.word/add']" type="primary" @click="handleEdit('add')">
                自定义敏感词
            </el-button>
            <el-table size="large" v-loading="pager.loading" :data="pager.lists" class="mt-4">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="敏感词" prop="word" min-width="280" />
                <el-table-column label="状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag type="success" v-if="row.status == 1">已开启</el-tag>
                        <el-tag type="danger" v-if="row.status == 0">已关闭</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" fixed="right" width="120">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['ai_setting.sensitive.word/edit']"
                            link
                            type="primary"
                            @click="handleEdit('edit', row)"
                            >编辑
                        </el-button>
                        <el-button
                            v-perms="['ai_setting.sensitive.word/delete']"
                            link
                            type="danger"
                            @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <editPop ref="editPopRef" v-if="showEdit" @close="handleClose" />
    </div>
</template>
<script setup lang="ts" name="sensitive">
import { usePaging } from "@/hooks/usePaging";
import { getSensitiveLists, delSensitive, getSensitiveConfig, setSensitiveConfig } from "@/api/ai_setting/sensitive";
import EditPop from "./edit.vue";
import feedback from "@/utils/feedback";

const queryParams = reactive({
    keyword: "",
    status: "",
});
const formData = ref({
    is_sensitive: 1,
    is_sensitive_system: 1,
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getSensitiveLists,
    params: queryParams,
});
getLists();

//弹框ref
const editPopRef = shallowRef<InstanceType<typeof EditPop>>();
//打开弹框
const showEdit = ref(false);
const handleEdit = async (type: any, row?: any) => {
    showEdit.value = true;
    await nextTick();
    editPopRef.value?.open(type);
    if (row) {
        editPopRef.value?.setFormData(row);
    }
};
const handleClose = () => {
    showEdit.value = false;
    getLists();
};
//删除
const handleDelete = async (id: number) => {
    await feedback.confirm("确定删除？");
    await delSensitive({ id });
    getLists();
};

const getSensitive = async () => {
    formData.value = await getSensitiveConfig();
};

const handleSubmit = async () => {
    try {
        await setSensitiveConfig(formData.value);
    } finally {
        getSensitive();
    }
};
getSensitive();
</script>
