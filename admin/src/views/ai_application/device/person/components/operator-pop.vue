<template>
    <popup
        ref="popupRef"
        title="内容发布 · 自动发布配置"
        :async="true"
        width="720px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2 flex flex-col gap-4" v-loading="loading">
            <!-- 发布平台（不同平台独立配置） -->
            <div class="rounded-2xl p-4 shadow-sm" style="background: #ffffff; border: 1px solid #f3f4f6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm font-extrabold text-[#1a1a1a]">发布平台</span>
                    <span class="text-xs text-[#9ca3af]">（不同平台独立配置）</span>
                </div>
                <el-radio-group v-model="activePlatform" size="default">
                    <el-radio-button v-for="item in PLATFORM_LIST" :key="item.platform" :label="item.platform">
                        {{ item.label }}
                    </el-radio-button>
                </el-radio-group>
            </div>

            <!-- 发布内容类型 + 文案生成方式 -->
            <div class="rounded-2xl p-4 shadow-sm flex flex-col gap-3" style="background: #ffffff; border: 1px solid #f3f4f6">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-extrabold text-[#1a1a1a]">发布内容类型</span>
                    <el-radio-group v-model="current.publish_media_type" size="default">
                        <el-radio-button :label="MediaType.VIDEO">视频</el-radio-button>
                        <el-radio-button v-if="showImageMediaType" :label="MediaType.IMAGE">图文</el-radio-button>
                    </el-radio-group>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm font-extrabold text-[#1a1a1a]">发布文案生成方式</span>
                    <el-radio-group v-model="current.publish_copywriting_source" size="default">
                        <el-radio-button :label="CopySource.AUTO">自动生成</el-radio-button>
                        <el-radio-button :label="CopySource.LIBRARY">素材库引用</el-radio-button>
                    </el-radio-group>
                </div>

                <!-- 自动生成 -->
                <template v-if="current.publish_copywriting_source === CopySource.AUTO">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-[#6b7280]">生成依据</span>
                        <el-radio-group v-model="current.generate_basis" size="default">
                            <el-radio-button :label="Basis.PERSONA">根据人设</el-radio-button>
                            <el-radio-button :label="Basis.CUSTOM">自定义方向</el-radio-button>
                        </el-radio-group>
                    </div>
                    <el-input
                        v-if="current.generate_basis === Basis.CUSTOM"
                        v-model="current.custom_direction"
                        placeholder="例：本期主题是换季护肤，重点突出保湿和修护…" />
                </template>

                <!-- 素材库引用 -->
                <template v-else>
                    <div
                        class="text-xs leading-relaxed text-[#6b7280] rounded-lg px-3 py-2.5"
                        style="background: #f7f9fc; border: 1px solid #eef2f8">
                        直接从该人设「素材库 · 文案库 · 发布文案」中取用，已审过的内容直接发，无需 AI 生成。
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-[#6b7280]">使用方式</span>
                        <el-radio-group v-model="current.library_use_mode" size="default">
                            <el-radio-button :label="LibUse.RANDOM">随机使用</el-radio-button>
                            <el-radio-button :label="LibUse.SEQUENCE">顺序使用</el-radio-button>
                        </el-radio-group>
                    </div>
                    <template v-if="current.library_use_mode === LibUse.RANDOM">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-[#6b7280]">随机规则</span>
                            <el-radio-group v-model="current.library_reuse_mode" size="default">
                                <el-radio-button :label="LibReuse.ONCE">每条只用一次</el-radio-button>
                                <el-radio-button :label="LibReuse.REPEAT">可重复使用</el-radio-button>
                            </el-radio-group>
                        </div>
                        <span class="text-xs text-[#9ca3af] leading-relaxed">{{ libraryReuseNote }}</span>
                    </template>
                </template>
            </div>

            <!-- 发布内容定位 -->
            <div class="rounded-2xl p-4 shadow-sm" style="background: #ffffff; border: 1px solid #f3f4f6">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm font-extrabold text-[#1a1a1a]">发布内容定位</span>
                        <span class="text-xs mt-0.5 text-[#9ca3af]">开启后每条内容自动附带地理位置</span>
                    </div>
                    <el-switch v-model="contentLocationEnabled" />
                </div>
                <el-input
                    v-if="current.is_content_location === 1"
                    v-model="current.content_location"
                    class="mt-3"
                    placeholder="输入定位地址，如：上海市 · 黄浦区" />
            </div>

            <!-- 抖音专属：挂载购物车 + 商家定位 -->
            <template v-if="isDouyinPlatform">
                <div class="rounded-2xl p-4 shadow-sm" style="background: #ffffff; border: 1px solid #f3f4f6">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-extrabold text-[#1a1a1a]">挂载购物车</span>
                                <span class="text-[10px] font-semibold text-primary bg-[#eff5ff] rounded px-1.5 py-0.5">
                                    抖音专属
                                </span>
                            </div>
                            <span class="text-xs mt-0.5 text-[#9ca3af]">默认挂载前 6 个，若无商品则不生效</span>
                        </div>
                        <el-switch v-model="publishCartEnabled" />
                    </div>
                </div>

                <div class="rounded-2xl p-4 shadow-sm" style="background: #ffffff; border: 1px solid #f3f4f6">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-extrabold text-[#1a1a1a]">商家定位</span>
                                <span class="text-[10px] font-semibold text-primary bg-[#eff5ff] rounded px-1.5 py-0.5">
                                    抖音专属
                                </span>
                            </div>
                            <span class="text-xs mt-0.5 text-[#9ca3af]">开启后挂载对应的定位，未填写则不生效</span>
                        </div>
                        <el-switch v-model="publishStoreLocationEnabled" />
                    </div>
                    <el-input
                        v-if="publishStoreLocationEnabled"
                        v-model="publishStoreLocation"
                        class="mt-3"
                        placeholder="输入商家定位地址，如：上海市 · 黄浦区 · XX 门店" />
                </div>
            </template>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { ref, reactive, computed, shallowRef } from "vue";
import { getPublishConfigDetail, updatePublishConfig } from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";
import { useLockFn } from "@/hooks/useLockFn";

// 平台（后端 platform 值：1 视频号 3 小红书 4 抖音 5 快手）
enum PublishPlatform {
    SHIPINHAO = 1,
    XIAOHONGSHU = 3,
    DOUYIN = 4,
    KUAISHOU = 5,
}
const PLATFORM_LIST = [
    { platform: PublishPlatform.XIAOHONGSHU, label: "小红书" },
    { platform: PublishPlatform.DOUYIN, label: "抖音" },
    { platform: PublishPlatform.KUAISHOU, label: "快手" },
    { platform: PublishPlatform.SHIPINHAO, label: "视频号" },
] as const;
enum MediaType {
    VIDEO = 1,
    IMAGE = 2,
}
enum CopySource {
    AUTO = 1,
    LIBRARY = 2,
}
enum GenerateMode {
    AUTO = 1,
    CUSTOM = 2,
    LIBRARY = 3,
}
enum Basis {
    PERSONA = 1,
    CUSTOM = 2,
}
enum LibUse {
    RANDOM = 1,
    SEQUENCE = 2,
}
enum LibReuse {
    ONCE = 1,
    REPEAT = 2,
}

interface PlatformPublishConfig {
    publish_media_type: MediaType;
    publish_copywriting_source: CopySource;
    generate_basis: Basis;
    custom_direction: string;
    library_use_mode: LibUse;
    library_reuse_mode: LibReuse;
    is_content_location: 0 | 1;
    content_location: string;
}

const getPlatformDefault = (platform: PublishPlatform): PlatformPublishConfig => ({
    publish_media_type: platform === PublishPlatform.XIAOHONGSHU ? MediaType.IMAGE : MediaType.VIDEO,
    publish_copywriting_source: CopySource.AUTO,
    generate_basis: Basis.PERSONA,
    custom_direction: "",
    library_use_mode: LibUse.RANDOM,
    library_reuse_mode: LibReuse.ONCE,
    is_content_location: 1,
    content_location: "",
});

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const loading = ref(false);
const personId = ref<string | number>("");

const createPlatformConfigs = (): Record<number, PlatformPublishConfig> =>
    PLATFORM_LIST.reduce<Record<number, PlatformPublishConfig>>((acc, { platform }) => {
        acc[platform] = getPlatformDefault(platform);
        return acc;
    }, {});

const platformConfigs = reactive<Record<number, PlatformPublishConfig>>(createPlatformConfigs());
const activePlatform = ref<PublishPlatform>(PublishPlatform.XIAOHONGSHU);
const publishCartEnabled = ref(false);
const publishGoodsName = ref("");
const publishStoreLocationEnabled = ref(false);
const publishStoreLocation = ref("");
// 保留后端 content_publish_config 顶层字段（version 2 下由 platform_configs 驱动，顶层原样回传）
const publishConfigBase = ref<Record<string, any>>({});

const toNumber = (v: any, fallback = 0): number => {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
};

// el-radio 可能把 label 转成字符串，统一按数字比较
const currentPlatform = computed(() => toNumber(activePlatform.value, PublishPlatform.XIAOHONGSHU) as PublishPlatform);
const showImageMediaType = computed(() => currentPlatform.value === PublishPlatform.XIAOHONGSHU);
const isDouyinPlatform = computed(() => currentPlatform.value === PublishPlatform.DOUYIN);

const current = computed(
    () => platformConfigs[currentPlatform.value] || getPlatformDefault(currentPlatform.value),
);

const LIBRARY_REUSE_NOTE: Record<LibReuse, string> = {
    [LibReuse.ONCE]: "用完即跳过，避免平台查重。文案用完后该 AI 员工将自动暂停。",
    [LibReuse.REPEAT]: "所有文案均参与抽取，文案不会耗尽。",
};
const libraryReuseNote = computed(() => LIBRARY_REUSE_NOTE[current.value.library_reuse_mode]);

const contentLocationEnabled = computed({
    get: () => current.value.is_content_location === 1,
    set: (v: boolean) => {
        current.value.is_content_location = v ? 1 : 0;
    },
});

// 单平台回显：默认值兜底；legacy（无 platform_configs）时用 content_publish_config 顶层字段迁移
const buildPlatformConfig = (source: Record<string, any>, platform: PublishPlatform): PlatformPublishConfig => {
    const def = getPlatformDefault(platform);
    const src = source && typeof source === "object" ? source : {};
    const generateMode = toNumber(src.generate_mode, 0);
    const copySourceRaw = toNumber(src.publish_copywriting_source, 0);
    const copySource =
        copySourceRaw === CopySource.LIBRARY || copySourceRaw === CopySource.AUTO
            ? copySourceRaw
            : generateMode === GenerateMode.LIBRARY
              ? CopySource.LIBRARY
              : CopySource.AUTO;
    const mediaTypeRaw = toNumber(src.publish_media_type, def.publish_media_type);
    return {
        // 仅小红书支持图文，其他平台强制视频
        publish_media_type:
            platform === PublishPlatform.XIAOHONGSHU && mediaTypeRaw === MediaType.IMAGE
                ? MediaType.IMAGE
                : MediaType.VIDEO,
        publish_copywriting_source: copySource,
        generate_basis: toNumber(src.generate_basis, def.generate_basis) === 2 ? Basis.CUSTOM : Basis.PERSONA,
        custom_direction: String(src.custom_direction ?? def.custom_direction),
        library_use_mode: toNumber(src.library_use_mode, def.library_use_mode) === 2 ? LibUse.SEQUENCE : LibUse.RANDOM,
        library_reuse_mode: toNumber(src.library_reuse_mode, def.library_reuse_mode) === 2 ? LibReuse.REPEAT : LibReuse.ONCE,
        is_content_location:
            src.is_content_location == null ? def.is_content_location : Number(src.is_content_location) === 1 ? 1 : 0,
        content_location: String(src.content_location ?? ""),
    };
};

const applyDetail = (data: Record<string, any>) => {
    const cfg = data?.content_publish_config || {};
    publishConfigBase.value = cfg;

    const platConfigs = cfg.platform_configs || {};
    const hasPlatformConfigs = platConfigs && Object.keys(platConfigs).length > 0;
    PLATFORM_LIST.forEach(({ platform }) => {
        const raw = platConfigs[platform] ?? platConfigs[String(platform)];
        const source = raw ?? (hasPlatformConfigs ? {} : cfg);
        platformConfigs[platform] = buildPlatformConfig(source, platform);
    });

    publishCartEnabled.value = Number(data?.is_shopping_cart) === 1;
    publishGoodsName.value = String(data?.goods_name ?? "");
    publishStoreLocationEnabled.value = Number(data?.is_store_position) === 1;
    publishStoreLocation.value = String(data?.store_position ?? "");
};

const getDetail = async () => {
    if (!personId.value) return;
    loading.value = true;
    try {
        const data = await getPublishConfigDetail({ id: personId.value });
        applyDetail(data || {});
    } finally {
        loading.value = false;
    }
};

const validate = (): boolean => {
    for (const { platform, label } of PLATFORM_LIST) {
        const config = platformConfigs[platform];
        const isAuto = config.publish_copywriting_source === CopySource.AUTO;
        if (isAuto && config.generate_basis === Basis.CUSTOM && !config.custom_direction.trim()) {
            activePlatform.value = platform;
            feedback.msgWarning(`请填写「${label}」的自定义方向`);
            return false;
        }
        if (config.is_content_location === 1 && !config.content_location.trim()) {
            activePlatform.value = platform;
            feedback.msgWarning(`请填写「${label}」的内容定位地址`);
            return false;
        }
    }
    if (publishStoreLocationEnabled.value && !publishStoreLocation.value.trim()) {
        activePlatform.value = PublishPlatform.DOUYIN;
        feedback.msgWarning("请填写商家定位地址");
        return false;
    }
    return true;
};

const buildPlatformConfigPayload = (platform: PublishPlatform): Record<string, any> => {
    const config = platformConfigs[platform];
    const isLibrary = config.publish_copywriting_source === CopySource.LIBRARY;
    const isCustomBasis = config.generate_basis === Basis.CUSTOM;
    return {
        platform,
        publish_media_type:
            platform === PublishPlatform.XIAOHONGSHU ? config.publish_media_type : MediaType.VIDEO,
        generate_mode: isLibrary ? GenerateMode.LIBRARY : GenerateMode.AUTO,
        publish_copywriting_source: isLibrary ? CopySource.LIBRARY : CopySource.AUTO,
        library_use_mode: config.library_use_mode,
        library_reuse_mode: config.library_reuse_mode,
        generate_basis: config.generate_basis,
        custom_direction: !isLibrary && isCustomBasis ? config.custom_direction.trim() : "",
        is_content_location: config.is_content_location,
        content_location: config.is_content_location === 1 ? config.content_location.trim() : "",
        custom_copywriting: { title: "", content: "", topic_tags: [] },
    };
};

const buildPayload = () => {
    const base = publishConfigBase.value || {};
    const baseCustomCopy = base.custom_copywriting || {};
    const platform_configs = PLATFORM_LIST.reduce<Record<number, Record<string, any>>>((acc, { platform }) => {
        acc[platform] = buildPlatformConfigPayload(platform);
        return acc;
    }, {});

    return {
        id: personId.value,
        content_publish_config: {
            version: 2,
            generate_mode: toNumber(base.generate_mode, 1),
            generate_basis: toNumber(base.generate_basis, 1),
            custom_direction: String(base.custom_direction ?? ""),
            is_content_location: toNumber(base.is_content_location, 0),
            content_location: String(base.content_location ?? ""),
            custom_copywriting: {
                title: String(baseCustomCopy.title ?? ""),
                content: String(baseCustomCopy.content ?? ""),
                topic_tags: Array.isArray(baseCustomCopy.topic_tags) ? baseCustomCopy.topic_tags : [],
            },
            platform_configs,
        },
        is_content_location: 0,
        content_location: "",
        is_shopping_cart: publishCartEnabled.value ? 1 : 0,
        goods_name: publishCartEnabled.value ? publishGoodsName.value.trim() : "",
        is_store_position: publishStoreLocationEnabled.value ? 1 : 0,
        store_position: publishStoreLocationEnabled.value ? publishStoreLocation.value.trim() : "",
    };
};

const handleSave = async () => {
    if (!validate()) return;
    await updatePublishConfig(buildPayload());
    close();
    emit("success");
};

const { isLock, lockFn } = useLockFn(handleSave);
const close = () => emit("close");

const open = (id: string | number) => {
    personId.value = id;
    getDetail();
    popupRef.value?.open();
};

defineExpose({ open });
</script>
