import { Transformer } from "markmap-lib";
import { Markmap, type IMarkmapOptions } from "markmap-view";
import { Toolbar } from "markmap-toolbar";
import "markmap-toolbar/dist/style.css";

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

    const mindMapExportAsPNG = (svgElement: SVGSVGElement) => {
        markmap.value?.fit().then(() => {
            createCanvasPng(svgElement);
        });
    };

    const createCanvasPng = (svgElement: SVGSVGElement) => {
        console.log(svgElement);
        const svgData = new XMLSerializer().serializeToString(svgElement);
        const canvas = document.createElement("canvas");
        const svgSize = svgElement.getBoundingClientRect();
        canvas.width = svgSize.width;
        canvas.height = svgSize.height;
        const ctx = canvas.getContext("2d");
        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        const img = new Image();
        img.onload = () => {
            ctx.drawImage(img, 0, 0);
            const pngFile = canvas.toDataURL("image/png");
            const downloadLink = document.createElement("a");
            downloadLink.href = pngFile;
            downloadLink.download = "mindmap.png";
            downloadLink.click();
        };
        img.src = "data:image/svg+xml;base64," + btoa(unescape(encodeURIComponent(svgData)));
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
