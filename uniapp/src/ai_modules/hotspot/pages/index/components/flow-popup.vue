<template>
    <!-- mask 不可关：面板内是已付费的搜索/分析结果，误触蒙层不应直接丢弃 -->
    <popup-bottom
        v-model="show"
        height="85%"
        custom-class="bg-[#F3F5FA]"
        :clearable="false"
        :mask-close-able="false"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-[#F3F5FA] px-[32rpx] pt-[20rpx] pb-[16rpx]">
                <view class="w-[72rpx] h-[8rpx] mx-auto mb-[20rpx] bg-[#E5E7EB] rounded-full" />
                <view class="flex items-start justify-between gap-[24rpx]">
                    <view class="flex-1 min-w-0">
                        <text class="block text-[34rpx] font-bold text-[#111827] leading-snug line-clamp-2">
                            {{ topic?.title || "—" }}
                        </text>
                        <text class="block text-xs text-[#9CA3AF] mt-[8rpx]">{{ subTitle }}</text>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#E9EBF0] flex items-center justify-center flex-shrink-0"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#8B9199" :size="18"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[32rpx] pb-[600rpx]">
                            <!-- 已还原上次的分析记录（含停留步骤的设置与文案） -->
                            <view
                                v-if="fromCache"
                                class="flex items-center justify-between gap-[16rpx] px-[20rpx] py-[14rpx] rounded-[16rpx] bg-[#EFF6FF] mt-[16rpx]">
                                <text class="flex-1 min-w-0 text-[22rpx] text-primary line-clamp-1">
                                    已载入上次的分析记录{{ cachedAt ? " · " + formatCachedAt(cachedAt) : "" }}
                                </text>
                                <text class="flex-shrink-0 text-[22rpx] font-bold text-primary" @click="reResearch">
                                    重新分析
                                </text>
                            </view>

                            <!-- 步骤 1 AI 联网搜索 -->
                            <view class="flex items-center gap-[12rpx] mt-[24rpx] mb-[16rpx]">
                                <view class="step-no bg-primary"><text class="text-white">1</text></view>
                                <text class="text-[29rpx] font-bold text-[#111827]">AI 联网搜索</text>
                            </view>
                            <view v-if="researchErr" class="err-box">
                                <view class="flex-shrink-0 mt-[4rpx]">
                                    <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                                </view>
                                <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ researchErr }}</text>
                            </view>
                            <view v-else-if="!research" class="flow-card flex items-center gap-[20rpx]">
                                <u-loading mode="circle" size="40" color="#0065fb"></u-loading>
                                <view>
                                    <text class="block text-sm font-semibold text-primary">正在联网搜索这个热点…</text>
                                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                        AI 正在抓取最新报道与讨论
                                    </text>
                                </view>
                            </view>
                            <template v-else>
                                <view class="flow-card">
                                    <template v-if="research.core_points?.length">
                                        <view
                                            v-for="(point, pi) in research.core_points"
                                            :key="pi"
                                            class="flex gap-[18rpx] py-[14rpx]"
                                            :class="
                                                pi ? 'border-[0] border-t-[2rpx] border-solid border-[#F9FAFB]' : ''
                                            ">
                                            <view
                                                class="w-[32rpx] h-[32rpx] rounded-[8rpx] bg-[#EFF6FF] flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                                                <text class="text-[20rpx] font-bold text-primary">{{ Number(pi) + 1 }}</text>
                                            </view>
                                            <view class="flex-1 min-w-0">
                                                <text class="block text-[26rpx] font-semibold text-[#111827]">
                                                    {{ point.label }}
                                                </text>
                                                <text
                                                    class="block text-[24rpx] text-[#6B7280] leading-relaxed mt-[4rpx] break-all">
                                                    {{ point.detail }}
                                                </text>
                                            </view>
                                        </view>
                                    </template>
                                    <text v-else class="text-[25rpx] text-[#6B7280] leading-relaxed break-all">
                                        {{ (research.summary || "").slice(0, 300) }}
                                    </text>
                                </view>
                                <view v-if="research.citations?.length" class="flow-card mt-[16rpx]">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mb-[8rpx]">
                                        信息来源 · {{ research.citations.length }}
                                    </text>
                                    <view
                                        v-for="(cite, ci) in research.citations.slice(0, 5)"
                                        :key="ci"
                                        class="flex items-center gap-[14rpx] py-[10rpx]">
                                        <view
                                            class="w-[28rpx] h-[28rpx] rounded-[6rpx] bg-[#F3F4F6] flex-shrink-0 overflow-hidden">
                                            <image
                                                v-if="cite.logo_url"
                                                :src="cite.logo_url"
                                                mode="aspectFill"
                                                class="w-full h-full" />
                                        </view>
                                        <text class="flex-1 min-w-0 text-[22rpx] text-[#6B7280] line-clamp-1">
                                            {{ cite.title || cite.site_name || cite.url }}
                                        </text>
                                        <text
                                            v-if="cite.publish_time"
                                            class="text-[20rpx] text-[#D1D5DB] flex-shrink-0">
                                            {{ String(cite.publish_time).slice(0, 10) }}
                                        </text>
                                    </view>
                                </view>
                            </template>

                            <!-- 平台热度洞察（抖音） -->
                            <view v-if="insight" class="flow-card mt-[16rpx]">
                                <view class="flex items-center justify-between">
                                    <text class="text-[26rpx] font-bold text-[#111827]">平台热度洞察</text>
                                    <text class="text-[20rpx] text-[#D1D5DB]">来自抖音热点数据</text>
                                </view>
                                <view v-if="trendBars.length" class="mt-[16rpx]">
                                    <view class="flex items-end gap-[4rpx] h-[88rpx]">
                                        <view
                                            v-for="(bar, bi) in trendBars"
                                            :key="bi"
                                            class="flex-1 rounded-t-[4rpx] bg-[#BFDBFE]"
                                            :style="`height:${bar}%`"></view>
                                    </view>
                                    <view class="flex justify-between mt-[6rpx]">
                                        <text class="text-[20rpx] text-[#D1D5DB]">{{ trendRange.start }}</text>
                                        <text class="text-[20rpx] text-[#D1D5DB]">热度趋势</text>
                                        <text class="text-[20rpx] text-[#D1D5DB]">{{ trendRange.end }}</text>
                                    </view>
                                </view>
                                <view
                                    v-if="insight.gender?.length"
                                    class="mt-[20rpx] pt-[20rpx] border-[0] border-t-[2rpx] border-solid border-[#F3F4F8]">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mb-[12rpx]">人群画像</text>
                                    <view class="flex items-center gap-[14rpx]">
                                        <text class="text-[22rpx] text-primary font-semibold flex-shrink-0">
                                            男 {{ malePercent }}%
                                        </text>
                                        <view class="flex-1 h-[14rpx] rounded-full overflow-hidden flex">
                                            <view :style="`width:${malePercent}%;background:#3B82F6`"></view>
                                            <view class="flex-1" style="background: #f472b6"></view>
                                        </view>
                                        <text class="text-[22rpx] font-semibold flex-shrink-0" style="color: #ec4899">
                                            女 {{ 100 - malePercent }}%
                                        </text>
                                    </view>
                                    <view class="flex items-center gap-[10rpx] mt-[14rpx] flex-wrap">
                                        <text
                                            v-for="age in ageTop"
                                            :key="'age-' + age.name"
                                            class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx]">
                                            {{ age.name }}岁 {{ toPercent(age.value) }}%
                                        </text>
                                        <text
                                            v-for="prov in provinceTop"
                                            :key="'prov-' + prov.name"
                                            class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                            {{ prov.name }}
                                        </text>
                                    </view>
                                </view>
                                <view
                                    v-if="insight.videos?.length"
                                    class="mt-[20rpx] pt-[20rpx] border-[0] border-t-[2rpx] border-solid border-[#F3F4F8]">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mb-[12rpx]">
                                        大家都怎么做这个热点 · {{ insight.videos.length }}
                                    </text>
                                    <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                                        <view class="inline-flex gap-[16rpx] pb-[6rpx]">
                                            <view
                                                v-for="(video, vi) in insight.videos.slice(0, 8)"
                                                :key="vi"
                                                class="w-[172rpx] flex-shrink-0"
                                                @click="previewInsightVideo(video)">
                                                <view
                                                    class="rounded-[14rpx] overflow-hidden bg-[#F3F4F6]"
                                                    style="height: 228rpx">
                                                    <image
                                                        v-if="video.cover"
                                                        :src="video.cover"
                                                        mode="aspectFill"
                                                        class="w-full h-full"
                                                        lazy-load />
                                                </view>
                                                <text
                                                    class="block text-[20rpx] text-[#4B5563] leading-snug mt-[6rpx] line-clamp-2">
                                                    {{ video.title }}
                                                </text>
                                                <text class="block text-[20rpx] text-[#D1D5DB] mt-[2rpx]">
                                                    ❤ {{ formatDigg(video.digg_cnt) }}
                                                </text>
                                            </view>
                                        </view>
                                    </scroll-view>
                                </view>
                            </view>
                            <view v-else-if="insightBusy" class="flow-card mt-[16rpx] flex items-center gap-[16rpx]">
                                <u-loading mode="circle" size="28" color="#60A5FA"></u-loading>
                                <text class="text-[24rpx] text-[#9CA3AF]">正在拉取平台热度洞察…</text>
                            </view>

                            <!-- 步骤 2 选择人设（可不选：不选人设时跳过契合分析，形象/音色改到高级设置里自选） -->
                            <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                                <view class="step-no" :class="research ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                    <text class="text-white">2</text>
                                </view>
                                <text class="text-[29rpx] font-bold text-[#111827]">选择人设</text>
                            </view>
                            <view v-if="!research" class="flow-card">
                                <text class="text-[25rpx] text-[#D1D5DB]">搜索完成后可选</text>
                            </view>
                            <template v-else>
                                <view class="inline-flex items-center bg-[#E9EBF0] rounded-full p-[6rpx] mb-[16rpx]">
                                    <text
                                        v-for="mode in personaModeOptions"
                                        :key="mode.val"
                                        class="px-[22rpx] py-[12rpx] rounded-full text-[22rpx]"
                                        :class="
                                            opt.persona_mode === mode.val
                                                ? 'bg-white text-primary font-semibold'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="switchPersonaMode(mode.val)">
                                        {{ mode.label }}
                                    </text>
                                </view>

                                <view v-if="isManualMode" class="flow-card flex items-start gap-[12rpx]">
                                    <view class="flex-shrink-0 mt-[2rpx]">
                                        <u-icon name="info-circle" color="#2563EB" :size="26"></u-icon>
                                    </view>
                                    <text class="flex-1 text-[24rpx] text-[#2563EB] leading-relaxed">
                                        不选人设：跳过「热点 × 人设」契合分析，AI
                                        按热点用中立口吻写口播；数字人形象与音色在下方高级设置里自选，画面素材固定由 AI
                                        找。
                                    </text>
                                </view>
                                <template v-else>
                                    <view v-if="!personas.length" class="flow-card">
                                        <text class="text-[25rpx] text-[#9CA3AF]">
                                            还没有可用人设，请先到「AI 数字员工」创建人设
                                        </text>
                                    </view>
                                    <view v-else class="grid grid-cols-2 gap-[16rpx]">
                                        <view
                                            v-for="persona in personas"
                                            :key="persona.id"
                                            class="flow-card flex items-center gap-[18rpx] border-[3rpx] border-solid"
                                            :class="[
                                                personaId === persona.id
                                                    ? 'border-primary'
                                                    : 'border-[transparent]',
                                                persona.enabled === false ? 'opacity-50' : ''
                                            ]"
                                            @click="pickPersona(persona.id)">
                                            <image
                                                :src="persona.avatar"
                                                mode="aspectFill"
                                                class="w-[72rpx] h-[72rpx] rounded-[14rpx] bg-[#F3F4F6] flex-shrink-0" />
                                            <view class="flex-1 min-w-0">
                                                <text class="block text-[26rpx] font-bold text-[#111827] line-clamp-1">
                                                    {{ persona.name }}
                                                </text>
                                                <!-- 冻结的人设照样列出来，但要说清楚，选中也会被拦 -->
                                                <text
                                                    v-if="persona.enabled === false"
                                                    class="block text-[20rpx] text-[#CC7A00] mt-[4rpx] line-clamp-1">
                                                    已冻结 · 恢复会员后可用
                                                </text>
                                                <text
                                                    v-else
                                                    class="block text-[20rpx] text-[#9CA3AF] mt-[4rpx] line-clamp-1">
                                                    {{ persona.tag || "" }}
                                                </text>
                                            </view>
                                        </view>
                                    </view>

                                    <view v-if="analyzing" class="flow-card mt-[16rpx] flex items-center gap-[20rpx]">
                                        <u-loading mode="circle" size="40" color="#0065fb"></u-loading>
                                        <view>
                                            <text class="block text-sm font-semibold text-primary">
                                                正在分析热点与人设的结合点…
                                            </text>
                                            <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                                判断契合度、切入方式和风险
                                            </text>
                                        </view>
                                    </view>
                                    <view v-else-if="analyzeErr" class="err-box mt-[16rpx]">
                                        <view class="flex-shrink-0 mt-[2rpx]">
                                            <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                                        </view>
                                        <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ analyzeErr }}</text>
                                    </view>
                                    <view v-else-if="analysis" class="flow-card mt-[16rpx]">
                                        <view class="flex items-center justify-between">
                                            <text class="text-[26rpx] font-bold text-[#111827]">热点 × 人设 结合分析</text>
                                            <text class="text-[24rpx] font-bold" :class="fitTextClass">
                                                {{ fitLevelText }} {{ analysis.fit_score }}
                                            </text>
                                        </view>
                                        <view class="h-[12rpx] bg-[#F3F4F6] rounded-full mt-[14rpx] overflow-hidden">
                                            <view
                                                class="h-full rounded-full"
                                                :class="fitBarClass"
                                                :style="`width:${analysis.fit_score}%`"></view>
                                        </view>
                                        <text
                                            v-if="analysis.fit_reason"
                                            class="block text-[24rpx] text-[#6B7280] leading-relaxed mt-[16rpx] break-all">
                                            {{ analysis.fit_reason }}
                                        </text>
                                        <template v-if="analysis.hooks?.length">
                                            <text class="block text-[22rpx] font-bold text-[#9CA3AF] mt-[22rpx] mb-[10rpx]">
                                                可以这么切
                                            </text>
                                            <view
                                                v-for="(hook, hi) in analysis.hooks"
                                                :key="hi"
                                                class="bg-[#F9FAFB] rounded-[16rpx] p-[18rpx] mb-[10rpx]">
                                                <text class="block text-[25rpx] font-semibold text-primary">
                                                    {{ hook.label }}
                                                </text>
                                                <text
                                                    class="block text-[24rpx] text-[#6B7280] leading-relaxed mt-[6rpx] break-all">
                                                    {{ hook.detail }}
                                                </text>
                                            </view>
                                        </template>
                                        <view
                                            v-if="analysis.risks?.length"
                                            class="flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] mt-[12rpx] bg-[#FFFBEB] border-[2rpx] border-solid border-[#FDE68A]">
                                            <u-icon
                                                name="warning"
                                                color="#F59E0B"
                                                :size="26"
                                                class="flex-shrink-0"></u-icon>
                                            <view class="flex-1 min-w-0">
                                                <text
                                                    v-for="(risk, ri) in analysis.risks"
                                                    :key="ri"
                                                    class="block text-xs text-[#D97706] leading-relaxed break-all">
                                                    {{ risk }}
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                </template>
                            </template>

                            <!-- 步骤 3 高级设置 -->
                            <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                                <view class="step-no" :class="stepReady ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                    <text class="text-white">3</text>
                                </view>
                                <text class="text-[29rpx] font-bold text-[#111827]">高级设置</text>
                                <view
                                    v-if="stepReady"
                                    class="ml-auto flex items-center gap-[6rpx] bg-[#E9EBF0] rounded-full px-[18rpx] py-[6rpx]"
                                    @click="showAdv = !showAdv">
                                    <text class="text-[20rpx] text-[#6B7280]">{{ showAdv ? "收起" : "展开" }}</text>
                                    <u-icon
                                        :name="showAdv ? 'arrow-up' : 'arrow-down'"
                                        color="#6B7280"
                                        :size="20"></u-icon>
                                </view>
                            </view>
                            <view v-if="!stepReady" class="flow-card">
                                <text class="text-[25rpx] text-[#D1D5DB]">
                                    {{ isManualMode ? "搜索完成后可配置" : "选好人设、分析完成后可配置" }}
                                </text>
                            </view>
                            <view
                                v-else-if="!showAdv"
                                class="flow-card flex items-center gap-[10rpx] flex-wrap"
                                @click="showAdv = true">
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx] font-semibold">
                                    {{ goalLabel(opt.goal) }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F5F3FF] text-[#7C3AED] text-[20rpx] font-semibold">
                                    {{ videoTypeLabel(opt.video_type)
                                    }}{{ opt.video_type === "digital" && opt.avatar ? " · " + opt.avatar : "" }}
                                </text>
                                <text
                                    v-if="isManualMode && opt.voice"
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#ECFDF5] text-[#059669] text-[20rpx] font-semibold">
                                    音色 · {{ opt.voice }}
                                </text>
                                <!-- 音量/语速只在改过默认值时才占位，避免收起态标签过长 -->
                                <text
                                    v-if="isManualMode && opt.music_volume !== HOTSPOT_MUSIC_VOLUME_DEFAULT"
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    音量 {{ musicVolumeText }}
                                </text>
                                <text
                                    v-if="isManualMode && opt.speech_rate !== HOTSPOT_SPEECH_RATE_DEFAULT"
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    语速 {{ opt.speech_rate.toFixed(1) }}x
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    {{ opt.direction }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    {{ materialLabel(opt.material_mode) }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    {{ opt.duration_sec }} 秒
                                </text>
                                <text
                                    v-if="submittedMaterials.length"
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    素材 {{ submittedMaterials.length }}
                                </text>
                            </view>
                            <view v-else class="flow-card">
                                <text class="adv-label">视频类型</text>
                                <view class="grid grid-cols-2 gap-[16rpx]">
                                    <view
                                        v-for="vt in videoTypeOptions"
                                        :key="vt.key"
                                        class="rounded-[16rpx] border-[3rpx] border-solid p-[20rpx]"
                                        :class="
                                            opt.video_type === vt.key
                                                ? 'border-primary bg-[#EFF6FF]'
                                                : 'border-[#F3F4F6] bg-[#F9FAFB]'
                                        "
                                        @click="opt.video_type = vt.key">
                                        <view class="flex items-center gap-[10rpx]">
                                            <image :src="vt.icon" mode="aspectFit" class="w-[30rpx] h-[30rpx]" />
                                            <text
                                                class="text-[25rpx] font-bold"
                                                :class="opt.video_type === vt.key ? 'text-primary' : 'text-[#6B7280]'">
                                                {{ vt.label }}
                                            </text>
                                        </view>
                                        <text
                                            class="block text-[20rpx] mt-[8rpx] leading-snug"
                                            :class="opt.video_type === vt.key ? 'text-[#60A5FA]' : 'text-[#9CA3AF]'">
                                            {{ vt.desc }}
                                        </text>
                                    </view>
                                </view>

                                <template v-if="opt.video_type === 'digital'">
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">数字人形象</text>
                                        <text
                                            class="text-[18rpx] font-semibold text-[#EF4444] bg-[#FEF2F2] px-[10rpx] py-[2rpx] rounded-[6rpx]">
                                            必选
                                        </text>
                                    </view>
                                    <view v-if="assetLoading" class="flex items-center gap-[12rpx] py-[16rpx]">
                                        <u-loading mode="circle" size="26" color="#0065fb"></u-loading>
                                        <text class="text-[22rpx] text-[#9CA3AF]">加载形象中…</text>
                                    </view>
                                    <text v-else-if="!avatarList.length" class="text-[22rpx] text-[#9CA3AF]">
                                        {{
                                            isManualMode
                                                ? "暂无可用数字人形象，请先在「数字人克隆」中创建形象"
                                                : "该人设暂无可用数字人形象，请先到人设中心创建"
                                        }}
                                    </text>
                                    <scroll-view v-else scroll-x class="w-full" :show-scrollbar="false">
                                        <view class="inline-flex gap-[16rpx] pb-[6rpx]">
                                            <view
                                                v-for="avatar in avatarList"
                                                :key="avatar.id"
                                                class="w-[144rpx] flex-shrink-0 rounded-[16rpx] border-[3rpx] border-solid p-[8rpx]"
                                                :class="
                                                    opt.avatar_id === avatar.id
                                                        ? 'border-primary bg-[#EFF6FF]'
                                                        : 'border-[#F3F4F6]'
                                                "
                                                @click="pickAvatar(avatar)">
                                                <image
                                                    :src="avatar.img"
                                                    mode="aspectFill"
                                                    class="w-full h-[128rpx] rounded-[12rpx] bg-[#F3F4F6]" />
                                                <text
                                                    class="block text-[22rpx] mt-[6rpx] text-center line-clamp-1"
                                                    :class="
                                                        opt.avatar_id === avatar.id
                                                            ? 'text-primary font-bold'
                                                            : 'text-[#6B7280]'
                                                    ">
                                                    {{ avatar.name }}
                                                </text>
                                            </view>
                                        </view>
                                    </scroll-view>
                                </template>

                                <!-- 不选人设：音色不再随形象捆绑，需单独选（素材混剪同样要音色配音） -->
                                <template v-if="isManualMode">
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">音色</text>
                                        <text
                                            class="text-[18rpx] font-semibold text-[#EF4444] bg-[#FEF2F2] px-[10rpx] py-[2rpx] rounded-[6rpx]">
                                            必选
                                        </text>
                                    </view>
                                    <view v-if="assetLoading" class="flex items-center gap-[12rpx] py-[16rpx]">
                                        <u-loading mode="circle" size="26" color="#0065fb"></u-loading>
                                        <text class="text-[22rpx] text-[#9CA3AF]">加载音色中…</text>
                                    </view>
                                    <text v-else-if="!voiceOptions.length" class="text-[22rpx] text-[#9CA3AF]">
                                        {{ voiceEmptyTip }}
                                    </text>
                                    <template v-else>
                                        <view
                                            v-for="voice in visibleVoices"
                                            :key="voice.id"
                                            class="flex items-center gap-[14rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] mb-[12rpx] border-[2rpx] border-solid"
                                            :class="
                                                opt.voice_id === voice.id
                                                    ? 'bg-[#EFF6FF] border-primary'
                                                    : 'bg-[#F9FAFB] border-[transparent]'
                                            "
                                            @click="pickVoice(voice)">
                                            <text
                                                class="flex-1 min-w-0 text-[24rpx] font-semibold line-clamp-1"
                                                :class="opt.voice_id === voice.id ? 'text-primary' : 'text-[#374151]'">
                                                {{ voice.name || "未命名音色" }}
                                            </text>
                                            <text
                                                v-if="voice.id === HOTSPOT_ORIGIN_VOICE_ID"
                                                class="flex-shrink-0 text-[18rpx] font-semibold text-[#D97706] bg-[#FFFBEB] px-[10rpx] py-[2rpx] rounded-[6rpx]">
                                                推荐
                                            </text>
                                            <u-icon
                                                v-if="opt.voice_id === voice.id"
                                                name="checkmark-circle-fill"
                                                color="#2563EB"
                                                :size="30"></u-icon>
                                        </view>
                                        <view
                                            v-if="hasMoreVoices"
                                            class="flex items-center justify-center gap-[8rpx] py-[12rpx]"
                                            @click="voiceShowCount += VOICE_PAGE_SIZE">
                                            <text class="text-[22rpx] text-primary">
                                                加载更多音色（还有 {{ voiceOptions.length - voiceShowCount }} 个）
                                            </text>
                                            <u-icon name="arrow-down" color="#0065fb" :size="20"></u-icon>
                                        </view>
                                    </template>

                                    <!-- 视频模板：不选人设没有人设合成配置可依，剪辑风格改由用户当次自选 -->
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">视频模板</text>
                                        <text class="text-[20rpx] text-[#D1D5DB]">选填 · 点播放键看样片</text>
                                    </view>
                                    <!-- key 绑视频类型：两种类型是两套模板，不换 key 的话
                                         切过去还停在上一套滑到的位置，看不到打头的「自动随机」 -->
                                    <scroll-view :key="opt.video_type" scroll-x class="w-full whitespace-nowrap">
                                        <view class="inline-flex gap-[16rpx] pb-[8rpx]">
                                            <!-- 自动随机放首位，等价于不指定模板 -->
                                            <view
                                                class="w-[228rpx] rounded-[16rpx] overflow-hidden bg-white border-[3rpx] border-solid"
                                                :class="!opt.clip_template_id ? 'border-primary' : 'border-[#EDEFF3]'"
                                                @click="opt.clip_template_id = ''">
                                                <view
                                                    class="w-full h-[304rpx] bg-[#F3F4F6] flex flex-col items-center justify-center">
                                                    <u-icon
                                                        name="reload"
                                                        :color="!opt.clip_template_id ? '#0065fb' : '#9CA3AF'"
                                                        :size="44"></u-icon>
                                                    <text class="text-[20rpx] text-[#9CA3AF] mt-[12rpx]">
                                                        每次随机挑一个
                                                    </text>
                                                </view>
                                                <text
                                                    class="block text-[22rpx] text-center py-[12rpx] line-clamp-1 px-[8rpx]"
                                                    :class="
                                                        !opt.clip_template_id
                                                            ? 'text-primary font-semibold'
                                                            : 'text-[#6B7280]'
                                                    ">
                                                    自动随机
                                                </text>
                                            </view>
                                            <view
                                                v-for="template in clipTemplates"
                                                :key="template.id"
                                                class="w-[228rpx] rounded-[16rpx] overflow-hidden bg-white border-[3rpx] border-solid"
                                                :class="
                                                    opt.clip_template_id === template.id
                                                        ? 'border-primary'
                                                        : 'border-[#EDEFF3]'
                                                "
                                                @click="pickClipTemplate(template.id)">
                                                <view class="relative w-full h-[304rpx] bg-[#F3F4F6]">
                                                    <image
                                                        v-if="template.cover_url"
                                                        :src="template.cover_url"
                                                        mode="aspectFill"
                                                        lazy-load
                                                        class="w-full h-full" />
                                                    <!-- 封面只看得出配色看不出剪辑风格，播放键单独拦一下点击去放样片 -->
                                                    <view
                                                        v-if="template.demo_url"
                                                        class="absolute inset-0 flex items-center justify-center"
                                                        @click.stop="previewClipTemplate(template)">
                                                        <view
                                                            class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center"
                                                            style="background: rgba(0, 0, 0, 0.45)">
                                                            <u-icon
                                                                name="play-right-fill"
                                                                color="#ffffff"
                                                                :size="30"
                                                                class="ml-[4rpx]"></u-icon>
                                                        </view>
                                                    </view>
                                                    <view
                                                        v-if="opt.clip_template_id === template.id"
                                                        class="absolute top-[8rpx] right-[8rpx] w-[36rpx] h-[36rpx] rounded-full bg-primary flex items-center justify-center">
                                                        <u-icon name="checkmark" color="#ffffff" :size="20"></u-icon>
                                                    </view>
                                                </view>
                                                <text
                                                    class="block text-[22rpx] text-center py-[12rpx] line-clamp-1 px-[8rpx]"
                                                    :class="
                                                        opt.clip_template_id === template.id
                                                            ? 'text-primary font-semibold'
                                                            : 'text-[#6B7280]'
                                                    ">
                                                    {{ template.name || "未命名模板" }}
                                                </text>
                                            </view>
                                        </view>
                                    </scroll-view>
                                    <text v-if="!clipTemplates.length" class="text-[22rpx] text-[#9CA3AF]">
                                        当前视频类型暂无可选模板，将自动随机
                                    </text>
                                </template>

                                <text class="adv-label mt-[28rpx]">素材来源</text>
                                <!-- 不选人设时本地素材库按人设归属查不到，来源锁死 AI 找素材，不给切换入口 -->
                                <view
                                    v-if="isManualMode"
                                    class="inline-flex items-center gap-[10rpx] px-[22rpx] py-[12rpx] rounded-full bg-[#EFF6FF]">
                                    <text class="text-[22rpx] text-primary font-semibold">AI找素材</text>
                                    <text class="text-[20rpx] text-[#9CA3AF]">不选人设时固定由 AI 找画面素材</text>
                                </view>
                                <view v-else class="flex items-center bg-[#F3F4F6] rounded-full p-[6rpx]">
                                    <text
                                        v-for="material in optionsData.materials"
                                        :key="material.key"
                                        class="flex-1 py-[12rpx] rounded-full text-[22rpx] text-center line-clamp-1 break-all"
                                        :class="
                                            opt.material_mode === material.key
                                                ? 'bg-white text-primary font-semibold'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="opt.material_mode = material.key">
                                        {{ material.label }}
                                    </text>
                                </view>
                                <view
                                    v-if="opt.material_mode !== 'persona'"
                                    class="inline-flex items-center gap-[10rpx] mt-[16rpx] px-[20rpx] py-[10rpx] rounded-full border-[2rpx] border-solid border-[#FDE68A] bg-[#FFFBEB]">
                                    <u-icon name="warning" color="#F59E0B" :size="24"></u-icon>
                                    <text class="text-xs text-[#D97706]">需额外消耗算力</text>
                                </view>

                                <template v-if="opt.material_mode !== 'ai'">
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx] flex-wrap">
                                        <text class="text-[24rpx] font-bold text-[#374151]">混剪素材</text>
                                        <text
                                            v-if="materialRequired"
                                            class="text-[18rpx] font-semibold text-[#EF4444] bg-[#FEF2F2] px-[10rpx] py-[2rpx] rounded-[6rpx]">
                                            必选
                                        </text>
                                        <text v-else class="text-[18rpx] text-[#D1D5DB]">选填 · 口播时穿插展示</text>
                                        <text class="text-[18rpx] text-[#D1D5DB]">
                                            限{{ montageConfig.materialTotalDuration }}分钟
                                        </text>
                                        <text
                                            v-if="opt.materials.length"
                                            class="text-[18rpx] font-semibold"
                                            :class="
                                                selectedMaterialBudget > MATERIAL_DURATION_LIMIT
                                                    ? 'text-[#EF4444]'
                                                    : 'text-primary'
                                            ">
                                            已选 {{ opt.materials.length }} · {{ selectedMaterialDuration }}/{{
                                                MATERIAL_DURATION_LIMIT
                                            }}秒
                                        </text>
                                    </view>
                                    <view v-if="materialLoading" class="flex items-center gap-[12rpx] py-[16rpx]">
                                        <u-loading mode="circle" size="26" color="#0065fb"></u-loading>
                                        <text class="text-[22rpx] text-[#9CA3AF]">加载素材中…</text>
                                    </view>
                                    <text v-else-if="!materialList.length" class="text-[22rpx] text-[#9CA3AF]">
                                        该人设暂无可用素材，可在人设素材库上传
                                    </text>
                                    <template v-else>
                                        <view class="grid grid-cols-3 gap-[16rpx]">
                                            <view
                                                v-for="material in materialList"
                                                :key="material.id"
                                                class="relative rounded-[16rpx] overflow-hidden border-[3rpx] border-solid"
                                                :class="
                                                    opt.materials.includes(material.id)
                                                        ? 'border-primary'
                                                        : 'border-[transparent]'
                                                "
                                                @click="toggleMaterial(material.id)">
                                                <view class="relative w-full h-[112rpx] bg-[#F3F4F6]">
                                                    <image
                                                        :src="material.img"
                                                        mode="aspectFill"
                                                        lazy-load
                                                        class="w-full h-full" />
                                                    <view
                                                        class="absolute top-[6rpx] left-[6rpx] px-[8rpx] py-[2rpx] rounded-[6rpx]"
                                                        style="background: rgba(0, 0, 0, 0.55)">
                                                        <text class="text-[16rpx] text-white leading-none">
                                                            {{ material.material_type === 1 ? "视频" : "图片" }}
                                                        </text>
                                                    </view>
                                                    <view
                                                        v-if="material.material_type === 1"
                                                        class="absolute inset-0 flex items-center justify-center">
                                                        <view
                                                            class="w-[36rpx] h-[36rpx] rounded-full bg-[#ffffff]/30 border border-solid border-[#ffffff]/40 flex items-center justify-center">
                                                            <u-icon
                                                                name="play-right-fill"
                                                                color="#ffffff"
                                                                :size="14"
                                                                class="ml-[2rpx]"></u-icon>
                                                        </view>
                                                    </view>
                                                </view>
                                                <view
                                                    v-if="opt.materials.includes(material.id)"
                                                    class="absolute top-[8rpx] right-[8rpx] w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center">
                                                    <u-icon name="checkmark" color="#ffffff" :size="18"></u-icon>
                                                </view>
                                                <view class="bg-white py-[8rpx]">
                                                    <text
                                                        class="block text-[20rpx] text-center line-clamp-1 px-[8rpx]"
                                                        :class="
                                                            opt.materials.includes(material.id)
                                                                ? 'text-primary font-semibold'
                                                                : 'text-[#6B7280]'
                                                        ">
                                                        {{ material.name }}
                                                    </text>
                                                </view>
                                            </view>
                                        </view>
                                        <view
                                            v-if="!materialFinished"
                                            class="mt-[16rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB] flex items-center justify-center gap-[10rpx] active:opacity-70"
                                            @click="loadMoreMaterials">
                                            <u-loading
                                                v-if="materialLoadingMore"
                                                mode="circle"
                                                size="24"
                                                color="#0065fb"></u-loading>
                                            <text
                                                class="text-[22rpx]"
                                                :class="materialLoadingMore ? 'text-[#9CA3AF]' : 'text-primary'">
                                                {{ materialLoadingMore ? "加载中…" : "加载更多素材" }}
                                            </text>
                                            <u-icon
                                                v-if="!materialLoadingMore"
                                                name="arrow-down"
                                                color="#0065fb"
                                                :size="22"></u-icon>
                                        </view>
                                        <view
                                            v-else-if="materialPage > 1"
                                            class="mt-[16rpx] flex items-center justify-center">
                                            <text class="text-[20rpx] text-[#C4C9D4]">已加载全部素材</text>
                                        </view>
                                    </template>
                                </template>

                                <text class="adv-label mt-[28rpx]">最终目的</text>
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <text
                                        v-for="goal in optionsData.goals"
                                        :key="goal.key"
                                        class="px-[22rpx] py-[12rpx] rounded-[12rpx] text-[24rpx]"
                                        :class="
                                            opt.goal === goal.key
                                                ? 'bg-primary text-white font-semibold'
                                                : 'bg-[#F3F4F6] text-[#6B7280]'
                                        "
                                        @click="opt.goal = goal.key">
                                        {{ goal.label }}
                                    </text>
                                </view>
                                <text
                                    v-if="analysis?.recommended_goal"
                                    class="block text-[20rpx] text-[#9CA3AF] mt-[10rpx]">
                                    AI 建议：{{ goalLabel(analysis.recommended_goal) }}
                                </text>
                                <view
                                    v-if="opt.goal === 'sell'"
                                    class="bg-[#F9FAFB] rounded-[16rpx] p-[20rpx] mt-[16rpx]">
                                    <input
                                        v-model="opt.product"
                                        :maxlength="100"
                                        placeholder="要推的产品 / 卖点（如：99元AI入门课）"
                                        placeholder-style="color:#9CA3AF"
                                        class="w-full text-[25rpx] text-[#1F2937]" />
                                </view>

                                <text class="adv-label mt-[28rpx]">内容方向</text>
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <text
                                        v-for="direction in optionsData.directions"
                                        :key="direction"
                                        class="px-[22rpx] py-[12rpx] rounded-[12rpx] text-[24rpx]"
                                        :class="
                                            opt.direction === direction
                                                ? 'bg-primary text-white font-semibold'
                                                : 'bg-[#F3F4F6] text-[#6B7280]'
                                        "
                                        @click="opt.direction = direction">
                                        {{ direction }}
                                    </text>
                                </view>

                                <text class="adv-label mt-[28rpx]">口播时长</text>
                                <view class="flex items-center bg-[#F3F4F6] rounded-full p-[6rpx]">
                                    <text
                                        v-for="duration in optionsData.durations"
                                        :key="duration"
                                        class="flex-1 py-[12rpx] rounded-full text-[22rpx] text-center"
                                        :class="
                                            opt.duration_sec === duration
                                                ? 'bg-white text-primary font-semibold'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="opt.duration_sec = duration">
                                        {{ duration }} 秒
                                    </text>
                                </view>

                                <!-- 不选人设没有人设合成配置可依，背景音乐/音量/语速改由用户当次自选（人设模式仍跟随人设设置） -->
                                <template v-if="isManualMode">
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">背景音乐</text>
                                        <text class="text-[20rpx] text-[#D1D5DB]">音量拖到 0 即不加背景音乐</text>
                                    </view>
                                    <view class="flex items-center gap-[14rpx]">
                                        <view
                                            v-for="source in HOTSPOT_MUSIC_SOURCE_OPTIONS"
                                            :key="source.val"
                                            class="flex-1 rounded-[16rpx] px-[16rpx] py-[18rpx] text-center border-[2rpx] border-solid"
                                            :class="
                                                opt.music_source === source.val
                                                    ? 'bg-[#EFF6FF] border-primary'
                                                    : 'bg-[#F9FAFB] border-[transparent]'
                                            "
                                            @click="pickMusicSource(source.val)">
                                            <text
                                                class="text-[23rpx] font-semibold"
                                                :class="opt.music_source === source.val ? 'text-primary' : 'text-[#6B7280]'">
                                                {{ source.label }}
                                            </text>
                                        </view>
                                    </view>
                                    <view
                                        v-if="opt.music_source === HotspotMusicSource.PICK"
                                        class="flex items-center gap-[14rpx] mt-[14rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB]">
                                        <text
                                            class="flex-1 min-w-0 text-[22rpx] line-clamp-1"
                                            :class="opt.system_music_id ? 'text-[#374151]' : 'text-[#9CA3AF]'">
                                            {{ opt.music_name || "还没有选择系统音乐" }}
                                        </text>
                                        <text class="flex-shrink-0 text-[22rpx] text-primary font-semibold" @click="showSystemMusic = true">
                                            {{ opt.system_music_id ? "重选" : "选择" }}
                                        </text>
                                    </view>
                                    <template v-if="opt.music_source === HotspotMusicSource.UPLOAD">
                                        <view
                                            class="flex items-center gap-[14rpx] mt-[14rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB]">
                                            <u-icon name="volume-up" color="#0065fb" :size="28"></u-icon>
                                            <text
                                                class="flex-1 min-w-0 text-[22rpx] line-clamp-1"
                                                :class="opt.music_url ? 'text-[#374151]' : 'text-[#9CA3AF]'">
                                                {{ opt.music_name || "还没有上传音乐" }}
                                            </text>
                                            <text
                                                class="flex-shrink-0 text-[22rpx] text-primary font-semibold"
                                                @click="handleUploadMusic">
                                                {{ opt.music_url ? "重新上传" : "上传" }}
                                            </text>
                                        </view>
                                        <text class="block text-[20rpx] text-[#9CA3AF] mt-[8rpx]">
                                            支持 mp3/wav/m4a/aac，微信端需先把音频文件发到聊天再选取
                                        </text>
                                    </template>

                                    <view class="flex items-center justify-between mt-[24rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">音乐音量</text>
                                        <text class="text-[22rpx] font-semibold text-primary">
                                            {{ musicVolumeText }}
                                        </text>
                                    </view>
                                    <slider
                                        :value="musicVolumeStep"
                                        :min="0"
                                        :max="10"
                                        :step="1"
                                        :block-size="20"
                                        activeColor="#0065fb"
                                        backgroundColor="#E5E9F0"
                                        @changing="onMusicVolumeChange"
                                        @change="onMusicVolumeChange" />
                                    <view class="flex justify-between px-[4rpx]">
                                        <text
                                            v-for="label in MUSIC_VOLUME_LABELS"
                                            :key="label"
                                            class="text-[20rpx] text-[#9CA3AF]">
                                            {{ label }}
                                        </text>
                                    </view>

                                    <view class="flex items-center justify-between mt-[24rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">口播语速</text>
                                        <text
                                            v-if="!speechRateDisabled"
                                            class="text-[22rpx] font-semibold text-primary">
                                            {{ opt.speech_rate.toFixed(1) }}x
                                        </text>
                                    </view>
                                    <!-- MiniMax 音色由预渲染音频驱动，闪剪不认 speedRatio，调了也无效，直接说明而不是给个假开关 -->
                                    <view
                                        v-if="speechRateDisabled"
                                        class="flex items-start gap-[10rpx] px-[20rpx] py-[14rpx] rounded-[16rpx] bg-[#F9FAFB]">
                                        <view class="flex-shrink-0 mt-[2rpx]">
                                            <u-icon name="info-circle" color="#9CA3AF" :size="24"></u-icon>
                                        </view>
                                        <text class="flex-1 text-[22rpx] text-[#9CA3AF] leading-relaxed">
                                            所选音色由 MiniMax 合成，语速跟随音色本身，暂不支持在这里调节
                                        </text>
                                    </view>
                                    <template v-else>
                                        <slider
                                            :value="speechRateStep"
                                            :min="5"
                                            :max="20"
                                            :step="1"
                                            :block-size="20"
                                            activeColor="#0065fb"
                                            backgroundColor="#E5E9F0"
                                            @changing="onSpeechRateChange"
                                            @change="onSpeechRateChange" />
                                        <view class="flex justify-between px-[4rpx]">
                                            <text
                                                v-for="label in SPEECH_RATE_LABELS"
                                                :key="label"
                                                class="text-[20rpx] text-[#9CA3AF]">
                                                {{ label }}
                                            </text>
                                        </view>
                                    </template>
                                </template>

                                <view class="flex items-center gap-[8rpx] mt-[28rpx] mb-[14rpx]">
                                    <text class="text-[24rpx] font-bold text-[#374151]">结尾引导语</text>
                                    <text class="text-[20rpx] text-[#D1D5DB]">选填</text>
                                </view>
                                <view class="bg-[#F9FAFB] rounded-[16rpx] p-[20rpx]">
                                    <input
                                        v-model="opt.cta"
                                        :maxlength="100"
                                        placeholder="留空则由 AI 按目的自动生成"
                                        placeholder-style="color:#9CA3AF"
                                        class="w-full text-[25rpx] text-[#1F2937]" />
                                </view>
                            </view>

                            <!-- 步骤 4 口播文案 -->
                            <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                                <view class="step-no" :class="showScript ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                    <text class="text-white">4</text>
                                </view>
                                <text class="text-[29rpx] font-bold text-[#111827]">口播文案</text>
                            </view>
                            <!-- 错误独立展示，不顶掉已生成的文案卡（旧文案仍可查看/提交） -->
                            <view v-if="scriptErr" class="err-box mb-[12rpx]">
                                <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                                <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ scriptErr }}</text>
                            </view>
                            <view v-if="scriptBusy && !script" class="flow-card flex items-center gap-[20rpx]">
                                <u-loading mode="circle" size="40" color="#0065fb"></u-loading>
                                <view>
                                    <text class="block text-sm font-semibold text-primary">正在生成口播文案…</text>
                                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                        按「{{ goalLabel(opt.goal) }}」的落点和人设口吻写
                                    </text>
                                </view>
                            </view>
                            <view v-else-if="!showScript" class="flow-card">
                                <text class="text-[25rpx] text-[#D1D5DB]">确认设置后点下方按钮生成</text>
                            </view>
                            <view v-else class="flow-card">
                                <view class="bg-[#F9FAFB] rounded-[16rpx] p-[20rpx]">
                                    <textarea
                                        v-model="editedScript"
                                        :maxlength="20000"
                                        placeholder="口播文案"
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        class="w-full text-[27rpx] text-[#1F2937] leading-relaxed" />
                                </view>
                                <view class="flex items-center justify-between mt-[14rpx]">
                                    <text class="text-[22rpx] text-[#9CA3AF]">
                                        {{ scriptWordCount }} 字 · 约 {{ scriptEstSec }} 秒
                                    </text>
                                    <!-- 重新生成时旧文案仍展示，busy 态靠按钮自身反馈 -->
                                    <view class="flex items-center gap-[8rpx]" @click="runScript">
                                        <u-loading
                                            v-if="scriptBusy"
                                            mode="circle"
                                            size="24"
                                            color="#0065fb"></u-loading>
                                        <text
                                            class="text-[24rpx] font-semibold"
                                            :class="scriptBusy ? 'text-[#9CA3AF]' : 'text-primary'">
                                            {{ scriptBusy ? "正在重新生成…" : "重新生成" }}
                                        </text>
                                    </view>
                                </view>
                                <view v-if="script.hashtags?.length" class="flex flex-wrap gap-[10rpx] mt-[16rpx]">
                                    <text
                                        v-for="tag in script.hashtags"
                                        :key="tag"
                                        class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx]">
                                        #{{ tag }}
                                    </text>
                                </view>
                                <template v-if="script.shots?.length">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mt-[22rpx] mb-[8rpx]">
                                        画面建议 · {{ materialLabel(script.material_mode || opt.material_mode) }}
                                    </text>
                                    <view
                                        v-for="(shot, si) in script.shots"
                                        :key="si"
                                        class="flex gap-[14rpx] py-[6rpx]">
                                        <text class="text-[20rpx] text-[#D1D5DB] font-bold flex-shrink-0 mt-[2rpx]">
                                            {{ Number(si) + 1 }}
                                        </text>
                                        <text class="flex-1 text-[24rpx] text-[#6B7280] leading-relaxed break-all">
                                            {{ shot }}
                                        </text>
                                    </view>
                                </template>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <!-- 底部：搜索完成后可入库；主按钮继续走分析/文案/出片 -->
                <view
                    class="px-[32rpx] pt-[12rpx] pb-[calc(16rpx+env(safe-area-inset-bottom))] bg-white border-[0] border-t-[2rpx] border-solid border-[#F3F4F6] flex items-center gap-[16rpx]">
                    <view
                        v-if="showStockBtn"
                        class="shrink-0 h-[72rpx] px-[24rpx] rounded-full bg-white border-[2rpx] border-solid border-[#E5E7EB] flex items-center justify-center gap-[8rpx]"
                        :class="stockBtnDisabled ? 'opacity-50' : 'active:opacity-80'"
                        @click="handleStock">
                        <image :src="ArchiveRestoreIcon" mode="aspectFit" class="w-[28rpx] h-[28rpx]" />
                        <text class="text-[24rpx] font-bold text-[#4B5563]">{{ stockBtnText }}</text>
                    </view>
                    <view
                        class="flow-cta grow min-w-0"
                        :class="mainBtn.disabled || mainBtn.gate ? 'bg-[#C3D4EE]' : 'cta-gradient'"
                        @click="handleMainAction">
                        <text
                            class="flow-cta-text"
                            :class="mainBtn.disabled || mainBtn.gate ? 'text-[#5C7299]' : 'text-white'">
                            {{ mainBtn.text }}
                        </text>
                        <view v-if="mainBtn.cost" class="flow-cta-cost">
                            <text class="text-[20rpx] leading-none text-white">{{ mainBtn.cost }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <!-- 模板样片：封面只看得出配色，看完样片再决定用不用（确认即选中该模板）。
         必须放在 popup-bottom 外面：u-popup 打开态带 transform，嵌在里面会让样片弹窗的
         position:fixed 相对面板定位（遮罩只盖住面板、内容位置错位） -->
    <video-preview
        v-model="showTemplatePreview"
        title="模板样片"
        confirm-btn-text="使用该模板"
        :video-url="previewTemplate?.demo_url || ''"
        :poster="previewTemplate?.cover_url || ''"
        @confirm="pickPreviewedTemplate" />
    <system-music-sheet v-model="showSystemMusic" :selected-id="opt.system_music_id" @confirm="onSystemMusic" />
</template>

<script setup lang="ts">
import {
    getHotspotAvatars,
    getHotspotClipMaterials,
    getHotspotGenerationOptions,
    hotspotAnalyze,
    hotspotResearch,
    hotspotScript,
    addHotspotTask,
    addHotspotStock,
    getHotspotStockDetail,
    getHotspotInsight,
    getHotspotLastFlow,
} from "@/api/hotspot";
import {
    HOTSPOT_MUSIC_SOURCE_OPTIONS,
    HOTSPOT_MUSIC_VOLUME_DEFAULT,
    HOTSPOT_ORIGIN_VOICE_ID,
    HOTSPOT_PERSONA_MODE_OPTIONS,
    HOTSPOT_SPEECH_RATE_DEFAULT,
    HotspotMusicSource,
    HotspotPersonaMode,
    HotspotPlatform,
    HotspotVideoType,
    platformLabel,
    type HotspotOptionsData,
} from "@/ai_modules/hotspot/enums";
import {
    FLOW_CACHE_KEY,
    FLOW_CACHE_LIMIT,
    flowCacheKey,
    readFlowCacheAll,
} from "@/ai_modules/hotspot/utils/flowCache";
import { montageConfig, calcMaterialDurationBudget } from "@/enums/montageEnums";
import useUpload from "@/hooks/useUpload";
import SystemMusicSheet from "@/components/system-music-sheet/system-music-sheet.vue";
import { useLockFn } from "@/hooks/useLockFn";
import IconDigitalBlue from "@/ai_modules/hotspot/static/icons/video_digital_blue.svg";
import IconClipsBlue from "@/ai_modules/hotspot/static/icons/video_clips_blue.svg";
import ArchiveRestoreIcon from "@/ai_modules/hotspot/static/icons/archive-restore-gray.svg";

interface HotTopic {
    title: string;
    platform: string;
    category?: string;
    rank?: number;
    heat_text?: string;
    days_on_board?: number;
    rank_diff?: number;
    /** 后端按分析记录或已建任务标记，已分析的热点可从服务端还原上次现场 */
    analyzed?: boolean;
    analysis_id?: number;
}

const props = defineProps<{
    modelValue: boolean;
    topic: HotTopic | null;
    periodLabel: string;
    personas: Record<string, any>[];
    optionsData: HotspotOptionsData;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "created", task: Record<string, any>, navigate: boolean): void;
    (e: "analyzed", topic: HotTopic): void;
}>();

