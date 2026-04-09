<template>
    <popup
        ref="popupRef"
        title="知识库配置"
        :async="true"
        width="660px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <div class="flex items-start gap-3 bg-[#eff6ff] border border-[#bfdbfe] rounded-xl px-4 py-3 mb-5">
                <div class="w-5 h-5 rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-0.5">
                    <el-icon class="text-white" :size="12"><InfoFilled /></el-icon>
                </div>
                <p class="text-xs text-[#2563eb] leading-relaxed font-medium flex-1 m-0">
                    系统将在自动生成
                    <span class="text-primary font-bold">24H视频</span>
                    时，根据知识库中的内容，来生成视频中对应的文案，如果为空则不参考
                </p>
            </div>
            <field-block icon="💼" icon-bg="#E6F0FF" title="我的主营业务/产品" tip="告诉AI您具体卖什么、提供什么服务。">
                <rich-textarea
                    v-model="formData.main_business"
                    :max-length="maxLength"
                    placeholder="请描述您具体卖什么？"
                    :examples="[
                        '正宗重庆老火锅，主打鲜切毛肚',
                        '短视频运营与剪辑线上课程',
                        '企业级AI智能客服SaaS系统',
                    ]" />
            </field-block>

            <field-block
                icon="👥"
                icon-bg="#E6F8F3"
                title="目标客户与痛点"
                tip="告诉AI您的客户是谁，以及他们遇到了什么麻烦/需求。">
                <rich-textarea
                    v-model="formData.target_pain_points"
                    :max-length="maxLength"
                    placeholder="您的客户是谁？他们遇到了什么麻烦？"
                    :examples="[
                        '周边3公里不知道吃什么的大学生',
                        '想做副业但毫无经验的宝妈',
                        '获客成本高、管理效率低的中小企业老板',
                    ]" />
            </field-block>

            <field-block
                icon="⭐"
                icon-bg="linear-gradient(135deg,#ff9a9e 0%,#a1c4fd 100%)"
                title="差异化优势与行动引导"
                tip="告诉AI为什么选您，以及视频最后用什么福利引导客户留资或到店。">
                <rich-textarea
                    v-model="formData.conversion_hook"
                    :max-length="maxLength"
                    placeholder="为什么选您？想要客户做什么？"
                    :examples="[
                        '30年祖传秘方，凭视频到店送特色菜',
                        '10年实战经验，私信回复&quot;学习&quot;领资料',
                        '服务过500强企业，点击主页免费定制方案',
                    ]" />
            </field-block>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { defineComponent, h, ref as vRef, computed as vComputed } from "vue";
import { InfoFilled } from "@element-plus/icons-vue";
import { ElMessage, ElInput } from "element-plus";
import { updateKnowledgeConfig } from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";
import { use } from "echarts";
import { useLockFn } from "@/hooks/useLockFn";

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

// ════════════════════════════════════════════════════
// 内联子组件：FieldBlock —— 区块标题行
// ════════════════════════════════════════════════════
const FieldBlock = defineComponent({
    name: "FieldBlock",
    props: {
        icon: { type: String, default: "" },
        iconBg: { type: String, default: "#E6F0FF" },
        title: { type: String, required: true },
        required: { type: Boolean, default: false },
        tip: { type: String, default: "" },
    },
    setup(props, { slots }) {
        return () =>
            h("div", { class: "mb-5" }, [
                // 标题行
                h("div", { class: "flex items-center gap-2 mb-2 px-1" }, [
                    h(
                        "div",
                        {
                            class: "w-7 h-7 rounded-full flex items-center justify-center text-sm flex-shrink-0",
                            style: { background: props.iconBg },
                        },
                        props.icon
                    ),
                    h("span", { class: "text-sm font-extrabold text-[#171717]" }, props.title),
                    h(
                        "div",
                        {
                            class: `ml-auto px-2 py-0.5 rounded-full text-xs font-bold ${
                                props.required ? "bg-[#fef2f2] text-[#ef4444]" : "bg-[#eff6ff] text-primary"
                            }`,
                        },
                        props.required ? "必填" : "选填"
                    ),
                ]),
                // 卡片容器
                h("div", { class: "bg-white rounded-xl p-4 border border-[#ececec] shadow-sm" }, [
                    props.tip ? h("p", { class: "text-xs text-[#b4b4b4] leading-relaxed mb-3 m-0" }, props.tip) : null,
                    slots.default?.(),
                ]),
            ]);
    },
});

