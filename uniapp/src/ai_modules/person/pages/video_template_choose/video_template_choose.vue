<template>
    <view class="h-screen flex flex-col bg-white">
        <view class="flex-shrink-0 bg-white border-b border-[#F3F4F6]">
            <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                <view class="flex items-center gap-[48rpx] px-[32rpx]">
                    <view
                        v-for="tab in typeTabs"
                        :key="tab.apiType"
                        class="tpl-tab"
                        :class="{ on: activeType === tab.apiType }"
                        @click="handleSwitchType(tab.apiType)">
                        <text>{{ tab.label }}</text>
                        <text
                            v-if="tab.mode === TemplateModeEnum.Custom && tab.count > 0"
                            class="tpl-tab-cnt">
                            {{ tab.count }}
                        </text>
                    </view>
                </view>
            </scroll-view>

            <view class="px-[32rpx] pt-[24rpx] pb-[8rpx]">
                <view class="flex gap-[20rpx]">
                    <view
                        class="tpl-mode"
                        :class="{ on: currentItem.mode === TemplateModeEnum.Auto }"
                        @click="handleSetMode(TemplateModeEnum.Auto)">
                        <view class="tpl-mode-dot"></view>
                        <text>自动随机</text>
                    </view>
                    <view
                        class="tpl-mode"
                        :class="{ on: currentItem.mode === TemplateModeEnum.Custom }"
                        @click="handleSetMode(TemplateModeEnum.Custom)">
                        <view class="tpl-mode-dot"></view>
                        <text>{{ customModeLabel }}</text>
                    </view>
                </view>
                <text class="block text-[22rpx] text-[#9CA3AF] mt-[16rpx] mb-[8rpx]">{{ modeHint }}</text>
            </view>
        </view>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view v-if="currentItem.mode === TemplateModeEnum.Auto" class="tpl-auto-empty">
                    <view class="tpl-auto-ic">
                        <u-icon name="reload" color="#2F73F6" size="40"></u-icon>
                    </view>
                    <text class="block text-[28rpx] font-bold text-[#1F2937]">已开启自动随机</text>
                    <text class="block text-[24rpx] text-[#9CA3AF] mt-[12rpx] leading-relaxed text-center">
                        AI 将在该类型的全部模板中自动随机使用，无需手动选择模板
                    </text>
                </view>

                <view v-else class="px-[32rpx] pt-[24rpx] pb-[40rpx]">
                    <view v-if="loading" class="flex flex-col items-center justify-center py-[120rpx]">
                        <text class="text-[28rpx] text-[#94A3B8]">模板加载中...</text>
                    </view>

                    <view
                        v-else-if="currentTemplateList.length === 0"
                        class="flex flex-col items-center justify-center py-[120rpx]">
                        <text class="text-[28rpx] text-[#94A3B8]">暂无可用模板</text>
                    </view>

                    <view v-else class="grid grid-cols-2 gap-x-[32rpx] gap-y-[40rpx]">
                        <view
                            v-for="template in currentTemplateList"
                            :key="template.templateID"
                            class="tpl-card"
                            :class="{ selected: isSelected(template.templateID) }"
                            @click="toggleSelect(template)">
                            <view class="tpl-cover aspect-[3/4] bg-[#F1F5F9]">
                                <image
                                    :src="getImageUrl(template.pic)"
                                    class="w-full h-full"
                                    mode="aspectFill"
                                    lazy-load />
                                <view class="tpl-check">
                                    <text v-if="isSelected(template.templateID)" class="text-white text-[20rpx]">
                                        ✓
                                    </text>
                                </view>
                                <view class="tpl-zoom" @click.stop="previewVideo(template)">
                                    <u-icon name="search" color="#111111" size="22"></u-icon>
                                </view>
                            </view>
                            <text
                                class="block text-center text-[30rpx] font-medium text-[#111827] mt-[20rpx] line-clamp-1">
                                {{ template.name }}
                            </text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="tpl-foot">
            <view class="tpl-done" @click="confirmSelection">确定</view>
        </view>

        <video-preview v-model="showVideoPreview" :video-url="videoUrl" />
    </view>
</template>

