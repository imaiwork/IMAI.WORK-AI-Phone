<template>
    <section class="space-y-5">
        <!-- 组织基础信息 -->
        <div class="panel">
            <div class="flex items-start gap-5">
                <div
                    class="w-16 h-16 rounded-2xl grid place-items-center text-white text-2xl font-[1000] shrink-0"
                    style="background: linear-gradient(135deg, #0065fb, #4f9dff)">
                    {{ (info.name || "T").slice(0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-[1000] text-slate-900 truncate">{{ info.name }}</h2>
                        <button
                            v-if="isOwner"
                            class="w-7 h-7 grid place-items-center rounded-lg text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors"
                            title="修改名称"
                            @click="onRename">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="w-4 h-4">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.86 4.49a1.88 1.88 0 112.65 2.65L7.5 19.14l-3.75 1.11 1.11-3.75L16.86 4.49z" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-2 xl:grid-cols-4 gap-x-8 gap-y-3 mt-4 text-sm">
                        <div>
                            <div class="text-slate-400 mb-1">超级管理员</div>
                            <div class="font-bold text-slate-700 truncate">{{ ownerName }}</div>
                        </div>
                        <div>
                            <div class="text-slate-400 mb-1">组织ID</div>
                            <div class="font-bold text-slate-700 flex items-center gap-2">
                                {{ info.team_id }}
                                <span class="copy" @click="copy(String(info.team_id ?? ''))">复制</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-slate-400 mb-1">当前套餐</div>
                            <div class="font-bold text-slate-700">{{ isOwner ? planName : "成员席位" }}</div>
                        </div>
                        <div>
                            <div class="text-slate-400 mb-1">创建时间</div>
                            <div class="font-bold text-slate-700">{{ fmtDate(info.create_time) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 成员/管理员：到期状态 + 邀请(普通成员无成员管理页,在此分享邀请) -->
        <div v-if="!isOwner" class="panel">
            <div v-if="info.expired === 1" class="notice-danger">团队权益已到期，请联系团队主续期</div>
            <div v-else class="notice-ok">
                团队权益有效，到期时间：<b>{{ info.team_expire_time_desc || "永久" }}</b>
            </div>
            <div class="mt-4 flex justify-end gap-2 flex-wrap">
                <ElButton type="primary" plain class="!rounded-xl" @click="onInvite">邀请成员</ElButton>
                <ElButton type="danger" plain class="!rounded-xl" @click="onLeaveTeam">退出团队</ElButton>
            </div>
        </div>

        <!-- 当前套餐(团队主) -->
        <div v-if="isOwner" class="panel">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                <div class="section-title !mb-0"><span class="bar"></span>当前套餐</div>
                <div class="flex gap-2">
                    <ElButton class="!rounded-xl" @click="emit('navigate', 2)">成员管理</ElButton>
                    <ElButton class="!rounded-xl" @click="emit('navigate', 3)">消耗明细</ElButton>
                </div>
            </div>
            <div class="space-y-4">
                <!-- 套餐卡 -->
                <div class="plan-card max-w-[280px]">
                    <span
                        class="plan-badge"
                        :class="{
                            '!bg-amber-400/20 !text-amber-300': oemStatus === 1,
                            '!bg-white/10 !text-slate-300': oemStatus === 0,
                        }">
                        {{ oemStatus === 2 ? "✓ 生效中" : oemStatus === 1 ? "⏳ 审核中" : "基础版" }}
                    </span>
                    <div class="plan-name">
                        {{ planName }} <span v-if="oemStatus === 2" class="text-amber-300">★</span>
                    </div>
                    <div class="plan-sub">
                        <template v-if="oemStatus === 0">
                            <button
                                class="underline underline-offset-2 hover:text-white transition-colors"
                                @click="emit('navigate', 4)">
                                升级企业OEM
                            </button>
                            ·
                        </template>
                        <template v-else-if="oemStatus === 1">等待站长审核 · </template>
                        <template v-else>长期有效 · </template>
                        <button
                            class="underline underline-offset-2 hover:text-white transition-colors"
                            @click="showBenefits = true">
                            查看权益
                        </button>
                    </div>
                </div>

                <!-- 指标卡片：独立一行，避免被套餐卡挤压成竖条 -->
                <div class="metric-grid">
                    <div class="metric-tile">
                        <div class="metric-ic tint-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-[18px] h-[18px]">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 14a1 1 0 01-.78-1.63l9.9-10.2a.5.5 0 01.86.46l-1.92 6.02A1 1 0 0013 10h7a1 1 0 01.78 1.63l-9.9 10.2a.5.5 0 01-.86-.46l1.92-6.02A1 1 0 0011 14H4z" />
                            </svg>
                        </div>
                        <div class="metric-label">剩余算力</div>
                        <div class="metric-value">{{ info.owner_tokens ?? info.tokens ?? 0 }}</div>
                    </div>

                    <div class="metric-tile">
                        <div class="metric-ic tint-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-[18px] h-[18px]">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 19.5a3 3 0 00-6 0M12 12.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM4.5 19.5a2.25 2.25 0 013.75-1.68M19.5 19.5a2.25 2.25 0 00-3.75-1.68" />
                            </svg>
                        </div>
                        <div class="metric-label">席位使用</div>
                        <div class="metric-value">
                            {{ info.member_count ?? 0
                            }}<span class="metric-unit">/ {{ info.seat_limit ?? 0 }}</span>
                        </div>
                        <div class="metric-bar">
                            <div class="metric-bar-fill" :style="{ width: seatPct + '%' }"></div>
                        </div>
                    </div>

                    <div class="metric-tile">
                        <div class="metric-ic tint-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-[18px] h-[18px]">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="metric-label">剩余席位</div>
                        <div class="metric-value">{{ info.seat_left ?? 0 }}</div>
                    </div>

                    <button type="button" class="metric-tile metric-tile--cost group" @click="emit('navigate', 3)">
                        <div class="metric-ic metric-ic--amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-[18px] h-[18px]">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 14a1 1 0 01-.78-1.63l9.9-10.2a.5.5 0 01.86.46l-1.92 6.02A1 1 0 0013 10h7a1 1 0 01.78 1.63l-9.9 10.2a.5.5 0 01-.86-.46l1.92-6.02A1 1 0 0011 14H4z" />
                            </svg>
                        </div>
                        <div class="metric-label">今日消耗</div>
                        <div class="metric-value is-cost">{{ info.today_cost ?? 0 }}</div>
                        <div class="metric-tip">不包含算力划拨</div>
                        <div class="metric-link">
                            查看明细
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6" />
                            </svg>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- 授权功能 -->
        <div class="panel">
            <div class="flex items-center justify-between mb-5">
                <div class="section-title !mb-0"><span class="bar"></span>授权功能</div>
                <span class="tag-soft">已启用 {{ enabledCount }} / {{ FEATURE_APPS.length }}</span>
            </div>
            <div class="grid grid-cols-5 gap-y-6 gap-x-3">
                <div v-for="app in FEATURE_APPS" :key="app.key" class="relative flex flex-col items-center gap-2">
                    <div
                        class="w-12 h-12 rounded-2xl grid place-items-center"
                        :class="isFeatureEnabled(app.key) ? 'bg-primary/5 text-primary' : 'bg-slate-100 text-slate-300'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-[22px] h-[22px]">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="app.icon" />
                        </svg>
                    </div>
                    <span
                        class="text-[13px] font-medium"
                        :class="isFeatureEnabled(app.key) ? 'text-slate-600' : 'text-slate-300'">
                        {{ app.label }}
                    </span>
                    <span
                        v-if="!isFeatureEnabled(app.key)"
                        class="absolute -top-1.5 left-1/2 translate-x-1 text-[10px] leading-none px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-400 whitespace-nowrap">
                        未开通
                    </span>
                    <template v-if="isOwner && !isFeatureEnabled(app.key)">
                        <span v-if="isFeatureRequested(app.key)" class="text-[12px] text-slate-300">已申请</span>
                        <button
                            v-else
                            class="text-[12px] text-primary hover:underline underline-offset-2"
                            @click="onRequestFeature(app)">
                            请求开通
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- 危险操作(团队主) -->
        <div v-if="isOwner" class="panel !border-red-100">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <div class="font-bold text-slate-900">解散企业</div>
                    <div class="text-slate-400 text-sm mt-1">
                        解散后所有成员与归属用户将被释放，品牌/域名/小程序配置将被清除，且无法恢复
                    </div>
                </div>
                <ElButton type="danger" plain class="!rounded-xl" @click="onDisband">解散企业</ElButton>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { useCopy } from "@/composables/useCopy";
import { useTeamContext } from "../_composables/context";
import { FEATURE_APPS } from "../_enums";
import { fmtDate } from "../_composables/helpers";

const emit = defineEmits<{ (e: "navigate", type: number): void }>();

const { copy } = useCopy();
const { info: infoCtx, members: membersCtx } = useTeamContext();
const {
    info,
    isOwner,
    ownerName,
    planName,
    oemStatus,
    seatPct,
    enabledCount,
    isFeatureEnabled,
    isFeatureRequested,
    showBenefits,
    onRename,
    onLeaveTeam,
    onDisband,
    onRequestFeature,
} = infoCtx;
const { onInvite } = membersCtx;
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";
</style>
