<template>
    <popup
        ref="popupRef"
        width="760px"
        class="consume-detail-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div
                    class="w-11 h-11 rounded-2xl grid place-items-center shrink-0"
                    style="
                        background: linear-gradient(135deg, #0065fb, #4f9dff);
                        box-shadow: 0 8px 20px -6px rgba(0, 101, 251, 0.45);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-6 h-6">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 13h6M9 17h4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[17px] font-[800] text-slate-900 leading-tight">{{ consumeMemberName }}</h3>
                    <p class="text-[12px] text-slate-400 mt-1">积分明细 · 共 {{ consumptionPager.count }} 条</p>
                </div>
            </div>
            <button class="cd-close" @click="showConsumption = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
        <ElTable
            :data="consumptionPager.lists"
            v-loading="consumptionPager.loading"
            max-height="460"
            class="consume-table">
            <ElTableColumn label="项目" prop="change_type_desc" min-width="160" show-overflow-tooltip />
            <ElTableColumn label="收支" width="90">
                <template #default="{ row }">
                    <ElTag :type="row.action === 2 ? 'danger' : 'success'" effect="light" size="small">
                        {{ row.action === 2 ? "消耗" : "获得" }}
                    </ElTag>
                </template>
            </ElTableColumn>
            <ElTableColumn label="变动算力" min-width="110" align="right">
                <template #default="{ row }">
                    <span class="font-bold" :class="row.action === 2 ? 'text-amber-500' : 'text-emerald-500'">
                        {{ row.action === 2 ? "-" : "+" }}{{ formatNum(row.change_amount) }}
                    </span>
                </template>
            </ElTableColumn>
            <ElTableColumn label="剩余" prop="left_tokens" min-width="100" align="right" />
            <ElTableColumn label="备注" prop="remark" min-width="160" show-overflow-tooltip />
            <ElTableColumn label="时间" prop="create_time" min-width="150" />
            <template #empty><ElEmpty description="暂无消耗记录" /></template>
        </ElTable>
        <div class="flex justify-end mt-4">
            <pagination v-model="consumptionPager" @change="getConsumptionLists" />
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";
import { formatNum } from "../_composables/helpers";

const { consumption } = useTeamContext();
const { showConsumption, consumeMemberName, consumptionPager, getConsumptionLists } = consumption;
const { popupRef, onPopupClose } = usePopupBridge(showConsumption);
</script>
