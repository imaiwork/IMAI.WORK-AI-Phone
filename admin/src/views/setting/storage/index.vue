<template>
    <div class="storage">
        <el-card class="!border-none" shadow="never">
            <el-alert
                type="warning"
                title="温馨提示：1.切换存储方式后，需要将资源文件传输至新的存储端；2.请勿随意切换存储方式，可能导致图片无法查看"
                :closable="false"
                show-icon></el-alert>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never" v-loading="state.loading">
            <el-table size="large" :data="state.lists">
                <el-table-column label="储存方式" prop="name" min-width="120" />
                <el-table-column label="储存位置" prop="path" min-width="160" />
                <el-table-column label="状态" min-width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.status == 1">开启</el-tag>
                        <el-tag type="danger" v-else>关闭</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="迁移状态" min-width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.migration == 1">迁移中</el-tag>
                        <el-tag type="success" v-else-if="row.migration == 2">迁移成功</el-tag>
                        <el-tag type="danger" v-else>未迁移</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" min-width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['setting.storage/setup']"
                            type="primary"
                            link
                            @click="handleSet(row.engine)">
                            设置
                        </el-button>
                        <el-button
                            v-if="row.status == 1 && row.engine != 'local' && row.migration != 1"
                            v-perms="['setting.storage/upload']"
                            type="primary"
                            link
                            @click="handleUpload(row)">
                            上传本地文件
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>
        <edit-popup ref="editRef" @success="getLists" />
    </div>
</template>
<script lang="ts" setup name="storage">
import { storageLists, storageMigration } from "@/api/setting/storage";
import EditPopup from "./edit.vue";
const editRef = shallowRef<InstanceType<typeof EditPopup>>();

// 列表数据
const state = reactive({
    loading: false,
    lists: [],
});

// 获取存储引擎列表数据
const getLists = async () => {
    try {
        state.loading = true;
        state.lists = await storageLists();
        state.loading = false;
    } catch (error) {
        state.loading = false;
    }
};

const handleSet = (engine: string) => {
    editRef.value?.open(engine);
};

const handleUpload = async (row: any) => {
    await storageMigration({
        status: row.status,
        engine: row.engine,
        migration: 1,
    });
    getLists();
};

getLists();
</script>
