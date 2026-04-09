<template>
    <popup
        ref="popupRef"
        title="获客与截流设置"
        :async="true"
        width="700px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <config-card-inner
                title="获客线索词"
                desc="监控视频号账号，出现以下词汇立即寻找线索"
                icon="🔍"
                icon-color="#FF4D4F"
                icon-bg="#FFF0F0">
                <tag-list-inner
                    :items="configData.acquisitionWords"
                    add-text="添加"
                    @add="handleAdd('acquisitionWords')"
                    @edit="handleEdit('acquisitionWords', $event)"
                    @remove="removeTag('acquisitionWords', $event)" />
            </config-card-inner>

            <config-card-inner
                title="截流线索词"
                desc="社媒平台寻找视频，出现以下词汇立即进入寻找评论"
                icon="📡"
                icon-color="#FF8C00"
                icon-bg="#FFF5F0">
                <tag-list-inner
                    :items="configData.interceptionWords"
                    add-text="添加"
                    @add="handleAdd('interceptionWords')"
                    @edit="handleEdit('interceptionWords', $event)"
                    @remove="removeTag('interceptionWords', $event)" />
            </config-card-inner>

            <config-card-inner
                title="评论区引流话术"
                desc="评论区回复，引导用户看私信或主页"
                icon="💬"
                icon-color="#00C08E"
                icon-bg="#E6F8F3">
                <script-list-inner
                    :items="configData.commentScripts"
                    add-text="添加话术"
                    @add="handleAdd('commentScripts')"
                    @edit="handleEdit('commentScripts', $event)"
                    @remove="removeTag('commentScripts', $event)" />
            </config-card-inner>

            <!-- ── 私信转化话术 ── -->
            <config-card-inner
                title="私信转化话术"
                desc="自动发送私信，直接留资或成交"
                icon="✉️"
                icon-color="#0065FB"
                icon-bg="#E6F0FF">
                <script-list-inner
                    :items="configData.dmScripts"
                    add-text="添加话术"
                    @add="handleAdd('dmScripts')"
                    @edit="handleEdit('dmScripts', $event)"
                    @remove="removeTag('dmScripts', $event)" />
            </config-card-inner>

            <config-card-inner title="触达时间限制" desc="" icon="🕐" icon-color="#8B5CF6" icon-bg="#F3F0FF">
                <div class="mb-5">
                    <div class="text-sm font-bold mb-2" style="color: #1f2937">内容发布日期</div>
                    <div class="grid grid-cols-4 gap-2">
                        <div
                            v-for="option in timeOptions"
                            :key="'content-' + option.value"
                            class="h-9 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-200 select-none text-xs font-medium"
                            :style="
                                configData.contentPublishTime === option.value
                                    ? 'background:#1F2937;color:#ffffff'
                                    : 'background:#F3F4F6;color:#6B7280'
                            "
                            @click="configData.contentPublishTime = option.value">
                            {{ option.label }}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-bold mb-2" style="color: #1f2937">评论发布日期</div>
                    <div class="grid grid-cols-4 gap-2">
                        <div
                            v-for="option in timeOptions"
                            :key="'comment-' + option.value"
                            class="h-9 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-200 select-none text-xs font-medium"
                            :style="
                                configData.commentPublishTime === option.value
                                    ? 'background:#1F2937;color:#ffffff'
                                    : 'background:#F3F4F6;color:#6B7280'
                            "
                            @click="configData.commentPublishTime = option.value">
                            {{ option.label }}
                        </div>
                    </div>
                </div>
            </config-card-inner>

            <config-card-inner title="防封控与频率限制" desc="" icon="⚙️" icon-color="#0065FB" icon-bg="#E6F0FF">
                <div class="rounded-lg p-3 mb-5 text-xs text-primary leading-relaxed" style="background: #eff6ff">
                    已开启"拟人随机停顿"。每次互动后，系统将随机停留 30秒~2分钟，模拟真人浏览行为，降低风控风险。
                </div>
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold" style="color: #1f2937">截流主动私信每天最大互动人数</span>
                        <span class="text-sm font-extrabold text-primary">{{ configData.messageNumber }}人</span>
                    </div>
                    <el-slider
                        v-model="configData.messageNumber"
                        :min="1"
                        :max="30"
                        :show-tooltip="false"
                        class="mb-1" />
                    <div class="flex justify-between text-xs" style="color: #9ca3af">
                        <span>保守 (防封)</span><span>激进 (易封)</span>
                    </div>
                </div>
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold" style="color: #1f2937">同城触达评论每天最大互动人数</span>
                        <span class="text-sm font-extrabold text-primary">{{ configData.commentNumber }}人</span>
                    </div>
                    <el-slider
                        v-model="configData.commentNumber"
                        :min="1"
                        :max="30"
                        :show-tooltip="false"
                        class="mb-1" />
                    <div class="flex justify-between text-xs" style="color: #9ca3af">
                        <span>保守 (防封)</span><span>激进 (易封)</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold" style="color: #1f2937">私信每接管个用户回复数</span>
                        <span class="text-sm font-extrabold text-primary">
                            {{ configData.replyNumber === 1 ? "1条" : "无限制" }}
                        </span>
                    </div>
                    <el-slider v-model="configData.replyNumber" :min="1" :max="2" :show-tooltip="false" class="mb-1" />
                    <div class="flex justify-between text-xs" style="color: #9ca3af">
                        <span>1条</span><span>无限制</span>
                    </div>
                </div>
            </config-card-inner>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { defineComponent, h } from "vue";