// ════════════════════════════════════════════════════
// 内联子组件：RichTextarea —— 带示例占位 + 字数统计
// ════════════════════════════════════════════════════
const RichTextarea = defineComponent({
    name: "RichTextarea",
    props: {
        modelValue: { type: String, default: "" },
        maxLength: { type: Number, default: 2000 },
        placeholder: { type: String, default: "请输入" },
        examples: { type: Array as () => string[], default: () => [] },
    },
    emits: ["update:modelValue"],
    setup(props, { emit }) {
        const focused = vRef(false);
        const showPlaceholder = vComputed(() => !props.modelValue && !focused.value);

        return () =>
            h(
                "div",
                {
                    class: `relative rounded-lg px-3 py-2.5 border transition-colors ${
                        focused.value ? "bg-[#eff6ff]/30 border-primary" : "bg-[#f9f9f9] border-[#e3e3e3]"
                    }`,
                },
                [
                    // 富占位符（仅在无值且未聚焦时显示）
                    showPlaceholder.value
                        ? h("div", { class: "absolute top-3 left-3 right-3 pointer-events-none select-none" }, [
                              h("p", { class: "text-xs text-[#cdcdcd] mb-1.5 m-0" }, props.placeholder),
                              h("p", { class: "text-xs text-[#cdcdcd] mb-1 m-0" }, "例如："),
                              ...props.examples.map((ex) =>
                                  h("p", { class: "text-xs text-[#cdcdcd] leading-relaxed mb-0.5 m-0" }, `· ${ex}`)
                              ),
                          ])
                        : null,
                    // 文本域 —— 使用引入的 ElInput 组件
                    h(ElInput, {
                        modelValue: props.modelValue,
                        type: "textarea",
                        rows: 5,
                        maxlength: props.maxLength,
                        resize: "none",
                        class: "rich-textarea",
                        style: { background: "transparent" },
                        "onUpdate:modelValue": (val: string) => emit("update:modelValue", val),
                        onFocus: () => {
                            focused.value = true;
                        },
                        onBlur: () => {
                            focused.value = false;
                        },
                    }),
                    // 字数统计
                    h("div", { class: "flex justify-end mt-1" }, [
                        h("span", { class: "text-xs text-[#cdcdcd]" }, `${props.modelValue.length}/${props.maxLength}`),
                    ]),
                ]
            );
    },
});

// ════════════════════════════════════════════════════
// 主逻辑
// ════════════════════════════════════════════════════
const loading = ref(false);
const saving = ref(false);
const personId = ref("");
const maxLength = 2000;

const formData = reactive({
    persona_id: "",
    main_business: "",
    target_pain_points: "",
    conversion_hook: "",
});

// ─── 校验 ────────────────────────────────────────────────────────
const checkForm = (): boolean => {
    return true;
};

// ─── 保存 ────────────────────────────────────────────────────────
const handleSave = async () => {
    if (!checkForm()) return;
    await updateKnowledgeConfig(formData);
    close();
    emit("success");
};
const { isLock, lockFn } = useLockFn(handleSave);

// ─── 对外暴露 ────────────────────────────────────────────────────
const open = (id: string) => {
    formData.persona_id = id;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({ open, setFormData: (data: any) => setFormData(data, formData) });
</script>

<style scoped>
:deep(.rich-textarea .el-textarea__inner) {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
    font-size: 13px;
    line-height: 1.8;
    color: #374151;
    resize: none;
}
</style>
