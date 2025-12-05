<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="模型名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入模型名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="模型描述">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.description"
                        placeholder="请输入模型描述"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="所属类目">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.scene_name"
                        placeholder="请输入所属类目名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="模型状态">
                    <el-select class="!w-[120px]" v-model="queryParams.status" :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="开启" :value="1" />
                        <el-option label="关闭" :value="0" />
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
            <div class="flex justify-between">
                <router-link v-perms="['ai_assistant.model/add']" :to="getRoutePath('ai_assistant.model/add:edit')">
                    <el-button type="primary">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增行业模型
                    </el-button>
                </router-link>
                <div>
                    <div v-perms="['ai_assistant.model/edit']">
                        小程是否可见
                        <el-switch
                            v-model="isShowRobot"
                            inline-prompt
                            active-value="1"
                            inactive-value="0"
                            active-text="是"
                            inactive-text="否"
                            @change="changeShowRobot" />
                    </div>
                    <el-button
                        v-if="isImport"
                        v-perms="['ai_assistant.model/import']"
                        type="primary"
                        @click="handleImport"
                        >导入模型</el-button
                    >
                </div>
            </div>
            <el-table size="large" class="mt-4" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="图标" min-width="100">
                    <template #default="{ row }">
                        <el-image :src="row.logo" class="w-[44px] h-[44px]" />
                    </template>
                </el-table-column>
                <el-table-column label="模型名称" prop="name" min-width="120" />
                <el-table-column label="模型描述" prop="description" min-width="150" show-overflow-tooltip />
                <el-table-column label="所属类目" prop="scene_name" min-width="140" />
                <el-table-column label="状态" min-width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['ai_assistant.model/status']"
                            @change="changeStatus(row.id)"
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" min-width="120" />
                <el-table-column label="创建时间" prop="create_time" show-overflow-tooltip min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link>
                            <router-link
                                v-perms="['ai_assistant.model/edit']"
                                :to="{
                                    path: getRoutePath('ai_assistant.model/add:edit'),
                                    query: {
                                        id: row.id,
                                    },
                                }">
                                编辑
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_assistant.model/delete']"
                            type="danger"
                            link
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
        <imports ref="importsRef" @success="getLists" />
    </div>
</template>
<script lang="ts" setup>
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import { saveConfig } from "@/api/app";
import {
    getAssistantModelList,
    assistantModelStatus,
    assistantModelDelete,
    assistantModelImport,
    assistantModelImportCheck,
} from "@/api/ai_assistant/model";
import feedback from "@/utils/feedback";
import useAppStore from "@/stores/modules/app";
import Imports from "./components/imports.vue";

const appStore = useAppStore();

const isShowRobot = computed({
    get() {
        return appStore.config.is_robot_show;
    },
    set(value) {
        appStore.config.is_robot_show = value;
    },
});

const importsRef = shallowRef();

//搜索参数
const queryParams = reactive({
    name: "",
    description: "",
    scene_name: "",
    start_time: "",
    end_time: "",
    status: "",
});

//删除
const handleDelete = async (id: number) => {
    await feedback.confirm("确定要删除？");
    await assistantModelDelete({ id });
    getLists();
};

//修改状态
const changeStatus = async (id: any) => {
    try {
        await assistantModelStatus({ id });
    } finally {
        getLists();
    }
};

//修改小程是否可见
const changeShowRobot = async () => {
    await saveConfig({
        data: isShowRobot.value,
        type: "assistants",
        name: "is_robot_show",
    });
};

//导入
const handleImport = async () => {
    await feedback.confirm("确定要导入？");
    feedback.loading("导入中...");

    try {
        await assistantModelImport();
        getLists();
        feedback.msgSuccess("导入成功");
    } finally {
        feedback.closeLoading();
    }
};

//导入检查
const isImport = ref(false);
const importCheck = async () => {
    const data = await assistantModelImportCheck();
    isImport.value = data.import;
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getAssistantModelList,
    params: queryParams,
});

importCheck();
getLists();
</script>
