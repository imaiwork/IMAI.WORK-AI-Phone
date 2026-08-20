import { ElMessage, ElMessageBox, ElNotification, ElLoading, type ElMessageBoxOptions } from "element-plus";
import type { LoadingInstance } from "element-plus/es/components/loading/src/loading";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";

export class Feedback {
    private loadingInstance: LoadingInstance | null = null;
    static instance: Feedback | null = null;
    static getInstance() {
        return this.instance ?? (this.instance = new Feedback());
    }
    // 消息提示
    msg(msg: string) {
        ElMessage.info(msg);
    }
    // 错误消息
    msgError(msg: string) {
        ElMessage.error(msg);
    }
    // 成功消息
    msgSuccess(msg: string) {
        ElMessage.success(msg);
    }
    // 警告消息
    msgWarning(msg: string) {
        ElMessage.warning(msg);
    }
    // 弹出提示
    alert(msg: string) {
        ElMessageBox.alert(msg, "系统提示");
    }
    // 错误提示
    alertError(msg: string) {
        ElMessageBox.alert(msg, "系统提示", { type: "error" });
    }
    // 成功提示
    alertSuccess(msg: string) {
        ElMessageBox.alert(msg, "系统提示", { type: "success" });
    }
    // 警告提示
    alertWarning(msg: string) {
        ElMessageBox.alert(msg, "系统提示", { type: "warning" });
    }
    // 通知提示
    notify(msg: string) {
        ElNotification.info(msg);
    }
    // 错误通知
    notifyError(msg: string) {
        ElNotification.error(msg);
    }
    // 成功通知
    notifySuccess(msg: string) {
        ElNotification.success(msg);
    }
    // 警告通知
    notifyWarning(msg: string) {
        ElNotification.warning(msg);
    }
    // 确认窗体
    confirm(msg: string, title = "温馨提示", options?: ElMessageBoxOptions) {
        return ElMessageBox.confirm(msg, title, {
            confirmButtonText: "确定",
            cancelButtonText: "取消",
            type: "warning",
            ...options,
        });
    }
    // 提交内容
    prompt(content: string, title: string, options?: ElMessageBoxOptions) {
        return ElMessageBox.prompt(content, title, {
            confirmButtonText: "确定",
            cancelButtonText: "取消",
            ...options,
        });
    }
    // 打开全局loading
    loading(msg: string, target?: HTMLElement) {
        this.loadingInstance = ElLoading.service({
            target: target,
            lock: true,
            text: msg,
        });
    }

    // 关闭全局loading
    closeLoading() {
        this.loadingInstance?.close();
    }
    // 算力不足文案(团队感知):企业空间内成员/管理员消耗团队算力,不足提示联系团队主
    powerInsufficientText(): string {
        const info = (useUserStore().userInfo || {}) as any;
        const inTeam = Number(info.team_id) > 0 && [1, 3].includes(Number(info.team_role));
        return inTeam ? "当前团队算力不足，请联系团队主" : "算力不足，请充值！";
    }
    // 算力不足提示
    msgPowerInsufficient() {
        const info = (useUserStore().userInfo || {}) as any;
        const inTeam = Number(info.team_id) > 0 && [1, 3].includes(Number(info.team_role));
        const appStore = useAppStore();
        // OEM 站点：统一弹联系管理员 + 兑换码
        if (Number(appStore.getOemConfig?.is_oem) === 1) {
            appStore.openRecharge();
        } else if (!inTeam) {
            // 成员不能给团队充值,不弹充值面板;个人/团队主/散客弹充值
            appStore.openRecharge();
        }
        feedback.msgWarning(this.powerInsufficientText());
    }
}

const feedback = Feedback.getInstance();

export default feedback;