<script setup lang="ts">
import { getShanjianClipTemplateList } from "@/api/digital_human";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import {
    SYNTH_TYPE_SCENE,
    TemplateConfigMap,
    TemplateModeEnum,
    VIDEO_TEMPLATE_CONFIRM_EVENT,
    VIDEO_TEMPLATE_DRAFT_KEY,
} from "../material_library/enums";
import {
    buildTemplateConfigForTypes,
    findEmptyCustomType,
    getSynthApiFullLabel,
    normalizeTemplateItem,
} from "../material_library/utils/template-config";

interface ClipTemplate {
    name: string;
    pic: string;
    link: string;
    templateID: string;
}

const { emit } = useEventBusManager();

const activeType = ref(0);
const typeList = ref<number[]>([]);
const configMap = reactive<TemplateConfigMap>({});
const templateList = ref<ClipTemplate[]>([]);
const loading = ref(false);
const listCache = reactive<Record<number, ClipTemplate[]>>({});

const showVideoPreview = ref(false);
const videoUrl = ref("");

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
    return currentItem.value.mode === TemplateModeEnum.Custom && count
        ? `自定义模板（${count}）`
        : "自定义模板";
});

const modeHint = computed(() => {
    const name = getSynthApiFullLabel(activeType.value);
    if (currentItem.value.mode === TemplateModeEnum.Custom) {
        const count = currentItem.value.selected_count;
        return count
            ? `「${name}」已选 ${count} 个自定义模板`
            : `「${name}」已选 0 个自定义模板，请在下方勾选`;
    }
    return `AI 将在「${name}」的全部模板中自动随机使用`;
});

const ensureTypeItem = (apiType: number): void => {
    const key = String(apiType);
    if (!configMap[key]) {
        configMap[key] = normalizeTemplateItem(apiType);
    }
};

const writeItem = (apiType: number, patch: Partial<(typeof configMap)[string]>): void => {
    const key = String(apiType);
    configMap[key] = normalizeTemplateItem(apiType, { ...configMap[key], ...patch });
};

/** 与 montage_styles_choose 保持一致的字段映射 */
const normalizeTemplate = (item: any): ClipTemplate => ({
    name: item.name,
    pic: item.cover_url,
    link: item.demo_url,
    templateID: String(item.id),
});

const getImageUrl = (pic: string) => pic;
const getVideoUrl = (link: string) => link;

const isSelected = (id: string): boolean => currentItem.value.template_ids.includes(id);

const toggleSelect = (template: ClipTemplate): void => {
    if (currentItem.value.mode !== TemplateModeEnum.Custom) return;
    const id = template.templateID;
    const ids = [...currentItem.value.template_ids];
    const index = ids.indexOf(id);
    if (index > -1) ids.splice(index, 1);
    else ids.push(id);
    writeItem(activeType.value, { template_ids: ids });
};

const previewImage = (pic: string): void => {
    if (!pic) {
        uni.$u.toast("暂无预览");
        return;
    }
    uni.previewImage({
        urls: [getImageUrl(pic)],
    });
};

const previewVideo = (template: ClipTemplate): void => {
    const link = template.link;
    const pic = template.pic;
    if (link) {
        videoUrl.value = getVideoUrl(link);
        showVideoPreview.value = true;
    } else {
        previewImage(pic);
    }
};

const fetchTemplateList = async (apiType: number): Promise<void> => {
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

        // 过滤已失效的历史勾选
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
        uni.$u.toast("风格模板加载失败");
    } finally {
        loading.value = false;
    }
};

const handleSwitchType = async (apiType: number): Promise<void> => {
    if (activeType.value === apiType) return;
    activeType.value = apiType;
    ensureTypeItem(apiType);
    if (normalizeTemplateItem(apiType, configMap[String(apiType)]).mode === TemplateModeEnum.Custom) {
        await fetchTemplateList(apiType);
    } else {
        templateList.value = listCache[apiType] || [];
    }
};

const handleSetMode = async (mode: TemplateModeEnum): Promise<void> => {
    writeItem(activeType.value, {
        mode,
        template_ids: mode === TemplateModeEnum.Auto ? [] : currentItem.value.template_ids,
    });
    if (mode === TemplateModeEnum.Custom) {
        await fetchTemplateList(activeType.value);
    }
};

const confirmSelection = (): void => {
    const payload = buildTemplateConfigForTypes(typeList.value, configMap);
    const emptyType = findEmptyCustomType(typeList.value, payload);
    if (emptyType != null) {
        uni.$u.toast(`「${getSynthApiFullLabel(emptyType)}」请至少勾选 1 个模板`);
        handleSwitchType(emptyType);
        return;
    }

    emit(VIDEO_TEMPLATE_CONFIRM_EVENT, payload);
    uni.navigateBack();
};

