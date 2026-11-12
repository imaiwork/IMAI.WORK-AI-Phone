<template>
    <popup-bottom
        v-model="visible"
        mode="bottom"
        height="88%"
        border-radius="44"
        custom-class="bg-white"
        :clearable="false"
        :z-index="5002"
        :mask-close-able="true"
        :custom-style="{ overflow: 'hidden' }"
        safe-area-inset-bottom
        @close="handleClose">
        <template #header>
            <view class="rule-sheet-handle"></view>
            <view class="rule-sheet-hd">
                <view class="rule-sheet-ic">
                    <image :src="VideoIcon" mode="aspectFit" class="w-[36rpx] h-[36rpx]" />
                </view>
                <view class="rule-sheet-info">
                    <text class="block rule-sheet-title">视频剪辑</text>
                    <text class="block rule-sheet-sub">视频剪辑将按此规则自动生成内容</text>
                </view>
                <view class="rule-sheet-close" @click.stop="handleClose">
                    <u-icon name="close" color="#86909C" size="22"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="rule-pop-content">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="rule-pop-inner">
                            <view class="rule-sec">
                                <view class="rule-sec-hd">
                                    <view class="rule-sec-title"><text>工作方式</text></view>
                                </view>
                                <view class="rule-seg">
                                    <view
                                        v-for="opt in WORK_MODE_OPTIONS"
                                        :key="opt.val"
                                        class="rule-seg-btn"
                                        :class="{ sel: innerState.workMode === opt.val }"
                                        @click="innerState.workMode = opt.val">
                                        <text>{{ opt.label }}</text>
                                    </view>
                                </view>
                                <view class="rule-note">
                                    <text class="block rule-note-text">{{ currentWorkModeNote }}</text>
                                </view>
                            </view>

                            <template v-if="innerState.workMode === WorkModeEnum.Lib">
                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>使用方式</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in LIB_USE_MODE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.libUseMode === opt.val }"
                                            @click="innerState.libUseMode = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="innerState.libUseMode === LibUseModeEnum.Random" class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>随机规则</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in EDITOR_LIB_RULE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.libRandomRule === opt.val }"
                                            @click="innerState.libRandomRule = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentEditorLibRuleNote }}</text>
                                    </view>
                                </view>
                            </template>

                            <template v-else>
                                <!-- 运行模式：这个人设的视频在哪生产、靠什么发布，比生成类型更上位所以放最前；对应人设 run_mode -->
                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>运行模式</text></view>
                                        <text class="rule-sec-hint">决定视频在哪生产、靠什么发布</text>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in RUN_MODE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.runMode === opt.val }"
                                            @click="innerState.runMode = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentRunModeNote.text }}</text>
                                        <text class="block rule-note-extra" :class="{ free: currentRunModeNote.tipOk }">
                                            {{ currentRunModeNote.tip }}
                                        </text>
                                    </view>
                                </view>

                                <!-- 素材模式：开关在「人设详情 › 素材库」（按人设存本机），这里只读回显，不给第二个入口 -->
                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>素材模式</text></view>
                                        <text class="rule-sec-hint">在「人设详情 › 素材库」里切换</text>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentMaterialKindNote }}</text>
                                    </view>
                                </view>

                                <!-- 每日出片条数：无手机模式按「分类型配置」里的类型条数汇总，手机模式按发布时段数出片 -->
                                <view v-if="isCloudRun" class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>每日出片条数</text></view>
                                        <text class="rule-sec-hint">按类型条数出片，不看发布时段</text>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">
                                            今日共 {{ cloudTotalCount }} 条 · {{ cloudCountSummary || "暂未设置" }}
                                        </text>
                                        <text class="block rule-note-extra">
                                            条数在上方「生成类型」卡片里按类型分别设置，出片时按类型交错。
                                        </text>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>生成类型</text></view>
                                        <text class="rule-sec-hint">
                                            {{ isCloudRun ? "可多选，每个类型按自己设置的条数出片" : "可多选，按发布时段数轮流生成" }}
                                        </text>
                                    </view>
                                    <view class="rule-type-grid">
                                        <view
                                            v-for="item in SYNTH_TYPES"
                                            :key="item.id"
                                            class="rule-type"
                                            :class="{ sel: innerState.synthTypes.includes(item.id) }"
                                            @click="toggleSynthType(item.id)">
                                            <view
                                                v-if="innerState.synthTypes.includes(item.id)"
                                                class="rule-type-check">
                                                <u-icon name="checkmark" color="#ffffff" size="16"></u-icon>
                                            </view>
                                            <u-icon
                                                :name="item.icon"
                                                :color="innerState.synthTypes.includes(item.id) ? '#2F73F6' : '#9CA3AF'"
                                                size="42"
                                                class="mb-[14rpx]"></u-icon>
                                            <text class="rule-type-name">{{ item.label }}</text>
                                            <!-- 条数 stepper 只在无手机模式渲染；手机模式由发布时段决定条数 -->
                                            <view
                                                v-if="isCloudRun && innerState.synthTypes.includes(item.id)"
                                                class="rule-type-stepper mt-[10rpx]"
                                                @click.stop>
                                                <view
                                                    class="rule-dur-btn"
                                                    :class="{ disabled: innerState.typeConfig[typeKeyOfSynth(item.id)].videoCount <= 0 }"
                                                    @click.stop="stepTypeVideoCount(typeKeyOfSynth(item.id), -1)">
                                                    <u-icon name="minus" color="#4B5563" size="22"></u-icon>
                                                </view>
                                                <text class="rule-type-stepper-val">
                                                    {{ innerState.typeConfig[typeKeyOfSynth(item.id)].videoCount }}
                                                </text>
                                                <view
                                                    class="rule-dur-btn"
                                                    :class="{ disabled: typeCountRemain(typeKeyOfSynth(item.id)) <= 0 }"
                                                    @click.stop="stepTypeVideoCount(typeKeyOfSynth(item.id), 1)">
                                                    <u-icon name="plus" color="#4B5563" size="22"></u-icon>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <!-- 类型配置：BGM/音量按生成类型独立设置，口播语速全局；默认收起，展开才可配置 -->
                                <view class="rule-sec">
                                    <view class="rule-collapse">
                                        <view
                                            class="rule-collapse-hd"
                                            @click="typeConfigExpanded = !typeConfigExpanded">
                                            <view class="rule-collapse-info">
                                                <view class="rule-sec-title"><text>类型配置</text></view>
                                                <text class="rule-collapse-sub">{{ typeConfigSummary }}</text>
                                            </view>
                                            <u-icon
                                                :name="typeConfigExpanded ? 'arrow-up' : 'arrow-down'"
                                                color="#9CA3AF"
                                                size="22"></u-icon>
                                        </view>
                                        <view v-if="typeConfigExpanded" class="rule-collapse-body">
                                            <view class="rule-seg">
                                                <view
                                                    v-for="key in visibleTypeKeys"
                                                    :key="`type-tab-${key}`"
                                                    class="rule-seg-btn"
                                                    :class="{ sel: activeTypeTab === key }"
                                                    @click="activeTypeTab = key">
                                                    <text>{{ TYPE_CONFIG_LABELS[key] }}</text>
                                                </view>
                                            </view>
                                            <!-- 新闻体时长：跟着「新闻体混剪」这个 tab 走，不再单独占一格 -->
                                            <view v-if="hasNews && activeTypeTab === '4'" class="mt-[16rpx]">
                                                <view class="rule-sec-hd rule-slider-hd">
                                                    <view class="rule-sec-title"><text>新闻体时长</text></view>
                                                    <text class="rule-slider-val">{{ innerState.newsDuration }} 秒</text>
                                                </view>
                                                <view class="rule-dur">
                                                    <view
                                                        class="rule-dur-btn"
                                                        :class="{ disabled: newsDurationMinusDisabled }"
                                                        @click="stepNewsDuration(-1)">
                                                        <u-icon name="minus" color="#4B5563" size="26"></u-icon>
                                                    </view>
                                                    <view class="rule-dur-input-wrap">
                                                        <input
                                                            class="rule-dur-input"
                                                            type="number"
                                                            :value="String(innerState.newsDuration)"
                                                            @input="handleNewsDurationInput"
                                                            @blur="handleNewsDurationBlur" />
                                                        <text class="rule-dur-unit">秒</text>
                                                    </view>
                                                    <view
                                                        class="rule-dur-btn"
                                                        :class="{ disabled: newsDurationPlusDisabled }"
                                                        @click="stepNewsDuration(1)">
                                                        <u-icon name="plus" color="#4B5563" size="26"></u-icon>
                                                    </view>
                                                </view>
                                                <text class="block rule-note-extra mt-[10rpx]">
                                                    范围 {{ NEWS_DURATION.min }}–{{ NEWS_DURATION.max }} 秒
                                                </text>
                                                <!-- 一镜到底：开启后每条新闻体只用 1 条视频素材 -->
                                                <view class="rule-sec-hd rule-slider-hd mt-[10rpx]" style="align-items: center">
                                                    <view class="rule-sec-title"><text>一镜到底</text></view>
                                                    <u-switch
                                                        v-model="innerState.typeConfig['4'].singleShot"
                                                        size="36"
                                                        active-color="#2F73F6"
                                                        inactive-color="#E5E7EB"></u-switch>
                                                </view>
                                                <text class="block rule-note-extra mt-[10rpx]">
                                                    开启后每条新闻体视频只用 1 条视频素材，且每条素材只用一次；素材不足设定时长时按实际时长出片，可用素材用完则该条任务判失败
                                                </text>
                                            </view>
                                            <!-- 指定素材分组：素材库为剪辑原片时可用，每个生成类型独立配置 -->
                                            <view v-if="showMaterialGroupConfig" class="mt-[16rpx]">
                                                <view class="rule-sec-hd rule-slider-hd" style="align-items: center">
                                                    <view class="rule-sec-title"><text>指定素材分组</text></view>
                                                    <u-switch
                                                        :model-value="currentTypeConfig.materialGroupEnabled"
                                                        size="36"
                                                        active-color="#2F73F6"
                                                        inactive-color="#E5E7EB"
                                                        @update:model-value="toggleTypeMaterialGroup"></u-switch>
                                                </view>
                                                <text class="block rule-note-extra mt-[10rpx]">
                                                    开启后该生成类型只从所选素材分组里取素材
                                                </text>
                                                <view
                                                    v-if="currentTypeConfig.materialGroupEnabled"
                                                    class="mt-[12rpx]">
                                                    <!-- 分组用下拉选择：分组多于一组时 chip 会撑爆面板；
                                                         已选分组以标签形式填进输入框，换行时框体自动下延 -->
                                                    <view class="rule-multi-picker" @click="showTypeMaterialGroupPicker = true">
                                                        <view
                                                            v-if="currentTypeConfig.materialGroupIds.length"
                                                            class="rule-multi-picker-tags">
                                                            <view
                                                                v-for="id in currentTypeConfig.materialGroupIds"
                                                                :key="`type-material-group-chip-${activeTypeTab}-${id}`"
                                                                class="rule-multi-picker-tag">
                                                                <text class="rule-multi-picker-tag-text">{{ materialGroupName(id) }}</text>
                                                                <view
                                                                    class="rule-multi-picker-tag-close"
                                                                    @click.stop="removeTypeMaterialGroupId(id)">
                                                                    <u-icon name="close" color="#2F73F6" size="16"></u-icon>
                                                                </view>
                                                            </view>
                                                        </view>
                                                        <text v-else class="rule-multi-picker-ph">请选择素材分组</text>
                                                        <u-icon
                                                            class="rule-multi-picker-arrow"
                                                            name="arrow-down"
                                                            color="#C9CDD4"
                                                            size="20"></u-icon>
                                                    </view>
                                                    <text
                                                        v-if="!materialGroups.length"
                                                        class="block mt-[10rpx] text-[22rpx] text-[#C0C4CC]">
                                                        暂无素材分组
                                                    </text>
                                                </view>
                                            </view>
                                            <!-- 画面素材：只有数字人口播会走 AI 抓素材，其余类型恒走素材库，因此跟这个 tab 走 -->
                                            <view v-if="hasDigitalHuman && activeTypeTab === '1'" class="mt-[16rpx]">
                                                <view class="rule-sec-hd rule-slider-hd">
                                                    <view class="rule-sec-title"><text>画面素材</text></view>
                                                </view>
                                                <view class="rule-seg">
                                                    <view
                                                        v-for="opt in MATERIAL_SOURCE_OPTIONS"
                                                        :key="opt.val"
                                                        class="rule-seg-btn"
                                                        :class="{ sel: innerState.materialSource === opt.val }"
                                                        @click="innerState.materialSource = opt.val">
                                                        <text>{{ opt.label }}</text>
                                                    </view>
                                                </view>
                                                <!-- 已开启指定素材分组的类型不会走 AI 抓素材，提前说清楚免得以为 AI 还在补 -->
                                                <text
                                                    v-if="aiMaterialDisabledTip"
                                                    class="block mt-[10rpx] text-[22rpx] text-[#FF7D00]">
                                                    {{ aiMaterialDisabledTip }}
                                                </text>
                                                <view v-if="isMaterialConflict" class="rule-note rule-note-error">
                                                    <text>
                                                        当前素材库为空，使用【纯素材库】将导致任务生成失败。请先上传素材。
                                                    </text>
                                                </view>
                                                <view v-else class="rule-note">
                                                    <text class="block rule-note-text">
                                                        {{ currentMaterialHint.effect }}
                                                    </text>
                                                    <text
                                                        class="block rule-note-extra"
                                                        :class="{ free: currentMaterialHint.costType === 'free' }">
                                                        {{ currentMaterialHint.cost }}
                                                    </text>
                                                </view>
                                                <view
                                                    v-if="innerState.materialSource === MaterialSourceEnum.Square"
                                                    class="mt-[16rpx] flex flex-col gap-[12rpx]">
                                                    <text class="text-[22rpx] text-[#86909C]">
                                                        从这些广场分组取素材（不选=全部已上架分组）
                                                    </text>
                                                    <view class="flex flex-wrap gap-[12rpx]">
                                                        <view
                                                            v-for="item in squareGroups"
                                                            :key="item.id"
                                                            class="rule-seg-btn"
                                                            :class="{ sel: innerState.squareGroupIds.includes(item.id) }"
                                                            @click="toggleSquareGroup(item.id)">
                                                            <text>{{ item.name }}</text>
                                                        </view>
                                                    </view>
                                                    <text v-if="!squareGroups.length" class="text-[22rpx] text-[#C0C4CC]">
                                                        还没有上架的广场分组
                                                    </text>
                                                </view>
                                            </view>
                                            <view class="mt-[16rpx]">
                                                <view class="rule-sec-hd rule-slider-hd">
                                                    <view class="rule-sec-title"><text>背景音乐</text></view>
                                                </view>
                                                <view class="rule-seg">
                                                    <view
                                                        v-for="opt in MUSIC_SOURCE_OPTIONS"
                                                        :key="`type-music-${activeTypeTab}-${opt.val}`"
                                                        class="rule-seg-btn"
                                                        :class="{ sel: currentTypeConfig.musicSource === opt.val }"
                                                        @click="pickTypeMusicSource(activeTypeTab, opt.val)">
                                                        <text>{{ opt.label }}</text>
                                                    </view>
                                                </view>
                                                <view class="rule-note">
                                                    <text class="block rule-note-text">{{ currentTypeMusicHint }}</text>
                                                </view>
                                                <view
                                                    v-if="currentTypeConfig.musicSource === MusicSourceEnum.System"
                                                    class="mt-[16rpx]">
                                                    <!-- 分组用选择器：分组多了 chip 会撑爆面板 -->
                                                    <view class="rule-picker" @click="showGroupPicker = true">
                                                        <text class="rule-picker-label">音乐分组</text>
                                                        <view class="rule-picker-value">
                                                            <text class="rule-picker-text">{{ currentTypeGroupLabel }}</text>
                                                            <u-icon name="arrow-down" color="#C9CDD4" size="20"></u-icon>
                                                        </view>
                                                    </view>
                                                    <view class="rule-seg mt-[16rpx]">
                                                        <view
                                                            class="rule-seg-btn"
                                                            :class="{ sel: !currentTypeConfig.systemMusicId }"
                                                            @click="clearTypeSystemMusic(activeTypeTab)">
                                                            <text>{{ currentTypeConfig.musicGroupId > 0 ? "组内随机" : "每次随机" }}</text>
                                                        </view>
                                                        <view
                                                            class="rule-seg-btn"
                                                            :class="{ sel: !!currentTypeConfig.systemMusicId }"
                                                            @click="openTypeSystemMusic(activeTypeTab)">
                                                            <text>指定一首</text>
                                                        </view>
                                                    </view>
                                                    <view
                                                        v-if="currentTypeConfig.systemMusicId"
                                                        class="mt-[12rpx] flex items-center gap-[12rpx]">
                                                        <text class="flex-1 min-w-0 text-[24rpx] text-[#374151] line-clamp-1">
                                                            {{ currentTypeConfig.systemMusicName || "已指定" }}
                                                        </text>
                                                        <text
                                                            class="flex-shrink-0 text-[24rpx] text-primary font-bold"
                                                            @click="openTypeSystemMusic(activeTypeTab)">
                                                            重选
                                                        </text>
                                                    </view>
                                                    <text
                                                        v-if="currentTypeConfig.systemMusicId && !currentTypeConfig.systemMusicEnabled"
                                                        class="block text-[22rpx] text-[#F59E0B] mt-[8rpx]">
                                                        这首已下架，下次将随机
                                                    </text>
                                                </view>

                                                <view
                                                    v-if="currentTypeConfig.musicSource !== MusicSourceEnum.None"
                                                    class="mt-[16rpx]">
                                                    <view class="rule-sec-hd rule-slider-hd">
                                                        <view class="rule-sec-title"><text>音乐音量</text></view>
                                                        <text class="rule-slider-val">{{ currentTypeVolumePercent }}%</text>
                                                    </view>
                                                    <!-- key：切换类型/来源时重新挂载，避免沿用上一块的轨道宽度 -->
                                                    <view
                                                        v-if="visible"
                                                        :key="`type-vol-${activeTypeTab}-${currentTypeConfig.musicSource}`"
                                                        class="rule-slider-body">
                                                        <u-slider
                                                            v-model="typeVolumeSlider"
                                                            :min="0"
                                                            :max="10"
                                                            :step="1"
                                                            active-color="#2F73F6"
                                                            inactive-color="#E5E9F0"
                                                            block-width="36"></u-slider>
                                                        <view class="rule-slider-labels">
                                                            <text
                                                                v-for="label in MUSIC_VOLUME_LABELS"
                                                                :key="label"
                                                                class="rule-slider-label">
                                                                {{ label }}
                                                            </text>
                                                        </view>
                                                    </view>
                                                </view>

                                                <!-- 口播语速：按类型各存一份，三类文案节奏不同，不能互相带着改 -->
                                                <view class="mt-[16rpx]">
                                                    <view class="rule-sec-hd rule-slider-hd">
                                                        <view class="rule-sec-title"><text>口播语速</text></view>
                                                        <text class="rule-slider-val">{{ currentTypeSpeechRateLabel }}</text>
                                                    </view>
                                                    <view v-if="visible" class="rule-slider-body">
                                                        <u-slider
                                                            v-model="typeSpeechRateSlider"
                                                            :min="5"
                                                            :max="20"
                                                            :step="1"
                                                            active-color="#2F73F6"
                                                            inactive-color="#E5E9F0"
                                                            block-width="36"></u-slider>
                                                        <view class="rule-slider-labels">
                                                            <text
                                                                v-for="label in SPEECH_RATE_LABELS"
                                                                :key="label"
                                                                class="rule-slider-label">
                                                                {{ label }}
                                                            </text>
                                                        </view>
                                                    </view>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>视频模板</text></view>
                                        <text class="rule-sec-hint">每个生成类型可独立配置，支持多选</text>
                                    </view>
                                    <view class="rule-tpl-entry" @click="handleOpenTemplatePicker">
                                        <view class="rule-tpl-ic">
                                            <u-icon name="grid" color="#2F73F6" size="28"></u-icon>
                                        </view>
                                        <view class="rule-tpl-info">
                                            <text class="block rule-tpl-title">模板选择</text>
                                            <text class="block rule-tpl-summary">{{ templateSummary }}</text>
                                        </view>
                                        <u-icon name="arrow-right" color="#C9CDD4" size="22"></u-icon>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>文案来源</text></view>
                                    </view>
                                    <view class="rule-seg rule-seg-copy">
                                        <view
                                            v-for="opt in copySourceOptions"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{
                                                sel: innerState.copySource === opt.val,
                                                locked: opt.locked,
                                            }"
                                            @click="!opt.locked && (innerState.copySource = opt.val)">
                                            <u-icon
                                                v-if="opt.locked"
                                                name="lock"
                                                color="#C0C8D8"
                                                size="20"
                                                class="mr-[6rpx]"></u-icon>
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentCopyHint }}</text>
                                    </view>

                                    <copy-hotspot-panel
                                        v-if="visible && hasCopyHotspot"
                                        ref="copyHotspotPanelRef"
                                        :persona-id="personId"
                                        v-model:take-mode="innerState.hotspotTakeMode"
                                        @go-track="handleGoHotspotTrack"
                                        @ask-delete="handleAskDeleteHotspot" />

                                    <view v-if="hasCopyAiGen" class="rule-sub rule-sub-ai">
                                        <text class="rule-sub-label">文案生成类型</text>
                                        <text class="rule-sub-desc">决定 AI 往哪个方向写口播文案</text>
                                        <view class="rule-chips">
                                            <view
                                                v-for="opt in COPY_AI_TYPE_OPTIONS"
                                                :key="opt.val"
                                                class="rule-chip"
                                                :class="{ sel: innerState.copyAiType === opt.val }"
                                                @click="innerState.copyAiType = opt.val">
                                                <text>{{ opt.label }}</text>
                                            </view>
                                        </view>
                                        <view
                                            v-if="innerState.copyAiType === CopyAiTypeEnum.Custom"
                                            class="rule-chip-custom">
                                            <input
                                                v-model="innerState.copyAiCustom"
                                                class="rule-chip-custom-input"
                                                :maxlength="COPY_AI_CUSTOM_MAX"
                                                placeholder="一句话描述想要的类型"
                                                placeholder-class="rule-chip-custom-ph" />
                                            <text class="rule-chip-custom-cnt">
                                                {{ innerState.copyAiCustom.length }}/{{ COPY_AI_CUSTOM_MAX }}
                                            </text>
                                        </view>
                                        <view class="rule-note">
                                            <text class="block rule-note-text">{{ currentCopyAiTypeNote }}</text>
                                        </view>
                                    </view>

                                    <view v-if="hasCopyLib" class="rule-sub">
                                        <text class="rule-sub-label">使用方式</text>
                                        <view class="rule-seg">
                                            <view
                                                v-for="opt in LIB_USE_MODE_OPTIONS"
                                                :key="opt.val"
                                                class="rule-seg-btn"
                                                :class="{ sel: innerState.copyLibUseMode === opt.val }"
                                                @click="innerState.copyLibUseMode = opt.val">
                                                <text>{{ opt.label }}</text>
                                            </view>
                                        </view>

                                        <view v-if="innerState.copyLibUseMode === LibUseModeEnum.Random">
                                            <text class="rule-sub-label">随机规则</text>
                                            <view class="rule-seg">
                                                <view
                                                    v-for="opt in COPY_LIB_RULE_OPTIONS"
                                                    :key="opt.val"
                                                    class="rule-seg-btn"
                                                    :class="{ sel: innerState.copyLibRandomRule === opt.val }"
                                                    @click="innerState.copyLibRandomRule = opt.val">
                                                    <text>{{ opt.label }}</text>
                                                </view>
                                            </view>
                                            <view class="rule-note">
                                                <text class="block rule-note-text">{{ currentCopyLibRuleNote }}</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view class="rule-sec">
                                    <view class="rule-sec-hd">
                                        <view class="rule-sec-title"><text>视频封面</text></view>
                                    </view>
                                    <view class="rule-seg">
                                        <view
                                            v-for="opt in COVER_SOURCE_OPTIONS"
                                            :key="opt.val"
                                            class="rule-seg-btn"
                                            :class="{ sel: innerState.coverSource === opt.val }"
                                            @click="innerState.coverSource = opt.val">
                                            <text>{{ opt.label }}</text>
                                        </view>
                                    </view>
                                    <view class="rule-note">
                                        <text class="block rule-note-text">{{ currentCoverHint }}</text>
                                    </view>

                                    <view v-if="innerState.coverSource === CoverSourceEnum.Manual">
                                        <view
                                            v-if="!innerState.coverImage"
                                            class="rule-upload"
                                            @click="handleUploadCover">
                                            <view class="rule-upload-ic">
                                                <u-icon name="plus" color="#9CA3AF" size="32"></u-icon>
                                            </view>
                                            <text class="block rule-upload-title">点击上传封面图片</text>
                                            <text class="block rule-upload-sub">支持 JPG、PNG 格式</text>
                                        </view>

                                        <view v-else class="rule-cover-preview">
                                            <image
                                                :src="innerState.coverImage"
                                                class="w-full h-[300rpx]"
                                                mode="aspectFill"></image>
                                            <view class="rule-cover-mask">
                                                <view class="rule-cover-action" @click="handleUploadCover">
                                                    <view class="rule-cover-action-ic">
                                                        <u-icon name="reload" color="#ffffff" size="34"></u-icon>
                                                    </view>
                                                    <text class="text-[22rpx] text-white">重新上传</text>
                                                </view>
                                                <view class="rule-cover-action" @click="handleDeleteCover">
                                                    <view class="rule-cover-action-ic">
                                                        <u-icon name="trash" color="#ffffff" size="34"></u-icon>
                                                    </view>
                                                    <text class="text-[22rpx] text-white">删除</text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                </view>

                <view v-show="!showDeleteHotspot" class="rule-sheet-foot">
                    <view class="rule-sheet-done" @click="handleConfirm">完成配置</view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <confirm-dialog
        v-model="showDeleteHotspot"
        :content="deleteHotspotContent"
        confirm-text="删除"
        center
        @confirm="handleConfirmDeleteHotspot" />

    <!-- 音乐分组选择：分组多时用选择器，避免 chip 撑爆面板 -->
    <!-- z-index 必须高于视频剪辑面板自身的 5002，否则弹窗会被渲染在面板下面、看起来像点了没反应 -->
    <popup-bottom v-model="showGroupPicker" title="选择音乐分组" height="55%" :z-index="5003">
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx] pb-[40rpx] flex flex-col gap-[12rpx]">
                    <view
                        class="rule-picker-option"
                        :class="{ sel: currentTypeConfig.musicGroupId === 0 }"
                        @click="pickTypeMusicGroup(0)">
                        <text class="rule-picker-option-text">全部分组（每次随机）</text>
                        <u-icon
                            v-if="currentTypeConfig.musicGroupId === 0"
                            name="checkmark"
                            color="#2F73F6"
                            size="26"></u-icon>
                    </view>
                    <view
                        v-for="group in musicGroups"
                        :key="`picker-group-${group.id}`"
                        class="rule-picker-option"
                        :class="{ sel: currentTypeConfig.musicGroupId === group.id }"
                        @click="pickTypeMusicGroup(group.id)">
                        <text class="rule-picker-option-text">{{ groupOptionLabel(group) }}</text>
                        <u-icon
                            v-if="currentTypeConfig.musicGroupId === group.id"
                            name="checkmark"
                            color="#2F73F6"
                            size="26"></u-icon>
                    </view>
                    <text v-if="!musicGroups.length" class="text-center text-[24rpx] text-[#9CA3AF] py-[40rpx]">
                        暂无音乐分组，可在后台「公共音乐库 › 分组」创建
                    </text>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>

    <!-- 素材分组选择：多选，选中即回填到输入框，关闭后仍可继续取消 -->
    <popup-bottom
        v-model="showTypeMaterialGroupPicker"
        title="选择素材分组"
        height="60%"
        :z-index="5003"
        :mask-close-able="true">
        <template #content>
            <view class="h-full flex flex-col">
                <scroll-view scroll-y class="grow min-h-0">
                    <view class="px-[32rpx] pb-[24rpx] flex flex-col gap-[12rpx]">
                        <view
                            v-for="item in materialGroups"
                            :key="`type-material-group-option-${item.id}`"
                            class="rule-picker-option"
                            :class="{ sel: currentTypeConfig.materialGroupIds.includes(item.id) }"
                            @click="toggleTypeMaterialGroupId(item.id)">
                            <text class="rule-picker-option-text">{{ item.name }}</text>
                            <u-icon
                                v-if="currentTypeConfig.materialGroupIds.includes(item.id)"
                                name="checkmark"
                                color="#2F73F6"
                                size="26"></u-icon>
                        </view>
                        <text v-if="!materialGroups.length" class="text-center text-[24rpx] text-[#9CA3AF] py-[40rpx]">
                            暂无素材分组
                        </text>
                    </view>
                </scroll-view>
                <view class="px-[32rpx] pb-[40rpx] pt-[16rpx]">
                    <view class="rule-sheet-done" @click="showTypeMaterialGroupPicker = false">完成</view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <system-music-sheet
        v-model="showSystemMusic"
        :selected-id="innerState.typeConfig[editingTypeKey].systemMusicId"
        :group-id="innerState.typeConfig[editingTypeKey].musicGroupId"
        allow-group-random
        @confirm="onSystemMusic" />
