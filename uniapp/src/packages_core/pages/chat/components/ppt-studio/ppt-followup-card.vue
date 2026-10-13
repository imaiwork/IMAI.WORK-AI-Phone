<template>
    <view class="fu-card">
        <view class="fu-head">
            <text class="fu-title">{{ description || "补充信息，生成结果更准确" }}</text>
            <text v-if="pptType" class="fu-badge">{{ pptType }}</text>
        </view>
        <text v-if="description" class="fu-sub">完善以下信息，AI 将为你生成更贴合预期的幻灯片</text>

        <view v-for="(field, idx) in normalizedFields" :key="field.id || idx" class="fu-q">
            <view class="fu-label">
                <text>{{ idx + 1 }}. {{ field.label }}</text>
                <text v-if="field.required" class="fu-req">*</text>
            </view>
            <text v-if="field.description" class="fu-desc">{{ field.description }}</text>

            <input
                v-if="field.field_type === 'input'"
                class="fu-input"
                :value="answers[field.id]"
                :placeholder="field.placeholder || '请输入'"
                :maxlength="field.max_length || 200"
                @input="(e) => onTextInput(field.id, e)" />

            <view v-else-if="field.field_type === 'number'" class="fu-number">
                <view class="fu-num-btn" @click="bumpNumber(field.id, -1)">−</view>
                <input
                    class="fu-num-input"
                    type="number"
                    :value="answers[field.id]"
                    :placeholder="field.placeholder || '请输入数量'"
                    @input="(e) => onTextInput(field.id, e)"
                    @blur="normalizeNumber(field.id)" />
                <view class="fu-num-btn" @click="bumpNumber(field.id, 1)">＋</view>
            </view>

            <textarea
                v-else-if="field.field_type === 'textarea'"
                class="fu-textarea"
                :value="answers[field.id]"
                :placeholder="field.placeholder || '请输入'"
                :maxlength="field.max_length || 500"
                :auto-height="true"
                @input="(e) => onTextInput(field.id, e)" />

            <picker
                v-else-if="field.field_type === 'select'"
                mode="selector"
                :range="field.options"
                :value="selectIndex(field)"
                @change="(e) => onSelect(field, e)">
                <view class="fu-select">
                    <text :class="answers[field.id] ? 'fu-select__val' : 'fu-select__ph'">
                        {{ answers[field.id] || field.placeholder || "请选择" }}
                    </text>
                    <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                </view>
            </picker>

            <view v-else-if="field.field_type === 'radio'" class="fu-opts">
                <view
                    v-for="(opt, i) in field.options"
                    :key="i"
                    class="fu-opt"
                    :class="{ 'fu-opt--on': answers[field.id] === opt }"
                    @click="answers[field.id] = opt">
                    <view class="fu-dot"></view>
                    <text>{{ opt }}</text>
                </view>
                <input
                    v-if="isOtherSelected(answers[field.id])"
                    class="fu-input fu-input--other"
                    :value="otherText[field.id]"
                    placeholder="请填写其他内容…"
                    @input="(e) => onOtherInput(field.id, e)" />
            </view>

            <view v-else-if="field.field_type === 'checkbox'" class="fu-opts">
                <view
                    v-for="(opt, i) in field.options"
                    :key="i"
                    class="fu-opt"
                    :class="{ 'fu-opt--on': (answers[field.id] || []).includes(opt) }"
                    @click="toggleCheckbox(field.id, opt)">
                    <view class="fu-dot fu-dot--sq"></view>
                    <text>{{ opt }}</text>
                </view>
                <input
                    v-if="isOtherInArray(answers[field.id])"
                    class="fu-input fu-input--other"
                    :value="otherText[field.id]"
                    placeholder="请填写其他内容…"
                    @input="(e) => onOtherInput(field.id, e)" />
            </view>

            <input
                v-else
                class="fu-input"
                :value="answers[field.id]"
                :placeholder="field.placeholder || '请输入'"
                @input="(e) => onTextInput(field.id, e)" />
        </view>

        <view class="fu-actions">
            <view
                class="fu-btn fu-btn--primary"
                hover-class="opacity-80"
                :hover-stay-time="80"
                @click="onConfirm">
                提交并生成 PPT
            </view>
            <view
                class="fu-btn"
                hover-class="opacity-80"
                :hover-stay-time="80"
                @click="emit('cancel')">
                取消
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const OTHER_LABELS = ["其他", "其它", "other", "Other", "OTHER"];

const props = withDefaults(
    defineProps<{
        description?: string;
        pptType?: string;
        fields?: any[];
    }>(),
    {
        description: "",
        pptType: "",
        fields: () => [],
    },
);

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

const answers = reactive<Record<string, any>>({});
const otherText = reactive<Record<string, string>>({});

