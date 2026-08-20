<template>
    <div class="ppt-fu w-full">
        <div class="fu-msg">
            <div class="bubble" :class="{ regen: regenLoading }">
                <div class="fu-section-title">
                    {{ description || "补充信息，生成结果更准确" }}
                    <span class="opt-badge">{{ pptType || "选填" }}</span>
                </div>
                <div v-if="description" class="fu-section-sub">完善以下信息，AI 将为你生成更贴合预期的幻灯片</div>

                <div
                    v-for="(field, idx) in fields"
                    v-show="idx < revealCount"
                    :key="field.id"
                    class="fu-question stream-in">
                    <label class="fu-q-label">
                        <span class="q-num">{{ idx + 1 }}.</span>{{ field.label }}
                        <span v-if="field.required" class="q-req">*</span>
                    </label>
                    <div v-if="field.description" class="fu-q-desc">{{ field.description }}</div>

                    <!-- 单行文本 -->
                    <input
                        v-if="field.field_type === 'input' || field.field_type === 'text'"
                        v-model="answers[field.id]"
                        :placeholder="field.placeholder"
                        :maxlength="field.max_length || undefined"
                        class="fu-input" />

                    <!-- 数字:带步进器,强制 ≥ 1 正整数 -->
                    <div
                        v-else-if="field.field_type === 'number'"
                        class="fu-number">
                        <button type="button" class="fu-num-btn" @click="bumpNumber(field.id, -1)">−</button>
                        <input
                            v-model="answers[field.id]"
                            type="number"
                            min="1"
                            step="1"
                            :placeholder="field.placeholder || '请输入数量'"
                            class="fu-num-input"
                            @blur="normalizeNumber(field.id)" />
                        <button type="button" class="fu-num-btn" @click="bumpNumber(field.id, 1)">＋</button>
                    </div>

                    <!-- 多行 -->
                    <textarea
                        v-else-if="field.field_type === 'textarea'"
                        v-model="answers[field.id]"
                        :placeholder="field.placeholder"
                        :maxlength="field.max_length || undefined"
                        class="fu-textarea" />

                    <!-- 下拉 -->
                    <select
                        v-else-if="field.field_type === 'select'"
                        v-model="answers[field.id]"
                        class="fu-select-real">
                        <option value="" disabled>{{ field.placeholder || "请选择" }}</option>
                        <option v-for="(opt, oi) in field.options" :key="oi" :value="opt">
                            {{ opt }}
                        </option>
                    </select>

                    <!-- 单选(点选)-->
                    <div v-else-if="field.field_type === 'radio'" class="fu-radio-group">
                        <div
                            v-for="(opt, i) in field.options"
                            :key="i"
                            class="fu-radio"
                            :class="{ selected: answers[field.id] === opt }"
                            @click="answers[field.id] = opt">
                            <span class="dot" />
                            <span>{{ opt }}</span>
                        </div>
                        <!-- 选了"其他" → 展开自定义输入 -->
                        <input
                            v-if="isOtherSelected(field, answers[field.id])"
                            v-model="otherText[field.id]"
                            class="fu-input fu-other-input"
                            placeholder="请填写其他内容…" />
                    </div>

                    <!-- 多选 -->
                    <div v-else-if="field.field_type === 'checkbox'" class="fu-radio-group">
                        <div
                            v-for="(opt, i) in field.options"
                            :key="i"
                            class="fu-radio"
                            :class="{ selected: (answers[field.id] || []).includes(opt) }"
                            @click="toggleCheckbox(field.id, opt)">
                            <span class="dot square" />
                            <span>{{ opt }}</span>
                        </div>
                        <input
                            v-if="isOtherInArray(field, answers[field.id])"
                            v-model="otherText[field.id]"
                            class="fu-input fu-other-input"
                            placeholder="请填写其他内容…" />
                    </div>

                    <!-- 兜底:未识别类型当 input -->
                    <input
                        v-else
                        v-model="answers[field.id]"
                        :placeholder="field.placeholder"
                        class="fu-input" />
                </div>

                <div v-if="regenLoading" class="fu-loading-overlay">
                    <span class="step-spinner" />
                    AI 正在为你重新生成问卷…
                </div>

                <div v-if="revealCount >= fields.length && fields.length > 0" class="fu-actions stream-in">
                    <div class="ob-btn primary" @click="confirm">{{ submitLabel }}</div>
                    <div class="ob-btn" @click="$emit('cancel')">取消</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CozeField } from "@/utils/coze";
import feedback from "@/utils/feedback";

interface Props {
    description?: string;
    pptType?: string;
    fields: CozeField[];
    /** 提交按钮文字(PPT 模式默认"提交并生成 PPT",数字人 AI 追问模式传"提交生成文案") */
    submitLabel?: string;
}
const props = withDefaults(defineProps<Props>(), {
    description: "",
    pptType: "",
    fields: () => [],
    submitLabel: "提交并生成 PPT",
});

