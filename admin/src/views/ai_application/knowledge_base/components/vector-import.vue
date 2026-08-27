<template>
    <el-drawer v-model="drawerVisible" title="添加新文件" size="80%" destroy-on-close @close="close">
        <div class="h-full flex flex-col">
            <div class="mb-4 flex items-center gap-3">
                <span class="text-sm shrink-0">
                    <span class="text-danger mr-1">*</span>所属知识库
                </span>
                <el-select
                    v-model="selectedKbId"
                    class="w-[320px]"
                    filterable
                    remote
                    clearable
                    placeholder="请选择知识库"
                    :remote-method="getKnLists"
                    :loading="knLoading">
                    <el-option
                        v-for="item in knLists"
                        :key="item.id"
                        :label="item.name"
                        :value="`${item.id}`" />
                </el-select>
            </div>
            <el-tabs v-model="activeType" class="mb-4" @tab-click="handleTypeChange">
                <el-tab-pane label="通用文档导入" :name="ImportType.DOCUMENT" />
                <el-tab-pane label="问答对导入" :name="ImportType.QUESTION" />
                <el-tab-pane label="网页解析" :name="ImportType.WEB" />
            </el-tabs>

            <div class="mb-4" v-if="activeType !== ImportType.WEB" v-loading="loading">
                <el-upload
                    ref="uploadRef"
                    drag
                    multiple
                    :auto-upload="false"
                    :show-file-list="false"
                    :accept="uploadAccept"
                    :on-change="handleFileChange">
                    <div class="py-6">
                        <Icon name="el-icon-UploadFilled" :size="36" />
                        <div class="mt-2">拖拽文件到这里，或 <span class="text-primary">点击选择文件</span></div>
                        <div class="text-xs text-tx-secondary mt-1">支持 {{ uploadAccept }}</div>
                    </div>
                </el-upload>
            </div>

            <div class="mb-4" v-else>
                <el-input
                    v-model="webUrls"
                    type="textarea"
                    :rows="4"
                    resize="none"
                    placeholder="请输入网页地址，多个地址请换行"
                    class="mb-3" />
                <el-button type="primary" :loading="webLoading" @click="handleCaptureWeb">开始解析</el-button>
            </div>

            <div v-if="activeType === ImportType.DOCUMENT && documents.length" class="mb-4 flex items-center gap-3">
                <span class="text-sm text-tx-secondary">分段长度</span>
                <el-input-number v-model="stageLen" :min="100" :max="30000" :step="100" controls-position="right" />
                <el-button @click="handleResplit">重新预览</el-button>
            </div>

            <div class="grow min-h-0 flex gap-4" v-if="documents.length">
                <div class="w-[260px] flex-shrink-0 border border-br rounded overflow-hidden">
                    <div class="px-4 py-3 bg-page font-medium">已添加文件 ({{ documents.length }})</div>
                    <el-scrollbar>
                        <div class="p-2">
                            <div
                                v-for="(item, index) in documents"
                                :key="`${item.name}-${index}`"
                                class="file-item"
                                :class="{ active: currentIndex === index }"
                                @click="currentIndex = index">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate">{{ item.name }}</div>
                                    <div class="text-xs text-tx-secondary">{{ item.data.length }} 条内容</div>
                                </div>
                                <el-button link type="danger" @click.stop="handleDeleteFile(index)">删除</el-button>
                            </div>
                        </div>
                    </el-scrollbar>
                </div>

                <div class="flex-1 min-w-0 border border-br rounded overflow-hidden flex flex-col">
                    <div class="px-4 py-3 bg-page flex items-center justify-between">
                        <span class="font-medium">内容预览</span>
                        <span class="text-xs text-tx-secondary"> {{ currentDocument?.data.length || 0 }} 条 </span>
                    </div>
                    <div class="grow min-h-0">
                        <el-scrollbar>
                            <el-table :data="currentDocument?.data || []">
                                <el-table-column type="index" width="60" />
                                <el-table-column
                                    :label="activeType === ImportType.QUESTION ? '问题' : '文档内容'"
                                    min-width="260">
                                    <template #default="{ row }">
                                        <el-input
                                            v-model="row.q"
                                            type="textarea"
                                            :rows="3"
                                            resize="none"
                                            placeholder="请输入内容" />
                                    </template>
                                </el-table-column>
                                <el-table-column v-if="activeType === ImportType.QUESTION" label="答案" min-width="260">
                                    <template #default="{ row }">
                                        <el-input
                                            v-model="row.a"
                                            type="textarea"
                                            :rows="3"
                                            resize="none"
                                            placeholder="请输入答案" />
                                    </template>
                                </el-table-column>
                                <el-table-column label="操作" width="80" fixed="right">
                                    <template #default="{ $index }">
                                        <el-button link type="danger" @click="handleDeleteChunk($index)"
                                            >删除</el-button
                                        >
                                    </template>
                                </el-table-column>
                            </el-table>
                        </el-scrollbar>
                    </div>
                </div>
            </div>

            <div v-else class="grow min-h-0 flex items-center justify-center">
                <el-empty description="暂无导入内容" />
            </div>

            <div class="pt-4 mt-4 border-t border-br flex justify-end gap-3">
                <el-button @click="drawerVisible = false">取消</el-button>
                <el-button type="primary" :loading="isSubmitting" @click="submitForm">确认并提交</el-button>
            </div>
        </div>
    </el-drawer>