import { User, Plus, Close, ArrowUp, ArrowDown } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { getPersonDetail, getTrafficConfig, updateTrafficConfig } from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import { useLockFn } from "@/hooks/useLockFn";

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

// ════════════════════════════════════════════════════
// 内联子组件：ConfigCardInner
// ════════════════════════════════════════════════════
const ConfigCardInner = defineComponent({
    name: "ConfigCardInner",
    props: {
        title: { type: String, required: true },
        desc: { type: String, default: "" },
        icon: { type: String, default: "" },
        iconColor: { type: String, default: "#0065FB" },
        iconBg: { type: String, default: "#E6F0FF" },
    },
    setup(props, { slots }) {
        return () =>
            h("div", { class: "rounded-xl p-4 shadow-sm mb-3", style: "background:#ffffff;border:1px solid #F3F4F6" }, [
                h("div", { class: "flex items-center gap-3 mb-4" }, [
                    h(
                        "div",
                        {
                            class: "w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-lg",
                            style: { background: props.iconBg },
                        },
                        props.icon
                    ),
                    h("div", { class: "flex flex-col" }, [
                        h("span", { class: "text-sm font-extrabold", style: "color:#111827" }, props.title),
                        props.desc ? h("span", { class: "text-xs mt-0.5", style: "color:#9CA3AF" }, props.desc) : null,
                    ]),
                ]),
                slots.default?.(),
            ]);
    },
});

// ════════════════════════════════════════════════════
// 内联子组件：TagListInner（标签胶囊形式）
// ════════════════════════════════════════════════════
const TagListInner = defineComponent({
    name: "TagListInner",
    props: {
        items: { type: Array as () => string[], required: true },
        addText: { type: String, default: "添加" },
        defaultShowCount: { type: Number, default: 3 },
    },
    emits: ["add", "edit", "remove"],
    setup(props, { emit }) {
        const isExpanded = ref(false);
        const displayedItems = computed(() =>
            isExpanded.value ? props.items : props.items.slice(0, props.defaultShowCount)
        );
        watch(
            () => props.items.length,
            (n, o) => {
                if (n > o) isExpanded.value = true;
            }
        );

        return () =>
            h("div", { class: "flex flex-wrap gap-2" }, [
                // 已有标签
                ...displayedItems.value.map((item, index) =>
                    h(
                        "div",
                        {
                            key: index,
                            class: "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer transition-colors",
                            style: "background:#F9FAFB;border:1px solid #E5E7EB",
                            onClick: () => emit("edit", index),
                        },
                        [
                            h("span", { class: "text-xs", style: "color:#374151" }, item),
                            h(
                                "div",
                                {
                                    class: "w-4 h-4 flex items-center justify-center rounded-full transition-colors",
                                    style: "background:#D1D5DB",
                                    onClick: (e: Event) => {
                                        e.stopPropagation();
                                        emit("remove", index);
                                    },
                                },
                                [h(Close, { style: "width:10px;height:10px;color:#fff" })]
                            ),
                        ]
                    )
                ),
                // 添加按钮
                h(
                    "div",
                    {
                        class: "inline-flex items-center gap-1 px-3 py-1.5 rounded-full cursor-pointer transition-colors",
                        style: "border:2px dashed #BFDBFE;background:#EFF6FF",
                        onClick: () => emit("add"),
                    },
                    [
                        h(Plus, { style: "width:12px;height:12px;color:#0065fb" }),
                        h("span", { class: "text-xs font-medium text-primary" }, props.addText),
                    ]
                ),
                // 展开/收起
                props.items.length > props.defaultShowCount
                    ? h(
                          "div",
                          {
                              class: "inline-flex items-center gap-0.5 px-3 py-1.5 rounded-full cursor-pointer",
                              style: "background:#F3F4F6",
                              onClick: () => {
                                  isExpanded.value = !isExpanded.value;
                              },
                          },
                          [
                              h(
                                  "span",
                                  { class: "text-xs", style: "color:#9CA3AF" },
                                  isExpanded.value ? "收起" : `+${props.items.length - props.defaultShowCount} 个`
                              ),
                              h(isExpanded.value ? ArrowUp : ArrowDown, {
                                  style: "width:12px;height:12px;color:#9CA3AF",
                              }),
                          ]
                      )
                    : null,
            ]);
    },
});

