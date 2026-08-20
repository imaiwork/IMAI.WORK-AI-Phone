/** 账号拉取步骤失败态（与页面 StepStatus.FAILED / 魔法值 3 对齐） */
export const ACCOUNT_FETCH_FAILED_STATUS = 3;

export function applyAccountFetchError(
    steps: Array<{ type: string; errorCode?: number; status: number }>,
    error: any,
    failedStatus = ACCOUNT_FETCH_FAILED_STATUS,
): string {
    const msg = String(error?.error || error?.content?.msg || "账号信息获取失败");
    const type = error?.type;
    const code = error?.code;

    // 先停掉转圈的步骤，避免 type/errorCode 对不上时一直停在「读取中」
    const running = steps.find((item) => item.status === 1);
    if (running) {
        running.status = failedStatus;
        return msg;
    }

    const matched = steps.find(
        (item) => item.type === type || (item.errorCode != null && item.errorCode == code),
    );
    if (matched) {
        matched.status = failedStatus;
    } else if (steps.length) {
        steps[steps.length - 1].status = failedStatus;
    }
    return msg;
}

export function formatAccountFetchError(msg: string): string {
    const text = String(msg || "账号信息获取失败").trim();
    if (text.includes("请检查手机软件运行状态")) return text;
    return `${text}，请检查手机软件运行状态是否正常。`;
}