</template>

<script lang="ts" setup>
import type { TabsPaneContext, UploadFile, UploadInstance } from "element-plus";
import { uploadFile } from "@/api/app";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";
import { knowKnowledgeVectorFileAdd, knowKnowledgeWebHtmlCapture } from "@/api/ai_application/knowledge_base/files";
import { knowKnowledgeVectorList } from "@/api/ai_application/knowledge_base/lists";

enum ImportType {
    DOCUMENT = 1,
    QUESTION = 2,
    WEB = 4,
}

interface ChunkItem {
    q: string;
    a: string;
}

interface DocumentItem {
    name: string;
    path: string;
    size: number;
    data: ChunkItem[];
    content?: string;
}

const props = withDefaults(
    defineProps<{
        kbId?: string | number;
        kbName?: string;
    }>(),
    {
        kbId: "",
        kbName: "",
    },
);

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const drawerVisible = ref(false);
const uploadRef = shallowRef<UploadInstance>();
const activeType = ref<ImportType>(ImportType.DOCUMENT);
const documents = ref<DocumentItem[]>([]);
const currentIndex = ref(0);
const loading = ref(false);
const webLoading = ref(false);
const knLoading = ref(false);
const webUrls = ref("");
const stageLen = ref(512);
const selectedKbId = ref("");
const knLists = ref<{ id: string | number; name: string }[]>([]);
const route = useRoute();

const uploadAccept = computed(() => (activeType.value === ImportType.DOCUMENT ? ".txt,.pdf,.md" : ".csv"));

const currentDocument = computed(() => documents.value[currentIndex.value]);

const normalizeKbId = (id?: string | number) => {
    if (id === undefined || id === null || id === "") return "";
    return String(id);
};

const resolveKbId = () => normalizeKbId(props.kbId || (route.query.id as string));
const resolveKbName = () => props.kbName || (route.query.name as string) || "";

const ensureSelectedOption = () => {
    const kbId = selectedKbId.value;
    if (!kbId) return;
    const exists = knLists.value.some((item) => String(item.id) === kbId);
    const kbName = resolveKbName();
    if (!exists && kbName) {
        knLists.value = [{ id: kbId, name: kbName }, ...knLists.value];
    }
};

const getKnLists = async (query?: string) => {
    knLoading.value = true;
    try {
        const { lists } = await knowKnowledgeVectorList({ page_size: 25000, name: query || "" });
        knLists.value = lists || [];
        ensureSelectedOption();
    } finally {
        knLoading.value = false;
    }
};