const emitAnalyzed = () => {
    if (props.topic) emit("analyzed", props.topic);
};

/** 分析记录持久缓存：点开过的热点把搜索/洞察/分析/设置/文案整体存下来，
 *  再进直接还原到上次停留步骤（跨页面、跨启动有效），不重跑收费的搜索/分析 */
const cacheKey = (topic: HotTopic | null) => (topic ? flowCacheKey(topic.platform, topic.title) : "");

/** 搜索完成后才值得存；各步骤成功与关闭弹窗时调用，LRU 保留最近 N 条 */
const saveFlowSnapshot = () => {
    const key = cacheKey(props.topic);
    if (!key || !research.value) return;
    const all = readFlowCacheAll();
    all[key] = {
        savedAt: Date.now(),
        research: research.value,
        insight: insight.value,
        personaId: personaId.value,
        analysis: analysis.value,
        opt: { ...opt, materials: [...opt.materials] },
        script: script.value,
        editedScript: editedScript.value,
    };
    const keys = Object.keys(all);
    if (keys.length > FLOW_CACHE_LIMIT) {
        keys.sort((a, b) => (all[a]?.savedAt || 0) - (all[b]?.savedAt || 0));
        keys.slice(0, keys.length - FLOW_CACHE_LIMIT).forEach((k) => delete all[k]);
    }
    try {
        uni.setStorageSync(FLOW_CACHE_KEY, all);
        // 缓存落盘后才点亮热榜，避免当前屏已分析、刷新后又灭
        emitAnalyzed();
    } catch {
        // 存储超限：只保留当前一条重试，再失败放弃缓存
        try {
            uni.setStorageSync(FLOW_CACHE_KEY, { [key]: all[key] });
            emitAnalyzed();
        } catch {
            /* 放弃缓存，不改热榜状态 */
        }
    }
};

