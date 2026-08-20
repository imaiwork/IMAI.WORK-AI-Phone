/** 统一错误文案:字符串直出,否则取后端 msg */
export const errText = (e: any) => (typeof e === "string" ? e : e?.msg || "操作失败");

/** 积分千分位格式化(取绝对值,固定两位小数) */
export const formatNum = (v: any) => {
    const n = Math.abs(Number(v) || 0);
    return n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

/** 日期格式化:兼容后端已格式化字符串与时间戳(秒) */
export const fmtDate = (ts: number | string) => {
    if (!ts) return "-";
    if (typeof ts === "string" && ts.includes("-")) return ts.slice(0, 10);
    const d = new Date(Number(ts) * 1000);
    if (isNaN(d.getTime())) return "-";
    const p = (n: number) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
};
