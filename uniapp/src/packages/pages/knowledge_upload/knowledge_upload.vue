<template>
    <view class="min-h-screen bg-[#F2F4FA] flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#F2F4FA' }"
            title="添加新文件"
            title-bold
            back-icon-color="#1D2129" />

        <scroll-view scroll-y class="flex-1 min-h-0">
            <!-- 选择导入方式 -->
            <view class="sec-bar">选择导入方式</view>
            <view
                v-for="way in WAYS"
                :key="way.key"
                class="way-card"
                :class="{ selected: currWay === way.key }"
                @click="selectWay(way.key)">
                <view class="flex items-center gap-x-[20rpx]">
                    <view class="way-icon" :class="currWay === way.key ? 'way-icon--on' : 'way-icon--off'">
                        <u-icon :name="way.icon" :color="currWay === way.key ? '#ffffff' : '#94A3B8'" :size="34" />
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="text-[28rpx] font-bold text-[#1D2129]">{{ way.title }}</text>
                        <text class="block text-[22rpx] text-[#94A3B8] mt-[4rpx] leading-snug">{{ way.desc }}</text>
                    </view>
                    <view class="way-check" :class="{ 'way-check--on': currWay === way.key }">
                        <u-icon v-if="currWay === way.key" name="checkmark" color="#ffffff" :size="22" />
                    </view>
                </view>
                <view class="mt-[16rpx] pt-[16rpx] border-0 border-t border-solid border-[#F1F5F9]">
                    <text class="text-[22rpx] text-[#94A3B8]">{{ way.fmt }}</text>
                </view>
            </view>

            <!-- 上传文件 / 网页链接 -->
            <view class="sec-bar">{{ currWay === "url" ? "网页链接" : "上传文件" }}</view>
            <view class="upload-area">
                <!-- 网页链接输入 -->
                <view v-if="currWay === 'url'" class="url-input-wrap">
                    <input v-model="urlValue" class="url-input" placeholder="请输入网页 URL 链接，如 https://..." />
                </view>

                <!-- 文件上传区 -->
                <template v-else>
                    <view class="upload-zone" @click="handleChoose">
                        <view class="upload-icon">
                            <u-icon name="arrow-upward" color="#2563EB" :size="40" />
                        </view>
                        <text class="text-[28rpx] text-[#1D2129]">
                            点击上传<text class="text-[#2F73F6] font-semibold">，选择本地文件</text>
                        </text>
                        <text class="block text-[22rpx] text-[#94A3B8] mt-[6rpx] italic">{{ currFmtTip }}</text>
                    </view>

                    <view v-if="selectedFiles.length" class="mt-[20rpx] flex flex-col gap-y-[12rpx]">
                        <view v-for="(f, index) in selectedFiles" :key="index" class="selected-item">
                            <view class="si-icon">
                                <u-icon name="file-text" color="#ffffff" :size="28" />
                            </view>
                            <view class="flex-1 min-w-0">
                                <text class="si-name">{{ f.name }}</text>
                                <text class="block text-[22rpx] text-[#94A3B8] mt-[2rpx]">
                                    {{ getExt(f.name) }} · {{ formatFileSize(f.size)
                                    }}<text v-if="f.data && f.data.length"> · {{ f.data.length }} 段</text>
                                </text>
                            </view>
                            <view class="flex items-center gap-x-[6rpx] flex-shrink-0">
                                <u-icon name="checkmark" color="#16A34A" :size="24" />
                                <text class="text-[22rpx] font-semibold text-[#16A34A]">就绪</text>
                            </view>
                            <view
                                class="w-[40rpx] h-[40rpx] flex items-center justify-center flex-shrink-0"
                                @click.stop="removeFile(index)">
                                <u-icon name="close" color="#C0C8D8" :size="22" />
                            </view>
                        </view>
                    </view>
                </template>
            </view>
            <view class="h-[180rpx]"></view>
        </scroll-view>

        <view class="bottom-cta">
            <view class="cta-btn" :class="{ 'cta-btn--disabled': !canSubmit }" @click="lockFn">
                <text class="text-white font-bold text-[32rpx]">确认上传并自动解析</text>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { vectorKnowledgeBaseFileImport, vectorKnowledgeBaseWebHtmlCapture } from "@/api/knowledge_base";
import { uploadFile } from "@/api/app";
import { chooseFile } from "@/components/file-upload/choose-file";
import { splitText2Chunks } from "@/utils/text-splitter";
import { formatFileSize } from "@/utils/util";
import { useLockFn } from "@/hooks/useLockFn";
import config from "@/config";

