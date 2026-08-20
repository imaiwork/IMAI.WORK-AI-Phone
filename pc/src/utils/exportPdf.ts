import html2canvas from '@/utils/html2canvas'
import type { Html2CanvasOptions } from '@/utils/html2canvas'

/**
 * 把一段 DOM 导出成 A4 多页 PDF。
 *
 * 为什么走 html2canvas 光栅化而不是服务端生成：
 * 服务端出 PDF（dompdf/mpdf）必须内嵌中文字体，一套思源黑体约 10MB，
 * 而 imai 仓库已经超出 gitee 819MB 配额，再塞字体会直接把推送推死。
 * 光栅化在浏览器里用系统字体渲染，中文零配置，也不增加仓库体积。
 * 代价是产出的是图片而非可选中文本 —— 对"给客户看的诊断报告"这个用途可以接受。
 */

const A4 = { w: 595.28, h: 841.89 } // jsPDF pt 单位下的 A4
const MARGIN = 24

export interface ExportPdfOptions {
    /** 文件名，不含扩展名 */
    filename?: string
    /** 渲染倍率，越高越清晰但越慢/越大 */
    scale?: number
    /** 不希望被切断的元素选择器（表格、卡片组等） */
    avoidBreakSelector?: string
}

/**
 * 找出所有「不能被切断」的元素在画布上的纵向区间，
 * 供分页时避开。返回值单位与 canvas 像素一致。
 */
function collectBlocks(root: HTMLElement, selector: string, scale: number): Array<[number, number]> {
    const rootTop = root.getBoundingClientRect().top
    const out: Array<[number, number]> = []
    root.querySelectorAll(selector).forEach((el) => {
        const r = (el as HTMLElement).getBoundingClientRect()
        if (r.height <= 0) return
        out.push([(r.top - rootTop) * scale, (r.bottom - rootTop) * scale])
    })
    return out.sort((a, b) => a[0] - b[0])
}

/**
 * 给定本页起点，算出一个不会切断整块内容的页高。
 * 若某块横跨候选切点，就把切点上移到该块顶部；块本身比一页还高时只能硬切。
 */
function findCut(start: number, maxH: number, total: number, blocks: Array<[number, number]>): number {
    let cut = Math.min(start + maxH, total)
    if (cut >= total) return total
    for (const [top, bottom] of blocks) {
        if (top < cut && bottom > cut) {
            // 整块能放进一页才值得上移，否则硬切，避免死循环
            if (bottom - top <= maxH && top > start) cut = top
            break
        }
    }
    return cut > start ? cut : Math.min(start + maxH, total)
}

export async function exportElementToPdf(el: HTMLElement, opts: ExportPdfOptions = {}): Promise<void> {
    const { filename = 'report', scale = 2, avoidBreakSelector = 'table,.no-break' } = opts

    // 报告在弹窗里是 max-height + overflow-y:auto 的滚动容器，
    // 直接截会只拿到可视区那一屏。临时解除高度限制与滚动，截完还原。
    const saved = {
        maxHeight: el.style.maxHeight,
        overflow: el.style.overflow,
        height: el.style.height,
    }
    el.style.maxHeight = 'none'
    el.style.overflow = 'visible'
    el.style.height = 'auto'

    let canvas: HTMLCanvasElement
    try {
        // 解除限制后重新量，块位置才是完整文档里的位置
        const blocks = collectBlocks(el, avoidBreakSelector, scale)
        canvas = await (html2canvas as any)(el, {
            scale,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            windowWidth: el.scrollWidth,
            windowHeight: el.scrollHeight,
            height: el.scrollHeight,
        } as Html2CanvasOptions)
        return await paginate(canvas, blocks, filename)
    } finally {
        el.style.maxHeight = saved.maxHeight
        el.style.overflow = saved.overflow
        el.style.height = saved.height
    }
}

/** 把整张画布按 A4 切页写进 PDF */
async function paginate(canvas: HTMLCanvasElement, blocks: Array<[number, number]>, filename: string): Promise<void> {
    const { jsPDF } = await import('jspdf')

    const pdf = new jsPDF('p', 'pt', 'a4')
    const contentW = A4.w - MARGIN * 2
    const pxPerPt = canvas.width / contentW          // 画布像素 → PDF pt 的换算
    const pageMaxPx = (A4.h - MARGIN * 2) * pxPerPt  // 一页能放多少画布像素

    let y = 0
    let first = true
    while (y < canvas.height) {
        const cut = findCut(y, pageMaxPx, canvas.height, blocks)
        const sliceH = cut - y

        const slice = document.createElement('canvas')
        slice.width = canvas.width
        slice.height = sliceH
        const ctx = slice.getContext('2d')
        if (!ctx) break
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, slice.width, slice.height)
        ctx.drawImage(canvas, 0, y, canvas.width, sliceH, 0, 0, canvas.width, sliceH)

        if (!first) pdf.addPage()
        pdf.addImage(slice.toDataURL('image/jpeg', 0.92), 'JPEG', MARGIN, MARGIN, contentW, sliceH / pxPerPt)
        first = false
        y = cut
    }

    pdf.save(`${filename}.pdf`)
}
