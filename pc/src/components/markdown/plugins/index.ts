import Mermaid from "mermaid";
import Murmur from "./murmurhash3_gc.js";
import hljs from "highlight.js";
import * as echarts from "echarts";

const highlightContent = (str, lang, className = "") => {
    return `<pre class="hljs ${className}"><code>${str}</code></pre>`;
};

const EChartsPlugin = (md, opts: EchartsPluginOptions = {}) => {
    const defaultRenderer = md.renderer.rules.fence.bind(md.renderer.rules);
    const chartId = `echarts${Date.now().toString()}`;

    md.renderer.rules.fence = function (tokens, idx, options, env, self) {
        const token = tokens[idx];
        const code = token.content.trim();

        if (token.info.startsWith("echarts")) {
            // 生成一个echart容器
            const echartsTemplate = () => {
                const defaultWidth = opts.defaultWidth || "600px";
                const defaultHeight = opts.defaultHeight || "400px";

                return `<div id="${chartId}" style="display: flex; align-items: center; justify-content: center; min-width: ${defaultWidth}; height: ${defaultHeight}; position: relative; overflow: auto;"></div>`;
            };
            // 错误处理
            const errorHandler = (chartDom) => {
                const html = highlightContent(token.content.trim(), "json", "h-full w-full");
                if (!chartDom.querySelector(`.error-container`)) {
                    const errorContainer = document.createElement("div");
                    errorContainer.className = "error-container w-full h-full ";
                    errorContainer.innerHTML = html;
                    chartDom.appendChild(errorContainer);
                } else {
                    chartDom.querySelector(`.error-container`).innerHTML = html;
                }
            };

            setTimeout(() => {
                const chartDom = document.getElementById(chartId);
                if (chartDom) {
                    try {
                        const option = isJson(token.content.trim()) ? JSON.parse(token.content.trim()) : "";
                        if (option) {
                            const myChart = echarts.init(chartDom);
                            myChart.setOption(option);
                        } else {
                            errorHandler(chartDom);
                        }
                    } catch (_) {}
                }
            }, 0);
            return echartsTemplate();
        }

        return defaultRenderer(tokens, idx, options, env, self);
    };
};

interface EchartsPluginOptions {
    defaultWidth?: string;
    defaultHeight?: string;
}

EChartsPlugin.default = {
    defaultWidth: "600px",
    defaultHeight: "400px",
} as EchartsPluginOptions;

const htmlEntities = (str) => String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

const MermaidChart = (code, title = "") => {
    try {
        // 先检查语法是否正确
        Mermaid.parse(code);

        var needsUniqueId = "render" + Murmur(code, 42).toString();
        // @ts-ignore
        Mermaid.mermaidAPI.render(needsUniqueId, code, (sc) => {
            code = sc;
        });

        if (title && String(title).length) {
            title = `<div class="mermaid-title">${htmlEntities(title)}</div>`;
        }

        return `<div class="mermaid">${title}${code}</div>`;
    } catch (err) {
        // 语法错误处理
        return `<pre>${htmlEntities(err.str)}</pre>`;
    }
};

const MermaidPlugIn = (md, opts) => {
    Mermaid.initialize(Object.assign(MermaidPlugIn.default, opts));
    const defaultRenderer = md.renderer.rules.fence.bind(md.renderer.rules);

    md.renderer.rules.fence = (tokens, idx, opts, env, self) => {
        const token = tokens[idx];
        const code = token.content.trim();
        if (token.info.startsWith("mermaid")) {
            let title;
            const spc = token.info.indexOf(" ", 7);
            if (spc > 0) {
                title = token.info.slice(spc + 1);
            }
            return MermaidChart(code, title);
        }
        return defaultRenderer(tokens, idx, opts, env, self);
    };
};

interface FlowchartConfig {
    htmlLabels: boolean;
    useMaxWidth: boolean;
}

interface MermaidConfig {
    startOnLoad?: boolean;
    securityLevel?: string;
    theme?: string;
    flowchart?: FlowchartConfig;
    suppressErrorRendering?: boolean;
}

MermaidPlugIn.default = {
    startOnLoad: false,
    securityLevel: "true",
    theme: "default",
    flowchart: {
        htmlLabels: false,
        useMaxWidth: true,
    },
    suppressErrorRendering: true,
} as MermaidConfig;

export { EChartsPlugin, MermaidPlugIn };