// ════════════════════════════════════════════════════
// 内联子组件：ScriptListInner（话术列表形式）
// ════════════════════════════════════════════════════
const ScriptListInner = defineComponent({
    name: "ScriptListInner",
    props: {
        items: { type: Array as () => string[], required: true },
        addText: { type: String, default: "添加话术" },
        defaultShowCount: { type: Number, default: 2 },
    },
    emits: ["add", "edit", "remove"],
    setup(props, { emit }) {
        const isExpanded = ref(false);
        const displayedItems = computed(() =>
            isExpanded.value ? props.items : props.items.slice(0, props.defaultShowCount)
        );
        watch(
            () => props.items.length,
            (n, o) => {
                if (n > o) isExpanded.value = true;
            }
        );

        return () =>
            h("div", { class: "flex flex-col gap-2" }, [
                // 已有话术
                ...displayedItems.value.map((item, index) =>
                    h(
                        "div",
                        {
                            key: index,
                            class: "flex items-start justify-between p-3 rounded-xl cursor-pointer transition-colors",
                            style: "background:#F9FAFB;border:1px solid #E5E7EB",
                            onClick: () => emit("edit", index),
                        },
                        [
                            h("span", { class: "text-xs leading-relaxed flex-1 pr-3", style: "color:#374151" }, item),
                            h(
                                "div",
                                {
                                    class: "w-5 h-5 flex-shrink-0 flex items-center justify-center rounded-full transition-colors mt-0.5",
                                    style: "background:#D1D5DB",
                                    onClick: (e: Event) => {
                                        e.stopPropagation();
                                        emit("remove", index);
                                    },
                                },
                                [h(Close, { style: "width:10px;height:10px;color:#fff" })]
                            ),
                        ]
                    )
                ),
                // 展开/收起
                props.items.length > props.defaultShowCount
                    ? h(
                          "div",
                          {
                              class: "flex items-center justify-center py-1 gap-1 cursor-pointer",
                              onClick: () => {
                                  isExpanded.value = !isExpanded.value;
                              },
                          },
                          [
                              h(
                                  "span",
                                  { class: "text-xs", style: "color:#9CA3AF" },
                                  isExpanded.value ? "收起" : `查看全部 ${props.items.length} 条`
                              ),
                              h(isExpanded.value ? ArrowUp : ArrowDown, {
                                  style: "width:12px;height:12px;color:#9CA3AF",
                              }),
                          ]
                      )
                    : null,
                // 添加按钮
                h(
                    "div",
                    {
                        class: "flex items-center justify-center w-full py-2.5 rounded-xl cursor-pointer transition-colors",
                        style: "border:2px dashed #BFDBFE;background:#EFF6FF",
                        onClick: () => emit("add"),
                    },
                    [
                        h(Plus, { style: "width:14px;height:14px;color:#0065fb" }),
                        h("span", { class: "text-xs font-medium text-primary ml-1" }, props.addText),
                    ]
                ),
            ]);
    },
});

// ════════════════════════════════════════════════════
// 主逻辑
// ════════════════════════════════════════════════════
type ConfigKey = "acquisitionWords" | "interceptionWords" | "commentScripts" | "dmScripts";

interface ConfigData {
    acquisitionWords: string[];
    interceptionWords: string[];
    commentScripts: string[];
    dmScripts: string[];
    messageNumber: number;
    commentNumber: number;
    replyNumber: number;
    contentPublishTime: number;
    commentPublishTime: number;
}

const TITLE_MAP: Record<ConfigKey, string> = {
    acquisitionWords: "获客线索词",
    interceptionWords: "截流线索词",
    commentScripts: "评论区话术",
    dmScripts: "私信转化话术",
};

const timeOptions = [
    { label: "当天", value: 1 },
    { label: "2天内", value: 2 },
    { label: "3天内", value: 3 },
    { label: "4天内", value: 4 },
    { label: "5天内", value: 5 },
    { label: "6天内", value: 6 },
    { label: "7天内", value: 7 },
    { label: "不限制", value: -1 },
];

