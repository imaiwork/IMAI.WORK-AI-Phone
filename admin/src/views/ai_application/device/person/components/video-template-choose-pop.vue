<template>
    <el-dialog
        v-model="visible"
        title="视频模板选择"
        width="920px"
        top="6vh"
        append-to-body
        destroy-on-close
        :close-on-click-modal="false"
        class="tpl-choose-dialog"
        @closed="handleClosed">
        <div class="tpl-choose">
            <div class="tpl-tabs">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.apiType"
                    type="button"
                    class="tpl-tab"
                    :class="{ on: activeType === tab.apiType }"
                    @click="handleSwitchType(tab.apiType)">
                    <span>{{ tab.label }}</span>
                    <span v-if="tab.mode === TemplateModeEnum.Custom && tab.count > 0" class="tpl-tab-cnt">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <div class="tpl-mode-row">
                <button
                    type="button"
                    class="tpl-mode"
                    :class="{ on: currentItem.mode === TemplateModeEnum.Auto }"
                    @click="handleSetMode(TemplateModeEnum.Auto)">
                    自动随机
                </button>
                <button
                    type="button"
                    class="tpl-mode"
                    :class="{ on: currentItem.mode === TemplateModeEnum.Custom }"
                    @click="handleSetMode(TemplateModeEnum.Custom)">
                    {{ customModeLabel }}
                </button>
            </div>
            <div class="tpl-hint">{{ modeHint }}</div>

            <div class="tpl-body">
                <div v-if="currentItem.mode === TemplateModeEnum.Auto" class="tpl-auto">
                    <el-icon :size="36" class="text-primary mb-3"><Refresh /></el-icon>
                    <div class="text-sm font-bold text-[#1D2129]">已开启自动随机</div>
                    <div class="text-xs text-[#86909C] mt-2 leading-relaxed text-center max-w-[360px]">
                        AI 将在该类型的全部模板中自动随机使用，无需手动选择模板
                    </div>
                </div>

                <div v-else v-loading="loading" class="tpl-grid-wrap">
                    <div v-if="!loading && currentTemplateList.length === 0" class="tpl-empty">暂无可用模板</div>
                    <div v-else class="tpl-grid">
                        <div
                            v-for="template in currentTemplateList"
                            :key="template.templateID"
                            class="tpl-card"
                            :class="{ selected: isSelected(template.templateID) }"
                            @click="toggleSelect(template)">
                            <div class="tpl-cover">
                                <el-image :src="template.pic" fit="cover" lazy class="w-full h-full" />
                                <div class="tpl-check">
                                    <el-icon v-if="isSelected(template.templateID)" :size="12" class="text-white">
                                        <Check />
                                    </el-icon>
                                </div>
                                <button
                                    v-if="template.link || template.pic"
                                    type="button"
                                    class="tpl-preview"
                                    @click.stop="previewTemplate(template)">
                                    预览
                                </button>
                            </div>
                            <div class="tpl-name">{{ template.name }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between">
                <div class="text-xs text-[#86909C]">
                    当前类型已选
                    <span class="text-primary font-bold mx-0.5">{{ currentItem.selected_count }}</span>
                    个
                </div>
                <div class="flex gap-2">
                    <el-button @click="visible = false">取消</el-button>
                    <el-button type="primary" @click="confirmSelection">确定</el-button>
                </div>
            </div>
        </template>
    </el-dialog>

    <el-dialog
        v-model="previewVisible"
        title="模板预览"
        width="720px"
        append-to-body
        destroy-on-close
        @closed="previewUrl = ''">
        <video v-if="previewUrl" :src="previewUrl" controls autoplay class="w-full max-h-[70vh] rounded-xl bg-black" />
        <el-image v-else-if="previewPic" :src="previewPic" fit="contain" class="w-full max-h-[70vh]" />
        <div v-else class="text-center text-[#86909C] py-10">暂无预览</div>
    </el-dialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Check, Refresh } from "@element-plus/icons-vue";
import { getShanjianClipTemplateList } from "@/api/ai_application/device/person";
import feedback from "@/utils/feedback";
import { SYNTH_TYPE_SCENE, TemplateConfigMap, TemplateModeEnum } from "../enums/template";
import {
    buildTemplateConfigForTypes,
    findEmptyCustomType,
    getSynthApiFullLabel,
    normalizeTemplateItem,
} from "../utils/template-config";

interface ClipTemplate {
    name: string;
    pic: string;
    link: string;
    templateID: string;
}

