<template>
    <el-drawer v-model="show" title="人设分析报告" width="80%">
        <div class="h-full overflow-y-auto p-4">
            <div v-if="loading" class="flex items-center justify-center h-40">
                <el-icon class="is-loading text-2xl text-gray-400">
                    <Loading />
                </el-icon>
                <span class="ml-2 text-gray-400 text-sm">报告生成中...</span>
            </div>

            <template v-else-if="reportData">
                <IndividualReport v-if="reportType === 1" :reportData="reportData" />
                <EnterpriseReport v-else-if="reportType === 2" :reportData="reportData" />
                <LocalReport v-else-if="reportType === 3" :reportData="reportData" />
            </template>

            <div v-else class="text-center text-gray-400 text-sm py-10">暂无报告数据</div>
        </div>
    </el-drawer>
</template>

<script setup lang="ts">
import { Loading } from "@element-plus/icons-vue";
import { getPersonDetail } from "@/api/ai_application/device/person";

// 子报告组件（三种类型）
import EnterpriseReport from "./enterprise.vue";
import IndividualReport from "./individual.vue";
import LocalReport from "./local.vue";

const show = ref(false);
const loading = ref(false);
const emit = defineEmits(["close"]);

const personId = ref<number>();
const reportType = ref<number>();
const reportData = ref<any>(null);

const getReport = async () => {
    if (!personId.value) return;
    loading.value = true;
    try {
        const res = await getPersonDetail({ id: personId.value });
        // 根据实际接口结构调整取值路径
        reportType.value = res.persona_type;
        if (reportType.value === 1) {
            reportData.value = res.report_content.individual;
        } else if (reportType.value === 2) {
            reportData.value = res.report_content.enterprise;
        } else if (reportType.value === 3) {
            reportData.value = res.report_content.local;
        }
    } catch (e) {
        console.error("获取报告失败", e);
    } finally {
        loading.value = false;
    }
};

const open = (id: number) => {
    personId.value = id;
    show.value = true;
    getReport();
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({ open, close });
</script>

<style scoped></style>
