<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="媒体名称">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.name"
                        placeholder="请输入媒体名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="渠道类型">
                    <el-select
                        class="!w-[170px]"
                        v-model="queryParams.type"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option v-for="t in options.types" :key="t.value" :label="t.label" :value="t.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.status"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="上架" value="1" />
                        <el-option label="下架" value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between">
                <el-button v-perms="['geo.media/add']" type="primary" @click="handleAdd">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增媒体
                </el-button>
            </div>
            <el-table size="large" class="mt-4" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="70" />
                <el-table-column label="媒体名称" prop="name" min-width="130" show-overflow-tooltip />
                <el-table-column label="渠道类型" prop="type_text" min-width="130" />
                <el-table-column label="渠道标识" prop="provider_code" min-width="110">
                    <template #default="{ row }">{{ row.provider_code || "-" }}</template>
                </el-table-column>
                <el-table-column label="授权平台" prop="platform_code" min-width="100">
                    <template #default="{ row }">{{ row.platform_code || "-" }}</template>
                </el-table-column>
                <el-table-column label="投稿类型" min-width="100">
                    <template #default="{ row }">{{ formText(row.content_form) }}</template>
                </el-table-column>
                <el-table-column label="PC权重" prop="pc_weight" width="80" />
                <el-table-column label="移动权重" prop="mobile_weight" width="90" />
                <el-table-column label="成功率" width="80">
                    <template #default="{ row }">{{ row.success_rate }}%</template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="70" />
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['geo.media/status']"
                            @change="changeStatus(row)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['geo.media/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button v-perms="['geo.media/delete']" type="danger" link @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <edit-popup v-if="showEdit" ref="editRef" :options="options" @success="getLists" @close="showEdit = false" />
    </div>
</template>

<script lang="ts" setup name="geoMedia">
import { usePaging } from "@/hooks/usePaging";
import { getGeoMediaList, getGeoMediaOptions, setGeoMediaStatus, deleteGeoMedia } from "@/api/marketing/geo";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

const editRef = shallowRef<InstanceType<typeof EditPopup>>();
const queryParams = reactive({
    name: "",
    type: "",
    status: "",
});
const showEdit = ref(false);
const options = ref<any>({ types: [], auth_platforms: [], phone_platforms: [], content_forms: [] });

const formText = (form: string) => {
    const found = (options.value.content_forms || []).find((f: any) => f.value === form);
    return found?.label || form || "-";
};

const loadOptions = async () => {
    try {
        options.value = await getGeoMediaOptions();
    } catch {
        // 无 options 权限时下拉退化为手输,列表仍可用
    }
};

const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("add");
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("edit");
    editRef.value?.setFormData(row);
};

const handleDelete = async (row: any) => {
    await feedback.confirm(`确定删除媒体「${row.name}」?用户端投稿列表将不再展示该渠道。`);
    await deleteGeoMedia({ id: row.id });
    getLists();
};

const changeStatus = async (row: any) => {
    try {
        await setGeoMediaStatus({ id: row.id, status: row.status });
    } finally {
        getLists();
    }
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getGeoMediaList,
    params: queryParams,
});

loadOptions();
getLists();
</script>
