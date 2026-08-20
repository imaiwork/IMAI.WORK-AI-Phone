<template>
    <ElDialog
        v-model="show"
        width="860px"
        append-to-body
        :show-close="false"
        style="border-radius: 16px; overflow: hidden; padding: 0"
        @close="$emit('close')">
        <div class="flex flex-col" style="height: 70vh">
            <div class="px-7 pt-6 pb-4 flex items-start justify-between border-b border-br">
                <div>
                    <div class="text-[18px] font-[900] text-[#1E293B]">我的CDK</div>
                    <div class="text-[13px] text-[#94A3B8] mt-1">
                        共 {{ pager.count || pager.lists.length }} 张 · 未使用 {{ unusedCount }} 张
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <ElButton type="primary" class="!rounded-xl" @click="$emit('purchase')">
                        <Icon name="local-icon-add_circle" :size="16" />
                        <span class="ml-1">购买CDK</span>
                    </ElButton>
                    <div
                        class="w-8 h-8 rounded-lg hover:bg-[#F1F5F9] flex items-center justify-center cursor-pointer"
                        @click="show = false">
                        <Icon name="el-icon-Close" color="#94A3B8" :size="18" />
                    </div>
                </div>
            </div>

            <div class="grow min-h-0 px-7 py-4">
                <ElTable v-loading="pager.loading" :data="pager.lists" height="100%">
                    <ElTableColumn label="CDK" min-width="220" show-overflow-tooltip>
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-[13px] text-[#1E293B]">{{ getCode(row) }}</span>
                                <span
                                    class="inline-flex cursor-pointer text-[#94A3B8] hover:text-primary"
                                    @click="copy(getCode(row))">
                                    <Icon name="local-icon-copy" :size="14" />
                                </span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="套餐" min-width="100">
                        <template #default="{ row }">{{
                            row.type_desc ||
                            row.auth_type_name ||
                            row.plan_name ||
                            row.name ||
                            getPlanTypeLabel(row.type ?? row.auth_type) ||
                            "-"
                        }}</template>
                    </ElTableColumn>
                    <ElTableColumn label="状态" width="100">
                        <template #default="{ row }">
                            <span
                                class="px-2 py-0.5 rounded-full text-xs font-bold"
                                :class="isUsed(row) ? 'bg-[#F1F5F9] text-[#94A3B8]' : 'bg-[#ECFDF5] text-[#10B981]'">
                                {{ row.status_desc || (isUsed(row) ? "已使用" : "未使用") }}
                            </span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="购买时间" min-width="160">
                        <template #default="{ row }">
                            {{ row.purchase_time || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="使用设备" min-width="160">
                        <template #default="{ row }">{{ row.device_code || "-" }}</template>
                    </ElTableColumn>
                    <ElTableColumn label="使用时间" min-width="160">
                        <template #default="{ row }">{{ row.use_time || "-" }}</template>
                    </ElTableColumn>
                </ElTable>
            </div>

            <div class="shrink-0 h-[60px] px-7 flex items-center justify-end border-t border-br">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { getDeviceAuthMyCodes } from "@/api/device_auth";
import { getPlanTypeLabel } from "../_enums/deviceAuthEnums";

defineEmits<{
    (e: "close"): void;
    (e: "purchase"): void;
}>();

const show = ref(true);
const { copy } = useCopy();

const { pager, getLists } = usePaging({
    fetchFun: getDeviceAuthMyCodes,
});

const getCode = (row: any) => row.code || row.card_no || row.card_code || row.sn || "-";

const isUsed = (row: any) => {
    const value = row.status ?? row.use_status ?? row.used_status;
    if (typeof value === "number") return value === 1;
    if (typeof value === "string") return ["1", "used", "已使用", "已激活"].includes(value);
    return !!row.use_time;
};

const unusedCount = computed(() => pager.lists.filter((row: any) => !isUsed(row)).length);

getLists();
</script>
