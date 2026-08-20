<template>
    <popup
        ref="popupRef"
        title="热点追踪 · 爆款追踪词"
        :async="true"
        width="640px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <div class="rounded-xl px-3 py-2 mb-4 flex items-start gap-2" style="background: #f0f5ff">
                <el-icon style="color: #3b71e8; margin-top: 2px; flex-shrink: 0"><InfoFilled /></el-icon>
                <span class="text-xs leading-relaxed" style="color: #3b71e8">
                    AI 每天会根据这些关键词追踪全平台爆款内容。可手动添加，也可调用 AI 推荐。
                </span>
            </div>

            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-extrabold text-[#1a1a1a]">关键词列表</span>
                <el-button type="primary" size="small" :loading="recommendLoading" plain @click="handleRecommend">
                    AI 推荐
                </el-button>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
                <el-tag
                    v-for="(word, index) in keywords"
                    :key="`${word}-${index}`"
                    closable
                    type="info"
                    effect="light"
                    round
                    class="cursor-pointer"
                    @close="handleRemove(index)"
                    @click="handleEdit(index)">
                    {{ word }}
                </el-tag>
                <span v-if="!keywords.length" class="text-xs text-[#9ca3af]">暂无追踪词，请在下方添加</span>
            </div>

            <div class="flex items-center gap-2 mb-5">
                <el-input
                    v-model="inputValue"
                    placeholder="输入新追踪词，回车添加"
                    size="default"
                    maxlength="20"
                    show-word-limit
                    @keyup.enter="handleAdd" />
                <el-button type="primary" size="default" @click="handleAdd">添加</el-button>
            </div>

            <div class="mb-4">
                <p class="text-sm font-extrabold text-[#1a1a1a] mb-2.5 m-0">视频时长</p>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="item in DURATION_OPTIONS"
                        :key="item.value"
                        class="px-4 py-1.5 rounded-full text-xs font-bold cursor-pointer transition-all border"
                        :class="
                            duration === item.value
                                ? 'bg-[#ebf2ff] text-primary border-primary'
                                : 'bg-white text-[#6b7280] border-[#e3eaf4]'
                        "
                        @click="duration = item.value">
                        {{ item.label }}
                    </div>
                </div>
            </div>

            <div class="mb-1">
                <p class="text-sm font-extrabold text-[#1a1a1a] mb-2.5 m-0">视频发布时间</p>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="item in PUBLISH_DAY_OPTIONS"
                        :key="item.value"
                        class="px-4 py-1.5 rounded-full text-xs font-bold cursor-pointer transition-all border"
                        :class="
                            publishDay === item.value
                                ? 'bg-[#ebf2ff] text-primary border-primary'
                                : 'bg-white text-[#6b7280] border-[#e3eaf4]'
                        "
                        @click="publishDay = item.value">
                        {{ item.label }}
                    </div>
                </div>
            </div>

            <el-dialog v-model="editVisible" title="编辑追踪词" width="380px" append-to-body>
                <el-input v-model="editValue" maxlength="20" show-word-limit />
                <template #footer>
                    <el-button @click="editVisible = false">取消</el-button>
                    <el-button type="primary" @click="handleConfirmEdit">保存</el-button>
                </template>
            </el-dialog>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { ref, shallowRef } from "vue";
import { InfoFilled } from "@element-plus/icons-vue";
import { getPersonTrackingWords, updatePersonTrackingWords } from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";

type FilterValue = 0 | 1 | 2 | 3;

export type TrendingOpenPayload = {
    hot_words?: any;
    duration?: unknown;
    publish_day?: unknown;
};

const DURATION_OPTIONS: { label: string; value: FilterValue }[] = [
    { label: "1 分钟以下", value: 1 },
    { label: "1-5 分钟", value: 2 },
    { label: "5 分钟以上", value: 3 },
    { label: "不限", value: 0 },
];

const PUBLISH_DAY_OPTIONS: { label: string; value: FilterValue }[] = [
    { label: "一天内", value: 1 },
    { label: "一周内", value: 2 },
    { label: "半年内", value: 3 },
    { label: "不限", value: 0 },
];

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const loading = ref(false);
const recommendLoading = ref(false);
const personId = ref<string | number>("");
const keywords = ref<string[]>([]);
const duration = ref<FilterValue>(0);
const publishDay = ref<FilterValue>(0);
const inputValue = ref("");
const editVisible = ref(false);
const editValue = ref("");
const editIndex = ref(-1);

const normalize = (raw: any): string[] => {
    if (Array.isArray(raw)) return raw.map((v) => String(v ?? "").trim()).filter(Boolean);
    if (typeof raw === "string" && raw.trim()) return [raw.trim()];
    return [];
};

const normalizeFilterValue = (value: unknown): FilterValue => {
    const num = Number(value);
    return num === 1 || num === 2 || num === 3 ? num : 0;
};

const handleAdd = () => {
    const val = inputValue.value.trim();
    if (!val) return;
    if (keywords.value.includes(val)) {
        feedback.msgWarning("该追踪词已存在");
        return;
    }
    keywords.value = [...keywords.value, val];
    inputValue.value = "";
};

const handleRemove = (index: number) => {
    keywords.value = keywords.value.filter((_, i) => i !== index);
};

const handleEdit = (index: number) => {
    editIndex.value = index;
    editValue.value = keywords.value[index] ?? "";
    editVisible.value = true;
};

const handleConfirmEdit = () => {
    const val = editValue.value.trim();
    if (!val) {
        feedback.msgWarning("内容不能为空");
        return;
    }
    if (keywords.value.some((w, i) => w === val && i !== editIndex.value)) {
        feedback.msgWarning("该追踪词已存在");
        return;
    }
    keywords.value[editIndex.value] = val;
    editVisible.value = false;
};

const handleRecommend = async () => {
    if (!personId.value || recommendLoading.value) return;
    try {
        recommendLoading.value = true;
        const res = await getPersonTrackingWords({ id: personId.value, recommend: 1 });
        const raw = res?.hot_words ?? res?.words ?? res?.keywords ?? res?.list ?? res?.lists ?? res;
        const recommended = normalize(raw);
        if (recommended.length) {
            keywords.value = recommended;
            feedback.msgSuccess("AI 已推荐追踪词");
        } else {
            feedback.msgWarning("暂无推荐词");
        }
    } finally {
        recommendLoading.value = false;
    }
};

const handleSave = async () => {
    if (!keywords.value.length) {
        feedback.msgWarning("请至少添加一个爆款追踪词");
        return;
    }
    await updatePersonTrackingWords({
        id: personId.value,
        tracking_mode: 1,
        hot_words: keywords.value,
        duration: duration.value,
        publish_day: publishDay.value,
    });
    close();
    emit("success");
};

const { isLock, lockFn } = useLockFn(handleSave);
const close = () => emit("close");

// 打开时不再请求接口：追踪词取人设子表，duration / publish_day 取详情最外层（对齐 uniapp）
const open = (id: string | number, payload?: TrendingOpenPayload) => {
    personId.value = id;
    keywords.value = normalize(payload?.hot_words);
    duration.value = normalizeFilterValue(payload?.duration);
    publishDay.value = normalizeFilterValue(payload?.publish_day);
    inputValue.value = "";
    popupRef.value?.open();
};

defineExpose({ open });
</script>
