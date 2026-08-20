<template>
    <div class="chat-model-toolbar">
        <ElPopover
            v-model:visible="showModelPopup"
            placement="top-start"
            :width="280"
            trigger="click"
            :show-arrow="false"
            popper-class="chat-model-popper"
            :popper-options="{ modifiers: [{ name: 'offset', options: { offset: [0, 8] } }] }">
            <template #reference>
                <button type="button" class="model-pill">
                    <span class="model-pill-avatar">
                        <img
                            v-if="currentModel?.logo && !isLogoBroken(currentModel)"
                            :src="currentModel.logo"
                            class="model-pill-logo"
                            alt=""
                            @error="markLogoBroken(currentModel)" />
                        <span v-else class="model-pill-fallback">{{ modelInitial(currentModel?.name) }}</span>
                    </span>
                    <span class="model-pill-name">{{ currentModel?.name || "选择模型" }}</span>
                    <Icon name="el-icon-CaretBottom" :size="10" class="model-pill-caret" />
                </button>
            </template>

            <div class="model-popup-panel">
                <div class="model-popup-title">选择模型</div>
                <div class="model-popup-list">
                    <button
                        v-for="item in models"
                        :key="getModelKey(item)"
                        type="button"
                        class="model-popup-item"
                        :class="{ active: isModelActive(item) }"
                        @click="selectModel(item)">
                        <span class="model-pill-avatar">
                            <img
                                v-if="item.logo && !isLogoBroken(item)"
                                :src="item.logo"
                                class="model-pill-logo"
                                alt=""
                                @error="markLogoBroken(item)" />
                            <span v-else class="model-pill-fallback">{{ modelInitial(item.name) }}</span>
                        </span>
                        <span class="model-popup-item-name">{{ item.name }}</span>
                        <Icon
                            v-if="isModelActive(item)"
                            name="el-icon-Check"
                            :size="14"
                            class="shrink-0 text-primary" />
                    </button>
                </div>
            </div>
        </ElPopover>

        <humanize-pop
            v-if="showSettings"
            ref="humanizePopRef"
            variant="toolbar"
            :model-id="currModel.model_id"
            :model-sub-id="currModel.model_sub_id" />
    </div>
</template>

<script setup lang="ts">
import { ElPopover } from "element-plus";
import cloneDeep from "lodash/cloneDeep";
import { useAppStore } from "@/stores/app";
import { useSelectedChatModel } from "@/composables/useSelectedChatModel";
import HumanizePop from "./humanize-pop.vue";

interface ChatModel {
    id?: string | number;
    name?: string;
    model_id: string;
    model_sub_id?: string;
    logo?: string;
    status?: string;
}

withDefaults(
    defineProps<{
        /** 是否在模型旁内联展示设置；对话页改为放右侧时传 false */
        showSettings?: boolean;
    }>(),
    { showSettings: true },
);

const emit = defineEmits<{
    "model-change": [model: ChatModel];
}>();

const appStore = useAppStore();
const { selectedChatModel, setSelectedChatModel } = useSelectedChatModel();

const currModel = ref<ChatModel>({ model_id: "", model_sub_id: "" });
const showModelPopup = ref(false);
const humanizePopRef = shallowRef<InstanceType<typeof HumanizePop>>();
const brokenLogoKeys = ref(new Set<string>());

const models = computed(() => cloneDeep(appStore.getAllowedChatModel));

const getModelKey = (item?: ChatModel | null) => {
    if (!item) return "";
    if (item.id != null && item.id !== "") return String(item.id);
    if (!item.model_id) return "";
    return `${item.model_id}_${item.model_sub_id ?? ""}`;
};

const matchModel = (list: ChatModel[], model_id?: string | number, model_sub_id?: string | number) => {
    if (model_id == null || model_id === "") return undefined;
    return list.find(
        (item) =>
            String(item.model_id) === String(model_id) &&
            String(item.model_sub_id ?? "") === String(model_sub_id ?? ""),
    );
};

const isModelActive = (item: ChatModel) => getModelKey(item) === getModelKey(currModel.value);

const currentModel = computed(() => {
    const matched = models.value.find((item) => isModelActive(item));
    if (matched) return matched;
    if (currModel.value.model_id) return currModel.value;
    return models.value[0];
});

const modelInitial = (name?: string) => (name?.trim()?.charAt(0) || "M").toUpperCase();

const isLogoBroken = (item?: ChatModel | null) => brokenLogoKeys.value.has(getModelKey(item));

const markLogoBroken = (item?: ChatModel | null) => {
    const key = getModelKey(item);
    if (!key) return;
    brokenLogoKeys.value = new Set([...brokenLogoKeys.value, key]);
};