// 文本切片长度（与 PC 默认一致）
const CHUNK_LEN = 512;
// 通用文档：当前仅支持可本地解析的纯文本（pdf/docx 解析库无法在小程序运行）
const DOC_EXTS = ["txt", "md"];
// 问答对：移动端本地解析 CSV（xlsx 依赖较重，暂不支持，可另存为 CSV）
const QA_EXTS = ["csv"];

enum WayKey {
    DOC = "doc",
    QA = "qa",
    URL = "url",
}
// method 与 PC/Admin 对齐：1=文档 2=问答对 4=网页
const WAYS = [
    {
        key: WayKey.DOC,
        icon: "file-text",
        title: "通用文档导入",
        desc: "选择文本文件，直接按其分段进行处理",
        fmt: "支持 .TXT, .MD（移动端暂不支持 PDF/DOCX）",
        method: 1,
    },
    {
        key: WayKey.QA,
        icon: "list",
        title: "问答对导入",
        desc: "批量导入问答对，效果最佳",
        fmt: "支持 .CSV（Excel 请另存为 CSV）",
        method: 2,
    },
    {
        key: WayKey.URL,
        icon: "share",
        title: "网页解析",
        desc: "输入网页链接，快速导入内容",
        fmt: "支持 URL 链接",
        method: 4,
    },
];

const kbId = ref<string | number>("");
const currWay = ref<string>(WayKey.DOC);
const selectedFiles = ref<any[]>([]);
const urlValue = ref("");

onLoad((options: any) => {
    kbId.value = options?.id || "";
});

const currFmtTip = computed(() => WAYS.find((w) => w.key === currWay.value)?.fmt || "");

const canSubmit = computed(() =>
    currWay.value === WayKey.URL ? !!urlValue.value.trim() : selectedFiles.value.length > 0,
);

const getExt = (name: string) => (name?.split(".").pop() || "FILE").toUpperCase();

const selectWay = (key: string) => {
    if (currWay.value === key) return;
    currWay.value = key;
    selectedFiles.value = [];
    urlValue.value = "";
};

const getChosenFilePath = (file: any) => file?.tempFilePath || file?.path || "";

const toAbsoluteUrl = (uri: string) => {
    if (!uri) return "";
    if (/^https?:\/\//i.test(uri)) return uri;
    const base = String(config.baseUrl || "").replace(/\/?$/, "/");
    return `${base}${String(uri).replace(/^\//, "")}`;
};

const readBlobAsText = (blob: Blob) =>
    new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ""));
        reader.onerror = () => reject("读取文件失败");
        reader.readAsText(blob);
    });

const readPathAsText = (filePath: string) =>
    new Promise<string>((resolve, reject) => {
        uni.getFileSystemManager().readFile({
            filePath,
            encoding: "utf8",
            success: (res: any) => resolve(res.data as string),
            fail: (err: any) => reject(err?.errMsg || "读取文件失败"),
        });
    });

const fetchUrlAsText = (url: string) =>
    new Promise<string>((resolve, reject) => {
        uni.request({
            url,
            method: "GET",
            dataType: "text",
            responseType: "text",
            success: (res) => {
                if (res.statusCode && (res.statusCode < 200 || res.statusCode >= 300)) {
                    reject("读取文件失败");
                    return;
                }
                resolve(typeof res.data === "string" ? res.data : String(res.data ?? ""));
            },
            fail: () => reject("读取文件失败"),
        });
    });

/**
 * 跨端读取选中文本文件并上传。
 * 微信小程序 chooseMessageFile 返回的 http://tmp/* 路径常无法 readFile，
 * 但 uploadFile 可用：本地读失败时先上传再拉远程内容。
 */
const prepareTextFile = async (file: any) => {
    const filePath = getChosenFilePath(file);
    if (!filePath) throw "读取文件失败";

    let content = "";

    // #ifdef H5
    if (file?.file) {
        content = await readBlobAsText(file.file);
    } else {
        try {
            const res = await fetch(filePath);
            if (!res.ok) throw new Error("fail");
            content = await res.text();
        } catch {
            content = "";
        }
    }
    // #endif

    // #ifndef H5
    try {
        content = await readPathAsText(filePath);
    } catch {
        content = "";
    }
    // #endif

    const up: any = await uploadFile("file", { filePath });
    const url = up?.uri || up?.url || "";
    if (!url) throw "上传失败";

    if (!content?.trim()) {
        content = await fetchUrlAsText(toAbsoluteUrl(url));
    }
    if (!content?.trim()) throw "读取文件失败";

    return {
        name: file.name,
        size: file.size || 0,
        url,
        content,
    };
};