</template>

<script setup lang="ts">
import { addAiSynthesisRule, updateAiSynthesisRule, getAiSynthesisRuleDetail, getAvatarList } from "@/api/person";
import SystemMusicSheet from "@/components/system-music-sheet/system-music-sheet.vue";
import { getSystemMusicGroups } from "@/api/digital_human";
import { getSquareBrowse } from "@/api/square";
import useUpload from "@/hooks/useUpload";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import VideoIcon from "@/ai_modules/person/static/icons/employee/video-blue.svg";
import { MaterialKindEnum, normalizeMaterialUseMode } from "@/ai_modules/person/enums";
import {
    SynthTypeEnum,
    MaterialSourceEnum,
    CopySourceEnum,
    CopyStyleEnum,
    CoverSourceEnum,
    HotspotTakeModeEnum,
    HOTSPOT_STOCK_DELETE_CONFIRM,
    normalizeHotspotTakeMode,
    RunModeEnum,
    TemplateConfigMap,
    VIDEO_TEMPLATE_CONFIRM_EVENT,
    VIDEO_TEMPLATE_DRAFT_KEY,
} from "../enums";
import {
    ALL_SYNTH_API_TYPES,
    buildTemplateConfigForTypes,
    buildTemplateSummary,
    createDefaultTemplateItem,
    findEmptyCustomType,
    getSynthApiFullLabel,
    normalizeTemplateItem,
    synthUiToApi,
} from "../utils/template-config";
import CopyHotspotPanel from "./copy-hotspot-panel.vue";

