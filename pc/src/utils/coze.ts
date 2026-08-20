/**
 * Coze 工作流调用封装(对标后端 ToolsService::Coze() 的姿势)
 *
 * 接入要点:
 *   - 直接走 https://api.coze.cn(CORS allow-origin: *,浏览器可直连)
 *   - 返回结构:`{ code: 0, msg, data: "JSON 字符串" }`,data 解出来还有 `{ output: "JSON 字符串" }`,**双层**
 *   - 失败时把后端错误信息抛出去,前端 catch 后用 ElMessage 显示
 *
 * 用法:
 *   const result = await genPptFollowup("2026 Q1 总结");
 *   // result.fields[].field_type 可能是 select / textarea / number / input / radio
 */

// ⚠️ Coze 工作流访问令牌(sat_...)
const COZE_TOKEN = "sat_9JwHt8drutxFBZXg6OaO61bzr9OOvC6xK1bYH70gzmwBFls4DQaxcD6nL4FEDWfc";
const COZE_BASE = "https://api.coze.cn/v1";

// PPT 智能追问工作流 ID
export const WORKFLOW_PPT_FOLLOWUP = "7649669436093349894";
// PPT 章节生成工作流 ID(根据主题 + 页数 → 每章节标题与内容)
export const WORKFLOW_PPT_CHAPTERS = "7649691612990079014";
// 地图获客 - 自然语言转高德搜索参数(只负责语言转换,不做意图判断和回复)
export const WORKFLOW_MAP_INTENT = "7649960436129316900";
// 视频生成 - 提示词优化(把用户随口写的描述扩成详细的视频生成 prompt)
export const WORKFLOW_VIDEO_PROMPT_OPTIMIZE = "7639931710104633370";
// 数字人 - AI 追问 第 1 步:用户描述 → 表单 schema(题目集)
export const WORKFLOW_DIGITAL_FOLLOWUP_FORM = "7650724648263696384";
// 数字人 - AI 追问 第 2 步:用户填好的表单 → 文案参数
export const WORKFLOW_DIGITAL_FOLLOWUP_PARAMS = "7650725914385612840";

/**
 * 提示词优化通用调用 — 同一个 Coze 工作流,不同业务用不同 Serial_Number / 输出字段
 *   - 视频: Serial_Number=6, 读 output_7
 *   - 图片: Serial_Number=10, 读 output_10
 */
/** 把值规整成非空字符串 — 兼容:直接字符串 / 字符串数组 / 数字 / undefined */
function pickString(v: any): string {
    if (typeof v === "string") return v.trim();
    if (Array.isArray(v)) {
        for (const item of v) {
            const s = pickString(item);
            if (s) return s;
        }
    }
    if (typeof v === "number") return String(v);
    return "";
}

async function callPromptOptimize(args: {
    serial: number;
    outputKey: string;
    text: string;
}): Promise<string> {
    const out = await runCozeWorkflow<any>(WORKFLOW_VIDEO_PROMPT_OPTIMIZE, {
        Serial_Number: args.serial,
        Text: args.text,
        Amount: 1,
        Length: "",
    });
    if (typeof out === "string") {
        const s = pickString(out);
        if (s) return s;
    }
    if (out && typeof out === "object") {
        // 工作流真实字段命名是 output10 / output7(无下划线),兼容带下划线写法
        const keysToTry = [
            args.outputKey,
            args.outputKey.replace(/_/g, ""),
            `output${args.serial}`,
            `output_${args.serial}`,
            "output",
        ];
        for (const k of keysToTry) {
            const s = pickString(out[k]);
            if (s) return s;
        }
        // 兜底:扫所有 output*(含数字下标)字段,取第一个非空的
        for (const k of Object.keys(out)) {
            if (/^output/i.test(k)) {
                const s = pickString(out[k]);
                if (s) return s;
            }
        }
    }
    console.warn("[optimize] 工作流原始返回:", out);
    throw new Error("提示词优化工作流返回为空");
}

export function optimizeVideoPrompt(userText: string): Promise<string> {
    return callPromptOptimize({ serial: 7, outputKey: "output_7", text: userText });
}

export function optimizeImagePrompt(userText: string): Promise<string> {
    return callPromptOptimize({ serial: 10, outputKey: "output_10", text: userText });
}

/** 视频标题生成(数字人:每条文案各自挑一个标题) — Serial_Number=8 */
export function genVideoTitle(copyText: string): Promise<string> {
    return callPromptOptimize({ serial: 8, outputKey: "output_8", text: copyText });
}