const removeFlowCacheEntry = () => {
    const key = cacheKey(props.topic);
    if (!key) return;
    const all = readFlowCacheAll();
    if (all[key]) {
        delete all[key];
        try {
            uni.setStorageSync(FLOW_CACHE_KEY, all);
        } catch {
            /* 忽略 */
        }
    }
};

/** 还原是「回放」而非用户改设置：抑制「目的/方向等变更即作废文案」的 watch，
 *  否则 Object.assign(opt) 触发 watch 会把刚还原的文案清空（返回再进入后文案丢失的根因） */
let suppressScriptReset = false;
/** 当前文案末尾已带的引导语（生成/还原时记录），引导语输入框改动后据此替换文案结尾，保持同步 */
let syncedCta = "";
const syncCtaIntoScript = (nextCta: string) => {
    const next = nextCta.trim();
    if (next === syncedCta) return;
    let body = editedScript.value.replace(/\s+$/, "");
    if (syncedCta) {
        // AI 可能在引导语后补了标点，匹配时一并去掉
        const escaped = syncedCta.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        body = body.replace(new RegExp(`\\s*${escaped}[\\s。！!.～~]*$`), "");
    }
    editedScript.value = next ? (body ? `${body}\n${next}` : next) : body;
    syncedCta = next;
};
const withScriptResetSuppressed = (restore: () => void) => {
    suppressScriptReset = true;
    try {
        restore();
    } finally {
        // watch 在同一 tick 的 flush 阶段执行，nextTick 后释放才能盖住本次变更
        nextTick(() => {
            suppressScriptReset = false;
        });
    }
};