// ─── UI 枚举（工作方式 / 成品库为本地态；文案库规则接接口）────────────────────
/** 工作方式：AI 合成视频 / 成品库直发 */
enum WorkModeEnum {
    Ai = "ai",
    Lib = "lib",
}
/** 库使用方式：随机 / 顺序（成品库、文案库共用）*/
enum LibUseModeEnum {
    Random = "random",
    Sequence = "sequence",
}
/** 库随机规则：只用一次 / 可重复 */
enum LibRuleEnum {
    Once = "once",
    Repeat = "repeat",
}
/** 文案来源 · 文案库（UI 专用，未纳入后端提交）*/
const COPY_LIB_VALUE = "lib";

const NEWS_DURATION = { min: 5, max: 300, step: 5, default: 60 } as const;
/** 每日出片条数：仅无手机模式使用，与后端 DAILY_VIDEO_COUNT_MAX 对齐 */
const DAILY_VIDEO_COUNT = { min: 1, max: 20, default: 1 } as const;

/** 背景音乐来源：1 系统 / 2 人设音乐库 / 3 无 */
enum MusicSourceEnum {
    System = 1,
    Persona = 2,
    None = 3,
}

const MUSIC_VOLUME_DEFAULT = 0.3;
const SPEECH_RATE_DEFAULT = 1.0;
const MUSIC_VOLUME_LABELS = ["静音", "较轻", "适中", "较响", "最大"] as const;
const SPEECH_RATE_LABELS = ["0.5x", "较慢", "标准", "较快", "2x"] as const;

const roundToOne = (value: number): number => Math.round(value * 10) / 10;

const normalizeMusicVolume = (value: unknown): number => {
    const num = Number(value);
    if (!Number.isFinite(num)) return MUSIC_VOLUME_DEFAULT;
    return Math.min(1, Math.max(0, roundToOne(num)));
};

const normalizeSpeechRate = (value: unknown): number => {
    const num = Number(value);
    if (!Number.isFinite(num)) return SPEECH_RATE_DEFAULT;
    return Math.min(2, Math.max(0.5, roundToOne(num)));
};

const normalizeMusicSource = (value: unknown): MusicSourceEnum => {
    const num = Number(value);
    if (num === MusicSourceEnum.Persona || num === MusicSourceEnum.None) return num;
    return MusicSourceEnum.System;
};

// ─── 分类型配置（条数仅无手机模式生效；背景音乐与音量两种模式都按类型生效）──
/** key 与闪剪 shanjian_type 同值 */
type TypeConfigKey = "1" | "3" | "4";
const TYPE_CONFIG_KEYS: TypeConfigKey[] = ["1", "3", "4"];
const TYPE_CONFIG_LABELS: Record<TypeConfigKey, string> = {
    "1": "数字人口播",
    "3": "纯素材混剪",
    "4": "新闻体混剪",
};
interface TypeMusicState {
    /** 每日视频数量：仅无手机模式消费，手机模式原样保留但不生效 */
    videoCount: number;
    musicSource: MusicSourceEnum;
    /** 系统音乐分组，0 = 全部分组 */
    musicGroupId: number;
    systemMusicId: string;
    systemMusicName: string;
    systemMusicEnabled: boolean;
    musicVolume: number;
    /** 口播语速：按类型各存一份，缺省继承顶层 speech_rate（老数据三类相同） */
    speechRate: number;
    /** 一镜到底：仅新闻体混剪可开，开启后每条只用 1 条视频素材 */
    singleShot: boolean;
    /** 指定素材分组：开启后该类型只从所选分组取素材 */
    materialGroupEnabled: boolean;
    /** 所选素材分组 ID（剪辑原片库的 ai_persona_material_group.id） */
    materialGroupIds: number[];
}
const createDefaultTypeMusic = (): TypeMusicState => ({
    videoCount: 0,
    musicSource: MusicSourceEnum.System,
    musicGroupId: 0,
    systemMusicId: "",
    systemMusicName: "",
    systemMusicEnabled: true,
    musicVolume: MUSIC_VOLUME_DEFAULT,
    speechRate: SPEECH_RATE_DEFAULT,
    singleShot: false,
    materialGroupEnabled: false,
    materialGroupIds: [],
});
const createDefaultTypeConfig = (): Record<TypeConfigKey, TypeMusicState> => ({
    "1": createDefaultTypeMusic(),
    "3": createDefaultTypeMusic(),
    "4": createDefaultTypeMusic(),
});
const clampTypeVideoCount = (value: unknown): number => {
    const num = Math.round(Number(value));
    if (!Number.isFinite(num)) return 0;
    return Math.max(0, Math.min(DAILY_VIDEO_COUNT.max, num));
};

/** 老数据条数分配：先均分，余数从第一个已勾选类型起各补 1（与后端同口径） */
const splitLegacyVideoCount = (total: number, types: number[]): Record<number, number> => {
    const result: Record<number, number> = {};
    TYPE_CONFIG_KEYS.forEach((key) => (result[Number(key)] = 0));
    const list = types.filter((t) => result[t] !== undefined);
    const n = list.length;
    if (n <= 0 || total <= 0) return result;
    const base = Math.floor(total / n);
    let rest = total % n;
    list.forEach((t) => {
        result[t] = base + (rest > 0 ? 1 : 0);
        if (rest > 0) rest -= 1;
    });
    return result;
};

// ─── 类型 ─────────────────────────────────────────────────────────
interface InnerState {
    id: string;
    synthTypes: string[];
    materialSource: string;
    copySource: string;
    /** AI生成 · 文案生成类型（对应 copywriting_generation_type / copywriting_generation_custom） */
    copyAiType: CopyAiTypeEnum;
    /** AI生成 · 自定义类型描述（copyAiType = custom 时有效） */
    copyAiCustom: string;
    copyStyle: string;
    coverSource: string;
    coverImage: string;
    // 工作方式 / 成品库 / 文案库规则（成品库模式下 library_* 取成品库选项）
    workMode: string;
    /** 运行模式：人设接口 run_mode（1 手机 / 2 无手机），随规则一起提交 */
    runMode: RunModeEnum;
    libUseMode: string;
    libRandomRule: string;
    newsDuration: number;
    copyLibUseMode: string;
    copyLibRandomRule: string;
    /** 热点生成 · 取用规则，提交 hotspot_pick_mode：1 最新入库优先 / 2 每条只用一次 */
    hotspotTakeMode: HotspotTakeModeEnum;
    /** 视频模板：key 为 generation_types 数字（1/3/4） */
    templateConfig: TemplateConfigMap;
    /** 分类型配置：1/3/4 三类恒存在（顶层 music_* 由数字人口播的值回写，兼容旧链路） */
    typeConfig: Record<TypeConfigKey, TypeMusicState>;
    /** 口播语速 0.5~2，一位小数 */
    speechRate: number;
    squareGroupIds: number[];
}

