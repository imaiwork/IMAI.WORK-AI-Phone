/** 将幻灯片图片打包为可下载的 .pptx（每页一张全幅图） */

export type PptExportSlide = {
    page?: number;
    title?: string;
    imageUrl?: string;
};

function sanitizeFileName(name: string): string {
    const cleaned = name.replace(/[\\/:*?"<>|]+/g, "_").trim();
    return cleaned.slice(0, 80) || "PPT";
}

/**
 * 产物存在主站本地盘时，后端用 app_host 拼图片 URL；OEM 域名下访问即跨域，
 * nginx 直出的静态文件不带 CORS 头，fetch 会直接抛 Failed to fetch。
 * OEM 域名与主站是同一台服务器、同一个 public 根，换成当前域同路径即可取到同一文件。
 */
function resolveSameOriginFallback(url: string): { host: string; url: string } | null {
    try {
        const target = new URL(url, window.location.href);
        if (target.origin === window.location.origin) return null;
        return {
            host: target.host,
            url: `${window.location.origin}${target.pathname}${target.search}`,
        };
    } catch {
        return null;
    }
}

/** 已确认要走当前域回退的图片主机，避免每一页都先撞一次跨域失败、白下一遍图 */
const sameOriginFallbackHosts = new Set<string>();

async function fetchImageBlob(url: string): Promise<Blob> {
    const res = await fetch(url, { mode: "cors", credentials: "omit" });
    if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
    }
    const blob = await res.blob();
    // 站点对不存在的 /uploads 路径会落到 PHP 返回 200 + JSON，不能只看 res.ok
    const type = (blob.type || "").toLowerCase();
    if (type.startsWith("text/") || type.includes("json")) {
        throw new Error("非图片响应");
    }
    return blob;
}

function blobToDataUrl(blob: Blob): Promise<string> {
    return new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ""));
        reader.onerror = () => reject(new Error("图片读取失败"));
        reader.readAsDataURL(blob);
    });
}

async function imageUrlToDataUrl(url: string): Promise<string> {
    if (url.startsWith("data:")) return url;
    const fallback = resolveSameOriginFallback(url);
    if (fallback && sameOriginFallbackHosts.has(fallback.host)) {
        return blobToDataUrl(await fetchImageBlob(fallback.url));
    }
    try {
        return blobToDataUrl(await fetchImageBlob(url));
    } catch (e) {
        if (!fallback) throw e;
        const blob = await fetchImageBlob(fallback.url);
        sameOriginFallbackHosts.add(fallback.host);
        return blobToDataUrl(blob);
    }
}

/**
 * 导出已出图的幻灯片为 .pptx 并触发浏览器下载。
 * @returns 实际写入的页数
 */
export async function exportSlidesToPptx(
    slides: PptExportSlide[],
    topic = "PPT",
): Promise<number> {
    const ready = slides.filter((s) => !!s.imageUrl);
    if (!ready.length) {
        throw new Error("暂无可导出的页面，请先完成出图");
    }

    const PptxGenJS = (await import("pptxgenjs")).default;
    const pptx = new PptxGenJS();
    // 与生图 1536x1024 接近的 3:2 横向版式
    pptx.defineLayout({ name: "PPT_3x2", width: 13.5, height: 9 });
    pptx.layout = "PPT_3x2";
    pptx.author = "IMAI";
    pptx.title = topic;

    for (const [idx, s] of ready.entries()) {
        let data: string;
        try {
            data = await imageUrlToDataUrl(s.imageUrl!);
        } catch (e: any) {
            // fetch 的网络/跨域失败只有一句英文 Failed to fetch，换成用户看得懂的提示
            const reason = e instanceof TypeError ? "网络或跨域受限" : e?.message || "未知错误";
            throw new Error(`第 ${s.page ?? idx + 1} 页图片拉取失败（${reason}），请重新生成该页后再导出`);
        }
        const slide = pptx.addSlide();
        slide.addImage({
            data,
            x: 0,
            y: 0,
            w: "100%",
            h: "100%",
        });
        if (s.title) {
            slide.addNotes(String(s.title));
        }
    }

    await pptx.writeFile({ fileName: `${sanitizeFileName(topic)}.pptx` });
    return ready.length;
}
