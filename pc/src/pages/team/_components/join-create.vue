<template>
    <div class="team-entry">
        <div class="team-entry__shell">
            <!-- 左侧：品牌与价值说明（单一主视觉） -->
            <aside class="team-entry__aside" aria-hidden="false">
                <div class="team-entry__glow" />
                <div class="team-entry__aside-inner">
                    <span class="team-entry__eyebrow">企业协作</span>
                    <h1 class="team-entry__title">团队模式</h1>
                    <p class="team-entry__lead">
                        把算力、成员与品牌收拢到一个企业空间，用自己的域名和小程序对外获客。
                    </p>
                    <ul class="team-entry__points">
                        <li v-for="item in points" :key="item">
                            <span class="team-entry__check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- 右侧：单一任务表单 -->
            <section class="team-entry__panel">
                <div class="team-entry__tabs" role="tablist" aria-label="团队入口">
                    <button
                        type="button"
                        role="tab"
                        class="team-entry__tab"
                        :class="{ active: mode === 'create' }"
                        :aria-selected="mode === 'create'"
                        @click="mode = 'create'">
                        开通团队
                    </button>
                    <button
                        type="button"
                        role="tab"
                        class="team-entry__tab"
                        :class="{ active: mode === 'join' }"
                        :aria-selected="mode === 'join'"
                        @click="mode = 'join'">
                        加入团队
                    </button>
                </div>

                <div v-show="mode === 'create'" class="team-entry__form" role="tabpanel">
                    <h2 class="team-entry__form-title">成为团队主</h2>
                    <p class="team-entry__form-desc">创建后即可邀请成员、分配算力，并配置企业品牌。</p>
                    <label class="team-entry__label" for="team-entry-name">团队名称</label>
                    <ElInput
                        id="team-entry-name"
                        v-model="teamName"
                        size="large"
                        maxlength="30"
                        clearable
                        placeholder="例如：星云科技"
                        @keyup.enter="onCreate" />
                    <ElButton
                        type="primary"
                        size="large"
                        class="team-entry__submit"
                        :disabled="!teamName.trim()"
                        @click="onCreate">
                        立即开通
                    </ElButton>
                </div>

                <div v-show="mode === 'join'" class="team-entry__form" role="tabpanel">
                    <h2 class="team-entry__form-title">用邀请码加入</h2>
                    <p class="team-entry__form-desc">向团队成员索取邀请码，加入后即可使用企业算力与共享资源。</p>
                    <label class="team-entry__label" for="team-entry-code">邀请码</label>
                    <ElInput
                        id="team-entry-code"
                        v-model="joinCode"
                        size="large"
                        clearable
                        placeholder="粘贴或输入邀请码"
                        @keyup.enter="onJoin" />
                    <ElButton
                        type="primary"
                        size="large"
                        class="team-entry__submit"
                        :disabled="!joinCode.trim()"
                        @click="onJoin">
                        加入团队
                    </ElButton>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";

const { info } = useTeamContext();
const { teamName, joinCode, onCreate, onJoin } = info;

const mode = ref<"create" | "join">("create");

const points = ["成员席位与算力划拨", "独立域名与站点品牌", "自有小程序与充值卡密"];
</script>

<style lang="scss" scoped>
.team-entry {
    display: flex;
    justify-content: center;
    padding: 48px 0 64px;
    animation: team-entry-in 420ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.team-entry__shell {
    display: grid;
    grid-template-columns: minmax(280px, 1.05fr) minmax(320px, 0.95fr);
    width: min(920px, 100%);
    min-height: 440px;
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid #e8eef6;
    background: #fff;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.06);
}

.team-entry__aside {
    position: relative;
    padding: 40px 36px;
    color: #fff;
    background: linear-gradient(155deg, #0050c8 0%, #0065fb 48%, #3b8bff 100%);
    overflow: hidden;
}

.team-entry__glow {
    position: absolute;
    inset: auto -40% -30% auto;
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.14);
    pointer-events: none;
}

.team-entry__aside-inner {
    position: relative;
    z-index: 1;
    max-width: 34ch;
}

.team-entry__eyebrow {
    display: inline-flex;
    align-items: center;
    height: 28px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    background: rgba(255, 255, 255, 0.16);
    color: rgba(255, 255, 255, 0.95);
}

.team-entry__title {
    margin: 18px 0 0;
    font-size: 34px;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    text-wrap: balance;
}

.team-entry__lead {
    margin: 14px 0 0;
    font-size: 14px;
    line-height: 1.65;
    color: rgba(255, 255, 255, 0.82);
}

.team-entry__points {
    margin: 28px 0 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.team-entry__points li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.94);
}

.team-entry__check {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    margin-top: 1px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.18);

    svg {
        width: 12px;
        height: 12px;
    }
}

.team-entry__panel {
    display: flex;
    flex-direction: column;
    padding: 32px 36px 36px;
    background: #fbfcfe;
}

.team-entry__tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px;
    padding: 4px;
    border-radius: 14px;
    background: #eef2f7;
}

.team-entry__tab {
    height: 40px;
    border: 0;
    border-radius: 11px;
    background: transparent;
    color: #64748b;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition:
        background 180ms ease,
        color 180ms ease,
        box-shadow 180ms ease;

    &.active {
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    &:focus-visible {
        outline: 2px solid #0065fb;
        outline-offset: 2px;
    }
}

.team-entry__form {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-top: 28px;
    animation: team-form-in 240ms ease-out both;
}

.team-entry__form-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.team-entry__form-desc {
    margin: 8px 0 0;
    font-size: 13px;
    line-height: 1.55;
    color: #64748b;
}

.team-entry__label {
    margin: 24px 0 8px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.team-entry__submit {
    margin-top: auto;
    width: 100%;
    height: 44px !important;
    border-radius: 12px !important;
    font-weight: 700 !important;
}

:deep(.el-input__wrapper) {
    border-radius: 12px;
    box-shadow: 0 0 0 1px #e2e8f0 inset;
}

:deep(.el-input__wrapper:hover) {
    box-shadow: 0 0 0 1px #cbd5e1 inset;
}

:deep(.el-input__wrapper.is-focus) {
    box-shadow: 0 0 0 1px #0065fb inset !important;
}

@keyframes team-entry-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes team-form-in {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

@media (max-width: 820px) {
    .team-entry {
        padding: 24px 0 40px;
    }

    .team-entry__shell {
        grid-template-columns: 1fr;
        min-height: 0;
    }

    .team-entry__aside {
        padding: 28px 24px;
    }

    .team-entry__title {
        font-size: 28px;
    }

    .team-entry__panel {
        padding: 24px;
    }

    .team-entry__submit {
        margin-top: 28px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .team-entry,
    .team-entry__form {
        animation: none;
    }
}
</style>