const open = () => {
    resetData();
    selectedKbId.value = resolveKbId();
    knLists.value = [];
    ensureSelectedOption();
    drawerVisible.value = true;
    getKnLists();
};

const close = () => {
    emit("close");
};

const resetData = () => {
    activeType.value = ImportType.DOCUMENT;
    documents.value = [];
    currentIndex.value = 0;
    webUrls.value = "";
    stageLen.value = 512;
    selectedKbId.value = "";
    knLists.value = [];
};

const handleTypeChange = (pane: TabsPaneContext) => {
    activeType.value = Number(pane.paneName) as ImportType;
    documents.value = [];
    currentIndex.value = 0;
    uploadRef.value?.clearFiles();
};

const handleFileChange = async ({ raw: file }: UploadFile) => {
    if (!file) return;
    loading.value = true;
    try {
        validateFile(file);
        const uploadRes: any = await uploadFile("file", { file });
        const path = uploadRes?.uri || uploadRes?.data?.uri || "";
        const item =
            activeType.value === ImportType.DOCUMENT
                ? await createDocumentItem(file, path)
                : await createQuestionItem(file, path);
        documents.value.push(item);
        currentIndex.value = documents.value.length - 1;
    } catch (error) {
        feedback.msgError(error as string);
    } finally {
        loading.value = false;
        uploadRef.value?.clearFiles();
    }
};

const validateFile = (file: File) => {
    const suffix = `.${file.name.split(".").pop()?.toLowerCase()}`;
    if (!uploadAccept.value.split(",").includes(suffix)) {
        throw `不支持的文件类型，请上传 ${uploadAccept.value} 格式的文件`;
    }
    const isSame = documents.value.some((item) => item.name === file.name && item.size === file.size);
    if (isSame) throw "请勿选择相同文件";
};

const createDocumentItem = async (file: File, path: string): Promise<DocumentItem> => {
    const content = await parseDocument(file);
    if (!content) throw "解析结果为空，已自动忽略";
    return {
        name: file.name,
        path,
        size: file.size,
        content,
        data: splitText2Chunks(content, stageLen.value).map((q) => ({ q, a: "" })),
    };
};

const createQuestionItem = async (file: File, path: string): Promise<DocumentItem> => {
    const rows = await parseQuestionFile(file);
    const data = normalizeQuestionRows(rows);
    if (!data.length) throw "解析结果为空，已自动忽略";
    return {
        name: file.name,
        path,
        size: file.size,
        data,
    };
};

const parseDocument = async (file: File) => {
    const suffix = file.name.substring(file.name.lastIndexOf(".") + 1).toLowerCase();
    switch (suffix) {
        case "md":
        case "txt":
            return readTxtContent(file);
        case "pdf":
            return readPdfContent(file);
        default:
            return readTxtContent(file);
    }
};

const parseQuestionFile = async (file: File) => {
    return readCsvContent(file);
};

const normalizeQuestionRows = (rows: any): ChunkItem[] => {
    if (!Array.isArray(rows)) return [];
    return rows
        .map((row) => {
            const values = Object.values(row || {});
            const q = row.q ?? row.question ?? row.qusetion ?? row.问题 ?? values[0] ?? "";
            const a = row.a ?? row.answer ?? row.答案 ?? values[1] ?? "";
            return {
                q: String(q || "").trim(),
                a: String(a || "").trim(),
            };
        })
        .filter((item) => item.q || item.a);
};

const handleResplit = () => {
    documents.value.forEach((item) => {
        if (!item.content) return;
        item.data = splitText2Chunks(item.content, stageLen.value).map((q) => ({ q, a: "" }));
    });
};

