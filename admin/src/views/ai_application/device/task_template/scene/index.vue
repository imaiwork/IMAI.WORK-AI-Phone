<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-2">任务类型</div>
            <div class="text-xs text-[#86909C] mb-5">
                控制前台「24h 自助任务流」与后台模板编辑添加任务节点时可选择的任务类型；关闭后不可新增。
                「开放平台」按平台单独控制 24h 任务生成；关闭某平台后会从已有节点剥离该平台，视频发布结束时间按剩余平台数重算（每平台 10 分钟）。任务类型关闭时平台开关不生效。
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
                <el-table-column label="开放平台" min-width="320">
                    <template #default="{ row }">
                        <div v-if="row.allow_platforms.length" class="flex flex-wrap items-center gap-x-4">
                            <el-checkbox
                                v-for="platform in row.allow_platforms"
                                :key="platform.account_type"
                                v-model="platform.status"
                                :true-value="1"
                                :false-value="0"
                                :disabled="Number(row.allow_add) !== 1">
                                {{ platformName(platform.account_type) }}
                            </el-checkbox>
                        </div>
                        <span v-else class="text-[#86909C]">-</span>
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
import { AppTypeEnum } from "@/enums/appEnums";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";

interface PlatformItem {
    account_type: number;
    status: number;
}

interface SceneItem {
    scene: number;
    name: string;
    allow_add: number;
    allow_platforms: PlatformItem[];
}

/** account_type → 平台显示名称，与后端 DeviceEnum::getAccountTypeDesc 一致 */
const accountTypeNameMap: Record<number, string> = {
    [AppTypeEnum.SPH]: "视频号",
    [AppTypeEnum.XHS]: "小红书",
    [AppTypeEnum.DOUYIN]: "抖音",
    [AppTypeEnum.KUAISHOU]: "快手",
};

const platformName = (accountType: number) => accountTypeNameMap[accountType] ?? `平台${accountType}`;

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
            allow_platforms: Array.isArray(item.allow_platforms)
                ? item.allow_platforms.map((platform: any) => ({
                      account_type: Number(platform.account_type),
                      status: Number(platform.status) === 1 ? 1 : 0,
                  }))
                : [],
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
        items: items.value.map((item) => {
            const payload: Record<string, any> = {
                scene: item.scene,
                name: String(item.name).trim(),
                allow_add: Number(item.allow_add) === 1 ? 1 : 0,
            };
            // 接口未下发平台开关时不回传该字段，避免被后端当作「全部平台关闭」
            if (item.allow_platforms.length) {
                payload.allow_platforms = item.allow_platforms.map((platform) => ({
                    account_type: platform.account_type,
                    status: Number(platform.status) === 1 ? 1 : 0,
                }));
            }
            return payload;
        }),
    });
    await getLists();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

getLists();
</script>
