import QRCode from "qrcode";
import jsPDF from "jspdf";
import html2Canvas from "@/utils/html2canvas";
import feedback from "@/utils/feedback";

export function useShare(
    contentList: ComputedRef<any[]>,
    containerRef: Ref<HTMLDivElement | null>,
    getWebsiteConfig: any
) {
    const showShare = ref(false);
    const showPreviewShare = ref(false);
    const shareContentIndexList = ref<number[]>([]);
    const previewShareRef = ref<any>(null);

    const isAllSelected = computed(
        () => shareContentIndexList.value.length === contentList.value.length && contentList.value.length > 0
    );

    const handleShare = () => {
        showShare.value = true;
        shareContentIndexList.value = contentList.value
            .map((item, index) => (!item.error && !item.stop_reply ? index : false))
            .filter((v) => v !== false) as number[];
    };

    const handleSelectAll = () => {
        if (isAllSelected.value) {
            shareContentIndexList.value = [];
        } else {
            shareContentIndexList.value = contentList.value.map((_, index) => index);
        }
    };

    const handleCancelShare = () => {
        showShare.value = false;
        shareContentIndexList.value = [];
    };

    const handleShareContent = (index: number) => {
        const currentItem = contentList.value[index];
        const currentType = currentItem.type;
        const isCurrentSelected = shareContentIndexList.value.includes(index);

        if (currentType === 1) {
            const nextIndex = index + 1;
            const nextItem = contentList.value[nextIndex];
            if (isCurrentSelected) {
                shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== index);
                if (nextItem?.type === 2)
                    shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== nextIndex);
            } else {
                shareContentIndexList.value.push(index);
                if (nextItem?.type === 2) shareContentIndexList.value.push(nextIndex);
            }
        } else if (currentType === 2) {
            const prevIndex = index - 1;
            const prevItem = contentList.value[prevIndex];
            if (isCurrentSelected) {
                shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== index);
                if (prevItem?.type === 1)
                    shareContentIndexList.value = shareContentIndexList.value.filter((i) => i !== prevIndex);
            } else {
                shareContentIndexList.value.push(index);
                if (prevItem?.type === 1) shareContentIndexList.value.push(prevIndex);
            }
        }

        shareContentIndexList.value = [...new Set(shareContentIndexList.value)].sort((a, b) => a - b);
    };

    /** 构建导出用的离屏 DOM 容器 */
    const buildExportContainer = async (): Promise<HTMLDivElement> => {
        const clonedElement = containerRef.value!.cloneNode(true) as HTMLElement;
        const chatMessages = clonedElement.querySelectorAll(".chat-message");
        chatMessages.forEach((el, index) => {
            if (!shareContentIndexList.value.includes(index)) {
                el.remove();
            } else {
                el.classList.remove("is-selected", "is-share");
                (el as HTMLElement).style.backgroundColor = "transparent";
            }
        });

        const container = document.createElement("div");
        container.style.cssText = `position:absolute;left:-9999px;top:0;width:${
            containerRef.value!.offsetWidth
        }px;height:auto;background-color:#FFFFFF;`;

        // 顶部 Logo
        const header = document.createElement("div");
        header.style.padding = "20px";
        header.innerHTML = `
            <div style="height:100px;display:flex;align-items:center;justify-content:center;gap:10px;border-bottom:1px solid #DDDEE0;margin-bottom:20px;">
                <img src="${getWebsiteConfig.shop_logo}" style="width:60px;height:60px;border-radius:50%;" />
                <div style="font-size:24px;font-weight:bold;">${getWebsiteConfig.shop_name}</div>
            </div>`;
        container.appendChild(header);
        container.appendChild(clonedElement);

        // 底部二维码
        const qrcodeDataURL = await QRCode.toDataURL(window.location.href, { width: 256 });
        const footer = document.createElement("div");
        footer.innerHTML = `
            <div style="display:flex;flex-direction:column;align-items:center;padding:40px 0;">
                <div style="width:100px;height:100px;border:1px solid #E5E7EB;border-radius:4px;padding:4px;background:#fff;">
                    <img src="${qrcodeDataURL}" style="width:100%;height:100%;" />
                </div>
                <div style="margin-top:10px;color:#666;">${getWebsiteConfig.shop_name}</div>
            </div>`;
        container.appendChild(footer);

        document.body.appendChild(container);
        await nextTick();
        return container;
    };

    const generateShareContent = async (isPDF = false) => {
        if (shareContentIndexList.value.length === 0) {
            feedback.msgError("请选择要导出的内容");
            return;
        }
        showPreviewShare.value = true;
        await nextTick();

        let container: HTMLDivElement | null = null;
        try {
            container = await buildExportContainer();

            if (isPDF) {
                const canvas = await html2Canvas(container, {
                    useCORS: true,
                    backgroundColor: "#FFFFFF",
                    scale: 2,
                });
                const imgData = canvas.toDataURL("image/png");
                const pdf = new jsPDF("p", "mm", "a4");
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                const imgHeightInPdf = (container.offsetHeight * pdfWidth) / container.offsetWidth;
                let heightLeft = imgHeightInPdf;
                let position = 0;
                pdf.addImage(imgData, "PNG", 0, position, pdfWidth, imgHeightInPdf);
                heightLeft -= pdfHeight;
                while (heightLeft > 0) {
                    position = heightLeft - imgHeightInPdf;
                    pdf.addPage();
                    pdf.addImage(imgData, "PNG", 0, position, pdfWidth, imgHeightInPdf);
                    heightLeft -= pdfHeight;
                }
                pdf.save(`${getWebsiteConfig.shop_name}-对话记录.pdf`);
                feedback.msgSuccess("PDF生成成功！");
            } else {
                const canvas = await html2Canvas(container, {
                    useCORS: true,
                    backgroundColor: "#ffffff",
                    scale: 2,
                });
                previewShareRef.value?.open();
                previewShareRef.value?.setContent(canvas.toDataURL("image/png", 1.0));
            }
        } catch (error) {
            console.error(`${isPDF ? "PDF" : "图片"}生成失败:`, error);
            feedback.msgError("导出失败，请重试");
        } finally {
            if (container && document.body.contains(container)) {
                document.body.removeChild(container);
            }
        }
    };

    const handleGenerateImage = () => generateShareContent(false);
    const handleGeneratePDF = () => generateShareContent(true);
    const handleGenerateLink = () => {
        if (shareContentIndexList.value.length === 0) return;
        // TODO: 生成分享链接
    };

    return {
        showShare,
        showPreviewShare,
        shareContentIndexList,
        previewShareRef,
        isAllSelected,
        handleShare,
        handleSelectAll,
        handleCancelShare,
        handleShareContent,
        handleGenerateImage,
        handleGeneratePDF,
        handleGenerateLink,
    };
}