const loading = ref(false);
const saving = ref(false);
const personId = ref("");
const personName = ref("");

const configData = reactive<ConfigData>({
    acquisitionWords: [],
    interceptionWords: [],
    commentScripts: [],
    dmScripts: [],
    messageNumber: 15,
    commentNumber: 15,
    replyNumber: 1,
    contentPublishTime: 1,
    commentPublishTime: 1,
});

// ─── 关键词编辑 ──────────────────────────────────────────────────
const openKeywordsPrompt = async (title: string, defaultValue = ""): Promise<string | null> => {
    try {
        const { value } = await ElMessageBox.prompt("", title, {
            confirmButtonText: "确认",
            cancelButtonText: "取消",
            inputValue: defaultValue,
            inputPlaceholder: `请输入${title}`,
            inputValidator: (val) => !!val?.trim() || "内容不能为空",
            customClass: "keywords-prompt",
        });
        return value?.trim() ?? null;
    } catch {
        return null;
    }
};

// ─── 标签操作 ────────────────────────────────────────────────────
const removeTag = (type: ConfigKey, index: number) => {
    configData[type].splice(index, 1);
};

const handleEdit = async (type: ConfigKey, index: number) => {
    const val = await openKeywordsPrompt(TITLE_MAP[type], configData[type][index]);
    if (val !== null) configData[type][index] = val;
};

const handleAdd = async (type: ConfigKey) => {
    const val = await openKeywordsPrompt(TITLE_MAP[type]);
    if (val !== null) configData[type].push(val);
};

// ─── 校验 ────────────────────────────────────────────────────────
const checkConfig = (): boolean => {
    const checks: [string[], string][] = [
        [configData.acquisitionWords, "请添加获客线索词"],
        [configData.interceptionWords, "请添加截流线索词"],
        [configData.commentScripts, "请添加评论区话术"],
        [configData.dmScripts, "请添加私信转化话术"],
    ];
    for (const [arr, msg] of checks) {
        if (arr.length === 0) {
            ElMessage.warning(msg);
            return false;
        }
    }
    return true;
};

// ─── 保存 ────────────────────────────────────────────────────────
const handleSave = async () => {
    if (!checkConfig()) return;
    await updateTrafficConfig({
        persona_id: personId.value,
        acquire_keywords: configData.acquisitionWords,
        intercept_keywords: configData.interceptionWords,
        comment_scripts: configData.commentScripts,
        dm_scripts: configData.dmScripts,
        message_number: configData.messageNumber,
        comment_number: configData.commentNumber,
        reply_number: configData.replyNumber === 2 ? 0 : 1,
        content_publish_day: configData.contentPublishTime === -1 ? 0 : configData.contentPublishTime,
        comment_publish_day: configData.commentPublishTime === -1 ? 0 : configData.commentPublishTime,
    });
    close();
    emit("success");
};

const { isLock, lockFn } = useLockFn(handleSave);

const close = () => emit("close");

// ─── 初始化 ──────────────────────────────────────────────────────
const getDetail = async () => {
    loading.value = true;
    try {
        const [configRes] = await Promise.allSettled([getTrafficConfig({ id: personId.value })]);
        if (configRes.status === "fulfilled") {
            const d = configRes.value;
            configData.acquisitionWords = d.acquire_keywords ?? [];
            configData.interceptionWords = d.intercept_keywords ?? [];
            configData.commentScripts = d.comment_scripts ?? [];
            configData.dmScripts = d.dm_scripts ?? [];
            configData.messageNumber = d.message_number ?? 15;
            configData.commentNumber = d.comment_number ?? 15;
            configData.replyNumber = d.reply_number === 0 ? 2 : 1;
            configData.contentPublishTime = d.content_publish_day === 0 ? -1 : d.content_publish_day ?? 1;
            configData.commentPublishTime = d.comment_publish_day === 0 ? -1 : d.comment_publish_day ?? 1;
        }
    } finally {
        loading.value = false;
    }
};

// ─── 对外暴露 ────────────────────────────────────────────────────
const open = (id: string) => {
    personId.value = id;
    console.log(id);
    getDetail();
    popupRef.value?.open();
};

defineExpose({ open });
</script>

<style scoped>
:deep(.el-slider__runway) {
    background: #e5e7eb;
}
:deep(.el-slider__bar) {
    background: #0065fb;
}
:deep(.el-slider__button) {
    border-color: #0065fb;
}
</style>