/** 还原上次记录：直接回到上次停留的步骤 */
const restoreFlowFromCache = (cached: Record<string, any>) => {
    withScriptResetSuppressed(() => {
        research.value = cached.research || null;
        insight.value = cached.insight || null;
        personaId.value = cached.personaId || null;
        analysis.value = cached.analysis || null;
        script.value = cached.script || null;
        editedScript.value = String(cached.editedScript ?? cached.script?.script ?? "");
        syncedCta = String(cached.opt?.cta || "").trim();
        if (cached.opt && typeof cached.opt === "object") {
            Object.assign(opt, cached.opt, {
                materials: Array.isArray(cached.opt.materials) ? [...cached.opt.materials] : [],
                // 旧版本缓存没有这几个字段，缺省回落人设模式，避免 undefined 让模式判断失效
                persona_mode: Number(cached.opt.persona_mode) || HotspotPersonaMode.PERSONA,
                voice_id: Number(cached.opt.voice_id) || 0,
                voice: String(cached.opt.voice || ""),
                music_volume: normalizeMusicVolume(cached.opt.music_volume),
                speech_rate: normalizeSpeechRate(cached.opt.speech_rate),
                clip_template_id: String(cached.opt.clip_template_id || ""),
                music_url: String(cached.opt.music_url || ""),
                music_source: Number(cached.opt.music_source) || HotspotMusicSource.SYSTEM,
                music_name: String(cached.opt.music_name || ""),
                system_music_id: String(cached.opt.system_music_id || ""),
            });
        }
    });
    // 高级设置仍置灰时，回放带回来的口播不能留着，先清再决定是否展开
    if (!stepReady.value) clearOralScript();
    fromCache.value = true;
    cachedAt.value = Number(cached.savedAt || 0);
    // 还原到第三步（已分析、未生成文案）时同样默认展开高级设置
    showAdv.value = stepReady.value && !script.value;
    if (isManualMode.value) {
        // 资产列表要重新拉，但保留缓存里的形象/音色选择
        loadManualAssets(true);
    } else if (personaId.value) {
        loadPersonaAssets(personaId.value, true);
    }
    emitAnalyzed();
    if (!stepReady.value) saveFlowSnapshot();
};

