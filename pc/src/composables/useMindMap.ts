import { Transformer } from "markmap-lib";
import { Markmap, type IMarkmapOptions } from "markmap-view";
import { Toolbar } from "markmap-toolbar";
import "markmap-toolbar/dist/style.css";
import html2Canvas from "html2canvas";

interface CustomMarkmapOptions extends Partial<IMarkmapOptions> {}

/** 导出画布最大面积（像素），超出后自动降倍率，避免大导图把浏览器画布顶爆 */
const MAX_EXPORT_PIXELS = 16000000;
/** 导出画布单边最大像素，部分浏览器超过该值会直接导出空白 */
const MAX_EXPORT_SIDE = 8192;
/** 导出图四周留白，单位是输出像素 */
const EXPORT_PADDING = 32;
/** 默认导出倍率：相对导图自然尺寸放大 2 倍，16px 正文栅格化后相当于 32px */
const DEFAULT_EXPORT_SCALE = 2;

export interface MindMapExportOptions extends CustomMarkmapOptions {
    /** 相对导图自然尺寸的栅格化倍率，默认 2 */
    scale?: number;
    /** 文件名（不含后缀），默认 mindmap */
    filename?: string;
    /** 需要保持折叠状态的节点内容，不传则沿用当前实例的折叠状态 */
    folds?: string[];
}

/** 收集当前实例里被折叠的节点，导出时还原同样的视图 */
const collectFolds = (node: any, folds: string[] = []): string[] => {
    if (!node) return folds;
    if (node.payload?.fold) folds.push(node.content);
    node.children?.forEach((child: any) => collectFolds(child, folds));
    return folds;
};

const applyFolds = (node: any, folds: Set<string>) => {
    if (!node) return;
    if (folds.has(node.content)) {
        node.payload = { ...(node.payload || {}), fold: 1 };
    }
    node.children?.forEach((child: any) => applyFolds(child, folds));
};

/** 按画布上限收敛倍率，保证大图也能导出成功 */
const resolveExportScale = (scale: number, width: number, height: number) => {
    const limitByArea = Math.sqrt(MAX_EXPORT_PIXELS / (width * height));
    const limitBySide = Math.min(
        (MAX_EXPORT_SIDE - EXPORT_PADDING * 2) / width,
        (MAX_EXPORT_SIDE - EXPORT_PADDING * 2) / height
    );
    return Math.max(0.1, Math.min(scale, limitByArea, limitBySide));
};