/** 轻量 CSV 解析（对齐 Admin vector-import，支持引号转义） */
const parseCsv = (text: string): string[][] => {
    const rows: string[][] = [];
    let row: string[] = [];
    let cell = "";
    let inQuotes = false;
    for (let index = 0; index < text.length; index++) {
        const char = text[index];
        const nextChar = text[index + 1];
        if (char === '"' && inQuotes && nextChar === '"') {
            cell += '"';
            index++;
            continue;
        }
        if (char === '"') {
            inQuotes = !inQuotes;
            continue;
        }
        if (char === "," && !inQuotes) {
            row.push(cell.trim());
            cell = "";
            continue;
        }
        if ((char === "\n" || char === "\r") && !inQuotes) {
            if (char === "\r" && nextChar === "\n") index++;
            row.push(cell.trim());
            rows.push(row);
            row = [];
            cell = "";
            continue;
        }
        cell += char;
    }
    row.push(cell.trim());
    rows.push(row);
    return rows;
};

const readCsvRows = (text: string): Record<string, string>[] => {
    const rows = parseCsv(text.replace(/^\uFEFF/, "")).filter((row) => row.some((cell) => cell));
    if (!rows.length) return [];
    const header = rows.shift() || [];
    return rows.map((row) => {
        const item: Record<string, string> = {};
        header.forEach((key, index) => {
            item[key || String(index)] = row[index] || "";
        });
        return item;
    });
};

/** 兼容表头：q/question/问题、a/answer/答案，或前两列 */
const normalizeQuestionRows = (rows: Record<string, string>[]) => {
    if (!Array.isArray(rows)) return [];
    return rows
        .map((row) => {
            const values = Object.values(row || {});
            const q = row.q ?? row.question ?? row.qusetion ?? row["问题"] ?? values[0] ?? "";
            const a = row.a ?? row.answer ?? row["答案"] ?? values[1] ?? "";
            return {
                q: String(q || "").trim(),
                a: String(a || "").trim(),
            };
        })
        .filter((item) => item.q || item.a);
};

// 通用文档：选 txt/md -> 本地读取内容 -> 切片 -> 上传文件取 path
const handleChooseDoc = async () => {
    let res: any;
    try {
        res = await chooseFile({ type: "file", count: 9, extension: DOC_EXTS });
    } catch (error) {
        return;
    }
    const files = (res?.tempFiles || []).filter((f: any) => DOC_EXTS.includes(getExt(f.name).toLowerCase()));

    if (!files.length) {
        uni.$u.toast("仅支持 txt / md 文本文件");
        return;
    }
    uni.showLoading({ title: "解析中...", mask: true });
    try {
        for (const f of files) {
            const prepared = await prepareTextFile(f);
            const data = splitText2Chunks(prepared.content, CHUNK_LEN).map((q) => ({ q, a: "" }));
            selectedFiles.value.push({
                name: prepared.name,
                url: prepared.url,
                size: prepared.size,
                data,
            });
        }
    } catch (error: any) {
        uni.$u.toast(typeof error === "string" ? error : "解析失败");
    } finally {
        uni.hideLoading();
    }
};

// 问答对：选 csv -> 本地解析为 {q,a}[] -> 上传文件取 path（与 PC/Admin 一致）
const handleChooseQa = async () => {
    let res: any;
    try {
        res = await chooseFile({ type: "file", count: 9, extension: QA_EXTS });
    } catch (error) {
        return;
    }
    const files = (res?.tempFiles || []).filter((f: any) => QA_EXTS.includes(getExt(f.name).toLowerCase()));
    if (!files.length) {
        uni.$u.toast("仅支持 csv 文件，Excel 请另存为 CSV");
        return;
    }
    uni.showLoading({ title: "解析中...", mask: true });
    try {
        for (const f of files) {
            const prepared = await prepareTextFile(f);
            const data = normalizeQuestionRows(readCsvRows(prepared.content));
            if (!data.length) {
                uni.$u.toast(`「${f.name}」解析结果为空，已忽略`);
                continue;
            }
            selectedFiles.value.push({
                name: prepared.name,
                url: prepared.url,
                size: prepared.size,
                data,
            });
        }
    } catch (error: any) {
        uni.$u.toast(typeof error === "string" ? error : "解析失败");
    } finally {
        uni.hideLoading();
    }
};

const handleChoose = () => {
    if (currWay.value === WayKey.DOC) handleChooseDoc();
    else if (currWay.value === WayKey.QA) handleChooseQa();
};

const removeFile = (index: number) => selectedFiles.value.splice(index, 1);