const persistModel = (model?: ChatModel | null) => {
    if (!model?.model_id) return;
    setSelectedChatModel({
        model_id: model.model_id,
        model_sub_id: model.model_sub_id,
    });
};

const resolveInitialModel = (list: ChatModel[]) => {
    if (!list.length) return null;
    const preferred = selectedChatModel.value;
    const matched = matchModel(list, preferred?.model_id, preferred?.model_sub_id);
    return cloneDeep(matched || list[0]);
};

onMounted(() => {
    appStore.ensureMemberQuota();
});

watch(
    models,
    (list) => {
        if (!list.length) return;
        if (!list.some((item) => isModelActive(item))) {
            const next = resolveInitialModel(list);
            if (next) currModel.value = next;
        }
    },
    { immediate: true },
);

watch(
    currentModel,
    (model) => {
        if (model?.model_id) {
            emit("model-change", cloneDeep(model));
        }
    },
    { immediate: true, deep: true },
);

const selectModel = (item: ChatModel) => {
    currModel.value = cloneDeep(item);
    persistModel(item);
    showModelPopup.value = false;
};

defineExpose({
    currModel,
    getModelConfig: () => {
        // 与展示态一致：未手动选择时回退到列表首项，避免 UI 显示有模型但请求未带 model_id
        const model = currentModel.value;
        if (!currModel.value.model_id && model?.model_id) {
            currModel.value = cloneDeep(model);
        }
        return {
            model_id: model?.model_id || undefined,
            model_sub_id: model?.model_sub_id || undefined,
        };
    },
    getHumanizeFormData: () => {
        const data = humanizePopRef.value?.formData;
        if (!data) return {};
        // 不透出 model_id/model_sub_id，选模型结果以 getModelConfig 为准
        const { model_id: _modelId, model_sub_id: _modelSubId, ...rest } = data;
        return rest;
    },
});
</script>

<style scoped lang="scss">
.chat-model-toolbar {
    @apply relative z-[10] flex items-center gap-2;
}

.model-pill {
    @apply inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-[18px] border border-[#ebedf0] bg-white py-[4.5px] pl-1 pr-3 text-[13px] text-[#1f2937] transition-colors duration-150 ease-[ease];

    &:hover {
        @apply border-[#93c5fd];
    }
}

.model-pill-avatar {
    @apply inline-flex h-[22px] w-[22px] flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#f3f5f9];
}

.model-pill-logo {
    @apply h-full w-full object-cover;
}

.model-pill-fallback {
    @apply text-[10px] font-semibold text-[#2563eb];
}

.model-pill-name {
    @apply max-w-[120px] truncate font-medium;
}

.model-pill-caret {
    @apply text-[#c4c8cf];
}

.model-popup-panel {
    @apply flex min-h-0 flex-col;
}

.model-popup-title {
    @apply mb-2 shrink-0 px-1 text-[13px] font-semibold text-[#1f2937];
}
</style>

<style lang="scss">
.chat-model-popper {
    @apply rounded-[14px] border border-[#ebedf0] bg-white p-3 shadow-[0_8px_32px_rgba(0,0,0,0.12)] !important;
    max-height: 320px !important;
    overflow: hidden !important;
    box-sizing: border-box !important;

    &.el-popper,
    .el-popover__content {
        max-height: inherit;
        overflow: hidden;
        box-sizing: border-box;
    }

    .model-popup-panel {
        display: flex;
        flex-direction: column;
        max-height: 320px;
        min-height: 0;
    }

    .model-popup-title {
        flex-shrink: 0;
        margin-bottom: 8px;
        padding: 0 4px;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
    }

    .model-popup-list {
        flex: 1;
        min-height: 0;
        max-height: 280px;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding-right: 2px;

        &::-webkit-scrollbar {
            width: 4px;
        }

        &::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: #e5e7eb;
        }
    }

    .model-popup-item {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 8px;
        border: 0;
        background: transparent;
        margin-bottom: 2px;
        cursor: pointer;
        border-radius: 8px;
        padding: 8px 10px;
        text-align: left;
        font-size: 13px;
        color: #4b5563;
        transition: background-color 0.15s ease;

        &:last-child {
            margin-bottom: 0;
        }

        &:hover {
            background: #f3f5f9;
        }

        &.active {
            background: #eff6ff;
            color: #2563eb;
        }
    }

    .model-popup-item-name {
        min-width: 0;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .model-pill-avatar {
        display: inline-flex;
        height: 22px;
        width: 22px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 9999px;
        background: #f3f5f9;
    }

    .model-pill-logo {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }

    .model-pill-fallback {
        font-size: 10px;
        font-weight: 600;
        color: #2563eb;
    }
}
</style>