/** 文件名可能来自用户输入，去掉路径分隔符等非法字符 */
const sanitizeFilename = (name?: string) =>
    (name || "mindmap").replace(/[\\/:*?"<>|\r\n]/g, "-").trim().slice(0, 40) || "mindmap";

export function useMindMap() {
    const toolbarContainer = ref(null);
    const markmap = ref<Markmap | null>(null);
    const transformer = new Transformer();
    const isFullscreen = ref(false);

    const mindMapInit = async (mindMapContainer: SVGSVGElement, params?: CustomMarkmapOptions) => {
        await nextTick();
        markmap.value = Markmap.create(mindMapContainer, params);

        if (toolbarContainer.value) {
            const toolbar = new Toolbar();
            toolbar.attach(markmap.value as any);
            toolbarContainer.value.appendChild(toolbar.el);

            const fullscreenButton = document.createElement("button");
            fullscreenButton.innerHTML =
                '<svg width="20" height="20" viewBox="0 0 20 20"><path stroke="none" fill="currentColor" fill-rule="evenodd" d="M4 9v-4h4v2h-2v2zM4 11v4h4v-2h-2v-2zM16 9v-4h-4v2h2v2zM16 11v4h-4v-2h2v-2z"></path></svg>';
            fullscreenButton.className = "mm-toolbar-item";
            fullscreenButton.onclick = () => {
                isFullscreen.value = !isFullscreen.value;
                setTimeout(() => {
                    markmap.value?.fit();
                }, 300);
            };
            toolbar.el.appendChild(fullscreenButton);
        }
    };

    const mindMapFit = (content: string) => {
        const { root } = transformer.transform(content);
        markmap.value?.setData(root);
        setTimeout(() => {
            markmap.value?.fit();
        }, 100);
    };

    /**
     * 导出高清 PNG
     *
     * 不能直接抓屏幕上的 SVG：fit() 会把整张图按 min(视口宽/图宽, 视口高/图高) 压进容器，
     * 节点越多缩得越小，导出的只是缩略图；html2canvas 又会按 CSS 尺寸序列化 SVG，
     * 再调大 scale 也只是放大位图。这里改为按导图自然尺寸建离屏实例，用倍率重新栅格化，
     * 节点层级和内容多少都不影响清晰度。
     */
    const mindMapExportAsPNG = async (content: string, options: MindMapExportOptions = {}) => {
        if (!content?.trim()) {
            feedback.msgError("暂无内容可导出");
            return;
        }

        const {
            scale = DEFAULT_EXPORT_SCALE,
            filename = "mindmap",
            folds = collectFolds(markmap.value?.state.data),
            ...markmapOptions
        } = options;

        // 离屏容器必须参与布局（不能用 display:none / opacity:0），否则 markmap 量不到节点文字尺寸
        const container = document.createElement("div");
        container.style.cssText = "position:fixed;left:-99999px;top:0;background:#ffffff;";
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
        svg.style.cssText = "display:block;background:#ffffff;";
        container.appendChild(svg);
        document.body.appendChild(container);

        let exportMarkmap: Markmap | null = null;
        feedback.loading("正在导出高清图片...");
        try {
            // duration: 0 —— 导出不需要过渡动画，否则会抓到节点动画中间态
            exportMarkmap = Markmap.create(svg, { autoFit: false, duration: 0, ...markmapOptions });
            const { root } = transformer.transform(content);
            if (folds.length) applyFolds(root, new Set(folds));
            await exportMarkmap.setData(root);
            // d3 的 0ms 过渡也是异步的，等它把最终样式落到 DOM
            await new Promise((resolve) => setTimeout(resolve, 100));

            const { x1, y1, x2, y2 } = exportMarkmap.state.rect;
            const naturalWidth = Math.max(x2 - x1, 1);
            const naturalHeight = Math.max(y2 - y1, 1);
            const factor = resolveExportScale(scale, naturalWidth, naturalHeight);
            const width = Math.ceil(naturalWidth * factor) + EXPORT_PADDING * 2;
            const height = Math.ceil(naturalHeight * factor) + EXPORT_PADDING * 2;

            // 让 SVG 视口覆盖整张图，再把内容按 factor 放大平移进视口，栅格化分辨率即 width×height
            svg.setAttribute("width", `${width}px`);
            svg.setAttribute("height", `${height}px`);
            svg.style.width = `${width}px`;
            svg.style.height = `${height}px`;
            exportMarkmap.g.attr(
                "transform",
                `translate(${EXPORT_PADDING - x1 * factor},${EXPORT_PADDING - y1 * factor}) scale(${factor})`
            );

            const canvas = await html2Canvas(container, {
                useCORS: true,
                backgroundColor: "#ffffff",
                width,
                height,
                scale: 1,
                logging: false,
            });
            const dataURL = canvas.toDataURL("image/png");
            const aTag = document.createElement("a");
            document.body.appendChild(aTag);
            aTag.href = dataURL;
            aTag.download = `${sanitizeFilename(filename)}.png`;
            aTag.target = "_blank";
            aTag.click();
            aTag.remove();
        } catch (error: any) {
            feedback.msgError(error?.message || error || "发生错误");
        } finally {
            feedback.closeLoading();
            exportMarkmap?.destroy();
            container.remove();
        }
    };

    return {
        toolbarContainer,
        markmap,
        isFullscreen,
        mindMapInit,
        mindMapFit,
        mindMapExportAsPNG,
    };
}