const formatCachedAt = (ts: number) => {
    if (!ts) return "";
    const d = new Date(ts);
    const pad = (n: number) => String(n).padStart(2, "0");
    return `${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const asPlainObject = (value: any): Record<string, any> | null =>
    value && typeof value === "object" && !Array.isArray(value) && Object.keys(value).length ? value : null;

/** 本地缓存 miss 时，从服务端最近一次任务/入库现场还原（不重跑收费的搜索/分析），并回写本地缓存 */
const toResearchPoints = (points: unknown): { label: string; detail: string }[] => {
    if (!Array.isArray(points)) return [];
    return points
        .map((item) => {
            if (typeof item === "string") {
                const text = item.trim();
                return text ? { label: text, detail: "" } : null;
            }
            if (!item || typeof item !== "object") return null;
            const row = item as { label?: string; detail?: string };
            const label = String(row.label || "").trim();
            const detail = String(row.detail || "").trim();
            if (!label && !detail) return null;
            return { label: label || detail, detail: label ? detail : "" };
        })
        .filter((item): item is { label: string; detail: string } => item !== null);
};

const restoreFlowFromServer = (data: Record<string, any>): boolean => {
    const corePoints = toResearchPoints(data.core_points);
    const scriptText = String(data.script || "");
    const summary = String(data.summary || "");
    if (!corePoints.length && !scriptText && !summary) return false;

    const options = asPlainObject(data.options);
    withScriptResetSuppressed(() => {
        research.value = {
            topic: data.topic || props.topic?.title || "",
            summary,
            core_points: corePoints,
            citations: Array.isArray(data.citations) ? data.citations : [],
        };
        analysis.value = asPlainObject(data.analysis);
        const persona = asPlainObject(data.persona);
        personaId.value = Number(persona?.id) || null;
        if (options) {
            Object.assign(opt, options, {
                materials: Array.isArray(options.materials) ? options.materials.map(Number) : [],
                avatar_id: Number(options.avatar_id) || 0,
                voice_id: Number(options.voice_id) || 0,
                persona_mode: Number(options.persona_mode) || HotspotPersonaMode.PERSONA,
                music_volume: normalizeMusicVolume(options.music_volume),
                speech_rate: normalizeSpeechRate(options.speech_rate),
                clip_template_id: String(options.clip_template_id || ""),
                music_url: String(options.music_url || ""),
                music_source: Number(options.music_source) || HotspotMusicSource.SYSTEM,
                music_name: String(options.music_name || ""),
                system_music_id: String(options.system_music_id || ""),
            });
        }
        if (scriptText) {
            script.value = {
                title: String(data.title || props.topic?.title || ""),
                script: scriptText,
                hashtags: Array.isArray(options?.hashtags) ? options?.hashtags : [],
                shots: Array.isArray(options?.shots) ? options?.shots : [],
                material_mode: String(options?.material_mode || opt.material_mode || ""),
            };
            editedScript.value = scriptText;
            syncedCta = String(options?.cta || "").trim();
        }
    });
    // 搜索和分析照常还原；置灰时不把接口口播留在第 4 步，且必须赶在落缓存之前清掉
    if (!stepReady.value) clearOralScript();
    fromCache.value = true;
    const createdTs = Date.parse(String(data.created_at || "").replace(/-/g, "/"));
    cachedAt.value = Number.isFinite(createdTs) ? createdTs : 0;
    showAdv.value = stepReady.value && !script.value;
    if (isManualMode.value) {
        loadManualAssets(true);
    } else if (personaId.value) {
        loadPersonaAssets(personaId.value, true);
    }
    saveFlowSnapshot();
    emitAnalyzed();
    return true;
};

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const CHARS_PER_SEC = 4.2;

const research = ref<Record<string, any> | null>(null);
const researchErr = ref("");
const insight = ref<Record<string, any> | null>(null);
const insightBusy = ref(false);
const personaId = ref<number | null>(null);
const analyzing = ref(false);
const analysis = ref<Record<string, any> | null>(null);
const analyzeErr = ref("");
const showAdv = ref(false);
const script = ref<Record<string, any> | null>(null);
const scriptBusy = ref(false);
const scriptErr = ref("");
const editedScript = ref("");
const creating = ref(false);
const fromCache = ref(false);
const cachedAt = ref(0);
const stockExists = ref(false);

const MATERIAL_PAGE_SIZE = 30;

// origin_voice_* 只有「不选人设」的自选形象才带，人设形象列表没有这两个字段
const avatarList = ref<
    {
        id: number;
        name: string;
        img: string;
        origin_voice_available?: boolean;
        origin_voice_preview_url?: string;
    }[]
>([]);
const avatarLoading = ref(false);
/** 素材总时长限制（秒），与混剪模块同一配置：视频按实际时长、图片按固定秒数/张 */
const MATERIAL_DURATION_LIMIT = Number(montageConfig.materialTotalDuration) * 60;

/** material_type：1=视频 2=图片（与后端 Material::MATERIAL_TYPE_* 一致） */
type ClipMaterial = { id: number; name: string; img: string; material_type: number; duration: number };
const materialList = ref<ClipMaterial[]>([]);
const materialLoading = ref(false);
const materialLoadingMore = ref(false);
const materialPage = ref(1);
const materialFinished = ref(false);

const opt = reactive({
    goal: "traffic",
    direction: "观点输出",
    persona_mode: HotspotPersonaMode.PERSONA as number,
    material_mode: "ai_persona",
    duration_sec: 60,
    product: "",
    cta: "",
    video_type: HotspotVideoType.DIGITAL as string,
    avatar: "",
    avatar_id: 0,
    voice: "",
    voice_id: 0,
    music_volume: HOTSPOT_MUSIC_VOLUME_DEFAULT,
    speech_rate: HOTSPOT_SPEECH_RATE_DEFAULT,
    clip_template_id: "",
    music_url: "",
    // 来源与曲名一并进 opt：只存 music_url 的话，「选了自传但还没传」和「要系统随机」
    // 两种状态回到页面时长得一模一样，曲名也会丢
    music_source: HotspotMusicSource.SYSTEM as number,
    music_name: "",
    system_music_id: "",
    materials: [] as number[],
});

/** 不选人设：跳过人设契合分析，形象/音色自选，素材固定 AI 找素材（与爆款复刻洗稿模式一致） */
const isManualMode = computed(() => opt.persona_mode === HotspotPersonaMode.MANUAL);

const personaModeOptions = HOTSPOT_PERSONA_MODE_OPTIONS;

type HotspotVoice = {
    id: number;
    name: string;
    preview_url: string;
    provider: string;
    speech_rate_supported?: boolean;
};
type HotspotClipTemplate = { id: string; name: string; cover_url: string; demo_url?: string };

/** 不选人设时可自选的音色（形象共用 avatarList，两套模式渲染一致） */
const voiceList = ref<HotspotVoice[]>([]);
const clipTemplateMap = ref<Record<string, HotspotClipTemplate[]>>({});
const manualAssetLoading = ref(false);

/** 形象加载态两套模式各一个来源，模板统一用这个判断 */
const assetLoading = computed(() => (isManualMode.value ? manualAssetLoading.value : avatarLoading.value));

/** 音量/语速：与人设合成配置同口径（音量 0~1、语速 0.5~2，均保留一位小数）
 *  滑块用 uni 原生 slider（整数刻度：音量 0~10、语速 5~20），不用 u-slider */
const MUSIC_VOLUME_LABELS = ["静音", "较轻", "适中", "较响", "最大"] as const;
const SPEECH_RATE_LABELS = ["0.5x", "较慢", "标准", "较快", "2x"] as const;
const roundToOne = (value: number): number => Math.round(value * 10) / 10;
const normalizeMusicVolume = (value: unknown): number => {
    const num = Number(value);
    if (!Number.isFinite(num)) return HOTSPOT_MUSIC_VOLUME_DEFAULT;
    return Math.min(1, Math.max(0, roundToOne(num)));
};
const normalizeSpeechRate = (value: unknown): number => {
    const num = Number(value);
    if (!Number.isFinite(num)) return HOTSPOT_SPEECH_RATE_DEFAULT;
    return Math.min(2, Math.max(0.5, roundToOne(num)));
};
const musicVolumeStep = computed(() => Math.round(opt.music_volume * 10));
const speechRateStep = computed(() => Math.round(opt.speech_rate * 10));
const musicVolumeText = computed(() =>
    opt.music_volume <= 0 ? "静音" : `${Math.round(opt.music_volume * 100)}%`,
);
const onMusicVolumeChange = (e: any) => {
    opt.music_volume = normalizeMusicVolume(Number(e?.detail?.value ?? 0) / 10);
};
const onSpeechRateChange = (e: any) => {
    opt.speech_rate = normalizeSpeechRate(Number(e?.detail?.value ?? 10) / 10);
};

/** 形象原音跟着所选形象走：只有数字人口播、且该形象克隆时出了原音才给这个选项
 *  （与爆款复刻洗稿模式同口径，音色库里没有这条记录，后端按形象解析 speakerId） */
const selectedManualAvatar = computed(() => avatarList.value.find((a: any) => a.id === opt.avatar_id));
const originVoiceOption = computed<HotspotVoice | null>(() => {
    if (!isManualMode.value || opt.video_type !== HotspotVideoType.DIGITAL) return null;
    const avatar: any = selectedManualAvatar.value;
    if (!avatar?.origin_voice_available) return null;
    const avatarName = String(avatar.name || "").trim();
    return {
        id: HOTSPOT_ORIGIN_VOICE_ID,
        name: avatarName ? `形象原音·${avatarName}` : "形象原音",
        provider: "shanjian",
        speech_rate_supported: true,
        preview_url: String(avatar.origin_voice_preview_url || ""),
    };
});
const voiceOptions = computed<HotspotVoice[]>(() =>
    originVoiceOption.value ? [originVoiceOption.value, ...voiceList.value] : voiceList.value,
);

/** MiniMax 音色走预渲染音频（audioUrl），闪剪只对 speakerId 路径认 speedRatio，
 *  所以这类音色下语速调了也不生效，直接不给调，免得用户以为设上了 */
const speechRateDisabled = computed(() => {
    const voice = voiceOptions.value.find((v) => v.id === opt.voice_id);
    if (!voice) return false;
    return voice.speech_rate_supported === false || voice.provider === "minimax";
});

/** 换形象或换视频类型后原音可能已不可选，清掉选中态，避免提交一个后端会拒的音色 */
watch([() => opt.avatar_id, () => opt.video_type], () => {
    if (opt.voice_id !== HOTSPOT_ORIGIN_VOICE_ID || originVoiceOption.value) return;
    // 形象列表还没到手时不能判死：还原现场是「opt 先落地、资产后到」，
    // 这一刻 originVoiceOption 恒为 null，照清会把还原出来的「形象原音」误清掉。
    // 资产回来后 loadManualAssets 会按 voiceOptions 再核一次，真失效那次才清。
    if (!avatarList.value.length) return;
    opt.voice_id = 0;
    opt.voice = "";
});

/** 没有克隆音色时别让用户以为只能去声音克隆——先选形象就能用原音 */
const voiceEmptyTip = computed(() =>
    opt.video_type === HotspotVideoType.DIGITAL && !opt.avatar_id
        ? "请先选择上方形象，可直接使用该形象的原音"
        : "暂无可用音色，请先在「声音克隆」中创建音色",
);

/** 音色可能很多，先展示一屏、点了才继续放（与爆款洗稿选型组件同口径） */
const VOICE_PAGE_SIZE = 5;
const voiceShowCount = ref(VOICE_PAGE_SIZE);
const visibleVoices = computed(() => voiceOptions.value.slice(0, voiceShowCount.value));
const hasMoreVoices = computed(() => voiceOptions.value.length > voiceShowCount.value);

/** 剪辑模板：按视频类型取候选，空 id = 自动随机（不指定模板，每次生成随机挑一个风格） */
const clipTemplates = computed<HotspotClipTemplate[]>(() => clipTemplateMap.value[opt.video_type] || []);
const pickClipTemplate = (id: string) => {
    opt.clip_template_id = opt.clip_template_id === id ? "" : id;
};
/** 样片预览：播放键只在有样片时才渲染，这里再兜一层，别弹出一个空播放器 */
const showTemplatePreview = ref(false);
const previewTemplate = ref<HotspotClipTemplate | null>(null);
const previewClipTemplate = (template: HotspotClipTemplate) => {
    if (!template.demo_url) {
        pickClipTemplate(template.id);
        return;
    }
    previewTemplate.value = template;
    showTemplatePreview.value = true;
};
const pickPreviewedTemplate = () => {
    if (previewTemplate.value) opt.clip_template_id = previewTemplate.value.id;
};
// 换视频类型后模板场景变了，旧选择不再适用
watch(
    () => opt.video_type,
    () => {
        if (opt.clip_template_id && !clipTemplates.value.some((t) => t.id === opt.clip_template_id)) {
            opt.clip_template_id = "";
        }
    },
);

/** 背景音乐：系统随机 / 上传我的音乐，来源与曲名都存在 opt 里，跟着现场一起还原 */
const { uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: 1,
    fileAccept: ["mp3", "wav", "m4a", "aac"],
    fileSize: 20,
    onSuccess: (materials: any[]) => {
        const item = materials?.[0];
        if (!item?.url) return;
        opt.music_url = item.url;
        opt.music_name = String(item.name || "").trim() || "已上传音乐";
        opt.music_source = HotspotMusicSource.UPLOAD;
        saveFlowSnapshot();
    },
});
const showSystemMusic = ref(false);
const pickMusicSource = (val: number) => {
    if (opt.music_source === val && val !== HotspotMusicSource.PICK) return;
    opt.music_source = val;
    if (val === HotspotMusicSource.SYSTEM) {
        opt.music_url = "";
        opt.music_name = "";
        opt.system_music_id = "";
    } else if (val === HotspotMusicSource.UPLOAD) {
        opt.system_music_id = "";
        if (!opt.music_url) opt.music_name = "";
    } else if (val === HotspotMusicSource.PICK) {
        opt.music_url = "";
        if (!opt.system_music_id) opt.music_name = "";
        showSystemMusic.value = true;
    }
    saveFlowSnapshot();
};
const onSystemMusic = (value: { id: string; name: string }) => {
    if (!value.id) {
        opt.music_source = HotspotMusicSource.SYSTEM;
        opt.system_music_id = "";
        opt.music_name = "";
    } else {
        opt.music_source = HotspotMusicSource.PICK;
        opt.system_music_id = value.id;
        opt.music_name = value.name;
        opt.music_url = "";
    }
    saveFlowSnapshot();
};
const handleUploadMusic = async () => {
    await uploadAndProcessFiles("file");
    // 通用 useUpload 的失败提示被注释掉了，这里自己兜一下
    const failed = uploadMaterialList.value.find((i: any) => i.status === "fail");
    if (failed && !opt.music_url) uni.$u.toast("音乐上传失败，请重试");
};
/** 还原现场后把音乐来源纠正回实际状态：有曲子必是自传；老缓存没有这两个字段时按 url 兜底 */
const syncMusicSourceFromOpt = () => {
    if (opt.music_url) {
        opt.music_source = HotspotMusicSource.UPLOAD;
        if (!opt.music_name) opt.music_name = "已上传音乐";
        return;
    }
    if (opt.system_music_id || opt.music_source === HotspotMusicSource.PICK) {
        opt.music_source = HotspotMusicSource.PICK;
        opt.music_url = "";
        return;
    }
    if (opt.music_source !== HotspotMusicSource.UPLOAD) {
        opt.music_source = HotspotMusicSource.SYSTEM;
        opt.music_name = "";
        opt.system_music_id = "";
    }
};

/** 契合分析只属于人设模式；不选人设时第 3 步只等搜索完成即可配置 */
const stepReady = computed(() => (isManualMode.value ? !!research.value : !!analysis.value));
/** 第 3 步可配置且已有文案时才展示口播；置灰时文案已被清空 */
const showScript = computed(() => stepReady.value && !!script.value);
/** 高级设置置灰时口播不保留。已经是空的也再赋一次，避免按「有没有字」漏清 */
const clearOralScript = () => {
    script.value = null;
    editedScript.value = "";
    syncedCta = "";
};

const videoTypeOptions = computed(() => [
    {
        key: HotspotVideoType.DIGITAL,
        label: videoTypeLabel(HotspotVideoType.DIGITAL),
        desc: "数字人出镜口播，可搭配素材画面",
        icon: IconDigitalBlue,
    },
    {
        key: HotspotVideoType.CLIPS,
        label: videoTypeLabel(HotspotVideoType.CLIPS),
        desc: "纯素材拼剪配音，无需数字人形象",
        icon: IconClipsBlue,
    },
]);

const subTitle = computed(() => {
    const topic = props.topic;
    if (!topic) return "—";
    const extra = topic.days_on_board
        ? ` · 上榜 ${topic.days_on_board} 天`
        : topic.heat_text
        ? ` · ${topic.heat_text}`
        : "";
    return `${platformLabel(topic.platform)} ${props.periodLabel} · 第 ${topic.rank || "-"} 名${extra}`;
});

const goalLabel = (key: string) => props.optionsData.goals.find((g) => g.key === key)?.label || key;
const materialLabel = (key: string) => props.optionsData.materials.find((m) => m.key === key)?.label || key;
const videoTypeLabel = (key: string) => props.optionsData.video_types.find((v) => v.key === key)?.label || key;

// ────────── 洞察展示 ──────────
const trendBars = computed(() => {
    const trend = (insight.value?.trend || []).slice(-24);
    if (trend.length < 3) return [] as number[];
    const values = trend.map((p: any) => Number(p.value) || 0);
    const min = Math.min(...values);
    const max = Math.max(...values);
    return values.map((v: number) => (max > min ? 12 + ((v - min) / (max - min)) * 88 : 50));
});
const trendRange = computed(() => {
    const trend = (insight.value?.trend || []).slice(-24);
    const pick = (i: number) => String(trend[i]?.time || "").slice(5);
    return { start: pick(0), end: pick(trend.length - 1) };
});
/** 画像比例归一：后端契约是 0-1 小数，若上游改传百分数（>1）按百分数处理 */
const toPercent = (value: number) => {
    const num = Number(value) || 0;
    return Math.max(0, Math.min(100, Math.round(num > 1 ? num : num * 100)));
};
const malePercent = computed(() => {
    const male = (insight.value?.gender || []).find((g: any) => g.name === "男")?.value || 0;
    return toPercent(male);
});
const ageTop = computed(() => [...(insight.value?.age || [])].sort((a: any, b: any) => b.value - a.value).slice(0, 3));
const provinceTop = computed(() =>
    [...(insight.value?.province || [])].sort((a: any, b: any) => b.value - a.value).slice(0, 3),
);
const formatDigg = (count: number) => (count >= 10000 ? (count / 10000).toFixed(1) + "万" : String(count || 0));
const previewInsightVideo = (video: any) => {
    if (!video?.cover) return;
    uni.previewImage({ urls: [video.cover] });
};

/** 画像摘要：喂给结合分析，受众判断从"猜"变成"对数据" */
const portraitSummary = () => {
    const ins = insight.value;
    if (!ins?.gender?.length) return "";
    const gender = ins.gender.map((x: any) => `${x.name}${toPercent(x.value)}%`).join(" ");
    const age = ageTop.value.map((x: any) => `${x.name}岁${toPercent(x.value)}%`).join(" ");
    const province = provinceTop.value.map((x: any) => x.name).join(" ");
    return `性别：${gender}；年龄：${age}；主要地域：${province}`;
};

// ────────── 契合度样式 ──────────
const fitScoreVal = computed(() => Number(analysis.value?.fit_score || 0));
const fitLevelText = computed(() =>
    fitScoreVal.value >= 70 ? "契合度高" : fitScoreVal.value >= 40 ? "有一定关联" : "关联较弱",
);
const fitTextClass = computed(() =>
    fitScoreVal.value >= 70 ? "text-[#16A34A]" : fitScoreVal.value >= 40 ? "text-[#D97706]" : "text-[#EF4444]",
);
const fitBarClass = computed(() =>
    fitScoreVal.value >= 70 ? "bg-[#22C55E]" : fitScoreVal.value >= 40 ? "bg-[#FBBF24]" : "bg-[#F87171]",
);

// ────────── 文案编辑统计 ──────────
const scriptWordCount = computed(() => editedScript.value.replace(/\s/g, "").length);
const scriptEstSec = computed(() => Math.max(1, Math.round(scriptWordCount.value / CHARS_PER_SEC)));

// ────────── 请求链 ──────────
let flowToken = 0;

const resetFlow = () => {
    flowToken += 1;
    research.value = null;
    researchErr.value = "";
    fromCache.value = false;
    insight.value = null;
    insightBusy.value = false;
    personaId.value = null;
    analyzing.value = false;
    analysis.value = null;
    analyzeErr.value = "";
    showAdv.value = false;
    script.value = null;
    scriptBusy.value = false;
    scriptErr.value = "";
    editedScript.value = "";
    syncedCta = "";
    creating.value = false;
    fromCache.value = false;
    cachedAt.value = 0;
    avatarList.value = [];
    voiceList.value = [];
    voiceShowCount.value = VOICE_PAGE_SIZE;
    manualAssetLoading.value = false;
    materialList.value = [];
    materialLoadingMore.value = false;
    materialPage.value = 1;
    materialFinished.value = false;
    Object.assign(opt, {
        goal: "traffic",
        direction: "观点输出",
        persona_mode: HotspotPersonaMode.PERSONA,
        material_mode: "ai_persona",
        duration_sec: 60,
        product: "",
        cta: "",
        video_type: HotspotVideoType.DIGITAL,
        avatar: "",
        avatar_id: 0,
        voice: "",
        voice_id: 0,
        music_volume: HOTSPOT_MUSIC_VOLUME_DEFAULT,
        speech_rate: HOTSPOT_SPEECH_RATE_DEFAULT,
        clip_template_id: "",
        music_url: "",
        music_source: HotspotMusicSource.SYSTEM,
        music_name: "",
        system_music_id: "",
        materials: [],
    });
    clipTemplateMap.value = {};
    syncMusicSourceFromOpt();
    stockExists.value = false;
};

const runResearch = async () => {
    const topic = props.topic;
    if (!topic) return;
    const token = flowToken;
    researchErr.value = "";
    research.value = null;
    try {
        const res = await hotspotResearch({
            topic: topic.title,
            platform: topic.platform,
            category: topic.category || "",
        });
        if (token !== flowToken) return;
        research.value = res;
        saveFlowSnapshot();
    } catch (error: any) {
        if (token !== flowToken) return;
        researchErr.value = String(error?.message || error || "联网搜索失败");
    }
};

/** 丢弃缓存记录，重新走搜索链 */
const reResearch = () => {
    removeFlowCacheEntry();
    resetFlow();
    runResearch();
    loadInsight();
};

const loadInsight = async () => {
    const topic = props.topic;
    if (!topic || topic.platform !== HotspotPlatform.DOUYIN) return;
    const token = flowToken;
    insightBusy.value = true;
    try {
        const res = await getHotspotInsight({ topic: topic.title });
        if (token !== flowToken) return;
        insight.value = res?.found ? res : null;
        saveFlowSnapshot();
    } catch {
        // 洞察是增强信息，失败静默
    } finally {
        if (token === flowToken) insightBusy.value = false;
    }
};

/** 切换人设模式：两套模式的形象来源与素材规则完全不同，切换即清掉上一套的选择与分析结果 */
const switchPersonaMode = (mode: number) => {
    if (opt.persona_mode === mode || analyzing.value) return;
    opt.persona_mode = mode;
    personaId.value = null;
    analysis.value = null;
    analyzeErr.value = "";
    script.value = null;
    scriptErr.value = "";
    scriptBusy.value = false;
    editedScript.value = "";
    syncedCta = "";
    avatarList.value = [];
    voiceList.value = [];
    voiceShowCount.value = VOICE_PAGE_SIZE;
    materialList.value = [];
    materialPage.value = 1;
    materialFinished.value = false;
    materialLoadingMore.value = false;
    opt.avatar = "";
    opt.avatar_id = 0;
    opt.voice = "";
    opt.voice_id = 0;
    opt.music_volume = HOTSPOT_MUSIC_VOLUME_DEFAULT;
    opt.speech_rate = HOTSPOT_SPEECH_RATE_DEFAULT;
    opt.clip_template_id = "";
    opt.music_url = "";
    opt.music_source = HotspotMusicSource.SYSTEM;
    opt.music_name = "";
    opt.system_music_id = "";
    opt.materials = [];
    syncMusicSourceFromOpt();
    if (mode === HotspotPersonaMode.MANUAL) {
        // 无人设归属的本地素材库查不到，素材来源只能是纯 AI 找素材（与后端 normalizeOptions 同口径）
        opt.material_mode = "ai";
        showAdv.value = true;
        loadManualAssets();
    } else {
        opt.material_mode = "ai_persona";
        showAdv.value = false;
    }
    saveFlowSnapshot();
};

/** 拉取不选人设模式下的自选形象与音色（用户自有数字人 + 自有克隆音色）
 *  preserveSelection：还原缓存/服务端现场时保留已选，仅在已失效时清掉 */
const loadManualAssets = async (preserveSelection = false) => {
    const token = flowToken;
    if (!preserveSelection) {
        opt.avatar = "";
        opt.avatar_id = 0;
        opt.voice = "";
        opt.voice_id = 0;
    }
    manualAssetLoading.value = true;
    voiceShowCount.value = VOICE_PAGE_SIZE;
    try {
        const res = await getHotspotGenerationOptions();
        if (token !== flowToken) return;
        avatarList.value = Array.isArray(res?.avatars) ? res.avatars : [];
        voiceList.value = Array.isArray(res?.voices) ? res.voices : [];
        clipTemplateMap.value = res?.clip_templates && typeof res.clip_templates === "object"
            ? res.clip_templates
            : {};
        if (preserveSelection) {
            // 缓存里选的形象/音色已被删除时清掉，避免带失效 id 提交
            if (opt.avatar_id && !avatarList.value.some((a) => a.id === opt.avatar_id)) {
                opt.avatar_id = 0;
                opt.avatar = "";
            }
            // 形象原音不在音色库里，按 voiceOptions 找（形象已失效时上面的 watch 会清掉它）
            const voiceIndex = opt.voice_id ? voiceOptions.value.findIndex((v) => v.id === opt.voice_id) : -1;
            if (opt.voice_id && voiceIndex < 0) {
                opt.voice_id = 0;
                opt.voice = "";
            } else if (voiceIndex >= 0) {
                if (voiceIndex >= voiceShowCount.value) {
                    // 已选音色排在折叠范围外会看不见，补足展示条数
                    voiceShowCount.value = voiceIndex + 1;
                }
                // 还原出来的组合可能是「MiniMax 音色 + 非默认语速」，该链路语速不生效，复位免得显示一个假值
                if (voiceOptions.value[voiceIndex]?.provider === "minimax") {
                    opt.speech_rate = HOTSPOT_SPEECH_RATE_DEFAULT;
                }
            }
            // 模板可能已下架，留着提交后端也会回落自动随机，这里先对齐显示
            if (opt.clip_template_id && !clipTemplates.value.some((t) => t.id === opt.clip_template_id)) {
                opt.clip_template_id = "";
            }
            syncMusicSourceFromOpt();
        }
    } catch {
        if (token !== flowToken) return;
        avatarList.value = [];
        voiceList.value = [];
        clipTemplateMap.value = {};
    } finally {
        if (token === flowToken) manualAssetLoading.value = false;
    }
};

const pickVoice = (voice: { id: number; name: string; provider?: string }) => {
    if (opt.voice_id === voice.id) {
        opt.voice_id = 0;
        opt.voice = "";
        return;
    }
    opt.voice_id = voice.id;
    opt.voice = voice.name;
    // 改选 MiniMax 音色时把语速复位：该链路下发的是预渲染音频，留着非默认值只会让详情页显示一个没生效的数
    if (voice.provider === "minimax") opt.speech_rate = HOTSPOT_SPEECH_RATE_DEFAULT;
};

const pickPersona = async (id: number) => {
    if (analyzing.value || !props.topic || !research.value) return;
    // 会员到期冻结的人设还列在选择页里，但口播文案链路取不到它的画像。
    // 不在这里拦住，用户要一直走到第 4 步才看到报错
    if (props.personas.find((item) => item.id === id)?.enabled === false) {
        uni.$u.toast("该人设已冻结，恢复会员权益后可继续使用");
        return;
    }
    const token = flowToken;
    personaId.value = id;
    analysis.value = null;
    analyzeErr.value = "";
    script.value = null;
    scriptErr.value = "";
    scriptBusy.value = false; // 在途的旧人设文案请求响应会被快照守卫丢弃，这里复位加载态
    editedScript.value = "";
    syncedCta = "";
    analyzing.value = true;
    loadPersonaAssets(id);
    try {
        const res = await hotspotAnalyze({
            topic: props.topic.title,
            platform: props.topic.platform,
            summary: research.value.summary || "",
            core_points: research.value.core_points || [],
            persona: { id },
            portrait: portraitSummary(),
        });
        if (token !== flowToken || personaId.value !== id) return;
        analysis.value = res;
        if (res?.recommended_goal) opt.goal = res.recommended_goal;
        if (res?.recommended_direction) opt.direction = res.recommended_direction;
        // 分析完成即进入第三步，高级设置默认展开让用户直接配置必填项
        showAdv.value = true;
        saveFlowSnapshot();
        emitAnalyzed();
    } catch (error: any) {
        if (token !== flowToken || personaId.value !== id) return;
        analyzeErr.value = String(error?.message || error || "结合分析失败");
    } finally {
        if (token === flowToken && personaId.value === id) analyzing.value = false;
    }
};

/** 选完人设后拉该人设的数字人形象与混剪素材（切换人设时重置已选与分页）
 *  preserveSelection：还原缓存记录时保留已选形象/素材，仅在形象已失效时清掉 */
const loadPersonaAssets = async (id: number, preserveSelection = false) => {
    const token = flowToken;
    if (!preserveSelection) {
        opt.avatar = "";
        opt.avatar_id = 0;
        opt.materials = [];
    }
    avatarLoading.value = true;
    materialLoading.value = true;
    materialLoadingMore.value = false;
    materialPage.value = 1;
    materialFinished.value = false;
    try {
        const list = await getHotspotAvatars({ persona_id: id });
        if (token === flowToken && personaId.value === id) {
            avatarList.value = Array.isArray(list) ? list : [];
            // 缓存里选的形象已被删除时清掉，避免带失效 id 提交
            if (preserveSelection && opt.avatar_id && !avatarList.value.some((a) => a.id === opt.avatar_id)) {
                opt.avatar_id = 0;
                opt.avatar = "";
            }
        }
    } catch {
        if (token === flowToken && personaId.value === id) avatarList.value = [];
    } finally {
        if (token === flowToken && personaId.value === id) avatarLoading.value = false;
    }
    await fetchMaterialPage(id, 1);
    if (preserveSelection && token === flowToken && personaId.value === id) {
        await syncRestoredMaterials(id, token);
    }
    if (token === flowToken && personaId.value === id) materialLoading.value = false;
};

/** 合并去重并保持接口的 id 倒序：回捞进来的已选素材与后续分页会有交集，重复项会撞 :key */
const mergeMaterials = (base: ClipMaterial[], rows: ClipMaterial[]): ClipMaterial[] => {
    const seen = new Set(base.map((row) => Number(row.id)));
    return [...base, ...rows.filter((row) => !seen.has(Number(row.id)))].sort(
        (a, b) => Number(b.id) - Number(a.id),
    );
};

/** 还原出来的已选素材可能已被删除或禁用，人设换了素材模式后也不再可选：
 *  这些 id 不在当前列表里，用户看不见也取消不掉，提交时会被后端整单打回。
 *  按 id 回捞一次，失效的剔掉、仍可用但不在首页的补进列表，让「已选」等于真能提交的那些 */
const syncRestoredMaterials = async (id: number, token: number) => {
    const selected = [...opt.materials];
    if (!selected.length) return;
    try {
        const list = await getHotspotClipMaterials({ persona_id: id, ids: selected.join(",") });
        if (token !== flowToken || personaId.value !== id) return;
        const rows = Array.isArray(list) ? list : [];
        const usable = new Set(rows.map((row: ClipMaterial) => Number(row.id)));
        // 后端若还没带 ids 过滤（前端先发版），返回的是第一页全量，据此剪已选会误删；
        // 出现没请求过的 id 就认定这次回捞不可信，放弃核对
        const requested = new Set(selected.map(Number));
        if ([...usable].some((materialId) => !requested.has(materialId))) return;
        opt.materials = selected.filter((materialId) => usable.has(Number(materialId)));
        materialList.value = mergeMaterials(materialList.value, rows);
        saveFlowSnapshot();
    } catch {
        // 回捞失败不动已选，交给创建时的后端校验兜底
    }
};

/** 素材分页：接口不返回总数，按「返回条数 < 页大小」判定到底 */
const fetchMaterialPage = async (id: number, pageNo: number) => {
    const token = flowToken;
    try {
        const list = await getHotspotClipMaterials({
            persona_id: id,
            page_no: pageNo,
            page_size: MATERIAL_PAGE_SIZE,
        });
        if (token !== flowToken || personaId.value !== id) return;
        const rows = Array.isArray(list) ? list : [];
        materialList.value = pageNo === 1 ? rows : mergeMaterials(materialList.value, rows);
        materialPage.value = pageNo;
        materialFinished.value = rows.length < MATERIAL_PAGE_SIZE;
    } catch {
        if (token !== flowToken || personaId.value !== id) return;
        if (pageNo === 1) {
            materialList.value = [];
            materialFinished.value = true;
        }
        // 加载更多失败不置 finished，保留按钮可重试
    }
};

const loadMoreMaterials = async () => {
    if (materialLoadingMore.value || materialFinished.value || !personaId.value) return;
    materialLoadingMore.value = true;
    await fetchMaterialPage(personaId.value, materialPage.value + 1);
    materialLoadingMore.value = false;
};

const pickAvatar = (avatar: { id: number; name: string }) => {
    if (opt.avatar_id === avatar.id) {
        opt.avatar_id = 0;
        opt.avatar = "";
    } else {
        opt.avatar_id = avatar.id;
        opt.avatar = avatar.name;
    }
};

/** 单个素材折算时长（秒）：视频按实际时长、图片按固定秒数/张（与混剪模块同口径） */
const materialSeconds = (id: number): number => {
    const material = materialList.value.find((item) => item.id === id);
    if (!material) return 0;
    return material.material_type === 1 ? Number(material.duration) || 0 : Number(montageConfig.imageDuration) || 0;
};

/** 「AI找素材」下混剪素材区隐藏，已选素材只留在内存里、不参与提交/校验/展示，
 *  切回人设素材原样恢复：手滑切一下来源不该让用户重挑一遍素材 */
const submittedMaterials = computed(() => (opt.material_mode === "ai" ? [] : opt.materials));

/** 已选素材总时长（秒）—— 展示用，保持素材库原值 */
const selectedMaterialDuration = computed(() =>
    submittedMaterials.value.reduce((total, id) => total + materialSeconds(id), 0),
);

/** 校验口径：视频补一秒截断误差（素材库 duration 是整秒），与后端同一口径 */
const materialBudgetSeconds = (id: number): number => {
    const material = materialList.value.find((item) => item.id === id);
    if (!material) return 0;
    return calcMaterialDurationBudget(material.material_type !== 1, material.duration);
};

const selectedMaterialBudget = computed(() =>
    submittedMaterials.value.reduce((total, id) => total + materialBudgetSeconds(id), 0),
);

const toggleMaterial = (id: number) => {
    const index = opt.materials.indexOf(id);
    if (index >= 0) {
        opt.materials.splice(index, 1);
        return;
    }
    if (selectedMaterialBudget.value + materialBudgetSeconds(id) > MATERIAL_DURATION_LIMIT) {
        uni.$u.toast(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟，无法生成视频`);
        return;
    }
    opt.materials.push(id);
};

