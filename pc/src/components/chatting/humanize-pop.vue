<template>
    <ElPopover
        v-model:visible="visible"
        trigger="click"
        :width="300"
        :show-arrow="false"
        placement="top-start"
        popper-class="chat-humanize-popper"
        :popper-options="{ modifiers: [{ name: 'offset', options: { offset: [0, 8] } }] }">
        <template #reference>
            <button type="button" class="humanize-trigger">
                <Icon name="el-icon-Setting" :size="14" />
            </button>
        </template>

        <div class="humanize-panel">
            <div class="humanize-panel-header">
                <span class="humanize-panel-title">模型参数</span>
                <span class="humanize-panel-sub">调整对话风格与输出</span>
            </div>

            <div class="humanize-panel-body">
                <section class="setting-block">
                    <div class="setting-block-title">上下文条数</div>
                    <div class="seg-row">
                        <button
                            v-for="n in 6"
                            :key="n - 1"
                            type="button"
                            class="seg-item"
                            :class="{ active: formData.context_num === n - 1 }"
                            @click="setContextNum(n - 1)">
                            {{ n - 1 }}
                        </button>
                    </div>
                </section>

                <section class="setting-block">
                    <div class="setting-row">
                        <span class="setting-label">创造性</span>
                        <span class="setting-value">{{ formData.temperature.toFixed(1) }}</span>
                    </div>
                    <input
                        type="range"
                        class="setting-range"
                        v-model.number="formData.temperature"
                        min="0"
                        :max="getMaxTemperature"
                        step="0.1"
                        @change="saveConfig" />
                    <div class="seg-hint">
                        <span>精准</span>
                        <span>平衡</span>
                        <span>发散</span>
                    </div>
                </section>

                <section class="setting-block">
                    <div class="setting-row">
                        <span class="setting-label">最大输出</span>
                        <span class="setting-value">{{ formData.max_tokens }}</span>
                    </div>
                    <input
                        type="range"
                        class="setting-range"
                        v-model.number="formData.max_tokens"
                        min="512"
                        :max="getMaxTokens"
                        step="1"
                        @change="saveConfig" />
                </section>

                <section v-if="hasAdvancedSettings" class="setting-block setting-block-advanced">
                    <button type="button" class="advanced-toggle" @click="showAdvanced = !showAdvanced">
                        <span>高级参数</span>
                        <Icon
                            name="el-icon-ArrowDown"
                            :size="12"
                            class="advanced-toggle-icon"
                            :class="{ 'is-open': showAdvanced }" />
                    </button>

                    <div v-show="showAdvanced" class="advanced-panel">
                        <div v-if="formData.model_id != ModelIdEnum.CLAUDE_SONNET_4_5" class="setting-compact">
                            <div class="setting-row">
                                <span class="setting-label">词汇多样性</span>
                                <span class="setting-value">{{ formData.top_p.toFixed(1) }}</span>
                            </div>
                            <input
                                type="range"
                                class="setting-range"
                                v-model.number="formData.top_p"
                                min="0"
                                max="1"
                                step="0.1"
                                @change="saveConfig" />
                        </div>

                        <div v-if="formData.model_id != ModelIdEnum.DEEPSEEK" class="setting-compact">
                            <div class="setting-row">
                                <span class="setting-label">重复词频率</span>
                                <span class="setting-value">{{ formData.frequency_penalty.toFixed(1) }}</span>
                            </div>
                            <input
                                type="range"
                                class="setting-range"
                                v-model.number="formData.frequency_penalty"
                                min="-2"
                                max="2"
                                step="0.1"
                                @change="saveConfig" />
                        </div>

                        <div v-if="formData.model_id != ModelIdEnum.DEEPSEEK" class="setting-compact">
                            <div class="setting-row">
                                <span class="setting-label">特定词重复率</span>
                                <span class="setting-value">{{ formData.presence_penalty.toFixed(1) }}</span>
                            </div>
                            <input
                                type="range"
                                class="setting-range"
                                v-model.number="formData.presence_penalty"
                                min="0"
                                max="1"
                                step="0.1"
                                @change="saveConfig" />
                        </div>

                        <div v-if="formData.model_id != ModelIdEnum.DEEPSEEK" class="setting-compact">
                            <div class="setting-row">
                                <span class="setting-label">候选词概率</span>
                                <span class="setting-value">{{ formData.top_logprobs }}</span>
                            </div>
                            <input
                                type="range"
                                class="setting-range"
                                v-model.number="formData.top_logprobs"
                                min="0"
                                max="20"
                                step="1"
                                @change="saveConfig" />
                        </div>

                        <div v-if="formData.model_id != ModelIdEnum.DEEPSEEK" class="setting-row setting-row-switch">
                            <span class="setting-label">显示候选词</span>
                            <button
                                type="button"
                                class="switch"
                                :class="{ on: formData.logprobs === 1 }"
                                @click="toggleLogprobs">
                                <span class="knob" />
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </ElPopover>
</template>

<script setup lang="ts">
import { ElPopover } from "element-plus";
import { ModelIdEnum } from "@/enums/appEnums";
import { getUserChatConfig, saveUserChatConfig } from "@/api/chat";
import { debounce } from "lodash-es";

const props = withDefaults(
    defineProps<{
        modelId: string | number;
        modelSubId: string | number;
        variant?: "default" | "toolbar";
    }>(),
    {
        modelId: "",
        modelSubId: "",
        variant: "toolbar",
    },
);

const visible = ref(false);
const showAdvanced = ref(false);

