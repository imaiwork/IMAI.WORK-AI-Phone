<template>
    <popup
        ref="popupRef"
        width="440px"
        class="consume-detail-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div class="invite-dialog">
            <button class="cd-close" type="button" aria-label="关闭" @click="showInvite = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="invite-dialog__hero">
                <div class="invite-dialog__ic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                    </svg>
                </div>
                <h3 class="invite-dialog__title">邀请成员</h3>
                <p class="invite-dialog__desc">
                    任意团队成员都可分享邀请码，对方在「团队模式 → 加入团队」中输入即可加入
                </p>
            </div>

            <div class="invite-dialog__code-wrap">
                <div class="invite-dialog__code-label">邀请码</div>
                <button
                    type="button"
                    class="invite-dialog__code"
                    :title="inviteCode ? '点击复制' : ''"
                    :disabled="!inviteCode"
                    @click="handleCopy">
                    <span class="invite-dialog__code-text">{{ inviteCode || "--------" }}</span>
                    <span class="invite-dialog__code-hint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        点击复制
                    </span>
                </button>
            </div>

            <ol class="invite-dialog__steps">
                <li><span>1</span>复制上方邀请码</li>
                <li><span>2</span>发给要加入的同事</li>
                <li><span>3</span>对方在加入团队中填写</li>
            </ol>

            <div class="invite-dialog__actions">
                <ElButton class="invite-dialog__btn" @click="showInvite = false">关闭</ElButton>
                <ElButton
                    type="primary"
                    class="invite-dialog__btn invite-dialog__btn--primary"
                    :disabled="!inviteCode"
                    @click="handleCopy">
                    复制邀请码
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { members } = useTeamContext();
const { showInvite, inviteCode, copyCode } = members;
const { popupRef, onPopupClose } = usePopupBridge(showInvite);

const handleCopy = () => {
    if (!inviteCode.value) return;
    copyCode();
};
</script>

<style lang="scss" scoped>
.invite-dialog {
    position: relative;
    padding: 8px 4px 4px;
}

.invite-dialog__hero {
    text-align: center;
    padding-top: 8px;
}

.invite-dialog__ic {
    width: 64px;
    height: 64px;
    margin: 0 auto;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #0065fb, #4f9dff);
    box-shadow: 0 10px 24px -6px rgba(0, 101, 251, 0.45);

    svg {
        width: 32px;
        height: 32px;
    }
}

.invite-dialog__title {
    margin: 16px 0 0;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.invite-dialog__desc {
    margin: 8px auto 0;
    max-width: 30ch;
    font-size: 13px;
    line-height: 1.55;
    color: #64748b;
}

.invite-dialog__code-wrap {
    margin-top: 24px;
}

.invite-dialog__code-label {
    margin-bottom: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.04em;
}

.invite-dialog__code {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 22px 20px 16px;
    border: 1.5px dashed #b7cffc;
    border-radius: 16px;
    background: linear-gradient(180deg, #f5f9ff 0%, #eef5ff 100%);
    cursor: pointer;
    transition:
        border-color 160ms ease,
        background 160ms ease,
        transform 160ms ease;

    &:hover:not(:disabled) {
        border-color: #0065fb;
        background: linear-gradient(180deg, #eff6ff 0%, #e0edff 100%);
    }

    &:active:not(:disabled) {
        transform: scale(0.99);
    }

    &:disabled {
        cursor: default;
        opacity: 0.7;
    }

    &:focus-visible {
        outline: 2px solid #0065fb;
        outline-offset: 2px;
    }
}

.invite-dialog__code-text {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 0.18em;
    color: #0065fb;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}

.invite-dialog__code-hint {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;

    svg {
        width: 14px;
        height: 14px;
    }
}

.invite-dialog__steps {
    margin: 18px 0 0;
    padding: 14px 16px;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 10px;
    border-radius: 14px;
    background: #f8fafc;

    li {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }

    span {
        flex-shrink: 0;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        font-size: 11px;
        font-weight: 800;
        color: #0065fb;
        background: #e6efff;
    }
}

.invite-dialog__actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.invite-dialog__btn {
    flex: 1;
    height: 44px !important;
    border-radius: 12px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
}

.invite-dialog__btn--primary {
    font-weight: 700 !important;
}

@media (prefers-reduced-motion: reduce) {
    .invite-dialog__code {
        transition: none;
    }
}
</style>