const mapFieldType = (t: any): string => {
    const k = String(t || "")
        .trim()
        .toLowerCase();
    if (k === "single_choice" || k === "radio") return "radio";
    if (k === "multi_choice" || k === "multiple_choice" || k === "checkbox") return "checkbox";
    if (k === "select" || k === "dropdown" || k === "select_list") return "select";
    if (k === "number" || k === "int" || k === "integer") return "number";
    if (k === "textarea" || k === "long_text" || k === "multiline") return "textarea";
    if (k === "text" || k === "input" || k === "string" || k === "short_text") return "input";
    return k || "input";
};

/** 统一成普通 string[]，避免小程序 picker 读不到 Proxy/对象选项 */
const normalizeOptions = (raw: any): string[] => {
    let list: any = raw;
    if (typeof list === "string") {
        const s = list.trim();
        if (!s) return [];
        try {
            list = JSON.parse(s);
        } catch {
            list = s.split(/[,，、\n|/]/).map((x) => x.trim()).filter(Boolean);
        }
    }
    if (!Array.isArray(list)) return [];
    return list
        .map((o) => {
            if (o == null) return "";
            if (typeof o === "string" || typeof o === "number" || typeof o === "boolean") {
                return String(o).trim();
            }
            if (typeof o === "object") {
                return String(o.label ?? o.value ?? o.name ?? o.text ?? o.title ?? "").trim();
            }
            return String(o).trim();
        })
        .filter(Boolean);
};

const normalizedFields = computed(() => {
    return (props.fields || []).map((q: any, idx: number) => {
        let field_type = mapFieldType(q?.field_type ?? q?.type ?? "input");
        const options = normalizeOptions(q?.options ?? q?.choices ?? q?.enum ?? q?.items ?? q?.option);
        // select 无选项时降级为输入，避免空 picker
        if (field_type === "select" && !options.length) field_type = "input";
        return {
            id: String(q?.id ?? q?.field ?? q?.name ?? `q_${idx}`),
            label: String(q?.label ?? q?.question ?? q?.title ?? `问题 ${idx + 1}`),
            description: String(q?.description || ""),
            field_type,
            default_value: q?.default_value ?? "",
            options: [...options],
            placeholder: String(q?.placeholder || ""),
            required: !!q?.required,
            max_length: q?.max_length,
        };
    });
});

const isOtherOption = (opt: any) => OTHER_LABELS.includes(String(opt));
const isOtherSelected = (value: any) => value != null && isOtherOption(value);
const isOtherInArray = (value: any) => Array.isArray(value) && value.some(isOtherOption);

const initAnswers = () => {
    for (const k of Object.keys(answers)) delete answers[k];
    for (const k of Object.keys(otherText)) delete otherText[k];
    for (const f of normalizedFields.value) {
        if (f.field_type === "checkbox") {
            answers[f.id] = f.default_value ? [String(f.default_value)] : [];
        } else {
            answers[f.id] = f.default_value ?? "";
        }
    }
};

watch(
    () => props.fields,
    () => initAnswers(),
    { immediate: true, deep: false },
);

const onTextInput = (id: string, e: any) => {
    answers[id] = e?.detail?.value ?? "";
};

const onOtherInput = (id: string, e: any) => {
    otherText[id] = e?.detail?.value ?? "";
};

const bumpNumber = (id: string, delta: number) => {
    const n = parseInt(String(answers[id] ?? "0"), 10);
    answers[id] = Math.max(1, (Number.isNaN(n) ? 0 : n) + delta);
};

const normalizeNumber = (id: string) => {
    const n = parseInt(String(answers[id] ?? ""), 10);
    if (!Number.isNaN(n) && n >= 1) answers[id] = n;
    else if (answers[id] !== "" && answers[id] !== undefined) answers[id] = 1;
};

const selectIndex = (field: { id: string; options: string[] }) => {
    const i = field.options.indexOf(String(answers[field.id] ?? ""));
    return i >= 0 ? i : 0;
};

const onSelect = (field: { id: string; options: string[] }, e: any) => {
    const idx = Number(e?.detail?.value);
    const list = field.options || [];
    answers[field.id] = list[idx] || "";
};

const toggleCheckbox = (id: string, opt: string) => {
    const cur: string[] = Array.isArray(answers[id]) ? [...answers[id]] : [];
    const i = cur.indexOf(opt);
    if (i >= 0) cur.splice(i, 1);
    else cur.push(opt);
    answers[id] = cur;
};

