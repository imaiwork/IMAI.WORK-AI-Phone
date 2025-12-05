<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="flex gap-4">
                <el-card class="w-[400px]" shadow="never">
                    <div class="text-lg font-bold">
                        OEM剩余授权名额： {{ oemInfo.useauthnum }} / {{ oemInfo.authnum }} 名
                    </div>
                    <div class="flex justify-end mt-4">
                        <el-popover width="200">
                            <template #reference>
                                <el-button type="primary">前往授权</el-button>
                            </template>
                            <div class="flex justify-center items-center">
                                <img class="w-[150px] h-[150px]" src="@/assets/images/support.png" />
                            </div>
                        </el-popover>
                    </div>
                </el-card>
                <el-card class="w-[400px]" shadow="never">
                    <div class="text-lg font-bold">OEM已授权用户： {{ oemInfo.useauthnum }} / 人</div>
                    <div class="flex justify-end mt-4">
                        <el-button v-perms="['marketing.oem.auth/add']" type="primary" @click="handleAdd"
                            >授权</el-button
                        >
                    </div>
                </el-card>
            </div>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="站点域名" prop="domain" min-width="200" />
                <el-table-column label="站点LOGO" width="120">
                    <template #default="{ row }">
                        <image-contain :src="row.site_logo" width="32" height="32" radius="50%" fit="cover" />
                    </template>
                </el-table-column>
                <el-table-column label="站点ICON" width="100">
                    <template #default="{ row }">
                        <image-contain :src="row.logo_url" width="32" height="32" radius="50%"  />
                    </template>
                </el-table-column>
                <el-table-column label="站点标题" prop="name" min-width="160" />
                <el-table-column label="所属用户" prop="username" min-width="160" />
                <el-table-column label="授权时间" prop="auth_time" width="180" />
                <el-table-column label="状态" prop="auth_time" width="120">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="changeStatus(row)" />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['marketing.oem.auth/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row)">
                            删除
                        </el-button>
                        <el-button v-perms="['marketing.oem.auth/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <edit-popup
        v-if="showEdit"
        ref="editPopupRef"
        @close="showEdit = false"
        @success="
            getLists();
            getOemInfo();
        " />
</template>

<script setup lang="ts">
import { getSiteOemInfo, getOemList, changeOemStatus, deleteOem } from "@/api/marketing/oem";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

const oemInfo = reactive({
    authnum: 0,
    useauthnum: 0,
});

const { pager, getLists } = usePaging({
    fetchFun: getOemList,
});

const showEdit = ref(false);

const editPopupRef = ref();

const handleAdd = async () => {
    if (oemInfo.useauthnum >= oemInfo.authnum) {
        await feedback.msgWarning("OEM剩余授权名额不足，无法进行授权");
        return;
    }
    showEdit.value = true;
    await nextTick();
    editPopupRef.value?.open("add");
};

const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editPopupRef.value?.open("edit");
    editPopupRef.value?.setFormData(row);
};

const handleDelete = async (row: any) => {
    await feedback.confirm("确定要删除该授权吗？");
    await deleteOem({ id: row.id });
    init();
};

const changeStatus = async (row: any) => {
    try {
        await changeOemStatus({ id: row.id, status: row.status });
    } finally {
        getLists();
    }
};

const getOemInfo = async () => {
    const res = await getSiteOemInfo();
    oemInfo.authnum = res.authnum;
    oemInfo.useauthnum = res.useauthnum;
};

const init = async () => {
    await getOemInfo();
    await getLists();
};

onMounted(() => {
    init();
});
</script>

<style scoped></style>