// ─── Props / Emits ────────────────────────────────────────────────
const props = defineProps<{
    modelValue: boolean;
    personId: string;
    isMaterialEmpty: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", val: boolean): void;
    (e: "confirm", state: InnerState, summary: string): void;
}>();

// ─── 显隐双向绑定 ─────────────────────────────────────────────────
const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

// ─── 静态常量 ─────────────────────────────────────────────────────
const SYNTH_TYPES = [
    { id: SynthTypeEnum.DigitalHuman, label: "数字人口播", icon: "account" },
    { id: SynthTypeEnum.News, label: "新闻体混剪", icon: "server-fill" },
    { id: SynthTypeEnum.Material, label: "纯素材混剪", icon: "photo" },
];

const MATERIAL_SOURCE_OPTIONS = [
    { val: MaterialSourceEnum.PureAI, label: "纯AI找素材" },
    { val: MaterialSourceEnum.AILib, label: "AI+素材库" },
    { val: MaterialSourceEnum.PureLib, label: "纯素材库" },
    { val: MaterialSourceEnum.Square, label: "素材库+广场" },
];

const COVER_SOURCE_OPTIONS = [
    { val: CoverSourceEnum.Default, label: "默认封面" },
    { val: CoverSourceEnum.AI, label: "AI自动封面" },
    { val: CoverSourceEnum.Manual, label: "手动封面" },
];

const MUSIC_SOURCE_OPTIONS = [
    { val: MusicSourceEnum.System, label: "系统音乐库" },
    { val: MusicSourceEnum.Persona, label: "人设音乐库" },
    { val: MusicSourceEnum.None, label: "无背景音乐" },
];

const MUSIC_SOURCE_HINTS: Record<number, string> = {
    [MusicSourceEnum.System]:
        "默认每次随机。指定一首后，人设自动出片、设备发布、热点已选人设、爆款人设模式都会用这一首。闪剪手动创建仍当次另选。",
    [MusicSourceEnum.Persona]: "从该人设「素材库 · 音乐库」中随机取用自定义配乐。",
    [MusicSourceEnum.None]: "生成视频时不添加背景音乐，仅保留口播与环境声。",
};

const WORK_MODE_OPTIONS = [
    { val: WorkModeEnum.Ai, label: "AI 合成视频" },
    { val: WorkModeEnum.Lib, label: "成品库直发" },
];

const WORK_MODE_NOTES: Record<string, string> = {
    [WorkModeEnum.Ai]: "AI 自动合成数字人口播 / 新闻体混剪 / 纯素材混剪短视频。",
    [WorkModeEnum.Lib]: "从「素材库 · 成品直发库」按规则取用已上传的成品视频，跳过 AI 合成，不消耗算力。",
};

const RUN_MODE_OPTIONS = [
    { val: RunModeEnum.Phone, label: "手机模式" },
    { val: RunModeEnum.Cloud, label: "无手机模式" },
];

/** 运行模式说明：text 讲在哪生产、靠什么发布；tip 讲前置条件（tipOk 绿字，否则橙字提醒） */
const RUN_MODE_NOTES: Record<RunModeEnum, { text: string; tip: string; tipOk: boolean }> = {
    [RunModeEnum.Phone]: {
        text: "交给绑定的 AI 手机制作并发布，自动化最全。",
        tip: "没绑手机 / 手机掉线时不会自动开工",
        tipOk: false,
    },
    [RunModeEnum.Cloud]: {
        text: "不用绑手机，云端自动制作，成片进内容记录，自己下载发布。",
        tip: "无需准备设备",
        tipOk: true,
    },
};

/** 素材模式只读回显文案：讲合成时怎么取用素材 */
const MATERIAL_KIND_NOTES: Record<MaterialKindEnum, string> = {
    [MaterialKindEnum.AUTO]: "自动适配模式：什么规格都能传，合成时统一缩放到模板分辨率。",
    [MaterialKindEnum.ORIGINAL]: "原图模式：合成时不缩放，只取原图模式下传的素材。",
};

const LIB_USE_MODE_OPTIONS = [
    { val: LibUseModeEnum.Random, label: "随机使用" },
    { val: LibUseModeEnum.Sequence, label: "顺序使用" },
];

const EDITOR_LIB_RULE_OPTIONS = [
    { val: LibRuleEnum.Once, label: "每个成品只用一次" },
    { val: LibRuleEnum.Repeat, label: "可重复使用" },
];

const EDITOR_LIB_RULE_NOTES: Record<string, string> = {
    [LibRuleEnum.Once]: "用完即跳过，避免平台查重。成品用完后该 AI 员工将自动暂停。",
    [LibRuleEnum.Repeat]: "所有成品均参与抽取，成品不会耗尽。",
};

const COPY_LIB_RULE_OPTIONS = [
    { val: LibRuleEnum.Once, label: "每个文案只用一次" },
    { val: LibRuleEnum.Repeat, label: "可重复使用" },
];

const COPY_LIB_RULE_NOTES: Record<string, string> = {
    [LibRuleEnum.Once]: "用完即跳过，避免重复内容触发平台查重。",
    [LibRuleEnum.Repeat]: "所有文案均参与抽取，文案不会耗尽。",
};

const MATERIAL_HINTS: Record<string, { effect: string; cost: string; costType: string }> = {
    [MaterialSourceEnum.PureAI]: {
        effect: "AI全网智能匹配高清素材，画面丰富度最高。",
        cost: "需额外消耗素材搜索算力",
        costType: "warn",
    },
    [MaterialSourceEnum.AILib]: {
        effect: "优先使用您的素材，不足部分由AI智能补充。",
        cost: "需额外消耗少量补充算力",
        costType: "warn",
    },
    [MaterialSourceEnum.PureLib]: {
        effect: "仅使用您上传的素材库，画面内容完全可控。",
        cost: "无额外费用",
        costType: "free",
    },
    [MaterialSourceEnum.Square]: {
        effect: "自有素材与广场素材对半使用，一边不够时用另一边补缺口。",
        cost: "无额外费用",
        costType: "free",
    },
};

const COPY_HINTS: Record<string, string> = {
    rewrite: "智能提取爆款视频框架，大幅提升上热门概率。",
    ai_gen: "根据核心词快速裂变，保证极高原创度和产出效率。",
    none: "纯画面与音乐展示，不生成任何口播或字幕，适合风景、沉浸式空镜。",
    [CopySourceEnum.Hotspot]: "从热点库取用已入库的热点，结合人设生成追热点文案，时效性最强。",
    [COPY_LIB_VALUE]: "从该人设的文案库中按规则取用，已审过的内容直接发，更稳。",
};

/** AI生成 · 文案生成类型（预设 + 自定义） */
enum CopyAiTypeEnum {
    Ganhuo = "ganhuo",
    Zhongcao = "zhongcao",
    Guandian = "guandian",
    Juqing = "juqing",
    Qinggan = "qinggan",
    Jiemi = "jiemi",
    Custom = "custom",
}
const COPY_AI_CUSTOM_MAX = 60;
const COPY_AI_TYPE_OPTIONS: { val: CopyAiTypeEnum; label: string; note: string }[] = [
    { val: CopyAiTypeEnum.Ganhuo, label: "干货科普", note: "把卖点讲成知识点，先给结论再讲原因，适合建立专业信任。" },
    { val: CopyAiTypeEnum.Zhongcao, label: "带货种草", note: "突出使用场景与前后对比，结尾直接引导下单或进店。" },
    { val: CopyAiTypeEnum.Guandian, label: "观点评论", note: "对行业现象亮明态度，争议感强，评论区互动率高。" },
    { val: CopyAiTypeEnum.Juqing, label: "剧情段子", note: "小剧情加反转带出产品，完播率高，适合泛流量涨粉。" },
    { val: CopyAiTypeEnum.Qinggan, label: "情感共鸣", note: "讲人和故事，唤起共鸣后自然带出品牌，转化路径更软。" },
    { val: CopyAiTypeEnum.Jiemi, label: "揭秘避坑", note: "用「内行人才知道」的口吻拆行业内幕，信任感与收藏率高。" },
    {
        val: CopyAiTypeEnum.Custom,
        label: "✏️ 自定义",
        note: "用一句话描述想要的类型，AI 按你的描述生成。如：反常识冷知识，结尾抛一个互动问题。",
    },
];
/** 文案生成类型：枚举 ↔ 接口数字（copywriting_generation_type 1~7） */
const COPY_AI_TYPE_TO_API: Record<string, number> = {
    [CopyAiTypeEnum.Ganhuo]: 1,
    [CopyAiTypeEnum.Zhongcao]: 2,
    [CopyAiTypeEnum.Guandian]: 3,
    [CopyAiTypeEnum.Juqing]: 4,
    [CopyAiTypeEnum.Qinggan]: 5,
    [CopyAiTypeEnum.Jiemi]: 6,
    [CopyAiTypeEnum.Custom]: 7,
};
const API_TO_COPY_AI_TYPE: Record<string, CopyAiTypeEnum> = Object.fromEntries(
    Object.entries(COPY_AI_TYPE_TO_API).map(([k, v]) => [String(v), k as CopyAiTypeEnum]),
);

const COVER_HINTS: Record<string, string> = {
    [CoverSourceEnum.Default]: "截取视频首帧作为封面，简单直接。",
    [CoverSourceEnum.AI]: "AI 根据视频内容自动生成更适合推荐流的封面。",
    [CoverSourceEnum.Manual]: "上传固定封面图，适合统一账号视觉风格。",
};

// ─── 提交 / 回显 映射表 ───────────────────────────────────────────

/** 生成类型：枚举 → 接口数字 */
const SYNTH_TYPE_TO_API: Record<string, number> = {
    [SynthTypeEnum.DigitalHuman]: 1,
    [SynthTypeEnum.Material]: 3,
    [SynthTypeEnum.News]: 4,
};

/** 画面素材来源：枚举 ↔ 接口数字 */
const MATERIAL_SOURCE_TO_API: Record<string, number> = {
    [MaterialSourceEnum.PureAI]: 1,
    [MaterialSourceEnum.AILib]: 2,
    [MaterialSourceEnum.PureLib]: 3,
    [MaterialSourceEnum.Square]: 4,
};

/** 文案来源：枚举 ↔ 接口数字（4 = 文案库，5 = 热点生成）*/
const COPY_SOURCE_TO_API: Record<string, number> = {
    [CopySourceEnum.Rewrite]: 1,
    [CopySourceEnum.AIGen]: 2,
    [CopySourceEnum.None]: 3,
    [COPY_LIB_VALUE]: 4,
    [CopySourceEnum.Hotspot]: 5,
};

/** 文案库 / 成品库使用方式：枚举 ↔ 接口数字 */
const LIB_USE_MODE_TO_API: Record<string, number> = {
    [LibUseModeEnum.Random]: 1,
    [LibUseModeEnum.Sequence]: 2,
};

/** 文案库 / 成品库重复规则：枚举 ↔ 接口数字 */
const LIB_RULE_TO_API: Record<string, number> = {
    [LibRuleEnum.Once]: 1,
    [LibRuleEnum.Repeat]: 2,
};

/** 视频封面：枚举 ↔ 接口数字 */
const COVER_SOURCE_TO_API: Record<string, number> = {
    [CoverSourceEnum.Default]: 1,
    [CoverSourceEnum.AI]: 2,
    [CoverSourceEnum.Manual]: 3,
};

/** 运行模式：枚举 ↔ 人设接口 run_mode（1 手机模式 / 2 无手机模式） */
const RUN_MODE_TO_API: Record<string, number> = {
    [RunModeEnum.Phone]: 1,
    [RunModeEnum.Cloud]: 2,
};

/** 工具函数：将映射表反转，用于回显 */
const invertMap = <K extends string, V extends string | number>(map: Record<K, V>) =>
    Object.fromEntries(Object.entries(map).map(([k, v]) => [v, k])) as Record<string, K>;

const API_TO_MATERIAL_SOURCE = invertMap(MATERIAL_SOURCE_TO_API);
const API_TO_COPY_SOURCE = invertMap(COPY_SOURCE_TO_API);
const API_TO_COVER_SOURCE = invertMap(COVER_SOURCE_TO_API);
const API_TO_SYNTH_TYPE = invertMap(SYNTH_TYPE_TO_API);
const API_TO_LIB_USE_MODE = invertMap(LIB_USE_MODE_TO_API);
const API_TO_LIB_RULE = invertMap(LIB_RULE_TO_API);
const API_TO_RUN_MODE = invertMap(RUN_MODE_TO_API);