const onConfirm = () => {
    const fields = normalizedFields.value;
    const missing = fields.filter((f) => {
        if (!f.required) return false;
        const v = answers[f.id];
        if (Array.isArray(v)) return v.length === 0;
        return v === undefined || v === "" || v === null;
    });
    if (missing.length) {
        uni.$u.toast(`请填写必填项：${missing.map((f) => f.label).join("、")}`);
        return;
    }

    for (const f of fields) {
        if (f.field_type !== "number") continue;
        const v = answers[f.id];
        if (v === "" || v === undefined || v === null) continue;
        const n = Number(v);
        if (!Number.isInteger(n) || n < 1) {
            uni.$u.toast(`「${f.label}」必须是大于等于 1 的正整数`);
            return;
        }
        answers[f.id] = n;
    }

    for (const f of fields) {
        const v = answers[f.id];
        if (f.field_type === "radio" && isOtherOption(v)) {
            const t = String(otherText[f.id] || "").trim();
            if (!t && f.required) {
                uni.$u.toast(`请填写「${f.label}」的其他内容`);
                return;
            }
            if (t) answers[f.id] = t;
        } else if (f.field_type === "checkbox" && Array.isArray(v) && v.some(isOtherOption)) {
            const t = String(otherText[f.id] || "").trim();
            if (!t && f.required) {
                uni.$u.toast(`请填写「${f.label}」的其他内容`);
                return;
            }
            if (t) answers[f.id] = v.map((x) => (isOtherOption(x) ? t : x));
        }
    }

    const summary: Record<string, string> = {};
    let pageCount: number | undefined;
    for (const f of fields) {
        const v = answers[f.id];
        const sval = Array.isArray(v) ? v.join("、") : String(v ?? "");
        if (sval !== "") summary[f.label] = sval;
        if (f.id === "slide_count" || f.id === "page_count") {
            const n = parseInt(String(v), 10);
            if (!Number.isNaN(n) && n > 0) pageCount = n;
        }
    }
    emit("confirm", { answers: { ...answers }, summary, pageCount });
};
</script>

<style lang="scss" scoped>
.fu-card {
    @apply bg-white rounded-[28rpx] px-[28rpx] py-[28rpx];
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
.fu-head {
    @apply flex items-start gap-x-[12rpx] mb-[8rpx];
}
.fu-title {
    @apply flex-1 text-[28rpx] font-semibold text-[#1F2937] leading-snug;
}
.fu-badge {
    @apply flex-shrink-0 text-[20rpx] text-primary bg-[#EFF6FF] rounded-[8rpx] px-[12rpx] py-[4rpx];
}
.fu-sub {
    @apply block text-[24rpx] text-[#9CA3AF] mb-[24rpx] leading-relaxed;
}
.fu-q {
    @apply mb-[28rpx];
}
.fu-label {
    @apply flex items-center gap-x-[6rpx] text-[26rpx] font-medium text-[#111827] mb-[8rpx];
}
.fu-req {
    @apply text-[#EF4444];
}
.fu-desc {
    @apply block text-[22rpx] text-[#9CA3AF] mb-[12rpx];
}
/* 小程序 input 对 padding-y 支持差，用固定高度避免塌陷 */
.fu-input {
    width: 100%;
    height: 72rpx;
    line-height: 72rpx;
    box-sizing: border-box;
    background: #f7f8fa;
    border-radius: 16rpx;
    padding: 0 20rpx;
    font-size: 26rpx;
    color: #111827;
}
.fu-input--other {
    margin-top: 12rpx;
}
.fu-textarea {
    width: 100%;
    min-height: 140rpx;
    box-sizing: border-box;
    background: #f7f8fa;
    border-radius: 16rpx;
    padding: 18rpx 20rpx;
    font-size: 26rpx;
    color: #111827;
    line-height: 1.5;
}
.fu-number {
    @apply flex items-center gap-x-[16rpx];
}
.fu-num-btn {
    width: 72rpx;
    height: 72rpx;
    border-radius: 16rpx;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36rpx;
    color: #374151;
    flex-shrink: 0;
}
.fu-num-input {
    flex: 1;
    height: 72rpx;
    line-height: 72rpx;
    text-align: center;
    background: #f7f8fa;
    border-radius: 16rpx;
    font-size: 28rpx;
    color: #111827;
    box-sizing: border-box;
    padding: 0 12rpx;
}
.fu-select {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 72rpx;
    box-sizing: border-box;
    background: #f7f8fa;
    border-radius: 16rpx;
    padding: 0 20rpx;
    font-size: 26rpx;
}
.fu-select__val {
    color: #111827;
}
.fu-select__ph {
    color: #9ca3af;
}
.fu-opts {
    @apply flex flex-col gap-y-[12rpx];
}
.fu-opt {
    @apply flex items-center gap-x-[12rpx] bg-[#F7F8FA] rounded-[16rpx] px-[20rpx] py-[18rpx] text-[26rpx] text-[#374151];
}
.fu-opt--on {
    @apply bg-[#EFF6FF] text-primary;
}
.fu-dot {
    @apply w-[28rpx] h-[28rpx] rounded-full border-2 border-solid border-[#D1D5DB] flex-shrink-0;
}
.fu-opt--on .fu-dot {
    @apply border-primary;
    background: radial-gradient(circle, #2563eb 45%, transparent 50%);
}
.fu-dot--sq {
    @apply rounded-[6rpx];
}
.fu-actions {
    @apply flex flex-col gap-y-[16rpx] mt-[8rpx];
}
.fu-btn {
    @apply h-[80rpx] rounded-full bg-[#F3F4F6] text-[28rpx] font-semibold text-[#374151] flex items-center justify-center;
}
.fu-btn--primary {
    @apply bg-primary text-white;
}
</style>