onLoad(() => {
    try {
        const draft = uni.getStorageSync(VIDEO_TEMPLATE_DRAFT_KEY) || {};
        const types = Array.isArray(draft.types)
            ? draft.types.map((n: any) => Number(n)).filter((n: number) => SYNTH_TYPE_SCENE[n])
            : [];
        const source = (draft.config || {}) as TemplateConfigMap;

        typeList.value = types.length ? types : Object.keys(SYNTH_TYPE_SCENE).map(Number);
        typeList.value.forEach((apiType) => {
            configMap[String(apiType)] = normalizeTemplateItem(apiType, source[String(apiType)]);
        });
        activeType.value = typeList.value[0];
        ensureTypeItem(activeType.value);

        if (currentItem.value.mode === TemplateModeEnum.Custom) {
            fetchTemplateList(activeType.value);
        }
    } catch {
        typeList.value = Object.keys(SYNTH_TYPE_SCENE).map(Number);
        activeType.value = typeList.value[0];
        ensureTypeItem(activeType.value);
    } finally {
        uni.removeStorageSync(VIDEO_TEMPLATE_DRAFT_KEY);
    }
});
</script>

<style lang="scss" scoped>
.tpl-tab {
    position: relative;
    padding: 20rpx 4rpx 28rpx;
    font-size: 32rpx;
    color: #9ca3af;
    font-weight: 500;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.tpl-tab.on {
    color: #111827;
    font-weight: 700;
}

.tpl-tab.on::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: 8rpx;
    transform: translateX(-50%);
    width: 36rpx;
    height: 6rpx;
    border-radius: 999rpx;
    background: #111827;
}

.tpl-tab-cnt {
    min-width: 32rpx;
    height: 32rpx;
    padding: 0 8rpx;
    border-radius: 999rpx;
    background: #2f73f6;
    color: #ffffff;
    font-size: 20rpx;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.tpl-mode {
    flex: 1;
    min-height: 72rpx;
    border-radius: 28rpx;
    border: 3rpx solid transparent;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
    font-size: 26rpx;
    font-weight: 600;
    color: #6b7280;
}

.tpl-mode-dot {
    width: 32rpx;
    height: 32rpx;
    border-radius: 50%;
    border: 3rpx solid #c9cdd4;
    background: #ffffff;
    box-sizing: border-box;
    position: relative;
    flex-shrink: 0;
}

.tpl-mode.on {
    background: #ebf3ff;
    border-color: #2f73f6;
    color: #2f73f6;
}

.tpl-mode.on .tpl-mode-dot {
    border-color: #2f73f6;
}

.tpl-mode.on .tpl-mode-dot::after {
    content: "";
    position: absolute;
    inset: 6rpx;
    border-radius: 50%;
    background: #2f73f6;
}

.tpl-auto-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 128rpx 80rpx 0;
}

.tpl-auto-ic {
    width: 128rpx;
    height: 128rpx;
    border-radius: 50%;
    background: #f3f6fc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32rpx;
}

.tpl-card.selected .tpl-cover {
    box-shadow: 0 0 0 5rpx #2f73f6;
}

.tpl-cover {
    position: relative;
    width: 100%;
    border-radius: 32rpx;
    overflow: hidden;
}

.tpl-check {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    width: 44rpx;
    height: 44rpx;
    border-radius: 50%;
    border: 3rpx solid rgba(255, 255, 255, 0.85);
    background: rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
}

.tpl-card.selected .tpl-check {
    background: #2f73f6;
    border-color: #2f73f6;
}

.tpl-zoom {
    position: absolute;
    right: 16rpx;
    bottom: 16rpx;
    width: 56rpx;
    height: 56rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.92);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.25);
}

.tpl-foot {
    flex-shrink: 0;
    padding: 24rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
    background: #ffffff;
    box-shadow: 0 -8rpx 40rpx rgba(0, 0, 0, 0.05);
}

.tpl-done {
    width: 100%;
    height: 92rpx;
    border-radius: 28rpx;
    background: #111827;
    color: #ffffff;
    font-size: 32rpx;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tpl-done:active {
    opacity: 0.85;
}
</style>