const emit = defineEmits<{
    (
        e: "confirm",
        payload: {
            answers: Record<string, any>;
            summary: Record<string, string>;
            pageCount?: number;
        },
    ): void;
    (e: "cancel"): void;
}>();

const regenLoading = ref(false);
const revealCount = ref(0);
const answers = reactive<Record<string, any>>({});
// "其他"选项的自定义文字(每题一份),提交时合并进 answers
const otherText = reactive<Record<string, string>>({});

const OTHER_LABELS = ["其他", "其它", "other", "Other", "OTHER"];
function isOtherOption(opt: any) {
    return OTHER_LABELS.includes(String(opt));
}
function isOtherSelected(_field: CozeField, value: any) {
    return value !== undefined && value !== null && isOtherOption(value);
}
function isOtherInArray(_field: CozeField, value: any) {
    return Array.isArray(value) && value.some((v) => isOtherOption(v));
}

function bumpNumber(id: string, delta: number) {
    const n = parseInt(String(answers[id] ?? "0"), 10);
    const next = Math.max(1, (isNaN(n) ? 0 : n) + delta);
    answers[id] = next;
}
function normalizeNumber(id: string) {
    const n = parseInt(String(answers[id] ?? ""), 10);
    if (!isNaN(n) && n >= 1) {
        answers[id] = n;
    } else if (answers[id] !== "" && answers[id] !== undefined) {
        // 非空且无效 → 拉回 1
        answers[id] = 1;
    }
}

let revealTimer: ReturnType<typeof setInterval> | null = null;

function initAnswers() {
    // 清空,然后用 default_value 初始化(checkbox 默认空数组)
    for (const k of Object.keys(answers)) delete answers[k];
    for (const k of Object.keys(otherText)) delete otherText[k];
    for (const f of props.fields) {
        if (f.field_type === "checkbox") {
            answers[f.id] = f.default_value ? [String(f.default_value)] : [];
        } else {
            answers[f.id] = f.default_value ?? "";
        }
    }
}

function startReveal() {
    revealCount.value = 0;
    if (revealTimer) clearInterval(revealTimer);
    if (!props.fields.length) return;
    revealTimer = setInterval(() => {
        revealCount.value++;
        if (revealCount.value >= props.fields.length) {
            if (revealTimer) clearInterval(revealTimer);
            revealTimer = null;
        }
    }, 280);
}

function toggleCheckbox(id: string, opt: string) {
    const cur: string[] = Array.isArray(answers[id]) ? answers[id] : [];
    const i = cur.indexOf(opt);
    if (i >= 0) cur.splice(i, 1);
    else cur.push(opt);
    answers[id] = [...cur];
}

watch(
    () => props.fields,
    () => {
        initAnswers();
        startReveal();
    },
    { immediate: true, deep: false },
);

onBeforeUnmount(() => {
    if (revealTimer) clearInterval(revealTimer);
});

function confirm() {
    // 校验必填
    const missing = props.fields.filter((f) => {
        if (!f.required) return false;
        const v = answers[f.id];
        if (Array.isArray(v)) return v.length === 0;
        return v === undefined || v === "" || v === null;
    });
    if (missing.length > 0) {
        feedback.msgWarning(`请填写必填项:${missing.map((f) => f.label).join("、")}`);
        return;
    }

    // 数字字段:必须是 >= 1 的正整数
    for (const f of props.fields) {
        if (f.field_type !== "number") continue;
        const v = answers[f.id];
        if (v === "" || v === undefined || v === null) continue; // 空值前面必填校验处理过
        const n = Number(v);
        if (!Number.isInteger(n) || n < 1) {
            feedback.msgWarning(`"${f.label}" 必须是大于等于 1 的正整数`);
            return;
        }
        answers[f.id] = n; // 规整成数字
    }

    // 选了"其他" → 用自定义文本替换答案;空则要求填写
    for (const f of props.fields) {
        const v = answers[f.id];
        if (f.field_type === "radio" && isOtherOption(v)) {
            const t = (otherText[f.id] ?? "").trim();
            if (!t && f.required) {
                feedback.msgWarning(`请填写"${f.label}"的"其他"内容`);
                return;
            }
            if (t) answers[f.id] = t;
        } else if (f.field_type === "checkbox" && Array.isArray(v) && v.some(isOtherOption)) {
            const t = (otherText[f.id] ?? "").trim();
            if (!t && f.required) {
                feedback.msgWarning(`请填写"${f.label}"的"其他"内容`);
                return;
            }
            if (t) {
                answers[f.id] = v.map((x) => (isOtherOption(x) ? t : x));
            }
        }
    }

    // summary 用 label: value 格式,便于送下游
    const summary: Record<string, string> = {};
    let pageCount: number | undefined;
    for (const f of props.fields) {
        const v = answers[f.id];
        const sval = Array.isArray(v) ? v.join("、") : String(v ?? "");
        if (sval !== "") summary[f.label] = sval;
        if (f.id === "slide_count" || f.id === "page_count") {
            const n = parseInt(String(v), 10);
            if (!isNaN(n) && n > 0) pageCount = n;
        }
    }
    emit("confirm", { answers: { ...answers }, summary, pageCount });
}
</script>

