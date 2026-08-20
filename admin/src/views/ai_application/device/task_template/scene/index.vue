<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-2">任务类型</div>
            <div class="text-xs text-[#86909C] mb-5">
                控制前台「24h 自助任务流」与后台模板编辑添加任务节点时可选择的任务类型；关闭后不可新增，已有节点不受影响。
            </div>
            <el-table :data="items" v-loading="loading" border>
                <el-table-column prop="scene" label="场景值" width="100" />
                <el-table-column label="任务类型名称" min-width="220">
                    <template #default="{ row }">
                        <el-input v-model="row.name" maxlength="64" placeholder="请输入名称" />
                    </template>
                </el-table-column>
                <el-table-column label="允许添加" width="140">
                    <template #default="{ row }">
                        <el-switch v-model="row.allow_add" :active-value="1" :inactive-value="0" />
                    </template>
                </el-table-column>
            </el-table>
        </el-card>
    </div>
    <footer-btns>
        <el-button v-perms="['setting.autoTaskScene/setConfig']" type="primary" :loading="isLock" @click="lockSubmit">
            保存
        </el-button>
    </footer-btns>
</template>

<script setup lang="ts">
import { getAutoTaskSceneConfig, setAutoTaskSceneConfig } from "@/api/ai_application/device/auto_task_scene";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";

interface SceneItem {
    scene: number;
    name: string;
    allow_add: number;
}

const loading = ref(false);
const items = ref<SceneItem[]>([]);

const getLists = async () => {
    loading.value = true;
    try {
        const res = await getAutoTaskSceneConfig();
        const list = Array.isArray(res?.items) ? res.items : [];
        items.value = list.map((item: any) => ({
            scene: Number(item.scene),
            name: String(item.name ?? ""),
            allow_add: Number(item.allow_add) === 1 ? 1 : 0,
        }));
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    if (!items.value.length) {
        feedback.msgWarning("暂无配置可保存");
        return;
    }
    const empty = items.value.find((item) => !String(item.name || "").trim());
    if (empty) {
        feedback.msgWarning(`场景 ${empty.scene} 名称不能为空`);
        return;
    }
    await setAutoTaskSceneConfig({
        items: items.value.map((item) => ({
            scene: item.scene,
            name: String(item.name).trim(),
            allow_add: Number(item.allow_add) === 1 ? 1 : 0,
        })),
    });
    await getLists();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

getLists();
</script>
