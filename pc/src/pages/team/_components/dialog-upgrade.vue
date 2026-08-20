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
        <div class="px-1 pb-1">
            <button class="cd-close" @click="showUpgrade = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <!-- 头部:图标 + 标题 -->
            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #0065fb, #4f9dff);
                        box-shadow: 0 10px 24px -6px rgba(0, 101, 251, 0.5);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-8 h-8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 21h18M5 21V10.5l7-5.25 7 5.25V21M9.75 21v-4.5h4.5V21" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">升级企业OEM</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    独立域名 · 品牌 LOGO · 自有小程序 · 团队卡密
                </p>
            </div>

            <!-- 表单 -->
            <div class="mt-6 space-y-4">
                <div>
                    <div class="text-[13px] font-medium text-slate-500 mb-1.5">手机号</div>
                    <ElInput v-model="upgradeForm.mobile" placeholder="请输入手机号码" size="large" />
                </div>
                <div>
                    <div class="text-[13px] font-medium text-slate-500 mb-1.5">验证码</div>
                    <div class="flex gap-2.5">
                        <ElInput v-model="upgradeForm.code" placeholder="请输入验证码" size="large" class="flex-1" />
                        <ElButton
                            size="large"
                            class="!rounded-xl !px-4 shrink-0"
                            :disabled="smsCountdown > 0"
                            @click="onSendSms">
                            {{ smsCountdown > 0 ? `${smsCountdown}s` : "获取验证码" }}
                        </ElButton>
                    </div>
                </div>
            </div>

            <!-- 应付算力 -->
            <div
                class="flex items-center justify-between rounded-xl px-4 py-3.5 mt-5"
                style="background: linear-gradient(135deg, #fffbeb, #fef3c7)">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.8" class="w-[18px] h-[18px]">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v7h5l-7 11v-7H6l7-11z" />
                    </svg>
                    <span class="text-[13px] font-medium text-amber-700">应付算力</span>
                </div>
                <b class="text-[22px] font-[900] text-amber-500 leading-none">{{ info?.oem_price }}</b>
            </div>
            <p class="text-[12px] text-slate-400 mt-3 leading-relaxed">
                从当前账户算力余额中扣除；提交后进入站长审核，审核未通过将全额退回，审核通过后即开通品牌能力。
            </p>

            <!-- 底部操作 -->
            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showUpgrade = false">取消</ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="upgrading"
                    :disabled="!canPayOem"
                    @click="onUpgradeOem">
                    支付算力
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const ctx = useTeamContext();
const { info } = ctx.info;
const { showUpgrade, upgradeForm, smsCountdown, onSendSms, upgrading, onUpgradeOem } = ctx.brand;
const { popupRef, onPopupClose } = usePopupBridge(showUpgrade);

const canPayOem = computed(() => {
    const mobile = String(upgradeForm.mobile || "").trim();
    const code = String(upgradeForm.code || "").trim();
    return /^1\d{10}$/.test(mobile) && code.length > 0;
});
</script>