const buildOptions = () => ({
    goal: opt.goal,
    direction: opt.direction,
    persona_mode: opt.persona_mode,
    material_mode: opt.material_mode,
    duration_sec: opt.duration_sec,
    product: opt.product.trim(),
    cta: opt.cta.trim(),
    video_type: opt.video_type,
    avatar: opt.avatar,
    avatar_id: opt.avatar_id,
    voice: opt.voice,
    voice_id: opt.voice_id,
    music_volume: opt.music_volume,
    speech_rate: opt.speech_rate,
    // 剪辑模板与自选背景音乐只在「不选人设」下有效，人设模式跟随人设合成配置
    clip_template_id: isManualMode.value ? opt.clip_template_id : "",
    music_url: isManualMode.value && opt.music_source === HotspotMusicSource.UPLOAD ? opt.music_url : "",
    music_source: isManualMode.value ? opt.music_source : HotspotMusicSource.SYSTEM,
    music_name: isManualMode.value ? opt.music_name : "",
    system_music_id:
        isManualMode.value && opt.music_source === HotspotMusicSource.PICK ? opt.system_music_id : "",
    materials: submittedMaterials.value,
});

/** 提交给后端的人设字段：不选人设时必须留空，否则后端会按人设模式解析形象/音色 */
const buildPersonaPayload = () => (isManualMode.value || !personaId.value ? {} : { id: personaId.value });

