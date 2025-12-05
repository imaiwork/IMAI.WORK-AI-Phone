import html2Canvas from '@/assets/js/html2canvas.esm.js'

export default html2Canvas

interface _Html2CanvasOptions {
    allowTaint: boolean //是否允许跨域图像。会污染画布，导致无法使用canvas.toDataURL 方法
    backgroundColor: string //画布背景色（如果未在DOM中指定）。设置null为透明
    canvas: HTMLCanvasElement //现有canvas元素用作绘图的基础
    foreignObjectRendering: string //如果浏览器支持，是否使用ForeignObject渲染
    imageTimeout: number //加载图像的超时时间（以毫秒为单位）。设置0为禁用超时。
    ignoreElements: (element) => false //谓词功能，可从渲染中删除匹配的元素。
    logging: boolean //启用日志以进行调试
    onclone: () => void | null //克隆文档以进行渲染时调用的回调函数可用于修改将要渲染的内容，而不会影响原始源文档。
    proxy: string | null //代理将用于加载跨域图像的网址。如果保留为空，则不会加载跨域图像。
    removeContainer: boolean //是否清除html2canvas临时创建的克隆DOM元素
    scale: number //用于渲染的比例。默认为浏览器设备像素比率。
    useCORS: boolean //是否尝试使用CORS从服务器加载图像
    width: number //canvas的宽度
    height: number //canvas的高度
    x: number //裁剪画布X坐标
    y: number //裁剪画布y坐标
    scrollX: number //渲染元素时要使用的x滚动位置（例如，如果Element使用position: fixed）
    scrollY: number //呈现元素时要使用的y-scroll位置（例如，如果Element使用position: fixed）
    windowWidth: number //渲染时使用的窗口宽度Element，这可能会影响媒体查询之类的内容
    windowHeight: number //渲染时要使用的窗口高度Element，这可能会影响媒体查询之类的内容
}

export type Html2CanvasOptions = Partial<_Html2CanvasOptions>