// ─── 组件内部状态 ─────────────────────────────────────────────────
const innerState = reactive<InnerState>({
    id: "",
    synthTypes: [SynthTypeEnum.DigitalHuman],
    materialSource: MaterialSourceEnum.AILib,
    copySource: CopySourceEnum.Rewrite,
    copyAiType: CopyAiTypeEnum.Ganhuo,
    copyAiCustom: "",
    copyStyle: CopyStyleEnum.Spoken,
    coverSource: CoverSourceEnum.AI,
    coverImage: "",
    workMode: WorkModeEnum.Ai,
    runMode: RunModeEnum.Phone,
    libUseMode: LibUseModeEnum.Random,
    libRandomRule: LibRuleEnum.Once,
    newsDuration: NEWS_DURATION.default,
    copyLibUseMode: LibUseModeEnum.Random,
    copyLibRandomRule: LibRuleEnum.Once,
    hotspotTakeMode: HotspotTakeModeEnum.Latest,
    templateConfig: {},
    typeConfig: createDefaultTypeConfig(),
    speechRate: SPEECH_RATE_DEFAULT,
    squareGroupIds: [],
});

const squareGroups = ref<{ id: number; name: string }[]>([]);
const toggleSquareGroup = (id: number) => {
    innerState.squareGroupIds = innerState.squareGroupIds.includes(id)
        ? innerState.squareGroupIds.filter((item) => item !== id)
        : innerState.squareGroupIds.concat(id);
};

/** 人设素材库模式：1=剪辑原片 2=成品直发；只有剪辑原片才允许指定素材分组 */
const publishMode = ref(0);
/** 「指定素材分组」可选分组：由配置详情按当前素材模式一并返回 */
const materialGroups = ref<{ id: number; name: string }[]>([]);
const showMaterialGroupConfig = computed(
    () => publishMode.value === 1 && innerState.workMode === WorkModeEnum.Ai
);
/** 开启前先确认有分组；无分组时保持关闭并提示 */
const toggleTypeMaterialGroup = (val: boolean | string | number): void => {
    const next = Boolean(val);
    if (next && materialGroups.value.length === 0) {
        uni.showToast({ title: "当前无素材分组，不可开启", icon: "none" });
        return;
    }
    currentTypeConfig.value.materialGroupEnabled = next;
};
const toggleTypeMaterialGroupId = (id: number): void => {
    const cfg = currentTypeConfig.value;
    cfg.materialGroupIds = cfg.materialGroupIds.includes(id)
        ? cfg.materialGroupIds.filter((item) => item !== id)
        : cfg.materialGroupIds.concat(id);
};
/** 素材分组选择器开关：多选面板点选即回填输入框 */
const showTypeMaterialGroupPicker = ref(false);
const removeTypeMaterialGroupId = (id: number): void => {
    const cfg = currentTypeConfig.value;
    cfg.materialGroupIds = cfg.materialGroupIds.filter((item) => item !== id);
};
/** 已选分组 ID 转名称；分组被删或详情还没带回时退回 ID，避免输入框空标签 */
const materialGroupName = (id: number): string => {
    const hit = materialGroups.value.find((item) => item.id === id);
    return hit?.name || `分组 ${id}`;
};
const loadSquareGroups = async () => {
    try {
        const res: any = await getSquareBrowse();
        squareGroups.value = res?.groups || [];
    } catch {
        squareGroups.value = [];
    }
};
/** 曲库分组兜底：详情没带 system_music_groups 时单独拉一次（all=1 带上暂无曲目的空分组） */
const loadMusicGroups = async () => {
    try {
        const res: any = await getSystemMusicGroups({ all: 1 });
        const list = Array.isArray(res) ? res : res?.list || [];
        musicGroups.value = list.map((g: any) => ({
            id: Number(g.id),
            name: String(g.name || ""),
            music_count: Number(g.music_count || 0),
        }));
    } catch {
        musicGroups.value = [];
    }
};

const { on: onEventBus } = useEventBusManager();

// ─── 计算属性 ─────────────────────────────────────────────────────
const hasDigitalHuman = computed(() => innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman));
const hasNews = computed(() => innerState.synthTypes.includes(SynthTypeEnum.News));
const hasCopyLib = computed(() => innerState.copySource === COPY_LIB_VALUE);
const hasCopyAiGen = computed(() => innerState.copySource === CopySourceEnum.AIGen);
const hasCopyHotspot = computed(() => innerState.copySource === CopySourceEnum.Hotspot);
const currentCopyAiTypeNote = computed(() => {
    const custom = innerState.copyAiCustom.trim();
    if (innerState.copyAiType === CopyAiTypeEnum.Custom && custom) return `将按「${custom}」的方向生成文案。`;
    return COPY_AI_TYPE_OPTIONS.find((o) => o.val === innerState.copyAiType)?.note ?? "";
});

const activeSynthApiTypes = computed(() =>
    innerState.synthTypes.map((t) => synthUiToApi(t)).filter((n): n is number => typeof n === "number"),
);

const templateSummary = computed(() => buildTemplateSummary(activeSynthApiTypes.value, innerState.templateConfig));

const currentWorkModeNote = computed(() => WORK_MODE_NOTES[innerState.workMode]);
const currentRunModeNote = computed(() => RUN_MODE_NOTES[innerState.runMode]);
const isCloudRun = computed(() => innerState.runMode === RunModeEnum.Cloud);

// 素材模式只读回显：人设接口 material_use_mode（合成配置详情双写同一份），开关在「人设详情 › 素材库」
const materialKind = ref<MaterialKindEnum>(MaterialKindEnum.AUTO);
const currentMaterialKindNote = computed(() => MATERIAL_KIND_NOTES[materialKind.value]);
const currentEditorLibRuleNote = computed(() => EDITOR_LIB_RULE_NOTES[innerState.libRandomRule]);
const currentCopyLibRuleNote = computed(() => COPY_LIB_RULE_NOTES[innerState.copyLibRandomRule]);

const newsDurationMinusDisabled = computed(() => innerState.newsDuration <= NEWS_DURATION.min);
const newsDurationPlusDisabled = computed(() => innerState.newsDuration >= NEWS_DURATION.max);

const canUseNoCopy = computed(
    () =>
        !innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman) &&
        !innerState.synthTypes.includes(SynthTypeEnum.News),
);

const copySourceOptions = computed(() => [
    { val: CopySourceEnum.Rewrite as string, label: "爆款仿写", locked: false },
    { val: CopySourceEnum.AIGen as string, label: "AI生成", locked: false },
    { val: CopySourceEnum.Hotspot as string, label: "热点生成", locked: false },
    { val: COPY_LIB_VALUE, label: "文案库", locked: false },
    // { val: CopySourceEnum.None, label: "无需文案", locked: !canUseNoCopy.value },
]);

const isMaterialConflict = computed(
    () => innerState.materialSource === MaterialSourceEnum.PureLib && props.isMaterialEmpty,
);

const currentMaterialHint = computed(() => MATERIAL_HINTS[innerState.materialSource]);
/**
 * 选「纯AI找素材」时，若数字人口播已开启指定素材分组，AI 抓素材不会生效
 * （出片侧 $willGrabByAi 被置 false，只从所选分组取素材），这里提前说明。
 */
const aiMaterialDisabledTip = computed(() => {
    if (innerState.materialSource !== MaterialSourceEnum.PureAI) return "";
    return innerState.typeConfig["1"].materialGroupEnabled
        ? "已开启指定素材分组，不使用AI素材"
        : "";
});
const currentCopyHint = computed(() => {
    const hint = COPY_HINTS[innerState.copySource] ?? "";
    // 无手机模式下仿写照常可用，但爆款来源只剩手动导入：热点追踪的自动抓取要靠绑定手机去平台搜
    if (isCloudRun.value && innerState.copySource === CopySourceEnum.Rewrite) {
        return hint + "无手机模式下爆款需要在爆款库手动导入分享链接（热点追踪的自动抓取要绑手机）。";
    }
    return hint;
});
const currentCoverHint = computed(() => COVER_HINTS[innerState.coverSource]);
const showSystemMusic = ref(false);
/** 当前正在给哪个生成类型选曲 */
const editingTypeKey = ref<TypeConfigKey>("1");
const musicGroups = ref<{ id: number; name: string; music_count?: number }[]>([]);
/** 分组选项文案：带可用曲目数，空分组提示会不会生效 */
const groupOptionLabel = (group: { name: string; music_count?: number }): string =>
    `${group.name}${Number(group.music_count || 0) > 0 ? ` · ${group.music_count} 首` : " · 暂无曲目"}`;
/** 音乐分组选择弹窗（分组多时 chip 会撑爆面板，改成选择器） */
const showGroupPicker = ref(false);
/** 类型配置卡片默认收起，避免设置项臃肿；收起时各项按已保存的值生效 */
const typeConfigExpanded = ref(false);

/**
 * 类型配置 tab 顺序：与上方「生成类型」卡片的前后顺序一致（数字人口播 → 新闻体混剪 → 纯素材混剪）。
 * 不能按 TYPE_CONFIG_KEYS（1/3/4）排，那样新闻体（4）会被挤到纯素材混剪（3）后面，
 * 站长先选新闻体时会觉得 tab 跑到最后去了。
 */
const SYNTH_CARD_TYPE_KEYS: TypeConfigKey[] = SYNTH_TYPES.map(
    (item) => String(SYNTH_TYPE_TO_API[item.id]) as TypeConfigKey
);
/** 只有勾选的生成类型才有类型配置面板 */
const visibleTypeKeys = computed<TypeConfigKey[]>(() => {
    const keys = SYNTH_CARD_TYPE_KEYS.filter(
        (key) => !!key && innerState.synthTypes.includes(API_TO_SYNTH_TYPE[Number(key)])
    );
    return keys.length ? keys : [TYPE_CONFIG_KEYS[0]];
});
const activeTypeTab = ref<TypeConfigKey>("1");
watch(visibleTypeKeys, (keys) => {
    if (!keys.includes(activeTypeTab.value)) activeTypeTab.value = keys[0];
});

const currentTypeConfig = computed<TypeMusicState>(() => innerState.typeConfig[activeTypeTab.value]);
const currentTypeMusicHint = computed(
    () => MUSIC_SOURCE_HINTS[currentTypeConfig.value.musicSource] || MUSIC_SOURCE_HINTS[MusicSourceEnum.System]
);
const currentTypeVolumePercent = computed(() => Math.round(currentTypeConfig.value.musicVolume * 100));
/** 当前类型选中的分组名（0 = 全部） */
const currentTypeGroupLabel = computed(() => {
    const id = currentTypeConfig.value.musicGroupId;
    if (!id) return "全部分组";
    return musicGroups.value.find((g) => g.id === id)?.name || "全部分组";
});
/** 收起状态下的摘要：每类型的 BGM 来源/音量/语速 + 新闻体时长（仅新闻体） */
const typeConfigSummary = computed(() => {
    const parts = visibleTypeKeys.value.map((key) => {
        const cfg = innerState.typeConfig[key];
        const rate = `${cfg.speechRate.toFixed(1)}x`;
        if (cfg.musicSource === MusicSourceEnum.None) return `${TYPE_CONFIG_LABELS[key]} 无音乐 · ${rate}`;
        const sourceLabel = MUSIC_SOURCE_OPTIONS.find((o) => o.val === cfg.musicSource)?.label ?? "系统音乐库";
        return `${TYPE_CONFIG_LABELS[key]} ${sourceLabel} ${Math.round(cfg.musicVolume * 100)}% · ${rate}`;
    });
    // 新闻体时长现在也收在卡片里，摘要里带一句，免得收起后找不到入口
    if (hasNews.value) parts.push(`新闻体 ${innerState.newsDuration} 秒`);
    return parts.join(" · ");
});
/** 无手机模式的条数汇总（类型条数之和即当天出片总量） */
const cloudTotalCount = computed(() =>
    visibleTypeKeys.value.reduce((sum, key) => sum + Number(innerState.typeConfig[key].videoCount || 0), 0)
);
const cloudCountSummary = computed(() =>
    visibleTypeKeys.value
        .map((key) => `${TYPE_CONFIG_LABELS[key]} ${innerState.typeConfig[key].videoCount}`)
        .join(" / ")
);
/** 数字人口播的类型配置，用来回写顶层音乐字段（兼容旧链路） */
const voiceTypeConfig = computed(() => innerState.typeConfig["1"]);
/** 提交用的分类型配置：手机模式条数原样回传（后端不生效），音乐/语速两种模式都按类型生效 */
const buildTypeConfigPayload = (): Record<string, Record<string, unknown>> => {
    const payload: Record<string, Record<string, unknown>> = {};
    TYPE_CONFIG_KEYS.forEach((key) => {
        const cfg = innerState.typeConfig[key];
        const isSystem = normalizeMusicSource(cfg.musicSource) === MusicSourceEnum.System;
        payload[key] = {
            video_count: clampTypeVideoCount(cfg.videoCount),
            music_source: normalizeMusicSource(cfg.musicSource),
            music_group_id: isSystem ? Number(cfg.musicGroupId) || 0 : 0,
            system_music_id: isSystem ? String(cfg.systemMusicId || "") : "",
            music_volume: normalizeMusicVolume(cfg.musicVolume),
            speech_rate: normalizeSpeechRate(cfg.speechRate),
            // 一镜到底只有新闻体混剪生效，其余类型恒 0
            single_shot: key === "4" && cfg.singleShot ? 1 : 0,
            // 指定素材分组：每个类型独立；关闭时保留已选 ID，便于再次开启
            material_group_enabled: cfg.materialGroupEnabled ? 1 : 0,
            material_group_ids: cfg.materialGroupIds,
        };
    });
    return payload;
};