const handleConfirm = async () => {
    if (!canSubmit.value) return;
    const way = WAYS.find((w) => w.key === currWay.value)!;

    uni.showLoading({ title: "解析中...", mask: true });
    try {
        let documents: any[];
        if (currWay.value === WayKey.URL) {
            // 网页解析需先调 capture 提取正文，再带 data 入库（与 PC/Admin 一致）
            const urls = urlValue.value
                .split("\n")
                .map((item) => item.trim())
                .filter(Boolean);
            const captured = await vectorKnowledgeBaseWebHtmlCapture({ url: urls });
            documents = (captured || []).map((item: any) => ({
                name: item.url,
                path: item.url,
                size: 0,
                data: [{ q: item.content, a: "" }],
            }));
            if (!documents.length) {
                throw "网页解析结果为空";
            }
        } else {
            documents = selectedFiles.value.map((f) => ({
                name: f.name,
                path: f.url,
                size: f.size,
                data: f.data || [],
            }));
            if (currWay.value === WayKey.QA && documents.some((d) => !d.data?.length)) {
                throw "问答对解析结果为空，请检查 CSV 内容";
            }
        }

        await vectorKnowledgeBaseFileImport({ kb_id: kbId.value, method: way.method, documents });
        uni.hideLoading();
        uni.showToast({ title: "上传成功，解析中", icon: "none" });
        uni.$emit("knowledgeDocUpdated");
        setTimeout(() => uni.navigateBack(), 800);
    } catch (error: any) {
        uni.hideLoading();
        uni.$u.toast(typeof error === "string" ? error : "上传失败");
    }
};

const { lockFn } = useLockFn(handleConfirm);
</script>

<style lang="scss" scoped>
.sec-bar {
    @apply flex items-center text-[28rpx] font-bold text-[#1D2129] px-[32rpx] pt-[32rpx] pb-[16rpx];
    &::before {
        content: "";
        @apply w-[6rpx] h-[28rpx] bg-[#2563EB] rounded-full mr-[12rpx] flex-shrink-0;
    }
}
.way-card {
    @apply bg-white mx-[32rpx] mb-[20rpx] rounded-[28rpx] px-[28rpx] py-[24rpx] border border-solid border-[transparent] active:opacity-95;
    &.selected {
        @apply border-primary bg-[#F8FAFE];
    }
}
.way-icon {
    @apply w-[72rpx] h-[72rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0;
}
.way-icon--off {
    @apply bg-[#F1F5F9];
}
.way-icon--on {
    background: linear-gradient(135deg, #3d82f7, #2563eb);
}
.way-check {
    @apply w-[40rpx] h-[40rpx] rounded-full border-[3rpx] border-solid border-[#E5EAF3] flex items-center justify-center flex-shrink-0;
    &--on {
        @apply border-primary bg-primary;
    }
}
.upload-area {
    @apply bg-white mx-[32rpx] rounded-[28rpx] p-[28rpx];
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
.upload-zone {
    @apply bg-[#F7F9FC] rounded-[24rpx] py-[72rpx] px-[40rpx] flex flex-col items-center text-center active:bg-[#EBF2FF];
    border: 2rpx dashed #bad4ff;
}
.upload-icon {
    @apply w-[112rpx] h-[112rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[24rpx];
}
.url-input-wrap {
    @apply bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[24rpx] border border-solid border-[#E5EAF3];
}
.url-input {
    @apply w-full text-[28rpx] text-[#1D2129];
}
.selected-item {
    @apply flex items-center gap-x-[16rpx] bg-[#F8FAFE] rounded-[16rpx] px-[20rpx] py-[18rpx];
}
.si-icon {
    @apply w-[56rpx] h-[56rpx] rounded-[14rpx] flex items-center justify-center flex-shrink-0;
    background: linear-gradient(135deg, #60a5fa, #2563eb);
}
.si-name {
    @apply text-[26rpx] font-semibold text-[#1D2129] truncate block;
}
.bottom-cta {
    @apply fixed left-0 right-0 bottom-0 bg-white px-[32rpx] pt-[20rpx] z-20;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #eaeef5;
}
.cta-btn {
    @apply w-full h-[96rpx] rounded-[28rpx] flex items-center justify-center active:opacity-85;
    background: linear-gradient(135deg, #3d82f7, #2563eb);
    box-shadow: 0 12rpx 40rpx rgba(47, 115, 246, 0.28);
    &--disabled {
        background: #e5eaf3;
        box-shadow: none;
        @apply pointer-events-none;
        text {
            @apply text-[#94A3B8];
        }
    }
}
</style>
