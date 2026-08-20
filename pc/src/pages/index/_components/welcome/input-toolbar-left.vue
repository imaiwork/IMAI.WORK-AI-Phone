<template>
    <div class="input-toolbar flex flex-wrap items-center gap-2">
        <!-- 图片模式：模型选择 -->
        <div v-if="activeMode === 'image'" class="relative">
            <div class="model-pill" @click.stop="onToggle('image-model')">
                <span class="model-pill-avatar">
                    <img
                        v-if="currentImageDrawModel?.logo"
                        :src="currentImageDrawModel.logo"
                        class="h-full w-full object-cover"
                        alt="" />
                    <span v-else class="text-[10px] font-semibold text-[#2563eb]">{{
                        (currentImageDrawModel?.name || "M").charAt(0).toUpperCase()
                    }}</span>
                </span>
                <span class="model-pill-name">{{ currentImageDrawModel?.name || "选择模型" }}</span>
                <span class="text-[#c4c8cf]">
                    <Icon name="el-icon-CaretBottom" :size="10" />
                </span>
            </div>
            <div v-if="openPopup === 'image-model'" class="popup model-popup" @click.stop>
                <div class="popup-title">选择模型</div>
                <div
                    v-for="m in imageDrawModels"
                    :key="m.id"
                    class="popup-item"
                    :class="{ active: m.id === selectedImageModelId }"
                    @click="emit('selectImageModel', m)">
                    <span class="model-pill-avatar">
                        <img v-if="m.logo" :src="m.logo" class="h-full w-full object-cover" alt="" />
                        <span v-else class="text-[10px] font-semibold text-[#2563eb]">{{
                            (m.name || "M").charAt(0).toUpperCase()
                        }}</span>
                    </span>
                    <span class="flex-1 min-w-0 truncate">{{ m.name }}</span>
                    <span v-if="m.id === selectedImageModelId" class="text-primary shrink-0">
                        <Icon name="el-icon-Check" :size="14" />
                    </span>
                </div>
                <div v-if="!imageDrawModels.length" class="px-2.5 py-2 text-[13px] text-[#9ca3af]">
                    暂无可用生图模型
                </div>
            </div>
        </div>

        <!-- PPT：仅 image-2 -->
        <div v-if="activeMode === 'ppt'" class="relative">
            <div class="model-pill" @click.stop="onToggle('ppt-model')">
                <span class="model-pill-avatar">
                    <img
                        v-if="currentPptDrawModel?.logo"
                        :src="currentPptDrawModel.logo"
                        class="h-full w-full object-cover"
                        alt="" />
                    <span v-else class="text-[10px] font-semibold text-[#2563eb]">{{
                        (currentPptDrawModel?.name || "M").charAt(0).toUpperCase()
                    }}</span>
                </span>
                <span class="model-pill-name">{{ currentPptDrawModel?.name || "image-2" }}</span>
                <span class="text-[#c4c8cf]">
                    <Icon name="el-icon-CaretBottom" :size="10" />
                </span>
            </div>
            <div v-if="openPopup === 'ppt-model'" class="popup model-popup" @click.stop>
                <div class="popup-title">选择模型</div>
                <div
                    v-for="m in pptDrawModels"
                    :key="m.id"
                    class="popup-item"
                    :class="{ active: m.id === selectedPptModelId }"
                    @click="emit('selectPptModel', m)">
                    <span class="model-pill-avatar">
                        <img v-if="m.logo" :src="m.logo" class="h-full w-full object-cover" alt="" />
                        <span v-else class="text-[10px] font-semibold text-[#2563eb]">{{
                            (m.name || "M").charAt(0).toUpperCase()
                        }}</span>
                    </span>
                    <span class="flex-1 min-w-0 truncate">{{ m.name }}</span>
                    <span v-if="m.id === selectedPptModelId" class="text-primary shrink-0">
                        <Icon name="el-icon-Check" :size="14" />
                    </span>
                </div>
                <div v-if="!pptDrawModels.length" class="px-2.5 py-2 text-[13px] text-[#9ca3af]">
                    暂无可用 PPT 模型（需启用 image-2）
                </div>
            </div>
        </div>

        <!-- 图片模式：规格弹窗 -->
        <div v-if="activeMode === 'image'" class="relative">
            <div class="toolbar-btn" @click.stop="onToggle('spec')">
                <span class="ratio-ic" />
                {{ imageState.ratio === "smart" ? "智能" : imageState.ratio }}
                <span class="divider">|</span>
                {{ imageState.resolution }}
            </div>
            <div v-if="openPopup === 'spec'" class="popup spec-popup" @click.stop>
                <h4>选择比例</h4>
                <div class="ratio-grid">
                    <div
                        v-for="r in RATIO_OPTIONS"
                        :key="r.key"
                        class="ratio-item"
                        :class="{ active: imageState.ratio === r.key }"
                        @click="emit('setRatio', r.key)">
                        <div class="shape" :style="{ width: r.w + 'px', height: r.h + 'px' }" />
                        <span>{{ r.label }}</span>
                    </div>
                </div>
                <h4>选择分辨率</h4>
                <div class="res-group">
                    <div
                        v-for="opt in ['高清 2K', '超清 4K']"
                        :key="opt"
                        class="res-opt"
                        :class="{ active: imageState.resolution === opt }"
                        @click="imageState.resolution = opt">
                        {{ opt }}
                    </div>
                </div>
                <h4>尺寸</h4>
                <div class="size-row">
                    <div class="size-cell">
                        <span class="lbl">W</span
                        ><input
                            type="number"
                            step="16"
                            v-model.number="imageState.width"
                            @blur="normalizeImageDim('width')"
                            @keydown.enter.prevent="normalizeImageDim('width')" />
                    </div>
                    <div class="size-link linked">🔗</div>
                    <div class="size-cell">
                        <span class="lbl">H</span
                        ><input
                            type="number"
                            step="16"
                            v-model.number="imageState.height"
                            @blur="normalizeImageDim('height')"
                            @keydown.enter.prevent="normalizeImageDim('height')" /><span class="size-unit">PX</span>
                    </div>
                </div>
                <div class="count-row">
                    <span class="lbl">生成张数</span>
                    <div class="count-stepper">
                        <button :disabled="imageState.count <= 1" @click="emit('changeCount', -1)">−</button>
                        <span class="val">{{ imageState.count }}</span>
                        <button :disabled="imageState.count >= imageMaxCount" @click="emit('changeCount', 1)">
                            ＋
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PPT 模式：页数 + 场景 -->
        <template v-if="activeMode === 'ppt'">
            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('pages')">
                    <Icon name="el-icon-Document" :size="14" />{{ pptState.pages
                    }}<span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'pages'" class="popup mini-popup" @click.stop>
                    <div
                        v-for="p in PPT_PAGES"
                        :key="p"
                        class="mp-item"
                        :class="{ active: p === pptState.pages }"
                        @click="
                            pptState.pages = p;
                            closePopup();
                        ">
                        {{ p }}
                    </div>
                    <div class="mp-divider" />
                    <div class="mp-custom">
                        <span class="mp-custom-label">自定义</span>
                        <input
                            :value="customPagesInput"
                            type="number"
                            @input="emit('update:customPagesInput', ($event.target as HTMLInputElement).value)"
                            min="1"
                            max="99"
                            placeholder="1-99"
                            class="mp-custom-input"
                            @keydown.enter.prevent="applyCustomPages"
                            @click.stop />
                        <span class="mp-custom-suffix">页</span>
                        <button class="mp-custom-btn" :disabled="!isCustomPagesValid" @click="emit('applyCustomPages')">
                            确定
                        </button>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('scene')">
                    <Icon name="el-icon-Grid" :size="14" />{{ pptState.scene
                    }}<span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'scene'" class="popup mini-popup scene-popup" @click.stop>
                    <div
                        v-for="s in PPT_SCENES"
                        :key="s"
                        class="mp-item"
                        :class="{ active: s === pptState.scene }"
                        @click="
                            pptState.scene = s;
                            closePopup();
                        ">
                        {{ s }}
                    </div>
                </div>
            </div>
        </template>

        <!-- 视频模式:模型 + 规格 + 数量 -->
        <template v-if="activeMode === 'video'">
            <div class="relative">
                <div class="model-pill" @click.stop="onToggle('video-model')">
                    <span class="model-pill-avatar">
                        <img
                            v-if="currentVideoDrawModel?.logo"
                            :src="currentVideoDrawModel.logo"
                            class="h-full w-full object-cover"
                            alt="" />
                        <span v-else class="text-[10px] font-semibold text-[#2563eb]">{{
                            (currentVideoDrawModel?.name || "V").charAt(0).toUpperCase()
                        }}</span>
                    </span>
                    <span class="model-pill-name">{{ currentVideoDrawModel?.name || "选择模型" }}</span>
                    <span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'video-model'" class="popup model-popup" @click.stop>
                    <div class="popup-title">选择模型</div>
                    <div
                        v-for="m in videoDrawModels"
                        :key="m.id"
                        class="popup-item"
                        :class="{ active: m.id === selectedVideoModelId }"
                        @click="emit('selectVideoModel', m)">
                        <span class="model-pill-avatar">
                            <img v-if="m.logo" :src="m.logo" class="h-full w-full object-cover" alt="" />
                            <span v-else class="text-[10px] font-semibold text-[#2563eb]">{{
                                (m.name || "V").charAt(0).toUpperCase()
                            }}</span>
                        </span>
                        <span class="flex-1 min-w-0 truncate">{{ m.name }}</span>
                        <span v-if="m.id === selectedVideoModelId" class="text-primary shrink-0">
                            <Icon name="el-icon-Check" :size="14" />
                        </span>
                    </div>
                    <div v-if="!videoDrawModels.length" class="px-2.5 py-2 text-[13px] text-[#9ca3af]">
                        暂无可用生视频模型
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('video-spec')">
                    <span class="ratio-ic" />
                    {{ videoState.ratio }}
                    <span class="divider">|</span>
                    {{ videoState.resolution }}
                </div>
                <div v-if="openPopup === 'video-spec'" class="popup spec-popup" @click.stop>
                    <h4>选择比例</h4>
                    <div class="ratio-grid">
                        <div
                            v-for="r in VIDEO_RATIO_OPTIONS"
                            :key="r.key"
                            class="ratio-item"
                            :class="{ active: videoState.ratio === r.key }"
                            @click="emit('setVideoRatio', r.key)">
                            <div class="shape" :style="{ width: r.w + 'px', height: r.h + 'px' }" />
                            <span>{{ r.label }}</span>
                        </div>
                    </div>
                    <h4>分辨率</h4>
                    <div class="res-group">
                        <div class="res-opt active">
                            {{ videoState.resolution }}
                        </div>
                    </div>
                    <div class="count-row">
                        <span class="lbl">生成数量</span>
                        <div class="count-stepper">
                            <button disabled @click="emit('changeVideoCount', -1)">−</button>
                            <span class="val">{{ videoState.count }}</span>
                            <button disabled @click="emit('changeVideoCount', 1)">＋</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- 数字人模式：模式切换 + 形象/人设/风格/音色/设置 -->
        <template v-if="activeMode === 'digital'">
            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('digital-mode')">
                    <Icon name="el-icon-VideoPlay" :size="14" />
                    {{ DIGITAL_MODES.find((m) => m.key === digitalState.mode)?.label }}
                    <span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'digital-mode'" class="popup mini-popup" @click.stop>
                    <div
                        v-for="m in DIGITAL_MODES"
                        :key="m.key"
                        class="mp-item"
                        :class="{ active: m.key === digitalState.mode }"
                        @click="
                            digitalState.mode = m.key;
                            closePopup();
                        ">
                        {{ m.label }}
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('digital-avatar')">
                    <img
                        v-if="currentDigitalAvatar?.cover"
                        :src="currentDigitalAvatar.cover"
                        :alt="currentDigitalAvatar.name"
                        class="toolbar-thumb" />
                    <Icon v-else name="el-icon-User" :size="14" />
                    {{ currentDigitalAvatar?.name ?? "选择形象" }}
                    <span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'digital-avatar'" class="popup avatar-popup" @click.stop>
                    <h4>选择形象</h4>
                    <div class="avatar-grid">
                        <div
                            v-for="a in DIGITAL_AVATARS"
                            :key="a.id"
                            class="avatar-item"
                            :class="{ active: a.id === digitalState.avatarId }"
                            @click="
                                digitalState.avatarId = a.id;
                                closePopup();
                            ">
                            <div class="av-cover" :style="{ background: a.cover ? '#000' : a.bg }">
                                <img v-if="a.cover" :src="a.cover" :alt="a.name" />
                                <span v-else>{{ a.emoji }}</span>
                            </div>
                            <span>{{ a.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('digital-persona')">
                    <Icon name="el-icon-Avatar" :size="14" />
                    {{ currentDigitalPersona?.name ?? "选择人设" }}
                    <span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'digital-persona'" class="popup persona-popup" @click.stop>
                    <h4>选择人设</h4>
                    <div class="persona-list">
                        <div
                            class="persona-item"
                            :class="{ active: !digitalState.personaId }"
                            @click="
                                digitalState.personaId = '';
                                closePopup();
                            ">
                            <div class="p-avatar none-avatar">✕</div>
                            <span class="p-name">不选择人设</span>
                        </div>
                        <div
                            v-for="p in digitalPersonas"
                            :key="p.id"
                            class="persona-item"
                            :class="{ active: p.id === digitalState.personaId }"
                            @click="
                                digitalState.personaId = p.id;
                                closePopup();
                            ">
                            <div class="p-avatar" :style="{ background: p.bg }">
                                {{ p.emoji }}
                            </div>
                            <span class="p-name">{{ p.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="toolbar-btn" @click.stop="onToggle('digital-style')">
                    <Icon name="el-icon-MagicStick" :size="14" />
                    {{ currentDigitalStyle?.name ?? "选择风格" }}
                    <span class="text-[#c4c8cf]">
                        <Icon name="el-icon-CaretBottom" :size="10" />
                    </span>
                </div>
                <div v-if="openPopup === 'digital-style'" class="popup style-popup" @click.stop>
                    <h4>视频风格</h4>
                    <div class="style-list">
                        <div
                            v-for="s in DIGITAL_STYLES"
                            :key="s.id"
                            class="style-item"
                            :class="{ active: s.id === digitalState.styleId }">
                            <div class="style-main" @click="digitalState.styleId = s.id">
                                <div class="style-cover" :style="{ background: s.bg }">
                                    {{ s.emoji }}
                                </div>
                                <span>{{ s.name }}</span>
                            </div>
                            <button
                                v-if="s.id !== 'random'"
                                class="style-preview"
                                @click.stop="emit('previewStyle', s)">
                                <Icon name="el-icon-View" :size="12" /> 预览
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 设置浮窗:所有数字人模式都有 -->
            <div class="relative">
                <div class="toolbar-icon-btn" @click.stop="onToggle('digital-settings')" title="设置">
                    <Icon name="el-icon-Setting" :size="14" />
                </div>
                <div
                    v-if="openPopup === 'digital-settings'"
                    class="popup settings-popup digital-settings-popup"
                    @click.stop>
                    <div class="popup-title">
                        {{ DIGITAL_MODES.find((m) => m.key === digitalState.mode)?.label }}设置
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">生成数量</label>
                        <div class="count-stepper">
                            <button @click="emit('changeDigitalCount', -1)">−</button>
                            <span class="val">{{ digitalState.settings.count }}</span>
                            <button @click="emit('changeDigitalCount', 1)">＋</button>
                        </div>
                    </div>
                    <div v-if="showMaterialSettings" class="setting-group">
                        <label class="setting-label">素材原声</label>
                        <span
                            class="switch"
                            :class="{
                                on: digitalState.settings.useOriginalAudio,
                            }"
                            @click="digitalState.settings.useOriginalAudio = !digitalState.settings.useOriginalAudio">
                            <span class="knob" />
                        </span>
                    </div>
                    <div v-if="showMaterialSettings" class="setting-group">
                        <label class="setting-label">素材应用方式</label>
                        <div class="seg-row wrap">
                            <div
                                v-for="opt in availableMaterialModes"
                                :key="opt.value"
                                class="seg-item"
                                :class="{
                                    active: digitalState.settings.materialMode === opt.value,
                                }"
                                @click="digitalState.settings.materialMode = opt.value">
                                {{ opt.label }}
                            </div>
                        </div>
                    </div>
                    <div v-if="showSettingVoice" class="setting-group">
                        <label class="setting-label">音色选择</label>
                        <div class="seg-row wrap">
                            <div
                                v-for="v in DIGITAL_SETTING_VOICES"
                                :key="v.id"
                                class="seg-item"
                                :class="{
                                    active: digitalState.settings.voice === v.id,
                                }"
                                @click="digitalState.settings.voice = v.id">
                                {{ v.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- 聊天模式：联网搜索 -->
        <div
            v-if="activeMode === 'chat'"
            class="toolbar-btn"
            :class="{ 'is-on': webSearch }"
            @click="emit('update:webSearch', !webSearch)">
            <span>联网搜索</span>
            <span class="switch" :class="{ on: webSearch }"><span class="knob" /></span>
        </div>

        <!-- 图片 / 视频：点击优化当前输入框文案 -->
        <div
            v-if="activeMode === 'image' || activeMode === 'video'"
            class="toolbar-btn"
            :class="{ disabled: promptOptimizing }"
            @click="emit('optimizePrompt')">
            <span v-if="promptOptimizing" class="animate-spin">
                <Icon name="el-icon-Loading" :size="14" />
            </span>
            <span>{{ promptOptimizing ? "优化中…" : "提示词优化" }}</span>
        </div>

        <!-- 数字人：AI 追问模式 -->
        <div
            v-if="activeMode === 'digital'"
            class="toolbar-btn"
            :class="{ 'is-on': digitalFollowupOn }"
            @click="emit('update:digitalFollowupOn', !digitalFollowupOn)">
            <span class="switch" :class="{ on: digitalFollowupOn }"><span class="knob" /></span>
            <span>AI 追问模式</span>
        </div>

        <!-- PPT 模式：智能追问 -->
        <div
            v-if="activeMode === 'ppt'"
            class="toolbar-btn"
            :class="{ 'is-on': followupOn }"
            @click="emit('update:followupOn', !followupOn)">
            <span class="switch" :class="{ on: followupOn }"><span class="knob" /></span>
            <span>智能追问</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    RATIO_OPTIONS,
    PPT_PAGES,
    PPT_SCENES,
    VIDEO_RATIO_OPTIONS,
    DIGITAL_MODES,
    DIGITAL_AVATARS,
    DIGITAL_STYLES,
    DIGITAL_SETTING_VOICES,
    snapDimToMultipleOf16,
} from "../../_enums/welcome-toolbar";
import feedback from "@/utils/feedback";

const props = defineProps<{
    activeMode: string;
    openPopup: string;
    imageState: {
        ratio: string;
        resolution: string;
        width: number;
        height: number;
        count: number;
    };
    pptState: { pages: string; scene: string };
    videoState: { ratio: string; resolution: string; count: number };
    digitalState: {
        mode: string;
        avatarId: string;
        personaId: string;
        styleId: string;
        settings: {
            count: number;
            useOriginalAudio: boolean;
            materialMode: string;
            voice: string;
        };
    };
    currentImageDrawModel?: { logo?: string; name?: string } | null;
    currentPptDrawModel?: { logo?: string; name?: string } | null;
    currentVideoDrawModel?: { logo?: string; name?: string } | null;
    imageDrawModels: Array<{ id: number | string; logo?: string; name?: string }>;
    pptDrawModels: Array<{ id: number | string; logo?: string; name?: string }>;
    videoDrawModels: Array<{ id: number | string; logo?: string; name?: string }>;
    selectedImageModelId?: number | string | null;
    selectedPptModelId?: number | string | null;
    selectedVideoModelId?: number | string | null;
    currentDigitalAvatar?: { cover?: string; name?: string } | null;
    currentDigitalPersona?: { name?: string } | null;
    currentDigitalStyle?: { name?: string } | null;
    digitalPersonas: Array<{ id: string; name: string; emoji: string; bg: string }>;
    customPagesInput: number | string;
    isCustomPagesValid: boolean;
    availableMaterialModes: Array<{ value: string; label: string }>;
    showMaterialSettings: boolean;
    showSettingVoice: boolean;
    imageMaxCount: number;
    webSearch: boolean;
    promptOptimizing: boolean;
    digitalFollowupOn: boolean;
    followupOn: boolean;
}>();

const emit = defineEmits<{
    toggle: [name: string];
    "update:openPopup": [value: string];
    selectImageModel: [model: { id: number | string; logo?: string; name?: string }];
    selectPptModel: [model: { id: number | string; logo?: string; name?: string }];
    selectVideoModel: [model: { id: number | string; logo?: string; name?: string }];
    setRatio: [key: string];
    setVideoRatio: [key: string];
    changeCount: [delta: number];
    changeVideoCount: [delta: number];
    changeDigitalCount: [delta: number];
    applyCustomPages: [];
    previewStyle: [style: { id: string; name: string }];
    optimizePrompt: [];
    "update:webSearch": [value: boolean];
    "update:digitalFollowupOn": [value: boolean];
    "update:followupOn": [value: boolean];
    "update:customPagesInput": [value: number | string];
}>();

function onToggle(name: string) {
    emit("toggle", name);
}

function closePopup() {
    emit("update:openPopup", "");
}

function normalizeImageDim(key: "width" | "height") {
    const raw = Number(props.imageState[key]);
    const snapped = snapDimToMultipleOf16(raw);
    if (snapped !== raw) {
        props.imageState[key] = snapped;
        feedback.msgWarning("宽高需为 16 的倍数，已自动调整");
    }
}
</script>

<style lang="scss" scoped>
.input-toolbar {
    .model-pill {
        @apply inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-[18px] border border-[#ebedf0] bg-white py-[4px] pl-1 pr-3 text-[13px] text-[#1f2937];

        &:hover {
            @apply border-[#2563eb];
        }
        .model-pill-name {
            @apply max-w-[160px] truncate font-medium;
        }
    }
    .model-pill-avatar {
        @apply inline-flex h-[22px] w-[22px] flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#f3f5f9];

        :deep(svg) {
            @apply h-4 w-4;
        }
    }

    .toolbar-btn {
        @apply inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-[18px] border border-[#ebedf0] bg-white px-3 py-1.5 text-[13px] text-[#4b5563];

        &:hover {
            @apply border-[#93c5fd] text-[#2563eb];
        }
        &.dashed {
            @apply border-dashed;
        }
        &.is-on {
            @apply border-[#93c5fd] bg-[#f5f8ff] text-[#2563eb];
        }
        &.disabled {
            @apply cursor-not-allowed opacity-60;
        }
        .ratio-ic {
            @apply inline-block h-4 w-3.5 rounded-[3px] border-[1.5px] border-current;
        }
        .toolbar-thumb {
            @apply -ml-0.5 h-[22px] w-[22px] rounded-full object-cover shadow-[0_0_0_1.5px_#fff,0_0_0_2px_#e5e7eb];
        }
        .divider {
            @apply mx-0.5 text-[#d1d5db];
        }
        .switch {
            @apply relative inline-block h-3.5 w-[26px] rounded-lg bg-[#d1d5db] transition-colors duration-150 ease-[ease];

            &.on {
                @apply bg-[#2563eb];
            }
            .knob {
                @apply absolute left-px top-px h-3 w-3 rounded-full bg-white shadow-[0_1px_2px_rgba(0,0,0,0.15)] transition-transform duration-150 ease-[ease];
            }
            &.on .knob {
                @apply translate-x-3;
            }
        }
    }
}

.input-toolbar .toolbar-icon-btn {
    @apply inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-[#ebedf0] bg-white text-[#6b7280];

    &:hover {
        @apply border-[#93c5fd] text-[#2563eb];
    }
}

.popup {
    @apply absolute bottom-[calc(100%+8px)] left-0 z-[100] min-w-[220px] rounded-[14px] border border-[#ebedf0] bg-white p-3 shadow-[0_8px_32px_rgba(0,0,0,0.12)];
}
.popup-title {
    @apply mb-2 text-[13px] font-semibold text-[#1f2937];
}
.model-popup .popup-item {
    @apply flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] text-[#4b5563];

    &:hover {
        @apply bg-[#f3f5f9];
    }
    &.active {
        @apply bg-[#eff6ff] text-[#2563eb];
    }
}

.mini-popup {
    @apply min-w-[130px] p-1.5;

    &.scene-popup {
        @apply max-h-[280px] min-w-[180px] overflow-y-auto;
    }
    .mp-item {
        @apply cursor-pointer rounded-md px-2.5 py-1.5 text-[13px] text-[#4b5563];

        &:hover {
            @apply bg-[#f3f5f9] text-[#2563eb];
        }
        &.active {
            @apply bg-[#eff6ff] font-semibold text-[#2563eb];
        }
    }
    .mp-divider {
        @apply mx-1 my-1.5 h-px bg-[#f0f1f4];
    }
    .mp-custom {
        @apply flex items-center gap-1.5 px-1.5 py-1;

        .mp-custom-label {
            @apply flex-shrink-0 text-xs text-[#6b7280];
        }
        .mp-custom-input {
            @apply w-14 rounded-md border border-[#ebedf0] bg-white px-2 py-[5px] text-center font-[inherit] text-[13px] text-[#1f2937] outline-none;
            -moz-appearance: textfield;

            &::-webkit-outer-spin-button,
            &::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            &:focus {
                @apply border-[#93c5fd];
            }
        }
        .mp-custom-suffix {
            @apply text-xs text-[#6b7280];
        }
        .mp-custom-btn {
            @apply ml-auto cursor-pointer rounded-md border-0 bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-2.5 py-[5px] text-xs font-medium text-white transition-opacity duration-150 ease-[ease];

            &:hover:not(:disabled) {
                @apply opacity-90;
            }
            &:disabled {
                @apply cursor-not-allowed bg-[#d1d5db];
            }
        }
    }
}

.spec-popup {
    @apply w-[480px] p-[18px];

    h4 {
        @apply mb-3 text-[13px] font-medium text-[#6b7280];
    }
    h4:not(:first-child) {
        @apply mt-[18px];
    }
    .ratio-grid {
        @apply grid grid-cols-9 gap-1;
    }
    .ratio-item {
        @apply flex cursor-pointer flex-col items-center gap-1 rounded-lg px-1 py-2 text-[11px] text-[#6b7280];

        &:hover {
            @apply bg-[#f3f5f9];
        }
        &.active {
            @apply bg-[#f3f5f9] font-semibold text-[#2563eb] shadow-[inset_0_0_0_1px_#c7dafd];
        }
        .shape {
            @apply rounded-[3px] border-[1.5px] border-current;
        }
    }
    .res-group {
        @apply flex rounded-xl bg-[#f5f6f8] p-1;
    }
    .res-opt {
        @apply flex-1 cursor-pointer rounded-[10px] p-2 text-center text-[13px] text-[#6b7280];

        &.active {
            @apply bg-white font-semibold text-[#1f2937] shadow-[0_1px_3px_rgba(0,0,0,0.08)];
        }
    }
    .size-row {
        @apply flex items-center gap-1.5 rounded-xl bg-[#f5f6f8] p-1;
    }
    .size-cell {
        @apply flex flex-1 items-center gap-2 rounded-[10px] bg-white px-3 py-1.5;

        .lbl {
            @apply text-xs text-[#9ca3af];
        }
        input {
            @apply w-[60px] flex-1 border-0 bg-transparent text-right text-[13px] font-medium text-[#1f2937] outline-none;
        }
    }
    .size-link {
        @apply flex h-7 w-7 cursor-pointer items-center justify-center text-[#2563eb];
    }
    .size-unit {
        @apply text-xs text-[#9ca3af];
    }
    .count-row {
        @apply mt-[18px] flex items-center gap-2.5;

        .lbl {
            @apply text-[13px] text-[#6b7280];
        }
    }
    .count-stepper {
        @apply flex items-center overflow-hidden rounded-[10px] bg-[#f5f6f8];

        button {
            @apply h-[30px] w-[30px] cursor-pointer border-0 bg-transparent text-sm text-[#6b7280];

            &:hover:not(:disabled) {
                @apply bg-[#e5e7eb] text-[#2563eb];
            }
            &:disabled {
                @apply cursor-not-allowed opacity-40;
            }
        }
        .val {
            @apply min-w-8 text-center text-[13px] font-semibold text-[#1f2937];
        }
    }
}

.settings-popup {
    @apply w-80;

    .setting-group {
        @apply mb-3 flex flex-col gap-1.5;

        &:last-child {
            @apply mb-0;
        }
    }
    .setting-label {
        @apply text-xs text-[#6b7280];
    }
    .seg-row {
        @apply flex gap-1;

        &.wrap {
            @apply flex-wrap;
        }
    }
    .seg-item {
        @apply min-w-0 flex-1 cursor-pointer whitespace-nowrap rounded-md border border-[#e5e7eb] bg-white px-2 py-1.5 text-center text-xs text-[#6b7280];

        &.active {
            @apply border-[#2563eb] bg-[#eff6ff] font-semibold text-[#2563eb];
        }
    }
    .seg-row.wrap .seg-item {
        @apply flex-[0_0_calc(50%-2px)];
    }
    .seg-hint {
        @apply mt-0.5 flex justify-between text-[10px] text-[#9ca3af];
    }
    .setting-range {
        @apply w-full accent-[#2563eb];
    }
    .setting-textarea {
        @apply min-h-[54px] w-full resize-y rounded-md border border-[#e5e7eb] bg-[#fafbfc] p-2 font-[inherit] text-xs outline-none;

        &:focus {
            @apply border-[#93c5fd];
        }
    }
    .count-stepper {
        @apply inline-flex items-center gap-1;

        button {
            @apply h-[26px] w-[26px] cursor-pointer rounded-md border border-[#e5e7eb] bg-white text-[#4b5563];

            &:hover {
                @apply border-[#2563eb] text-[#2563eb];
            }
        }
        .val {
            @apply min-w-[22px] text-center font-semibold text-[#1f2937];
        }
    }
    .switch {
        @apply relative h-[18px] w-8 cursor-pointer rounded-full bg-[#e5e7eb] transition-colors duration-150 ease-[ease];

        .knob {
            @apply absolute left-0.5 top-0.5 h-3.5 w-3.5 rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,0.15)] transition-[left] duration-150 ease-[ease];
        }
        &.on {
            @apply bg-[#2563eb];

            .knob {
                @apply left-4;
            }
        }
    }
}

// 数字人:形象 grid
.avatar-popup {
    @apply w-80;

    h4 {
        @apply mb-2 text-xs font-semibold text-[#6b7280];
    }
    .avatar-grid {
        @apply grid grid-cols-3 gap-2;
    }
    .avatar-item {
        @apply flex cursor-pointer flex-col items-center gap-1 rounded-lg p-1.5 transition-colors duration-150 ease-[ease];

        &:hover {
            @apply bg-[#f3f5f9];
        }
        &.active {
            @apply bg-[#eff6ff] outline outline-2 outline-[#2563eb];
        }
    }
    .av-cover {
        @apply flex h-12 w-12 items-center justify-center overflow-hidden rounded-full text-2xl;

        img {
            @apply block h-full w-full object-cover;
        }
    }
    .avatar-item span {
        @apply text-[11px] text-[#4b5563];
    }
}
// 数字人:人设 list
.persona-popup {
    @apply w-[220px] p-1.5;

    h4 {
        @apply mb-2 ml-1.5 mr-1.5 mt-0.5 text-xs font-semibold text-[#6b7280];
    }
    .persona-list {
        @apply flex flex-col gap-0.5;
    }
    .persona-item {
        @apply flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5;

        &:hover {
            @apply bg-[#f3f5f9];
        }
        &.active {
            @apply bg-[#eff6ff];

            .p-name {
                @apply font-semibold text-[#2563eb];
            }
        }
    }
    .p-avatar {
        @apply flex h-[26px] w-[26px] items-center justify-center rounded-full text-sm;

        &.none-avatar {
            @apply border border-dashed border-[#d1d5db] bg-[#f3f4f6] text-xs text-[#9ca3af];
        }
    }
    .p-name {
        @apply text-[13px] text-[#4b5563];
    }
}
// 数字人:风格 list + 每项右侧预览按钮
.style-popup {
    @apply w-[280px];

    h4 {
        @apply mb-2 text-xs font-semibold text-[#6b7280];
    }
    .style-list {
        @apply flex flex-col gap-1;
    }
    .style-item {
        @apply flex cursor-pointer items-center justify-between rounded-lg p-1;

        &:hover {
            @apply bg-[#f3f5f9];
        }
        &.active {
            @apply bg-[#eff6ff];

            .style-main span {
                @apply font-semibold text-[#2563eb];
            }
        }
    }
    .style-main {
        @apply flex flex-1 items-center gap-2;
    }
    .style-cover {
        @apply flex h-8 w-8 items-center justify-center rounded-md text-base;
    }
    .style-main span {
        @apply text-[13px] text-[#4b5563];
    }
    .style-preview {
        @apply inline-flex cursor-pointer items-center gap-1 rounded-md border border-[#e5e7eb] bg-white px-2 py-1 text-[11px] text-[#6b7280];

        &:hover {
            @apply border-[#2563eb] text-[#2563eb];
        }
    }
}
</style>