/** 类型配置里的音量滑块：只渲染当前 tab，直接绑到当前类型 */
const typeVolumeSlider = computed({
    get: () => Math.round(innerState.typeConfig[activeTypeTab.value].musicVolume * 10),
    set: (val: number) => {
        innerState.typeConfig[activeTypeTab.value].musicVolume = normalizeMusicVolume(Number(val) / 10);
    },
});

/** 生成类型卡片上的条数 stepper */
const typeKeyOfSynth = (id: string): TypeConfigKey => String(SYNTH_TYPE_TO_API[id] ?? 1) as TypeConfigKey;
/** 该类型还能再加几条：三类合计不得超过每日上限 */
const typeCountRemain = (key: TypeConfigKey): number =>
    DAILY_VIDEO_COUNT.max - (cloudTotalCount.value - (Number(innerState.typeConfig[key].videoCount) || 0));
const stepTypeVideoCount = (key: TypeConfigKey, dir: 1 | -1): void => {
    const cfg = innerState.typeConfig[key];
    const next = (Number(cfg.videoCount) || 0) + dir;
    cfg.videoCount = clampTypeVideoCount(Math.min(next, typeCountRemain(key)));
};

const pickTypeMusicSource = (key: TypeConfigKey, val: MusicSourceEnum): void => {
    const cfg = innerState.typeConfig[key];
    cfg.musicSource = val;
    if (val !== MusicSourceEnum.System) {
        cfg.musicGroupId = 0;
        cfg.systemMusicId = "";
        cfg.systemMusicName = "";
        cfg.systemMusicEnabled = true;
    }
};
const setTypeMusicGroup = (key: TypeConfigKey, groupId: number): void => {
    const cfg = innerState.typeConfig[key];
    cfg.musicGroupId = Number(groupId) || 0;
    // 换分组后原指定曲目可能不属于该组，直接改为组内随机
    cfg.systemMusicId = "";
    cfg.systemMusicName = "";
    cfg.systemMusicEnabled = true;
};
const clearTypeSystemMusic = (key: TypeConfigKey): void => {
    const cfg = innerState.typeConfig[key];
    cfg.systemMusicId = "";
    cfg.systemMusicName = "";
    cfg.systemMusicEnabled = true;
};
/** 分组选择弹窗选中某项 */
const pickTypeMusicGroup = (groupId: number): void => {
    setTypeMusicGroup(activeTypeTab.value, groupId);
    showGroupPicker.value = false;
};
const openTypeSystemMusic = (key: TypeConfigKey): void => {
    editingTypeKey.value = key;
    showSystemMusic.value = true;
};

const onSystemMusic = (value: { id: string; name: string; group_id?: number }) => {
    const cfg = innerState.typeConfig[editingTypeKey.value];
    cfg.systemMusicId = value.id;
    cfg.systemMusicName = value.name;
    cfg.systemMusicEnabled = value.id !== "";
    // 指定单曲时把该曲所属分组一并记下；「系统随机」group_id=0 表示全部分组
    cfg.musicGroupId = Number(value.group_id || 0);
};

/** 当前类型 tab 的口播语速（各类型独立） */
const currentTypeSpeechRateLabel = computed(() => `${currentTypeConfig.value.speechRate.toFixed(1)}x`);

/** u-slider 用整数刻度：语速 5~20 → 0.5~2.0，只作用于当前 tab 的类型 */
const typeSpeechRateSlider = computed({
    get: () => Math.round(currentTypeConfig.value.speechRate * 10),
    set: (val: number) => {
        innerState.typeConfig[activeTypeTab.value].speechRate = normalizeSpeechRate(Number(val) / 10);
    },
});

// ─── 联动纠错 watch ───────────────────────────────────────────────
watch(
    () => [...innerState.synthTypes],
    () => {
        if (
            !innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman) &&
            innerState.materialSource !== MaterialSourceEnum.PureLib
        ) {
            innerState.materialSource = MaterialSourceEnum.PureLib;
        }
        if (!canUseNoCopy.value && innerState.copySource === CopySourceEnum.None) {
            innerState.copySource = CopySourceEnum.Rewrite;
        }
        // 原地补默认，不重建整个 map，避免取消勾选时丢掉已选模板
        activeSynthApiTypes.value.forEach((apiType) => {
            const key = String(apiType);
            if (!innerState.templateConfig[key]) {
                innerState.templateConfig[key] = createDefaultTemplateItem(apiType);
            }
        });
    },
    { immediate: true },
);

// 切到无手机模式时文案来源若还是找爆款仿写，自动改为 AI 直接生成并提示
onEventBus(VIDEO_TEMPLATE_CONFIRM_EVENT, (payload: TemplateConfigMap) => {
    if (!payload || typeof payload !== "object") return;
    Object.keys(payload).forEach((key) => {
        const apiType = Number(key);
        innerState.templateConfig[key] = normalizeTemplateItem(apiType, payload[key]);
    });
});

// ─── 方法 ─────────────────────────────────────────────────────────
const toggleSynthType = (id: string): void => {
    const idx = innerState.synthTypes.indexOf(id as SynthTypeEnum);
    if (idx > -1) {
        if (innerState.synthTypes.length > 1) {
            innerState.synthTypes.splice(idx, 1);
            // 取消勾选即该类型不出片，条数同步归零，避免后端把「有条数没勾选」的类型补回列表
            innerState.typeConfig[typeKeyOfSynth(id as SynthTypeEnum)].videoCount = 0;
        }
    } else {
        innerState.synthTypes.push(id as SynthTypeEnum);
    }
};

// ─── 新闻体时长 stepper ────────────────────────────────────────────
const clampNewsDuration = (value: unknown): number => {
    const num = Math.round(Number(value));
    if (!Number.isFinite(num)) return NEWS_DURATION.default;
    return Math.max(NEWS_DURATION.min, Math.min(NEWS_DURATION.max, num));
};

const stepNewsDuration = (dir: 1 | -1): void => {
    innerState.newsDuration = clampNewsDuration(innerState.newsDuration + dir * NEWS_DURATION.step);
};

const handleNewsDurationInput = (e: any): void => {
    const raw = Number(e?.detail?.value);
    innerState.newsDuration = Number.isFinite(raw) ? raw : innerState.newsDuration;
};

const handleNewsDurationBlur = (): void => {
    innerState.newsDuration = clampNewsDuration(innerState.newsDuration);
};

const { uploadAndProcessFiles } = useUpload({
    // 封面直接原样交给闪剪，不走素材那套转码压缩，所以这里必须按合成端的硬限制拦：
    // 之前写成 [99999, 99999] 等于没拦，一张 2176x1952 的封面把人设每天每条视频都判失败了
    imageResolution: [2000, 2000],
    onSuccess: (res) => {
        innerState.coverImage = res[0]?.url;
    },
});

const handleUploadCover = async (): Promise<void> => {
    uni.showLoading({ title: "上传中...", mask: true });
    try {
        await uploadAndProcessFiles("image");
    } finally {
        uni.hideLoading();
    }
};

const handleDeleteCover = (): void => {
    uni.showModal({
        title: "提示",
        content: "确定删除当前封面图片吗？",
        success: ({ confirm }) => {
            if (confirm) innerState.coverImage = "";
        },
    });
};

const handleClose = (): void => {
    visible.value = false;
};

const handleGoHotspotTrack = (): void => {
    visible.value = false;
    uni.navigateTo({
        url: "/ai_modules/hotspot/pages/index/index",
    });
};

const copyHotspotPanelRef = ref<{ confirmDelete: () => Promise<void> }>();
const showDeleteHotspot = ref(false);
const deleteHotspotContent = ref(HOTSPOT_STOCK_DELETE_CONFIRM);

const handleAskDeleteHotspot = (content: string): void => {
    deleteHotspotContent.value = content || HOTSPOT_STOCK_DELETE_CONFIRM;
    showDeleteHotspot.value = true;
};

const handleConfirmDeleteHotspot = (): void => {
    copyHotspotPanelRef.value?.confirmDelete();
};

const handleOpenTemplatePicker = (): void => {
    const types = activeSynthApiTypes.value;
    if (!types.length) {
        uni.showToast({ title: "请先选择生成类型", icon: "none" });
        return;
    }

    uni.setStorageSync(VIDEO_TEMPLATE_DRAFT_KEY, {
        types,
        config: buildTemplateConfigForTypes(types, innerState.templateConfig),
    });
    uni.navigateTo({
        url: "/ai_modules/person/pages/video_template_choose/video_template_choose",
    });
};

/** 生成规则摘要文字 */
const buildSummary = (): string => {
    if (innerState.workMode === WorkModeEnum.Lib) {
        const useLabel = LIB_USE_MODE_OPTIONS.find((o) => o.val === innerState.libUseMode)!.label;
        return `成品库直发 · ${useLabel}`;
    }

    // 无手机模式按类型条数出片，摘要直接给每类型条数，员工卡按「 · 」拆分展示
    if (isCloudRun.value) {
        const parts = visibleTypeKeys.value.map(
            (key) => `${TYPE_CONFIG_LABELS[key]} ${innerState.typeConfig[key].videoCount} 条`
        );
        return parts.length ? parts.join(" · ") : "无手机模式 · 暂未设置条数";
    }

    const typeLabels = innerState.synthTypes.map((id) => SYNTH_TYPES.find((t) => t.id === id)!.label);
    const typeStr = typeLabels.length > 1 ? `${typeLabels[0]}等` : typeLabels[0];

    const mLabel = hasDigitalHuman.value
        ? ` · ${MATERIAL_SOURCE_OPTIONS.find((o) => o.val === innerState.materialSource)!.label}`
        : "";

    const cLabel = copySourceOptions.value.find((o) => o.val === innerState.copySource)!.label;

    return `${typeStr}${mLabel} · ${cLabel}`;
};

const validateParams = (): boolean => {
    if (
        innerState.workMode === WorkModeEnum.Ai &&
        innerState.coverSource === CoverSourceEnum.Manual &&
        !innerState.coverImage
    ) {
        uni.showToast({ title: "请上传视频封面", icon: "none" });
        return false;
    }

    if (innerState.workMode === WorkModeEnum.Ai) {
        // 无手机模式没有发布时段兜底，条数全 0 就一条都不出，保存前先拦住
        if (isCloudRun.value && cloudTotalCount.value <= 0) {
            uni.showToast({ title: "请至少给一个生成类型设置视频数量", icon: "none" });
            return false;
        }
        // 指定素材分组：开启后必须至少选一个，否则合成时无素材可取
        if (showMaterialGroupConfig.value) {
            const emptyGroupType = visibleTypeKeys.value.find((key) => {
                const cfg = innerState.typeConfig[key];
                return cfg.materialGroupEnabled && cfg.materialGroupIds.length === 0;
            });
            if (emptyGroupType) {
                uni.showToast({
                    title: `「${TYPE_CONFIG_LABELS[emptyGroupType]}」请至少选择一个素材分组`,
                    icon: "none",
                });
                return false;
            }
        }
        const emptyType = findEmptyCustomType(activeSynthApiTypes.value, innerState.templateConfig);
        if (emptyType != null) {
            uni.showToast({
                title: `「${getSynthApiFullLabel(emptyType)}」请至少选择 1 个模板`,
                icon: "none",
            });
            return false;
        }
    }
    return true;
};