<style lang="scss" scoped>
.fu-msg {
    display: flex;
    gap: 10px;
    max-width: 100%;
}
.bubble {
    flex: 1;
    background: transparent;
    padding: 0;
    font-size: 13px;
    color: #1f2937;
    position: relative;
}
.bubble.regen {
    pointer-events: none;
}
.fu-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.opt-badge {
    background: linear-gradient(135deg, #ec4899, #f97316);
    color: #fff;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 6px;
    font-weight: 500;
}
.fu-section-sub {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
    margin-bottom: 18px;
}
.fu-question {
    margin-bottom: 16px;
}
.fu-q-label {
    display: flex;
    align-items: baseline;
    gap: 4px;
    font-size: 13px;
    color: #374151;
    margin-bottom: 6px;
    flex-wrap: wrap;
    font-weight: 500;
    .q-num {
        color: #2563eb;
        font-weight: 600;
    }
    .q-req {
        color: #ef4444;
        font-weight: bold;
    }
}
.fu-q-desc {
    font-size: 11px;
    color: #9ca3af;
    margin-bottom: 8px;
    line-height: 1.5;
}
.fu-input,
.fu-textarea,
.fu-select-real {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ebedf0;
    border-radius: 10px;
    font-size: 13px;
    color: #1f2937;
    background: #fafbfc;
    outline: none;
    font-family: inherit;
    transition: border-color 0.15s;
    &:focus {
        border-color: #93c5fd;
        background: #fff;
    }
}
.fu-textarea {
    min-height: 76px;
    resize: vertical;
    line-height: 1.5;
}
// 数字步进器:左右大圆按钮 + 居中显示数字
.fu-number {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border: 1px solid #c7d2fe;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.06);
    .fu-num-btn {
        width: 38px;
        height: 38px;
        border: none;
        background: #eff6ff;
        color: #2563eb;
        font-size: 20px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
        &:hover { background: #dbeafe; }
        &:active { background: #bfdbfe; }
    }
    .fu-num-input {
        width: 90px;
        height: 38px;
        border: none;
        outline: none;
        text-align: center;
        font-size: 18px;
        font-weight: 600;
        color: #1d4ed8;
        background: #fff;
        -moz-appearance: textfield;
        &::-webkit-outer-spin-button,
        &::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        // 占位文字不能跟数值一样大,显得拥挤
        &::placeholder { font-size: 12px; font-weight: 400; color: #9ca3af; }
    }
}
.fu-other-input {
    margin-top: 6px;
    border-color: #c7d2fe !important;
    background: #f5f8ff !important;
}
.fu-select-real {
    cursor: pointer;
}
.fu-radio-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.fu-radio {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border: 1px solid #ebedf0;
    border-radius: 10px;
    cursor: pointer;
    background: #fafbfc;
    font-size: 13px;
    color: #4b5563;
    transition: all 0.15s;
    &:hover {
        border-color: #93c5fd;
        background: #f5f8ff;
    }
    .dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1.5px solid #d1d5db;
        flex-shrink: 0;
        position: relative;
        &.square {
            border-radius: 3px;
        }
    }
    &.selected {
        border-color: #2563eb;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 500;
        .dot {
            border-color: #2563eb;
            &::after {
                content: "";
                position: absolute;
                inset: 2px;
                border-radius: 50%;
                background: #2563eb;
            }
            &.square::after {
                border-radius: 1px;
            }
        }
    }
}
.fu-actions {
    display: flex;
    gap: 8px;
    margin-top: 18px;
    flex-wrap: wrap;
}
.ob-btn {
    padding: 8px 18px;
    border: 1px solid #ebedf0;
    border-radius: 18px;
    font-size: 13px;
    color: #4b5563;
    cursor: pointer;
    background: #fff;
    font-weight: 500;
    &:hover {
        border-color: #93c5fd;
        color: #2563eb;
    }
    &.primary {
        background: linear-gradient(135deg, #4f8ef7, #2563eb);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.22);
        &:hover {
            opacity: 0.92;
            color: #fff;
        }
    }
}
.fu-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(2px);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #2563eb;
    font-size: 13px;
    font-weight: 500;
    z-index: 5;
}
.step-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid #dbeafe;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
.stream-in {
    animation: streamIn 0.35s ease both;
}
@keyframes streamIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
