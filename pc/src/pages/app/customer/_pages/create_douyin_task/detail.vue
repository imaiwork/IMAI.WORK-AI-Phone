<template>
    <div class="h-full flex flex-col rounded-[20px] bg-white w-full min-w-[1000px] border border-br overflow-hidden">
        <div class="px-8 py-5 border-b border-br-extra-light flex items-center justify-between bg-white">
            <div
                class="flex items-center gap-2 cursor-pointer group hover:text-primary transition-colors"
                @click="emit('back')">
                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-blue-50">
                    <span class="text-tx-regular group-hover:text-primary leading-[0]">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                    </span>
                </div>
                <div class="text-base font-black text-gray-950">任务详情线索</div>
            </div>

            <div class="flex items-center gap-3" v-if="detail?.status && detail?.status != 0">
                <export-data :params="queryParams" :fetch-fun="getDouyinTaskClue" :export-fun="getDouyinTaskClue">
                    <template #trigger>
                        <ElButton type="primary" class="!rounded-full !h-10 px-6 font-medium shadow-light">
                            <Icon name="el-icon-Download"></Icon>
                            <span class="ml-1">导出线索</span>
                        </ElButton>
                    </template>
                </export-data>
            </div>
        </div>

        <div class="px-8 py-6 bg-[#f9f9f9]/30">
            <div
                class="relative transition-all duration-300 overflow-hidden"
                :class="isExpanded ? 'max-h-[80px]' : 'max-h-[500px]'">
                <div class="grid grid-cols-1 gap-y-4 text-sm">
                    <div class="flex items-start">
                        <span class="w-24 text-tx-secondary font-medium">执行设备：</span>
                        <div class="flex flex-wrap gap-2 flex-1">
                            <span
                                class="px-3 py-1 rounded-lg bg-white border border-br-light text-tx-regular text-xs font-medium shadow-sm"
                                v-for="item in detail?.binds"
                                :key="item.device_code"
                                >{{ item.device_code }}</span
                            >
                        </div>
                    </div>

                    <div class="flex items-start border-t border-dashed border-br-light pt-4">
                        <span class="w-24 text-tx-secondary font-medium"
                            >{{ detail?.search_mode == SearchModeEnum.ACCOUNT ? "指定账号" : "关键词" }}({{
                                keywords.length
                            }})：</span
                        >
                        <div class="flex-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="kw in keywords"
                                :key="kw"
                                class="text-primary bg-blue-50 px-2 py-0.5 rounded text-xs font-medium border border-blue-100">
                                {{ kw }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-4 p-4 bg-white rounded-xl border border-br-extra-light">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">搜寻方式</span>
                            <span class="text-tx-regular font-medium">{{
                                detail?.search_mode == SearchModeEnum.ACCOUNT ? "指定账号搜寻" : "关键词搜寻"
                            }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">自动加微</span>
                            <span
                                :class="detail?.add_type == 1 ? 'text-green-600' : 'text-tx-placeholder'"
                                class="font-medium">
                                {{ detail?.add_type == 1 ? "● 已开启" : "○ 已关闭" }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">加微规则</span>
                            <span class="text-tx-regular font-medium">{{ getAddRuleText(detail?.add_rule) }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-tx-placeholder uppercase font-black">加微频率</span>
                            <span class="text-tx-regular font-medium"
                                >{{ detail?.add_number }}次/天 · {{ detail?.add_interval_time }}min间隔</span
                            >
                        </div>
                    </div>

                    <div class="flex items-start">
                        <span class="w-24 text-tx-secondary font-medium">加微备注：</span>
                        <div class="flex flex-wrap gap-2 flex-1">
                            <span
                                class="px-3 py-1 rounded-lg bg-gray-100 text-tx-regular text-xs"
                                v-for="item in formatRemarks(detail?.remarks)"
                                :key="item"
                                >{{ item }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-if="isExpanded"
                    class="absolute bottom-0 left-0 w-full h-12 bg-gradient-to-t from-gray-50 to-transparent pointer-events-none"></div>
            </div>

            <div
                class="flex items-center justify-center mt-4 cursor-pointer text-primary hover:text-blue-700 transition-all"
                @click="isExpanded = !isExpanded">
                <span class="text-xs font-medium mr-1">{{ !isExpanded ? "收起参数" : "展开任务参数" }}</span>
                <Icon :name="!isExpanded ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'" :size="12"></Icon>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col px-4">
            <ElTable height="100%" :data="pager.lists" v-loading="pager.loading">
                <ElTableColumn label="账号" min-width="180" fixed="left">
                    <template #default="{ row }">
                        <div class="flex items-center gap-2">
                            <ElAvatar :size="32" :src="row.avatar">
                                <span class="text-xs">{{ (row.nickname || "抖")[0] }}</span>
                            </ElAvatar>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-950 line-clamp-1">
                                    {{ row.nickname || "-" }}
                                </div>
                                <div class="text-xs text-tx-placeholder line-clamp-1">{{ row.dy_id || "-" }}</div>
                            </div>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="exec_keyword" label="匹配词" min-width="100">
                    <template #default="{ row }">
                        <span
                            class="px-2 py-0.5 bg-gray-50 rounded text-tx-secondary text-xs border border-br-extra-light"
                            >{{ row.exec_keyword || "-" }}</span
                        >
                    </template>
                </ElTableColumn>

                <ElTableColumn label="账号资料" min-width="120">
                    <template #default="{ row }">
                        <span class="text-xs text-tx-secondary">
                            粉丝{{ row.fans || 0 }} · 作品{{ row.works || 0 }}
                            <template v-if="row.is_enterprise == 1"> · 企业号</template>
                        </span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="识别位置" min-width="100">
                    <template #default="{ row }">
                        <span class="text-xs text-tx-secondary">{{ getScanSourceText(row.scan_source) }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="reg_content" label="提取信息" min-width="160">
                    <template #default="{ row }">
                        <span class="text-primary font-medium">{{ row.reg_content || "-" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="采集结果" min-width="110">
                    <template #default="{ row }">
                        <div class="status-indicator" :class="getResultClass(row.result)">
                            <span class="dot"></span>
                            {{ row.result_name || "走访中" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn prop="tokens" label="消耗算力" width="90" align="center">
                    <template #default="{ row }">
                        <span class="font-mono text-orange-600">{{ row.tokens || 0 }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn
                    prop="exec_time"
                    label="执行时间"
                    width="160"
                    class-name="text-tx-placeholder text-xs" />

                <template #empty>
                    <div class="py-20 flex flex-col items-center">
                        <ElEmpty description="暂无抓取到的线索数据" :image-size="120"></ElEmpty>
                    </div>
                </template>
            </ElTable>
        </div>

        <div class="shrink-0 h-[72px] px-8 flex items-center justify-between">
            <div class="text-xs font-medium text-[#CBD5E1]">共计 {{ pager.count }} 条线索数据</div>
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getDouyinTaskDetail, getDouyinTaskClue } from "~/api/customer";
import { RECORD_RESULT_CLASS, SCAN_SOURCE_TEXT, SearchModeEnum } from "./_enums";

const emit = defineEmits<{
    (e: "back"): void;
}>();

const query = searchQueryToObject();

const detail = ref<any>({});
const isExpanded = ref(false);

// 任务表 keywords 是 JSON 字符串，进模板前归一化
const keywords = computed(() => {
    const raw = detail.value?.keywords;
    if (Array.isArray(raw)) return raw;
    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
});

const ADD_RULE_TEXT: Record<string, string> = {
    all: "全部方式",
    phone: "手机号",
    wechat: "微信号",
    phone_first: "手机号优先",
};

const getAddRuleText = (rule: string) => {
    return ADD_RULE_TEXT[rule] || "全部方式";
};

// scan_source 是逗号拼接的识别位置（bio,dyid,cover）
const getScanSourceText = (source: string) => {
    if (!source) return "-";
    return source
        .split(",")
        .map((item) => SCAN_SOURCE_TEXT[item.trim()] || item.trim())
        .join("、");
};

const getResultClass = (result: number) => {
    return RECORD_RESULT_CLASS[result] || "";
};

const queryParams = reactive({
    task_id: query.id,
});

const { pager, getLists } = usePaging({
    fetchFun: getDouyinTaskClue,
    params: queryParams,
});

const formatRemarks = (remarks: any) => {
    if (!remarks) return [];
    if (Array.isArray(remarks)) return remarks;
    try {
        const parsed = JSON.parse(remarks);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [remarks];
    }
};

const getDetail = async () => {
    try {
        const data = await getDouyinTaskDetail({ id: query.id });
        detail.value = data;
    } catch (error) {
        detail.value = { status: 1, binds: [] };
    }
};

const init = async () => {
    await getDetail();
    await getLists();
};

onMounted(init);
</script>
<style lang="scss" scoped>
/* 采集结果指示器 */
.status-indicator {
    @apply inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium;
    .dot {
        @apply w-1.5 h-1.5 rounded-full;
    }

    &.valid {
        @apply bg-green-50 text-green-600;
        .dot {
            @apply bg-green-500;
        }
    }
    &.partial {
        @apply bg-blue-50 text-primary;
        .dot {
            @apply bg-primary;
        }
    }
}
</style>