/** 把后端 type 映射成 ppt-followup 能渲染的 field_type */
function mapDigitalFieldType(t: string): CozeFieldType {
    const k = (t || "").toLowerCase();
    if (k === "single_choice" || k === "radio") return "radio";
    if (k === "multi_choice" || k === "multiple_choice" || k === "checkbox") return "checkbox";
    if (k === "select" || k === "dropdown") return "select";
    if (k === "number" || k === "int" || k === "integer") return "number";
    if (k === "textarea" || k === "long_text") return "textarea";
    return "input";
}

/** 数字人 AI 追问 第 1 步:用户描述 → 动态问卷
 *  实际工作流返回 {output: "<JSON string>"},内层是 {questions: [{field, question, type, options?}]}
 *  这里转成 ppt-followup 渲染需要的 {description, ppt_type, fields[]} 结构 */
export async function genDigitalFollowupForm(text: string): Promise<PptFollowupResult> {
    const out = await runCozeWorkflow<any>(WORKFLOW_DIGITAL_FOLLOWUP_FORM, { input: text });
    // 解开外层包装(可能已经是对象也可能是字符串)
    let schema: any = out;
    if (schema && typeof schema === "object" && schema.output && typeof schema.output === "string") {
        try { schema = JSON.parse(schema.output); } catch { /* ignore */ }
    }
    if (schema && typeof schema === "string") {
        try { schema = JSON.parse(schema); } catch { /* ignore */ }
    }
    // 兼容 fields(我原本预期) 和 questions(实际返回)
    const list: any[] = Array.isArray(schema?.fields)
        ? schema.fields
        : Array.isArray(schema?.questions)
            ? schema.questions
            : [];
    if (!list.length) {
        console.warn("[digital-followup] 工作流原始返回:", out);
        throw new Error("追问表单工作流返回缺少 fields/questions");
    }
    const fields: CozeField[] = list.map((q: any, idx: number) => ({
        id: q.id ?? q.field ?? q.name ?? `q_${idx}`,
        label: q.label ?? q.question ?? q.title ?? `问题 ${idx + 1}`,
        description: q.description ?? "",
        field_type: mapDigitalFieldType(q.field_type ?? q.type ?? "input"),
        default_value: q.default_value ?? "",
        options: Array.isArray(q.options) ? q.options : [],
        placeholder: q.placeholder ?? "",
        required: q.required ?? true,
        max_length: q.max_length,
    }));
    return {
        description: schema?.description ?? "",
        ppt_type: schema?.ppt_type ?? "",
        fields,
    };
}

/** 数字人 AI 追问 第 2 步:用户填好的表单答案原样传给工作流 → 拿到文案参数 */
export async function genDigitalFollowupParams(answers: Record<string, any>): Promise<any> {
    // 工作流可能要求一个固定的 input 字段,也可能要求把字段平铺。先平铺 + 额外塞一个 input 兜底
    return await runCozeWorkflow<any>(WORKFLOW_DIGITAL_FOLLOWUP_PARAMS, {
        ...answers,
        input: JSON.stringify(answers),
    });
}

/** 数字人 AI 追问 第 3 步:把第 2 步的 params 喂给文案生成工作流(7639931710104633370)
 *  返回所有非空文案数组(Amount=N 时工作流会返回 N 条)
 *  options.amountOverride:单条"重新生成"时强制传 Amount=1 */
export async function genDigitalFinalCopy(
    params: any,
    options?: { amountOverride?: number },
): Promise<string[]> {
    const payload: any = typeof params === "string" ? { Text: params } : { ...params };
    if (options?.amountOverride !== undefined) {
        payload.Amount = options.amountOverride;
    }
    const out = await runCozeWorkflow<any>(WORKFLOW_VIDEO_PROMPT_OPTIMIZE, payload);
    const results: string[] = [];
    const collect = (v: any) => {
        if (typeof v === "string") {
            const s = v.trim();
            if (s) results.push(s);
        } else if (Array.isArray(v)) {
            v.forEach(collect);
        }
    };
    if (typeof out === "string") collect(out);
    if (out && typeof out === "object") {
        for (const k of Object.keys(out)) {
            if (/^output/i.test(k)) collect(out[k]);
        }
        if (!results.length) {
            // 兜底:扫所有字段
            for (const k of Object.keys(out)) collect(out[k]);
        }
    }
    if (!results.length) {
        console.warn("[digital-final-copy] 工作流原始返回:", out);
        throw new Error("文案生成工作流返回为空");
    }
    return results;
}

