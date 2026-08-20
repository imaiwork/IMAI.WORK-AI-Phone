<template>
    <popup
        ref="popupRef"
        width="860px"
        top="12vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Share" :size="20" />
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-gray-950 text-lg font-[1000] tracking-tight leading-none">
                            {{ parentUser.nickname || "该下级" }} 的下级
                        </span>
                        <span class="text-[11px] text-slate-400 font-bold mt-1 truncate">
                            共 {{ pager.count || 0 }} 位
                            <span v-if="parentUser.mobile" class="font-mono">· {{ parentUser.mobile }}</span>
                        </span>
                    </div>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="px-6 py-5">
                <ElTable :data="pager.lists" max-height="420" class="custom-table" v-loading="pager.loading">
                    <ElTableColumn label="用户信息" min-width="220">
                        <template #default="{ row }">
                            <div class="flex items-center gap-3">
                                <img :src="row.avatar" class="w-9 h-9 rounded-full object-cover" />
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-[1000] text-slate-800 truncate">{{ row.nickname }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold mt-0.5">{{ row.mobile }}</span>
                                </div>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="下级人数" min-width="110" align="center">
                        <template #default="{ row }">
                            <div class="flex flex-col items-center">
                                <span class="text-slate-900 font-[1000]">{{ getDescendantCount(row) }}</span>
                                <span class="text-slate-300 text-[10px] font-bold mt-0.5">含子孙</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="代理等级" min-width="110" align="center">
                        <template #default="{ row }">
                            <span class="text-xs font-bold text-slate-600">{{ levelName(row.level) }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="可用点数" min-width="100" align="center">
                        <template #default="{ row }">
                            <span class="text-slate-900 font-[1000]">{{ row.tokens || 0 }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="充值业绩" min-width="150" align="center">
                        <template #default="{ row }">
                            <div class="flex flex-col items-center">
                                <span class="text-slate-900 font-[1000]">￥{{ row.recharge_amount || 0 }}</span>
                                <button
                                    class="text-primary text-[11px] font-black mt-0.5 hover:underline"
                                    @click="emit('view-recharge', row)">
                                    {{ row.recharge_count || 0 }} 笔 · 查看流水
                                </button>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="加入时间" min-width="150" align="center">
                        <template #default="{ row }">
                            <span class="text-xs font-bold text-slate-600">
                                {{ typeof row.become_time === "string" ? row.become_time : "--" }}
                            </span>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <div class="py-14 flex flex-col items-center">
                            <ElEmpty :image-size="90" description="该下级暂无下级" />
                        </div>
                    </template>
                </ElTable>

                <div class="flex justify-end mt-4">
                    <pagination v-model="pager" layout="prev, pager, next" @change="getLists" />
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getAgentSubList } from "@/api/user";

const props = defineProps<{
    agentLevel: any[];
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "view-recharge", row: any): void;
}>();

const popupRef = ref();

const parentUser = reactive({
    user_id: 0,
    nickname: "",
    mobile: "",
});

const queryParams = reactive({
    user_id: 0,
    status: 1,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: getAgentSubList,
    params: queryParams,
});

const getDescendantCount = (row: any) => Number(row?.descendant_count ?? row?.sub_count ?? 0);

const levelName = (level: number) =>
    props.agentLevel.find((item: any) => item.level == level)?.name || "普通用户";

const open = (row: any) => {
    parentUser.user_id = row?.user_id ?? 0;
    parentUser.nickname = row?.nickname ?? "";
    parentUser.mobile = row?.mobile ?? "";
    queryParams.user_id = parentUser.user_id;

    popupRef.value?.open();
    resetPage();
};

const close = () => {
    popupRef.value?.close();
    emit("close");
};

defineExpose({ open, close });
</script>
