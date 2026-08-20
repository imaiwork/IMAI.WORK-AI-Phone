/**
 * AIHubMix gpt-image-2 图像生成 / 编辑封装
 *
 * 用法:
 *   - 把下面 const API_KEY 填上你在 aihubmix.com 后台生成的 Key(sk-***)
 *   - 或者运行时在浏览器控制台:`window.AIHUBMIX_KEY = "sk-..."` 临时注入(刷新失效)
 *
 * 接入位置:
 *   - 图片创作模式 + ChatGPT 模型时被调用(见 welcome-hero.vue)
 *   - 无参考图 → /images/generations
 *   - 有参考图 → /images/edits(多图 = 多次 image 字段)
 */

// ⚠️ 这里换成你在 aihubmix.com 生成的 Key,以 "sk-" 开头
const API_KEY = "sk-nHDptj3e1iG5pFlCD9F0825b0d6d4034AeE498E6E963BaAc";

const BASE_URL = "https://aihubmix.com/v1";
const MODEL = "gpt-image-2";

export type ApiSize = "1024x1024" | "1536x1024" | "1024x1536" | "auto";
export type ApiQuality = "high" | "medium" | "low" | "auto";

export interface ImageGenOpts {
    prompt: string;
    n?: number; // 1-10
    size?: ApiSize;
    quality?: ApiQuality;
    moderation?: "low" | "auto";
    background?: "transparent" | "opaque" | "auto";
}

export interface ImageEditOpts {
    prompt: string;
    images: File[]; // 1 张或多张参考图
    n?: number;
    size?: ApiSize;
    quality?: ApiQuality;
    input_fidelity?: "high" | "low"; // 特征保留强度
}

export interface ImageGenResult {
    data: { b64_json?: string; url?: string }[];
    usage?: {
        input_tokens?: number;
        output_tokens?: number;
        total_tokens?: number;
        input_tokens_details?: { image_tokens?: number; text_tokens?: number };
    };
}

function getKey(): string {
    // 1) 运行时 window 注入优先
    if (typeof window !== "undefined" && (window as any).AIHUBMIX_KEY) {
        return (window as any).AIHUBMIX_KEY as string;
    }
    // 2) 编译期常量兜底
    return API_KEY;
}

async function readErr(resp: Response): Promise<string> {
    const txt = await resp.text();
    try {
        const j = JSON.parse(txt);
        return j.error?.message ?? txt;
    } catch {
        return txt || `HTTP ${resp.status}`;
    }
}

/** 文生图(等价 Python client.images.generate) */
export async function generateImage(opts: ImageGenOpts): Promise<ImageGenResult> {
    const key = getKey();
    if (!key) throw new Error("AIHubMix API Key 未配置 — 请填 utils/aihubmix.ts 或 window.AIHUBMIX_KEY");

    const body = {
        model: MODEL,
        prompt: opts.prompt,
        n: opts.n ?? 1,
        size: opts.size ?? "auto",
        quality: opts.quality ?? "auto",
        moderation: opts.moderation ?? "auto",
        background: opts.background ?? "auto",
    };

    const resp = await fetch(`${BASE_URL}/images/generations`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${key}`,
        },
        body: JSON.stringify(body),
    });

    if (!resp.ok) throw new Error(await readErr(resp));
    return resp.json();
}

/** 图生图 / 参考图编辑(等价 Python client.images.edit) */
export async function editImage(opts: ImageEditOpts): Promise<ImageGenResult> {
    const key = getKey();
    if (!key) throw new Error("AIHubMix API Key 未配置 — 请填 utils/aihubmix.ts 或 window.AIHUBMIX_KEY");
    if (!opts.images.length) throw new Error("editImage 需要至少 1 张参考图");

    const form = new FormData();
    form.append("model", MODEL);
    form.append("prompt", opts.prompt);
    form.append("n", String(opts.n ?? 1));
    form.append("size", opts.size ?? "auto");
    form.append("quality", opts.quality ?? "auto");
    form.append("input_fidelity", opts.input_fidelity ?? "high");

    // 多图:同名字段重复 append,OpenAI 兼容端会当数组解析
    opts.images.forEach((file, i) => {
        form.append("image", file, file.name || `ref-${i}.png`);
    });

    const resp = await fetch(`${BASE_URL}/images/edits`, {
        method: "POST",
        headers: { Authorization: `Bearer ${key}` },
        body: form,
    });

    if (!resp.ok) throw new Error(await readErr(resp));
    return resp.json();
}

/** 项目 UI 比例 → API size(gpt-image-2 实际只支持 3 档 + auto) */
export function mapApiSize(ratio: string): ApiSize {
    if (!ratio || ratio === "smart") return "auto";
    const landscape = ["21:9", "16:9", "3:2", "4:3"];
    const portrait = ["3:4", "2:3", "9:16"];
    if (landscape.includes(ratio)) return "1536x1024";
    if (portrait.includes(ratio)) return "1024x1536";
    return "1024x1024"; // 1:1
}

/** 项目 UI 分辨率("高清 2K"/"超清 4K") → API quality */
export function mapApiQuality(resolution: string): ApiQuality {
    if (!resolution) return "auto";
    if (resolution.includes("4K") || resolution.includes("2K")) return "high";
    if (resolution.includes("低") || resolution.toLowerCase().includes("low")) return "low";
    return "auto";
}

/** 错误信息本地化 — 把英文错误翻译成中文(主要是 moderation 拒绝) */
export function localizeError(raw: string): string {
    if (!raw) return "未知错误";
    if (raw.includes("safety system") || raw.includes("moderation_blocked")) {
        return "提示词触发安全策略，请调整后重试";
    }
    if (raw.includes("rate_limit") || raw.includes("Rate limit")) {
        return "请求过于频繁，请稍后再试";
    }
    if (raw.includes("Incorrect API key") || raw.includes("invalid_api_key")) {
        return "API Key 无效，请确认 utils/aihubmix.ts 里的 Key";
    }
    if (
        raw.includes("billing") ||
        raw.includes("quota") ||
        raw.includes("balance is insufficient") ||
        raw.includes("balance") ||
        raw.includes("recharge")
    ) {
        return "AiHubMix 账户余额不足,请到 aihubmix.com 充值后重试";
    }
    return raw;
}