const formData = reactive<{
    top_p: number;
    temperature: number;
    presence_penalty: number;
    frequency_penalty: number;
    context_num: number;
    top_logprobs: number;
    logprobs: number;
    model_id: ModelIdEnum;
    model_sub_id: string;
    max_tokens: number;
}>({
    top_p: 0.5,
    temperature: 1,
    presence_penalty: 0.1,
    frequency_penalty: 2,
    context_num: 3,
    top_logprobs: 10,
    logprobs: 0,
    model_id: ModelIdEnum.DEEPSEEK,
    model_sub_id: "",
    max_tokens: 4096,
});

const getMaxTemperature = computed(() => 1);
const getMaxTokens = computed(() => 10000);

const hasAdvancedSettings = computed(() => {
    const showTopP = formData.model_id != ModelIdEnum.CLAUDE_SONNET_4_5;
    const showPenaltyAndLogprobs = formData.model_id != ModelIdEnum.DEEPSEEK;
    return showTopP || showPenaltyAndLogprobs;
});

const getConfig = async () => {
    if (!props.modelId) return;
    try {
        const data = await getUserChatConfig({
            model_id: props.modelId,
            model_sub_id: props.modelSubId,
        });
        // 接口异常或返回非对象时保留本地默认，避免把数组当配置写进表单
        if (!data || typeof data !== "object" || Array.isArray(data)) return;
        Object.keys(data).forEach((key) => {
            data[key] = Number(data[key]);
        });
        setFormData(
            {
                ...data,
                model_id: props.modelId,
                model_sub_id: props.modelSubId,
            },
            formData,
        );
    } catch (e) {
        console.warn("[humanize-pop] 加载模型参数失败:", e);
    }
};

const saveConfig = debounce(async () => {
    await saveUserChatConfig(formData);
    getConfig();
}, 300);

const setContextNum = (value: number) => {
    formData.context_num = value;
    saveConfig();
};

const toggleLogprobs = () => {
    formData.logprobs = formData.logprobs === 1 ? 0 : 1;
    saveConfig();
};

watch(
    () => props.modelId,
    (val) => {
        if (val) getConfig();
    },
    { immediate: true },
);

watch(visible, (open) => {
    if (!open) showAdvanced.value = false;
});

defineExpose({
    formData,
});
</script>

<style scoped lang="scss">
.humanize-trigger {
    @apply inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-[#ebedf0] bg-white text-[#6b7280] transition-colors duration-150 ease-[ease];

    &:hover {
        @apply border-[#93c5fd] text-[#2563eb];
    }
}
</style>

<style lang="scss">
.chat-humanize-popper {
    border-radius: 14px !important;
    border: 1px solid #ebedf0 !important;
    background: #fff !important;
    padding: 12px !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
    max-height: min(420px, 58vh) !important;
    overflow: hidden !important;
    box-sizing: border-box !important;

    &.el-popper,
    .el-popover__content {
        max-height: inherit;
        overflow: hidden;
        box-sizing: border-box;
    }

    .humanize-panel {
        display: flex;
        flex-direction: column;
        max-height: min(396px, calc(58vh - 24px));
        min-height: 0;
    }

    .humanize-panel-header {
        flex-shrink: 0;
        margin-bottom: 10px;
    }

    .humanize-panel-title {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        line-height: 1.3;
    }

    .humanize-panel-sub {
        display: block;
        margin-top: 2px;
        font-size: 11px;
        color: #9ca3af;
        line-height: 1.3;
    }

    .humanize-panel-body {
        flex: 1;
        min-height: 0;
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

    .setting-block {
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f1f4;

        &:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }
    }

    .setting-block-title {
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
    }

    .setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
    }

    .setting-row-switch {
        margin-bottom: 0;
        margin-top: 4px;
    }

    .setting-label {
        font-size: 12px;
        color: #6b7280;
    }

    .setting-value {
        font-size: 12px;
        font-weight: 600;
        color: #2563eb;
        font-variant-numeric: tabular-nums;
    }

    .setting-range {
        width: 100%;
        height: 4px;
        accent-color: #2563eb;
        cursor: pointer;
    }

    .seg-row {
        display: flex;
        gap: 4px;
    }

    .seg-item {
        flex: 1;
        min-width: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        padding: 6px 0;
        font-size: 12px;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s ease;

        &:hover {
            border-color: #93c5fd;
            color: #2563eb;
        }

        &.active {
            border-color: #2563eb;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
        }
    }

    .seg-hint {
        display: flex;
        justify-content: space-between;
        margin-top: 4px;
        font-size: 10px;
        color: #9ca3af;
    }

    .advanced-toggle {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        border: 0;
        background: transparent;
        padding: 0;
        font-size: 12px;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;

        &:hover {
            color: #2563eb;
        }
    }

    .advanced-toggle-icon {
        color: #9ca3af;
        transition: transform 0.15s ease;

        &.is-open {
            transform: rotate(180deg);
        }
    }

    .advanced-panel {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #eef0f3;
    }

    .setting-compact {
        margin-bottom: 10px;

        &:last-child {
            margin-bottom: 0;
        }
    }

    .switch {
        position: relative;
        height: 18px;
        width: 32px;
        flex-shrink: 0;
        cursor: pointer;
        border: 0;
        border-radius: 9999px;
        background: #e5e7eb;
        padding: 0;
        transition: background-color 0.15s ease;

        .knob {
            position: absolute;
            left: 2px;
            top: 2px;
            height: 14px;
            width: 14px;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            transition: left 0.15s ease;
        }

        &.on {
            background: #2563eb;

            .knob {
                left: 16px;
            }
        }
    }
}
</style>