const handleCaptureWeb = async () => {
    const urls = webUrls.value
        .split("\n")
        .map((item) => item.trim())
        .filter(Boolean);
    if (!urls.length) return feedback.msgError("请输入网页链接");
    webLoading.value = true;
    try {
        const data = await knowKnowledgeWebHtmlCapture({ url: urls });
        const newDocs = (data || []).map((item: any) => ({
            name: item.url,
            path: item.url,
            size: 0,
            data: [{ q: item.content, a: "" }],
        }));
        documents.value = [...newDocs, ...documents.value];
        currentIndex.value = 0;
        webUrls.value = "";
        feedback.msgSuccess("解析成功");
    } catch (error) {
        feedback.msgError(error as string);
    } finally {
        webLoading.value = false;
    }
};

const handleDeleteFile = async (index: number) => {
    await feedback.confirm("确定删除该文件吗？");
    documents.value.splice(index, 1);
    currentIndex.value = Math.max(0, Math.min(currentIndex.value, documents.value.length - 1));
};

const handleDeleteChunk = (index: number) => {
    currentDocument.value?.data.splice(index, 1);
};

const readTxtContent = (file: File) => {
    return new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ""));
        reader.onerror = () => reject("读取文件失败");
        reader.readAsText(file);
    });
};

const readPdfContent = async (file: File) => {
    // @ts-ignore pdfjs-dist does not provide declarations in this project.
    const pdfjsLib = await import("pdfjs-dist/build/pdf");
    // @ts-ignore worker URL import is handled by Vite.
    const workerSrc = (await import("pdfjs-dist/build/pdf.worker.js?url")).default;
    pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;
    const buffer = await file.arrayBuffer();
    const doc = await pdfjsLib.getDocument(buffer).promise;
    const pages: string[] = [];
    for (let pageNo = 1; pageNo <= doc.numPages; pageNo++) {
        const page = await doc.getPage(pageNo);
        const textContent = await page.getTextContent();
        pages.push(textContent.items.map((item: any) => item.str).join(""));
    }
    return pages.join("\n");
};

const readCsvContent = async (file: File) => {
    const text = await readTxtContent(file);
    const rows = parseCsv(text).filter((row) => row.some((cell) => cell));
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

const parseCsv = (text: string) => {
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

const splitText2Chunks = (text: string, maxLen = 512) => {
    const chunks: string[] = [];
    const paragraphs = text
        .replace(/\r\n/g, "\n")
        .split(/\n{2,}|(?<=[。！？；.!?;])\s*/g)
        .filter((item) => item.trim());
    let chunk = "";
    paragraphs.forEach((paragraph) => {
        if (chunk && chunk.length + paragraph.length > maxLen) {
            chunks.push(chunk);
            chunk = "";
        }
        if (paragraph.length > maxLen) {
            for (let index = 0; index < paragraph.length; index += maxLen) {
                chunks.push(paragraph.slice(index, index + maxLen));
            }
            return;
        }
        chunk += `${chunk ? "\n" : ""}${paragraph}`;
    });
    if (chunk) chunks.push(chunk);
    return chunks;
};

const { lockFn: submitForm, isLock: isSubmitting } = useLockFn(async () => {
    if (!selectedKbId.value) return feedback.msgWarning("请选择知识库");
    const submitDocuments = documents.value
        .map(({ content, ...item }) => ({
            ...item,
            data: item.data.filter((chunk) => chunk.q || chunk.a),
        }))
        .filter((item) => item.data.length);
    if (!submitDocuments.length) return feedback.msgWarning("请先添加数据");
    await knowKnowledgeVectorFileAdd({
        kb_id: selectedKbId.value,
        method: activeType.value,
        documents: submitDocuments,
    });
    feedback.msgSuccess("添加成功");
    drawerVisible.value = false;
    emit("success");
});

defineExpose({
    open,
});
</script>

<style scoped lang="scss">
.file-item {
    @apply flex gap-2 items-center px-3 py-2 rounded cursor-pointer;

    &:hover,
    &.active {
        @apply bg-primary-light-9 text-primary;
    }
}
</style>
