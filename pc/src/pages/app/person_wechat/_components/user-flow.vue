<template>
    <div class="w-full h-full bg-[#FBFCFD] py-6">
        <ElScrollbar>
            <div class="flex flex-col gap-y-6 px-6">
                <div
                    class="flex items-center justify-between bg-white p-5 rounded-[20px] border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-x-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#0065fb]/5 flex items-center justify-center">
                            <img src="@/assets/images/7_day.png" class="w-7 h-7" />
                        </div>
                        <div class="flex flex-col">
                            <div class="text-[14px] text-slate-500 font-medium mb-1">当前停留时长统计</div>
                            <div class="text-[14px] text-slate-700">
                                流程停留
                                <span class="text-primary font-[900] mx-0.5 text-[16px]">{{
                                    flowData.stay_flow_day
                                }}</span>
                                天
                                <span class="mx-3 text-slate-200">|</span>
                                阶段停留
                                <span class="text-primary font-[900] mx-0.5 text-[16px]">{{
                                    flowData.stay_stage_day
                                }}</span>
                                天
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-400 font-medium uppercase tracking-wider">
                        Real-time Tracking
                    </div>
                </div>

                <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden flex flex-col">
                    <div class="h-[64px] flex items-center justify-between px-6 border-b border-slate-50 bg-white">
                        <div class="flex items-center gap-x-3">
                            <div class="w-2 h-6 rounded-full bg-primary shadow-sm shadow-[#0065fb]/30"></div>
                            <div class="flex items-center gap-2">
                                <span class="text-[16px] font-medium text-slate-800 tracking-tight">{{
                                    flowData.flow_name
                                }}</span>
                                <span class="px-2 py-0.5 bg-[#0065fb]/10 text-primary text-[10px] rounded-md font-black"
                                    >执行中</span
                                >
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-400 text-xs font-medium">
                            <Icon name="el-icon-Calendar" :size="14" />
                            进入流程日期：{{ flowData.join_flow_time || "-" }}
                        </div>
                    </div>

                    <div class="grow min-h-0 w-full p-10 overflow-x-auto no-scrollbar">
                        <div class="flex items-center min-w-max px-4">
                            <template v-for="(item, index) in flowData.stage" :key="index">
                                <div class="flex items-center shrink-0">
                                    <div class="flex flex-col items-center gap-[16px] relative group">
                                        <div
                                            class="node-circle-progress shadow-light"
                                            :class="[
                                                flowData.stage_id > item.stage_id
                                                    ? 'is-finished'
                                                    : flowData.stage_id == item.stage_id
                                                    ? 'is-current'
                                                    : 'is-pending',
                                            ]">
                                            <Icon
                                                v-if="flowData.stage_id > item.stage_id"
                                                name="el-icon-Check"
                                                :size="18"
                                                color="#ffffff" />
                                            <div v-else class="inner-dot-progress"></div>

                                            <span class="step-badge">{{ index + 1 }}</span>
                                        </div>

                                        <div
                                            class="stage-name-tag"
                                            :class="{ 'is-active': flowData.stage_id == item.stage_id }">
                                            {{ item.sub_stage_name }}
                                        </div>

                                        <div
                                            v-if="flowData.stage_id == item.stage_id"
                                            class="absolute -top-8 animate-bounce">
                                            <div
                                                class="px-2 py-1 bg-primary text-white text-[10px] rounded-md font-medium shadow-lg shadow-[#0065fb]/20 relative">
                                                当前位置
                                                <div
                                                    class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-primary rotate-45"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="index < flowData.stage.length - 1"
                                        class="connector-line-progress"
                                        :class="{ 'is-active': flowData.stage_id > item.stage_id }">
                                        <div class="arrow"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>
<script setup lang="ts">
const props = defineProps<{
    flowData: Record<string, any>;
}>();
</script>
<style scoped lang="scss">
.node-circle-progress {
    @apply w-[48px] h-[48px] rounded-full bg-white border-[3px] flex items-center justify-center relative z-10 transition-all duration-300;

    .inner-dot-progress {
        @apply w-[10px] h-[10px] rounded-full transition-all;
    }

    &.is-finished {
        @apply border-emerald-100 bg-emerald-500;
        .inner-dot-progress {
            @apply bg-white;
        }
    }

    &.is-current {
        @apply border-[#0065fb]/20 bg-white ring-4 ring-[#0065fb]/10;
        .inner-dot-progress {
            @apply bg-primary scale-125;
        }
    }

    &.is-pending {
        @apply border-slate-50 bg-white;
        .inner-dot-progress {
            @apply bg-slate-200;
        }
    }
}

.step-badge {
    @apply absolute -top-1 -right-1 w-[18px] h-[18px] text-[10px] rounded-full flex items-center justify-center font-medium shadow-light;
    background: #1e293b;
    color: white;
}

.stage-name-tag {
    @apply text-[13px] font-medium text-slate-500 bg-[#f8fafc]/50 px-[12px] py-[4px] rounded-[10px] border border-slate-100 transition-all;

    &.is-active {
        @apply text-primary bg-[#0065fb]/5 border-[#0065fb]/20 font-black scale-105;
    }
}

.connector-line-progress {
    @apply w-[80px] h-[2px] bg-slate-100 mx-[4px] relative;

    &::after {
        content: "";
        @apply absolute inset-0 bg-gradient-to-r from-emerald-400 to-emerald-500 scale-x-0 origin-left transition-transform duration-700;
    }

    &.is-active::after {
        @apply scale-x-100;
    }

    .arrow {
        @apply absolute right-0 top-1/2 -translate-y-1/2 w-[6px] h-[6px] border-t-2 border-r-2 border-slate-200 rotate-45;
    }

    &.is-active .arrow {
        @apply border-emerald-500;
    }
}

.animate-bounce {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}
</style>