/** 只快照影响文案内容的设置：视频类型/形象/素材/素材来源只决定画面，
 *  且形象/素材会被资产异步回填改动，纳入快照会误报「设置已变更」白丢已扣费的文案 */
const scriptAffectingOptions = () => {
    const options = buildOptions();
    return JSON.stringify({
        goal: options.goal,
        direction: options.direction,
        // 人设模式决定口播是否带人设口吻，属于影响文案内容的设置
        persona_mode: options.persona_mode,
        duration_sec: options.duration_sec,
        product: options.product,
        cta: options.cta,
    });
};

const runScript = async () => {
    if (scriptBusy.value || !props.topic || !research.value) return;
    if (!isManualMode.value && !personaId.value) return;
    const token = flowToken;
    // 快照发起时的人设与设置：生成期间用户换人设/改设置时，过期响应直接丢弃，
    // 避免旧口吻/旧时长的文案挂到新选择上被提交
    const snapPersonaId = personaId.value;
    const snapOptions = scriptAffectingOptions();
    // 画面建议是按发起时的素材来源写的，生成期间用户切了来源也标注这一个
    const snapMaterialMode = opt.material_mode;
    scriptBusy.value = true;
    scriptErr.value = "";
    try {
        const res = await hotspotScript({
            topic: props.topic.title,
            platform: props.topic.platform,
            core_points: research.value.core_points || [],
            summary: research.value.summary || "",
            persona: buildPersonaPayload(),
            analysis: analysis.value || {},
            options: buildOptions(),
        });
        if (token !== flowToken || personaId.value !== snapPersonaId) return;
        if (scriptAffectingOptions() !== snapOptions) {
            uni.$u.toast("设置已变更，请重新生成文案");
            return;
        }
        // 记下生成时的素材来源：之后切素材来源不作废文案，画面建议仍按生成时的模式标注
        script.value = res ? { ...res, material_mode: snapMaterialMode } : null;
        editedScript.value = String(res?.script || "");
        syncedCta = opt.cta.trim();
        saveFlowSnapshot();
    } catch (error: any) {
        if (token !== flowToken || personaId.value !== snapPersonaId) return;
        scriptErr.value = String(error?.message || error || "文案生成失败");
    } finally {
        if (token === flowToken && personaId.value === snapPersonaId) scriptBusy.value = false;
    }
};

