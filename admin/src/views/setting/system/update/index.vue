<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-alert class="text-xxl" type="warning" :closable="false" show-icon>
                <template v-slot:title>
                    <div class="py-[3px]">温馨提示：</div>
                    <div class="py-[3px]">
                        1.版本更新需要逐个版本更新，
                        <span class="text-[#f56c6c]"> 更新前请备份好系统和数据库，更新成功后需要强制刷新站点； </span>
                    </div>
                    <div class="py-[3px]">2.系统没有做二次开发，可以直接选择在线更新功能；</div>
                    <div class="py-[3px]">
                        3.系统已做二次开发，进行了功能修改，请谨慎选择在线更新功能，推荐以更新包的形式手动更新；
                    </div>
                    <div class="py-[3px]">
                        4.检查服务器文件权限是否都是777，如果权限不是777，请手动设置权限为777；否则会导致更新失败；
                    </div>
                </template>
            </el-alert>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never" v-loading="pager.loading">
            <div>
                <el-table :data="pager.lists" size="large" border>
                    <el-table-column prop="date" label="发布日期" min-width="180px" />
                    <el-table-column label="版本号" min-width="160px">
                        <template #default="{ row, $index }">
                            <div class="flex items-center">
                                <div class="mr-2">
                                    {{ row.name }}
                                </div>
                                <el-tag v-if="$index == 0 && pager.page == 1" size="small" type="primary">
                                    最新
                                </el-tag>
                                <el-tag v-else-if="config.version_number == row.version" size="small" type="warning">
                                    当前
                                </el-tag>
                            </div>
                            <div class="" v-if="config.version_number == row.version">您的系统当前处于此版本</div>
                            <div class="" v-else-if="config.version_number < row.version">系统可更新至此版本</div>
                        </template>
                    </el-table-column>
                    <el-table-column prop="" label="版本内容" min-width="220px">
                        <template #default="{ row }">
                            <div class="whitespace-pre-line">
                                {{ row.content }}
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="160px" fixed="right">
                        <template #default="{ row }">
                            <div class="operation flex flex-wrap">
                                <div v-perms="['setting.system.upgrade/upgrade']">
                                    <el-button
                                        v-if="row.version > config.version_number"
                                        type="primary"
                                        link
                                        @click="onOuterVisible(row.version)">
                                        一键更新
                                    </el-button>
                                </div>
                                <el-button type="primary" link @click="downloadUpdate(row.version)">
                                    下载更新包
                                </el-button>
                            </div>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <div class="flex mt-4 justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <el-dialog v-model="state.outerVisible" width="400px" title="系统是否进行二次开发" center>
            <div class="flex flex-col justify-between items-center">
                <div class="flex flex-col pb-8">
                    <div>
                        <el-button class="w-full" type="primary" @click="notSecondaryDev">
                            未做二次开发，直接更新
                        </el-button>
                    </div>
                    <div class="mt-2.5">
                        <el-button class="w-full" @click="secondaryDev">已做二次开发</el-button>
                    </div>
                    <div class="mt-2.5">
                        <el-button class="w-full" @click="state.outerVisible = false"> 取消更新 </el-button>
                    </div>
                </div>
            </div>
        </el-dialog>
        <el-dialog width="400px" v-model="state.innerVisible" append-to-body center>
            <div>
                <div v-if="state.isSecondaryDev == false" style="text-align: center">
                    一键更新导致的系统问题，请做好系统备份！
                </div>
                <div v-else style="text-align: center">
                    <div>二次开发后请谨慎使用一键更新功能！</div>
                    <div>二次开发后一键更新导致的系统问题，官方无法处理，请做好系统备份！</div>
                </div>
            </div>
            <template v-slot:footer>
                <div>
                    <el-button type="primary" @click="confirmUpdate"> 确定更新 </el-button>
                    <el-button @click="state.innerVisible = false"> 取消更新 </el-button>
                </div>
            </template>
        </el-dialog>
        <el-dialog
            width="300px"
            v-model="state.threeVisible"
            append-to-body
            title="更新中"
            center
            :close-on-click-modal="false"
            :close-on-press-escape="false">
            <div class="pt-10" style="height: 200px; text-align: center">
                <div
                    v-loading="state.loading"
                    element-loading-text="系统更新中，更新完毕后会自行刷新页面 切勿关闭窗口或刷新页面" />
            </div>
        </el-dialog>
    </div>
</template>
<script setup lang="ts">
import { getUpgradeLists, upgrade } from "@/api/setting/update";
import { useLockFn } from "@/hooks/useLockFn";
import { usePaging } from "@/hooks/usePaging";
import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";

const config = computed(() => useAppStore().config?.version || {});

const state = reactive({
    version: "",
    outerVisible: false,
    loading: false,
    isSecondaryDev: false,
    threeVisible: false,
    innerVisible: false,
});

const { pager, getLists } = usePaging({
    fetchFun: getUpgradeLists,
});

const onOuterVisible = (version: any) => {
    const index = pager.lists.findIndex((item) => item.version === version);
    const currVersionIndex = pager.lists.findIndex((item) => item.version == config.value.version_number);
    if (currVersionIndex - index !== 1) {
        feedback.msgWarning("当前版本落后几个版本，只可以向上依次更新版本");
        return;
    }
    state.outerVisible = true;
    state.version = version;
};

const downloadUpdate = (version: any) => {
    window.open(`https://update.imai.work/update/version/${version}.zip`, "_blank");
};

const confirmUpdate = () => {
    state.innerVisible = false;
    state.threeVisible = true;
    systemUpgrade();
};

// 未做二次开发
const notSecondaryDev = () => {
    state.outerVisible = false;
    state.isSecondaryDev = false;
    state.innerVisible = true;
};
// 已做二次开发
const secondaryDev = () => {
    state.outerVisible = false;
    state.isSecondaryDev = true;
    state.innerVisible = true;
};

const { lockFn: systemUpgrade } = useLockFn(async () => {
    try {
        state.loading = true;
        await upgrade({
            version: state.version,
        });
        window.location.reload();
    } catch (error: any) {
        feedback.msgError(error || "更新失败!");
    }
    state.threeVisible = false;
    state.innerVisible = false;
    state.outerVisible = false;
});

onMounted(() => {
    getLists();
});
</script>