const props = defineProps<{
    modelValue: boolean;
    types: number[];
    config: TemplateConfigMap;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", val: boolean): void;
    (e: "confirm", config: TemplateConfigMap): void;
}>();

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

const activeType = ref(0);
const typeList = ref<number[]>([]);
const configMap = reactive<TemplateConfigMap>({});
const templateList = ref<ClipTemplate[]>([]);
const loading = ref(false);
const listCache = reactive<Record<number, ClipTemplate[]>>({});

const previewVisible = ref(false);
const previewUrl = ref("");
const previewPic = ref("");

const currentTemplateList = computed(() => templateList.value);
const currentItem = computed(() => normalizeTemplateItem(activeType.value, configMap[String(activeType.value)]));

const typeTabs = computed(() =>
    typeList.value.map((apiType) => {
        const item = normalizeTemplateItem(apiType, configMap[String(apiType)]);
        return {
            apiType,
            label: getSynthApiFullLabel(apiType),
            mode: item.mode,
            count: item.selected_count,
        };
    }),
);

const customModeLabel = computed(() => {
    const count = currentItem.value.selected_count;
    return currentItem.value.mode === TemplateModeEnum.Custom && count ? `自定义模板（${count}）` : "自定义模板";
});

const modeHint = computed(() => {
    const name = getSynthApiFullLabel(activeType.value);
    if (currentItem.value.mode === TemplateModeEnum.Custom) {
        const count = currentItem.value.selected_count;
        return count ? `「${name}」已选 ${count} 个自定义模板` : `「${name}」已选 0 个自定义模板，请在下方勾选`;
    }
    return `AI 将在「${name}」的全部模板中自动随机使用`;
});

const ensureTypeItem = (apiType: number) => {
    const key = String(apiType);
    if (!configMap[key]) {
        configMap[key] = normalizeTemplateItem(apiType);
    }
};

const writeItem = (apiType: number, patch: Partial<TemplateConfigMap[string]>) => {
    const key = String(apiType);
    configMap[key] = normalizeTemplateItem(apiType, { ...configMap[key], ...patch });
};

const normalizeTemplate = (item: any): ClipTemplate => ({
    name: item.name,
    pic: item.cover_url,
    link: item.demo_url,
    templateID: String(item.id),
});

const isSelected = (id: string) => currentItem.value.template_ids.includes(id);

const toggleSelect = (template: ClipTemplate) => {
    if (currentItem.value.mode !== TemplateModeEnum.Custom) return;
    const id = template.templateID;
    const ids = [...currentItem.value.template_ids];
    const index = ids.indexOf(id);
    if (index > -1) ids.splice(index, 1);
    else ids.push(id);
    writeItem(activeType.value, { template_ids: ids });
};

const previewTemplate = (template: ClipTemplate) => {
    if (template.link) {
        previewUrl.value = template.link;
        previewPic.value = "";
        previewVisible.value = true;
        return;
    }
    if (template.pic) {
        previewUrl.value = "";
        previewPic.value = template.pic;
        previewVisible.value = true;
        return;
    }
    feedback.msgWarning("暂无预览");
};

const fetchTemplateList = async (apiType: number) => {
    if (listCache[apiType]) {
        templateList.value = listCache[apiType];
        return;
    }

    loading.value = true;
    try {
        const res: any = await getShanjianClipTemplateList({
            scene: SYNTH_TYPE_SCENE[apiType],
            auto_type: 1,
            page_no: 1,
            page_size: 999,
        });
        const lists = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
        const mapped = lists.map(normalizeTemplate);
        listCache[apiType] = mapped;
        templateList.value = mapped;

        const validIds = new Set(mapped.map((t: ClipTemplate) => t.templateID));
        const item = normalizeTemplateItem(apiType, configMap[String(apiType)]);
        if (item.mode === TemplateModeEnum.Custom) {
            const nextIds = item.template_ids.filter((id) => validIds.has(id));
            if (nextIds.length !== item.template_ids.length) {
                writeItem(apiType, { template_ids: nextIds });
            }
        }
    } catch {
        templateList.value = [];
        feedback.msgError("风格模板加载失败");
    } finally {
        loading.value = false;
    }
};

const handleSwitchType = async (apiType: number) => {
    if (activeType.value === apiType) return;
    activeType.value = apiType;
    ensureTypeItem(apiType);
    if (normalizeTemplateItem(apiType, configMap[String(apiType)]).mode === TemplateModeEnum.Custom) {
        await fetchTemplateList(apiType);
    } else {
        templateList.value = listCache[apiType] || [];
    }
};