/** 生成视频前的硬性校验（与后端 assertCreateOptions 同口径） */
/** 混剪素材必选：纯人设素材（数字人口播/素材混剪均需用户提供）；「AI+人设素材」需用户提供人设素材 */
const materialRequired = computed(
    () => !isManualMode.value && (opt.material_mode === "ai_persona" || opt.material_mode === "persona"),
);

const videoGate = computed(() => {
    if (isManualMode.value) {
        // 不选人设：形象与音色互不捆绑，各自单独必选（与后端 assertCreateOptions 同口径）
        if (opt.video_type === HotspotVideoType.DIGITAL && !opt.avatar_id) {
            return "请先选择数字人形象";
        }
        if (!opt.voice_id) return "请先选择音色";
        // 选了自传音乐却没传：默默按系统音乐出片等于把用户的选择吞了（与后端 assertCreateOptions 同口径）
        if (opt.music_source === HotspotMusicSource.UPLOAD && !opt.music_url) {
            return "请先上传背景音乐";
        }
        if (opt.music_source === HotspotMusicSource.PICK && !opt.system_music_id) {
            return "请先选择一首系统音乐，或改回系统随机";
        }
        return "";
    }
    if (opt.video_type === HotspotVideoType.DIGITAL && !opt.avatar_id && !opt.avatar) {
        return "请先选择数字人形象";
    }
    if (materialRequired.value && !submittedMaterials.value.length) {
        return opt.material_mode === "ai_persona" ? "「AI+人设素材」需选择人设素材" : "纯人设素材需选择混剪素材";
    }
    // 兜底：缓存还原等路径带入的超限组合（正常选择在 toggleMaterial 已拦截）
    if (selectedMaterialBudget.value > MATERIAL_DURATION_LIMIT) {
        return `素材总时长不能超过${montageConfig.materialTotalDuration}分钟`;
    }
    return "";
});

const costBadge = computed(() => {
    const cost = props.optionsData.costs?.[opt.video_type];
    if (!cost || !Number(cost.score)) return "";
    return `消耗${Number(cost.score).toFixed(2)}${cost.unit || "算力/秒"}`;
});

const PERSONA_REQUIRED_HINT = "请先选择人设";
const STOCK_PERSONA_REQUIRED_HINT = "请选择人设";
const STOCK_ID_NONE = 0;
const STOCK_ACTION_ADD = "入库";
const STOCK_ACTION_UPDATE = "更新入库";
const STOCK_MSG_ADD = "入库成功";
const STOCK_MSG_UPDATE = "更新入库成功";

const showStockBtn = computed(() => !isManualMode.value && !!research.value && !researchErr.value);
let stockQueryToken = 0;

const queryStockDetail = async () => {
    const topic = props.topic;
    if (!props.modelValue || !topic) {
        stockExists.value = false;
        return;
    }
    const token = ++stockQueryToken;
    try {
        const res = await getHotspotStockDetail({
            topic: topic.title,
            platform: topic.platform,
            persona_id: Number(personaId.value) || STOCK_ID_NONE,
        });
        if (token !== stockQueryToken) return;
        stockExists.value = Boolean(res?.found);
    } catch {
        if (token !== stockQueryToken) return;
        stockExists.value = false;
    }
};

const toCorePointTexts = (points: unknown): string[] => {
    if (!Array.isArray(points)) return [];
    return points
        .map((item) => {
            if (typeof item === "string") return item.trim();
            if (!item || typeof item !== "object") return "";
            const row = item as { label?: string; detail?: string };
            const label = String(row.label || "").trim();
            const detail = String(row.detail || "").trim();
            if (label && detail) return `${label}：${detail}`;
            return label || detail;
        })
        .filter(Boolean);
};

const buildStockAnalysis = (): Record<string, any> => {
    const src = analysis.value;
    if (!src) return {};
    return {
        fit_score: Number(src.fit_score) || 0,
        hooks: Array.isArray(src.hooks) ? src.hooks : [],
        risks: Array.isArray(src.risks) ? src.risks : [],
        recommended_goal: String(src.recommended_goal || opt.goal || ""),
        recommended_direction: String(src.recommended_direction || opt.direction || ""),
    };
};

const buildStockPayload = () => {
    const topic = props.topic;
    const payload: Record<string, any> = {
        persona_id: Number(personaId.value) || STOCK_ID_NONE,
        topic: topic?.title || "",
        platform: topic?.platform || "",
        analysis_id: STOCK_ID_NONE,
    };
    // 口播区未展示时 editedScript 可能还是上一个人设的草稿，入库和更新入库都不提交
    const scriptText = script.value
        ? editedScript.value.trim() || String(script.value.script || "").trim()
        : "";
    if (!scriptText) {
        payload.title = "";
        payload.script = "";
        payload.summary = String(research.value?.summary || "");
        payload.core_points = toCorePointTexts(research.value?.core_points);
        payload.analysis = buildStockAnalysis();
        return payload;
    }
    payload.creation_id = STOCK_ID_NONE;
    payload.title = String(script.value?.title || topic?.title || "");
    payload.script = scriptText;
    payload.summary = String(research.value?.summary || "");
    payload.core_points = toCorePointTexts(research.value?.core_points);
    payload.analysis = buildStockAnalysis();
    return payload;
};

const { lockFn: handleStock, isLock: stocking } = useLockFn(async () => {
    if (!showStockBtn.value || !props.topic || analyzing.value) return;
    if (!Number(personaId.value)) {
        uni.$u.toast(STOCK_PERSONA_REQUIRED_HINT);
        return;
    }
    try {
        const res = await addHotspotStock(buildStockPayload());
        stockExists.value = true;
        emitAnalyzed();
        uni.$u.toast(res?.updated ? STOCK_MSG_UPDATE : STOCK_MSG_ADD);
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "入库失败");
    }
});

const stockBtnText = computed(() => {
    const label = stockExists.value ? STOCK_ACTION_UPDATE : STOCK_ACTION_ADD;
    return stocking.value ? `${label}中…` : label;
});

/** 热点分析进行中时禁止入库/更新，避免把未完成的分析结果写进热点库 */
const stockBtnDisabled = computed(() => analyzing.value || stocking.value);

const mainBtn = computed(() => {
    // gate=true：必填项未操作，按钮灰显但可点，点击展开高级设置并提示
    if (researchErr.value) return { text: "重试搜索", disabled: false, cost: "" };
    if (!research.value) return { text: "搜索中…", disabled: true, cost: "" };
    if (!isManualMode.value) {
        if (analyzing.value) return { text: "分析中…", disabled: true, cost: "" };
        if (!personaId.value) return { text: PERSONA_REQUIRED_HINT, disabled: true, cost: "" };
        if (analyzeErr.value) return { text: "重试分析", disabled: false, cost: "" };
    }
    if (showScript.value) {
        // 重新生成期间禁止用旧文案去生成视频，避免新文案回来前提交
        if (scriptBusy.value) return { text: "口播文案重新生成中…", disabled: true, cost: "" };
        if (videoGate.value) return { text: videoGate.value, disabled: false, gate: true, cost: "" };
        return {
            text: creating.value ? "创建中…" : "开始生成视频",
            disabled: creating.value,
            cost: costBadge.value,
        };
    }
    // 第三步高级设置有必填项未操作时，按钮先提示必填，而不是直接显示第四步的「确认设置，生成文案」
    if (!scriptBusy.value && videoGate.value) {
        return { text: `高级设置：${videoGate.value}`, disabled: false, gate: true, cost: "" };
    }
    return {
        text: scriptBusy.value ? "生成中…" : "确认设置，生成文案",
        disabled: scriptBusy.value,
        cost: "",
    };
});

const handleMainAction = () => {
    if (mainBtn.value.disabled) return;
    if (researchErr.value) {
        runResearch();
        loadInsight();
        return;
    }
    if (!isManualMode.value && analyzeErr.value && personaId.value) {
        pickPersona(personaId.value);
        return;
    }
    if (videoGate.value) {
        showAdv.value = true;
        uni.$u.toast(videoGate.value);
        return;
    }
    if (showScript.value) {
        startVideo();
        return;
    }
    // 置灰时口播应已清空。再挡一层，避免漏清的回放文案被当成未生成重跑并扣费
    if (script.value) return;
    runScript();
};

const startVideo = async () => {
    if (creating.value || !props.topic || !script.value) return;
    if (!isManualMode.value && !personaId.value) return;
    const finalScript = editedScript.value.trim();
    if (!finalScript) {
        uni.$u.toast("口播文案不能为空");
        return;
    }
    creating.value = true;
    try {
        const task = await addHotspotTask({
            topic: props.topic.title,
            platform: props.topic.platform,
            title: script.value.title || props.topic.title,
            script: finalScript,
            hashtags: script.value.hashtags || [],
            shots: script.value.shots || [],
            persona: buildPersonaPayload(),
            core_points: research.value?.core_points || [],
            citations: research.value?.citations || [],
            analysis: analysis.value || {},
            options: buildOptions(),
        });
        // 不选人设没有结合分析这一步，落库的「做过」标记来自任务本身，
        // 这里补一次让热榜条目立刻点亮「已分析」，不必等整榜刷新
        emitAnalyzed();
        // 用户在创建期间关掉了面板：仅刷新队列，不再强制跳转详情页
        const stillOpen = props.modelValue;
        emit("created", task || {}, stillOpen);
        if (stillOpen) emit("update:modelValue", false);
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "创建任务失败");
    } finally {
        creating.value = false;
    }
};

// 文案已生成后改结尾引导语：不作废文案，直接同步替换文案末尾的引导语
watch(
    () => opt.cta,
    (value) => {
        if (suppressScriptReset || !script.value || scriptBusy.value) return;
        syncCtaIntoScript(String(value || ""));
        saveFlowSnapshot();
    },
);

// 目的/方向/时长影响文案内容，改动后旧文案作废需重新生成；
// 视频类型/形象/素材/素材来源只决定画面不改口播内容，切换不作废文案：
// 还原已分析热点后随手切一下素材来源就丢掉已有（甚至手改过的）文案、还要再花一次算力重生成
watch(
    () => [opt.goal, opt.direction, opt.duration_sec],
    () => {
        // 缓存/服务端还原是「回放」而非用户改设置，此时不作废文案
        if (suppressScriptReset) return;
        if (script.value && !scriptBusy.value) {
            script.value = null;
            editedScript.value = "";
        }
    },
);

// 从可配置变成置灰时清掉口播并落缓存。回放赋值当次若本来就是置灰，由还原函数自己先清再存
watch(stepReady, (ready) => {
    if (ready) return;
    clearOralScript();
    if (props.modelValue && research.value) saveFlowSnapshot();
});

watch(
    () => props.modelValue,
    async (visible) => {
        if (!visible) {
            // 关闭时把最新状态（含高级设置/编辑过的文案）落缓存，下次进入还原到停留步骤
            saveFlowSnapshot();
            return;
        }
        resetFlow();
        // 还原优先级：本地缓存 → 服务端最近任务现场（已分析热点）→ 全新流程
        const cached = readFlowCacheAll()[cacheKey(props.topic)];
        if (cached?.research) {
            restoreFlowFromCache(cached);
            return;
        }
        if (props.topic?.analyzed) {
            const token = flowToken;
            try {
                const last = await getHotspotLastFlow({
                    topic: props.topic.title,
                    platform: props.topic.platform,
                });
                if (token !== flowToken) return;
                if (last?.found && restoreFlowFromServer(last)) return;
            } catch {
                // 还原失败退回全新流程
            }
            if (token !== flowToken) return;
        }
        runResearch();
        loadInsight();
    },
);

watch(
    () => [props.modelValue, props.topic?.title, props.topic?.platform, personaId.value],
    () => {
        queryStockDetail();
    },
);
</script>

<style lang="scss" scoped>
.flow-card {
    @apply bg-white rounded-[24rpx] p-[24rpx];
    box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08);
}

.step-no {
    @apply w-[36rpx] h-[36rpx] rounded-[8rpx] flex items-center justify-center flex-shrink-0;

    text {
        @apply text-[20rpx] font-bold;
    }
}

.adv-label {
    @apply block text-[24rpx] font-bold text-[#374151] mb-[14rpx];
}

.err-box {
    @apply flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#FEF2F2];
    border: 2rpx solid #fecaca;
}

.cta-gradient {
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
}

.flow-cta {
    @apply h-[72rpx] px-[24rpx] rounded-full flex items-center justify-center gap-[10rpx] overflow-hidden;
}

.flow-cta-text {
    @apply min-w-0 text-[24rpx] font-semibold leading-none truncate;
}

.flow-cta-cost {
    @apply shrink-0 h-[36rpx] px-[14rpx] rounded-full flex items-center justify-center;
    background: rgba(255, 255, 255, 0.2);
}
</style>