/** 构建提交参数：文案库 / 成品库字段分拆提交，始终携带 work_mode */
const buildApiParams = () => ({
    id: innerState.id,
    persona_id: props.personId,
    generation_types: innerState.synthTypes.map((t) => SYNTH_TYPE_TO_API[t]).filter(Boolean),
    visual_material_source: MATERIAL_SOURCE_TO_API[innerState.materialSource],
    square_group_ids:
        innerState.materialSource === MaterialSourceEnum.Square ? innerState.squareGroupIds : [],
    copywriting_source: COPY_SOURCE_TO_API[innerState.copySource],
    hotspot_pick_mode: normalizeHotspotTakeMode(innerState.hotspotTakeMode),
    copywriting_generation_type: COPY_AI_TYPE_TO_API[innerState.copyAiType],
    copywriting_generation_custom:
        innerState.copyAiType === CopyAiTypeEnum.Custom ? innerState.copyAiCustom.trim() : "",
    library_use_mode: LIB_USE_MODE_TO_API[innerState.copyLibUseMode],
    library_reuse_mode: LIB_RULE_TO_API[innerState.copyLibRandomRule],
    video_cover_source: COVER_SOURCE_TO_API[innerState.coverSource],
    news_mixcut_duration: innerState.newsDuration,
    pic: innerState.coverImage,
    work_mode: innerState.workMode === WorkModeEnum.Lib ? 2 : 1,
    // 成品直发没有运行模式：上面那一节压根不渲染，这里再把回显来的旧值提交回去，
    // 就会让一个成品直发人设背着 run_mode=2，发布 cron 直接跳过它，界面上还看不出来
    run_mode: innerState.workMode === WorkModeEnum.Lib
        ? RUN_MODE_TO_API[RunModeEnum.Phone]
        : RUN_MODE_TO_API[innerState.runMode],
    // 手机模式不提交条数，避免把无手机模式的配置带进按发布时段出片的链路
    daily_video_count: isCloudRun.value ? cloudTotalCount.value : 0,
    product_use_mode: LIB_USE_MODE_TO_API[innerState.libUseMode],
    product_reuse_mode: LIB_RULE_TO_API[innerState.libRandomRule],
    // 顶层音乐取数字人口播的配置，兼容未分类型的旧链路（爆款复刻等）
    music_source: normalizeMusicSource(voiceTypeConfig.value.musicSource),
    system_music_id:
        normalizeMusicSource(voiceTypeConfig.value.musicSource) === MusicSourceEnum.System
            ? voiceTypeConfig.value.systemMusicId
            : "",
    music_volume: normalizeMusicVolume(voiceTypeConfig.value.musicVolume),
    // 分类型配置：条数、来源、分组、指定曲目、音量、口播语速（语速按类型各存一份）
    type_config: buildTypeConfigPayload(),
    // 顶层语速同样取数字人口播，兼容未分类型的旧链路
    speech_rate: normalizeSpeechRate(voiceTypeConfig.value.speechRate),
    // 提交全部类型配置，避免未勾选类型在后端被重置为自动随机
    template_config: buildTemplateConfigForTypes(ALL_SYNTH_API_TYPES, innerState.templateConfig),
});

/** 数字人检测 */
const checkDigitalHuman = async (): Promise<boolean> => {
    const { lists } = await getAvatarList({ persona_id: props.personId });
    // 列表会把冻结/失效的绑定一并列出来（available=false），合成时它们照样会被跳过，
    // 所以这里只数真正能用的，别因为界面上还看得见形象就不提醒降级
    const usable = (lists ?? []).filter((item: any) => item.available !== false);
    if (usable.length === 0) {
        return new Promise((resolve) => {
            uni.showModal({
                title: "提示",
                content: "检测到未配置数字人，实际生成时将自动降级为素材混剪",
                confirmText: "去配置",
                cancelText: "继续保存",
                success: ({ confirm }) => {
                    if (confirm) {
                        uni.navigateTo({
                            url: `/ai_modules/person/pages/digital_human_config/digital_human_config?id=${props.personId}`,
                        });
                        resolve(false);
                    } else {
                        resolve(true);
                    }
                },
            });
        });
    }
    return true;
};

const handleConfirm = async (): Promise<void> => {
    if (!validateParams()) return;

    if (innerState.workMode === WorkModeEnum.Ai && innerState.synthTypes.includes(SynthTypeEnum.DigitalHuman)) {
        const canContinue = await checkDigitalHuman();
        if (!canContinue) return;
    }

    uni.showLoading({ title: "保存中...", mask: true });
    try {
        const params = buildApiParams();
        await (innerState.id ? updateAiSynthesisRule(params) : addAiSynthesisRule(params));

        visible.value = false;
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        emit(
            "confirm",
            {
                ...innerState,
                synthTypes: [...innerState.synthTypes],
                templateConfig: buildTemplateConfigForTypes(ALL_SYNTH_API_TYPES, innerState.templateConfig),
            },
            buildSummary(),
        );
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
    }
};

/** 回显详情数据 */
const getDetail = async (): Promise<void> => {
    const res = await getAiSynthesisRuleDetail({ persona_id: props.personId });
    if (!res) return;

    innerState.id = res.id ?? "";
    const isProductDirect = Number(res.work_mode) === 2 || Number(res.publish_mode) === 2;
    innerState.workMode = isProductDirect ? WorkModeEnum.Lib : WorkModeEnum.Ai;
    // 素材库为「剪辑原片」时才显示「指定素材分组」
    publishMode.value = Number(res.publish_mode) || 0;
    materialGroups.value = Array.isArray(res.material_groups)
        ? res.material_groups
              .map((item: any) => ({ id: Number(item.id) || 0, name: String(item.name || "") }))
              .filter((item: { id: number; name: string }) => item.id > 0)
        : [];
    innerState.synthTypes = (res.generation_types as number[])?.map((n) => API_TO_SYNTH_TYPE[n]).filter(Boolean) ?? [
        SynthTypeEnum.DigitalHuman,
    ];
    innerState.materialSource = API_TO_MATERIAL_SOURCE[res.visual_material_source] ?? MaterialSourceEnum.AILib;
    innerState.squareGroupIds = Array.isArray(res.square_group_ids)
        ? res.square_group_ids.map((id: any) => Number(id)).filter(Boolean)
        : [];
    innerState.copySource = API_TO_COPY_SOURCE[res.copywriting_source] ?? CopySourceEnum.Rewrite;
    innerState.hotspotTakeMode = normalizeHotspotTakeMode(res.hotspot_pick_mode);
    innerState.copyAiType = API_TO_COPY_AI_TYPE[String(res.copywriting_generation_type)] ?? CopyAiTypeEnum.Ganhuo;
    innerState.copyAiCustom = String(res.copywriting_generation_custom ?? "").slice(0, COPY_AI_CUSTOM_MAX);
    const libraryUseMode = API_TO_LIB_USE_MODE[res.library_use_mode] ?? LibUseModeEnum.Random;
    const libraryReuseMode = API_TO_LIB_RULE[res.library_reuse_mode] ?? LibRuleEnum.Once;
    const hasProductFields = res.product_use_mode != null || res.product_reuse_mode != null;
    if (hasProductFields) {
        innerState.copyLibUseMode = libraryUseMode;
        innerState.copyLibRandomRule = libraryReuseMode;
        innerState.libUseMode = API_TO_LIB_USE_MODE[res.product_use_mode] ?? LibUseModeEnum.Random;
        innerState.libRandomRule = API_TO_LIB_RULE[res.product_reuse_mode] ?? LibRuleEnum.Once;
    } else if (isProductDirect) {
        // 旧数据：library_* 实际存的是成品库规则
        innerState.libUseMode = libraryUseMode;
        innerState.libRandomRule = libraryReuseMode;
    } else {
        innerState.copyLibUseMode = libraryUseMode;
        innerState.copyLibRandomRule = libraryReuseMode;
    }
    innerState.coverSource = API_TO_COVER_SOURCE[res.video_cover_source] ?? CoverSourceEnum.Default;
    innerState.newsDuration = res.news_mixcut_duration
        ? clampNewsDuration(res.news_mixcut_duration)
        : NEWS_DURATION.default;
    innerState.coverImage = res.pic ?? "";
    innerState.speechRate = normalizeSpeechRate(res.speech_rate ?? SPEECH_RATE_DEFAULT);
    materialKind.value = normalizeMaterialUseMode(res.material_use_mode);
    innerState.runMode = (API_TO_RUN_MODE[String(res.run_mode)] as RunModeEnum) ?? RunModeEnum.Phone;

    // 分类型配置回显：后端已把老数据补齐（音乐继承顶层 + 条数均分），这里只兜底老接口
    const detailTypeConfig = (res.type_config || {}) as Record<string, any>;
    const legacyCounts = splitLegacyVideoCount(
        Number(res.daily_video_count) || 0,
        ((res.generation_types as number[]) || []).map((n) => Number(n)),
    );
    const nextTypeConfig = createDefaultTypeConfig();
    TYPE_CONFIG_KEYS.forEach((key) => {
        const target = nextTypeConfig[key];
        const cfg = detailTypeConfig[key];
        if (cfg && typeof cfg === "object") {
            target.videoCount = clampTypeVideoCount(cfg.video_count ?? 0);
            target.musicSource = normalizeMusicSource(cfg.music_source ?? res.music_source);
            target.musicGroupId = Number(cfg.music_group_id ?? 0) || 0;
            target.systemMusicId = String(cfg.system_music_id || "");
            target.systemMusicName = String(cfg.system_music_name || "");
            target.systemMusicEnabled = cfg.system_music_enabled !== false;
            target.musicVolume = normalizeMusicVolume(cfg.music_volume ?? res.music_volume ?? MUSIC_VOLUME_DEFAULT);
            target.speechRate = normalizeSpeechRate(cfg.speech_rate ?? res.speech_rate ?? SPEECH_RATE_DEFAULT);
            target.singleShot = Number(cfg.single_shot ?? 0) === 1;
            target.materialGroupEnabled = Number(cfg.material_group_enabled ?? 0) === 1;
            target.materialGroupIds = Array.isArray(cfg.material_group_ids)
                ? cfg.material_group_ids.map((id: any) => Number(id)).filter((id: number) => id > 0)
                : [];
            return;
        }
        // 老数据 / 老接口：三类音乐与语速都继承顶层配置，条数按「先均分余数补前」分配
        target.videoCount = legacyCounts[Number(key)] ?? 0;
        target.musicSource = normalizeMusicSource(res.music_source);
        target.musicGroupId = 0;
        target.systemMusicId = String(res.system_music_id || "");
        target.systemMusicName = String(res.system_music_name || "");
        target.systemMusicEnabled = res.system_music_enabled !== false;
        target.musicVolume = normalizeMusicVolume(res.music_volume ?? MUSIC_VOLUME_DEFAULT);
        target.speechRate = normalizeSpeechRate(res.speech_rate ?? SPEECH_RATE_DEFAULT);
    });
    innerState.typeConfig = nextTypeConfig;
    // 分组由详情一并返回；老版本接口没有该字段时单独拉一次，否则选择器只剩「全部分组」
    if (Array.isArray(res.system_music_groups) && res.system_music_groups.length) {
        musicGroups.value = res.system_music_groups.map((g: any) => ({
            id: Number(g.id),
            name: String(g.name || ""),
            music_count: Number(g.music_count || 0),
        }));
    } else {
        void loadMusicGroups();
    }

    // 回显全部类型模板（含当前未启用），以便重新勾选生成类型时恢复
    innerState.templateConfig = buildTemplateConfigForTypes(
        ALL_SYNTH_API_TYPES,
        (res.template_config || {}) as TemplateConfigMap,
    );
};

watch(visible, (newVal) => {
    if (!newVal) {
        showDeleteHotspot.value = false;
        return;
    }
    // 运行模式与素材模式先回默认，等 getDetail 按接口值回显
    innerState.runMode = RunModeEnum.Phone;
    innerState.hotspotTakeMode = HotspotTakeModeEnum.Latest;
    materialKind.value = MaterialKindEnum.AUTO;
    innerState.typeConfig = createDefaultTypeConfig();
    publishMode.value = 0;
    materialGroups.value = [];
    // 每次打开都从收起态开始，避免上次展开的设置项撑着面板
    typeConfigExpanded.value = false;
    showGroupPicker.value = false;
    showTypeMaterialGroupPicker.value = false;
    getDetail();
    loadSquareGroups();
});

watch(hasCopyHotspot, (hotspot) => {
    if (!hotspot) showDeleteHotspot.value = false;
});

defineExpose({
    getDetail,
    buildSummary,
});
</script>

<style lang="scss" scoped>
.rule-sheet-handle {
    width: 76rpx;
    height: 8rpx;
    border-radius: 999rpx;
    background: #e5e9f0;
    margin: 18rpx auto 0;
    flex-shrink: 0;
}

