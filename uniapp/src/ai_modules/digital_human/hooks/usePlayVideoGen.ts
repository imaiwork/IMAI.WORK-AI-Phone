import { getPlayVideoDetail, submitPlayVideo } from "@/api/play_video";
import {
    EXPECT_SECONDS,
    PERCENT_CAP,
    PERCENT_START,
    POLL_BACKOFF_MS,
    POLL_FIRST_MS,
    POLL_INTERVAL_MS,
    PlayKeyEnum,
    PlayTaskStatusEnum,
} from "../enums/play_video";
import { makePlaySn, type GenState } from "../utils/play_video";

/**
 * 爆款玩法生成流程：提交 → 轮询 detail → 终态。
 * 「后台运行」关浮层并退回上一级（爆款玩法复刻页），任务在服务端照常推进，完成后在创作记录可见。
 * retry=true 只在上一单还没出终态时沿用同一 sn（站长幂等不重复扣费）；
 * 成功「再生成一条」与失败「重试」都走新 sn，否则幂等会把旧的终态任务原样返回。
 */
/** 站长在模板已下架时返回的提示关键字（PlayVideoLogic::submit） */
const TEMPLATE_OFFLINE_HINT = "已下架";
const TEMPLATE_OFFLINE_BACK_DELAY_MS = 800;

export function usePlayVideoGen(play: PlayKeyEnum) {
    const state = reactive<GenState>({
        show: false,
        phase: "running",
        stepLabel: "排队等待生成",
        percent: PERCENT_START,
        elapsed: 0,
        sn: "",
        videoUrl: "",
        failReason: "",
        refunded: false,
        tokensCost: 0,
    });
    const expectSeconds = EXPECT_SECONDS[play];
    let timer: ReturnType<typeof setInterval> | null = null;
    let pollTimer: ReturnType<typeof setTimeout> | null = null;
    let currentSn = "";

    const stopTimers = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    };

    const tick = () => {
        state.elapsed += 1;
        state.percent = Math.min(
            PERCENT_CAP,
            PERCENT_START + (state.elapsed / expectSeconds) * (PERCENT_CAP - PERCENT_START),
        );
    };

    const poll = (delay: number) => {
        pollTimer = setTimeout(async () => {
            if (!state.show || state.phase !== "running") return;
            try {
                const data: any = await getPlayVideoDetail({ sn: state.sn });
                if (data.status === PlayTaskStatusEnum.SUCCESS) {
                    state.percent = 100;
                    state.videoUrl = data.video_url || "";
                    state.phase = "success";
                    stopTimers();
                    return;
                }
                if (data.status === PlayTaskStatusEnum.FAILED || data.status === PlayTaskStatusEnum.TIMEOUT) {
                    state.failReason = data.fail_reason || "生成失败";
                    state.refunded = !!data.refunded;
                    state.phase = "fail";
                    stopTimers();
                    return;
                }
                if (data.step_label) state.stepLabel = data.step_label;
                poll(POLL_INTERVAL_MS);
            } catch (e) {
                poll(POLL_BACKOFF_MS);
            }
        }, delay);
    };

    /** 提交并打开浮层；params 为站长 submit 契约（不含 sn，由这里按 retry 规则注入） */
    const start = async (params: Record<string, any>, retry = false) => {
        // 站长与中台都按 sn 幂等：上一单已出终态（成功/失败）再沿用同一 sn，
        // 拿回来的只会是那条终态任务本身——失败浮层的「重试」曾经因此等于没点
        if (!retry || state.phase !== "running") currentSn = "";
        if (!currentSn) currentSn = makePlaySn();
        state.show = true;
        state.phase = "running";
        state.stepLabel = "排队等待生成";
        state.percent = PERCENT_START;
        state.elapsed = 0;
        state.videoUrl = "";
        state.failReason = "";
        try {
            const data: any = await submitPlayVideo({ ...params, play, sn: currentSn });
            state.sn = data.sn;
            state.tokensCost = Number(data.tokens_cost || 0);
            uni.$u.toast("已提交，开始合成");
            stopTimers();
            timer = setInterval(tick, 1000);
            poll(POLL_FIRST_MS);
            return data;
        } catch (e: any) {
            state.show = false;
            stopTimers();
            const msg = String(e || "提交失败");
            uni.$u.toast(msg);
            // 模板被后台下架：这页已经没法继续了，回模板墙重选（与 onLoad 里「请先选择模板」同款处理）
            if (msg.includes(TEMPLATE_OFFLINE_HINT)) {
                setTimeout(() => uni.navigateBack(), TEMPLATE_OFFLINE_BACK_DELAY_MS);
            }
            throw e;
        }
    };

    const close = () => {
        state.show = false;
        stopTimers();
    };

    /** 后台运行：关浮层后退回上一级，留在当前页会让人以为没反应 */
    const background = () => {
        close();
        uni.navigateBack();
    };

    onUnmounted(stopTimers);

    return { state, start, close, background };
}