export type CozeFieldType =
    | "input"
    | "text"
    | "textarea"
    | "number"
    | "select"
    | "radio"
    | "checkbox"
    | string;

export interface CozeField {
    id: string;
    label: string;
    description?: string;
    field_type: CozeFieldType;
    default_value: string | number;
    options: string[];
    placeholder?: string;
    required: boolean;
    max_length?: number;
}

export interface PptFollowupResult {
    description: string;
    ppt_type: string;
    fields: CozeField[];
}

function getToken(): string {
    if (typeof window !== "undefined" && (window as any).COZE_TOKEN) {
        return (window as any).COZE_TOKEN as string;
    }
    return COZE_TOKEN;
}

interface CozeRunResp {
    code: number;
    msg?: string;
    data?: string | object;
    debug_url?: string;
    execute_id?: string;
}

/** 通用:跑一个 Coze 工作流并解出 output(自动处理双层 JSON 编码) */
export async function runCozeWorkflow<T = any>(
    workflowId: string,
    parameters: Record<string, any>,
): Promise<T> {
    const token = getToken();
    if (!token) throw new Error("Coze Token 未配置 — 请填 utils/coze.ts");

    const resp = await fetch(`${COZE_BASE}/workflow/run`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ workflow_id: workflowId, parameters }),
    });

    if (!resp.ok) {
        throw new Error(`Coze HTTP ${resp.status}: ${(await resp.text()).slice(0, 200)}`);
    }

    const json: CozeRunResp = await resp.json();
    if (json.code !== 0) {
        throw new Error(json.msg || `Coze code=${json.code}`);
    }

    // data 可能是 JSON 字符串(走 fetch / curl)或对象(走某些 SDK)
    let data: any = json.data;
    if (typeof data === "string") {
        try {
            data = JSON.parse(data);
        } catch {
            return data as T;
        }
    }
    // output 又是 JSON 字符串
    let out: any = data?.output ?? data;
    if (typeof out === "string") {
        try {
            out = JSON.parse(out);
        } catch {
            // 工作流可能直接返回字符串,不强制 parse
        }
    }
    return out as T;
}

/** 触发 PPT 智能追问工作流,得到一份待填表单 schema */
export async function genPptFollowup(topic: string): Promise<PptFollowupResult> {
    const out = await runCozeWorkflow<PptFollowupResult>(WORKFLOW_PPT_FOLLOWUP, { input: topic });
    if (!out || !Array.isArray((out as any).fields)) {
        throw new Error("工作流返回缺少 fields 字段,无法渲染表单");
    }
    return out;
}

/** PPT 章节(每页) */
export interface PptChapter {
    page: number;
    title: string;
    content: string;
}

/**
 * 调章节生成工作流,得到每页的标题和内容
 * @param input1 用户问卷答案(或者直接提交的主题文本)
 * @param input2 生成页数
 */
export async function genPptChapters(input1: string, input2: number | string): Promise<PptChapter[]> {
    const out = await runCozeWorkflow<{ pages: PptChapter[] }>(WORKFLOW_PPT_CHAPTERS, {
        input1,
        input2: String(input2),
    });
    if (!out || !Array.isArray(out.pages)) {
        throw new Error("工作流返回缺少 pages 字段");
    }
    return out.pages;
}

/** 地图获客 - 自然语言转参数 */
export interface MapSearchParams {
    biz: string;
    city: string;
    region: string;
    location_extra: string;
    target_count: number | string | null;
    types: string;
}

/**
 * 把用户随口说的话(如"10个南山美容店")翻译成高德 POI 搜索参数。
 * 工作流仅负责语言转换,不返回 intent/reply(那些前端硬写死)。
 * target_count 没识别出来 → 由前端兜底 20。
 */
export async function genMapSearchParams(text: string): Promise<MapSearchParams> {
    const out = await runCozeWorkflow<MapSearchParams>(WORKFLOW_MAP_INTENT, { input: text });
    if (!out || typeof out !== "object") {
        throw new Error("工作流返回格式异常");
    }
    return {
        biz: (out.biz ?? "").toString().trim(),
        city: (out.city ?? "").toString().trim(),
        region: (out.region ?? "").toString().trim(),
        location_extra: (out.location_extra ?? "").toString().trim(),
        target_count: out.target_count ?? null,
        types: (out.types ?? "").toString().trim(),
    };
}