const handleSetMode = async (mode: TemplateModeEnum) => {
    writeItem(activeType.value, {
        mode,
        template_ids: mode === TemplateModeEnum.Auto ? [] : currentItem.value.template_ids,
    });
    if (mode === TemplateModeEnum.Custom) {
        await fetchTemplateList(activeType.value);
    }
};

const resetLocal = () => {
    activeType.value = 0;
    typeList.value = [];
    Object.keys(configMap).forEach((key) => delete configMap[key]);
    Object.keys(listCache).forEach((key) => delete listCache[Number(key)]);
    templateList.value = [];
};

const initFromProps = async () => {
    resetLocal();
    const types = (props.types || []).map((n) => Number(n)).filter((n) => SYNTH_TYPE_SCENE[n]);
    typeList.value = types.length ? types : Object.keys(SYNTH_TYPE_SCENE).map(Number);
    typeList.value.forEach((apiType) => {
        configMap[String(apiType)] = normalizeTemplateItem(apiType, props.config?.[String(apiType)]);
    });
    activeType.value = typeList.value[0];
    ensureTypeItem(activeType.value);
    if (currentItem.value.mode === TemplateModeEnum.Custom) {
        await fetchTemplateList(activeType.value);
    }
};

const confirmSelection = () => {
    const payload = buildTemplateConfigForTypes(typeList.value, configMap);
    const emptyType = findEmptyCustomType(typeList.value, payload);
    if (emptyType != null) {
        feedback.msgWarning(`「${getSynthApiFullLabel(emptyType)}」请至少勾选 1 个模板`);
        handleSwitchType(emptyType);
        return;
    }
    emit("confirm", payload);
    visible.value = false;
};

const handleClosed = () => {
    resetLocal();
};

watch(
    () => props.modelValue,
    (val) => {
        if (val) initFromProps();
    },
);
</script>

<style scoped>
.tpl-choose {
    @apply flex flex-col;
}

.tpl-tabs {
    @apply flex items-center gap-5 border-b border-[#F2F3F5] mb-4 overflow-x-auto;
}

.tpl-tab {
    @apply relative flex items-center gap-1.5 pb-3 text-sm font-semibold text-[#86909C] cursor-pointer bg-[transparent] border-0;
}
.tpl-tab.on {
    color: #2f73f6;
}
.tpl-tab.on::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: #2f73f6;
    border-radius: 2px;
}
.tpl-tab-cnt {
    @apply min-w-[18px] h-[18px] px-1 rounded-full text-[11px] leading-[18px] text-center text-white;
    background: #2f73f6;
}

.tpl-mode-row {
    @apply flex gap-2 mb-2;
}
.tpl-mode {
    @apply h-9 px-4 rounded-xl text-xs font-semibold cursor-pointer border border-[transparent];
    background: #f6f8fc;
    color: #6b7280;
}
.tpl-mode.on {
    background: #ebf3ff;
    border-color: #2f73f6;
    color: #2f73f6;
}

.tpl-hint {
    @apply text-xs text-[#86909C] mb-3;
}

.tpl-body {
    min-height: 420px;
    max-height: 52vh;
    overflow: auto;
}

.tpl-auto {
    @apply h-[420px] flex flex-col items-center justify-center;
}

.tpl-empty {
    @apply h-[420px] flex items-center justify-center text-sm text-[#86909C];
}

.tpl-grid-wrap {
    min-height: 420px;
}

.tpl-grid {
    @apply grid grid-cols-4 gap-4;
}

.tpl-card {
    @apply cursor-pointer rounded-2xl overflow-hidden border-2 border-[transparent] bg-white transition-all;
}
.tpl-card.selected {
    border-color: #2f73f6;
}
.tpl-cover {
    @apply relative aspect-[3/4] bg-[#F1F5F9] overflow-hidden;
}
.tpl-check {
    @apply absolute top-2 right-2 w-5 h-5 rounded-full border-2 border-[#ffffff]/60 flex items-center justify-center;
    background: rgba(0, 0, 0, 0.25);
}
.tpl-card.selected .tpl-check {
    background: #2f73f6;
    border-color: #2f73f6;
}
.tpl-preview {
    @apply absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 px-3 py-1.5 rounded-full text-xs font-bold opacity-0 transition-opacity;
    background: rgba(255, 255, 255, 0.92);
    color: #1d2129;
}
.tpl-card:hover .tpl-preview {
    opacity: 1;
}
.tpl-name {
    @apply px-2 py-2.5 text-xs font-semibold text-[#1D2129] text-center truncate;
}
</style>
