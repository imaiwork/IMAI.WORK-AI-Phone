import { Transformer } from "markmap-lib";
import { Markmap, type IMarkmapOptions } from "markmap-view";
import { Toolbar } from "markmap-toolbar";
import "markmap-toolbar/dist/style.css";
import html2Canvas from "html2canvas";

interface CustomMarkmapOptions extends Partial<IMarkmapOptions> {}

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

    const mindMapExportAsPNG = (el: HTMLElement) => {
        markmap.value?.fit().then(() => {
            createCanvasPng(el);
        });
    };

    const createCanvasPng = async (el: HTMLElement) => {
        try {
            const canvas = await html2Canvas(el, {
                useCORS: true,
                backgroundColor: "transparent",
            });
            const dataURL = canvas.toDataURL(`image/png`);
            const aTag = document.createElement("a");
            document.body.appendChild(aTag);
            aTag.href = dataURL;
            aTag.download = "mindmap.png";
            aTag.target = "_blank";
            aTag.click();
            aTag.remove();
        } catch (error) {
            feedback.msgError(error || "发生错误");
        }
    };

    return {
        toolbarContainer,
        markmap,
        isFullscreen,
        mindMapInit,
        mindMapFit,
        mindMapExportAsPNG,
        createCanvasPng,
    };
}