.rule-sheet-hd {
    display: flex;
    align-items: center;
    gap: 24rpx;
    padding: 24rpx 36rpx 28rpx;
    border-bottom: 2rpx solid #f2f5fa;
    flex-shrink: 0;
    background: #ffffff;
}

.rule-sheet-ic {
    width: 80rpx;
    height: 80rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #eff6ff;
}

.rule-sheet-info {
    flex: 1;
    min-width: 0;
}

.rule-sheet-title {
    font-size: 33rpx;
    font-weight: 800;
    color: #1d2129;
    line-height: 1.2;
}

.rule-sheet-sub {
    font-size: 25rpx;
    color: #9ca3af;
    margin-top: 6rpx;
    line-height: 1.35;
}

.rule-sheet-close {
    width: 60rpx;
    height: 60rpx;
    border-radius: 50%;
    background: #f3f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rule-pop-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}

.rule-pop-inner {
    padding: 8rpx 32rpx 28rpx;
}

.rule-sec {
    margin-top: 44rpx;
}

.rule-sec:first-child {
    margin-top: 8rpx;
}

.rule-sec-hd {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 20rpx;
    margin-bottom: 24rpx;
}

/* 分块标题：与 CS / Operator 的 cs-sec-title / op-row-label 风格对齐
   30rpx / 800 / #1D2129 + 左侧 6rpx 蓝色短条；用 view 承载以兼容小程序的 ::before */
.rule-sec-title {
    display: flex;
    align-items: center;
    font-size: 30rpx;
    font-weight: 800;
    color: #1d2129;
    line-height: 1.2;
}

.rule-sec-title::before {
    content: "";
    width: 6rpx;
    height: 28rpx;
    border-radius: 999rpx;
    background: #2f73f6;
    margin-right: 16rpx;
    flex-shrink: 0;
}

.rule-sec-sub {
    font-size: 22rpx;
    color: #9ca3af;
    font-weight: 400;
    margin-left: 8rpx;
}

.rule-sec-hint {
    flex: 1;
    min-width: 0;
    text-align: right;
    font-size: 22rpx;
    color: #9ca3af;
    line-height: 1.35;
}

.rule-type-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20rpx;
}

.rule-type {
    position: relative;
    min-height: 174rpx;
    border-radius: 28rpx;
    border: 3rpx solid #e9edf4;
    background: #f5f6f8;
    padding: 36rpx 12rpx 28rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.rule-type:active {
    transform: scale(0.97);
}

.rule-type.sel {
    border-color: #2f73f6;
    background: #eff5ff;
}

.rule-type-check {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    background: #2f73f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rule-type-name {
    font-size: 26rpx;
    font-weight: 700;
    color: #6b7280;
    line-height: 1.25;
    text-align: center;
}

.rule-type.sel .rule-type-name {
    color: #2f73f6;
}

/* 类型卡片内的条数 stepper：只在无手机模式渲染，点按不触发勾选 */
.rule-type-stepper {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.rule-type-stepper-val {
    min-width: 44rpx;
    font-size: 26rpx;
    font-weight: 800;
    color: #2f73f6;
    text-align: center;
}

/* 分段控件：与 CS 的 cs-seg / cs-seg-btn 对齐
   轨道 #f3f4f6 / r-20rpx / p-6rpx；激活态白底 + primary 蓝字 */
.rule-seg {
    display: flex;
    background: #f3f4f6;
    border-radius: 20rpx;
    padding: 6rpx;
    gap: 6rpx;
}

.rule-seg-btn {
    flex: 1;
    min-width: 0;
    min-height: 64rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: 700;
    color: #9ca3af;
    line-height: 1.2;
    transition: all 0.15s;
}

.rule-seg-btn.sel {
    background: #ffffff;
    color: #2f73f6;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}

.rule-seg-btn.locked {
    background: #ebeef4;
    color: #c0c8d8;
}

.rule-seg-copy .rule-seg-btn {
    @apply text-[22rpx] whitespace-nowrap;
}

/* 可折叠设置卡：收起只留一行摘要，展开才渲染具体设置项 */
.rule-collapse {
    border-radius: 28rpx;
    background: #f7f9fc;
    border: 2rpx solid #eaeef5;
    padding: 24rpx 28rpx;
}

.rule-collapse-hd {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.rule-collapse-info {
    flex: 1;
    min-width: 0;
}

.rule-collapse-sub {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    line-height: 1.45;
    color: #9ca3af;
}

.rule-collapse-body {
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 2rpx dashed #e2e8f2;
}

/* 分组选择器：点开底部弹窗选，分组多了也不会撑爆面板 */
.rule-picker {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 22rpx 26rpx;
    border-radius: 20rpx;
    background: #ffffff;
    border: 2rpx solid #e9edf4;
}

.rule-picker-label {
    flex-shrink: 0;
    font-size: 24rpx;
    color: #6b7280;
}

.rule-picker-value {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8rpx;
}

.rule-picker-text {
    font-size: 26rpx;
    font-weight: 700;
    color: #1d2129;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.rule-picker-option {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 0 24rpx;
    height: 100rpx;
    border-radius: 20rpx;
    background: #f7f9fc;
    border: 2rpx solid transparent;
}

.rule-picker-option.sel {
    background: #eff5ff;
    border-color: #2f73f6;
}

.rule-picker-option-text {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    font-weight: 700;
    color: #1d2129;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.rule-note {
    margin-top: 20rpx;
    background: #f7f9fc;
    border: 2rpx solid #eef2f8;
    border-radius: 24rpx;
    padding: 24rpx 28rpx;
}

.rule-note-error {
    background: #fef2f2;
    border-color: rgba(252, 165, 165, 0.5);
    color: #dc2626;
    font-weight: 600;
    font-size: 24rpx;
    line-height: 1.65;
}

.rule-note-text {
    font-size: 22rpx;
    color: #6b7280;
    line-height: 1.7;
}

.rule-note-extra {
    color: #f97316;
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.5;
    margin-top: 8rpx;
}

.rule-note-extra.free {
    color: #16a34a;
}

.rule-slider-hd {
    margin-bottom: 8rpx;
}

.rule-slider-val {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 800;
    color: #2f73f6;
    line-height: 1.2;
}

/* 左右各留半个滑钮宽度，避免最大值时滑钮溢出被 overflow:hidden 裁切导致无法回拖 */
.rule-slider-body {
    padding: 0 18rpx;
}

.rule-slider-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 12rpx;
    padding: 0 4rpx;
}

.rule-slider-label {
    font-size: 20rpx;
    color: #9ca3af;
    line-height: 1.2;
}

/* 新闻体时长 stepper */
.rule-dur {
    display: inline-flex;
    align-items: stretch;
    height: 76rpx;
    background: #f7f9fc;
    border: 2rpx solid #e5e9f0;
    border-radius: 20rpx;
    overflow: hidden;
}

.rule-dur-btn {
    width: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s, opacity 0.15s;
}

.rule-dur-btn:active {
    background: #ebeff6;
}

.rule-dur-btn.disabled {
    opacity: 0.3;
    pointer-events: none;
}

.rule-dur-input-wrap {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 0 18rpx;
    background: #ffffff;
    border-left: 2rpx solid #e5e9f0;
    border-right: 2rpx solid #e5e9f0;
    min-width: 150rpx;
}

.rule-dur-input {
    width: 96rpx;
    min-width: 0;
    background: transparent;
    font-size: 30rpx;
    font-weight: 700;
    color: #1d2129;
    text-align: center;
}

.rule-dur-unit {
    font-size: 24rpx;
    color: #9ca3af;
    flex-shrink: 0;
}

/* 文案库子设置 */
.rule-sub {
    margin-top: 24rpx;
}

.rule-sub-label {
    display: block;
    font-size: 24rpx;
    font-weight: 600;
    color: #4b5563;
    margin: 0 0 16rpx;
}

.rule-sub .rule-seg + .rule-sub-label {
    margin-top: 24rpx;
}

/* AI生成 · 文案生成类型 */
.rule-sub-ai {
    padding-left: 24rpx;
    border-left: 4rpx solid #e3e9f2;
}

.rule-sub-ai .rule-sub-label {
    margin-bottom: 8rpx;
}

.rule-sub-desc {
    display: block;
    font-size: 22rpx;
    color: #9ca3af;
    margin: 0 0 20rpx;
}

.rule-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.rule-chip {
    padding: 14rpx 26rpx;
    border-radius: 999rpx;
    border: 2rpx solid #e3e9f2;
    background: #ffffff;
    font-size: 24rpx;
    font-weight: 500;
    color: #4b5563;
    transition: all 0.15s;
}

.rule-chip.sel {
    border-color: #2f73f6;
    background: #2f73f6;
    color: #ffffff;
    font-weight: 700;
}

.rule-chip-custom {
    margin-top: 20rpx;
}

.rule-chip-custom-input {
    width: 100%;
    box-sizing: border-box;
    height: 80rpx;
    padding: 0 24rpx;
    border-radius: 24rpx;
    border: 2rpx solid #e3e9f2;
    background: #f7f9fc;
    font-size: 25rpx;
    color: #1d2129;
}

.rule-chip-custom-ph {
    font-size: 23rpx;
    color: #c9cdd4;
}

.rule-chip-custom-cnt {
    display: block;
    text-align: right;
    font-size: 21rpx;
    color: #c9cdd4;
    margin: 10rpx 4rpx 0;
}

.rule-upload {
    margin-top: 20rpx;
    border: 3rpx dashed #d4dbe6;
    border-radius: 28rpx;
    padding: 52rpx 32rpx;
    text-align: center;
    background: #ffffff;
    transition: all 0.15s;
}

.rule-upload:active {
    background: #f7f9fc;
}

.rule-upload-ic {
    width: 68rpx;
    height: 68rpx;
    border-radius: 50%;
    background: #f2f5fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.rule-upload-title {
    font-size: 26rpx;
    font-weight: 700;
    color: #6b7280;
    margin-top: 20rpx;
}

.rule-upload-sub {
    font-size: 22rpx;
    color: #c0c8d8;
    margin-top: 4rpx;
}

.rule-cover-preview {
    position: relative;
    border-radius: 28rpx;
    overflow: hidden;
    margin-top: 20rpx;
}

.rule-cover-mask {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 48rpx;
}

.rule-cover-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8rpx;
}

.rule-cover-action:active {
    transform: scale(0.96);
}

.rule-cover-action-ic {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2rpx solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.rule-tpl-entry {
    display: flex;
    align-items: center;
    gap: 24rpx;
    background: #f7f9fc;
    border: 2rpx solid #eef1f6;
    border-radius: 28rpx;
    padding: 24rpx 28rpx;
}

.rule-tpl-entry:active {
    background: #f0f4fa;
}

.rule-tpl-ic {
    width: 72rpx;
    height: 72rpx;
    border-radius: 24rpx;
    background: #ebf3ff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rule-tpl-info {
    flex: 1;
    min-width: 0;
}

.rule-tpl-title {
    font-size: 26rpx;
    font-weight: 700;
    color: #1d2129;
    line-height: 1.2;
}

.rule-tpl-summary {
    font-size: 22rpx;
    color: #9ca3af;
    margin-top: 6rpx;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rule-sheet-foot {
    flex-shrink: 0;
    padding: 24rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
    border-top: 2rpx solid #f2f5fa;
    background: #ffffff;
}

.rule-sheet-done {
    width: 100%;
    height: 92rpx;
    border-radius: 28rpx;
    background: #2f73f6;
    color: #ffffff;
    font-size: 31rpx;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12rpx 30rpx rgba(47, 115, 246, 0.24);
}

.rule-sheet-done:active {
    background: #2862d8;
    transform: scale(0.98);
}

/* 素材分组下拉：已选分组以标签填进输入框，超过一行自动换行、框体随之向下延伸 */
.rule-multi-picker {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    min-height: 84rpx;
    padding: 14rpx 60rpx 14rpx 22rpx;
    border-radius: 20rpx;
    background: #ffffff;
    border: 2rpx solid #e9edf4;
}

.rule-multi-picker-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    width: 100%;
}

.rule-multi-picker-tag {
    display: flex;
    align-items: center;
    gap: 8rpx;
    max-width: 100%;
    padding: 8rpx 14rpx;
    border-radius: 12rpx;
    background: #eff5ff;
    border: 2rpx solid #d6e4ff;
}

.rule-multi-picker-tag-text {
    font-size: 24rpx;
    font-weight: 600;
    color: #2f73f6;
    max-width: 360rpx;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.rule-multi-picker-tag-close {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    padding: 2rpx;
}

.rule-multi-picker-ph {
    font-size: 26rpx;
    color: #c0c4cc;
}

.rule-multi-picker-arrow {
    position: absolute;
    right: 22rpx;
    top: 22rpx;
}
</style>
