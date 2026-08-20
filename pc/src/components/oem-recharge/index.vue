<template>
    <ElDialog
        v-model="visible"
        width="420px"
        align-center
        append-to-body
        :show-close="false"
        class="oem-recharge-dialog"
        header-class="!p-0 !m-0"
        body-class="!p-0"
        footer-class="!p-0 !m-0 !border-0"
        @closed="onClosed">
        <div class="or-body">
            <button type="button" class="or-close" aria-label="关闭" @click="visible = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="or-head">
                <div class="or-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-7 h-7">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </div>
                <h3 class="or-title">获取算力</h3>
                <p class="or-desc">
                    本站不支持在线购买算力<br />请联系管理员获取卡密，或直接填写兑换码
                </p>
            </div>

            <div v-if="adminQr" class="or-qr-wrap">
                <img :src="adminQr" alt="管理员联系二维码" class="or-qr" />
                <p class="or-qr-tip">微信扫码联系管理员</p>
            </div>
            <div v-else class="or-empty">
                <p>管理员暂未配置联系方式</p>
                <p class="or-empty-sub">请通过其他渠道联系管理员获取卡密</p>
            </div>

            <div class="or-redeem">
                <div class="or-redeem-label">算力兑换码</div>
                <ElInput
                    v-model="sn"
                    class="or-input"
                    clearable
                    maxlength="64"
                    placeholder="请输入卡密编号"
                    @keyup.enter="onRedeem" />
                <ElButton type="primary" class="or-btn" :loading="loading" @click="onRedeem">
                    立即兑换
                </ElButton>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { checkRedeemCode, useRedeemCode } from "@/api/recharge";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import feedback from "@/utils/feedback";

const appStore = useAppStore();
const userStore = useUserStore();

const visible = computed({
    get: () => appStore.showOemRecharge,
    set: (v: boolean) => {
        appStore.showOemRecharge = v;
    },
});

const adminQr = computed(() => String((appStore.getOemConfig as any)?.admin_qr || ""));
const sn = ref("");
const loading = ref(false);

const onClosed = () => {
    sn.value = "";
    loading.value = false;
};

const onRedeem = async () => {
    const code = sn.value.trim();
    if (!code) {
        feedback.msgWarning("请输入兑换码");
        return;
    }
    if (!userStore.isLogin) {
        visible.value = false;
        userStore.toggleShowLogin(true);
        return;
    }
    loading.value = true;
    try {
        // OEM 获取算力：仅兑算力卡，拒绝会员兑换码
        await checkRedeemCode({ sn: code, scene: "tokens" });
        await useRedeemCode({ sn: code, scene: "tokens" });
        feedback.msgSuccess("兑换成功");
        sn.value = "";
        visible.value = false;
        await userStore.getUser();
    } catch (e: any) {
        feedback.msgError(typeof e === "string" ? e : e?.msg || e || "兑换失败");
    } finally {
        loading.value = false;
    }
};
</script>

<style lang="scss">
.el-dialog.oem-recharge-dialog {
    border-radius: 20px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 24px 60px -12px rgba(15, 23, 42, 0.28);
}
.oem-recharge-dialog .el-dialog__header {
    display: none;
}
.oem-recharge-dialog .el-dialog__body {
    padding: 0;
}

.oem-recharge-dialog .or-body {
    position: relative;
    padding: 28px 24px 24px;
}
.oem-recharge-dialog .or-close {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 1;
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    color: #94a3b8;
    transition: background-color 0.15s ease, color 0.15s ease;
}
.oem-recharge-dialog .or-close:hover {
    background: rgba(15, 23, 42, 0.06);
    color: #475569;
}
.oem-recharge-dialog .or-head {
    text-align: center;
}
.oem-recharge-dialog .or-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #0065fb, #4f9dff);
    box-shadow: 0 10px 24px -6px rgba(0, 101, 251, 0.45);
}
.oem-recharge-dialog .or-title {
    margin-top: 16px;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.oem-recharge-dialog .or-desc {
    margin-top: 8px;
    font-size: 13px;
    line-height: 1.6;
    color: #64748b;
}
.oem-recharge-dialog .or-qr-wrap {
    margin-top: 20px;
    padding: 16px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    text-align: center;
}
.oem-recharge-dialog .or-qr {
    display: block;
    width: 100%;
    max-width: 200px;
    margin: 0 auto;
    border-radius: 12px;
    background: #fff;
}
.oem-recharge-dialog .or-qr-tip {
    margin-top: 12px;
    font-size: 12px;
    font-weight: 500;
    color: #94a3b8;
}
.oem-recharge-dialog .or-empty {
    margin-top: 20px;
    padding: 28px 16px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px dashed #e2e8f0;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}
.oem-recharge-dialog .or-empty-sub {
    margin-top: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #94a3b8;
}
.oem-recharge-dialog .or-redeem {
    margin-top: 20px;
}
.oem-recharge-dialog .or-redeem-label {
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}
.oem-recharge-dialog .or-input .el-input__wrapper {
    min-height: 44px;
    border-radius: 12px;
}
.oem-recharge-dialog .or-btn {
    width: 100%;
    height: 44px;
    margin-top: 12px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
}
</style>
