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

async function imageUrlToDataUrl(url: string): Promise<string> {
    if (url.startsWith("data:")) return url;
    const res = await fetch(url, { mode: "cors", credentials: "omit" });
    if (!res.ok) {
        throw new Error(`图片拉取失败(${res.status})`);
    }
    const blob = await res.blob();
    return await new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ""));
        reader.onerror = () => reject(new Error("图片读取失败"));
        reader.readAsDataURL(blob);
    });
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

    for (const s of ready) {
        const data = await imageUrlToDataUrl(s.imageUrl!);
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
